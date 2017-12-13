<?php

  /*
   * This model represents the storage of logging information for each experiment
   * Actions can read and write to this table, providing an action, and the necessary parameters.
   */

class CleanupDate extends AppModel{

  var $name	= "CleanupDate";
  var $useTable = "cleanup_date";
	


  function checkDateStatus($year,$month){	   
    $query	= "SELECT `id` FROM `cleanup_date` WHERE `year`='".$year."' AND `month`='".$month."' ";
    $res	= $this->query($query);     
    if($res){
      return $res[0]['cleanup_date']['id'];
    }
    else{
       $insert_query	= "INSERT INTO `cleanup_date` (`year`,`month`) VALUES ('".$year."','".$month."') ";
       $this->query($insert_query);
       return -1;
    }      
      
  }


}


?>