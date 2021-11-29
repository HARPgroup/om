<?php

$noajax = 1;
include_once('xajax_modeling.element.php');
if (count($argv) < 3) {
   print("USAGE: php get_nhd_basins.php pointname lat lon [debug=0] [overwrite=0]\n");
   die;
}
if ($usgsdb) {
  error_log("Successfully connected to: " . pg_host($usgs_db));
} else {
  error_log(pg_last_error($usgsdb));
  die;
}
list($pointname, $latdd, $londd, $debug, $overwrite) = array($argv[1], $argv[2], $argv[3], $argv[4], $argv[5]);
$outlet_info = findNHDSegment($usgsdb, $latdd, $londd);
$outlet = $outlet_info['comid'];
$area = $outlet_info['areasqkm'];
$carea = $outlet_info['cumdrainag'];
print("Outlet COMID : $outlet \n");
print("Outlet Cumulative Area : $carea \n");
print("Outlet Local Area : $area \n");
print("Finding Merged Shape $latdd $londd \n");
$basininfo = getMergedNHDBasin($usgsdb, $latdd, $londd, 0, $debug);
//print(" NHD+ Shape Retrieved \n");
$wkt_geom = $basininfo['the_geom'];
$outlet_comid = $basininfo['outlet_comid'];
error_log("Basin Info flow_segments" . print_r($basininfo['flow_segments'],1));
error_log("Basin Info merged_segments" . print_r($basininfo['merged_segments'],1));
if ($outlet_comid <> $outlet) {
  error_log("Warning: Mismatch outlet, findNHDSegment = $outlet, getMergedNHDBasin = $outlet_comid ");
}
storeNHDMergedShape($usgsdb, $outlet_comid, $wkt_geom, $overwrite,  $debug);

?>
