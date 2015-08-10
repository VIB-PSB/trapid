<?php
  /*
   * This model represents info on the transcripts
   */
class TranscriptsLabels extends AppModel{

  var $name	= 'TranscriptsLabels';
  var $useTable = 'transcripts_labels';

  

  function getDataTranscript2Labels($exp_id){
    $query	= "SELECT `transcript_id`,`label` FROM `transcripts_labels` WHERE `experiment_id`='".$exp_id."' ";
    $res	= $this->query($query);
    $tmp	= array();
    foreach($res as $r){
      $transcript_id	= $r['transcripts_labels']['transcript_id'];
      $label		= $r['transcripts_labels']['label'];
      if(!array_key_exists($transcript_id,$tmp)){$tmp[$transcript_id]=array();}
      $tmp[$transcript_id][]	= $label;      
    }
    return $tmp;
  }



  function enterTranscripts($exp_id,$transcripts,$label){
    //first: get overview of transcripts to make joins redundant in checking validity of transcript
    $query1	= "SELECT `transcript_id` FROM `transcripts` WHERE `experiment_id`='".$exp_id."' ";
    $res1	= $this->query($query1);
    $all_transcripts = array();
    foreach($res1 as $r){
      $tid	= $r['transcripts']['transcript_id'];
      $all_transcripts[$tid] = $tid;
    }

    $counter = 0;
    foreach($transcripts as $transcript_id){
      if(array_key_exists($transcript_id,$all_transcripts)){
	$counter++;
	$statement	= "INSERT INTO `transcripts_labels` (`experiment_id`,`transcript_id`,`label`) VALUES 
				('".$exp_id."','".$transcript_id."','".$label."');";
	$this->query($statement);
      }
    }
    return $counter;
  }



  function getLabels($exp_id){
    $query	= "SELECT `label`,count(`transcript_id`) as count FROM `transcripts_labels` WHERE `experiment_id`='".$exp_id."' GROUP BY `label` ";
    $res	= $this->query($query);
    $result	= array();
    foreach($res as $r){
      $label	= $r['transcripts_labels']['label'];
      $count	= $r[0]['count'];
      $result[$label]	= $count;
    }
    return $result;
  }

}



?>