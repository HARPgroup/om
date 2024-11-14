#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

// Is single command line arg?
$vars = array();
$query_type = $args[0];
if (count($args) >= 4) {
  $vars['entity_type'] = $args[1];
  $vars['featureid'] = $args[2];
  $vars['propname'] = $args[3];
} elseif ($query_type == 'pid') {
  $vars['pid'] = $args[1];
} elseif ($query_type == 'rename') {
  $vars['pid'] = $args[1];
  $propname = $args[2];
} else {
  error_log("Usage: drush scr om_saveprop.php query_type entity_type/pid featureid propname");
  error_log("Note: 'file' is not yet enabled");
  error_log("Note: Use featureid = -1 for all ");
  error_log("Note: For query_type = pid only 2 arguments are used, and 2nd argument is the pid of the property to be saved ");
  error_log("Ex: drush pid om_saveprop.php pid 1099999");
  error_log("Note: For query_type = rename 3 arguments are used, pid new_name ");
  error_log("Ex: drush rename om_saveprop.php rename 1099999 bizarro");
  die;
}

if (!in_array($query_type, array('cmd', 'pid', 'rename') )) {
  error_log("Only cmd & pid mode enabled");
  die;
}

$q = "select pid from {dh_properties} ";
switch ($query_type) {
  case 'cmd':
  $q .= " where propname = :propname ";
  $q .= " and entity_type = :entity_type ";
  if ($vars['featureid'] <> 'all') {
    $q .= " and featureid = :featureid ";
  } else {
    unset($vars['featureid']);
  }
  break;
  
  case 'pid':
  case 'rename':
  $q .= " where pid = :pid ";
  break;
}
error_log($q . "vars " . print_r($vars,1));

$rez = db_query($q, $vars);
while ($pid = $rez->fetchColumn()) {
  $prop = entity_load_single('dh_properties', $pid);
  if ($query_type == 'rename') {
    $prop->propname = $propname;
  }
  error_log("saving $prop->propname ($prop->pid)");
  $prop->save();
}


?>
