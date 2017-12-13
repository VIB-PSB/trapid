<?php
  /*
   */
class PlazaConfiguration extends AppModel{
  var $name		= "PlazaConfiguration";
  var $useTable		= "configuration";
  var $useDbConfig 	= "db_plaza_public_02_5";



 /*
     * functino should return a hash, mapping clades to an array of species.
     * E.g. array("monocot"=>array('osa','zma',...))
     *
     */
    function getCladesWithSpecies(){
      $result		= array();
      $query		= "SELECT `value` FROM `configuration` WHERE `method`='manual' AND `key`='species_tree'";
      $res		= $this->query($query);
      if(!$res){return $result;}
      $res		= $res[0]['configuration']['value'];      
      //here comes the fun part: parsing the newick string in such a way that 
      if(preg_match_all("/[)][A-Z][A-Z,a-z,0-9]*:/",$res,$matches)){       
	foreach($matches[0] as $match){
	  $start_index			= strrpos($res,$match);
	  $num_closed_parenthesis	= 1;
	  $buffer			= "";
	  $species_array		= array();	
	  for($i=$start_index-1;$i>=0 && $num_closed_parenthesis>0;$i--){
	    $c	= $res[$i];
	    if($c!=":" && $c!="," && $c!="(" && $c!=")"){$buffer = $c.$buffer;}
	    else{ //check if species
	      if(strlen($buffer)>=3){
		  if(preg_match("/^[a-z]+[a-z,0-9]*/",$buffer,$secmatches)){
		    $species_array[]=$buffer;	    
		  }		 	
	      }
	      $buffer			= "";
	    }
	    if($c=="("){$num_closed_parenthesis--;}
	    if($c==")"){$num_closed_parenthesis++;}
	  }
	  $clade_name	  = substr($match,1,-1);
	  $result[$clade_name] = $species_array;
	}
      }
      return $result;
    }



}	
?>