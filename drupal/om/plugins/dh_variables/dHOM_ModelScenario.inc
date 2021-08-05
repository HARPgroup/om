<?php

$plugin = array(
  'label' => t('OM - Model Scenario'),
  'handler' =>  array(
    'class' => 'dHOM_ModelScenario',
    'file' => 'dHOMObjectClasses.class.php',
  ),
  'variable' =>  array(
    'varname' => 'OM - Model Scenario',
    'vardesc' => 'Object Model Scenario - value is status, auto set by run routines (0 = finished, 1 = initializing, 2 = running).',
    'vocabulary' => 'om',
    'varunits' => 'n/a',
    'varkey' => 'om_scenario',
    'datatype' => 'collection',
    'varcode' => 'om_scenario',
    'isregular' => 0,
    'timestep' => 0,
    'timeunits' => 'n/a',
    'nodataval' => 0,
    'data_entry' => 'value,code,text,startdate,enddate',
    'plugin' => 'dHOM_ModelScenario',
    'options' => '',
    'varabbrev' => 'Scenario',
  ),
);

?>