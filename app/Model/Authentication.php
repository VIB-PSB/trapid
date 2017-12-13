<?php
  /**
   * This model represents the necessary functionality to authenticate users,
   * and to regulate their access rights.
   */

class Authentication extends AppModel{
  var $name	= 'Authentication';
  var $useTable	= 'authentication';
  var $validate = array("email"=>array("rule"=>array("email")));





}


?>
