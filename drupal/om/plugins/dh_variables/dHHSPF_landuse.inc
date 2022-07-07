<?php

$plugin = array(
  'variable' =>  array(
    'varname' => 'OM - HSPF Land Use Model',
    'vardesc' => 'Container for basic land use portion of a land segment simulation.',
    'vocabulary' => 'hspf',
    'varunits' => 'var',
    'varkey' => 'om_hspf_landuse',
    'datatype' => 'constant',
    'varcode' => 'om_hspf_landuse',
    'isregular' => 0,
    'timestep' => 0,
    'timeunits' => 'n/a',
    'multiplicity' => 'tspan_singular',
    'nodataval' => 0,
    'data_entry' => 'value',
    'plugin' => 'dHOMSubComp',
    'options' => '',
    'varabbrev' => 'LU Model',
  ),
);

?>