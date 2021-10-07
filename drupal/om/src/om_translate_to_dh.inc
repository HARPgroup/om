<?php

// load routines needed for tablefield handling
// New Generic Surface Water User: 340402
module_load_include('module', 'dh_wsp');
global $om, $php_path;
//$om = 'http://deq2.bse.vt.edu/om/get_model.php';
$om = 'http://localhost/om/get_model.php';
$php_path = "/var/www/html/om/";

function om_get_om_model($elid) {
  global $om;
  $uri = $om . "?elementid=$elid";
  error_log("Opening $uri ");
  $json = file_get_contents ($uri);
  //error_log("json:" . $json);
  $om_object = json_decode($json);
  return $om_object;
}

function om_load_dh_model($query_type, $fid, $model_name, $model_varkey = 'om_model_element', $object_class = 'modelContainer', $model_scenario = 'vahydro-1.0') {
  if ($model_varkey == 'varcode') {
    // 
    $model_varkey = dh_varcode2varid($object_class, TRUE);
    $model_varkey = !$model_varkey ? 'om_model_element' : $model_varkey;
    error_log("Object class: " . $object_class . " Using variable key from Varcode query: $model_varkey ");
  }
  $dh_model = FALSE;
  switch($query_type) {
    case 'object':
      $search_mode = 'name';
      $model_entity_type = $fid->entityType();
      $fid = $fid->identifier();
    break;
    
    case 'pid':
    break;
    
    case 'prop_feature':
    $search_mode = 'name';
    $model_entity_type = 'dh_properties';
    error_log("Using query_mode PROP_FEATURE to load model element");
    break;
    
    case 'feature':
    default:
    $search_mode = 'propcode_singular';
    $model_entity_type = 'dh_feature';
    error_log("Using query_mode FEATURE to load model element");
    $om_feature = entity_load_single($model_entity_type, $fid);
    error_log("Found $om_feature->name ($om_feature->hydroid)");
    break;
  }
  if ($query_type == 'pid') {
    // this is a reference to a direct model pid, no need to query
    $dh_model = entity_load_single('dh_properties', $fid);
    error_log("Using query_mode PID to load model element $fid directly.");
  } else {
    error_log("Searching Model " . print_r($values,1));
    $values = array(
      'entity_type' => $model_entity_type,
      'featureid' => $fid,
      'propcode'=>$model_scenario,
      'propname' => $model_name,
      'varkey' => $model_varkey,
    );
    // If the model prop does not exist yet, this will create AND save it.
    $dh_model = om_model_getSetProperty($values, $search_mode); // do not save on creation in case this is a container.
    error_log("Searched mode $search_mode, found Model = $dh_model->propname - $dh_model->propcode ");
  }
  return $dh_model;
}

function om_create_property($values, $singularity = 'name') {
  error_log("Object does not exist...creating " . print_r($values,1));
  $om_model = dh_properties_enforce_singularity($values, $singularity);
  return $om_model;
}

function om_translate_to_dh($om_comp, &$dh_prop) {
  $translated = FALSE;
  error_log("om_translate_to_dh() for $om_comp->object_class ");
  // load object plugin if exists
  $plugin = dh_variables_getPlugins($dh_prop);
  if (is_object($plugin )) {
    error_log("Calling getDefaults on " . get_class($plugin ));
    $default_subprops = $plugin->getDefaults($dh_prop);
    //error_log("Obtained defaults: " . print_r(array_keys($default_subprops),1));
  } else {
    error_log("Could not load plugin for $dh_prop->propname, class= " . get_class($dh_prop ));
  }
  
  switch ($om_comp->object_class) {
    case 'lookupObject':
      // translate this into the new object class dataMatrix 
      $dh_prop->keycol1 = $om_comp->input; // 
      $dh_prop->lutype1 = $om_comp->lutype; // 
      $dh_prop->valuetype = 1; // 
      $om_comp->defaultval = $om_comp->defval; // this class has a non-standard name for defaultval, and also inherits defaultval from the parent class, which results in over-writing during translation.  Set this here to make this progress smoothly later.
      error_log("lookupObject defval prop = $om_comp->defval");
      $table = array();
      // if this has a file set,load it, and translate it into the basic lookupObject format for later translation to a matrix
      if (strlen($om_comp->filepath) > 0) {
        if (file_exists($om_comp->filepath)) {
          $om_comp->lucsv = '';
          $lu_file = readDelimitedFile($om_comp->filepath);
          foreach($lu_file as $thiswua) {
            $key = trim($thiswua[0]);
            $value = trim($thiswua[1]);
            $om_comp->lucsv .= $key . ':' .$value;
          }
        }
      }
      $in = array();
      foreach (explode(",", $om_comp->lucsv) as $pair) {
        list($key, $val) = explode(":", $pair);
        $key = trim($key);
        $val = trim($val);
        $in[] = array($key, $val);
      }
      if (method_exists($plugin, 'setCSVTableField')) {
        //error_log("Translating matrix to tablefield" . print_r((array)$om_comp,1));
        //error_log("Translating matrix to tablefield" . print_r($in,1));
        error_log("Translating matrix to tablefield");
        $plugin->setCSVTableField($dh_prop, $in);
      }
      $translated = TRUE;
    break;
      
        
    case 'dataMatrix':
      $dh_prop->keycol1 = $om_comp->keycol1;
      $dh_prop->keycol2 = $om_comp->keycol2;
      //$dh_prop->field_mytablefield['und'][0]['value'] = serialize($yourtable);
      $in = $om_comp->matrix_rowcol;
      array_walk(
        $in, 
        function(&$item, $key) {
          $item = (array)$item;
        }
      );
      if (method_exists($plugin, 'setCSVTableField')) {
        //error_log("Translating matrix to tablefield" . print_r((array)$om_comp,1));
        //error_log("Translating matrix to tablefield" . print_r($in,1));
        error_log("Translating matrix to tablefield");
        $plugin->setCSVTableField($dh_prop, $in);
      }
      $translated = TRUE;
    break;
    case 'wsp_1tierflowby':
      $dh_prop->keycol1 = $om_comp->tier_var; // different naming convention since OM is not subclass of matrix
      $in = $om_comp->rule_matrix->matrix_rowcol;
      array_walk(
        $in, 
        function(&$item, $key) {
          $item = (array)$item;
        }
      );
      if (method_exists($plugin, 'setCSVTableField')) {
        //error_log("Translating 1tierflowby matrix to tablefield" . print_r((array)$om_comp,1));
        //error_log("Translating matrix to tablefield" . print_r($in,1));
        error_log("Translating 1tierflowby matrix to tablefield");
        $plugin->setCSVTableField($dh_prop, $in);
      }
      $translated = TRUE;
    break;
    case 'broadCastObject':
      if (method_exists($plugin, 'setCSVTableField')) {
        while ( $local_var = array_shift($om_comp->local_varname) ) {
          $remote_var = array_shift($om_comp->broadcast_varname);
          $varpairs[] = array($local_var, $remote_var);
        }
        //error_log("Translating 1tierflowby matrix to tablefield" . print_r((array)$om_comp,1));
        //error_log("Translating matrix to tablefield" . print_r($in,1));
        error_log("Translating broadCastObject lists to tablefield" . print_r($varpairs,1));
        $plugin->setCSVTableField($dh_prop, $varpairs);
      } else {
        error_log("could not find function setCSVTableField");
      }
      $translated = TRUE;
    break;
    case 'Equation':
      error_log("Setting Eq: $om_comp->equation with default $om_comp->defaultval ");
      $dh_prop->propcode = $om_comp->equation;
      $dh_prop->defaultval = $om_comp->defaultval;
      if (strlen($dh_prop->propcode) > 255) {
        // set to proptext
        // @todo: set to proptext of a subcomp-subcomp and handle in plugin
      }
      $translated = TRUE;
    break;
    case 'USGSGageSubComp':
    case 'USGSGageObject':
      $dh_prop->propcode = $om_comp->staid;
      $dh_prop->propvalue = $om_comp->sitetype; #sitetype = 1 - stream gage, 2 - groundwater, 3 - reservoir level;
      $translated = TRUE;
    break;
    case 'textField':
      $dh_prop->propcode = $om_comp->value;
      $translated = TRUE;
    break;
    case 'USGSChannelGeomObject':
    case 'USGSChannelGeomObject_sub':
      $dh_prop->province = $om_comp->province;
      $dh_prop->area = $om_comp->area;
      $dh_prop->drainage_area = $om_comp->drainage_area;
      //error_log("USGSChannelGeomObject set Translated province = $dh_prop->province and drainage_area = $dh_prop->drainage_area");
      $translated = TRUE;
    break;
    case 'wsp_flowby':
    case 'hydroImpSmall':
    case 'Statistic':
      $translated = TRUE;
      error_log("Simple Translation supported for $om_comp->object_class");
    break;
  
  }
  foreach ($default_subprops as $thisprop) {
    if (property_exists($om_comp, $thisprop['propname'])) {
      error_log("Setting $thisprop[propname] to " . $om_comp->{$thisprop['propname']});
      $dh_prop->{$thisprop['propname']} = $om_comp->{$thisprop['propname']};
    }
    // what if the default_subprop is a subcomponent, like a dataMatrix?
    // algorithm: recursion is needed (or could work)
    if (property_exists($om_comp->processors, $thisprop['propname'])) {
      if ($thisprop['propname'] == 'storage_stage_area') {
        $plugin->loadProperties($dh_prop, FALSE, $thisprop['propname'], TRUE);
        $stransok = om_translate_to_dh($om_comp->processors->{$thisprop['propname']}, $dh_prop->storage_stage_area);
        $dh_prop->storage_stage_area->save();
        error_log("Tried to translate storage_stage_area result = $stransok");
        //error_log("proc props = " . print_r(get_object_vars($om_comp->processors->{$thisprop['propname']}),1));
      }
    }
  }
  if ($translated) {
    error_log("Translated $om_comp->object_class to $dh_prop->propname");
  } else {
    error_log("Could not translate -- check to see that OM sets base (given object_class: $om_comp->object_class)");
  }
  return $translated;
}

// Model querying tools
// - Get a model attached to a given vahydro feature
// - Get a sub-model of a model 
// Query Methods:
// - Query by name 
// - Query by code 

function om_get_model_element($entity_type, $featureid, $name = FALSE, $code = FALSE, $varids = FALSE) {
  // @todo: enable varids or even vocab searching
  // return a given model object
  // $fetch_refs handles special case of when the object found is an om_map_model_linkage{
  // searches models on a feature with attribute propval or propcode
  $q = "  select pid from {dh_properties} ";
  $q .= " where entity_type = :entity_type";
  $bind = array(':entity_type' => $entity_type);
  if (!is_array($featureid)) {
    $featureid = array($featureid);
  }
  if (empty($featureid)) {
    return array();
  }
  $fids = implode(', ', $featureid);
  $q .= " and featureid in ($fids) ";
  if ($code) {
    // search a specific model scenario, like vahydro-1.0
    $q .= " and propcode = :code ";
    $bind['code'] = $code;
  }
  if ($name) {
    // search a specific model scenario, like vahydro-1.0
    $q .= " and propname = :name ";
    $bind['name'] = $name;
  }
  # in one of the props attached to this model 
  //error_log($q);
  $result = db_query($q, $bind);
  $pids = $result->fetchCol();
  return $pids;
}

function om_get_vahydro_feature_model($hydrocode, $bundle, $ftype, $model_version = 'vahydro-1.0') {
  // return the final parent feature
}

function om_get_vahydro_model_feature($vahydro_model) {
  // searches up the tree until it finds a dh_feature element 
  // returns false if does not end at a dh_feature 
}

function om_get_search_model_subprops($entity_type, $featureid, $subprop_name, $first = TRUE, $subprop_code = FALSE, $model_propcode = FALSE, $model_propname = FALSE, $model_varkey = FALSE) {
  // searches models on a feature with attribute propval or propcode
  $models = om_get_model_element($entity_type, $featureid, $model_propname, $model_propcode, $pids);
  $submodels = om_get_model_element('dh_properties', $models, $subprop_name, $subprop_code); 
  if (empty($submodels)) {
    return FALSE;
  }
  if ($first) {
    return array_shift($submodels);
  }
  return $submodels;
}

function om_get_dh_varkey($om_comp) {
  $duplicates = array(
    'USGSGageSubComp' => 'USGSGageObject',
    'lookupObject' => 'dataMatrix',
  );
  $object_class = 
    isset($duplicates[$om_comp->object_class]) 
    ? $duplicates[$om_comp->object_class]
    : $om_comp->object_class
  ;
  error_log(" Searching varcode for object_class = $object_class");
  
  $q = "  select varkey from {dh_variabledefinition} ";
  $q .= " where varcode = :om_class and vocabulary = 'om_object_classes'";
  $result = db_query($q, array(':om_class' => $object_class));
  $varkey = $result->fetchField();
  return $varkey;
}

function om_link2dh($elid, &$dh_model) {
  $link_props = array(
    'entity_type' => $dh_model->entityType(),
    'featureid' => $dh_model->pid,
    'propname' => 'om_element_connection',
    'propvalue' => $elid,
    'varkey'=>'om_element_connection'
  );
  // retrieve or create the link
  $om_link = om_model_getSetProperty($link_props, 'varid');
  return $om_link;
}

function om_object2dh($elid, &$om_object, &$dh_model, $classes = array(), $one_proc = 'all') {
  // function om_object2dh($elid, $om_object, $dh_model, $classes = array(), $one_proc = 'all')
  // we now have a saved dH object, with defaults if specified by the class plugin.
  // Now we:
  // 1. disable the element link back save so we can handle everything first.
  // 2. update all properties with their OM object values 
  // 3. Save the dH model element afterwards
  // set the object class value ??
  // Currently this is not used.  The object_class is a function of the plugin, which is set by the 
  // varkey.  We need to either have a lookup or 
  //$dh_model->object_class;
  error_log("Model with pid = $dh_model->pid");
  // first, disable set_remote to prevent looping
  // add the element link
  $link_props = array(
    'entity_type' => 'dh_properties',
    'featureid' => $dh_model->pid,
    'propname' => 'om_element_connection',
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
  // allow data override of link 
  // comment.
  $props = dh_get_dh_propnames('dh_properties', $dh_model->pid);
  error_log("Prop names for this entity: " . print_r($props,1));
  // now add these components.
  $procs = $om_object->processors;
  $procnames = array_keys($procs);
  error_log(count($om_object->processors) . " Processor names for om model: " . print_r($procnames,1));
  foreach ($procs as $procname => $proc) {
    // just do one
    if (($one_proc <> 'all') and ($procname <> $one_proc)) {
      continue;
    }
    $om_object_class = $proc->object_class;
    error_log("Found $procname : $om_object_class");
    if (empty($classes) or in_array($om_object_class, $classes)) {
      $proc_data = array(
        'propname' => $procname,
        'entity_type' => 'dh_properties',
        'featureid' => $dh_model->pid,
        'varkey' => om_get_dh_varkey($proc),
      );
      // load or establish the property (do not save until sure if we've handled it)
      //error_log("Looking for: " . print_r($proc_data,1));
      $prop = om_model_getSetProperty($proc_data, 'name');
      if (is_object($prop)) {
        error_log("Prop loaded for $prop->propname ");
      }
      $translated = om_translate_to_dh($proc, $prop);
      $prop->set_remote = FALSE;
      error_log("Translated $om_object_class = $translated ");
      if ($translated) {
        error_log("Saving $procname $prop->pid $prop->propname .");
        $prop->save();
      } else {
        error_log("Translation failed.");
      }
    } else {
      error_log("Skipping Classes - $om_object_class");
    }
  }
  // Now, save the model element
  // wait: does this already get done when we save the remote link?
  //$dh_model->set_remote = 0;
  // handle object class settings if specified 
  if ($one_proc == 'all') {
    om_translate_to_dh($om_object, $dh_model);
    //error_log("Post save: A: $dh_model->area, DA: $dh_model->drainage_area ");
    $dh_model->save();
  }
  // finally, restore the link setting to enable saves from dH to OM if requested.
  $om_link->propcode = $link_set_remote;
  $om_link->save();
}

function om_copy_element($scenarioid, $template_elementid, $parentid = -1, $new_name = '', $copy_children = 0) {
  global $php_path;
  $cmd = "cd $php_path \n";
  $cmd .= "php fn_copy_element.php $scenarioid $template_elementid $parentid $copy_children";
  if (!empty($new_name)) {
    $cmd .= " \"$new_name\" ";
  }
  //dpm( $php_path, "Exec Path ");
  //dpm( $cmd, "Executing ");
  error_log("$cmd");
  $elid = shell_exec($cmd);
  return $elid;
}

function om_dh_stashlink($entity, $linkname='om_element_connection') {
  $om_link = om_load_dh_property($entity, $linkname);
  $cc = FALSE;
  if (!($om_link->pid === NULL)) {
    $cc = $om_link->propcode;
    $om_link->propcode = '0';
    $om_link->save();
  }
  return $cc;
}

function om_dh_unstashlink($entity, $cc, $linkname='om_element_connection') {
  $om_link = om_load_dh_property($entity, $linkname);
  if (!($om_link->pid === NULL)) {
    $om_link->propcode = $cc;
    $om_link->save();
  }
  return $om_link;
}
