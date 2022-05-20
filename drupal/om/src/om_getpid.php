#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

// Is single command line arg?
$vars = array();
if (count($args) >= 3) {
  $vars['entity_type'] = $args[0];
  $vars['featureid'] = $args[1];
  $vars['propname'] = $args[2];
  if (isset($args[3])) {
    $vars['propcode'] = $args[3];
  }
} else {
  error_log("Usage: php om_getpid.php entity_type featureid propname [propcode]");
  die;
}
if ( ($vars['propname'] === 'NULL') and (!isset($vars['propcode']) ) {
  error_log("If propname == NULL propcode must be given");
  error_log("Usage: php om_getpid.php entity_type featureid propname [propcode]");
  die;
}

$q = "select pid from {dh_properties} ";
if (!($vars['propname'] === 'NULL')) {
  $q .= " where propname = :propname ";
} else {
  $q .= " where propcode = :propcode ";
}
$q .= " and entity_type = :entity_type ";
if (isset($vars['propcode'])) {
  $q .= " and propcode = :propcode ";
}
$q .= " and featureid = :featureid ";
error_log($q . "vars " . print_r($vars,1));

$rez = db_query($q, $vars);
$pid = $rez->fetchColumn();
error_log("PID:" . $pid);
echo $pid;

?>