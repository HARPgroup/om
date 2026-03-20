<?php

require_once 'Math_Expression_Structure.php';

class Math_Expression_Structure_Vector extends Math_Expression_Structure {

    public $regex          = '/^(\{((?:[^{}]+|(?1))+)\})/';
    public $match          = array();

    protected $_expression = null;

    public $pieces = array();

    protected $_separator = ',';
    
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
        
        // have to parse that expression

        $deepness = 0;
        $key      = 0;

        $parts = array('');

        for($i = 0; $i < strlen($this->match[2]); $i++) {
            
            $char = $this->match[2][$i];
            
            if ($char == '{') {
                $deepness++;
            } elseif ($char == '}') {
                $deepness--;
            } elseif ($char == $this->_separator && !$deepness) {
                $key++;
                array_push($parts, '');
                continue;
            }

            $parts[$key] .= $char;
            
            
        }
        
        $this->pieces = array();

        foreach ($parts as $key => $piece) {
            $expr = new Math_Expression($piece);
            
            $this->pieces[] = $expr->evaluate();
        }

        

        return $this;
    }

    public function __construct($pieces = '') 
    {
        if (is_string($pieces)) {
            $this->_expression = $pieces;
        } else if(is_array($pieces)) {
            $this->pieces = $pieces;
            $this->_evaluated = true;
        }
    }
    
    public function handleOperation($op, $struct2, $reverse = false) 
    {
        $obj2 = $struct2->evaluate();
        
        $this->evaluate();

        $new_pieces = array();


        if ($obj2 instanceof Math_Expression_Structure_Real) {
            
            
            switch(get_class($op)) {
            
                case 'Math_Expression_Operator_Mult':
                case 'Math_Expression_Operator_Div':   
                    
                
                    foreach($this->pieces as $key => $piece) {
                        $new_pieces[$key] = Math_Expression::handleOperation($piece, $op, $obj2);
                    }

                    $new_vector = new self($new_pieces);

                    return $new_vector->evaluate();

            }
        }

        return false;
            
    }

    public function __toString()
    {

        $first  = true;
        $return = '';


        foreach ($this->pieces as $piece) {
            if ($first) {
                $first = false;
                $return .= $piece->__toString();
            } else {
                $return .= $this->_separator.' '.$piece->evaluate()->__toString();    
            }
        }

        return '{'.$return.'}';
    }

}

?>
