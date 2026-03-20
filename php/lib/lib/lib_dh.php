<?php

function dh_search_feature($hydrocode, $bundle, $ftype) {
  return TRUE;

}

function om_get_om_model($elid) {
  global $om;
  $uri = $om . "?elementid=$elid";
  error_log("Opening $uri ");
  $json = file_get_contents ($uri);
  //error_log("json:" . $json);
  $om_object = json_decode($json);
  return $om_object;
}

?>