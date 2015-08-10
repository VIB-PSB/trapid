<?php
  /*
   * This model represents info on the transcripts
   */
class TranscriptsInterpro extends AppModel{

  var $name	= 'TranscriptsInterpro';
  var $useTable = 'transcripts_interpro';

  
 function findInterproCountsFromTranscripts($exp_id,$transcript_ids){
    $result	= array();
    $transcripts_string	= "('".implode("','",$transcript_ids)."')";
    $query	= "SELECT `interpro`,COUNT(`transcript_id`) as `count` FROM `transcripts_interpro` WHERE `experiment_id`='".$exp_id."' AND `transcript_id` IN ".$transcripts_string." GROUP BY `interpro` ORDER BY `count` DESC";
    $res	= $this->query($query);
    foreach($res as $r){
      $go	= $r['transcripts_interpro']['interpro'];
      $count	= $r[0]['count'];
      $result[$go] = $count;
    }    
    return $result;
  }



  function findTranscriptsFromInterpro($exp_id,$ipr_terms){
    $result	= array();
    $ipr_terms_string	= "('".implode("','",array_keys($ipr_terms))."')";
    $query	= "SELECT `interpro`,count(`transcript_id`) as count FROM `transcripts_interpro` WHERE `experiment_id`='".$exp_id."' AND `interpro` IN ".$ipr_terms_string." GROUP BY `interpro` ";
    $res	= $this->query($query);  
    foreach($res as $r){
      $result[$r['transcripts_interpro']['interpro']] = array("count"=>$r[0]['count'],"desc"=>$ipr_terms[$r['transcripts_interpro']['interpro']]);
    }    
    return $result;
  }



  function getStats($exp_id){
    $query	= "SELECT COUNT(DISTINCT(`interpro`)) as count1, COUNT(DISTINCT(`transcript_id`)) as count2 FROM `transcripts_interpro` WHERE `experiment_id`='".$exp_id."' ";
    $res	= $this->query($query);
    $result	= array("num_interpro"=>0,"num_transcript_interpro"=>0);
    if($res){
      $result["num_interpro"]			= $res[0][0]['count1'];
      $result["num_transcript_interpro"]	= $res[0][0]['count2'];
    }
    return $result;
  }


}


?>