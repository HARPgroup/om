#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

$cbp6_template = 6564010;
if (count($args) >= 2) {
  $rseg_hydroid = $args[0]; // river segment 
  $lseg_hydroid = $args[1]; // land-river segment 
} else {
  error_log("Usage: php om_create_landseg.php riverseg_hydroid landseg_hydroid");
  die;
}
// check if a model already exists
$lseg_pid = FALSE;

$rseg_feature = entity_load_single('dh_feature', $rseg_hydroid);
$lseg_feature = entity_load_single('dh_feature', $lseg_hydroid);
$lsm_info = array(
  'propcode' => 'vahydro-1.0',
  'varkey' => 'om_class_cbp_eos_file',
  'entity_type' => 'dh_feature',
  'featureid' => $lseg_hydroid
);

$lseg_model = om_get_property($lsm_info, 'all');
if ($lseg_model === FALSE) {   
  // if not create it
  $lseg_model = om_copy_properties($rseg_feature, $lseg_feature, "File-Based Land Segment Runoff Template", TRUE, TRUE, 1);
  $lseg_model = entity_load_single('dh_properties', $lseg_pid);
  error_log("Created New Model");
} else {
  error_log("Found Model with pid: $lseg_model->pid");
}
$lseg_model->landseg = substr($lseg_feature->hydrocode, 0, 6);
$lseg_model->riverseg = substr($lseg_feature->hydrocode, 7);
$lseg_model->propname = $lseg_feature->hydrocode;
$lseg_model->save();
$plugin = dh_variables_getPlugins($lseg_model);
$plugin->loadProperties($lseg_model);
$oc = $lseg_model->om_element_connection;

// now create a model if it doesn't have an om_element_connection 
error_log("OM pid: " . $oc->pid);
error_log("OM elid: " . $oc->propvalue);
  
?>