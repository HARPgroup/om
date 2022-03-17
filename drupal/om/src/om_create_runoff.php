#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');
// go thru list of Local and remote elements without CBP6 Runoffs
// Create a clone of an object in OM 
// create a shell on VAHydro 
// pull_once from OM to VAHydro 

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

// Is single command line arg?
if (count($args) >= 3) {
  $query_type = $args[0];
  $src_id = $args[1];
  $dest_id = $args[2];
} else {
  print("Usage: php om_create_runoff.php query_type src_id dest_id [all/sub1[|newname],sub2,...] [cascade=0/1] \n");
  die;
}

if ($query_type == 'file') {
  $filepath = $src_id;
  error_log("File requested: $filepath");
  $data = array();
  $file = fopen($filepath, 'r');
  $header = fgetcsv($file, 0, "\t");
  if (count($header) == 0) {
    $header = fgetcsv($file, 0, "\t");
  }
  while ($line = fgetcsv($file, 0, "\t")) {
    
    $data[] = array_combine($header,$line);
  }
  error_log("File opened with records: " . count($data));
  error_log("Header: " . print_r($header,1));
  error_log("Record 1: " . print_r($data[0],1));
} else {
  $data = array();
  $data[] = array(
    'feature_hydroid' => $feature_hydroid, 
    'coverage_hydroid' => $coverage_hydroid,
    'coverage_hydrocode' => $coverage_hydrocode,
    'feature_name' => $feature_name,
    'feature_hydrocode' => $feature_hydrocode,
    'coverage_name' => $coverage_name,
    'propname' => $propname,
    'prop_varkey' => $prop_varkey,
    'propvalue' => $propvalue,
  );
}

foreach ($data as $element) {
  $src_prop = om_load_dh_model($query_type, $src_id, $model_name);
  $dest_prop = om_load_dh_model($query_type, $dest_id, $model_name);
}



?>