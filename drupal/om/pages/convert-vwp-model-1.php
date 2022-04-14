<?php
// Convert a vwp-1.0 model to a vahydro-1.0 model 
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = arg();
dpm($args,'args');
// Defaults
$hydroid = FALSE;

// Is single command line arg?
if (count($args) < 3) {
  // warn and quit
  dsm("Usage: hydroid [src_pid] [dest_pid]");
  //die;
} else {
  $hydroid = $args[2];
  if (isset($args[3])) {
    $src_pid = $args[3];
  }
  if (isset($args[4])) {
    $dest_pid = $args[4];
  }

  $om = 'http://localhost/om/get_model.php';


  $om_feature = entity_load_single('dh_feature', $hydroid);
  dsm("Found $om_feature->name ($om_feature->hydroid)");
  $om_model = FALSE;
  $values = array(
    'entity_type' => 'dh_feature', 
    'featureid' => $hydroid, 
    'propcode'=>'vwp-1.0',
    'varkey' => 'om_water_system_element'
  );
  $src_model = om_get_property($values, 'propcode_singular');
  $values['propcode'] = 'vahydro-1.0';
  $dest_model = om_get_property($values, 'propcode_singular');
  dsm("Copying from model pid = $src_model->pid, to model pid $dest_model->pid");
  $src_plug = dh_variables_getPlugins($src_model);
  $dest_plug = dh_variables_getPlugins($dest_model);

  if ( is_object($src_model) and is_object($dest_model)) {
    // do the conversion
    // flowby
    
    $ft = om_load_dh_property($src_model, 'flowby_type');
    $flowby_name = ($ft->propcode == '1') ? "tiered_flowby" : "simple_flowby";
    $f1 = om_load_dh_property($src_model, $flowby_name, TRUE);
    $f2 = om_load_dh_property($dest_model, 'flowby_current', TRUE);
    // warn if missing a variable 
    if (!in_array($f1->cfb_var->propcode, $dest_local_vars)) {
      dsm("Can No Find Conditional variable for flowby " . $f1->cfb_var->propcode . " on " . $dest_model->propname . " - must manually configure");
    }
    // set the varid then save to load new properties 
    entity_delete('dh_properties', $f2->pid);
    // now use the copy command to get this all 
    // disable pushing on the dest_model till the end 
    $stash = om_dh_stashlink($dest_model);
    om_copy_properties($src_model, $dest_model, "$flowby_name|flowby_current", TRUE, TRUE, TRUE);
    om_dh_unstashlink($dest_model, $stash);
    
    // impoundment (if present)
    $imp1 = om_load_dh_property($src_model, 'impoundment');
    $imp2 = om_load_dh_property($dest_model, 'local_impoundment');
    //dpm($imp1,'imp1');
    //dpm($imp2,'imp2');
    // copy basic impoundment attributes
    if (is_object($imp2)) {
      $pl1 = dh_variables_getPlugins($imp1);
      $pl2 = dh_variables_getPlugins($imp2);
      $pl1->loadProperties($imp1);
      $pl2->loadProperties($imp2);
      $imp2->unusable_storage->propvalue = $imp1->unusable_storage->propvalue;
      $imp2->initstorage->propvalue = $imp1->initstorage->propvalue;
      $imp2->full_surface_area->propvalue = $imp1->full_surface_area->propvalue;
      $imp2->maxcapacity->propvalue = $imp1->maxcapacity->propvalue;
      
      $imp2->save();
      // storage 
      $st1 = om_load_dh_property($imp1, 'storage_stage_area');
      $st2 = om_load_dh_property($imp2, 'storage_stage_area');
      //dpm($st1,'st1');
      $st2->field_dh_matrix = $st1->field_dh_matrix;
      $st2->save();
      
      // copy release which is complex, warn if can not makeout all vars 
      $rt = om_load_dh_property($src_model, 'release_type');
      $cp_r = FALSE;
      //dpm($dest_model,'dest_model');
      $dest_local_vars = $dest_plug->getLocalVars($dest_model);
      //dpm($dest_local_vars,'localvars');
      $release_name = ($rt->propcode == '1') ? "tiered_release" : "simple_release";
      $r1 = om_load_dh_property($src_model, $release_name, TRUE);
      $r2 = om_load_dh_property($dest_model, 'release_current', TRUE);
      // warn if missing a variable 
      if (!in_array($r1->cfb_var->propcode, $dest_local_vars)) {
        dsm("Can No Find Conditional variable" . $r1->cfb_var->propcode . " on " . $dest_model->propname . " - must manually configure");
      }
      // set the varid then save to load new properties 
      $r2->varid = $r1->varid;
      $r2->save();
      $r2 = om_load_dh_property($dest_model, 'release_current', TRUE);
      $r2->cfb_condition->propcode = $r1->cfb_condition->propcode;
      $r2->flowby_eqn->propcode = $r1->flowby_eqn->propcode;
      $r2->cfb_var->propcode = $r1->cfb_var->propcode;
      $r2->exec_hierarch->propvalue = $r1->exec_hierarch->propvalue;
      $r2->varid = $r1->varid;
      $r2->object_class->propcode = $r1->object_class->propcode;
      $r2->save();
    } else {
      dsm("There is no impoundment object on $dest_model->propname... skipping.");
    }
  }
}
?>