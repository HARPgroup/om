<?php
 

  /* functions for accessing postgres database widgets */


function lookupDetail($dbconn,$listtable,$listpkcol,$listcolumns,$selectedcol,$sortcol) {


   $getlistsql = "select $listcolumns from $listtable where $listpkcol = '$selectedcol'";
   if ($sortcol <> "") { $getlistsql .= " order by ".$sortcol; }
   #debug
   #print("$getlistsql<br>");
   $returnvals = array();
   $getlistquery = pg_exec($dbconn,$getlistsql);
   for ($i = 0;$i < pg_numrows($getlistquery);$i++) {
      $getlistrow = pg_fetch_array($getlistquery,$i,PGSQL_ASSOC);
      array_push($returnvals,$getlistrow);
   }
   return $returnvals;
}




####################################################################################
##                                pgsql_QueryObject                               ##
####################################################################################


class pgsql_QueryObject {
   var $queryrecords; /* array containing records returned from query */
   var $tablename;
   var $querystring = "";
   var $dbsystem = 'postgresql';
   var $ogc_compliant = 0;
   var $whereclause;
   var $connstring;
   var $dbconn;
   var $maxrecords = -1;
   var $result;
   var $error = FALSE;
   var $log_errors = 0;// if set, will shoot SQL into error_log for array2table
   var $error_mode = 0; // 0 - error_log, 1 - print, 2 - $error_string
   var $error_string = '';
   var $adminsetup;
   var $adminsetuparray;
   var $showlabels = 1;
   var $viewcolumns = '';
   var $pk;
   var $debug = 0;
   var $show = 1;
   var $numrows = 0;
   var $endline = "\n";
   var $rowclass0 = 'd0';
   var $rowclass1 = 'd1';
   var $lastserial = -1;
   var $outstring = '';
   var $alias_string = 'AS'; 
   var $temptables = array();
   # system specific key word for column and table aliases SQL SERVER/Oracle this is ''
   var $buffersql = '';
   var $max_buffer = 8096; # max number of bytes to store in a buffered query - buffer will automatically flush before this is exceeded, or when the flushQueryBuffer() method is called
   
  function __construct($dbconn = FALSE, $connstring = '', $dbip = '', $dbname = '', $user = '', $password = '', $port = '') {
    if ($dbconn === FALSE) {
      if (!empty($connstring)) {
        // use a conn string if given 
      } else {
        if (!empty($dbip)) {
          // assume we want to create a connection here 
          $connstring = "host=$dbip dbname=$dbname user=$user password=$password port=$port";
        }
      }
      if (!empty($connstring)) {
        //error_log($connstring);
        $this->dbconn = pg_connect($connstring, PGSQL_CONNECT_FORCE_NEW);
        $stat = pg_connection_status($this->dbconn);
        if ($stat === PGSQL_CONNECTION_OK) {
          error_log( 'Connection status ok');
        } else {
          error_log( 'Connection status bad');
          error_log(' Error: ' . pg_last_error($this->dbconn));
        }
      }
    } else {
      $this->dbconn = $dbconn;
    }
  }
  
   function init() {
      $this->temptables = array();
   }

   function performQuery() {

      if ($this->debug > 1) {
         print("$this->querystring ; <br>connection: $this->dbconn ; <br>");
      }
      $this->numrows = 0;

      if ( ($this->querystring <> "") && (isset($this->dbconn)) ) {
         #$this->result = pg_exec($this->dbconn,$this->querystring);
         $this->error = FALSE;
         // asynchronous method
         //pg_send_query($this->dbconn,$this->querystring);
         //$this->result = pg_get_result($this->dbconn);
         //$this->error = pg_result_error($this->result);
         // synchronous method
         $this->result = pg_query($this->dbconn,$this->querystring);
         $this->error = pg_last_error($this->dbconn);
         $this->queryrecords = array();
         
         if (!$this->error) {
            # a false error result means that the query succeeded, so proceed with records, otherwise set error
            # only store a certain number of records, if not, simply store the result and exit
            if ( (pg_numrows($this->result) <= $this->maxrecords) or ($this->maxrecords == -1)) {
               for ($i = 0;$i < pg_numrows($this->result);$i++) {
                  array_push($this->queryrecords,pg_fetch_array($this->result,$i,PGSQL_ASSOC));
                  $this->numrows++;
               }
            } else {
               print("Max records exceeded in query, result stored.");
            }
            $this->lastserial = -1;
            # get the last serial value for the pk, can be used later if this is an insert
            if (isset($this->adminsetup['table info']['pk_seq'])) {
               # this only works if the sequence is identified in the table setup
               $seqname = $this->adminsetup['table info']['pk_seq'];
               $seqres = pg_exec($this->dbconn, "SELECT currval('$seqname')");
               if (pg_numrows($seqres) == 1) {
                  $resvals = pg_fetch_array($seqres,0,PGSQL_ASSOC);
                  $this->lastserial = $resvals['currval'];
               }
            }
         }
            
      } else {
         if (!isset($this->dbconn)) {
            $this->error .= " DBConn for this object hdoes not exist.";
         }
      }
   } /* end function performQuery() */
   
   function getAllRecords($start,$limit,$order) {
      if (strlen(ltrim(rtrim(str_replace('"','',$order)))) > 0) {
         $ocl = "ORDER BY $order";
      } else {
         $ocl = '';
      }
      $this->querystring = " select * from $this->tablename $ocl OFFSET $start LIMIT $limit";
      error_log($this->querystring);
      $this->performQuery();
   }
   
   function getNumRows() {
      
      $this->querystring = " select count(*) as numrows from $this->tablename ";
      $this->performQuery();
      $numrows = $this->getRecordValue(1,'numrows');
      return $numrows;
   }
   
   function bufferQuery() {
      # add the current querystring to the buffer if need be
      $this->stack_query($this->querystring);
      //error_log("Buffering: " . $this->querystring);
   }
   
   function flushQueryBuffer() {
      $result = 1;
      while ( !($result === FALSE )) {
         $result = pg_get_result($this->dbconn);
      }
   }

   function stack_query($sql = FALSE)
   {
     if ($sql !== FALSE)
       $this->buffersql[] = $sql;
     while (count($this->buffersql) && !pg_connection_busy($this->dbconn)  && (pg_transaction_status($this->dbconn) === PGSQL_TRANSACTION_IDLE ) )
       pg_send_query($this->dbconn, array_shift($this->buffersql));
     return count($this->buffersql) + (pg_connection_busy($this->dbconn) ? 1 : 0);
   }
   
   function escapeString($thistext) {
      $etext = pg_escape_string($thistext); 
      return $etext;
   }

   function logError($errstring) {
      switch ($this->error_mode) {
         case 0:
            error_log($errstring);
         break;
         case 1:
            print($errstring);
         break;
         case 2:
            $this->error_string .= $errstring;
         break;
      }
   }

  function tableExists($tablename) {
     $this->querystring = "select count(*) as numtables from pg_stat_user_tables where relname = '$tablename' ";
     //error_log("BEGIN psql tableExists output");
     //error_log($this->querystring);
     $this->performQuery();
     //error_log(print_r($this->queryrecords,1));
     //error_log("END psql tableExists output");
     if ( $this->getRecordValue(1,'numtables') > 0 ) {
        $te = 1;
     } else {
        $te = 0;
     }
     #error_log($this->querystring);

     return $te;
   }

   function getColumns($tablename) {
      $this->querystring = "  set search_path to public,information_schema ";
      $this->performQuery();
      $this->querystring = " select column_name from columns where table_name = '$tablename' ";
      $this->performQuery();
      $retarr = array();
      foreach($this->queryrecords as $thisrec) {
         array_push($retarr, $thisrec['column_name']);
      }

      return $retarr;
   }

   function getColumnsSubquery($tablename) {
      $this->querystring = "  select * from ($tablename) as foo LIMIT 1 ";
      $this->performQuery();
      $retarr = array_keys($this->queryrecords);

      return $retarr;
   }

   function getTables() {
      $this->querystring = "  set search_path to public,information_schema ";
      $this->performQuery();
      $this->querystring = "  select table_name from table_privileges where table_schema = 'public'";
      $this->performQuery();
      $retarr = array();
      foreach($this->queryrecords as $thisrec) {
         array_push($retarr, $thisrec['table_name']);
      }

      return $retarr;
   }

  function cancel() {
  }

  function getRecordValue($recnumber, $colname) {
     if ( count($this->queryrecords) > ($recnumber - 1)) {
        $thisrec = $this->queryrecords[($recnumber -1)];
        $thisval = $thisrec[$colname];
     } else {
        $thisval = "Error: Bad record ID";
     }
     return $thisval;
  }

  function array2tmpTable($values, $tablename, $colnames = array(), $valuetypes = array(), $forcelower = 1, $buffer = 0) {
     return $this->array2Table($values, $tablename, $colnames, $valuetypes, $forcelower, $buffer, 0);
  }

  function array2Table($values, $tablename, $colnames = array(), $valuetypes = array(), $forcelower = 1, $buffer = 0, $isPerm=0, $printonly = 0) {

     # expects an associative array columnname->value
     # check second line of array for column value types if they are not passed in
     if ($isPerm) {
        $tstring = '';
     } else {
        $tstring = 'TEMP';
     }
     if ($this->debug) {
        error_log("array2table called with table: $tablename with " . count($values) . " values.<br>\n");
        error_log("Data format types submitted" . print_r($valuetypes, 1) . " .<br>\n");
     }
     $numeric_types = array('numeric', 'float8', 'integer', 'bigint', 'int4', 'int8', 'int','timestamp');
     if ( (count($values) == 0) or (strlen(rtrim(ltrim($tablename))) == 0) ) {
        if ($this->debug) {
           error_log("Error: Input data has " . count($values) . " and tablename = $tablename ");
        }
        return;
     } else {
        if (count($colnames) == 0) {
           # use all columns if not instructed otherwise
           $rowkeys = array_keys($values);
           $colnames = array_keys($values[$rowkeys[0]]);
        }
        $cdel = '';
        $typedel = '';
        $charlens = array();
        $createsql = "create $tstring table \"$tablename\" (";
        $columnsql = " insert into \"$tablename\" (";
        $firstkey = current(array_keys($values));
        // set up types that have been sent, regardless of whether they are present in the data
        foreach (array_keys($valuetypes) as $thiscol) {
           $vtype = $valuetypes[$thiscol];
           $createsql .= $typedel . " \"$thiscol\" $vtype ";
           $typedel = ',';
        }
           
        foreach($colnames as $thisname) {
           if ($this->debug) {
              error_log("Formatting $thisname - current key" . $firstkey . "<br>\n");
           }
           if (in_array($thisname, array_keys($valuetypes))) {
              if ($this->debug) {
                 error_log("already Found type definition for $thisname " . $valuetypes[$thisname] . "<br>\n");
              }
           } else {
              # try to guess the type
              $thisval = $values[$firstkey][$thisname];

              if (is_numeric($thisval)) {
                 $vtype = 'float8';
              } elseif (strtotime($thisval)) {
                 $vtype = 'timestamp';
              } elseif (is_string($thisval)) {
                 $vtype = 'varchar(' . intval(3.0 * strlen($thisval) + 1) . ')';
                 $charlens[$thisname] = intval(3.0 * strlen($thisval) + 1);
              } else {
                 $vtype = 'varchar(' . intval(3.0 * strlen($thisval) + 1) . ')';
                 $charlens[$thisname] = intval(3.0 * strlen($thisval) + 1);
              }
              $valuetypes[$thisname] = $vtype;
              if ($this->debug) {
                 error_log("Could not find definition for $thisname, Guessing - $vtype<br>\n");
              }
              $createsql .= $typedel . " \"$thisname\" $vtype ";
              $typedel = ',';
           }

           $columnsql .= $cdel . " \"$thisname\" ";
           $cdel = ',';
        }
        $createsql .= ") ";
        $columnsql .= ") ";
        if ($printonly) {
           print("$createsql ;\n");
        }
        if ($this->debug) {
          error_log("Temp tables:" . print_r($this->temptables,1));
        }
        if (in_array($tablename, $this->temptables)) {
           // a quicker means of checking since we will not have to hit the db server each time if this is a successive call
           $te = 1;
           if ( $this->debug) {
              error_log("Table $tablename already exists<br>\n");
           }
        } else {
           $te = $this->tableExists($tablename);
           if ( $this->debug) {
              error_log("Checking for existence of table $tablename, result: $te <br>\n");
           }
        }
        if ( $te > 0 ) {
           # do nothing
        } else {
           $this->querystring = $createsql;
           if ( $this->debug) {
              error_log("Creating Table with SQL: " . $createsql . "<br>\n");
           }
           # we do not buffer table creation
           if (!$printonly) { 
              $this->performQuery();
              if ( ($this->tableExists($tablename) > 0) ) {
                 array_push($this->temptables, $tablename);
                 if ( $this->debug) {
                    error_log("$tablename created successfully <br>\n");
                 }
              } else {
                 //if ( $this->debug) {
                    error_log("Creation FAILED for $tablename with SQL: $createsql<br>\n");
                 //}
              }
           }
        }

        $icount = 0;
        if ($this->debug) {
           error_log("Sample Record: " . print_r($values[0], 1) . "<br>\n");
        }
        foreach ($values as $thisrow) {
           if ($this->debug > 1) {
              error_log("Record $icount: " . print_r($thisrow, 1) . "<br>\n");
           }
           $valsql = " values ( ";
           $vdel = '';
           foreach ($colnames as $thiscol) {
             if ($this->debug > 1) {
                error_log("Name: $thiscol, Value:" . $thisrow[$thiscol] ."<br>\n" );
                error_log("Type:" . $valuetypes[$thiscol] ."<br>\n");
                error_log("All Types:" . print_r($numeric_types,1) ."<br>\n");
             }

              // check for improperly formed numeric type value
              if (in_array($valuetypes[$thiscol], $numeric_types)) {
                 if ($this->debug) {
                    //error_log("$thiscol is Numeric column <br>\n");
                 }
                 if (trim($thisrow[$thiscol]) == '') {
                    $thisrow[$thiscol] = 'NULL';
                 }
                 if (trim($thisrow[$thiscol]) == '""') {
                    $thisrow[$thiscol] = 'NULL';
                 }
                 if (trim($thisrow[$thiscol]) == "''") {
                    $thisrow[$thiscol] = 'NULL';
                 }
                 if ( floatval($thisrow[$thiscol]) <> $thisrow[$thiscol] ) {
                    if ($this->debug > 1) {
                       error_log("Invalid Number found" . $thisrow[$thiscol] . "<br>\n");
                    }
                    $thisrow[$thiscol] = 'NULL';
                 } else {
                    if ($this->debug > 1) {
                       error_log("Valid Number: " . $thisrow[$thiscol] . "<br>\n");
                    }
                 }
             }
             if ($thisrow[$thiscol] == 'NULL') {
                $valsql .= $vdel . $thisrow[$thiscol];
             } else {
                $valsql .= $vdel . "'" . $thisrow[$thiscol] . "'";
             }
             $vdel = ',';
          }
          $valsql .= ")";

          $this->querystring = $columnsql . $valsql;
          if ($this->debug and ($icount == 0)) {
             # send the first line out to the error log
             error_log($this->querystring . "<br>\n");
          }
          $icount++;
          if ($printonly) {
              print("$this->querystring ; \n");
          } else {
             if ($buffer) {
                $this->bufferQuery();
             } else {
                $this->performQuery();
                if ($this->error and $this->log_errors) {
                   $this->logError("ERROR: psql error: $this->error");
                   $this->logError("ERROR: Data line " . print_r($thisrow,1));
                   $this->logError($this->querystring);
                }
             }
          }
       }
    }
     
    return $createsql;
 }
   
   function guessDataType($thisval) {
     // strip quotes from the beginning and end?
     if (is_numeric($thisval)) {
        $vtype = 'float8';
     } elseif (strtotime($thisval)) {
        $vtype = 'timestamp';
     } elseif (is_string($thisval)) {
        $vtype = 'varchar(' . intval(3.0 * strlen($thisval) + 1) . ')';
        $charlens[$thisname] = intval(3.0 * strlen($thisval) + 1);
     } else {
        $vtype = 'varchar(' . intval(3.0 * strlen($thisval) + 1) . ')';
        $charlens[$thisname] = intval(3.0 * strlen($thisval) + 1);
     }
     
     return array('vtype' => $vtype, 'length' => $charlens);
  }



   function showDetail() {
      if (count($this->queryrecords) > 0) {
         foreach ($this->queryrecords as $thisrec) {
            $this->detailView($thisrec);
         }
      }
   } /* end showDetail() */


   function detailView($thisrec) {
      if (!isset($this->adminview)) {
         $this->getAdminSetupInfo($this->tablename,$this->viewcolumns);
         if ($this->debug) {
            print("Table: $this->tablename, Cols: $this->viewcolumns <br>");
         }
      }
      $formatinfo = $this->adminsetup;


      if ($formatinfo == "raw") {
         #print("Unformatted output");
         $formatinfo = array();
         $colkeys = array_keys($thisrec);
         $formatinfo["table info"] = array("pk"=>'');
         foreach ($colkeys as $thiskey) {
            $thisformat = array("type"=>1,"label"=>$thiskey,"visible"=>1);
            $formatinfo["column info"][$thiskey] = $thisformat;
         }
      }


      # get the value for this records pk, note: pk records do NOT get displayed
      $tableinfo = $formatinfo["table info"];
      $pkcol = $tableinfo["pk"];


      $pkvalue = $thisrec[$pkcol];
      $rowdesc = $formatinfo["column info"];
      #debug
      if ($this->debug) {
         print("Table: $this->tablename, pk column: $pkcol = $pkvalue<br>");
      }


      #
      # created above in favour of:


      if (strlen($this->viewcolumns) > 0) {
         $colkeys = array_keys($rowdesc);
      } else {
         $colkeys = array_keys($thisrec);
      }


      foreach ($colkeys as $colname) {
         # do the admin setup stuff
         $thisdesc = $rowdesc[$colname];
         $type = $thisdesc["type"];
         $params = $thisdesc["params"];
         $label = $thisdesc["label"];


         $value = $thisrec[$colname];


         if ($this->debug) {
            print("Handling column $colname, type: $type<br>");
         }


         $visible = $thisdesc["visible"];
         if ( ($this->showlabels) && ($visible) ) { print("<b>$label:</b>"); }
         if ( ($this->showlabels) && ($visible) ) {
            $this->printColumnValue($type,$params,$colname,$thisrec);
         }
         if ($visible) { print("<br>\n"); }


      } # end foreach (column)
   } /* end detailView */


   function showList() {


      # clear the output string
      $this->outstring = '';
      $numrecs = count($this->queryrecords);
      if ($this->debug) { print("number of records returned for $this->tablename: $numrecs<br>"); }

      if ( (!isset($this->adminview)) or ($this->adminview == 0)) {
         $this->getAdminSetupInfo($this->tablename,$this->viewcolumns);
      }

      if (count($this->queryrecords) > 0) {
         //error_log("Records returned: " . count($this->queryrecords));
         $formatinfo = $this->adminsetup;
         //error_log("Format Info: " .print_r($formatinfo,1));
         if ($formatinfo == "raw") {
            $thisrec = $this->queryrecords[min(array_keys($this->queryrecords))];
            $formatinfo = array();
            $colkeys = array_keys($thisrec);
            $formatinfo["table info"] = array("pk"=>'');
            foreach ($colkeys as $thiskey) {
               $thisformat = array("type"=>1,"label"=>"$thiskey","visible"=>1);
               $formatinfo["column info"][$thiskey] = $thisformat;
               #error_log("$thiskey ");
            }
            //error_log("Keys: " . print_r($thisformat,1));
         }

         $rowdesc = $formatinfo["column info"];

         # commented out to force only showing columns that have been queried, in order of query
         #$rkeys = array_keys($rowdesc);
         # added next row
         $samplerow = $this->queryrecords[min(array_keys($this->queryrecords))];
         #modified next row
         $rkeys = array_keys($samplerow);
         $pk = $formatinfo["table info"]["pk"];

         if ($this->show) {
            print("\n<table>");
         }
         $this->outstring .= "$this->endline<table>";

         if ($this->showlabels) {
            if ($this->show) {
               print("\n<tr>\n");
            }
            $this->outstring .= "$this->endline<tr>$this->endline";
            foreach ($rkeys as $thiskey) {
               $label = $rowdesc[$thiskey]["label"];
               $visible = $rowdesc[$thiskey]["visible"];
               if ( ($visible) ) {
                  if ($this->show) {
                     print("<td valign=bottom><b>$label</b></td>");
                  }
                  $this->outstring .= "<td valign=bottom><b>$label</b></td>";
               }
            }
            if ($this->show) {
               print("\n</tr>\n");
            }
            $this->outstring .= "$this->endline</tr>$this->endline";
         }
         
         $n = 0; # row class number
         foreach ($this->queryrecords as $thisrec) {
            if ($this->show) {
               print("<tr>\n");
            }
            switch ($n) {
               case 0:
                  $cls = $this->rowclass0;
               break;
               
               case 1:
                  $cls = $this->rowclass1;
               break;
            }
            $this->outstring .= "<tr class=$cls>$this->endline";
            $this->listView($thisrec);
            if ($this->show) {
               print("\n</tr>");
            }
            $this->outstring .= "$this->endline</tr>";
            
            $n += -1;
            $n = abs($n);
         }
         if ($this->show) {
            print("\n</table>");
         }
         $this->outstring .= "$this->endline</table>";
      }
   } /* end function showList() */


   function listView($thisrec) {


      if (!isset($this->adminview)) {
         $this->getAdminSetupInfo($this->tablename,$this->viewcolumns);
      }
      $formatinfo = $this->adminsetup;

      if ($formatinfo == "raw") {
         $formatinfo = array();
         $colkeys = array_keys($thisrec);
         $formatinfo["table info"] = array("pk"=>'');
         foreach ($colkeys as $thiskey) {
            $thisformat = array("type"=>1,"params"=>'',"label"=>$thiskey,"visible"=>1);
            $formatinfo["column info"][$thiskey] = $thisformat;
         }
      }

      # get the value for this records pk, note: pk records do NOT get displayed
      $tableinfo = $formatinfo["table info"];
      $pkcol = $tableinfo["pk"];

      $pkvalue = @$thisrec[$pkcol];
      $rowdesc = $formatinfo["column info"];
      #debug
      if ($this->debug) {

         print("Formatted row: pk = $pkcol => $pkvalue, visible = $visible, rowdesc = $rowdesc<br>");
      }

      if (strlen($this->viewcolumns) > 0) {
         $colkeys = array_keys($rowdesc);
      } else {
         $colkeys = array_keys($thisrec);
      }

      $rowout = "";
      foreach ($colkeys as $colname) {
         # do the admin setup stuff
         $thisdesc = $rowdesc[$colname];
         $type = $thisdesc["type"];
         $params = $thisdesc["params"];
         $label = $thisdesc["label"];
         $visible = $thisdesc['visible'];
         if ($formatinfo == "raw") {
            $visible = 1;
         }

         $value = $thisrec[$colname];

         if ($this->debug) {
            print("Handling column $colname, type: $type<br>");
         }
         if ($visible) {
            if ($this->show) {
               print("<td valign=top>\n");
            }
            $this->outstring .= "<td valign=top>$this->endline";
            $this->printColumnValue($type,$params,$colname,$thisrec);
            if ($this->show) {
               print("\n</td>\n");
            }
            $this->outstring .= "$this->endline</td>$this->endline";
         }
      } # end foreach (column)
   } /* end listView */




   function printColumnValue($type,$params,$cname,$thisrow) {


      $value = $thisrow[$cname];
      $pkvalue = @$thisrow[$this->pk];


      # for columns which don;t appear in the adminsetup table
      if (!($type > 0)) { $type = 1;}


      #print("PK: $pkvalue<br>");


      switch ($type) {


         case 0:
         # pk column, do nothing
         break;


         case 3:
         # multiple value lookup (aka multi select list)
            list($foreigntable,$pkcol,$listcols,$sortcol,$showlabels) = explode(":",$params);
            $pkvalue = $thisrow[$pkcol];
            $getlistsql = "select $listcols from $foreigntable where $pkcol = '$pkvalue'";
            #print("$getlistsql<br>");
            if ($sortcol <> "") { $getlistsql .= " order by ".$sortcol; }
            $listobject = new pgsql_QueryObject();
            $listobject->show = $this->show;
            $listobject->querystring = $getlistsql;
            $listobject->tablename = $foreigntable;
            $listobject->dbconn = $this->dbconn;
            $listobject->showlabels = $showlabels;
            $listobject->performQuery();
            $listobject->showList();
            $fvalue = '';
            $this->outstring .= $listobject->outstring;
         break;

         case 7: # percentage
            list($decimalplaces) = explode(":",$params);
            if ($decimalplaces == '') { $decimalplaces = 2; }
            $fvalue = number_format($value * 100.0,$decimalplaces);
            $fvalue = "$fvalue %";

         break;


         case 8: # scientific notation
            list($decimalplaces) = explode(":",$params);
            if ($decimalplaces == '') { $decimalplaces = 0; }
            $fvalue = sciFormat($value,$decimalplaces);
            $fvalue = "$fvalue";

         break;

         case 9: # number format
            list($decimalplaces) = explode(":",$params);
            if (strlen($decimalplaces) == 0) {
               $decimalplaces = 2;
            }
            $fvalue = number_format($value, $decimalplaces);
            $fvalue = "$fvalue";
         break;


         case 10: # currency format

            list($decimalplaces) = explode(":",$params);
            if ($decimalplaces == '') { $decimalplaces = 2; }
            $fvalue = number_format($value, $decimalplaces);
            $fvalue = "\$$fvalue";
         break;


         case 11: # 2-column map
         # multiple value lookup (aka multi select list)
            list($local1,$local2,$ftable,$foreign1,$foreign2,$listcols,$sortcol,$showlabels) = explode(":",$params);
            $l1 = $thisrow[$local1];
            $l2 = $thisrow[$local2];
            $getlistsql = "select $listcols from $ftable where ";
            $getlistsql .= "$foreign1 = '$l1' and $foreign2 = '$l2'";
            if ($sortcol <> "") { $getlistsql .= " order by ".$sortcol; }
            #print("$local1,$local2: $getlistsql<br>");
            $listobject = new pgsql_QueryObject();
            $listobject->show = $this->show;
            $listobject->querystring = $getlistsql;
            $listobject->tablename = $foreigntable;
            $listobject->dbconn = $this->dbconn;
            $listobject->showlabels = $showlabels;
            $listobject->performQuery();
            $listobject->showList();
            $fvalue = '';
            $this->outstring .= $listobject->outstring;
         break;


         case 12: # highlight min or max column value
         # multiple value lookup (aka multi select list)
            list($comptype,$compcol) = explode(":",$params);
            $compcols = explode(",",$compcol);
            $flagval = "unset";
            $flagcol = "";
            foreach ($compcols as $ccol) {
               $cval = $thisrow[$ccol];
               switch ($comptype) {
               case "min":
                  if ( ($cval < $flagval) || ($flagval == "unset") ) {
                     #print("$cval < $flagval <br>");
                     $flagval = $cval;
                     $flagcol = $ccol;
                  }
               break;

               case "max":
                  if ( ($cval > $flagval) || ($flagval == "unset") ) {
                     $flagval = $cval;
                     $flagcol = $ccol;
                  }
               break;
               }
            }
            if ($cname == $flagcol) {
               $fvalue = "<b>$value</b>";
            } else {
               $fvalue = "$value";
            }
         break;


         case 13: # print out min or max column value from list of columns in this row
         # multiple value lookup (aka multi select list)
            list($comptype,$nullval,$compcol) = explode(":",$params);
            $compcols = explode(",",$compcol);
            $flagval = "unset";
            $flagcol = "";
            foreach ($compcols as $ccol) {
               $cval = $thisrow[$ccol];
               switch ($comptype) {
               case "min":
                  if ( (($cval < $flagval) || ($flagval == "unset")) && ($cval <> $nullval) ) {
                     #print("$cval < $flagval <br>");
                     $flagval = $cval;
                     $flagcol = $ccol;
                  }
               break;


               case "max":
                  if ( ($cval > $flagval) || ($flagval == "unset") ) {
                     $flagval = $cval;
                     $flagcol = $ccol;
                  }
               break;
               }
            }
            $fvalue = "$flagval";


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
            $listobject = new pgsql_QueryObject();
            $listobject->show = $this->show;
            $listobject->querystring = $getlistsql;
            $listobject->tablename = $foreigntable;
            $listobject->dbconn = $this->dbconn;
            $listobject->showlabels = $showlabels;
            $listobject->performQuery();
            $listobject->showList();
            $fvalue = '';
            $this->outstring .= $listobject->outstring;
         break;

         case 15: # edit link
            list($editpage,$extravars,$targetframe,$linktext,$urlextras) = explode(":",$params);

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

            $fvalue = "<a href='$editpage?$extraurl&$pkcol=$pkvalue' target='$targetframe'>$linktext </a>";
         break;

         default:
         # text plain, numeric and text types (1,2)
            $fvalue = "$value";
         break;
      }

      if ($this->show) {
         print("$fvalue");
      }
      $this->outstring .= $fvalue;
   } /* end getColumnValue */


   function getAdminSetupInfo($tablename,$columns) {
      # retrieves admin setup info from table, or from existing array structure
      #      returns an associative array with the following:
      #           "table info" => array("pkcol"=>columname, "sortcol"=>columnname )
      #           "column info" => array(columname1=>array("type"=>displaytype,
      #                                                    "params"=>"param1:param2:param3",
      #                                                    "label"=>string )
      #      returns 0 if table is undefined
      # $dbconn - psql database connection id
      # $tablename - the name of the table to retrieve info for
      # $columns - a comma seperated list of columns to retrieve info for, if this is

      #            blank ALL columns will be retrieved


      $dbconn = $this->dbconn;
      #$exists = include('adminsetup.php'); /* creates array $adminsetuparray */


      #debug
      #print("get adminsetup stuff: $tablename, $adminsetuparray<br>");


      if (is_array($this->adminsetuparray)) {
         $askeys = array_keys($this->adminsetuparray);
      } else {
         $askeys = array();
      }

      if ($this->debug) {
         print("All adminsetup entries: <br>");
         foreach(array_keys($this->adminsetuparray) as $thiskey) {
            print("&nbsp;&nbsp;&nbsp;$thiskey<br>");
         }
      }


      if (in_array($tablename,$askeys)) {
         //error_log("Found table: $tablename");
         $formatinfo = $this->adminsetuparray[$tablename];
         $tableinfo = $formatinfo["table info"];
         $columninfo = $formatinfo["column info"];
         if ($this->debug) {
            print("Columns: $columns <br>");
         }
         if (!($columns == '')) {
            foreach(explode(",",$columns) as $cname) {
               if ($this->debug) {
                  $allcols = implode(",",array_keys($columninfo));
                  print("Name: $cname in $allcols ??<br>");
               }
               if ( in_array($cname, array_keys($columninfo)) ) {
                  #print("$cname located.<br>");
                  $newcolumninfo[$cname] = $columninfo[$cname];
               }
            }
         } else {
            $newcolumninfo = $columninfo;
         }
         $formatinfo["table info"] = $tableinfo;
         $formatinfo["column info"] = $newcolumninfo;
         $this->pk = $tableinfo["pk"];
      } else {
         //error_log("Did not find table: $tablename");
         $formatinfo = "raw";
      }

      if ($this->debug) { print("$formatinfo<br>"); }
      $this->adminsetup = $formatinfo;
   }


    /* end function getAdminSetupInfo($dbconn,$tablename,$columns) */

} /* end pgsql_QueryObject */



?>
