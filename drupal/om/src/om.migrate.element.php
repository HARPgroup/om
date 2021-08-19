#!/user/bin/env drush
<?php
// Migrate Land-River Segment runoff models from OM to vahydro 2.0
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

// Defaults
// dH Settings
$bundle = 'watershed';
$ftype = 'vahydro';
// single proc settings
$one_proc = 'all';
// all batch element settings
$elementid = FALSE;
$hydrocode = FALSE;
$model_scenario = 'vahydro-1.0';
$model_varkey = 'varcode';
$model_entity_type = 'dh_feature';
// command line class override
$classes = array();
// we no longer need this since the om_translate_to_dh() function can determine if a class is supported or not 
//$classes = array('dataMatrix', 'Equation', 'USGSGageSubComp', 'textField');
//$classes = array('Equation');

// Is single command line arg?
if (count($args) >= 2) {
  // Do command line, single element settings
  // set these if doing a single -- will fail if both not set
  // $elementid = 340385; // set these if doing a single
  // $hydrocode = 'vahydrosw_wshed_JB0_7050_0000_yarmouth';
  $query_type = $args[0];
  $elementid = $args[1];
  $hydrocode = $args[2];
  if (isset($args[3])) {
    $one_proc = $args[3];
  }
  if (isset($args[4])) {
    $bundle = $args[4];
  }
  if (isset($args[5])) {
    $ftype = $args[5];
  }
  if (isset($args[6])) {
    $model_scenario = $args[6];
  }
  if (isset($args[7])) {
    $model_varkey = $args[7];
  }
  if (isset($args[8])) {
    $classes = explode(',',$args[8]);
  }
} else {
  // warn and quit
  error_log("Usage: om.migrate.element.php query_type=[feature]|pid,prop_feature elementid hydrocode [procname=''(all)] [bundle=watershed] [ftype=vahydro] [model_scenario=vahydro-1.0] [model_varkey=varcode (queries for varcode matching OM class)] [classes=" . implode(',', $classes) . "]");
  error_log("If query_type = feature and hydrocode is integer, will assume a hydroid of the parent of the model element has been submitted ");
  error_log("If query_type = pid and hydrocode is integer, will assume a pid for the model element has been submitted ");
  error_log("If query_type = prop_feature and hydrocode is integer, will assume a pid for the model element that is the parent of the model element has been submitted");
  die;
}

error_log("elementid = $elementid, hydrocode = $hydrocode, procname = $one_proc, bundle=$bundle, ftype=$ftype, classes=" . implode(',', $classes) . "]");


// read csv of elementid / hydrocode pairs
// find dh feature -- report error if it does not exist
// name = hydrocode + vah-1.0
// iterate through properties

if ($elementid == 'file') {
  //$filepath = '/var/www/html/files/vahydro/om_lrsegs.txt';
  //$filepath = '/var/www/html/files/vahydro/om_lrsegs-short.txt';
  // 2nd param should be hydrocode
  // To do all model containers, use:
  //   /www/files/vahydro/vahydrosw_om_wshed_elements.tsv
  //  This includes subnodes as well as model nodes for scenario 37
  $filepath = $hydrocode;
  $elementid = FALSE;
  $hydrocode = FALSE;
  error_log("File requested: $filepath");
}


// classes = array() empty mean all

if (!($elementid and $hydrocode)) {
  $data = array();
  $file = fopen($filepath, 'r');
  $header = fgetcsv($file, 0, "\t");
  while ($line = fgetcsv($file, 0, "\t")) {
    $data[] = array_combine($header,$line);
  }
  error_log("File opened with records: " . count($data));
} else {
  $data = array();
  $data[] = array('elementid' => $elementid, 'hydrocode' => $hydrocode);
}

foreach ($data as $element) {
  // data could have elementid on one side, pid on another. or parent_pid to migrate an element to a specific
  // parent model
  // all model elements should have propcode = vahydro-1.0 or some other relevant model scenario indicator
  // propname, propcode, and varkey/varid can all be used for matching elements 
  // similarly, 
  // could pass in parent_elid and custom1 or name of child in order to link parents and children.
  // if (isset($element['parent_elid']) and isset($element['custom1']) ) {
    // $children = getNestedContainersCriteria ($listobject, $elementid, $types, $custom1, $custom2, $ignore);
    // $child = array_shift($children);
    // $elid = $child['elementid'];
  //}
  $elid = $element['elementid'];
  $hydrocode = $element['hydrocode'];
  $classes = isset($element['classes']) ? explode(',', $element['classes']) : $classes;
  // if hydrocode is numeric, we are passing a pid for the target model element in
  if (!isset($element['om_fid']) and is_numeric($hydrocode)) {
    $element['om_fid'] = $hydrocode;
  }
  $model_entity_type = isset($element['model_entity_type']) ? $element['model_entity_type'] : $model_entity_type;

  $om_object = om_get_om_model($elid);
  // Check to see if we have passed in a drupal prop featureid as om_fid
  // otherwise, try to load the drupal object with matching hydrocode
  $om_fid = isset($element['om_fid']) ? $element['om_fid'] : dh_search_feature($hydrocode, $bundle, $ftype);
  if (!$om_fid) {
    error_log("Could not load dh feature with bundle=$bundle, ftype = $ftype and hydrocode = $hydrocode");
    watchdog('om', "Could not load dh feature with ftype = $ftype and hydrocode = $hydrocode");
    // skip to the next one
    continue;
  }
  if (is_object($om_object)) {
    $dh_model = om_load_dh_model($query_type, $om_fid, $om_object->name, 'varcode', $om_object->object_class, $model_scenario);
    if (is_object($dh_model)) {
      om_object2dh($elid, $om_object, $dh_model, $classes, $one_proc);
    }
  } else {
    error_log("Could not find: elementid=$elid ");
  }

}

?>
