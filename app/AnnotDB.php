<?php

/**
 *
 * A object-relational mapper for the perl webservice.
 *
 * It is necessary to explicitly load this class, which is done in bootstrap.php.
 *
 * The idea is the automagically detect to which module and function you wish 
 * to connect through the perl webservice.
 *
 * The classes who inherit from this class should redefine the $module variable 
 * as this will be the module which is called in the perl WS.
 * 
 * Throught the callback method __call we catch all function calls that are not 
 * defined in this class. The name of the function is the same as the name of 
 * the subroutine in the module in the perl WS.
 *
 * If an error occures, it will be accessible through the variable $error.
 *
 * @usage
 *
 * First define your model:
 *
 * class AnnotDB extends WsModel {
 *
 *  var $module = 'AnnotDB';
 * 
 * }
 *
 * Then you can call it from within your cake code, after you've loaded AnnotDB 
 * as a model through var $uses = array('AnnotDB'):
 *
 * $response = $this->AnnotDB->generalQ($genome, $release, $locus_id, array('extra param' => 
 * 'extra param value')); 
 * 
 */

class WsModel {

    var $module = 'Echo';
    var $useTable = false;
    var $ws;
    var $error;

    function __construct() {
        vendor('WebService');	    				
        $this->ws = new WebService();  
    }

    function ws_model() {
        $this->__construct();
    }

    /**
     * Translates all unknown function calls to calls to the WS.
     * You don't have to call this funtion directly, just call any other
     * function.
     *
     * @param $function String The name of the function.
     * @param $args array The parameters of the function call.
     *
     */
    function __call($function, $args) {
        $array_to_send 	= array();	
	$webservice	= WEBSERVICE;

	$data		= null;
	
	if(count($args)==1){
	  $data 	= $args[0];
	}
	else if(count($args==2)){
	  $webservice 	= $args[0];
	  $data		= $args[1];	
	}
	else{
	  return null;
	}
	     
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $array_to_send[ $key ] = $value;
            }
        }
        else {
            $array_to_send[] = $data;
            
        }
	//WE NEED TO USE THE APPLICATION SERVER IF THIS VARIABLE IS SET. THIS MEANS CHANGING THE WEBSERVICE URL
	if(isset($array_to_send["use_application_server"]) && $array_to_send["use_application_server"]==1){
	     $webservice = WEBSERVICE_APPLICATION_SERVER;
	}

        if (!$result = $this->ws->query($webservice,'PLAZA/' . $this->module, $function, $array_to_send)) {
            $this->error = $this->ws->get_error();
            echo "Error : ".$this->error;
        }
        return $result;
    }

}
