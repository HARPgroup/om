<?php

require_once 'Math_Expression_Operator.php';

class Math_Expression_Operator_Plus extends Math_Expression_Operator
{

    public $precedence    = 2;
    public $regex         = '/^\+/';
    public $value         = '+';
    public $associativity = parent::ASSOC_LEFT;

}

class Math_Expression_Operator_Minus extends Math_Expression_Operator
{

    public $precedence    = 3;
    public $regex         = '/^-/';
    public $value         = '-';
    public $associativity = parent::ASSOC_LEFT;

}

class Math_Expression_Operator_Mult extends Math_Expression_Operator
{

    public $precedence    = 4;
    public $regex         = '/^\*/';
    public $value         = '*';
    public $associativity = parent::ASSOC_LEFT;

}

class Math_Expression_Operator_Div extends Math_Expression_Operator
{

    public $precedence    = 5;
    public $regex         = '!^/!';
    public $value         = '/';
    public $associativity = parent::ASSOC_LEFT;

}

class Math_Expression_Operator_Power extends Math_Expression_Operator
{

    public $precedence    = 6;
    public $regex         = '!^\^!';
    public $value         = '^';
    public $associativity = parent::ASSOC_LEFT;

}

?>
