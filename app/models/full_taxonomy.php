<?php
  /*
   */
class FullTaxonomy extends AppModel{
  var $name		= "FullTaxonomy";
  var $useTable		= "full_taxonomy";
  var $useDbConfig 	= "db_trapid_01_taxonomy";






  function findClades($species_array){
    $result		= array();
    if(count($species_array)==0){return $result;}

    $tax_data		= array();
    $species_string	= "('".implode("','",$species_array)."')";
    $query		= "SELECT * FROM `full_taxonomy` WHERE `txid` IN $species_string ORDER BY `tax` ASC";
    $res		= $this->query($query);

    $clade_to_species		= array();
    $parents_to_child_clade 	= array();
    //$clade_descriptions		= array();

    foreach($res as $r){
      $tid		= $r['full_taxonomy']['txid'];
      $scname		= $r['full_taxonomy']['scname'];
      $tax_string	= $r['full_taxonomy']['tax'];
      $tax_split	= explode(";",$tax_string);

      for($i=0;$i<count($tax_split);$i++){
	$ts	= trim($tax_split[$i]);
      	if(!array_key_exists($ts,$clade_to_species)){$clade_to_species[$ts] = array();}
	$clade_to_species[$ts][] = $tid;

	if(!array_key_exists($ts,$parents_to_child_clade)){$parents_to_child_clade[$ts] = array();}
	for($j=$i+1;$j<count($tax_split);$j++){
	  $child_clade	= trim($tax_split[$j]);
	  if(!array_key_exists($child_clade,$parents_to_child_clade[$ts])){
	    $parents_to_child_clade[$ts][$child_clade] = $child_clade;
	  }
	}
      }
    }

    //ok, now select the unique "smallest" clades which contain more than 1 species
    foreach($clade_to_species as $clade=>$species){
      $num_species	= count($species);
      if($num_species>1){
        //check child clades
	$accept	= true;
	foreach($parents_to_child_clade[$clade] as $child_clade){
	  $num_species_child_clade	= count($clade_to_species[$child_clade]);
	  if($num_species_child_clade>1 && $num_species_child_clade == $num_species){
	    $accept	=false;
	    break;
	  }
	}
	if($accept){
	  $result[$clade] = $species;
	}
      }
    }   
    

    //ok, now create better parent-to-child clade representation.
    $final_parents_to_child_clade	= array();
    foreach($result as $clade=>$species){
      $child_clades	= array();
      foreach($parents_to_child_clade[$clade] as $child_clade){
	if(array_key_exists($child_clade,$result)){
	  $child_clades[] = $child_clade;
	}
      }
      $final_parents_to_child_clade[$clade] = $child_clades;
    }

    //pr($parents_to_child_clade);
    //pr($final_parents_to_child_clade);

 
    //query the database, and get the full string representation for this top clade
    $temp		= array();
    foreach($res as $r){
      $txid		= $r['full_taxonomy']['txid'];
      $scname		= $r['full_taxonomy']['scname'];
      $tax_string	= $r['full_taxonomy']['tax'];     
      $tax_split	= explode(";",$tax_string);
      $local_tmp	= "";
      for($i=0;$i<count($tax_split);$i++){
	$ts	= trim($tax_split[$i]);	
	if(array_key_exists($ts,$final_parents_to_child_clade)){
	  $local_tmp = $local_tmp."".$ts.";";
	}
      }
      $temp[] = $local_tmp."".$txid;
    }   
    //pr($temp);
    //pr($clade_to_species);
    $full_tree	       = $this->explodeTree($temp,";");
    
    $final_result	= array("parent_child_clades"=>$final_parents_to_child_clade,"clade_species_tax"=>$result,"full_tree"=>$full_tree);
    return $final_result;
  }


  function explodeTree($array,$delimiter='_'){
    if(!is_array($array)){ return false;}
    $splitRE   = '/' . preg_quote($delimiter, '/') . '/';
    
    $returnArr = array();
    $i = 0;
    foreach ($array as $val) {
	// Get parent parts and the current leaf	
	$parts  = preg_split($splitRE, $val, -1, PREG_SPLIT_NO_EMPTY);       
	//if($i==42){pr($val);pr($parts);}
	$leafPart = array_pop($parts);

	// Build parent structure
	// Might be slow for really deep and large structures
	$parentArr = &$returnArr;
	foreach ($parts as $part) {
	  if (!isset($parentArr[$part])) {
	    $parentArr[$part] = array();
	  } 
	  elseif (!is_array($parentArr[$part])) {		
	      $parentArr[$part] = array();		
	  }
	  $parentArr = &$parentArr[$part];
	}
	// Add the final part to the structure
	if (empty($parentArr[$leafPart])) {
	  if(is_numeric($leafPart)){$parentArr[]=$leafPart;}
	  else{$parentArr[$leafPart] = array();}
	} 
	$i++;
    }
    return $returnArr;
  }
}
?>