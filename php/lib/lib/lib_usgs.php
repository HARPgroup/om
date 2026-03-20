<?php

# lib_usgs - contains libraries to retrieve USGS station information

# Query Examples:

function setUSGSCodes() {

   # testing, this is not yet in use
   $usgscodes = array(
      '00060'=>array(
      'name'=>'Discharge, cubic feet per second',
      'shortname'=>'meancfs',
      'stats'=>'00003'
      ),
      '00065'=>array(
      'name'=>'Discharge, cubic feet per second',
      'shortname'=>'maxheight,minheight,meanheight',
      'stats'=>'00001,00002,00003'
      )
   );
   return $usgscodes;
}


function getGagesByWKT($listobject, $wktshape, $arealimit = -1, $debug = 0) {
   // $arealimit - what is the largest basin size that we will retrieve?

   $retvals = array();
   $retvals['debug'] = '';
   $listobject->querystring = "  select gid, objectid, plot_seq, station_nu, drainage_a, huc_8_digi, ";
   $listobject->querystring .= "     huc_6_digi, regionalba,river_basi, shape_leng, shape_area,  ";
   $listobject->querystring .= "     ( area2d(intersection(the_geom, geomFromText('$wktshape', 4326))) ";
   $listobject->querystring .= "     / area2d(geomFromText('$wktshape', 4326)) ) as over_pct  ";
   $listobject->querystring .= " from usgs_drainage_dd ";
   // right now we use the centroid(the_geom)) to estimate the center of the shape
   // so that we can find out which gages are within a given watershed.  This will only work well for 
   // headwater watersheds, we really need an outlet later, or better yet a "near outlet" laye
   $listobject->querystring .= " WHERE ";
   $listobject->querystring .= "   (";
   $listobject->querystring .= "      ( contains(geomFromText('$wktshape', 4326), centroid(the_geom)) ) ";
   $listobject->querystring .= "      OR ( contains(the_geom, centroid(geomFromText('$wktshape', 4326))) ) ";
   $listobject->querystring .= "      OR ( ";
   $listobject->querystring .= "         area2d(intersection(the_geom, geomFromText('$wktshape', 4326))) > ";
   // screen for sliver polygons throwing a false positive
   // if the overlap section is at least 10% of the base shape, we consider that they are overlapping
   $listobject->querystring .= "         0.1 * area2d(geomFromText('$wktshape', 4326)) ";
   $listobject->querystring .= "      ) ";
   $listobject->querystring .= "   ) ";
   $listobject->querystring .= " ORDER BY drainage_a ";
   $retvals['query'] .= " $listobject->querystring ; <br>";
   $listobject->performQuery();
   $retvals['records'] = $listobject->queryrecords;
   
   return $retvals;   
}

function getSitesHUC($dataitem, $huc, $debug) {
   $site_result = retrieveUSGSData('', '', $debug, '', '', 3, '', 'rdb', $dataitem, '', '', '1', "huc_cd=$huc");

   $recs = $site_result['row_array'];
   $sdel = '';
   $sitelist = '';
   foreach($recs as $thisrec) {
      $sitelist .= $sdel . $thisrec['site_no'];
      $sdel = ',';
   }
   return $sitelist;
}

# lat/lon bounding box
# nw_longitude_va=73.7508316040039&nw_latitude_va=37.2763977050781&se_longitude_va=79.9869003295898&se_latitude_va=39.6018333435059&coordinate_format=decimal_degrees

function retrieveUSGSData($siteno, $period='', $debug=0, $start_date='', $end_date='', $datatype=1, $nwisurl='', $format='rdb', $params='00060', $state='', $siteid = '', $ddnu='1', $extraoptions = '') {
   # returns an array containing data parsed from the requested station file
   # if more than one station is needed, they may be requested simultaneously
   # by submitting the $siteno variable in csv format (ex: 02037500,02016500 )
   $debuginfo = '';
   # this works, but needs more thought to be useful as a model
   $dataformats = array(
      '2'=>array(
         'siteno'=>'site_no=%8s',
         'params'=>'por_%8s_2=%8s,%5s,2'
      )
   );

   if (strlen($ddnu) == 0) {
      $ddnu = '1';
   }
   #https://waterdata.usgs.gov/nwis/dvstat?&site_no=02037500&agency_cd=USGS&por_02037500_2=189680,00060,2&stat_cds=mean_va&referred_module=sw&format=rdb
   $vardel = '?';

   $uri = "https://waterdata.usgs.gov/";
   if (strlen($state) > 0) {
      $uri .= $state . '/';
   }
   $uri .= "nwis/";

   switch ($datatype) {
      case 0:
      # realtime
      $uri .= "uv";
      break;

      case 1:
      # daily mean
      $uri .= "dv";
      break;

      case 2:
      # daily statistics
      $uri .= "dvstat";
      if (!(strlen($siteno) > 0) ) {
         $debuginfo .= "You must choose a site number.<br>";
         $outdata = array();
         $outdata['uri'] = $uri;
         $outdata['columns'] = $cols;
         $outdata['numrecs'] = 0;
         $outdata['row_array'] = array();
         $outdata['row_tab'] = array();
         $outdata['debug'] = $debuginfo;
         return $outdata;
      }
      if (count(explode(',', $siteno)) > 1) {
         $debuginfo .= "Statistics queries may only have one site per query.<br>";
         $outdata = array();
         $outdata['uri'] = $uri;
         $outdata['columns'] = $cols;
         $outdata['numrecs'] = 0;
         $outdata['row_array'] = array();
         $outdata['row_tab'] = array();
         $outdata['debug'] = $debuginfo;
         return $outdata;
      }
      $uri .= $vardel . "por_" . $siteno . "_" . $ddnu . "=" . $siteid . "," . $params . "," . $ddnu . "&stat_cds=mean_va";
      $vardel = '&';
      # this works, but needs more thought to be useful as a model
      #$test = sprintf($dataformats['2']['siteno'],$siteno, $siteno, $params);
      #print("The format would be: $uri?$test ");
      #die;
      break;

      case 3:
      # flow station inventory
      $uri .= "inventory";
      $uri .= $vardel . "data_type=discharge&column_name=agency_cd&column_name=site_no&column_name=site_id&column_name=dec_lat_va&column_name=dec_long_va&column_name=state_cd&column_name=alt_va&column_name=dd_nu&column_name=drain_area_va&column_name=contrib_drain_area_va";
      $vardel = '&';
      break;

      case 4:
      # groundwater station inventory
      $uri .= "inventory";
      $uri .= $vardel . "data_type=groundwater&column_name=agency_cd&column_name=site_no&column_name=site_id&column_name=dec_lat_va&column_name=dec_long_va&column_name=state_cd&column_name=alt_va&column_name=dd_nu";
      $vardel = '&';
      break;

      default:
      $uri .= "dv";
      break;
   }

   if (strlen($nwisurl) > 0 ) {
      $uri = $nwisurl;
   }

   if (! ( (strlen($siteno) > 0) or ($datatype > 2) ) ) {
      $debuginfo .= "You must choose a site number.<br>";
      $outdata = array();
      $outdata['uri'] = $uri;
      $outdata['columns'] = $cols;
      $outdata['numrecs'] = 0;
      $outdata['row_array'] = array();
      $outdata['row_tab'] = array();
      $outdata['debug'] = $debuginfo;
      return $outdata;
   }

   if (strlen($siteno) > 0) {
      $uri .= $vardel . "site_no=$siteno";
      $vardel = '&';
   }

   foreach (explode(',', $params) as $thisparam) {
      $uri .= $vardel . "cb_" . $thisparam . '=on';
      $vardel = '&';
   }

   if (strlen($period) > 0) {
      $uri .= $vardel . "period=$period";
      $vardel = '&';
   }

   if (strlen($start_date) > 0) {
      $uri .= $vardel . "begin_date=$start_date";
      $vardel = '&';
   }

   if (strlen($end_date) > 0) {
      $uri .= $vardel . "end_date=$end_date";
      $vardel = '&';
   }

   if (strlen($extraoptions) > 0) {
      $uri .= $vardel . $extraoptions;
      $vardel = '&';
   }

   if (strlen($format) > 0) {
      $uri .= $vardel . "format=$format";
      $vardel = '&';
   } else {
      $uri .= $vardel . "format=rdb";
      $vardel = '&';
   }

   $outarr = array();
   $rowraw = array();
   $debuginfo .= "URL: $uri<br>";

   /*
   # if you do this through a proxy server, this is how.
   # this does not seem to apply to the copy() command, so I opt to use that for as long as this works
   # for some reason, file_get_contents needs the proxy, but copy() does not?  This is of early November 2008,
   # presumably as a result of new network settings and proxy info that was announced by the DEQHelpDesk
   $proxy = 'tcp://10.193.64.33:80';
   $uid = '';
   $pw = '';
   if (strlen($proxy) > 0) {
      $aContext = array(
         'http' => array(
            'proxy' => $proxy, // This needs to be the server and the port of the NTLM Authentication Proxy Server.
            'request_fulluri' => True,
            'userid'=>$uid,
            'password'=>$pw
         )
      );
      $cxContext = stream_context_create($aContext);

      // Now all file stream functions can use this context.

      $gagedata = file_get_contents($uri, False, $cxContext);
   } else {
      $gagedata = file_get_contents($uri);
   }
   */
   # this is only needed if you have a crappy network
   #copy($uri, '/tmp/scratch.txt');
   #$gagedata = file_get_contents('/tmp/scratch.txt');
   $gagedata = file_get_contents($uri);
   $gagelines = explode("\n", $gagedata);
   //$gagelines = explode("\r\n", $gagedata);
   $i = 0; # counter for parsed lines
   $debuginfo .= "Retrieved " . count($gagelines) . " lines for NWIS <br>";
   $debuginfo .= "Excerpt " . substr($gagedata,0,255) . " <br>";
   #print("<b>Retrieving:</b> $uri<br>");

   foreach ($gagelines as $thisline) {
      $firstchar = substr(ltrim($thisline),0,1);
      $debuginfo .= "<b>Debug:</b> $firstchar <br>";
      if ($firstchar <> '#') {
         # not a comment line - is it a data line?
         # expect a column header line (e.g. agency_cd   site_no  datetime 13_00065 ...)
         # followed by a column length descriptor (e.g. 5s  15s   16s   14s   14s )
         $i++;
      }
      if ($i == 1) {
         # this is the header line
         $cols = explode("\t", $thisline);
      }
      $debuginfo .= "<b>Debug</b>: $thisline <br>";
      if ($i > 2) {
         array_push($rowraw, $thisline);
         # this is a good dataline, past the comments, header and format lines
         if (ltrim($thisline) <> '') {
            $thesecols = explode("\t", $thisline);
            $j = 0;
            reset($cols);
            $thisrowarr = array();
            foreach ($cols as $thiscol) {
               # strips commas out of numbers
               $thisrowarr[ltrim(rtrim($thiscol))] = str_replace(',','',$thesecols[$j]);
               $j++;
            }
            array_push($outarr, $thisrowarr);
            if ($debug) {
               $debuginfo .= print_r($thisrowarr,1) . "<br>";
            }
         }
      }
   }
   $debuginfo .= "Parsed $i data lines for NWIS <br>";
   $outdata = array();
   $outdata['uri'] = $uri;
   $outdata['columns'] = $cols;
   $outdata['numrecs'] = $i - 2;
   $outdata['row_array'] = $outarr;
   $outdata['row_tab'] = $rowraw;
   $outdata['debug'] = $debuginfo;
   return $outdata;
}

?>
