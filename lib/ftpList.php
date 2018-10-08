<?PHP

  function ftpList ($conn, $path, $pathPrepend = false) {
    // Retrive raw listing
    if (!($list = ftp_rawlist ($conn, $path))) {
      trigger_error ('Could not get ftp-list of ' . $path);
      
      return array ();
    }
    
    if ($pathPrepend && (substr ($path, -1, 1) != '/'))
      $path .= '/';
    
    // Parse the result
    $index = array ();
    
    foreach ($list as $item) {
      // Clean up
      $item = str_replace ("\t", ' ', $item);
      
      while (strpos ($item, '  ') > 0)
        $item = str_replace ('  ', ' ', $item);
      
      $info = explode (' ', $item);
      
      // Get the filename
      $fn = array_pop ($info);
      $ld = null;
      
      if (($fn == '.') || ($fn == '..'))
        continue;
      
      $c = count ($info);
      
      if ($c > 8)
        while (($c > 8) && ($info [--$c] != '->'))
          $fn = array_pop ($info) . ' ' . $fn;
      
      // Handle symlinks
      if ($info [count ($info) - 1] == '->') {
        array_pop ($info);
        $ld = $fn;
        $fn = array_pop ($info);
      }
      
      // Get the mode
      $Mode = array_shift ($info);
      array_shift ($info);
      
      $pMode = 0;
      $bMode = substr ($Mode, -9, 9);
      
      while (strlen ($bMode) > 0) {
        $pMode =
          ($pMode << 3) +
          ($bMode [0] != '-' ? 4 : 0) +
          ($bMode [1] != '-' ? 2 : 0) +
          ($bMode [2] != '-' ? 1 : 0);
        
        $bMode = substr ($bMode, 3);
      }
      
      // Get user/group
      $User = array_shift ($info);
      $Group = array_shift ($info);
      
      // Get the size
      $Size = intval (array_shift ($info));
      $Date = implode (' ', $info);
      
      // Append to index
      $index [] = array (
        'type' => ($Mode [0] == 'd' ? 'dir' : ($Mode [0] == 'l' ? 'link' : 'file')),
        'filename' => ($pathPrepend ? $path : '') . $fn,
        'link-destination' => $ld,
        'user' => $User,
        'group' => $Group,
        'mode' => $pMode,
        'size' => $Size,
        'time' => strtotime ($Date),
      );
    }
    
    return $index;
  }

?>