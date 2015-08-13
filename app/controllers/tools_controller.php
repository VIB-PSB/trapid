<?php
App::import("Sanitize");
/*
 * General controller class for the trapid functionality
 */
class ToolsController extends AppController{
  var $name		= "Tools";
  var $helpers		= array("Html","Form","Javascript","Ajax");

  var $uses		= array("Authentication","Experiments","DataSources","Transcripts","GeneFamilies",
				"TranscriptsGo","TranscriptsInterpro","TranscriptsLabels","ExperimentLog",
				"ExperimentJobs",

				"AnnotSources","Annotation","ExtendedGo","ProteinMotifs","GfData","GoParents",

				"FullTaxonomy"
				);

  var $components	= array("Cookie","TrapidUtils","Statistics");
  var $paginate		= array(
				"Transcripts"=>
				array(
					"limit"=>10,
			       		"order"=>array("Transcripts.transcript_id"=>"ASC")					
				)			
			  );


  /*
   * Function for running framedp on a set of transcripts (from gene family page).
   */
  function framedp($exp_id=null,$gf_id=null,$transcript_id=null){
    //Configure::write("debug",2);
    if(!$exp_id || !$gf_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	 
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);	   
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);

    //if($exp_info['framedp_state']!="finished"){
    //  $this->set("error","framedp_state");
    //}
    
    //check if correct gene family 
    $gf_id	= mysql_real_escape_string($gf_id);
    $gf_info	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));
    if(!$gf_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}

    $transcripts = $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));

    $this->set("transcripts",$transcripts);
    $this->set("gf_id",$gf_id);
    if($transcript_id){	//just for visualization reasons
	    $this->set("selected_transcript_id",$transcript_id);	
    }
    if($_POST){    
      //pr($_POST);
      //return; 
      //select the transcripts
      $selected_transcripts = array();
      foreach($_POST as $k=>$v){	
	$transcript_info = $this->Transcripts->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$k)));
	if($transcript_info){$selected_transcripts[mysql_real_escape_string($k)]=$transcript_info['Transcripts']['transcript_sequence'];}
      }
      //pr($selected_transcripts);return;

      if(count($selected_transcripts)==0){
	$this->set("error","No transcripts selected");
	return;
      }
      $MIN_NUM_SEQUENCES = 10;
      //ok, we need at least a certain amount of sequences (e.g. 10) for the training/evaluation to make any kind of sense
      //so what we do is, we continously add new sequences until we have reached the minimum limit.
      //we can do this through adding random putative non-frameshifted sequences from other gene families
      $extra_transcripts = $this->Transcripts->getRandomTranscriptsFrameDP($exp_id,$gf_id,($MIN_NUM_SEQUENCES-count($selected_transcripts))); 

      //pr($selected_transcripts);pr($extra_transcripts);return;
	
      //create shell_file, also allready write the multi-fasta file containing the sequences
      $qsub_file		= $this->TrapidUtils->create_qsub_script($exp_id);
      $shell_file      		= $this->TrapidUtils->create_shell_script_framedp($exp_id,$exp_info['used_plaza_database'],$gf_id,$selected_transcripts,$extra_transcripts);

      //pr($shell_file);return;
      		
      if($shell_file == null || $qsub_file == null ){$this->set("error","problem creating program files");return;} 
      $tmp_dir	= TMP."experiment_data/".$exp_id."/framedp/".$gf_id."/";
      $qsub_out	= $tmp_dir."framedp_".$exp_id."_".$gf_id.".out";
      $qsub_err	= $tmp_dir."framedp_".$exp_id."_".$gf_id.".err";     
      if(file_exists($qsub_out)){unlink($qsub_out);}
      if(file_exists($qsub_err)){unlink($qsub_err);}
      $command  	= "sh $qsub_file -q medium -o $qsub_out -e $qsub_err $shell_file";
      $output		= array();      
      exec($command,$output);   	           
      $cluster_job	= $this->TrapidUtils->getClusterJobId($output);
      if($cluster_job==null){$this->set("error","Problem with retrieving job identifier from web cluster");return;}     

      $this->ExperimentLog->addAction($exp_id,"framedp",$gf_id);     
      //declare options
      $this->ExperimentLog->addAction($exp_id,"framedp","options",1);
      $this->ExperimentLog->addAction($exp_id,"framedp","selected_transcripts",2);
      foreach($selected_transcripts as $k=>$v){$this->ExperimentLog->addAction($exp_id,"framedp",$k,3);}
      $this->ExperimentLog->addAction($exp_id,"framedp","training_transcripts",2);	
      foreach($extra_transcripts as $k=>$v){$this->ExperimentLog->addAction($exp_id,"framedp",$k,3);}
      $this->ExperimentLog->addAction($exp_id,"framedp_start",$gf_id,1);
      
      //add job to the cluster queue, and then redirect the entire program. 
      //the user will receive an email to notify him when the job is done, together with a link to this page.
      //the result will then automatically be opened.     
      $this->ExperimentJobs->addJob($exp_id,$cluster_job,"short","run_framedp ".$gf_id);

      $this->set("run_pipeline",true);
      return; 
    }    	 	      
  }





  function load_framedp($exp_id=null,$gf_id=null,$job_id=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]); 	
    $this->set("exp_id",$exp_id);
    $this->set("gf_id",$gf_id);
    $this->layout = "";
    if($gf_id==null || $job_id==null){$this->set("error","Incorrect parameters");return;}         
    $cluster_res	= $this->TrapidUtils->waitfor_cluster($exp_id,$job_id,600,15);
    $this->ExperimentLog->addAction($exp_id,"framedp_stop",$gf_id,1);      	
    if(isset($cluster_res["error"])){$this->set("error",$cluster_res["error"]);return;}
    
  }







 function load_msa($exp_id=null,$gf_id=null,$job_id=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]); 	
    $this->set("exp_id",$exp_id);
    $this->layout = "";
    if($gf_id==null || $job_id==null){$this->set("error","Incorrect parameters");return;}         
    $cluster_res	= $this->TrapidUtils->waitfor_cluster($exp_id,$job_id,180,5);
    if(isset($cluster_res["error"])){$this->set("error",$cluster_res["error"]);return;}
    $gf_info	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));   
    if(!$gf_info){$this->set("error","No gene family information found");return;}
    $this->ExperimentLog->addAction($exp_id,"create_msa_stop",$gf_id,1); 

    $this->set("gf_id",$gf_id);
    $this->set("gf_info",$gf_info);    
    $this->set("hashed_user_id",parent::get_hashed_user_id());
  }





 function view_msa($user_identifier=null,$exp_id=null,$gf_id=null,$type="normal",$viewtype="display"){
   //Configure::write("debug",1);
    $this->layout = "";	    	 
    if(!$user_identifier||!$exp_id || !$gf_id){return;}
    $user_identifer = mysql_real_escape_string($user_identifier);
    $exp_id	= mysql_real_escape_string($exp_id);
    if(!parent::check_user_exp_no_cookie($user_identifier,$exp_id)){return;}
    $gf_info	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));   
    if(!$gf_info){return;}
    if(!($type=="normal" || $type=="stripped")){$type="normal";}
    $this->set("gf_id",$gf_id);
    if($type=="normal"){$this->set("msa",$gf_info['GeneFamilies']['msa']);}
    else if($type=="stripped"){$this->set("msa",$gf_info['GeneFamilies']['msa_stripped']);}             
    $this->set("file_name","msa_".$exp_id."_".$gf_id.".faln");
  }



 function create_msa($exp_id=null,$gf_id=null,$stripped=null){
    if(!$exp_id || !$gf_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	 
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);	
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    //check gf_id
    $gf_info	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));   
    if(!$gf_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("gf_id",$gf_id);
    $this->set("gf_info",$gf_info);  

    //check whether the number of jobs in the queue for this experiment has not been reached.
    $current_job_number = $this->ExperimentJobs->getNumJobs($exp_id);
    if($current_job_number>=MAX_CLUSTER_JOBS){$this->redirect(array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id));}

    //get phylogenetic profile, depending on type of GF assignment (HOM/IORTHO)
    $phylo_profile	= array();
    if($exp_info['genefamily_type']=="HOM"){
      $gf_content	= $this->GfData->find("all",array("conditions"=>array("gf_id"=>$gf_info['GeneFamilies']['plaza_gf_id']),"fields"=>"gene_id"));        
      //pr($gf_content);
      $phylo_profile	= $this->Annotation->getSpeciesProfile($this->TrapidUtils->reduceArray($gf_content,"GfData","gene_id"));
    }
    else if($exp_info['genefamily_type']=="IORTHO"){
      $iortho_content	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id),"fields"=>array("gf_content")));    
      $phylo_profile=$this->Annotation->getSpeciesProfile(explode(" ",$iortho_content['GeneFamilies']['gf_content']));
    }
    $this->set("phylo_profile",$phylo_profile);      
    
	
    //retrieve the species from the associated reference database
    $available_species		= $this->AnnotSources->find("all");
    $available_species_tax	= $this->TrapidUtils->indexArrayMulti($available_species,"AnnotSources","tax_id",array("species","common_name"));  
    $available_species_common	= $this->TrapidUtils->indexArrayMulti($available_species,"AnnotSources","common_name",array("species","tax_id"));	
    $available_clades		= $this->FullTaxonomy->findClades(array_keys($available_species_tax));   
    ksort($available_species_common);

    $clades_species_tax	= $available_clades["clade_species_tax"];
    $clades_parental	= $available_clades["parent_child_clades"];
    $full_tree		= $available_clades["full_tree"];
    $this->set("available_species_tax",$available_species_tax);    
    $this->set("available_clades",$clades_species_tax);
    $this->set("parent_child_clades",$clades_parental);
    $this->set("available_species_common",$available_species_common);
    $this->set("full_tree",$full_tree);

    $MAX_GENES_MSA_TREE		= 200;
    $this->set("MAX_GENES",$MAX_GENES_MSA_TREE);

    $editing_modes	= array("0.10"=>"Stringent editing","0.25"=>"Relaxed editing");
    $this->set("editing_modes",$editing_modes);


    // pr($clades_parental);
    //pr($clades_species_tax);
    //pr($full_tree);


    //ok, check whether there is already a multiple sequence alignment present in the database.	
    //if so, get the used-species, and the msa, and display it!  
    if($gf_info['GeneFamilies']['msa']){          
      $this->set("previous_result",true);
      $tax2clades	= array();    
      foreach($available_clades["clade_species_tax"] as $clade=>$tax_list){
	foreach($tax_list as $tax){
	  if(!array_key_exists($tax,$tax2clades)){$tax2clades[$tax]=array();}
	  $tax2clades[$tax][$clade] = $clade;	
	}
      }    
      $selected_species	= array(); 
      $selected_clades	= array();	
      $used_species	= explode(",",$gf_info['GeneFamilies']["used_species"]);
      foreach($used_species as $us){
	$selected_species[$us]	= $us;
	foreach($tax2clades[$us] as $cl){$selected_clades[$cl] = $cl;}
      }         
      $this->set("selected_species",$selected_species);
      $this->set("selected_clades",$selected_clades);
      $this->set("hashed_user_id",parent::get_hashed_user_id());
    }
    if($stripped=="stripped" && $gf_info['GeneFamilies']['msa_stripped']){
      $this->set("stripped_msa",true);      
    }
    else{
      $this->set("stripped_msa",false);
    }

    
    if($_POST){                
      $tmp_dir	= TMP."experiment_data/".$exp_id."/";
      $this->set("previous_result",false);
      $selected_species	= array();
      $selected_clades	= array();
      foreach($_POST as $k=>$v){
	$t	= trim($k);
	if(array_key_exists($t,$available_species_tax)){$selected_species[$t]= $t;}
	else if(array_key_exists($t,$clades_parental)){$selected_clades[$t]= $t;}
      }
      $this->set("selected_species",$selected_species);
      $this->set("selected_clades",$selected_clades);  

      if(count($selected_species)==0){$this->set("error","No genes/species selected");return;}
      //do it here, don't trust user input
      $gene_count	= 0; 
      foreach($selected_species as $ss){$gene_count+=$phylo_profile[$available_species_tax[$ss]['species']];}
      if($gene_count>$MAX_GENES_MSA_TREE || $gene_count==0){$this->set("error","Incorrect number of genes selected");return;}
    
      //check for double gene ids/transcript ids in the input! Important, as this will otherwise crash the strip_msa procedure
      $contains_double_entries	= $this->has_double_entries($exp_id,$gf_info['GeneFamilies'],$selected_species,$exp_info['genefamily_type']);
      if($contains_double_entries){
	$this->set("error","Some transcripts have the same name as genes in the selected species.");return;
      }

      //ok, now write this species information to the database.
      $this->GeneFamilies->updateAll(array("used_species"=>"'".implode(",",$selected_species)."'"),array("experiment_id"=>$exp_id,"gf_id"=>$gf_id));
      //create launch scripts, and put them on the web cluster. The view should show an ajax page which checks every X seconds 
      //whether the job has finished yet.
      $qsub_file		= $this->TrapidUtils->create_qsub_script($exp_id);
      $shell_file      		= $this->TrapidUtils->create_shell_script_msa($exp_id,$exp_info['used_plaza_database'],$gf_id);
      		
      if($shell_file == null || $qsub_file == null ){$this->set("error","problem creating program files");return;} 
      $qsub_out	= $tmp_dir."msa_".$exp_id."_".$gf_id.".out";
      $qsub_err	= $tmp_dir."msa_".$exp_id."_".$gf_id.".err";     
      if(file_exists($qsub_out)){unlink($qsub_out);}
      if(file_exists($qsub_err)){unlink($qsub_err);}
      $command  	= "sh $qsub_file -q short -o $qsub_out -e $qsub_err $shell_file";
      $output		= array();      
      exec($command,$output);   	           
      $cluster_job	= $this->TrapidUtils->getClusterJobId($output);

      //add job to the cluster queue, and then redirect the entire program. 
      //the user will receive an email to notify him when the job is done, together with a link to this page.
      //the result will then automatically be opened.     
      $this->ExperimentJobs->addJob($exp_id,$cluster_job,"short","create_msa ".$gf_id);
      
      //if($cluster_job==null){$this->set("error","Problem with retrieving job identifier from web cluster");return;}
      //$this->set("job_id",$cluster_job); 
      //$this->set("run_pipeline",true);
      $this->ExperimentLog->addAction($exp_id,"create_msa",$gf_id);  
      //declare options
      $this->ExperimentLog->addAction($exp_id,"create_msa","options",1);
      $this->ExperimentLog->addAction($exp_id,"create_msa","gene_family=".$gf_id,2);
      $this->ExperimentLog->addAction($exp_id,"create_msa","selected_species",2);
      foreach($selected_species as $ss){$this->ExperimentLog->addAction($exp_id,"create_msa",$ss,3);}
      $this->ExperimentLog->addAction($exp_id,"create_msa_start",$gf_id,1);
      $this->set("run_pipeline",true);
      return; 
    }
  }


  function has_double_entries($exp_id,$gf_info,$selected_species,$gf_type){
    //step 1: get the transcript ids which are in the experiment and in the gene family.
    $transcripts	= $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_info['gf_id']),"fields"=>array("transcript_id")));

    $transcripts	= $this->TrapidUtils->reduceArray($transcripts,"Transcripts","transcript_id");
       
    //step 2: get the genes from the selected species and gene family
    $gene_ids		= array();
    if($gf_type=="HOM"){
      $species		= $this->AnnotSources->getSpeciesFromTaxIds(array_values($selected_species));      
      $gene_ids_gf	= $this->GfData->getGenes($gf_info['plaza_gf_id']);
      $gene_id_data	= $this->Annotation->getSpeciesForGenes($gene_ids_gf);     
      foreach($gene_id_data as $spec=>$spec_genes){       
	if(array_key_exists($spec,$species)){$gene_ids = array_merge($gene_ids,$spec_genes);}
      }     
    }
    else{
      $gene_ids	= explode(" ",$gf_info['gf_content']);
    }
    Configure::write("debug",1);
    //$shared_identifiers = array_intersect($transcripts,$gene_ids);
    //pr($shared_identifiers);

    $count_transcripts	= count($transcripts);
    $count_genes	= count($gene_ids);
    $count_merged	= count(array_unique(array_merge($transcripts,$gene_ids)));
    //pr($transcripts);
    //pr($count_transcripts."\t". count(array_unique($transcripts)));
    //pr($count_genes);
    //pr($count_merged);
    if($count_merged==($count_transcripts+$count_genes)){
      	return false;
    }
    else{
    	return true;
    }
  }



 function load_tree($exp_id=null,$gf_id=null,$job_id=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]); 	
    $this->set("exp_id",$exp_id);
    $this->layout = "";
    if($gf_id==null || $job_id==null){$this->set("error","Incorrect parameters");return;}         
    $cluster_res	= $this->TrapidUtils->waitfor_cluster($exp_id,$job_id,600,5);
    if(isset($cluster_res["error"])){$this->set("error",$cluster_res["error"]);return;}
    $gf_info	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));   
    if(!$gf_info){$this->set("error","No gene family information found");return;}
    $this->set("gf_id",$gf_id);
    $this->set("gf_info",$gf_info);    
    $this->set("hashed_user_id",parent::get_hashed_user_id());

    $this->ExperimentLog->addAction($exp_id,"create_tree_stop",$gf_id,1); 
    $atv_config_file	= TMP_WEB."experiment_data/".$exp_id."/atv_config.cfg";
    $this->set("atv_config_file",$atv_config_file);
    $subset_colors	= $this->TrapidUtils->getSubsetColorsATVConfig(TMP."experiment_data/".$exp_id."/atv_config.cfg");
    $this->set("subset_colors",$subset_colors);
  }
  


 function view_tree($user_identifier=null,$exp_id=null,$gf_id=null,$format="xml"){
    $this->layout 	= "";	    	 
    if(!$user_identifier||!$exp_id || !$gf_id){return;}
    $user_identifer 	= mysql_real_escape_string($user_identifier);
    $exp_id		= mysql_real_escape_string($exp_id);
    if(!parent::check_user_exp_no_cookie($user_identifier,$exp_id)){return;}
    $gf_info		= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));   
    if(!$gf_info){return;} 
    $this->set("gf_id",$gf_id);
    if($format=="newick"){
	$this->set("tree",$gf_info['GeneFamilies']['tree']);
	$this->set("file_name","tree_".$exp_id."_".$gf_id.".newick");
    }
    else if($format=="xml"){
	$this->set("tree",$gf_info['GeneFamilies']['xml_tree']);  
	$this->set("file_name","tree_".$exp_id."_".$gf_id.".xml");
    }
    //fallback on xml
    else{
	$this->set("tree",$gf_info['GeneFamilies']['xml_tree']);  
	$this->set("file_name","tree_".$exp_id."_".$gf_id.".xml");
    }
  
  }




  function create_tree($exp_id=null,$gf_id=null){
    if(!$exp_id || !$gf_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	 
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);	
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    //check gf_id
    $gf_info	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));   
    if(!$gf_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("gf_id",$gf_id);
    $this->set("gf_info",$gf_info);  

    //check whether the number of jobs in the queue for this experiment has not been reached.
    $current_job_number = $this->ExperimentJobs->getNumJobs($exp_id);
    if($current_job_number>=MAX_CLUSTER_JOBS){$this->redirect(array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id));}

    //get phylogenetic profile, depending on type of GF assignment (HOM/IORTHO)
    $phylo_profile	= array();
    if($exp_info['genefamily_type']=="HOM"){
      $gf_content	= $this->GfData->find("all",array("conditions"=>array("gf_id"=>$gf_info['GeneFamilies']['plaza_gf_id']),"fields"=>"gene_id"));        
      $phylo_profile	= $this->Annotation->getSpeciesProfile($this->TrapidUtils->reduceArray($gf_content,"GfData","gene_id"));
      // pr($gf_content);
    }
    else if($exp_info['genefamily_type']=="IORTHO"){
      $iortho_content	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id),"fields"=>array("gf_content")));    
      $phylo_profile=$this->Annotation->getSpeciesProfile(explode(" ",$iortho_content['GeneFamilies']['gf_content']));
    }
    $this->set("phylo_profile",$phylo_profile);      
	
    //get number of transcripts which are partial
    $num_partial_transcripts = $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id,"meta_annotation"=>"Partial")));
    $this->set("num_partial_transcripts",$num_partial_transcripts);

    //get all transcripts, together with their meta annotation
    $gf_transcripts	= $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id),"fields"=>array("transcript_id","meta_annotation")));
    $gf_transcripts	= $this->TrapidUtils->indexArraySimple($gf_transcripts,"Transcripts","transcript_id","meta_annotation");
    $this->set("gf_transcripts",$gf_transcripts);

    //retrieve the species from the associated reference database
    $available_species		= $this->AnnotSources->find("all");
    $available_species_tax	= $this->TrapidUtils->indexArrayMulti($available_species,"AnnotSources","tax_id",array("species","common_name"));  
    $available_species_common	= $this->TrapidUtils->indexArrayMulti($available_species,"AnnotSources","common_name",array("species","tax_id"));	
    $available_species_species	= $this->TrapidUtils->indexArrayMulti($available_species,"AnnotSources","species",array("common_name","tax_id"));
    $available_clades		= $this->FullTaxonomy->findClades(array_keys($available_species_tax));   
    ksort($available_species_common);  

    $clades_species_tax	= $available_clades["clade_species_tax"];
    $clades_parental	= $available_clades["parent_child_clades"];
    $full_tree		= $available_clades["full_tree"];	
    $this->set("available_species_tax",$available_species_tax);
    $this->set("available_species_species",$available_species_species);
    $this->set("available_clades",$clades_species_tax);
    $this->set("parent_child_clades",$clades_parental);
    $this->set("available_species_common",$available_species_common);
    $this->set("full_tree",$full_tree);


    $MAX_GENES_MSA_TREE		= 200;
    $this->set("MAX_GENES",$MAX_GENES_MSA_TREE);

    $tree_programs	= array("fasttree"=>"FastTree","phyml"=>"PhyML");
    $this->set("tree_programs",$tree_programs);
    $tree_program	= "fasttree";
    $this->set("tree_program",$tree_program);

    $editing_modes	= array("0.10"=>"Stringent editing","0.25"=>"Relaxed editing");
    $this->set("editing_modes",$editing_modes);
    $editing_mode	= "0.25";
    $this->set("editing_mode",$editing_mode);

    $bootstrap_modes	= array("1"=>"1","100"=>"100","500"=>"500");
    $this->set("bootstrap_modes",$bootstrap_modes);
    $bootstrap_mode	= "100";
    $this->set("bootstrap_mode",$bootstrap_mode);
    //$optimization_modes	= array("n"=>"No optimization (fast)","tl"=>"Tree topology and branch lengths optimized (slow)");
    //$optimization_modes	= array("n"=>"No optimization (fast)");	
    //$this->set("optimization_modes",$optimization_modes);
    //$optimization_mode	= "n";
    //$this->set("optimization_mode",$optimization_mode);
    $include_subsets	= false;
    $include_meta	= true;
    $this->set("include_subsets",$include_subsets);    

    //ok, check whether there is already a multiple sequence alignment present in the database.	
    //if so, get the used-species, and the msa, and display it!     
    if($gf_info['GeneFamilies']['tree']){          
      $this->set("previous_result",true);
      $tax2clades	= array();    
      foreach($available_clades["clade_species_tax"] as $clade=>$tax_list){
	foreach($tax_list as $tax){
	  if(!array_key_exists($tax,$tax2clades)){$tax2clades[$tax]=array();}
	  $tax2clades[$tax][$clade] = $clade;	
	}
      }    
      $selected_species	= array(); 
      $selected_clades	= array();	
      $used_species	= explode(",",$gf_info['GeneFamilies']["used_species"]);
      foreach($used_species as $us){
	$selected_species[$us]	= $us;
	foreach($tax2clades[$us] as $cl){$selected_clades[$cl] = $cl;}
      }         
      $this->set("selected_species",$selected_species);
      $this->set("selected_clades",$selected_clades);
      $this->set("hashed_user_id",parent::get_hashed_user_id());      
            
      $subset_colors	= $this->TrapidUtils->getSubsetColorsATVConfig(TMP."experiment_data/".$exp_id."/atv_config.cfg");
      $this->set("subset_colors",$subset_colors);
	
      //$meta_colors	= array("Full Length"=>"","Quasi Full Length"=>"","Partial"=>"","No Information"=>"");
      //$this->set("meta_colors",$meta_colors);

      //Configure::write("debug",1);
      $this->set("full_msa_length",$this->TrapidUtils->getMsaLength($gf_info["GeneFamilies"]["msa"]));
      $this->set("stripped_msa_length",$this->TrapidUtils->getMsaLength($gf_info["GeneFamilies"]["msa_stripped"]));         
    }

    
    if($_POST){     
      //Configure::write("debug",2);
      //pr($_POST);       
             	  
      $tmp_dir	= TMP."experiment_data/".$exp_id."/";
      $this->set("previous_result",false);
      $selected_species	= array();
      $selected_clades	= array();
      $exclude_transcripts = array();
      foreach($_POST as $k=>$v){
	$t	= trim($k);
	if(array_key_exists($t,$available_species_tax)){$selected_species[$t]= $t;}
	else if(array_key_exists($t,$clades_parental)){$selected_clades[$t]= $t;}
	else if(strlen($t)>8 && substr($t,0,8)=="exclude_"){
	  $putative_transcript	= substr($t,8);
	  //pr($putative_transcript);
	  if(array_key_exists($putative_transcript,$gf_transcripts)){$exclude_transcripts[]=$putative_transcript;}
	}	
      }
      $this->set("selected_species",$selected_species);
      $this->set("selected_clades",$selected_clades);        
      
      // pr($exclude_transcripts);
      //pr($selected_species);
      //pr($selected_clades);
      //return;     


      if(!array_key_exists("tree_program",$_POST)){$this->set("error","No tree algorithm defined");return;}
      if(array_key_exists($_POST['tree_program'],$tree_programs)){$tree_program = $_POST['tree_program'];}
      $this->set("tree_program",$tree_program);

      //select editing mode for MSA
      if(!array_key_exists("editing_mode",$_POST)){$this->set("error","No editing mode defined");return;}   
      if(array_key_exists($_POST["editing_mode"],$editing_modes)){$editing_mode = $_POST['editing_mode'];}
      $this->set("editing_mode",$editing_mode);

      //select bootstrapping for treebuilding
      if(!array_key_exists("bootstrap_mode",$_POST)){$this->set("error","No bootstrap mode defined");return;}     
      if(array_key_exists($_POST["bootstrap_mode"],$bootstrap_modes)){$bootstrap_mode = $_POST['bootstrap_mode'];}
      $this->set("bootstrap_mode",$bootstrap_mode);
     
      //select subset presence
      if(array_key_exists('include_extra',$_POST)){
	if($_POST['include_extra'] == "subsets"){$include_subsets=true;$include_meta=false;}
	if($_POST['include_extra'] == "meta"){$include_subsets=false;$include_meta=true;}
      }
      $this->set("include_subsets",$include_subsets);

      if(count($selected_species)==0){$this->set("error","No genes/species selected");return;}
      //do count of genes here, don't trust user input
      $gene_count	= 0; 
      foreach($selected_species as $ss){$gene_count+=$phylo_profile[$available_species_tax[$ss]['species']];}
      if($gene_count>$MAX_GENES_MSA_TREE || $gene_count==0){$this->set("error","Incorrect number of genes selected");return;}

      //check for double gene ids/transcript ids in the input! Important, as this will otherwise crash the strip_msa procedure
      $contains_double_entries	= $this->has_double_entries($exp_id,$gf_info['GeneFamilies'],$selected_species,$exp_info['genefamily_type']);
      if($contains_double_entries){
	$this->set("error","Some transcripts have the same name as genes in the selected species.");return;
      }
   
      //ok, now write this species information to the database.
      $this->GeneFamilies->updateAll(array("used_species"=>"'".implode(",",$selected_species)."'","exclude_transcripts"=>"'".implode(",",$exclude_transcripts)."'"),array("experiment_id"=>$exp_id,"gf_id"=>$gf_id));
      
      //create launch scripts, and put them on the web cluster. The view should show an ajax page which checks every X seconds 
      //whether the job has finished yet.
      $qsub_file		= $this->TrapidUtils->create_qsub_script($exp_id);
      /* $shell_file      		= $this->TrapidUtils->create_shell_script_tree($exp_id,$exp_info['used_plaza_database'],$gf_id,
									       $editing_mode,$bootstrap_mode,$optimization_mode,
									       $include_subsets);*/
      $shell_file      		= $this->TrapidUtils->create_shell_script_tree($exp_id,$exp_info['used_plaza_database'],$gf_id,
									       $editing_mode,$bootstrap_mode,$tree_program,
									       $include_subsets,$include_meta);
      		
      if($shell_file == null || $qsub_file == null ){$this->set("error","problem creating program files");return;} 
      $qsub_out	= $tmp_dir."tree_".$exp_id."_".$gf_id.".out";
      $qsub_err	= $tmp_dir."tree_".$exp_id."_".$gf_id.".err";     
      if(file_exists($qsub_out)){unlink($qsub_out);}
      if(file_exists($qsub_err)){unlink($qsub_err);}
      $command  	= "sh $qsub_file -q short -o $qsub_out -e $qsub_err $shell_file";
      $output		= array();      
      exec($command,$output);   	           
      $cluster_job	= $this->TrapidUtils->getClusterJobId($output);

	
      //add job to the cluster queue, and then redirect the entire program. 
      //the user will receive an email to notify him when the job is done, together with a link to this page.
      //the result will then automatically be opened.     
      $this->ExperimentJobs->addJob($exp_id,$cluster_job,"short","create_tree ".$gf_id);

      $this->ExperimentLog->addAction($exp_id,"create_tree",$gf_id);  
      //declare options in the log
      $this->ExperimentLog->addAction($exp_id,"create_tree","options",1);
      $this->ExperimentLog->addAction($exp_id,"create_tree","gene_family=".$gf_id,2);
      $this->ExperimentLog->addAction($exp_id,"create_tree","selected_species",2);
      foreach($selected_species as $ss){$this->ExperimentLog->addAction($exp_id,"create_tree",$ss,3);}
      $this->ExperimentLog->addAction($exp_id,"create_tree","algorithm=".$tree_program,2);
      $this->ExperimentLog->addAction($exp_id,"create_tree","editing=".$editing_mode,2);
      $this->ExperimentLog->addAction($exp_id,"create_tree","bootstrap=".$bootstrap_mode,2);
      //$this->ExperimentLog->addAction($exp_id,"create_tree","parameter_opt=".$optimization_mode,2);
      $this->ExperimentLog->addAction($exp_id,"create_tree","incl_subsets=".$include_subsets,2);
      $this->ExperimentLog->addAction($exp_id,"create_tree_start",$gf_id,1);
      $this->set("run_pipeline",true);
      return; 
	
      /*	
      if($cluster_job==null){$this->set("error","Problem with retrieving job identifier from web cluster");return;}
      $this->set("job_id",$cluster_job);	     
      $this->set("run_pipeline",true);
      $this->ExperimentLog->addAction($exp_id,"create_tree",$gf_id);  
      $this->ExperimentLog->addAction($exp_id,"create_tree_start",$gf_id,1); 
      return;	             	         
      */
    }    
  }





  function compare_ratios_chart($exp_id=null,$type=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]); 
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $possible_types	= array("go"=>"GO","ipr"=>"Protein domain");
    //check type
    $type		= mysql_real_escape_string($type);
    if(!array_key_exists($type,$possible_types)){$this->redirect("/");}
    $this->set("available_types",$possible_types);
    $this->set("type",$type);

    $subsets	= $this->TranscriptsLabels->getLabels($exp_id);   
    if(count($subsets) <= 1){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("subsets",$subsets);

    if($type=="go"){
	$possible_go_types	= array("BP"=>"Biological Process","MF"=>"Molecular Function","CC"=>"Cellular Component");	
	$this->set("possible_go_types",$possible_go_types);
	$possible_depths	= $this->ExtendedGo->getDepthsPerCategory();	
	$max_depth		= 0; foreach($possible_depths as $d){if($d>$max_depth){$max_depth=$d;}}
	$this->set("max_go_depth",$max_depth);	   

	if($_POST){
      
	    if(!(array_key_exists("subset1",$_POST) && array_key_exists("subset2",$_POST) && 
		array_key_exists("go_category",$_POST) && array_key_exists("go_depth",$_POST) )){
	       	$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));     
	    }
	    $subset1	= mysql_real_escape_string($_POST['subset1']);
            $subset2	= mysql_real_escape_string($_POST['subset2']);	     
            if($subset1==$subset2){$this->set("error","Subset 1 should not be equal to Subset 2");return;}
	    if(!(array_key_exists($subset1,$subsets) && array_key_exists($subset2,$subsets))){
		$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));   
	    }
	    $this->set("subset1",$subset1);
      	    $this->set("subset2",$subset2);

      	    $go_category	= mysql_real_escape_string($_POST['go_category']);
	    $go_depth		= mysql_real_escape_string($_POST['go_depth']);
	    if(!(array_key_exists($go_category,$possible_go_types) && $go_depth>0 && $go_depth<=$max_depth)){
		$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id)); 
	    }	
	    $this->set("go_category",$go_category);
	    $this->set("go_depth",$go_depth);

	    $min_coverage	= null;
	    if(array_key_exists("min_coverage",$_POST)){$min_coverage=mysql_real_escape_string($_POST['min_coverage']);}	    
	    if($min_coverage){$this->set("min_coverage",$min_coverage);}
	    if($min_coverage=="none"){$min_coverage=false;}
		
	    $both_subsets_present	= false;
	    if(array_key_exists("both_present",$_POST)){$both_subsets_present=true; $this->set("both_present",true);}	
	    
	    //select the transcripts
	    $subset1_transcripts	= $this->TranscriptsLabels->find("all",array("conditions"=>
						array("experiment_id"=>$exp_id,"label"=>$subset1),"fields"=>array("transcript_id")));	    
      	    $subset2_transcripts	= $this->TranscriptsLabels->find("all",array("conditions"=>
						array("experiment_id"=>$exp_id,"label"=>$subset2),"fields"=>array("transcript_id")));
      	    $subset1_transcripts = $this->TrapidUtils->reduceArray($subset1_transcripts,"TranscriptsLabels","transcript_id");
      	    $subset2_transcripts = $this->TrapidUtils->reduceArray($subset2_transcripts,"TranscriptsLabels","transcript_id");
            $this->set("subset1_size",count($subset1_transcripts));
            $this->set("subset2_size",count($subset2_transcripts));

	    //pr(count($subset1_transcripts));
	    //pr(count($subset2_transcripts));
			  
	    //ok, now select all the GOs from extended go which adhere to the given category and depth
	    $go_ids	= $this->ExtendedGo->find("all",array("conditions"=>array("type"=>$go_category,"num_sptr_steps"=>$go_depth,"is_obsolete"=>"0"),"fields"=>array("go","desc")));
	    if(count($go_ids)==0){$this->set("error","No GO terms match with the given parameters");return;}
	    $go_ids	= $this->TrapidUtils->indexArraySimple($go_ids,"ExtendedGo","go","desc");	    
	    $data_all	= $this->TranscriptsGo->findTranscriptCountsFromGos($exp_id,array_keys($go_ids));
	    $data_sub1	= $this->TranscriptsGo->findTranscriptCounts($exp_id,array_keys($go_ids),$subset1_transcripts);
	    $data_sub2	= $this->TranscriptsGo->findTranscriptCounts($exp_id,array_keys($go_ids),$subset2_transcripts);
	    
	  
	    //making JSON data for the 
	    
	    $all_count	= $exp_info['transcript_count'];
	    $sub1_count	= $subsets[$subset1];
	    $sub2_count	= $subsets[$subset2];
	    $selected_gos = array();
	   		   
	    $result	= array("vars"=>array(),"smps"=>array("All",$subset1,$subset2),"desc"=>array("Ratios"),"data"=>array());
	    $counter	= 0;
	    foreach($data_all as $k=>$v){
	      if(array_key_exists($k,$data_sub1) || array_key_exists($k,$data_sub2)){		       
		$all_ratio	= number_format($v*100.0/$all_count,2);
		$sub1_ratio	= 0; 
		$sub2_ratio	= 0;
		if(array_key_exists($k,$data_sub1)){		 
		  $sub1_ratio	= number_format($data_sub1[$k]*100.0/$sub1_count,2);
		}
		if(array_key_exists($k,$data_sub2)){
		  $sub2_ratio	= number_format($data_sub2[$k]*100.0/$sub2_count,2);
		}
		if($both_subsets_present && ($sub1_count==0 || $sub2_count==0)){} //do nothing, not present in both 
		else{		 		  
		  if($min_coverage && ($sub1_ratio<$min_coverage || $sub2_ratio<$min_coverage)){} //do nothing, min ratio not reached 
		  else{		
		    $result["vars"][]	= $k." ".$go_ids[$k];		  
		    $result["data"][] = array($all_ratio,$sub1_ratio,$sub2_ratio);
		    $counter++;
		  }
		}
	      }	
	    }
	    $this->set("num_selected_gos",$counter);
	    $this->set("result",$result);	  	    	    			
      }		

    }

		
  }





  /*
   * Display ratios between GO or protein domains
   */
  function compare_ratios($exp_id=null,$type=null){
    // Configure::write("debug",2);
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]); 
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $possible_types	= array("go"=>"GO","ipr"=>"Protein domain");
    //check type
    $type		= mysql_real_escape_string($type);
    if(!array_key_exists($type,$possible_types)){$this->redirect("/");}
    $this->set("available_types",$possible_types);
    $this->set("type",$type);

    $subsets	= $this->TranscriptsLabels->getLabels($exp_id);
    if(count($subsets) <= 1){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("subsets",$subsets);	
    $this->set("hashed_user_id",parent::get_hashed_user_id());

    if($_POST){     
      if(!(array_key_exists("subset1",$_POST) && array_key_exists("subset2",$_POST))){	
	$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));     
      }
      $subset1	= mysql_real_escape_string($_POST['subset1']);
      $subset2	= mysql_real_escape_string($_POST['subset2']);
      if($subset1==$subset2){
	$this->set("error","Subset 1 should not be equal to Subset 2");return;
      }
      if(!(array_key_exists($subset1,$subsets) && array_key_exists($subset2,$subsets))){
	$this->redirect(array("controller"=>"tools","action"=>"go_ratios",$exp_id));     
      }
      $this->set("subset1",$subset1);
      $this->set("subset2",$subset2);
      $subset1_transcripts	= $this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"label"=>$subset1),"fields"=>array("transcript_id")));		
      $subset2_transcripts	= $this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"label"=>$subset2),"fields"=>array("transcript_id")));
      $subset1_transcripts = $this->TrapidUtils->reduceArray($subset1_transcripts,"TranscriptsLabels","transcript_id");
      $subset2_transcripts = $this->TrapidUtils->reduceArray($subset2_transcripts,"TranscriptsLabels","transcript_id");
      $this->set("subset1_size",count($subset1_transcripts));
      $this->set("subset2_size",count($subset2_transcripts));

      if($type=="go"){
      	$subset1_go_counts  	= $this->TranscriptsGo->findGoCountsFromTranscripts($exp_id,$subset1_transcripts);	
	$subset2_go_counts	= $this->TranscriptsGo->findGoCountsFromTranscripts($exp_id,$subset2_transcripts);
	$go_ids			= array_unique(array_merge(array_keys($subset1_go_counts),array_keys($subset2_go_counts)));	
	$go_descriptions	= $this->ExtendedGo->find("all",array("conditions"=>array("go"=>$go_ids)));
	$go_types		= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","go","type");
	$go_descriptions	= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","go","desc");
	$type_descriptions	= array("BP"=>"Biological Process","CC"=>"Cellular Component","MF"=>"Molecular Function");
	$this->set("data_subset1",$subset1_go_counts);
	$this->set("data_subset2",$subset2_go_counts);
	$this->set("descriptions",$go_descriptions);
	$this->set("go_types",$go_types);       
	$this->set("type_desc",$type_descriptions);
      }
      else if($type=="ipr"){
	$subset1_ipr_counts	= $this->TranscriptsInterpro->findInterproCountsFromTranscripts($exp_id,$subset1_transcripts);
	$subset2_ipr_counts	= $this->TranscriptsInterpro->findInterproCountsFromTranscripts($exp_id,$subset2_transcripts);
	$ipr_ids		= array_unique(array_merge(array_keys($subset1_ipr_counts),array_keys($subset2_ipr_counts)));
	$ipr_descriptions	= $this->ProteinMotifs->find("all",array("conditions"=>array("motif_id"=>$ipr_ids)));
	$ipr_descriptions	= $this->TrapidUtils->indexArraySimple($ipr_descriptions,"ProteinMotifs","motif_id","desc");
	$this->set("data_subset1",$subset1_ipr_counts);
	$this->set("data_subset2",$subset2_ipr_counts);
	$this->set("descriptions",$ipr_descriptions);	
      }
    }	
  }


  function compare_ratios_download($exp_id=null,$type=null,$comparison=null,$subset1=null,$subset2=null,$subtype=null){
    // Configure::write("debug",2);
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]); 
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $possible_types	= array("go"=>"GO","ipr"=>"Protein domain");
    //check type
    $type		= mysql_real_escape_string($type);
    if(!array_key_exists($type,$possible_types)){$this->redirect("/");}    
    $this->set("type",$type);

    $subsets	= $this->TranscriptsLabels->getLabels($exp_id);
    if(count($subsets) <= 1){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("subsets",$subsets);

    $comparison		= mysql_real_escape_string($comparison);
    $subset1		= mysql_real_escape_string($subset1);
    $subset2		= mysql_real_escape_string($subset2);
    $comparison_types	= array("1"=>"both","2"=>"first","3"=>"last");    
    if(!($comparison==1 || $comparison==2 || $comparison==3)){
	$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
    }
    if(!(array_key_exists($subset1,$subsets) && array_key_exists($subset2,$subsets))){
	$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
    }
    if($subset1==$subset2){
	$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
    }
    if($type=="go"){
      $subtype		= mysql_real_escape_string($subtype);
      if(!($subtype=="MF" || $subtype=="BP" || $subtype=="CC")){
	$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
      }
    }
    $this->set("comparison",$comparison);
    $this->set("subset1",$subset1);
    $this->set("subset2",$subset2);
    $this->set("subtype",$subtype); 
          
    $subset1_transcripts	= $this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"label"=>$subset1),"fields"=>array("transcript_id")));		
    $subset2_transcripts	= $this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"label"=>$subset2),"fields"=>array("transcript_id")));
    $subset1_transcripts = $this->TrapidUtils->reduceArray($subset1_transcripts,"TranscriptsLabels","transcript_id");
    $subset2_transcripts = $this->TrapidUtils->reduceArray($subset2_transcripts,"TranscriptsLabels","transcript_id");

    $this->set("subset1_size",count($subset1_transcripts));
    $this->set("subset2_size",count($subset2_transcripts));

    if($type=="go"){
        $this->layout="";
      	$subset1_go_counts  	= $this->TranscriptsGo->findGoCountsFromTranscripts($exp_id,$subset1_transcripts);	
	$subset2_go_counts	= $this->TranscriptsGo->findGoCountsFromTranscripts($exp_id,$subset2_transcripts);
	$go_ids			= array_unique(array_merge(array_keys($subset1_go_counts),array_keys($subset2_go_counts)));	
	$go_descriptions	= $this->ExtendedGo->find("all",array("conditions"=>array("go"=>$go_ids)));
	$go_types		= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","go","type");
	$go_descriptions	= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","go","desc");
	//$type_descriptions	= array("BP"=>"Biological Process","CC"=>"Cellular Component","MF"=>"Molecular Function");
	$this->set("data_subset1",$subset1_go_counts);
	$this->set("data_subset2",$subset2_go_counts);
	$this->set("descriptions",$go_descriptions);
	$this->set("go_types",$go_types);
	$this->set("file_name","compare_ratios_".$exp_id."_".$type."_".$subtype."_".$subset1."_".$subset2."_".$comparison_types[$comparison].".txt");       
	//$this->set("type_desc",$type_descriptions);
    }
    else if($type=="ipr"){
        $this->layout="";
	$subset1_ipr_counts	= $this->TranscriptsInterpro->findInterproCountsFromTranscripts($exp_id,$subset1_transcripts);
	$subset2_ipr_counts	= $this->TranscriptsInterpro->findInterproCountsFromTranscripts($exp_id,$subset2_transcripts);
	$ipr_ids		= array_unique(array_merge(array_keys($subset1_ipr_counts),array_keys($subset2_ipr_counts)));
	$ipr_descriptions	= $this->ProteinMotifs->find("all",array("conditions"=>array("motif_id"=>$ipr_ids)));
	$ipr_descriptions	= $this->TrapidUtils->indexArraySimple($ipr_descriptions,"ProteinMotifs","motif_id","desc");
	$this->set("data_subset1",$subset1_ipr_counts);
	$this->set("data_subset2",$subset2_ipr_counts);
	$this->set("descriptions",$ipr_descriptions);
	$this->set("file_name","compare_ratios_".$exp_id."_".$type."_".$subset1."_".$subset2."_".$comparison_types[$comparison].".txt"); 
    }
   	
  }




  function enrichment($exp_id=null,$type=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]); 
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $possible_types	= array("go"=>"GO","ipr"=>"Protein domain");
    //check type
    $type		= mysql_real_escape_string($type);
    if(!array_key_exists($type,$possible_types)){$this->redirect("/");}
    $this->set("available_types",$possible_types);
    $this->set("type",$type);

    //check whether the number of jobs in the queue for this experiment has not been reached.
    $current_job_number = $this->ExperimentJobs->getNumJobs($exp_id);
    if($current_job_number>=MAX_CLUSTER_JOBS){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}

    //get subsets for this experiment.
    $subsets	= $this->TranscriptsLabels->getLabels($exp_id);
    if(count($subsets)==0){$this->set("error","No subsets defined");return;}
    $this->set("subsets",$subsets);
   
    //possible p-values
    $possible_pvalues	= array("0.1","0.05","0.01","0.005","0.001","0.0001","0.00001");
    $selected_pvalue	= 0.05;
    $this->set("possible_pvalues",$possible_pvalues);
    $this->set("selected_pvalue",$selected_pvalue);

    //see if the user posted form
    if($_POST){
      //check for present subset
      if(!array_key_exists("subset",$_POST)){$this->set("error","No subset indicated in form");return;}
      $subset		= mysql_real_escape_string($_POST['subset']);
      if(!array_key_exists($subset,$subsets)){$this->set("error","Illegal subset");return;}	  
      $this->set("selected_subset",$subset);

      if(array_key_exists("pvalue",$_POST)){
	$pvalue	= mysql_real_escape_string($_POST['pvalue']);
	if(in_array($pvalue,$possible_pvalues)){$selected_pvalue=$pvalue;}
	$this->set("selected_pvalue",$selected_pvalue);
      }
        
      //file locations
      $tmp_dir		= TMP."experiment_data/".$exp_id."/";
      $result_file 	= $type."_enrichment_".$exp_id."_".$subset."_".$selected_pvalue.".txt";
      $all_fa_file	= $type."_transcript_".$exp_id."_all.txt";
      $subset_fa_file	= $type."_transcript_".$exp_id."_".$subset.".txt";
      $result_file_path	= $tmp_dir."".$result_file;
      $all_fa_file_path	= $tmp_dir."".$all_fa_file;
      $subset_fa_file_path	= $tmp_dir."".$subset_fa_file;
     
      if(!array_key_exists("use_cache",$_POST)){	//force recomputation : delete result
	if(file_exists($result_file_path)){unlink($result_file_path);}	
      }
	

      //if force computation or result does not exist: perform go enrichment computation
      if(!file_exists($result_file_path)){
	//create shell file which contains necessary java programs
	$qsub_file  = $this->TrapidUtils->create_qsub_script($exp_id);     
        $shell_file = $this->TrapidUtils->create_shell_file_enrichment($exp_id,$type,$exp_info['used_plaza_database'],$all_fa_file_path,$subset_fa_file_path,$result_file_path,$subset,$selected_pvalue);	     	
	if($shell_file == null || $qsub_file == null ){$this->set("error","problem creating program files");return;} 
	$qsub_out	= $tmp_dir.$type."_enrichment_".$subset.".out";
      	$qsub_err	= $tmp_dir.$type."_enrichment_".$subset.".err";     
      	if(file_exists($qsub_out)){unlink($qsub_out);}
      	if(file_exists($qsub_err)){unlink($qsub_err);}
	$command  	= "sh $qsub_file -q short -o $qsub_out -e $qsub_err $shell_file";
	$output		= array();      
        exec($command,$output);   	           
	$cluster_job	= $this->TrapidUtils->getClusterJobId($output);
	if($cluster_job==null){$this->set("error","Problem with retrieving job identifier from web cluster");return;}
	$this->set("job_id",$cluster_job);	
	$this->set("result_file",$result_file);       
	return;
      }	
      else{
	//Configure::write("debug",1);
	//	pr($result_file);
	$this->set("result_file",$result_file);	
	return;
      }
    }     
  }






  function go_enrichment_graph($exp_id=null,$selected_subset=null,$go_type=null,$pvalue=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]); 
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    //get subsets for this experiment.
    $subsets	= $this->TranscriptsLabels->getLabels($exp_id);
    if(!$selected_subset || !array_key_exists($selected_subset,$subsets)){
      $this->redirect(array("controller"=>"tools","action"=>"go_enrichment",$exp_id));
    }
    $this->set("selected_subset",$selected_subset);

    //check go_type
    $available_go_types	= array("BP"=>"Biological Process","CC"=>"Cellular Component","MF"=>"Molecular Function");
    $this->set("available_go_types",$available_go_types);
    if(!array_key_exists($go_type,$available_go_types)){
	$this->redirect(array("controller"=>"tools","action"=>"go_enrichment",$exp_id));
    }
    $this->set("go_type",$go_type);
    $this->set("pvalue",$pvalue);
	
    //file locations
    $tmp_dir		= TMP."experiment_data/".$exp_id."/";
    $result_file_path 	= $tmp_dir."go_enrichment_".$exp_id."_".$selected_subset."_".$pvalue.".txt";
    if(!file_exists($result_file_path)){$this->redirect(array("controller"=>"tools","action"=>"go_enrichment",$exp_id));}
    
    //ok, now read the file.
    $result_data_string	= array();
    if(filesize($result_file_path)!=0){   
	$fh	      		= fopen($result_file_path,"r");
    	$result_data_string	= fread($fh,filesize($result_file_path));
    	fclose($fh);  
    }
    $result	= array();    
    foreach(explode("\n",$result_data_string) as $r){
      $s	= explode("\t",$r);
      if(count($s)==5){
	if($s[3]>0){
		$result[$s[0]]	= array("go"=>$s[0],"hidden"=>$s[1],"p_value"=>$s[2],"ratio"=>$s[3],"perc"=>$s[4]);
	}
      }
    }
    $go_data		= $this->ExtendedGo->find("all",array("conditions"=>array("go"=>array_keys($result))));   
    $go_types		= $this->TrapidUtils->indexArraySimple($go_data,"ExtendedGo","go","type");
    $go_sptr		= $this->TrapidUtils->indexArraySimple($go_data,"ExtendedGo","go","num_sptr_steps");

    
    $sptr_array		= array();
    $max_sptr		= 0;	
    $data		= array();
    $go_desc		= array();
    foreach($result as $go_id=>$g){
      if($go_types[$go_id] == $go_type){
	$data[$go_id]		= $g;
	$sptr			= $go_sptr[$go_id];
	if($sptr > $max_sptr){$max_sptr = $sptr;}
	if(!array_key_exists($sptr,$sptr_array)){$sptr_array[$sptr] = array();}
	$sptr_array[$sptr][] = $go_id;		
      }
    }


    $all_graphs			= array();    
    $accepted_gos		= array();
    for($i = $max_sptr; $i>0;$i--){
      if(array_key_exists($i,$sptr_array)){
	  $level_gos 		= $sptr_array[$i];
	  foreach($level_gos as $level_go){	
	    if(count($all_graphs) > 0){
	      //check the already present graphs in the array, to determine whether or not the given GO term is already accounted for
    	      $done = false;
    	      foreach($all_graphs as $ag){
		if(array_key_exists($level_go,$ag['desc'])){$done = true; break 1;}
	      }
    	      if(!$done){
		$all_graphs[$level_go] = $this->GoParents->getParentalGraph($level_go);
		$accepted_gos[] = $level_go;
	      }
	    }
	    else{
	      $all_graphs[$level_go] = $this->GoParents->getParentalGraph($level_go);
	      $accepted_gos[] = $level_go;
	    }
	  }
      }
    }          
       
    // pr($sptr_array);
    //pr($data);
    //pr($all_graphs);
    //pr($accepted_gos);
    $this->set("all_graphs",$all_graphs); 
    $this->set("accepted_gos",$accepted_gos);
    $this->set("data",$data);
     
  }

  
  function download_enrichment($exp_id=null,$type=null,$selected_subset=null,$pvalue=null){
    //Configure::write("debug",1);
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $this->layout = "";
    //file locations
    $tmp_dir		= TMP."experiment_data/".$exp_id."/";   
    if($type==null || $selected_subset==null || $pvalue==null){
      $this->set("error","Cannot instantiate data");
      return;
    }

    $type		= mysql_real_escape_string($type);   
    $selected_subset	= mysql_real_escape_string($selected_subset);
    $pvalue		= mysql_real_escape_string($pvalue);
    $this->set("file_name",$type."_enrichment_".$exp_id."_".$selected_subset."_".$pvalue.".txt");
    $this->set("type",$type);

    $result_file_path 	= $tmp_dir."/".$type."_enrichment_".$exp_id."_".$selected_subset."_".$pvalue.".txt";	
    if(!file_exists($result_file_path)){     
      $this->set("error","Cannot load data");
      return;
    }
    $fh	      		= fopen($result_file_path,"r");
    $result_data_string	= fread($fh,filesize($result_file_path));
    fclose($fh); 
    
    $result	= array();    
    foreach(explode("\n",$result_data_string) as $r){
      $s	= explode("\t",$r);
      if(count($s)==5){
	if($s[3]>0){
	  if($type=="go"){
		$result[$s[0]]	= array("go"=>$s[0],"is_hidden"=>$s[1],"p-value"=>$s[2],"enrichment"=>$s[3],"subset_ratio"=>$s[4]);
	  }
	  else if($type=="ipr"){
		$result[$s[0]]	= array("ipr"=>$s[0],"is_hidden"=>$s[1],"p-value"=>$s[2],"enrichment"=>$s[3],"subset_ratio"=>$s[4]);
	  }
	}
      }
    }
    $this->set("result",$result);   
     
    //get extra information
    if($type=="go"){
    	$go_data		= $this->ExtendedGo->find("all",array("conditions"=>array("go"=>array_keys($result))));
    	$go_descriptions	= $this->TrapidUtils->indexArray($go_data,"ExtendedGo","go","desc");
    	$go_types		= array("MF"=>array(),"BP"=>array(),"CC"=>array());
    	foreach($go_data as $gd){  
      		$go_type	= $gd['ExtendedGo']['type'];
      		$go_types[$go_type][] = $gd['ExtendedGo']['go'];
    	}                 
    	$this->set("go_descriptions",$go_descriptions);
    	$this->set("go_types",$go_types);
    }
    else if($type=="ipr"){
      $ipr_data			= $this->ProteinMotifs->find("all",array("conditions"=>array("motif_id"=>array_keys($result))));
      $ipr_descriptions		= $this->TrapidUtils->indexArray($ipr_data,"ProteinMotifs","motif_id","desc");
      $this->set("ipr_descriptions",$ipr_descriptions);
    }
  }


  function load_enrichment($exp_id=null,$type=null,$subset_title=null,$pvalue=null,$result_file=null,$job_id=null){
    // Configure::write("debug",2);
    // pr($result_file);
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]); 	
    $this->set("exp_id",$exp_id);
    $this->layout = "";
    $this->helpers[] = 'FlashChart';
	
    $possible_types	= array("go"=>"GO","ipr"=>"Protein domain");
    //check type
    $type		= mysql_real_escape_string($type);
    if(!array_key_exists($type,$possible_types)){$this->redirect("/");}
    $this->set("available_types",$possible_types);
    $this->set("type",$type);


    if($subset_title==null){
      $this->set("error","No subset defined");
      return;
    }
    $this->set("subset",urldecode($subset_title));

    if($pvalue==null){
      $this->set("error","No P-value defined");
      return;
    }
    $this->set("selected_pvalue",$pvalue);

    if($result_file==null){
      $this->set("error","No result file defined");
      return;
    }    
    $result_file_path	= TMP."experiment_data/".$exp_id."/".$result_file;
    //pr($result_file_path);
    if($job_id!=null){      
      $cluster_res	= $this->TrapidUtils->waitfor_cluster($exp_id,$job_id,300,10);
      if(isset($cluster_res["error"])){$this->set("error",$cluster_res["error"]);return;}
      $file_sync	= $this->TrapidUtils->sync_file($result_file_path);
      if(!$file_sync){$this->set("error","Error syncing files");return;}
    }

    //ok, no error, so just read the result file
    //account for possible 
    $cont		= true;
    $result_data_string	= array();
    if(filesize($result_file_path)!=0){   
	$fh	      		= fopen($result_file_path,"r");
    	$result_data_string	= fread($fh,filesize($result_file_path));
    	fclose($fh);  
    }
    $result	= array();    
    foreach(explode("\n",$result_data_string) as $r){
      $s	= explode("\t",$r);
      if(count($s)==5){
	if($s[3]>0){
	  if($type=="go"){
		$result[$s[0]]	= array("go"=>$s[0],"is_hidden"=>$s[1],"p-value"=>$s[2],"enrichment"=>$s[3],"subset_ratio"=>$s[4]);
	  }
	  else if($type=="ipr"){
		$result[$s[0]]	= array("ipr"=>$s[0],"is_hidden"=>$s[1],"p-value"=>$s[2],"enrichment"=>$s[3],"subset_ratio"=>$s[4]);
	  }
	}
      }
    }
    $this->set("result",$result);

     
    //get extra information
    if($type=="go"){
    	$go_data		= $this->ExtendedGo->find("all",array("conditions"=>array("go"=>array_keys($result))));
    	$go_descriptions	= $this->TrapidUtils->indexArray($go_data,"ExtendedGo","go","desc");
    	$go_types		= array("MF"=>array(),"BP"=>array(),"CC"=>array());
    	foreach($go_data as $gd){  
      		$go_type	= $gd['ExtendedGo']['type'];
      		$go_types[$go_type][] = $gd['ExtendedGo']['go'];
    	}                 
    	$this->set("go_descriptions",$go_descriptions);
    	$this->set("go_types",$go_types);
    }
    else if($type=="ipr"){
      $ipr_data			= $this->ProteinMotifs->find("all",array("conditions"=>array("motif_id"=>array_keys($result))));
      $ipr_descriptions		= $this->TrapidUtils->indexArray($ipr_data,"ProteinMotifs","motif_id","desc");
      $this->set("ipr_descriptions",$ipr_descriptions);
    }

  }







  function orf_statistics($exp_id=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);       
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);


    //now, get distribution of meta annotation of ORF sequences.
    $meta_info	= $this->Transcripts->getMetaAnnotation($exp_id);
    $this->set("meta_info",$meta_info);

  }



  function length_distribution($exp_id=null,$sequence_type=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);       
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);	

    if($sequence_type!=null){if(!($sequence_type=="transcript" || $sequence_type=="orf")){$sequence_type="transcript";}}
    else{$sequence_type	= "transcript";}
    $this->set("sequence_type",$sequence_type);


    //Binning information
    $possible_bins	= array("5"=>"20","10"=>"15","20"=>"10","50"=>"5","100"=>"5","200"=>"5");
    $this->set("possible_bins",$possible_bins);
    $num_bins		= 50;
    if($_POST && array_key_exists("num_bins",$_POST) && array_key_exists($_POST['num_bins'],$possible_bins)){
      $num_bins	= mysql_real_escape_string($_POST['num_bins']);
    }
    $this->set("num_bins",$num_bins);
    $this->set("bars_offset",$possible_bins[$num_bins]);	   

    //based on sequence type, get different options
    //transcript : different meta types, different graphtypes
    if($sequence_type=="transcript"){
	//get standard length information
        $transcript_lengths	= $this->Transcripts->getLengths($exp_id,"transcript"); 
	//graphtype information
    	$possible_graphtypes   = array("grouped"=>"Adjacent","stacked"=>"Stacked");
    	$this->set("possible_graphtypes",$possible_graphtypes);
   	$graphtype		   = "grouped";
    	if($_POST && array_key_exists("graphtype",$_POST) && array_key_exists($_POST['graphtype'],$possible_graphtypes)){
      		$graphtype	   = mysql_real_escape_string($_POST['graphtype']);	 
    	}
    	$this->set("graphtype",$graphtype);
	$bins_transcript		= $this->Statistics->create_length_bins($transcript_lengths,$num_bins);  
	$json_transcript		= $this->Statistics->create_json_data_infovis($bins_transcript,"Transcript lengths");
	//get extra information (partials/no info)
    	$show_partials	= false;
    	$show_noinfo	= false;   
    	if($_POST && array_key_exists("meta_partial",$_POST)){$show_partials=true;}
    	if($_POST && array_key_exists("meta_noinfo",$_POST)){$show_noinfo=true;} 
	if($show_partials){
	  	$partial_lengths	= $this->Transcripts->getLengths($exp_id,"transcript","Partial");       
		$json_transcript	= $this->Statistics->update_json_data("Transcript lengths (Partial)",
					$partial_lengths,$json_transcript,$bins_transcript); 	    
		$this->set("meta_partial",1);     
    	}
    	if($show_noinfo){      
	  	$noinfo_lengths		= $this->Transcripts->getLengths($exp_id,"transcript","No Information");
      		$json_transcript	= $this->Statistics->update_json_data("Transcript lengths (No Information)",
					$noinfo_lengths,$json_transcript,$bins_transcript);      		      
      		$this->set("meta_noinfo",1);
    	}
	//update last label     
    	$last_label_transcript	= explode(",",$bins_transcript["labels"][$num_bins-1]);	
	$json_transcript["values"][$num_bins-1]["label"]=">=".$last_label_transcript[0];
	if(count($json_transcript["label"])>1){
	  $json_transcript["label"][0] = "Transcript lengths (full-length or quasi full-length)";
	}
	 $this->set("bins_transcript",$json_transcript);
    }
    //orf: possible reference species
    else if($sequence_type=="orf"){
        $orf_lengths		= $this->Transcripts->getLengths($exp_id,"orf");	
	//reference species
    	$reference_species	= $this->AnnotSources->find("all",array("order"=>"`species` ASC"));
    	$reference_species	= $this->TrapidUtils->indexArraySimple($reference_species,"AnnotSources","species","common_name");
    	$this->set("available_reference_species",$reference_species);
    	$selected_ref_species	= "";
    	if($_POST && array_key_exists("reference_species",$_POST)){
      		$rs	= mysql_real_escape_string($_POST['reference_species']);
      		if($this->AnnotSources->find("first",array("conditions"=>array("species"=>$rs)))){
			$selected_ref_species	= $rs;
      		}
    	}
    	$this->set("selected_ref_species",$selected_ref_species);

	//default value graph type
	$this->set("graphtype","grouped");

	$normalize_data	= false;
    	//normalize the values of the json data if necessary.
    	if(array_key_exists("normalize",$_POST) && $selected_ref_species!=""){
      		$normalize_data	= true;
      		$this->set("normalize_data",true);
    	}   	
	$bins_orf			= $this->Statistics->create_length_bins($orf_lengths,$num_bins);
	//Configure::write("debug",1);
	//pr($bins_orf);
        $json_orf			= $this->Statistics->create_json_data_infovis($bins_orf,"ORF lengths");

	//pr($json_orf);

	if($selected_ref_species!=""){
      		$ref_species_lengths	= $this->Annotation->getLengths($selected_ref_species);    
		//pr($ref_species_lengths);        
      		$json_orf		= $this->Statistics->update_json_data($reference_species[$selected_ref_species],
								      $ref_species_lengths,$json_orf,$bins_orf,false);	
    	} 
        $last_label_orf		= explode(",",$bins_orf["labels"][$num_bins-1]);	
	$json_orf["values"][$num_bins-1]["label"]=">=".$last_label_orf[0];
	//pr($json_orf);
	if($normalize_data){	 
	  $json_orf			= $this->Statistics->normalize_json_data($json_orf);
	}
	//pr($json_orf);
	//if(count($json_orf["label"])>1){
	// $json_orf["label"][0] = "Transcript lengths (full-length or quasi full-length)";
	//}
	//pr($json_orf);
	$this->set("bins_orf",$json_orf);    
    }
    else{
      $this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
    }


  }





  function statistics($exp_id=null,$pdf='0'){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);       
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);  

    //all species names
    $all_species	= $this->AnnotSources->getSpeciesCommonNames();
    $this->set("all_species",$all_species);
    
    //get transcript information
    $num_transcripts	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id)));
    $seq_stats		= $this->Transcripts->getSequenceStats($exp_id);
    $num_start_codons	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"orf_contains_start_codon"=>1)));
    $num_stop_codons	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"orf_contains_stop_codon"=>1)));
    $num_putative_fs	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"putative_frameshift"=>1)));
    $num_correct_fs	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"is_frame_corrected"=>1)));
    $meta_annot_fulllength	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"meta_annotation"=>"Full Length")));
    $meta_annot_quasi	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"meta_annotation"=>"Quasi Full Length")));
    $meta_annot_partial	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"meta_annotation"=>"Partial")));
    $meta_annot_noinfo	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"meta_annotation"=>"No Information")));

    $this->set("num_transcripts",$num_transcripts);
    $this->set("seq_stats",$seq_stats);
    $this->set("num_start_codons",$num_start_codons);
    $this->set("num_stop_codons",$num_stop_codons);
    $this->set("num_putative_fs",$num_putative_fs);
    $this->set("num_correct_fs",$num_correct_fs);
    $this->set("meta_annot_fulllength",$meta_annot_fulllength);
    $this->set("meta_annot_quasi",$meta_annot_quasi);	
    $this->set("meta_annot_partial",$meta_annot_partial);
    $this->set("meta_annot_noinfo",$meta_annot_noinfo);


    //get gene family information
    $num_gf		= $this->GeneFamilies->find("count",array("conditions"=>array("experiment_id"=>$exp_id)));
    $num_transcript_gf	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"not"=>array("gf_id"=>null))));
    $biggest_gf	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id),"order"=>array("num_transcripts DESC")));
    $biggest_gf_res	= array("gf_id"=>$biggest_gf['GeneFamilies']['gf_id'],"num_transcripts"=>$biggest_gf['GeneFamilies']['num_transcripts']);    
    $single_copy	= $this->GeneFamilies->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"num_transcripts"=>1)));

    $this->set("num_gf",$num_gf);
    $this->set("num_transcript_gf",$num_transcript_gf);
    $this->set("biggest_gf",$biggest_gf_res);
    $this->set("single_copy",$single_copy);

    //get functional data information
    $go_stats		= $this->TranscriptsGo->getStats($exp_id);
    $this->set("num_go",$go_stats['num_go']);
    $this->set("num_transcript_go",$go_stats['num_transcript_go']);
    $interpro_stats	= $this->TranscriptsInterpro->getStats($exp_id);
    $this->set("num_interpro",$interpro_stats['num_interpro']);
    $this->set("num_transcript_interpro",$interpro_stats['num_transcript_interpro']);

    if($_POST || $pdf=='1'){
      if($pdf=='1' || (array_key_exists("export_type",$_POST) && $_POST['export_type']=="pdf")){
	$this->set("pdf_view",1);
	$this->helpers[] 	= "fpdf";
	$this->layout		= "pdf";	 
	$pdf_transcript_info	= array(
			"#Transcripts"=>$num_transcripts,
			"Average transcript length"=>$seq_stats['transcript']." basepairs",
			"Average ORF length"=>$seq_stats['orf']." basepairs",
			"#ORFs with start codon"=>$num_start_codons." (".round(100*$num_start_codons/$num_transcripts,1)."%)",
			"#ORFs with stop codon"=>$num_stop_codons." (".round(100*$num_stop_codons/$num_transcripts,1)."%)"
					);

	$pdf_frameshift_info	= array(
		        "#Transcripts with putative frameshift"=>$num_putative_fs." (".round(100*$num_putative_fs/$num_transcripts,1)."%)",
			"#Transcripts with corrected frameshift"=>$num_correct_fs." (".round(100*$num_correct_fs/$num_transcripts,1)."%)"
					);
				       
	$pdf_meta_info		= array(
	        "#Meta annotation full-length"=>$meta_annot_fulllength." (".round(100*$meta_annot_fulllength/$num_transcripts,1)."%)",
	        "#Meta annotation quasi full-length"=>$meta_annot_quasi." (".round(100*$meta_annot_quasi/$num_transcripts,1)."%)",
	       	"#Meta annotation partial"=>$meta_annot_partial." (".round(100*$meta_annot_partial/$num_transcripts,1)."%)",
   		"#Meta annotation no information"=>$meta_annot_noinfo." (".round(100*$meta_annot_noinfo/$num_transcripts,1)."%)",
				);	
     
	$pdf_gf_info		= array(
			 "#Gene families"=>$num_gf,
			 "#Transcripts in GF"=>$num_transcript_gf." (".round(100*$num_transcript_gf/$num_transcripts,1)."%)"			
				);
	
	$pdf_func_info		= array(
	   "#Transcripts with GO"=>$go_stats['num_transcript_go']." (".round(100*$go_stats['num_transcript_go']/$num_transcripts,1)."%)",
	   "#Transcripts with Protein Domain"=>$interpro_stats['num_transcript_interpro']." (".round(100*$interpro_stats['num_transcript_interpro']/$num_transcripts,1)."%)",
				);
	
				
	$this->set("pdf_transcript_info",$pdf_transcript_info);
	$this->set("pdf_frameshift_info",$pdf_frameshift_info);
	$this->set("pdf_meta_info",$pdf_meta_info);	
	$this->set("pdf_gf_info",$pdf_gf_info);
	$this->set("pdf_func_info",$pdf_func_info);

	$this->render();       
      }
    }


  }


  function comparative_statistics($exp_id=null){

  }

  /*
   * Extract the necessary datastructs to generate a Sankey diagram
   */
  function sankey($exp_id=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    //$this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);       
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);  

    $rows	= $this->Transcripts->getLabelToGFMapping($exp_id);
    // transform the data to the format expected by d3
    // Expected format:
	  /*$d = array(
            "nodes" =>  array(
                    array('name' => 'A'),
                    array('name' => 'B')
                    ), 
            "links" => array(
                    array("source" => 0,"target" => 1,"value"=>124.729)
                    )
            );
    */
  // What we get from the DB: [[A,B,124]]


    $names = array(); // Helper array because searching in the associative $nodes array is slower
    $nodes = array();
    $links = array();
    $inflow = array(); // Keeps track of the total size of gf's
    $k = 40; // The number of gfs we initially want to show.
    
    foreach ($rows as $row){
      // Clean up the label, from expId_GFId to GFId, expId is assumed to be numerical
      $gf_id = preg_replace("/\d*_/","",$row[0],1);
      $label = $row[1];
      $count = $row[2];
      if(!in_array($label, $names)){
        $names[] = $label;
        $nodes[] = array('name' => $label);
      }
      if(!in_array($gf_id, $names)){
        $names[] = $gf_id;
        $nodes[] = array('name' => $gf_id);
        $inflow[$gf_id] = 0;
      }
  
      $links[] = array("source" => array_search($label,$names),"target" => array_search($gf_id,$names),"value"=>$count);
      // Keeping track of total inflow in a gene_family
      $inflow[$gf_id] += $count;
    }

    $d = array("nodes" => $nodes, 
               "links" => $links);
    arsort($inflow);
    if(count($inflow) < $k){
        // There are less than k gfs, display them all.
        $min = 0;
    } else {
        $min = reset(array_slice ($inflow, $k, 1, true));
    }
    //$this->set('maximum_count', $max_count);
    $this->set('maximum_count', reset($inflow));
    $this->set('minimum_count', $min);
	$this->set('sankeyData', json_encode($d));
    $this->set('inflow_data', json_encode($inflow));
  }

function cmp($a, $b)
{
    return strcmp($a["title"], $b["title"]);
}




  /*
   * Cookie setup: 
   * The entire TRAPID websit is based on user-defined data sets, and as such a method for 
   * account handling and user identification is required.
   *
   * The 'beforeFilter' method is executed BEFORE each method, and as such ensures that the necessary
   * identification through cookies is done.
   */
  function beforeFilter(){
    parent::beforeFilter();  
  }


}
?>
