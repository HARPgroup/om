<?php

module_load_include('inc', 'dh', 'plugins/dh.display');
module_load_include('module', 'dh');
// make sure that we have base plugins 
$plugin_def = ctools_get_plugins('dh', 'dh_variables', 'dHOMmodelElement');
$class = ctools_plugin_get_class($plugin_def, 'handler');

class dHOMwaterSupplyModelNode extends dHOMModelElement {
  // takes over some duties from wsp_vpdesvwuds, and wsp_waterUser
  //   since most of their function was bacjend database connections 
  // See getDefaults() for default subcomps and properties
  var $object_class = 'waterSupplyModelNode';
  var $attach_method = 'contained';
  var $om_template_id = 176615; // remote server template ID, set FALSE if not used.
  
  public function hiddenFields() {
    $hidden = array_merge(array('propvalue'), parent::hiddenFields());
    return $hidden;
  }

  public function getPublicProps($entity) {
    // gets only properties that are visible (must be manually defined for now, could allow this to be set later)
    // taken directly from om library -- will revisit after full porting
    // children will subclass this and add their own like:
    // $publix = parent::getPublicProps($entity)
    $publix = parent::getPublicProps($entity) + array('drought_status');
    return $publix;
  }
  
  public function getDefaults($entity, &$defaults = array()) {
    parent::getDefaults($entity, $defaults);
    // @tbd: 
    $defaults = array(
      'riverseg' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'riverseg',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'riverseg',
        'vardesc' => 'riverseg.',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ), 
      'run_mode' => array(
        'entity_type' => $entity->entityType(),
        'propvalue_default' => 2, // default to "current" mode 
        'propname' => 'run_mode',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Default Run Mode, if not over-ridden by sub-props, globals, or broadcast.',
        'varname' => 'Run Mode default',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'flow_mode' => array(
        'entity_type' => $entity->entityType(),
        'propvalue_default' => 3, // default to "CBP Phase 5.3" mode 
        'propname' => 'flow_mode',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Default Flow Mode, if not over-ridden by sub-props, globals, or broadcast.',
        'varname' => 'Flow Mode default',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
    ) + $defaults;
    //dpm($defaults,'defs');
    return $defaults; 
  }
  
}

// changed parent cause it wasn't embedding 
//class dHOMWaterSystemObject extends dHOMModelContainer {
class dHOMWaterSystemObject extends dHOMModelElement {
  // takes over some duties from wsp_vpdesvwuds, and wsp_waterUser
  //   since most of their function was bacjend database connections 
  // See getDefaults() for default subcomps and properties
  var $object_class = 'waterSupplyElement';
  var $attach_method = 'contained';
  var $om_template_id = 340402; // remote server template ID, set FALSE if not used.
  var $use_new_save = TRUE;
  var $json2d = TRUE;
  
  public function hiddenFields() {
    $hidden = array_merge(array('propvalue'), parent::hiddenFields());
    return $hidden;
  }

  public function getPublicProps($entity) {
    // gets only properties that are visible (must be manually defined for now, could allow this to be set later)
    // taken directly from om library -- will revisit after full porting
    // children will subclass this and add their own like:
    // $publix = parent::getPublicProps($entity)
    $publix = array_merge(parent::getPublicProps($entity), array('drought_status'));
    //dpm($publix,'publix');
    return $publix;
  }
  
  public function getDefaults($entity, &$defaults = array()) {
    parent::getDefaults($entity, $defaults);
    // @tbd: 
    // - historic_monthly_pct
    // - historic_annual 
    // - consumption
    // - surface_mgd : an equation that always equals wd_mgd, since these are all ssumed to be intakes not wells
    $defaults = array(
      'riverseg' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'riverseg',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'riverseg',
        'vardesc' => 'riverseg.',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ), 
      'fac_current_mgy' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => '0.0',
        'propvalue_default' => 0.0,
        'propname' => 'fac_current_mgy',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Facility Current MGY',
        'vardesc' => 'Total current average annual demand for the facility associated with this mode.',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ), 
      'current_mgy' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => '0.0',
        'propvalue_default' => 0.0,
        'propname' => 'current_mgy',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Intake Current MGY',
        'vardesc' => 'Intake specific current average annual demand.',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ), 
      'current_mgd' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => 'current_mgy * historic_monthly_pct / modays',
        'propvalue_default' => 0.0,
        'propname' => 'current_mgd',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Intake Current MGD',
        'vardesc' => 'Intake specific average daily demand.',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ), 
      'wsp2020_2020_mgy' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => '0.0',
        'propvalue_default' => 0.0,
        'propname' => 'wsp2020_2020_mgy',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'WSP Facility 2020 MGY',
        'vardesc' => 'WSP estimated current average annual demand in 2020.',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ), 
      'wsp2020_2040_mgy' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => '0.0',
        'propvalue_default' => 0.0,
        'propname' => 'wsp2020_2040_mgy',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'WSP Facility 2040 MGY',
        'vardesc' => 'WSP estimated future average annual demand in 2040.',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ), 
      'wsp2020_2030_mgy' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => '(wsp2020_2020_mgy + wsp2020_2040_mgy) / 2.0',
        'propvalue_default' => 0.0,
        'propname' => 'wsp2020_2030_mgy',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'WSP Facility 2040 MGY',
        'vardesc' => 'WSP estimated future average annual demand in 2040.',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ), 
      'riverseg_frac' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => 'current_mgy / fac_current_mgy',
        'propvalue_default' => 0.0,
        'propname' => 'riverseg_frac',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Intake Current MGY',
        'vardesc' => 'Intake specific current average annual demand.',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ), 
      'fac_demand_mgd' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => 'fac_demand_mgy * riverseg_frac * historic_monthly_pct / modays',
        'propvalue_default' => 0.0,
        'propname' => 'fac_demand_mgd',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'WSP Facility 2040 MGY',
        'vardesc' => 'WSP estimated future average annual demand in 2040.',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ), 
      'vwp_exempt_mgd' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => '0.0',
        'propvalue_default' => 0.0,
        'propname' => 'vwp_exempt_mgd',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Estimated Exempt Value (MGD)',
        'vardesc' => 'Estimated Exempt Value.',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ), 
      'consumption' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => '0.25',
        'propvalue_default' => 0.25,
        'propname' => 'consumption',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Consumption (0.0-1.0)',
        'vardesc' => 'Consumptive fraction of withdrawal for calculating return flow.',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ), 
      'discharge_mgd' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => 'ps_enabled * (1.0 - consumption) * wd_mgd',
        'propvalue_default' => 0.0,
        'propname' => 'discharge_mgd',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Consumption (0.0-1.0)',
        'vardesc' => 'Consumptive fraction of withdrawal for calculating return flow.',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ), 
      /*
      'ps_enabled' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'ps_enabled',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'PS Enabled? (0/1)',
        'field_dh_matrix_default' => array(
          0 => array(0,0),
          1 => array(1,0),
          2 => array(2,0),
          3 => array(3,1),
          100 => array(100,1),
        ),
        'lutype1_default' => 2,
        'keycol1_default' => 'run_mode',
        'vardesc' => 'Whether or not to broadcast calculated return flow to parent.',
        'varid' => dh_varkey2varid('om_class_DataMatrix', TRUE),
      ), 
      */
    ) + $defaults;
    //dpm($defaults,'defs');
    return $defaults; 
  }
  
  public function insurePropertyTEST($entity, $thisvar) {
    $prop = parent::insureProperty($entity, $thisvar);
    $defaults = $this->getDefaults($entity);
    if ($thisvar['propname'] == 'ps_enabled') {
      //@todo: this code fails here because the is_new is FALSE, since it is already saved by the parent routine
      dpm($prop,'is_new setting defaults insureProperty called');
      return $prop;
      if (is_object($prop) and $prop->is_new) {
        $plugin = dh_variables_getPlugins($prop);
        if (isset($defaults['ps_enabled']['field_dh_matrix_default'])) {
          $plugin->setCSVTableField($prop, $defaults['ps_enabled']['field_dh_matrix_default']);
        }
        if (isset($defaults['ps_enabled']['lutype1_default'])) {
          $prop->lutype1 = $defaults['ps_enabled']['lutype1_default'];
        }
        if (isset($defaults['ps_enabled']['keycol1_default'])) {
          $prop->keycol1 = $defaults['ps_enabled']['keycol1_default'];
        }
        dpm($prop,'ps_enable');
        $prop->save();
      }
    }
    return $prop;
  }
}

// ## Special function for use by tiered and simple flowby vars
function om_formatCFB(&$form, $entity) {
  // combine into fieldset
  $form['cfb'] = array(
    '#type' => 'fieldset',
    '#title' => 'Conditional Alternate MIF',
    '#collapsible' => TRUE,
    '#collapsed' => $entity->enable_cfb->propvalue > 0 ? FALSE : TRUE,
  );
  $form['cfb']['enable_cfb'] = $form['enable_cfb'];
  $form['cfb']['cfb_var'] = $form['cfb_var'];
  $form['cfb']['cfb_var']['#prefix'] = '<table><tr><td>Set MIF to ';
  $form['cfb']['cfb_var']['#suffix'] = '';
  $form['cfb']['cfb_var']['#title'] = '';
  $form['cfb']['cfb_condition'] = $form['cfb_condition'];
  $form['cfb']['cfb_condition']['#title'] = '';
  $form['cfb']['cfb_condition']['#prefix'] = 'if ';
  $form['cfb']['cfb_condition']['#suffix'] = ' Base Flowby/Release </td></tr></table>';
  unset($form['cfb_var']);
  unset($form['cfb_condition']);
  unset($form['enable_cfb']);
}

class dHOMWaterSystemFlowBy extends dHOMSubComp {
  var $json2d = TRUE;
  var $object_class = 'wsp_flowby';
  var $attach_method = 'contained';
  
  public function hiddenFields() {
    $hidden = array_merge(array('propvalue', 'propcode'), parent::hiddenFields());
    return $hidden;
  }
  public function formRowSave(&$form_values, &$entity) {
    parent::formRowSave($form_values, $entity);
    //dpm($form_values,'Saving Values');
    //dpm($entity,'Saving entity');
    $entity->tscode = $form_values['flowby_eqn']; 
  }
  
  public function getDefaults($entity, &$defaults = array()) {
    parent::getDefaults($entity, $defaults);
    // @tbd: 
    // - historic_monthly_pct
    // - historic_annual 
    // - consumption
    // - surface_mgd : an equation that always equals wd_mgd, since these are all ssumed to be intakes not wells
    $defaults = array(
      'flowby_eqn' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => '0.0',
        'propvalue_default' => 0.0,
        'propname' => 'flowby_eqn',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Base Flowby/Release',
        'vardesc' => 'Base equation for calculating flowby/release.',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ), 
      'enable_cfb' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0,
        'datatype' => 'boolean',
        'propname' => 'enable_cfb',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Enable Conditional Variable',
        'vardesc' => 'Select TRUE enable over-ride condition for calculated flowby.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'cfb_var' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'cfb_var',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Set Flowby/Release to:',
        'vardesc' => 'Variable to compare to select over-ride for flowby/release.',
        'varid' => dh_varkey2varid('om_class_PublicVars', TRUE),
      ),
      'cfb_condition' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0,
        'datatype' => 'numeric',
        'propname' => 'cfb_condition',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'When',
        'vardesc' => 'As compared to calculated flowby in base flowby equation.',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
    ) + $defaults;
    //dpm($defaults,'defs');
    return $defaults;
  }
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    
    $form['cfb_condition']['#type'] = 'select';
    $form['cfb_condition']['#options'] = array('lt'=>'<', 'gt'=>'>');
    
    // orders
    $form['varid']['#weight'] = -10;
    $form['propname']['#weight'] = -9;
    $form['flowby_eqn']['#weight'] = -4;
    om_formatCFB($form, $entity);
    $form['cfb']['#weight'] = -3;
  }
  
}

class dHOMWaterSystemTieredFlowBy extends dHOMDataMatrix {
  var $object_class = 'wsp_1tierflowby';
  var $attach_method = 'contained';
  var $json2d = TRUE;
  
  public function hiddenFields() {
    $hidden = array_merge(array('propvalue', 'propcode'), parent::hiddenFields());
    return $hidden;
  }
  
  public function getDefaults($entity, &$defaults = array()) {
    parent::getDefaults($entity, $defaults);
    // @tbd: 
    // - historic_monthly_pct
    // - historic_annual 
    // - consumption
    // - surface_mgd : an equation that always equals wd_mgd, since these are all assumed to be intakes not wells
    $defaults = array(
      'enable_cfb' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0,
        'datatype' => 'boolean',
        'propname' => 'enable_cfb',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Enable Conditional Variable',
        'vardesc' => 'Select TRUE to utilize riser structure alorithm to solve for outflow.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'cfb_var' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0,
        'propname' => 'cfb_var',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Set Flowby/Release to:',
        'vardesc' => 'Variable to compare to select alternate for flowby/release.',
        'varid' => dh_varkey2varid('om_class_PublicVars', TRUE),
      ),
      'cfb_condition' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0,
        'datatype' => 'numeric',
        'propname' => 'cfb_condition',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'When',
        'vardesc' => 'As compared to calculated flowby in base flowby equation.',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
    ) + $defaults;
    //dpm($defaults,'defs');
    $defaults['lutype1']['propvalue_default'] = 2;
    // don't include these as editable for now...
    $defaults['lutype2']['embed'] = FALSE;
    $defaults['keycol2']['embed'] = FALSE;
    return $defaults;
  }
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    // these are overridden in the parent, so we have to unset them here
    unset($form['lutype2']);
    unset($form['keycol2']);
    
    $form['cfb_condition']['#type'] = 'select';
    $form['cfb_condition']['#options'] = array('lt'=>'<', 'gt'=>'>');
    
    // orders
    $form['varid']['#weight'] = -10;
    $form['propname']['#weight'] = -9;
    $form['keycol1']['#weight'] = -8;
    $form['lutype1']['#weight'] = -7;
    $form['field_dh_matrix']['#weight'] = -6;
    $form['enable_cfb']['#weight'] = -5;
    $form['cfb_var']['#weight'] = -4;
    $form['cfb_condition']['#weight'] = -3;
    
    om_formatCFB($form, $entity);
    $form['cfb']['#weight'] = -5;
  }
  
  public function tableDefault($entity) {
    // Returns associative array keyed table (like is used in OM)
    // This format is not used by Drupal however, so a translation 
    //   with tablefield_parse_assoc() is usually in order (such as is done in load)
    // set up defaults - we can sub-class this to handle each version of the model land use
    // This version is based on the Chesapeake Bay Watershed Phase 5.3.2 model land uses
    // this brings in an associative array keyed as $table[$luname] = array( $year => $area )
    $table = array();
    $table[] = array('xTrigger', 'xMIF');
    $table[] = array(0,0);
    return $table;
  }
}

class dHOMConsumptiveUseFractionsPWS extends dHOMDataMatrix {
  var $wd_matrix_name = 'historic_monthly_pct';
  // get the wd_matrix 
  // C(m) = 1.0 - (dx * Ff)/(df * Fx); Ff = Fraction of MGY in February, dx = Days in month(x), df = days in Feb, Fx = Fration of MGY in Month "x"
  
  public function tableDefault($entity) {
    // get Parent
    $defaults = array(
      0=>array('xMonth','xFactor'),
      1=>array(1,0.0),
      2=>array(2,0.0),
      3=>array(3,0.0),
      4=>array(4,0.1),
      5=>array(5,0.15),
      6=>array(6,0.15),
      7=>array(7,0.2),
      8=>array(8,0.15),
      9=>array(9,0.15),
      10=>array(10,0.1),
      11=>array(11,0.1),
      12=>array(12,0.1),
    );
    $consumption = FALSE;
    $parent = $this->getParentEntity($entity);
    // load matrix property on parent
    //dpm($parent,'found parent');
    $wd_matrix_entity = om_load_dh_property($parent, $this->wd_matrix_name);
    //dpm($wd_matrix_entity,'found historic_monthly_pct');
    // get matrix property entity from parent 
    if (is_object($wd_matrix_entity)) {
      // load plugin for Matrix entity 
      $mplugin = dh_variables_getPlugins($wd_matrix_entity);
      // get the table of data from the matrix entity
      if (method_exists($mplugin, 'getMatrixFieldTable')) {
        $pct_wd = $mplugin->getCSVTableField($wd_matrix_entity);
        if(count($pct_wd) >= 13) {
          $feb = $pct_wd[2];
          $Ff = $feb[1];
          $consumption[0] = array('xMonth', 'xFrac');
          $checksum = 0;
          for ($i = 1; $i <= 12; $i++) {
            $mofrac = $pct_wd[$i];
            $x = $mofrac[0];
            $Fx = $mofrac[1];
            $cfrac = ($Fx > 0) ? (1.0 - ( ($this->modays[$x] * $Ff) / ($this->modays[2] * $Fx) )) : 0.0;
            $cfrac = ($cfrac < 0) ? 0.0 : $cfrac;
            $checksum += $cfrac;
            $mf_checksum += $mofrac[1];
            $consumption[$x] = array($x, $cfrac);
          }
          //dpm($consumption,'consumption calculated');
          // consider consumption above annual total of 50%, or below 1% to be erroneous for CWS.
          if ( ($checksum > 6.0) or ($checksum < 0.12 ) ) {
            $consumption = FALSE;
            dsm("Calculated consumption " . ($checksum/12.0) . " outside bounds 0.5 to 0.01");
          }
          if ( ($mf_checksum < 0.9) or ($mf_checksum > 1.1)) {
            // must be a problem in the mo frac
            $consumption = FALSE;
            dsm("Source withdrawal fractions data outside bounds 0.9 to 1.1");
          }
        }
      }
    }
    if ($consumption === FALSE) {
      $consumption = $defaults;
    }
    return $consumption;
  }
  
  public function getDefaults($entity, &$defaults = array()) {
    $defaults = parent::getDefaults($entity, $defaults);
    $defaults['keycol1']['propcode_default'] = 'month';
    $defaults['lutype1']['propvalue_default'] = 0;
    return $defaults;
  }
  
  public function hiddenFields() {
    return array('pid', 'startdate', 'enddate', 'varid', 'featureid', 'entity_type', 'bundle','dh_link_admin_pr_condition');
  }
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    //dpm($form,'form');
    // now, format the lookup type fields 
    $modes = array(
      'auto' => "Auto",
      'manual' => "Manual",
      'default' => "Default",
    );
    $form['propvalue']['#title'] = 'Average Monthly Consumption Fraction';
    $form['propcode']['#title'] = 'Estimation Mode';
    $form['propcode']['#type'] = 'select';
    $form['propcode']['#options'] = $modes;
    $form['propcode']['#size'] = 1;
    $form['propcode']["#default_value"] = "auto";
    $form['propcode']["#description"] = "Select mode for estimation/setting of consumptive use fractions.  Auto will attempt to calculate from monthly withdrawal distribution.";
  }

  public function save(&$entity) {
    if (empty ($entity->propcode)) {
      $entity->propcode='auto';
    }
    if ($entity->propcode=='auto') {
      $datatable = $this->tableDefault($entity);
      $this->setCSVTableField($entity, $datatable);
    }
    // Always calculate the Mean annual.
    $datatable = $this->getCSVTableField($entity);
    // remove the header
    array_shift($datatable);
    $entity->propvalue = 0;
    foreach ($datatable as $row){
      $entity->propvalue += $row[1] / 12.0;  
    }
    parent::save($entity);
  }
}


class dHOMWaterSystemPermitStatus extends dHOMtextField {
  // to be done: reconfigure text columns to integer
  public function getOptions() {
    $fsopts = array(
      'active' => t('Active'), // 1
      'exempt' => t('Exempt'), // 2
      'expired' => t('Expired'), // 3
      'npne' => t('Non-Permitted, Non-Exempt'), // 4
      'proposed' => t('Proposed'), // 5
      'other' => t('Other'), // 6
      'reissue' => t('Reissue'), // 7
    );
    return $fsopts;
  }
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    $form['propcode'] = array(
      '#title' => t($entity->varname),
      '#type' => 'select',
      '#description' => 'Permit Status for this entity. Use Other if status not matching.',
      '#options' => $this->getOptions(),
      '#default_value' => !empty($entity->propcode) ? $entity->propcode : "",
    );
  }
  
}

?>