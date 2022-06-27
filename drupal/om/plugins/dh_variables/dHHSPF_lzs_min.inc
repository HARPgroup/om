<?php

$plugin = array(
  'variable' =>  array(
    'varname' => 'OM - HSPF LZS Min',
    'vardesc' => 'Minimum value of lower zone storage (LZS) during indicated time period.',
    'vocabulary' => 'hspf',
    'varunits' => 'var',
    'varkey' => 'om_hspf_lzs_min',
    'datatype' => 'constant',
    'varcode' => 'om_hspf_lzs_min',
    'isregular' => 0,
    'timestep' => 0,
    'timeunits' => 'n/a',
    'multiplicity' => 'tspan_singular',
    'nodataval' => 0,
    'data_entry' => 'value',
    'plugin' => 'dHOMConstant',
    'options' => '',
    'varabbrev' => 'LZS min',
  ),
);

?>