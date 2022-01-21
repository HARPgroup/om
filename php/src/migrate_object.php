<?php
# set up db connection
#include('config.php');
$noajax = 1;
$projectid = 3;
include_once('xajax_modeling.element.php');
#error_reporting(E_ALL);
error_log("Un-serializing Model Object <br>");
if (isset($argv[1])) {
  $elementid = $argv[1];
} else {
  exit;
}
$debug = 0;

// create a shell modelContainer object
// create a timer
error_log("Element $elementid wake() Returned from calling routine.");
$model = new modelContainer();
$model->starttime = '1984-01-01';
$model->endtime = '1984-01-31';
$model->dt = 86400;

// add a timeseriesfile / cbp type

$thisobresult = unSerializeModelObject($elementid);
$thisobject = $thisobresult['object'];
$thisname = $thisobject->name;
$thisobject->debug = 0;
error_log("Returned element named: $thisname ");

// find this objects class attributes that are distinct from parent class 
  // get parent class 
  // get parrent class properties
  // get child class properties 
  // return a array_diff 
  // properties will be number/string/array, does the receiving class need to know this? I don't think so...
  //   what about objects?  yes.  all part of the "class" attribute that this migrates to. 
  // should this just be a method on the php class?  very much like the openmi exporter in the drupal classes?
  // YES
  // BUT, for migrating to create object classes, we DO need to know what properties are defined on the child class
  // so, when loading do we load the class in question and the parent class? 
  // YES, but then we need to make sure that we include parent_class as an attribute in the export
  // Ahh OK, there is a difference between exporting abstract class information versus exporting actual instantiatable objects that are part of an existing model.
  $parent_class = get_parent_class($thisobject);
  $all_properties = get_object_vars($thisobject);
  $parent_properties = get_class_vars($parent_class); // note varname is keys of this array, default values are values 
  $local_properties = array_diff(array_keys($parent_properties, $all_properties);
  // then we implement
    // createClassFromOpenMI() code somewhere else. the importer is the only thing that needs separation of parent/child attributes.
    // could include in export, a list of child specific attributes to facilitate import 
    // for migrating data, not object class defs, we should indicate if an attribute is read-only, that is, only defined on the class and not migrate those.
    
?>