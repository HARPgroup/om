<?php

// copies model object general reports properties 
// copies model scenario reports properties
// inputs:
// - model pid 
// - model run name, if not existing, create
// initial behavior will just run the create scripts and DO NOT OVERWRITE 
// later version may allow checkbox behavior described below.
// Usage: om-model-reports/[pid]/[runid_xx]&template=

$arg = arg();
$parms = drupal_get_query_parameters();
if ($arg[0] == 'node') {
  $aoff = 1;
} else {
  $aoff = 0;
}
$pid = $arg[1 + $aoff];
$run_name = $arg[2 + $aoff];
//dpm($arg,'args');
//dsm("pid = $pid, $run_name = $run_name");
$model = entity_load_single('dh_properties', $pid);
//dpm($model, 'model');

if (is_object($model)) {
  $entity = entity_load_single($model->entity_type, $model->featureid);
  switch ($entity->bundle) {
    case 'facility':
    case 'intake':
    case 'well':
    $tr_pid = 4988636;
    break;
    
    case 'watershed':
    case 'waterbody':
    $tr_pid = 6541481;
    break;
    
    default:
    echo "Cannot handle object of entity_type = $model->entity_type, bundle = $entity->bundle ";
    exit;
  }
  // can override the template to copy from another scenario or model if needed
  if (isset($parms['template'])) {
    $tr_pid = $parms['template'];
  }
  $template_entity = entity_load_single('dh_properties', $tr_pid);
  // create, but don't overwrite properties on model
  $model_reports = om_copy_properties($template_entity, $model, 'reports', TRUE, TRUE, TRUE, FALSE);
  //dpm($model_reports,'model_reports');
  // create, but don't overwrite properties on scenario
  if ($run_name <> '') {
    $run_atts = array('featureid' => $model->pid, 'entity_type' => 'dh_properties', 'propname' => $run_name );
    // retrieve or create the model run info reports container
    $run_info = om_model_getSetProperty($run_atts, 'name', TRUE, FALSE) ;
    //dpm($run_info, 'run info');
    $scenario_reports = om_copy_properties($template_entity, $run_info, 'scenario_reports|reports', TRUE, TRUE, TRUE, FALSE);
    //dpm($scenario_reports,'scenario_reports');
  }
  dsm("Default scenario summary parameters added.");
}

if (isset($parms['destination'])) {
  $url = $parms['destination'];
  drupal_goto($url);
}
if (isset($parms['finaldest'])) {
  $url = $parms['finaldest'];
  drupal_goto($url);
}


// @todo: 
function om_create_model_reports_form() {
  // @todo: integrate this 
  $form['run_name'] = array(
    '#title' => t('Model Run ID'),
    '#type' => 'textfield',
    '#default_value' => $run_name,
    '#description' => t('Unique Identifier for a model run.'),
    '#required' => TRUE,
    '#multiple' => FALSE,
    '#weight' => 1,
  );
}

function om_create_model_reports_form_submit($pid, $run_name) {
  // @todo: this does the work that is currently hard-wired below 
  // get type, determine template 
  // check for existing props on model, and run 
    // create from template if not, skip if they exist
  
}



// **********************
// @todo: Load the form to review instead of default actions 
// **********************
/*
$form_state = array();
$form_state['wrapper_callback'] = 'entity_ui_main_form_defaults';
$form_state['entity_type'] = 'dh_properties';
$form_state['bundle'] = 'dh_properties';
$model = entity_load_single('dh_properties', $pid);
form_load_include($form_state, 'inc', 'entity', 'includes/entity.ui');
// set things before initial form_state build
$form_state['build_info']['args'] = array($model, $run_name);

$elements = drupal_build_form('om_create_model_reports_form', $form_state);
$form = drupal_render($elements);
echo $form;

*/
// determine runid object pid from run name and model pid 
// determine entity type from model pid 
// load model parent entity 
// get bundle from parent entity (if relevant, otherwise, use generic defauilts)

// bundle = watershed, use
// pid = 6541481 
// bundle = facility, use 
// pid = 4988636 
// others: report, currently cannot handle reports for objects other than watershed, facility 

// on submit()
// copy scenario_reports to runid object reports with all children, if it does not already exist 
// copy reports to model object with all children if it does not exist or if overwrite requested 
// show a list of each existing report template object, with a check box if it exists, and if it does not exist
//  just show the name and sample text that will be created.
// If this is a replacement call, iterate through the selected check box entries to see if overwriting is desired
// default to no overwrite 


?>