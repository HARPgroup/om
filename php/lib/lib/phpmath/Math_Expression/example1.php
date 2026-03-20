<pre><?php
    require_once 'Expression.php';
    $t1 = microtime(true);
    
    require_once 'Structures/Math_Expression_Structure_Vector.php';
    Math_Expression::registerStructure(new Math_Expression_Structure_Vector);


   require_once 'lib_equation2.php';
   
   $arData['x'] = 0.0000546;
   $arData['y'] = 2.0;
   $arData['z'] = 3.0;
   
   # 2.87546E+11
   
   $phpresult1 = sin(1.5);
   
   #$equation = "288100161177.34 + 122404039941.07 - 122958536576.41";
   $equation = "sin(1.5)";
   
   #$input = mathProcessor2( $equation, $arData);
   
   $input = $equation;

   echo $input;
   echo "\n" . $phpresult1;

    $expression = new Math_Expression($input);

    if ($result = $expression->evaluate()) {

        echo "\nEvaluation result: ", $result;
        
        $f = "$result";
        
        $f += 0;
        
        echo "\n $f";

    }

    
?>
