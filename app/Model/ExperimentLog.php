<?php

  /*
   * This model represents the storage of logging information for each experiment
   * Actions can read and write to this table, providing an action, and the necessary parameters.
   */

class ExperimentLog extends AppModel{

  var $name	= "ExperimentLog";
  var $useTable = "experiment_log";
	


  function addAction($exp_id,$action,$param,$depth=0){    
    $query	= "INSERT INTO `experiment_log`(`experiment_id`,`date`,`action`,`parameters`,`depth`) VALUES ('".$exp_id."',NOW(),'".$action."','".$param."','".$depth."')";    
    $this->query($query);

  }


}


?>