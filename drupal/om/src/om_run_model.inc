<?php
module_load_include('module', 'dh');
module_load_include('module', 'om');


// @todo: run_mode handling
//        - vwp projects shoudl continue to use the vwp modes
//        - other models should allow choice between normal single container running 
//          and the shakeTree run mode.

function om_run_model_form($form, &$form_state, $model = null, $op = 'run') {
  $run_id_options = om_run_id_options(); // will determine what a user can run
  if (is_object($model)) {
    $plugin = dh_variables_getPlugins($model);
    if ($plugin) {
    //dpm($plugin,'plugin');
      if (method_exists($plugin, 'loadProperties')) {
        $plugin->loadProperties($model);
        //dsm("loadProperties called");
        $flow_mode = $model->flow_mode->propvalue;
        $run_mode = $model->run_mode->propvalue;
        $def_cache_date = date('Y-m-d',$model->flow_mode->modified);
        $cache_date = (is_object($model->cache_date) and !empty($model->cache_date->startdate)) ? date('Y-m-d', $model->cache_date->startdate) : $def_cache_date;
        $startdate = (is_object($model->run_info) and !empty($model->run_info->startdate)) ? date('Y-m-d', $model->run_info->startdate) : '2000-01-01';
        $enddate = (is_object($model->run_info) and !empty($model->run_info->enddate)) ? date('Y-m-d', $model->run_info->enddate) : '2000-12-31';
        $run_id = (is_object($model->run_info) and ($model->run_info->propvalue >= 0)) ? $model->run_info->propvalue : 100;
      }
    }
  } else {
    dsm("Model object not set");
  }
  
  $form['run_id'] = array(
    '#title' => t('Model Run ID'),
    '#type' => 'select',
    '#default_value' => $run_id,
    '#options' => $run_id_options,
    '#description' => t('Unique Identifier for a model run.'),
    '#required' => TRUE,
    '#multiple' => FALSE,
    '#weight' => 1,
  );
  $date_format = 'Y-m-d';
  $form['startdate'] = array(
    '#title' => t('Start Date'),
    '#description' => t('Start date for model run.'),
    '#required' => FALSE,
    '#default_value' => $startdate,
    '#date_format' => $date_format,
    '#type' => 'date_select',
    '#date_year_range' => '-100:+5',
  );
  $form['enddate'] = array(
    '#title' => t('Model End Date'),
    '#description' => t('End date for selected model run.'),
    '#default_value' => $enddate,
    '#date_format' => $date_format,
    '#type' => 'date_select',
    '#date_year_range' => '-100:+5',
    '#required' => FALSE,
  );
  $form['dt'] = array(
    '#title' => t('Timestep'),
    '#type' => 'select',
    '#default_value' => -1,
    '#options' => array(
      '60' => t('1 Minute'),
      '600' => t('10 Minutes'),
      '3600' => t('1 Hour'),
      '7200' => t('2 Hours'),
      '10800' => t('3 Hours'),
      '14400' => t('4 Hours'),
      '21600' => t('6 Hours'),
      '28800' => t('8 Hours'),
      '43200' => t('12 Hours'),
      '86400' => t('Daily'),
    ),
    '#default_value' => empty($model->dt) ? 86400 : $model->dt,
    '#description' => t('Model timestep. NOT YET ENABLED - To change timestep, edit model property named "dt"'),
    '#required' => TRUE,
    '#multiple' => FALSE,
    '#weight' => 1,
  );

	$form['Advanced'] = array();
	$form['Advanced']['#type'] = 'fieldset';
	$form['Advanced']['#title'] = t('Advanced Run Options');
	$form['Advanced']['#collapsible'] = TRUE;
	$form['Advanced']['#collapsed'] = TRUE;
	$form['Advanced']['#weight'] = 2;
  $form['Advanced']['runtype'] = array(
    '#title' => t('Run Caching'),
    '#type' => 'select',
    '#options' => array(
      'cached' => 'Standard (Container Tree cached)',
      'shake_tree' => 'Tree (Sequential Tree, with caching)',
    ),
    '#size' => 1,
    '#default_value' => empty($model->runtype) ? 'shake_tree' : $model->runtype
  );
  $run_mode_options = om_run_mode_options(); // will determine what a user can run
  $form['Advanced']['run_mode'] = array(
    '#title' => t('Run Mode (wd/ps/lakes)'),
    '#type' => 'select',
    '#options' => $run_mode_options,
    '#size' => 1,
    '#default_value' => empty($run_mode) ? 2 : $run_mode,
  );
  $flow_mode_options = om_flow_mode_options(); // will determine what a user can run
  $form['Advanced']['flow_mode'] = array(
    '#title' => t('Flow Mode (runoff dataset)'),
    '#type' => 'select',
    '#options' => $flow_mode_options,
    '#size' => 1,
    '#default_value' => empty($flow_mode) ? 3 : $flow_mode,
  );
  //dpm($model,'model');
  //dsm("empty(model->flow_mode) ? = " . empty($model->flow_mode));
  // advanced options for shakeTree
  $form['Advanced']['cache_date'] = array(
    '#title' => t('Cache Date'),
    '#description' => t('Date to determine if cacheable components are re-run or not.'),
    '#default_value' => $cache_date,
    '#date_format' => $date_format,
    '#type' => 'date_select',
    '#date_year_range' => '-20:+0',
    '#required' => FALSE,
  );
  $form['Advanced']['force_overwrite'] = array(
    '#title' => 'Force Re-run (no caching)',
    '#type' => 'select',
    '#options' => array(
      '0' => 'None',
      '1' => 'All Model Elements',
      '2' => 'Model Outlet Only',
      '3' => 'All watershed nodes',
    ),
    '#size' => 1,
    '#default_value' => 2
  );
  
  // add controls
  $form['controls'] = om_run_model_form_controls($form, $form_state);
  
  return $form;

}

function om_run_model_form_controls(&$form, &$form_state) {
  $controls = array(
    '#type' => 'container',
    '#weight' => 40,
    '#prefix' => '<div id="om_model_controls">',
    '#suffix' => '</div>',
  );
  // this element should have a call to check the status of model at render
  // and then 2 version of the button text "run model" and "update model status" 
  // depending on if the specific elementid is currently running.  
  // this can prevent double-running
  $controls['run_model'] = array(
    '#type' => 'button',
    '#value' => t('Run Model'),
    //'#markup' => t('Run Model'),
    '#weight' => 5,
    '#ajax' => array(
      'callback' => 'om_run_model_form_run',
      'wrapper' => 'om_model_controls',
      'method' => 'replace',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Run requested - please wait'),
      ),
      #'event' => 'click', // not needed, default is mousedown
    ),
  );
  // this element should have a call to check the status of model at render
  // and then disabled if model is idle.
  $controls['check_model'] = array(
    '#type' => 'button',
    '#value' => t('Check Model Status'),
    '#weight' => 10,
    '#ajax' => array(
      'callback' => 'om_run_model_form_run',
      'wrapper' => 'om_model_controls',
      'method' => 'replace',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Checking - please wait'),
      ),
    ),
  );
  $controls['end_model'] = array(
    '#type' => 'button',
    '#value' => t('Terminate Model Run'),
    '#weight' => 10,
    '#ajax' => array(
      'callback' => 'om_run_model_form_run',
      'wrapper' => 'om_model_controls',
      'method' => 'replace',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Terminate requested - please wait'),
      ),
    ),
  );
  $controls['status_message'] = array(
    '#prefix' => "<div>",
    '#suffix' => "</div>",
    '#value' => 0,
    '#markup' => t('Model Run Status Unknown'),
    '#weight' => 15,
  );
  $controls['model_status'] = array(
    '#type' => 'hidden',
    '#value' => 0,
    '#weight' => 15,
  );
  return $controls;
  
}

// @todo: move this to the base module?
function om_run_model($config) {
  global $php_path; // @todo: put this in the module settings somewhere
  //$test_only = TRUE;
  //dpm($config,'submitted to run model');
  // run the model 
  $run_id = $config['run_id'];
  $elementid = $config['elementid'];
  $startdate = $config['startdate'];
  $enddate = $config['enddate'];
  $flow_mode = isset($config['flow_mode']) ? $config['flow_mode']: FALSE;
  $dt = $config['dt'];
  $debug = 0;
  
  switch ($config['runtype']) {
    case 'shake_tree':
    // shake tree optionsL
    
    $operation = 1; // $operation = 1, should be elementid ONLY 
    // $elemname = $argv[2] = elementid ONLY (used in query)
    // $run_id = $argv[3], $startdate = $argv[4], $enddate = $argv[5] = already handled
    $cache_date = $config['cache_date'];// replace previous run data based on whether or not it has been run since this date
    // $force_overwrite = $argv[7], $scid = $argv[8], $strict = $argv[9], $run_mode = $argv[10]
    // operation - method of querying which units to run, usnig 2nd parameter as key
    $force_overwrite = $config['force_overwrite']; // replace all previous run data for these runs or use a cache_date?
    $scid = 37; // scenario ID, hardwired scenario ID, should pass in as part of model object for VWP interface at min
    $strict = empty($config['strict']) ? -1 : $config['strict']; 
    //strict setting - whether or not to evaluate the unit area on a relatively strict limit in the post-processing verification routine
    //  strict = 0 - do the run verification routine, but use broad criteria to evaluate it
    //  strict = 1 - verify with narrow valid criteria
    //  strict = -1 - do not verify, assume it is OK
    $run_mode = $config['run_mode']; // run_mode - model setting for use by internal lists etc. select list of 1-100?
    $setstr = "/usr/bin/php -f $php_path" . "run_shakeTree.php $operation $elementid $run_id $startdate $enddate $cache_date $force_overwrite $scid $strict $run_mode ";
    // @todo: this does not do anything.  The run_model.php script and the forkRun() routines are not able to handle this
    //        so for now this is a placeholder.
    $setstr .= "normal flow_mode=$flow_mode $debug $dt";
    //$test_only = TRUE;
    break;
	
    case 'cached':
    default:
    $setstr = "/usr/bin/php -f $php_path" . "run_model.php $elementid $run_id cached $startdate $enddate -1 \"\" 1 0 ";
    break;
  }
  $cmd_output = array();
  $cmd = "cd $php_path \n";
  $cmd .= $setstr;
  if ($test_only) {
      //dpm( $cmd, "Testing Only ");
      error_log("Testing Run Type: $config[runtype] : $cmd");
  } else {
    if ($setstr) {
      //dpm( $cmd, "Executing ");
      // this runs in the same process
      //shell_exec($cmd);
      // this runs in a separate process.
      error_log("Executing Run Type: $config[runtype] : $cmd");
      $forkout = exec( "$cmd > /dev/null &", $cmd_output );
    }
  }
  return array('command' => $cmd, 'output' => $forkout);
}

// @todo: move this to the base module?
function om_run_message_model($config) {
  global $php_path;
  $config += array(
    'host' => 'localhost',
    'runtype' => 'cached',
  );
  // run the model 
  $run_id = $config['run_id'];
  $elementid = $config['elementid'];
  $host = $config['host'];
  $msg = $config['message'];
  om_message_model($elementid, $host, $msg, $run_id);
}

function om_run_model_form_run(&$form, &$form_state) {
  //dpm($form_state,'Model Run Requested');
  // load the property for the model elements
  $model = entity_ui_form_submit_build_entity($form, $form_state);
  $pid = $model->pid;
  $elementid = 'no-elementid';
  // get the element link ID
  foreach ($model->dh_variables_plugins as $plugin) {
    if (method_exists($plugin, 'findRemoteOMElement')) {
      $path = array();
      $elementid = $plugin->findRemoteOMElement($model, $path);
    } else {
      dsm("Can't find remote Element method on plugin findRemoteOMElement()");
    }
  }
  //dpm($form,'form');
  // figure out which button
  // the last item in the triggering_element parents array will contain the button name
  //    either "run_model" or "end_model"
  $parents = $form_state['triggering_element']['#array_parents'];
  $button = $parents[count($parents) - 1];
  $model_config = $form_state['values'];
  $model_config['elementid'] = $elementid;
  // since this has not been oficially submitted (in order to use ajax properly)
  //   we need to handle the date array
  $sd = $model_config['startdate'];
  $ed = $model_config['enddate'];
  $cd = $model_config['cache_date'];
  $model_config['startdate'] = $sd['year'] . '-' . $sd['month'] . '-' . $sd['day'];
  $model_config['enddate'] = $ed['year'] . '-' . $ed['month'] . '-' . $ed['day'];
  $model_config['cache_date'] = $cd['year'] . '-' . $cd['month'] . '-' . $cd['day'];
  // cannot create entirely new element, because it loses info that 
  //   the form passes in.  must modify from $form array instead
  //$element = om_run_model_form_controls($form, $form_state);
  $element = $form['controls'];
  // figure out the run mode
  $status = om_get_model_status($elementid);
  $status = (array)$status;
  $status_flag = $status['status_flag'];
  $element['model_status']['#value'] = $status_flag;
  switch($button) {
    case 'run_model':
      // stash flow and run modes on the object, to be used as defaults next time
      // and to be used by remote object during requested run.
      om_run_stash_modes($model, $model_config);
      if ($status_flag == 0) {
        $output = om_run_model($model_config);
        $element['run_model']['#value'] = 'Run Requested';
        $element['run_model']['#disabled'] = TRUE;
        $element['model_status']['#value'] = 1; // set as 1 to prevent accidental overwriting
        $message = "Model Run forked " . print_r($output,1);
      } else {
        $message = "Model is already running.  Request ignored.";
      }
    break;
    
    case 'check_model':
      $message = "Model Status Update returned.";
      $element['model_status']['#value'] = $status_flag;
    break;
    
    case 'end_model':
      $message = "Request to Terminate model run.";
      $element['model_status']['#value'] = -1;
      om_message_model($status['elementid'], $status['host'], 'end', $status['runid']);
    break;
    
    default:
      watchdog('om', "om_run_model_form_run called with unknown button $button");
      $message = "om_run_model_form_run called with unknown button $button .";
    break;
  }
  $content = array();
  $content['info'] = array(
    '#markup' => $message
  );
  $show = array(
    'elemname' => "Name", 
    'status_mesg' => "Message", 
    'runid' => "Run ID",
    'host' => "Host",
    'query' => "SQL",
  );
  foreach ($show as $key => $label) {
    $content[$key] = array(
      '#type' => 'fieldset',
      '#title' => $label,
      '#value' => $status[$key],
    );
  }
  
  /*
  $content['table'] = array(
    '#theme' => 'table',
    '#header' => array_keys($show),
    '#rows' => array(0=> array_values($status)),
    '#attributes' => array (
      'class' => array('views-table', 'cols-3', 'table', 'table-hover', 'table-striped'),
    ),
  );
  */
  
  
  $status_formatted['rows'] = array(
    0 => array('data' => array_values($status))
  );
  
  //dpm($status, 'status returned');
  //$element['status_message']['#markup'] = $message;
  $element['status_message']['#markup'] = render($content);
  return $element;
}

function om_run_stash_modes($model, $model_config) {
  // Save items on object needed for current run, and future stashing
  // set the model run_mode property here, it will cascade a change back to the OM database before running.
  // also stores all of this data and more on the "runid_XX" prop, and in the future *only* the runid_XX prop 
  // will need to hold this data.
  //error_log("Config" . print_r($config,1));
  
  // we set these on the object AND on the scenario object because the model runtime looks back at this object to get info 
  $run_mode = $model_config['run_mode'];
  $run_mode_config = array(
    'entity_type' => 'dh_properties',
    'featureid' => $model->pid,
    'varkey' => 'om_class_Constant',
    'propname' => 'run_mode',
    'propvalue' => $run_mode,
  );
  $run_mode = om_model_getSetProperty($run_mode_config, 'name');
  $run_mode->save();
  
  // set model timestep dt here
  $dt = $model_config['dt'];
  $dt_config = array(
    'entity_type' => 'dh_properties',
    'featureid' => $model->pid,
    'varkey' => 'om_class_Constant',
    'propname' => 'dt',
    'propvalue' => $dt,
  );
  $dt = om_model_getSetProperty($dt_config, 'name');
  $dt->save();
  
  // set the model flow_mode property here
  $flow_mode = $model_config['flow_mode'];
  $flow_mode_config = array(
    'entity_type' => 'dh_properties',
    'featureid' => $model->pid,
    'varkey' => 'om_class_Constant',
    'propname' => 'flow_mode',
    'propvalue' => $flow_mode,
  );
  $flow_mode = om_model_getSetProperty($flow_mode_config, 'name');
  $flow_mode->save();
  
  // set the model flow_mode property here
  $cache_date = $model_config['cache_date'];
  $cache_date_config = array(
    'entity_type' => 'dh_properties',
    'featureid' => $model->pid,
    'varkey' => 'om_class_Constant',
    'propname' => 'cache_date',
    'startdate' => $cache_date,
  );
  $cache_date = om_model_getSetProperty($cache_date_config, 'name');
  //dpm($cache_date, 'cache date');
  $cache_date->save();
  
  // set the model flow_mode property here
  $run_info_config = array(
    'entity_type' => 'dh_properties',
    'featureid' => $model->pid,
    'varkey' => 'om_class_Constant',
    'propname' => 'run_info',
    'startdate' => $model_config['startdate'],
    'enddate' => $model_config['enddate'],
    'propvalue' => $model_config['run_id'],
  );
  $run_info = om_model_getSetProperty($run_info_config, 'name');
  $run_info->save();
}

function om_run_create_scenario($model, $model_config) {
  // Now stash on runid_XX 
  
  // create or load a run scenario 
  // set the status = 1 (run requested)
  // set run_mode, flow_mode 
  // set model_session_db_status
  // set model_runtime_db_status 
  
  $scenario_config = array(
    'entity_type' => 'dh_properties',
    'featureid' => $model->pid,
    'varkey' => 'om_scenario',
    'propname' => 'runid_' . $model_config['runid'],
    'propvalue' => $model_config['runid'],
  );
  $run_scenario = om_model_getSetProperty($scenario_config, 'name');
  if (isset($run_scenario->pid) and ($run_scenario->pid > 0)) {
    $run_pid = $run_scenario->pid;
    $run_mode_config['featureid'] = $run_pid;
    $run_mode = om_model_getSetProperty($run_mode_config, 'name');
    $cache_date_config['featureid'] = $run_pid;
    $cache_date = om_model_getSetProperty($cache_date_config, 'name');
    $flow_mode_config['featureid'] = $run_pid;
    $flow_mode = om_model_getSetProperty($flow_mode_config, 'name');
    $dt_config['featureid'] = $run_pid;
    $dt = om_model_getSetProperty($dt_config, 'name');
    $runid_config = array(
      'entity_type' => 'dh_properties',
      'featureid' => $run_pid,
      'varkey' => 'om_class_Constant',
      'propname' => 'runid_' . $model_config['runid'],
      'propvalue' => $model_config['runid'],
    );
    $runid = om_model_getSetProperty($runid_config, 'name');
  }
}

/* USAGE
//echo "This is some cool code.";
module_load_include('inc', 'om', 'src/om_run_model');
$arg = arg();
$form_state = array();
$form_state['wrapper_callback'] = 'entity_ui_main_form_defaults';
$form_state['entity_type'] = 'dh_properties';
$form_state['bundle'] = 'dh_properties';
$pid = $arg[1];
$model = entity_load_single('dh_properties', $pid);
$op = 'run';
form_load_include($form_state, 'inc', 'entity', 'includes/entity.ui');
// set things before initial form_state build
$form_state['build_info']['args'] = array($model, $op, 'dh_properties');

// **********************
// Load the form
// **********************
form_load_include($form_state, 'inc', 'om', 'src/om_run_model');
$elements = drupal_build_form('om_run_model_form', $form_state);
$form = drupal_render($elements);
echo $form;
*/
?>