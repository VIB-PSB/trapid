<?php
  /*
   */
class ExtendedGo extends AppModel{
  var $name		= "ExtendedGo";
  var $useTable		= "extended_go";
  var $useDbConfig 	= "db_plaza_public_02_5";





  function getDepthsPerCategory(){
    $result	= array();
    $query 	= "SELECT `type` , MAX( `num_sptr_steps` ) AS max FROM `extended_go` 
			WHERE `is_obsolete` = '0' GROUP BY `type`";	
    $res	= $this->query($query);
    foreach($res as $r){
      $type	= $r['extended_go']['type'];     
      $max 	= $r[0]['max'];
      $result[$type]	= $max;
    }
    return $result;
  }

  function retrieveGoInformation($go_ids){
    $result	= array();
    if($go_ids==null || count($go_ids)==0){return $result;}
    $go_string	= "('".implode("','",$go_ids)."')";
    $query	= "SELECT * FROM `extended_go` WHERE `go` IN ".$go_string;
    $res	= $this->query($query);
    foreach($res as $r){
      $s	= $r['extended_go'];
      $result[$s['go']] = array("desc"=>$s['desc']);
    }
    return $result;
  }



  function getGeneralProfile($go_ids){
    if(!is_array($go_ids)){$go_ids = array($go_ids);}
    
  }



}
?>