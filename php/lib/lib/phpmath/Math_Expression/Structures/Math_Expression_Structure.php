<?php

abstract class Math_Expression_Structure {

    public $regex;
    public $match;

    protected $_expression;

    abstract public function evaluate();

    abstract public function handleOperation($op, $value, $reverse = null);

    abstract public function __toString();

}


?>
