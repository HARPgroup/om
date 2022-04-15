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

$elementid = 6;

if (isset($_GET['elementid'])) {
   $elementid = $_GET['elementid'];
}
if (count($argv) > 3) {
   $modelid = $argv[1]; // the model container 
   $runid = $argv[2]; // the model container 
   $targetid = $argv[3]; // the element to test 
}

global $modeldb, $listobject, $tmpdir, $shellcopy, $ucitables, $scenarioid, $outdir, $outurl, $goutdir, $gouturl, $unserobjects, $adminsetuparray, $wdm_messagefile, $basedir, $model_startdate, $model_enddate;


$model_elements = loadModelUsingCached($modeldb, $elementid, $runid, $cache_runid, $input_props, $cache_level, $cache_list, $run_date);
$model = $model_elements['object'];
$target = loadModelElement($targetid);
$model->outdir = $outdir;
$model->outurl = $outurl;
$model->modelhost = $serverip;
$model->runid = $runid;
$model->systemlog_obj = $listobject;
$model->initTimer();
$model->setSessionID();
$model->init();
for ($i = 1; $i < $num ; $i++) {
  $model->step();
  error_log("State" . print_r($target->state,1));
}



?>