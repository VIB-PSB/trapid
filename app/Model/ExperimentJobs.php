<?php
  /*
   * This model represents the storage of logging information for each experiment
   * Actions can read and write to this table, providing an action, and the necessary parameters.
   */

class ExperimentJobs extends AppModel{

  var $name	= "ExperimentJobs";
  var $useTable = "experiment_jobs";
	

  function addJob($exp_id,$job_id,$type="long",$comment=""){
    $query	= "INSERT INTO `experiment_jobs`(`experiment_id`,`job_id`,`job_type`,`start_date`,`comment`) VALUES ('".$exp_id."','".$job_id."','".$type."',NOW(),'".$comment."')";
    $this->query($query);
  }

  function getJobs($exp_id){
    $query	= "SELECT * FROM `experiment_jobs` WHERE `experiment_id`='".$exp_id."' ";
    $res	= $this->query($query);
    $result	= array();
    foreach($res as $r){
      $result[] = $r['experiment_jobs'];
    }
    return $result;
  }

  function getNumJobs($exp_id){
    $query	= "SELECT COUNT(*) as count FROM `experiment_jobs` WHERE `experiment_id`='".$exp_id."' ";
    $res 	= $this->query($query);
    $result	= $res[0][0]['count'];
    return $result;
  }

  function deleteJob($exp_id,$job_id){
    $query	= "DELETE FROM `experiment_jobs` WHERE `experiment_id`='".$exp_id."' AND `job_id`='".$job_id."' ";
    $this->query($query);
  }

  function deleteJobReturn($exp_id,$job_id){
    $query	= "DELETE FROM `experiment_jobs` WHERE `experiment_id`='".$exp_id."' AND `job_id`='".$job_id."' ";
    $this->query($query);
    return $this->getJobs($exp_id);
  }



}


?>