#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}
$pid = $args[0];
$pretty = $arg[1];
$model = entity_load_single('dh_properties', $pid);
$plugin = dh_variables_getPlugins($model);
$plugin->loadProperties($model);
$exp = $plugin->exportOpenMI($model);
$exp_json = $exp[$model->propname];
if ($pretty === 1) {
  $exp_json = json_encode($exp_json, JSON_PRETTY_PRINT);
} else {
  $exp_json = addslashes(json_encode($exp_json));
}
echo $exp_json;

?>