<?php
// lib_hydro.php

function green_ampt ($R ,$dt,$thetai, $thetasat, $Ks, $depth, $F, $Inf, $Sav, $tol = 0.00001) {

   $M = $thetasat - $thetai;
   $flast = $Inf;
   $fguess = $flast;
   $i = 4;


   if ($F == 0) {
      $flast = $R;
    }

    $Fwg = $F + $flast * $dt/3600.0;
    $fcalc = $Ks*(1.0+$M*$Sav/$Fwg);

    if ($fcalc > $R) {
      $frate = $R;
    } else {
      for($i = 0; (($fcalc - $fguess)*($fcalc - $fguess) > $tol) ;$i++) {
          $fguess = $fcalc;
          $Fwg = $F + $flast * $dt/3600.0;
          $fcalc = $Ks*(1.0+$M*$Sav/$Fwg);
        }
        $frate = $fcalc;
    }
    if ($fcalc > $R) {
      $frate = $R;
    }

    if ($frate < 0) {
       $frate = 0.0;
    }

    return $frate;

}


function kinwave($dep, $Qin, $Qout, $R, $I, $S, $length, $width, $dt, $n, $units, $debug) {
   $A = $length * $width;
   $tol = 0.0001;
   $lhs = 1.0;
   $rhs = 0.0;
   $j = 0;
   $diff = 1.0;
   $th = $dt/3600.0;

   $dguess = $dep + ($R - $I) * $th;
   $ldguess = 0.0;
   $lastdiff = 0.5;
   switch ($units) {
    case 1:
       $manco = 1.0; # SI
    break;

    case 2:
       $manco = 1.49; # English
    break;

    default:
       $manco = 1.0; # SI
    break;
   }

/* given d(t-dt), Qin (volume into cell in last it.), Qout (volume out of cell in last it.), R (rainfall rate), I (infiltration rate), S (slope), A (Area) calculate d and velocity and Qout, based on given Area (A):
   Qout = d * w * v, where w = sqrt(area) for all cells, regardless of flow direction.
   d = d[t-dt] + (Qin - Qout)/A + R - I : therefore;
      Qout = ( d[t-dt] - d + Qin/A + R - I ) * A
   v = 1.49*(d^2/3 * S^1/2) / n ; therefore:
      Qout = 1.49 * w * d^(5/3) * S^(1/2) / n
   Finally:
      ( d[t-dt] - d + Qin/A + R - I ) * A = 1.49 * w * d^(5/3) * S^(1/2) / n
     Must balance.

   Usage: kinWave(d,Qin,Qout,R,I,S,A,dt,n)
*/
   for ($j = 0;( (abs($diff) > $tol) && ($j < 5000) ); $j++) {

      $lhs = ( ($dep - $dguess)/12.0 + $Qin/($A) + $th*($R - $I)/12.0) *$A;
      $rhs = $th*$manco * $width * pow(($dguess/12.0),(5.0/3.0))*pow($S,(0.5)) / $n;
      $diff = $lhs - $rhs;
      # increasing $dguess will reduce the lhs and increase the rhs
      # thus, if $diff < 0 then $dguess should be reduced
      # if $diff > 0 then $dguess should be increased
      # if $diff is a different sign than $lastdiff then  our new guess
      #    should lie between the current guess and the last guess
      # if the last 2 guesses have the same signs then the new guess
      #    should be outside of the range of our last 2 guesses
      # if the new guess should be outside the range of the last 2 then
      #    we can obtain the new guess by squaring the ratio of our last 2 guesses
      #    the direction of our new guess (greater or lesser) should determine which
      #    of the last two guesses is the numerator, and which the denominator
      # if the new guess is between the last 2 guesses then we should simply
      #    choose the mean of the last two guesses as the new guess

      if ($debug) { print(" <b>$j:</b> D= $dep, R= $R, I= $I, $lhs, $rhs, dguess = $dguess, ldguess = $ldguess, diff = $diff, lastdiff = $lastdiff <br>\n"); }
      if ($debug) { print(" lhs = ( ($dep - $dguess)/12.0 + $Qin/($A) + $th*($R - $I)/12.0) *$A <br>\n"); }
      if ($debug) { print(" rhs = $th * $manco * $width * pow(($dguess/12.0),(5.0/3.0))*pow($S,(0.5)) / $n; <br>\n"); }

      # get sign of $diff and $lastdiff
      # no concern about division by zero because we would have exited the loop if
      # $lastdiff were less than tolerance which is non-zero
      if ($diff <> 0) {
         if ($j == 0) {
            # first time through, make a standard adjustment to the guess
            $lastdiff = $diff;
            $ldguess = $dguess;
            if ($debug) { print("Making our first guess. <br>\n"); }
            if ($diff < 0) {
               if ($debug) { print("Decreasing our guess. <br>\n"); }
               $dguess = $dguess * 0.9;
            } else {
               if ($debug) { print("Increasing our guess. <br>\n"); }
               $dguess = $dguess * 1.1;
            }
         } else {
            # stash the current guess
            $thisguess = $dguess;
            # make an educated new guess based on last guess results
            $lsign = $lastdiff/abs($lastdiff);
            $dsign = $diff/abs($diff);
            if ($debug) { print("dsign = $dsign, lsign = $lsign <br>\n"); }

            if ($dsign <> $lsign) {
               # choose a new guess between the last two guesses
               if ($debug) { print("Last 2 guesses have different signs. <br>\n"); }
               $neghalf = min(array($lastdiff, $diff));
               $minguess = min(array($ldguess, $dguess));
               $interval = abs($lastdiff - $diff);
               $frac = 1.0 - (abs($neghalf) / $interval);
               if ($debug) {  print(" frac = 1.0 - (abs($neghalf) / $interval) = $frac; <br>\n"); }
               $dguess = $minguess + abs($dguess - $ldguess) * $frac;
               if ($debug) { print(" dguess = $minguess + abs($dguess - $ldguess) * $frac = $dguess; <br>\n"); }
               # set last guess to whichever was closer
               if (abs($lastdiff) > abs($diff)) {
                  $ldguess = $thisguess;
                  $lastdiff = $diff;
               }
            } else {
               if ($debug) {  print("Last 2 guesses have the same signs. <br>\n"); }
               # the last two guesses have the same sign, so the same type
               # of change should occur, but with a larger magnitude
               if ($dsign < 0) {
                  # the new guess should decrease by a greater magnitude
                  # than tried previously
                  $num = min(array($ldguess, $dguess));
                  $denom = max(array($ldguess, $dguess));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Decreasing our guess by factor $gfact. <br>\n"); }

               } else {
                  $num = max(array($ldguess, $dguess));
                  $denom = min(array($ldguess, $dguess));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Increasing our guess by factor $gfact. <br>\n"); }
                  # the new guess should increase by a greater magnitude
                  # than tried previously
               }
               if ($debug) {  print("Changing our guess by factor $gfact. <br>\n"); }
               $dguess = $gfact * $ldguess;
            }
         }
      }
   }

  # if ($dep == 0.0) {
      # no flow when depth is zero
  #    $dguess = 0;
  # }

   $Qnew = ($manco * $dguess/12.0 * pow($dguess/12.0,(5.0/3.0)) * pow($S,(0.5)))/$n;
   if ($debug) { print("Finished: Qnew = $Qnew <br>\n"); }

   return array($Qnew, $dguess);
}


function typeii_storm($totald, $t, $dur) {

   $d = 0.0;
/*
 given total depth of rainfall during the event, the current time into the event, and the duration of the event, calculate the SCS type II rainfall curve.
   Usage: typeII_storm(totaldepth,t,dur)
  */

   $tr = 12.0 - $dur/2.0;
   $pr = 0.5 + ( ($tr/24.0) * pow( 24.04/(2 * abs($tr) + 0.04), 0.75));
   $tf = 12.0 + $dur/2.0;
   $pf = 0.5 + ( ($tf/24.0) * pow( 24.04/(2 * abs($tf) + 0.04), 0.75));
   $tn = 12.0 + $t - $dur/2.0;
   $p = 0.5 + ( ($tn/24.0) * pow( 24.04/(2 * abs($tn) + 0.04), 0.75));

   $d = totald * ( ($p - $pr) / ($pf - $pr) );

   return $d;
}



function storagerouting($I1, $I2, $O1, $W, $b, $Z, $ct, $S1, $slength, $slope, $dt, $n, $units, $debug, $maxits = 100) {
# returns all quantities of interest - Velocity, Storage, depth and Flow
/* dt is interval time in seconds */
/* $ct is channel type - not yet active */
 /* StorageRoutingQout, part of a suite of storage routing routines including:
       StorageRoutingD, StorageRoutingS
    Inputs:
       stream geometry:
          base(b),Z (trap, rect or triangle for now, later maybe pass shape in)
          length
       Inflow.old v(I1), Outflow.old(O1), Inflow.new(I2), depth.old(d1)
    calculate:
       Storage.old(S1) from depth, and geometry inputs (length term equates volume)
       Outflow.new(O2)
    Return:
       O2 (i.e. new Qout)
    Usage: StorageRoutingQout(Iold,Qin,Qout,base,Z,channeltype,Storage,length,slope,toSeconds(dt),n)
 */
 switch ($units) {
    case 1:
       $manco = 1.0; # SI
    break;

    case 2:
       $manco = 1.49; # English
    break;

    default:
       $manco = 1.0; # SI
    break;
 }


   /* d1 will be calculated from S1, length and geometry */
   $tol = 0.001; /* set tolerance for equation solution */
   /* if (channeltype == 2) { ... ; to come, for now assume trapezoidal */
   $O2 = 0.0;

   $A = $S1 / $slength; /* cross-sectional area of channel based on last storage value */
   /* solve quadratic formula */
   /* since eqn. for channel area is of the form A = bd + Zd^2 quadratic formula is then:
         Zd^2 + bd - A = 0,
      and quadratic soln. is:
         x = ( -b +/- (b^2 - 4ac)^0.5 ) / 2a; where x = Depth, a = Z, b = b, c = -A
      thus, since b is always > 0, and ((b^2 - 4ac)^0.5) > b, only + soln. will work
   */
   #$debug = 1;

   $d1 = (-1.0*$b + sqrt( (pow($b,2.0) + 4.0*$A*$Z) ) ) / (2.0 * $Z);
   $d2 = $d1 + 0.1;
   # Based on continuity equation
   # Storage = Begin_S + Inflows - Outflows
   # Continuity rearranged with flow rates added (I and O in volume/time)
   #    1) S2 = S1 + Imean*dt - Omean*dt
   #    2) Omean = (O1 + O2)*dt/2 = O1*dt/2 + O2*dt/2
   #    3) Imean = (I1 + I2)*dt/2
   # Substituting eqs. 2) and 3) from above into 1) yields:
   #    4) S2 = S1 + (I1 + I2)*dt/2 - O1*dt/2 - O2*dt/2
   # Rearranging 4) so that all unknowns (S2 and O2) are on the same side yields
   #    5) S2 + O2*dt/2 = S1 - O1*dt/2 + (I1 + I2)*dt/2
   # if we have KNOWN withdrawals we can include them on the right side of the equation
   # making 5) iinto:
   #   6) S2 + O2*dt/2 = S1 - O1*dt/2 + (I1 + I2)*dt/2 - W*dt
   # The right side ( $eq1 ) does not change, since all are known
   # the left side components, O2 and S2 can be solved iteratively using
   # Manning's equation:
   #    V = 1.49/n * R^2/3 * slope^0.5
   # and a series of guesses
   # since the depth of flow determines velocity, cross-sectional area,
   # and therefore storage volume and Outflow
   $eq2 = $S1 - ($O1/2.0)*$dt + (($I1 + $I2)*$dt)/2.0 - $W*$dt;
   $eq1 = $eq2 + 1.0;
   if ($eq2 <= 0) {
      $O2 = $S1/$dt;
      $S2 = 0.0;
      $eq1 = 0.0;
      $eq2 = 0.0;
      $d2 = 0.0;
      $V2 = 0.0;
   }
   for ($i = 0; (abs($eq1 - $eq2) > $tol); $i++) {
      $A2 = $b*$d2 + $Z*pow($d2,2.0);
      $S2 = $slength * $A2;
      $p = $b + 2.0*$d2*pow((pow($Z,2.0) + 1.0),0.5);
      $R = $A2 / $p;
      $O2 = $A2 * ($manco/$n) * pow($R,2.0/3.0) * pow($slope,0.5);
      $V2 = ($manco/$n) * pow($R,2.0/3.0) * pow($slope,0.5);
      $eq1 = $S2 + $O2*$dt/2.0;

      # old method
      /*
      if ($eq2 > $eq1) {
         $d2 = $d2*1.01;
      } else {
         $d2 = $d2*0.99;
      }
      */

      if ($i > $maxits) {
         # this tells us that we are having trouble finding a solution
         # we first try to adjust the tolerance a slight bit, if this does not work, 
         # then we assume that infllow equals outflow, and simply call for the initial Storage
         # routine to give us a storage value, then call this routine again
         if ($i < (2.0 * $maxits)) {
            # try adjusting the tolerance
            $tol = $tol * 1.1;
         } else {
            # set these to be steady state by setting outflow = inflow
            $Sest = storageroutingInitS($I2, $b, $Z, $ct, $slength, $slope, $dt, $n, $units, $debug, $maxits);
            list ($V2, $O2, $d2, $S2) = storagerouting($I2, $I2, $I2, $W, $b, $Z, $ct, $Sest, $slength, $slope, $dt, $n, $units, $debug, $maxits);
            break;
         }
      }

      # test new method for quicker solution
      $diff = $eq2 - $eq1;

      if ($debug) { print(" <b>$i:</b> D= $d1, $eq1, $eq2, dguess = $d2, ldguess = $ld2, diff = $diff, lastdiff = $lastdiff <br>\n"); }
      if ($debug) { print(" eq1 = $S2 + $O2*$dt/2.0 <br>\n"); }
      if ($debug) { print(" eq2 = $S1 - ($O1/2.0)*$dt + (($I1 + $I2)*$dt)/2.0; <br>\n"); }

      # get sign of $diff and $lastdiff
      # no concern about division by zero because we would have exited the loop if
      # $lastdiff were less than tolerance which is non-zero
      if ($diff <> 0) {
         if ($i == 0) {
            # first time through, make a standard adjustment to the guess
            $lastdiff = $diff;
            $ld2 = $d2;
            if ($debug) { print("Making our first guess. <br>\n"); }
            if ($diff < 0) {
               if ($debug) { print("Decreasing our guess. <br>\n"); }
               $d2 = $d2 * 0.9;
            } else {
               if ($debug) { print("Increasing our guess. <br>\n"); }
               $d2 = $d2 * 1.1;
            }
         } else {
            # stash the current guess
            $thisguess = $d2;
            # make an educated new guess based on last guess results
            $lsign = $lastdiff/abs($lastdiff);
            $dsign = $diff/abs($diff);
            if ($debug) { print("dsign = $dsign, lsign = $lsign <br>\n"); }

            if ($dsign <> $lsign) {
               # choose a new guess between the last two guesses
               if ($debug) { print("Last 2 guesses have different signs. <br>\n"); }
               $neghalf = min(array($lastdiff, $diff));
               $minguess = min(array($ld2, $d2));
               $interval = abs($lastdiff - $diff);
               $frac = 1.0 - (abs($neghalf) / $interval);
               if ($debug) {  print(" frac = 1.0 - (abs($neghalf) / $interval) = $frac; <br>\n"); }
               $d2 = $minguess + abs($d2 - $ld2) * $frac;
               if ($debug) { print(" d2 = $minguess + abs($d2 - $ld2) * $frac = $d2; <br>\n"); }
               # set last guess to whichever was closer
               if (abs($lastdiff) < abs($diff)) {
                  $ld2 = $thisguess;
                  $lastdiff = $diff;
               }
            } else {
               if ($debug) {  print("Last 2 guesses have the same signs. <br>\n"); }
               # the last two guesses have the same sign, so the same type
               # of change should occur, but with a larger magnitude
               if ($dsign < 0) {
                  # the new guess should decrease by a greater magnitude
                  # than tried previously
                  $num = min(array($ld2, $d2));
                  $denom = max(array($ld2, $d2));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Decreasing our guess by factor $gfact. <br>\n"); }

               } else {
                  $num = max(array($ld2, $d2));
                  $denom = min(array($ld2, $d2));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Increasing our guess by factor $gfact. <br>\n"); }
                  # the new guess should increase by a greater magnitude
                  # than tried previously
               }
               if ($debug) {  print("Changing our guess by factor $gfact. <br>\n"); }
               $d2 = $gfact * $ld2;
            }
         }
      }
   }
   if ($debug) {
     error_log("Iterations: $i");
   }

   return array($V2, $O2, $d2, $S2, $i);
}



function storageroutingInitS($Q, $b, $Z, $channeltype, $slength, $slope, $dt, $n, $units, $debug, $maxits = 100)
{
   # estimates the inital value of storage in order to reduce warmup time
   # assumes that the system is at steady state
   #    i.e., that the initial and final storage are equivalent, and that the
   #    initial and final inputs/outputs are all equal
   /* $channeltype = 1 - triangle, 2 - trapezoid, 3 - rectangle */
   $I1 = $Q;
   $I2 = $Q;
   $O1 = $Q;
   $S1 = 0;

    switch ($units) {
       case 1:
          $manco = 1.0; # SI
       break;

       case 2:
          $manco = 1.49; # English
       break;

       default:
          $manco = 1.0; # SI
       break;
    }

 /* StorageRoutingS, part of a suite of storage routing routines including:
       StorageRoutingD, StorageRoutingQout
    Inputs:
       stream geometry:
          base(b),Z (trap, rect or triangle for now, later maybe pass shape in)
          length
       Inflow.old v(I1), Outflow.old(O1), Inflow.new(I2), depth.old(d1)
    calculate:
       Storage.old(S1) from depth, and geometry inputs (length term equates volume)
       Outflow.new(O2)
    Return:
       O2 (i.e. new Qout)
    Usage: StorageRoutingS(Iold,Qin,Qout,base,Z,channeltype,Storage,length,slope,toSeconds(dt),n)
 */

   /* d1 will be calculated from S1, length and geometry */

   $tol = 0.001; /* set tolerance for equation solution */

   /* if (channeltype == 2) { ... ; to come, for now assume trapezoidal */

   $A = $S1 / $slength; /* cross-sectional area of channel based on last storage value */
   $S2 = 0.0;
   /* solve quadratic formula */
   /* since eqn. is of the form A = bd + Zd^2 quadratic formula is then:
         Zd^2 + bd - A = 0, (ax^2 + bx + c = 0)
      and quadratic soln. is:
         x = ( -b +/- (b^2 - 4ac)^0.5 ) / 2a, a = Z, b = b, c = -A
      thus, since b is always > 0, and ((b^2 - 4ac)^0.5) > b, only + soln. will work
      Where x = d, a = Z, b = b, c = -A
   */
   # we simply have to solve mannings for the depth (and therefore area and volume)
   # that correspond to the given flow rate

   $O2 = 0.0;
   $d2 = 1.0; # set an initial, non-zero value for $d2

   for ($i = 0; (abs($Q - $O2) > $tol); $i++) {
      $A2 = $b*$d2 + $Z*pow($d2,2.0);
      $S2 = $slength * $A2;
      $p = $b + 2*$d2*pow((pow($Z,2.0) + 1),0.5);
      $R = $A2 / $p;
      $O2 = $A2 * ($manco/$n) * pow($R,2.0/3.0) * pow($slope,0.5);

      # test new method for quicker solution
      $diff = $Q - $O2;

      if ($i > $maxits) {
         break;;
      }

      if ($debug) { print(" <b>$i:</b> D= $d1, Q = $Q, $Qguess = $O2, dguess = $d2, ldguess = $ld2, diff = $diff, lastdiff = $lastdiff <br>\n"); }
      if ($debug) { print(" Q = $Q <br>\n"); }
      if ($debug) { print(" Qguess = $A2 * ($manco/$n) * pow($R,2.0/3.0) * pow($slope,0.5); <br>\n"); }

      # get sign of $diff and $lastdiff
      # no concern about division by zero because we would have exited the loop if
      # $lastdiff were less than tolerance which is non-zero
      if ($diff <> 0) {
         if ($i == 0) {
            # first time through, make a standard adjustment to the guess
            $lastdiff = $diff;
            $ld2 = $d2;
            if ($debug) { print("Making our first guess. <br>\n"); }
            if ($diff < 0) {
               if ($debug) { print("Decreasing our guess. <br>\n"); }
               $d2 = $d2 * 0.9;
            } else {
               if ($debug) { print("Increasing our guess. <br>\n"); }
               $d2 = $d2 * 1.1;
            }
         } else {
            # stash the current guess
            $thisguess = $d2;
            # make an educated new guess based on last guess results
            $lsign = $lastdiff/abs($lastdiff);
            $dsign = $diff/abs($diff);
            if ($debug) { print("dsign = $dsign, lsign = $lsign <br>\n"); }

            if ($dsign <> $lsign) {
               # choose a new guess between the last two guesses
               if ($debug) { print("Last 2 guesses have different signs. <br>\n"); }
               $neghalf = min(array($lastdiff, $diff));
               $minguess = min(array($ld2, $d2));
               $interval = abs($lastdiff - $diff);
               $frac = 1.0 - (abs($neghalf) / $interval);
               if ($debug) {  print(" frac = 1.0 - (abs($neghalf) / $interval) = $frac; <br>\n"); }
               $d2 = $minguess + abs($d2 - $ld2) * $frac;
               if ($debug) { print(" d2 = $minguess + abs($d2 - $ld2) * $frac = $d2; <br>\n"); }
               # set last guess to whichever was closer
               if (abs($lastdiff) < abs($diff)) {
                  $ld2 = $thisguess;
                  $lastdiff = $diff;
               }
            } else {
               if ($debug) {  print("Last 2 guesses have the same signs. <br>\n"); }
               # the last two guesses have the same sign, so the same type
               # of change should occur, but with a larger magnitude
               if ($dsign < 0) {
                  # the new guess should decrease by a greater magnitude
                  # than tried previously
                  $num = min(array($ld2, $d2));
                  $denom = max(array($ld2, $d2));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Decreasing our guess by factor $gfact. <br>\n"); }

               } else {
                  $num = max(array($ld2, $d2));
                  $denom = min(array($ld2, $d2));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Increasing our guess by factor $gfact. <br>\n"); }
                  # the new guess should increase by a greater magnitude
                  # than tried previously
               }
               if ($debug) {  print("Changing our guess by factor $gfact. <br>\n"); }
               $d2 = $gfact * $ld2;
            }
         }
      }
   }

   return $S2;
}

function solveManningsWithQ($Q, $s, $b, $n, $Z, $d=0.1, $units=1, $debug) {
   if ($Q == 0) {
      return array(0.0, 0.0, 0.0);
   }
   $tol = 0.001 * $Q;
   $maxits = 100;
   switch ($units) {
    case 1:
       $manco = 1.0; # SI
    break;

    case 2:
       $manco = 1.49; # English
    break;

    default:
       $manco = 1.0; # SI
    break;
   }
   // Mannings:
   // V = (1.49/n) * pow(R, 2/3) * pow(s,1/2)
   // Q = V / A 
   // A = b * d + Z*d^2
   // R = A / p
   // p = b + 2.0 * d * (Z^2 + 1.0) ^ 0.5
   // iteratively guess d ( higher d = faster flow, thus if final Q > Qin, decrease d, otherwise increase)
   $error = 2.0 * $tol;
   $its = 0;
   $lastd = 0; // stash the lst guess
   $gfact = 0.1;
   if ($debug) {
      error_log("(abs($error) > $tol) and ($its < $maxits) \n");
   }
   while ( (abs($error) > $tol) and ($its < $maxits) ) {
      $A = $b*$d + $Z*pow($d,2.0);
      $p = $b + 2.0*$d*pow((pow($Z,2.0) + 1.0),0.5);
      $R = $A / $p;
      $V = ($manco/$n) * pow($R,2.0/3.0) * pow($s,0.5);
      $Qguess = $V * $A;
      $error = $Qguess - $Q;
      if ($debug) {
         error_log("
         $A = $b*$d + $Z*pow($d,2.0);
         $p = $b + 2.0*$d*pow((pow($Z,2.0) + 1.0),0.5);
         $R = $A / $p;
         $V = ($manco/$n) * pow($R,2.0/3.0) * pow($s,0.5);
         ");
      }
      if ($debug) {
         error_log ("error: $error \n");
      }
      $dhold = $d;
      if ( num_sign($error) == num_sign($lasterror) ) {
         if ($error > 0) {
            // guess is too high, reduce d
            $d = $d - $gfact * $d;
         } else {
            $d = $d + $gfact * $d;
         }
      } else {
         $gfact = 0.5 * $gfact;
         $d = ($lastd + $d) / 2.0;
      }
      if ($debug) {
         error_log("Error: $error, Last Error: $lasterror, Factor = $gfact (Iterations: $its)\n");
      }
      $lasterror = $error;
      $lastd = $dhold;
      $its++;
   }
   return array($V, $d, $A);
}

function reverseStorageRouting($I1, $S1, $O1, $O2, $b, $Z, $slength, $slope, $dt, $n, $tol, $units, $debug=0, $maxits = 100) {
// takes as inputs previous in-flow (I1) and outflow(O1), current outflow(O2),
// and back-calculates the Inflow at the current time (I2)
// returns all quantities of interest - Velocity, Storage, depth and Flow


   //if ($debug) {
      error_log("solveManningsWithQ($O2, $slope, $b, $n, $Z, $units, $debug);\n");
   //}
   list($V2, $d2, $A2) = solveManningsWithQ($O2, $slope, $b, $n, $Z, 0.1, $units, $debug);
   $S2 = $slength * $A2;
   //$I2 = ( 2.0 / $dt ) * ($S2 - $S1 + ($O1 + $O2)*$dt/2.0 - $I1*$dt/2.0);
   $I2 = (2.0 * ($S2 - $S1) / $dt) + ($O1 + $O2) - $I1;
   $Imean = (( $S2 - $S1) / $dt) + ( ($O1 + $O2) / 2.0);
   if ($debug) {
      error_log("S2 $S2 = $slength * $A2;\n");
      error_log("I2 $I2 = ( 2.0 / $dt ) * ($S2 - $S1 + ($O1 + $O2)*$dt/2.0 - $I1*$dt/2.0); \n");
   }


   if ($I2 < 0) {
      $I2 = 0;
   }

   return array($V2, $I2, $d2, $S2, $A2, $Imean);
}

function storageroutingqout($I1, $I2, $O1, $b, $Z, $ct, $S1, $slength, $slope, $dt, $n, $debug, $maxits = 100) {

/* dt is interval time in seconds */
 /* StorageRoutingQout, part of a suite of storage routing routines including:
       StorageRoutingD, StorageRoutingS
    Inputs:
       stream geometry:
          base(b),Z (trap, rect or triangle for now, later maybe pass shape in)
          length
       Inflow.old v(I1), Outflow.old(O1), Inflow.new(I2), depth.old(d1)
    calculate:
       Storage.old(S1) from depth, and geometry inputs (length term equates volume)
       Outflow.new(O2)
    Return:
       O2 (i.e. new Qout)
    Usage: StorageRoutingQout(Iold,Qin,Qout,base,Z,channeltype,Storage,length,slope,toSeconds(dt),n)
 */


   /* d1 will be calculated from S1, length and geometry */
   $tol = 0.001; /* set tolerance for equation solution */
   /* if (channeltype == 2) { ... ; to come, for now assume trapezoidal */
   $O2 = 0.0;

   $A = $S1 / $slength; /* cross-sectional area of channel based on last storage value */
   /* solve quadratic formula */
   /* since eqn. is of the form A = bd + Zd^2 quadratic formula is then:
         Zd^2 + bd - A = 0,
      and quadratic soln. is:
         x = ( -b +/- (b^2 - 4ac)^0.5 ) / 2a, a = Z, b = b, c = -A
      thus, since b is always > 0, and ((b^2 - 4ac)^0.5) > b, only + soln. will work
   */
   #$debug = 1;

   $d1 = (-1.0*$b + sqrt( (pow($b,2.0) + 4.0*$A*$Z) ) ) / (2.0 * $Z);
   $d2 = $d1 + 0.1;
   $eq2 = $S1 - ($O1/2.0)*$dt + (($I1 + $I2)*$dt)/2.0;
   $eq1 = $eq2 + 1.0;
   if ($eq2 <= 0) {
      $O2 = $S1/$dt;
      $S2 = 0.0;
      $eq1 = 0.0;
      $eq2 = 0.0;
      $d2 = 0.0;
   }
   for ($i = 0; (abs($eq1 - $eq2) > $tol); $i++) {
      $A2 = $b*$d2 + $Z*pow($d2,2.0);
      $S2 = $slength * $A2;
      $p = $b + 2.0*$d2*pow((pow($Z,2.0) + 1.0),0.5);
      $R = $A2 / $p;
      $O2 = $A2 * (1.0/$n) * pow($R,2.0/3.0) * pow($slope,0.5);
      $eq1 = $S2 + $O2*$dt/2.0;

      # old method
      /*
      if ($eq2 > $eq1) {
         $d2 = $d2*1.01;
      } else {
         $d2 = $d2*0.99;
      }
      */

      if ($i > $maxits) {
         $O2 = $I2; # assume that inflow equals outflow if we can not solve in reasonable time frame
         break;
      }

      # test new method for quicker solution
      $diff = $eq2 - $eq1;

      if ($debug) { print(" <b>$i:</b> D= $d1, $eq1, $eq2, dguess = $d2, ldguess = $ld2, diff = $diff, lastdiff = $lastdiff <br>\n"); }
      if ($debug) { print(" eq1 = $S2 + $O2*$dt/2.0 <br>\n"); }
      if ($debug) { print(" eq2 = $S1 - ($O1/2.0)*$dt + (($I1 + $I2)*$dt)/2.0; <br>\n"); }

      # get sign of $diff and $lastdiff
      # no concern about division by zero because we would have exited the loop if
      # $lastdiff were less than tolerance which is non-zero
      if ($diff <> 0) {
         if ($i == 0) {
            # first time through, make a standard adjustment to the guess
            $lastdiff = $diff;
            $ld2 = $d2;
            if ($debug) { print("Making our first guess. <br>\n"); }
            if ($diff < 0) {
               if ($debug) { print("Decreasing our guess. <br>\n"); }
               $d2 = $d2 * 0.9;
            } else {
               if ($debug) { print("Increasing our guess. <br>\n"); }
               $d2 = $d2 * 1.1;
            }
         } else {
            # stash the current guess
            $thisguess = $d2;
            # make an educated new guess based on last guess results
            $lsign = $lastdiff/abs($lastdiff);
            $dsign = $diff/abs($diff);
            if ($debug) { print("dsign = $dsign, lsign = $lsign <br>\n"); }

            if ($dsign <> $lsign) {
               # choose a new guess between the last two guesses
               if ($debug) { print("Last 2 guesses have different signs. <br>\n"); }
               $neghalf = min(array($lastdiff, $diff));
               $minguess = min(array($ld2, $d2));
               $interval = abs($lastdiff - $diff);
               $frac = 1.0 - (abs($neghalf) / $interval);
               if ($debug) {  print(" frac = 1.0 - (abs($neghalf) / $interval) = $frac; <br>\n"); }
               $d2 = $minguess + abs($d2 - $ld2) * $frac;
               if ($debug) { print(" d2 = $minguess + abs($d2 - $ld2) * $frac = $d2; <br>\n"); }
               # set last guess to whichever was closer
               if (abs($lastdiff) < abs($diff)) {
                  $ld2 = $thisguess;
                  $lastdiff = $diff;
               }
            } else {
               if ($debug) {  print("Last 2 guesses have the same signs. <br>\n"); }
               # the last two guesses have the same sign, so the same type
               # of change should occur, but with a larger magnitude
               if ($dsign < 0) {
                  # the new guess should decrease by a greater magnitude
                  # than tried previously
                  $num = min(array($ld2, $d2));
                  $denom = max(array($ld2, $d2));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Decreasing our guess by factor $gfact. <br>\n"); }

               } else {
                  $num = max(array($ld2, $d2));
                  $denom = min(array($ld2, $d2));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Increasing our guess by factor $gfact. <br>\n"); }
                  # the new guess should increase by a greater magnitude
                  # than tried previously
               }
               if ($debug) {  print("Changing our guess by factor $gfact. <br>\n"); }
               $d2 = $gfact * $ld2;
            }
         }
      }
   }

   return $O2;
}


function storageroutings($I1, $I2, $O1, $b, $Z, $channeltype, $S1, $slength, $slope, $dt, $n, $debug)
{

   /* $channeltype = 1 - triangle, 2 - trapezoid, 3 - rectangle */

 /* StorageRoutingS, part of a suite of storage routing routines including:
       StorageRoutingD, StorageRoutingQout
    Inputs:
       stream geometry:
          base(b),Z (trap, rect or triangle for now, later maybe pass shape in)
          length
       Inflow.old v(I1), Outflow.old(O1), Inflow.new(I2), depth.old(d1)
    calculate:
       Storage.old(S1) from depth, and geometry inputs (length term equates volume)
       Outflow.new(O2)
    Return:
       O2 (i.e. new Qout)
    Usage: StorageRoutingS(Iold,Qin,Qout,base,Z,channeltype,Storage,length,slope,toSeconds(dt),n)
 */

   /* d1 will be calculated from S1, length and geometry */

   $tol = 0.001; /* set tolerance for equation solution */

   /* if (channeltype == 2) { ... ; to come, for now assume trapezoidal */

   $A = $S1 / $slength; /* cross-sectional area of channel based on last storage value */
   $S2 = 0.0;
   /* solve quadratic formula */
   /* since eqn. is of the form A = bd + Zd^2 quadratic formula is then:
         Zd^2 + bd - A = 0,
      and quadratic soln. is:
         x = ( -b +/- (b^2 - 4ac)^0.5 ) / 2a, a = Z, b = b, c = -A
      thus, since b is always > 0, and ((b^2 - 4ac)^0.5) > b, only + soln. will work
   */
   #$debug = 1;

   $d1 = (-1*$b + pow( (pow($b,2.0) + 4*$A*$Z), 0.5) ) / (2 * $Z);
   $d2 = $d1 + 0.1;
   $eq2 = $S1 - ($O1/2.0)*$dt + (($I1 + $I2)*$dt)/2.0;
   $eq1 = $eq2 + 1.0;

   if ($eq2 <= 0) {
      $O2 = $S1/$dt;
      $S2 = 0.0;
      $eq1 = 0.0;
      $eq2 = 0.0;
      $d2 = 0.0;
   }

   for ($i = 0; (abs($eq1 - $eq2) > $tol); $i++) {
      $A2 = $b*$d2 + $Z*pow($d2,2.0);
      $S2 = $slength * $A2;
      $p = $b + 2*$d2*pow((pow($Z,2.0) + 1),0.5);
      $R = $A2 / $p;
      $O2 = $A2 * (1.0/$n) * pow($R,2.0/3.0) * pow($slope,0.5);
      $eq1 = $S2 + $O2*$dt/2.0;
      # old method
      /*
      if ($eq2 > $eq1) {
         $d2 = $d2*1.01;
      } else {
         $d2 = $d2*0.99;
      }
      */

      # test new method for quicker solution
      $diff = $eq2 - $eq1;

      if ($debug) { print(" <b>$i:</b> D= $d1, $eq1, $eq2, dguess = $d2, ldguess = $ld2, diff = $diff, lastdiff = $lastdiff <br>\n"); }
      if ($debug) { print(" eq1 = $S2 + $O2*$dt/2.0 <br>\n"); }
      if ($debug) { print(" eq2 = $S1 - ($O1/2.0)*$dt + (($I1 + $I2)*$dt)/2.0; <br>\n"); }

      # get sign of $diff and $lastdiff
      # no concern about division by zero because we would have exited the loop if
      # $lastdiff were less than tolerance which is non-zero
      if ($diff <> 0) {
         if ($i == 0) {
            # first time through, make a standard adjustment to the guess
            $lastdiff = $diff;
            $ld2 = $d2;
            if ($debug) { print("Making our first guess. <br>\n"); }
            if ($diff < 0) {
               if ($debug) { print("Decreasing our guess. <br>\n"); }
               $d2 = $d2 * 0.9;
            } else {
               if ($debug) { print("Increasing our guess. <br>\n"); }
               $d2 = $d2 * 1.1;
            }
         } else {
            # stash the current guess
            $thisguess = $d2;
            # make an educated new guess based on last guess results
            $lsign = $lastdiff/abs($lastdiff);
            $dsign = $diff/abs($diff);
            if ($debug) { print("dsign = $dsign, lsign = $lsign <br>\n"); }

            if ($dsign <> $lsign) {
               # choose a new guess between the last two guesses
               if ($debug) { print("Last 2 guesses have different signs. <br>\n"); }
               $neghalf = min(array($lastdiff, $diff));
               $minguess = min(array($ld2, $d2));
               $interval = abs($lastdiff - $diff);
               $frac = 1.0 - (abs($neghalf) / $interval);
               if ($debug) {  print(" frac = 1.0 - (abs($neghalf) / $interval) = $frac; <br>\n"); }
               $d2 = $minguess + abs($d2 - $ld2) * $frac;
               if ($debug) { print(" d2 = $minguess + abs($d2 - $ld2) * $frac = $d2; <br>\n"); }
               # set last guess to whichever was closer
               if (abs($lastdiff) < abs($diff)) {
                  $ld2 = $thisguess;
                  $lastdiff = $diff;
               }
            } else {
               if ($debug) {  print("Last 2 guesses have the same signs. <br>\n"); }
               # the last two guesses have the same sign, so the same type
               # of change should occur, but with a larger magnitude
               if ($dsign < 0) {
                  # the new guess should decrease by a greater magnitude
                  # than tried previously
                  $num = min(array($ld2, $d2));
                  $denom = max(array($ld2, $d2));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Decreasing our guess by factor $gfact. <br>\n"); }

               } else {
                  $num = max(array($ld2, $d2));
                  $denom = min(array($ld2, $d2));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Increasing our guess by factor $gfact. <br>\n"); }
                  # the new guess should increase by a greater magnitude
                  # than tried previously
               }
               if ($debug) {  print("Changing our guess by factor $gfact. <br>\n"); }
               $d2 = $gfact * $ld2;
            }
         }
      }
   }

   return $S2;
}

function storageroutingd( $I1, $I2, $O1, $b, $Z, $channeltype, $S1, $slength, $slope, $dt, $n, $debug)
{

 /* StorageRoutingD, part of a suite of storage routing routines including:
       StorageRoutingS, StorageRoutingQout
    Inputs:
       stream geometry:
          base(b),Z (trap, rect or triangle for now, later maybe pass shape in)
          length
       Inflow.old v(I1), Outflow.old(O1), Inflow.new(I2), depth.old(d1)
    calculate:
       Storage.old(S1) from depth, and geometry inputs (length term equates volume)
       Outflow.new(O2)
    Return:
       d2 (i.e. new depth)
    Usage: StorageRoutingS(Iold,Qin,Qout,base,Z,channeltype,Storage,length,slope,toSeconds(dt),n)
 */

   /* $channeltype = 1 - triangle, 2 - trapezoid, 3 - rectangle */

   /* d1 will be calculated from S1, length and geometry */
   $tol = 0.001; /* set tolerance for equation solution */

   /* if (channeltype == 2) { ... ; to come, for now assume trapezoidal */

   $A = $S1 / $slength; /* cross-sectional area of channel based on last storage value */
   /* solve quadratic formula */
   /* since eqn. is of the form A = bd + Zd^2 quadratic formula is then:
         Zd^2 + bd - A = 0,
      and quadratic soln. is:
         x = ( -b +/- (b^2 - 4ac)^0.5 ) / 2a, a = Z, b = b, c = -A
      thus, since b is always > 0, and ((b^2 - 4ac)^0.5) > b, only + soln. will work
   */
   $d1 = (-1*$b + pow( (pow($b,2.0) + 4*$A*$Z), 0.5) ) / (2 * $Z);
   $d2 = $d1 + 0.1;
   $eq2 = $S1 - ($O1/2.0)*$dt + (($I1 + $I2)*$dt)/2.0;
   $eq1 = $eq2 + 1.0;

   if ($eq2 <= 0) {
      $O2 = $S1/$dt;
      $S2 = 0.0;
      $eq1 = 0.0;
      $eq2 = 0.0;
      $d2 = 0.0;
   }

   for ($i = 0; (abs($eq1 - $eq2) > $tol); $i++) {
      $A2 = $b*$d2 + $Z*pow($d2,2.0);
      $S2 = $slength * $A2;
      $p = $b + 2*$d2*pow((pow($Z,2.0) + 1),0.5);
      $R = $A2 / $p;
      $O2 = $A2 * (1.0/$n) * pow($R,2.0/3.0) * pow($slope,0.5);
      $eq1 = $S2 + $O2*$dt/2.0;
      # old method
      /*
      if ($eq2 > $eq1) {
         $d2 = $d2*1.01;
      } else {
         $d2 = $d2*0.99;
      }
      */

      # test new method for quicker solution
      $diff = $eq2 - $eq1;

      if ($debug) { print(" <b>$i:</b> D= $d1, $eq1, $eq2, dguess = $d2, ldguess = $ld2, diff = $diff, lastdiff = $lastdiff <br>\n"); }
      if ($debug) { print(" eq1 = $S2 + $O2*$dt/2.0 <br>\n"); }
      if ($debug) { print(" eq2 = $S1 - ($O1/2.0)*$dt + (($I1 + $I2)*$dt)/2.0; <br>\n"); }

      # get sign of $diff and $lastdiff
      # no concern about division by zero because we would have exited the loop if
      # $lastdiff were less than tolerance which is non-zero
      if ($diff <> 0) {
         if ($i == 0) {
            # first time through, make a standard adjustment to the guess
            $lastdiff = $diff;
            $ld2 = $d2;
            if ($debug) { print("Making our first guess. <br>\n"); }
            if ($diff < 0) {
               if ($debug) { print("Decreasing our guess. <br>\n"); }
               $d2 = $d2 * 0.9;
            } else {
               if ($debug) { print("Increasing our guess. <br>\n"); }
               $d2 = $d2 * 1.1;
            }
         } else {
            # stash the current guess
            $thisguess = $d2;
            # make an educated new guess based on last guess results
            $lsign = $lastdiff/abs($lastdiff);
            $dsign = $diff/abs($diff);
            if ($debug) { print("dsign = $dsign, lsign = $lsign <br>\n"); }

            if ($dsign <> $lsign) {
               # choose a new guess between the last two guesses
               if ($debug) { print("Last 2 guesses have different signs. <br>\n"); }
               $neghalf = min(array($lastdiff, $diff));
               $minguess = min(array($ld2, $d2));
               $interval = abs($lastdiff - $diff);
               $frac = 1.0 - (abs($neghalf) / $interval);
               if ($debug) {  print(" frac = 1.0 - (abs($neghalf) / $interval) = $frac; <br>\n"); }
               $d2 = $minguess + abs($d2 - $ld2) * $frac;
               if ($debug) { print(" d2 = $minguess + abs($d2 - $ld2) * $frac = $d2; <br>\n"); }
               # set last guess to whichever was closer
               if (abs($lastdiff) < abs($diff)) {
                  $ld2 = $thisguess;
                  $lastdiff = $diff;
               }
            } else {
               if ($debug) {  print("Last 2 guesses have the same signs. <br>\n"); }
               # the last two guesses have the same sign, so the same type
               # of change should occur, but with a larger magnitude
               if ($dsign < 0) {
                  # the new guess should decrease by a greater magnitude
                  # than tried previously
                  $num = min(array($ld2, $d2));
                  $denom = max(array($ld2, $d2));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Decreasing our guess by factor $gfact. <br>\n"); }

               } else {
                  $num = max(array($ld2, $d2));
                  $denom = min(array($ld2, $d2));
                  $gfact = pow($num/$denom, 2.0);
                  if ($debug) { print("Increasing our guess by factor $gfact. <br>\n"); }
                  # the new guess should increase by a greater magnitude
                  # than tried previously
               }
               if ($debug) {  print("Changing our guess by factor $gfact. <br>\n"); }
               $d2 = $gfact * $ld2;
            }
         }
      }
   }

   return $d2;
}


function soilhydroksat($percent_sand, $percent_clay)
{
/* Soil texture triangle
hydraulic properties calculator
Taken from: http://www.bsyse.wsu.edu/saxton/soilwater/

Original Code written in javascript

*/


   $PWP = 0.0;  // Perm. Wilt. Point (cm3/cm3) % expressed as a decimal
   $FC = 0.0;  // Field Capacity    (cm3/cm3) % expressed as a decimal
   $SAT = 0.0;  // Saturation  (cm3/cm3) % expressed as a decimal
   $KSAT = 0.0;  // Saturated hydraulic conductivity  cm/hr

   $acoef =0.0;
   $bcoef =0.0;
   $sand_2=0.0;  // sand squared
   $clay_2=0.0;  // clay squared

   $percent_total = ($percent_sand + $percent_clay);

   if ($percent_total > 100.0)
   {  $percent_sand = 0.0;
      $percent_clay = 0.0;
   }

   if (($percent_sand > 0) && ($percent_clay > 0))
   {
      $sand_2 = $percent_sand * $percent_sand;
      $clay_2 = $percent_clay * $percent_clay;

      $acoef = exp(-4.396 - 0.0715 * $percent_clay -
                4.88e-4 * $sand_2 - 4.285e-5 * $sand_2 * $percent_clay);
      $bcoef = - 3.140 - 0.00222 * $clay_2 - 3.484e-5 * $sand_2 * $percent_clay;
      $SAT = 0.332 - 7.251e-4 * $percent_sand + 0.1276 * log10($percent_clay);

   if (($acoef != 0.0) && ($bcoef != 0.0))
   {
      $FC   = pow((0.3333/ $acoef),(1.0 / $bcoef));
      $PWP  = pow((15.0  / $acoef),(1.0 / $bcoef));
   }


      if ($SAT != 0.0)
         $KSAT =
            exp((12.012 - 0.0755 * $percent_sand)  +
            (- 3.895 + 0.03671 * $percent_sand -
                       0.1103  * $percent_clay +
                       8.7546e-4 * $clay_2) / $SAT);
    }

   return $KSAT;

 } /* end soil hydraulic characteristics, Saturated Hydraulic Conductivity (Ksat) */



function soilhydrothetasat($percent_sand, $percent_clay)
 {
 /* Soil texture triangle
 hydraulic properties calculator
 Taken from: http://www.bsyse.wsu.edu/saxton/soilwater/

 Original Code written in javascript

 */


    $PWP =0.0;  // Perm. Wilt. Point (cm3/cm3) % expressed as a decimal
    $FC =0.0;  // Field Capacity    (cm3/cm3) % expressed as a decimal
    $SAT =0.0;  // Saturation  (cm3/cm3) % expressed as a decimal
    $KSAT =0.0;  // Saturated hydraulic conductivity  cm/hr

    $acoef =0.0;
    $bcoef =0.0;
    $sand_2=0.0;  // sand squared
    $clay_2=0.0;  // clay squared

    $percent_total = ($percent_sand + $percent_clay);

    if ($percent_total > 100.0)
    {  $percent_sand = 0.0;
       $percent_clay = 0.0;
    };

    if (($percent_sand > 0) && ($percent_clay > 0))
    {
       $sand_2 = $percent_sand * $percent_sand;
       $clay_2 = $percent_clay * $percent_clay;

       $acoef = exp(-4.396 - 0.0715 * $percent_clay -
                 4.88e-4 * $sand_2 - 4.285e-5 * $sand_2 * $percent_clay);
       $bcoef = - 3.140 - 0.00222 * $clay_2 - 3.484e-5 * $sand_2 * $percent_clay;
       $SAT = 0.332 - 7.251e-4 * $percent_sand + 0.1276 * log10($percent_clay);

   if (($acoef != 0.0) && ($bcoef != 0.0))
   {
      $FC   = pow((0.3333/ $acoef),(1.0 / $bcoef));
      $PWP  = pow((15.0  / $acoef),(1.0 / $bcoef));
   }
       if ($SAT != 0.0)
          $KSAT =
             exp((12.012 - 0.0755 * $percent_sand)  +
             (- 3.895 + 0.03671 * $percent_sand -
                        0.1103  * $percent_clay +
                        8.7546e-4 * $clay_2) / $SAT);
     }

    return $SAT;

 } /* end soil hydraulic characteristics, Sat. Moisture Content (thetaSat) */


 function soilhydrothetafc($percent_sand, $percent_clay)
 {
 /*
 returns field capacity moisture content (thetafc)
 Derived from:
 Soil texture triangle
 hydraulic properties calculator
 Taken from: http://www.bsyse.wsu.edu/saxton/soilwater/

 Original Code written in javascript



 */

    $PWP   =0.0;  // Perm. Wilt. Point (cm3/cm3) % expressed as a decimal
    $FC    =0.0;  // Field Capacity    (cm3/cm3) % expressed as a decimal
    $SAT   =0.0;  // Saturation  (cm3/cm3) % expressed as a decimal
    $KSAT  =0.0;  // Saturated hydraulic conductivity  cm/hr

    $acoef =0.0;
    $bcoef =0.0;
    $sand_2=0.0;  // sand squared
    $clay_2=0.0;  // clay squared

    $percent_total = ($percent_sand + $percent_clay);

    if ($percent_total > 100.0)
    {  $percent_sand = 0.0;
       $percent_clay = 0.0;
    };

    if (($percent_sand > 0) && ($percent_clay > 0))
    {
       $sand_2 = $percent_sand * $percent_sand;
       $clay_2 = $percent_clay * $percent_clay;

       $acoef = exp(-4.396 - 0.0715 * $percent_clay -
                 4.88e-4 * $sand_2 - 4.285e-5 * $sand_2 * $percent_clay);
       $bcoef = - 3.140 - 0.00222 * $clay_2 - 3.484e-5 * $sand_2 * $percent_clay;
       $SAT = 0.332 - 7.251e-4 * $percent_sand + 0.1276 * log10($percent_clay);

   if (($acoef != 0.0) && ($bcoef != 0.0))
   {
      $FC   = pow((0.3333/ $acoef),(1.0 / $bcoef));
      $PWP  = pow((15.0  / $acoef),(1.0 / $bcoef));
   }
       if ($SAT != 0.0)
          $KSAT =
             exp((12.012 - 0.0755 * $percent_sand)  +
             (- 3.895 + 0.03671 * $percent_sand -
                        0.1103  * $percent_clay +
                        8.7546e-4 * $clay_2) / $SAT);
     }

    return $FC;

 } /* end soil hydraulic characteristics, Sat. Moisture Content (thetaSat) */

 function soilhydrowiltp($percent_sand, $percent_clay) {
 /*
 returns wilting point moisture content (wiltp)
 Derived from:
 Soil texture triangle
 hydraulic properties calculator
 Taken from: http://www.bsyse.wsu.edu/saxton/soilwater/

 Original Code written in javascript



 */

    $PWP   =0.0;  // Perm. Wilt. Point (cm3/cm3) % expressed as a decimal
    $FC    =0.0;  // Field Capacity    (cm3/cm3) % expressed as a decimal
    $SAT   =0.0;  // Saturation  (cm3/cm3) % expressed as a decimal
    $KSAT  =0.0;  // Saturated hydraulic conductivity  cm/hr

    $acoef =0.0;
    $bcoef =0.0;
    $sand_2=0.0;  // sand squared
    $clay_2=0.0;  // clay squared

    $percent_total = ($percent_sand + $percent_clay);

    if ($percent_total > 100.0)
    {  $percent_sand = 0.0;
       $percent_clay = 0.0;
    };

    if (($percent_sand > 0) && ($percent_clay > 0))
    {
       $sand_2 = $percent_sand * $percent_sand;
       $clay_2 = $percent_clay * $percent_clay;

       $acoef = exp(-4.396 - 0.0715 * $percent_clay -
                 4.88e-4 * $sand_2 - 4.285e-5 * $sand_2 * $percent_clay);
       $bcoef = - 3.140 - 0.00222 * $clay_2 - 3.484e-5 * $sand_2 * $percent_clay;
       $SAT = 0.332 - 7.251e-4 * $percent_sand + 0.1276 * log10($percent_clay);

      if (($acoef != 0.0) && ($bcoef != 0.0))
      {
         $PWP  = pow((15.0  / $acoef),(1.0 / $bcoef));
      }
   }

    return $PWP;

 } /* end soil hydraulic characteristics, Sat. Moisture Content (thetaSat) */
 
 ?>