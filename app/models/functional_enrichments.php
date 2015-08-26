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
      $transcript	= $r['transcripts']['transcript_id'];
      $gf_id	= $r['transcripts']['gf_id'];
      $label	= $r['functional_enrichments']['label'];
      $GO	    = $r['functional_enrichments']['identifier'];
      $hidden	= $r['functional_enrichments']['is_hidden'];
      $p_val	= $r['functional_enrichments']['max_p_value'];
      $ratio	= $r['functional_enrichments']['subset_ratio'];
      $result[] = array($label,$GO,$ratio,$hidden,$p_val);
    }    
    return $result;
  }


/*SELECT transcript_id, label,gf_id, identifier, is_hidden, subset_ratio
FROM `transcripts` 
RIGHT JOIN `transcripts_labels`
USING (experiment_id, transcript_id)
JOIN `functional_enrichments`
USING (experiment_id,label)
WHERE experiment_id = 1 AND data_type = 'go'

SELECT transcript_id, label,gf_id, identifier, is_hidden, subset_ratio, max_p_value
FROM `transcripts` 
RIGHT JOIN `transcripts_labels`
USING (experiment_id, transcript_id)
JOIN `functional_enrichments`
USING (experiment_id,label)
WHERE experiment_id = 1 AND data_type = 'go' */

}
?>
