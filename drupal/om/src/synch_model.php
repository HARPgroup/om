#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}
$pid = $args[0];
$model = entity_load_single('dh_properties', $pid);
$plugin = dh_variables_getPlugins($model);
$plugin->loadProperties($model);
$oc = om_load_dh_property($model, "om_element_connection");
error_log("Synching Data for $model->propname with elid = " . $oc->propvalue);
// now push
if ($oc->propvalue > 0) {
  error_log("Pushing Data");
  $exp = $plugin->exportOpenMI($model);
  dpm($exp,"Using JSON export mode");
  $exp_json = addslashes(json_encode($exp[$model->propname]));
  om_set_element($oc->propvalue, $exp_json);
  error_log("Finished Pushing Data");
}

?>