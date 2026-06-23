
<?php

// lib_vwp.php - routines to support the vwp functions

function saveCustomModelElement($listobject, $formValues, $adminsetup, $content, $elementid, $templateid, $debug, $scid = -1) {
   global $defaultgroupid;
   if ($scid == -1) {
      $scid = $_SESSION['defscenarioid'];
   }
   $debugHTML = '';
   if ($debug) {
      $debugHTML .= "Form submission: " . print_r($_POST,1) . "\n<br>";
   }
   // if we have had data submitted, save:
   $new = 0;
   $error = 0;
   if ($elementid == -1) {
      $new = 1;
      // clone the template object
      // then save the form data into the results of the clone operation
      $params = array(
         'projectid'=>3,
         'dest_scenarioid'=>$scid,
         'elements'=>array($templateid),
         'groupid'=>$defaultgroupid,
         'dest_parent'=>-1
      );
      //print("Creating a new Project<br>");
      //error_log("Calling copyModelGroupFull( " . print_r($params, 1) . ", 1)");
      //$output = copyModelGroupFull($params, 1);
      $output = copyModelGroupFull($params);
      $elementid = $output['elementid'];
      //error_log("Created new copy of template ($templateid) - $elementid ");
      if ($elementid == -1) {
         $debugHTML .=   $output['innerHTML'];
         $error = 1;
      }
      //error_log($output['innerHTML']);
   }
   if ($debug) {
      $debugHTML .= "Saving results of form submission <br>\n";
   }
   //$debug = 1;
   if (!$error) {
      $result = saveCustomElementForm($listobject, $adminsetup, $elementid, $formValues, $content, $debug, $new);
   } else {
      error_log("Error " . $error . "\n");
      $result = array();
   }
   
   // set the elementid in case we created a new one here.
   $result['elementid'] = $elementid;
   $result['debugHTML'] = $debugHTML;
   $result['error'] = $error;
   
   return $result;
}

function showVWPProjectMenu($listobject, $toggleStatus, $single, $elementid, $userid) {
   $toggleText = " style=\"display: $toggleStatus\"";
   $menuHTML = '';
   if (!$single) {
      if ($elementid == -1) {
         $toggleText = 'style="display: block;"';
      } else {
         $toggleText = 'style="display: none;"';
      }
      $menuHTML .= "<div class='insetBox'><a class='mH' id='op$i' ";
      $menuHTML .= "onclick=\"toggleMenu('vwp_projinfo')\" title='Click to Expand/Hide'>(+) Project Templates (click to view/hide)</a>";
      $menuHTML .= "<div id='vwp_projinfo' $toggleText>";
      // get all items of this templates type that this user has access to
      
      $eligible_projs = getUserObjectTypes($listobject, $userid, -1, '', 'greenDesignProject');
      //print_r($eligible_projs);
      if ($debug) {
         $menuHTML .= $eligible_projs['debugHTML'];
      }
      $user_vwp = $eligible_projs['user'];
      $group_vwp = $eligible_projs['group'];
      $menuHTML .= "<table><tr><td valign=top width=25%><b>General Functions</b><ul class=mNormal>";
      $menuHTML .= "<li><a onclick='document.forms[\"$form_name\"].elements[\"actiontype\"].value = \"load_template\"; document.forms[\"$form_name\"].submit()'>Create New Project</a></li>";
      $menuHTML .= "</ul></td>";
      $menuHTML .= "<td valign=top width=37%><b>Your Existing Projects</b><ul class=mNormal>";
      foreach ($user_vwp as $thisvwp) {
         $vid = $thisvwp['elementid'];
         $vname = $thisvwp['elemname'];
         $menuHTML .= "<li><a onclick='document.forms[\"$form_name\"].elements[\"actiontype\"].value = \"edit\";  document.forms[\"$form_name\"].elements[\"switch_elid\"].value = $vid; document.forms[\"$form_name\"].submit()'>$vname</a>";
      }
      $menuHTML .= "</ul></td><td valign=top width=37%><b>Other Projects That You Can Access</b><ul class=mNormal>";
      foreach ($group_vwp as $thisvwp) {
         $vid = $thisvwp['elementid'];
         $vname = $thisvwp['elemname'];
         $oname = $thisvwp['owner'];
         $menuHTML .= "<li><a onclick='document.forms[\"$form_name\"].elements[\"actiontype\"].value = \"edit\"; document.forms[\"$form_name\"].elements[\"switch_elid\"].value = $vid; document.forms[\"$form_name\"].submit()'>$vname ($oname)</a>";
      }
      $menuHTML .= "</ul></td></tr></table>";
      $menuHTML .= "</div></div>";
      
   }
   return $menuHTML;

}


function getModelVarsForCustomForm($elementid, $customformfile, $debug = 0, $from_stored_run = -2) {
   // $elementid is the default element, if an entry in the custom form specifies an elementid, it will
   // override this, and then the elementid will be persistent through subsequent un-elementid bearing form vars   
   $content = file_get_contents($customformfile);
   if ($debug) {
      if (!$content) {
         //error_log("File $customformfile could not be loaded. ");
      } else {
         //error_log("File beginning: " . substr($content,0,64));
      }
   }
   $forminfo = getCustomHTMLFormVars($content);
   $thisrec = array();
   foreach ($forminfo as $thisvar) {
      // check for elementid OPTIONAL
      if (isset($thisvar['elementid'])) {
         $elementid = $thisvar['elementid'];
      }
      $view = '';
      if (isset($thisvar['view'])) {
         // a custom object view is requested
		 // this allows us to have access to all manner of object info, 
		 // such as graphical output from a model run, summary queries, or even custom edit forms
         $view = $thisvar['view'];
      }
      if ($debug) {
         error_log("Var Contents:" . print_r( $thisvar, 1));
      }
      
      $view = '';
      if (isset($thisvar['view'])) {
         // a custom object view is requested
         $view = $thisvar['view'];
      }
	  
      if ($debug) {
         error_log("loadModelElement called for elementid $elementid " );
      }
      $loadres = loadModelElement($elementid, array(), 1, $from_stored_run);
	  
      if ($debug) {
         error_log("loadModelElement returned Debug: " . $loadres['debugHTML'] );
         error_log("loadModelElement returned Error: " . $loadres['errorHTML'] );
      }
      if (is_object($loadres['object'])) {
         $thisobject = $loadres['object'];
         $prop = '';
         if ($debug) {
            print("Output: " . $loadres['innerHTML'] . "<br>\n");
         }
         // check for propname MANDATORY
         // regardless of whether this is on the object or the sub-component 
         // we need to know what variable we are seeking
         if (isset($thisvar['propname'])) {
            $propname = $thisvar['propname'];
            // check for paramname MANDATORY - holds the ultimate form variable name
            if (isset($thisvar['paramname'])) {
               $paramname = $thisvar['paramname'];
               // check for subcompname OPTIONAL
               if (isset($thisvar['subcompname'])) {
                  // load the subcomp
                  $subcompname = $thisvar['subcompname'];
                  if ($debug) print("Looking for $subcompname on $elementid <br>\n");
                  if (is_object($thisobject->processors[$subcompname])) {
                     if ($debug) print("Getting $subcompname -> $propname from $elementid <br>\n");
                     if ($view == '') {
                        $prop = $thisobject->processors[$subcompname]->getProp($propname);
                     } else {
                        $prop = $thisobject->processors[$subcompname]->showElementInfo('', $view);
                     }
                     /*
                     if ($view == '') {
                        $prop = $thisobject->processors[$subcompname]->getProp($propname);
                     } else {
                        $prop = $thisobject->processors[$subcompname]->showElementInfo($propname, $view);
                     }
                     */
                     if ($debug) print("Value Returned as $prop <br>\n");
                  } else {
                     if ($debug) {
                        print("<b>Error:</b> $subcompname is not a sub-component on $elementid <br>\n");
                     }
                  }
               } else {
                  if ($debug) {
                     print("$subcompname is not a sub-component on $elementid <br>\n");
                  }
                  // this is a property, not a sub-comp, it should be displayed by itself
                  if ($view == '') {
                     $prop = $thisobject->getProp($propname);
                  } else {
                     $prop = $thisobject->showElementInfo($propname, $view);
                  }
               }
               $thisrec[$paramname] = $prop;
               // if all MANDATORY values are set, add to variable queue under variable paramname
            } else {
               print("<b>Error:</b> 'paramname' is not set in custom form <br>\n");
            }
         } else {
            print("<b>Error:</b> 'propname' is not set in custom form <br>\n");
         }
      } else {
         print("<b>Error:</b> There was a problem loading object $elementid<br>\n");
         print("Error: " . $loadres['errorHTML'] . "<br>\n");
         if ($debug) {
            print("Debug: " . $loadres['debugHTML'] . "<br>\n");
         }
      }
   }
   
   return $thisrec;
}

function saveCustomElementForm($listobject, $adminsetup, $default_elid, $formvars, $content, $debug = 0, $new = 0) {
   //** parse the form to know whether the value is for an object prop or a subcomp prop
   $result = array('innerHTML'=>'', 'errors'=>array(), 'debugHTML'=>'');
   $forminfo = getCustomHTMLFormVars($content);
   $processed = array();
   $recreate = array();
   //print("Raw form fields: " . print_r($forminfo,1) . " <br>\n");
   // load the object in question
   //** call object or subcomp setProp() method
   foreach ($forminfo as $thisinfo) {
      if (isset($thisinfo['elementid'])) {
         $thiselid = $thisinfo['elementid'];
      } else {
         $thiselid = $default_elid;
      }
      if (isset($processed[$thiselid])) {
         //error_log("Retrieving object from processed stack " . get_class($processed[$thiselid]));
         $thisobject = $processed[$thiselid];
      } else {
         //error_log("Retrieving object from database");
         $loadres = loadModelElement($thiselid, array(), 0);
         $thisobject = $loadres['object'];
         if ($loadres['error']) {
            //error_log($loadres['errorHTML']);
            //error_log($loadres['debugHTML']);
         }
      }
      if (isset($thisinfo['paramname']) and isset($thisinfo['propname'])) {
         $paramname = $thisinfo['paramname'];
         $propname = $thisinfo['propname'];
         if (isset($thisinfo['view'])) {
            $view = $thisinfo['view'];
         } else {
            $view = '';
         }
         $result['debugHTML'] .= "<br><br>Processing vars for $propname ($paramname):<br>";
         // run processMultiFormVars over the input vars with ONLY this records adminsetup info
         // therefore extracting only the variables associated with this record
         // then, all variables that appear in the "outvalues" array get applied to the (sub)object in question
         $oneadmin = array('column info' => array()); 
         $oneadmin['table info'] = $adminsetup['table info'];
         // must have the pk field value set in this, otherwise, processmultiform will not process
         $oneadmin['column info']['elementid'] = $adminsetup['column info']['elementid'];
         $oneadmin['column info'][$paramname] = $adminsetup['column info'][$paramname];
         $varout = processMultiFormVars($listobject,$formvars,$oneadmin,0,$debug);
         //error_log("Setting $propname on $subcompname = $valstring<br>\n");
         //print("<br><br>  &nbsp;&nbsp;&nbsp;&nbsp;" . print_r($varout,1) . "<br>" . print_r( $oneadmin, 1) . "<br>");
         if (isset($formvars[$paramname])) {
            $propval = $formvars[$paramname];
            if (isset($thisinfo['subcompname'])) {
               $subcompname = $thisinfo['subcompname'];
               if ($debug) {
                  if (is_array($propval)) { 
                     $valstring = print_r($propval,1);
                  } else {
                     $valstring = $propval;
                  }
                  $result['debugHTML'] .= "Setting $propname on $subcompname = $valstring<br>\n";
               }
               if (isset($thisobject->processors[$subcompname])) {
                  if (is_object($thisobject->processors[$subcompname])) {
                     if ($thisobject->processors[$subcompname]->debug) {
                        error_log("Setting $propname on $subcompname = $valstring<br>\n");
                     }
                     if (property_exists(get_class($thisobject->processors[$subcompname]), 'recreate_list')) {
                        $rec_list = explode(',', trim($thisobject->processors[$subcompname]->recreate_list));
                     } else {
                        $rec_list = array();
                     }
                     if ( (count($rec_list) > 0) and ($rec_list[0] <> '') ) {
                        //error_log("$subcompname has rec list: " . print_r($rec_list,1));
                        if (in_array($propname, $rec_list)) {
                           //error_log("Found $propname in Recreate List");
                           //error_log("Comparing $propval to " . $thisobject->getProp($propname));
                           if ($propval <> $thisobject->getProp($propname)) {
                              $recreate[$thiselid][$subcompname] = 1;
                           }
                        }
                     }
                     $thisobject->processors[$subcompname]->setProp($propname, $propval);
                     // update subcomp prop
                     //print("Setting $propname on subcomp $subcompname of parent $elementid <br>\n");
                     foreach($varout['outspecial'][$paramname] as $key => $val) {  
                        if ($debug) {
                           if (is_array($val)) { 
                              $valstring = print_r($val,1);
                           } else {
                              $valstring = $val;
                           }
                           $result['debugHTML'] .= "Setting $key on $subcompname = $valstring<br>\n";
                        }
                        $thisobject->processors[$subcompname]->setProp($key, $val);
                     }
                  }
               }
            } else {
               // update parent object prop
               if ($debug) {
                  $result['debugHTML'] .= "Setting $propname = $propval on parent $thiselid <br>\n";
               }
               $thisobject->setProp($propname, $propval);
               foreach($varout['outspecial'][$paramname] as $key => $val) { 
                  //print("Setting $key on parent $thiselid <br>\n");
                  $thisobject->setProp($key, $val);
               }
            }
         }
      } else {
         $result['errors'][] = "Failed to process: " . print_r($thisinfo,1) . "<br>";
      }
      // process recreate calls
      //error_log("Checking reCreate calls");
      $processed[$thiselid] = $thisobject;
   }
   //** save the subcomps on the object and the object itself
   //error_log("Calling saveObjectSubComponents \n");
   foreach ($processed as $thiselid => $thisobject) {
      // call the create() routine if this is a new one
      if ($new) {
         //error_log("**************** Calling create() for new object.");
         $thisobject->create();
      }
      foreach (array_keys($recreate[$thiselid]) as $thisprop) {
         if (is_object($thisobject->processors[$thisprop])) {
            //error_log("Calling reCreate() on sub-comp $thisprop");
            $thisobject->processors[$thisprop]->reCreate();
         }
      }
      $res = saveObjectSubComponents($listobject, $thisobject, $thiselid, 1, $debug);
      if ($debug) {
         $result['debugHTML'] .= "<b>Result of subobject save :</b><br> $res <br>";
      }
      if (!is_object($thisobject)) {
         $result['errors'][] = "Object got clobbered during sub-comp save <br>";
      }
      //** save the object
      //updateObjectProps(3, $cid, $prop_array)
      if ($debug) {
         $result['debugHTML'] .= "Calling updateObjectPropList for $thiselid \n";
      }
      $res = updateObjectPropList($thiselid, $thisobject, $debug);
      if ($debug) {
         $result['debugHTML'] .= "<b>Result of object save :</b><br> $res <br>";
      }
   }
   return $result;
}

function showVWPTemplates($listobject, $userid) {
   global $usergroupids;
   $ret = array();
   # get base model domains, then later we will get the Model Containers from them
   $listobject->querystring = "  select a.elementid, a.elemname ";
   $listobject->querystring .= " from scen_model_element as a ";
   $listobject->querystring .= " where ownerid = $userid ";
   $listobject->querystring .= "    and custom1 = 'cova_vwp_projinfo' ";
   $listobject->querystring .= " order by a.elemname  " ;
   $ret['debugHTML'] .= "$listobject->querystring <br>";
   $listobject->performQuery();
   //$listobject->showList();

   $ret['user'] = $listobject->queryrecords;
   # get base model domains, then later we will get the Model Containers from them
   $listobject->querystring = "  select a.elementid, a.elemname, b.username as owner ";
   $listobject->querystring .= " from scen_model_element as a, users as b ";
   $listobject->querystring .= " where ownerid <> $userid ";
   $listobject->querystring .= "    and a.groupid in ($usergroupids) ";
   $listobject->querystring .= "    and a.gperms >= 4 ";
   $listobject->querystring .= "    and a.custom1 = 'cova_vwp_projinfo' ";
   $listobject->querystring .= "    and a.ownerid = b.userid ";
   $listobject->querystring .= " order by a.elemname  " ;
   $ret['debugHTML'] .= "$listobject->querystring <br>";
   $listobject->performQuery();

   $ret['group'] = $listobject->queryrecords;
   
   return $ret;
}

function guessCOVALocation($listobject, $scenarioid, $lat, $lon, $debug = 0) {
   
   // check for containment by local trib
   // check for containment by major segment
   $options = findCOVALocationPossibilities($listobject, $scenarioid, $lat, $lon, $debug);
   
}

function findCOVALocationPossibilities($listobject, $scenarioid, $latdd, $londd, $debug = 0) {
   global $usgsdb;
   // check for containment by local trib
   // check for containment by major segment
   // check for nearest local trib
   // get containing NHD+ segment
   $options = array(); // type- nhd+ / major_seg / local_seg; id - elementid/com_id
   $recs = getElementsContainingPoint($listobject, $scenarioid, $latdd, $londd, $debug);
   //error_log($recs);
   foreach ($recs as $thisrec) {
      // check custom1 and see what gives
      $type = $thisrec['custom1'];
      $elementid = $thisrec['elementid'];
      $local_area = -1;
      $name = $thisrec['elemname'];
      switch ($type) {
         case 'cova_ws_container':
            // hierarchical, complex container
            // get stream stats, cumulative area, local area
            $channelid = getCOVAMainstem($listobject, $elementid);
            $props = getElementPropertyValue($listobject, $channelid, array('area','drainage_area','the_geom'), $debug);
            $local_area = $props['area'];
            $cumulative_area = $props['drainage_area'];
         break;
         
         case 'cova_ws_subnodal':
            // hierarchical, complex container
            $channelid = getCOVAMainstem($listobject, $elementid);
            $props = getElementPropertyValue($listobject, $channelid, array('area','drainage_area','the_geom'), $debug);
            $local_area = $props['area'];
            $cumulative_area = $props['drainage_area'];
         break;
         
         case 'vahydro_lite_container':
            // non-hierarchical, simple container
         break;
      }
      $the_geom = getElementShape($elementid);
      if ($local_area >= 0) {
         $options[] = array('type'=>$type, 'id'=>$elementid, 'name'=>$name, 'local_area'=>$local_area, 'cumulative_area' => $cumulative_area, 'the_geom'=>$the_geom);
      }
   }
   
   if (is_object($usgsdb)) {
      // now look at NHD+ to find the location of the containing NHD+ segment
      $nhdinfo = findNHDSegment($usgsdb, $latdd, $londd, $debug, 'sqmi');
      $nhd_area = $nhdinfo['cumdrainag'];
      $comid = $nhdinfo['comid'];
      $wktgeom = $nhdinfo['wktgeom'];
      if ($nhd_area > 0) {
         $options[] = array('type'=>'nhd+', 'id'=>$comid, 'local_area' => $nhd_area, 'cumulative_area' => $nhd_area, 'the_geom' => $wktgeom);
      }
      // add next largest downstream
      $comid = findNextDown($usgsdb, $comid, $debug);
      $nhdinfo = findNHDSegInfo($usgsdb, $comid, $debug, 'sqmi');
      $nhd_area = $nhdinfo['cumdrainag'];
      $comid = $nhdinfo['comid'];
      $wktgeom = $nhdinfo['wktgeom'];
      if ($nhd_area > 0) {
         $options[] = array('type'=>'nhd+', 'id'=>$comid, 'local_area' => $nhd_area, 'cumulative_area' => $nhd_area, 'the_geom' => $wktgeom);
      }
      
   }
   
   return $options;
   
}

function scratchModelProperties () {

   // add this data to the Edit Properties View
   // add a custom mode view to the "Run Model / View Results" panel
   $content = "[formfield propname=name paramname=name][/formfield]";
   $content .= "[modelobject propname=name paramname=name][/modelobject]";
   $dynamic_fields = $thisrec;
   $results_panel = parseMarkupSubstituteValues('formfield', $content, $dynamic_fields, $debug);

   // parseMarkup just finds tags and parses them for later processing
   $model_viewers = parseMarkup('modelobject', $results_panel, $debug);
   $model_propnames = array();
   foreach ($model_viewers as $thismodel) {
      if (isset($unserobjects[$elementid])) {
         //$unserobjects[$elementid]->debug = 1;
         //$model_info = "Calling showElementInfo " . $unserobjects[$elementid]->showElementInfo('', 'info');
         $model_info = "Calling showElementInfo FOR $thismodel " . $unserobjects[$elementid]->showElementInfo('', 'editform');
      }
   }

}


function showBioDB ($wkt) {
   global $vwuds_dbip, $aquatic_biodb;
   $retarr = array('innerHTML'=>'', 'debug'=>'');
   // set up conn to new edas db
   //$edas_db_dbconn = pg_connect("host=$vwuds_dbip dbname='aquatic_bio' user='aquatic_bio_ro' password=@quaticB10");
   //$edas_db = new pgsql_QueryObject;
   //$edas_db->dbconn = $edas_db_dbconn;
   $edas_db = $aquatic_biodb;
   $edas_db->querystring = " select a.\"Latitude\", a.\"Longitude\", b.* ";
   $edas_db->querystring .= " from \"Stations\" as a, \"edas_xtab_export1\" as b ";
   $edas_db->querystring .= " where contains(geomfromtext('$wkt',4326), setsrid(a.the_geom,4326))";
   $edas_db->querystring .= " and a.\"StationID\" = b.\"StationID\" ";
   $edas_db->performQuery();
   $edas_db->show = 0;
   $edas_db->showList();
   $retarr['innerHTML'] .= "<b>Biometric Data Results from EDAS db</b><br>";
   $retarr['innerHTML'] .= "<i>Learn more from <a href='http://www.edas2.com/dokuwiki/doku.php/history' target=_new>EDAS Web Site</a></i><br>";
   $retarr['innerHTML'] .= "Query returned $edas_db->numrows biometric analysis points<br>";
   $retarr['innerHTML'] .= "Error: $edas_db->error <br>";
   $retarr['innerHTML'] .= $edas_db->outstring;
   //$retarr['innerHTML'] .= $edas_db->querystring . "<br>";
   $retarr['debug'] .= $edas_db->querystring;
   $foo_table = "( " . $edas_db->querystring . ") as foo ";
   //$wiz = showGenericQueryWizard ($edas_db, array(), 'edas_xtab_export1', 'edas_bio', 'xajax');
   /* 
   // this query wizard approach does not yet work, so only show all data in a single form
   $formvals = array('tablename'=>'edas_xtab_export1', 'xajax_submit' => 'xajax_refreshAquaticBioAnalysisWindow');
   $wiz = showGenericAnalysisWindow( $formvals, $aquatic_biodb,$debug, 'xajax', $foo_table);
   $retarr['innerHTML'] .= "<div id='edas_bio" . "' style=\"border: 1px solid rgb(0 , 0, 0); border-style: dotted; overflow: auto; height: 480px; width: 720px; display: block;  background: #eee9e9;\">";;
   $retarr['innerHTML'] .= "<form name='edas_bio' id='edas_bio'>";
   $retarr['innerHTML'] .= $wiz['innerHTML'];
   $retarr['innerHTML'] .= "</form>";
   $retarr['innerHTML'] .= "</div>";
   //$retarr['innerHTML'] .= $edas_db->querystring;
   */
   
   $bioquery = " select sppbova,taxagrp,genus,species,subspecies, common_nam, targetspp, count, waterbody from observed_vdgif_xy where contains(geomfromtext('$wkt',4269), setsrid(the_geom,4269)) and taxagrp = 'Fish' ";
   //$bioquery = " select sppbova,taxagrp,genus,species,subspecies, common_nam, targetspp, count, waterbody, y(the_geom) as lat, x(the_geom) as lon from observed_vdgif_xy where contains(geomfromtext('$wkt',4269), setsrid(the_geom,4269)) ";
   
   
   
//error_reporting(E_ALL);   
   // USE THIS for analysis grid
   
   
   // un-comment this for plain old vanilla
   $aquatic_biodb->querystring = $bioquery;
   $aquatic_biodb->performQuery();
   $aquatic_biodb->show = 0;
   $aquatic_biodb->showList();
   $retarr['innerHTML'] .= "<b>Historic biological monitoring species sampled in this watershed.</b><br>";
   $retarr['innerHTML'] .= $aquatic_biodb->outstring;
   //$retarr['innerHTML'] .= $bioquery . " ; <br>";
   
   return $retarr;
}

?>