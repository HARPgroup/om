<?php

$plugin = array(
  'label' => t('OM Object Class - dHOMHydroImpoundmentSmall'),
  'handler' =>  array(
    'class' => 'dHOMHydroImpoundmentSmall',
    'file' => 'dHOMObjectHydrologyClasses.class.php',
  ),
  'variable' =>  array(
    'varname' => 'OM Object Class - HydroImpoundment Sub-component',
    'vardesc' => 'Lake or reservoir class.',
    'vocabulary' => 'om_object_classes',
    'varunits' => 'n/a',
    'varkey' => 'om_hydroimpsmall',
    'datatype' => 'component',
    'varcode' => 'hydroImpSmall',
    'isregular' => 0,
    'timestep' => 0,
    'timeunits' => 'n/a',
    'nodataval' => 0,
    'data_entry' => '',
    'plugin' => 'dHOMHydroImpoundmentSmall',
    'options' => '',
    'varabbrev' => 'Imp',
  ),
);

?>