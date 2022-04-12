<?php

# Database Functions for use with data base object (should not care if pg or mysql or MS sql server):
   # expects an object with thre following attributes
      # dbconn
      # querystring
      #
function implode_md($glue, $array, $array_path='')
{
    if ( !empty($array_path) )
    {
        $array_path = explode('.', $array_path);

        if ( ( $array_path_sizeof = sizeof($array_path) ) < 1 )
        {
            return implode($glue, $array);
        }
    }
    else
    {
        return implode($glue, $array);
    }

    $str = '';

    $array_sizeof = sizeof($array) - 1;

    for ( $i = 0; $i < $array_sizeof; $i++ )
    {
        $value = $array[ $i ];

        for ( $j = 0; $j < $array_path_sizeof; $j++ )
        {
            $value =& $value[ $array_path[ $j ] ];
        }

        $str .= $value . $glue;
    }

    $value = $array[ $array_sizeof ];

    for ( $j = 0; $j < $array_path_sizeof; $j++ )
    {
        $value =& $value[ $array_path[ $j ] ];
    }

    $str .= $value;

    return $str;
}

function formMultiList($dbobj,$varname,  $listtable, $listpkcol, $listcolumn, $selectedcol, $orderby)
{

   $getlistsql = "select distinct $listpkcol, $listcolumn from $listtable";
   if ($orderby <> '') {
      $getlistsql .= " order by $orderby";
   }


   $dbobj->querystring = $getlistsql;
   #print("$getlistsql");
   $dbobj->performQuery();
   $thisresultset = $dbobj->queryrecords;


   print("<select name='$varname");
   print("[]' size='5' multiple>\n");

   if (strlen($selectedcol) == 0) {
      print("<option value=''>---</option>");
   }

   $selarray = explode(',',$selectedcol);

   foreach ($thisresultset as $getlistrow) {
      $selecttext = $getlistrow[$listcolumn];
      $selectid = $getlistrow[$listpkcol];
      $selected = "";
      if ( in_array($selectid, $selarray) ) { $selected = " selected"; }
      print("<option value='$selectid'$selected>$selecttext</option>");
   }

   print("</select>");

} /* end showRangeList */

function showMultiList2($dbobj,$varname, $listtable, $listpkcol, $listcolumn, $selectedcol, $whereclause, $orderby, $debug, $numrows = 5, $silent=0, $disabled=0)
{
   if (is_array($selectedcol)) {
      $selectedcol = join(',', $selectedcol);
      if ($debug) {
         $innerHTML .= "Selected Columns converted from array <br>";
      }
   }
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   $innerHTML = '';
   if ( ($listpkcol == $listcolumn) or (in_array($listpkcol, explode(",", $listcolumn))) ) {
      $selcols = $listcolumn;
   } else {
      $selcols = "$listpkcol, $listcolumn ";
   }
   $getlistsql = "select $selcols from $listtable ";
   if ($whereclause <> '') {
      $getlistsql .= " where $whereclause";
   }
   $getlistsql .= " group by $selcols";
   if ($orderby <> '') {
      $getlistsql .= " order by $orderby";
   }


   if (is_array($dbobj)) {
      $thisresultset = $dbobj;
   } else {
      $dbobj->querystring = $getlistsql;
      if ($debug) {
         $innerHTML .= "$getlistsql ;<br>";
      }
      if (is_object($dbobj)) {
         $dbobj->performQuery();
         $thisresultset = $dbobj->queryrecords;
      } else {
         if ($debug) {
            $innerHTML .= "DB Object Not Set, no results returned.<br> ";
         }
         $thisresultset = array();
      }
   }
   if ($debug) {
      $innerHTML .= "Selected Columns: " . print_r( $selectedcol,1) . " <br>";
   }


   $innerHTML .= "<select name='$varname";
   $innerHTML .= "[]' size='$numrows' multiple $distext>\n";

   if (strlen($selectedcol) == 0) {
      $innerHTML .= "<option value=''>---</option>";
   }

   $selarray = explode(',',$selectedcol);
   if ($debug) {
      $innerHTML .= print_r($selarray,1);
   }
   
   foreach ($thisresultset as $getlistrow) {
      $lcols = explode(",", $listcolumn);
      $selecttext = '';
      $seldel = '';
      foreach ($lcols as $tc) {
         $selecttext .= $seldel . $getlistrow[ltrim(rtrim($tc))];
         $seldel = ',';
      }
      $selectid = $getlistrow[$listpkcol];
      $selected = "";
      if ( in_array($selectid, $selarray) ) { $selected = " selected"; }
      $innerHTML .= "<option value='$selectid'$selected>$selecttext</option>";
   }

   $innerHTML .= "</select>";
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }


} /* end showRangeList */


function showActiveList($dbobj, $thiscolname, $listtable, $listcolumn, $listpkcol, $whereclause,$selectedcol, $onchange, $orderby, $debug, $silent=0, $disabled=0, $elid='', $readonly=0, $options_only=0) {

   $rotext = '';
   $options_html = '';
   
   if (strlen($whereclause) > 0) {
      $conditions = "where $whereclause";
   } else {
      $conditions = '';
   }
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   if (strlen($elid) > 0) {
      $eltext = " id=$elid";
   } else {
      $eltext = '';
   }
   $innerHTML = '';
   
   //$innerHTML .= "Debug setting: $debug<br>";
   
   $orderclause = '';

   if (strlen($orderby) > 0) {
      $orderclause = "order by $orderby";
   }

   # id $dbobj is an array
   #we have been passed an array of values to use instead of a query
   # therefore, do NOT treat this as an object
   if (is_array($dbobj)) {
      $thisresultset = $dbobj;
   } else {
      // this is added to make the regular showList and the showActiveList conform to the same behaviour
      if (is_array($listtable)) {
         # list values have been passed in by an array, thus, no need to query
         $thisresultset = $listtable;
      } else {
         $dbobj->querystring = "select $listcolumn,$listpkcol from $listtable $conditions $orderclause";
         $dbobj->performQuery();
         if ($debug) { $innerHTML .= "$dbobj->querystring <br>\n"; }
         $thisresultset = $dbobj->queryrecords;
         if ($debug) { $innerHTML .= "$thisresultset <br>\n"; }
         if ($debug) { $innerHTML .= "Dbobj debug level: $dbobj->debug <br>\n"; }
         if ($debug) { $innerHTML .= "Selected Column Value: $selectedcol <br>\n"; }
      }
   }
   
   $chtag = "";
   if(strlen($onchange) > 0) { 
      if (strpos($onchange, "'")) {
         $q = "\"";
      } else {
         $q = "'";
      }
      $chtag = " onChange=$q$onchange$q";
   }
   $innerHTML .= "<select name='$thiscolname'$chtag $distext $eltext>";

   if (strlen($selectedcol) == 0) {
     $innerHTML .= "<option value=''>---</option>";
     $options_html .= "<option value=''>---</option>";
   }


   #if ($listpkcol == 'elementid') { error_log(print_r(array_keys($thisresultset[0]),1)); }
   $lcols = explode(',', $listcolumn);
   foreach ($thisresultset as $getlistrow) {
     $selectid = $getlistrow[$listpkcol];
     $seldel = '';
     $selecttext = '';
     foreach ($lcols as $thiscol) {
        $scol = str_replace("\"", '', $thiscol);
        $selecttext .= $seldel . $getlistrow[$scol];
        $seldel = ',';
     }
     $selected = "";
     if ($selectid == $selectedcol) {
        $selected = " selected";
        if ($readonly) {
           $rotext = $selecttext;
        }
     }
     $innerHTML .= "<option value='$selectid'$selected>$selecttext</option>";
     $options_html .= "<option value='$selectid'$selected>$selecttext</option>";
   }
   $innerHTML .= "</select>";
   if ($options_only) {
      // if we request this, we only want to fill in the middle part
      $innerHTML = $options_html;
   }
   if (!$silent) {
      print $innerHTML;
   } else {
      if ($readonly) {
         return $rotext;
      } else {
         return $innerHTML;
      }
   }

}


function showList($dbobj,$varname,$listtable,$listcolumn,$listpkcol,$whereclause,$selectedcol,$debug, $silent=0, $disabled=0, $readonly=0) {

   $onchange = ''; # this is asked for later, but is not passed in by the cfunction call, so we set it to '',
   $rotext = '';

   if (strlen($whereclause) > 0) {
      $conditions = "where $whereclause";
   } else {
      $conditions = '';
   }
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   $innerHTML = '';

   if (is_array($listtable)) {
      # list values have been passed in by an array, thus, no need to query
      $thisresultset = $listtable;
   } else {
      $dbobj->querystring = "select $listcolumn,$listpkcol from $listtable $conditions order by $listcolumn";
      $dbobj->performQuery();
      if ($debug) { $innerHTML .= "$dbobj->querystring <br>\n"; }
      $thisresultset = $dbobj->queryrecords;
   }

   if ($debug) { $innerHTML .= "$thisresultset <br>\n Selected ID: $selectedcol <br>\n"; }
   if ($debug) { $innerHTML .= "Dbobj debug level: $dbobj->debug <br>\n"; }
   $chtag = "";
   if(strlen($onchange) > 0) { $chtag = " onChange=\"$onchange\"";}
   $innerHTML .= "<select name='$varname'$chtag $distext>";

   if (strlen($selectedcol) == 0) {
     $innerHTML .= "<option value=''>---</option>";
   }

   $lcols = explode(',', $listcolumn);

   foreach ($thisresultset as $getlistrow) {
     $spk = str_replace("\"", '', $listpkcol);
     $selectid = $getlistrow[$spk];
     $selecttext = '';
     $seldel = '';
     reset($lcols);
     foreach ($lcols as $thiscol) {
        $scol = str_replace("\"", '', $thiscol);
        $selecttext .= $seldel . $getlistrow[$scol];
        $seldel = ',';
     }
     $selected = "";
     if ($selectid == $selectedcol) { 
        $selected = " selected"; 
        if ($readonly) {
           $rotext = $selecttext;
        }
     }
     $innerHTML .= "<option value='$selectid'$selected>$selecttext</option>";
   }
   $innerHTML .= "</select>";

   if (!$silent) {
      print $innerHTML;
   } else {
      if ($readonly) {
         return $rotext;
      } else {
         return $innerHTML;
      }
   }
}


function
showRangeList($dbobj,$varname,$listtable,$listcolumn,$whereclause,$selectedcol,$orderby)
{

   if (strlen($whereclause) > 0) {
      $conditions = "where $whereclause";
   } else {
      $conditions = '';
   }

   $getlistsql = "select distinct $listcolumn from $listtable $conditions";
   if ($orderby <> '') {
      $getlistsql .= " order by $orderby";
   }


   $dbobj->querystring = $getlistsql;
   #print("$getlistsql");
   $dbobj->performQuery();
   $thisresultset = $dbobj->queryrecords;

   $chtag = "";
   #if(strlen($onchange) > 0) { $chtag = " onChange=\"$onchange\"";}

   print("<select name='$varname'$chtag>");

   if (strlen($selectedcol) == 0) {
      print("<option value=''>---</option>");
   }

   foreach ($thisresultset as $getlistrow) {
      $selecttext = $getlistrow[$listcolumn];
      $selectid = $selecttext;
      $selected = "";
      if ($selectid == $selectedcol) { $selected = " selected"; }
      print("<option value='$selectid'$selected>$selecttext</option>");
   }

   print("</select>");

} /* end showRangeList */

function
showMultiList($dbobj,$varname,$listtable,$listcolumn,$selectedcol, $orderby)
{

   $getlistsql = "select distinct $listcolumn from $listtable";
   if ($orderby <> '') {
      $getlistsql .= " order by $orderby";
   }


   $dbobj->querystring = $getlistsql;
   #print("$getlistsql");
   $dbobj->performQuery();
   $thisresultset = $dbobj->queryrecords;

   $selarray = explode(',',$selectedcol);

   print("<select name='$varname");
   print("[]' size='5' multiple>\n");

   if (strlen($selectedcol) == 0) {
      print("<option value=''>---</option>");
   }

   foreach ($thisresultset as $getlistrow) {
      $selecttext = $getlistrow[$listcolumn];
      $selectid = $selecttext;
      $selected = "";
      if ( in_array($selectid, $selarray) ) { $selected = " selected"; }
      print("<option value='$selectid'$selected>$selecttext</option>");
   }

   print("</select>");

} /* end showRangeList */


/* -- Start showMapList -- */
function showMapList($db,$dbname,$cname,$maptable,$mapkeycolumn,$mapdatacolumn,$foreigntable,$foreignkeycol,$foreigndisplaycol,$rec_id,$extrawhere) {


 #  $thissql = "select $maptable.$mapdatacolumn,$foreigntable.$foreignkeycolumn,$foreigntable.$foreigndisplaycolumn";

   $dbobj->querystring = "select $mapdatacolumn from $maptable where $mapkeycolumn = $rec_id";
   $dbobj->performquery();
   $thisresultset = $dbobj->queryrecords;

   $optiondelim = "";
   $optionlist = "";
   foreach ($thisresultset as $optionrow) {
      $optionval = $optionrow["$mapdatacolumn"];
      $optionlist .= "$optiondelim$optionval";
      $optiondelim = ",";
   }
   #print("$optionlist<br>");
   $selhtml = "<select name='$cname";
   $selhtml .= "[]' size='5' multiple>\n";

   if ( (!isset($extrawhere)) or ($extrawhere == "")) { $extrawhere = '0 = 0'; }

   $dbobj->querystring = "select $foreignkeycol,$foreigndisplaycol from $foreigntable where $extrawhere";
   #print ("$mapsql<br>");
   $dbobj->performquery();
   foreach ($dbobj->queryrecords as $maprow) {
      $listval = $maprow[$foreignkeycol];
      $dispval = "";
      $dispdel = "";

      foreach(explode(",",$foreigndisplaycol) as $dispcol) {
         $thisdispval = $maprow[$dispcol];
         $dispval .= "$dispdel$thisdispval";
         $dispdel = ", ";
      }

      $selstring = "";
      if (in_array($listval,explode(",",$optionlist))) {$selstring = " selected";}
      $selhtml .= "<option value='$listval'$selstring>$dispval</option>\n";
   }
   $selhtml .= "</select>\n";

   print(" $selhtml <br>");
} /* -- End showMapList -- */

/* -- Start updateMapList -- */
function updateMapList($dbobj,$maptable,$mapkeycolumn,$mapdatacolumn,$foreigntable,$foreignkeycol,$foreigndisplaycol,$rec_id,$selectedrows) {


   $dbobj->querystring = "delete from $maptable where $mapkeycolumn = $rec_id";
   $dbobj->performquery();

   $selectedar = explode(",",$selectedrows);
   reset($selectedar);

   foreach ($selectedar as $listval) {

      $dbobj->querystring = "insert into $maptable($mapkeycolumn,$mapdatacolumn) values($rec_id,$listval)";
      $dbobj->performquery();
      #print("$dbobj->querystring<br>");
   }

   #print("$selectedrows<br>");

} /* -- End updateMapList -- */

function showMultiLinkList($dbobj, $containertable, $keycol, $keyval, $pkcol, $listcol, $linkURL, $otherparams, $extrawhere, $ordercol, $target, $debug) {

   $whereclause = '';
   if (strlen($extrawhere) > 0) {
      $whereclause = "and $extrawhere";
   }

   if (strlen($ordercol) > 0) {
         $orderclause = "order by $ordercol";
   }

   $dbobj->querystring = "select $pkcol, $listcol from $containertable where $keycol = $keyval $whereclause $orderclause";

   if ($debug) { print("$dbobj->querystring<br>"); }

   $dbobj->performquery();
   $qvals = $dbobj->queryrecords;
   $urlend = '';

   if (strlen($otherparams) > 0) {
      $urlend = "&$otherparams";
   }

   if (strlen($target) > 0) {
      $targetstr = "target='$target'";
   }

   print("<ul>");

   foreach ($qvals as $thisrec) {
      $linktext = '';
      foreach(explode(',', $listcol ) as $tcol) {
         $linktext .= $thisrec[ltrim(rtrim($tcol))] . ' ';
      }
      $linkid = $thisrec[$pkcol];

      print("<li><a href='$linkURL?$pkcol=$linkid$urlend' $targetstr>$linktext</a>");
   }

   print("</ul>");

   return;
}


function showCustomHTMLForm($listobject,$thisrec,$aset, $content, $ismulti, $mindex, $debug = 0, $disabled = 0) {
   $formobject = showFormVars($listobject,$thisrec,$aset,1, 1, $debug, $ismulti, 1, $disabled, $fno=-1, $mindex, 1);
   #print("Form Pieces:<br>");
   #print_r($formobject->formpieces);
   #print("<hr>");
   $fields = $formobject->formpieces['fields'];
   $debuginfo = '';
   
   $regex = '/\[formfield[^\]]*\](.*?)\[\/formfield\]/si';
   preg_match_all( $regex, $content, $matches );
   
   $regex = '/\[formfield[^\]](.*?)\]/si';
   $set_attributes = array();
   $replacements = array();
   $att_sets = 1;
   foreach ($matches[0] as $matchkey=>$thismatch) {
      preg_match_all( $regex, $thismatch, $att_tags );
      #print("\n Attribute tags?:\n");
      $attline = $att_tags[1][0];
      #print("$attline\n");
      #$attpairs = explode("=", explode(" ", $attline));
      $attpairs = explode(" ", $attline);
      if (count($attpairs) > 0) {
         $replacements[$matchkey] = '';
      }
      foreach ($attpairs as $thispair) {
         list($key, $value) = explode("=", $thispair);
         $set_attributes[$att_sets][$key] = $value;
         switch ($key) {
            case 'paramname':
               if ($debug) {
                  $debuginfo .= "formfield $value replaced with" . $fields[$value] . "<br>";
               }
               if (in_array($value, array_keys($fields))) {
                  #array_push($replacements, $fields[$value]);
                  $replacements[$matchkey] .= $fields[$value];
               }
            break;
         }
      }
      $att_sets++;
   }
   
   preg_match_all( $regex, $content, $att_tags );
   if ($debug) {
      $debuginfo .= "<hr>These entities matching tag syntax:<br>";
      $debuginfo .= print_r($matches[0],1);
      $debuginfo .= "<hr>Should be replaced by:<br>";
      $debuginfo .= print_r($replacements,1);
   }
   
   #test replace
   #$output = str_replace($matches[0], $matches[1], $content);
   $output = $debuginfo . str_replace($matches[0], $replacements, $content);
   return $output;
}

function parseMarkupSubstituteValues($tagname, $content, $fields, $debug = 0) {

   $debuginfo = '';
   
   $regex = '/\[' . $tagname . '[^\]]*\](.*?)\[\/' . $tagname . '\]/si';
   preg_match_all( $regex, $content, $matches );
   
   $regex = '/\[' . $tagname . '[^\]](.*?)\]/si';
   $set_attributes = array();
   $replacements = array();
   $att_sets = 1;
   foreach ($matches[0] as $matchkey=>$thismatch) {
      preg_match_all( $regex, $thismatch, $att_tags );
      #print("\n Attribute tags?:\n");
      $attline = $att_tags[1][0];
      #print("$attline\n");
      #$attpairs = explode("=", explode(" ", $attline));
      $attpairs = explode(" ", $attline);
      if (count($attpairs) > 0) {
         $replacements[$matchkey] = '';
      }
      foreach ($attpairs as $thispair) {
         list($key, $value) = explode("=", $thispair);
         $set_attributes[$att_sets][$key] = $value;
         switch ($key) {
            case 'paramname':
               if ($debug) {
                  $debuginfo .= "formfield $value replaced with" . $fields[$value] . "<br>";
               }
               if (in_array($value, array_keys($fields))) {
                  #array_push($replacements, $fields[$value]);
                  $replacements[$matchkey] .= $fields[$value];
               }
            break;
         }
      }
      $att_sets++;
   }
   
   preg_match_all( $regex, $content, $att_tags );
   if ($debug) {
      $debuginfo .= "<hr>These entities matching tag syntax:<br>";
      $debuginfo .= print_r($matches[0],1);
      $debuginfo .= "<hr>Should be replaced by:<br>";
      $debuginfo .= print_r($replacements,1);
      $debuginfo .= "<hr>From the input:<br>";
      $debuginfo .= print_r($fields,1);
   }
   
   #test replace
   #$output = str_replace($matches[0], $matches[1], $content);
   $output = $debuginfo . str_replace($matches[0], $replacements, $content);
   return $output;
}

class modelMarkupObject {
   var $name = '';
   var $tag = '';
   var $tagHTML = '';
   var $tag_contents = ''; // whatever is between an opening and closing tag
   var $tag_value = ''; // whatever the final rendered value of the tag is
   var $attributes = array();
   var $att_pairs = array();
   var $children = array();
}

function parseMarkup($tagname, $content, $debug = 0) {
   // returns an object of class modelMarkupObject, with children for each occurence of the tag in the content
   // this
   $debuginfo = '';
   $retobj = new modelMarkupObject();
   
   $regex = '/\[' . $tagname . '[^\]]*\](.*?)\[\/' . $tagname . '\]/si';
   preg_match_all( $regex, $content, $matches );
   
   $regex = '/\[' . $tagname . '[^\]](.*?)\]/si';
   $set_attributes = array();
   $replacements = array();
   $att_sets = 1;
   foreach ($matches[0] as $matchkey=>$thismatch) {
      $thisobj = new modelMarkupObject();
      $thisobj->tag = $matchkey;
      $thisobj->tagHTML = $thismatch;
      preg_match_all( $regex, $thismatch, $att_pairs );
      $thisobj->att_pairs = $att_pairs;
      foreach ($att_pairs[1] as $these_atts) {
         foreach (explode(' ', $these_atts) as $thispair) {
            list($key, $value) = explode("=", $thispair);

            switch ($key) {
               case 'name':
                  $thisobj->name = $value;
               break;

               default:
                  $thisobj->attributes[$key] = $value;
               break;
            }
         }
      }
      $retobj->children[] = $thisobj;
      $att_sets++;
   }
   
   return $retobj;
}

function getCustomHTMLFormVars($content) {
   
   // gets the variables that are present in a custom HTML form
   
   $regex = '/\[formfield[^\]]*\](.*?)\[\/formfield\]/si';
   preg_match_all( $regex, $content, $matches );
   
   $regex = '/\[formfield[^\]](.*?)\]/si';
   $form_fields = array();
   $att_sets = 0;
   foreach ($matches[0] as $matchkey=>$thismatch) {
      preg_match_all( $regex, $thismatch, $att_tags );
      #print("\n Attribute tags?:\n");
      $attline = $att_tags[1][0];
      #print("$attline\n");
      #$attpairs = explode("=", explode(" ", $attline));
      $attpairs = explode(" ", $attline);
      foreach ($attpairs as $thispair) {
         list($key, $value) = explode("=", $thispair);
         $form_fields[$att_sets][$key] = $value;
      }
      $att_sets++;
   }
   
   return $form_fields;
}

function showFormVars($dbobj,$thisrecord,$adminsetup,$showlabels, $showmissing, $debug, $multiform=0, $silent=0, $disabled=0, $fno=-1, $multiformindex = NULL, $returnobject=0) {

   $formHTML = '';

   $tablename = $dbobj->tablename;
   $colinfo = $adminsetup['column info'];
   if (isset($colinfo['valign'])) {
      $valign = $colinfo['valign'];
   } else {
      $valign = 'bottom';
   }
   $tableinfo = $adminsetup['table info'];
   $columns = array();
   
   if (isset($tableinfo['mark_mandatory'])) {
      $mark_mandatory = $tableinfo['mark_mandatory'];
   } else {
      $mark_mandatory = 0;
   }
   
   if (isset($tableinfo['showlabels']) and ($showlabels == -1)) {
      // have to expliocitly ask table info to override showlabels to preserve backwards compatibility.
      $showlabels = $tableinfo['showlabels'];
   }
   
   
   if (isset($tableinfo['mandatory_text'])) {
      $mandatory_text = $tableinfo['mandatory_text'];
   } else {
      $mandatory_text = ' * ';
   }

   if (isset($tableinfo['output type'])) {
      # multi forms must appear in list, not tabbed form.
      switch ((!$multiform) and $tableinfo['output type']) {
         case 'tabbed':
            $outobject = new tabbedListObject;
            if (isset($tableinfo['tabs'])) {
               foreach (array_keys($tableinfo['tabs']) as $thistab) {
                  if (isset($tableinfo['tabs'][$thistab]['tab header'])) {
                     $th = $tableinfo['tabs'][$thistab]['tab header'];
                  } else {
                     $th = '';
                  }
                  $outobject->addTab($thistab, $tableinfo['tabs'][$thistab]['tab label'], $th);
               }
            }
            if (isset($tableinfo['divname'])) {
               $outobject->name = $tableinfo['divname'];
            }
            $outobject->init();
            if (isset($tableinfo['width'])) {
               $outobject->width = $tableinfo['width'];
            }
            if (isset($tableinfo['height'])) {
               $outobject->height = $tableinfo['height'];
            }
         break;

         default:
            $outobject = new genericListObject;
         break;
      }
   } else {
      $outobject = new genericListObject;
   }
   $outobject->values = $thisrecord;

   $incolumns = array_keys($thisrecord);
   if (!is_array($incolumns)) {
      $incolumns = array();
   }
   # order by adminsetup if present
   $ascolumns = array_keys($colinfo);
   $outobject->debugHTML .= "Adminsetup Columns: " . print_r($ascolumns,1) . "<br>";
   
   foreach ($ascolumns as $ascol) {
      if (in_array($ascol, $incolumns)) {
         $found = 0;
         while (!$found) {
            $thiscol = array_shift($incolumns);
            if ($thiscol == $ascol) {
               array_push($columns, $thiscol);
               $found = 1;
            } else {
               array_push($incolumns, $thiscol);
            }
         }
      }
   }
   if ($multiform) {
      if (!isset($tableinfo['showPlusMinus'])) {
         # default to not show the plus/minus form row buttons
         $tableinfo['showPlusMinus'] = 0;
      }
      if (!isset($tableinfo['formName'])) {
         # default to not show the plus/minus form row buttons
         $tableinfo['formName'] = 'defaultform';
      }
   }
   # append the rest to the end of the array
   $columns = array_merge($columns, $incolumns);
   if (!$showmissing) {
      # use only columns in the admin setup record
      $columns = array_intersect($columns, array_keys($colinfo));
   }
   if (count($columns) == 0) {
      # if this is blank, we assume that we are calling for a blank form entry, therefore, do basic adminsetup record
      $columns = $ascolumns;
      $thisrecord = array();
      foreach ($ascolumns as $thiscol) {
         $thisrecord[$thiscol] = $colinfo[$thiscol]['default'];
      }
   }
   if (isset($adminsetup['rowcolor'])) {
      $rowcolor = $adminsetup['rowcolor'];
   } else {
      $rowcolor = 'ffffff';
   }
   // default to showing the comment field (if it exists) next to the form field - this can be used to pass error messages
   // and other informational items such as help
   if (isset($adminsetup['showcomments'])) {
      $showcomments = $adminsetup['showcomments'];
   } else {
      $showcomments = 1;
   }
   $pkcol = $tableinfo['pk'];
   $outputformat = $tableinfo['outputformat'];
   switch($outputformat) {
      case 'row':
      $rowopen = '';
      $headsep = '';
      $rowclose = '<br>';
      $tailsep = '';
      $labelsep = '';
      $labelitemdelim = '';
      $separator = "&nbsp;";
      $radioopen = '<table><tr>';
      $radioclose = '</tr></table>';
      break;

      case 'tablerow':
      $rowopen = '<table><tr>';
      $headsep = "<td bgcolor='$rowcolor' valign=$valign>";
      $separator = '';
      $labelsep = '<br>';
      $labelitemdelim = '';
      $tailsep = '</td>';
      $rowclose = '</tr></table>';
      $radioopen = '<table><tr>';
      $radioclose = '</tr></table>';
      break;

      case 'mapmatrix':
      $rowopen = '<tr>';
      $headsep = "<td bgcolor='$rowcolor' valign=$valign>";
      $separator = '';
      $labelsep = '<br>';
      $labelitemdelim = '';
      $tailsep = '</td>';
      $rowclose = '</tr>';
      $radioopen = '';
      $radioclose = '';
      break;

      default:
      $rowopen = '';
      $headsep = '';
      $rowclose = '';
      $tailsep = '';
      $labelitemdelim = '';
      $labelsep = '';
      $separator = '<BR>';
      break;

   }

   $pkvalue = $thisrecord[$pkcol];
   if ($multiform) {
      if (!($multiformindex === NULL)) {
         $pkvalue = $multiformindex;
      }
   }
   if ($debug) {
      $formHTML .= "<br>All Admin setup info:<br>";
      //$formHTML .= print_r($adminsetup,1);
      $formHTML .="<br>Column setup info:<br>";
      //$formHTML .= print_r($colinfo,1);
      $formHTML .="<br>Table setup info for:<br>";
      //$formHTML .= print_r($tableinfo,1);
      $formHTML .="<br>Record Data:<br>";
      //$formHTML .= print_r($thisrecord,1);
   }

   $formHTML .="$rowopen";

   $outobject->append($formHTML);
   $formFields = array();

   foreach ($columns as $thiscol) {

      # create a blank formHTML variable to stash this column in,
      # at the end of the loop, we will output this HTML to the output object
      # which will then be rendered at the end, and returned to the user or printed
      $formHTML = '';

      $isinadminsetup = in_array($thiscol, array_keys($colinfo));
      if ($debug) {
         $formHTML .="Found $thiscol, $ismissing<br>";
      }
      if ($isinadminsetup) {
         $thisadmin = $colinfo[$thiscol];
      } else {
         $thisadmin = array();
      }
      if (isset($thisadmin['label'])) {
         $thislabel = $thisadmin['label'];
      } else {
         $thislabel = '';
      }
      // put quotes around the value?? should do this by default
      if (isset($thisadmin['quote_values'])) {
         $quote_values = $thisadmin['quote_values'];
      } else {
         $quote_values = 1;
      }
      if (isset($thisadmin['prefix'])) {
         $prefix = $thisadmin['prefix'];
      } else {
         $prefix = '';
      }
      if (isset($thisadmin['visible'])) {
         $thisvisible = $thisadmin['visible'];
      } else {
         $thisvisible = 0;
      }
      if (isset($thisadmin['showchecklabels'])) {
         $showchecklabels = $thisadmin['showchecklabels'];
      } else {
         $showchecklabels = 0;
      }
      if (isset($thisadmin['params'])) {
         $thisparams = $thisadmin['params'];
      } else {
         $thisparams = '';
      }
      if (isset($thisadmin['type'])) {
         $thistype = $thisadmin['type'];
      } else {
         $thistype = '';
      }
      if (isset($thisadmin['width'])) {
         $thiswidth = $thisadmin['width'];
      } else {
         $thiswidth = 10;
      }
      if (isset($thisadmin['maxlength'])) {
         $thismaxlength = $thisadmin['maxlength'];
      } else {
         $thismaxlength = -1;
      }
      if (isset($thisadmin['onchange'])) {
         $thisonchange = $thisadmin['onchange'];
      } else {
         $thisonchange = '';
      }
      if (isset($thisadmin['readonly'])) {
         $readonly = $thisadmin['readonly'];
      } else {
         $readonly = 0;
      }
      if (isset($thisadmin['mandatory'])) {
         $thismandatory = $thisadmin['mandatory'];
      } else {
         $thismandatory = 0;
      }
      // if this setting is passed in, we will set an "id" parameter in the HTML tag
      if (isset($thisadmin['domid'])) {
         $thisdomid = $thisadmin['domid'];
      } else {
         $thisdomid = '';
      }
      // if this setting is passed in, we will set an "id" parameter in the HTML tag
      if (isset($thisadmin['comments'])) {
         $thiscomments = $thisadmin['comments'];
      } else {
         $thiscomments = '';
      }
      
      if (isset($thisadmin['tab'])) {
         $outobject->current_tab = $thisadmin['tab'];
      }
      
      #$formHTML .="<br>Variable Read Only:$readonly ";
      #$formHTML .="<br>Form Disabled: $disabled ";
      $readonly = $readonly | $disabled;
      #$formHTML .="<br>Result:$readonly ";
      $thisvalue = $thisrecord["$thiscol"];

      # check to see if this table is to be presented in a multiple record edit form
      # if so, change the column name to a gridded format
      # we still have to preserve the column names without the [] because we need to be able to use the 
      # substitution routines
      $colname = $thiscol;
      if ($multiform) {
         # for some reason, a multi-form, which adds a '[]' on the end of the name, screws up
         # a multiselect list, because it results in a notation like [0][], so, we preserve the
         # base variable name for use in the multiselect component of a multi-form
         $multiformcol = $thiscol;
         $thiscol = $thiscol . "[" . "$pkvalue" . "]";
      } else {
         $multiformcol = $thiscol;
      }

      if ($debug) {
         $formHTML .="<br>Name: $thiscol, Value: $thisvalue, Type: $thistype, Label: $thislabel, Params: $thisparams, Visible: $thisvisible";
         $formHTML .="<br>Admin Info:$thisadmin";
         $formHTML .= print_r($thisadmin, 1);
      }

      $formHTML .="$headsep";
      $thisHTML = '';
      $formFields['fields'][$colname] = '';
      $formFields['labels'][$colname] = '';
      if ($thisvisible ) {

         if ($showlabels) {
            $formHTML .="<b>$thislabel$labelitemdelim</b>$labelsep";
         }
         $formFields['labels'][$colname] = "<b>$thislabel$labelitemdelim</b>";

         switch ($thistype) {


            case 1:
            # text field
               list($height) = explode(':', $thisparams);
               # we actually will show this if the disabled flag is set, since this will cause the field to be shown,
               # but NOT to return a form variable - just read-only means that we will print a hidden field
               if ( (!$readonly) or ($disabled)) {
                  if ($height > 1) {
                     $thisHTML .= showTextArea($thiscol,$thisvalue,$thiswidth,$height,'',1, $disabled, $thismaxlength, $thisonchange);
                  } else {
                     $thisHTML .= showWidthTextField($thiscol,$thisvalue,$thiswidth,'',1, $disabled, $thismaxlength, $thisonchange);
                  }
               } else {
                  $thisHTML .= "$thisvalue";
                  $thisHTML .= showHiddenField($thiscol,$thisvalue,1);
               }
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
               $formFields['print'][$colname] = $thisvalue;
            break;

            case 3:
            //$debug = 1;
            // select list
               list($listtable, $listpkcol, $listcols, $sortcol, $slabels, $extrawhere, $rows, $tbl_type) = array_pad(explode(':', $thisparams),6,'');
               //error_log("explode result: ($listtable, $listpkcol, $listcols, $sortcol, $slabels, $extrawhere, $rows) ");
               if ($debug) {
                  $formHTML .= "<br> Param string: $thisparams<br>";
                  $formHTML .= "<br> the extra where clause: $extrawhere<br>";
               }
               if (!$rows) {
                  // default to single select, not multi
                  $rows = 1;
               }
               # check to see if the list table is a CSV, if so, we need to handle it specially
               $listarray = explode(",", $listtable);

               $keyvaltest = explode("|", $listarray[0]);
               if ($debug) {
                  $thisHTML .= print_r($listarray,1);
                  $thisHTML .= print_r($keyvaltest,1);
                  $thisHTML .= print_r($listarray[0],1);
               }
               if ( (count($keyvaltest) > 1) and !($tbl_type == 'sql') ) {
                  $listtable = array();
                  $k = 0;
                  foreach($listarray as $thispair) {
                     list($key, $value) = explode("|", $thispair);
                     $listtable[$k][$listpkcol] = $key;
                     $listtable[$k][$listcols] = $value;
                     if ($debug) {
                        $thisHTML.= "Pair $k: $key $value <br>";
                     }
                     $k++;
                  }
               }
               //$debug=1;
               #$formHTML .= "$thiscol, $listcols, $listpkcol <br>";
               $textonly = 0;
               if ($readonly and !$disabled) {
                  $textonly = 1;
                  $thisHTML .= showHiddenField($thiscol,$thisvalue,1);
               }
               //$thisHTML .= showList($dbobj, $thiscol, $listtable, $listcols, $listpkcol, $extrawhere, $thisvalue, $debug, 1, $readonly, $textonly);
               $thisHTML .= showActiveList($dbobj, $thiscol, $listtable, $listcols, $listpkcol, $extrawhere,$thisvalue, $thisonchange, $sortcol, $debug, 1, $readonly, $thisdomid, $textonly);
               //$debug = 0;
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
               // show text only version here
               $print_str .= showActiveList($dbobj, $thiscol, $listtable, $listcols, $listpkcol, $extrawhere,$thisvalue, '', $sortcol, $debug, 1, 1, $thisdomid, 1);
               $formFields['print'][$colname] = $print_str;
            break;

            case 5:
            # map table
               list($localkeycol,$fortable,$maptable,$mapkeycol,$fkeycol,$mapfkeycol,$listcols,$showlab,$sortcol) = explode(":",$thisparams);
               $lkvalue = $thisrecord[$localkeycol];
               $getlistsql = "select $listcols from $fortable where $maptable.$mapkeycol = '$lkvalue' and $maptable.$mapfkeycol = $fortable.$fkeycol";
               if ($sortcol <> "") { $getlistsql .= " order by ".$sortcol; }
               if ( ($debug) ) {
                  $thisHTML .="$getlistsql<br>";
                  #print_r($thisadmin);
               }
               #$this->debug = 1;
               $dbobj->querystring = $getlistsql;
               $dbobj->debug = $debug;
               $dbobj->showlabels = $showlab;
               $dbobj->performQuery();
               #$listobject->adminview = 1;
               $thismapval = $dbobj->getRecordValue(1,$listcols);

               if ($readonly) {
                  $thisHTML .="$thismapval ";

               } else {
                  $thisHTML .= showList($dbobj, $thiscol, $fortable, $listcols, $fkeycol, '', $thismapval, $this->debug, 1, $disabled);
               }

               #$this->debug = 0;
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
               $formFields['print'][$colname] = $thismapval;
            break;

            case 8: # scientific notation
               list($decimalplaces) = explode(":",$thisparams);
               if ($$decimalplaces == '') { $decimalplaces = 0; }
               $fvalue = sciFormat($thisvalue,$decimalplaces);
               $thisHTML .="$fvalue";
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
               $formFields['print'][$colname] = $thisHTML;

            break;

            case 9: # number format
               # do NOT format a variable for edit, since this would effectively round it
               # unless it is READ-ONLY, then that is fine
               #$fvalue = number_format($thisvalue, 2);
               list($decimalplaces) = explode(":",$thisparams);
               if (!$readonly) {
                  $thisHTML .= showWidthTextField($thiscol,$thisvalue,$thiswidth, '',1);
               } else {
                  $fvalue = number_format($thisvalue, $decimalplaces);
                  $thisHTML .="$fvalue";
                  if (!$disabled) {
                     $thisHTML .= showHiddenField($thiscol,$thisvalue, 1);
                  }
               }
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
               $formFields['print'][$colname] = number_format($thisvalue, $decimalplaces);
            break;

            case 10: # currency format
               list($decimalplaces) = explode(":",$thisparams);
               if ($decimalplaces == '') { $decimalplaces = 2; }
               $fvalue = number_format($thisvalue, $decimalplaces);
               $thisHTML .="\$$fvalue";
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
               $formFields['print'][$colname] = $thisHTML;
            break;

            case 14: # multi select list
            list($listpkcol,$local2,$ftable,$foreign1,$foreign2,$listcols,$sortcol,$showlabels,$numrows) = explode(":",$thisparams);
               # check to see if the list table is a CSV, if so, we need to handle it specially
               //$debug = 1;
               $listarray = explode(",", $ftable);
               $keyvaltest = explode("|", $listarray[0]);
               if ($debug) {
                  $thisHTML .= print_r($listarray,1);
                  $thisHTML .= print_r($keyvaltest,1);
                  $thisHTML .= print_r($listarray[0],1);
                  $thisHTML .= print_r($thisvalue,1);
               }
               if (count($keyvaltest) > 1) {
                  $listtable = array();
                  $k = 0;
                  foreach($listarray as $thispair) {
                     list($key, $value) = explode("|", $thispair);
                     $listtable[$k][$listpkcol] = $key;
                     $listtable[$k][$listcols] = $value;
                     if ($debug) {
                        $thisHTML.= "$k: $key -> $value ";
                     }
                     $k++;
                  }
                  if (is_array($thisvalue)) {
                     $colvalues = join(',', $thisvalue);
                  } else {
                     $colvalues = $thisvalue;
                  }
                  if ($debug) {
                     $thisHTML.= "<br>Table:" . print_r($listtable,1) . "<br>";
                  }

                  $thisHTML .= showMultiList2($listtable,$multiformcol, $listtable, $listpkcol, $listcols, $colvalues, '', $sortcol, $debug, $numrows, 1, $disabled);
               } else {
                  $thisHTML .= showMultiList2($dbobj,$multiformcol, $ftable, $listpkcol, $listcols, $thisvalue, '', $sortcol, $debug, $numrows, 1, $disabled);
               }
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
               //$debug = 0;
            break;

            case 15: # edit link
               list($editpage,$extravars,$targetframe,$linktext,$urlextras) = explode(":",$thisparams);

               $extralist = explode(',',$extravars);
               $extraurl = '';
               $udel = '';
               foreach ($extralist as $extrafield) {
                  $thisfield = $thisrecord[$extrafield];
                  $extraurl .= "$udel$extrafield=$thisfield";
                  $udel = '&';
               }

               if (strlen($urlextras) > 0) {
                  $extraurl .= "&$urlextras";
               }

               $thisHTML .="<a href='./$editpage?$extraurl&$pkcol=$pkvalue' target='$targetframe'>$linktext </a>";
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
            break;

            case 16:
               # multi key list
               list($listtable, $listpkcol, $seckeycol, $myseckeycol, $listcols, $sortcol, $slabels) = explode(':', $thisparams);
               $seckey = $thisrecord["$myseckeycol"];
               $whereclause = "$seckeycol = $seckey";
               $thisHTML .= showList($dbobj, $thiscol, $listtable, $listcols, $listpkcol, $whereclause, $thisvalue, $debug, 1, $disabled);
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
            break;

            case 17:
               list($foreigntable, $localkeycol, $foreignkeycol, $paramcol) = explode(':', $thisparams);
               $seckey = $thisrecord["$localkeycol"];
               $dbobj->querystring = "select $paramcol,$foreignkeycol from $foreigntable where $localkeycol = '$seckey'";
               $dbobj->performQuery();

               $paramcols = explode(",",$paramcol);

               foreach ($dbobj->queryrecords as $thisrec) {
                  foreach($paramcols as $paramcol) {
                     $pval = $thisrec["$paramcol"];
                     $kval = $thisrec["$foreignkeycol"];
                     $thisHTML .="<br>";
                     $thisHTML .= showWidthTextField("$paramcol$kval",$pval,$thiswidth, '',1, $disabled);
                     $thisHTML .="<br>";
                  }
               }
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
            break;

            case 18: 
            // TF selector
               $thisHTML .= showTFListType($thiscol,$thisvalue,1, '',1);
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
            break;

            case 19:
            // multi-check box
               list($lcommon, $fcommon, $mcommon, $maptable, $localkeycol, $localmapcol, $foreignmapcol, $foreigntable, $foreignkeycol, $paramcol) = explode(':', $thisparams);
               $commonkey = $thisrecord["$lcommon"];
               $localmap = $thisrecord["$localkeycol"];

               $dbobj->querystring = " ( select $paramcol,$foreignkeycol, ";
               $dbobj->querystring .= " 'checked' as checked from $foreigntable ";
               $dbobj->querystring .= " where $foreignkeycol in ";
               $dbobj->querystring .= " ( select $foreignmapcol from $maptable ";
               $dbobj->querystring .= " where $mcommon = '$commonkey' ";
               $dbobj->querystring .= " and $localmapcol = '$localmap' )";
               $dbobj->querystring .= "  and $fcommon = '$commonkey' ";
               $dbobj->querystring .= "  order by $paramcol ) ";
               $dbobj->querystring .= " UNION ( select $paramcol,$foreignkeycol, ";
               $dbobj->querystring .= " '' as checked from $foreigntable ";
               $dbobj->querystring .= " where $foreignkeycol not in ";
               $dbobj->querystring .= " ( select $foreignmapcol from $maptable ";
               $dbobj->querystring .= " where $mcommon = '$commonkey' and ";
               $dbobj->querystring .= " $localmapcol = '$localmap' )";
               $dbobj->querystring .= "  and $fcommon = '$commonkey' ";
               $dbobj->querystring .= "  order by $paramcol ) ";
               $dbobj->performQuery();

               #$thisHTML .="$dbobj->querystring<br>");

               $checkrecs = $dbobj->queryrecords;
               $labelrecs = $dbobj->queryrecords;

               $z = 1;
               $ro = '';
               $rc = '</td>';
               #$thisHTML .="$thisvalue $tailsep $headsep");
               if ($showchecklabels) {
                  # print a place holder if outputting the header of check columns
                  $thisHTML .=" &nbsp; $tailsep $headsep";
                  foreach ($labelrecs as $thislabelrec) {
                     $thislabel = $thislabelrec[$paramcol];
                     $thisHTML .="$ro";
                     $thisHTML .="<b>$thislabel </b>";
                     $thisHTML .="$rc";
                     $ro = '<td>';
                     $z++;
                  }
                  $thisHTML .="$rowclose";
                  $thisHTML .="$rowopen";
               }
               $ro = '';
               $rc = '</td>';
               $thisHTML .="$prefix $tailsep $headsep";
               foreach ($checkrecs as $thischeckrec) {
                  $thischeckval = $thischeckrec[$foreignkeycol];
                  $thischecked = $thischeckrec['checked'];
                  $thisHTML .="$ro";
                  $thisHTML .= showCheckBox("$thiscol" ."[$z]", $thischeckval, $thischecked, '', 1, $disabled);
                  $thisHTML .="$rc";
                  $ro = "<td bgcolor='$rowcolor'>";
                  $z++;
               }
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
            break;

            case 21:
            # password field
               $thisHTML .= showWidthPasswordField($thiscol,$thisvalue,$thiswidth,'', 1, $disabled);
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
            break;


            case 22:
               # fileNice file browser requires the fileNice library, i.e.,  include_once "fileNice/fileNice.php";
               if (is_object($fno)) {
                  list($fileTypesCSV) = explode(':', $thisparams);
                  $fno->silent = 1;
                  $fno->showFlickrForm = 0;
                  $fno->showSearchForm = 0;
                  $fno->showPrefsForm = 0;
                  $fno->showFilePath = 0;
                  $fno->restrictTypes = explode(',', $fileTypesCSV);
                  $thisHTML .= ": <font size=-1>" . showHiddenField($thiscol,$thisvalue,1) . $thisvalue . "</font>";
                  $thisHTML .= $fno->showFNBrowser(array(), array());
                  if ($debug) {
                     $thisHTML .= "File Types: $fileTypesCSV - " . print_r($fno->restrictTypes,1) . "<br>";
                  }
               } else {
                  $thisHTML .= "<b>Error:</b> File Nice Object must be included";
               }
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
            break;

            case 23:
            # radio button
               list($checkvalues,$radlabels,$delim) = explode(':', $thisparams);
               $checks = explode(',', $checkvalues);
               $radlabs = explode(',', $radlabels);
               if (strlen($delim) == 0) {
                  $delim = ' ';
               }
               $thisdel = '';
               foreach ($checks as $checkvalue) {
                  list($thischeck, $thislab, $onclick) = explode("|", $checkvalue);
                  $thisHTML .= $thisdel . showRadioButton($thiscol, $thischeck, $thisvalue, $onclick, 1, $disabled) . " " . $thislab;
                  $thisdel = $delim;
               }
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;

            break;

            case 26:
            # check box
               list($checkvalue, $delim, $uncheckedval) = explode(':', $thisparams);
               //$thisHTML .= $thisparams . " - $checkvalue, $delim, $uncheckedval <br>";
               if ( ($delim === NULL) or (strtolower($delim) == 'null') ) {
                  if (!($uncheckedval === NULL) ) {
                     $thisHTML .= showHiddenField($thiscol, $uncheckedval, 1);
                  }
                  $thisHTML .= showCheckBox($thiscol, $checkvalue, $thisvalue, '', 1, $disabled);
               } else {
                  $opairs  = explode(',', $checkvalue);
                  $options = array();
                  //$thisHTML .= print_r($opairs,1) . "<br>";
                  foreach ($opairs as $thispair) {
                     list($val, $lab) = explode("|", $thispair);
                     $options[] = array('option'=>$val, 'label'=>$lab);
                  }
                  //$thisHTML .= print_r($options,1) . "<br>";
                  $delim = $uncheckedval; // 3rd param is the printout delimiter in this case
                  $thisHTML .= showMultiCheckBox($colname, $options, $thisvalue, $delim, '', 1, $disabled);
               }
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;

            break;
            
            case 27: 
            // expandable matrix
               $innerHTML = '';
               // $formatted = , $formname = the name of this form
               $formname = $tableinfo['formName'];
               //$innerHTML = 'Expandable Matrix Entry Form';
               $matrix = $thisvalue;
               // this maintains backward compatibility because the "list(.." construct will parse the inputr array from RIGHT TO LEFT, thus if your list of variable names is longer than the number of items in the input array, you will have null values on the left hand side of the list
               if (count(explode(':', $thisparams)) == 4) {
                  list($fixed_cols, $fixed_rows, $valuetype, $cellsize) = explode(':', $thisparams);
                  $firstrow_ro = 0;
                  $firstcol_ro = 0;
               } else {
                  list($fixed_cols, $fixed_rows, $valuetype, $cellsize, $firstrow_ro, $firstcol_ro) = explode(':', $thisparams);
               }
               $rowfield = $colname . "_numrows";
               $colfield = $colname . "_numcols";
               if ($fixed_rows) {
                  $numrows = $fixed_rows;
               } else {
                  $numrows = $thisrecord[$rowfield]; // must be hidden field
               }
               if ($fixed_cols) {
                  $numcols = $fixed_cols;
               } else {
                  $numcols = intval( count($matrix) / $numrows);
               }
               if (!isset($thisrecord[$rowfield]) and !$fixed_rows) {
                  $numrows = intval( count($matrix) / $numcols);
               }
               // have we passed multiple values in for $firstrow_ro or $firstcol_ro??
               // if so, then we have the ability to specify multiple read-only rows and/or columns
               $row_ro = array_pad(array(), $numrows, 0);
               $ri = 0;
               foreach (explode(',', $firstrow_ro) as $thisrow_ro) {
                  $row_ro[$ri] = $thisrow_ro;
                  $ri++;
               }
               // do the same for columns, but with these we start our index at 1
               $col_ro = array_pad(array(), $numcols + 1, 0);
               $ci = 1;
               foreach (explode(',', $firstcol_ro) as $thiscol_ro) {
                  $col_ro[$ci] = $thiscol_ro;
                  $ci++;
               }
               //error_log("$colname Rows: " . print_r($row_ro,1));
               //error_log("$colname Cols: " . print_r($col_ro,1));
               
               $innerHTML .= showHiddenField($rowfield, $numrows, 1);
               $innerHTML .= showHiddenField($colfield, $numcols, 1);
               if ($debug) {
                  $innerHTML .= "<br>Rows = $numrows , Cols = $numcols, Cell Size = $cellsize <br>";
               }
               // Value Type Array / 1-column Lookup / 2-column Lookup  --  1st Lookup (matches 1st Column), 2nd Lookup (Matches 1st Row Values)
               $matrixname = "matrix_$thiscol";
               if ($debug) {
                  $innerHTML .= "Creating table named - '$matrixname'<br>";
               }
               // END - text2table section
               $innerHTML .= "<table id='$matrixname'>";
               $windex = 0;
               // calculate column widths
               $colwidths = array();
               for ($i = 0; $i < $numrows; $i++) {
                  for ($j = 1; $j <= $numcols; $j++) {
                     if (!isset($colwidths[$j])) {
                        $colwidths[$j] = $cellsize;
                     }
                     $cw = strlen($matrix[$windex]);
                     if ($cw > $colwidths[$j]) {
                        $colwidths[$j] = $cw;
                        error_log("Column $j width set to $cw");
                     }
                     $windex++;
                  }
               }
               // end get Column Widths
               $mindex = 0;
               $innerHTML .= "<tr>";
               for ($j = 1; $j <= $numcols; $j++) {
                  if (!$fixed_cols) {
                     $innerHTML .= "<td align=center><a onclick=\"var entity=this.parentNode || this.parentElement; var colno=entity.cellIndex + 1; deleteColumn('$formname','$matrixname', colno);  \" ><img src='/images/delete.gif' width=16 height=16></a></td>";
                  }
               }
               $innerHTML .= "</tr>";
               for ($i = 0; $i < $numrows; $i++) {
                  $innerHTML .= "<tr>";
                  for ($j = 1; $j <= $numcols; $j++) {
                     $style_str = '';
                     if (isset($colwidths[$j])) {
                        $cw = $colwidths[$j];
                     } else {
                        $cw = $cellsize;
                     }
                     switch ($valuetype) {
                        case 0:
                        //nothing to do, all columns are value
                        break;

                        case 1:
                        // grey the first column of each row to indicate that these are your key columns
                           if ($j == 1) {
                              $style_str = "style='background-color: #BEBEBE'";
                           }
                        break;

                        case 2:
                        // grey the first column of each row to indicate that these are your key columns
                           if ( ($j == 1) or ($i == 0)) {
                              $style_str = "style='background-color: #BEBEBE'";
                           }
                        break;
                     }
                     //if ( (($i == 0) and $firstrow_ro) or (($j == 1) and $firstcol_ro) )  {
                     if ( $row_ro[$i] or $col_ro[$j] )  {
                        $innerHTML .= "<td><input type=hidden $style_str SIZE=" . $cw . " name=$colname" . "[] value=\"" . $matrix[$mindex] . "\">";
                        $innerHTML .= "<b>" . $matrix[$mindex] . "</b></td>";
                     } else {
                        $innerHTML .= "<td><input type=text $style_str SIZE=" . $cw . " name=$colname" . "[] value=\"" . $matrix[$mindex] . "\"></td>";
                     }

                     
                     $mindex++;
                  }
                  if (!$fixed_rows) {
                     $innerHTML .= "<td align=center><a onclick=\"var cellobj=this.parentNode || this.parentElement; rowobj = cellobj.parentNode; tableobj = rowobj.parentNode; rowno =rowobj.rowIndex; tableobj.deleteRow(rowno);  \" ><img src='/images/delete.gif' width=16 height=16></a></td>";
                  }
                  $innerHTML .= "</tr>";
               }
               $innerHTML .= "</table>";
               if (!$fixed_cols or !$fixed_rows) {
                  $innerHTML .= "<br>";
               }
               if (!$fixed_cols) {
                  $innerHTML .= "<input type=\"button\" onclick=\"addColumn('$formname','$matrixname','<input type=text SIZE=" . $cellsize . " name=$colname" . "[]>'); incrementFormField('$formname', '$colfield', 1) ;\" value=\"Add column\">";
               }
               if (!$fixed_rows) {
                  $innerHTML .= "<input type=\"button\" onclick=\"cloneLastRow('$matrixname');  incrementFormField('$formname', '$rowfield', 1) ; \" value=\"Add row\">";
               }
               $formFields['fields'][$colname] = $innerHTML;
               $formHTML .= $innerHTML;
               $thisHTML .= $innerHTML;

            break;


            case -1:
            // just an HTML snippet, do nothing
               $thisHTML .= "$thisvalue";
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
            break;


            default:
               if (!$readonly) {
                  if ($debug) {
                     $thisHTML .= "<b>Variable type: </b>$thistype <br>";
                  }
                  $thisHTML .= showWidthTextField($thiscol,$thisvalue,$thiswidth,'',1, $disabled);
               } else {
                  $thisHTML .= "$thisvalue";
                  $thisHTML .= showHiddenField($thiscol,$thisvalue,1);
               }
               $formHTML .= $thisHTML;
               $formFields['fields'][$colname] = $thisHTML;
            break;
         }
         if ($mark_mandatory and $thismandatory and !($readonly)) {
            $formHTML .= $mandatory_text;
            $formFields['fields'][$colname] .= $mandatory_text;
         }
         if ($showcomments) {
            $formHTML .= $thiscomments;
            $formFields['fields'][$colname] .= $thiscomments;
         }
         $formHTML .="$separator";
      } else {
         if ($showmissing or ($isinadminsetup)) {
            $thisHTML .= showHiddenField($thiscol,$thisvalue, 1);
            $formHTML .= $thisHTML;
            $formFields['fields'][$colname] = $thisHTML;
         }
      }
      $formHTML .="$tailsep";

      $outobject->append($formHTML);
   }

   $finishHTML = '';
   if ($multiform and $tableinfo['showPlusMinus']) {
      $finishHTML .= "$headsep";
      $formName = $tableinfo['formName'];
      $adname = $tableinfo['adname'];
      $parentname = $tableinfo['parentname'];
      $childname = $tableinfo['childname'];
      //$finishHTML .= showGenericButton('+'."[$pkvalue]",'+', "xajax_formRowPlus(xajax.getFormValues(\"$formName\"),\"$formName\",\"$parentname\",\"$childname\",\"$adname\"); return false;", 1, $disabled);
      //$finishHTML .= showGenericButton('-'."[$pkvalue]",'-', " document.forms[\"$formName\"].elements.xajax_removeitem.value=$pkvalue ; xajax_formRowMinus(xajax.getFormValues(\"$formName\"),\"$childname\"); return false;", 1, $disabled);
      
      $finishHTML .= showGenericButton('+'."[$pkvalue]",'+', "formRowPlus(\"$parentname\",\"$childname\"); return false;", 1, $disabled);
      $finishHTML .= showGenericButton('-'."[$pkvalue]",'-', "formRowMinus(\"$parentname\",this.parentNode); return false;", 1, $disabled);
      $finishHTML .= "$tailsep";
   }
   $finishHTML .="$rowclose";

   $outobject->append($finishHTML);
   $outobject->formpieces = $formFields;
   #print("Fields gotten<br>");
   #print_r($formFields);

   $outobject->render();
   $formHTML = $outobject->innerHTML;
   if ($returnobject) {
      # send back the output object, which includes the for components
      return $outobject;
   } else {
      if ($silent) {
         return $formHTML;
      } else {
         print($formHTML);
      }
   }

} /* end showFormVars($listobject,$thisrecord,$adminsetup) */




function processFormVars($dbobj,$invalues,$adminsetup,$showlabels,$debug) {

   $tablename = $dbobj->tablename;
   $colinfo = $adminsetup['column info'];
   $tableinfo = $adminsetup['table info'];
   if (isset($tableinfo['table_name'])) {
      $tablename = $tableinfo['table_name'];
   }
   $columns = array_keys($colinfo);

   $pkcol = $tableinfo['pk'];

   if ($debug) {
      print("<br>All Admin setup info:<br>");
      print_r($adminsetup);
      print("<br>Admin setup info for $tablename:<br>");
      print_r($adminfo);
      print("<br>Column setup info:<br>");
      print_r($colinfo);
      print("<br>Table setup info for:<br>");
      print_r($tableinfo);
   }

   $updatequery = '';
   $vfields = '';
   $ufields = '';
   $qdelim = '';

   foreach ($columns as $thiscol) {

      $thisadmin = $colinfo[$thiscol];
      if (isset($invalues[$thiscol]) and !($thisadmin['disabled']) ) {
         if ($thiscol <> $pkcol) {
            $thisvalue = $invalues["$thiscol"];
            $thislabel = $thisadmin['label'];
            $thistype = $thisadmin['type'];
            $thisparams = $thisadmin['params'];
            $thisvisible = $thisadmin['visible'];
            $thiswidth = $thisadmin['width'];
            if (isset($thisadmin['maxlength'])) {
               $thismaxlength = $thisadmin['maxlength'];
            } else {
               $thismaxlength = -1;
            }
            if (!$thiswidth) { $thiswidth = 10; }


            #print(" $thiscol - $thisvalue <br>");

            if ($debug) {
               print("<br><br>Value: $thisvalue, Type: $thistype, Label: $thislabel, Params: $thisparams, Visible: $thisvisible");
               #print("<br>Admin Info:$thisadmin");
               #print_r($thisadmin);
            }

            switch ($thistype) {

               case 15: # a link variable, do nothing

                  $valstring = '';
                  $qstring = '';

               break;

               case 18:
                  list($maptable, $local1, $local2, $map1, $map2, $foreignmapcol, $foreigntable, $foreignkeycol, $paramcol, $ismulti, $numrows) = explode(':', $thisparams);
                  $key1 = $invalues["$local1"];
                  $key2 = $invalues["$local2"];

                  $dbobj->querystring = " delete from $maptable where $map1 = '$key1' and $map2 = '$key2' ";
                  #print("$dbobj->querystring <br>");

                  $dbobj->performQuery();

                  foreach ($thisvalue as $mapval) {
                     $dbobj->querystring = " insert into $maptable ($map1, $map2, $foreignmapcol)  values ('$key1', '$key2', '$mapval') ";
                     #print("$dbobj->querystring <br>");
                     $dbobj->performQuery();
                  }

                  $valstring = '';
                  $qstring = '';

               break;

               default:
                  if ( ($thismaxlength > 0) and (strlen($thisvalue) > $thismaxlength) ) {
                     $thisvalue = substr($thisvalue, 1, $thismaxlength);
                  }
                  $valstring = "'" . $dbobj->escapeString($thisvalue) . "'";
                  #$valstring = "'$thisvalue'";
                  $qstring = "$thiscol = ";
               break;
            }



            if ($qstring <> '') {
               $updatequery .= "$qdelim $qstring";
               $updatequery .= "$valstring";
               $qdelim = ',';
            }

         }

      }

   }
   if ($debug) { print("$updatequery<br>"); }

   $uparray = array($updatequery, $ufields,  $vfields);
   return $uparray;

} /* end processFormVars */

function splitMultiFormVars($invalues, $columns, $pkcol) {
   # then we have multiple column values
   # we use the array keys, since if this is an edit, the array keys will
   # be equal to array values for the PK, but if this is
   # an insert the array keys will hold dummy values to key the multiple
   # insert entries, while the value will be '' or the default blank value
   $inmulti = array();
   foreach (array_keys($invalues[$pkcol]) as $thispk) {
      reset($columns);
      $multival = array();
      foreach ($columns as $thiscol) {
         if (isset($invalues[$thiscol])) {
            $thismulti = $invalues[$thiscol];
            $multival[$thiscol] = $thismulti[$thispk];
         }
      }
      array_push($inmulti, $multival);
   }
   return $inmulti;
}

function processMultiFormVars($dbobj,$invalues,$adminsetup,$isnewrecord,$debug, $nullpk = -1, $strictnull = 0) {

   # $nullpk = the value that indicates an insert form, if needed for a multiple insert
   $tablename = $dbobj->tablename;
   $colinfo = $adminsetup['column info'];
   $tableinfo = $adminsetup['table info'];
   $columns = array_keys($colinfo);
   if (isset($tableinfo['table_name'])) {
      $tablename = $tableinfo['table_name'];
   }

   $nullvals = array(1=>'NULL', 3=>'NULL', 9=>'NULL', 14=>'NULL', 20=>'NULL');

   $pkcol = $tableinfo['pk'];

   if ($debug) {
      #$debugstr .= "<br>All Admin setup info:<br>";
      #$debugstr .= print_r($adminsetup,1);
      #$debugstr .= "<br>Admin setup info for $tablename:<br>";
      #$debugstr .= print_r($adminfo,1);
      $debugstr .= "<br>Column setup info:<br>";
      $debugstr .= print_r($colinfo,1);
      #$debugstr .= "<br>Table setup info for:<br>";
      #$debugstr .= print_r($tableinfo,1);
   }

   $updatequery = '';
   $qdelim = '';
   $collist = '';
   $error = 0;
   $errorstring = '';
   $mapqueries = array();

   # need to determine if there are multiple copies
   # must have pk column for this to work
   $inmulti = array();
   $outmulti = array();
   $multiassoc = array();

   if (isset($invalues[$pkcol])) {
      if ( is_array($invalues[$pkcol]) ) {
            $inmulti = splitMultiFormVars($invalues, $columns, $pkcol);
            #print_r($multival);
      } else {
         array_push($inmulti, $invalues);
      }
   } else {
      $errorstring .= "<b>Warning: </b> PK field '$pkcol' not included.<br>";
   }

   foreach ($inmulti as $invalues) {
      reset($columns);
      $vlist = '';
      $clist = '';
      $valcheck = '';
      $updatequery = '';
      $validcol_updatequery = ''; // for valid columns only
      $validcol_checkdelim = '';
      $validcol_delim = '';
      $mapqueries = '';
      $qdelim = '';
      $valcheckdelim = '';
      $udelim = '';
      $outvalues = array();
      $outspecial = array();
      $column_error = array();
      $column_error_msg = array();
      foreach ($columns as $thiscol) {
         $mandatory_missing = 0;
         
         $checkpass = substr($thiscol,strlen($thiscol)-13,12);
         #print("ColVal: $invalues[$thiscol]<br>");
         if ( $colinfo[$thiscol]['mandatory'] == 1 ) {
            if ( !isset($invalues[$thiscol]) ) {
               $mandatory_missing = 1;
               $error = 1;
               $errorstring .= "<b>Error: </b> Mandatory field '$thiscol' not included.<br>";
               $column_errors[$thiscol]['error'] = 1;
               $column_errors[$thiscol]['error_msg'] = "<b>Error: </b> Mandatory field '$thiscol' not included.";
            } else {
               if (strlen($invalues[$thiscol]) == 0 ) {
                  $mandatory_missing = 1;
                  $error = 1;
                  $errorstring .= "<b>Error: </b> Mandatory field '$thiscol' must not be blank.<br>";
                  $column_errors[$thiscol]['error'] = 1;
                  $column_errors[$thiscol]['error_msg'] = "<b>Error: </b> Mandatory field '$thiscol' must not be blank.";
               }
            }
         }

         if (isset($invalues[$thiscol]) and ($checkpass <> '_oldpassword') ) {

            $thisvalue = $invalues[$thiscol];
            $debugstr .= "Debug: $thiscol : $thisvalue <br>";
            if ( ($thiscol == $pkcol) ) {
               $pkval = $thisvalue;
               if ( ($thisvalue == '') or ($thisvalue == $nullpk) ) {
               # this is an insert, do not include pk if not set
               # this assumes that if an insert is requested without a pk
               # value, that the pk is an auto generated key
               } else {
                  $pkclause = "WHERE \"$pkcol\" = '$thisvalue'";
               }
            } else {

               $clist .= "$qdelim \"$thiscol\"";
               $thisadmin = $colinfo[$thiscol];
               $thislabel = $thisadmin['label'];
               $thistype = $thisadmin['type'];
               $thisparams = $thisadmin['params'];
               $thisvisible = $thisadmin['visible'];
               $thiswidth = $thisadmin['width'];
               if (isset($thisadmin['readonly'])) {
                  $readonly = $thisadmin['readonly'];
               } else {
                  $readonly = 0;
               }
               if (isset($thisadmin['maxlength'])) {
                  $thismaxlength = $thisadmin['maxlength'];
               } else {
                  $thismaxlength = -1;
               }
               if (isset($thisadmin['nullval'])) {
                  $nullval = $thisadmin['nullval'];
               } else {
                  if (in_array($thistype, array_keys($nullvals))) {
                     $nullval = $nullvals[$thistype];
                  } else {
                     $nullval = 'NULL';
                  }
               }
               if (!$readonly) {
                  $updatequery .= "$udelim\"$thiscol\" = ";
               } else {
                  $debugstr .= "$thiscol is read-only <br>";
               }
               if (!$thiswidth) { $thiswidth = 10; }

               #print(" $thiscol - $thisvalue <br>");

               if ($debug) {
                  $debugstr .= "<br>Value: $thisvalue, Type: $thistype, Label: $thislabel, Params: $thisparams, Visible: $thisvisible";
                  $debugstr .= "<br>Admin Info:$thisadmin";
                  $debugstr .= print_r($thisadmin, 1);
               }

               switch ($thistype) {

                  case 15: # a link variable, do nothing

                  break;


                 case 14: # 2-column map
                  # multiple value lookup (aka multi select list)
                     list($local1,$local2,$ftable,$foreign1,$foreign2,$listcols,$sortcol,$showlabels) = explode(":",$params);
                     $l1 = $local1;
                     $l2 = $thisrow[$local2];
                     $getlistsql = "select $listcols from $ftable where ";
                     $getlistsql .= "$foreign1 = '$l1' and $foreign2 = '$l2'";
                     if ($sortcol <> "") { $getlistsql .= " order by ".$sortcol; }
                     #print("$local1,$local2: $getlistsql<br>");
                     //$listobject = new mysql_QueryObject();
                     $listobject = clone $dbobj;
                     $listobject->querystring = $getlistsql;
                     $listobject->tablename = $foreigntable;
                     $listobject->dbconn = $this->dbconn;
                     $listobject->showlabels = $showlabels;
                     $listobject->performQuery();
                     $listobject->showList();
                  break;

                 case 17: # 2-table map
                  # multiple value lookup (aka multi select list)
                     list($foreigntable, $localkeycol, $foreignkeycol, $paramcol) = explode(':', $thisparams);
                     $listar = explode(",",$listcols);
                     $maplist = join($listar,' a.');
                     $lkey = $thisrow[$localkey];

                     $selcols = join("','", $thisvalue);
                     $listobject->querystring = "select $paramcol,$foreignkeycol from $foreigntable where $localkeycol = '$seckey'";

                     $setlistsql = "update $foreigntable set $foreignkeycol = '$seckey' ";
                     $setlistsql .= " select $mapkey, '$lkey' ";
                     $setlistsql .= " from $ftable ";
                     $setlistsql .= " where $mapkey in ('$selcols') ";

                     arrray_push($mapqueries, $setlistsql);
                  break;

                  case 20:
                  # special numeric field
                  list($numtype,$p1,$p2,$p3,$p4) = explode(":",$thisparams);
                  switch ($numtype) {
                     case 1:
                     $outvalue = timeToSeconds($thisvalue);
                     $valstring = $outvalue;
                     break;

                     case 2:
                     $outvalue = timeToSeconds($thisvalue);
                     $valstring = $outvalue;
                     break;

                     default:
                     $valstring = $thisvalue;
                     break;
                  }
                  break;

                  case 21:
                     # password field
                     # check for hidden password duplicate
                     if (isset($invalues["$thiscol" . "_oldpassword"])) {
                        $oldpass = $invalues["$thiscol" . "_oldpassword"];
                     }
   #print("Old Pass: $oldpass  New Pass: $thisvalue <br>");
                     if ((!isset($oldpass)) or ($thisvalue == $oldpass)) {
                        $pwval = "'$thisvalue'";
                     } else {
                        $pwval = "password('$thisvalue')";
                    }
                    $valstring = "$pwval";
                  break;

                  case 24:
                     # geometry column
                     $wkt_geom = $thisvalue;
                     $geomtext = '';
                     $geompieces = explode('\(', $wkt_geom);
                     $innerHTML .= "Geom type: " . $geompieces[0] . "<br>";
                     switch ($geompieces[0]) {
                        case 'POINT':
                           $wkt_type = 1;
                           $geomtext = "GeomFromText('$thisvalue')";
                        break;

                        case 'LINE':
                           $wkt_type = 2;
                           $geomtext = "GeomFromText('$thisvalue')";
                        break;

                        case 'POLYGON':
                           $wkt_type = 3;
                           $geomtext = "GeomFromText('$thisvalue')";
                        break;

                        case 'MULTIPOLYGON':
                           $wkt_type = 3;
                           $geomtext = "Multi(GeomFromText('$thisvalue'))";
                        break;

                        case 'MULTILINE':
                           $wkt_type = 4;
                           $geomtext = "Multi(GeomFromText('$thisvalue'))";
                        break;

                        default:
                        # geometry is not WKT, either it is original binary or type not understood,
                        # assume it is an original Binary value
                           $wkt_type = 0;
                           $geomtext = $thisvalue;
                        break;
                     }
                     $valstring = "'$geomtext'";
                     if ($wkt_type <> 0) {
                        # if this is not a binary value (no change), we try to set and SRID if provided
                        if (isset($tableinfo['srid'])) {
                           if (strlen($tableinfo['srid']) > 0) {
                              $valstring = 'setSRID(' . $geomtext . ',' . $tableinfo['srid'] . ')';
                           }
                        }
                     }
                  break;

                  case 25:
                     # lat/lon field
                     $latlon = $thisvalue;
                     # need to be able to handle entries of the form:
                     # DD.dddd - is given in decimal degress, no action
                     # DD MM SS - is given in degrees minutes seconds with space delimiter
                     # DD MM'SS" - is given in degrees minutes seconds with traditional delimiter
                     $geomtext = '';
                     #$reg = "([0-8][0-9]|[9][0])°[ ][0-9][0-9][ ]";
                     $reg = "([0-8][0-9]|[9][0])[ ][0-9][0-9][ ]'[ ][0-9][0-9][ ]\"";
                     $valstring = floatval(str_replace(',','',$thisvalue));
                     
                  break;
 
                  case 27:
                     // need to handle the extra variables numrows and numcols that might be set here
                     $rowcol = $thiscol . "_numrows";
                     $colcol = $thiscol . "_numcols";
                     $outspecial[$thiscol]['numrows'] = $invalues[$rowcol];
                     $outspecial[$thiscol]['numcols'] = $invalues[$colcol];
                     $fvalue = $thisvalue;
                  break;
                  
                  case 9:
                     # numerical field, let's check to make sure that some do-gooder didn't try and insert
                     # things like commas at the thousands place
                     $stv = str_replace(',','',$thisvalue);
                     $valstring = floatval($stv);
                     if (!(is_numeric($stv)) ) {
                        // this means the floatval somehow changed the field, so pop an error message
                        $error = 1;
                        $column_errors[$thiscol]['error'] = 1;
                        $column_errors[$thiscol]['error_msg'] = "<b>Error: </b> In column , '$thiscol', value '$thisvalue' is not a valid number.";
                        $errorstring .= "<b>Error: </b> In column , '$thiscol', '$thisvalue' is not a valid number.";
                     }
                  break;

                  default:
                  // this is case 1 (text) and all others coincide with this
                  $fvalue = $thisvalue;
                     if ( ($thismaxlength > 0) and (strlen($thisvalue) > $thismaxlength) ) {
                        $fvalue = substr($thisvalue, 1, $thismaxlength);
                        $error = 1;
                        $column_errors[$thiscol]['error'] = 1;
                        $column_errors[$thiscol]['error_msg'] = "<b>Error: </b> Length of '$thiscol' exceeds $thismaxlength chars.";
                        $errorstring .= "<b>Error: </b> Length of '$thiscol' exceeds $thismaxlength chars.";
                     }
                     $valstring = "'" . $dbobj->escapeString($fvalue) . "'";
                     #$valstring = "'$thisvalue'";
                  break;
               }

               if ( ( ($valstring == "''") or ($valstring == '')) and $strictnull) {
                  $valstring = $nullval;
               }

               if (!$readonly) {
                  $updatequery .= "$valstring";
                  $rwvlist .= "$udelim $valstring";
                  $rwclist .= "$udelim \"$thiscol\"";
                  $udelim = ',';
                  $valcheck .= " $valcheckdelim \"$thiscol\" = $valstring";
                  $valcheckdelim = 'AND';
                  if (!$column_errors[$thiscol]) {
                     $validcol_updatequery .= " $validcol_delim \"$thiscol\" = $valstring";
                     $validcol_delim = ',';
                     $validcol_checkdelim = 'AND';
                  }
               }
               $vlist .= "$qdelim $valstring";
               $outvalues[$thiscol] = $thisvalue;

               $qdelim = ',';
            }

         }
      }
      # associative array output
      $outassoc = array();
      $outassoc['updateintro'] = "UPDATE $tablename SET ";
      $outassoc['updatequery'] = $updatequery;
      $outassoc['updateclause'] = $pkclause;
      $outassoc['validcol_updatequery'] = $validcol_updatequery;
      $outassoc['pkclause'] = $pkclause;
      $outassoc['updatesql'] = $updatesql;
      $outassoc['valcheck'] = $valcheck;
      $outassoc['columns'] = $clist;
      $outassoc['values'] = $vlist;
      $outassoc['rwclist'] = $rwclist;
      $outassoc['rwvlist'] = $rwvlist;
      $outassoc['mapqueries'] = $mapqueries;
      $outassoc['outmulti'] = $outmulti;
      $outassoc['outmulti_assoc'] = $outmulti;
      $outassoc['outvalues'] = $outvalues;
      $outassoc['outspecial'] = $outspecial;
      $outassoc['error'] = $error;
      $outassoc['errormesg'] = $errorstring;
      $outassoc['column_errors'] = $column_errors;
      array_push($multiassoc, $outassoc );
      array_push($outmulti, array($updatequery,$clist,$vlist,$mapqueries, $pkval, $outvalues, $invalues) );
      if ($debug) {
         $debugstr .= "$updatequery<br>";
      }
      $updatesql = "UPDATE $tablename SET $updatequery $pkclause";
      if ($debug) {
         $debugstr .= "$updatesql<br>";
      }
    #  print("<br>Debug: ");
    #  print_r($outmulti);
   } /* end $inmulti loop */
   #return array($updatequery,$clist,$vlist,$mapqueries,$outmulti);
   # set up array for output
   $outarray = array();
   $outarray['updateintro'] = "UPDATE $tablename SET ";
   $outarray['updatequery'] = $updatequery;
   $outarray['updateclause'] = $pkclause;
   $outarray['validcol_updatequery'] = $validcol_updatequery;
   $outarray['pkclause'] = $pkclause;
   $outarray['updatesql'] = $updatesql;
   $outarray['valcheck'] = $valcheck;
   $outarray['columns'] = $clist;
   $outarray['values'] = $vlist;
   $outarray['rwclist'] = $rwclist;
   $outarray['rwvlist'] = $rwvlist;
   $outarray['insertsql'] = "INSERT INTO $tablename ( $rwclist ) VALUES ( $rwvlist ) ";
   $outarray['mapqueries'] = $mapqueries;
   $outarray['outmulti'] = $outmulti;
   $outarray['outmulti_assoc'] = $multiassoc;
   $outarray['outvalues'] = $outvalues;
   $outarray['outspecial'] = $outspecial;
   $outarray['error'] = $error;
   $outarray['errormesg'] = $errorstring;
   $outarray['debugstr'] = $debugstr;
   $outarray['column_errors'] = $column_errors;
   return $outarray;

} /* end processMultiFormVars */


function showTextField($fieldname,$fieldvalue, $onclick='', $silent=0) {
   #$innerHTML = "<input type=text name=$fieldname value='$fieldvalue'>";
   $innerHTML = "<input type=text name=$fieldname value=\"$fieldvalue\">";
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function showTextArea($fieldname,$fieldvalue,$fieldwidth, $fieldheight, $onclick='', $silent=0, $disabled=0, $maxlength=-1, $onchange='') {
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   $maxstr = '';
   if ($maxlength > 0) {
      $maxstr = "size=$maxlength";
   }
   $innerHTML = "<textarea name=$fieldname cols=$fieldwidth rows=$fieldheight $maxstr $distext >$fieldvalue</textarea>";
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function showWidthTextField($fieldname,$fieldvalue,$fieldwidth, $onclick='', $silent=0, $disabled=0, $maxlength=-1, $onchange='') {
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   $maxstr = '';
   if ($maxlength > 0) {
      $maxstr = "maxlength=$maxlength";
   }
   
   $chstr = '';
   if ($onchange <> '') {
      if (strpos($onchange, "'")) {
         $q = "\"";
      } else {
         $q = "'";
      }
      $chstr = "onChange=$q$onchange$q";
   }
   #$innerHTML = "<input type=text name=$fieldname value='$fieldvalue' size=$fieldwidth $distext>";
   $innerHTML = "<input type=text name=$fieldname value=\"$fieldvalue\" size=$fieldwidth $maxstr $chstr $distext>";
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function showWidthPasswordField($fieldname,$fieldvalue,$fieldwidth, $onclick='', $silent=0, $disabled=0) {
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   $innerHTML = "<input type=password name=$fieldname value='$fieldvalue' size=$fieldwidth $distext>";
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function showHiddenField($fieldname,$fieldvalue, $silent=0, $fieldid='') {
   $idstring = '';
   if (strlen(rtrim(ltrim($fieldid))) > 0) { 
      $cleanid = rtrim(ltrim($fieldid));
      $idstring = "id=\"$cleanid\"";
   }
   $innerHTML = "<input type=hidden name=$fieldname value='$fieldvalue' $idstring>";
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function showCheckBox($fieldname, $checkvalue, $fieldvalue, $onclick='', $silent=0, $disabled=0) {
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   if (!is_array($fieldvalue)) {
      // check for array, otherwise, just do a straight compare
      if ($fieldvalue == $checkvalue) {
         $checked = 'checked';
      } else {
         $checked = '';
      }
   } else {
      if (in_array($checkvalue, $fieldvalue)) {
         $checked = 'checked';
      } else {
         $checked = '';
      }
   }
   $innerHTML = "<input type=checkbox name=$fieldname value='$checkvalue' $checked onClick=\"$onclick\" $distext>";
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function showMultiCheckBox($fieldbase, $options, $values, $fielddelim, $onclick='', $silent=0, $disabled=0) {
   # field of name $fieldbase[0-N]
   $i = 0;
   $thisdelim = '';
   $innerHTML = '';
   foreach ($options as $thisoption) {
      $i++;
      $fieldvalue = '';
      #if (isset($thisoption['option'])) {
      if (is_array($thisoption)) {
         #expect a key=>value pair
         $coption = $thisoption['option'];
         $clabel = $thisoption['label'];
      } else {
         $coption = $thisoption;
         $clabel = $thisoption;
      }

      if (in_array($coption, $values)) {
         $fieldvalue = $coption;
      }
      $innerHTML .= "$thisdelim ";
      $innerHTML .= showCheckBox($fieldbase . '[' . $i .']', $coption, $fieldvalue, $onclick, 1, $disabled);
      $innerHTML .= " $clabel";
      $thisdelim = $fielddelim;
   }
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function showGenericButton($fieldname,$fieldvalue, $onclick='', $silent=0, $disabled=0) {
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   $innerHTML = "<input type=button name='$fieldname' id='$fieldname' value='$fieldvalue'";
   if (strlen($onclick) > 0) {
      $innerHTML .= "onClick='$onclick'";
   }
   $innerHTML .= "$distext>";
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function showSubmitButton($fieldname,$fieldvalue, $onclick='', $silent=0, $disabled=0) {
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   $innerHTML = "<input type=submit name=$fieldname value='$fieldvalue'";
   if (strlen($onclick) > 0) {
      $innerHTML .= "onClick='$onclick'";
   }
   $innerHTML .= "$distext>";
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function showImageButton($fieldname, $fieldvalue, $imageurl, $onclick='', $silent=0) {
   $innerHTML = "<INPUT type='image' name='$fieldname' value= '$fieldvalue' src='$imageurl' border='0'>";
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function showRadioButton($fieldname, $checkvalue, $fieldvalue, $onclick='', $silent=0, $disabled=0, $fieldid='') {
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   if ($fieldvalue == $checkvalue) {
      $checked = 'checked';
   } else {
      $checked = '';
   }
   if (strlen(rtrim(ltrim($fieldid))) > 0) { 
      $cleanid = rtrim(ltrim($fieldid));
      $idstring = "id=\"$cleanid\"";
   }
   $innerHTML = "<INPUT TYPE=RADIO NAME='$fieldname' VALUE='$checkvalue' onClick='$onclick' $distext $idstring $checked>";
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function showTFList($fieldname,$fieldvalue, $onchange='', $silent=0, $disabled=0) {
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   $tsel = '';
   $fsel = '';
   if ( ($fieldvalue == 'false') or ($fieldvalue == 'f') or (!$fieldvalue) ) {
      $fsel = ' selected';
   } else {
      $tsel = ' selected';
   }
   $innerHTML = "<select name='$fieldname' $distext>\n";
   $innerHTML .= "<option value='true'$tsel>True</option>\n";
   $innerHTML .= "<option value='false'$fsel>False</option>\n";
   $innerHTML .= "</select>\n";
   print("$selhtml");
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function formatHiearchicalMenuItem($thislink) {   
   $name = $thislink['name'];
   $label = $thislink['label'];
   $info = $thislink['info'];
   $onClick = $thislink['onClick'];
   $iconHTML = '';
   if (isset($thislink['icon'])) {
      $icon = $thislink['icon'];
      $label = "<img src='$icon'> ";
   }
   $href = "<a id=\"$name\" class=\"mE\" title=\"$info\" onClick=\"$onClick\" border=1>$label</a>";
   return $href;
}

function showHierarchicalSelect($selections, $varname, $varvalue, $isMulti, $disabled) {
   global $icons;
   # $vis_allobjects - whether the menu is open or closed by default
   $menuHTML = '';
   $count = 0;
   // this makes a dhtml based multi-level folder select list.  Single select is radio button, multi is check box
   foreach ($selections as $thisobject) {
      $itemoutput = showHierarchicalSelectItem($thisobject, $varname, $varvalue, $isMulti, $disabled, $count);
      $count = $itemoutput['count'];
      $thisitem = $itemoutput['innerHTML'];
      $menuHTML .= $delim . $thisitem;
      $delim = '<br>';
   }
   
   return $menuHTML;
}

function showHierarchicalSelectItem($thisobject, $varname, $varvalue, $isMulti, $disabled, $count=0) {
   global $icons;
   // called by showHierarchicalSelect() or by itself recursively
   # $vis_allobjects - whether the menu is open or closed by default
   $innerHTML = '';
   $output = array();
   // this makes a dhtml based multi-level folder select list.  Single select is radio button, multi is check box

   $name = $thisobject['name'];
   $id = $thisobject['id'];
   if (!isset($thisobject['label'])) {
      $label = $name;
   } else {
      $label = $thisobject['label'];
   }
   if (!isset($thisobject['info'])) {
      $info = $name;
   } else {
      $info = $thisobject['info'];
   }
   if (!isset($thisobject['children'])) {
      $children = array();
   } else {
      $children = $thisobject['children'];
   }
   if (!isset($thisobject['icon'])) {
      $icon = '';
   } else {
      $icon = $thisobject['icon'];
   }

   if (!isset($thisobject['onClick'])) {
      $onClick = $thisobject['links']['edit']['onClick'];
   } else {
      $onClick = $thisobject['onClick'];
   }

   $iconHTML = '[Icon]';
   if (strlen($icon) > 0) {
      $iconHTML = "<img src='$icon'>";
   }
   // now, select (radio) or multi-select (checkbox) button here
   switch ($isMulti) {
      case 0:
         $buttonHTML = showRadioButton($varname, $id, $varvalue, '', 1, 0, $varname);
      break;

      case 1:
         $buttonHTML = showCheckBox($varname . '[' . $count . ']', $id, '', '', 1, 0);
      break;
      
      default:
         $buttonHTML = showRadioButton($varname, $id, $varvalue, '', 1, 0, $varname);
      break;
   }
   $count++;
   $buttonHTML .= '&nbsp;';

   $innerHTML .= "\n<div style=\"width: 360px;float: left;\">";
   if (count($children) > 0) {
      // include a toggle button if this has children
      $innerHTML .= "<span id=\"togbut$id\" onclick=\"toggleMenu('$id'); toggle_button('togbut$id');\" class=\"mHier\">&#x25B7;</span>";
   } else {
      $innerHTML .= "<span class=\"mHier\">&nbsp;&nbsp;&nbsp;&nbsp;</span>";
   }

   $innerHTML .= "$buttonHTML<a class=\"mHier\" id=\"toggle$id\" onclick=\"$onClick ;\" title=\"$info\"><b> $iconHTML $label </b></a>";
   $innerHTML .= "</div>";
   $innerHTML .= "\n<div id=\"$id\"  style=\"display: $vis_allobjects;\" class=\"mC\" >";
   //return $innerHTML;
   if (count($children) > 0) {
      $innerHTML .= "<ul class=\"mHier\">";
      foreach ($children as $thischild) {
         $itemoutput = showHierarchicalSelectItem($thischild, $varname, $varvalue, $isMulti, $disabled, $count);
         $count = $itemoutput['count'];
         $thisitem = $itemoutput['innerHTML'];
         $innerHTML .= '<li class="mHier">' . $thisitem . " ";
      }
      $innerHTML .= "</ul>";
   }
   $innerHTML .= "</div>";
   
   $output['count'] = $count;
   $output['innerHTML'] = $innerHTML;
   return $output;
}

function showTFListType($fieldname,$fieldvalue,$type, $onchange='', $silent=0, $disabled=0) {
   if ($disabled) {
      $distext = 'disabled';
   } else {
      $distext = '';
   }
   # $fieldname - the form variable name
   # $filedvalue - the current selected value
   # $type - the type of data (boolean, text), see below
   # $onchange - what to do when the value is changed, '' means nothing
   $tsel = '';
   $fsel = '';
   switch ($type) {
      case 1:
      # boolean
         $tval = 1;
         $fval = 0;
      break;

      case 2:
      # tf text
         $tval = 'true';
         $fval = 'false';
      break;

      default:
         $tval = 'true';
         $fval = 'false';
      break;

   }

   if ( ($fieldvalue == $fval) or (!$fieldvalue) ) {
      $fsel = ' selected';
   } else {
      $tsel = ' selected';
   }
   #print("$fieldvalue");
   $innerHTML = "<select name='$fieldname' onChange='$onchange' $distext>\n";
   $innerHTML .= "<option value='$tval'$tsel>True</option>\n";
   $innerHTML .= "<option value='$fval'$fsel>False</option>\n";
   $innerHTML .= "</select>\n";
   if ($debug) {
      $innerHTML .= "$selhtml";
   }
   if (!$silent) {
      print $innerHTML;
   } else {
      return $innerHTML;
   }
}

function listToSQL(&$item, $key) {
   if ( (strlen($item) == 0) or (is_null($item)) ) {
      $item = "''";
   } elseif (!is_numeric(chop($item))) {
      $item = ltrim(rtrim($item));
      $item = "'$item'";
      $ch = ord(substr($item,0,1));
      $l1 = strlen($item);
      $l2 = strlen(ltrim($item));
      $isblank = strstr($item,' ');
      #print("$l1, $l2, $isblank : $ch \n<br>");
      if ( ($isblank) or (strlen($item) == 2) ) {$item = "NULL";}
      #if ( $isblank ) {$item = "NULL";}
   }
}

function parseCSVdb($dbobj,$infilename,$tablename,$tabledef,$debug) {

   $inf = fopen($infilename,'r');
   $sectiondata = array();
   $maxlinewidth = 1000;

   $dbobj->querystring = $tabledef;
   if ($debug) {
      print("$dbobj->querystring ; <br>\n");
   }
   $dbobj->performquery();

   $headerline = fgets($inf,$maxlinewidth);

   while ($inline = fgets($inf,$maxlinewidth)) {
      $thisline = explode(",",$inline);
      array_walk($thisline,'listToSQL');
      $linevals = implode(",",$thisline);
      $dbobj->querystring = "insert into $tablename ($headerline) values ($linevals)";
      if ($debug) {
         print("$dbobj->querystring ; <br>\n");
      }
      $dbobj->performquery();
   }

   fclose($inf);

}

function parseCSVToTableDef($tablename, $maxstrlen, $inlines, $sep, $temp) {

   # expects an array with at least 2 entries, as delimited lines, with collumn names
   # and a row of sample data.
   $header = $inlines[0];
   $dataline = $inlines[1];
   $labels = explode("$sep", $header);
   $data = explode("$sep", $dataline);

   if ($temp) {
      $ttext = 'temp';
   } else {
      $ttext = '';
   }

   $cstr = "create $ttext table $tablename ( ";
   $vdel = '';

   for ($i = 0; $i < count($data); $i++) {
      $td = chop($data[$i]);
      $tc = rtrim(ltrim($labels[$i]));
      if (is_numeric($td)) {
         $vtype = 'float8';
      } else {
         if (strlen($td) > 255) {
            $strlen = $maxstrlen;
         } else {
            $strlen = 255;
         }
         $vtype = "varchar($strlen)";
      }

      $cstr .= "$vdel \"$tc\" $vtype";
      $vdel = ',';
   }

   $cstr .= ")";

   return $cstr;

}

function createDBFromCSV($dbobj, $infilename,$tablename, $maxstr, $istemp, $debug) {

   $inf = fopen($infilename,'r');
   $sectiondata = array();
   $maxlinewidth = 1000;

   $headerline = fgets($inf,$maxlinewidth);
   $headercols = array();
   foreach (explode(',', $headerline) as $thiscol) {
      array_push($headercols, ltrim(rtrim($thiscol)) );
   }

   $fheaderline = '"' . join('","', $headercols) . '"';

   $testdata = array();
   array_push($testdata, $headerline);
   $i = 0;

   while ($inline = fgets($inf,$maxlinewidth)) {
      if ($i == 0) {
         array_push($testdata, $inline);
         $tabcreate = parseCSVToTableDef($tablename, $maxstr, $testdata, ",", $istemp);
         $dbobj->querystring = $tabcreate;
         if ($debug) {
            print("$dbobj->querystring ; <br>\n");
         }
         $dbobj->performQuery();
      }

      $thisline = explode(",",$inline);
      array_walk($thisline,'listToSQL');
      $linevals = implode(",",$thisline);
      $dbobj->querystring = "insert into $tablename ($fheaderline) values ($linevals)";
      if ($debug) {
         print("$dbobj->querystring ; <br>\n");
      }
      $dbobj->performquery();

      $i++;

   }

   fclose($inf);

   return array($headerline);

}

function parseCSVToFormat($dbobj, $columninfo, $infilename, $tablename, $istemp, $delim, $createonly, $debug) {

   # creates a table from a csv file
   # expects an array in to describe the input columns as:
   # $columninfo['columnname']['required'] = 1/0
   # $columninfo['columnname']['type'] = SQL column type for create statement

   # if $createonly = 1; just create the table and then return

   $inf = fopen($infilename,'r');
   $sectiondata = array();
   $maxlinewidth = 1000;

   $retvals = array();
   $retvals['columns'] = array();
   $error = 0;
   $errmsg = '';
   $tempstr;
   $thisdel = '';
   $keydel = '';
   $keycols = '';

   if ($istemp) {
      $tempstr = 'temp';
   }
   $tabledef = "create $tempstr table $tablename ( ";

   # get column labels
   $headerline = fgets($inf,$maxlinewidth);
   if ($debug) {
      print("Header Line: $headerline <br>");
   }
   $tfields = explode($delim, $headerline);

   reset($tfields);
   #remove whitespace from the field names
   for ($j = 0; $j < count($tfields); $j++) {
      $tfields[$j] = ltrim(rtrim($tfields[$j]));
   }
   # stash in return array
   $retvals['columns'] = $tfields;

   # check for required columns, and build table definition statement
   foreach (array_keys($columninfo) as $thiscol) {
      # check for required fields
      if ($columninfo[$thiscol]['required']) {
         if (!(in_array($thiscol, $tfields))) {
            # this required column is missing
            $retvals['error'] = 1;
            $retvals['errmsg'] .= "Required column <b>$thiscol</b> is missing.<br>";
            # add it to the definition anyhow
            $thistype = $columninfo[$thiscol]['type'];
            $tabledef .= "$thisdel \"$thiscol\" $thistype";
            $thisdel = ',';
         }
      }

      # add this column to the table def and insert statement if it is present
      if (in_array($thiscol, $tfields)) {
         $thistype = $columninfo[$thiscol]['type'];
         $tabledef .= "$thisdel \"$thiscol\" $thistype";
         $thisdel = ',';
         $keycols .= $keydel . $thiscol;
         if ($debug) { print("DEL: $keydel COL: $thiscol <br>"); }
         $keydel = ',';
      }
   }

   $tabledef .= ')';
   # stash in return array
   $retvals['keycols'] = $keycols;


   $testdata = array();
   array_push($testdata, $headerline);
   $i = 0;

   if (!($retvals['error'])) {
      #create the table
      $dbobj->querystring = $tabledef;
      if ($debug) {
         print("$dbobj->querystring ; <br>\n");
      }
      $dbobj->performquery();

      if (!$createonly) {
         while ($inline = fgets($inf,$maxlinewidth)) {
            $thisline = explode(",",$inline);
            array_walk($thisline,'listToSQL');
            $linevals = implode(",",$thisline);
            $dbobj->querystring = "insert into $tablename ($headerline) values ($linevals)";
            if ($debug) {
               print("$dbobj->querystring ; <br>\n");
            }
            $dbobj->performquery();

            $i++;
         }
      }
   }

   fclose($inf);
   $retvals['number'] = $i;
   $retvals['insql'] = $tabledef;
   $retvals['headerline'] = $headerline;

   return $retvals;

}

####################################################################################
##                                   sessionData                                  ##
####################################################################################
// session data table management



####################################################################################
##                               genericListObject                                ##
####################################################################################
class genericListObject {
   # this is a shell to provide a generic interface to the list object output routines, so that
   # an object may address a simple text object in the same manner as a tabbed list object
   var $listobject = '';
   var $name = '';
   var $innerHTML = '';
   var $debugHTML = '';
   var $values = array();

   function init() {
      $this->innerHTML = '';
   }

   function append($textstring, $tabname='') {
      // $tabname does nothing
      $this->innerHTML .= $textstring;
   }

   function render() {
      # blank function, does nothing
      return;
   }

}
####################################################################################
##                                tabbedListObject                                ##
####################################################################################


class tabbedListObject {
   var $tab_names = array(); /* array containing list of tabs */
   var $tab_buttontext = array();
   var $tab_onclick = array(); // extra onClick scripts
   var $tab_headers = array(); // text for top of tab display area
   var $tab_HTML = array();
   var $tab_prefix = array(); // things to insert before the <div> marker beginning this tab
   var $tab_postfix = array(); // things to insert after the </div> marker on this tab
   var $adminsetup = array();
   var $listobject = '';
   var $name = '';
   var $button_class = 'tabmenu';
   var $innerHTML = '';
   var $height = '600px';
   var $width = '800px';
   # for use with generic functions
   var $current_tab = '';
   var $default_tab = 'default';
   var $formpieces = array(); # will hold the pieces of a form if set
   var $values = array(); // the source form record

   function init() {
      foreach ($this->tab_names as $thistab) {
         $this->tab_HTML[$thistab] = array_key_exists($thistab, $this->tab_headers) ? $this->tab_headers[$thistab] : $thistab;
      }
      if (count($this->tab_names) == 0) {
         $this->default_tab = 'default';
         $this->current_tab = 'default';
         $this->tab_names[0] = 'default';
         $this->tab_HTML['default'] = '';
         $this->tab_buttontext['default'] = 'Other';
      } else {
         $this->default_tab = $this->tab_names[0];
      }
      $this->innerHTML = '';
   }

   function append($textstring, $tabname='') {
      if ( $tabname <> '' ) {
         $active_tab = $tabname;
      } else {
         if ( $this->current_tab <> '' ) {
            $active_tab = $this->current_tab;
         } else {
            $active_tab = $this->default_tab;
         }
      }
      $this->tab_HTML[$active_tab] .= $textstring;
      #error_log("Adding to active tab: $active_tab, " . substr($textstring, 0, 10) . " ... ");
   }

   function addTab($tabname, $tablabel, $tab_header = '') {
      #error_log("Checking for tab: $tabname, $tablabel IN " . print_r($this->tab_names,1) . "<br>" );
      if (!in_array($tabname, $this->tab_names)) {
         array_push($this->tab_names, $tabname);
         $this->tab_headers[$tabname] = $tab_header;
         if (strlen($tab_header) > 0) {
            $this->tab_headers[$tabname] .= "<br>";
         }
         $this->tab_buttontext[$tabname] = $tablabel;
         #error_log("Adding tab: $tabname, $tablabel");
      }
   }
   
   function getTabKey($tabname) {
      $akey = array_search($tabname, $this->tab_names);
      return $akey;
   }
   
   function getTabID($tabname) {
      $akey = $this->getTabKey($tabname);
      return $this->name . "_data$akey";
   }
   
   function render() {
      $this->createTabListView();
   }

   function createTabListView($activetab = '') {
      if ($activetab == '') {
         $activetab = $this->default_tab;
         $akey = 0;
      } else {
         $akey = $this->getTabKey($activetab);
      }

      $i = 0;
      $innerHTML = '';
      #$innerHTML .= $this->showJavaScript($activetab);
      $groupname = $this->name;
      $innerHTML .= "<script language='Javascript'>";
      $innerHTML .= "last_tab['$groupname'] = '$groupname" . "_data$akey';\n ";
      $innerHTML .= "last_button['$groupname'] = '$groupname" . "_$akey';";
      $innerHTML .= "</script>";
      $innerHTML .= "<table><tr><td width='$this->width' ><ul class='$this->button_class'>";
      foreach ($this->tab_names as $key => $thistab) {
         # show tab buttons
         // use this i = key for autonotations
         $i = $key;
         $dataname = $this->name . "_data$i";
         $buttontext = $this->tab_buttontext[$thistab];
         if (isset($this->tab_onclick[$thistab])) {
            $tabclick = $this->tab_onclick[$thistab];
         } else {
            $tabclick = '';
         }
         if ($activetab == $thistab) {
            $class = "active";
         } else {
            $class = '';
         }
         $innerHTML .= "<li><a id=" . $this->name . "_$i class='$class' onclick=\"$tabclick ; show_next('$dataname', '" . $this->name . "_$i', '$groupname')\" >$buttontext</a></li>";
         //$i++;
      }
      $innerHTML .= "</ul></td></tr></table>";
      reset($this->tab_names);
      #$innerHTML .= "<table><tr><td width=$this->width >";
      $i = 0;
      foreach ($this->tab_names as $thistab) {
         # now show the tab data
         $dataname = $this->name . "_data$i";
         $hide = '';
         if ($thistab <> $activetab) {
            $hide = "display:none ;";
         }
         if (isset($this->tab_prefix[$thistab])) {
            $innerHTML .= $this->tab_prefix[$thistab];
         }
         $innerHTML .= "<div style=\"overflow: auto; height: $this->height; width: $this->width; $hide\" id=$dataname >" . $this->tab_HTML[$thistab] . "</div>";
         if (isset($this->tab_postfix[$thistab])) {
            $innerHTML .= $this->tab_postfix[$thistab];
         }
         $i++;
      }
      #$innerHTML .= "</td></tr></table>";

      $this->innerHTML = $innerHTML;
   }

   function showCSS() {
      $cssHTML = "<STYLE TYPE='text/css'>";
      $cssHTML .= "<!--\n";
      $cssHTML .= "#tabmenu {";
      $cssHTML .= "  color: #000;";
      $cssHTML .= "   border-bottom: 2px solid black;";
      $cssHTML .= "   margin: 12px 0px 0px 0px;";
      $cssHTML .= "   padding: 0px;";
      $cssHTML .= "   z-index: 1;";
      $cssHTML .= "   padding-left: 10px }";
      $cssHTML .= "#tabmenu li {";
      $cssHTML .= "   display: inline;";
      $cssHTML .= "   overflow: hidden;";
      $cssHTML .= "   list-style-type: none; }";
      $cssHTML .= "#tabmenu a, a.active {";
      $cssHTML .= "   background: #4f94cd;";
      $cssHTML .= "   font-style: bold ;";
      $cssHTML .= "   border: 2px solid black;";
      $cssHTML .= "   padding: 2px 5px 0px 5px;";
      $cssHTML .= "   margin: 0;";
      $cssHTML .= "        font-size:smaller;";
      $cssHTML .= "   text-decoration: none; }";
      $cssHTML .= "#tabmenu a.active {";
      $cssHTML .= "   background: #e2eff5;";
      $cssHTML .= "   border-bottom: 3px solid #e2eff5; }";
      $cssHTML .= "#tabmenu a:hover {";
      $cssHTML .= "   color: #fff;";
      $cssHTML .= "   background: #ADC09F; }";
      $cssHTML .= "#tabmenu a.active:hover {";
      $cssHTML .= "   background: #e2eff5;}";
      $cssHTML .= "-->";
      $cssHTML .= "</STYLE>";

      return $cssHTML;
   }

   function showJavaScript($activetab) {
      $scriptHTML = '';
      $scriptHTML .= "<script language='JavaScript'> ";
      $scriptHTML .= " // Gleaned from ";
      $scriptHTML .= "http://www.aliroman.com/article/how-to-create-web-tabs-with-javascript-show-hide-layers-34-1.html ";
      $scriptHTML .= "last_tab = '$activetab'; ";
      $scriptHTML .= "function show(layerName) { ";
      $scriptHTML .= "document.getElementById(layerName).style.display = ''; ";
      $scriptHTML .= "} ";
      $scriptHTML .= " ";
      $scriptHTML .= "function hide(layerName) { ";
      $scriptHTML .= "document.getElementById(layerName).style.display = 'none'; ";
      $scriptHTML .= "} ";
      $scriptHTML .= "function show_next(tab_name) { ";
      #$scriptHTML .= "document.getElementById(last_tab).className = 'tab'; ";
      $scriptHTML .= "var curr = document.getElementById(tab_name); ";
      #$scriptHTML .= "curr.className='tab_hover'; ";
      $scriptHTML .= "hide(last_tab); ";
      $scriptHTML .= "show(tab_name); ";
      $scriptHTML .= "last_tab=tab_name; ";
      $scriptHTML .= "} ";
      $scriptHTML .= "</script> ";

      return $scriptHTML;
   }

}


class listObjectSearchForm {

   var $adminsetup = array(); # this should be populated with a single entry of adminsetup record for the search form in question
   var $listobject = -1; # must be a valid list object, or this will return nothing
   var $debug = 0;
   var $searchTerms = array(); # will contain all formatted search terms
   var $record_submit_script = ''; # javascript to execute on a record forward/backward/edit button click
   var $page_submit_script = ''; # javascript to execute on a page forward/backward button click
   var $search_submit_script = ''; # javascript to execute on a search button click
   var $readonly = 0; # this tells the object to disable saving and editing
   var $insertOK = 0; # this tells the object to disable/enable insert
   var $deleteOK = 0; # tells whether or not the user can delete the given record
   var $formcols = array(); # associative array to map record column names with search colulmn names
   var $forcePK = ''; # if this is set by the calling function, we will force the displayed record to be equal to the value
   var $geomcollectsql = ''; # string to hold a query for the geometries to pass to external mapping environment
   var $geomlimit = 20; 
   # limit the number of geometries to pass back for display on an external mapping system, this is needed
   # to prevent a mapping suite such as OpenLayers from bogging down by drawing too many vector features

   var $searchnames = array();
   var $pkval = '';
   var $prev_record_id = '';
   var $next_record_id = '';
   ##############################################################################################
   # Format of adminsetup 'search info' record, used to make search form, and perform searches  #
   ##############################################################################################
   # 'search info' - columns to be included in search form, method for searching
      # 1 - exact match, entry field, multi-select or select list, based on 'column info' entry
      # 2 - exact match, select list, based on 'column info' entry
      # 3 - exact match, Simple multi-select, based on 'column info' entry (not mapping, just select from values in table)
      # 4 - upper, lower bounds
      # 5 - fuzzy match - uses ilike or like (case-sensitive is parameter)
      # 6 - geometric bounding box overlap && - not yet implemented
   ##############################################################################################
   ###                    END - 'search info' record description                              ###
   ##############################################################################################

   # search variables prefix
   var $default_prefix = 'srch_';
   # example 'search info' record
   # "search info"=>array(
   #    "tabledef"=>"vwuds_measuring_point",
   #    "pk"=>"elementid", # primary key (may be meaningless)
   #    "sortcol"=>"record_id", # column to sort results by
   #    "passthrough"=>0, # pass form variables that are not included in search column as hidden vars?
   #    'columns'=>array(
   #       'USERID'=> array('searchtype'=>4)
   #    )
   # )

   function formatModFunction($function, $mod, $varname, $varvalue = NULL) {

      switch ($mod) {
         case 'not':
            $ftext = " not ($function) ";
         break;

         case 'eq':
            $ftext = $function;
         break;

         case 'lt':
            $ftext = " \"$varname\" < '" . $varvalue . "' ";
         break;
         
         case 'gt':
            $ftext = " \"$varname\" > '" . $varvalue . "' ";
         break;

         case 'notnull':
            $ftext = " \"$varname\" is not null ";
         break;

         case 'isnull':
            $ftext = " \"$varname\" is null ";
         break;

         case 'notblank':
            $ftext = "( (\"$varname\" <> '') and (\"$varname\" is not null) ) ";
         break;

         case 'isblank':
            $ftext = " ( (\"$varname\" = '') or (\"$varname\" is null) ) ";
         break;

         case 'like':
            $ftext = " \"$varname\" ilike '%" . $varvalue . "%' ";
         break;

         case 'notlike':
            $ftext = " \"$varname\" NOT ilike '%" . $varvalue . "%' ";
         break;
         
         case 'in':
            $ftext = "\"$varname\" IN ( $varvalue ) ";
         break;

         default:
            $ftext = $function;
         break;

      }

      return $ftext;
   }
   
   function setVariableNames($formValues = array()) {
      # search_prefix is used to give a unique column name to variable columns, by adding the prefix onto the
      # columns base name.  This will be over-ridden by the search column parameter "search_variable" in the adminsetup
      # record
      if (isset($this->adminsetup['search info'])) {
         if ($this->debug) {
            $searchResult['debug_msg'] .= "Found search info<br>";
         }
         $search_info = $this->adminsetup['search info'];
         $pkcol = $search_info['pk'];
         if (isset($search_info['variable_prefix'])) {
            $prefix = $search_info['variable_prefix'];
         } else {
            $prefix = $this->default_prefix;
         }
         if (isset($search_info['columns'])) {
            $search_cols = array_keys($search_info['columns']);
            if ($this->debug) {
               $searchResult['debug_msg'] .= "Found columns for search<br>" . print_r($search_cols,1) . "<br>";
            }
         } else {
            $search_cols = array();
         }

         if (count($search_cols) == 0) {
            # makes a generic form for all submitted values if no columns defined
            $search_cols = array_keys($formValues);
         }

         # set up a variable name for searching for the pk value
         $this->pksearchcol = $prefix . $pkcol;
         #print_r($search_cols);

         foreach ($search_cols as $varname) {
            # creeate a unique form variable name for this, in case search and update forms are mixed
            # if the name is set in adminsetup, we simply accept it.
            if (isset($search_info['columns'][$varname]['search_var'])) {
               $search_var = $search_info['columns'][$varname]['search_var'];
            } else {
               $search_var = $prefix . $varname;
            }
            $mod_var = 'mod_' . $search_var;
            $this->searchnames[$varname] = array('varname'=>$varname, 'mod_var'=>$modname, 'search_var'=>$search_var);
         }
      }
   }

   function showSearchForm($formValues = array()) {
      $controlHTML = '';
      $searchResult = array(
         'formHTML'=>'',
         'searchview'=>'list',
         'recordvalue'=>'',
         'recordid'=>'',
         'numrecs'=>0,
         'page_offset'=>0,
         'prev_page'=>0,
         'num_pages'=>0,
         'next_page'=>0,
         'nextid'=>'',
         'previd'=>'',
         'currentpos'=>0,
         'error_msg'=>'',
         'debug_msg'=>'',
         'query'=>'',
         'error'=>0,
         'navButtonHTML'=>''
      );
      $clauses = array();

      if (!is_object($this->listobject) ) {
         $searchResult['error'] = TRUE;
         $searchResult['error_msg'] .= "<b>Error:</b> Database Connection Not Defined.<br>";
         return $searchResult;
      } else {

         if (isset($formValues['searchtype'])) {
            $searchtype = $formValues['searchtype'];
         } else {
            $searchtype = 'browse';
         }

         if (isset($formValues['search_view'])) {
            $search_view = $formValues['search_view'];
         } else {
            $search_view = 'list';
         }

         if ($this->readonly and ($search_view == 'edit')) {
            if ( ($searchtype == 'new') and ($this->insertOK)) {
               # do nothing, allow the insert, otherwise
            } else {
               $search_view = 'detail';
               $controlHTML .= "<b>Error: </b> You do not have edit permissions on this record.<br>";
            }
         }
         
         # set up variable names
         $this->setVariableNames($formValues);

         # do paging stuff
         if (isset($formValues['page_records'])) {
            $page_records = $formValues['page_records'];
         } else {
            if ($search_view == 'list') {
               $page_records = 10;
            } else {
               $page_records = 1;
            }
         }
         if ($page_records <= 0) {
            $page_records = 1;
         }
         if (isset($formValues['page_offset'])) {
            $page_offset = $formValues['page_offset'];
         } else {
            $page_offset = 0;
         }
         $rec_offset = $page_offset * $page_records;

         $controlHTML .= "Submitted search view: $search_view <br>";

         #$debug = 1;
         $tableinfo = array();
         if (isset($this->adminsetup['table info'])) {
            $tableinfo = $this->adminsetup['table info'];
            $tabledef = $tableinfo['tablename'];
            if (isset($tableinfo['tabledef'])) {
               if (strlen($tableinfo['tabledef']) > 0) {
                  $tabledef = $tableinfo['tabledef'];
               }
            }
         }
         # parse 'search info'
            # determine fields for searching
            # get format of search
         # loop through search vars
            # parse formValues entry for each variable
               # get values set for each variable
               # add form field to controlHTML
         if (isset($this->adminsetup['search info'])) {
            if ($this->debug) {
               $searchResult['debug_msg'] .= "Found search info<br>";
            }
            $search_info = $this->adminsetup['search info'];
            # tabledef in the search info array overrides the one from the table info
            if (isset($search_info['tabledef'])) {
               if (strlen($search_info['tabledef']) > 0) {
                  $tabledef = $search_info['tabledef'];
               }
            }
            $pkcol = $search_info['pk'];
            $col_info = $this->adminsetup['column info'];
            # search_prefix is used to give a unique column name to variable columns, by adding the prefix onto the
            # columns base name.  This will be over-ridden by the search column parameter "search_variable" in the adminsetup
            # record
            if (isset($search_info['variable_prefix'])) {
               $prefix = $search_info['variable_prefix'];
            } else {
               $prefix = $this->default_prefix;
            }
            if (isset($search_info['columns'])) {
               $search_cols = array_keys($search_info['columns']);
               if ($this->debug) {
                  $searchResult['debug_msg'] .= "Found columns for search<br>" . print_r($search_cols,1) . "<br>";
               }
            } else {
               $search_cols = array();
            }
         } else {
            # must have the basic search form info put in, otherwise, we have no idea what the table name is
            return $searchResult;
         }

         if (count($search_cols) == 0) {
            # makes a generic form for all submitted values if no columns defined
            $search_cols = array_keys($formValues);
         }

         # set up a variable name for searching for the pk value
         $pksearchcol = $prefix . $pkcol;
         #print_r($search_cols);

         $controlHTML .= "<table>";
         $controlHTML .= "<tr>";
         $controlHTML .= "<td>";

         $formatted_search = array();
         $n = 0;
         foreach ($search_cols as $varname) {
            # creeate a unique form variable name for this, in case search and update forms are mixed
            # if the name is set in adminsetup, we simply accept it.
            if (isset($search_info['columns'][$varname]['search_var'])) {
               $search_var = $search_info['columns'][$varname]['search_var'];
            } else {
               $search_var = $prefix . $varname;
            }
            $varsubmitted = FALSE;
            if (isset($formValues[$search_var])) {
               $varsubmitted = TRUE;
            }
            if (isset($search_info['columns'][$varname]['searchtype'])) {
               $type = $search_info['columns'][$varname]['searchtype'];
            } else {
               # default to an exact value match field
               $type = 1;
            }
            if (isset($search_info['columns'][$varname]['params'])) {
               $thisparams = $search_info['columns'][$varname]['params'];
            } else {
               $thisparams = '';
            }
            if (isset($search_info['columns'][$varname]['label'])) {
               $thislabel = "<b> " . $search_info['columns'][$varname]['label'] . ": </b>";
            } else {
               $thislabel = "<b> " . $col_info[$varname]['label'] . ": </b>";
            }
            #$controlHTML .= $thislabel;
            # the content of formatted_search will be added to the controlHTML via a listobject output
            # to take advantage of the columnar formatting and color variation to improve readability
            $formatted_search[$n]['Column'] = $thislabel;

            if (isset($col_info[$varname]['width'])) {
               $colwidth = $col_info[$varname]['width'];
            } else {
               $colwidth = 12;
            }

            $thisfield = '';
            # set up modifier switch, this allows us to negate the clause
            $modname = 'mod_' . $search_var;
            $modlist = array(
               0=>array('mid'=>'eq', 'mtext'=>'='),
               1=>array('mid'=>'not', 'mtext'=>'Not'),
               8=>array('mid'=>'lt', 'mtext'=>"<"),
               9=>array('mid'=>'gt', 'mtext'=>">"),
               2=>array('mid'=>'notnull', 'mtext'=>'Not NULL'),
               3=>array('mid'=>'isnull', 'mtext'=>'Is Null'),
               4=>array('mid'=>'notblank', 'mtext'=>"Not Blank: <> '' and not null"),
               5=>array('mid'=>'isblank', 'mtext'=>"Blank: = '' OR NULL"),
               6=>array('mid'=>'in', 'mtext'=>"IN ('search1','search2',..."),
               7=>array('mid'=>'like', 'mtext'=>"Fuzzy Match"),
               8=>array('mid'=>'notlike', 'mtext'=>"No Match")
            );
            # output operators to act on columns
            $outsumlist = array(
               0=>array('osid'=>'sum', 'ostext'=>'Sum()'),
               1=>array('osid'=>'min', 'ostext'=>'Min()'),
               2=>array('osid'=>'max', 'ostext'=>'Max()'),
               3=>array('osid'=>'mean', 'ostext'=>'Mean()')
            );
            if (isset($formValues[$modname])) {
               $modvalue = $formValues[$modname];
            } else {
               $modvalue = '';
            }
            $formatted_search[$n]['Search Modifier'] = showList($this->listobject,$modname,$modlist,'mtext','mid','',$modvalue,$debug, 1, 0);

            # screen for null and not null queries, cause they do not process like the others
            $nullmods = array('isnull', 'notnull', 'notblank', 'isblank', 'like', 'notlike');
            # exact value match
            if (isset($formValues[$search_var])) {
               $varvalue = $formValues[$search_var];
            } else {
               $varvalue = '';
            }
            if (in_array($modvalue, $nullmods)) {
               $thisclause = $this->formatModFunction('', $modvalue, $varname, $varvalue);
               array_push($clauses, $thisclause);
               $thisfield .= showWidthTextField($search_var, $varvalue, $colwidth, '', 1, 0);
            } else {

               switch ($type) {
                  case 1:
                  # exact value match
                     if (isset($formValues[$search_var])) {
                        $varvalue = $formValues[$search_var];
                     } else {
                        $varvalue = '';
                     }
                     if ($this->debug) {
                        $searchResult['debug_msg'] .= "$varname = Search Field Type 1 <br>";
                     }

                     $thisfield .= showWidthTextField($search_var, $varvalue, $colwidth, '', 1, 0);
                     if ($varsubmitted and ( strlen($varvalue) > 0) ) {
                        $thisclause = $this->formatModFunction(" \"$varname\" = '$varvalue' ", $modvalue, $varname, $varvalue);
                        array_push($clauses, $thisclause);
                     }
                  break;

                  case 3:
                  # Simple Multi-select value match
                     if (isset($formValues[$search_var])) {
                        $varvalue = $formValues[$search_var][0];
                     } else {
                        $varvalue = '';
                     }
                     if ($this->debug) {
                        $searchResult['debug_msg'] .= "$varname = Search Field Type 3 <br>";
                     }
                     if ($debug) {
                        $thisfield .= print_r($formValues[$search_var],1);
                     }
                     if (strlen($thisparams) > 0) {
                        //$controlHTML .= "Search var $search_var has params $thisparams <br>";
                        list($listtable, $listpkcol, $listcols, $sortcol, $slabels, $extrawhere) = explode(':', $thisparams);
                     } else {
                        # assume we should just show a list of the values in this table
                        list($listtable, $listpkcol, $listcols, $sortcol, $slabels, $extrawhere) = array($this->tablename, $varname, $varname, $varname, '', '');
                        //$controlHTML .= "Search var $search_var has params $thisparams <br>";
                        list($listtable, $listpkcol, $listcols, $sortcol, $slabels, $extrawhere) = explode(':', $thisparams);
                     }
                     # check to see if the list table is a CSV, if so, we need to handle it specially
                     $listarray = explode(",", $listtable);
                     $keyvaltest = explode("|", $listarray[0]);
                     if ($debug) {
                        $controlHTML .= print_r($listarray,1);
                        $controlHTML .= print_r($keyvaltest,1);
                        $controlHTML .= print_r($listarray[0],1);
                        $controlHTML .= print_r($thisvalue,1);
                     }
                     if (count($keyvaltest) > 1) {
                        $listtable = array();
                        $k = 0;
                        foreach($listarray as $thispair) {
                           list($key, $value) = explode("|", $thispair);
                           $listtable[$k][$listpkcol] = $key;
                           $listtable[$k][$listcols] = $value;
                           if ($debug) {
                              $controlHTML .= "$k: $key $value - ";
                           }
                           $k++;
                        }

                        $thisfield .= showMultiList2($listtable, $search_var, $listtable, $listpkcol, $listcols, $varvalue, $extrawhere, $sortcol, $this->debug, 3, 1, 0);
                     } else {
                        $thisfield .= showMultiList2($this->listobject, $search_var, $listtable, $listpkcol, $listcols, $varvalue, $extrawhere, $sortcol, $this->debug, 3, 1, 0);
                     }
                     if ($varsubmitted and (count($varvalue) > 0)) {
                        $thisclause = $this->formatModFunction(" \"$varname\" in ( '" . join("','", $varvalue) . "') " , $modvalue, $varname, $varvalue);
                        array_push($clauses, $thisclause);
                     }
                  break;

                  case 4:
                  # Bounded Search
                  # need to fetch min column, max column, and then decide whether to use one or both
                  # presence of the search var itself is simply a boolean, then we look for it's associated min/max variables
                     if (isset($search_info['columns'][$varname]['min_searchvar'])) {
                        $minvar = $search_info['columns'][$varname]['min_searchvar'];
                     } else {
                        $minvar = $prefix . 'min_' . $varname;
                     }
                     if (isset($formValues[$minvar])) {
                        $minvarvalue = $formValues[$minvar];
                     } else {
                        $minvarvalue = '';
                     }
                     if (isset($search_info['columns'][$varname]['max_searchvar'])) {
                        $maxvar = $search_info['columns'][$varname]['max_searchvar'];
                     } else {
                        $maxvar = $prefix . 'max_' . $varname;
                     }
                     if (isset($formValues[$maxvar])) {
                        $maxvarvalue = $formValues[$maxvar];
                     } else {
                        $maxvarvalue = '';
                     }
                     if ($this->debug) {
                        $searchResult['debug_msg'] .= "$varname = Search Field Type 4<br>";
                     }

                     $thisfield .= showHiddenField($search_var, 1, 1);
                     $thisfield .= '&nbsp;&nbsp; From ';
                     $thisfield .= showWidthTextField($minvar, $minvarvalue, $colwidth, '', 1, 0);
                     $thisfield .= ' to ';
                     $thisfield .= showWidthTextField($maxvar, $maxvarvalue, $colwidth, '', 1, 0);
                     # we do not use the negation clause in the bounded range, because it would always return no records
                     # so we reset this
                     $formatted_search[$n]['Search Modifier'] = '';
                     # array to hold multiple columns for this query
                     $multivars = array(0=>$varname);
                     if (isset($search_info['columns'][$varname]['search_cols'])) {
                        # setup file calls for multiple or custom search column
                        if (strlen($search_info['columns'][$varname]['search_cols']) > 0) {
                           $multivars = explode(',', $search_info['columns'][$varname]['search_cols']);
                        }
                     }
                     $thisclause = "";
                     # array to hold multiple clauses for this query, seperated by OR's
                     $multisearch = array();
                     if ($varsubmitted) {
                        foreach ($multivars as $orvar) {
                           $multiclause = '';
                           $minclause = '';
                           $subconjunction = '';
                           $addclause = 0;
                           # AND the min/max for a single column
                           # OR the seperate columns
                           if (strlen($minvarvalue) > 0) {
                              $multiclause .= " \"$orvar\" >= '$minvarvalue' ";
                              $subconjunction = 'AND';
                              $addclause = 1;
                           }
                           $maxclause = '';
                           if (strlen($maxvarvalue) > 0) {
                              $multiclause .= " $subconjunction \"$orvar\" <= '$maxvarvalue' ";
                              $addclause = 1;
                           }
                           if ($addclause) {
                              array_push($multisearch, "( " . $multiclause . " )");
                           }
                        }
                        if (count($multisearch) > 0) {
                           $thisclause = "( " . join(" OR ", $multisearch) . " )";
                           array_push($clauses, $thisclause);
                           #$controlHTML .= " Multi-clause = $thisclause <br>";
                        }
                     }
                  break;

                  default:
                  # exact value match
                     if (isset($formValues[$search_var])) {
                        $varvalue = $formValues[$search_var];
                     } else {
                        $varvalue = '';
                     }
                     $thisfield .= showWidthTextField($search_var, $varvalue, $colwidth, '', 1, 0);
                     if ($varsubmitted) {
                        $thisclause = $this->formatModFunction(" \"$varname\" = '$varvalue' ", $modvalue, $varname, $varvalue);
                        array_push($clauses, $thisclause);
                     }
                  break;
               }
            }
            #$controlHTML .= $thisfield;
            #$controlHTML .= print_r($clauses, 1) . '<br>';
            $formatted_search[$n]['Search Criteria'] = $thisfield;
            $n++;
            #$controlHTML .= "<br>";
         }
         $controlHTML .= "<hr>";
         $this->listobject->queryrecords = $formatted_search;
         $this->listobject->show = 0;
         $this->listobject->showList();
         $controlHTML .= $this->listobject->outstring . '<hr>';

         $controlHTML .= "</td>";
         $controlHTML .= "</tr>";
         $controlHTML .= "<tr>";
         $controlHTML .= "<td align=center>";

         # get query prepared
         $query_foo = "SELECT * FROM " . $tabledef . " ";
         # now add clauses
         $clause_join = 'WHERE';
         $search_clause = '';
         foreach ($clauses as $thisclause) {
            $search_clause .= $clause_join . " " . $thisclause;
            $clause_join = ' AND ';
         }
         $query_foo .= $search_clause;

         $searchResult['query'] = $query_foo;
         $searchResult['debug'] .= "Base Query: $query_foo <br>";
         $searchResult['pkclause'] = '';
         $pkclause = '(1 = 1)';

         if ( isset($formValues[$pksearchcol]) and ($searchtype <> 'search') ) {
            # if we have a forced pk set, then we go ahead and use it, otherwise, we can look at the pksearchcol
            # the forced pk overrides the searchpkcol
            if ($this->forcePK <> '') {
               $pkval = $this->forcePK;
               $searchResult['debug'] .= "Forcing pk search on $pkval <br>";
            } else {
               $pkval = $formValues[$pksearchcol];
               $searchResult['debug'] .= "Defaulting to PK search on $pksearchcol - $pkval <br>";
            }
            $numrecs = $formValues[$prefix . 'numrecs'];
            $pkclause = " \"$pkcol\" = '$pkval' ";
            $searchResult['pkclause'] = $pkclause;
         } else {
            # if the record_id is NOT set, OR if this is a search, we go for this
            $this->listobject->querystring = "select min(\"$pkcol\") as \"$pkcol\", count(*) as numrecs from $tabledef ";
            $this->listobject->querystring .= $search_clause;
            if ($debug) {
               $controlHTML .= $this->listobject->querystring . " ; <br>";
            }
            $this->listobject->performQuery();
            if ($this->forcePK <> '') {
               $pkval = $this->forcePK;
            } else {
               $pkval = $this->listobject->getRecordValue(1,$pkcol);
            }
            $numrecs = $this->listobject->getRecordValue(1,'numrecs');
            if ($numrecs > 0) {
               $pkclause = " \"$pkcol\" = '$pkval' ";
            }
         }

         # do hidden variables for form control
         if ($debug) {
            $controlHTML .= "PK Search col name: $pksearchcol <br>";
         }
         switch ($search_view) {

            case 'batchedit':
               # must add hidden variable for pk and page offset
               $controlHTML .= showHiddenField($pksearchcol, $pkval, 1);
               $controlHTML .= showHiddenField('page_offset', $page_offset, 1);
            break;

            case 'detail':
               # must add hidden variable for pk
               $controlHTML .= showHiddenField($pksearchcol, $pkval, 1);
               $controlHTML .= showHiddenField('page_offset', $page_offset, 1);
            break;

            case 'edit':
               # must add hidden variable for pk
               $controlHTML .= showHiddenField($pksearchcol, $pkval, 1);
               $controlHTML .= showHiddenField('page_offset', $page_offset, 1);
            break;

            case 'file':
            # no hiddens
               $controlHTML .= showHiddenField('page_offset', $page_offset, 1);

            default:
            # list view is default
            # no hiddens
               # must add hidden variable for pk and page offset
               $controlHTML .= showHiddenField($pksearchcol, $pkval, 1);
               $controlHTML .= showHiddenField('page_offset', $page_offset, 1);
            break;
         }

         # show form display control variables
         $searchOptions = '';
         $searchOptions .= showRadioButton('search_view', 'list', $search_view, '', 1, 0) . ' List View';
         $searchOptions .= showRadioButton('search_view', 'detail', $search_view, '', 1, 0) . ' Detail View';
         $searchOptions .= showRadioButton('search_view', 'edit', $search_view, '', 1, 0) . ' Edit View';
         # not ready for prime time
         #$controlHTML .= showRadioButton('search_view', 'batchedit', $search_view, '', 1, 0) . ' Batch Edit View';
         $searchOptions .= showRadioButton('search_view', 'file', $search_view, '', 1, 0) . ' Output to File';
         $searchOptions .= showRadioButton('search_view', 'custom', $search_view, '', 1, 0) . ' Custom';
         # action info
         $searchOptions .= showHiddenField('searchtype', $searchtype, 1);
         $searchOptions .= '<br>' . showGenericButton('search','Search', "document.forms[\"control\"].elements.searchtype.value=\"search\"; document.forms[\"control\"].elements.page_offset.value=0;  $this->search_submit_script ; ", 1, $pd);

         $controlHTML .= "&nbsp</td>";
         $controlHTML .= "</tr>";
         $controlHTML .= "</table>";

         #$controlHTML .= "Search View: $search_view <br>";
         $controlHTML .= showHiddenField($prefix . 'numrecs', $numrecs, 1);

         $this->listobject->querystring = '';
         if ($numrecs > 0) {
            switch ($search_view) {

               case 'batchedit':
                  $this->listobject->querystring = "select * from $tabledef ";
                  $this->listobject->querystring .= $search_clause;
                  $this->limitstring = '';
                  $nolimit_sql = $this->listobject->querystring;
               break;

               case 'file':
                  $this->listobject->querystring = "select * from $tabledef ";
                  $this->listobject->querystring .= $search_clause;
                  $this->limitstring = '';
                  $nolimit_sql = $this->listobject->querystring;
                  break;

               case 'detail':
                  $this->listobject->querystring = "select * from $tabledef WHERE $pkclause ";
                  $this->limitstring = '';
                  $nolimit_sql = $this->listobject->querystring;
               break;

               case 'edit':
                  $this->listobject->querystring = "select * from $tabledef WHERE $pkclause ";
                  $this->limitstring = '';
                  $nolimit_sql = $this->listobject->querystring;
               break;

               default:
               // this is the case for list-view
                  $this->listobject->querystring = "select * from $tabledef ";
                  $this->listobject->querystring .= $search_clause;
                  $nolimit_sql = $this->listobject->querystring;
                  $this->limitstring = " LIMIT $page_records OFFSET $rec_offset ";
                  $this->listobject->querystring .= $this->limitstring;
               break;

            }
         }
         if ($debug) {
            $controlHTML .= $search_view . " " . $this->listobject->querystring . " ; <br>";
         }
         $searchResult['debug'] .= $this->listobject->querystring . " ; <br>";
         $this->listobject->performQuery();
         # stash this for use later
         $recsql = $this->listobject->querystring;
         switch ($search_view) {

            case 'batchedit':
               $props = $this->listobject->queryrecords;
               $pkval = $props[0][$pkcol];
            break;

            case 'detail':
               $props = $this->listobject->queryrecords[0];
               $pkval = $props[$pkcol];
            break;

            case 'edit':
               $props = $this->listobject->queryrecords[0];
               $pkval = $props[$pkcol];
            break;

            case 'file':
               $props = $this->listobject->queryrecords;
               $pkval = $props[0][$pkcol];
            break;

            default:
               $props = $this->listobject->queryrecords;
               $pkval = $props[0][$pkcol];
            break;
         }
         $this->pkval = $pkval;

         # find the extent of the selected records if the proper entries are set in adminsetup
         # also, return a query that will show a specified number of geometries in WKT form to be
         # imported into OpenLayers if the user desires
         if (isset($this->adminsetup['table info']['geom_col'])) {
            $extent_where = "";
            #$controlHTML .= "Getting geometry elements<br>";
            $geomcol = $this->adminsetup['table info']['geom_col'];
            if (isset($this->adminsetup['table info']['extent_where'])) {
               if (strlen($this->adminsetup['table info']['extent_where']) > 0 ) {
                  $extent_where = "WHERE " . $this->adminsetup['table info']['extent_where'] . " ";
               }
            }
            $ext_sql = "  SELECT xmin(extent($geomcol)) as x1, ";
            $ext_sql .= "    ymin(extent($geomcol)) as y1, ";
            $ext_sql .= "    xmax(extent($geomcol)) as x2, ";
            $ext_sql .= "    ymax(extent($geomcol)) as y2 ";
            $ext_sql .= " FROM ( " . $recsql . " ) as foo ";
            $ext_sql .= $extent_where;
            if ($debug) {
               $controlHTML .= "$ext_sql <br>";
            }
            $this->listobject->querystring = $ext_sql;
            $this->listobject->performQuery();
            $searchResult['x1'] = $this->listobject->getRecordValue(1,'x1');
            $searchResult['y1'] = $this->listobject->getRecordValue(1,'y1');
            $searchResult['x2'] = $this->listobject->getRecordValue(1,'x2');
            $searchResult['y2'] = $this->listobject->getRecordValue(1,'y2');

            # WKT geometry collection
            # check for property "maplabelcols" as CSV of columns to show in the map as labels otherwise, just get PK
            if (isset($this->adminsetup['table info']['maplabelcols'])) {
               $labelar = explode(",",$this->adminsetup['table info']['maplabelcols']);
               $labelcols = '';
               $gdel = '';
               foreach($labelar as $thislab) {
                  $labelcols .= $gdel . '"' . $thislab . '"';
                  $gdel = ',';
               }
            } else {
               $labelcols = '"' . $pkcol . '"';
            }
            $geomcollectsql = "  select $labelcols, asText($geomcol) as $geomcol ";
            $geomcollectsql .= " from ( $recsql ) as foo ";
            $geomcollectsql .= $extent_where;
            $geomcollectsql .= " GROUP BY $labelcols, $geomcol ";
            $geomcollectsql .= " LIMIT " . $this->geomlimit;
            $this->geomcollectsql = $geomcollectsql;

         }
         $searchResult['geomcollectsql'] = $this->geomcollectsql;
         #$controlHTML .= $this->geomcollectsql . "<br>";

         $searchResult['recordvalue'] = $props;
         $searchResult['pkval'] = $pkval;
         $searchResult['numrecs'] = $numrecs;
         $searchResult['page_offset'] = $page_offset;
         if ($page_offset > 0) {
            $prev_page = $page_offset - 1;
         } else {
            $prev_page = $page_offset;
         }
         $searchResult['prev_page'] = $prev_page;
         $searchResult['num_pages'] = ceil($numrecs / $page_records);
         if ( ($page_offset + 1) >= ($numrecs / $page_records) ) {
            $next_page = $page_offset;
         } else {
            $next_page = $page_offset + 1;
         }
         $searchResult['next_page'] = $next_page;
         #$debug = 0;
         # get previous and next
         $this->listobject->querystring = "select min(\"$pkcol\") as next_record_id from $tabledef ";
         $this->listobject->querystring .= $search_clause;
         if ( ltrim(rtrim($pkval)) <> '') {
            $this->listobject->querystring .= " $clause_join \"$pkcol\" > '$pkval' ";
         }
         if ($debug) {
            $controlHTML .= $this->listobject->querystring . " ; <br>";
         }
         $this->listobject->performQuery();
         $next_record_id = $this->listobject->getRecordValue(1,'next_record_id');
         $this->listobject->querystring = "select max(\"$pkcol\") as prev_record_id, count(*) as prevno from $tabledef ";
         $this->listobject->querystring .= $search_clause;
         if ( ltrim(rtrim($pkval)) <> '') {
            $this->listobject->querystring .= " $clause_join \"$pkcol\" < '$pkval' ";
         }
         if ($debug) {
            $controlHTML .= $this->listobject->querystring . " ; <br>";
         }
         $this->listobject->performQuery();
         $prev_record_id = $this->listobject->getRecordValue(1,'prev_record_id');
         $currentpos = $this->listobject->getRecordValue(1,'prevno');
         $searchResult['nextid'] = $next_record_id;
         $searchResult['previd'] = $prev_record_id;
         $this->next_record_id = $next_record_id;
         $this->prev_record_id = $prev_record_id;
         $searchResult['currentpos'] = $currentpos;


         ############################################################################
         ###                 Control Buttons - START                              ###
         ############################################################################
         $navButtonHTML = '';
         switch ($search_view) {

            case 'file':
            # do nothing
            break;

            case 'detail':

               #$navButtonHTML .= "Previous record_id = $prev_record_id ";
               $edit_disabled = $this->readonly;
               # show the edit button
               $navButtonHTML  .= showGenericButton('editrecord','Edit', "document.forms[\"control\"].elements.searchtype.value=\"browse\"; document.forms[\"control\"].elements.search_view.value=\"edit\"; for (i=0;i<document.forms[\"control\"].elements.search_view.length;i++) { if (document.forms[\"control\"].elements.search_view[i].value == \"edit\") { document.forms[\"control\"].elements.search_view[i].checked=true ; } } ; document.forms[\"control\"].elements.$pksearchcol.value=\"$pkval\"; $this->record_submit_script ; ", 1, $edit_disabled);
               $navButtonHTML  .= "<br>";
               # should we disable the next button? (at last record)
               if ($prev_record_id == '') {
                  $pd = 1;
               } else {
                  $pd = 0;
               }
               $navButtonHTML  .= showGenericButton('showprev','<-- Previous', "document.forms[\"control\"].elements.searchtype.value=\"browse\"; document.forms[\"control\"].elements.$pksearchcol.value=\"$prev_record_id\";  $this->record_submit_script ; ", 1, $pd);
               # should we disable the next button? (at last record)
               if ($next_record_id == '') {
                  $nd = 1;
               } else {
                  $nd = 0;
               }
               #$navButtonHTML .= "Next record_id = $next_record_id ";
               $navButtonHTML .= showGenericButton('shownext','Next -->', "document.forms[\"control\"].elements.searchtype.value=\"browse\"; document.forms[\"control\"].elements.$pksearchcol.value=\"$next_record_id\"; $this->record_submit_script ; ", 1, $nd);

            break;

            case 'edit':      # this needs to be set by a permission check function, for now, it will allow editing by default
               if ($searchtype == 'new') {
                  $sv = 'insert';
                  if ($this->insertOK) {
                     $edit_disabled = 0;
                  }
               } else {
                  $sv = 'save';
                  $deletedisabled = 1;
                  $deleteOK = $this->deleteOK;
                  if ($this->deleteOK) {
                     $deletedisabled = 0;
                  }
               }

               $navButtonHTML .= $this->showSaveButton($searchtype);

               $navButtonHTML .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
               # show the delete button
               if ($sv == 'save') {
                  $navButtonHTML  .= showGenericButton('deleterecord','Delete', "document.forms[\"control\"].elements.searchtype.value=\"delete\"; for (i=0;i<document.forms[\"control\"].elements.search_view.length;i++) { if (document.forms[\"control\"].elements.search_view[i].value == \"edit\") { document.forms[\"control\"].elements.search_view[i].checked=true ; } } ; document.forms[\"control\"].elements.$pksearchcol.value=\"$pkval\"; $this->record_submit_script ; ", 1, $deletedisabled);
               }
               
               $navButtonHTML  .= "<br>";
               # should we disable the next button? (at last record)
               if ($prev_record_id == '') {
                  $pd = 1;
               } else {
                  $pd = 0;
               }
               $navButtonHTML  .= showGenericButton('showprev','<-- Previous', "document.forms[\"control\"].elements.searchtype.value=\"browse\"; document.forms[\"control\"].elements.$pksearchcol.value=\"$prev_record_id\";  $this->record_submit_script ; ", 1, $pd);
               # should we disable the next button? (at last record)
               if ($next_record_id == '') {
                  $nd = 1;
               } else {
                  $nd = 0;
               }
               #$navButtonHTML .= "Next record_id = $next_record_id ";
               $navButtonHTML .= showGenericButton('shownext','Next -->', "document.forms[\"control\"].elements.searchtype.value=\"browse\"; document.forms[\"control\"].elements.$pksearchcol.value=\"$next_record_id\"; $this->record_submit_script ; ", 1, $nd);
               #print("<br>");
               #$debug = 1;
            break;

            default:
            # defaults to "list" view
               #$navButtonHTML .= "Previous record_id = $prev_record_id ";
               if ($prev_page == $page_offset) {
                  $pd = 1;
               } else {
                  $pd = 0;
               }
               $navButtonHTML  .= showGenericButton('prev_page','<-- Previous Page', "document.forms[\"control\"].elements.searchtype.value=\"browse\"; document.forms[\"control\"].elements.page_offset.value=\"$prev_page\"; $this->page_submit_script ;", 1, $pd);
               # should we disable the next button? (at last record)
               if ($next_page == $page_offset) {
                  $nd = 1;
               } else {
                  $nd = 0;
               }
               #$navButtonHTML .= "Next record_id = $next_record_id ";
               $navButtonHTML .= showGenericButton('next_page','Next Page -->', "document.forms[\"control\"].elements.searchtype.value=\"browse\"; document.forms[\"control\"].elements.page_offset.value=\"$next_page\";  $this->page_submit_script ; ", 1, $nd);
               #print("<br>");
            break;
         }

         # "new record" button
         if ($this->insertOK) {
            $navButtonHTML .= ' | | | ';
            $navButtonHTML .= showGenericButton('new','New Record', "document.forms[\"control\"].elements.searchtype.value=\"new\";  for (i=0;i<document.forms[\"control\"].elements.search_view.length;i++) { if (document.forms[\"control\"].elements.search_view[i].value == \"edit\") { document.forms[\"control\"].elements.search_view[i].checked=true; } else { document.forms[\"control\"].elements.search_view[i].checked=false; } } $this->page_submit_script ; ", 1, 0);
         }
         $searchResult['navButtonHTML'] = $navButtonHTML;

         ############################################################################
         ###                  Control Buttons - END                               ###
         ############################################################################

         $searchResult['formHTML'] = $controlHTML;
         $searchResult['searchOptions'] = $searchOptions;
         $searchResult['search_view'] = $search_view;

         return $searchResult;
      }
   }
   
   function showSaveButton($searchtype = '') {
      $edit_disabled = $this->readonly;

      # this needs to be set by a permission check function, for now, it will allow editing by default
      if ($searchtype == 'new') {
         $sv = 'insert';
         if ($this->insertOK) {
            $edit_disabled = 0;
         }
      } else {
         $sv = 'save';
         $deletedisabled = 1;
         $deleteOK = $this->deleteOK;
         if ($this->deleteOK) {
            $deletedisabled = 0;
         }
      }

      # show the edit button
      $editButtonHTML  = showGenericButton('editrecord','Save', "document.forms[\"control\"].elements.searchtype.value=\"$sv\"; for (i=0;i<document.forms[\"control\"].elements.search_view.length;i++) { if (document.forms[\"control\"].elements.search_view[i].value == \"edit\") { document.forms[\"control\"].elements.search_view[i].checked=true ; } } ; document.forms[\"control\"].elements.$this->pksearchcol.value=\"$this->pkval\"; $this->record_submit_script ; ", 1, $edit_disabled);
      
      return $editButtonHTML;
   }
   
   function showPrevNextRecordButtons() {
      # should we disable the next button? (at last record)
      $navButtonHTML = '';
      if ($this->prev_record_id == '') {
         $pd = 1;
      } else {
         $pd = 0;
      }
      $navButtonHTML  .= showGenericButton('showprev','<-- Previous', "document.forms[\"control\"].elements.searchtype.value=\"browse\"; document.forms[\"control\"].elements.$this->pksearchcol.value=\"$this->prev_record_id\";  $this->record_submit_script ; ", 1, $pd);
      # should we disable the next button? (at last record)
      if ($this->next_record_id == '') {
         $nd = 1;
      } else {
         $nd = 0;
      }
      #$navButtonHTML .= "Next record_id = $next_record_id ";
      $navButtonHTML .= showGenericButton('shownext','Next -->', "document.forms[\"control\"].elements.searchtype.value=\"browse\"; document.forms[\"control\"].elements.$this->pksearchcol.value=\"$this->next_record_id\"; $this->record_submit_script ; ", 1, $nd);
      
      return $navButtonHTML;
   }
}

?>
