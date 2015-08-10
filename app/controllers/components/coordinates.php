<?php
class CoordinatesComponent extends Object{
   


  function removeEnclosingStatements($coord_string){
    $cs = $coord_string;
    if(substr($cs,0,1)=='c'){
      $cs = substr($cs,11);
      $cs = substr($cs,0,strlen($cs)-1);
    }
    if(substr($cs,0,1)=='j'){
      $cs = substr($cs,5);
      $cs = substr($cs,0,strlen($cs)-1);
    }
    pr($cs);
    return $cs;
  }


  function getStartFromCoordinates($coordinates){
    if(substr($coordinates,0,1)=="j"){
      $coordinates = substr($coordinates,5);
      $coordinates = substr($coordinates,0,strlen($coordinates)-1);
    }
    else{
      $coordinates = substr($coordinates,16);
      $coordinates = substr($coordinates,0,strlen($coordinates)-2);
    }
    $split = split(",",$coordinates);
    $split2 = split("\\.\\.",$split[0]);
    $start = $split2[0];
    return $start;
  }

  function coordinatesToArray($coord){
     $result = array();
     $temp = split(",",$coord);
     foreach($temp as $t){
       $k = split("\\.\\.",$t);
       $result[] = $k;
     }
     return $result;
   }
  
  function parseCoordinates($trans,$start_cds,$stop_cds,$strand){       
    $result 		= array();	
	
    $bf_utr		= "five_prime_UTR";
    $af_utr		= "three_prime_UTR";
    if($strand=="-"){
      $bf_utr		= "three_prime_UTR";
      $af_utr		= "five_prime_UTR";
    }

    foreach($trans as $tr){
      $start 		= $tr[0];
      $stop 		= $tr[1];
      //normal operation : exon lies within coding regions
      if($start>=$start_cds && $stop<=$stop_cds){
	$result[]	= array("type"=>"CDS","start"=>$start,"stop"=>$stop);
      }
      //pure UTR behavior : exon lies before coding sequence
      else if($start<$start_cds && $stop<=$start_cds){       
	$result[] 	= array("type"=>$bf_utr,"start"=>$start,"stop"=>$stop);
      }
      //pure UTR behavior : exon lies after coding sequence
      else if($start>=$stop_cds && $stop>$stop_cds){
	$result[] 	= array("type"=>$af_utr,"start"=>$start,"stop"=>$stop);
      }
      //spliced UTR behavior : exon lies accross starting point region and not acros ending point region
      else if($start< $start_cds && $stop>$start_cds && $stop<=$stop_cds){     
	$result[]	= array("type"=>$bf_utr,"start"=>$start,"stop"=>$start_cds-1);
	$result[]	= array("type"=>"CDS","start"=>$start_cds,"stop"=>$stop);
      }
      //spliced UTR behavior : exon lies accross ending point region and not acros starting point region
      else if($start < $stop_cds && $stop > $stop_cds && $start>=$start_cds){
	$result[]	= array("type"=>"CDS","start"=>$start,"stop"=>$stop_cds);
	$result[]	= array("type"=>$af_utr,"start"=>$stop_cds+1,"stop"=>$stop);	
      }
      //Fully splice UTR behavior:
      //gene lies across both start and ending point region
      //should only happen with single exon genes having UTR's
      else{
	$result[]	= array("type"=>$bf_utr,"start"=>$start,"stop"=>$start_cds-1);
	$result[]	= array("type"=>"CDS","start"=>$start_cds,"stop"=>$stop_cds);
	$result[]	= array("type"=>$af_utr,"start"=>$stop_cds+1,"stop"=>$stop);
      }
    }
    return $result;	
  }
}
?>