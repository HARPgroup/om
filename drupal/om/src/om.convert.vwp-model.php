#!/user/bin/env drush
<?php
// Convert a vwp-1.0 model to a vahydro-1.0 model 
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

// Defaults
// dH Settings
$bundle = 'watershed';
$ftype = 'vahydro';
// single proc settings
$one_proc = '';
// all batch element settings
$elementid = FALSE;
$hydroid = FALSE;

// Is single command line arg?
if (count($args) >= 2) {
  // Do command line, single element settings
  // set these if doing a single -- will fail if both not set
  // $elementid = 340385; // set these if doing a single
  // $hydroid = 'vahydrosw_wshed_JB0_7050_0000_yarmouth'; 
  $mode = $args[0];
  $hydroid = $args[1];
  if (isset($args[2])) {
    $src_pid = $args[2];
  }
  if (isset($args[3])) {
    $dest_pid = $args[3];
  }
} else {
  // warn and quit
  error_log("Usage: om.convert.vwp-model.php mode[cmd,file] hydroid [src_pid] [dest_pid]");
  die;
}

error_log("elementid = $elementid, hydroid = $hydroid, procname = $one_proc, bundle=$bundle, ftype=$ftype");


// read csv of elementid / hydroid pairs
// find dh feature -- report error if it does not exist
// name = hydroid + vah-1.0
// iterate through properties

if ($elementid == 'file') {
  //$filepath = '/var/www/html/files/vahydro/om_lrsegs.txt';
  //$filepath = '/var/www/html/files/vahydro/om_lrsegs-short.txt';
  // 2nd param should be hydroid
  // To do all model containers, use:
  //   /www/files/vahydro/vahydrosw_om_wshed_elements.tsv
  //  This includes subnodes as well as model nodes for scenario 37
  $filepath = $hydroid;
  $elementid = FALSE;
  $hydroid = FALSE;
  error_log("File requested: $filepath");
}

$om = 'http://localhost/om/get_model.php';

if ($mode == 'file') {
  $data = array();
  $file = fopen($filepath, 'r');
  $header = fgetcsv($file, 0, "\t");
  while ($line = fgetcsv($file, 0, "\t")) {
    $data[] = array_combine($header,$line);
  }
  error_log("File opened with records: " . count($data));
} else {
  $data = array();
  $data[] = array('elementid' => $elementid, 'hydroid' => $hydroid);
}

foreach ($data as $element) {
  
  $hydroid = $element['hydroid'];
  $src_pid = $element['hydroid'];
  $dest_pid = $element['hydroid'];
  $om_feature = entity_load_single('dh_feature', $hydroid);
  error_log("Found $om_feature->name ($om_feature->hydroid)");
  $om_model = FALSE;
  $values = array(
    'entity_type' => 'dh_feature', 
    'featureid' => $hydroid, 
    'propcode'=>'vwp-1.0',
    'varkey' => 'om_water_system_element'
  );
  $src_model = om_get_property($values, 'propcode_singular');
  $values['propcode'] = 'vahydro-1.0';
  $dest_model = om_get_property($values, 'propcode_singular');
  error_log($src_model->pid);
  error_log($dest_model->pid);
  
  if ( is_object($src_model) and is_object($dest_model)) {
    // do the conversion
    $imp1 = om_load_dh_property($src_model, 'impoundment');
    $imp2 = om_load_dh_property($dest_model, 'local_impoundment');
    $pl1 = dh_variables_getPlugins($imp1);
    $pl2 = dh_variables_getPlugins($imp2);
    $pl1->loadProperties($imp1);
    $pl2->loadProperties($imp2);
    //dpm($imp1,'imp1');
    //dpm($imp2,'imp2');
    // copy basic impoundment attributes
    $imp2->unusable_storage->propvalue = $imp1->unusable_storage->propvalue;
    $imp2->initstorage->propvalue = $imp1->initstorage->propvalue;
    $imp2->full_surface_area->propvalue = $imp1->full_surface_area->propvalue;
    $imp2->maxcapacity->propvalue = $imp1->maxcapacity->propvalue;
    
    $imp2->save();
    // storage 
    $st1 = om_load_dh_property($imp1, 'storage_stage_area');
    $st2 = om_load_dh_property($imp2, 'storage_stage_area');
    dpm($st1,'st1');
    $st2->field_dh_matrix = $st1->field_dh_matrix;
    $st2->save();
  }
}

?>