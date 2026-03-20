<?php

abstract class Math_Expression_Operator
{

    const ASSOC_NONE  = 0;
    const ASSOC_RIGHT = 1;
    const ASSOC_LEFT  = 2;

    public $precedence    = 0;
    public $regex         = '/^/';
    public $value         = '';
    public $associativity = self::ASSOC_NONE;

    public function __toString()
    {
        return $this->value;
    }

}

?>
