<?php


# set up db connection
$noajax = 1;
$projectid = 3;
$scid = 28;

include_once('./xajax_modeling.element.php');
$noajax = 1;
$projectid = 3;
error_reporting(E_ERROR);

if ( count($argv) < 3 ) {
   print("Usage: edit_submatrix.php scenarioid matrix_name \"prop=value\" [elementid] [elemname] [custom1] [custom2] [function (append,overwrite,delete)]\n");
   print("Use '-1' as value for scenarioid to update all scenarios (use with caution) \n");
   die;
}

$scenarioid = $argv[1];
$subcomp_name = $argv[2];
list($prop,$value) = split('=', $argv[3]);

if (isset($argv[4])) {
   $elid = $argv[4];
} else {
   $elid = '';
}
if (isset($argv[5])) {
   $elemname = $argv[5];
} else {
   $elemname = '';
}
if (isset($argv[6])) {
   $custom1 = $argv[6];
} else {
   $custom1 = '';
}
if (isset($argv[7])) {
   $custom2 = $argv[7];
} else {
   $custom2 = '';
}
if (isset($argv[8])) {
   $function = $argv[8];
} else {
   $function = 'append';
}

$segs = array();
$listobject->querystring = "  select elementid, elemname from scen_model_element  ";
$listobject->querystring .= " where ( (scenarioid = $scenarioid) or ($scenarioid = -1) ) ";
if ($elid <> '') {
   $listobject->querystring .= " AND elementid = $elid ";
}
if ($elemname <> '') {
   $listobject->querystring .= " AND elemname = '$elemname' ";
}
if ($custom1 <> '') {
   $listobject->querystring .= " AND custom1 = '$custom1' ";
}
if ($custom2 <> '') {
   $listobject->querystring .= " AND custom2 = '$custom2' ";
}
print("Looking for match <br>\n");
print("$listobject->querystring ; <br>\n");
$listobject->performQuery();
$recs = $listobject->queryrecords;

foreach ($recs as $thisrec) {
   $elid = $thisrec['elementid'];
   $elemname = $thisrec['elemname'];
   print("Editing $subcomp_name on $elemname ($elid) \n");
   $loadres = unSerializeSingleModelObject($elid);
   $thisobject = $loadres['object'];

   if (is_object($thisobject)) {
      if (isset($thisobject->processors[$subcomp_name])) {
         print("Editing Matrix $subcomp_name\n ");
         $thisobject->processors[$subcomp_name]->formatMatrix();
         $orig = $thisobject->processors[$subcomp_name]->matrix_formatted;
         print("Original Matrix: " . print_r($orig,1) . "\n");
         $orig[$prop] = $value;
         ksort($orig);
         print("Modified Matrix: " . print_r($orig,1) . "\n");
         $thisobject->processors[$subcomp_name]->oneDimArrayToMatrix($orig);
         $thisobject->processors[$subcomp_name]->formatMatrix();
         $mod = $thisobject->processors[$subcomp_name]->matrix_formatted;
         print("Final Matrix: " . print_r($mod,1) . "\n");
         //$thisobject->processors[$subcomp_name]->$prop = $value;
         saveObjectSubComponents($listobject, $thisobject, $elid );
      }
   }
}
   
print("Finished.\n");

?>
