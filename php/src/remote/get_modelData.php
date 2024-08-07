<?php
//error_reporting(E_ALL);
include('./config.php');
//error_reporting(E_ALL);

$startdate = '1984-01-01';
//$enddate = '1984-12-31';
$enddate = '2005-12-31';
$cache_date = '2010-10-18 12:00:00';
// specify max models to run at a time
$max_simultaneous = 7;
$scid = 28; // 37?
$params = '';
$delimiter = ',';
$invars = array();

if (isset($argv[1])) {
   $elementid = $argv[1];
} else {
   $elementid = -1;
}
if (isset($argv[2])) {
   $variables = $argv[2];
} else {
   $variables = 'Qout';
}
if (isset($argv[3])) {
   $runid = $argv[3];
} else {
   $runid = -1;
}
if (isset($argv[4])) {
   $startdate = $argv[4];
} else {
   $startdate = '';
}
if (isset($argv[5])) {
   $enddate = $argv[5];
} else {
   $enddate = '';
}
$operation = 2;
if (isset($_GET['elementid'])) {
   $elementid = $_GET['elementid'];
   $invars = $_GET;
}
if (isset($_GET['variables'])) {
   $variables = $_GET['variables'];
   $invars = $_GET;
}
if (isset($_GET['operation'])) {
   $operation = $_GET['operation'];
   $invars = $_GET;
}
if (isset($_GET['runid'])) {
   $runid = $_GET['runid'];
   $invars = $_GET;
}
if (isset($_GET['view'])) {
   $view = $_GET['view'];
} else {
   $view = '';
}

if (isset($_GET['delimiter'])) {
   $invars = $_GET;
   $delimiter = $_GET['delimiter'];
   switch ($delimiter) {
      case '\t':
         $delimiter = "\t";
      break;
      case 'tab':
         $delimiter = "\t";
      break;
      case 'comma':
         $delimiter = ",";
      break;
      case 'pipe':
         $delimiter = "|";
      break;
      
      default:
      break;
   }
}
if (isset($_GET['startdate'])) {
   $startdate = $_GET['startdate'];
   $invars = $_GET;
} 
if (isset($_GET['enddate'])) {
   $enddate = $_GET['enddate'];
   $invars = $_GET;
} 
if (isset($_GET['params'])) {
   $params = $_GET['params'];
} 
if (isset($_GET['querytype'])) {
   $querytype = $_GET['querytype'];
} 
$title_column = 'elementid';
if (isset($_GET['title_column'])) {
   $title_column = $_GET['title_column'];
} 
if (isset($_GET['header'])) {
   $header = $_GET['header'];
}  else {
   $header = 1;
}
if (isset($_GET['include_geom'])) {
   $include_geom = $_GET['include_geom'];
}  else {
   $include_geom = 0;
}
if (isset($_GET['nontidal'])) {
   $nontidal = $_GET['nontidal'];
}  else {
   $nontidal = 0;
}
if (isset($_GET['debug'])) {
   $debug = $_GET['debug'];
}  else {
   $debug = 0;
}

// drupal feed specific
if (isset($_GET['scenarioid'])) {
   $scenarioid = $_GET['scenarioid'];
} 
if (isset($_GET['node_scenarioid'])) {
   $node_scenarioid = $_GET['node_scenarioid'];
} 
$extras = array();
if (isset($_GET['objectclass'])) {
   $objectclass = $_GET['objectclass'];
   $extras['objectclass'] = $objectclass;
} 
if (isset($_GET['custom1'])) {
   $custom1 = $_GET['custom1'];
   $extras['custom1'] = $custom1;
} 
if (isset($_GET['custom2'])) {
   $custom2 = $_GET['custom2'];
   $extras['custom2'] = $custom2;
} 

if (isset($_GET['convert'])) {
  $convert = $_GET['convert'];
  $conversions = array(
    'auglowflow' => array(
      21 => 'wsp_current_alf',
      22 => 'wsp_future_alf',
    ),
    '7q10' => array(
      21 => 'wsp_current_7q10',
      22 => 'wsp_future_7q10',
    ),
    'yr_2002_Qout_mean_mon10_mean' => array(
      21 => 'wsp_current_dor',
      22 => 'wsp_future_dor',
    ),
    'Qout_mean_mon09_pct10' => array(
      21 => 'wsp_current_9w',
      22 => 'wsp_future_9w',
    ),
    'wd_mgd_mean' => array(
      21 => 'wsp_sw_wd_current_mgd',
      22 => 'wsp_sw_wd_future_mgd',
    ),
    'wd_cumulative_mgd_mean' => array(
      21 => 'wsp_sw_cuwd_current_mgd',
      22 => 'wsp_sw_cuwd_future_mgd',
    ),
    'Qout_mean' => array(
      21 => 'qout_run21',
      22 => 'qout_run22',
      2 => 'qout_run2',
    ),
  );
} else {
  $convert = FALSE;
  $conversions = array();
}

if (isset($_GET['date_type'])) {
   switch ($_GET['date_type']) {
      case 'matlab':
      $date_format = 'MM/DD/YYYY';
      break;
      
      default:
      $date_format = '';
      break;
   }
}

switch ($operation) {

   case 1:
   // show the value of requested parameters as a text file
   $output = '';
   $ld = '';
   $loadres = loadModelElement($elementid, array(), 1);
   if (is_object($loadres['object'])) {
      $thisobject = $loadres['object'];
      foreach (explode(',',$variables) as $thisvar) {
         if ($params == '') {
            error_log("Calling getProp($thisvar, $view) \n");
            if ($debug) {
               print("Calling getProp($thisvar, $view) on elementid = $elementid<br>");
            }
				$thisval = $thisobject->getProp($thisvar, $view);
         } else {
            error_log("Calling showElementInfo($thisvar, $params) \n");
            if ($debug) {
               print("Calling showElementInfo($thisvar, $params) <br>");
            }
            $thisval = $thisobject->showElementInfo($thisvar, $params);
         }
			if (is_array($thisval)) {
			   $thisval = json_encode($thisval);
			}
         $output .= $ld . $thisval;
         $ld = "\n";
      }
   }
   print($output);
   break;

   case 2:
   $result = compareRunData($elementid, $runid, $variables, $startdate, $enddate, 1, $debug, array(), 'left outer', $date_format);
   $query = $result['query'];
   if ($debug) {
      print("$query" . "<br>\n");
      print($result['error'] . "<br>\n");
   }
   //print("Date Format: $date_format $query\n");
   if (count($result['records']) > 0) {
      $header = implode($delimiter, array_keys($result['records'][0]));
      print("$header\r\n");
      foreach ($result['records'] as $thisrec) {
         $line = implode($delimiter, array_values($thisrec));
         print("$line\r\n");
      }
   }
   break;
   
   case 3:
   // get model results from the scen_model_run_data table in CSV format
   $varlist = "'" . implode("','", explode(",",$variables)) . "'";
   $listobject->querystring = " select md5(a.elementid::varchar || a.runid::varchar || a.dataname ) as data_hash, 'vahydrosw_wshed_' || b.custom2 as hydrocode, ";
   $listobject->querystring .= " a.* from scen_model_run_data as a ";
   $listobject->querystring .= " left outer join scen_model_element as b ";
   $listobject->querystring .= " on (";
   $listobject->querystring .= "   a.elementid = b.elementid";
   $listobject->querystring .= " )";
   $listobject->querystring .= " where ( ( a.elementid in ($elementid) ) or ('$elementid' = '-1') ) ";
   $listobject->querystring .= "    and ( (a.dataname in ($varlist)) or ('$variables' = '') )";
   $listobject->querystring .= "    and ( ( a.runid in ($runid) ) or ('$runid' = '-1') ) ";
   if (isset($invars['date_created'])) {
      $dc = $invars['date_created'];
      $listobject->querystring .= "    and ( a.date_created >= '$dc'::date ) ";
   }
   if (isset($scenarioid)) {
      $listobject->querystring .= "    and b.scenarioid = $scenarioid ";
   }
   if ($debug) {
      print($listobject->querystring . "<br>");
   }
   $listobject->performQuery();
  if ($listobject->numrows > 0) {
    $header = implode($delimiter, array_keys($listobject->queryrecords[0]));
    if ($convert) {
      $header .= ',propname,varkey,propvalue';
    }
    print("$header\r\n");
    foreach ($listobject->queryrecords as $thisrec) {
      $runid = $thisrec['runid'];
      if ($convert) {
        $cname = $thisrec['dataname'];
        $dataval = $thisrec['dataval'];
        $tname = $cname;
        if (isset($conversions[$cname][$runid])) {
          $tname = $conversions[$cname][$runid];
        }
        $thisrec['dataname'] = $tname;
        $thisrec['propname'] = $tname;
        $thisrec['varkey'] = $tname;
        $thisrec['propvalue'] = $dataval;
      }
      $line = implode($delimiter, array_values($thisrec));
      print("$line\r\n");
    }
  }
  break;
   
   case 4:
   // get model information from the scen_model_element table in CSV format
   $objectclasslist = "'" . implode("','", explode(",",$objectclass)) . "'";
   $custom1list = "'" . implode("','", explode(",",$custom1)) . "'";
   $custom2list = "'" . implode("','", explode(",",$custom2)) . "'";
   $titlist = implode("::varchar || ' ' ||", explode(",",$title_column)) . "::varchar";
   $listobject->querystring = "  select $titlist as element_title, elementid, scenarioid, ";
   if ($include_geom) {
      $listobject->querystring .= " CASE WHEN geomtype = 1 THEN asText(point_geom)  ";
      $listobject->querystring .= "    WHEN geomtype = 2 THEN asText(line_geom)  ";
      $listobject->querystring .= "    WHEN geomtype = 3 THEN asText(poly_geom)  ";
      $listobject->querystring .= " ELSE NULL ";
      $listobject->querystring .= " END as wkt_geom, ";
   }
   $listobject->querystring .= " elemname, objectclass, custom1, custom2 ";
   $listobject->querystring .= " from scen_model_element ";
   $listobject->querystring .= " where ( ( elementid in ($elementid) ) or ('$elementid' = '-1') ) ";
   $listobject->querystring .= "    and ( ( scenarioid in ($scenarioid) ) or ('$scenarioid' = '-1') ) ";
   if (count(explode(",",$custom1)) > 0) {
      $listobject->querystring .= "    and ( custom1 in ($custom1list) ) ";
   }
   $listobject->querystring .= "    and ( ( custom2 in ($custom2list) ) or ($custom2list = '') ) ";
   $listobject->querystring .= "    and ( ( objectclass in ($objectclasslist) ) or ($objectclasslist = '') ) ";
   if ($debug) {
      print($listobject->querystring . "<br>");
   }
   $listobject->performQuery();
   if ($listobject->numrows > 0) {
      $header = implode($delimiter, array_keys($listobject->queryrecords[0]));
      print("$header\r\n");
      if ($debug) {
         print("<br>");
      }
      foreach ($listobject->queryrecords as $thisrec) {
         $line = implode($delimiter, array_values($thisrec));
         print("$line\r\n");
         if ($debug) {
            print("<br>");
         }
      }
   }
   break;
   
   case 5:
   $varlist = "'" . implode("','", explode(",",$variables)) . "'";
   $listobject->querystring = "  select a.elementid from scen_model_element as a, scen_model_element as b ";
   $listobject->querystring .= " where b.elementid = $elementid  ";
   $listobject->querystring .= "    and contains( a.poly_geom, b.point_geom )";
   $listobject->querystring .= "    and a.custom1 in ('cova_ws_subnodal', 'cova_ws_container') ";
   $listobject->querystring .= "    and a.scenarioid = $scenarioid ";
   $listobject->querystring .= "    and b.scenarioid = $node_scenarioid ";
   //print($listobject->querystring . "<br>");
   $listobject->performQuery();
   if ($listobject->numrows > 0) {
      $parentid = $listobject->getRecordValue(1,'elementid');
   } else {
      $parentid = -1;
   }
   print("$parentid\r\n");
   break;
   
   case 6:
   // get an elements name
   $varlist = "'" . implode("','", explode(",",$variables)) . "'";
   $listobject->querystring = "  select a.elemname from scen_model_element as a ";
   $listobject->querystring .= " where a.elementid = $elementid  ";
   //print($listobject->querystring . "<br>");
   $listobject->performQuery();
   if ($listobject->numrows > 0) {
      $elemname = $listobject->getRecordValue(1,'elemname');
   } else {
      $elemname = -1;
   }
   print("$elemname\r\n");
   break;
   
   case 7:
   // do a vahydro element query
   // Requires: querytype, scenarioid, variables
   // Optional: custom1, custom2, objectclass, params
   $known_vars = array();
   $known_vars['elemname'] = array('table' => 'scen_model_element');
   $known_vars['elementid'] = array('table' => 'scen_model_element');
   $known_vars['custom1'] = array('table' => 'scen_model_element');
   $known_vars['custom2'] = array('table' => 'scen_model_element');
   $known_vars['huc'] = array('table' => 'huc_va');
   $query_vars = array();
   $varlist = explode(",",$variables);
   foreach ($varlist as $thisvar) {
      if (in_array($thisvar, array_keys($known_vars))) {
         $query_vars[] = $thisvar;
      }
   }
   $paramlist = explode(",",$params);
   $known_queries = array();
   $tables = array('scen_model_element');
   $qv_formatted = array();
   foreach ($query_vars as $vname) {
      if (isset($known_vars[$vname])) {
         $qv_formatted[] = $known_vars[$vname]['table'] . '."' . $vname . '"';
         if (!in_array($known_vars[$vname]['table'], $tables)) {
            $tables[] = $known_vars[$vname]['table'];
         }
      } else {
         $qv_formatted[] = 'scen_model_element."' . $vname . '"';
      }
   }
   $varsql = implode(", ", $qv_formatted);
   
   $known_queries['cbp_major'] = array('where' => "( substring(scen_model_element.custom2, 1, 1) = '$params' )" );
   $known_queries['cbp_minor'] = array('where' => "( substring(scen_model_element.custom2, 1, 2) = '$params' )" );
   $known_queries['custom2'] = array('where' => "( scen_model_element.custom2 = '$params' )" );
   $known_queries['elementid'] = array('where' => "( scen_model_element.elementid in ( $params ) )" );
   $known_queries['huc8'] = array('table' => 'huc_va', 'where' => "( huc_va.huc = '$params' ) " );
   
   $wparms = array(" ( scenarioid = $scenarioid ) ");
   if (isset($known_queries[$querytype])) {
      $wparms[] = $known_queries[$querytype]['where'];
      if (isset($known_queries[$querytype]['table'])) {
         if (!in_array($known_queries[$querytype]['table'], $tables)) {
            $tables[] = $known_queries[$querytype]['table'];
         }
      }
   }
   foreach ($extras as $thiscol => $thisext) {
      if (count(explode(",", $thisext)) > 1) {
         $options = explode(",", $thisext);
         $wparms[] = "($thiscol in ( '" . implode("', '", $options) . "') )";
      } else {
         $wparms[] = "($thiscol = '$thisext')";
      }
   }
   //$listobject->querystring = "  select $varsql from " . implode(", ", $tables);
   $listobject->querystring = "  select $varsql ";
   $listobject->querystring .= "  from scen_model_element ";
   if ($querytype == 'huc8') {
      $listobject->querystring .= "  left outer join huc_va on (CASE WHEN scen_model_element.geomtype = 1 THEN contains(huc_va.the_geom, scen_model_element.point_geom) ";
      $listobject->querystring .= "WHEN scen_model_element.geomtype = 2 THEN (huc_va.the_geom && scen_model_element.line_geom) ";
      $listobject->querystring .= "WHEN scen_model_element.geomtype = 3 THEN contains(huc_va.the_geom, st_PointOnSurface(scen_model_element.poly_geom) ) ";
      $listobject->querystring .= "ELSE FALSE END) ";
   }
   $listobject->querystring .= " where " . implode(' AND ', $wparms);
   if ($nontidal) {
     $listobject->querystring .= " AND custom2 not like '%0000' ";
   }
   $listobject->querystring .= " group by  $varsql ";
   if ($debug) {
      print($listobject->querystring . "<br>");
   }
   
   $listobject->performQuery();
   if ($listobject->numrows > 0) {
      if ($header) {
         $header = implode($delimiter, array_keys($listobject->queryrecords[0]));
         print("$header\r\n");
      }
      foreach ($listobject->queryrecords as $thisrec) {
         $line = implode($delimiter, array_values($thisrec));
         print("$line\r\n");
         if ($debug) {
            print("<br>");
         }
      }
   }
   
   break;

   case 8:
   // get a wsp or vwuds withdrawal totals to watershed outlet, cumulative or local
   // elementid or custom2 can be used to get item
   // params = wsp or vwuds
   // querytype=cumulative or local
   // startdate and enddate are important for vwuds but we supply defaults
   
   if ($startdate == '') {
      $startdate = '2005-01-01';
   }
   if ($enddate == '') {
      $enddate = '2009-12-31';
   }
   if (!isset($querytype)) {
      $querytype = 'local';
   }
   if (!isset($scenarioid)) {
      $scenarioid = 37;
   }
   $ncnt = array('cova_ws_container', 'cova_ws_subnodal');
   if (function_exists('getCOVASegments')) {
      $wkt_segs = getCOVASegments($elementid, $ncnt, $querytype);
   } else {
      print("Cannot access getCOVASegments");
   }
   if ($debug) { print("getCOVASegments($elementid, $ncnt); = " . print_r($wkt_segs,1) . " <br>"); }
   $wkt = getMergedCOVAShape($scenarioid, $listobject, $wkt_segs);
   if ($debug) { print("getMergedCOVAShape($scenarioid, " . print_r($wkt_segs,1) . " ) <br>"); }

   switch ($params) {
      case 'vwuds':
      $results = getTotalAnnualSurfaceWithdrawalByWKT($vwuds_listobject, date('Y',strtotime($startdate)), date('Y',strtotime($enddate)), $wkt, $debug, 1, 0);
      // just make it conform
      foreach ($results['annual_records'] as $thisrec) {
         $total += $thisrec['total_mgd'];
      }
      $mean_wd = $total / count($results['annual_records']);
      $summary = array('elementid'=>$elementid, 'wd_mgd' => $mean_wd);
      $results['records'] = $results['annual_records'];
      break;
      
      case 'wsp':
      $results = getWSPWithdrawalByWKT($wsp_listobject, $wkt, $debug);
      $summary = $results;
      unset($summary['query']);
      unset($summary['message']);
      $summary['elementid'] = $elementid;
      break;
      
   }
   $query = $results['query'];
   if ($debug) { print("$query\n"); }
   if (count($summary) > 0) {
      $header = implode($delimiter, array_keys($summary));
      print("$header\r\n");
      $line = implode($delimiter, array_values($summary));
      print("$line\r\n");
   } else {
      print("No Results");
   }
   break;
   
  case 10:
  // multiple elements show the value of requested parameters as a text file
  $ld = '';
  $lineend = "\n";
  $ld = "\t";
  if (trim($variables) == '') {
    $varstring = '';
  } else {
    $varstring = "$ld$variables";
  }
  if ($debug) $lineend = "<br>";
  $output = implode($ld, array('elementid','elemname','custom1','custom2','varkey','varvalue'));
  print($output . $lineend);
  //print("Elementid = $elementid \n");
  $varar = explode(',',$variables);
  $els = explode(',', $elementid);
  foreach ($els as $elementid) {
    $loadres = loadModelElement($elementid, array(), 1);
    $output = '';
    if ($debug) print "object returned: " . print_r($loadres['record'],1). $lineend;
    if (is_object($loadres['object'])) {
      $thisobject = $loadres['object'];
      $elinfoitems = array(
        $elementid,
        $loadres['record']['elemname'],
        $loadres['record']['custom1'],
        $loadres['record']['custom2'],
      );
      $output = implode($ld, $elinfoitems);
      foreach ($varar as $thisvar) {
        if ($params == '') {
          //error_log("Calling getProp($thisvar, $view) \n");
          if ($debug) {
            print("Calling getProp($thisvar, $view) on elementid = $elementid". $lineend);
          }
          $thisval = $thisobject->getProp($thisvar, $view);
        } else {
          //error_log("Calling showElementInfo($thisvar, $params) \n");
          if ($debug) {
            print("Calling showElementInfo($thisvar, $params) ". $lineend);
          }
          $thisval = $thisobject->showElementInfo($thisvar, $params);
        }
        if (is_array($thisval)) {
           $thisval = json_encode($thisval);
        }
        print($output . $ld . $thisvar . $ld . $thisval . $lineend);
      }
    } else {
      if ($debug) print "No object returned: " . print_r((array)$loadres,1);
    }
  }
  break;

  case 11:
  error_log(print("listobject "));
  error_log(print_r((array)$listobject,1));
  $run_rec = getRunFile($listobject, $elementid, $runid, TRUE);
  error_log("Run Record for $elementid, $runid:". print_r($run_rec,1));
  unset($run_rec['run_summary']);
  // zip the file up
  $zipurl = $run_rec['remote_url'] . ".zip";
  $logfile = $run_rec['output_file'];
  $zipfile = $run_rec['output_file'] . ".zip";
  $cmd = "rm $zipfile; zip $zipfile $logfile -j";
  $run_rec['compressed'] = 0;
  $rez = shell_exec($cmd);
  if ($rez) {
    $run_rec['remote_url'] = $zipurl;
    $run_rec['compressed'] = 1;
  }
  error_log("get_modelData.php CMD: $cmd :: Result: $rez ");
  if (!empty($run_rec)) {
    $header = implode($delimiter, array_keys($run_rec));
    print("$header\r\n");
    $line = implode($delimiter, array_values($run_rec));
    print("$line\r\n");
  }
  break;

   default:
   $result = compareRunData($elementid, $runid, $variables, $startdate, $enddate, 1, $debug, array(), 'left outer', $date_format);
   $query = $result['query'];
   print("$query\n");
   if (count($result['records']) > 0) {
      $header = implode($delimiter, array_keys($result['records'][0]));
      print("$header\r\n");
      foreach ($result['records'] as $thisrec) {
         $line = implode($delimiter, array_values($thisrec));
         print("$line\r\n");
      }
   }
   break;

}
?>
