<?php

// model run framework to force caching of select components
# set up db connection
#include('config.php');
$noajax = 1;
$projectid = 3;
$runtype = 'normal';
$sumdir = '/var/www/html/d.dh/'; // default for post-processing
include_once('xajax_modeling.element.php');
error_log("Remote Run Parameter: $remote_run ");
error_reporting(E_ERROR);
$required = array('elementid');
$optional = array('');

$runVars = array('runid'=>-1, 'elementid'=>-1, 'cache_runid'=>-1, 'startdate'=>'', 'enddate'=>'', 'cache_level'=>-1, 'cache_list' => '', 'test_only' => 0, 'scenarioid' => -1);

if (isset($_GET['runtype'])) {
   $runtype = $_GET['runtype'];
   $method = 'get';
   $formValues = $_GET;
} else {
   if (isset($_POST['runtype'])) {
      $runtype = $_POST['runtype'];
      $method = 'post';
      $formValues = $_POST;
   } else {
      if (!isset($argv[1])) {
         error_log("Usage: php run_model.php elementid [runid=-1] [runtype(normal,cached,cached_cova)=normal] [startdate] [enddate] [cacheid=-1] [cachelist=elid1,elid2,...] [cache_level=-1] [test_only=0] [scenarioid=$scenarioid] [sumdir=$sumdir]");
         die;
      }
      if (isset($argv[3])) {
         $runtype = $argv[3];
      }
      $vars = array(1=>'elementid', 2=>'runid', 3=>'runtype', 4=>'startdate', 5=>'enddate', 6=>'cache_runid', 7=>'cache_list', 8=>'cache_level', 9=>'test_only', 10=>'scenarioid');
      foreach ($vars as $key => $var) {
         if (isset($argv[$key])) {
            $formValues[$var] = $argv[$key];
         }
      }
      $method = 'console';
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
   if (isset($formValues[$thiskey + $offset])) {
      $optional_input[$thisval] = $formValues[$thiskey + $offset];
   }
}

$startdate = '2000-07-01';
$enddate = '2000-07-31';
$runid = 14;
$cache_runid = 2;
$prop_elid = 321861;

error_log("Run Vars passed in via method $method \n");
error_log("Run type $runtype \n");

switch ($runtype) {
   case 'normal':
   error_log("Calling $runtype run with " . print_r($runVars,1) . "\n");
   runCached($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], array(),  $runVars['cache_level'], array(), $optional_input);
   error_log("Returned from Normal run with " . print_r($runVars,1) . "\n");
   break;
   
   case 'cached_cova':
   error_log("Calling runCOVAProposedWithdrawal() with " . print_r($runVars,1) . "\n");
   runCOVAProposedWithdrawal ($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], $runVars['cache_level'], 0, $runVars['cache_list']);
   error_log("Returned from $runtype  with " . print_r($runVars,1) . "\n");
   break;
   
   case 'cached_wsp':
   error_log("Calling runCiaWatershed() with " . print_r($runVars,1) . "\n");
   // later we can pass this in from the form via the forkRun() routine, but for now we hard wire to use all other control points
   // that are spatially contained in this scenarioid
   $use_all_control_pts = 1;
   runCiaWatershed ($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], $runVars['cache_level'], 0, $runVars['cache_list'], $runVars['test_only'], $use_all_control_pts, $runVars['scenarioid']);
   //runCumulativeWSP ($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], $runVars['cache_level'], 0, $runVars['cache_list']);
   if ($runVars['test_only']) {
      // set the message - testing completed
      setStatus($listobject, $runVars['elementid'], "<b>Testing Completed</b> Returned from runCOVAProposedWithdrawal() with " . print_r($runVars,1), $serverip, 0, $runVars['runid']);
   }
   error_log("Returned from $runtype with " . print_r($runVars,1) . "\n");
   break;
   
   case 'test_wsp':
   error_log("Calling testCiaWatershed() with " . print_r($runVars,1) . "\n");
   // later we can pass this in from the form via the forkRun() routine, but for now we hard wire to use all other control points
   // that are spatially contained in this scenarioid
   $use_all_control_pts = 1;
   testCiaWatershed ($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], $runVars['cache_level'], 0, $runVars['cache_list'], $runVars['test_only'], $use_all_control_pts, $runVars['scenarioid']);
   //runCumulativeWSP ($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], $runVars['cache_level'], 0, $runVars['cache_list']);
   if ($runVars['test_only']) {
      // set the message - testing completed
      setStatus($listobject, $runVars['elementid'], "<b>Testing Completed</b> Returned from runCOVAProposedWithdrawal() with " . print_r($runVars,1), $serverip, 0, $runVars['runid']);
   }
   error_log("Returned from $runtype with " . print_r($runVars,1) . "\n");
   break;
   
   case 'cached':
   case 'cached2':
   error_log("Calling runCached() with " . print_r($runVars,1) . "\n");
//error_reporting(E_ALL);
   // test only uncomment below
   //runCached($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], $runVars['cache_list'], $runVars['cache_level'], array(), array(), true);
   runCached($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], $runVars['cache_list'], $runVars['cache_level'], array(), array(), $runVars['test_only']);
   error_log("Returned from $runtype with " . print_r($runVars,1) . "\n");
   break;
   
   default:
   error_log("Calling runCached run with " . print_r($runVars,1) . "\n");
   runCached($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], array(),  $runVars['cache_level'], array());
   error_log("Returned from $runtype run with " . print_r($runVars,1) . "\n");
   break;
   
}

pg_close($modeldb);//boot the model db to prevent temp name collisions?
if (!$runVars['test_only']) {
// handle post-processing
  $runid = intval(trim($runVars['runid']));
  $elementid = $runVars['elementid'];
  $manifest = $outdir . "/manifest.$runid" . "." . $elementid . ".log";
  error_log("Looking for manifest : $manifest");
  $elements = file($manifest);
  foreach ($elements as $elid) {
    $cmd_output = array();
    $cmd = "cd $sumdir \n";
    $elid = intval(trim($elid));
    $elinfo = getElementInfo($listobject, $elid);
    $mesg = "Post-processing Element " . $elinfo['elemname'] . " with summarize_element.sh $elid $runid";
    setStatus($listobject, $elementid, $mesg, $serverip, 0, $runid);
    $cmd .= "/opt/model/om/drupal/om/sh/summarize_element.sh $elid $runid";
    error_log("Executing Summary : $cmd");
    $forkout = exec( $cmd, $cmd_output );
    $mesg = "Run & Post-Processing Complete for $elid.";
    setStatus($listobject, $elementid, $mesg, $serverip, 0, $runid);
  }
  $mesg = "Run & Post-Processing Complete.";
  setStatus($listobject, $elementid, $mesg, $serverip, 0, $runid);
}
?>
