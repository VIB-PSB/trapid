<?php
  /*
   * This model represents info on the transcripts
   */
class FunctionalEnrichments extends AppModel{

  var $name	= 'FunctionalEnrichments';
  var $useTable = 'functional_enrichments';

  
 function getLabeltoEnrichedInterproMapping($exp_id){
    $result	= array();
    $query	= "SELECT  `functional_enrichments`.label, 
                       `functional_enrichments`.identifier,
                       `functional_enrichments`.is_hidden,
                       `functional_enrichments`.max_p_value,
                       `functional_enrichments`.subset_ratio
               FROM `functional_enrichments` 
               WHERE experiment_id = $exp_id
                   AND data_type = 'ipr'";    
    $res	= $this->query($query);
    foreach($res as $r){
      $label	= $r['functional_enrichments']['label'];
      $interpro	= $r['functional_enrichments']['identifier'];
      $hidden	= $r['functional_enrichments']['is_hidden'];
      $p_val	= $r['functional_enrichments']['max_p_value'];
      $ratio	= $r['functional_enrichments']['subset_ratio'];
      $result[] = array($label,$interpro,$ratio,$hidden,$p_val);
    }    
    return $result;
  }

 function getLabeltoEnrichedGOMapping($exp_id){
    $result	= array();
    $query	= "SELECT `functional_enrichments`.label, 
                      `functional_enrichments`.identifier,
                      `functional_enrichments`.is_hidden,
                      `functional_enrichments`.max_p_value,
                      `functional_enrichments`.subset_ratio
               FROM `functional_enrichments` 
               WHERE experiment_id = $exp_id
                   AND data_type = 'go'";
    $res	= $this->query($query);
    foreach($res as $r){
      $label	= $r['functional_enrichments']['label'];
      $GO	    = $r['functional_enrichments']['identifier'];
      $hidden	= $r['functional_enrichments']['is_hidden'];
      $p_val	= $r['functional_enrichments']['max_p_value'];
      $ratio	= $r['functional_enrichments']['subset_ratio'];
      $result[] = array($label,$GO,$ratio,$hidden,$p_val);
    }    
    return $result;
  }

/* Works in theory, exhausts memory in practice */
 function getEnrichedTranscripts($exp_id){
    $result	= array();
    $query	= "SELECT transcript_id, label,gf_id, identifier, is_hidden, subset_ratio, max_p_value
               FROM `transcripts` 
               RIGHT JOIN `transcripts_labels`
               USING (experiment_id, transcript_id)
               JOIN `functional_enrichments`
               USING (experiment_id,label)
               WHERE experiment_id = $exp_id
                   AND data_type = 'go'";
    $res	= $this->query($query);
    foreach($res as $r){
      $transcr	= $r['transcripts']['transcript_id'];
      $label	= $r['functional_enrichments']['label'];
      $gf_id	= $r['transcripts']['gf_id'];
      $GO	    = $r['functional_enrichments']['identifier'];
      $hidden	= $r['functional_enrichments']['is_hidden'];
      $ratio	= $r['functional_enrichments']['subset_ratio'];
      $p_val	= $r['functional_enrichments']['max_p_value'];
      $result[] = array($transcr,$label,$gf_id,$GO,$hidden,$p_val);
    }    
    return $result;
  }

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

// ratio is unnecessary maybe
 function getEnrichedGO($exp_id){
    $result	= array();
    $query	= "SELECT identifier, is_hidden, max_p_value, subset_ratio
               FROM  `functional_enrichments`
               WHERE experiment_id = $exp_id
                   AND data_type = 'go'";
    $res	= $this->query($query);
    foreach($res as $r){
      $GO	    = $r['functional_enrichments']['identifier'];
      $hidden	= $r['functional_enrichments']['is_hidden'];
      $p_val	= $r['functional_enrichments']['max_p_value'];
      if(!isset($result[$p_val]))$result[$p_val] = array();
      $result[$p_val][] = array($GO => $hidden);
    }    
    return $result;
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
}


?>
