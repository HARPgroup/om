<?php
module_load_include('inc', 'dh', 'plugins/dh.display');
module_load_include('module', 'dh');
// make sure that we have base plugins 
$plugin_def = ctools_get_plugins('dh', 'dh_variables', 'dHOMModelElement');
$class = ctools_plugin_get_class($plugin_def, 'handler');

class dHOMCBPLandDataConnection extends dHOMModelElement {
  // @todo: add getDefaults 
  
}

class dHOMCBPLandDataConnectionFile extends dHOMModelElement {
  var $object_class = 'CBPLandDataConnectionFile';
  var $om_template_id = 340398; // remote server template ID, set FALSE if not used.
  var $json2d = TRUE; // use JSON 2d for all remote syncs, much faster
  
  public function getDefaults($entity, &$defaults = array()) {
    // needs:
    //   - land seg - constant alpha
    //   - riverseg - constant alpha 
    //   - landuse - default matrix with acreage 
    //   - landuse_var - default matrix with runmode/luvar name pairs (defaults to all being "landuse")
    //   @tbd - file_var - make object in OM be flexible at startup to use a lookup or alpha variable to select 
    //          the file to use.  Allows us to switch between climate change or other meteorology runs.
    //   @tbd: - luyear - land use year, defaults to timer year, but may be fixed.
    $defaults = parent::getDefaults($entity, $defaults);
    $defaults += array(
      'defaultval' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'defaultval',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Initial value.',
        'varname' => 'Initial Value',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'om_template_id' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'om_template_id',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Template ID.',
        'varname' => 'Initial Value',
        'varid' => dh_varkey2varid('om_class_OMConstant', TRUE),
      ),
      'landuse' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'landuse',
        'bundle' => 'om_data_matrix',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'embed' => FALSE,
        'vardesc' => 'Base matrix for land use over time.',
        'varname' => 'Default Landuse Table',
        'varid' => dh_varkey2varid('om_class_DataMatrix', TRUE),
      ), 
      'luyear' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => 'year',
        'propname' => 'luyear',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => "Land Use year (set to thisyear if dynamic, or single value if static).",
        'varname' => 'Landuse Year',
        'varid' => dh_varkey2varid('om_class_Equation', TRUE),
      ),
      'landseg' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'landseg',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => "Land Segment.",
        'varname' => 'Land Segment',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'riverseg' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'riverseg',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => "River Segment.",
        'varname' => 'River Segment',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'scenario' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'scenario',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => "Model Scenario.",
        'varname' => 'Scenario',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'version' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'version',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => "model Version.",
        'varname' => 'Version',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'modelpath' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => '/media/NAS/omdata/p6',
        'propname' => 'modelpath',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => "Model base path (for files).",
        'varname' => 'Model Path',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'filepath' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'filepath',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => "Unit area runoff file location (auto-generated).",
        'varname' => 'File Path',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'lufile' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'lufile',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => "Landuse CSV file location (auto-generated).",
        'varname' => 'LU File',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
    );
    //error_log("Defaults:" . print_r(array_keys($defaults),1));
    return $defaults;
  }

  function getPublicVars($entity, &$publix = array()) {
    parent::getPublicVars($entity, $publix);
    //dpm($this,"called getPublicVars()");
    // gets all viewable variables
    $publix = array_unique(array_merge(array('luyear'), $publix));
    sort($publix);
    //dpm($publix,"called getPublicVars()");
    return $publix;
  }
  
  function update(&$entity) {
    //
    parent::update($entity);
    $this->setFilePath($entity);
  }
  
  public function setFilePath($entity) {
    $defs = $this->getDefaults($entity);
    $modelpath = is_object($entity->modelpath) ? $entity->modelpath->propcode : $entity->modelpath;
    if (empty($modelpath)) {
      $modelpath = $defs['modelpath']['propcode_default'];
    }
    $scenario = is_object($entity->scenario) ? $entity->scenario->propcode : $entity->scenario;
    $landseg = is_object($entity->landseg) ? $entity->landseg->propcode : $entity->landseg;
    $riverseg = is_object($entity->riverseg) ? $entity->riverseg->propcode : $entity->riverseg;
    $filepath = implode("/", array($modelpath, 'out/land', $scenario, 'eos', $landseg .'_0111-0211-0411.csv' ));
    if (is_object($entity->filepath)) {
      $entity->filepath->propcode = $filepath;
    } else {
      $entity->filepath = $filepath;
    }
    // @todo: lu file needs to be generated for other scenarios, maybe static?
    // for now, we hard-wire the scenario and dsm to let users know 
    $luscenario = 'CFBASE30Y20180615';
    $entity->lufile = implode("/", array($modelpath, 'out/land', $luscenario, 'landuse', 'lutable_' . $landseg .'_' . $riverseg . '.csv' ));
  }
  
  public function formRowEdit(&$rowform, $entity) {
    parent::formRowEdit($rowform, $entity);
    $rowform['propcode']['#title'] = '';
    $rowform['propcode']['#prefix'] = ' = ';
  }
  
  public function setAllRemoteProperties($entity, $elid, $path) {
    parent::setAllRemoteProperties($entity, $elid, $path);
  }
}
  
?>