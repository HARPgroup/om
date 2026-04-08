
<?php
# set up db connection
#include('config.php');
$noajax = 1;
$projectid = 3;
$runtype = 'normal';
$sumdir = '/var/www/html/d.dh/'; // default for post-processing
include_once('xajax_modeling.element.php');

error_reporting(E_ERROR);
print("Un-serializing Model Object <br>");
$debug = 0;
// **********************************************************************//
// THIS SECTION REPLICATES run_model.php calling:
// php -f run_model.php 214595 200 cached_meta_model 1998-01-01 2002-12-31 -1 -1 1 0 37
// **********************************************************************//

list($elementid, $runid) = array(214595, 200);
$argv = array('run_model.php', 214595, 200, 'cached_meta_model', '1998-01-01', '2002-12-31', -1, -1, 1, 0, 37);
$runVars = array('runid'=>-1, 'elementid'=>-1, 'cache_runid'=>-1, 'startdate'=>'', 'enddate'=>'', 'cache_level'=>-1, 'cache_list' => '', 'test_only' => 0, 'scenarioid' => -1);
$vars = array(1=>'elementid', 2=>'runid', 3=>'runtype', 4=>'startdate', 5=>'enddate', 6=>'cache_runid', 7=>'cache_list', 8=>'cache_level', 9=>'test_only', 10=>'scenarioid');
$formValues = array();
foreach ($vars as $key => $var) {
    if (isset($argv[$key])) {
       $formValues[$var] = $argv[$key];
    }
 }

foreach ($runVars as $thiskey => $thisval) {
    if (isset($formValues[$thiskey])) {
       $runVars[$thiskey] = $formValues[$thiskey];
    }
 }
$offset = $thiskey;
$optional_input = array('dt'); // This starts at the 9th param.
                               // A hinky way of doing it, but works OK for cmd line
foreach ($optional as $thiskey => $thisval) {
    if (isset($formValues[$thiskey . $offset])) {
       $optional_input[$thisval] = $formValues[$thiskey . $offset];
    }
 }

// *****************************************************************//
// simulate this:
// runCached($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], $runVars['cache_list'], $runVars['cache_level'], array(), array(), $runVars['test_only']);
// NOTE: function runCached($elementid, $runid, $cache_runid, $startdate, $enddate, $cache_list, $cache_level, $dynamics, $input_props = array(), $test_only = 0);
// *****************************************************************//

$elementid = $runVars['elementid'];$runid = $runVars['runid'];$cache_runid = $runVars['cache_runid'];
$startdate = $runVars['startdate'];$enddate = $runVars['enddate'];$cache_list = $runVars['cache_list'];
$cache_level = $runVars['cache_level'];$dynamics = array();$input_props = array();$test_only = $runVars['test_only'];


global $modeldb, $listobject, $outdir, $serverip;
$run_date = date('r');
if (!is_array($cache_list)) {
  if (trim($cache_list) == "") {
  $cache_list = array();
  } else {
    $cache_list = explode(',', $cache_list);
  }
}
// this routine, if passed empty cache_list and dynamics list will simply perform a normal model run
if (is_array($elementid)) {
   if (count($elementid) > 0) {
      $firstel = $elementid[0];
   } else {
      $firstel = -1;
   }
 } else {
   $firstel = $elementid;
 }
$input_props['outdir'] = isset($input_props['outdir']) ? $input_props['outdir'] : $outdir;

if ( (strlen($startdate) > 0) and (strlen($enddate) > 0)) {
    $input_props['model_startdate'] = $startdate;
    $input_props['model_enddate'] = $enddate;
 }

if (!$test_only) {
   setStatus($listobject, $firstel, "Initiating Model Run for Elementid $firstel with runCached() function", $serverip, 1, $runid, -1, 1);
 } else {
   // stash the old status
   $test_status = verifyRunStatus($listobject, $firstel, $runid);
 }

// load up all of the things that are in the base model, with caching specified
error_log("Calling loadModelUsingCached(modeldb, $elementid, $runid, $cache_runid with cache_level = $cache_level");
error_log("  with input_props = " . print_r($input_props,1));


// ***** BReaking Down loadModelUsingCached
//$model_elements = loadModelUsingCached($modeldb, $elementid, $runid, $cache_runid, $input_props, $cache_level, $cache_list, $run_date);
$model_elements = loadModelUsingCached($modeldb, $elementid, $runid, $cache_runid, $input_props, $cache_level, $cache_list, $run_date);

<?php
# set up db connection
#include('config.php');
$noajax = 1;
$projectid = 3;
$runtype = 'normal';
$sumdir = '/var/www/html/d.dh/'; // default for post-processing
include_once('xajax_modeling.element.php');

error_reporting(E_ERROR);
print("Un-serializing Model Object <br>");
$debug = 0;
// **********************************************************************//
// THIS SECTION REPLICATES run_model.php calling:
// php -f run_model.php 214595 200 cached_meta_model 1998-01-01 2002-12-31 -1 -1 1 0 200
// **********************************************************************//

list($elementid, $runid) = array(214595, 200);
$argv = array('run_model.php', 214595, 200, 'cached_meta_model', '1998-01-01', '2002-12-31', -1, -1, 1, 0, 37);
$runVars = array('runid'=>-1, 'elementid'=>-1, 'cache_runid'=>-1, 'startdate'=>'', 'enddate'=>'', 'cache_level'=>-1, 'cache_list' => '', 'test_only' => 0, 'scenarioid' => -1);
$vars = array(1=>'elementid', 2=>'runid', 3=>'runtype', 4=>'startdate', 5=>'enddate', 6=>'cache_runid', 7=>'cache_list', 8=>'cache_level', 9=>'test_only', 10=>'scenarioid');
$formValues = array();
foreach ($vars as $key => $var) {
    if (isset($argv[$key])) {
       $formValues[$var] = $argv[$key];
    }
 }

foreach ($runVars as $thiskey => $thisval) {
    if (isset($formValues[$thiskey])) {
       $runVars[$thiskey] = $formValues[$thiskey];
    }
 }
$offset = $thiskey;
$optional_input = array('dt'); // This starts at the 9th param.
                               // A hinky way of doing it, but works OK for cmd line
foreach ($optional as $thiskey => $thisval) {
    if (isset($formValues[$thiskey . $offset])) {
       $optional_input[$thisval] = $formValues[$thiskey . $offset];
    }
 }

// *****************************************************************//
// simulate this:
// runCached($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], $runVars['cache_list'], $runVars['cache_level'], array(), array(), $runVars['test_only']);
// NOTE: function runCached($elementid, $runid, $cache_runid, $startdate, $enddate, $cache_list, $cache_level, $dynamics, $input_props = array(), $test_only = 0);
// *****************************************************************//

$elementid = $runVars['elementid'];$runid = $runVars['runid'];$cache_runid = $runVars['cache_runid'];
$startdate = $runVars['startdate'];$enddate = $runVars['enddate'];$cache_list = $runVars['cache_list'];
$cache_level = $runVars['cache_level'];$dynamics = array();$input_props = array();$test_only = $runVars['test_only'];


global $modeldb, $listobject, $outdir, $serverip;
$run_date = date('r');
if (!is_array($cache_list)) {
  if (trim($cache_list) == "") {
  $cache_list = array();
  } else {
    $cache_list = explode(',', $cache_list);
  }
}
// this routine, if passed empty cache_list and dynamics list will simply perform a normal model run
if (is_array($elementid)) {
   if (count($elementid) > 0) {
      $firstel = $elementid[0];
   } else {
      $firstel = -1;
   }
 } else {
   $firstel = $elementid;
 }
$input_props['outdir'] = isset($input_props['outdir']) ? $input_props['outdir'] : $outdir;

if ( (strlen($startdate) > 0) and (strlen($enddate) > 0)) {
    $input_props['model_startdate'] = $startdate;
    $input_props['model_enddate'] = $enddate;
 }

if (!$test_only) {
   setStatus($listobject, $firstel, "Initiating Model Run for Elementid $firstel with runCached() function", $serverip, 1, $runid, -1, 1);
 } else {
   // stash the old status
   $test_status = verifyRunStatus($listobject, $firstel, $runid);
 }

// load up all of the things that are in the base model, with caching specified
error_log("Calling loadModelUsingCached(modeldb, $elementid, $runid, $cache_runid with cache_level = $cache_level");
error_log("  with input_props = " . print_r($input_props,1));


// ***** BReaking Down loadModelUsingCached
//$model_elements = loadModelUsingCached($modeldb, $elementid, $runid, $cache_runid, $input_props, $cache_level, $cache_list, $run_date);
$model_elements = loadModelUsingCached($modeldb, $elementid, $runid, $cache_runid, $input_props, $cache_level, $cache_list, $run_date);

