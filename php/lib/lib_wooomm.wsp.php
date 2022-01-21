<?php

// **************************************
// *** lib_wooomm.wsp.php            ****
// *** commonwealth of virginia      ****
// *** water supply planning objects ****
// **************************************


if (!class_exists('modelObject')) {
   include_once('./lib_hydrology.php');
}
if (!class_exists('Equation')) {
   include_once('./lib_equation2.php');
}


class waterSupplyModelNode extends modelContainer {
   var $flow_mode = 0; // 0 - best available, 1 - USGS baseline, 2 - USGS Synth, 3 - VA HSPF, 4+ are for custom use
   // currently a blank class, but ultimately it might hold some sensible common properties
   
   function init() {
      parent::init();
      $this->setSingleDataColumnType('flow_mode', 'integer', $this->flow_mode);
   }
   
   function showElementInfo($propname = '', $view = 'info', $params = array()) {
      $localviews = array('gmap');
      $output = '';
      //error_log("$this->name showElementInfo called with view = '$view' and propname '$propname' ");
      if ( (trim($propname) == '') or !isset($this->processors[$propname] ) ) {
         if (in_array(trim($view), $localviews)) {
            switch (trim($view)) {
               case 'gmap':
               //error_log("calling showShapeOnMap () ");
               $output .= $this->showShapeOnMap();
               //error_log("done showShapeOnMap () ");
               break;

            }
         } else {
            $output .= parent::showElementInfo($propname, $view, $params);
         }
      }
      return $output;
   }
   
   function showShapeOnMap() {
      // shows the geometry of this element on a gmap interface
      // get extent of this geometry 
      $innerHTML = '';
      if (is_object($this->listobject)) {
         $this->listobject->querystring = "select st_extent(st_geomFromText('$this->the_geom')) as ext ";
         $this->listobject->performQuery();
         if ( ($this->listobject->numrows > 0) and !$this->listobject->error) {
            $ext = $this->listobject->getRecordValue(1,'ext');
            //print("Geometry extent returned: $ext <br>");
            // gmap view
            list($open,$geomtext,$close) = preg_split("/[()]/", $ext);
            $dims = join(",", preg_split("/[,\s]/", $geomtext));
            list($lon1,$lat1,$lon2,$lat2) = explode(',',$dims);
            $mapurl = "http://deq1.bse.vt.edu/om/nhd_tools/gmap_test.php?";
            $mapurl .= "lon1=$lon1&lat1=$lat1&lon2=$lon2&lat2=$lat2&elementid=";
            $mapurl .= $this->componentid . "&mapwidth=400&mapheight=400";
            $innerHTML .= "<iframe height=400 width=400 src='" . $mapurl . "'></iframe>";
            //$innerHTML .= " URL = " . $mapurl;
         } else {
            $innerHTML .= $this->listobject->error_string;
         }
      } else {
         $innerHTML .= "Can Not show shape on map - listobject not defined";
      }
      return $innerHTML;
   }
}


class waterSupplyElement extends modelContainer {
  var $flow_mode = 0; // 0 - best available, 1 - USGS baseline, 2 - USGS Synth, 3 - VA HSPF, 4+ are for custom use
  // currently a blank class, but ultimately it might hold some sensible common properties
  function step() {
   parent::step();
   if ($this->debug) {
     error_log("Executing timestep on $this->name");
   }
  }
}

class dynamicWaterUsers extends dynamicQuerySubComponents {
   var $scenarioid = 37;
   var $custom1 = 'cova_withdrawal';
   var $waterusetype = ''; // VWUDS abbreviation: PWS, COM, PN, etc.
   var $wdtype = ''; // VWUDS abbreviation: GW, SW, TW
   var $action = ''; // VWUDS abbreviation: WL, SD, SR
   var $threshold = 0; // minimum/maximum numeric threshold
   var $threshold_operator = 'na'; // na - Not Applicable (default), lt, gt, le, ge 
   var $threshold_var = 'na'; // na - Not Applicable (default), current_mgy, max_wd_annual, max_wd_daily, max_wd_monthly
   var $current_mgy = 0;
   var $historic_annual = 0;
   var $recreate_list = 'threshold,waterusetype';
   var $wvars = 0;
   var $glm; //holds the array of glm parameters
   var $keeplist = array();
   
   function wake() {
      $this->addParams(array('scenarioid', 'custom1'));
      $this->addSerialist('waterusetype');
      $this->addSerialist('wdtype');
      $this->addSerialist('action');
      parent::wake();
      // obtain basic information
      $this->summarizeData();
      $this->wvars = array('current_mgy', 'linear_trend_mgy');
      $this->setDataColumnTypes();
   }
       
   function sleep() {
      $this->historic_annual = 0;
      $this->glm = 0;
      parent::sleep();
   }
  
   function init() {
      parent::init();
      // obtain in-depth information
      $this->summarizeData();
   }
 
   function summarizeData() {
      $this->current_mgy = 0;
      $this->historic_annual = array();
      //error_log("Going through prop list: " . print_r($this->proplist,1));
      foreach ($this->proplist as $key => $props) {
         $current_mgy = floatval($props['current_mgy']);
         //error_log("Adding $current_mgy to current MGY");
         $this->current_mgy += $current_mgy;
         $yrs = array();
         foreach ($props['historic_annual'] as $yr => $wd) {
            if ($yr > 0) {
               if (!isset($this->historic_annual[$yr])) {
                  $this->historic_annual[$yr] = $wd;
               } else {
                  $this->historic_annual[$yr] += $wd;
               }
            }
         }
         $this->state['current_mgy'] = $this->current_mgy;
         //$this->state['current_mgy'] = $this->current_mgy; 
      }

      if ( class_exists('Stat1') and count($this->historic_annual) ) {
         $h = new Stat1;
         if ($this->debug) {
            $this->logDebug("Creating GLM with: " . print_r($this->historic_annual,1)  . "<br>");
         }
         //error_log("Creating GLM with: " . print_r($this->historic_annual,1) );
         $h->create(array_keys($this->historic_annual),array_values($this->historic_annual),"");
         $this->glm = $h->getStats();
      } else {
         //error_log("Class Stat1 not found");
         if ($this->debug) {
            $this->logDebug("class_exists('Stat1') returns " . class_exists('Stat1') . " count(this->historic_annual) = " . count($this->historic_annual) . " -- will not project linear trend<br>");
         }
      }
   }

   function writeToParent() {
      if (is_object($this->parentobject)) {
         foreach ($this->wvars as $thisvar) {
            $this->parentobject->setStateVar($this->getParentVarName($thisvar), $this->state[$thisvar]);
         }
      }
   }
   
   function step() {
      parent::step();
      $this->doLinearUseTrend();
      $this->writeToParent();
   }
   
   function doLinearUseTrend() {
      if ($this->debug) {
         $this->logDebug("Trying to project linear trend<br>");
      }
      if ( is_array($this->glm) ) {
         if ( isset($this->glm['m']) and isset($this->glm['b']) ) {
            $this->state['linear_trend_mgy'] = $this->glm['m'] * $this->state['year'] + $this->glm['b'];
            if ($this->debug) {
               $this->logDebug("Projecting: " . $this->state['linear_trend_mgy'] . ' = ' . $this->glm['m'] . ' * ' . $this->state['year'] . ' + ' . $this->glm['b'] . "<br>");
            }
         }
      } else {
         $this->state['linear_trend_mgy'] = 0;
         if ($this->debug) {
            $this->logDebug("GLM not set for $this->name not projecting<br>");
         }
      }
   }
   
   function showFormHeader($formatted,$formname, $disabled = 0) {
      $innerHTML = '<table>';
      $innerHTML .= "<tr><td colspan=3>";
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'];
      $innerHTML .= " | <b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'];
      $innerHTML .= " | <b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'];
      $innerHTML .= "</td></tr>";
      $innerHTML .= "<tr><td colspan=3>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'];
      $innerHTML .= " | <b>Scenario:</b> " . $formatted->formpieces['fields']['scenarioid'];
      $innerHTML .= "</td></tr>";
      $innerHTML .= "<tr><td>";
      $innerHTML .= "<b>Use Type:</b><br>" . $formatted->formpieces['fields']['waterusetype'];
      $innerHTML .= "</td><td>";
      $innerHTML .= "<b>Source Type:</b><br>" . $formatted->formpieces['fields']['wdtype']; 
      $innerHTML .= "</td><td>";
      $innerHTML .= "<b>Action:</b><br>" . $formatted->formpieces['fields']['action'];
      $innerHTML .= "</td></tr>";
      $innerHTML .= "<tr><td colspan=3>";
      $innerHTML .= "<b>Apply Threshold?:</b> " . $formatted->formpieces['fields']['threshold_operator'];
      $innerHTML .= " | <b>Threshold Value:</b> " . $formatted->formpieces['fields']['threshold'];
      $innerHTML .= "</td></tr>";
      $innerHTML .= "</table>";
      return $innerHTML;
   }
   
   function showFormBody($formatted,$formname, $disabled = 0) {
      $innerHTML = '';
      //$innerHTML .= "<b>Child Query:</b> $this->sql <br>";
      //$innerHTML .= print_r($this->proplist,1) . "<br>";
      $innerHTML .= "<b>Children:</b> <br>";
      //$innerHTML .= "<div id='water_users' style='border: 1px solid rgb(0 , 0, 0); border-style: dotted; overflow: auto; height: 120px;' > ";
      
      $this->listobject->tablename = '';
      $this->listobject->show = 0;
      $allyears = array();
      $this->listobject->queryrecords = $this->proplist;
      $this->listobject->showList();
      $innerHTML .= $this->listobject->outstring;
      return $innerHTML;
   }
   
   function assembleChildQuery() {
      $this->request_properties[] = 'id1';
      $this->request_properties[] = 'waterusetype';
      $this->request_properties[] = 'historic_annual:matrix';
      $this->request_properties[] = 'current_mgy:equation';
      $this->request_properties[] = 'action';
      $this->request_properties[] = 'wdtype';
      $this->request_properties[] = 'waterusetype';
      $this->request_properties[] = 'vpdes_permitno';
      $this->request_properties[] = 'vdh_pwsid';
      $this->request_properties[] = 'vwp_permit';
      $this->request_properties[] = 'max_wd_annual';
      $this->request_properties[] = 'max_wd_daily';
      $this->request_properties[] = 'system';
      $this->request_properties[] = 'ownname';
      $this->request_properties[] = 'source';
      $this->request_properties[] = 'name';
      $this->keeplist['waterusetype'] = array('localvar'=>'waterusetype', 'op'=>'eq');
      $this->keeplist['action'] = array('localvar'=>'action', 'op'=>'eq');
      $this->keeplist['wdtype'] = array('localvar'=>'wdtype', 'op'=>'eq');
      $this->keeplist['current_mgy'] = array('localvar'=>'threshold', 'op'=>$this->threshold_operator);
      parent::assembleChildQuery();
   }
   
   function showElementInfo($propname = '', $view = 'info', $params = array()) {
      $localviews = array('80-B.1-3','80-B.6','80-B.7','80-B.8');
      $output = '';
      //error_log("$this->name showElementInfo called with view = '$view' and propname '$propname' ");
      if (trim($propname) == '') {
         if (in_array(trim($view), $localviews)) {
            switch (trim($view)) {
               case '80-B.1-3':
               //error_log("calling showCWSInfoView () ");
               $output .= $this->showCWSInfoView($propname);
               //$output .= $this->showFormBody(array(),'');
               //error_log("done showCWSInfoView () ");
               break;
               
               case '80-B.6':
               //error_log("calling showNonAgSSUInfoView () ");
               $output .= $this->showNonAgSSUInfoView($propname);
               //$output .= $this->showFormBody(array(),'');
               //error_log("done showNonAgSSUInfoView () ");
               break;
               
               case '80-B.8':
               // same info for small self-supplied users
               $output .= $this->showNonAgSSUInfoView($propname);
               //error_log("done 80-B.8 - > showNonAgSSUInfoView () ");
               break;
               
               case '80-B.7':
               //error_log("calling showNonAgSSUInfoView () ");
               $output .= $this->showNonAgSSUInfoView($propname);
               //$output .= $this->showFormBody(array(),'');
               //error_log("done showNonAgSSUInfoView () ");
               break;
               
               case '80.locality_info':
               //error_log("calling showLocalityInfoView () ");
               $output .= $this->showLocalityInfoView($propname);
               //error_log("done showLocalityInfoView () ");
               break;

            }
         } else {
            $output .= parent::showElementInfo($propname, $view, $params);
         }
      }
      return $output;
   }
   
   function showCWSInfoView() {
   
      $innerHTML = '';
      $columns = array('vdh_pwsid', 'userid', 'name', 'source', 'population', 'connections', 'current_mgd', 'max_wd_daily', 'current_mgy', 'comments');
      $outrecs = array();
      foreach ($this->proplist as $key => $props) {
         $thisrec = array();
         $current_mgd = floatval($props['current_mgy']) / 365.0;
         $userid = $props['id1'];
         foreach ($columns as $thiscol) {
            $propval = '';
            if (isset($props[$thiscol])) {
               $propval = $props[$thiscol];
            }
            if ($thiscol == 'current_mgd') {
               $propval = $current_mgd;
            }
            if ($thiscol == 'userid') {
               $propval = $userid;
            }
            $thisrec[$thiscol] = $propval;
         }
         $outrecs[] = $thisrec;
      }
      $this->listobject->queryrecords = $outrecs;
      $this->listobject->show = 0;
      $this->listobject->showList();
      $innerHTML .= $this->listobject->outstring;
      
      //error_log("showCWSInfoView ()Returning $innerHTML from props: " . print_r($this->proplist,1));
      return $innerHTML;
   }
   
   function showLocalityInfoView() {
   
      $innerHTML = '';
      $columns = array('locality', 'population', 'pop_per_hh', 'num_res_wells', 'num_bus_wells', 'per_capita_gpd');
      $outrecs = array();
      foreach ($this->proplist as $key => $props) {
         $thisrec = array();
         foreach ($columns as $thiscol) {
            $propval = '';
            if (isset($props[$thiscol])) {
               $propval = $props[$thiscol];
            }
            $thisrec[$thiscol] = $propval;
         }
         $outrecs[] = $thisrec;
      }
      $this->listobject->queryrecords = $outrecs;
      $this->listobject->show = 0;
      $this->listobject->showList();
      $innerHTML .= $this->listobject->outstring;
      
      //error_log("showCWSInfoView ()Returning $innerHTML from props: " . print_r($this->proplist,1));
      return $innerHTML;
   }
   
   function showNonAgSSUInfoView() {
   
      $innerHTML = '';
      $columns = array('name', 'source', 'waterusetype', 'use_category', 'current_mgd', 'max_wd_daily', 'max_permit_mgd', 'future_mgd', 'comments');
      $outrecs = array();
      foreach ($this->proplist as $key => $props) {
         $thisrec = array();
         $subs = array();
         $subs['current_mgd'] = floatval($props['current_mgy']) / 365.0;
         $subs['future_mgd'] = floatval($props['max_wd_annual']) / 365.0;
         //error_log("Custom Value Array: " . print_r($subs,1));
         $userid = $props['id1'];
         foreach ($columns as $thiscol) {
            $propval = '';
            if (in_array($thiscol, array_keys($subs))) {
               $propval = $subs[$thiscol];
               //error_log("Applying custom value $propval for $thiscol ");
            } else {
               if (isset($props[$thiscol])) {
                  $propval = $props[$thiscol];
               }
            }
            $thisrec[$thiscol] = $propval;
         }
         $outrecs[] = $thisrec;
      }
      $this->listobject->queryrecords = $outrecs;
      $this->listobject->show = 0;
      $this->listobject->showList();
      $innerHTML .= $this->listobject->outstring;
      
      //error_log("showNonAgSSUInfoView ()Returning $innerHTML from props: " . print_r($this->proplist,1));
      return $innerHTML;
   }
}



class vwudsUserGroup extends dynamicWaterUsers {

   var $use_spatial = 0; // turns off the containment query
   var $userids = '';
   var $mpids = '';
   var $custom2 = '';

   function wake() {
      $this->custom2 = explode(',', $this->userids);
      $this->addParams(array('custom2','waterusetype'));
      parent::wake();
   }
       
   function sleep() {
      $this->custom2 = join(',', $this->custom2);
      parent::sleep();
   }
   
   function showEditForm($formname, $disabled=0) {
      //error_log("calling SHOWEDITFORM");
      if (is_array($this->custom2)) {
         $this->custom2 = join(',', $this->custom2);
         
      }
      //error_log("Setting custom2 = $this->custom2 ");
      $innerHTML = parent::showEditForm($formname, $disabled);
      return $innerHTML;
   }
   
   function showFormHeader($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . " | ";
      $innerHTML .= "<b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'] . " | ";
      $innerHTML .= "<b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description']; 
      $innerHTML .= " | <b>Scenario:</b> " . $formatted->formpieces['fields']['scenarioid'] . "<br>";
      $innerHTML .= "<b>VWUDS UserID (csv):</b> " . $formatted->formpieces['fields']['userids'] . "<br>";
      $innerHTML .= "<b>Source Type:</b> " . $formatted->formpieces['fields']['wdtype']; 
      $innerHTML .= " | <b>Action:</b> " . $formatted->formpieces['fields']['action'];
      $innerHTML .= " | <b>Use Type:</b> " . $formatted->formpieces['fields']['waterusetype'] . "<br>";
      $innerHTML .= "<b>Apply Threshold?:</b> " . $formatted->formpieces['fields']['threshold_operator'];
      $innerHTML .= " | <b>Threshold Value:</b> " . $formatted->formpieces['fields']['threshold'];
      return $innerHTML;
   }

}


class wsp_flowby extends modelSubObject {
  var $json2d = TRUE;
   // this is a general purpose class for a simple flow-by
   // other, more complicated flow-bys will inherit this class
   
   var $enable_conservation = 0;
   var $cc_watch = 0.05;
   var $cc_warning = 0.1;
   var $cc_emergency = 0.15;
   var $custom_conservation = 0;
   var $custom_cons_var = '';
   var $enable_cfb = 0; // cfb = Conditional Flow By (like, the calculated flowby OR inflow whichever is less
   var $cfb_condition = 'lt';
   var $cfb_var = '';
   var $flowby_value = 0.0;
   var $flowby_eqn = 0.0;
   var $name = 'flowby';
   var $value_dbcolumntype = 'float8';
   var $serialist = 'wvars';
   var $loggable = 1; // can log the value in a data table

   function setDataColumnTypes() {
      parent::setDataColumnTypes();
	  if (is_object($this->parentobject)) {
         $this->parentobject->setSingleDataColumnType($this->name, 'float8', 0.0);
      }
   }

   function showEditForm($formname, $disabled=0) {
      $returnInfo = array();
      $returnInfo['name'] = $this->name;
      $returnInfo['description'] = $this->description;
      $returnInfo['debug'] = '';
      $returnInfo['elemtype'] = get_class($this);
      $returnInfo['innerHTML'] = '';
      if (is_object($this->listobject)) {


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
         $innerHTML .= $this->showFormFlowby($formatted,$formname, $disabled );
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormFooter($formatted,$formname, $disabled );
         $innerHTML .= "</tr></table>";
         
         // show the formatted matrix
         //$this->formatMatrix();
         //$innerHTML .= print_r($this->matrix_formatted,1) . "<br>";

         $returnInfo['innerHTML'] = $innerHTML;
      }
      return $returnInfo;
   }
   
   function init() {
      parent::init();
      $flow_eqn = new Equation;
      $flow_eqn->equation = $this->flowby_eqn;
      $flow_eqn->nonnegative = 1;
      $flow_eqn->debug = $this->debug;
      $flow_eqn->parseOperands();
      $this->addOperator('flowby_calc', $flow_eqn, 0);
      $this->vars = array_unique(array_merge($this->vars, $flow_eqn->vars));
   }
   
   function step() {
      parent::step();
      // is conservation enabled?
      // if so, which conservation mode (local or custom defined)
      // is this an either/or flow-by?
      // if so, make either or comparison and decide
      // set property $this->state['flowby_value'];
   }
   
   function evaluate() {
      
      // flowby has already been set
      $this->state['flowby_values'] = $this->state['flowby_calc'];
      $this->result = $this->state['flowby_calc'];
      if ($this->debug) {
         $this->logDebug("Checking to see if extra condition is enabled: $this->enable_cfb <br>");
      }
      if ($this->enable_cfb <> 0) {
         $this->evaluateExtraCondition();
      }
      
   }
   
   function evaluateExtraCondition() {
      $flowby = $this->result;
      if ($this->debug) {
         $this->logDebug("Evaluating additional flowby condition flowby $this->cfb_condition $this->cfb_var <br>");
      }
      if (isset($this->arData[$this->cfb_var])) {
         $cfb_val = $this->arData[$this->cfb_var];
         if ($this->debug) {
            $this->logDebug("Conditional variable value = $cfb_val, prelim flowby value = $flowby <br>");
         }
         switch ($this->cfb_condition) {
            case 'lt':
            // calculated flowby or $cfb_val whichever is SMALLER
            if ($flowby > $cfb_val) {
               if ($this->debug) {
                  $this->logDebug("Condition met: $flowby > $cfb_val <br>");
               }
               $flowby = $cfb_val;
            }
            break;

            case 'gt':
            // calculated flowby or $cfb_val whichever is LARGER
            if ($flowby < $cfb_val) {
               if ($this->debug) {
                  $this->logDebug("Condition met: $flowby < $cfb_val <br>");
               }
               $flowby = $cfb_val;
            }
            break;
         }
      } else {
         if ($this->debug) {
            $this->logDebug("Condtional variable $this->cfb_var not found in " . print_r(array_keys($this->arData),1) . " <br>");
         }
      }
      $this->result = $flowby;
   }
   
   function showFormHeader($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . " | ";
      $innerHTML .= "<b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'] . " | ";
      $innerHTML .= "<b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'];
      return $innerHTML;
   }
   
   function showFormFlowby($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<b>Flow-by Value:</b> " . $formatted->formpieces['fields']['flowby_eqn'];
      return $innerHTML;
   }
   
   function showFormFooter($formatted,$formname, $disabled = 0) {
      $innerHTML = '';
      
      $innerHTML .= $formatted->formpieces['fields']['enable_cfb'];
      $innerHTML .= " Set flowby to " . $formatted->formpieces['fields']['cfb_var'];
      $innerHTML .= " if " . $formatted->formpieces['fields']['cfb_condition'];
      $innerHTML .= " calculated flow-by <br>";
      return $innerHTML;
   }
   
}

class wsp_demand_flowby extends wsp_flowby {
   // combines a demand and a flow-by into a single entity
   // allows for make-up pumping and calculation of deficit
   // uses most plumbing from the standard flow by, but then adds a withdrawal equation
   // and gives the option of over-riding the withdrawal value if available is less than demand
   // and also to increase current value if a deficit exists
   // sets values on parent name_wd, name_flowby, and name_deficit, name_def_pd (deficit period after which the cumulative deficit is discarded)
   
   // maybe just add a section to the wsp_demand object and form to allow flowby consideration?? (and dropdown for flowby variable)
   
   var $demand_eqn = 0;
   var $demand_value = 0;
   var $deficit_pd = 0; // number of seconds to track deficit

}

class wsp_1tierflowby extends wsp_flowby {
   // this is a general purpose class for a simple flow-by
   // other, more complicated flow-bys will inherit this class
   
  var $json2d = TRUE;
  var $enable_conservation = 0;
  var $cc_watch = 0.05;
  var $cc_warning = 0.1;
  var $cc_emergency = 0.15;
  var $custom_conservation = 0;
  var $custom_cons_var = '';
  var $enable_cfb = 0; // cfb = Conditional Flow By (like, the calculated flowby OR inflow whichever is less
  var $cfb_condition = 'lt';
  var $cfb_var = '';
  var $tier_var = ''; // equivalent to keycol1 in matrix
  var $keycol1 = ''; // this is here to allow setting of property via setPropJSON2d() which checks class properties 
  var $lutype1 = 2; 
  var $flowby_value = 0.0;
  var $flowby_eqn = 0.0;
  var $name = 'flowby';
  var $rule_matrix = -1;
  var $matrix = array();
  var $value_dbcolumntype = 'float8';
  var $serialist = 'matrix'; # tells routines to serialize this before storing in XML

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
         
         $innerHTML .= "<table><tr><td>";
         $innerHTML .= $this->showFormHeader($formatted,$formname, $disabled );
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormFlowby($formatted,$formname, $disabled);
         $innerHTML .= $this->rule_matrix->showFormBody($formatted,$formname, $disabled);
         $innerHTML .= "<hr> ";
         //$innerHTML .= "Stored values for matrix " . print_r($this->matrix,1) . "<br>";
         //$innerHTML .= "Matrix object values " . print_r($this->rule_matrix->matrix,1) . "<br>";
         $innerHTML .= $this->showFormFooter($formatted,$formname, $disabled);
         $innerHTML .= "</td></tr></table>";
         
         // show the formatted matrix
         //$this->formatMatrix();
         //$innerHTML .= print_r($this->matrix_formatted,1) . "<br>";

         $returnInfo['innerHTML'] = $innerHTML;

         return $returnInfo;

      }
   }
   
   function wake() {
      parent::wake();
      $this->setupMatrix();
      // set up the matrix for this element
   }
   
   function sleep() {
      parent::sleep();
      // set up the matrix for this element
      unset($this->rule_matrix);
      $this->rule_matrix = -1;
   }
   
   function init() {
      parent::init();
   }
   
   function step() {
      parent::step();
   }
   
   function evaluate() {
      if ($this->debug) {
         $this->logDebug("tier1 flowby evaluate() method called<br>");
      }
      // is conservation enabled?
      // if so, which conservation mode (local or custom defined)
      // is this an either/or flow-by?
      // if so, make either or comparison and decide
      // set property $this->state['flowby_value'];
      // do historic
      $flowby = 0.0;
      if ($this->debug) {
         $this->logDebug("<b>Evaluating Tiered Flow-by: </b> <br>");
      }
      if (isset($this->processors['rule_matrix'])) {
         $rules = $this->processors['rule_matrix']->matrix_rowcol;
         if ($this->debug) {
            $this->logDebug("<b>Evaluating Rules: </b> " . print_r($rules,1) . " <br>");
         }
         $keyval = $this->arData[$this->tier_var];
         $flowby = $this->processors['rule_matrix']->evaluateMatrix($keyval);
         if ($this->debug) {
           $this->logDebug("<b>Flow-by key variable: </b> $this->tier_var, value = $keyval, flowby = $flowby<br>");
           error_log("<b>Flow-by key variable: </b> $this->tier_var, value = $keyval, flowby = $flowby<br>");
         }
      }
      $this->state['rule_matrix'] = $flowby;
      $this->result = $flowby;
      // added to insure continuity
      $this->processors['flowby_calc']->result = $this->state['rule_matrix'];
      $this->state['flowby_calc'] = $this->state['rule_matrix'];
      if ($this->debug) {
         $this->logDebug("Preliminary flowby set to $this->result -- checking if CFB is required <br>");
      }
      if ($this->enable_cfb <> 0) {
         $this->evaluateExtraCondition();
      }
      
   }
   
   function showFormHeader($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . " | ";
      $innerHTML .= "<b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'] . " | ";
      $innerHTML .= "<b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'];
      return $innerHTML;
   }
   
   function showFormFlowby($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<b>Tiered Trigger Variable:</b> " . $formatted->formpieces['fields']['tier_var'];
      $innerHTML .= "<b>Default Value:</b> " . $formatted->formpieces['fields']['defaultval'];
      return $innerHTML;
   }
   
   function showFormFooter($formatted,$formname, $disabled = 0) {
      $innerHTML = '';
      
      $innerHTML .= $formatted->formpieces['fields']['enable_cfb'];
      $innerHTML .= " Set flowby to " . $formatted->formpieces['fields']['cfb_var'];
      $innerHTML .= " if " . $formatted->formpieces['fields']['cfb_condition'];
      $innerHTML .= " calculated flow-by <br>";
      return $innerHTML;
   }
   
   function setProp($propname, $propvalue, $view = '') {
     
     switch ($propname) {
       case 'matrix':
         // handle calls to set the matrix on this object
         // Default behavior is to expect this to be an array that is 1-d, and the object uses numcols to decode it
         //$this->matrix = array('storage','stage','surface_area',0,0,0);
         // check for a valid json object, transform to array
         switch ($view) {
           case 'json-1d':
           $raw_json = $propvalue;
           $propvalue = json_decode($propvalue, TRUE);
           if (is_array($propvalue)) {
             //error_log("setProp() flowby Array located, handling " . print_r($propvalue,1));
             $this->matrix = $propvalue;
           } else {
             error_log("JSON decode failed wih $propvalue for $raw_json");
           }
           break;
           
           default:
           parent::setProp($propname, $propvalue, $view);
           break;
         }
       break;
       
       case 'lutype1':
         $this->lutype1 = $propvalue;
       break;
       
       case 'keycol1':
         $this->tier_var = $propvalue;
       break;
       
       default:
         parent::setProp($propname, $propvalue, $view);
       break;
     }
   }
  
  function setClassProp($propname, $propvalue, $view = '') { 
  // this adds a special handler for items with a "matrix" property.
  // arrays have already been parsed by json2d handler that calls this 
  // so we rearrange as 1-d, implode, and call setupMatrix
    error_log("setClassProp ($propname, $view) wsp_1tierflowby propvalue = ". print_r($propvalue,1));
    switch ($propname) {
      case 'matrix':
        switch ($view) {
          case 'array':
          // this is a simple matrix so needs no extra handling.
            $this->assocArrayToMatrix($propvalue, FALSE);
          break;
          default:
            parent::setClassProp($propname, $propvalue, $view);
          break;
        }
      break;
       
      case 'lutype1':
       $this->lutype1 = $propvalue;
      break;

      case 'keycol1':
       $this->tier_var = $propvalue;
       error_log("====> tier_var = $this->tier_var");
      break;
      
      default:
        parent::setClassProp($propname, $propvalue, $view);
      break;
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
   
  function setupMatrix() {
    $this->rule_matrix = new dataMatrix;
    $this->rule_matrix->name = 'flowby';
    $this->rule_matrix->ebug = $this->debug;
    $this->rule_matrix->wake();
    $this->rule_matrix->numcols = 2;
    $this->rule_matrix->fixed_cols = true;
    $this->rule_matrix->valuetype = 1; // 1 column lookup (col & row)
    $this->rule_matrix->keycol1 = $this->tier_var; // key for 1st lookup variable
    $this->rule_matrix->lutype1 = $this->lutype1; // lookup type, default is - stair step
    // add a row for the header line
    if ( !is_array($this->matrix) or (count($this->matrix) == 0)) {
       $this->matrix = array(0,0);
    }
    $this->rule_matrix->numrows = count($this->matrix) / 2.0;
    $this->rule_matrix->matrix = $this->matrix;// map the text mo to a numerical description
    $this->rule_matrix->formatMatrix();
    $this->addOperator('rule_matrix', $this->rule_matrix, 0);
  }

  function getProp($propname, $view = '') {
    //error_log("DataMatrix Property requested: $propname, $view ");
    $localviews = array('matrix', 'matrix_formatted', 'csv');
    if (!in_array($view, $localviews)) {
       return parent::getProp($propname, $view);
    } else {
       switch ($view) {
          case 'matrix':
             $this->rule_matrix->formatMatrix();
             //error_log("Returning: " . print_r($this->matrix_formatted,1));
             return $this->rule_matrix->matrix_formatted;
          break;
          case 'matrix_formatted':
             $this->rule_matrix->formatMatrix();
             //error_log("Returning: " . print_r($this->matrix_formatted,1));
             return $this->rule_matrix->matrix_formatted;
          break;
      case 'csv':
             $this->rule_matrix->formatMatrix();
             //error_log("calling showCWSInfoView () ");
             //return array2Delimited($this->matrix_rowcol, ',', 1,'unix');
             return array2Delimited($this->rule_matrix->matrix_formatted, ',', 1,'unix');
      break;
       }
    }
  }
}


class wsp_demand extends modelSubObject {
   // this is a general purpose class for a demand with 3-tiered conservation option (can be over-ridden with a custom variable)
   // and calculation of deficit, i.e., amount of demand not met
   
   var $enable_conservation = 'disabled'; // disabled, internal, or custom
   var $cc_watch = 0.05;
   var $cc_warning = 0.1;
   var $cc_emergency = 0.15;
   var $custom_cons_var = '';
   var $status_var = 'drought_status';
   var $name = 'demand_mgd';
   var $demand_eqn = '0';
   var $wvars = array();
   var $serialist = 'wvars';
   var $loggable = 1; // can log the value in a data table

   function setState() {
      parent::setState();
      $this->setStateVar('enable_conservation', $this->enable_conservation);
      $this->wvars = array('baseline','cons_pct');
      
      $this->initOnParent();
   }

   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      foreach ($this->wvars as $thisvar) {
         if ($this->debug) {
            $this->logDebug("Setting $this->name" . "_" . "$thisvar to type float8 on parent.<br>\n");
            error_log("Setting $this->name" . "_" . "$thisvar to type float8 on parent.<br>\n");
         }
         $this->parentobject->setSingleDataColumnType($this->name . "_" . $thisvar, 'float8', 0.0);
         $this->vars[] = $this->name . "_" . $thisvar;
      }
      $this->parentobject->setSingleDataColumnType($this->name, 'float8', 0.0);
   }

   function writeToParent() {
      if (is_object($this->parentobject)) {
         foreach ($this->wvars as $thisvar) {
            $this->parentobject->setStateVar($this->name . "_" . $thisvar, $this->state[$thisvar]);
         }
      }
   }

   function logDebug($debugstring) {
      if (is_object($this->parentobject)) {
         $this->parentobject->logDebug($debugstring);
      }
   }

   function initOnParent() {
      if (is_object($this->parentobject)) {
         foreach ($this->wvars as $thisvar) {
            $this->parentobject->setStateVar($this->name . "_" . $thisvar, $this->state[$thisvar]);
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
         $innerHTML .= $this->showFormHeader($formatted,$formname, $disabled);
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormDemandVars($formatted,$formname, $disabled);
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormFooter($formatted,$formname, $disabled);
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
      $demand_eqn = new Equation;
      $demand_eqn->equation = $this->demand_eqn;
      $demand_eqn->debug = $this->debug;
      $demand_eqn->nonnegative = 1; # Make minimum value = $minvalue
      $demand_eqn->minvalue = 0; # Default Minimum value = 0
      $this->addOperator('demand_calc', $demand_eqn, 0);
   }
   
   function step() {
      parent::step();
      // is conservation enabled?
      // if so, which conservation mode (local or custom defined)
      // is this an either/or flow-by?
      // if so, make either or comparison and decide
      // set property $this->state['flowby_value'];
      //error_log("Conservation var: " . $this->state['enable_conservation']);
      //error_log("Drought status: " . $this->arData[$this->status_var]);
      $this->state['cons_pct'] = $this->calcReduction();
      $this->state['baseline'] = $this->state['demand_calc'];
      $this->result = (1.0 - $this->state['cons_pct']) * $this->state['baseline'];
      $this->writeToParent();
         
   }
   
   function calcReduction() {
      $reduction = 0.0;
      //error_log("Conservation State: " . $this->state['enable_conservation']);
      //error_log("Conservation Property: " . $this->enable_conservation);
      switch ($this->state['enable_conservation']) {
         case 'disabled':
         $reduction = 0;
         break;
         case 'internal':
         switch ($this->arData[$this->status_var]) {
            case '1':
            $pct = floatval($this->cc_watch);
            break;
            case '2':
            $pct = floatval($this->cc_warning);
            break;
            case '3':
            $pct = floatval($this->cc_emergency);
            break;
            
            default:
            $pct = 0.0;
            break;
         }
         $reduction = $pct;
      //error_log("Internal cons var: " . $reduction);
         break;
         case 'custom':
         $reduction = $this->state[$this->custom_cons_var];
      //error_log("Custom cons var: " . $reduction);
         break;
         /*
         default:
         switch ($this->arData[$this->status_var]) {
            case '1':
            $pct = floatval($this->cc_watch);
            break;
            case '2':
            $pct = floatval($this->cc_warning);
            break;
            case '3':
            $pct = floatval($this->cc_emergency);
            break;
            
            default:
            $pct = 0.0;
            break;
         }
         $reduction = $pct;
      //error_log("Internal cons var: " . $reduction);
         break;
         */
      }
      
      return $reduction;
   }
   
   function showFormHeader($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . " | ";
      $innerHTML .= "<b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'] . " | ";
      $innerHTML .= "<b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'] . "<BR>";
      $innerHTML .= "<b>Variable Reporting Drought Status:</b> " . $formatted->formpieces['fields']['status_var'];
      return $innerHTML;
   }
   
   function showFormDemandVars($formatted,$formname, $disabled = 0) {
      $innerHTML = '';
      $innerHTML .= "<b>Demand Baseline:</b> " . $formatted->formpieces['fields']['demand_eqn'] . "(prior to cons. reduction)<BR>";
      return $innerHTML;
   }
   
   function showFormFooter($formatted, $formname, $disabled = 0) {
      $innerHTML = '';
      $cd = '';
      $ci = ''; // internal - standard 3-tiered conservation
      $cc = '';
      switch ($this->enable_conservation) {
         case 'disabled':
         $cd = 'disabled';
         break;
         case 'internal':
         $ci = 'internal';
         break;
         case 'custom':
         $cc = 'custom';
         break;
      }
      $innerHTML .= "Debugging: cd: $cd, ci: $ci, cc: $cc <br>";
      $innerHTML .= showRadioButton('enable_conservation', 'disabled', $cd, '', 1, 0, '');      
      $innerHTML .= " Disable Conservation <br>";
      //$innerHTML .= $formatted->formpieces['fields']['enable_conservation'];
      $innerHTML .= showRadioButton('enable_conservation', 'internal', $ci, '', 1, 0, '');
      $innerHTML .= " Enable Conservation, % reduction Watch: " . $formatted->formpieces['fields']['cc_watch'];
      $innerHTML .= ", Warn: " . $formatted->formpieces['fields']['cc_warning'];
      $innerHTML .= ", Emerg: " . $formatted->formpieces['fields']['cc_emergency'] . "<BR>";
      //$innerHTML .= $formatted->formpieces['fields']['custom_conservation'];
      $innerHTML .= showRadioButton('enable_conservation', 'custom', $cc, '', 1, 0, '');
      $innerHTML .= " Use Custom Conservation Variable " . $formatted->formpieces['fields']['custom_cons_var'];
      return $innerHTML;
   }
   
}


class wsp_conservation extends modelSubObject {
   // this is a general purpose class for a simple flow-by
   // other, more complicated flow-bys will inherit this class
   
   var $enable_conservation = 'disabled'; // disabled, internal, or custom
   var $cc_watch = 0.05;
   var $cc_warning = 0.1;
   var $cc_emergency = 0.15;
   var $custom_cons_var = '';
   var $status_var = 'drought_status';
   var $name = 'conservation';
   

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
         $innerHTML .= $this->showFormHeader($formatted,$formname, $disabled);
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormFlowby($formatted,$formname, $disabled);
         $innerHTML .= "<hr> ";
         $innerHTML .= $this->showFormFooter($formatted,$formname, $disabled);
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
   }
   
   function step() {
      parent::step();
      // is conservation enabled?
      // if so, which conservation mode (local or custom defined)
      // is this an either/or flow-by?
      // if so, make either or comparison and decide
      // set property $this->state['flowby_value'];
      switch ($this->state['enable_conservation']) {
         case 'disabled':
         $reduction = 0;
         break;
         case 'internal':
         switch ($this->state[$this->status_var]) {
            case '1':
            $pct = floatval($this->cc_warning);
            break;
            case '2':
            $pct = floatval($this->cc_watch);
            break;
            case '3':
            $pct = floatval($this->cc_emergency);
            break;
            
            default:
            $pct = 0.0;
            break;
         }
         $reduction = $pct;
         break;
         case 'custom':
         $reduction = $this->state[$this->custom_cons_var];
         break;
      }
         
   }
   
   function evaluate() {
      
      // flowby has already been set
      $this->result = $this->state['reduction'];
      
   }
   
   function showFormHeader($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<b>Name:</b> " . $formatted->formpieces['fields']['name'] . " | ";
      $innerHTML .= "<b>Debug Mode?:</b> " . $formatted->formpieces['fields']['debug'] . " | ";
      $innerHTML .= "<b>Exec. Rank:</b> " . $formatted->formpieces['fields']['exec_hierarch'] . "<BR>";
      $innerHTML .= "<b>Description:</b> " . $formatted->formpieces['fields']['description'];
      return $innerHTML;
   }
   
   function showFormFlowby($formatted,$formname, $disabled = 0) {
      $innerHTML .= "<b>Variable Reporting Drought Status:</b> " . $formatted->formpieces['fields']['status_var'];
      return $innerHTML;
   }
   
   function showFormFooter($formatted, $formname, $disabled = 0) {
      $innerHTML = '';
      $cd = '';
      $ci = ''; // internal - standard 3-tiered conservation
      $cc = '';
      switch ($this->enable_conservation) {
         case 'disabled':
         $cd = 'disabled';
         break;
         case 'internal':
         $ci = 'internal';
         break;
         case 'custom':
         $cc = 'custom';
         break;
      }

      $innerHTML .= showRadioButton('enable_conservation', 'disabled', $cd, '', 1, 0, '');      
      $innerHTML .= " Disable Conservation <br>";
      //$innerHTML .= $formatted->formpieces['fields']['enable_conservation'];
      $innerHTML .= showRadioButton('enable_conservation', 'internal', $ci, '', 1, 0, '');
      $innerHTML .= " Enable Conservation, % reduction Watch: " . $formatted->formpieces['fields']['cc_watch'];
      $innerHTML .= ", Warn: " . $formatted->formpieces['fields']['cc_warning'];
      $innerHTML .= ", Emerg: " . $formatted->formpieces['fields']['cc_emergency'] . "<BR>";
      //$innerHTML .= $formatted->formpieces['fields']['custom_conservation'];
      $innerHTML .= showRadioButton('enable_conservation', 'custom', $cc, '', 1, 0, '');
      $innerHTML .= " Use Custom Conservation Variable " . $formatted->formpieces['fields']['custom_cons_var'];
      return $innerHTML;
   }
   
}


class wsp_PopBasedProjection extends waterSupplyModelNode {

}

class wsp_PopBasedProjection_VAWC extends wsp_PopBasedProjection {
   // needs linkage to the populations in question
   var $basedata = 'http://deq1.bse.vt.edu/data/proj3/components/vawc/vawc_1.2012.csv';
   var $poptable = array();
   var $fips = '';
   var $value_dbcolumntype = 'float8';
   var $yearvar = '';
   
   function create() {
      // load base data file
      // create a matrix to hold it
      // filegetcontents
      // csv to array
      $this->loadRemotePopData();
   }
   
   function loadRemotePopData() {
      $this->logError("Trying to Load Data from $this->basedata <br>");
      $initdata = readDelimitedFile($this->basedata, ',', 1);
      if (count($initdata) > 0) {
         $this->poptable = $initdata;
      }
   }
   
   function wake() {
      parent::wake();
      $this->appendSerialList('poptable');
      // create a matric from the poptable variable
      if ($this->debug) {
         $this->logDebug("Pop Data Array has " . count($this->poptable) . " <br>");
      }
      $this->loadRemotePopData();
      if ($this->debug) {
         $this->logDebug("Pop Data Array has " . count($this->poptable) . " <br>");
      }
      $this->setPopMatrix($this->poptable);
   }
   
   function sleep() {
      parent::sleep();
      // set up the matrix for this element
   }
   
   function step() {
      parent::step();
      // is conservation enabled?
      // if so, which conservation mode (local or custom defined)
      // is this an either/or flow-by?
      // if so, make either or comparison and decide
      // set property $this->state['flowby_value'];
      // do historic
      $flowby = 0.0;
      if ($this->debug) {
         $this->logDebug("<b>Evaluating Population Projection: </b> <br>");
      }
      $pop = 0;
      if (isset($this->processors['popmatrix'])) {
         foreach (split(',', $this->fips) as $thisfips) {
         

            if ($this->debug) {
               $popm = $this->processors['popmatrix']->matrix_rowcol;
               $this->logDebug("<b>Evaluating Pop: </b> " . print_r($popm,1) . " <br>");
            }
            $yearval = $this->arData[$this->yearvar];
            $thispop = $this->processors['popmatrix']->evaluateMatrix(trim($thisfips), $yearval);
            $pop += $thispop;
            if ($this->debug) {
               $this->logDebug("<b>popmatrix key variable: </b> $thisfips - $this->tier_var, value = $yearval, pop = $thispop (total = $pop)<br>");
            }
         }
      }
      $this->state['popmatrix'] = $pop;
      $this->result = $pop;
   }
   
   function evaluate() {
    /*  
      // flowby has already been set
      $this->result = $this->state['rule_matrix'];
      // added to insure continuity
      $this->processors['flowby_calc']->result = $this->state['rule_matrix'];
      $this->state['flowby_calc'] = $this->state['rule_matrix'];
      if ($this->enable_cfb <> 0) {
         $this->evaluateExtraCondition();
      }
    */
   }
   
   function appendSerialList($varname) {
      $sl = explode(',', $this->serialist);
      if (!in_array(trim($varname), $sl)) {
         $sl[] = trim($varname);
         $this->serialist = join(',', $sl);
      }
   }
   
   function setPopMatrix($poparray) {
      $popmatrix = new dataMatrix;
      $popmatrix->name = 'popmatrix';
      $popmatrix->wake();
      $popmatrix->lutype1 = 0; // exact match on fips
      $popmatrix->lutype2 = 1; // linear interpolation on pop
      $popmatrix->valuetype = 2;
      $popmatrix->debug = $this->debug;
      $popmatrix->assocArrayToMatrix($poparray);
      $this->addOperator('popmatrix', $popmatrix, 0);
      $innerHTML .= "Added operator popmatrix <br>";
      $retarr = array();
      $retarr['innerHTML'] = $innerHTML;
      return $retarr;
   
   }
}

class wsp_LUBasedProjection extends waterSupplyModelNode {
   // 
   
   
   function create() {
      parent::create();
      $innerHTML = '';
      // should create base sub-components for its class, which are:
      //    Land-Use definition grid (ludef):
      //       Land Use Name  | MGD/unit area
      //    Land-Use time series (luts)
      // these should have read-only names, since the names will not be allowed to change
      // add this component
      $ludef = new dataMatrix;
      $ludef->name = 'ludef';
      $ludef->wake();
      $ludef->numcols = 2;
      $ludef->numrows = 3;
      $ludef->matrix = array(0,0,0,0,0,0);
      $this->addOperator('ludef', $ludef, 0);
      $innerHTML .= "Added operator ludef <br>";
      $luts = new dataMatrix;
      $luts->name = 'luts';
      $luts->wake();
      $luts->numcols = 3;
      $luts->numrows = 3;
      $luts->matrix = array(0,0,0,0,0,0,0,0,0);
      $this->addOperator('luts', $luts, 0);
      $innerHTML .= "Added operator luts <br>";
      $retarr = array();
      $retarr['innerHTML'] = $innerHTML;
      return $retarr;
   }
   
}

class wsp_VWUDSData extends XMLDataConnection {
   // time series for withdrawal data, based on objects geometry
   var $id1 = ''; # USER ID
   var $historic_monthly = array();
   var $historic_annual = array();
   var $projected_monthly = array();
   var $projected_annual = array();
   var $maxuse_monthly = array();
   var $maxuse_annual = array();
   var $usetypes = array();
   var $historic_mgd = 0.0;
   var $current_mgd = 0.0;
   var $feed_address = 'http://deq1.bse.vt.edu/om/remote/xml_getdata_wateruse.php';
   var $data_inventory_address = 'http://deq1.bse.vt.edu/om/remote/xml_getdata_wateruse.php';
   var $datecolumn = 'thisdate';
   var $lon_col = 'lon_dd';
   var $lat_col = 'lat_dd';
   var $serialist = 'historic_monthly,historic_annual,projected_monthly,projected_annual,maxuse_monthly,maxuse_annual,usetypes';
   
   function wake() {
      $this->feed_address = 'http://deq1.bse.vt.edu/om/remote/xml_getdata_wateruse.php';
      $this->data_inventory_address = 'http://deq1.bse.vt.edu/om/remote/xml_getdata_wateruse.php';
      parent::wake();
      $this->prop_desc['historic_mgd'] = 'Historic Demand (mgd).';
      $this->prop_desc['current_mgd'] = 'Current Demand (mgd).';
   }
   
   function init() {
      parent::init();
      
      // parent routines grab all data, now do summary queries to
      //$this->summarizeWithdrawals();
      
      // grab USGS stations
      
   }
   function setState() {
      parent::setState();
      $this->state['current_mgd'] = 0.0;
      $this->state['historic_mgd'] = 0.0;
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      // add these to the data columns for logging
      $statenums = array('current_mgd','historic_mgd');
      foreach ($statenums as $thiscol) {
         $this->dbcolumntypes[$thiscol] = 'float8';
         $this->data_cols[] = $thiscol;
      }
      
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'current_mgd');
      array_push($publix, 'historic_mgd');

      return $publix;
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
         $this->logDebug("<b>$this->name Sub-processors executed at hour " . $this->state['hour'] . " on " . $this->state['thisdate'] . " week " . $this->state['week'] . " month " . $this->state['month'] . " year " . $this->state['year'] . ".</b><br>\n");
      }
      
      // parent does data aquisition, now calculate demands
      $thismo = $this->state['month'];
      $thisyr = $this->state['year'];
      // do historic
      $hist_demand_mgd = 0.0;
      if (isset($this->processors['historic_annual'])) {
         $hist_d = $this->processors['historic_annual']->matrix_rowcol;
         if ($this->debug) {
            $this->logDebug("<b>Evaluating Historic Demands @ $thisyr, $thismo: </b> " . print_r($hist_d,1) . " <br>");
         }
         for ($i = 1; $i < count($hist_d); $i++) {
            $wd_type = $hist_d[$i][0];
            $demand_historic_mgd = $this->processors['historic_annual']->evaluateMatrix($wd_type,$thisyr);
            $demand_historic_frac = $this->processors['historic_monthly_pct']->evaluateMatrix($wd_type,$thismo);
            $hist_demand_mgd += ($demand_historic_mgd * $demand_historic_frac);
            if ($this->debug) {
               $this->logDebug("Evaluated $wd_type,$thisyr $wd_type,$thismo = $demand_historic_mgd * $demand_historic_frac <br>");
            }
         }
         if ($this->debug) {
            $this->logDebug("Historic demand set to: $hist_demand_mgd <br>");
         }
      }
      $this->state['historic_mgd'] = $hist_demand_mgd;
      // do current
      $curr_demand_mgd = 0.0;
      if (isset($this->processors['current_annual'])) {
         $curr_d = $this->processors['current_annual']->matrix_rowcol;
         if ($this->debug) {
            $this->logDebug("<b>Evaluating Current Demands @ $thisyr, $thismo:</b>" . print_r($curr_d,1) . " <br>");
         }
         for ($i = 1; $i < count($curr_d); $i++) {
            $wd_type = $hist_d[$i][0];
            $demand_curr_mgd = $this->processors['current_annual']->evaluateMatrix($wd_type,$thisyr);
            $demand_curr_frac = $this->processors['current_monthly']->evaluateMatrix($wd_type,$thismo);
            $curr_demand_mgd += ($demand_curr_mgd * $demand_curr_frac);
            if ($this->debug) {
               $this->logDebug("Evaluated $wd_type,$thisyr $wd_type,$thismo = $demand_curr_mgd * $demand_curr_frac <br>");
            }
         }
         if ($this->debug) {
            $this->logDebug("Current demand set to: $curr_demand_mgd <br>");
         }
      }
      $this->state['current_mgd'] = $curr_demand_mgd;
      $this->postStep();
      
   }
   
   function create() {
      parent::create();
      // set default land use
      // set basic data query
      $this->logDebug("Create() function called <br>");
      // add use types
      $this->getHistoricUse();
      $this->addUseTypeComponent();
      $this->addHistoricUseComponent();
      $this->logDebug("Processors on this object: " . print_r(array_keys($this->processors),1) . " <br>");
   }
   
   function addUseTypeComponent() {
      if (isset($this->processors['usetypes'])) {
         unset($this->processors['usetypes']);
      }
      // landuse subcomponent to allow users to simulate land use values
      $usedef = new dataMatrix;
      $usedef->name = 'usetypes';
      $usedef->wake();
      $usedef->numcols = count($this->usetypes[0]);  
      $usedef->valuetype = 2; // 2 column lookup (col & row)
      $usedef->keycol1 = ''; // key for 1st lookup variable
      $usedef->lutype1 = 0; // lookup type - exact match for land use name
      $usedef->keycol2 = ''; // key for 2nd lookup variable
      $usedef->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line
      $usedef->numrows = count($this->usetypes) + 1;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      foreach (array_keys($this->usetypes[0]) as $thisvar) {
         $usedef->matrix[] = $thisvar;
      }
      // now add the individual records
      foreach ($this->usetypes as $thistype) {
         foreach ($thistype as $thisvar) {
            $usedef->matrix[] = $thisvar;
         }
      }
      $this->logDebug("Trying to add use type sub-component matrix with values: " . print_r($usedef->matrix,1) . " <br>");
      $this->addOperator('usetypes', $usedef, 0);
   }
   
   function addHistoricUseComponent() {
      if (isset($this->processors['historic_monthly'])) {
         unset($this->processors['historic_monthly']);
      }
      // historic percent subcomponent 
      $usedef = new dataMatrix;
      $usedef->name = 'historic_monthly';
      $usedef->description = 'Historic average monthly percent of annual use';
      $usedef->wake();
      $usedef->numcols = count($this->historic_monthly[0]);  
      $usedef->valuetype = 2; // 2 column lookup (col & row)
      $usedef->keycol1 = ''; // key for 1st lookup variable
      $usedef->lutype1 = 0; // lookp type - exact match for land use name
      $usedef->keycol2 = ''; // key for 2nd lookup variable
      $usedef->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line
      $usedef->numrows = count($this->historic_monthly) + 1;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      foreach (array_keys($this->historic_monthly[0]) as $thisvar) {
         $usedef->matrix[] = $thisvar;
      }
      // now add the individual records
      foreach ($this->historic_monthly as $thistype) {
         foreach ($thistype as $thisvar) {
            $usedef->matrix[] = $thisvar;
         }
      }
      $this->logDebug("Trying to add use type sub-component matrix with values: " . print_r($usedef->matrix,1) . " <br>");
      $this->addOperator('historic_monthly', $usedef, 0);
      
      
      // historic Total use subcomponent 
      $usedef = new dataMatrix;
      $usedef->name = 'historic_annual';
      $usedef->description = 'Historic annual use in MG per year';
      $usedef->wake();
      $usedef->numcols = count($this->historic_annual[0]);  
      $usedef->valuetype = 2; // 2 column lookup (col & row)
      $usedef->keycol1 = ''; // key for 1st lookup variable
      $usedef->lutype1 = 1; // lookp type - stair step
      // add a row for the header line
      $usedef->numrows = count($this->historic_annual) + 1;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      foreach (array_keys($this->historic_annual[0]) as $thisvar) {
         $usedef->matrix[] = $thisvar;
      }
      // now add the individual records
      foreach ($this->historic_annual as $thistype) {
         foreach ($thistype as $thisvar) {
            $usedef->matrix[] = $thisvar;
         }
      }
      $this->logDebug("Trying to add use type sub-component matrix with values: " . print_r($usedef->matrix,1) . " <br>");
      $this->addOperator('historic_annual', $usedef, 0);
   }
   
   
   function getTablesColumns() {
      parent::getTablesColumns();
   }
   
   function getHistoricUse() {
      $this->historic_monthly = array();
      $this->historic_annual = array();
      $this->projected_monthly = array();
      $this->projected_annual = array();
      $this->maxuse_monthly = array();
      $this->maxuse_annual = array();
      $this->usetypes = array();
      if (function_exists('fetch_rss')) {
         // this rss feed should return a single record with descriptive information
         if ($this->debug) {
            $this->logDebug("Starting Data Inventory Address: $this->data_inventory_address <br>\n");
         }
         if (strlen(rtrim(ltrim($this->final_data_inventory_address))) > 0) {
            // actiontype=2 - get the use types and consumptive factors
            $usetype_var = "actiontype=2";
            $iurl = $this->appendExtrasToURL($this->final_data_inventory_address, $usetype_var);
            $rss = fetch_rss($iurl);
            #print_r($rss->items);
            $this->usetypes = $rss->items;
            
            // actiontype=3 - get the historical use monthly patterns
            $hist_var = "actiontype=3";
            if (strlen($this->id1) > 0) {
               $hist_var .= '&id1=' . $this->id1;
            } else {
               // we don't do the geom query if the userid is set
               if (strlen($this->the_geom) > 0) {
                  $hist_var .= '&the_geom=' . urlencode($this->the_geom);
               }
            }
            $hurl = $this->appendExtrasToURL($this->final_data_inventory_address, $hist_var);
            if ($this->debug) {
               $this->logDebug("Historic Monthly URL add-ons: $iurl <br>\n");
            }
            define('MAGPIE_CACHE_ON', TRUE);
            //$mrss = fetch_rss($iurl);
            $mrss = simplexml_load_file($hurl);
            $linklist = $mrss->channel->item;
            $ignore_fields = array('title', 'link', 'description');
            $rename_fields = array('jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12);
            $historic_monthly = $this->arrayRenameOmit($linklist, $ignore_fields, $rename_fields);
            // now, calculate the monthly percentages from this
            foreach ($historic_monthly as $thismo) {
               $total = $thismo[1] + $thismo[2] + $thismo[3] + $thismo[4] + $thismo[5] + $thismo[6] + $thismo[7] + $thismo[8] + $thismo[9] + $thismo[10] + $thismo[11] + $thismo[12];
               if ($total == 0) {
                  for ($i = 1; $i <= 12; $i++) {
                     $thismo[$i] = 0.0833;
                  }
               } else {
                  for ($i = 1; $i <= 12; $i++) {
                     $thismo[$i] = number_format($thismo[$i] / $total,4);
                  }
               }
               $this->historic_monthly[] = $thismo;
            }
               
            if ($this->debug) {
               $this->logDebug("Historic Monthly records: " . print_r($this->historic_monthly,1) . " <br>\n");
            }
            if ($this->debug) {
               $hcount = count($this->historic_monthly);
               $this->logDebug("Added $hcount Historic Monthly records <br>\n");
            }
            
            // actiontype=4 - get the historical use annual values
            $hist_var = "actiontype=4";
            if (strlen($this->id1) > 0) {
               $hist_var .= '&id1=' . $this->id1;
            } else {
               // we don't do the geom query if the userid is set
               if (strlen($this->the_geom) > 0) {
                  $hist_var .= '&the_geom=' . urlencode($this->the_geom);
               }
            }
            $yurl = $this->appendExtrasToURL($this->final_data_inventory_address, $hist_var);
            if ($this->debug) {
               $this->logDebug("Historic Annual URL add-ons: $yurl <br>\n");
            }
            define('MAGPIE_CACHE_ON', TRUE);
            $mrss = simplexml_load_file($yurl);
            $linklist = $mrss->channel->item;
            $ignore_fields = array('title', 'link', 'description');
            $rename_fields = array();
            $firstrec = (array)$linklist[0];
            foreach (array_keys($firstrec) as $thiscol) {
               if ($this->debug) {
                  $this->logDebug("Checking Column Name: $thiscol<br>\n");
               }
               $pos = strpos($thiscol, 'thisyear');
               if (!($pos === false)) {
                  $rename_fields[$thiscol] = str_replace('thisyear','',$thiscol);
               }
            }
            if ($this->debug) {
               $this->logDebug("Replacing Year Names: " . print_r($rename_fields,1) . " <br>\n");
            }
            $this->historic_annual = $this->arrayRenameOmit($linklist, $ignore_fields, $rename_fields);
         }
      } else {
         $this->logError("Error Retrieving Column Names: RSS Magpie function 'fetch_rss' is not defined - can not retrieve feed.");
      }
   }
   
   function summarizeWithdrawals() {
      // This is no longer valid since this is now an XML connection and the tables and dbconn's are not set up
      
      /*
      // parent routines grab all data, now do summary queries to
      // the following summaries should be generated by the XML object:
      // historic annual totals, by year and use_type (row - type, column - year)
      $this->dbobj->querystring = "  select a.cat_mp, sum(a.max_annual) as max_annual, ";
      $this->dbobj->querystring .= "    sum(a.max_annual/365.0) as max_mgd ";
      $this->dbobj->querystring .= " FROM $dbt as a, vwuds_max_withdrawal as b ";
      $this->dbobj->querystring .= " where a.mpid = b.mpid ";
      // currently, the vwuds_max withdrawal table holds only that, withdrawals, but at some point
      // it, or a table that would be more properly known as vwuds_max_annual would contain 
      // the data pertaining to transfers and the like
      $this->dbobj->querystring .= " group by a.cat_mp ";
      if ($this->debug) {
         $this->logDebug("$this->dbobj->querystring ; <br>");
      }
      $this->dbobj->performQuery();
      // historica monthly mean percent of annual by use_type (row - type, column - month)
      // update other components, such as the summary data, and the category multipliers
      $dbt = $this->dbtable;
      $this->dbobj->querystring = "  select a.cat_mp, sum(a.max_annual) as max_annual, ";
      $this->dbobj->querystring .= "    sum(a.max_annual/365.0) as max_mgd ";
      $this->dbobj->querystring .= " FROM $dbt as a, vwuds_max_withdrawal as b ";
      $this->dbobj->querystring .= " where a.mpid = b.mpid ";
      // currently, the vwuds_max withdrawal table holds only that, withdrawals, but at some point
      // it, or a table that would be more properly known as vwuds_max_annual would contain 
      // the data pertaining to transfers and the like
      $this->dbobj->querystring .= " group by a.cat_mp ";
      if ($this->debug) {
         $this->logDebug("$this->dbobj->querystring ; <br>");
      }
      $this->dbobj->performQuery();
      
      */
   }
   
}

class wsp_waterUser extends modelObject {/*
    typeid |      typename       | typeabbrev | consumption | max_day_mos
   --------+---------------------+------------+-------------+-------------
         4 | Hydro Power         | PH         |           0 |           1
         8 | Mining              | MIN        |           0 |           1
        13 | Other               | OTH        |           0 |           1
         5 | Public Water Supply | PWS        |         0.5 |        1.25
        12 | Irrigation          | IRR        |           1 |           1
         9 | Agriculture         | AGR        |        0.75 |           1
         6 | Fossil Power        | PF         |         0.9 |           1
        10 | Nuclear Power       | PN         |         0.9 |           1
        11 | Manufacturing       | MAN        |        0.25 |           1
         7 | Commerical          | COM        |        0.25 |           1
*/
   // time series for withdrawal data, based on MPID and USERID - one object per withdrawal
   var $waterusetype = ''; // VWUDS abbreviation: PWS, COM, PN, etc.
   var $wdtype = ''; // VWUDS abbreviation: GW, SW, TW
   var $annual_mg = 0.0; // may be subclassed as an array with annually varying uses or some other formula
   var $maxuse_monthly = array(); // multipliers to put against mean daily 
   var $maxuse_annual = 0.0;
   var $consumption_pct = array(); // monthly factors for computing consumption
   var $wd_info = array(); // information about this withdrawal
   var $historic_mgd = 0.0;
   var $current_mgd = 0.0;
   var $discharge_enabled = 0; // discharge is disabled unless we have explicitly set it
   var $serialist = 'wd_info,maxuse_monthly,maxuse_annual,consumption_pct';
   
   function wake() {
      parent::wake();
      $this->prop_desc['annual_mg'] = 'Historic Demand (mgd).';
      $this->prop_desc['current_mgd'] = 'Current Demand (mgd).';
      $this->prop_desc['flowby'] = 'Flow rate below which withdrawal is halted - depends on Qriver (cfs).';
   }
   
   function init() {
      parent::init();
      
      // parent routines grab all data, now do summary queries to
      //$this->summarizeWithdrawals();
      
      // grab USGS stations
      
   }
   function setState() {
      parent::setState();
      $this->state['current_mgd'] = 0.0;
      $this->state['historic_mgd'] = 0.0;
      $this->state['flowby'] = NULL;
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      // add these to the data columns for logging
      $statenums = array('current_mgd','historic_mgd', 'flowby');
      foreach ($statenums as $thiscol) {
         $this->dbcolumntypes[$thiscol] = 'float8';
         $this->data_cols[] = $thiscol;
      }
      
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'current_mgd');
      array_push($publix, 'historic_mgd');
      array_push($publix, 'wd_mgd');
      array_push($publix, 'flowby');

      return $publix;
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
         $this->logDebug("<b>$this->name Sub-processors executed at hour " . $this->state['hour'] . " on " . $this->state['thisdate'] . " week " . $this->state['week'] . " month " . $this->state['month'] . " year " . $this->state['year'] . ".</b><br>\n");
      }
      
      
      // estimated demands are calculated here in case we do not have a time series data
      // parent does data aquisition, now calculate demands
      $thismo = $this->state['month'];
      $thisyr = $this->state['year'];
      $modays = $this->timer->modays;
      if ($modays == 0) {
         $modays = 30;
      }
      $wd_mgd = $this->state['wd_mgd'];
      $historic_mgd = $this->state['historic_mgd'];
      
      if ( ($this->state['flowby'] <> NULL) and ($this->state['Qriver'] <> NULL) ) {
         // if we have these variables, then we have the capabilty of overriding the 
         // withdrawal values
         if ( $this->state['flowby'] > $this->state['Qriver'] ) {
            $this->state['safe_yield'] = 0.0;
            $this->state['current_mgd'] = 0.0;
         }
      }
      
      // calculate the estimated discharge, unless we have over-ridden with a sub-component
      if ( (!isset($this->processors['discharge_calc'])) and ($this->discharge_enabled) ) {
         $consumption = $this->state['consumption'];
         $discharge_calc = $wd_mgd * (1.0 - $consumption);
      } else {
         if ($this->discharge_enabled) {
            $discharge_calc = $this->state['discharge_calc'];
         } else {
            $discharge_calc = 0.0;
         }
      }
      if (!is_float($discharge_calc)) {
         $discharge_calc = 0.0;
      }
      $this->state['discharge_calc'] = $discharge_calc;
      
      // calculate the estimated discharge, unless we have over-ridden with a sub-component
      if (!isset($this->processors['surface_mgd'])) {
         if ($this->wdtype == 'SW') {
            $surface_mgd = $wd_mgd;
         } else {
            $surface_mgd = 0.0; // GW and TW do not have a local withdrawal
         }
         $this->state['surface_mgd'] = $surface_mgd;
      }
      
      $this->postStep();
      
   }
   
   function create() {
      parent::create();
      // set default land use
      // set basic data query
      $this->logDebug("Create() function called <br>");
      // add use types
      $this->getHistoricUse();
      $this->addHistoricUseMatrices();
      $this->logDebug("Processors on this object: " . print_r(array_keys($this->processors),1) . " <br>");
   }

   function addHistoricUseMatrices() {
   // these matrices hold a copy of the historic monthly percent of annual (mean), and the historic annual
   // these are data pieces that can be used in place of the monthly time series on this object 
   // or as a part of the current or projected estimates
      if (isset($this->processors['historic_monthly_pct'])) {
         unset($this->processors['historic_monthly_pct']);
      }
      // historic percent subcomponent 
      $usedef = new dataMatrix;
      $usedef->name = 'historic_monthly_pct';
      $usedef->description = 'Historic average monthly percent of annual use';
      $usedef->wake();
      $usedef->numcols = 2;  
      $usedef->valuetype = 1; // 2 column lookup (col & row)
      $usedef->keycol1 = 'month'; // key for 1st lookup variable
      $usedef->lutype1 = 0; // lookp type - exact match for land use name
      $usedef->keycol2 = ''; // key for 2nd lookup variable
      $usedef->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line
      $usedef->numrows = 13;
      foreach (array('month_num','pct_of_annual') as $header) {
         $usedef->matrix[] = $header;
      }
      // now add the individual records
      $mos = array('jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'aug'=>8,'sep'=>9, 'oct'=>10,'nov'=>11,'dec'=>12);
      foreach ($mos as $thismo=>$thisno) {
         $usedef->matrix[] = $thisno;// map the text mo to a numerical description
         $usedef->matrix[] = $this->historic_monthly_pct[0][$thismo];
      }
      $this->logDebug("Trying to add Monthly Use fraction sub-component matrix with values: " . print_r($usedef->matrix,1) . " from " . print_r($this->historic_monthly_pct,1) . " <br>");
      $this->addOperator('historic_monthly_pct', $usedef, 0);
      
      
      // historic Total use subcomponent 
      $usedef = new dataMatrix;
      $usedef->name = 'historic_annual';
      $usedef->description = 'Historic annual use in MG per year';
      $usedef->wake();
      $usedef->numcols = count($this->historic_annual[0]);  
      $usedef->valuetype = 1; // 2 column lookup (col & row)
      $usedef->keycol1 = 'year'; // key for 1st lookup variable
      $usedef->lutype1 = 1; // lookup type - interpolated
      // add a row for the header line
      $usedef->numrows = count($this->historic_annual) + 1;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      foreach (array_keys($this->historic_annual[0]) as $thisvar) {
         $usedef->matrix[] = $thisvar;
      }
      // now add the individual records
      foreach ($this->historic_annual as $thistype) {
         foreach ($thistype as $thisvar) {
            $usedef->matrix[] = $thisvar;
         }
      }
      $this->logDebug("Trying to add use type sub-component matrix with values: " . print_r($usedef->matrix,1) . " <br>");
      $this->addOperator('historic_annual', $usedef, 0);
      
      
      // consumptive use factors by month
      if (isset($this->processors['consumption'])) {
         unset($this->processors['consumption']);
      }
      $usedef = new dataMatrix;
      $usedef->name = 'consumption';
      $usedef->description = 'Monthly consumptive use factors (return flow % is [1-consumption] )';
      $usedef->wake();
      $usedef->numcols = 2;  
      $usedef->valuetype = 1; // 1 column lookup (col & row)
      $usedef->keycol1 = 'month'; // key for 1st lookup variable
      $usedef->lutype1 = 0; // lookp type - exact match for land use name
      $usedef->keycol2 = ''; // key for 2nd lookup variable
      $usedef->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line
      $usedef->numrows = 13;
      foreach (array('month_num','pct_consumptive') as $header) {
         $usedef->matrix[] = $header;
      }
      // now add the individual records
      $mos = array('c_jan'=>1,'c_feb'=>2,'c_mar'=>3,'c_apr'=>4,'c_may'=>5,'c_jun'=>6,'c_jul'=>7,'c_aug'=>8,'c_sep'=>9, 'c_oct'=>10,'c_nov'=>11,'c_dec'=>12);
      foreach ($mos as $thismo=>$thisno) {
         $usedef->matrix[] = $thisno;// map the text mo to a numerical description
         $usedef->matrix[] = $this->consumption_pct[$thismo];
      }
      $this->logDebug("Trying to add Monthly consumtive fraction sub-component matrix with values: " . print_r($usedef->matrix,1) . " from " . print_r($this->consumption_pct,1) . " <br>");
      $this->addOperator('consumption', $usedef, 0);
      
   }
   
   
   function getTablesColumns() {
      parent::getTablesColumns();
   }
   
   function getWDInfo() {
      $this->wd_info = array();
      if ($this->debug) {
         $this->logDebug("Withdrawal Info: " . print_r($this->wd_info,1));
      }
      return $this->wd_info;
      
   }
   
   function getConsumptionFractions($thistype = 'OTH') {
      $this->consumption_pct = array();
      if ($this->debug) {
         $this->logDebug("Consumption Info: " . print_r($this->consumption_pct,1));
      }
      return $this->consumption_pct;
      
   }
   
   function getTransfers() {
      //get any transfers associated with this MP
   }
   
   function getHistoricUse() {
      $this->historic_monthly_pct = array();
      $this->historic_annual = array();
      $this->projected_monthly = array();
      $this->projected_annual = array();
      $this->maxuse_monthly = array();
      $this->maxuse_annual = array();
      $this->usetypes = array();
      $this->getWDInfo();
      
      if (!isset($this->wd_info['CAT_MP'])) {
         $thistype = 'OTH';
      } else {
         $thistype = $this->wd_info['CAT_MP'];
      }
      
      $this->getConsumptionFractions($thistype);
      
   }
   
   function summarizeWithdrawals() {
   }
   
}

class wsp_waterUserOld extends dataConnectionObject {/*
    typeid |      typename       | typeabbrev | consumption | max_day_mos
   --------+---------------------+------------+-------------+-------------
         4 | Hydro Power         | PH         |           0 |           1
         8 | Mining              | MIN        |           0 |           1
        13 | Other               | OTH        |           0 |           1
         5 | Public Water Supply | PWS        |         0.5 |        1.25
        12 | Irrigation          | IRR        |           1 |           1
         9 | Agriculture         | AGR        |        0.75 |           1
         6 | Fossil Power        | PF         |         0.9 |           1
        10 | Nuclear Power       | PN         |         0.9 |           1
        11 | Manufacturing       | MAN        |        0.25 |           1
         7 | Commerical          | COM        |        0.25 |           1
*/
   // time series for withdrawal data, based on MPID and USERID - one object per withdrawal
   var $id1 = ''; # USER ID
   var $id2 = ''; # MP ID
   var $historic_monthly_pct = array();
   var $historic_annual = array();
   var $projected_monthly = array();
   var $projected_annual = array();
   var $maxuse_monthly = array();
   var $maxuse_annual = array();
   var $usetypes = array();
   var $consumption_pct = array();
   var $wd_info = array();
   var $historic_mgd = 0.0;
   var $current_mgd = 0.0;
   // vwuds data connection information
   var $max_memory_values = 1000;
   var $username = 'wsp_ro';
   var $password = '314159';
   var $dbname = 'vwuds';
   var $host = '128.173.217.20';
   var $intmethod = 1; // use previous value method or monthly observed withdrawals
   // vpdes database connection info
   var $vpdes_username = 'vpdes_ro';
   var $vpdes_password = 'vpd3sROpw';
   var $vpdes_dbname = 'vpdes';
   var $vpdes_host = '128.173.217.20';
   var $vpdes_db = -1;
   // end data connection information
   // VPDES permit information
   var $vpdes_permitno = '';
   var $vpdes_outfallno = ''; // if none specified then we retrieve all of them
   var $datecolumn = 'thisdate';
   var $lon_col = 'lon_dd';
   var $lat_col = 'lat_dd';
   var $serialist = 'wd_info,historic_monthly_pct,historic_annual,projected_monthly,projected_annual,maxuse_monthly,maxuse_annual,usetypes,consumption';
   
   function wake() {
      parent::wake();
      $this->prop_desc['historic_mgd'] = 'Historic Demand (mgd).';
      $this->prop_desc['current_mgd'] = 'Current Demand (mgd).';
   }
   
   function init() {
      parent::init();
      
      // parent routines grab all data, now do summary queries to
      //$this->summarizeWithdrawals();
      
      // grab USGS stations
      
   }
   function setState() {
      parent::setState();
      $this->state['current_mgd'] = 0.0;
      $this->state['historic_mgd'] = 0.0;
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      // add these to the data columns for logging
      $statenums = array('current_mgd','historic_mgd');
      foreach ($statenums as $thiscol) {
         $this->dbcolumntypes[$thiscol] = 'float8';
         $this->data_cols[] = $thiscol;
      }
      
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'current_mgd');
      array_push($publix, 'historic_mgd');

      return $publix;
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
      // we also set our remote query here, which IS needed
      $this->createQuery();
      return array($public_cols, $group_cols);
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
         $this->logDebug("<b>$this->name Sub-processors executed at hour " . $this->state['hour'] . " on " . $this->state['thisdate'] . " week " . $this->state['week'] . " month " . $this->state['month'] . " year " . $this->state['year'] . ".</b><br>\n");
      }
      
      // demands from time series
      
      // parent does data aquisition, now calculate demands
      $thismo = $this->state['month'];
      $thisyr = $this->state['year'];
      $modays = $this->timer->modays;
      if ($modays == 0) {
         $modays = 30;
      }
      // do current
      $curr_demand_mgd = 0.0;
      if (isset($this->processors['current_annual'])) {
         $curr_d = $this->processors['current_annual']->matrix_rowcol;
         if ($this->debug) {
            $this->logDebug("<b>Evaluating Current Demands @ $thisyr, $thismo:</b>" . print_r($curr_d,1) . " <br>");
         }
         for ($i = 1; $i < count($curr_d); $i++) {
            $wd_type = $hist_d[$i][0];
            $demand_curr_mgd = $this->processors['current_annual']->evaluateMatrix($wd_type,$thisyr);
            $demand_curr_frac = $this->processors['current_monthly']->evaluateMatrix($wd_type,$thismo);
            // shouldn't we divide this by number of days in the month??
            $curr_demand_mgd += ($demand_curr_mgd * $demand_curr_frac) / $modays;
            if ($this->debug) {
               $this->logDebug("Evaluated $wd_type,$thisyr $wd_type,$thismo = $demand_curr_mgd * $demand_curr_frac <br>");
            }
         }
         if ($this->debug) {
            $this->logDebug("Current demand set to: $curr_demand_mgd <br>");
         }
      }
      $this->state['current_mgd'] = $curr_demand_mgd;
      $this->postStep();
      
   }
   
   function create() {
      parent::create();
      // set default land use
      // set basic data query
      $this->logDebug("Create() function called <br>");
      // add use types
      $this->getHistoricUse();
      $this->addHistoricUseMatrices();
      $this->logDebug("Processors on this object: " . print_r(array_keys($this->processors),1) . " <br>");
   }

   function addHistoricUseMatrices() {
   // these matrices hold a copy of the historic monthly percent of annual (mean), and the historic annual
   // these are data pieces that can be used in place of the monthly time series on this object 
   // or as a part of the current or projected estimates
      if (isset($this->processors['historic_monthly_pct'])) {
         unset($this->processors['historic_monthly_pct']);
      }
      // historic percent subcomponent 
      $usedef = new dataMatrix;
      $usedef->name = 'historic_monthly_pct';
      $usedef->description = 'Historic average monthly percent of annual use';
      $usedef->wake();
      $usedef->numcols = 2;  
      $usedef->valuetype = 1; // 2 column lookup (col & row)
      $usedef->keycol1 = 'month'; // key for 1st lookup variable
      $usedef->lutype1 = 0; // lookp type - exact match for land use name
      $usedef->keycol2 = ''; // key for 2nd lookup variable
      $usedef->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line
      $usedef->numrows = 13;
      foreach (array('month_num','pct_of_annual') as $header) {
         $usedef->matrix[] = $header;
      }
      // now add the individual records
      $mos = array('jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'aug'=>8,'sep'=>9, 'oct'=>10,'nov'=>11,'dec'=>12);
      foreach ($mos as $thismo=>$thisno) {
         $usedef->matrix[] = $thisno;// map the text mo to a numerical description
         $usedef->matrix[] = $this->historic_monthly_pct[0][$thismo];
      }
      $this->logDebug("Trying to add Monthly Use fraction sub-component matrix with values: " . print_r($usedef->matrix,1) . " from " . print_r($this->historic_monthly_pct,1) . " <br>");
      $this->addOperator('historic_monthly_pct', $usedef, 0);
      
      
      // historic Total use subcomponent 
      $usedef = new dataMatrix;
      $usedef->name = 'historic_annual';
      $usedef->description = 'Historic annual use in MG per year';
      $usedef->wake();
      $usedef->numcols = count($this->historic_annual[0]);  
      $usedef->valuetype = 1; // 2 column lookup (col & row)
      $usedef->keycol1 = 'year'; // key for 1st lookup variable
      $usedef->lutype1 = 1; // lookup type - interpolated
      // add a row for the header line
      $usedef->numrows = count($this->historic_annual) + 1;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      foreach (array_keys($this->historic_annual[0]) as $thisvar) {
         $usedef->matrix[] = $thisvar;
      }
      // now add the individual records
      foreach ($this->historic_annual as $thistype) {
         foreach ($thistype as $thisvar) {
            $usedef->matrix[] = $thisvar;
         }
      }
      $this->logDebug("Trying to add use type sub-component matrix with values: " . print_r($usedef->matrix,1) . " <br>");
      $this->addOperator('historic_annual', $usedef, 0);
      
      
      // consumptive use factors by month
      if (isset($this->processors['consumption'])) {
         unset($this->processors['consumption']);
      }
      $usedef = new dataMatrix;
      $usedef->name = 'consumption';
      $usedef->description = 'Monthly consumptive use factors (return flow % is [1-consumption] )';
      $usedef->wake();
      $usedef->numcols = 2;  
      $usedef->valuetype = 1; // 1 column lookup (col & row)
      $usedef->keycol1 = 'month'; // key for 1st lookup variable
      $usedef->lutype1 = 0; // lookp type - exact match for land use name
      $usedef->keycol2 = ''; // key for 2nd lookup variable
      $usedef->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line
      $usedef->numrows = 13;
      foreach (array('month_num','pct_consumptive') as $header) {
         $usedef->matrix[] = $header;
      }
      // now add the individual records
      $mos = array('c_jan'=>1,'c_feb'=>2,'c_mar'=>3,'c_apr'=>4,'c_may'=>5,'c_jun'=>6,'c_jul'=>7,'c_aug'=>8,'c_sep'=>9, 'c_oct'=>10,'c_nov'=>11,'c_dec'=>12);
      foreach ($mos as $thismo=>$thisno) {
         $usedef->matrix[] = $thisno;// map the text mo to a numerical description
         $usedef->matrix[] = $this->consumption_pct[$thismo];
      }
      $this->logDebug("Trying to add Monthly consumtive fraction sub-component matrix with values: " . print_r($usedef->matrix,1) . " from " . print_r($this->consumption_pct,1) . " <br>");
      $this->addOperator('consumption', $usedef, 0);
      
   }
   
   
   function getTablesColumns() {
      parent::getTablesColumns();
   }
   
   function getWDInfo() {
      $this->wd_info = array();
      $this->dbobject->querystring = "  select * from vwuds_mp_detail ";
      $this->dbobject->querystring .= " where \"MPID\" = '$this->id2' ";
      $this->dbobject->querystring .= "    and \"USERID\" = '$this->id1' ";
      $this->dbobject->querystring .= "    and \"ACTION\" = '$this->action' ";
      $this->dbobject->performQuery();
      if ($this->debug) {
         $this->logDebug($this->dbobject->querystring);
      }
      $this->wd_info = $this->dbobject->queryrecords[0];
      if ($this->debug) {
         $this->logDebug("Withdrawal Info: " . print_r($this->wd_info,1));
      }
      
   }
   
   function getConsumptionFractions($thistype = 'OTH') {
      $this->consumption_pct = array();
      $this->dbobject->querystring = "  select * from waterusetype ";
      $this->dbobject->querystring .= " where typeabbrev = '$thistype' ";
      $this->dbobject->performQuery();
      if ($this->debug) {
         $this->logDebug($this->dbobject->querystring);
      }
      $this->consumption_pct = $this->dbobject->queryrecords[0];
      if ($this->debug) {
         $this->logDebug("Consumption Info: " . print_r($this->consumption_pct,1));
      }
      
   }
   
   function getTransfers() {
      //get any transfers associated with this MP
   }
   
   function getHistoricUse() {
      $this->historic_monthly_pct = array();
      $this->historic_annual = array();
      $this->projected_monthly = array();
      $this->projected_annual = array();
      $this->maxuse_monthly = array();
      $this->maxuse_annual = array();
      $this->usetypes = array();
      $this->getWDInfo();
      
      $annual_query = "  select \"YEAR\" as thisyear, ";
      $annual_query .= "    CASE ";
      $annual_query .= "       WHEN \"ANNUAL\" is null THEN 0.0 ";
      $annual_query .= "       ELSE round((\"ANNUAL/365\"*365.0)::numeric,5) ";
      $annual_query .= "    END as total_mg ";
      $annual_query .= " FROM vwuds_annual_mp_data ";
      // by putting this outside of the "ON" clause, we override the left join, only returning these specific uses
      $annual_query .= " WHERE \"USERID\" = '$this->id1' ";
      $annual_query .= " AND \"MPID\" = '$this->id2' ";
      $annual_query .= " ORDER BY \"YEAR\"";
      $this->dbobject->querystring = $annual_query;
      //error_log($this->dbobject->querystring);
      $this->dbobject->performQuery();
      $this->historic_annual = $this->dbobject->queryrecords;
      
      if (!isset($this->wd_info['CAT_MP'])) {
         $thistype = 'OTH';
      } else {
         $thistype = $this->wd_info['CAT_MP'];
      }
      
      $this->dbobject->querystring = "  select ";
      $this->dbobject->querystring .= "    round(avg(jan)::numeric,4) as jan, round(avg(feb)::numeric,4) as feb, ";
      $this->dbobject->querystring .= "    round(avg(mar)::numeric,4) as mar, round(avg(apr)::numeric,4) as apr, ";
      $this->dbobject->querystring .= "    round(avg(may)::numeric,4) as may, round(avg(jun)::numeric,4) as jun, ";
      $this->dbobject->querystring .= "    round(avg(jul)::numeric,4) as jul, round(avg(aug)::numeric,4) as aug, ";
      $this->dbobject->querystring .= "    round(avg(sep)::numeric,4) as sep, round(avg(oct)::numeric,4) as oct, ";
      $this->dbobject->querystring .= "    round(avg(nov)::numeric,4) as nov, round(avg(dec)::numeric,4) as dec ";
      $this->dbobject->querystring .= " from ( ";
      $this->dbobject->querystring .= "    select a.typeabbrev, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"JANUARY\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as jan, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"FEBRUARY\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as feb, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"MARCH\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as mar, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"APRIL\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as apr, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"MAY\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as may, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"JUNE\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as jun, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"JULY\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as jul, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"AUGUST\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as aug, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"SEPTEMBER\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as sep, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"OCTOBER\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as oct, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"NOVEMBER\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as nov, ";
      $this->dbobject->querystring .= "    CASE ";
      $this->dbobject->querystring .= "       WHEN (b.\"ANNUAL\" is null) or (b.\"ANNUAL\" = 0) THEN 0.0 ";
      $this->dbobject->querystring .= "       ELSE round( (b.\"DECEMBER\" / b.\"ANNUAL\")::numeric, 4) ";
      $this->dbobject->querystring .= "    END as dec ";
      $this->dbobject->querystring .= " from waterusetype as a left outer join vwuds_annual_mp_data as b ";
      $this->dbobject->querystring .= " on (a.typeabbrev = b.\"CAT_MP\" ";
      $this->dbobject->querystring .= "     and b.\"USERID\" = '$this->id1' ";
      $this->dbobject->querystring .= "     AND b.\"MPID\" = '$this->id2' ) ";
      $this->dbobject->querystring .= " WHERE a.typeabbrev = '" . $thistype . "' ";
      $this->dbobject->querystring .= " order by typeabbrev ";
      $this->dbobject->querystring .= " ) as foo ";
      $this->dbobject->querystring .= " group by typeabbrev";
      $this->dbobject->performQuery();
      //error_log($this->dbobject->querystring);
      $this->historic_monthly_pct = $this->dbobject->queryrecords;
      
      $this->getConsumptionFractions($thistype);
      
   }
   
   function summarizeWithdrawals() {
      // This is no longer valid since this is now an XML connection and the tables and dbconn's are not set up
      
      /*
      // parent routines grab all data, now do summary queries to
      // the following summaries should be generated by the XML object:
      // historic annual totals, by year and use_type (row - type, column - year)
      $this->dbobj->querystring = "  select a.cat_mp, sum(a.max_annual) as max_annual, ";
      $this->dbobj->querystring .= "    sum(a.max_annual/365.0) as max_mgd ";
      $this->dbobj->querystring .= " FROM $dbt as a, vwuds_max_withdrawal as b ";
      $this->dbobj->querystring .= " where a.mpid = b.mpid ";
      // currently, the vwuds_max withdrawal table holds only that, withdrawals, but at some point
      // it, or a table that would be more properly known as vwuds_max_annual would contain 
      // the data pertaining to transfers and the like
      $this->dbobj->querystring .= " group by a.cat_mp ";
      if ($this->debug) {
         $this->logDebug("$this->dbobj->querystring ; <br>");
      }
      $this->dbobj->performQuery();
      // historica monthly mean percent of annual by use_type (row - type, column - month)
      // update other components, such as the summary data, and the category multipliers
      $dbt = $this->dbtable;
      $this->dbobj->querystring = "  select a.cat_mp, sum(a.max_annual) as max_annual, ";
      $this->dbobj->querystring .= "    sum(a.max_annual/365.0) as max_mgd ";
      $this->dbobj->querystring .= " FROM $dbt as a, vwuds_max_withdrawal as b ";
      $this->dbobj->querystring .= " where a.mpid = b.mpid ";
      // currently, the vwuds_max withdrawal table holds only that, withdrawals, but at some point
      // it, or a table that would be more properly known as vwuds_max_annual would contain 
      // the data pertaining to transfers and the like
      $this->dbobj->querystring .= " group by a.cat_mp ";
      if ($this->debug) {
         $this->logDebug("$this->dbobj->querystring ; <br>");
      }
      $this->dbobj->performQuery();
      
      */
   }

   
   function createQuery() {
   
      if (is_object($this->timer)) {
         if (is_object($this->timer->thistime)) {
            $startdate = $this->timer->thistime->format('Y-m-d');
         }
         if (is_object($this->timer->endtime)) {
            $enddate = $this->timer->endtime->format('Y-m-d');
         }

         $this->sql_query = "  select thisdate, wd_mgd as historic_mgd from vwuds_monthly_data ";
         $this->sql_query .= " where userid = '$this->id1' ";
         $this->sql_query .= " AND mpid = '$this->id2' ";
         $this->sql_query .= " and thisdate >= '$startdate'";
         $this->sql_query .= " and thisdate <= '$enddate'";
         // END - if using precip
         $this->sql_query .= " order by thisdate ";
      
      } else {
         if ($this->debug) {
            $this->logDebug("Timer object not set - query not created.<br>\n");
         }
      }
      parent::reCreate();
   }
   
}



class wsp_vpdesvwuds extends timeSeriesInput {
/*
    typeid |      typename       | typeabbrev | consumption | max_day_mos
   --------+---------------------+------------+-------------+-------------
         4 | Hydro Power         | PH         |           0 |           1
         8 | Mining              | MIN        |           0 |           1
        13 | Other               | OTH        |           0 |           1
         5 | Public Water Supply | PWS        |         0.5 |        1.25
        12 | Irrigation          | IRR        |           1 |           1
         9 | Agriculture         | AGR        |        0.75 |           1
         6 | Fossil Power        | PF         |         0.9 |           1
        10 | Nuclear Power       | PN         |         0.9 |           1
        11 | Manufacturing       | MAN        |        0.25 |           1
         7 | Commerical          | COM        |        0.25 |           1
*/
   // time series for withdrawal data, based on MPID and USERID - one object per withdrawal
   var $id1 = ''; # USER ID
   var $id2 = ''; # MP ID
   var $vwp_permit = ''; # VWP Permit ID
   var $vdh_pwsid = ''; # VDH Number
   var $system = ''; # Water System Name
   var $source = ''; # Water Source name
   var $ownname = ''; # Owner name
   var $waterusetype = ''; // VWUDS abbreviation: PWS, COM, PN, etc.
   var $wdtype = 'SW'; // VWUDS abbreviation: GW, SW, TW
   var $action = 'WL'; // VWUDS abbreviation: WL, SD, SR
   var $historic_monthly_pct = array();
   var $historic_annual = array();
   var $projected_monthly = array();
   var $projected_annual = array();
   var $max_wd_annual = 0.0;
   var $avg_wd_annual = 0.0; // should be same as "current_annual"
   var $max_wd_daily = 0.0;
   var $avg_wd_daily = 0.0;
   var $max_wd_monthly = 0.0;
   var $usetypes = array();
   var $wd_info = array();
   var $consumption_pct = array();
   var $patchnull = 'none'; // whether or not topatch null records
   var $historic_mgd = 0.0;
   var $current_mgd = 0.0;
   var $current_mgy = 0.0;
   var $current_years = '2005,2006,2007,2008,2009,2010';
   var $discharge_enabled = 0; // discharge is disabled unless we have explicitly set it
   // vwuds data connection information
   var $withdrawal_enabled = 1; // withdrawal is enabled unless we have explicitly set it
   // vwuds data connection information
   var $vwuds_db = -1;
   var $max_memory_values = 1000;
   var $vwuds_username = 'wsp_ro';
   var $vwuds_password = '314159';
   var $vwuds_dbname = 'vwuds';
   var $vwuds_host = '192.168.0.21';
   var $vwuds_port = 5432;
   var $intmethod = 1; // use previous value method or monthly observed withdrawals
   // vpdes database connection info
   var $vpdes_db = -1;
   var $vpdes_username = 'vpdes_ro';
   var $vpdes_password = 'vpd3sROpw';
   var $vpdes_dbname = 'vpdes';
   var $vpdes_host = '128.173.217.26';
   var $vpdes_port = 5432;
   var $vpdes_curr_start = '2007-01-01';
   var $vpdes_curr_end = '2009-12-31';
   // end data connection information
   // VPDES permit information
   var $ps_info = array();
   var $vpdes_permitno = '';
   var $vpdes_outfallno = ''; // if none specified then we retrieve all of them?  or only 001??
   var $datecolumn = 'thisdate';
   var $lon_col = 'lon_dd';
   var $lat_col = 'lat_dd';
   var $serialist = 'wd_info,ps_info,historic_monthly_pct,historic_annual,projected_monthly,projected_annual,usetypes,consumption_pct';
   
   function wake() {
      parent::wake();
      //$this->setupDBConn();
      $this->prop_desc['wd_mgd'] = 'Current modeled withdrawal rate (mgd).';
      $this->prop_desc['historic_mgd'] = 'Historic Demand (mgd).';
      $this->prop_desc['current_mgd'] = 'Current Demand (mgd).';
      $this->prop_desc['current_mgy'] = 'Current annual withdrawal (mgy).';
      $this->prop_desc['discharge_mgd'] = 'Current modeled discharge rate (mgd).';
      $this->prop_desc['discharge_vpdes_mgd'] = 'Current Demand (mgd).';
      $this->prop_desc['discharge_calc'] = 'Estimated Discharge (mgd).';
      $this->prop_desc['ps_bestest_mgd'] = 'Best estimate of discharge (mgd).';
      $this->prop_desc['max_wd_annual'] = 'Maximum single year withdrawal (mgd).';
      $this->prop_desc['surface_mgd'] = 'Rate of withdrawal from surface water in this time step (mgd).';
      $this->prop_desc['safe_yield'] = 'Maximum allowable withdrawal rate (mgd) - defaults to maximum single year divided by historical monthly fractions.';
      $this->prop_desc['flowby'] = 'Flow rate below which withdrawal is halted - depends on Qriver (cfs).';
      $this->prop_desc['Qriver'] = 'Current river flow, default variable for evaluating flowby, if NULL, flowby will not be evaluated (cfs).';
   }

   function sleep() {
      parent::sleep();
      $this->vpdes_db = NULL;
      $this->vwuds_db = NULL;
   }
   
   function init() {
      parent::init();
      
      // since this is a WD/PS object, and data for these objects is in monthly format, there is no need (currently)
      // to economize in the manner of the cached query that the DataConnection objects use.  thus, this object
      // simply sub-classes the timeSeriesInput object 
      // this is also advantageous, since this the first object to utilize two data connections, and thus it would 
      // require some re-programming.  
      // get WD data, add to time series
      // get PS data, add to time series
      //$this->loadVPDESData();
      //$this->loadVWUDSData();
      //$this->estimateCurrentWithdrawal();
      // now, close db connections to save memory/transaction loads.
      // cannot do this however, since multiple objects share a single connection
      //$this->closeDBConns();
      
   }
   
   function closeDBConns() {
      // close all non-needed connections
      if ($this->discharge_enabled) {
        pg_close($this->vpdes_db->dbconn);
      }
      pg_close($this->vwuds_db->dbconn);
      $this->vpdes_db = NULL;
      $this->vwuds_db = NULL;
   
   }
   
   function setState() {
      parent::setState();
      $this->state['wd_mgd'] = NULL;
      $this->state['current_mgd'] = NULL;
      $this->state['current_mgy'] = NULL;
      $this->state['historic_mgd'] = NULL;
      $this->state['discharge_vpdes_mgd'] = NULL;
      $this->state['wd_bestest_mgd'] = NULL;
      $this->state['ps_bestest_mgd'] = NULL;
      $this->state['discharge_calc'] = NULL;
      $this->state['max_wd_annual'] = $this->max_wd_annual;
      $this->state['max_wd_daily'] = $this->max_wd_daily;
      $this->state['safe_yield'] = NULL;
      $this->state['surface_mgd'] = NULL;
      $this->state['flowby'] = NULL;
      $this->state['Qriver'] = NULL;
   }
   
   function setDataColumnTypes() {
      parent::setDataColumnTypes();
      // add these to the data columns for logging
      $statenums = array('wd_mgd','current_mgy','current_mgd','historic_mgd','discharge_vpdes_mgd','wd_bestest_mgd','ps_bestest_mgd', 'discharge_calc','surface_mgd','discharge_mgd','historic_monthly_pct','current_monthly_discharge', 'consumption', 'max_wd_annual','safe_yield', 'flowby', 'Qriver');
      foreach ($statenums as $thiscol) {
         $this->dbcolumntypes[$thiscol] = 'float8';
         $this->data_cols[] = $thiscol;
      }
      
   }

   function getPublicProps() {
      # gets only properties that are visible (must be manually defined)
      $publix = parent::getPublicProps();

      array_push($publix, 'current_mgd');
      array_push($publix, 'wd_mgd');
      array_push($publix, 'current_mgy');
      array_push($publix, 'historic_mgd');
      array_push($publix, 'discharge_vpdes_mgd');
      array_push($publix, 'wd_bestest_mgd');
      array_push($publix, 'ps_bestest_mgd');
      array_push($publix, 'discharge_calc');
      array_push($publix, 'max_wd_annual');
      array_push($publix, 'surface_mgd');
      array_push($publix, 'flowby');
      array_push($publix, 'Qriver');

      return $publix;
   }
   
   function setupDBConn($refresh = 0) {
   
      $this->vwuds_db = new pgsql_QueryObject;
      $this->vwuds_db->dbconn = pg_connect("host=$this->vwuds_host port=$this->vwuds_port dbname=$this->vwuds_dbname user=$this->vwuds_username password=$this->vwuds_password");
     
     $stat = pg_connection_status($this->vwuds_db->dbconn);
     /*
     if ($stat === PGSQL_CONNECTION_OK) {
         error_log( "Connection status ok (PG CONNECTION STRING (for $this->name) : host=$this->vwuds_host port=$this->vwuds_port dbname=$this->vwuds_dbname user=$this->vwuds_username password=$this->vwuds_password)");
     } else {
         error_log("Connection status bad (PG CONNECTION STRING (for $this->name) : host=$this->vwuds_host port=$this->vwuds_port dbname=$this->vwuds_dbname user=$this->vwuds_username password=$this->vwuds_password)");
     }
     */
      if ($this->discharge_enabled) {
        $this->vpdes_db = new pgsql_QueryObject;
        $this->vpdes_db->dbconn = pg_connect("host=$this->vpdes_host port=$this->vpdes_port dbname=$this->vpdes_dbname user=$this->vpdes_username password=$this->vpdes_password");
      }
        
      /*
     $stat = pg_connection_status($this->vpdes_db->dbconn);
     if ($stat === PGSQL_CONNECTION_OK) {
         error_log( 'Connection status ok');
     } else {
         error_log("Connection status bad (PG CONNECTION STRING (for $this->name) : host=$this->vpdes_host port=$this->vpdes_port dbname=$this->vpdes_dbname user=$this->vpdes_username password=$this->vpdes_password)");
     }
     */

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
         $this->logDebug("<b>$this->name Sub-processors executed at hour " . $this->state['hour'] . " on " . $this->state['thisdate'] . " week " . $this->state['week'] . " month " . $this->state['month'] . " year " . $this->state['year'] . ".</b><br>\n");
      }
      
      
      // estimated demands are calculated here in case we do not have a time series data
      // parent does data aquisition, now calculate demands
      $thismo = $this->state['month'];
      $thisyr = $this->state['year'];
      $modays = $this->timer->modays;
      if ($modays == 0) {
         $modays = 30;
      }
      $wd_mgd = $this->state['wd_mgd'];
      $historic_mgd = $this->state['historic_mgd'];
      $discharge_vpdes_mgd = $this->state['discharge_vpdes_mgd'];
      $demand_current_mgy = $this->state['current_mgy'];
      
      // calculate the estimated discharge, unless we have over-ridden with a sub-component
      if ( (!isset($this->processors['discharge_calc'])) and ($this->discharge_enabled) ) {
         $consumption = $this->state['consumption'];
         $discharge_calc = $wd_mgd * (1.0 - $consumption);
         // perform "best estimate" for a smooth withdrawal and ps record, unless we have over-ridden with a sub-component
         if (!isset($this->processors['ps_bestest_mgd'])) {
            if ( ($discharge_vpdes_mgd === null) or ($discharge_vpdes_mgd == 'NULL')) {
               $this->state['ps_bestest_mgd'] = $this->state['discharge_calc'];
            } else {
               $this->state['ps_bestest_mgd'] = $discharge_vpdes_mgd;
            }
         }
      } else {
         if ($this->discharge_enabled) {
            $discharge_calc = $this->state['discharge_calc'];
         } else {
            $discharge_calc = 0.0;
            $this->state['discharge_calc'] = $discharge_calc;
            $this->state['ps_bestest_mgd'] = $discharge_calc;
         }
      }
      
      // calculate the estimated discharge, unless we have over-ridden with a sub-component
      if (!isset($this->processors['surface_mgd'])) {
         if ($this->wdtype == 'SW') {
            $surface_mgd = $wd_mgd;
         } else {
            $surface_mgd = 0.0; // GW and TW do not have a local withdrawal
         }
         $this->state['surface_mgd'] = $surface_mgd;
      }
      
      // do current, unless we have over-ridden with a sub-component
      if (isset($this->processors['historic_monthly_pct'])) {
         $demand_curr_frac = $this->processors['historic_monthly_pct']->evaluateMatrix($thismo);
      } else {
         $demand_curr_frac = $modays / 365.0;
      }
      if ( !isset($this->processors['current_mgd']) ) {
         $curr_demand_mgd = ($demand_current_mgy * $demand_curr_frac) / $modays;
         $this->state['current_mgd'] = $curr_demand_mgd;
      }
      // calculate the safe yield number (or permitted max)
      if (!isset($this->processors['safe_yield'])) {
         $safe_yield = ($this->state['max_wd_annual'] * $demand_curr_frac) / $modays;
         $this->state['safe_yield'] = $safe_yield;
      }
      
      if ( ($this->state['flowby'] <> NULL) and ($this->state['Qriver'] <> NULL) ) {
         // if we have these variables, then we have the capabilty of overriding the 
         // withdrawal values
         if ( $this->state['flowby'] > $this->state['Qriver'] ) {
            $this->state['safe_yield'] = 0.0;
            $this->state['current_mgd'] = 0.0;
         }
      }
      
      // check to see if we have both discharges and withdrawals from time series
      // if we have enabled value estimation, we then see if a given value is missing, and then make it
      // NULL, so that it can be accounted for later. 
      if ($this->debug) {
         $this->logDebug("Patch setting: $this->patchnull <br>\n");
         $this->logDebug("Variables: wd: $historic_mgd - ps: $discharge_vpdes_mgd<br>\n");
      }

      if (!isset($this->processors['wd_bestest_mgd'])) {
         if ( ($historic_mgd === null) or ($historic_mgd == 'NULL')) { 
            $this->estimateHistoricWithdrawal();
         } else {
            $this->state['wd_bestest_mgd'] = $historic_mgd;
         }
      }
      if (!$this->withdrawal_enabled) {
         if (!isset($this->processors['historic_mgd'])) {
            $this->state['historic_mgd'] = 0.0;
         }
         if (!isset($this->processors['wd_mgd'])) {
            $this->state['wd_mgd'] = 0.0;
         }
      }
      
      $this->postStep();
      
   }
   
   function estimateCurrentWithdrawal() {
   // estimation hierarchy
   // - use annual totals from data matrix "historic_annual" if present
   // - multiply this number by monthly distro "historic_monthly_pct" if present
      $yrs = explode(',', $this->current_years);
      $wd = 0;
      $this->processors['historic_annual']->init();
      if (count($yrs) > 0) {
         if (isset($this->processors['historic_annual'])) {
            $demand_curr = 0.0;
            //$this->processors['historic_annual']->debug = 1;
            foreach ($yrs as $thisyr) {
               $dmd = $this->processors['historic_annual']->evaluateMatrix($thisyr);
               $this->logDebug("Adding $thisyr ($dmd MG) to current demand estimate <br>\n");
               $demand_curr += $dmd;
            }
            $this->logDebug($this->processors['historic_annual']->debugstring . " <br>\n");
            $wd = $demand_curr / count($yrs);
         }
         
      }
      if (!isset($this->processors['current_mgy'])) {
         $cmgy = new Equation;
         $cmgy->equation = $wd;
         $cmgy->debug = $this->debug;
         $this->addOperator('current_mgy', $cmgy, 0);
      }
      
      if (isset($this->processors['current_mgy'])) {
         $this->processors['current_mgy']->equation = $wd;
      }
   }
   
   function estimateHistoricWithdrawal() {
   // estimation hierarchy
   // - use annual totals from data matrix "historic_annual" if present
   // - multiply this number by monthly distro "historic_monthly_pct" if present
      $modays = $this->timer->modays;
      if ($modays == 0) {
         $modays = 30;
      }
      if (isset($this->state['historic_annual'])) {
         $annualwd = $this->state['historic_annual'];
         if (isset($this->state['historic_monthly_pct'])) {
            $mopct = $this->state['historic_monthly_pct'];
         } else {
            $mopct = 0.08333;
         }
         $wd = $annualwd * $mopct / $modays;
      } else {
         $wd = 0.0;
      }
      $this->state['wd_bestest_mgd'] = $wd;
   }
   
   function create() {
      parent::create();
      // set default land use
      // set basic data query
      $this->logDebug("Create() function called <br>");
      // add use types
      $this->logDebug("Getting historic water withdrawals. <br>\n");
      //$this->getHistoricUse();
     // $this->addHistoricUseMatrices();
      //$this->getHistoricDischarges();
     // $this->addHistoricPSMatrices();
      // this must be done AFTER addHistoricUseMatrices, since it relies upon the matrix historic_annual
      //$this->estimateCurrentWithdrawal();
      $this->logDebug("Processors on this object: " . print_r(array_keys($this->processors),1) . " <br>");
   }

   function addHistoricPSMatrices() {
      $this->logDebug("addHistoricPSMatrices() function called <br>");
      $discharges = $this->getCurrentVPDESDischarges();
      $this->logDebug("getCurrentVPDESDischarges() returned: " . print_r($discharges,1) . "<br>");
      $this->addHistoricDischargeMatrices($discharges);
   
   }

   function addHistoricUseMatrices() {
   // these matrices hold a copy of the historic monthly percent of annual (mean), and the historic annual
   // these are data pieces that can be used in place of the monthly time series on this object 
   // or as a part of the current or projected estimates
      if (isset($this->processors['historic_monthly_pct'])) {
         unset($this->processors['historic_monthly_pct']);
      }
      // historic percent subcomponent 
      $usedef = new dataMatrix;
      $usedef->name = 'historic_monthly_pct';
      $usedef->description = 'Historic average monthly percent of annual use';
      $usedef->wake();
      $usedef->numcols = 2;  
      $usedef->valuetype = 1; // 1 column lookup (col & row)
      $usedef->keycol1 = 'month'; // key for 1st lookup variable
      $usedef->lutype1 = 0; // lookp type - exact match for land use name
      $usedef->keycol2 = ''; // key for 2nd lookup variable
      $usedef->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line
      $usedef->numrows = 13;
      foreach (array('month_num','pct_of_annual') as $header) {
         $usedef->matrix[] = $header;
      }
      // now add the individual records
      $mos = array('jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'aug'=>8,'sep'=>9, 'oct'=>10,'nov'=>11,'dec'=>12);
      foreach ($mos as $thismo=>$thisno) {
         $usedef->matrix[] = $thisno;// map the text mo to a numerical description
         $usedef->matrix[] = $this->historic_monthly_pct[0][$thismo];
      }
      $this->logDebug("Trying to add Monthly Use fraction sub-component matrix with values: " . print_r($usedef->matrix,1) . " from " . print_r($this->historic_monthly_pct,1) . " <br>");
      $this->addOperator('historic_monthly_pct', $usedef, 0);
      
      
      // historic Total use subcomponent 
      $usedef = new dataMatrix;
      $usedef->name = 'historic_annual';
      $usedef->description = 'Historic annual use in MG per year';
      $usedef->wake();
      $usedef->numcols = count($this->historic_annual[0]);  
      $usedef->valuetype = 1; // 2 column lookup (col & row)
      $usedef->keycol1 = 'year'; // key for 1st lookup variable
      $usedef->lutype1 = 1; // lookup type - interpolated
      // add a row for the header line
      $usedef->numrows = count($this->historic_annual) + 1;
      // since these are stored as a single dimensioned array, regardless of their lookup type 
      // (for compatibility with single dimensional HTML form variables)
      // we set alternating values representing the 2 columns (luname - acreage)
      foreach (array_keys($this->historic_annual[0]) as $thisvar) {
         $usedef->matrix[] = $thisvar;
      }
      // now add the individual records
      foreach ($this->historic_annual as $thistype) {
         foreach ($thistype as $thisvar) {
            $usedef->matrix[] = $thisvar;
         }
      }
      $this->logDebug("Trying to add historic annual sub-component matrix with values: " . print_r($usedef->matrix,1) . " <br>");
      $this->addOperator('historic_annual', $usedef, 0);
      
      
      // consumptive use factors by month
      if (isset($this->processors['consumption'])) {
         unset($this->processors['consumption']);
      }
      $usedef = new dataMatrix;
      $usedef->name = 'consumption';
      $usedef->description = 'Monthly consumptive use factors (return flow % is [1-consumption] )';
      $usedef->wake();
      $usedef->numcols = 2;  
      $usedef->valuetype = 1; // 1 column lookup (col & row)
      $usedef->keycol1 = 'month'; // key for 1st lookup variable
      $usedef->lutype1 = 0; // lookp type - exact match for land use name
      $usedef->keycol2 = ''; // key for 2nd lookup variable
      $usedef->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line
      $usedef->numrows = 13;
      foreach (array('month_num','pct_consumptive') as $header) {
         $usedef->matrix[] = $header;
      }
      // now add the individual records
      $mos = array('c_jan'=>1,'c_feb'=>2,'c_mar'=>3,'c_apr'=>4,'c_may'=>5,'c_jun'=>6,'c_jul'=>7,'c_aug'=>8,'c_sep'=>9, 'c_oct'=>10,'c_nov'=>11,'c_dec'=>12);
      foreach ($mos as $thismo=>$thisno) {
         $usedef->matrix[] = $thisno;// map the text mo to a numerical description
         $usedef->matrix[] = $this->consumption_pct[$thismo];
      }
      $this->logDebug("Trying to add Monthly consumtive fraction sub-component matrix with values: " . print_r($usedef->matrix,1) . " from " . print_r($this->consumption_pct,1) . " <br>");
      $this->addOperator('consumption', $usedef, 0);
      
   }

   function addHistoricDischargeMatrices($discharges) {
   // these matrices hold a copy of the historic monthly percent of annual (mean), and the historic annual
   // these are data pieces that can be used in place of the monthly time series on this object 
   // or as a part of the current or projected estimates
      if (isset($this->processors['current_monthly_discharge'])) {
         unset($this->processors['current_monthly_discharge']);
      }
      // historic percent subcomponent 
      $usedef = new dataMatrix;
      $usedef->name = 'current_monthly_discharge';
      $usedef->description = 'Current estimated mean daily discharge by month (MGD)';
      $usedef->wake();
      $usedef->numcols = 2;  
      $usedef->valuetype = 1; // 1 column lookup (col & row)
      $usedef->keycol1 = 'month'; // key for 1st lookup variable
      $usedef->lutype1 = 0; // lookp type - exact match for land use name
      $usedef->keycol2 = ''; // key for 2nd lookup variable
      $usedef->lutype2 = 0; // lookup type - interpolated for year value
      // add a row for the header line
      $usedef->numrows = 12;
      // now add the individual records
      $mos = array('jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'aug'=>8,'sep'=>9, 'oct'=>10,'nov'=>11,'dec'=>12);
      foreach ($mos as $thismo=>$thisno) {
         $usedef->matrix[] = $thisno;// map the text mo to a numerical description
         if (isset($discharges[$thisno - 1]['ps_mgd'])) {
            $usedef->matrix[] = $discharges[$thisno - 1]['ps_mgd'];
         } else {
            $usedef->matrix[] = 0.0;
         }
      }
      $this->logDebug("Trying to add Monthly discharge sub-component matrix with values: " . print_r($usedef->matrix,1) . " from " . print_r($discharges,1) . " <br>");
      $this->addOperator('current_monthly_discharge', $usedef, 0);
      
   }
   
   
   function getTablesColumns() {
      //parent::getTablesColumns();
   }
   
   function getWDInfo() { 
      if (strlen(trim($this->id2)) > 0) {
         $hasmps = TRUE;
         $mpidlist = "'" . join("','", explode(",",$this->id2)) . "'";
      }
      $this->vwuds_db->querystring = "  select a.*, b.max_annual, b.max_maxday from vwuds_mp_detail as a ";
      $this->vwuds_db->querystring .= " left outer join vwuds_max_action_cached as b ";
      $this->vwuds_db->querystring .= " on (a.\"USERID\" = b.userid ";
      $this->vwuds_db->querystring .= "    AND a.\"MPID\" = b.mpid ";
      $this->vwuds_db->querystring .= "    AND a.\"ACTION\" = b.action";
      $this->vwuds_db->querystring .= " )";
      $this->vwuds_db->querystring .= " where  \"USERID\" = '$this->id1' ";
      if ($hasmps) {
         $this->vwuds_db->querystring .= "    and \"MPID\" in ($mpidlist) ";
      }
      $this->vwuds_db->querystring .= "    and \"ACTION\" = '$this->action' ";
      $this->logDebug("Retrieving MP Info: " . $this->vwuds_db->querystring . " ;<br>");
		
      $this->vwuds_db->performQuery();
      if (count($this->vwuds_db->queryrecords) > 0) {
         $this->wd_info = $this->vwuds_db->queryrecords[0];
         //error_log("Rec: " . print_r($this->wd_info,1));
         // we go ahead and overwrite the value of waterusetype on the object, otherwise,
         // we allow the user-defined value to remain
         $this->waterusetype = $this->wd_info['CAT_MP'];
         $this->wdtype = $this->wd_info['TYPE'];
         $this->vpdes_permitno = $this->wd_info['VPDES'];
         $this->vdh_pwsid = $this->wd_info['VDH_NUM'];
         $this->vwp_permit = $this->wd_info['VWP_PERMIT'];
         $this->system = $this->wd_info['system'];
         $this->ownname = $this->wd_info['ownname'];
         $this->source = $this->wd_info['SOURCE'];
         //error_log("Setting $this->system $this->ownname  $this->source \n");
         $this->max_wd_annual = $this->wd_info['max_annual'];
         $this->max_wd_daily = $this->wd_info['max_maxday'];
      } else {
         $this->wd_info = array();
         $this->logDebug("No records: " . $this->vwuds_db->error . "\n");
      }
      if ($this->debug) {
         //$this->logDebug($this->vwuds_db->querystring);
         $this->logDebug("Basic withdrawal Info: " . print_r($this->wd_info,1) . "<br>");
      }
      
      
      $this->vwuds_db->querystring = "  select sum(max_annual) as max_annual, sum(max_maxday) as max_day from vwuds_max_action ";
      $this->vwuds_db->querystring .= " where userid = '$this->id1' ";
      if ($hasmps) {
         $this->vwuds_db->querystring .= "    and mpid in ($mpidlist) ";
      }
      $this->vwuds_db->querystring .= "    and action = '$this->action' ";
      if ($this->debug) {
         $this->logDebug("Retrieving Maximum withdrwals: " . $this->vwuds_db->querystring . " ;<br>");
      }
      $this->vwuds_db->performQuery();
      if (count($this->vwuds_db->queryrecords) > 0) {
         $this->logDebug("Max query returned: " . count($this->vwuds_db->queryrecords) . " records<br>");
         $maxinfo = $this->vwuds_db->queryrecords;
         $this->max_wd_annual = $this->vwuds_db->getRecordValue(1,'max_annual');
         $this->max_wd_daily = $this->vwuds_db->getRecordValue(1,'max_day');
      } else {
         $this->max_wd_annual = 0;
         $this->max_wd_daily = 0;
         $maxinfo = array('No records returned.');
      }
      if ($this->debug) {
         //$this->logDebug($this->vwuds_db->querystring);
         $this->logDebug("Max withdrawal Info: " . print_r($maxinfo,1) . "<br>");
      }
      
   }
   
   function getConsumptionFractions($thistype = 'OTH') {
      $this->consumption_pct = array();
      $this->vwuds_db->querystring = "  select * from waterusetype ";
      $this->vwuds_db->querystring .= " where typeabbrev = '$thistype' ";
      $this->vwuds_db->performQuery();
      if ($this->debug) {
         $this->logDebug($this->dbobject->querystring);
      }
      $this->consumption_pct = $this->vwuds_db->queryrecords[0];
      if ($this->debug) {
         $this->logDebug("Consumption Info: " . print_r($this->consumption_pct,1));
      }
      
   }
   
   function getTransfers() {
      //get any transfers associated with this MP
   }
   
   function getVPDESInfo() {
      if (!$this->discharge_enabled) {
        return;
      }
      //get any VPDES point source records associated with this MP
      if ( strlen($this->vpdes_permitno) > 0) {
         $this->vpdes_db->querystring = "  select vpdes_permit_no, outfall_no, facility_name, ";
         $this->vpdes_db->querystring .= "    st_y(the_geom) as lat_dd, st_x(the_geom) as lon_dd, asText(the_geom) as wkt_geom ";
         $this->vpdes_db->querystring .= " from vpdes_locations ";
         $this->vpdes_db->querystring .= " where vpdes_permit_no = '$this->vpdes_permitno' ";
         if (strlen($this->vpdes_outfallno) > 0) {
            $this->vpdes_db->querystring .= " AND outfall_no = '$this->vpdes_outfallno' ";
         }
         //$this->vpdes_db->querystring .= " GROUP BY vpdes_permit_no, outfall_no, facility_name ";
         $this->vpdes_db->performQuery();
         if (count($this->vpdes_db->queryrecords) > 0) {
            $this->ps_info = $this->vpdes_db->queryrecords[0];
            // we go ahead and overwrite the value of waterusetype on the object, otherwise,
            // we allow the user-defined value to remain
            $this->vpdes_outfallno = $this->ps_info['outfall_no'];
         } else {
            $this->logError("No VPDES outfall information available for Permit Number $this->vpdes_permitno with outfall: $this->outfall_no <br>\n");
            $this->ps_info = array();
         }
         $this->logDebug($this->vpdes_db->querystring . "<br>");
         $this->logDebug("Consumption Info: " . print_r($this->ps_info,1) . "<br>");
      }
   }
   
   function getVPDESDischarges() {
      //get any discharges associated with this MP
   }
   
   function getCurrentVPDESDischarges() {
      //get any discharges associated with this MP
      if (is_object($this->vpdes_db) and (strlen($this->vpdes_permitno) > 0) ) {
         // try this query, gets a broader data range to accomodate for quarterly reporting
         $vpidlist = "'" . join("','", explode(",",$this->vpdes_permitno)) . "'";
         $this->vpdes_db->querystring = "  select a.thismonth, ";
         $this->vpdes_db->querystring .= " CASE ";
         $this->vpdes_db->querystring .= "    WHEN avg(b.discharge_vpdes_mgd) IS NULL THEN 0.0 ";
         $this->vpdes_db->querystring .= "    ELSE avg(b.discharge_vpdes_mgd) ";
         $this->vpdes_db->querystring .= " END as ps_mgd, count(b.*) as num ";
         $this->vpdes_db->querystring .= " from ( ";
         $this->vpdes_db->querystring .= "    select generate_series as thismonth ";
         $this->vpdes_db->querystring .= "    from generate_series(1,12) ";
         $this->vpdes_db->querystring .= "    order by thismonth ";
         $this->vpdes_db->querystring .= " ) as a left outer join ( ";
         $this->vpdes_db->querystring .= "    SELECT extract(month from mon_startdate) as startmo, ";
         $this->vpdes_db->querystring .= "       extract(month from mon_enddate) as endmo, ";
         $this->vpdes_db->querystring .= "       sum(mean_value) as discharge_vpdes_mgd  ";
         $this->vpdes_db->querystring .= "    FROM vpdes_discharge_no_ms4 ";
         $this->vpdes_db->querystring .= "    WHERE vpdes_permit_no in ($vpidlist) ";
         // this is hard coded to only grab all outfalls for now
         //$this->vpdes_db->querystring .= " AND outfall_no = '$this->vpdes_outfallno' ";
         $this->vpdes_db->querystring .= "       AND mon_startdate >= '$this->vpdes_curr_start' ";
         $this->vpdes_db->querystring .= "       AND mon_startdate <= '$this->vpdes_curr_end'  ";
         // this is hard coded to only grab water for now
         $this->vpdes_db->querystring .= "       AND constit = '001' ";
         $this->vpdes_db->querystring .= "       AND and outfall_no like '0%' ";
         $this->vpdes_db->querystring .= "    GROUP BY mon_startdate, mon_enddate ";
         $this->vpdes_db->querystring .= "    ORDER BY mon_startdate ";
         $this->vpdes_db->querystring .= " ) as b ";
         // uses exact date match, screws up on quarterly reporters
         //$this->vpdes_db->querystring .= " on (a.thisdate = b.thisdate) ";
         // this one tries to stretch quarterly matches out
         $this->vpdes_db->querystring .= " on (a.thismonth >= b.startmo  ";
         $this->vpdes_db->querystring .= "    AND a.thismonth <= b.endmo) ";
         $this->vpdes_db->querystring .= " group by a.thismonth ";
         $this->vpdes_db->querystring .= " order by a.thismonth ";
//error_reporting(E_ALL);
         //if ($this->debug) {
            $this->logDebug("VPDES query: <br>\n");
            $this->logDebug($this->vpdes_db->querystring);
            $this->logDebug("<br>\n");
         //}
         //error_log($this->vpdes_db->querystring);
         $this->vpdes_db->performQuery();
         $this->logDebug($this->vpdes_db->error);
         $this->logDebug("<br>\n");
         $this->logDebug(print_r($this->vpdes_db->queryrecords,1));
         $this->logDebug("<br>\n");
//error_reporting(E_ERROR);


         if (count($this->vpdes_db->queryrecords) > 0) {
            return $this->vpdes_db->queryrecords;
         } else {
            $this->logError("VPDES query for vpdes_permit_no = '$this->vpdes_permitno' returned no data <br>\n");
            if ($this->debug) {
               $this->logDebug("VPDES query returned no data <br>\n");
            }
         }
      } else {
         $this->logDebug("VPDES db object not set. <br>\n");
      }
   }
   
   function getHistoricUse() {
      $this->historic_monthly_pct = array();
      $this->historic_annual = array();
      $this->projected_monthly = array();
      $this->projected_annual = array();
      $this->maxuse_annual = array();
      $this->usetypes = array();
      $this->getWDInfo();
      $hasmps = FALSE;
      if (strlen(trim($this->id2)) > 0) {
         $hasmps = TRUE;
         $mpidlist = "'" . join("','", explode(",",$this->id2)) . "'";
      }
      
      $annual_query = "  select \"YEAR\" as thisyear, ";
      $annual_query .= "    CASE ";
      $annual_query .= "       WHEN sum(\"ANNUAL\") is null THEN 0.0 ";
      $annual_query .= "       ELSE round(sum(\"ANNUAL/365\"*365.0)::numeric,5) ";
      $annual_query .= "    END as total_mg ";
      $annual_query .= " FROM vwuds_annual_mp_data ";
      // by putting this outside of the "ON" clause, we override the left join, only returning these specific uses
      $annual_query .= " WHERE \"USERID\" = '$this->id1' ";
      if ($hasmps) {
         $annual_query .= " AND \"MPID\" in ( $mpidlist ) ";
      }
      $annual_query .= " GROUP BY \"YEAR\"";
      $annual_query .= " ORDER BY \"YEAR\"";
      $this->vwuds_db->querystring = $annual_query;
      $this->logDebug($this->vwuds_db->querystring);
      $this->vwuds_db->performQuery();
      $this->historic_annual = $this->vwuds_db->queryrecords;
      $this->logDebug("WD Info: " . print_r($this->wd_info,1) . "<br>");
      if (!isset($this->wd_info['CAT_MP'])) {
         $thistype = 'OTH';
      } else {
         $thistype = $this->wd_info['CAT_MP'];
      }
      
      $this->vwuds_db->querystring = "  select ";
      $this->vwuds_db->querystring .= "    round(avg(jan)::numeric,4) as jan, round(avg(feb)::numeric,4) as feb, ";
      $this->vwuds_db->querystring .= "    round(avg(mar)::numeric,4) as mar, round(avg(apr)::numeric,4) as apr, ";
      $this->vwuds_db->querystring .= "    round(avg(may)::numeric,4) as may, round(avg(jun)::numeric,4) as jun, ";
      $this->vwuds_db->querystring .= "    round(avg(jul)::numeric,4) as jul, round(avg(aug)::numeric,4) as aug, ";
      $this->vwuds_db->querystring .= "    round(avg(sep)::numeric,4) as sep, round(avg(oct)::numeric,4) as oct, ";
      $this->vwuds_db->querystring .= "    round(avg(nov)::numeric,4) as nov, round(avg(dec)::numeric,4) as dec ";
      $this->vwuds_db->querystring .= " from ( ";
      $this->vwuds_db->querystring .= "    select a.typeabbrev, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"JANUARY\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as jan, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"FEBRUARY\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as feb, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"MARCH\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as mar, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"APRIL\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as apr, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"MAY\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as may, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"JUNE\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as jun, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"JULY\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as jul, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"AUGUST\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as aug, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"SEPTEMBER\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as sep, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"OCTOBER\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as oct, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"NOVEMBER\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as nov, ";
      $this->vwuds_db->querystring .= "    CASE ";
      $this->vwuds_db->querystring .= "       WHEN ( (sum(b.\"ANNUAL\") is null) or (sum(b.\"ANNUAL\") = 0)) THEN 0.0 ";
      $this->vwuds_db->querystring .= "       ELSE ( sum(b.\"DECEMBER\") / sum(b.\"ANNUAL\")) ";
      $this->vwuds_db->querystring .= "    END as dec ";
      $this->vwuds_db->querystring .= " from waterusetype as a left outer join vwuds_annual_mp_data as b ";
      $this->vwuds_db->querystring .= " on (a.typeabbrev = b.\"CAT_MP\" ";
      $this->vwuds_db->querystring .= "     and b.\"USERID\" = '$this->id1' ";
      $this->vwuds_db->querystring .= "     and b.\"ACTION\" = '$this->action' ";
      if ($hasmps) {
         $this->vwuds_db->querystring .= " AND b.\"MPID\" in ( $mpidlist ) ";
      }
      $this->vwuds_db->querystring .= "    ) ";
      $this->vwuds_db->querystring .= " WHERE a.typeabbrev = '" . $thistype . "' ";
      $this->vwuds_db->querystring .= " group by typeabbrev ";
      $this->vwuds_db->querystring .= " order by typeabbrev ";
      $this->vwuds_db->querystring .= " ) as foo ";
      $this->vwuds_db->querystring .= " group by typeabbrev";
      $this->vwuds_db->performQuery();
      $this->logDebug($this->vwuds_db->querystring);
      $this->historic_monthly_pct = $this->vwuds_db->queryrecords;
      
      $this->getConsumptionFractions($thistype);
      
   }
   
   function getWithdrawalLocation() {
      $hasmps = FALSE;
      if (strlen(trim($this->id2)) > 0) {
         $hasmps = TRUE;
         $mpidlist = "'" . join("','", explode(",",$this->id2)) . "'";
      }
      
      if ($hasmps) {
         // get the location as the centroid of valid mps
         $geom_query = "  select asText(centroid(the_geom)) as wkt_geom ";
         $geom_query .= " FROM vwuds_measuring_point ";
         // by putting this outside of the "ON" clause, we override the left join, only returning these specific uses
         $geom_query .= " WHERE \"USERID\" = '$this->id1' ";
         if ($hasmps) {
            $geom_query .= " AND \"MPID\" in ( $mpidlist ) ";
         }
         $geom_query .= " and  geom_valid = 1 ";
         $this->vwuds_db->querystring = $geom_query;
         $this->logDebug($this->vwuds_db->querystring);
         //error_log($this->vwuds_db->querystring);
         $this->vwuds_db->performQuery();
         if ($this->vwuds_db->numrows > 0) {
            $this->the_geom = $this->vwuds_db->getRecordValue(1,'wkt_geom');
            return $this->the_geom;
         } else {
            return FALSE;
         }
      }
   }
   
   
   function getHistoricDischarges() {
      if ($this->discharge_enabled) {
        $this->getVPDESInfo();
      }
      $vpidlist = "'" . join("','", explode(",",$this->vpdes_permitno)) . "'";
   }
   
   function summarizeWithdrawals() {
      // This is no longer valid since this is now an XML connection and the tables and dbconn's are not set up
      
      /*
      // parent routines grab all data, now do summary queries to
      // the following summaries should be generated by the XML object:
      // historic annual totals, by year and use_type (row - type, column - year)
      $this->dbobj->querystring = "  select a.cat_mp, sum(a.max_annual) as max_annual, ";
      $this->dbobj->querystring .= "    sum(a.max_annual/365.0) as max_mgd ";
      $this->dbobj->querystring .= " FROM $dbt as a, vwuds_max_withdrawal as b ";
      $this->dbobj->querystring .= " where a.mpid = b.mpid ";
      // currently, the vwuds_max withdrawal table holds only that, withdrawals, but at some point
      // it, or a table that would be more properly known as vwuds_max_annual would contain 
      // the data pertaining to transfers and the like
      $this->dbobj->querystring .= " group by a.cat_mp ";
      if ($this->debug) {
         $this->logDebug("$this->dbobj->querystring ; <br>");
      }
      $this->dbobj->performQuery();
      // historica monthly mean percent of annual by use_type (row - type, column - month)
      // update other components, such as the summary data, and the category multipliers
      $dbt = $this->dbtable;
      $this->dbobj->querystring = "  select a.cat_mp, sum(a.max_annual) as max_annual, ";
      $this->dbobj->querystring .= "    sum(a.max_annual/365.0) as max_mgd ";
      $this->dbobj->querystring .= " FROM $dbt as a, vwuds_max_withdrawal as b ";
      $this->dbobj->querystring .= " where a.mpid = b.mpid ";
      // currently, the vwuds_max withdrawal table holds only that, withdrawals, but at some point
      // it, or a table that would be more properly known as vwuds_max_annual would contain 
      // the data pertaining to transfers and the like
      $this->dbobj->querystring .= " group by a.cat_mp ";
      if ($this->debug) {
         $this->logDebug("$this->dbobj->querystring ; <br>");
      }
      $this->dbobj->performQuery();
      
      */
   }

   
   function loadVPDESData() {
     
      if (!$this->discharge_enabled) {
        return;
      }
      if (is_object($this->timer)) {
         if (is_object($this->timer->thistime)) {
            $startdate = $this->timer->thistime->format('Y-m-d');
            $startyear = $this->timer->thistime->format('Y');
         } else {
            $this->logError("startdate object not set - VPDES query not created.<br>\n");
            return;
         }
         if (is_object($this->timer->endtime)) {
            $enddate = $this->timer->endtime->format('Y-m-d');
            $endyear = $this->timer->endtime->format('Y');
         } else {
            $this->logError("enddate object not set - VPDES query not created.<br>\n");
            return;
         }
         
         $vpidlist = "'" . join("','", explode(",",$this->vpdes_permitno)) . "'";
         /*
         $this->vpdes_db->querystring = "  select a.thisdate, b.discharge_vpdes_mgd ";
         $this->vpdes_db->querystring .= " from ( ";
         $this->vpdes_db->querystring .= "    select (a.thisyear || '-' || b.thismonth || '-01')::date as thisdate, null as thisvalue ";
         $this->vpdes_db->querystring .= "    from ( ";
         $this->vpdes_db->querystring .= "       select generate_series as thisyear ";
         $this->vpdes_db->querystring .= "       from generate_series($startyear,$endyear) ";
         $this->vpdes_db->querystring .= "    ) as a, ( ";
         $this->vpdes_db->querystring .= "       select generate_series as thismonth ";
         $this->vpdes_db->querystring .= "       from generate_series(1,12) ";
         $this->vpdes_db->querystring .= "    ) as b ";
         $this->vpdes_db->querystring .= "    order by a.thisyear, b.thismonth ";
         $this->vpdes_db->querystring .= " ) as a left outer join ( ";
         $this->vpdes_db->querystring .= "    SELECT mon_startdate as thisdate, ";
         $this->vpdes_db->querystring .= "       sum(mean_value) as discharge_vpdes_mgd  ";
         $this->vpdes_db->querystring .= "    FROM vpdes_discharge_no_ms4 ";
         $this->vpdes_db->querystring .= "    WHERE vpdes_permit_no in ($vpidlist) ";
         // this is hard coded to only grab all outfalls for now
         //$this->vpdes_db->querystring .= " AND outfall_no = '$this->vpdes_outfallno' ";
         $this->vpdes_db->querystring .= "       AND mon_startdate >= '$startdate' ";
         $this->vpdes_db->querystring .= "       AND mon_startdate <= '$enddate' ";
         // this is hard coded to only grab water for now
         $this->vpdes_db->querystring .= "       AND constit = '001' ";
         $this->vpdes_db->querystring .= "    GROUP BY mon_startdate ";
         $this->vpdes_db->querystring .= "    ORDER BY mon_startdate ";
         $this->vpdes_db->querystring .= " ) as b ";
         $this->vpdes_db->querystring .= " on (a.thisdate = b.thisdate) ";
         $this->vpdes_db->querystring .= " WHERE a.thisdate >= '$startdate' ";
         $this->vpdes_db->querystring .= "    AND a.thisdate <= '$enddate' ";
         $this->vpdes_db->querystring .= " order by a.thisdate ";
         */
         
         // try this query, gets a broader data range to accomodate for quarterly reporting
         $this->vpdes_db->querystring = "  select a.thisdate, b.discharge_vpdes_mgd ";
         $this->vpdes_db->querystring .= " from ( ";
         $this->vpdes_db->querystring .= "    select (a.thisyear || '-' || b.thismonth || '-01')::date as thisdate, null as thisvalue ";
         $this->vpdes_db->querystring .= "    from ( ";
         $this->vpdes_db->querystring .= "       select generate_series as thisyear ";
         $this->vpdes_db->querystring .= "       from generate_series($startyear - 1,$endyear + 1) ";
         $this->vpdes_db->querystring .= "    ) as a, ( ";
         $this->vpdes_db->querystring .= "       select generate_series as thismonth ";
         $this->vpdes_db->querystring .= "       from generate_series(1,12) ";
         $this->vpdes_db->querystring .= "    ) as b ";
         $this->vpdes_db->querystring .= "    order by a.thisyear, b.thismonth ";
         $this->vpdes_db->querystring .= " ) as a left outer join ( ";
         $this->vpdes_db->querystring .= "    SELECT mon_startdate, mon_enddate, ";
         $this->vpdes_db->querystring .= "       sum(mean_value) as discharge_vpdes_mgd  ";
         $this->vpdes_db->querystring .= "    FROM vpdes_discharge_no_ms4 ";
         $this->vpdes_db->querystring .= "    WHERE vpdes_permit_no in ($vpidlist) ";
         // this is hard coded to only grab all outfalls for now
         //$this->vpdes_db->querystring .= " AND outfall_no = '$this->vpdes_outfallno' ";
         $this->vpdes_db->querystring .= "       AND mon_startdate >= ( '$startdate'::date - interval '3 months' ) ";
         $this->vpdes_db->querystring .= "       AND mon_startdate <= ( '$enddate'::date + interval '3 months')  ";
         // this is hard coded to only grab water for now
         $this->vpdes_db->querystring .= "       AND constit = '001' ";
         $this->vpdes_db->querystring .= "    GROUP BY mon_startdate, mon_enddate ";
         $this->vpdes_db->querystring .= "    ORDER BY mon_startdate ";
         $this->vpdes_db->querystring .= " ) as b ";
         // uses exact date match, screws up on quarterly reporters
         //$this->vpdes_db->querystring .= " on (a.thisdate = b.thisdate) ";
         // this one tries to stretch quarterly matches out
         $this->vpdes_db->querystring .= " on (a.thisdate >= b.mon_startdate  ";
         $this->vpdes_db->querystring .= "    AND a.thisdate <= b.mon_enddate) ";
         $this->vpdes_db->querystring .= " WHERE a.thisdate >= ( '$startdate'::date - interval '3 months' ) ";
         $this->vpdes_db->querystring .= "    AND a.thisdate <= ( '$enddate'::date + interval '3 months') ";
         $this->vpdes_db->querystring .= " order by a.thisdate ";
         //$this->logError("VPDES query for vpdes_permit_no = '$this->vpdes_permitno':<br>\n");
         //$this->logError($this->vpdes_db->querystring);
         //$this->logError("<br>\n");
         
         /*
         // try this query, just gives VPDES records
         $this->vpdes_db->querystring = "  select a.thisdate, b.discharge_vpdes_mgd ";
         $this->vpdes_db->querystring .= " from ( ";
         $this->vpdes_db->querystring .= "    SELECT mon_startdate as thisdate, ";
         $this->vpdes_db->querystring .= "       sum(mean_value) as discharge_vpdes_mgd  ";
         $this->vpdes_db->querystring .= "    FROM vpdes_discharge_no_ms4 ";
         $this->vpdes_db->querystring .= "    WHERE vpdes_permit_no in ($vpidlist) ";
         // this is hard coded to only grab all outfalls for now
         //$this->vpdes_db->querystring .= " AND outfall_no = '$this->vpdes_outfallno' ";
         $this->vpdes_db->querystring .= "       AND mon_startdate >= ( '$startdate'::date - interval '3 months' ) ";
         $this->vpdes_db->querystring .= "       AND mon_startdate <= ( '$enddate'::date + interval '3 months')  ";
         // this is hard coded to only grab water for now
         $this->vpdes_db->querystring .= "       AND constit = '001' ";
         $this->vpdes_db->querystring .= "    GROUP BY mon_startdate ";
         $this->vpdes_db->querystring .= "    ORDER BY mon_startdate ";
         $this->vpdes_db->querystring .= " ) as b ";
         $this->vpdes_db->querystring .= " WHERE b.thisdate >= ( '$startdate'::date - interval '3 months' ) ";
         $this->vpdes_db->querystring .= "    AND b.thisdate <= ( '$enddate'::date + interval '3 months') ";
         $this->vpdes_db->querystring .= " order by a.thisdate ";
         */
         
         if ($this->debug) {
            $this->logDebug("VPDES query: <br>\n");
            $this->logDebug($this->vpdes_db->querystring);
            $this->logDebug("<br>\n");
         }
         $this->vpdes_db->performQuery();

         if (count($this->vpdes_db->queryrecords) > 0) {
            $this->addArrayData($this->vpdes_db->queryrecords, 'thisdate', array('discharge_vpdes_mgd'));
         } else {
            $this->logError("VPDES query for vpdes_permit_no = '$this->vpdes_permitno' returned no data <br>\n");
            if ($this->debug) {
               $this->logDebug("VPDES query returned no data <br>\n");
            }
         }
      
      } else {
         if ($this->debug) {
            $this->logDebug("Timer object not set - query not created.<br>\n");
         }
         $this->logError("Timer object not set - VPDES query not created.<br>\n");
      }
      
      // add this data to a time series file
   }

   
   function loadVWUDSData() {
   
      if (is_object($this->timer)) {
         if (is_object($this->timer->thistime)) {
            $startdate = $this->timer->thistime->format('Y-m-d');
            $startyear = $this->timer->thistime->format('Y');
         } else {
            $this->logError("startdate object not set - VPDES query not created.<br>\n");
            return;
         }
         if (is_object($this->timer->endtime)) {
            $enddate = $this->timer->endtime->format('Y-m-d');
            $endyear = $this->timer->endtime->format('Y');
         } else {
            $this->logError("enddate object not set - VPDES query not created.<br>\n");
            return;
         }
         
         $hasmps = FALSE;
         if (strlen(trim($this->id2)) > 0) {
            $hasmps = TRUE;
            $mpidlist = "'" . join("','", explode(",",$this->id2)) . "'";
         }

         $this->vwuds_db->querystring = "  select a.thisdate, CASE WHEN b.historic_mgd is NULL THEN 0.0 ELSE b.historic_mgd END as historic_mgd ";
         $this->vwuds_db->querystring .= " from ( ";
         $this->vwuds_db->querystring .= "    select (a.thisyear || '-' || b.thismonth || '-01')::date as thisdate, null as thisvalue ";
         $this->vwuds_db->querystring .= "    from ( ";
         $this->vwuds_db->querystring .= "       select generate_series as thisyear ";
         $this->vwuds_db->querystring .= "       from generate_series($startyear,$endyear) ";
         $this->vwuds_db->querystring .= "    ) as a, ( ";
         $this->vwuds_db->querystring .= "       select generate_series as thismonth ";
         $this->vwuds_db->querystring .= "       from generate_series(1,12) ";
         $this->vwuds_db->querystring .= "    ) as b ";
         $this->vwuds_db->querystring .= "    order by a.thisyear, b.thismonth ";
         $this->vwuds_db->querystring .= " ) as a left outer join ( ";
         $this->vwuds_db->querystring .= "    select thisdate, sum(wd_mgd) as historic_mgd ";
         $this->vwuds_db->querystring .= "    from vwuds_monthly_data ";
         $this->vwuds_db->querystring .= "    where userid = '$this->id1' ";
         if ($hasmps) {
            $this->vwuds_db->querystring .= "       AND mpid in ($mpidlist) ";
         }
         $this->vwuds_db->querystring .= "       and thisdate >= '$startdate'";
         $this->vwuds_db->querystring .= "       and thisdate <= '$enddate'";
         $this->vwuds_db->querystring .= "    group by thisdate ";
         $this->vwuds_db->querystring .= "    order by thisdate ";
         $this->vwuds_db->querystring .= " ) as b ";
         $this->vwuds_db->querystring .= " on (a.thisdate = b.thisdate) ";
         $this->vwuds_db->querystring .= " WHERE a.thisdate >= '$startdate' ";
         $this->vwuds_db->querystring .= "    AND a.thisdate <= '$enddate' ";
         $this->vwuds_db->querystring .= " order by a.thisdate ";
         //if ($this->debug) {
            $this->logDebug("VWUDS query:<br>\n");
            $this->logDebug($this->vwuds_db->querystring);
            $this->logDebug("<br>\n");
         //}
         $this->vwuds_db->performQuery();
         //$this->logError("VWUDS query for userid = '$this->id1' AND mpid in ($mpidlist):<br>\n");
         //$this->logError($this->vwuds_db->querystring);
         //$this->logError("<br>\n");
         if (count($this->vwuds_db->queryrecords) > 0) {
            $this->addArrayData($this->vwuds_db->queryrecords, 'thisdate');
         } else {
            $this->logError("VWUDS query for userid = '$this->id1' AND mpid in ($mpidlist) returned no data <br>\n");
            if ($this->debug) {
               $this->logDebug("VWUDS query returned no data <br>\n");
               $this->logDebug($this->vwuds_db->querystring);
               $this->logDebug("<br>\n");
            }
         }
      
      } else {
         if ($this->debug) {
            $this->logDebug("Timer object not set - query not created.<br>\n");
         }
         $this->logError("Timer object not set - VWUDS query not created.<br>\n");
      }
      
      // add this data to a time series file
   }



   function addArrayData($theserecs, $tcol) {
      # expects an associative array in the format of the listobject queryrecords
      if ($this->debug) {
         $this->logDebug("Adding " . count($theserecs) . ' records <br>');
      }
      foreach ($theserecs as $thisrec) {
         if (in_array($tcol, array_keys($thisrec))) {
            $ts = $thisrec[$tcol];
            $this->addValue($ts, $thisrec);
            #break;
         } else {
            if ($this->debug) {
               $this->logDebug("Error: Date column $tcol does not exist.<br>");
            }
         }
      }
   }
   
}


class cova_watershedContainerLink extends textField {
   // this is a simple object that contains a reference to another model object
   // or to an NHD+ watershed
   var $set_parent_geom = 0;
   var $parent_geom_type = 1; // 0 - set point based on the latdd/londd values; 1 - set polygon geometry based on the remote geometry retrieved
   var $parent_elementid = -1;
   var $latdd = 37.0;
   var $londd = -80.0;
   // should have a customformview and then they will automatically choose this.
   
   function wake() {
      parent::wake();
      // get parent geometry
      //error_log("*****************************************");
      //error_log("$this->name - Handling geometry retrieval operations");
      //error_log("*****************************************");
      $this->getParentGeometry();
      $this->setParentGeometry();
   }
   
   function sleep() {
      parent::sleep();
      // nullify geometry?
   }
   
   function setState() {
      parent::setState();
      $this->wvars[] = 'the_geom'; // need to supress logging of this quantity
      $this->setSingleDataColumnType('the_geom', null, $this->the_geom , 0);
      $this->initOnParent();
   }
   
   function init() {
      parent::init();
      //$this->parentobject->setSingleDataColumnType($this->getParentVarName('the_geom'), 'null', NULL, 0, 1);
   }
   
   function setParentGeometry() {
      //error_log("*****************************************");
      //error_log("Checking for over-ride on parent geometry");
      //error_log("*****************************************");
      if ($this->set_parent_geom) {
         if (is_object($this->parentobject)) {
            if ($this->debug) {
               $this->logDebug("At Time = " . time() . "Over-riding Parent geometry with " . $this->the_geom);
            }
            switch ($this->parent_geom_type) {
               case 0:
               $this->parentobject->the_geom = "POINT($this->londd $this->latdd)";
               break;
            
               default:
               $this->parentobject->the_geom = $this->the_geom;
               break;
            }
         }
      } 
      // initialize state variable for geometry
      $this->setStateVar('the_geom', $this->the_geom);
      $this->writeToParent(array('the_geom'), 1);
   }
   
   function getParentGeometry() {
      if (is_object($this->parentobject)) {
         $this->latdd = $this->parentobject->getProp('wd_lat', 'equation');
         $this->londd = $this->parentobject->getProp('wd_lon', 'equation');
         $locid = $this->value;
         //error_log("Location object $this->name has locid = $locid");
         $scenarioid = 37;
         //$debug = 1;
         // this needs to be made more generic, whereby the component is a generic remote container
         // could screen on objectclass, custom1, custom2, and scenarioid to find those that contain it
         $options = findCOVALocationPossibilities($this->listobject, $scenarioid, $this->latdd, $this->londd, $debug);
         //error_log("Found " . count($options) . " possible model locations");
         foreach ($options as $thisoption) {
            $contid = $thisoption['id'];
            $type = $thisoption['type'];
            $radval = $type . $contid;
            //error_log("Checking for ($radval == $this->value) ");
            if ($radval == $this->value) {
               $this->the_geom = $thisoption['the_geom'];
               $this->parent_elementid = $contid;
               $thisoption['the_geom'] = substr($thisoption['the_geom'],0,128);
               $this->logDebug("Found matching watershed container: " . print_r($thisoption,1));
            }
         }
      }
   }
   
   function showElementInfo($propname, $view='info', $params = array()) {
      $view = trim($view);
      $propname = trim($propname);
      $localviews = array('cova_locator');
      $output = '';
      if ($this->debug) {
         //error_log("$this->name showElementInfo called with view = '$view' and propname '$propname' ");
         $output .= "$this->name showElementInfo called with view = '$view' and propname '$propname' ";
      }
      if ( ($propname == '') or ($propname == 'value') ) {
         if (in_array($view, $localviews)) {
            switch ($view) {
               case 'cova_locator':
               if ($this->debug) {
                  error_log("calling showMapLocator () ");
               }
               $output .= $this->showMapLocator();
               if ($this->debug) {
                  error_log("done showMapLocator () ");
               }
               break;
            }
         } else {
            return parent::showElementInfo($propname, $view, $params);
         }
      } else {
         return parent::showElementInfo($propname, $view, $params);
      }
      if ($this->debug) {
         error_log("Returning $output ");
      }
      return $output;
      
   }
      
               
   function showMapLocator() {
      global $usgsdb;
      $locHTML = '';
      if ($this->debug) {
         if (!is_object($this->parentobject)) {
            $locHTML .= "Parent Object for $this->name does not exist <br>";
         } else {
            $locHTML .= "Parent Object " . $this->parentobject->name . " for $this->name Found <br>";
         }
      }
      $latdd = $this->parentobject->getProp('wd_lat', 'equation');
      $londd = $this->parentobject->getProp('wd_lon', 'equation');
      $locid = $this->value;
      if ($this->debug) { 
         error_log("Getting location for coords $latdd - $londd = ( current: $locid ) ");
         $locHTML .= "Getting location for coords $latdd - $londd = ( current: $locid ) ";
      }
      
      $cia_container = -1; // only set this if contid indicates an existing, COVA model object
      $scenarioid = 37; // the cova framework for chooseing locations
      if (function_exists('findCOVALocationPossibilities') ) {
         $options = findCOVALocationPossibilities($this->listobject, $scenarioid, $latdd, $londd, $debug);
         $locHTML .= "<div class='insetBox' width=320px>";
         //$locHTML .= "<table><tr>";
         //$locHTML .= "<td>";
         $locHTML .= "<ul>";
         $cdel = '';
         $adminsetup = array('table info'=>array(), 'column info'=>array());
         $adminsetup['column info']['locid'] = array("type"=>-1,"params"=>"","label"=>"Loc ID? ","visible"=>1, "readonly"=>1, "width"=>6);
         $adminsetup['column info']['locid']['type'] = 23;
         
         foreach ($options as $thisoption) {
            $contid = $thisoption['id'];
            $type = $thisoption['type'];
            $lname = $thisoption['name'];
            $area = round($thisoption['cumulative_area'],1);
            $radval = $type . $contid;
            if ($radval == $locid) {
               // set the location of the map to the selected
               $mapurl = 'http://deq1.bse.vt.edu/cgi-bin/mapserv?map=/var/www/html/om/nhd_tools/nhd_cbp_small.map&layers=nhd_fulldrainage%20poli_bounds%20proj_seggroups&mode=map&mapext=' . $box . '&mode=indexquerymap&';
               switch ($type) {
                  case 'cova_ws_container':
                     $mapurl .= "elementid=$contid";
                  break;
                  
                  case 'nhd+':
                     $mapurl .= "compid=$contid";
                  break;
               }
               $thisrec['watershed_map'] = $mapurl;
               // the selected object is a watershed container, so set cia_container
               $cia_container = $contid;
            }
            $checkpair = '';
            if ( ($contid > 0) and ($type <> 'nhd+') ) {
               $label = "VAHydro Main Stem Segment $lname - $area sqmi.<a href='/om/summary/cova_model_infotab.php?elementid=$contid' target='_new'>CIA Info</a>";
               // get map extent
               $ext = getGroupExtents($this->listobject, 'scen_model_element', 'poly_geom', '', '', "elementid=$contid", 0.15, $debug);
               //print("Geometry extent returned: $ext <br>");
               // gmap view
               list($lon1,$lat1,$lon2,$lat2) = explode(',',$ext);
               $mapurl = "//deq1.bse.vt.edu/om/nhd_tools/gmap_test.php?lon1=$lon1&lat1=$lat1&lon2=$lon2";
               $mapurl .= "&lat2=$lat2&elementid=$contid";
               $onclick = "document.getElementById(\"watershed_map\").src=\"$mapurl\"";
            } else {
               $label = "NHD+ Segment $contid - $area sqmi. ";
               if (!checkNHDBasinShape($usgsdb, $contid)) {
                  $result = createMergedNHDShape($usgsdb,$contid, $debug);
               }
               $ext = getGroupExtents($usgsdb, 'nhd_fulldrainage', 'the_geom', '', '', "comid=$contid", 0.15, 0);
               print("Geometry extent returned: $ext <br>");
               // gmap view
               list($lon1,$lat1,$lon2,$lat2) = explode(',',$ext);
               $mapurl = "//deq1.bse.vt.edu/om/nhd_tools/gmap_test.php?lon1=$lon1&lat1=$lat1&lon2=$lon2&lat2=$lat2&comid=$contid";
               $onclick = "document.getElementById(\"watershed_map\").src=\"$mapurl\"";
            }
            if ($radval == $locid) {
               $thisrec['watershed_map'] = $mapurl;
            }
            $locHTML .= "<li>" . showRadioButton($this->name, $radval, $locid, $onclick, 1, 0, '') . $label . "</li>";
         }
         $locHTML .= "</ul>";
        //$locHTML .= "</td><td>";
         $locHTML .= "<iframe src='" . $thisrec['watershed_map'] . "' id='watershed_map' width=260 height=260 ></iframe>";
         //$locHTML .= "</td></tr></table>";
         $locHTML .= "</div>";
         
        
      } else {
         if ($userid == 1) {
            $locHTML .= "getCOVACBPPointContainer does not exist<br>";
         }
      }
      
      return $locHTML;
   }
}
?>
