<?php
// @todo: figure out how to insure other plugin files are called when needed by this plugin
//        OR just move all the base classes into the module ?
module_load_include('inc', 'dh', 'plugins/dh.display');
module_load_include('module', 'dh');
// make sure that we have base plugins 
$plugin_def = ctools_get_plugins('dh', 'dh_variables', 'dHOMmodelElement');
$class = ctools_plugin_get_class($plugin_def, 'handler');
//dpm("so far so good");

class dHOMHydroObject extends dHOMModelElement {
  var $object_class = 'hydroObject';
  var $attach_method = 'contained';
  
  public function hiddenFields() {
    $hidden = array_merge(array('propcode', 'propvalue'), parent::hiddenFields());
    return $hidden;
  }
  // can create framework here to set properties that are needed, similar to object_class properties
  // being automatically added.
  // will use standard editing for now, but...
  
  public function getDefaults($entity, &$defaults = array()) {
    parent::getDefaults($entity, $defaults);
    
    $defaults += array(
      'riverseg' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'riverseg',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'riverseg',
        'vardesc' => 'riverseg.',
        'varid' => dh_varkey2varid('om_class_textField', TRUE),
      ), 
      'area' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'area',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Surface Area (ac)',
        'vardesc' => 'Physical surface area of this component (not including any tributary drainage area).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
    );
    return $defaults;
  }
}

class dHOMHydroObjectOtherProps extends dHOMHydroObject {
  
  // these were definied in OM on the base class but unused in many, and should not be created nor exposed?
  public function getDefaults($entity, &$defaults = array()) {
    parent::getDefaults($entity, $defaults);
  
    $defaults += array(
      'Qin' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'Qin',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => "Qin",
        'vardesc' => 'Total Inflow during last timestep(transient).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'Qout' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'Qout',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => "Qout",
        'vardesc' => 'Total outflow during last timestep(transient).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'Iold' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'Iold',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => "Iold",
        'vardesc' => 'Inflow during last timestep(transient).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'slength' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 100.0,
        'propname' => 'slength',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'slength',
        'vardesc' => 'slength.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
      'totalflow' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'totalflow',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'totalflow',
        'vardesc' => 'totalflow.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
      'totalinflow' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'totalinflow',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'totalinflow',
        'vardesc' => 'totalinflow.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
      'Storage' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'Storage',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => "Storage",
        'vardesc' => 'Storage.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
    );
    return $defaults;
  }
}

class dHOMHydroImpoundment extends dHOMHydroObject {
  var $object_class = 'hydroImpoundment';
  var $attach_method = 'contained';
  //var $json2d = TRUE; // only enable this when the main classes support json which for now only subcomps do
  
  public function hiddenFields() {
    $hidden = array_merge(array('propcode', 'propvalue'), parent::hiddenFields());
    return $hidden;
  }
  // can create framework here to set properties that are needed, similar to object_class properties
  // being automatically added.
  // will use standard editing for now, but...
  
  public function getDefaults($entity, &$defaults = array()) {
    parent::getDefaults($entity, $defaults);
    $defaults = array(
      'area' => array(
        'embed' => FALSE,
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'area',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Area of feature',
        'vardesc' => 'Area in sqmi (should be derived from full_surface_area).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'full_surface_area' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'full_surface_area',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Normal Pool Area (ac)',
        'vardesc' => 'Used for area calculations as needed.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'initstorage' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'initstorage',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Initial Storage (ac-ft)',
        'vardesc' => 'Reservoir storage at model simulation timestep 0 in ac-ft.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'maxcapacity' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 100.0,
        'propname' => 'maxcapacity',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Maximum Storage (ac-ft)',
        'vardesc' => 'Reservoir maximum storage, includes unusable storage in ac-ft.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'unusable_storage' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 10.0,
        'propname' => 'unusable_storage',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Unusable Storage (ac-ft)',
        'vardesc' => 'Storage that is unusable based on water quality or intake depth limitations in ac-ft.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'riser_diameter' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 10.0,
        'propname' => 'riser_diameter',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Riser Diameter(ft)',
        'vardesc' => 'Riser diameter(ft).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
      'riser_length' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 10.0,
        'propname' => 'riser_length',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Riser Length(ft)',
        'vardesc' => 'Riser.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
      'storage_stage_area' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'embed' => FALSE,
        'propvalue_default' => 10.0,
        'bundle' => 'om_data_matrix',
        'propname' => 'storage_stage_area',
        'varname' => 'Stage-Storage-SA',
        'vardesc' => 'Lookup table to provide stage and surface area for a given storage value.',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_DataMatrix', TRUE),
      ), 
    ) + $defaults;
    return $defaults;
  }
   
  // Advanced embedded properties in form   
  // these properties can also be added to the main form, and handled with an entity map
  // or handled directly
  // how to automatically include properties on the form?
  // 1. load the prop
  // 2. load its plugins
  // 3. create a 
  // 4. pass the form to prop->plugin->formRowEdit

  function getPublicProps($entity) {
    # gets only properties that are visible (must be manually defined)
    $publix = parent::getPublicProps($entity);
    // @todo: Many of these are subcomps, so not needed. Storage is though. Sort out extras just to be tidy.
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
   
  // @todo: if this is successful we should migrate to the base model class dHOMModelElement
  function exportOpenMI($entity) {
    $export = parent::exportOpenMI($entity);
    $export[$entity->propname]['matrix'] = $export[$entity->propname]['storage_stage_area']['matrix'];
    unset($export[$entity->propname]['storage_stage_area']);
    return $export;
  }

  function insureProperty($entity, $thisvar) {
    $iprop = parent::insureProperty($entity, $thisvar);
    if ($iprop->propname == 'storage_stage_area' and empty($iprop->pid)) {
      // need to set up the default storage table here
      $table = array();
      $table[] = array('storage', 'stage', 'surface_area');
      $table[] = array(0,0,0);
      $table[] = array(100,10,20);
      $tplug = dh_variables_getPlugins($iprop);
      $tplug->setCSVTableField($iprop, $table);
    }
    return $iprop;
  }
}

class dHOMHydroImpoundmentSmall extends dHOMHydroImpoundment {
  var $object_class = 'hydroImpSmall'; 
  var $json2d = TRUE;
  public function getDefaults($entity, &$defaults = array()) {
    $defaults = parent::getDefaults($entity, $defaults);
    // must account for these which are handled specially from local variables on the subcomp version
    // $this->rvars = array('et_in','precip_in','release','demand', 'Qin', 'refill');
    $defaults = array(
      'Qin' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'Qin',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Inflow (cfs)',
        'vardesc' => 'Reservoir inflow variable name.',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'et_in' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'et_in',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'ET (inches)',
        'vardesc' => 'Reservoir evaporation variable name.',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'precip_in' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'precip_in',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Precip (inches)',
        'vardesc' => 'Reservoir precip variable name.',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'release' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'release',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Release (cfs)',
        'vardesc' => 'Reservoir release variable name (in cfs).',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'demand' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'demand',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Demand (MGD)',
        'vardesc' => 'Reservoir demand variable name (in MGD).',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'refill' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'refill',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Refill (mgd)',
        'vardesc' => 'Reservoir refill variable name (if pump-store).',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'riser_length' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 1.0,
        'propname' => 'riser_length',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Riser Length (feet)',
        'vardesc' => 'Riser length dimension (if using riser mode).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'riser_diameter' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 1.0,
        'propname' => 'riser_diameter',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Riser Diameter (feet)',
        'vardesc' => 'Riser diameter dimension (if using riser mode).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'riser_pipe_flow_head' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 1.0,
        'propname' => 'riser_pipe_flow_head',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Riser Pipe Flow Head (feet)',
        'vardesc' => 'Depth of water over riser structure at which pipe-flow conditions ensue.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'riser_opening_storage' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 1.0,
        'propname' => 'riser_opening_storage',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Riser Pipe Active Storage',
        'vardesc' => 'Volume of impounded water at which riser orifice becomes active (reference stage-storage table).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'riser_opening_elev' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 1.0,
        'propname' => 'riser_opening_elev',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Riser Pipe Active Elevation',
        'vardesc' => 'Depth of water at which riser orifice becomes active (reference stage-storage table).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'riser_enabled' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0,
        'datatype' => 'boolean',
        'propname' => 'riser_enabled',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Use Riser Pipe?',
        'vardesc' => 'Select TRUE to utilize riser structure alorithm to solve for outflow.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'exec_hierarch' => array(
        'entity_type' => $entity->entityType(),
        'propvalue_default' => 0,
        'propname' => 'exec_hierarch',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Execution hierarchy (manually defined).  Smaller numbers executed first.  Negative values OK. Zero (0) means use default model hierarchy assessment',
        'title' => 'Execution hierarchy',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
    ) + $defaults;
//dpm($defaults,'defs');
    return $defaults;
  }
  
  public function setAllRemoteProperties($entity, $elid, $path) {
    // this hysroImpSmall is a sub-comp, so we use the plumbing from the subomponent class
    // to handle setting all of these props at once.
    // this is TEST!
    if ($this->json2d) {
      $ppath = $path;
      array_unshift($ppath, $entity->propname);
      //$this->setRemoteProp($entity, $elid, $ppath, "", $this->object_class);
      $exp = $this->exportOpenMI($entity);
      //dpm($exp,"Using JSON export mode");
      $exp_json = addslashes(json_encode($exp[$entity->propname]));
      $this->setRemoteProp($entity, $elid, $ppath, $exp_json, $this->object_class, 'json-2d');
    } else {
      parent::setAllRemoteProperties($entity, $elid, $path);
      //dpm($path, 'original path to setAllRemoteProperties()');
      //dpm($entity, 'subcomp entity to setAllRemoteProperties()');
      // create the base property if needed.
      // this seems to only be used by sub-comps, why?
      $ppath = $path;
      array_unshift($ppath, $entity->propname);
      $this->setRemoteProp($entity, $elid, $ppath, "", $this->object_class);
      if (property_exists($entity, 'proptext')) {
        array_unshift($path, 'description');
        $this->setRemoteProp($entity, $elid, $path, $entity->proptext['und'][0]['value'], $this->object_class);
        //$this->setRemoteProp($entity, $elid, $path, 'description', $this->proptext);
      }
    }
  }

}

class dHOMUSGSChannelGeomObject extends dHOMHydroObject {
  var $attach_method = 'contained';
  var $object_class = 'USGSChannelGeomObject';
  var $length; // channel length in feet
  var $drainage_area; // in square miles
  var $base = 1.0; // base width of channel 
  var $Z = 1.0; // side slope ratio 
  var $n = 0.002; // Manning's n
  
  public function getDefaults($entity, &$defaults = array()) {
    $defaults = parent::getDefaults($entity, $defaults);
    // length, drainage_area, Z, base, province
    $defaults = array(
      'depth' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'depth',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => "depth",
        'vardesc' => 'depth.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
      'tol' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0001,
        'propname' => 'tol',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => "Tolerance in iterative solutions",
        'vardesc' => 'Tolerance in iterative solutions.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
      'n' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.025,
        'propname' => 'n',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => "Manning's n",
        'vardesc' => 'Roughness coefficient for use in model runoff and channel flow simualtions.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
      'slope' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.01,
        'propname' => 'slope',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'slope',
        'vardesc' => 'slope.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
      'Z' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 1.0,
        'propname' => 'Z',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Z',
        'vardesc' => 'Channel side-slope ratio.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
      'length' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 5000.0,
        'propname' => 'length',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Channel mainstem length (ft)',
        'vardesc' => 'Local channel mainstem.  Channel length is used to compute volume of storage and travel time.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'base' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 1.0,
        'propname' => 'base',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Channel base width.',
        'vardesc' => 'Mean channel base width is calculated automatically (TBD: see setChjannelGeom() function, call during save()).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'drainage_area' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 1.0,
        'propname' => 'drainage_area',
        'singularity' => 'name_singular',
        'vardesc' => 'Contributing area of entire watershed above outlet of this segment.',
        'varname' => 'Watershed Drainage Area',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'area' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 1.0,
        'propname' => 'area',
        'singularity' => 'name_singular',
        'vardesc' => 'Contributing area to only this specific section of channel.',
        'varname' => 'Local Drainage Area',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'province' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 1,
        'propname' => 'province',
        'singularity' => 'name_singular',
        'vardesc' => '1 of 4 eco-regional provinces used in USGS model of stream morphology in VA: # 1 - Appalachian Plateau, # 2 - Valley and Ridge, # 3 - Piedmont, # 4 - Coastal Plain.',
        'varname' => 'Ecoregional Province',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'reset_channelprops' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 1,
        'propname' => 'reset_channelprops',
        'singularity' => 'name_singular',
        'datatype' => 'boolean',
        'vardesc' => 'If TRUE, will re-calculate channel properties side-slope, base-width based on drainage area and physiographic province.',
        'varname' => 'Re-calculcate Channel Morphology?',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'q_var' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0,
        'propname' => 'q_var',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Upstream Inflow (cfs):',
        'vardesc' => 'Upstream tributaries flow here.',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'r_var' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0,
        'propname' => 'r_var',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Runoff In (cfs)o:',
        'vardesc' => 'Local drainage area inflows.',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'w_var' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0,
        'propname' => 'w_var',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Withdrawals (mgd):',
        'vardesc' => 'Direct withdrawals from this stream.',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
    ) + $defaults;
    return $defaults;
  }

  function setChannelGeom(&$values) {
    // @tbd: implement this on save() or update()?
    $drainage_area = $values['drainage_area'];
    switch ($values['province']) {
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
    $h = $hc * pow($drainage_area, $he);
    $bf = $bfc * pow($drainage_area, $bfe);
    $b = $bc * pow($drainage_area, $be);
    $z = 0.5 * ($bf - $b) / $h; 
    # since Z is increase slope of a single side, 
    # the top width increases (relative to the bottom) at a rate of (2 * Z * h)
    # only use these derived values if they are non-zero, otherwise, use defaults
    if ($z > 0) {
      $values['Z'] = $z;
    } else {
      $values['Z'] = $this->Z;
      dsm("Calculated Z value from (0.5 * ($bf - $b) / $h) less than zero, using default " . $this->Z);
    }
    if ($b > 0) {
      $values['base'] = $b;
    } else {
      $values['base'] = $this->base;
      dsm("Calculated base value from ($bc * pow($drainage_area, $be)) less than zero, using default " . $this->base);
    }
    dsm("Calculated base value from ($bc * pow($drainage_area, $be)), = $b  Province: $values[province]");
    $values['n'] = $n;
    return;
  }
  
  public function formRowSave(&$values, &$entity) {
    // special form save handlers
    //dpm($values,'vals');
    //dpm($entity,'entity');
    if ($values['reset_channelprops'] > 0) {
      $this->setChannelGeom($values);
      $entity->base = $values['base'];
      $entity->n = $values['n'];
      $entity->Z = $values['Z'];
    }
    parent::formRowSave($values, $entity);
  }
   
  public function getOptions() {
    $fsopts = array(
      1 => t('Appalachian Plateau'),
      2 => t('Valley and Ridge'),
      3 => t('Piedmont'),
      4 => t('Coastal Plain'),
    );
    return $fsopts;
  }
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    $form['province'] = array(
      '#title' => "Physiographic Province",
      '#type' => 'select',
      '#description' => 'Physiographic province.',
      '#options' => $this->getOptions(),
      '#default_value' => !empty($entity->province->propvalue) ? $entity->province->propvalue : "",
    );
  }
}

class dHOMUSGSChannelGeomObject_sub extends dHOMUSGSChannelGeomObject {
  var $object_class = 'USGSChannelGeomObject_sub';
  var $json2d = TRUE;
  
  public function setAllRemoteProperties($entity, $elid, $path) {
    // this hysroImpSmall is a sub-comp, so we use the plumbing from the subomponent class
    // to handle setting all of these props at once.
    // this is TEST!
    if ($this->json2d) {
      $ppath = $path;
      array_unshift($ppath, $entity->propname);
      //$this->setRemoteProp($entity, $elid, $ppath, "", $this->object_class);
      $exp = $this->exportOpenMI($entity);
      //dpm($exp,"Using JSON export mode");
      $exp_json = addslashes(json_encode($exp[$entity->propname]));
      $this->setRemoteProp($entity, $elid, $ppath, $exp_json, $this->object_class, 'json-2d');
    } else {
      parent::setAllRemoteProperties($entity, $elid, $path);
      //dpm($path, 'original path to setAllRemoteProperties()');
      //dpm($entity, 'subcomp entity to setAllRemoteProperties()');
      // create the base property if needed.
      // this seems to only be used by sub-comps, why?
      $ppath = $path;
      array_unshift($ppath, $entity->propname);
      $this->setRemoteProp($entity, $elid, $ppath, "", $this->object_class);
      if (property_exists($entity, 'proptext')) {
        array_unshift($path, 'description');
        $this->setRemoteProp($entity, $elid, $path, $entity->proptext['und'][0]['value'], $this->object_class);
        //$this->setRemoteProp($entity, $elid, $path, 'description', $this->proptext);
      }
    }
  }
  
}
class dHOMUSGSRecharge extends dHOMSubComp {
  var $object_class = 'USGSRecharge';
  
  public function getDefaults($entity, &$defaults = array()) {
    parent::getDefaults($entity, $defaults);
    $defaults = array(
      'q_var' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'q_var',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Initial Storage (ac-ft)',
        'vardesc' => 'Flow variable to summarize as input to MLLR.',
        'varid' => dh_varkey2varid('om_class_PublicVars', TRUE),
      ),
      'r_start_day' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 300,
        'propname' => 'r_start_day',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Recharge JDay Begin',
        'vardesc' => 'Julian day of period start.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'r_end_day' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 100,
        'propname' => 'r_end_day',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Recharge JDay End',
        'vardesc' => 'Julian day of period start.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'r_default' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 100,
        'propname' => 'r_default',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'Recharge Default (cfs)',
        'vardesc' => 'Default value in the event of missing data, or at simulation begin.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'b0' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 100,
        'propname' => 'b0',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'β<sub>0</sub>',
        'vardesc' => 'First regression term.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'b1' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 100,
        'propname' => 'b1',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varname' => 'β<sub>1</sub>',
        'vardesc' => 'Second regression term.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
    );
    return $defaults;
  }
}


?>
