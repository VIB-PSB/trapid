<?php
  /*
   * This model represents the necessary functionality to authenticate users,
   * and to regulate their access rights.
   */
class Experiments extends AppModel{

  var $name	= 'Experiments';
  var $useTable = 'experiments';
  var $primaryKey = "experiment_id";



  function getSharedExperiments($exp_ids){
    $result	= array();
    if(count($exp_ids)==0){return $result;}
    $exp_id_string = "('".implode("','",$exp_ids)."')";
    $query1	= "SELECT Experiments.*, DataSources.`name`,DataSources.`URL` FROM `data_sources` DataSources, `experiments` Experiments
			 WHERE Experiments.experiment_id IN ".$exp_id_string." AND DataSources.`db_name`=Experiments.`used_plaza_database`
			 ORDER BY Experiments.`last_edit_date` DESC";
    $res1	= $this->query($query1);
    $result	= $res1;
    return $result;
  }

  function getUserExperiments($user_id){
    //perform 2 different queries, in order to prevent major joins
    $query1	= "SELECT Experiments.*, DataSources.`name`,DataSources.`URL` FROM `data_sources` DataSources, `experiments` Experiments
			 WHERE Experiments.user_id='".$user_id."' AND DataSources.`db_name`=Experiments.`used_plaza_database`
			 ORDER BY Experiments.`last_edit_date` DESC";
    $res1	= $this->query($query1);
    $res	= array();
    foreach($res1 as $r){
      $exp_id	= $r['Experiments']['experiment_id'];
      #$query2	= "SELECT COUNT(`transcript_id`) as count FROM `transcripts` WHERE `experiment_id`='".$exp_id."' ";
      #$res2	= $this->query($query2);
      #$r[0]	= $res2[0][0];
      $r['count']= "computing";


      $query3 	= "SELECT * FROM `experiment_jobs` WHERE `experiment_id`='".$exp_id."'";
      $res3	= $this->query($query3);
      $rese3	= array();
      foreach($res3 as $r3){
	$rese3[] = $r3['experiment_jobs'];
      }
      $r['experiment_jobs'] = $rese3;
      $res[] 	= $r;
    }
    return $res;
  }


  function getDefaultInformation($exp_id){
    $exp_id	= mysql_real_escape_string($exp_id);
    // Modified query to comply with MySQL 5.7 / psbsql01
    // $query	= "SELECT a.*,COUNT(b.`transcript_id`) as transcript_count,d.`name`,d.`URL`,d.`plaza_linkout`, d.`gf_prefix` FROM `experiments` a LEFT JOIN `transcripts` b ON a.`experiment_id`=b.`experiment_id` JOIN `data_sources` d ON a.`used_plaza_database`=d.`db_name` WHERE a.`experiment_id`='".$exp_id."' ";
    $query	= "SELECT exp.*, COUNT(tr.`transcript_id`) AS transcript_count, ds.`name`, ds.`URL`, ds.`plaza_linkout`, ds.`gf_prefix` FROM `experiments` exp LEFT JOIN `transcripts` tr ON exp.`experiment_id`=tr.`experiment_id` JOIN `data_sources` ds ON exp.`used_plaza_database`=ds.`db_name` WHERE exp.`experiment_id`='".$exp_id."' GROUP BY ds.`name`, ds.`URL`, ds.`plaza_linkout`, ds.`gf_prefix`";
    $res = $this->query($query);
    $result = array();
    if($res){
    	$result	= $res[0]['exp'];
    	$result["transcript_count"] = $res[0][0]['transcript_count'];
	$result["datasource"] = $res[0]['ds']['name'];
	$result["datasource_URL"] = $res[0]['ds']['URL'];
	$result["allow_linkout"]  = $res[0]['ds']['plaza_linkout'];
	$result["gf_prefix"]	  = $res[0]['ds']['gf_prefix'];
    }
    return $result;
  }


}


?>
