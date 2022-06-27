<?php
  /*
   * This model represents info on the transcripts
   */
class GeneFamilies extends AppModel {

  var $name	= 'GeneFamilies';
  var $useTable = 'gene_families';




  function findByGene($exp_id,$gene_id){
    $data_source = $this->getDataSource();
    $exp_id	= $data_source->value($exp_id, 'integer');
    $result	= array();
    if(strlen($gene_id) <= 5){return $result;}

    $gf_data = $this->find("all",
        array("conditions"=>array("experiment_id"=>$exp_id, "gf_content LIKE"=>"%$gene_id%"),
              "fields"=>array("gf_id", "plaza_gf_id", "num_transcripts")));
    // $query	= "SELECT `gf_id`,`plaza_gf_id`,`num_transcripts` FROM `gene_families` WHERE `experiment_id`='".$exp_id."' AND `gf_content` LIKE '%".$gene_id."%' ";
    // pr($query)
    // $res	= $this->query($query);
    // foreach($res as $r){
    //    $result = $r['gene_families'];
    foreach($gf_data as $r){
      $result = $r['GeneFamilies'];
    }
    return $result;

  }


  function getGfInfo($exp_id,$gf_id){
    $data_source = $this->getDataSource();
    $exp_id	= $data_source->value($exp_id, 'integer');
    $gf_id	= $data_source->value($gf_id, 'string');

    $query 	= "SELECT a.*,b.`transcript_id` FROM `gene_families` a,`transcripts` b 
			WHERE a.`experiment_id`='".$exp_id."' AND b.`experiment_id`='".$exp_id."' 
			AND a.`gf_id`=".$gf_id." AND b.`gf_id`=a.`gf_id` ";
    $res	= $this->query($query);
    pr($res);

    return 12;
  }


    function gfExists($exp_id, $gf_id){

        $data_source = $this->getDataSource();
        $exp_id	= $data_source->value($exp_id, 'integer');
        $gf_id	= $data_source->value($gf_id, 'string');
        $query = "SELECT * FROM `gene_families` WHERE `experiment_id`='".$exp_id."' AND `gf_id`=".$gf_id.";";
        $res = $this->query($query);
        if($res)
            return true;
        else
            return false;
    }


}
