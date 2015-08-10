<?php
  /*
   * This model represents info on the transcripts
   */
class TranscriptsGo extends AppModel{

  var $name	= 'TranscriptsGo';
  var $useTable = 'transcripts_go';

  

  function getExportData($exp_id){
    $result 	= array();
    $query	= "SELECT COUNT(`transcript_id`),`go`,`is_hidden` FROM `transcripts_go` WHERE `experiment_id`='".$exp_id."' ";
    $res	= $this->query($query);
    pr($res);
    foreach($res as $r){
      $result[]	= array($r['transcripts_go']['transcript_id'],$r['transcripts_go']['go'],$r['transcripts_go']['is_hidden']);
    }
    return $result;
  }





  function findTranscriptCountsFromGos($exp_id,$go_ids){
    $result	= array();
    $go_string	= "('".implode("','",$go_ids)."')";
    $query	= "SELECT `go`,COUNT(`transcript_id`) as `count` FROM `transcripts_go` WHERE `experiment_id`='".$exp_id."' 
			AND `go` IN ".$go_string." GROUP BY `go` ORDER BY `count` DESC";  
    $res	= $this->query($query);
    foreach($res as $r){
      $go	= $r['transcripts_go']['go'];
      $count	= $r[0]['count'];
      $result[$go] = $count;
    }    
    return $result;	
  }

  function findGoCountsFromTranscripts($exp_id,$transcript_ids){
    $result	= array();
    $transcripts_string	= "('".implode("','",$transcript_ids)."')";
    $query	= "SELECT `go`,COUNT(`transcript_id`) as `count` FROM `transcripts_go` WHERE `experiment_id`='".$exp_id."' AND `transcript_id` IN ".$transcripts_string." GROUP BY `go` ORDER BY `count` DESC";
    $res	= $this->query($query);
    foreach($res as $r){
      $go	= $r['transcripts_go']['go'];
      $count	= $r[0]['count'];
      $result[$go] = $count;
    }    
    return $result;
  }



  function findTranscriptCounts($exp_id,$go_ids,$transcript_ids){
    $result		= array();
    $query		= "SELECT `go`,count(`transcript_id`) as count FROM `transcripts_go` WHERE `experiment_id`='".$exp_id."' ";
    if($go_ids!=null){
	$gos_string	= "('".implode("','",$go_ids)."')";
	$query		= $query." AND `go` IN ".$gos_string." ";
    }
    if($transcript_ids!=null){
	$transcripts_string	= "('".implode("','",$transcript_ids)."')";
	$query		= $query." AND `transcript_id` IN ".$transcripts_string." ";
    }
    $query	= $query." GROUP BY `go` ORDER BY `count` DESC";
    $res	= $this->query($query);
    foreach($res as $r){
      $go		= $r['transcripts_go']['go'];
      $count		= $r[0]['count'];
      $result[$go]	= $count;
    }
    return $result;
  }




  function findTranscriptsFromGo($exp_id,$go_terms){
    $result	= array();
    $go_terms_string	= "('".implode("','",array_keys($go_terms))."')";
    $query	= "SELECT `go`,count(`transcript_id`) as count FROM `transcripts_go` WHERE `experiment_id`='".$exp_id."' AND `go` IN ".$go_terms_string." GROUP BY `go` ";
    $res	= $this->query($query);
    foreach($res as $r){
      $result[$r['transcripts_go']['go']] = array("count"=>$r[0]['count'],"desc"=>$go_terms[$r['transcripts_go']['go']]);
    }
    return $result;
  }


  function getStats($exp_id){
    $query	= "SELECT COUNT(DISTINCT(`go`)) as count1, COUNT(DISTINCT(`transcript_id`)) as count2 FROM `transcripts_go` WHERE `experiment_id`='".$exp_id."' ";
    $res	= $this->query($query);
    $result	= array("num_go"=>0,"num_transcript_go"=>0);
    if($res){
      $result["num_go"]			= $res[0][0]['count1'];
      $result["num_transcript_go"]	= $res[0][0]['count2'];
    }
    return $result;
  }



}


?>