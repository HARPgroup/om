#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');
// go thru list of Local and remote elements without CBP6 Runoffs
// om_elementid, vahydro_pid, varkey, template_id
// Create a clone of an object in OM 
// create a shell on VAHydro 
// add om_element_connection with pull_once from OM to VAHydro 

// test: cmd 210453 4696374 om_model_element 340393 
// drush scr modules/om/src/om_cbp_lrseg_landuse.php cmd N51045_JU1_7690_7490 4745316
// drush scr modules/om/src/om_cbp_lrseg_landuse.php cmd N51161_JU1_7690_7490 4836252
$scenario = 'CFBASE30Y20180615';
$basepath = '/media/NAS/omdata/p6/out/land';
$version = 'p6';
$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

// Is single command line arg?
if (count($args) >= 2) {
  $query_type = $args[0];
  $model_name = $args[1];
  $vahydro_pid = $args[2];
  if (count($args) > 3) {
    $scenario = $args[3];
  }
} else {
  print("Usage: php om_cbp_lrseg_landuse.php query_type model_name/file vahydro_pid \n");
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
    'model_name' => $model_name,
    'vahydro_pid' => $vahydro_pid,
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
  $vahydro_model = om_load_dh_model('pid', $vahydro_pid, $model_name);
  $vahydro_lu = om_load_dh_model('prop_feature', $vahydro_pid, 'landuse', 'om_class_DataMatrix');
  error_log("Found land use element: " . $vahydro_lu->pid);
  error_log("VARID land use element: " . $vahydro_lu->varid);
  $vahydro_lu->keycol1 = '';
  $vahydro_lu->keycol2 = 'luyear';
  $vahydro_lu->lutype1 = 0;
  $vahydro_lu->lutype2 = 1;
  $vahydro_lu->valuetype = 2; // set at as 2-d lookup
  // set model container properties
  $vahydro_model->scenario = $scenario;
  $vahydro_model->version = $version;
  $vahydro_model->landseg = $landseg;
  $vahydro_model->riverseg = $riverseg;
  // set the Runoff File Path
  $vahydro_model->filepath = implode('/', array($basepath, $scenario, 'eos', $landseg . '_0111-0211-0411.csv'));
  // e.g.: /media/NAS/omdata/p6/out/land/CFBASE30Y20180615/eos/N51121_0111-0211-0411.csv
  $plugin = dh_variables_getPlugins($vahydro_lu);
  // Now set the Land use import file path 
  $lupath = "/opt/model/p6/p6_gb604/out/land";
  $lu_filepath = implode('/', array($lupath, $scenario, 'landuse', 'lutable_' . $model_name . '.csv'));
  $vahydro_model->lufile = $lu_filepath;
  $csv = om_readDelimitedFile($lu_filepath);
  error_log("Opening " . $lu_filepath);
  if (is_object($plugin )) {
    //error_log("Checking plugin " . get_class($plugin));
    if (method_exists($plugin, 'setCSVTableField')) {
      error_log("Setting csv" . print_r($csv,1));
      $plugin->setCSVTableField($vahydro_lu, $csv);
    }
  }
  // we save the parent model element, which saves all attached properties, except the landuse matrix
  $vahydro_model->save();
  // save the lu matrix (cannot be embedded ... yet)
  error_log("************************" );
  error_log("************************" );
  error_log("************************" );
  error_log("saving LU as bundle " . $vahydro_lu->bundle);
  //$plugin->convert_attributes_to_dh_props($vahydro_lu);
  //$vahydro_lu->save();
  entity_save('dh_properties', $vahydro_lu);
}

?>
