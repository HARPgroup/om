<?php

// ***********************************************
// *******         lib_nhdplus.php       *********
// ***********************************************

// getBasin - get all of the segments of a given outlet point, or HUC, in a nested array
// resolveMultipleToNodes - for an entry with multiple downstream linkages (which is erroneous unless it is tidal), select the most sensible downstream link for that segment.

// findTribs - retrieves the next upstream linkages for this entity - make sure that we do not allow a trib to be inserted at multiple points in the tree, thuis, we 

// findNextDown - retrieves the next downstream linkage for this entity - resolves the bug with multiple downstream entities by selecting the downstream link with the shortest path to a common downstream point

function getMergedNHDBasin($hydro_db, $lat, $lon, $extra_basins = 0, $debug = 0, $tol = 0.001, $pct_shrink = 0.9) {
   $merged_info = array();
   // find the outlet
   $outlet_info = findNHDSegment($hydro_db, $lat, $lon);
   $outlet = $outlet_info['comid'];
   $outletarea = $outlet_info['areasqkm'];
   $nextdown = $outlet;
   // if we requested extra downstream basins, get them now and reset the outlet so we can query all of IT's upstream
   // segments
   if ($debug) error_log("Looking for $extra_basins downstream linkages <br>\n");
   for ($i = 0; $i < $extra_basins; $i++) {
      $nextdown = findNextDown($hydro_db, $nextdown);
      $merged_info['flow_segments'][] = $nextdown;
      if ($debug) error_log("Adding $nextdown to segment list <br>\n");
      $next_info = findNHDSegInfo($hydro_db, $nextdown);
      $outletarea = $next_info['areasqkm'];
   }   
   $outlet = $nextdown;
   $merged_info['outlet_comid'] = $outlet;
   $merged_info['areasqkm'] = $outletarea;
   //Find the tribs for this outlet
   if ($debug) error_log("\n Getting Tributaries for $outlet<br>\n");
   //$result = findTribs($hydro_db,$outlet);
   $result = findMergedTribs($hydro_db,$outlet, $debug);
   $merged_info['flow_tree'] = $result;
   $merged_info['flow_segments'] = $result['segment_list'];
   $merged_info['merged_segments'] = $result['merged_segments'];
   // if we were asked to get extras, we do so now:
   if ($debug) error_log("Found basins with OMID's: " . join(',',$result['segment_list']) . " <br>\n");
   if ($debug) error_log("Found merged sbasins with COMID's: " . join(',',$result['merged_segments']) . " <br>\n");
   // create a merged shape for this outlet and its tributaries
   // check to see if this outlet has already been gotten in the cache
   $merged_shape = findNHDBasinShape($hydro_db, $outlet);
   // if not, create it anew
   if ($merged_shape === false) {
      if ($debug) error_log("Shape NOT in NHD+ Merged basin geometry cache .. calculating.\n");
      $merged_shape = getMergedNHDShape($hydro_db, $merged_info['flow_segments'],$merged_info['merged_segments'], $debug, $tol, $pct_shrink);
   } else {
      if ($debug) error_log("Shape located in NHD+ Merged basin geometry cache .. retrieving cached.\n");
   }
   $merged_info['the_geom'] = $merged_shape;
   return $merged_info;
}

function createMergedNHDShape($usgsdb,$contid, $debug) {
   $result = findMergedTribs($usgsdb,$contid, $debug);
   $wktgeom = getMergedNHDShape($usgsdb, $result['segment_list'],$result['merged_segments'], 1);
   storeNHDMergedShape($usgsdb, $contid, $wktgeom, 1, $debug);
}

function findNHDBasinShape($dbobj, $outlet) {
   $dbobj->querystring = "  select st_asText(the_geom) as seg_geom from nhd_fulldrainage ";
   $dbobj->querystring .= " where comid = $outlet ";
   //error_log("$dbobj->querystring ; <br>\n");
   $dbobj->performQuery();
   if ($dbobj->numrows > 0) {
      $shp = $dbobj->getRecordValue(1,'seg_geom');
      return $shp;
   } else {
      return false;
   }
}

function checkNHDBasinShape($dbobj, $outlet) {
   $dbobj->querystring = "  select count(*) as numrecs from nhd_fulldrainage ";
   $dbobj->querystring .= " where comid = $outlet ";
   //error_log("$dbobj->querystring ; <br>\n");
   $dbobj->performQuery();
   if ($dbobj->numrows > 0) {
      $numrecs = $dbobj->getRecordValue(1,'numrecs');
      if ($numrecs > 0) {
         return true;
      } else {
         return false;
      }
   } else {
      return false;
   }
}
   

function storeNHDMergedShape($dbobj, $comid, $wkt_geom, $overwrite = 0, $debug = 0) {
   

   $dbobj->querystring = " select count(*) as matches from nhd_fulldrainage where comid = $comid ";
   if ($debug) error_log("$usgsdb->querystring <br>\n");
   $dbobj->performQuery();
   $matches = $dbobj->getRecordValue(1,'matches');
   if ( ($matches == 0) or ($overwrite) ) {
      $dbobj->querystring = " delete from nhd_fulldrainage where comid = $comid ";
      if ($debug) {
         error_log("$dbobj->querystring <br>\n");
      }
      $dbobj->performQuery();
      if ($debug) {
         error_log("Storing NHD+ Shape for $comid \n<br>");
      }
      $dbobj->querystring = " insert into nhd_fulldrainage (comid, the_geom) values ($comid, st_multi( st_geomFromText('$wkt_geom',4269)) ) ";
      if ($debug) {
         error_log(substr($dbobj->querystring, 0,64) . "<br>\n");
      }
      $dbobj->performQuery();
   } else {
      if ($debug) {
         error_log("Already stored NHD+ Shape for $comid, overwrite = $overwrite \n<br>");
      }
   }
      
}

function deleteNHDMergedShape($dbobj, $comid, $debug = 0) {
   $dbobj->querystring = " delete from nhd_fulldrainage where comid = $comid ";
   if ($debug) {
      error_log("$dbobj->querystring <br>\n");
   }
   if ($debug) {
      error_log("Deleting NHD+ Shape for $comid \n<br>");
   }
   $dbobj->performQuery();
   if ($debug) {
      error_log("Deleted. \n<br>");
   }
   
}

function getMergedNHDShape($dbobj, $seglist, $cache_merge_list = array(), $debug = 0, $maxtol = 0.001, $pct = 0.9) {
   $segs = implode(',', $seglist);
   $tol = 0.00001;
   $tolinc = 0.00001;
   $shp = false;
   while ( ($shp === false) and ($tol <= $maxtol) ) {
      $dbobj->querystring = "  select st_asText( ";
      if ( ($tol + $tolinc) >= ($maxtol) ) {
         //$dbobj->querystring .= "      st_multi(st_polygonize(the_geom)) ";
         $dbobj->querystring .= "      st_multi(ST_ConcaveHull(the_geom, $pct)) ";
      } else {
         $dbobj->querystring .= "      st_multi(st_union(st_buildarea(the_geom))) ";
      } 
      $dbobj->querystring .= "  ) as seg_geom ";
      $dbobj->querystring .= "  FROM ( ";
      $dbobj->querystring .= "  (select ";
      if ( ($tol + $tolinc) >= ($maxtol) ) {
         //$dbobj->querystring .= "      st_multi(st_polygonize(the_geom)) ";
         $dbobj->querystring .= "      st_multi(ST_ConcaveHull(the_geom, $pct)) ";
         //$dbobj->querystring .= "         polygonize(the_geom) ";
      } else {
         $dbobj->querystring .= "      st_multi(st_union(st_buffer(the_geom,$tol))) ";
         //$dbobj->querystring .= "         st_union(st_buffer(the_geom,$tol)) ";
      } 
      $dbobj->querystring .= "       as the_geom ";
      $dbobj->querystring .= " from nhdplus_catchment ";
      $dbobj->querystring .= " where comid in ($segs) ";
      $dbobj->querystring .= " ) ";
      if (count($cache_merge_list) > 0) {
         $mergedsegs = join(',', $cache_merge_list);
         $dbobj->querystring .= "  UNION ";
         $dbobj->querystring .= "  (select  ";
         if (count($cache_merge_list) == 1) {
            // no need to union, since it is only one segment and has already been processed in this fashion
            $dbobj->querystring .= "      the_geom ";
         } else {
            if ( ($tol + $tolinc) >= ($maxtol) ) {
               //$dbobj->querystring .= "      st_multi(st_polygonize(the_geom)) ";
               $dbobj->querystring .= "      st_multi(ST_ConcaveHull(the_geom, $pct)) ";
               //$dbobj->querystring .= "         polygonize(the_geom) ";
            } else {
               $dbobj->querystring .= "      st_multi(st_union(st_buffer(the_geom,$tol))) ";
               //$dbobj->querystring .= "         st_union(st_buffer(the_geom,$tol)) ";
            } 
         }
         $dbobj->querystring .= "       as the_geom ";
         $dbobj->querystring .= " from nhd_fulldrainage ";
         $dbobj->querystring .= " where comid in ($mergedsegs) ";
         $dbobj->querystring .= " ) ";
      }
      $dbobj->querystring .= " ) as foo ";
      if ($debug) {
         error_log("$dbobj->querystring ; <br>\n");
      }
      $dbobj->performQuery();
      if ($dbobj->numrows > 0) {
         $shp = $dbobj->getRecordValue(1,'seg_geom');
      } else {
         $shp = false;
      }
      $tol += $tolinc;
   }
   return $shp;
}

function getSingleNHDShape($dbobj, $seglist, $debug = 0) {
   $segs = join(',', $seglist);
   $tol = 0.00001;
   $tolinc = 0.00001;
   $maxtol = 0.001;
   $shp = false;
   while ( ($shp === false) and ($tol <= $maxtol) ) {
      $dbobj->querystring = "  select st_asText( ";
      if ( ($tol + $tolinc) >= ($maxtol) ) {
         $dbobj->querystring .= "      st_multi(polygonize(the_geom)) ";
      } else {
         $dbobj->querystring .= "      st_multi(st_union(buildarea(the_geom))) ";
      } 
      $dbobj->querystring .= "  ) as seg_geom ";
      $dbobj->querystring .= "  FROM ( ";
      $dbobj->querystring .= "  (select ";
      if ( ($tol + $tolinc) >= ($maxtol) ) {
         $dbobj->querystring .= "      st_multi(polygonize(the_geom)) ";
         //$dbobj->querystring .= "         polygonize(the_geom) ";
      } else {
         $dbobj->querystring .= "      st_multi(st_union(st_buffer(the_geom,$tol))) ";
         //$dbobj->querystring .= "         st_union(st_buffer(the_geom,$tol)) ";
      } 
      $dbobj->querystring .= "       as the_geom ";
      $dbobj->querystring .= " from nhdplus_catchment ";
      $dbobj->querystring .= " where comid in ($segs) ";
      $dbobj->querystring .= " ) ";
      $dbobj->querystring .= " ) as foo ";
      if ($debug) {
         error_log("$dbobj->querystring ; <br>\n");
      }
      $dbobj->performQuery();
      if ($dbobj->numrows > 0) {
         $shp = $dbobj->getRecordValue(1,'seg_geom');
      } else {
         $shp = false;
      }
      $tol += $tolinc;
   }
   return $shp;
}

function getNHDLandUse($usgsdb, $seglist, $units = 'sqmi', $debug=0) {

   switch ($units) {
      case 'sqkm':
      $conv = 1.0;
      break;

      case 'sqmi':
      $conv = 0.386102159;
      break;

      case 'acres':
      $conv = 247.105;
      break;

      default:
      $conv = 1.0;
      break;
   }
   $comlist = join(',', $seglist);

   $usgsdb->querystring = "  select sum($conv * b.areasqkm * a.nlcd_11 / 100.0) as nlcd_11, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_12 / 100.0) as nlcd_12, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_21 / 100.0) as nlcd_21, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_22 / 100.0) as nlcd_22, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_23 / 100.0) as nlcd_23, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_31 / 100.0) as nlcd_31, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_32 / 100.0) as nlcd_32, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_33 / 100.0) as nlcd_33, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_41 / 100.0) as nlcd_41, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_42 / 100.0) as nlcd_42, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_43 / 100.0) as nlcd_43, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_51 / 100.0) as nlcd_51, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_61 / 100.0) as nlcd_61, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_71 / 100.0) as nlcd_71, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_81 / 100.0) as nlcd_81, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_82 / 100.0) as nlcd_82, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_83 / 100.0) as nlcd_83, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_84 / 100.0) as nlcd_84, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_85 / 100.0) as nlcd_85, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_91 / 100.0) as nlcd_91, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_92 / 100.0) as nlcd_92, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * pct_cn / 100.0) as area_cn, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * pct_mx / 100.0) as area_mx, ";
   $usgsdb->querystring .= "    sum(sum_pct)/count(*)::float8 as sum_pct ";
   $usgsdb->querystring .= " from nhdplus_catt_nlcd as a, nhdplus_catchment as b ";
   $usgsdb->querystring .= " where a.comid in ($comlist) ";
   $usgsdb->querystring .= " and b.comid = a.comid ";
   if ($debug) {
      error_log("$usgsdb->querystring ;");
   }
   $usgsdb->performQuery();
   
   if ($usgsdb->numrows > 0) {
      return $usgsdb->queryrecords[0];
   } else {
      return FALSE;
   }

}

function getNHDChannelInfo($usgsdb, $outlet, $seglist, $units = 'km', $debug = 0) {
   if (is_array($seglist)) {
      $seglist = join(',', $seglist);
   }
   switch ($units) {
      case 'km':
      $conv = 1.0;
      break;

      case 'mi':
      $conv = 0.62137119;
      break;

      case 'ft':
      $conv = 3280.8;
      break;

      default:
      $conv = 1.0;
      break;
   }
   $comlist = join(',', $basininfo['flow_segments']);

   $usgsdb->querystring = "  select sum(b.lengthkm*c.slope)/sum(b.lengthkm) as c_slope, max(c.cumdrainag) * $conv * $conv as drainage_area, ";
   $usgsdb->querystring .= " sum(b.lengthkm) * $conv as reachlen, sum(b.lengthkm*c.slope), count(*)  ";
   $usgsdb->querystring .= " from nhdplus_flatt_flow as c, nhdplus_flowline as b, nhdplus_flowline as a ";
   $usgsdb->querystring .= " where b.comid in ($seglist) ";
   $usgsdb->querystring .= "    and b.comid = c.comid ";
   // joining on the gnis ID gives only a single reaches attributes, so we have a main channel
   $usgsdb->querystring .= "   and (  ";
   $usgsdb->querystring .= "    (b.gnis_id = a.gnis_id)  ";
   // if we only have a headwater, it might have no GNIS_ID, so we allow for null (but NULL <> NULL)
   $usgsdb->querystring .= "    or (b.gnis_id is null and a.gnis_id is null)  ";
   $usgsdb->querystring .= "   ) ";
   $usgsdb->querystring .= "   and a.comid = $outlet ";
   if ($debug) {
      error_log("$usgsdb->querystring ; \n");
   }
   $usgsdb->performQuery();
   
   if ($usgsdb->numrows > 0) {
      return $usgsdb->queryrecords[0];
   } else {
      return FALSE;
   }

}



function getNHDLandUseWKT($usgsdb, $wktgeom, $units = 'sqmi') {
   switch ($units) {
      case 'sqkm':
      $conv = 1.0;
      break;

      case 'sqmi':
      $conv = 0.386102159;
      break;

      case 'acres':
      $conv = 247.10;
      break;

      default:
      $conv = 1.0;
      break;
   }
   $comlist = join(',', $basininfo['flow_segments']);

   $usgsdb->querystring = "  select sum($conv * b.areasqkm * a.nlcd_11 / 100.0) as nlcd_11, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_12 / 100.0) as nlcd_12, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_21 / 100.0) as nlcd_21, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_22 / 100.0) as nlcd_22, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_23 / 100.0) as nlcd_23, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_31 / 100.0) as nlcd_31, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_32 / 100.0) as nlcd_32, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_33 / 100.0) as nlcd_33, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_41 / 100.0) as nlcd_41, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_42 / 100.0) as nlcd_42, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_43 / 100.0) as nlcd_43, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_51 / 100.0) as nlcd_51, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_61 / 100.0) as nlcd_61, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_71 / 100.0) as nlcd_71, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_81 / 100.0) as nlcd_81, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_82 / 100.0) as nlcd_82, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_83 / 100.0) as nlcd_83, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_84 / 100.0) as nlcd_84, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_85 / 100.0) as nlcd_85, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_91 / 100.0) as nlcd_91, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * a.nlcd_92 / 100.0) as nlcd_92, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * pct_cn / 100.0) as area_cn, ";
   $usgsdb->querystring .= "    sum($conv * b.areasqkm * pct_mx / 100.0) as area_mx, ";
   $usgsdb->querystring .= "    sum(sum_pct)/count(*)::float8 as sum_pct ";
   $usgsdb->querystring .= " from nhdplus_catt_nlcd as a,   ";
   $usgsdb->querystring .= " ( select comid, ";
   $usgsdb->querystring .= "      areasqkm *  st_area2d(st_intersection(st_setsrid( st_geomFromText('$wktgeom'),4269),the_geom)) ";
   $usgsdb->querystring .= "         /  st_area2d(the_geom) as areasqkm ";
   $usgsdb->querystring .= "   from nhdplus_catchment ";
   $usgsdb->querystring .= "   where st_setsrid( st_geomFromText('$wktgeom'),4269) && the_geom ";
   $usgsdb->querystring .= "      and  st_area2d(st_intersection(st_setsrid( st_geomFromText('$wktgeom'),4269),the_geom)) > 0 ";
   $usgsdb->querystring .= " ) as b ";
   $usgsdb->querystring .= " where b.comid = a.comid ";
   $usgsdb->performQuery();
   //$usgsdb->showList();

   //error_log(" $usgsdb->querystring ; \n Finished \n");
   
   if ($usgsdb->numrows > 0) {
      return $usgsdb->queryrecords[0];
   } else {
      return FALSE;
   }
}

function checkOverlap($usgsdb, $wktgeom) {
   $usgsdb->querystring = " select comid, areasqkm as areasqkm_orig,  ";
   $usgsdb->querystring .= "       st_area2d(st_intersection(st_setsrid( st_geomFromText('$wktgeom'),4269),the_geom)) ";
   $usgsdb->querystring .= "         /  st_area2d(the_geom) as ratio ";
   $usgsdb->querystring .= "   from nhdplus_catchment ";
   $usgsdb->querystring .= "   where st_setsrid( st_geomFromText('$wktgeom'),4269) && the_geom ";
   $usgsdb->querystring .= "      and  st_area2d(st_intersection(st_setsrid( st_geomFromText('$wktgeom'),4269),the_geom)) > 0 ";
   $usgsdb->performQuery();
   if ($usgsdb->numrows > 0) {
      return $usgsdb->queryrecords;
   } else {
      return FALSE;
   }
}

function findNHDSegment($dbobj, $lat, $lon, $debug = 0, $units = 'sqmi') {
   
   $dbobj->querystring = "  select comid, areasqkm from nhdplus_catchment ";
   $dbobj->querystring .= " where st_contains(the_geom, st_setsrid(st_makePoint($lon, $lat),4269)) ";
   $dbobj->querystring .= "    AND the_geom && st_setsrid(st_makePoint($lon, $lat),4269) ";
   if ($debug) {
      error_log("$dbobj->querystring ; <br>\n");
   }
   $dbobj->performQuery();
   if (count($dbobj->queryrecords) > 0) {
      $cominfo = $dbobj->queryrecords[0];
      $cominfo = findNHDSegInfo($dbobj, $cominfo['comid'], $debug, $units);
      return $cominfo;
   } else {
      error_log("No records found \n");
      return FALSE;
   }
}

function findNHDSegInfo($dbobj, $comid, $debug = 0, $units = 'sqkm') {
   switch ($units) {
      case 'sqkm':
      $conv = 1.0;
      break;

      case 'sqmi':
      $conv = 0.386102159;
      break;

      case 'acres':
      $conv = 247.10;
      break;

      default:
      $conv = 1.0;
      break;
   }
   
   $dbobj->querystring = "  select b.*, a.areasqkm, ($conv * c.cumdrainag) as cumdrainag ";
   $dbobj->querystring .= " from nhdplus_catchment as a, nhdplus_catt_nlcd as b,  ";
   $dbobj->querystring .= "    nhdplus_flatt_flow as c ";
   $dbobj->querystring .= " where a.comid = $comid  ";
   $dbobj->querystring .= "    and b.comid = $comid  ";
   $dbobj->querystring .= "    and c.comid = $comid";
   if ($debug) {
      error_log("$dbobj->querystring ; <br>\n");
   }
   $dbobj->performQuery();
   if (count($dbobj->queryrecords) > 0) {
      $cominfo = $dbobj->queryrecords[0];
      $wktgeom = findNHDBasinShape($dbobj, $comid);
      $cominfo['wktgeom'] = $wktgeom;
      return $cominfo;
   } else {
      error_log("No records found \n");
      return FALSE;
   }
}

function findTribs($dbobj, $comid, $debug = 0, $heap = array(), $heapmax=5000) {
   if ($debug) {
      error_log("findTribs called for COMID = $comid \n");
   }
   if (!is_array($heap)) {
      $heap = array();
      $heapmax = 5000;
   }
   $tree = array('segment_id'=>$comid, 'tribs'=>array(), 'segment_list'=>array($comid));
   if (in_array($comid, $heap)) {
      if ($debug) {
         error_log("Circular reference found at node $comid \n");
     }
      return $tree;
   }
   $heap[] = $comid;
   $heapcount = count($heap);
   if ($debug) {
      if ( ($heapcount / 500.0) == intval($heapcount / 500.0)) {
         error_log("Heap size reached $heapcount at node $comid \n");
      }
   }
   if ($heapcount > $heapmax) {
      if ($debug) {
         error_log("Heap max size of $heapmax exceeded at node $comid \n");
     }
      return $tree;
   }
   $direct_tribs = findNextUp($dbobj, $comid, 0, 0);
   foreach ($direct_tribs as $thistrib) {
      $trib_info = findTribs($dbobj, $thistrib, $debug, $heap, $heapmax);
      $tree['tribs'][] = $trib_info;
      foreach ($trib_info['segment_list'] as $this_seg) {
         $tree['segment_list'][] = $this_seg;
      }
   }
   
   return $tree;
}

function findMergedTribs($dbobj, $comid, $debug = 0) {
   global $heap, $heapmax;
   if (!is_array($heap)) {
      $heap = array();
      $heapmax = 5000;
   }
   // operates similarly to findTribs, but stops when it reaches a segment that is cached in the merged table
   $cached = checkNHDBasinShape($dbobj, $comid);
   // if not, create it anew
   if (!($cached === false)) {
      $tree = array('segment_id'=>$comid, 
         'tribs'=>array(), 
         'segment_list'=>array(), 
         'merged_segments' => array($comid)
      );
   } else {
      $tree = array('segment_id'=>$comid, 
         'tribs'=>array(), 
         'segment_list'=>array($comid), 
         'merged_segments' => array()
      );
      if (in_array($comid, $heap)) {
         if ($debug) {
            error_log("Circular reference found at node $comid \n");
         }
         return $tree;
      }
      $heap[] = $comid;
      $heapcount = count($heap);
      if ($debug) {
         if ( ($heapcount / 500.0) == intval($heapcount / 500.0)) {
            error_log("Heap size reached $heapcount at node $comid \n");
         }
     }
      if ($heapcount > $heapmax) {
         if ($debug) {
            error_log("Heap max size of $heapmax exceeded at node $comid \n");
        }
         return $tree;
      }
      $direct_tribs = findNextUp($dbobj, $comid, 0, 0);
      foreach ($direct_tribs as $thistrib) {
         // check for an endless loop
         if (in_array($thistrib, $tree['segment_list']) or in_array($thistrib, $tree['merged_segments'])) {
            break;
         }
         $trib_info = findMergedTribs($dbobj, $thistrib, $debug);
         $tree['tribs'][] = $trib_info;
         foreach ($trib_info['segment_list'] as $this_seg) {
            $tree['segment_list'][] = $this_seg;
         }
         foreach ($trib_info['merged_segments'] as $this_seg) {
            $tree['merged_segments'][] = $this_seg;
         }
      }
   }
   return $tree;
}

function findNextUp($dbobj, $comid, $debug = 0, $verify = 1) {
   $tribs = array();
   $dbobj->querystring = "  select \"FROMCOMID\" as nextid from nhdplus_flow ";
   $dbobj->querystring .= " where \"TOCOMID\" = $comid ";
   $dbobj->querystring .= "    AND \"DIRECTION\" = 709 ";
   // use this group statement on the off chance that there is simple a duplicate down-link, which would fail
   // during the resolveDownConflict routine
   $dbobj->querystring .= " group by nextid ";
   if ($debug) {
      error_log("Checking upstream links <br>\n");
      error_log("$dbobj->querystring ; <br>\n");
   }
   $dbobj->performQuery();
   $trib_recs = $dbobj->queryrecords;
   if ($verify and $debug) {
      error_log("Verifying upstream links " . print_r($trib_recs,1) . " for $comid ; <br>\n");
   }
   foreach ($trib_recs as $this_trib) {
      // verify that this link is not a duplicate, and if it IS, that it is the shortest path
      if ($verify) {
         error_log("Verifying upstream link " . $this_trib['nextid'] . " for $comid ; <br>\n");
         $nextlink = findNextDown($dbobj, $this_trib['nextid'], $debug);
         if ($comid == $nextlink) {
            $tribs[] = $this_trib['nextid'];
            if ($debug) {
               error_log("Verified " . $this_trib['nextid'] . " outlets to $comid ; <br>\n");
            }
         }
      } else {
         $tribs[] = $this_trib['nextid'];
      }
   }
   return $tribs;
   
}

function findNextDown($dbobj, $comid, $debug) {
   if ($debug) {
      error_log("findNextDown called with $comid \n");
   }
   $dbobj->querystring = "  select \"TOCOMID\" as nextid from nhdplus_flow ";
   $dbobj->querystring .= " where \"FROMCOMID\" = $comid ";
   $dbobj->querystring .= "    AND \"DIRECTION\" = 709 ";
   // use this group statement on the off chance that there is simple a duplicate down-link, which would fail
   // during the resolveDownConflict routine
   $dbobj->querystring .= " group by nextid ";
   if ($debug) {
      error_log("Checking downstream links <br>\n");
      error_log("$dbobj->querystring ; <br>\n");
   }
   $dbobj->performQuery();
   $num_links = count($dbobj->queryrecords);
   if ($num_links == 0) {
      return $comid;
   }
   if ($num_links == 1) {
      $nextid = $dbobj->getRecordValue(1,'nextid');
   } else {
      $a = $dbobj->getRecordValue(1,'nextid');
      $b = $dbobj->getRecordValue(2,'nextid');
      if ($debug) {
         error_log("Conflict between $a and $b  <br>\n");
      }
      $nextid = resolveDownConflict($dbobj, $a, $b, $debug);
      if (!$nextid) {
         // resolvedownconflict failed, just guess
         $nextid =  $dbobj->getRecordValue(1,'nextid');
      }
   }
   return $nextid;
}

function resolveDownConflict($dbobj, $a, $b, $debug = 0) {
   $max_its = 3;
   $tries = 0;
   $x = $a;
   $y = $b;
   $xs = array();
   $ys = array();
   $resolution = $a; // defaults to the first one
   while ($tries < $max_its) {
      $xs[] = $x;
      $ys[] = $y;
      $x = findNextDown($dbobj, $x, $debug);
      $y = findNextDown($dbobj, $y, $debug);
      if (!$x and !$y) {
         $resolution = false;
         break;
      }
      if ($debug) {
         error_log("Checking for $y in " . print_r($xs,1) . " \n");
      }
      if (in_array($y, $xs)) {
         $resolution = $a;
         break;
      }
      if ($debug) {
         error_log("Checking for $x in " . print_r($ys,1) . " \n");
      }
      if (in_array($x, $ys)) {
         $resolution = $b;
         break;
      }
      $tries++;
   }
   if ($debug) {
      error_log("Conflict resolved $a and $b - $resolution yields the shortest path <br>\n");
   }
   return $resolution;
}

?>
