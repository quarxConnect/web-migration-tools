<?PHP

  function dumpTable ($db, $table, $drop = true, $create = true, $data = true) {
    if ($drop)
      echo 'DROP TABLE IF EXISTS `', $table, '`;', "\n";
    
    if ($create) {
      $r = $db->query ('SHOW CREATE TABLE `' . $table . '`');
      $s = $r->fetch_array (MYSQLI_NUM);
      $r->free ();
      
      $s [1] = substr ($s [1], 0, 12) . ' IF NOT EXISTS' . substr ($s [1], 12);
      
      echo $s [1], ";\n\n";
    }
    
    if ($data) {
      // Request all records from the table
      $r = $db->query ('SELECT * FROM `' . $table . '`', MYSQLI_USE_RESULT);
      
      if (!($t = $r->fetch_array (MYSQLI_ASSOC)))
        return;
      
      // Disable key-checks in import
      echo '/*!40000 ALTER TABLE `', $table, '` DISABLE KEYS */';
      
      // Prepare INSERT-Statement
      $Insert = ";\n" . 'INSERT INTO `' . $table . '` (';
      $f = 0;
      
      foreach ($t as $k=>$v)
        $Insert .= ($f++ == 0 ? '' : ',') . '`' . $k . '`';
      
      $Insert .= ') VALUES ';
      
      // Process all records
      $i = 0;
      
      do {
        // Concatenate record with previous one
        echo ($i++ % 100 == 0 ? $Insert : ','), '(';
        
        // Output all cells
        $f = 0;
        
        foreach ($t as $k=>$v) {
          if ($f++ > 0)
            echo ',';
          
          if ($v === null)
            echo 'NULL';
          else
            echo '"', $db->real_escape_string ($v), '"';
        }
        
        echo ')';
        
      } while ($t = $r->fetch_array (MYSQLI_NUM));
      
      $r->free ();
      
      // Finish the last statement and re-enable key-checks
      echo ";\n",
           '/*!40000 ALTER TABLE `', $table, '` ENABLE KEYS */;', "\n\n";
    }
  }
  
  if (isset ($_REQUEST ['host'])) {
    $Host = $_REQUEST ['host'];
    $User = $_REQUEST ['user'];
    $Pass = $_REQUEST ['pass'];
    $DB = $_REQUEST ['db'];
  } else {
    $Host = $argv [1];
    $User = $argv [2];
    $Pass = $argv [3];
    $DB = $argv [4];
  }
  
  // Connect to server
  if (!is_object ($db = mysqli_connect ($Host, $User, $Pass)))
    die ('/* Failed to connect to database */' . "\n");
  
  $db->set_charset ('utf8');
  $db->select_db ($DB);
  
  echo '/*!40101 SET NAMES utf8 */;', "\n",
       '/*!40103 SET TIME_ZONE="+00:00" */;', "\n",
       '/*!40014 SET UNIQUE_CHECKS=0 */;', "\n",
       '/*!40014 SET FOREIGN_KEY_CHECKS=0 */;', "\n",
       '/*!40101 SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO" */;', "\n",
       '/*!40111 SET SQL_NOTES=0 */;', "\n\n";
  
  // Process all tables
  $r = $db->query ('SHOW TABLE STATUS');
  
  while ($t = $r->fetch_array (MYSQLI_NUM))
    dumpTable ($db, $t [0], true, true);
    
  $r->free ();

?>