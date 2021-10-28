<?php
require_once 'Structures/Math_Expression_Structure_Real.php';
require_once 'Structures/Math_Expression_Structure_Parenthesis.php';
require_once 'Operators/Math_Expression_Operator_Basics.php';
require_once 'Exceptions/Math_Expression_Exception_Syntax.php';
require_once 'Exceptions/Math_Expression_Exception_UnhandledOperator.php';
require_once 'Exceptions/Math_Expression_Exception_Fatal.php';

class Math_Expression 
{
    protected static $_registeredOperators  = array();
    protected static $_registeredStructures = array();
    
    protected $_pieces = array();
    protected $_expression = '';

    protected $_status = self::S_VALID;

    const S_VALID = 0;
    const S_ERROR = 1;

    const P_STRUCTURE = 1;
    const P_OPERATOR  = 2;

    public function __construct($expression)
    {
        
        try {
        
            self::registerOperator (new Math_Expression_Operator_Plus);
            self::registerOperator (new Math_Expression_Operator_Minus);
            self::registerOperator (new Math_Expression_Operator_Mult);
            self::registerOperator (new Math_Expression_Operator_Div);
            self::registerOperator (new Math_Expression_Operator_Power);
            
            self::registerStructure(new Math_Expression_Structure_Real);
            
            self::registerStructure(new Math_Expression_Structure_Parenthesis);
        
            $this->_expression = $this->_clean($expression);

            $this->_parse();
            
        } catch (Math_Expression_Exception_Fatal $e) {
            $this->_status = self::S_ERROR;
            error_log( $e->getMessage());
        }

    }

    protected function _parse()
    {
        $status = self::P_STRUCTURE;

        try {

            $tempExpression = $this->_expression;
        
            while (strlen($tempExpression) > 0) {

                $matches = 0;
                
                if (($status & self::P_OPERATOR) == self::P_OPERATOR) {
                    foreach (self::$_registeredOperators as $operator) {
                        
                        if (preg_match($operator->regex, $tempExpression, $match)) {

                            $tempExpression  = substr($tempExpression, strlen($match[0]));
                            $this->_pieces[] = clone $operator;
                            
                            $status = self::P_STRUCTURE;
                            
                            continue 2;
                        }
                    }
                }

                if (($status & self::P_STRUCTURE) == self::P_STRUCTURE) {
                
                    foreach (self::$_registeredStructures as $structure) {
                        
                        if (preg_match($structure->regex, $tempExpression, $match)) {

                            $tempExpression  = substr($tempExpression, strlen($match[0]));
                            $temp            = clone $structure;
                            $temp->match     = $match;
                            $this->_pieces[] = $temp;

                            $status = self::P_OPERATOR;
                            
                            continue 2;
                        }
                    }
                }

                throw new Math_Expression_Exception_Syntax($tempExpression);
                
            }

        } catch (Math_Expression_Exception_Syntax $e) {
            throw new Math_Expression_Exception_Fatal($e);
        }
        
    }

    public function evaluate()
    {
    
        try {
        
            if ($this->_status != self::S_VALID) {
                throw new Math_Expression_Exception_Fatal('Unable to recover from an erroneous Math_Expression: ');
                //throw new Math_Expression_Exception_Fatal('Unable to recover from an erroneous Math_Expression: ' . $this->_expression);
                return FALSE;
            }
        
            $rpn = $this->_toRPN();

            $stack = array();

            foreach ($rpn as $object) {
                if ($object instanceof Math_Expression_Structure) {
                
                    $stack[] = $object;
                    
                } else if ($object instanceof Math_Expression_Operator) {
                
                    $n1 = array_pop($stack);

                    if (empty($stack)) {
                        $n2 = new Math_Expression_Structure_Real(0);
                    } else {
                        $n2 = array_pop($stack);
                    }
                    
                    $stack[] = self::handleOperation($n2, $object, $n1);

                    
                }
            }
            
            $var = array_pop($stack);
            
            return $var->evaluate(); 
            
        } catch (Math_Expression_Exception_Fatal $e) {
            $this->_status = self::S_ERROR;
            error_log($e->getMessage());
            return false;
        }
    }
    
    static public function handleOperation(Math_Expression_Structure $struct1, Math_Expression_Operator $operator, Math_Expression_Structure $struct2) 
    {
        try {

            if (($return = $struct1->handleOperation($operator, $struct2, false)) !== false) {

                return $return->evaluate();
                
            } else if (($return = $struct2->handleOperation($operator, $struct1, true)) !== false) {

                return $return->evaluate();
                
            } else {
                throw new Math_Expression_Exception_UnhandledOperator($struct1, $operator, $struct2);
            }
            
        } catch (Math_Expression_Exception_UnhandledOperator $e) {
            throw new Math_Expression_Exception_Fatal($e);
        }

    }    
    
    protected function _toRPN()
    {
        $outputStack   = array();
        $operatorStack = array();
    
        foreach($this->_pieces as $name => $object) {

            if ($object instanceof Math_Expression_Structure) {
            
                $outputStack[] = $object;
                
            } else if ($object instanceof Math_Expression_Operator) {
            
                $continue = true;

                while(!empty($operatorStack) && $continue) {

                    $topOperator = array_pop($operatorStack);

                    if (($object->associativity == Math_Expression_Operator::ASSOC_LEFT  && $object->precedence <= $topOperator->precedence) ||
                        ($object->associativity == Math_Expression_Operator::ASSOC_RIGHT && $object->precedence <  $topOperator->precedence)) {
                        $outputStack[] = $topOperator;
                    } else {
                        $operatorStack[] = $topOperator;
                        $continue = false;
                    }

                }


                $operatorStack[] = $object;
            }
        }

        return array_merge($outputStack, array_reverse($operatorStack));
        
    }

    
    public static function registerOperator(Math_Expression_Operator $operator) 
    {
        if (!in_array($operator, self::$_registeredOperators)) {
        
            array_unshift(self::$_registeredOperators, $operator);
            
            return true;
            
        } else {
            return false;
        }
    }

    public static function registerStructure(Math_Expression_Structure $structure) 
    {
        if (!in_array($structure, self::$_registeredStructures)) {
        
            array_unshift(self::$_registeredStructures, $structure);
            
            return true;
            
        } else {
            return false;
        }
    }

    protected function _clean($expression)
    {
        return preg_replace('/\s+|--/', '', $expression);
    }

    public function __toString()
    {
        $return = '';

        foreach ($this->_pieces as $piece) {
            $return .= $piece->__toString();
        }

        return $return;
    }

}
?>
