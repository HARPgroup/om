<?php
// for use in a Views No Result Behavior
$eid = !1;
$link_parts = array('om-model-reports', $eid);
// check if this is a model object or a model run object.
// if it is a model run object, we add a 2nd argument to this link
$obj = entity_load_single('dh_properties', $eid);
if ($obj->varid == dh_varkey2varid('om_scenario', TRUE)) {
  // we got and get the parent id any time we want this since the routine expects that as first argument.
  $link_parts = array('om-model-reports', $obj->featureid, $obj->propname);
  //dpm($link_parts);
}
$link_url = implode("/", $link_parts);
$l = l("Click here to set up report elements.", $link_url, array('query' => array('destination'=> "om-model-info/$eid")));

echo "You have no report settings for $obj->propname -- $l";

?>