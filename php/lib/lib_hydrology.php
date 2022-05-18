<?php
/**
 * @file
 * Base class definitions for OM model objects.
 */
 
if (!function_exists('green_ampt')) {
   include_once('./lib_hydro.php');
}
global $run_mode, $flow_mode;

class modelObject {
  // ******************************************
  // ****** BEGIN - Settable Properties *******
  // ******************************************
  // object class settings
  var $name = '';
  var $nullvalue = NULL;
  var $debug = 0;
  var $debugmode = 0; // 0-store in string, 1-print debugging info to stderr, 2-print out to stdout, 3-spool to file
  var $cascadedebug = 0;
  var $disabled = 0;
  var $description = '';
  var $objectname = '';
  var $defaultval = 0;
  var $logging = 1;
  var $object_class = 'modelObject'; // will be set externally but could be overridden
  var $intmethod = 0; // interpolation method - 0 = linear, 1 - stair step
  var $units = 2; // Units 1 - SI, 2 - EE
  var $component_type = 1;
  var $cache_log = 1; // store variable state log in an external file?  Defaults to 1 for graph and report objects, 0 for others
  var $the_geom = ''; // geography holder
  var $geomtype = 1; // 1 - point, 2 - line, 3 - polygon
  var $geomformat = 0; // 0 - WKT, 1 - WKB, 2 - file, 3 - KML
  var $geomx = 0; // longitude
  var $geomy = 0; // longitude
  var $log2db = 1; 
   // 0 - store log in memory, 1 - store log in temp db table (should save memory, but may slow things down due to frequent db writes)
   // equation execution hierarchy, if conflict
   // 0 is default, least favored, unlimited number
  var $run_mode = 0; // for operational modelControl broadcast, or linear linkage
  var $flow_mode = 1; // for meteorology/landuse modelControl broadcast, or linear linkage
  var $mode_global = FALSE; // whether or not to use the global run and flow modes or to allow override (via prop links, broadcasts, etc)
  var $cacheable = 1; 
  // ******************************************
  // ******* END - Settable Properties ********
  // ******************************************
  // Runtime Settings
  var $componentid = 0; // to be set by a model container at run-time to provide a unique numerical identifier
  var $scenarioid = -1; // a way of associating objects that exist in the same "world" - equiv to scenarioid in database
  var $parentobject = -1; // link to parent, if this object is contained by another (for use by operators only)
  var $vars = array();
  var $setvarnames = array();
  var $childstatus = array();
  var $recreate_list = ''; // csv list that force an object create() call when changed
  var $timer; /* Time Keeping object */
  var $exectime = 0; // cumulative list of time to execute this component
  var $meanexectime = 0; // cumulative divided by # of timesteps (done at finish() routine)
  var $stepstart = 0; // pointer for calculating $exectime
  var $thisdate = '';
  var $inputs = array(); /* array containing object references for upland entities */
  var $tsin = array(); /* array containing time series inputs */
  var $logtable = array();
  var $log_geom = 0; // save space in the log by NOT logging the geometry column
  var $lookups = array();
  var $processors = array();
  var $execlist = array();
  var $loglist = array();
  var $column_defs = null;
  var $data_cols = array(); // this will be used to populate the log list
  var $arData = array(); // subs for state array in other objects (parent object)
  // names of publicly accessible variables, to speed querying without having to reinstantiate the entire object
  var $compnames = array();
  var $inputnames = array();
  var $procnames = array();
  var $propnames = array();
  // end publicly accessible variables
  // permissions
  var $groupid = 2;
  var $operms = 7;
  var $gperms = 4;
  var $pperms = 0;
  // end permissions
  var $debugfile = '';
  var $maxdebugfile = 20971520; // max size of 20Mb
  var $outdir = '.';
  var $outurl = '';
  var $imgurl = '';
  var $logfile = '';
  var $logLoaded = 0; // set after logging begins, or after log is restored from file/db
  var $goutdir = '';
  var $gouturl = '';
  var $fileformat = 'unix';
  var $strform = '"%s"';
  var $numform = '%10.5f';
  var $delimiter = ',';
  var $state = array(); /* holder for state variables */
  // date information for lookups, other conditional entities
  var $year = 1970;
  var $month = 1;
  var $day = 1;
  var $hour = 1;
  var $weekday = 1;
  var $dt = 60.0;
  var $logerrors = 1;
  var $errorstring = '';
  var $errorfile = '';
  var $debugstring = '';
  var $outstring = '';
  var $graphstring = '';
  var $loggable = 0; // is this an entity with "loggable" data?  Only really relevant for sub-components
  // set this to 0 if it is something like a QueryWizardComponent which has no data to log
  var $strict_log = 1;
  // this is only valid currently for the modelContainer, but may later be used for other types of modelObjects
  var $components = array();
  var $compexeclist = array();
  // a db connection
  var $listobject = -1;
  // cache for formatted form variables
  var $formvars = array();
  var $rvars = 0;
  var $wvars = 0;

  var $dbtblname = '';
  var $tblprefix = 'tmp_';
  var $is_datasource = 0; // by default, this will not act as data source when it is a subcomp
  var $datasources = array();
  var $dbcolumntypes = array();
  var $logformats = array();
  var $log_headers_sent = 0; // always zero at the beginning of the model run
  var $bufferlog = 0; // allow list object to buffer log data sent during a run (1 will increase performance)
  var $logRetrieved = 0; // this gets set to 1 after a retrieval from db to avoid redundant retrievals
  var $exec_hierarch = 0;
  // filenice object
  var $fno = -1;
  // temp file stuff
  var $platform = 'unix'; // valid values are 'dos', 'unix'
  var $tmpdir = '/tmp'; // path to the temporary dir writeable by this process
  var $basedir = '';
  var $tmp_files = array();
  var $shellcopy = 'cp'; // the copy command to be used in the shell
  var $sessionid = 0; // variable to use to set data for separate model runs, will be set to the id of the containing model
  var $startdate = '';
  var $enddate = '';
  var $starttime = '';
  var $endtime = '';
  var $restrictdates = 0;
  var $iscontainer = 0; // only true for objects of type "model container" or it's subclasses
  // indicator of which variabels are to be serialized/unserialized when model is awakened/saved (CSV format)
  var $serialist = '';
  var $reportstring = '';
  // property descriptions for viewing/editing components
  var $prop_desc = array();
  var $multivar = 0; // this is for sub-components, is the variable a "multivar", in other words, does it create more than one?
  var $multivarnames = array(); // for use if multivar
  // json object loading enabled.
  var $json2d = TRUE;

  function init() {
    if ($this->debug) {
       $this->logDebug("Initializing $this->name <br>");
    }
    //error_log("Initializing $this->name ");
    $this->setState();
    $this->subState();
    // set up state variables for any public vars that are not already instantiated
    foreach ($this->getPublicProps() as $thisvar) {
       if (!in_array($thisvar, array_keys($this->state))) {
          if ($this->debug) {
             $this->logDebug("Adding $thisvar to viewable state variables.<br>");
          }
          if (property_exists(get_class($this), $thisvar)) {
             $this->state[$thisvar] = $this->$thisvar;
          }
       }
    }
    if ($this->debug) {
       $sv = $this->state;
       if (isset($sv['the_geom'])) {
          $sv['the_geom'] = substr($sv['the_geom'],0, 128);
       }
       $this->logDebug("PublicProps values initialized on $this->name: " . print_r($this->state,1) . " <br>");
       $this->logDebug("Initializing processors on $this->name <br>");
    }
    
    // set this before we initialize the processors, since they will rely upon it
    // they wil call it transparently
    $this->childHub = new broadCastHub;
    $this->childHub->parentname = $this->name;
    
    foreach ($this->processors as $procname => $thisproc) {
       // set state vars for this proc, so that it knows what it will be able to gain access to
       $thisproc->arData = $this->state;
       $thisproc->init();
       // experiment
    }
    if (!is_object($this->parentobject)) {
       // only do this for standalone objects, not sub-components
       $mem_use = (memory_get_usage(true) / (1024.0 * 1024.0));
       $mem_use_malloc = (memory_get_usage(false) / (1024.0 * 1024.0));
       //error_log("Memory Use after init() on $this->name = $mem_use ( $mem_use_malloc )<br>\n");
    }

    // set up data type for dbexpropt
    // set a name for the temp table that will not hose the db
    $targ = array(' ',':','-','.');
    $repl = array('_', '_', '_', '_');
    $this->setDBTablePrefix();
    $this->dbtblname = $this->tblprefix . 'datalog';
    $this->setDataColumnTypes();

    // order all operations on this entity
    $this->orderOperations();
    

  }
  
  function setDBTablePrefix() {
    $this->tblprefix = str_replace($targ, $repl, "tmp$this->componentid" . "_"  . str_pad(rand(1,99), 3, '0', STR_PAD_LEFT) . "_");
  }
  
  function setPropDesc() {
  }

  function addDataSource($procname) {
    if (!in_array($procname, $this->datasources)) {
       $this->datasources[] = $procname;
    }
  }

  function addSerialist($varname) {
    $slist = explode(',', $this->serialist);
    if (!in_array($varname, $slist)) {
       $slist[] = $varname;
    }
    $this->serialist = implode(',', $slist);
  }

  function evaluate() {
  }

  function systemLog($mesg, $status_flag = 1) {
    if (is_object($this->parentobject)) {
       $this->parentobject->systemLog($mesg, $status_flag);
    }
  }
    
  function cleanUp() {
    // remove all the temp tables associated with this object
    if (is_object($this->listobject)) {
       if ($this->listobject->tableExists($this->dbtblname)) {
          $this->listobject->querystring = "  drop table $this->dbtblname ";
          $this->listobject->performQuery();
       }
    }
    
    foreach ($this->processors as $thisproc) {
       // clean up processors if they have the method
       if (is_object($thisproc)) {
          if (method_exists($thisproc, 'cleanUp')) {
             $thisproc->cleanUp();
             unset($thisproc);
          }
       }
    }
  }

  function varsToSetOnParent($format='vals') {
    $vars = array();
    if (!is_array($this->wvars)) {
      $this->wvars = array();
    }
    foreach ($this->wvars as $thisvar) {
       if ($this->debug) {
          $this->logDebug("This var will create $this->name" . "_" . "$thisvar on parent.<br>\n");
       }
       switch ($format) {
          case 'vals':
          // return the values only
          $vars[] = $this->getParentVarName($thisvar);
          break;
          
          case 'map':
          // return local names and parent names
          $vars[$thisvar] = $this->getParentVarName($thisvar);
          break;
          
          default:
          // return the values only
          $vars[] = $this->getParentVarName($thisvar);
          break;
       }
       
    }
    return $vars;
  }

  function writeToParent($vars = array(), $verbose = 0) {
    if (count($vars) == 0) {
       $vars = is_array($this->wvars) ? $this->wvars : array();
    }
    if ($this->debug) {
       $this->logDebug("writeToParent() called on $this->name ");
       //error_log("writeToParent() called on $this->name ");
    }
    if (is_object($this->parentobject)) {
       foreach ($vars as $thisvar) {
          if ($this->debug) {
             $this->logDebug("Writing $thisvar on parent as " . $this->getParentVarName($thisvar) . " = " .  $this->state[$thisvar] . "<br>\n");
             error_log("Writing $thisvar on parent as " . $this->getParentVarName($thisvar) . " = " .  $this->state[$thisvar] . "<br>\n");
             $tstate = $this->state;
             $tstate['the_geom'] = 'geom huidden';
             error_log("State variables = " .  print_r($tstate,1) . "\n");
          }
          $this->parentobject->setStateVar($this->getParentVarName($thisvar), $this->state[$thisvar]);
          if ($this->debug and $verbose) {
             $this->logDebug(" $thisvar = " . $this->state[$thisvar] . "<br>\n");
          }
       }
    }
  }

  function initOnParent() {
  //error_log("$this->name calling initOnParent() ");
    if (is_object($this->parentobject)) {
       //error_log("$this->name - parent exists - checking for varsToSetOnParent() ");
       foreach ($this->varsToSetOnParent('map') as $thisvar => $parentvar) {
          if (isset($this->column_defs[$thisvar])) {
             $thistype = $this->column_defs[$thisvar]['dbcolumntype'];
             $log_format = $this->column_defs[$thisvar]['log_format'];
             $loggable = $this->column_defs[$thisvar]['loggable'];
          } else {
             $thistype = 'float8';
             $log_format = '%s';
             $loggable = 1;
          }
          //$this->parentobject->setSingleDataColumnType($parentvar, $thistype, NULL, $loggable, 1, $log_format);
          $this->parentobject->setSingleDataColumnType($parentvar, $thistype, $this->getProp($thisvar), $loggable, 1, $log_format);
          if ($this->debug) {
             $this->logDebug("$this->name calling on parent - > setSingleDataColumnType($parentvar, $thistype, getProp($thisvar), $loggable, 1, $log_format)");
             $this->logDebug("$this->name Setting $parentvar on parent as " . $this->getProp($thisvar));
          }
       }
    }
  }

  function setSingleDataColumnType($thiscol, $thistype = 'float8', $defval = NULL, $loggable = 1, $overwrite = 0, $logformat='%s') {
  if ($this->debug) {
    $this->logDebug("called: setSingleDataColumnType( $thiscol, type = $thistype , defaval = $defval, loggable = $loggable , $overwrite , $logformat)");
  }
    if (strtolower($thistype) == 'guess') {
       if (is_object($this->listobject)) {
          if (method_exists($this->listobject, 'guessDataType')) {
             $guess = $this->listobject->guessDataType($defval);
             $thistype = $guess['vtype'];
          }
       }
    }
    if (trim($thiscol) == '') {
       error_log("Blank column name submitted for $this->name setSingleDataColumnType(thiscol = $thiscol, thistype = $thistype, defval = $defval, loggable = $loggable , overwrite = $overwrite , $logformat='%s'");
       return;
    }
    // set up structure for local column storage
    // this should take the place of all of these in the future: logformats, datacols, dbcolumntypes
    if ( (!isset($this->column_defs)) or (!is_array($this->column_defs)) ) {
       $this->column_defs = array();
    }
    // set up column formats for appropriate outputs to database
    if ( (!isset($this->dbcolumntypes)) or (!is_array($this->dbcolumntypes)) ) {
       $this->dbcolumntypes = array();
    }
    if ( (!isset($this->logformats)) or (!is_array($this->logformats)) ) {
       $this->logformats = array();
    }
    if ( (!isset($this->data_cols)) or (!is_array($this->data_cols)) ) {
       $this->data_cols = array();
    }
    // added RWB 5/17/2015 - improve loading into session table by 
    // setting the default
    if (!isset($this->column_defs[$thiscol]) or $overwrite) {
       $this->column_defs[$thiscol] = array(
          'log_format'=>$logformat,
          'loggable'=>$loggable,
          'dbcolumntype'=>$thistype,
          'default'=>$defval
       );
    }
    if (!isset($this->state[$thiscol]) or $overwrite) {
       $this->setStateVar($thiscol, $defval);
    }
    if (!isset($this->dbcolumntypes[$thiscol]) or $overwrite) {
       if (trim($thiscol) <> "") {
          $this->dbcolumntypes[$thiscol] = $thistype;
          if ($loggable and (strlen($thistype) > 0) and (strtolower($thistype) <> 'null') ) {
             $this->data_cols[] = $thiscol;
             if ($this->debug) {
                $this->logDebug("Adding $thiscol to loggable columns on $this->name with type $thistype and default value $defval");
             }
          }
          $this->state[$thiscol] = $defval;
          if ($overwrite and in_array($thiscol, $this->data_cols) and !$loggable) {
             if ($this->debug) {
                $this->logDebug("Removing $thiscol from loggable columns on $this->name");
             }
             unset($this->data_cols[array_search($thiscol, $this->data_cols)]);
          }
       }
    }
    if (!$loggable) {
       if ($this->debug) {
          $this->logDebug("Removing $thiscol from loggable columns on $this->name");
       }
       unset($this->data_cols[array_search($thiscol, $this->data_cols)]);
       unset($this->dbcolumntypes[$thiscol]);
       unset($this->logformats[$thiscol]);
    }
  }

  function addDataColumnType($colname, $samplevalue = 0, $overwrite = 0, $forcetype = '') {
    if ( (!isset($this->data_cols)) or (!is_array($this->data_cols)) ) {
       $this->data_cols = array();
    }
    if (!(trim($colname) == '')) {
       if (in_array($colname, $this->dbcolumntypes)) {
          $exists = 1;
       } else {
          $exists = 0;
       }

       if ($exists and !$overwrite) {
          return;
       }

       if (strlen($forcetype) > 0) {
          // we have been given a type to make this, so do not guess
          $type = $forcetype;
       } else {
          $type = 'varchar';
       }

       $this->dbcolumntypes[$colname] = $type;
       if ( (strlen($type) > 0) and (strtolower($type) <> 'null') and !(in_array($colname, $this->data_cols)) ) {
          $this->data_cols[] = $colname;
          $this->logformats[$colname] = '%s';
       }
    }
  }

  function setBaseTypes() {
    // set up column formats for appropriate outputs to database
    if ( (!isset($this->data_cols)) or (!is_array($this->data_cols)) ) {
       $this->data_cols = array();
    }
    if ( (!isset($this->dbcolumntypes)) or (!is_array($this->dbcolumntypes)) ) {
       $this->dbcolumntypes = array();
    }
    if ( (!isset($this->logformats)) or (!is_array($this->logformats)) ) {
       $this->logformats = array();
    }

    $basetypes = array(
             'name'=>'varchar(' . intval(2.0 * strlen($this->name) + 1) . ')',
             'description'=>'varchar(' . intval(2.0 * strlen($this->description) + 1) . ')',
          //   'objectname'=>'varchar(' . intval(2.0 * strlen($this->objectname) + 1) . ')',
             'componentid'=>'varchar(128)',
             'subshedid'=>'varchar(128)',
             'dt'=>'float8',
             'month'=>'float8',
             'day'=>'float8',
             'year'=>'float8',
             'jday'=>'float8',
             'thisdate'=>'date',
             'time'=>'timestamp',
             'timestamp'=>'bigint',
             'modays'=>'bigint',
             'run_mode'=>'float8',
             'flow_mode'=>'float8',
             'season'=>'varchar(8)'
    );
    $dcs = array('thisdate', 'month', 'day', 'year', 'jday', 'week', 'timestamp', 'modays', 'run_mode', 'flow_mode');
    foreach ($dcs as $thisdc) {
       $this->data_cols[] = $thisdc;
    }
    
    foreach($basetypes as $key=>$val) {   
       $this->dbcolumntypes[$key] = $val;
    } 
    if ($this->debug) {
       $this->logDebug("MERGE RESULT: " . print_r(array_merge($this->dbcolumntypes, $basetypes),1) . " <br>");
       $this->logDebug("UNIQUE RESULT: " . print_r($thisarray,1) . " <br>");
    }

  }
  function setDataColumnTypes() {

    $this->setBaseTypes();
    
    $logtypes = array(
             'name'=>'%s',
             'description'=>'%s',
           //  'objectname'=>'%s',
             'componentid'=>'%u',
             'dt'=>'%u',
             'month'=>'%u',
             'day'=>'%u',
             'jday'=>'%u',
             'year'=>'%u',
             'thisdate'=>'%s',
             'time'=>'%s',
             'timestamp'=>'%s',
             'modays'=>'%u',
             'run_mode'=>'%u',
             'flow_mode'=>'%u',
             'season'=>'%u'
    );
    
    // now, go through and see if any sub-components have db types set
    foreach ($this->processors as $thisproc) {
       
       if (property_exists($thisproc, 'value_dbcolumntype') and !empty($thisproc->value_dbcolumntype) and !($thisproc->value_dbcolumntype == 'auto')) {
          // this does not work, since the logtypes is looking for string format, NOT a db column type
          //$logtypes[$thisproc->name] = $thisproc->value_dbcolumntype;
          // howwever, this should be OK
          if (isset($thisproc->defaultval)) {
             $this->setSingleDataColumnType($thisproc->name, $thisproc->value_dbcolumntype, $thisproc->defaultval, $thisproc->loggable);
             //error_log("Calling setSingleDataColumnType($thisproc->name, $thisproc->value_dbcolumntype, $thisproc->defaultval, $thisproc->loggable);");
          } else {
             $this->setSingleDataColumnType($thisproc->name, $thisproc->value_dbcolumntype, NULL, $thisproc->loggable);
             error_log("No Default Value for $thisproc->name -- Calling setSingleDataColumnType($thisproc->name, $thisproc->value_dbcolumntype, NULL, $thisproc->loggable);");
          }
       }
       
       
       if (method_exists($thisproc, 'setDataColumnTypes')) {
          $thisproc->setDataColumnTypes();
       }
       
       if ( (!($thisproc->loggable === 0)) and !in_array($thisproc->name, $this->data_cols)  and !(trim($thisproc->name) == '')) {
          
          $this->data_cols[] = $thisproc->name;
       }
    }
    $thisarray = array_unique(array_merge($this->logformats, $logtypes));

    $this->logformats = $thisarray;
    // log to postgres if connection is set
    if (is_object($this->listobject) and ($this->log2db == 1)) {
       if ($this->listobject->tableExists($this->dbtblname)) {
          $this->listobject->querystring = "  drop table $this->dbtblname ";
          $this->listobject->performQuery();
       }
    }
    // clean up data_cols based on definitions in the column_defs array
    if (is_array($this->column_defs)) {
       foreach ($this->column_defs as $thiscol => $def) {
          $loggable = $def['loggable'];
          $dbcolumntype = $def['dbcolumntype'];
          $logformat = $def['log_format'];
          if (!$loggable) {
             if (in_array($thiscol, $this->data_cols)) {
                //error_log("Removing $thiscol from loggable columns on $this->name");
                unset($this->data_cols[array_search($thiscol, $this->data_cols)]);
             }
             if (in_array($thiscol, $this->logformats)) {
                //error_log("Removing $thiscol from log formats on $this->name");
                unset($this->logformats[array_search($thiscol, $this->logformats)]);
             }
             if (in_array($thiscol, $this->dbcolumntypes)) {
                //error_log("Removing $thiscol from db types on $this->name");
                unset($this->dbcolumntypes[array_search($thiscol, $this->dbcolumntypes)]);
             }
          } else {
             $this->data_cols[] = $thiscol;
             $this->log_formats[$thiscol] = $logformat;
             $this->dbcolumntypes[$thiscol] = $dbcolumntype;
          }
       }
    }
  }

  function getParentVarName($thisvar) {
    return $this->name . "_" . $thisvar;
  }

  function finish() {
    // add post-processing functions here

    // iterate through each sub-processor stored in this object
    if (is_array($this->processors)) {
      foreach ($this->processors as $thisproc) {
        if ($this->debug) {
           $this->logDebug("Finishing $thisproc->name<br>\n");
        }
        if (is_object($thisproc)) {
           if (method_exists($thisproc, 'finish')) {
              $thisproc->finish();
           }
        }
        // show component output reports
        $this->reportstring .= "<b>Reports for: </b>" . $thisproc->name . " <br>\n";
        $avgexec = $thisproc->meanexectime;
        $this->reportstring .= "Avg. exec time: $avgexec \n";
        if (strlen($thisproc->reportstring) > 0) {
           $this->reportstring .= $thisproc->description . "<br>" . $thisproc->reportstring . "<br>";
           $thisproc->reportstring = '';
        }
        // show component error reports
        if (strlen($thisproc->errorstring) > 0) {
           $this->errorstring .= "<b>Errors for: </b>" . $thisproc->name . '<br>';
           $this->errorstring .= $thisproc->description . "<br>" . $thisproc->errorstring . "<br>";
           $thisproc->errorstring = '';
        }
      }
      unset($thisproc);
    }

    if ($this->cache_log) {
      // error_log("Ouputting log values to file for $this->name");
       $this->log2file();
    }
    $this->logDebug("Finished $this->name");
    if ($this->debugmode == 3) {
       $this->debugstring .= "</body></html>";
       $this->flushDebugToFile();
       $this->debugstring = "<b>Debug File for $this->name:</b> <a href=" . $this->outurl . '/' . $this->debugfile . ">Click Here</a><br>";
    }
    if (is_object($this->timer)) {
       
       $this->meanexectime = $this->exectime / $this->timer->steps;
       $this->reportstring .= "<br>\n Final mean Execution time for $this->name = $this->meanexectime <br>\n";
    }
    
    // find and run postProcessing methods 
    $this->postProcessing();

  }
  
  function postProcessing() {
    // tbd
  }

  function wake() {
   $stime = microtime(true);
    if (!isset($this->processors)) {
       $this->processors = array();
    } else {
       // if this thing is already an array, we may be re-awakening, in which case we should NOT overwrite its contents
       if (!is_array($this->processors)) {
          $this->processors = array();
       } else {
          // should we call re-awakening on sub-components?
          /*
          foreach ($this->processors as $thisproc) {
             $thisproc->wake();
          }
          */
       }
    }
    if (is_array($this->data_cols)) {
       $this->data_cols = array_unique($this->data_cols);
    }
    if (!is_array($this->setvarnames)) {
       $this->setvarnames = array();
    }
    if (!is_array($this->column_defs)) {
       $this->column_defs = array();
    }
    $this->state = array();
    $this->execlist = array();
    $this->compexeclist = array();
    $this->inputs = array();
    $this->vars = array();
    $this->logtable = array();
    $this->lookups = array();
    $this->loglist = array();
    $this->datasources = array();
    // set up property descriptions
    $this->prop_desc = array();
    $this->setPropDesc();
    // end prop descriptions
    $this->dbcolumntypes = array();
    if ( (!isset($this->data_cols)) or (!is_array($this->data_cols)) ) {
       $this->data_cols = array();
    }
    $this->logLoaded = 0;
    // things to do before this goes away, or gets stored
    $ser = explode(',', $this->serialist);
    foreach ($ser as $thisvar) {
       if (property_exists($this, $thisvar)) {
          if (!is_array($this->$thisvar) and !is_object($this->$thisvar)) {
             if (strlen($this->$thisvar) > 0) {
                $uvar = unserialize($this->$thisvar);
                $this->$thisvar = $uvar;
                if ($this->debug) {
                   $this->logDebug("$thisvar unserialized to : " . print_r($uvar,1));
                }
                //error_log("$thisvar unserialized to : " . print_r($uvar,1));
             }
          }
       }
    }
    $this->setState();
    $this->subState();
   $etime = microtime(true);
   $this->logDebug("$this->name wake() took " . round($etime - $stime, 5) . " seconds (prec=5)<br>");
  }

  function preProcess() {
    // do nothing here, this is sub-classed where necessary
    foreach ($this->processors as $thisproc) {
       // set state vars for this proc, so that it knows what it will be able to gain access to
       //error_log("$thisproc->name - > preProcess()");
       $thisproc->arData = $this->state;
       $thisproc->preProcess();
    }
  }

  function create() {
    // things to do when this object is first created
    // it must be called AFTER the wake() method, but prior to the init() method
    foreach ($this->processors as $thisproc) {
       // set state vars for this proc, so that it knows what it will be able to gain access to
       $thisproc->create();
    }
  }

  function reCreate() {
    // things to do if something on this object has changed and the user wishes to re-run the create routine
    // defauls to simply calling the classes own create() method, but could perform other house-keeping functions
    $this->create();
  }

  function sleep() {
    // things to do before this goes away, or gets stored
    $ser = explode(',', $this->serialist);
    error_log("sleep() called on $this->name");
    if (is_array($this->data_cols)) {
      error_log("Pre-unique data_cols count = " . count($this->data_cols));
      $this->data_cols = array_unique($this->data_cols);
      error_log("Post-unique data_cols count = " . count($this->data_cols));
      $this->data_cols = array_unique($this->data_cols);
    } else {
      error_log("sleep() method data_cols is not an array");
    }
    foreach ($ser as $thisvar) {
       if (property_exists($this, $thisvar)) {
          $this->$thisvar = serialize($this->$thisvar);
       }
    }
    // blank out components and processors
    $this->processors = array();
    $this->components = array();
    $this->formvars = array();
    $this->inputs = array();
    $this->state = array();
    $this->data_cols = array();
    $this->datasources = array();
    $this->setvarnames = array();
    $this->errorstring = '';
    $this->debugstring = '';
    $this->column_defs = null;
  }
  
  // Functions to facilitate an export/serialization
  public function toArray() {
    $this->removeRecursions();
    $this->debugstring = "";
    return $this->processArray(get_object_vars($this));
  }

  function removeRecursions() {
    // things to do before this gets exported
    unset($this->listobject);
    unset($this->parentobject);
    // @todo: keep this around in case we need it?
    /*
    $ser = explode(',', $this->serialist);
    foreach ($ser as $thisvar) {
       if (property_exists($this, $thisvar)) {
          $this->$thisvar = serialize($this->$thisvar);
       }
    }
    */
  }
    
  private function processArray($array) {
    foreach($array as $key => $value) {
      if (is_object($value)) {
        if (method_exists($value, 'toArray')) {
          $array[$key] = $value->toArray();
        } else {
          $array[$key] = get_object_vars($this);
        }
      }
      if (is_array($value)) {
        $array[$key] = $this->processArray($value);
      }
    }
    // If the property isn't an object or array, leave it untouched
    return $array;
  }
    
  public function __toString() {
    return json_encode($this->toArray());
  }

  function setDebug($thisdebug, $thisdebugmode = -1) {
    $this->debug = $thisdebug;
    if ($thisdebugmode <> -1) {
       $this->debugmode = $thisdebugmode;
    }
    if ($this->cascadedebug) {
       foreach($this->processors as $thisproc) {
          $thisproc->setDebug($this->debug, $this->debugmode);
       }
    }
  }

  function setBuffer($bufferlog) {
    $this->bufferlog = $bufferlog;
    foreach($this->processors as $thisproc) {
       if (method_exists($thisproc, 'setBuffer')) {
          $thisproc->setBuffer($this->bufferlog);
       }
    }
  }

  function setSimTimer($thistimer) {
    $this->timer = $thistimer;
    $this->dt = $thistimer->dt;
    $this->startdate = $thistimer->thistime->format('Y-m-d');
    $this->enddate = $thistimer->endtime->format('Y-m-d');
    // properly format start and end time
    $this->starttime = $thistimer->thistime->format('Y-m-d H:i:s');
    $this->endtime = $thistimer->endtime->format('Y-m-d H:i:s');
    
    if ($this->debug) {
       $this->logDebug("Setting Timer for $this->name <br>");
       #$this->logDebug($this->timer);
       #$this->logDebug($thistimer);
    }
    #$this->logDebug("<br>");
    $this->logDebug("Setting Timer for processors $this->name <br>");
    #$this->logDebug($this->processors);
    #$this->logDebug("<br>");
    if (is_array($this->processors)) {
       foreach ($this->processors as $thisop) {
          $thisop->setSimTimer($thistimer);
       }
    }
    if (is_array($this->components)) {
       foreach ($this->components as $thisop) {
          $thisop->setSimTimer($thistimer);
       }
    }
  }

  function setCompID($thisid) {
    $this->componentid = $thisid;
    // set a name for the temp table that will not hose the db
    $targ = array(' ',':','-','.');
    $repl = array('_', '_', '_', '_');
    $this->tblprefix = str_replace($targ, $repl, "tmp$this->componentid" . "_" . str_pad(rand(1,99), 3, '0', STR_PAD_LEFT) . "_");
    $this->dbtblname = $this->tblprefix . 'datalog';
  }

  function setState() {
    // initialize the state array
    // any object properties that are to be visible to other components, or even to 
    // sub-components on this object must be initialized in the state variable here
    // unless they are explicitly set in a sub-component processor, otherwise, they will 
    // be invisible and result as null
    $this->state = array();
    if (!is_array($this->wvars)) {
       $this->wvars = array();
    }
    if (!is_array($this->rvars)) {
       $this->rvars = array();
    }

    if (is_array($this->processors)) {
       foreach (array_keys($this->processors) as $thisop) {
          $this->state[$thisop] = $this->processors[$thisop]->defaultval;
       }
    } else {
       $this->processors = array();
    }
    if (is_array($this->inputs)) {
       foreach (array_keys($this->inputs) as $thisop) {
          $this->state[$thisop] = @$this->$thisop;
       }
    } else {
       $this->inputs = array();
    }
    // handle global mode variables 
    global $run_mode, $flow_mode;
    //error_log("$this->name Checking Modes: $run_mode, $flow_mode");
    if (!($run_mode === NULL) and $this->mode_global) {
      // if this is not the simulation root, and global requested, grab them 
      $this->flow_mode = $flow_mode;
      $this->run_mode = $run_mode;
      $this->state['run_mode'] = $this->run_mode;
      $this->state['flow_mode'] = $this->flow_mode;
    } else {
      if ($run_mode === NULL) {
        // this is the first simulation entity, so set the global values
        $run_mode = $this->run_mode;
        $flow_mode = $this->flow_mode;
        //error_log("Model Controller Setting run_mode = $run_mode, flow_mode = $flow_mode");
      }
    }
    //error_log("Model Controller Setting run_mode = $run_mode, flow_mode = $flow_mode");
  }

  function subState() {
  // stub for sub-classes to to special operations within the setState() routine
    $this->initOnParent();
  }

  function setStateVar($varname, $varvalue) {
    if (!is_array($this->setvarnames)) {
       $this->setvarnames = array();
    }
    // sets a specific state variable to a specific value
    $this->state[$varname] = $varvalue;
    // compile a list of all variables set by this method
    if (!in_array($varname, $this->setvarnames)) {
       $this->setvarnames[] = $varname;
    }
  }


  function appendStateVar($varname, $varvalues, $action = 'append', $method = 'guess') {
    //error_log("appendStateVar($varname, $varvalues, $action = 'append', $method = 'guess') called");
    if (!is_array($varvalues)) {
       $varvalues = array($varvalues);
    }
    
    if (!isset($this->state[$varname])) {
       $this->state[$varname] = NULL;
    }
    
    switch ($action) {
       case 'refresh':
          // means that we want to start with only the values passed at this time, so clear previous values
          if (is_numeric($varvalues[0])) {
             $this->state[$varname] = 0.0;
          } else {
             $this->state[$varname] = '';
          }
       break;
    }
    
    // default method is ADD if numeric, replace if string
    // user can set methods in function call
    foreach ($varvalues as $inval) {
       // the following cases assume that all variables are scalar.  
       // we need to adjust this to work with array type variables as well.
       // 
       if (!($inval === NULL)) {
          if ($method == 'guess') {
             if (is_numeric($inval)) {
                if( $this->state[$varname] === NULL) {
                   $this->state[$varname] = 0;
                }
                if (is_array($this->state[$varname])) {
                  error_log("Malformed broadcast variables, array $varname on $this->name ($this->componentid)" );
                  $this->systemLog("Malformed broadcast variables, array $varname on $this->name ($this->componentid)");
                  error_log("Adding $inval to " . $this->state[$varname] );
                  error_log("All values:" . print_r($varvalues,1) );
                }
                $this->state[$varname] += $inval;
             } else {
                $this->state[$varname] = $inval;
             }
          } else {
             switch ($method) {
                case 'replace':
                   $this->state[$varname] = $inval;
                break;
                
                case 'stringappend':
                   $this->state[$varname] .= $inval;
                break;
                
                case 'numappend':
                   if( $this->state[$varname] === NULL) {
                      $this->state[$varname] = 0;
                   }
                   $this->state[$varname] += $inval;
                break;
             }
          }
       }
       if ($this->debug) {
          if (strlen($this->state[$varname]) < 200) {
             $this->logDebug("updated $varname = " . $this->state[$varname] . " Appended from Inputs: " . print_r($varvalues,1) . "<br>\n");
          }
       }
    }
  }
  
  /**
   * Set any property on the object.  3 main types of properties are supported:
   * - A simple un-typed attribute of the class itself (i.e., declared with 'var $propname;' in the class body.
   * - A sub-component type property.  These are auto-detected by matching their name in the array $this->processors[] 
   * - A JSON string object.  This calls method setPropJSON2d which can theoretically handle a complex JSON object representing the entire object class itself. See modelObject::setPropJSON2d()
   * Pane for annual reporting submittal.
   */
  function setProp($propname, $propvalue, $view = '') {
    // sets a specific state variable to a specific value
    switch ($view) {
      case 'json-2d':
        //error_log("Props of " . get_class($this) . " = " . print_r(get_class_vars(get_class($this)),1));
        if (property_exists($this, 'json2d')) {
          $this->setPropJSON2d($propname, $propvalue, $view);
        } else {
          // standard handling 
          error_log("JSON2d handling not enabled for $propname of class " . get_class($this));
        }
      break;
      
      default:
        if ($this->debug) {
           $this->logDebug("Trying to set $propname to " . print_r((array)$propvalue,1) . " on " . $this->name);
        }
        //error_log("Trying to set $propname to $propvalue on " . $this->name);
        $this->setClassProp($propname, $propvalue, $view);
      break;
    }
    return;
    // @todo: processors (subcomps) over-ride locals
    if (isset($this->processors[$propname])) {
      $this->processors[$propname]->setProp($propname, $propvalue, $view);
    } else {
      $this->setClassProp($propname, $propvalue, $view);
    }
  }
  
  
  /**
   * Set all properties on an object, simple and processor-type 
   * Shoudl accept a JSON string that is parsed into an array of objects 
   * formatted in OpenMI style 
   */
  function setPropJSON2d($propname, $propvalue, $view = '') {
    // this can set all attributes of an object class in one lloop
    //if (get_class($this) == 'Equation') {
    //if ($this->json2d) {
    // expects openMI style objects in json format 
    //error_log("Calling setPropJSON2d($propname)");
    $base_types = array("textField", "matrix", "array", "table"); // these can be set on base object all others must be subcomps
    $raw_json = $propvalue;
    //error_log("RAW JSON = " . $raw_json);
    $raw_props = json_decode($raw_json, TRUE);
    //error_log("JSON raw_props names = " . print_r(array_keys($raw_props),1));
    // this is being called recursively, or by another routine that has already translated from json 
    if ($view == 'json_decoded') {
      $json_props = $propvalue;
    } else {
      $json_props = json_decode($propvalue, TRUE);
    }
    foreach ($json_props as $pname => $pvalue) {
      //error_log("setPropJSON2d: this->setClassProp($pname)");
      if ($pname == 'object_class') {
        continue;
      }
      if (
        property_exists($this, $pname) 
        and 
        ( 
          (!isset($pvalue['object_class']))
          or in_array($pvalue['object_class'], $base_types)
        )
      ) {
        $this->applyJSONPropArray($pname, $pvalue);
      } else {
        $prop = $this->applyJSONComponentArray($pname, $pvalue);
        error_log("Final prop object " . $prop->name . " of class " . get_class($prop) );
      }
    }
  }
  
  function applyJSONComponentArray($pname, $pvalue) {
    // this is not a property on the base class, look for processors
    $skips = array('id', 'om_element_connection', 'host');
    // @TODO: we may handle om_element_connection as an entry in the map_model_linkages table 
    
    //error_log("Notice: Looking to add $pname as processor ");
    if (!is_array($pvalue)) {
      error_log("Warning: Skipping component $pname because json did not have array. ");
      return;
    }
    if (!isset($pvalue['object_class']) ) {
      error_log("Warning: Skipping component $pname because json did not have object_class. ");
      return;
    }
    // @todo: include plumbing from set_subprop.php to handle robust json property setting.
    // Does a sub-comp of this name exist? Or, is this an object_class change?
    $prop = isset($this->processors[$pname]) ? $this->processors[$pname] : FALSE;
    $object_class = $pvalue['object_class'];
    if (is_object($prop)) {
      if ($prop->object_clas <> $object_class) {
        $prop = FALSE;
      }
    }
    // if either is true, we insert a brand new object 
    if ($prop === FALSE) {
      if (!class_exists($object_class)) {
        error_log("Error: Object class $object_class can not be found. Skipping $pname .");
        return;
      }
      if (
        is_subclass_of($object_class, 'modelObject')
        or is_subclass_of($object_class, 'modelSubObject')
      ) {
        $syobj = new $object_class;
        $this->addOperator($pname, $syobj);
        //error_log("Added $pname as component type $object_class .");
        // re-retrieve to make sure that the object is not cloned.
        $prop = $this->processors[$pname];
      } else {
        error_log("$object_class is not an addable component");
      }
    }
    // recursively calls setPropJSON2d on the subcomp.
    if (method_exists($prop, 'setPropJSON2d') ) {
      error_log("Updating properties on $pname (type = $object_class) with setPropJSON2d .");
      $prop->setPropJSON2d($pname, $pvalue, 'json_decoded');
    } else {
      error_log("Can not locate method setPropJSON2d() on $prop->name");
    }
    // Now, we should have an object set in $this->processors
    // Load the object and call 
    return $prop;
  }
  
  function applyJSONPropArray($pname, $pvalue) {
    if (!is_array($pvalue)) {
      // handle normal attributes
      $this->setClassProp($pname, $pvalue, "");
    } else {
      // handle openmi structured attribute
      //error_log("object_class: $pvalue[object_class]");
      switch ($pvalue['object_class']) {
        //default, flat properties are all for now.
        case NULL:
        case 'textField':
          $this->setClassProp($pvalue['name'], $pvalue['value'], "");
          //error_log("Exec: this->setClassProp($pvalue[name], $pvalue[value], \"\")");
        break;
        case 'table':
        case 'array':
        case 'matrix':
        if (is_array($pvalue['value'])) {
          $this->setClassProp($pvalue['name'], $pvalue['value'], "array");
        } else {
          error_log("Error: Property $pvalue[name] is object_class $pvalue[object_class] but is not valid array");
        }
          //error_log("Exec: this->setClassProp($pvalue[name], $pvalue[value], \"\")");
        break;
        default:
        // Handle more complex object properties 
        error_log("Warning: Skipping $pname -- setProp cannot handle json-2d with object_class = " . $pvalue['object_class']);
        break;
      }
    }
  }
  
  function setClassProp($propname, $propvalue, $view = '') { 
    if (property_exists($this, $propname)) {
       $this->$propname = $propvalue;
       if ($this->debug) {
          $this->logDebug("Trying to set $propname to " . print_r((array)$propvalue,1) . " on " . $this->name);
       }
    } else {
       if ($this->debug) {
          $this->logDebug("Property $propname does not exist on class " . get_class($this));
       }
    }
  }

  function getProp($propname, $view = '') {
    // sets a specific state variable to a specific value
    if ($this->debug) {
       //error_log("Trying to get $propname, $view from " . $this->name);
    }
    if (isset($this->processors[$propname])) {
       if ($this->debug) {
          $this->logDebug("Property defined sub-comp - checking Sub-component $propname : $view on " . $this->name);
       }
       return $this->processors[$propname]->getProp($propname, $view);
       //return $this->processors[$propname]->getProp($view);
    } else {
       if (property_exists(get_class($this), $propname)) {
          if ($this->debug) {
             //error_log("Returning $this->name -> $propname = " . $this->$propname);
          }
          return $this->$propname;
       } else {
          // check to see if the view is the same as the property name
          if (isset($this->state[$propname])) {
             if ($this->debug) {
                error_log("Returning State Variable $propname " . $this->state[$propname]);
             }
             return $this->state[$propname];
          }
          if (property_exists(get_class($this), $view)) {
             if ($this->debug) {
                error_log("Returning $this->name -> $view = " . $this->$view);
             }
             return $this->$view;
          }
          if ($this->debug) {
             error_log("Property $this->name -> $propname does not exist on class " . get_class($this));
          }
          return FALSE;
       }
    }
  }

  function getObjectDependencies() {
    // return a list of all known objects that supply information that this uses (for runtime hierarchy)
    $dependencies = array();
    if ($this->debug) {
       $this->logDebug("<br>$this->name called getObjectDependencies <br>");
    }
    foreach (array_keys($this->inputs) as $thisinputname) {
       if ($this->debug) {
          $this->logDebug("Checking $thisinputname ");
       }
       if (trim($thisinputname) <> '') {
          foreach ($this->inputs[$thisinputname] as $thisinput) {
             $thisinobj = $thisinput['object'];
             if ($this->debug) {
                $ci = $thisinobj->componentid;
                $this->logDebug(".. Adding $ci ");
             }
             $dependencies[] = $thisinobj->componentid;
          }
       } else {
          if ($this->debug) {
             $this->logDebug("<br>Null Input Found - will not process <br>");
          }
       }
       if ($this->debug) {
          $this->logDebug("<br>Finished checking $thisinputname <br>");
       }
    }
    if ($this->debug) {
       $this->logDebug("<br>Returning " . count($dependencies) . "from getObjectDependencies <br>");
    }
    // now, should check all broadcast hubs that I listen on for dependencies...
    return array_unique($dependencies);
  }

  function getLog($startdate = '', $enddate = '', $variables = array(), $scenario = -1) {
    // gets all log values
    
    switch ($this->log2db) {
       
       case 0:
       // do nothing, since the logtable is already there
       break;
       
       case 1:
       if (!$this->logRetrieved) {
          // if logRetrieved is false, we have to actually grab it from the database log
          // we also should check here for a desired scenario, and also specific variables
          // must retrieve log from data table to array
          $qs = " select * from $this->dbtblname ";
          $this->logtable = array();
          if ($this->restrictdates) {
             // use only a narrow date field, also must have valid start and end dates set
             if ( (strlen($this->startdate) > 0) and (strlen($this->enddate) > 0) ) {
                $qs .= " where thisdate >= '$this->startdate' and thisdate <= '$this->enddate' ";
             }
          }
          if (is_object($this->listobject)) {
             if ($this->listobject->tableExists($this->dbtblname)) {
                $this->listobject->querystring = $qs;
                if ($this->debug) {
                   $this->logDebug($qs);
                }
                $this->listobject->performQuery();
                // sets 
                $this->logtable = $this->listobject->queryrecords;
             }
          }
          $this->logRetrieved = 1;
       }
       break;
       
       case 2:
       // flush the remnants of the memory log to the file, then retrieve the file
       $this->flushMemoryLog2file();
       $this->logFromFile();
       $this->logRetrieved = 1;
       break;
    }

    return $this->logtable;
  }

  function openTempFile($filename, $mode = 'a', $ftype = 'file', $platform='') {
    if (strlen($filename) > 0) {

       if ($platform == '') {
          $platform = $this->platform;
       }
       $handle = fopen($filename, $mode);
       $this->tmp_files[$filename]['filename'] = $filename;
       $this->tmp_files[$filename]['handle'] = $handle;
       $this->tmp_files[$filename]['type'] = $ftype;
       $this->tmp_files[$filename]['platform'] = $platform;

    }

    return $handle;

  }

  function generateTempFileName($basename = 'tmp', $ext = 'tmp', $min = 0, $max = 32768) {

    // generic routine to try to generate a random file and test to see if it is indeed unique in the temp directory
    $filenum = rand($min, $max);
    $filename = $this->tmpdir . "/$basename$filenum" . ".$ext";
    if ($this->debug) {
       $this->logDebug("Checking to see if random file exists: $filename <br>");
    }
    while (file_exists($filename)) {
       $filenum = rand($min, $max);
       $filename = $this->tmpdir . "/$filenum" . ".$ext";
       if ($this->debug) {
          $this->logDebug("Checking next random name: $filename <br>");
       }
    }
    return $filename;
  }

  function copy2TempFile($filename, $tmpfilename = '', $platform='', $isPerm=0) {

    if (strlen($platform) == 0) {
       $platform = $this->platform;
    }
    if ($this->debug) {
       $this->logDebug("Exporting $filename <br>");
    }
    // generate a tmp file name if there is none supplied
    if (strlen($tmpfilename) == 0) {
       if ($this->debug) {
          $this->logDebug("No destination file given, generating one. <br>");
       }
       // if this is a dos platform, we need to try to keep a
       $tmpfilename = $this->generateTempFileName();
       if ($this->debug) {
          $this->logDebug("Generated file name: $tmpfilename <br>");
       }
    }

    if ( (strlen($filename) > 0) ) {
       $result = copy($filename, $tmpfilename);
       if ($this->debug) {
          $this->logDebug("Trying: copy($filename $tmpfilename) <br>");
       }
       

       if ($this->debug) {
          $this->logDebug("Result: $result <br>");
       }
       if (!$isPerm) {
          // stash it for later clean up unles we have been instructed to make it permananent
          $this->tmp_files[$tmpfilename]['filename'] = $tmpfilename;
          $this->tmp_files[$tmpfilename]['handle'] = -1;
          $this->tmp_files[$tmpfilename]['type'] = 'file';
          $this->tmp_files[$tmpfilename]['platform'] = $platform;
          $this->tmp_files[$tmpfilename]['result'] = $result;
       }
    }
    return $tmpfilename;
  }

  function closeTempFile($filename) {
    if ( in_array($filename, array_keys($this->tmp_files)) ) {
       $handle = $this->tmp_files[$filename]['handle'];
       if ($handle <> -1) {
          fclose($handle);
       }
       $this->tmp_files[$filename]['handle'] = -1;
    }
  }

  function clearTempFiles($filenames = array()) {
    // close and delete any temp files that have been opened in this sesion
    // files to close
    $files_to_close = array();
    if (!is_array($filenames)) {
       // make sure this is a valid file name
       if (in_array($filenames, array_keys($this->tmp_files))) {
          $files_to_close[$filenames] = $this->tmp_files[$filenames];
       }
    } else {
       foreach ($filenames as $thisname) {
          // make sure this is a valid file name
          if (in_array($thisname, array_keys($this->tmp_files))) {
             $files_to_close[$thisname] = $this->tmp_files[$thisname];
          }
       }
    }
    foreach ($this->tmp_files as $thisfile) {
       $handle = $thisfile['handle'];
       $fname = $thisfile['filename'];
       $ftype = $thisfile['type']; // currently unused, but later may allow us to treat local files and streams robustly
       if (file_exists($fname)) {
          // close it if the file handle is still set (indicating that it is open)
          if ($handle <> -1) {
             fclose($handle);
          }
          // now, remove the file
          shell_exec("rm $fname");
          #print("rm $fname<br>");
       }
    }
  }

  function getPropertySourceClass($propsource, $propclass) {
    $returnprops = array();
    switch ($propsource) {
        case 'parent':
        $returnprops = $this->getPropertyClass($propclass);
        break;
        
        default:
        if (isset($this->processors[$propsource])) {
           $this->processors[$propsource]->getPropertyClass($propclass);
        }
        break;
     }
    return $returnprops;
  }

  function getPropertyClass($propclass) {
    // $propclass - array containing any of the following:
    //              'publicprops', 'publicprocs', 'publicinputs', 'publicomps',
    //              'privateprops', 'privateprocs', 'privateinputs', 'privatecomps'
    // Sub-classing this function can add additional property classes to retrieve
    // See example in HSPFContainer for plotgen, and wdm properties
    $returnprops = array();
    foreach ($propclass as $thisclass) {

       switch ($thisclass) {

          case 'publicprops':
          $returnprops = array_unique(array_merge($returnprops, $this->getPublicProps()));
          break;

          case 'publicprocs':
          $returnprops = array_unique(array_merge($returnprops, $this->getPublicProcs()));
          break;

          case 'publicinputs':
          $returnprops = array_unique(array_merge($returnprops, $this->getPublicInputs()));
          break;

          case 'publiccomps':
          $returnprops = array_unique(array_merge($returnprops, $this->getPublicComps()));
          break;

          case 'publicvars':
          $returnprops = array_unique(array_merge($returnprops, $this->getPublicVars()));
          break;

          case 'privatevars':
          $returnprops = array_unique(array_merge($returnprops, $this->getPrivateProps()));
          break;

          case 'datasources':
          $returnprops = array_unique(array_merge($returnprops, $this->getDataSources()));
          break;

       }
    }
    return $returnprops;
  }


  function getPublicVars() {
    // gets all viewable variables
    $publix = array_unique(array_merge(array_keys($this->state), $this->setvarnames, $this->getPublicProps(), $this->getPublicProcs(), $this->getPublicInputs()));

    return $publix;
  }

  function getLocalVars() {
    // gets all viewable variables
    $publix = array_unique(array_merge(array_keys($this->state), $this->getPublicProps(), $this->getPublicProcs(), $this->getPublicInputs()));

    return $publix;
  }

  function getPublicProps() {
    // gets only properties that are visible (must be manually defined for now, could allow this to be set later)
    //$publix = array('name','objectname','description','componentid', 'startdate', 'enddate', 'dt', 'month', 'day', 'year', 'thisdate', 'the_geom', 'weekday', 'modays', 'week', 'hour', 'run_mode');
    $publix = array('name','objectname','description','componentid', 'startdate', 'enddate', 'dt', 'month', 'jday', 'day', 'year', 'thisdate', 'the_geom', 'weekday', 'modays', 'week', 'hour', 'run_mode', 'flow_mode', 'timestamp');

    return $publix;
  }

  function getDataSources() {

    return $this->datasources;
  }

  function getPublicProcs() {
    // gets all viewable processors
    $retarr = array();
    if (is_array($this->procnames)) {
       #$this->logDebug("Procs for $this->name: " . print_r($this->procnames,1));
       //error_log("Procs for $this->name: " . print_r($this->procnames,1));
       foreach ($this->procnames as $pn) {
          $retarr[] = $pn;
          // check for vars on proc, if set add names to the array to return
          if (isset($this->processors[$pn])) {
             if (is_object($this->processors[$pn])) {
                if (isset($this->processors[$pn]->vars)) {
                   if (is_array($this->processors[$pn]->vars)) {
                      foreach ($this->processors[$pn]->vars as $procvar) {
                         if (!in_array($procvar, $retarr)) {
                            $retarr[] = $procvar;
                         }
                      }
                   }
                }
             }
          }
       }
    }
    
    return $retarr;
  }

  function getPublicInputs() {
    // gets all viewable variables
    if (is_array($this->inputnames)) {
       return $this->inputnames;
    } else {
       return array();
    }
  }

  function getPublicComponents() {
    // gets all viewable variables
    if (is_array($this->compnames)) {
       return $this->compnames;
    } else {
       return array();
    }
  }

  function getPrivateProps() {
    // gets all viewable variables in the local context only
    $privitz = array();

    return $privitz;
  }

  function setStateTimerVars() {

    if (is_object($this->timer)) {
       $this->state['thisdate'] = $this->timer->thistime->format('Y-m-d');
       $this->state['year'] = $this->timer->thistime->format('Y');
       $this->state['month'] = $this->timer->thistime->format('n');
       $this->state['day'] = $this->timer->thistime->format('j');
       $this->state['jday'] = $this->timer->thistime->format('z');
       $this->state['weekday'] = $this->timer->thistime->format('N');
       $this->state['week'] = $this->timer->thistime->format('W');
       $this->state['hour'] = $this->timer->thistime->format('G');
       $this->state['modays'] = $this->timer->thistime->format('t');
    } else {
       if ($this->debug) {
          $this->logDebug("<b>Error: </b>$this->name timer is NOT an object <br>\n");
       }
       $this->logError("<b>Error: </b>$this->name timer is NOT an object <br>\n");
    }
    if ($this->debug) {
       $this->logDebug("<b>$this->name setStateTimerVars() method called at hour " . $this->state['hour'] . " on " . $this->state['thisdate'] . " week " . $this->state['week'] . " month " . $this->state['month'] . ".</b><br>\n");
    }
    $this->timer->timeSplit();
    $this->stepstart = $this->timer->timestart;
  }

  function readParentBroadCasts() {
    if ($this->debug) {
       $this->logDebug("Checking for parent read broadCastObjects on $this->name<br>\n");
    }
    // iterate through each equation stored in this object
    foreach (array_values($this->execlist) as $thisvar) {
       if (is_object($this->processors[$thisvar])) {
          if (get_class($this->processors[$thisvar]) == 'broadCastObject') {
             if ( ($this->processors[$thisvar]->broadcast_mode == 'read') 
                and ($this->processors[$thisvar]->broadcast_hub == 'parent')
             ) {
                // set all required inputs for the equation
                $this->processors[$thisvar]->arData = $this->state;
                // calls processor step method
                if (method_exists($this->processors[$thisvar], 'step')) {
                   $this->processors[$thisvar]->step();
                }
                if ($this->processors[$thisvar]->debug) {
                   $this->logDebug("Subcomp $thisvar debug output: <br>" . $this->processors[$thisvar]->debugstring);
                   // reset debugging string for processor, since it is appended here
                   $this->processors[$thisvar]->debugstring = '';
                }
                // evaluate the equation
                if ($this->debug) {
                   $this->logDebug("Finished Evaluating $thisvar, parent read<br>\n");
                }
             } // end is parent read
          } // end get_class == broadCastObject
          
       } // end is_object()
    }
  }

  function sendParentBroadCasts() {
    if ($this->debug) {
       $this->logDebug("Checking for parent send broadCastObjects on $this->name<br>\n");
    }
    // iterate through each equation stored in this object
    foreach (array_values($this->execlist) as $thisvar) {
       if (is_object($this->processors[$thisvar])) {
          if (get_class($this->processors[$thisvar]) == 'broadCastObject') {
             if ( ($this->processors[$thisvar]->broadcast_mode == 'cast') 
                and ($this->processors[$thisvar]->broadcast_hub == 'parent')
             ) {
                // set all required inputs for the equation
                $this->processors[$thisvar]->arData = $this->state;
                // calls processor step method
                if (method_exists($this->processors[$thisvar], 'step')) {
                   $this->processors[$thisvar]->step();
                }
                // evaluate the equation
                if ($this->debug) {
                   $this->logDebug("Finished Evaluating $thisvar, parent send<br>\n");
                }
             } // end is parent read
          } // end get_class == broadCastObject
          
       } // end is_object()
    }
  }

  function readChildBroadCasts() {
    if ($this->debug) {
       $this->logDebug("Checking for child read broadCastObjects on $this->name<br>\n");
    }
    // iterate through each equation stored in this object
    foreach (array_values($this->execlist) as $thisvar) {
       if (is_object($this->processors[$thisvar])) {
          if (get_class($this->processors[$thisvar]) == 'broadCastObject') {
             if ( ($this->processors[$thisvar]->broadcast_mode == 'read') 
                and ($this->processors[$thisvar]->broadcast_hub == 'child')
             ) {
                // set all required inputs for the equation
                $this->processors[$thisvar]->arData = $this->state;
                // calls processor step method
                if (method_exists($this->processors[$thisvar], 'step')) {
                   $this->processors[$thisvar]->step();
                }
                if ($this->processors[$thisvar]->debug) {
                   $this->logDebug($this->processors[$thisvar]->debugstring);
                   $this->logDebug("<br>\n");
                   // reset debugging string for processor, since it is appended here
                   $this->processors[$thisvar]->debugstring = '';
                }
                // evaluate the equation
                if ($this->debug) {
                   $this->logDebug("Finished Evaluating $thisvar, child read<br>\n");
                }
             } // end is parent read
          } // end get_class == broadCastObject
          
       } // end is_object()
    }
  }

  function sendChildBroadCasts() {
    // iterate through each equation stored in this object
    if ($this->debug) {
       $this->logDebug("Checking for child send broadCastObjects on $this->name<br>\n");
    }
    foreach (array_values($this->execlist) as $thisvar) {
       if (is_object($this->processors[$thisvar])) {
          if (get_class($this->processors[$thisvar]) == 'broadCastObject') {
             if ($this->debug) {
                $this->logDebug("Broadcast object $thisvar is mode= " . $this->processors[$thisvar]->broadcast_mode . " and hub= " . $this->processors[$thisvar]->broadcast_hub . "<br>\n");
             }
             if ( ($this->processors[$thisvar]->broadcast_mode == 'cast') 
                and ($this->processors[$thisvar]->broadcast_hub == 'child')
             ) {
                // set all required inputs for the equation
                $this->processors[$thisvar]->arData = $this->state;
                // calls processor step method
                if (method_exists($this->processors[$thisvar], 'step')) {
                   $this->processors[$thisvar]->step();
                }
                // evaluate the equation
                if ($this->debug) {
                   $this->logDebug("Finished Evaluating $thisvar, child send<br>\n");
                }
             } // end is parent read
          } // end get_class == broadCastObject
          
       } // end is_object()
    }
  }

  function preStep() {
    $this->setStateTimerVars();
    // data aquisition first
    $this->readParentBroadCasts();
    // hard wired inputs override broadcasts
    $this->getInputs();
    $this->sendChildBroadCasts();
  }

  function postStep() {
    $this->writeToParent();
    $this->sendParentBroadCasts();
    $this->logstate();
    $this->timer->timeSplit();
    $this->exectime += ($this->timer->timeend - $this->stepstart);
  }

  function step() {
    // many object classes will subclass this method.  
    // all step methods MUST call preStep(),execProcessors(), postStep()
    $this->preStep();
    if ($this->debug) {
       $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
    }
    // execute sub-components
    $this->execProcessors();
    if (method_exists($this, 'evaluate')) {
       //error_log("Calling evaluate() method on $this->name");
       $this->evaluate();
    }
    
    // log the results
    if ($this->debug) {
       $this->logDebug("$this->name Calling Logstate() thisdate = ");
    }
    
    $this->postStep();
  }

  function getCurrentValue($thisvar) {
    // returns the current value of any state variable
    return $this->state[$thisvar];
  }

  function getValue($thistime, $thisvar) {
    // currrently, does nothing with the time, assumes that the input time is
    // equal to the current modeled time and returns the current value
    if ($this->debug) {
       $sv = $this->state;
       if (isset($sv['the_geom'])) {
          $sv['the_geom'] = 'HIDDEN';
       }
       $this->logDebug("Variable $thisvar requested from $this->name, returning " . $sv[$thisvar] . " from " . print_r($sv,1) );
    }
   //error_log("Variable $thisvar requested from $this->name, returning " . $this->state[$thisvar] . " from " . print_r($this->state,1) );
    if (in_array($thisvar, array_keys($this->state))) {
       return $this->state[$thisvar];
    } else {
       return NULL;
    }
  }

  function setLogFileName() {
    if (strlen($this->logfile) == 0) {
       $this->logfile = 'objectlog.' . $this->sessionid . '.' . $this->componentid . '.log';
       $log_headers_sent = false; // log headers can not have been sent if we don't have a file name yet
    }
  }

  function log2file() {
    // output the log to a text file
    if (strlen($this->logfile) == 0) {
       $this->setLogFileName();
    }
    //error_log("log2File() called on $this->name");
    //error_log("Serial Log File set to: $this->logfile");
    // get log from postgres if connection is set
    if (is_object($this->listobject) and ($this->log2db == 1)) {
       if ($this->listobject->tableExists($this->dbtblname)) {
          $this->listobject->querystring = " select * from $this->dbtblname order by thisdate";
          if ($this->debug) {
             $this->logDebug("Retrieving log from db: " . $this->listobject->querystring . " <br>");
          }
          $this->listobject->performQuery();
          $this->logtable = $this->listobject->queryrecords;
          if ($this->debug) {
             $this->logDebug("Retrieved " . count($this->logtable) . " records from run log table. <br>");
             $this->logDebug("Sample, last log entry: " . print_r($this->logtable[count($this->logtable) - 1],1) . " records from run log table. <br>");
          }

          // create a serial file
          //$serialfile = $this->outdir . '/' . $this->logfile . ".serial";
          //$open = fopen($serialfile, 'w');
          //fwrite($open, serialize($this->logtable));
          //fclose($open);
       }
    }
    
    // now, send the logtable to a file
    $filename = $this->flushMemoryLog2file();
    // create a second copy of the file as most recent run for this object
    $lastlog = $this->outdir . '/' . 'lastlog' . $this->componentid . '.log';
    // if we give the copy2tempfile routine a file name, it will NOT put the name in the list of files to be cleaned up later
    $this->copy2tempFile($filename, $lastlog, $this->platform, 1);
    $this->logfile = $lastlog;
    //error_log("Final Log File set to: $this->logfile");
  }

  function flushMemoryLog2file() {
    // output the log to a text file -- appending to the file, and emptying the log array
    if (strlen($this->logfile) == 0) {
       $this->setLogFileName();
    }
    $filename = $this->outdir . '/' . $this->logfile;

    // format for output
    if ($this->debug) {
       $this->logDebug("Flushing Log to File: $this->outdir / $this->logfile <br>");
    }
    //error_log("Flushing Log to File: $this->outdir / $this->logfile <br>");
    
    if (!$this->log_headers_sent) {
       #$this->logDebug($outarr);
       $colnames = array(array_keys($this->logtable[0]));
       putDelimitedFile("$filename",$colnames,$this->translateDelim($this->delimiter),1,$this->fileformat);
       $this->log_headers_sent = true;
    }
    
    if (count($this->logtable) > 0) {
       $outarr = $this->formatLogData();
       // append the data to the file, then close the file
       putArrayToFilePlatform("$filename", $outarr,0,$this->fileformat);
       // clear log from memory 
    }
    $this->logtable = array();
    $this->logRetrieved = 0;
    return $filename;
  }

  function translateDelim($delimtext) {
    switch ($delimtext) {
       case 0:
          $d = ',';
       break;
       case 1:
          $d = "\t";
       break;
       
       case 2:
          $d = ' ';
       break;
       
       case ',':
          $d = ',';
       break;
       
       case "\t":
          $d = "\t";
       break;
       
       case '|':
          $d = '|';
       break;
       
       default:
       // by making the default a non-translation, we should preserve backward compatibility with the previous version
          $d = $delimtext;
       break;
    }
    
    return $d;
       
  }

  function formatLogData() {
    $fdel = '';
    $outform = '';
    foreach (array_keys($this->logtable[0]) as $thiskey) {
       if (in_array($thiskey, array_keys($this->logformats))) {
          // get the log file format from here, if it is set
          if ($this->debug) {
             $this->logDebug("Getting format for log table " . $thiskey . "\n");
          }
          $outform .= $fdel . $this->logformats[$thiskey];
       } else {
          if (is_numeric($this->logtable[0][$thiskey])) {
             //$outform .= $fdel . $this->numform;
             $outform .= $fdel . $this->strform;
          } else {
             $outform .= $fdel . $this->strform;
          }
       }
       $fdel = ',';
    }
    $outarr = nestArraySprintf($outform, $this->logtable);
    return $outarr;
  }

  function logFromFile() {
    // check for a file, if set, use it to populate the log table
    if ($this->debug) {
       $this->logDebug("logFromFile method called on $this->name<br>");
    }
    //error_log("logFromFile method called on $this->name<br>");
    if (strlen($this->logfile) == 0) {
       $this->setLogFileName();
    }
    $filename = $this->outdir . '/' . $this->logfile;
    if ($this->debug) {
       $this->logDebug("Checking for $filename for object $this->name<br>");
    }
    if (file_exists($filename)) {
       if ($this->debug) {
          $this->logDebug("Loading data from $filename <br>");
       }
       // since this is from a log file that we generated, we can assume that it has the column headers
       $tsvalues = readDelimitedFile($filename, $this->translateDelim($this->delimiter), 1);
       $tcount = 0;
       #$this->logDebug($tsvalues);
       foreach ($tsvalues as $thisline) {
          if (isset($thisline['thisdate'])) {
             $this->logstate($thisline, 1);
             #$this->logDebug("Adding Line");
             #$this->logDebug($thisline);
          } else {
             if ($this->debug) {
                // only log this once
                if ($tcount == 0) {
                   $this->logDebug("Date column not found in $this->logfile.<br>");
                }
                $tcount++;
             }
          }
       }
    } else {
       if ($this->debug) {
          $this->logDebug("Can not find log file $this->logfile <br>");
       }
    }
    $this->logLoaded = 1;
  }

  function log2listobject($columns = array()) {
    if ($this->listobject > 0) {
       // format for output
       if ($this->debug) {
          $this->logDebug("Outputting Time Series to db: $tblname <br>");
       }
       $createsql = $this->listobject->array2tmpTable($this->logtable, $this->dbtblname, $columns, $this->dbcolumntypes, 1, $this->bufferlog);
       if ( ($this->timer->steps <= 1) and $this->debug ) {
          $this->logDebug("Table creatiopn SQL: " . $createsql . "<br>");
       }
    } else {
       $this->logDebug("List object not set.<br>");
    }
  }

  function list2file($listrecs, $filename='') {

    if (strlen($filename) == 0) {
       $filename = 'datafile.' . $this->componentid . '.csv';
    }

    $datafile = $this->outdir . '/' . $filename;
    $colnames = array(array_keys($listrecs[0]));
    putDelimitedFile($datafile,$colnames,$this->translateDelim($this->delimiter),1,'unix');

    if (count($listrecs) > 0) {
       if ($this->debug) {
          $this->logDebug("Appending values to monthly $datafile<br>");
       }
       $fdel = '';
       $outform = '';
       foreach (array_keys($listrecs[0]) as $thiskey) {
          if (in_array($thiskey, array_keys($this->logformats))) {
             // get the log file format from here, if it is set
             if ($this->debug) {
                $this->logDebug("Getting format for log table " . $thiskey . "\n");
             }
             $outform .= $fdel . $this->logformats[$thiskey];
          } else {
             if (is_numeric($listrecs[0][$thiskey])) {
                $outform .= $fdel . $this->numform;
             } else {
                $outform .= $fdel . $this->strform;
             }
          }
          $fdel = $this->translateDelim($this->delimiter);
       }
       // format for output if records exist for each year in the dataset
       $outarr = nestArraySprintf($outform, $listrecs);
       putArrayToFilePlatform("$datafile", $outarr,0,'unix');
       #print_r($outarr);

    }

    return $filename;
  }


  //function logstate($logvalues = array(), $preserve_timestamp=0) {
  function logstate($logvalues = array()) {

    $thislog = array();

    $logsrc = array();

    // if an array of values is passed in, use these instead of our state array (used to pass child info upstream)
    if (count($logvalues) > 0) {
       $logsrc = $logvalues;
    } else {
       $logsrc = $this->state;
    }

    if (!isset($logsrc['thisdate'])) {
       $logsrc['thisdate'] = $this->timer->thistime->format('Y-m-d');
    }
    if ($this->debug) {
       $this->logDebug("Logging called - using thisdate = " . $logsrc['thisdate'] . "<br>");
    }
    if (!isset($logsrc['time'])) {
       $logsrc['time'] = $this->timer->thistime->format('r');
    }
    if (!isset($logsrc['month'])) {
       $logsrc['month'] = $this->timer->thistime->format('m');
    }
    if (!isset($logsrc['day'])) {
       $logsrc['day'] = $this->timer->thistime->format('d');
    }
    if (!isset($logsrc['jday'])) {
       $logsrc['jday'] = $this->timer->thistime->format('z');
    }
    if (!isset($logsrc['year'])) {
       $logsrc['year'] = $this->timer->thistime->format('Y');
    }
    if (!isset($logsrc['week'])) {
       $logsrc['week'] = $this->timer->thistime->format('W');
    }
    if (!(isset($logsrc['timestamp'])) or !$preserve_timestamp ) {
       if (is_object($this->timer)) {
          $logsrc['timestamp'] = $this->timer->thistime->format('U');
       }
    }
    if ( ($logsrc['timestamp'] == '') or ($logsrc['timestamp'] === NULL)) {
       $logsrc['timestamp'] = $this->timer->thistime->format('U');
    }
    /*
    if (!isset($logsrc['timestamp'])) {
       $logsrc['timestamp'] = $this->timer->thistime->format('U');
    } else {
       if ($logsrc['timestamp'] == '') {
          $logsrc['timestamp'] = $this->timer->thistime->format('U');
       }
    }
    */
    // log the season
    $seasons = array(1=>'winter',2=>'winter',3=>'winter',4=>'spring',5=>'spring',6=>'spring',7=>'summer',8=>'summer',9=>'summer',10=>'fall',11=>'fall',12=>'fall');
    $logsrc['season'] = $seasons[$logsrc['month']];

    if (count($this->loglist) > 0) {
       $logvars = $this->loglist;
    } else {
       $logvars = array_keys($logsrc);
    }
    // must intersect logsrc and logvars to avoid tons of warnings
    // and let the user know they requested logging that cannot be logged
    $logmissing = array_diff($logsrc, $logvars);
    if (count($logmissing) > 0) {
      if ($this->timer->steps <= 2) {
         $this->logDebug("Unable to find requested logvariables: " . print_r($logmissing,1));
         //error_log("Unable to find requested logvariables: " . print_r($logmissing,1));
      }
      $logvars = $logsrc;
    }
    
    if ($this->timer->steps <= 2) {
      //error_log("Checking for strict_log setting (this->strict_log = $this->strict_log). ");
    }
    if ($this->strict_log and (count($this->data_cols) > 0)) {
       $logvars = array_unique($this->data_cols);
       if ($this->debug) {
          if ($this->timer->steps <= 2) {
             $this->logDebug("Using data_cols to restrict log variables. ");
             error_log("$this->name Using data_cols to restrict log variables: " . print_r($logvars,1));
          }
       }
    }
  //error_log(print_r($logvars, 1) );
    foreach ($logvars as $thisvar) {
       // eleminate the geometry column if log_geom is set to 0 (default)
       if ( (strlen(trim($thisvar)) > 0) and ( ($this->log_geom == 1) or ($thisvar <> 'the_geom') ) ) {
          $thislog[$thisvar] = $logsrc[$thisvar];
       }
    }

    if ($this->debug) {
       $this->logDebug("$this->name Logging Output at time-step " . $this->timer->steps . "<br>");
       if ($this->timer->steps <= 2) {
          $this->logDebug("DB Column formats: " . print_r($this->dbcolumntypes,1) . "<br>");
          $this->logDebug("Logged Columns and Values: " . print_r($thislog,1) . "<br>");
       }
    }
    
    switch ($this->log2db) {

       
       case 0:
       array_push($this->logtable, $thislog);
       break;
       
       case 1:
       // log to db object if connection is set
       if (is_object($this->listobject)) {
          if ($this->debug) {
             $this->logDebug("$this->name Logging Output at time-step " . $this->timer->steps . "<br>");
             if ($this->timer->steps <= 2) {
                $olddebug = $this->listobject->debug;
                // un-comment this to turn on debugging in listobject temporarily
                //$this->listobject->debug = 1;
                $this->logDebug("DB Column formats: " . print_r($this->dbcolumntypes,1));
                $this->logDebug("Logged Columns and Values: " . print_r($thislog,1));
             }
          }

          $createsql = $this->listobject->array2tmpTable(array($thislog), $this->dbtblname, array_keys($thislog), $this->dbcolumntypes, 1, $this->bufferlog);

          // always log table creation sql cause why not?
          //if ($this->debug) {
             if ($this->timer->steps <= 1) {
                $lkl = 1;
                foreach (str_split($createsql, 128) as $csql) {
                  $this->logDebug("Table creation SQL ($lkl): " . $csql . "<br>");                  
                  $lkl++;
                }
                $this->listobject->debug = $olddebug;
               $this->reportstring .= "Runtime Table SQL: " . $createsql . "\n\n<br>";
             }
          //}
       } else {
          // log to db requested, but no valid db object is set
          if ($this->timer->steps <= 1) {
             $this->logError("<b>Error: </b> Object $this->name log to db requested, but no valid db object is set.<br>\n");
          }
       }
       break;

       case 2:
       // log to file
       // if memory is at 85% of limit flush the log to the log file
       // use the timer object to store the maximum memory value since all objects share access to the timer
       array_push($this->logtable, $thislog);
       if ($this->debug) {
          $this->logDebug("Log Flush Requested <br>");
          $this->logDebug("Log Flush Parameters: " . $this->timer->max_memory_mb . " * " . $this->timer->max_memory_pct . " <br>");
       }
       $mem_use = (memory_get_usage(true) / (1024.0 * 1024.0));
       $mem_use_malloc = (memory_get_usage(false) / (1024.0 * 1024.0));
       $tstep = $this->timer->steps;
       if ( ($this->timer->max_memory_mb * $this->timer->max_memory_pct) <= $mem_use ) {
          if ($this->debug) {
             $this->logDebug("Flush requested at $tstep on $this->name because memory usage is $mem_use ($mem_use_malloc)<br>\n");
          }
          //error_log("Flush requested at $tstep on $this->name because memory usage is $mem_use ($mem_use_malloc)<br>\n");
          $this->flushMemoryLog2file();
       }
       
       break;

       default:
       array_push($this->logtable, $thislog);
       break;
    }

    $this->logLoaded = 1;
  }

  function logDebug($debuginfo) {
    if (is_array($debuginfo)) {
       $debuginfo = print_r($debuginfo,1);
    }

    switch ($this->debugmode) {
       case -1:
          // ignore all calls to log errors
       break;
       
       case 0:
       // store in a string unless the log is too big, then put it in a file
       $this->debugstring .= $debuginfo;
       if (strlen($this->debugstring) > (1024 * 1024 * 5))  {
          $this->flushDebugToFile();
          $this->debugmode = 3;
       }
       break;

       case 1:
       // print to stderr - apache error log bites it if we give it too much data, so we truncate this if debug model is 1
       if (strlen($debuginfo) > 512) {
          $debuginfo = substr($debuginfo, 0, 511);
       }
       error_log($debuginfo);
       break;

       case 2:
       // print to stdout
       print($debuginfo);
       break;

       case 3:
       // spool to a file
       $this->debugstring .= $debuginfo;
       // spool to file in 5Mb increments to keep write frequency low
       if (strlen($this->debugstring) > (1024 * 1024 * 5))  {
          $this->flushDebugToFile();
       }
       break;
    }
  }

  function flushDebugToFile() {
    if ($this->debugfile == '') {
       $this->setDebugFile();
    }
    if (filesize($this->outdir . "/" . $this->debugfile) >= $this->maxdebugfile) {
       $this->debugstring .= "<br>Max debug file size of $this->maxdebugfile exceeded.  Debugging suspended.<br>";
       $this->debug = 0;
    }
    $dfp = fopen($this->outdir . "/" . $this->debugfile,'a');
    fwrite($dfp, $this->debugstring);
    $this->debugstring = '';
    fclose($dfp);
  }

  function setDebugFile() {
    $this->debugfile = 'debuglog.' . $this->sessionid . '.' . $this->componentid . '.log';
    $dfp = fopen($this->outdir . "/" . $this->debugfile,'w');
    if ($dfp) {
       fwrite($dfp,"<html><body>");
       fclose($dfp);
    }
  }

  function logSysTemp($errorstring) {
    if (isset($this->parentobject)) {
       $this->parentobject->logSysTemp($errorstring);
    } else {
       if (is_array($errorstring)) {
          $errorstring = print_r($errorstring,1);
       }
       $this->errorstring .= $errorstring;
       // spool to file in 5Mb increments to keep write frequency low
       if (strlen($this->errorstring) > (1024 * 1024 * 5))  {
          $this->flushErrorToFile();
       }
    }
  }

  function logError($errorstring) {
    if ($this->logerrors) {
       if (is_array($errorstring)) {
          $errorstring = print_r($errorstring,1);
       }
       $this->errorstring .= $errorstring;
       // spool to file in 5Mb increments to keep write frequency low
       if (strlen($this->errorstring) > (1024 * 1024 * 5))  {
          $this->flushErrorToFile();
       }
    }
  }

  function flushErrorToFile() {
    if ($this->errorfile == '') {
       $this->setErrorFile();
    }
    if (filesize($this->outdir . "/" . $this->errorfile) >= $this->maxdebugfile) {
       $this->errorstring .= "<br>Max error file size of $this->maxdebugfile exceeded.  Error logging suspended.<br>";
       $this->logerrors = 0;
    }
    $dfp = fopen($this->outdir . "/" . $this->errorfile,'a');
    fwrite($dfp, $this->errorstring);
    $this->errorstring = '';
    fclose($dfp);
  }

  function setErrorFile() {
    $this->errorfile = 'errorlog.' . $this->sessionid . '.' . $this->componentid . '.log';
    $dfp = fopen($this->outdir . "/" . $this->debugfile,'w');
    if ($dfp) { 
      fwrite($dfp,"<html><body>");
      fclose($dfp);
    }
  }

  function execProcessors() {

    if ($this->debug) {
       $this->logDebug("Going through processors for $this->name.<br>\n");
    }
    // iterate through each equation stored in this object
    foreach (array_values($this->execlist) as $thisvar) {
       // evaluate the equation
       if ($this->debug) {
          $this->logDebug("Next subcomp: $thisvar<br>\n");
       }
       //error_log("exec $this->name ---> $thisvar ");
       if (is_object($this->processors[$thisvar])) {
          // broadcast components get executed in the preStep() and postStep() methods
          if ( !(get_class($this->processors[$thisvar]) == 'broadCastObject') ) {
             // set all required inputs for the equation
             // if this is a sub-comp on a sub-comp, we need to merge arData arrays
             if (is_array($this->arData)) {
                if ($this->processors[$thisvar]->debug) {
                   $this->logDebug("Merging array this -> arData with internal state array <br>\n");
                }
                $statearr = array_merge($this->state,$this->arData);
             } else {
                if ($this->processors[$thisvar]->debug) {
                   $this->logDebug("this -> arData not an array - using internal state array only <br>\n");
                }
                $statearr = $this->state;
             }
             if ($this->processors[$thisvar]->debug) {
                if (isset($statearr['the_geom'])) {
                   $statearr['the_geom'] = 'Truncated for debugging purposes';
                }
                $this->logDebug("Setting child $thisvar arData to: " . print_r($statearr,1) . " <br>\n");
             }
             $this->processors[$thisvar]->arData = $statearr;
             if ($this->debug or $this->processors[$thisvar]->debug) {
                $this->logDebug("Checking Processor step() method on $thisvar<br>\n");
             }
             // calls processor step method
             if (method_exists($this->processors[$thisvar], 'step')) {
                $this->processors[$thisvar]->step();
                //error_log("step() method called on $this->name -> $thisvar<br>\n");
             }
             // evaluate the equation
             if ($this->debug or $this->processors[$thisvar]->debug) {
                $this->logDebug("Evaluating $thisvar<br>\n");
             }
             // if this processor is not transparent, it will evaluate and return a value, otherwise,
             // we assume that it does not set a value
             //error_log("Evaluating $this->name -> $thisvar<br>\n");
             if (method_exists($this->processors[$thisvar], 'evaluate')) {
                //error_log("evaluate() method exists <br>\n");
                //$this->processors[$thisvar]->evaluate(); // this is now doen in the step() function
                if ($this->processors[$thisvar]->debug) {
                   //error_log("Appending Subcomp $thisvar debug output: <br>" . $this->processors[$thisvar]->debugstring);
                   $this->logDebug($this->processors[$thisvar]->debugstring);
                   $this->logDebug("<br>\n");
                   // reset debugging string for processor, since it is appended here
                   $this->processors[$thisvar]->debugstring = '';
                }
                if ($this->debug) {
                   $sv = $this->state;
                   if (isset($sv['the_geom'])) {
                      $sv['the_geom'] = 'HIDDEN';
                   }
                   $this->logDebug($sv);
                   $this->logDebug("<br>\n");
                }
                //error_log("checking if this is multivar <br>\n");
                // set the state variable with the equation result
                switch ($this->processors[$thisvar]->multivar) {
                   case 0:
                      $this->state[$thisvar] = $this->processors[$thisvar]->result;
                   break;

                   case 1:
                      foreach ($this->processors[$thisvar]->multivarnames as $mvname) {
                         $this->state[$mvname] = $this->processors[$thisvar]->state[$mvname];
                      }
                   break;

                   default:
                      $this->state[$thisvar] = $this->processors[$thisvar]->result;
                   break;
                }
                //error_log("Done. <br>\n");
             }
             // evaluate the equation
             if ($this->debug) {
                $this->logDebug("Finished Evaluating $thisvar, Result = " . $this->state[$thisvar] . "<br>\n");
             }
          }
       } else {
          if ($this->debug) {
             $this->logDebug("Error: Sub-component not set $thisvar<br>\n");
          }
       }
       //error_log("done $this->name ---> $thisvar ");
    }
    //error_log("Finished with processors on $this->name . <br>\n");
  }

  function orderOperations() {

    $dependents = array();
    $independents = array();
    $sub_queue = array();
    $execlist = array();
    // compile a list of independent and dependent variables
    // @todo: rvars on subcomps are explicit independent inputs to subcomps that are not yet handled
    //        wvars are explicit outputs from subcomps that are often used by other comps
    //        We also need to check to see if we are putting things in vars that should not be?
    //        vars is a catchall used by equations which is equivalent to rvars but I *think*
    //        vars has become a place for both rvars and wvars which might lead to unpredictable behavior
    foreach (array_keys($this->processors) as $thisinput) {
      foreach ($this->processors[$thisinput]->wvars as $wv) {
        $independents[$this->processors[$thisinput]->getParentVarName($wv)] = $thisinput;
      }
      array_push($dependents, $thisinput);
    }
    if ($this->debug) {
       $this->logDebug("<b>Ordering Operations for $this->name</b><br> ");
    }
    $this->outstring .= "Ordering Operations for $this->name\n";
    // now check the list of independent variables for each processor,
    // if none of the variables are in the current list of dependent variables
    // put it into the execution stack, remove from queue
    $queue = $dependents;
    // sort those with non-zero hierarchy settings, placing all <0 hierarchy in order on the bottom of the queue (early)
    // then place all of those later
    $preexec = array();
    $postexec = array();
    $nonhier = array();
    $hiersort = array();
    foreach ($queue as $thisel) {
       array_push($hiersort, $thisel);
    }
    sort($hiersort);
    foreach ($hiersort as $thisel) {
       $hier = $this->processors[$thisel]->exec_hierarch;
       if ($hier < 0) {
          $preexec[$thisel] = $hier;
       } else {
          if ($this->processors[$thisel]->exec_hierarch > 0) {
             $postexec[$thisel] = $hier;
          } else {
             array_push($nonhier, $thisel);
          }
       }
    }
    asort($preexec);
    $preexec = array_keys($preexec);
    asort($postexec);
    $postexec = array_keys($postexec);
    $queue = $nonhier;
    $this->logDebug("Beginning Queue \n");
    $this->logDebug($queue);
    $this->logDebug("Beginning independents \n");
    $this->logDebug($independents);
    $i = 0;
    $dbc = $this->debug;
    $this->debug = 0;
    while (count($queue) > 0) {
       $thisdepend = array_shift($queue);
       $pvars = $this->processors[$thisdepend]->vars;
       //$watchlist = array('impoundment', 'local_channel');
       //$this->debug = in_array( $this->processors[$depend]->name, $watchlist) ? 1 : 0;
       if ($this->debug) {
          $this->logDebug("Checking $thisdepend variables \n");
          $this->logDebug($pvars);
          $this->logDebug(" <br>\n in ");
          $this->logDebug($queue);
          $this->logDebug("<br>\n");
       }
       $numdepend = $this->array_in_array($pvars, $queue);
       if (!$numdepend) {
          array_push($execlist, $thisdepend);
          $i = 0;
          if ($this->debug) {
             $this->logDebug("Not found, adding $thisdepend to execlist.<br>\n");
          }
          // remove it from the derived var list if it exists there 
          while ($dkey = array_search($thisdepend, $independents)) {
            unset($independents[$dkey]);
          }
       } else {
          // put it back on the end of the stack
          if ($this->debug) {
             $this->logDebug("Found.<br>\n");
          }
          array_push($queue, $thisdepend);
       }
       $i++;
       // should try to sort them out by the number of unsatisfied dependencies,
       // adding those with 1 dependency first
       if ( ($i > count($queue)) and (count($queue) > 0)) {
          # we have reached an impasse, since we cannot currently
          # solve simultaneous variables, we just put all remaining on the
          # execlist and hope for the best
          # a more robust approach would be to determine which elements are in a circle,
          # and therefore producing a bottleneck, as other variables may not be in a circle
          # themselves, but may depend on the output of objects that are in a circle
          # then, if we add the circular variables to the queue, we may be able to continue
          # trying to order the remaining variables

          # first, create a list of execution hierarchies and compids
          $hierarchy = array();
          foreach ($queue as $thisel) {
             $hierarchy[$thisel] = $this->processors[$thisel]->exec_hierarch;
          }
          # sort in reverse order of hierarchy
          # then, look at exec_hierarch property, if the first element is higher priority than the lowest in the stack
          # pop it off the list, and add it to the queue
          # then, after doing that, we can go back, set $i = 0, and try to loop through again,
          arsort($hierarchy);
          $keyar = array_keys($hierarchy);
          if ($this->debug) {
             $this->logDebug("Cannot determine sequence of remaining variables, searching manual execution hierarchy setting.<br>\n");
          }
          $firstid = $keyar[0];
          $fh = $hierarchy[$firstid];
          $mh = min(array_values($hierarchy));
          if ($this->debug) {
             $this->logDebug("Highest hierarchy value = $fh, Lowest = $mh.<br>\n");
          }
          if ($fh > $mh) {
             # pop off and resume trying to sort them out
             $newqueue = array_diff($queue, array($firstid) );
             array_push($execlist, $firstid);
             $i = 0;
             if ($this->debug) {
                $this->logDebug("Elelemt " . $firstid . ", with hierarchy " . $hierarchy[$firstid] . " added to execlist.<br>\n");
             }
             $queue = $newqueue;
          } else {

             if ($this->debug) {
                $this->logDebug("Can not determine linear sequence for the remaining variables. <br>\n");
                $this->logDebug($queue);
                $this->logDebug("<br>\nDefaulting to order by number of unsatisfied dependencies.<br>\n");
                $this->logDebug("<br>\nHoping their execution order does not matter!.<br>\n");
             }
             foreach ($queue as $lastcheck) {
                $pvars = $this->processors[$lastcheck]->vars;
                $numdepend = $this->array_in_array($pvars, $queue);
                $dependsort[$lastcheck] = $numdepend;
             }
             asort($dependsort);
             if ($this->debug) {
                $this->logDebug("Remaining variable sort order: \n");
                $this->logDebug($dependsort);
             }
             $numdepend = $this->array_in_array($pvars, array_keys($dependsort));
             $newexeclist = array_merge($execlist, $queue);
             $execlist = $newexeclist;
             break;
          }
       }
    }
    $this->debug = $dbc;
    $hiersort = array_merge($preexec, $execlist, $postexec);
    
    
    $this->logDebug("Final Queue \n");
    $this->logDebug($queue);
    $this->logDebug("Final independents \n");
    $this->logDebug($independents);
    $this->logDebug("Pre-exec list: \n");
    $this->logDebug($preexec);
    $this->logDebug("Dependency ordered: \n");
    $this->logDebug($hiersort);
    $this->logDebug("Post-exec list:  \n");
    $this->logDebug($postexec);
    
    $this->outstring .= "Ordering Operations\n";
    $this->outstring .= "Independents Remaining: " . print_r($independents,1) . "\n";
    $this->outstring .= "Pre-exec list: " . print_r($preexec,1) . "\n";
    $this->outstring .= "To Be ordered: " . print_r($nonhier,1) . "\n";
    $this->outstring .= "Dependency ordered: " . print_r($execlist,1) . "\n";
    $this->outstring .= "Post-exec list: " . print_r($postexec,1) . "\n";
    $this->outstring .= "Sorted: " . print_r($hiersort,1) . "\n";
    $this->execlist = $hiersort;
  }

  function array_in_array($needle, $haystack) {
     //Make sure $needle is an array for foreach
     if(!is_array($needle)) $needle = array($needle);
     $count = 0;
     //For each value in $needle, return TRUE if in $haystack
     foreach($needle as $pin)
         //if(in_array($pin, $haystack)) return TRUE;
         if(in_array($pin, $haystack)) $count++;
     //Return FALSE if none of the values from $needle are found in $haystack
     //return FALSE;
     return $count;
  }

  function interpValue($thiskey, $lowkey, $lowvalue, $highkey, $highvalue) {

    switch ($this->intmethod) {
       case 0:
          $retval = $lowvalue + ($highvalue - $lowvalue) * ( ($thiskey - $lowkey) / ($highkey - $lowkey) );
       break;

       case 1:
          $retval = $tv;
       break;

    }
    return $retval;
  }

  function addLookup($thisinput, $srcparam, $lutype, $lookuptable, $defaultval) {
    # stashes the lookup table
    $this->lookups[$thisinput]['default'] = $defaultval;
    $this->lookups[$thisinput]['table'] = $lookuptable;
    $this->lookups[$thisinput]['lutype'] = $lutype;
    $this->lookups[$thisinput]['srcparam'] = $srcparam;
    $this->lookups[$thisinput]['debug'] = 0;
  }

  function addOperator($statevar, $operator, $initval = NULL) {
    if (!in_array($statevar, array_keys($this->state))) {
       if ($this->debug) {
          $this->errorstring .= "Adding state variable $statevar <br>\n";
       }
       # need to add this named input
       $this->state[$statevar] = $initval;
    }
    $operator->name = $statevar;
    # set link to operators parent (i.e., this containing object)
    $operator->parentobject = $this;
    $this->processors[$statevar] = $operator;
    if ($this->debug) {
       $this->errorstring .= "Adding operator $statevar<br>\n";
       $this->logDebug("Adding operator $statevar<br>\n");
    }
    # add to exec list in order of creation, may later order by precedence with the
    # function orderOperations()
    array_push($this->execlist, $statevar);
    $this->procnames = array_keys($this->processors);
  }

  function addInput($thisinput, $inputparam, $input_obj, $input_type = 'float8') {
    $inkeys = array();
    if (is_array($this->inputs)) {
       $inkeys = array_keys($this->inputs);
    }
    if (!in_array($thisinput, $inkeys)) {
       if ($this->debug) {
          $this->logDebug("New Input $thisinput added. ");
          #$this->logDebug(array_keys($this->inputs));
       }
       # need to add this named input
       $this->inputs[$thisinput] = array();
       # add db column setup info
       if (strlen($input_type) > 0) {
          $this->dbcolumntypes[$thisinput] = $input_type;
          #error_log(print_r($this->dbcolumntypes,1));
       }
    }
    // get some details about this input for messages/debugging
    $iname = $input_obj->name;
    $myname = $this->name;
    if ($this->debug) {
       $this->logDebug("Adding $iname -> $inputparam to $myname as $thisinput <br>\n");
    }
    // *** CHECK FOR UPDATE *** 
    // we want to check to see if this object->param link has alrady been set, if so we overwrite so
    // as not to add redundant links
    $insert = 1;
    foreach ($this->inputs[$thisinput] as $thislink) {
       // this code does not yet work
       
    }
    // *** END - CHECK FOR UPDATE
    if ($insert) {
       // this is a new input, not an update to an existing one, so we add it
       array_push($this->inputs[$thisinput], array('param'=>$inputparam, 'objectname'=>$input_obj->name, 'object'=>$input_obj, 'value'=>NULL));
    }
    
    // set the parent state for this array if it is not yet set
    if (!(isset($this->state[$thisinput]))) {
       $this->state[$thisinput] = 0.0;
    }
    // update the inputnames array
    $this->inputnames = array_keys($this->inputs);
    // add this to the loggable columns data_cols if we use strict logging
    if (!in_array( $thisinput, $this->data_cols)) {
       if ($this->debug) {
          $this->logDebug("Adding $thisinput to loggable variables $myname <br>\n");
       }
       $this->data_cols[] = $thisinput;
    }
  }

  function clearInputs($toclear = array()) {
    if (!is_array($toclear)) {
       $toclear = array($toclear);
    }
    if (count($toclear) == 0) {
       $this->inputnames = array();
       $this->inputs = array();
    } else {
       foreach ($toclear as $thisone) {
          unset($this->inputs[$thisone]);
          unset($this->inputnames[array_search($this->inputnames,$thisone)]);
       }
    }
  }

  function getInputs() {
    // debug
    //error_log("Getting Inputs for $this->name <br>");
    if ($this->debug) {
       $this->logDebug("Getting Inputs for $this->name <br>");
       $sv = $this->state;
       if (isset($sv['the_geom'])) {
          $sv['the_geom'] = 'HIDDEN';
       }
       $this->logDebug("Inputs Beginning State array: " . print_r($sv,1) . "\n<br>");
    }
    
    // *****************************
    // BEGIN - get Hard Wired Inputs
    // *****************************
    foreach (array_keys($this->inputs) as $varname) {
       if ($this->debug) {
          $this->logDebug("Getting Input $varname for $this->name <br>");
       }
       # reset each input param to 0.0 for the beginning of the timestep
       $this->state[$varname] = 0.0;

       $k = 0;
       foreach ($this->inputs[$varname] as $thisin) {
          $outparam = $thisin['param'];
          $inobject = $thisin['object'];
          $lv = $thisin['value'];
          if ($this->debug) {
             $iname = $inobject->name;
             if ($varname <> 'the_geom') {
                $this->logDebug("Searching $iname ($outparam) for $varname - last value = $lv... ");
             }
          }
          # accumulate inputs if they are numeric,
          # since we may input to the same input multiple sources
          $inval = $inobject->getValue($this->timer->timeseconds, $outparam);
          $this->inputs[$varname][$k]['value'] = $inval;
          # if the child object returns NULL, we don't use it
          if (!($inval === NULL)) {
             if (is_numeric($inval)) {
                $this->state[$varname] += $inval;
             } else {
                $this->state[$varname] = $inval;
             }
             if ($this->debug) {
                $iname = $inobject->name;
                if ($varname <> 'the_geom') {
                   $this->logDebug("updated with $outparam = $inval from Input: $iname, input total = " . $this->state[$varname] . "<br>\n");
                }
             }
          }
          $thisin['value'] = $inval;
          $k++;
       }
    }
    // *****************************
    // END - get Hard Wired Inputs
    // *****************************
    
    if ($this->debug) {
       $sv = $this->state;
       if (isset($sv['the_geom'])) {
          $sv['the_geom'] = 'HIDDEN';
       }
       $this->logDebug("Inputs gathered. State array: " . print_r($sv,1) . "\n<br>");
    }
    # now, process lookups, replaces lookup key with value in state variable
    $this->doLookups();
    if ($this->debug) {
       $this->logDebug("Lookups calculated. State array: " . print_r($sv,1) . "\n<br>");
    }

  }

  function doLookups() {
    foreach(array_keys($this->lookups) as $thisl) {
       $thistab = $this->lookups[$thisl]['table'];
       $defval = $this->lookups[$thisl]['default'];
       $lutype = $this->lookups[$thisl]['lutype'];
       $srcparam = $this->lookups[$thisl]['srcparam'];
       $curval = $this->state[$srcparam];
       $luval = '';
       switch ($lutype) {
          case 0:
          # exact match lookup table
          if (in_array($curval, array_keys($thistab))) {
             $luval = $thistab[$curval];
          } else {
             $luval = $defval;
          }
          if ($thisl->debug) {
             $this->logDebug("$thisl: $curval, def: $defval, lookup: $luval <br>\n");
             $this->logDebug($thistab);
             $this->logDebug("<br>\n");
          }
          break;

          case 1:
          # interpolated lookup table
          $lukeys = array_keys($thistab);
          $luval = $defval;
          for ($i=0; $i < (count($lukeys) - 1); $i++) {
             $lokey = $lukeys[$i];
             $hikey = $lukeys[$i+1];
             $loval = $thistab[$lokey];
             $hival = $thistab[$hikey];
             $minkey = min(array($lokey,$hikey));
             $maxkey = max(array($lokey,$hikey));
             if ( ($minkey <= $curval) and ($maxkey >= $curval) ) {
                $luval = $this->interpValue($curval, $lokey, $loval, $hikey, $hival);
                if ($this->lookups[$thisl]['debug']) {
                   $sv = $this->state;
                   if (isset($sv['the_geom'])) {
                      $sv['the_geom'] = 'HIDDEN';
                   }
                   $this->logDebug($sv);
                   $this->logDebug("<br>\nLow: $lokey, Value: $curval, Hi: $hikey = $luval <br>\n");
                }
             }
          }
          break;

       }
       $this->state[$thisl] = $luval;
    }
  }

  function showElementInfo($propname = '', $view = 'info', $params = array()) {

    $view = strtolower($view);
    $output = '';
    //error_log("showElementInfo($propname, $view) called on $this->name ");
    
    if ($propname == '') {
       switch ($view) {

          case 'info':
          $output .= $this->showHTMLInfo();
          break;

          case 'initval':
          $output .= $this->showInitialValues();
          break;

          case 'finalvalue':
          $output .= $this->showFinalValues();
          break;

          case 'editform':
          if (method_exists($this, 'showEditForm')) {
             // $params[0] = form name, $params[1] = disabled? (0/1)
             $editforminfo = $this->showEditForm($params[0], $params[1]);
             if (is_array($editforminfo)) {
                $output .= $editforminfo['innerHTML'];
             } else {
                $output .= $editforminfo;
             }
          } else {
             $output .= $this->showFormVars();
          }
          break;

       }
    } else {
    
       if (isset($this->processors[$propname])) {
          $output .= $this->processors[$propname]->showElementInfo('', $view, $params);
          //error_log("Calling showElementInfo('', $view) on Processor $propname ");
       } else {
          if ($view == 'editform') {
             $output .= $this->showFormVars(array($propname));
          }
       }
    }
    
    return $output;
  }

  function initFormVars() {
    if (!is_object($this->formvars)) {
       if (is_object($this->listobject)) {
          // check for form display methods
          if (function_exists('showFormVars')) {
             //$myvars = (array)$this;
             $myvars = $this->state;
             if ($this->debug) {
                $this->logDebug("Creating form fields for " . print_r($myvars,1) . "<br>");
             }
             if (isset($this->listobject->adminsetuparray[get_class($this)])) {
                $aset = $this->listobject->adminsetuparray[get_class($this)];
             } else {
                $aset = array();
                foreach ($myvars as $thisvar) {
                   $aset['column info'][$thisvar] = array('type'=>1,'hidden'=>0,'readonly'=>0);
                }
             }
             $showlabels = 1;
             $showmissing = 0;
             $multiform = 0;
             $this->formvars = showFormVars($this->listobject,$myvars,$aset,$showlabels, $showmissing, $this->debug, $multiform, 1, 0, -1, NULL, 1);
             if ($this->debug) {
                if (is_object($this->formvars)) {
                   $this->logDebug("Form object successfully created for $this->name <br>");
                } else {
                   $this->logDebug("Form object creation unsuccessful for $this->name <br>");
                }
             }
             // now, check to see if we are subclassing any of our vars to sub-components
             foreach ($myvars as $thisvar) {
                if (isset($this->processors[$thisvar])) {
                   if (method_exists($this->processors[$thisvar], 'showFormVars')) {
                      $this->formvars->formpieces['fields'][$thisvar] = $this->processors[$thisvar]->showFormVars(array($thisvar));
                   }
                }
             }
          } else {
             $this->logError(" Function 'showFormVars' is not defined, module db_functions.php required for this function.<br>");
          }
       } else {
          $this->logError(" listobject is not defined on this object.<br>");
       }
    } else {
       if ($this->debug) {
          $this->logDebug("Form variables already initialized<br>");
       }
    }
  }

  function showFormVars($vars = array()) {
    $this->initFormVars();
    $output = '';
    
    if (is_object($this->formvars)) {
       if (count($vars) == 0) {
          $output .= $outobject->innerHTML;
       } else {
          $del = '';
          foreach ($vars as $thisvar) {
             if (isset($this->formvars->formpieces['fields'][$thisvar])) {
                $output .= $del . $this->formvars->formpieces['fields'][$thisvar];
                $del = '<br>';
             }
          }
       }
    }
    return $output;

  }

  function showInitialValues() {

    return $this->defaultval;

  }

  function showFinalValues() {

    return $this->result;

  }

  function showHTMLInfo() {
    # prints out information about this object.  Should sub-class to get in depth report

    $props = $this->getPublicVars();
    $HTMLInfo = '';
    
    // do those in prop_desc first, then those that are sub-components (and hence have a desc)
    // then finally, those that do not have a description
    $subs = array_keys($this->processors);
    sort($subs);
    
    $HTMLInfo .= "<h3>Internal Properties:</h3><br>";
    foreach ($this->prop_desc as $varname => $desc) {
       if (!in_array($varname, $subs)) {
          // will assume that it has been sub-classed if it is set as a user-defined property
          $HTMLInfo .= "<b>$varname</b> - $desc <br> ";
       }
    }
    $HTMLInfo .= "Un-described: ";
    foreach ($props as $thisprop) {
       if ( (!(in_array($thisprop, array_keys($this->prop_desc)))) and (!(in_array($thisprop, $subs))) ) {
          $HTMLInfo .= "$thisprop ";
       }
    }
    $HTMLInfo .= "<hr>";
    $HTMLInfo .= "<h3>User-defined Sub-components:</h3><br>";
    $undesc = '';
    foreach ($subs as $thisproc) {
       if (property_exists($this->processors[$thisproc], 'description')) {
          if (strlen(trim($this->processors[$thisproc]->description)) > 0) {
             $HTMLInfo .= "<b>$thisproc</b> - " . $this->processors[$thisproc]->description . " <br> ";
          } else {
             $undesc .= "$thisproc ";
          }
       } else {
          $undesc .= "$thisproc ";
       }
    }
    $HTMLInfo .= "Un-described: $undesc";

    return $HTMLInfo;

  }

}

class modelSubObject extends modelObject {
  var $wvars = 0;
  
  function wake() {
    parent::wake();
  }
     
  function sleep() {
    $this->wvars = 0;
    $this->rvars = 0;
    parent::sleep();
  }

  function logState() {

    // logging will be done by the parent, so no need to waste memory and time with this
    // should consider whether we filter values here to prevent mismatches with the log data type.

  }

  function subState() {
    parent::subState();
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
       if (! is_array($this->vars)) {
         $this->vars = array();
       }
       $this->vars[] = $this->name . "_" . $thisvar;
    }
  }
}
   

class broadCastObject extends modelSubObject {
   // HUB object communication entities
   var $parentHub; // the "up-link" for peer-broadcast sharing in this container space
   var $childHub; // the hub space for this objects contained children (their parentHub - if they exist)
   var $broadcast_params = array();  // used for remote setting of local_varname and broadcast_varname -- @tbd replace them with this single variable as an array
   var $read_vars = array();
   var $cast_vars = array();
   // in form 'groupName'=>array(
   //            'cast_parent'=>1, // send messages to the HUB on parent?
   //            'read_parent'=>0, // read messages on the parent HUB?
   //            'cast_child'=>0,  // send messages to the child HUB (actually located on the object FOR its children)
   //            'read_child'=>1,  // read messages on the child HUB
   // in general, casting/reading settings should be unique to an object class, but might be specific for a given entity 
   // as well.  Right now, there is no facility for making them unique to each parameter, but that could be added later
   // also, these are NOT settable by the client for now.  maybe this should ALL be done in sub-components, which would 
   // make this a bit easier to manage.
   //            'params'=>array('Qout','drainarea_sqmi')
   //         );  
   var $broadcast_class = '';
   var $local_varname = array();
   var $broadcast_varname = array();
   var $broadcast_hub = 'parent'; // parent or child
   var $broadcast_mode = 'cast'; // either cast or read
   var $multivar = 1; // this is a multi-var entity
   var $loggable = 0; 
   var $exclude_my_broadcasts = 0; // don't READ values from this objects broadcasts to hub
   var $json2d = TRUE;
   
   function getInputs() {
      // has an entirely different approach to getting inputs
      // only do something on getInputs() if this is a reader
      // if this is a broadcaster, we do something on step()
      switch ($this->broadcast_mode) {
         case 'read':
            $this->broadRead();
         break;
      }
   }
   
   function init() {
      parent::init();
      // has an entirely different approach to getting inputs
      $this->parentHub = $this->parentobject->parentHub;
      $this->childHub = $this->parentobject->childHub;
      switch ($this->broadcast_hub) {
         case 'parent':
            $target = $this->parentHub;
         break;
         
         case 'child':
            $target = $this->childHub;
         break;
      }
      switch ($this->broadcast_mode) {
         case 'cast':
            // if this is a cast, then we will be pushing local variables, and NOT be mnodifying them on the parent
            $this->multivarnames = array();
         break;
         
         case 'read':
            // if this is a read, then we will be pulling local variables, and therefore modifying them on the parent
            $this->multivarnames = $this->local_varname;
            // now, if this is a read, set variables types for parent data logging of these input variables
            for ($i = 0; $i < count($this->local_varname); $i++) {
               $local = $this->local_varname[$i];
               if ($this->debug) {
                  $this->logDebug("Setting parent log type for variable $local <br>\n");
               }
               // later, we may allow the interface to provide settings for variable type and default value
               // for now, we just force them all to float8, and 0.0
               // we don't initialize variables if this broadcast hub does not exist, for example, if a the top-most
               // parent is calling the runs, there will be no ParentHub for it to read from
               if (is_object($target)) {
                  $this->parentobject->setSingleDataColumnType($local, 'float8', 0.0);
                  if (!in_array($local, $this->vars)) {
                     array_push($this->vars,$local);
                  }
               }
            }
         break;
         
         default:
            // if this is a cast, then we will be pulling local variables, and NOT be mnodifying them on the parent
            $this->multivarnames = array();
         break;         
      }
      
   }
   
   function evaluate() {
   }
   
   function step() {
      parent::step();
      $this->stepBroadcast();
   }
   
   function stepBroadcast() {
      // only do something on step() if this is a broadcaster
      // if this is a reader, we do something on getInputs()
      switch ($this->broadcast_mode) {
         case 'cast':
            $this->broadCast();
         break;
      }
   }
   
   function broadCast() {
      // send my broadcast variables (if any) to the broadcast hub
      // The broadcast HUB is an object with few methods, just a message passer
      // the broadcast hub is usually located in the parent object, but for a non-contained model container (i.e., that 
      // which initiated the model run) there would be no broadcast hub for it to send data to.  
      // var $parentHub; // the "up-link" for peer-broadcast sharing in this container space
      // var $childHub; // the hub space for this objects contained children (their parentHub)
      
      // iterate through each broadcast type and send the variables
      // also, the broadcast sub-components will do a broadcast call, but not via this method, rather, via their own calls
      // the the broadcast method -- maybe we think of creating subcomponents to handle all broadcasts, ???  not for now though
      switch ($this->broadcast_hub) {
         case 'parent':
            $target = $this->parentHub;
         break;
         
         case 'child':
            $target = $this->childHub;
         break;
      }
      
      // this is the broadcast hub itself, give all variables on the broadcast hub
      if (!is_object($target)) {
         $this->logError("<b>Error:</b> Selected hub for $this->name is not an object <br>");
         return;
      }
      if (!method_exists($target, 'broadCast')) {
         $this->logError("<b>Error:</b> No broadCast method on hub for $this->name <br>");
         return;
      }
      
      if ($this->debug) {
         if (is_object($this->childHub)) {
            $this->logDebug("Child Hub Contents: <br>" . print_r($this->childHub->hub_data,1) . "<br>");
         }
         if (is_object($this->parentHub)) {
            $this->logDebug("Parent Hub Contents: <br>" . print_r($this->parentHub->hub_data,1) . "<br>");
         }
      }
      
      if ($this->debug) {
         $this->logDebug("Broadcasting to $this->broadcast_hub for $this->name : <br>");
         $this->logDebug("Local info : " . print_r($this->arData,1) . "<br>");
      }
      for ($i = 0; $i < count($this->local_varname); $i++) {
         $remote = $this->broadcast_varname[$i];
         $local = $this->local_varname[$i];
         $target->broadCast($this->broadcast_class, $remote, $this->componentid, $this->arData[$local]);
         $this->logDebug("Broadcasting $this->broadcast_class, $remote, $this->componentid, " . $this->arData[$local] . "<br>");
      }
   }
   
   function wake() {
      parent::wake();
      $this->initBroadCast();
   }
   
   function initBroadCast() {
      // broadcast set-up
      // make sure these are ready to be stored and iterated through if there is ony a single entry.
      if ($this->debug) {
         $this->logDebug("Local vars: " . print_r($this->local_varname, 1) . " and broadcast vars " . print_r($this->broadcast_varname, 1) . "<br>");
      }
      if (!is_array($this->broadcast_varname)) {
         $this->broadcast_varname = array($this->broadcast_varname);
      }
      if (!is_array($this->local_varname)) {
         $this->local_varname = array($this->local_varname);
      }
      if ($this->debug) {
         $this->logDebug("Local vars: " . print_r($this->local_varname, 1) . " and broadcast vars " . print_r($this->broadcast_varname, 1) . "<br>");
      }
      
      if ($this->broadcast_mode == 'read') {
         // add readable vars to local procs
         for ($i = 0; $i < count($this->local_varname); $i++) {
            $local = $this->local_varname[$i];
            if (!in_array($local, $this->vars)) {
               array_push($this->vars,$local);
            }
         }
      }
   }
   
   function broadRead() {
      
      switch ($this->broadcast_hub) {
         case 'parent':
            $target = $this->parentHub;
         break;
         
         case 'child':
            $target = $this->childHub;
         break;
      }
      
      // this is the broadcast hub itself, give all variables on the broadcast hub
      if (!is_object($target)) {
         $this->logError("<b>Error:</b> Selected hub for $this->name is not an object <br>");
         $this->logDebug("<b>Error:</b> Selected hub for $this->name is not an object <br>");
         return;
      }
      if (!method_exists($target, 'read')) {
         $this->logError("<b>Error:</b> No read method on hub for $this->name <br>");
         return;
      }
      
      // get the raw hub data
      if ($this->debug) {
         $this->logDebug("Reading $this->broadcast_class for " . $this->name . "<br>");
      }
      
      //$hub_data = $target->read(array('dataClass'=>$this->broadcast_class));
      $hub_data = $target->read();
      if ($this->debug) {
         $this->logDebug("Data Retrieved from $target->parentname :" . print_r($hub_data,1) . "<br>");
      }
      
      if (!isset($hub_data[$this->broadcast_class])) {
         if ($this->debug) {
            $this->logDebug("$this->broadcast_class does not exist on target hub $target->parentname <br>");
         }
      } else {
         if ($this->debug) {
            $this->logDebug("Iterating through local Inputs: " . print_r($this->local_varname,1) . "<br>\n");
         }
         // get the array of elements from the hub with this broadcast_class as key
         $thisclass = $hub_data[$this->broadcast_class];
         if ($this->debug) {
            $this->logDebug("In data set: " . print_r($thisclass,1) . "<br>\n");
         }
         for ($i = 0; $i < count($this->local_varname); $i++) {
            $remote = $this->broadcast_varname[$i];
            if ($this->debug) {
               $this->logDebug("Looking for remote variable $remote <br>\n");
            }
            $local = $this->local_varname[$i];
            $remote_array = is_array($thisclass[$remote]) ? $thisclass[$remote] : array();
            if ($this->debug) {
               $this->logDebug("Found " . print_r($remote_array, 1) . "<br>\n");
            }
            $remote_vals = array();
            foreach ($remote_array as $thiskey => $thisrecord) {
               if (isset($thisrecord['value'])) {
                  if ( (!$this->exclude_my_broadcasts) or ($this->componentid <> $thiskey)) {
                     if ($this->debug) {
                        $this->logDebug("Reading from Comp $thiskey (my ID: $this->componentid)<br>\n");
                     }
                     $remote_vals[] = $thisrecord['value'];
                     if (is_array($thisrecord['value'])) {
                       error_log("Component $thiskey sent Array $local to broadcast object $remote ");
                     }
                  }
               }
            }
            if ($this->debug) {
               $this->logDebug("Trying to append $local with Inputs: " . print_r($remote_vals,1) . "<br>\n");
            }
            if (count($remote_vals) > 0) {
               $this->parentobject->appendStateVar($local, $remote_vals, 'refresh');
               if ($this->debug) {
                  $this->logDebug("Parent variable $local = " . $this->parentobject->state[$local] . "<br>\n");
               }
            } else {
               if ($this->debug) {
                  $this->logDebug("No values retrieved for $remote <br>\n");
               }
            }
         }
         
      }

   }

   function showEditForm($formname, $disabled=0) {
      if (is_object($this->listobject)) {

         $returnInfo = array();
         $returnInfo['name'] = $this->name;
         $returnInfo['description'] = $this->description;
         $returnInfo['debug'] = '';
         $returnInfo['elemtype'] = get_class($this);

         $i = 0;

         $aset = $this->listobject->adminsetuparray['broadCastObject'];
         $aset['column info']['broadcast_mode']['onchange'] = "xajax_showOperatorEditResult(xajax.getFormValues(\"$formname\"));";
         foreach (array_keys($aset['column info']) as $tc) {
            $props[$tc] = $this->getProp($tc);
         }
         //$props = array('matrix'=>$this->matrix, 'name'=>$this->name);
         $formatted = showFormVars($this->listobject,$props,$aset,0, 1, 0, 0, 1, 0, -1, NULL, 1);

         $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . "<BR>";
         $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'] . "<BR>";
         $innerHTML .= "<b>Broadcast Class Name:</b> " . $formatted->formpieces['fields']['broadcast_class'] . "<BR>";
         $innerHTML .= "<b>Broadcast Hub:</b> " . $formatted->formpieces['fields']['broadcast_hub'] . "<BR>";
         $innerHTML .= "<b>Broadcast Mode:</b> " . $formatted->formpieces['fields']['broadcast_mode'] . "<BR>";
         $innerHTML .= "<b>Execution Hierarchy:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
         $aset['table info']['formName'] = $formname;
         $parentname = "broadvars_$formname";
         $aset['table info']['parentname'] = $parentname;
         $childname = "onebroadvar_$formname";;
         $aset['table info']['childname'] = $childname . '[]';
         $innerHTML .= showHiddenField("xajax_removeitem", -1, 1);
         
         if (is_array($this->matrix)) {
         } else {
            $this->matrix = array($this->matrix);
         }
         $numcols = intval(count($this->matrix) / $this->numrows);
         //$innerHTML .= "<table id='broadvars_$formname'>";
         $innerHTML .= "<div id='$parentname' name='$parentname'>";
         $mindex = 0;
         for ($i = 0; $i < count($this->local_varname); $i++) {
            
            $innerHTML .= "<div name='$childname"."[]' id='$childname"."[]'>";
            // now, if this is a read call, we let the user create variable names (text field), and hub names
            // if it is a WRITE call, we make a select list for the local param, and a text field for the remote
            switch ($this->broadcast_mode) {
               case 'read':
                  $aset['column info']['local_varname']['type'] = 1;
                  $aset['column info']['broadcast_varname']['type'] = 1;
               break;
               
               default:
                  $aset['column info']['local_varname']['type'] = 3;
                  $aset['column info']['broadcast_varname']['type'] = 1;
               break;
            }
            // just create a record object with the two columns used here, and tell the showformvars routine to 
            // supress missing variables
            $thisrecord = array('broadcast_varname'=>$this->broadcast_varname[$i], 'local_varname'=>$this->local_varname[$i]);
            $innerHTML .= showFormVars($this->listobject,$thisrecord,$aset,1, 0, 0, 1, 1, $disabled, -1, $multiformindex = '',0);
            
            $innerHTML .= "</div>";
         }
         $innerHTML .= "</div>";
         
         $innerHTML .= "<br><b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'];
         
         $returnInfo['innerHTML'] = $innerHTML;

         return $returnInfo;
      }
   }
  
  function setClassProp($propname, $propvalue, $view = '') { 
    switch ($propname) {
      case 'broadcast_params':
        switch ($view) {
          case 'array':
          // we know that broadcast_params
          //error_log("setClassProp called with $propname = " . print_r($propvalue,1));
            // this is a special array variable that we split into local_varname and broadcast_varname 
            $this->local_varname = array();
            $this->broadcast_varname = array();
            foreach ($propvalue as $pair) {
              //error_log("Trying to write array: " . print_r($pair,1));
              $this->local_varname[] = $pair[0];
              $this->broadcast_varname[] = $pair[1];
            }
          break;
          default:
            parent::setClassProp($propname, $propvalue, $view);
          break;
        }
      break;
      default:
        parent::setClassProp($propname, $propvalue, $view);
      break;
    }
  }

}


class broadCastHub {
   // this is a bare-bones class for passing messages in broadcast form.
   // broadcast data packets have the following attributes:
      // dataClass
      // dataName
      // sourceID (unique to the component that sent it)
      // efficient storage would be to key things by their sourceID, 
      //    since all packets from a single sourceID would come in at once
      // efficient RETRIEVAL would be to key things by their infoClass, 
      //   since retrieval would be expected to go by class
   var $hub_data = array();
   var $parentname = '';
   var $loggable = 0;
   
   function broadCast($dataClass, $dataName, $sourceID, $dataValue) {
      $this->hub_data[$dataClass][$dataName][$sourceID]['value'] = $dataValue;
   }
   
   // will allow a detailed read to get source object id's as well
   // we could pass all sorts of ancillary information keyed to the object ID, such as geography, and name
   // this would allow for a robust reporting facility, keyed by object ID
   
   function read($dataParams=array()) {
      // this would be in the form:
      // of an array of criteria that may have at least the dataClass, and any of the following:
      // dataClass, dataName, sourceID
      // Ex:
      // array('dataClass'=>'hydroObject', 'dataName'=>'Qout');
      $retarr = array();
      if (count($dataParams) == 0) {
         // just send it all
         return $this->hub_data;
      } else {
         if (isset($dataParams['dataClass'])) {
            // we can proceed, otherwise we would just get all data
            if (isset($this->hub_data[$dataParams['dataClass']])) {
               $retarr[$dataParams['dataClass']] = $this->hub_data[$dataParams['dataClass']];
            }
            // for now we do not allow finer querying, but we can in the future
            /*
            $dn = '';
            if (isset($thisparam['dataName'])) {
               // screen on variable name
               $id = '';
               if (isset($thisparam['sourceID']) {
               }
            }
            */
         }
      }
   }
   
}

class modelContainer extends modelObject {
   # Model Container
   # Acts as an container to other modeling object components
   # Calculates the run time order of each component
   # Supplies the timer object to each component
   # Executes the step() call for each component
   # Keeps a log of modeling operations, and the status of each component (if there is an error value)
   var $starttime = '1999-01-01';
   var $endtime = '1999-12-31';
   var $modelhost = 'localhost';
   var $nextid = 0;
   var $dt = 86400; # time-step seconds (1 hour = 3600, 1 day = 86400)
   var $component_type = 2;
   var $outputinterval = 1;
   var $outstring = '';
   var $graphstring = '';  # stores images urls after model runs
   var $iscontainer = 1; # only true for objects of type "model container" or it's subclasses
   var $systemlog_obj = -1; // listobject for system log (may differ from model listobject)
   var $syslogrec = -1; # a flag to tell the container if a syslog record is existing for this object
   var $runid = -1; # a flag to tell the container if a syslog record is existing for this object
   var $compexectimes = array();
   var $standalone = 1; // can this container be run as standalone?  
                        // This is useful for "tree" based running schemas, where we will run
                        // different model components simultaneously to take advantage of multiple processors
   function wake() {
      parent::wake();
      // make sure that time is properly 
      $this->setModelTime($this->starttime, $this->endtime);
   }
   
   function setModelTime($starttime, $endtime = '') {
      // this sets and properly formats time, to allow for using offest/relative times (like "+7 days" and "-7 days"
      if (!strtotime($starttime)) {
         $starttime = date();
      }
      $conv_time = new DateTime($starttime);
      $this->starttime = $conv_time->format('Y-m-d H:i:s');
      if (($endtime <> '') and strtotime($endtime)) {
         $conv_time = new DateTime($endtime);
         $this->endtime = $conv_time->format('Y-m-d H:i:s');
      } else {
         $this->endtime = $this->starttime;
      }
   }
   
   function init() {
      parent::init();
      $this->setSessionID();
      //error_log("$this->name orderOperations() ");
      $this->orderOperations();
      //error_log("$this->name orderComponents() ");
      $this->orderComponents();
      if ($this->debug) {
         $this->logDebug("Initializing components<br>");
      }
      $this->outstring .= "Components included in model run:\n";
      foreach($this->compexeclist as $thiscompid) {
         $thiscomp = $this->components[$thiscompid];
         if ($this->debug) {
            $this->logDebug($this->name . " Initializing component $thiscomp->name <br>");
         }
         $this->systemLog(" Initializing component $thiscomp->name \n");
         //error_log(" Initializing component $thiscomp->name ");
         $this->outstring .= "* $thiscomp->name \n";
         $thiscomp->setProp('sessionid', $this->sessionid);
         $thiscomp->parentHub = $this->childHub;
         $thiscomp->init();
      }
      $this->outstring .= "\n";
      $this->syslogrec = -1;
      $this->compexectimes = array();
    //error_log("$this->name finished init()");
   }
   
   function setSessionID() {
      if ($this->sessionid == 0) {
         $this->sessionid = $this->componentid;
      }
   }

   function step() {
      // many object classes will subclass this method.  
      // all step methods MUST call preStep(),execProcessors(), postStep()
      $this->preStep();
      
      if ($this->debug) {
         $this->logDebug("<b>$this->name step() method called at hour " . $this->state['hour'] . " on " . $this->state['thisdate'] . ".</b><br>\n");
      }
      if ($this->debug) {
         $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
      }

      # data aquisition first - should this be opposite for container? i.e., should they get their inputs After their
      # components are executed?  Will this assist in the lag time phenomenon?
      # or should we do it twice?  With the second one only inputs from objects that are contained by this one, 
      # getContainedInputs() method?

      # then process calculations
      $this->execComponents();
      // we have just executed our children and gotten the results from their broadcasts
      // now we will execute our local sub-comps to process any data obtained from children via broadcast
      $this->execProcessors();
      // refresh inputs from contained objects, in order to communicate with other objects that link to this object
      // via a cross-container linkage
      // we also implicitly update child broadcasts by reading them at the end of execComponents
      $this->getInputs();
      $this->postStep();

   }


   function getContainedInputs() {
      if ($this->debug) {
         $this->logDebug("Getting Inputs for $this->name <br>");
      }
      foreach (array_keys($this->inputs) as $varname) {
         if ($this->debug) {
            $this->logDebug("Getting Input $varname for $this->name <br>");
         }
         # reset each input param to 0.0 for the beginning of the timestep
         $this->state[$varname] = 0.0;

         $k = 0;
         foreach ($this->inputs[$varname] as $thisin) {
            $outparam = $thisin['param'];
            $inobject = $thisin['object'];
            $lv = $thisin['value'];
            if ($this->debug) {
               $iname = $inobject->name;
               if ($varname <> 'the_geom') {
                  $this->logDebug("Searching $iname ($outparam) for $varname - last value = $lv... ");
               }
            }
            # accumulate inputs if they are numeric,
            # since we may input to the same input multiple sources
            $inval = $inobject->getValue($this->timer->timeseconds, $outparam);
            $this->inputs[$varname][$k]['value'] = $inval;
            # if the child object returns NULL, we don't use it
            if (!($inval === NULL)) {
               if (is_numeric($inval)) {
                  $this->state[$varname] += $inval;
               } else {
                  $this->state[$varname] = $inval;
               }
               if ($this->debug) {
                  $iname = $inobject->name;
                  if ($varname <> 'the_geom') {
                     $this->logDebug("updated with $outparam = $inval from Input: $iname, input total = " . $this->state[$varname] . "<br>\n");
                  }
               }
            }
            $thisin['value'] = $inval;
            $k++;
         }
      }
      if ($this->debug) {
         $sv = $this->state;
         if (isset($sv['the_geom'])) {
            $sv['the_geom'] = 'HIDDEN';
         }
         $this->logDebug("Inputs gathered. State array: " . print_r($sv,1) . "\n<br>");
      }
      # now, process lookups, replaces lookup key with value in state variable
      $this->doLookups();
      if ($this->debug) {
         $this->logDebug("Lookups calculated. State array: " . print_r($sv,1) . "\n<br>");
      }

   }

   function systemLog($mesg, $status_flag=1) {
      $pid = getmypid();
      if (is_object($this->systemlog_obj)) {
         setStatus($this->systemlog_obj, $this->sessionid, $mesg, $this->modelhost, $status_flag, $this->runid, $pid);
         
         if (count($this->childstatus) > 0) {
            foreach ($this->childstatus as $thischild) {
               setStatus($this->systemlog_obj, $thischild, "(run $this->sessionid)" . $mesg, $this->modelhost, $status_flag, $this->runid, $pid);
            }
         }
         
      } else {
         if (is_object($this->parentobject)) {
            $this->parentobject->systemLog($mesg, $status_flag);
         }
      }
   }
   
   function cleanUp() {
      # remove all the temp tables associated with this object
      if (is_object($this->listobject)) {
         if ($this->listobject->tableExists($this->dbtblname)) {
            $this->listobject->querystring = "  drop table $this->dbtblname ";
            $this->listobject->performQuery();
         }
      }
      # iterate through each equation stored in this object
      foreach ($this->compexeclist as $thiscomp) {
         # evaluate the equation
         if ($this->debug) {
            $this->logDebug("Cleaning Up $thiscomp<br>\n");
         }
         $outmesg = "Cleaning Up $thiscomp<br>\n";
         //error_log($outmesg);
         $this->systemLog($outmesg);

         # set all required inputs for the equation
         if (is_object($this->components[$thiscomp])) {
            if (method_exists($this->components[$thiscomp], 'cleanUp')) {
               $this->components[$thiscomp]->cleanUp();
               unset($this->components[$thiscomp]);
            }
         }
      }
      parent::cleanUp();
   }

   function finish() {
      $max_used = memory_get_usage(true) / (1024.0 * 1024.0);
      $this->outstring .= " Memory use at simulation end: $max_used Mb / Flush Parameters: " . $this->timer->max_memory_mb . " * " . $this->timer->max_memory_pct . "\n";
      $outmesg = "Calling finish() Method on contained model components.";
      //error_log($outmesg);
      $this->systemLog($outmesg);
      parent::finish(); // this calls the finish routine on any sub-components/aka processors 
      $outmesg = "Gathering info and error reports for model components.";
      //error_log($outmesg);
      $this->systemLog($outmesg);

      # iterate through each contained object in this model run
      foreach ($this->compexeclist as $thiscomp) {
         # evaluate the equation
         if ($this->debug) {
            $this->logDebug("Finishing $thiscomp<br>\n");
         }
         $outmesg = "Finishing $thiscomp<br>\n";
         //error_log($outmesg);
         $this->systemLog($outmesg);
         $thisname = $this->components[$thiscomp]->name;
         # call finish method
         if ($this->components[$thiscomp]->debug) {
            error_log("Component $thiscomp finish() method called with debug enabled.");
         }
         $this->components[$thiscomp]->finish();
         // stash the report of average time of execution in the outstring on this parent object
         //$avgexec = $this->compexectimes[$thiscomp] / $this->timer->steps;
         $avgexec = $this->components[$thiscomp]->meanexectime;
         $this->outstring .= "Component $thisname ($thiscomp) avg. exec time: $avgexec \n";

         # now get reporting data from any contained components
         # check for report files
         if ( (strlen($this->components[$thiscomp]->logfile) > 0) and (file_exists($this->components[$thiscomp]->outdir . '/' . $this->components[$thiscomp]->logfile)) ) {
            $this->reportstring .= "For log click here: <a href='" . $this->components[$thiscomp]->outurl;
            $this->reportstring .= '/' . $this->components[$thiscomp]->logfile . "' target=_new>";
            $this->reportstring .= $thisname . ' log file';
            $this->reportstring .= '</a>';
            if ($this->components[$thiscomp]->debug) {
               error_log("Log File for $thiscomp = " . $this->components[$thiscomp]->logfile);
            }
         } else {
            if ($this->components[$thiscomp]->debug) {
               error_log("No log File created for $thiscomp = " . $this->components[$thiscomp]->logfile);
            }
         }
         # check for debugging info
         if ( (strlen($this->components[$thiscomp]->debugstring) > 0) ) {
            if ($this->components[$thiscomp]->debug) {
               error_log("Appending debug info for $thiscomp ");
            }
            $this->debugstring .= "<b>Debug info for: </b>" . $thisname . '<br>';
            $this->debugstring .= $this->components[$thiscomp]->debugstring . '<br>';
            # CLEAN UP to save memory, keep it from ballooning out
            $this->components[$thiscomp]->debugstring = ''. '<br>';
         } else {
            if ($this->components[$thiscomp]->debug) {
               error_log("Zero length debug string for $thiscomp - will not append ");
            }
         }

         # get comp graphs
         $outmesg = "Getting graphs on $thiscomp<br>\n";
         $this->getCompGraphs($this->components[$thiscomp]);

         # show component output reports
         if (strlen($this->components[$thiscomp]->reportstring) > 0) {
            $this->reportstring .= "<b>Reports for: </b>" . $thisname . '<br>';
            $this->reportstring .= $this->components[$thiscomp]->description . "<br>";
            $this->reportstring .= $this->components[$thiscomp]->reportstring . "<br>";
            # CLEAN UP to save memory, keep it from ballooning out
            $this->components[$thiscomp]->reportstring = ''. '<br>';
         }
         # show component output reports
         if (strlen($this->components[$thiscomp]->errorstring) > 0) {
            $this->errorstring .= "<b>Errors for: </b>" . $thisname . '<br>';
            $this->errorstring .= $this->components[$thiscomp]->description . "<br>";
            $this->errorstring .= $this->components[$thiscomp]->errorstring . "<br>";
            # CLEAN UP to save memory, keep it from ballooning out
            $this->components[$thiscomp]->errorstring = ''. '<br>';
         }
         unset($this->components[$thiscomp]);
      }

      $this->reportstring .= "Finished Model run for $this->name.<br>";

   }

   function reDraw() {

      if ($this->debug) {
         $this->logDebug("Trying to redraw children<br>\n");
      }
      if ($this->sessionid == 0) {
         $this->sessionid = $this->componentid;
      }
      # iterate through each contained object
      foreach ($this->compexeclist as $thiscomp) {
         # evaluate the equation
         if ($this->debug) {
            $this->logDebug("Checking for reDraw() on $thiscomp<br>\n");
         }
         # set all required inputs for the equation
         if (method_exists($this->components[$thiscomp], 'reDraw')) {
            if ($this->debug) {
               $this->logDebug("Redrawing $thiscomp<br>\n");
            }
            $this->components[$thiscomp]->setProp('sessionid', $this->sessionid);
            $this->components[$thiscomp]->reDraw();
            $this->getCompGraphs($this->components[$thiscomp]);
         }
      }

   }

   function getCompGraphs($thiscomp) {

      # check for formatted graph output (overrides a URL)
      if (strlen($thiscomp->graphstring) > 0) {
         $this->graphstring .= $thiscomp->graphstring . "<br>";
      } else {
         # check for graph output in the form of a URL
         if (strlen($thiscomp->imgurl) > 0) {
            //$this->graphstring .= "<img src='" . $thiscomp->imgurl . "'><br>";
            $this->graphstring .= "<a class='mH' onClick='document[\"image_screen\"].src = \"" . $thiscomp->imgurl . "\"; '>$thiscomp->name - $thiscomp->title</a> | ";
            $this->graphstring .= "<a href='" . $thiscomp->imgurl . "' target='_new'>View Image in New Window</a><br>";
         }
     }
   }
   
   function initTimer() {
      //error_log("<b>Creating Timer<br>");
      $newtimer = new simTimer;
      //error_log("<b>Initializing Timer properties<br>");
      $newtimer->setStep($this->dt);
      $newtimer->setTime($this->starttime, $this->endtime);
      $this->starttime = $newtimer->thistime->format('Y-m-d H:i:s');
      $this->endtime = $newtimer->endtime->format('Y-m-d H:i:s');
      #$this->logDebug($newtimer);
      error_log("<b>Setting Timer<br>");
      $this->setSimTimer( $newtimer);
   }

   function runModel() {
      error_log("<b>Beginning Model Run<br>");
      $this->logDebug("<b>Error info for ModelContainer: </b>" . $this->name . '<br>');
      $this->systemLog("<b>Beginning Model Run<br>");
      if ($this->debug) {
         $this->logDebug("<b>Beginning Model Run</b><br>");
         $this->logDebug("<b>Time of Run Start:</b> " . date('r') . "<br>");
         #$this->logDebug($this->timer);
      }
      $this->initTimer();
      if ($this->debug) {
         $this->logDebug("<b>Time set on all subcomps</b><br>");
      }
      $this->outstring .= "\n\nBeginning Model Run at: " . date('r') . "\n";
      $this->outstring .= "Model Time Span Set:" . $this->starttime . " to " . $this->endtime . "\n";
      $this->outstring .= "Setting Component Session ID to: " . $this->sessionid . "\n";
      if ($this->cascadedebug) {
         error_log("<b>Setting Debug Mode on all children<br>");
         foreach($this->components as $thiscomp) {
            $thiscomp->setDebug($this->debug, $this->debugmode);
            $thiscomp->runid = $this->runid;
         }
      }
      error_log("<b>Initializing components<br>");
      $this->setSessionID();
      $this->systemLog("<b>Initializing components<br>");
      $this->outstring .= "Initializing model components at: " . date('r') . "\n";
      $this->init();
      $i = 0;

      if ($this->bufferlog == 1) {
         $this->setBuffer(1);
         //error_log("Database log queries will be sent asynchronously (buffered).");
      } else {
         $this->setBuffer(0);
         //error_log("Database log queries will be sent synchronously (non-buffered).");
      }
      $this->outstring .= "Stepping through model execution at: " . date('r') . "\n";
      error_log("<b>Iterating through timesteps<br>");
      while (!$this->timer->finished) {
         if (intval($i / $this->outputinterval) == ($i / $this->outputinterval)) {
            $ts = $this->timer->thistime->format('r');
            $outmesg = "Executing step $i @ time: $ts";
            $this->outstring .= $outmesg . "\n";
            $this->systemLog($outmesg);
         }
         // moved this to eliminate over-stepping
         //$this->timer->step();
         $this->step();
         $i++;
         $msgs = $this->checkMessages();
         foreach ($msgs as $msg) {
            //error_log("Message sent to $this->name : " . $msg['msg_type'] . " \n");
            switch ($msg['msg_type']) {
               case 'end':
                  // a call to end the model had come in, gracefully exit
                  $this->timer->finished = 1;
                  error_log("Model run interrupt requested for $this->name : " . $msg['msg_type'] . " \n");
               break;
            }
         }
         if (!$this->timer->finished) {
            $this->timer->step();
         }
      }
      $outmesg = "<b>Finished model interations -- starting post processing<br>";
      error_log($outmesg);
      $this->systemLog($outmesg,1);
      
      # make sure that all pending log queries have been processed
      if (is_object($this->listobject)) {
         $this->listobject->flushQueryBuffer();
      }
    

      # now, do any post-processing
      $outmesg = "<b>Post-processing model components at:</b> " . date('r') . "<br>";
      $this->outstring .= $outmesg;
      $this->systemLog($outmesg);
      $this->finish();
      $outmesg = "<b>Finished post-processing. model run ended.<br>";
      error_log($outmesg);
      $this->systemLog($outmesg,0);
   }
   
   function checkMessages() {
      $msgs = checkMessages($this->systemlog_obj, $this->sessionid, $this->modelhost, '', $this->runid);
      if (count($msgs) > 0) {
         clearMessages($this->systemlog_obj, $this->sessionid, $this->modelhost, '', $this->runid);
      }
      return $msgs;
   }

   function addComponent($thiscomp, $componentid = '') {
      # add a modeling component to this global model container
      if ($componentid == '') {
         $componentid = $thiscomp->componentid;
      }
      if (!is_array($this->components)) {
         $this->components = array();
      }
      if ( !( in_array($componentid, array_keys($this->components)) ) ) {

         $this->components[$componentid] = $thiscomp;
         if ($this->cascadedebug) {
            $this->components[$componentid]->debug = $this->debug;
         }
         #$this->components['comp' . $this->nextid]->setCompID('comp' . $this->nextid);
         array_push($this->compexeclist, $componentid);
         $this->compexectimes[$componentid] = 0.0; // initialize the timer 
         #$this->nextid++;
         if ($this->debug) {
            #error_log("Adding component " . $thiscomp->name . ' ID: ' . $thiscomp->componentid);
            $this->logDebug("Container " . $this->name . " is adding component " . $thiscomp->name . "(" . $componentid . ")<br>\n");
         }
         # add to exec list in order of creation, may later order by precedence with the
         # function orderOperations()
      } else {
         if ($this->debug) {
            #error_log("Adding component " . $thiscomp->name . ' ID: ' . $thiscomp->componentid);
            $this->logDebug("<b>Error: </b>Component named " . $thiscomp->name . " (" . $componentid . ") already exists in container " . $this->name . ".  Cannot Add.<br>\n");
         }
      }

      return $thiscomp;

   }


   function execComponents() {

      if ($this->debug) {
         $this->logDebug("Going through components for $this->name.<br>\n");
         #error_log("Going through components for $this->name.");
      }
      # iterate through each equation stored in this object
      foreach ($this->compexeclist as $thiscomp) {
         # evaluate the equation
         if ($this->debug) {
            $this->logDebug("Executing $thiscomp<br>\n");
            #error_log("Executing $thiscomp .");
         }
         # set all required inputs for the equation
         if (method_exists($this->components[$thiscomp], 'step' )) {
            $this->components[$thiscomp]->step();
            if ($this->debug) {
               $this->logDebug("$thiscomp Finished ($et seconds). <br>\n");
               #error_log("$thiscomp Finished ($et seconds). <br>\n");
               #error_log("$thiscomp Finished.");
            }
         } else {
            if ($this->debug) {
               $arr = get_class_methods(get_class($this->components[$thiscomp]));
               $this->logDebug("Method step() on $thiscomp is undefined - skipping. ( Defined methods: "  . print_r($arr,1) . ") \n<br>");
               #Qerror_log("Method step() on $thiscomp is undefined - skipping. ( Defined methods: "  . print_r($arr,1) . ") ");
            }
         }
      }
      $this->readChildBroadCasts();
   }

   function orderComponents() {

      $dependents = array();
      $execlist = array();

      # compile a list of independent and dependent variables
      foreach ($this->components as $thiscomp) {
         // use new getObjectDependencies() method
         $deps = $thiscomp->getObjectDependencies();
         if ($this->debug) {
            $this->logDebug("<br>getObjectDependencies returned " . count($deps) . " <br>");
         }
         $dependents[$thiscomp->componentid] = array('compid'=>$thiscomp->componentid, 'invars'=>$deps, 'object'=>$thiscomp);
         
         /*
         if (!in_array($thiscomp->componentid, array_keys($dependents))) {
            if ($this->debug) {
               $cn = $thiscomp->name;
               $ci = $thiscomp->componentid;
               $this->logDebug("<br>Adding component $cn ($ci) to queue <br>\n");
            }
            # need to add this named input
            $dependents[$thiscomp->componentid] = array('compid'=>$thiscomp->componentid, 'invars'=>array(), 'object'=>$thiscomp);
         }
         if ($this->debug) {
            $this->logDebug("<br>This components inputs:<br>");
            $this->logDebug(array_keys($thiscomp->inputs));
            $this->logDebug("<br>");
         }
         foreach (array_keys($thiscomp->inputs) as $thisinputname) {
            if ($this->debug) {
               $this->logDebug("Checking $thisinputname ");
            }
            foreach ($thiscomp->inputs[$thisinputname] as $thisinput) {
               $thisinobj = $thisinput['object'];
               if ($this->debug) {
                 # $this->logDebug("This input object: <br>");
                  #$this->logDebug($thisinobj) ;
                  #$this->logDebug("<br>");
               }
               if ( !in_array($thisinobj->componentid, $dependents[$thiscomp->componentid]['invars']) ) {
                  if ($this->debug) {
                     $ci = $thisinobj->componentid;
                     $this->logDebug(".. Adding $ci ");
                  }
                  array_push($dependents[$thiscomp->componentid]['invars'], $thisinobj->componentid );
               }
            }
            if ($this->debug) {
               $this->logDebug("<br>Finished checking $thisinputname <br>");
            }
         }
         */
      }
      # now check the list of independent variables for each processor,
      # if none of the variables are in the current list of dependent variables
      # put it into the execution stack, remove from queue
      if ($this->debug) {
         $this->logDebug("<br>Initial Queue:<br>");
         $this->logDebug(array_keys($dependents));
         foreach ($dependents as $thisdep) {
            $dn = $thisdep['compid'];
            $dvars = $thisdep['invars'];
            $this->logDebug("$dn = ");
            $this->logDebug($dvars);
            $this->logDebug("<br>");
         }
         $this->logDebug("<br>");
      }
      $queue = $dependents;
      $i = 0;
      while (count($queue) > 0) {
         $thisdepend = array_shift($queue);
         $newqueue = array();
         # array shift resets the keys, destroying our link by compid, thus, we need to
         # restore this after shifting
         foreach ($queue as $thisel) {
            $newqueue[$thisel['compid']] = $thisel;
         }
         $queue = $newqueue;
         $pvars = $thisdepend['invars'];
         $thiscompid = $thisdepend['compid'];
         if ($this->debug) {
            $this->logDebug("Checking $thiscompid variables ");
            $this->logDebug($pvars);
            $this->logDebug(" <br>\nin ");
            $this->logDebug(array_keys($queue));
            $this->logDebug("<br>\n");
         }
         if (!$this->array_in_array($pvars, array_keys($queue))) {
            array_push($execlist, $thiscompid);
            $i = 0;
            if ($this->debug) {
               $this->logDebug("Not found, adding $thiscompid to execlist.<br>\n");
            }
         } else {
            # put it back on the end of the stack
            if ($this->debug) {
               $this->logDebug("Found.<br>\n");
            }
            $queue[$thiscompid] = $thisdepend;
         }
         $i++;
         if ( ($i > count($queue)) and (count($queue) > 0)) {
            # we have reached an impasse, since we cannot currently
            # solve simultaneous variables, we just put all remaining on the
            # execlist and hope for the best
            # a more robust approach would be to determine which elements are in a circle,
            # and therefore producing a bottleneck, as other variables may not be in a circle
            # themselves, but may depend on the output of objects that are in a circle
            # then, if we add the circular variables to the queue, we may be able to continue
            # trying to order the remaining variables

            # first, create a list of execution hierarchies and compids
            $hierarchy = array();
            foreach ($queue as $thisel) {
               $hierarchy[$thisel['compid']] = $thisel['object']->exec_hierarch;
            }
            # sort in reverse order of hierarchy
            # then, look at exec_hierarch property, if the first element is higher priority than the lowest in the stack
            # pop it off the list, and add it to the queue
            # then, after doing that, we can go back, set $i = 0, and try to loop through again,
            arsort($hierarchy);
            $keyar = array_keys($hierarchy);
            if ($this->debug) {
               $this->logDebug("Cannot determine sequence of remaining variables, searching manual execution hierarchy setting.<br>\n");
            }
            $firstid = $keyar[0];
            $fh = $hierarchy[$firstid];
            $mh = min(array_values($hierarchy));
            if ($this->debug) {
               $this->logDebug("Highest hierarchy value = $fh, Lowest = $mh.<br>\n");
            }
            if ($fh > $mh) {
               # pop off and resume trying to sort them out
               $newqueue = array_diff_assoc($queue, array($firstid => $queue[$firstid]) );
               array_push($execlist, $firstid);
               $i = 0;
               if ($this->debug) {
                  $this->logDebug("Elelemt " . $queue[$firstid]['object']->name . ", with hierarchy " . $hierarchy[$firstid] . " added to execlist.<br>\n");
               }
               $queue = $newqueue;
            } else {

               # otherwise, we will add the rest to the list by numb3er of dependents, and then break
               if ($this->debug) {
                  $this->logDebug("Can not determine linear sequence for the remaining components. <br>\n");
                  //$this->logDebug(print_r($queue,1));
                  $this->logDebug("<br>\nHoping their execution order does not matter!.<br>\n");
               }
               $newexeclist = array_merge($execlist, array_keys($queue));
               $execlist = $newexeclist;
               break;
            }
         }
      }
      $this->compexeclist = $execlist;
      if ($this->debug) {
         $this->logDebug("<br>\nExecution list: ");
         $this->logDebug($this->compexeclist);
         $this->logDebug("<br>\n");
      }
      $this->outstring .= "\n\nExecution list: ";
      $this->outstring .= print_r($this->compexeclist,1);
      $this->outstring .= "\n\n";
   }

}

class simTimer {

   var $thistime;
   var $debug = 0;
   var $endtime;
   var $timeseconds;
   var $year;
   var $month;
   var $day;
   var $hour;
   var $modays;
   var $finished = 0;
   var $dt = 60.0;
   var $listobject = -1;
   var $log2db = 0;
   var $max_memory_mb = 2048; // megabytes
   var $max_memory_pct = 0.85; // percentage of max memory at which to initiate flushing of log tables
   var $steps = 0;
   var $timestamp;
   # simple time functions
   var $timestart = 0;
   var $timeend = 0;
   var $splittime = -1;

   function init() {
      $this->thistime = new DateTime();
      $this->endtime = $this->thistime;
      $this->modays = $this->thistime->format('t');
      $this->year = $this->thistime->format('Y');
      $this->month = $this->thistime->format('n');
      $this->day = $this->thistime->format('j');
      $this->jday = $this->thistime->format('z');
      $this->hour = $this->thistime->format('G');
      $this->timeseconds = $this->thistime->format('U');
      $this->timestamp = $this->thistime->format('r');
      $this->finished = 0;

      $this->dbtblname = $this->tblprefix . 'datalog';
   }
   
   
   function microtime_float()
   {
      list($usec, $sec) = explode(" ", microtime());
      return ((float)$usec + (float)$sec);
   }

   function timeSplit()
   # starts, or takes a split
   {
      $this->timeend = $this->microtime_float();
      $this->splittime = $this->timeend - $this->timestart;
      $this->timestart = $this->microtime_float();
      $split = 0;
      if ($this->splittime > 0) {
         $split = $this->splittime;
      }
         return $split;
   }

   function setStep($dt) {
      $this->dt = $dt;
   }

   function setTime($starttime, $endtime='') {
      $this->thistime = new DateTime($starttime);
      if ($endtime <> '') {
         $this->endtime = new DateTime($endtime);
      }
   }

   function step() {
      $this->thistime->modify("$this->dt seconds");
      $this->modays = $this->thistime->format('t');
      $this->year = $this->thistime->format('Y');
      $this->month = $this->thistime->format('n');
      $this->day = $this->thistime->format('j');
      $this->jday = $this->thistime->format('z');
      $this->hour = $this->thistime->format('G');
      $this->timeseconds = $this->thistime->format('U');
      $this->timestamp = $this->thistime->format('r');
      $this->state['thisdate'] = $this->thistime->format('Y-m-d');
      if ($this->debug) {
         $this->logDebug("<br><b>$this->name step() method called at hour " . $this->state['hour'] . " on " . $this->state['thisdate'] . ".</b><br>\n");
      }
      if ($this->thistime->format('U') > $this->endtime->format('U')) {
         $this->finished = 1;
      } else {
         $this->finished = 0;
      }
      $this->steps++;
   }
}

class dataMatrix extends modelSubObject {
   # this is a general purpose class to provide a multi-dimensional data array sub-component
   # it can be used directly or subclassed to contain the parameter tables from model files such as HSPF UCI's
   # or can possibly be used to hold geographic points, and act as a database or spreadsheet.
   
   // this class may function in one of four ways:
   // 1) as an array variable, returning an array of values in response to getValue method
   // 2) as a 1-column lookup, using the first column as the key, and returning all subsquent column in that row
   //    in response to getValue method - if there are only 2 columns, it will return a scalar value for 2nd column
   // 3) as a 2-column lookup, using two variable inputs to select from a single matching row-column value, 
   //    returns scalar ALWAYS
   // 4) as a 1.5-column lookup
   
   // additionally, the return values may be variable references
   
   var $object_class = 'dataMatrix'; // will be set externally but could be overridden
   // How to evaluate each cell in the matrix (type reference looks for variable in state array, type auto uses old method which guesses based on contents at each timestep)
   var $eval_type = 'auto'; // auto, numeric, string, reference 
   var $valuetype = 0; // 0 - returns entire array (normal), 1 - single column lookup (col), 2 - 2 column lookup (col & row)
   var $value_dbcolumntype = ''; // can be a db type, or an equation, which resolves to numeric in db storage
   var $keycol1 = ''; // key for 1st lookup variable
   var $lutype1 = 0; // lookup type for first lookup variable: 0 - exact match; 1 - interpolate values; 2 - stair step
   var $keycol2 = ''; // key for 2nd lookup variable
   var $lutype2 = 0; // lookup type for second lookup variable
   var $matrix = array();
   var $lasteval_ts = null;
   var $matrix_formatted = array();
   var $matrix_rowcol = array();
   // if this is set to true, the first row will be ignored in data requests, assuming that these are labels only
   var $firstrow_colnames = 0;
   var $firstrow_ro = 0; // is the first row read-only?  Will be shown as hidden if so.
   // if this is set to true, the first column values will be ignored, assuming that these are labels only
   var $firstcol_keys = 0;
   var $numrows = 1;
   var $numcols = 1;
   var $rownames = array();
   var $colnames = array();
   var $serialist = 'matrix'; # tells routines to serialize this before storing in XML
   var $cellsize = 4;
   var $fixed_cols = 0; # whether or not to restrict the number of rows or columns
   var $fixed_rows = 0;
   var $loggable = 1; // can log the value in a data table
   var $text2table = ''; // text to derive columns
   var $delimiter = 0; // 0|Comma,1|Tab,2|pipe,3|Space
   var $autosetvars = 0; // 1 = will create automatic wvars on parent based on column headers
   var $autovars = null; // 1 = will create automatic wvars on parent based on column headers
   var $json2d = TRUE;


   function subState() {
      $this->formatMatrix();
      if (!is_array($this->wvars)) {
         $this->wvars = array();
      }
      if ($this->autosetvars) {
         $this->autovars = array_values(array_slice($this->matrix_rowcol[0], 1));
         $this->wvars = array_unique(array_merge($this->wvars, $this->autovars));
         //error_log("Setting wvars on $this->name = " . print_r($this->wvars,1));
      }
      parent::subState();
   }
   
   function sleep() {
      $this->autovars = null;
      parent::sleep();
   }

   function showEditForm($formname, $disabled=0) {
      if (is_object($this->listobject)) {

         $innerHTML = '';
         $returnInfo = array();
         $returnInfo['name'] = $this->name;
         $returnInfo['description'] = $this->description;
         $returnInfo['debug'] = '';
         $returnInfo['elemtype'] = get_class($this);
         if (is_array($this->matrix)) {
         } else {
            $this->matrix = array($this->matrix);
         }
         # set up div to contain each seperate multi-parameter block
         $aset = $this->listobject->adminsetuparray['dataMatrix'];
         if ($this->debug) {
            $innerHTML .= "Getting columns for this class " . get_class($this) . "<br>";
         }
         foreach (array_keys($aset['column info']) as $tc) {
            $props[$tc] = $this->getProp($tc);
         }
         if ($this->debug) {
            $innerHTML .= "Columns are " . print_r($props,1) . "<br>";
         }
         // get form variables in formatted HTML input fields
         $formatted = showFormVars($this->listobject,$props,$aset,0, 1, 0, 0, 1, 0, -1, NULL, 1);

         //$props = (array)$this;
         $innerHTML .= $this->showFormHeader($formatted, $formname, $disabled);
         $innerHTML .= "<hr>";
         $innerHTML .= $this->showFormBody($formatted, $formname, $disabled);
         $innerHTML .= $this->showFormFooter($formatted, $formname, $disabled);
         
         
         
         // show the formatted matrix
         //$this->formatMatrix();
         //$innerHTML .= print_r($this->matrix_formatted,1) . "<br>";

         $returnInfo['innerHTML'] = $innerHTML;

         return $returnInfo;

      }
   }
   
   function showFormHeader($formatted, $formname, $disabled = 0) {
      $innerHTML = '';
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . " | ";
      $innerHTML .= "<b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'] . " | ";
      $innerHTML .= "<b>Loggable?:</b> " . $formatted->formpieces['fields']['loggable'] . " | ";
      $innerHTML .= "<b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'] . "<BR>";
      $innerHTML .= "<b>Value Type:</b> " . $formatted->formpieces['fields']['valuetype'] . " | ";
      $innerHTML .= "<b>Default Value:</b> " . $formatted->formpieces['fields']['defaultval'] . "<br>";
      $innerHTML .= "<b>Auto-set Parent Vars?:</b> " . $formatted->formpieces['fields']['autosetvars'] . "<br>";
      $innerHTML .= " <b>Row Key:</b> " . $formatted->formpieces['fields']['keycol1'];
      $innerHTML .= " <b>Type:</b>" . $formatted->formpieces['fields']['lutype1'] ;
      $innerHTML .= " <b>Col Key:</b> (2-d) " . $formatted->formpieces['fields']['keycol2'];
      $innerHTML .= " <b>Type:</b>" . $formatted->formpieces['fields']['lutype2'];
      return $innerHTML;
   }
   
   function showFormBody($formatted, $formname, $disabled = 0) {
      $innerHTML = '';
      
      // use the more robust function to create this
      //list($fixed_cols, $fixed_rows, $valuetype, $cellsize)
      //$asparams = "$this->fixed_cols:$this->fixed_rows:$this->valuetype:$this->cellsize";
      
      $innerHTML .= showHiddenField("numrows", $this->numrows, 1);
      $numcols = intval( count($this->matrix) / $this->numrows);
      if ($this->debug) {
         $innerHTML .= "<br>Rows = $this->numrows , Cols = $this->numcols , derived cols: $numcols<br>";
      }
      // Value Type Array / 1-column Lookup / 2-column Lookup  --  1st Lookup (matches 1st Column), 2nd Lookup (Matches 1st Row Values)
      if ($this->debug) {
         $innerHTML .= "Creating table named - 'matrix_$formname'<br>";
      }
      // BEGIN - text2table functions and data
      if ($this->debug) {
         $innerHTML .= "Form variables = " . print_r($formatted->values,1) . "<br>";
      }
      $toggleText = " style=\"display: none\"";
      $innerHTML .= "<a class='mH' id='$formname t2tbutton' ";
      $innerHTML .= "onclick=\"toggleMenu('$formname t2t')\" title='Click to Expand/Hide'>click to Show Text 2 Table </a>";
      $innerHTML .= "<div id='$formname t2t' class='mProp' $toggleText>";
      $this->formatMatrix();
      //error_log("Matrix: " . print_r($this->matrix_formatted,1));
      //error_log("Matrix Rowcol: " . print_r($this->matrix_rowcol,1));
      $this->text2table = array2Delimited($this->matrix_rowcol, "\t", 0,'unix');
      $this->delimeter = 1;
      $innerHTML .= showTextArea('text2table', $this->text2table, 80, 8, '', 1, $disabled, -1, '');
      $innerHTML .= "<br>" . "<b>Delimeter:</b> " . $formatted->formpieces['fields']['delimiter'] . "<br>";
      $innerHTML .= showGenericButton('docreate', 'Text to Table', "document.forms[\"$formname\"].elements.callcreate.value = 1; xajax_showOperatorEditResult(xajax.getFormValues(\"$formname\"))", 1);
      $innerHTML .= "</div><hr>";
      // END - text2table section
      $innerHTML .= "<table id='matrix_$formname'>";
      $mindex = 0;
      $colwidths = $this->getColumnWidths();
      if ($this->debug) {
         $this->logDebug("Column widths = " . print_r($colwidths,1) . "<br>");
         $innerHTML .= "Column widths = " . print_r($colwidths,1) . "<br>";
         $innerHTML .= "Formatted = " . print_r($this->matrix_formatted,1) . "<br>";
      }
      $innerHTML .= "<input type=hidden name=firstrow_ro value=\"" . $this->firstrow_ro . "\">";
      for ($i = 0; $i < $this->numrows; $i++) {
         $innerHTML .= "<tr>";
         for ($j = 1; $j <= $numcols; $j++) {
            $style_str = '';
            if (isset($colwidths[$j])) {
               $cw = $colwidths[$j];
            } else {
               $cw = $this->cellsize;
            }
            switch ($this->valuetype) {
               case 0:
               //nothing to do, all columns are value
               break;

               case 1:
               // grey the first column of each row to indicate that these are your key columns
                  if ($j == 1) {
                     $style_str = "style='background-color: #BEBEBE'";
                  }
               break;

               case 2:
               // grey the first column of each row to indicate that these are your key columns
                  if ( ($j == 1) or ($i == 0)) {
                     $style_str = "style='background-color: #BEBEBE'";
                  }
               break;
            }
            if ( ($i == 0) and $this->firstrow_ro) {
               $innerHTML .= "<td><input type=hidden name=matrix[] value=\"" . $this->matrix[$mindex] . "\">";
               $innerHTML .= "<b>" . $this->matrix[$mindex] . "</b></td>";
            } else {
               $innerHTML .= "<td><input type=text $style_str SIZE=" . $cw . " name=matrix[] value=\"" . $this->matrix[$mindex] . "\"></td>";
            }
            $mindex++;
         }
         $innerHTML .= "</tr>";
      }
      $innerHTML .= "</table>";
      if (!$this->fixed_cols) {
         $innerHTML .= "<input type=\"button\" onclick=\"addColumn('$formname','matrix_$formname','<input type=text SIZE=" . $this->cellsize . " name=matrix[]>')\" value=\"Add column\">";
      }
      if (!$this->fixed_rows) {
         $innerHTML .= "<input type=\"button\" onclick=\"addRow('$formname','matrix_$formname','<input type=text SIZE=" . $this->cellsize . " name=matrix[]>'); incrementFormField('$formname', 'numrows', 1) ; \" value=\"Add row\">";
      }
      return $innerHTML;
   }
   
   function showFormFooter($formatted,$formname, $disabled = 0) {
      $innerHTML = '';
      return $innerHTML;
   }
   
   function getColumnWidths() {
      $mindex = 0;
      $maxwidth = array();
      $numcols = intval(count($this->matrix) / $this->numrows);
      for ($i = 0; $i < $this->numrows; $i++) {
         for ($j = 1; $j <= $numcols; $j++) {
            if (!isset($maxwidth[$j])) {
               $maxwidth[$j] = $this->cellsize;
            }
            $cw = strlen($this->matrix[$mindex]);
            if ($cw > $maxwidth[$j]) {
               $maxwidth[$j] = $cw;
            }
            $mindex++;
         }
      }
      return $maxwidth;
   }
   
  function init() {
    parent::init();
    $this->formatMatrix();
    $this->recordInvars();
    // valuetype 0, aka "normal" is crazy.  need to assume it is returning an array which needs to be handled
    // valuetype 3 = 'csv', which seems like text or array to me, so make it bigger text than type 0
    if (in_array($this->valuetype, array( 0,3 ))) {
      if (empty($this->value_dbcolumntype) or ($this->value_dbcolumntype == 'auto')) {
        $this->value_dbcolumntype = ($this->valuetype == 3) ? 'varchar(255)' : 'varchar(32)';
      }
    }
  }
   
   function create() {
      parent::create();
      // check to see if the text2table field has anything in it
      // if so, parse the text appropriately
      if (strlen($this->text2table) > 0) {
         //if ($this->debug) {
            $this->logDebug("Calling tableFromText($this->text2table, $this->delimiter)");
         //}
         $this->tableFromText($this->text2table, $this->delimiter);
      }
   }
   
   function tableFromText($text, $delimiter=0) {
      // split by line-breaks
      // split by column-delimiter
      $lines = explode("\n",$text);
      $tdel = $this->translateDelim($delimiter);
      if ($this->debug) {
         $this->logDebug("Delimiter = $tdel ($delimiter)");
      }
      $this->matrix = array();
      if (count($lines) > 0) {
         $this->numrows = count($lines);
         if ($this->debug) {
            $this->logDebug("Function tableFromText() found $this->rows rows");
         }
         $i = 0;
         foreach ($lines as $thisline) {
            $args = explode($tdel, $thisline);
            if ($i == 0) {
               $this->numcols = count($args);
               if ($this->debug) {
                  $this->logDebug("Function tableFromText() found $this->cols columns ");
               }
            }
            foreach ($args as $thisarg) {
               $this->matrix[] = $thisarg;
            }
            $i++;
         }
      }      
   }
   
   function recordInvars() {
      // sets up the list of inputs and variable references used in this, so that we can be ordered properly

      $mindex = 0;
      $numcols = intval(count($this->matrix) / $this->numrows);
      $skeys = array_keys($this->arData);
      for ($i = 0; $i < $this->numrows; $i++) {
         for ($j = 0; $j < $numcols; $j++) {
            $thisvar = $this->matrix[$mindex];
            if (in_array($thisvar, $skeys)) {
               if ($this->debug) {
                  $this->logDebug("Checking $thisname = $thisval in lookup table<br>");
               }
               # stash in var table to help with processor hierarchy
               array_push($this->vars, $thisvar);
            }
            $mindex++;
         }
      }
      // now also add the keys that we are using to evaluate this object if it is a lookup
      switch ($this->valuetype) {
         case 1:
            array_push($this->vars, $this->keycol1);
         break;
         
         case 2:
            array_push($this->vars, $this->keycol1);
            array_push($this->vars, $this->keycol2);
         break;
      }
   }
      
  function setDataColumnTypes() {
    parent::setDataColumnTypes();
    // this sets the default return type for logging.
    // If the user has set a non-empty value, we return it here
    if (!empty($this->value_dbcolumntype) and !($this->value_dbcolumntype == 'auto')) {
      $this->parentobject->setSingleDataColumnType($this->name, $this->value_dbcolumntype, $this->defaultval);
    }
  }
  
   function search_state($thisvar, $use_default = FALSE) {
    if (!is_array($this->arData)) {
       $this->arData = array();
    }
    $skeys = array_keys($this->arData);
    if (in_array($thisvar, $skeys)) {
      $thisval = $this->arData[$thisvar];
    } else {
      // slight behavior change from before which returned the variable itself
      // was: $thisval = $thisvar; 
      // which essentially made the fallback handling type string, which supported 
      // strings that existed in the database without proper formatting, but could
      // break simulations so we will $use_default = FALSE for now to retain backward compat 
      if ($use_default) {
        $thisval = $this->defaultval; 
      } else {
        $thisval = $thisvar;
      }
    }
    return $thisval;
  }
   
   function evalMatrixVar($thisvar) {
      // this checks to see if a value is a variable reference, string, or a number
      switch($this->eval_type) {
        case 'numeric':
          $thisval = floatval($thisvar);
          break;
        case 'string':
          $thisval = trim($thisvar,"'\":");
          break;
        case 'reference':
          $thisval = $this->search_state($thisvar, TRUE);
          break;
        case 'auto':
        default:
          if (is_numeric($thisvar)) {
            $thisval = $thisvar;
          } else {
            if(trim($thisvar,"'\":") <> $thisvar) {
              // this is a string variable, as indicated by ' or "
              $thisval = trim($thisvar,"'\":");
            } else {
              $thisval = $this->search_state($thisvar);
            }
          }
        break;
      }
      //$this->logDebug("Checking Lookup Key: $thisvar , value: $thisval ");
      //error_log("Checking Lookup Key: $thisvar , value: $thisval ");
      //error_log("In State Vars: " . print_r($this->arData,1));
      return $thisval;
   }
   
   function step() {
      parent::step();
      $this->formatMatrix();
   }
   
   function formatMatrix($force_refresh=0) {
      $refreshed = 0;
      //error_log("formatMatrix on $this->name $this->lasteval_ts = " . $this->timer->timestamp);
      if (isset($this->timer)) {
         if (is_object($this->timer)) {
            if ( ($this->lasteval_ts === $this->timer->timestamp) and !($this->lasteval_ts === NULL) ) {
               $refreshed = 1;
            }
            
         }
      }
      if (!$refreshed or $force_refresh) {
      //error_log("Refreshing $this->name $this->lasteval_ts = " . $this->timer->timestamp);
         // need to put our matrix into a formatted matrix
         // start by storing in a strict row-column format, if we are doing lookups, we will use this as the basis
         // for the latter re-formatting
         $matrix_formatted = array();
         $matrix_rowcol = array();
         $mindex = 0;
         $numcols = intval(count($this->matrix) / $this->numrows);
         if ($this->debug) {
            $this->logDebug("Formatting matrix, with " . count($this->matrix) . " cells, and $this->numrows rows ($numcols columns). <br>\n");
         }
         for ($i = 0; $i < $this->numrows; $i++) {
           $matrix_rowcol[$i] = array();
            for ($j = 0; $j < $numcols; $j++) {
               // old - did not consider if a value was a variable or not
               //$matrix_rowcol[$i][$j] = $this->matrix[$mindex];
               $matrix_rowcol[$i][$j] = $this->evalMatrixVar(trim($this->matrix[$mindex]));
               $mindex++;
            }
         }
         if ($this->debug) {
            $this->logDebug("Row-Col Matrix Assembled: " . print_r($matrix_rowcol,2) . " <br>\n");
         }
         switch ($this->valuetype) {
            case 0:
               // keep in row-col format, so do no further transformation
               $matrix_formatted = $matrix_rowcol;
            break;
            
            case 1:
               // put it in a single dimensional key-value relationship, with the first column being the keys
               for ($i = 0; $i < $this->numrows; $i++) {
                  $key = $matrix_rowcol[$i][0];
                  
                  if ($numcols == 1) {
                     $values = $this->defaultval;
                  } else {
                     $values = array();
                     for ($j = 1; $j < $numcols; $j++) {
                        $values[$j-1] = $matrix_rowcol[$i][$j];
                     }
                     // make it scalar if there is only one entry
                     if (count($values) == 1) {
                        $values = $values[0];
                     }
                  }
                  $matrix_formatted[$key] = $values;
               }
            break;
            
            case 2:
               // put it in a multi-dimensional key-value relationship, with the first column being the keys
               // the first column first row, however, will be a throw-away, 
               for ($i = 1; $i < $this->numrows; $i++) {
               //for ($i = 1; $i < count($matrix_rowcol); $i++) {
                  $key = $matrix_rowcol[$i][0];
                  
                  if ($numcols == 1) {
                     $values = $this->defaultval;
                  } else {
                     $values = array();
                     for ($j = 1; $j < $numcols; $j++) {
                        // set the key to be the first (0th) row entry for this column, the value to be the 
                        // value of this column in the current row
                        $values[$matrix_rowcol[0][$j]] = $matrix_rowcol[$i][$j];
                     }
                  }
                  $matrix_formatted[$key] = $values;
               }
               $this->colnames = array_keys($matrix_formatted[$key]);
               $this->rownames = array_keys($matrix_formatted);
            break;
            
            case 3:
               // csv format
               // put it in a multi-dimensional key-value relationship, with the first row values as keys
               for ($i = 1; $i < count($matrix_rowcol); $i++) {
                  $key = $matrix_rowcol[$i][0];
                  
                  if ($numcols == 1) {
                     $values = $this->defaultval;
                  } else {
                     $values = array();
                     for ($j = 0; $j < $numcols; $j++) {
                        // set the key to be the first (0th) row entry for this column, the value to be the 
                        // value of this column in the current row
                        $values[$matrix_rowcol[0][$j]] = $matrix_rowcol[$i][$j];
                     }
                  }
                  $matrix_formatted[] = $values;
               }
            break;
         }
         if (is_object($this->timer)) {
           $this->lasteval_ts = $this->timer->timestamp;
         }
         $this->matrix_formatted = $matrix_formatted;
         $this->matrix_rowcol = $matrix_rowcol;
      }
   }
   
   function evaluate() {
      
      $key1 = isset($this->arData[$this->keycol1]) ? $this->arData[$this->keycol1] : '';
      $key2 = isset($this->arData[$this->keycol2]) ? $this->arData[$this->keycol2] : '';
      if ($this->debug) {
         if (isset($this->arData['the_geom'])) {
            $geomcache = $this->arData['the_geom'];
            $this->arData['the_geom'] = 'Trunctated for debugging';
         }
         $this->logDebug("Matrix Parent Values: " . print_r($this->arData,1) . "<br>");
         if (isset($this->arData['the_geom'])) {
            $this->arData['the_geom'] = $geomcache;
         }
      }
      // make sure that all variable values are set
      $this->formatMatrix();
      $this->result = $this->evaluateMatrix($key1, $key2);
      // if auto-set is requested, create a value on the parent for each column of selected row (as 1-D)
      if ($this->autosetvars) {
         foreach ($this->autovars as $cname) {
            $cvalue = $this->evaluateMatrix($key1, $cname);
            $this->setStateVar($cname, $cvalue);
            //error_log("$this->name Auto-Setting $cname ($key1) = $cvalue ");
         }
      }
   }
   
   function checkSumCols() {
      if (is_array($this->matrix_formatted)) {
         $sums = array();
         foreach ($this->matrix_formatted as $thisrow) {
            $sums = array_mesh($thisrow, $sums);
         }
      }
      return $sums;
   }
            
   
   function evaluateMatrix($key1 = '', $key2 = '') {
      // this routine assumes that the formatMatrix() routine has been called since any updates to state variables
      // has occured.  This happens automatically when the step() method is called, as well as the evaluate() method,
      // so it will occur during any reasonable invocation.  Other methods that call this dynamically, need to make sure 
      // that step() or evaluate(), or formatMatrix() has been called before calling evaluateMatrix()
      //error_log("evaluateMatrix() called on $this->name  with keys: '$key1' and '$key2' <br>");
      if ($this->debug) {
         error_log("evaluateMatrix() called on $this->name, lookup-type = $this->lutype1, value-type = $this->valuetype with keys: '$key1' and '$key2' <br>");
         $this->logDebug("evaluateMatrix() called on $this->name with keys: '$key1' and '$key2' <br>");
      }
      // need to see if this is a normal array, 1 or 2 column lookup
      switch ($this->valuetype) {
         case 0:
         // normal array, just return it
            $luval = $this->matrix_formatted;
         break;
         
         case 1:
         // 1-column lookup
            $luval = arrayLookup($this->matrix_formatted, $key1, $this->lutype1, $this->defaultval, $this->debug);
            if ($this->debug) {
               error_log("Matrix = " . print_r($this->matrix_formatted,1) . "<br>");
               error_log("Key = " . $key1 . " - Value: $luval<br>");
               $this->logDebug("Matrix = " . print_r($this->matrix_formatted,1) . "<br>");
               $this->logDebug("Key = " . $key1 . " - Value: $luval<br>");
            }
         break;
         
         case 2:
            // this will interpolate in both directions
            // first get the matching row array, or interpolated row array that fits the row key
            if ($this->debug) {
               //$this->logDebug("Final Matrix = " . print_r($this->matrix_formatted,1) . "<br>");
            }
            $rowvals = arrayLookup($this->matrix_formatted, $key1, $this->lutype1, $this->defaultval, $this->debug);
            if ($this->debug) {
               $this->logDebug("Rowvals Matrix = " . print_r($rowvals,1) . "<br>");
            }
            // now perform the column lookup in the selected/interpolated row
            $luval = arrayLookup($rowvals, $key2, $this->lutype2, $this->defaultval, $this->debug);
            if ($this->debug) {
               $this->logDebug("Final Matrix = " . print_r($this->matrix_formatted,1) . "<br>");
               //error_log("Final Matrix = " . print_r($this->matrix_formatted,1) . "<br>");
               error_log("Rowvals Matrix = " . print_r($rowvals,1) . "<br>");
               $this->logDebug("Key 1 = $key1, Key 2 = $key2, LUType = $this->lutype2 - Value: $luval<br>");
               error_log("Key 1 = $key1, Key 2 = $key2, LUType = $this->lutype2 - Value: $luval<br>");
            }
         break;
      }
      
      return $luval;

   }
   
   function printFormat() {
      $this->formatMatrix();
      // formats the matrix data for printing in a listobject 
      // this also makes it suitable for import to a new SQL table since it is an associate array using:
      // array2Table($values, $tablename, $colnames, $valuetypes, $forcelower, $buffer, $isPerm);
      switch ($this->valuetype) {
         case 0:
            $printformatted = $this->matrix_rowcol;
         break;

         case 1:
            $printformatted = $this->matrix_rowcol;
         break;

         case 2:
            $printformatted = array();
            $keys = array_values($this->matrix_rowcol[0]);
            $cols = array_keys($this->matrix_rowcol[0]);
            for ($u = 1; $u < count($this->matrix_rowcol); $u++) {
               foreach ($keys as $col => $key) {
                  $printformatted[$u][$key] = $this->matrix_rowcol[$u][$col];
               }
            }
         break;
      }
      
      return $printformatted;
   }
   
   function showHTMLInfo() {
      #$this->init();
      $HTMLInfo = parent::showHTMLInfo();
      $matrixprint = $this->printFormat();
      //$matrixprint = $this->matrix_rowcol;
      $this->listobject->queryrecords = $matrixprint;
      //$this->listobject->showlabels = 0;
      $this->listobject->adminsetup = 'raw';
      $this->listobject->tablename = '';
      // make it silent, outputted to listobject->outstring
      $this->listobject->show = 0;
      $this->listobject->showList();
      $HTMLInfo .= $this->name . "Matrix values:<br>" . $this->listobject->outstring . "<br>";
      //$HTMLInfo .= print_r($matrixprint,1) . "<br>";
      return $HTMLInfo;
   }
   
   function appendToMatrix($thisarray = array(), $sort=0) {
      $orig = $this->formatMatrix();
   
   }
   
   function deleteFromMatrix($key1, $key2='') {
   
   
   }
   
  function setProp($propname, $propvalue, $view = '') {
   
    if ( ($propname == 'matrix') ) {
      // handle calls to set the matrix on this object
      // Default behavior is to expect this to be an array that is 1-d, and the object uses numcols to decode it
      //$this->matrix = array('storage','stage','surface_area',0,0,0);
      // check for a valid json object, transform to array
      switch ($view) {
        case 'json-1d':
          $raw_json = $propvalue;
          $propvalue = json_decode($propvalue, TRUE);
          if (is_array($propvalue)) {
            //error_log("Array located, handling " . print_r($propvalue,1));
            $this->matrix = $propvalue;
          } else {
            error_log("JSON decode failed wih $propvalue for $raw_json");
          }
        break;

        default:
          parent::setProp($propname, $propvalue, $view);
        break;
      }
    } else {
      parent::setProp($propname, $propvalue, $view);
    }
  }
  
  
  function setClassProp($propname, $propvalue, $view = '') { 
    switch ($view) {
      case 'array':
        switch ($propname) {
          case 'matrix':
            $this->assocArrayToMatrix($propvalue, FALSE);
          break;
          default:
            parent::setClassProp($propname, $propvalue, $view);
          break;
        }
      break;
      
      default:
        parent::setClassProp($propname, $propvalue, $view);
      break;
    }
  }
   
  function twoDimArrayToMatrix($thisarray = array()) {
    // sets this objects matric to a flattened version of the input matrix
    $this->matrix = array();
    if (count($thisarray) > 0) {
      $this->matrix = $propvalue;
      foreach($thisarray as $thisline) {
        foreach ($thisline as $key => $value) {
          $this->matrix[] = $value;
        }
      }
    }
  }
   
   function assocArrayToMatrix($thisarray = array(), $header = TRUE) {
      // sets this objects matric to the input matrix
      $this->matrix = array();
      if (count($thisarray) > 0) {
         if (count($thisarray[0]) > 0) {
            $this->numcols = count($thisarray[0]);
            // add a row for the header line
            $this->numrows = count($thisarray);
            if ($header) {
              $this->numrows++;
              // since these are stored as a single dimensioned array, regardless of their lookup type 
              // (for compatibility with single dimensional HTML form variables)
              // we set alternating values representing the 2 columns (luname - acreage)
              foreach (array_keys($thisarray[0]) as $colname) {
                 $this->matrix[] = $colname;
              }
            }
            foreach($thisarray as $thisline) {
               foreach ($thisline as $key => $value) {
                  $this->matrix[] = $value;
               }
            }
         }
      }
   }
   
   function oneDimArrayToMatrix($thisarray = array()) {
      // sets this objects matric to the input matrix
      $this->matrix = array();
      if (count($thisarray) > 0) {
         $this->numcols = 2;
         // add a row for the header line
         $this->numrows = count($thisarray);
         // since these are stored as a single dimensioned array, regardless of their lookup type 
         // (for compatibility with single dimensional HTML form variables)
         // we set alternating values representing the 2 columns (luname - acreage)
         foreach ($thisarray as $key=>$value) {
            $this->matrix[] = $key;
            $this->matrix[] = $value;
         }
      }
   }

   function getProp($propname, $view = '') {
      //error_log("DataMatrix Property requested: $propname, $view ");
      $localviews = array('matrix', 'matrix_formatted', 'csv');
      if (!in_array($view, $localviews)) {
         return parent::getProp($propname, $view);
      } else {
         switch ($view) {
            case 'matrix':
               $this->formatMatrix();
               //error_log("Returning: " . print_r($this->matrix_formatted,1));
               return $this->matrix_formatted;
            break;
            case 'matrix_formatted':
               $this->formatMatrix();
               //error_log("Returning: " . print_r($this->matrix_formatted,1));
               return $this->matrix_formatted;
            break;
				case 'csv':
               $this->formatMatrix();
               //error_log("calling showCWSInfoView () ");
               //return array2Delimited($this->matrix_rowcol, ',', 1,'unix');
               return array2Delimited($this->matrix_formatted, ',', 1,'unix');
				break;
         }
      }
   }
   
   function showElementInfo($propname = '', $view = 'info', $params = array()) {
      $localviews = array('csv','tsv');
      $output = '';
      //error_log("$this->name showElementInfo called with view = '$view' and propname '$propname' ");
      if (trim($propname) == '') {
         if (in_array(trim($view), $localviews)) {
            switch (trim($view)) {
               case 'csv':
               //error_log("calling formatMatrix () ");
               $this->formatMatrix();
               //error_log("calling showCWSInfoView () ");
               $output .= array2Delimited($this->matrix_formatted, ',', 1,'unix');
               //$output .= $this->showFormBody(array(),'');
               break;
               
               case 'tsv':
               //error_log("calling formatMatrix () ");
               $this->formatMatrix();
               //error_log("calling array2Delimited () tab separated");
               $output .= array2Delimited($this->matrix_formatted, "\t", 1,'unix');
               //$output .= $this->showFormBody(array(),'');
               break;

            }
         } else {
            $output .= parent::showElementInfo($propname, $view, $params);
         }
      }
      return $output;
   }
   
}

class matrixAccessor extends modelSubObject {
   var $targetmatrix;
   var $keycol1;
   var $keycol2;
   var $coltype1;
   var $coltype2;
   
   function init() {
      parent::init();
      if ($this->coltype1 == 1) {
         $key1_calc = new Equation;
         $key1_calc->equation = $this->keycol1;
         $key1_calc->debug = $this->debug;
         $this->addOperator('key1_calc', $key1_calc, 0);
      } else {
         $this->state['key1_calc'] = $this->keycol1;
      }
      if ($this->coltype2 == 1) {
         $key2_calc = new Equation;
         $key2_calc->equation = $this->keycol2;
         $key2_calc->debug = $this->debug;
         $this->addOperator('key2_calc', $key2_calc, 0);
      } else {
         $this->state['key2_calc'] = $this->keycol2;
      }
   }
   
   function evaluate() {
      
      // flowby has already been set
      $key1 = $this->state['key1_calc'];
      $key2 = $this->state['key2_calc'];
      
      // each evaluation field should be an equation
      if (isset($this->parentobject->processors[$this->targetmatrix])) {
         if ($this->debug) {
            $this->logDebug("Evaluating Matrix Lookup on matrix $this->targetmatrix with $key1 and $key2 <br>");
         }
         $this->result = $this->parentobject->processors[$this->targetmatrix]->evaluateMatrix($key1, $key2);
         if ($this->debug) {
            $this->logDebug(" Matrix Evaluation = $this->result <br>");
         }
      } else {      
         if ($this->debug) {
            $this->logDebug("<b>Error: </b> $this->targetmatrix not set on parent <br>");
         }
         $this->result = $this->nullvalue;
      }
   }

   function showEditForm($formname, $disabled=0) {
      if (is_object($this->listobject)) {

         $returnInfo = array();
         $returnInfo['name'] = $this->name;
         $returnInfo['description'] = $this->description;
         $returnInfo['debug'] = '';
         $returnInfo['elemtype'] = get_class($this);
         # set up div to contain each seperate multi-parameter block
         $aset = $this->listobject->adminsetuparray['matrixAccessor'];
         foreach (array_keys($aset['column info']) as $tc) {
            $props[$tc] = $this->getProp($tc);
         }
         // get form variables in formatted HTML input fields
         $formatted = showFormVars($this->listobject,$props,$aset,0, 1, 0, 0, 1, 0, -1, NULL, 1);

         //$props = (array)$this;
         $innerHTML = '';
         $innerHTML .= $this->showFormHeader($formatted, $formname, $disabled);
         $innerHTML .= "<hr>";
         $innerHTML .= $this->showFormBody($formatted, $formname, $disabled);
         $innerHTML .= $this->showFormFooter($formatted, $formname, $disabled);
         
         
         
         // show the formatted matrix
         //$this->formatMatrix();
         //$innerHTML .= print_r($this->matrix_formatted,1) . "<br>";

         $returnInfo['innerHTML'] = $innerHTML;

         return $returnInfo;

      }
   }
   
   function showFormHeader($formatted, $formname, $disabled = 0) {
      $innerHTML = '';
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . " | ";
      $innerHTML .= "<b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'] . " | ";
      $innerHTML .= "<b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'] . "<BR>";
      $innerHTML .= "<b>Default Value:</b> " . $formatted->formpieces['fields']['defaultval'];
      return $innerHTML;
   }
   
   function showFormBody($formatted, $formname, $disabled = 0) {
      $innerHTML = '';
      $innerHTML .= " <b>Target Matrix:</b> " . $formatted->formpieces['fields']['targetmatrix'] . "<br>";
      $innerHTML .= " <b>Row Expression:</b> " . $formatted->formpieces['fields']['keycol1'];
      $innerHTML .= " <b>Type:</b>" . $formatted->formpieces['fields']['coltype1'] . "<br>" ;
      $innerHTML .= " <b>Col Expression:</b> " . $formatted->formpieces['fields']['keycol2'];
      $innerHTML .= " <b>Type:</b>" . $formatted->formpieces['fields']['coltype2'];
      return $innerHTML;
   }
   
   function showFormFooter($formatted,$formname, $disabled = 0) {
      $innerHTML = '';
      return $innerHTML;
   }
   
}

class lookupObject extends modelSubObject {
   var $table = array();
   var $defval = 0.0;
   var $lutype = 1; # 0 - exact match; 1 - interpolate values; 2 - stair step
   var $valtype = 0; # 0 - numeric; 1 - alphanumeric; 2 - variable reference
   var $state = array();
   var $luval = 0.0;
   var $input = '';
   var $vars = array();
   var $filepath = ''; # optional, derives lookup table from a CSV file
   var $component_type = 3;
   var $lucsv = ''; # comma seperated value lookup list
   var $arData = array(); # subs for state array in other objects (parent object)
   var $dateranges = array();
   # date variables, if they are set, it sets them in the date array on init()
   # so, this way, the functions can be instantiated two ways,
   #   the old way "setUp()", and
   #   the new way, set the properties manually, and then call init()
   var $startyear = '';
   var $endyear = '';
   var $startmonth = '';
   var $endmonth = '';
   var $startday = '';
   var $endday = '';
   var $startweekday = '';
   var $endweekday = '';
   var $starthour = '';
   var $endhour = '';
   # place to stash lookup value variable reference names
   var $luVars = array();
   # an associative array that can contain any of the following keys, with a valid value:
   # startyear, endyear, startmonth, endmonth, startday, endday, startweekday, endweekday, starthour, endhour
   var $datekeypairs = array(
      'year' => array('lo'=>'startyear', 'hi'=>'endyear', 'format'=>'Y', 'minkey'=>'', 'maxkey'=>''),
      'month' => array('lo'=>'startmonth', 'hi'=>'endmonth', 'format'=>'n', 'minkey'=>1, 'maxkey'=>12),
      'day' => array('lo'=>'startday', 'hi'=>'endday', 'format'=>'j', 'minkey'=>1, 'maxkey'=>31),
      'weekday' => array('lo'=>'startweekday', 'hi'=>'endweekday', 'format'=>'N', 'minkey'=>1, 'maxkey'=>7),
      'hour' => array('lo'=>'starthour', 'hi'=>'endhour', 'format'=>'G', 'minkey'=>0, 'maxkey'=>23)
   );
   # weekday constraints are 1-7, corresponding to Monday-Sunday
   var $loggable = 1; // can log the value in a data table

   function init() {
      parent::init();
      if ($this->debug) {
         $this->logDebug("Scanning $this->lucsv for valid entries<br>");
      }
      $this->dateranges = array();
      # check for properties, which would override the datevalues
      foreach (array('startyear', 'endyear', 'startmonth', 'endmonth', 'startday', 'endday', 'startweekday', 'endweekday', 'starthour','endhour') as $thisprop) {
         if (strlen(ltrim(rtrim($this->$thisprop))) > 0) {
            $this->dateranges[$thisprop] = $this->$thisprop;
         }
      }
      #$this->logDebug("date ranges array = " . $this->logDebug($this->dateranges,1) . "<br>");

      if ($this->debug) {
         $this->logDebug("Scanning $this->lucsv for valid entries<br>");
      }

      # check for a file, if set, use it to populate the lookup table, otherwise, use the CSV string
      if (strlen($this->filepath) > 0) {
         if (file_exists($this->filepath)) {
            $flowwua = readDelimitedFile($this->filepath);
            $wua = array();
            foreach($flowwua as $thiswua) {
               $key = $thiswua[0];
               $value = $thiswua[1];
               $this->table[ltrim(rtrim("$key"))] = ltrim(rtrim($value));
            }
         }
      } else {
         $this->parseLUTable();
      }

      # set the input variable to be in the vars array so that this can be evaluated in the processor hierarchy
      $this->vars = array($this->input);

      # now, check for variable references in the lookup value fields for names in the arData array,
      # if there exists name references, then stash them
      $arNames = array_keys($this->arData);
      $this->luVars = array();
      foreach ($this->table as $thisname => $thisval) {
         if (in_array($thisval, $arNames)) {
            if ($this->debug) {
               $this->logDebug("Checking $thisname = $thisval in lookup table<br>");
            }
            $this->luVars[$thisname] = $thisval;
            # stash in var table to help with processor hierarchy
            array_push($this->vars, $thisval);
         }
      }

   }
   
   function parseLUTable() {
      if (strlen($this->lucsv) > 0) {
         # we have a csv of the lookup, over-ride other values
         $pairs = explode(",", $this->lucsv);
         foreach ($pairs as $thispair) {
            list($key, $value) = explode(':', $thispair);
            $this->table[ltrim(rtrim("$key"))] = ltrim(rtrim($value));
            if ($this->debug) {
               $this->logDebug("Adding $key = $value to lookup table from csv<br>");
            }
         }
      }
   }

   function setState() {
      parent::setState();
      $this->datekeypairs = array(
            'year' => array('lo'=>'startyear', 'hi'=>'endyear', 'format'=>'Y', 'minkey'=>'', 'maxkey'=>''),
            'month' => array('lo'=>'startmonth', 'hi'=>'endmonth', 'format'=>'n', 'minkey'=>1, 'maxkey'=>12),
            'day' => array('lo'=>'startday', 'hi'=>'endday', 'format'=>'j', 'minkey'=>1, 'maxkey'=>31),
            'weekday' => array('lo'=>'startweekday', 'hi'=>'endweekday', 'format'=>'N', 'minkey'=>1, 'maxkey'=>7),
            'hour' => array('lo'=>'starthour', 'hi'=>'endhour', 'format'=>'G', 'minkey'=>0, 'maxkey'=>23)
      );
   }


   # deprecated function, should no longer be called
   function setUp($input, $lutype, $table, $defval, $nullval=0.0, $dateranges=array()) {
      $this->table = $table;
      $this->defval = $defval;
      $this->state[$input] = $defval;
      $this->input = $input;
      $this->lutype = $lutype;
      $this->nullvalue = $nullval;
      if (count($dateranges) > 0) {
         $this->dateranges = $dateranges;
      }
      array_push($this->vars, $input);
   }

   function step() {
      parent::step();

      if ($this->debug) {
         $this->logDebug("Checking for variable references in lookup value fields.<br>\n");
         $this->logDebug(print_r($this->luVars, 1));
      }
      foreach ($this->luVars as $thiskey => $thisvar) {
         $this->table[$thiskey] = $this->arData[$thisvar];
         if ($this->debug) {
            $this->logDebug("Evaluating lookup object.<br>\n");
         }
      }

   }

   function evaluate() {
      #$this->step();
      if ($this->debug) {
         $this->logDebug("Evaluating lookup object.<br>\n");
      }
      $thistab = $this->table;
      if (is_string($this->defval) and in_array($this->defval,array_keys($this->arData))) {
         # use a variable reference for default value
         $defval = $arData[$this->defval];
         if ($this->debug) {
            $this->logDebug("Setting default value to variable reference $this->defval = $defval.<br>\n");
         }
      } else {
         $defval = $this->defval;
         if ($this->debug) {
            $this->logDebug("Setting default value to static default value = $defval.<br>\n");
         }
      }
      $lutype = $this->lutype;
      $dateranges = $this->dateranges;
      $curval = $this->arData[$this->input];
      $luval = '';
      # evaluate date validity
      if ($this->debug) {
         $this->logDebug("Checking Date Validity.<br>\n");
      }
      $disabled = 0; # assume it is not disabled, check date
      if (count(array_keys($dateranges)) > 0) {
         if ($this->debug) {
            $drc = count($dateranges);
            $this->logDebug("$drc Date constraints submitted.<br>\n");
            $this->logDebug($dateranges);
            $this->logDebug(array_keys($dateranges));
         }
         # set the max for day of month wrap-around to the max day in this month
         $this->datekeypairs['day']['maxkey'] = $this->timer->thistime->format('t');
         # check the keys
         $inkeys = array_keys($dateranges);
         foreach ($this->datekeypairs as $thispair) {
            $lokey = $thispair['lo'];
            $hikey = $thispair['hi'];
            if (in_array($lokey, $inkeys) or in_array($hikey, $inkeys)) {
               $fstr = $thispair['format'];
               $minkey = $thispair['minkey'];
               $maxkey = $thispair['maxkey'];
               $dateval = $this->timer->thistime->format($fstr);
               if (!in_array($lokey, $inkeys)) {
                  $loval = $dateval;
               } else {
                  $loval = $dateranges[$lokey];
               }
               if ($maxkey == '') {
                  $maxkey = max(array($dateval,$loval));
               }
               if ($minkey == '') {
                  $minkey = $loval;
               }
               if (!in_array($hikey, $inkeys)) {
                  $hival = max(array($dateval,$maxkey));
               } else {
                  $hival = $dateranges[$hikey];
               }
               /*
               if ($maxkey <> '') {
                  # evaluate as wraparound
                  if ($loval > $hival) {
                     $hival += $maxkey - $lokey;
                     $dateval += $maxkey - $lokey;
                  }
               }
               */
               # calculated distance of bounded range
               if (($hival - $loval) <> 0) {
                  $f = ( ($maxkey - $loval) + ($hival - $minkey + 1) ) * ( ($hival - $loval) - abs($hival - $loval)) / (2 * ($hival - $loval));
               } else {
                  $f = 0;
               }
               $b = ( ($hival - $loval) + abs($hival - $loval) ) / 2.0;
               $rangemin = $loval;
               $rangemax = $rangemin + $f + $b;
               # calculated distance from lo value to given value
               if (($dateval - $loval) <> 0) {
                  $fz = ( ($maxkey - $loval) + ($dateval - $minkey + 1) ) * ( ($dateval - $loval) - abs($dateval - $loval)) / (2 * ($dateval - $loval));
               } else {
                  $fz = 0;
               }
               $bz = ( ($dateval - $loval) + abs($dateval - $loval) ) / 2.0;
               $rangeval = $rangemin + $fz + $bz;

               if ($this->debug) {
                  $this->logDebug("Date value ($lokey - $minkey/$hikey - $maxkey): $dateval, Low Bound: $loval, High Bound: $hival <br>");
                  $this->logDebug("Range Distance $f (forward) + $b (backward) <br>");
                  $this->logDebug("Distance from date to low bound: $fz (forward) + $bz (backward) <br>");
               }

               if ( ($rangemin > $rangeval) or ($rangeval > $rangemax) ) {
                  $disabled = 1;
                  if ($this->debug) {
                     $this->logDebug(" Disabled.<br>");
                  }
               }
            } else {
               if ($this->debug) {
                  $this->logDebug("Date value ($lokey/$hikey) not specified <br>");
               }
            }
         }
      }

      if ($disabled) {
         if ($this->debug) {
            $this->logDebug("Lookup disabled due to date constraint. Value = $this->nullvalue <br>");
         }
         $luval = $this->nullvalue;
      } else {

         if ($this->debug) {
            $this->logDebug("Searching for $curval in <br>" . $this->logDebug($thistab,1));
         }
         switch ($lutype) {
            case 0:
            # exact match lookup table
            if (in_array($curval, array_keys($thistab))) {
               $luval = $thistab[$curval];
            } else {
               $luval = $defval;
            }
            if ($this->debug) {
               $this->logDebug("Exact match: $thisl: $curval, def: $defval, lookup: $luval <br>\n");
               $this->logDebug($thistab);
               $this->logDebug("<br>\n");
            }
            break;

            case 1:
            # interpolated lookup table
            $lukeys = array_keys($thistab);
            $luval = $defval;
            for ($i=0; $i < (count($lukeys) - 1); $i++) {
               $lokey = $lukeys[$i];
               $hikey = $lukeys[$i+1];
               $loval = $thistab[$lokey];
               $hival = $thistab[$hikey];
               $minkey = min(array($lokey,$hikey));
               $maxkey = max(array($lokey,$hikey));
               if ( ($minkey <= $curval) and ($maxkey >= $curval) ) {
                  $luval = $this->interpValue($curval, $lokey, $loval, $hikey, $hival);
                  if ($this->debug) {
                     $this->logDebug("Low: $lokey, Value: $curval, Hi: $hikey = $luval <br>\n");
                  }
               }
            }
            break;

            case 2:
            # stair-step lookup table
            $lukeys = array_keys($thistab);
            if ($this->debug) {
               $this->logDebug("Searching table:");
               $this->logDebug($thistab);
               $this->logDebug("<br>");
            }
            $luval = $defval;
            $lastkey = 'N/A';
            for ($i=0; $i <= (count($lukeys) - 1); $i++) {
               $lokey = $lukeys[$i];
               $loval = $thistab[$lokey];
               if ( ((float)$lokey <= $curval) ) {
                  $luval = $loval;
                  $lastkey = $lokey;
               }
            }
            if ($this->debug) {
               $this->logDebug("Stair Step Value: $curval, key: $lastkey, = $luval <br>\n");
            }
            break;

            default:
            # exact match lookup table
            if (in_array($curval, array_keys($thistab))) {
               $luval = $thistab[$curval];
            } else {
               $luval = $defval;
            }
            if ($this->debug) {
               $this->logDebug("Exact match: $thisl: $curval, def: $defval, lookup: $luval <br>\n");
               $this->logDebug($thistab);
               $this->logDebug("<br>\n");
            }
            break;

         }

         switch ($this->valtype) {
            # decides how to handle the value, if this is a variable lookup, we get the value from the variable,
            # otherwise, we return whatever the value is
            case 3:
               # variable reference
               if (in_array($luval, array_keys($this->arData))) {
                  $luval = $this->arData[$luval];
               }
            break;

            default:
            # do nothing
            break;
         }
      }
      if ($this->debug) {
         $this->logDebug("Lookup Value: $luval <br>\n");
      }

      $this->result = $luval;
   }

}

class hydroObject extends modelContainer {
   var $Qin = 0.0;
   var $Qout = 0.0;
   var $Iold = 0.0; /* last inflow */
   var $depth = 0.0;
   var $tol = 0.0001;
   var $Storage;
   var $n = 0.025; /* Mannings N - roughness coefficient */
   var $slope = 0.01; /* slope */
   var $slength = 100.0; /* slope length */
   var $area = 0;
   var $drainarea_sqmi = 0;
   var $totalflow = 0;
   var $totalinflow = 0;
   
   function wake() {
      parent::wake();
      $this->prop_desc['area'] = 'Local drainage area, capable of producing runoff into this object from Qafps inputs (sqmi).';
   }

   function setState() {
      parent::setState();
      $this->state['Qin'] = 0.0;
      $this->state['Qout'] = 0.0;
      $this->state['Iold'] = 0.0;
      $this->state['Vout'] = 0.0;
      $this->state['depth'] = 0.0;
      $this->state['Storage'] = 0.0;
      $this->state['demand'] = 0.0;
      $this->state['area'] = $this->area;
      $this->state['slope'] = $this->slope;
      $this->state['slength'] = $this->slength;
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      
      $statenums = array('Qin','Qout','Iold','Vout','depth','Storage','demand','area','slope','slength');
      foreach ($statenums as $thiscol) {
         if (isset( $this->$thiscol)) {
            $this->setSingleDataColumnType($thiscol, 'float8', $this->$thiscol);
         } else {
            $this->setSingleDataColumnType($thiscol, 'float8', 0.0);
         }
      }
   }

   function preStep() {
      # Iold is used by storage routing routines, so stash a copy before getting inputs
      $this->Iold = $this->state['Qin'];
      $this->state['Qin'] = 0.0;
      $this->state['Iold'] = $this->Iold;

      # now proceed with the gathering of inputs
      parent::preStep();
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'Storage');
      array_push($publix, 'depth');
      array_push($publix, 'maxcapacity');
      array_push($publix, 'Qout');
      array_push($publix, 'Qin');
      array_push($publix, 'area');
      array_push($publix, 'unusable_storage');

      return $publix;
   }
}

class hydroContainer extends modelContainer {
   var $Qin = 0.0;
   var $Qout = 0.0;
   var $Iold = 0.0; /* last inflow */
   var $depth = 0.0;
   var $tol = 0.0001;
   var $Storage;
   var $n = 0.025; /* Mannings N - roughness coefficient */
   var $slope = 0.01; /* slope */
   var $slength = 100.0; /* slope length */
   var $area = 0;
   var $totalflow = 0;
   var $totalinflow = 0;

   function setState() {
      parent::setState();
      $this->state['Uin'] = 0.0;
      $this->state['Uout'] = 0.0;
      $this->state['Qin'] = 0.0;
      $this->state['Qout'] = 0.0;
      $this->state['Iold'] = 0.0;
      $this->state['Vout'] = 0.0;
      $this->state['depth'] = 0.0;
      $this->state['Storage'] = 0.0;
      $this->state['demand'] = 0.0;
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      
      $statenums = array('Uin','Uout','Qin','Qout','Iold','Vout','depth','Storage','demand');
      foreach ($statenums as $thiscol) {
         if (isset( $this->$thiscol)) {
            $this->setSingleDataColumnType($thiscol, 'float8', $this->$thiscol);
         } else {
            $this->setSingleDataColumnType($thiscol, 'float8', 0.0);
         }
      }
      
   }
   
   function preStep() {
      # Iold is used by storage routing routines, so stash a copy before getting inputs
      $this->Iold = $this->state['Qin'];
      $this->state['Qin'] = 0.0;
      $this->state['Iold'] = $this->Iold;

      # now proceed with the gathering of inputs
      parent::preStep();
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'Storage');
      array_push($publix, 'depth');
      array_push($publix, 'maxcapacity');
      array_push($publix, 'Qout');
      array_push($publix, 'Qin');
      array_push($publix, 'unusable_storage');

      return $publix;
   }
}

class timeSeriesInput extends modelObject {
   var $tsvalues = array();
   var $nullflag = -99999; # a data flag to tell the object that the corresponding data value
                           # is not good
   var $intflag = 0; # 0 - always interpolate, 1 - never interpolate,
                       # 2 - interpolate up to a distance of ilimit seconds
   var $intmethod = 0; # 0 - linear, 1 - previous value,
                       # 2 - next value, 3 - period mean, 4 - period min, 5 - period max, 6 - period sum
   var $ilimit = 432000.0; # default ilimit = 5 days
   var $extflag = 2; # 0 - always extrapolate, 1 - never extrapolate,
                       # 2 - extrapolate up to a distance of elimit seconds
   var $elimit = 432000.0; # default elimit = 5 days
   var $extmethod = 1; # 0 - linear, 1 - closest value
   var $datastate = 0; // 0 - within data series, 1 - pre-data series, 2 - reached end of data
   var $nullvalue = NULL; # value to use if intmethod says to flag nulls
   var $lasttimeslot = 0; # integer pointing to position in time series array
   var $lasttimesec = 0; # the time in seconds corresponding to the last position in the time series
   var $delimiter = ',';
   var $cache_ts = 0; # whether or not to store the time series array in a file at the end of a run
   var $tsfile = ''; # name of the file to store our time series in
   var $ts_enabled = 1; // can turn this off if we retieved no data for this TS
   var $filepath = '';
   var $db_cache_name = ''; # name of an auxiliary table to hold the time series if we need to save memory
   var $db_cache_persist = FALSE; # whether to keep this in the model runtime database
   var $max_memory_values = -1; # setting this to greater than zero results in cacheing
   var $cache_create_sql = ''; // place to hold the create statement for debugging purposes
   var $tscount = 0;

   function addValue($thistime, $thisvar, $thisvalue = 0) {
      $datetime = new DateTime(date('r', strtotime($thistime)));
      $tsecs = $datetime->format("U");
      $tr = $datetime->format("r");
      $td = $datetime->format("Y-m-d");
      if ($this->debug) {
         if (count($this->tsvalues) < 10) {
            $this->logDebug("Adding " . print_r($thisvar,1) . " at time $tr, $td, $tsecs <br>");
         }
      }
      if (is_array($thisvar)) {
         # assume we are being passed an associative array
         foreach ($thisvar as $key => $value) {
            $this->tsvalues[$tsecs][$key] = $value;
            # set intial values for this variable to NULL
            if (strlen($key) > 0) {
               if (!isset($this->state[$key])) {
                  $this->setStateVar($key, 'NULL');
                  // add it to loggable columns:
                  if ($this->debug) {
                     error_log("TS $this->name initalize calling setSingleDataColumnType($key, 'guess', $value, 1, 0)");
                  }
                  $this->setSingleDataColumnType($key, 'guess', $value, 1, 0);
               }
            }
         }
      } else {
         # otherwise, just put in a single pair
         $this->tsvalues[$tsecs][$thisvar] = $thisvalue;
         # set intial values for this variable to NULL
         if (strlen($thisvar) > 0) {
            if (!isset($this->state[$thisvar])) {
               $this->setStateVar($thisvar, 'NULL');
            }
         }
      }
      $this->tsvalues[$tsecs]['timestamp'] = $tsecs;
      
      if (!in_array('thisdate',$this->tsvalues[$tsecs])) {
         $this->tsvalues[$tsecs]['thisdate'] = $td;
      }
      $this->tscount++;
   }

   function setDBCacheName() {
      # set a name for the temp table that will not hose the db
      $targ = array(' ',':','-','.');
      $repl = array('_', '_', '_', '_');
      $this->tblprefix = str_replace($targ, $repl, "tmp$this->sessionid" . "_"  . str_pad(rand(1,99), 3, '0', STR_PAD_LEFT) . "_" . $this->componentid . "_");
      $this->db_cache_name = $this->tblprefix . '_cache';
      //error_log("DSN $this->name set to $this->db_cache_name ");
   }

   function init() {
      parent::init();
      $this->tsvalues = array();
      $this->lasttimesec = 0;
      $this->tscount = 0;
      if ($this->cache_ts) {
         $this->logDebug("Calling tsvaluesFromLogFile() <br>\n");
         if ($this->debug) {
            $this->logDebug("Obtaining values from file <br>\n");
         }
         $this->tsvaluesFromLogFile();
      } else {
         if ($this->debug) {
            $this->logDebug("No value file specified. <br>\n");
         }
      }
      $this->setDBCacheName();
      ksort($this->tsvalues);
      // only do this for standalone objects, not sub-components
      $mem_use = (memory_get_usage(true) / (1024.0 * 1024.0));
      $mem_use_malloc = (memory_get_usage(false) / (1024.0 * 1024.0));
      //error_log("Memory Use after init() on $this->name = $mem_use ( $mem_use_malloc )<br>\n");
         
      if ( (count($this->tsvalues) > $this->max_memory_values) and ($this->max_memory_values > 0)) {
         $this->logDebug("tsvalues cacheing enabled on $this->name - max # of recs in memory: $this->max_memory_values <br>\n");
         //error_log("tsvalues cacheing enabled on $this->name");
         $this->tsvalues2listobject();
         $this->getCurrentDataSlice();
         $mem_use = (memory_get_usage(true) / (1024.0 * 1024.0));
         $mem_use_malloc = (memory_get_usage(false) / (1024.0 * 1024.0));
         //error_log("Memory Use after caching timeseries data on $this->name = $mem_use ( $mem_use_malloc )<br>\n");
      } else {
        error_log("TimeSeriesInput $this->name not calling getCurrentDataSlice(): count(tsvalues) = " . count($this->tsvalues) . " max # of recs in memory: $this->max_memory_values <br>");
      }
      // disabled the time series search and retrieval if there is nothing to retrieve
      //if ($this->tscount == 0) {
      //   $this->ts_enabled = 0;
      //}
   }
   
   function cleanUp() {
      # remove all the temp tables associated with this object
      if (!$this->db_cache_persist and is_object($this->listobject)) {
         if ($this->listobject->tableExists($this->db_cache_name)) {
            $this->listobject->querystring = "  drop table $this->db_cache_name ";
            //error_log("Dropping db cache for $this->name ");
            $this->listobject->performQuery();
         }
      }
      parent::cleanUp();
   }
   
   function getCurrentDataSlice() {
      // need to get a certain, sensible number of values that match two criteria:
      //   1) do not exceed the max_memory_values
      //  OR
      //   2) only get enough to encompass the current dt
      // see CBPLandDataConnectionFile class for a fixed version of this that acts properly.  
      $current_time = $this->timer->thistime->format("U");
      // do this in case we are in a before the epoch time period and the default value of lasttimesec = 0
      if ($this->lasttimesec > $current_time) {
         $this->lasttimesec = $current_time;
      }
      $dt = $this->dt;
      if ($this->listobject->tableExists($this->db_cache_name)) {
      
         $this->listobject->querystring = "  select count(*) as numts, min(\"timestamp\") as mints, max(\"timestamp\") as maxts ";
         $this->listobject->querystring .= " from  \"$this->db_cache_name\"";
         $this->listobject->querystring .= " where \"timestamp\" > $this->lasttimesec and \"timestamp\" <= ($current_time + $dt) ";
         if ($this->debug) {
            $this->logDebug($this->listobject->querystring);
         }
         $this->listobject->performQuery();
         $numts = $this->listobject->getRecordValue(1,'numts');
         if ($this->debug) {
            $this->logDebug("$numts values remaining in cache\n");
         }
         // @todo: check this out.  I think it should set limit to be max_memory_values if numts > max_memory_mb
         //                         Otherwise, what is this accomplishing other than loading all ts all the time?
         if ($numts > $this->max_memory_values) {
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

   function finish() {
      if ($this->cache_ts) {
         $this->tsvalues2file();
      }
      parent::finish();
      # free up this memory
      $this->tsvalues = array();
   }

   function orderValues() {
      ksort($this->tsvalues);
   }


   function tsvaluesFromLogFile($infile='') {
     // @todo: for elements with a persistent cache OR for those with a shared cache
     //         1. check to see if the cache table exists already.
     //         2. If cache table already exists, check date on table in cache table lookup
     //           - this is something to borrow from the analysis table/session table management
     //         3. If cache date is < file modified date then do nothing and return
     //         4. Otherwise, proceed as normal     
     // check for a file, if set, use it to populate the lookup table, otherwise, use the CSV string
      if (!(strlen($infile) > 0)) {
         if (strlen($this->tsfile) == 0) {
            $this->tsfile = 'tsvalues.' . $this->componentid . '.csv';
            $filename = $this->outdir . '/' . $this->tsfile;
         } else {
            $filename = $this->tsfile;
         }
      } else {
         $filename = $infile;
      }
      if ($this->debug) {
         $this->logDebug(" tsvaluesFromLogFile($infile) called - filename = $filename <br>/n");
      }
      $fe = fopen($filename,'r');
      if ($fe) {
         fclose($fe);
         # since this is from a log file that we generated, we can assume that it has the column headers
         //$tsvalues = readDelimitedFile($filename, $this->translateDelim($this->delimiter), 1);
         $tsvalues = readDelimitedFile_fgetcsv($filename, $this->translateDelim($this->delimiter), 1);
         $lc = count($tsvalues);
         if ($this->debug) {
            $tdel = $this->translateDelim($this->delimiter);
            $this->logDebug("$lc lines found. delimiter is '$tdel'.<br>");
         }
         $k = 0;
         foreach ($tsvalues as $thisline) {
            if ($k == 0) {
               if ($this->debug) {
                  $this->logDebug("Column names:" . print_r(array_keys($thisline),1) . "<br>");
               }
            }
            if (isset($thisline['timestamp'])) {
               $ts = intval($thisline['timestamp']);
               $this->tsvalues[$ts] = $thisline;
            } else {
               if (isset($thisline['thisdate'])) {
                  $ts = strtotime($thisline['thisdate']);
                  $this->tsvalues[$ts] = $thisline;
                  $this->tsvalues[$ts]['timestamp'] = $ts;
                  if ($this->debug) {
                     if ($k == 0) {
                        $this->logDebug($thisline['thisdate'] . " converted to Timestamp $ts <br>");
                        error_log($thisline['thisdate'] . " converted to Timestamp $ts <br>");
                     }
                  }
               } else {
                  if ($this->debug) {
                     $this->logDebug("Timestamp column not found.<br>");
                  }
               }
            }
            if ($this->debug >= 2) {
               $this->logDebug("Adding line at timestamp $ts : " . print_r($thisline, 1) . "<br>");
            }
            $k++;
         }
      } else {
         $this->logDebug("File $filename Does Not exist.<br>");
      }
   }

   function tsvalues2file() {
      if (strlen($this->tsfile) == 0) {
         $this->tsfile = 'tsvalues.' . $this->componentid . '.csv';
         $filename = $this->outdir . '/' . $this->tsfile;
      } else {
         $filename = $this->tsfile;
      }
      # format for output
      $fdel = '';
      $outform = '';
      if ($this->debug) {
         $this->logDebug("Outputting Time Series to file: $this->logfile <br>");
      }
      if (count($this->tsvalues) > 0) {
         $minkey = min(array_keys($this->tsvalues));
         if ($this->debug) {
            $this->logDebug("Time Series Start (seconds): $minkey <br> Exporting Columns");
            $this->logDebug(array_keys($this->tsvalues[$minkey]));
         }

         foreach (array_keys($this->tsvalues[$minkey]) as $thiskey) {
            if (in_array($thiskey, array_keys($this->logformats))) {
               # get the log file format from here, if it is set
               if ($this->debug) {
                  $this->logDebug("Getting format for log table " . $thiskey . "\n");
               }
               $outform .= $fdel . $this->logformats[$thiskey];
            } else {
               if ($this->debug) {
                  $this->logDebug("Guessing format for log table " . $thiskey . "\n");
               }
               if (is_numeric($this->tsvalues[$minkey][$thiskey])) {
                  $outform .= $fdel . $this->numform;
               } else {
                  $outform .= $fdel . $this->strform;
               }
            }
            $fdel = ',';
         }
         if ($this->debug) {
            $this->logDebug("Using format string: $outform <br>");
         }
         $outarr = nestArraySprintf($outform, $this->tsvalues);
         #$this->logDebug($outarr);
         $colnames = array(array_keys($this->tsvalues[$minkey]));
         if ($this->debug) {
            $colcsv = implode(',', $colnames);
            $this->logDebug("Columns: $colcsv <br>");
         }

         if ($this->debug) {
            $numlines = count($this->tsvalues);
            $this->logDebug("Outputting: $numlines lines <br>");
         }

         putDelimitedFile("$filename",$colnames,$this->translateDelim($this->delimiter),1,$this->fileformat);

         putArrayToFilePlatform("$filename", $outarr,0,$this->fileformat);
      }
   }

   function tsvalues2listobject($columns = array()) {
      if ($this->debug) {
         $this->logDebug("tsvalues2listobject called.");
      }
      if (is_object($this->listobject)) {
         if ($this->debug) {
            $this->logDebug("list object set, adding records: " . count($this->tsvalues));
         }
         $this->setDBCacheName();
         # format for output
         $fdel = '';
         $outform = '';
         
         if ($this->debug) {
            $this->logDebug("Outputting Time Series to db: $this->db_cache_name <br>");
            $this->logDebug("Using the following db-columntypes: " . print_r($this->dbcolumntypes,1) . " <br>");
         }
         //$this->listobject->debug = 1;
         $this->cache_create_sql = $this->listobject->array2Table($this->tsvalues, $this->db_cache_name, $columns, $this->dbcolumntypes, 1, $this->bufferlog, $this->db_cache_persist);
         //$this->listobject->debug = 0;
         if ($this->debug) {
            $this->logDebug($this->cache_create_sql);
            if ($this->listobject->error) {
               $this->logDebug("Last error from insert: " . $this->listobject->error . "<br>");
            }
         }
      } else {
         $this->logDebug("List object not set.<br>");
      }
   }

   function step() {
      # search for this time in seconds in the keys to the tsvalues array
      parent::step();
   }

   function getValue($thistime, $thisvar) {
      # search for this time in seconds in the keys to the tsvalues array
      if ($this->debug) {
         $sv = $this->state;
         if (isset($sv['the_geom'])) {
            $sv['the_geom'] = 'HIDDEN';
         }
         $this->logDebug($sv);
      }
      if (in_array($thisvar,array_keys($this->state)) ) {
         if ($this->debug) {
            $varval = $this->state[$thisvar];
            $this->logDebug("Returning $thisvar = $varval ");
         }
         return $this->state[$thisvar];
      } else {
         return NULL;
      }
   }

   function getInputs() {

      parent::getInputs();

      if ($this->ts_enabled) {
         $tvals = $this->searchTimeSeries();
         if ($this->debug) {
            $this->logDebug(" TS variable list = " . print_r(array_keys($tvals),1) . " <br>");
         }
         // forbidden to overwrite the following:
         $forbidden = array('thisdate','month','day','year');
         // check for run_mode and flow_mode from other sources, do not set if existing
         if (in_array('run_mode', array_keys($this->state))) {
           array_push($forbidden, 'run_mode');
         }
         if (in_array('flow_mode', array_keys($this->state))) {
           array_push($forbidden, 'flow_mode');
         }
         foreach(array_keys($tvals) as $tkey) {
            if (!in_array($tkey, $forbidden)) {
               if (isset($tvals[$tkey])) {
                  $this->state[$tkey] = $tvals[$tkey];
                  if ($this->debug) {
                     $this->logDebug(" State variable $tkey = " . $tvals[$tkey] . " <br>");
                  }
               } else {
                  // this state variable does not get retrieved from the time series
                  if ($this->debug) {
                     $this->logDebug(" State variable $tkey not a piece of time-series data <br>");
                  }
               }
            }
         }
      } else {
         if ($this->debug) {
            $this->logDebug(" $this->name time series disabled (ts_enabled = $this->ts_enabled )<br>");
         }
      }
         
      if ($this->debug) {
         $sv = $this->state;
         if (isset($sv['the_geom'])) {
            $sv['the_geom'] = 'HIDDEN';
         }
         $this->logDebug($sv);
      }
   }

  function searchTimeSeries2($searchtime = -99999, $starttime = -99999) {
    // use faster methods, array_shift, to get data 
    // array_shift until timestamp exceeds current, then we store 
    // prev_data = last piece of data *before* the current timestep 
    // next_data = next piece of data *after* the current timestep + dt 
    // curr_data = all pieces of data between previous and next timestep 
  }
   
   function searchTimeSeries($searchtime = -99999, $starttime = -99999) {
      # returns an array of values that corresponds to the state array at the given time
      # alows the user to specify a start time, if not passed in (-99999)
      # assumes that the starting point is the current timer value
      # alows the user to specify a start time, if not passed in (-99999) default to min value of
      # tsvalues array
      if ($searchtime == -99999) {
         $thistime = $this->timer->thistime->format("U");
         $prevtime = $this->timer->thistime->format("U") - ($this->timer->dt/2.0);
         $nexttime = $this->timer->thistime->format("U") + ($this->timer->dt/2.0);
      } else {
         $intime = new DateTime($searchtime);
         $thistime = $intime->format("U");
         $prevtime = $intime->format("U") - ($this->timer->dt/2.0);
         $nexttime = $intime->format("U") + ($this->timer->dt/2.0);
      }
      $retarr = array();

      if ($this->debug) {
         $this->logDebug("Searching for time series values at: $thistime <br>\n");
      }
      # get an array of the timeslotes, which are indexed by seconds (integer) and index them from 0 - count(timeslots)
      $timeslots = array_keys($this->tsvalues);
      # check to see if we are storing our timeseries in a database to enhance speed and limit memory consumption
      if ( (count($timeslots) <= 3) or ($nexttime > max($timeslots)) ) {
         # check to see if we can get cached values
         if ( $this->max_memory_values > 0) {
            $this->getCurrentDataSlice();
            # refresh timeslot array
            $timeslots = array_keys($this->tsvalues);
            if ($this->debug) {
               $this->logDebug("After getCurrentDataSlice() " . count($timeslots) . " timeslots available.");
            }
         }
      }
      
      
      //error_log (print_r($timeslots, 1));

      if (count($timeslots) > 0) {
         # check for exact match, if there, no further action necessary
         if (in_array($thistime, $timeslots) and (!(($this->intmethod > 2) and ($this->intmethod <= 6))) ) {
            $retarr = $this->tsvalues[$thistime];
            if ($this->debug) {
               $this->logDebug("Exact match Found at time: $thistime <br>\n");
               $this->logDebug($retarr);
            }
            $this->lasttimesec = $thistime;
            $akey = array_search($thistime, $timeslots);
            if (is_array($akey)) {
               $akey = $akey[0];
            }
            //$this->removePastValues($i, $akey, $thistime);
            $this->removePastValues(0, $akey, $thistime);
            $this->datastate = 0;
            return $retarr;
         }

         # if not an exact match, we check to see if we are outside of our data range (require extrap)
         # or inside of our data range (require interpolation).
         # we also check the rules to see if we are allowed to interpolate or extrapolate
         if ( ($thistime < min($timeslots)) or ($thistime > max($timeslots)) ) {
            if ($thistime < min($timeslots)) {
               // we are interpolating
               $this->datastate = 1;
            } else {
               // we must be extrapolating
               $this->datastate = 2;
            }
            # we need to extrapolate
            if ($this->debug) {
               $this->logDebug("Need to extrapolate.<br>");
            }
            switch ($this->extflag) {
               case 1: # never extrapolate
               if ($this->debug) {
                  $this->logDebug("Extrapolation disabled.<br>");
               }
               $firstts = array_keys($this->tsvalues);
               foreach (array_keys($this->tsvalues[$firstts[0]]) as $thisvar) {
                  $retarr[$thisvar] = NULL;
                  if ($this->debug) {
                     $this->logDebug("$thisvar set to NULL<br>");
                  }
               }
               break;

               case 0: # always extrapolate
                  switch ($this->extmethod) {
                     case 0:
                     # linear

                     break;

                     case 1:
                     if ($this->debug) {
                        $this->logDebug("Entering extrapolation mode.<br>\n");
                     }
                     if ($thistime < min($timeslots)) {
                        $retarr = $this->tsvalues[0];
                        $this->lasttimeslot = 0;
                     } else {
                        $this->lasttimeslot = count($this->tsvalues) - 1;
                        $retarr = $this->tsvalues[$this->lasttimeslot];
                     }

                     default:
                     if ($thistime < min($timeslots)) {
                        $retarr = $this->tsvalues[$timeslots[0]];
                        $this->lasttimeslot = 0;
                     } else {
                        $this->lasttimeslot = count($this->tsvalues) - 1;
                        $retarr = $this->tsvalues[$timeslots[$this->lasttimeslot]];
                     }
                  }
               break;
               
               default: # never extrapolate
               if ($this->debug) {
                  $this->logDebug("Extrapolation disabled.<br>");
               }
               $firstts = array_keys($this->tsvalues);
               foreach (array_keys($this->tsvalues[$firstts[0]]) as $thisvar) {
                  $retarr[$thisvar] = NULL;
                  if ($this->debug) {
                     $this->logDebug("$thisvar set to NULL<br>");
                  }
               }
               break;
            }

            return $retarr;
         }
 
         # otherwise, we search to interpolate, unless we  are never to interpolate
         if ($this->intflag == 1) {
            if ($this->debug) {
               $this->logDebug("Interpolation disabled - returning NULL.<br>\n");
            }
            # never interpolate
            foreach (array_keys($this->tsvalues[0]) as $thisvar) {
               $retarr[$thisvar] = NULL;
            }
            $this->datastate = 0;
           return $retarr;
         }
         # otherwise, proceed along
         if ($starttime == -99999) {
            $lasttimeslot = $this->lasttimeslot;
         } else {
            $searchtime = new DateTime($starttime);
            $searchsecs = $searchtime->format("U");
            $spos = array_search($searchsecs, $timeslots);
            if ($spos <> FALSE) {
               $lasttimeslot = 0;
            }
         }
         $dt = $this->timer->dt; # returns time increment in seconds
         $retval = 0;
         $found = 0;
         $cachedist = 0; # for use with mean/min/max span values
         $storei = 0;
         $spanvals = array();
         for ($i = $lasttimeslot; $i <= (count($this->tsvalues) - 2); $i++) {
            $ts = $timeslots[$i];
            $nts = $timeslots[$i + 1];
            $vars = array_keys($this->tsvalues[$ts]);
            #$this->logDebug($this->tsvalues[$ts]);
            foreach ($vars as $thisvar) {
               $tv = $this->tsvalues[$ts][$thisvar];
               $ntv = $this->tsvalues[$nts][$thisvar];
               if ($this->debug) {
                  $this->logDebug("Searching for $thisvar time-slot $i in $ts to $nts, time = $thistime <br>\n");
               }
               //error_log("Searching for time-slot $i in $ts to $nts, time = $thistime <br>\n");
               # do a mean, min, max or sum value for the period between the preceding, and next time step
               if ( ($this->intmethod > 2) and ($this->intmethod <= 6) ) {
                  if ( ($ts >= $prevtime) and ($ts <= $nexttime) ) {
                     if (!isset($spanvals[$thisvar])) {
                        # initialize storage space if need be
                        $spanvals[$thisvar] = array();
                     }
                     array_push($spanvals[$thisvar], $tv);
                  }
                  if ( ($prevtime > $ts) and ($nexttime < $nts) ) {
                     # we have a time step that is too narrow to obtain
                     # valid values for this method, thus we default to using the interpolating value
                     # get single interpolated value
                     if ( ($ts < $thistime) and ($nts > $thistime) ) {
                        # between values, interpolate
                        $savemethod = $this->intmethod;
                        $this->intmethod = 0;
                        $varout = $this->interpValue($thistime, $ts, $tv, $nts, $ntv);
                        $this->intmethod = $savemethod;
                        $spanvals[$thisvar] = array($varout);
                        if ($this->debug) {
                           $this->logDebug("<br>\nInterpolating $thisvar: [t+dt]: $thistime, [t]: $ts, Value[t]: $tv, [t+1]: $nts, Value[t+1]: $ntv, Value[t+dt]: $varout <br>\n");
                        }
                        $found = 1;
                     }
                  }
                  if ( ($ts < $nexttime) and ($nts > $thistime) ) {
                     # store the value of i for searching again
                     $tdist = $ts - $thistime;
                     if ( ($tdist > 0) and ($tdist <= $cachedist) ) {
                        $cachedist = $tdist;
                        $storei = $i;
                     }
                  }
                  if ($ts > $nexttime) {
                     # we have exceeded the span
                     $found = 1;
                     # decrement $i so that we don't lose previous values
                     if ($i > 0) {
                        $i += -1;
                     }
                  }
               } else {
                  # get single interpolated value, or previous value
                  if ( ($ts < $thistime) and ($nts > $thistime) ) {
                     # between values, interpolate or previous value
                     switch ($this->intmethod) {
                        case 0:
                        $varout = $this->interpValue($thistime, $ts, $tv, $nts, $ntv);
                        $retarr[$thisvar] = $varout;
                        if ($this->debug) {
                           $this->logDebug("<br>\nInterpolating $thisvar: [t+dt]: $thistime, [t]: $ts, Value[t]: $tv, [t+1]: $nts, Value[t+1]: $ntv, Value[t+dt]: $varout <br>\n");
                        }
                        $found = 1;
                        break;
                        
                        case 1:
                        # previous value
                        $retarr[$thisvar] = $tv;
                        if ($this->debug) {
                           $this->logDebug("<br>\nUsing Previous Value for $thisvar: $tv <br>\n");
                        }
                        $found = 1;
                        break;
                        
                        case 2:
                        # next value
                        $retarr[$thisvar] = $ntv;
                        if ($this->debug) {
                           $this->logDebug("<br>\nUsing Next Value for $thisvar: $ntv <br>\n");
                        }
                        $found = 1;
                        break;
                     }
                     
                  }
               }
            }
            if ($found) {
               break;
            }
         }
         $lasti = $i;
         if ( ($this->intmethod > 2) and ($this->intmethod <= 6) ) {
            if ($this->debug) {
               $this->logDebug("Using time span summary, mean/min/max.<br>");
            }
            $i = $storei;
            # create holders for aggregating this data span
            $meanarr = array();
            $sumarr = array();
            $retarr = array();
            $sp = 0;
            #$this->logDebug($spanvals);
            #$this->logDebug("<br>");
            foreach($vars as $thisvar) {
               $meanarr[$thisvar] = 0;
            }
            foreach(array_keys($spanvals) as $thisspan) {
               $meanarr[$thisspan] = 0;
               $sumarr[$thisspan] = 0;
               foreach ($spanvals[$thisspan] as $spanv) {
                  $meanarr[$thisspan] += $spanv;
                  $sumarr[$thisspan] += $spanv;
               }
               $sp = count($spanvals[$thisspan]);
               if ($sp > 0) {
                  $meanarr[$thisspan] = $meanarr[$thisspan] / $sp;
               }
            }
            #$this->logDebug($meanarr);
            #$this->logDebug("<br>");
            switch($this->intmethod) {
               case 3:
               # mean value
               $retarr = $meanarr;
               break;

               case 4:
               # min value
               foreach (array_keys($spanvals) as $spankey) {
                  $retarr[$spankey] = min($spanvals[$spankey]);
               }
               break;

               case 5:
               # max value
               foreach (array_keys($spanvals) as $spankey) {
                  $retarr[$spankey] = max($spanvals[$spankey]);
               }
               break;

               case 6:
               # sum of values
                  $retarr = $sumarr;
               break;
            }

         }
         # speed up execution by removing values that predate the currently used values
         $this->removePastValues($i, $lasti, $ts);
         //error_log($this->name . " has " . count($this->tsvalues) . " entries after slicing ");
         $this->datastate = 0;
      
         return $retarr;
      }
   }
   
   function removePastValues($i, $lasti, $ts) {
      # always set to zero, since the timeslots array is shortened as the tsvalues array is shortened
      $this->lasttimeslot = 0;
      # stash the model time in seconds
      $this->lasttimesec = $ts;
      //error_log("last time values is $ts ,i = $i and lasti = $lasti");
      $new_ta = array_slice($this->tsvalues, $lasti, count($this->tsvalues) - $lasti + 1, true);
      $this->tsvalues = $new_ta;
   }
   
   function getCurrentTSValues($searchtime = -99999, $starttime = -99999) {
      # returns an array of values that corresponds to the state array at the given time
      # alows the user to specify a start time, if not passed in (-99999)
      # assumes that the starting point is the current timer value
      # alows the user to specify a start time, if not passed in (-99999) default to min value of
      # tsvalues array
      if ($searchtime == -99999) {
         $thistime = $this->timer->thistime->format("U");
         $prevtime = $this->timer->thistime->format("U") - ($this->timer->dt/2.0);
         $nexttime = $this->timer->thistime->format("U") + ($this->timer->dt/2.0);
      } else {
         $intime = new DateTime($searchtime);
         $thistime = $intime->format("U");
         $prevtime = $intime->format("U") - ($this->timer->dt/2.0);
         $nexttime = $intime->format("U") + ($this->timer->dt/2.0);
      }
      $retarr = array();
      
      // pull values from the front of the stack until we are >= the desired time
         // stash values in a mini stack while pulling them off
         // refresh the current in memory TS stack if we have gotten to the end of it before the desired timestep
      // perform the desired matching, interpolation, aggregating or extrapolating depending on the 
      // data set 
      // return the values
   }

   function interpValue($thistime, $ts, $tv, $nts, $ntv) {

      if ($this->intflag == 2) {
         # places a limit on how long we can interpolate
         if ( abs($nts - $ts) > $this->ilimit ) {
            return NULL;
         }
      }
      switch ($this->intmethod) {
         case 0:
            // mean value
            $retval = $tv + ($ntv - $tv) * ( ($thistime - $ts) / ($nts - $ts) );
         break;

         case 1:
            // previous value
            $retval = $tv;
         break;

         case 2:
            // next value
            $retval = $ntv;
         break;

      }
      return $retval;
   }
}

class timeSeriesFile extends timeSeriesInput {
  // a static read-only file with time series values
  var $cache_ts = 1;
  var $location_type = 0; // 0 - local file system, 1 - http
  var $remote_url = '';
  var $file_vars = FALSE;
  var $file_info = FALSE;
  
  function sleep() {
    $file_vars = FALSE;
    $file_info = FALSE;
    parent::sleep();
  }
  
  function logState($logvalues = array()) {
    // need to call this to fix timestamp values such as modays that may get interpolated if
    // the source file is a model run record.
    $this->setStateTimerVars();
    parent::logState($logvalues);
  }

  function wake() {
    parent::wake();
    $fe = fopen($this->getFileName(),'r');
    error_log("File open result: $fe for " . $this->getFileName());
    if ($fe) {
      if ($this->location_type == 0) {
        fclose($fe);
      }
      $this->logDebug("Obtaining values from file: $this->filepath \n");
      error_log("Obtaining values from file: " . $this->getFileName);
      $this->tsVarsFromFile();
    } else {
      $this->logDebug("Can not find file: $this->filepath \n");
    }
    error_log("File Variables loaded. wake() returning.");
  }

  function getFileInfo() {
    if (!is_array($this->file_info)) {
      $this->file_info = array();
    }
    $this->file_info['path'] = $this->getFileName();
    $this->file_info['location_type'] = ($this->location_type == 1) ? 'Remote' : 'Unknown';
    $fe = fopen($this->getFileName(),'r');
    if ($fe) {
      $this->file_info['handle'] = $fe;
    }
    if ($fe and ($this->location_type == 0)) {
      $this->file_info['location_type'] = 'Local';
      $this->file_info['filemtime'] = filemtime($file_info['path']);
      $this->file_info['filesize'] = filesize($file_info['path']);
    }
  }
  
  function getFileName() {
    // handles file movement in the background, choices among source types
    switch ($this->location_type) {
      case 0:
      // local file system
      $retfile = $this->filepath;
      break;

      case 1:
      // http request
      $retfile = $this->remote_url;
      break;

      default:
      // local file system
      $retfile = $this->filepath;
      break;
    
    }
    if (strlen(trim($retfile)) == 0) {
      if (strlen(trim($this->remote_url)) > 0) {
        // try this, as we are migrating to all remote url field treatment instead of filenice.
        $retfile = $this->remote_url;
      }
      $retfile = null;
    }
    //error_log("Returning file name: $retfile ");
    return $retfile;
  }

  function init() {
    #$debug = 1;
    $this->cache_ts = 1;
    $fe = fopen($this->getFileName(),'r');
    if ($this->debug) {
      $this->logDebug("Opening for reading: " . $this->getFileName() . "<br>");
    }
    if ($fe) {
      $this->tsfile = $this->getFileName();
      if ($this->location_type == 0) {
        fclose($fe);
      }
    } else {
      $this->logError("Failed to open " . $this->getFileName() . "<br>");
      if ($this->debug) {
        $this->logDebug("Failed to open " . $this->getFileName() . "<br>");
      }
    }
    parent::init();
  }

  function finish() {
    # disable this since we assume this is a read-only file
    $this->cache_ts = 0;
    parent::finish();
  }

  function tsVarsFromFile() {
    # get the header line with variable names and the first line of values.
    # 
    $this->file_vars = array();
    if ($this->debug) {
      $this->logDebug("Function tsVarsFromFile called. <br>");
      $this->logDebug("calling readDelimitedFile($this->filepath,'$this->delimiter', 0, 2); <br>");
    }
    // set base types to avoid timestamp troubles
    $this->setBaseTypes();
    //$first2lines = readDelimitedFile($this->getFileName(),$this->translateDelim($this->delimiter), 0, 2);
    //error_log("Calling readDelimitedFile_fgetcsv for first 2 lines");
    //error_log("Opening for reading: " . $this->getFileName() . "<br>");
    $first2lines = readDelimitedFile_fgetcsv($this->getFileName(),$this->translateDelim($this->delimiter), 0, 2);
    if ($this->debug) { 
      $this->logDebug("First Line of $this->name : " . print_r($first2lines[0],1) . "<br>");
      $this->logDebug("2nd Line of $this->name : " . print_r($first2lines[1],1) . "<br>");
    }
    $nb = 0;
    if (!in_array('timestamp', $first2lines[0]) and !in_array('thisdate', $first2lines[0]) ) {
      error_log("timestamp/thisdate column missing in text file in " . $this->getFileName());
      //return;
    }
    foreach ($first2lines[0] as $thiskey => $thisvar) {
      if (trim($thisvar) == '') {
        $nb++;
        $this->logError("Blank value found in $this->name time series file " . $this->getFileName() . "<br>");
      } else {
        if ( ($thisvar <> 'timestamp') and ($thisvar <> 'thisdate') ) {
          if (!in_array($thisvar, $this->file_vars)) {
            $this->file_vars[] = $thisvar;
          }
        }
        if (!in_array($thisvar, array_keys($this->dbcolumntypes))) {
          if ($this->debug) {
            $this->logDebug("$thisvar not in dbcolumntypes array <br>");
          }
          $this->setSingleDataColumnType($thisvar, 'guess',  $first2lines[1][$thiskey]);
          //error_log("Calling setSingleDataColumnType($thisvar, 'guess',  " . $first2lines[1][$thiskey]);
          if ($this->debug) {
            $this->logDebug("Calling setSingleDataColumnType($thisvar, 'guess',  " . $first2lines[1][$thiskey] . ")<br>");
          }
          //$this->state[$thisvar] = $first2lines[1][$thiskey];
        }
        if ($this->debug) {
           $this->logDebug("Column $thisvar found.<br>\n");
        }
      }
      if ($nb > 0) {
        $this->logError("Total of $nb blank value found in $this->name time series file " . $this->getFileName() . "<br>");
      }
    }
  }
  function showHTMLInfo() {
    $htmlinfo = parent::showHTMLInfo();
    $htmlinfo .= "<br><strong>Note: </strong>File must contain column 'timestamp' with format U (seconds since Unix Epoch)";
    return $htmlinfo;
  }
}

class pumpObject extends hydroObject {

   var $criteria; # reference to the quantity to compare against, i.e. 'Qout' or 'Storage'
   var $priority; # order of execution if multiple withdrawals, 0 is highest priority
   var $withdrawals = array(); # array of pairs 'criteria_value'=>'withdrawal_value'
   var $currentdemand = 0.0;
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      
      $statenums = array('Qin','Qout','currentdemand');
      foreach ($statenums as $thiscol) {
         if (isset( $this->$thiscol)) {
            $this->setSingleDataColumnType($thiscol, 'float8', $this->$thiscol);
         } else {
            $this->setSingleDataColumnType($thiscol, 'float8', 0.0);
         }
      }
      
   }

   function getDemand($statear = 0) {
      # allows the user to pass in a custom state array if desired
      if ($statear == 0) {
         $statear = $this->state;
      }
      $demand = 0.0;
      $criteria_name = $this->criteria;
      #$this->logDebug($statear);
      if ($this->debug) {
         $this->logDebug("Criteria found: $criteria_name <br>\n");
      }
      if (in_array($criteria_name, array_keys($statear))) {
         ksort($this->withdrawals);
         #$this->logDebug($this->withdrawals);
         foreach (array_keys($this->withdrawals) as $drawlevel) {
            $cval = $statear[$criteria_name];
            if ($this->debug) {
               $this->logDebug("Comparing $criteria_name ( $cval ) to $drawlevel <br>\n");
            }
            if ($cval >= $drawlevel) {
               $demand = $this->withdrawals["$drawlevel"];
            }
         }
      }
      $this->currentdemand = $demand;
      $this->state['Qin'] = $demand;
      $this->state['Qout'] = $demand;
      return $demand;
   }
}

class pumpPctObject extends pumpObject {
   var $default_pct = 0.1;

   # makes pumps based on a percentage of flow rather than an absolute flow value
   function init() {
      # calls the parent routine
      parent::init();
      # check the array of withdrawals to make sure that none are greater than 1.0
      foreach (array_keys($this->withdrawals) as $thisc) {
         if ($this->withdrawals[$thisc] > 1.0) {
            $this->withdrawals[$thisc] = $this->default_pct;
         }
      }
   }


   function getDemand($statear = 0) {
      # allows the user to pass in a custom state array if desired
      if ($statear == 0) {
         $statear = $this->state;
      }
      $demand = 0.0;
      $criteria_name = $this->criteria;
      #$this->logDebug($statear);
      if ($this->debug) {
         $this->logDebug("Criteria found: $criteria_name <br>\n");
      }
      if (in_array($criteria_name, array_keys($statear))) {
         ksort($this->withdrawals);
         #$this->logDebug($this->withdrawals);
         foreach (array_keys($this->withdrawals) as $drawlevel) {
            $cval = $statear[$criteria_name];
            if ($this->debug) {
               $this->logDebug("Comparing $criteria_name ( $cval ) to $drawlevel <br>\n");
            }
            if ($cval >= $drawlevel) {
               $demand = $this->withdrawals["$drawlevel"] * $cval;
            }
         }
      }
      $this->currentdemand = $demand;
      $this->state['Qin'] = $demand;
      $this->state['Qout'] = $demand;
      return $demand;
   }
}


class USGSSyntheticRecord extends modelObject {
   # power function representation of a stream gage, may have multiple relationships types eventually
   var $m = 1.0; /* m in ( y = bx^m) */
   var $b = 1.0; /* b in ( y = bx^m) */
   // Upper Confidence interval
   var $mup = ''; /* m in ( y = bx^m) */
   var $bup = ''; /* b in ( y = bx^m) */
   // Lower Confidence interval
   var $mlow = ''; /* m in ( y = bx^m) */
   var $blow = ''; /* b in ( y = bx^m) */
   var $Q = 0.0;
   var $Qgage = 0.0;
   var $equationtype = 0; // 0 - power bx^m, 1 - linear = mx + b

   function init() {
      parent::init();
      $this->dbcolumntypes['m'] = 'float8';
      $this->dbcolumntypes['b'] = 'float8';
      $this->dbcolumntypes['Q'] = 'float8';
      $this->dbcolumntypes['mup'] = 'float8';
      $this->dbcolumntypes['bup'] = 'float8';
      $this->dbcolumntypes['Qup'] = 'float8';
      $this->dbcolumntypes['mlow'] = 'float8';
      $this->dbcolumntypes['blow'] = 'float8';
      $this->dbcolumntypes['Qlow'] = 'float8';
      $this->dbcolumntypes['Qgage'] = 'float8';
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      
      $statenums = array('m','b','Q','mup','bup','Qup','mlow','blow','Qlow','Qgage');
      foreach ($statenums as $thiscol) {
         if (isset($this->$thiscol)) {
            $this->setSingleDataColumnType($thiscol, 'float8', $this->$thiscol);
         } else {
            $this->setSingleDataColumnType($thiscol, 'float8', 0.0);
         }
      }
      
   }

   function setState() {
      parent::setState();
      $this->state['b'] = $this->b;
      $this->state['m'] = $this->m;
      $this->state['Q'] = $this->Q;
      $this->state['bup'] = $this->bup;
      $this->state['mup'] = $this->mup;
      $this->state['Qup'] = $this->Qup;
      $this->state['blow'] = $this->blow;
      $this->state['mlow'] = $this->mlow;
      $this->state['Qlow'] = $this->Qlow;
      $this->state['Qgage'] = $this->Qgage;
   }
   
   function step() { 
      // all step methods MUST call preStep(),execProcessors(), postStep()
      $this->preStep();
      if ($this->debug) {
         $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
      }
      // execute sub-components
      $this->execProcessors();
      
      $Qgage = $this->state['Qgage'];
      
      # perform local functions
      $Q = $this->solveFlowEquation($Qgage, $this->b, $this->m);
      $this->state['Q'] = $Q;
      if ( ($this->mup <> '') and ($this->bup <> '') ) {
         //$Qup = $this->bup * pow($Qgage, $this->mup);
         $Qup = $this->solveFlowEquation($Qgage, $this->bup, $this->mup);
         $this->state['Qup'] = $Qup;
      } else {
         $this->state['Qup'] = $Q;
      }
      if ( ($this->mlow <> '') and ($this->blow <> '') ) {
         //$Qlow = $this->blow * pow($Qgage, $this->mlow);
         $Qup = $this->solveFlowEquation($Qgage, $this->blow, $this->mlow);
         $this->state['Qlow'] = $Qlow;
      } else {
         $this->state['Qlow'] = $Q;
      }
      # END - local functions

      if ($this->debug) {
         $this->logDebug("$this->name Calling Logstate() thisdate = ");
      }
      $this->postStep();
   }
   
   function solveFlowEquation($Qgage, $b, $m) {
      if ($this->debug) {
         $this->logDebug("Solving for Q = ");
      }
      switch ($this->equationtype) {
         case 0:
         $Q = $b * pow($Qgage, $m);
         if ($this->debug) {
            $this->logDebug("$b * pow($Qgage, $m) <br>");
         }
         break;
         
         case 1:
         $inter = $b + $m * log10($Qgage);
         $Q = pow(10.0, $inter);
         if ($this->debug) {
            $this->logDebug("$b + $m * log($Qgage); <br>");
            $this->logDebug("pow(10.0, $inter ); <br>");
         }
         break;
         
      }
      if ($this->debug) {
         $this->logDebug("Preliminary Q = $Q <br>");
      }
      if ($Q < 0) {
         $Q = 0.0;
         if ($this->debug) {
            $this->logDebug("can not have negative value - Q = 0.0 <br>");
         }
      }
      return $Q;
   }
}

class channelObject extends hydroObject {
   var $base = 1.0; /* base width of channel */
   var $Z = 1.0; /* side slope ratio */
   var $length = 5000.0;
   var $pdepth = 0.5; # mean pool depth below channel bottom
   var $substrateclass = 'C';  # A, B, C, D
   var $channeltype = 2; # only trapezoidal channels (type 2) are currently supported
   var $totalwithdrawn = 0.0;
   var $Rin = 0.0;
   var $json2d = TRUE;
   var $storageinitialized = 0; # is storage initialized at beginning of run? If 0, this will cause the storage estimation to run
                                # this should only occur once per simulation, as the flag wil be set to 1 after running
   var $tol = 0.01;
   # key is name, values are:
   #     priority (0 is highest priority),
   #     criteria - the name of the state variable that the criteria is based on
   #     withdrawals = array(criteriavalue, amount (volume per hour))

   function init() {
      parent::init();
   }
   
   function wake() {
      parent::wake();
      $this->prop_desc['Qin'] = 'Upstream, or tributary inflows to this stream (cfs).  This will be combined with any local Runoff flows.';
      $this->prop_desc['Rin'] = 'Local Runoff in-flows (cfs).';
      $this->prop_desc['Qafps'] = 'Area weighted local inflows (acre-ft / sec).  This will be combined with any pre-weighted or upstream flows.';
      $this->prop_desc['length'] = 'Channel mainstem length (ft).';
      $this->prop_desc['demand'] = 'Withdrawal of water from this reach (cfs).';
      $this->prop_desc['last_demand'] = 'Withdrawal of water from this reach during last time step (cfs).';
      $this->prop_desc['discharge'] = 'Discharge of water into this reach (MGD).';
      $this->prop_desc['last_discharge'] = 'Discharge of water into this reach during last timestep (MGD).';
   }


   function setState() {
      parent::setState();
      $this->setSingleDataColumnType('base', 'float8', $this->base);
      $this->setSingleDataColumnType('Z', 'float8', $this->Z);
      $this->setSingleDataColumnType('slope', 'float8', $this->slope);
      $this->setSingleDataColumnType('length', 'float8', $this->length);
      $this->setSingleDataColumnType('substrateclass', 'varchar(2)', $this->substrateclass);
      $this->setSingleDataColumnType('pdepth', 'float8', $this->pdepth);
      $this->setSingleDataColumnType('last_S', 'float8', $this->pdepth);
      
      $statenums = array('Qout', 'depth', 'Vout', 'Storage', 'T', 'U', 'Uout','demand', 'pdepth', 'Rin', 'discharge', 'last_discharge', 'last_demand', 'imp_off', 'Qlocal', 'rejected_demand_mgd', 'rejected_demand_pct');
      foreach ($statenums as $thiscol) {
         $this->setSingleDataColumnType($thiscol, 'float8', 0);
         //$this->dbcolumntypes[$thiscol] = 'float8';
         //$this->data_cols[] = $thiscol;
      }
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      // set log formats
      $this->logformats['runoff_in'] = '%s';

      
   }
   
  function step() {
    // all step methods MUST call preStep(),execProcessors(), postStep()
    $this->preStep();
    if ($this->debug) {
       $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
    }
    // execute sub-components
    // execute child components (since this is a model container)
    $this->execComponents();
    # now execute any operations
    $this->execProcessors();

    $this->state['year'] = $this->timer->thistime->format('Y');
    $this->state['month'] = $this->timer->thistime->format('n');
    $this->state['day'] = $this->timer->thistime->format('j');
    $this->state['weekday'] = $this->timer->thistime->format('N');
    $this->state['week'] = $this->timer->thistime->format('W');
    $this->state['hour'] = $this->timer->thistime->format('G');
    $this->state['thisdate'] = $this->timer->thistime->format('Y-m-d');
    if ($this->debug) {
       $this->logDebug("<b>$this->name step() method called at hour " . $this->state['hour'] . " on " . $this->state['thisdate'] . ".</b><br>\n");
    }

    $Uin = $this->state['Uin']; // heat in
    $U0 = $this->state['U']; // heat in BTU/Kcal at previous timestep
    $T = $this->state['T']; // Temp at previous timestep
    $area = $this->state['area'];
    $Qafps = $this->state['Qafps'];
    $Rin = $this->state['Rin'];
    $discharge = $this->state['discharge']; // get any point source discharges into this water body (MGD)
    $Qlocal = 0.0;
    if ( ($area > 0) and ($Qafps > 0)) {
       $Qlocal = $Qafps * $area * 640.0 * 43560.0;
    }
    //error_log("Calculating Equation Qlocal += Rin - > $Qlocal += $Rin ;");
    if ( ($Rin > 0) ) {
       $Qlocal += $Rin;
    }
    //error_log("I2 = this->state['Qin'] + Qlocal + discharge * 1.547 -> $I2 = " . $this->state['Qin'] . " + $Qlocal + $discharge * 1.547 ;");

    // track previous days mean flow
    /*
    $pjday = $this->state['prev_jday'];
    if ( ( $pjday < ($jday -1)) or ( ($pjday == 365) and ($jday == 1 )) ) {
      // need to re-up
      $this->state['prevday_Qin'] = $this->state['prevday_Qin_count'] > 0 ? ($this->state['prevday_Qin_sum'] / $this->state['prevday_Qin_count']) : $this->state['prevday_Qin_sum'];
      $this->state['prevday_Qin_count'] = 1;
      $this->state['prevday_Qin_sum'] = $this->state['Qin'];
    } else {
      $this->state['prevday_Qin_count'] += 1;
      $this->state['prevday_Qin_sum'] += $this->state['Qin'];        
    }

    */

    $I2 = $this->state['Qin'] + $Qlocal + $discharge * 1.547;
    if ($this->debug) {
       $this->logDebug("Final Inflows I2 : $I2 = " . $this->state['Qin'] . " + $Qlocal + $discharge <br>\n");
    }
    $I1 = $this->state['Iold'];
    $O1 = $this->state['Qout'];
    $S1 = $this->state['Storage'];
    $initialStorage = $this->state['Storage'];
    $depth = $this->state['depth'];
    $demand = $this->state['demand'];
    // avoid Storage < 0 by adjusting demand as much as possible 
    $demand_balance = $demand - $I2;
    $rejected_demand_mgd = 0.0;
    $rejected_demand_pct = 0.0;
   if ( ($demand_balance > 0) and ($demand > 0) ) {
    $rejected_demand = $demand_balance;
    $rejected_demand_mgd = $rejected_demand / 1.547;
    $rejected_demand_pct = $rejected_demand / $demand;
    $demand = $demand - $rejected_demand;
   }
    if ($this->length > 0) {
       // if length is set to zero we automatically pass inflows

       if ($this->storageinitialized == 0) {
          # first time, need to estimate initial storage,
          # assuems that we are in steady state, that is,
          # the initial and final Q, and S are equivalent
          $I1 = $I2;
          $O1 = $I2;
          if ($this->debug) {
             $this->logDebug("Estimating initial storage, calling: storageroutingInitS($I2, $this->base, $this->Z, $this->channeltype, $this->length, $this->slope, $dt, $this->n, $this->units, 0)");
          }
          $S1 = storageroutingInitS($I2, $this->base, $this->Z, $this->channeltype, $this->length, $this->slope, $dt, $this->n, $this->units, 0);
          if ($this->debug) {
             $this->logDebug("Initial storage estimated as $S1 <br>\n");
          }
          $this->storageinitialized = 1;
       }

       # get time step from timer object
       $dt = $this->timer->dt;

       if($this->debug) {
          $dtime = $this->timer->thistime->format('r');
          $this->logDebug("Calculating flow at time $dtime <br>\n");
          $this->logDebug("Iold = $I1, Qin = $I2, Last Qout = $O1, base = $this->base, Z = $this->Z, type = 2, Storage = $S1, length = $this->length, slope = $this->slope, $dt, n = $this->n <br>\n");
          #die;
       }
       

       # now execute any operations
       #$this->execProcessors();
       # re-calculate the channel flow parameters, if any other operations have altered the flow:
       list($Vout, $Qout, $depth, $Storage, $its) = storagerouting($I1, $I2, $O1, $demand, $this->base, $this->Z, $this->channeltype, $S1, $this->length, $this->slope, $dt, $this->n, $this->units, 0);
       if ( ($I1 > 0) and ($I2 > 0) and ($demand < $I1) and ($demand < $I2) and ($Qout == 0) ) {
          // numerical error, adjust
          // @todo: revisit this.  If storage is available in the channel, flow may theoretically be 0.0
          //        but a withdrawal could be possible?  Mannings roughness etc?
          //        Also need to create handling of rejected demand.  That is, do not let the storage be < 0.0
          $Qout = (($I1 + $I2) / 2.0) - $demand;
       }
    } else {
       // zero length channel, this is a pass-through - still decrement storage if we ask for it though
       $Vout = 0.0;
       $Storage = 0.0;
       $depth = 0.0;
       if ($demand > 0) {
          if ($I2 > $demand) {
             $Qout = $I2 - $demand;
          } else {
             $Qout = 0.0;
          }
       } else {
          $Qout = $I2;
       }
    }
    
    $this->state['last_S'] = $S1;
    $this->state['Qin'] = $I2;
    $this->state['Qlocal'] = $Qlocal;
    $this->state['Vout'] = $Vout;
    $this->state['area'] = $area;
    $this->state['Qout'] = $Qout;
    $this->state['depth'] = $depth;
    $this->state['Storage'] = $Storage;
    $this->state['last_demand'] = $demand;
    $this->state['last_discharge'] = $discharge;
    $this->state['rejected_demand_mgd'] = $rejected_demand_mgd;
    $this->state['rejected_demand_pct'] = $rejected_demand_pct;
    $this->state['its'] = $its;

    if($this->debug) {
       $this->logDebug("Qout = $Qout <br>\n");
    }
    
    // now calculate heat flux
    // O1 is outflow at last time step, 
    $U = ($Storage * ($U0 + $Uin)) / ( $Qout * $dt + $Storage);
    switch ($this->units) {
       case 1:
       // SI
       $T = $U / $Storage; // this is NOT right, don't know what units for storage would be in SI, since this is not really implemented
       break;
       
       case 2:
       // EE
       $T = 32.0 + ($U / ($Storage * 7.4805)) * (1.0 / 8.34); // Storage - cubic feet, 7.4805 gal/ft^3
       break;
    }
    // let's also assume that the water isn't frozen, so we limit this to zero
    if ($T < 0) {
       $T = 0;
    }
    $Uout = $U0 + $Uin - $U;
    $this->state['U'] = floatval($U);
    $this->state['Uout'] = floatval($Uout);
    $this->state['T'] = floatval($T);
       
    $this->postStep();
    $this->totalflow += floatval($Qout * $dt);
    $this->totalinflow += floatval($I2 * $dt);
    $this->totalwithdrawn += floatval($demand * $dt);
  }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'Vout');
      array_push($publix, 'depth');
      array_push($publix, 'length');
      array_push($publix, 'Z');
      array_push($publix, 'pdepth');
      array_push($publix, 'demand');

      return $publix;
   }
}

class storageObject extends modelObject {
   # a generic object, needs a depth/storage table
   var $maxcapacity = 1000.0;
   var $unusable_storage = 100.0;
   var $max_usable = 100.0;
   var $depth = 10.0;
   var $fulldepth = 10.0;
   var $initstorage = 100.0;
   var $Qout = 0.0;
   var $Qin = 0.0;
   
   function wake() {
      parent::wake();
      $this->prop_desc['Qin'] = 'Inflows to this impoundment (cfs).';
      $this->prop_desc['Qout'] = 'Total outflows from this impoundment includes spillage and flow-bys (cfs).';
      $this->prop_desc['initstorage'] = 'Initial impoundment storage (acre-feet).';
      $this->prop_desc['maxcapacity'] = 'Maximum impoundment storage (acre-feet).';
      $this->prop_desc['unusable_storage'] = 'Unusable impoundment storage (acre-feet).';
   }

   function init() {
      parent::init();
      /*
      $inames = array_keys($this->inputs);
      // this should form the basis of an automated way of going through all of a model components 
      // internal properties, looking to see if there is an input for them, and setting the property to the value
      // of that input.  Or not???
      if (in_array('initstorage',$inames)) {
         if (isset($this->inputs['initstorage'][0])) {
            if (is_object($this->inputs['initstorage'][0]['object'])) {
               $this->initstorage = $this->inputs['initstorage'][0]['object']->value;
            }
         }
      } 
      
      if (in_array('unusable_storage',$inames)) {
         if (isset($this->inputs['unusable_storage'][0])) {
            if (is_object($this->inputs['unusable_storage'][0]['object'])) {
               $this->unusable_storage = $this->inputs['unusable_storage'][0]['object']->value;
            }
         }
      } 
      
      if (in_array('maxcapacity',$inames)) {
         if (isset($this->inputs['maxcapacity'][0])) {
            if (is_object($this->inputs['maxcapacity'][0]['object'])) {
               $this->maxcapacity = $this->inputs['maxcapacity'][0]['object']->value;
            }
         }
      }
      */
      
      $this->Storage = $this->initstorage;
      $this->state['Storage'] = $this->initstorage;
      $this->state['unusable_storage'] = $this->unusable_storage;
      $this->state['maxcapacity'] = $this->maxcapacity;      
      $this->state['max_usable'] = $this->maxcapacity - $this->unusable_storage;
      $this->state['fulldepth'] = $this->fulldepth;
      $this->state['Qin'] = $this->Qin;
      $this->state['Qout'] = $this->Qout;
   }

   function setState() {
      parent::setState();
      $this->state['maxcapacity'] = $this->maxcapacity;
      $this->state['initstorage'] = $this->initstorage;
      $this->state['unusable_storage'] = $this->unusable_storage;
      $this->state['max_usable'] = $this->maxcapacity - $this->unusable_storage;
      $this->state['fulldepth'] = $this->fulldepth;
      $this->state['Qin'] = $this->Qin;
      $this->state['Qout'] = $this->Qout;
      $this->state['Storage'] = $this->initstorage;
   }
   
   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'Storage');
      array_push($publix, 'depth');
      array_push($publix, 'maxcapacity');
      array_push($publix, 'max_usable');
      array_push($publix, 'initstorage');
      array_push($publix, 'Qout');
      array_push($publix, 'Qin');
      array_push($publix, 'unusable_storage');

      return $publix;
   }
}

class hydroImpoundment extends hydroObject {
   # a generic object, needs a depth/storage/area table
   var $maxcapacity = 1000.0;
   var $unusable_storage = 100.0;
   var $max_usable = 100.0;
   var $depth = 10.0;
   var $fulldepth = 10.0;
   var $initstorage = 100.0;
   var $Qout = 0.0;
   var $Qin = 0.0;
   var $full_surface_area = 0.0; // this should be deprecated in favor of the parent class "area"
   
   function wake() {
      parent::wake();
      $this->prop_desc['Qin'] = 'Inflows to this impoundment (cfs).';
      $this->prop_desc['Qout'] = 'Total outflows from this impoundment includes spillage and flow-bys (cfs).';
      $this->prop_desc['initstorage'] = 'Initial impoundment storage (acre-feet).';
      $this->prop_desc['maxcapacity'] = 'Maximum impoundment storage (acre-feet).';
      $this->prop_desc['demand'] = 'Rate of Water Withdrawal (MGD).';
      $this->prop_desc['unusable_storage'] = 'Unusable impoundment storage (acre-feet).';
      $this->prop_desc['flowby'] = 'Minimum flow to be maintained below the spillway (cfs).';
      $this->prop_desc['evap_acfts'] = 'Evaporation Rate (ac-ft/s).';
      $this->prop_desc['refill_full_mgd'] = 'Rate to refill in a single timestep (MGD).';
      $this->prop_desc['refill'] = 'Current rate of refill into storage (MGD).';
      $this->prop_desc['discharge'] = 'Discharge of water into this impoundment (MGD).';
      $this->prop_desc['lake_elev'] = 'Elevation (feet ASL) of water surface.';
      $this->prop_desc['pct_use_remain'] = 'Percent of Usable Storage Remaining.';
      $this->prop_desc['use_remain_mg'] = 'Usable Storage Remaining in MG.';
      $this->prop_desc['et_in'] = 'Evapotrasnpiration Input (inches / day)';
      $this->prop_desc['precip_in'] = 'Precipitation (inches / day)';
      $this->prop_desc['evap_mgd'] = 'Calculated rate of evaporation off the lakes surface (MGD).';
      $this->prop_desc['demand_met_mgd'] = 'Demand actually satisified (MGD).';
      $this->prop_desc['deficit_acft'] = 'Storage needed to meet desired demand if deficit (acft).';
      $this->setSingleDataColumnType('lake_elev', 'float8',0);
      $this->setSingleDataColumnType('evap_mgd', 'float8',0);
      $this->setSingleDataColumnType('et_in', 'float8',0);
      $this->setSingleDataColumnType('precip_in', 'float8',0);
      $this->setSingleDataColumnType('pct_use_remain', 'float8',0);
      $this->setSingleDataColumnType('use_remain_mg', 'float8',0);
      $this->setSingleDataColumnType('days_remaining', 'float8',0);
      $this->setSingleDataColumnType('demand_met_mgd', 'float8',0);
      $this->setSingleDataColumnType('deficit_acft', 'float8',0);
      $this->setSingleDataColumnType('release', 'float8',0);
      $this->setSingleDataColumnType('maxcapacity', 'float8',0);
      $this->setSingleDataColumnType('refill_full_mgd', 'float8',0);
   }

   function init() {
      parent::init();
      $this->Storage = $this->initstorage;
      $this->state['Storage'] = $this->initstorage;
      $this->state['maxcapacity'] = $this->maxcapacity;
      $this->state['initstorage'] = $this->initstorage;
      $this->state['max_usable'] = $this->maxcapacity - $this->unusable_storage;
      $this->state['unusable_storage'] = $this->unusable_storage;
      $this->state['fulldepth'] = $this->fulldepth;
      $this->state['Qin'] = $this->Qin;
      $this->state['Qout'] = $this->Qout;
      $this->state['discharge'] = 0.0;
      $this->state['lake_elev'] = 1.0;
      $this->state['pct_use_remain'] = 0.0;
      $this->state['precip'] = 0.0;
      $this->state['pan_evap'] = 0.0;
      $this->state['precip_in'] = NULL;
      $this->state['et_in'] = NULL;
      $this->state['evap_mgd'] = 0.0;
      $this->state['use_remain_mg'] = 0.0;
      $this->state['demand_met_mgd'] = 0.0;
      $this->state['deficit_acft'] = 0.0;
      $this->processors['storage_stage_area']->valuetype = 2; // 2 column lookup (col & row)
   }

   function setState() {
      parent::setState();
      $this->state['maxcapacity'] = $this->maxcapacity;
      $this->state['initstorage'] = $this->initstorage;
      $this->state['unusable_storage'] = $this->unusable_storage;
      $this->state['max_usable'] = $this->maxcapacity - $this->unusable_storage;
      $this->state['fulldepth'] = $this->fulldepth;
      $this->state['Qin'] = $this->Qin;
      $this->state['Qout'] = $this->Qout;
      $this->state['pan_evap'] = 0.0;
      $this->state['precip'] = 0.0;
      $this->state['precip_in'] = NULL;
      $this->state['et_in'] = NULL;
      $this->state['demand'] = 0.0;
      $this->state['evap_acfts'] = 0.0;
      $this->state['refill_full_mgd'] = 0.0;
      $this->state['refill'] = 0.0;
      $this->state['discharge'] = 0.0;
      $this->state['lake_elev'] = 0.0;
      $this->state['pct_use_remain'] = 1.0;
      $this->state['evap_mgd'] = 0.0;
      $this->state['days_remaining'] = 0.0;
      $this->state['demand_met_mgd'] = 0.0;
      $this->state['deficit_acft'] = 0.0;
   }
   
   function create() {
      parent::create();
      // set up a table for impoundment geometry
      $this->logDebug("Create() function called <br>");
      
      if (isset($this->processors['storage_stage_area'])) {
         unset($this->processors['storage_stage_area']);
      }
      
      // matrix subcomponent to allow users to simulate stage/storage/area tables
      $storage_stage_area = new dataMatrix;
      $storage_stage_area->listobject = $this->listobject;
      $storage_stage_area->name = 'storage_stage_area';
      $storage_stage_area->wake();
      $storage_stage_area->numcols = 3;  
      $storage_stage_area->valuetype = 2; // 2 column lookup (col & row)
      $storage_stage_area->keycol1 = ''; // key for 1st lookup variable
      $storage_stage_area->lutype1 = 1; // lookp type - interpolate for storage
      $storage_stage_area->keycol2 = 'year'; // key for 2nd lookup variable
      $storage_stage_area->lutype2 = 0; // lookup type - exact match with column names for surface area
      // add a row for the header line
      $storage_stage_area->numrows = 3;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      $storage_stage_area->matrix[] = 'storage';
      $storage_stage_area->matrix[] = 'stage';
      $storage_stage_area->matrix[] = 'surface_area';
      $storage_stage_area->matrix[] = 0; // put a basic sample table - conic
      $storage_stage_area->matrix[] = 0; // put a basic sample table - conic
      $storage_stage_area->matrix[] = 0; // put a basic sample table - conic
      $storage_stage_area->matrix[] = $this->maxcapacity; // put a basic sample table - conic
      $storage_stage_area->matrix[] = $this->fulldepth; // put a basic sample table - conic
      $storage_stage_area->matrix[] = $this->full_surface_area; // put a basic sample table - conic
      
      if ($this->debug) {
         $this->logDebug("Trying to add stage-surfacearea-storage sub-component matrix with values: " . print_r($storage_stage_area->matrix,1) . " <br>");
      }
      $this->addOperator('storage_stage_area', $storage_stage_area, 0);
      if ($this->debug) {
         $this->logDebug("Processors on this object: " . print_r(array_keys($this->processors),1) . " <br>");
      }
   }
   
   function step() {
      // all step methods MUST call preStep(),execProcessors(), postStep()
      $this->preStep();
      if ($this->debug) {
         $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
      }
      # then process calculations
      $this->execComponents();
      // we have just executed our children and gotten the results from their broadcasts
      // now we will execute our local sub-comps to process any data obtained from children via broadcast
      // execute sub-components
      $this->execProcessors();
      
      if ($this->debug) {
         $this->logDebug("Step Begin state[] array contents " . print_r($this->state,1) . " <br>\n");
      }
      //$this->debug = 1;

      $Uin = $this->state['Uin']; // heat in
      $U0 = $this->state['U']; // total heat in BTU/Kcal at previous timestep
      $T = $this->state['T']; // Temp at previous timestep
      $Q0 = $this->state['Qout']; // outflow during previous timestep
      $S0 = $this->state['Storage']; // storage at end of previous timestep (ac-ft)
      $Qin = $this->state['Qin'];
      $demand = $this->state['demand']; // assumed to be in MGD
      $refill = $this->state['refill']; // assumed to be in MGD
      $discharge = $this->state['discharge']; // assumed to be in MGD
      if ( isset($this->state['flowby']) and (is_numeric($this->state['flowby'])) ) {
         $flowby = $this->state['flowby']; // assumed to be in cfs
      } else {
         $flowby = 0;
      }
      // maintain backward compatibility with old ET nomenclature
      if (!($this->state['et_in'] === NULL)) {
         $pan_evap = $this->state['et_in'];
      } else {
         $pan_evap = $this->state['pan_evap']; // assumed to be in inches/day
      }
      // maintain backward compatibility with old precip nomenclature
      if (!($this->state['precip_in'] === NULL)) {
         $precip = $this->state['precip_in']; // assumed to be in inches/day
      } else {
         $precip = $this->state['precip']; // assumed to be in inches/day
      }
      // this checks to see if the user has subclassed the area and stage(depth) calculations
      // or if it is using the internal routines with the stage/storage/area table
      if (isset($this->processors['area'])) {
         $area = $this->state['area']; // area at the beginning of the timestep - assumed to be acres
      } else {
         // calculate area - in an ideal world, this would be solved simultaneously with the storage
         if (isset($this->processors['storage_stage_area'])) {
            // must have the stage/storage/sarea dataMatrix for this to work
            if ($this->debug) {
               $this->processors['storage_stage_area']->debug = 1;
            }
            $this->processors['storage_stage_area']->lutype2 = 0; // a fix
            $stage = $this->processors['storage_stage_area']->evaluateMatrix($S0,'stage');
            $area = $this->processors['storage_stage_area']->evaluateMatrix($S0,'surface_area');
            if ($this->debug) {
               $this->processors['storage_stage_area']->debug = 0;
            }
         } else {
            $stage = 0;
            $area = 0;
            $this->reportstring .= "Element $this->elemname does not have a storage_stage_area table.\n";
            error_log("Element $this->elemname does not have a storage_stage_area table.");
         }
      }
      $dt = $this->timer->dt; // timestep in seconds
      if (isset($this->processors['maxcapacity'])) {
         $max_capacity = $this->state['maxcapacity']; // we have subclassed this witha stage-discharge relationship or oher
      } else {
         $max_capacity = $this->maxcapacity;
      }
      
      // we need to include some ability to plug-and-play the evap and other routines to allow users to sub-class it
      // or the components that go into it, such as the storage/depth/surface_area relationships
      // could look at processors, and if any of the properties are sub-classed, just take them as they are
      // also need inputs such as the pan_evap
      // since the processors have already been exec'ed we could just take them, but we could also get fancier
      // and look at each step in the process to see if it has been sub-classed and insert it in the proper place.
      
      // calculate evaporation during this time step - acre-feet per second
      // need estimate of surface area.  SA will vary based on area, but we assume that area is same as last timestep area
      $evap_acfts = $area * $pan_evap / 12.0 / 86400.0;
      $precip_acfts = $area * $precip / 12.0 / 86400.0; 
      if ($this->debug) {
         $this->logDebug("Calculating P and ET: P) $precip_acfts = $area * $precip / 12.0 / 86400.0;  <br>\n ET: $evap_acfts = $area * $pan_evap / 12.0 / 86400.0;<br>\n");
      }
      $thisdate = $this->state['thisdate'];
      // change in storage
      if ($this->debug) {
         $this->logDebug("Calculating Volume Change: storechange = S0 + ((Qin - flowby) * dt / 43560.0)+ (1.547 * refill * dt / 43560.0) - (1.547 * demand * dt /  43560.0) - (evap_acfts * dt) + (precip_acfts * dt); <br>\n");
         $this->logDebug(" :::: $storechange = $S0 + (($Qin - $flowby) * $dt / 43560.0)+ (1.547 * $refill * $dt / 43560.0) - (1.547 * $demand * $dt /  43560.0) - ($evap_acfts * $dt) + ($precip_acfts * $dt); <br>\n");
      }
      $storechange = $S0 + (($Qin - $flowby) * $dt / 43560.0) + (1.547 * $discharge * $dt / 43560.0)  + (1.547 * $refill * $dt / 43560.0) - (1.547 * $demand * $dt /  43560.0) - ($evap_acfts * $dt) + ($precip_acfts * $dt);
      if ($storechange < 0) {
         // what to do with flowby & wd?
         // if storechange is less than zero, its magnitude represents the deficit of flowby+demand
         // we can either choose to evenly distribute them or assume that demand wins
         $deficit_acft = abs($storechange);
         $s_avail = (1.547 * $demand * $dt /  43560.0) + ($flowby * $dt /  43560.0) - $deficit_acft;
         if ($s_avail <= (1.547 * $demand * $dt /  43560.0)) {
            // no water available for flowby
            $flowby = 0.0;
            $demand_met_mgd = $s_avail * 43560.0 / (1.547 * $dt);
         } else {
            // flowby is remainder
            $flowby = ($s_avail - (1.547 * $demand * $dt /  43560.0)) * 43560.0 / $dt;
            $demand_met_mgd = $demand;
         }
         $storechange = 0;
      } else {
         $demand_met_mgd = $demand;
         $deficit_acft = 0.0;
      }
      $Storage = min(array($storechange, $max_capacity));
      if ($storechange > $max_capacity) {
         $spill = ($storechange - $max_capacity) * 43560.0 / $dt;
      } else {
         $spill = 0;
      }
      if ($Storage < 0.0) {
         $Storage = 0.0;
      }
      if (isset($this->processors['Qout'])) {
         $Qout = $this->state['Qout']; // we have subclassed this witha stage-discharge relationship or oher
      } else {
         $Qout = $spill + $flowby;
      }
      
      // local unit conversion dealios
      $this->state['evap_mgd'] = $evap_acfts * 28157.7;
      $this->state['pct_use_remain'] = ($Storage - $this->state['unusable_storage']) / ($this->state['maxcapacity'] - $this->state['unusable_storage']);
      $this->state['use_remain_mg'] = ($Storage - $this->state['unusable_storage']) / 3.07;
      if ($this->state['use_remain_mg'] < 0) {
         $this->state['use_remain_mg'] = 0;
         $this->state['pct_use_remain'] = 0;
      }
      // days remaining
      if ( ($demand > 0) and ($dt > 0)) {
         $days_remaining = $this->state['use_remain_mg'] / ($demand);
      } else {
         $days_remaining = 0;
      }

      $this->state['days_remaining'] = $days_remaining;
      $this->state['deficit_acft'] = $deficit_acft;
      $this->state['demand_met_mgd'] = $demand_met_mgd;
      $this->state['depth'] = $depth;
      $this->state['Storage'] = $Storage;
      $this->state['Vout'] = $Vout;
      $this->state['Qout'] = $Qout;
      $this->state['depth'] = $stage;
      $this->state['Storage'] = $Storage;
      $this->state['spill'] = $spill;
      $this->state['area'] = $area;
      $this->state['evap_acfts'] = $evap_acfts;
      $this->state['storage_mg'] = $Storage / 3.07;
      $this->state['lake_elev'] = $stage;
      $this->state['refill_full_mgd'] = (($max_capacity - $Storage) / 3.07) * (86400.0 / $dt);
      
      // now calculate heat flux
      // O1 is outflow at last time step, 
      if ( ( $Qout * $dt + $Storage) > 0) {
         $U = ($Storage * ($U0 + $Uin)) / ( $Qout * $dt + $Storage);
      } else {
         $U = 0.0;
      }
      switch ($this->units) {
         case 1:
         // SI
         $T = $U / $Storage; // this is NOT right, don't know what units for storage would be in SI, since this is not really implemented
         break;
         
         case 2:
         // EE
         $T = 32.0 + ($U / ($Storage * 7.4805)) * (1.0 / 8.34); // Storage - cubic feet, 7.4805 gal/ft^3
         break;
      }
      // let's also assume that the water isn't frozen, so we limit this to zero
      if ($T < 0) {
         $T = 0;
      }
      $Uout = $U0 + $Uin - $U;
      $this->state['U'] = $U;
      $this->state['Uout'] = $Uout;
      $this->state['T'] = $T;
         
      $this->postStep();
      $this->totalflow += $Qout * $dt;
      $this->totalinflow += (1.547 * $refill + $Qin) * $dt;
      $this->totalwithdrawn += $demand * $dt;
      
      if ($this->debug) {
         $this->logDebug("Step END state[] array contents " . print_r($this->state,1) . " <br>\n");
      }
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'Storage');
      array_push($publix, 'depth');
      array_push($publix, 'maxcapacity');
      array_push($publix, 'max_usable');
      array_push($publix, 'initstorage');
      array_push($publix, 'Qout');
      array_push($publix, 'Qin');
      array_push($publix, 'unusable_storage');
      array_push($publix, 'evap_acfts');
      array_push($publix, 'refill_full_mgd');
      array_push($publix, 'refill');
      array_push($publix, 'lake_elev');
      array_push($publix, 'pct_use_remain');
      array_push($publix, 'evap_mgd');
      array_push($publix, 'days_remaining');

      return $publix;
   }
}

class blankShell extends modelObject {

   # has no internal functions defined, all operations must come from operators added into the component
   # within the model design

   function init() {
      parent::init();
   }

   function step() {
      parent::step();
   }

}

class surfaceObject extends hydroObject {
   var $base = 5000.0; /* base width of surface, idealized as rectangle */
   var $length = 500.0;
   var $precip = 0.0;
   var $irate = 0.0;
   var $Qvel = 0.0; # sheet flow velocity
   var $pct_clay = 15.0;
   var $pct_sand = 75.0;
   var $pct_om = 0.05;
   var $ksat = 0.0;
   var $wiltp = 0.0;
   var $thetasat = 0.0;
   var $Sav = 4.68; # average Suction at wetting front
   var $fc = 0.0;
   var $totalflow = 0.0;
   var $F = 0.0; # total water infiltrated into soil column in inches
   var $sdepth = 50.0; # soil depth inches
   var $atmosdep = array();

   function init() {
      parent::init();
      $this->state['P'] = 0.0;
      $this->state['F'] = 0.0;
      $this->state['I'] = 0.0;
      $this->initSurfaceParams();
   }

   function initSurfaceParams() {

      $ksat_cm = soilhydroksat($this->pct_sand, $this->pct_clay);
      $this->ksat = $ksat_cm / 2.54;
      # other params are in length/length, so they are non-dimensional
      $this->thetasat = soilhydrothetasat($this->pct_sand, $this->pct_clay);
      $this->fc = soilhydrothetafc($this->pct_sand, $this->pct_clay);
      $this->wiltp = soilhydrowiltp($this->pct_sand, $this->pct_clay);
      $this->area = $this->length * $this->base;

      $Smax = (log(1500.0)-log(33.0))/(log($this->fc) - log($this->wiltp));
      $Smin = exp(log(33.0)+($Smax*log($this->fc)));
      $this->Sav = (($Smax + $Smin)/2.0);
      if ($this->debug) {
         $this->logDebug("Soil Properties: ThetaSat = $this->thetasat, FC = $this->fc, Ksat = $this->ksat, Sav = $this->Sav <br>\n");
      }
   }

   function addAtmos($thisinput) {
      array_push($this->atmosdep, $thisinput);
   }

   function preStep() {
      # Iold is used by storage routing routines
      $this->precip = 0;
      $this->state['P'] = 0;

      foreach ($this->inputs['Pin'] as $thisin) {
         $outparam = $thisin['param'];
         $inobject = $thisin['object'];
         $this->precip += $inobject->Qout;
         $this->state['P'] += $inobject->state['Qout'];
      }
      parent::preStep();
   }

   function step() {
      // all step methods MUST call preStep(),execProcessors(), postStep()
      $this->preStep();
      if ($this->debug) {
         $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
      }
      // execute sub-components
      $this->execProcessors();

      $Qin = $this->state['Qin'];
      $Qout = $this->state['Qout'];
      $F = $this->state['F'];
      $depth = $this->state['depth'];
      $P = $this->state['P'];
      $I = $this->state['I'];

      # get time step from timer object
      $dt = $this->timer->dt;

      if($this->debug) {
         $dtime = $this->timer->thistime->format('r');
         $this->logDebug("Calculating runoff at time $dtime <br>\n");
         #die;
      }

      # step iteration next
      $I = green_ampt( $P, $dt, $this->fc, $this->thetasat, $this->ksat, $depth, $F, $I, $this->Sav, 0.00001, $this->debug);

      $F += ($I * $dt / 3600.0);

      #$this->depth += ($this->precip - $this->irate) * $dt / 3600.0;

      list($Vout, $depth) = kinwave($depth, $Qin, $Qout, $P, $I, $this->slope, $this->length, $this->base, $dt, $this->n, $this->units, $this->debug);

      $Qout = $Vout * $this->base * $depth / 12.0;

      if ($depth < 0) {
         $depth = 0.0;
      }

      if ($this->debug) {
         $this->logDebug(" D: $depth, Qin: $Qin, Qvel = $Vout, Qout: $Qout, P: $P, I: $I, F: $F <br>\n");
      }

      $this->state['F'] = $F;
      $this->state['depth'] = $depth;
      $this->state['P'] = $P;
      $this->state['I'] = $I;
      $this->state['Vout'] = $Vout;
      $this->state['Qout'] = $Qout;
      $this->state['Qin'] = $Qin;

      $this->postStep();

      $this->totalflow += $Qout * $dt;
      $this->totalp += $P * $dt / 3600.0;

   }
}

class HabitatSuitabilityObject extends modelContainer {
   var $state = array();
   # requires the library lib_equation2.php, which has the class "Equation"
   var $equations = array(); # keyed by statevarname=>equationtext
}

class HabitatSuitabilityObject_NWRC extends HabitatSuitabilityObject {
   // this class contains default values for all parameters used in the set of 
   // NWRC habitat suitability models available from: http://www.nwrc.usgs.gov/wdb/pub/hsi/hsiintro.htm
   // child objects added below this can expect broadcast data pertaining to all of these metrics
   // by over-riding these defaults with inputs from other model components, one can customize the 
   // HSI output to reflect local modeled conditions
   var $hsi_vars = array();
   var $units = 2; // base units 1 - SI, 2 - EE 
   // *******************************
   // ** Water Physical Properties **
   // *******************************
   var $V = 0.0; // velocity ft/sec / m/sec
   var $depth = 0.0; // flow depth ft / m
   // ******************************
   // ** Water Quality Parameters **
   // ******************************
   var $pH = 6.5;
   var $T = 40.0; // Mean Water Temp degrees F / C
   var $dT_bot = 1.0; // ratio of bottom temp to mean temp
   var $dT_sur = 1.0; // ratio of surface temp to mean temp
   var $DO = 5.0; // disolved oxygen mg/L / ppm
   var $salinity = 0; // salinity ppt
   var $tds = 0; // Total Disolved Solids (ppm)
   var $tur = 0; // Turbidity (JTU)
   // *******************************
   // ** Bottom/Substrate Props    **
   // *******************************
   var $substrateclass = 'C';  # A, B, C, D - dominant class of substrate (may be changed by deposition, etc.)
   // A - Silt and Sand ( < 0.2 cm) and/or rooted vegetation
   // B - Pebble (0.2-1.5 cm)
   // C - Gravel, broken rock (1.6-2.0 cm) and boulder/cobble with adequate interstitial space
   // D - Cobble
   // E - Boulder and Bedrock
   var $sub_silt_pct = 0.2; // substrate silt percent
   var $sub_sand_pct = 0.5; // substrate sand percent
   var $sub_pebble_pct = 0.1; // substrate pebble percent
   var $sub_cobble_pct = 0.1; // substrate cobble percent
   var $sub_rock_pct = 0.1; // substrate large rock percent
   // *******************************
   // **   Channel/Segment Props   **
   // *******************************
   // general channel segment properties
   var $slope = 0.01; // slope percent
   var $width = 10.0; // channel base width ft / m
   var $length = 1000.0; // channel section length ft / m
   // channel segment pool, riffle and run properties
   // these properties will be used to calculate variations in temperature and velocity in the 
   // various pool/riffle/run micro-habitats
   // pool properties
   var $pool_pct = 0.1;
   var $pool_depth = 0.5; // depth of pools ft / m - relative to the channel mean
   var $pool_substrate = 'B'; // substrate in pools
   var $pool_class = 'B'; 
   // A - large,deep "deadwater" pools (stream mouths), 
   // B - Moderate below falls or riffle-run areas; 5-30% of bottom obscured by turbulence
   // C - Small or shallow or both, no surface turbulence and little structure
   // riffle properties
   var $riffle_pct = 0.3;
   var $riffle_depth = -0.2; // depth of riffles - relative to the channel mean
   var $riffle_substrate = 'C'; // substrate in riffles
   // run properties
   var $run_pct = 0.5;
   var $run_depth = 0.0; // depth of runs - relative to the channel mean
   var $run_substrate = 'D'; // substrate in runs
   // shallow margin properties (or littoral zone)
   var $margin_pct = 0.1;
   var $margin_depth = 0.0; // depth of runs - relative to the channel mean
   var $margin_substrate = 'A'; // substrate in runs
   // *******************************
   // **    Cover Properties       **
   // *******************************
   var $cover_pct_lg = 0.1; // percent cover from large objects, such as boulders, stumps, crevices
   var $cover_pct_sv = 0.1; // percent cover from vegetation
   var $wetland_pct = 0.0;
   var $shade_pct = 0.5; // percent of stream area shaded
   // *******************************
   // **      Food Availability    **
   // *******************************
   var $zoopl_count = 0; // mean zooplankton count per gal / liter of water
   
   function wake() {
      parent::wake();
      $this->set_HSIvars();
   }
   
   function set_HSIvars() {
      // add the broadcast LISTENER object needed by the NWRC individual species models
      $this->hsi_vars = array('Qin','V','depth','pH','T','dT_bot','dT_sur','DO','salinity','tds','tur','substrateclass','sub_silt_pct','sub_sand_pct','sub_pebble_pct','sub_cobble_pct','sub_rock_pct','slope','width','length','pool_pct','pool_depth','pool_substrate','pool_class','riffle_pct','riffle_depth','riffle_substrate','run_pct','run_depth','run_substrate','margin_pct','margin_depth','margin_substrate','cover_pct_lg','cover_pct_sv','wetland_pct','shade_pct', 'zoopl_count');
   }
   
   function setPropDesc() {
      parent::setPropDesc();
      $this->prop_desc['Qin'] = 'Upstream, or tributary inflows to this stream (cfs).  This will be combined with any local Runoff flows.';
      $this->prop_desc['V'] = 'velocity ft/sec / m/sec';
      $this->prop_desc['depth'] = 'flow depth ft / m';
      $this->prop_desc['pH'] = 'pH';
      $this->prop_desc['T'] = 'Mean Water Temp degrees F / C';
      $this->prop_desc['dT_bot'] = 'ratio of bottom temp to mean temp';
      $this->prop_desc['dT_sur'] = 'ratio of surface temp to mean temp';
      $this->prop_desc['DO = 5.0'] = 'disolved oxygen mg/L / ppm';
      $this->prop_desc['salinity'] = 'salinity ppt';
      $this->prop_desc['tds'] = 'Total Disolved Solids (ppm)';
      $this->prop_desc['tur'] = 'Turbidity (JTU)';
      $this->prop_desc['substrateclass'] = "Dominant class of substrate (may be changed by deposition, etc.)\n A - Silt and Sand ( < 0.2 cm) and/or rooted vegetation\n B - Pebble (0.2-1.5 cm)\n C - Gravel, broken rock (1.6-2.0 cm) and boulder/cobble with adequate interstitial space\n D - Cobble\n E - Boulder and Bedrock";
      $this->prop_desc['sub_silt_pct'] = 'substrate silt percent';
      $this->prop_desc['sub_sand_pct'] = 'substrate sand percent';
      $this->prop_desc['sub_pebble_pct'] = 'substrate pebble percent';
      $this->prop_desc['sub_cobble_pct'] = 'substrate cobble percent';
      $this->prop_desc['sub_rock_pct'] = 'substrate large rock percent';
      $this->prop_desc['slope'] = 'slope percent';
      $this->prop_desc['width'] = 'channel base width ft / m';
      $this->prop_desc['length'] = 'channel section length ft / m';
      $this->prop_desc['pool_pct'] = '';
      $this->prop_desc['pool_depth'] = 'depth of pools ft / m - relative to the channel mean';
      $this->prop_desc['pool_substrate'] = 'substrate in pools';
      $this->prop_desc['pool_class'] = '';
      $this->prop_desc['riffle_pct'] = '';
      $this->prop_desc['riffle_depth'] = 'depth of riffles - relative to the channel mean';
      $this->prop_desc['riffle_substrate'] = 'substrate in riffles';
      $this->prop_desc['run_pct'] = '';
      $this->prop_desc['run_depth'] = 'depth of runs - relative to the channel mean';
      $this->prop_desc['run_substrate'] = 'substrate in runs';
      $this->prop_desc['margin_pct'] = '';
      $this->prop_desc['margin_depth'] = 'depth of runs - relative to the channel mean';
      $this->prop_desc['margin_substrate'] = 'substrate in runs';
      $this->prop_desc['cover_pct_lg'] = 'percent cover from large objects, such as boulders, stumps, crevices';
      $this->prop_desc['cover_pct_sv'] = 'percent cover from vegetation';
      $this->prop_desc['wetland_pct'] = '';
      $this->prop_desc['shade_pct'] = 'percent of stream area shaded';
      $this->prop_desc['zoopl_count'] = 'mean zooplankton count per gal / liter of water';
   }
   
   function create() {
      parent::create();
      $this->set_HSIvars();
      // add the broadcast CASTING object needed by the NWRC individual species models
      $hsi_bc = new broadCastObject;
      $hsi_bc->name = 'Broadcast HSI Parameters';
      $hsi_bc->wake();
      $hsi_bc->local_varname = $this->hsi_vars;
      $hsi_bc->broadcast_varname = $this->hsi_vars;
      $hsi_bc->broadcast_hub = 'child';
      $hsi_bc->broadcast_mode = 'cast';
      if ($this->debug) {
         $this->logDebug("Trying to add broadcast sub-component with values: " . print_r($hsi_bc->local_varname,1) . " <br>");
      }
      $this->addOperator('Broadcast HSI Parameters', $hsi_bc, 0);
      
   }
   
}

class HSI_NWRC_species extends modelObject {
   // these are stand-alone objects, but are best when added as the child of a HabitatSuitabilityObject_NWRC
   // since it has the expected broadcast parameters that are needed by the models from NWRC
   var $hsi_vars = array();
   
   function wake() {
      parent::wake();
      
      $this->set_HSIvars();
   }
   
   function set_HSIvars() {
      $this->hsi_vars = array('Qin','V','depth','pH','T','dT_bot','dT_sur','DO','salinity','tds','tur','substrateclass','sub_silt_pct','sub_sand_pct','sub_pebble_pct','sub_cobble_pct','sub_rock_pct','slope','width','length','pool_pct','pool_depth','pool_substrate','pool_class','riffle_pct','riffle_depth','riffle_substrate','run_pct','run_depth','run_substrate','margin_pct','margin_depth','margin_substrate','cover_pct_lg','cover_pct_sv','wetland_pct','shade_pct', 'zoopl_count');
   }
   
   function create() {
      parent::create();
      $this->set_HSIvars();
      // add the broadcast LISTENER object needed by the NWRC individual species models
      $hsi_bc = new broadCastObject;
      $hsi_bc->name = 'Listen HSI Parameters';
      $hsi_bc->wake();
      $hsi_bc->local_varname = $this->hsi_vars;
      $hsi_bc->broadcast_varname = $this->hsi_vars;
      $hsi_bc->broadcast_hub = 'parent';
      $hsi_bc->broadcast_mode = 'read';
      if ($this->debug) {
         $this->logDebug("Trying to add Listener sub-component with values: " . print_r($hsi_bc->local_varname,1) . " <br>");
      }
      $this->addOperator('Listen HSI Parameters', $hsi_bc, 0);
      
   }
}

class HSI_NWRC_american_shad extends HSI_NWRC_species {
   
   function create() {
      parent::create();
      // add all of the widgets for this, including the base curves in the HSI doc
      
      
      //$this->addOperator('Listen HSI Parameters', $hsi_bc, 0);
      // V1 - mean surface water temp during spawning
      // V2 - mean water velocity during spawning
      // V3 - mean surface water temp during egg larval development
      // V4 - mean near-bottom water temp during winter and spring
      // V5 - percentage of areas supporting emergent and/or submerged vegetation
   }
   
   function step() {
      parent::step();
      // perform timestep relevant calculations
      
   }
   
   function computeHSI() {
      // compute the actual value of the HSI for this, it may only occur at certain times,
      // based on the restrictions for evaluation of each individual factor
      // or it might be computed every step, but with certain factors only being updated
      // when the simulation is at the appropriate time to evaluate
      
   }
      
}


class PopulationGenerationObject extends modelContainer {
   # specifically for use with "age-class" type of entities, clones itself whjen reproducing, and sets properties on child obejct
   # also tracks all children, keeping a cumulative population of all descendants

   var $parent_object = -1;
   var $log2parent = 1; # sends logging info back to parent

   var $base_name = ''; # base of name for object children

   var $age_resolution = 'year'; # year, month, day, hour, minute, second
   var $birth_date = ''; # date of birth in age resolution (year = YYYY-01-01 00:00:00, month = YYYY-MM-01-01 00:00:00, day = YYYY-MM-DD 00:00:00, etc.)
   var $birth_class = '' ; # contains the short_format date variable
   var $birth_seconds = ''; # DOB in seconds since the Epoch
   var $age = 0.0;
   var $population = 0.0; # population of this generation (set as initial population in model interface)
   var $child_pop = 0.0; # population of descendant generations
   var $total_pop = 0.0; # population of this generation and all descendants (population + child_pop)

   # mortality
   var $death_rate = 1.0; # rate is calculated every 1.0 age resolution units
   var $max_age = -1; # units of age resolution, -1 means no explicit max age
   var $mortality_pop = 0.0; # starts with zero
   var $alive = 1;

   # reproduction
   var $birth_rate = 1.0; # if population only reproduces during certain times of the year, can sub-class
                          # this with a lookup table that is only active at the spawning times
   var $birth_pop = 0.0; # starts with zero
   var $birth_frequency = 1; # units of age resolution
   var $min_birth_age = -1; # units of age resolution, -1 means no lower reproduction restriction
   var $max_birth_age = -1; # units of age resolution, -1 means no upper reproduction restriction

   # record of birth classes
   var $birthclasses = array();

   function init() {

      parent::init();

      # explicitly set up column types for my properties
      $this->dbcolumntypes['child_pop'] = 'float8';
      $this->dbcolumntypes['total_pop'] = 'float8';
      $this->dbcolumntypes['birth_rate'] = 'float8';
      $this->dbcolumntypes['mortality_pop'] = 'float8';
      $this->dbcolumntypes['max_age'] = 'float8';
      $this->dbcolumntypes['death_rate'] = 'float8';
      $this->dbcolumntypes['population'] = 'float8';
      $this->dbcolumntypes['age'] = 'float8';
      $this->dbcolumntypes['birth_seconds'] = 'bigint';
      $this->dbcolumntypes['birth_date'] = 'timestamp';
      $this->dbcolumntypes['birth_frequency'] = 'float8';
      $this->dbcolumntypes['refract_interval'] = 'float8';

      if (strlen($this->base_name) == 0) {
         $this->base_name = $this->name;
      }

      $this->birthclasses = array();

      switch($this->age_resolution) {
         case 'year':
            $this->age_interval = 365.25 * 24.0 * 60.0 * 60.0;
            $this->birth_format = 'Y-01-01 00:00:00';
            $this->short_format = 'Y';
         break;

         case 'month':
            $this->age_interval = 30.4375 * 24.0 * 60.0 * 60.0;
            $this->birth_format = 'Y-m-01 00:00:00';
            $this->short_format = 'm';
         break;

         case 'day':
            $this->age_interval = 24.0 * 60.0 * 60.0;
            $this->birth_format = 'Y-m-d 00:00:00';
            $this->short_format = 'd';
         break;

         case 'hour':
            $this->age_interval = 60.0 * 60.0;
            $this->birth_format = 'Y-m-d H:i:00';
            $this->short_format = 'H';
         break;

         case 'second':
            $this->age_interval = 60.0;
            $this->birth_format = 'Y-m-d H:i:s';
            $this->short_format = 'i';
         break;

         default:
            $this->age_interval = 365.25 * 24.0 * 60.0 * 60.0;
            $this->birth_format = 'Y-m-d H:i:s';
            $this->short_format = 's';
         break;

      }
   }

   function finish() {

      # iterate through each stored component, and delete any child population objects
      #error_log("Finishing object " . $this->name);
      $this->compexeclist = array();
      foreach ($this->components as $thiscomp) {
         # evaluate the equation
         if ($this->debug) {
            $this->logDebug("Checking for child process:" . $thiscomp->name . "<br>\n");
         }
         # set all required inputs for the equation
         if ( !(get_class($thiscomp) == get_class($this)) ) {
            array_push($this->compexeclist, $thiscomp->componentid);
         }

      }
      #error_log("Including components for Finishing: " . print_r($this->compexeclist,1));
      parent::finish();

   }




   function execComponents() {

      if ($this->debug) {
         $this->logDebug("Culling dead components from $this->name.<br>\n");
         #error_log("Going through components for $this->name.");
      }

      # iterate through each equation stored in this object
      $live_list = array();

      foreach ($this->compexeclist as $thiscomp) {
         # evaluate the equation
         if ($this->debug) {
            $this->logDebug("Checking $thiscomp<br>\n");
            #error_log("Executing $thiscomp .");
         }
         # set all required inputs for the equation
         if ($this->components[$thiscomp]->alive) {
            array_push($live_list, $thiscomp);
            if ($this->debug) {
               $this->logDebug("Keeping $thiscomp \n<br>");
            }
         } else {
            unset($this->components[$thiscomp]);
            if ($this->debug) {
               $this->logDebug("Destroying $thiscomp \n<br>");
            }
         }
      }
      $this->compexeclist = $live_list;

      # now, we are cleaned up, so go ahead and execute children
      parent::execComponents();
   }

   function setState() {
      parent::setState();

      $this->state['name'] = $this->name;
      $this->state['population'] = $this->population;
      $this->state['child_pop'] = $this->child_pop;
      $this->state['birth_pop'] = $this->birth_pop;
      $this->state['birth_rate'] = $this->birth_rate;
      $this->state['death_rate'] = $this->death_rate;
      $this->state['birth_date'] = $this->birth_date;
      $this->state['mortality_pop'] = $this->mortality_pop;
      $this->state['age'] = $this->age;
      $this->state['total_pop'] = $this->population;
      $this->state['birth_frequency'] = $this->birth_frequency;
      $this->state['refract_interval'] = 0.0;

   }

   function setBirthDate() {
      # set my birth date to NOW!!
      $this->birth_date = $this->timer->thistime->format($this->birth_format);
      $this->last_birth = $this->timer->timeseconds;
      $this->birth_seconds = $this->timer->timeseconds;
      $this->birth_class = $this->timer->thistime->format($this->short_format);
      $this->state['birth_date'] = $this->birth_date;

      if ($this->debug) {
         $this->logDebug("Birth Date Set to: " . $this->birth_date . "\n<br>");
         #error_log("Birth Date Set to: " . $this->birth_date);
      }
   }

   function setParent($parent_object) {
      $this->parent_object = $parent_object;
   }

   function addCloneComponent($clone_id) {
      $this->addComponent(clone $this, $clone_id);
   }

   function getValue($thistime, $thisvar) {
      # currrently, does nothing with the time, assumes that the input time is
      # equal to the current modeled time and returns the current value
      if ($this->debug) {
         $sv = $this->state;
         if (isset($sv['the_geom'])) {
            $sv['the_geom'] = 'HIDDEN';
         }
         $this->logDebug("Variable $thisvar requested from $this->name at step #" . $this->timer->steps . ", returning " . $this->state[$thisvar] . " from " . print_r($sv,1) );
      }
      return $this->state[$thisvar];
   }

   function step() {
      // all step methods MUST call preStep(),execProcessors(), postStep()
      $this->preStep();
      if ($this->debug) {
         $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
      }
      if (strlen($this->birth_date) == 0) {
         $this->setBirthDate();
      }
      # execute children
      $this->execComponents();
      # calculate age
      # subtract current date from birth_date, then convert into units of this objects age_resolution
      $time_seconds = $this->timer->timeseconds;
      $age_seconds = $time_seconds - $this->birth_seconds;
      $refract_seconds = $time_seconds - $this->last_birth;
      $age = $age_seconds / $this->age_interval;
      $refract_interval = $refract_seconds / $this->age_interval;
      # now execute any operations on this object
      $this->execProcessors();

      # get variables from state container, in case any are altered by internal processors
      $birth_rate = $this->state['birth_rate'];
      $death_rate = $this->state['death_rate'];
      $population = $this->state['population'];
      $mortality_pop = $this->state['mortality_pop'];
      $child_pop = $this->state['child_pop'];

      $birth_pop = 0; # assume no birthing, check later for birth_pop set in state by internal processor which overrides the standard method

      # handle death/die-off
      # first zero out population if it has exceeded max age (if set)
      if ( ($this->max_age > 0) and ($age > $this->max_age) ) {
         $this->population = 0;
         $population = 0;
         $this->alive = 0;
         if ($this->debug) {
            $this->logDebug("Max Age of " . $this->max_age . " exceeded by " . $this->name . " ( age = " . $age . " )\n<br>");
            $this->logDebug("Calculated from age in seconds " . $age_seconds . " divided by age interval " . $this->age_interval . " at time " . $time_seconds . " ( birth seconds = " . $this->birth_seconds . " )\n<br>");
            #error_log("Max Age of " . $this->max_age . " exceeded by " . $this->name . " ( age = " . $age . " )\n<br>");
            #error_log("Calculated from age in seconds " . $age_seconds . " divided by age interval " . $this->age_interval . " at time " . $time_seconds . " ( birth seconds = " . $this->birth_seconds . " )\n<br>");
         }
      }

      if ($population <= 0) {
         $this->alive = 0;
      }

      $procvars = array_keys($this->processors);
      # death is handled on a continual basis, so we divide the timestep by the age_interval to scale the death rate
      if (!in_array('mortality_pop', $procvars)) {
         # use embedded mortality calculation
         $mortality_pop = ($this->timer->dt / $this->age_interval) * ($death_rate) * $population;
      }

      # handle birth
      # done differently than death, assumed that their is a refractory period after reproduction, this is so that
      # we are not continually spawning young. Could handle this differently, by storing a link to a child population,
      # referenced by year-class (or whatever the age_interval is ), and then adding new births into that population
      if ( ($birth_rate > 0) and ($this->alive) ) {
         # check for minimum/maximum birth ages
         $old_enough = 0;
         $young_enough = 0;
         $rested_enough = 0;

         if ($this->min_birth_age <= $age) {
            $old_enough = 1;
         }
         if ( ($this->max_birth_age >= $age) or ($this->max_birth_age == -1) ) {
            $young_enough = 1;
         }
         # check for reproductive refractory period
         if ( $refract_interval >= $this->birth_frequency ) {
            $rested_enough = 1;
         }

         $can_reproduce = $old_enough * $young_enough * $rested_enough;

         if ($can_reproduce) {
            # use embedded birth calculation
            $birth_pop = $birth_rate * $population;
         }
      }

      # check if birth_pop is superceded by an external calculation
      if ( in_array('birth_pop', $procvars ) ) {
         $birth_pop = $this->state['birth_pop'];
      }

      if ($this->debug) {
         $this->logDebug("Birth Pop Calculated on $this->name, pop = " . $birth_pop . ", alive? " . $this->alive . " at " . $this->timer->timestamp . "\n<br>");
         #error_log("Birth Pop Calculated on $this->name, pop = " . $birth_pop . ", alive? " . $this->alive . " at " . $this->timer->timestamp . "\n<br>");
      }

      if ( ($birth_pop > 0) and ($this->alive) ) {
         $this->addBirthClass($birth_pop);
      }



      $population = $population - $mortality_pop;
      # do not include birth pop here, because this is figured elsewhere
      if ($population < 0) {
         $population = 0;
      }

      # now, set state variables
      $this->state['birth_rate'] = $birth_rate;
      $this->state['death_rate'] = $death_rate;
      $this->state['population'] = $population;
      $this->state['mortality_pop'] = $mortality_pop;
      $this->state['birth_pop'] = $birth_pop;
      $this->state['age'] = $age;
      $this->state['refract_interval'] = $refract_interval;
      if ($this->debug) {
         $this->logDebug("Calculating total population from child population, " . $child_pop . " and parent pop, " . $population . "\n<br>");
      }
      $total_pop = $population + $child_pop;
      $this->state['total_pop'] = $total_pop;

      #error_log("Total pop on $this->name at step #" . $this->timer->steps . " = " . $this->state['total_pop'] . "<br>");

      # log my values - use the pass up method so that any
      $this->state['thisdate'] = $this->timer->thistime->format('Y-m-d');
      $this->state['time'] = $this->timer->thistime->format('r');
      $this->state['year'] = $this->timer->thistime->format('Y');
      $this->state['month'] = $this->timer->thistime->format('m');
      $this->state['day'] = $this->timer->thistime->format('d');
      if (is_object($this->parent_object)) {
         $this->parent_object->postStep($this->state);
      } else {
         $this->postStep();
      }

   }

   function addBirthClass($birth_pop) {
      if ($this->debug) {
         $this->logDebug("$this->name requests adding $birth_pop \n<br>");
         #error_log("$this->name requests adding $birth_pop \n<br>");
      }
      $birthdate = $this->timer->thistime->format($this->birth_format);
      if (is_object($this->parent_object)) {
         $new_child = $this->parent_object->getBirthClass($birthdate);
      } else {
         $new_child = $this->getBirthClass($birthdate);
      }
      #$new_child = clone($this);
      if ($this->debug) {
         $this->logDebug("Adding $birth_pop to Birth Class $birthdate, " . $new_child->state['population'] . " + " .  + $birth_pop . "\n<br>");
         #error_log("Adding $birth_pop to Birth Class $birthdate, " . $new_child->state['population'] . " + " .  + $birth_pop . "\n<br>");
      }
      $new_child->setStateVar('population', $new_child->state['population'] + $birth_pop);
      $new_child->setStateVar('total_pop', $new_child->state['total_pop'] + $birth_pop);
      if ($this->debug) {
         $this->logDebug($new_child->name . " Updated, population = " . $new_child->state['population'] . "\n<br>");
         #error_log($new_child->name . " Updated, population = " . $new_child->state['population'] . "\n<br>");
      }

      # stash the time of birth for refractory period calculation
      $this->last_birth = $this->timer->timeseconds;
   }

   function getBirthClass($birthdate) {
      if (is_object($this->parent_object)) {
         # pass the request up the line
         $bcs = $this->parent_object->getBirthClass($birthdate);
         return $bcs;
      } else {
         $dbg_obj = $this->components['Population: Shad_2007-01-04 00:00:00'];

         if ($this->debug) {
            $this->logDebug("Checking State of $dbg_obj->name , population = " . $dbg_obj->state['population'] . "<br>\n");
            $this->logDebug("Birth Class $birthdate Requested\n<br>");
         }
         # if we are at the top object, handle the request
         $classname = $this->base_name . "_" . $birthdate;
         if (in_array($classname, array_keys($this->components))) {
            if ($this->debug) {
               $this->logDebug("Birth Class $birthdate Exists in " . $this->components[$classname]->name . "\n<br>");
               #error_log("Birth Class $birthdate Exists in " . $this->components[$classname]->name . "\n<br>");
            }
            return $this->components[$classname];
         } else {
            if ($this->debug) {
               $this->logDebug("Creating Birth Class $birthdate\n<br>");
               #error_log("Creating Birth Class $birthdate\n<br>");
            }
            # need to create a new object for this birthclass
            $this->addCloneComponent($this->base_name . "_" . $birthdate);
            $new_child = $this->components[$this->base_name . "_" . $birthdate];
            if ($this->debug) {
               $this->logDebug("Checking State of $dbg_obj->name , population = " . $dbg_obj->state['population'] . "<br>\n");
            }
            $new_child->setProp('base_name', $this->base_name);
            $new_inputs = clone $this->inputs;
            $new_child->setProp('inputs', $new_inputs);
            $new_child->setProp('inputs', array());
            $new_child->setProp('components', array());
            $new_child->init();
            $new_child->setProp('state', array());
            $new_child->setSimTimer($this->timer);
            $new_child->setBirthDate();
            #$new_child->setDebug(0);
            $new_child->setProp('population', 0);
            $new_child->setProp('total_pop', 0);
            $new_child->setProp('child_pop', 0);
            $new_child->setProp('alive', 1);
            $new_child->setProp('name', $this->base_name . " " . $this->birth_class . "-" . $new_child->birth_class);
            # set a unique compid for this child
            $new_child->setProp('componentid', $this->base_name . "_" . $birthdate);
            if ($this->debug) {
               $arr = get_class_methods(get_class($new_child));
               $this->logDebug("Child added to $this->name with methods "  . print_r($arr,1) . "\n<br>");
               #error_log("Child " . $new_child->name . " added to $this->name " );
            }
            if (is_object($this->parent_object)) {
               $new_child->setParent($this->parent_object);
            } else {
               $new_child->setParent($this);
            }
            if ($this->debug) {
               $this->logDebug("Checking State of $dbg_obj->name , population = " . $dbg_obj->state['population'] . "<br>\n");
            }
            $new_child->setState();
            # clear inputs for total pop on child object to avoid redundancy
            $this->addInput('child_pop', 'total_pop', $new_child);
            $this->birthclasses[$birthdate] = $new_child;
            if ($this->debug) {
               $this->logDebug("$new_child->name added to birth Class $birthdate \n<br>");
               #error_log("$new_child->name added to birth Class $birthdate \n<br>");
               $this->logDebug("Checking State of $dbg_obj->name , population = " . $dbg_obj->state['population'] . "<br>\n");
            }

            return $new_child;
         }
      }
   }

}

class stockComponent extends modelObject {

   var $inflows = array();
   var $outflows = array();
   var $serialist = 'inflows,outflows';
   var $loggable = 1; // can log the value in a data table

   function setState() {
      parent::setState();
   }

   function step() {
      parent::step();
      # we will later use all inputs to accumulate and decrement stocks and flows, but for now, we will
      # just have it do nothing
   }

   function sleep() {
      parent::sleep();
      # we will later use all inputs to accumulate and decrement stocks and flows, but for now, we will
      # just have it do nothing
   }

   function finish() {
      parent::finish();
   }
}

class reportObject extends modelObject {

   var $imgurl = '';
   var $cache_log = 0; # store variable state log in an external file?  Defaults to 1 for graph and report objects, 0 for others
   # requires lib_plot.php to produce graphic output

   function setState() {
      parent::setState();
   }

   function step() {
      parent::step();
      if ($this->timer->finished) {
         # simulation finished, go ahead and generate any graphs
         $this->log2file();
      }
   }

   function finish() {
      parent::finish();
   }
}

class dataConnectionObject extends timeSeriesInput {

   var $conntype = 1; # 1 - postgis, 2 - ODBC, 3 - Oracle, 4 - WFS, 5 - object to object query, 6 - XML, 7 - RSS
   var $dbobject = -1; # need to keep this seperate from the listobject, which is used by the system for logging, etc.
   var $host = 'localhost';
   var $dbname = 'wsp';
   var $port = 5432;
   var $single_datecol = 1;
   var $datecolumn = ''; # use this if $single_datecol = true
   var $yearcolumn = '';
   var $monthcolumn = ''; # otherwise, need to have a column with the year, month, and day
   var $daycolumn = '';
   var $initval = 0;
   var $username = 'wsp_ro';
   var $password = 'q_only';
   var $table = ''; # selected table/dataset from the data source
   var $alltables = array();
   var $localprocs = array();
   var $lat_col = '';
   var $lon_col = '';
   var $restrict_spatial = 0;
   var $area_m = 0.0; // the area of the object geometry if this is set
   var $lasttable = ''; # last table value, for reference
   var $bbox = '';
   var $sql_query = ''; # this would be for a user defined query.  This can be dicey, since it could be a security risk if
                        # the user had write privileges.
   var $public_sql = ''; # this is the query that we run to create the publicly visible columns
   var $remote_query = '';
   var $transform_query = ''; // this is a test to see if we can perform the who shebang on the remote server
   // if it is a postgresql connection, we should be able to do this (if it has postgis that is)
   var $groupcols = ''; # columns to use in grouping
   var $scratchtable = ''; # table for storing local copy of raw query data
   var $schema = ''; # postgis specific schema for temp tables
   var $force_refresh = 1;
   var $data_refreshed = 0;
   var $rawdbfile = ''; # file name to cache raw data values to avoid continuous querying
   var $max_memory_values = 1000;
   var $raw_columns = ''; // place to stash columns from raw query
   var $raw_column_types = ''; // place to stash columns types from src database
   var $log2db = 0; // this saves processing time immensely if this is turned off
   var $groupremote = 1; // whether to use the "performGroupedRemoteQueries()" function, which does transforms on the server side to reduce the amount of local data storage and temp table creation overhead, which can be substantial in the case of things like the CBP data

   function init() {
      $this->localprocs = array();
      parent::init();
      # get list of available tables and colmns
      $this->setupDBConn();

      # all columns in the raw data query are assumed to be private data
      # in order to make them public, we have to explicitly declare them/process them with a
      # component 'dataConnectionTransform'.  This will allow us to do summaries, rename columns, whatever
      # it stores the results in $this->public_sql
      $this->preProcess();
      # now that we have our raw query, restrict spatial extent if desired
      $theserecs = array();
      $cache_failed = 0;

      # now, get the RAW data for this query and store it in the tstable
      if ($this->debug) {
         $this->logDebug("Getting Data<br>\n");
      }
      $this->getData();
      if ($this->debug) {
         $this->logDebug("Data retrieved<br>\n");
      }

      # if an input for scratchtable is set, this means that we are using a remote query, and will do no querying ourselves
      if (isset($this->inputs['scratchtable'])) {
         if ($this->debug) {
            $this->logDebug("Trying to gain data from remote connection.<br>\n");
         }
         if (is_object($this->inputs['scratchtable'][0]['object'])) {
            $theserecs = $this->getRemoteData();
         }
      } else {
         if ( ($this->conntype == 1) and ($this->groupremote)) { 
            // try the new groupedremoteQuery function
            if ($this->debug) {
               $this->logDebug("Performing Grouped Query<br>\n");
            }
            $theserecs = $this->performGroupedRemoteQueries();
         } else {
            if ($this->debug) {
               $this->logDebug("Setting local table<br>\n");
            }
            $this->setupLocalTable($this->dbobject->queryrecords);
            if ($this->debug) {
               $this->logDebug("Setting geometry column<br>\n");
            }
            $this->setupGeometryColumn();
            if ($this->debug) {
               $this->logDebug("Performing local queries<br>\n");
            }
            $theserecs = $this->performLocalQueries();
            # blank out query records to save memory
            $this->dbobject->queryrecords = array();
         }
      }

      # and add them to our timeSeries values, otherwise, there are no time series values added
      $this->addQueryData($theserecs);
      ksort($this->tsvalues);
      // need to use our parent objects time series caching functions if they exist, since they take place in the 
      // parent call to init(), which takes place BEFORE we load our data
         
      if ( (count($this->tsvalues) > $this->max_memory_values) and ($this->max_memory_values > 0)) {
         //error_log("tsvalues cacheing enabled on $this->name");
         $this->tsvalues2listobject();
         $this->getCurrentDataSlice();
         $mem_use = (memory_get_usage(true) / (1024.0 * 1024.0));
         $mem_use_malloc = (memory_get_usage(false) / (1024.0 * 1024.0));
         //error_log("Memory Use after caching timeseries data on $this->name = $mem_use ( $mem_use_malloc )<br>\n");
      }
      
      $this->getGeometryArea();
      //$this->closeDBConns();
   }


   function wake() {
      $this->localprocs = array();
      $this->setupDBConn(1);
      $this->raw_columns = array();
      parent::wake();
      $this->prop_desc['area_m'] = 'Area of this objects geometry in square meters.';
   }

   function finish() {
      #
      parent::finish();
      # now, if we have defined an X/Y column, we go ahead and add the geometry
      if (is_object($this->listobject)) {
         # now, clean up
         if ($this->listobject->tableExists($scratchname)) {
            $this->listobject->querystring = " DROP TABLE $scratchname ";
            if ($this->debug) {
               $this->logDebug($this->listobject->querystring . " ;<br>");
            }
            $this->listobject->performQuery();
         }

         if ( in_array($this->lat_col, $this->columns) and in_array($this->lon_col, $this->columns) ) {
            # now, if we have defined an X/Y column, we have to clean up the geomtry table
            $this->listobject->querystring = " SELECT nspname FROM pg_namespace WHERE oid = pg_my_temp_schema() ";
            if ($this->debug) {
               $this->logDebug($this->listobject->querystring . " ;<br>");
            }
            $this->listobject->performQuery();
            $schema = $this->listobject->getRecordValue(1,'nspname');
            $this->listobject->querystring = " select dropGeometryColumn ( '$schema', '$this->scratchtable', 'the_geom') ";
            if ($this->debug) {
               $this->logDebug($this->listobject->querystring . " ;<br>");
            }
            $this->listobject->performQuery();
         }
      }

   }

   function sleep() {
      parent::sleep();
      $this->closeDBConns();
      $this->raw_columns = '';
      $this->raw_column_types = '';
   }
   
   function closeDBConns() {
      // close all non-needed connections
      
      switch ($this->conntype) {
         case 1:
         # postgis 
         pg_close($this->dbobject->dbconn);
         break;
         case 2:
         # odbc will call all tables
         odbc_close($this->dbobject->dbconn);
         break;
         case 3:
         # Oracle will call embedded table methods
         break;
         default:
         # XML Feed - this will not be done here, sub-classes by xmlDataConnection object class
         # do nothing
         break;
      }
      $this->dbobject = NULL;
   }

   function setState() {
      parent::setState();
      # puts columns from query into state array
      $this->getTablesColumns();
   }

   function getExtentWKT() {
      # grabs the extnet of this geometry
      if (is_object($this->listobject)) {
         $this->listobject->querystring = "  select st_asText(st_extent( st_geomFromText('$this->the_geom',4326) )) as extent ";
         //error_log($this->listobject->querystring);
         $this->listobject->performQuery();
         return $this->listobject->getRecordValue(1,'extent');
      } else {
         //error_log("Listobject is not an object");
      }
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();
      foreach ($this->localprocs as $thiscol) {
         array_push($publix, $thiscol);
      }

      array_push($publix, 'the_geom');
      $this->bbox = $this->getExtentWKT();
      //error_log("BBOX set to $this->bbox");
      array_push($publix, 'bbox');
      array_push($publix, 'area_m');

      return $publix;
   }

   function getPrivateProps() {
      # gets all viewable variables in the local context only.  These are the column names
      $privitz = $this->localprocs;

      return $privitz;
   }

   function showHTMLInfo() {
      $htmlinfo = parent::showHTMLInfo();

      # puts columns from query into state array
      #$this->getData();
      #$htmlinfo .= 'Query: ' . $this->dbobject->querystring;
      #$htmlinfo .= print_r($this->dbobject->queryrecords,1);
      #$htmlinfo .= print_r($this->state,1);
      $htmlinfo .= "<hr>$this->name query = <br>" . $this->subLocalProperties($this->sql_query);
      return $htmlinfo;
   }

   function preProcess() {
      parent::preProcess();
      if ($this->debug) {
         $this->logDebug("$this->name calling preProcess() <br>\n");
      }
      # if we have defined the_geom as an input, go ahead and grab that ahead of time, since we will want to have it
      # when we call getData.
      # this will be unreliable unless the link to the_geom is a parent, otherwise, the init() method may be called 
      # before the init() method of whatever object we link from.
      if (in_array('the_geom', array_keys($this->inputs))) {
         if ($this->debug) {
            $this->logDebug("Geometry property the_geom to be loaded from remote input <br>\n");
         }
         foreach ($this->inputs['the_geom'] as $thisin) {
            if (is_object($thisin['object'])) {
               $pname = $thisin['param'];
               $inobject = $thisin['object'];
               //error_log("I would like to retrieve $pname from another object ");
               $thistime = ''; //currently does nothing since we do not permit scanning for a particular time period
               //$this->the_geom = $inobject->getValue($thistime, $pname);
               $this->the_geom = $inobject->getProp($pname);
               if ($this->debug) {
                  $this->logDebug("*****************************************<br>\n");
                  $this->logDebug("At Time = " . time() . " Geometry on $this->name copied from " . $inobject->name . " ($pname) set to: " . substr($this->the_geom,0,64)  . "<br>\n");
                  $this->logDebug("State Vars: " . print_r($inobject->state,1)  . "<br>\n");
                  $this->logDebug("*****************************************<br>\n");
               }
            }
         }
      }
      
      list($public_cols, $group_cols) = $this->chooseLocalColumns();
      if ($this->debug) {
         $this->logDebug("*****************************************<br>\n");
         $this->logDebug("shooseLocalColumns() returned public_cols = $public_cols, group_cols = $group_cols<br>\n");
         $this->logDebug("*****************************************<br>\n");
      }
      $this->public_sql .= $public_cols;
      //$this->groupcols .= $group_cols;
      $this->groupcols = $group_cols;
      

   }
   
   function chooseLocalColumns() {
      // this is kind of a legacy, when we didn't want to allow access to all columns,
      // this will stay here for the base object, for the time being, but will be sub-classed 
      // by all children, under the assumption that if a column is brought in by some query, 
      // then it must be desired to be used.  If not, there would be no reason to query it 
      // in the first place
      $public_cols = '';
      $group_cols = '';
      $pdel = '';
      $gdel = '';
      if ($this->single_datecol == 1) {
         $public_cols = "\"$this->datecolumn\"";
         $group_cols = "\"$this->datecolumn\"";
         $pdel = ' ,';
         $gdel = ' ,';
      } else {
         $public_cols = "\"$this->yearcolumn\", \"$this->monthcolumn\", \"$this->daycolumn\"";
         $group_cols = "\"$this->yearcolumn\", \"$this->monthcolumn\", \"$this->daycolumn\"";
         $pdel = ' ,';
         $gdel = ' ,';
      }
      error_log("Created initial Public_cols as $public_cols  ");
      $tcols = 0;
      foreach ($this->processors as $thisproc) {
         // looks for dataconnectiontransforms
         
         if (is_object($thisproc)) {
            if (get_class($thisproc) == 'dataConnectionTransfornm') {
               if ( isset($thisproc->mysql) and (strlen(isset($thisproc->mysql)) > 0) ) {
                  $public_cols .= $pdel . $thisproc->mysql . " " . $this->listobject->alias_string . " \"$thisproc->name\"";
                  if (strlen($thisproc->groupcol) > 0) {
                     $group_cols .= $gdel . "\"$thisproc->groupcol\"";
                  }
                  if ($this->debug) {
                     $this->logDebug("Found additional public query column: $thisproc->name ; <br>");
                  }
                  $tcols++;
               }
            } 
         }
         
      }# the parent routine calls the pre-cprocess method on all children, now iterate through looking for dataConnTransform children
      if ($tcols == 0) {
         // we have no transforms, so we need to grab the raw columns
         //$public_cols .= implode(',', $this->raw_columns);
         if ($this->debug) {
            $this->logDebug("Setting columns public_cols = $public_cols and raw columns " . print_r( $this->raw_columns,1) . " <br>");
         }
         $public_cols = implode(',', array_unique(array_merge(explode(',',$public_cols),$this->raw_columns)));
         $group_cols = '';
         //error_log("Created $public_cols from " . print_r( array_unique(array_merge(explode(',',$public_cols),$this->raw_columns)),1));
      }
      return array($public_cols, $group_cols);
   }

   function setupDBConn($refresh = 0) {
      # set up the appropriate database connection
      #if ( (!is_object($this->dbobject)) or $refresh) {
         #error_log("Setting up DB Connection ");
         if ($this->debug) {
            $this->logDebug("DB Conn params: $this->host port=$this->port dbname=$this->dbname user=$this->username password=xxxxx <br>\n");
         }
         switch ($this->conntype) {
            case 1:
            # postgis will call all tables
            $this->dbobject = new pgsql_QueryObject;
            $this->dbobject->dbconn = pg_connect("host=$this->host port=$this->port dbname=$this->dbname user=$this->username password=$this->password");
            #error_log("PG Connection: host=$this->host port=$this->port dbname=$this->dbname user=$this->username password=$this->password <br>");
            break;

            case 2:
            # postgis will call all tables
            $this->dbobject = new odbc_QueryObject;
            $this->dbobject->dbconn = odbc_connect( $this->dbname, $this->username, $this->password);
            $tbls = $this->dbobject->getTables();
            if ($this->debug) {
               $this->logDebug("Tables: " . print_r($tbls, 1) . "<br>");
            }
            #error_log("Tables: " . print_r($tbls, 1) . "<br>");
            break;

            case 3:
            # Oracle will call embedded table methods
            $this->dbobject = new oci8_QueryObject;
            if (function_exists('oci_connect')) {
               $this->dbobject->dbconn = oci_connect("$this->username", "$this->password", "$this->dbname");
            }

            break;

            case 4:
            # WFS will require a getCapabilities call.  Don't yet know how to parse that.
            break;

            case 5:
            # Shared data connection, uses the data gathered by another dataConnection object as the source by
            # accessing its local table
            break;

            case 6:
            # XML Feed - this will not be done here, sub-classes by xmlDataConnection object class
            //$this->dbobject = new rss_listObject;
            break;

            case 7:
            # RSS Feed - this will not be done here, sub-classes by RSSDataConnection object class
            //$this->dbobject = new rss_listObject;
            break;

         }
      #}
   }

   function getData() {
      # run this step, unless we have a shared data connection, in which case we use the shared objects data
      $this->data_refreshed = 0;
      $this->logDebug("Getting Data");
      if ($this->debug) {
         $this->logDebug("Getting Data");
      }
      if (is_object($this->dbobject) and (strlen($this->sql_query) > 0) ) {
         // check to see if query has any wildcards in it that we should sub for
         // this should have a select box, so that we don't inadvertently goof it up
         $this->dbobject->querystring = "select * from ( " . $this->subLocalProperties($this->sql_query);
         $this->dbobject->querystring .= ") as bar ";
         $this->dbobject->querystring .= " where (1 = 1) ";
         if ($this->restrict_spatial and (strlen(trim($this->the_geom)) > 0) ) {
            $this->dbobject->querystring .= " and within( ";
            $this->dbobject->querystring .= " st_geomFromText('POINT(' || $this->lon_col || ' ' || $this->lat_col || ' )', 4326 )";
            $this->dbobject->querystring .= " , st_geomFromText('$this->the_geom',4326) ) ";
         }
         if ($this->single_datecol and is_object($this->timer) ) {
            $this->logDebug("Adding Timespan Restriction");
            $sdate = $this->timer->thistime->format('Y-m-d');
            $ndate = $this->timer->endtime->format('Y-m-d');
            $this->dbobject->querystring .= " and $this->datecolumn >= '$sdate' ";
            $this->dbobject->querystring .= " and $this->datecolumn <= '$ndate' ";
         }
         
         $this->remote_query = $this->dbobject->querystring;
         //error_log("Query :" . $this->dbobject->querystring);
         if ($this->debug) {
            $this->logDebug("Query defined: " . $this->dbobject->querystring . "; <br>");
         }
         $cache_failed = 1; // set this to failed, regardless, 
                            //then we set it to 0 if we get cached data, otherwise, query anew
         
         //error_log("Checking Cache setting: Cache setting: $this->cache_ts <br>");
         
         if ( ($this->cache_ts == 1) and ($this->force_refresh <> 1) ) {
            //error_log("Cache setting: $this->cache_ts , Refresh setting: $this->force_refresh , Looking for raw values in cache file <br>");
            if ($this->debug) {
               $this->logDebug("Looking for raw values in cache file <br>");
            }
            # stash these in a file
            if (strlen($this->rawdbfile) == 0) {
               $this->rawdbfile = 'rawdbvalues.' . $this->componentid . '.csv';
            }
            $filename = $this->outdir . '/' . $this->rawdbfile;
            $theserecs = readDelimitedFile($filename, $this->translateDelim($this->delimiter), 1);
            if (count($theserecs) > 0) {
               $cache_failed = 0;
               $this->dbobject->queryrecords = $theserecs;
               if ($this->debug) {
                  $this->logDebug(count($theserecs) . " raw values found in cache file <br>");
               }
            }
         }
         if ($cache_failed == 1) {
            if ($this->debug) {
               $this->logDebug(" Cache retrieval failed/disabled. Performing query.<br>");
            }
            //error_log(" Cache retrieval failed/disabled. Performing query.<br>");
            $this->dbobject->performQuery();
            $this->data_refreshed = 1;
         }
      } else {
         if ($this->debug) {
            $isobj = is_object($this->dbobject);
            $querylen = strlen($this->sql_query);
            $this->logDebug("Data Retrieval not performed, Is dbobject set? ($isobj) - Query length: $querylen<br>");
         }
      }

      if ($this->cache_ts == 1 and $this->data_refreshed) {
         //error_log("Putting data in cachde file<br>");
         # stash these in a file
         $this->rawDBValues2file($this->dbobject->queryrecords);
      }
   }
   
   function getGeometryArea() {
      
      if ( strlen(trim($this->the_geom)) > 0 ) {
         If ($this->debug) {
            $this->logdebug(" Querying geometry area for this watershed.<br>");
         }
         // get the area of this geometry for later use
         $this->dbobject->querystring = " select  st_area2d(  st_transform( st_geomFromText('$this->the_geom',4326),26918) ) ";
         $this->dbobject->performQuery();
         if (count($this->dbobject->queryrecords) > 0) {
            $this->area_m = $this->dbobject->getRecordValue(1,'area2d');
            $this->state['area_m'] = $this->area_m;
         }
         if ($this->debug) {
            $this->logdebug("Geometry area query: " . $this->dbobject->querystring . " ;<br>");
            $this->logdebug("Geometry area set to $this->area_m .<br>");
         }
      }
   }
   
   function subLocalProperties($thisquery) {
      // check to see if we want to substitute any values into the query string.  these will be 
      // indicated by [variable name] in brackets
      // we must have a list of variables that are approved, so we can't go showing our password for the db server
      $pprops = $this->getPublicProps();
      $searched = '';
      $regex = '/\[([^\]]+)\]/';
      preg_match_all( $regex, $thisquery, $matches );
      foreach ($matches[1] as $thistag) {
         if ( isset($this->$thistag) or isset($this->state[$thistag]) ) {
            if (isset($this->$thistag)) {
               $thisval = $this->$thistag;
            } else {
               $thisval = $this->state[$thistag];
            }
            $thisquery = str_replace("[$thistag]", $thisval, $thisquery);
            if ($this->debug) {
               error_log("Replacing: [$thistag] with " . substr($this->$thistag,0,24));
            }
         } else {
            if ($this->debug) {
               error_log("Could not Find: $thistag  <br>");
            }
         }
      }
      /*
      foreach ($pprops as $thisprop) {
         if ( isset($this->$thisprop) ) {
            $thisquery = str_replace("[$thisprop]", $this->$thisprop, $thisquery);
            $searched .= " " . "[$thisprop]";
            if ($this->debug) {
               error_log("Replacing: $thisprop  <br>");
            }
         } else {
            if ($this->debug) {
               error_log("Could not Find: $thisprop  <br>");
            }
         }
      }
      */
      
      //error_log("Searched: $searched ; <br>");
      //error_log("Query defined: $thisquery ; <br>");
      return $thisquery;
   }
      

   function getRemoteData() {

      # first, verify that the remote data source is actually a functioning remote source
      if (method_exists($this->inputs['scratchtable'][0]['object'], 'doRemoteQuery')) {
         # source has remoteQuery method, now see if we need to use it
         # check if we have cached out time series data in a local file.
         if ($this->cache_ts) {
            # if this is set, data has already been retrieved from the cache by the parent init() method
            # thus, we need to check to see if the data exists in the tsvalues array
            # if the tsvalues have been retrieved we can return
            if ( (count($this->tsvalues) > 0) and ($this->inputs['scratchtable'][0]['object']->data_refreshed == 0) ) {
               if ($this->debug) {
                  $this->logDebug("Cached data retrieved, and remote source not updated, no need to query.<br>");
               }
               return array();
            }
         }

         # also need to check if we have all of the variables in this file
         # also need to check if remote data source has updated its query this run
         if ($this->debug) {
            $this->logDebug("Remote Object exists with doRemoteQuery method, requesting data.<br>");
         }
         #error_log("Object requesting remote query " . $this->name . "<br>");
         #error_log("Remote refreshed " . $this->inputs['scratchtable'][0]['object']->data_refreshed . "<br>");
         #error_log("Local record count " . count($this->tsvalues) . "<br>");
         #error_log("Remote Object exists with doRemoteQuery method, requesting data.<br>");
         $theserecs = $this->inputs['scratchtable'][0]['object']->doRemoteQuery($this->username, $this->password, $this->the_geom);
         if ($this->debug) {
            $nr = count($theserecs);
            $this->logDebug("$nr records returned from remote query.<br>");
         }
         $this->datecolumn = $this->inputs['scratchtable'][0]['object']->datecolumn;
         $this->single_datecol = $this->inputs['scratchtable'][0]['object']->single_datecol;
         $this->yearcolumn = $this->inputs['scratchtable'][0]['object']->yearcolumn;
         $this->monthcolumn = $this->inputs['scratchtable'][0]['object']->monthcolumn;
         $this->daycolumn = $this->inputs['scratchtable'][0]['object']->daycolumn;
      }

      return $theserecs;
   }

   function setupLocalTable($theserecs) {
      # this will take the raw data records, and process them for public consumption, as well as restricting
      # access in a spatial manner
      $columns = array();
      $sprecs = array();

      if ($this->conntype <> 5) {
         $scratchname = "tmp_db$this->sessionid" . "_$this->componentid" . "_" . str_pad(rand(1,99), 3, '0', STR_PAD_LEFT);
      } else {
         if (isset($this->inputs['scratchtable'])) {
            if (property_exists($this->inputs['scratchtable'][0]->scratchtable)) {
               $scratchname = $this->inputs['scratchtable'][0]->scratchtable;
            } else {
               $scratchname = "tmp_db$this->sessionid" . "_$this->componentid" . "_" . str_pad(rand(1,99), 3, '0', STR_PAD_LEFT);
            }
         }
      }
      if ($this->debug) {
         $this->logDebug("Public records routine called <br>");
      }
      # default to a guess of the schema name
      $this->schema = 'pg_temp_1';
      if ($this->debug) {
         $this->logDebug("Checking data source status is object(this listobject) " . is_object($this->listobject) . " and conntype <> 5 = " . ($this->conntype <> 5) . "<br>");
      }
      # first we store the data in a local, temp table
      if ( is_object($this->listobject) and ($this->conntype <> 5) ) {
         # only have to set up the tables if this is NOT a shared connection
         # format for output
         if ($this->debug) {
            $this->logDebug("Outputting Time Series to db: $scratchname <br>");
         }
         $columns = array_keys($theserecs[0]);
         if ($this->debug) {
            $this->logDebug("Found columns: " . print_r($columns,1) . "<br>" );
         }
         $numin = count($theserecs);
         if ($this->debug) {
            $this->logDebug("Input data array has $numin records.<br>");
         }
         
         // set up a custom set of dbcolumntypes here, so that it does not load columns for local 
         // state variables, since they will then be fetched from the timeseries table and overwrite 
         // the state values (or any local sub-processors) with NULL values
         $localdbcols = array();
         foreach ($columns as $colname) {
            if (isset($this->dbcolumntypes[trim($colname)])) {
               $localdbcols[trim($colname)] = $this->dbcolumntypes[trim($colname)];
            } else {
               if ($this->debug) {
                  $this->logDebug("$colname not found in dbcolumntypes<br>");
               }
            }
         }
         //$this->listobject->debug = 1;
         $this->localtab_create_sql = $this->listobject->array2tmpTable($theserecs, $scratchname, $columns, $localdbcols, 1, $this->bufferlog);
         //$this->listobject->debug = 0;
         
         if ($this->debug) {
            //error_log("$this->name calling array2tmptable with localdbcols - " . print_r($localdbcols,1));
            $this->logDebug("Local Table Creation SQL: $this->localtab_create_sql<br>");
         }
         if ($this->debug) {
            $this->logDebug("Sent the following column formats: " . print_r($localdbcols,1) . "<br>");
            $this->logDebug("Getting schema name for temp table.<br>");
         }
         $this->listobject->querystring = " SELECT nspname FROM pg_namespace WHERE oid = pg_my_temp_schema(); ";
         if ($this->debug) {
            $this->logDebug($this->listobject->querystring . " ;<br>");
         }
         $this->listobject->performQuery();
         $this->schema = $this->listobject->getRecordValue(1,'nspname');
         if ($this->debug) {
            $this->listobject->querystring = " SELECT count(*) as numrecs FROM $scratchname; ";
            $this->listobject->performQuery();
            $numrecs = $this->listobject->getRecordValue(1,'numrecs');
            $this->logDebug($numrecs . " records in local table;<br>");
            //error_log($numrecs . " records in local table;<br>");
         }
      } else {
         # format for output
         if ($this->debug) {
            $this->logDebug("List object not set.<br>");
         }
         return;
      }

      $this->scratchtable = $scratchname;
      $this->columns = $columns;

   }

   function setupGeometryColumn() {
      # this will take the raw data records, and process them for ppublic consumtion, as well as restricting
      # access in a spatial manner

      $this->geomclause = '';
      $schema = $this->schema;
      $scratchname = $this->scratchtable;
      if ($this->debug) {
         $this->logDebug("Checking for spatial constraints: $this->lat_col , $this->lon_col.<br>");
      }
      # now, if we have defined an X/Y column, we go ahead and add the geometry
      if ( in_array($this->lat_col, $this->columns) and in_array($this->lon_col, $this->columns) and ($this->restrict_spatial) ) {
         if ($this->debug) {
            $this->logDebug("Geometry columns located. Preparing query<br>");
         }
         if ($this->conntype <> 5) {
            # only need to set this up if this is NOT a shared connection
            //$this->listobject->querystring = " select addGeometryColumn ( '$schema', '$scratchname', 'the_geom', 4326, 'POINT', 2) ";
            $this->listobject->querystring = " select addGeometryColumn ( '$scratchname', 'the_geom', 4326, 'POINT', 2) ";
            if ($this->debug) {
               $this->logDebug($this->listobject->querystring . " ;<br>");
            }
            $this->listobject->performQuery();
            $this->listobject->querystring = " UPDATE $scratchname set the_geom =  st_geomFromText('POINT(' || $this->lon_col || ' ' || $this->lat_col || ' )', 4326 )";
            if ($this->debug) {
               $this->logDebug($this->listobject->querystring . " ;<br>");
            }
            $this->listobject->performQuery();
            if ($this->debug) {
               $this->listobject->querystring = " SELECT st_extent(the_geom) as geomext FROM $scratchname; ";
               $this->listobject->performQuery();
               $geomext = $this->listobject->getRecordValue(1,'geomext');
               $this->logDebug("Geometry extent: " . $geomext . " <br>");
            }
         }
         # get spatially overlapping records
         $this->geomclause = " where within(the_geom,  st_geomFromText('$this->the_geom', 4326)) ";
         if ($this->debug) {
            $this->logDebug($this->listobject->querystring . " ;<br>");
         }

      } else {
         if ($this->debug) {
            $this->logDebug("No spatial constraints found: $this->lat_col , $this->lon_col <br>");
         }
      }

   }

   function doRemoteQuery($un, $pw, $geom = '') {
      # this will take the raw data records, and process them for public consumption, as well as restricting
      # access in a spatial manner for a CLIENT connection, i.e., some other query object that may want either a
      # copy of these records, or a special set of spatial constraints to this data

      $scratchname = $this->scratchtable;

      $sprecs = array();

      # do not allow access to a custom query if the username and password are not matching, this increases security somewhat
      if ( ($un == $this->username) and ($pw == $this->password) ) {

         $remotegeomclause = '';

         if ($geom <> '') {
            $remotegeomclause = " where within(the_geom,  st_geomFromText('$geom', 4326)) ";
         }

         # get spatially overlapping records
         $this->listobject->querystring = " SELECT " . $this->public_sql  . " FROM $scratchname " . $remotegeomclause;
         if (strlen($this->groupcols) > 0) {
            $this->listobject->querystring .= " GROUP BY " . $this->groupcols;
         }
         if ($this->debug) {
            $this->logDebug($this->listobject->querystring . " ;<br>");
         }
         $this->listobject->performQuery();
         $sprecs = $this->listobject->queryrecords;
      }

      return $sprecs;

   }

   function performLocalQueries() {
      # this will take the raw data records, and process them for public consumption, as well as restricting
      # access in a spatial manner

      $scratchname = $this->scratchtable;

      $sprecs = array();

      # get spatially overlapping records
      $this->listobject->querystring = " SELECT " . $this->public_sql  . " FROM \"$scratchname\" " . $this->geomclause;

      if (strlen($this->groupcols) > 0) {
         $this->listobject->querystring .= " GROUP BY " . $this->groupcols;
      }
      if ($this->debug) {
         $this->logDebug("localQuery: " . $this->listobject->querystring . " ;<br>");
      }
      $this->listobject->performQuery();
      $sprecs = $this->listobject->queryrecords;

      return $sprecs;

   }

   function performGroupedRemoteQueries() {
      # this will take the raw data records, and process them for public consumption, as well as restricting
      # access in a spatial manner
      $sprecs = array();

      # get spatially overlapping records
      $this->dbobject->querystring = " SELECT " . $this->public_sql  . " FROM ($this->remote_query) as foo " . $this->geomclause;

      if (strlen($this->groupcols) > 0) {
         $this->dbobject->querystring .= " GROUP BY " . $this->groupcols;
      }
      if ($this->debug) {
         $this->logDebug("localQuery: " . $this->dbobject->querystring . " ;<br>");
      }
      $this->dbobject->performQuery();
      $this->logDebug("Query Message: " . $this->dbobject->error . " ;<br>");
      $sprecs = $this->dbobject->queryrecords;

      return $sprecs;

   }

   function addQueryData($theserecs) {
      # expects an associative array in the format of the listobject queryrecords
      if ($this->debug) {
         $this->logDebug("Adding " . count($theserecs) . ' records <br>');
      }
      $added = 0;
      $addcount = 0;
      $total = count($theserecs);
      // set up data column types
      foreach ($theserecs as $thisrec) {
         if (strlen($this->datecolumn) > 0) {
            $tcol = $this->datecolumn;
         } else {
            $tcol = 'timestamp';
         }
         if ($this->single_datecol <> 1) {
            # need to construct a date column from a year/mo/day set of fields
            $tcol = $this->yearcolumn;
            # we set the $tcol to the year field, and then later, we check for the existence of the other fields, and if
            # they do not exist in the record, we default to 1 as their value
            if ($this->debug) {
               $this->logDebug("Using meta-column for date <br>");
               $this->logDebug("Searching for " . print_r($tcol,1) . " in " . print_r(array_keys($thisrec),1) . "<br>");
            }
         }
         if (in_array($tcol, array_keys($thisrec))) {
            if ($this->single_datecol <> 1) {
               # add default values for month and day column if they do not exist
               if (!in_array($this->monthcolumn, array_keys($thisrec))) {
                  $thisrec[$this->monthcolumn] = 1;
               }
               if (!in_array($this->daycolumn, array_keys($thisrec))) {
                  $thisrec[$this->daycolumn] = 1;
               }
               $ts = str_pad($thisrec[$this->yearcolumn], 2, '0', STR_PAD_LEFT);
               $ts .= '-' . str_pad($thisrec[$this->monthcolumn], 2, '0', STR_PAD_LEFT);
               $ts .= '-' . str_pad($thisrec[$this->daycolumn], 2, '0', STR_PAD_LEFT);
               $thisrec['thisdate'] = $ts;
            } else {
               $ts = $thisrec[$tcol];
            }
            $this->addValue($ts, $thisrec);
            #break;
         } else {
            if ($this->debug) {
               $this->logDebug("Error: Date column $tcol does not exist.<br>");
            }
         }
         $added++;
         $addcount++;
         if ( $addcount == 500) {
            $this->systemLog("$this->name added $added of $total records", 1);
            $addcount = 0;
         }
      }
   }

   function getTablesColumns() {
      if ($this->debug) {
         $this->logDebug("function getTablesColumns() called on $this->name <br>");
      }
      
      $base_query = $this->subLocalProperties($this->sql_query);
      $this->raw_columns = array();
      $this->raw_column_types = array();

      switch ($this->conntype) {
         case 1:
         # postgis will call all tables
         if (is_object($this->dbobject) and (strlen($base_query) > 0) ) {
            if(method_exists($this->dbobject,'performQuery')) {
               //$shortquery = " select * from ( $base_query ) as foo LIMIT 1 ";
               $shortquery = " $base_query LIMIT 1 ";
               if ($this->debug) {
                  $this->logDebug("short column query: " . $shortquery . "<br>");
               }
               $this->dbobject->querystring = $shortquery;
               $this->dbobject->performQuery();
               if ($this->debug) {
                  $this->logDebug("short column query error mesg?: " . $this->dbobject->error . "<br>");
               }
               $allcols = array_keys($this->dbobject->queryrecords[0]);
               foreach ($allcols as $colkey => $thiscol) {
                  if ($this->debug) {
                     $this->logDebug("Adding $thiscol to raw_columns <br>");
                  }
                  array_push($this->localprocs,$thiscol);
                  $this->raw_columns[] = $thiscol;
                  if ($this->dbobject->dbsystem == 'postgresql') {
                     $this->raw_column_types[$thiscol] = pg_field_type($this->dbobject->result, $colkey);
                  }
               }
            }
         }
         break;

         case 2:
         # ODBC
         if (is_object($this->dbobject) and (strlen($base_query) > 0) ) {
            if(method_exists($this->dbobject,'performQuery')) {
               $shortquery = " select * from ( $base_query ) as foo LIMIT 1 ";
               $this->dbobject->querystring = $shortquery;
               if ($this->debug) {
                  $this->logDebug("column qery: " . $shortquery);
               }
               $this->dbobject->performQuery();
               $allcols = array_keys($this->dbobject->queryrecords);
               foreach ($allcols as $thiscol) {
                  array_push($this->localprocs,$thiscol);
                  $this->raw_columns[] = $thiscol;
               }
            }
         }
         break;

         case 3:
         # Oracle
         if (is_object($this->dbobject) and (strlen($base_query) > 0) ) {
            if(method_exists($this->dbobject,'performQuery')) {
               # due to difficulty with oracles lack of a LIMIT clause, this will be very slow
               $this->dbobject->querystring = $base_query;
               if ($this->debug) {
                  $this->logDebug("column qery: " . $base_query);
               }
               $this->dbobject->performQuery();
               $allcols = array_keys($this->dbobject->queryrecords[0]);
               foreach ($allcols as $thiscol) {
                  array_push($this->localprocs,$thiscol);
                  $this->raw_columns[] = $thiscol;
               }
            }
         }

         break;

         case 4:
         # WFS will require a getCapabilities call.  Don't yet know how to parse that.
         break;

      }

   }

   function rawDBValues2file($theserecs) {
      if (strlen($this->rawdbfile) == 0) {
         $this->rawdbfile = 'rawdbvalues.' . $this->componentid . '.csv';
      }
      $filename = $this->outdir . '/' . $this->rawdbfile;
      # format for output
      $fdel = '';
      $outform = '';
      if ($this->debug) {
         $this->logDebug("Outputting Time Series to file: $this->logfile <br>");
      }
      if (count($theserecs) > 0) {
         $minkey = min(array_keys($theserecs));
         if ($this->debug) {
            $this->logDebug("Time Series Start (seconds): $minkey <br> Exporting Columns");
            $this->logDebug(array_keys($theserecs[$minkey]));
         }

         foreach (array_keys($theserecs[$minkey]) as $thiskey) {
            if (in_array($thiskey, array_keys($this->logformats))) {
               # get the log file format from here, if it is set
               if ($this->debug) {
                  $this->logDebug("Getting format for log table " . $thiskey . "\n");
               }
               $outform .= $fdel . $this->logformats[$thiskey];
            } else {
               if ($this->debug) {
                  $this->logDebug("Guessing format for log table " . $thiskey . "\n");
               }
               if (is_numeric($theserecs[$minkey][$thiskey])) {
                  $outform .= $fdel . $this->numform;
               } else {
                  $outform .= $fdel . $this->strform;
               }
            }
            $fdel = ',';
         }
         if ($this->debug) {
            $this->logDebug("Using format string: $outform <br>");
         }
         $outarr = nestArraySprintf($outform, $theserecs);
         #$this->logDebug($outarr);
         $colnames = array(array_keys($theserecs[0]));
         if ($this->debug) {
            $colcsv = implode(',', $colnames);
            $this->logDebug("Columns: $colcsv <br>");
         }

         if ($this->debug) {
            $numlines = count($theserecs);
            $this->logDebug("Outputting: $numlines lines <br>");
         }

         putDelimitedFile("$filename",$colnames,$this->translateDelim($this->delimiter),1,$this->fileformat);

         putArrayToFilePlatform("$filename", $outarr,0,$this->fileformat);
      }
   }


}

class dataConnectionTransform extends modelObject {

   var $func = '';
   var $col_name = '';
   var $mysql = ''; # will contain my formatted SQL complliant column function definition
   var $groupcol = '';
   var $loggable = 1;
   var $log2db = 0; // do not create a table for this, just log in parent

   function preProcess() {
      list($myopen, $myclose, $mygroup) = $this->getFunctionFormat($this->func, $this->col_name);
      $this->mysql = "$myopen \"$this->col_name\" $myclose";
      $this->groupcol = "$mygroup";
   }
   
   function evaluate() {
      $this->result = $this->arData[$this->col_name];
   }


   function getFunctionFormat($func, $colname) {

      $fopen = '';
      $fclose = '';

      switch ($func) {
         case 'min':
            $fopen = 'min(';
            $fclose = ')';
            $fgroup = '';
         break;

         case 'mean':
            $fopen = 'avg(';
            $fclose = ')';
            $fgroup = '';
         break;

         case 'max':
            $fopen = 'max(';
            $fclose = ')';
            $fgroup = '';
         break;

         case 'gini':
            $fopen = 'gini(array_accum(';
            $fclose = '))';
            $fgroup = '';
         break;

         case 'sum':
            $fopen = 'sum(';
            $fclose = ')';
            $fgroup = '';
         break;

         case 'count':
            $fopen = 'count(';
            $fclose = ')';
            $fgroup = '';
         break;

         default:
         # default to a selection only, which is NOT an aggregate, therefore, we return this column for grouping
            $fgroup = $colname;
         break;
      }

      return array($fopen, $fclose, $fgroup);
   }

}


class dataConnectionSubObject extends dataConnectionObject {

   function chooseLocalColumns() {
      // this version returns all columns
      $public_cols = '*';
      $group_cols = '';
      $this->logDebug("Columns for local query: $public_cols, $group_cols <br>\n");
      return array($public_cols, $group_cols);
   }
   
   function setupGeometryColumn() {
      parent::setupGeometryColumn();
   }
   
   function wake() {
      if (is_object($this->parentobject)) {
         $this->the_geom = $this->parentobject->the_geom;
        error_log("Getting parent geometry info");
      } else {
        error_log ("Parent object not set on $this->name");
      }
      parent::wake();
   }
   
   function step() {
      parent::step();
      $this->writeToParent();
   }

   function setState() {
      parent::setState();
      $this->wvars = $this->raw_columns;
      $this->initOnParent();
   }
   
   function getTablesColumns() {
      parent::getTablesColumns();
      if ($this->debug) {
         error_log("Query: " . $this->dbobject->querystring);
         error_log("Result: " . $this->dbobject->error);
         error_log("Records: " . print_r($this->dbobject->queryrecords,1));
      }
   }

   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      if (is_object($this->parentobject)) {
         foreach ($this->varsToSetOnParent('map') as $localvar => $thisvar) {
            if ($this->debug) {
               $this->logDebug("Setting $thisvar to type float8 on parent.<br>\n");
               error_log("$thisvar to type float8 on parent.<br>\n");
            }
            //error_log("Checking usability of raw_column_types");
            //error_log("thisdbobject = " . $this->dbobject->dbsystem . " and parent sys = " . $this->parentobject->listobject->dbsystem) ;
            //error_log("raw_column_types = " . print_r($this->raw_column_types,1));
            if ( ($this->dbobject->dbsystem == $this->parentobject->listobject->dbsystem) 
               and isset($this->raw_column_types[$localvar]) 
               ) {
               $this->parentobject->setSingleDataColumnType($thisvar, $this->raw_column_types[$localvar], 0.0);
               //error_log("Setting column $thisvar type to " . $this->raw_column_types[$localvar]);
            } else {
               $this->parentobject->setSingleDataColumnType($thisvar, 'float8', 0.0);
               //error_log("Setting column $thisvar type to 'float8'");
            }
            if (!in_array($thisvar, $this->vars)) {
               $this->vars[] = $thisvar;
            }
         }
      }
   }
}

class XMLDataConnection extends dataConnectionObject {
   
   var $conntype = 6;
   var $feed_address = '';
   var $urls_finalized = 0; // set to 1 after finalizeFeedURLs() is called by init or wake() to prevent double execution
   var $final_feed_url = ''; // after adding any extra local variables, etc.
   // parent variable conntype will contain the descriptotr, for now, we assume RSS
   var $data_inventory_address = ''; // basically, what address to use to get a list of data columns for use by the object
   var $final_data_inventory_address = ''; // after adding any extra local variables, etc.
   var $extra_variables = ''; // a list of key=value pairs entered in a text field, if there are carriage returns, it will concatenate them along URL lines with &
   var $final_extra_variables = ''; // local internal properties appended to extra_variables (only actually modified in sub-class objects who have defined processLocalExtras())
   var $feed_inventory = array(); //
   var $feed_columns = array();
   var $cache = 'cache';
   var $cache_age = 2592000; // cache age in seconds (259200 = (86400 * 30)), i.e. 30 days
   var $read_timeout = 2400; // time out in seconds to read data file
   var $mincache = 0; // file size for automatic cache refresh
   function setupDBConn($refresh = 0) {
      // make the 
      parent::setupDBConn($refresh);
   }
   
   function init() {
      $this->dbobject = new pgsql_QueryObject;
      // check for extra URL stuff added
      define('MAGPIE_CACHE_ON', TRUE);
      define('MAGPIE_FETCH_TIME_OUT', 240);
      define('MAGPIE_CACHE_DIR', $this->basedir . "/" . $this->cache);
      $this->urls_finalized = 0;
      $this->final_extra_variables = $this->extra_variables;
      $this->processLocalExtras();
      $this->finalizeFeedURLs();
      $this->getTablesColumns();
      parent::init();
   }
   
   function wake() {
      //parent::wake();
      // added 6/5/2017 to see if we can get rid of some warnings
      $this->localprocs = array();
      define('MAGPIE_CACHE_ON', TRUE);
      define('MAGPIE_FETCH_TIME_OUT', 240);
      define('MAGPIE_CACHE_DIR', $this->basedir . "/" . $this->cache);
      $this->urls_finalized = 0;
      $this->final_extra_variables = $this->extra_variables;
      $this->processLocalExtras();
      if ($this->debug) error_log("XMLCOnnection calling finalizeFeedURLs()");
      $this->finalizeFeedURLs();
      if ($this->debug) error_log("XMLCOnnection calling getTablesColumns()");
      $this->getTablesColumns();
      parent::wake();
   }

   function showHTMLInfo() {
      $htmlinfo = parent::showHTMLInfo();

      # puts columns from query into state array
      #$this->getData();
      #$htmlinfo .= 'Query: ' . $this->dbobject->querystring;
      #$htmlinfo .= print_r($this->dbobject->queryrecords,1);
      #$htmlinfo .= print_r($this->state,1);
      $htmlinfo .= "<hr>$this->name query = <br>" . $this->final_feed_url;
      /* 
      // this won't work unless we have finalized our feed URL
      $enc_name = $this->encodeFilename();
      $filename = $this->basedir . "/" . $this->cache . "/" . $enc_name;
      $htmlinfo .= "<hr>Cache File = <br>" . $filename;
      */
      return $htmlinfo;
   }
   
   function chooseLocalColumns() {
      // this is kind of a legacy, when we didn't want to allow access to all columns,
      // this will stay here for the base object, for the time being, but will be sub-classed 
      // by all children, under the assumption that if a column is brought in by some query, 
      // then it must be desired to be used.  If not, there would be no reason to query it 
      // in the first place
      // this version returns all columns
      $public_cols = '*';
      $group_cols = '';
      $this->logDebug("Columns for local query: $public_cols, $group_cols <br>\n");
      return array($public_cols, $group_cols);
   }
   
   function getRemoteFileData() {
      
      // encode the file URL
      // check the cahce directory for said file
      // if the file exists in the cache
         // check the data for staleness
         // if state refreshCache()
      // if the file does NOT exist - get it (refreshCache())
         // stash a copy in the cache
      if ($this->debug) {
         $this->logDebug("Checking for cached file <br>\n");
      }
      $this->logDebug("Checking for cached file <br>\n");
      if (!$this->checkCache() or $this->force_refresh) {
         if ($this->force_refresh) {
            $this->logDebug("Refresh forced for cached file <br>\n");
         }
         $xmlfile = $this->refreshCache();
      } else {
         $xmlfile = $this->readCache();
      }
      
      return $xmlfile;
   }
   
   function refreshCache() {
      $ctx = stream_context_create(array(
          'http' => array(
              'timeout' => $this->read_timeout
              )
          )
      );
      $xmlfile = file_get_contents($this->final_feed_url, 0, $ctx);
      $enc_name = $this->encodeFilename();
      $filename = $this->basedir . "/" . $this->cache . "/" . $enc_name;
      if ($this->debug) {
         $this->logDebug("Writing $filename to cache <br>\n");
      }
      $this->logDebug("Writing $filename to cache <br>\n");
      $fp = fopen($filename, 'w');
      fwrite($fp, $xmlfile);
      return $xmlfile;
   }
   
   function readCache() {
      $enc_name = $this->encodeFilename();
      $filename = $this->basedir . "/" . $this->cache . "/" . $enc_name;
      $ctx = stream_context_create(array(
          'http' => array(
              'timeout' => $this->read_timeout
              )
          )
      );
      if ($this->debug) {
         $this->logDebug("$this->name reading $filename from cache <br>\n");
      }
      $this->logDebug("$this->name reading $filename from cache <br>\n");
      $xmlfile = file_get_contents($filename, 0, $ctx);
      return $xmlfile;
   }
   
   function encodeFilename($filename = '') {
      if ($filename == '') {
         $filename = $this->final_feed_url;
      }
      $enc_name = md5($filename);
      return $enc_name;
   }
   
   function checkCache() {
      $enc_name = $this->encodeFilename();
      $filename = $this->basedir . "/" . $this->cache . "/" . $enc_name;
      $modtime = filemtime($filename);
      $age = mktime() - $modtime;
      $file_size = filesize($filename);
      $this->logDebug("$filename age $age = mktime() - $modtime <br>\n");
      if ( ($age > $this->cache_age) or ($file_size <= $this->mincache)) {
         return false;
      }
      return true;
   }
   
   function getData() {
      $retvals = array();
      // check for extra URL stuff added
      $this->finalizeFeedURLs();
      if (class_exists('jiffyXmlReader')) {
         if ($this->debug) {
            $this->logDebug("Fetching Feed:  $this->final_feed_url <br>\n");
         }

         // ***************************************************************** //
         // START - using new internally cahcing file_get_contents
         // ***************************************************************** //
         $xmlfile = $this->getRemoteFileData();
         // instead of:
         //$xmlfile = file_get_contents($this->final_feed_url, 0, $ctx);
         $rawlength = strlen($xmlfile);
         if ($this->debug) {
            $this->logDebug("Length of raw feed:  $rawlength <br>\n");
         }
         $xml = simplexml_load_string($xmlfile);
         if ($this->debug) {
            $this->logDebug("Keys: " . print_r(array_keys((array)$xml),1) . " <br>\n");
         }
         //$xml = simplexml_load_file($this->final_feed_url);
         $linklist = $xml->channel->item;
         // ***************************************************************** //
         // END - new internally cahcing file_get_contents
         // ***************************************************************** //
         
         // this currently only works if we have a valid data column, or collection of date columns (year, mo, day)
         $valid_datecol = 0;
         $ignore_fields = array('title', 'link', 'description');  
         if ($this->datecolumn <> '') {
            $k = 0;
            // iterate through returned values, ignore fields 'title', 'link', 'description'
            $retvals = array();
            foreach ($linklist as $linkobj) {
               $thislink = (array)$linkobj;
               foreach($ignore_fields as $thisfield) {
                  unset($thislink[$thisfield]);
               }
               if ($k == 0) {
                  // check to see if this has a date returned
                  $firstrec = $thislink;
                  if ($this->debug) {
                     $this->logDebug("First record returned = " . print_r($thislink, 1) . " <br>\n");
                  }
                  if (isset($thislink[$this->datecolumn])) {
                     $valid_datecol = 1;
                  } else {
                     $this->logError("Date Column $this->datecolumn not found <br>\n");
                     $this->logDebug("Date Column $this->datecolumn not found <br>\n");
                     if ($this->debug) {
                        $this->logDebug("Date Column $this->datecolumn not found <br>\n");
                     }
                     // no data column found, so set the state to the first record and exit
                     foreach ($thislink as $thiskey=>$thisval) {
                        $this->setStateVar($thiskey, $thisval);
                     }
                     break;
                  }
               }
               $retvals[] = $thislink;
               $k++;
            }
            if ($this->debug) {
               $this->logDebug("Feed returned " . count($retvals) . " records <br>\n");
            }
            $this->logDebug("Feed returned " . count($retvals) . " records <br>\n");
         } else {
            // no data column defined so set the state to the first record and exit
            $thislink = (array)$linklist[min(array_keys($linklist))];
            foreach($ignore_fields as $thisfield) {
               unset($thislink[$thisfield]);
            }
            foreach ($thislink as $thiskey=>$thisval) {
               $this->setStateVar($thiskey, $thisval);
            }
            if ($this->debug) {
               $this->logDebug("Date column not defined<br>\n");
               $this->logDebug("Feed returned " . count($linklist) . " records <br>\n");
            }
         }
         
         
      } else {
         $this->logError("Error Retrieving Data: RSS Magpie function 'fetch_rss' is not defined - can not retrieve feed.");
         error_log("Error Retrieving Column Names: RSS Magpie function 'fetch_rss' is not defined - can not retrieve feed.");
      }
      
      $this->dbobject->queryrecords = $retvals;
      $numrecs = count($linklist);
      $numparsed = count($retvals);
      $this->logError("$this->name : $numrecs returned, $numparsed added to data series <br>\n");
      //error_log("$this->name : $rawlength char xml file,  $numrecs returned, $numparsed added to data series <br>\n");
   }
   
   function finalizeFeedURLs() {
      // this will incorporate any local properties, and check to make sure that the url is formed OK,
      // this creates a finalized feed url, AND a finalized data_inventory_url so that we don't try to get 
      // data that does not exist
      if (!$this->urls_finalized) {
         $extras = $this->subLocalProperties($this->final_extra_variables); 
         $url = ltrim(rtrim($this->feed_address));
         $this->final_feed_url = $this->appendExtrasToURL($url, $extras);
         if ($this->debug) {
            $this->logDebug("Final Query URL: $this->final_feed_url <br>\n");
         }
         //error_log("Final Query URL: $this->final_feed_url <br>\n");

         // only do this if we actually HAVE a data_inventory_address set
         if (strlen($this->data_inventory_address) > 0) {
            $extras = $this->subLocalProperties($this->final_extra_variables); 
            $url = ltrim(rtrim($this->data_inventory_address));
            $this->final_data_inventory_address = $this->appendExtrasToURL($url, $extras);
            if ($this->debug) {
               $this->logDebug("Final Inventory URL: $this->final_data_inventory_address <br>\n");
            }
            //error_log("Final Inventory URL: $this->final_data_inventory_address <br>\n");
         }
         $this->urls_finalized = 1;
      }
   }
   
   function processLocalExtras() {
      // this is just a stub
   }
   
   function appendExtrasToURL($url, $extra_variables) {
      $extras = preg_split("/((\r(?!\n))|((?<!\r)\n)|(\r\n))/", $this->subLocalProperties($extra_variables)); 
      $url = ltrim(rtrim($url));
      $udel = '';
      $callpieces = preg_split('[\/]', $url);
      $lastpiece = $callpieces[(count($callpieces) - 1)];
      if (!strpos($lastpiece, '?')) {
         // this URL has not parameters appended to it, so we have to add the '?' before appending parameters
         $url .= '?';
      } else {
         if (substr($url,-1) <> '?') {
            // the URL already contains a ? AND has a parameter after the ?, so set the delimiter to '&'
            $udel = '&';
         }
      }
      foreach ($extras as $thisextra) {
         if (strlen(ltrim(rtrim($thisextra))) > 0) {
            $url .= $udel . ltrim(rtrim($thisextra));
            $udel = '&';
         }
      }
      $final_url = $url;
      if ($this->debug) {
         $this->logDebug("Final Query URL: $url <br>\n");
      }
      //error_log("Final Query URL: $url <br>\n");
      return $url;
   }
   
   
   function getTablesColumns() {
      if (function_exists('fetch_rss')) {
         // this rss feed should return a single record with descriptive information
         if ($this->debug) {
            $this->logDebug("Starting Data Inventory Address: $this->data_inventory_address <br>\n");
            $this->logDebug("Final Inventory Address: $this->final_data_inventory_address <br>\n");
            error_log("Starting Data Inventory Address: $this->data_inventory_address <br>\n");
            error_log("Final Inventory Address: $this->final_data_inventory_address <br>\n");
         }
         if (strlen(rtrim(ltrim($this->final_data_inventory_address))) > 0) {
            if ($this->debug) error_log("Calling fetch_rss($this->final_data_inventory_address) ");
            $rss = fetch_rss($this->final_data_inventory_address);
            if ($this->debug) error_log("returning from fetch_rss() ");
         } else {
            // try the regular rss data address
            if ($this->debug) error_log("Calling fetch_rss($this->final_feed_url) ");
            $rss = fetch_rss($this->final_feed_url);
            if ($this->debug) error_log("returning from fetch_rss() ");
         }
         if (!is_object($rss)) {
            if ($this->debug) error_log("fetch_rss did not return a valid object - returning");
         } else {
            #print_r($rss->items);
            $linklist = $rss->items;
            $firstrec = $linklist[min(array_keys($linklist))];
            // stash this for use by other information gathering methods
            $this->feed_inventory = $firstrec;
            if (isset($firstrec['all_columns'])) {
               if ($this->debug) {
                  $this->logdebug("RAW all_columns: " . $firstrec['all_columns'] . " <br>\n");
               }
               $all_columns = explode(",", $firstrec['all_columns']);
               if (isset($firstrec['data_column_types'])) {
                  $data_column_types = explode(",", $firstrec['data_column_types']);
                  if ($this->debug) {
                     $this->logdebug("Found data column types: " . $firstrec['data_column_types'] . " <br>\n");
                  }
               } else {
                  $this->logdebug("data_column_types not in inventory feed: " . print_r(array_keys($firstrec),1) . " <br>\n");
               }
               // get data column types into object
               if (isset($firstrec['data_columns'])) {
                  $data_columns = explode(",", $firstrec['data_columns']);
                  $data_column_types = array();
                  if (isset($firstrec['data_column_types'])) {
                     $data_column_types = explode(",", $firstrec['data_column_types']);
                     if ($this->debug) {
                        $this->logdebug("Using data_column_types: " . print_r($data_column_types,1) . " <br>\n");
                     }
                  } else {
                     if ($this->debug) {
                        $this->logdebug("data_column_types not in inventory feed: " . print_r(array_keys($firstrec),1) . " <br>\n");
                     }
                  }
                  $colindex = 0;
                  foreach ($data_columns as $thiscol) {
                     $this->setSingleDataColumnType($thiscol, $data_column_types[$colindex]);
                     //$this->dbcolumntypes[$thiscol] = $data_column_types[$colindex];
                     if ($this->debug) {
                        $this->logdebug("Setting $thiscol to type: " . $data_column_types[$colindex] . " <br>\n");
                     }
                     $colindex++;
                  }
                  if (isset($firstrec['time_column'])) {
                     array_push($this->localprocs,$firstrec['time_column']);
                     $this->dbcolumntypes[$firstrec['time_column']] = 'timestamp';
                     $this->datecolumn = $firstrec['time_column'];
                     if ($this->debug) {
                        $this->logdebug("Setting datecolumn to " . $firstrec['time_column'] . " <br>\n");
                     }
                  }

                  if ($this->debug) {
                     $this->logdebug("Columns available: " . print_r($this->localprocs,1) . " <br>\n");
                     $this->logdebug("Column types set to: " . print_r($this->dbcolumntypes,1) . " <br>\n");
                  }
               }
               foreach ($all_columns as $thiscol) {
                  array_push($this->localprocs,$thiscol);
               }
               if ($this->debug) {
                  $this->logdebug("Columns obtained from all_columns: " . print_r($this->localprocs,1) . " <br>\n");
               }
            } else {
               if (isset($firstrec['data_columns'])) {
                  $data_columns = explode(",", $firstrec['data_columns']);
                  $data_column_types = array();
                  if (isset($firstrec['data_column_types'])) {
                     $data_column_types = explode(",", $firstrec['data_column_types']);
                     $this->logdebug("Using data_column_types: " . print_r($data_column_types,1) . " <br>\n");
                  } else {
                     $this->logdebug("data_column_types not in inventory feed: " . print_r(array_keys($firstrec),1) . " <br>\n");
                  }
                  $colindex = 0;
                  foreach ($data_columns as $thiscol) {
                     array_push($this->localprocs,$thiscol);
                     $this->dbcolumntypes[$thiscol] = $data_column_types[$colindex];
                     $this->logdebug("Setting $thiscol to type: " . $data_column_types[$colindex] . " <br>\n");
                     $colindex++;
                  }
                  if (isset($firstrec['time_column'])) {
                     array_push($this->localprocs,$firstrec['time_column']);
                     $this->dbcolumntypes[$firstrec['time_column']] = 'timestamp';
                     $this->datecolumn = $firstrec['time_column'];
                     if ($this->debug) {
                        $this->logdebug("Setting datecolumn to " . $firstrec['time_column'] . " <br>\n");
                     }
                  }

                  if ($this->debug) {
                     $this->logdebug("Columns available: " . print_r($this->localprocs,1) . " <br>\n");
                     $this->logdebug("Column types set to: " . print_r($this->dbcolumntypes,1) . " <br>\n");
                  }
               } else {
                  $this->logError("Error Retrieving Column Names: Data Columns Property Not set.<br>\n");
                  $this->logError("Feed Info URL: $this->data_inventory_address <br>\n");
                  $this->logError("Feed returned:" . print_r($linklist,1) . "<br>\n");
                  $this->logError("Guessing Columns From Feed Contents" . print_r($linklist,1) . "<br>\n");
                  foreach (array_keys($firstrec) as $thiscol) {
                     array_push($this->localprocs,$thiscol);
                  }
               }
            }
            $this->feed_columns = $this->localprocs;
         }
      } else {
         $this->logError("Error Retrieving Column Names: RSS Magpie function 'fetch_rss' is not defined - can not retrieve feed.");
         error_log("Error Retrieving Column Names: RSS Magpie function 'fetch_rss' is not defined - can not retrieve feed.");
      }
   }
   
   function createQuery() {
      // dummy function, not needed in XML object
   }

   function arrayRenameOmit($linklist, $ignore_fields, $rename_fields) {
      // processes an XML object, renames fields and ommits fields as specified
      // this will only work for a simplexml object, since the magpie rss does NOT use object format
      $retarr = array();
      foreach ($linklist as $linkobj) {
         $thislink = (array)$linkobj;
         $mapped = array();
         foreach($ignore_fields as $thisfield) {
            unset($thislink[$thisfield]);
         }
         foreach($thislink as $key=>$val) {
            if (in_array($key, array_keys($rename_fields))) {
               $mapped[$rename_fields[$key]] = $val;
            } else {
               $mapped[$key] = $val;
            }
         }
         $retarr[] = $mapped;
      }
      return $retarr;
   }
   
}


//class RSSDataConnection extends dataConnectionObject {
class RSSDataConnection extends XMLDataConnection {
   
   var $conntype = 7;
   var $feed_address = '';
   var $urls_finalized = 0; // set to 1 after finalizeFeedURLs() is called by init or wake() to prevent double execution
   var $final_feed_url = ''; // after adding any extra local variables, etc.
   // parent variable conntype will contain the descriptotr, for now, we assume RSS
   var $data_inventory_address = ''; // basically, what address to use to get a list of data columns for use by the object
   var $final_data_inventory_address = ''; // after adding any extra local variables, etc.
   var $extra_variables = ''; // a list of key=value pairs entered in a text field, if there are carriage returns, it will concatenate them along URL lines with &
   var $final_extra_variables = ''; // local internal properties appended to extra_variables (only actually modified in sub-class objects who have defined processLocalExtras())
   var $feed_inventory = array(); //
   var $feed_columns = array();
   function setupDBConn($refresh = 0) {
      // make the 
      parent::setupDBConn($refresh);
   }
   
   function init() {
      parent::init();
   }
   
   function wake() {
      parent::wake();
   }
   
   function showHTMLInfo() {
      $htmlinfo = parent::showHTMLInfo();

      # puts columns from query into state array
      #$this->getData();
      #$htmlinfo .= 'Query: ' . $this->dbobject->querystring;
      #$htmlinfo .= print_r($this->dbobject->queryrecords,1);
      #$htmlinfo .= print_r($this->state,1);
      $htmlinfo .= "<hr>RSS Connection $this->name Final Feed URL = <br>" . $this->final_feed_url;
      return $htmlinfo;
   }
   
   function getData() {
      $retvals = array();
      // check for extra URL stuff added
      $this->finalizeFeedURLs();
      if (function_exists('fetch_rss')) {
         if ($this->debug) {
            $this->logDebug("Fetching Feed:  $this->final_feed_url <br>\n");
         }
         define('MAGPIE_CACHE_ON', TRUE);
         define('MAGPIE_FETCH_TIME_OUT', 2400);
         $rss = fetch_rss($this->final_feed_url);
         #print_r($rss->items);
         $elements = array();
         $linklist = $rss->items;
         // this currently only works if we have a valid data column, or collection of date columns (year, mo, day)
         $valid_datecol = 0;
         switch ($this->single_datecol) {
            case 1:
               if ($this->datecolumn <> '') {
                  // check to see if this has a date returned
                  $firstrec = $linklist[min(array_keys($linklist))];
                  if ($this->debug) {
                     $this->logDebug("First record returned = " . print_r($firstrec, 1) . " <br>\n");
                  }
                  if (isset($firstrec[$this->datecolumn])) {
                     $valid_datecol = 1;
                  }
               }
            break;
            
         }
         
         if ($valid_datecol) {
            $retvals = $linklist;
            if ($this->debug) {
               $this->logDebug("Feed returned " . count($retvals) . " records <br>\n");
            }
         } else {
            if ($this->debug) {
               $this->logDebug("Date column $this->datecolumn not in feed <br>\n");
               $this->logDebug("Feed returned " . count($linklist) . " records <br>\n");
               $this->logDebug("MagpieRSS errors:" . $rss->ERROR . " <br>\n");
               $this->logDebug("MagpieRSS warnings:" . $rss->WARNING . " <br>\n");
               $this->logDebug("MagpieRSS object:" . print_r((array)$rss,1) . " <br>\n");
            }
         }
         
         
      } else {
         $this->logError("Error Retrieving Data: RSS Magpie function 'fetch_rss' is not defined - can not retrieve feed.");
         error_log("Error Retrieving Column Names: RSS Magpie function 'fetch_rss' is not defined - can not retrieve feed.");
      }
      
      $this->dbobject->queryrecords = array();
      foreach ($retvals as $thisrec) {
         $this->dbobject->queryrecords[] = $thisrec;
      }
   }
   
   function finalizeFeedURLs() {
      // this will incorporate any local properties, and check to make sure that the url is formed OK,
      // this creates a finalized feed url, AND a finalized data_inventory_url so that we don't try to get 
      // data that does not exist
      if (!$this->urls_finalized) {
         $extras = $this->subLocalProperties($this->final_extra_variables); 
         $url = ltrim(rtrim($this->feed_address));
         $this->final_feed_url = $this->appendExtrasToURL($url, $extras);
         if ($this->debug) {
            $this->logDebug("Final Query URL: $this->final_feed_url <br>\n");
         }
         //error_log("Final Query URL: $this->final_feed_url <br>\n");

         // only do this if we actually HAVE a data_inventory_address set
         if (strlen($this->data_inventory_address) > 0) {
            $extras = $this->subLocalProperties($this->final_extra_variables); 
            $url = ltrim(rtrim($this->data_inventory_address));
            $this->final_data_inventory_address = $this->appendExtrasToURL($url, $extras);
            if ($this->debug) {
               $this->logDebug("Final Inventory URL: $this->final_data_inventory_address <br>\n");
            }
            //error_log("Final Inventory URL: $this->final_data_inventory_address <br>\n");
         }
         $this->urls_finalized = 1;
      }
   }
   
   function processLocalExtras() {
      // this is just a stub
   }
   
   function appendExtrasToURL($url, $extra_variables) {
      $extras = preg_split("/((\r(?!\n))|((?<!\r)\n)|(\r\n))/", $this->subLocalProperties($extra_variables)); 
      $url = ltrim(rtrim($url));
      $udel = '';
      $callpieces = preg_split('[\/]', $url);
      $lastpiece = $callpieces[(count($callpieces) - 1)];
      if (!strpos($lastpiece, '?')) {
         // this URL has not parameters appended to it, so we have to add the '?' before appending parameters
         $url .= '?';
      } else {
         if (substr($url,-1) <> '?') {
            // the URL already contains a ? AND has a parameter after the ?, so set the delimiter to '&'
            $udel = '&';
         }
      }
      foreach ($extras as $thisextra) {
         if (strlen(ltrim(rtrim($thisextra))) > 0) {
            $url .= $udel . ltrim(rtrim($thisextra));
            $udel = '&';
         }
      }
      $final_url = $url;
      if ($this->debug) {
         $this->logDebug("Final Query URL: $url <br>\n");
      }
      //error_log("Final Query URL: $url <br>\n");
      return $url;
   }

   function createQuery() {
      // dummy function, not needed in XML object
   }
   
}

class CBPDataConnection extends dataConnectionObject {
   # this is temporary, later, it will be a strictly XML affair, but for now, I am hardwiring this as a postgresql object
   # with some special add-ons to make auto-population easier
   var $scid = -1;
   var $id1 = ''; # model data class: river, land, or met
   var $id2 = ''; # model segment: i.e., PU2_2790_3290
   var $max_memory_values = 1000;
   var $username = 'cbp_ro';
   var $password = 'CbPF!v3';
   var $dbname = 'cbp';
   var $host = 'localhost';
   
   function init() {
      $this->reCreate();
      parent::init();
   }
   
   function wake() {
      $this->reCreate();
      parent::wake();
   }
   
   function reCreate() {
      $this->createQuery();
      parent::reCreate();
   }
   
   function step() {
      parent::step();
   }

   function showHTMLInfo() {
      $htmlinfo = parent::showHTMLInfo();

      # puts columns from query into state array
      list($river, $segid, $destid) = explode('_', $this->id2);
      $seginfo = getCBPSegmentLanduse($this->dbobject, $this->scid, $segid);
      if (!is_object($this->dbobject)) {
         $this->setupDBConn(1);
      }
      if (is_object($this->dbobject)) {
         $this->dbobject->queryrecords = $seginfo['local_annual'];
         $this->dbobject->show = 0;
         $this->dbobject->showList();
         //$htmlinfo .= "Segment Land Uses: <br>$this->scid, $segid :<br>" . print_r($seginfo,1);
         $htmlinfo .= "<hr>Local Annual Land Uses:<br>" . $this->dbobject->outstring;
         $this->dbobject->queryrecords = $seginfo['contrib_annual'];
         $this->dbobject->showList();
      }
      $htmlinfo .= "<hr>Total Upstream Land Uses:<br>" . $this->dbobject->outstring;
      return $htmlinfo;
   }
   
   function createQuery() {

      $this->sql_query = "  select a.td as thisdate, ";
      $this->sql_query .= "    CASE WHEN b.meanvalue is NULL THEN 0.0 ";
      $this->sql_query .= "       ELSE b.meanvalue ";
      $this->sql_query .= "    END as ivol, ";
      $this->sql_query .= "    CASE WHEN c.meanvalue is NULL THEN 0.0 ";
      $this->sql_query .= "       ELSE c.meanvalue ";
      $this->sql_query .= "    END as ovol, ";
      $this->sql_query .= "    CASE WHEN d.meanvalue is NULL THEN 0.0 ";
      $this->sql_query .= "       ELSE d.meanvalue ";
      $this->sql_query .= "    END as oheat, ";
      $this->sql_query .= "    CASE WHEN e.meanvalue is NULL THEN 0.0 ";
      $this->sql_query .= "       ELSE e.meanvalue";
      $this->sql_query .= "    END as iheat, ";
      $this->sql_query .= "    CASE WHEN f.meanvalue is NULL THEN 0.0 ";
      $this->sql_query .= "       ELSE f.meanvalue * 24.0 ";
      $this->sql_query .= "    END as prec , ";
      $this->sql_query .= "    CASE WHEN g.meanvalue is NULL THEN 0.0 ";
      $this->sql_query .= "       ELSE g.meanvalue * 24.0 ";
      $this->sql_query .= "    END as et ";
      // END - if using heat
      // this just gets any and all dates for this particular location id
      $this->sql_query .= " from (";
      $this->sql_query .= "    select date_trunc('day',thisdate) as td ";
      $this->sql_query .= "    from cbp_scenario_output where location_id in ";
      $this->sql_query .= "       (select location_id from cbp_model_location where id1 = '$this->id1' ";
      $this->sql_query .= "           and id2 = '$this->id2'";
      $this->sql_query .= "           and scenarioid = '$this->scid'";
      $this->sql_query .= "       ) ";
      $this->sql_query .= "       group by td order by td";
      $this->sql_query .= " ) as a left outer join ";
      // inflow (IVOL)
      $this->sql_query .= " (";
      $this->sql_query .= "    select date_trunc('day',thisdate) as td, avg(thisvalue) as meanvalue ";
      $this->sql_query .= "    from cbp_scenario_output where location_id in ";
      $this->sql_query .= "       (select location_id from cbp_model_location where id1 = '$this->id1' ";
      $this->sql_query .= "           and id2 = '$this->id2'";
      $this->sql_query .= "           and scenarioid = '$this->scid'";
      $this->sql_query .= "       ) ";
      $this->sql_query .= "       and param_name = 'IVOL' group by td order by td";
      $this->sql_query .= " ) as b  ";
      $this->sql_query .= " on ( a.td = b.td ) left outer join ";
      $this->sql_query .= " (";
      $this->sql_query .= "    select date_trunc('day',thisdate) as td, avg(thisvalue) as meanvalue ";
      $this->sql_query .= "    from cbp_scenario_output where location_id in ";
      $this->sql_query .= "       (select location_id from cbp_model_location where id1 = '$this->id1' ";
      $this->sql_query .= "           and scenarioid = '$this->scid'";
      $this->sql_query .= "           and id2 = '$this->id2'";
      $this->sql_query .= "    ) ";
      $this->sql_query .= "    and param_name = 'OVOL' group by td order by td";
      $this->sql_query .= " ) as c ";
      $this->sql_query .= " on ( a.td = c.td ) ";
      $this->sql_query .= " left outer join ";
      // if using heat
      $this->sql_query .= " (";
      $this->sql_query .= "    select date_trunc('day',thisdate) as td, avg(thisvalue) as meanvalue ";
      $this->sql_query .= "    from cbp_scenario_output where location_id in ";
      $this->sql_query .= "       (select location_id from cbp_model_location where id1 = '$this->id1' ";
      $this->sql_query .= "           and id2 = '$this->id2'";
      $this->sql_query .= "           and scenarioid = '$this->scid'";
      $this->sql_query .= "    ) ";
      $this->sql_query .= "    and param_name = 'OHEAT' group by td order by td";
      $this->sql_query .= " ) as d ";
      $this->sql_query .= " on ( a.td = d.td ) ";
      $this->sql_query .= " left outer join ";
      // if using heat
      $this->sql_query .= " (";
      $this->sql_query .= "    select date_trunc('day',thisdate) as td, avg(thisvalue) as meanvalue ";
      $this->sql_query .= "    from cbp_scenario_output where location_id in ";
      $this->sql_query .= "       (select location_id from cbp_model_location where id1 = '$this->id1' ";
      $this->sql_query .= "           and id2 = '$this->id2'";
      $this->sql_query .= "           and scenarioid = '$this->scid'";
      $this->sql_query .= "    ) ";
      $this->sql_query .= "    and param_name = 'IHEAT' group by td order by td";
      $this->sql_query .= " ) as e ";
      $this->sql_query .= " on ( a.td = e.td ) ";
      // END - if using heat
      // if using precip
      $this->sql_query .= " left outer join ";
      $this->sql_query .= " (";
      $this->sql_query .= "    select date_trunc('day',thisdate) as td, avg(thisvalue) as meanvalue ";
      $this->sql_query .= "    from cbp_scenario_output where location_id in ";
      $this->sql_query .= "       (select location_id from cbp_model_location where id1 = '$this->id1' ";
      $this->sql_query .= "           and id2 = '$this->id2'";
      $this->sql_query .= "           and scenarioid = '$this->scid'";
      $this->sql_query .= "    ) ";
      $this->sql_query .= "    and param_name = 'PREC' group by td order by td";
      $this->sql_query .= " ) as f ";
      $this->sql_query .= " on ( a.td = f.td ) ";
      // END - if using precip
      // if using et
      $this->sql_query .= " left outer join ";
      $this->sql_query .= " (";
      $this->sql_query .= "    select date_trunc('day',thisdate) as td, avg(thisvalue) as meanvalue ";
      $this->sql_query .= "    from cbp_scenario_output where location_id in ";
      $this->sql_query .= "       (select location_id from cbp_model_location where id1 = '$this->id1' ";
      $this->sql_query .= "           and id2 = '$this->id2'";
      $this->sql_query .= "           and scenarioid = '$this->scid'";
      $this->sql_query .= "    ) ";
      $this->sql_query .= "    and param_name in ('PETINP', 'POTEV') group by td order by td";
      $this->sql_query .= " ) as g ";
      $this->sql_query .= " on ( a.td = g.td ) ";
      // END - if using et
      $this->sql_query .= " order by a.td ";
      //error_log($this->sql_query);
      parent::reCreate();
   }


   function chooseLocalColumns() {
      // this version returns all columns
      $public_cols = '*';
      $group_cols = '';
      $this->logDebug("Columns for local query: $public_cols, $group_cols <br>\n");
      return array($public_cols, $group_cols);
   }
}

class CBPDataInsert extends modelObject {
   // time series for inserting data into CBP mode container table
   // users and db admins should be very judicious about using these, as they involve having a username and 
   // password for an account that has write access to one or more tables.   In this specific case, they only 
   // have write access to one table, cbp_scenario_output
   function wake() {
      parent::wake();
   }
   
   function init() {
      parent::init();
   }   
}

class dynamicQuerySubComponents extends modelSubObject {
   var $serialist = 'params,children,request_properties,proplist,keeplist';
   var $params = array();
   var $children = array();
   var $request_properties = array();
   var $sql = '';
   var $properties = array();
   var $keeplist = array();
   var $proplist = array();
   var $refresh = 0; // 0 - only gather data on create() method, 1 - only gather new data when preparing for run
   var $use_spatial = 1; // whether or not to include a containment
   
   function wake() {
      parent::wake();
      if (!is_array($this->params)) {
         $this->params = array();
      }
   }
   
   function create() {
       parent::create();
      $this->assembleChildQuery();
	}
	   
   
   function init() {
      parent::init();
	  switch ($this->refresh) {
	     case 1:
		 $this->assembleChildQuery();
		 break;
	  }
   }


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
         $innerHTML .= showHiddenField("numrows", $this->numrows, 1);
         $aset = $this->listobject->adminsetuparray[get_class($this)];
         $refresh_vars = extract_arrayvalue($this->keeplist, 'localvar');
         //$refresh_vars = array();
         //$innerHTML .= "Screening Vars: " . print_r($this->keeplist,1) . "<br>";
         //$innerHTML .= "Extracted Refresh Vars: " . print_r($refresh_vars,1) . "<br>";
         $innerHTML .= "Final Query: " . $this->sql . "<br>";
         foreach (array_keys($aset['column info']) as $tc) {
            $props[$tc] = $this->getProp($tc);
            // if the variable is a screening criteria variable, we will default to refreshing, i.e. calling reCreate() method
            /*
            if (in_array($tc, $refresh_vars)) {
               $aset['column info'][$tc]['onchange'] = "document.forms[\"$formname\"].elements.callcreate.value=1;  ";
            }
            */
         }
         $formatted = showFormVars($this->listobject,$props,$aset,0, 1, 0, 0, 1, 0, -1, NULL, 1);
         
         //$innerHTML .= "<table><tr><td>";
         $innerHTML .= $this->showFormHeader($formatted,$formname, $disabled );
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormBody($formatted,$formname, $disabled );
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormFooter($formatted,$formname, $disabled );
         //$innerHTML .= "</td></tr></table>";
         
         // show the formatted matrix
         //$this->formatMatrix();
         //$innerHTML .= print_r($this->matrix_formatted,1) . "<br>";

         $returnInfo['innerHTML'] = $innerHTML;

         return $returnInfo;

      }
   }
   
   function showFormHeader($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . " | ";
      $innerHTML .= "<b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'] . " | ";
      $innerHTML .= "<b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'];
      return $innerHTML;
   }
   
   function showFormBody($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<table><tr>";
      $innerHTML .= "<td valign=top>";
      $innerHTML .= "<b>Children:</b> <br>";
      $this->listobject->queryrecords = $this->children;
      $this->listobject->show = 0;
      $this->listobject->showList();
      $innerHTML .= $this->listobject->outstring;
      $innerHTML .= "Childs:" . print_r( $this->children, 1) . "<br>";
      $innerHTML .= "Params ". print_r( $this->children, 1) . "<br>";
      $innerHTML .= "<br><b>Scenarioid:</b> " . $formatted->formpieces['fields']['scenarioid'];
      $innerHTML .= "<br><b>custom1:</b> " . $formatted->formpieces['fields']['custom1'];
      $innerHTML .= "</td>";
      return $innerHTML;
   }
   
   function showFormFooter($formatted,$formname, $disabled = 0) {
      $innerHTML = '';
      /*
      $innerHTML .= $formatted->formpieces['fields']['enable_cfb'];
      $innerHTML .= " Set flowby to " . $formatted->formpieces['fields']['cfb_var'];
      $innerHTML .= " if " . $formatted->formpieces['fields']['cfb_condition'];
      $innerHTML .= " calculated flow-by <br>";
      */
      return $innerHTML;
   }
   
   function assembleChildQuery() {
      $p = array();
      //error_log("assembleChildQuery called on $this->name "); 
      //error_log("Assembling Child Query");
      foreach ($this->params as $thisone) {
         if (isset($this->$thisone)) {
            $p[$thisone] = $this->$thisone;
         }
      }
      $this->children = array();
      $els = array();
      if (function_exists('getSpatiallyContainedObjects') and is_object($this->parentobject)) {
         $res = getSpatiallyContainedObjects($this->parentobject->componentid, $p, $this->use_spatial);
         $els = $res['elements'];
         $this->sql = $res['query'];
         //error_log("$this->name Query: $this->sql ");
         if ($this->debug) {
            $this->logDebug("<br>getSpatiallyContainedObjects query:" . $this->sql . "<br>");
         }
         //error_log("getSpatiallyContainedObjects returned: " . count($els) . " objects."); 
      } else {
         if ($this->debug) {
            $this->logDebug("<br>Error in assembleChildQuery<br>");
            if (!function_exists('getSpatiallyContainedObjects')) {
               $this->logDebug("Failed Test: function_exists('getSpatiallyContainedObjects') " . function_exists('getSpatiallyContainedObjects') . "<br>");
            }
            if (! is_object($this->parentobject) ) {
               $this->logDebug("Failed Test: is_object(this->parentobject) = " . is_object($this->parentobject) . "<br>");
            }
            $this->logDebug("Will not Perform Query<br>");
         }
      }
     // error_log("function_exists('getSpatiallyContainedObjects') = " . function_exists('getSpatiallyContainedObjects') . "and is_object(this->parentobject) = " . is_object($this->parentobject));
      
      foreach ($els as $thisel) {
         $this->children[] = $thisel['elementid'];
      }
      //error_log("Children = " . count($this->children) . " objects."); 
      $this->proplist = array();
      foreach ($this->children as $thischild) {
         $childval = getElementPropertyValue($this->listobject, $thischild, $this->request_properties);
         //if (!is_array($childval)) {
            $this->proplist[$thischild] = $childval;
            //error_log("Children props ($thischild): " . print_r($childval,1));
         //} else {
         //   foreach ($childval as $key => $val) {
         //      $this->proplist[$thischild . "_" . $key] = $val;
         //   }
         //}
      }
      $this->screenProperties();
   }
   
   function screenProperties() {
      // further screen the obtained data for inclusion/exclusion
      $pl = array();
      //error_log("Keep list: " . print_r($this->keeplist,1));
      $i = 0;
      if (count($this->keeplist) > 0) {
         foreach ($this->proplist as $key => $props) {
            $keep = 1;
            $i++;
            //error_log("RECORD $i ");
            foreach ($this->keeplist as $propkey => $params) {
               $localvar = trim($params['localvar']);
               $op = trim($params['op']);
               //error_log("Comparing record $propkey to local property $localvar with operator $op");
               // does the desired property even exist on the variable?  If not, discard
               if (isset($props[$propkey])) {
                  $localval = $this->getProp($localvar);
                  /*
                  if (is_array($localval)) {
                     error_log("$propkey = " . $props[$propkey] . " $localvar = " . print_r($localval,1));
                  } else {
                     error_log("$propkey = " . $props[$propkey] . " $localvar = " . $localval);
                  }
                  */
                  switch (strtolower($op)) {
                     case 'na':
                     // not applicable, do not apply this condition
                     break;
                     
                     case 'eq':
                     if (is_array($localval)) {
                        if (! ( in_array($props[$propkey], $localval) or (count($localval) == 0) or (in_array('',$localval)) ) ) {
                           // do not keep it 
                           $keep = 0;
                           //error_log("Discarding");
                        }
                     } else {
                        if (! ( ($props[$propkey] == $localval) or (trim($localval) == '') ) ) {
                           // do not keep it 
                           $keep = 0;
                           //error_log("Discarding");
                        }
                     }
                     break;
                     
                     case 'gt':
                     if (! ( ($props[$propkey] > $localval) or (trim($localval) == '') ) ) {
                        // do not keep it 
                        $keep = 0;
                           //error_log("Discarding");
                     }
                     break;
                     
                     case 'lt':
                     if (! ( ($props[$propkey] < $localval) or (trim($localval) == '') ) ) {
                        // do not keep it 
                        $keep = 0;
                           //error_log("Discarding");
                     }
                     break;
                     
                     case 'ge':
                     if (! ( ($props[$propkey] >= $localval) or (trim($localval) == '') ) ) {
                        // do not keep it 
                        $keep = 0;
                           //error_log("Discarding");
                     }
                     break;
                     
                     case 'le':
                     if (! ( ($props[$propkey] <= $localval) or (trim($localval) == '') ) ) {
                        // do not keep it 
                        $keep = 0;
                           //error_log("Discarding");
                     }
                     break;
                     
                     default:
                     if (! ( ($props[$propkey] == $localval) or (trim($localval) == '') ) ) {
                        // do not keep it 
                        $keep = 0;
                           //error_log("Discarding");
                     }
                     break;
                  }
               } else {
                  // this next comparator assumes that we discard if there is no matching property to evaluate
                  $keep = 0;
                  //error_log("Discarding");
               }
            }
            if ($keep) {
               $pl[$key] = $props;
            }
         }
         $this->proplist = $pl;
      }
   }
   
   function addParams($these = array()) {
      //error_log("Trying to add: " . print_r($these,1));
      //error_log("To: " . print_r($this->params,1));
      if (!is_array($these)) {
         $these = array($these);
      }
      $this->params = array_unique(array_merge($this->params, $these));
      //error_log("New Parameter list: " . print_r($this->params,1));
   }


}

class queryWizardComponent extends modelSubObject {

   var $goutdir = '';
   var $gouturl = '';
   var $imgurl = '';
   var $maxreportrecords = 100; 
   # maximum number of records to actually show in the report string, above this defers to downloadable file
   var $value_dbcolumntype = 'varchar'; // just a dummy since, we should not even need to dump this to a db table, since it is meaningless
   var $quote_tablename = 0; // if we have unorthodox tale names, we may 
   //need to quote them, but defalt to not, since we may be given a 
   //sub-query as a table, in which case quoting would goof it up
   # query specific properties
   # column props
   #var $qcols = array();
   #var $qcols_func = array();
   #var $qcols_alias = array();
   var $qcols;
   var $qcols_func;
   var $qcols_alias;
   var $qcols_txt; # text entry column, now it is manually entered, but this is unsafe, so we should soon make it a wizard type column
   # column props
   var $wcols = array();
   var $wcols_op = array();
   var $wcols_value = array();
   var $wcols_refcols = array();
   # order by props
   var $ocols = array();
   var $loggable = 0;
   // data source info
   var $is_datasource = 1;
   var $datasource_name = 'parent.publicvars';
   var $tablename = '';
   var $sqlstring = '';


   # requires lib_plot.php to produce graphic output

   function setState() {
      parent::setState();
      /*
      $this->qcols = array('name','Qout','Qin');
      $this->qcols_func = array('','min','min');
      $this->qcols_alias = array('a','b','c');
      */
      $this->reportstring = '';
   }

   function wake() {
      parent::wake();
      if (is_object($this->parentobject)) {
         $this->tablename = $this->parentobject->dbtblname;
      } else {
         $this->tablename = '';
      }
      /*
      $this->qcols = array('name','Qout','Qin');
      $this->qcols_func = array('','min','min');
      $this->qcols_alias = array('a','b','c');
      */
   }

   function step() {
      // all step methods MUST call getInputs(), execProcessors(),  and logstate()
      parent::step();
   }

   function evaluate() {
      # do nothing
      $this->result = NULL;
   }

   function finish() {
      //$this->runAndDisplayResult()
   }

   function runAndDisplayResult() {
      # create query string, need to know parent name, or simply pass the SQL to the parent. hmm. which to do?
      $this->assembleQuery();

      if ($this->debug) {
         $this->reportstring .= $this->sqlstring . "<br>";
      }
      if (is_object($this->listobject)) {
         $this->listobject->queryrecords = $this->executeQuery();
         $this->listobject->show = 0;
         $this->listobject->showList();
         $filename = $this->list2file($this->listobject->queryrecords);
         #$filename = $this->list2file(array());
         if (count($this->listobject->queryrecords) <= $this->maxreportrecords) {
            $this->reportstring .= "<b>Query Output:</b><br> " . $this->listobject->outstring . "<br>";
         } else {
            $this->listobject->queryrecords = array_slice($this->listobject->queryrecords, 0, $this->maxreportrecords - 1);
            $this->listobject->show = 0;
            $this->listobject->showList();
            $this->reportstring .= "<b>Query Output:</b><br> " . $this->listobject->outstring . "<br><b>Max display records exceeded, download file (below) to view results.<br>";
         }
         $this->reportstring .= "<b>Download Data:</b><br> <a href=" . $this->outurl . '/' . $filename . ">Click Here</a><br>";
      }
   }
   
   function executeQuery() {

      if (is_object($this->listobject)) {
         if ($this->listobject->tableExists( $this->tablename)) {
            $this->listobject->querystring = $this->sqlstring;
            $this->listobject->performQuery();
            $theserecs = $this->listobject->queryrecords;
          } else {
            $this->reportstring .= "<b>Query Error:</b><br> Query output requested on $this->name, but $this->tablename dioes not exist. ";
            $this->reportstring .= "Be sure that 'Log to db' is selected in parent object: " . $this->parentobject->name . " <br>";
         }
      } else {
         $theserecs = array();
         $this->reportstring .= "<b>Query Error:</b><br> Query output requested on $this->name, but listobject not enabled. ";
         $this->reportstring .= "Be sure that 'Log to db' is selected in parent object: " . $this->parentobject->name . " <br>";
      }
      
      /*
      if (is_object($this->listobject) and $this->parentobject->log2db) {
         $this->listobject->querystring = $this->sqlstring;
         $this->listobject->performQuery();
         $theserecs = $this->listobject->queryrecords;
      } else {
         $theserecs = array();
         $this->reportstring .= "<b>Query Error:</b><br> Query output requested on $this->name, but listobject not enabled. ";
         $this->reportstring .= "Be sure that 'Log to db' is selected in parent object: " . $this->parentobject->name . " <br>";
      }
      */
      
      return $theserecs;
   }

   function assembleQuery() {
      $sqlstring = '';
      //$this->reportstring .= "Checking table nameL: $this->tablename <br>";
      if ($this->tablename <> '') {
         $ptable = $this->tablename;
      } else {
         $ptable = $this->parentobject->dbtblname;
      }

      # selected columns and any applied functions
      $selstring = '';
      $groupstring = '';
      $sdel = '';
      $gdel = '';
      # make sure we have all entries needed (even if blank)
      $scolumns = array(
         'qcols'=>$this->qcols,
         'qcols_func'=>$this->qcols_func,
         'qcols_alias'=>$this->qcols_alias,
         'qcols_txt'=>$this->qcols_txt
      );
      $tpa = $this->makeAssoc($scolumns);
      $qcolumns = $tpa['assoc_out'];
      foreach ($qcolumns as $thiscol) {
         $qcol = $thiscol['qcols'];
         $qfunc = $thiscol['qcols_func'];
         $qalias = $thiscol['qcols_alias'];
         $qtxt = $thiscol['qcols_txt'];
         $grpcol = $qcol; # assume we use the name, unless we have an alias
         if (strlen($qcol) > 0) {
            //list($funcopen, $funcclose) = $this->getFunctionFormat($qfunc);
            //$selstring .= "$sdel $funcopen \"$qcol\" $funcclose ";
            //error_log("calling formatFunctionFull($qfunc, $qcol, $qtxt) ");
            $formatted = $this->formatFunctionFull($qfunc, $qcol, $qtxt);
            //error_log("returned $formatted from formatFunctionFull ");
            $selstring .= "$sdel " . $formatted;
            $grpcol = $formatted;
            $sdel = ',';
            /*q
            if (strlen($qalias) > 0) {
               $selstring .= " AS \"$qalias\"";
               //$grpcol = "\"$qalias\"";
            }
            */
            if (strlen($qalias) == 0) {
               $pieces = array($qcol);
               if ( (strlen($qfunc) > 0) and ($qfunc <> 'none')) {
                  $pieces[] = $qfunc;
               }
               $qalias = implode("_", $pieces);
            }
            $selstring .= " AS \"$qalias\"";
            if ( !$this->isAggregate($qfunc) ) {
               $groupstring .= "$gdel $grpcol ";
               $gdel = ',';
            }
         }
      }

      # condition clause
      $wdel = '';
      $wherestring = '';
      $wcolumns = array(
         'wcols'=>$this->wcols,
         'wcols_op'=>$this->wcols_op,
         'wcols_value'=>$this->wcols_value
      );
      $tpa = $this->makeAssoc($wcolumns);
      #$this->reportstring .= $tpa['debugstring'];
      $wcolumns = $tpa['assoc_out'];
      foreach ($wcolumns as $thiscol) {
         $wcol = $thiscol['wcols'];
         $wop = $thiscol['wcols_op'];
         $wval = $thiscol['wcols_value'];
         #$this->reportstring .= "Evaluating WHERE data: $wcol, $wop, $wval<br>";
         if (strlen($wcol) > 0) {
            # must have all three, a colunn, an operator, and a comparison value
            if (strlen($wop) > 0) {
               # Format this clause
               $subclause = $this->formatWhereClause($wop, $wcol, $wval);
               if (strlen($subclause) > 0) {
                  $wherestring .= "$wdel $subclause ";
                  $wdel = 'AND';
               }
            }
         }
      }
      
      if (ltrim(rtrim($selstring)) == '') {
         $selstring = '*';
      }
      $this->sqlstring = "SELECT $selstring FROM ";
      if ($this->quote_tablename) {
         $this->sqlstring .= " \"$ptable\" ";
      } else {
         $this->sqlstring .= " $ptable ";
      }
      if (strlen($wherestring) > 0) {
         $this->sqlstring .= " WHERE $wherestring ";
      }
      if (strlen($groupstring) > 0) {
         $this->sqlstring .= " GROUP BY $groupstring ";
      }
      $odel = '';
      $orderstring = '';
      
      if (!is_array($this->ocols) ) {
         if (strlen(rtrim(ltrim($this->ocols))) > 0) {
            $ocols = array($this->ocols);
         } else {
            $ocols = array();
         }
      } else {
         $ocols = $this->ocols;
      }
      if (count($ocols) > 0) {

         foreach ($ocols as $ocol) {
            if (strlen(rtrim(ltrim($ocol))) > 0) {
               $orderstring .= " $odel \"$ocol\"";
               $odel = ',';
            }
         }
      }
      
      if (strlen($orderstring) > 0) {
         $this->sqlstring .= " ORDER BY $orderstring ";
      }
   }
   
   function isAggregate($func) {
      
      $isagg = 0;
      switch ($func) {

         case 'none':
            $isagg = 0;
         break;

         case '':
            $isagg = 0;
         break;
         
         default:
            $isagg = 1;
         break;
         
      } 
      return $isagg;
   }
   
   function formatFunctionFull($func, $col, $params) {
      
      $formatted = '';
      if (function_exists('sanitize_sql_string')) {
         $paramsan = sanitize_sql_string($params);
      } else {
         $paramsan = '0';
      }
      $ps = explode(",", $paramsan);
      switch ($func) {

         case 'none':
            if ($ps[0] <> '') {
               $formatted = "round( \"$col\"::numeric, " . intval($ps[0]) . " ) ";
            } else {
               $formatted = " \"$col\" ";
            }
         break;

         case '':
            if ($ps[0] <> '') {
               $formatted = "round( \"$col\"::numeric, " . intval($ps[0]) . " ) ";
            } else {
               $formatted = " \"$col\" ";
            }
         break;
         
         case 'min':
            if ($ps[0] <> '') {
               $formatted = "round(min( \"$col\" )::numeric, " . intval($ps[0]) . " ) ";
            } else { 
               $formatted = "min( \"$col\" ) ";
            }
         break;

         case 'mean':
            if ($ps[0] <> '') {
               $formatted = "round(avg( \"$col\" )::numeric, " . intval($ps[0]) . " ) ";
            } else { 
               $formatted = "avg( \"$col\" ) ";
            }
         break;

         case 'median':
            if ($ps[0] <> '') {
               $formatted = "round(median( \"$col\" )::numeric, " . intval($ps[0]) . " ) ";
            } else { 
               $formatted = "median( \"$col\" ) ";
            }
         break;

         case 'max':
            if ($ps[0] <> '') {
               $formatted = "round(max( \"$col\" )::numeric, " . intval($ps[0]) . " ) ";
            } else { 
               $formatted = "max( \"$col\" ) ";
            }
         break;

         case 'gini':
            $formatted = "gini(array_accum( \"$col\" ) ) ";
         break;

         case 'quantile':
            switch (count($ps)) {
               case 2:
               $formatted = "round(r_quantile(array_accum( \"$col\" ), " . $ps[0] . ")::numeric, " . $ps[1] . " ) ";
               break;
               
               case 1:
               $formatted = "r_quantile(array_accum( \"$col\" ), " . $ps[0] . " ) ";
               break;
               
               default:
               $formatted = "r_quantile(array_accum( \"$col\" ), 0.5 ) ";
               break;
            }
         break;

         case 'sum':
            if ($ps[0] <> '') {
               $formatted = "round(sum( \"$col\" )::numeric, " . intval($ps[0]) . " ) ";
            } else { 
               $formatted = "sum( \"$col\" ) ";
            }
         break;

         case 'count':
            $formatted = "count( \"$col\" ) ";
         break;

         case 'stddev':
            if ($ps[0] <> '') {
               $formatted = "round(stddev( \"$col\" )::numeric, " . intval($ps[0]) . " ) ";
            } else { 
               $formatted = "stddev( \"$col\" ) ";
            }
         break;
         
         default:
            $formatted .= " $func ( \"$col\" ) ";
         break;
      }
      
      return $formatted;
   }

   function formatWhereClause($wop, $wcol, $wval) {
      $whereclause = '';

      //if (!is_numeric($wval)) {
      //   $wval = "'" . $wval . "'";
      //}

      switch ($wop) {
         case 'notnull':
            $whereclause = "\"$wcol\" IS NOT NULL ";
         break;
         
         case 'in':
            $whereclause = "\"$wcol\" IN ( $wval ) ";
         break;

         default:
            $whereclause = "\"$wcol\" $wop $wval ";
         break;
      }

      return $whereclause;
   }

   function getFunctionFormat($func) {

      $fopen = '';
      $fclose = '';

      switch ($func) {
         case 'min':
            $fopen = 'min(';
            $fclose = ')';
         break;

         case 'mean':
            $fopen = 'avg(';
            $fclose = ')';
         break;

         case 'median':
            $fopen = 'median(';
            $fclose = ')';
         break;

         case 'max':
            $fopen = 'max(';
            $fclose = ')';
         break;

         case 'gini':
            $fopen = 'gini(array_accum(';
            $fclose = '))';
         break;

         case 'sum':
            $fopen = 'sum(';
            $fclose = ')';
         break;

         case 'count':
            $fopen = 'count(';
            $fclose = ')';
         break;

         case 'stddev':
            $fopen = 'stddev(';
            $fclose = ')';
         break;

         default:
         break;
      }

      return array($fopen, $fclose);
   }


   function showEditForm($formname, $disabled=0) {
      if (is_object($this->listobject)) {

         $returnInfo = array();
         $returnInfo['name'] = $this->name;
         $returnInfo['description'] = $this->description;
         $returnInfo['debug'] = '';
         $returnInfo['elemtype'] = get_class($this);

         $props = (array)$this;
         $innerHTML = '';
         $adminsetuparray = $this->listobject->adminsetuparray;
         $innerHTML .= showFormVars($this->listobject,$props,$adminsetuparray[$returnInfo['elemtype']],1, 0, $this->debug, 0, 1, $disabled, $this->fno);

         # now, show the meta columns (select, where, group, order)

         # 1 - columns to select
         $coladmin = $adminsetuparray['queryWizard_selectcolumns'];
         # set the column names from the selected data source
         //$asparams = $this->formatSelectList($thispname, $parent_props);
         //$coladmin['column info']['qcols']['params'] = $asparams;
         //$coladmin['column info']['datasource']['onchange'] = "xajax_showOperatorEditResult(xajax.getFormValues(\"$formname\"));";

         # set the name of the form for reference by xajax
         $coladmin['table info']['formName'] = $formname;
         $parentname = $formname . "_selectcolumns";
         $coladmin['table info']['parentname'] = $parentname;
         $childname = $formname . "_selectid";
         $coladmin['table info']['childname'] = $childname . '[]';
         $showlabels = $coladmin['table info']['showlabels'];
         
         
         $selectcolumns = array(
            'qcols'=>$this->qcols,
            'qcols_func'=>$this->qcols_func,
            'qcols_alias'=>$this->qcols_alias,
            'qcols_txt'=>$this->qcols_txt
         );
         $tpa = $this->makeAssoc($selectcolumns);
         $cols = $tpa['assoc_out'];
         # set up div to contain each seperate multi-parameter block
         $innerHTML .= showHiddenField("xajax_removeitem", -1, 1);
         $innerHTML .= "<b>SELECT:</b><div name='$parentname' id='$parentname'>";
         if (count(array_keys($cols)) > 0) {
            foreach (array_keys($cols) as $thisrowkey) {
               $thisrow = $cols[$thisrowkey];
               //$innerHTML .= "<div name='$childname"."[$thisrowkey]' id='$childname"."[$thisrowkey]'>";
               //$innerHTML .= showFormVars($this->listobject,$thisrow,$coladmin,$showlabels, 0, $this->debug, 1, 1, $disabled, $this->fno, $thisrowkey);
               $innerHTML .= "<div name='$childname"."[]' id='$childname"."[]'>";
               $innerHTML .= showFormVars($this->listobject,$thisrow,$coladmin,$showlabels, 0, $this->debug, 1, 1, $disabled, $this->fno, '');
               $innerHTML .= "</div>";
            }
         } else {
            # if none exist, add a blank line in
            $innerHTML .= "<div name='$childname"."[]' id='$childname"."[]'>";
            $innerHTML .= showFormVars($this->listobject,$thisrow,$coladmin,$showlabels, 0, $this->debug, 1, 1, $disabled, $this->fno, '');
            $innerHTML .= "</div>";
         }
         $innerHTML .= "</div>";

         # 2 - where conditions
         $coladmin = $adminsetuparray['queryWizard_wherecolumns'];

         # set the name of the form for reference by xajax
         $coladmin['table info']['formName'] = $formname;
         $parentname = $formname . "_wherecolumns";
         $coladmin['table info']['parentname'] = $parentname;
         $childname = $formname . "_whereid";
         $coladmin['table info']['childname'] = $childname . '[]';
         $showlabels = $coladmin['table info']['showlabels'];
         $wherecolumns = array(
            'wcols'=>$this->wcols,
            'wcols_op'=>$this->wcols_op,
            'wcols_value'=>$this->wcols_value
         );
         $tpa = $this->makeAssoc($wherecolumns);
         $cols = $tpa['assoc_out'];
         # set up div to contain each seperate multi-parameter block
         $innerHTML .= "<b>WHERE:</b><div name='$parentname' id='$parentname'>";
         if (count(array_keys($cols)) > 0) {
            foreach (array_keys($cols) as $thisrowkey) {
               $thisrow = $cols[$thisrowkey];
               $innerHTML .= "<div name='$childname"."[]' id='$childname"."[]'>";
               $innerHTML .= showFormVars($this->listobject,$thisrow,$coladmin,$showlabels, 0, $this->debug, 1, 1, $disabled, $this->fno, '');
               $innerHTML .= "</div>";
            }
         } else {
            # if none exist, add a blank line in
            $innerHTML .= "<div name='$childname"."[]' id='$childname"."[]'>";
            $innerHTML .= showFormVars($this->listobject,$thisrow,$coladmin,$showlabels, 0, $this->debug, 1, 1, $disabled, $this->fno, '');
            $innerHTML .= "</div>";
         }
         $innerHTML .= "</div>";

         # 3 - order columns
         $coladmin = $adminsetuparray['queryWizard_ordercolumns'];

         # set the name of the form for reference by xajax
         $coladmin['table info']['formName'] = $formname;
         $parentname = $formname . "ordercolumns";
         $coladmin['table info']['parentname'] = $parentname;
         $childname = $formname . "_orderid";
         $coladmin['table info']['childname'] = $childname . '[]';
         $showlabels = $coladmin['table info']['showlabels'];
         $ordercolumns = array(
            'ocols'=>$this->ocols
         );
         $tpa = $this->makeAssoc($ordercolumns);
         $cols = $tpa['assoc_out'];
         #$innerHTML .= $tpa['debugstring'];
         # set up div to contain each seperate multi-parameter block
         $innerHTML .= "<b>ORDER BY:</b><div name='$parentname' id='$parentname'>";
         if (count(array_keys($cols)) > 0) {
            foreach (array_keys($cols) as $thisrowkey) {
               $thisrow = $cols[$thisrowkey];
               $innerHTML .= "<div name='$childname"."[]' id='$childname"."[]'>";
               $innerHTML .= showFormVars($this->listobject,$thisrow,$coladmin,$showlabels, 0, $this->debug, 1, 1, $disabled, $this->fno, '');
               $innerHTML .= "</div>";
            }
         } else {
            # if none exist, add a blank line in
            $innerHTML .= "<div name='$childname"."[]' id='$childname"."[]'>";
            $innerHTML .= showFormVars($this->listobject,$thisrow,$coladmin,$showlabels, 0, $this->debug, 1, 1, $disabled, $this->fno, '');
            $innerHTML .= "</div>";
         }
         $innerHTML .= "</div>";

         $returnInfo['innerHTML'] = $innerHTML;

         return $returnInfo;

      }
   }
   
   function showWhereFields($formname, $disabled=0) {
      # 2 - where conditions
      $adminsetuparray = $this->listobject->adminsetuparray;
      $innerHTML = '';
      $coladmin = $adminsetuparray['queryWizard_wherecolumns'];

      # set the name of the form for reference by xajax
      $coladmin['table info']['formName'] = $formname;
      $parentname = $formname . "_wherecolumns";
      $coladmin['table info']['parentname'] = $parentname;
      $childname = $formname . "_whereid";
      $coladmin['table info']['childname'] = $childname . '[]';
      $showlabels = $coladmin['table info']['showlabels'];
      $wherecolumns = array(
         'wcols'=>$this->wcols,
         'wcols_op'=>$this->wcols_op,
         'wcols_value'=>$this->wcols_value
      );
      $tpa = $this->makeAssoc($wherecolumns);
      $cols = $tpa['assoc_out'];
      # set up div to contain each seperate multi-parameter block
      $innerHTML .= "<b>WHERE:</b><div name='$parentname' id='$parentname'>";
      if (count(array_keys($cols)) > 0) {
         foreach (array_keys($cols) as $thisrowkey) {
            $thisrow = $cols[$thisrowkey];
            $innerHTML .= "<div name='$childname"."[]' id='$childname"."[]'>";
            $innerHTML .= showFormVars($this->listobject,$thisrow,$coladmin,$showlabels, 0, $this->debug, 1, 1, $disabled, $this->fno, '');
            $innerHTML .= "</div>";
         }
      } else {
         # if none exist, add a blank line in
         $innerHTML .= "<div name='$childname"."[]' id='$childname"."[]'>";
         $innerHTML .= showFormVars($this->listobject,$thisrow,$coladmin,$showlabels, 0, $this->debug, 1, 1, $disabled, $this->fno, '');
         $innerHTML .= "</div>";
      }
      $innerHTML .= "</div>";
      return $innerHTML;
   }
   
   function formatSelectList($thispname, $parent_props) {
   // takes an array of column names and formats them into a suitable syntax for a select list in adminsetup format

      if ($this->debug) {
         $this->logDebug("Formatting Property List: " . print_r($parent_props,1) . "<br>");
      }
      
      $asep = '';
      $aslist = '';
      foreach ($parent_props as $thisprop) {
         if ($thisprop <> '') {
            $aslist .= $asep . $thisprop . '|' . $thisprop;
            $asep = ',';
         }
      }
      $asparams = $aslist . ":$thispname" . "id:$thispname::0";
      
      if ($debug) {
         $this->logDebug("$thispname -&lt; $thisptype = " . print_r($parent_props, 1) . "asrec = ($aslist) " . $asparams . "<br>");
      }
      
      return $asparams;
   }

   function makeAssoc($invars) {
      $outarr = array();
      $debugout = '';
      $assoc_out = array();
      $maxcols = 0; # keep a tally on columns, make sure that a blank entry exists for any missing columns
      $ivnames = array_keys($invars);
      sort($ivnames);
      foreach ($ivnames as $varname) {
         $debugout .= "Transposing $varname <br>";
         $varvals = $invars[$varname];
         if (!is_array($varvals)) {
            $varvals = array($varvals);
         }
         $valcount = 0;
         foreach (array_keys($varvals) as $thiskey) {
            $assoc_out[$thiskey][$varname] = $varvals[$thiskey];
            $valcount++;
         }
         if ($valcount > $maxcols) {
            $maxcols = $valcount;
         }
      }
      # insert blank entries if needed
      foreach (array_keys($invars) as $varname) {
         $thiscount = count($invars[$varname]);
         if ($thiscount < $maxcols) {
            for ($i = 0; $i < $maxcols; $i++) {
               $assoc_out[$i][$varname] = '';
            }
         }
      }
      $outarr['assoc_out'] = $assoc_out;
      $outarr['debugstring'] = $debugout;
      return $outarr;
   }

   function getPropertyClass($propclass) {
      # Call parent method to get any standard classes matching the criteria
      $returnprops = parent::getPropertyClass($propclass);
      foreach ($propclass as $thisclass) {

         switch ($thisclass) {

            case 'data_columns':
            $selectcolumns = array(
               'qcols'=>$this->qcols,
               'qcols_func'=>$this->qcols_func,
               'qcols_alias'=>$this->qcols_alias,
               'qcols_txt'=>$this->qcols_txt
            );
            $tpa = $this->makeAssoc($selectcolumns);
            $props = array();
            foreach ($tpa as $thisrow) {
               if ($thisrow['qcols_alias'] == '') {
                  if ( in_array($thisrow['qcols_func'], array ('none', '')) ) {
                     $val = $thisrow['qcols'];
                  } else {
                     $val = $thisrow['qcols_func'];
                  }
               } else {
                  $val = $thisrow['qcols_alias'];
               }
               $props[] = $val;
            }
            $returnprops = array_unique(array_merge($returnprops, $props));
            break;

            case 'broadcast_vars':
            $returnprops = array_unique(array_merge($returnprops, $this->broadcast_varname));
            break;

         }
      }
      #$returnprops = $this->plotgens;
      return $returnprops;
   }
}

class graphObject extends modelObject {

   var $goutdir = '';
   var $gouturl = '';
   var $cache_log = 1; # store variable state log in an external file?  Defaults to 1 for graph and report objects, 0 for others
   var $log2db = 1;
   var $imgurl = '';
   var $gwidth = 400;
   var $gheight = 300;
   var $title = '';
   var $xlabel = '';
   var $ylabel = '';
   var $y2label = '';
   var $forceyscale = 0; # whether or not we insist on setting a scale
   var $ymin = '';
   var $ymax = '';
   var $y2min = '';
   var $y2max = '';
   var $scale = 'intlin';
   var $labelangle = 90;
   var $x_interval = -1; # this forces the routine to do an autocalculation of interval unless it is set by user
   var $num_xlabels = 5; # this forces the routine to use the # set by user
   var $graphtype = 'line';
   var $graphstring = '';
   var $adminsetup = array();
   var $restrictdates = 0; # whether or not to use the whole data record, or to restrict the dates

   # requires lib_plot.php to produce graphic output

   function setState() {
      parent::setState();
   }

   function reDraw() {

      if ($this->debug) {
         $this->logDebug("Redraw method called on $this->name<br>");
      }
      if (!$this->logLoaded) {
         #need to get the log from a table or file
         $this->logFromFile();
      }

      $this->graphResults();
      if ($this->debug) {
         $this->logDebug("<br>Image at: $this->imgurl<br>");
      }
      #$this->logDebug($this->logtable);

   }

   function step() {
      // all step methods MUST call getInputs(), execProcessors(), and logstate()
      parent::step();
   }

   function finish() {
      # simulation finished, go ahead and generate any graphs
      $this->graphResults();
      parent::finish();
   }

   function formatActiveGraph() {
      $this->graphstring = '';
      $eid = $this->componentid;
      # use a javascript trick to relaod the image to prevent caching
      $reload_js = "refreshimage(\"img" . $eid . "\");";
      $img_name = "img$eid";
      #return;
      if (is_object($this->listobject)) {
         $taboutput = new tabbedListObject;
         if (is_object($taboutput)) {
            /*
            $taboutput->name = "modelgraph_$eid";
            $taboutput->tab_names = array("graphs_$eid", "el_ctrl_$eid", 'debug');
            $taboutput->tab_buttontext = array(
               "graphs_$eid"=>'Graphs',
               "el_ctrl_$eid"=>'Graph Data',
               'debug'=>'Debug'
            );
            $taboutput->init();
            $taboutput->tab_HTML["graphs_$eid"] .= "<img id='$img_name' ";
            $taboutput->tab_HTML["graphs_$eid"] .= " title='Click to Reload'";
            $taboutput->tab_HTML["graphs_$eid"] .= " onClick='$reload_js'";
            $taboutput->tab_HTML["graphs_$eid"] .= " src='" . $this->imgurl . "'><br>";
            $taboutput->tab_HTML["el_ctrl_$eid"] .= "<a onclick=\"dg_move_file(tt1,11 , './objectlog.785.792.log.serial' )\">Scroll Through Model Data</a>";
            $taboutput->tab_HTML["el_ctrl_$eid"] .= "<b>Model Controls:</b><br>";
            $taboutput->createTabListView();
            $this->graphstring .= "<div>" . $taboutput->innerHTML . "</div>";
            if ($this->debug) {
               $this->logDebug("Finished graphing $this->name");
            }            
            */
            // new school way of showing the graph
            $this->graphstring .= "<a class='mH' onClick='document[\"image_screen\"].src = \"" . $this->imgurl . "\"; '>$this->name - $this->title</a> | ";
            $this->graphstring .= "<a href='" . $this->imgurl . "' target='_new'>View Image in New Window</a><br>";
         } else {
            if ($this->debug) {
               $this->logDebug("Using Simple Display for $this->name");
            }
            /*
            $this->graphstring .= "<img id='$img_name' ";
            $this->graphstring .= " title='Click to Reload'";
            $this->graphstring .= " onClick='$reload_js'";
            $this->graphstring .= " src='" . $this->imgurl . "'><br>";
            */
            // new school way of showing the graph
            $this->graphstring .= "<a class='mH' onClick='document[\"image_screen\"].src = \"" . $this->imgurl . "\"; '>$this->name - $this->title</a> | ";
            $this->graphstring .= "<a href='" . $this->imgurl . "' target='_new'>View Image in New Window</a><br>";
         }
      } else {
         # do nothing but put out the graph
         /*
         $this->graphstring .= "<img id='$img_name' ";
         $this->graphstring .= " title='Click to Reload'";
         $this->graphstring .= " onClick='$reload_js'";
         $this->graphstring .= " src='" . $this->imgurl . "'><br>";
         */
         if ($this->debug) {
            $this->logDebug("Listobject not set for $this->name");
         }
         // new school way of showing the graph
         $this->graphstring .= "<a class='mH' onClick='document[\"image_screen\"].src = \"" . $this->imgurl . "\"; '>$this->name - $this->title</a> | ";
         $this->graphstring .= "<a href='" . $this->imgurl . "' target='_new'>View Image in New Window</a><br>";
      }
   }


   function graphResults() {
// RWB disabled until upgrade is complete
return;
      if ($this->debug) {
         $this->logDebug("graphResults() called on $this->name<br>");
      }

      # set up basic info
      $thisgraph = array();

      $thisgraph['title'] = $this->title;
      $thisgraph['xlabel'] = $this->xlabel;
      $thisgraph['ylabel'] = $this->ylabel;
      $thisgraph['y2label'] = $this->y2label;
      $thisgraph['gwidth'] = $this->gwidth;
      $thisgraph['gheight'] = $this->gheight;
      $thisgraph['x_interval'] = $this->x_interval;
      //$thisgraph['num_xlabels'] = $this->num_xlabels;
      $thisgraph['scale'] = $this->scale;
      if ($this->forceyscale and ($this->ymin <> $this->ymax)) {
         # use a custom scale
         $thisgraph['ymin'] = $this->ymin;
         $thisgraph['ymax'] = $this->ymax;
      }
      $thisgraph['labelangle'] = $this->labelangle;
      $thisgraph['filename'] =  'graph.' . $this->sessionid . '.' . $this->componentid;

      $i = 0;
      #$this->logDebug("<br>Processors:<br>");
      #$this->logDebug($this->processors);
      #$this->logDebug("<br>");
      $logtab = $this->getLog();
      $vars = array_keys($logtab[0]);
      if ($this->debug) {
         $this->logDebug("My data_log variables contained: " . print_r($this->data_cols,1) . "<br>");
         $this->logDebug("getLog() returned " . count($logtab) . "<br>");
         $this->logDebug("getLog() Columns: " . print_r(array_keys($logtab[0]),1) . "<br>");
      }
      
      foreach ($this->processors as $thiscol) {
         if (!$thiscol->disabled) {
            $obclass = get_class($thiscol);
            // to check to make sure that the variable is present (and avoid a crash) use the next line
            //if ( in_array($obclass, array('graphComponent')) and in_array($thiscol, $vars)) {
            if ( in_array($obclass, array('graphComponent')) ) {
               $xcol = $thiscol->xcol;
               $graphtab = $logtab;
               if ($thiscol->sorted) {
                  # this object requires a post-process sorting
                  if (strlen($thiscol->sortcol) == 0) {
                     $thiscol->sortcol = $xcol;
                  }
                  if (strlen($thiscol->sortcol)) {
                     # sortcol must be set
                     $sortkeys = array();
                     $sortrecs = array();
                     $scol = $thiscol->sortcol;
                     $isort = 0;
                     $icount = count($logtab);
                     foreach ($logtab as $logrec) {
                        # add an index column to be used as the Xcolumn value
                        $logrec['sortindex'] = number_format(100.0*$isort/$icount,0);
                        array_push($sortkeys, $logrec[$scol]);
                        $sortrecs["$logrec[$scol]"] = $logrec;
                        $isort++;
                     }
                     ksort($sortrecs);
                  }
                  $graphtab = $sortrecs;
                  $xcol = 'sortindex';
               }
               $thisgraph['bargraphs'][$i]['graphrecs'] = $graphtab;
               $thisgraph['bargraphs'][$i]['xcol'] = $xcol;
               $thisgraph['bargraphs'][$i]['plottype'] = $thiscol->graphtype;
               if (strlen($thiscol->ycol) > 0) {
                  # using the new way
                  $thisgraph['bargraphs'][$i]['ycol'] = $thiscol->ycol;
               } else {
                  $thisgraph['bargraphs'][$i]['ycol'] = $thiscol->name;
               }
               $thisgraph['bargraphs'][$i]['color'] = $thiscol->color;
               $thisgraph['bargraphs'][$i]['weight'] = $thiscol->weight;
               $thisgraph['bargraphs'][$i]['yaxis'] = $thiscol->yaxis;
               $thisgraph['bargraphs'][$i]['ylegend'] = $thiscol->ylegend;
               #$this->logDebug("Adding graph $i <br>");
               $i++;
            }
         }
      }
      #$this->debug = 1;
      if ($this->debug) {
         $this->logDebug("Drawing $this->graphtype $this->goutdir, $this->gouturl:<br>");
         //$this->logDebug(print_r($thisgraph,1) . "<br>");
      }
      // something in this next block is triggering an error
      //it seems to be with the postgresql query object, which makes no sense but there is a 
      // call to the getColumns() method of the pg object, or some other thing that is doing a 
      // select column_name from 
      switch ($this->graphtype) {
         case 'line':
         $thisimg = showGenericMultiPlot($this->goutdir, $this->gouturl, $thisgraph, $this->debug);
//         $thisimg = showGenericMultiLine($this->goutdir, $this->gouturl, $thisgraph, $this->debug);
         $this->imgurl = $thisimg;
         $this->formatActiveGraph();
         break;
         
         case 'multi':
         $thisimg = showGenericMultiPlot($this->goutdir, $this->gouturl, $thisgraph, $this->debug);
         $this->imgurl = $thisimg;
         $this->formatActiveGraph();
         break;

         default:
         $thisimg = showGenericMultiPlot($this->goutdir, $this->gouturl, $thisgraph, $this->debug);
//         $thisimg = showGenericMultiLine($this->goutdir, $this->gouturl, $thisgraph, $this->debug);
         $this->imgurl = $thisimg;
         $this->formatActiveGraph();
         break;
      }
   }
   
   function showElementInfo($propname = '', $view = 'info', $params = array()) {
      $localviews = array();
      $output = '';
      
      if ($propname == '') {
         if (in_array($view, $localviews)) {
            switch ($view) {

               case 'graph':
               $this->reDraw();
               $output .= $this->graphString;
               break;

            }
         } else {
            $output .= parent::showElementInfo($propname, $view, $params);
         }
      }
      return $output;
   }

}

class giniGraph extends graphObject {

   var $goutdir = '';
   var $gouturl = '';
   var $imgurl = '';
   var $gwidth = 400;
   var $gheight = 300;
   var $title = '';
   var $xlabel = '';
   var $ylabel = '';
   var $y2label = '';
   var $scale = 'intlin';
   var $labelangle = 90;
   var $splitmedian = 0; # 0 - do not, 1 - analyze curves above and below median seperately
   var $graphtype = 'line';

   function graphResults() {
// RWB disabled until upgrade is complete
return;

      # set up basic info
      $thisgraph = array();

      $thisgraph['title'] = $this->title;
      $thisgraph['xlabel'] = $this->xlabel;
      $thisgraph['ylabel'] = $this->ylabel;
      $thisgraph['y2label'] = $this->y2label;
      $thisgraph['gwidth'] = $this->gwidth;
      $thisgraph['gheight'] = $this->gheight;
      $thisgraph['scale'] = $this->scale;
      $thisgraph['labelangle'] = $this->labelangle;
      $thisgraph['filename'] =  'graph.' . $this->sessionid . '.' . $this->componentid;

      $i = 0;
      #$this->logDebug("<br>Processors:<br>");
      #$this->logDebug($this->processors);
      #$this->logDebug("<br>");
      $logtab = $this->getLog();
      foreach ($this->processors as $thiscol) {
         $obclass = get_class($thiscol);
         if ( in_array($obclass, array('graphComponent')) ) {

            $xcol = $thiscol->xcol;
            # gini is automatically assumed to be sorted by the y-column
            $sortkeys = array();
            $sortrecs = array();
            if (strlen($thiscol->ycol) > 0) {
               # we are using the new way of operating
               $scol = $thiscol->ycol;
            } else {
               $scol = $thiscol->name;
            }
            $isort = 0;
            $gtotal = 0;
            $gtotalhi = 0;
            $gtotallo = 0;
            $icount = count($logtab);
            $xmedian = intval($icount / 2);
            foreach ($logtab as $logrec) {
               $isort++;
               # accumulate the population total
               $gtotal += $logrec[$scol];
               $sortindex = $logrec[$scol];
               # add an index column to be used as the Xcolumn value
               $logrec['sortindex'] = number_format(100.0*$isort/$icount,0);
               array_push($sortkeys, $logrec[$scol]);
               #$sortrecs["$sortindex"] = $logrec;
               array_push($sortrecs, $logrec[$scol]);
            }
            #ksort($sortrecs);
            sort($sortrecs);
            if ($this->splitmedian) {
               # do seperate accumulators for the portion above and below median
               $isort = 0;
               foreach ($sortrecs as $logrec) {
                  $isort++;
                  if ($isort > $xmedian) {
                     #$gtotalhi += $logrec[$scol];
                     $gtotalhi += $logrec;
                  } else {
                     #$gtotallo += $logrec[$scol];
                     $gtotallo += $logrec;
                  }
               }
            }
            if ($this->debug) {
               $this->debugstring .= "Total Records in " . $scol . " = $icount<br>";
               $this->debugstring .= "Sorted Record count = " . count($sortrecs) . "<br>";
            }
            # now, calculate gini factors and normalize on the total
            $gxn = 1.0;
            $gyn = 0.0;
            $gynhi = 0;
            $gynlo = 0;
            $lgx = 0;
            $lgy = 0;
            $ga = 0;
            $ginirec = array();
            # for split analysis
            $gahi = 0;
            $galo = 0;
            $ginihi = array();
            $ginilo = array();
            # the line representing a gini of 1.0 (equal distribution)
            $oneline = array();
            $onehi = array();
            $onelo = array();
            reset($sortrecs);
            foreach ($sortrecs as $thisrec) {
               # calculate percentage of population
               if (!$this->splitmedian) {
                  $gx = round($gxn / $icount,2);
                  #$gyn += $thisrec[$scol];
                  $gyn += $thisrec;
                  # calculate percentage of total value
                  $gy = $gyn / $gtotal;
                  $ga += 0.5 * ($gy + $lgy) * ($gx - $lgx);
                  if ($this->debug) {
                     $this->debugstring .= "Evaluating Record " . $gxn . " = $gx %, value = $gy<br>";
                  }
                  array_push($ginirec, array('gx'=>$gx, 'gy'=>$gy));
                  array_push($oneline, array('gx'=>$gx, 'gy'=>$gx));
               } else {
                  # doing a split gini at the median value
                  if ($gxn > $xmedian) {
                     $gx = number_format(($gxn - $xmedian) / ($icount - $xmedian),2);
                     #$gynhi += $thisrec[$thiscol->name];
                     $gynhi += $thisrec;
                     $gy = $gynhi / $gtotalhi;
                     if ($lgx > $gx) {
                        $lgx = 0;
                     }
                     $gahi += 0.5 * ($gy + $lgy) * ($gx - $lgx);
                     array_push($ginihi, array('gx'=>$gx, 'gy'=>$gy));
                     array_push($onelo, array('gx'=>$gx, 'gy'=>$gx));
                  } else {
                     $gx = number_format($gxn / $xmedian,2);
                     #$gynlo += $thisrec[$thiscol->name];
                     $gynlo += $thisrec;
                     $gy = $gynlo / $gtotallo;
                     $galo += 0.5 * ($gy + $lgy) * ($gx - $lgx);
                     array_push($ginilo, array('gx'=>$gx, 'gy'=>$gy));
                     array_push($onehi, array('gx'=>$gx, 'gy'=>$gx));
                  }
               }
               # stash the last x value
               $lgx = $gx;
               $lgy = $gy;
               $gxn++;
            }
            # calculate gini coefficient
            $g = number_format(1 - 2.0 * $ga,3);
            $thiscol->value = $g;
            $ghi = number_format(1 - 2.0 * $gahi,3);
            $glo = number_format(1 - 2.0 * $galo,3);
            if (!$this->splitmedian) {
               $thisgraph['bargraphs'][$i]['graphrecs'] = $ginirec;
               $thisgraph['bargraphs'][$i]['xcol'] = 'gx';
               $thisgraph['bargraphs'][$i]['ycol'] = 'gy';
               $thisgraph['bargraphs'][$i]['weight'] = $thiscol->weight;
               $thisgraph['bargraphs'][$i]['color'] = $thiscol->color;
               $thisgraph['bargraphs'][$i]['yaxis'] = $thiscol->yaxis;
               $thisgraph['bargraphs'][$i]['ylegend'] = $thiscol->ylegend . " (G = $g)";
               #$this->logDebug("Adding graph $i <br>");
               $i++;
            } else {
               $thisgraph['bargraphs'][$i]['graphrecs'] = $ginihi;
               $thisgraph['bargraphs'][$i]['xcol'] = 'gx';
               $thisgraph['bargraphs'][$i]['ycol'] = 'gy';
               $thisgraph['bargraphs'][$i]['weight'] = $thiscol->weight;
               $thisgraph['bargraphs'][$i]['color'] = $thiscol->color;
               $thisgraph['bargraphs'][$i]['yaxis'] = $thiscol->yaxis;
               $thisgraph['bargraphs'][$i]['ylegend'] = $thiscol->ylegend . " - Upper (G = $ghi)";

               #$this->logDebug("Adding graph $i <br>");
               $i++;
               $thisgraph['bargraphs'][$i]['graphrecs'] = $ginilo;
               $thisgraph['bargraphs'][$i]['xcol'] = 'gx';
               $thisgraph['bargraphs'][$i]['ycol'] = 'gy';
               $thisgraph['bargraphs'][$i]['weight'] = $thiscol->weight;
               $thisgraph['bargraphs'][$i]['color'] = $thiscol->color;
               $thisgraph['bargraphs'][$i]['yaxis'] = $thiscol->yaxis;
               $thisgraph['bargraphs'][$i]['ylegend'] = $thiscol->ylegend . " - Lower (G = $glo)";
               #$this->logDebug("Adding graph $i <br>");
               $i++;
               $oneline = $onelo;
            }

         }
      }

      # now, add the one-one line
      $thisgraph['bargraphs'][$i]['graphrecs'] = $oneline;
      $thisgraph['bargraphs'][$i]['xcol'] = 'gx';
      $thisgraph['bargraphs'][$i]['ycol'] = 'gy';
      $thisgraph['bargraphs'][$i]['color'] = 'gray';
      $thisgraph['bargraphs'][$i]['yaxis'] = 1;
      $thisgraph['bargraphs'][$i]['ylegend'] = '';
      #$this->logDebug("Adding graph $i <br>");
      $i++;

      #$this->debug = 1;
      switch ($this->graphtype) {
         case 'line':
         $thisimg = showGenericMultiLine($this->goutdir, $this->gouturl, $thisgraph, $this->debug);
         $this->imgurl = $thisimg;
         $this->formatActiveGraph();
         break;

         default:
         $thisimg = showGenericMultiLine($this->goutdir, $this->gouturl, $thisgraph, $this->debug);
         $this->imgurl = $thisimg;
         $this->formatActiveGraph();
         break;
      }
   }

}

class flowDurationGraph extends graphObject {

   var $goutdir = '';
   var $gouturl = '';
   var $imgurl = '';
   var $gwidth = 400;
   var $gheight = 300;
   var $title = '';
   var $xlabel = '';
   var $ylabel = '';
   var $y2label = '';
   //var $scale = 'intlin';
   var $scale = 'linlog';
   var $flowstat = 0; # 0 - exceedance percentage, 1 - recurrence interval
   var $normalize = 0; # normalize graph components on max, to show relative to max value (0 - 1.0)
   var $labelangle = 90;
   var $splitmedian = 0; # 0 - do not, 1 - analyze curves above and below median seperately
   var $graphtype = 'line';

   function graphResults() {
// RWB disabled until upgrade is complete
return;
      # set up basic info
      $thisgraph = array();

      $thisgraph['title'] = $this->title;
      $thisgraph['xlabel'] = $this->xlabel;
      $thisgraph['ylabel'] = $this->ylabel;
      $thisgraph['y2label'] = $this->y2label;
      $thisgraph['gwidth'] = $this->gwidth;
      $thisgraph['gheight'] = $this->gheight;
      $thisgraph['scale'] = $this->scale;
      //$thisgraph['scale'] = 'linlin';
      //error_reporting(E_ALL);
      $thisgraph['labelangle'] = $this->labelangle;
      $thisgraph['filename'] =  'graph.' . $this->sessionid . '.' . $this->componentid;

      $i = 0;
      #$this->logDebug("<br>Processors:<br>");
      #$this->logDebug($this->processors);
      #$this->logDebug("<br>");
      $logtab = $this->getLog();
      $mingtzero = 0.001;
      $ymax = 0.001;
      $ymin = 0.001;
      foreach ($this->processors as $thiscol) {
         $obclass = get_class($thiscol);
         if ( in_array($obclass, array('graphComponent')) ) {
            $xcol = $thiscol->xcol;
            # gini is automatically assumed to be sorted by the y-column
            $sortkeys = array();
            $sortrecs = array();
            if (strlen($thiscol->ycol) > 0) {
               # we are using the new way of operating
               $scol = $thiscol->ycol;
            } else {
               $scol = $thiscol->name;
            }

            if ($this->debug) {
               $this->logDebug("Preparing $thiscol->name for graphing<br>");
            }
            $isort = 0;
            $icount = count($logtab);
            foreach ($logtab as $logrec) {
               $isort++;
               array_push($sortrecs, $logrec[$scol]);
               if ( ($logrec[$scol] > 0) and ($logrec[$scol] < $mingtzero) ) {
                  $mingtzero = $logrec[$scol] / 2.0;
               }
            }

            if ($this->debug) {
               $this->logDebug("Settng 0.0 records to $mingtzero to permit graphing on log scale<br>");
            }
            $gmax = max($sortrecs);
            if ($gmax > $ymax) {
               $ymax = $gmax;
            }
            // replaces each zero value with the minimum greater than zero value divided by 2.0 (from above)
            foreach ($sortrecs as $thiskey=>$thisval) {
               if ($thisval == 0) {
                  $sortrecs[$thiskey] = $mingtzero;
               }
            }
            $gmin = min($sortrecs);
            if ($gmin < $ymin) {
               $ymin = $gmin;
            }            
               
            switch ($this->flowstat) {
               case 0:
               # exceedence percent;
               rsort($sortrecs);
               break;

               case 0:
               # reccurence percent;
               sort($sortrecs);
               break;
            }

            if ($this->debug) {
               $this->logDebug("Total Records in " . $scol . " = $icount<br>");
               $this->logDebug("Sorted Record count = " . count($sortrecs) . "<br>");
               $this->logDebug("Max Value = " . $gmax . "<br>");
               $this->logDebug("Lowest values fixed at $gmin<br>");
               error_log("Lowest values fixed at $gmin<br>");
            }
            # now, calculate gini factors and normalize on the total
            $gxn = 1.0;
            $gyn = 0.0;
            $fdrec = array();
            reset($sortrecs);
            foreach ($sortrecs as $thisrec) {
               # calculate percentage of population
               $gx = round($gxn / $icount,2);
               switch ($this->flowstat) {
                  case 0:
                  # exceedence percent;
                  $gx = 1.0 - $gx;
                  break;

                  case 0:
                  # reccurence percent;
                  $gx = $gx;
                  break;
               }

               # calculates percent of max flow
               if ($gmax > 0 and $this->normalize) {
                  $gy = $thisrec / $gmax;
               } else {
                  $gy = $thisrec;
               }
               if ($this->debug) {
                  $this->logDebug("Evaluating Record " . $gxn . " = $gx %, value = $gy<br>");
               }
               array_push($fdrec, array('gx'=>$gx, 'gy'=>$gy));
               # stash the last x value
               $gxn++;
            }
            $thisgraph['ymin'] = $ymin;
            //$thisgraph['ymax'] = $ymax;
            # plot this curve
            $thisgraph['bargraphs'][$i]['graphrecs'] = $fdrec;
            $thisgraph['bargraphs'][$i]['xcol'] = 'gx';
            $thisgraph['bargraphs'][$i]['ycol'] = 'gy';
            $thisgraph['bargraphs'][$i]['weight'] = $thiscol->weight;
            $thisgraph['bargraphs'][$i]['color'] = $thiscol->color;
            $thisgraph['bargraphs'][$i]['yaxis'] = $thiscol->yaxis;
            $thisgraph['bargraphs'][$i]['ylegend'] = $thiscol->ylegend;
            #$this->logDebug("Adding graph $i <br>");
            $i++;
         }
      }
      $i++;

      #$this->debug = 1;
      
      switch ($this->graphtype) {
         case 'line':
         $thisimg = showGenericMultiLine($this->goutdir, $this->gouturl, $thisgraph, $this->debug);
         $this->imgurl = $thisimg;
         $this->formatActiveGraph();
         break;

         default:
         $thisimg = showGenericMultiLine($this->goutdir, $this->gouturl, $thisgraph, $this->debug);
         $this->imgurl = $thisimg;
         $this->formatActiveGraph();
         break;
      }
      
   }

}

class graphComponent extends modelSubObject {

   var $xcol = '';
   var $ycol = '';
   var $name = ''; #varuiable name
   var $yaxis = 1;
   var $ylegend = '';
   var $color = 'black';
   var $weight = 1;
   var $arData = array();
   var $cache_log = 1; # store variable state log in an external file?  Defaults to 1 for graph and report objects, 0 for others
   var $result = 0;
   var $sorted = 0; # whether or not to sort
   var $sortcol = '';
   var $graphtype = 'line';
   var $loggable = 0;

   # acts as sub-component for graphObject

   function evaluate() {
      $this->result = $this->arData[$this->name];
      return;
   }

}

class flowTransformer extends modelObject {
   # a type of processor
   # transforms flow from one or more basins, or gages, into a single flow value
   # for use as a paired watershed type of deal, or to extrapolate flow upstream in a basin
   # to a downstream point
   # expects inputs of flow from a variety of flow objects (may be time series, but must have
   # area property set).  Also expects that
   var $inputname = 'Qin'; # the name of the input that we will aggregate
   # requires the library lib_equation2.php, which has the class "Equation"
   var $method = 0; # 0 - area-weighted flow, 1 - mean value
   var $area = 0.0;

   function init() {
      parent::init();
      $this->state[$inputname] = 0.0;
   }

   function step() {
      // all step methods MUST call preStep(),execProcessors(), postStep()
      $this->preStep();
      if ($this->debug) {
         $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
      }
      // execute sub-components
      $this->execProcessors();
      # need to look through the inputs and weight their flow inputs
      # will ignore any input whose current state is NULL (i.e., does not have an input)
      # can use one of two weighting methods, weighted by flow
      $n = 0;
      $flow = 0;
      $area = $this->area;
      $activearea = 0.0;
      switch ($this->method) {
         case 0:
         $fin = $this->inputs['flow'];
         break;

         case 1:
         $fin = $this->inputs['flowpera'];
         break;

         case 2:
         # area weighted
         $fin = $this->inputs['flowtimesa'];
         break;
      }
      if ($this->debug) {
         $this->logDebug("Calculating Flow Transformation, method: $this->method .<br>");
      }

      foreach($fin as $thisinobj) {
         $fv = $thisinobj['value'];
         if ( !($fv === NULL) ) {
            if ($this->debug) {
               $this->logDebug(" + $fv ");
            }
            $flow += $fv;
            $n++;
         }
         if ($this->debug) {
            $this->logDebug(" = $flow (aggregate flow)<br>\n");
         }
      }
      # assumes that area has been manipulated such that it is NULL if the flow is null
      foreach($this->inputs['activearea'] as $thisinobj) {
         $av = $thisinobj['value'];
         if ( !($av === NULL) ) {
            $activearea += $av;
            if ($this->debug) {
               $this->logDebug(" = $activearea (aggregate area)<br>\n");
            }
         }
      }
      switch ($this->method) {
         case 0:
         if ($activearea > 0) {
            $outflow = $area * $flow / $activearea;
         } else {
            $outflow = $flow;
         }
         break;

         case 1:
         if ($n > 0) {
            $outflow = $area * $flow / $n;
         } else {
            $outflow = NULL;
         }
         break;

         case 2:
         if ($n > 0) {
            $outflow = $area * $flow / ($activearea * $activearea);
         } else {
            $outflow = NULL;
         }
         break;
      }
      if ($this->debug) {
         $this->logDebug(" Transformed flow = $outflow <br>\n");
      }

      $this->state['Qout'] = $outflow;
      # expects that the flow input objects, whether they are time series, or other
      # must have an "area" property signifying their drainage area (acres in EE, ha in SI)

      $this->postStep();
   }

}



class reverseFlowObject extends modelSubObject {
   var $base = 1.0; /* base width of channel */
   var $Z = 1.0; /* side slope ratio */
   var $length = 5000.0;
   var $slope = 0.0001;
   var $n = 0.002;
   var $Qvar = '';
   var $Qout = 0.0;
   var $units = 2;
   var $channeltype = 2; # only trapezoidal channels (type 2) are currently supported
   # tolerance for sollution to storage routing, percent as fraction
   var $tol = 0.001;
   # key is name, values are:
   #     priority (0 is highest priority),
   #     criteria - the name of the state variable that the criteria is based on
   #     withdrawals = array(criteriavalue, amount (volume per hour))

   function step() {
   //$this->debugmode = 1;
   //$this->debug = 1;
      // all step methods MUST call preStep(),execProcessors(), postStep()
      $this->preStep();
      if ($this->debug) {
         $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
         $this->logDebug("State variables: "  . print_r($this->state,1) . "<br>");
         $this->logDebug("arData variables: "  . print_r($this->arData,1) . "<br>");
      }
      // execute sub-components
      $this->execProcessors();
      $I1 = $this->state['Iold'];
      $O1 = $this->state['Qold'];
      if (isset($this->state['depth'])) {
         $last_depth = $this->state['depth'];
      } else {
         $last_depth = 0.1;
      }
      # this is obtained from the parent time series input
      $O2 = $this->arData[$this->Qvar];
      if ($this->debug) {
         $this->logDebug("$this->name Getting O2 from parent $this->Qvar = $O2 <br>");
      }
      $S1 = $this->state['Storage'];
      $initialStorage = $this->state['Storage'];
      $depth = $this->state['depth'];
      # assume demand = 0.0 for now
      $currentDemand = 0.0;

      # get time step from timer object
      $dt = $this->timer->dt;

      if ($this->timer->steps <= 3) {
         # first time, need to estimate initial storage,
         # assumes that we are in steady state, that is,
         # the initial and final Q, and S are equivalent
         $I2 = $O2;
         $I1 = $O2;
         $O1 = $O2;
         $last_depth = 0.1;
         if ($this->debug) {
            $this->logDebug("Estimating initial storage");
         }
         //$S1 = storageroutingInitS($I2, $this->base, $this->Z, $this->channeltype, $this->length, $this->slope, $dt, $this->n, $this->units, $this->debug);
         // getting initial d estimate
         list($V, $d, $A) = solveManningsWithQ($O1, $this->slope, $this->base, $this->n, $this->Z, 0.1, $this->units, 0);
         $S1 = $this->length * $A;
         if ($this->debug) {
            $this->logDebug("Initial storage estimated as $S1 = $this->length * $A = solveManningsWithQ($Q1, $this->slope, $this->base, $this->n, $this->Z, 0.1, $this->units, 0);<br>\n");
         }
      }

      if($this->debug) {
         $dtime = $this->timer->thistime->format('r');
         $this->logDebug("Calculating inflow at time $dtime <br>\n");
         $this->logDebug("Q1 = $O1, Q2 = $O2, I1 = $I1, last_depth = $last_depth, base = $this->base, Z = $this->Z, type = 2, S1 = $S1, length = $this->length, slope = $this->slope, $dt, n = $this->n <br>\n");
         #die;
      }
      # step iteration next - array($V2, $I2, $d2, $S2, $A2)
      list($Vout, $I2, $depth, $Storage, $A2, $Imean) = reverseStorageRouting($I1, $S1, $O1, $O2, $this->base, $this->Z, $this->length, $this->slope, $dt, $this->n, $this->tol, $this->units, $this->debug);
      
      if($this->debug) {
         $this->logDebug("Inflow estimated as $Imean (Vout = $Vout, I2 = $I2, depth = $depth, Storage = $Storage, A2 = $A2, Imean = $Imean)<br>\n");
         $teststore = storagerouting($I1, $I2, $O1, $currentDemand, $this->base, $this->Z, $this->channeltype, $Storage, $this->length, $this->slope, $dt, $this->n, $this->units, 0);
         $this->logDebug($teststore);
         #die;
      }

      $this->state['Vout'] = $Vout;
      $this->state['Qold'] = $O2;
      $this->state['Iold'] = $I2;
      $this->state['Qin'] = $Imean;
      # since we are using this as an input
      $this->state['depth'] = $depth;
      $this->state['Storage'] = $Storage;
      $this->state['demand'] = $currentDemand;

      $this->writeToParent();
      $this->postStep();
   }
   
   function wake() {
      parent::wake();
      //foreach($$this->setSingleDataColumnType($thiscol, 'float8', 0.0, 1);
   }
   
   function setState() {
      parent::setState();
      $this->rvars = array();
      $this->wvars = array('Qin');
      
      $this->initOnParent();
   }
}

class CBPModelContainer extends modelContainer {
   var $riversegs = array();
   var $landseg = array();
   var $autoloadriver = 0; # whether or not to auto-load any child river components
   var $autoloadland = 0; # whether or not to auto-load any child land components
   var $filepath = ''; # uci file of the farthest downstream component
   var $ucidir = ''; # directory path to outlet uci file
   var $uciname = ''; # file name of outlet uci
   var $createLog = ''; # logging info for the create() method
   var $defaultProcs = 'CBPModelReachFlow';
   var $wdimex_exe = '/Library/WebServer/CGI-Executables/wdimex';

   function wake() {
      parent::wake();
      $this->ucidir = dirname($this->filepath);
      $this->uciname = basename($this->filepath);
      if ($this->autoloadriver) {
         $this->getRiverSegs();
      }

   }

   function create() {
      # this method does any operations that are batched into this object
      # it must be called AFTER the wake() method, but prior to the init() method
      parent::create();
      $this->ucidir = dirname($this->filepath);
      $this->uciname = basename($this->filepath);
      # instantiate all riversegs and land segs
      $logstr = '';
      if ($this->autoloadriver) {
         $logstr .= 'Looking for riversegs<br>';
         $this->getRiverSegs();
      }
      # add the default CBP processors, if they do not already exist
      #   CBPModelReachFlow - separates upstream and local inflows from the WDM DSN 11 input
      #      this involves adding a WDMDSNaccessor for the local 11, and for each of the upstream 11's
      #      then adding an equation component that subtracts the upstream 11s from the local 11 to get the
      #      local upland contribution for this
      #      The most upstream segment will need to be instantiated first? or since we have a consistent naming
      #      convention, we can just trust that it WILL be added once we process all members of this group?
      #      This might hold true, but all of the upstream segments will still have to have an object instantiated on them
      #      Maybe go through all segments, create objects for them, add them to this objects CONTAINED entities
      #      Then go through and add all of the sub-components.  YES!!!
      #   CBPModelReachComponent - generic object that can be used for any of the inputs, flow, or quality
      # look for components on this object, if any of the defaults are missing, add them
      foreach (explode(',',$this->defaultProcs) as $thisdef) {
         foreach ($this->processors as $thisproc) {

         }
      }
      # for each riverseg, do any processors that have functions for this riverseg
      # this will add inputs for flow, and other quality constituents that are defined on this parent object
      # to each of the child riversegs
      $logstr .= "Examining Upstream Inputs to $this->uciname <br>";
      foreach ($this->riversegs as $thisreach) {
         # create it on the child
         $segid = $thisreach['segid'];
         $dsegid = $thisreach['down_segid'];
         $uci = $thisreach['uci'];
         $elementid = $thisreach['elementid'];
         $subobject = $thisreach['object'];
         # check on this objects CONTAINED sub-objects and see if it exists
         # if it does not exist, create it.  This simply creates the object in memory, in order to save this
         # newly created object for later editing, an OUTSIDE routine must be used, otherwise, this is only good
         # for the current instantiation (for example, if this were invoked during a model run call, the sub-objects
         # would be created and available only during the model run)
         if ($subobject === NULL) {
            $logstr .= "Creating Child object for Segment $segid<br>";
         }
         if ($dsegid <> -1) {
            $logstr .= "Adding upstream input from $segid to $dsegid<br>";
         }
         foreach ($this->processors as $thisproc) {
            # run the create routine on this
            # if this is a CBPComponentProc (such as flow, or qual constit)
            # create a version of it on the child
         }
      }
      $this->createLog .= $logstr;
   }

   function sleep() {
      # things to do before this goes away, or gets stored
      # turn the riversegs and landsegs arrays into strings?
      parent::sleep();
   }

   function showHTMLInfo() {
      $HTMLInfo = '';
      $HTMLInfo .= parent::showHTMLInfo();
      $HTMLInfo .= "<b>Outlet River Name: </b>" . $this->out_name . "<br>";
      $HTMLInfo .= "<b>Outlet Segment ID: </b>" . $this->out_segment . "<br>";
      $HTMLInfo .= "<b>Next Downstream Segment: </b>" . $this->down_segment . "<hr>";
      $HTMLInfo .= "<b>Found Model River Segments: </b>" . print_r($this->riversegs,1) . "<hr>";

      $HTMLInfo .= '<hr>' . $this->createLog;

      return $HTMLInfo;
   }

   function getRiverSegs() {

      if (file_exists($this->filepath)) {
         list($ucibase, $uciext) = explode('\.', $this->uciname);
         list($this->out_name, $this->out_segment, $this->down_segment) = explode("_", $ucibase);
         $this->riversegs = array();
         # set the down_segid to -1 since this is the outlet of this watershed
         $this->riversegs[$this->out_segment] = array('segid'=>$this->out_segment, 'uci'=>$this->uciname, 'down_segid'=>-1, 'elementid'=>-1, 'object'=>NULL);

         # repeat until no more upstream files are found
         $segsleft = array($this->out_segment);
         while(count($segsleft) > 0) {
            $currseg = array_shift($segsleft);
            $upfiles = $this->getUpstream($currseg);
            foreach ($upfiles as $thisfile) {
               list($ucibase, $uciext) = explode('\.', $thisfile);
               list($out_name, $out_segment, $down_segment) = explode("_", $ucibase);
               $this->riversegs[$out_segment] = array('segid'=>$out_segment, 'down_segid'=>$down_segment, 'uci'=>$thisfile,'elementid'=>-1, 'object'=>NULL);
               array_push($segsleft, $out_segment);
            }
         }

         # now, iterate through river segments, and check to see if they are already instantiated as objects under this
         # component -- should we do this here?  This might should be NOT the place to do this.  Hmmm ... maybe there should
         # be some other facility, in a model import script or something that creates these objects.
         # we can go a couple of routes here.  We could specify parameters of interest as components
         # of this container, then at runtime instantiate the necessary subwatershed reaches and landsegs, and then add the
         # appropriate operators on them.  For example:
         # specify - flow
         # instantiate - one HSPF container for each reach
         # add components at runtime - add components for upstream inflow, local inflow, flow out on each reach obbject
         # positives - we would maintain object nature of this routine, and cleanly define each reach
         # negatives - we would be unable to add static linkages to reach objects of interest from additional, user defined
         #    modeling objects

         # alternative #2
         # have a method called "create" which does specified tasks, called automatically when object is first created (or cloned)
         #    then have a button on the interface to force a re-creation.  This would enable us to re-specify included sub-objects
         #    (such as reaches, or landsegs), and enable us to specify consituents to be included in the
         #    HSPFcontainer as components that would propagate to the sub-objects on the re-creation.
         #    Might default to the creation of the basic constituents, such as flow, and perhaps DO.
         #    May consider having a function called reCreate() that defaults to calling create(), but could also include special
         #    behaviours for a given class.
      }
   }

   function getUpstream($segid) {
      $dirfiles = scandir($this->ucidir);
      $retfiles = array();
      foreach ($dirfiles as $thisfile) {
         if (strpos(strtolower($thisfile), '.uci')) {
            if (substr($thisfile, 9, 4) == $segid) {
               array_push($retfiles, $thisfile);
            }
         }
      }

      return $retfiles;
   }

}

class HSPFContainer extends modelContainer {
   var $state = array();
   var $sections = array();
   var $filepath = '';
   var $uciname = '';
   var $ucidir = '';
   var $uciobject;
   var $uciblocks;
   var $ucitables = array();
   var $blocks = array();
   var $subblocks = array();
   var $formats = array();
   var $errorstring = '';
   var $plotinfo = array();
   var $files = array();
   var $plotgens = array();
   var $plotgenfiles = array();
   var $wdms = array();
   var $wdm_dsns = array();
   var $listobject = -1;
   var $initialiazed = 0;
   var $parsed = 0;
   var $wdm_messagefile = '';
   var $hspf_timestep = 3600; # time step of HSPF model in seconds
   var $max_memory_values = -1; # passed to DSNs to cache data in order to save memory & speed execution
   var $wdimex_exe = '/Library/WebServer/CGI-Executables/wdimex';

   # requires the library lib_equation2.php, which has the class "Equation"
   var $equations = array(); # keyed by statevarname=>equationtext

   function wake() {
      parent::wake();
      $this->parseUCI();
   }

   function setCompID($thisid) {
      parent::setCompID($thisid);
   }
   
   function setState() {
      parent::setState();
      $this->state['hspf_timestep'] = $this->hspf_timestep;
      
   }
   
   function sleep() {
      parent::sleep();
      # things to do before this goes away, or gets stored
      $this->uciobject = NULL;
      $this->ucitables = array();
      $this->uciblocks = array();
      $this->sections = array();
      $this->formats = array();
      $this->blocks = array();
      $this->subblocks = array();
      $this->wdms = array();
      $this->wdm_dsns = array();
      $this->plotinfo = array();
      $this->files = array();
      $this->plotgens = array();
      $this->plotgenfiles = array();
   }

   function init() {
      
      if ( (!is_object($this->listobject)) or (strlen($this->filepath) == 0)) {
         $this->errorstring .= "You must set a UCI file, and a list object.<br>";
         if (!is_object($this->listobject)) {
            $this->errorstring .= "&nbsp;&nbsp;&nbsp;<i>List object missing.</i><br>";
         }
         if (strlen($this->filepath) == 0) {
            $this->errorstring .= "&nbsp;&nbsp;&nbsp;<i>Null File Path: $this->filepath </i>.<br>";
         }
      } else {
         if (!$this->parsed) {
            $this->parseUCI();
         }

         ############################################
         # now, create plotgen object for all files #
         ############################################
         if ($this->debug) {
            $this->logDebug("Checking for Plotgens ; <br>");
         }
         $this->listobject->querystring = "  select filepath, count(*) ";
         $this->listobject->querystring .= " from $this->tblprefix" . "tmp_allplotgens ";
         $this->listobject->querystring .= " group by filepath ";
         $this->listobject->performQuery();
         if ($this->debug) {
            $qs = $this->listobject->querystring;
            $this->logDebug("$qs ; <br>");
            $this->listobject->showList();
            $os = $this->listobject->outstring;
            $this->logDebug("$os ; <br>");
         }
         $this->plotgens = array();
         $pgnames = $this->listobject->queryrecords;
         $i = 0;
         #$this->debug = 1;
         #$this->debugmode = 1;
         foreach ($pgnames as $thisrec) {
            $pg_obj[$i] = new HSPFPlotgen;
            $pgparamcols = array();
            $fp = $thisrec['filepath'];
            /*
            if ($fp == 'RCHRES.localflow.1.out') {
               $pg_obj[$i]->debug = 1;
               $pg_obj[$i]->debugmode = 1;
               $pg_obj[$i]->loglines = 5;
            }
            */
            $pg_obj[$i]->filepath = dirname($this->filepath) . '/' . $fp;
            $pg_obj[$i]->tmpdir = $this->tmpdir;
            $pg_obj[$i]->wdm_messagefile = $this->wdm_messagefile;
            $this->listobject->querystring = "  select linkname, ssegno, linkgroup, targetmember, label, output_column ";
            $this->listobject->querystring .= " from $this->tblprefix" . "tmp_allplotgens ";
            $this->listobject->querystring .= " where filepath = '$fp' ";
            $this->listobject->performQuery();
            if ($this->debug) {
               $this->logDebug("Accessing plotgens on file $fp ; <br>");
               $qs = $this->listobject->querystring;
               $this->logDebug("$qs ; <br>");
               #$this->listobject->showList();
               #$os = $this->listobject->outstring;
               #$this->logDebug("$os ; <br>");
            }
            foreach ($this->listobject->queryrecords as $pgcol) {
               $pglabel = 'Plotgen - ' . $pgcol['linkname'] . " " . $pgcol['ssegno'] . " " . $pgcol['linkgroup'] . " " . $pgcol['targetmember'] . " " . $pgcol['label'] . "-" . $pgcol['output_column'];
               $this->addInput($pglabel, $pglabel, $pg_obj[$i]);
               $pgparamcols[$pglabel] = $pgcol['output_column'];
               if ($this->debug) {
                  $this->logDebug("Adding Plot label: $pglabel <br>");
                  $this->logDebug("Column:" . $pgcol['output_column'] . "<br>");
               }
            }
            $pg_obj[$i]->paramcolumns = $pgparamcols;
            if ($this->debug) {
               $this->logDebug("Param Columns on file $fp set to: <br>");
               $this->logDebug($pg_obj[$i]->paramcolumns);
               $this->logDebug("<br>");
               $this->logDebug("Param Columns on file $fp set to: <br>");
               $this->logDebug($pgparamcols);
               $this->logDebug("<br>");
               #$this->listobject->showList();
               #$os = $this->listobject->outstring;
               #$this->logDebug("$os ; <br>");
            }
            $pg_obj[$i]->setSimTimer($this->timer);
            $pg_obj[$i]->name = $fp;
            $pg_obj[$i]->debug = $this->debug;
            # set a unique id on this
            $pg_obj[$i]->componentid = $this->componentid . "_" . $i;
            #$this->errorstring .= $this->logDebug($thisrec,1);
            # must trigger init and setsimtimer from here, because these are hidden model components, and will
            # not be governed by the model container that calls the init process for all other components
            $this->addComponent($pg_obj[$i]);
            $i++;
         }
         #$this->debug = 0;
         #$this->debugmode = 0;

         ############################################
         # now, create WDM objects for all files    #
         ############################################

         # this creates WDM object shells, but does NOT enable any DSNs
         # users must now DEFINE accessible WDM components as individual sub-components
         # this will eliminate importing massive quantities of data that are not to be used
         $this->listobject->querystring = "  select filepath, handle, count(*) ";
         $this->listobject->querystring .= " from $this->tblprefix" . "tmp_allwdms ";
         $this->listobject->querystring .= " group by filepath, handle ";
         $this->listobject->performQuery();
         if ($this->debug) {
            $qs = $this->listobject->querystring;
            $this->logDebug("$qs ; <br>");
            $this->listobject->showList();
            $os = $this->listobject->outstring;
            $this->logDebug("$os ; <br>");
         }
         #$this->wdms = array();
         $pgnames = $this->listobject->queryrecords;
         # We started the count of components with the plotgens, so we need to keep these moving forward with the WDMs
         # so as not to overwrite the plotgens
         # $i = 0;
         $wdm_obj = array();
         #$this->debug = 1;
         #$this->debugmode = 1;
         foreach ($pgnames as $thisrec) {
            $wdm_obj[$i] = new HSPFWDM;
            
            $pgparamcols = array();
            $fp = $thisrec['filepath'];
            $wdmid = $thisrec['handle'];
            $wdm_obj[$i]->filepath = dirname($this->filepath) . '/' . $fp;
            $wdm_obj[$i]->wdimex_exe = $this->wdimex_exe;
            $wdm_obj[$i]->max_memory_values = $this->max_memory_values;
            $wdm_obj[$i]->name = $fp;
            $wdm_obj[$i]->tmpdir = $this->tmpdir;
            $wdm_obj[$i]->outdir = $this->outdir;
            $wdm_obj[$i]->sessionid = $this->sessionid;
            $wdm_obj[$i]->componentid = $this->componentid . $i;
            $wdm_obj[$i]->wdm_messagefile = $this->wdm_messagefile;
            $wdm_obj[$i]->listobject = $this->listobject;
            $wdm_obj[$i]->setSimTimer($this->timer);
            #error_log("Adding WDM Component $i");
            $this->wdm_files[$wdmid]['object'] = $wdm_obj[$i];
            $i++;
            #error_log("Creating Base WDM object for " . $wdmid);
         }
      }
      $this->initialiazed = 1;
      if ($this->debug) {
         $this->logDebug("Components: <br>");
         # commented out because trying to log an object will cause troubles
         # $this->logDebug($this->components);
         $this->logDebug("<br>");
      }

      # this will do all the normal stuff, including initializing processors, some of which MAY be DSN's
      # so we do it, then return to instantiate the WDMs with their associated DSNs
      parent::init();
      # now, any DSN accessors have been declared, and have called the parents activateDSN function
      foreach ($this->wdm_files as $thiswdm) {
         //if (is_object($thiswdm['object'])) {
            $thiswdm['object']->init();
         //}
      }

   }
   
   function setUpBlockList() {
      
      $this->blocks = array('FILES', 'PLTGEN', 'SCHEMATIC', 'EXT SOURCES', 'EXT TARGETS', 'MASS-LINK', 'RCHRES', 'PERLND', 'IMPLND', 'COPY');
      $this->subblocks = array(
         'FILES'=>array(''),
         'PLTGEN'=>array('PLOTINFO','CURV-DATA'),
         'SCHEMATIC'=>array(''),
         'EXT SOURCES'=>array(''),
         'EXT TARGETS'=>array(''),
         'MASS-LINK'=>array('MASS-LINK'),
         'RCHRES'=>array('GEN-INFO','HYDR-PARM2'),
         'PERLND'=>array('GEN-INFO'),
         'IMPLND'=>array('GEN-INFO'),
         'COPY'=>array('TIMESERIES')
      );
      $this->formats = array(
         'FILES'=>array('FILES'=>$this->uciobject->ucitables['FILES']),
         'PLTGEN'=>array(
            'PLOTINFO'=>$this->uciobject->ucitables['PLOTINFO'],
            'CURV-DATA'=>$this->uciobject->ucitables['CURV-DATA']
         ),
         'SCHEMATIC'=>array('SCHEMATIC'=>$this->uciobject->ucitables['SCHEMATIC']),
         'EXT TARGETS'=>array('EXT TARGETS'=>$this->uciobject->ucitables['EXT TARGETS']),
         'EXT SOURCES'=>array('EXT SOURCES'=>$this->uciobject->ucitables['EXT SOURCES']),
         'MASS-LINK'=>array('MASS-LINK'=>$this->uciobject->ucitables['MASS-LINKS']),
         'RCHRES'=>array('GEN-INFO'=>$this->uciobject->ucitables['RGEN-INFO'],'HYDR-PARM2'=>$this->uciobject->ucitables['HYDR-PARM2']),
         'PERLND'=>array('GEN-INFO'=>$this->uciobject->ucitables['PGEN-INFO']),
         'IMPLND'=>array('GEN-INFO'=>$this->uciobject->ucitables['PGEN-INFO']),
         'COPY'=>array('TIMESERIES'=>$this->uciobject->ucitables['TIMESERIES'])
      );
      
   }

   function step() {
      # step any DSN accessors have been declared
      foreach ($this->wdm_files as $thiswdm) {
         $thiswdm['object']->step();
      }
      # this will do all the normal stuff, including stepping processors, some of which MAY be DSN's
      # so we do that first, and now we can step their associated DSNs and they will have the needed data
      parent::step();
   }

   function finish() {
      # step any DSN accessors have been declared
      foreach ($this->wdm_files as $thiswdm) {
         $thiswdm['object']->finish();
      }

      # this will do all the normal stuff, including stepping processors, some of which MAY be DSN's
      # so we do that first, and now we can step their associated DSNs and they will have the needed data
      parent::finish();
   }

   function cleanUp() {
      # remove all the temp tables associated with this object
      $temp_tables = array("$this->tblprefix" . "tmp_allwdms", "$this->tblprefix" . "tmp_allplotgens", "$this->tblprefix" . "uciblocks");
      # remove all the temp tables associated with this object
      foreach ($temp_tables as $this_tbl) {
         if (is_object($this->listobject)) {
            if ($this->listobject->tableExists($this_tbl)) {
               $this->listobject->querystring = "  drop table $this_tbl ";
               $this->listobject->performQuery();
            }
         }
      }
      
      # cleanUp any DSN accessors have been declared
      foreach ($this->wdm_files as $thiswdm) {
         if (is_object($thiswdm['object'])) {
            if (method_exists($thiswdm['object'],'cleanUp')) {
               $thiswdm['object']->cleanUp();
            }
            unset($thiswdm['object']);
         }
      }
      
      # get rid of all temp tables created with UCI parsing
      foreach (array_keys($this->uciobject->ucitables) as $this_block) {
         if (is_object($this->listobject)) {
            if ($this->listobject->tableExists($this->db_cache_name)) {
               $this_tbl = $this->uciobject->ucitables[$this_block]['tablename'];
               $this->listobject->querystring = "  drop table $this_tbl ";
               $this->listobject->performQuery();
            }
         }
      }
      
      # this will do all the normal stuff, including stepping processors, some of which MAY be DSN's
      # so we do that first, and now we can step their associated DSNs and they will have the needed data
      parent::cleanUp();
   }
   
   function parseUCIPart($blockname) {

      $tablename = $this->tblprefix . $this->uciobject->ucitables[$blockname]['tablename'];
      $block = $this->uciobject->ucitables[$blockname]['parentblock'];
      $subblock = $this->uciobject->ucitables[$blockname]['subblock'];
      $this->uciobject->ucitables[$blockname]['tablename'] = $tablename;
      $thisinfo = $thisinfoset[$block][$subblock];
      
      if (count($thisinfo) == 0) {
         $thisinfo[0] = array();
      }
      
      foreach (array_keys($thisinfo) as $infoid) {
         $tbdata = $thisinfo[$infoid];
         makeUCITable($this->uciobject->listobject,$blockname,$tbdata,$this->uciobject->ucitables,0, 1);
      }
      
   }


   function parseUCI() {

      # set a unique prefix for UCI tables
      # set a name for the temp table that will not hose the db
      $targ = array(' ',':','-','.');
      $repl = array('_', '_', '_', '_');
      $this->tblprefix = str_replace($targ, $repl, "tmp$this->componentid" . "_" . str_pad(rand(1,99), 3, '0', STR_PAD_LEFT) . "_" );

      if ( (!$this->listobject) or (strlen($this->filepath) == 0) or (count($this->ucitables) == 0)) {
         $this->errorstring .= "You must set a UCI file, UCI table templates, and a list object.<br>";
         $this->errorstring .= "ListObject: " . is_object($this->listobject) . "<br>";
         $this->errorstring .= "UCITable Entries: " . count($this->ucitables) . "<br>";
         $this->errorstring .= "File Path: " . $this->filepath . "<br>";
         $this->errorstring .= "Method parseUCI() abandoned.<br>";
      } else {
         $this->uciobject = new HSPF_UCIobject();
         $this->uciobject->listobject = $this->listobject;
         $this->uciobject->ucitables = $this->ucitables;
         # parses the uci, and gets the blocks present in it
         $this->ucidir = dirname($this->filepath);
         $this->uciname = basename($this->filepath);
         $this->uciobject->ucifile = $this->filepath;
         $this->uciobject->uciblocks_tbl = $this->tblprefix . 'uciblocks';
         $this->uciblocks = $this->uciobject->getUCIBlocks();
         $secar = array();

         # store the UCI in an array to speed up querying
         #$uciarray = file($this->uciobject->ucifile);
         $uciarray = $this->uciobject->ucifile;

         $this->listobject->show = 0;
         $this->listobject->tablename = '';

         # set up table information and requests for multiple parsing of UCI
         # this will save many accesses, as it only parses the file once, and gets all requested
         # blocks and sub-blocks in a single pass
         
         $this->setUpBlockList();
         $blocks = $this->blocks;
         $subblocks = $this->subblocks;
         $formats = $this->formats;

         # new code to make requests all at once
         $this->errorstring .= "Parsing All Blocks at once.<br>";
         $thisinfoset = parseMultipleUCIBlocks($uciarray, $blocks, $subblocks, $formats, 0);
         # create a custom table prefix in order to maintain table associations, reset the names in the ucitables array,
         # so that these are preserved later.  Will need to vet any functions that operate on UCI tables to make sure that they
         # query the ucitables array for the name of a table, rather than to assume that it is the original name

         $tablename = $this->tblprefix . $this->uciobject->ucitables['FILES']['tablename'];
         $this->uciobject->ucitables['FILES']['tablename'] = $tablename;
         $thisblock = 'FILES';
         $thisinfo = $thisinfoset['FILES']['FILES'];
         if (count($thisinfo) == 0) {
            $thisinfo[0] = array();
         }
         foreach (array_keys($thisinfo) as $infoid) {
            $tbdata = $thisinfo[$infoid];
            makeUCITable($this->uciobject->listobject,$thisblock,$tbdata,$this->uciobject->ucitables,0, 1);
         }

         $tablename = $this->tblprefix . $this->uciobject->ucitables['PLOTINFO']['tablename'];
         $this->uciobject->ucitables['PLOTINFO']['tablename'] = $tablename;
         $thisblock = 'PLOTINFO';
         $thisinfo = $thisinfoset['PLTGEN']['PLOTINFO'];
         if (count($thisinfo) == 0) {
            $thisinfo[0] = array();
         }
         foreach (array_keys($thisinfo) as $infoid) {
            $tbdata = $thisinfo[$infoid];
            makeUCITable($this->uciobject->listobject,$thisblock,$tbdata,$this->uciobject->ucitables,0, 1);
         }

         $tablename = $this->tblprefix . $this->uciobject->ucitables['CURV-DATA']['tablename'];
         $this->uciobject->ucitables['CURV-DATA']['tablename'] = $tablename;
         $thisblock = 'CURV-DATA';
         $HTMLInfo .= "<br>Searching UCI for $tablename <br>";
         $thisinfo = $thisinfoset['PLTGEN']['CURV-DATA'];
         if (count($thisinfo) == 0) {
            $thisinfo[0] = array();
         }
         foreach (array_keys($thisinfo) as $infoid) {
            $tbdata = $thisinfo[$infoid];
            makeUCITable($this->uciobject->listobject,$thisblock,$tbdata,$this->uciobject->ucitables,0, 1);
         }

         $tablename = $this->tblprefix . $this->uciobject->ucitables['SCHEMATIC']['tablename'];
         $this->uciobject->ucitables['SCHEMATIC']['tablename'] = $tablename;
         $thisblock = 'SCHEMATIC';
         $this->uciobject->debug = 0;
         $thisinfo = $thisinfoset['SCHEMATIC']['SCHEMATIC'];
         if (count($thisinfo) == 0) {
            $thisinfo[0] = array();
         }
         foreach (array_keys($thisinfo) as $infoid) {
            $tbdata = $thisinfo[$infoid];
            makeUCITable($this->uciobject->listobject,$thisblock,$tbdata,$this->uciobject->ucitables,0, 1);
         }

         $tablename = $this->tblprefix . $this->uciobject->ucitables['EXT TARGETS']['tablename'];
         $this->uciobject->ucitables['EXT TARGETS']['tablename'] = $tablename;
         $thisblock = 'EXT TARGETS';
         $thisinfo = $thisinfoset['EXT TARGETS']['EXT TARGETS'];
         if (count($thisinfo) == 0) {
            $thisinfo[0] = array();
         }
         foreach (array_keys($thisinfo) as $infoid) {
            $tbdata = $thisinfo[$infoid];
            makeUCITable($this->uciobject->listobject,$thisblock,$tbdata,$this->uciobject->ucitables,0, 1);
         }

         $tablename = $this->tblprefix . $this->uciobject->ucitables['EXT SOURCES']['tablename'];
         $this->uciobject->ucitables['EXT SOURCES']['tablename'] = $tablename;
         $thisblock = 'EXT SOURCES';
         $thisinfo = $thisinfoset['EXT SOURCES']['EXT SOURCES'];
         if (count($thisinfo) == 0) {
            $thisinfo[0] = array();
         }
         foreach (array_keys($thisinfo) as $infoid) {
            $tbdata = $thisinfo[$infoid];
            makeUCITable($this->uciobject->listobject,$thisblock,$tbdata,$this->uciobject->ucitables,0, 1);
         }

         $tablename = $this->tblprefix . $this->uciobject->ucitables['MASS-LINKS']['tablename'];
         $this->uciobject->ucitables['MASS-LINKS']['tablename'] = $tablename;
         $thisblock = 'MASS-LINKS';
         $thisinfo = $thisinfoset['MASS-LINK']['MASS-LINK'];
         if (count($thisinfo) == 0) {
            $thisinfo[0] = array();
         }
         foreach (array_keys($thisinfo) as $infoid) {
            $tbdata = $thisinfo[$infoid];
            makeUCITable($this->uciobject->listobject,$thisblock,$tbdata,$this->uciobject->ucitables,0, 1);
         }

         $tablename = $this->tblprefix . $this->uciobject->ucitables['RGEN-INFO']['tablename'];
         $this->uciobject->ucitables['RGEN-INFO']['tablename'] = $tablename;
         $thisinfo = $thisinfoset['RCHRES']['GEN-INFO'];
         if (count($thisinfo) == 0) {
            $thisinfo[0] = array();
         }
         $thisblock = 'RGEN-INFO';
         foreach (array_keys($thisinfo) as $infoid) {
            $tbdata = $thisinfo[$infoid];
            makeUCITable($this->uciobject->listobject,$thisblock,$tbdata,$this->uciobject->ucitables,0, 1);
         }

         $tablename = $this->tblprefix . $this->uciobject->ucitables['PGEN-INFO']['tablename'];
         $this->uciobject->ucitables['PGEN-INFO']['tablename'] = $tablename;
         $thisinfo = $thisinfoset['PERLND']['GEN-INFO'];
         if (count($thisinfo) == 0) {
            $thisinfo[0] = array();
         }
         $thisblock = 'PGEN-INFO';
         foreach (array_keys($thisinfo) as $infoid) {
            $tbdata = $thisinfo[$infoid];
            makeUCITable($this->uciobject->listobject,$thisblock,$tbdata,$this->uciobject->ucitables,0, 1);
         }

         $tablename = $this->tblprefix . $this->uciobject->ucitables['IGEN-INFO']['tablename'];
         $this->uciobject->ucitables['IGEN-INFO']['tablename'] = $tablename;
         $thisinfo = $thisinfoset['IMPLND']['GEN-INFO'];
         if (count($thisinfo) == 0) {
            $thisinfo[0] = array();
         }
         $thisblock = 'IGEN-INFO';
         foreach (array_keys($thisinfo) as $infoid) {
            $tbdata = $thisinfo[$infoid];
            makeUCITable($this->uciobject->listobject,$thisblock,$tbdata,$this->uciobject->ucitables,0, 1);
         }

         $tablename = $this->tblprefix . $this->uciobject->ucitables['TIMESERIES']['tablename'];
         $this->uciobject->ucitables['TIMESERIES']['tablename'] = $tablename;
         $thisinfo = $thisinfoset['COPY']['TIMESERIES'];
         if (count($thisinfo) == 0) {
            $thisinfo[0] = array();
         }
         $thisblock = 'TIMESERIES';
         foreach (array_keys($thisinfo) as $infoid) {
            $tbdata = $thisinfo[$infoid];
            makeUCITable($this->uciobject->listobject,$thisblock,$tbdata,$this->uciobject->ucitables,0, 1);
         }

         # output schematic outputs to a plotgen
         $this->listobject->querystring = "  select * into temp table $this->tblprefix" . "tmp_allplotgens ";
         $this->listobject->querystring .= " from ( ";
         $this->listobject->querystring .= " ( select d.ssegno, b.filepath, e.linkname, e.linkgroup, e.targetmember, ";
         $this->listobject->querystring .= "    f.label, e.targetno1, e.targetno2, f.columnno as output_column ";
         $this->listobject->querystring .= "  from $this->tblprefix" . "rchresgeninfo as a, $this->tblprefix" . "files as b, $this->tblprefix" . "plotinfo as c, $this->tblprefix" . "schematic as d, ";
         $this->listobject->querystring .= "    $this->tblprefix" . "masslinks as e, $this->tblprefix" . "curvdata as f ";
         $this->listobject->querystring .= " where c.fileno = b.fileno ";
         $this->listobject->querystring .= "    and d.sname = 'RCHRES' ";
         $this->listobject->querystring .= "    and d.dname = 'PLTGEN' ";
         $this->listobject->querystring .= "    and ( (";
         $this->listobject->querystring .= "       c.segstart <= d.dsegno ";
         $this->listobject->querystring .= "       and c.segend >= d.dsegno ";
         $this->listobject->querystring .= "    ) or ( ";
         $this->listobject->querystring .= "       c.segstart = d.dsegno ";
         $this->listobject->querystring .= "       and c.segend is null";
         $this->listobject->querystring .= "    ) ) ";
         $this->listobject->querystring .= "    and ( (";
         $this->listobject->querystring .= "       f.segstart <= d.dsegno ";
         $this->listobject->querystring .= "       and f.segend >= d.dsegno ";
         $this->listobject->querystring .= "    ) or ( ";
         $this->listobject->querystring .= "       f.segstart = d.dsegno ";
         $this->listobject->querystring .= "       and f.segend is null";
         $this->listobject->querystring .= "    ) ) ";
         $this->listobject->querystring .= "    and ( (";
         $this->listobject->querystring .= "       a.segstart <= d.ssegno ";
         $this->listobject->querystring .= "       and a.segend >= d.ssegno ";
         $this->listobject->querystring .= "    ) or ( ";
         $this->listobject->querystring .= "       a.segstart = d.ssegno ";
         $this->listobject->querystring .= "       and a.segend is null";
         $this->listobject->querystring .= "    ) ) ";
         $this->listobject->querystring .= "    and d.mlno = e.masslink ";
         $this->listobject->querystring .= "    and e.targetno1 = f.columnno ";

         $this->listobject->querystring .= " ) UNION ( ";

         $this->listobject->querystring .= "  select d.ssegno, b.filepath, e.linkname, e.linkgroup, e.targetmember, ";
         $this->listobject->querystring .= "    f.label, e.targetno1, e.targetno2, f.columnno as output_column ";
         $this->listobject->querystring .= " from $this->tblprefix" . "implndgeninfo as a, $this->tblprefix" . "files as b, $this->tblprefix" . "plotinfo as c, $this->tblprefix" . "schematic as d, ";
         $this->listobject->querystring .= "    $this->tblprefix" . "masslinks as e, $this->tblprefix" . "curvdata as f ";
         $this->listobject->querystring .= " where c.fileno = b.fileno ";
         $this->listobject->querystring .= "    and d.sname = 'IMPLND' ";
         $this->listobject->querystring .= "    and d.dname = 'PLTGEN' ";
         $this->listobject->querystring .= "    and c.segstart <= d.dsegno ";
         $this->listobject->querystring .= "    and c.segend >= d.dsegno ";
         $this->listobject->querystring .= "    and ( (";
         $this->listobject->querystring .= "       f.segstart <= d.dsegno ";
         $this->listobject->querystring .= "       and f.segend >= d.dsegno ";
         $this->listobject->querystring .= "    ) or ( ";
         $this->listobject->querystring .= "       f.segstart = d.dsegno ";
         $this->listobject->querystring .= "       and f.segend is null";
         $this->listobject->querystring .= "    ) ) ";
         $this->listobject->querystring .= "    and ( (";
         $this->listobject->querystring .= "       a.segstart <= d.ssegno ";
         $this->listobject->querystring .= "       and a.segend >= d.ssegno ";
         $this->listobject->querystring .= "    ) or ( ";
         $this->listobject->querystring .= "       a.segstart = d.ssegno ";
         $this->listobject->querystring .= "       and a.segend is null";
         $this->listobject->querystring .= "    ) ) ";
         $this->listobject->querystring .= "    and d.mlno = e.masslink ";
         $this->listobject->querystring .= "    and e.targetno1 = f.columnno ";

         $this->listobject->querystring .= " ) UNION ( ";

         $this->listobject->querystring .= "  select d.ssegno, b.filepath, e.linkname, e.linkgroup, e.targetmember, ";
         $this->listobject->querystring .= "    f.label, e.targetno1, e.targetno2, f.columnno as output_column ";
         $this->listobject->querystring .= " from $this->tblprefix" . "perlndgeninfo as a, $this->tblprefix" . "files as b, $this->tblprefix" . "plotinfo as c, $this->tblprefix" . "schematic as d, ";
         $this->listobject->querystring .= "    $this->tblprefix" . "masslinks as e, $this->tblprefix" . "curvdata as f ";
         $this->listobject->querystring .= " where c.fileno = b.fileno ";
         $this->listobject->querystring .= "    and d.sname = 'PERLND' ";
         $this->listobject->querystring .= "    and d.dname = 'PLTGEN' ";
         $this->listobject->querystring .= "    and c.segstart <= d.dsegno ";
         $this->listobject->querystring .= "    and c.segend >= d.dsegno ";
         $this->listobject->querystring .= "    and ( (";
         $this->listobject->querystring .= "       f.segstart <= d.dsegno ";
         $this->listobject->querystring .= "       and f.segend >= d.dsegno ";
         $this->listobject->querystring .= "    ) or ( ";
         $this->listobject->querystring .= "       f.segstart = d.dsegno ";
         $this->listobject->querystring .= "       and f.segend is null";
         $this->listobject->querystring .= "    ) ) ";
         $this->listobject->querystring .= "    and ( (";
         $this->listobject->querystring .= "       a.segstart <= d.ssegno ";
         $this->listobject->querystring .= "       and a.segend >= d.ssegno ";
         $this->listobject->querystring .= "    ) or ( ";
         $this->listobject->querystring .= "       a.segstart = d.ssegno ";
         $this->listobject->querystring .= "       and a.segend is null";
         $this->listobject->querystring .= "    ) ) ";
         $this->listobject->querystring .= "    and d.mlno = e.masslink ";
         $this->listobject->querystring .= "    and e.targetno1 = f.columnno ";

         $this->listobject->querystring .= " ) UNION ( ";

         $this->listobject->querystring .= "  select d.ssegno, b.filepath, e.linkname, e.linkgroup, e.targetmember, ";
         $this->listobject->querystring .= "    f.label, e.targetno1, e.targetno2, f.columnno as output_column ";
         $this->listobject->querystring .= " from $this->tblprefix" . "copytimeseries as a, $this->tblprefix" . "files as b, $this->tblprefix" . "plotinfo as c, $this->tblprefix" . "schematic as d, ";
         $this->listobject->querystring .= "    $this->tblprefix" . "masslinks as e, $this->tblprefix" . "curvdata as f ";
         $this->listobject->querystring .= " where c.fileno = b.fileno ";
         $this->listobject->querystring .= "    and d.sname = 'COPY' ";
         $this->listobject->querystring .= "    and d.dname = 'PLTGEN' ";
         $this->listobject->querystring .= "    and c.segstart <= d.dsegno ";
         $this->listobject->querystring .= "    and c.segend >= d.dsegno ";
         $this->listobject->querystring .= "    and ( (";
         $this->listobject->querystring .= "       f.segstart <= d.dsegno ";
         $this->listobject->querystring .= "       and f.segend >= d.dsegno ";
         $this->listobject->querystring .= "    ) or ( ";
         $this->listobject->querystring .= "       f.segstart = d.dsegno ";
         $this->listobject->querystring .= "       and f.segend is null";
         $this->listobject->querystring .= "    ) ) ";
         $this->listobject->querystring .= "    and ( (";
         $this->listobject->querystring .= "       a.segstart <= d.ssegno ";
         $this->listobject->querystring .= "       and a.segend >= d.ssegno ";
         $this->listobject->querystring .= "    ) or ( ";
         $this->listobject->querystring .= "       a.segstart = d.ssegno ";
         $this->listobject->querystring .= "       and a.segend is null";
         $this->listobject->querystring .= "    ) ) ";
         $this->listobject->querystring .= "    and d.mlno = e.masslink ";
         $this->listobject->querystring .= "    and e.targetno1 = f.columnno ";

         $this->listobject->querystring .= ") ) as foo ";
         #$this->errorstring .= $this->listobject->querystring . "<br>";
         $this->listobject->performQuery();

         $this->listobject->querystring = " select * from $this->tblprefix" . "tmp_allplotgens ";
         $this->listobject->performQuery();
         $this->listobject->showList();
         #$this->errorstring .= $this->listobject->querystring . "<br>";
         $this->errorstring .= $this->listobject->outstring;

         # output ext source outputs to a wdm
         if ($this->debug) {
            $this->listobject->querystring = "select *  from $this->tblprefix" . "extsources";
            $this->listobject->performQuery();
            $this->logDebug($this->listobject->querystring . " ; <br>");
            $this->listobject->showList();
            $this->logDebug($this->listobject->outstring . " <br>");
            $this->listobject->querystring = "select *  from $this->tblprefix" . "exttargets";
            $this->listobject->performQuery();
            $this->logDebug($this->listobject->querystring . " ; <br>");
            $this->listobject->showList();
            $this->logDebug($this->listobject->outstring . " <br>");
         }
         $this->listobject->querystring = "  select * into temp table $this->tblprefix" . "tmp_allwdms ";
         $this->listobject->querystring .= " from ( ";
         $this->listobject->querystring .= " ( select 'o' as io, a.sourcevolume as hspfvol, a.sourceid as hspfsegid, ";
         $this->listobject->querystring .= "    a.sourcegroup::varchar(8) as hspfgroup, ";
         $this->listobject->querystring .= "    a.sourcename as hspfmember, a.memberid1, a.memberid2, ";
         $this->listobject->querystring .= "    a.targetname as wdmname, a.targetid as dsn, b.handle, b.filepath ";
         $this->listobject->querystring .= " from $this->tblprefix" . "exttargets as a, $this->tblprefix" . "files as b ";
         $this->listobject->querystring .= " where a.sourcevolume = 'RCHRES' ";
         $this->listobject->querystring .= "    and a.targetvolume = b.handle ";
         $this->listobject->querystring .= " ) UNION ( ";
         $this->listobject->querystring .= "  select 'i' as io, a.targetvolume as hspfvol, a.segstart as hspfsegid, ";
         $this->listobject->querystring .= "    a.targetgroup as hspfgroup, a.targetname as hspfmember, ";
         $this->listobject->querystring .= "    a.targetnum1 as memberid1, a.targetnum2 as memberid2, ";
         $this->listobject->querystring .= "    a.elem as wdmname, a.recid as dsn, b.handle, b.filepath ";
         $this->listobject->querystring .= " from $this->tblprefix" . "extsources as a, $this->tblprefix" . "files as b ";
         $this->listobject->querystring .= " where a.targetvolume = 'RCHRES' ";
         $this->listobject->querystring .= "    and a.wdmid = b.handle ";
         $this->listobject->querystring .= ") ) as foo ";
         $this->listobject->performQuery();
         if ($this->debug) {
            $this->logDebug($this->listobject->querystring . " ; <br>");
         }
         # ext source columns - wdmid, recid, elem, units, defmissing, multfactor, tstran, targetvolume, segstart, segend, targetgroup, targetname, targetnum1, targetnum2

         $this->listobject->querystring = " select * from $this->tblprefix" . "tmp_allwdms ";
         $this->listobject->performQuery();
         $this->listobject->showList();
         if ($this->debug) {
            $this->logDebug("Contents of $this->tblprefix" . "tmp_allwdms <br>");
            $this->logDebug($this->listobject->outstring . " ; <br>");
         }
         $this->errorstring .= $this->listobject->outstring;

         # now, populate the wdm array, and plotgen array with the names of the files
         # this is disabled, users must now DEFINE accessible WDM components as individual sub-components
         # this will eliminate importing massive quantities of data that are not to be used
         $this->listobject->querystring = "  select handle, filepath, count(*) ";
         $this->listobject->querystring .= " from $this->tblprefix" . "tmp_allwdms ";
         $this->listobject->querystring .= " group by handle, filepath ";
         $this->listobject->performQuery();
         if ($this->debug) {
            $qs = $this->listobject->querystring;
            $this->logDebug("$qs ; <br>");
            $this->listobject->showList();
            $os = $this->listobject->outstring;
            $this->logDebug("$os ; <br>");
         }
         #$this->wdms = array();
         $pgnames = $this->listobject->queryrecords;
         # We started the count of components with the plotgens, so we need to keep these moving forward with the WDMs
         # so as not to overwrite the plotgens
         # $i = 0;
         $this->wdm_files = array();
         #$this->debug = 1;
         #$this->debugmode = 1;
         foreach ($pgnames as $thisrec) {
            $this->wdm_files[$thisrec['handle']] = array(
               'id'=>$thisrec['handle'],
               'filepath'=>$thisrec['filepath'],
               'object'=>-1,
               'active_dsns'=>array()
            );
            if ($this->debug) {
               $this->logDebug("Adding " . $thisrec['handle'] . " " . $thisrec['filepath'] . " to wdm_files <br>");
            }
         }
         $this->listobject->querystring = "  select * ";
         $this->listobject->querystring .= " from $this->tblprefix" . "tmp_allwdms ";
         $this->listobject->performQuery();
         $this->wdms = array();
         $this->wdm_dsns = array();
         foreach ($this->listobject->queryrecords as $thisrec) {
            if ($thisrec['io'] == 'i') {
               $pre = 'IN - ';
            } else {
               $pre = 'OUT - ';
            }
            $pglabel = $pre . $thisrec['handle'] . " " . $thisrec['dsn'] . " " . $thisrec['wdmname'] . " " . $thisrec['hspfvol'] . " " . $thisrec['hspfsegid'] . " " . $thisrec['hspfgroup'];
            array_push($this->wdms, $pglabel);
            $this->wdm_dsns[$pglabel] = array(
               'id'=> $thisrec['handle'],
               'dsn'=> ltrim(rtrim($thisrec['dsn']))
            );
            if ($this->debug) {
               $this->logDebug("Creating Base DSN object for " . $pglabel . "<br>");
            }
         }

         # now, populate the wdm array, and plotgen array with the names of the files
         $this->listobject->querystring = "  select * ";
         $this->listobject->querystring .= " from $this->tblprefix" . "tmp_allplotgens ";
         $this->listobject->performQuery();
         $this->plotgenfiles = array();
         foreach ($this->listobject->queryrecords as $thisrec) {
            $pglabel = 'Plotgen - ' . $thisrec['linkname'] . " " . $thisrec['ssegno'] . " " . $thisrec['linkgroup'] . " " . $thisrec['targetmember'] . " " . $thisrec['label'] . "-" . $thisrec['output_column'];
            array_push($this->plotgenfiles, $pglabel);
            $this->errorstring .= $this->logDebug($thisrec,1);
         }
         $this->parsed = 1;
         $this->errorstring .= "<br>Plotgens: " . $this->logDebug($this->plotgenfiles,1);
      }

   }

   function activateDSN($dsname) {
      #error_log("Activating DSN by Name = " . $dsname);
      if (in_array($dsname, array_keys($this->wdm_dsns))) {
         $wdmid = $this->wdm_dsns[$dsname]['id'];
         $dsn = $this->wdm_dsns[$dsname]['dsn'];
         if (in_array($wdmid, array_keys($this->wdm_files))) {
            if (is_object($this->wdm_files[$wdmid]['object'])) {
               $this->wdm_files[$wdmid]['object']->activateDSN($dsn);
            }
         }
      }
   }

   function getDSNValue($dsname) {
      $dsnval = NULL;
      #error_log("getDSNValue called for $dsname");
      if (in_array($dsname, array_keys($this->wdm_dsns))) {
         $wdmid = $this->wdm_dsns[$dsname]['id'];
         $dsn = $this->wdm_dsns[$dsname]['dsn'];
         #error_log("Found IDs for $dsname " . $wdmid . " " . $dsn);
         if (in_array($wdmid, array_keys($this->wdm_files))) {
            if (is_object($this->wdm_files[$wdmid]['object'])) {
               $dsnval = $this->wdm_files[$wdmid]['object']->getValue($this->timer->timeseconds, $dsn);
               #error_log("DSNVal retrieved " . $dsnval . " From state = " . print_r($this->wdm_files[$wdmid]['object']->state,1));
            }
         }
      }
      return $dsnval;
   }

   function getPropertyClass($propclass) {
      # Call parent method to get any standard classes matching the criteria
      if (!$this->parsed) {
         $this->parseUCI();
      }
      $returnprops = parent::getPropertyClass($propclass);
      foreach ($propclass as $thisclass) {

         switch ($thisclass) {

            case 'plotgen':
            $returnprops = array_unique(array_merge($returnprops, $this->getPlotgens()));
            #$returnprops = $rproptemp;
            break;

            case 'wdm':
            $returnprops = array_unique(array_merge($returnprops, $this->getWDMs()));
            break;

         }
      }
      #$returnprops = $this->plotgens;
      return $returnprops;
   }

   function getPublicVars() {
      if (!$this->parsed) {
         #error_log("Parsing UCI before returning properties");
         $this->parseUCI();
      }
      # gets all viewable variables
      # this is a sub-class method, to include getting plotgens
      
      $procs = $this->getPublicProcs();
      
      $publix = array_unique(array_merge(array_keys($this->state), $this->getPublicProps(), $this->getPublicProcs(), $this->getPublicInputs(), $this->getPlotgens()));
      return $publix;
   }
   
   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'hspf_timestep');

      return $publix;
   }

   function getPlotgens() {
      if (is_array($this->plotgenfiles)) {
         return $this->plotgenfiles;
      } else {
         return array();
      }
   }

   function getWDMs() {
      if (is_array($this->wdms)) {
         return $this->wdms;
      } else {
         return array();
      }
   }

   function showHTMLInfo() {
      $HTMLInfo = '';
      $HTMLInfo .= parent::showHTMLInfo();
      if (!$this->initialiazed) {
         #$this->init();
         $HTMLInfo .= "Calling UCI Parsing routine.<br>";
         $this->parseUCI();
      }
      $HTMLInfo .= $this->errorstring;
      $this->uciobject->debug = 0;

      if ( (!$this->listobject) or (strlen($this->filepath) == 0)) {
         $HTMLInfo .= "You must set a UCI file, and a list object.<br>";
         $HTMLInfo .= "UCI file: " . $this->filepath  . "<br>";
      } else {

         #$HTMLInfo .= $this->logDebug($this->ucitables,1);
         $HTMLInfo .= "Parsed: " . $this->filepath . "<br>";

         $this->listobject->show = 0;
         $this->listobject->tablename = '';
         
         $HTMLInfo .= "<hr>Blocks in this UCI: " . print_r($this->uciblocks,1) . "<hr>";

         $tablename = $this->uciobject->ucitables['FILES']['tablename'];
         $thisblock = 'FILES';
         $HTMLInfo .= "<br>Searching UCI for $tablename <br>";
         $this->listobject->querystring = " select * from $tablename ";
         $HTMLInfo .= $this->listobject->querystring . "<br>";
         $this->listobject->performQuery();
         $this->listobject->showList();
         $HTMLInfo .= $this->listobject->outstring;

         $tablename = $this->uciobject->ucitables['PLOTINFO']['tablename'];
         $thisblock = 'PLOTINFO';
         $HTMLInfo .= "<br>Searching UCI for $tablename <br>";
         $this->listobject->querystring = " select * from $tablename ";
         $this->listobject->performQuery();
         $this->listobject->showList();
         $HTMLInfo .= $this->listobject->outstring;

         $tablename = $this->uciobject->ucitables['CURV-DATA']['tablename'];
         $thisblock = 'CURV-DATA';
         $HTMLInfo .= "<br>Searching UCI for $tablename <br>";
         $this->listobject->querystring = " select * from $tablename ";
         $this->listobject->performQuery();
         $this->listobject->showList();
         $HTMLInfo .= $this->listobject->outstring;

         $tablename = $this->uciobject->ucitables['SCHEMATIC']['tablename'];
         $thisblock = 'SCHEMATIC';
         $HTMLInfo .= "<br>Searching UCI for $tablename <br>";
         $this->listobject->querystring = " select * from $tablename ";
         $this->listobject->performQuery();
         $this->listobject->showList();
         $HTMLInfo .= $this->listobject->outstring;

         $tablename = $this->uciobject->ucitables['EXT TARGETS']['tablename'];
         $thisblock = 'EXT TARGETS';
         $HTMLInfo .= "<br>Searching UCI for $tablename <br>";
         $this->listobject->querystring = " select * from $tablename ";
         $this->listobject->performQuery();
         $this->listobject->showList();
         $HTMLInfo .= $this->listobject->outstring;

         $tablename = $this->uciobject->ucitables['MASS-LINKS']['tablename'];
         $thisblock = 'MASS-LINKS';
         $HTMLInfo .= "<br>Searching UCI for $tablename <br>";
         $this->listobject->querystring = " select * from $tablename ";
         $this->listobject->performQuery();
         $this->listobject->showList();
         $HTMLInfo .= $this->listobject->outstring;

         $tablename = $this->uciobject->ucitables['RGEN-INFO']['tablename'];
         $thisblock = 'RGEN-INFO';
         $HTMLInfo .= "<br>Searching UCI for $tablename <br>";
         $this->listobject->querystring = " select * from $tablename ";
         $this->listobject->performQuery();
         $this->listobject->showList();
         $HTMLInfo .= $this->listobject->outstring;

         # output schematic outputs to a plotgen
         $tablename = $this->uciobject->ucitables['TIMESERIES']['tablename'];
         $HTMLInfo .= "COPY blocks:<br>";
         $this->listobject->querystring = "  select * from $tablename ";
         $this->listobject->performQuery();
         $this->listobject->showList();
         $HTMLInfo .= $this->listobject->outstring;

         # output schematic outputs to a plotgen
         $HTMLInfo .= "Reaches output to PLOTGEN:<br>";
         $this->listobject->querystring = "  select * from $this->tblprefix" . "tmp_allplotgens ";
         $this->listobject->performQuery();
         $this->listobject->showList();
         $HTMLInfo .= $this->listobject->outstring;

         # output ext target outputs to a wdm
         $HTMLInfo .= "Outputs to WDM:<br>";
         $this->listobject->querystring = "  select * from $this->tblprefix" . "tmp_allwdms where io = 'o'";
         $this->listobject->performQuery();
         $this->listobject->showList();
         $HTMLInfo .= $this->listobject->outstring;

         # input ext source outputs to hspf
         $HTMLInfo .= "Inputs from WDM:<br>";
         $this->listobject->querystring = "  select * from $this->tblprefix" . "tmp_allwdms where io = 'i'";
         $this->listobject->performQuery();
         $this->listobject->showList();
         $HTMLInfo .= $this->listobject->outstring;
      }

      return $HTMLInfo;
   }

}

class HSPFPlotgen extends timeSeriesInput {
   var $errorstring = '';
   var $plotgenoutput = '';
   var $filepath = '';
   var $paramcolumns = array();
   var $loglines = -1;

   # requires the library lib_equation2.php, which has the class "Equation"
   var $equations = array(); # keyed by statevarname=>equationtext

   function showHTMLInfo() {
      $this->init();
      $HTMLInfo = parent::showHTMLInfo();
      return $HTMLInfo;
   }

   function init() {

      if (is_object($this->listobject) and ($this->log2db == 1)) {
         # set a name for the temp table that will not hose the db
         $targ = array(' ',':','-','.');
         $repl = array('_', '_', '_', '_');
         $this->tblprefix = str_replace($targ, $repl, $this->tblprefix);
         $this->dbtblname = $this->tblprefix . 'datalog';
      }

      #error_log("initializing plotgen " . $this->filepath);

      # part of modeling widgets, expects lib_hydrology,php, and HSPFFunctions.php to be included
      # expects user to create a time series object, $flow2, and pass it as an input
      # adds time series data from a plotgen to the object, and returns it

      # $paramcolumns is an array of format:
      #   array('paramname'=>paramcolumn)
      # $totaldatacols = the number of total output columns in the file, should figure out a way to
      #  determine this automatically
      # parseHSPFMultiout($infilename,$thiscolumn, $totaldatacols)

      # $rectype = 0, hourly, 1 - daily
      $this->maxflow = 0;

      if ($this->timer == NULL) {
         return;
      }
      if ($this->timer->thistime <> '') {
         $sd = $this->timer->thistime;
         $sdts = $sd->format('U');
      }
      if ($this->timer->endtime <> '') {
         $ed = $this->timer->endtime;
         $edts = $ed->format('U');
      }

      if ($this->debug) {
         $this->logDebug("Parsing Plotgen File: $this->filepath <br>");
      }
      if (file_exists($this->filepath)) {
         $plotgen = parseHSPFMultiout($this->filepath,-1, count($this->paramcolumns), 0);
         # column 0 contains header data, column 1 contains the actual data
         $filedata = $plotgen[1];
      } else {
         $filedata = array();
         if ($this->debug) {
            $this->logDebug("$this->filepath not found.<br>");
         }
      }

      if ($this->debug) {
         $this->logDebug("<br> Parameter Columns: ");
         $this->logDebug($this->paramcolumns);
         $this->logDebug("<br>");
      }
      $linecount = 0;
      $totallines = count($filedata);
      foreach ($filedata as $thisdata) {
         $linecount++;
         if ($this->debug and ( ($linecount < $this->loglines) or ($linecount > ($totallines - $this->loglines)) or ($this->loglines < 0)) ) {
            $this->logDebug($thisdata);
         }
         $thisdate = new DateTime($thisdata[0]);
         $ts = $thisdate->format('r');
         $uts = $thisdate->format('U');
         $thisflag = '';
         # default to missing
         $thisval = 0.0;
         if ($this->debug and ( ($linecount < $this->loglines) or ($linecount > ($totallines - $this->loglines)) or ($this->loglines < 0)) ) {
            $this->logDebug("<br> Parameter Columns: ");
            $this->logDebug($this->paramcolumns);
            $this->logDebug("<br>");
         }
         #$this->logDebug("<br>");
         $withinperiod = 1;
         if ( ($this->timer->thistime <> '') and ($sdts > $uts) ) {
            # if have defined a start date, and this date is less than the start date, do not add
            $withinperiod = 0;
         }
         if ( ($this->timer->endtime <> '') and ($edts < $uts) ) {
            # if have defined an end date, and this date is greater than the end date, we are done
            break;
         }
         #$this->logDebug("Within: $within (TS: $uts, SDTS: $sdts, EDTS: $edts) <br>");
         if ($withinperiod) {
            foreach (array_keys($this->paramcolumns) as $dataitem) {

               $thiscol = $this->paramcolumns[$dataitem];
               if ($thisdata[$thiscol] <> '') {
                  $thisval = $thisdata[$thiscol];
               } else {
                  $thisval = '0.0';
               }
               if ($this->debug and ( ($linecount < $this->loglines) or ($linecount > ($totallines - $this->loglines)) or ($this->loglines < 0)) ) {
                  $this->logDebug("$dataitem : $thiscol : $thisval <br>");
               }
               # add to timeseries object
               $this->addValue($ts, $dataitem, floatval($thisval));
               if ($dataitem == 'Qout') {
                  if ($thisval > $this->maxflow) {
                     $this->maxflow = floatval($thisval);
                  }
               }
            }
            $this->addValue($ts, 'timestamp', $uts);
            $this->addValue($ts, 'thisdate', $thisdate->format('m-d-Y'));
         }
      }

      # test - will this modify the object?
      #return $tsobject;
      if ($this->debug) {
         //$this->logDebug($this->tsvalues);
      }
      #@ set up data columns
      $this->setDataColumnTypes();
      ksort($this->tsvalues);
   }


}

class HSPFWDM extends modelObject {
   var $errorstring = '';
   var $wdmoutput = '';
   var $filepath = '';
   var $debugmode = 0;
   var $wdm_messagefile = '';
   var $ereg_format = array();
   var $dsns = array();
   var $dsn_exps = array(); # place to store links to export files for dsns
   var $cache_log = 1;
   var $max_memory_values = -1; # cache values in db (saves time and memory) passed to WDMDSN
   var $wdimex_exe = '/var/www/cgi-bin/wdimex';
   var $loadall = 0; # load all dsn's by default?  This is VERY time consuming, and should be worked around
                     # perhaps by having the dsn's explicitly called by a local sub-component on the HSPF UCI

   # requires the library lib_equation2.php, which has the class "Equation"
   var $equations = array(); # keyed by statevarname=>equationtext

   function showHTMLInfo() {
      #$this->init();
      $HTMLInfo = parent::showHTMLInfo();
      return $HTMLInfo;
   }

   function finish() {
      parent::finish();
      if ($this->debug) {
         $this->logDebug("Clearing any temp files <br>");
      }
      foreach ($this->processors as $thisproc) {
         #print($thisproc->debugstring);
         #$tbl = $thisproc->dbtblname;
         #$this->listobject->querystring = "select * from $tbl limit 50";
         #error_log($this->listobject->querystring . "; <br>");
         #$this->listobject->performQuery();
         #$this->listobject->show = 0;
         #$this->listobject->showlist();
         #error_log($this->listobject->outstring . "; <br>");
      }
      $this->clearTempFiles();
   }

   function activateDSN($dsn) {
      #error_log("Activating DSN by ID = " . $dsn);
      array_push($this->dsns, $dsn);
   }
   
   function setUpFormats() {
      $this->ereg_format['dsn_line'] = "/([DSN]{3})([ ]{8})([ 0-9]{5})([ 0-9A-Za-z]{63})/";
      $this->ereg_format['dsn_end'] = "/([ DSNE]{8})/";
      $this->ereg_format['data_start'] = "/([ ]{2})([DAT]{4})([ ]{7})([STAR:]{7})([ 0-9A-Za-z:]{47})/";
      $this->ereg_format['data_end'] = "/([ ]{2})([ DATEN]{8})/";
      $this->ereg_format['label_start'] = "/([ ]{2})([ LABE]{8})/";
      $this->ereg_format['label_end'] = "/([ ]{2})([ DLABEN]{12})/";
      $this->ereg_format['label_data'] = "/([ ]{4})([ A-Z]{6})([ ]{2})([ 0-9A-Za-z()/:.\-]{68})/";
      #$this->ereg_format['data_line'] = "/([ ]{4})([0-9]{4})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{4})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{5})([ ]{1})([ 0-9]{2})([ ]{4})([ 0-9A-Za-z:.\-]{15})/";
      $this->ereg_format['data_line'] = '/([ ]{4})([0-9]{4})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{4})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{2})([ ]{1})([ 0-9]{5})([ ]{1})([ 0-9]{2})([ ]{4})([ 0-9A-Za-z:.+-]{15})/';
   }

   function init() {
//$this->debug = 1;
      $this->setUpFormats();
      if (is_object($this->timer)) {
         if (is_object($this->timer->thistime)) {
            $this->startdate = $this->timer->thistime->format('Y-m-d');
         }
         if (is_object($this->timer->endtime)) {
            $this->enddate = $this->timer->endtime->format('Y-m-d');
         }
      } else {
         $this->startdate = '';
         $this->enddate = '';
      }
      if (count($this->dsns) > 0) {
         if ($this->debug) {
            $this->logDebug("Calling loadDataSet for file " . $this->filepath . "<br>");
         }
         $this->loadDataSet($this->dsns);
         #error_log("Loading DSNs " . print_r($this->dsns,1));
         //error_log("Calling loadDataSet for file " . $this->filepath . "<br>");
      } else {
         if ($this->loadall == 1) {
            $this->loadDataSet(array());
            #error_log("No DSN specified, Loading ALL DSNs " . print_r($this->dsns,1));
         }
      }

   }
   
   
   function loadDataSetMemory($dsn = array(), $flag = 'c') {
      //loadDataSetMemory($thisdsn,'c');
      if (!is_array($dsn)) {
         $dsn = array($dsn);
      }
      $crit = array();
      if ( count($dsn) > 0) {
         $crit = array('DSN' => $dsn);
      }
      //error_log("loadDataSetMemory Called for file " . $this->filepath . "<br>\n");
      //error_log("Exporting WDM " . $this->filepath . "<br>\n");
      
      $expfile = $this->exportWDM('','',0,$dsn);

      if ($this->debug) error_log("Parsing " . $expfile . "<br>\n");
      
      $stashdebug = $this->debug;
      //$this->debug = 1;
      error_log("Calling this->parseEXPFile($expfile, 'DSN' => $dsn, $this->startdate, $this->enddate)");;
      $dsn_recs = $this->parseEXPFile($expfile, $crit, $this->startdate, $this->enddate);

      return $dsn_recs;
   }
   
   function loadDataSet($dsn = array()) {
      if (!is_array($dsn)) {
         $dsn = array($dsn);
      }

      $crit = array();

      if ( count($dsn) > 0) {
         $crit = array('DSN' => $dsn);
      }

      if ($this->debug) {
         $this->logDebug("loadDataSet Called for file " . $this->filepath . "<br>\n");
      }

      //error_log("loadDataSet Called for file " . $this->filepath . "<br>\n");

      if ($this->debug) {
         $this->logDebug("Exporting WDM " . $this->filepath . "<br>\n");
      }
      $expfile = $this->exportWDM('','',1,$dsn);
      # stash the debug setting, because you really don't want this much
      # debug info, unles you really want it, then you will go to
      # the function parseEXPFile and enable it there

      if ($this->debug) {
         $this->logDebug("Parsing " . $expfile . "<br>\n");
      }
      $stashdebug = $this->debug;
      #$this->debug = 0;
      $dsn_recs = $this->parseEXPFile($expfile, $crit, $this->startdate, $this->enddate);
      #$this->debug = $stashdebug;

      if ($this->debug) {
         $this->logDebug("Loading data from " . $expfile . "<br>\n");
         $this->logDebug(" For " . $this->startdate . " to " . $this->enddate . "<br>\n");
      }

      $d_names = array_keys($dsn_recs);
      $k = 0;
      foreach ($d_names as $thisdsn) {
         #$varname = $dsn_recs[$thisdsn]['DSN'] . '-' . $dsn_recs[$thisdsn]['labels']['TSTYPE'];
         $varname = $dsn_recs[$thisdsn]['DSN'];
         if ($this->debug) {
            $this->logDebug("Processing DSN data for $varname <br>\n");
         }
         $tsvalues = $dsn_recs[$thisdsn]['tsvalues'];

         # cache this DSN in an xml file if:
         #    * it is not already cached, so we need to create it
         #    * if we ARE forcing a refresh we assume that the cached file is out of date
         $this_dsn_cache = $this->outdir . "/cache" . $this->componentid . "_dsn_$thisdsn" . ".log";
         if (!file_exists($this_dsn_cache)) {
            $options = array();
            #error_log("Creating serializer for DSN cache");
            $serializer = new XML_Serializer($options);
            $result = $serializer->serialize($tsvalues);
            $xml = $serializer->getSerializedData();
            #error_log("DSN $thisdsn contains " . count($tsvalues) . " records.");
            #error_log("Dumping DSN $thisdsn to file: $this_dsn_cache");
            file_put_contents($this_dsn_cache, $xml);
         }

         if ($this->debug) {
            $this->logDebug("Creating DSN Object for $varname <br>\n");
         }
         $d = new WDMDSN;
         $d->name = $varname;
         $d->setSimTimer($this->timer);
         $d->max_memory_values = $this->max_memory_values;
         $d->setProp('sessionid', $this->sessionid . "_" . $k);
         $d->setProp('componentid', $this->componentid . "_" . $k);
         $k++;
         //error_log("WDMDSN create with max_memory_values set to " . $d->max_memory_values);
         $d->init();
         if ($this->debug) {
            //$d->debug = 1;
            $d->debugmode = 0;
            $this->logDebug("Adding Records to DSN Object <br>\n");
         }
         if (is_object($this->listobject)) {
            if ($this->debug) {
               $this->logDebug("Setting list object on $varname <br>\n");
            }
            $d->listobject = $this->listobject;
         }
         $d->addDSNRecs($tsvalues);
         if ($this->debug) {
            $this->logDebug("Finished adding to DSN Object: " . $d->debugstring . "<br>\n");
         }
         if ($this->debug) {
            $this->logDebug("Adding Operator for $varname <br>\n");
         }
         $this->addOperator($varname, $d, 0);
      }
   }

   function tsvalues2listobject($complist = array()) {
      if (count($complist) == 0) {
         $complist = array_keys($this->processors);
      }
      foreach ($complist as $thiscomp) {
         if ($this->debug) {
            $this->logDebug("Moving time series data for $thiscomp to list object.<br>\n");
         }
         # check to see if this processor has a listobject method
         if (method_exists($this->processors[$thiscomp], 'tsvalues2listobject')) {
            # see if it has a valid list object
            if (!is_object($this->processors[$thiscomp]->listobject)) {
               if ($this->debug) {
                  $this->logDebug("$thiscomp does not have list object <br>\n");
               }
               # if not, try to add ours, if we have one
               if (is_object($this->listobject)) {
                  if ($this->debug) {
                     $this->logDebug("Copying list object to $thiscomp  <br>\n");
                  }
                  $this->processors[$thiscomp]->listobject = $this->listobject;
               }
            }
            if (is_object($this->processors[$thiscomp]->listobject)) {
               if ($this->debug) {
                  $this->logDebug("Outputting values to db for $thiscomp  <br>\n");
               }
               $this->processors[$thiscomp]->tsvalues2listobject();
            }
         }
      }
   }

   function parseEXPFile($expfile, $label_criteria = array(),$startdate='',$enddate='') {
# commented in lieu of reading whole file into memory
#      $fhandle = fopen($expfile, 'r');
      $in_dsn = 0;
      $in_data = 0;
      if ($this->debug) {
         $this->logDebug("Start and end dates: $startdate - $enddate<br>\n");
      }

      # creates a separate time series for each DSN ID
      # for now this will just return an associative array with an entry for each DSN encountered
      # but at a later date, we should actually instantiate TimeSeries Objects for each DSN
      $these_dsns = array();

      if ($this->debug) {
         $this->logDebug("Trying to import EXP data from file " . $expfile . "<br>\n");
      }
      $outmesg = "Trying to import EXP data from file " . $expfile . "<br>\n";
      //error_log($outmesg);
      //error_log("Using format: " . print_r($this->ereg_format,1));

      $ss = '';
      $es = '';
      if (strlen($startdate) > 0) {
         $sd = new DateTime($startdate);
         $ss = $sd->format('U');
      }
      if (strlen($enddate) > 0) {
         $ed = new DateTime($enddate);
         $es = $ed->format('U');
      }
      
      # set up useful constants to be used in loops:
      $spacexplen = 8; # number of characters that make up the leading blank space regexp
      $numreglen = 16; # number of characters that make up value regexp
      $validintervals = array(3,4,5); # 3 - hourly, 4 - daily, 5 - monthly
      
      if ($this->debug) {
         $this->logDebug("Start ($startdate) and end ($enddate) epoch: $es $ss<br>\n");
         error_log("Formats: " . print_r($this->ereg_format,1));
      }
         #error_log("Start ($startdate) and end ($enddate) epoch: $ss $es <br>\n");

      $wholefile = file($expfile);
#      while ($thisline = fgets($fhandle,255) ) {
      #foreach ($wholefile as $thisline ) {
      //error_log("File $expfile has " . count($wholefile) . " lines");
      $lastk = -1;
      for ($k=0; $k < count($wholefile); $k++) {
         $thisline = $wholefile[$k];
         if (($k >= ($lastk + 999)) or ($lastk == -1)) {
            $outmesg = "Parsing Line $k <br>\n";
            //if ($this->debug) {
               $this->logDebug($outmesg);
            //}
            $lastk = $k;
         }

         if ($in_dsn) {
            //error_log("DSN LINE: $thisline<br>\n");
            # we are inside a DSN block, look for data line
            if ($in_data) {
               # check for data end
               preg_match($this->ereg_format['data_end'], $thisline, $tokens);
               if (count($tokens) >= 3) {
                  if (rtrim(ltrim($tokens[2])) == 'END DATA') {
                     if ($this->debug) {
                        $this->logDebug("Found END of DSN DATA " . $dsn . "<br>\n");
                     }
                     $in_data = 0;
                  }
               }
               # no end found, so now parse the data
               if ($in_data and $desired) {
                  # reset the tokens array so we know if we have a pattern match
                  $tokens = array();
                  preg_match($this->ereg_format['data_line'], $thisline, $tokens);
                  if (count($tokens) >= 3) {
                     if ($tokens[2] > 0) {
                        # valid numerical entry for the year, and valid line of form
                        if ($this->debug) {
                           $this->logDebug("Found Data for " . $tokens[2] . "-" . $tokens[4] . "-" . $tokens[6] . " = " . $tokens[24] . "<br>\n");
                        }
                        $yr = $tokens[2];
                        $mo = str_pad(ltrim(rtrim($tokens[4])), 2, '0', STR_PAD_LEFT);
                        $day = str_pad(ltrim(rtrim($tokens[6])), 2, '0', STR_PAD_LEFT);
                        $hr = str_pad(ltrim(rtrim($tokens[8])), 2, '0', STR_PAD_LEFT);
                        $min = str_pad(ltrim(rtrim($tokens[10])), 2, '0', STR_PAD_LEFT);
                        $sec = str_pad(ltrim(rtrim($tokens[12])), 2, '0', STR_PAD_LEFT);
                        $fixthedate = 0;
                        if ($hr == 24) {
                           # this will cause a malformed time stamp, since php considers hour 24 to be the 0 hour, next day
                           # so...
                           $fixtime = $yr . "-" . $mo . "-" . $day . " " . 23 . ":" . $min . ":" . $sec;
                           $fixobj = new DateTime($fixtime);
                           $fixobj->modify("+1 hour");
                           $fixthedate = 1;
                        }
                        $thistime = $yr . "-" . $mo . "-" . $day . " " . $hr . ":" . $min . ":" . $sec;
                        if ($fixthedate == 1) {
                           $dtobj = $fixobj;
                        } else {
                           $dtobj = new DateTime($thistime);
                        }
                        if ($this->debug) {
                           $this->logDebug("Creating Time Stamp Y-m-d H:m:s from: $thistime<br>\n");
                        }
                        $thistime = $dtobj->format('r');
                        $thisdate = $dtobj->format('Y-m-d');
                        $thissec = $dtobj->format('U');
                        $intime = 1;
                        # if we were given start and end dates, look to see if this line is within the bounds that we want
                        if ($ss <> '') {
                           if ($thissec < $ss) {
                              $intime = 0;
                           }
                        }
                        if ($es <> '') {
                           if ($thissec > $es) {
                              $intime = 0;
                           }
                        }
                        if ($this->debug) {
                           $this->logDebug(" $thistime validity check = $intime <br>\n");
                        }

                        # there are other important fields in the EXP file, but for now, I will leave it here
                        # how many values does this persist for? (may be 1, or multiple, in which case we have to massage the time set)
                        $numvals = $tokens[20]; # number of values given, if greater than 1, we have some fancy footwork to do
                        $interval = $tokens[14]; 
                        $thisval= ltrim(rtrim($tokens[24]));
                        $repeater = $tokens[22];
                        # 0 - no repeating values, if multiple, expect that many columns to follow,
                        # 1 - single value given, if multiple, use this value each time
                        if ($intime == 1) {
                           array_push($these_dsns[$dsn]['tsvalues'], array('thistime'=>$thistime, 'thisdate'=>$thisdate, 'thisvalue'=>$thisval));
                           //if ($vals_got < 5) {
                           //   error_log("Line Start: $thistime, 'thisdate' $thisdate, 'thisvalue' $thisval \n<br>\n");
                           //   error_log("Number of Values in Group: $numvals <br>\n");
                           //}
                        }
                        # now, check if this is multiple, if so, we have to parse the second number from the first line, then each
                        # successive line until we reach the end of our data
                        $intxt = '';
                        switch ($interval) {
                           case 3:
                              $intxt = 'hours';
                           break;
                           case 4:
                              $intxt = 'days';
                           break;

                           case 5:
                              $intxt = 'months';
                           break;
                        }
                        if ($numvals > 1) {
                           # check ahead, if we fall inside of numvals intervals, then parse, otherwise, skip
                           $fobj = clone $dtobj;
                           $fobj->modify("+$numvals $intxt");
                           $sps = $thissec; # the start of this chunk of multiline data
                           $spe = $fobj->format('U');  # the end of this chunk
                           if ($ss == '') {
                              $dss = $thissec; # either the current time, or the start of the desired interval (if set)
                           } else {
                              $dss = $ss;
                           }
                           if ($es == '') {
                              $des = $spe; # the end of the desired interval
                           } else {
                              $des = $es;
                           }
                           if ( ($sps <= $des) and ($spe >= $dss) ) {
                              $parsespan = 1;
                           } else {
                              $parsespan = 0;
                           }
                           #error_log("Parse $parsespan ($sps <= $des) and ($spe >= $dss) ");
                           if (in_array($interval, $validintervals) and $parsespan) {

                              if ($repeater == 1) {
                                 if ($this->debug) {
                                    $this->logDebug("Repeater needed, $thisval repeats $numvals times<br>\n");
                                 }
                                 # just duplicate the value, otherwise, we have to parse the stuff
                                 $vals_got = 0;
                                 while ($vals_got < $numvals) {
                                    $dtobj->modify("+1 $intxt");
                                    $thissec = $dtobj->format('U');
                                    if ( ($ss <> '') or ($es <> '')) {# if we were given start and end dates, look to see if this line is within the bounds that we want
                                       if ($ss <> '') {
                                          if ($thissec < $ss) {
                                             $intime = 0;
                                          }
                                       }
                                       if ($es <> '') {
                                          if ($thissec > $es) {
                                             $intime = 0;
                                          }
                                       }
                                    } else {
                                       $intime = 1;
                                    }
                                    $thistime = $dtobj->format('r');
                                    $thisdate = $dtobj->format('Y-m-d');
                                    if ($intime == 1) {
                                       array_push($these_dsns[$dsn]['tsvalues'], array('thistime'=>$thistime, 'thisdate'=>$thisdate, 'thisvalue'=>$thisval));
                                    }
                                    $vals_got++;
                                 }
                              } else {
                                 # add the second variable on the first line
                                 //$line2reg = '([ 0-9A-Za-z:.\-\+]{63})([ 0-9.E\-\+]{12})';
                                 $line2reg = '/([ 0-9A-Za-z:.+-]{63})([ 0-9.E+-]{12})/';
                                 $vals_got = 1;
                                 preg_match($line2reg, $thisline, $tokens);
                                 // the second value on the first line shuld be at position 25
                                 $nextval = ltrim(rtrim($tokens[2]));
                                 $vals_got++;
                                 $dtobj->modify("+1 $intxt");
                                 $thissec = $dtobj->format('U');
                                 if ( ($ss <> '') or ($es <> '')) {# if we were given start and end dates, look to see if this line is within the bounds that we want
                                    if ($ss <> '') {
                                       if ($thissec < $ss) {
                                          $intime = 0;
                                       }
                                    }
                                    if ($es <> '') {
                                       if ($thissec > $es) {
                                          $intime = 0;
                                       }
                                    }
                                 } else {
                                    $intime = 1;
                                 }
                                 if ($intime == 1) {
                                    $thistime = $dtobj->format('r');
                                    $thisdate = $dtobj->format('Y-m-d');
                                    array_push($these_dsns[$dsn]['tsvalues'], array('thistime'=>$thistime, 'thisdate'=>$thisdate, 'thisvalue'=>$nextval));
                                    if ($this->debug) {
                                       $this->logDebug("2nd value added " . $thisdate . " = " . $nextval);
                                       $this->logDebug("Adding DSN $dsn: " . $thisdate . " = " . $nextval);
                                    }
                                 }
                                 # now, figure out how many lines should contain the rest of this record, and retrieve them and parse them
                                 # max 6 records per line
                                 $numlines = ceil(($numvals - $vals_got) / 6.0);
                                 for ($i = 1; $i <= $numlines; $i++) {
                                    # now get the line
                                    # old-school, OK if you have fast file system, otherwise, no good
                                    # $thisline = fgets($fhandle,255);
                                    # new school
                                    $k++;
                                    $thisline = $wholefile[$k];

                                    # calulate the number of entries that should be on this line
                                    $getnum = $numvals - $vals_got;
                                    if ($getnum > 6) {
                                       $getnum = 6;
                                    }
                                    # assemble the regexp to parse the next line of values
                                    // what this does is compute the total number of characters needed to assemble the
                                    // regexp string, so numreglen needs to be the string length of the base regular expression, so if you increase or decrease characters in the regexp (to make it more robust, or more compact) you will need to change numreglen
                                    $charlen = $spacexplen + $getnum * $numreglen;
                                    //$charlen = $getnum * 12;
                                    //$reg = str_pad('([ ]{3})', $charlen, '([ 0-9.E\-\+]{12})');
                                    $reg = '/' . str_pad('([ ]{3})', $charlen, '([ 0-9.E+-]{12})') . '/';

                                    $tokens = array();
                                    if ($this->debug) {
                                       $this->logDebug("Ereg for line $i, $getnum values, charlen $charlen: $reg<br>\n");
                                    }
                                    preg_match($reg, $thisline, $tokens);
                                    $numtokes = count($tokens);
                                    if ($this->debug) {
                                       $this->logDebug("Line $i; $numtokes tokens, $getnum values wanted<br>\n");
                                       $this->logDebug("Tokens; " . print_r($tokens,1) . "<br>\n");
                                    }

                                    $cols = $numtokes - 1;
                                    for ($a = 2; $a <= $cols; $a++) {
                                       $nextval = ltrim(rtrim($tokens[$a]));
                                       $dtobj->modify("+1 $intxt");
                                       $thissec = $dtobj->format('U');
                                       $intime = 1;
                                       if ( ($ss <> '') or ($es <> '')) {# if we were given start and end dates, look to see if this line is within the bounds that we want
                                          if ($ss <> '') {
                                             if ($thissec < $ss) {
                                                $intime = 0;
                                             }
                                          }
                                          if ($es <> '') {
                                             if ($thissec > $es) {
                                                $intime = 0;
                                             }
                                          }
                                       }

                                       if ($intime == 1) {
                                          $thistime = $dtobj->format('r');
                                          $thisdate = $dtobj->format('Y-m-d');
                                          if ($this->debug) {
                                             $this->logDebug("Token position $a; Date/Time: $thistime, value = $nextval<br>\n");
                                          }

                                          array_push($these_dsns[$dsn]['tsvalues'], array('thistime'=>$thistime, 'thisdate'=>$thisdate, 'thisvalue'=>$nextval));
                                          if ($this->debug) {
                                             $this->logDebug("Adding DSN $dsn: " . $thisdate . " = " . $nextval . "<br>\n");
                                          }
                                       }
                                       $vals_got++;
                                    }
                                 }
                              }
                           }
                        }
                     }
                  }
               }
               #$this->debug = 0;
               #$this->debugmode = 0;
            } else {
               if ($in_label) {
                  if ($this->debug) {
                     $this->logDebug("Checking for LABEL end<br>\n");
                  }
                  # not yet in data block, look for the data block start
                  preg_match($this->ereg_format['label_end'], $thisline, $tokens);
                  if (count($tokens) >= 3) {
                     if (rtrim(ltrim($tokens[2])) == 'END LABEL') {
                        if ($this->debug) {
                           $this->logDebug ("Found LABEL end.<br>\n");
                        }
                        $in_label = 0;
                     }
                     # check for label pieces of interest
                     if ($this->debug) {
                        $this->logDebug("Checking for LABEL entries <br>\n");
                     }
                  } else {
                     if ($this->debug) {
                        $this->logDebug("No match for label end <br>\n");
                     }
                  }
                  preg_match($this->ereg_format['label_data'], $thisline, $tokens);
                  if (count($tokens) >= 5) {
                     if (rtrim(ltrim($tokens[2])) <> '') {
                        if ($this->debug) {
                           $this->logDebug ("Found " . $tokens[2] . "-" . $tokens[4] . ".<br>\n");
                        }
                        # store this label entry
                        if ($desired) {
                           $these_dsns[$dsn]['labels'][$tokens[2]] = $tokens[4];
                        }
                     }
                  }
               } else {
                  if ($this->debug) {
                     $this->logDebug("Checking for label start<br>\n");
                  }
                  # not yet in data block, look for the data block start
                  preg_match($this->ereg_format['label_start'], $thisline, $tokens);
                  if (count($tokens) >= 3) {
                     if (rtrim(ltrim($tokens[2])) == 'LABEL') {
                        if ($this->debug) {
                           $this->logDebug ("Found LABEL START.<br>\n");
                        }
                        $in_label = 1;
                     }
                  }
               }
               if ($this->debug) {
                  $this->logDebug("Checking for DATA start<br>\n");
               }
               # not yet in data block, look for the data block start
               preg_match($this->ereg_format['data_start'], $thisline, $tokens);
               if (count($tokens) >= 3) {
                  if ($tokens[2] == 'DATA') {
                     if ($this->debug) {
                        $this->logDebug ("Found DATA Block.<br>\n");
                     }
                     $in_data = 1;
                  }
               }
               if ($this->debug) {
                  $this->logDebug("Checking for DSN END<br>\n");
               }
               # not yet in data block, look for the data block end
               preg_match($this->ereg_format['dsn_end'], $thisline, $tokens);
               if (count($tokens) >= 2) {
                  #print(print_r($tokens,1) . "<br>\n");
                  if (rtrim(ltrim($tokens[1])) == 'END DSN') {
                     if ($this->debug) {
                        $this->logDebug ("Found END DSN " . $dsn . ".<br>\n");

                     }
                     $in_dsn = 0;
                     $in_data = 0;
                  }
                  if (!$in_dsn) {
                     # check to see if we have gotten all of our dsns
                     if (isset($label_criteria['DSN'])) {
                        if (count($label_criteria['DSN']) == 0) {
                           if ($this->debug) {
                              $this->logDebug("Found all requested DSN's.  Returning.<br>\n");
                           }
                           #error_log("Found all requested DSN's.  Returning.<br>\n");
                           // Commented in lieu of reading whole file into memory
                           //fclose($fhandle);
                           return $these_dsns;
                        }
                     }
                  }
               }
            }

         } else {
            # look for the DSN tag
            preg_match($this->ereg_format['dsn_line'], $thisline, $tokens);
            if (count($tokens) >= 2) {
               if ($tokens[1] == 'DSN') {
                  if ($this->debug) {
                     $this->logDebug ("found DSN line with DSN ID = " . $tokens[3] . "<br>\n");
                  }
                  if ($this->debug) error_log ("found DSN line with DSN ID = " . $tokens[3] . "<br>\n");
                  $in_dsn = 1;
                  $in_data = 0;
                  #$dsn = $tokens[3];
                  $dsn = ltrim(rtrim($tokens[3]));
                  $desired = 1;
                  if (count($label_criteria) > 0) {
                     #we have been asked to only retrieve certain DSNs or LABEL-types
                     if ($this->debug) {
                        $this->logDebug("Screening for criteria " . print_r($label_criteria,1) . "<br>\n");
                     }
                     $cosas = array_keys($label_criteria);
                     if (in_array('DSN', $cosas)) {
                        $ds = $label_criteria['DSN'];
                        if ($this->debug) {
                           $this->logDebug("User requested DSNs " . print_r($ds,1) . "<br>\n");
                        }
                        if (!is_array($ds)) {
                           $ds = array($ds);
                        }
                        if (!in_array($dsn, $ds)) {
                           $desired = 0;
                           if ($this->debug) {
                              $this->logDebug("DSN $dsn is not required <br>\n");
                           }
                        } else {
                           if ($this->debug) {
                              $this->logDebug("Found Required DSN $dsn <br>\n");
                           }
                           $cache_criteria = array();
                           foreach ($ds as $thisds) {
                              if ($thisds <> $dsn) {
                                 # not the droid we were looking for, so add it back in the queue
                                 array_push($cache_criteria, $thisds);
                              }
                           }
                           $label_criteria['DSN'] = $cache_criteria;
                        }
                     }
                  }
                  if ($desired <> 0) {
                     $these_dsns[$dsn] = array('DSN' => $dsn, 'tsvalues'=>array(), 'labels'=>array());
                  }
               }
            }
         }
      }
      $outmesg = "Finished EXP parse <br>\n";
      //error_log($outmesg);

      fclose($fhandle);
      return $these_dsns;
   }

   function exportWDM($infile='', $expfilename ='', $tmp = 1, $dsns = array()) {
      # NOTE: it is very important that if you are on a dos system (maybe others too) that the wdimex routine
      # does NOT like long file names, nor long file paths, and in fact, there is a maximum file name length
      # thus, when using wdimex to export, one needs to have a temp dir that is a short path,
      # c:\temp\ is good for windows, and of course, \tmp\ is a safe bet for unix
      # if the user passes in there own infile and expfile, these checks do not get made, so buyer beware
      # if no file is passed in, assume it is this file
      # if dsns is a blank array, it will export all datasets, which can be 
      # very time consuming, thus it is always more efficient to single them out
      # particularly if you onlyu need a small number of dsns
      if (strlen($infile) == 0) {
         $infile = $this->filepath;
      }

      # we assume this is dos so me have to create a tmep wdm file of proper length or wdimex will choke
      $base = '';
      $ext = 'wdm';
      if ($this->debug) {
         $this->logDebug("Trying to create WDM with base: $base and ext: $ext <br>");
      }
      # using 0 and 99999 will automatically generate a filename with less than or equal to 8 places (since the base is 3)
      $wdmfilename = $this->generateTempFileName($base, $ext, 0, 99999);
      if ($this->debug) {
         $this->logDebug("WDM FILE: $wdmfilename <br>");
      }
      //error_log("WDM FILE: $wdmfilename <br>");
      # now, copy the wdm to the temp file
      //if ($this->debug) {
         $this->logDebug("Copying $infile to $wdmfilename <br>");
      //}
      $wdmfile = $this->copy2TempFile($infile, $wdmfilename);

      if (strlen($expfilename) == 0) {
         # make a file name
         # assume this is a dos platform, we must create a file name that is the proper length,
         # or wdimex will choke
         $base = 'wdm';
         $ext = 'exp';
         # using 0 and 99999 will automatically generate a filename with less than or equal to 8 places (since the base is 3)
         if ($this->debug) {
            $this->logDebug("Calling file name generation routine<br>");
         }
         $expfilename = $this->generateTempFileName($base, $ext, 0, 99999);
         //if ($this->debug) {
            $this->logDebug("File name generation returned: $expfilename<br>");
         //}
      }
      # open and close it just to get it in the system
      $this->openTempFile($expfilename, 'rw', 'file', $this->platform);
      $this->closeTempFile($expfilename);
      if ($this->debug) {
         $this->logDebug("EXP FILE: $expfilename <br>");
      }

      # we must format the commands differently if we are trying to export 
      # individual dsns, or if we are exporting them all
      $wdimex = array();
      if (count($dsns) > 0) {
         # now assemble the export function commands for ALL DSN's in this volume
         /* do an import from existing wdm */
         array_push($wdimex,"$wdmfile\n");  /* send the wdm name to wdimex */
         array_push($wdimex,"$this->wdm_messagefile\n");   /* indicate the wdm file which has header information */
         array_push($wdimex,"E\n");   /* select export option */
         array_push($wdimex,"$expfilename\n");   /* give the import file name */
         array_push($wdimex,"\n");  /* Carriage Return to end comment line */
         array_push($wdimex,"S\n");  /* Export Single at a time */
         foreach ($dsns as $thisdsn) {
            array_push($wdimex,"$thisdsn\n");  /* Export DSN Number */
         }
         array_push($wdimex,"0\n");  /* 0 tells it we are finished */
         array_push($wdimex,"R\n");  /* return to operating system */
         $overwrite = 1;
         //if ($this->debug) {
            $this->logDebug("Generated Script: " . print_r($wdimex,1) . "<br>");
         //}
      } else {
         # create a file name for this?
         # now assemble the export function commands for ALL DSN's in this volume
         /* do an import from existing wdm */
         array_push($wdimex,"$wdmfile\n");  /* send the wdm name to wdimex */
         array_push($wdimex,"$this->wdm_messagefile\n");   /* indicate the wdm file which has header information */
         array_push($wdimex,"E\n");   /* select export option */
         array_push($wdimex,"$expfilename\n");   /* give the import file name */
         array_push($wdimex,"\n");  /* Carriage Return to end comment line */
         array_push($wdimex,"A\n");  /* Export All */
         array_push($wdimex,"A\n");  /* Update All Attributes */
         array_push($wdimex,"R\n");  /* return to operating system */
         $overwrite = 1;
         if ($this->debug) {
            $this->logDebug("Generated Script: " . print_r($wdimex,1) . "<br>");
         }
      }
      # need to genreate a temp file name for the proc log file so that it 
      # will not create conflicts
      $proclogfile = $this->generateTempFileName('proc', 'log', 0, 99999);
      $descriptorspec = array(
         0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
         1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
         2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
      );

      $cwd = '/tmp';
      $env = array('some_option' => 'aeiou');
      if ($this->debug) {
         $this->logDebug("Calling $this->wdimex_exe <br>\n");
      }
      //error_log("Calling $this->wdimex_exe <br>\n");
      $process = proc_open($this->wdimex_exe, $descriptorspec, $pipes, $cwd, $env);

      if (is_resource($process)) {
         // $pipes now looks like this:
         // 0 => writeable handle connected to child stdin
         // 1 => readable handle connected to child stdout
         // Any error output will be appended to /tmp/error-output.txt

         foreach ($wdimex as $thisline) {
            //error_log($thisline . "<br>");
            fwrite($pipes[0], $thisline);
            #echo stream_get_contents($pipes[1]) . "<br>";
         }
         fclose($pipes[0]);

         //error_log(stream_get_contents($pipes[1]));
         fclose($pipes[1]);

         // It is important that you close any pipes before calling
         // proc_close in order to avoid a deadlock
         $return_value = proc_close($process);

         error_log("command returned $return_value\n");
      } else {
         error_log("Process did not run");
      }

      if ($this->debug) {
         $this->logDebug("<br>Command Output:<br> $wdimexresult <br>");
      }
      return $expfilename;

   }

   function importEXPFile($expfile, $wdmfile='') {
      if (strlen($wdmfile) == 0) {
         $wdmfile = $this->filepath;
      }
      $wdimex = array();

      # verify that exp file exists, otherwise, return with Error
      if (!file_exists($expfile)) {
         $this->logError("<b>Error:</b> Export File $expfile does not exist<br>");
      }
      if (!file_exists($wdmfile)) {
         $this->logError("<b>Notice:</b> WDM File $wdmfile does not exist, trying to create<br>");
         /* create the wdm, import the exp file, then run hspf and get the data */
         array_push($wdimex,"$wdmfile");  /* send the wdm name to wdimex */
         array_push($wdimex,"C");   /* select to create the WDM */
         array_push($wdimex,"N");   /* say no to putting headers on the file */
         array_push($wdimex,"$this->wdm_messagefile");   /* indicate the wdm file which has header information */
         array_push($wdimex,"R");  /* return to operating system */

         if ($this->debug) {
            $this->logDebug("Generated Script: " . print_r($wdimex,1) . "<br>");
         }
         $scriptfile = $this->generateTempFileName('exp', 'txt', 0, 99999);
         # open and close it just to get it in the system
         $this->openTempFile($scriptfile, 'rw', 'file', $this->platform);
         $this->closeTempFile($scriptfile);
         putArrayToFilePlatform("$scriptfile",$wdimex,1,$this->platform);
         # now, try to execute the create script
         if ($this->debug) {
            $this->logDebug("<br>Trying to use wdimex to create the WDM with command:<br>cat $scriptfile | $this->wdimex_exe<br>");
         }
         $wdimexresult = shell_exec("cat $scriptfile | $this->wdimex_exe");
         if ($this->debug) {
            $this->logDebug("<br>Command Output:<br> $wdimexresult <br>");
         }

      }

      if (!file_exists($wdmfile)) {
         $this->logError("<b>Error:</b> WDM $wdmfile does not exist, and could not create <br>");
         return;
      } else {
         # clear the wdimex array for the insert statement
         $wdimex = array();
         /* do an import to existing wdm */
         array_push($wdimex,"$wdmfile");  /* send the wdm name to wdimex */
         array_push($wdimex,"$this->wdm_messagefile");   /* indicate the wdm file which has header information */
         array_push($wdimex,"I");   /* select import option */
         array_push($wdimex,"$expfile");   /* give the import file name */
         array_push($wdimex,"R");  /* return to operating system */

         if ($this->debug) {
            $this->logDebug("Generated Script: " . print_r($wdimex,1) . "<br>");
         }
         $scriptfile = $this->generateTempFileName('exp', 'txt', 0, 99999);
         # open and close it just to get it in the system
         $this->openTempFile($scriptfile, 'rw', 'file', $this->platform);
         $this->closeTempFile($scriptfile);
         putArrayToFilePlatform("$scriptfile",$wdimex,1,$this->platform);
         # now, try to execute the create script
         if ($this->debug) {
            $this->logDebug("<br>Trying to use wdimex to create the WDM with command:<br>cat $scriptfile | $this->wdimex_exe<br>");
         }
         $wdimexresult = shell_exec("cat $scriptfile | $this->wdimex_exe");
         if ($this->debug) {
            $this->logDebug("<br>Command Output:<br> $wdimexresult <br>");
         }

      }
      return $wdmfile;


   }

   function unionWDMs($src_wdms, $wdmfile) {
      if (strlen($wdmfile) == 0) {
         $wdmfile = $this->filepath;
      }

      foreach ($src_wdms as $this_src) {
         $expfile = $this->exportWDM($this_src);
         $this->importEXPFile($expfile, $wdmfile);
      }

      return;

   }

   function init_old($tsobject, $filename, $paramcolumns, $totaldatacols, $startdate, $enddate, $debug) {

      # part of modeling widgets, expects lib_hydrology,php, and HSPFFunctions.php to be included
      # expects user to create a time series object, $flow2, and pass it as an input
      # adds time series data from a plotgen to the object, and returns it

      # $paramcolumns is an array of format:
      #   array('paramname'=>paramcolumn)
      # $totaldatacols = the number of total output columns in the file, should figure out a way to
      #  determine this automatically
      # parseHSPFMultiout($infilename,$thiscolumn, $totaldatacols)

      # $rectype = 0, hourly, 1 - daily
      $this->maxflow = 0;
      if ($this->timer->thistime <> '') {
         $sd = $this->timer->thistime;
         $sdts = $sd->format('U');
      }
      if ($this->timer->endtime <> '') {
         $ed = $this->timer->endtime;
         $edts = $ed->format('U');
      }

      #$this->logDebug("Parsing Plotgen File: $staid <br>");
      $plotgen = parseHSPFMultiout($this->filepath,1, $totaldatacols, 0);
      # column 0 contains header data, column 1 contains the actual data
      $filedata = $plotgen[1];

      foreach ($filedata as $thisdata) {
         if ($this->debug) {
            $this->logDebug($thisdata);
         }
         $thisdate = new DateTime($thisdata[0]);
         $ts = $thisdate->format('r');
         $uts = $thisdate->format('U');
         $thisflag = '';
         # default to missing
         $thisval = 0.0;
         #$this->logDebug(array_keys($paramcolumns));
         #$this->logDebug("<br>");
         $withinperiod = 1;
         if ( ($this->timer->thistime <> '') and ($sdts > $uts) ) {
            # if have defined a start date, and this date is less than the start date, do not add
            $withinperiod = 0;
         }
         if ( ($this->timer->endtime <> '') and ($edts < $uts) ) {
            # if have defined an end date, and this date is greater than the end date, we are done
            break;
         }
         #$this->logDebug("Within: $within (TS: $uts, SDTS: $sdts, EDTS: $edts) <br>");
         if ($withinperiod) {
            foreach (array_keys($paramcolumns) as $dataitem) {

               $thiscol = $paramcolumns[$dataitem];
               if ($thisdata[$thiscol] <> '') {
                  $thisval = $thisdata[$thiscol];
               } else {
                  $thisval = '0.0';
               }
               #$this->logDebug("$dataitem : $thiscol : $thisval <br>");
               # add to timeseries object
               $this->addValue($ts, $dataitem, floatval($thisval));
               if ($dataname == 'Qout') {
                  if ($thisval > $this->maxflow) {
                     $this->maxflow = $thisval;
                  }
               }
            }
            $this->addValue($ts, 'timestamp', $ts);
            $this->addValue($ts, 'thisdate', $thisdate->format('m-d-Y'));
         }
      }
      ksort($this->tsvalues);

      # test - will this modify the object?
      #return $tsobject;
   }


}

class WDMDSN extends timeSeriesInput {

   var $dsn = '';
   var $intmethod = 3;
   var $dtobj;
   var $loggable = 1; // can log the value in a data table

   function setDataColumnTypes() {
      parent::setDataColumnTypes();

      $this->dbcolumntypes['thisvalue'] = 'float8';
      $this->dbcolumntypes['thistime'] = 'timestamp';
      $this->dbcolumntypes['timestamp'] = 'bigint';
   }

   function evaluate() {
      $this->result = $this->state['thisvalue'];
      #error_log("State of DSN $dsn " . print_r($this->state, 1));
   }
   
   function logState() {
      # do not log this item till we figure out why there is the trouble with the
      # thistime column coming out as an integer, and hosing the logging routine.
   }
   
   function cleanUp() {
      parent::cleanUp();
      if (is_object($this->dtobj)) {
         unset($this->dtobj);
      }
   }

   function addDSNRecs($theserecs) {
      # expects records formatted adccording to the routines in HSPFWDM object
      if ($this->debug) {
         $this->logDebug("Adding " . count($theserecs) . ' records <br>');
      }
      //error_log("Adding " . count($theserecs) . ' records <br>');
      //error_log("Sample DSN Record " . print_r($theserecs[0],1) . '  <br>');
      if (!is_object($this->dtobj)) {
         $this->dtobj = new DateTime;
      }
      $n = 0;
      foreach ($theserecs as $thisrec) {
         $ts = date('r',strtotime($thisrec['thistime']));
         $this->addValue($ts, $thisrec);
         #break;
         $n++;
      }
      //error_log("$n values added <br>\n");
      ksort($this->tsvalues);
      if ( (count($this->tsvalues) > $this->max_memory_values) and ($this->max_memory_values > 0)) {
         $this->setDBCacheName();
         //error_log("tsvalues cacheing enabled on $this->name");
         $this->tsvalues2listobject();
         $this->getCurrentDataSlice();
      }
   }

}


class WDMDSNaccessor extends modelObject {

   var $name = '';
   var $wdmoutput = '';
   var $loggable = 1; // can log the value in a data table

   function init() {
      $this->parentobject->activateDSN($this->wdmoutput);
   }

   function evaluate() {
      $this->result = $this->parentobject->getDSNValue($this->wdmoutput);
   }

}


class WatershedShapeFile extends modelContainer {

   var $name = '';
   var $wdmoutput = '';
   var $serialist = 'containment_columns';

   function init() {
      parent::init();
   }

   function create() {
      # this method does any operations that are batched into this object
      # it must be called AFTER the wake() method, but prior to the init() method
      parent::create();
      
      # 1. verify that we have a shape file (or a text file with data in it is OK, 
      #    we will just not have any shapes created)
      # 2. If this is an actual shapefile, convert with shp2psql, else, try to read it 
      #    (have a selector for file type: shp, dbf, csv)
      # 3. Create a temp table with the data from the file
      # 4. Process any containment queries, and create model containers for each level of containment found, 
      #    nesting them by order of the containment column(s)
      
      
   }
   
   

}

class parseFileObject extends timeSeriesInput {

   var $datecol = '';
   var $dateformat = '';
   var $timeformat = '';
   var $timecol = '';
   var $dataURL = '';
   var $missingvals = array();

   function init() {
      parent::init();
      $this->orderOperations();
      $this->retrieveData();

   }

   function setDataColumnTypes() {

      parent::setDataColumnTypes();
      # set up column formats for appropriate outputs to database
      if ( (!isset($this->dbcolumntypes)) or (!is_array($this->dbcolumntypes)) ) {
         $this->dbcolumntypes = array();
      }

      $basetypes = array(
               'abbrevdate'=>'varchar(9)',
               'miltime'=>'varchar(4)'
      );
      $thisarray = array_unique(array_merge($this->dbcolumntypes, $basetypes));
      
      $this->data_cols[] = 'abbrevdate';
      $this->data_cols[] = 'miltime';

      $this->dbcolumntypes = $thisarray;

      $logtypes = array(
               'abbrevdate'=>'%s',
               'miltime'=>'%s'
      );
      $thisarray = array_unique(array_merge($this->logformats, $logtypes));

      $this->logformats = $thisarray;
   }

   function retrieveData() {
      # Basic Format:
      # Date        Time  Inflow  Outflow     Elev     Elev       Gen   Rainfall    ELev     Elev
      # 13JUN2008   1200       0        0    300.40   300.41     0.00      0.00    258.20   258.28
      #
      # date format: DDmonYYYY (mon is 3 letter month abbreviation)
      # time format: MIL  (4 digit with leading zeroes)
      # datecol and timecol must be set, if timecol is '', then we will create a timestamp with out it, which defaults to 00:00:00
      # datecol MUST be set to retrieve data
      $this->datecol = 'abbrevdate';
      $this->timecol = 'miltime';
      $this->dateformat = '/([ 0-9]{2})([ A-Z]{3})([ 0-9]{4})/';
      $this->timeformat = '/([0-9]{2})([0-9]{2})/';
      # these will be passed in later, perhaps if this has widespread use, for now they help to convert the parsed date,
      # which can be in any order (not necessarily one interpretable by php) and turn it into an ordered time/date
      $dateorder = array('d','m','y');
      $timeorder = array('h','m');
      $this->missingvals = array('?MIS', '?MISS', '-99', '?MISST');

      if ($this->debug) {
         $this->logDebug("Retrieve Date method called." );
      }

      if ( strlen($this->datecol) > 0) {

         # this will serve as a template for quick and dirty data retrieval and parsing
         # this requires the input of a parsing string, and a date and time field
         # normally, the user will enter the "linereg" and the "colnames" as field inputs, but for now,
         # we will hardwire these
         $linereg = '/([A-Z0-9]{9}) ([ 0-9]{6}) ([ -.A-Z?0-9]{7}) ([ -.A-Z?0-9]{8}) ([ -.A-Z?0-9]{9}) ([ -.A-Z?0-9]{8}) ([ -.A-Z?0-9]{8}) ([ -.A-Z?0-9]{9}) ([ -.A-Z?0-9]{9}) ([ -.A-Z?0-9]{8})/';
         $colnames = array('abbrevdate', 'miltime', 'inflow', 'outflow', 'elev1', 'elev2', 'gen_mwh', 'rainfall', 'gage1_msl', 'gage2_msl');

         if ($this->debug) {
            $this->logDebug("Trying to retrieve " .$this->dataURL );
         }
         
         $ctx = stream_context_create(array(
             'http' => array(
                 'timeout' => 240
                 )
             )
         );
         $indata = file_get_contents($this->dataURL, 0, $ctx);
         if ($this->debug) {
            $this->logDebug("Retrieved " .$this->dataURL );
         }
         $inlines = explode("\n", $indata);

         $parsing = 0;
         $thisentry = array();
         # just start parsing, we will do it until we reach the end
         foreach ($inlines as $thisline) {
            #print("$thisline \n");
            # must clear the $parsed array, since a failure will NOT overwrite any previous contents

            $parsedvals = array();
            preg_match($linereg, $thisline, $parsedvals);
            if ($this->debug) {
               $this->logDebug("File Entry Parsed to:");
               $this->logDebug($parsedvals);
            }
            $data = array_slice($parsedvals, 1, count($parsedvals) - 1);
            $thisentry = array_combine($colnames, $data );
            if ($this->debug) {
               $this->logDebug("Data and Keys merged to:");
               $this->logDebug($thisentry);
            }
            $dateparts = array();
            $ps = array();
            preg_match($this->dateformat, $thisentry[$this->datecol], $ps);
            $ardate = array_slice($ps, 1, count($ps) - 1);
            if ($this->debug) {
               $this->logDebug("Data :" . $thisentry[$this->datecol] . ' ');
               $this->logDebug("Parsed to:");
               $this->logDebug($ardate);
                  $this->logDebug("From:");
               $this->logDebug($ps);
            }
            foreach ($dateorder as $thispart) {
               $dateparts[$thispart] = array_shift($ardate);
            }
            $timeparts = array('h'=>0,'m'=>0,'s'=>0);
            if (strlen($this->timeformat) > 0) {
               $parsedvals = array();
               preg_match($this->timeformat, $thisentry[$this->timecol], $parsedvals);
               $artime = array_slice($parsedvals, 1, count($parsedvals) - 1);
               if ($this->debug) {
                  $this->logDebug("Data :" . $thisentry[$this->timecol] . ' ');
                  $this->logDebug("Parsed to:");
                  $this->logDebug($artime);
                  $this->logDebug("From:");
                  $this->logDebug($parsedvals);
               }
               foreach ($timeorder as $thispart) {
                  $timeparts[$thispart] = array_shift($artime);
               }
            }
            # only adds it if it is a valid date
            if ($this->debug) {
               $this->logDebug("Checking Validity of Date:");
               $this->logDebug($dateparts);
            }
            if (strtotime($dateparts['m'] . ' ' . $dateparts['d'] . ' ' . $dateparts['y'])) {
               $datestamp = strtotime($dateparts['m'] . ' ' . $dateparts['d'] . ' ' . $dateparts['y']);
               list($dateparts['m'],$dateparts['d'],$dateparts['y']) = explode('-',date('n-j-Y', $datestamp));
               $timeseconds = mktime(ltrim($timeparts['h']),ltrim($timeparts['m']),$timeparts['s'],$dateparts['m'],$dateparts['d'],$dateparts['y']);
               $timestamp = date('r', $timeseconds);
               $thisdate = date('m-d-Y', $timeseconds);
               $thisentry['timestamp'] = $timestamp;
               $thisentry['thisdate'] = $thisdate;
               if ($this->debug) {
                  $this->logDebug("Number of entries in array: " . count(array_keys($thisentry)) . "\n");
               }
               if (count(array_keys($thisentry)) > 2 ) {
                  if ($this->debug) {
                     $this->logDebug("Adding " . count(array_keys($thisentry)) . " Values\n");
                  }
                  foreach($thisentry as $thiskey => $thisval) {
                     # set to NULL if it is a missing code

                     if (in_array(ltrim(rtrim($thisval)), $this->missingvals)) {
                        $this->logDebug("Missing Value Found $thisdate " . $timeparts['h'] . ":" . $timeparts['m'] . " ". $thisval);
                        $thisval = 'NULL';
                     }
                     $this->addValue($timestamp, $thiskey, $thisval);
                  }
                  if ($this->debug) {
                     $this->logDebug("Entry Added for $thisdate " . $timeparts['h'] . ":" . $timeparts['m'] . " ");
                     $this->logDebug($thisentry);
                  }
               }
            }
         }
      }
   }
}

class droughtMonitor extends modelObject {
   # shell object for drought monitoring
   var $flowgage = '';
   var $palmer_region = '';
   var $gw_gage = '';
   var $the_geom = '';


}

class hydroImpSmall extends hydroImpoundment {
   // this is a general purpose class for a simple flow-by
   // other, more complicated flow-bys will inherit this class
   var $rvars = array();
   var $wvars = array();
   var $storage_matrix = -1; // object shell for storage table
   var $matrix = array(); // array shell for storage table
   var $serialist = 'rvars,wvars,matrix';
   var $refill = 0;
   var $et_in = 0;
   var $precip_in = 0;
   // adding to form - must have 
   // * state var set in wake() 
   // * var must exist on class
   // * var must exist in adminsetup
   var $riser_shape = 'rectangular'; // @todo: add option for circular
   var $riser_diameter = 0; // the width of the orifice opening, either rectangular or circular
   var $riser_pipe_flow_head = 0; // the head above pipe opening when flow becomes actual pipe flow
   var $riser_length = 0; // the full length of the pipe
   var $riser_opening_elev = 0; // relative to stage in stage/storage table
   var $riser_opening_storage = 0; // relative to stage in stage/storage table
   var $demand = 0;
   var $riser_enabled = 0;
   var $release = 0;
   var $text2table = '';
   var $outlet_plugin = FALSE;
   var $log_solution_problems = FALSE;
   var $tmpfile = '';
   var $json2d = TRUE;
   
   function writeToParent($vars = array(), $verbose = 0) {
     // @todo: eliminate this after debugging is finished
      parent::writeToParent($vars, $verbose);
      if ($this->log_solution_problems) {
        $ts = $this->this->timer->timestamp;
        $storage = $this->parentobject->state[$this->getParentVarName("Storage")];
        $riser_head = $this->parentobject->state[$this->getParentVarName("riser_head")];
        $riser_flow = $this->parentobject->state[$this->getParentVarName("riser_flow")];
        $its = $this->parentobject->state[$this->getParentVarName("its")];
        if ($this->timer->steps == 0) {
          fwrite($this->tmpfile, "timestamp,storage,riser_head,riser_flow,its\n");
        }
        fwrite($this->tmpfile, "$ts,$storage,$riser_head,$riser_flow,$its\n");
      }
   }

   // *************************************************
   // BEGIN - Special Parent Variable Setting Interface
   // *************************************************
   function setState() {
      parent::setState();
      $this->rvars = array('et_in','precip_in','release','demand', 'Qin', 'refill');
      // since this is a subcomp need to explicitly declare which write on parent
      $this->wvars = array('Qin', 'evap_mgd','Qout','lake_elev','Storage', 'refill_full_mgd', 'demand', 'use_remain_mg', 'days_remaining', 'max_usable', 'riser_stage', 'riser_head', 'riser_mode', 'riser_flow', 'riser_diameter', 'demand_met_mgd', 'its', 'spill', 'release', 'area');
      
      $this->initOnParent();
   }

   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      foreach ($this->varsToSetOnParent() as $thisvar) {
         if ($this->debug) {
            $this->logDebug("Setting $thisvar to type float8 on parent.<br>\n");
         }
         //error_log("$this->name calling parentobject -> setSingleDataColumnType($thisvar, 'float8', 0.0) ");
         $this->parentobject->setSingleDataColumnType($thisvar, 'float8', 0.0);
         if (!in_array($thisvar, $this->vars)) {
            $this->vars[] = $thisvar;
         }
      }
   }
   
   
   // *************************************************
   // END - Special Parent Variable Setting Interface
   // *************************************************

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
   
   function init() {
      parent::init();
      $this->setupOutlet();
      if ($this->debug) {
         $this->logDebug("init() method set variables to " . print_r($this->state,1) . "<br>");
      }
      $this->tmpfile = fopen("/tmp/custom.$this->componentid" . ".log", 'w');
   }

   function wake() {
      parent::wake();
      $this->setSingleDataColumnType('riser_diameter', 'float8',$this->riser_diameter);
      $this->setSingleDataColumnType('riser_shape', 'varchar(32)',$this->riser_shape);
      $this->setSingleDataColumnType('riser_mode', 'varchar(32)','');
      $this->setSingleDataColumnType('riser_pipe_flow_head', 'float8',0);
      $this->setSingleDataColumnType('riser_opening_elev', 'float8',0);
      $this->setSingleDataColumnType('riser_length', 'float8',$this->riser_length);
      $this->setSingleDataColumnType('riser_enabled', 'integer',$this->riser_enabled);
      $this->setSingleDataColumnType('riser_flow', 'float8',0);
      $this->setSingleDataColumnType('riser_head', 'float8',0);
      $this->setSingleDataColumnType('riser_storage_estimate', 'float8',0);
      $this->setSingleDataColumnType('its', 'float8',0);
      $this->setupMatrix();
      $this->initOnParent();
   } 
   
  function setupOutlet() {
    if (!$this->riser_enabled) {
      return;
    }
    //@todo: replace this with proper plugin detection code
    include_once("/var/www/html/om/plugins/omRuntime_HydroRiser.class.php");
    $config = array(
      'container' => &$this,
      'storage_stage_area' => &$this->processors['storage_stage_area'],
      'riser_opening_storage' => $this->riser_opening_storage,
      'riser_length' =>  $this->riser_length,
      'riser_diameter' =>  $this->riser_diameter,
      'riser_pipe_flow_head' =>  $this->riser_pipe_flow_head,
    );
    $this->outlet_plugin = new omRuntime_HydroRiser($config);
    if (!$this->outlet_plugin) {
      $this->logDebug("Riser enabled TRUE but could not create object with " . print_r($config,1));
    }
  }

   function sleep() {
      parent::sleep();
      // set up the matrix for this element
      unset($this->storage_matrix);
      $this->storage_matrix = -1;
      $this->vars = array();
      $this->outlet_plugin = FALSE;
      fclose($this->tmpfile);
   }
   
   function create() {
      parent::create();
      if ($this->debug) error_log("Create routine called on $this->name with $this->text2table");
      //error_log("Create routine called on $this->name with $this->text2table");
      $this->matrix = array();
      $this->setupMatrix($this->text2table);
      // check to see if the text2table field has anything in it
      // if so, parse the text appropriately
      //error_log("Storage matrix created $this->name ");
   }
   
   function setupMatrix($text2table = '') {
      $this->storage_matrix = new dataMatrix;
      $this->storage_matrix->name = 'storage_stage_area';
      $this->storage_matrix->wake();
      //$this->storage_matrix->debug = 1;
      //$this->storage_matrix->debugmode = 1;
      $this->storage_matrix->delimiter = $this->delimiter;
      $this->storage_matrix->numcols = 3;
      $this->storage_matrix->fixed_cols = true;
      $this->storage_matrix->valuetype = 2; // 1 column lookup (col & row)
      $this->storage_matrix->keycol1 = ''; // key for 1st lookup variable
      $this->storage_matrix->lutype1 = 1; // lookup type for first lookup variable: 0 - exact match; 1 - interpolate values; 2 - stair step
      $this->storage_matrix->keycol2 = ''; // key for 1st lookup variable
      $this->storage_matrix->lutype2 = 1; // lookup type for 2nd lookup variable: 0 - exact match; 1 - interpolate values; 2 - stair step
      if ($this->debug) error_log("setUpMatrix called with $text2table ");
      // add a row for the header line
      if ( (!is_array($this->matrix) or (count($this->matrix) == 0)) and ($text2table == '') ) {
        // need to initialize storage table
         $this->matrix = array('storage','stage','surface_area',0,0,0);
         $this->storage_matrix->numrows = 3;
         $this->storage_matrix->matrix[] = 'storage';
         $this->storage_matrix->matrix[] = 'stage';
         $this->storage_matrix->matrix[] = 'surface_area';
         $this->storage_matrix->matrix[] = 0; // put a basic sample table - conic
         $this->storage_matrix->matrix[] = 0; // put a basic sample table - conic
         $this->storage_matrix->matrix[] = 0; // put a basic sample table - conic
         $this->storage_matrix->matrix[] = $this->maxcapacity; // put a basic sample table - conic
         $this->storage_matrix->matrix[] = 0.0; // put a basic sample table - conic
         $this->storage_matrix->matrix[] = 0.0; // put a basic sample table - conic
      } else {
         if ($text2table <> '') {
            if (is_array($text2table)) {
              $this->storage_matrix->twoDimArrayToMatrix($text2table);
              $this->matrix = $this->storage_matrix->matrix;
            } else {
              error_log("Calling matrix create with $text2table");
              $this->storage_matrix->text2table = $text2table;
              $this->matrix = explode(',', $text2table);
            }
            $this->storage_matrix->create();
         } else {
            $this->storage_matrix->matrix = $this->matrix;// map the text mo to a numerical description
            $this->storage_matrix->numrows = count($this->storage_matrix->matrix) / 3.0;
            $this->storage_matrix->formatMatrix();
         }
      }
      
      $this->addOperator('storage_stage_area', $this->storage_matrix, 0);
   }
   
   function getInputs() {
      parent::getInputs();
      if ($this->debug) {
         $this->logDebug("Initial variables on this object " . print_r($this->state,1) . "<br>");
      }
      if ($this->debug) {
         $this->logDebug("Variables from parent " . print_r($this->arData,1) . "<br>");
      }
      // now, overwrite crucial variables from parent to this objects state array
      foreach ($this->rvars as $thisvar) {
         if ($thisvar == 'release') {
            $this->setStateVar('flowby',$this->arData['release']);
            $this->setStateVar('flowby',$this->arData[$this->release]);
            if ($this->debug) {
               $this->logDebug("Setting variable 'flowby' to parent value for release: " . $this->arData[$this->$thisvar] . "<br>");
            }
         } else {
            if (isset($this->$thisvar)) {
               if ($this->debug) {
                  $this->logDebug("Setting variable " . $thisvar . " to parent value: " . $this->arData[$this->$thisvar] . "<br>");
               }
               $this->setStateVar($thisvar,$this->arData[$this->$thisvar]);
            } else {
               if ($this->debug) {
                  $this->logDebug("can not find variable " . $this->$thisvar . "<br>");
               }
            }
         }
      }
      if ($this->debug) {
         $this->logDebug("Final variables on this object " . print_r($this->state,1) . "<br>");
      }
      //error_log("Final variables on this object " . print_r($this->state,1) . "<br>");
   }
   
   function step() {
      if (!$this->riser_enabled or !is_object($this->outlet_plugin)) {
        parent::step();
        $this->value = $this->state['Qout'];
        //$this->writeToParent();
        return;
      }
      // ********************************************************************
      // all step methods MUST call preStep(),execProcessors(), postStep()
      // ********************************************************************
      $this->preStep();
      if ($this->debug) {
         $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
      }
      // execute sub-components
      $this->execProcessors();
      if ($this->debug) {
         $this->logDebug("Step Begin state[] array contents " . print_r($this->state,1) . " <br>\n");
      }

      // ********************************************************************
      // set up all quantities that plugins will use
      // ********************************************************************
      if ($this->debug) {
         $this->logDebug("Step END state[] array contents " . print_r($this->state,1) . " <br>\n");
      }
      $Uin = $this->state['Uin']; // heat in
      $U0 = $this->state['U']; // total heat in BTU/Kcal at previous timestep
      $T = $this->state['T']; // Temp at previous timestep
      $Q0 = $this->state['Qout']; // outflow during previous timestep
      $S0 = $this->state['Storage']; // storage at end of previous timestep (ac-ft)
      $Qin = $this->state['Qin'];
      $demand = $this->state['demand']; // assumed to be in MGD
      $refill = $this->state['refill']; // assumed to be in MGD
      $discharge = $this->state['discharge']; // assumed to be in MGD
      if ( isset($this->state['flowby']) and (is_numeric($this->state['flowby'])) ) {
         $flowby = $this->state['flowby']; // assumed to be in cfs
      } else {
         $flowby = 0;
      }
      // maintain backward compatibility with old ET nomenclature
      if (!($this->state['et_in'] === NULL)) {
         $pan_evap = $this->state['et_in'];
      } else {
         $pan_evap = $this->state['pan_evap']; // assumed to be in inches/day
      }
      // maintain backward compatibility with old precip nomenclature
      if (!($this->state['precip_in'] === NULL)) {
         $precip = $this->state['precip_in']; // assumed to be in inches/day
      } else {
         $precip = $this->state['precip']; // assumed to be in inches/day
      }
      
      // this checks to see if the user has subclassed the area and stage(depth) calculations
      // or if it is using the internal routines with the stage/storage/area table
      $area = $this->state['area']; // area at the beginning of the timestep - assumed to be acres
      $dt = $this->timer->dt; // timestep in seconds
      if (isset($this->processors['maxcapacity'])) {
         $max_capacity = $this->state['maxcapacity']; // we have sub-classed this with a stage-discharge relationship or other
      } else {
         $max_capacity = $this->maxcapacity;
      }
      
      // we need to include some ability to plug-and-play the evap and other routines to allow users to sub-class it
      // or the components that go into it, such as the storage/depth/surface_area relationships
      // could look at processors, and if any of the properties are sub-classed, just take them as they are
      // also need inputs such as the pan_evap
      // since the processors have already been exec'ed we could just take them, but we could also get fancier
      // and look at each step in the process to see if it has been sub-classed and insert it in the proper place.
      
      // calculate evaporation during this time step - acre-feet per second
      // need estimate of surface area.  SA will vary based on area, but we assume that area is same as last timestep area
      $evap_acfts = $area * $pan_evap / 12.0 / 86400.0;
      $precip_acfts = $area * $precip / 12.0 / 86400.0; 
      if ($this->debug) {
         $this->logDebug("Calculating P and ET: P) $precip_acfts = $area * $precip / 12.0 / 86400.0;  <br>\n ET: $evap_acfts = $area * $pan_evap / 12.0 / 86400.0;<br>\n");
      }
      // the riser routine needs this 
      $this->state['evap_acfts'] = $evap_acfts;
      $this->state['precip_acfts'] = $precip_acfts;
      $thisdate = $this->state['thisdate'];
      
      // change in storage
      if ($this->debug) {
         $this->logDebug("Calculating Volume Change: storechange = S0 + ((Qin - flowby) * dt / 43560.0)+ (1.547 * refill * dt / 43560.0) - (1.547 * demand * dt /  43560.0) - (evap_acfts * dt) + (precip_acfts * dt); <br>\n");
         $this->logDebug(" :::: $S1 = $S0 + (($Qin - $flowby) * $dt / 43560.0)+ (1.547 * $refill * $dt / 43560.0) - (1.547 * $demand * $dt /  43560.0) - ($evap_acfts * $dt) + ($precip_acfts * $dt); <br>\n");
      }
      // ********************************************************************
      // Call Plugins
      // ********************************************************************
      // plugins operate more intimately because they have access to this objects state array
      // outlet plugin sets variables riser_flow and riser_head state values
      $this->outlet_plugin->evaluate();
      $riser_flow = $this->state['riser_flow'];
      $riser_head = $this->state['riser_head'];
      // ********************************************************************
      // now calculate final state after plugins 
      // ********************************************************************
      $S1 = $S0 + (($Qin - $flowby - $riser_flow) * $dt / 43560.0) 
        + (1.547 * $discharge * $dt / 43560.0) 
        + (1.547 * $refill * $dt / 43560.0) 
        - (1.547 * $demand * $dt /  43560.0) 
        - ($evap_acfts * $dt) 
        + ($precip_acfts * $dt)
      ;
      
      if ($S1 < 0) {
         // @todo: what to do with flowby & wd?
         // if storechange is less than zero, its magnitude represents the deficit of flowby+demand
         // 3 modes:
         //   * Demand Wins - assume that demand wins, flowby gets any leftovers
         //   * Flowby Wins - we can choose to meet the flowby, demand gets leftovers
         //   * Weighted - we can choose to evenly distribute them based on weight 
         // Currently this assumes mode 1 - Demand Wins
         $deficit_acft = abs($S1);
         $s_avail = (1.547 * $demand * $dt /  43560.0) + ($flowby * $dt /  43560.0) - $deficit_acft;
         if ($s_avail <= (1.547 * $demand * $dt /  43560.0)) {
            // no water available for flowby
            $flowby = 0.0;
            $demand_met_mgd = $s_avail * 43560.0 / (1.547 * $dt);
         } else {
            // flowby is remainder
            $flowby = ($s_avail - (1.547 * $demand * $dt /  43560.0)) * 43560.0 / $dt;
            $demand_met_mgd = $demand;
         }
         $S1 = 0;
      } else {
         $demand_met_mgd = $demand;
         $deficit_acft = 0.0;
      }
      $Storage = min(array($S1, $max_capacity));
      if ($S1 > $max_capacity) {
         $spill = ($S1 - $max_capacity) * 43560.0 / $dt;
      } else {
         $spill = 0;
      }
      if ($Storage < 0.0) {
         $Storage = 0.0;
      }
      if (isset($this->processors['stage'])) {
        $stage = $this->processors['stage']->value;
      } else {
        if (isset($this->processors['storage_stage_area'])) {
          $stage = $this->processors['storage_stage_area']->evaluateMatrix($Storage,'stage');
        } else {
          $stage = 0;
        }
      }
      if (isset($this->processors['area'])) {
         $area = $this->state['area']; // area at the beginning of the timestep - assumed to be acres
      } else {
         // calculate area - in an ideal world, this would be solved simultaneously with the storage
         if (isset($this->processors['storage_stage_area'])) {
           $area = $this->processors['storage_stage_area']->evaluateMatrix($Storage,'surface_area');
         } else {
           $area = 0;
         }
      }
      // sub-classing is disabled here
      //if (isset($this->processors['Qout'])) {
      //   $Qout = $this->state['Qout']; // we have subclassed this witha stage-discharge relationship or oher
      //} else {
         $Qout = $spill + $flowby + $riser_flow;
      //}
      if ($this->debug) {
        error_log("RISER($this->state[runid] : S0 = $S0, S1 = $S1, Storage = $Storage ");
        error_log("RISER($this->state[runid] : Qout = spill + flowby + riser_flow;");
        error_log("RISER($this->state[runid] : $Qout = $Qin - $spill + $flowby + $riser_flow;");
      }
      
      // local unit conversion dealios
      $this->state['evap_mgd'] = $evap_acfts * 28157.7;
      $this->state['pct_use_remain'] = ($Storage - $this->state['unusable_storage']) / ($this->state['maxcapacity'] - $this->state['unusable_storage']);
      $this->state['use_remain_mg'] = ($Storage - $this->state['unusable_storage']) / 3.07;
      if ($this->state['use_remain_mg'] < 0) {
         $this->state['use_remain_mg'] = 0;
         $this->state['pct_use_remain'] = 0;
      }
      // days remaining
      if ( ($demand > 0) and ($dt > 0)) {
         $days_remaining = $this->state['use_remain_mg'] / ($demand);
      } else {
         $days_remaining = 0;
      }

      $this->state['days_remaining'] = $days_remaining;
      $this->state['deficit_acft'] = $deficit_acft;
      $this->state['demand_met_mgd'] = $demand_met_mgd;
      $this->state['depth'] = $depth;
      $this->state['Storage'] = $Storage;
      $this->state['Vout'] = $Vout;
      $this->state['Qout'] = $Qout;
      $this->state['depth'] = $stage;
      $this->state['Storage'] = $Storage;
      $this->state['spill'] = $spill;
      $this->state['area'] = $area;
      $this->state['release'] = $flowby;
      $this->state['evap_acfts'] = $evap_acfts;
      $this->state['storage_mg'] = $Storage / 3.07;
      $this->state['lake_elev'] = $stage;
      $this->state['refill_full_mgd'] = (($max_capacity - $Storage) / 3.07) * (86400.0 / $dt);
      
      // now calculate heat flux
      // O1 is outflow at last time step, 
      if ( ( $Qout * $dt + $Storage) > 0) {
         $U = ($Storage * ($U0 + $Uin)) / ( $Qout * $dt + $Storage);
      } else {
         $U = 0.0;
      }
      switch ($this->units) {
         case 1:
         // SI
         $T = $U / $Storage; // this is NOT right, don't know what units for storage would be in SI, since this is not really implemented
         break;
         
         case 2:
         // EE
         $T = 32.0 + ($U / ($Storage * 7.4805)) * (1.0 / 8.34); // Storage - cubic feet, 7.4805 gal/ft^3
         break;
      }
      // let's also assume that the water isn't frozen, so we limit this to zero
      if ($T < 0) {
         $T = 0;
      }
      $Uout = $U0 + $Uin - $U;
      $this->state['U'] = $U;
      $this->state['Uout'] = $Uout;
      $this->state['T'] = $T;
         
      $this->postStep();
      $this->totalflow += $Qout * $dt;
      $this->totalinflow += (1.547 * $refill + $Qin) * $dt;
      $this->totalwithdrawn += $demand * $dt;
      
      $this->value = $this->state['Qout'];
      //error_log("Iterations at main imp object = " . $this->state['its']);
      //$this->writeToParent();
   }
   
   function showFormHeader($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . " | ";
      $innerHTML .= "<b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'] . " | ";
      $innerHTML .= "<b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'];
      $innerHTML .= "<br><b>Init Storage:</b> " . $formatted->formpieces['fields']['initstorage'];
      $innerHTML .= " | <b>Max Storage:</b> " . $formatted->formpieces['fields']['maxcapacity'];
      $innerHTML .= " | <b>Dead Storage:</b> " . $formatted->formpieces['fields']['unusable_storage'];
      return $innerHTML;
   }
   
   function showFormBody($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<table><tr>";
      $innerHTML .= "<td valign=top>";
      $innerHTML .= "<b>Impoundment Geometry:</b> <br>";
      //$this->storage_matrix->debug = 1;
      $innerHTML .= $this->storage_matrix->showFormBody($formatted,$formname, $disabled);
      $innerHTML .= "</td>";
      $innerHTML .= "<td valign=top>";
      $innerHTML .= "<b>Inflow (cfs):</b> " . $formatted->formpieces['fields']['Qin'];
      $innerHTML .= "<br><b>Refill (MGD):</b> " . $formatted->formpieces['fields']['refill'];
      $innerHTML .= "<br><b>Demand (MGD):</b> " . $formatted->formpieces['fields']['demand'];
      $innerHTML .= "<br><b>ET (in/day):</b> " . $formatted->formpieces['fields']['et_in'];
      $innerHTML .= "<br><b>Precip (in/day):</b> " . $formatted->formpieces['fields']['precip_in'];
      $innerHTML .= "<br><b>Release (cfs):</b> " . $formatted->formpieces['fields']['release'];
      $innerHTML .= "<br><b>Use Riser Structure?:</b> " . $formatted->formpieces['fields']['riser_enabled'];
      $innerHTML .= "<br><b>Riser Shape:</b> " . $this->riser_shape; // @todo: make selectable
      $innerHTML .= "<br><b>Riser Diameter (ft):</b> " . $formatted->formpieces['fields']['riser_diameter'];
      $innerHTML .= "<br><b>Riser Opening Storage (ac-ft):</b> " . $formatted->formpieces['fields']['riser_opening_storage'];
      $innerHTML .= "<br><b>Pipe Flow Head (ft above opening):</b> " . $formatted->formpieces['fields']['riser_pipe_flow_head'] . " (below this head flow is modeled as weir)";
      $innerHTML .= "<br><b>Riser Length (ft):</b> " . $formatted->formpieces['fields']['riser_length'];
      $innerHTML .= "<br><b>Log Failed Solutions?:</b> " . $formatted->formpieces['fields']['log_solution_problems'];
      $innerHTML .= "</td>";
      return $innerHTML;
   }
   
   function showFormFooter($formatted,$formname, $disabled = 0) {
      $innerHTML = '';
      /*
      $innerHTML .= $formatted->formpieces['fields']['enable_cfb'];
      $innerHTML .= " Set flowby to " . $formatted->formpieces['fields']['cfb_var'];
      $innerHTML .= " if " . $formatted->formpieces['fields']['cfb_condition'];
      $innerHTML .= " calculated flow-by <br>";
      */
      return $innerHTML;
   }
   
  function setProp($propname, $propvalue, $view = '') {
    //$json_object = json_decode($json);
    //if (is_object($thisobject) and $json_object
    // subprop_name can be name:subname 
    // if so, this is a special sub-prop like hydroImpSmall matrix
    list($subprop_name, $subsub_name) = explode(':', $propname);
    if ( ($subprop_name == 'storage_stage_area') and ($subsub_name == 'matrix') ) {
      // handle calls to set the stage-storage attributes 
      // decode from json if applicable
      //$this->matrix = array('storage','stage','surface_area',0,0,0);
      switch ($view) {
        case 'json-1d':
        default:
          $text2table = implode(",",json_decode($propvalue));
        break;
      }
      error_log("$this->name Calling setupMatrix($text2table)");
      $this->setupMatrix($text2table);
    }
    parent::setProp($propname, $propvalue, $view);
  }
  
  
  function setClassProp($propname, $propvalue, $view = '') { 
  // arrays have already been parsed by json2d handler that calls this 
  // so we rearrange as 1-d, implode, and call setupMatrix
    switch ($view) {
      case 'array':
        switch ($propname) {
          case 'matrix':
            $this->setupMatrix($propvalue);
          break;
          default:
            parent::setClassProp($propname, $propvalue, $view);
          break;
        }
      break;
      
      default:
        parent::setClassProp($propname, $propvalue, $view);
      break;
    }
  }
   
}


class efdata_alife extends blankShell {
   // time series for withdrawal data, based on objects geometry
   function wake() {
      parent::wake();
   }
   
   function init() {
      parent::init();
   }   
}
class efdata_flow extends blankShell {
   // time series for withdrawal data, based on objects geometry
   function wake() {
      parent::wake();
   }
   
   function init() {
      parent::init();
   }   
}
class efdata_hydro extends blankShell {
   // time series for withdrawal data, based on objects geometry
   function wake() {
      parent::wake();
   }
   
   function init() {
      parent::init();
   }   
}
class efdata_wqual extends blankShell {
   // time series for withdrawal data, based on objects geometry
   function wake() {
      parent::wake();
   }
   
   function init() {
      parent::init();
   }   
}

// ***************************************************************************
// *********            Water Withdrawal Rule Components           ***********
// ***************************************************************************

class withdrawalRuleObject extends blankShell {
   var $max_annual_mg = 0;
   var $max_daily_mgd = 0;
   var $max_instant_mgd = 0;
   var $flowby;
   var $flowby_type = 1; // 1 - simple rate flowby, 2 - simple percentage flowby, 3 - simple tiered rate, 4 - simple tiered percent, 5 - monthly tiered rate, 6 - monthly tiered percent
   var $Qstream = 0; // this should be overridden by an input
   var $demand_mgd = 0;
   var $allowed_wd_mgd = 0;
   
   function wake() {
      parent::wake();
      $this->prop_desc['max_annual_mg'] = 'Maximum Total Annual Withdrawal Volume (Millions of Gallons). This is disabled if set to 0.0';
      $this->prop_desc['max_daily_mgd'] = 'Maximum Daily Withdrawal Rate (MGD).  This is disabled if set to 0.0';
      $this->prop_desc['max_instant_mgd'] = 'Maximum Instantaneous Withdrawal Rate (MGD). This is disabled if set to 0.0';
      $this->prop_desc['flowby'] = 'Rate of flow remaining in source stream after withdrawal (cfs).';
      $this->prop_desc['Qstream'] = 'Current rate of flow in source stream.  This variable should be populated by a link to the indicator stream object (cfs).';
      $this->prop_desc['demand_mgd'] = 'Current rate of desired withdrawal.  This variable may be over-written by a link to another use object (MGD).';
      $this->prop_desc['allowed_wd_mgd'] = 'Current rate of allowed withdrawal after rule evaluation. (MGD).';
   }
      

   function setState() {
      parent::setState();
      $this->state['max_annual_mg'] = $this->max_annual_mg;
      $this->state['max_daily_mgd'] = $this->max_daily_mgd;
      $this->state['max_instant_mgd'] = $this->max_instant_mgd;
      $this->state['flowby'] = $this->flowby;
      $this->state['Qstream'] = $this->flowby;
      $this->state['demand_mgd'] = $this->demand_mgd;
      $this->state['allowed_wd_mgd'] = $this->allowed_wd_mgd;
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      
      $statenums = array('max_annual_mg', 'max_daily_mgd','max_instant_mgd', 'flowby', 'Qstream','demand_mgd','allowed_wd_mgd');
      foreach ($statenums as $thiscol) {
         $this->setSingleDataColumnType($thiscol, 'float8',0.0);
         $this->logformats[$thiscol] = '%s';
      }
   }
   
   
   function create() {
      parent::create();
      // set up stock items, based on the flowby_type
      if ($this->debug) {
         $this->logDebug("Create() function called <br>");
      }
      $this->addFlowByVars();
      $this->logDebug("Processors on this object: " . print_r(array_keys($this->processors),1) . " <br>");
   }
   
   function step() {
      // all step methods MUST call preStep(),execProcessors(), postStep()
      $this->preStep();
      if ($this->debug) {
         $this->logDebug("$this->name Inputs obtained. thisdate = " . $this->state['thisdate']);
      }
      // execute sub-components
      $this->execProcessors();
      
      if ( isset($this->processors['demand_mgd']) or isset($this->inputs['demand_mgd']) ) {
         // let the custom processor handle this
         $demand_mgd = $this->state['demand_mgd']; 
      } else {
         $demand_mgd = $this->max_daily_mgd;
      }
      // verify that the withdrawal does not violate the annual, instant and daily limits
      // for now we just compare to instantaneous limit, but we should keep a running 
      // tally of the total per day, or per year and restrict if those are exceeded
      if ( ($demand_mgd > $this->max_instant_mgd) ) {
         $demand_mgd = $this->max_instant_mgd;
      }
      
      if (isset($this->processors['allowed_wd_mgd'])) {
         // let the custom processor handle this
         $aw = $this->state['allowed_wd_mgd']; 
      } else {
         // otherwise, do it here
         $excess_mgd = ($this->state['Qstream'] - $this->state['flowby']) / 1.547;
         if ($excess_mgd > 0) {
            $aw = min( $excess_mgd, $demand_mgd);
         } else {
            $aw = 0.0;
         }
      }

      $state['demand_mgd'] = $demand_mgd;
      $state['allowed_wd_mgd'] = $aw;
      $this->postStep();
   }
   
   
   function addFlowByVars() {
      // 1 - simple rate flowby, 2 - simple percentage flowby, 3 - simple tiered rate, 4 - simple tiered percent, 5 - monthly tiered rate, 6 - monthly tiered percent
      switch ($this->flowby_type) {
         case 1:
         // just a single equation
         $this->addSimpleFlowBy();
         break;
         
         case 2:
         $this->addSimplePercentFlowBy();
         break;
      }
      
      // add conservation triggers
      //$this->addConservation();
   }
   
   function addSimpleFlowBy() {
      if (isset($this->processors['flowby'])) {
         unset($this->processors['flowby']);
      }
      // simple rate based flowby
      $flowby = new Equation;
      $flowby->name = 'flowby';
      $flowby->wake();
      $flowby->equation = 0.0;
      $flowby->defaultval = 0.0;
      $flowby->nanvalue = 0.0;
      $flowby->strictnull = 0;
      $flowby->nonnegative = 1;
      $flowby->minvalue = 0.0;
   
      $this->logDebug("Trying to add simple flowby <br>");
      $this->addOperator('flowby', $flowby, 0);
   }
   
   function addSimplePercentFlowBy() {            
      // simple percent based flowby
      $diversion_pct = new Equation;
      $diversion_pct->name = 'diversion_pct';
      $diversion_pct->decription = 'Allowable percentage diversion, used to caluclate flow by';
      $diversion_pct->wake();
      $diversion_pct->equation = '0.1';
      $diversion_pct->defaultval = 0.0;
      $diversion_pct->nanvalue = 0.0;
      $diversion_pct->strictnull = 0;
      $diversion_pct->nonnegative = 1;
      $diversion_pct->minvalue = 0.0;
   
      $this->logDebug("Trying to add simple diversion_pct <br>");
      $this->addOperator('diversion_pct', $diversion_pct, 0);
      
      $flowby = new Equation;
      $flowby->name = 'flowby';
      $flowby->wake();
      $flowby->equation = 'Qstream * (1.0 - diversion_pct)';
      $flowby->defaultval = 0.0;
      $flowby->nanvalue = 0.0;
      $flowby->strictnull = 0;
      $flowby->nonnegative = 1;
      $flowby->minvalue = 0.0;
   
      $this->logDebug("Trying to add simple flowby <br>");
      $this->addOperator('flowby', $flowby, 0);
   }
   
   function addConservation() {            
      // landuse subcomponent to allow users to simulate land use values
      $base_rows = array(
         0=>array('Surface Name'=>'Roof','area(sqft)'=>0,'Imp Frac'=>1.0, 'Capture Frac'=>0.9),
         1=>array('Surface Name'=>'Driveway','area(sqft)'=>0,'Imp Frac'=>1.0, 'Capture Frac'=>0.5),
         2=>array('Surface Name'=>'Patio','area(sqft)'=>0,'Imp Frac'=>1.0, 'Capture Frac'=>0.5)
      );
      $surface_cols = array_keys($base_rows[0]);
      $surface = new dataMatrix;
      $surface->name = 'usetypes';
      $surface->wake();
      $surface->numcols = count($surface_cols);  
      $surface->valuetype = 2; // 2 column lookup (col & row)
      $surface->keycol1 = ''; // key for 1st lookup variable
      $surface->lutype1 = 0; // lookp type - exact match for land use name
      $surface->keycol2 = ''; // key for 2nd lookup variable
      $surface->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line and default entries
      $surface->numrows = count($base_rows) + 1;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      foreach ($surface_cols as $thisvar) {
         $surface->matrix[] = $thisvar;
      }
      // now add blanks the basic types individual records
      foreach ($base_rows as $thisrow) {
         foreach ($surface_cols as $thisvar) {
            $surface->matrix[] = $thisrow[$thisvar];
         }
      }
      $this->logDebug("Trying to add surface matrix with values: " . print_r($surface->matrix,1) . " <br>");
      $this->addOperator('surfaces', $surface, 0);
   }
   
   function addTieredFlowBy() {            
      // landuse subcomponent to allow users to simulate land use values
      $base_rows = array(
         0=>array('Surface Name'=>'Roof','area(sqft)'=>0,'Imp Frac'=>1.0, 'Capture Frac'=>0.9),
         1=>array('Surface Name'=>'Driveway','area(sqft)'=>0,'Imp Frac'=>1.0, 'Capture Frac'=>0.5),
         2=>array('Surface Name'=>'Patio','area(sqft)'=>0,'Imp Frac'=>1.0, 'Capture Frac'=>0.5)
      );
      $surface_cols = array_keys($base_rows[0]);
      $surface = new dataMatrix;
      $surface->name = 'usetypes';
      $surface->wake();
      $surface->numcols = count($surface_cols);  
      $surface->valuetype = 2; // 2 column lookup (col & row)
      $surface->keycol1 = ''; // key for 1st lookup variable
      $surface->lutype1 = 0; // lookp type - exact match for land use name
      $surface->keycol2 = ''; // key for 2nd lookup variable
      $surface->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line and default entries
      $surface->numrows = count($base_rows) + 1;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      foreach ($surface_cols as $thisvar) {
         $surface->matrix[] = $thisvar;
      }
      // now add blanks the basic types individual records
      foreach ($base_rows as $thisrow) {
         foreach ($surface_cols as $thisvar) {
            $surface->matrix[] = $thisrow[$thisvar];
         }
      }
      $this->logDebug("Trying to add surface matrix with values: " . print_r($surface->matrix,1) . " <br>");
      $this->addOperator('surfaces', $surface, 0);
   }
}


// ***************************************************************************
// *********              Rainfall harvesting Components           ***********
// ***************************************************************************

class genericLandSurface extends blankShell {
   var $surface_cols;
   
   function wake() {
      parent::wake();
      $this->prop_desc['rainfall_in'] = 'Rainfall rate of input to this surface object (inches/day).';
      $this->prop_desc['imp_runoff'] = 'Runoff from impervious portion of this area (cfs).';
      $this->prop_desc['imp_runoff_gpm'] = 'Runoff from impervious portion of this area (Gal/min).';
      $this->prop_desc['imp_capture'] = 'Flows from impervious portion of this area that are captured by cistern (cfs)';
      $this->prop_desc['imp_capture_gpm'] = 'Flows from impervious portion of this area that are captured by cistern (Gal/min)';
      $this->prop_desc['area'] = 'Channel mainstem length (ft).';
   }
      

   function setState() {
      parent::setState();
      $this->state['imp_runoff'] = 0.0;
      $this->state['imp_capture'] = 0.0;
      $this->state['imp_runoff_gpm'] = 0.0;
      $this->state['imp_capture_gpm'] = 0.0;
      $this->state['area'] = 0.0;
      $this->state['rainfall_in'] = 0.0;
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      
      $statenums = array('imp_runoff_gpm', 'imp_capture_gpm','imp_runoff', 'imp_capture','area', 'rainfall_in');
      foreach ($statenums as $thiscol) {
         $this->setSingleDataColumnType($thiscol, 'float8',0.0);
         $this->logformats[$thiscol] = '%s';
      }
   }
   
   
   function create() {
      parent::create();
      // set default land use
      $this->logDebug("Create() function called <br>");
      // add use types
      $this->addSurfaceComponent();
      $this->logDebug("Processors on this object: " . print_r(array_keys($this->processors),1) . " <br>");
   }
   
   function step() {
      parent::step();
      $this->calcFlows();
   }
   
   function calcFlows() {
      $this->logDebug("calcFlows() function called <br>");
      // add use types
      // sum up all surface areas, and percent impervious
      // search inputs/statevars for precip input
      // calculate total volume inflow from impervious to capture, non-capture routes
      // set values on parent for those quantities:
      //  - imp_detained (cubic feet)
      //  - imp_runoff (cubic feet)
      $dt = $this->timer->dt;
      $rainfall = $this->state['rainfall_in'];
      $imp_capture = 0.0;
      $imp_runoff = 0.0;
      $area = 0.0;
      $surface_names = array_keys($this->processors['surfaces']->matrix_formatted);
      foreach ($surface_names as $thisname) {
         
         $area += $this->processors['surfaces']->evaluateMatrix($thisname, 'area(sqft)');
         $imp_frac = $this->processors['surfaces']->evaluateMatrix($thisname, 'Imp Frac');
         $cap_frac = $this->processors['surfaces']->evaluateMatrix($thisname, 'Capture Frac');
         // $rainfall/12 = feet per day, ($dt/86400) = % of a day = cubic-feet per time step
         if ($this->debug) {
            $this->logDebug("Surface $surface_name, imp_capture += area * imp_frac * cap_frac * rainfall / ( 86400.0 * 12.0); <br>\n");
            $this->logDebug(" $imp_capture += $area * $imp_frac * $cap_frac * $rainfall / ( 86400.0 * 12.0); <br>\n");
         }
         $imp_capture += $area * $imp_frac * $cap_frac * $rainfall / ( 86400.0 * 12.0);
         if ($this->debug) {
            $this->logDebug(" imp_capture = $imp_capture <br>\n");
         }
         // add in the part of the 
         $imp_runoff += $area * $imp_frac * (1.0 - $cap_frac) * $rainfall / ( 86400.0 * 12.0);
      }
      $this->state['imp_runoff'] = $imp_runoff;
      $this->state['imp_runoff_gpm'] = $imp_runoff * 448.8; //448.8 is conversion from cfs to gpm
      $this->state['imp_capture'] = $imp_capture;
      $this->state['imp_capture_gpm'] = $imp_capture * 448.8; //448.8 is conversion from cfs to gpm
      $this->state['area'] = $area;
      $this->logDebug("Flows calculated: Imp RO: $imp_runoff, Imp Capture: $imp_capture <br>");
   }
   
   function addSurfaceComponent() {
      if (isset($this->processors['surfaces'])) {
         unset($this->processors['surfaces']);
      }
      // landuse subcomponent to allow users to simulate land use values
      $base_rows = array(
         0=>array('Surface Name'=>'Roof','area(sqft)'=>0,'Imp Frac'=>1.0, 'Capture Frac'=>0.9),
         1=>array('Surface Name'=>'Driveway','area(sqft)'=>0,'Imp Frac'=>1.0, 'Capture Frac'=>0.5),
         2=>array('Surface Name'=>'Patio','area(sqft)'=>0,'Imp Frac'=>1.0, 'Capture Frac'=>0.5)
      );
      $surface_cols = array_keys($base_rows[0]);
      $surface = new dataMatrix;
      $surface->name = 'usetypes';
      $surface->wake();
      $surface->numcols = count($surface_cols);  
      $surface->valuetype = 2; // 2 column lookup (col & row)
      $surface->keycol1 = ''; // key for 1st lookup variable
      $surface->lutype1 = 0; // lookp type - exact match for land use name
      $surface->keycol2 = ''; // key for 2nd lookup variable
      $surface->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line and default entries
      $surface->numrows = count($base_rows) + 1;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      foreach ($surface_cols as $thisvar) {
         $surface->matrix[] = $thisvar;
      }
      // now add blanks the basic types individual records
      foreach ($base_rows as $thisrow) {
         foreach ($surface_cols as $thisvar) {
            $surface->matrix[] = $thisrow[$thisvar];
         }
      }
      $this->logDebug("Trying to add surface matrix with values: " . print_r($surface->matrix,1) . " <br>");
      $this->addOperator('surfaces', $surface, 0);
   }
}

class genericDwelling extends blankShell {
   var $occupants = 1.0;
   var $matrix;
   var $serialist = 'matrix'; # tells routines to serialize this before storing in XML
   
   function wake() {
      parent::wake();
      $this->prop_desc['Use PPPD'] = '# of Uses Per Person Per Day.';
      $this->prop_desc['use_gpd'] = 'Calculated total use rate in Gallons/day.';
      $this->prop_desc['use_gpm'] = 'Calculated total use rate in Gallons/minute.';
      $this->addUseTypesComponent();
   }
      

   function setState() {
      parent::setState();
      $this->state['occupants'] = $this->occupants;
      $this->state['use_gpd'] = 0.0;
      $this->state['use_gpm'] = 0.0;
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      
      $statenums = array('use_gpm', 'use_gpd', 'occupants');
      foreach ($statenums as $thiscol) {
         $this->setSingleDataColumnType($thiscol, 'float8', 0.0);
         $this->logformats[$thiscol] = '%s';
      }
   }
   
   
   function create() {
      parent::create();
      // set default land use
      $this->logDebug("Create() function called <br>");
      // add use types
      $this->matrix = array();
      $this->addUseTypesComponent();
      $this->logDebug("Processors on this object: " . print_r(array_keys($this->processors),1) . " <br>");
   }
   
   function step() {
      parent::step();
      $this->calcFlows();
   }
   
   function calcFlows() {
      $this->logDebug("calcFlows() function called <br>");
      // add use types
      // sum up all surface areas, and percent impervious
      // search inputs/statevars for precip input
      // calculate total volume inflow from impervious to capture, non-capture routes
      // set values on parent for those quantities:
      //  - imp_detained (cubic feet)
      //  - imp_runoff (cubic feet)
      $dt = $this->timer->dt;
      $occupants = $this->state['occupants'];
      
      $use_names = array_keys($this->processors['usetypes']->matrix_formatted);
      $use_gpd = 0.0;
      foreach ($use_names as $thisname) {
         $rate += $this->processors['usetypes']->evaluateMatrix($thisname, 'rate');
         $use_pppd = $this->processors['usetypes']->evaluateMatrix($thisname, 'Use PPPD');
         $pct_cold = $this->processors['usetypes']->evaluateMatrix($thisname, '% cold');
         // use either time rate or use rate, not both - time rate trumps use rate
         $this_use = $rate * $use_pppd * $dt / 86400.0;
         if ($this->debug) {
            $this->logDebug("Use Type $thisname, $this_use Gal/Day <br>");
         }
         $use_gpd += $this_use;
      }
      $this->state['use_gpd'] = $use_gpd;
      $this->state['use_gpm'] = $use_gpd * 60.0 / 86400.0;
      if ($this->debug) {
         $this->logDebug("Uses calculated: $use_gpd Gal/Day<br>");
      }
   }
   
   function addUseTypesComponent() {
      if (isset($this->processors['usetypes'])) {
         unset($this->processors['usetypes']);
      }
      $usetypes = new dataMatrix;
      $usetypes->firstrow_ro = 1;
      $usetypes->name = 'usetypes';
      $usetypes->description = "Types of water uses:  The 'rate' column and the 'Use PPPD' column should be set such that rate*Use_PPPD = gallons/day.  The 'rate units', and 'use units' columns are for descriptive purposes only.";
      $usetypes->wake();
      $usetypes->numcols = 6;  
      $usetypes->valuetype = 2; // 2 column lookup (col & row)
      $usetypes->keycol1 = ''; // key for 1st lookup variable
      $usetypes->lutype1 = 0; // lookp type - exact match for land use name
      $usetypes->keycol2 = ''; // key for 2nd lookup variable
      $usetypes->lutype2 = 0; // lookup type - interpolated for year value
      if ( !is_array($this->matrix) or (count($this->matrix) == 0)) {
         // landuse subcomponent to allow users to simulate land use values
         $base_rows = array(
            0=>array('Fixture'=>'Shower','Rate'=>2.5,'Rate Units'=>'gpm', 'Use PPPD'=>5.0, 'PPPD Units'=>'min/day/person','% Cold'=>0.40),
            1=>array('Fixture'=>'toilet','Rate'=>1.6,'Rate Units'=>'Gal/use', 'Use PPPD'=>4.0, 'PPPD Units'=>'uses/day/person','% Cold'=>1.0),
            2=>array('Fixture'=>'dishwasher','Rate'=>15.0,'Rate Units'=>'Gal/use', 'Use PPPD'=>0.42, 'PPPD Units'=>'uses/day/person','% Cold'=>0.40),
            3=>array('Fixture'=>'clothes washer','Rate'=>42.0,'Rate Units'=>'Gal/use', 'Use PPPD'=>0.42, 'PPPD Units'=>'uses/day/person','% Cold'=>0.40)
         );
         $usetypes->numrows = count($base_rows) + 1;
         $usetypes_cols = array_keys($base_rows[0]);
         // add a row for the header line and default entries
         // since these are stored as a single dimensioned array, regardless of their lookup type 
         // (for compatibility with single dimensional HTML form variables)
         // we set alternating values representing the 2 columns (luname - acreage)
         foreach ($usetypes_cols as $thisvar) {
            $usetypes->matrix[] = $thisvar;
         }
         // now add blanks the basic types individual records
         foreach ($base_rows as $thisrow) {
            foreach ($usetypes_cols as $thisvar) {
               $usetypes->matrix[] = $thisrow[$thisvar];
            }
         }
      } else {
         $usetypes->matrix = $this->matrix;
         $usetypes->numrows = count($this->matrix) / 6; 
      }
      $this->matrix = $usetypes->matrix;
      $this->logDebug("Trying to add usetypes matrix with values: " . print_r($usetypes->matrix,1) . " <br>");
      //error_log("Trying to add usetypes matrix with values: " . print_r($usetypes->matrix,1) . " <br>");
      $this->addOperator('usetypes', $usetypes, 0);
   }
}


class hydroTank extends hydroObject {
   # a generic object, needs a depth/storage/area table
   // this object uses small container friendly numbers, such as gallons, and GPM
   var $maxcapacity = 1000.0;
   var $initstorage = 1000.0;
   var $precip = 0;
   var $pan_evap = 0;
   var $demand = 0;
   var $matrix = array(); // array shell for storage table
   var $serialist = 'rvars,wvars,matrix';
   var $open_air = 0; // 0 - closed tank, 1 - modeled as open (will include evap)
   
   function wake() {
      parent::wake();
      $this->prop_desc['Qin'] = 'Inflows to this tank (Gal/min).';
      $this->prop_desc['Qout'] = 'Total outflows from this tank - spillage (Gal/min).';
      $this->prop_desc['initstorage'] = 'Initial storage (Gallons).';
      $this->prop_desc['maxcapacity'] = 'Maximum storage (Gallons).';
      $this->prop_desc['demand'] = 'Rate of Water Withdrawal (Gal/min).';
      $this->prop_desc['pan_evap'] = 'Evaporation Rate off the surface area (inches/day) - evap_gpm will be calculated from this.';
      $this->prop_desc['evap_gpm'] = 'Evaporation Rate (Gal/min).';
   }

   function init() {
      parent::init();
      $this->Storage = $this->initstorage;
      $this->state['Storage'] = $this->initstorage;
      $this->state['maxcapacity'] = $this->maxcapacity;
      $this->state['initstorage'] = $this->initstorage;
      $this->state['Qin'] = $this->Qin;
      $this->state['Qout'] = $this->Qout;
      $this->processors['storage_stage_area']->valuetype = 2; // 2 column lookup (col & row)
   }

   // *************************************************
   // BEGIN - Special Parent Variable Setting Interface
   // *************************************************
   function setState() {
      parent::setState();
      $this->state['maxcapacity'] = $this->maxcapacity;
      $this->state['initstorage'] = $this->initstorage;
      $this->state['Qin'] = $this->Qin;
      $this->state['Qout'] = $this->Qout;
      $this->state['pan_evap'] = 0.1;
      $this->state['precip'] = 0.0;
      $this->state['demand'] = 0.0;
      $this->state['evap_gpm'] = 0.0;
      $this->rvars = array('pan_evap','precip','demand', 'Qin');
      $this->wvars = array('Qin', 'evap_gpm','Qout','depth','Storage', 'percent_use_remain', 'demand', 'spill');
      
      $this->initOnParent();
   }

   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      foreach ($this->varsToSetOnParent() as $thisvar) {
         if ($this->debug) {
            $this->logDebug("Setting $this->name" . "_" . "$thisvar to type float8 on parent.<br>\n");
            error_log("Setting $this->name" . "_" . "$thisvar to type float8 on parent.<br>\n");
         }
         if (is_object($this->parentobject)) {
            if (method_exists($this->parentobject, 'setSingleDataColumnType')) {
               $this->parentobject->setSingleDataColumnType($thisvar, 'float8', 0.0);
            }
         }
         if (!in_array($thisvar, $this->vars)) {
            $this->vars[] = $thisvar;
         }
      }
   }
   
   // *************************************************
   // END - Special Parent Variable Setting Interface
   // *************************************************

   function create() {
      parent::create();
      // set up a table for impoundment geometry
      $this->logDebug("Create() function called <br>");
      $this->setUpTank(1);
   }
   
   function setUpTank($reinitialize=0) {
      if ($reinitialize) {
         if (isset($this->processors['storage_stage_area'])) {
            unset($this->processors['storage_stage_area']);
         }
      }
      
      // matrix subcomponent to allow users to simulate stage/storage/area tables
      $storage_stage_area = new dataMatrix;
      $storage_stage_area->listobject = $this->listobject;
      $storage_stage_area->name = 'storage_stage_area';
      $storage_stage_area->wake();
      $storage_stage_area->numcols = 3;  
      $storage_stage_area->valuetype = 2; // 2 column lookup (col & row)
      $storage_stage_area->keycol1 = ''; // key for 1st lookup variable
      $storage_stage_area->lutype1 = 1; // lookup type - interpolate for storage
      $storage_stage_area->keycol2 = 'year'; // key for 2nd lookup variable
      $storage_stage_area->lutype2 = 1; // lookup type - interpolated for other values
      if ( !is_array($this->matrix) or (count($this->matrix) == 0)) {
         // initialize
         // add a row for the header line
         $storage_stage_area->numrows = 3;
         // since these are stored as a single dimensioned array, regardless of their lookup type 
         // (for compatibility with single dimensional HTML form variables)
         // we set alternating values representing the 2 columns (luname - acreage)
         $storage_stage_area->matrix[] = 'storage';
         $storage_stage_area->matrix[] = 'stage';
         $storage_stage_area->matrix[] = 'surface_area';
         $storage_stage_area->matrix[] = 0; // put a basic sample table - conic
         $storage_stage_area->matrix[] = 0; // put a basic sample table - conic
         $storage_stage_area->matrix[] = 0; // put a basic sample table - conic
         $storage_stage_area->matrix[] = $this->maxcapacity; // put a basic sample table - conic
         $storage_stage_area->matrix[] = 0.0; // put a basic sample table - conic
         $storage_stage_area->matrix[] = 0.0; // put a basic sample table - conic
      } else {
         $storage_stage_area->matrix = $this->matrix;
      }
      
      if ($this->debug) {
         $this->logDebug("Trying to add stage-surfacearea-storage sub-component matrix with values: " . print_r($storage_stage_area->matrix,1) . " <br>");
      }
      $this->addOperator('storage_stage_area', $storage_stage_area, 0);
      if ($this->debug) {
         $this->logDebug("Processors on this object: " . print_r(array_keys($this->processors),1) . " <br>");
      }
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
         $this->logDebug("Step Begin state[] array contents " . print_r($this->state,1) . " <br>\n");
      }

      $Q0 = $this->state['Qout']; // outflow during previous timestep
      $S0 = $this->state['Storage']; // storage at end of previous timestep (Gallons)
      $Qin = $this->state['Qin']; // inflow GPM
      $demand = $this->state['demand']; // assumed to be in GPM
      $pan_evap = $this->state['pan_evap']; // assumed to be in inches/day
      $precip = $this->state['precip']; // assumed to be in inches/day
      // this checks to see if the user has subclassed the area and stage(depth) calculations
      // or if it is using the internal routines with the stage/storage/area table
      if (isset($this->processors['area'])) {
         $area = $this->state['area']; // surface area at the beginning of the timestep - assumed to be square feet
      } else {

         // calculate area - in an ideal world, this would be solved simultaneously with the storage
         if (isset($this->processors['storage_stage_area'])) {
            // must have the stage/storage/sarea dataMatrix for this to work
            $stage = $this->processors['storage_stage_area']->evaluateMatrix($S0,'stage');
            $area = $this->processors['storage_stage_area']->evaluateMatrix($S0,'surface_area');
         } else {
            $stage = 0;
            $area = 0;
         }
      }
      $dt = $this->timer->dt; // timestep in seconds
      if (isset($this->processors['maxcapacity'])) {
         $max_capacity = $this->state['maxcapacity'];
      } else {
         if (isset($this->inputs['maxcapacity'])) {
            $max_capacity = $this->state['maxcapacity'];
         } else {
            $max_capacity = $this->maxcapacity;
         }
      }
      
      // we need to include some ability to plug-and-play the evap and other routines to allow users to sub-class it
      // or the components that go into it, such as the storage/depth/surface_area relationships
      // could look at processors, and if any of the properties are sub-classed, just take them as they are
      // also need inputs such as the pan_evap
      // since the processors have already been exec'ed we could just take them, but we could also get fancier
      // and look at each step in the process to see if it has been sub-classed and insert it in the proper place.
      
      // calculate evaporation during this time step - acre-feet per second
      // need estimate of surface area.  SA will vary based on area, but we assume that area is same as last timestep area
      //error_log(" $area * $pan_evap / 12.0 / 86400.0 <br>");
      if ($this->open_air) {
         $evap_gpm = $area * $pan_evap * 60.0 / 12.0 / 86400.0;
         $precip_gpm = $area * $precip * 60.0 / 12.0 / 86400.0; 
      } else {
         $evap_gpm = 0.0;
         $precip_gpm = 0.0;
      }
      
      // change in storage
      $storechange = $S0 + ($Qin * $dt / 60.0) - ($demand * $dt / 60.0) - ($evap_gpm * $dt / 60.0) + ($precip_gpm * dt / 60.0);
      if ($storechange < 0) {
         $storechange = 0;
      }
      $Storage = min(array($storechange, $max_capacity));
      if ($storechange > $max_capacity) {
         $spill = ($storechange - $max_capacity) * 43560.0 / $dt;
      } else {
         $spill = 0;
      }
      $Qout = $spill;
      
      // local unit conversion dealios
      $this->state['evap_gpm'] = $evap_gpm;
      $this->state['pct_use_remain'] = $Storage / $this->state['maxcapacity'];

      $this->state['Storage'] = $Storage;
      $this->state['Qout'] = $Qout;
      $this->state['depth'] = $stage;
      $this->state['Storage'] = $Storage;
      $this->state['spill'] = $spill;
      $this->state['area'] = $area;
      
      $this->postStep();
      
      if ($this->debug) {
         $this->logDebug("Step END state[] array contents " . print_r($this->state,1) . " <br>\n");
      }
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'Storage');
      array_push($publix, 'depth');
      array_push($publix, 'maxcapacity');
      array_push($publix, 'initstorage');
      array_push($publix, 'Qout');
      array_push($publix, 'Qin');
      array_push($publix, 'evap_gpm');

      return $publix;
   }
}
   

class wsp_CumulativeImpactObject extends modelObject {
   
   // time series for withdrawal data, based on objects geometry
   
   // USGS basin shapes connection object
   var $usgsdb = -1;
   var $usgs_host = 'localhost';
   var $usgs_port = 5432;
   var $usgs_dbname = 'va_hydro';
   var $usgs_username = 'usgs_ro';
   var $usgs_password = '@ustin_CL';
   var $usgs_maxrecs = 6;
   // data holder for USGS connection
   var $usgs_basins = array();
   
   // VWUDS data connection object
   var $vwudsdb = -1;
   var $vwuds_host = 'localhost';
   var $vwuds_port = 5432;
   var $vwuds_dbname = 'vwuds';
   var $vwuds_username = 'wsp_ro';
   var $vwuds_password = '314159';
   // data holder for vwuds connection
   var $vwuds_basins = array();
   var $max_diversion = 10.0; // this is a percentage, will be converted later in the routine
   
   
   function wake() {
      parent::wake();
   }
   
   function init() {
      parent::init();
      $this->listobject->show = 0;
      
      $this->reportstring .= "<b>Getting Basins for $this->lon, $this->lat:</b><br>";
      // grab USGS stations that contain these points
      $this->getUSGSBasins($this->lon, $this->lat);
      
      // grab flow stats (if available) for USGS stations
      $this->getUSGSGageStats();
      
      // get all measuring points in this set of basins
      $this->getMeasuringPoints();
      
   }
   
   function finish() {
      $this->listobject->show = 0;
      foreach ($this->usgs_basins as $thiskey => $thisbasin) {
         $this->listobject->queryrecords = $thisbasin['stats'];
         $this->reportstring .= "<b>Basin Name:</b>" . $thisbasin['river_basi'] . "<br>";
         $this->reportstring .= "<b>USGS Gage:</b>" . $thisbasin['station_nu'] . "<br>";
         $this->reportstring .= "<b>Drainage Area:</b>" . $thisbasin['drainage_a'] . "<br>";
         $this->reportstring .= "<b>Basin Stats:</b><br>";
         $this->reportstring .= "<b>Maximum Annual Withdrawal:</b> " . number_format($thisbasin['max_annual_mg'],2) . "<br>";
         $this->reportstring .= "<b>Maximum Daily Withdrawal:</b> " . number_format($thisbasin['max_daily_mgd'],2) . " (" . number_format(($thisbasin['max_daily_mgd'] * 1.547),2) . " cfs)<br>";
         $this->reportstring .= "<b>Maximum Max Daily (with MOS):</b> " . number_format($thisbasin['max_day_mos'],2) . " (" . number_format(($thisbasin['max_day_mos'] * 1.547),2) . " cfs)<br>";
         
         $this->listobject->queryrecords = $thisbasin['withdrawals_cat'];
         $this->reportstring .= "<b>Withdrawals By Category:</b><br>";
         $this->listobject->showList();
         $this->reportstring .= $this->listobject->outstring;
         $this->reportstring .= "<b>Flow Return Period, Percent Diversion Table:</b><br>";
         $this->reportstring .= $thisbasin['exceedance_diversion_table'];
         $this->reportstring .= "<hr>";
      }
   }
   
   function getUSGSBasins($lon = -78.35, $lat = 36.997222) {
      if (!is_object($this->usgsdb)) {
         //$this->reportstring .= "Creating USGS DB Object<br>";
         $this->usgsdb = new pgsql_QueryObject;
         $this->usgsdb->dbconn = pg_connect("host=$this->usgs_host port=$this->usgs_port dbname=$this->usgs_dbname user=$this->usgs_username password=$this->usgs_password");
         $this->usgsdb->show = 0;
         //$this->reportstring .= "USGS DB Object created<br>";
      }
      
      // parent routines grab all data, now do summary queries to
      // update other components, such as the summary data, and the category multipliers
      $dbt = $this->dbtable;
      
      
      $this->usgsdb->querystring = "  select river_basi, station_nu, drainage_a, asText(the_geom) as the_geom  ";
      $this->usgsdb->querystring .= " from usgs_drainage_dd  ";
      $this->usgsdb->querystring .= " where st_contains(the_geom,  st_geomFromText('POINT( $lon $lat )',4326))  ";
      $this->usgsdb->querystring .= " order by drainage_a ";
      if ($this->usgs_maxrecs > 0) {
         $this->usgsdb->querystring .= " limit $this->usgs_maxrecs ";
      }
      if ($this->debug) {
         $this->logDebug("$this->usgsdb->querystring ; <br>");
      }
      //$this->reportstring .= $this->usgsdb->querystring;
      $this->usgsdb->performQuery();
      $this->usgsdb->showList();
      //$this->reportstring .= $this->usgsdb->outstring;
      $this->usgs_basins = $this->usgsdb->queryrecords;
      return;
   }
   
   function getMeasuringPoints() {
      if (!is_object($this->vwudsdb)) {
         $this->vwudsdb = new pgsql_QueryObject;
         $this->vwudsdb->dbconn = pg_connect("host=$this->vwuds_host port=$this->vwuds_port dbname=$this->vwuds_dbname user=$this->vwuds_username password=$this->vwuds_password");
         $this->vwudsdb->show = 0;
      }
      
      // parent routines grab all data, now do summary queries to
      // update other components, such as the summary data, and the category multipliers
      if ($this->debug) {
         $this->logDebug("Iterating through basin list" . print_r($this->usgs_basins,1) . " ; <br>");
      }
      
      foreach ($this->usgs_basins as $thiskey => $thisbasin) {
      
         // get all points in this basin area
         // right now, we are including a sub-query to get the consumptive factors and MOS multipliers 
         // from the waterusetype database.  Later, we will query the waterusetype db to give us these default
         // values, and then put them in a DataMatrix sub-component to allow the users to edit them
         $geom = $thisbasin['the_geom'];
         $basin = $thisbasin['river_basi'];
         $staid = $thisbasin['station_nu'];
         $drainage = $thisbasin['drainage_a'];
         
         $this->vwudsdb->querystring = "  SELECT a.cat_mp, sum(a.max_annual) as max_annual_mg, ";
         $this->vwudsdb->querystring .= "    sum(a.max_maxday) as max_daily_mgd, ";
         $this->vwudsdb->querystring .= "    avg(b.consumption) as consumption, avg(b.max_day_mos) as max_day_mos ";
         $this->vwudsdb->querystring .= " from vwuds_max_action as a, waterusetype as b, usgs_drainage_dd as c ";
         $this->vwudsdb->querystring .= " WHERE a.type = 'SW'  ";
         $this->vwudsdb->querystring .= "    AND a.cat_mp <> 'PH' ";
         $this->vwudsdb->querystring .= "    AND a.action = 'WL' ";
         $this->vwudsdb->querystring .= "    AND a.cat_mp = b.typeabbrev ";
         $this->vwudsdb->querystring .= "    and within(a.the_geom, c.the_geom) ";
         $this->vwudsdb->querystring .= "    and c.station_nu = '$staid' ";
         $this->vwudsdb->querystring .= " GROUP BY a.cat_mp ";
         
         if ($this->debug) {
            $this->logDebug("$this->vwudsdb->querystring ; <br>");
         }
         //error_log($this->vwudsdb->querystring . "; <br>");
         $this->vwudsdb->performQuery();
         if (count($this->vwudsdb->queryrecords) > 0) {
            $this->vwudsdb->showList();
            $this->reportstring .= $this->vwudsdb->outstring;
            $this->reportstring .= $this->vwudsdb->querystring . " ; <br>";
            $this->usgs_basins[$thiskey]['withdrawals_cat'] = $this->vwudsdb->queryrecords;
            $max_annual_mg = 0;
            $max_daily_mgd = 0;
            $max_day_mos = 0;
            foreach ($this->vwudsdb->queryrecords as $thisrec) {
               $max_annual_mg += $thisrec['max_annual_mg'];
               $max_daily_mgd += $thisrec['max_daily_mgd'];
               $max_day_mos += $thisrec['max_daily_mgd'] * $thisrec['max_day_mos'];
            }
            $this->usgs_basins[$thiskey]['max_annual_mg'] = $max_annual_mg;
            $this->usgs_basins[$thiskey]['max_daily_mgd'] = $max_daily_mgd;
            $max_annual365_mgd = $max_annual_mg / 365.0;
            $this->usgs_basins[$thiskey]['max_annual365_mgd'] = $max_annual365_mgd;
            $this->usgs_basins[$thiskey]['max_day_mos'] = $max_day_mos;

            // create Exceedance - Diversion Table
            // based on MAX DAILY
            $edt = '<table>';
            $edt .= "<tr><td><b>Month</b></td>";
            $edt .= "<td><b>5 %</b></td>";
            $edt .= "<td><b>10 %</b></td>";
            $edt .= "<td><b>20 %</b></td>";
            $edt .= "<td><b>25 %</b></td>";
            $edt .= "<td><b>50 %</b></td>";
            $edt .= "<td><b>75 %</b></td>";
            $edt .= "<td><b>80 %</b></td>";
            $edt .= "<td><b>90 %</b></td>";
            $edt .= "<td><b>95 %</b></td></tr>";
            foreach ($this->usgs_basins[$thiskey]['stats'] as $thisstat) {
               $edt .= "<tr>";
               $edt .= "<td>" . $thisstat['month_nu'] . "</td>";
               foreach (array_keys($thisstat) as $statkey) {
                  if (substr($statkey,0,1) == 'p') {
                     // 1.54 converts from MGD to CFS
                     $diversion = 100.0 * $max_day_mos * 1.54 / $thisstat[$statkey];
                     if ($diversion > $this->max_diversion) {
                        $bgcolor = "bgcolor=red";
                        $fopen = "<font color=white>";
                        $fclose = "</font>";
                     } else {
                        $fopen = "<font>";
                        $bgcolor = '';
                        $fclose = "</font>";
                     }
                     $edt .= "<td $bgcolor>$fopen" . number_format($thisstat[$statkey],2);
                     $edt .= " (" . number_format($diversion,2)  . "% )$fclose</td>";
                  }
               }
               $edt .= "</tr>";
            }
            $edt .= "</table>";

            // create Exceedance - Diversion Table
            // based on 1.5 * (MAX ANNUAL / 365.0)
            $edt .= '<b>Max Diversion based on 1.5 * Avg. of Max Annual<br>';

            $edt .= '<table>';
            $edt .= "<tr><td><b>Month</b></td>";
            $edt .= "<td><b>5 %</b></td>";
            $edt .= "<td><b>10 %</b></td>";
            $edt .= "<td><b>20 %</b></td>";
            $edt .= "<td><b>25 %</b></td>";
            $edt .= "<td><b>50 %</b></td>";
            $edt .= "<td><b>75 %</b></td>";
            $edt .= "<td><b>80 %</b></td>";
            $edt .= "<td><b>90 %</b></td>";
            $edt .= "<td><b>95 %</b></td></tr>";
            foreach ($this->usgs_basins[$thiskey]['stats'] as $thisstat) {
               $edt .= "<tr>";
               $edt .= "<td>" . $thisstat['month_nu'] . "</td>";
               foreach (array_keys($thisstat) as $statkey) {
                  if (substr($statkey,0,1) == 'p') {
                     // 1.54 converts from MGD to CFS
                     $diversion = 100.0 * 1.5 * $max_annual365_mgd * 1.54 / $thisstat[$statkey];
                     if ($diversion > $this->max_diversion) {
                        $bgcolor = "bgcolor=red";
                        $fopen = "<font color=white>";
                        $fclose = "</font>";
                     } else {
                        $fopen = "<font>";
                        $bgcolor = '';
                        $fclose = "</font>";
                     }
                     $edt .= "<td $bgcolor>$fopen" . number_format($thisstat[$statkey],2);
                     $edt .= " (" . number_format($diversion,2)  . "% )$fclose</td>";
                  }
               }
               $edt .= "</tr>";
            }
            $edt .= "</table>";

            $this->usgs_basins[$thiskey]['exceedance_diversion_table'] = $edt;
         } else {
            $this->usgs_basins[$thiskey]['exceedance_diversion_table'] = "<b>No withdrawal records exist for " . $thisbasin['station_nu'] . "</b><br>";
         }
      }
      
   }
   
   function getUSGSGageStats() {
      
      foreach ($this->usgs_basins as $thiskey => $thisbasin) {
      
         // get all points in this basin area
         // right now, we are including a sub-query to get the consumptive factors and MOS multipliers 
         // from the waterusetype database.  Later, we will query the waterusetype db to give us these default
         // values, and then put them in a DataMatrix sub-component to allow the users to edit them
         $geom = $thisbasin['the_geom'];
         $basin = $thisbasin['river_basi'];
         $staid = $thisbasin['station_nu'];
         $drainage = $thisbasin['drainage_a'];
         //$this->reportstring .= "$staid" . "; <br>";
         
         
         $thisgage = new USGSGageObject;
         $thisgage->staid = $staid;
         $thisgage->listobject = $this->listobject;
         $thisgage->wake();
         $thisgage->init();
         $numflows = count($thisgage->tsvalues);
         //$this->reportstring .= "Records: $numflows<br>";
         $thisgage->getStationInfo();
         //$this->reportstring .= "Got info<br>";
         $thisgage->getStationStats();
         //$this->reportstring .= "Got Stats<br>";
         $this->reportstring .= $thisgage->debugstring . "<br>";
         $thisgage->listobject->querystring = "  SELECT month_nu, min(min_va) as min_mo, avg(mean_va) as mean_mo, ";
         $thisgage->listobject->querystring .= "    avg(p05_va) as p05, avg(p10_va) AS p10, ";
         $thisgage->listobject->querystring .= "    avg(p20_va) AS p20, avg(p25_va) AS p25, avg(p50_va) AS p50, ";
         $thisgage->listobject->querystring .= "    avg(p75_va) AS p75, avg(p80_va) AS p80, avg(p90_va) AS p90, ";
         $thisgage->listobject->querystring .= "    avg(p95_va) AS p95 ";
         $thisgage->listobject->querystring .= " FROM $thisgage->stat_dbtblname ";
         $thisgage->listobject->querystring .= " GROUP BY month_nu ";
         $thisgage->listobject->querystring .= " ORDER BY month_nu ";
         if ($this->debug) {
            $this->logDebug("$thisgage->listobject->querystring ; <br>");
         }
         //$this->reportstring .= $thisgage->listobject->querystring . "; <br>";
         $thisgage->listobject->performQuery();
         $thisgage->listobject->showList();
         //$this->reportstring .= $thisgage->listobject->outstring . "; <br>";
         
         $this->usgs_basins[$thiskey]['stats'] = $thisgage->listobject->queryrecords;
         
      }
      
   }
   
}


class textField extends modelSubObject {

   var $charlength = 64;
   var $value_dbcolumntype = 'varchar(64)';
   var $value = '';
   var $loggable = 1;
   var $json2d = TRUE;
   
   
  function wake() {
    $this->loggable = 1;
    $this->value_dbcolumntype = "varchar($this->charlength)";
    parent::wake();
    $this->result = $this->value;
  }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'value');

      return $publix;
   }
   function evaluate() {
      //$this->result = 'test_string';
   }
   
   function postStep() {
      $this->writeToParent();
   }
}


function add_one($x_off)
{
    return (x_off + 1.0);
}

class omRuntime_SubComponent {
  var $value;
  function __construct($options) {
    return TRUE;
  }
}

?>
