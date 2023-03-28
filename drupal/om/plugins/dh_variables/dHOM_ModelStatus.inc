<?php

$plugin = array(
  'label' => t('OM - Model Run Status'),
  'handler' =>  array(
    'class' => 'dHVariablePluginDefault',
    'file' => 'dh.display.inc',
  ),
  'variable' =>  array(
    'varname' => 'OM - Run Status',
    'vardesc' => 'Run Status - value is status, auto set by run routines (0 = finished, 1 = initializing, 2 = running).',
    'vocabulary' => 'om',
    'varunits' => 'n/a',
    'varkey' => 'om_model_status',
    'datatype' => 'collection',
    'varcode' => 'model_status',
    'isregular' => 0,
    'timestep' => 0,
    'timeunits' => 'n/a',
    'nodataval' => 0,
    'data_entry' => 'value,code,text,startdate,enddate',
    'plugin' => 'dHVariablePluginDefault',
    'options' => '',
    'varabbrev' => 'Status',
  ),
);

?>