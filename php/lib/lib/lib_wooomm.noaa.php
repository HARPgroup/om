<?php

//********************************
//**  LIBRARY OF NOAA FUNCTIONS **
//********************************

//error_reporting(E_ALL);

class noaaGriddedPrecip extends dataConnectionObject {
   var $groupremote = 0;
   var $extflag = 1;
   var $intflag = 1;
   var $intmethod = 0;
   var $nullvalue = 0.0;
   
   function wake() {
      if ($this->startdate == '') {
         $this->startdate = '2005-01-01';
      }
      if ($this->enddate == '') {
         $this->enddate = '2005-01-31';
      }
      parent::wake();
      $this->prop_desc['precip_wgtd'] = 'Weighted precip inputs in watershed inches.';
   }
   
   function step() {
      parent::step();
      $this->logDebug("Time series vals: " . print_r($this->tsvalues[min(array_keys($this->tsvalues))],1) . "<br>");
      if (!is_object($this->state['precip_pts'])) {
         $precip_area = 17065136.0 * $this->state['precip_pts'];
      }
      if ( $precip_area >= $this->area_m ) {
         $this->state['precip_wgtd'] = $this->state['precip_in'];
      } else {
         $this->state['precip_wgtd'] = 17065136.0 * $this->state['precip_sum'] / $this->area_m;
      }
   }

   function setState() {
      # gets only properties that are visible (must be manually defined)
      parent::setState();
      $this->state['precip_wgtd'] = 0.0;
      $this->state['precip_in'] = 0.0;
      $this->state['precip_sum'] = 0.0;
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();
      array_push($publix, 'precip_wgtd');

      return $publix;
   }
   
   function finish() {
      //if ($this->debug) {
         $this->logDebug("Table creation SQL:" . $this->localtab_create_sql . "<br>\n");
      //}
      # Create a mapObj, initialized with the blank mapfile
      //$map_file = "/var/www/html/test/blank.map";
      $map_file = "/var/www/html/mapserv/precip_grid.map";
      $mapobject = ms_newMapObj($map_file);
      // get extent
      $this->listobject->querystring = " select xmin(extent(the_geom)), ymin(extent(the_geom)), ";
      $this->listobject->querystring .= " xmax(extent(the_geom)), ymax(extent(the_geom)) ";
      $this->listobject->querystring .= " from $this->scratchtable ";
      $this->logDebug($this->listobject->querystring . " <br>\n");
      $this->listobject->performQuery();
      $xmin = $this->listobject->getRecordValue(1,'xmin');
      $ymin = $this->listobject->getRecordValue(1,'ymin');
      $xmax = $this->listobject->getRecordValue(1,'xmax');
      $ymax = $this->listobject->getRecordValue(1,'ymax');
      
      $this->listobject->querystring = " select * ";
      $this->listobject->querystring .= " from $this->scratchtable limit 5 ";
      $this->logDebug($this->listobject->querystring . " <br>\n");
      $this->listobject->show = 0;
      $this->listobject->performQuery();
      $this->listobject->showList();
      $this->logDebug("Sample: " . $this->listobject->outstring . " <br>\n");
      
      // get the quantiles for these points in order to set their classes when displaying them
      $this->listobject->querystring = " select r_quantile(array_accum(globvalue), 0.25) as q25, ";
      $this->listobject->querystring .= "   r_quantile(array_accum(globvalue), 0.5) as q50, ";
      $this->listobject->querystring .= "   r_quantile(array_accum(globvalue), 0.75) as q75 ";
      $this->listobject->querystring .= " from (select x(the_geom) as x, y(the_geom) as y, ";
      $this->listobject->querystring .= "    sum(globvalue) as globvalue ";
      $this->listobject->querystring .= " from $this->scratchtable ";
      $this->listobject->querystring .= " group by x, y) as foo ";
      $this->logDebug($this->listobject->querystring . " <br>\n");
      $this->listobject->performQuery();
      $q25 = $this->listobject->getRecordValue(1,'q25');
      $q50 = $this->listobject->getRecordValue(1,'q50');
      $q75 = $this->listobject->getRecordValue(1,'q75');
      $this->listobject->show = 0;
      $this->listobject->performQuery();
      $this->listobject->showList();
      $this->logDebug("Quantiles: " . $this->listobject->outstring . " <br>\n");
      
      // make query to summarize the rainfall data by point
      $this->listobject->querystring = " select x(the_geom) as x, y(the_geom) as y, ";
      $this->listobject->querystring .= "    sum(globvalue) as total_precip_in ";
      $this->listobject->querystring .= " from $this->scratchtable ";
      $this->listobject->querystring .= " group by x, y ";
      $this->logDebug($this->listobject->querystring . " <br>\n");
      //error_reporting(E_ALL);
      $this->listobject->performQuery();
      $this->logDebug("Error fromPG: " . $this->listobject->error . " <br>\n");
      $qresult = $this->listobject->queryrecords;
      $this->listobject->show = 0;
      $this->listobject->showList();
      $this->logDebug($this->listobject->outstring . " <br>\n");
      
      // create a map object of this summary data
      # Get the first layer and set a classification attribute
      // *****************************
      // BEGIN - using dynamic layer creation
      // *****************************
      /*
      $layer = ms_newLayerObj($mapobject);
      $layer->set("status", MS_ON);
      $layer->set("type", MS_LAYER_POINT);
      //$layer->set("minscaledenom", 70); // <------ Shows up if denom<71 and doesn't show if denom>=71
      // you can change min to max or add another if you want for the other way around
      // add new class to new layer
      $class = ms_newClassObj($layer);
      $class->label->set("font", "arial");
      $class->label->color->setRGB(0, 222, 31);
      $class->label->set("size", 10);
      $class->label->set("type", MS_TRUETYPE);
      $class->label->set("position", MS_CR);
      $class->label->set("antialias", TRUE);

      $style = ms_newStyleObj($class);
      $style->color->setRGB(0, 0, 0);
      $style->set("size", 6);
      $style->set("symbol", 0);
      $style->set("antialias", TRUE);
      */
      // *****************************
      // END - using dynamic layer creation
      // *****************************
      
      // *****************************
      // BEGIN - using existing layer
      // *****************************
      // this could be done with getLayerByName?
      
      $layer = $mapobject->getLayerByName('precip_period');
      $layer->set("status", MS_ON);
      
      // *****************************
      // END - using existing layer
      // *****************************

      $i = 0;
      //$shape = ms_newShapeObj(MS_SHAPE_POINT);
      
      $max_precip = 0.0;
      foreach($qresult as $row) {
         $shape = ms_newShapeObj(MS_SHAPE_POINT);
         if ($row['total_precip_in'] > $max_precip) {
            $max_precip = $row['total_precip_in'];
         }
         $p = $row['total_precip_in'];
         if ($p < $q25) {
            $c = 0;
         } else {
            if ($p < $q50) {
               $c = 1;
            } else {
               if ($p < $q75) {
                  $c = 2;
               } else {
                  $c = 3;
               }
            }
         }
         $this->logDebug("Adding: " . $row['x'] . "," . $row['y'] . " with Precip = $p and Class = $c <br>\n");
         $pt = ms_newPointObj();
         $pt->setXY($row['x'], $row['y'], 0.0);
         $line = ms_newLineObj();
         $line->add( $pt );
         $shape->add($line);
         //$shape->classindex = $c;
         $shape->set('classindex', $c);
         $layer->addFeature( $shape );
         $i++;
      }
      
      //$pointShape->set("text", "London");
      //$layer->addFeature( $shape );

      $mapobject->insertLayer($layer);
      $this->logDebug("Setting extent to $xmin,$ymin,$xmax,$ymax <br>\n");
      //if ($xmin > 0) {
         $mapobject->setExtent($xmin,$ymin,$xmax,$ymax);
         $this->logDebug("Extent Set<br>\n");
      //}

      $image=$mapobject->draw();


      $this->logDebug("Scale Denominator: " . $mapobject->scaledenom . '<br>'); // to check what your denom is
      $mapobject->drawLabelCache($image);
      //$map->save("/tmp/test2.map"); // if you want to save mapfile to see your output
      $image_url=$image->saveWebImage();

      // new school way of showing the graph
      $this->graphstring .= "<a class='mH' onClick='document[\"image_screen\"].src = \"" . $image_url . "\"; '>$this->name</a> | ";
      $this->graphstring .= "<a href='" . $image_url . "' target='_new'>View Image in New Window</a><br>";
      error_reporting(E_ERROR);
      $this->logDebug("Image URL: $image_url<br>\n");

      // after we finish, we call the parent routines to do clean up such as dropping the scratch table, etc.
      parent::finish();
      
   }
}


class NOAADataObject extends timeSeriesInput {
   var $datatype = 0; # 0 - area Forecast matrix, 1 - stream flow forecast, 2 - Palmer Drought Index
   var $dataURL = 'http://www.srh.noaa.gov/data/LWX/AFMLWX';
   var $stationid = 'VAZ027';
   var $dateformat = '([0-9]{2}/[0-9]{2}/[0-9]{2})';
   var $timeformat = '([0-9]{2})';
   var $datastartcol = 14;
   var $cache_ts = 1; # always opt to store this data in a file, since these are mostly realtime value /future values,
                      # we wish to be able to store any previous record that has been made.
   var $stateabbrev = ''; # state abbreviation for NOAA zones
   var $region = 0; # noaa region code (both state and region needed for Palmer drought)
   var $dataformats = array();
   var $AFMrecordend = '$$'; # end of record for Area Forecast matrix
   var $timezone = 'EST'; # NOAA returns UTC and EST/EDT, maybe others, set desired time zone here
   var $daylightzone = 'EDT'; # corresponds to daylight savings version of zone. This is set in the init() routine, so
                              # it is not really necessary to define here
   # sample URLS:
   # Blacksburg Area Forecast Matrix: http://www.srh.noaa.gov/data/RNK/AFMRNK
   # Wakefiled Area Forecast Matrix: http://www.srh.noaa.gov/data/AKQ/AFMAKQ
   # Sterling Area Forecast Matrix: http://www.srh.noaa.gov/data/LWX/AFMLWX

   # sample data block for Area Forecast matrices
   # 6 hour format:
   #DATE               THU 01/31/08  FRI 02/01/08  SAT 02/02/08  SUN 02/03/08
   #UTC 6HRLY     05   11 17 23 05   11 17 23 05   11 17 23 05   11 17 23
   #EST 6HRLY     00   06 12 18 00   06 12 18 00   06 12 18 00   06 12 18
   #
   #MIN/MAX            23    43      30    43      30    45      25    44
   #TEMP          30   26 39 39 34   32 40 39 34   32 42 38 32   28 40 38
   #DEWPT         14   11 17 20 24   27 31 29 24   23 23 24 22   22 26 26
   #PWIND DIR          NW    SE      SE    SW      NW     W       S     W
   #WIND CHAR          LT    LT      LT    LT      GN    LT      LT    LT
   #AVG CLOUDS    FW   FW FW SC B1   OV OV OV B1   B1 B1 SC SC   SC B1 SC
   #POP 12HR            5    10      60    70      40    20      10    10
   #RAIN                         S    L  L  C  C    S
   #SNOW                              L             S
   # 3 hour format:
   #DATE             MON 01/28/08            TUE 01/29/08            WED 01/30/08
   #UTC 3HRLY     08 11 14 17 20 23 02 05 08 11 14 17 20 23 02 05 08 11 14 17 20 23
   #EST 3HRLY     03 06 09 12 15 18 21 00 03 06 09 12 15 18 21 00 03 06 09 12 15 18
   #
   #MAX/MIN                45 47 49          31          51    35 36 38          47
   #TEMP                38 44 46 42 38 36 35 34 39 47 50 46 43 41 40 38 41 45 46 41
   #DEWPT               23 21 20 22 24 25 26 28 29 32 35 37 38 39 39 33 25 19 16 16
   #RH                  54 39 35 44 57 64 69 78 67 56 56 71 82 92 96 82 52 35 29 36
   #WIND DIR            NW NW NW NW SW SW  S  S  S  S  S  S  S  S SW  W  W  W  W NW
   #WIND SPD             6  4  4  3  3  3  4  3  5  8  6  8 10 13 16 18 19 18 16 11
   #WIND GUST                                                              28
   #CLOUDS              B2 SC FW FW SC SC B1 B1 B2 OV OV OV OV OV OV B1 FW FW FW FW
   #POP 12HR                      0          10          40          70          30
   #QPF 12HR                      0           0        0.01        0.28        0.08
   #RAIN                                            S  C  L  L  L  L  C
   #WIND CHILL                                              37 34 32 29 32 37    34
   #MIN CHILL              29    38    33    30    31          32    27    27    33

   function wake() {
      parent::wake();
      // didn't really want to do this init(), BUT, we need to get our data in 
      // order to know what variables we have to offer
      #$this->init();
      // so, instead, we call jst formatvariables, and retrievedata()
      $this->setUpFormatVariables();
      $this->retrieveData();
   }

   function init() {
      parent::init();
      $this->setUpFormatVariables();
      $this->orderOperations();
      $this->retrieveData();
      ksort($this->tsvalues);
      # stash data values
      #$this->tsvalues2file();

   }

   function setUpFormatVariables() {
      # set up format strings for this data
      $this->dateformat = '([0-9]{2}/[0-9]{2}/[0-9]{2})';
      $this->datastartcol = 13; # what character is the first data column?
      $this->timeformat = '([0-9]{2})';
      $this->dataformats = array(
         # this format is accurate for the 6 hour distribution,
         '6HRLY' => array(
            'dataformat' => "([ A-Z0-9a-z]{13})([ 0-9]{3})([ 0-9]{5})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{5})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{5})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})",
            'numperday' => 4
         ),
         # this format is accurate for the 3 hour distribution,
         '3HRLY' => array(
            'dataformat' => "([ A-Z0-9a-z]{13})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})([ 0-9]{3})",
            'numperday' => 8
         )
      );

      # set the daylight savings zone if applicable
      switch ($this->timezone) {
         case 'AST':
            $this->daylightzone = 'ADT';
         break;

         case 'CST':
            $this->daylightzone = 'CDT';
         break;

         case 'EST':
            $this->daylightzone = 'EDT';
         break;

         case 'MST':
            $this->daylightzone = 'MDT';
         break;

         case 'PST':
            $this->daylightzone = 'PEDT';
         break;

         default:
         # default to no zone defined
            $this->daylightzone = $this->timezone;
         break;
      }
      $this->tsvalues = array();
   }


   function retrieveData() {
      # decide which module to use

      switch ($this->datatype) {

         case 0:
            $this->retrieveAreaMatrix();
         break;

         case 1:
            $this->retrieveFlowForecast();
         break;

         case 2:
            $this->retrievePalmerDroughtIndex();
         break;

      }

   }

   ######################################################################
   ###             Palmer Drought Index Retrieval                     ###
   ######################################################################

   function retrievePalmerDroughtIndex() {
      if ($this->debug) {
         $this->logDebug("Trying to retrieve " .$this->dataURL );
      }
      $indata = file_get_contents($this->dataURL);
      if ($this->debug) {
         $this->logDebug("Retrieved " .$this->dataURL );
      }
      $inlines = explode("\n", $indata);
      $i = 0; # counter for parsed lines
      $startline = -1;
      # station ID corresponds to a region ID for this dataset
      if ($this->debug) {
         $this->logDebug("Searching for Region $this->stationid ");
      }
      $header1 = $inlines[0]; # contains region info
      $header2 = $inlines[1]; # has source info
      $header3 = $inlines[2]; # has date info

      # get the date info
      $regex = 'WEEK ([ 0-9]{2}) OF THE ([0-9]{4}) GROWING SEASON';
      preg_match( $regex, $header3, $matches );
      $week = $matches[1];
      $season = $matches[2];
      $regex = 'WEEK ENDING ([ 0-9]{2}) ([ a-zA-Z]{3}) ([0-9]{4})';
      preg_match( $regex, $header3, $matches );
      $day = $matches[1];
      $month = $matches[2];
      $year = $matches[3];

      # file format
#                                           SOIL     PCT                           MONTH  PRELIM-P  PRECIP
#                                         MOISTURE  FIELD                  CHANGE  MOIST  FINAL -F  NEEDED
#                                       UPPER LOWER  CAP.  POT  RUN  CROP   FROM    ANOM  PALMER    TO END
#                            TEMP  PCPN LAYER LAYER  END  EVAP  OFF MOIST   PREV    (Z)   DROUGHT   DROUGHT
#ST CD CLIMATE DIVISION       (F)  (IN)  (IN)  (IN)  WEEK (IN) (IN) INDEX   WEEK   INDEX  INDEX      (IN)
      # sample line:"MD  5 NORTHEASTERN SHORE    49.2  0.02  0.73  5.00  95.5 0.29 0.00  0.51  -0.51   -0.42   -0.59 P   1.11";
      $linereg = '([A-Z]{2}) ([ 0-9]{2}) ([ .A-Za-z0-9]{20}) ([ -.0-9]{5}) ([ -.0-9]{5}) ([ -.0-9]{5}) ([ -.0-9]{5}) ([ -.0-9]{5}) ([ -.0-9]{4}) ([ -.0-9]{4}) ([ -.0-9]{5}) ([ -.0-9]{6}) ([ -.0-9]{7}) ([ -.0-9]{7}) ([ A-Z]{1})([ -.0-9]{0,7})';
      $colnames = array('state', 'region', 'division', 'temp', 'precip', 'ul_moist', 'll_moist', 'fc_pct', 'pet', 'ro', 'crop_mi', 'delta_wk', 'z_month', 'pdi', 'flag', 'precip_need');

      $parsing = 0;
      $thisentry = array();
      # skip down till we find the "ST" marker at the beginning of the line
      for ($i = 4; $i < count($inlines); $i++) {
         #print("$thisline \n");
         $thisline = $inlines[$i];
         if (substr($thisline, 0,2) == 'ST') {
            # start parsing lines
            $parsing = 1;
         }
         if ($parsing) {
            # must clear the $parsed array, since a failure will NOT overwrite any previous contents
            #print("$thisline \n");
            $parsedvals = array();
            preg_match($linereg, $thisline, $parsedvals);
            #print_r($parsedvals);
            $stateabbrev = $parsedvals[1];
            $region = $parsedvals[2];
            #print("Looking for $stateabbrev = $this->stateabbrev and $region = $this->region \n");
            if ( ($region == $this->region) and ($stateabbrev == $this->stateabbrev) ) {
               # we have a match, grab it.
               $startline = $i;
               if ($this->debug) {
                  $this->logDebug("Found $this->stationid ");
               }
               if ($this->debug) {
                  $this->logDebug(count($colnames) . " column headers \n");
               }
               $data = array_slice($parsedvals, 1, count($parsedvals) - 1);

               if ($this->debug) {
                  $this->logDebug(count($data) . " data values \n");
               }
               $thisentry = array_combine($colnames, $data );
               break;
            }
         }
      }
      # initialize the last date with today, this is valid since this is a forecast relative to today
      $lastdate = date('d/m/Y');
      $lasttime = 24; # set this up to automatically retrieve the next date
      if ($this->debug) {
         $this->logDebug("File has " . count($inlines) . " lines.");
      }
      if (count($thisentry) > 0) {
         $ts = date('r', strtotime("$day $month $year"));
         #print("Adding Values" . print_r($thisentry,1) . "\n");
         $this->addValue($ts, $thisentry);
         $this->addValue($ts, 'timestamp', date('U', strtotime("$day $month $year")));
         $this->addValue($ts, 'thisdate', date('m-d-Y', strtotime("$day $month $year")));

      }


   }

   ######################################################################
   ###           END - Palmer Drought Index Retrieval                 ###
   ######################################################################

   ######################################################################
   ###             Area Forecast Matrix Retrieval                     ###
   ######################################################################
   function retrieveAreaMatrix() {
      if ($this->debug) {
         $this->logDebug("Trying to retrieve " .$this->dataURL );
      }
      $indata = file_get_contents($this->dataURL);
      if ($this->debug) {
         $this->logDebug("Retrieved " .$this->dataURL );
      }
      $inlines = explode("\n", $indata);
      $i = 0; # counter for parsed lines
      $startline = -1;
      if ($this->debug) {
         $this->logDebug("Searching for $this->stationid ");
      }
      for ($i = 0; $i < count($inlines); $i++) {
         $thisline = $inlines[$i];
         if (substr_count($thisline, $this->stationid) > 0) {
            $startline = $i;
            if ($this->debug) {
               $this->logDebug("Found $this->stationid ");
            }
            break;
         }
      }
      # initialize the last date with today, this is valid since this is a forecast relative to today
      $lastdate = date('d/m/Y');
      $lasttime = 24; # set this up to automatically retrieve the next date
      if ($this->debug) {
         $this->logDebug("File has " . count($inlines) . " lines.");
      }
      if ($startline >= 0) {
         # we found the first line, so proceed:
         $lineno = $startline;
         $infoline = '';
         # stash information below this line until we hit the DATE line
         while (!(substr_count($thisline, 'DATE '))) {
            $infoline .= " " . $thisline;
            $lineno++;
            $thisline = $inlines[$lineno];
         }
         if ($this->debug) {
            $this->logDebug("<b>Debug:</b> Info Header: " . $infoline . " <br>");
         }
         # parse the date ranges
         $dates = array();
         # stash the date line for parsing after we determine the temporal resolution
         $dateline = $inlines[$lineno];
         # parse the time stamps and determine data temporal resolution
         $atdata = 0;

         # do this until the end of record or end of file is reached
         while ((substr($inlines[$lineno],0,2) <> $this->AFMrecordend) and ($lineno < count($inlines))) {
            if ($this->debug) {
               $this->logDebug("<b>Debug:</b> Looking for records <br>");
            }

            # scan for temporal resolution and date/time header rows
            # when we find one, call the routine for parsing the record
            $thisline = $inlines[$lineno];
            if (substr($thisline,0,4) == 'DATE') {
               if ($this->debug) {
                  $this->logDebug("<b>Debug:</b> Calling Date Parsing routine <br>");
               }
               # grab the new date info
               $parseresult = $this->parseDateLines($inlines, $lineno, $lastdate, $lasttime);
               $dates = $parseresult['dates'];
               $lineno = $parseresult['lineno'];
               $datares = $parseresult['datares'];
               $times = $parseresult['times'];
               $timecols = $parseresult['timecols'];
               # grab the last date for the wraparound
               $lastdate = $dates[count($dates) -1];
               $lasttime = $dates[count($times) -1];
               if ($this->debug) {
                  $this->logDebug("<b>Debug:</b> Calling Data Parsing routine <br>");
               }

               $lineno = $this->scanLines($inlines, $lineno, $datares, $dates, $times, $timecols);
            } else {
               $lineno++;
            }
         }

      } else {
         if ($this->debug) {
            $this->logDebug("<b>Error:</b> Could not locate requested record: $this->stationid <br>");
         }
      }

   }

   function parseDateLines($inlines, $lineno, $lastdate) {

      $preferredzone = 0;
      $atdata = 0;
      $datares = '';
      $timeline = array();
      $dateline = $inlines[$lineno];
      $lineno++;
      while (!($atdata ) and ($lineno < count($inlines))) {
         $thisline = $inlines[$lineno];
         #ereg($this->dateformat, $thisline, $dates);
         $thiszone = substr($thisline,0,3);
         $thisres = rtrim(ltrim(substr($thisline, 4, 6)));
         if ($this->debug) {
            $this->logDebug("<b>Debug:</b> Time Zone/data resolution Parsed: $thiszone / $thisres <br>");
         }
         if ( ($thiszone == $this->timezone) or ($thiszone == $this->daylightzone) ) {
            # we have found our desired time zone
            $preferredzone = 1;
            $timeline = $thisline;
            $datares = $thisres;
            if ($this->debug) {
               $this->logDebug("<b>Debug:</b> Required Time Zone Found: $thiszone / $datares <br>");
            }
         }

         if (!in_array($thisres, array_keys($this->dataformats))) {
            # either this is of a format that we don;t understand, or we have reached the data lines
            # either way, we break, and then check to see if we have gotten a valid set of times
            $atdata = 1;
         }
         $lineno++;
      }

      # if we have found our preferred zone, then proceed, otherwise log an error and return
      if (!$preferredzone) {
         $this->logError("<b>Error:</b> Could not locate requested time zone $this->timezone <br>");
         return;
      }

      # if we have found a recognized temporal resolution, then proceed, otherwise log an error and return
      if (! (strlen($datares) > 0)) {
         $this->logError("<b>Error:</b> Could not locate recognized temporal resolution in " . print_r(array_keys($this->dataformats),1) . " <br>");
         return;
      }
      $dateparms = array();

      preg_match_all($this->dateformat, $dateline, $dateparms);
      $dates = $dateparms[0];
      preg_match_all($this->timeformat, substr($timeline,$this->datastartcol), $timeparms, PREG_OFFSET_CAPTURE);
      $times = array();
      $timecols = array();
      foreach ($timeparms[0] as $thistime) {
         array_push($times, $thistime[0]);
         array_push($timecols, $thistime[1]);
      }
      $numperday = $this->dataformats[$datares]['numperday'];

      if (count($dates) < (count($times) / $numperday) ) {
         # missing first day, prepend it
         array_pad($dates, (-1 * (count($dates) + 1)), $lastdate);
      }

      if ($this->debug) {
         $this->logDebug("<b>Debug:</b> Date Info, Raw Data: " . $dateline . " <br>");
         $this->logDebug("<b>Debug:</b> Date Format ereg: " . $this->dateformat . " <br>");
         $this->logDebug("<b>Debug:</b> Date Format Output: " . print_r($dateparms,1) . " <br>");
         $this->logDebug("<b>Debug:</b> Date Info, parsed: " . print_r($dates,1) . " <br>");
         $this->logDebug("<b>Debug:</b> Time Info, Raw Data: " . $timeline . " <br>");
         $this->logDebug("<b>Debug:</b> Time Format ereg: " . $this->timeformat . " <br>");
         $this->logDebug("<b>Debug:</b> Time Format Output: " . print_r($timeparms,1) . " <br>");
         $this->logDebug("<b>Debug:</b> Times, parsed: " . print_r($times,1) . " <br>");
         $this->logDebug("<b>Debug:</b> Time Columns, parsed: " . print_r($timecols,1) . " <br>");
      }

      $retarr = array();
      $retarr['dates'] = $dates;
      $retarr['lineno'] = $lineno;
      $retarr['datares'] = $datares;
      $retarr['times'] = $times;
      $retarr['timecols'] = $timecols;

      return $retarr;
   }

   function scanLines($inlines, $lineno, $datares, $dates, $times, $timecols) {
      $thisformat = $this->dataformats[$datares]['dataformat'];
      $numperday = $this->dataformats[$datares]['numperday'];
      $thisline = $inlines[$lineno];
      if ($this->debug) {
         $this->logDebug("<b>Debug:</b> Format Info: $thisformat <br>");
         $this->logDebug("<b>Debug:</b> Numer of records per day: $numperday <br>");
      }

      # screen for the occurence of '$$' which mean srecord end,
      # or 'DATE' which means the next temporal resolution has been found
      while ( (substr($thisline,0,2) <> $this->AFMrecordend) and (substr($thisline,0,4) <> 'DATE') and ($lineno < count($inlines)) ) {

         preg_match($thisline, $thisformat, $dataline);
         if ($this->debug) {
            $this->logDebug("<b>Debug:</b> In Line: $thisline <br>");
            $this->logDebug("<b>Debug:</b> Format Line: $thisformat <br>");
            $this->logDebug("<b>Debug:</b> Parsed Array: " . print_r($dataline,1) . " <br>");
            $this->logDebug("<b>Debug:</b> Item name: " .  substr($thisline,0,4) . "<br>");
         }
         $dataname = ltrim(rtrim(substr($thisline,0,13)));
         if (strlen($dataname) > 0) {
            # iterate through each element of our time fields and add the corresponding
            # element from the data field, with a properly formatted time stamp
            $j = 0;
            $d = 0;
            $lasttime = $times[0];
            while ($j < count($times)) {
               $thistime = $times[$j];
               if ($thistime < $lasttime) {
                  $d++;
               }
               $lasttime = $thistime;
               $thisdate = $dates[$d];
               if ($this->debug) {
                  $this->logDebug("<b>Debug:</b> Date: $thisdate <br>");
               }
               $tstring = $thisdate . " " . $thistime . ":00:00";
               # timecols has column position of time entry
               # subtract 1 from this position, to accomodate values of 100 if present
               $timepos = $timecols[$j] - 1;
               $thisval = ltrim(rtrim(substr(substr($thisline, $this->datastartcol), $timepos,3)));
               $ts = date('r', strtotime($tstring));
               if ($this->debug) {
                  $this->logDebug("<b>Debug:</b> Found: $tstring $dataname - $thisval <br>");
               }
               if (strlen($thisval) > 0 ) {
                  $this->addValue($ts, $dataname, $thisval);
                  $this->addValue($ts, 'timestamp', date('U', $ts));
                  $this->addValue($ts, 'thisdate', date('m-d-Y', $ts));
                  if ($this->debug) {
                     $this->logDebug("<b>Debug:</b> Adding: $tstring $dataname - $thisval <br>");
                  }
               }
               $j++;
            }
         }

         $lineno++;
         $thisline = $inlines[$lineno];
      }

      return $lineno;
   }
   ######################################################################
   ###           END - Area Forecast Matrix Retrieval                 ###
   ######################################################################

}

?>
