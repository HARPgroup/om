<?php
    
    class Math_Expression_Exception_Fatal extends Exception 
    {

        protected $message = '';

        public function __construct($e) {
        
            if ($e instanceof Exception) {
                $message = $e->getMessage();
            } else if (is_string($e)) {
                $message = $e;
            }
        
            $this->message = '<strong>Math_Expression::Fatal Error</strong> : '.$message.'';

        }


    }

?>
