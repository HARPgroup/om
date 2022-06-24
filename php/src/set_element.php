<?php
/**
 * @file
 * Load model element from JSON code.
 * - If elementid property is set, try to retrieve local mode element from db
 */
# set up db connection
$noajax = 1;
$projectid = 3;
$scid = 28;
error_reporting(E_ERROR);
include_once('./xajax_modeling.element.php');
$noajax = 1;
$projectid = 3;

if ( count($argv) < 3 ) {
  error_log("Usage: set_element.php elementid openmi_json \n");
  die;
}

list($script, $elid, $openmi_json) = $argv;

// for now we over-ride and get hard coded file if we send test as json
if ($openmi_json === 'test') {
  $elid = 340268;
  $openmi_json = file_get_contents('https://raw.githubusercontent.com/HARPgroup/om/master/data/json/difficult_run.json');
}
$openmi_json = stripslashes($openmi_json);
$json_obj = json_decode(trim($openmi_json), TRUE);
error_log("**** json_obj[name] = " . $json_obj['name']);
error_log("json_last_error() " . json_last_error());
error_log("json encoding " . mb_detect_encoding());
error_log("Calling unSerializeSingleModelObject($elid)"); 

if ($elid > 0) {
  $loadres = unSerializeSingleModelObject($elid);
  $thisobject = $loadres['object'];
} else {
  # need to handle case where we create a new element.
  # get object_class 
  # create new blank object 
  # set all props
  # save 
}
error_log("Retrieved $thisobject->name"); 
//error_log("$openmi_json"); 
$thisobject->setProp('all', $openmi_json, 'json-2d');
// @todo: handle these for now we just want to see if it works 
//saveModelObject($elid, $thisobject, array('name' => $thisobject->name));
$res = saveObjectSubComponents($listobject, $thisobject, $elid, 1, 0);
//error_log("Finished.\n");
$params = array('name' => $json_obj['name']);
if (isset($json_obj['object_class'])) {
  $params['object_class'] = $json_obj['object_class'];
}
$ret = saveModelObject($elid, $thisobject, $params, FALSE);

//error_log("Save Query: " . $ret['debugHTML']);
?>
