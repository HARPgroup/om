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
$subcomp = '';

if (count($argv) <= 3){
  error_log("Usage: php test_step.php modelid runid targetid [steps] [subcomp]");
  exit;
}
if (count($argv) > 3) {
  $modelid = $argv[1]; // the model container 
  $runid = $argv[2]; // the model container 
  $targetid = $argv[3]; // the element to test 
}
if (count($argv) > 4) {
  $steps = $argv[4]; // the element to test 
}
if (count($argv) > 5) {
  $subcomp = $argv[5]; // the subcomp to test 
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
if ($subcomp <> '') {
  $sc = $target->processors[$subcomp];
  $sc->debug = 1;
  $sc->debugmode = 1; // prints to stderr
}
error_log("All Objects " . array_keys($unserobjects,1));
error_log("Model Objects " . array_keys($model->components,1));
error_log("Target info ($targetid) = " . get_class($target) . ' ' . $target->name);
if ($subcomp <> '') {
  $target->processors[$subcomp]->debug = 1;
}

for ($i = 1; $i <= $steps ; $i++) {
  $model->step();
  error_log("State for " . $target->name . "(" . get_class($target) . ") " . print_r($target->debugFormat($target->state),1));
  if ($subcomp <> '') {
    $sv = $target->debugFormat($sc->state);
    $ar = $target->debugFormat($sc->arData);
    error_log("arData and state for $subcomp" . "(" . get_class($sc) . ") ");
    error_log("arData:" . print_r($ar,1) );
    error_log("state:" . print_r($sv,1) );
  }
}

$outmesg = "<b>Finished test model run. Note - this is not a complete run and re-run should be completed before analyzing data.<br>";
error_log($outmesg);
$model->systemLog($outmesg,0);
?>
