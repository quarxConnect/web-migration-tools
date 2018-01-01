<?PHP

  // Make sure the FTP-Extension was loaded  
  if (!extension_loaded ('ftp'))
    die ('Error: Missing FTP-Extension' . "\n");
  
  require_once (dirname (__FILE__) . '/../lib/ftpList.php');
  
  // Read site.ini
  if (($Info = parse_ini_file ('site.ini', true)) === false)
    die ('Invalid site.ini' . "\n");
  
  // Try to connect to host
  if (!is_resource ($conn = ftp_connect ($Info ['remote']['ftp.host'], isset ($Info ['remote']['ftp.port']) ? intval ($Info ['remote']['ftp.port']) : 21)))
    die ('Error: Could not establish FTP-Connection to ' . $Info ['remote']['ftp.host'] . "\n");
  
  // Authenticate
  if (isset ($Info ['remote']['ftp.user'])) {
    $user = $Info ['remote']['ftp.user'];
    $pass = strval ($Info ['remote']['ftp.pass']);
  } else {
    $user = 'anonymous';
    $pass = 'support@tiggerswelt.net';
  }
  
  if (!ftp_login ($conn, $user, $pass))
    die ('Error: Login on FTP-Server failed' . "\n");
  
  // Enable passive mode
  if (!ftp_pasv ($conn, true))
    trigger_error ('Could not enable passive mode', E_USER_NOTICE);
  
  // Scan FTP-Directories
  $Paths = array ();
  $List = ftpList ($conn, '/', true);
  
  while (count ($List) > 0) {
    // Get the next entry from the list
    $File = array_shift ($List);
    
    if ($File ['type'] == 'file') {
      $Filename = basename ($File ['filename']);
      
      if ($Filename == 'wp-config.php') {
        $Dir = dirname ($File ['filename']);
        $Mask = 0x01;
      } elseif ($Filename == 'version.php') {
        $Dir = dirname (dirname ($File ['filename']));
        $Mask = 0x02;
      } else
        continue;
      
      if (isset ($Paths [$Dir]))
        $Paths [$Dir] |= $Mask;
      else
        $Paths [$Dir] = $Mask;
      
      if ($Paths [$Dir] == 0x03) {
        // Read version.php into memory
        $buf = fopen ('php://temp', 'r+'); 
        
        ftp_fget ($conn, $buf, $Dir . '/wp-includes/version.php', FTP_ASCII, 0); 
        rewind ($buf);
        
        $version = stream_get_contents ($buf); 
        fclose ($buf);
        
        // Try to extract version from memory
        if (!preg_match ('/\$wp_version = \'(.*)\';/', $version, $m))
          $m = array (1 => '(unknown version)');
        
        $version = null;
        
        // Output the found version
        echo 'Wordpress ', $m [1], ' at ', $Dir, "\n";
      }
    
    // Enqueue next directory
    } elseif ($File ['type'] == 'dir')
      $List = array_merge (ftpList ($conn, $File ['filename'], true), $List);
  }

?>