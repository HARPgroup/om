<?php


// checks for files/runs fidelity - clears them if they fail vertain tests

// choose the elements to run, these must be root monitoring sites, as indicated by the suffix 'A01' -- 'A12'
$noajax = 1;
$projectid = 3;
include_once("./xajax_modeling.element.php");
include_once("./lib_verify.php");
include_once("./lib_batchmodel.php");

if (isset($argv[6])) {
   $elementid = $argv[1];
   $runid = $argv[2];
   $startdate = $argv[3];
   $enddate = $argv[4];
   $cache_date = $argv[5];
   $current_level = $argv[6];
} else { 
   print("Usage: php fn_checkObjectCacheStatus.php elementid runid startdate enddate cache_date current_level\n");
   die;
}
$order = getElementOrder($listobject, $elementid);
$cache_res = checkObjectCacheStatus($listobject, $elementid, $order, $cache_date, $runid, $current_level, $startdate, $enddate, 1);
error_log("Cache Check:" . print_r($cache_res,1));

?>
