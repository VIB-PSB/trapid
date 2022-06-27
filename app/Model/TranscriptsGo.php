<?php
  /*
  * This model represents protein motifs (i.e. InterPro domains) information associated to the transcripts
   */

class TranscriptsGo extends AppModel {

  var $name	= 'TranscriptsGo';
  var $useTable = 'transcripts_annotation';



  function getExportData($exp_id){
    $result 	= array();
    $query	= "SELECT COUNT(`transcript_id`),`name`,`is_hidden` FROM `transcripts_annotation` WHERE `experiment_id`='".$exp_id."' AND `type`='go'";
    $res	= $this->query($query);
    pr($res);
    foreach($res as $r){
      $result[]	= array($r['transcripts_annotation']['transcript_id'], $r['transcripts_annotation']['name'], $r['transcripts_annotation']['is_hidden']);
    }
    return $result;
  }





  function findTranscriptCountsFromGos($exp_id,$go_ids){
    $result	= array();
    $go_string	= "('".implode("','",$go_ids)."')";
    $query	= "SELECT `name`,COUNT(`transcript_id`) as `count` FROM `transcripts_annotation` WHERE `experiment_id`='".$exp_id."'
			 AND `type`='go' AND `name` IN ".$go_string." GROUP BY `name` ORDER BY `count` DESC";
    $res	= $this->query($query);
    foreach($res as $r){
      $go	= $r['transcripts_annotation']['name'];
      $count	= $r[0]['count'];
      $result[$go] = $count;
    }
    return $result;
  }

  function findGoCountsFromTranscripts($exp_id,$transcript_ids){
    $result	= array();
    $transcripts_string	= "('".implode("','",$transcript_ids)."')";
    $query	= "SELECT `name`,COUNT(`transcript_id`) as `count` FROM `transcripts_annotation` WHERE `experiment_id`='".$exp_id."' AND `transcript_id` IN ".$transcripts_string." AND `type`='go' GROUP BY `name` ORDER BY `count` DESC";
    $res	= $this->query($query);
    foreach($res as $r){
      $go	= $r['transcripts_annotation']['name'];
      $count	= $r[0]['count'];
      $result[$go] = $count;
    }
    return $result;
  }



  function findTranscriptCounts($exp_id,$go_ids,$transcript_ids){
    $result		= array();
    $query		= "SELECT `name`,count(`transcript_id`) as count FROM `transcripts_annotation` WHERE `experiment_id`='".$exp_id."' AND `type`='go'";
    if($go_ids!=null){
	$gos_string	= "('".implode("','",$go_ids)."')";
	$query		= $query." AND `name` IN ".$gos_string." ";
    }
    if($transcript_ids!=null){
	$transcripts_string	= "('".implode("','",$transcript_ids)."')";
	$query		= $query." AND `transcript_id` IN ".$transcripts_string." ";
    }
    $query	= $query." GROUP BY `name` ORDER BY `count` DESC";
    $res	= $this->query($query);
    foreach($res as $r){
      $go		= $r['transcripts_annotation']['name'];
      $count		= $r[0]['count'];
      $result[$go]	= $count;
    }
    return $result;
  }




  function findTranscriptsFromGo($exp_id,$go_terms){
    $result	= array();
    $go_terms_string	= "('".implode("','",array_keys($go_terms))."')";
    $query	= "SELECT `name`, count(`transcript_id`) as count FROM `transcripts_annotation` WHERE `experiment_id`='".$exp_id."' AND `type`='go' AND `name` IN ".$go_terms_string." GROUP BY `name` ";
    $res	= $this->query($query);
    foreach($res as $r){
      $result[$r['transcripts_annotation']['name']] = array("count"=>$r[0]['count'],"desc"=>$go_terms[$r['transcripts_annotation']['name']]['desc'], "info"=>$go_terms[$r['transcripts_annotation']['name']]['info']);
    }
    return $result;
  }


  function getStats($exp_id){
    $query	= "SELECT COUNT(DISTINCT(`name`)) as count1, COUNT(DISTINCT(`transcript_id`)) as count2 FROM `transcripts_annotation` WHERE `experiment_id`='".$exp_id."' AND `type`='go' ";
    $res	= $this->query($query);
    $result	= array("num_go"=>0,"num_transcript_go"=>0);
//    debug($result);
    if($res){
      $result["num_go"]			= $res[0][0]['count1'];
      $result["num_transcript_go"]	= $res[0][0]['count2'];
    }
    return $result;
  }

}

