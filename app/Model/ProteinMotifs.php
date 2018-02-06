<?php
  /*
   */
class ProteinMotifs extends AppModel{
  var $name		= "ProteinMotifs";
  // var $useTable		= "protein_motifs";
  // var $useDbConfig 	= "db_plaza_public_02_5";
  var $useTable		= "functional_data";
  // var $useDbConfig 	= "db_trapid_ref_plaza_monocots_03_test";




  function retrieveInterproInformation($interpro_ids){
    $result	= array();
    if($interpro_ids==null || count($interpro_ids)==0){return $result;}
    $interpro_string	= "('".implode("','",$interpro_ids)."')";
    // $query	= "SELECT * FROM `protein_motifs` WHERE `motif_id` IN ".$interpro_string;
    $query	= "SELECT * FROM `functional_data` WHERE `type`=\"interpro\" AND `name` IN ".$interpro_string;
    $res	= $this->query($query);
    foreach($res as $r){
      // $s	= $r['protein_motifs'];
      // $result[$s['motif_id']] = array("desc"=>$s['desc']);
      $s	= $r['functional_data'];
      $result[$s['name']] = array("desc"=>$s['desc']);
    }
    return $result;
  }



}
?>
