<?php

require('StructToXML.php');

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
 
$parser = new StructToXML($struct);
print_r($parser->getResult());

?>
