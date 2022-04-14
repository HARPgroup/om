#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

$cbp6_template = 6564010;
$rseg_hydroid = $a(1); // river segment 
$lseg_hydroid = $a(2); // land-river segment 
// check if a model already exists
$lseg_pid = FALSE;

$lseg_feature = entity_load_single('dh_feature', $lseg_hydroid);
$lsm_info = array(
  'propcode' => 'vahydro-1.0',
  'varid' => 'om_class_cbp_eos_file'
  'entity_type' => 'dh_feature',
  'featureid' => $lseg_hydroid
);
$lseg_model = om_get_property($lsm_info);
error_log("Model pid: " . $lseg_model->pid;
exit;

if ($lseg_model === FALSE) {   
  // if not create it
  $new_lseg_pid = shell_exec("drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $cbp6_template dh_feature $lseg_hydroid "File-Based Land Segment Runoff Template" 1");
  $lseg_model = entity_load_single('dh_properties', $lseg_pid);
}

  
?>