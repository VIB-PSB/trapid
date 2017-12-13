<?php
  /*
   * This model represents info on the transcripts
   */
class GeneFamilies extends AppModel{

  var $name	= 'GeneFamilies';
  var $useTable = 'gene_families';




  function findByGene($exp_id,$gene_id){
    $exp_id	= mysql_real_escape_string($exp_id);
    $gene_id	= mysql_real_escape_string($gene_id);
    $result	= array();
    if(strlen($gene_id) <= 5){return $result;}

    $query	= "SELECT `gf_id`,`plaza_gf_id`,`num_transcripts` FROM `gene_families` WHERE `experiment_id`='".$exp_id."' AND `gf_content` LIKE '%".$gene_id."%' ";	
    $res	= $this->query($query);
    foreach($res as $r){
      $result	= $r['gene_families'];
    }    
    return $result;

  }


  function getGfInfo($exp_id,$gf_id){
    $exp_id	= mysql_real_escape_string($exp_id);
    $gf_id 	= mysql_real_escape_string($gf_id);

    $query 	= "SELECT a.*,b.`transcript_id` FROM `gene_families` a,`transcripts` b 
			WHERE a.`experiment_id`='".$exp_id."' AND b.`experiment_id`='".$exp_id."' 
			AND a.`gf_id`='".$gf_id."' AND b.`gf_id`=a.`gf_id` ";  
    $res	= $this->query($query);
    pr($res);

    return 12;
  }




}


?>