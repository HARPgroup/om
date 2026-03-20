<?php

require_once 'Math_Expression_Structure.php';

class Math_Expression_Structure_Real extends Math_Expression_Structure {

    public $regex          = '/^([+-])?\s*(\d+)(\.\d+)?(?:[eE]([+-]?\d+))?/';
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
        
        return $this;
    }

    public function __construct($value = null) 
    {
        $this->_expression = $value;
    }

    public function getValue()
    {
        if(!empty($this->match)) {
            if (empty($this->match[3])) {
                if (!empty($this->match[4])) {
                    return (float)($this->match[1].$this->match[2])*pow(10, $this->match[4]);
                } else {
                    return (float)($this->match[1].$this->match[2]);
                }
            } else {
                if (!empty($this->match[4])) {
                    return (float)($this->match[1].$this->match[2].$this->match[3])*pow(10, $this->match[4]);
                } else {
                    return (float)($this->match[1].$this->match[2].$this->match[3]);
                }
            }
        } else {
            return (int)0;
        }

    }

    public function handleOperation($op, $struct2, $reverse = false) 
    {
        
        $obj2 = $struct2->evaluate();
        
        if (!$obj2 instanceof self) {
            return false;
        }

        switch(get_class($op)) {
            
            case 'Math_Expression_Operator_Plus':
                return new Math_Expression_Structure_Real($this->getValue() + $obj2->getValue());
            
            case 'Math_Expression_Operator_Minus':
                
                $result = $this->getValue() - $obj2->getValue();
                
                if ($reverse) {
                    $result = -$result;
                }

                return new Math_Expression_Structure_Real($result);
            
            case 'Math_Expression_Operator_Mult':
                return new Math_Expression_Structure_Real($this->getValue() * $obj2->getValue());

            case 'Math_Expression_Operator_Div':
                return new Math_Expression_Structure_Real($this->getValue() / $obj2->getValue());

            case 'Math_Expression_Operator_Power':
                if ($reverse) {
                    return new Math_Expression_Structure_Real(pow($obj2->getValue(),$this->getValue()));
                } else {
                    return new Math_Expression_Structure_Real(pow($this->getValue(),$obj2->getValue()));                    
                }
        }

        return false;
    }

    public function __toString()
    {
        return (string)$this->getValue();
    }

}

?>
