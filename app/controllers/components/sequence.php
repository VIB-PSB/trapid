<?php
class SequenceComponent extends Object{

 

  function translate_multicds_php($dna_sequences){
    $lookup	= array("TC"=>"S","CT"=>"L","CC"=>"P","CG"=>"R","AC"=>"T","GT"=>"V","GC"=>"A","GG"=>"G",
			"TAA"=>"*","TAG"=>"*","TAR"=>"*","TGA"=>"*",
			"TTT"=>"F","TTC"=>"F","TTY"=>"F",
			"TTA"=>"L","TTG"=>"L","TTR"=>"L",
			"TAT"=>"Y","TAC"=>"Y","TAY"=>"Y",			
			"TGT"=>"C","TGC"=>"C","TGY"=>"C",
			"TGG"=>"W",
			"CAT"=>"H","CAC"=>"H","CAY"=>"H",
			"CAA"=>"Q","CAG"=>"Q","CAR"=>"Q",
			"ATT"=>"I","ATC"=>"I","ATA"=>"I","ATH"=>"I","ATY"=>"I","ATW"=>"I","ATM"=>"I",
			"ATG"=>"M",
			"AAT"=>"N","AAC"=>"N","AAY"=>"N",
			"AAA"=>"K","AAG"=>"K","AAR"=>"K",
			"AGT"=>"S","AGC"=>"S","AGY"=>"S",
			"AGA"=>"R","AGG"=>"R","AGR"=>"R",
			"GAT"=>"D","GAC"=>"D","GAY"=>"D",
			"GAA"=>"E","GAG"=>"E","GAR"=>"E",
			);
    $result	= array();
    foreach($dna_sequences as $transcript_id=>$dna_sequence){
	$res	= "";
	for($i=0;$i<strlen($dna_sequence);$i+=3){
	  $start	= $i;
	  $stop	= $i+3; if($stop>=strlen($dna_sequence)){$stop = strlen($dna_sequence)-1;}
	  $codon	= substr($dna_sequence,$start,($stop-$start));
	  if(array_key_exists($codon,$lookup)){$res=$res."".$lookup[$codon];}
	  else{
	    $codon	= substr($dna_sequence,$start,2);
	    if(array_key_exists($codon,$lookup)){$res=$res."".$lookup[$codon];}
	    else{$res=$res."X";}
	  }		       
	}
	$onemin	= substr($res,0,strlen($res)-1);
	$last	= substr($res,strlen($res)-1);
	$final_res	= $res;
	if($last=='X'){$final_res = $onemin."*";}	
	$result[$transcript_id]	= $final_res;
    }			
    return $result;
  }



  function translate_cds_php($dna_sequence){
    $tmp	= array("tmp"=>$dna_sequence);
    $result	= $this->translate_multicds_php($tmp);
    $res	= $result['tmp'];
    return $res;			
  }


  function get_all_blast_dbs($annot_source_handle){
     	$db_suffix     	= substr(DB_NAME,9); 
	$all_species   	= $annot_source_handle->getCommonNamesNice();
	$available_db	= array();
        foreach($all_species as $k=>$v){            
	   $available_db[$k] = "plaza/genomes/plaza_genomes_".$db_suffix.".".$k;
        }
	return $available_db;	
  }

  function get_blast_db($species){   
    $db_suffix     	= substr(DB_NAME,9); 	
    $blast_db		= "plaza/genomes/plaza_genomes_".$db_suffix.".".$species;
    return $blast_db;
  }

  	


  function fetch_sequence_webservice($webservice_handle,$annotation_db_handle,$gene_id,$extraction_type,$file_id,$force_fastacmd){    
    if($extraction_type=="exons"){
      $processed_file_name	= TMP."multi_fasta/processed_".$file_id."_".$extraction_type.".txt";
      $genomic_result_file	= TMP."multi_fasta/fastcmd_".$file_id."_"."genomic".".txt";		
      if(!file_exists($processed_file_name)){
	//create genomic data file
	$this->fetch_sequence_webservice($webservice_handle,$annotation_db_handle,$gene_id,"genomic",
							   $file_id,$force_fastacmd);
	$annot_data	 	= $annotation_db_handle->getFullGeneInfo($gene_id);
	$this->process_fasta_cmd_result($genomic_result_file,$processed_file_name,$annot_data,$extraction_type);
      }
      $result				= $this->read_processed_results($processed_file_name);
      return $result;				
    }
    else if($extraction_type=="2kbupstream" || $extraction_type=="2kbdownstream" 
		|| $extraction_type=="1kbupstream" || $extraction_type=="1kbdownstream"){
      $gene_file_name		= TMP."multi_fasta/gene_list_".$file_id."_".$extraction_type.".txt";    
      $result_file_name		= TMP."multi_fasta/fastcmd_".$file_id."_".$extraction_type.".txt";
      $processed_file_name	= TMP."multi_fasta/processed_".$file_id."_".$extraction_type.".txt";	
      if(!file_exists($processed_file_name) || !file_exists($result_file_name) ||$force_fastacmd){
	$positions		= null;
	if($extraction_type=="1kbupstream"){$positions	= $this->find_intergenic_positions($gene_id,$annotation_db_handle,"1000","up");}
	if($extraction_type=="1kbdownstream"){$positions= $this->find_intergenic_positions($gene_id,$annotation_db_handle,"1000","down");}
	if($extraction_type=="2kbupstream"){$positions	= $this->find_intergenic_positions($gene_id,$annotation_db_handle,"2000","up");}
	if($extraction_type=="2kbdownstream"){$positions= $this->find_intergenic_positions($gene_id,$annotation_db_handle,"2000","down");}
	$annot_data	= $annotation_db_handle->getFullGeneInfo($gene_id);
	$this->createIntergenicGeneFileRaw($gene_file_name,$gene_id,$annot_data['species'],$annot_data['chr'],
			$positions['start'],$positions['stop'],$annot_data['strand']);
	$ws_data	= array("gene_file_name"=>$gene_file_name,"result_file_name"=>$result_file_name,"temp_dir"=>TMP."multi_fasta/");
	$ws_result	= $webservice_handle->fetch_sequences_from_blastdb($ws_data);
	$this->process_fasta_cmd_result($result_file_name,$processed_file_name,$annot_data,$extraction_type);
      }
      $result	       = $this->read_processed_results($processed_file_name);   
      return $result;       
    }
    else{   
  	$gene_file_name		= TMP."multi_fasta/gene_list_".$file_id."_".$extraction_type.".txt";    
    	$result_file_name	= TMP."multi_fasta/fastcmd_".$file_id."_".$extraction_type.".txt";
	$processed_file_name	= TMP."multi_fasta/processed_".$file_id."_".$extraction_type.".txt";
	$result			= array();
	if(!file_exists($processed_file_name) || !file_exists($result_file_name) || $force_fastacmd){
	  //fetch the annotation data
	  $annot_data	 	= $annotation_db_handle->getFullGeneInfo($gene_id);	   	  	
	  $data_written		= $this->createIntergenicGeneFile($gene_file_name,$annot_data,$gene_id,$extraction_type);
	  if($data_written){
	    if(!file_exists($result_file_name) || $force_fastacmd){
	        $ws_data			= array("gene_file_name"=>$gene_file_name,
					"result_file_name"=>$result_file_name,
					"temp_dir"=>TMP."multi_fasta/");
	        $ws_result			= $webservice_handle->fetch_sequences_from_blastdb($ws_data);	
	    }
	    $this->process_fasta_cmd_result($result_file_name,$processed_file_name,$annot_data,$extraction_type);	
	  }
	  else{
	    return $result;
	  }
	}
	$result				= $this->read_processed_results($processed_file_name);
	return $result;
     }
  }




  function find_intergenic_positions($gene_id,$annotation_db_handle,$size,$type){
     $annot = $annotation_db_handle->getFullGeneInfo($gene_id);
     $transcript_exons			= $this->get_exons($annot['coord_transcript']);	
     $transcript_start			= $transcript_exons[0][0];
     $transcript_stop			= $transcript_exons[count($transcript_exons)-1][1];

     $result				= array();

     //find gene_data of next/prev gene.
     $other_gene			= null;
     $other_gene_start			= -1;
     $other_gene_stop			= -1;     	
     if($annot['strand']=="+" && $type=="up"){
        $other_gene=  $annotation_db_handle->findPreviousGene($annot['species'],$annot['chr'],$transcript_start);
     }
     else if($annot['strand']=="+" && $type=="down"){
        $other_gene = $annotation_db_handle->findNextGene($annot['species'],$annot['chr'],$transcript_stop);
     }
     else if($annot['strand']=="-" && $type=="up"){
	$other_gene = $annotation_db_handle->findNextGene($annot['species'],$annot['chr'],$transcript_stop);
     }
     else if($annot['strand']=="-" && $type=="down"){
	$other_gene =  $annotation_db_handle->findPreviousGene($annot['species'],$annot['chr'],$transcript_start);
     }
     
     if($other_gene!=null){
       //pr($other_gene);
	$annot_other		= $annotation_db_handle->getFullGeneInfo($other_gene);
	$transcript_exons_other = $this->get_exons($annot_other['coord_transcript']);
	$other_gene_start	= $transcript_exons_other[0][0];
	$other_gene_stop	= $transcript_exons_other[count($transcript_exons_other)-1][1];	
	
	$start			= 0;
	$stop			= 0;
	if($annot['strand']=="+" && $type=="up"){
	  $start 		= $transcript_start - $size; if($start<$other_gene_stop){$start=$other_gene_stop+1;}
	  $stop			= $transcript_start-1;
	}
	else if($annot['strand']=="+" && $type=="down"){
	  $start		= $transcript_stop+1;
	  $stop			= $transcript_stop + $size; if($stop>$other_gene_start){$stop=$other_gene_start-1;}
	}
	else if($annot['strand']=="-" && $type=="up"){
	  
	  $start		= $transcript_stop+1;
	  $stop			= $transcript_stop + $size; if($stop>$other_gene_start){$stop=$other_gene_start-1;}
	}
	else if($annot['strand']=="-" && $type=="down"){
	  $start		= $transcript_start - $size; if($start<$other_gene_stop){$start=$other_gene_stop+1;}
	  $stop			= $transcript_start-1;
	}
	$result["start"]	= $start;
        $result["stop"]		= $stop;	
     }
     else{
       $start			= 0;
       $stop			= 0;
       if($annot['strand']=="+" && $type=="up"){
	  $start		= $transcript_start - $size+1; if($start<0){$start=0;}
	  $stop			= $transcript_start-1;
       }
       else if($annot['strand']=="+" && $type=="down"){
	 $start			= $transcript_stop+1;
	 $stop			= $transcript_stop+$size;	//fastacmd handles this
       }
       else if($annot['strand']=="-" && $type=="up"){
	 $start			= $transcript_stop+1;
	 $stop			= $transcript_stop+$size;	//fastacmd handles this	       
       }
       else if($annot['strand']=="-" && $type=="down"){
	 $start			= $transcript_start - $size+1; if($start<0){$start=0;}
	 $stop			= $transcript_start-1;
       }
       $result["start"]	= $start;
       $result["stop"]	= $stop;
     }
     return $result;
          
  }

  
  function process_fasta_cmd_result($fastacmd_file,$processed_file,$annot_data,$extraction_type){
    $result			= array();
    $fh 			= fopen($fastacmd_file,'r');    
    $file_data			= fread($fh,filesize($fastacmd_file));    	
    fclose($fh);
    $data			= explode("\n",$file_data);
    if($extraction_type=="genomic"){
      //should be only 1 line        
      $data			= explode("\t",$data[0]);
      $sequence			= $data[5];
      $transcript_exons		= $this->get_exons($annot_data['coord_transcript']);
      $to_subtract		= $transcript_exons[0][0];     	
      $cds_start		= $annot_data['start'];
      $cds_stop			= $annot_data['stop'];
      for($i=0;$i<count($transcript_exons);$i++){
	$exon			= $transcript_exons[$i];
	//CHECK EXONS
	//pure 5'UTR
	if($exon[0]<$cds_start && $exon[1]<$cds_start){
	  $result[]		= array("type"=>"utr","seq"=>substr($sequence,$exon[0]-$to_subtract,$exon[1]-$exon[0]+1),
					"start"=>$exon[0],"stop"=>$exon[1]);            
	}
	//pure exon
	else if($exon[0]>=$cds_start && $exon[1]<=$cds_stop){
	  $result[]		= array("type"=>"exon","seq"=>substr($sequence,$exon[0]-$to_subtract,$exon[1]-$exon[0]+1),
					"start"=>$exon[0],"stop"=>$exon[1]);
	}
	//pure 3' UTR
	else if($exon[0]>$cds_stop && $exon[1]>$cds_stop){
	  $result[]		= array("type"=>"utr","seq"=>substr($sequence,$exon[0]-$to_subtract,$exon[1]-$exon[0]+1),
					"start"=>$exon[0],"stop"=>$exon[1]);
	}
	//start lies within transcript exon
	else if($exon[0]<$cds_start && $cds_start<$exon[1] && $exon[1]<=$cds_stop){
	  $result[]		= array("type"=>"utr","seq"=>substr($sequence,$exon[0]-$to_subtract,$cds_start-$exon[0]),
					"start"=>$exon[0],"stop"=>$cds_start-1);
	  $result[]		= array("type"=>"exon","seq"=>substr($sequence,$cds_start-$to_subtract,$exon[1]-$cds_start+1),
					"start"=>$cds_start,"stop"=>$exon[1]);
	}
	//stop lies within transcript exon
	else if($exon[0]<$cds_stop && $cds_stop<$exon[1] && $cds_start<=$exon[0]){
	  $result[]		= array("type"=>"exon","seq"=>substr($sequence,$exon[0]-$to_subtract,$cds_stop-$exon[0]+1),
					"start"=>$exon[0],"stop"=>$cds_stop);
	  $result[]		= array("type"=>"utr","seq"=>substr($sequence,$cds_stop-$to_subtract+1,$exon[1]-$cds_stop),
					"start"=>$cds_stop+1,"stop"=>$exon[1]);
	}
	//fully spliced behaviour: single exon gene with UTR before and after cds
	else{
	  $result[]		= array("type"=>"utr","seq"=>substr($sequence,$exon[0]-$to_subtract,$cds_start-$exon[0]),
					"start"=>$exon[0],"stop"=>$cds_start-1);
	  $result[]		= array("type"=>"exon","seq"=>substr($sequence,$cds_start-$to_subtract,$cds_stop-$cds_start+1),
					"start"=>$cds_start,"stop"=>$cds_stop);
	  $result[]		= array("type"=>"utr","seq"=>substr($sequence,$cds_stop-$to_subtract+1,$exon[1]-$cds_stop),
					"start"=>$cds_stop+1,"stop"=>$exon[1]);
	}	
	//AT END : ADD INTRON
	if($i!=(count($transcript_exons)-1)){
	  $result[]   		= array("type"=>"intron",
					"seq"=>substr($sequence,$exon[1]-$to_subtract+1,$transcript_exons[$i+1][0]-$exon[1]-1),
					"start"=>$exon[1]+1,"stop"=>$transcript_exons[$i+1][0]-1);
	}		
      }                     	     
    }    
    else if($extraction_type=="exons"){
      //we get the result file of a genomic file here
      $data			= explode("\t",$data[0]);
      $sequence			= $data[5];
      $transcript_exons		= $this->get_exons($annot_data['coord_transcript']);
      $to_subtract		= $transcript_exons[0][0];
      for($i=0;$i<count($transcript_exons);$i++){
	 $exon			= $transcript_exons[$i];
	 $result[]		= array("type"=>"exon","seq"=>substr($sequence,$exon[0]-$to_subtract,$exon[1]-$exon[0]+1),
					"start"=>$exon[0],"stop"=>$exon[1]);	
      }
    }
    else if($extraction_type=="2kbupstream" || $extraction_type=="2kbdownstream" 
		|| $extraction_type=="1kbupstream" || $extraction_type=="1kbdownstream"){
      $data			= explode("\t",$data[0]);
      $result[]			= array("type"=>$extraction_type,"seq"=>$data[5],"start"=>$data[3],"stop"=>$data[4]);
    }    
    //write result to file 
    $fh		= fopen($processed_file,'w');
    foreach($result as $r){	
      fwrite($fh,$r["type"]."\t".$r["seq"]."\t".$r['start']."\t".$r['stop']."\n");
    }
    fclose($fh);
  }



  function read_processed_results($processed_file){    
    $fh			= fopen($processed_file,'r');
    $file_data		= fread($fh,filesize($processed_file));    	
    fclose($fh);
    $data		= explode("\n",$file_data);
    $result		= array();
    foreach($data as $d){
      $split		= explode("\t",$d);
      if(count($split)==4){
	$result[]		= array("type"=>$split[0],"seq"=>$split[1],"start"=>$split[2],"stop"=>$split[3]);
      }
    }
    return $result;
  }
  


  
  function fetch_intergenic_sequence_webservice($webservice_handle,$annotation_db_handle,$gene_list,$extraction_type,
						$blast_dbs,$file_id,$force_fastacmd){
    //create temp file in TMP directory
    $gene_file_name	= TMP."multi_fasta/gene_list_".$file_id."_".$extraction_type.".txt";    
    $result_file_name	= TMP."multi_fasta/fastcmd_".$file_id."_".$extraction_type.".txt";
    $result		= array();
    if(!file_exists($result_file_name) || $force_fastacmd){           
      //pr("execute webservice");
      $data_written = $this->createIntergenicGeneListFile($gene_file_name,$annotation_db_handle,$gene_list,$extraction_type,$blast_dbs);
      if($data_written){
          $ws_data			= array("gene_file_name"=>$gene_file_name,
					"result_file_name"=>$result_file_name,
					"temp_dir"=>TMP."multi_fasta/");
          $ws_result		= $webservice_handle->fetch_sequences_from_blastdb($ws_data);		                                   
      }
      else{
	return $result;
      }
    }
    $result		= $this->parseIntergenicResultFile($result_file_name);
    return $result;
  }

  
  function createIntergenicGeneFileRaw($file_path,$gene_id,$species,$chromosome,$start,$stop,$strand){
    $fh		= fopen($file_path,'w');
    $spec_db	= $this->get_blast_db($species);	
    $dat	= $gene_id."\t".$spec_db."\t".$species."\t".$chromosome."\t".$start."\t".$stop."\t".$strand."\n";
    fwrite($fh,$dat);
    fclose($fh);
  }


  function createIntergenicGeneFile($file_path,$annot_data,$gene_id,$extraction_type){   
    $data_written	= false;
    $fh			= fopen($file_path,'w');
    $spec_db		= $this->get_blast_db($annot_data['species']);
    $chr		= $annot_data['chr'];
    $transcript_exons 	= $this->get_exons($annot_data['coord_transcript']);
    if($extraction_type=="genomic"){    
      $start		= $transcript_exons[0][0];
      $stop		= $transcript_exons[count($transcript_exons)-1][1];
      if($start < $stop){
	$dat = $gene_id."\t".$spec_db."\t".$annot_data['species']."\t".$chr."\t".$start."\t".$stop."\t".$annot_data['strand']."\n";
	fwrite($fh,$dat);
	$data_written	= true;
      }
    }
    else if($extraction_type=="transcript"){
      foreach($transcript_exons as $exon){
	$start	= $exon[0];
	$stop	= $exon[1];
	if($start<$stop){
	  $dat = $gene_id."\t".$spec_db."\t".$annot_data['species']."\t".$chr."\t".$start."\t".$stop."\t".$annot_data['strand']."\n";
	  fwrite($fh,$dat);
	  $data_written = true;
	}
      }	
    }  
    fclose($fh);
    return $data_written;      
  }



  function createIntergenicGeneListFile($file_path,$annotation_db_handle,$gene_list,$extraction_type,$blast_dbs){   
    $data_written	= false;
    //first get all data, later on write to file
    $gene_data		= array();
    foreach($gene_list as $gene_id){
      $annot = $annotation_db_handle->getFullGeneInfo($gene_id);
      if($annot){
       $gene_data[$gene_id] = array("start"=>$annot['start'],"stop"=>$annot['stop'],"species"=>$annot['species'],"chr"=>$annot['chr'],"strand"=>$annot['strand'], "cds_exons"=>$this->get_exons($annot['coord_cds']),"transcript_exons"=>$this->get_exons($annot['coord_transcript']));
      }
    }	     
    
    $fh	= fopen($file_path,'w');    
    //now write to file, according to the extraction_type 
    if($extraction_type=="intron"){
      foreach($gene_data as $gene_id=>$gd){
	$spec_db       	= $blast_dbs[$gd['species']];
	$chr		= $gd['chr'];
	$e		= $gd['transcript_exons'];
	//we have exons, but need the introns.
	for($i=0;$i<(count($e)-1);$i++){
	  $intron_start	= $e[$i][1]+1;
	  $intron_stop	= $e[$i+1][0]-1;
	  if($intron_start < $intron_stop){
	     $dat  = $gene_id."\t".$spec_db."\t".$gd['species']."\t".$chr."\t".$intron_start."\t".$intron_stop."\t".$gd['strand']."\n";
	     fwrite($fh,$dat);
	     $data_written	= true;
	  }
	}
      }
    }
    else if($extraction_type=="upstream_intergenic"){

    }
    else if($extraction_type=="downstream_intergenic"){	

    }    
    fclose($fh);
    return $data_written;	
  }

  



  function parseIntergenicResultFile($file_path){  
    $result		= array();
    $fh 		= fopen($file_path,'r');    
    $file_data		= fread($fh,filesize($file_path));    	
    fclose($fh);
    $data		= explode("\n",$file_data);
    foreach($data as $dat){
      $split		= explode("\t",$dat);
      if(count($split)==6){	
	$gene_id	= $split[0];
	$species	= $split[1];
	$chromosome	= $split[2];
	$start		= $split[3];
	$stop		= $split[4];
	$sequence	= $split[5];
	if(!array_key_exists($gene_id,$result)){$result[$gene_id]=array();}
	$result[$gene_id][]  = array("start"=>$start,"stop"=>$stop,"sequence"=>$sequence);
      }
    }
    return $result;
  }





  function get_exons($coord_string){
    $result	= array();
    if(!$coord_string || $coord_string=="NULL"){return $result;}    
    if(substr($coord_string,0,11)=="complement("){$coord_string=substr($coord_string,11,strlen($coord_string)-12);}	   		   
    if(substr($coord_string,0,5)=="join("){$coord_string=substr($coord_string,5,strlen($coord_string)-6);}
    $split	= explode(",",$coord_string);
    foreach($split as $s){
      $split2	= explode("..",$s);
      if(count($split2)==2){
	$result[] = array($split2[0],$split2[1]);
      }
    }
    return $result;
  }  

  
}


?>
