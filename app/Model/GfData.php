<?php
  /*
   */
class GfData extends AppModel{
  var $name		= "GfData";
  var $useTable		= "gf_data";
  // var $useDbConfig 	= "db_plaza_public_02_5";
  // var $useDbConfig 	= "db_trapid_ref_plaza_monocots_03_test";




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
        $result		= array();
        $string_gf_ids	= "('".implode("','",$gf_ids)."')";
        // We now have a `species` field in the `gf_data` table directly.
        $query		= "SELECT `gf_id`, count(distinct `species`) as count FROM `gf_data` WHERE `gf_id` IN ".$string_gf_ids." GROUP BY `gf_id` ";
        $res		= $this->query($query);
        foreach($res as $r){
            $gf_id		= $r['gf_data']['gf_id'];
            $count		= $r[0]['count'];
            $result[$gf_id]	= $count;
        }
        return $result;
    }


  // This still works but should be slower than the method defined above
  function getSpeciesCountOldTrapid($gf_ids){
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


  // Check if GF exists -- use `gene_families` instead?
    function gfExists($ref_gf_id){
        $gf_data = $this->find("first", array('conditions'=>array('gf_id'=>$ref_gf_id)));
        if($gf_data) {
          return true;
        }
        else {
          return false;
        }
    }

    // To move to another model?
    function getTopGoTerms($ref_gf_id, $n_max) {
        $top_gos = [];
        $query		= "SELECT gf_fd.`name`, fd.`desc`, fd.`info` FROM  `gf_functional_data` gf_fd, `functional_data` fd WHERE gf_fd.`gf_id`='" . $ref_gf_id . "' AND gf_fd.`type`='go' AND gf_fd.`is_hidden`='0' AND fd.`name`=gf_fd.`name` ORDER BY gf_fd.`f_score` DESC";
        $res		= $this->query($query);
        foreach($res as $r) {
            $go_aspect = $r['fd']['info'];
            $go_name = $r['gf_fd']['name'];
            $go_desc = $r['fd']['desc'];
            if(!array_key_exists($go_aspect, $top_gos)){
                $top_gos[$go_aspect] = [];
            }
            if(count($top_gos[$go_aspect]) < $n_max){
                $top_gos[$go_aspect][] =  array("name"=>$go_name, "desc"=>$go_desc);
            }
        }
        return $top_gos;
    }

    // Retrieve EggNOG taxonomic scope for orthologous group `$nog_id`
    // Return taxonomic scope data (identifier and name/description) as an associative array
    function getEggnogTaxScope($nog_id) {
        $tax_scope_data = array();
        $db = $this->getDataSource();
        // Tax. scope could also be retrieved from `gene_families`
        $res = $db->fetchAll(
            'select name,scope from taxonomic_levels where scope = (select scope from gf_data where gf_id =? limit 1)',
            array($nog_id)
        );
        if($res) {
            $tax_scope_data = array("name"=>$res[0]['taxonomic_levels']['name'], "scope"=>$res[0]['taxonomic_levels']['scope']);
        }
        return $tax_scope_data;
    }
}
?>
