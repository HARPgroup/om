<?php

//error_log("Loading lib_batchmodel.php from /www/lib tree");
$connstring = "host=$vwuds_dbip dbname=vwuds user=$dbuser password=$dbpass";
$dbconn = pg_connect($connstring, PGSQL_CONNECT_FORCE_NEW);

$model_db = $listobject;

$vwudsdb = new pgsql_QueryObject;
$vwudsdb->connstring = $connstring;
$vwudsdb->ogis_compliant = 1;
$vwudsdb->dbconn = $dbconn;
$vwudsdb->adminsetuparray = $adminsetuparray;

$connstring = "host=$vwuds_dbip dbname=vpdes user=$dbuser password=$dbpass";
$dbconn = pg_connect($connstring, PGSQL_CONNECT_FORCE_NEW);

$vpdesdb = new pgsql_QueryObject;
$vpdesdb->connstring = $connstring;
$vpdesdb->ogis_compliant = 1;
$vpdesdb->dbconn = $dbconn;
$vpdesdb->adminsetuparray = $adminsetuparray;

// new pg 14
$connstring = "host=$vwuds_dbip port=5432 dbname=va_hydro user=$dbuser password=$dbpass";
// new pg 9.5
//$connstring = "host=192.168.0.20 dbname=va_hydro user=$dbuser password=$dbpass";
$dbconn = pg_connect($connstring, PGSQL_CONNECT_FORCE_NEW);

$usgsdb = new pgsql_QueryObject;
$usgsdb->connstring = $connstring;
$usgsdb->ogis_compliant = 1;
$usgsdb->dbconn = $dbconn;
$usgsdb->adminsetuparray = $adminsetuparray;

global $templateid, $t_reachid, $pswd_group_tid, $lstemplateid, $sw_tid, $gw_tid, $ps_contid, $ps_tid, $projectid, $cbp_scen, $cova_met_container, $cbp_met_template_id, $listobject, $vwudsdb, $model_db, $cbp_listobject, $vpdesdb;

$templateid = 176615;
$t_reachid = 207667;
$pswd_group_tid = 207671;
$lstemplateid = 207683;
$sw_tid = 207673;
$gw_tid = 207961;
$ps_contid = 216384;
$ps_tid = 216385;
$gwd_tid = 215093; //generic withdrawal/discharge object
$projectid = 3;
$cbp_scen = 4;
$lutemplateid = 1111;
// meteorology
$cova_met_container = 207659;
$cbp_met_template_id = 320989;
// proposed projects
$cova_pp_container = 321854;
$cova_pp_template_id = 323192;
// templates for ICPRB linkages 
$icprb_pstid = 319780;
$icprb_wdtid = 319782;

$cbp_rivers = array(
   'SU' => array('name'=>'Upper Susquehanna River, above confluence with West Branch', 'terminal_seg'=>'SU0_0000_0000', 'terminal_name'=>'Susquehanna River'),
   'SW' => array('name'=>'Susquehanna River, West Branch', 'terminal_seg'=>'SU0_0000_0000', 'terminal_name'=>'Susquehanna River'),
   'SJ' => array('name'=>'Juniata River', 'terminal_seg'=>'SU0_0000_0000', 'terminal_name'=>'Susquehanna River'),
   'SL' => array('name'=>'Lower Susquehanna River below West Branch confluence not including the Juniata River', 'terminal_seg'=>'SU0_0000_0000', 'terminal_name'=>'Susquehanna River'),
   'PR' => array('name'=>'Potomac River', 'terminal_seg'=>'PR0_0000_0000', 'terminal_name'=>'Potomac River'),
   'PU' => array('name'=>'Upper Potomac River, above Shenandoah confluence', 'terminal_seg'=>'PR0_0000_0000', 'terminal_name'=>'Potomac River'),
   'PS' => array('name'=>'Shenandoah River', 'terminal_seg'=>'PR0_0000_0000', 'terminal_name'=>'Potomac River'),
   'PM' => array('name'=>'Middle Potomac River, including Monocacy River below Shenandoah confluence, above Chain Bridge', 'terminal_seg'=>'PR0_0000_0000', 'terminal_name'=>'Potomac River'),
   'PL' => array('name'=>'Lower Potomac River, below Chain Bridge', 'terminal_seg'=>'PR0_0000_0000', 'terminal_name'=>'Potomac River'),
   'JR' => array('name'=>'James River', 'terminal_seg'=>'JR0_0000_0000', 'terminal_name'=>'James River'),
   'JU' => array('name'=>'Upper James River, above the Maury River confluence', 'terminal_seg'=>'JR0_0000_0000', 'terminal_name'=>'James River'),
   'JL' => array('name'=>'Lower James River, below the Maury River confluence, above Richmond, Virginia', 'terminal_seg'=>'JR0_0000_0000', 'terminal_seg'=>'JR0_0000_0000', 'terminal_name'=>'James River'),
   'JA' => array('name'=>'Appomattox River', 'terminal_seg'=>'JR0_0000_0000', 'terminal_name'=>'James River'),
   'JB' => array('name'=>'James River, below Richmond, Virginia, not including the Appomattox River', 'terminal_seg'=>'JR0_0000_0000', 'terminal_seg'=>'JR0_0000_0000', 'terminal_name'=>'James River'),
   'YP' => array('name'=>'Pamunkey River', 'terminal_seg'=>'YR0_0000_0000', 'terminal_name'=>'York River'),
   'YM' => array('name'=>'Mattaponi River', 'terminal_seg'=>'YR0_0000_0000', 'terminal_name'=>'York River'),
   'YL' => array('name'=>'York River, below Mattaponi and Pamunkey confluence, including the Piankatank River', 'terminal_seg'=>'YR0_0000_0000', 'terminal_seg'=>'YR0_0000_0000', 'terminal_name'=>'York River'),
   'YR' => array('name'=>'York River', 'terminal_seg'=>'YR0_0000_0000', 'terminal_name'=>'York River'),
   'RR' => array('name'=>'Rappahannock River', 'terminal_seg'=>'RR0_0000_0000', 'terminal_seg'=>'RR0_0000_0000', 'terminal_name'=>'Rappahannock River'),
   'RU' => array('name'=>'Upper Rappahannock River', 'terminal_seg'=>'RR0_0000_0000', 'terminal_name'=>'Rappahannock River'),
   'RL' => array('name'=>'Lower Rappahannock River', 'terminal_seg'=>'RR0_0000_0000', 'terminal_name'=>'Rappahannock River'),
   'XU' => array('name'=>'Patuxent River above Bowie, Maryland', 'terminal_seg'=>'PX0_0000_0000', 'terminal_name'=>'Patuxent River'),
   'XL' => array('name'=>'Patuxent River below Bowie, Maryland', 'terminal_seg'=>'PX0_0000_0000', 'terminal_name'=>'Patuxent River'),
   'PX' => array('name'=>'Patuxent River', 'terminal_seg'=>'PX0_0000_0000', 'terminal_name'=>'Patuxent River'),
   'WS' => array('name'=>'Western shore', 'terminal_seg'=>'WS0_0000_0000', 'terminal_name'=>'Western Shore'),
   'WL' => array('name'=>'Lower Western shore', 'terminal_seg'=>'WS0_0000_0000', 'terminal_name'=>'Western Shore'),
   'WM' => array('name'=>'Middle Western shore, including the Patapsco and Back Rivers', 'terminal_seg'=>'WS0_0000_0000', 'terminal_name'=>'Western Shore'),
   'WU' => array('name'=>'Upper Western shore', 'terminal_seg'=>'WS0_0000_0000', 'terminal_name'=>'Western Shore'),
   'ES' => array('name'=>'Eastern Shore', 'terminal_seg'=>'ES0_0000_0000', 'terminal_name'=>'Eastern Shore'),
   'EU' => array('name'=>'Upper Eastern Shore', 'terminal_seg'=>'ES0_0000_0000', 'terminal_name'=>'Eastern Shore'),
   'EL' => array('name'=>'Lower Eastern Shore', 'terminal_seg'=>'ES0_0000_0000', 'terminal_name'=>'Eastern Shore'),
   'EM' => array('name'=>'Middle Eastern Shore, including the Choptank River', 'terminal_seg'=>'ES0_0000_0000', 'terminal_name'=>'Eastern Shore'),
   'GY' => array('name'=>'Part of the Youghiogheny River', 'terminal_seg'=>'GY0_0000_0000', 'terminal_name'=>'Youghiogheny River'),
   'DE' => array('name'=>'Delmarva Peninsula, outside the Chesapeake Bay watershed', 'terminal_seg'=>'DE0_0000_0000', 'terminal_name'=>'Delmarva Peninsula'),
   'TU' => array('name'=>'Part of the Upper Tennessee River', 'terminal_seg'=>'TU0_0000_0000', 'terminal_name'=>'Tennessee River'),
   'BS' => array('name'=>'Part of the Big Sandy River', 'terminal_seg'=>'BS0_0000_0000', 'terminal_name'=>'Big Sandy River'),
   'NR' => array('name'=>'Part of the New River', 'terminal_seg'=>'NR0_0000_0000', 'terminal_name'=>'New River'),
   'OD' => array('name'=>'Dan River, tributary of the Roanoke River', 'terminal_seg'=>'OD0_0000_0000', 'terminal_name'=>'Roanoke River'),
   'OR' => array('name'=>'Part of Roanoke River, not including the Dan River', 'terminal_seg'=>'OR0_0000_0000', 'terminal_name'=>'Roanoke River'),
   'MN' => array('name'=>'Meherrin and Nottoway rivers', 'terminal_seg'=>'MN0_0000_0000', 'terminal_name'=>'Nottoway River')
);

function getVWUDSWithdrawals($vwudsdb, $listobject, $parentid, $debug = 0) {
   // get mpids that fit the spatial containment query
   // also get mpids that do not have a valid lat/lon but are in the same HUC
   $listobject->querystring = "  select st_asText(poly_geom) as wkt_geom from scen_model_element where elementid = $parentid";
   if ($debug) {
      print($listobject->querystring . "\n");
   }
   $listobject->performQuery();
   $wktshape = $listobject->getRecordValue(1,'wkt_geom');
   // must return list of MPID/USERID/ACTION/CAT_MP
   $records = getUserMPIDsByWKT($vwudsdb, $wktshape, '', "'WL'", $debug);
   //if ($debug) {
      print($records['debug'] . " <br>\n");
   //}
   // returns $retvals['mpids'], $retvals['userids'] and $retvals['records'] = $listobject->queryrecords
   // merge multiple measuring points
   //print_r($records['records']);
   //print("\n");
   foreach ($records['records'] as $thisrec) {
      $userid = $thisrec['USERID'];
      $cat = $thisrec['CAT_MP'];
      //$type = $thisrec[$mptype];
      $type = $thisrec['mptype'];
      //$key = $userid . $mptype;
      //$key = $userid . $type;
      $key = $userid . $type . $cat;
      if (!isset($recs[$key])) {
         $recs[$key] = $thisrec;
         if (strlen(trim($thisrec['SOURCE'])) > 0) {
            $recs[$key]['SOURCE'] = array(trim($thisrec['SOURCE']));
         }
         if (strlen(trim($thisrec['mpid'])) > 0) {
            $recs[$key]['mpid'] = array(trim($thisrec['mpid']));
         }
         if (strlen(trim($thisrec['VPDES'])) > 0) {
            $recs[$key]['VPDES'] = array(trim($thisrec['VPDES']));
         }
      } else {
         if ($thisrec['mptype'] == $recs[$key]['mptype']) {
            if (strlen(trim($thisrec['SOURCE'])) > 0) {
               $recs[$key]['SOURCE'][] = trim($thisrec['SOURCE']);
            }
            if (strlen(trim($thisrec['mpid'])) > 0) {
               $recs[$key]['mpid'][] = trim($thisrec['mpid']);
            }
            if (strlen(trim($thisrec['VPDES'])) > 0) {
               $recs[$key]['VPDES'][] = trim($thisrec['VPDES']);
            }
            $recs[$key]['max_daily_wd'] += floatval($thisrec['max_daily_wd']);
         }
      }
   }
   return $recs;
}

function getVWUDSTransfers($vwudsdb, $listobject, $parentid, $debug = 0) {
   // get mpids that fit the spatial containment query
   // also get mpids that do not have a valid lat/lon but are in the same HUC
   $listobject->querystring = "  select st_asText(poly_geom) as wkt_geom from scen_model_element where elementid = $parentid";
   if ($debug) {
      print($listobject->querystring . "\n");
   }
   $listobject->performQuery();
   $wktshape = $listobject->getRecordValue(1,'wkt_geom');
   // must return list of MPID/USERID/ACTION/CAT_MP
   $records = getUserMPIDsByWKT($vwudsdb, $wktshape, "'TW'", '', $debug);
   // returns $retvals['mpids'], $retvals['userids'] and $retvals['records'] = $listobject->queryrecords
   $recs = $records['records'];
   return $recs;
}

function getVPDESDischarges($vpdesdb, $listobject, $parentid, $debug = 0) {
   // get mpids that fit the spatial containment query
   // also get mpids that do not have a valid lat/lon but are in the same HUC
   $listobject->querystring = "  select st_asText(poly_geom) as wkt_geom from scen_model_element where elementid = $parentid";
   if ($debug) {
      print($listobject->querystring . "\n");
   }
   $listobject->performQuery();
   $wktshape = $listobject->getRecordValue(1,'wkt_geom');
   // must return list of MPID/USERID/ACTION/CAT_MP
   $records = getUserVPDESIDsByWKT($vpdesdb, $wktshape, '', $debug);
   $recs = $records['records'];
   if ($debug) {
      print($records['query']);
   }
   return $recs;
}

function getUSGSGages($usgsdb, $listobject, $parentid, $debug = 0) {
   // get mpids that fit the spatial containment query
   // also get mpids that do not have a valid lat/lon but are in the same HUC
   $listobject->querystring = "  select st_asText(poly_geom) as wkt_geom from scen_model_element where elementid = $parentid";
   print($listobject->querystring . "\n");
   $listobject->performQuery();
   $wktshape = $listobject->getRecordValue(1,'wkt_geom');
   // must return list of MPID/USERID/ACTION/CAT_MP
   $records = getGagesByWKT($usgsdb, $wktshape, -1, $debug);
   $recs = $records['records'];
   if ($debug) {
      //print($records['query']);
   }
   return $recs;
}



function deleteCOVAWithdrawals($listobject, $parentid, $criteria = array(), $debug = 0) {
   $recs = getCOVAWithdrawals($listobject, $parentid, $criteria, $debug);
   foreach ($recs as $thisrec) {
      print("Going to delete " . $thisrec['elementid'] . " <br>\n");
      deleteObject(array('elementid'=>$thisrec['elementid']));
   }
}

function addGenericWaterUser($scenid, $parentid, $w_cat, $w_name, $w_type, $debug = 0) {
   global $vwudsdb, $model_db,$templateid, $t_reachid, $pswd_group_tid, $lstemplateid, $sw_tid, $gw_tid, $ps_contid, $ps_tid, $gwd_tid, $projectid, $cbp_scen;
   print("Adding Generic Withdrawal, $w_name, type $w_cat on $parentid <br>\n");
   $pswd_pid = getCOVAPS($model_db, $parentid);
   $props = array('name'=>$w_name, 'waterusetype'=>$w_cat, 'wdtype'=>$w_type, 'custom1' =>'generic_water_user', 'discharge_enabled'=>0);
   print("Setting properties to: " . print_r($props,1) . " <br>\n");
   print("Calling: addModelElement($scenid, $gwd_tid, $props, $pswd_pid) <br>\n");
   $output = addModelElement($scenid, $gwd_tid, $props, $pswd_pid, $debug);
   print($output . " <br>\n");
}

function addModelElement($scenid, $tid, $props, $parentid = -1, $debug = 0) {
   global $model_db;
   $output = cloneModelElement($scenid, $tid, $parentid, 0, $debug);
   $recid = $output['elementid'];
   if ($debug) {
      print($output['innerHTML'] . "<br>\n");
   }
   print("Setting properties on $recid \n" . print_r($props,1) . "\n");
   if (count($props) > 0) {
      updateObjectProps($projectid, $recid, $props);
   }
   $obres = unSerializeSingleModelObject($recid);
   $thisobject = $obres['object'];
   if (method_exists($thisobject, 'reCreate')) {
      error_log("reCreate() method exists");
      $thisobject->reCreate();
      $innerHTML = saveObjectSubComponents($model_db, $thisobject, $recid );
      print("$innerHTML \n");
   }
   return $innerHTML;
}

function getWithdrawalGroup($scenarioid, $listobject, $subwatershed_elid, $groupcat, $groupname, $g_template, $debug = 0, $add_missing = 1) {
   // returns the requested type group, inserts it if it does not exist
   $existing = getWithdrawalTypeGroups($listobject, $subwatershed_elid, $criteria = array(), $debug = 0);
   $exists = FALSE;
   $g_elid = array();
   if ($groupname <> '') {
      $g_elid[$groupcat] = FALSE;
   }
   foreach($existing as $thisrec) {
      //print("Existing Withdrawal Groups: " . print_r($thisrec,1) . "<br>\n");
      $gcat = $thisrec['custom2'];
      if ( ($gcat == $groupcat) or ($groupcat == '') ) {
         $exists = TRUE;
         $g_elid[$gcat] = $thisrec['elementid'];
      }
   }
   if (!$exists and $add_missing) {
      // must add this group
      // get the PS/WD container ID first
      $tops = getChildComponentCustom1($listobject, $subwatershed_elid, 'cova_pswd', 1);
      $cid = $tops[0]['elementid'];
      //print("\nCloning cloneModelElement($scenarioid, $g_template, $subwatershed_elid, 0) \n");
      $clone = cloneModelElement($scenarioid, $g_template, $cid, 0);
      $g_elid = $clone['elementid'];
      $props = array('custom2'=>$groupcat,'name'=>$groupname);
      updateObjectProps(-1, $g_elid, $props);
      print_r($clone);
      if ($debug) {
         print("\nObject for $groupcat created with ID = $g_elid \n");
      }
   } else {
      if ($debug) {
         print("Object for $groupcat already exists with ID = $g_elid \n");
      }
   }
   return $g_elid;
}




function getCOVAPointSourceContainer($scenarioid, $listobject, $subwatershed_elid, $g_template, $debug = 0) {
   // returns the requested type group, inserts it if it does not exist
   $cid = getCOVAPS($listobject, $subwatershed_elid);
   $exists = getChildComponentCustom1($listobject, $cid, 'cova_ps_group', 1);
   if (count($exists) == 0) {
      // must add this group
      // get the PS/WD container ID first
      print("\nCloning cloneModelElement($scenarioid, $g_template, $subwatershed_elid, 0) \n");
      $clone = cloneModelElement($scenarioid, $g_template, $cid, 0, $debug);
      $g_elid = $clone['elementid'];
      if ($debug) {
         print($clone['innerHTML'] . "<br>\n");
      }
      $props = array('custom1'=>'cova_ps_group', 'custom2'=>'');
      updateObjectProps(-1, $g_elid, $props);
      if ($debug) {
         print_r($clone);
         print("\nObject for $groupcat created with ID = $g_elid \n");
      }
   } else {
      $g_elid = $exists[0]['elementid'];
      if ($debug) {
         print("Object for $groupcat already exists with ID = $g_elid \n");
      }
   }
   if ($g_elid <> -1) {
      return $g_elid;
   } else {
      return FALSE;
   }
}

function getCOVAPSContNoCreate($scenarioid, $listobject, $subwatershed_elid, $debug = 0) {
   // returns the requested type group, inserts it if it does not exist
   $cid = getCOVAPS($listobject, $subwatershed_elid);
   $exists = getChildComponentCustom1($listobject, $cid, 'cova_ps_group', 1);
   if (count($exists) > 0) {
      $g_elid = $exists[0]['elementid'];
      if ($debug) {
         print("Object for $groupcat already exists with ID = $g_elid \n");
      }
      return $g_elid;
   } else {
      return FALSE;
   }
}


function getWithdrawalTypeGroups($listobject, $elementid, $criteria = array(), $debug = 0) {
   // $criteria will be some screening facility, but for now it does not work, all are obtained
   $children = array();
   $tops = getChildComponentCustom1($listobject, $elementid, 'cova_pswd', -1);
   foreach ($tops as $thistop) {
      $gid = $thistop['elementid'];
      $containers = getChildComponentCustom1($listobject, $gid, 'cova_water_usetype_group', $limit = -1);
      foreach ($containers as $thisgroup) {
         array_push($children, $thisgroup);
      }
   }
   return $children;
}

function getCOVAWithdrawals($listobject, $elementid, $criteria = array(), $debug = 0) {
   // $criteria will be some screening facility, but for now it does not work, all are obtained
   $children = array();
   $child_types = array('wsp_waterUser','wsp_vpdesvwuds','blankShell','waterSupplyElement');
   $containers = getWithdrawalTypeGroups($listobject, $elementid, $criteria, $debug);
   if ($debug) {
      print("Looking for withdrawals in $elementid <br>\n");
   }
   foreach ($containers as $thisgroup) {
      if ($debug) {
         print("Checking for type = " .print_r($thisgroup,1) . " <br>\n");
      }
      $parentid = $thisgroup['elementid'];
      foreach ($child_types as $ct) {
         $childrecs = getChildComponentType($listobject, $parentid, $ct, -1, $debug);
         foreach ($childrecs as $thischild) {
            array_push($children, $thischild);
         }
      }
   }
   // now, get any generic water users that are residing in the base container
   $at_large = getAtLargeWithdrawals($listobject, $elementid, $criteria, $debug);
   $all = array_merge($children, $at_large);
   return $all;
}

function getAtLargeWithdrawals($listobject, $elementid, $criteria, $debug) {
  $children = array();
  $tops = getChildComponentCustom1($listobject, $elementid, 'cova_pswd', $limit = -1, $debug);
  $imps = getChildComponentCustom1($listobject, $elementid, 'cova_impoundment', $limit = -1, $debug);
  $tops = array_merge($tops, $imps);
  foreach ($tops as $thistop) {
    $gid = $thistop['elementid'];
    $child_types = array('wsp_waterUser','wsp_vpdesvwuds','blankShell','waterSupplyElement');
    error_log("Getting At-Large withdrawals");
    foreach ($child_types as $ct) {
       $childrecs = getChildComponentType($listobject, $gid, $ct, -1, $debug);
       foreach ($childrecs as $thischild) {
          array_push($children, $thischild);
       }
    }
    $generics = getChildComponentCustom1($listobject, $gid, 'generic_water_user', -1, $debug);
    foreach ($generics as $thisgroup) {
       array_push($children, $thisgroup);
    }
  }
  return $children;
}

function deleteCOVAPointSources($listobject, $pscontid, $criteria = array(), $debug = 0) {
   $recs = getChildComponentCustom1($listobject, $pscontid, 'cova_pointsource');
   foreach ($recs as $thisrec) {
      print("Going to delete " . $thisrec['elementid'] . " <br>\n");
      deleteObject(array('elementid'=>$thisrec['elementid']));
   }
}

function getCOVAPointSources($listobject, $elid, $criteria = array(), $debug = 0) {
   global $ps_contid;
   $contid = getCOVAPointSourceContainer($scenid, $listobject, $elid, $ps_contid, 1);
   $recs = getChildComponentCustom1($listobject, $contid, 'cova_pointsource');
   return $recs;
}

function createCOVAWithdrawalObjects($segs, $scenid, $projectid) {
   global $vwudsdb, $listobject, $cbp_listobject, $pswd_group_tid, $sw_tid, $gw_tid;
    // $pswd_group_tid - the template for the ps/withdrawal group container
    // $sw_tid - surface water withdrawal template object ID
    // $gw_tid - surface water withdrawal template object ID
    // $segs = array of river segments to operate on, this routine will look into the model database and get their ID
    
   foreach ($segs as $this_segment) {
      $seginfo = getCBPInfoByRiverseg($listobject, $this_segment);
      $parentinfo = getComponentCustom($listobject, $scenid, 'cova_ws_container', $this_segment, 1);
      $parentid = $parentinfo[0]['elementid'];
      $wd = getVWUDSWithdrawals($vwudsdb, $listobject, $parentid, 0);
      print_r($wd);
      print("<br>\n");
      $result = deleteCOVAWithdrawals($listobject, $parentid, array(), 0);
      foreach ($wd as $thiswd) {
         $w_cat = $thiswd['CAT_MP'];
         $mpid = $thiswd['mpid'];
         $uid = $thiswd['USERID'];
         $wtype = $thiswd['mptype'];
         $vwudsdb->querystring = "select typename from waterusetype where typeabbrev = '$w_cat'";
         $vwudsdb->performQuery();
         $gname = $vwudsdb->getRecordValue(1,'typename');
         $grecs = getWithdrawalGroup($scenid, $listobject, $parentid, $w_cat, $gname, $pswd_group_tid, 1);
         $gid = $grecs[$w_cat];
         if ($gid) {
            $tid = -1;
            switch($thiswd['mptype']) {
               case 'GW':
                  $tid = $gw_tid;
               break;

               case 'SW':
                  $tid = $sw_tid;
               break;
            }
            print("Withdrawal group $wtype, $w_cat, $gname has ID $gid \n");
            if ($tid > 0) {
               print("Adding copy of $tid \n");
               $output = cloneModelElement($scenid, $tid, $gid, 0);
               $recid = $output['elementid'];
               print("Setting properties on $recid \n" . print_r($props,1) . "\n");
               $props = array('name'=>$thiswd['facility'] . ' ' . $thiswd['SOURCE'] . '(' . $thiswd['mpid'] . ')', 'id1'=>$uid, 'id2'=>$mpid, 'custom2' =>$uid, 'discharge_enabled'=>0, 'wdtype'=>$wtype);
               updateObjectProps($projectid, $recid, $props);
               $obres = unSerializeSingleModelObject($recid);
               $thisobject = $obres['object'];
               if (method_exists($thisobject, 'reCreate')) {
                  error_log("reCreate() method exists");
                  $thisobject->reCreate();
                  $innerHTML = saveObjectSubComponents($listobject, $thisobject, $recid );
                  print("$innerHTML \n");
               }
            }
         }
      }
      print("Withdrawal objects <br>\n");
      //die;
   }
}

function createCOVAPSWDObjects($segs, $scenid, $projectid, $do_wds = 1, $do_ps = 1) {
   global $vwudsdb, $vpdesdb, $listobject, $cbp_listobject, $sw_tid, $gw_tid, $ps_tid, $pswd_group_tid, $ps_contid;
    // $pswd_group_tid - the template for the ps/withdrawal group container
    // $sw_tid - surface water withdrawal template object ID
    // $gw_tid - surface water withdrawal template object ID
    // $segs = array of river segments to operate on, this routine will look into the model database and get their ID
    
   foreach ($segs as $this_segment) {
      $seginfo = getCBPInfoByRiverseg($listobject, $this_segment);
      $parentinfo = getComponentCustom($listobject, $scenid, 'cova_ws_container', $this_segment, 1);
      $parentid = $parentinfo[0]['elementid'];
      print("Looking for withdrawals and point sources on $this_segment ($parentid) <br>\n");
      $vpdes_ids = array();
      if ($do_wds) {
         $wd = getVWUDSWithdrawals($vwudsdb, $listobject, $parentid, 0);
         print_r($wd);
         $result = deleteCOVAWithdrawals($listobject, $parentid, array(), 0);
         foreach ($wd as $thiswd) {
            $w_cat = $thiswd['CAT_MP'];
            $mpid = $thiswd['mpid'];
            $uid = $thiswd['USERID'];
            $wtype = $thiswd['mptype'];
            $vpdes = $thiswd['VPDES'];
            $vwudsdb->querystring = "select typename from waterusetype where typeabbrev = '$w_cat'";
            $vwudsdb->performQuery();
            $gname = $vwudsdb->getRecordValue(1,'typename');
            // look for vpdes info if it is set on the table
            $props = array('name'=>$thiswd['ownname'] . ' ' . $thiswd['facility'] . ' ' . join(',',array_unique($thiswd['SOURCE'])), 'id1'=>$uid, 'id2'=>join(',',array_unique($thiswd['mpid'])), 'custom1'=>'cova_withdrawal', 'custom2' =>$thiswd['mpid'][0], 'discharge_enabled'=>0, 'waterusetype'=>$w_cat, 'wdtype'=>$wtype);
            if (count($vpdes) > 0) {
               // look for the vpdes ID in the location table
               // add the points
               $props['vpdes_permitno'] = join(',',array_unique($vpdes));
               $props['discharge_enabled'] = 1;
               foreach ($vpdes as $thisvp) {
                  // stash these vpdes in an array so that we can make sure that we don't duplicate them later
                  $vpdes_ids[] = trim($thisvp);
               }
            }

            $grecs = getWithdrawalGroup($scenid, $listobject, $parentid, $w_cat, $gname, $pswd_group_tid, 1);
            $gid = $grecs[$w_cat];

            if ($gid) {
               $tid = -1;
               switch($thiswd['mptype']) {
                  case 'GW':
                     $tid = $gw_tid;
                  break;

                  case 'SW':
                     $tid = $sw_tid;
                  break;
               }
               print("Withdrawal group $wtype, $w_cat, $gname has ID $gid \n");
               if ($tid > 0) {
                  print("Adding copy of $tid for " . $props['name'] . "\n");
                  $output = cloneModelElement($scenid, $tid, $gid, 0);
                  $recid = $output['elementid'];
                  error_log("Setting properties on $recid \n" . print_r($props,1) . "\n");
                  updateObjectProps($projectid, $recid, $props);
                  $obres = unSerializeSingleModelObject($recid);
                  $thisobject = $obres['object'];
						error_log("Pre-Save Processor Names: " . print_r(array_keys($thisobject->processors),1));
                  if (method_exists($thisobject, 'reCreate')) {
                     error_log("reCreate() method exists");
                     $thisobject->reCreate();
                     $innerHTML = saveObjectSubComponents($listobject, $thisobject, $recid );
                     //print("$innerHTML \n");
							error_log("Post-Save Processor Names: " . print_r(array_keys($thisobject->processors),1));
                  }
               }
            }
         }
         print("Withdrawal objects Completed<br>\n");
      }      
      if ($do_ps) {
         // clear existing point sources
         $contid = getCOVAPointSourceContainer($scenid, $listobject, $parentid, $ps_contid, $debug);
         print("PS Container ID - $contid \n");
         $result = deleteCOVAPointSources($listobject, $contid, array(), 0);
         // now, get all VPDES points
         print("VPDES records added as part of VWUDS records " . print_r($vpdes_ids,1) . "\n");
         $ps = getVPDESDischarges($vpdesdb, $listobject, $parentid, 0);
         print("\n VPDES records found \n");
         foreach ($ps as $thisps) {
            $vpdesno = trim($thisps['vpdes_permit_no']);
            if (in_array($vpdesno, $vpdes_ids)) {
               print("VPDES Permit $vpdesno already added\n");
            } else {
               $vpdes_ids[] = $vpdesno;
               print_r($thisps);
               $props = array('name'=>'VPDES ' . $vpdesno, 'id1'=>'', 'id2'=>'', 'custom1'=>'cova_pointsource');
               $props['vpdes_permitno'] = $vpdesno;
               $props['discharge_enabled'] = 1;
               print_r($props);
               print("Cloning object $ps_tid for $vpdesno\n");
               $output = cloneModelElement($scenid, $ps_tid, $contid, 0);
               print("Setting properties on $recid \n" . print_r($props,1) . "\n");
               $recid = $output['elementid'];
               updateObjectProps($projectid, $recid, $props);
               $obres = unSerializeSingleModelObject($recid);
               $thisobject = $obres['object'];
               if (method_exists($thisobject, 'reCreate')) {
                  error_log("reCreate() method exists");
                  $thisobject->reCreate();
                  $innerHTML = saveObjectSubComponents($listobject, $thisobject, $recid );
                  print("$innerHTML \n");
               }
            }
         }
         print("Point Sources Completed\n");
      }
      //die;
   }
}



function createCOVAPSWDSingle($parentid, $scenid, $projectid) {
   global $vwudsdb, $vpdesdb, $listobject, $cbp_listobject, $pswd_group_tid, $ps_contid, $sw_tid, $gw_tid, $ps_tid;
    // parentid = the model container to look for withdrawals
    // $pswd_group_tid - the template for the ps/withdrawal group container
    // $sw_tid - surface water withdrawal template object ID
    // $gw_tid - surface water withdrawal template object ID
    // $segs = array of river segments to operate on, this routine will look into the model database and get their ID
    
   $seginfo = getCBPInfoByRiverseg($listobject, $this_segment);
   $added = array('vpdes'=>array(),'vwuds'=>array());
   // get any existing withdrawals that are assigned to anothjer object
   $res = getSpatiallyContainedObjects($parentid, array('custom1'=>'cova_withdrawal'));
   $existing_obs = $res['elements'];
   $exist_mps = array();
   foreach ($existing_obs as $key => $val) {
      $pl = getElementPropertyValue($listobject, $val['elementid'], array('id1','id2'), $debug);
      $exist_mps[$pl['id1']]['userid'] = $pl['id1'];
      $exist_mps[$pl['id1']]['elementid'] = $val['elementid'];
      $exist_mps[$pl['id1']]['mpid'] = sort(split(',',$pl['id2']));
   }
   print("This new watershed contains the following existing withdrawals: " . print_r($exist_mps,1) . "\n");
   $wd = getVWUDSWithdrawals($vwudsdb, $listobject, $parentid, 0);
   //print_r($wd);
   print("<br>\n");
   $result = deleteCOVAWithdrawals($listobject, $parentid, array(), 0);

   $vpdes_ids = array();
   foreach ($wd as $thiswd) {
      $w_cat = $thiswd['CAT_MP'];
      $mpid = sort($thiswd['mpid']);
      $uid = $thiswd['USERID'];
      $wtype = $thiswd['mptype'];
      $vpdes = $thiswd['VPDES'];
      $copy = 0;
      if (in_array($uid, array_keys($exist_mps))) {
         print("UserID $uid is in Existing USERID List\n");
         if ($mpid == $exist_mps[$uid]['mpid']) {
            $copy = $exist_mps[$uid]['elementid'];
            print("This water user (" . $copy . ") is identical to existing model object - moving to this new container \n");
         }
      }
      $vwudsdb->querystring = "select typename from waterusetype where typeabbrev = '$w_cat'";
      $vwudsdb->performQuery();
      $gname = $vwudsdb->getRecordValue(1,'typename');
      // look for vpdes info if it is set on the table
      $props = array('name'=>$thiswd['facility'] . ' ' . join(',',array_unique($thiswd['SOURCE'])), 'id1'=>$uid, 'id2'=>join(',',array_unique($thiswd['mpid'])), 'custom2' =>$thiswd['mpid'][0], 'discharge_enabled'=>0, 'waterusetype'=>$w_cat, 'wdtype'=>$wtype);
      if (count($vpdes) > 0) {
         // look for the vpdes ID in the location table
         // add the points
         $props['vpdes_permitno'] = join(',',array_unique($vpdes));
         $props['discharge_enabled'] = 1;
         foreach ($vpdes as $thisvp) {
            // stash these vpdes in an array so that we can make sure that we don't duplicate them later
            $vpdes_ids[] = trim($thisvp);
         }
      }
      $grecs = getWithdrawalGroup($scenid, $listobject, $parentid, $w_cat, $gname, $pswd_group_tid, 1);
      $gid = $grecs[$w_cat];
      if ($gid) {
         $tid = -1;
         switch($thiswd['mptype']) {
            case 'GW':
               $tid = $gw_tid;
            break;

            case 'SW':
               $tid = $sw_tid;
            break;
         }
         print("Withdrawal group $wtype, $w_cat, $gname has ID $gid \n");
         if ($tid > 0) {
            // should check to see if this wd or ps is alrady in the model under another container
            // check by userid (custom2 field)
            if (!($copy > 0)) {
               print("Adding copy of $tid \n");
               $output = cloneModelElement($scenid, $tid, $gid, 0);
               print("Setting properties on $recid \n" . print_r($props,1) . "\n");
               $recid = $output['elementid'];
               updateObjectProps($projectid, $recid, $props);
               $obres = unSerializeSingleModelObject($recid);
               $thisobject = $obres['object'];
               if (method_exists($thisobject, 'reCreate')) {
                  error_log("reCreate() method exists");
                  $thisobject->reCreate();
                  $innerHTML = saveObjectSubComponents($listobject, $thisobject, $recid );
                  print("$innerHTML \n");
               }
            } else {
               // just moving existing object
               print("Moving element $copy beneath element $gid \n");
               createObjectLink(-1, $scenid, $copy, $gid, 1);
            }
         }
      }
   }
   print("Withdrawal objects <br>\n");
   // clear existing point sources
   $contid = getCOVAPointSourceContainer($scenid, $listobject, $parentid, $ps_contid, $debug);
   print("PS Container ID - $contid \n");
   $result = deleteCOVAPointSources($listobject, $contid, array(), 0);
   // now, get all VPDES points
   print("VPDES records added as part of VWUDS records " . print_r($vpdes_ids,1) . "\n");
   /*
   $res = getSpatiallyContainedObjects($parentid, array('custom1'=>'cova_pointsource'));
   $existing_obs = $res['elements'];
   $exist_ps = array();
   foreach ($existing_obs as $key => $val) {
      $pl = getElementPropertyValue($listobject, $val['elementid'], array('vpdes_permitno'), $debug);
      $exist_ps[$pl['id1']]['vpdes_permitno'] = $pl['vpdes_permitno'];
      $exist_ps[$pl['elementid']]['userid'] = $pl['elementid'];
      $exist_ps[$pl['id1']]['mpid'] = sort(split(',',$pl['id2']));
   }
   print("This new watershed contains the following existing withdrawals: " . print_r($exist_mps,1) . "\n");
   */
   $ps = getVPDESDischarges($vpdesdb, $listobject, $parentid, 0);
   print("\n VPDES records found \n");
   foreach ($ps as $thisps) {
      $vpdesno = trim($thisps['vpdes_permit_no']);
      if (in_array($vpdesno, $vpdes_ids)) {
         print("VPDES Permit $vpdesno already added\n");
      } else {
         $vpdes_ids[] = $vpdesno;
         print_r($thisps);
         $props = array('name'=>'VPDES ' . $vpdesno, 'id1'=>'', 'id2'=>'', 'custom1'=>'cova_pointsource');
         $props['vpdes_permitno'] = $vpdesno;
         $props['discharge_enabled'] = 1;
         print_r($props);
         print("Cloning object $ps_tid for $vpdesno\n");
         $output = cloneModelElement($scenid, $ps_tid, $contid, 0);
         print("Setting properties on $recid \n" . print_r($props,1) . "\n");
         $recid = $output['elementid'];
         updateObjectProps($projectid, $recid, $props);
         $obres = unSerializeSingleModelObject($recid);
         $thisobject = $obres['object'];
         if (method_exists($thisobject, 'reCreate')) {
            error_log("reCreate() method exists");
            $thisobject->reCreate();
            $innerHTML = saveObjectSubComponents($listobject, $thisobject, $recid );
            print("$innerHTML \n");
         }
      }
   }
   print("\n");
   return $added;
   //die;
}

function getCBPInfoByRiverseg($listobject, $riverseg, $debug = 0) {
   $retvals = array();
   $listobject->querystring = "select riverseg, watershed, rivername, max(contrib_area_sqmi) as contrib_area_sqmi, sum( st_area2d( st_transform(the_geom,26918)) * 3.86102159E-7) as local_area_sqmi from  sc_cbp5 where riverseg = '$riverseg' group by riverseg, watershed, rivername ";
   if ($debug) {
      print(" $listobject->querystring <br>\n");
   }
   $listobject->performQuery();
   $retvals = $listobject->queryrecords[0];
   $retvals['reach_code'] = substr($retvals['riverseg'],9,4);
   if (count($listobject->queryrecords) > 0) {
      return $retvals;
   } else {
      return FALSE;
   }
}

function getCOVADischarges($listobject, $scenarioid, $parentid, $criteria = array(), $debug = 0) {
   global $ps_contid;
   // $criteria will be some screening facility, but for now it does not work, all are obtained
   $children = array();
   $child_types = array('wsp_vpdesvwuds');
   $cid = getCOVAPSContNoCreate($scenarioid, $listobject, $parentid, $debug);
   if ($debug) {
      print("Looking for withdrawals in $elementid <br>\n");
   }
   
   foreach ($child_types as $ct) {
      $childrecs = getChildComponentType($listobject, $cid, $ct, -1, $debug);
      foreach ($childrecs as $thischild) {
         array_push($children, $thischild);
      }
   }
   
   return $children;

}

function getCOVACBPSubnodal($listobject, $scenarioid, $riverseg) {

   // retrfoieves the cova_channel sub-component
   // when given the 'cova_runoff' containers ID
   $elinfo = getComponentCustom($listobject, $scenarioid, 'cova_ws_subnodal', $riverseg);
   if (count($elinfo) > 0) {
      $elid = $elinfo[0]['elementid'];
   } else {
      $elid = -1;
   }
   return $elid;

}

function getCOVACBPContainer($listobject, $scenarioid, $riverseg) {

   // retrfoieves the cova_channel sub-component
   // when given the 'cova_runoff' containers ID
   $elinfo = getComponentCustom($listobject, $scenarioid, 'cova_ws_container', $riverseg);
   if (count($elinfo) > 0) {
      $elid = $elinfo[0]['elementid'];
   } else {
      $elid = -1;
   }
   return $elid;

}

function getCOVACBPPointContainer($listobject, $latdd, $londd) {
   $listobject->querystring = "  select riverseg from sc_cbp53 ";
   $listobject->querystring .= " where st_contains(the_geom,  st_transform(st_setsrid(st_makePoint($londd, $latdd), 4326),26918) ) ";
   //print("$listobject->querystring \n");
   $listobject->performQuery();
   
   if (count($listobject->queryrecords) > 0) {
      return $listobject->queryrecords[0]['riverseg'];
   } else {
      return FALSE;
   }

}



function getCOVACBPLandsegPointContainer($latdd, $londd, $debug = 0) {
   global $cbp_listobject;
   //$cbp_listobject->querystring = "  select fipsab from gis_cbp_landsegs ";
   //$cbp_listobject->querystring .= " where st_contains(the_geom,  st_transform(st_setsrid(st_makePoint($londd, $latdd), 4326),26918) ) ";
   $cbp_listobject->querystring = "  select fipsab from gis_cbp_landsegs_merged_dd ";
   $cbp_listobject->querystring .= " where st_contains(the_geom, st_setsrid(st_makePoint($londd, $latdd), 4326) ) ";
   if ($debug) {
      error_log("$cbp_listobject->querystring \n");
   }
   $cbp_listobject->performQuery();
   
   if (count($cbp_listobject->queryrecords) > 0) {
     if ($debug) {
        error_log("Found " . $cbp_listobject->queryrecords[0]['fipsab'] . "\n");
     }
      return $cbp_listobject->queryrecords[0]['fipsab'];
   } else {
      return FALSE;
   }

}

function findExistingCOVAContainers($listobject, $scenarioid, $latdd, $londd) {

   $recs = getElementsContainingPoint($listobject, $scenarioid, $latdd, $londd);
   print("Existing elements that contain this point: \n");
   print_r($recs);
   print("\n");
   $fromlist = array();
   // compile a list of ANY that contain it, then see if any are watershed containers
   foreach ($recs as $thisrec) {
      $fromlist[] = $thisrec['elementid'];
   }
   $els = array();
   if (count($fromlist) > 0) {
       $pels = getComponentCustom($listobject, $scenarioid, 'cova_ws_container', '', -1, $fromlist, 1);
       $snels = getComponentCustom($listobject, $scenarioid, 'cova_ws_subnodal', '', -1, $fromlist, 1);
      // should screen for smallest one here, but for now, we just take the first one
      $els = array_merge($pels, $snels);
      foreach ($els as $key => $props) {
         $allinfo = getElementInfo($listobject, $props['elementid'], 1);
         $els[$key]['area_sqmi'] = $allinfo['area_sqmi'];
      }
      $ret = subval_sort($els,'area_sqmi');
      print("Found elements of type 'cova_ws_container' and 'cova_ws_subnodal' that contain this point: \n");
      print_r($ret);
      print("\n");
   }
   return $els;
}

function addCBPLandRiverSegment($projectid, $scenarioid,$landseg, $riverseg, $parentid, $debug) {
   global $listobject,  $lstemplateid;
   $pid = getCOVACBPRunoffContainer($listobject, $parentid);
   $clone = cloneModelElement($scenarioid, $lstemplateid, $pid, 0 , $debug);
   print_r($clone);
   print("\n");
   $recid = $clone['elementid'];
   $params = array('name'=>$landseg, 'description' => "$landseg, $riverseg", 'riverseg'=>$riverseg, 'id2'=>$landseg, 'custom1'=>'cova_cbp_lrseg', 'custom2'=>$landseg);
   print("Object properties set to id1 = $landseg, riverseg = $riverseg \n");
   updateObjectProps($projectid, $recid, $params);
   $obres = unSerializeSingleModelObject($recid);
   $lsobject = $obres['object'];
   print("Calling object create() method on object of type " . get_class($lsobject) . "\n");
   //error_reporting(E_ALL);
   if (method_exists($lsobject, 'reCreate')) {
      error_log("reCreate() method exists");
      $lsobject->reCreate();
      //error_log("Saving Sub-components");
      saveObjectSubComponents($listobject, $lsobject, $recid);
      //error_log("Sub-components Saved");
   }
   print("Finished adding landriver segment object \n");
}

function getCOVAMainstem($listobject, $parentid) {

   // retrieves the cova_channel sub-component
   // when given the 'cova_runoff' containers ID
   $elid = getChildCustomType($listobject, $parentid, 'cova_channel', '');
   return $elid;

}

function getCOVAAquaticBio($listobject, $parentid) {

   // retrieves the cova_channel sub-component
   // when given the 'cova_runoff' containers ID
   $elid = getChildCustomType($listobject, $parentid, 'cova_aquaticbio', '');
   return $elid;

}

function getCOVAImpoundment($listobject, $parentid) {



}

function getCOVAPopulation($listobject, $parentid) {



}

function getCOVARunoff($listobject, $parentid) {

   $elid = getChildCustomType($listobject, $parentid, 'cova_runoff', '');
   return $elid;
}

function getVAHydroComponent($listobject, $parentid) {
   // retrieves the va_hydro sub-component (va's HSPF cbp model)
   // when given the 'cova_runoff' containers ID
   $elid = getChildCustomType($listobject, $parentid, 'va_hydro', '');
   return $elid;
}

function getCOVAFlowGage($listobject, $parentid) {
   // get the overarching runoff container, then get the gage inside it
   $parentid = getCOVARunoff($listobject, $parentid);
   $elid = getChildCustomType($listobject, $parentid, 'cova_usgs_gage', '');
   return $elid;
}

function getCOVALanduse($listobject, $parentid) {
   // the land use from the basic model containers land use (separate from CBP land use)


}

function getCOVAPS($listobject, $parentid) {

   $elid = getChildCustomType($listobject, $parentid, 'cova_pswd', '');
   return $elid;
}

function getCBPLanduseData($listobject, $riversegment, $cbp_scenarioid, $landseg = '') {
   // the land use from CBP land connection objects
   // this returns an array like so:
   //   array(0=>array('landseg'=>'A24xxx', 'for'=>1001.0, 'bar'=>11, ....)
   //         1=>array('landseg'=>'A51xxx', 'for'=>1001.0, 'bar'=>11, ....)
   // get the riverseg corresponding to this parentid
   // find the landuse from the cbp database for this scenarioid
   // SEE FUNCTION getCBPLandSegmentLanduse($dbobj, $scenid, $landsegid, $debug, $rseg = NULL)
   // (LOCATED IN lib_local.php as of 3/21/2011)
}

function setNLCDLanduse($elid, $lu_matrix_name = 'landuse_nlcd', $minyear = 1980, $maxyear = 2050, $shape_elid = -1, $translate = 0) {
  global $usgsdb;
  if ($shape_elid > 0) {
    $wktgeom = getElementShape($shape_elid);
  } else {
    $shape_elid = $elid;
    $wktgeom = getElementShape($elid);
  }
  $wktsnip = substr($wktgeom, 1, 64);
  $lu = getNHDLandUseWKT($usgsdb, $wktgeom, 'acres');
  error_log("Found land use: " . print_r($lu, 1));
  $lr = array();
  foreach ( $lu as $thislu => $thisarea ) {
    if (substr($thislu,0,4) == 'nlcd') {
       $lr[] = array('luname'=>$thislu, $minyear => round($thisarea,3), $maxyear => round($thisarea,3));
    }
  }
  switch ($translate) {
    case 1:
    error_log("Translating NLCD to CBP5 land uses for $elid using geom from $shape_elid (wkt: $wktsnip...) ");
      $lr = translateNLCDtoCBP($lu, $minyear, $maxyear);
    break;

    case 'cbp6':
    error_log("Translating NLCD to CBP6 land uses for $elid using geom from $shape_elid (wkt: $wktsnip...)");
      $lr = translateNLCDtoCBP6($lu, $minyear, $maxyear);
    break;

    default:
    break;
  }
   setLUMatrix ($elid, $lu_matrix_name, $lr);
}


function translateNLCDtoCBP ($lu, $minyear, $maxyear, $debug = 0) {
   $nlcd_cbp_lumap = array(
      'nlcd_11' => array('wat'=>1.0),
      'nlcd_12' => array(),
      'nlcd_21' => array('pul'=>0.45, 'iml'=>0.55),
      'nlcd_22' => array('puh'=>0.1, 'imh'=>0.9),
      'nlcd_23' => array('imh'=>1.0),
      'nlcd_23' => array('imh'=>1.0),
      'nlcd_31' => array('bar'=>1.0),
      'nlcd_32' => array('ext'=>1.0),
      'nlcd_33' => array('hvf'=>1.0),
      'nlcd_41' => array('for'=>1.0),
      'nlcd_42' => array('for'=>1.0),
      'nlcd_43' => array('for'=>1.0),
      'nlcd_51' => array('hyw'=>1.0),
      'nlcd_61' => array('hyo'=>1.0),
      'nlcd_71' => array('hyo'=>1.0),
      'nlcd_81' => array('pas'=>1.0),
      'nlcd_82' => array('hom'=>1.0),
      'nlcd_84' => array('hyo'=>1.0),
      'nlcd_85' => array('puh'=>1.0),
      'nlcd_91' => array('for'=>1.0),
      'nlcd_92' => array('for'=>1.0)
   );

   if ($debug) {
      error_log(print_r($lu,1) . "\n");
   }
   $lr = array();
   $maplu = array();
   foreach ( $lu as $thislu => $thisarea ) {
      // check for entry in mapping array
      // if found, perform the map,
      // then check to see if we already have an entry for it, if so, add it to the total, if not create new entry for this lu class
      if (substr($thislu,0,4) == 'nlcd') {
         if (isset($nlcd_cbp_lumap[$thislu])) {
            foreach ($nlcd_cbp_lumap[$thislu] as $luname => $lupct) {
               if (!isset($maplu[$luname])) {
                  $maplu[$luname] = 0.0;
               }
               $maplu[$luname] += $thisarea * $lupct;
            }
         }
      }

   }
   foreach ( $maplu as $thislu => $thisarea ) {
      $lr[] = array('luname'=>$thislu, $minyear => round($thisarea,3), $maxyear => round($thisarea,3));

   }
   return $lr;
}


function translateNLCDtoCBP6($lu, $minyear, $maxyear, $debug = 0) {
   $nlcd_cbp_lumap = array(
      'nlcd_11' => array('wat'=>1.0),
      'nlcd_12' => array(),
      'nlcd_21' => array('ntg'=>0.3, 'mch'=>0.15, 'cnr'=>0.45, 'mci'=>0.1),
      'nlcd_22' => array('ntg'=>0.1, 'mnr'=>0.45, 'mir'=>0.45),
      'nlcd_23' => array('mnr'=>1.0),
      'nlcd_23' => array('mnr'=>1.0),
      'nlcd_31' => array('mcn'=>1.0),
      'nlcd_32' => array('ccn'=>1.0), 
      'nlcd_33' => array('hfr'=>1.0),
      'nlcd_41' => array('for'=>1.0),
      'nlcd_42' => array('for'=>1.0),
      'nlcd_43' => array('for'=>1.0),
      'nlcd_51' => array('hyw'=>1.0),
      'nlcd_61' => array('lhy'=>1.0),
      'nlcd_71' => array('lhy'=>1.0),
      'nlcd_81' => array('pas'=>1.0),
      'nlcd_82' => array('gom'=>1.0),
      'nlcd_84' => array('hom'=>1.0),
      'nlcd_85' => array('ctg'=>1.0),
      'nlcd_91' => array('for'=>1.0),
      'nlcd_92' => array('for'=>1.0)
   );

   if ($debug) {
      error_log(print_r($lu,1) . "\n");
   }
   $lr = array();
   $maplu = array();
   foreach ( $lu as $thislu => $thisarea ) {
      // check for entry in mapping array
      // if found, perform the map,
      // then check to see if we already have an entry for it, if so, add it to the total, if not create new entry for this lu class
      if (substr($thislu,0,4) == 'nlcd') {
         if (isset($nlcd_cbp_lumap[$thislu])) {
            foreach ($nlcd_cbp_lumap[$thislu] as $luname => $lupct) {
               if (!isset($maplu[$luname])) {
                  $maplu[$luname] = 0.0;
               }
               $maplu[$luname] += $thisarea * $lupct;
            }
         }
      }

   }
   foreach ( $maplu as $thislu => $thisarea ) {
      $lr[] = array('luname'=>$thislu, $minyear => round($thisarea,3), $maxyear => round($thisarea,3));

   }
   return $lr;
}

function getCOVACBPRunoffContainer($listobject, $parentid) {
   //get the container for CBP land connection objects in the model
   $lid = getCOVARunoff($listobject, $parentid);
   // locate 'VAHydro model' sub-comp
   $vhid = getVAHydroComponent($listobject, $lid);
   return $vhid;
}


function getCOVACBPLanduseObjects($listobject, $parentid) {
   //get all CBP land connection object in the model
   $vhid = getCOVACBPRunoffContainer($listobject, $parentid);
   // get objects of type 'CBPLandDataConnection'
   // return ->name parameter or id2 parameter
   $landrecs = getChildComponentType($listobject, $vhid, 'CBPLandDataConnection', -1);
   $outrecs = array();
   foreach ($landrecs as $thisrec) {
      $lsid = $thisrec['elementid'];
      // just make sure to set the land seg info
      $unser = unserializeSingleModelObject($lsid);
      $lobj = $unser['object'];
      $lseg = $lobj->id2;
      $riverseg = $lobj->riverseg;
      $outrecs[] = array('elementid'=>$lsid, 'lsrs'=>"$lseg-$riverseg", 'landseg'=>$lseg, 'riverseg'=>$riverseg);
   }
   return $outrecs;
}



function getCOVACBPLRsegObject($listobject, $parentid, $landseg) {
   //get all CBP land connection object in the model
   $vhid = getCOVACBPRunoffContainer($listobject, $parentid);
   // get objects of type 'CBPLandDataConnection'
   // return ->name parameter or id2 parameter
   error_log("$vhid, 'cova_cbp_lrseg', $landseg \n");
   $elid = getChildCustomType($listobject, $vhid, 'cova_cbp_lrseg', $landseg);
   return $elid;
}

function getCOVATribs($listobject, $parentid) {

   $elid = getChildCustomType($listobject, $parentid, 'cova_tribs', '');
   return $elid;

}


function getCOVAUpstream($listobject, $parentid, $debug = 0) {

   $listobject->querystring = "  select elementid from scen_model_element where custom1 = 'cova_upstream' ";
   $listobject->querystring .= "    and elementid in (";
   $listobject->querystring .= "       select src_id from map_model_linkages ";
   $listobject->querystring .= "       where dest_id = $parentid ";
   $listobject->querystring .= "          and linktype = 1";
   $listobject->querystring .= "    )";
   if ($debug) {
      print("Looking for upstream trib container on $parentid <br>\n");
      print("$listobject->querystring ; <br>\n");
   }
   $listobject->performQuery();
   if (count($listobject->queryrecords) > 0) {
      return $listobject->getRecordValue(1,'elementid');
   } else {
      return FALSE;
   }

}


function getCOVADownstream($listobject, $elementid) {
   // for cova objects the receiving stream (next downstream segment) will be a grandparent, since 
   // both "upstream tribs" and "local tribs" are inside of a container, thus the actual receiving waterbody
   // is the parent of that container
   $parent = getElementContainer($listobject, $elementid);
   $grandparent = getElementContainer($listobject, $parent);
   if ($grandparent > 0) {
      return $grandparent;
   } else {
      return FALSE;
   }

}

function insertCOVABasin($listobject, $projectid, $scenarioid, $templateid, $destinationid, $objectname, $basintype) {
   // $templateid - the template for the model object arrangement
   // $destinationid - the elementid of the container that we are linking into, this is NOT the ultimate parent, 
   //   but rather, the segment container that this pours into, be it a trib, or an upstream reach
   // $basintype:
   //    'upstream' - goes into "Upstream Reach Segments" container, 
   //    'trib' - goes into "Local Tribs" container
   $basininfo = createCOVABasin($listobject, $projectid, $scenarioid, $templateid, $objectname);
   $elid = $basininfo['elementid'];
   switch ($basintype) {
      case 'trib':
      $tribs = getCOVATribs($listobject, $destinationid);
      $parentid = $tribs['containerid'];
      break;
      
      case 'upstream':
      $upstream = getCOVAUpstream($listobject, $destinationid);
      $parentid = $upstream['containerid'];
   }
   
   createObjectLink($projectid, $scenarioid, $elid, $parentid, 1);
   
   return $elid;

}

function createCOVABasin($listobject, $projectid, $scenarioid, $templateid, $objectname) {   
   $basininfo = array('elementid' => -1);
   $cbp_copy_params = array(
      'projectid'=>$projectid,
      'dest_scenarioid'=>$scenarioid,
      'elements'=>array($templateid)
   );
   $copy = copyModelGroupFull($cbp_copy_params);
   $map = $copy['element_map'];
   $parent = $map[$templateid]['new_id'];
   $basininfo['elementid'] = $parent;
   $basininfo['innerHTML'] = $copy['innerHTML'];
   updateObjectProps($projectid, $parent, array('name'=>$objectname, 'description'=>"Main CBP Segment Container for $objectname", 'debug'=>0));
   return $basininfo;
}



function createCOVALocalTrib($listobject, $cbp_listobject, $tribname, $templateid, $scenid, $projectid, $local_trib_container, $overwrite = 0) {
   // THIS IS A DRAFT NOT YET TESTED, AND PROBABLY NOT FUNCTIONING
   // create clone of shell for segment
   $cbp_copy_params = array(
      'projectid'=>$projectid,
      'dest_scenarioid'=>$scenid,
      'elements'=>array($templateid)
   );
   // check to see if this trib exists already
   $exists = getChildCustomType($listobject, $local_trib_container, 'cova_ws_subnodal', $tribname);
   print("Does child exist? $exists \n");
   if ( ($exists <= 0) or $overwrite) {
      if ($exists > 0) {
         print("Deleting $exists \n");
         deleteModelElement($exists);
      }
      $output = copyModelGroupFull($cbp_copy_params);
      //print_r($output);
      $elid = $output['element_map'][$templateid]['new_id'];
      print("Clone created with ID = $elid \n");
      // set name/description of element to:
      $params = array('name'=>$tribname, 'description' => "$tribname", 'custom1'=>'cova_ws_subnodal', 'custom2'=>substr($tribname,0,23) );
      print("Setting properties " . print_r($params,1) . "\n");
      $output = updateObjectProps($projectid, $elid, $params, 1);
      //print("Setting Output " . print_r($output,1) . "\n");
      print("Property update output " . $output['innerHTML'] . "\n");
      //print("Property update debugging " . $output['debugHTML'] . "\n");
      // clear all land use
      // clear all ps/wd objects
      createObjectLink($projectid, $scenid, $elid, $local_trib_container, 1);
      print("Finished $tribname <br>\n");
      return $elid;
   } else {
      return -1;
   }
}


function createCOVABasin2($scenid, $projectid, $segs) {
   global $listobject, $cbp_listobject, $templateid, $t_reachid;
   // sets model up based on the CBP 5.3 segment hierarchy
   foreach ($segs as $riverseg) {
      // create clone of shell for segment
      $cbp_copy_params = array(
         'projectid'=>$projectid,
         'dest_scenarioid'=>$scenid,
         'elements'=>array($templateid)
      );
      $output = copyModelGroupFull($cbp_copy_params);
      //print_r($output);
      $elid = $output['element_map'][$templateid]['new_id'];
      // set name/description of element to:
      $river_attributes = getCBPRiverInfo($riverseg);
      $riverseg = $river_attributes['riverseg'];
      $rivername = $river_attributes['rivername'];
      $watershed = $river_attributes['watershed'];
      $params = array('name'=>$rivername, 'description' => "$riverseg, $rivername, $watershed");
      updateObjectProps($projectid, $elid, $params);
      // set the custom1 field on the db to contain the CBP 5.3 riverseg nomenclature
      $listobject->querystring = "  update scen_model_element set elemname = '$rivername', custom2 = '$riverseg' where elementid = $elid";
      print(" $listobject->querystring <br>\n");
      $listobject->performQuery();
      // set shape on parent container db entry
      $listobject->querystring = "  update scen_model_element set poly_geom =  st_transform(a.the_geom,4326), ";
      $listobject->querystring .= " geomtype = 3 ";
      $listobject->querystring .= " from (";
      $listobject->querystring .= "    select geomunion(the_geom) as ";
      $listobject->querystring .= "    the_geom from sc_cbp53 where riverseg = '$riverseg' ";
      $listobject->querystring .= " ) as a ";
      $listobject->querystring .= " where elementid = $elid  ";
      print(" $listobject->querystring <br>\n");
      $listobject->performQuery();
      // set the reach length and slope parameters from the YP3_6690_6720 table in the cbp database
      // 10 | 0.0000378
      // now, this one is on the "model" data base
      $cid = $output['element_map'][$t_reachid]['new_id'];
      setCOVACBPReachProps($projectid, $cbp_listobject, $listobject, $riverseg, $cid, $debug);
      // link this object to the next downstream segment if it exists/has been created
      $parentinfo = getCOVACBPParent($scenid, $riverseg);
      $parentid = $parentinfo['elementid'];
      $linktype = $parentinfo['linktype'];
      if ($parentid <> -1) {
         switch ($linktype) {
            case 'upstream':
               // now link to the "Upstream Tributaries" component in the parent container
               $us_container = getCOVAUpstream($listobject, $parentid);
               $elementname = getElementName($listobject, $parentid);
               createObjectLink($projectid, $scenid, $elid, $us_container, 1);
               $elementname = getElementName($listobject, $us_container);
               print("Linked $riverseg into $elementname ($parentid ($us_container)) <br>\n");
            break;

            case 'tidal':
               // now link to the "Upstream Tributaries" component in the parent container
               $us_container = getCOVATribs($listobject, $parentid);
               $elementname = getElementName($listobject, $parentid);
               createObjectLink($projectid, $scenid, $elid, $us_container, 1);
               $elementname = getElementName($listobject, $us_container);
               print("Linked $riverseg into $elementname ($parentid ($us_container)) <br>\n");
            break;
         }
      } else {
         print("No upstream container for $riverseg exists in model domain $scenid. <br>\n");
      }
      // find any upstream tribs for this segment
      print("Looking for tributaries to $riverseg. <br>\n");      
      $children = getCOVACBPTribObjects($scenid, $riverseg);
      if (count($children) > 0) {
         print("Found children to $riverseg: " . print_r($children,1) . "<br>\n");
         // now link all children to this object "Upstream Tributaries" component
         $us_container = getCOVAUpstream($listobject, $elid);
         foreach ($children as $thischild) {
            $childid = $thischild['elementid'];
            createObjectLink($projectid, $scenid, $childid, $us_container, 1);
            $elementname = getElementName($listobject, $thischild);
            print("Child $elementname linked to $riverseg ($us_container) <br>\n");
         }
      } else {
         print("No upstream children for $riverseg in model domain $scenid. <br>\n");
      }
      print("Finished $riverseg <br>\n");
   }
}

function getCBPRiverInfo($riverseg) {
   global $cbp_rivers, $listobject, $cbp_listobject;
   $info = array();
   
   $reach_code = substr($riverseg,4,4);
   $reach_next = substr($riverseg,9,4);
   switch ($reach_next) {
   /*
      case '0001':
      $reach_key = substr($riverseg,0,2);
      $info['riverseg'] = $riverseg;
      print("Searching for reach_key '$reach_key' <br>\n");
      $info['rivername'] = $cbp_rivers[$reach_key]['terminal_name'];
      $info['watershed'] = $cbp_rivers[$reach_key]['terminal_name'];
      break;
   */   
      case '0000':
      $reach_key = substr($riverseg,0,2);
      $info['riverseg'] = $riverseg;
      print("Searching for reach_key '$reach_key' <br>\n");
      $info['rivername'] = "Tidal $riverseg " . $cbp_rivers[$reach_key]['terminal_name'];
      $info['watershed'] = '';
      break;
      
      default:
      $listobject->querystring = "select riverseg, watershed, rivername from  sc_cbp53 where riverseg = '$riverseg' ";
      print(" $listobject->querystring <br>\n");
      $listobject->performQuery();
      
      $info['riverseg'] = $listobject->getRecordValue(1,'riverseg');
      $info['rivername'] = $listobject->getRecordValue(1,'rivername');
      $info['watershed'] = $listobject->getRecordValue(1,'watershed');
      break;
   }
   
   return $info;
}

function getContainingNodeType($elementid, $levelcount = 0, $criteria = array(), $maxlevels = 20, $debug = 0) {
   global $listobject;
   if (!isset($criteria['objectclass'])) {
      $criteria['objectclass'] = array();
   }
   if (!isset($criteria['custom1'])) {
      $criteria['custom1'] = array();
   }
   if (!isset($criteria['custom2'])) {
      $criteria['custom2'] = array();
   }
   if ($debug) {
      print("Searching for parents of $elementid with objectclass in " . print_r($criteria['objectclass'],1) . " and custom1 in " . print_r($criteria['custom1'],1) . " and custom2 in " . print_r($criteria['custom2'],1) . "\n");
   }
   
   $parentid = getElementContainer($listobject, $elementid);
   $info = getElementInfo($listobject, $parentid);
   if ($debug) {
      print("Info for $elementid: " . print_r($info,1) . "\n");
   }
   $match = TRUE;
   if ( (count($criteria['objectclass']) > 0) and !(in_array($info['objectclass'], $criteria['objectclass'])) ) {
      $match = FALSE;
   }
   if ( (count($criteria['custom1']) > 0) and !(in_array($info['custom1'], $criteria['custom1'])) ) {
      $match = FALSE;
   }
   if ( (count($criteria['custom2']) > 0) and !(in_array($info['custom2'], $criteria['custom2'])) ) {
      $match = FALSE;
   }
   if ($match) {
      return $parentid;
   }
   
   // iterate through layers of containment until you get to the next container of the given type(s)
   $levelcount++;
   
   if ( ($levelcount >= $maxlevels) ) {
      return -1;
   } else {
      // keep looking
      $pid = getContainingNodeType($parentid, $levelcount, $criteria, $maxlevels, $debug);
      return $pid;
   }
}
   

function getCOVACBPParent($scenid, $riverseg, $debug = 0) {
   global $listobject, $cbp_rivers;
   $parentinfo = array();
   
   $reach_code = substr($riverseg,4,4);
   $reach_outlet = substr($riverseg,9,4);
   if ( ($reach_outlet == '0001') or ($reach_outlet == '0000') ) {
      // parent is the terminal container, 0001 objects are stashed in "Upstream"
      // whereas '0000' objects are stashed in the Local Tribs sub-object
      // need to obtain the model name abbreviation to determine appropriate parent
      
      $reach_key = substr($riverseg,0,2);
      $al = $cbp_rivers[$reach_key]['terminal_seg'];
      $listobject->querystring = "  select elementid from scen_model_element where scenarioid = $scenid and custom1 = 'cova_ws_container' and custom2 = '$al' ";
      if ( ($reach_outlet == '0001') ) {
         $parentinfo['linktype'] = 'upstream';
      } else {
         $parentinfo['linktype'] = 'tidal';
      }
      if ($reach_code == '0000') {
         $listobject->querystring = "  select -1 as elementid ";
      }
   } else {
      $parentinfo['linktype'] = 'upstream';
      $listobject->querystring = "  select elementid from scen_model_element where scenarioid = $scenid and substring(custom2,5,4) = '$reach_outlet' and custom1 = 'cova_ws_container' ";
   }
   if ($debug) {
      print(" $listobject->querystring <br>\n");
   }
   $listobject->performQuery();
   if ($listobject->numrows > 0) {
      $eid = $listobject->getRecordValue(1,'elementid');
   } else {
      $eid = -1;
   }
   $parentinfo['elementid'] = $eid;
   return $parentinfo;
}


function getCOVACBPTribObjects($scenid, $riverseg) {
   global $listobject;
   
   $reach_code = substr($riverseg,4,4);
   if ($reach_code <> '0000') {
      $listobject->querystring = "  select elementid from scen_model_element where scenarioid = $scenid and substring(custom2,10,4) = '$reach_code' and custom1 = 'cova_ws_container' ";
      print(" $listobject->querystring <br>\n");
      $listobject->performQuery();
   } else {
      return array();
   }
   if ($listobject->numrows > 0) {
      return $listobject->queryrecords;
   } else {
      return array();
   }
}

function setCOVACBPReachProps($projectid, $cbp_listobject, $listobject, $riverseg, $cid, $debug, $use_nhdplus = 0) {
   global $usgsdb;
   $cbp_listobject->querystring = "   select  reach_len_mi * 5284.0 as reachlen_ft,  ";
   $cbp_listobject->querystring .= " round( ( reach_drop_ft / (reach_len_mi * 5284.0))::numeric,6 ) as c_slope ";
   $cbp_listobject->querystring .= " from reaches_p53 where riverseg = '$riverseg' ";
   print(" $cbp_listobject->querystring <br>\n");
   $cbp_listobject->performQuery();
   $cbpc_slope = -1;
   if ($cbp_listobject->numrows > 0) {
      $reachlen_ft = $cbp_listobject->getRecordValue(1,'reachlen_ft');
      $c_slope = $cbp_listobject->getRecordValue(1,'c_slope');
      $cbpreachlen_ft = $cbp_listobject->getRecordValue(1,'reachlen_ft');
      $cbpc_slope = $cbp_listobject->getRecordValue(1,'c_slope');
   } else {
      $reachlen_ft = 5000.0;
      $c_slope = 0.001;
   }
   $nhdc_slope = -1;
   $usgsdb->querystring = "  select a.catcode2,  ";
   $usgsdb->querystring .= " sum(b.lengthkm*c.slope)/sum(b.lengthkm) as c_slope, ";
   $usgsdb->querystring .= " sum(b.lengthkm) * 3280.8 as reachlen_ft, sum(b.lengthkm*c.slope), count(*)  ";
   $usgsdb->querystring .= " from nhdplus_flatt_flow as c, nhdplus_flowline as b, sc_cbp5 as a ";
   $usgsdb->querystring .= " where st_contains( st_transform(a.the_geom,4296), b.the_geom) ";
   $usgsdb->querystring .= " and b.comid = c.comid ";
   $usgsdb->querystring .= " and c.cumdrainag >= (a.contrib_area_sqmi - a.local_area_sqmi) ";
   $usgsdb->querystring .= " and a.catcode2 = '$riverseg'";
   $usgsdb->querystring .= " group by a.catcode2 ";
   print(" $usgsdb->querystring <br>\n");
   $usgsdb->performQuery();
   if ($usgsdb->numrows > 0) {
      $nhdreachlen_ft = $usgsdb->getRecordValue(1,'reachlen_ft');
      $nhdc_slope = $usgsdb->getRecordValue(1,'c_slope');
   }
   if ( ($use_nhdplus > 0) & ($nhdc_slope > 0)) {
      switch($use_nhdplus) {
         case 1:
         // use nhd+ regardless
         $reachlen_ft = $nhdreachlen_ft;
         $c_slope = $nhdc_slope;
         print("Using NHD+ Slope by default\n");
         break;
         
         case 2:
         // use nhd+ only if greater than CBP
         if ($nhdc_slope > $cbpc_slope) {
            $reachlen_ft = $nhdreachlen_ft;
            $c_slope = $nhdc_slope;
            print("NHD+ Slope greater than CBP slope -- Using NHD+ Slope \n");
         }
         break;
      }
   }
   $listobject->querystring = "  select riverseg, sum( st_area2d( st_transform(the_geom,26918))*3.86102159E-7) as areasqmi, ";
   $listobject->querystring .= " max(contrib_area_sqmi) as contrib_area_sqmi from sc_cbp53 where riverseg = '$riverseg'  and tidalwater <> 'Y' group by riverseg ";
   print(" $listobject->querystring <br>\n");
   $listobject->performQuery();
   if ($listobject->numrows > 0) {
      $areasqmi = $listobject->getRecordValue(1,'areasqmi');
      $contrib_area_sqmi = $listobject->getRecordValue(1,'contrib_area_sqmi');
   } else {
      $areasqmi = 1.0;
      $contrib_area_sqmi = 1.0;
   }
   $params = array('area'=>$areasqmi, 'drainage_area' => $contrib_area_sqmi, 'cbpreachlen_ft' => $cbpreachlen_ft, 'cbpc_slope' => $cbpc_slope, 'nhdreachlen_ft' => $nhdreachlen_ft, 'nhdc_slope' => $nhdc_slope, 'slope' => $c_slope, 'length' => $reachlen_ft);
   print("updateObjectProps($projectid, $cid, " . print_r($params,1) . " ); <br>\n");
   updateObjectProps($projectid, $cid, $params);
}

function initCOVACBPLandUse($scenid, $segs, $delete_extra = 1, $replace_all = TRUE, $debug = 0) {
   global $listobject, $cbp_listobject,  $lstemplateid, $cbp_scen;
   // sets up the initial land segments in a given river segment, and loads theCBP historical land use for them
   // Set up CBP River segment land use objects
   // first, get the container of these objects, so that we have the ID for later
   // $pid = getCOVACBPRunoffContainer($listobject, $parentid); // the CBP model parent containerin the runoff object
   // then, 
   // 1.  get the land segments that are in this river segment

   foreach ($segs as $riverseg) {
      print("Handling $riverseg \n");
      // get the model element
      $segments = getCBPLandSegments($cbp_listobject, $cbp_scen, $riverseg, $debug);
      print("Found");
      print_r($segments);
      print("\n");
      $landsegs = array();
      // populate array $landsegs with "$landseg-$riverseg" combined name
      foreach ($segments['local_landsegs'] as $landseg) {
          $landsegs["$landseg-$riverseg"] = array('landseg'=>$landseg, 'riverseg'=>$riverseg);
          print("Adding $landseg-$riverseg to queue \n");
      }
      // 2. get existing landuse/landsegment objects from model
      // array $exists is keyed with "$landseg-$riverseg" combined name
      $parentid = getCOVACBPContainer($listobject, $scenid, $riverseg);
      $exists = getCOVACBPLanduseObjects($listobject, $parentid); // get all existing landuse/runoff objects
      // 3. delete all or non-matching land-segment objects 
      print("Existing LR Objects <br>\n");
      print_r($exists);
      foreach ($exists as $thisprops) {
         $thisone = $thisprops['lsrs'];
         if ( (!in_array($thisone, array_keys($landsegs)) and $delete_extra) or $replace_all ) { 
             if ($delete_extra or $replace_all) {
                print("Deleting $thisone (" . $thisprops['elementid'] . ")\n");
                deleteModelElement($thisprops['elementid']);
             }
         } else {
           $keepers[] = $thisone;
         }
      }
      print("Land Segments on this model segment " . print_r($landsegs,1) . "\n");
      print("Existing objects to be kept " . print_r($keepers,1) . "\n");

      // 4. add missing segments 
      foreach ($landsegs as $thisseg=>$thisdata) {
         print("Checking to add $thisseg \n");
         $lseg = $thisdata['landseg'];
         if (!in_array($thisseg, $keepers)) {
            addCBPLandRiverSegment($projectid, $scenid, $lseg, $riverseg, $parentid, 1);

         }
      }
   }
}


function setCOVACBPLandUse($listobject, $scenarioid, $parentid, $lutemplateid, $lu_array, $delete_extra = 0) {
   global $projectid;
   // expects $lu_array to contain records of the following format:
   // array( 0=>array('landseg'=>'A24xxx', 'variable_name'=>'landuse_current',
   //             'landuses'=>array(
   //         [0] => Array
   //        (
   //            [luname] => wat
   //            [1985] => 166.89
   //            [1987] => 166.89
   //            [1992] => 166.89
   //            [1997] => 165.89
   //            [2002] => 164.89
   //            [2005] => 164.89
   //        )
   $ro_container = getCOVARunoff($listobject, $parentid);
   //print("Runoff intput container = $ro_container \n");
   $vahydro_container = getVAHydroComponent($listobject, $ro_container);
   $landuse_objects = getCOVACBPLanduseObjects($listobject, $parentid);
   //print("LU Objects - " . print_r($landuse_objects,1) . " \n");
   $existing_landsegs = array_keys($landuse_objects);
   print("Land Segment Names - " . print_r($existing_landsegs,1) . " \n");
   // iterate through the ones that we have been given, 
   $lus_added = array();
   foreach ($lu_array as $thislu) {
      $landseg = $thislu['landseg'];
      $var_name = $thislu['variable_name'];
      $landuse = $thislu['landuses'];
      if (in_array($landseg, $existing_landsegs)) {
         // if there is a matching name, then 
         // grab the elementid and proceeed to the next step
         print("Found object for $landseg \n");
         //print("Found object for $landseg in " . print_r($existing_landsegs,1) . " \n");
         $ls_elid = $landuse_objects[$landseg]['elementid'];
         print("Found object for $landseg with ID = $ls_elid\n");
      } else {
         // if NOT, then insert a new land use element, based on the lutemplateid element, get the new elementid that results
         print("Did not find object for $landseg ... creating, and adding as a child to $vahydro_container \n");
         $clone = cloneModelElement($scenarioid, $lutemplateid, $vahydro_container);
         $ls_elid = $clone['elementid'];
         print("Object for $landseg created with ID = $ls_elid \n");
      }
      
      // set up basic info
      $luprops = array('name'=>$landseg, 'description'=>"CBP Model Runoff for segment $landseg", 'id2'=>$landseg, 'debug'=>0);
      updateObjectProps($projectid, $ls_elid, $luprops);

      print("Trying to apply land use matrix: " . print_r($landuse[0],1) . "<br>\n to element $ls_elid \n");
      $loadres = unSerializeSingleModelObject($ls_elid);
      $thisobject = $loadres['object'];
      if (is_object($thisobject)) {
         print("$ls_elid object retrieved<br>\n");
         if (is_object($thisobject->processors["$var_name"])) {
            print("$ls_elid object landuse found $var_name<br>\n");
            if (method_exists($thisobject->processors[$var_name], 'assocArrayToMatrix')) {
               print("$ls_elid object assocArrayToMatrix() exists<br>\n");
               $thisobject->processors[$var_name]->assocArrayToMatrix($landuse);
               saveObjectSubComponents($listobject, $thisobject, $ls_elid, 1);
            }
         }
         // stash a record of this
         $lus_added[] = $landseg;
      }
   }
   // update the element with the lu_array contents for that landseg, 
   // now, eliminate old landsegs that do not match if we have asked to overwrite
   if ($delete_extra) {
      foreach ($existing_landsegs as $thisone) {
         if (!in_array($thisone, $lus_added)) {
            $elid = $landuse_objects[$thisone]['elementid'];
            print("Clearing Land Segment $thisone ($elid) from parent object \n");
            deleteModelElement($elid);
         }
      }
   }
}


function setLUMatrix ($elementid, $lumatrix_name, $lumatrix) {
   global $listobject;
   print("Trying to apply land use matrix to $elementid: \n");
   $loadres = unSerializeSingleModelObject($elementid);
   $thisobject = $loadres['object'];
   if (is_object($thisobject)) {
      print("$elementid object retrieved<br>\n");
      if (is_object($thisobject->processors["$lumatrix_name"])) {
         print("$ls_elid object landuse found $lumatrix_name<br>\n");
         if (method_exists($thisobject->processors[$lumatrix_name], 'assocArrayToMatrix')) {
            print("$ls_elid object assocArrayToMatrix() exists<br>\n");
            $thisobject->processors[$lumatrix_name]->assocArrayToMatrix($lumatrix);
         }
      } else {
         print("Adding $lumatrix_name to $elementid object<br>\n");
         $ludef = new dataMatrix;
         $ludef->listobject = $listobject;
         $ludef->name = $lumatrix_name;
         $ludef->wake();
         $ludef->numcols = 3;  
         $ludef->valuetype = 2; // 2 column lookup (col & row)
         $ludef->keycol1 = ''; // key for 1st lookup variable
         $ludef->lutype1 = 0; // lookp type - exact match for land use name
         $ludef->keycol2 = 'year'; // key for 2nd lookup variable
         $ludef->lutype2 = 1; // lookup type - interpolated for year value
         // add a row for the header line
         $ludef->assocArrayToMatrix($lumatrix);
         $thisobject->addOperator($lumatrix_name, $ludef, 0);
      }
      saveObjectSubComponents($listobject, $thisobject, $elementid, 1);
   }
}

function getCBPWatershedSegmentOverview($scenarioid, $segment, $getchildren = 1) {
   global $listobject;
   $elementid = getCOVACBPContainer($listobject, $scenarioid, $segment);
   $info = watershedModelOverview ($elementid, $getchildren);
   return $info;
}

function watershedModelOverview ($elementid, $getchildren = 1) {
   global $listobject;
   
   // returns the following information about a watershed model
   // all withdrawals defined on the model -> current, historic, and future
   // land use -> current, historic and future
   // point sources -> current, historic and future
   // summary information -> eco regions overlapped (%'s) - requires a merged shape
   
   // get the list of containers first (if getchildren = 1, otherwise, just the parent)
}


function getNestedContainersCriteria ($listobject, $elementid, $types = array(), $custom1 = array(), $custom2 = array(), $ignore = array(), $debug = 0 ) {
   $containers = getTree($listobject, $elementid, $ignore);
   $keepers = array();
   $keepids = array();
   foreach ($containers as $thisobj) {
      $keep = 1;
      $elid = $thisobj['elementid'];
      if (!in_array($elid, $ignore)) {
         $elinfo = getElementInfo($listobject, $elid);
         $thisobj['objectclass'] = $elinfo['objectclass'];
         $thisobj['custom1'] = $elinfo['custom1'];
         $thisobj['custom2'] = $elinfo['custom2'];
         //print(print_r($elinfo,1) . "\n");
         if ( (count($types) > 0) ) {
            //print(" Checking type \n");
            if (!in_array($elinfo['objectclass'], $types)) {
               $keep = 0;
            }
         }
         if ( (count($custom1) > 0) ) {
            if (!in_array($elinfo['custom1'], $custom1)) {
               $keep = 0;
               //print($elinfo['custom1'] . " not in " . print_r($custom1,1) . "\n");
            }
         }
         if ( (count($custom2) > 0) ) {
            if (!in_array($elinfo['custom2'], $custom2)) {
               $keep = 0;
            }
         }
         if (in_array($elid, $keepids)) {
            // no duplicates
            $keep = 0;
         }
         
         if ($keep) {
            $keepers[] = $thisobj;
            $keepids[] = $elid;
         }
      }
   }
   return $keepers;
   
}

function getTree($listobject, $elementid, $ignore = array()) {
   // gets all the branches and leaves in a tree
   $containers = getNestedContainers($listobject, $elementid, 0, $ignore);
   $keepers = $containers;
   
   foreach ($containers as $thisobj) {
      $nodeid = $thisobj['elementid'];
      $children = getChildComponentType($listobject, $nodeid, '');
      foreach ($children as $thischild) {
         $keepers[] = $thischild;
      }
   }
   
   return $keepers;
}


function getVAHydroLiteContainer($listobject, $scenarioid, $riverseg) {
   $elid = getComponentCustom($listobject, $scenarioid, 'vahydro_lite_container', $riverseg);
   return $elid;
}


function getVAHydroLiteLandseg($listobject, $parentid, $landseg, $debug) {
   $els = getChildComponentType($listobject, $parentid, 'CBPLandDataConnection', -1, $debug);
   $elid = -1;
   foreach ($els as $thisel) {
      if ($thisel['custom2'] == $landseg) {
         $elid = $thisel['elementid'];
      }
   }
   return $elid;
}


function getVAHydroLiteWithdrawal($listobject, $parentid, $debug) {
   $els = getChildComponentType($listobject, $parentid, 'DataConnectionObject', -1, $debug);
   $elid = $els[0]['elementid'];
   return $elid;
}


function listObjectSubComps($srcid) {
   global $listobject;
   // get the list of all sub-comps from the source
   $obres = unserializeSingleModelObject($srcid);
   $srcob = $obres['object'];
   $name = $srcob->name;
   $subcomps = array_keys($srcob->processors);
   return $subcomps;
}

/* ICPRB model structure functions
*/


function getICPRBLandseg($listobject, $parentid, $landseg, $debug) {
   $els = getChildComponentType($listobject, $parentid, 'CBPLandDataConnection', -1, $debug);
   $elid = -1;
   foreach ($els as $thisel) {
      if ($debug) {
         error_log("Checking " . print_r($thisel,1) . " for $langseg \n");
      }
      if ($thisel['custom2'] == $landseg) {
         $elid = $thisel['elementid'];
      }
   }
   return $elid;
}


function getICPRBContainer($listobject, $scenarioid, $riverseg) {
   $elid = getElementID($listobject, $scenarioid, $riverseg);
   return $elid;
}



?>
