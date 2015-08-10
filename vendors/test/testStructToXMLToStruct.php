<?php

require('../StructToXML.php');
require('../XMLToStruct.php');

$struct =Array (
    "echo" => Array (
            "another" => Array (
                    "0" => "last",
                    "1" => "3",
                    "2" => "7",
                    "another" => Array (
                            "0" => "last",
                            "1" => "3",
                            "2" => "7",
                        ),
                ),
            "something" => "2",
            "some" => "2",
        ),
);


 
$sparser = new StructToXML();
$xml     = $sparser->xml($struct);   

$xparser = new XMLToStruct($xml);
$struct2 = $xparser->getResult();


print $xml;
print_r($struct2);

print_r($sparser->xml($struct2));




$struct3 = array('hi'); 
print_r($struct3);

$xml2 = $sparser->xml($struct3);

$struct4 = $xparser->struct($xml2);
print_r($struct4);


?>
