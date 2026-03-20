<?php
   require_once ('common.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>XajaxGrid</title>
        <link rel="stylesheet" href="css/style.css" type="text/css" />
        <?php $xajax->printJavascript("/devlib/xajax") ?>
</head>
<body>
        <br>
        <center>
        <table width="90%" border="0">
                <tr>
                        <td>
                                <div id="formDiv" class="formDiv"></div>
                                <div id="msgZone">&nbsp;</div>
                                <div id="grid" align="center"> </div>
                                <script type="text/javascript">
                                xajax_showGrid(0,<?php echo ROWSXPAGE; ?>);
                                </script>

                        </td>
                </tr>
        </table>
        </center>
</body>
</html>
