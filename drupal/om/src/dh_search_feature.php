#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

// Is single command line arg?
$hydrocode = isset($args[0]) ? $args[0] : FALSE;
$bundle = isset($args[1]) ? $args[1] : FALSE;
$ftype = isset($args[2]) ? $args[2] : FALSE;
if ($hydrocode === FALSE) {
  error_log("Usage: php dh_search_feature.php hydrocode [bundle] [ftype]");
  die;
}
$hydroid = dh_search_feature($hydrocode, $bundle, $ftype);
error_log("hydroid:" . $hydroid);
echo $hydroid;

?>