#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');
// test: cmd 210453 4696374 om_model_element 340393 
// drush scr modules/om/src/om_cbp_lrseg_landuse_file_import.php cmd N51045_JU1_7690_7490 4745316
$basepath = '/media/model';
$luname = 'landuse'; // we could opt to add another lu matrix
//$basepath = '/media/NAS/omdata/p6/out/land';
$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

// Is single command line arg?
if (count($args) >= 2) {
  $query_type = $args[0];
  $vahydro_pid = $args[1];
  $version = $args[2];
  $scenario = $args[3];
  if (count($args) > 4) {
    $luname = $args[4];
  }
  if (count($args) > 5) {
    $basepath = $args[5];
  }
} else {
  print("Usage: php om_cbp_lrseg_landuse_file_import.php query_type vahydro_pid/file version scenario [luname=landuse] [basepath=/media/model/]\n");
  die;
}

if ($query_type == 'file') {
  $filepath = $model_name;
  error_log("File requested: $filepath");
  $data = array();
  $file = fopen($filepath, 'r');
  $header = fgetcsv($file, 0, "\t");
  if (count($header) == 0) {
    $header = fgetcsv($file, 0, "\t");
  }
  while ($line = fgetcsv($file, 0, "\t")) {
    
    $data[] = array_combine($header,$line);
  }
  error_log("File opened with records: " . count($data));
  error_log("Header: " . print_r($header,1));
  error_log("Record 1: " . print_r($data[0],1));
} else {
  $data = array();
  $data[] = array(
    'vahydro_pid' => $vahydro_pid,
    'version' => $version,
    'scenario' => $scenario,
    'luname' => $luname,
    'basepath' => $basepath,
  );
}

foreach ($data as $element) {
  $model_name = $element['model_name'];
  $vahydro_pid = $element['vahydro_pid']; 
  $landseg = substr($model_name, 0, 6);
  $riverseg = substr($model_name, 7, 13);
  if (!$vahydro_pid) {
    error_log("Missing model ID cannot process");
    error_log(print_r($element,1));
    die;
  }
  // use getset instead of this below 
  $lu_info = array(
     'varkey' => 'om_class_DataMatrix',
     'propname' => $luname,
     'featureid' => $vahydro_pid,
     'entity_type' => 'dh_properties',
  );
  error_log("Searching for:" . print_r($lu_info, 1));
  $vahydro_lu = om_model_getSetProperty($lu_info, 'name');
  error_log("Found land use element: " . $vahydro_lu->pid);
  error_log("VARID land use element: " . $vahydro_lu->varid);
  if (! ($vahydro_lu->pid > 0) ) {
    // we need to create it 
    error_log("Creating a new matrix named $luname ");
  }
  
  // set the Runoff File Path
  $lu_filepath = implode('/', array($basepath, $version, $scenario, 'land', 'lutable_ ' . $landseg . '.csv'));
  $csv = om_readDelimitedFile($lu_filepath);
  error_log("Opening " . $lu_filepath);
  if (is_object($plugin )) {
    //error_log("Checking plugin " . get_class($plugin));
    if (method_exists($plugin, 'setCSVTableField')) {
      error_log("Setting csv" . print_r($csv,1));
      $plugin->setCSVTableField($vahydro_lu, $csv);
    }
  }
  // save the lu matrix (cannot be embedded ... yet)
  $vahydro_lu->save();
}
?>