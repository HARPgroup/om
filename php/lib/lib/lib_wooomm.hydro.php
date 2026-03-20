<?php

// lib_wooomm.hydro.php

function diff_array_map ($v, $sub) {
   $diff = $sub - $v;
   return $diff;
}

function floor_array_map ($v, $floor) {
   return max($floor, $v);
}

class hydroGrid extends dataMatrix {

   var $last_elev = NULL;
   var $last_storage = 0;
   var $last_surface_area = 0;
   var $x_size = 10;
   var $y_size = 10;
   var $last_increment = 1.0; // change in depth in feet/meters (unit of depth)
   var $tol = 5.0; // units of storage, tolerance of error
   var $dVdx = 0.1; // elevation change of smallest graduation
   var $maxits = 100; // maximum iterations to use when solving
   var $bottoms = NULL;
   
   function wake() {
      parent::wake();
      // calculate an initial guess for the surface elevation from the mean value in the matrix
      $this->last_elev = $this->getMatrixMean();
      $this->wvars = array('elevation', 'surface_area');
   }
   
   function sleep() {
      $this->bottoms = NULL;
      $this->wvars = null;
      parent::sleep();
   
   }
   
   function evaluate() {
      $this->formatMatrix();
      $storage = $this->arData[$this->keycol1]; // storage volume column
      $this->solveForElevation($storage);
   }
   
   function formatMatrix($force_refresh = 0) {
      parent::formatMatrix($force_refresh);
      //subtract elevation number from cell bottom matrix (array_sub)
      $this->bottoms = array();
      $vals = array_values_recursive($this->matrix_rowcol);
      $blanks = array_search('', $vals);
      if (!is_array($blanks)) {
         $blanks = array($blanks);
      }
      error_log("Blank cells are: " . print_r($blanks,1));
      foreach ($vals as $key => $val) {
         if (!in_array($key, $blanks)) {
            $this->bottoms[] = $val;
         }
      }
   }
   
   function solveForElevation($storage = 0) {
      if ($this->bottoms === NULL) {
         $this->formatMatrix();
      }
      $guess = $this->last_storage;
      if ($this->last_elev == NULL) {
         $elev = min($this->bottoms);
         error_log("Getting $elev from bottoms array");
      } else {
         $elev = $this->last_elev;
      }
      list($guess, $sa) = $this->getStorageFromElevation($elev);
      $i = 0;
      $last_dir = NULL;
      $diff = $storage - $guess;
      $last_elev = $elev;
      while (abs($diff) > $this->tol) {
         // use newtons method for elev guess
         $e1 = $elev - ($this->dVdx/10.0);
         $e2 = $elev + ($this->dVdx/10.0);
         list($v1, $sa) = $this->getStorageFromElevation($e1);
         $f = $guess - $storage;
         list($v2, $sa) = $this->getStorageFromElevation($e2);
         $dvdx = ($v1 - $v2) / ($e1 - $e2);
         error_log("dvdx = (v1 - v2) / (e1 - e2) = $dvdx = ($v1 - $v2) / ($e1 - $e2)");
         $elev = $last_elev - ($f / $dvdx);
         error_log("elev = elev - (f / dvdx) : $elev = $last_elev - ($f / $dvdx)");
         //error_log("Last: $last_elev, Diff: $diff, Dir: $last_dir");
         $last_elev = $elev;
         list($guess, $sa) = $this->getStorageFromElevation($elev);
         $diff = $storage - $guess;
         if ($diff < 0) {
            // if diff is negative, we decrease our guess
            $dir = -1.0;
         } else {
            $dir = +1.0;
         }
         error_log("Guess Elev: $elev, Guess: $guess, Dir: $dir, Inc: $increment \n");
         $i++;
         if ($i > $this->maxits) {
            break;
         }
      }
      $this->last_storage = $storage;
      $this->last_elev = $elev;
      $this->last_increment = $increment;
      $this->state['elevation'] = $elev;
      $this->state['surface_area'] = $sa;
      return $elev;
   }
   
   function getSmallestIncrement() {
      if ($this->bottoms === NULL) {
         $this->formatMatrix();
      }
      //subtract elevation number from cell bottom matrix (array_sub)
      $bottoms = $this->bottoms;
      sort($bottoms);
      error_log("Sorted: " . print_r($bottoms,1));
      $granule = NULL;
      for ($i=1; $i <= count($bottoms); $i++) {
         $prev = $bottoms[$i-1];
         $next = $bottoms[$i];
         $space = abs($prev - $next);
         if ($space > 0) {
            if ( ( $space < $granule) or ($granule === NULL) ) {
               $granule = $space;
            }
         }
      }
      if (!($granule === NULL)) {
         $this->dVdx = $granule;
      }
   }
   
   function getStorageFromElevation($elevation) {
      if ($this->bottoms === NULL) {
         $this->formatMatrix();
      }
      $bottoms = $this->bottoms;
      $num = count($bottoms);
      error_log("Filling $num slots with $elevation \n");
      $sub = array_fill(0, $num, $elevation);
      //error_log("Subtracting " . print_r($sub,1) . " from " . print_r($bottoms, 1) . "\n");
      //$inundated = array_map("diff_array", $bottoms, $sub);
      $difference = array_map("diff_array_map", $bottoms, $sub);
      //error_log("difference " . print_r($difference,1) . " \n");
      $sub = array_fill(0, $num, 0);
      // zero out negative cells:
      $inundated = array_map("floor_array_map", $difference, $sub);
      error_log("inundated " . print_r($inundated,1) . " \n");
      //Multiple by unit area to get total volume:
      //$volume = $unit_area * $cell_depth_sum;
      $storage = array_sum($inundated) * $this->x_size * $this->y_size;
      $surface_area = count($inundated) * $this->x_size * $this->y_size;
      error_log("Surface Area: $surface_area ");
      return array($storage, $surface_area);
   }
   
   function getMatrixMean() {
      if ($this->bottoms === NULL) {
         $this->formatMatrix();
      }
      $mean = array_sum($this->bottoms) / count($this->bottoms);
      error_log("Matrix mean = $mean");
      return $mean;
   }
}


?>
