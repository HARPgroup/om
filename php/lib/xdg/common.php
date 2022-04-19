<?php

        define('ROWSXPAGE', 3); // Number of rows show it per page.
        define('MAXROWSXPAGE', 25);  // Total number of rows show it when click on "Show All" button.

   require_once ("/var/www/html/devlib/xajax/xajax_core/xajax.inc.php");

   $xajax = new xajax("server.php");
   //$xajax->debugOn();
   $xajax->registerFunction("showGrid");
   $xajax->registerFunction("add");
   $xajax->registerFunction("edit");
   $xajax->registerFunction("show");
   $xajax->registerFunction("delete");
   $xajax->registerFunction("save");
   $xajax->registerFunction("update");

?>
