<?php
  /*
   * This model represents info on the functional enrichments
   */
class FunctionalEnrichments extends AppModel{

  var $name	= 'FunctionalEnrichments';
  var $useTable = 'functional_enrichments';
  
 
 function getTranscriptToLabelAndGF($exp_id){
    $result	= array();
    $query	= "SELECT transcript_id, label,gf_id
               FROM `transcripts` 
               RIGHT JOIN `transcripts_labels`
               USING (experiment_id, transcript_id)               
               WHERE experiment_id = $exp_id";
    $res	= $this->query($query);
    foreach($res as $r){
      $transcr	= $r['transcripts_labels']['transcript_id'];
      $label	= $r['transcripts_labels']['label'];
      $gf_id	= $r['transcripts']['gf_id'];
      if(!isset($result[$label])){
        $result[$label] = array($transcr => $gf_id);
       } else {
        $result[$label][$transcr] = $gf_id;
      }
    }
    return $result;
  }

function getEnrichedIdentifier($exp_id,$type){
    $result	= array();
    $query	= "SELECT label, identifier, is_hidden, max_p_value, log_enrichment
               FROM  `functional_enrichments`
               WHERE experiment_id = $exp_id
                   AND data_type = '$type'";
    $res	= $this->query($query);
    foreach($res as $r){
      $label  = $r['functional_enrichments']['label'];
      $ident  = $r['functional_enrichments']['identifier'];
      $hidden = $r['functional_enrichments']['is_hidden'];
      $p_val  = $r['functional_enrichments']['max_p_value'];
      $sign	  = $r['functional_enrichments']['log_enrichment'];
      if(!isset($result[$label]))$result[$label] = array();
      if(!isset($result[$label][$p_val]))$result[$label][$p_val] = array();
      $result[$label][$p_val][$ident] = array($hidden,$sign);
    }    
    return $result;
  }


 function getEnrichedGO($exp_id){
    return $this->getEnrichedIdentifier($exp_id, 'go');
  }

  function getEnrichedInterpro($exp_id){
    return $this->getEnrichedIdentifier($exp_id, 'ipr');
  }

 function getTranscriptGOMapping($exp_id){
    $result	= array();
    $query	=  "SELECT transcript_id, go
                FROM  `transcripts_go` 
                WHERE experiment_id =$exp_id
                  AND go IN (
                    SELECT DISTINCT identifier
                    FROM  `functional_enrichments` 
                    WHERE experiment_id =$exp_id
                    AND data_type = 'go')
                  AND transcript_id IN ( 
                    SELECT DISTINCT transcript_id
                    FROM  `transcripts_labels` 
                    WHERE experiment_id =$exp_id)";
    $res	= $this->query($query);
    foreach($res as $r){
      $transcr = $r['transcripts_go']['transcript_id'];
      $GO      = $r['transcripts_go']['go'];
      if(!isset($result[$transcr]))$result[$transcr] = array();
      $result[$transcr][$GO] = 1;
    }
    return $result;
  }



 function getTranscriptInterproMapping($exp_id){
    $result	= array();
    $query	=  "SELECT transcript_id, interpro
                FROM  `transcripts_interpro` 
                WHERE experiment_id =$exp_id
                  AND interpro IN (
                    SELECT DISTINCT identifier
                    FROM  `functional_enrichments` 
                    WHERE experiment_id =$exp_id
                    AND data_type = 'ipr')
                  AND transcript_id IN ( 
                    SELECT DISTINCT transcript_id
                    FROM  `transcripts_labels` 
                    WHERE experiment_id =$exp_id)";
    $res	= $this->query($query);
    foreach($res as $r){
      $transcr = $r['transcripts_interpro']['transcript_id'];
      $ipr      = $r['transcripts_interpro']['interpro'];
      if(!isset($result[$transcr]))$result[$transcr] = array();
      $result[$transcr][$ipr] = 1;
    }
    return $result;
  }

}


?>
