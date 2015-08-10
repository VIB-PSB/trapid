<?php

require('WebService.php');


$ws = new WebService();

$response = $ws->ws_query('PLAZA/echo', 'echo', array('i hear' => 'I HEAR'));


print_r($response);


?>
