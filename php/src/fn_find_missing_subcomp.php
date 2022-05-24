<?php

$noajax = 1;
$projectid = 3;
$userid = 1;
$scenarioid = 37;
$wd_template_id = 284895;
# New Generic Surface Water User: 340402

include_once('xajax_modeling.element.php');
//error_reporting(E_ALL);
##include_once("./lib_batchmodel.php");

if (count($argv) < 3) {
   print("Usage: fn_find_missing_subcomp.php scenario subcomp custom1 [elementid] \n");
   die;
}
error_log(print_r($argv,1));
$scenario = $argv[1];
$subcomp = $argv[2];
$custom1 = $argv[3];
$elementid = isset($argv[4]) ? $argv[4] : -1;

$listobject->querystring = "select elementid, custom2 from scen_model_element where custom1 = '$custom1' and scenarioid = $scenarioid ";
if ($elementid > 0) {
  $listobject->querystring .= " and elementid = $elementid ";
}
$listobject->performQuery();
error_log("$listobject->querystring ");
$elements = $listobject->queryrecords;
$i = 0;
$j = 0; // absent
$bad_els = array();
$bad_props = array(); // not used
$bad_deets = array();
$k = 0; // present
$has_els = array();
$has_props = array(); // not used
$has_deets = array();
foreach ($elements as $element) {
  $elid = $element['elementid'];
  $riverseg = $element['custom2'];
  $loadres = unSerializeSingleModelObject($elid);
  $object = $loadres['object'];
  if (isset($object->processors['vahydro_hydroid'])) {
    $vahydro_hydroid = $object->processors['vahydro_hydroid']->getProp('value');
  } else {
    $vahydro_hydroid = -1;
  }
  $i++;
  error_log("Checking $subcomp on $object->name ");
  if (!isset($object->processors[$subcomp])) {
    $j++;
    error_log("$subcomp on Element $object->name ($elid) is empty");
    if (!in_array($vahydro_hydroid, $bad_pids)) {
      if ($vahydro_hydroid > 0) {
        $bad_pids[] = $vahydro_hydroid;
      }
    }
    if (!in_array($elid, $bad_els)) {
      $bad_els[] = $elid;
    }
    if (!isset($bad_deets[$elid])) {
      $bad_deets[$elid] = array('elementid'=>$elid, 'vahydro_pid' => $vahydro_hydroid);
    }
    $bad_deets[$elid][$thisproc->name] = 'empty';
  } else {
    $k++;
    if (!in_array($vahydro_hydroid, $has_pids)) {
      if ($vahydro_hydroid > 0) {
        $has_pids[] = $vahydro_hydroid;
      }
    }
    if (!in_array($elid, $has_els)) {
      $has_els[] = $elid;
    }
    if (!isset($has_deets[$elid])) {
      $has_deets[$elid] = array('elementid'=>$elid, 'vahydro_pid' => $vahydro_hydroid);
    }
  }
}
error_log("***********************************************");
error_log("Elements Missing $subcomp: " . implode(" ", $bad_els));
error_log("VAHydro pids : " . implode(" ", $bad_pids));
error_log("Details: " . print_r($bad_deets,1));
error_log("Total Missing items $j out of $i checked.");

error_log("***********************************************");
error_log("Elements WITH $subcomp: " . implode(" ", $has_els));
error_log("VAHydro pids : " . implode(" ", $has_pids));
error_log("Details: " . print_r($has_deets,1));
error_log("Total Missing items $j out of $i checked.");

?>