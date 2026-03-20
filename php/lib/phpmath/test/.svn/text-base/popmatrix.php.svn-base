<?php

require_once "../Matrix.php";


# vars = array( pasture, rowcrops, hay, urban);
# coefficients from regression equations
/*
$dairy = array(-0.0127, 0.0826, 0.3014, 0.0215);
$swine = array(0.0447, 0.44268, 0.04118, -0.00494);
$broiler = array(24.56, 63.823, -50.243, -7.238);
$beef = array(0.1797, 0.005158, -0.07055, 0.00291);
*/
# vars = array( pasture, rowcrops, hay);
$dairy = array(-0.0127, 0.0826, 0.3014);
$broiler = array(24.56, 63.823, -50.243);
$beef = array(0.1797, 0.005158, -0.07055);

# pops
/*
$dairypop = 8122;
$swinepop = 1;
$broilerpop = 18271724;
$beefpop = 2630;
*/
$dairypop = array(180569 + 4401);
$swinepop = array(552351 + 6048);
$broilerpop = array(10769195 + 341077);
$beefpop = array(27935 - 526);

/*
$avals = array($dairy,$swine,$broiler,$beef);
$bvals = array($dairypop, $swinepop, $broilerpop, $beefpop);
*/
$avals = array($dairy,$broiler,$beef);
$bvals = array($dairypop, $broilerpop, $beefpop);

$a = new Matrix($avals);
$b = new Matrix($bvals);

$ai = $a->inverse();

$x = $ai->times($b);

$i = $a->times($ai);

print_r($a);
print("<br>Inverse<br>");
print_r($ai);
print("<br>b<br>");
print_r($b);
print("<br>Identity<br>");
print_r($i);
print("<br>Solution<br>");
print_r($x);

?>