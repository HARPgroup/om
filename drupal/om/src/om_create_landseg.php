#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');
error_log("Note: this routine is called  create 'landseg' but its actually creating a model for an LRseg");
$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

$basepath = '/media/model';
$cbp6_template = 6564010;
$om_lseg_template_elid = 352129; // we could jsut as easily get this from the template object that we copied
if (count($args) >= 4) {
  $rseg_hydroid = $args[0]; // river segment 
  $lseg_hydroid = $args[1]; // land-river segment 
  $version = $args[2];
  $scenario = $args[3];
  if (count($args) > 4) {
    $basepath = $args[4];
  }
} else {
  error_log("Usage: php om_create_landseg.php riverseg_hydroid lrseg_hydroid version(p6,p532) scenario [basepath=$basepath]");
  exit;
}
// check if a model already exists
$lseg_pid = FALSE;

// **********************************************
// ***** Load the Features Rseg &Lseg
// **********************************************
$rseg_feature = entity_load_single('dh_feature', $rseg_hydroid);
$lseg_feature = entity_load_single('dh_feature', $lseg_hydroid);
$lsm_info = array(
  'propcode' => 'vahydro-1.0',
  'varkey' => 'om_class_cbp_eos_file',
  'entity_type' => 'dh_feature',
  'featureid' => $lseg_hydroid
);


// **********************************************
// ***** Insure the Land Segment model
// **********************************************
$lseg_model = om_get_property($lsm_info, 'propcode_singular');
$cbp6_template_model = entity_load_single('dh_properties', $cbp6_template);
if ($lseg_model === FALSE) {   
  // if not create it
  $lseg_model = om_copy_properties($cbp6_template_model, $lseg_feature, "File-Based Land Segment Runoff Template", TRUE, TRUE, 1);
  $lseg_model = entity_load_single('dh_properties', $lseg_model->pid);
  error_log("Created New Model");
} else {
  error_log("Found Model with pid: $lseg_model->pid");
}

// **********************************************
// ***** Populate basic attributes of the Land Segment model
// **********************************************
$plugin = dh_variables_getPlugins($lseg_model);
$plugin->loadProperties($lseg_model);
$oc = om_load_dh_property($lseg_model, "om_element_connection");
if ($oc === FALSE) {
  // we must create a holder for an OM connection - this should not be necessary because the template had one
  $values = array(
    'varkey' => 'om_element_connection',
    'entity_type' => 'dh_properties',
    'featureid' => $lseg_model->pid,
    'propname' => 'om_element_connection',
    'propcode' => '0',
  );
  om_model_getSetProperty($values, 'name');
  $oc = om_load_dh_property($lseg_model, "om_element_connection");
  error_log("Added om_element_connection to $lseg_model->pid");
  
} 
// now create a model if it doesn't have an om_element_connection 
error_log("OM pid: " . $oc->pid);
error_log("OM elid: " . $oc->propvalue);
// verify that the remote model exists
if ($oc->propvalue > 0) {
  $om_model = om_get_om_model($oc->propvalue);
  if (empty($om_model)) {
    error_log("Could not find linked OM model.  Resetting.");
    $oc->propvalue = 0;
  }
}
// **********************************************
// load the river segment model to get the CBP6 container
// **********************************************
$rsm_info = array(
  'propcode' => 'vahydro-1.0',
  'varkey' => 'om_water_model_node',
  'entity_type' => 'dh_feature',
  'featureid' => $rseg_hydroid
);
$rseg_model = om_get_property($rsm_info, 'propcode_singular');

if ($rseg_model === FALSE) {
  error_log("Could not load watershed model segment");
  die;
}
error_log("River Model pid: " . $rseg_model->pid);
$ro_container = om_load_dh_property($rseg_model, "1. Local Runoff Inflows");
if ($rseg_model === FALSE) {
  error_log("Could not load runoff container");
  die;
}
$cbp6_flows = om_load_dh_property($ro_container, "CBP6 Runoff");
if ($rseg_model === FALSE) {
  error_log("Could not load CBP6 container");
  die;
}
error_log("CBP6 Model container: " . $cbp6_flows->pid);
// **********************************************
// Make a connection between land and river model in vahydro 
// **********************************************
$link = array(
   'varkey' => 'om_map_model_linkage',
   'propname' => $lseg_model->propname,
   'featureid' => $cbp6_flows->pid,
   'propvalue' => $lseg_model->pid,
   'entity_type' => 'dh_properties',
   'propcode' => 'dh_properties',
   'link_type' => 3
);
error_log("Link info:" . print_r($link, 1));
$link = om_model_getSetProperty($link, 'name');
$link->save();
error_log("Link: " . $link->pid);
// create a child linked property in vahydro 

if (!($oc->propvalue > 0)) {
  // we do not yet have a remote model object
  // create one
  // get elementid for OM cbp6 container 
  $cbp6_link = om_load_dh_property($cbp6_flows, "om_element_connection");
  $cbp6_elid = $cbp6_link->propvalue;
  // now tell the connection object to do a one time clone of a basic object 
  $oc->propcode = 'clone';
  $oc->om_template_id = $om_lseg_template_elid;
  $oc->remote_parentid = $cbp6_elid;
  $oc->save();
  $oc = om_load_dh_property($lseg_model, "om_element_connection");
  error_log("Output of create element = $oc->provalue");

}
// now push changes 
$lseg_model->landseg = substr($lseg_feature->hydrocode, 0, 6);
$lseg_model->riverseg = substr($lseg_feature->hydrocode, 7);
$lseg_model->modelpath = '/media/model/p532';
$lseg_model->version = $version;
$lseg_model->propname = $lseg_feature->hydrocode;
// when we use the exportOpenMI method, we don't need to push everything on save
// so we shouodl disable the OC, save the model,
// then do the push, then later we can come back and re-enable the push on save 
$oc->propcode = 0;
$oc->save();
$lseg_model->save();
if ($oc->propvalue > 0) {
  error_log("Pushing Data");
  $exp = $plugin->exportOpenMI($lseg_model);
  dpm($exp,"Using JSON export mode");
  $exp_json = addslashes(json_encode($exp[$lseg_model->propname]));
  om_set_element($oc->propvalue, $exp_json);
  error_log("Finished Pushing Data");
}
// now set to save on auto. 
$oc->propcode = 1;
$oc->save();

error_log("Complete. Now update land use.");
echo $lseg_model->pid;
?>