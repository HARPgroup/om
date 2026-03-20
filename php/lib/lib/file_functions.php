<?php


function fileSelectForm($varname,$dirpath,$fileext, $debug=0) {

   if ($debug) {
      print("Searching: $dirpath for $fileext <br>");
   }
   print("<select name='$varname'>\n");
   # cd-rom file
   $handle=opendir($dirpath);
   $lowext = strtolower($fileext);
   while (false!==($file = readdir($handle))) {
      if ( ($file != "." && $file != "..") and (substr(strtolower($file),(strlen($file) - strlen($lowext)),strlen($lowext)) == $lowext) ) {
         print ("<option value='$file'>$file</option>\n");
      }
   }
   closedir($handle);
   print("</select>\n");
}

function fileSelectedForm($varname,$dirpath,$fileext,$selectedfile,$debug=0) {

   if ($debug) {
      print("Searching $dirpath for files with extension $fileect, $selected file already selected.<br>");
   }

   print("<select name='$varname'>\n");
   # cd-rom file
   $handle=opendir($dirpath);
   $thisdir = array();
   while (false!==($file = readdir($handle))) {
      array_push($thisdir, $file);
   }

   closedir($handle);
   sort($thisdir);
   foreach ($thisdir as $file) {
      if ( ($file != "." && $file != "..") and (substr($file,(strlen($file) - strlen($fileext)),strlen($fileext)) == $fileext) ) {
         if ($file == $selectedfile) {
            $seltext = " SELECTED";
         } else {
            $seltext = "";
         }
         print ("<option value='$file'$seltext>$file</option>\n");
      }
   }
   print("</select>\n");
}

function fileMultiSelectedForm($varname,$dirpath,$fileext,$height,$selectedfiles) {

   print("<select name='$varname");
   print("[]' size='$height' multiple>\n");
   # cd-rom file
   $selarray = explode(',',$selectedfiles);
   $handle=opendir($dirpath);
   $thisdir = array();
   while (false!==($file = readdir($handle))) {
      array_push($thisdir, $file);
   }

   closedir($handle);
   sort($thisdir);

   foreach ($thisdir as $file) {
      if ( ($file != "." && $file != "..") and (substr($file,(strlen($file) - strlen($fileext)),strlen($fileext)) == $fileext) ) {
         if ( in_array($file, $selarray) ) {
            $seltext = " SELECTED";
         } else {
            $seltext = "";
         }
         print ("<option value='$file'$seltext>$file</option>\n");
      }
   }
   print("</select>\n");
}

function getFileArray($dirpath,$fileext) {

   # returns an array of all files in a path with given extension
   $handle=opendir($dirpath);
   $outar = array();
   //while (false!==($file = readdir($handle))) {
   while ( $file = readdir($handle) ) {
      if ( ($file != "." && $file != "..") and (strtolower(substr($file,(strlen($file) - strlen($fileext)),strlen($fileext))) == strtolower($fileext)) ) {
         array_push($outar,$file);
      }
   }
   closedir($handle);
   return $outar;
}

function searchFileArray($dirpath,$filenamecontains) {

   # returns an array of all files in a path with given extension
   $handle=opendir($dirpath);
   $outar = array();
   while (false!==($file = readdir($handle))) {
      if ( strstr($file, $filenamecontains) or preg_match($filenamecontains, $file) ) {
         array_push($outar,$file);
      }
   }
   closedir($handle);
   return $outar;
}


function writeArrayToFile($filename,$thisarray) {

   # if $overwrite is false, it will append
   $fp = fopen($filename,"w");

   foreach($thisarray as $thisline) {
      fwrite($fp,"$thisline\r");
   }

   fclose($fp);
}

function putArrayToFile($filename,$thisarray,$overwrite) {

   # if $overwrite is false, it will append
   if ($overwrite) {
      $options = 'w';
   } else {
      $options = 'a';
   }
   $fp = fopen($filename,"$options");

   foreach($thisarray as $thisline) {
      $thiswrite = chop($thisline);
      fwrite($fp,"$thiswrite\r");
   }

   fclose($fp);
}

function putArrayToFilePlatform($filename,$thisarray,$overwrite,$platform) {

   # if $overwrite is false, it will append
   if ($overwrite) {
      $options = 'w';
   } else {
      $options = 'a';
   }

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

   $fp = fopen($filename,"$options");

   foreach($thisarray as $thisline) {
      $thiswrite = chop($thisline);
      fwrite($fp,"$thiswrite$endline");
   }

   fclose($fp);
}

function array2Delimited($thisarray, $delim=',', $header=0,$platform='unix') {
   // returns a formatted string

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
   $outstring = '';
   $count = 0;
   foreach($thisarray as $thisline) {
      if (is_array($thisline)) {
         if (!$count and $header) {
            $outstring .= join ($delim, array_keys($thisline)) . $endline;
         }
         $outstring .= join ($delim, array_values($thisline)) . $endline;
      } else {
         $outstring .= $thisline . $endline;
      }
      $count++;
   }

   return $outstring;
}

function readDelimitedFile_fgetcsv($filename,$delimiter=',', $headerline=0, $numlines = -1) {
   # $headerline = 1 indicates that the first line contains column names,
   #                 if this is the case, we create an associative array to return
   
   if ($headerline) {
      $outarr = buildStock($filename, $delimiter);
      return $outarr;
   }
   
   $fp = fopen($filename,"r");
   $maxlinewidth = 8192;
   $thisarr = array();
   $outarr = array();
   
   if ($numlines == -1) {

      while($thisarr = fgetcsv($fp,$maxlinewidth, $delimiter)) {
         array_push($outarr,$thisarr);
      }
   } else {
      $numread = 1;
      while( ($thisarr = fgetcsv($fp,$maxlinewidth, $delimiter)) and ($numread <= $numlines)) {
         array_push($outarr,$thisarr);
         $numread++;
      }
   }
      

   fclose($fp);

   return $outarr;
}

function readDelimitedFile($filename,$delimiter=',', $headerline=0, $numlines = -1) {
   # $headerline = 1 indicates that the first line contains column names,
   #                 if this is the case, we create an associative array to return
   
   if ($headerline) {
      $outarr = buildStock($filename, $delimiter);
      return $outarr;
   }
   
   $fp = fopen($filename,"r");
   $maxlinewidth = 4096;
   $thisarr = array();
   $outarr = array();
   
   if ($numlines == -1) {

      while($thisline = fgets($fp,$maxlinewidth)) {
         if ($delimiter <> 'null') {
            $thisarr = explode($delimiter,ltrim(chop($thisline)));
         } else {
            $thisarr = $thisline;
         }
         array_push($outarr,$thisarr);
      }
   } else {
      $numread = 1;
      while( ($thisline = fgets($fp,$maxlinewidth)) and ($numread <= $numlines)) {
         if ($delimiter <> 'null') {
            $thisarr = explode($delimiter,ltrim(chop($thisline)));
         } else {
            $thisarr = $thisline;
         }
         array_push($outarr,$thisarr);
         $numread++;
      }
   }
      

   fclose($fp);

   return $outarr;
}

function delimitedFileToTable($dbobject, $filename, $delimiter=',', $tblname, $isPerm=0, $numlines = -1, $columns=array(), $dbcoltypes=array(), $printonly=0) {
   // file must have column headings, or this will break
   // $isPerm - is the table permanent?  Default to NO
   error_log("Parsing File $filename with delimiter $delimiter");
   $outarr = buildStock($filename, $delimiter);
   error_log("Stock Build FINISHED, calling array2Table");
   //$dbobject->debug = 1;
   $dbobject->array2Table($outarr, $tblname, $columns, $dbcoltypes, 1, $buffer, $isPerm, $printonly);
   //$dbobject->debug = 0;
   return $outarr;
   
}

function buildStock($filename, $delimiter=',', $debug = 0) {
   $handle = fopen($filename, "r");
   $fields = fgetcsv($handle, 0, "$delimiter");

   while($data = fgetcsv($handle, 0, "$delimiter")) {
      $detail[] = $data;
   }

   $x = 0;
   $y = 0;
   $stock = array();

   if (count($detail) > 0) {
      foreach($detail as $i) {
         foreach($fields as $z) {
             $stock[$x][$z] = $i[$y];
             if ($debug) {
                $val = $i[$y];
                error_log("setting [ $x ] [ $z ] = $val ($i [ $y ]) \n");
             }
             $y++;
         }
         $y = 0;
         $x++;
      }
   }
   fclose($handle);
   return $stock;
}

function putDelimitedFile($filename,$thisarray,$thisdelim=',',$overwrite=1,$platform='unix',$header=0) {

   # if $overwrite is false, it will append
   if ($overwrite) {
      $options = 'w';
   } else {
      $options = 'a';
   }

   switch ($platform) {

      case 'unix':
      $endline = "\n";
      break;

      case 'dos':
      $endline = "\r";
      break;

      default:
      $endline = "\r";
      break;
   }

   $fp = fopen($filename,"$options");
   $linecount = 0;
   foreach($thisarray as $thisline) {
      if ($header and ($linecount == 0)) {
         $deline = join($thisdelim,array_values(array_keys($thisline)));
         fwrite($fp,"$deline$endline");
      }
      $deline = join($thisdelim,array_values($thisline));
      fwrite($fp,"$deline$endline");
      $linecount++;
   }

   fclose($fp);
}

function putExcelFile($export_file, $fileprops) {
   
   if ( (substr($export_file, 0, 1) == '/') ) {
      $streamfix = 'xlsfile:/';
   } else {
      $streamfix = 'xlsfile://';
   }
   $fp = fopen("$streamfix$export_file", "wb");
   error_log("Opening " . "$streamfix$export_file" );
   if (!is_resource($fp))
   {
      error_log("Cannot open $export_file");
   }
   
   fwrite($fp, serialize($fileprops));
   fclose($fp);
   error_log("File: $export_file saved.");
}


function putXLSXFile($export_file, $sheet_name, $fileprops) {
   /** Create a new PHPExcel Object **/
   if (!class_exists("PHPExcel")) {
      error_log("Calls PHPExcel does not exist.");
      return false;
   }
   
   $objPHPExcel = new PHPExcel();
   // construct the flat array from assoc with keys as first row
   $flat = array();
   $flat[] = array_keys($fileprops[0]);
   // Loop through the data, adding it to the sheet  
   foreach ( $fileprops as $item ) {  
      // Write each item to the sheet  
     $flat[] = array_values($item);
   }
   
   $worksheet = $objPHPExcel->getActiveSheet();
   $worksheet->fromArray($flat,NULL,'A1',true);
   $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");
   $objWriter->save($export_file);
   return true;
}


function putExcel2kFile($export_file, $sheet_name, $fileprops) {

   // assumes that require_once "$libpath/PEAR/Spreadsheet/Excel/Writer.php"; 
   // has already been called in a config file
   // Create an instance, passing the filename to create
   error_log("Creating XLS writer ");
   $xls = new Spreadsheet_Excel_Writer($export_file);
   $xls->setVersion(8); // sets to e3xcel97 to allow long columns > 255 chars
   if (is_object($xls)) {
      error_log("XLS writer created successfully");
      // Add a worksheet to the file, returning an object to add data to
      $sheet = $xls->addWorksheet($sheet_name);

      // Write column headers
      $currentRow = 0;
      $headers = array_keys($fileprops[0]);
      error_log("Writing header line " . print_r($headers,1));
      $sheet->writeRow($currentRow,0,$headers);  
      error_log("Finished header line");

      // Use this to keep track of the current row number  
      $currentRow++;  

      // Loop through the data, adding it to the sheet  
      foreach ( $fileprops as $item ) {  
         // Write each item to the sheet  
        $sheet->writeRow($currentRow,0,$item);  
        $currentRow++;  
      }

      // Finish the spreadsheet, dumping it to the browser
      $xls->close(); 
      error_log("Wrote excel file $export_file ");
   }
}


function filemtime_remote($uri)
{
    $uri = parse_url($uri);
    $handle = @fsockopen($uri['host'],80);
    if(!$handle)
        return 0;

    fputs($handle,"GET $uri[path] HTTP/1.1\r\nHost: $uri[host]\r\n\r\n");
    $result = 0;
    while(!feof($handle))
    {
        $line = fgets($handle,1024);
        if(!trim($line))
            break;

        $col = strpos($line,':');
        if($col !== false)
        {
            $header = trim(substr($line,0,$col));
            $value = trim(substr($line,$col+1));
            if(strtolower($header) == 'last-modified')
            {
                $result = strtotime($value);
                break;
            }
        }
    }
    fclose($handle);
    return $result;
}
// echo filemtime_remote('http://www.somesite.com/someimage.jpg');

?>
