<?php

$noajax = 1;
$projectid = 3;
$userid = 1;

$noajax = 1;
//include_once('/var/www/html/om/xajax_modeling.element.php');
include_once('./xajax_modeling.element.php');

error_reporting(E_ERROR);

if (count($argv) < 2) {
   print("Usage: php delete_element.php elementid \n");
   die;
}
$elid = $argv[1];

$out = deleteModelElement($elid);
error_log($out['innerHTML']);
?>
