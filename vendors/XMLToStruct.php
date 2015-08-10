<?php

/**
 *
 * Class to convert a yaml like XML structure to an array structure.
 * This class looks at the openingtag's name to determine the key in
 * the array. The closing tag is not considered, it just closes off.
 *
 * This class also asumesthe following about the XML:
 * - that character data is immediatly closed off by an end tag.
 * - no attributes.
 * - reoccuring tags on the same level are overwritten!
 * - numbers can be used as tags.
 *
 */

class XMLToStruct {

    var $elements = array();
    var $parent   = array();
    var $el       = array();
    var $result;

    function XMLToStruct($xml = null) {
        $this->__construct($xml);
    }

    function __construct($xml = null) {
        if ($xml) {
            $this->result =& $this->struct($xml);
        }
    }            

    function struct($xml) {
        $this->reset();

        $this->startElement('ROOT');
        $this->elements = &$this->el;

        $xml = explode('>', $xml);

        foreach ($xml as $tag) {
            $tag = rtrim(trim($tag));
            if ($tag) {
                if ($tag[0] !== '<') { # character data
                    if (strpos($tag, '<') !== false) {
                        list($data, $end_tag) = explode('<', $tag);
                        $end_tag = substr($end_tag, 1);
                        $this->characterData($data);
                        $this->endElement($end_tag);
                    }
                    else { # holy crap, this is not xml, this is a simple string!
                        $this->characterData($tag);
                    }
                }
                elseif ($tag[1] === '/') { # an end tag
                    $tag = substr($tag, 2);
                    $this->endElement($tag);
                }
                elseif ($tag[0] === '<') { # a start tag
                    $tag = substr($tag, 1);
                    $this->startElement($tag);
                }
                else {
                    throw new Exception("$tag could not be parsed!");
                }
            }
        }

        return $this->elements;  
    }

    function &getResult() {
        return $this->result;
    }

    function reset() {
        $this->elements = array();
        $this->parent   = array();
        $this->el       = array(); 
        $this->result   = '';
    }

    function startElement($name) {
        $cur_el = array();

        $this->el[ $name ] = &$cur_el;
        $this->parent[] = &$cur_el;

        $this->el = &$cur_el;
    }

    function endElement($name) {
        $this->el = &$this->parent[count($this->parent) - 2]; # it's -2 because we already add an el as parent before we knew if he is a parent
        array_pop($this->parent);
    }

    function characterData($data) {
        $data = str_replace(array('&gt;', '&lt;', '&amp;'), array('>', '<', '&'), $data);
        $this->el = $data; # replace the reference with something more substantial
    }

}
