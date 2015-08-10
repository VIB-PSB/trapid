<?php
class GenesComponent extends Object{
	
     var $controller = true;

	  
     function startup(&$controller){
       $this->controller = & $controller;
     } 	

     /*
      * The idea is to have a component which is able to provide a rather flexible system for displaying a list
      * of genes that is constrained by certain functional items.
      * 
      * The first parameter is an array with key-value items. Every key must be in this list:
      * 	a) gf 	: gene_family
      *		b) sp   : species
      *		c) go	: go-label
      *		d) ev	: evidence tag	
      *		e) ip   : interpro-label 
      * The associated value then acts as a restriction on the total list of genes.
      * 
      * The second parameter is a link to the database model (models should not be constructed from a component, but from
      * a controller).
      *
      * The third parameter contains some extra information, that can be used for configuration (used limits, used parent-child
      * relationships between go-labels, ...).
      */
     function index_reduced($keys, $db_model, $special_info){
	
       $result = array();
       $key_array = array("gf"=>"gene family","sp"=>"species","go"=>"go-label","ev"=>"evidence tag (go-label)","ip"=>"interpro domain");

       //basic check for valid keys
       foreach($keys as $key=>$value){
	 if(!array_key_exists($key,$key_array)){
	   return $esult;
	 }
       }

       //retrieve the correct information from the database
       $extra_info = array("go"=>"include_children","limit"=>"on");
       $query_array = array("search"=>$keys,"extra"=>$extra_info);
       $result = $this->paginate($db_model,$query_array);
       return $result;	
     }	





     function merge_3_2_results($db_model,$cv1,$cv2,$limit=null,$key_value_1=null,$key_value_2=null,$key_value_3 = null){
       
       $res_3_restrictions  = $this->index_reduced_new($db_model,$cv1,"OFF",$key_value_1,$key_value_2,$key_value_3);
       $res_2_restrictions  = $this->index_reduced_new($db_model,$cv2,$limit,$key_value_1,$key_value_2);
       
       $overview_keys = $res_3_restrictions['overview_keys'];
       $used_keys     = $res_3_restrictions['used_keys'];

       $res_3_restrictions = $res_3_restrictions['result'];
       $res_2_restrictions = $res_2_restrictions['result'];

       $res_3_gene_list    = array();
       foreach($res_3_restrictions as $r){
	 $res_3_gene_list[$r['gene_id']] = $r['gene_id'];
       }       
       $result = array();
	
       $difference = substr($cv1,strlen($cv2)+1,strlen($cv1)-strlen($cv2)-1);
       $incl = "incl";
       if($difference=="go")
	 $incl = "go";
       else if($difference=="ip")
	 $incl = "interpro";      
       	

       foreach($res_2_restrictions as $key=>$val){
	 $r2_geneid = $val['gene_id'];
	 
	 $temp = $val;
	 if(isset($res_3_gene_list[$r2_geneid]))
	   $temp[$incl] = "true";
	 else
	   $temp[$incl] = "false";
	 $result[] = $temp;
  
       }

       $final_result = array();
       $final_result['result'] 		= $result;
       $final_result['overview_keys'] 	= $overview_keys;
       $final_result['used_keys'] 	= $used_keys;	
       return $final_result;       
     }



     function index_reduced_new($db_model,$combo_value = null,$limit=null,$key_value_1 = null, $key_value_2 = null, $key_value_3 = null){
      if(!$combo_value){
      	$this->redirect("/");
      }
      //split the key indicator, create arrays with possibilities
      $split_c_value = split("-",$combo_value);
      $key_array = array("gf"=>"gene family","sp"=>"species","go"=>"go-label","ev"=>"evidence tag (go-label)","ip"=>"interpro domain");
      //  $options_array = array("gf"=>array("0"=>""),
      //			     "sp"=>array("0"=>""),
      //			     "go"=>array("0"=>"","1"=>"include_children"),
      //			     "ev"=>array("0"=>""),
      //			     "ip"=>array("0"=>""));	
		   
      $final_key_array = array();
      $value_array = array("0"=>$key_value_1,"1"=>$key_value_2,"2"=>$key_value_3);

      //first thing to do: remove duplicate values from the $split_c_value array... You can't put different constraints on 1 key
      $split_c_value = array_unique($split_c_value);

      //check whether or not the correct amount of keys is offered.
      if(!(count($split_c_value) ==1 || count($split_c_value) == 2 || count($split_c_value)==3)){
       	$this->redirect("/");
      }

      //check the correct amount of values
      if(count($split_c_value)==1){
	if(!($key_value_1 && !$key_value_2 && !$key_value_3))
	  $this->redirect("/");
      }
      else if(count($split_c_value)==2){
	if(!($key_value_1 && $key_value_2 && !$key_value_3))
	  $this->redirect("/");
      }
      else if(count($split_c_value)==3){
	if(!($key_value_1 && $key_value_2 && $key_value_3))
	  $this->redirect("/");
      } 
	
      //check for the correctness of the keys
      foreach($split_c_value as $index=>$sc){
	if(!(array_key_exists($sc,$key_array)))
	   $this->redirect("/");
	else{
	   $final_key_array[$sc] = $value_array[$index];
	   //special case: renaming of go-labels
	   if($sc=="go"){
	     $final_key_array[$sc] = str_replace("-",":",$value_array[$index]);
	   }
	}
      }	

	

      //get the information about using limits
      if(!($limit == "ON" || $limit=="on" || $limit=="OFF"||$limit=="off"))
	$this->redirect("/");
	

      //retrieve the correct information from the database
      $extra_info = array("go"=>"include_children","limit"=>$limit);
      $query_array = array("search"=>$final_key_array,"extra"=>$extra_info);
      $result = $this->controller->paginate($db_model,$query_array);

      $final_result = array("result"=>$result,"overview_keys"=>$key_array,"used_keys"=>$final_key_array);
      return $final_result;	
     }
}
?>