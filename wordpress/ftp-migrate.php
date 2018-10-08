<?PHP

  // Make sure the FTP-Extension was loaded  
  if (!extension_loaded ('ftp'))
    die ('Error: Missing FTP-Extension' . "\n");

  require_once (dirname (__FILE__) . '/../lib/ftpList.php');

  // Read configuration
  if ($argc > 1)
    $Configfile = $argv [1];
  else
    $Configfile = 'site.ini';
  
  if (($Info = parse_ini_file ($Configfile, true)) === false)
    die ('Failed to read configuration (site.ini by default)' . "\n");
  
  // Prepare MySQL-Connection
  $db = new mysqli ($Info ['local']['mysql.host'], $Info ['local']['mysql.user'], $Info ['local']['mysql.pass'], $Info ['local']['mysql.db']);
  
  // Detect old MySQL-Versions
  $r = $db->query ('SHOW COLLATION WHERE Charset="utf8mb4"', MYSQLI_STORE_RESULT);
  
  $haveUTF8mb4 = ($r->num_rows > 0);
  $r->free ();
  
  // Mirror all files from FTP
  $Parameters = array (
    escapeshellarg (dirname (dirname (__FILE__)) . '/ftp/ftp-mirror.php'),
    escapeshellarg ('ftp://' . (isset ($Info ['remote']['ftp.user']) ? $Info ['remote']['ftp.user'] . ':' .  $Info ['remote']['ftp.pass'] : '') . '@' .  $Info ['remote']['ftp.host'] . (isset ($Info ['remote']['ftp.port']) ? ':' . $Info ['remote']['ftp.port'] : '') . $Info ['remote']['ftp.path']),
    escapeshellarg ($Info ['local']['path']),
  );
  
  system ('php ' . implode (' ', $Parameters));
  
  // Read MySQL-Configuration
  if (!is_file ($Info ['local']['path'] . '/wp-config.php'))
    die ('Missing wp-config.php' . "\n");
  
  function get_constant ($Config, $Name) {
    preg_match ('/[^; 	]define[ 	]*\(\'' . $Name . '\'[ 	]*,[ 	]*[\'"](.*)[\'"]\)[ 	]*;/', $Config, $m);
    
    return $m [1];
  }
  
  $Config = file_get_contents ($Info ['local']['path'] . '/wp-config.php');
  
  $dbHost = get_constant ($Config, 'DB_HOST');
  $dbUser = get_constant ($Config, 'DB_USER');
  $dbPass = get_constant ($Config, 'DB_PASSWORD');
  $dbName = get_constant ($Config, 'DB_NAME');
  
  // Import MySQL-Databaase
  $Parameters [0] = escapeshellarg (dirname (dirname (__FILE__)) . '/hybrid-dump/hybrid-dump.php');
  $Parameters [2] = escapeshellarg ('mysql://' . $dbUser . ':' . $dbPass . '@' . $dbHost . '/' . $dbName);
  $Parameters [] = escapeshellarg ($Info ['remote']['http.url']);
  
  if (!$haveUTF8mb4) {
    $Parameters [] = '| replace utf8mb4_unicode_520_ci utf8_unicode_ci';
    $Parameters [] = '| replace utf8mb4 utf8';
  }
  
  $Parameters [] = '| mysql';
  $Parameters [] = escapeshellarg ('-h' . $Info ['local']['mysql.host']);
  $Parameters [] = escapeshellarg ('-u' . $Info ['local']['mysql.user']);
  $Parameters [] = escapeshellarg ('-p' . $Info ['local']['mysql.pass']);
  $Parameters [] = escapeshellarg ($Info ['local']['mysql.db']);
  
  system ('php ' . implode (' ', $Parameters));
  
  // Rewrite wp-config.php
  function set_constant (&$Config, $Name, $Value) {
    $Config = preg_replace ('/([^; 	])define[ 	]*\(\'' . $Name . '\'[ 	]*,[ 	]*([\'"].*[\'"])[ 	]*\)[ 	]*;(.*)/', '$1define (\'' . $Name . '\', ' . var_export ($Value, true) . ');$3', $Config);
    
    return $Config;
  }
  
  set_constant ($Config, 'DB_HOST', $Info ['local']['mysql.host']);
  set_constant ($Config, 'DB_USER', $Info ['local']['mysql.user']);
  set_constant ($Config, 'DB_PASSWORD', $Info ['local']['mysql.pass']);
  set_constant ($Config, 'DB_NAME', $Info ['local']['mysql.db']);
  
  file_put_contents ($Info ['local']['path'] . '/wp-config.php', $Config);
  
  // Rewrite Hostname on database
  preg_match ('/\$table_prefix[ 	]*=[ 	]*[\'"](.*)[\'"];/', $Config, $m);
  
  $db->query ('UPDATE ' . $m [1] . 'options SET option_value="' . $db->real_escape_string ($Info ['local']['http.url']) . '" WHERE option_name IN ("siteurl", "home")');

?>