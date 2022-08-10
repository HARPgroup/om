<?php

$plugin = array(
  'label' => t('OM - Model Run Record'),
  'handler' =>  array(
    'class' => 'dHOM_ModelRun',
    'file' => 'dHOMObjectClasses.class.php',
  ),
  'variable' =>  array(
    'varname' => 'OM - Model Scenario',
    'vardesc' => 'Object Model run record - value is status, auto set by run routines (0 = finished, 1 = initializing, 2 = running).  tstime = model initiation timestamp, tsendtime = completion time.',
    'vocabulary' => 'om',
    'varunits' => 'n/a',
    'varkey' => 'om_model_run',
    'datatype' => 'collection',
    'varcode' => 'om_model_run',
    'isregular' => 0,
    'timestep' => 0,
    'timeunits' => 'n/a',
    'multiplicity' => 'code_singular',
    'nodataval' => 0,
    'data_entry' => 'value,code,text,startdate,enddate',
    'plugin' => 'dHOM_ModelRun',
    'options' => '',
    'varabbrev' => 'Run',
  ),
);

?>