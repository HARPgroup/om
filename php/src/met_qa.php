<?php

$noajax = 1;
$projectid = 3;
$userid = 1;
$scenarioid = 37;

include_once('xajax_modeling.element.php');

// get inputs 
if (count($argv) < 3) {
  error_log("Usage: php met_qa.php landseg version scenario_long_name");
  die;
}
$landseg = $argv[1];
$scenario = $argv[2];

switch ($version) {
  case 'cbp6':
    $prefix = 'p6';
    
  break;
  
  case 'cbp532':
    $prefix = 'p532';
    
  break;
}
//error_reporting(E_ALL);
##include_once("./lib_batchmodel.php");
$out = array(
  'table_name' =>'cbp_' . $prefix . '_' . $scenario . '_' . $landseg,
  'table_exists' => 0,
  'num_recs' => 0,
  'startdate' => '',
  'enddate' => '',
  'qa_status' => 0,
);

$modeldb->querystring = "  select min(timestamp) as startdate, max(timestamp) as enddate, count(*) as num_recs ";
$modeldb->querystring .= " from \"$tablename\" ";
$modeldb->performQuery();

if ($modeldb->numrows > 0) {
  $out['table_exists'] = 1;
  $out['startdate'] = $modeldb->getRecordValue(1, "startdate");
  $out['enddate'] = $modeldb->getRecordValue(1, "enddate");
  $out['num_recs'] = $modeldb->getRecordValue(1, "num_recs");
  if ($num_recs > 0) {
    $out['qa_status'] = 1; // we've set a pretty low bar here.  should do more later
  }
}

$out_json = json_encode($out);
echo $out_json;
?>
