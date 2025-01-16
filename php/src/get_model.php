<?php
error_log("get_model.php loaded.");
error_reporting(E_ALL);
header('Content-Type: application/json');
# set up db connection
#include('config.php');
$noajax = 1;
$projectid = 3;
error_log("Loading xajax_modeling.element.php");
include_once('xajax_modeling.element.php');
error_log("xajax loaded.");
$debug = 0;

$elementid = FALSE;
$include_geom = FALSE;
if (isset($_GET['elementid'])) {
   $elementid = $_GET['elementid'];
}
if (isset($argv[1])) {
   $elementid = $argv[1];
}
if (isset($_GET['include_geom'])) {
   $include_geom = $_GET['include_geom'];
}
if (isset($argv[2])) {
   $include_geom = $argv[2];
}
if ($elementid === FALSE) {
  $info = "ERROR: get_model.php called without elementid.";
  $json = json_encode(array('error' => "get_model.php called without elementid"));
} else {
  // this is a single component retrieval only
  $thisobresult = unSerializeSingleModelObject($elementid);
  $thisobject = $thisobresult['object'];
  foreach ($thisobject->processors as $procname => $thisproc) {
    $thisobject->processors[$procname]->object_class = get_class($thisproc);
    error_log("object_class $procname = " .   $thisobject->processors[$procname]->object_class);
  }
  # retrieve child component linkages
  $linkrecs = getChildComponentType($listobject, $elementid);
  $thisobject->components = $linkrecs;
  // this simply stashes an array of children object elementids
  // client function can iterate through children if desired
  $thisobject->components = getChildComponentType($listobject, $elementid);

  $thisname = $thisobject->name;
  $thisobject->debug = 0;
  $thisobject->outdir = $outdir;
  $thisobject->outurl = $outurl;
  if (!$include_geom) {
    unset($thisobject->the_geom);
  }
  //$thisobject->sleep();
  $thisobject = $thisobject->toArray();
  error_log("************ get_model.php $elementid called **************");
  $info = "json_encode handled object properly.";
  $json = json_encode($thisobject);
  if (!$json) {
    $info = "ERROR handling whole object Json." . "\n";
    $info .= "JSON Error: " . json_last_error_msg() . "\n";
    $vars = get_object_vars($thisobject);
    $json = json_encode($vars);
    if (!$json) {
      $info = "ERROR handling output of get_object_vars." . "\n";
      $info .= "JSON Error: " . json_last_error_msg() . "\n";
      //error_log(print_r(get_object_vars($thisobject),1));

      foreach (get_object_vars($thisobject) as $varname => $vardata) {
        $json = json_encode($thisobject->{$varname}, 0 ,2);
        if (!$json) {
          $info .= "Error handling individual get_object_vars $varname" . "\n";
          $info .= json_last_error_msg() . "\n";
        }
      }
    }
  }
}


error_log("$info");
error_log("************ get_model.php $elementid Finished **************");
echo $json;
?>
