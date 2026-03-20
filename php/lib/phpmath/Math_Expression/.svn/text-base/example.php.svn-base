<pre><?php
    require_once 'Expression.php';
    $t1 = microtime(true);
    
    require_once 'Structures/Math_Expression_Structure_Vector.php';
    Math_Expression::registerStructure(new Math_Expression_Structure_Vector);


    $input = '288100161177.34 + 122404039941.07 - 122958536576.41';

    $expression = new Math_Expression($input);

    if ($result = $expression->evaluate()) {

        echo "\n" . 'Input: ' . $input . 'Evaluation result: ', $result;

    }

    $input = '288100161177.35 + 122404039941.07 - 122958536576.41';

    $expression = new Math_Expression($input);

    if ($result = $expression->evaluate()) {

        echo "\n" . 'Input: ' . $input . 'Evaluation result: ', $result;

    }

    $input = '288100161177.33 + 122404039941.07 - 122958536576.41';

    $expression = new Math_Expression($input);

    if ($result = $expression->evaluate()) {

        echo "\n" . 'Input: ' . $input . 'Evaluation result: ', $result;

    }

    
?>
