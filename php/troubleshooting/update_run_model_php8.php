<?php
# set up db connection
#include('config.php');
$noajax = 1;
$projectid = 3;
$runtype = 'normal';
$sumdir = '/var/www/html/d.dh/'; // default for post-processing
include_once('xajax_modeling.element.php');

error_reporting(E_ERROR);
print("Un-serializing Model Object <br>");
Un-serializing Model Object <br>
$debug = 0;
//**********************************************************************//
// THIS SECTION REPLICATES run_model.php calling:
// php -f run_model.php 214595 200 cached_meta_model 1998-01-01 2002-12-31 -1 -1 1 0 200
//**********************************************************************//

list($elementid, $runid) = array(214595, 200);
$argv = array('run_model.php', 214595, 200, 'cached_meta_model', '1998-01-01', '2002-12-31', -1, -1, 1, 0, 200);
$runVars = array('runid'=>-1, 'elementid'=>-1, 'cache_runid'=>-1, 'startdate'=>'', 'enddate'=>'', 'cache_level'=>-1, 'cache_list' => '', 'test_only' => 0, 'scenarioid' => -1);
$vars = array(1=>'elementid', 2=>'runid', 3=>'runtype', 4=>'startdate', 5=>'enddate', 6=>'cache_runid', 7=>'cache_list', 8=>'cache_level', 9=>'test_only', 10=>'scenarioid');
$formValues = array();
foreach ($vars as $key => $var) {
    if (isset($argv[$key])) {
       $formValues[$var] = $argv[$key];
    }
 }

foreach ($runVars as $thiskey => $thisval) {
    if (isset($formValues[$thiskey])) {
       $runVars[$thiskey] = $formValues[$thiskey];
    }
 }
$offset = $thiskey;
$optional_input = array('dt'); // This starts at the 9th param.
                               // A hinky way of doing it, but works OK for cmd line
foreach ($optional as $thiskey => $thisval) {
    if (isset($formValues[$thiskey . $offset])) {
       $optional_input[$thisval] = $formValues[$thiskey . $offset];
    }
 }

//*****************************************************************//
// simulate this:
// runCached($runVars['elementid'], $runVars['runid'], $runVars['cache_runid'], $runVars['startdate'], $runVars['enddate'], $runVars['cache_list'], $runVars['cache_level'], array(), array(), $runVars['test_only']);
// NOTE: function runCached($elementid, $runid, $cache_runid, $startdate, $enddate, $cache_list, $cache_level, $dynamics, $input_props = array(), $test_only = 0);
//*****************************************************************//

$elementid = $runVars['elementid'];$runid = $runVars['runid'];$cache_runid = $runVars['cache_runid'];
$startdate = $runVars['startdate'];$enddate = $runVars['enddate'];$cache_list = $runVars['cache_list'];
$cache_level = $runVars['cache_level'];$dynamics = array();$input_props = array();$test_only = $runVars['test_only'];


global $modeldb, $listobject, $outdir, $serverip;
$run_date = date('r');
if (!is_array($cache_list)) {
   $cache_list = explode(',', $cache_list);
 }
// this routine, if passed empty cache_list and dynamics list will simply perform a normal model run
if (is_array($elementid)) {
   if (count($elementid) > 0) {
      $firstel = $elementid[0];
   } else {
      $firstel = -1;
   }
 } else {
   $firstel = $elementid;
 }
$input_props['outdir'] = isset($input_props['outdir']) ? $input_props['outdir'] : $outdir;

if ( (strlen($startdate) > 0) and (strlen($enddate) > 0)) {
    $input_props['model_startdate'] = $startdate;
    $input_props['model_enddate'] = $enddate;
 }

if (!$test_only) {
   setStatus($listobject, $firstel, "Initiating Model Run for Elementid $firstel with runCached() function", $serverip, 1, $runid, -1, 1);
 } else {
   // stash the old status
   $test_status = verifyRunStatus($listobject, $firstel, $runid);
 }

// load up all of the things that are in the base model, with caching specified
error_log("Calling loadModelUsingCached(modeldb, $elementid, $runid, $cache_runid with cache_level = $cache_level");
error_log("  with input_props = " . print_r($input_props,1));


// ***** BReaking Down loadModelUsingCached
//$model_elements = loadModelUsingCached($modeldb, $elementid, $runid, $cache_runid, $input_props, $cache_level, $cache_list, $run_date);

global $listobject;
$retarr = array();
$retarr['errors'] = '';
$cache_complete = array();
// *********************************
// *** Get Cached Objects       ****
// *********************************

// iterate through the objects that are requested to be cached and instantiate them
error_log("Checking the following objects for valid cache entries" . print_r($cache_list,1));
foreach ($cache_list as $thisel) {
  if (is_array($thisel)) {
     $elid = $thisel['elementid'];
     $crid = $thisel['runid'];
  } else {
     $elid = $thisel;
     $crid = $cache_runid;
  }
  //error_log("Loading cached version of $elid from run $crid");
  // should check cache status here to see if this object should be rerun based on cache_level setting
  // this will allow there to be objects for which cache_runid = runid to use the info from the last run of runid
  // such as upstream objects
  // if cache_runid = -1, we do not intend to use this function (though some may be sent with individual cache requests and should be honored)
  $cacheable = getElementCacheable($listobject, $elid);
  //error_log( "function getElementCacheable(listobject, $elid) returned $cacheable (crid = $crid )");
  //die;
  //if ( ($crid <> -1) and (in_array($cacheable, array(1,3))) ) {
  if ( ($crid <> -1) ) {
  // check cache status, only allow caching if setting is 1 or 3
     $res = loadCachedObject($modeldb, $elid, $crid, $debug);
     $retarr['error'] .= $res['error'];
    error_log("loadCachedObject($elid, $crid) tableinfo:" . print_r($res['tableinfo'],1));
     //copyTreeCacheFiles($elid, $crid, $runid, 0);
     copyTreeCacheFiles($elid, $crid, $runid, 1, 0, $run_date);
     // get all children of this object and copy the cache file into this file
     // copyRunCacheFile($elementid, $src_runid, $dest_runid);
     $cache_complete[] = $elid;
  }
  
}

error_log("Calling unSerializeModelObject($elementid, input_props, modeldb, $cache_level, $runid) ");
error_log("  With input_props = " . print_r($input_props,1));
//********** stepping through unSerializeModelObject
// $thisobresult = unSerializeModelObject($elementid, $input_props, $modeldb, $cache_level, $runid);

global $listobject, $tmpdir, $shellcopy, $ucitables, $scenarioid, $debug, $outdir, $outurl, $goutdir, $gouturl, $unserobjects, $adminsetuparray, $wdm_messagefile, $wdimex_exe, $basedir, $model_startdate, $model_enddate, $serverip, $modeldb, $modelcontainerid, $modelcontainername;

error_log("unSerializeModelObject called for $elementid <br>");
$modelcontainerid = (!isset($modelcontainerid)) ? $elementid : $modelcontainerid;
//error_log("**** calling getElementName(, $elementid)");
$elemname = getElementName($listobject, $elementid);
$modelcontainername = (!isset($modelcontainername)) ? $elemname : $modelcontainername;

# goes through all contained objects:
#   processors
#   inputs
#   components
# and un-serializes them into objects
# connects objects to parent container
# returns the object, and any error output in an associative array('debug', 'object')
#$debug = 1;
# create a global container to hold any objects that have already been instantiated
$returnArray = array();
$returnArray['error'] = '';
$returnArray['debug'] = '';
$returnArray['object'] = '';
$returnArray['complist'] = array();
$returnArray['cached'] = array();
$returnArray['live'] = array();
$returnArray['remote'] = array();
array_push($returnArray['complist'], $elementid);
if (!isset($model_startdate)) {
  $model_startdate = '';
  $returnArray['error'] .= "Global model_startdate not defined .<br>";
}
error_log("Checking start date <br>");
if (isset($input_props['model_startdate'])) {
  $conv_time = new DateTime($input_props['model_startdate']);
  $model_startdate = $conv_time->format('Y-m-d H:i:s');
  $returnArray['error'] .= "Setting model_startdate - $model_startdate .<br>";
}
if (!isset($model_enddate)) {
  $model_enddate = '';
  $returnArray['error'] .= "Global model_enddate not defined .<br>";
}
error_log("Checking end date <br>");
if (isset($input_props['model_enddate'])) {
  $conv_time = new DateTime($input_props['model_enddate']);
  $model_enddate = $conv_time->format('Y-m-d H:i:s');
  $returnArray['error'] .= "Setting model_enddate - $model_enddate .<br>";
}
//error_log("Checking listobject $elementid <br>");
// unless we are passed one, we implicitly assume that the standard list object is valid
if (!is_object($model_listobj)) {
 // swap modeldb for listobject as default here, when loading objects for editing - does it break anything?
 // maybe it requires the adminsetuparray or something?
  $model_listobj = $listobject;
  //error_log("No Valid Model Object Passed, using default Database for Model Runtime Storage");
} else {
  //error_log("Received Valid Object for Database for Model Runtime Storage");
}

if (!is_array($unserobjects)) {
  if ($debug) {
     $returnArray['debug'] .= "Creating Blank Unser Objects array<br>";
  }
  $unserobjects = array();
   // the level in the nested hierarchy
}
if ($debug) {
  $returnArray['debug'] .= "Unser Objects<br>";
  $returnArray['debug'] .= print_r($unserobjects,1);
  $returnArray['debug'] .= "End Unser Objects<br>";
}

// check on caching status of this object
//error_log("**** calling getElementOrder(, $elementid)");
$order = getElementOrder($listobject, $elementid);
$cache_file_exists = 0;

// new cache check sub-routine

//error_log("**** calling checkObjectCacheStatus(, $elementid)");
$cache_res = checkObjectCacheStatus($listobject, $elementid, $order, $cache_level, $cache_id, $current_level, $model_startdate, $model_enddate, $debug);
$cache_type = $cache_res['cache_type'];
$cache_file_exists = $cache_res['cache_file_exists'];
$cacheable = $cache_res['cacheable'];
$returnArray['error'] .= $cache_res['error'];
//error_log("Element $elementid: checkObjectCacheStatus(order = $order, cache_level = $cache_level, cache_id = $cache_id, current_level = $current_level) :: Cache Type: $cache_type - Cacheable - $cacheable ");
//error_log("Element $elementid: Cache Settings: " . print_r($cache_res,1));
switch ($cache_res['cache_host_status']) {
 case -1:
   setStatus($listobject, -1, "Error: Cache request for $elementid timed out", $serverip, 1, $cache_id, -1, 1);
 break;
 case 0:
   if (($cache_type <> 'disabled') and ($cache_file_exists == 0)) {
     setStatus($listobject, -1, "Error: Could not find cache file $cache_file for $elementid", $serverip, 1, $cache_id, -1, 1);
   }
 break;
 default:
   // do nothing, cache is OK 
 break;
}



error_log("Loading $elemname ($elementid) as live model element.");
setStatus($listobject, $modelcontainerid, "Loading $elemname ($elementid) as live model element.", $serverip, 1, $cache_id, -1, 0);
array_push($returnArray['live'], $elementid);
// instantiate this model to run
// new code, uses unserializeSingleModelObject
if (!isset($unserobjects[$elementid])) {
   $us_result = unserializeSingleModelObject($elementid, $input_props, $debug, $model_listobj);
   $thisobject = $us_result['object'];
   // we call this AFTER the wake() method of the object, because this will properly format the start and end time
   if (count($unserobjects) <= 1) {
      if (property_exists($thisobject, 'starttime')) {
         if ( ($model_startdate == '') or ($model_enddate == '') ) {
            $model_startdate = $thisobject->starttime;
            $model_enddate = $thisobject->endtime;
         }
      }
   }
} else {
   $thisobject = $unserobjects[$elementid];
   $returnArray['error'] .= "Retrieving object from cache<br>";
}
$returnArray['error'] .= "Retrieving object sub-components, dates: $model_startdate, $model_enddate <br>";


# retrieve child component linkages
$linkrecs = getChildComponentType($listobject, $elementid);
if ($debug) {
   $returnArray['debug'] .= " Searching for Contained objects in $thisobject->name <br>";
}
foreach ($linkrecs as $thisrec) {
   $src_id = $thisrec['elementid'];
   //error_log("Found child $src_id of parent $elementid");
   if ($debug) {
      $returnArray['debug'] .= " Searching for $src_id in " . print_r(array_keys($unserobjects)) . '<br>';
   }
   if (in_array($src_id, array_keys($unserobjects))) {
      # fetch from already instantiated objects
      $linkobj = $unserobjects[$src_id];
   } else {
      // increment current_level + 1 when we call contained objects
      $returnArray['error'] .= "Unserializing element $src_id with dates $model_startdate, $model_enddate <br>";
      if ($cacheable == 0) {
         $child_cache_level = -1;
      } else {
         $child_cache_level = $cache_level;
      }
      $params = array();
      //error_log("Unserializing child $src_id of parent $elementid");
      $linkobjarray = unSerializeModelObject($src_id, array(), $model_listobj, $child_cache_level, $cache_id, $current_level + 1, $set_status);
      $linkerror = $linkobjarray['error'];
      $linkdebug = $linkobjarray['debug'];
      foreach ($linkobjarray['complist'] as $thiselement) {
         if (!in_array($thiselement, $returnArray['complist'])) {
            array_push($returnArray['complist'], $thiselement);
         }
      }
      foreach ($linkobjarray['cached'] as $thiselement) {
         if (!in_array($thiselement, $returnArray['cached'])) {
            array_push($returnArray['cached'], $thiselement);
         }
      }
      foreach ($linkobjarray['live'] as $thiselement) {
         if (!in_array($thiselement, $returnArray['live'])) {
            $returnArray['live'][] = $thiselement;
         }
      }
      foreach ($linkobjarray['remote'] as $thiselement) {
         if (!in_array($thiselement, $returnArray['remote'])) {
            $returnArray['remote'][] = $thiselement;
         }
      }
      $returnArray['debug'] .= $linkdebug;
      $linkobj = $linkobjarray['object'];
      if (strlen($linkerror) > 0) {
         # error in sub-object, return the message and quit
         $returnArray['error'] .= " Error instantiating sub-object $src_id :<br>";
         $returnArray['error'] .= $linkerror;
      }
   }
   if ($debug) {
      $returnArray['debug'] .= " Adding Component $linkobj->name  <br>";
   }
   $thisobject->addComponent($linkobj);
   error_log("Added child $linkobj->name ($src_id)");
}

//error_log("*** Contained child models added");
# retrieve input linkages
$linkrecs = getInputLinkages($listobject, $elementid, array(2,3)); 
if ($debug) {
   $returnArray['debug'] .= " Searching for Input objects in $thisobject->name <br>";
}
error_log("*** Adding Input Linkages");
foreach ($linkrecs as $thisrec) {
   $src_id = $thisrec['src_id'];
   $src_prop = $thisrec['src_prop'];
   $dest_prop = $thisrec['dest_prop'];
   $linktype = $thisrec['linktype'];
   if ($linktype == 3) {
      // remote link, always use cached, if does not exist, report error and continue
      $returnArray['error'] .= "Found remote link for element $src_id - param $src_prop -> $dest_prop <br>\n";
      error_log("Found remote link for element $src_id - param $src_prop -> $dest_prop <br>\n");
      $res = loadCachedObject($model_listobj, $src_id, $cache_id, $debug);
      if ($res['tableinfo']['record_missing']) {
         $returnArray['error'] .= "Cache file for runid $cache_id and $elementid MISSING<br>";
         error_log("Cache file for runid $cache_id and $elementid MISSING<br>");
      }
      $linkobj = $res['object'];
      //$linkobj->debug = 1;
      //$linkobj->debugmode = 1;
      array_push($returnArray['cached'], $src_id);
      //$unserobjects[$elementid] = $thisobject;
      
      $returnArray['error'] .= "Cached object created for $src_id <br>";
      $returnArray['error'] .= "Used New Caching Routine <br>";
      error_log("Cached object created for $src_id <br>");
      error_log('cache info: ' . print_r($res['tableinfo'],1));
      $thisobject->addInput($dest_prop, $src_prop, $linkobj);
      // this means that only actual containers can USE a remotely linked object, since there is no way to pass the 
      // init(), step() and other methods to it - could think about adding this in a different way, as a timeseries subcomp
      // perhaps?
      if (method_exists($thisobject, 'addComponent')) {
         $thisobject->addComponent($linkobj);
      }
   } else {
      if ($debug) {
         $returnArray['debug'] .= " Searching for $src_id in " . print_r(array_keys($unserobjects)) . '<br>';
      }
      if ($src_id <> -1) {
         if (in_array($src_id, array_keys($unserobjects))) {
            # fetch from already instantiated objects
            $linkobj = $unserobjects[$src_id];
            if ($debug) {
               $returnArray['debug'] .= " Adding Input $linkobj->name :from unser array <br>";
            }
         } else {
            if ($debug) {
               $returnArray['debug'] .= " Creating Input $src_id  <br>";
            }
            // now, here we could put in a switch to see if we should run the input objects that are NOT contained
            // by this object.  We could opt to have those objects instantiated as a time series with cached values 
            // from a previous model run, allowing us a more economical way of running objects that rely on inputs 
            // from other model containers, external to this one.
            // could force cacheing with a switch at cache_level
            $linkobjarray = unSerializeModelObject($src_id, array(), $model_listobj, $cache_level, $cache_id, $current_level, $set_status);
            $linkerror = $linkobjarray['error'];
            $linkdebug = $linkobjarray['debug'];
            $returnArray['debug'] .= $linkdebug;
            foreach ($linkobjarray['complist'] as $thiselement) {
               if (!in_array($thiselement, $returnArray['complist'])) {
                  array_push($returnArray['complist'], $thiselement);
               }
            }
            foreach ($linkobjarray['cached'] as $thiselement) {
               if (!in_array($thiselement, $returnArray['cached'])) {
                  array_push($returnArray['cached'], $thiselement);
               }
            }
            foreach ($linkobjarray['live'] as $thiselement) {
               if (!in_array($thiselement, $returnArray['live'])) {
                  $returnArray['live'][] = $thiselement;
               }
            }
            foreach ($linkobjarray['remote'] as $thiselement) {
               if (!in_array($thiselement, $returnArray['remote'])) {
                  $returnArray['remote'][] = $thiselement;
               }
            }
            $linkobj = $linkobjarray['object'];
            $returnArray['error'] .= $linkerror;
         }
         if ($debug) {
            $returnArray['debug'] .= " Adding Input $linkobj->name : $src_prop -gt $dest_prop <br>";
         }
         $thisobject->addInput($dest_prop, $src_prop, $linkobj);
      } else {
         $linkerror = 'NULL Linkage found';
         $linkdebug = 'NULL Linkage found';
         $returnArray['debug'] .= $linkdebug;
         $linkobj = NULL;
      }
   }