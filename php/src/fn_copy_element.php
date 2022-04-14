<?php

$userid = 1;
$noajax = 1;
include('./xajax_modeling.element.php');

//include_once('/var/www/html/om/xajax_modeling.element.php');
//include_once('lib_batchmodel.php');

// get outlet for terminal CBP segment
// OR, set them up manually as in here


error_reporting(E_ERROR);

if (count($argv) < 2) {
   error_log("Usage: php copy_element.php dest_scenarioid elementid [dest_parent (-1)] [copychildren=-1 (1=copy,0=no children,-1=no links at all] [name=''] \n");
   die;
}
$debug = 0;
$scenid = $argv[1];
$elementid = $argv[2];
if (isset($argv[3])) {
   $destination = $argv[3];
} else {
   $destination = -1;
}
if (isset($argv[4])) {
   $copychildren = $argv[4];
} else {
   $copychildren = -1;
}
$params = array();
$name = '';
if (isset($argv[5])) {
   $name = $argv[5];
}
// place holder for list of sub-comps or props that require a call to reCreate()
$recreate = array();

if ($debug) {
  //error_log("Creating a copy and setting these params: " . print_r($params,1) . "\n");
}


if ($destination > 0) {
   // get scenarioid from destination parent
   $info = getElementInfo($listobject, $destination, 1);
   $scenid = $info['scenarioid'];
   //error_log("Parent Info: " . print_r($info,1) . "\n");
}
//die;


//$debug = 1;

switch ($copychildren) {
   case 0:
      $cloneresult = cloneModelElement($scenid, $elementid, $destination, 0, $debug);
      $newelid = $cloneresult['elementid'];
      $retarr['elementid'] = $newelid;
      if ($destination > 0) {
         $innerHTML .= "New object group $newelid inserted underneath $destination <br>";
         $innerHTML .= createObjectLink($projectid, $scenid, $newelid, $destination, 1);
      }
   break;
   case -1:
      // without any linkages
      $cloneresult = cloneModelElement($scenid, $elementid, $destination, 0, $debug);
      $newelid = $cloneresult['elementid'];
      $retarr['elementid'] = $newelid;
      if ($destination > 0) {
         $innerHTML .= "New object group $newelid inserted underneath $destination <br>";
         $innerHTML .= createObjectLink($projectid, $scenid, $newelid, $destination, 1);
      } else {
         error_log($cloneresult['innerHTML'] . "\n");
      }
   break;
      
   case 1:
     $cbp_copy_params = array(
        'projectid'=>$projectid,
        'dest_scenarioid'=>$scenid,
        'elements'=>array($elementid),
        'dest_parent'=>$destination
     );
     $output = copyModelGroupFull($cbp_copy_params, 1);

     error_log(print_r($output,1));
     $newelid = $output['elementid'];
     if ($destination > 0) {
        $oldparentid = getElementContainer($listobject, $elementid);
        error_log("Changing direct links from the old parent to the new parent for the copied element");
        // we should update any links that the copied object had to its old parent to the new parent
        $listobject->querystring = " update map_model_linkages set dest_id = $destination where src_id = $newelid and linktype = 2";
        error_log("$listobject->querystring ; \n");
        $listobject->performQuery();
        $listobject->querystring = " update map_model_linkages set src_id = $destination where src_id = $oldparentid and dest_id = $newelid and linktype = 2 ";
        error_log("$listobject->querystring ; \n");
        $listobject->performQuery();
     }
   break;
}
if ( !($newelid > 0) ) {
   error_log("Error copying elements\n");
} else {
   error_log("New object created with elementid $newelid \n");
   global $unserobjects;
   error_log("Renaming to: $name");
   if ( !empty($name) ) {
      //$loadres = unSerializeSingleModelObject($newelid);
      //$thisobject = $loadres['object'];
      $loadres = loadModelElement($newelid, array(), 0);
      $thisobject = $loadres['object'];
      error_log("Renaming object to $name\n");
      saveModelObject($newelid, $thisobject, array('name' => $name), $debug) ;
   }
   echo $newelid;
}
?>
