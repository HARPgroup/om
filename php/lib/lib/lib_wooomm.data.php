<?php

class runVariableStorageObject extends modelSubObject {
   var $reporting_frequency = 'single';
   var $reporting_trigger = 'end_of_simulation';
   var $temporal_res = '';
   var $dataname = '';
   var $master_db;
   var $runid = -1;
   var $elementid = -1;
   var $update_mode = 'all'; // options - all, period
   
   function step() {
      parent::step();
      // for now, only end of simulation reporting is enabled, so there is nothing to do here
   }
   
   function finish() {
      error_log("$this->name finish()");
      switch ($this->reporting_trigger) {
         case 'end_of_simulation':
            $this->stashValue($this->arData[$this->dataname], '', $this->starttime, $this->endtime);
         break;
      }
      parent::finish();
   }
   
   function init() {
      error_log("$this->name init()");
      parent::init();
      $this->runid = $this->parentobject->runid;
      $this->elementid = $this->parentobject->componentid;
      $this->clearValues();
   }
   
   function clearValues() {
      error_log("$this->name clearValues()");
      if (is_object($this->master_db)) {
         // Clear old values
         // store runid, elementid, variable name, reporting_frequency, reporting_trigger, variable value value_text, value_float
         $this->master_db->querystring = "  delete from scen_model_run_data ";
         $this->master_db->querystring .= " where runid = $this->runid ";
         $this->master_db->querystring .= "     AND elementid = $this->elementid ";
         $this->master_db->querystring .= "     AND temporal_res = '$this->temporal_res' ";
         $this->master_db->querystring .= "     AND reporting_frequency = '$this->reporting_frequency' ";
         // need to think this through, multiple reportiong instances in a single run might
         // require special handling
         // also, things like water year reporting resolution might want to allow storage of several
         // iterations
         // this will vary depending on the combo, for example, we might wish to run a model over seveal 
         // years but then only report the water year totals from the final year.  gotta think this through
         if ($this->update_mode == 'period') {
            $this->master_db->querystring .= "     AND starttime >= '$this->starttime' ";
            $this->master_db->querystring .= "     AND endtime <= '$this->$endtime' ";
         }
         
         switch ($this->temporal_res) {
            case 'water_year':
            $swyear = date( 'Y', strtotime($this->starttime)) + 1;
            $ewyear = date( 'Y', strtotime($this->endtime));
            switch ($this->reporting_frequency) {
               case 'single':
               $this->master_db->querystring .= "     AND extract(year from model_report_time) = $ewyear ";
               break;
               
               case 'multiple':
               $this->master_db->querystring .= "     AND extract(year from model_report_time) >= $swyear ";
               $this->master_db->querystring .= "     AND extract(year from model_report_time) <= $ewyear ";
            }
         }
         
         $this->master_db->querystring .= "     AND dataname = '$this->dataname' ";
         if ($this->debug) {
            error_log($this->master_db->querystring . " ; ");
         }
         $this->master_db->performQuery();
      }
   }
   
   function clearAllValues($runid = '') {
      error_log("$this->name clearAllValues()");
      if (is_object($this->master_db)) {
         // Clear old values
         // store runid, elementid, variable name, reporting_frequency, reporting_trigger, variable value value_text, value_float
         $this->master_db->querystring = "  delete from scen_model_run_data ";
         $this->master_db->querystring .= " where elementid = $this->elementid ";
         if ($runid <> '') {
            $this->master_db->querystring .= "    AND runid = $this->runid ";
         }
         if ($this->debug) {
            error_log($this->master_db->querystring . " ; ");
         }
         $this->master_db->performQuery();
      }
   }
   
   function stashValue($dataval = null, $datatext = '', $starttime = '', $endtime = '', $model_time = '') {
      error_log("$this->name stashValue($dataval, '$datatext', '$starttime', '$endtime')");
      if ($model_time == '') {
         if (is_object($this->timer)) {
            $model_time = $this->timer->timestamp;
         } else {
            $model_time = time();
         }
      }
      if (is_object($this->master_db)) {
         // store runid, elementid, variable name, reporting_frequency, reporting_trigger, variable value value_text, value_float
         $this->master_db->querystring = "  insert into scen_model_run_data (runid, elementid, temporal_res, reporting_frequency, ";
         $this->master_db->querystring .= "     model_report_time, ";
         if ($starttime <> '') {
            $this->master_db->querystring .= " starttime, ";
         }
         if ($endtime <> '') {
            $this->master_db->querystring .= " endtime, ";
         }
         $this->master_db->querystring .= " dataname, dataval, datatext)";
         $this->master_db->querystring .= " values ($this->runid, $this->elementid, ";
         $this->master_db->querystring .= " '$this->temporal_res', '$this->reporting_frequency', ";
         $this->master_db->querystring .= " '$model_time', ";
         if ($starttime <> '') {
            $this->master_db->querystring .= "     '$starttime', ";
         }
         if ($endtime <> '') {
            $this->master_db->querystring .= "     '$endtime', ";
         }
         $this->master_db->querystring .= "    '$this->dataname', $dataval, '$datatext') ";
         if ($this->debug) {
            error_log($this->master_db->querystring . " ; ");
         }
         $this->master_db->performQuery();
      }
   }
   
   function retrieveValue($dataval = null, $datatext = '', $starttime = '', $endtime = '', $model_time = '') {
      error_log("$this->name retrieveValue($dataval, '$datatext', '$starttime', '$endtime')");
      
      if (is_object($this->master_db)) {
         // store runid, elementid, variable name, reporting_frequency, reporting_trigger, variable value value_text, value_float
         $this->master_db->querystring = "  select * from scen_model_run_data runid, elementid, temporal_res, reporting_frequency, ";
         $this->master_db->querystring .= "  where runid = $this->runid ";
         $this->master_db->querystring .= "     and elementid = $this->elementid ";
         $this->master_db->querystring .= "     and temporal_res = '$this->temporal_res' ";
         $this->master_db->querystring .= "     and reporting_frequency = '$this->reporting_frequency' ";
         if ($model_time <> '') {
            $this->master_db->querystring .= "     and model_report_time = '$model_time' ";
         }
         if ($starttime <> '') {
            $this->master_db->querystring .= "    and starttime = '$starttime' ";
         }
         if ($endtime <> '') {
            $this->master_db->querystring .= "    and endtime = '$endtime' ";
         }
         $this->master_db->querystring .= "    and dataname = '$this->dataname' ";
         if ($this->debug) {
            error_log($this->master_db->querystring . " ; ");
         }
         $this->master_db->performQuery();
      }
   }
   
   function sleep() {
      $this->master_db = -1;
      parent::sleep();
   }
}

class dataTableComparisonObject extends modelSubObject {
   // creates facility for comparing multiple versions of a related data set
   // connects to model run data storage tables
   var $tbl_prefix = 'tbl_';
   
   function getRunVariableList($runids = array(), $var_subset = array()) {
      // var_subset - an array of var names to use instead of the whole list, makes for more compact, efficient queries
      // returns a list of variable and table names
      if (count($var_subset) == 0) {
         if (is_object($this->parentobject)) {
            $var_subset = $this->parentobject->getPublicVars();
         }
      }
      $c_info = array('all_columns' => array());
      $tlist = array();
      foreach ($runids as $thisrun) {
         $tlist[$thisrun] = array('table_name' => "$this->tbl_prefix$ti");
         foreach ($var_subset as $thisvar) {
            $c_info[$thisrun][$thisvar] = array('var_name' => "$thisvar" . "_$thisrun", 'column_alias'=> ".\"$thisvar\" as \"$thisvar" . "_$thisrun\"");
            $c_info['all_columns'][] = $c_info[$thisrun][$thisvar]['var_name'];
         }
      }
      $final = array('table_list'=>$tlist, 'column_info'=>$c_info);
      return $final;
   }
}

?>