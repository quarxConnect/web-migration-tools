<?PHP

  // Make sure the FTP-Extension was loaded
  if (!extension_loaded ('ftp'))
    die ('Error: Missing FTP-Extension' . "\n");
  
  require_once (dirname (__FILE__) . '/../lib/ftpList.php');
  
  // Parse arguements
  if ($argc < 2)
    die ('Error: Too few arguements. Plaese use this like ' . $argv [0] . ' ftp://username:password@host/path/' . "\n");
  
  if (!($info = parse_url ($argv [1])))
    die ('Error: Failed to parse FTP-URL' . "\n");
  
  // Change local path if requested
  if (($argc > 2) && !chdir ($argv [2]))
    die ('Error: Could not chdir() to ' . $argv [2] . "\n");
    
  // Check if we have a host in URL or parse again with forced scheme
  if (!isset ($info ['host']) && (!($info = parse_url ('ftp://' . $argv [1])) || !isset ($info ['host'])))
    die ('Error: Missing host on FTP-URL' . "\n");
  
  // Check if there is an ftp-scheme on the URL
  if (isset ($info ['scheme']) && ($info ['scheme'] != 'ftp'))
    die ('Error: URL-Scheme has to be ftp' . "\n");
  
  // Sanity-Check the URL
  if (isset ($info ['query']))
    echo 'Warning: Query-Part from URL will be ignored!', "\n";
  
  if (isset ($info ['fragment']))
    echo 'Warning: Fragment-Part from URL will be ignored!', "\n";
  
  // Try to connect to host
  if (!is_resource ($conn = ftp_connect ($info ['host'], isset ($info ['port']) ? intval ($info ['port']) : 21)))
    die ('Error: Could not establish FTP-Connection' . "\n");
  
  // Authenticate
  if (isset ($info ['user'])) {
    $user = $info ['user'];
    $pass = strval ($info ['pass']);
  } else {
    $user = 'anonymous';
    $pass = 'support@tiggerswelt.net';
  }
  
  if (!ftp_login ($conn, $user, $pass))
    die ('Error: Login on FTP-Server failed' . "\n");
  
  // Determine OS of remote server
  if (!($ostype = ftp_systype ($conn)))
    die ('Error: Can not determine remote server-type' . "\n");
  
  echo 'Info: Os-Type is ', $ostype, "\n";
  
  // Enable passive mode
  if (!ftp_pasv ($conn, true))
    echo 'Notice: Could not enable passive mode', "\n";
  
  // Change to destination
  $path = $info ['path'];
  $rpath = './';
  $sfile = null;
  
  if (!ftp_chdir ($conn, $path)) {
    $sfile = basename ($path);
    $path = dirname ($path);
    
    if (!ftp_chdir ($conn, $path))
      die ('Error: Given path is invalid' . "\n");
  }
  
  if (substr ($path, -1, 1) != '/')
    $path .= '/';
  
  if (!is_array ($files = ftpList ($conn, $path)))
    die ('Error: Could not retrive list of top-level path' . "\n");
  
  while (count ($files) > 0) {
    $file = array_shift ($files);
    // Check if a single file is requested
    if (($sfile !== null) && ($sfile != $file ['filename']))
      continue;
    
    $lf = $file ['filename'];
    
    // Retrive a file from ftp
    if ($file ['type'] == 'file') {
      // Check if there is no local copy or if the copy has different meta-data
      if (!is_file ($lf) || (filemtime ($lf) < $file ['time']) || (filesize ($lf) != $file ['size'])) {
        echo '  Fetching ', $lf, ' - ', $file ['size'] . ' Bytes', (!is_file ($lf) ? '' : ' (' . filemtime ($lf) . ' < ' . $file ['time'] . ' / ' . filesize ($lf) . ' =? ' . $file ['size'] . ')'), "\n";
        
        $rc = ftp_get ($conn, $lf, $path . $lf, FTP_BINARY);
        touch ($lf, $file ['time']);
      } # else
        # echo '  Keeping local copy of ', $lf, "\n";
    
    // Create a local symlink
    } elseif ($file ['type'] == 'link')
      $rc = symlink ($file ['link-destination'], $lf);
    
    // Queue a sub-directory
    elseif ($file ['type'] == 'dir') {
      if (!($rc = is_dir ($lf)))
        $rc = mkdir ($lf);
      
      if (is_array ($dfiles = ftpList ($conn, $lf, true)))
        $files = array_merge ($dfiles, $files);
    }
    
    // Set local meta-data
    if ($file ['user'] !== null)
      @chown ($lf, $file ['user']);
    
    if ($file ['group'] !== null)
      @chgrp ($lf, $file ['group']);
    
    if ($file ['mode'] !== null)
      chmod ($lf, $file ['mode']);
    
    if ($file ['time'])
      touch ($lf, $file ['time']);
  }

?>