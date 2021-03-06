<?PHP

  // Redirect error-output
  ini_set ('display_errors', 'stderr');
  
  function err ($msg, $rc = 1) {
    trigger_error ($msg, E_USER_ERROR);
    
    exit ($rc);
  }
  
  // Make sure the FTP-Extension was loaded  
  if (!extension_loaded ('ftp'))
    err ('Missing FTP-Extension');
  
  // Parse arguements
  if ($argc < 4) {
    // Check for configuration-file
    if (($argc != 2) || !is_file ($argv [1]))
      err ('Too few arguements. Plaese use this like ' . $argv [0] . ' ftp://username:password@host/path/ mysql://username:password@host/db http://[username[:password]@]host/path/' . "\n");
    
    // Try to read config-file
    if (($Info = parse_ini_file ($argv [1], true)) === false)
      err ('Failed to read configuration-file' . "\n");
    
    if (!isset ($Info ['remote']))
      err ('No remote-section on configuration' . "\n");
    
    if (!isset ($Info ['remote']['ftp.host']))
      err ('No ftp.host on remote-section' . "\n");
    
    if (!isset ($Info ['remote']['mysql.host']))
      err ('Missing mysql.host on remote-section' . "\n");
    
    if (!isset ($Info ['remote']['mysql.db']))
      err ('Missing mysql.db on remote-section' . "\n");
    
    if (!isset ($Info ['remote']['http.url']))
      err ('Missing http.url on remote-section' . "\n");
    
    // Rewrite command-line
    $argv [1] =
      'ftp://' .
      (isset ($Info ['remote']['ftp.user']) ? $Info ['remote']['ftp.user'] . (isset ($Info ['remote']['ftp.pass']) ? ':' .  $Info ['remote']['ftp.pass'] : '') . '@' : '') .
      $Info ['remote']['ftp.host'] . (isset ($Info ['remote']['ftp.port']) ? ':' . $Info ['remote']['ftp.port'] : '') .
      (isset ($Info ['remote']['ftp.path']) ? $Info ['remote']['ftp.path'] : '/');
    
    $argv [2] =
      'mysql://' .
      (isset ($Info ['remote']['mysql.user']) ? $Info ['remote']['mysql.user'] . (isset ($Info ['remote']['mysql.pass']) ? ':' .  $Info ['remote']['mysql.pass'] : '') . '@' : '') .
      $Info ['remote']['mysql.host'] . (isset ($Info ['remote']['mysql.port']) ? ':' . $Info ['remote']['mysql.port'] : '') .
      '/' . $Info ['remote']['mysql.db'];
    
    $argv [3] = $Info ['remote']['http.url'];
  }
        
  if (!($info = parse_url ($argv [1])))
    err ('Failed to parse FTP-URL' . "\n");
  
  if (!($minfo = parse_url ($argv [2])))
    err ('Failed to parse MySQL-URL' . "\n");
  
  if (!($hinfo = parse_url ($argv [3])))
    err ('Failed to parse HTTP-URL' . "\n");
  
  // Check if there is an ftp-scheme on the URL
  if (isset ($info ['scheme']) && ($info ['scheme'] != 'ftp'))
    err ('URL-Scheme has to be ftp' . "\n");
  
  // Try to connect to host
  if (!is_resource ($conn = ftp_connect ($info ['host'], isset ($info ['port']) ? intval ($info ['port']) : 21)))
    err ('Could not establish FTP-Connection' . "\n");

  // Authenticate
  if (isset ($info ['user'])) {
    $user = $info ['user'];
    $pass = strval ($info ['pass']);
  } else {
    $user = 'anonymous';
    $pass = 'support@tiggerswelt.net';
  }
   
  if (!ftp_login ($conn, $user, $pass))
    err ('Login on FTP-Server failed' . "\n");
  
  // Enable passive mode
  if (!ftp_pasv ($conn, true))
    trigger_error ('Could not enable passive mode', E_USER_NOTICE);
  
  // Change to destination
  $path = $info ['path'];

  if (!ftp_chdir ($conn, $path))
    err ('Given path is invalid' . "\n");
  
  if (!ftp_put ($conn, $path . '/mini-dmp-' . getmypid () . '.php', dirname (__FILE__) . '/mini-dmp.php', FTP_BINARY))
    err ('Could not upload mini-dumper' . "\n");
  
  $http = $argv [3];
  
  if (is_resource ($f = fopen ($http . '/mini-dmp-' . getmypid () . '.php?host=' . urlencode ($minfo ['host']) . (isset ($minfo ['user']) ? '&user=' . urlencode ($minfo ['user']) . (isset ($minfo ['pass']) ? '&pass=' . urlencode ($minfo ['pass']) : '') : '') . '&db=' . urlencode (substr ($minfo ['path'], 1)) , 'r'))) {
    while (!feof ($f))
      echo fread ($f, 4096);
    
    fclose ($f);
  }
  
  if (!ftp_delete ($conn, $path . '/mini-dmp-' . getmypid () . '.php'))
    trigger_error ('Could not remove mini-dumper', E_USER_WARNING);

?>