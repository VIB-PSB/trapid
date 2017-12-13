<?php
App::uses("Component", "Controller");
class WorkbenchValidationComponent extends Component{
	
     var $controller = true;

	  
     function startup(&$controller){
       $this->controller = & $controller;
     } 	


     // function 

}
?>