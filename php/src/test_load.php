<?php
# set up db connection
#include('config.php');
$noajax = 1;
$projectid = 3;
include_once('xajax_modeling.element.php');
#error_reporting(E_ALL);
error_log("Un-serializing Model Object <br>");
if (isset($argv[1])) {
  $elementid = $argv[1];
} else {
  exit;
}
$debug = 0;

// create a shell modelContainer object
// create a timer
error_log("Element $elementid wake() Returned from calling routine.");
$model = new modelContainer();
$model->starttime = '1984-01-01';
$model->endtime = '1984-01-31';
$model->dt = 86400;

// add a timeseriesfile / cbp type

$thisobresult = unSerializeModelObject($elementid);
$thisobject = $thisobresult['object'];
$thisname = $thisobject->name;
$thisobject->debug = 0;
error_log("Returned element named: $thisname ");


?>