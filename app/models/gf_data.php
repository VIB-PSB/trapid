<?php
  /*
   */
class GfData extends AppModel{
  var $name		= "GfData";
  var $useTable		= "gf_data";
  var $useDbConfig 	= "db_plaza_public_02_5";


  

  function getProfile($gene_ids,$gf_prefix){
    $result		= array();
    $string_gene_ids	= "('".implode("','",$gene_ids)."')";	
    $query		= "SELECT `gf_id`,count(`gene_id`) as count FROM `gf_data` WHERE `gene_id` IN ".$string_gene_ids." ";
    if($gf_prefix){$query = $query." AND `gf_id` LIKE '".$gf_prefix."%' ";}
    $query		= $query." GROUP BY `gf_id` ";
    $res		= $this->query($query);
    foreach($res as $r){
      $gf_id		= $r['gf_data']['gf_id'];
      $count		= $r[0]['count'];
      $result[$gf_id] 	= $count;
    }
    return $result;
  }


  function getGeneCount($gf_ids){
    $result		= array();
    $string_gf_ids	= "('".implode("','",$gf_ids)."')";
    $query		= "SELECT `gf_id`,count(`gene_id`) as count FROM `gf_data` WHERE `gf_id` IN ".$string_gf_ids." GROUP BY `gf_id` "; 
    $res		= $this->query($query);
    foreach($res as $r){
      $gf_id		= $r['gf_data']['gf_id'];
      $count		= $r[0]['count'];
      $result[$gf_id]	= $count;	
    }
    return $result;
  }

  function getGenes($gf_ids){
    $result		= array();
    if(is_array($gf_ids)){
	$string_gf_ids	= "('".implode("','",$gf_ids)."')";
	$query		= "SELECT `gf_id`,`gene_id` FROM `gf_data` WHERE `gf_id` IN ".$string_gf_ids." ";
	$res		= $this->query($query);
	foreach($res as $r){
	  $gf_id		= $r['gf_data']['gf_id'];
	  $gene_id		= $r['gf_data']['gene_id'];
	  if(!array_key_exists($gf_id,$result)){$result[$gf_id] = array();}
	  $result[$gf_id][] = $gene_id;
	}
    }
    else{
      $query	= "SELECT `gene_id` FROM `gf_data` WHERE `gf_id`='".$gf_ids."' ";
      $res	= $this->query($query);
      foreach($res as $r){
	$gene_id  = $r['gf_data']['gene_id'];
	$result[] = $gene_id;
      }
    }    
    return $result;
  }


  function getSpeciesCount($gf_ids){
    $gf_genes		= $this->getGenes($gf_ids);
    $result		= array();
    foreach($gf_ids as $gf_id){
      $gene_ids		= $gf_genes[$gf_id];
      $gene_ids_string	= "('".implode("','",$gene_ids)."')";
      $query		= "SELECT COUNT(DISTINCT(`species`)) as count FROM `annotation` WHERE `gene_id` IN ".$gene_ids_string." ";
      $res		= $this->query($query);
      $count		= $res[0][0]['count'];
      $result[$gf_id] 	= $count;
    }   
    return $result;
  }

}
?>