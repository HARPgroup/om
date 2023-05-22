<?php

$plugin = array(
  'label' => t('OM USGS Recharge Object'),
  'handler' =>  array(
    'class' => 'dHOMUSGSRecharge',
    'file' => 'dHOMObjectHydrologyClasses.class.php',
  ),
  'variable' =>  array(
    'varname' => 'OM USGS Recharge Object',
    'vardesc' => 'Uses MLLR method to estimate wintertime recharge probabilities.',
    'vocabulary' => 'om_object_classes',
    'varunits' => 'n/a',
    'varkey' => 'om_usgs_recharge',
    'datatype' => 'component',
    'varcode' => 'USGSRecharge',
    'isregular' => 0,
    'timestep' => 0,
    'timeunits' => 'n/a',
    'nodataval' => 0,
    'data_entry' => '',
    'plugin' => 'dHOMUSGSRecharge',
    'options' => '',
    'varabbrev' => 'MLLR',
  ),
);

?>