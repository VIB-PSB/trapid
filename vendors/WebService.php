<?php

require_once('XMLToStruct.php');

class WebService {

	
    var $error_msg = '';       

    function __construct() {
        $this->error_msg = '';
    }

    function WebService() {
        $this->__construct();
    }

    /**
     * Used to call the Perl Webservice with POST parameters, and return a nested PHP hash structure that contains the answered data
     * Parameters: STRING module, STRING function, STRING parameters
     * Returnvalue: nested Hash/Array structure
     */
    function query ($ws_url,$module, $function, $params) {	                
       $POST_CONTENT = '';
       foreach ($params as $k => &$v) {	
           $POST_CONTENT .= $k . "=" . $v . "&";	
       }
       $POST_CONTENT = rtrim($POST_CONTENT, "&");    

       //depending on the parameters : if the use_application_server variable is set in the params, we send the request to the 
       //application server. If this variable is not set, we send it to the "normal" webservice.       
       $url = "";
       if(isset($params['use_application_server'])){	 
	 $POST_CONTENT = $POST_CONTENT."&module=".str_replace("/","+",$module)."&module_function=".$function;	
	 $url = $ws_url;
       }
       else{
	 $url = $ws_url. "$module/$function"; //standard case : no shaman server
       }
	 
            
       //pr("URL : " .$url);
       //pr("POST : ".$POST_CONTENT);      	                            	   	
        try {
            $curl = curl_init();			
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $POST_CONTENT);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);	
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.2) Gecko/20070223 Firefox/2.0.0.2');

            $response = curl_exec($curl);	   	    
            curl_close ($curl);

            $xml = new XMLToStruct($response);	   	   
            $parsed = $xml->getResult($response);	  	
	    // pr($parsed);
        }
        catch (Exception $e) {
            $this->error_msg = $e->getMessage();
            return false;
        }

        if (is_array($parsed) && array_key_exists('error', $parsed)) {
            $this->error_msg = $parsed['error']['message'];
            return false;
        }

        return $parsed;
    }

    function get_error() {
        return $this->error_msg;
    }

}

?>
