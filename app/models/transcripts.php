<?php
  /*
   * This model represents info on the transcripts
   */
class Transcripts extends AppModel{

  var $name	= 'Transcripts';
  var $useTable = 'transcripts';



  function getBasicInformationTranscripts($exp_id,$transcript_ids){   
    $result		= array();
    $transcripts_string	= "('".implode("','",$transcript_ids)."')";
    $query		= "SELECT `transcript_id`,`gf_id`,`meta_annotation` FROM `transcripts` WHERE `experiment_id`='".$exp_id."' AND `transcript_id` IN ".$transcripts_string." ";
    $res		= $this->query($query);
    foreach($res as $r){
      $result[$r['transcripts']['transcript_id']] = array("transcript_id"=>$r['transcripts']['transcript_id'],
							  "gf_id"=>$r['transcripts']['gf_id'],
							  "meta_annotation"=>$r['transcripts']['meta_annotation']);	
    }
    return $result;
  }


  function getRandomTranscriptsFrameDP($exp_id,$bad_gene_family,$count){   
    $result		= array();
    if($count<=0){return $result;}
    $max_iterations	= 100;	//prevent infinite loops
    $query		= "SELECT `transcript_id`,`transcript_sequence` FROM `transcripts` WHERE `experiment_id`='".$exp_id."' AND `gf_id`!='".$bad_gene_family."' AND `putative_frameshift`='0' ORDER BY RAND() LIMIT ".$max_iterations; 
    $res		= $this->query($query);    	       
    for($i=0;$i< count($res) && count($result)<$count;$i++){
      $r		= $res[$i]['transcripts'];
      $transcript_id	= $r['transcript_id'];
      $transcript_sequence	= $r['transcript_sequence'];
      $result[$transcript_id] = $transcript_sequence;
    }  
    return $result;
  }



  //implemented because the "find" method uses too much memory to be effective
  function getColumnInfo($exp_id,$columns){
    $query	= "SELECT `transcript_id`";
    foreach($columns as $column){$query = $query.",`".$column."`";}
    $query	= $query." FROM `transcripts` WHERE `experiment_id`='".$exp_id."'";
    $res	= $this->query($query);   
    $result	= array();
    foreach($res as $r){
      $result[] = $r['transcripts'];
    }
    return $result;
  }


  function findExperimentInformation($exp_id){
    $exp_id	= mysql_real_escape_string($exp_id);
    $query	= "SELECT COUNT(`transcript_id`) as transcript_count, COUNT(DISTINCT(`gf_id`)) as gf_count FROM `transcripts` WHERE `experiment_id`='".$exp_id."' ";
    $res	= $this->query($query);   
    return $res;
  }

  function updateCodonStats($exp_id,$transcript_id,$orf_sequence){
    $has_start_codon	= 0;
    $has_stop_codon 	= 0;	
    if(strlen($orf_sequence)>=3){
      $start_codon	= substr($orf_sequence,0,3);
      $stop_codon	= substr($orf_sequence,-3,3);
      if($start_codon=="ATG"){$has_start_codon=1;}
      if($stop_codon=="TAA" || $stop_codon=="TAG" || $stop_codon=="TGA"){$has_stop_codon=1;}     
    }
    $statement = "update `transcripts` SET `orf_contains_start_codon`='".$has_start_codon."',`orf_contains_stop_codon`='".$has_stop_codon."' WHERE `experiment_id`='".$exp_id."' AND `transcript_id`='".$transcript_id."' ";
    $this->query($statement);
  }


  function findAssociatedGf($exp_id,$transcript_ids){
    $transcripts_string	= "('".implode("','",$transcript_ids)."')";
    $query	= "SELECT `gf_id`,COUNT(`transcript_id`) as count FROM `transcripts` WHERE `experiment_id`='".$exp_id."' AND 
	`transcript_id` IN ".$transcripts_string." GROUP BY `gf_id` ";
    $res	= $this->query($query);
    $result	= array();
    foreach($res as $r){
      $result[$r['transcripts']['gf_id']] = $r[0]['count'];
    }
    return $result;
  }

  function getSequenceStats($exp_id){
    $query	= "SELECT AVG(CHAR_LENGTH(`transcript_sequence`)) as avg_transcript_length, 
				AVG(CHAR_LENGTH(`orf_sequence`)) as avg_orf_length FROM `transcripts` 
			WHERE `experiment_id`='".$exp_id."' ";
    $res	= $this->query($query);
    $result     = array();
    $result["transcript"]	= round($res[0][0]['avg_transcript_length'],1);
    $result['orf']		= round($res[0][0]['avg_orf_length'],1);
    return $result;
  }





  function getLengths($exp_id,$sequence_type=null,$meta_annot=null){
    $result 	= array();
    $query	= null;
    if($sequence_type=="transcript"){
      $query	= "SELECT CHAR_LENGTH(`transcript_sequence`) as `length` FROM `transcripts` WHERE `experiment_id`='".$exp_id."'";
    }
    else if($sequence_type=="orf"){
      $query	= "SELECT CHAR_LENGTH(`orf_sequence`) as `length` FROM `transcripts` WHERE `experiment_id`='".$exp_id."'";
    }
    else{
      return $result;
    }   
    if($meta_annot!=null){
      $query	= $query." AND `meta_annotation`='".$meta_annot."' ";
    }
    $res	= $this->query($query);
    $result	= array();   
    foreach($res as $r){      
	$result[] 	= $r[0]['length'];	
    }  
    return $result;
  }





  function getMetaAnnotation($exp_id){
    $query	= "SELECT `meta_annotation`,`meta_annotation_score`,COUNT(`transcript_id`) as count FROM `transcripts` 
			WHERE `experiment_id`='".$exp_id."' GROUP BY `meta_annotation`,`meta_annotation_score` ";
    $res	= $this->query($query);
    $result	= array();
    foreach($res as $r){
      $meta_annotation		= $r['transcripts']['meta_annotation'];
      $meta_annotation_score	= $r['transcripts']['meta_annotation_score'];
      $count			= $r[0]['count'];
      if($meta_annotation==""){//no gene family assigned?
	$result["none"]["none"]	= $count;
	$result["none"]["total"] = $count;
      }
      else{
	if(!array_key_exists($meta_annotation,$result)){$result[$meta_annotation] = array("total"=>0);}
	$result[$meta_annotation][$meta_annotation_score] = $count;
	$result[$meta_annotation]["total"]+=$count;
      }
    }
    return $result;
  }

}


?>