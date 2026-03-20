<?php

# lib_noaa.php
function getNOAARasterPrecip ($scratchdir, $fileURL, $filename, $debug) {

   if (!copy($fileURL, $scratchdir . '/' . $filename)) {
       echo "failed to copy $file...\n";
   }
/*
   if ($debug) { print("Downloading $fileURL.<br>"); }
   $imgdata = imagecreatefromgif ( $fileURL );
   
   if ($debug) { print("Saving to $filename.<br>"); }
   imagegif ( $imgdata, $scratchdir . '/' . $filename );
*/
}


function getNOAAGriddedPrecipHTTP ($listobject, $scratchdir, $baseurl, $filename, $overwrite, $debug) {
   $shp2pg_cmd = 'shp2pgsql'; // windows, use shp2pgsql.exe
   $srid = 4326; // this is a psuedo value that oinly is needdeed to keep the import from breaking.
                 // we don't actually use the spatial component of this 
   if (strlen($baseurl) == 0) {
      $baseurl = "http://www.srh.noaa.gov/rfcshare/p_download_new";
   }

   $fileURL = $baseurl . '/' . $filename;
   
   $result = array();
   $results['fileURL'] = $fileURL;
   $results['numrecs'] = 0;
   
   $getfile = 0;
   if (fopen($scratchdir . '/' . $filename, "r")) {
      if ($overwrite) {
         $getfile = 1;
         print("File $filename exists locally, but refresh from network requested.<br>");
         print("Attempting retrieval from network.<br>");
      } else { 
         print("File $filename exists locally, no refresh requested.<br>");
      }
      fclose($scratchdir . '/' . $filename);
   } else {
      print("File $filename does not exist locally, attempting retrieval from network.<br>");
      $getfile = 1;
   }
   
   if ($getfile) {
      if ($debug) { print("Initializing server info.<br>"); }
      if ($debug) { print("Trying to retrieve $fileURL.<br>"); }
      if (!copy($fileURL, $scratchdir . '/' . $filename)) {
          $results['error'] .= "failed to retrieve $fileURL...\n";
          $ftpfile = 'ftp://63.77.98.88/pub/rfcshare/precip_new/' . $filename;
          $results['error'] .= "trying ftp file $fileURL...\n";
          if (!copy($ftpfile, $scratchdir . '/' . $filename)) {
             $results['error'] .= "failed to retrieve $ftpfile...\n";
          }
      }
   }

   # unzipping the archive
   if ($debug) { print("Unpacking file $filename.<br>"); }
   #gunzip($tarfile, $filename);
   $tar = new Archive_Tar($scratchdir . '/' . $filename);
   @$tar->extract($scratchdir);

   $files = $tar->listContent();       // array of file information
   if ( !count($files) ) {
      $results['error'] .= "Could not extract files!";
      return $results;
   }

   $shapename = '';
   #print_r($files);
   foreach ($files as $f) {
      #print_r($f);
       $fn = $f['filename'];
       if ($debug) {
          print("Examining archive member $fn <br>");
       }
       $ext = substr($fn,-4,4);
       print("Extension = $ext <br>");
       if (substr($fn,-4,4) == '.shp') {
          # we found the shape file base, extract the name
          $shapename = substr($fn,0,strlen($fn)-4);
       }
   }
   
   if ($shapename == '') {
      $results['error'] .= "Could not locate shapefile in archive!";
      return $results;
   }
   
   $results['shapename'] = $shapename;
   
   $shapefilename = "$scratchdir/" . $shapename;

   print("Creating PostGIS loadable data from file.<br>");
   print("Using command: $shp2pg_cmd -s $srid $shapefilename tmp_precipgrid > $shapefilename.sql <br>");
   exec("$shp2pg_cmd -s $srid $shapefilename tmp_precipgrid > $shapefilename.sql", $cout);


   # assumes 8k line lenght maximum. This should be OK for these data records
   # since they are only point data, but would be much larger if it were shape data

   if ($listobject->tableExists('tmp_precipgrid')) {
      $listobject->querystring = "drop table tmp_precipgrid ";
      if ($debug) { print("$listobject->querystring ; <br>"); }
      $listobject->performQuery();
   }
   print("Reading the contents of $shapefilename.sql into PG database.<br>");
   $shphandle = fopen("$shapefilename.sql","r");
   while ($thisline = fgets($shphandle) ) {
      $listobject->querystring = $thisline;
      while (substr(rtrim($thisline), -1, 1) <> ';') {
        # keep looking for more, this is a multi-line query
        $thisline = fgets($shphandle);
        $listobject->querystring .= $thisline;
      }
      # Can't uncomment this one, or will end up with a billion records printed out
      #if ($debug) { print("$listobject->querystring ; <br>"); }
      $listobject->performQuery();
      $i++;
      if (($i / 500.0) == intval($i/500.0)) {
        if ($debug) { print("$i records processed.<br>"); }
        #break;
      }
   }
   if ($debug) { print("<b>Total Records</b> = $i.<br>"); }
   $results['error'] .= "<b>Total Lines Parsed</b> = $i.<br>";
   $results['numrecs'] = $i;

   $listobject->querystring = "select count(*) as recs from tmp_precipgrid ";
   $listobject->performQuery();
   if ($debug) {
      print("$listobject->querystring ; <br>");
      $listobject->showList();
   }
   $imps = $listobject->getRecordValue(1,'recs');
   $results['error'] .= "<b>Total Records Imported</b> = $imps.<br>";

   return $results;
}

function getNOAAGriddedPrecipFTP ($listobject, $scratchdir, $year, $month, $day, $debug, $ftpuser='anonymous', $ftppass='foo@bar.net', $dataserver='63.77.98.88', $dataroot='/pub/rfcshare/precip_new/') {

   if ($debug) { print("Initializing server info.<br>"); }

   $shapename = "nws_precip_$year$month$day";
   $dataloc = $shapename . ".tar.gz";
   $filename = "$scratchdir/" . $shapename . ".tar.gz";
   $tarfile = "$scratchdir/" . $shapename . ".tar";
   $shapefilename = "$scratchdir/" . $shapename;
   print("Connecting to precip data server.<br>");
   $conn_id = ftp_connect($dataserver);
   $login_result = ftp_login($conn_id, $ftpuser, $ftppass);
   if ((!$conn_id) || (!$login_result)) {
       $error = "FTP connection has failed!<br>";
       $error .= "Attempted to connect to $dataserver for user $ftpuser ($conn_id - $login_result)<br>";
      if ($debug) {
         echo "$error";
      }
      return $error;
   } else {
      if ($debug) { echo "Connected to $dataserver, for anonymous user ($conn_id - $login_result)<br>"; }
   }

   // try to change the directory to somedir
   if (ftp_chdir($conn_id, $dataroot)) {
      if ($debug) { echo "Current directory is now: " . ftp_pwd($conn_id) . "<br>"; }
   } else {
      if ($debug) { echo "Couldn't change directory.<br>"; }
   }
   if ($debug) { print("Downloading file.<br>"); }
   ftp_get($conn_id, $filename, $dataloc, FTP_BINARY);


   # unzipping the archive
   if ($debug) { print("Unpacking file $filename.<br>"); }
   #gunzip($tarfile, $filename);
   $tar = new Archive_Tar($filename);
   $tar->extract('./data/') or die ("Could not extract files!");

   print("Creating PostGIS loadable data from file.<br>");
   exec("shp2pgsql.exe $shapefilename tmp_precipgrid > $shapefilename.sql", $cout);


   # assumes 8k line lenght maximum. This should be OK for these data records
   # since they are only point data, but would be much larger if it were shape data

   if ($listobject->tableExists('tmp_precipgrid')) {
      $listobject->querystring = "drop table tmp_precipgrid ";
      if ($debug) { print("$listobject->querystring ; <br>"); }
      $listobject->performQuery();
   }
   print("Reading the contents of $shapefilename.sql into PG database.<br>");
   $shphandle = fopen("$shapefilename.sql","r");
   while ($thisline = fgets($shphandle) ) {
      $listobject->querystring = $thisline;
      while (substr(rtrim($thisline), -1, 1) <> ';') {
        # keep looking for more, this is a multi-line query
        $thisline = fgets($shphandle);
        $listobject->querystring .= $thisline;
      }
      # Can't uncomment this one, or will end up with a billion records printed out
      #if ($debug) { print("$listobject->querystring ; <br>"); }
      $listobject->performQuery();
      $i++;
      if (($i / 500.0) == intval($i/500.0)) {
        if ($debug) { print("$i records processed.<br>"); }
        #break;
      }
   }
   if ($debug) { print("<b>Total Records</b> = $i.<br>"); }
   $error .= "<b>Total Lines Parsed</b> = $i.<br>";

   $listobject->querystring = "select count(*) as recs from tmp_precipgrid ";
   $listobject->performQuery();
   if ($debug) {
      print("$listobject->querystring ; <br>");
      $listobject->showList();
   }
   $imps = $listobject->getRecordValue(1,'recs');
   $error .= "<b>Total Records Imported</b> = $imps.<br>";

   return $error;
}


function retrievePrecipDeparture($dbobj, $uri, $type, $period, $debug) {

   # returns an array containing data parsed from the requested region file
   
   $mapcoldbcol = array(
      'ST'=>'state', 
      'COUNTY'=>'county',
      'LAT'=>'lat',
      'LON'=>'lon',
      'TOTL'=>'total',
      'DPRT'=>'departure',
      'FRACT'=>'fraction'
   );
   

   $outarr = array();
   $rowraw = array();
   $outdata = array();
   $gagedata = file_get_contents($uri);
   $gagelines = explode("\n", $gagedata);
   $error = '';
   $i = 0; # counter for parsed lines

   print("<b>Retrieving:</b> $uri<br>");

   foreach ($gagelines as $thisline) {
      $firstchar = substr(ltrim($thisline),0,1);
      #print("<b>Debug:</b> $firstchar <br>");
      if ($firstchar <> '#') {
         # not a comment line - is it a data line?
         # expect a column header line (e.g. agency_cd   site_no  datetime 13_00065 ...)
         # followed by a column length descriptor (e.g. 5s  15s   16s   14s   14s )
         $i++;
         if ($i == 1) {
            # this is the date span line
            list($startdate, $enddate) = explode("through", str_replace(' ', '', $thisline));
            $startdate = str_replace(' ', '', $startdate);
            $enddate = str_replace(' ', '', $enddate);
            $error .= "Time period from $startdate to $enddate. <br>";
         }
      }
      if ($i == 2) {
         # this is the header line
         $incols = preg_split("/[\s,]+/", $thisline);
         $insql = 'insert into precip_departure ( ';
         $indel = '';
         $k = 0;
         $cols = array();
         # assemble insert query, and create array for mapped column names
         foreach ($incols as $thiscol) {
            $insql .= $indel . $mapcoldbcol[$thiscol];
            array_push($cols, $mapcoldbcol[$thiscol]);
            $indel = ',';
            $k++;
         }
         $insql .= ', start_date, end_date, ptype, period, src_citation';
         $insql .= ' ) ';
         if ($k < count($mapcoldbcol)) {               
            # columns not found, there must be some error, or change of format
            # abort and alert the user
            $error = "Not all expected columns were found. <br>";
            $error .= "Found: " . join(',', $incols) . "<br>";
            $error .= ".  Expected: " . join(',', array_keys($mapcoldbcol)) . "<br>";
            $outdata['error'] = $error;
            return $outdata;
         }
      }
      if ($debug) { print("<b>Debug</b>: $thisline <br>"); }
      if ($i > 2) {
         array_push($rowraw, $thisline);
         # this is a good dataline, past the comments, header and format lines
         if (ltrim($thisline) <> '') {
          $thesecols = preg_split("/[\s,]+/", ltrim(rtrim($thisline)));
          $j = 0;
          reset($cols);
          $thisrowarr = array();
          foreach ($cols as $thiscol) {
             $thisrowarr[$thiscol] = $thesecols[$j];
             $j++;
          }
          array_push($outarr, $thisrowarr);
          if ($debug) {
             print("<b>Debug</b>: ");
             print_r($thisrowarr);
             print("<br>"); }
         }
         $valsql = 'values ( ';
         $valsql .= "'" . join ("','", $thesecols) . "'";
         $valsql .= " , '$startdate'::timestamp, '$enddate'::timestamp, '$type', '$period', 2 ";
         $valsql .= ")";
         
         # delete old value for this period, if any
         $st = $thisrowarr['state'];
         $co = $thisrowarr['county'];
         $dbobj->querystring = "  delete from precip_departure ";
         $dbobj->querystring .= " where state = '$st' ";
         $dbobj->querystring .= "    and county = '$co'";
         $dbobj->querystring .= "    and start_date = '$startdate'::timestamp ";
         $dbobj->querystring .= "    and end_date = '$enddate'::timestamp ";
         $dbobj->querystring .= "    and period = '$period' ";
         $dbobj->querystring .= "    and ptype = '$type' ";
         if ($debug) { print ("$dbobj->querystring ; <br>"); }
         $dbobj->performQuery();
         
         $dbobj->querystring = $insql . $valsql;
         if ($debug) { print ("$dbobj->querystring ; <br>"); }
         $dbobj->performQuery();
      }
   }
   $error .= " $i rows parsed for period $startdate to $enddate. <br>";
   $outdata['columns'] = $cols;
   $outdata['row_array'] = $outarr;
   $outdata['row_tab'] = $rowraw;
   $outdata['error'] = $error;
   return $outdata;
}



?>
