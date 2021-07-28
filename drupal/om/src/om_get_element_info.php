#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

// Is single command line arg?
$vars = array();
if (count($args) >= 1) {
  $vars['elid'] = $args[0];
} else {
  error_log("Usage: php om_getmodelpid.php om_elementid ");
  die;
}

$q = "select a.pid, b.propvalue, oc.propcode, c1.propcode as custom1, c2.propcode as custom2
  from dh_properties as a
  left outer join dh_properties as b
  on (a.pid = b.featureid and b.propname = 'om_element_connection' and b.entity_type = 'dh_properties')
  left outer join dh_properties as oc
  on (a.pid = oc.featureid and oc.propname = 'object_class' and oc.entity_type = 'dh_properties')
  left outer join dh_properties as c1
  on (a.pid = c1.featureid and c1.propname = 'custom1' and c1.entity_type = 'dh_properties')
  left outer join dh_properties as c2
  on (a.pid = c2.featureid and c2.propname = 'custom2' and c2.entity_type = 'dh_properties')
  where b.propvalue = :elid ";
error_log($q . "vars " . print_r($vars,1));

$rez = db_query($q, $vars);
$props = $rez->fetchAssoc();
error_log("Info:" . print_r($props,1));
echo implode("\t",$props);

?>