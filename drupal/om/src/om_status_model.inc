<?php

module_load_include('module', 'dh');
module_load_include('module', 'om');

function om_status_model_form($form, &$form_state) {
  $run_id_options = om_run_id_options(); // will determine what a user can run
 
  $form['mins'] = array(
    '#title' => t('Show activity in last'),
    '#type' => 'select',
    '#options' => array(
      '60' => t('1 Hour'),
      '120' => t('2 Hours'),
      '240' => t('4 Hours'),
      '480' => t('8 Hours'),
      '1440' => t('24 Hours'),
      '4320' => t('3 Days'),
      '10800' => t('7 Days'),
      '-1' => t('Any Time (last 100 runs)'),
    ),
    '#default_value' => 60,
    '#description' => t(''),
    '#required' => TRUE,
    '#multiple' => FALSE,
    '#weight' => 1,
  );
  $form['elementid'] = array(
    '#title' => t('Search for Specific Elementid'),
    '#type' => 'textfield',
    '#default_value' => '',
    '#description' => t(''),
    '#required' => FALSE,
    '#weight' => 2,
  );

  // add controls
  $form['controls'] = om_status_model_form_controls($form, $form_state);
  
  return $form;

}

function om_status_model_form_controls(&$form, &$form_state) {
  $controls = array(
    '#type' => 'container',
    '#weight' => 40,
    '#prefix' => '<div id="om_model_status_controls">',
    '#suffix' => '</div>',
  );
  // this element should have a call to check the status of model at render
  // and then disabled if model is idle.
  $controls['check_model'] = array(
    '#type' => 'button',
    '#value' => t('Check Model Status'),
    '#weight' => 10,
    '#ajax' => array(
      'callback' => 'om_status_model_form_run',
      'wrapper' => 'om_model_status_message',
      'method' => 'replace',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Checking - please wait'),
      ),
    ),
  );
  $controls['om_model_status_message'] = array(
    '#prefix' => "<div id ='om_model_status_message'>",
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

function om_status_model_form_run(&$form, &$form_state) {
  //dpm($form_state,'Model Run Requested');
  // load the property for the model elements
  /*
  $model = entity_ui_form_submit_build_entity($form, $form_state);
  $pid = $model->pid;
  $elementid = 'no-elementid';
  // get the element link ID
  foreach ($model->dh_variables_plugins as $plugin) {
    if (method_exists($plugin, 'findRemoteOMElement')) {
      $path = array();
      $elementid = $plugin->findRemoteOMElement($model, $path);
    }
  }
  
  $status = om_get_model_status($elementid);
  */
  $mins = $form_state['values']['mins'];
  $elementid = intval($form_state['values']['elementid']);
  $elementid = (!empty($elementid)) ? $elementid : -1;
  $status = om_get_model_status($elementid, $mins, 'list');
  //dpm($status,'status');
  $status = (array)$status;
  foreach ($status as $key => $row) {
    $elpid = om_find_dh_by_elementid($row['elementid']);
    if ($elpid > 0) {
      $status[$key]['elemname'] = l($row['elemname'], "om-model-info/$elpid");
    }
    $status[$key]['report'] = l("Run Report", $row['report'], array('attributes' => array('target' => '_blank')));
  }
  $content = array();
  
  $content['table'] = array(
    '#theme' => 'table',
    '#header' => array_keys($status[0]),
    '#rows' => $status,
    '#attributes' => array (
      'class' => array('views-table', 'cols-3', 'table', 'table-hover', 'table-striped'),
    ),
  );
  
  $element['om_model_status_message']['#prefix'] = "<div id ='om_model_status_message'>";
  $element['om_model_status_message']['#markup'] = "</div>";
  $element['om_model_status_message']['#markup'] = render($content);
  return $element;
}

function om_find_dh_by_elementid($elementid) {
  $q = "select featureid from {dh_properties} where propvalue = :elid and varid in (select hydroid from dh_variabledefinition where varkey = 'om_element_connection')";
  $pid = db_query($q,array('elid'=>$elementid))->fetchField();
  return $pid;
}
