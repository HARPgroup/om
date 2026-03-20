<?php

require_once 'Math_Expression_Structure.php';

class Math_Expression_Structure_Parenthesis extends Math_Expression_Structure {

    public $regex          = '/^(([-+]?)\(((?:[^()]+|(?1))+)\))/';
    public $match          = array();

    protected $_expression = null;

    protected $_evaluated = false;
    
    public function evaluate() 
    {

        if ($this->_evaluated) {
            return $this;
        } else {
            $this->_evaluated = true;
        }
    
        if (empty($this->match)) {
            preg_match($this->regex, $this->_expression, $this->match);
        }

        if ($this->match[2] == '-') {
            $expression = new Math_Expression('0-('.$this->match[3].')');
        } else {
            $expression = new Math_Expression($this->match[3]);
        }

        return $expression->evaluate();
    }

    public function __construct($value = null) 
    {
        $this->_expression = $value;
    }
    
    public function handleOperation($op, $struct2, $reverse = false) 
    {
        $obj1 = clone $this;

        $result = $obj1->evaluate();
        
        if ($reverse) {
            $obj1 = $struct2;
            $obj2 = $result;
        } else {
            $obj1 = $result;
            $obj2 = $struct2;
        }
    
        return Math_Expression::handleOperation($obj1, $op, $obj2);
    }

    public function __toString()
    {
        return (string)($this->evaluate());
    }

}

?>
