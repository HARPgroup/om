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
$classes = array('dataMatrix', 'Equation', 'USGSGageSubComp', 'textField');
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

error_log("elementid = $elementid, hydrocode = $hydrocode, procname = $one_proc, bundle=$bundle, ftype=$ftype");


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

$om = 'http://deq2.bse.vt.edu/om/get_model.php';

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
  // if hydrocode is numeric, we are passing a pid for the target model element in
  if (!isset($element['om_fid']) and is_numeric($hydrocode)) {
    $element['om_fid'] = $hydrocode;
  }
  $uri = $om . "?elementid=$elid";
  $model_entity_type = isset($element['model_entity_type']) ? $element['model_entity_type'] : $model_entity_type;
  error_log("Opening $uri ");
  $json = file_get_contents ($uri);
  //error_log("json:" . $json);
  $om_model_element = json_decode($json);
  // Check to see if we have passed in a drupal prop featureid as om_fid
  // otherwise, try to load the drupal object with matching hydrocode
  $om_fid = isset($element['om_fid']) ? $element['om_fid'] : dh_search_feature($hydrocode, $bundle, $ftype);
  if (!$om_fid) {
    error_log("Could not load dh feature with bundle=$bundle, ftype = $ftype and hydrocode = $hydrocode");
    watchdog('om', "Could not load dh feature with ftype = $ftype and hydrocode = $hydrocode");
    // skip to the next one
    continue;
  }
  if (is_object($om_model_element)) {
    // check the model_varkey 
    // - varcode = search the database for a variable whose varcode matches the OM objectclass of this object 
    // - all others expect the varkey to use 
    if ($model_varkey == 'varcode') {
      // 
      $model_varkey = dh_varcode2varid(get_class($om_model_element), TRUE);
      $model_varkey = !$model_varkey ? 'om_model_element' : $model_varkey;
      error_log("Using variable key from Varcode query: $model_varkey ");
    }
    switch($query_type) {
      case 'pid':
      // this is a reference to a direct model pid, no need to query
      $vahydro_model_element = entity_load_single('dh_properties', $om_fid);
      error_log("Using query_mode PID to load model element directly.");
      break;
      
      case 'prop_feature':
      $model_entity_type = 'dh_properties';
      error_log("Using query_mode PROP_FEATURE to load model element");
      case 'feature':
      default:
      error_log("Using query_mode FEATURE to load model element");
      $om_feature = entity_load_single($model_entity_type, $om_fid);
      error_log("Found $om_feature->name ($om_feature->hydroid)");
      $vahydro_model_element = FALSE;
      $values = array(
        'entity_type' => $model_entity_type,
        'featureid' => $om_fid,
        'propcode'=>$model_scenario,
        'varkey' => $model_varkey,
        'propname' => $om_model_element->name,
      );
      error_log("Searching Model " . print_r($values,1));
      $vahydro_model_element = om_model_getSetProperty($values, 'propcode_singular');
      break;
    }
    error_log("Model = $vahydro_model_element->propname - $vahydro_model_element->propcode ");
    // see if the
    if (is_object($vahydro_model_element)) {
      // set the object class value ??
      // Currently this is not used.  The object_class is a function of the plugin, which is set by the 
      // varkey.  We need to either have a lookup or 
      //$vahydro_model_element->object_class;
      error_log("Model with pid = $vahydro_model_element->pid");
      // first, disable set_remote to prevent looping
      // add the element link
      $link_props = array(
        'entity_type' => 'dh_properties',
        'featureid' => $vahydro_model_element->pid,
        'propname' => 'OM Element Link',
        'propvalue' => $elid,
        'varkey'=>'om_element_connection'
      );
      // retrieve or create the link
      $om_link = om_model_getSetProperty($link_props, 'varid');
      // now, we stash the link set_remote property, since it needs to be disabled here
      //   to prevent saving and then resaving on om
      error_log("om_link PID for this entity: $om_link->pid");
      $link_set_remote = $om_link->propcode;
      $om_link->propcode = '0';
      $om_link->save();
      $props = dh_get_dh_propnames('dh_properties', $vahydro_model_element->pid);
      error_log("Prop names for this entity: " . print_r($props,1));
      // now add these components.
      $procs = $om_model_element->processors;
      $procnames = array_keys($procs);
      error_log(count($om_model_element->processors) . " Processor names for om model: " . print_r($procnames,1));
      foreach ($procs as $procname => $proc) {
        // just do one
        if (($one_proc <> 'all') and ($procname <> $one_proc)) {
          continue;
        }
        $om_model_element_class = $proc->object_class;
        error_log("Found $procname : $om_model_element_class");
        if (empty($classes) or in_array($om_model_element_class, $classes)) {
          $proc_data = array(
            'propname' => $procname,
            'entity_type' => 'dh_properties',
            'featureid' => $vahydro_model_element->pid,
            'varkey' => om_get_dh_varkey($proc),
          );
          // load or establish the property (do not save until sure if we've handled it)
          error_log("Looking for: " . print_r($proc_data,1));
          $prop = om_model_getSetProperty($proc_data, 'name');
          if (is_object($prop)) {
            error_log("Prop loaded for $prop->propname ");
          }
          $translated = om_translate_to_dh($proc, $prop);
          $prop->set_remote = FALSE;
          error_log("Translated $om_model_element_class = $translated ");
          if ($translated) {
            error_log("Saving $procname .");
            $prop->save();
          } else {
            error_log("Translation failed.");
          }
        } else {
          error_log("Skipping Classes - $om_model_element_class");
        }
      }
      $om_link->propcode = $link_set_remote;
      $om_link->save();
    }
  } else {
    error_log("Could not find: elementid=$elid ");
  }

}

?>
