<?php


# general purpose functions

function num_sign( $number ) { 
    return ( $number > 0 ) ? 1 : ( ( $number < 0 ) ? -1 : 0 ); 
} 

function arrayMult($inarray, $factor, $subkey) {

# multiplies all the values of an array by a factor
# $subkey would be the key in the array if this is an array within an array

$numr = count($inarray);
print("INputs: $numr \n<br>");

for($i = 0;$i < count($inarray);$i++) {
   if($subkey <> '') {
      $outarray[$i] = $inarray[$i];
      $outarray[$i]["$subkey"] = $inarray[$i]["$subkey"] * $factor;
   } else {
      $outarray[$i] = $inarray[$i] * $factor;
   }
}

return $outarray;

}

function subval_sort($a,$subkey, $sorttype='asc') {
   foreach($a as $k=>$v) {
      $b[$k] = strtolower($v[$subkey]);
   }
   switch (strtolower($sorttype)) {
      
      case 'asc':
         asort($b);
      break;
      
      case 'desc':
         arsort($b);
      break;
      
      default:
         asort($b);
      break;
   }
   
   foreach($b as $key=>$val) {
      $c[] = $a[$key];
   }
   return $c;
}

function test_alter(&$item1, $key, $prefix)
{
    $item1 = "$prefix: $item1";
}

function arrayQuote(&$item, $key, $borderchars ) {
   $item = $borderchars . $item . $borderchars;
}

function arrayReplace(&$item, $key, $testreplace ) {
   # replaces any matching item with replacement value
   # must pass the array $testreplace = array('testval'=>'', 'replaceval'=>'NULL')
   # useage:
   # $testreplace = array('testval'=>'', 'replaceval'=>'NULL');
   # array_walk($array_to_alter, 'arrayReplace', $testreplace);
   $testval = $testreplace['testval'];
   $replaceval = $testreplace['replaceval'];
   if ($item == $testval) {
      $item = $replaceval;
   }
}

function isalpha($test) {
   return !(preg_match("/[^a-z,A-Z ]/", $test));
}

function nestArraySprintf($formatstring, $inarray) {

   $outarr = array();
   foreach ($inarray as $thisline) {
      $outline = arraySprintf($formatstring, $thisline);
      array_push($outarr, $outline);
   }

   return $outarr;
}

function arraySprintf($formatstring, $inarray) {

# performs formatted print on an array of dynamic length
# uses sciFormat, a custom sprintf function

$numr = count($inarray);
$formar = explode('%',$formatstring);
$valar = array_values($inarray);
$outstring = $formar[0];
#print_r($formar);
#print_r($inarray);

for($i = 0;$i < count($formar);$i++) {

   $thisval = $valar[$i];
   $thisform = $formar[$i+1];
   #print("format: $thisform - value: $thisval <br>");
   if ( substr($thisform,0,2) == 'sc') {
      $numdecimals = substr($thisform,2);
      $outstring .= sciFormat($thisval,$numdecimals);
   } else {
      $outstring .= sprintf("%$thisform", $thisval);
   }

}

return $outstring;

}


function arrayScanf($formatstring, $inarray) {

# not yet working!!!
# performs formatted s on an array of dynamic length
# uses sciFormat, a custom sprintf function

$numr = count($inarray);
$formar = explode('%',$formatstring);
$valar = array_values($inarray);
$outstring = $formar[0];
#print_r($formar);
#print_r($inarray);

for($i = 0;$i < count($formar);$i++) {

   $thisval = $valar[$i];
   $thisform = $formar[$i+1];
   #print("format: $thisform - value: $thisval <br>");
   if ( substr($thisform,0,2) == 'sc') {
      $numdecimals = substr($thisform,2);
      $outstring .= sciFormat($thisval,$numdecimals);
   } else {
      $outstring .= sprintf("%$thisform", $thisval);
   }

}

return $outstring;

}

function formatArray($formatstring, $inarray, $numcols, $platform) {

   $outarr = array();
   foreach ($inarray as $thisline) {
      $outline = arraySprintfCol($formatstring, $thisline, $numcols, $platform);
      array_push($outarr, $outline);
   }

   return $outarr;
}

function arraySprintfCol($formatstring, $inarray, $numcols, $platform) {

# performs formatted print on an array of dynamic length
# uses sciFormat, a custom sprintf function

$numr = count($inarray);
$formar = explode('%',$formatstring);
$valar = array_values($inarray);
$outstring = $formar[0];
#print_r($formar);
#print_r($inarray);
$colcount = 1;

switch ($platform) {
   case 'unix':
   $endline = "\n";
   break;

   case 'dos':
   $endline = "\r\n";
   break;

   default:
   $endline = "\n";
   break;
}

$colcount = 0;

for($i = 0;$i < count($inarray);$i++) {

   $thisval = $valar[$i];
   $thisform = $formar[1];
   print("format: $thisform - value: $thisval <br>");
   if ( substr($thisform,0,2) == 'sc') {
      $numdecimals = substr($thisform,2);
      $outstring .= sciFormat($thisval,$numdecimals);
   } else {
      $numformats = array('d','f','F');
      if (in_array($thisform, $numformats)) {
         # check to make sure this is not returned as a null string
         if (strlen($thisval) == 0) {
            $thisval = 0;
         }
      }
      $outstring .= sprintf("%$thisform", $thisval);
   }

   $colcount++;
   if ($colcount >= $numcols) {
      $outstring .= "$endline";
      $colcount = 0;
   }

}

return $outstring;

}


function sciFormat($inval,$numdecimals) {
   $rightside = intval(log10($inval)) - 1;
   $multiplier = ($inval/pow(10.0, $rightside));
   if ($numdecimals == 0) {
      $multiplier = intval($multiplier);
   }
   $fvalue = number_format( $multiplier , $numdecimals);
   if ($fvalue == 100.0) {
      #print("SCI debug: $inval, $fvalue, $rightside, $multiplier<br>");
      $rightside += 1;
      $multiplier = 10;
      $fvalue = number_format( $multiplier , $numdecimals);
   }
   $rightstr = str_pad("$rightside",2,'0', STR_PAD_LEFT);
   $retval = "$fvalue";
   $retval .= "E$rightstr";
   if ($inval == 0 ) { $retval = "00E00"; }
   #print("$multiplier, $rightside, $inval, $numdecimals<br>");
   return $retval;
}


function array_values_recursive($ary)
{
   $lst = array();
   foreach( array_keys($ary) as $k ){
     $v = $ary[$k];
     if (is_scalar($v)) {
         $lst[] = $v;
     } elseif (is_array($v)) {
         $lst = array_merge( $lst,
           array_values_recursive($v)
         );
     }
   }
   return $lst;
}

function implode_with_keys($array, $keyglue, $valglue, $is_query = false) {
    if($is_query == true) {
        return str_replace(array('[', ']', '&'), array('%5B', '%5D', '&amp;'), http_build_query($array));

    } else {
        return urldecode(str_replace("=", $keyglue, str_replace("&", $valglue, http_build_query($array))));

    }

}

function extract_keyvalue($array, $key, $valcol = '', $keyname = '') {
    # iterates through an array of rows, and extracts the requested key and its values
    # if a keyname and valcol are given it allows you to create a new key->value relationship from 
    # elements in the associative array
    # if no valcol is given, assumes that the value column is equal to the key
    if ($valcol == '') {
       $valcol = $key;
    }
    # if no keyname is given, assumes that the keyname column is equal to the key
    if ($keyname == '') {
       $keyname = $key;
    }
    $retarray = array();
    if (is_array($array)) {
       foreach ($array as $thisarray) {
          array_push($retarray, array($keyname => $thisarray[$valcol]));
       }
    }
    return $retarray;

}

function extract_arrayvalue($array, $key) {
    # iterates through an associative array, and extracts the value of each occurence of the requested key
    # the result will be a single dimensional non-associate array
    $retarray = array();
    if (is_array($array)) {
       foreach ($array as $thisarray) {
          $retarray[] = $thisarray[$key];
       }
    }
    return $retarray;

}

function trim_value(&$value) 
{ 
    $value = trim($value); 
}

function arrayLookup($src_array, $search_key, $lookup_method, $defval, $debug=0) {
   // takes an array, and a key to search for, and performs a lookup, with flexible
   // key matching (exact match, interpolation, or stair-step) and value tranformation (if stair-step)
   switch ($lookup_method) {
      case 0:
      # exact match lookup table
      if (in_array($search_key, array_keys($src_array))) {
         $luval = $src_array[$search_key];
      } else {
         $luval = $defval;
      }
      break;

      case 1:
      # interpolated lookup table
      $lukeys = array_keys($src_array);
      if ($debug) {
         error_log("Trying to interpolate key $search_key in set " . print_r($lukeys,1));
      }
      $luval = $defval;
      for ($i=0; $i < (count($lukeys) - 1); $i++) {
         $lokey = $lukeys[$i];
         $hikey = $lukeys[$i+1];
         $loval = $src_array[$lokey];
         $hival = $src_array[$hikey];
         $minkey = min(array($lokey,$hikey));
         $maxkey = max(array($lokey,$hikey));
         if ($debug) {
            error_log("Is ($minkey <= $search_key) and ($maxkey >= $search_key) ?? ");
         }
         if ( ($minkey <= $search_key) and ($maxkey >= $search_key) ) {
            if (is_array($loval)) {
               // we have an array, so we have to interpolate each member of the hi and low value arrays
               $luval = array();
               foreach ($loval as $key => $value) {
                  $hv = $hival[$key];
                  $lv = $value;
                  $intval = interpValue($search_key, $lokey, $lv, $hikey, $hv);
                  $luval[$key] = $intval;
               }
            } else {
               if ($debug) {
                  error_log("Interpolating: interpValue($search_key, $lokey, $loval, $hikey, $hival) ");
               }
               $luval = interpValue($search_key, $lokey, $loval, $hikey, $hival);
            }
         }
      }
      break;

      case 2:
      # stair-step lookup table
      $lukeys = array_keys($src_array);
      if ($debug) {
         error_log("Stair Step Lookup requested for key $search_key in set " . print_r($lukeys,1));
      }
      $luval = $defval;
      $lastkey = 'N/A';
      for ($i=0; $i <= (count($lukeys) - 1); $i++) {
        $lokey = $lukeys[$i];
        $loval = $src_array[$lokey];
        if ($debug) {
           error_log("Comparing $lokey <= $search_key");
        }
        if ( ((float)$lokey <= $search_key) ) {
          $luval = $loval;
          $lastkey = $lokey;
          if ($debug) {
             error_log("match, setting  luval = $loval ");
          }
        }
      }
      break;

      case 3:
      # interpolated lookup table, but rather than return the value, returns the interpolated key
      // useful for return period type calcs
      $lukeys = array_keys($src_array);
      $luval = $defval;
      for ($i=0; $i < (count($lukeys) - 1); $i++) {
         $lokey = $lukeys[$i];
         $hikey = $lukeys[$i+1];
         $loval = $src_array[$lokey];
         $hival = $src_array[$hikey];
         $minkey = min(array($loval,$hival));
         $maxkey = max(array($loval,$hival));
         //error_log("Type 3 Lookup: Row $i : $search_key, $loval, $lokey, $hival, $hikey");
         if (!is_array($loval) and !is_array($hival)) {
            if ( ($minkey <= $search_key) and ($maxkey >= $search_key) ) {
               $luval = interpValue($search_key, $loval, $lokey, $hival, $hikey);
            }
         }
      }
      break;

      default:
      # exact match lookup table
      if (in_array($search_key, array_keys($src_array))) {
         $luval = $src_array[$search_key];
      } else {
         $luval = $defval;
      }
      break;

   }
   
   if ($debug) {
      error_log("Returning $luval ");
   }
   return $luval;
   
}

function array_avg($thisarray) {
   if (count($thisarray) > 0) {
      return array_sum($thisarray)/count($thisarray);
   } else {
      return FALSE;
   }
}

function array_mesh() {
   // Combine multiple associative arrays and sum the values for any common keys
   // The function can accept any number of arrays as arguments
   // The values must be numeric or the summed value will be 0

   // Get the number of arguments being passed
   $numargs = func_num_args();
  
   // Save the arguments to an array
   $arg_list = func_get_args();
 
   // Create an array to hold the combined data
   $out = array();

   // Loop through each of the arguments
   for ($i = 0; $i < $numargs; $i++) {
      $in = $arg_list[$i]; // This will be equal to each array passed as an argument

       // Loop through each of the arrays passed as arguments
      foreach($in as $key => $value) {
         // If the same key exists in the $out array
         if(array_key_exists($key, $out)) {
            // Sum the values of the common key
            $sum = $in[$key] + $out[$key];
            // Add the key => value pair to array $out
            $out[$key] = $sum;
         }else{
            // Add to $out any key => value pairs in the $in array that did not have a match in $out
            $out[$key] = $in[$key];
         }
      }
   }
 
   return $out;
}

function interpValue($thistime, $ts, $tv, $nts, $ntv, $intmethod=1) {

   switch ($intmethod) {
      default:
         //error_log(" $tv + ($ntv - $tv) * ( ($thistime - $ts) / ($nts - $ts) ) ");
         $retval = $tv + ($ntv - $tv) * ( ($thistime - $ts) / ($nts - $ts) );
      break;

   }
   return $retval;
}

function pc_fixed_width_substr($fields,$data) {

# function for parsing fixed width records in php
# http://d0om.fnal.gov/d0admin/doctaur/dtdocs/p-langs/
#       web_prog_bookshelf/pcook/ch01_11.htm (url wrapped)
#

# expected format:
# array with $fields = array ('field_name1' => field_length, 'field_name2' ... )
#   array(
#      'recid' => 2,
#      'ussnum' => 14,
#      'pfname' => 15,
#      'ethnic' => 2,
#      'jrhigh' => 1,
#      'high' => 1,
#      'ymca' => 1,
#      'college' => 1,
#      'parkrec' => 1,
#      'summer' => 1,
#      'cc' => 1,
#      'masters' => 1,
#      'disabled' => 1,
#      'waterpolo' => 1
#   )

  $r = array();
  for ($i = 0, $j = count($data); $i < $j; $i++) {
    $line_pos = 0;
    foreach($fields as $field_name => $field_length) {
      $r[$field_name] = ltrim(substr($data,$line_pos,$field_length));
      $line_pos += $field_length;
    }
  }
  return $r;
}


########################################################################
##                                TimerObject                         ##
########################################################################

class timerObject {
   # created for php 4.x and lower, some functions would be simplified
   # in a php 5.x system
   # creates a basic stopwatch
   var $timestart = 0;
   var $timeend = 0;
   var $timesplit = 0;

   function microtime_float()
   {
      list($usec, $sec) = explode(" ", microtime());
      return ((float)$usec + (float)$sec);
   }

   function startsplit()
   # starts, or takes a split
   {
      $this->timeend = $this->microtime_float();
      $this->timesplit = $this->timeend - $this->timestart;
      $this->timestart = $this->microtime_float();
      $split = 0;
      if ($this->timesplit > 0) {
         $split = $this->timesplit;
      }
      return $split;
   }

} /* end timerObject */

/*
Return an array of the pids of the processes that are running for the specific command
e.g.
  returnPids('myprocess.php');
*/
function returnPids($command) {
   exec("ps -C $command -o pid=",$pids);
   foreach ($pids as $key=>$value) $pids[$key]=trim($value);
   return $pids;
}

function returnCommands($command, $like='') {
  exec("ps -C $command -o command=",$cmds);
  $ret = array();
  foreach ($cmds as $key=>$value) {
    if (strlen($like) > 0) {
      //error_log("Searching for $like in $value -> " . strpos($value, $like));
      if (!(strpos($value, $like) === FALSE)) {
        $ret[$key]=trim($value);
      }
    } else {
      $ret[$key]=trim($value);
    }
  }
  return $ret;
}    

/*
Returns an array of the pids for processes that are like me, i.e. my program running
*/
function returnMyPids() {
   return returnPids(basename($_SERVER["SCRIPT_NAME"]));
}
?>