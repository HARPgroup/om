#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');
// test: cmd 210453 4696374 om_model_element 340393 
// drush scr modules/om/src/om_cbp_lrseg_landuse_file_import.php cmd N51045_JU1_7690_7490 4745316
$basepath = '/media/model';
$luname = 'landuse'; // we could opt to add another lu matrix
$last_year = 2050; // final year to append
$vahydro_version = 'vahydro-1.0';
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
    $vahydro_version = $args[5];
  }
  if (count($args) > 6) {
    $basepath = $args[6];
  }
} else {
  error_log("Usage: php om_cbp_lrseg_landuse_file_import.php query_type vahydro_pid/file version scenario [last_year=$last_year] [luname=landuse] [vahydro_version-$vahydro_version] [basepath=/media/model/]\n");
  error_log("Note: To skip appending a final column with a large year, set last_year=0 .");
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
    'version' => $vahydro_version,
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
  $lseg_model = entity_load_single('dh_properties', $vahydro_pid);
  if (!is_object($lseg_model)) {
    error_log("Could not load object with PID $vahydro_pid");
    exit;
  }
  $plugin = dh_variables_getPlugins($lseg_model);
  $plugin->loadProperties($lseg_model);
  $landseg = $lseg_model->landseg->propcode;
  $riverseg = $lseg_model->riverseg->propcode;
  // load the landuse component, or create a blank one if it doesn't exist
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
  $lu_filepath = implode('/', array($basepath, $version, 'out', 'land', $scenario, 'landuse', 'lutable_' . $landseg . '_' . $riverseg . '.csv'));
  $csv = om_readDelimitedFile($lu_filepath);
  // append a final year to insure that we can get land use when the year is outside the dataset 
  $l = 0;
  foreach ($csv as $lno => $luline) {
    $l++; // number of lines handled 
    $final_value = end($luline); // get the last value in the array, which will be either a year or the acreage for that year 
    if ($l == 1) {
      $final_year = end($luline);
      $final_value = $last_year; // we overwrite because we want our desired ending year
    }
    // for each data year, if requested, we copy the last value onto the final_year 
    if ($last_year > $final_year) {
      $csv[$lno][] = $final_value;
    }
  }
  $lu_plugin = dh_variables_getPlugins($vahydro_lu);
  error_log("Opening " . $lu_filepath);
  if (is_object($lu_plugin )) {
    error_log("Checking plugin " . get_class($lu_plugin));
    if (method_exists($lu_plugin, 'setCSVTableField')) {
      error_log("Setting csv" . print_r($csv,1));
      $lu_plugin->setCSVTableField($vahydro_lu, $csv);
    }
  }
  // save the lu matrix (cannot be embedded ... yet)
  $vahydro_lu->save();
}
?>