<?php

function reverse_strrchr($haystack, $needle)
{
   return strrpos($haystack, $needle) ? substr($haystack, 0, strrpos($haystack, $needle) +1 ) : false;
}



/**
 * Output - Deals with all html output
 *
 */
class FNOutput{
   // handles the html output

   var $html;
   var $server;
   var $silent = 0; #whether to use print (0), or to return a string (1)

   // variables set by calling function
   var $imgTypes;
   var $embedTypes;
   var $htmlTypes;
   var $phpTypes;
   var $miscTypes;
   var $dateFormat;
   var $ignoreFiles;
   var $ignoreFolders;
   var $pathToHere;
   var $flickr = false;

   # set the folliwing (1) to enable a file select radio button, and subsequent setting of a hidden form variable
   # to be equal to the value of this file
   var $fileselect = 0;
   var $fieldname = '';
   var $formname = '';

   var $containerid = 1;


   function FNOutput(){
      $this->html = "";
   }

   function sendOutput(){
      if ($this->silent) {
         return $this->html;
      } else {
         echo($this->html);
      }
   }

   /**
    * outputs html list of folders
    *
    * @param array $folders
    */
   function folderList($folders){
      // do the folders
      $foldersret = "";
      for($i = 0; $i<count($folders);$i++){
         $file    = $folders[$i][0];
         $path    = $folders[$i][1];
         $isOpen = $folders[$i][2];
         if(substr($path,0,3) == ".//"){ $hPath = substr($path,3); }else{ $hPath = $path; };
         if(!in_array($hPath,$this->ignoreFolders)){
            if($isOpen){ $class1 = "open"; $class2 = "contents_open"; }else{ $class1 = "closed"; $class2 = "contents"; }
            if(file_exists("$path/fComments.txt")){
               $comment = "<span class=\"folder_comment\"> - " . file_get_contents("$path/fComments.txt") . "</span>";
            }else{
               $comment = "";
            }
            $path = str_replace("//","/",$path);

            $foldersret .= "

            <!-- START FOLDER -->
   <li class=\"$class1\" id=\"li_fn_$this->containerid".$this->stripID("$path/$file")."\">
      <!-- FOLDER TITLE -->
      <a href=\"?dir=$path&containerid=$this->containerid\" class=\"fn_$this->containerid".$this->stripID("$path/$file")." $path $this->containerid\" title=\"click to open this folder\">$file </a> <span>$comment</span>
      <!-- START CONTENTS -->
      <ul class=\"$class2\" id=\"F_fn_$this->containerid".$this->stripID("$path/$file")."\" >";
         if($isOpen){
            $list = new FNFileList;
            $list->containerid = $this->containerid;
            $list->sortBy = $this->sortBy;
            $list->sortDir = $this->sortDir;
            $list->imgTypes = $this->imgTypes;
            $list->embedTypes = $this->embedTypes;
            $list->htmlTypes = $this->htmlTypes;
            $list->phpTypes = $this->phpTypes;
            $list->miscTypes = $this->miscTypes;
            $list->dateFormat = $this->dateFormat;
            $list->ignoreFiles = $this->ignoreFiles;
            $list->ignoreFolders = $this->ignoreFolders;
            $list->pathToHere = $this->pathToHere;
            $list->setShowTypes();
            if (count($this->restrictTypes) > 0) {
               $list->allowedTypes = $this->restrictTypes;
            }
            $rec_folders = $list->getFolderArray($path);
            $rec_files = $list->getFilesArray($path);
            $foldersret .= $this->folderList($rec_folders);
            $foldersret .= $this->fileList($rec_files);
         }else{
            $foldersret .= "<li>Empty</li>";
         }

      $foldersret .= "</ul>
      <!-- END CONTENTS -->
   </li>
   <!-- END FOLDER -->";


         }
      }
      return $foldersret;
   }

   /**
    * outputs html list of files
    *
    * @param array $files
    */
   function fileList($files){
      // do the files;
      $filesret = "";
      for($i = 0; $i<count($files);$i++){
         $file = $files[$i][0];
         $path = $files[$i][1];
         $isOpen = $files[$i][2];
         if(substr($path,0,3) == ".//"){ $hPath = substr($path,3); }else{ $hPath = $path; };
         if(!in_array($hPath,$this->ignoreFiles) && $file != "fComments.txt"){
            if($isOpen){ $class1 = "file_open icon_".$this->getExt($file); $class2 = "props_open"; }else{ $class1 = "file icon_".$this->getExt($file); $class2 = "props"; }
            // remove double slash
            $path = str_replace("//","/",$path);

            # added in to allow file selection, and setting of form variable value to be equal to the selected file
            if ($this->fileselect) {
               $onclick = "document.forms[\"$this->formname\"].elements.$this->fieldname" . ".value=\"$path\";";
               $fileselecthtml = "<input type=radio name='$this->fieldname' value='$path' onclick='$onclick'>";
            } else {
               $fileselecthtml = '';
            }

            $filesret  .= "
            <!-- START FILE -->
            <li class=\"$class1\">
               <!-- FILE TITLE -->
               ". $fileselecthtml . $this->doFileLink($file,$path). " <a href=\"?dir=".$this->stripID("F_$path/$file")."\" class=\"properties ".$this->stripID("$path/$file")."\" title=\"show properties\"><span>View $file Properties</span></a>
               <!-- START PROPERTIES -->
               <dl class=\"$class2\" id=\"".$this->stripID("F_$path/$file")."\">
                  ".$this->doFileProps($file,$path) . "
               </dl>
               <!-- END PROPERTIES -->
            </li>
            <!-- END FILE -->";
         }
      }
      return $filesret ;
   }

   /**
    * returns html properties of given file
    *
    * @param string $file
    * @param string $path
    */
   function doFileProps($file,$path){
      // get the file extension for checking file type:
      $ext = substr(strrchr($file, '.'), 1);
      if(substr($path,0,3) == ".//"){
         $absolute = $this->pathToHere . substr($path,3);
      }else{
         $absolute = $this->pathToHere . $path;
      }

      if(in_array(strtolower($ext),$this->imgTypes)){
         // get image dimensions
         $imageDim = @getimagesize($path);
         $ret = "
         <dt>last changed:</dt>
         <dd>" . date($this->dateFormat, filectime($path)) . "</dd>
         <dt>dimensions:</dt>
         <dd>".$imageDim[0]."x".$imageDim[1]."</dd>
         <dt>size:</dt>
         <dd>" . $this->returnFileSize(filesize($path)) . "</dd>
         <dt>HTML Image Code:</dt>
         <dd><kbd onclick=\"selectThis(this);\">&lt;img src=\"$absolute\" width=\"".$imageDim[0]."\" height=\"".$imageDim[1]."\" alt=\"$file\" /&gt;</kbd></dd>";
         if($this->flickr == true) $ret .= "<dt>Options:</dt><dd><a href=\"javascript:sendToFlickr('$absolute');\" title=\"send this image to Flickr\">Send to Flickr</a></dd>";
         return $ret;
      }else if(in_array(strtolower($ext),$this->embedTypes)){
         return "
         <dt>last changed:</dt>
         <dd>" . date($this->dateFormat, filectime($path)) . "</dd>
         <dt>size:</dt>
         <dd>" . $this->returnFileSize(filesize($path)) . "</dd>
         <dt>HTML Embed Code:</dt>
         <dd><kbd  onclick=\"selectThis(this);\">&lt;embed autoplay=\"false\" src=\"$absolute\" /&gt;</kbd></dd>";
      }else if(in_array(strtolower($ext),$this->phpTypes) || in_array(strtolower($ext),$this->htmlTypes)){
         return "
         <dt>last changed:</dt>
         <dd>" . date($this->dateFormat, filectime($path)) . "</dd>
         <dt>size:</dt>
         <dd>" . $this->returnFileSize(filesize($path)) . "</dd>
         <dt>HTML Link:</dt>
         <dd><kbd onclick=\"selectThis(this);\">&lt;a href=\"$absolute\"&gt;$file&lt;/a&gt;</kbd></dd>
         <dt>Options:</dt>
         <dd><a href=\"$PHP_SELF?src=$path\" title=\"view $file source code\">view source</a></dd>";
      }else if(in_array(strtolower($ext),$this->miscTypes)){
         $ret =  "
         <dt>last changed:</dt>
         <dd>" . date($this->dateFormat, filectime($path)) . "</dd>
         <dt>size:</dt>
         <dd>" . $this->returnFileSize(filesize($path)) . "</dd>
         <dt>HTML Link:</dt>
         <dd><kbd onclick=\"selectThis(this);\">&lt;a href=\"$absolute\" title=\"$file\" &gt;$file&lt;/a&gt;</kbd></dd>
         <dt>Options:</dt>
            <dd><a href=\"$path\" title=\"download $file\">download</a></dd>";
         return $ret;
      }
   }

   /**
    * returns html link of given file
    *
    * @param string $file
    * @param string $path
    */
   function doFileLink($file,$path){
      // get the file extension for checking file type:
      $ext = substr(strrchr($file, '.'), 1);
      if(in_array(strtolower($ext),$this->imgTypes)){
         return "<a href=\"$PHP_SELF?view=$path\" title=\"view $file\">$file</a>";
      }else if(in_array(strtolower($ext),$this->embedTypes)){
         return "<a href=\"$PHP_SELF?view=$path\" title=\"view $file\">$file</a>";
      }else if(in_array(strtolower($ext),$this->phpTypes) || in_array(strtolower($ext),$this->htmlTypes)){
         return "<a href=\"$path\" title=\"open $file\">$file</a>";
      }else if(in_array(strtolower($ext),$this->miscTypes)){
         return "<a href=\"$path\" title=\"download $file\">$file</a>";
      }
   }

   /**
    * returns fileNice view link of given file
    *
    * @param string $file
    * @param string $path
    */
   function doFileLinkInt($file,$path){
      // get the file extension for checking file type:
      $ext = substr(strrchr($file, '.'), 1);
      if(in_array(strtolower($ext),$this->imgTypes)){
         return "<a href=\"$PHP_SELF?view=$path\" title=\"view $file\">$file</a>";
      }else if(in_array(strtolower($ext),$this->embedTypes)){
         return "<a href=\"$PHP_SELF?view=$path\" title=\"view $file\">$file</a>";
      }else if(in_array(strtolower($ext),$this->phpTypes) || in_array(strtolower($ext),$this->htmlTypes)){
         return "<a href=\"$PHP_SELF?src=$path\" title=\"view $file source code\">$file</a>";
      }else if(in_array(strtolower($ext),$this->miscTypes)){
         return "<a href=\"$path\" title=\"download $file\">$file</a>";
      }
   }


   function searchResults($arr,$sstring){
      //echo("<pre>");
      //print_r($arr);
      //echo("</pre>");
      $output = "<div id=\"searchResults\"><h3>Search results for '$sstring':</h3>";
      //$output = "<h3>Search results for '$sstring':</h3>";
      if(count($arr) > 0){
         for($i = 0; $i < count($arr); $i++){
            $folderOK = true;
            $fileOK = false;
            if(substr($arr[$i][1],0,3) == ".//"){ $hPath = substr($arr[$i][1],3); }else{ $hPath = $arr[$i][1]; };
            // make a folders array to check that none are in the ignore list
            $folders = explode("/",$this->getFolder($hPath));
            while(count($folders) > 0){
               $tempPath = implode("/",$folders);
               //$output .= "<br>Checking: " .$tempPath;
               if(in_array($tempPath,$this->ignoreFolders)){
                  //$output .= "<br />" . $tempPath . " is in ignoreFolders<br />";
                  $folderOK = false;
               }
               array_pop($folders);
            }
            // check file is not private
            if(substr($arr[$i][1],0,3) == ".//"){ $hPath = substr($arr[$i][1],3); }else{ $hPath = $arr[$i][1]; };
            if(!in_array($hPath,$this->ignoreFiles) && $arr[$i][0] != "fComments.txt"){
               $fileOK = true;
            }
            if($folderOK == true && $fileOK == true){
               $output .= "<dt>" . $this->doFileLinkInt($arr[$i][0],$arr[$i][1]) . "</dt>";
               $output .= "<dd>(" . $arr[$i][1] . ")</dd>";
            }
         }
      }else{
         $output .= "Sorry, the search term '$sstring' returned no results.";
      }
      $output .= "</div>";

      if ($this->silent) {
         return $output;
      } else {
         echo($output);
      }
   }


   /**
    * returns human readable file size
    *
    * @param int $sizeInBytes
    * @param int $precision
    */
   function returnFileSize($sizeInBytes,$precision=2){
      if($sizeInBytes < 1024){
         return "$sizeInBytes bytes";
      }else{
         $k = intval($sizeInBytes/1024);
         if($k < 1024){
            return $k . "k ish";
         }else{
            $m = number_format((($sizeInBytes/1024) / 1024),2);
            return $m . "mb ish";
         }
      }
   }

   function showSource($file){
      if ($this->silent) {
         $sourcecode = "<!-- BEGIN SOURCE OUTPUT -->";
         $sourcecode .= $this->get_sourcecode($file);
         $sourcecode .= "<!-- END SOURCE OUTPUT -->";
      } else {
         echo "<!-- BEGIN SOURCE OUTPUT -->";
         echo $this->get_sourcecode($file);
         echo "<!-- END SOURCE OUTPUT -->";
      }
   }

   function nextAndPrev($currentPic){
      $fileNum = 0;
      $fileArray = array();
      $dir = $this->getFolder($currentPic);
      $hook = @opendir($dir);
      while (false !== ($file = readdir($hook))) {
         array_push($fileArray,$file);
      }
      // order the file list the same as in the dir listing
      // ignorecasesort($fileArray);
      for($i = 0; $i < count($fileArray); $i++){
         // look for current pic
         if($dir."/".$fileArray[$i] == $currentPic){
            $currentFileNum = $i;
         }
      }
      // loop through fileArray to find previous and next images
      for($i = $currentFileNum-1; $i>=0; $i--){
         $type=$this->getExt($fileArray[$i]);
         if(in_array(strtolower($type),$this->imgTypes)){
            $prev = $dir."/".$fileArray[$i];
            break;
         }
      }
      for($i = $currentFileNum+1; $i<count($fileArray); $i++){
         $type=$this->getExt($fileArray[$i]);
         if(in_array(strtolower($type),$this->imgTypes)){
            $next = $dir."/".$fileArray[$i];
            break;
         }
      }
      for($i = 0; $i<=count($fileArray); $i++){
         $type=$this->getExt($fileArray[$i]);
         if(in_array(strtolower($type),$this->imgTypes)){
            $first = $dir."/".$fileArray[$i];
            break;
         }
      }
      return array($prev,$next,$first);
      closedir($hook);
   }


   function viewFile($file){
      $innerHTML = '';
      $path = $_GET['view'];
      $ext = substr(strrchr($_GET['view'], '.'), 1);
      if(substr($path,0,3) == ".//"){
         $absolute = $this->pathToHere . substr($path,3);
      }else{
         $absolute = $this->pathToHere . $path;
      }
      if(in_array($ext,$this->imgTypes)){
         // we're showing an image
         $imageDim = @getimagesize($file);
         $preNext = $this->nextAndPrev($_GET['view']);
         $innerHTML .= "\n<div id=\"imgWrapper\">\n<div id=\"imgPreview\">";
         if($preNext[0] != ""){
            $innerHTML .= "\n<a href=\"$PHP_SELF?view=".$preNext[0]."\">Prev</a>";
         }
         if($preNext[0] != "" && $preNext[1] != ""){
            $innerHTML .= " | ";
         }
         if($preNext[1] != ""){
            $innerHTML .= "\n<a href=\"$PHP_SELF?view=".$preNext[1]."\">Next</a>";
         }
         $innerHTML .= "\n<br /><br />";
         if($preNext[1] != ""){
            $innerHTML .= "\n<a href=\"$PHP_SELF?view=".$preNext[1]."\"><img src=\"".$_GET['view']."\" width=\"".$imageDim[0]."\" height=\"".$imageDim[1]."\" alt=\"".$_GET['view']."\" /></a>\n<br /><br />";
         }else{
            $innerHTML .= "\n<img src=\"".$_GET['view']."\" /><br /><br />";
         }
         $innerHTML .= "\n<a href=\"$PHP_SELF?view=".$this->getFolder($file)."\" title=\"close image\">close</a>";
         $innerHTML .= "\n</div>\n</div>\n<br /><br />
         \n<span id=\"slidelink\"><a href=\"javascript:startSlideshow('$file');\" title=\"start slideshow\">start slideshow</a></span><br /><br />
         \n<div id=\"picinfo\"><strong>".basename($_GET['view'])."</strong><br />
         last changed: " . date($this->dateFormat, filectime($_GET['view'])) . "<br />
         dimensions: ".$imageDim[0]."x".$imageDim[1]."<br />
         size: " . $this->returnFileSize(filesize($_GET['view'])) . "
         \n<br />";
         if($this->flickr == true) $innerHTML .= "\n<a href=\"javascript:sendToFlickr('$absolute');\" title=\"send this image to Flickr\">Send to Flickr</a><br />";
         $innerHTML .= "\n<br />\n</div>";
      }else if(in_array($ext,$this->embedTypes)){
         // we're embedding
         $dimensiones=getimagesize($_GET['view']);
         echo$innerHTML .= "<div id=\"imgWrapper\"><div id=\"imgPreview\"><embed autoplay=\"false\" src=\"".$_GET['view']."\" ".$dimensiones[3]."></embed></div></div><br /><br />".$_GET['view']."<br />".$_GET['view']."<br />
         last changed: " . date($this->dateFormat, filectime($_GET['view'])) . "<br />
         size: " . $this->returnFileSize(filesize($_GET['view'])) . "
         <br /><br />";
      }

      return $innerHTML;
   }

   function getExt($file){
      return substr(strrchr($file, '.'), 1);
   }

   function getFolder($filePath){
      $temp = explode("/",$filePath);
      array_pop($temp);
      return implode("/",$temp);
   }

   /**
    * returns syntax hi-lited html / php
    *
    * @author  unknown
    * @param string $filename
    *
    */
   function get_sourcecode($filename) {
       // Get highlighted code
       $html_code = highlight_file($filename, TRUE);
       // Remove the first "<code>" tag from "$html_code" (if any)
       if (substr($html_code, 0, 6) == "<code>") {
           $html_code = substr($html_code, 6, strlen($html_code));
       }
       // Replacement-map to replace deprecated "<font>" tag with "<span>"
       $xhtml_convmap = array(
           '<font' => '<span',
           '</font>' => '</span>',
           'color="' => 'style="color:'
       );
       // Replace "<font>" tags with "<span>" tags, to generate a valid XHTML code
       $html_code = strtr($html_code, $xhtml_convmap);
       ### Okay, Now we have a valid XHTML code
       $retval = "<code>" . $html_code;    // Why? remember Bookmark #1, that I removed the tag "<code>"
       return $retval;
   }

   function stripID($str){
      $pattern = '/[^\d\w]/';
      $replace = '_';
      return preg_replace($pattern, $replace, $str);
   }

}

/**
 * FNFileList - Handles directory/file listings
 *
 */
class FNFileList{

   var $allowHTML;
   var $allowScripts;
   var $allowImages;
   var $allowEmbed;
   var $allowMisc;

   var $hook;
   var $folders = array();
   var $files = array();
   var $file;
   var $path;

   var $openPath;

   var $allowedTypes = array();

   //default sort
   var $sortBy;
   var $sortDir;
   var $searchArray = array();

   // properties controlling output
   var $showImg = 'show';
   var $showEmbed = 'show';
   var $showHtml = 'show';
   var $showScript = 'show';
   var $showMisc = 'show';
   var $flickr = false;

   // properties to be set by calling function
   var $imgTypes = '';
   var $embedTypes = '';
   var $htmlTypes = '';
   var $phpTypes = '';
   var $miscTypes = '';
   var $dateFormat = '';
   var $ignoreFiles = '';
   var $ignoreFolders = '';
   var $pathToHere = '';

   var $containerid = 1;

   // return output as string (1), or simply echo as generated (0)
   var $silent = 0;

   // set the folliwing (1) to enable a file select radio button, and subsequent setting of a hidden form variable
   // to be equal to the value of this file
   var $fileselect = 0;
   var $fieldname = '';
   var $formname = '';

   /**
    * Sets up initial variables for the FileList class
    *
    * @return FileList
    */
   function FNFileList(){
      // init the file list and set up necessary variables
      //global $sortBy, $sortDir, $showImg, $showEmbed, $showHtml, $showScript, $showMisc, $imgTypes, $embedTypes, $htmlTypes, $phpTypes, $miscTypes;
      $this->setShowTypes();
      // get openPath
      if(isset($_GET['src'])){
         $this->openPath = $_GET['src'];
      }else if(isset($_GET['view'])){
         $this->openPath = $_GET['view'];
      }else{
         $this->openPath = false;
      }
      $this->sortBy = $sortBy;
      $this->sortDir = $sortDir;
   }

   function setShowTypes() {
      $sortBy = $this->sortBy;
      $sortDir = $this->sortDir;
      $showImg = $this->showImg;
      $showEmbed = $this->showEmbed;
      $showHtml = $this->showHtml;
      $showScript = $this->showScript;
      $showMisc = $this->showMisc;
      $imgTypes = $this->imgTypes;
      $embedTypes = $this->embedTypes;
      $htmlTypes = $this->htmlTypes;
      $phpTypes = $this->phpTypes;
      $miscTypes = $this->miscTypes;

      // set up allowed types
      if($showImg == "show"){
         for($i=0; $i<count($imgTypes); $i++){
            array_push($this->allowedTypes,$imgTypes[$i]);
         }
      }
      if($showEmbed == "show"){
         for($i=0; $i<count($embedTypes); $i++){
            array_push($this->allowedTypes,$embedTypes[$i]);
         }
      }
      if($showHtml == "show"){
         for($i=0; $i<count($htmlTypes); $i++){
            array_push($this->allowedTypes,$htmlTypes[$i]);
         }
      }
      if($showScript == "show"){
         for($i=0; $i<count($phpTypes); $i++){
            array_push($this->allowedTypes,$phpTypes[$i]);
         }
      }
      if($showMisc == "show"){
         for($i=0; $i<count($miscTypes); $i++){
            array_push($this->allowedTypes,$miscTypes[$i]);
         }
      }
   }

   function getFolderArray($dir){
      $folders = array();
      $hook = @opendir($dir);
      while (($file = @readdir($hook))!==false){
         if (substr($file,0,1) != "."){
            $path = $dir."/".$file;
            if(is_dir($path)){
               // get last modified time for date sorting
               $mod = filectime($path);
               if(substr($this->openPath,0,strlen($path)) == $path){
                  array_push($folders,array($file,$path,true,$mod));
               }else{
                  array_push($folders,array($file,$path,false,$mod));
               }
            }
         }
      }
      // sort the array before passing it on
      // make the sort by arrays
      foreach ($folders as $key => $row) {
         $namesTemp[$key]  = strtolower($row[1]);
         $timesTemp[$key] = $row[3];
      }
      // do the sort
      if($this->sortBy == "name"){
         if($this->sortDir == "ascending"){
            @array_multisort($folders, SORT_ASC, SORT_STRING, $namesTemp, SORT_ASC, SORT_STRING);
         }else{
            @array_multisort($folders, SORT_DESC, SORT_STRING, $namesTemp, SORT_DESC, SORT_STRING);
         }
      }else{
         if($this->sortDir == "ascending"){
            @array_multisort($folders, SORT_ASC, SORT_NUMERIC, $timesTemp, SORT_ASC, SORT_NUMERIC);
         }else{
            @array_multisort($folders, SORT_DESC, SORT_NUMERIC, $timesTemp, SORT_DESC, SORT_NUMERIC);
         }
      }
      return $folders;
   }

   function getFilesArray($dir){
      $files = array();
      $hook = @opendir($dir);
      while (($file = @readdir($hook))!==false){
         if (substr($file,0,1) != "."){
            $path = $dir."/".$file;
            if(!is_dir($path) && in_array($this->getExt($file),$this->allowedTypes)){
               // get last modified time for date sorting
               $mod = filectime($path);
               if($path == $this->openPath){
                  array_push($files,array($file,$path,true,$mod));
               }else{
                  array_push($files,array($file,$path,false,$mod));
               }
            }
         }
      }
      // sort the array before passing it on
      // make the sort by arrays
      foreach ($files as $key => $row) {
         $namesTemp[$key]  = strtolower($row[1]);
         $timesTemp[$key] = $row[3];
      }
      // do the sort
      if($this->sortBy == "name"){
         if($this->sortDir == "ascending"){
            @array_multisort($files, SORT_ASC, SORT_STRING, $namesTemp, SORT_ASC, SORT_STRING);
         }else{
            @array_multisort($files, SORT_DESC, SORT_STRING, $namesTemp, SORT_DESC, SORT_STRING);
         }
      }else{
         if($this->sortDir == "ascending"){
            @array_multisort($files, SORT_ASC, SORT_NUMERIC, $timesTemp, SORT_ASC, SORT_NUMERIC);
         }else{
            @array_multisort($files, SORT_DESC, SORT_NUMERIC, $timesTemp, SORT_DESC, SORT_NUMERIC);
         }
      }

      return $files;
   }

   function getFilesRecursive($files,$dir,$noreturn = false){
      if(!is_dir($dir)){
         return false;
      }
      $hook = @opendir($dir);
      while (($file = readdir($hook))!==false){
         if (substr($file,0,1) != "."){
            $path = $dir."/".$file;
            if(!is_dir($path) && in_array($this->getExt($file),$this->allowedTypes)){
               // get last modified time for date sorting
               $mod = filectime($path);
               if($path == $this->openPath){
                  array_push($this->searchArray ,array($file,$path,true,$mod));
               }else{
                  array_push($this->searchArray ,array($file,$path,false,$mod));
               }
            }else{
               if(is_dir($path)){
                  $this->getFilesRecursive($this->searchArray ,$path,true);
               }
            }
         }
      }
      // sort the array before passing it on
      // make the sort by arrays
      foreach ($this->searchArray  as $key => $row) {
         $namesTemp[$key]  = strtolower($row[1]);
         $timesTemp[$key] = $row[3];
      }
      // do the sort
      if($this->sortBy == "name"){
         if($this->sortDir == "ascending"){
            @array_multisort($this->searchArray , SORT_ASC, SORT_STRING, $namesTemp, SORT_ASC, SORT_STRING);
         }else{
            @array_multisort($this->searchArray , SORT_DESC, SORT_STRING, $namesTemp, SORT_DESC, SORT_STRING);
         }
      }else{
         if($this->sortDir == "ascending"){
            @array_multisort($this->searchArray , SORT_ASC, SORT_NUMERIC, $timesTemp, SORT_ASC, SORT_NUMERIC);
         }else{
            @array_multisort($this->searchArray , SORT_DESC, SORT_NUMERIC, $timesTemp, SORT_DESC, SORT_NUMERIC);
         }
      }

      if($noreturn != true){
         return $this->searchArray ;
      }
   }


   function search($sstring){
      $innerHTML = '';
      if(strlen($sstring)>0){
         $this->searchArray = array();
         $f = $this->getFilesRecursive($array,"./");
         $found = array();
         for($i = 0; $i< count($this->searchArray);$i++){
            if(strstr(strtolower($this->searchArray[$i][0]),strtolower($sstring))){
               array_push($found,$this->searchArray[$i]);
            }
         }
         $out = new FNOutput;
         $out->containerid = $this->containerid;
         $out->imgTypes = $this->imgTypes;
         $out->embedTypes = $this->embedTypes;
         $out->htmlTypes = $this->htmlTypes;
         $out->phpTypes = $this->phpTypes;
         $out->miscTypes = $this->miscTypes;
         $out->dateFormat = $this->dateFormat;
         $out->ignoreFiles = $this->ignoreFiles;
         $out->ignoreFolders = $this->ignoreFolders;
         $out->pathToHere = $this->pathToHere;
         $out->flickr = $this->flickr;
         $out->fileselect = $this->fileselect;
         $out->fieldname = $this->fieldname;
         $out->formname = $this->formname;
         $out->silent = 1;
         $innerHTML .= $out->searchResults($found,$sstring);
      }

      return $innerHTML;
   }


   function getDirList($dir){
      $dirHTML = '';
      $this->folders = $this->getFolderArray($dir);
      $this->files = $this->getFilesArray($dir);
      $out = new FNOutput;
      $out->containerid = $this->containerid;
      $out->imgTypes = $this->imgTypes;
      $out->embedTypes = $this->embedTypes;
      $out->htmlTypes = $this->htmlTypes;
      $out->phpTypes = $this->phpTypes;
      $out->miscTypes = $this->miscTypes;
      $out->dateFormat = $this->dateFormat;
      $out->ignoreFiles = $this->ignoreFiles;
      $out->ignoreFolders = $this->ignoreFolders;
      $out->pathToHere = $this->pathToHere;
      $out->flickr = $this->flickr;
      $out->fileselect = $this->fileselect;
      $out->fieldname = $this->fieldname;
      $out->formname = $this->formname;
      $out->silent = 1;
      $out->html .= $out->folderList($this->folders);
      # rwb - debugging, added allowedTypes
      #$out->html .= $out->fileList($this->files)  . print_r($this->allowedTypes,1);
      $out->html .= $out->fileList($this->files);
      $dirHTML .= $out->sendOutput();
      if ($this->silent) {
         return $dirHTML;
      } else {
         echo $dirHTML;
      }
   }

   function getExt($file){
      return substr(strrchr($file, '.'), 1);
   }
   function namesort($a, $b) {
       return strnatcasecmp($a["name"], $b["name"]);
   }

   function unset_by_val($needle,&$haystack) {
      while(($gotcha = array_search($needle,$haystack)) > -1)
      unset($haystack[$gotcha]);
   }

}



/**
 * UserInfo - Handles display of error and debug messages
 *
 */
class UserInfo{
   var $info;
   var $silent = 0;

   function info($str){
      $this->info .= "$str<br />";
   }

   function warn($str){
      $this->info .= "<span class=\"warning\">$str</span><br />";
   }

   function output(){
      $outinfo = '';
      if($this->info != ""){
         $outinfo .= "<div style=\"width:600px; border:0px; background-color:#ffffcc; padding:5px\">";
         $outinfo .= $this->info;
         $outinfo .= "</div>";
      }
      if ($this->silent) {
         return $outinfo;
      } else {
         echo $outinfo;
      }
   }
}


/*********************************************************************/
/*               FNObject: fileNice - wrapper                        */
/*                                                                   */
/*  Wrapper around fileNice classes based on fileNice                */
/*     modified by Robert W. Burgholzer                              */
/*  Heirachical PHP file browser - http://filenice.com               */
/*  Written by Andy Beaumont - http://andybeaumont.com               */
/*                                                                   */
/*  Send bugs and suggestions to stuff[a]fileNice.com                */
/*                                                                   */
/*                                                                   */
/*********************************************************************/

/*********************************************************************/
/*                                                                   */
/* User editable preferences are now stored in fileNice/prefs.php    */
/* for easier maintenance and to assist with some fancy new features */
/* in this and future versions.                                      */
/*                                                                   */
/*********************************************************************/

class FNObject {
   // fileNice version
   var $version = "1.0.2";
   // default skin
   var $defaultSkin = "default";
   //default sort
   var $defaultSort = "name";
   // default sort order
   var $defSortDirection = "ascending";
   // default time to show each image in a slideshow (in seconds)
   var $defaultSSSpeed = 6;
   // Show "send to Flickr" links
   var $flickr = true;
   // any files you don't want visible to the file browser add into this
   // array...
   var $ignoreFiles = array();
   // any folders you don't want visible to the file browser add into this
   // array...
   var $ignoreFolders = array();
   // file type handling, add file extensions to these array to have the
   // file types handled in certain ways
   var $imgTypes   = array();
   var $embedTypes = array();
   var $htmlTypes  = array();
   var $phpTypes   = array();
   var $miscTypes  = array();
   // date format - see http://php.net/date for details
   var $dateFormat = "F d Y ";
   // server specific variables
   var $server = '';
   var $thisDir = '';
   var $dirPath = '';
   var $pathToHere = '';
   var $skindir = 'fileNice/skins';
   var $scriptdir = 'fileNice';
   var $skin = 'default';
   var $ssSpeed = 6000;
   var $sortBy = '';
   var $sortDir = '';
   var $fileselect = 0;
   var $fieldname = '';
   var $formname = '';
   var $restrictTypes = array();

   // set these properties to 0 when using this object as a form component
   var $showFlickrForm = 1;
   var $showSearchForm = 1;
   var $showPrefsForm = 1;
   var $showFilePath = 1;

   // for multiuple containers on one page
   var $containerid = 1;

   function init() {
      $this->ignoreFiles = array(   "file1.txt",
                        "file2.txt",
                        "index.php",
                        "fComments.txt"
                        );

      // any folders you don't want visible to the file browser add into this
      // array...
      $this->ignoreFolders = array("fileNice",
                        "someFolder"
                        );

      // file type handling, add file extensions to these array to have the
      // file types handled in certain ways
      $this->imgTypes   = array("gif","jpg","jpeg","bmp","png");
      $this->embedTypes = array("mp3","mov","aif","aiff","wav","swf","mpg","avi","mpeg","mid");
      $this->htmlTypes  = array("html","htm","txt","css","log");
      $this->phpTypes   = array("php","php3","php4","asp","js");
      $this->miscTypes  = array("pdf","doc","zip","sit","rar","rm","ram","uci","csv");
      $this->server = $_SERVER['HTTP_HOST'];
      $this->thisDir = dirname($_SERVER['PHP_SELF']);
      $this->pathToHere = "http://$this->server$this->thisDir/";
      // name of the php file that handles the ajax requests for fileNice
      $this->fnscript = basename($_SERVER['PHP_SELF']);

      /*********************************************************************/
      /*                                                                   */
      /*  Best not to touch stuff below here unless you know what you're   */
      /*  doing.                                                           */
      /*                                                                   */
      /*********************************************************************/
      $this->names = array("showImg","showEmbed","showHtml","showScript","showMisc");
   }

   function showFNBrowser($getvars, $postvars) {
   // HANDLE THE PREFERENCES

      $innerHTML = '';
      //$innerHTML .= "Inputs: " . print_r($_GET,1) . print_r($_POST,1) . "<br>";

      if($postvars['action'] == "prefs"){
         // lets set the cookie values
         $varsArray = array();
         for($i=0; $i<count($this->names);$i++){
            if($postvars[$this->names[$i]] == "show"){
               $varsArray[$this->names[$i]] = "show";
            }else{
               $varsArray[$this->names[$i]] = "hide";
            }
            setcookie($this->names[$i],$varsArray[$this->names[$i]],time()+60*60*24*365);
            $this->names[$i] = $varsArray[$this->names[$i]];
         }
         // set the skin
         setcookie("skin",$postvars['skin'],time()+60*60*24*365);
         $this->skin = $postvars['skin'];
         // set the slideshow speed
         setcookie("ssSpeed",$postvars['ssSpeed'],time()+60*60*24*365);
         $this->ssSpeed = $postvars['ssSpeed'] * 1000;
         // set the sortBy
         setcookie("sortBy",$postvars['sortBy'],time()+60*60*24*365);
         $this->sortBy = $postvars['sortBy'];
         // set the sortDir
         setcookie("sortDir",$postvars['sortDir'],time()+60*60*24*365);
         $this->sortDir = $postvars['sortDir'];
      }else{
         // retreive prefs
         for($i=0; $i<count($this->names);$i++){
            if(isset($_COOKIE[$this->names[$i]])){
               //echo("COOKIE[".$this->names[$i]."] = " . $_COOKIE[$this->names[$i]] . "<br />");
               if($_COOKIE[$this->names[$i]] != "show"){
                  $this->names[$i] = "hide";
               }else{
                  $this->names[$i] = "show";
               }
            }else{
               $this->names[$i] = "show";
            }
         }
         // GET THE PREFERRED SKIN
         if(isset($_COOKIE['skin'])){
            $this->skin = $_COOKIE['skin'];
         }else{
            $this->skin = $this->defaultSkin;
         }
         // GET THE SLIDE SHOW SPEED
         if(isset($_COOKIE['ssSpeed'])){
            $this->ssSpeed = $_COOKIE['ssSpeed'] * 1000;
         }else{
            $this->ssSpeed = $this->defaultSSSpeed * 1000;
         }
         // GET THE SORT BY AND DIRECTION
         if(isset($_COOKIE['sortBy'])){
            $this->sortBy = $_COOKIE['sortBy'];
         }else{
            $this->sortBy = $this->defaultSort;
         }
         if(isset($_COOKIE['sortDir'])){
            $this->sortDir = $_COOKIE['sortDir'];
         }else{
            $this->sortDir = $this->defSortDirection;
         }
      }


      if($getvars['action'] == "getFolderContents"){
         //if(substr($getvars['dir'],0,2) != ".." && substr($getvars['dir'],0,1) != "/" && $getvars['dir'] != "./" && !stristr($getvars['dir'], '../')){
         if(substr($getvars['dir'],0,2) != ".." && !stristr($getvars['dir'], '../')){
            $dir = $getvars['dir'];
            $list = new FNFileList;
            $list->containerid = $this->containerid;
            $list->sortBy = $this->sortBy;
            $list->sortDir = $this->sortDir;
            $list->imgTypes = $this->imgTypes;
            $list->embedTypes = $this->embedTypes;
            $list->htmlTypes = $this->htmlTypes;
            $list->phpTypes = $this->phpTypes;
            $list->miscTypes = $this->miscTypes;
            $list->dateFormat = $this->dateFormat;
            $list->ignoreFiles = $this->ignoreFiles;
            $list->ignoreFolders = $this->ignoreFolders;
            $list->pathToHere = $this->pathToHere;
            $list->silent = 1;
            $list->fileselect = $this->fileselect;
            $list->fieldname = $this->fieldname;
            $list->formname = $this->formname;
            $list->setShowTypes();
            if (count($this->restrictTypes) > 0) {
               $list->allowedTypes = $this->restrictTypes;
            }
            $innerHTML .= $list->getDirList($dir);
            return $innerHTML;
         }else{
            // someone is poking around where they shouldn't be
            $innerHTML .= "Nothing.";
            return $innerHTML;
         }
      }else if($getvars['action'] == "nextImage"){
         $out = new FNOutput;
         $out->containerid = $this->containerid;
         $out->sortBy = $this->sortBy;
         $out->sortDir = $this->sortDir;
         $out->imgTypes = $this->imgTypes;
         $out->embedTypes = $this->embedTypes;
         $out->htmlTypes = $this->htmlTypes;
         $out->phpTypes = $this->phpTypes;
         $out->miscTypes = $this->miscTypes;
         $out->dateFormat = $this->dateFormat;
         $out->ignoreFiles = $this->ignoreFiles;
         $out->ignoreFolders = $this->ignoreFolders;
         $out->pathToHere = $this->pathToHere;
         $out->silent = 1;
         $tmp = $out->nextAndPrev($getvars['pic']);
         if($tmp[1] == ""){
            $nextpic = $tmp[2];
         }else{
            $nextpic = $tmp[1];
         }
         // get the image to preload
         $tmp2 = $out->nextAndPrev($nextpic);
         // get the image dimensions
         $imageDim = @getimagesize($nextpic);
         $innerHTML .= $nextpic."|".$imageDim[0]."|".$imageDim[1]."|".$tmp2[1];
         return $innerHTML;
      }


      $innerHTML .= "<!-- busy indicators -->";
      $innerHTML .= "<div id=\"overDiv\">&nbsp;</div>";
      $innerHTML .= "<div id=\"busy\">&nbsp;</div>";
      $innerHTML .= "<!-- main -->";
      $innerHTML .= "<div id=\"fnContainer[$this->containerid]\">";
      $innerHTML .= "   <div id=\"header\" class=\"fn_logo\">";
      $sstring = $postvars['sstring'];
      if ($this->showSearchForm) {
         $innerHTML .= "   <form name=\"search\" id=\"search\" action=\"$PHP_SELF\" method=\"post\"><input type=\"text\" name=\"sstring\" id=\"sstring\" value=\"$sstring\" /><input type=\"button\" name=\"search\" value=\"search\" id=\"searchButton\" onclick=\"validateSearch()\" /></form>";
      }
      $innerHTML .= "      <!-- please leave the word fileNice visible on the page, it's only polite really isn't it. -->";
      $innerHTML .= "      Powered By: <a href=\"http://www.filenice.com\" title=\"About fileNice&trade;\" class=\"fn_logo\" target=new>fileNice&#8482;</a>";
      if ($this->showFilePath) {
         $innerHTML .= "      <h2>Files in [<a href=\"index.php\" title=\"reset\"> $this->pathToHere</a>]</h2>";
      }
      if ($this->showPrefsForm) {
         $innerHTML .= "      <h3><a href=\"#\" title=\"edit prefs\" class=\"expander preferences\">(?)</a></h3>";
      }
      $innerHTML .= "   </div>";
      $innerHTML .= "<div id=\"about_filenice\">";
      $innerHTML .= "fileNice&trade; $this->version<br />";
      $innerHTML .= "";
      $innerHTML .= "<!-- please leave the word fileNice and the link to fileNice.com in the about, it's only polite really isn't it. I didn't do all this work just for you to try to pass it off as your own. -->";
      $innerHTML .= "Free open source file browser available from <a href=\"http://fileNice.com\" title=\"fileNice.com\">fileNice.com</a><br />";
      $innerHTML .= "Created by <a href=\"http://andybeaumont.com\" title=\"andybeaumont.com\">Andy Beaumont</a><br />";

      if(file_exists("fileNice/skins/$this->skin/about.txt")){
         $innerHTML .= "<br /><br />";
         $innerHTML .= "Skin:<br />\";";
         $innerHTML .= file_get_contents("fileNice/skins/$this->skin/about.txt");
      }
      $innerHTML .= "</div>";

      if ($this->showPrefsForm) {
         $innerHTML .= "<form name=\"prefs\" action=\"$PHP_SELF\" method=\"post\" id=\"preferences\">";
         $innerHTML .= "Preferences:<br /><br />";
         $innerHTML .= "<fieldset>";
         $innerHTML .= "<legend>Sort by</legend>";
         $innerHTML .= "<input type=\"radio\" name=\"sortBy\" id=\"name\" value=\"name\" ";
         if($this->sortBy == "name") $innerHTML .= "checked=\"checked\"";
         $innerHTML .= "/>";
         $innerHTML .= "<label for=\"name\">file name</label><br />";
         $innerHTML .= "<input type=\"radio\" name=\"sortBy\" id=\"date\" value=\"date\"";
         if($this->sortBy == "date") $innerHTML .= "checked=\"checked\"";
         $innerHTML .= "/>";
         $innerHTML .= "<label for=\"date\">date modified</label>";
         $innerHTML .= "</fieldset>";
         $innerHTML .= "";
         $innerHTML .= "<fieldset>";
         $innerHTML .= "<legend>Sort direction</legend>";
         $innerHTML .= "<input type=\"radio\" name=\"sortDir\" id=\"ascending\" value=\"ascending\" ";
         if($this->sortDir == "ascending") { $innerHTML .= "checked=\"checked\""; }
         $innerHTML .= "/>";
         $innerHTML .= "<label for=\"ascending\">ascending</label><br />";
         $innerHTML .= "<input type=\"radio\" name=\"sortDir\"  id=\"descending\" value=\"descending\"";
         if($this->sortDir == "descending") { $innerHTML .= "checked=\"checked\"" ; }
         $innerHTML .= "/>";
         $innerHTML .= "<label for=\"descending\">descending</label>";
         $innerHTML .= "</fieldset>";
         $innerHTML .= "";
         $innerHTML .= "<fieldset>";
         $innerHTML .= "<legend>Filetypes to display</legend>";
         $innerHTML .= "<input type=\"hidden\" name=\"action\" value=\"prefs\" />";
         if($showImg != "show") $checked = "";  else  $checked = "checked=\"checked\"";
         $innerHTML .= "<input type=\"checkbox\" name=\"showImg\" id=\"showImg\" value=\"show\" $checked />";
         $innerHTML .= "<label for=\"showImg\">Show image type files</label><br />";
         $innerHTML .= "";
         if($showEmbed  != "show") $checked = "";  else  $checked = "checked=\"checked\"";
         $innerHTML .= "<input type=\"checkbox\" name=\"showEmbed\" id=\"showEmbed\" value=\"show\" $checked />";
         $innerHTML .= "<label for=\"showEmbed\"> Show embed type files</label><br />";
         $innerHTML .= "";
         if($showHtml != "show") $checked = "";  else  $checked = "checked=\"checked\"";
         $innerHTML .= "<input type=\"checkbox\" name=\"showHtml\" id=\"showHtml\" value=\"show\" $checked />";
         $innerHTML .= "<label for=\"showHtml\">Show html/text files</label><br />";
         $innerHTML .= "";
         if($showScript != "show") $checked = "";  else  $checked = "checked=\"checked\"";
         $innerHTML .= "<input type=\"checkbox\" name=\"showScript\" id=\"showScript\" value=\"show\" $checked />";
         $innerHTML .= "<label for=\"showScript\">Show script files</label><br />";
         $innerHTML .= "";
         if($showMisc != "show") $checked = "";  else  $checked = "checked=\"checked\"";
         $innerHTML .= "<input type=\"checkbox\" name=\"showMisc\" id=\"showMisc\" value=\"show\" $checked />";
         $innerHTML .= "<label for=\"showMisc\" >Show misc files</label><br />";
         $innerHTML .= "";
         $innerHTML .= "<br />";
         $innerHTML .= "</fieldset>";
         $innerHTML .= "<fieldset>";
         $innerHTML .= "<legend>Skin</legend>";
         $innerHTML .= "<select name=\"skin\" id=\"skin_select\">";
         $hook = opendir("./fileNice/skins/");
         while (false !== ($file = readdir($hook))){
            if($file != "." && $file != ".."){
               if($file == $this->skin){
                  $innerHTML .= "<option value=\"$file\" selected=\"selected\">$file</option>";
               }else{
                  $innerHTML .= "<option value=\"$file\">$file</option>";
               }
            }
         }
         closedir($hook);
         $innerHTML .= "</select><br />";
         $innerHTML .= "</fieldset>";
         $innerHTML .= "<fieldset>";
         $innerHTML .= "<legend>Slideshow speed</legend>";
         $innerHTML .= "<input type=\"text\" maxlength=\"2\" name=\"ssSpeed\" id=\"slideshow_speed\" value=\"" . $this->ssSpeed/1000 . "\" style=\"width:30px;\" /> seconds per image<br />";
         $innerHTML .= "</fieldset>";
         $innerHTML .= "<input type=\"submit\" name=\"Save\" id=\"prefSave\" value=\"Save\" />";
         $innerHTML .= "</form>";
      }


      if(isset($getvars['view'])){
         //if(substr($getvars['view'],0,2) != ".." && substr($getvars['view'],0,1) != "/" && $getvars['view'] != "./" && !stristr($getvars['view'], '../')){
         if(substr($getvars['view'],0,2) != ".." && !stristr($getvars['view'], '../')){
            $out = new FNOutput;
            $out->containerid = $this->containerid;
            $out->silent = 1;
            $out->sortBy = $this->sortBy;
            $out->sortDir = $this->sortDir;
            $out->imgTypes = $this->imgTypes;
            $out->embedTypes = $this->embedTypes;
            $out->htmlTypes = $this->htmlTypes;
            $out->phpTypes = $this->phpTypes;
            $out->miscTypes = $this->miscTypes;
            $out->dateFormat = $this->dateFormat;
            $out->ignoreFiles = $this->ignoreFiles;
            $out->ignoreFolders = $this->ignoreFolders;
            $out->pathToHere = $this->pathToHere;
            $innerHTML .= $out->viewFile($getvars['view']);
         }else{
            // someone is poking around where they shouldn't be
            $innerHTML .= "Nothing.";

            return $innerHTML;
         }
      }else if(isset($getvars['src'])){
         //if(substr($getvars['src'],0,2) != ".." && substr($getvars['src'],0,1) != "/" && $getvars['src'] != "./" && !stristr($getvars['src'], '../')){
         if(substr($getvars['src'],0,2) != ".." && !stristr($getvars['src'], '../')){
            $out = new FNOutput;
            $out->containerid = $this->containerid;
            $out->sortBy = $this->sortBy;
            $out->sortDir = $this->sortDir;
            $out->imgTypes = $this->imgTypes;
            $out->embedTypes = $this->embedTypes;
            $out->htmlTypes = $this->htmlTypes;
            $out->phpTypes = $this->phpTypes;
            $out->miscTypes = $this->miscTypes;
            $out->dateFormat = $this->dateFormat;
            $out->ignoreFiles = $this->ignoreFiles;
            $out->ignoreFolders = $this->ignoreFolders;
            $out->pathToHere = $this->pathToHere;
            $out->silent = 1;
            $innerHTML .= $out->showSource($getvars['src']);
         }else{
            // someone is poking around where they shouldn't be
            $innerHTML .= "Nothing.";
            return $innerHTML;
         }
      }

      $innerHTML .= "<ul id=\"root\"> ";
      // show file list
      $list = new FNFileList;
      $list->containerid = $this->containerid;
      $list->sortBy = $this->sortBy;
      $list->sortDir = $this->sortDir;
      $list->imgTypes = $this->imgTypes;
      $list->embedTypes = $this->embedTypes;
      $list->htmlTypes = $this->htmlTypes;
      $list->phpTypes = $this->phpTypes;
      $list->miscTypes = $this->miscTypes;
      $list->dateFormat = $this->dateFormat;
      $list->ignoreFiles = $this->ignoreFiles;
      $list->ignoreFolders = $this->ignoreFolders;
      $list->pathToHere = $this->pathToHere;
      $list->fileselect = $this->fileselect;
      $list->fieldname = $this->fieldname;
      $list->formname = $this->formname;
      $list->silent = 1;
      $list->setShowTypes();
      if (count($this->restrictTypes) > 0) {
         $list->allowedTypes = $this->restrictTypes;
      }

      if(isset($postvars['sstring'])){
         $str = $postvars['sstring'];
         #$innerHTML .= "Searching for $str <br>";
         $innerHTML .= $list->search($postvars['sstring']);
      }

      if ($this->debug) {
         $innerHTML .= "Path: " . $this->dirPath . "<br>";
      }
      $innerHTML .= $list->getDirList($this->dirPath);

      $innerHTML .= "</ul>";
      $innerHTML .= "</div>";

      if ($this->showFlickrForm) {
         $innerHTML .= "<!-- send to Flickr form -->";
         $innerHTML .= "<form name=\"flickr\" action=\"http://www.flickr.com/tools/sendto.gne\" method=\"get\">";
         $innerHTML .= "<input type=\"hidden\" name=\"url\" />";
         $innerHTML .= "</form>";
      }
      #$innerHTML .= "</body>";
      #$innerHTML .= "<!-- script for applying the javascript events -->";
      #$innerHTML .= "<script type=\"text/javascript\">";
      #$innerHTML .= "function setFNFunctions () { ";
      #$innerHTML .= "var o = new com.filenice.actions;";
      #$innerHTML .= "o.setFunctions();";
      #$innerHTML .= "alert('Executing Javascript');";
      #$innerHTML .= "o.setFunctions(document.getElementById(\"fnContainer\"));";
      #$innerHTML .= "o.setFunctions();";
      #$innerHTML .= "} ";
      #$innerHTML .= "setFNFunctions(); ";
      #$innerHTML .= "</script>";


      // increment the container id
      $this->containerid++;
      return $innerHTML;
   }


   function showFNHeaders() {
      $innerHTML .= "<meta name=\"generator\" content=\"the fantabulous mechanical eviltwin code machine\" />";
      $innerHTML .= "<meta name=\"MSSmartTagsPreventParsing\" content=\"true\" />";

      $innerHTML .= "<link rel=\"shortcut icon\" href=\"favicon.ico\" type=\"image/x-icon\" />";
      $innerHTML .= "<meta http-equiv=\"Content-Style-Type\" content=\"text/css\" />";
      $innerHTML .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$this->skindir/$this->skin/fileNice.css\" />";
      $r = rand(99999,99999999);
      $innerHTML .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$this->skindir/$this->skin/icons.php?r=$r\" />";

      $innerHTML .= "<script language=\"javascript\" type=\"text/javascript\">";
      $innerHTML .= "var ssSpeed = $this->ssSpeed; ";
      $innerHTML .= "var fnscript = '$this->fnscript'; ";
      $innerHTML .= "</script>";
      $innerHTML .= "<script src=\"$this->scriptdir/fileNice.js\" type=\"text/javascript\"></script>";

      return $innerHTML;

   }


}


?>
