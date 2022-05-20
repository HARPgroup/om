#!/user/bin/env drush
<?php
module_load_include('inc', 'om');

// get landseg name, model version/ftype, model scenario (for runid)
// create or load land seg model 
// return pid for use in rest call or setprop call 

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}
if (count($args) >= 2) {
  $landseg = arg[0];
  $version = arg[1];
} else {
  error_log("Usage: drush scr met_qa.php landseg-code model-version");
  error_log("Ex: drush scr met_qa.php N51011 cbp6 (or cbp532)");
  echo 0;
  exit;
}
$ftype = $version . '_landseg';
$template_parent = 72575; // cointainer for template modelame 
$template_model_name = "Land Segment Model CBP";
$lseg_hydroid = dh_search_feature($landseg, 'landunit', $ftype);
$lseg_feature = entity_load_single('dh_feature', $lseg_hydroid);
$lsm_info = array(
  'propcode' => $version,
  'varkey' => 'om_model_element',
  'entity_type' => 'dh_feature',
  'featureid' => $lseg_hydroid
);

// **********************************************
// ***** Insure the Land Segment model
// **********************************************
$lseg_model = om_get_property($lsm_info, 'propcode_singular');
$template_parent = entity_load_single('dh_feature', $template_parent);
if ($lseg_model === FALSE) {   
  // if not create it
  $lseg_model = om_copy_properties($template_parent, $lseg_feature, $template_model_name, TRUE, TRUE, 1);
  $lseg_model = entity_load_single('dh_properties', $lseg_model->pid);
  error_log("Created New Model");
} else {
  error_log("Found Model with pid: $lseg_model->pid");
}


?>
