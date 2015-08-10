<?php
class MultipliconsComponent extends Object{
   

  function compute_density($multiplicons,$chromosomes){
    $result = array();
    foreach($chromosomes as $k=>$v){$result[$k]  = array();}
    foreach($multiplicons as $chr1=>$data1){
      foreach($data1 as $chr2=>$data2){
	foreach($data2 as $data){
	    
	}
      }
    }
    return $result;
  }

}
?>