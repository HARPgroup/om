#!/user/bin/env drush
<?php
module_load_include('inc', 'om', 'src/om_translate_to_dh');

$args = array();
while ($arg = drush_shift()) {
  $args[] = $arg;
}

// Is single command line arg?
if (count($args) >= 3) {
  $query_type = $args[0];
  $src_entity_type = $args[1];
  $src_id = $args[2];
  $dest_entity_type = $args[3];
  $dest_id = $args[4];
  $propname = $args[5];
} else {
  error_log("Usage: php copy_subcomps.php query_type src_entity_type src_id dest_entity_type dest_id [all/propname[|newname],sub2,...] [cascade=0/1]");
  error_log("Note: 'all' is not yet enabled");
  die;
}

function om_get_copyable($src_prop) {
  // standard fields
/*
  $copyable = array(
    'propname' = array('required', 
    'propvalue', 
    'startdate', 
    'enddate', 
    'propcode', 
    'varid'
  );
  */
  // load field info
  
  
  return $copyable;
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
    'src_entity_type' => $src_entity_type, 
    'dest_entity_type' => $dest_entity_type, 
    'src_id' => $src_id, 
    'dest_id' => $dest_id,
    'propname' => $propname,
  );
}

foreach ($data as $element) {
  if (
    empty($element['src_entity_type'])
    or empty($element['dest_entity_type'])
    or empty($element['propname'])
    or empty($element['src_id'])
    or empty($element['dest_id'])
  ) {
    error_log("Could not process " . print_r($element,1));
    continue;
  }
  $src_entity_type = $element['src_entity_type'];
  $src_id = $element['src_id'];
  $dest_entity_type = $element['dest_entity_type'];
  $dest_id = $element['dest_id'];
  $propname = $element['propname'];
  
  $src_entity = entity_load_single($src_entity_type, $src_id);
  $dest_entity = entity_load_single($dest_entity_type, $dest_id);
  // cache and disable object synch if it exists
  $dcc = om_dh_stashlink($dest_entity, 'om_element_connection');  
  $result = om_copy_properties($src_entity, $dest_entity, $propname);
  // om_copy_properties($src_entity, $dest_entity, $propname, $fields = FALSE, $defprops = FALSE, $allprops = FALSE)
  //$copy->save();
  error_log("Property $copy->propname created with pid = $copy->pid");
  // restore original object synch if it exists
  $link = om_dh_unstashlink($dest_entity, $dcc, 'om_element_connection');
}



?>