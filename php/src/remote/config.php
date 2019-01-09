<?php
# this is to over-ride newer PHP defaults that turn off error reporting by default
# this is to prevent an unexpected error from revealing information that could be
# used to compromise security

# shutdown function
function halted()
{
    global $listobject, $cropobject;
#    $listobject->cancel();
    #$cropobject->cancel();
    pg_close($listobject->dbconn);
    #pg_close($cropobject->dbconn);
    #print("Query Cancelled!<br>");
}
//register_shutdown_function('halted');
if (!isset($debug)) {
   $debug = 0;
}
#$debug = 1;
#ini_set('display_errors', 'On');
#ini_set('error_reporting', 'E_ALL');
#error_reporting(E_ALL & ~E_STRICT);
error_reporting(E_ERROR);
//error_reporting(E_ALL);
#error_reporting(E_NONE);

$scriptname = $_SERVER['PHP_SELF'];

include_once('./config.local.php');

if (isset($_SESSION['projectid'])) {
   $projectid = $_SESSION['projectid'];
} else {
   if (!isset($projectid)) {
      $projectid = 3;
   }
}
$indir = "$basedir/in";
$compdir = "$datadir/proj$projectid/components";
$outdir = "$datadir/proj$projectid/out";
$outurl = "$dataurl/proj$projectid/out";
$ucidir = "$httppath/uci/";
$goutdir = "$httproot/tmp/";
$goutpath = "$httproot/tmp";
# location of the graphics library - jpgraph
//$glibdir = "$libpath/jpgraph";
$glibdir = "$libpath/jpgraph/jpgraph-4.0.2/src";

#include_once("$libpath/module_activemap.php");

# get database and file libraries
include_once("$libpath/psql_functions.php");
include_once("$libpath/lib_oracle.php");
include_once("$libpath/lib_odbc.php");
include_once("$libpath/file_functions.php");
# custom stream definition to write and read excel files
#include_once("$libpath/xlsstream/excel.php");

# security related libraries
include_once("$libpath/sanitize.inc.php");

# get PEAR libraries
if ($debug) {
   error_log("Loading PEAR Libraries");
}
#if (!class_exists('PEAR')) {
#   include_once("$libpath/PEAR/PEAR.php");
#}
include_once("$libpath/PEAR/Tar.php");
include_once("$libpath/PEAR/Serializer.php");
include_once("$libpath/PEAR/Unserializer.php");
require_once("$libpath/magpierss/rss_fetch.inc"); 


if ($debug) {
   error_log("Loading Misc Libraries");
}
include_once("$libpath/misc_functions.php");
include_once("$libpath/db_functions.php");
include_once("$libpath/data_functions.php");
//include_once("$libpath/phpmath/Matrix.php");


if ($debug) {
   error_log("Loading Modeling Libraries");
}
# get application libraries
include_once("$libpath/HSPFFunctions.php");
include_once("$libpath/lib_source_assessment.php");
include_once("$libpath/lib_hydro.php");
include_once("$libpath/lib_hydrology.php");
include_once("$libpath/lib_nhdplus.php");
if ($debug) {
   error_log("Loading Math Libraries");
}
include_once("$libpath/lib_equation2.php");
if ($debug) {
   error_log("Loading GIS Libraries");
}
include_once("$libpath/lib_gis.php");
if ($debug) {
   error_log("Loading Remote Data Aquisition Libraries");
}
include_once("$libpath/lib_usgs.php");
if ($debug) {
   error_log("Loading Graphing Libraries");
}
include_once("$libpath/lib_plot.php");
define('DEFAULT_GFORMAT',$default_imagetype);
if ($debug) {
   error_log("Loading Water Supply Libraries");
}
include_once("$libpath/lib_vwuds.php");
include_once("$libpath/lib_wsp.php");
include_once("$libpath/lib_batchmodel.php");


if ($debug) {
   error_log("Loading Local Libraries");
}
# get local libraries
include_once("$basedir/local_functions.php");
include_once("$basedir/lib_scenario.php");
include_once("$basedir/lib_admin.php");
include_once("$basedir/lib_vwp.php");
// summary lib
include_once("$basedir/summary/lib_cova_summary.php");
# get modeling components
#include_once("$basedir/who_xmlobjects.php");


if ($debug) {
   error_log("Loading Variable Defaults");
}
# get local default values
include_once("$basedir/adminsetup.php");
include_once("$libpath/lib_wooomm.php");
include_once("$libpath/lib_wooomm.USGS.php");
include_once("$libpath/lib_wooomm.wsp.php");
include_once ("$libdir/lib_wooomm.data.php");

# get local form functions
#include_once("$basedir/forms/form_modeldata.php");


if ($debug) {
   error_log("Connecting to Database Object");
}
// START - set up database connections
$connstring = "host=$dbip dbname=$dbname user=$dbuser password=$dbpass";
$dbconn = pg_connect($connstring, PGSQL_CONNECT_FORCE_NEW);

$listobject = new pgsql_QueryObject;
$listobject->connstring = $connstring;
$listobject->ogis_compliant = 1;
$listobject->dbconn = $dbconn;
$listobject->adminsetuparray = $adminsetuparray;

$session_connstring = "host=$session_dbip port=$session_port dbname=$session_dbname user=$session_dbuser password=$session_dbpass";
$session_dbconn = pg_connect($session_connstring, PGSQL_CONNECT_FORCE_NEW);
$session_db = new pgsql_QueryObject;
$session_db->connstring = $session_connstring;
$session_db->ogis_compliant = 1;
$session_db->dbconn = $session_dbconn;
$session_db->adminsetuparray = $adminsetuparray;

// create a linkage to the deq2 database in order to use the plR stats package
$dbconn = pg_connect("host=$dbip2 port=5432 dbname=$dbname2 user=$dbuser password=$dbpass");
$analysis_db = new pgsql_QueryObject;
$analysis_db->dbconn = $dbconn;

// linkage to cbp database with ICPRB info
$connstring = "host=$cbp_dbip dbname=cbp user=$dbuser password=$dbpass port=$cbp_port";
$dbconn = pg_connect($connstring, PGSQL_CONNECT_FORCE_NEW);
$cbp_listobject = new pgsql_QueryObject;
$cbp_listobject->connstring = $connstring;
$cbp_listobject->ogis_compliant = 1;
$cbp_listobject->dbconn = $dbconn;
$cbp_listobject->adminsetuparray = $adminsetuparray;

// vwuds database link
$vwuds_dbconn = pg_connect("host=$vwuds_dbip port=5432 dbname=$vwuds_dbname user=$vwuds_dbuser password=$vwuds_dbpass");
$vwuds_listobject = new pgsql_QueryObject;
$vwuds_listobject->dbconn = $vwuds_dbconn;

// wsp database
$wsp_dbconn = pg_connect("host=$vwuds_dbip port=5432 dbname=drupal715 user=wsp_ro password=q_only");
$wsp_listobject = new pgsql_QueryObject;
$wsp_listobject->dbconn = $wsp_dbconn;

// END - set up database connections
# Timer
$timer = new timerObject;

$useragent = $_SERVER['HTTP_USER_AGENT'];
if (preg_match('|MSIE ([0-9].[0-9]{1,2})|',$useragent,$matched)) {
   // IE no like .ico files in pages, so use gif
   $icons = array(
      'modelContainer'=>"$iconurl/model_container.gif",
      'HSPFContainer'=>"$iconurl/basins.jpg",
      'graphObject'=>"$iconurl/pie_chart.gif",
      'channelObject'=>"$iconurl/river.gif",
      'USGSChannelGeomObject'=>"$iconurl/river.gif",
      'hydroObject'=>"$iconurl/river.gif",
      'domain'=>"$iconurl/earth_sm.png",
      'NOAADataObject'=>"$iconurl/weather.gif",
      'giniGraph'=>"$iconurl/gini.gif",
      'USGSGageObject'=>"$iconurl/usgs.gif",
      'clone'=>"$iconurl/clone.gif",
      'deepclone'=>"$iconurl/deep-clone.gif",
      'edit'=>"$iconurl/edit.gif",
      'trash'=>"$iconurl/icon_trash.gif",
      'default'=>"$iconurl/default.gif",
      'tools'=>"$iconurl/tools.gif",
      'tools'=>"$iconurl/tools.gif",
      'CBPModelContainer'=>"$iconurl/cbp.gif",
      'CBPDataConnection'=>"$iconurl/cbp.gif",
      'CBPLandDataConnection'=>"$iconurl/cbp.gif",
      'CBPDataInsert'=>"$iconurl/cbp.gif"
   );
} else {
   $icons = array(
      'modelContainer'=>"$iconurl/model_container.ico",
      'HSPFContainer'=>"$iconurl/basins.jpg",
      'graphObject'=>"$iconurl/pie_chart.ico",
      'channelObject'=>"$iconurl/river.ico",
      'USGSChannelGeomObject'=>"$iconurl/river.ico",
      'hydroObject'=>"$iconurl/river.ico",
      'domain'=>"$iconurl/earth_sm.png",
      'NOAADataObject'=>"$iconurl/weather.ico",
      'giniGraph'=>"$iconurl/gini.ico",
      'USGSGageObject'=>"$iconurl/usgs.ico",
      'clone'=>"$iconurl/clone.ico",
      'deepclone'=>"$iconurl/deep-clone.ico",
      'edit'=>"$iconurl/edit.ico",
      'trash'=>"$iconurl/icon_trash.gif",
      'default'=>"$iconurl/default.ico",
      'tools'=>"$iconurl/tools.ico",
      'tools'=>"$iconurl/tools.ico",
      'CBPModelContainer'=>"$iconurl/cbp.ico",
      'CBPDataConnection'=>"$iconurl/cbp.ico",
      'CBPLandDataConnection'=>"$iconurl/cbp.ico",
      'CBPDataInsert'=>"$iconurl/cbp.ico"
   );
}

if ($debug) {
   error_log("Initializing HSPF_UCIobject");
}
$uciobject = new HSPF_UCIobject;
$uciobject->ucidir = $ucidir;
$uciobject->listobject = $listobject;
$uciobject->ucitables = $ucitables;

# create a separate db object for the crop database
# should merge these dbs in the future
#$cropobject = new pgsql_QueryObject;
#$cropobject->dbconn = $cropdbconn;
#$cropobject->adminsetuparray = $adminsetuparray;

# get standard masslinks
#$masslinks =  file ("$libpath/mass-link.txt");

$loggedin = 0;

if ($debug) {
   error_log("Initializing session");
}
session_start();
# process login and user specific vaiables
if (isset($_POST['username'])) {
   # check the credentials
   //session_register('username');
   //session_register('userpass');
   $_SESSION['username'] = $_POST['username'];
   $_SESSION['userpass'] = $_POST['userpass'];
}

if (isset($_GET['username'])) {
   # check the credentials
   //session_register('username');
   //session_register('userpass');
   $_SESSION['username'] = $_GET['username'];
   $_SESSION['userpass'] = $_GET['userpass'];
}
#print_r($_GET);
if (isset($_SESSION['username'])) {
   $up = sanitize($_SESSION['userpass'], SQL);
   $un = $_SESSION['username'];
   $listobject->querystring = "select * from users where username = '$un' and userpass = '$up'";
   $listobject->performQuery();
   //$listobject->showList();
   //print("$listobject->querystring ; <br>");
   $setextent = 0;
   if (count($listobject->queryrecords) > 0) {
      # stash user info
      $userinfo = $listobject->queryrecords[0];
      $userid = $listobject->getRecordValue(1,'userid');
      $usertype = $listobject->getRecordValue(1,'usertype');
      $defaultgroupid = $listobject->getRecordValue(1,'groupid');
      $userproject = $listobject->getRecordValue(1,'defaultproject');
      $defscenarioid = $listobject->getRecordValue(1,'defscenario');

      $_SESSION['userid'] = $userid;
      $_SESSION['usertype'] = $usertype;
      $_SESSION['defscenarioid'] = $defscenarioid;
      $_SESSION['indir'] = $indir . '/users/' . $listobject->getRecordValue(1,'indir');
      $_SESSION['outdir'] = $outdir . '/users/' . $listobject->getRecordValue(1,'outdir');
      #print("$listobject->querystring ; <br>");
      $loggedin = 1;
      #print("Login Successful.<br>");
      if (isset($_POST['actiontype'])) {
         if ( $_POST['actiontype'] == 'login' ) {
            # this is a login request, so log the result
            $thisdate = date('r',time());
            $thisip = $_SERVER['REMOTE_ADDR'];
            $listobject->querystring = "  insert into loginlog (userid, thisdate, thisip) ";
            $listobject->querystring .= " values ($userid, '$thisdate', '$thisip') ";

            $listobject->performQuery();

            # force a re-centering of GIS window
            $currentgroup = -2;
            $lastgroup = -1;

            # load information from the state table
            $listobject->querystring = "  select * from user_state ";
            $listobject->querystring .= " where userid = $userid ";
            $listobject->performQuery();
            #$listobject->showList();

            foreach ($listobject->queryrecords as $statevar) {
               $varname = $statevar['varname'];
               $varvalue = $statevar['varvalue'];
               switch ($varname) {
                  case 'scenarioid':
                  $scenarioid = $varvalue;
                  break;

                  case 'projectid':
                  $projectid = $varvalue;
                  break;

                  case 'currentgroup':
                  $currentgroup = $varvalue;
                  break;

                  case 'extent':
                  $stateextent = $varvalue;
                  $setextent = 1;
                  break;
               }
            }

         }
      }
   } else {
      $_SESSION['userid'] = $userid;
      //print("Login Failed.<br>");
      $loggedin = 0;
   }
   $listobject->querystring = "select groupid from mapusergroups where userid = $userid ";
   $listobject->performQuery();
   if (count($listobject->queryrecords) > 0) {
      $usergroupids = '';
      $udel = '';
      foreach($listobject->queryrecords as $thisrec) {
         $usergroupids .= $udel . $thisrec['groupid'];
         $udel = ',';
      }
   } else {
      $usergroupids = 0;
   }
}


###############################################################
###################     End Scen/Proj Info    #################
###############################################################
$projectid = -2;
# first set it to the users default project, if this is specified in their account
if ($userproject > 0) {
   $projectid = $userproject;
}
if (isset($_GET['projectid'])) {
   $projectid = $_GET['projectid'];
}
if (isset($_POST['projectid'])) {
   $projectid = $_POST['projectid'];
}

if ($projectid == -2) {
   #$listobject->querystring = "select min(projectid) as projectid from project where projectname <> 'default'";
   #$listobject->performQuery();
   #$projectid = $listobject->getRecordValue(1,'projectid');
   # set default projectid here
   $projectid = 65;
}

if ($projectid <> -1) {
   $listobject->querystring = "select * from project where projectid = $projectid";
   $listobject->performQuery();
   $projinfo = $listobject->queryrecords[0];
}

$_SESSION['projectid'] = $projectid;

# defaults
# this is now set in the users own information
#$defscenarioid = $projinfo['defscenario'];
$scenarioid = -1;
$thisdate = date('r',time());

if (isset($_GET['scenarioid']) ) {
   $scenarioid = $_GET['scenarioid'];
}

if (isset($_POST['scenarioid']) ) {
   $scenarioid = $_POST['scenarioid'];
}

if ( ($scenarioid == -1) or ($scenarioid == '') ) {
   $scenarioid = $defscenarioid;
}
$_SESSION['scenarioid'] = $scenarioid;

# process state variables
#print_r($_POST);
$statevarnames = array('scenarioid', 'projectid', 'currentgroup', 'extent');
foreach ($statevarnames as $varname) {
   $novar = 0;
   switch ($varname) {
      case 'scenarioid':
         $varvalue = $scenarioid;
      break;

      case 'projectid':
         $varvalue = $projectid;
      break;

      case 'currentgroup':
         if (isset($_GET['currentgroup'])) {
            $currentgroup = $_GET['currentgroup'];
         }
         if (isset($_POST['currentgroup'])) {
            $currentgroup = $_POST['currentgroup'];
         }
         $varvalue = $currentgroup;
      break;

      case 'extent':
         $novar = 1;
         if (isset($_GET['currentextent'])) {
            $varvalue = $_GET['currentextent'];
            $novar = 0;
         }
         if (isset($_POST['extent'])) {
            $varvalue = $_POST['extent'];
            $novar = 0;
         }
      break;

      default:
         $novar = 1;
      break;
   }

   if (!$novar) {
      $listobject->querystring = " delete from user_state where userid = $userid and varname = '$varname'";
      $listobject->performQuery();
      $listobject->querystring = "  insert into user_state (userid, varname, varvalue) ";
      $listobject->querystring .= " values($userid, '$varname', '$varvalue') ";
      #print("$listobject->querystring ; <br>");
      $listobject->performQuery();
   }
}

# get some basic scenario info
$listobject->querystring = "select * from scenario where scenarioid = $scenarioid";
$listobject->performQuery();
#$listobject->showList();
$sceninfo = $listobject->queryrecords[0];
# Determine Permissions for this user and scenario
$perms = getScenarioPerms($listobject, $scenarioid, $userid, $usergroupids, $debug);

###############################################################
###################     End Scen/Proj Info    #################
###############################################################

# session variables
if ( !$loggedin ) {
   include_once('./login.php');
}
# parse the file that is requested
//print($defaultpage . "<br>");
$callpieces = preg_split('[\/]', $scriptname);
$callfile = $callpieces[(count($callpieces) - 1)];
if (isset($_POST['target'])) {
   $targpieces = preg_split('[\/]', $_POST['target']);
   $targetpage = $targpieces[(count($targpieces) - 1)];
   if (!in_array($targetpage, array('login.php','index.php','logout.php','create_account.php')) and ($targetpage <> '') ) {
      //print("Setting $defaultpage to $targetpage <br>");
      $defaultpage = $targetpage;
   }
}

//print($defaultpage . "<br>");
if ( ($callfile == 'login.php') ) {
   if (strlen($_SERVER['QUERY_STRING']) > 0) {
      http_redirect("$baseurl/$defaultpage", $_SERVER['argv']);
   }
}
if ( ($callfile == 'login.php') or ($callfile == 'index.php') ) {
   $scriptname = $baseurl . '/' . $defaultpage;
   $callfile = $defaultpage;
   #print("$scriptname <br>");
   include_once($callfile);
}


###############################################################
###################       Misc. Settings      #################
###############################################################
# row colors for formatted printouts
$rc[0] = 'white';
$rc[1] = 'grey';

?>
