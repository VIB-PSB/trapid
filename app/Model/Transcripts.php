<?php
  /*
   * This model represents info on the transcripts
   */

   // Queries updated to reflect changes made to the db for TRAPID 2.0
class Transcripts extends AppModel {

  var $name	= 'Transcripts';
  var $useTable = 'transcripts';


    public $virtualFields = array(
        'transcript_sequence' => 'UNCOMPRESS(Transcripts.transcript_sequence)',
        'transcript_sequence_corrected' => 'UNCOMPRESS(Transcripts.transcript_sequence_corrected)',
        'orf_sequence' => 'UNCOMPRESS(Transcripts.orf_sequence)'
    );


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
    $data_source = $this->getDataSource();
    $exp_id	= $data_source->value($exp_id, 'integer');
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
    $query	= "SELECT AVG(CHAR_LENGTH(UNCOMPRESS(`transcript_sequence`))) as avg_transcript_length,
				AVG(CHAR_LENGTH(UNCOMPRESS(`orf_sequence`))) as avg_orf_length FROM `transcripts`
			WHERE `experiment_id`='".$exp_id."' ";
    $res	= $this->query($query);
    $result     = array();
    $result["transcript"]	= round($res[0][0]['avg_transcript_length'],1);
    $result['orf']		= round($res[0][0]['avg_orf_length'],1);
    return $result;
  }


  function getAvgTranscriptLength($exp_id){
    $query	= "SELECT AVG(CHAR_LENGTH(UNCOMPRESS(`transcript_sequence`))) as avg_transcript_length FROM `transcripts`
			WHERE `experiment_id`='".$exp_id."' ";
    $res	= $this->query($query);
//    $result     = 0;
    $result	= round($res[0][0]['avg_transcript_length'],1);
    return $result;
  }


  function getAvgOrfLength($exp_id){
    $query	= "SELECT AVG(CHAR_LENGTH(UNCOMPRESS(`orf_sequence`))) as avg_orf_length FROM `transcripts`
			WHERE `experiment_id`='".$exp_id."' ";
    $res	= $this->query($query);
//    $result     = 0;
    $result	= round($res[0][0]['avg_orf_length'],1);
    return $result;
  }





  function getLengths($exp_id,$sequence_type=null,$meta_annot=null){
    $result 	= array();
    $query	= null;
    if($sequence_type=="transcript"){
      $query	= "SELECT CHAR_LENGTH(UNCOMPRESS(`transcript_sequence`)) as `length` FROM `transcripts` WHERE `experiment_id`='".$exp_id."'";
    }
    else if($sequence_type=="orf"){
      $query	= "SELECT CHAR_LENGTH(UNCOMPRESS(`orf_sequence`)) as `length` FROM `transcripts` WHERE `experiment_id`='".$exp_id."'";
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

  function getLabelToGFMapping($exp_id,$reverse=false){
    $query	= "SELECT COUNT(*), transcripts.`gf_id`,transcripts_labels.`label`
               FROM transcripts LEFT JOIN transcripts_labels ON
                  (transcripts_labels.`transcript_id`=transcripts.`transcript_id`
                   AND transcripts_labels.`experiment_id`=transcripts.`experiment_id`)
               WHERE transcripts.`experiment_id` = ".$exp_id." AND transcripts.`gf_id` IS NOT NULL
               GROUP BY transcripts.`gf_id`, transcripts_labels.`label`
               ORDER BY COUNT( * ) DESC ";
    $res	= $this->query($query);
    $result	= array();
    foreach($res as $r){
      $gf_id    = $r['transcripts']['gf_id'];
      $label    = $r['transcripts_labels']['label'];
      $count    = reset($r[0]);
      if(!$reverse){
        $result[] = array($gf_id,$label,$count);
      } else {
        $result[] = array($label,$gf_id,$count);
      }
    }
    return $result;
  }

  function getGOToGFMapping($exp_id,$reverse=false){
    $query	= "SELECT COUNT( * ) , transcripts.`gf_id` , transcripts_annotation.`name`
               FROM transcripts
               LEFT JOIN transcripts_annotation ON ( transcripts_annotation.`transcript_id` = transcripts.`transcript_id`
                                             AND transcripts_annotation.`experiment_id` = transcripts.`experiment_id` )
               WHERE transcripts.`experiment_id` = ".$exp_id."
               AND transcripts_annotation.`type` = 'go'
               AND transcripts.`gf_id` IS NOT NULL AND transcripts_annotation.`name` IS NOT NULL
               GROUP BY transcripts.`gf_id` , transcripts_annotation.`name`
               ORDER BY COUNT( * ) DESC ";
    $res	= $this->query($query);
    $result	= array();
    foreach($res as $r){
      $gf_id    = $r['transcripts']['gf_id'];
      $GO       = $r['transcripts_annotation']['name'];
      $count    = reset($r[0]);
      if(!$reverse){
        $result[] = array($gf_id,$GO,$count);
      } else {
        $result[] = array($GO,$gf_id,$count);
      }
    }
    return $result;
  }

  function getOneGOToGFMapping($exp_id,$go){
    $query	= "SELECT COUNT( * ) , transcripts.`gf_id` , transcripts_annotation.`name`
               FROM transcripts
               LEFT JOIN transcripts_annotation ON ( transcripts_annotation.`transcript_id` = transcripts.`transcript_id`
                                             AND transcripts_annotation.`experiment_id` = transcripts.`experiment_id` )
               WHERE transcripts.`experiment_id` = $exp_id
               AND transcripts_annotation.`name` = '$go'
               AND transcripts_annotation.`type` = 'go'
               GROUP BY transcripts.`gf_id`";
    $res	= $this->query($query);
    $result	= array();
    foreach($res as $r){
      $gf_id    = $r['transcripts']['gf_id'];
      $GO       = $r['transcripts_annotation']['name'];
      $count    = reset($r[0]);
      $result[] = array($GO,$gf_id,$count);
    }
    return $result;
  }

  function getinterproToGFMapping($exp_id,$reverse=false){
    $query	= "SELECT COUNT( * ) , transcripts.`gf_id` , transcripts_annotation.`name`
               FROM transcripts
               LEFT JOIN transcripts_annotation ON ( transcripts_annotation.`transcript_id` = transcripts.`transcript_id`
                                             AND transcripts_annotation.`experiment_id` = transcripts.`experiment_id` )
               WHERE transcripts.`experiment_id` = ".$exp_id."
               AND transcripts_annotation.`type`='ipr'
               AND transcripts.`gf_id` IS NOT NULL
               AND transcripts_annotation.`name` IS NOT NULL
               GROUP BY transcripts.`gf_id` , transcripts_annotation.`name`
               ORDER BY COUNT( * ) DESC ";
    $res	= $this->query($query);
    $result	= array();
    foreach($res as $r){
      $gf_id    = $r['transcripts']['gf_id'];
      $interpro = $r['transcripts_annotation']['name'];
      $count    = reset($r[0]);
      if(!$reverse){
        $result[] = array($gf_id,$interpro,$count);
      } else {
        $result[] = array($interpro,$gf_id,$count);
      }
    }
    return $result;
  }


  function getOneInterproToGFMapping($exp_id,$interpro){
    $query	= "SELECT COUNT( * ) , transcripts.`gf_id` , transcripts_annotation.`name`
               FROM transcripts
               LEFT JOIN transcripts_annotation ON ( transcripts_annotation.`transcript_id` = transcripts.`transcript_id`
                                             AND transcripts_annotation.`experiment_id` = transcripts.`experiment_id` )
               WHERE transcripts.`experiment_id` = $exp_id
               AND transcripts_annotation.`type` = 'ipr'
               AND transcripts_annotation.`name` = '$interpro'
               GROUP BY transcripts.`gf_id` ";  //, transcripts_interpro.`interpro` ";
    $res	= $this->query($query);
    $result	= array();
    foreach($res as $r){
      $gf_id    = $r['transcripts']['gf_id'];
      $interpro = $r['transcripts_annotation']['name'];
      $count    = reset($r[0]);
      $result[] = array($interpro,$gf_id,$count);

    }
    return $result;
  }



  // No check on `$ko`??
  function getOneKOToGFMapping($exp_id, $ko){
    $query	= "SELECT COUNT( * ) , transcripts.`gf_id` , transcripts_annotation.`name`
               FROM transcripts
               LEFT JOIN transcripts_annotation ON ( transcripts_annotation.`transcript_id` = transcripts.`transcript_id`
                                             AND transcripts_annotation.`experiment_id` = transcripts.`experiment_id` )
               WHERE transcripts.`experiment_id` = $exp_id
               AND transcripts_annotation.`type` = 'ko'
               AND transcripts_annotation.`name` = '$ko'
               GROUP BY transcripts.`gf_id` ";
    $res	= $this->query($query);
    $result	= array();
    foreach($res as $r){
      $gf_id    = $r['transcripts']['gf_id'];
      $ko_id = $r['transcripts_annotation']['name'];
      $count    = reset($r[0]);
      $result[] = array($ko_id, $gf_id, $count);
    }
    return $result;
  }


    // Count the number of ORFs in an experiment
    // ORF => `orf_stop` > 0 (end of ORF sequence on the transcript)
    function getOrfCount($exp_id){
        $result 	= array();
        $query = "SELECT COUNT(*) as count FROM `transcripts` WHERE `experiment_id`='".$exp_id."' AND `orf_stop` > 0;";
        $res	= $this->query($query);
        $result	= 0;
        foreach($res as $r){
          $result = $r[0]['count'];
        }
        return $result;
    }


    // Get all sequences (uploaded sequence, corrected sequence, ORF sequence) associate to transcript `transcript_id`,
    // from experiment `exp_id`, as text. Return them as associative array (keys = name of fields in the database).
    // Why is data returned in such a nested array?
    function getAllSqces($exp_id, $transcript_id) {
        $result 	= array();
        $query = "SELECT UNCOMPRESS(`transcript_sequence`) as `transcript_sequence`, UNCOMPRESS(`transcript_sequence_corrected`) as `transcript_sequence_corrected`, UNCOMPRESS(`orf_sequence`) as `orf_sequence` FROM `transcripts` WHERE `experiment_id`='".$exp_id."' AND `transcript_id` ='" . $transcript_id . "';";
        $res	= $this->query($query);
        foreach($res[0][0] as $k=>$v){
            $result[$k] = $v;
        }
        return $result;
    }

    /* Unused code: quick test to write custom queries when overriding pagination */

//    /* Create custom query, optionally forcing to use a certain index */
//    // TODO: Upgrade function to make it work with a selection of fields as well (not using `*`)
//    function createQuery($parameters,$count=FALSE, $chosen_index=null) {
//        // `SELECT` string pieces, concatenated at the end to create the custom query
//        $query_select = "SELECT ";
//        $query_from = "FROM transcripts ";
//        $query_where = "WHERE ";
//        // Handle parameters
//        $num_parameters = count($parameters);
//        $exp_id = mysql_real_escape_string($parameters["Transcripts.experiment_id"]);
//        $parameters_keys = array_keys($parameters);
//        $prefix = false;
//        if (strpos($parameters_keys[0], "Transcripts") === 0) {
//            $prefix = true;
//        };
//        // Parse parameters
//        foreach ($parameters as $col => $value) {
//            if ($col != end($parameters_keys)) {
//                $query_where = $query_where . $col . " = '" . $value . "' AND ";
//            } else {
//                $query_where = $query_where . $col . " = '" . $value . "' ";
//            }
//        }
//        // Put table alias if there is a prefix
//        if($prefix) {
//            $query_from = $query_from . " as Transcripts ";
//        }
//        // If we chose to force use an index (`chosen_index`), append it
//        if ($chosen_index) {
//            $query_from = $query_from . "FORCE INDEX (" . $chosen_index . ") ";
//        }
//        // Append data to select (depending on count/prefix)
//        if ($count) {
//            if ($prefix) {
//                $query_select = $query_select . "COUNT(Transcripts.transcript_id) as count ";
//            } else {
//                $query_select = $query_select . "COUNT(transcript_id) as count ";
//            }
//        }
//        else {
//          $query_select	= $query_select ." * ";
//        }
//        // Create full query string and return it
//        $query_full = $query_select . $query_from . $query_where;
//        return $query_full;
//    }
//
//
//    /* Override `paginate()` function */
//    function paginate($conditions,$fields,$order,$limit,$page=1,$recursive=null,$extra=array()){
//        $custom_query = $this->createQuery($conditions,false, $chosen_index="experiment_id");
//        // pr("custom_query:".$custom_query);
//        if($custom_query === false) {
//          return null;
//        }
//        $limit_start = ($page-1)*$limit;
//        $limit_end = $limit;
//        if($limit_start<0) {
//            $limit_start = 0;
//        }
//        $use_limit = true;
//
//        if($order) {
//            $custom_query = $custom_query." ORDER BY ";
//            foreach($order as $key=>$value){
//                $custom_query = $custom_query . "" . $key . " " . $value . " ";
//            }
//        }
//        if($use_limit) {
//            $custom_query = $custom_query . " LIMIT " . $limit_start . "," . $limit_end;
//        }
//        // pr("custom_query:".$custom_query);
//        $res = $this->query($custom_query);
//        $result	= array();
//        foreach($res as $r){
//            $result[] = $r;
//        }
//        return $result;
//    }
//
//
//    /* Override `paginateCount()` function */
//    function paginateCount($conditions=null,$recursive=0,$extra=array()){
//        $custom_query = $this->createQuery($conditions,true);
//        if($custom_query === false) {
//            return 0;
//        }
//        $res = $this->query($custom_query);
//        $result = $res[0][0]['count'];
//        return $result;
//    }

}
