<?php


// checks for files/runs fidelity - clears them if they fail vertain tests

// choose the elements to run, these must be root monitoring sites, as indicated by the suffix 'A01' -- 'A12'
$noajax = 1;
$projectid = 3;
include_once("./xajax_modeling.element.php");
include_once("./lib_verify.php");
include_once("./lib_batchmodel.php");

if (isset($argv[1])) {
   $elementid = $argv[1];
} else { 
   error_log("Usage: php fn_getRunFile.php elementid runid [debug=0]\n");
   die;
}
if (isset($argv[2])) {
   $runid = $argv[2];
} else {
   error_log("Usage: php fn_clearRun.php elementid runid \n");
   die;
}

$branch_info = getRunFile($listobject, $elementid, $runid, $debug);

error_log("Branch $elid Run File Info: " . print_r($branch_info,1));

$outstr = "$branch_info[elementid],$branch_info[output_file],$branch_info[starttime],$branch_info[endtime],$branch_info[run_verified],$branch_info[remote_url],$branch_info[host]";
print $outstr;
?>
