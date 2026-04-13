<?php
include_once("config.php");
print("<html><head>");
include("./misc_headers.php");
//include_once("xajax_config.php");
/* ******************************************************* 
*** This is a generic project template that takes a    ***
*** single object with a custom form as a template     ***
*** and lets the user create copies of the template    ***
******************************************************* */

$noajax = 0;
include_once('xajax_modeling.common.php');
if ($debug) {
   print("Loading xajax javascript: $liburl/xajax");
}
$xajax->printJavascript("$liburl/xajax");
?>
<script src="/scripts/scripts.js"></script>

<script type="text/javascript">
function confirmDeleteElement(elemname) {
    var answer = confirm("Are you sure that you wish to delete " + elemname + "?")
    if (answer){
        document.forms['elementtree'].elements.actiontype.value='delete'; 
        xajax_deleteObject(xajax.getFormValues('elementtree'));
    }
}
function confirmRevert(elemname, formname) {
    var answer = confirm("Are you sure that you wish to discard your changes for " + elemname + "?")
    if (answer){
        document.forms[formname].elements.actiontype.value='edit'; 
        document.forms[formname].submit(); 
    }
}
</script>
</head>
<body>
<?php
$debugHTML = '';
$noajax = 1; // tell it not to send headers, already done
include_once('./xajax_modeling.element.php');
//print("Aset variables: " . print_r(array_keys($adminsetuparray),1) . "<br>");
# Set the map file to use
include("./cust_asetup.php");
$scriptname = "$baseurl/edit_project.php";
$elementid = -1;
$templateid = 176170;
error_reporting(E_ALL);
$vars = array('elementid', 'single', 'actiontype', 'projtype', 'templatefile', 'templateid', 'adminname', 'runform', 'cache_runid' );
// template files for the properties & analysis tabs are specified according to projtype
// template id also set according to projtype
$props = array(
   'elementid' => -1,
   'scenarioid' => -1,
   'single' => 0,
   'actiontype' => '',
   'templateid'=>176170, 
   'templatefile'=>"./forms/project_info.html",
   'adminname' => '',
   'runform' => 'generic',
   'cache_runid' => ''
);


foreach ($vars as $thisvar) {
   if (isset($_GET[$thisvar])) {
      $formvars = $_GET;
      $props[$thisvar] = $_GET[$thisvar];
      //print("Setting $thisvar = " . $_GET[$thisvar] . "<br>");
   }
   if (isset($_POST[$thisvar])) {
      $formvars = $_POST;
      $props[$thisvar] = $_POST[$thisvar];
      //print("Setting $thisvar = " . $_POST[$thisvar] . "<br>");
   }
}
//print_r($_POST);
//print_r($props);
if ( ($props['actiontype'] == '') or ($props['actiontype'] == 'login') ) {
   if ( $props['elementid'] > 0) {
      $props['actiontype'] = 'edit';
   } else {
      $props['actiontype'] = 'list';
   }
}
$debug = 0;
if ($userid == 1) {
   $debug = 0;
   //print_r($_POST);
   //print_r($props);
} else {
   $debug = 0;
}
if (isset($_POST['switch_elid'])) { 
   if ($_POST['switch_elid'] > 0) {
      // this will happen if we are loading an element
      $props['elementid'] = $_POST['switch_elid'];
   }
}

// Retrieve object information
$object_info = getElementInfo($listobject, $props['elementid']);
// check for cached object property request
if ($props['actiontype'] == 'load_cached') {
   $res = unSerializeSingleModelObject($props['elementid'], array(), $debug, FALSE, TRUE, $props['cache_runid'] );
   print("Load Cached called with Run ID: " . $props['cache_runid'] . "<br>");
   //print("Result: " . $res['error'] . "<br>");
   //print("Stored objects: " . print_r(array_keys($unserobjects),1) . "<br>");
}

// if admin_name is not set, guess from object information
$always_guess = 1;
if ( ($props['adminname'] == '') or ($always_guess) ) {
   if ($debug) {
      $debugHTML .= "Looking for admin table name for object  " . print_r($object_info,1) . " <br>";
   }
   //print( "Looking for admin table name for object  " . print_r($object_info,1) . " <br>");
   foreach ($adminsetuparray as $thistype => $asrec) {
      if ($debug) {
         $debugHTML .= " ... Checking $thistype against " . $object_info['custom1'];
      }
      if (isset($asrec['table info']['object_custom1'])) {
         if (!is_array($asrec['table info']['object_custom1'])) {
            $types = array($asrec['table info']['object_custom1']);
         } else {
            $types = $asrec['table info']['object_custom1'];
         }
         if (in_array($object_info['custom1'], $types)) {
            $props['adminname'] = $thistype;
            if ($debug) {
               $debugHTML .= " ... Setting admin type to $thistype<br>";
            }
            break;
         }
      } else {
	     if ($debug) {
		    $debugHTML .= " ... No object_custom1 setting for $thistype<br>";
         }
	  }
   }
}
if ( ($props['adminname'] == '') and ($elementid <> -1) ) {
   $props['adminname'] = 'project_info';
}

#########################################
# Print Headers
#########################################
if (!$props['single']) {
   $restrict_menus = array('logout.php');
   include("./medit_menu.php");
}
#########################################
# END - Print Headers
#########################################

$thisrec = array();

$form_name = 'form1';
$adminsetup = $adminsetuparray[$props['adminname']];
$adminsetup['column info']['groupid']['params'] = "groups:groupid:groupname:groupname:0:groupid in (select groupid from mapusergroups where userid = $userid) ";
$adminsetup['column info']['scenarioid']['params'] = "scenario:scenarioid:scenario:scenario:0:groupid in (select groupid from mapusergroups where userid = $userid) ";
$adminsetup['table info']['formName'] = $form_name;
if (isset($adminsetup['table info']['templatefile'])) {
   $props['templatefile'] = $adminsetup['table info']['templatefile'];
   if ($debug) {
      $debugHTML .= "Getting template setup from adminsetup record.<br>";
   }
}
if (isset($adminsetup['table info']['templateid'])) {
   $props['templateid'] = $adminsetup['table info']['templateid'];
   if ($debug) {
      $debugHTML .= "Getting template id setup from adminsetup record.<br>";
   }
}
if (isset($adminsetup['table info']['runform'])) {
   $props['runform'] = $adminsetup['table info']['runform'];
   if ($debug) {
      $debugHTML .= "Getting Run Form ( " . $props['runform'] . ") from adminsetup record.<br>";
   }
}
// model property tabs, default to one
/* not active - one tab for properties now, only!
if (!is_array($props['templatefile'])) {
   $property_tabs = array('model_props'=>$props['templatefile']);
} else {
   $property_tabs = $props['templatefile'];
}
*/
if ($debug) {
   $debugHTML .= "ASET: " . $props['adminname'] . " - FILE: $filename - ASETrecs" . print_r($adminsetup,1) . "<br>";
}

// ******************************** //
// *** BEGIN - SAVING ROUTINE   *** //
// ******************************** //
$formerror = 0;
if ($props['actiontype'] == 'save') { 
   print("<b>Notice: </b> Saving Model Object.<br>");
   $content = file_get_contents($props['templatefile']);
   $sr = saveCustomModelElement($listobject, $_POST, $adminsetup, $content, $props['elementid'], $props['templateid'], $debug);
   if ($debug) {
      $debugHTML .= "<hr><b>Saving Debugging Info</b>:<br>" . $sr['debugHTML'];
      $debugHTML .= "<hr><b>Saving Errors</b>:<br>" . $sr['errors'];
   }
   if ($sr['error']) {
      $debugHTML .= "<hr><b>Saving Debugging Info</b>:<br>" . $sr['debugHTML'];
      //print("<hr><b>Saving Debugging Info</b>:<br>" . $sr['debugHTML']);
   }
   if ($sr['elementid'] > 0) {
      $showel = $sr['elementid'];
      $props['elementid'] = $showel;
   } else {
      print("<b>Error:</b> Could Not Save Object.<br>");
      print("<div bgcolor='ltgray'>" . $sr['innerHTML'] . print_r($sr['errors'],1) . "</div>");
      $formerror = 1;
   }
}
// ******************************** //
// ***  END  - SAVING ROUTINE   *** //
// ******************************** //

//**** NOW - LOAD OBJECTS to edit if requested ****
// if elementid = -1, we want to show the template object
if ($props['actiontype'] == 'load_template') {
   $showel = $props['templateid'];
   $props['elementid'] = -1;
} else {
   $showel = $props['elementid'];
}

if ( ($showel <> -1) and ($props['actiontype'] <> 'load_cached') ) {
   $action = 'save';
   print("Clearing model element cache<br>");
   $unserobjects = array(); // clear unserobjects so we will get fresh copies
   if ($debug) {
      $debugHTML .= "getModelVarsForCustomForm($showel, " . $props['templatefile'] . ", $debug);<br>";
      $debugHTML .= "User: $userid, Default Scenario: " . $_SESSION['defscenarioid'] . " <br>";
   }
}
if ( $props['actiontype'] == 'load_cached' ) {
   $action = 'save';
   $props['actiontype'] = 'edit';
}
// evaluate permissions

// *****************************
// set up a panel object
// *****************************
if ($debug) {
   error_log("Creating tabbedListObject ");
}
$panelHTML = '';
$taboutput = new tabbedListObject;
$taboutput->name = 'model_element';
$taboutput->height = '800px';
$taboutput->width = '800px';
$taboutput->width = '100%';
//$taboutput->button_class = 'iconmenu';
$taboutput->tab_names = array();
$taboutput->tab_names[] = 'model_props';
$taboutput->tab_names[] = 'model_run';
$taboutput->tab_names[] = 'analysis';
$taboutput->tab_names[] = 'aquatic_bio';
$taboutput->tab_names[] = 'browse';
$taboutput->tab_names[] = 'search';
$taboutput->tab_names[] = 'create';
$taboutput->tab_names[] = 'tools';
$taboutput->tab_buttontext = array(
   'search'=>"<img src='/icons/search.png' alt'Search'>",
   'browse'=>'Browse',
   'model_props'=>'Project Information',
   'model_run'=>'Run Model',
   'analysis'=>'Results / Analysis',
   'aquatic_bio'=>'Aquatic Biology',
   'create'=>"<a onclick='document.forms[\"$form_name\"].elements[\"actiontype\"].value = \"load_template\"; document.forms[\"$form_name\"].submit()'>New Project</a><br>",
   'tools'=>'Tools'
);
if ($debug or ($userid == 1)) {
   $taboutput->tab_names[] = 'debug';
   $taboutput->tab_buttontext['debug'] = 'Debug';
}
$taboutput->init();
// *****************************
// Finished setting up a panel object
// *****************************

// *****************************
// main editing panel information
// *****************************
if ($debug) {
   error_log("creating main editing panel ");
}

$taboutput->tab_HTML['model_props'] = "<form id='$form_name' name='$form_name' action='$scriptname' method=post>";
//$debug = 1;
// set up form basics
$taboutput->tab_HTML['model_props'] .= showHiddenField('elementid',$props['elementid'], 1, 0);
$taboutput->tab_HTML['model_props'] .= showHiddenField('adminname',$props['adminname'], 1, 0);
$taboutput->tab_HTML['model_props'] .= showHiddenField('templatefile',$props['templatefile'], 1, 0);
$taboutput->tab_HTML['model_props'] .= showHiddenField('templateid',$props['templateid'], 1, 0);
$taboutput->tab_HTML['model_props'] .= showHiddenField('single',$props['single'], 1, 0);
$taboutput->tab_HTML['model_props'] .= showHiddenField('switch_elid',-1, 1, 0);
$taboutput->tab_HTML['model_props'] .= showHiddenField('cache_runid','', 1, 0);
$taboutput->tab_HTML['model_props'] .= showHiddenField('actiontype',$action, 1, 0);
$i = 1;
//print_r($props);
if ($props['actiontype'] == 'list') {
   $taboutput->default_tab = 'browse';
} else {
   // show the object editing interface
   if ($debug) {
      $debugHTML .= "Setting up tab $tabname from file " . $props['templatefile'] . " <br>";
   }
   $content = file_get_contents($props['templatefile']);
   if (!$formerror) {
      $thisrec = getModelVarsForCustomForm($showel, $props['templatefile'], $debug);
   } else {
      // if there was a form submission error, we keep whatever data was submitted
      $thisrec = $formdata;
   }
   // evaluate permissions
   $disabled = 0;
   if ($debug) {
      error_log("Form Data retrieved ");
   }

   if ($debug) {
      $debugHTML .= "Parsed Custom Form: " . print_r($thisrec,1) . "<br>";
   }
   $taboutput->tab_HTML['model_props'] .= "Element ID: " . $props['elementid'] . "<br>";
   $taboutput->tab_HTML['model_props'] .= "<table>";
   $taboutput->tab_HTML['model_props'] .= showCustomHTMLForm($listobject,$thisrec,$adminsetup, $content, 0, 0, $debug, $disabled);
   $taboutput->tab_HTML['model_props'] .= "</table>";
   $submit = "document.forms[\"$form_name\"].submit()";
   if (!$disabled) {
      $taboutput->tab_HTML['model_props'] .= showGenericButton('save','Save', $submit, 1, 0);
      $revert = "confirmRevert(\"" . $object_info['elemname'] . "\", \"$form_name\"); ";
      $taboutput->tab_HTML['model_props'] .= showGenericButton('revert','Cancel Changes', $revert, 1, 0);
   }
   $i++;
}
$taboutput->tab_HTML['model_props'] .= "</form>";
   // set the default tab to the project browse panel
// *****************************
// *** FINISHED MAIN EDITING PANEL *** //
// *****************************

if ($debug) {
   error_log("creating MODEL ruN panel ");
}
// *****************************
// model run tab
// *****************************
$taboutput->tab_HTML['model_run'] .= "<b>Run Model / View Results:</b><br>";
//$taboutput->tab_HTML['model_run'] .= print_r($props,1) . "<br>";
$runvars = array('projectid'=>$props['projectid'], 'scenarioid'=>$props['scenarioid'], 'elementid'=>$props['elementid'], 'runform'=>$props['runform'], 'formname'=>'runinfo', 'statusdiv'=>'status_bar', 'cache_runid' => 2, 'startdate'=>'2001-01-01', 'enddate'=>'2001-12-31');
switch ($props['runform']) {
   case 'vwp':
      //$taboutput->tab_HTML['model_run'] .= print_r($props, 1) . "<br>";
      $taboutput->tab_HTML['model_run'] .= vwpModelControlForm($runvars);
      $taboutput->tab_HTML['model_run'] .= "<br><b>Model Run Status</b><br>";
      $taboutput->tab_HTML['model_run'] .= showGenericButton('refresh', 'Update Model Status', "xajax_showRecentStatus(xajax.getFormValues(\"runinfo\"));", 1) . "<br>";
      $taboutput->tab_HTML['model_run'] .= "\n<div id='status_bar' bgcolor='lightgrey' style=\"border: 1px solid rgb(0 , 0, 0); border-style: dotted; overflow: auto; height: 200px;  width: 600; display: block;  background: #eee9e9;\">&nbsp;</div>\n";
   break;
   
   case 'wsp':
      //$taboutput->tab_HTML['model_run'] .= print_r($props, 1) . "<br>";
      //$taboutput->tab_HTML['model_run'] .= covaWSPModelControlForm($runvars);
      $taboutput->tab_HTML['model_run'] .= genericModelControlForm($runvars);
      $taboutput->tab_HTML['model_run'] .= "<br><b>Model Run Status</b><br>";
      $taboutput->tab_HTML['model_run'] .= showGenericButton('refresh', 'Update Model Status', "xajax_showRecentStatus(xajax.getFormValues(\"runinfo\"));", 1) . "<br>";
      $taboutput->tab_HTML['model_run'] .= "\n<div id='status_bar' bgcolor='lightgrey' style=\"border: 1px solid rgb(0 , 0, 0); border-style: dotted; overflow: auto; height: 200px;  width: 600; display: block;  background: #eee9e9;\">&nbsp;</div>\n";
   break;
   
   case 'cova_child':
      //$taboutput->tab_HTML['model_run'] .= print_r($props, 1) . "<br>";
      $taboutput->tab_HTML['model_run'] .= covaChildModelControlForm($runvars);
      $taboutput->tab_HTML['model_run'] .= "<br><b>Model Run Status</b><br>";
      $taboutput->tab_HTML['model_run'] .= showGenericButton('refresh', 'Update Model Status', "xajax_showRecentStatus(xajax.getFormValues(\"runinfo\"));", 1) . "<br>";
      $taboutput->tab_HTML['model_run'] .= "\n<div id='status_bar' bgcolor='lightgrey' style=\"border: 1px solid rgb(0 , 0, 0); border-style: dotted; overflow: auto; height: 200px;  width: 600; display: block;  background: #eee9e9;\">&nbsp;</div>\n";
   break;
   
   case 'child_select':
      //$taboutput->tab_HTML['model_run'] .= print_r($props, 1) . "<br>";
      $runvars['parentid'] = $runvars['elementid'];
      $taboutput->tab_HTML['model_run'] .= selectChildCacheModelControlForm($runvars);
      $taboutput->tab_HTML['model_run'] .= "<br><b>Model Run Status</b><br>";
      $taboutput->tab_HTML['model_run'] .= showGenericButton('refresh', 'Update Model Status', "xajax_showRecentStatus(xajax.getFormValues(\"runinfo\"));", 1) . "<br>";
      $taboutput->tab_HTML['model_run'] .= "\n<div id='status_bar' bgcolor='lightgrey' style=\"border: 1px solid rgb(0 , 0, 0); border-style: dotted; overflow: auto; height: 200px;  width: 600; display: block;  background: #eee9e9;\">&nbsp;</div>\n";
   break;
  
  
   default:
      //$taboutput->tab_HTML['model_run'] .= genericModelControlForm($runvars);
      $taboutput->tab_HTML['model_run'] .= selectChildCacheModelControlForm($runvars);
      $taboutput->tab_HTML['model_run'] .= showGenericButton('refresh', 'Update Model Status', "xajax_showRecentStatus(xajax.getFormValues(\"runinfo\"));", 1) . "<br>";
      $taboutput->tab_HTML['model_run'] .= "\n<div id='status_bar' bgcolor='lightgrey' style=\"border: 1px solid rgb(0 , 0, 0); border-style: dotted; overflow: auto; height: 200px;  width: 600; display: block;  background: #eee9e9;\">&nbsp;</div>\n";
   break;
}
// *****************************
// END - model run tab
// *****************************
  

// *****************************
// Analysis and Output Tab
// *****************************
// do a query for any children that are graph objects, or report objects
// show any querywizard sub-objects
// show the analysis widget
//$results_panel = showModelRunForm();
//$taboutput->tab_HTML['model_run'] .= $results_panel;
if ( ($props['actiontype'] <> 'list') and ($showel <> -1) ) {
   $taboutput->tab_HTML['analysis'] .= "<div id='agrid_" . $props['elementid'] . "' style=\"border: 1px solid rgb(0 , 0, 0); border-style: dotted; overflow: auto; height: 480px; width: 720px; display: block;  background: #eee9e9;\">";
   $awin = showAnalysisWindow($props);
   $controlHTML = $awin['innerHTML'];
   $taboutput->tab_HTML['analysis'] .= $controlHTML;
   $taboutput->tab_HTML['analysis'] .= "</div>";

   // now load any graph objects by default
   $gtypes = array('graphObject', 'giniGraph');
   $graph_children = getChildComponentType($listobject, $props['elementid'], $gtypes, -1, $debug);
   if ($debug) {
      $taboutput->tab_HTML['analysis'] .= "<br>Graph children " . print_r($graph_children,1);
   }
   foreach ($graph_children as $thischild) {
      $taboutput->tab_HTML['analysis'] .= "<hr>";
      $taboutput->tab_HTML['analysis'] .= "<b>Output Table for Object:</b>" . $thischild['elemname'] . "<br>";
      $taboutput->tab_HTML['analysis'] .= "<div id='agrid_" . $thischild['elementid'] . "' style=\"border: 1px solid rgb(0 , 0, 0); border-style: dotted; overflow: auto; height: 480px; width: 720px; display: block;  background: #eee9e9;\">";
      $awin = showAnalysisWindow($thischild);
      $controlHTML = $awin['innerHTML'];
      $taboutput->tab_HTML['analysis'] .= $controlHTML;
      $taboutput->tab_HTML['analysis'] .= "</div>";
   }
}

// *****************************
// END - Analysis and Output Tab
// *****************************

// *****************************
// BEGIN - Aquatic Bio Tab
// *****************************
$spatial_id = $runvars['elementid'];
switch ($props['runform']) {
   case 'wsp':
      if (is_object($unserobjects[$runvars['elementid']])) {
         $thisobject = $unserobjects[$runvars['elementid']];
         if (is_object($thisobject->processors['locid'])) {
            $spatial_id = $thisobject->processors['locid']->getProp('parent_elementid');
         }
      }
   break;
  
   default:
      $spatial_id = $runvars['elementid'];
   break;
}
/*
// DISABLE UNUSED TAB
$wkt = getElementShape($spatial_id);
$bioinfo = showBioDB ($wkt);
$taboutput->tab_HTML['aquatic_bio'] .= "<i>Using elementid $spatial_id for spatial coverage </i><br>";
$taboutput->tab_HTML['aquatic_bio'] .= $bioinfo['innerHTML'];
*/
//$taboutput->tab_HTML['aquatic_bio'] .= $bioinfo['debug'];
/ *****************************
// END - Aquatic Bio Tab
// *****************************


// *****************************
// Object Tree Browser
// *****************************
$searchvars = $_POST;
$taboutput->tab_HTML['browse'] .= "<h3>Browse Projects</h3><hr>";
if (isset($adminsetup['table info']['object_custom1'])) {
   $obtype = $adminsetup['table info']['object_custom1'];
} else {
   $obtype = 'cova_vwp_projinfo';
}
$obsql = " select a.elementid, a.elemname as \"Project Name\", b.username as \"Project Owner\", c.groupname as \"Project Group\" ";
$obsql .= " from scen_model_element as a ";
$obsql .= " left outer join users as b ";
$obsql .= "    on (a.ownerid = b.userid) ";
$obsql .= " left outer join groups as c ";
$obsql .= "    on (a.groupid = c.groupid) ";
$obsql .= " where custom1 = '$obtype' ";
$obsql .= "    and a.ownerid = $userid ";
$obsql .= " order by a.elemname ";

$othsql = " select a.elementid, a.elemname as \"Project Name\", b.username as \"Project Owner\", c.groupname as \"Project Group\" ";
$othsql .= " from scen_model_element as a ";
$othsql .= " left outer join users as b ";
$othsql .= "    on (a.ownerid = b.userid) ";
$othsql .= " left outer join groups as c ";
$othsql .= "    on (a.groupid = c.groupid) ";
$othsql .= " where custom1 = '$obtype' ";
// groupid 2 is Users, which is essentially public for all logged in users
$othsql .= "    and ( ( a.groupid in ($usergroupids) and a.gperms >= 4 and a.groupid <> 2) ";
$othsql .= "    ) ";
$othsql .= "    and a.ownerid <> $userid ";
$othsql .= " order by a.elemname ";

$pubsql = " select a.elementid, a.elemname as \"Project Name\", b.username as \"Project Owner\", c.groupname as \"Project Group\" ";
$pubsql .= " from scen_model_element as a ";
$pubsql .= " left outer join users as b ";
$pubsql .= "    on (a.ownerid = b.userid) ";
$pubsql .= " left outer join groups as c ";
$pubsql .= "    on (a.groupid = c.groupid) ";
$pubsql .= " where custom1 = '$obtype' ";
$pubsql .= "    and ( (NOT a.groupid in ($usergroupids)) or (a.groupid = 2) ) ";
$pubsql .= "       and (a.pperms >= 4) ";
// dont show the templates to regular users even though they technically have to be read-able
$pubsql .= "       and (a.elementid <> " . $props['templateid'] . ") ";
$pubsql .= "    and a.ownerid <> $userid ";
$pubsql .= " order by a.elemname ";

foreach (array("My Projects"=>$obsql, "My Group Projects"=>$othsql, "Public Projects"=>$pubsql) as $key => $sql) {
  $listobject->querystring = $sql;
  $listobject->performQuery();
  
  $recs = $listobject->queryrecords;
  foreach ($recs as $thiskey => $thisrec) {
	 $vid = $thisrec['elementid'];
	 $vname = $thisrec['Project Name'];
	 $recs[$thiskey]['Project Name'] = "<a onclick='document.forms[\"$form_name\"].elements[\"actiontype\"].value = \"edit\";  document.forms[\"$form_name\"].elements[\"switch_elid\"].value = $vid; document.forms[\"$form_name\"].submit()'>$vname</a>";
  }
  $listobject->queryrecords = $recs;
  $listobject->show = 0;
  $listobject->showList();
  $taboutput->tab_HTML['browse'] .= "<div style=\"border: 1px solid rgb(0 , 0, 0); border-style: dotted; overflow: auto; height: 80px; display: block;  background: #eee9e9;\">";
  $taboutput->tab_HTML['browse'] .= "<b>$key</b><br>";
  if ($debug) {
	 $taboutput->tab_HTML['browse'] .= $listobject->querystring . "<br>";
  }
  if ($listobject->numrows > 0) {
	 $taboutput->tab_HTML['browse'] .= $listobject->outstring;
  } else {
	 $taboutput->tab_HTML['browse'] .= "<i>None Available.</i><br>";
  }
	 $taboutput->tab_HTML['browse'] .= "</div>";
}
// *****************************
// END - Object Tree Browser
// *****************************

// *****************************
// Search Interface
// *****************************
$searchvars = $_POST;
$searchvars['divname'] = $taboutput->getTabID('search');
$searchvars['result_type'] = 'vwp_modelsearch';
$taboutput->tab_HTML['search'] .= "searchvars = " . print_r($searchvars,1) . "<br>";
$taboutput->tab_HTML['search'] .= modelSearchForm($searchvars);
// *****************************
// END - Search Interface
// *****************************

// *****************************
//   START - Tools & Special
// *****************************
$cache_form_name = 'load_cached_form';
$load_script = "document.forms[\"$form_name\"].elements[\"actiontype\"].value = \"load_cached\";  document.forms[\"$form_name\"].elements[\"cache_runid\"].value = document.forms[\"$cache_form_name\"].elements[\"cache_runid\"].value; document.forms[\"$form_name\"].submit()";
$taboutput->tab_HTML['tools'] .= "<form id='$cache_form_name' name='$cache_form_name' >";
$taboutput->tab_HTML['tools'] .= "Load Variables from Run ID:" . showActiveList($listobject, 'cache_runid', 'scen_model_run_elements', 'runid', 'runid', " elementid = ". $props['elementid'] , -1, "", 'runid', $debug, 1, 0);
$taboutput->tab_HTML['tools'] .= "<br>" . showGenericButton('load_cached','Load Cached', $load_script, 1, 0);
$taboutput->tab_HTML['tools'] .= "</form>";
// *****************************
//   END - Tools & Special
// *****************************
if ($userid == 1) {
   $debugHTML .= "POST Variables: " . $_POST . "<hr>";
   $taboutput->tab_HTML['debug'] .= $debugHTML;
}


/* RENDER FINAL TABBED OBJECT */
$taboutput->createTabListView($activetab);
# add the tabbed view the this object
$panelHTML .= $taboutput->innerHTML;
print($panelHTML);

//print("$innerHTML<hr>");
?>
</body>
</html>
