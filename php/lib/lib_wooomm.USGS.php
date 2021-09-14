<?php

// ********************************************* //
// ***      lib_wooomm.USGS.php                *** //

if (!class_exists('modelSubObject')) {
   include_once('./lib_hydrology.php');
}
if (!class_exists('Equation')) {
   include_once('./lib_equation2.php');
}

if (!function_exists('retrieveUSGSData')) {
   include_once('./lib_usgs.php');
}

class USGSGageObject extends timeSeriesInput {
   var $maxflow = 0.0; /* maximum flow during period */
   var $startdate = '';
   var $enddate = '';
   var $data_periods = array();
   var $flow_begin = '';
   var $flow_end = '';
   var $laststaid = '';
   var $staid = '';
   var $siteid = '';
   var $force_refresh = 0; # whether or not to query the station for record dates each time this object is initialized
   var $period = 7;
   var $ddnu = '';
   var $area = -1.0; # drainage area in sq. miles
   var $stationstats = array();
   var $recformat = 'rdb';
   var $rectype = 1; # 0 - realtime, 1 - daily mean, 2 - daily stats, 3 - station inventory
   var $dataitems = '00060,00010';
   var $uri = ''; # the URI used to retrieve NWIS data
   var $code_name = array();
   var $code_suffix = array();
   var $datadefaults = array();
   var $result = 0;
   var $sitetype = 1; # 1 - stream gage, 2 - groundwater, 3 - reservoir level
   var $stat_dbtblname = '';
   // widget to obtain station information
//   var $xml_station_info_url = "http://deq1.bse.vt.edu/wooommdev/remote/xml_usgs_basin.php";
   var $xml_station_info_url = "http://deq2.bse.vt.edu/wooommdev/remote/xml_usgs_basin.php";
   var $usgsdb = -1;
   var $usgs_host = 'gradlab4.bse.vt.edu';
   var $usgs_port = 5432;
   var $usgs_dbname = 'va_hydro';
   var $usgs_username = 'usgs_ro';
   var $usgs_password = '@ustin_CL';

   function setState() {
      parent::setState();
      $this->state['Qout'] = 0.0;
      $this->state['Temp'] = 0.0;
      $this->state['Qinches'] = 0.0;
      $this->state['Qfps'] = 0.0;
      $this->state['area'] = $this->area;
      
      $sitecodes = array(
         1=>array('00060'=>'Qout', '00010'=>'Temp', '00095'=>'specific_cond','00045'=>'precip'),
         2=>array('72019'=>'tabledepth','00045'=>'precip'),
         3=>array('00062'=>'stage','00045'=>'precip')
      );
      # when various codes are returned, a specific suffix can be used to obtain the exact desired measurement
      $codesuffixes = array(
         1=>array('00060'=>'', '00010'=>'','00045'=>'','00095'=>''),
         2=>array('72019'=>'00001'),
         3=>array('00062'=>'','00045'=>'','00095'=>'')
      );
      $datadefaults = array(
         'Qout'=>0.0,
         'Temp'=>0.0,
         'specific_cond'=>0.0,
         'precip'=>0.0,
         'stage'=>0.0
      );
      $this->code_name = $sitecodes[$this->sitetype];
      $this->code_suffix = $codesuffixes[$this->sitetype];
      $this->dataitems = join(',', array_keys($this->code_name));
      $this->datadefaults = $datadefaults;
      #$this->getStationInfo();
   }

   function wake() {
      if ($this->debug) {
         $this->logDebug("Waking " . $this->name . " at " . date('r'));
      }
      parent::wake();
      if ($this->debug) {
         $this->logDebug("Finished waking " . $this->name . " at " . date('r'));
      }
      
      if (strlen(rtrim(ltrim($this->area))) == 0) {
         $this->area = -1;
      }

      if ($this->area < 0) {
         # get the station data, do it once
         $this->forcerefresh = 1;
         $this->getStationInfo();
      }
   }
       
   function sleep() {
      $this->usgsdb = -1;
      parent::sleep();
   }
   
   function step() {
      parent::step();
      if ($this->calc_pct) {
         $this->calcCurrentPct();
      }
   }
   
   function calcCurrentPct() {
      if (isset($this->processors['exceedence_monthly'])) {
         $statproc = $this->processors['exceedence_monthly'];
         if (is_object($statproc)) {
            switch ($this->sitetype) {
               case 1:
               $varname = 'Qout';
               break;
               
               case 2:
               $varname = 'tabledepth';
               break;
               
               default:
               $varname = 'Qout';
               break;
            }
            
            if (method_exists($statproc, 'evaluateMatrix')) {
               $result = $statproc->evaluateMatrix($this->state['month'], $this->state[$varname]);
            }
         }
      }
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();
      # add specific USGS parameter names
      foreach (array_values($this->code_name) as $thiscode) {
         array_push($publix, $thiscode);
      }

      return $publix;
   }

   function setProp($propname, $propvalue, $view='') {
      # checks to see if we should stow the value for laststaid
      # this is used to eliminate redundant calls to query the station basic data
      parent::setProp($propname, $propvalue);
      if ($propname == 'staid') {
         # refresh station info
         #$this->getStationInfo();
      }
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      // added RWB thisdate to statenums on 1/30/2014
      $statenums = array('area', 'Qout', 'Temp', 'Qinches', 'Qfps', 'precip', 'tabledepth');
      foreach ($statenums as $thiscol) {
         $this->dbcolumntypes[$thiscol] = 'float8';
         $this->data_cols[] = $thiscol;
      }
      if (!isset($this->data_cols['thisdate'])) {
         $this->data_cols[] = 'thisdate';
      }
   }
   
   function create() {
      if (strlen($this->staid) > 0 ) {
         $this->getStationStats();
         $this->getStationInfo();
      }
   }
   
   function addStatTable($thistab) {
      if ($this->debug) {
         $this->logDebug("Received request to add stat array: " . print_r($thistab,1) . " <br>");
      }
      $assoc = array();
      $keys = array();
      $i = 0;
      foreach ($thistab as $mo => $statpairs) {
         $row = array();
         $row['month'] = $mo;
         $row['0.0'] = 0.0;
         foreach ($statpairs as $thisstat => $thisval) {
            preg_match('/[0-9]+/',$thisstat,$num);
            if ($this->debug) {
               $this->logDebug($thisstat . " : " . floatval($num[0])/100.0 . " = $thisval\n");
            }
            $statkey = floatval($num[0])/100.0;
            //$statkey = floatval($num[0]);
            $row["$statkey"] = $thisval;
         }
         $assoc[$i] = $row;
         $i++;
      }
      $this->setStatTable($assoc);
      
   }
   
   function setStatTable($formatted) {
      
      // hiastoric landuse subcomponent to allow users access to model simulated land uses
      $mostat = new dataMatrix;
      $mostat->listobject = $this->listobject;
      $mostat->name = 'stat_table';
      $mostat->defaultval = 1.0;
      $mostat->wake();
      $mostat->valuetype = 2; // 2 column lookup (col & row)
      $mostat->keycol1 = 'month'; // key for 1st lookup variable
      $mostat->lutype1 = 0; // lookup type - exact match for month
      $mostat->keycol2 = 'Qout'; // key for 2nd lookup variable
      $mostat->lutype2 = 3; // lookup type - interpolated for return period value
      // add a row for the header line
      $mostat->assocArrayToMatrix($formatted);
      if ($this->debug) {
         $this->logDebug("Trying to add historic land use sub-component matrix with values: " . print_r($mostat->matrix,1) . " <br>");
      }
      $this->addOperator('exceedence_monthly', $mostat, 0);
      if ($this->debug) {
         $this->logDebug("Processors on this object: " . print_r(array_keys($this->processors),1) . " <br>");
      }
   }


   function init() {
      parent::init();
      // initialize dates with dummy variables
      if ($this->startdate == '') {
         $this->startdate = date('Y-m-d');
      }
      if ($this->enddate == '') {
         $this->enddate = date('Y-m-d');
      }
      $this->logDebug("Debug Mode for $this->name : $this->debug <br> ");
      
      # set a name for the temp table that will not hose the db
      $targ = array(' ',':','-','.');
      $repl = array('_', '_', '_', '_');
      $this->tblprefix = str_replace($targ, $repl, 'tmp_usgs' . $this->staid . '_' . "$this->componentid" . "_");
      $this->dbtblname = $this->tblprefix . 'datalog';
      $this->stat_dbtblname = $this->tblprefix . 'stationstats';
      
      $this->orderOperations();
      if (strlen(trim($this->staid)) > 0) {
         $this->getStationInfo();
         $this->retrieveData();
      }
      ksort($this->tsvalues);
      if ( (count($this->tsvalues) > $this->max_memory_values) and ($this->max_memory_values > 0)) {
         $this->setDBCacheName();
         $this->tsvalues2listobject();
         $this->getCurrentDataSlice();
      }

   }


   function evaluate() {
      //$this->step();
      $this->result = $this->state['Qout'];

   }

   function getStationInfo() {
      if ($this->debug) {
         $this->logDebug("Obtaining Physical Data for station: $this->staid <br>");
      }
      
      //error_log("$this->name Retrieve Data Module PRE: Watershed Area object prop set to " . $this->area . " and state variable area set to " . $this->state['area']);
      
      if (!strlen($this->staid) > 0 ) {
         $this->logError("$this->name gage $this->staid is not valid <br>\n");
         $this->ts_enabled = 0;
      } else {
         if ( ($this->laststaid <> $this->staid) or ($this->forcerefresh)) {
            $this->logDebug("Refreshing station info for " . $this->staid . " at " . date('r') . "<br>");
            # only try to retrieve station info if staid is set, otherwise, application will retrieve info for all
            # USGS gages, which takes a long time....
            $exopt = '';
            switch ($this->sitetype) {
               case 1:
                  $exopt .= 'referred_module=sw';
               break;
               
               case 2:
                  $exopt .= 'referred_module=gw';
               break;
               
               case 3:
                  $exopt .= 'referred_module=sw';
               break;
            }
            $usgs_result = retrieveUSGSData($this->staid, $this->period, $this->debug, '', '', 3, '', '', '', $state='', '', 1, $exopt);
            $sitedata = $usgs_result['row_array'][0];
            if (isset($sitedata['discharge_begin_date'])) {
               $this->flow_begin = $sitedata['discharge_begin_date'];
            }
            if (isset($sitedata['discharge_end_date'])) {
               $this->flow_end = $sitedata['discharge_end_date'];
            }
            if (isset($sitedata['drain_area_va'])) {
               $this->area = $sitedata['drain_area_va'];
            }
            if (isset($sitedata['site_id'])) {
               $this->siteid = $sitedata['site_id'];
            }
            if (isset($sitedata['ddnu'])) {
               $this->ddnu = $sitedata['ddnu'];
            }
            $this->logDebug("Gage " . $this->staid . ", Data = " . $this->flow_begin . " to " . $this->flow_end . ', area: ' . $this->area);
         }
         $this->laststaid = $this->staid;
      }
      //error_log("Area set to : $this->area ");
      if (strlen(rtrim(ltrim($this->area))) == 0) {
         $this->area = -1;
         $this->state['area'] = 0;
      }
      
      if ($this->area <= 0) {
         $this->state['area'] = 0;
      } else {
         $this->state['area'] = $this->area;
      }
      $this->logDebug("Area set to : $this->area ");
      
      $this->logDebug("$this->name Retrieve Data Module post: Watershed Area object prop set to " . $this->area . " and state variable area set to " . $this->state['area']);
      
      // look for shape information in USGS database
      $this->getStationGeometry();

   }
   
   function getStationGeometry() {
      error_log("Looking for USGS Basin geometry for station $this->staid");
      $ctx = stream_context_create(array(
          'http' => array(
              'timeout' => 2400
              )
          )
      );
      $feed_url = $this->xml_station_info_url . "?gage_id=$this->staid";
      $xmlfile = file_get_contents($feed_url, 0, $ctx);
      $this->logDebug("Fetching Feed:  $feed_url <br>\n");
      
      
      // avoiding this XML reader right now, in favor of a raw text geometry coming back
      if (class_exists('jiffyXmlReader')) {
         if ($this->debug) {
            $this->logDebug("Fetching Feed:  $feed_url <br>\n");
         }
         $rawlength = strlen($xmlfile);
         //error_log("USGS XML File Length = $rawlength");
         $xml = simplexml_load_string($xmlfile);
         $linklist = $xml->channel->item;
         
         $linkobj = (array)$linklist;
         $this->the_geom = $linkobj['the_geom'];
         $this->logDebug("USGS Basin Info Columns: " . $linkobj['gage_id'] . " " . $linkobj['drainage_area']);
         $this->logDebug("USGS Basin geom: " . substr($this->the_geom,0,128));
         $this->logError("USGS Basin Info Columns: " . $linkobj['gage_id'] . " " . $linkobj['drainage_area']);
      } else {
         $this->logError("XMLReader jiffyXMLReader cannot be found<br>\n");
         $this->logDebug("XMLReader jiffyXMLReader cannot be found<br>\n");
      }
      
      error_log("USGS Basin geom: " . substr($this->the_geom,0,128));
   }
   
   function getStationStats() {
      switch($this->sitetype) {
      
         case 1:
         // streamflows
         $this->getStreamStationStats();
         $this->addStatTable($this->stationstats_lookup);
         break;
         
         case 2:
         // groundwater
         $this->getGWStationStats();
         $this->setStatTable($this->stationstats_lookup);
         break;
      
         default:
         // streamflows
         $this->getStreamStationStats();
         $this->addStatTable($this->stationstats_lookup);
         break;
      }
   }
   
   function getGWStationStats() {
      if (!is_object($this->usgsdb)) {
         //$this->reportstring .= "Creating USGS DB Object<br>";
         $this->usgsdb = new pgsql_QueryObject;
         $this->usgsdb->dbconn = pg_connect("host=$this->usgs_host port=$this->usgs_port dbname=$this->usgs_dbname user=$this->usgs_username password=$this->usgs_password");
         $this->usgsdb->show = 0;
         //$this->reportstring .= "USGS DB Object created<br>";
      }
      $this->stationstats_lookup = array();
      $this->usgsdb->querystring = "  select month, ";
      $this->usgsdb->querystring .= " min_va as \"0.0\", ";
      $this->usgsdb->querystring .= " p05_va as \"0.05\", ";
      $this->usgsdb->querystring .= " p10_va as \"0.10\", ";
      $this->usgsdb->querystring .= " p25_va as \"0.25\", ";
      $this->usgsdb->querystring .= " p50_va as \"0.50\", ";
      $this->usgsdb->querystring .= " p75_va as \"0.75\", ";
      $this->usgsdb->querystring .= " p90_va as \"0.90\", ";
      $this->usgsdb->querystring .= " p95_va as \"0.95\" ";
      $this->usgsdb->querystring .= " from site_monthly_stats ";
      $this->usgsdb->querystring .= " where site_no = '$this->staid' ";
      $this->usgsdb->performQuery();
      if ($this->debug) {
         $this->logDebug("Query Error Status: " . $this->usgsdb->error . "<br>\n");
         $this->usgsdb->show = 0;
         $this->usgsdb->showList();
         $this->logDebug("Query Result: " . $this->usgsdb->outstring . "<br>\n");
      }
      $this->stationstats_lookup = $this->usgsdb->queryrecords;
      
      return;
   }
   
   function getStreamStationStats() {
      if ($this->debug) {
         $this->logDebug("Obtaining Stream Flow Statistics for station: $this->staid <br>");
      }
      
      # gets historical daily flow exceedence values
      $ddnu = $this->ddnu;
      
      if ($ddnu == '') {
         $ddnu = 1;
      }
      
      $site_result = retrieveUSGSData($this->staid, '', $this->debug, '', '', 2, '', '', '00060', 'va', $this->siteid, $ddnu);
      $gagedata = $site_result['row_array'];
      $debuginfo = $site_result['debuginfo'];
      $uri = $site_result['uri'];
      if ($this->debug) {
         $this->logDebug("$debuginfo<br>");
      }

      $numstats = count($gagedata);
      //if ($this->debug) {
         $this->logDebug("URI: $uri<br>");
         $this->logDebug("$numstats records returned with ddnu = $ddnu<br>");
      //}

      if ($numstats == 0) {
         # try ddnu = 2
         $ddnu = 2;
         $site_result = retrieveUSGSData($this->staid, '', $this->debug, '', '', 2, '', '', '00060', 'va', $this->siteid, $ddnu);
         $gagedata = $site_result['row_array'];
         $numstats = count($gagedata);
         $uri = $site_result['uri'];
         //if ($this->debug) {
            $this->logDebug("URI: $uri<br>");
            $this->logDebug("$numstats records returned with ddnu = $ddnu<br>");
         //}
      }

      if ($this->debug) {
         $this->logDebug("Inserting $numstats new daily flow stats for $siteno (ID: $siteid).<br>");
      }
      
      $statcols = array(
         'agency_cd'=>'varchar(8)',
         'site_no'=>'varchar(12)',
         'parameter_cd'=>'varchar(8)',
         'dd_nu'=>'varchar(4)',
         'month_nu'=>'int4',
         'day_nu'=>'int4',
         'begin_yr'=>'int8',
         'end_yr'=>'int8',
         'count_nu'=>'int8',
         'max_va_yr'=>'int8',
         'max_va'=>'float8',
         'min_va_yr'=>'int8',
         'min_va'=>'float8',
         'mean_va'=>'float8',
         'p05_va'=>'float8',
         'p10_va'=>'float8',
         'p20_va'=>'float8',
         'p25_va'=>'float8',
         'p50_va'=>'float8',
         'p75_va'=>'float8',
         'p80_va'=>'float8',
         'p90_va'=>'float8',
         'p95_va'=>'float8'
      );
      $columns = array_keys($gagedate[0]);
      
      $this->listobject->array2tmpTable($gagedata, $this->stat_dbtblname, $columns, $statcols, 1, $this->bufferlog);
      if ($this->debug) {
         $this->logDebug("Getting schema name for temp table.<br>");
      }
      $stats = array('p05_va','p10_va','p20_va','p25_va','p50_va','p75_va','p80_va','p90_va', 'p95_va');
      $this->stationstats = $gagedata;
      $this->stationstats_lookup = array();
      foreach ($gagedata as $thisdata) {
         $lu = $thisdata['month_nu'];
         if (isset($thisdata['dat_nu'])) {
            $lu .= '-' . $thisdata['dat_nu'];
         }
         foreach ($stats as $sn) {
            $this->stationstats_lookup[$lu][$sn] = $thisdata[$sn];
         }
      }
      
      return;

   }

   function retrieveData() {
      if ($this->debug) {
         $this->logDebug("Obtaining Physical Data for station: $staid <br>");
      }
      if (!strlen($this->staid) > 0 ) {
         $this->logError("$this->name gage $this->staid is not valid <br>\n");
         $this->ts_enabled = 0;
      } else {
         $this->ts_enabled = 1;
         $usgs_result = retrieveUSGSData($this->staid, $this->period, $this->debug, '', '', 3, '', '', '');
         $sitedata = $usgs_result['row_array'][0];
         //error_log($usgs_result['uri']);
         #$this->logDebug($sitedata);
         if (isset($sitedata['drain_area_va'])) {
            $dav = $sitedata['drain_area_va'];
            $this->logDebug("<br>Area = $dav<br>");
            $this->state['area'] = $dav;
         } else {
            $dav = $this->area;
         } 

         # if not given valid date ranges, will default to the period (default value = 7 days

         # gets daily flow values for indicated period
         if (is_object($this->timer)) {
            # set the timer info to be our start and end date
            #$this->logDebug($this->timer);
            $sdate = new DateTime($this->timer->thistime->format('Y-m-d'));
            $ndate = new DateTime($this->timer->endtime->format('Y-m-d'));
            $sdate->modify("-1 days");
            $ndate->modify("+1 days");
            $this->startdate = $sdate->format('Y-m-d');
            $this->enddate = $ndate->format('Y-m-d');
         }
         if ($this->debug) {
            $this->logDebug("Obtaining Flow Data for station: $this->staid $this->startdate to $this->enddate<br>");
         }
         if ( (strlen($this->startdate) > 0) and (strlen($this->enddate) > 0) ) {
            # assume date range is valid
            $this->period = '';
         }
         $site_result = retrieveUSGSData($this->staid, $this->period, $this->debug, $this->startdate, $this->enddate, $this->rectype, '', $this->recformat, $this->dataitems);
         $gagedata = $site_result['row_array'];
         if ( (!isset($gagedata[0]['site_no'])) and ($this->rectype == 0) ) {
            // try to get the daily data for this site if the hourly failed (only a short time period of hourly is currently avail on NWIS)
            $this->logError("Hourly data retrieval for $this->staid, $this->startdate, $this->enddate failed - trying daily retrieval <br>\n");
            $site_result = retrieveUSGSData($this->staid, $this->period, $this->debug, $this->startdate, $this->enddate, 1, '', $this->recformat, $this->dataitems);
            $gagedata = $site_result['row_array'];
         }
         $this->uri = $site_result['uri'];
         error_log("USGS URL: $this->uri ");
         if ($this->debug) {
            $this->logDebug("Info returned from USGS retrieval routine <br>");
            $this->logDebug($site_result['debug']);
         }
         $thisno = $gagedata[0]['site_no'];
         #$this->logDebug($site_result['uri'] . "<br>");
         if ($this->debug) {
            $this->logDebug("<br><b>Adding retrieved data to time series array.</b><br>");
         }
         if (!isset($gagedata[0]['site_no'])) {
            # we came up empty here, so let's insert a single zero value for all items
            foreach (explode(',', $this->dataitems) as $dataitem) {
               $dataname = $this->code_name[$dataitem];
               $gagedata[0][$dataname] = $this->datadefaults[$dataname];
            }
            if ($this->debug) {
               $this->logDebug("No data retrieved, using default settings : " . print_r($gagedata[0],1) . "<br>");
            }
         }
         //error_log(print_r($gagedata[0],1));
         foreach ($gagedata as $thisdata) {
            if ($this->debug) {
               $this->logDebug("Adding record: " . print_r($thisdata,1) . "<br>");
            }
            $thisdate = new DateTime($thisdata['datetime']);
            $ts = $thisdate->format('r');
            $uts = $thisdate->format('U');
            $thisflag = '';
            # default to missing
            $thisval = 0.0;
            foreach (explode(',', $this->dataitems) as $dataitem) {
               $suffix = $this->code_suffix[$dataitem];

               foreach (array_keys($thisdata) as $thiscol) {
                  if (substr_count($thiscol, $dataitem)) {
                     # this is a flow related column, check if it is a flag or data
                     if (!substr_count($thiscol, 'cd')) {
                        # check for proper suffix
                        if ($this->debug) {
                           $this->logDebug("Searching for $dataitem" . "_$suffix" . " in $thiscol <br>");
                           $c = substr_count($thiscol, $dataitem . "_$suffix");
                        }
                        if ( (substr_count($thiscol, $dataitem . "_$suffix")) or ( ($this->rectype == 0) and (substr_count($thiscol, $dataitem)) ) ) {
                           # must be a valid value
                           if ($thisdata[$thiscol] <> '') {
                              $thisval = $thisdata[$thiscol];
                           } else {
                              $thisval = '0.0';
                           }
                           if ($this->debug) {
                              $this->logDebug("Found $dataitem" . "_$suffix" . " = $thisval <br>");
                           }
                        }
                     }
                  }
               }
               $dataname = $this->code_name[$dataitem];
               #$this->logDebug("<br>$dataitem - $dataname <br>");
               #$this->logDebug($this->code_name);
               # multiply by area factor to adjust for area factor at inlet
               $this->addValue($ts, $dataname, floatval($thisval));
               if ($dataname == 'Qout') {
                  if ($this->debug) {
                     $this->logDebug("$ts, $dataname: " . floatval($thisval) . "<br>\n");
                  }
                  # add a watershed inch conversion if area > 0.0
                  if ($dav > 0) {
                     $this->addValue($ts, 'Qinches', (floatval($thisval) * 0.9917 * 0.0015625 * (1.0/$dav) * 24.0) );
                     // watershed ft/sec
                     $this->addValue($ts, 'Qfps', (floatval($thisval) / ( 640.0 * 43560.0 * $dav)) );
                  }
                  if ($thisval > $this->maxflow) {
                     $this->maxflow = $thisval;
                  }
               }
            }
            $this->addValue($ts, 'timestamp', $uts);
            #$this->addValue($ts, 'thisdate', $thisdate->format('m-d-Y'));
            $this->addValue($ts, 'thisdate', $thisdate->format('Y-m-d'));
            if ($this->debug) {
               $this->logDebug("Added ");
               $this->logDebug($this->tsvalues[$uts]);
               $this->logDebug("<br>");
            }
         }
      }
   }
}

class USGSGageSubComp extends USGSGageObject {
   var $wvars = 0;

   function wake() {
      parent::wake();
      $this->setSingleDataColumnType('Qunit', 'float8', 0);
   }



   // *************************************************
   // BEGIN - Special Parent Variable Setting Interface
   // *************************************************
   function setState() {
      parent::setState();
      $this->rvars = array();
      $this->wvars = array('Qout', 'Qunit','area');
      
      $this->initOnParent();
   }

   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      foreach ($this->wvars as $thisvar) {
         if ($this->debug) {
            $this->logDebug("Setting $this->name" . "_" . "$thisvar to type float8 on parent.<br>\n");
            error_log("Setting $this->name" . "_" . "$thisvar to type float8 on parent.<br>\n");
         }
         if (is_object($this->parentobject)) {
            if (method_exists($this->parentobject, 'setSingleDataColumnType')) {
               $dval = $this->getProp($thisvar);
               if (isset($this->dbcolumntypes[$thisvar])) {
                  $this->parentobject->setSingleDataColumnType($this->name . "_" . $thisvar, $this->dbcolumntypes[$thisvar], $dval);
               } else {
                  $this->parentobject->setSingleDataColumnType($this->name . "_" . $thisvar, 'float8', $dval);
               }
            }
         }
         $this->vars[] = $this->name . "_" . $thisvar;
      }
   }
   
   // *************************************************
   // END - Special Parent Variable Setting Interface
   // *************************************************
   
   function postStep() {
      // set the Qunit variable
      //error_log("Setting Qunit as " . $this->state['Qout'] . '/' . $this->state['area'] );
      if ( ($this->state['area'] > 0) and !isset($this->processors['Qunit'])) {
         $this->state['Qunit'] = $this->state['Qout'] / $this->state['area'];
      }
      $this->writeToParent();
      parent::postStep();
   }
       
   function sleep() {
      $this->wvars = 0;
      parent::sleep();
   }
   
   function logState() {
   
      // logging will be done by the parent, so no need to waste memory and time with this
   
   }
   
}
   
class USGSArima extends modelSubObject {
   // needs to store the date, select the temporal resolution of the data storage
   // resolution options:
   // year, month, day, hour
   // format the date according to the desired resolution
   
   // this is a general purpose class for a simple flow-by
   // other, more complicated flow-bys will inherit this class
   
   var $var_prefix = 'q';
   var $q_var = 'Qout';
   var $flow_calc = 0.0;
   var $arima_eqn = 0.0;
   var $name = 'Qarima';
   var $num_vars = 6;
   var $init_vals = 0;
   var $states = array();
   var $lastday = '';

   function showEditForm($formname, $disabled=0) {
      if (is_object($this->listobject)) {

         $returnInfo = array();
         $returnInfo['name'] = $this->name;
         $returnInfo['description'] = $this->description;
         $returnInfo['debug'] = '';
         $returnInfo['elemtype'] = get_class($this);
         $returnInfo['innerHTML'] = '';

         //$props = (array)$this;
         $innerHTML = '';

         # set up div to contain each seperate multi-parameter block
         $aset = $this->listobject->adminsetuparray[get_class($this)];
         foreach (array_keys($aset['column info']) as $tc) {
            $props[$tc] = $this->getProp($tc);
         }
         $formatted = showFormVars($this->listobject,$props,$aset,0, 1, 0, 0, 1, 0, -1, NULL, 1);
         
         $innerHTML .= "<table><tr>";
         $innerHTML .= $this->showFormHeader($formatted,$formname);
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormBody($formatted,$formname);
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormFooter($formatted,$formname);
         $innerHTML .= "</tr></table>";
         
         // show the formatted matrix
         //$this->formatMatrix();
         //$innerHTML .= print_r($this->matrix_formatted,1) . "<br>";

         $returnInfo['innerHTML'] = $innerHTML;

         return $returnInfo;

      }
   }
   
   function init() {
      parent::init();
      $this->result = 0;
      $arima_eqn = new Equation;
      $arima_eqn->equation = $this->arima_eqn;
      $arima_eqn->debug = $this->debug;
      $this->addOperator('flow_calc', $arima_eqn, 0);
      for ($i = 0;$i < $this->num_vars; $i++) {
         $this->arData[$this->var_prefix . $i] = $this->init_vals;
         $this->states[$i] = $this->init_vals;
         if ($this->debug) {
            $this->logDebug("Setting state var " . $this->var_prefix . "$i = $this->init_vals <br>");
         }
      }
      $this->lastdate = $this->timer->thistime->format('Y-m-d');
   }
   
   function step() {
      parent::step();
      // get the date, format it to desired resolution
      $today = $this->timer->thistime->format('Y-m-d');
      if ($today <> $this->lastdate) {
         // pop a value off the back of the storage array and push this on the front,
         // otherwise, accumulate it in the current [0] entry
         $this->currentvals = array();
         array_unshift($this->states,0);
         array_pop($this->states);
         $this->lastdate = $today;
      }
      if ($this->debug) {
         $this->logDebug("Values (currentvals): " . print_r($this->currentvals,1) . "<br>");
         $this->logDebug("States (states): " . print_r($this->states,1) . "<br>");
         $this->
logDebug("Parent Data State (datastate): " . $this->parentobject->datastate . "<br>");
      }
      // add the most recent seed value to the variable list
      // unless the parent object has exhausted its values, then we are in full prediction mode
      if ($this->parentobject->datastate == 2) {
         $this->currentvals[] = $this->state->result;
      } else {
         $this->currentvals[] = $this->state[$this->q_var];
      }
      foreach ($this->currentvals as $thisval) {
         $sum += $thisval;
      }
      // stash in an array
      $this->states[0] = $sum / count($this->currentvals);
      // create entries in the arData input matrix for the variables
      for ($i = 0;$i < $this->num_vars; $i++) {
         $this->arData[$this->var_prefix . $i] = $this->states[$i];
      }
   }
   
   function evaluate() {
      
      // flow_calc has already been set
      $this->result = $this->state['flow_calc'];
      if ($this->debug) {
         $this->logDebug("evaluate() called - returning result of ARIMA model: $this->result <br>");
      }
   }
   
   function showFormHeader($formatted,$formname) {
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . " | ";
      $innerHTML .= "<b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'] . " | ";
      $innerHTML .= "<b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'];
      return $innerHTML;
   }
   
   function showFormBody($formatted,$formname) {
      $innerHTML .= " Flow Variable to Use as Equation Input" . $formatted->formpieces['fields']['q_var'] . "<br>";
      $innerHTML .= "<b># of days values to store (q0 (=t-0), q1, q2, ... q#-1):</b> " . $formatted->formpieces['fields']['num_vars'] . "<BR>";
      $innerHTML .= "<b>ARIMA Equation:</b> " . $formatted->formpieces['fields']['arima_eqn'] . "<br>";
      $innerHTML .= "<b>Initial Value:</b> " . $formatted->formpieces['fields']['init_vals'] . "<BR>";
      return $innerHTML;
   }
   
   function showFormFooter($formatted,$formname) {
      $innerHTML = '';
      return $innerHTML;
   }
   
}
   
class USGSRecharge extends modelSubObject {
   // needs to store the date, select the temporal resolution of the data storage
   // resolution options:
   // year, month, day, hour
   // format the date according to the desired resolution
   
   // this is a general purpose class for a simple flow-by
   // other, more complicated flow-bys will inherit this class
   
   var $q_var = 'Qout';
   var $r_start_day = 300; // julian day of period start
   var $r_end_day = 100; // julian day of period end
   var $r_default = 0.0;
   var $r_q = NULL;
   var $b0 = 0.0; // first regression term
   var $b1 = 0.0; // 2nd regression term
   var $p_lt = NULL; // probability of being greater than desired flow level
   var $p_gt = NULL; // probability of being less than desired flow level
   
   function wake() {
      parent::wake();
      $this->result = 0;
      $this->accumulator = array();
      $this->rvars[] = $this->q_var;
      $this->vars[] = $this->q_var;
      $this->wvars[] = 'p_gt';
      $this->wvars[] = 'p_lt';
      $this->wvars[] = 'r_q';
      $this->state['p_gt'] = 0.0;
      $this->state['p_lt'] = 0.0;
      $this->state['r_q'] = 0.0;
      if ($this->r_start_day < $this->r_end_day) {
         // we have a single calendar year date range
         $this->r_type = 'single';
      } else {
         // we have a date range that spans multiple calendar years
         $this->r_type = 'multi';
      }
   }
   
   function appendStringArray($src, $var, $delim=',') {
      $ar = explode($delim, $src);
      $ar[] = $var;
      return join($delim, $ar);
   }
   
   function setState() {
      parent::setState();
      $this->setSingleDataColumnType('p_gt', 'float8', 0.0, 1);
      $this->setSingleDataColumnType('p_lt', 'float8', 0.0, 1);
      $this->setSingleDataColumnType('r_q', 'float8', 0.0, 1);
      $this->state['p_gt'] = 0.0;
      $this->state['p_lt'] = 0.0;
      $this->state['r_q'] = 0.0;
   }
   
   function step() {
      parent::step();
      // if in the period, add the most recent Q to the stack and compute average
      // if outside the period, clear the stack and use last value for the average
      // if the last value is null it means that we have not yet entered a recharge period
      // during the current simulation, and we should use the default value for recharge
      $q = $this->arData[$this->q_var];
      // get the date, format it to desired resolution
      $julian = $this->timer->thistime->format('z');
      $inside = 0;
      if ($this->debug) {
         $this->logDebug("Getting $this->q_var from arData on julian day $julian: $q<br>");
         $this->logDebug("Selected date ranges: start = $this->r_start_day, end = $this->r_end_day<br>");
      }
      switch ($this->r_type) {
         case 'single':
         if ( ($julian >= $this->r_start_day) and ($julian <= $this->r_end_day) ) {
            $this->accumulator[] = $q;
            $this->r_q = array_avg($this->accumulator);
            $this->setStateVar('r_q', $this->r_q);
            $inside = 1;
         } else {
            $this->accumulator = array();
         }
         break;
         
         case 'multi':
         if ( ($julian >= $this->r_start_day) or ($julian <= $this->r_end_day) ) {
            $this->accumulator[] = $q;
            $this->r_q = array_avg($this->accumulator);
            $this->setStateVar('r_q', $this->r_q);
            $inside = 1;
         } else {
            $this->accumulator = array();
         }
         break;
      
      }
      $this->setStateVar('inside', $inside);
      if ($this->debug) {
         $this->logDebug("This date series type = $this->r_type <br>");
         $this->logDebug("Stack: " . print_r($this->accumulator,1) . "<br>");
      }
      
   }
   
   function evaluate() {
      if ($this->state['inside']) {
         $this->p_gt = 1.0 /( 1.0 + exp(1.0 * ($this->b0 + $this->b1 * $this->r_q)));
         $this->p_lt = 1.0 /( 1.0 + exp(-1.0 * ($this->b0 + $this->b1 * $this->r_q)));
         $this->setStateVar('p_gt', $this->p_gt);
         $this->setStateVar('p_lt', $this->p_lt);
      }
      if ($this->debug) {
         $this->logDebug("Prob Greater: $this->p_gt = 1.0 /( 1.0 + exp(1.0 * ($this->b0 + $this->b1 * $this->r_q))); <br>");
         $this->logDebug("Prob Less: $this->p_lt = 1.0 /( 1.0 + exp(-1.0 * ($this->b0 + $this->b1 * $this->r_q))); <br>");
      }
   }
   
}

class USGSChannelGeomObject extends channelObject {
   # an object that employs the USGS physiographic province based methodology to estimate channel geometry
   # user must input channel length and drainage area
   var $province = 1; 
      # 1 - Appalachian Plateau
      # 2 - Valley and Ridge
      # 3 - Piedmont
      # 4 - Coastal Plain
   var $drainage_area = 100.0; # given in square miles - this is the entire drainage area above the outlet
   var $reset_channelprops = 0;
   var $recreate_list = 'reset_channelprops';

   function setState() {
      parent::setState();
      $this->state['drainage_area'] = $this->drainage_area;
      $this->setSingleDataColumnType('drainage_area', 'float8', $this->drainage_area);
   }
   
   function create() {
      parent::create();
      error_log("Calling create()");
      # set channel geometries
      $this->setChannelGeom();
      $this->reset_channelprops = 0;
   }
      
   
   function wake() {
      
      # now call parent channel routing routine
      parent::wake();
      $this->prop_desc['drainage_area'] = 'The entire drainage area above the outlet. (sqmi)';
      $this->prop_desc['demand'] = 'Withdrawal of water from this reach (cfs).';
      $this->prop_desc['discharge'] = 'Discharge of water into this reach (MGD).';
   }
   
   function sleep() {
      
      # now call parent channel routing routine
      parent::sleep();
   }
   
   function init() {
      
      # now call parent channel routing routine
      parent::init();
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'drainage_area');

      return $publix;
   }
   
   function setChannelGeom() {
      
      switch ($this->province) {
         
         case 1:
            # Appalachian Plateau
            # bank full stage
            $hc = 2.030;
            $he = 0.2310;
            # bank full width
            $bfc = 12.175;
            $bfe = 0.4711;
            # base width
            $bc = 5.389;
            $be = 0.5349;
            $n = 0.036; # these are mannings N, using only the median numbers from USGS study,
                        # later we should incorporate the changing N as it is for high median and low flows
         break;
         case 2:
            # Valley and Ridge
            $hc = 1.435;
            $he = 0.2830;
            $bfc = 13.216;
            $bfe = 0.4532;
            $bc = 4.667;
            $be = 0.5489;
            $n = 0.038;
         break;
         case 3:
            # Piedmont
            $hc = 2.137;
            $he = 0.2561;
            $bfc = 14.135;
            $bfe = 0.4111;
            $bc = 6.393;
            $be = 0.4604;
            $n = 0.095;
         break;
         case 4:
            # Coastal Plain
            $hc = 2.820;
            $he = 0.2000;
            $bfc = 15.791;
            $bfe = 0.3758;
            $bc = 6.440;
            $be = 0.4442;
            $n = 0.040;
         break;
         
         default:
            $hc = 2.030;
            $he = 0.2000;
            $bfc = 12.175;
            $bfe = 0.4711;
            $bc = 5.389;
            $be = 0.5349;
            $n = 0.036;
         break;
      }
         
      $h = $hc * pow($this->drainage_area, $he);
      $bf = $bfc * pow($this->drainage_area, $bfe);
      $b = $bc * pow($this->drainage_area, $be);
      $z = 0.5 * ($bf - $b) / $h; 
         # since Z is increase slope of a single side, 
         # the top width increases (relative to the bottom) at a rate of (2 * Z * h)
      # only use these derived values if they are non-zero, otherwise, use defaults
      if ($z > 0) {
         $this->Z = $z;
      } else {
         $this->logError("Calculated Z value from (0.5 * ($bf - $b) / $h) less than zero, using default " . $this->Z . "<br>");
      }
      if ($b > 0) {
         $this->base = $b;
      } else {
         $this->logError("Calculated base value from ($bc * pow($this->drainage_area, $be)) less than zero, using default " . $this->base . "<br>");
      }
      $this->logError("Calculated base value from ($bc * pow($this->drainage_area, $be)), = $b / " . $this->base . " Province: $this->province <br>");
      $this->n = $n;

      return;

   }
}

class USGSChannelGeomObject_sub extends USGSChannelGeomObject {
  // does all of channel, but in small sub-object form
  
   var $q_var = 'Qup';
   var $r_var = 'Runit';
   var $w_var = 'demand';
   // *************************************************
   // BEGIN - Special Parent Variable Setting Interface
   // *************************************************
   function setState() {
      parent::setState();
      $this->wvars[] = 'Qin';
      $this->wvars[] = 'Rin';
      $this->wvars[] = 'Qout';
      $this->wvars[] = 'depth';
      $this->wvars[] = 'drainage_area';
      $this->wvars[] = 'area';
      $this->wvars[] = 'demand';
      $this->wvars[] = 'its';
      $this->wvars[] = 'Storage';
      $this->wvars[] = 'last_S';
      $this->wvars[] = 'rejected_demand_mgd';
      $this->wvars[] = 'rejected_demand_pct';
      $this->rvars[] = $this->q_var;
      $this->rvars[] = $this->r_var;
      $this->rvars[] = $this->w_var;
      
      $this->initOnParent();
   }
   
   function getInputs() {
      parent::getInputs();
      $this->setStateVar('Rin',$this->arData[$this->r_var]);
      $this->setStateVar('Qin',$this->arData[$this->q_var]);
      $this->setStateVar('demand',$this->arData[$this->w_var]);
      //error_log("Copying $this->q_var and $this->r_var from arData: " . print_r($this->arData,1));
   }

   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      foreach ($this->varsToSetOnParent() as $thisvar) {
         if ($this->debug) {
            $this->logDebug("Setting $thisvar to type float8 on parent.<br>\n");
            //error_log("$thisvar to type float8 on parent.<br>\n");
         }
         $this->parentobject->setSingleDataColumnType($thisvar, 'float8', 0.0);
         if (!in_array($thisvar, $this->vars)) {
            $this->vars[] = $thisvar;
         }
      }
   }
   // *************************************************
   // END - Special Parent Variable Setting Interface
   // *************************************************   
   
   function sleep() {
      $this->wvars = null;
      parent::sleep();
   
   }

}

?>
