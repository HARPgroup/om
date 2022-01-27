<?php

$plugin = array(
  'label' => t('OM Component Class - textField'),
  'handler' =>  array(
    'class' => 'dHOMWaterSystemPermitStatus',
    'file' => 'dHOMWaterSystemObjectClasses.class.php',
  ),
  'variable' =>  array(
    'varname' => 'OM - Water Model Permit Status (subcomp)',
    'vardesc' => 'Drop down with permit status for model run modes.',
    'vocabulary' => 'om_object_classes',
    'varunits' => 'var',
    'varkey' => 'om_class_permit_status',
    'datatype' => 'constant',
    'varcode' => 'textField',
    'isregular' => 0,
    'timestep' => 0,
    'timeunits' => 'n/a',
    'nodataval' => 0,
    'data_entry' => 'name,code',
    'plugin' => 'dHOMWaterSystemPermitStatus',
    'options' => '',
    'varabbrev' => 'Permit Status',
  ),
);

?>