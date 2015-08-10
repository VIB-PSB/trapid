<?php
class NormalizationComponent extends Object{
  var $controller	= true;
  function startup(&$controller){
     $this->controller = & $controller;
  } 	

  function getStatistics($gene_data){   
    $sum	= 0;
    $num_data	= count($gene_data);   
    if($num_data==0){
      $result	= array("average"=>"N/A","deviation"=>"N/A");
      return $result;
    }
    foreach($gene_data as $k=>$v){$sum+=$v;}
    $average	= $sum / $num_data;   
    
    $deviation	= 0;
    foreach($gene_data as $k=>$v){
      $d	= $v-$average;
      $d	= $d*$d;
      $deviation+=$d;	
    }
    $deviation  = sqrt($deviation/$num_data);
    $result	= array("average"=>$average,"deviation"=>$deviation);
    return $result;    
  }


  function makeGroups($gene_data,$predefined_min=null,$predefined_max=null){
    $result	= array();
    if(count($gene_data)==0){
      $result['min']	= "N/A";
      $result['max']	= "N/A";
      $result['gene_data'] = array();
      return $result;
    }
    //get min_max
    $min = PHP_INT_MAX;
    $max = 0;
    foreach($gene_data as $gd){
      if($gd<$min){$min=$gd;}
      if($gd>$max){$max=$gd;}
    }
    $result['min']  = $min;
    $result['max']  = $max;

    if($predefined_min==null && $predefined_max==null){
        if($min!=0){$min--;}
        $max++;	
    }
    else{
      $min	= $predefined_min;
      $max 	= $predefined_max;
      if($min!=0){$min--;}
      $max++;
    }        		    
    //pr($min."\t".$max);
    //make groups
    $result_gene_data = array();
    for($i=$min;$i<=$max;$i++){
      $result_gene_data[$i] = array("min"=>$i,"max"=>$i,"count"=>0);
    }
    foreach($gene_data as $gd){
      $result_gene_data[$gd]["count"]++;	
    }

    $final_gene_data	= array();
    foreach($result_gene_data as $k=>$v){
      //$final_gene_data[] = array("min"=>$k,"max"=>$k,"count"=>$v);
      $final_gene_data[] = $v;
    }
    $result['gene_data'] = $final_gene_data;
    return $result;	
  }



  function makeBuckets($gene_data,$num_buckets,$predefined_min=null,$predefined_max=null){    
    $result	= array();    
    if(count($gene_data)==0){
      $result['min']	= "N/A";
      $result['max']	= "N/A";
      $result['gene_data'] = array();
      return $result;
    } 

    //get min/max
    $min	= PHP_INT_MAX;
    $max	= 0;
    foreach($gene_data as $gd){
      if($gd<$min){$min=$gd;}
      if($gd>$max){$max=$gd;}
    }
    // pr("true min : ".$min);
    // pr("true max : ".$max); 
    $result['min']	= $min;
    $result['max']	= $max;
      
    if($predefined_min==null && $predefined_max==null){
        //round min/max to closest hundred-value     
        $min	= $min - $min%100;
        $max	= $max + (100 - $max%100);
    }
    else{
      $min	= $predefined_min - $predefined_min%100;
      $max 	= $predefined_max + (100 - $predefined_max%100);
    }
    //pr("rounded min : ".$min);
    //pr("rounded max : ".$max);
	

    $bucket_size 	= ($max-$min)/$num_buckets; 

    //pr("bucket_size : ".$bucket_size);

    $result_gene_data	= array();  	 
    for($i=0;$i<$num_buckets;$i++){      
      $result_gene_data[] = array("min"=>$min+$i*$bucket_size,"max"=>$min+($i+1)*$bucket_size,"count"=>0);
    }          
    foreach($gene_data as $gd){    
      $bucket_entry	= intval(($gd - $min)/$bucket_size); 
      $result_gene_data[$bucket_entry]['count'] ++;      
    }
    $result['gene_data']	= $result_gene_data;
    return $result;
  }
  
}
?>