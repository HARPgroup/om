
<?php
# set up db connection
$noajax = 1;
$projectid = 3;
$scid = 28;
error_reporting(E_ERROR);
include_once('./xajax_modeling.element.php');
$noajax = 1;
$projectid = 3;

if ( count($argv) < 5 ) {
  // @todo: change syntax from elid comp_name "subprop_name=value" comp_class overwrite
  //        to:
  //        elid comp_name comp_class subprop_name subprop_value comp_class setprop_mode overwrite 
  error_log("Usage: edit_subprop.php elementid comp_name comp_class subprop_name subprop_value [setprop_mode=''] [overwrite=FALSE] \n");
  die;
}

// supported classes to add, if empty, all are eligible
$supported = array();

list($script, $elid, $comp_name, $comp_class, $subprop_name, $subprop_value, $setprop_mode, $overwrite) = $argv;
//error_log("argv: $elid, $comp_name, $comp_class, $subprop_name, $subprop_value, $setprop_mode, overwrite=$overwrite");
$setprop_mode = ($setprop_mode === NULL) ? '' : $setprop_mode;
$overwrite = ($overwrite === NULL) ? FALSE : $overwrite;
// this is the object class of the parent component.
//error_log("argv mods: elid=$elid, comp_name=$comp_name, comp_class=$comp_class, subprop_name=$subprop_name, subprop_value=$subprop_value, setprop_mode=$setprop_mode, overwrite=$overwrite");


if (isset($argv[7])) {
   $overwrite = ( ($overwrite == 1) or (strtolower($overwrite) == 'true')) ? TRUE : FALSE;
} else {
   $overwrite = FALSE;
}


$loadres = unSerializeSingleModelObject($elid);
$thisobject = $loadres['object'];
$type_change = FALSE;
if (is_object($thisobject)) {
  // this is a subcomp, so add if need be
  if (in_array($setprop_mode, array('json-2d', 'json-1d'))) {
    error_log("Trying to set $comp_name -> $subprop_name from JSON  \n");
    $subprop_value = stripslashes($subprop_value);
  } else {
    error_log("Trying to set $comp_name -> $subprop_name = $subprop_value \n");
  }
  error_log("*** Conditional to Overwrite or Add Sub-Comp");
  error_log("*** Overwrite = $overwrite");
  error_log("*** isset(thisobject->processors[$comp_name] = " . isset($thisobject->processors[$comp_name]));
  error_log("*** get_class(thisobject->processors[$comp_name]) <> $comp_class) = " . get_class($thisobject->processors[$comp_name]) );
  error_log("*** ($comp_name == $subprop_name)");
  if ( $overwrite or (!isset($thisobject->processors[$comp_name])) 
    or (
      (  get_class($thisobject->processors[$comp_name]) <> $comp_class) 
      and ($comp_name == $subprop_name)
    )
  ) {
    if (!class_exists($comp_class)) {
      error_log("Cannot find object_class = $comp_class -- skipping.");
      die;
    }
    if (empty($supported) or in_array($comp_class, $supported)) {
      error_log("Adding $comp_name of type $comp_class\n");
      if (isset($thisobject->processors[$comp_name])) {
        error_log("This is a component type change requested");
        $type_change = TRUE;
      }
      $syobj = new $comp_class;
      $thisobject->addOperator($comp_name, $syobj);
      if (!$type_change) {
        error_log("Saving all model operators due to new operator creation");
        $res = saveObjectSubComponents($listobject, $thisobject, $elid, 1, 0);
        // now we reload in case the save caused operators to be re-indexed
        $loadres = unSerializeSingleModelObject($elid);
        $thisobject = $loadres['object'];
      }
    } else {
      error_log("$comp_class not in supported " . print_r($supported, 1));
    }
  }

  error_log("Updating $subprop_name with mode $setprop_mode");
  if (isset($thisobject->processors[$comp_name]) and ($comp_name <> $prop_name) ) {
    error_log("Calling setProp($subprop_name, [...some data...], $setprop_mode)");
    $thisobject->processors[$comp_name]->setProp($subprop_name, $subprop_value, $setprop_mode);
    $thisobject->processors[$comp_name]->objectclass = $comp_class;
    error_log("Saving $comp_name (prop = $prop_name) ");
    $operatorid = array_search($comp_name, array_keys($thisobject->processors));
    if ($operatorid === FALSE) {
      error_log("Cannot find operator $comp_name in object $thisobject->name with elementid $elid.");
    } else {
      // increment since the key in a php array starts at 0, but postgresql array columns start at 1
      $operatorid = $operatorid + 1;
      $cresult = compactSerializeObject($thisobject->processors[$comp_name]);
      $innerHTML .= $cresult['innerHTML'];
      $debughtml .= $cresult['debugHTML'];
      error_log("Saving single operator as ID $operatorid on $elid");
      $xml = $cresult['object_xml'];
      error_log("calling storeElemOperator($elid, $operatorid):" . substr($xml,1,32));
      // store in database
      $store_result = storeElemOperator($elid, $operatorid, $xml);
    }
  } else {
    error_log("Could not add property $subprop_name to $elid");
  }
  //error_log("Save result: $result_html");
}
   
//error_log("Finished.\n");

?>
