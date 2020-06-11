<?php
  /*
   * This model represents info on the functional enrichments
   */

// Queries updated to reflect changes on the DB Structure for TRAPID v2

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
    // pr($result);
    return $result;
  }


function getSankeyEnrichmentResults($exp_id, $type){
    $enr_results	= array();
    $enr_hits_gf = array();
    $query	= "SELECT label, identifier, is_hidden, max_p_value, log_enrichment, subset_hits, subset_hits_gf_data
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
      $n_hits = $r['functional_enrichments']['subset_hits'];

      if(!isset($enr_results[$label])) {
          $enr_results[$label] = array();
      }
      if(!isset($enr_hits_gf[$label])) {
          $enr_hits_gf[$label] = array();
      }
      if(!isset($enr_results[$label][$p_val])) {
          $enr_results[$label][$p_val] = array();
      }
//      $enr_results[$label][$p_val][$ident] = array("is_hidden"=>$hidden, "enrichment"=>(float)$sign,  "n_hits"=>(int)$n_hits, "n_hits_gf"=>$n_hits_gf);
      $enr_results[$label][$p_val][$ident] = array("is_hidden"=>$hidden, "enrichment"=>(float)$sign,  "n_hits"=>(int)$n_hits);
        if(!isset($enr_hits_gf[$label][$ident])) {
            $n_hits_gf = array();
            $gf_data  = explode(";", $r['functional_enrichments']['subset_hits_gf_data']);
            foreach($gf_data as $gf_hit) {
                $gf_hit_val = explode('=', $gf_hit);
                $n_hits_gf[$gf_hit_val[0]] = (int) $gf_hit_val[1];
            }
            $enr_hits_gf[$label][$ident] = $n_hits_gf;
        }

    }
    return array("enrichment"=>$enr_results, "n_hits_gf"=>$enr_hits_gf);
  }


 function getEnrichedGO($exp_id){
    return $this->getEnrichedIdentifier($exp_id, 'go');
  }

  function getEnrichedInterpro($exp_id){
    return $this->getEnrichedIdentifier($exp_id, 'ipr');
  }

 function getTranscriptGOMapping($exp_id){
    $result	= array();
    $query	=  "SELECT transcript_id, name
                FROM  `transcripts_annotation`
                WHERE experiment_id =$exp_id
                AND `type`='go'
                  AND name IN (
                    SELECT DISTINCT identifier
                    FROM  `functional_enrichments`
                    WHERE experiment_id =$exp_id
                    AND data_type = 'go')
                  AND transcript_id IN (
                    SELECT DISTINCT transcript_id
                    FROM  `transcripts_labels`
                    WHERE experiment_id =$exp_id)";
    $res	= $this->query($query);
    foreach($res as $r) {
        $transcr = $r['transcripts_annotation']['transcript_id'];
        $GO = $r['transcripts_annotation']['name'];
        if (!isset($result[$transcr])) $result[$transcr] = array();
        $result[$transcr][$GO] = 1;
    }
    return $result;
  }



 function getTranscriptInterproMapping($exp_id){
    $result	= array();
    $query	=  "SELECT transcript_id, name
                FROM  `transcripts_annotation` 
                WHERE experiment_id =$exp_id
                AND `type`='ipr'
                  AND name IN (
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
      $transcr = $r['transcripts_annotation']['transcript_id'];
      $ipr      = $r['transcripts_annotation']['name'];
      if(!isset($result[$transcr]))$result[$transcr] = array();
      $result[$transcr][$ipr] = 1;
    }
    return $result;
  }

}


?>
