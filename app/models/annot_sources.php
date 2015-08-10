<?php
  /*
   */
class AnnotSources extends AppModel{
  var $name		= "AnnotSources";
  var $useTable		= "annot_sources";
  var $useDbConfig 	= "db_plaza_public_02_5";


  function getSpeciesCommonNames(){
	$query	= "SELECT `species`,`common_name` FROM `annot_sources` ORDER BY `common_name` ASC " ;
	$res	= $this->query($query);
	$result	= array();
	foreach($res as $r){
	  $sp	= $r['annot_sources']['species'];
	  $cn	= $r['annot_sources']['common_name'];
	  $result[$sp] = $cn;
	}
	return $result;
  }

  function getSpeciesFromTaxIds($tax_ids){
    $result	= array();
    if(count($tax_ids)==0){return $result;}
    $tax_ids_string   = "('".implode("','",$tax_ids)."')";
    $query	= "SELECT `species` FROM `annot_sources` WHERE `tax_id` IN ".$tax_ids_string;
    $res	= $this->query($query);
    foreach($res as $r){
      $spec	= $r['annot_sources']['species'];
      $result[$spec] = $spec;
    }
    return $result;
  }


}	
?>