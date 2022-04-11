<?php
/*
   File: multiply.common.php

   Example which demonstrates a multiplication using xajax.

   Title: Multiplication Example

   Please see <copyright.inc.php> for a detailed description, copyright
   and license information.
*/

/*
   Section: Files

   - <multiply.php>
   - <multiply.common.php>
   - <multiply.server.php>
*/

/*
   @package xajax
   @version $Id: multiply.common.php 362 2007-05-29 15:32:24Z calltoconstruct $
   @copyright Copyright (c) 2005-2006 by Jared White & J. Max Wilson
   @license http://www.xajaxproject.org/bsd_license.txt BSD License
*/


$xajaxscript = "xajax_modeling.element.php";
include_once ("xajax_config.php");
include_once ("$libpath/xdg/xajaxgrid.inc.php");
# includes the status bar routines
$xajax->registerFunction("showAddElementForm");
$xajax->registerFunction("showAddElementResult");
$xajax->registerFunction("showOperatorEditForm");
$xajax->registerFunction("showOperatorEditResult");
$xajax->registerFunction("showModelRunForm");
$xajax->registerFunction("showModelRunResult");
$xajax->registerFunction("showImportModelElementForm");
$xajax->registerFunction("showImportModelElementResult");
$xajax->registerFunction("showRedrawGraphs");
$xajax->registerFunction("showCopyModelGroupForm");
$xajax->registerFunction("showCopyModelGroupResult");
$xajax->registerFunction("showRefreshWHOObjectsForm");
$xajax->registerFunction("showRefreshWHOObjectsResult");
$xajax->registerFunction("showStatus");
$xajax->registerFunction("showRecentStatus");
$xajax->registerFunction("runModelBackground");
$xajax->registerFunction("openModelRunWorkspace");
$xajax->registerFunction("showModelDesktopView");
$xajax->registerFunction("insertComponent");
$xajax->registerFunction("insertComponentClone");
$xajax->registerFunction("deleteObject");
$xajax->registerFunction("showScenarioEditor");
$xajax->registerFunction("showCopyModelGroupForm2");
$xajax->registerFunction("showRemoteObjectBrowserSelect");
$xajax->registerFunction("saveRemoteObjectBrowserSelect");
$xajax->registerFunction("addRemoteObjectSelect");
$xajax->registerFunction("refreshAnalysisWindow");
$xajax->registerFunction("refreshAquaticBioAnalysisWindow");
$xajax->registerFunction("insertGroupClone");
$xajax->registerFunction("showModelSearchForm");
$xajax->registerFunction("refreshHierarchicalMenu");
$xajax->registerFunction("showModelActivity");
$xajax->registerFunction("showLocationSelector");

?>
