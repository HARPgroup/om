<?php
    
    class Math_Expression_Exception_UnhandledOperator extends Exception 
    {

        protected $message = '';

        public function __construct($struct1, $operator, $struct2) {
        
            $this->message = 'Unable to handle the operator '.get_class($operator).' between '.get_class($struct1).' and '.get_class($struct2).".\n";

        }


    }

?>
