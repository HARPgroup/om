<?php
// lib_wooomm.cbp.php


class CBPLandDataConnectionBase extends XMLDataConnection {
//class CBPLandDataConnection extends RSSDataConnection {
//   var $feed_address = 'http://deq1.bse.vt.edu/wooommdev/remote/rss_cbp_land_data.php?actiontype=4';
   var $feed_address = 'http://deq2.bse.vt.edu/om/remote/rss_cbp_land_data.php?actiontype=4';
//   var $data_inventory_address = 'http://deq1.bse.vt.edu/wooommdev/remote/rss_cbp_land_data.php?actiontype=1'; 
   var $data_inventory_address = 'http://deq2.bse.vt.edu/om/remote/rss_cbp_land_data.php?actiontype=1'; 
   var $extra_variables = "startdate=[startdate]\nenddate=[enddate]"; // a list of key=value pairs entered in a text field, if there are carriage returns, it will concatenate them along URL lines with &


   // element for connecting to land use parameters, and outputs, 
   // with a facility for multiplying outputs by the land use areas DataMatrix
   // for modeling the landuse change effects
   var $lunames = array();
   var $scid = -1;
   var $id1 = 'land'; # model data class: river, land, or met
   var $id2 = ''; # land segment: i.e., A24001
   var $riverseg = ''; // optional, this will only be used during calls to "create()" method, restricting the historical land use to the given river and land segment intersection
   var $max_memory_values = 500;
   var $username = 'cbp_ro';
   var $password = 'CbPF!v3';
   var $dbname = 'cbp';
   var $host = 'localhost';
   var $locationid = -1;
   var $conntype = 7;
   var $romode = 'component';
   var $hspf_timestep = 3600.0;
   var $serialist = 'lunames';
   var $datecolumn = 'thisdatetime';
   var $landuse_var = 'landuse'; // this allows the user to switch between land use matrices
   var $mincache = 1024; // file size for automatic cache refresh, if file is not at least 1k, we might have a problem
  
   function init() {
      parent::init();
      //$this->getLandUses();
   }
   function setState() {
      parent::setState();
      $this->state['Qout'] = 0.0;
      $this->state['area_ac'] = 0.0;
      $this->state['area_sqmi'] = 0.0;
      $this->state['Qafps'] = 0.0;
      $this->state['suro'] = 0.0;
      $this->state['ifwo'] = 0.0;
      $this->state['agwo'] = 0.0;
      $this->state['in_ivld'] = 0.0;
      $this->state['landuse_var'] = $this->landuse_var;
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      
      $statenums = array('Qout','area_ac','Qafps', 'suro', 'ifwo', 'agwo', 'prec', 'area_sqmi', 'in_ivld');
      foreach ($statenums as $thiscol) {
         $this->setSingleDataColumnType($thiscol, 'float8', 0.0);
         $this->logformats[$thiscol] = '%s';
      }
      $this->dbcolumntypes['substrateclass'] = 'varchar(2)';
      $this->dbcolumntypes['landuse_var'] = 'varchar(255)';
      
   }
   
   
   function wake() {
      $this->feed_address = 'http://deq2.bse.vt.edu/om/remote/rss_cbp_land_data.php?actiontype=4';
      $this->data_inventory_address = 'http://deq2.bse.vt.edu/om/remote/rss_cbp_land_data.php?actiontype=1'; 
      parent::wake();
      $this->datatemp = 'tmp_crosstab' . $this->componentid;
      $this->lunames = array();
      if ($this->debug) error_log("Calling getLandUses()");
      $this->getLandUses();
   }

   function step() {
      // all step methods MUST call preStep(),execProcessors(), postStep()
      $this->preStep();
      if ($this->debug) {
         $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
      }
      // execute sub-components
      $this->execProcessors();
      if ($this->debug) {
         $this->logDebug("<b>$this->name Sub-processors executed at hour " . $this->state['hour'] . " on " . $this->state['thisdate'] . " week " . $this->state['week'] . " month " . $this->state['month'] . ".</b><br>\n");
      }
      // now do local flow routing manipulations
      // need to multiply landuse matrix values by the appropriate ifow, agwo, and suro for that land use
      // aggregate the results into a Qout variable or some area weighted value as well
      // get landuse matrix
      $Qout = 0.0;
      $Qafps = 0.0;
      $area_ac = 0.0;
      $landuse_var = $this->state['landuse_var'];
      $thisyear = isset($this->state['luyear']) ? $this->state['luyear'] : $this->state['year'];
      if (!($thisyear > 0)) {
         if ($this->debug) {
            $this->logDebug("Something is wrong with the year in the state array calling setStateTimerVars()<br>\n");
         }
         $this->setStateTimerVars();
         $thisyear = $this->state['year'];
      }
      switch ($this->romode) {
         case 'component':
            $flow_comps = array('suro', 'ifwo', 'agwo');
         break;
         
         case 'merged':
            $flow_comps = array('in_ivld');
         break;
      }
         
      $other_comps = array('prec');
      $comp_vals = array('suro'=>0.0, 'ifwo'=>0.0, 'agwo'=>0.0, 'prec'=>0.0, 'in_ivld'=>0.0);
      /*
      // test, if no 'landuse_current' sub-comp is set, draw from the landuse_historic matrix
      // this doesn't work just yet
      // schema should use "landusevar" and "landuseyear", where landusevar is the name of a matrix
      // and landuseyear specifies either a static year, the model property "thisyear" or a reference 
      // to another variable.
      // if "landuseyear" variable is not set as a sub-component, can default to use prop "thisyear"
      // if "landusevar" component is not set, default to "landuse_historic"
      // landuse_year can be a matrix, keyed on run_mode, such that:
      // 0 = some year in history (lets say 1850)
      // 1 = thisyear
      // 2 = some year representing current conditions (say 2005)
      // if "landuse_historic" is not set, then we bail, otherwise we should be good to go
      // this should maintain backward compatibility with previous incarnations of the model which 
      // used separate landuse_var settings and matrices, but did not specify a landuse_year, 
      // so long as landuse_year defaults to thisyear
      
      if ( isset($this->processors[$landuse_var]) or ( ($landuse_var == 'landuse_current') and isset($this->processors['landuse_historic']) ) ) {
         // if the "current" land use is not set,  
         if (($landuse_var == 'landuse_current') and !isset($this->processors['landuse_current']) ) {
            $landuse_matrix = $this->processors['landuse_historic'];
            $luyear = $this->current_lu_year;
      */      
      if ( isset($this->processors[$landuse_var]) ) {
         $landuse_matrix = $this->processors[$landuse_var];
         // get the values for the land uses
         $landuse_matrix->formatMatrix();
         if ($this->debug) {
            $this->logDebug("Getting land use values for year $thisyear<br>\n");
         }
         $lumatrix = $landuse_matrix->matrix_formatted;
         foreach ($lumatrix as $luname=>$values) {
            $luarea = $landuse_matrix->evaluateMatrix($luname, $thisyear);
            if (is_numeric($luarea)) {
               if ($this->debug) {
                  $this->logDebug("Found Land use $luname with area $luarea<br>\n");
               }
               $area_ac += $luarea;
               // only evaluate this if the land use area is > 0.0
               if ($luarea > 0) {
                  foreach ($flow_comps as $thiscomp) {
                     // this is the expected format of this variable, i.e. for_ifwo
                     $lu_flowvar = $luname . '_' . $thiscomp;
                     if ($this->debug) {
                        $this->logDebug("Evaluating $lu_flowvar = " . $this->state[$lu_flowvar]);
                        $this->logDebug("<br>\n");
                     }
                     if (isset($this->state[$lu_flowvar])) {
                        // converts from watershed in/ivld to watershed ft/ivld (/12.0)
                        // to acre-feet/ivld (* luarea)
                        // to cubic-feet (* 43560 ft-per-acre)
                        // to cfs (/timestep)
                        $thisflow = ( ($this->state[$lu_flowvar]/12.0) * $luarea * 43560.0) / $this->hspf_timestep;
                        $comp_vals[$thiscomp] += $thisflow;
                        $Qout += $thisflow;
                        if ($this->debug) {
                           $this->logDebug("Adding $lu_flowvar @ $thisflow cfs to Qout ($Qout) ");
                           $this->logDebug("<br>\n");
                        }
                     }
                  }
                  foreach ($other_comps as $thiscomp) {
                     // this is the expected format of this variable, i.e. for_ifwo
                     $lu_flowvar = $luname . '_' . $thiscomp;
                     if (isset($this->state[$lu_flowvar])) {
                        // converts from watershed in/ivld to watershed ft/ivld (/12.0)
                        // to acre-feet/ivld (* luarea)
                        // to cubic-feet (* 43560 ft-per-acre)
                        // to cfs (/timestep)
                        // weight this comp by the land use area, then later we will un-weight it when we store in the state var
                        switch ($thiscomp) {
                           case 'prec':
                           // since precip is given as a rate in the model output, we have to convert it to a quantity
                              $thisflow = $this->state[$lu_flowvar] * $luarea * ($this->timer->timestep / $this->hspf_timestep);
                           break;

                           default:                           
                              $thisflow = $this->state[$lu_flowvar] * $luarea;
                           break;
                        }
                        $comp_vals[$thiscomp] += $thisflow;
                     }
                  }
               }
            }
         }
         $Qafps = $Qout / ($area_ac * 43560.0);
         if ($this->debug) {
            $this->logdebug("Qout = $Qout <br>\n");
            $this->logdebug("luarea = $luarea <br>\n");
            $this->logdebug("Qafps = $Qout / ($area_ac * 43560.0) <br>\n");
         }
      } else {
         if ($this->debug) {
            $this->logdebug("landuse sub-component not found <br>\n");
         }
      }
      $this->state['Qout'] = floatval($Qout);
      $this->state['area_ac'] = floatval($area_ac);
      $this->state['area_sqmi'] = floatval($area_ac) / 640.0;
      $this->state['Qafps'] = floatval($Qafps);
      $this->state['suro'] = floatval($comp_vals['suro']);
      $this->state['agwo'] = floatval($comp_vals['agwo']);
      $this->state['ifwo'] = floatval($comp_vals['ifwo']);
      $this->state['in_ivld'] = floatval($comp_vals['in_ivld']);
      foreach ($other_comps as $thiscomp) {
         // un-weight these other components now, since we want raw, not converted values
         $this->state[$thiscomp] = floatval($comp_vals[$thiscomp] / $area_ac);
      }
      
      // log the results
      if ($this->debug) {
         $this->logDebug("$this->name Calling Logstate() thisdate = ");
      }
      $this->postStep();
   }

   function showHTMLInfo() {
      $HTMLInfo = '';
      $HTMLInfo .= parent::showHTMLInfo() . "<hr>";
      if (is_object($this->dbobject)) {
         $HTMLInfo .= "<b>DB Info: </b>" . $this->dbobject->dbconn . "<hr>";
      //$this->getLandUses();
      } else {
         $HTMLInfo .= "<b>DB Error: </b> DB Object not valid <hr>";
         $this->setupDBConn(1);
      }
      $HTMLInfo .= "<b>Land Uses: </b>" . print_r($this->lunames,1) . "<hr>";

      if (isset($this->processors[$this->landuse_var])) {
         if (is_object($this->processors[$this->landuse_var])) {
            $HTMLInfo .= '<b>Land Use:</b><br>' . $this->processors[$this->landuse_var]->showHTMLInfo();
            $check_sum = $this->processors[$this->landuse_var]->checkSumCols();
            $HTMLInfo .= 'Check Sum: '. print_r($check_sum,1) . "<br>";
         } else {
            $HTMLInfo .= "Sub-component named 'landuse' is not an object.<br>";
         }
      } else {
         $HTMLInfo .= "Unabled to find sub-component named '$landuse_var' in:<br>";
         $HTMLInfo .= print_r(array_keys($this->processors),1) . "<br>";
      }
      
      $seginfo = $this->getHistoricLandUses();
      $HTMLInfo .= "<hr>Query:<br>" . $seginfo['debug'];
      $this->dbobject->queryrecords = $seginfo['local_annual'];
      $this->dbobject->show = 0;
      $this->dbobject->showList();
         
      $HTMLInfo .= "<hr>Local Land Uses:<br>" . $this->dbobject->outstring;
      return $HTMLInfo;
   }
   
   function getHistoricLandUses() {
      
      if (!is_object($this->dbobject)) {
         $this->conntype = 1; // set temporarily to pgsql
         $this->setupDBConn(1);
         $this->conntype = 7; // set temporarily to pgsql
      }
      if (is_object($this->dbobject)) {
         if (strlen($this->riverseg) > 0) {
            $riverseg = $this->riverseg;
         } else {
            $riverseg = NULL;
         }
         $seginfo = getCBPLandSegmentLanduse($this->dbobject, $this->scid, $this->id2 , $this->debug, $riverseg);
         if ($this->debug) {
            $this->logDebug($seginfo['debug']);
         }
      }
      return $seginfo;
   }
   
   function getModelOutputData($type = 'flowsum', $landuses = '') {
      
      if (!is_object($this->dbobject)) {
         $this->conntype = 1; // set temporarily to pgsql
         $this->setupDBConn(1);
         $this->conntype = 7; // set temporarily to pgsql
      }
      if (is_object($this->dbobject)) {
         if (strlen($this->riverseg) > 0) {
            $riverseg = $this->riverseg;
         } else {
            $riverseg = NULL;
         }
         if (strlen($landuses) > 0) {
            $lus = explode(',', $landuses);
         } else {
            $lus = $this->lunames;
         }
         
         $seginfo = array();
         $this->dbobject->querystring = '';
         switch ($type) {
            case 'flowsum':
            sort($lus);
            foreach ($lus as $thislu) {
               $this->dbobject->querystring = $this->landSegOutputQuery('',$thislu);
               if ($this->debug) {
                  $this->logDebug($this->dbobject->querystring . "<br>");
               }
               $this->dbobject->performQuery();
               $recs = $this->dbobject->queryrecords;
               foreach ($recs as $thisrec) {
                  $seginfo[$thisrec['thisyear']]['thisyear'] = $thisrec['thisyear'];
                  $seginfo[$thisrec['thisyear']][$thislu . '_cfs'] = round($thisrec['ro_cfs'],2);
               }
            }
            break;
            
            case 'runoffsum':
            $q = $this->landSegOutputQuery('SURO');
            $basetab = "( $q ) as foo ";
            $this->dbobject->querystring = doGenericCrossTab ($dbobject, $basetab, 'lseg,thisyear', 'landuse', 'ro_cfs', 1, 0);
            break;
            
            case 'baseflowsum':
            $q = $this->landSegOutputQuery('AGWO');
            $basetab = "( $q ) as foo ";
            $this->dbobject->querystring = doGenericCrossTab ($dbobject, $basetab, 'lseg,thisyear', 'landuse', 'ro_cfs', 1, 0);
            break;
            
            case 'interflowsum':
            $q = $this->landSegOutputQuery('IFWO');
            $basetab = "( $q ) as foo ";
            $this->dbobject->querystring = doGenericCrossTab ($dbobject, $basetab, 'lseg,thisyear', 'landuse', 'ro_cfs', 1, 0);
            break;
            
         }
      }
         
      return $seginfo;
   }

   function landSegOutputQuery($flowparam = '', $luname = '') {
      $query = "  select lseg, landuse, extract(year from thisday) as thisyear, ";
      $query .= "    sum(thisvalue) as runoff, ";
      $query .= "    (avg(thisvalue / 12.0 * 640.0 * 43560.0/3600.0)/24.0) as ro_cfs, ";
      $query .= " sum(numrecs) ";
      $query .= " from (";
      $query .= "   select c.thisdate::date as thisday, b.location_id, b.id2 as lseg, ";
      $query .= "      b.id3 as landuse, sum(c.thisvalue) as thisvalue, count(c.*) as numrecs ";
      $query .= "   from cbp_model_location as b, cbp_scenario_output as c";
      $query .= "      where ";
      $query .= "       c.location_id = b.location_id";
      // restrict land uses?
      if (strlen($flowparam) > 0) {
         $query .= "      and c.param_name in ( '" . implode("','",split(",", $flowparam)) . "') ";
      } else {
         $query .= "      and c.param_name in ( 'SURO' , 'IFWO', 'AGWO') ";
      }
      $query .= "      and b.id2 = '$this->id2' ";
      // restrict land uses?
      if (strlen($luname) > 0) {
         $query .= "      and b.id3 in ( '" . implode("','",split(",", $luname)) . "') ";
      }
      $query .= "      and b.scenarioid = $this->scid ";
      $query .= "   group by thisday, b.id2, b.location_id, b.id3 ";
      $query .= "   order by thisday, b.id2, b.id3 ";
      $query .= " ) as foo ";
      $query .= " group by lseg, thisyear, landuse ";
      $query .= " order by lseg, landuse, thisyear ";
      //error_log("Query: $query");
      return $query;
   }

   
   function create() {
      if ($this->debug) {
         $this->logDebug("Processors on this object before create(): " . print_r(array_keys($this->processors),1) . " <br>");
      }
      parent::create();
      if ($this->debug) {
         $this->logDebug("Processors after parent create(): " . print_r(array_keys($this->processors),1) . " <br>");
      }
      // set default land use
      // set basic data query
      $this->logDebug("Create() function called <br>");
      $this->lunames = array();
      //return;
      $this->getLandUses();
      
      if (count($this->lunames) == 0) {
         // if there are none set, just hit some defaults
         $this->lunames = array('for');
      }
      sort($this->lunames);
      
      $this->logDebug("Object landuses: " . print_r($this->lunames,1) . " <br>");
      
      if (isset($this->processors['landuse'])) {
         unset($this->processors['landuse']);
      }
      // landuse subcomponent to allow users to simulate land use values
      $ludef = new dataMatrix;
      $ludef->listobject = $this->listobject;
      $ludef->name = 'landuse';
      $ludef->wake();
      $ludef->numcols = 3;  
      $ludef->valuetype = 2; // 2 column lookup (col & row)
      $ludef->keycol1 = ''; // key for 1st lookup variable
      $ludef->lutype1 = 0; // lookp type - exact match for land use name
      $ludef->keycol2 = 'year'; // key for 2nd lookup variable
      $ludef->lutype2 = 1; // lookup type - interpolated for year value
      // add a row for the header line
      $ludef->numrows = count($this->lunames) + 1;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      $ludef->matrix[] = 'luname - year(acres)';
      $ludef->matrix[] = 1980; // put this year in the date field as a default lower limit
      $ludef->matrix[] = date('Y'); // put current year in the date field as a default upper limit
      foreach ($this->lunames as $thisname) {
         $ludef->matrix[] = $thisname;
         $ludef->matrix[] = 0.0;
         $ludef->matrix[] = 0.0;
      }
      if ($this->debug) {
         $this->logDebug("Trying to add land use sub-component matrix with values: " . print_r($ludef->matrix,1) . " <br>");
      }
      $this->addOperator('landuse', $ludef, 0);
      
      // hiastoric landuse subcomponent to allow users access to model simulated land uses
      $luhist = new dataMatrix;
      $luhist->listobject = $this->listobject;
      $luhist->name = 'landuse_historic';
      $luhist->wake();
      $luhist->valuetype = 2; // 2 column lookup (col & row)
      $luhist->keycol1 = ''; // key for 1st lookup variable
      $luhist->lutype1 = 0; // lookp type - exact match for land use name
      $luhist->keycol2 = 'year'; // key for 2nd lookup variable
      $luhist->lutype2 = 1; // lookup type - interpolated for year value
      // add a row for the header line
      $hist_result = $this->getHistoricLandUses();
      $luhist->assocArrayToMatrix($hist_result['local_annual']);
      if ($this->debug) {
         $this->logDebug("Trying to add historic land use sub-component matrix with values: " . print_r($luhist->matrix,1) . " <br>");
      }
      $this->addOperator('landuse_historic', $luhist, 0);
      if ($this->debug) {
         $this->logDebug("Processors on this object: " . print_r(array_keys($this->processors),1) . " <br>");
      }
   }

   function getData() {
      // restrict the land uses to non-zero values set in the landuse DataMatrix if the use has not explicitly
      // proceed with parent getData routine
      parent::getData();
   }
   
   function restrictLandUses() {
      // check the extra_variables field for id3 (luname) parameter
      // if this is NOT set by the user, then go ahead and look for non-zero entries 
      // in the landuse DataMatrix and append the extra_variables field with a land use criteria 
      // to only get non-zero land uses.  This should speed up the execution time of the XML data query
      $extras = preg_split("/((\r(?!\n))|((?<!\r)\n)|(\r\n))/", $this->subLocalProperties($this->final_extra_variables));
      $edel = ''; // assume no extras to start, if we have them then we will make this a carriage return
      $e_keys = array(); // hold the keys for any extra variables here
      foreach ($extras as $thisextra) {
         list($key, $value) = explode("=", $thisextra);
         $edel = "\n";
         // stow the keys in the e_keys array for searching later
         $e_keys[] = $key;
      }
      
      if (!in_array('id3', $e_keys)) {
         $nz_landuses = array();
         // user has NOT asked for specific land uses, thus, restrict the query of land uses to our non-zero ones
         // defined in the landuse DataMatrix
         if (isset($this->processors['landuse'])) {
            $landuse_matrix = $this->processors['landuse'];
            // get the values for the land uses, if there are NO non-zero land uses, we will not retrieve data 
            // call formatMatrix routine, then look in the rows for each land use for non-zero values
            // WE Should look within the start and end dates of this simulation and ONLY retrieve values within 
            // the current start and end dates, but for now, we will retrieve if ANY non-zero values occur
            // later we can figure out a smart way to do this to improve efficiency
            $landuse_matrix->formatMatrix();
            $lumatrix = $landuse_matrix->matrix_formatted;
            foreach ($lumatrix as $luname=>$values) {
               foreach ($values as $year => $luarea) {
                  if ($luarea > 0) {
                     $nz_landuses[] = $luname;
                  }
               }
            }
         } else {
            $this->logdebug("landuse sub-component not found <br>\n");
         }
         $rstring = 'id3=-1'; // send a dummy that will not match by default (no landuses)
         if (count($nz_landuses) > 0) {
            $rstring = $edel . 'id3=' . implode(",", $nz_landuses);
         }
         $this->final_extra_variables .= $edel . $rstring;
         if ($this->debug) {
            $this->logDebug("restricting land uses with $rstring <br>\n");
         }
      }
         
   }
   
   function getLandUses() {
      
      // the landuse_names are stashed in the retrieval 'feed_inventory' variable 
      
      if (!is_array($this->feed_inventory)) {
        error_log("Feed_inventory is not array, gettype = " . gettype($this->feed_inventory));
        return FALSE;
      }
      if (isset($this->feed_inventory['landuse_names'])) {
         $lu_csv = $this->feed_inventory['landuse_names'];
         if ($this->debug) {
           error_log("Lu CSV: " . $lu_csv . "<br>\n");
         }
         //return;
         foreach (explode(",",$lu_csv) as $thislu) {
            if ( !in_array($thislu, $this->lunames) ) {
               $this->lunames[] = $thislu;
            }
         }
         //error_log("Lu Array: " . print_r($this->lunames,1) . "<br>\n");
      }
    if ($this->debug) {
     error_log("Lu array: " . print_r($this->lunames,1));
    }
   }

   
   function processLocalExtras() {
      // this will incorporate any local properties, and check to make sure that the url is formed OK,
      // this needs to be slightly different from the parent finalizeFeedURLs() method, so we sub-class it 
      // set our extra parameters, then call the parent method
      if (!$this->urls_finalized) {
         // final_extra_variables should be a copy of extra_variables, but if anything was appended priot, we will use it
         $extra_variables = $this->final_extra_variables;
         // check for extras, if we have some, append a newline, then the id1, and id2 props set in our object props
         $extras = preg_split("/((\r(?!\n))|((?<!\r)\n)|(\r\n))/", $this->subLocalProperties($extra_variables));
         $edel = '';
         foreach ($extras as $thisextra) {
            if (strlen(ltrim(rtrim($thisextra))) > 0) {
               $edel = "\n";
            }
         }
         // now append local properties
         $extra_variables .= $edel . 'id1=' . $this->id1;
         $edel = "\n";
         $extra_variables .= $edel . 'id2=' . $this->id2;
         $extra_variables .= $edel . 'timestep=' . $this->dt;
         $extra_variables .= $edel . 'scenarioid=' . $this->scid;
         $extra_variables .= $edel . 'romode=' . $this->romode;
         // now, set the extra_variables property to this expanded version and call the parent routine
         $this->final_extra_variables = $extra_variables;
         if ($this->debug) {
            $this->logDebug("Final Extra Variables: $extra_variables <br>\n");
         }
         //error_log("Final Query URL: $url <br>\n");
         // adds our land uses to the extra_variables parameter
         // this would speed things up, but unfortunately, we cannot do this because "landuse" is a sub-component
         // and by definition cannot be awoken until AFTER the parent is awoken.  need to rethink this if 
         // this causes a significant performance hit
         //$this->restrictLandUses();
      }
   }

}


class CBPLandDataConnectionFile extends timeSeriesFile {
//   var $feed_address = 'http://deq1.bse.vt.edu/wooommdev/remote/rss_cbp_land_data.php?actiontype=4';
  var $filepath = '/tmp/text';
  var $modelpath = '/media/text';
  var $json2d = TRUE;

  // element for connecting to land use parameters, and outputs, 
  // with a facility for multiplying outputs by the land use areas DataMatrix
  // for modeling the landuse change effects
  var $lunames = array();
  var $scenarios = array();
  var $scid = -1;
  var $id1 = 'land'; # model data class: river, land, or met
  var $version = ''; # model version: i.e., cbp6
  var $scenario = ''; # 062211
  var $landseg = ''; # land segment: i.e., A24001
  var $riverseg = ''; // optional, this will only be used during calls to "create()" method, restricting the historical land use to the given river and land segment intersection
  var $max_memory_values = 500;
  var $locationid = -1;
  var $romode = 'component';
  var $hspf_timestep = 3600.0;
  var $serialist = 'lunames';
  var $datecolumn = 'thisdatetime';
  var $landuse_var = 'landuse'; // this allows the user to switch between land use matrices
  var $mincache = 1024; // file size for automatic cache refresh, if file is not at least 1k, we might have a problem
  var $mode_global = TRUE; // retrieves 
  // @todo: test this
  var $intmethod = 3;
  // - local munging of timestep?
  //   Could pass entire hourly simulation to model, and use:
  //     var $intmethod = 3;
  //   Which will do a period mean inflow for us (but may be time-consuming)
   
	// Needs to Support:
  // - Use a shared runtime database table 
  // - Make runtime database table persistant 
  // @todo: explore passing arrays to broadcast object, 
  //        such as the array of current runoff for a given landseg
  //        Or just store it in a global, accessible by runoff objects
  
   // @todo: 
   // Implement global land seg data
   // global $cbp_landseg_data;
    // $cbp_landseg_data 
  // Behavior
  
  function setSingleDataColumnType($thiscol, $thistype = 'float8', $defval = NULL, $loggable = 1, $overwrite = 0, $logformat='%s') {
    //error_log("$thiscol, $thistype, $defval, $loggable, $overwrite, $logformat ");
    parent::setSingleDataColumnType($thiscol, $thistype, $defval, $loggable, $overwrite, $logformat);
  }

  function getFileName() {
    global $run_mode, $flow_mode;
    // This overrides the parent method which used a file browser no longer desired.
    // handles file movement in the background, choices among source types
    // could do 
    // filepath (base dir) + out/land/ + scenario + eos + landseg 
    //error_log("Global Modes: $run_mode, $flow_mode ");
    // if component flow_scenario is set, 
    // then override the scenario setting and the file name 
    if (isset($this->processors['flow_scenario'])) {
      if (method_exists($this->processors['flow_scenario'], 'evaluateMatrix')) {
        $flow_scenario = $this->processors['flow_scenario']->evaluateMatrix($flow_mode);
      } else {
        $flow_scenario = $this->getProp('flow_scenario');
      }
      if (strlen(trim($flow_scenario)) > 0) {
        //error_log("flow_scenario prop found: $flow_scenario ");
        //error_log("State array:" . print_r($this->state,1));
        $this->scenario = $flow_scenario;
        $this->filepath = implode("/", array($this->modelpath, 'out/land', $this->scenario, 'eos', $this->landseg .'_0111-0211-0411.csv' ));
        error_log("Scenario: $this->scenario, Filepath: $this->filepath");
        $this->setDBCacheName();
      }
    }
    $retfile = $this->filepath;
    return $retfile;
  }
  
   function init() {
      parent::init();
      //$this->getLandUses();
   }
   function toArray() {
     $this->file_info['handle'] = FALSE;
     return parent::toArray();
   }
   function setState() {
      parent::setState();
      $this->state['Qout'] = 0.0;
      $this->state['area_ac'] = 0.0;
      $this->state['area_sqmi'] = 0.0;
      $this->state['Qafps'] = 0.0;
      $this->state['suro'] = 0.0;
      $this->state['ifwo'] = 0.0;
      $this->state['agwo'] = 0.0;
      $this->state['in_ivld'] = 0.0;
      $this->state['landuse_var'] = $this->landuse_var;
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      
      $statenums = array('Qout','area_ac','Qafps', 'suro', 'ifwo', 'agwo', 'prec', 'area_sqmi', 'in_ivld');
      foreach ($statenums as $thiscol) {
         $this->setSingleDataColumnType($thiscol, 'float8', 0.0);
         $this->logformats[$thiscol] = '%s';
      }
      $this->dbcolumntypes['substrateclass'] = 'varchar(2)';
      $this->dbcolumntypes['landuse_var'] = 'varchar(255)';
   }
   
   function sleep() {
     $this->scenarios = NULL;
     parent::sleep();
   }
   
  function wake() {
    parent::wake();
    // @todo: move this to parent class after testing
    $this->getFileInfo();
    // @todo: check for sub-comps that override version, scenario, landseg based on runid or run_mode, need to use code to initialize these 
    error_log("Calling getLandUses()");
    $this->setDBCacheName();
    // @todo: make this persistent, and shared
    $this->datatemp = 'tmp_crosstab' . $this->componentid;
    $this->lunames = array();
    //if ($this->debug) 
      error_log("Calling getLandUses()");
    // @todo: check sub-comps for a 'filepath' variable.
    //        this can be used to over-ride the default filepath property
    $this->getLandUses();
  }

   function setDBCacheName() {
      # set a name for the temp table that will not hose the db
      if ($this->debug) {
        $this->logDebug("Setting db_cache_name to cbp_  $this->version _ $this->scenario _ $this->landseg");
      }
      error_log("Setting db_cache_name to cbp_  $this->version _ $this->scenario _ $this->landseg");
      $this->db_cache_name = strtolower('cbp_' . implode('_', array($this->version, $this->scenario, $this->landseg)));
      $this->db_cache_persist = TRUE; // we can do this for files that are large and infrequently updated
      //error_log("DSN $this->name set to $this->db_cache_name ");
   }
   
  function tsvaluesFromLogFile($infile='') {
    global $modeldb;
    // @todo: for elements with a persistent cache OR for those with a shared cache
    //         1. check to see if the cache table exists already.
    //         2. If cache table already exists, check date on table in cache table lookup
    //           - this is something to borrow from the analysis table/session table management
    //         3. If cache date is < file modified date then do nothing and return
    //         4. Otherwise, proceed as normal     
    // check for a file, if set, use it to populate the lookup table, otherwise, use the CSV string
    // if table exists, just return, all is cool.
    if (is_object($modeldb)) {
      if ($modeldb->tableExists($this->db_cache_name)) {
       error_log("Cache Table $this->db_cache_name already exists. Returning.");
       return;
      }
    }
    parent::tsvaluesFromLogFile($infile);
  }
   
  function tsvalues2listobject($columns = array()) {
    global $modeldb;
    // if table exists, just return, all is cool.
    if (is_object($modeldb)) {
      $this->listobject = $modeldb;
      if ($modeldb->tableExists($this->db_cache_name)) {
       error_log("Cache Table $this->db_cache_name already exists. Returning.");
       return;
      }
    }
    // otherwise use parent methods
    parent::tsvalues2listobject($columns);
  }

   function step() {
      // all step methods MUST call preStep(),execProcessors(), postStep()
      $this->preStep();
      if ($this->debug) {
         $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
      }
      // execute sub-components
      $this->execProcessors();
      if ($this->debug) {
         $this->logDebug("<b>$this->name Sub-processors executed at hour " . $this->state['hour'] . " on " . $this->state['thisdate'] . " week " . $this->state['week'] . " month " . $this->state['month'] . ".</b><br>\n");
      }
      // now do local flow routing manipulations
      // need to multiply landuse matrix values by the appropriate ifwo, agwo, and suro for that land use
      // aggregate the results into a Qout variable or some area weighted value as well
      // get landuse matrix
      $Qout = 0.0;
      $Qafps = 0.0;
      $area_ac = 0.0;
      $landuse_var = $this->state['landuse_var'];
      $thisyear = isset($this->state['luyear']) ? $this->state['luyear'] : $this->state['year'];
      if (!($thisyear > 0)) {
         if ($this->debug) {
            $this->logDebug("Something is wrong with the year in the state array calling setStateTimerVars()<br>\n");
         }
         $this->setStateTimerVars();
         $thisyear = $this->state['year'];
      }
      switch ($this->romode) {
         case 'component':
            $flow_comps = array('suro', 'ifwo', 'agwo');
         break;
         
         case 'merged':
            $flow_comps = array('in_ivld');
         break;
      }
         
      $other_comps = array('prec');
      $comp_vals = array('suro'=>0.0, 'ifwo'=>0.0, 'agwo'=>0.0, 'prec'=>0.0, 'in_ivld'=>0.0);
      /*
      // test, if no 'landuse_current' sub-comp is set, draw from the landuse_historic matrix
      // this doesn't work just yet
      // schema should use "landusevar" and "landuseyear", where landusevar is the name of a matrix
      // and landuseyear specifies either a static year, the model property "thisyear" or a reference 
      // to another variable.
      // if "landuseyear" variable is not set as a sub-component, can default to use prop "thisyear"
      // if "landusevar" component is not set, default to "landuse_historic"
      // landuse_year can be a matrix, keyed on run_mode, such that:
      // 0 = some year in history (lets say 1850)
      // 1 = thisyear
      // 2 = some year representing current conditions (say 2005)
      // if "landuse_historic" is not set, then we bail, otherwise we should be good to go
      // this should maintain backward compatibility with previous incarnations of the model which 
      // used separate landuse_var settings and matrices, but did not specify a landuse_year, 
      // so long as landuse_year defaults to thisyear
      
      if ( isset($this->processors[$landuse_var]) or ( ($landuse_var == 'landuse_current') and isset($this->processors['landuse_historic']) ) ) {
         // if the "current" land use is not set,  
         if (($landuse_var == 'landuse_current') and !isset($this->processors['landuse_current']) ) {
            $landuse_matrix = $this->processors['landuse_historic'];
            $luyear = $this->current_lu_year;
      */      
      if ( isset($this->processors[$landuse_var]) ) {
         $landuse_matrix = $this->processors[$landuse_var];
         // get the values for the land uses
         $landuse_matrix->formatMatrix();
         if ($this->debug) {
            $this->logDebug("Getting land use values for year $thisyear<br>\n");
         }
         $lumatrix = $landuse_matrix->matrix_formatted;
         foreach ($lumatrix as $luname=>$values) {
            $luarea = $landuse_matrix->evaluateMatrix($luname, $thisyear);
            if (is_numeric($luarea)) {
               if ($this->debug) {
                  $this->logDebug("Found Land use $luname with area $luarea<br>\n");
               }
               $area_ac += $luarea;
               // only evaluate this if the land use area is > 0.0
               if ($luarea > 0) {
                  foreach ($flow_comps as $thiscomp) {
                     // this is the expected format of this variable, i.e. for_ifwo
                     $lu_flowvar = $luname . '_' . $thiscomp;
                     if ($this->debug) {
                        $this->logDebug("Evaluating $lu_flowvar = " . $this->state[$lu_flowvar]);
                        $this->logDebug("<br>\n");
                     }
                     if (isset($this->state[$lu_flowvar])) {
                        // converts from watershed in/ivld to watershed ft/ivld (/12.0)
                        // to acre-feet/ivld (* luarea)
                        // to cubic-feet (* 43560 ft-per-acre)
                        // to cfs (/timestep)
                        $thisflow = ( ($this->state[$lu_flowvar]/12.0) * $luarea * 43560.0) / $this->hspf_timestep;
                        $comp_vals[$thiscomp] += $thisflow;
                        $Qout += $thisflow;
                        if ($this->debug) {
                           $this->logDebug("Adding $lu_flowvar @ $thisflow cfs to Qout ($Qout) ");
                           $this->logDebug("<br>\n");
                        }
                     }
                  }
                  foreach ($other_comps as $thiscomp) {
                     // this is the expected format of this variable, i.e. for_ifwo
                     $lu_flowvar = $luname . '_' . $thiscomp;
                     if (isset($this->state[$lu_flowvar])) {
                        // converts from watershed in/ivld to watershed ft/ivld (/12.0)
                        // to acre-feet/ivld (* luarea)
                        // to cubic-feet (* 43560 ft-per-acre)
                        // to cfs (/timestep)
                        // weight this comp by the land use area, then later we will un-weight it when we store in the state var
                        switch ($thiscomp) {
                           case 'prec':
                           // since precip is given as a rate in the model output, we have to convert it to a quantity
                              $thisflow = $this->state[$lu_flowvar] * $luarea * ($this->timer->timestep / $this->hspf_timestep);
                           break;

                           default:                           
                              $thisflow = $this->state[$lu_flowvar] * $luarea;
                           break;
                        }
                        $comp_vals[$thiscomp] += $thisflow;
                     }
                  }
               }
            }
         }
         $Qafps = $Qout / ($area_ac * 43560.0);
         if ($this->debug) {
            $this->logdebug("Qout = $Qout <br>\n");
            $this->logdebug("luarea = $luarea <br>\n");
            $this->logdebug("Qafps = $Qout / ($area_ac * 43560.0) <br>\n");
         }
      } else {
         if ($this->debug) {
            $this->logdebug("landuse sub-component not found <br>\n");
            error_log("$this->name: landuse sub-component not found <br>\n");
         }
      }
      $this->state['Qout'] = floatval($Qout);
      $this->state['area_ac'] = floatval($area_ac);
      $this->state['area_sqmi'] = floatval($area_ac) / 640.0;
      $this->state['Qafps'] = floatval($Qafps);
      $this->state['suro'] = floatval($comp_vals['suro']);
      $this->state['agwo'] = floatval($comp_vals['agwo']);
      $this->state['ifwo'] = floatval($comp_vals['ifwo']);
      $this->state['in_ivld'] = floatval($comp_vals['in_ivld']);
      foreach ($other_comps as $thiscomp) {
         // un-weight these other components now, since we want raw, not converted values
         $this->state[$thiscomp] = floatval($comp_vals[$thiscomp] / $area_ac);
      }
      
      // log the results
      if ($this->debug) {
         $this->logDebug("$this->name Calling Logstate() thisdate = ");
      }
      $this->postStep();
   }

   function showHTMLInfo() {
    global $modeldb;
      $HTMLInfo = '';
      $HTMLInfo .= parent::showHTMLInfo() . "<hr>";
      $HTMLInfo .= "File Info:" . print_r($this->file_info,1) . "<hr>";
      $HTMLInfo .= "DB Cache: $this->db_cache_name (Persist = " . intval($this->db_cache_persist);
      if (is_object($modeldb)) {
        $HTMLInfo .= ", Exist = " . intval($modeldb->tableExists($this->db_cache_name)) . " )";
        $HTMLInfo .= "DB Host:" . pg_host($modeldb->dbconn);
        $HTMLInfo .= ", Port:" . pg_port($modeldb->dbconn);
        $HTMLInfo .= ", Name:" . pg_dbname($modeldb->dbconn);
        $HTMLInfo .= " ) <hr>";
      } else {
        $HTMLInfo .= " ) <hr>";
      }
      $HTMLInfo .= "Vars from File:" . print_r($this->file_vars,1) . "<hr>";
      return $HTMLInfo;
   }

   function create() {
      if ($this->debug) {
         $this->logDebug("Processors on this object before create(): " . print_r(array_keys($this->processors),1) . " <br>");
      }
      parent::create();
      if ($this->debug) {
         $this->logDebug("Processors after parent create(): " . print_r(array_keys($this->processors),1) . " <br>");
      }
      // set default land use
      // set basic data query
      $this->logDebug("Create() function called <br>");
      $this->lunames = array();
      //return;
      $this->getLandUses();
      
      if (count($this->lunames) == 0) {
         // if there are none set, just hit some defaults
         $this->lunames = array('for');
      }
      sort($this->lunames);
      
      $this->logDebug("Object landuses: " . print_r($this->lunames,1) . " <br>");
      
      if (isset($this->processors['landuse'])) {
         unset($this->processors['landuse']);
      }
      // landuse subcomponent to allow users to simulate land use values
      $ludef = new dataMatrix;
      $ludef->listobject = $this->listobject;
      $ludef->name = 'landuse';
      $ludef->wake();
      $ludef->numcols = 3;  
      $ludef->valuetype = 2; // 2 column lookup (col & row)
      $ludef->keycol1 = ''; // key for 1st lookup variable
      $ludef->lutype1 = 0; // lookp type - exact match for land use name
      $ludef->keycol2 = 'year'; // key for 2nd lookup variable
      $ludef->lutype2 = 1; // lookup type - interpolated for year value
      // add a row for the header line
      $ludef->numrows = count($this->lunames) + 1;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      $ludef->matrix[] = 'luname - year(acres)';
      $ludef->matrix[] = 1980; // put this year in the date field as a default lower limit
      $ludef->matrix[] = date('Y'); // put current year in the date field as a default upper limit
      foreach ($this->lunames as $thisname) {
         $ludef->matrix[] = $thisname;
         $ludef->matrix[] = 0.0;
         $ludef->matrix[] = 0.0;
      }
      if ($this->debug) {
         $this->logDebug("Trying to add land use sub-component matrix with values: " . print_r($ludef->matrix,1) . " <br>");
      }
      $this->addOperator('landuse', $ludef, 0);
   }

   function getData() {
      // restrict the land uses to non-zero values set in the landuse DataMatrix if the use has not explicitly
      // proceed with parent getData routine
      parent::getData();
   }
   
  function getLandUses() {
    // the landuse_names are the first row of the csv file
    $this->luname = $this->file_vars; // this should be set in tsVarsFromFile() called in wake() method
    if ($this->debug) {
     error_log("Lu array: " . print_r($this->lunames,1));
    }
  }
   
  function getCurrentDataSlice() {
    # need to get a certain, sensible number of values that match two criteria:
    #   1) do not exceed the max_memory_values
    #  OR
    #   2) only get enough to encompass the current dt
    # hmmm... would a query do this?
    # how about
    # select count(*) numts from table where timestamp >= $currenttime and timestamp <= $currenttime + dt
    # then, if numts > max_memory_values set LIMIT = numts
    # select * from table where timestamp >= $currenttime and timestamp <= $currenttime + dt LIMIT numts
    $current_time = $this->timer->thistime->format("U");
    // do this to give us a sane starting time at first get 
    $dt = $this->dt;
    if ($this->timer->steps == 0 ) {
      // subtracting dt here insures that we will be "inside" the time interval
      $this->lasttimesec = $current_time - $dt;
    }
    if ($this->listobject->tableExists($this->db_cache_name)) {
      // get all from last time to now 
      $this->listobject->querystring = "  select count(*) as numts, min(\"timestamp\") as mints, max(\"timestamp\") as maxts ";
      $this->listobject->querystring .= " from  \"$this->db_cache_name\"";
      $this->listobject->querystring .= " where \"timestamp\" > $this->lasttimesec and \"timestamp\" <= ($current_time + $dt * this->max_memory_values) ";
      if ($this->debug) {
        $this->logDebug($this->listobject->querystring);
      }
      error_log("getCurrentDataSlice $this->name");
      error_log($this->listobject->querystring);
      $this->listobject->performQuery();
      $numts = $this->listobject->getRecordValue(1,'numts');
      if ($this->debug) {
        $this->logDebug("$numts values remaining in cache\n");
      }
      // @todo: check this out.  I think it should set limit to be max_memory_values if numts > max_memory_mb
      //                         Otherwise, what is this accomplishing other than loading all ts all the time?
      if ($numts < $this->max_memory_values) {
        $limit = $numts;
      } else {
        $limit = $this->max_memory_values;
      }
      $this->listobject->querystring = "  SELECT * ";
      $this->listobject->querystring .= " FROM  " . $this->db_cache_name;
      $this->listobject->querystring .= " WHERE \"timestamp\" > $this->lasttimesec ";
      $this->listobject->querystring .= " ORDER BY \"timestamp\"";
      $this->listobject->querystring .= " LIMIT $limit ";
      if ($this->debug) {
        $this->logDebug($this->listobject->querystring);
      }
      error_log($this->listobject->querystring);
      $this->listobject->performQuery();
      $tvs = $this->listobject->queryrecords;
      $this->tsvalues = array();
      foreach ($tvs as $thistv) {
        $ts = $thistv['timestamp'];
        $this->tsvalues[$ts] = $thistv;
        $keys = array_keys($thistv);
        $firstkey = $keys[0];
        //error_log("At Timestamp $ts adding: $firstkey = " . $thistv[$firstkey]);
      }
    }
    
    if ($this->debug) {
       $this->logDebug("$this->name getCurrentDataSlice() added " . count($this->tsvalues) . " to tsvalues array");
    }
  }

  function setSimTimer($thistimer) {
    parent::setSimTimer($thistimer);
    /*
    // @todo: test this out. Think it kills the simulation?
    //        But it could reduce in memory values by a great deal for short time period sims
    $this->lasttimesec = $this->timer->starttime->format("U") - $this->timer->dt;
    */
  }
}

class CBPLandDataConnection extends CBPLandDataConnectionBase {
}


class CBPLandDataConnection_sub extends CBPLandDataConnection {
   //sub-comp version of land data connection
   // includes it's own land use matrix which is fixed
   var $landuse_var = 'landuse_matrix';
   var $landuse_matrix = null;
   var $lat_dd = null;
   var $lon_dd = null;
   var $matrix = array(); // array shell for storage table
   var $serialist = 'rvars,wvars,matrix';
   var $nearest_landseg = '';
   var $nhd_db = null;
   var $reload_nlcd = 0;
   var $channel_slope = 0.01;
   var $drainage_area = 10.0;
   var $channel_length = 10.0;
   var $recreate_list = 'reload_nlcd';
   
   function setState() {
    $this->object_state = 'setState';
    parent::setState();
    $this->initOnParent();
   }
   
   function init() {
    $this->object_state = 'init';
   }
   
   function initOnParent() {
      if (!is_array($this->wvars)) {
         $this->wvars = array();
      }
      if (!is_array($this->rvars)) {
         $this->rvars = array();
      }
      $this->rvars[] = 'year';
      foreach ( array('suro', 'agwo', 'ifwo','Qout','Runit','area_sqmi') as $thisone) {
         $this->wvars[] = $thisone;
      }
      parent::initOnParent();
   }

   function wake() {
      //error_log("$this->name calling parent wake()");
      $this->object_state = 'wake';
      parent::wake();
      //error_log("$this->name calling setupLanduseMatrix()");
      $this->setupLanduseMatrix();
      //$this->debugmode = 1;
   }
   
   function getTablesColumns() {
    parent::getTablesColumns();
    
    //Experimental code to optimize, not helping
    /*
     //if (!in_array($this->object_state, array('wake', 'setState'))) {
     if (in_array($this->object_state, array('init', 'create'))) {
      parent::getTablesColumns();
     }
     */
   }
       
   function sleep() {
      $this->wvars = 0;
      $this->rvars = 0;
      unset($this->landuse_matrix);
      $this->landuse_matrix = -1;
      parent::sleep();
   }
   
   function logState() {
   
      // logging will be done by the parent, so no need to waste memory and time with this
   
   }

   function subState() {
      $this->initOnParent();
   }
   
   function getParentLatLon() {
      $lat_dd = null; $lon_dd = null;
      if (is_object($this->parentobject)) {
         $lat_dd = $this->parentobject->getProp($this->lat_dd);
         $lon_dd = $this->parentobject->getProp($this->lon_dd);
         //error_log("Calling getCOVACBPLandsegPointContainer($lat_dd, $lon_dd) ");
      } else {
         //error_log("Parent object not set.");
      }
      return array($lat_dd, $lon_dd);
   }
   
  function guessLandSeg() {
    // find the parents geometry and see if it overlaps with a known landseg
    // parent must have valid geometry, default to the first overlapping shape that we find
    //  php fn_findTribs.php 2 36.75639 -82.4347
    // $landseg = getCOVACBPLandsegPointContainer($latdd, $londd);
    //error_log("guessLandSeg() called for $this->name");
    $landseg = null;
    list($lat_dd, $lon_dd) = $this->getParentLatLon();
    //error_log("this->getParentLatLon() returned $lat_dd $lon_dd");
    if ( is_numeric($lat_dd) and is_numeric($lon_dd)) {
      $debug = TRUE;
      $landseg = getCOVACBPLandsegPointContainer($lat_dd, $lon_dd, $debug);
    }
    //error_log("Land Segment: $landseg ");
    $this->nearest_landseg = $landseg;
  }
   
   function getNHDProperties() {
      $nhd = new nhdPlusDataSource;
      $nhd->init();
      $nhd->debug = 1;
      list($lat_dd, $lon_dd) = $this->getParentLatLon();
      if ( is_numeric($lat_dd) and is_numeric($lon_dd)) {
         $nhd->getPointInfo($lat_dd, $lon_dd);
         //error_log("Searching for coords: $lat_dd, $lon_dd");
         //error_log("NLCD Land Use: " . count($nhd->nlcd_landuse));
         //error_log("NHD+ Reaches: " . print_r($nhd->nhd_segments,1));
      }
      $lumatrix = $this->createLUMatrix($nhd->nlcd_landuse, 1850, 2050, 1);
      $this->assocArrayToMatrix($lumatrix);
      // get channel properties
      //error_log("NHD Properties" . print_r((array)$nhd,1));
      $this->channel_slope = $nhd->channel_slope;
      $this->drainage_area = $nhd->drainage_area;
      $this->channel_length = $nhd->channel_length;
   }
   
   function createLUMatrix($lu, $minyear, $maxyear, $translate = 1) {
      $lr = array();
      foreach ( $lu as $thislu => $thisarea ) {
         if (substr($thislu,0,4) == 'nlcd') {
            $lr[] = array('luname'=>$thislu, $minyear => round($thisarea,3), $maxyear => round($thisarea,3));
         }
      }
      //error_log("Land use recs: " . print_r($lr,1));
      if ($translate) {
         $lr = translateNLCDtoCBP($lu, $minyear, $maxyear);
      }
      return $lr;
   }

   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      foreach ($this->wvars as $thisvar) {
         if ($this->debug) {
            $this->logDebug("Setting $this->name" . "_" . "$thisvar to type float8 on parent.<br>\n");
            //error_log("Setting $this->name" . "_" . "$thisvar to type float8 on parent.<br>\n");
         }
         if (is_object($this->parentobject)) {
            if (method_exists($this->parentobject, 'setSingleDataColumnType')) {
               $this->parentobject->setSingleDataColumnType($this->name . "_" . $thisvar, 'float8', 0.0);
            }
         }
         $this->vars[] = $this->name . "_" . $thisvar;
      }
   }  
   
   function postStep() {
      $this->setStateVar('Runit', $this->state['Qout'] / $this->state['area_sqmi']);
      parent::postStep();
   }
   
   function create() {
     // @todo: move these object_state calls in the base modelObject class 
      $this->object_state = 'create';
      parent::create();
      //error_log("Create routine called on $this->name with $this->text2table");
      $this->setupLanduseMatrix();
      // check to see if the text2table field has anything in it
      // if so, parse the text appropriately
      //error_log("Storage matrix created $this->name ");
      $this->guessLandSeg();
      $this->getNHDProperties();
      $this->reload_nlcd = 0;
      //error_log("Finished loading land use\n");
   }
   
   function assocArrayToMatrix($thisarray = array()) {
   // this may be called by routines that want to set a matrix (such as setNLCDLanduse), so we pass this info to the sub-comp landuse_matrix 
      if (isset($this->processors['landuse_matrix'])) {
         $this->processors['landuse_matrix']->assocArrayToMatrix($thisarray);
         $this->matrix = $this->processors['landuse_matrix']->matrix;
      }
      
   }
   
   function setupLanduseMatrix($text2table = '') {
      //error_log("Called setupLanduseMatrix()");
      $this->landuse_matrix = new dataMatrix;
      $this->landuse_matrix->name = 'storage_stage_area';
      $this->landuse_matrix->wake();
      //$this->landuse_matrix->debug = 1;
      //$this->landuse_matrix->debugmode = 1;
      $this->landuse_matrix->numcols = 3;
      $this->landuse_matrix->fixed_cols = false;
      $this->landuse_matrix->valuetype = 2; // 2 column lookup (col & row)
      $this->landuse_matrix->keycol1 = 'year'; // key for 1st lookup variable
      $this->landuse_matrix->lutype1 = 0; // lookup type for first lookup variable: 0 - exact match; 1 - interpolate values; 2 - stair step
      $this->landuse_matrix->keycol2 = ''; // key for 1st lookup variable
      $this->landuse_matrix->lutype2 = 1; // lookup type for 2nd lookup variable: 0 - exact match; 1 - interpolate values; 2 - stair step
      // add a row for the header line
      if ( !is_array($this->matrix) or (count($this->matrix) == 0) or ($text2table <> '') ) {
         $default_landuses = array( 'for', 'hyo', 'pas', 'hwm', 'pul', 'iml', 'puh', 'imh', 'ext', 'bar');
         $this->landuse_matrix->numrows = count($default_landuses) + 1;
         $this->landuse_matrix->matrix[] = 'luname';
         $this->landuse_matrix->matrix[] = 1900;
         $this->landuse_matrix->matrix[] = 2050;
         foreach ($default_landuses as $thislu) {
            $this->landuse_matrix->matrix[] = $thislu; // the name
            $this->landuse_matrix->matrix[] = 0; // vaue for start year
            $this->landuse_matrix->matrix[] = 0; // value for end year
         }
         $this->matrix = $this->landuse_matrix->matrix;
         //error_log("Creating land use matrix anew: ");
      } else {
         if ($text2table <> '') {
            $this->landuse_matrix->text2table = $text2table;
            $this->landuse_matrix->create();
            //error_log("Creating land use matrix from text string: ");
         } else {
            $this->landuse_matrix->matrix = $this->matrix;// map the text mo to a numerical description
            $this->landuse_matrix->numrows = count($this->landuse_matrix->matrix) / 3.0;
            //error_log("Creating land use matrix from stored data ");
         }
      }
      
      $this->addOperator('landuse_matrix', $this->landuse_matrix, 0);
      //error_log("Adding Landuse Sub-comp 'landuse_matrix': " . print_r($this->landuse_matrix->matrix, 1) );
   }
    
   // *************************************************
   // *********           display interface
   //*************************************************
   function showEditForm($formname, $disabled=0) {
      if (is_object($this->listobject)) {
         $returnInfo = array();
         $returnInfo['name'] = $this->name;
         $returnInfo['description'] = $this->description;
         $returnInfo['debug'] = '';
         $returnInfo['elemtype'] = get_class($this);
         $returnInfo['innerHTML'] = '';
         $innerHTML = '';
         # set up div to contain each seperate multi-parameter block
         $innerHTML .= showHiddenField("numrows", $this->numrows, 1);
         $aset = $this->listobject->adminsetuparray[get_class($this)];
         foreach (array_keys($aset['column info']) as $tc) {
            $props[$tc] = $this->getProp($tc);
         }
         $formatted = showFormVars($this->listobject,$props,$aset,0, 1, 0, 0, 1, 0, -1, NULL, 1);
         $innerHTML .= "<table><tr>";
         $innerHTML .= $this->showFormHeader($formatted,$formname, $disabled );
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormBody($formatted,$formname, $disabled );
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormFooter($formatted,$formname, $disabled );
         $innerHTML .= "</tr></table>";
         
         // show the formatted matrix
         //$this->formatMatrix();
         //$innerHTML .= print_r($this->matrix_formatted,1) . "<br>";
         $returnInfo['innerHTML'] = $innerHTML;
         return $returnInfo;
      }
   }
   
   function showFormHeader($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . " | ";
      $innerHTML .= "<b>Debug?:</b> " . $formatted->formpieces['fields']['debug'] . " | ";
      $innerHTML .= "<b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debugmode'] . " | ";
      $innerHTML .= "<b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'];
      $innerHTML .= "<br><b>Scenario:</b> " . $formatted->formpieces['fields']['scid'];
      $innerHTML .= " | <b>Landseg ID:</b> " . $formatted->formpieces['fields']['landseg'];
      $innerHTML .= " | <b>Data Timestep:</b> " . $formatted->formpieces['fields']['hspf_timestep'];
      $innerHTML .= "<br><b>Latitude:</b> " . $formatted->formpieces['fields']['lat_dd'];
      $innerHTML .= " | <b>Longitude:</b> " . $formatted->formpieces['fields']['lon_dd'];
      //$innerHTML .= "<br><b>Nearest Land Segment:</b> " . $this->nearest_landseg;
      $innerHTML .= "<br><b>Nearest Land Segment:</b> " . $formatted->formpieces['fields']['nearest_landseg'];
      $innerHTML .= "<br><b>Reload NHD Landuse on Save?:</b> " . $formatted->formpieces['fields']['reload_nlcd'];
      
      $innerHTML .= "<br>NHD Props:  ";
      $innerHTML .= "<b>Channel Slope (ft/ft):</b> " . $formatted->formpieces['fields']['channel_slope'];
      $innerHTML .= "<b>Channel Length(ft):</b> " . $formatted->formpieces['fields']['channel_length'];
      $innerHTML .= "<b>Drainage (sqmi):</b> " . $formatted->formpieces['fields']['drainage_area'];
      return $innerHTML;
   }
   
   function showFormBody($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<table><tr>";
      $innerHTML .= "<td valign=top>";
      $innerHTML .= "<b>Land Use Values (acres):</b> <br>";
      //$this->landuse_matrix->debug = 1;
      $innerHTML .= $this->landuse_matrix->showFormBody($formatted,$formname, $disabled);
      $innerHTML .= "</td>";
      return $innerHTML;
   }
   
   function showFormFooter($formatted,$formname, $disabled = 0) {
      $innerHTML = '';
      return $innerHTML;
   }
   // *************************************************
   // *********       END display interface
   // *************************************************
}

class nhdPlusDataSource {
   var $nhd_db = null;
   var $units = 'ft';
   var $host = '192.168.0.21';
   var $dbname = 'va_hydro';
   var $username = 'usgs_ro';
   var $password = '@ustin_CL';
   var $port = 5432;
   var $dbconn = null;
   var $length_units = 'feet';
   var $area_units = 'acres';
   var $channel_info = null;
   var $nlcd_landuse = null;
   var $nhd_segments = null;
   var $channel_slope = null;
   var $drainage_area = null;
   var $channel_length = null;
   
   function init() {
   if (class_exists('pgsql_QueryObject')) {
         $this->nhd_db = new pgsql_QueryObject;
         $this->nhd_db->dbconn = pg_connect("host=$this->host port=$this->port dbname=$this->dbname user=$this->username password=$this->password");
         //error_log("Setting NHD dbconn: host=$this->host port=$this->port dbname=$this->dbname user=$this->username password=$this->password ");
      } else {
         error_log("Cannot locate class pgsql_QueryObject ");
      }
   }
   
   function getPointInfo($lat_dd, $lon_dd) {
      if (is_object($this->nhd_db)) {
         if (is_numeric($lat_dd) and is_numeric($lon_dd)) {
            // general location
            //error_log("NHD DB: " . print_r((array)$this->nhd_db,1));
            $outlet_info = findNHDSegment($this->nhd_db, $lat_dd, $lon_dd, TRUE);
            //error_log("Found outlet: " . print_r($outlet_info,1));
            $comid = $outlet_info['comid'];
            $tribs = findTribs($this->nhd_db, $comid, $this->debug);
            $this->nhd_segments = $tribs['segment_list'];
            //error_log("Found tribs: " . print_r($this->nhd_segments,1));
            // reach characteristics
            $cinfo = getNHDChannelInfo($this->nhd_db, $comid, $this->nhd_segments, $this->units, $this->debug);
            //$cinfo = getNHDChannelInfo($this->nhd_db, $comid, $this->nhd_segments, $this->units, 1);
            //error_log("Found Channel Info for comid: $comid, segments: $this->nhd_segments = " . print_r($cinfo,1));
            $this->channel_slope = round($cinfo['c_slope'],4);
            $this->channel_length = round($cinfo['reachlen'],1);
            // need different units for drainage area
            $cinfo = getNHDChannelInfo($this->nhd_db, $comid, $this->nhd_segments, 'mi', $this->debug);
            $this->drainage_area = round($cinfo['drainage_area'],2);
            // land use info
            $this->nlcd_landuse = getNHDLandUse($this->nhd_db, $this->nhd_segments, $this->area_units, $this->debug);
         }
      } else {
        error_log("NHD DB Connection not valid.");
      }
   }

}

?>
