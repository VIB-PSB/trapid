<?php
  /*
   * This model represents info on the transcripts
   */
class TranscriptsLabels extends AppModel {

  // The 'BigData' behavior is used to used for subset creation/update (to insert large amounts of data by chunks).
  public $actsAs = array('BigData.BigData');

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


  function getLabelToFctMapping($exp_id, $fct_type){
     $result = array();
     if(!in_array($fct_type, ["go", "ipr", "ko"])) {
         return $result;
     }
    // Add `is_hidden` = 0?
    $query	= "SELECT COUNT(*), label, name
                FROM `transcripts_annotation`
                LEFT JOIN `transcripts_labels` USING (experiment_id,transcript_id)
                WHERE experiment_id = ".$exp_id."
                AND `type`='" . $fct_type . "' 
                GROUP BY label,name";
    $res	= $this->query($query);
    foreach($res as $r){
      $label   = $r['transcripts_labels']['label'];
      $fct    = $r['transcripts_annotation']['name'];
      $count    = reset($r[0]);
      $result[] = array($label, $fct, $count);
    }
    return $result;
  }



    function enterTranscriptsNoCheck($exp_id,$transcripts,$label){
        $counter = 0;
        foreach($transcripts as $transcript_id){
              $counter++;
              $statement	= "INSERT INTO `transcripts_labels` (`experiment_id`,`transcript_id`,`label`) VALUES
              ('".$exp_id."','".$transcript_id."','".$label."');";
              $this->query($statement);
        }
        return $counter;
    }

    function enterTranscriptsInsertMulti($exp_id, $transcripts, $label) {
        $trapid_db = ConnectionManager::getDataSource('default');
        $fields = array('transcript_id', 'experiment_id', 'label');
        $to_save = array();
        $counter = 0;
        foreach($transcripts as $transcript) {
            $counter += 1;
            array_push($to_save, array($transcript, $exp_id, $label));
        }
        $result = $trapid_db->insertMulti("transcripts_labels", $fields, $to_save);
        return $counter;
    }


    function enterTranscriptsByChunks($exp_id, $transcripts, $label, $chunk_size=20000, $replace="ignore") {
//        $trapid_db = ConnectionManager::getDataSource('default');
        $fields = array('transcript_id', 'experiment_id', 'label');
        // $to_save = array();
        $counter = 0;
        foreach($transcripts as $transcript) {
            $counter += 1;
//            $this->addToBundle([$transcript, $exp_id, $label]);
            $this->addToBundle(array("id"=>0, "transcript_id"=>$transcript, "experiment_id"=>$exp_id, "label"=>$label));
        }
        $this->saveBundle($chunk_size, $replace);
//        $result = $trapid_db->insertMulti("transcripts_labels", $fields, $to_save);
        return $counter;
    }
}
