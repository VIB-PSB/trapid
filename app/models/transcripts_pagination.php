<?php
  /*
   * This model represents info on the transcripts
   */
class TranscriptsPagination extends AppModel{

  var $name	= 'TranscriptsPagination';
  var $useTable = 'transcripts';




  function getPossibleParameters(){
	//transcripts:			a
    	//transcripts_labels:		b,b1,b2,b3,...
    	//transcripts_go:		c,c1,c2,c3,...
    	//transcripts_interpro: 	d,d1,d2,d3,...	
	 $possible_parameters	= 
	      array("min_transcript_length"=>array("query"=>"CHAR_LENGTH(`transcript_sequence`)>=",
						   "table"=>"`transcripts`",
						   "tabid"=>"a",
						   "desc"=>"Minimum transcript length"),   
		    "max_transcript_length"=>array("query"=>"CHAR_LENGTH(`transcript_sequence`)<=",
						   "table"=>"`transcripts`",
						   "tabid"=>"a",
						   "desc"=>"Maximum transcript length"),
		    "min_orf_length"=>array("query"=>"CHAR_LENGTH(`orf_sequence`)>=",
					    "table"=>"`transcripts`",
					    "tabid"=>"a",
					    "desc"=>"Minimum ORF length"),						   
		    "max_orf_length"=>array("query"=>"CHAR_LENGTH(`orf_sequence`)<=",
					    "table"=>"`transcripts`",
					    "tabid"=>"a",
					    "desc"=>"Maximum ORF length"),
		    "meta_annotation"=>array("query"=>"`meta_annotation`=",
					     "table"=>"`transcripts`",
					     "tabid"=>"a",
					     "desc"=>"Meta annotation"),
		    "gf_id"=>array("query"=>"`gf_id`=",
				   "table"=>"`transcripts`",
				   "tabid"=>"a",
				   "desc"=>"Gene family"),				   
		    "label"=>array("query"=>"`label`=",
				   "table"=>"`transcripts_labels`",
				   "tabid"=>"b",
				   "desc"=>"Subset"),						       
		    "go"=>array("query"=>"`go`=",
				"table"=>"`transcripts_go`",
				"tabid"=>"c",
				"desc"=>"GO label"),						 
		    "interpro"=>array("query"=>"`interpro`=",
				      "table"=>"`transcripts_interpro`",
				      "tabid"=>"d",
				      "desc"=>"InterPro domain"),						          
		    );
	 return $possible_parameters;
  }


  function getParsedParameters($parameters){
    	$result			= array();	
	$possible_parameters	= $this->getPossibleParameters();	
	$num_parameters	= count($parameters);	
	for($i=1;$i<$num_parameters;$i+=2){
      		$key	= mysql_real_escape_string($parameters[$i]);
     		$value	= mysql_real_escape_string(urldecode($parameters[$i+1]));
		if(array_key_exists($key,$possible_parameters)){
		  $desc		= $possible_parameters[$key]["desc"];
		  if(!array_key_exists($desc,$result)){$result[$desc] = array();}
		  $result[$desc][] = $value;
		}
	}
	return $result;
  }


  function createQuery($parameters,$count=FALSE){      
  	
    $possible_parameters	= $this->getPossibleParameters();
    $params		= array();
    $num_parameters	= count($parameters);
    $exp_id		= mysql_real_escape_string($parameters[0]);
    //parse parameters
    for($i=1;$i<$num_parameters;$i+=2){      
      $key	= mysql_real_escape_string($parameters[$i]);
      $value	= mysql_real_escape_string(urldecode($parameters[$i+1]));    
      if(!array_key_exists($key,$possible_parameters)){/*pr($key);*/return FALSE;}
      $params[]	= array("key"=>$key,"value"=>$value);	//not single hash, as multiple keys should be able to be used at the same time.
    }

    //select the necessary tables for joining.
    $used_tables	= array("`transcripts` a");		//default inclusion
    $used_joins		= array();
    $used_exp_sel	= array("a.`experiment_id`='".$exp_id."'");
    $used_queries	= array();
    $used_increases	= array("a"=>0,"b"=>0,"c"=>0,"d"=>0);
    foreach($params as $param){
      $par		= $possible_parameters[$param['key']];
      $tabid		= $par["tabid"];
      //establish unique table identifier per table (except transcripts)
      $table_id		= $tabid;
      if($tabid!="a"){
	$used_increases[$tabid]++;
	$table_id   	= $tabid."".$used_increases[$tabid];	
      }
      //table definitions
      $used_tables[] 	= $par['table']." ".$table_id;	      
      //joins between transcript table and other tables
      if($tabid!="a"){	//don't need a join when restriction is put on table transcripts
	$used_joins[]	= $table_id.".`transcript_id`=a.`transcript_id`";
      }    
      //every table should be restricted by experiment id as well      
      $used_exp_sel[]	= $table_id.".`experiment_id`='".$exp_id."'";
      //query itself, table identifier should be entered before the first "`" character (which should indicate a column)
      $query_insert_loc	= strpos($par['query'],"`");
      $used_query	= substr($par['query'],0,$query_insert_loc)."".$table_id.".".substr($par['query'],$query_insert_loc)."'".$param['value']."'";
      $used_queries[]	= $used_query;         
    }    
    $used_tables	= implode(",",array_unique($used_tables));
    $used_joins		= implode(" AND ",array_unique($used_joins));
    $used_exp_sel	= implode(" AND ",array_unique($used_exp_sel));	
    $used_queries	= implode(" AND ",array_unique($used_queries));
    
    $query		= "SELECT ";
    if($count){$query	= $query. "COUNT(a.`transcript_id`) as count ";}    
    else{$query		= $query." a.`transcript_id` ";}
    $query		= $query." FROM ".$used_tables." WHERE ".$used_exp_sel." AND ".$used_queries;
    if(strlen($used_joins)>0){$query = $query." AND ".$used_joins;}   	      
    return $query;
  }





  function paginate($conditions,$fields,$order,$limit,$page=1,$recursive=null,$extra=array()){  
    $custom_query		= $this->createQuery($conditions,FALSE);     
    //pr("custom_query:".$custom_query);
    if($custom_query===FALSE){return null;}
    $limit_start = ($page-1)*$limit;
    $limit_end = $limit;	
    if($limit_start<0){$limit_start = 0;}
    $use_limit = true;
	
    if($order){
      $custom_query		= $custom_query." ORDER BY ";
      foreach($order as $k=>$v){
	$custom_query		= $custom_query." a.`".$k."` ".$v." ";
      }
    }
    if($use_limit){
	$custom_query		= $custom_query." LIMIT ".$limit_start.",".$limit_end;
    }    
    $res			= $this->query($custom_query);   
    $result			= array();
    foreach($res as $r){
      $result[]		= $r['a']['transcript_id'];
    }
    return $result;
  }

  function paginateCount($conditions=null,$recursive=0,$extra=array()){
    $custom_query	= $this->createQuery($conditions,TRUE);
    if($custom_query===FALSE){return 0;}
    $res		= $this->query($custom_query);   
    $result		= $res[0][0]['count'];
    return $result;
  }


}


?>