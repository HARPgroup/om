<?php

$noajax = 1;
$projectid = 3;
$userid = 1;

$noajax = 1;
//include_once('/var/www/html/om/xajax_modeling.element.php');
include_once('./xajax_modeling.element.php');

error_reporting(E_ERROR);

if (count($argv) < 2) {
   print("Usage: php delete_element.php elementid [debug=0] [root_force=0]\n");
   die;
}
$elid = $argv[1];
$debug=0;
$root_force=FALSE;
if (count($argv) > 2) {
  $debug=$argv[2];
}
if (count($argv) > 3) {
  $root_force=$argv[3];
  if ($root_force == 1) {
    $root_force = TRUE;
  }
}
$out = deleteModelElement($elid, $debug, $root_force);
error_log($out['innerHTML']);
?>
