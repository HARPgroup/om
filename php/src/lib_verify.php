<?php


function checkUnitFlow($listobject, $unique_id, $sname, $minflow, $maxflow, $debug=0) {
   $listobject->querystring = "  select count(*) as numbad from iha_karst_analysis ";
   $listobject->querystring .= " where unique_id = '$unique_id' ";
   $listobject->querystring .= "    and ( (runfile_cfssqmi > $maxflow) ";
   $listobject->querystring .= "       OR (runfile_cfssqmi < $minflow)";
   $listobject->querystring .= "       OR (verified_runmode = 0)";
   $listobject->querystring .= "       OR (runfile_cfssqmi is null) ";
   $listobject->querystring .= "    )";
   $listobject->querystring .= "    and scenario = '$sname' ";
   if ($debug) {
      print("$listobject->querystring ; \n");
   }
   $listobject->performQuery();
   $numbad = $listobject->getRecordValue(1,'numbad');
   return $numbad;
}

function checkRunMode($listobject, $unique_id, $sname, $debug=0) {
   $listobject->querystring = "  select verified_runmode from iha_karst_analysis ";
   $listobject->querystring .= " where unique_id = '$unique_id' ";
   $listobject->querystring .= "    and scenario = '$sname' ";
   if ($debug) {
      print("$listobject->querystring ; \n");
   }
   $listobject->performQuery();
   if (count($listobject->queryrecords) > 0) {
      $verified_runmode = $listobject->getRecordValue(1,'verified_runmode');
   } else {
      // did not run, so we return 0
      $verified_runmode = 0;
   }
   return $verified_runmode;
}

function getLandUseArea($cbp_listobject, $unique_id, $lutab = 'baseline', $debug = 0) {
   $cbp_listobject->querystring = "  select * from ( ";
   $cbp_listobject->querystring = "     select a.shed_merge, a.local_area, a.total_area, ";
   $cbp_listobject->querystring .= "       round((sum(b.afo + b.alf + bar + ext + b.for + hom + hvf + hwm + ";
   $cbp_listobject->querystring .= "          hyo + hyw + imh + iml + lwm + nal + nhi + nho + nhy + nlo + ";
   $cbp_listobject->querystring .= "          npa + pas + puh + pul + trp + urs + wat ";
   $cbp_listobject->querystring .= "       )/640.0)::numeric,3) as ls_area ";
   $cbp_listobject->querystring .= "    from icprb_watersheds as a, tmp_icprb_landuse_baseline as b ";
   $cbp_listobject->querystring .= "    where a.unique_id = b.uniqid ";
   $cbp_listobject->querystring .= "       and a.cbp_segmentid = b.riverseg ";
   $cbp_listobject->querystring .= "       and a.unique_id = '$unique_id' ";
   $cbp_listobject->querystring .= "    group by a.shed_merge, a.local_area, a.total_area ";
   $cbp_listobject->querystring .= " ) as foo ";
   if ($debug) {
      print("$cbp_listobject->querystring ;\n");
   }
   $cbp_listobject->performQuery();
   return $cbp_listobject->queryrecords[0];
}

function getLandUseRunoff($cbp_listobject, $lseg, $luname, $debug = 0) {
// summarizes the flows for each component
   $cbp_listobject->querystring = "  select lseg, landuse, avg(ro_cfsqmi) as avg_cfs_sqmi ";
   $cbp_listobject->querystring .= "  from ( ";
   $cbp_listobject->querystring .= "     select lseg, thisyear, landuse, sum(ro_cfsqmi) as ro_cfsqmi, sum(numrecs) ";
   $cbp_listobject->querystring .= "     from ( ";
   $cbp_listobject->querystring .= "        select lseg, landuse, param_name, extract(year from thisday) as thisyear,  ";
   $cbp_listobject->querystring .= "           sum(thisvalue) as runoff,  ";
   $cbp_listobject->querystring .= "              (avg(thisvalue / 12.0 * 640.0 * 43560.0/3600.0)/24.0) as ro_cfsqmi,  ";
   $cbp_listobject->querystring .= "               sum(numrecs) as numrecs  ";
   $cbp_listobject->querystring .= "        from ( ";
   $cbp_listobject->querystring .= "          select c.thisdate::date as thisday, c.param_name, b.location_id, b.id2 as lseg,  ";
   $cbp_listobject->querystring .= "             b.id3 as landuse, sum(c.thisvalue) as thisvalue, count(c.*) as numrecs  ";
   $cbp_listobject->querystring .= "          from cbp_model_location as b, cbp_scenario_output as c ";
   $cbp_listobject->querystring .= "             where  ";
   $cbp_listobject->querystring .= "              c.location_id = b.location_id ";
   $cbp_listobject->querystring .= "             and c.param_name in ( 'SURO' , 'IFWO', 'AGWO')  ";
   $cbp_listobject->querystring .= "             and b.id2 in ('$lseg') ";
   $cbp_listobject->querystring .= "             and b.id3 in ( '$luname' )  ";
   $cbp_listobject->querystring .= "             and b.scenarioid = 3  ";
   $cbp_listobject->querystring .= "          group by thisday, b.id2, b.location_id, b.id3, c.param_name  ";
   $cbp_listobject->querystring .= "          order by thisday, b.id2, b.id3 ";
   $cbp_listobject->querystring .= "        ) as foo ";
   $cbp_listobject->querystring .= "        group by lseg, thisyear, landuse, param_name ";
   $cbp_listobject->querystring .= "        order by lseg, landuse, param_name, thisyear ";
   $cbp_listobject->querystring .= "     ) as bar ";
   $cbp_listobject->querystring .= "     group by lseg, thisyear, landuse ";
   $cbp_listobject->querystring .= "     order by lseg, thisyear, landuse ";
   $cbp_listobject->querystring .= "  ) as foo ";
   $cbp_listobject->querystring .= "  group by lseg, landuse ";
   $cbp_listobject->querystring .= "  order by lseg, landuse ";
   if ($debug) {
      print("$cbp_listobject->querystring ;\n");
   }
   $cbp_listobject->performQuery();
   return $cbp_listobject->queryrecords[0];
}


function getZeroFlowElements($cbp_listobject, $sname, $debug=0) {
   if ($debug) {
      print("Searching for zero flow runs\n");
   }
   // get list of runs with zero flow in iha_karst_analysis table
   $cbp_listobject->querystring = "  select a.shed_merge from icprb_watersheds as a, iha_karst_analysis as b ";
   $cbp_listobject->querystring .= " where a.unique_id = b.unique_id ";
   $cbp_listobject->querystring .= "    and b.scenario = '$sname' ";
   $cbp_listobject->querystring .= "    and b.runfile_cfs = 0.0 ";
   if ($debug) {
      print("$cbp_listobject->querystring ;\n");
   }
   $cbp_listobject->performQuery();

   $elemlist = '';
   $eldel = '';
   foreach ($cbp_listobject->queryrecords as $thisrec) {
      $elemlist .= $eldel . "'" . $thisrec['shed_merge'] . "'";
      $eldel = ',';
   }
   return $elemlist;
}


function getRunAnalysisData($listobject, $unique_id, $sname, $debug=0) {
   $retvals = array();

   $listobject->querystring = "  select * from iha_karst_analysis ";
   $listobject->querystring .= " where unique_id = '$unique_id' ";
   $listobject->querystring .= "    and scenario = '$sname' ";
   $retvals['query'] = $listobject->querystring;
   $listobject->performQuery();
   $retvals['data'] = $listobject->queryrecords[0];
   return $retvals;
}


function screenTargetOrder($listobject, $elements, $target_order, $debug=0) {
   $target_elements = array();
   foreach ($elements as $thisel) {
      if (is_array($thisel)) {
         $elid = $thisel['elementid'];
         $ename = $thisel['elemname'];
      } else {
         $elid = $thisel;
         $ename = '';
      }
      $order = getElementOrder($listobject, $elid);
      if ($debug) {
         print("Element ($ename): $elid, Order: $order \n");
      }
      if ( ($target_order == 'all') or ($target_order == $order) ) {
         // now get run info
         $target_elements[] = $thisel;
      }
   }
   
   return $target_elements;
}

function screenRunMode($listobject, $cbp_listobject, $elements, $target_runmode, $sname, $debug=0) {
   $target_elements = array();
   foreach ($elements as $thisel) {
      if (is_array($thisel)) {
         $elid = $thisel['elementid'];
      } else {
         $elid = $thisel;
      }
      $uid = getUID($listobject, $elid);
      $runmode_verified = checkRunMode($cbp_listobject, $uid, $sname, $debug);
      if ( ($target_runmode === 'all') or ($target_runmode == $runmode_verified) ) {
         // now get run info
         $target_elements[] = $thisel;
         if ($debug) {
            print(" Element $elid - target_mode = $target_runmode, verified_runmode = $runmode_verified <br>\n");
         }
      }
   }
   return $target_elements;
}

function getUID($listobject, $elementid) {
   $listobject->querystring = "  select unique_id from icprb_watersheds as a, scen_model_element as b ";
   $listobject->querystring .= " where b.elementid = $elementid ";
   $listobject->querystring .= "    and a.shed_merge = b.elemname ";
   //print("$listobject->querystring ; \n");
   $listobject->performQuery();
   if (count($listobject->queryrecords) > 0) {
      $uid = $listobject->getRecordValue(1,'unique_id');
   } else {
      $uid = '';
   }
   return $uid;

}

function getICPRBElements($listobject, $scenarioid, $cbp_segs = 1, $debug = 0) {
   $listobject->querystring = " ( select b.elementid, b.elemname, a.unique_id ";
   $listobject->querystring .= " from icprb_watersheds as a, scen_model_element as b ";
   $listobject->querystring .= " where a.shed_merge = b.elemname ";
   $listobject->querystring .= "    and b.scenarioid = $scenarioid ";
   $listobject->querystring .= "    and b.objectclass = 'modelContainer' ";
   $listobject->querystring .= " ) ";
   if ($cbp_segs) {
      $listobject->querystring .= " UNION ( select b.elementid, b.elemname, '' as unique_id ";
      $listobject->querystring .= " from icprb_watersheds as a, scen_model_element as b ";
      $listobject->querystring .= " where a.cbp_segmentid = b.elemname ";
      $listobject->querystring .= "    and b.scenarioid = $scenarioid ";
      $listobject->querystring .= "    and b.objectclass = 'modelContainer' ";
      $listobject->querystring .= "    and a.shed_merge is null ";
      $listobject->querystring .= " group by b.elementid, b.elemname, unique_id ";
      $listobject->querystring .= " ) ";
      /*
      $listobject->querystring .= " UNION ( select d.elementid, d.elemname, '' as unique_id ";
      $listobject->querystring .= " from icprb_watersheds as a, scen_model_element as b, map_model_linkages as c, scen_model_element as d ";
      $listobject->querystring .= " where a.shed_merge = b.elemname ";
      $listobject->querystring .= "    and b.scenarioid = $scenarioid ";
      $listobject->querystring .= "    and b.objectclass = 'modelContainer' ";
      $listobject->querystring .= "    and b.elementid = c.dest_id ";
      $listobject->querystring .= "    and d.elementid = c.src_id ";
      $listobject->querystring .= "    and c.linktype = 1 ";
      $listobject->querystring .= "    and d.objectclass = 'modelContainer' ";
      $listobject->querystring .= "    and d.elemname not in (select shed_merge from icprb_watersheds where shed_merge is not null) ";
      $listobject->querystring .= " ) ";
      */
   }
   if ($debug) {
      print("$listobject->querystring ; \n");
   }
   $listobject->performQuery();
   return $listobject->queryrecords;

}

function deleteRunRecord($listobject, $elementid, $runid) {
   $listobject->querystring = "  delete from scen_model_run_elements ";
   $listobject->querystring .= " where runid = $runid and elementid = $elementid " ;
   print("$listobject->querystring ; \n");
   $listobject->performQuery();
   $listobject->querystring = "  delete from system_status ";
   $listobject->querystring .= " where runid = $runid and element_key = $elementid " ;
   print("$listobject->querystring ; \n");
   $listobject->performQuery();
}

function checkTreeRunMode($listobject, $cbp_listobject, $elementid,$sname, $minflow, $maxflow) {
   $container_tree = getNestedContainers($listobject, $elementid);
   $isbad = 0;
   foreach ($container_tree as $thisone) {
      $thisid = $thisone['elementid'];
      $uid = getUID($listobject,$thisid);
      $isbad = checkUnitFlow($cbp_listobject, $uid, $sname, $minflow, $maxflow);
      if ($isbad > 0) {  
         break;
      }
   }
   return $isbad;
}


function checkRunData($listobject, $runid, $startdate, $enddate, $outlets, $strict=1) {
   $msg_ok = '';
   $msg_bad = '';
   $msg_null = '';
   $msg_warn = '';
   $bad_elements = array();
   $null_elements = array();
   $ok_elements = array();
   foreach ($outlets as $thisrec) {
      $elid = $thisrec['elementid'];
      // get run detail info
      $elinfo = getElementInfo($listobject, $elid);
      switch ($elinfo['scenarioid']) {
         case 28:
         $detail = getICPRBRunDetail($listobject, $elid, $runid, '');
         $msg_ok .= "Using getICPRBRunDetail <br>";
         $msg_bad .= "Using getICPRBRunDetail <br>";
         break;
         
         default:
         $detail = getCOVARunDetail($listobject, $elid, $runid, '');
         $msg_ok .= "Using getCOVARunDetail <br>";
         $msg_bad .= "Using getCOVARunDetail <br>";
         break;
      }
      
      $bad = 0;
      if ($detail['exists']) {
         $starttime = $detail['starttime'];
         $endtime = $detail['endtime'];
         // check date ranges
         if (! (check_in_range($starttime, $endtime, $startdate)) and (check_in_range($starttime, $endtime, $enddate)) ) {
            $msg_bad .= "Element $elid Failed Date Span Check $starttime/$endtime is not in $startdate/$enddate <br>\n";
            $bad = 1;
         }
         // hi/lo based on strictness
         if ($strict) {
            $hiland = 2.3;
            $loland = 0.5;
         } else {
            $hiland = 10.3;
            $loland = 0.01;
         }         
         // land segment flows
         foreach ($detail['landsegs'] as $lseg) {
            $Q = $lseg['Qout'];
            $A = $lseg['area_sqmi'];
            $ls = $lseg['landseg'];
            // high land unit runoff exceptions
            $exceptions = array('A54093','A54077','A51073','A54023','A11001');
            print("Checking for $ls in " . print_r($exceptions,1) . "<br>\n");
            if (in_array($ls, $exceptions)) {
               $hiland = $hiland * 1.6;
            }
            // low land unit runoff exceptions
            $exceptions = array('A51147');
            print("Checking for $ls in " . print_r($exceptions,1) . "<br>\n");
            if (in_array($ls, $exceptions)) {
               $loland = $loland * 0.5;
            }
            if ( ($A > 0) and ($Q > 0) ) {
               $unit = $Q / $A;
               if ( ( $unit <= $loland ) or ($unit >= $hiland) ) {
                  $msg_bad .= "Element $elid, land seg $ls Failed Unit Flow Test $unit = ($Q / $A)<br>\n";
                  $bad = 1;
               }
            } else {
               $msg_bad .= " Checking Q and A <br>\n";
               if ( ! (($A > 0.0) and ($Q > 0)) ) {
                  $msg_warn .= "Warning: Element $elid, land seg $ls had zero for Q = $Q and A = $A <br>\n";
               } else {
                  $msg_bad .= "Element $elid, land seg $ls Failed, bad values for Q = $Q and/or A = $A <br>\n";
                  $bad = 1;
               }
            }
         }
         
         // local flows per unit area
         $Qlocal = $detail['Rin'];
         $Alocal = $detail['area'];
         if (isset($detail['trib_area'])) {
            $trib_area = floatval($detail['trib_area']);
         } else {
            $trib_area = 0;
         }
         if (isset($detail['Qtrib'])) {
            $Qtrib = floatval($detail['Qtrib']);
         } else {
            $Qtrib = 0;
         }
         $wd = $detail['demand_cfs'];
         $ps = $detail['discharge_mgd'];
         $unit = ( ($Qlocal + $Qtrib) / $Alocal);
         if ( ( $unit <= $loland ) or ($unit >= $hiland) ) {
            $msg_bad .= "Element $elid Failed Local Unit Flow Test $unit = ($Qlocal + $Qtrib + WD[$wd] - PS[$ps]) / $Alocal<br>\n";
            $bad = 1;
         }
         // total flows per unit area
         $Q = $detail['Qout'];
         $A = $detail['drainage_area'];
         $unit = ($Q + $wd - $ps) / $A;
         if ( ( $unit <= $loland ) or ($unit >= $hiland) ) {
            $msg_bad .= "Element $elid Failed Cumulative Unit Flow Test $unit = ($Q + WD[$wd] - PS[$ps]) / $A)<br>\n";
            $bad = 1;
         }
         if ( ( $ps >= (0.1 * $Q) ) or ($wd >= (0.1 * $Q) ) ) {
            $msg_warn .= "Warning: Either withdrawal or discharge >= 10% of Flow (Q - $Q, WD - $wd, PS - $ps <br>\n";
         }

         if (!$bad) {
            $msg_ok .= "Element $elid passed all tests <br>\n" . $msg_warn;
            $msg_ok .= formatPrintContainer($detail);
            $ok_elements[] = $elid;
         } else {
            $msg_bad .= formatPrintContainer($detail);
            $bad_elements[] = $elid;
         }
      } else {
         $msg_null .= "No run file exists for Element $elid, run $runid<br>\n";
         $null_elements[] = $elid;
      }
   }
   $results = array();
   $results['msg_ok'] = $msg_ok;
   $results['msg_bad'] = $msg_bad;
   $results['msg_null'] = $msg_null;
   $results['bad_elements'] = $bad_elements;
   $results['ok_elements'] = $ok_elements;
   $results['null_elements'] = $null_elements;
   return $results;
}

function verifyPatches($scid, $listobject, $cbp_listobject, $records) {      
   $results = array();
   $refresh = array();
   $old_names = extract_arrayvalue($records, 'shed_merge');
   $new_names = extract_arrayvalue($records, 'new_name');

   foreach ($records as $thisrec) {
      $oldname = trim($thisrec['shed_merge']);
      $newname = trim($thisrec['new_name']);
      $to_node = trim($thisrec['to_node']);
      $mainstem = trim($thisrec['mainstem_segment']);

      // set up the basic return record structure, with all as false, until proven true
      $thisres = array(
         'orphaned'=>0, 
         'to_node_exists'=>0,
         'to_node'=>$to_node,
         'to_node_id'=>-1,
         'upstream_children'=>0, 
         'upstream_orphaned'=>0, 
         'elementid'=>-1,
         'unique_id'=>-1,
         'oldname'=>$oldname,
         'mainstem'=>$mainstem,
         'newname'=>$newname,
         'exists_scen_model_element'=>0,
         'exists_icprb_watersheds_model'=>0,
         'exists_icprb_watersheds_cbp'=>0
      );

      // query the scen_model_element table
      $listobject->querystring = "  select elementid from scen_model_element ";
      $listobject->querystring .= " where scenarioid = $scid ";
      $listobject->querystring .= "    and elemname = '$oldname' ";
      $listobject->performQuery();
      if (count($listobject->queryrecords) > 0) {
         $elid = $listobject->getRecordValue(1,'elementid');
         $thisres['elementid'] = $elid;
         $thisres['exists_scen_model_element'] = 1;
      }

      // query the scen_model_element table
      $listobject->querystring = "  select unique_id, count(*) as numrecs from icprb_watersheds ";
      $listobject->querystring .= " where shed_merge = '$oldname' group by unique_id";
      //$thisres['q_im'] = $listobject->querystring;
      $listobject->performQuery();
      if (count($listobject->queryrecords) > 0) {
         if ( $listobject->getRecordValue(1,'numrecs') > 0 ) {
            $thisres['exists_icprb_watersheds_model'] = 1;
            $thisres['unique_id'] = $listobject->getRecordValue(1,'unique_id');

         }
      }

      if ($to_node <> '') {
         // query the scen_model_element table for the downstream destination of this watershed
         $listobject->querystring = "  select elementid from scen_model_element ";
         $listobject->querystring .= " where scenarioid = $scid ";
         $listobject->querystring .= "    and elemname = '$to_node' ";
         //$thisres['q_orph'] = $listobject->querystring;
         $listobject->performQuery();
         if ( (count($listobject->queryrecords) > 0) or (in_array($to_node, $new_names)) ) {
            $thisres['to_node_exists'] = 1;
            $thisres['to_node_id'] = $listobject->getRecordValue(1,'elementid');;
         } else {
            $thisres['orphaned'] = 1;
         }
      }

      if (!($oldname == $newname) and ($newname <> '')) {
         // query the scen_model_element table on the CBP database
         $listobject->querystring = "  select count(*) as numrecs from icprb_watersheds ";
         $listobject->querystring .= " where t_node = '$oldname' ";
         //$thisres['q_orph'] = $listobject->querystring;
         $listobject->performQuery();
         if (count($listobject->queryrecords) > 0) {
            if ( ($listobject->getRecordValue(1,'numrecs') > 0) and !in_array($oldname, $new_names) ) {
               $thisres['upstream_children'] = 1;
            }
         }
      }

      // query the scen_model_element table on the CBP database
      $cbp_listobject->querystring = "  select count(*) as numrecs from icprb_watersheds ";
      $cbp_listobject->querystring .= " where shed_merge = '$oldname' ";
      //$cbp_listobject->performQuery();
      if (count($cbp_listobject->queryrecords) > 0) {
         if ( $cbp_listobject->getRecordValue(1,'numrecs') > 0 ) {
            $thisres['exists_icprb_watersheds_cbp'] = 1;
         }
      }


      if ( (!$thisres['exists_scen_model_element']) 
            or (!$thisres['exists_icprb_watersheds_model']) 
         //   or !$thisres['exists_icprb_watersheds_cbp'] 
      ) {
         if (strlen($oldname) > 13) {
            $problems[] = $thisres;
         }
      }
      if ( $thisres['upstream_children'] ) {
         $thisres['orphaned_children'] = array();
         $children = getNestedContainers($listobject, $elid);
         foreach ($children as $thischild) {
            // check for this child in the old_names, if it IS in old_names, then we assume that 
            // we do not need to do the orphan check here, since it may be renamed to a non-orphan name,
           // AND, even if it is NOT renamed, we will still be checking its orphan status, since it is in the
           // dataset of elements to be changed
            if (!in_array($thischild['elemname'], $old_names)) {
               // this child is truly orphaned
               $thisres['orphaned_children'][] = $thischild;
            } else {
               // stash it in the list of children to have their links refreshed
               $refresh[] = $thischild;
            }
         }
         if (count($thisres['orphaned_children']) > 0) {
            $orphans[] = $thisres;
         } else {
            $thisres['upstream_orphaned'] = 0;
            $not_orphans[] = $thisres;
         }
      } else {
         $not_orphans[] = $thisres;
      }
      if ($thisres['orphaned']) {
         // this record does not have the described to_node
         $orphans[] = $thisres;
      }
      // now, add this to the results, keyed by the new name
      $results['results'][$newname] = $thisres;
   }
   $results['name_problems'] = $problems;
   $results['orphans'] = $orphans;
   $results['old_names'] = $old_names;
   $results['new_names'] = $new_names;
   $results['refresh'] = $refresh;
   
   return $results;      
}

define('READ_LEN', 4096);

//   pass two file names
//   returns TRUE if files are the same, FALSE otherwise
function files_identical($fn1, $fn2, $max_lines = -1, $debug=0, $remote_files=0) {
   if (!$remote_files) {
      if(filetype($fn1) !== filetype($fn2)) {
         if ($debug) { print("File types disagree\n");}
         return FALSE;
      }
      if(filesize($fn1) !== filesize($fn2)) {
         if ($debug) { print("File sizes disagree\n");}
         return FALSE;
      }
   }

    if(!$fp1 = fopen($fn1, 'r')) {
        if ($debug) { print("Can't open $fn1\n"); }
        return FALSE;
     }

    if(!$fp2 = fopen($fn2, 'r')) {
       if ($debug) { print("Can't open $fn2\n"); }
        fclose($fp2);
        return FALSE;
    }

    $same = TRUE;
    $ct = 0;
    while ( !feof($fp1) and !feof($fp2) and !( ($max_lines <> -1) and ($ct > $max_lines)) ) {
       $l1 = fgets($fp1, READ_LEN);
       $l2 = fgets($fp2, READ_LEN);
        if( $l1 !== $l2 ) {
            $same = FALSE;
            if ($debug) { 
               print("File disagreement at line $ct \n"); 
               print("File 1, $fn1:<br>\n $l1 <br>\n File 2, $fn2<br>\n $l2");
            }
            break;
        }
        $ct++;
     }

    if ($max_lines == -1) {
       if(feof($fp1) !== feof($fp2))
        $same = FALSE;
     }

    fclose($fp1);
    fclose($fp2);

    return $same;
}

function groupByOrder($listobject, $records) {
   // records is a list of elementid
   foreach ($records as $thisrec) {
      $elid = $thisrec['elementid'];
      $order = getElementOrder($listobject, $elid);
      if (!isset($orders[$order])) {
         $orders[$order]['count'] = 0;
         $orders[$order]['elements'] = array();
      }
      $orders[$order]['count'] += 1;
      $orders[$order]['elements'][] = $elid;
   }
   return $orders;
}

function check_in_range($start_date, $end_date, $date_from_user)
{
  // Convert to timestamp
  $start_ts = strtotime($start_date);
  $end_ts = strtotime($end_date);
  $user_ts = strtotime($date_from_user);

  // Check that user date is between start & end
  return (($user_ts >= $start_ts) && ($user_ts <= $end_ts));
}
      

function getICPRBRunDetail($listobject, $elid, $runid, $host = '') {
   $quick_num = 1000;
   // get information for the parent container
   $output = getStatusSingle($listobject, $elid, $runid, $host);   
   $run_rec = getRunFile($listobject, $elid, $runid);
   if (is_array($run_rec)) {
      if ($output['run_status'] == '') {
         // we have no system_status entry, but we DO have a file, so we consider it to be a 
         // valid completed model run
         $output['run_status'] = 0;
      }
      $output['exists'] = 1;
      $run_file = $run_rec['output_file'];
      $output['runfile'] = $run_file;
      $output['rundate'] = $run_rec['run_date'];
      $output['starttime'] = $run_rec['starttime'];
      $output['endtime'] = $run_rec['endtime'];
      $info = verifyRunVars($run_file,array('demand_mgd','discharge_mgd','Qout'),$quick_num);
      $output['Qout'] = $info['Qout']['mean'];
      $imp_off = $info['impoundment_inactive']['max'];
      // get this from the river since the mapping is wrong inthe broadcast object
      //$output['demand_mgd'] = $info['demand_mgd']['mean'];
      $output['discharge_mgd'] = $info['discharge_mgd']['mean'];
      // ***********************************************
      // ***     verify the land segment flows objects
      $landrecs = getChildComponentType($listobject, $elid, 'CBPLandDataConnection', -1);
      foreach ($landrecs as $thisland) {
         $cid = $thisland['elementid'];
         $prop_array = array('area_sqmi', 'Qout');
         $ls_rec = getRunFile($listobject, $cid, $runid,1);
         $ls_file = $ls_rec['output_file'];
         $info = verifyRunVars($ls_file,$prop_array,$quick_num);
         $output['landsegs'][] = array(
            'landseg'=>substr($thisland['elemname'],-6),
            'area_sqmi'=>$info['area_sqmi']['mean'],
            'Qout'=>$info['Qout']['mean']
         );
      }
      // get information for the Mainstem Container pertaining to drainage area
      $child_rec = getChildComponentType($listobject, $elid, 'USGSChannelGeomObject', 1);
      $thischild = $child_rec[0];
      $cid = $thischild['elementid'];
      $prop_array = array('drainage_area', 'area', 'length');
      $props = getElementPropertyValue($listobject, $cid, $prop_array);
      // get Runoff data from the Mainstem Container peraining to drainage area
      $runoff_rec = getRunFile($listobject, $cid, $runid);
      $runoff_file = $runoff_rec['output_file'];
      $info = verifyRunVars($runoff_file,array('demand_broadcast_mgd','Rin'),$quick_num);
      $output['demand_cfs'] = $info['demand_broadcast_mgd']['mean'] * 1.54;
      $output['Rin'] = $info['Rin']['mean'];
      return array_merge($output, $props);
   } else {
      $output['exists'] = 0;
      return $output;
   }
}

function getCOVARunDetail($listobject, $elid, $runid, $host = '') {
   // gets a mode run summary using new COVA framework specific routines
   $quick_num = 1000;
   // get information for the parent container
   $output = getStatusSingle($listobject, $elid, $runid, $host);   
   $run_rec = getRunFile($listobject, $elid, $runid);
   if (is_array($run_rec)) {
      if ($output['run_status'] == '') {
         // we have no system_status entry, but we DO have a file, so we consider it to be a 
         // valid completed model run
         $output['run_status'] = 0;
      }
      $output['exists'] = 1;
      $run_file = $run_rec['output_file'];
      $output['runfile'] = $run_file;
      $output['rundate'] = $run_rec['run_date'];
      $output['starttime'] = $run_rec['starttime'];
      $output['endtime'] = $run_rec['endtime'];
      $info = verifyRunVars($run_file,array('wd_mgd','ps_mgd','Qout'),$quick_num);
      $output['Qout'] = $info['Qout']['mean'];
      $imp_off = $info['impoundment_inactive']['max'];
      // get this from the river since the mapping is wrong in the broadcast object
      //$output['demand_mgd'] = $info['demand_mgd']['mean'];
      $output['discharge_mgd'] = $info['ps_mgd']['mean'];
      // ***********************************************
      // ***     verify the land segment flows objects
      //$landrecs = getChildComponentType($listobject, $elid, 'CBPLandDataConnection', -1);
      $landrecs = getCOVACBPLanduseObjects($listobject, $elid);
      foreach ($landrecs as $thisland) {
         $cid = $thisland['elementid'];
         $prop_array = array('area_sqmi', 'Qout');
         $ls_rec = getRunFile($listobject, $cid, $runid);
         $ls_file = $ls_rec['output_file'];
         $info = verifyRunVars($ls_file,$prop_array,$quick_num);
         $output['landsegs'][] = array(
            'landseg'=>$thisland['landseg'],
            'area_sqmi'=>$info['area_sqmi']['mean'],
            'Qout'=>$info['Qout']['mean']
         );
      }
      // get information for the Mainstem Container pertaining to drainage area
      $child_rec = getChildComponentType($listobject, $elid, 'USGSChannelGeomObject', 1);
      $thischild = $child_rec[0];
      $cid = $thischild['elementid'];
      $prop_array = array('drainage_area', 'area', 'length');
      $props = getElementPropertyValue($listobject, $cid, $prop_array);
      // get Runoff data from the Mainstem Container peraining to drainage area
      $runoff_rec = getRunFile($listobject, $cid, $runid);
      $runoff_file = $runoff_rec['output_file'];
      $info = verifyRunVars($runoff_file,array('demand_broadcast_mgd', 'trib_area', 'Qtrib','Runit', 'local_area'),$quick_num);
      $output['demand_cfs'] = $info['demand_broadcast_mgd']['mean'] * 1.54;
      $output['Rin'] = $info['Runit']['mean'] * $info['local_area']['mean'];
      $output['Qtrib'] = $info['Qtrib']['mean'];
      $output['trib_area'] = $info['trib_area']['mean'];
      return array_merge($output, $props);
   } else {
      $output['exists'] = 0;
      return $output;
   }
}

function summarizeRun($listobject, $recid, $run_id, $startdate, $enddate, $force=0, $strict=1) {
   error_log("Summarizing run $run_id for element $recid");
   $output = array();
   $run_mesg = '';
   $node_types = array('cova_ws_container', 'cova_ws_subnodal', 'vahydro_lite_container');
   $elinfo = getElementInfo($listobject, $recid);
   $isnode = in_array($elinfo['custom1'], $node_types);
   // only do this if the summary is not already done   
   if ( (!checkSummary($listobject, $recid, $run_id)) or $force) {
      $outlet = array();
      $outlet[] = array('elementid'=>$recid);
      error_log("Checking Run Data (strict = $strict)\n");
      if ( ($strict == -1) or (!$isnode) ) {
         // we do not run verify routines, but assume that it is OK
         $run_verified = 1;
         $run_mesg .= " No VAHydro criteria verification attempted. \n";
      } else {
         $output = checkRunData($listobject, $run_id, $startdate, $enddate, $outlet, $strict);
         //print("Done Checking\n");
         if ( (count($output['bad_elements']) > 0) or (count($output['null_elements']) > 0) ) {
            if (count($output['null_elements']) > 0) {
               $run_verified = -1;
               $run_mesg .= $output['msg_null'];
            } else {
               $run_verified = 0;
               $run_mesg .= $output['msg_bad'];
            }
         } else {
            $run_verified = 1;
            $run_mesg .= $output['msg_ok'];
         }
      }
      postSummary($listobject, $recid, $run_id, $listobject->escapeString($run_mesg), $run_verified);
      error_log("Posting summary verified = $run_verified ");
   }
   return $output;
}

function checkVerified($listobject, $recid, $run_id) {
   $listobject->querystring = "  select 1 as numsum, starttime::date as starttime, endtime::date as endtime from scen_model_run_elements where runid = $run_id and elementid = $recid and run_verified = 1 ";
   //print("$listobject->querystring \n");
   $listobject->performQuery();
   if (count($listobject->queryrecords) > 0) {
      return $listobject->queryrecords[0];
   } else {
      return array('numsum'=>0);
   }
}


function checkSummary($listobject, $recid, $run_id) {
   $listobject->querystring = "  select count(*) as numsum from scen_model_run_elements where runid = $run_id and elementid = $recid and ( (run_summary is not null) and (run_summary <> '') ) ";
   error_log("$listobject->querystring \n");
   $listobject->performQuery();
   if (count($listobject->queryrecords) > 0) {
      return $listobject->getRecordValue(1,'numsum');
   } else {
      return 0;
   }
}

function postSummary($listobject, $recid, $run_id, $runsum, $verified = 0) {
   $listobject->querystring = "  update scen_model_run_elements set run_summary = '$runsum', run_verified = $verified where runid = $run_id and elementid = $recid ";
   //print("$listobject->querystring \n");
   $listobject->performQuery();
   if (count($listobject->queryrecords) > 0) {
      return $listobject->getRecordValue(1,'numsum');
   } else {
      return 0;
   }
}

function retrieveRunSummary($listobject, $elid, $runid) {
   $output = array();
   //print("Checking for run file\n");   
   $status_vars = verifyRunStatus($listobject, $elid, $runid, '');
   $status = $status_vars['status_flag'];
   switch ($status) {
      case -1:
         $fc = 'red';
      break;
      
      case 0:
         $fc = 'black';
      break;
      
      case 1:
      case 2:
         $fc = 'green';
      break;
      
      case 3:
         $fc = 'gray';
      break;
      
      default:
         $fc = 'black';
      break;
   }
   $output['run_summary'] = "<font color='$fc'>Current Run Status: " . $status . "</font><br>";
   $output['run_summary'] .= "Most recent Run Info<br>";
   $run_rec = getRunFile($listobject, $elid, $runid);
   $output['starttime'] = $run_rec['starttime'];
   $output['endtime'] = $run_rec['endtime'];
   if (!strlen($run_rec['run_summary']) > 0) {
      //print("No summary found, summarizing\n");
      $startdate = $run_rec['starttime'];
      $enddate = $run_rec['endtime'];
      $output = summarizeRun($listobject, $elid, $runid, $startdate, $enddate);
      $run_rec = getRunFile($listobject, $elid, $runid);
   };
   //print("Returning run info\n");
   $output['query'] = $status_vars['query'];
   $output['run_status'] = $status;
   $output['run_summary'] .= $run_rec['run_summary'];
   $output['run_verified'] = $run_rec['run_verified'];
   $output['output_file'] = $run_rec['output_file'];
   $output['elementid'] = $elid;
   
   return $output;
}

function shakeTree($listobject, $sip, $num_sim, $recid, $run_id, $startdate, $enddate, $cache_date, $debug = 0, $strict=1, $run_mode = NULL, $extra_params = array(), $runtype = 'cached' ) {
   error_log("strictness setting: $strict \n");
   // Introduce - run_params ()
   // run_type
   // cache_runid
   // cache_list
   // test_only
   // scenarioid
   // cache_level

   if ($run_mode === NULL) {
      $run_mode = $run_id;
   }
   // Algorithm
   // check to see if this object is allowed to run standalone - if not return 1
   //checkTreeRunDate
   //if LRD of tree is > CD, return 1, else
   //if this element is currently running (runstate = 1 or 2), return 0, else
   //set runStatus = 3 (queued but not yet running)
   //Iterate through directly linked children call shakeTree
   //If shakeTree any child returns 0, return 0, else
   //If num_running < max_simultaneous, Run this element, return 0
   /*
   $solo_check = checkStandAlone($listobject, $recid);
   if (!$solo_check) {
      // this can't run solo, so we assume that it MUST be run as a part of its parent container
      // thus we return true, so it will be ignored in this batch, but run as part of its parent
      removeRunCache($listobject, $recid, $run_id);
      return 1;
   }
   */
   $elemname = getElementName($listobject, $recid);
   $cacheable = getElementCacheable($listobject, $recid);
   if ($debug) {
      error_log("Element $elemname ($recid) Cacheable Mode - $cacheable \n");
   }
   switch ($cacheable) {
      case 0:
      error_log("Item $recid can not be cached, returning 1 \n");
      return 1;
      break;
      
      case 2:
      // proceed on, but since this object can not be cached by itself, it will return 1 when it gets to the 
      // step to be run
      error_log("Item $recid permits pass-through caching, checking for cacheable/runnable children \n");
      break;
      
      default:
      // proceed on, this is a fully stand-alone object, capable of separate running and caching
      error_log("Item $recid permits full caching, checking last run date \n");
      break;
      
   }
   summarizeRun($listobject, $recid, $run_id, $startdate, $enddate, 0, $strict);
   
   if ($debug) {
      error_log("Tree Check - $tree_check : checkTreeRunDate(listobject, $recid, $run_id, $startdate, $enddate, $cache_date);\n");
   }
   $tree_check = checkTreeRunDate($listobject, $recid, $run_id, $startdate, $enddate, $cache_date, $debug);
   
   if ($tree_check) {
      // this tree has been run since the cache_date - nothing to do!
      return 1;
   }
   $running = array(1,2);
   $status_vars = verifyRunStatus($listobject, $recid, $run_id, $sip);
   $status = $status_vars['status_flag'];
   if ($debug) {
      error_log("Run status Check -  $status \n");
   }
   if (in_array($status, $running)) {
      // this element is currently running, return 0 (not finished)
      return 0;
   }
   // set status to 3
   if ($status <> 3) {
      if ($cacheable <> 2) {
         setStatus($listobject, $recid, "Element $recid Queued for Run", $sip, 3, $run_id);
      } else {
         //setStatus($listobject, $recid, "Searching pass-through cache $recid for children", $sip, 0, $run_id);
      }
   }
   $children = getNextContainers($listobject, $recid);
   $child_status = 1;
   foreach ($children as $thischild) {
      $childid = $thischild['elementid'];
      $check = shakeTree($listobject, $sip, $num_sim, $childid, $run_id, $startdate, $enddate, $cache_date, 0, $strict, $run_mode, $extra_params, $runtype);
      $child_status = $child_status & $check;
      if ($debug) {
         error_log("Result of shakeTree($childid) - $check / (group status - $child_status) \n");
      }
   }
   
   if (!$child_status) {
      error_log("Children of object $recid currently running - returning \n");
      return 0;
   }
   if ($cacheable == 2) {
      // this is a special type of object, which can not be cached, but may contain cacheable children
      // since we reached this step, all of its children have been verified so we go home
      error_log("Object $recid is un-cacheable, all children run or cached - returning \n");
      return 1;
   } 

   $active_models = returnCommands('php','run_model.php');
   $num_running = count($active_models);
   error_log("$num_running models currently running");
   if ($num_running < $num_sim) {
      // spawn a new one
      $run_params = array();
      $run_params['elements'] = $recid;
      $run_params['runid'] = $run_id;
      $run_params['startdate'] = $startdate;
      $run_params['enddate'] = $enddate;
      $run_params['cache_level'] = $cache_date;
      $run_params['runtype'] = $runtype;
      foreach ($extra_params as $key => $val) {
         $run_params[$key] = $val;
      }
      if ($debug) {
         // allows objects own debug setting
         $prop_array = array('run_mode' => $run_mode);
      } else {
         // forces object and children to run silently
         $prop_array = array('run_mode' => $run_mode, 'debug' => 0, 'cascadedebug' => 1);
      }
      if (isset($run_params['flow_mode'])) {
        $prop_array['flow_mode'] = $run_params['flow_mode'];
      }
      if (isset($run_params['dt'])) {
        $prop_array['dt'] = $run_params['dt'];
      }
      error_log("Setting properties on element $elementid " . print_r($prop_array,1));
      updateObjectProps($projectid, $recid, $prop_array);
      // check status one last time just in case another thread has called this one in the interim
      $status_update = verifyRunStatus($listobject, $recid);
      $recent_status = $status_update['status_flag'];
      if (!in_array($recent_status, $running)) {
         error_log("Forking $recid with params " . print_r($run_params,1) . "\n");
         
         //setStatus($listobject, $recid, "Model Run for Element $recid Forked", $sip, 1, $run_id);
         forkRun($run_params);
      }
      //if ($debug) {
         error_log("Model Run for Element $recid Forked \n");
         error_log("Model Run Parameters: ");
         error_log(print_r($run_params,1));
      //}
      deleteRunRecord($listobject, $recid, $run_id);
   } else {
     error_log("$num_running models currently running, max $num_sim, awaiting free processes");
   }
   return 0;
   
}


function formatPrintContainer($container) {
   $innerHTML = '';
   $innerHTML .= "<table border=1>";
   $innerHTML .= "<tr><td align=center valign=top>";
   $innerHTML .= "Element: " . $container['elemname'] . "<br>";
   $innerHTML .= "ID: " . $container['elementid'] . "<br>";
   $innerHTML .= "Order: " . $container['order'] . "<br>";
   if (isset($container['drainage_area'])) {
      $innerHTML .= "Drainage Area: " . $container['drainage_area'] . "<br>";
      $innerHTML .= "Mean Flow: " . $container['Qout'] . "<br>";
      $innerHTML .= "Mean withdrawal (cfs): " . $container['demand_cfs'] . "<br>";
      $innerHTML .= "Mean discharge (MGD): " . $container['discharge_mgd'] . "<br>";
      $innerHTML .= "Local Area: " . $container['area'] . "<br>";
      $innerHTML .= "Local Flow: " . $container['Rin'] . "<br>";
      $innerHTML .= "Run Date: " . $container['rundate'] . "<br>";
      $innerHTML .= "Model Start: " . $container['starttime'] . "<br>";
      $innerHTML .= "Model End: " . $container['endtime'] . "<br>";
   }
   if (isset($container['landsegs'])) {
      $innerHTML .= "Land Segments: <ul>";
      foreach ($container['landsegs'] as $lseg) {
         $Q = $lseg['Qout'];
         $A = $lseg['area_sqmi'];
         $ls = $lseg['landseg'];
         $innerHTML .= "<li> $ls, Q = $Q, A = $A ";
      }
      $innerHTML .= "</ul>";
   }
   if (isset($container['link'])) {
      $link = $container['link'];
      $innerHTML .= "<a href='$link'>" . $link . "</a><br>";
   }
   switch ($container['run_status']) {
      case -1:
         $fc = 'red';
      break;
      
      case 0:
         $fc = 'black';
      break;
      
      case 1:
      case 2:
         $fc = 'green';
      break;
      
      case 3:
         $fc = 'gray';
      break;
      
      default:
         $fc = 'black';
      break;
   }
   $innerHTML .= "<font color='$fc'>Current Run Status: " . $container['run_status'] . "</font><br>";
   $innerHTML .= "# of Children: " . count($container['children']);
   // recurse
   if (count($container['children']) > 0) {
      $innerHTML .= "<table>";
      $innerHTML .= "<tr>";
      foreach($container['children'] as $thischild) {
        $innerHTML .= "<td align=center valign=top>";
        // recurse
        $innerHTML .= formatPrintContainer($thischild);
        $innerHTML .= "&nbsp;</td>";
      }
      $innerHTML .= "</tr></table>";
   }
   
   $innerHTML .= "</td></tr></table>";
   return $innerHTML;
}


function formatPrintMessages($container) {
   $innerHTML = '';
   $innerHTML .= "<table border=1>";
   $innerHTML .= "<tr><td align=center valign=top>";
   $innerHTML .= $container['message'] . "<br>";
   if (isset($container['link'])) {
      $link = $container['link'];
      $innerHTML .= "<a href='$link'>" . $link . "</a><br>";
   }
   // recurse
   if (count($container['children']) > 0) {
      $innerHTML .= "<table>";
      $innerHTML .= "<tr>";
      foreach($container['children'] as $thischild) {
        $innerHTML .= "<td align=center valign=top>";
        // recurse
        $innerHTML .= formatPrintMessages($thischild);
        $innerHTML .= "&nbsp;</td>";
      }
      $innerHTML .= "</tr></table>";
   }
   
   $innerHTML .= "</td></tr></table>";
   return $innerHTML;
}
?>