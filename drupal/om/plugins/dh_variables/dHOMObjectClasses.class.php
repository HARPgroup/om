<?php
module_load_include('inc', 'dh', 'plugins/dh.display');
module_load_include('module', 'dh');

class dHVariablePluginDefaultOM extends dHVariablePluginDefault {
  
  // @todo: should we have a method for adding these defaults, to insure proper formation?
  // **** BEGIN - Experimental un-used Component Adding Methods
  //        the property $component_defaults and method add_component_default() are not currently used
  var $component_defaults = FALSE; // will be initialized in getDefaults or other place.
  var $set_remote = FALSE; // should we push changes?
  public function add_component_default($config) {
    if ($this->component_defaults === FALSE) {
      $this->component_defaults = array();
    }
    if (!isset($config['form_machine_name'])) {
      $config['form_machine_name'] = $this->handleFormPropname($config['propname']);
    }
    if (!$this->validate_component_default($config)) {
      return FALSE;
    }
    $this->component_defaults[$config['form_machine_name']] = $config;
    return TRUE;
  }
  public function validate_component_default($config) {
    if (!isset($config['propname'])) {
      return FALSE;
    }
    if (!$this->validate_alphanumeric_underscore($config['form_machine_name'])) {
      return FALSE;
    }
    return TRUE;
  }
  public function validate_alphanumeric_underscore($str) {
    return preg_match('/^[A-Za-z][A-Za-z0-9]*(?:_[A-Za-z0-9]+)*$/',$str);
  }
  // **** END - Experimental un-used Component Adding Methods
  
  public function hiddenFields() {
    return array(
      'pid',
      'featureid',
      'startdate',
      'enddate',
      'bundle',
      'entity_type',
      'dh_link_admin_pr_condition', 
      'field_prop_upload',
    );
  }
  
  public function formRowEdit(&$rowform, $row) {
    parent::formRowEdit($rowform, $row); // does hiding etc.
    $varinfo = $row->varid ? dh_vardef_info($row->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    $this->loadProperties($row);
    // apply custom settings here
    $this->addAttachedProperties($rowform, $row);
  }
  public function getDefaults($entity, &$defaults = array()) {
    // Example:
    /*
    $defaults += array(
      'berry_weight_g' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'Berry Weight',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varkey' => 'berry_weight_g',
        'varid' => dh_varkey2varid('berry_weight_g', TRUE),
        'embed' => TRUE, // defaults to TRUE, set this to FALSE to prevent embedding
      ),
    );
    */
    return $defaults;
  }
  
  public function addProperties(&$entity) {
    // add properties that reside on this entity.
    // this can add any number of properties that should reside on this entity.
    // sub-class this and call repeatedly
    // Example:
    //$values = array(
    //  'entity_type' => $entity->entityType(),
    //  'propcode' => $this->object_class,
    //  'propname' => 'object_class',
    //  'featureid' => $entity->identifier(),
    //  'varid' => dh_varkey2varid('om_object_class', TRUE),
    //);
    //dh_update_properties($values, 'singular');
    $defaults = $this->getDefaults($entity);
    foreach ($defaults as $thisone) {
      //dpm($thisone,'adding default');
      dh_update_properties($thisone, $thisone['singularity']);
    }
  }
  
  public function insert(&$entity) {
    //$entity->propname = 'blankShell';
    // check for transition from ts to prop
    //$this->addProperties($entity);
    //$this->loadProperties($entity);
    // anything that came in from the form API will already be a scalar property/attribute 
    // this converts known attributes to dh_properties.
    $this->convert_attributes_to_dh_props($entity);
    // now, load any others that didn't get passed in already -- this function defaults not to overwrite
    //$this->loadProperties($entity);
    $this->updateProperties($entity);
    parent::insert($entity);
  }
  
  public function update(&$entity) {
    // check for transition from for value to prop
    $this->convert_attributes_to_dh_props($entity);
    $this->updateProperties($entity);
    parent::update($entity);
  }
  
  public function save(&$entity) {
    // We comment out the below call to convert... because it 
    // somehow prevents the saving of this components attached properties.
    // This makes sense since, the children props need this prop to already be saved so that they 
    // can point to a featureid, but, because update() does not get called, the children don't have their
    // props pushed.  Maybe do this *after* the parent::save() call?
    //$this->convert_attributes_to_dh_props($entity);
    parent::save($entity);
    //dpm($entity,'save()');
  }
  
  public function delete(&$entity) {
    parent::delete($entity);
  }
  
  public function loadProperties(&$entity, $overwrite = FALSE, $propname = FALSE, $force_embed = FALSE) {
    $props = $this->getDefaults($entity);
    if (!($propname === FALSE)) {
      // a single prop has been requested
      if (!array_key_exists($propname, $props)) {
        watchdog('dh', 'loadProperties(entity, propname) called on dH Variable plugin object but propname = ' . strval($propname) . ' not found');
        return FALSE;
      }
      $props = array($propname => $props[$propname]);
    }
    //error_log("Props:" . print_r($props,1));
    foreach ($props as $propname => $thisvar) {
	    // propname is arbitrary by definition
      // also, propname can be non-compliant with form API, which requires underscores in place of spaces.
      // user can also rename properties, but that shouldn't be allowed with these kinds of defined by DefaultSettings
      // or at least, if the user renames the property then this plugin should create a new one.
      // name should alternatively be read-only in these forms.
      // if we create the name as form compliant, and create a field called "form_name", can we eliminate any guesswork?
      // we still have to deal with user-named properties, which is definitely something available to users.
      //   - actually, user defined would be handled in a separate fashion.  We need to handle this well, since the 
      //     modeling framework will enable many user-defined props, and we WILL want to be able to edit them in a multi-form
      //     type scenario. 
      $pn = $this->handleFormPropname($propname);
      if (!isset($thisvar['embed']) or ($thisvar['embed'] === TRUE) or $force_embed) {
        // @todo: debug the use of propname here.  Propname is ONLY set if this function is called for a single prop, 
        //        which is an unusual case 
        $this->loadSingleProperty($entity, $propname, $thisvar, $overwrite);
      }
    }
  }
  
  public function loadSingleProperty(&$entity, $propname, $thisvar, $overwrite = FALSE) {
    // @todo: Replace this function with loadSingleProperty2() 
    if ($overwrite 
      or !property_exists($entity, $propname) 
      or (property_exists($entity, $propname) 
        and !is_object($entity->{$propname})
      ) 
    ) {
      $thisvar['featureid'] = $entity->{$this->row_map['id']};
      $prop = $this->insureProperty($entity, $thisvar);
      //dpm($prop,"insured $propname");
      $varinfo = $prop->varid ? dh_vardef_info($prop->varid) : FALSE;
      $varinfo = (!empty($prop->varkey) and !$prop->varid) ? dh_vardef_info($prop->varkey) : $varinfo;
      //dpm($varinfo,"varinfo");
      if (!$varinfo) {
        //watchdog("loadProperty called without varid");
        watchdog('om', "loadProperty called without varid");
        return;
      }
      if (!$prop) {
        watchdog('om', 'Could not Add Properties in plugin loadProperties');
        return FALSE;
      }
      // apply over-rides if given
      $prop->vardesc = isset($thisvar['vardesc']) ? $thisvar['vardesc'] : $varinfo->vardesc;
      $prop->varname = isset($thisvar['varname']) ? $thisvar['varname'] : $varinfo->varname;
      $prop->title = isset($thisvar['title']) ? $thisvar['title'] : $varinfo->propname;
      $prop->datatype = isset($thisvar['datatype']) ? $thisvar['datatype'] : $varinfo->datatype;
      // embed = is this being sved as part of a multi-object form?
      //         since properties could be saved as embedded, OR as stad-alone
      //         we have to set this because a standalone prop would have to synchronize itself 
      //         but others would not.
      $prop->embedded = TRUE; 
      $entity->{$prop->propname} = $prop;
    }
  }
  
  public function loadSingleProperty2(&$entity, $thisvar, $overwrite = FALSE) {
    // @todo: Replace loadSingleProperty() with this function 
    // this uses the $thisvar to grab the propname when checking for overwrites
    if ($overwrite 
      or !property_exists($entity, $thisvar['propname']) 
      or (property_exists($entity, $thisvar['propname']) 
        and !is_object($entity->{$thisvar['propname']})
      ) 
    ) {
      $thisvar['featureid'] = $entity->{$this->row_map['id']};
      $prop = $this->insureProperty($entity, $thisvar);
      $varinfo = $prop->varid ? dh_vardef_info($prop->varkey) : FALSE;
      if ($varinfo === FALSE) {
        watchdog('om', "loadProperty called without varid", array(), 'error');
        return;
      }
      //dpm($thisvar, "Insuring ");
      if (!$prop) {
        watchdog('om', 'Could not Add Properties in plugin loadProperties');
        return FALSE;
      }
      //dpm($prop,'prop');
      // apply over-rides if given
      $prop->vardesc = isset($thisvar['vardesc']) ? $thisvar['vardesc'] : $varinfo->vardesc;
      $prop->varname = isset($thisvar['varname']) ? $thisvar['varname'] : $varinfo->varname;
      $prop->datatype = isset($thisvar['datatype']) ? $thisvar['datatype'] : $varinfo->datatype;
      // embed = is this being sved as part of a multi-object form?
      //         since properties could be saved as embedded, OR as stad-alone
      //         we have to set this because a standalone prop would have to synchronize itself 
      //         but others would not.
      $prop->embedded = TRUE;
      $entity->{$prop->propname} = $prop;
    }
  }
  
  public function insureProperty($entity, $thisvar) {
    // make sure all standard props are here
    $thisvar['featureid'] = $entity->{$this->row_map['id']};
    //dpm($thisvar, "insuring $thisvar[propname]");
    $thisvar = $thisvar + array('singularity' => 'name');
    $prop = om_model_getSetProperty($thisvar, $thisvar['singularity'], FALSE);
    return $prop;
  }
  
  public function updateProperties(&$entity) {
    // @todo: move this to the base plugin class 
    $props = $this->getDefaults($entity);
    foreach ($props as $thisvar) {
      $iprop = $this->insureProperty($entity, $thisvar);
      //$this->insureProperty($entity, $thisvar);
      if (!isset($thisvar['embed']) or ($thisvar['embed'] === TRUE)) {
        // load the property 
        // if a property with propname is set on $entity, send its value to the plugin 
        //   * plugin should be stored on the property object already
        // if prop on entity is an object already, handle directly, otherwise, load it
        //   the object method is advantageous because we can make things persist
        if (property_exists($entity, $thisvar['propname'])) {
          if (!is_object($entity->{$thisvar['propname']})) {
            // this has been set by the form API as a value 
            // so we need to load/create a property then set the value
            $thisvar['featureid'] = $entity->{$this->row_map['id']};
            //@todo: this needs to use the plugin handler for this instead of assuming propvalue instead of propcode
            //       why isn't this already an object after convert_attributes_to_dh_props is called?
            //     Location (the featureid loader property) is already loaded, but Location Sharing is NOT -- why????
            $prop = om_model_getSetProperty($thisvar, 'name');
            // now, apply the stashed value to the property
            foreach ($prop->dh_variables_plugins as $plugin) {
              // the default method will guess location based on the value unless overridden by the plugin
              $plugin->applyEntityAttribute($prop, $entity->{$thisvar['propname']});
            }
            entity_save('dh_properties', $prop);
          } else {
            $prop = $entity->{$thisvar['propname']};\
            // already a loaded form object, so just let it rip.\
            entity_save('dh_properties', $prop);
          }
        }
      } else {
        // just check here that we need to save properties that are not embedded, but have never been created
        if (empty($iprop->pid)) {
          entity_save('dh_properties', $iprop);
        }
      }
    }
  }
  
  public function addAttachedProperties(&$form, &$entity) {
    $dopples = $this->getDefaults($entity);
    //dpm($entity, 'addAttachedProperties');
    foreach ($dopples as $thisvar) {
      if (!isset($thisvar['embed']) or ($thisvar['embed'] === TRUE)) {
        $pn = $this->handleFormPropname($thisvar['propname']);
        $dopple = $entity->{$thisvar['propname']};
        // @todo: if this is a code variable, we should get propcode?
        // Attach_method is the parent override of embedded attributes.  This is weird, but will keep for now.
        switch ($this->attach_method) {
          case 'contained':
          $plugin = dh_variables_getPlugins($dopple);
          //dsm("Trying to attach $pn as contained for plugin class " . get_class($plugin));
          if ($plugin) {
            if (method_exists($plugin, 'attachNamedForm')) {
              //dsm("Using attachNamedForm()");
              $plugin->attachNamedForm($form, $dopple);
            } else {
              $plugin->formRowEdit($dopple_form, $dopple);
              $form[$pn] = $dopple_form['propvalue'];
            }
          }
          break;
          default:
          $dopple_form = array();
          dh_variables_formRowPlugins($dopple_form, $dopple);
          $form[$pn] = $dopple_form['propvalue'];
          break;
        }
      }
      if (isset($thisvar['#weight'])) {
        $form[$pn]['#weight'] = $thisvar['#weight'];
      }
    }
  }  
  
  public function convert_attributes_to_dh_props(&$entity) {
    // this will be called after a form submittal, the added form fields from attached props will be/
    // added as plain fields on the entity, we then grab them by name and handle their contents.
    $props = $this->getDefaults($entity);
    //dpm($props,'props from getDefaults');
    //error_log("Handling properties on $entity->propname " . print_r($props,1));
    foreach ($props as $thisvar) {
      $convert_value = FALSE; // flag to see if we need to convert (in case we are called multiple times)
      $load_property = FALSE;
      $propvalue = NULL;
      $propname = $thisvar['propname'];
      // form property name will be converted to a machine name by attachNamedForm() methods.
      // so now we just get this name here so that we can keep things straight but allow users descriptive names
      $pn = $this->handleFormPropname($propname);
      // check for conversion from value to property
      // this could need to change as fully loaded objects could be stored as array  that are then loaded as object or handled more completely
      // in Form API *I think*
      // but for now, this handles the case where a property value is stashed on the object
      // cases:
      // - property exists, and IS object: check for form API munged name and copy over, otherwise, do nothing
      // - property exists and is NOT object: stash the value, load the prop object, and setValue to stashed
      // - property does not exist: load property and return
      if (property_exists($entity, $propname) and !is_object($entity->{$propname})) {
        // if the prop is not an object, stash the value and load property, 
        $convert_value = TRUE;
        $propvalue = $entity->{$propname};
        $load_property = TRUE;
        //dsm("Propvalue from $propname = " . $propvalue);
      }
      if ( ($pn <> $propname) and property_exists($entity, $pn) ) {
        // handle case where prop name had spaces and was munged by form API
        // we assume that this is not going to be an object sine form API will return just a value
        $propvalue = $entity->{$pn};
        $convert_value = TRUE;
        //dsm("Propvalue from converted propname $pn = " . $propvalue);
      }
      if (!property_exists($entity, $propname) ) {
        $load_property = TRUE;
      }
      if ($load_property) {
        //dsm("Loading property $pn");
        //dsm("Initializing property $propname");
        $this->loadProperties($entity, FALSE, $propname);
      }
      // now, apply the stashed value to the property
      if ($convert_value and is_object($entity->{$propname})) {
        $prop = $entity->{$thisvar['propname']};
        foreach ($prop->dh_variables_plugins as $plugin) {
          // the default method will guess location based on the value unless overridden by the plugin
          $plugin->applyEntityAttribute($prop, $propvalue);
        }
        // insure this featureid.  There is probably a better way to do this earlier in the process.
        // we need to insure a valid parent entity first, save it, then load attached properties and update.  
        $prop->featureid = $entity->identifier();
      }
    }
    //dpm($entity,'entity post convert_attributes_to_dh_props()');
  }
  
  public function formRowSave(&$rowvalues, &$row) {
    // special form save handlers
    //dpm($rowvalues,'vals');
    //dpm($row,'entity');
    parent::formRowSave($rowvalues, $row);
  }
  
  public function buildContent(&$content, &$entity, $view_mode) {
    // @todo: handle teaser mode and full mode with plugin support
    foreach ($this->hiddenFields() as $hide) {
      unset($content[$hide]);
    }
    $modate = date("Y-m-d h:m a", dh_handletimestamp($entity->modified) );
    switch ($view_mode) {
      case 'openmi_json':
        parent::buildContent($content, $entity, $view_mode);
      break;
      case 'plugin':
      case 'teaser':
      default:
        $content['propname'] = array(
          '#type' => 'item',
          '#markup' => "<b>Name:</b> $entity->propname<sub>($entity->varname)</sub> <br>(mod: $modate)"
        );
        if (isset($content['propvalue'])) {
          $content['propvalue'] = array(
            '#type' => 'item',
            '#markup' => "<b>Value:</b> " . $entity->propvalue,
          ); 
        }
        if (isset($content['propcode'])) {
          $content['propcode'] = array(
            '#type' => 'item',
            '#markup' => "<b>Code:</b> " . $entity->propcode,
          ); 
        }
      break;
    }
  }
}

// @todo: evaluate dHVariablePluginCodeAttribute and dHVariablePluginNumericAttribute
//        for migration to base dh class
class dHVariablePluginCodeAttribute extends dHVariablePluginDefault {
  var $default_code = '';
  
  public function hiddenFields() {
    return array('tstime','featureid','tsendtime','entity_type','tsvalue');
  }
  public function formRowEdit(&$rowform, $row) {
    $varinfo = $row->varid ? dh_vardef_info($row->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    $rowform['propcode'] = array(
      '#title' => t($varinfo->varname),
      '#type' => 'textfield',
      '#description' => $varinfo->vardesc,
      '#default_value' => !empty($row->propcode) ? $row->propcode : "0.0",
    );
  }
  
  // @todo: move this into dh module once we are satisifed that it is robust
  public function attachNamedForm(&$rowform, $row) {
    $varinfo = $row->varid ? dh_vardef_info($row->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    $mname = $this->handleFormPropname($row->propname);
    // This is labeling overrides.  What should hierarchy be?
    // - This variation goes 
    //    - plugin
    //    - vardef 
    // But what should it be? Shouldn't it be UI/database over code?
    //    - entity sub-property label over-ride 
    //    - vardef sub-property 
    //    - vardef 
    //    - plugin 
    //    - module code default.
    // BUT! Plugins will often employ a generic, like om_constant, in which case 
    // the plugin *should* over-ride the base vardef.  Especially in the case of 
    // attached properties, in other words, when we have a meta form whose attachements 
    // are defined in code, like the chem sample, we need to over-ride.  Perhaps there is another case, 
    //   i.e. "plugin attached Properties" that take precedence. 
    // OR, perhaps we allow a setting at the vardef level to control the hierarchy of label/format changes?
    $rowform[$mname] = array(
      '#title' => isset($entity->title) ? t($entity->title) : t($entity->propname),
      '#type' => 'textfield',
      '#description' => t($entity->vardesc),
      '#default_value' => !empty($row->propcode) ? $row->propcode : "0.0",
    );
  }
  public function dh_getValue($entity, $ts = FALSE, $propname = FALSE, $config = array()) {
    // @todo: implement om routines
    return $this->getPropertyAttribute($entity);
  }
  
  public function applyEntityAttribute(&$property, $value) {
    $property->propcode = $value;
  }
  
  public function getPropertyAttribute($property) {
    return $property->propcode;
  }
  
  public function exportOpenMI($entity) {
    return array('object_class' => $entity->propcode);
  }
}

class dHVariablePluginNumericAttribute extends dHVariablePluginDefault {
  var $default_value = 0;
  var $default_code = '';
  var $pct_range = array('<5', 10, 25, 50, 75, 90, 100);
  var $pct_default = NULL;
  
  public function hiddenFields() {
    return array('startdate','featureid','enddate','entity_type','propcode');
  }
  public function formRowEdit(&$rowform, $entity) {
    $varinfo = $entity->varid ? dh_vardef_info($entity->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    switch ($entity->datatype) {
      // @todo: datatype does not actually get copied from vardef to props by the base class, it only grabs varid, varunits and varname, so we need to add datatype to make this work.
      case 'percent':
      $opts = $this->pct_list($this->pct_range);
      $rowform['propvalue'] = array(
        '#title' => t($varinfo->varname),
        '#type' => 'select',
        '#options' => $opts,
        '#empty_option' => 'n/a',
        '#description' => $varinfo->vardesc,
        '#default_value' => !empty($entity->propvalue) ? $entity->propvalue : $this->default_value,
      );
      break;
      case 'boolean':
      $opts = array(0 => 'False', 1 => 'True');
      $rowform['propvalue'] = array(
        '#title' => t($varinfo->varname),
        '#type' => 'select',
        '#options' => $opts,
        '#description' => $varinfo->vardesc,
        '#default_value' => !empty($entity->propvalue) ? $entity->propvalue : "$this->pct_default",
      );
      break;
      
      default:
      $rowform['propvalue'] = array(
        '#title' => t($varinfo->varname),
        '#type' => 'textfield',
        '#description' => $varinfo->vardesc,
        '#default_value' => !empty($entity->propvalue) ? $entity->propvalue : NULL,
      );
      break;
    }
  }
    
  public function pct_list($inc = 10) {
    $pcts = array();
    if (is_array($inc)) {
      // we already have our list of percents, just work it out
      foreach ($inc as $i) {
        $dec = floatval(preg_replace('/\D/', '', $i)) / 100.0;
        $pcts["$dec"] = $i . " %";
      }
    } else {
      $i = $inc;
      while ($i <= 100) {
        $dec = floatval($i) / 100.0;
        $pcts["$dec"] = $i . " %";
        $i += $inc;
      }
    }
    return $pcts;
  }
  
  // @todo: move this into dh module once we are satisifed that it is robust
  public function attachNamedForm(&$rowform, $entity) {
    $varinfo = $entity->varid ? dh_vardef_info($entity->varid) : FALSE;
    //dpm($varinfo,'var info');
    if (!$varinfo) {
      return FALSE;
    }
    //dpm($entity, 'attaching');
    $formshell = array();
    // use standard formatting to enable choices.
    $this->formRowEdit($formshell, $entity);
    $mname = $this->handleFormPropname($entity->propname);
    $rowform[$mname] = $formshell['propvalue'];
    $rowform[$mname]['#title'] = isset($entity->title) ? t($entity->title) : t($entity->propname);
    $rowform[$mname]['#description'] = t($entity->vardesc);
  }
  
  public function applyEntityAttribute(&$property, $value) {
    $property->propvalue = $value;
  }
  
  public function getPropertyAttribute($property) {
    return $property->propvalue;
  }
  
  public function dh_getValue($entity, $ts = FALSE, $propname = FALSE, $config = array()) {
    // @todo: implement om routines getPropertyAttribute() in base class 
    return $this->getPropertyAttribute($entity);
  }
}

class dHOMBaseObjectClass extends dHVariablePluginDefaultOM {
  // @todo: inherit dHVariablePluginDefaultOM, which will handle auto-adding of subprops in EditForm
  var $object_class = FALSE; // use to be BlankShell, but BlankShell will all be saved as modelElement 
  var $use_new_save = FALSE; // use to be BlankShell, but BlankShell will all be saved as modelElement 
  var $path = "/var/www/html/om/";
  var $state = array();
  var $setvarnames = array();
  var $attach_method = 'contained';
  var $om_template_id = 347359; // blankShell object 
  var $modays = array(1 => 31, 2 => 28.25, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31);
  
  public function hiddenFields() {
    $hidden = array(
      'pid', 
      'featureid', 
      'entity_type', 
      'bundle', 
      'varid', 
      'dh_link_admin_pr_condition', 
      'field_prop_upload',
      'object_class',
      'startdate',
      'enddate',
      'varname'
    );
    return $hidden;
  }
  
  public function load(&$entity) {
    // get field default basics
    //dpm($entity, 'load()');
    // can't call loadProperties here, it causes some kind of endless loop
    //$this->loadProperties($entity);
  }
  
  public function loadProperties(&$entity, $overwrite = FALSE, $propname = FALSE, $force_embed = FALSE) {
    // when we load these properties, we need to disable pushing to remote elements if an om_element_connection is set
    // otherwise, we get a multitude of remote saves triggered each time these properties are saved if this is an 
    // initial setup.  We will rely on things called after loadProperties() to save remotes.
    // grab set_remote, stash a copy
    $path = array();
    $this->findRemoteOMElement($entity, $path);
    $set_remote_save = $this->set_remote;
    $this->set_remote = 0;
    // call parent loadProperties() method 
    parent::loadProperties($entity, $overwrite, $propname, $force_embed);
    // restore set_remote to original value
    $this->set_remote = $set_remote_save;
  }

  function getPublicVars($entity, &$publix = array()) {
    //dpm($this,"called getPublicVars()");
    // gets all viewable variables
    /*
    $publix += array_keys($this->state); 
    $publix += $this->setvarnames; 
    $publix += $this->getPublicProps($entity); 
    $publix += $this->getPublicProcs($entity); 
    $publix += $this->getPublicInputs($entity); 
    $publix = array_unique($publix);
    */
    $publix = array_unique(
      array_merge(
        $publix,
        array_keys($this->state), 
        $this->setvarnames, 
        $this->getPublicProps($entity), 
        $this->getPublicProcs($entity), 
        $this->getPublicInputs($entity)
      )
    );
    sort($publix);
    //dpm($publix, 'getPublicVars ' . get_class($this));
    return $publix;
  }

  function getLocalVars($entity) {
    // gets all viewable variables
    // NOT YET FULLY OPERATIONAL
    $this->state = array();
    $publix = array_unique(array_merge(array_keys($this->state), $this->getPublicProps($entity), $this->getPublicProcs($entity), $this->getPublicInputs($entity)));

    return $publix;
  }

  public function getPublicProps($entity) {
    // gets only properties that are visible (must be manually defined for now, could allow this to be set later)
    // taken directly from om library -- will revisit after full porting
    // children will subclass this and add their own like:
    // $publix = parent::getPublicProps($entity)
    $publix = array('name','objectname','description','componentid', 'startdate', 'enddate', 'dt', 'month', 'day', 'year', 'thisdate', 'the_geom', 'weekday', 'modays', 'week', 'hour', 'flow_mode', 'run_mode', 'timestamp');
    return $publix;
  }

  function getDataSources() {
    // taken directly from om library -- will revisit after full porting
    return array();
    return $this->datasources;
  }
  function getPublicProcs($entity) {
    // taken directly from om library -- will revisit after full porting
    // @todo: retrieve parent props, and local props.
    $procnames = dh_get_dh_propnames('dh_properties', $entity->identifier());
    if (!is_array($procnames)) {
      $procnames = array();
    }
    // @todo: add in vars that should be set on parent, i.e., for a local_channel proc, it would set local_channel_Qin, local_channel_Qout, local_channel_depth, ...
    // for broadcast vars, all local varnames that are heard get pulled.
    // can store these in a sub-prop called "public_procs" 
    //dpm($procnames,'getPublicProcs ' . get_class($this));
    return $procnames;
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
  function getPublicInputs($entity) {
    // taken directly from om library -- will revisit after full porting
    return array();
    // gets all viewable variables
    // should include broadcast listeners, and map_model_linkages
    if (is_array($this->inputnames)) {
       return $this->inputnames;
    } else {
       return array();
    }
  }
  function getPublicComponents($entity) {
    // taken directly from om library -- will revisit after full porting
    return array();
    // gets all viewable variables
    if (is_array($this->compnames)) {
       return $this->compnames;
    } else {
       return array();
    }
  }
  function getPrivateProps($entity) {
    // taken directly from om library -- will revisit after full porting
    return array();
    // gets all viewable variables in the local context only
    $privitz = array();

    return $privitz;
  }

  public function formRowRender(&$rowvalues, &$row) {
    // special render handlers when displaying in a grouped property block
    // show select list for varid
    // update form via ajax when changed
    parent::formRowRender($rowvalues, $row);
  }
  
  public function setUp(&$entity) {
    //dpm($entity, 'setUp()');
  }
  
  public function saveObjectClass(&$entity) {
    // get field default basics
    // @todo: this should be done in getDefaults() function 
    if ($this->object_class) {
      $values = array(
        'entity_type' => $entity->entityType(),
        'propcode' => $this->object_class,
        'propname' => 'object_class',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_object_class', TRUE),
      );
      dh_update_properties($values, 'singular');
      //error_log("Saving object_class: " . print_r($values,1));
    }
  }
  public function getDefaults($entity, &$defaults = array()) {
    // Example:
    /*
    $defaults += array(
      'berry_weight_g' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'Berry Weight',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varkey' => 'berry_weight_g',
        'varid' => dh_varkey2varid('berry_weight_g', TRUE),
        'embed' => TRUE, // defaults to TRUE, set this to FALSE to prevent embedding
      ),
    );
    */
    return $defaults;
  }
  
  public function insert(&$entity) {
    //$entity->propname = 'blankShell';
    $this->saveObjectClass($entity);
    parent::insert($entity);
    $this->loadProperties($entity, FALSE);
    $this->synchronize($entity);
  }
  
  public function update(&$entity) {
    //$entity->propname = 'blankShell';
    //dsm("update() " . $entity->propname);
    $this->saveObjectClass($entity);
    parent::update($entity);
    $this->loadProperties($entity, FALSE);
    // should we do this here?
    $this->synchronize($entity);
  }
  
  public function save(&$entity) {
    //$entity->propname = 'blankShell';
    //dsm("save() " . $entity->propname);
    parent::save($entity);
    // now, find out if we are suppose to sync to a remote server
    // 1. $elid = findRemoteOMElement($entity, $path) ; this returns $elid and increments $path
    // 2. if $elid = 0 then no remote sync
    // 3. Determine how to save
    $path = array(); // we init here, since save() shouldn't be called in this chain on any upstream objects
    //if ($entity->is_new) {
      // otherwise, we wait till the update() routine is called.
      //$this->synchronize($entity);
    //}
  }
  
  public function synchronize(&$entity, $force = FALSE) {
    $json2d = $this->checkParentJSON($entity);
    // - We will skip if this is a child of an object that uses json2d for synch, 
    //   unless $force == TRUE, or not embedded 
    // - Will skip if parent has use_new_save and this is embedded
    // - If NOT embedded (edited solo), we DO want to force and skip loading the parent.
    if ($json2d and !$force and ($entity->embedded === TRUE) ) {
      return;
    }
    //error_log("**** synchronize() called from $entity->propname");
    $elid = $this->findRemoteOMElement($entity, $path);
    $new_save = om_load_dh_property($entity, 'use_new_save');
    if (is_object($new_save) or ($this->use_new_save === TRUE)) {
      if ( ($new_save->propvalue == 1) or ($this->use_new_save === TRUE) and (intval($this->set_remote) > 0)) {
        // We should check if this is a non-sub-component otherwise, the om_set_element method will be incorrect.
        dpm("Using new save method");
        $exp = $this->exportOpenMI($entity);
        //dsm($entity, 'entity - does it have valuetype??');
        dpm($exp,"Using JSON export mode");
        $exp_json = addslashes(json_encode($exp[$entity->propname]));
        // now strip escaped single quotes because 
        // valid json ONLY permits escapes of double quotes since all 
        // json elements should be surrounded in double quotes.
        $exp_json = str_replace("\'", "'", $exp_json);
        om_set_element($elid, $exp_json);
        return;
      }
    }
    // take the last parent out since that is just the name of the model element
    // and we don't need that, since we have the elementid 
    // if this was a form API use case we could keep the parent name
    array_pop($path);
    // $path will be modified by the methods
    // the property set_remote allows us to disable this functionality, for example
    // if we are doing an insert from an import, we wouldn't want to do this.
    if (($elid > 0) and (intval($this->set_remote) > 0)) {
      $this->setAllRemoteProperties($entity, $elid, $path);
      if (count($path) == 0) {
        // if path is zero length it means that this is an exact match, so set the vahydro_hydroid prop 
        // on the OM element 
        $this->setRemoteProp($entity, $elid, array('value', 'vahydro_hydroid'), $entity->pid, 'textField');
      }
    }
  }
  
  public function setAllRemoteProperties($entity, $elid, $path) {
    // this is to be done on save.  The base class saves nothing
    // subclasses can save other things
  }
  
  public function setRemoteProp($entity, $elid, $path, $propvalue, $object_class = FALSE, $mode = '') {
    if ( ($this->set_remote === '0') or ($entity->set_remote === 0)) {
      dpm("set_remote = FALSE - returning without setting $entity->propname");
      return;
    } else {
      //$db = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
      //error_log("Debug:" . print_r($db,1));
      //error_log("set_remote = $this->set_remote - Setting remote for $entity->propname");
    }
    // object_class ONLY refers to the base component being added to a model element
    // if a nested property is being set, like a matrix on a hydroImpSmall, the object STILL
    // refers to hydroImpSmall, and the parent component has to be able to handle the prop by name
    // this is due to the limitation of the OM data model, this won't be a limit when we go to full json objects
    // but then this will be obsolete.
    if ( ($object_class == FALSE) and (count($path) > 1)) {
      $object_class = $this->object_class;
      //watchdog('om', "Missing objectclass called for a nested OM property $propname on $parentname");
    }
    // all subclasses should handle this in
    // 
    $setstr = FALSE;
    $test_only = FALSE;
    // handle when the propvalue is an object, if it has a plugin
    if (is_object($propvalue)) {
      $plugin = dh_variables_getPlugins($propvalue);
      if (method_exists($plugin, 'getPropertyAttribute')) {
        $propvalue = $plugin->getPropertyAttribute($propvalue);
      } else {
        $propvalue = $propvalue->propvalue;
      }
    }
    // @todo
    // constants should have 1 layer lower, 
    // Ex: a max_storage property on an impoundment comes in as 3 parents, 
    //     but really should be handled by the 2 parent code
    // Ex: run_mode on the parent model object should resolve 2 1 parent, but comes in as 2
    // Equations ARE handled correctly however, since the 
    //dpm($path,'path');
    //dpm($entity,'entity');
    //dsm("Handling a property on a subcomp of the element ($elid) depth = " . count($path));
    switch (count($path)) {
      case 1:
        list($propname) = $path;
        // this is a property of the element itself
        // @todo: set a regular attribute using batch_setprop.php
        //$setstr = "php setprop.php $elid \"$propname=$propvalue\" ";
        // modified to use base OM plumbing
        $setstr = FALSE;
        om_setprop($elid, $propname, $propvalue);
      break;
      case 2:
        list($propname, $parentname) = $path;
        // this is a property on a subcomp of the element
        // setprop_mode = 'json' for matrices, '' is default
        $setstr = "php set_subprop.php $elid \"$parentname\" \"$object_class\" \"$propname\" \"$propvalue\" \"$mode\" 0 ";
      break;
      case 3:
        // @todo: this would be a sub-comp of a sub-comp,
        //        Ex: a matrix on a subcomp, like the land use matrix of a CBP object or the stage-storage of lake
        //        Or, like an equation on a subcomp like channelObject
        //        at this time I don't think we should have any Equations sub-subs, but just in case 
        dpm($path, "path to setRemoteProp()");
        list($propname, $parentname, $grandparentname) = $path;
        // this is a property on a subcomp of the element
        //$setstr = "php set_subprop.php $elid $parentname $object_class $propname \"$propvalue\" \"$mode\" 0 ";
        $setstr = "php set_subprop.php $elid \"$grandparentname\" \"$object_class\" \"$parentname:$propname\" \"$propvalue\" \"$mode\" 0 ";
        // @todo: change syntax from elid propname "subpropname=value" parent_object_class overwrite
        //        to:
        //        elid propname subpropname subpropvalue parent_object_class setprop_mode overwrite 
        //dpm( $setstr, "3 level subcomp not yet handled -- will not execute ");
        $test_only = FALSE;
      break;
      default:
        drupal_set_message("Can not handle remote update of properties with depth = " . count($path));
      break;
    }
    if ($setstr and !$test_only) {
      $cmd = "cd $this->path \n";
      $cmd .= $setstr;
      //dpm( $path, "Exec Path ");
      dpm( $cmd, "Executing ");
      shell_exec($cmd);
    }
    if ($test_only) {
      $cmd = "Testing Only. \n";
      $cmd = "cd $this->path \n";
      $cmd .= $setstr;
      //dpm( $path, "Testing Path ");
      //dpm( $cmd, "Testing to execute ");
    }
  }
  
  public function getRemoteProp($entity, $elid, $path, $object_class = FALSE) {
    // this element connection does not currently use this, but its children props might
    // @todo: make this work - for now just return
    if ( ($object_class == FALSE) and (count($path) > 1)) {
      $object_class = $this->object_class;
      //watchdog('om', "Missing objectclass called for a nested OM property $propname on $parentname");
    }
    // @todo:
    //   - eliminate propname from function args since it is on the $path stack
    //   - 
    //   - decide if we need to handle the last argument, which is the name of the final containing element
    //     I think that this is superfluous information, or redundant, since we have the elementid
    //     But if we think in terms of the Drupal Form API, this information becomes very relevant.
    //     So for now we will include it, but pop it off BEFORE sending to setRemoteProp
    
    // this will vary depending upon the variable being set, and also the depth
    // thought most often edit_subcomp_props will be 90% of transactions??
    // all subclasses should handle this in
    // 
    $setstr = FALSE;
    $test_only = FALSE;
    // @todo
    // constants should have 1 layer lower, 
    // Ex: a max_storage property on an impoundment comes in as 3 parents, 
    //     but really should be handled by the 2 parent code
    // Ex: run_mode on the parent model object should resolve 2 1 parent, but comes in as 2
    // Equations ARE handled correctly however, since the 
    //dpm($path,'path');
    //dsm("Handling a property on a subcomp of the element ($elid) depth = " . count($path));
    switch (count($path)) {
      case 1:
        list($propname) = $path;
        // this is a property of the element itself
        // @todo: set a regular attribute using batch_setprop.php
        $setstr = "php getprop.php $elid \"$propname=$propvalue\" ";
      break;
      case 2:
        list($propname, $parentname) = $path;
        // this is a property on a subcomp of the element
        $setstr = "php get_subprop.php $elid $parentname \"$propname=$propvalue\" $object_class 0 ";
      break;
      case 3:
      // @todo: this would be a sub-comp of a sub-comp, like an equation on a subcomp like channelObject
      //        at this time I don't think we should have any of these, but just in case 
        list($propname, $parentname, $grandparentname) = $path;
        // this is a property on a subcomp of the element
        $setstr = "php get_subprop.php $elid $parentname \"$propname=$propvalue\" $object_class 0 ";
        //dpm( $setstr, "3 level subcomp not yet handled -- will not execute ");
        $setstr = FALSE;
      break;
      default:
        drupal_set_message("Can not handle remote update of properties with depth = " . count($path));
      break;
    }
    if ($setstr and !$test_only) {
      $cmd = "cd $this->path \n";
      $cmd .= $setstr;
      //dpm( $cmd, "Executing ");
      shell_exec($cmd);
    }
  }
    
  public function findRemoteOMElementProp($entity) {
    // check for a property with varkey om_element_connection on this entity
    $elvar_info = array(
      'featureid' => $entity->pid,
      'entity_type' => 'dh_properties',
      'bundle' => 'dh_properties',
      'bundle' => 'om_element_connection',
      'varid' => dh_varkey2varid('om_element_connection', TRUE),
    );
    $elvar = dh_properties_enforce_singularity($elvar_info, 'singular');
    //dpm($elvar_info, " dh_get_properties(elvar_info, 'singular')");
    //dpm($elvar, " elvar");
    return $elvar;
  }
  
  public function findRemoteOMElement($entity, &$path) {
    //dpm($entity, "findRemoteOMElement @ depth = $path");
    $elid = 0;
    $path[] = $entity->propname;
    $elvar = $this->findRemoteOMElementProp($entity);
    if ($elvar) {
      $elid = $elvar->propvalue;
      $this->set_remote = $elvar->propcode;
    } else {
      // get parent
      $parent = $this->getParentEntity($entity);
      //dpm($parent,"Looking at object parent for remote element link.");
      if (isset($parent->dh_variables_plugins) and is_array($parent->dh_variables_plugins)) {
        foreach ($parent->dh_variables_plugins as $plugin) {
          if (is_object($plugin) and method_exists($plugin, 'findRemoteOMElement')) {
            $elid = $plugin->findRemoteOMElement($parent, $path);
          }
          $this->set_remote = property_exists($plugin, 'set_remote') ? $plugin->set_remote : 0;
        }
      }
    }
    // if not, load parent, check for findRemoteOMElement() method, if present, call it, if not, return
    return $elid;
  }
  
  public function checkParentJSON($entity) {
    // we check here to see if this resides under a parent with JSON syncing.
    // if so, we don't synch ourself, even if we have json synching, under the 
    // assumption that the parent will handle it.
    $parent = $this->getParentEntity($entity);
    $pplug = dh_variables_getPlugins($parent);
    if (is_object($pplug)) {
      if ($pplug->json2d) {
        return TRUE;
      }
      // 
      if ($pplug->use_new_save) {
        return TRUE;
      }
      if (method_exists($pplug, 'checkParentJSON')) {
        $json2d = $pplug->checkParentJSON($parent);
        return $json2d;
      }
    }
    return FALSE;
  }
  
  public function create(&$entity) {
    // set up defaults?
    $entity->propname = !empty($entity->propname) ? $entity->propname : (!empty($this->object_class) ? $this->object_class : 'un-named');
    if ($this->default_bundle) {
      $entity->bundle = $this->default_bundle;
    }
  }
  
  public function formRowEdit(&$form, $entity) {
    // special render handlers when displaying in a grouped property block
    //dpm($entity, 'entity');
    parent::formRowEdit($form, $entity);
    $this->hideFormRowEditFields($form);
    if ($this->object_class) {
      //$form['varid']['#default_value'] = 'om_class_BlankShell';
      $opts = dh_vardef_varselect_options(array("vocabulary = 'om_object_classes'"));
      $form['varid']['#options'] = $opts;
      $form['varid']['#title'] = 'Model Object Type';
      $form['varid']['#description'] = 'Select object class here. ';
      $form['varid']['#type'] = 'select';
    } else {
      $form['varid']['#type'] = 'hidden';
    }
    $form['propname']['#default_value'] = empty($entity->propname) ? $this->object_class : $entity->propname;
    $form['propname']['#title_display'] = 'before';
    $form['propname']['#title'] = 'Name';
    $form['propname']['#type'] = 'textfield';
    $form['proptext']['#weight'] = 10;
    //$form['propname']['#markup'] = 'object_class';
    // if this is an existing object (has pid) 
    // check to see if it has any missing default properties,
    // if so, offer to add them automatically on save.
    // make this weight 20 so it's last thing before save button
    $defprops = $this->getDefaults($entity);
    
  }
  
  public function applyEntityAttribute(&$property, $value) {
    $property->propvalue = $value;
  }
  
  public function getPropertyAttribute($property) {
    return $property->propvalue;
  }
  
  public function dh_getValue($entity, $ts = FALSE, $propname = FALSE, $config = array()) {
    // @todo: implement om routines getPropertyAttribute() in base class 
    return $this->getPropertyAttribute($entity);
  }
}

class dHOMElementConnect extends dHOMBaseObjectClass {
  var $object_class = FALSE;
  var $default_bundle = 'dh_properties';
  var $can_embed = FALSE; // om_element_connection can never be embedded.
  var $set_remote = FALSE; // should we push changes?
  
  public function findRemoteOMElement($entity, &$path) {
    // since this connector is the final model container, we know the elid is by definition the propvalue
    $elid = $entity->propvalue;
    // don't increment here since this is a property on another object that already increments
    //$path[] = $entity->propname;
    return $elid;
  }
  
  public function getDefaults($entity, &$defaults = array()) {
    parent::getDefaults($entity, $defaults);
    // @tbd: 
    // - historic_monthly_pct
    // - historic_annual 
    // - consumption
    // - surface_mgd : an equation that always equals wd_mgd, since these are all ssumed to be intakes not wells
    $defaults = array(
      'om_template_id' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => NULL,
        'propname' => 'om_template_id',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'title' => 'om_template_id',
        'vardesc' => 'om_template_id.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
      'remote_parentid' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => NULL,
        'propname' => 'remote_parentid',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'title' => 'Remote Parentid',
        'vardesc' => 'Remote Parent (used only for creating new remote objects).',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ), 
    ) + $defaults;
    //dpm($defaults,'defs');
    return $defaults;
  }
  
  public function setRemoteProp($entity, $elid, $path, $propvalue, $object_class = FALSE) {
    // this element connection does not currently use this, but its children props might
  }
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    $varinfo = $entity->varid ? dh_vardef_info($entity->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    $form["propname"]['#default_value'] = empty($form["propname"]['#default_value']) ? $varinfo->varkey : $form["propname"]['#default_value'];
    $form['propcode'] = array(
      '#title' => t('Remote Synch Option'),
      '#type' => 'select',
      '#options' => array('0'=>'Never push changes', '1'=>'Always push change', 'pull_once' => 'One-Time Pull Remote Properties on Save()', 'clone' => 'One-Time Clone Remote Element and Link', 'push_once' => 'One Time Push all Properties'),
      '#description' => '',
      '#default_value' => !empty($entity->propcode) ? $entity->propcode : "",
    );
    $remote_parentid = $entity->remote_parentid->propvalue;
    $form['remote_parentid'] = array(
      '#title' => t('Remote parent of remote object (for cloning)'),
      '#type' => 'textfield',
      '#description' => '',
      '#states' => array(
        'visible' => array(
          ':input[name="propcode"]' => array('value' => "clone"),
        ),
      ),
      '#default_value' => !empty($remote_parentid) ? $remote_parentid : "-1",
    );
    
    $parent = $this->getParentEntity($entity);
    //dpm( $parent, "parent ");
    $pplug = dh_variables_getPlugins($parent);
    $default_template_id = $pplug->om_template_id; 
    $last_template_id = $entity->om_template_id->propvalue;
    $form['om_template_id'] = array(
      '#title' => t('Remote template ID'),
      '#type' => 'textfield',
      '#description' => '',
      '#states' => array(
        'visible' => array(
          ':input[name="propcode"]' => array('value' => "clone"),
        ),
      ),
      '#default_value' => !empty($last_template_id) ? $last_template_id : $default_template_id,
    );
  }
  public function save(&$entity) {
    parent::save($entity);
    if ($entity->propcode === 'pull_once') {
      error_log("Entity with featureid = $entity->featureid, and pid = $entity->pid has propcode '$entity->propcode'");
      // pull from remote, then set this back to previous entity value 
      $this->pullFromRemote($entity);
      // @todo: because the entity is already updatred by the time we get here, we can't retrieve the previous synch setting, so we assume that it is OK to push remote changes after this save and pull is complete.  Why?  Can't we intercept before entity is updated?
      $entity->propcode = '1';
    }
    if ($entity->propcode === 'push_once') {
      // pull from remote, then set this back to previous entity value 
      $this->pushAllToRemote($entity);
      // @todo: because the entity is already updatred by the time we get here, we can't retrieve the previous synch setting, so we assume that it is OK to push remote changes after this save and poull is complete.  Why?  Can't we intercept before entity is updated?
      $entity->propcode = '1';
    }
    if ($entity->propcode === 'clone') {
      // pull from remote, then set this back to previous entity value 
      $this->cloneRemoteElement($entity);
      // @todo: because the entity is already updatred by the time we get here, we can't retrieve the previous synch setting, so we assume that it is OK to push remote changes after this save and poull is complete.  Why?  Can't we intercept before entity is updated?
      $entity->propcode = '1';
    }
  }
  
  public function pullFromRemote($entity) {
    global $base_url;
    $cmd = "cd " . DRUPAL_ROOT . '/' . drupal_get_path('module', 'om') . "/src/ \n";
    $cmd .= "drush om.migrate.element.php pid $entity->propvalue $entity->featureid ";
    dpm( $cmd, "Executing ");
    shell_exec($cmd);
  }
  
  public function pushAllToRemote($entity) {
    global $base_url;  
    $parent = $this->getParentEntity($entity);
    // nothing yet - need to exclude om_element_connection properties, or at least nullify settings to prevent redundant updates.
    $propnames = dh_get_dh_propnames($parent->entityType(), $parent->identifier());
    //dpm($propnames, "Propnames ");
    foreach ($propnames as $propname) {
      // get property by name
      // save the property
      $prop = om_load_dh_property($parent, $propname);
      //dpm($prop,'prop ' . $propname);
      if (is_object($prop)) {
        // check to see that property does *not* have it's own remote element 
        // omit if it does since it is an embedded virtual prop
        $elid = om_load_dh_property($prop, 'om_element_connection');
        if (!is_object($elid)) {
          dsm("Pushing property $prop->propname to remote");
          error_log("Pushing property $prop->propname to remote");
          entity_save('dh_properties', $prop);
        } else {
          dsm("Skipping stand-alone model object $prop->propname ");
        }
      }
    }
  }
  
  public function cloneRemoteElement(&$entity) {
    global $base_url;
    $parent = $this->getParentEntity($entity);
    $cmd = "cd $this->path \n";
    $cmd .= "php fn_copy_element.php 37 $entity->om_template_id $entity->remote_parentid -1 \"$parent->propname\" ";
    dpm( $cmd, "Executing ");
    //dpm( $entity, "Entity ");
    $returned = shell_exec($cmd);
    $entity->propvalue = intval($returned);
    dpm(intval($returned),'Remote elementid');
  }
}

class dHOMModelElement extends dHOMBaseObjectClass {
  // All objects of this class and inherited by this class
  // should assume that propcode is used to describe the primary 
  // model version/scenario.  However, a model object may belong to 
  // multiple scenarios which can be defined by the om_model_scenario subcomp
  var $object_class = 'modelObject';
  var $use_new_save = TRUE; // this is a standalone, use new method
  
  public function hiddenFields() {
    $hidden = parent::hiddenFields();
    return $hidden;
  }
  
  public function setAllRemoteProperties($entity, $elid, $path) {
    parent::setAllRemoteProperties($entity, $elid, $path);
    // this is to be done on save.  The base class only saves name and description, 
    // subclasses can save other things
    //dpm($path, 'original path to setAllRemoteProperties()');
    array_unshift($path, 'name');
    $this->setRemoteProp($entity, $elid, $path, $entity->propname, $this->object_class);
    // removes the name 
    array_shift($path);
    array_unshift($path, 'description');
    $this->setRemoteProp($entity, $elid, $path, $entity->proptext['und'][0]['value'], $this->object_class);
    //$this->setRemoteProp($entity, $elid, $path, 'description', $this->proptext);
  }
  
  public function delete(&$entity) {
    // @todo: ask if we want to delete the corresponding remote
    // @todo: enable to delete the corresponding remote
    //dpm($entity,'plugin delete() method called');
    parent::delete($entity);
  }
  
  public function getDefaults($entity, &$defaults = array()) {
    $defaults = parent::getDefaults($entity, $defaults);
    $defaults += array(
      'run_info' => array(
        'entity_type' => $entity->entityType(),
        'propvalue_default' => -1, // default to "CBP Phase 5.3" mode 
        'propname' => 'run_info',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Most recent run information, start, end and run_id.',
        'title' => 'Last Run ID',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'defaultval' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'defaultval',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Default Value.',
        'title' => 'Default Value',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'cache_date' => array(
        'entity_type' => $entity->entityType(),
        'propvalue_default' => -1, // default to "CBP Phase 5.3" mode 
        'propname' => 'cache_date',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Cache Date setting of most recent run.',
        'title' => 'Last Cache Date',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
    );
    return $defaults;
  }
  
  public function exportOpenMIBase($entity) {
    // creates the base properties for this class
    $export = array(
      $entity->propname => array(
        'host' => $_SERVER['HTTP_HOST'], 
        'id' => $entity->pid, 
        'name' => $entity->propname, 
        'scenario' => array(
          'name' => 'scenario',
          'object_class' => 'textField',
          'value' =>  $entity->propcode
        ),  
      )
    );
    return $export;
  }
  
  public function formRowEdit(&$form, $entity) {
    // special render handlers when displaying in a grouped property block
    //dpm($entity, 'entity');
    parent::formRowEdit($form, $entity);
    /*
    // add special save mode 
    $opts = array(0=>'FALSE', 1=>'TRUE');
    $form['new_save'] = array();
    $form['new_save']['#options'] = $opts;
    $form['new_save']['#title'] = 'Use New All JSON object saving?';
    $form['new_save']['#type'] = 'select';
    */
  }
}

class dHOMModelContainer extends dHOMModelElement {
  var $object_class = 'modelContainer';
  
  public function hiddenFields() {
    $hidden = array_merge(array('propvalue', 'startdate', 'enddate'), parent::hiddenFields());
    return $hidden;
  }
  
  public function getDefaults($entity, &$defaults = array()) {
    $defaults = parent::getDefaults($entity, $defaults);
    $defaults += array(
      'run_mode' => array(
        'entity_type' => $entity->entityType(),
        'propvalue_default' => 2, // default to "current" mode 
        'propname' => 'run_mode',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Default Run Mode, if not over-ridden by sub-props, globals, or broadcast.',
        'title' => 'Run Mode default',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'flow_mode' => array(
        'entity_type' => $entity->entityType(),
        'propvalue_default' => 3, // default to "CBP Phase 5.3" mode 
        'propname' => 'flow_mode',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Default Flow Mode, if not over-ridden by sub-props, globals, or broadcast.',
        'title' => 'Flow Mode default',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
    );
    return $defaults;
  }
  
  public function formRowEdit(&$rowform, $entity) {
    parent::formRowEdit($rowform, $entity);
    $rowform['propname']['#default_value'] = empty($entity->propname) ? $this->object_class : $entity->propname;
    $rowform['propname']['#title_display'] = 'before';
    $rowform['propname']['#title'] = 'Name';
    $rowform['propname']['#type'] = 'textfield';
    $rowform['propcode']['#title'] = 'Model Version';
    $rowform['proptext']['und']['#title'] = 'Model Description';
    $rowform['proptext']['und'][0]['value']['#title'] = 'Model Description';
    $rowform['proptext']['#weight'] = 10;
  }
}

class dHOMSubComp extends dHOMBaseObjectClass {
  // this class has a name, and a description, an exec_hierarchy and other atributes
  // @todo: add basic handling of things other than descriptions
  var $use_new_save = FALSE; // this is a sub-comp, it already has a separate method
  
  public function hiddenFields() {
    return array('pid', 'startdate', 'enddate', 'varid', 'featureid', 'entity_type', 'bundle','dh_link_admin_pr_condition');
  }
  
  public function setAllRemoteProperties($entity, $elid, $path) {
    // this toggles parent method with full object json transfer
    if ($this->json2d) {
      $ppath = $path;
      array_unshift($ppath, $entity->propname);
      //$this->setRemoteProp($entity, $elid, $ppath, "", $this->object_class);
      $exp = $this->exportOpenMI($entity);
      dpm($exp,"Using JSON export mode");
      $exp_json = addslashes(json_encode($exp[$entity->propname]));
      $this->setRemoteProp($entity, $elid, $ppath, $exp_json, $this->object_class, 'json-2d');
    } else {
      parent::setAllRemoteProperties($entity, $elid, $path);
      //dpm($path, 'original path to setAllRemoteProperties()');
      //dpm($entity, 'subcomp entity to setAllRemoteProperties()');
      // create the base property if needed.
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
  public function getDefaults($entity, &$defaults = array()) {
    $defaults = parent::getDefaults($entity, $defaults);
    $defaults += array(
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
      'defaultval' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'defaultval',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Default value.',
        'title' => 'Default Value',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
    );
    return $defaults;
  }
  
  public function delete(&$entity) {
    dpm($entity,'plugin delete() method called');
    $comp_path = array(); // initialize the path var. 
                     // We will than use it later to determine if we should 
                     // Delete the remote element 
    $propname = $entity->propname;
    $elid = $this->findRemoteOMElement($entity, $comp_path);
    dpm($comp_path,'Delete subcomp path');
    dpm($elid,'Delete subcomp elid');
    // findRemoteElement
    // Check path depth - if this is a 1st level sub-comp delete, if not, return 
    switch (count($comp_path)) {
      case 2:
        // this is a property on a subcomp of the element
        $setstr = "php delete_subcomp.php $elid \"$propname\" ";
      break;
    }
    if ($setstr and !$test_only) {
      $cmd = "cd $this->path \n";
      $cmd .= $setstr;
      dpm( $cmd, "Executing ");
      shell_exec($cmd);
    }
    parent::delete($entity);
  }
  
  public function getPublicProcs($entity) {
    $procnames = parent::getPublicProcs($entity);
    //dpm($procnames, 'local procs');
    $parent = $this->getParentEntity($entity);
    $plugin = dh_variables_getPlugins($parent);
    if ($plugin) {
    //dpm($plugin,'plugin');
      if (method_exists($plugin, 'getPublicProcs')) {
        $pprocs = $plugin->getPublicProcs($parent);
      }
      //dpm($pprocs, 'parent procs');
      $procnames = array_merge($pprocs, $procnames);
    }
    //dpm($procnames, 'procs' . get_class($this));
    return $procnames;
  }
  
  public function getPublicVars($entity, &$publix = array()) {
    // @todo: if this works, move to the dHOMSubComp class
    parent::getPublicVars($entity, $publix);
    $parent = $this->getParentEntity($entity);
    $plugin = dh_variables_getPlugins($parent);
    if ($plugin) {
    //dpm($plugin,'plugin');
      if (method_exists($plugin, 'getPublicVars')) {
        $plugin->getPublicVars($parent, $publix);
      }
    }
    return $publix;
  }
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    //dpm($form,"equation before 1st customization");
    $form['propname']['#title'] = 'Name';
    $form['propcode']['#title'] = 'Code';
    $form['propcode']['#prefix'] = ' = ';
    $form['propvalue']['#title'] = 'Value';
    $form['propcode']['#prefix'] = ' = ';
  }
  
  public function attachNamedForm(&$form, $entity) {
    $varinfo = $entity->varid ? dh_vardef_info($entity->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    $mname = $this->handleFormPropname($entity->propname);
    $form[$mname] = array(
      '#title' => isset($entity->title) ? t($entity->title) : t($entity->propname),
      '#type' => 'textfield',
      '#description' => t($entity->vardesc),
      '#default_value' => !empty($entity->propcode) ? $entity->propcode : "0.0",
    );
  }
  
  
  public function exportOpenMIBase($entity) {
    // creates the base properties for this class
    $export = array(
      $entity->propname => array(
        'id' => $entity->pid, 
        'name' => $entity->propname, 
        'object_class' => $this->object_class, 
        'value' => $entity->propvalue, 
      )
    );
    if (property_exists($entity, 'proptext')) {
      $export[$entity->propname]['description'] = $entity->proptext['und'][0]['value'];
    }
    return $export;
  }
}

class dHOMEquation extends dHOMSubComp {
  var $object_class = 'Equation';
  var $json2d = TRUE; // use JSON 2d for all remote syncs, much faster
  
  public function getDefaults($entity, &$defaults = array()) {
    $defaults = parent::getDefaults($entity, $defaults);
    $defaults += array(
      'defaultval' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'defaultval',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Default Value.',
        'title' => 'Default Value',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'nonnegative' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0,
        'datatype' => 'boolean',
        'propname' => 'nonnegative',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'title' => 'Non-Negative?',
        'vardesc' => 'Select TRUE to cause any negative result to be returned as default minimum value.',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'minvalue' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'minvalue',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Value to use if variable is type Non-Negative.',
        'title' => 'Non-Negative Default Value',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'engine' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => 'mathProcessor2',
        'propname' => 'engine',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Parser.',
        'varname' => 'Which Equation Parser to Use?',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
    );
    return $defaults;
  }
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    //dpm($form,"equation before 1st customization");
    $form['propcode']['#title'] = '';
    $form['propcode']['#prefix'] = ' = ';
    $engines = array(
      'mathProcessor2' => "Default (mathProcessor2)",
      'mathProcessor3' => "mathProcessor3",
    );
    $form['engine']['#type'] = 'select';
    $form['engine']['#options'] = $engines;
    $form['engine']['#size'] = 1;
  }
  
  public function attachNamedForm(&$form, $entity) {
    $varinfo = $entity->varid ? dh_vardef_info($entity->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    $mname = $this->handleFormPropname($entity->propname);
    $form[$mname] = array(
      '#title' => isset($entity->title) ? t($entity->title) : t($entity->propname),
      '#type' => 'textfield',
      '#description' => t($entity->vardesc),
      '#default_value' => !empty($entity->propcode) ? $entity->propcode : "0.0",
    );
  }
  
  public function setAllRemoteProperties($entity, $elid, $path) {
    // this replaces parent method in favor of full object json transfer
    // @todo: make this work for all at base class 
    //     JSON enabled objects must have:
    //     - var $json2d = TRUE; // for all plugins in Drupal/om module
    //     - var $json2d = TRUE; // on OM class 
    //     - The attribute types must be supported in function setPropJSON2d($p
    //       in the base OM class, or in the subclass 
    //       Current supported types: normal attributes, array, textField
    // Old Code:
    /*
    parent::setAllRemoteProperties($entity, $elid, $path);
    array_unshift($path, 'equation');
    */
    $ppath = $path;
    array_unshift($ppath, $entity->propname);
    //$this->setRemoteProp($entity, $elid, $ppath, "", $this->object_class);
    $exp = $this->exportOpenMI($entity);
    //dpm($exp,"Using JSON export mode");
    $exp_json = addslashes(json_encode($exp[$entity->propname]));
    $this->setRemoteProp($entity, $elid, $ppath, $exp_json, $this->object_class, 'json-2d');
  }
  
  public function applyEntityAttribute(&$property, $value) {
    $property->propcode = $value;
  }
  /*
  public function attachNamedForm(&$form, $entity) {
    $varinfo = $entity->varid ? dh_vardef_info($entity->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    // create a blank to house the original form info
    $pform = array();
    $this->formRowEdit($pform, $entity);
    // harvest pieces I want to keep
    $mname = $this->handleFormPropname($entity->propname);
    $form[$mname] = $pform['propcode'];
  }
  */
  
  public function exportOpenMIBase($entity) {
    // creates the base properties for this class
    $export = array(
      $entity->propname => array(
        'id' => $entity->pid, 
        'name' => $entity->propname, 
        'default' => $entity->propvalue,
        'equation' => array(
          'name' => 'equation',
          'value' => $entity->propcode,
        ),          
      )
    );
    return $export;
  }
}

class dHOMStatistic extends dHOMSubComp {
  var $object_class = 'Statistic';
  var $default_bundle = 'dh_properties';
  var $json2d = TRUE;
  
  public function hiddenFields() {
    return array('pid', 'propcode', 'startdate', 'enddate', 'varid', 'featureid', 'entity_type', 'bundle','dh_link_admin_pr_condition');
  }
  
  public function getDefaults($entity, &$defaults = array()) {
    $defaults = parent::getDefaults($entity, $defaults);
    $defaults += array(
      'operands' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'operands',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Operands to evaluate with this statistic.',
        'title' => 'Operands',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'statname' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => 'mean',
        'propname' => 'statname',
        'vardesc' => 'Statistic to calculate.',
        'title' => 'Stat',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
    );
    return $defaults;
  }
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    //dpm($form,'form');
    // now, format the lookup type fields 
    $form['propvalue']['#title'] = 'Default Value';
    $statnames = array(
      'min' => "Min",
      'max' => "Max",
      'mean' => "Mean",
      'median' => "Median",
      'stddev' => "Std. Dev.",
      'pow' => "Power(x ^ y)",
      'log' => "ln",
      'log10' => "log base 10",
      'stack' => "Stack"
    );
    $form['statname']['#type'] = 'select';
    $form['statname']['#options'] = $statnames;
    $form['statname']['#size'] = 1;
    $form['statname']["#empty_value"] = "";
    // column lookup 
  }
  public function attachNamedForm(&$form, $entity) {
    $varinfo = $entity->varid ? dh_vardef_info($entity->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    // create a blank to house the original form info
    $pform = array();
    $this->formRowEdit($pform, $entity);
    // harvest pieces I want to keep
    $mname = $this->handleFormPropname($entity->propname);
    $form[$mname] = $pform['propcode'];
    $form[$mname]['#title'] = isset($entity->title) ? t($entity->title) : t($entity->propname);
    $form[$mname]['#description'] = t($entity->vardesc);
  }
 
}

//class dHOMAlphanumericConstant extends dHVariablePluginDefault {
class dHOMAlphanumericConstant extends dHOMBaseObjectClass {
  var $object_class = FALSE;
  
  public function hiddenFields() {
    return array('varname', 'startdate', 'enddate','featureid','entity_type', 'propname','propvalue','dh_link_admin_pr_condition');
  }
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    if (!$entity->varid) {
      return FALSE;
    }
    $form['propcode'] = array(
      '#title' => t($entity->propname),
      '#type' => 'textfield',
      '#description' => $entity->vardesc,
      '#default_value' => !empty($entity->propcode) ? $entity->propcode : "",
    );
  }
  public function attachNamedForm(&$form, $entity) {
    $varinfo = $entity->varid ? dh_vardef_info($entity->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    // create a blank to house the original form info
    $pform = array();
    $this->formRowEdit($pform, $entity);
    // harvest pieces I want to keep
    $mname = $this->handleFormPropname($entity->propname);
    $form[$mname] = $pform['propcode'];
    $form[$mname]['#title'] = isset($entity->title) ? t($entity->title) : t($entity->propname);
    $form[$mname]['#description'] = t($entity->vardesc);
  }
  
  public function applyEntityAttribute(&$property, $value) {
    $property->propcode = $value;
  }
  
  public function getPropertyAttribute($property) {
    return $property->propcode;
  }
  
  public function buildContent(&$content, &$entity, $view_mode) {
    // @todo: handle teaser mode and full mode with plugin support
    foreach ($this->hiddenFields() as $hide) {
      unset($content[$hide]);
    }
    $modate = date("Y-m-d h:m a", dh_handletimestamp($entity->modified) );
    switch ($view_mode) {
      case 'plugin':
      case 'teaser':
      default:
        $content['propname'] = array(
          '#type' => 'item',
          '#markup' => "<b>Name:</b> $entity->propname<sub>($entity->varname)</sub>  <br>(mod: $modate)"
        );
        $content['propcode'] = array(
          '#type' => 'item',
          '#markup' => "<b>Code:</b> " . $entity->propcode,
        );
      break;
    }
  }
  
  public function setAllRemoteProperties($entity, $elid, $path) {
    parent::setAllRemoteProperties($entity, $elid, $path);
    // this sets only the variable on the base object
    //array_shift($path);
    $this->setRemoteProp($entity, $elid, $path, $entity->propcode, $this->object_class);
  }
  
  public function getPublicProcs($entity) {
    $procnames = parent::getPublicProcs($entity);
    $parent = $this->getParentEntity($entity);
    $plugin = dh_variables_getPlugins($parent);
    if ($plugin) {
    //dpm($plugin,'plugin');
      if (method_exists($plugin, 'getPublicProcs')) {
        $pprocs = $plugin->getPublicProcs($parent);
      }
      $procnames = array_merge($pprocs, $procnames);
    }
    //dpm($procnames, 'procs' . get_class($this));
    return $procnames;
  }
  
  public function getPublicVars($entity, &$publix = array()) {
    // @todo: if this works, move to the dHOMBaseObjectClass class
    parent::getPublicVars($entity, $publix);
    $parent = $this->getParentEntity($entity);
    $plugin = dh_variables_getPlugins($parent);
    if ($plugin) {
    //dpm($plugin,'plugin');
      if (method_exists($plugin, 'getPublicVars')) {
        $plugin->getPublicVars($parent, $publix);
      }
    }
    return $publix;
  }
  
  public function exportOpenMIBase($entity) {
    // creates the base properties for this class
    $export = array(
      $entity->propname => array(
        'id' => $entity->pid, 
        'name' => $entity->propname, 
        'value' => $entity->propcode, 
      )
    );
    return $export;
  }
}


class dHOMConstant extends dHOMBaseObjectClass {
  // changed inheritance to support remote OM editing.
//class dHOMConstant extends dHVariablePluginNumericAttribute {
  // numeric constant 
  // this can be a stand-alone property, with it's own save() method unlike
  //   unlike the alphanumeric constants that are just embedded in the object edit form and 
  //   do not have their own save methods.
  //   This will be seldom used, as virtually all setting fields will be attached to something (like run_mode)
  // But WILL be used for object class attributes in OM (like area, slope, etc.)
  var $object_class = FALSE;
  var $default_value = 0;
  var $pct_range = array('<5', 10, 25, 50, 75, 90, 100); // @todo - allow default can be over-ridden in getDefaults() code
  
  public function hiddenFields() {
    $hidden = array_merge(array('propcode', 'startdate', 'enddate'), parent::hiddenFields());
    return $hidden;
  }
  
  public function setAllRemoteProperties($entity, $elid, $path) {
    parent::setAllRemoteProperties($entity, $elid, $path);
    // this sets only the variable on the base object
    //array_shift($path);
    $this->setRemoteProp($entity, $elid, $path, $entity->propvalue, $this->object_class);
  }
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    if (!$entity->varid) {
      return FALSE;
    }
    $varinfo = $entity->varid ? dh_vardef_info($entity->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    $form['propvalue'] = array(
      '#title' => t($entity->varname),
      '#type' => 'textfield',
      '#description' => $entity->vardesc,
      '#default_value' => $entity->propvalue,
    );
    if (property_exists($entity, 'datatype')) {
      switch ($entity->datatype) {
        case 'percent_select':
        $opts = $this->pct_list($this->pct_range);
        $form['propvalue']['#type'] = 'select';
        $form['propvalue']['#options'] = $opts;
        $form['propvalue']['#empty_option'] = 'n/a';
        break;
        case 'boolean':
        $opts = array(0 => 'False', 1 => 'True');
        $form['propvalue']['#type'] = 'select';
        $form['propvalue']['#options'] = $opts;
        $form['propvalue']['#default_value'] = !empty($entity->propvalue) ? $entity->propvalue : "$this->pct_default";
        break;
      }
    }
  }
  
  public function pct_list($inc = 10) {
    $pcts = array();
    if (is_array($inc)) {
      // we already have our list of percents, just work it out
      foreach ($inc as $i) {
        $dec = floatval(preg_replace('/\D/', '', $i)) / 100.0;
        $pcts["$dec"] = $i . " %";
      }
    } else {
      $i = $inc;
      while ($i <= 100) {
        $dec = floatval($i) / 100.0;
        $pcts["$dec"] = $i . " %";
        $i += $inc;
      }
    }
    return $pcts;
  }
  
  public function applyEntityAttribute(&$property, $value) {
    $property->propvalue = $value;
  }
  
  public function getPropertyAttribute($property) {
    return $property->propvalue;
  }
  
  public function attachNamedForm(&$form, $entity) {
    //dpm($entity,'numeric constant ' . $mname);
    $varinfo = $entity->varid ? dh_vardef_info($entity->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    // create a blank to house the original form info
    $pform = array();
    $this->formRowEdit($pform, $entity);
    // harvest pieces I want to keep
    $mname = $this->handleFormPropname($entity->propname);
    $form[$mname] = $pform['propvalue'];
    $form[$mname]['#title'] = isset($entity->title) ? t($entity->title) : t($entity->propname);
    $form[$mname]['#description'] = t($entity->vardesc);
  }
  
  public function getPublicProcs($entity) {
    $procnames = parent::getPublicProcs($entity);
    $parent = $this->getParentEntity($entity);
    $plugin = dh_variables_getPlugins($parent);
    if ($plugin) {
    //dpm($plugin,'plugin');
      if (method_exists($plugin, 'getPublicProcs')) {
        $pprocs = $plugin->getPublicProcs($parent);
      }
      $procnames = array_merge($pprocs, $procnames);
    }
    return $procnames;
  }
  
  public function exportOpenMIBase($entity) {
    // creates the base properties for this class
    $export = array(
      $entity->propname => array(
        'id' => $entity->pid, 
        'name' => $entity->propname, 
        'value' => $entity->propvalue, 
      )
    );
    return $export;
  }
}

class dHOMtextField extends dHOMSubComp {
  // special subcomp for alpha info
  var $object_class = 'textField';
  var $json2d = TRUE;

  public function hiddenFields() {
    return array('varname', 'startdate', 'enddate','featureid','entity_type', 'propvalue','dh_link_admin_pr_condition');
  }
  
  public function getDefaults($entity, &$defaults = array()) {
    // getDefaults is required to be compatible with om.migrate.element.php
    // could add a check for that to not call getDefaults, but for now, just put it here
    return $defaults;
  }
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    if (!$entity->varid) {
      return FALSE;
    }
    $form['propname'] = array(
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#description' => $entity->vardesc,
      '#default_value' => !empty($entity->propname) ? $entity->propname : "",
    );
    $form['propcode'] = array(
      '#title' => t($entity->varname),
      '#type' => 'textfield',
      '#description' => 'Value for this text variable',
      '#default_value' => !empty($entity->propcode) ? $entity->propcode : "",
    );
  }
  public function attachNamedForm(&$form, $entity) {
    $varinfo = $entity->varid ? dh_vardef_info($entity->varid) : FALSE;
    if (!$varinfo) {
      return FALSE;
    }
    // create a blank to house the original form info
    $pform = array();
    $this->formRowEdit($pform, $entity);
    // harvest pieces I want to keep
    $mname = $this->handleFormPropname($entity->propname);
    $form[$mname] = $pform['propcode'];
    $form[$mname]['#title'] = isset($entity->title) ? t($entity->title) : t($entity->propname);
    $form[$mname]['#description'] = t($entity->vardesc);
  }
  
  public function applyEntityAttribute(&$property, $value) {
    $property->propcode = $value;
  }
  
  public function getPropertyAttribute($property) {
    return $property->propcode;
  }
  
  public function exportOpenMIBase($entity) {
    // creates the base properties for this class
    $export = array(
      $entity->propname => array(
        'id' => $entity->pid, 
        'name' => $entity->propname, 
        'object_class' => $this->object_class, 
        'value' => $entity->propcode, 
      )
    );
    return $export;
  }
}

class dHOMObjectClass extends dHVariablePluginCodeAttribute {
  
}

class dHOMPublicVars extends dHOMAlphanumericConstant {
  var $object_class = 'textField';
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    if (!$entity->varid) {
      return FALSE;
    }
    $public_vars = $this->getPublicVars($entity);
    //dpm($public_vars,'public vars');
    $form['propcode'] = array(
      '#title' => t($entity->propname),
      '#type' => 'select',
      '#empty_option' => t('- Select -'),
      '#options' => array_combine($public_vars, $public_vars),
      '#description' => $entity->vardesc,
      '#default_value' => !empty($entity->propcode) ? $entity->propcode : "",
    );
  }
}

class dHOM_ModelScenario extends dHVariablePluginDefault {
  var $object_class = FALSE;
  public function hiddenFields() {
    return array('pid', 'varid', 'featureid', 'entity_type', 'bundle','dh_link_admin_pr_condition');
  }
  // @todo: Scenario should accomodate sub-comps that detail analyses to be performed on parent object 
  //  Things from the scen_model_run_elements table 
  //    - runid
  //    - runfile
  //    - host
  //    - output_file
  //    - fullpath
  //    - remote_path
  //    - remote_url
  //    - rundate
  //    - starttime
  //    - endtime
  //    - elementid
  //  Watershed model specific items
  //    - flow_mode
  //    - run_mode 
  // @todo: Automatically run analyses comps defined on element 
  //    - dHOM_ModelScenario elements look to parent to find comps of type dHOM_Analysis to run when updating scen record
  
  public function getRunData() {
    // returns run CSV
  }
  public function exportOpenMIBase($entity) {
    // creates the base properties for this class
    $export = array(
      $entity->propname => array(
        'id' => $entity->pid, 
        'name' => $entity->propname, 
        'startdate' => $entity->startdate, 
        'enddate' => $entity->enddate, 
        'modified' => $entity->modified, 
        'value' => $entity->propcode, 
      )
    );
    return $export;
  }
}

class dHOM_ModelRun extends dHVariablePluginDefault {
  // this is a timeseries record of a model run.
  var $object_class = FALSE;
  public function hiddenFields() {
    return array('pid', 'varid', 'featureid', 'entity_type', 'bundle','dh_link_admin_pr_condition');
  }
  // @todo: Scenario should accomodate sub-comps that detail analyses to be performed on parent object 
  //  Things from the scen_model_run_elements table 
  //    - runid
  //    - runfile
  //    - host
  //    - output_file
  //    - fullpath
  //    - remote_path
  //    - remote_url
  //    - rundate
  //    - starttime
  //    - endtime
  //    - elementid
  //  Watershed model specific items
  //    - flow_mode
  //    - run_mode 
  // @todo: Automatically run analyses comps defined on element 
  //    - dHOM_ModelScenario elements look to parent to find comps of type dHOM_Analysis to run when updating scen record
  
  public function getRunData() {
    // returns run CSV
  }
  public function exportOpenMIBase($entity) {
    // creates the base properties for this class
    $export = array(
      $entity->propname => array(
        'id' => $entity->tid, 
        'name' => $entity->tscode, 
        'startdate' => $entity->tstime, 
        'enddate' => $entity->tsendtime, 
        'modified' => $entity->modified, 
        'value' => $entity->tsvalue, 
      )
    );
    return $export;
  }
}

class dHOM_Analysis extends dHVariablePluginDefault {
  var $object_class = FALSE;
  public function hiddenFields() {
    return array('pid', 'varid', 'featureid', 'entity_type', 'bundle','dh_link_admin_pr_condition');
  }
  // @todo: Model elements can have multiple dHOM_Analysis components to detail default analyses to perform after run 
  //    - dHOM_ModelScenario elements look to parent to find comps of type dHOM_Analysis to run when updating scen record
  //    - target_element (defaults to first object in containment tree with method getRunData )
  //    - script_path: if not FALSE, this points to a script file 
  //    - script_type: FALSE or R, python, php, bash 
  //    - 
}

class dHOM_ModelVersion extends dHVariablePluginDefault {
  var $object_class = FALSE;
  public function hiddenFields() {
    return array('pid', 'propcode', 'startdate', 'enddate', 'varid', 'featureid', 'entity_type', 'bundle','dh_link_admin_pr_condition');
  }
}

class dHOMDataMatrix extends dHOMSubComp {
  var $object_class = 'dataMatrix';
  var $default_bundle = 'om_data_matrix';
  var $matrix_field = 'field_dh_matrix';
  var $json2d = TRUE; // use JSON 2d for all remote syncs, much faster
  
  public function hiddenFields() {
    return array('pid', 'propcode', 'startdate', 'enddate', 'varid', 'featureid', 'entity_type', 'bundle','dh_link_admin_pr_condition');
  }
  
  public function entityDefaults(&$entity) {
    //dpm($entity,'entity');
    // special render handlers when displaying in a grouped property block
    $entity->bundle = $this->default_bundle;
    $datatable = $this->tableDefault($entity);
    $this->setCSVTableField($entity, $datatable);
    //dpm($entity, 'entityDefaults');
  }
  
  public function getDefaults($entity, &$defaults = array()) {
    $defaults = parent::getDefaults($entity, $defaults);
    $defaults += array(
      'keycol1' => array(
        'embed' => TRUE,
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'keycol1',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Variable to use for row lookup if 1-d or 2-d lookup type.',
        'title' => 'Row Key',
        'varid' => dh_varkey2varid('om_class_PublicVars', TRUE),
      ),
      'keycol2' => array(
        'embed' => TRUE,
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'keycol2',
        'vardesc' => 'Variable to use for column lookup if 2-d lookup type.',
        'title' => 'Column Key',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        //'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
        'varid' => dh_varkey2varid('om_class_PublicVars', TRUE),
      ),
      'valuetype' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'embed' => TRUE,
        'propvalue_default' => 1,
        'propname' => 'valuetype',
        'vardesc' => 'Value Type.',
        'title' => 'Return Value Type',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'eval_type' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => 'auto',
        'propvalue_default' => 1,
        'propname' => 'eval_type',
        'vardesc' => 'How to evaluate each cell in the matrix (type reference looks for variable in state array, type auto uses old method which guesses based on contents at each timestep).',
        'title' => 'Cell Eval Type',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'value_dbcolumntype' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => 'auto', // auto is the old method and we need to maintain consistency
        'propname' => 'value_dbcolumntype',
        'vardesc' => 'How to store data in the runtime log table.',
        'title' => 'DB Value Type',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'lutype1' => array(
        'embed' => TRUE,
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'lutype1',
        'vardesc' => 'Row Lookup Type.',
        'title' => 'Row Lookup Type',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'lutype2' => array(
        'embed' => TRUE,
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'lutype2',
        'vardesc' => 'Column Lookup Type (2-dimensional lookups).',
        'title' => 'Column Lookup Type',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'defaultval' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0.0,
        'propname' => 'defaultval',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Default value.',
        'title' => 'Default Value',
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
      'autosetvars' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propvalue_default' => 0,
        'propname' => 'autosetvars',
        'datatype' => 'boolean',
        'vardesc' => 'Create Column Vars on parent in form: [propname]_[colname] (0 or 1).',
        'title' => 'Auto-Set Parent Vars',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'varid' => dh_varkey2varid('om_class_Constant', TRUE),
      ),
    );
    return $defaults;
  }
  
  public function load(&$entity) {
    // get field default basics
    //dpm($entity, 'load()');
    parent::load($entity);
    if ($entity->is_new or $entity->reset_defaults) {
      $datatable = $this->tableDefault($entity);
      $this->setCSVTableField($entity, $datatable);
    }
  }
  
  
  // this class has a name, and a description, an exec_hierarchy and other atributes
  // @todo: add basic handling of things other than descriptions
  
  public function setAllRemoteProperties($entity, $elid, $path) {
    // this replaces parent method in favor of full object json transfer
    // @todo: make this work for all at base class 
    // Old Code:
    /*
    parent::setAllRemoteProperties($entity, $elid, $path);
    array_unshift($path, 'equation');
    */
    //$this->loadProperties($entity, FALSE);
    $ppath = $path;
    array_unshift($ppath, $entity->propname);
    if ($entity->valuetype->propvalue === NULL){
      $om_matrix = $this->tablefieldToOMMatrix($entity->field_dh_matrix);
      $rows = $om_matrix['rows'];
      $cols = $om_matrix['cols'];
      // Guess if needed 0 - array (normal), 1 - 1-col lookup, 2 - 2-col lookup
      $entity->valuetype->propvalue = ($cols > 2) ? 2 : 1; 
    }
    //$this->setRemoteProp($entity, $elid, $ppath, "", $this->object_class);
    $exp = $this->exportOpenMI($entity);
    // rewrite matrix as 1-d list because OM setProp import breaks otherwise
    $om_matrix = $this->tablefieldToOMMatrix($entity->field_dh_matrix);
    //dpm($exp,"Using JSON export mode");
    $exp_json = addslashes(json_encode($exp[$entity->propname]));
    $this->setRemoteProp($entity, $elid, $ppath, $exp_json, $this->object_class, 'json-2d');
  }
  
  public function setAllRemotePropertiesOld($entity, $elid, $path) {
    parent::setAllRemoteProperties($entity, $elid, $path);
    // @todo: move this to the base class if it checks out as OK
    //dpm($entity, 'before loadProp()');
    //$this->loadProperties($entity, FALSE);
    //dpm($path, 'original path to setAllRemoteProperties()');
    //dpm($entity, 'subcomp entity to setAllRemoteProperties()');
    
    // create Separate handlers for this if it is a component or sub-subcomponent
    // If sub-component omit the object_class since they should fail if they do not exist, rather than adding
    if (property_exists($entity, 'field_dh_matrix')) {
      $cols = $entity->field_dh_matrix['und'][0]['tablefield']['rebuild']['count_cols'];
      $om_matrix = $this->tablefieldToOMMatrix($entity->field_dh_matrix);
      $rows = $om_matrix['rows'];
      $cols = $om_matrix['cols'];
      // set rows
      $spath = $path;
      array_unshift($spath, 'numrows');
      $this->setRemoteProp($entity, $elid, $spath, $rows, $this->object_class, '');
      // set value type
      $spath = $path;
      array_unshift($spath, 'valuetype');
      $valuetype = ($cols > 2) ? 2 : 1; // 0 - array (normal), 1 - 1-col lookup, 2 - 2-col lookup
      $this->setRemoteProp($entity, $elid, $spath, $valuetype, $this->object_class, '');
       
      // set table matrix data
      $spath = $path;
      array_unshift($spath, 'matrix');
      $formatted = $om_matrix['array-1d'];
      $scsv = addslashes(json_encode($formatted));
      $this->setRemoteProp($entity, $elid, $spath, $scsv, $this->object_class, 'json-1d');
      $debug_json = json_decode(stripslashes($scsv), TRUE);
    }
  }
  
  public function tableDefault($entity) {
    // Returns associative array keyed table (like is used in OM)
    // This format is not used by Drupal however, so a translation 
    //   with tablefield_parse_assoc() is usually in order (such as is done in load)
    //dpm($entity,'setting table default');
    // @todo: why doesn't this get called when we set this default in an attached getDefaults() property array?
    if (isset($entity->field_dh_matrix_default)) {
      $table = $entity->field_dh_matrix_default;
    } else {
      $table = array();
      $table[] = array('col1', 'col2', 'col3');
      $table[] = array('','','');
    }
    return $table;
  }
  
  public function getMatrixField($entity) {
    $tablefield = om_tablefield_tablefield($entity->{$this->matrix_field});
    return $tablefield;
  }
  
  public function getMatrixFieldTable($entity) {
    $tablefield = $this->getMatrixField($entity);
    $tabledata = $tablefield['tabledata'];
    return $tabledata;
  }
  
  public function tablefieldToOMMatrix($field) {
    // translate tablefield to 1-D array, with values from rows left to right, top to bottom as 
    // comes from an HTML form submission in OM
    $ttrans = array('rows' => 0, 'cols' => $cols, 'array-1d' => array());
    // This relies upon finding the column and row counts in this location
    //  This is ONLY true after a tablefield is saved, which is nutso, so we insert a layer of code to handle this.
    $tablefield = om_tablefield_tablefield($field);
    $ttrans['cols'] = $tablefield['rebuild']['count_cols'];
    $ttrans['rows'] = $tablefield['rebuild']['count_rows'];
    $trat = $tablefield['tabledata'];
    //dpm($trat,'data');
    $rowkey = 0;
    foreach ($trat as $rowix => $rowvals) {
      $c = 0;
      foreach ($rowvals as $ix => $val) {
        $ttrans['array-1d'][] = $val;
        $c++;
        if ($c >= $ttrans['cols']) {
          break;
        }
      }
      $rowkey++;
    }
    return $ttrans;
  }
  
  function getCSVTableField(&$entity) {
    $tabledata = $this->getMatrixFieldTable($entity);
    $csv = array();
    foreach ($tabledata as $rowix => $rowvals) {
      unset($rowvals['weight']);
      $csv[] = array_values($rowvals);
    }
    return $csv;
  }
  
  function setCSVTableField(&$entity, $csvtable) {
    // requires a table to be set in non-associative format (essentially a csv)
    $instance = field_info_instance($entity->entityType(), $this->matrix_field, $entity->bundle);
    $field = field_info_field($this->matrix_field);
    $default = field_get_default_value($entity->entityType(), $entity, $field, $instance);
    //dpm($default,'default');
    list($imported_tablefield, $row_count, $max_col_count) = dh_tablefield_parse_array($csvtable);
    // set some default basics
    $default[0]['tablefield']['tabledata'] = $imported_tablefield;
    $default[0]['tablefield']['rebuild']['count_cols'] = $max_col_count;
    $default[0]['tablefield']['rebuild']['count_rows'] = $row_count;
    if (function_exists('tablefield_serialize')) {
      $default[0]['value'] = tablefield_serialize($field, $default[0]['tablefield']);
    } else {
      $default[0]['value'] = serialize($default[0]['tablefield']);
    }
    $default[0]['format'] = !isset($default[0]['format']) ? NULL : $default[0]['format'];
    $entity->{$this->matrix_field} = array(
      'und' => $default
    );
  }
  
  public function formRowEdit(&$form, $entity) {
    parent::formRowEdit($form, $entity);
    //dpm($entity,'entity');
    //dpm($form,'form');
    // now, format the lookup type fields 
    $eval_types = array(
      "auto",
      "numeric",
      "string",
      "reference",
    );
    $eval_options = array_combine($eval_types, $eval_types);
    // value_dbcolumntype = how this is stored in the runtime database
    // eval_type = how this is evaulated: variable, numeric, equation, string 
    $form['eval_type']['#type'] = 'select';
    $form['eval_type']['#options'] = $eval_options;
    $form['eval_type']['#size'] = 1;
    $form['eval_type']["#empty_value"] = "";
    $form['eval_type']["#empty_option"] = "Not Set";
    $form['eval_type']["#description"] = "How to evaluate the variables. For type 'equation' the variable will be evaluated as an equation each time-step, then stored as type numeric in the database.";
    $dbtypes = array(
      "auto",
      "float8",
      "numeric",
      "varchar(32)",
      "varchar(64)",
      "varchar(128)",
      "json",
    );
    $db_options = array_combine($dbtypes, $dbtypes);
    $db_options['json'] .= " (" . t("not yet enabled") . ")";
    // value_dbcolumntype = how this is stored in the runtime database
    // evaltype = how this is evaulated: variable, numeric, equation, string 
    $form['value_dbcolumntype']['#type'] = 'select';
    $form['value_dbcolumntype']['#options'] = $db_options;
    $form['value_dbcolumntype']['#size'] = 1;
    $form['value_dbcolumntype']["#empty_value"] = "";
    $form['value_dbcolumntype']["#empty_option"] = "Not Set";
    $form['value_dbcolumntype']["#description"] = "How to store the variable in the runtime db.";
    $lutypes = array(
      0 => "Exact Match",
      1 => "Interpolated",
      2 => "Stair Step",
      3 => "Key Interpolate"
    );
    $form['lutype1']['#type'] = 'select';
    $form['lutype1']['#options'] = $lutypes;
    $form['lutype1']['#size'] = 1;
    $form['lutype1']["#empty_value"] = "";
    $form['lutype1']["#empty_option"] = "Not Set";
    $form['lutype1']["#description"] = "How to handle matching.  If this is 'Not Set' unexpected behavior may occur.";
    // column lookup 
    $form['lutype2']['#type'] = 'select';
    $form['lutype2']['#options'] = $lutypes;
    $form['lutype2']['#size'] = 1;
    $form['lutype2']["#empty_value"] = "";
    $form['lutype2']["#empty_option"] = "Not Set";
    $form['lutype2']["#description"] = "How to handle matching.  If this is 'Not Set' unexpected behavior may occur.";
    $valuetypes = array(
      0 => "Return Whole Array",
      1 => "1-D lookup",
      2 => "2-D Lookup",
      3 => "CSV"
    );
    $form['valuetype']['#type'] = 'select';
    $form['valuetype']['#options'] = $valuetypes;
    $form['valuetype']['#size'] = 1;
    $form['valuetype']["#empty_value"] = "";
    $form['valuetype']["#empty_option"] = "Not Set";
    $form['valuetype']["#description"] = "Note: only types 1-D and 2-D are fully supported.";
    if ($entity->valuetype->propvalue === NULL){
      $om_matrix = $this->tablefieldToOMMatrix($entity->field_dh_matrix);
      $rows = $om_matrix['rows'];
      $cols = $om_matrix['cols'];
      // Guess if needed 0 - array (normal), 1 - 1-col lookup, 2 - 2-col lookup
      $form['valuetype']["#default_value"] = ($cols > 2) ? 2 : 1; 
    }
  }
  
  public function exportOpenMIBase($entity) {
    // creates the base properties for this class
    $export = parent::exportOpenMIBase($entity);
    $export[$entity->propname]['matrix'] = array(
      'name' => 'matrix',
      'object_class' => 'array',
      'value' => $this->getCSVTableField($entity)
    );
    unset($export[$entity->propname]['code']);
    return $export;
  }
 
}

class dHOM_USGSGageObject extends dHOMModelElement {
  var $object_class = 'USGSGageObject';
}

class dHOM_USGSGageSubComp extends dHOMSubComp {
  var $object_class = 'USGSGageSubComp';
}

class dHOMLinkage extends dHOMBaseObjectClass {
  var $object_class = FALSE;
  var $attach_method = 'contained';
  
  public function getDefaults($entity, &$defaults = array()) {
    $defaults = parent::getDefaults($entity, $defaults);
    $defaults += array(
      'link_type' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'link_type',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => '1: parent-child link, 2: local property link, 3: remote object property link (not direct parent or child).',
        'title' => 'Link Type',
        '#weight' => 1,
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'src_prop' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'src_prop',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Source Entity Property Name.',
        'title' => 'Source Prop',
        '#weight' => 4,
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
      ),
      'dest_entity_type' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'dest_entity_type',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Destination Entity Type.',
        'title' => 'Destination Entity Type',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
        '#weight' => 5,
      ),
      'dest_entity_id' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'dest_entity_id',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Destination Entity Unique Identifier, if blank, assumed to be this properties parent.',
        'title' => 'Destination Entity ID',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
        '#weight' => 6,
      ),
      'dest_prop' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'dest_prop',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Destination Entity Property Name.',
        'title' => 'Destination Prop',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
        '#weight' => 7,
      ),
      'src_location' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'src_location',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'How to obtain link: null/localhost is default handled by local system. Other values: JSONAPI, RESTapi, and OMapi.',
        'title' => 'Source Location',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
        '#weight' => 8,
      ),
      'src_uri' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'src_uri',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Null/localhost is for local, rest, json or OM path to getProp.php and setProp.php.',
        'title' => 'Source URI',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
        '#weight' => 9,
      ),
      'update_setting' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'update_setting',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Valid settings: create (first time only), update, step, all. Only step objects are executed during model simulations.',
        'title' => 'Update Setting',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
        '#weight' => 10,
      ),
      'delete_setting' => array(
        'entity_type' => $entity->entityType(),
        'propcode_default' => NULL,
        'propname' => 'delete_setting',
        'singularity' => 'name_singular',
        'featureid' => $entity->identifier(),
        'vardesc' => 'Valid settings: delete, confirm_delete, none (or empty).',
        'title' => 'Update Setting',
        'varid' => dh_varkey2varid('om_class_AlphanumericConstant', TRUE),
        '#weight' => 10,
      ),
    );
    return $defaults;
  }
  
  public function fix_bigint(&$entity) {
    // @todo: move to base dh class 
    if ($entity->pid > 0) {
      $result = db_query("select propvalue from dh_properties where pid = $entity->pid");
      $propvalue = $result->fetchField();
      $entity->propvalue = $propvalue;
    }
    //dpm("Fix int $entity->propvalue");
  }
  
  
  public function formRowEdit(&$rowform, $entity) {
    $this->fix_bigint($entity);
    parent::formRowEdit($rowform, $entity);
    // @todo:
    // - Link Type Select List
    // - Entity + Property Browser w/
    //   - a select list of local parent & child entities directly connected.
    //   - Visible if link type is local property (parent or child) or parent:child container link
    // - Remote entity search
    //   - user enters entity_type, name, location (default localhost), and/or elementid 
    //   - uses local search facility or REST/JSONAPI if remote 
    $rowform['propcode']['#title'] = 'Source Entity Type';
    $rowform['propcode']['#weight'] = 2;
    $rowform['propvalue']['#title'] = 'Source Entity ID';
    $rowform['propvalue']['#weight'] = 3;
    $rowform['propvalue']['#default_value'] = $entity->propvalue;
    //dpm($rowform['propvalue'],'pv');
  }
  
  public function updateProperties(&$entity) {
    parent::updateProperties($entity);
    // looks at link info,
    // if this is a remote or local property link
    // and if update_setting == 'update' or 'all' 
    // retrieve the linked data.
    //dpm($entity,'save model linkage');
    $src_location = empty($entity->src_location->propcode) ? 'localhost' : $entity->src_location->propcode;
    $update_setting = empty($entity->update_setting->propcode) ? 'none' : $entity->update_setting->propcode;
    switch ($update_setting) {
      case 'all':
      case 'update':
      // type 1 is a parent child, so no updates to make.
      if (in_array($entity->link_type->propcode, array(2,3))) {
        switch ($src_location) {
          case 'localhost':
            $linked_value = $this->getLocalhostLinkedValue($entity);
            //dsm("Found $linked_value ");
            $this->setLocalhostLinkedValue($entity, $linked_value);
          break;
          // @todo: handle other types besides localhost
        }
      }
      break;
      case 'step':
      // this is a model runtime linkage.  If this model element has an om_element_connection.
      // Nothing is done here, see this classes method setAllRemoteProperties()
      break;
    }
  }
  
  function setLocalhostLinkedValue(&$entity, $linked_value) {
    //dpm($entity, 'setLocalhostLinkedValue');
    $this->fix_bigint($entity);
    $dest_prop = $entity->dest_prop->propcode;
    // @todo: we don't yet use the dest_entity_type, or dest_entity_id since 
    //        we assumed this is attached to a parent property to set value 
    //        but we may later allow this
    $dest_entity = $this->getDestEntity($entity);
    if (is_object($dest_entity)) {
      if (property_exists($dest_entity, $dest_prop)) {
        $dest_entity->{$dest_prop} = $linked_value;
        $dest_entity->save();
        //dpm($dest_entity, "Saved dest entity");
      }
    }
  }
  
  function getSourceEntity(&$entity) {
    //dpm($entity, 'getSourceEntity'); 
    $entity->src_entity_type = $entity->propcode;
    $entity->src_entity_id = $entity->propvalue;
    if (!empty($entity->src_entity_type) and !empty($entity->src_entity_id)) {
      $entity->src_entity = entity_load_single($entity->src_entity_type, $entity->src_entity_id);
      return $entity->src_entity;
    } 
    return FALSE;
  }
  
  function getDestEntity(&$entity) {
    //dpm($entity, 'getDestEntity');
    if (!is_object($entity->dest_entity)) {
      $dest_entity_type = $entity->dest_entity_type->propcode;
      $dest_entity_id = $entity->dest_entity_id->propcode;
      if ( empty($dest_entity_id) or empty($dest_entity_type)) {
        $entity->dest_entity = $this->getParentEntity($entity);
      } else {
        $entity->dest_entity = entity_load_single($dest_entity_type, $dest_entity_id);
      }
    }
    return $entity->dest_entity;
  }
  
  function getLocalhostLinkedValue(&$entity) {
    //dpm($entity,'getLocalhostLinkedValue entity');
    //dpm($entity->src_entity,'getLocalhostLinkedValue src_entity');
    if (!$entity->src_entity) {
      $this->getSourceEntity($entity);
    }
    if (is_object($entity->src_entity)) {
      // check if prop already exists, if so, just grab it,
      // otherwise, try to load a dh_property with the target name 
      if (!empty($entity->src_prop->propcode)) {
        $src_prop = $entity->src_prop->propcode;
        if (property_exists($entity->src_entity, $src_prop)) {
          $linked_value = $entity->src_entity->{$src_prop};
        } else {
          $conds = array();
          $conds[] = array(
            'name' => 'propname',
            'value' => $src_prop
          );
          $loaded = $entity->src_entity->loadComponents($conds);
          //dpm($entity->src_entity,'source entity');
          if (count($loaded) > 0) {
            $loname = strtolower($src_prop);
            $src_object = $entity->src_entity->dh_properties[$loname];
            // @todo: support linking propcode or other values on dh_properties
            $linked_value = $src_object->propvalue;
          } else {
            watchdog('om', "OMLinkage could not find src_prop " . $src_prop);
          }
        }
      } else {
        watchdog('om', "Missing src_prop on OMLinkage config.");
      }
    }
    return $linked_value;
  }
  
  public function setAllRemoteProperties($entity, $elid, $path) {
    // @todo: this is a special entity that lives in its own table in 
    //        the om 1.0 system.  This should simply utilize the 
    //        w_linkElements script plumbing 
    // For now, we just return.
    $link_type = $entity->link_type->propcode;
    $update_setting = empty($entity->update_setting->propcode) ? 'none' : $entity->update_setting->propcode;
    if (in_array($link_type, array(2,3))) {
      switch ($update_setting) {
        case 'step':
        // this is a model runtime linkage.  If this model element has an om_element_connection 
        // we need to push this to the remote model database.
        // using: om_fn_addObjectLink($srcid, $destid, $srcpropname='', $destpropname='', $linktype = 3)
          
        break;
      }
    }
    return;
    // Copied from Equation class - to be modified.
    //parent::setAllRemoteProperties($entity, $elid, $path);
    //array_unshift($path, 'equation');
    //$this->setRemoteProp($entity, $elid, $path, $entity->propcode, $this->object_class);
  }
  public function findRemoteOMElement($entity, &$path) {
    // do not pass to sub-props as this does not propagate. (yet!)
    return 0;
  }
  
  public function linkTypeLink($entity) {
    // returns a href based on the type of link -- if prop link, return source, if parent:child returns dest 
    if (!$entity->src_entity) {
      $this->getSourceEntity($entity);
    }
    switch ($this->link_type->propvalue) {
      case 1:
      // return dest 
        $href = "om-model-info/" . $entity->dest_entity->identifier() ."/" . $entity->dest_entity->entityType();
      break;
      
      default:
      // return source 
        $href = "om-model-info/" . $entity->src_entity->identifier() ."/" . $entity->src_entity->entityType();
      break;
    }
    return $href;
      
  }
  
  public function buildContent(&$content, &$entity, $view_mode) {
    // @todo: handle teaser mode and full mode with plugin support
    parent::buildContent($content, $entity, $view_mode);
    $this->getSourceEntity($entity);
    //dpm($entity->src_entity,'ent to content');
    switch ($view_mode) {
      case 'plugin':
      case 'teaser':
      default:
        if (is_object($entity->src_entity) and method_exists($entity->src_entity, 'entityType')) {
          // this is a local drupal entity, we can handle it 
          $content['remote'] = array(
            '#type' => 'link',
            '#title' => "From: " . $entity->src_entity->label(),
            '#href' => $this->linkTypeLink($entity),
          );
        }
      break;
    }
  }  
  
  public function delete(&$entity) {
    // cascade delete for certain link types
    $this->loadProperties($entity);
    switch ($entity->link_type->propcode) {      
      case 4:
        $this->delete_replicant($entity);
      break;
    }
    parent::delete($entity);
  }
  public function delete_replicant(&$entity) {
    switch ($entity->delete_setting->propcode) {
      case 'delete':
      $rep = $this->getDestEntity($entity);
      entity_delete('dh_timeseries', $rep->tid);
      break;
    }    
  }
}


// Remaining Objects from who_xmlobject.php
// ***************************
// full objects only
// ***************************
/* 
   model=# select objectclass, count(*) from scen_model_element where scenarioid = 37 group by objectclass;
        objectclass      | count
  -----------------------+-------
   blankShell            |     1
   dataConnectionObject  |   476
   CBPDataConnection     |    68
   CBPLandDataConnection |  1474
   flowDurationGraph     |   646
   graphObject           |   675
   hydroImpoundment      |   655
   modelContainer        |  8093
   timeSeriesFile        |    21
   USGSChannelGeomObject |   654
   USGSGageObject        |  1313
   USGSSyntheticRecord   |   651
   waterSupplyElement    |    24
   waterSupplyModelNode  |   658
   wsp_vpdesvwuds        |  5938
   wsp_waterUser         |     2
  (16 rows)
  
-- VWP elements
  select objectclass, count(*) from scen_model_element where scenarioid = 13 group by objectclass;
           objectclass        | count
  --------------------------+-------
                            |     7
   blankShell               |    35
   CBPLandDataConnection    |     9
   channelObject            |     2
   dataConnectionObject     |     4
   flowDurationGraph        |     7
   giniGraph                |     7
   graphObject              |    89
   HabitatSuitabilityObject |     2
   hydroContainer           |     1
   hydroImpoundment         |     9
   modelContainer           |    90
   noaaGriddedPrecip        |     1
   reportObject             |     5
   storageObject            |    31
   timeSeriesFile           |    17
   USGSChannelGeomObject    |     9
   USGSGageObject           |    54
   USGSSyntheticRecord      |     7
   waterSupplyModelNode     |     5
   withdrawalRuleObject     |     1

*/ 

?>
