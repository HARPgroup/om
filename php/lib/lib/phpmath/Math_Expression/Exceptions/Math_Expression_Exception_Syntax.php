<?php
    
    class Math_Expression_Exception_Syntax extends Exception 
    {

        protected $message = '';

        public function __construct($near) {
        
            if (strlen($near) > 30) {
                $near = substr($near, 0, 27).'...';
            }
        
            $this->message = 'Syntax error near :"'.$near."\"\n";

        }


    }

?>
