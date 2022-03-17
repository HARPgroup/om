<?php

module_load_include('module', 'om');
$a = arg();
$pid = NULL;

if (isset($a[2])) {
  $pid = $a[2];
  $mode = $a[3];
} else {
  echo "Usage: /[pid]/mode(json,single_json,vardef,debug)<br>";
}
if ($pid <> NULL) {
  $prop = entity_load_single('dh_properties', $pid);
  $plugin = dh_variables_getPlugins($prop);
  $exp = $plugin->exportOpenMI($prop);
  $vars = array();
  $plugin->exportVarDefs($prop, $vars);

  //dpm($exp,'Export');
  //dpm($vars,'Vars');

  // tis may no lionger be used, as drupal_export_json handles it?  But we *could* use this if it is better.
  $exp_json = json_encode($exp, JSON_PRETTY_PRINT);
  $vars_json = json_encode($vars, JSON_PRETTY_PRINT);

  switch ($mode) {
    case 'vardef':
      drupal_json_output($vars);
      drupal_exit();
    break;
    case 'debug':
      echo "<pre>$vars_json</pre>";
      echo "<pre>$exp_json</pre>";
    break;
    case 'single_json':
      // this asks for bare json export, not encapsulated as a self-named component 
      $exp = $exp[$prop->propname];
    case 'json':
    default:
      drupal_json_output($exp);
      drupal_exit();
    break;
  }
}
?>