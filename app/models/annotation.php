<?php
  /*
   */
class Annotation extends AppModel{
  var $name		= "Annotation";
  var $useTable		= "annotation";
  var $useDbConfig 	= "db_plaza_public_02_5";



  function getSequencesGo($go_term){    
    $query 		= "SELECT `gene_id` FROM `gene_go` WHERE `go`='".$go_term."' ";
    $res		= $this->query($query);
    $gene_ids		= array();
    foreach($res as $r){$gene_ids[] = $r['gene_go']['gene_id'];}
    return $this->getSequences($gene_ids);
  }

  function getSequencesGf($gf_id){
    $query		= "SELECT `gene_id` FROM `gf_data` WHERE `gf_id`='".$gf_id."' ";
    $res		= $this->query($query);
    $gene_ids		= array();
    foreach($res as $r){$gene_ids[] = $r['gf_data']['gene_id'];}
    return $this->getSequences($gene_ids);
  }

  function getSequencesInterpro($interpro_id){
    $query		= "SELECT `gene_id` FROM `protein_motifs_data` WHERE `motif_id`='".$interpro_id."' ";
    $res		= $this->query($query);
    $gene_ids		= array();
    foreach($res as $r){$gene_ids[] = $r['protein_motifs_data']['gene_id'];}
    return $this->getSequences($gene_ids);
  }

  function getSequences($gene_ids){
    $gene_ids_string	= "('".implode("','",$gene_ids)."')";
    $query		= "SELECT `gene_id`,`seq` FROM `annotation` WHERE `gene_id` IN ".$gene_ids_string." ";
    $res		= $this->query($query);
    $result	        = array();
    foreach($res as $r){
      $gene_id	= $r['annotation']['gene_id'];
      $seq	= $r['annotation']['seq'];
      $result[$gene_id] = $seq;
    }
    return $result;
  }



  function getLengths($species){    
    $query		= "SELECT CHAR_LENGTH(`seq`) as `length` FROM `annotation` WHERE `species`='".$species."' AND `type`='coding'";
    $res		= $this->query($query);  
    $result		= array();
    foreach($res as $r){
      $length		= $r[0]['length'];
      $result[]		= $length;
    }       
    return $result;
  }


  function getGeneSizes($gene_ids){
    $result		= array();
    if(count($gene_ids)==0){return $result;}
    $gene_string	= "('".implode("','",$gene_ids)."')";
    $query		= "SELECT `gene_id`,CHAR_LENGTH(`seq`) as length FROM `annotation` WHERE `gene_id` IN ".$gene_string;
    $res		= $this->query($query);
    foreach($res as $r){
      $gene_id	= $r['annotation']['gene_id'];
      $length	= $r[0]['length'];
      $result[$gene_id]  = $length;
    }

    return $result;
  }

  function getSpeciesGeneCounts(){
    $query	= "SELECT `species`,count(`gene_id`) as count FROM `annotation` WHERE `type`='coding' GROUP BY `species`";
    $res	= $this->query($query);    
    $result	= array();
    foreach($res as $r){
      $spec	= $r['annotation']['species'];
      $count	= $r[0]['count'];
      $result[$spec] = $count;
    }
    return $result;
  }


  function getSpeciesCountForGenes($gene_ids){
    //pr($gene_ids);
    $result		= 0;
    $gene_string	= "('".implode("','",$gene_ids)."')";
    $query		= "SELECT COUNT(DISTINCT(`species`)) as count FROM `annotation` WHERE `gene_id` IN ".$gene_string; 
    $res		= $this->query($query);
    $result 		= $res[0][0]['count'];
    return $result;
  }


  function getSpeciesForGenes($gene_ids){
    $result		= array();
    if(count($gene_ids)==0){return $result;}
    $gene_string	= "('".implode("','",$gene_ids)."')";
    $query		= "SELECT `species`,`gene_id` FROM `annotation` WHERE `gene_id` IN ".$gene_string;
    $res		= $this->query($query);
    foreach($res as $r){
      $spec		= $r['annotation']['species'];
      $gene		= $r['annotation']['gene_id'];
      if(!array_key_exists($spec,$result)){$result[$spec] = array();}
      $result[$spec][] = $gene;      
    }
    return $result;
  }

  function getSpeciesProfile($gene_ids){
    $result			= array();
    if(count($gene_ids)==0){return $result;}
    $gene_string	= "('".implode("','",$gene_ids)."')";
    $query  = "SELECT `species`,COUNT(`gene_id`) as count FROM `annotation` WHERE `gene_id` IN ".$gene_string." GROUP BY `species` ";
    $res	= $this->query($query);
    foreach($res as $r){
      $spec	= $r['annotation']['species'];
      $count	= $r[0]['count'];
      $result[$spec]	= $count;
    }  
    return $result;
  }


}	
?>
