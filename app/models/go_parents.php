<?php
  /*
   */
class GoParents extends AppModel{
  var $name		= "GoParents";
  var $useTable		= "go_parents";
  var $useDbConfig 	= "db_plaza_public_02_5";



	/**
	 * This method retrievs all the parental GO terms from a given GO, and their associated smallest path to root (sptr).
	 * Data is stored in 2 ways (go to sptr, sptr to multiple go's), in order for fast lookups.
	 * 
	 * @param go_id GO term for which we want the parent terms
	 * @return A double mapping from go to sptr and sptr to go. Indexed in result by 'go_depth' and 'depth_go'
	 */
	function getParentsDepth($go_id){
	   $query   = "SELECT a.`parent_go`, b.`num_sptr_steps`,b.`desc` FROM `go_parents` a, `extended_go` b 
				WHERE a.`child_go`='".$go_id."'
	      			AND a.`parent_go` = b.`go` ; ";
	   $rs	    = $this->query($query);	  
	   $result1 = array();
	   $result2 = array();
	   $result3 = array();
	   $max_depth = 0;
	   foreach($rs as $r){
	     $pgo 	= $r['a']['parent_go'];
	     $depth	= $r['b']['num_sptr_steps'];	     
	     $desc	= $r['b']['desc'];
	     $result1[$pgo] = $depth;	     //GO's are unique
	     if(!array_key_exists($depth,$result2)){$result2[$depth] = array();}
	     $result2[$depth][] = $pgo;
	     if($depth > $max_depth){$max_depth = $depth;}
	     $result3[$pgo] = $desc;
	   }
	   $result = array("go_depth"=>$result1,"depth_go"=>$result2,"max_depth"=>$max_depth,"desc"=>$result3);
	   return $result;
	}

	/**
	 * This method tries to reconstruct the parental graph for a given GO term.
	 * This is not that easy, since we'll have to do multiple queries (due to database structure)
	 *
	 * @param go_id GO term for which we want to construct the parental terms in a graph
	 * @return Graph datastructure
	 */
       	function getParentalGraph($go_id){
	  //step 0: check validity of go
	  $query_desc = "SELECT `desc` FROM `extended_go` WHERE `go` = '".$go_id."' ";
	  $desc_go    = $this->query($query_desc);
	  if(!$desc_go){return null;}
	  $desc_go    = $desc_go[0]['extended_go']['desc'];

	  //step1 : get parental GO terms, and their associated depths from this GO term
	  $go_parents_depth 	= $this->getParentsDepth($go_id);	  
	  $nodes		= array();	
	  $edges		= array();
	  $max_depth		= $go_parents_depth['max_depth'];
	  //step2 : top-bottom approach : those with the shortest path to root are the top
	  $custom_depth 	= 0;		  
	  for($i=0;$i<=$max_depth;$i++){
	    $data = $go_parents_depth["depth_go"];		
	    if($i==0){$nodes[0] = array($data[0][0]);}
	    else{	      
	      //check if it is connected to one or more GO terms at sptr $i-1. If so: create edges
	      //also check if it is connected to one or more GO terms at level $i. If so: push down the current GO term a level, 
	      //and all subsequent child terms of that GO as well. Therefore we include a new variable: custom_depth, which should handle 
	      //this type of work.	      
	      
	      //A)check for parental GO terms at level $i-1;
	      $relations1 = $this->checkChildrenAgainstParents($data[$i],$data[$i-1]);
	      
	      //B)check for parental GO terms within a level
	      $relations2 = $this->checkChildrenAgainstParents($data[$i],$data[$i]);
	      
 	      //C)now, we select the correct depth for each child GO term, and add data to the graph result
	      $data1	    = $relations1["child_parent"];
	      $data2	    = $relations2["child_parent"];	     
	      $hor_f	    = false;
	      foreach($data1 as $child_term=>$parental_terms){
		$current_depth = $custom_depth;
		if(array_key_exists($child_term,$data2)){
		  $current_depth++;
		  $hor_f    = true;	
		}
		if(!array_key_exists($current_depth,$nodes)){$nodes[$current_depth] = array();}
	        $nodes[$current_depth][] = $child_term;
		foreach($parental_terms as $pt){
		  $edges[] = array("child"=>$child_term,"parent"=>$pt);
		}
	      }
	      if($hor_f){$custom_depth++;}	
	    }
	    $custom_depth++;	//new total depth.
	  } 
	  //finally, latch the query go_id onto the graph
	  $nodes[$custom_depth] = array($go_id);	  
	  $edges[] = array("child"=>$go_id,"parent"=>$nodes[$custom_depth-1][0]);
	  $go_parents_depth = $go_parents_depth['desc'];
	  $go_parents_depth[$go_id] = $desc_go;	 	
	  $result = array("edges"=>$edges,"nodes"=>$nodes,"desc"=>$go_parents_depth);
	  return $result;
	}




	/**
	 * Takes as input a set of child GO terms and a set of parent GO terms, and then tries to find
	 * a parent child relationship between them.
	 * Result consists of 2 mappings: 1 parent_child and 1 child_parent
	 *
	 * @param go_children : Set of potential child GO terms
	 * @param go_parents : Set of potential parent GO terms
	 * @return Mapping of true parent-child relationships.
	 */
	function checkChildrenAgainstParents($go_children,$go_parents){
	  $children_string = implode("','",$go_children);
	  $parents_string  = implode("','",$go_parents);
	  $query 	   = "SELECT `child_go`,`parent_go` FROM `go_parents` 
					WHERE `child_go` IN ('".$children_string."') 
					AND `parent_go` IN ('".$parents_string."') ;";
	  $res  	   = $this->query($query);
	  $result	   = array();
	  $pc_array	   = array();
	  $cp_array	   = array();	
	  foreach($res as $r){
	    $cg		   = $r['go_parents']['child_go'];
	    $pg		   = $r['go_parents']['parent_go'];
	    if(!array_key_exists($cg,$cp_array)){$cp_array[$cg] = array();}
	    $cp_array[$cg][] = $pg;
	    if(!array_key_exists($pg,$pc_array)){$pc_array[$pg] = array();}
	    $pc_array[$pg][] = $cg;
	  }
	  $result["parent_child"] = $pc_array;
	  $result["child_parent"] = $cp_array;
	  return $result;
	}

}
?>