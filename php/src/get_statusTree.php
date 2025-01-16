<?php

include_once('./xajax_modeling.element.php');
include_once("./lib_verify.php");
error_log("Called get_statusTree.php" . print_r($argv,1));

/*
$connstring = "host=$dbip dbname=cbp user=$dbuser password=$dbpass";
$dbconn = pg_connect($connstring, PGSQL_CONNECT_FORCE_NEW);
$cbp_listobject = new pgsql_QueryObject;
$cbp_listobject->connstring = $connstring;
$cbp_listobject->ogis_compliant = 1;
$cbp_listobject->dbconn = $dbconn;
$cbp_listobject->adminsetuparray = $adminsetuparray;
*/
$format = 'array';
if (isset($_GET['elementid'])) {
   $elid = $_GET['elementid'];
   $format = 'table';
   $runid = -1;
} else {
   $elid = $argv[1];
   $format = $argv[2];
   $runid = $argv[3];
}
if (isset($_GET['runid'])) {
   $runid = $_GET['runid'];
}
if (isset($_GET['format'])) {
   $format = $_GET['format'];
}
if (isset($_GET['host'])) {
   $host = $_GET['host'];
} else {
   $host = $serverip;
}

error_log("Calling getStatusTree(listobject, $elid, $runid, $host ");
$container_tree = getStatusTree($listobject, $elid, $runid, $host);
error_log("Tree contains:" . count($container_tree));
switch ($format) {
   case 'array':
      $formatted = "Number of elements in tree = " . count($container_tree) . "\n";
      $formatted .= "Container Tree " . print_r($container_tree, 1) . "\n";
   break;
   
   case 'table':
      $formatted = formatPrintContainer($container_tree);
   break;
   
   case 'json':
      $formatted = json_encode($container_tree);
   break;
}

echo $formatted;
?>
