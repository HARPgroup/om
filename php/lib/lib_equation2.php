<?php
include_once '/var/www/html/lib/phpmath/Math_Expression/Expression.php';
$t1 = microtime(true);

#require_once 'phpmath/Math_Expression/Structures/Math_Expression_Structure_Real.php';
#Math_Expression::registerStructure(new Math_Expression_Structure_Real);
    
class Equation extends modelSubObject {
   var $arData = array();
   var $logdata = array();
   var $vars = array();
   var $equation = '';
   var $result;
   var $name = '';
   var $description = '';
   var $component_type = 3;
   var $value_dbcolumntype = 'float8'; 
   var $debug = 0;
   var $defaultval = 0;
   var $nanvalue = 0; # value to use if result it NAN
   var $strictnull = 0; # evaluate equation as null if any input is null? 0/1 (false/true)
   var $nonnegative = 0; # Make minimum value = $minvalue
   var $minvalue = 0; # Default Minimum value = 0
   var $reportstring = '';
   var $errorstring = '';
   var $debugstring = '';
   var $debugmode = 0; # 0-store in string, 1-print debugging info to stderr, 2-print out to stdout
   var $precision = 14; # should correspond to the precision set by php.ini
   var $loggable = 1; // can log the value in a data table
   var $numnull = 0;
   var $engine = 'mathProcessor2';
   var $json2d = TRUE;

   
   function init() {
      $this->vars = array();
      $this->logdata = array();
      $this->parseOperands();
      array_push($this->logdata, $this->vars);
      $this->debugstring = '';
      if ($this->debug) {
         $this->logDebug("Equation: $this->equation, Array Data:");
         $this->logDebug(print_r($this->arData, 1));
         $this->logDebug("Local Values:");
         $this->logDebug(print_r($this->vars, 1));
         $this->logDebug("<br>");
      }
      #$this->logError();
      $this->value = $this->defaultval;
   }
   
   function setSimTimer($simtimer) {
      
   }
   
   function parseOperands() {
      $ops = array();
      $arOperands = preg_split('/[\)\(\+\-\*\/\\^]/',$this->equation);
      foreach ($arOperands as $thisop) {
         $thisop = ltrim(rtrim($thisop));
         if (!is_numeric($thisop) and (strlen($thisop) > 0)) {
            # not numeric, must be a variable, not a constant
            # check to see if it has been added already
            if (!in_array($thisop, $this->vars)) {
               $this->vars[] = $thisop;
            }
         }
      }
   }
   

   function getProp($propname, $view = '') {
      //error_log("Equation Property requested: $propname, $view ");
      if ($view == '') {
         $view = 'equation';
      }
      $localviews = array('equation');
      if (!in_array($view, $localviews)) {
         return parent::getProp($propname, $view);
      } else {
         switch ($view) {
            case 'equation':
               return $this->equation;
            break;
         }
      }
   }
   
   function setProp($propname, $propvalue, $view = '') {
     if ( ($propname == 'default') ) {
       parent::setProp('defaultval', $propvalue, $view);
     } else {
       parent::setProp($propname, $propvalue, $view);
     }
   }
   
   function finish() {
      if ($this->numnull > 0) {
         $this->logError("Equation $this->name = $this->equation resolved to NULL $this->numnull times.");
         $this->reportstring .= "Equation $this->name = $this->equation resolved to NULL $this->numnull times.";
         error_log("Equation $this->name = $this->equation resolved to NULL $this->numnull times.");
      }
      parent::finish();
   }
   
   function step() {
      $this->evaluate();
      $this->postStep();
   }
   
   function postStep() {
      $this->writeToParent();
   }
   
   function setDebug($thisdebug, $thisdebugmode = -1) {
      $this->debug = $thisdebug;
   }
   
   function setState() {
      
   }
   
   function wake() {
      if (!is_array($this->wvars)) {
         $this->wvars = array();
      }
   }
   
   function logError($errorstring) {
      switch ($this->debugmode) {
         case 0:
         # do nothing
         break;
         
         case 1:
         # print to stderr
         error_log($this->errorstring);
         break;
         
         case 2:
         # print to stdout
         print($this->errorstring);
         break;
      }
   }
   
   function evaluate() {
      
      $this->errorstring = '';
      
      if ($this->debug) {
         $this->logDebug("<br> Evaluating: $this->name = ");
      }
      if ($this->debug) {
         $this->logDebug(" <br>Trying to solve $this->equation <br>");
         //error_log(" Trying to solve $this->equation <br>");
         $this->logDebug(" Variables: " . print_r($this->vars,1));

      }
      foreach ($this->vars as $tvar) {
         if ( ($this->arData[$tvar] === NULL) 
            or ($this->arData[$tvar] === 'NULL') 
            or (strtolower($this->arData[$tvar]) == 'null') 
            or ($this->arData[$tvar] === '') 
            or (strlen(trim($this->arData[$tvar])) == 0) 
         ) {
         #if ( ($this->arData[$tvar] === NULL) ) {
            if ($this->debug) {
               $this->logDebug("NULL value found for: $tvar <br>");
            }
            if ($this->strictnull) {
               $this->result = NULL;
               return;
            } else {
               $this->arData[$tvar] = $this->nanvalue;
            }
         } else {
            if ($this->debug) {
               $this->logDebug("Variable $tvar = " . $this->arData[$tvar] . "<br>");
            }
         }
      }
      if ( (strlen(ltrim(rtrim($this->equation))) > 0) ) {
        switch ($this->engine) {
          case 'mathProcessor3':
            $this->result = mathProcessor3( $this->equation, $this->arData, $this->debug);
          break;
          default:
          try {
            $this->result = mathProcessor2( $this->equation, $this->arData, $this->debug);
          } catch (Exception $e) {
            error_log( 'Error Executing Object:',  $this->name);
            error_log( 'Equation:',  $this->equation);
            error_log( 'Data:',  print_r($this->arData,1));
            error_log( 'Caught exception: ',  $e->getMessage());
          }
          break;
        }
      } else {
         $this->result = $this->defaultval;
      }
      if ($this->debug) {
         $this->logDebug(" Checking Validity of result <br>");
      }
      if ($this->result === NULL) {
         if ($this->debug) {
            $this->logError("NULL result in equation $this->name ($this->componentid) on object " . $this->parentobject->name);
         }
         $this->numnull++;
         if ($this->debug) {
            $this->logDebug("NULL result in equation $this->name on object <br>");
         }
         if ($this->numnull == 30) {
           // arbitrary reporting threshold
           error_log("NULL result in equation (>= 30x) $this->equation, from $this->name ($this->componentid) on object " . $this->parentobject->name);
         }
      }
      if (is_nan($this->result) or is_infinite($this->result)) {
         # not a number, 
         if ($this->debug) {
            $this->logDebug(" Result is not valid, using default: $this->nanvalue <br>");
         }
         $this->result = $this->nanvalue;
      }
      if ($this->nonnegative and ($this->result < $this->minvalue)) {
         if ($this->debug) {
            $this->logDebug("Negative value ($this->result), setting $this->name to minimum ($this->minvalue).<br>");
         }
         $this->result = $this->minvalue;
      }
      if ($this->debug) {
         $this->logDebug(" Input Values: " . print_r($this->arData,1));
         $this->logDebug(" Result: $this->result <br>");
      }
   }
}


class Statistic extends Equation {
   
   var $statname = ''; # min, max, mean, median, geomean, stddev,log,log10
   var $operands = ''; # csv list of variables to evaluate
   // * VariableToStack, # to stack, # of timesteps to stack, [size of timestep]
   //   * size of timestep - should always be used if we expect our stack to do something like days, 
   //     not just timesteps.  So if we want to accumulate a 7 day rolling average, we would put:
   //       Qout,7,86400
   //     Then, if timestep were 21600, the stack would actually accumulate 7 x (86400 / 21600) = 28 steps
   var $stack = ''; // a variable for storing multiple occurences
   var $stack_depth;
   var $stack_stat = 0;
   var $stack_last = ''; // last value of the stack
   var $value_dbcolumntype = 'float8'; 
   var $loggable = 1; // can log the value in a data table
   var $json2d = TRUE;
   
   function init() {
      $initvars = explode(',',$this->operands);
      if ($this->debug) {
         $this->logDebug("<br> Operand Inputs " . $this->operands);
         #error_log($this->debugstring);
      }
      $cleanvars = array();
      foreach ($initvars as $tvar) {
         array_push($cleanvars, ltrim(rtrim($tvar)));
      }
      if ($this->debug) {
         $this->logDebug(" in array form: " . print_r($cleanvars,1));
         #error_log($this->debugstring);
      }
      $this->operands = join(',',$cleanvars);
      $this->vars = $cleanvars;
      $this->setUpStack();
   
   }
   
   function wake() {
      parent::wake();
      switch ($this->statname) {
         case 'stack':
         //error_log("Setting stack_last on parent");
         $this->setStateVar('stack_last', 0);
         $this->wvars[] = 'stack_last';
         break;
         
         default:
         //error_log("Not a stack");
         break;
      }
   }
   
   function sleep() {
      parent::sleep();
      $this->stack = '';
   }
   
   function setSimTimer($simtimer) {
      parent::setSimTimer($simtimer);
      $this->setUpStack();
   }
   
   function setUpStack() {
      $this->stack = array();
      if ($this->statname == 'stack') {
         $initvars = explode(',',$this->operands);
         if ($this->debug) {
            $this->logDebug("<br> Initial Stack Parameters " . print_r($initvars,1) . "<br>");
         }
         $defaults = array('mean', -1, $this->dt);
         // not until we have php 5.3.0
         // $stackinfo = array_replace($defaults, array_slice($initvars, 1) );
         $stackinfo = $defaults;
         // Ex: $this->operands = impoundment_Qin,mean,2,86400
         //   $initvars = explode(',', impoundment_Qin,mean,2,86400)
         // $stackinfo[0] = $initvars[1] = mean
         // $stackinfo[1] = $initvars[2] = 2
         // $stackinfo[2] = $initvars[3] = 86400
         for ($i = 1; $i < count($initvars); $i++ ) {
            $stackinfo[$i - 1] = $initvars[$i];
         }
         
         if ($stackinfo[1] > 0) {
            $this->stack_depth = $stackinfo[1] * ($stackinfo[2] / $this->dt);
         } else {
            $this->stack_depth = -1;
         }
         $this->stack_stat = $stackinfo[0];
         if ($this->debug) {
            $this->logDebug("<br> Stack Params are " . print_r($stackinfo,1) . "<br>");
            $this->logDebug("<br> Stack Stat is " . $this->stack_stat . "<br>");
            $this->logDebug("<br> Setting stack vars to " . $initvars[0] . "<br>");
         }
         $this->vars = array($initvars[0]);
      }
   }
   
   function evaluate() {
      if ($this->debug) {
         $this->logDebug("<br> Evaluating: $this->statname <br>");
      }
      
      $opdata = array();
      foreach ($this->vars as $tvar) {
         # check to see if this is a variable or a value, if it is a variable, grab it, if it is a value, use it
         if (is_numeric($tvar)) {
            $varval = $tvar;
         } else {
            if (in_array($tvar, array_keys($this->arData))) {
               $varval = $this->arData[$tvar];
            } else {
               $varval = 'NULL';
            }
         }
         $opdata[$tvar] = $varval;
      }
      if ($this->debug) {
         $this->logDebug("<br> operand Data assembled<br>");
      }
      
      foreach (array_keys($opdata) as $tvarname) {
         $tvar = $opdata[$tvarname];
         if ( ($tvar === NULL) or ($tvar === 'NULL') ) {
            if ($this->debug) {
               $this->logDebug("Operand $tvarname is NULL <br>");
               $this->logDebug("Equation: $this->statname, Available Data:");
               if (isset($this->arData['the_geom'])) {
                  $this->arData['the_geom'] = 'Geometry Hidden';
               }
               $this->logDebug($this->arData);
               $this->logDebug("Selected Values:");
               $this->logDebug($this->vars);
               $this->logDebug("<br>");
            }
            if ($this->strictnull) {
               $this->result = NULL;
               return;
            } else {
               $this->result = $this->nanvalue;
               return;
            }
         }
      }
      
      if ($this->debug) {
         $this->logDebug("<br> $this->statname of " . print_r(array_values($opdata),1));
         $this->logDebug("<br> $this->statname of " . print_r($opdata,1));
         #error_log($this->debugstring);
      }
      
      switch ($this->statname) {
         
         case 'stack':
         // push a value onto the stack
         // stack params are: varname,function,stack_depth,stack_resolution
         // varname - variable to stack evaluation
         // *function - one of the available stats (default mean)
         // *stack_depth - how many values to stack (-1 for unlimited)
         // *stack_resolution - relates in terms of seconds dt=default)
         // *stack_depth and stack_resolution combine to determine the actual number of items stored on the stack
            // actual number on stack = stack_depth * (stack_resolution / dt)
         // * = non-mandatory
            $opk = array_keys($opdata);
            if ($this->debug) {
               $this->logDebug("putting " . $opdata[$opk[0]] . " on the stack<br>");
            }
            array_push($this->stack, $opdata[$opk[0]]);
            if ($this->debug) {
               $this->logDebug("Current Stack Depth = " . count($this->stack) . ", max depth = $this->stack_depth <br>");
            }
            //error_log("Current Stack Depth = " . count($this->stack) . ", max depth = $this->stack_depth <br>");
            while ( ($this->stack_depth > 0) and (count($this->stack) > 0) and (count($this->stack) > $this->stack_depth) ) {
               $stackdump = array_shift($this->stack);
               if ($this->debug) {
                  $this->logDebug("$this->name Shifting $stackdump off the stack ");
               }
               $this->setStateVar('stack_last', $stackdump);
            }
            if ($this->debug) {
               if (count($this->stack) < 25) {
                  $this->logDebug($this->stack);
               }
            }
            //error_log("$this->name Current Stack Depth = " . count($this->stack) . ", max depth = $this->stack_depth <br>");
            $res = $this->evalStat($this->stack_stat, $this->stack);
            //error_log(" = $res ");
         break;
         
         default:
         # default to mean
            $res = $this->evalStat($this->statname, $opdata);
         break;
      }
      $this->result = $res;
      if ($this->debug) {
         $this->logDebug(" = $this->result <br>");
         #$this->logError($this->debugstring);
      }
   }
   
   function evalStat($stat, $opdata) {
      
      if ($this->debug) {
         $this->logDebug(" Calculating ($stat) for $this->name <br>");
      }
      
      switch ($stat) {
         
         case 'min':
            $res = min(array_values($opdata));
         break;
         
         case 'max':
            $res = max(array_values($opdata));
         break;
         
         case 'avg':
            $res = mean(array_values($opdata));
         break;

         case 'mean':
            $res = mean(array_values($opdata));
         break;
         
         case 'median':
            $res = median(array_values($opdata));
         break;
         
         case 'stddev':
            $res = stddev(array_values($opdata));
         break;
         
         case 'log':
            $opk = array_keys($opdata);
            $res = log($opdata[$opk[0]]);
         break;
         
         case 'log10':
            $opk = array_keys($opdata);
            $res = log($opdata[$opk[0]],10);
         break;
         
         case 'sum':
            $res = array_sum(array_values($opdata));
         break;
         
         case 'pow':
            $opk = array_keys($opdata);
            $res = pow($opdata[$opk[0]],$opdata[$opk[1]]);
            if ($this->debug) {
               $this->logDebug("pow (" . $opdata[$opk[0]] . ", " . $opdata[$opk[1]] . " = $res <br>");
            }
         break;
         
         default:
         # default to mean
            $res = mean(array_values($opdata));
         break;
      }
      
      return $res;
   }
}



function mathProcessor2( $sEquation, $arData, $debug = 0) {
/* USAGE:
   There are two parameters you pass the function. One being a string equation such as:

   $sEquation = '100*(field1+field2)/40*(field3+field4)';

   And the second parameter is an associative array of data. The keys for this associative arrays are items you can use in the above math expression to specify replacements. So if I have an associative array like:

   $arData = array( 'field1' => 1,
   'field2' => 2,
   'field3' => 3,
   'field4' => 4);

   it would change the above expression to:
   100*(1+2)/40*(3+4)
   
   then return for evaluation by the Math_Expression module

*/

    # original code 
    # assumes that variables are given in brackets []
    preg_match_all('/\[([^\]]+)\]/', $sEquation, $arRepVals);
    
    for ($i = 0; $i < sizeof($arRepVals[0]); $i++) {
        $sEquation = str_replace($arRepVals[0][$i],$arData[$arRepVals[1][$i]], $sEquation);
    }
    
    # modified code, by Robert Burgholzer, rburghol@vt.edu, 7-26-2007
    # does not use brackets, on the logic that if we simply order our variable names 
    # by length (descending), and that variable names are not numbers (but may contain numbers) 
    # then we can substitute all of the variables in and then evaluate based on numbers only
    $arDataLength = array();
    $orig = $sEquation;
    foreach (array_keys($arData) as $thisvar) {
       $vlen = strlen($thisvar);
       # check to see if any variables with this length have been added
       $arDataLength[$vlen][$thisvar] = $arData[$thisvar];
    }
    # sort these by the length of their keys
    ksort($arDataLength, SORT_NUMERIC);
    # reverse the order, going now from highest to lowest
    $arSorted = array_reverse($arDataLength);
    
    foreach ($arSorted as $thisLengthData) {
       foreach(array_keys($thisLengthData) as $thisvar) {
          $sEquation = str_replace($thisvar,$arData[$thisvar], $sEquation);
       }
    }
    // substitute + for double --
    $sEquation = str_replace('--', '+',$sEquation);  
    $sEquation = str_replace('- -', '+',$sEquation);  

    # end code modification
    
    // Now we should strictly have a math equation. We can evaluate all the supported operators
    
    // Use Math Expression passed in
        
        // Or, preferable, use the Math_Expression engine!
        
   if ( (!preg_match("/[a-df-zA-DF-Z]/", $sEquation)) and (strlen(trim($sEquation)) > 0) ) {
     //if (strpos($sEquation,'Array') === FALSE) {
      //error_log("Expression \"$sEquation\" looks valid  ");
      $expression = new Math_Expression($sEquation);
      //error_log("Expression created");
      try {
        //$result = @$expression->evaluate();
        $result = $expression->evaluate();
      } catch (Math_Expression_Exception_Fatal $e) {
        error_log( 'Error Executing Equation:',  $orig);
        error_log( 'Subbed:' . $sEquation);
        error_log( 'Caught exception: ',  $e->getMessage());
      }
      if ($result === FALSE) {
        error_log( 'Error Executing Equation:' . $orig);
        error_log( 'Subbed:' . $sEquation);
        if ($debug) {
           //error_log("Error processing: $orig -> $sEquation ");
        }
        return NULL;
      } else {
        if ($debug) {
           //error_log("Equation: " . $sEquation . " = " . $result->__toString() . " (" . $result . ")");
        }
        return $result->__toString();
      }
    } else {
       if ($debug) {
          //error_log("Error: array variable in Equation: $orig -> $sEquation ");
       }
      return NULL;
   }
    
}

function mathProcessor3($sEquation, $arData, $debug = 0) {
  $result = FALSE;
  # Robert Burgholzer, rburghol@vt.edu, 12-31-2019
  # does not use brackets, on the logic that if we simply order our variable names 
  # by length (descending), and that variable names are not numbers (but may contain numbers) 
  # then we can substitute all of the variables in and then evaluate based on numbers only
  $arDataLength = array();
  $orig = $sEquation;
  foreach (array_keys($arData) as $thisvar) {
     $vlen = strlen($thisvar);
     # check to see if any variables with this length have been added
     $arDataLength[$vlen][$thisvar] = $arData[$thisvar];
  }
  # sort these by the length of their keys
  ksort($arDataLength, SORT_NUMERIC);
  # reverse the order, going now from highest to lowest
  $arSorted = array_reverse($arDataLength);
  
  foreach ($arSorted as $thisLengthData) {
     foreach(array_keys($thisLengthData) as $thisvar) {
        $sEquation = str_replace($thisvar,$arData[$thisvar], $sEquation);
     }
  }
  # end variable substitution
  // Remove whitespaces
  $equation = preg_replace('/\s+/', '', $sEquation);
    // substitute + for double --
    // and - for -+ and +-
  $equation = str_replace('--', '+',$equation); 
  $equation = str_replace('+-', '-',$equation); 
  $equation = str_replace('-+', '-',$equation); 


  $number = '(?:\d+(?:[Ee,.]\d+)?|pi|π)'; // What is a number
  $functions = '(?:sinh?|cosh?|tanh?|abs|acosh?|asinh?|atanh?|exp|log10|deg2rad|rad2deg|sqrt|ceil|floor|round)'; // Allowed PHP functions
  $operators = '[+\/*\^%-]'; // Allowed math operators
  $regexp = '/^(('.$number.'|'.$functions.'\s*\((?1)+\)|\((?1)+\))(?:'.$operators.'(?2))?)+$/'; // Final regexp, heavily using recursive patterns
  error_log("Final eq: $equation");
  if (preg_match($regexp, $equation)) {
    $equation = preg_replace('!pi|π!', 'pi()', $equation); // Replace pi with pi function
    eval('$result = ' . $equation . ';');
  } else {
    $result = false;
  }
  return $result;
}

# Statistical functions

function mean ($a)
{
  //variable and initializations
  $the_result = 0.0;
  $the_array_sum = array_sum($a); //sum the elements
  $number_of_elements = count($a); //count the number of elements

  //calculate the mean
  $the_result = $the_array_sum / $number_of_elements;

  //return the value
  return $the_result;
}

function median ($a)
{
  //variable and initializations
  $the_median = 0.0;
  $index_1 = 0;
  $index_2 = 0;

  //sort the array
  sort($a);

  //count the number of elements
  $number_of_elements = count($a);

  //determine if odd or even
  $odd = $number_of_elements % 2;

  //odd take the middle number
  if ($odd == 1)
  {
    //determine the middle
    $the_index_1 = $number_of_elements / 2;

    //cast to integer
    settype($the_index_1, "integer");

    //calculate the median
    $the_median = $a[$the_index_1];
  }
  else
  {
    //determine the two middle numbers
    $the_index_1 = $number_of_elements / 2;
    $the_index_2 = $the_index_1 - 1;

    //calculate the median
    $the_median = ($a[$the_index_1] + $a[$the_index_2]) / 2;
  }

  return $the_median;
}

function stddev ($a)
{
  //variable and initializations
  $the_standard_deviation = 0.0;
  $the_variance = 0.0;
  $the_mean = 0.0;
  $the_array_sum = array_sum($a); //sum the elements
  $number_elements = count($a); //count the number of elements

  //calculate the mean
  $the_mean = $the_array_sum / $number_elements;

  //calculate the variance
  for ($i = 0; $i < $number_elements; $i++)
  {
    //sum the array
    $the_variance = $the_variance + ($a[$i] - $the_mean) * ($a[$i] - $the_mean);
  }

  $the_variance = $the_variance / $number_elements;

  //calculate the standard deviation
  $the_standard_deviation = pow( $the_variance, 0.5);

  //return the variance
  return $the_standard_deviation;
}
?>
