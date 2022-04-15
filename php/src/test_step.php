<html>
<body>
<h3>Test Model Run</h3>

<?php


# set up db connection
#include('config.php');
$noajax = 1;
$projectid = 3;
include_once('xajax_modeling.element.php');
#include('qa_functions.php');
#include('ms_config.php');
/* also loads the following variables:
   $libpath - directory containing library files
   $indir - directory for files to be read
   $outdir - directory for files to be written
*/
error_reporting(E_ERROR);
print("Un-serializing Model Object <br>");
$debug = 0;

$steps = 2;
error_log("argv" . print_r($argv,1));
if (count($argv) > 3) {
  $modelid = $argv[1]; // the model container 
  $runid = $argv[2]; // the model container 
  $targetid = $argv[3]; // the element to test 
}
if (count($argv) > 4) {
  $steps = $argv[4]; // the element to test 
}

global $modeldb, $listobject, $tmpdir, $shellcopy, $ucitables, $scenarioid, $outdir, $outurl, $goutdir, $gouturl, $unserobjects, $adminsetuparray, $wdm_messagefile, $basedir, $model_startdate, $model_enddate;


$model_elements = loadModelUsingCached($modeldb, $modelid, $runid, $cache_runid, $input_props, $cache_level, $cache_list, $run_date);
$model = $model_elements['object'];
$model->outdir = $outdir;
$model->outurl = $outurl;
$model->modelhost = $serverip;
$model->runid = $runid;
$model->systemlog_obj = $listobject;
$model->initTimer();
$model->setSessionID();
$model->init();
$target = loadModelElement($targetid);
$target = $target['object'];
error_log("All Objects " . array_keys($unserobjects,1));
error_log("Model Objects " . array_keys($model->components,1));
error_log("Target info ($targetid) = " . get_class($target) . ' ' . $target->name);
for ($i = 1; $i <= $steps ; $i++) {
  $model->step();
  error_log("State for " . $target->name . print_r($target->state,1));
}



?>