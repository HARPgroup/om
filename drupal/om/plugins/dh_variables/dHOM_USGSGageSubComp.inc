<?php

$plugin = array(
  'label' => t('OM Component Class - USGSGageObject'),
  'handler' =>  array(
    'class' => 'dHOM_USGSGageSubComp',
    'file' => 'dHOMHelperClasses.class.php',
  ),
  'variable' =>  array(
    'varname' => 'OM Object Class - USGSGageSubComp',
    'vardesc' => 'Gage object, retrieves and provides runtime settings for USGS gage',
    'vocabulary' => 'om_object_classes',
    'varunits' => 'n/a',
    'varkey' => 'om_class_USGSGageSubComp',
    'datatype' => 'component',
    'varcode' => 'USGSGageSubComp',
    'isregular' => 0,
    'timestep' => 0,
    'timeunits' => 'n/a',
    'nodataval' => 0,
    'data_entry' => 'name,code,text',
    # Uses same plugin as full object
    'plugin' => 'dHOM_USGSGageObject',
    'options' => '',
    'varabbrev' => 'USGS Gage',
  ),
);

?>