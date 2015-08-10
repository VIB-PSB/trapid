<?php

class StructToXML {

    var $result = '';

    function StructToXML($struct = null) {
        $this->__construct($struct);
    }

    function __construct($struct = null) {
        if ($struct) {
            $this->result = $this->xml($struct);
        }
    }

    function xml($struct) {
        $xml = '';
        $this->result = '';

        if (is_array($struct)) {
            foreach ($struct as $key => $value) {
                $xml .= "<$key>";
                $xml .= $this->xml($value);
                $xml .= "</$key>";
            }
        }
        else {
            return $this->escape($struct);
        }
		
        return $xml; 
    }

    function getResult() {
        return $this->result;
    }

    function escape($value) {
        return str_replace(array('<', '>', '&'), array('&lt;', '&gt', '&amp;'), $value);
    }


}

?>
