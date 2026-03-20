<?php


function mathProcessor2( $sEquation, $arData) {
/* USAGE:
   There are two parameters you pass the function. One being a string equation such as:

   $sEquation = '100*([field1]+[field2])/40*([field3]+[field4])';

   And the second parameter is an associative array of data. The keys for this associative arrays are items you can use in the above math expression to specify replacements. So if I have an associative array like:

   $arData = array( 'field1' => 1,
   'field2' => 2,
   'field3' => 3,
   'field4' => 4);

   it would change the above expression to:
   100*(1+2)/40*(3+4)

*/



    // Split the equation on open and close parenthesis characters. Since we're going to be checking for the outmost
    // parenthesized equation, we're going to need to figure out which is is and recombine the inside equations.
    $arContents = preg_split('/[\(\)]/', $sEquation);
    // This matches all open and close parenthesis in the string. We're going to need to
    // go through this returned array and examine which set of parenthesis forms outmost 
    // parenthesized expressions
    preg_match_all('/[\(\)]/', $sEquation, $arParen);
    
    // To determine the outmost parenthesized equations (since we could have nested parenthesis)
    // we use a variable 'stack' to keep track of what level of nested parenthesis we're at. Everytime
    // we encounter
    $stack = 0;
    $start_index = 0;
    // If there are parenthesis then we should actually perform the search for the outmost equations.
    if (sizeof($arParen)) {
        for ($i = 0; $i < sizeof($arParen[0]); $i++) {
            // Everytime we find an open parenthesis we need to increment the stack variable.
            // If the stack is at its lowest level, save the index of the open parenthesis so we know where to
            // combine again when we find the matching close parenthesis.
            if ($arParen[0][$i] == '(') {
                if ($stack == 0) { $start_index = $i; }
                $stack++;
            }
            // If we hit a close parenthesis character, we need to decrement the stack. If the stack value reaches
            // 0 again, this means we've hit a top level parenthesis and we need to recursively call the function
            // and tell it to evaluate the inner expression.
            elseif ($arParen[0][$i] == ')') {
                $stack--;
                if ($stack == 0) {
                    $sSubEq = '';
                    // Here we recombine the inside equation. Since this can also include nested parenthesis, we need
                    // to take care of that.
                    for ($j = $start_index+1; $j <= $i; $j++) {
                        $sSubEq .= $arContents[$j];
                        if ($j != $i) {
                            $sSubEq .= $arParen[0][$j];
                        }
                    }
                    // After the function is recursively called enough times, it will evaluate the parenthesized expression
                    // and return only numerical values which we can use to replace the whole parenthesis equation.
                    $sReplacedVal = mathProcessor2( $sSubEq, $arData );
                    $sEquation = str_replace('('.$sSubEq.')', $sReplacedVal, $sEquation);
                }
            }
        }
    }
    
    // Thus by this point all we're left with is numbers and possible ID values.
    // All that needs to be done at this point is to replace the IDs with their actual
    // values from $arData.
    /*
    # original code 
    # assumes that variables are given in brackets []
    preg_match_all('/\[([^\]]+)\]/', $sEquation, $arRepVals);
    
    for ($i = 0; $i < sizeof($arRepVals[0]); $i++) {
        $sEquation = str_replace($arRepVals[0][$i],$arData[$arRepVals[1][$i]], $sEquation);
    }
    */
    # modified code, by Robert Burgholzer, rburghol@vt.edu, 7-26-2007
    # does not use brackets, on the logic that if we simply order our variable names 
    # by length (descending), and that variable names are not numbers (but may contain numbers) 
    # then we can substitute all of the variables in and then evaluate based on numbers only
    $arDataLength = array();
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
    # end code modification
    
    // Now we should strictly have a math equation. We can evaluate all the supported operators
    
    // Use Math Expression passed in
        
        // Or, preferable, use the Math_Expression engine!
        
    return $sEquation;
    
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