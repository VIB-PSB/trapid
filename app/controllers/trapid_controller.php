<?php
App::import("Sanitize");
/*
 * General controller class for the trapid functionality
 */
class TrapidController extends AppController{
  var $name		= "Trapid";
  var $helpers		= array("Html","Form","Javascript","Ajax");

  var $uses		= array("Authentication","Experiments","DataSources","Transcripts","GeneFamilies","ExperimentLog",
				"TranscriptsGo","TranscriptsInterpro","TranscriptsLabels","TranscriptsPagination","Similarities",
				"SharedExperiments","DataUploads","ExperimentJobs","CleanupDate","CleanupExperiments",

				"AnnotSources","Annotation","ExtendedGo","ProteinMotifs","GfData"
				);

  var $components	= array("Cookie","TrapidUtils","Sequence");
  var $paginate		= array(
				"Transcripts"=>
					array(
						"limit"=>10,
			       			"order"=>array("Transcripts.transcript_id"=>"ASC")					
				  	),			
				"TranscriptsPagination"=>
					array(
					      "limit"=>10,
					      "order"=>array("transcript_id"=>"ASC")	
					)
			  );


  function qdel_all($code=null){
    if($code=="enable_delete"){
      Configure::write("debug",2);
      $qdel_file	= $this->TrapidUtils->create_qdel_script("test");
      $out	= array();
      exec("sh ".$qdel_file." -u \"apache\"  2>&1",$out);
      pr($out);	
    }    
  }



  function qdel_experiment($cluster_id=null){
    Configure::write("debug",1);
    $exp_id	= "1";
    if(!$cluster_id){$cluster_id = "1549";}
    $qdel_file	= $this->TrapidUtils->create_qdel_script($exp_id);
    $out	= array();
    exec("sh ".$qdel_file." ".$cluster_id." 2>&1",$out);
    pr($out);
  }  

  function clear_framedp_evaluation(){
    $exp_id			= "48";
    $tmp_dir			= TMP."experiment_data/".$exp_id."/";	
    $framedp_dir_eval		= $tmp_dir."framedp/evaluation/";
    //shell_exec("rm -rf ".$framedp_dir_eval);	
    $framedp_dir_results	= $tmp_dir."framedp/training/000/";
    shell_exec("rm -rf ".$framedp_dir_results);
  }

  function clear_framedp(){
    $exp_id			= "55";
    $tmp_dir			= TMP."experiment_data/".$exp_id."/";	
    $framedp_dir		= $tmp_dir."framedp/";
    shell_exec("rm -rf ".$framedp_dir);
  }

  function clear_framedp_training(){
    $exp_id			= "25";
    $tmp_dir			= TMP."experiment_data/".$exp_id."/";	
    $framedp_dir		= $tmp_dir."framedp/";
    $framedp_dir_training	= $framedp_dir."training/";
    if(!(file_exists($framedp_dir_training) && is_dir($framedp_dir_training))){
	  mkdir($framedp_dir_training);
	  shell_exec("chmod a+rw ".$framedp_dir_training);
    }
    else{
	  //delete previous content for now!!!
	  shell_exec("rm -rf ".$framedp_dir_training."*");      
    }
  }


  function manage_jobs($exp_id=null){
    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	 
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $running_jobs	= $this->ExperimentJobs->getJobs($exp_id);
    //if(count($running_jobs)==0){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $running_jobs	= $this->TrapidUtils->checkJobStatus($exp_id,$running_jobs);
    $this->set("running_jobs",$running_jobs);
    
    $jobs_to_delete	= array();
    if($_POST){
      foreach($_POST as $k=>$v){
	if(strlen($k)>=5  && $v=="on"){
	   $ji = substr($k,4);
	   //check if this job-id actually appears in the job-list, so users cannnot delete jobs of other users.
	   if(array_key_exists($ji,$running_jobs)){
	     $jobs_to_delete[] = $ji;
	   }
	}
      }      
      foreach($jobs_to_delete as $jtd){      
	$this->TrapidUtils->deleteClusterJob($exp_id,$jtd);
	$this->ExperimentJobs->deleteJob($exp_id,$jtd);
      }
      $this->redirect(array("controller"=>"trapid","action"=>"manage_jobs",$exp_id));
    }   	
  }



  function change_status($exp_id=null){
    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	 
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);	
    
    $tmp_dir	= TMP."experiment_data/".$exp_id."/";   
    $usage	= "0M";
    if(file_exists($tmp_dir) && is_dir($tmp_dir)){
       $usage = shell_exec("du -h ".$tmp_dir." | tail -n 1 | cut -f 1");		      	
    }
    $this->set("disk_usage",$usage);

    if($_POST && array_key_exists("form_type",$_POST)){
      if($_POST['form_type']=="clear_storage"){
	shell_exec("rm -rf ".$tmp_dir."* ");
	$this->redirect(array("controller"=>"trapid","action"=>"experiments"));
      }
      if($_POST['form_type']=="change_status" && array_key_exists("new_status",$_POST)){	
	//change status
	$new_status	= mysql_real_escape_string($_POST['new_status']);
        $this->Experiments->updateAll(array("process_state"=>"'".$new_status."'"),array("experiment_id"=>$exp_id));
	//try to delete running job
	if($exp_info['process_state']=='processing'){
	  //check for all_running jobs  (exp_id -> job_id)	  
	  $running_jobs	= $this->TrapidUtils->get_all_processing_experiments();
	  if(array_key_exists($exp_id,$running_jobs)){
	    //delete job 
	    $this->TrapidUtils->delete_job($running_jobs[$exp_id]);
	  }
	}
	shell_exec("rm -rf ".$tmp_dir."* ");
      }
      $this->redirect(array("controller"=>"trapid","action"=>"experiments"));
    }

  }




  /**
   * Entry page of the TRAPID online website. 
   * Only displays some information and a login-screen.
   * Skips right to experiments overview if user is already logged in.
   */
  function index(){   
      	$user_id	= $this->Cookie->read("user_id");
      	$email		= $this->Cookie->read("email");
      	$user_id  	= mysql_real_escape_string($user_id);
      	$email		= mysql_real_escape_string($email);
      	$user_data	= $this->Authentication->find("first",array("conditions"=>array("user_id"=>$user_id,"email"=>$email)));
	if($user_data){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}

	//test email
	//mail("mibel@psb.ugent.be","test email","blablabla");
	
  }


  //fast and ugly hack to facilitate ajax call to get extra information about number of transcripts per experiment.
  function experiments_num_transcripts($exp_id){
    //Configure::write("debug",2);
    $this->layout = "";   
    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	 
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $num_transcripts = "N/A";
    if($exp_info){
      $num_transcripts = $exp_info['transcript_count'];
    }
    $this->set("num_transcripts",$num_transcripts);
    return;
  }


  /*
   * Displays experiment information for a given user
   */
  function experiments(){   
    //Configure::write("debug",2);   


    $MAX_USER_EXPERIMENTS	= 10;
    $this->set("max_user_experiments",$MAX_USER_EXPERIMENTS);

    //check whether valid user id.
    //$user_id 		= $this->check_user();
    $user_id		= parent::check_user();
	
    //retrieve information about the user
    $user_email		= $this->Authentication->find("first",array("fields"=>array("email"),"conditions"=>array("user_id"=>$user_id)));
    $this->set("user_email",$user_email);
	

    //retrieve possible available PLAZA databases from the configuration table
    $available_sources	= $this->DataSources->find("all");    
    $this->set("available_sources",$available_sources);

    //retrieve current user experiments. 
    $experiments	= $this->Experiments->getUserExperiments($user_id); 
    //pr($experiments);
    $this->set("experiments",$experiments);

    //shared experiments
    $shared_exp_ids	= $this->SharedExperiments->find("all",array("conditions"=>array("user_id"=>$user_id)));
    $shared_exp_ids	= $this->TrapidUtils->reduceArray($shared_exp_ids,"SharedExperiments","experiment_id");   
    $shared_experiments	= $this->Experiments->getSharedExperiments($shared_exp_ids);
    $this->set("shared_experiments",$shared_experiments);

    if(count($shared_experiments)!=0){
      $all_user_ids	= $this->Authentication->find("all",array("fields"=>array("user_id","email")));
      $all_user_ids	= $this->TrapidUtils->indexArraySimple($all_user_ids,"Authentication","user_id","email");
      $this->set("all_user_ids",$all_user_ids);
    }
    //check if post
    if($_POST){
      if(array_key_exists("experiment_name",$_POST) && array_key_exists("experiment_description",$_POST) && array_key_exists("data_source",$_POST)){
       	$experiment_name	= mysql_real_escape_string($_POST['experiment_name']);
	$experiment_description	= mysql_real_escape_string($_POST['experiment_description']);
	$data_source		= mysql_real_escape_string($_POST['data_source']);
	
	//check whether person has not already reached the limit of number of experiments (normally form should be disabled as well)
	if(count($experiments)>=$MAX_USER_EXPERIMENTS){
	  $this->set("error","Maximum experiments reached for this user account");
	  return;
	}
	
	//check whether valid plaza version is selected	
	$data_source_name	= null;
	foreach($available_sources as $as){
	  if($as['DataSources']['db_name']==$data_source){$data_source_name=$as['DataSources']['name'];}
	}
	if($data_source_name==null){
	  $this->set("error","Unknown data source selected. Please contact website administrator");
	  return;
	}

	if($experiment_name==""){
	  $this->set("error","No name defined.");
	  return;
	}
	
	//ok, checks are passed, now simply create new experiment in the database
	$this->Experiments->save(array("user_id"=>$user_id,"experiment_id"=>"","title"=>$experiment_name,"description"=>$experiment_description,"creation_date"=>date("Y-m-d H:i:s"),"last_edit_date"=>date("Y-m-d H:i:s"),"process_state"=>"empty","used_plaza_database"=>$data_source));
	//get last experiment id
	$user_experiments	= $this->Experiments->query("SELECT `experiment_id` FROM `experiments` WHERE `user_id`='".$user_id."' ORDER BY `experiment_id` DESC ");
	$exp_id			= $user_experiments[0]['experiments']['experiment_id'];
	$this->ExperimentLog->addAction($exp_id,"create_experiment","");      
	$this->redirect(array("controller"=>"trapid","action"=>"experiments"));	
      }
      else{
      	$this->set("error","Please enter the necessary information in the required fields");
	return;
      }
    }   
  }



  /*
   * Content page for the log of an experiment.
   * Pages and actions which are deemed worthy of logging, can be 
   */
  function view_log($exp_id=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["all"]);       
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $log_info	= $this->ExperimentLog->find("all",array("conditions"=>array("experiment_id"=>$exp_id),
							"order"=>array("ExperimentLog.id ASC")));
    $this->set("log_info",$log_info);    
  }



  /*
   * Share the experiment. 
   */
  function experiment_access($exp_id=null){
     $exp_id	= mysql_real_escape_string($exp_id);
     parent::check_user_exp($exp_id);	
     $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
     $this->set("exp_info",$exp_info);
     $this->set("exp_id",$exp_id);
     $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]); 


     //get all the users.
     $all_users_	= $this->Authentication->find("all",array("fields"=>array("user_id","email")));
     $all_users		= $this->TrapidUtils->indexArraySimple($all_users_,"Authentication","user_id","email");
     $all_users_inv	= $this->TrapidUtils->indexArraySimple($all_users_,"Authentication","email","user_id");
     $this->set("all_users",$all_users);

     if($_POST){
       if(array_key_exists("new_share",$_POST)){
	 $new_share	= preg_split("/[ \n]/",$_POST['new_share']);
	 $selected	= array();
	 foreach($new_share as $ns){     
	   $ns	= trim($ns);
	   if(array_key_exists($ns,$all_users_inv)){
	     $selected[$all_users_inv[$ns]] = $ns;
	   }
	 }	 
	 $this->set("num_added",count($selected));
	 foreach($selected as $k=>$v){
	   $this->SharedExperiments->save(array("user_id"=>$k,"experiment_id"=>$exp_id));
	 }		 
       }
     }
    	
     //show the users with who this experiment is shared
     $shared_user_ids	= $this->SharedExperiments->find("all",array("conditions"=>array("experiment_id"=>$exp_id)));
     $shared_user_ids	= $this->TrapidUtils->reduceArray($shared_user_ids,"SharedExperiments","user_id");
     $shared_users	= array("owner"=>array(),"shared"=>array());
     $shared_users["owner"][$exp_info['user_id']]	= $all_users[$exp_info['user_id']];
     foreach($shared_user_ids as $sui){$shared_users['shared'][$sui] = $all_users[$sui];}
     $this->set("shared_users",$shared_users);
     

     //however, only the owner of the experiment (original creator) can change the access to the experiment
     $is_owner 		= parent::is_owner($exp_id);
     $this->set("is_owner",$is_owner);	

 	
  }




  /*
   * Change some settings (name, description, etc...) of an experiment.
   */
  function experiment_settings($exp_id=null){

    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	 
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    //$this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);	   
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]); 
    $this->set("exp_info",$exp_info);
    $this->set("show_experiment_overview_description",1);

    if($_POST){
      if(array_key_exists("experiment_name",$_POST)){
	$new_exp_name		= mysql_real_escape_string($_POST["experiment_name"]);
	if($new_exp_name==""){
	  $this->set("error","No name defined");
	  return;
	}
	$this->Experiments->updateAll(array("title"=>"'".$new_exp_name."'"),array("experiment_id"=>$exp_id));
      }
      if(array_key_exists("experiment_description",$_POST)){
	$new_exp_desc		= mysql_real_escape_string($_POST["experiment_description"]);
	$this->Experiments->updateAll(array("description"=>"'".$new_exp_desc."'"),array("experiment_id"=>$exp_id));
      }	
      $exp_info	= $this->Experiments->getDefaultInformation($exp_id);   
      $this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
    }
    
    
  }





  /*
   * Content page of a single experiment
   * Data displayed here should consist of basic information.
   * More complicated info (which would require more processing)
   * should only be accesible through tool-pages
   */
  function experiment($exp_id=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);   
    $standard_experiment_info	= $this->Experiments->find("first",array("conditions"=>array("experiment_id"=>$exp_id)));        
    $this->TrapidUtils->checkPageAccess($standard_experiment_info['Experiments']['title'],$standard_experiment_info['Experiments']['process_state'],$this->process_states["default"]); 

    //set the edit date 
    $this->Experiments->updateAll(array("last_edit_date"=>"'".date("Y-m-d H:i:s")."'"),array("experiment_id"=>$exp_id));
  
    
    $this->set("exp_id",$exp_id);
    $user_group=$this->Authentication->find("first",array("fields"=>array("group"),"conditions"=>array("user_id"=>parent::check_user())));
    if($user_group['Authentication']['group'] == "admin"){$this->set("admin",1);} 
   
    //check whether the number of jobs in the queue for this experiment has not been reached.
    $current_job_number = $this->ExperimentJobs->getNumJobs($exp_id);
    if($current_job_number>=MAX_CLUSTER_JOBS){$this->set("max_number_jobs_reached",true);}

    //get default experiment information
    $transcript_experiment_info	= $this->Transcripts->findExperimentInformation($exp_id); 
    $datasource_info		= $this->DataSources->find("first",array("conditions"=>array("db_name"=>$standard_experiment_info["Experiments"]["used_plaza_database"])));
    $this->set("standard_experiment_info",$standard_experiment_info);
    $this->set("transcript_experiment_info",$transcript_experiment_info);
    $this->set("datasource_info",$datasource_info["DataSources"]);
    $num_transcripts	= $transcript_experiment_info[0][0]['transcript_count'];
    $this->set("num_transcripts",$num_transcripts);
    $all_subsets	= $this->TranscriptsLabels->getLabels($exp_id);
    $this->set("num_subsets",count($all_subsets));

  
    if($standard_experiment_info['Experiments']['process_state']!="empty"){
	//retrieve information for table at bottom of page
	$transcripts_p	= $this->paginate("Transcripts",array("Transcripts.experiment_id"=>$exp_id));      
	$transcript_ids	= $this->TrapidUtils->reduceArray($transcripts_p,"Transcripts","transcript_id");

	//retrieve functional annotation for the table       
	$transcripts_go	= $this->TrapidUtils->indexArray($this->TranscriptsGo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids,"is_hidden"=>"0"))),"TranscriptsGo","transcript_id","go");
	$go_info	= array();
	if(count($transcripts_go)!=0){
		$go_ids		=  array_unique(call_user_func_array("array_merge",array_values($transcripts_go)));  
		$go_info        = $this->ExtendedGo->retrieveGoInformation($go_ids);       
	}

	$transcripts_ipr= $this->TrapidUtils->indexArray($this->TranscriptsInterpro->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids))),"TranscriptsInterpro","transcript_id","interpro");
	$ipr_info	= array();
	if(count($transcripts_ipr)!=0){
		$ipr_ids        = array_unique(call_user_func_array("array_merge",array_values($transcripts_ipr))); 
		$ipr_info	= $this->ProteinMotifs->retrieveInterproInformation($ipr_ids);
	}

	//retrieve subset/label information
	$transcripts_labels	= $this->TrapidUtils->indexArray($this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids))),"TranscriptsLabels","transcript_id","label");
	

	$this->set("transcript_data",$transcripts_p);
	$this->set("transcripts_go",$transcripts_go);
	$this->set("transcripts_ipr",$transcripts_ipr);
	$this->set("transcripts_labels",$transcripts_labels);
	$this->set("go_info",$go_info);
	$this->set("ipr_info",$ipr_info);
    }
  }




  function change_transcript_gf($exp_id=null,$transcript_id=null){
    if(!$exp_id || !$transcript_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	 
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);	   
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]); 

    //check whether transcript is valid
    $transcript_id 	= mysql_real_escape_string($transcript_id);
    $transcript_info    = $this->Transcripts->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
    //pr($transcript_info);
    if(!$transcript_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}    	

    $this->set("transcript_info",$transcript_info);	

  }




  function similarity_hits($exp_id=null,$transcript_id=null){
    //Configure::write("debug",2);
    if(!$exp_id || !$transcript_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	 
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);	   
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]); 
    //check whether transcript is valid
    $transcript_id 	= mysql_real_escape_string($transcript_id);
    $transcript_info    = $this->Transcripts->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));    
    if(!$transcript_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}    	
    $this->set("transcript_info",$transcript_info['Transcripts']);
    //get the similarity search hits for this transcript
    $similarity_hits   = $this->Similarities->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));           
    $similarity_hits	= explode(";",$similarity_hits['Similarities']['similarity_data']);
    $sim_hits		= array();
    $gene_ids		= array();
    //get the gene identifiers.
    foreach($similarity_hits as $sh){
      $t		= explode(",",$sh);
      $gene_id		= $t[0];
      $gene_ids[] 	= $gene_id;
      $sim_hits[$gene_id][]	= $t;	
    }
    $gene_ids		= array_unique($gene_ids);
    $gf_ids		= array();
    //ok, now see whether the experiment is HOM or IORTHO. If IORTHO, don't do anything.  
    if($exp_info['genefamily_type']=="HOM"){
      $gf_prefix	= $this->DataSources->find("first",array("conditions"=>array("name"=>$exp_info['datasource'])));
      $gf_prefix	= $gf_prefix['DataSources']['gf_prefix'];       
      if($gf_prefix){$gf_ids=$this->GfData->find("all",array("conditions"=>array("gene_id"=>$gene_ids,"`gf_id` LIKE '".$gf_prefix."%'")));}
      else{$gf_ids=$this->GfData->find("all",array("conditions"=>array("gene_id"=>$gene_ids)));}          
      $gf_ids		= $this->TrapidUtils->indexArraySimple($gf_ids,"GfData","gene_id","gf_id");
      $plaza_gf_ids	= array_unique(array_values($gf_ids));         
      $plaza_gf_counts  = $this->GfData->getGeneCount($plaza_gf_ids);      
      $transcript_gfs	= $this->GeneFamilies->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"plaza_gf_id"=>$plaza_gf_ids))); 
      $transcript_gfs1	= $this->TrapidUtils->indexArraySimple($transcript_gfs,"GeneFamilies","plaza_gf_id","gf_id");
      $transcript_gfs2 	= $this->TrapidUtils->indexArraySimple($transcript_gfs,"GeneFamilies","gf_id","num_transcripts");
      $this->set("plaza_gf_counts",$plaza_gf_counts);
      $this->set("transcript_gfs1",$transcript_gfs1);
      $this->set("transcript_gfs2",$transcript_gfs2);
    }
    $this->set("sim_hits",$sim_hits);
    $this->set("gf_ids",$gf_ids);

    if($this->Session->check("error")){$this->set("error",$this->Session->read("error"));$this->Session->delete("error");}
    if($this->Session->check("message")){$this->set("message",$this->Session->read("message"));$this->Session->delete("message");}
    


    if($_POST && array_key_exists("plaza_gf_id",$_POST)){     
                    
      $new_plaza_gf_id		= mysql_real_escape_string($_POST['plaza_gf_id']);
      //check if exists. If not, return to page with error message.
      $num_plaza_genes		= $this->GfData->find("count",array("conditions"=>array("gf_id"=>$new_plaza_gf_id)));
      if($num_plaza_genes==0){$this->set("error","Illegal external identifier for gene family");}
      $new_trapid_gf_id		= null;
      $new_trapid_gf_info	= null;
      $total_new_gf		= true;
      if(array_key_exists("trapid_gf_id",$_POST)){
	$new_trapid_gf_id	= mysql_real_escape_string($_POST['trapid_gf_id']);
	$new_trapid_gf_info	= $this->GeneFamilies->find("first",array("conditions"=>array("gf_id"=>$new_trapid_gf_id)));
	if(!$new_trapid_gf_info){$this->set("error","Illegal internal gene family identifier");return;}
	$total_new_gf		= false;
      }
      

      //Total new gene family:
      // - create new gf id (prefix is experiment id, suffix is plaza gf id)
      // - add gene family
      // - update transcript gene family association
      if($total_new_gf){
	$new_trapid_gf_id	= $exp_id."_".$new_plaza_gf_id;
	$this->GeneFamilies->save(array("experiment_id"=>$exp_id,"gf_id"=>$new_trapid_gf_id,"plaza_gf_id"=>$new_plaza_gf_id,"num_transcripts"=>"1"));
	$this->Transcripts->updateAll(array("gf_id"=>"'".$new_trapid_gf_id."'"),array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id));	
      }
      //
      else{
	//update the new gf id, so the number of associated transcripts is higher
        $this->GeneFamilies->updateAll(array("num_transcripts"=>"'".($new_trapid_gf_info['GeneFamilies']['num_transcripts']+1)."'","used_species"=>NULL,"exclude_transcripts"=>NULL,"msa"=>NULL,"msa_stripped"=>NULL,"msa_stripped_params"=>NULL,"tree"=>NULL),array("experiment_id"=>$exp_id,"gf_id"=>$new_trapid_gf_id));
	$this->Transcripts->updateAll(array("gf_id"=>"'".$new_trapid_gf_id."'"),array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id));
      }
    	


      //process the old gene family of the transcript: reduce the number of associated genes with 1
      //if it reaches 0, remove the entry from the database.   							         
      $prev_gf_id			= $transcript_info['Transcripts']['gf_id'];
      $prev_transcript_count		= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$prev_gf_id),"fields"=>array("num_transcripts")));
      $prev_transcript_count		= $prev_transcript_count['GeneFamilies']['num_transcripts'];
      $prev_transcript_count--;	
  
      $this->ExperimentLog->addAction($exp_id,"change_genefamily",$transcript_id."(".$prev_gf_id."->".$new_trapid_gf_id.")");      
	

      if($prev_transcript_count==0){	//delete this entry
	$this->GeneFamilies->deleteAll(array("experiment_id"=>$exp_id,"gf_id"=>$prev_gf_id));
      }
      else{
     	$this->GeneFamilies->updateAll(array("num_transcripts"=>"'".$prev_transcript_count."'","used_species"=>NULL,"exclude_transcripts"=>NULL,"msa"=>NULL,"msa_stripped"=>NULL,"msa_stripped_params"=>NULL,"tree"=>NULL),array("experiment_id"=>$exp_id,"gf_id"=>$prev_gf_id));
      }

      //now, also execute a short java which should 
      //a) move the functional annotation, or in cas of 'total new gf', create one from scratch
      //b) perform meta-annotation again for the newly assigned transcript.
      //these are all steps of the initial processing pipeline, but should now be performed only for a given transcript/gene family association.   
      $qsub_file  = $this->TrapidUtils->create_qsub_script($exp_id);
      $shell_file =$this->TrapidUtils->create_shell_script_data_update_gf($exp_id,$exp_info['used_plaza_database'],$new_trapid_gf_id,$transcript_id,$total_new_gf);            
      if($shell_file == null || $qsub_file == null ){
	$this->Session->write("error","Problem creating program files. Functional annotation synchronization not performed.");
	//$this->set("error","problem creating program files. Functional annotation synchronization not performed.")
      }
      else{
	  //ok, now we submit this program to the web-cluster
	  $tmp_dir	= TMP."experiment_data/".$exp_id."/";
	  $qsub_out	= $tmp_dir."gf_change_".$exp_id."_".$transcript_id."_".$new_trapid_gf_id.".out";
	  $qsub_err	= $tmp_dir."gf_change_".$exp_id."_".$transcript_id."_".$new_trapid_gf_id.".err";
	  if(file_exists($qsub_out)){unlink($qsub_out);}
	  if(file_exists($qsub_err)){unlink($qsub_err);}
	  $command  = "sh $qsub_file -q medium -o $qsub_out -e $qsub_err $shell_file";
	  exec($command);     
	  $this->Session->write("message","Synchronizing functional and meta annotation with new gene family.");
      }
      $this->redirect(array("controller"=>"trapid","action"=>"similarity_hits",$exp_id,$transcript_id));
	
    }


  }



  /******************************************************************************************************
   *
   * DATA TYPE PAGES :
   *
   ******************************************************************************************************
   */



  function detect_orfs($exp_id=null,$transcript_id=null){
    $this->layout = "";
    if(!$exp_id || !$transcript_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	 
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);	   
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]); 

    //check whether transcript is valid
    $transcript_id 	= mysql_real_escape_string($transcript_id);
    $transcript_info    = $this->Transcripts->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
    //pr($transcript_info);
    if(!$transcript_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}    



  }


  function transcript($exp_id=null,$transcript_id=null){
    
    if(!$exp_id || !$transcript_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	 
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);	   
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]); 

    //check whether transcript is valid
    $transcript_id 	= mysql_real_escape_string($transcript_id);
    $transcript_info    = $this->Transcripts->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
    //pr($transcript_info);
    if(!$transcript_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}    
    if($transcript_info['Transcripts']['orf_sequence'] !=""){
      $transcript_info['Transcripts']['aa_sequence'] = $this->Sequence->translate_cds_php($transcript_info['Transcripts']['orf_sequence']);
    }
  
    //check whether the number of jobs in the queue for this experiment has not been reached.
    $current_job_number = $this->ExperimentJobs->getNumJobs($exp_id);
    if($current_job_number>=MAX_CLUSTER_JOBS){$this->set("max_number_jobs_reached",true);}


    //CHANGES TO THE INITIAL DATA. SOME OTHER POST_PROCESSING STEPS MIGHT BE NECESSARY!
    //put it here, as it might influence the later results. Also reload the transcript info
    if($_POST){           
      if(array_key_exists("orf_sequence",$_POST)){       
	$this->Transcripts->updateAll(array("orf_sequence"=>"'".mysql_real_escape_string($_POST['orf_sequence'])."'"),array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id));
	$this->Transcripts->updateCodonStats($exp_id,$transcript_id,$_POST['orf_sequence']);
	$this->ExperimentLog->addAction($exp_id,"change_orf_sequence",$transcript_id);    
      }
      if(array_key_exists("transcript_sequence",$_POST)){
	$this->Transcripts->updateAll(array("transcript_sequence"=>"'".mysql_real_escape_string($_POST['transcript_sequence'])."'"),array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id));
	$this->ExperimentLog->addAction($exp_id,"change_transcript_sequence",$transcript_id);  
      }
      if(array_key_exists("corrected_sequence",$_POST)){
	$this->Transcripts->updateAll(array("transcript_sequence_corrected"=>"'".mysql_real_escape_string($_POST['corrected_sequence'])."'"),array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id));
	$this->ExperimentLog->addAction($exp_id,"change_corrected_sequence",$transcript_id);  
      }
      if(array_key_exists("meta_annotation",$_POST)){
	$this->Transcripts->updateAll(array("meta_annotation"=>"'".mysql_real_escape_string($_POST['meta_annotation'])."'"),array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id));
	$this->ExperimentLog->addAction($exp_id,"change_meta_annotation",$transcript_id);  
      }
      if(array_key_exists("subsets",$_POST) && $_POST["subsets"]=="subsets"){
	$available_subsets	= $this->TranscriptsLabels->getLabels($exp_id);
	$transcript_subsets	= $this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
    	$transcript_subsets	= $this->TrapidUtils->reduceArray($transcript_subsets,"TranscriptsLabels","label");
	//check for new subset
	if(array_key_exists("new_subset",$_POST) && $_POST['new_subset']=="on" && array_key_exists("new_subset_name",$_POST)){
	  $new_subset		= mysql_real_escape_string($_POST['new_subset_name']);
	  $save_data		= array(array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id,"label"=>$new_subset)); 	
	  $this->TranscriptsLabels->saveAll($save_data);
	}
	//check for addition of transcript to existing subsets
	$save_data		= array();
	foreach($_POST as $k=>$v){
	  if($v=="on" && array_key_exists($k,$available_subsets) && !in_array($k,$transcript_subsets)){
	    $save_data[]	= array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id,"label"=>$k);
	  }
	}
	if(count($save_data)>0){$this->TranscriptsLabels->saveAll($save_data);}

	//check for deletion of transcript from existing subsets
	foreach($transcript_subsets as $subset){
	  if(!array_key_exists($subset,$_POST)){	//deletion of subset
		$this->TranscriptsLabels->query("DELETE FROM `transcripts_labels` WHERE `experiment_id`='".$exp_id."' AND `transcript_id`='".$transcript_id."' AND `label`='".$subset."' ");
	  }	
	}
	//$available_subsets	= $this->TranscriptsLabels->getLabels($exp_id);
	//$this->set("available_subsets",$available_subsets);
      }      
      //update edit data of the experiment!
      $this->Experiments->updateAll(array("last_edit_date"=>"'".date("Y-m-d H:i:s")."'"),array("experiment_id"=>$exp_id));
      //$this->redirect(array("controller"=>"trapid","action"=>"transcript",$exp_id,$transcript_id));
      $transcript_info = $this->Transcripts->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
    }

    $this->set("transcript_info",$transcript_info['Transcripts']);
    //pr($transcript_info['Transcripts']);
    //go and interpro information
    $associated_go	= $this->TranscriptsGo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));    
    $go_ids		= $this->TrapidUtils->reduceArray($associated_go,"TranscriptsGo","go");
    //TODO!!
    $go_information	= $this->ExtendedGo->retrieveGoInformation($go_ids);
    $this->set("associated_go",$associated_go);
    $this->set("go_info",$go_information);	

    $associated_interpro	= $this->TranscriptsInterpro->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));    
    $interpros	= $this->TrapidUtils->reduceArray($associated_interpro,"TranscriptsInterpro","interpro");
    $interpro_information = $this->ProteinMotifs->retrieveInterproInformation($interpros);
    $this->set("associated_interpro",$associated_interpro);
    $this->set("interpro_info",$interpro_information);

  
    //subset information
    $available_subsets	= $this->TranscriptsLabels->getLabels($exp_id);
    $transcript_subsets	= $this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
    $transcript_subsets	= $this->TrapidUtils->reduceArray($transcript_subsets,"TranscriptsLabels","label");
    $this->set("available_subsets",$available_subsets);
    $this->set("transcript_subsets",$transcript_subsets);   
   

  }
  

 

  /*
   * TODO further implement method, and take care in the possible parameters data structure to also indicate the 
   * necessary joins and required tables. 
   */
  function transcript_selection(){
    //Configure::write("debug",1);
    $num_parameters	= func_num_args();
    if($num_parameters < 3 || $num_parameters%2==0 ){$this->redirect("/");}
    $parameters		= func_get_args();
    $exp_id		= mysql_real_escape_string($parameters[0]);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    // pr($parameters);	
    for($i=0;$i<count($parameters);$i++){
      $value	= $parameters[$i];
      if($value=="go" && $i<(count($parameters)-1)){
	$next_value	= $parameters[$i+1];
	$parameters[$i+1] = str_replace("-",":",$next_value);	
      }
      $parameters[$i] = urldecode($value);
    }
    
    // foreach($parameters as $k=>$v){
    // if($v=="go"){$parameters[$k+1] = str_replace("-",":",$parameters[$k+1]);	}
    // $parameters[$k] = urldecode($v);
    //}    
    //pr($parameters);
   
    $download_type		= null;
    $available_download_types	= array("table","fasta_transcript","fasta_orf","fasta_protein_ref");
    if($_POST){
      if(array_key_exists("download_type",$_POST) && in_array($_POST['download_type'],$available_download_types)){
	$download_type		= $_POST["download_type"];
      }
    }
       
    //if download, get all of the transcripts.
    if($download_type!=null){
      $this->layout = "";
      $this->set("download_type",$download_type);
      $this->paginate['TranscriptsPagination']['limit'] = $exp_info['transcript_count'];
    }    
            
    //ok, now retrieve the transcripts
    $transcript_ids	= $this->paginate("TranscriptsPagination",$parameters);	    
    $transcripts	= $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids)));	
     $this->set("transcript_data",$transcripts);
    //if there is a download option, we should see now whether or not extra data retrieval is necessary.
     if($download_type=="fasta_transcript"){$this->set("file_name","transcripts_".implode("_",$parameters).".fasta");return;}
     if($download_type=="fasta_orf"){$this->set("file_name","orf_".implode("_",$parameters).".fasta");return;}
     if($download_type=="fasta_protein_ref"){ //should only be accesible from a GF page, or any other with only a single restriction
       $this->set("file_name","protein_".implode("_",$parameters).".fasta");
	//retrieve reference information. 	
	$reference_sequences	= $this->getReferenceSequences($exp_id,$parameters[1],$parameters[2]);
	$this->set("reference_sequences",$reference_sequences);
	$trapid_sequences	= $this->getTrapidSequences($exp_id,$transcripts);
	$this->set("trapid_sequences",$trapid_sequences);	
	return;
     }
      
	    

    $parsed_parameters	= $this->TranscriptsPagination->getParsedParameters($parameters);   	        
     //retrieve functional annotation for the table       
    $transcripts_go	= $this->TrapidUtils->indexArray($this->TranscriptsGo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids,"is_hidden"=>"0"))),"TranscriptsGo","transcript_id","go");
    $go_info	= array();
    if(count($transcripts_go)!=0){
	    $go_ids		=  array_unique(call_user_func_array("array_merge",array_values($transcripts_go)));  
	    $go_info        = $this->ExtendedGo->retrieveGoInformation($go_ids);       
    }

    $transcripts_ipr= $this->TrapidUtils->indexArray($this->TranscriptsInterpro->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids))),"TranscriptsInterpro","transcript_id","interpro");
    $ipr_info	= array();
    if(count($transcripts_ipr)!=0){
	    $ipr_ids        = array_unique(call_user_func_array("array_merge",array_values($transcripts_ipr))); 
	    $ipr_info	= $this->ProteinMotifs->retrieveInterproInformation($ipr_ids);
    }

    //retrieve subset/label information
    $transcripts_labels	= $this->TrapidUtils->indexArray($this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids))),"TranscriptsLabels","transcript_id","label");

	
    $this->set("parameters",$parsed_parameters);   
    $this->set("transcripts_go",$transcripts_go);
    $this->set("transcripts_ipr",$transcripts_ipr);
    $this->set("transcripts_labels",$transcripts_labels);
    $this->set("go_info_transcripts",$go_info);
    $this->set("ipr_info_transcripts",$ipr_info);	

    if($download_type=="table"){$this->set("file_name","table_".implode("_",$parameters).".txt");return;}	    
  }


  function getTrapidSequences($exp_id,$transcript_data){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $seq_type	= $this->DataSources->find("first",array("conditions"=>array("name"=>$exp_info['datasource']),"fields"=>"seq_type"));
    $seq_type 	= $seq_type['DataSources']['seq_type'];	
    $result	= array();
    foreach($transcript_data as $td){
      $transcript_id		= $td['Transcripts']['transcript_id'];
      $transcript_sequence	= $td['Transcripts']['orf_sequence'];
      $result[$transcript_id]	= $transcript_sequence;
    }	
    $result	= $this->Sequence->translate_multicds_php($result);
    return $result;
  }


  function getReferenceSequences($exp_id,$param_type,$param_value){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $seq_type	= $this->DataSources->find("first",array("conditions"=>array("name"=>$exp_info['datasource']),"fields"=>"seq_type"));
    $seq_type 	= $seq_type['DataSources']['seq_type'];
    $gf_type	= $exp_info['genefamily_type'];
    //ok, necessary info has been gathered.
    $result	= array(); //identifier to sequence
    if($param_type=="gf_id"){
      if($gf_type=="HOM"){
	//get HOM id
	$plaza_gf_id	= $this->GeneFamilies->find("first",array("conditions"=>array("gf_id"=>$param_value),"fields"=>"plaza_gf_id"));
	$result		= $this->Annotation->getSequencesGf($plaza_gf_id['GeneFamilies']['plaza_gf_id']);
      }
      else{
	//iOrtho group content
	$iortho_content	= $this->GeneFamilies->find("first",array("conditions"=>array("gf_id"=>$param_value),"fields"=>"gf_content"));
	$iortho_content	= explode(" ",$iortho_content['GeneFamilies']['gf_content']);
	$result		= $this->Annotation->getSequences($iortho_content);
      }     
    }
    else if($param_type=="go"){
      $result	= $this->Annotation->getSequencesGo($param_value);	     
    }
    else if($param_type=="interpro"){
      $result	= $this->Annotation->getSequencesInterpro($param_value);	
    }

    //convert to protein sequences
    if($seq_type=="DNA"){
      $result	= $this->Sequence->translate_multicds_php($result);
    }		
    return $result;	  
  }





  /******************************************************************************************************
   *
   * SEARCH RESULT PAGES :
   *
   ******************************************************************************************************
   */



  function search($exp_id=null){
    //Configure::write("debug",2);
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    //	pr($exp_info); 


    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);       
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    if(!$_POST){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}	
    if(!array_key_exists("search_type",$_POST) || !array_key_exists("search_value",$_POST)){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}	
    $mvc = false;
    if(array_key_exists("multiple_values_check",$_POST) && $_POST['multiple_values_check']=='on'){
      $mvc = true;
    }
    $this->set("mvc",$mvc);    	
    $st	= mysql_real_escape_string(trim($_POST['search_type']));
    $sv = mysql_real_escape_string(trim($_POST['search_value']));
    $sv_array = array($sv);
    if($mvc){
      $sv_array		= array();
      $sv_array_tmp 	= explode("\n",$_POST['search_value']);
      foreach($sv_array_tmp as $sat){
	$tmp		= mysql_real_escape_string(trim($sat));
	if($tmp!=""){$sv_array[] 	= $tmp;}
      }     
    }
    $sv = implode("\n",$sv_array);
               
    $this->set("search_type",$st);
    $this->set("search_value",$sv);
    if(strlen($sv)==0){$this->set("search_result","bad_search");return;}  

    //check when multiple values, in order to not allow too big searches
    $MAX_MVC_VALUES	= 200;
    if($mvc && count($sv_array)>$MAX_MVC_VALUES){
      $this->set("search_result","bad_search");
      $this->set("error","Too many identifiers submitted");
      //pr("here");
      return;
    } 

  
    //first do only the multiple lines 
    //return in order to not have to deal with the following lines
    if($mvc){
      if($st=="transcript"){
	$transcripts_info	= $this->Transcripts->getBasicInformationTranscripts($exp_id,$sv_array);
	if(!$transcripts_info){$this->set("search_result","bad_search");return;}
	else{$this->set("search_result","transcript");$this->set("transcripts_info",$transcripts_info);return;}
      }
      else if($st=="gene"){
	//once again some kind of clusterfuck based on the code for a single gene below
	$genes_info		= array();
	foreach($sv_array as $sa){
		if($exp_info['genefamily_type'] == "IORTHO"){
			$gf_info	= $this->GeneFamilies->findByGene($exp_id,$sa);
			if($gf_info){$genes_info[$sa] = $gf_info;}			
		}
		else if($exp_info['genefamily_type'] == 'HOM'){
		  $genes			= $this->Annotation->find("first",array("fields"=>array("gene_id"),"conditions"=>array("gene_id"=>$sa))); 
			if($genes){	
				$gene_id	= $genes['Annotation']['gene_id'];
        			$gene_family	= $this->GfData->query("SELECT `gf_id` FROM `gf_data` WHERE `gene_id`='".$gene_id."' AND `gf_id` LIKE '".$exp_info['gf_prefix']."%'");				
				if($gene_family){
					$gf_id		= $gene_family[0]['gf_data']['gf_id'];					
					$gf_info	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"plaza_gf_id"=>$gf_id),"fields"=>array("gf_id","plaza_gf_id","num_transcripts")));
					if($gf_info){
        					$gf_info	= $gf_info['GeneFamilies'];
					}
					else{
					  $gf_info	= array("gf_id"=>"","plaza_gf_id"=>$gf_id,"num_transcripts"=>"");
					}
					$genes_info[$sa] = $gf_info;					
				}
			}
		}	
	}	
	//pr($genes_info);
	if(!$genes_info){$this->set("search_result","bad_search");return;}
	else{$this->set("search_result","gene");$this->set("genes_info",$genes_info);return;}
      }
    }


    if($st=="transcript"){    
      $transcripts_info	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$sv))); 
      if(!$transcripts_info){$this->set("search_result","bad_search");return;}      
      else{$this->redirect(array("controller"=>"trapid","action"=>"transcript",$exp_id,urlencode($sv)));}
    }
    else if($st=="gene"){
      //okay, this becomes complicated based on the type of experiment. 
      //If iortho, we do a query on the gf_content row of the tabel gene families (not optimal)
      //if HOM, we do a query on the associated PLAZA database. This should be a relatively fast query, from which we can further on
      //deduct the associated gene family.
      if($exp_info['genefamily_type'] == "IORTHO"){
	$gf_info	= $this->GeneFamilies->findByGene($exp_id,$sv);      
	if(!$gf_info){$this->set("search_result","bad_search");return;}
        $this->set("search_result","gene");
	$this->set("gf_info",$gf_info);       
	return;
      }
      else if($exp_info['genefamily_type'] == 'HOM'){
	//find genes
	$genes		= $this->Annotation->find("first",array("conditions"=>array("gene_id"=>$sv)));
	if(!$genes){$this->set("search_result","bad_search");return;}
	$gene_id	= $genes['Annotation']['gene_id'];
        $gene_family	= $this->GfData->query("SELECT `gf_id` FROM `gf_data` WHERE `gene_id`='".$gene_id."' AND `gf_id` LIKE '".$exp_info['gf_prefix']."%'");
	if(!$gene_family){$this->set("search_result","bad_search");return;}
	$gf_id		= $gene_family[0]['gf_data']['gf_id'];
	$gf_info	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"plaza_gf_id"=>$gf_id),"fields"=>array("gf_id","plaza_gf_id","num_transcripts")));
        $gf_info	= $gf_info['GeneFamilies'];
	$this->set("search_result","gene");       
	$this->set("gf_info",$gf_info);
	return;
      }
      else{
	$this->redirect("/");
      }
    }
    else if($st=="gf"){
      $transcripts_info = $this->GeneFamilies->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$sv)));
      if(!$transcripts_info){$this->set("search_result","bad_search");return;}
      else{$this->redirect(array("controller"=>"gene_family","action"=>"gene_family",$exp_id,urlencode($sv)));}
    }
    else if($st=="GO"){
      //check on length of search value.
      if(strlen($sv) < 3){$this->set("search_result","bad_search");$this->set("error","Description should be 3 characters or more");return;}
      //find GO terms with this description
      $go_terms	= $this->ExtendedGo->find("all",array("conditions"=>array("`ExtendedGo.desc` LIKE '%$sv%'")));
      if(!$go_terms){$this->set("search_result","bad_search");$this->set("error","Unknown GO description");return;}
      $go_terms = $this->TrapidUtils->indexArraySimple($go_terms,"ExtendedGo","go","desc");    
      //ok, now find possible associated transcripts
      $transcripts_info = $this->TranscriptsGo->findTranscriptsFromGo($exp_id,$go_terms);
      if(!$transcripts_info){$this->set("search_result","bad_search");return;}
      $this->set("transcripts_info",$transcripts_info);
      $this->set("search_result","go");
      return;
    }
    else if($st=="interpro"){
      //check on length of search value
      if(strlen($sv) < 3){$this->set("search_result","bad_search");$this->set("error","Description should be 3 characters or more");return;}
      //find InterPro domains with this description       
      $ipr_terms = $this->ProteinMotifs->find("all",array("conditions"=>array("`ProteinMotifs.desc` LIKE '%$sv%' ")));
      if(!$ipr_terms){$this->set("search_result","bad_search");$this->set("error","Unknown InterPro description");return;}
      $ipr_terms = $this->TrapidUtils->indexArraySimple($ipr_terms,"ProteinMotifs","motif_id","desc");
      //ok, now find possibe associated transcripts
      $transcripts_info = $this->TranscriptsInterpro->findTranscriptsFromInterpro($exp_id,$ipr_terms);
      if(!$transcripts_info){$this->set("search_result","bad_search");return;}
      $this->set("transcripts_info",$transcripts_info);
      $this->set("search_result","interpro");
      return;
    }
    else if($st=="meta_annotation"){
      if(!($sv=="No Information" || $sv=="Partial" || $sv=="Full Length" || $sv=="Quasi Full Length")){
		$this->set("search_result","bad_search");
		return;
      }
      $this->redirect(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"meta_annotation",urlencode($sv)));
    }
    else{
      $this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
    }	
  }



  /*******************************************************************************************************
   *
   *  DATA PROCESSING
   * 
   ********************************************************************************************************/

  function initial_processing($exp_id=null){   
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);	
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id); 
    $user_group=$this->Authentication->find("first",array("fields"=>array("group"),"conditions"=>array("user_id"=>parent::check_user())));
    if($user_group['Authentication']['group'] != "admin"){
      $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["upload"]);       
    }
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
  
    $possible_db_types	= array("SINGLE_SPECIES"=>"Single Species","CLADE"=>"Phylogenetic Clade","GF_REP"=>"Gene Family Representatives");
    $possible_gf_types	= array("HOM"=>"Gene Families","IORTHO"=>"Integrative Orthology");    		 
    
    //retrieve species information for BLAST info   
    $species_info = $this->AnnotSources->getSpeciesCommonNames();    
    $data_sources = $this->DataSources->find("first",array("conditions"=>array("db_name"=>$exp_info["used_plaza_database"])));     
    $clades	= $this->TrapidUtils->valueToIndexArray(explode(";",$data_sources["DataSources"]["clades"]));	
    ksort($clades);           
    $species_info	= $this->TrapidUtils->checkAvailableRapsearchDB($exp_info['used_plaza_database'],$species_info);
    $clades		= $this->TrapidUtils->checkAvailableRapsearchDB($exp_info['used_plaza_database'],$clades);
    $gf_representatives	= $this->TrapidUtils->checkAvailableRapsearchDB($exp_info['used_plaza_database'],array("gf_representatives"=>"Genefamily representatives"));     
 
    $this->set("available_species",$species_info);   
    $this->set("clades_species",$clades);
    $this->set("gf_representatives",$gf_representatives);
    if(count($species_info)==0){$this->set("error","No valid species databases found. Please contact webadmin");}
    if(count($clades)==0 && count($species_info)>0){$this->set("error","No valid clades databases found. Please contact webadmin");}    
    if(count($species_info)==0){unset($possible_db_types["SINGLE_SPECIES"]);}
    if(count($clades)==0){unset($possible_db_types["CLADE"]);}
    if(count($gf_representatives)==0){unset($possible_db_types["GF_REP"]);}

    //pr($possible_db_types);

    //retrieve information for the gene family type
    if($data_sources['DataSources']['gf']==0){unset($possible_gf_types['HOM']);}
    if($data_sources['DataSources']['iortho']==0){unset($possible_gf_types['IORTHO']);}

    $this->set("possible_db_types",$possible_db_types);
    $this->set("possible_gf_types",$possible_gf_types);	  

    //possible e-values
    $possible_evalues	= array("10e-2"=>"-2","10e-3"=>"-3","10e-4"=>"-4",
				"10e-5"=>"-5","10e-6"=>"-6","10e-7"=>"-7",
				"10e-8"=>"-8","10e-9"=>"-9","10e-10"=>"-10");
    $this->set("possible_evalues",$possible_evalues);

    //possible func annots
    $possible_func_annot  = array(
				"gf"=>"Transfer based on gene family",
				"besthit"=>"Transfer from best similarity hit",
				"gf_besthit"=>"Transfer from both GF and best hit"
				);
    $this->set("possible_func_annot",$possible_func_annot);

    if($_POST){	      
      //pr($_POST);
      //parameter checking.
      if(!(array_key_exists("blast_db_type",$_POST) && array_key_exists("blast_db",$_POST)
	   && array_key_exists("blast_evalue",$_POST) && array_key_exists("gf_type",$_POST) 
	   && array_key_exists("functional_annotation",$_POST)
	)){
	$this->set("error","Incorrect parameters : missing parameters");return;
      }                  
      $num_blast_hits	= 1;
      $blast_db_type 	= mysql_real_escape_string($_POST['blast_db_type']);
      $blast_db		= mysql_real_escape_string($_POST['blast_db']);
      $blast_evalue	= mysql_real_escape_string($_POST['blast_evalue']);
      $gf_type		= mysql_real_escape_string($_POST['gf_type']);
      $func_annot	= mysql_real_escape_string($_POST['functional_annotation']);
      $used_blast_desc	= "";	
      if(!(array_key_exists($blast_db_type,$possible_db_types) && 
	   array_key_exists($gf_type,$possible_gf_types) && 
	   array_key_exists($blast_evalue,$possible_evalues) &&
	   array_key_exists($func_annot,$possible_func_annot)
	)){
	$this->set("error","Incorrect parameters: faulty parameters");return;
      }	     
      if($blast_db_type=="SINGLE_SPECIES"){
	if(!array_key_exists($blast_db,$species_info)){$this->set("error","Incorrect parameters: unknown species - ".$blast_db);return;}
	else{$used_blast_desc = $species_info[$blast_db];}
      }      
      if($blast_db_type=="CLADE"){
	if(!array_key_exists($blast_db,$clades)){$this->set("error","Incorrect parameters : unknown clade - ".$blast_db);return;}
	else{$used_blast_desc = $clades[$blast_db];}
      }
      if($blast_db_type=="GF_REP"){
	if($blast_db!="gf_representatives"){$this->set("error","Incorrect parameters : wrong GF_REP DB - ".$blast_db);return;}       
      }
      if($gf_type=="IORTHO" && $blast_db_type!="SINGLE_SPECIES"){
	$this->set("error","Incorrect parameters : IORTHO conflict");return;
      }

      if($blast_db_type=="SINGLE_SPECIES"){$num_blast_hits=1;}
      else if($blast_db_type=="CLADE"){$num_blast_hits=1;}
      else if($blast_db_type=="GF_REP"){$num_blast_hits=5;}
                
      //pr($_POST);
      //return;


      //parameters are ok. we can now proceed with the actual pipeline organization.    
      //create shell file for submission to the web-cluster.
      //Shell file contains both the necessary module load statements
      //as well as the correct name for the global perl-file.
      //A single "initial processing" job should only run on a single cluster-node   
      $qsub_file  = $this->TrapidUtils->create_qsub_script($exp_id);     
      $shell_file = $this->TrapidUtils->create_shell_file_initial($exp_id,$exp_info['used_plaza_database'],$blast_db,$gf_type,$num_blast_hits,$possible_evalues[$blast_evalue],$func_annot);
      if($shell_file == null || $qsub_file == null ){$this->set("error","problem creating program files");return;}	          
     
      //ok, now we submit this program to the web-cluster
      $tmp_dir	= TMP."experiment_data/".$exp_id."/";
      $qsub_out	= $tmp_dir."initial_processing.out";
      $qsub_err	= $tmp_dir."initial_processing.err";     
      if(file_exists($qsub_out)){unlink($qsub_out);}
      if(file_exists($qsub_err)){unlink($qsub_err);}
      
      $output   = array();
      $command  = "sh $qsub_file -q medium -o $qsub_out -e $qsub_err $shell_file";
      exec($command,$output);
      $job_id	= $this->TrapidUtils->getClusterJobId($output);
      
      //indicate int the database the new job-id
      $this->ExperimentJobs->addJob($exp_id,$job_id,"long","initial_processing");

      //indicate in the database that the current experiment is "busy", and should as such not be accesible.
      $this->Experiments->updateAll(array("process_state"=>"'processing'","genefamily_type"=>"'".$gf_type."'","last_edit_date"=>"'".date("Y-m-d H:i:s")."'","used_blast_database"=>"'".$possible_db_types[$blast_db_type]."/".$used_blast_desc."'"),array("experiment_id"=>$exp_id));      
      if($gf_type=="IORTHO"){
	$this->Experiments->updateAll(array("target_species"=>"'".$blast_db."'"),array("experiment_id"=>$exp_id));
      }

      $this->ExperimentLog->addAction($exp_id,"initial_processing","");
      $this->ExperimentLog->addAction($exp_id,"initial_processing","options",1);		
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","blast_db_type=".$blast_db_type,2);
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","blast_db=".$blast_db,2);
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","e_value=".$blast_evalue,2);
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","gf_type=".$gf_type,2);
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","func_annot=".$func_annot,2);
      $this->ExperimentLog->addAction($exp_id,"initial_processing","start",1); 	

      //finally, redirect
      $this->redirect(array("controller"=>"trapid","action"=>"experiments"));
    }   
  }









  /*******************************************************************************************************
   *
   *  DATA IMPORT / EXPORT
   * 
   ********************************************************************************************************/



  /**
   * Function to import transcript-data. Either multi-fasta files (normal) or zipped files.
   * For zipped files, we apply some extra tests to see whether we're not dealing with a zip-bomb.
   */
  function import_data($exp_id=null){	
    //Configure::write("debug",2);
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["start"]);       
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    //this is a basis location for creating the temp storage for an experiment.
    $tmp_dir	= TMP."experiment_data/".$exp_id."/";	
    if(!file_exists($tmp_dir) || !is_dir($tmp_dir)){mkdir($tmp_dir,0777);}
    shell_exec("chmod a+w $tmp_dir");    
    $upload_dir = $tmp_dir."upload_files/";
    if(!file_exists($upload_dir) || !is_dir($upload_dir)){mkdir($upload_dir,0777);}
    shell_exec("chmod a+w $upload_dir");
    
    //give an overview of the content of the directory, so the user doesn't upload the same file twice
    //$uploaded_files = $this->TrapidUtils->readDir($upload_dir);
    $uploaded_files = $this->DataUploads->findAll(array("user_id"=>$exp_info['user_id'],"experiment_id"=>$exp_id));
    $this->set("uploaded_files",$uploaded_files);  	
         
    $uploaded_files_index = $this->TrapidUtils->indexArrayMulti($uploaded_files,"DataUploads","id",
							array("user_id","experiment_id","type","name"));   
    
    if($_POST && array_key_exists("type",$_POST)){     
      //#########################################
      //     FILE OR URL UPLOAD
      //#########################################
      if($_POST["type"]=="upload_file" && array_key_exists("uploadtype",$_POST)){
	$label_name = null;
	if(array_key_exists("label_name",$_POST)){
	  //$label_name = mysql_real_escape_string($_POST['label_name']);
	  $label_name = Sanitize::paranoid($_POST['label_name'],array('.','_','-'));	 //also remove other characters b/o problems
	}
	//#####   URL ###########################
	if($_POST['uploadtype']=="url" && array_key_exists("uploadedurl",$_POST)){
	  $uploadurl = mysql_real_escape_string($_POST['uploadedurl']);	 
	  $this->DataUploads->saveAll(array(array("user_id"=>$exp_info['user_id'],"experiment_id"=>$exp_id,"type"=>"url",
						    "name"=>$uploadurl,"label"=>$label_name,"status"=>"to_download")));	  
	  $this->redirect(array("controller"=>"trapid","action"=>"import_data",$exp_id));  
	}
	//#####   FILE  ###########################  --> IMMEDIATELY UNZIP DATA!!!
	else if($_POST['uploadtype']=="file" && array_key_exists("uploadedfile",$_FILES)){
	   set_time_limit(180);  
	   $MAX_FILE_SIZE_NORMAL		= 32000000;
	   $MAX_FILE_SIZE_ZIP			= 32000000;
	   if(($_FILES['uploadedfile']['name']=="")){$this->set("error","Illegal input file (no name)");return;}
	   if(($_FILES['uploadedfile']['size'] == 0)){$this->set("error","Illegal input file (size is 0)");return;}
	   if(($_FILES['uploadedfile']['tmp_name'] == "")){$this->set("error","Illegal input file (no tmp_name)");return;}
	   $myFile 		= $_FILES['uploadedfile']['tmp_name'];
       	   $filename		= $_FILES['uploadedfile']['name'];			
	   //find file-extension of filename, and based on this decided what to do with it.
           $filename_d		= explode(".",$filename);
	   if(count($filename_d)==1){$this->set("error","No file extension detected. Please rename file.");return;}
	   $file_extension		= $filename_d[count($filename_d)-1];
	   if(count($filename_d)>=3 && $filename_d[count($filename_d)-2]=="tar"){$file_extension = "tar.gz";}
	   $normal_fasta_file	= true;
           if($file_extension=="fasta"||$file_extension=="tfa"||$file_extension=="fa"){$normal_fasta_file=true;}
	   else if($file_extension=="zip"||$file_extension=="gz" || $file_extension=="tar.gz"){$normal_fasta_file = false;}
	   else{
	      $this->set("error","Illegal file-name. Only allowed file-extensions: 'fasta','fa','tfa' (normal) ,'zip','gz' (compressed)");
	      return;
	   }	 	 
	   if($normal_fasta_file){
	  	//check on file size
	  	if($_FILES['uploadedfile']['size']>$MAX_FILE_SIZE_NORMAL){$this->set("error","File is too large");return;}    
	   }
	   else{
		if($_FILES['uploadedfile']['size']>$MAX_FILE_SIZE_ZIP){$this->set("error","File is too large");return;}
	   }
	    
	   //copy file to upload directory
	   shell_exec("cp ".$myFile." ".$upload_dir."".$_FILES['uploadedfile']['name']);
	   shell_exec("chmod a+rwx ".$upload_dir."".$_FILES['uploadedfile']['name']);	  			
	   $this->DataUploads->saveAll(array(array("user_id"=>$exp_info['user_id'],"experiment_id"=>$exp_id,"type"=>"file",
						    "name"=>$_FILES['uploadedfile']['name'],
						    "label"=>$label_name,"status"=>"uploaded")));	  
	   $this->redirect(array("controller"=>"trapid","action"=>"import_data",$exp_id));	   	
	}
	else{
	  $this->set("error","Invalid upload type");$this->set("uploaded_files",$uploaded_files);return;
	}
      }
      //#########################################
      //     FILE OR URL DELETION
      //#########################################
      else if($_POST["type"]=="delete_file"){       
	foreach($_POST as $k=>$v){
	  if(substr($k,0,3)=="id_" && $v=="on"){
	    $to_del=substr($k,3);
	    if(array_key_exists($to_del,$uploaded_files_index)){
	      if($uploaded_files_index[$to_del]["user_id"]==$exp_info["user_id"]&&$uploaded_files_index[$to_del]["experiment_id"]==$exp_id){
		$type = $uploaded_files_index[$to_del]["type"];
		//delete from database
		$this->DataUploads->query("DELETE FROM `data_uploads` WHERE `user_id`='".$exp_info["user_id"]."' AND 
			`experiment_id`='".$exp_id."' AND `id`='".$to_del."'");
		//if file: delete file as well
		if($type=="file"){
		  shell_exec("rm ".$upload_dir."".$uploaded_files_index[$to_del]["name"]);
		}		
	      }
	    }
	  }
	}	
	$this->redirect(array("controller"=>"trapid","action"=>"import_data",$exp_id));	
      }
      //#########################################
      //     DATABASE UPLOAD
      //#########################################
      else if($_POST["type"]=="database_file"){
	//create cluster job and submit
	$qsub_file  = $this->TrapidUtils->create_qsub_script($exp_id);    
    	$shell_file = $this->TrapidUtils->create_shell_file_upload($exp_id,$upload_dir);
    	if($shell_file == null || $qsub_file == null ){
		$this->set("error","Problem creating program files. Please contact webmaster.");
		return;
	}	          	    
      	$qsub_out	= $tmp_dir."upload.out";
      	$qsub_err	= $tmp_dir."upload.err";     
        if(file_exists($qsub_out)){unlink($qsub_out);}
        if(file_exists($qsub_err)){unlink($qsub_err);}

        $command  = "sh $qsub_file -q medium -o $qsub_out -e $qsub_err $shell_file";
	$output   = array();
        $qsub_submit = exec($command,$output);  	
	$job_id	= $this->TrapidUtils->getClusterJobId($output);
       
	//indicate in the database the new job-id
	$this->ExperimentJobs->addJob($exp_id,$job_id,"short","database_upload");

    	//indicate in the database that the current experiment is "busy", and should as such not be accesible.
        $this->Experiments->updateAll(array("process_state"=>"'loading_db'","last_edit_date"=>"'".date("Y-m-d H:i:s")."'"),
					array("experiment_id"=>$exp_id));

        $this->ExperimentLog->addAction($exp_id,"upload transcript data into DB","");       
	$this->redirect(array("controller"=>"trapid","action"=>"experiments"));
	//pr($shell_file);
	//return;
      }
      else{
	$this->set("error","No valid type defined");$this->set("uploaded_files",$uploaded_files);return;
      }
    }
  }




  /**
   * Function to import transcript-data. Either multi-fasta files (normal) or zipped files.
   * For zipped files, we apply some extra tests to see whether we're not dealing with a zip-bomb.
   */
  function import_data_old($exp_id=null){
    //Configure::write("debug",2);	

    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["start"]);       
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    //this is a basis location for creating the temp storage for an experiment.
    $tmp_dir	= TMP."experiment_data/".$exp_id."/";	
    if(!file_exists($tmp_dir) || !is_dir($tmp_dir)){
	mkdir($tmp_dir,0777);
	shell_exec("chmod a+w $tmp_dir");
    }
    
    if($_POST){	

        set_time_limit(180);       

	$MAX_FILE_SIZE_NORMAL		= 32000000;
	$MAX_FILE_SIZE_ZIP		= 32000000;
	$MAX_FILE_SIZE_ZIP_EXPANDED	= 100000000;

        //check uploaded file
      	if(!isset($_FILES["uploadedfile"])){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}	       		
	if(($_FILES['uploadedfile']['name']=="")){$this->set("error","Illegal input file (no name)");return;}
	if(($_FILES['uploadedfile']['size'] == 0)){$this->set("error","Illegal input file (size is 0)");return;}
	if(($_FILES['uploadedfile']['tmp_name'] == "")){$this->set("error","Illegal input file (no tmp_name)");return;}
      			
	$myFile 		= $_FILES['uploadedfile']['tmp_name'];
	$filename		= $_FILES['uploadedfile']['name'];		
	
	//find file-extension of filename, and based on this decided what to do with it.
        $filename_d		= explode(".",$filename);
	if(count($filename_d)==1){$this->set("error","No file extension detected. Please rename file.");return;}
	$file_extension		= $filename_d[count($filename_d)-1];
	if(count($filename_d)>=3 && $filename_d[count($filename_d)-2]=="tar"){$file_extension = "tar.gz";}
	$normal_fasta_file	= true;
        if($file_extension=="fasta"||$file_extension=="tfa"||$file_extension=="fa"){$normal_fasta_file=true;}
	else if($file_extension=="zip"||$file_extension=="gz" || $file_extension=="tar.gz"){$normal_fasta_file = false;}
	else{
	  $this->set("error","Illegal file-name. Only allowed file-extensions: 'fasta','fa','tfa' (normal) ,'zip','gz' (compressed)");
	  return;
	}
	
	$transcripts		= array();
	$delete_temp_file	= false;
	if($normal_fasta_file){
	  //check on file size
	  if($_FILES['uploadedfile']['size']>$MAX_FILE_SIZE_NORMAL){$this->set("error","File is too large");return;}    
	  //copy file to upload directory
	  shell_exec("cp ".$myFile." ".$tmp_dir."uploaded.fasta");
	  $myFile = $tmp_dir."uploaded.fasta";
	  $delete_temp_file = true;
	}
	else{
	  //check on file size
	  if($_FILES['uploadedfile']['size']>$MAX_FILE_SIZE_ZIP){$this->set("error","File is too large");return;}	 	  
	  if($file_extension=="gz" || $file_extension=="tar.gz"){
	    //check uncompressed file size
	    $file_info	= explode("\n",shell_exec("gzip -l ".$myFile));	   
	    //pr($file_info);return;
	    if(count($file_info)<2){$this->set("error","Corrupt archive");return;}	    
	    $file_info	= explode(" ",$file_info[1]);
	    $fi	= array();
	    foreach($file_info as $f){if($f!=""){$fi[]=$f;}}
	    if($f[1]>$MAX_FILE_SIZE_ZIP_EXPANDED){$this->set("error","Uncompressed file is too large");return;}			    
	    //extract file	   
	    if($file_extension=="gz"){
	    	shell_exec("gunzip -c ".$myFile." > ".$tmp_dir."unzipped.fasta");	  
	    }
	    else if($file_extension=="tar.gz"){
	      shell_exec("tar -zxvOf ".$myFile." > ".$tmp_dir."unzipped.fasta");
	    }
	    $myFile = $tmp_dir."unzipped.fasta";  
	    $delete_temp_file = true;	    
	  }	 
	  else if($file_extension=="zip"){
	    //check uncompressed file size
	    $file_info	= explode("\n",shell_exec("zipinfo ".$myFile));			   
	    if(count($file_info)!=5){$this->set("error","Corrupt archive");return;}
	    $file_info = explode(",",$file_info[3]);
	    $file_info = explode(" ",$file_info[1]);
	    $file_size = $file_info[1];
	    //pr($file_size);return;
	    if($file_size>$MAX_FILE_SIZE_ZIP_EXPANDED){$this->set("error","Uncompressed file is too large");return;}
	    //extract file	  
	    shell_exec("unzip -p ".$myFile." > ".$tmp_dir."unzipped.fasta");
	    $myFile = $tmp_dir."unzipped.fasta";
	    $delete_temp_file = true;
	  }		
	}	
	
	//right, now we should have a multi-fasta file, with path at $myFile.
	//we now just attempt to read it, and check whether each line is actually OK.
	//but first: dos2unix to prevent any stupid characters to give trouble
	shell_exec("dos2unix ".$myFile);
       

	$fh			= fopen($myFile,'r');
	if(!$fh){$this->set("error","Cannot open file");return;}
       	$transcripts		= array();
	$current_transcript	= null;
	$current_seq		= "";
	$all_transcript_ids	= array();	       
	while(($buffer = fgets($fh)) !== false){	//read line per line instead of entire file at once, to prevent memory issues
	     if(substr($buffer,0,1)==">"){
	       	  if($current_transcript!=null){
			if(count($transcripts)>1000){//reduce out of memory problems	       
				$this->Transcripts->saveAll($transcripts);
	      			$transcripts	= array();
	      		}
	      		$transcripts[] = array("experiment_id"=>$exp_id,"transcript_id"=>$current_transcript,
						"transcript_sequence"=>$current_seq);
	      		$all_transcript_ids[] = $current_transcript;
	       	  }
		  //initiate new variables
	    	  $current_transcript = mysql_real_escape_string(trim(substr($buffer,1)));
	    	  if(strpos($current_transcript," ")!== false){
			$current_transcript=substr($current_transcript,0,strpos($current_transcript," "));
		  }
	    	  if(strpos($current_transcript,"|")!== false){
			$current_transcript=substr($current_transcript,0,strpos($current_transcript,"|"));
		  }
	    	  $current_seq	= "";	  
	     }
	     else{
	    	$current_seq	= $current_seq.(Sanitize::paranoid(trim($buffer)));	
	     }	     
	}	
	if($current_transcript!=null){
	  $transcripts[] = array("experiment_id"=>$exp_id,"transcript_id"=>$current_transcript,"transcript_sequence"=>$current_seq);
	  $all_transcript_ids[]	= $current_transcript;		 
	}	   	
	//store data in the database
	$this->Transcripts->saveAll($transcripts);
	fclose($fh);	//close file handler		

	//update dates and process state for the experiment
	$this->Experiments->updateAll(	array("last_edit_date"=>"'".date("Y-m-d H:i:s")."'","process_state"=>"'upload'"),
		array("experiment_id"=>$exp_id));     
	$this->ExperimentLog->addAction($exp_id,"transcript_upload",$_FILES['uploadedfile']['name']);            

	//store the label information, if defined
	if(array_key_exists("label_name",$_POST)){
	  $label_name	= mysql_real_escape_string($_POST['label_name']);
	  $this->TranscriptsLabels->enterTranscripts($exp_id,$all_transcript_ids,$label_name);
	}



	//delete temp-file if necessary
	if($delete_temp_file){
	  unlink($myFile);
	}

	//redirect to experiment
	//return;
	$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));      
    }              
  }



  function import_labels($exp_id=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);       
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $MAX_FILE_SIZE_NORMAL		= 2000000;	

    if($_POST){
	if(!isset($_FILES["uploadedfile"])){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}	       		
        if(($_FILES['uploadedfile']['name']=="") || ($_FILES['uploadedfile']['size'] == 0) ||($_FILES['uploadedfile']['tmp_name'] == "")){
	  $this->set("error","Illegal input file");return;
	}
	
	if(!isset($_POST['label'])||$_POST['label']==""){$this->set("error","No label defined");return;}
	//$label	= mysql_real_escape_string($_POST['label']);
	$label 		= Sanitize::paranoid($_POST['label'],array('.','_','-'));

	if($_FILES['uploadedfile']['size']>$MAX_FILE_SIZE_NORMAL){$this->set("error","File is too large");return;}    
	$myFile 		= $_FILES['uploadedfile']['tmp_name'];	
	$fh 			= fopen($myFile,'r');
    	$transcripts_input	= fread($fh,filesize($myFile));   
       
    	$transcripts		= preg_split("/[\s,]+/",$transcripts_input);       
	$transcripts		= array_unique($transcripts);
	fclose($fh);    
        //check correctness of both the transcripts and whether label already exists for the indicated transcripts
	$counter = $this->TranscriptsLabels->enterTranscripts($exp_id,$transcripts,$label);
        $this->set("message",$counter." transcripts have been labeled as '".$label."' ");
	$this->ExperimentLog->addAction($exp_id,"label_definition",$label);        
	return;    
    }
  }









  function export_data($exp_id=null){
    // Configure::write("debug",2);
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);   
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $plaza_database	= $exp_info['used_plaza_database'];
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);       
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);	

    $structural_export = array("transcript_id"=>"Transcript identifier","frame_info"=>"Frame information","frameshift_info"=>"Frameshift information","orf"=>"ORF information","meta_annotation"=>"Meta annotation");
    $structural_export_cols = array(	"transcript_id"=>array("transcript_id"),
					"frame_info"=>array("detected_frame","detected_strand","full_frame_info"), 
					"frameshift_info"=>array("putative_frameshift","is_frame_corrected"),
					"orf"=>array("orf_start","orf_stop","orf_contains_start_codon","orf_contains_stop_codon"),
					"meta_annotation"=>array("meta_annotation","meta_annotation_score")
					);
    $this->set("structural_export",$structural_export);

    $available_subsets		= $this->TranscriptsLabels->getLabels($exp_id);
    $this->set("available_subsets",$available_subsets);

    if($_POST){
      // pr($_POST);
      set_time_limit(180);      
      $user_id		= $this->Cookie->read("email");

      if(!array_key_exists("export_type",$_POST)){return;}
      $export_type	= $_POST["export_type"];     
      $this->set("export_type",$export_type);
      if($export_type=="structural"){
	//get columns for export
	$columns	= array();	
	foreach($_POST as $k=>$v){	  
	  if(array_key_exists($k,$structural_export_cols)){foreach($structural_export_cols[$k] as $col){$columns[]=$col;}}
	}

	$columns_string	= implode(',',$columns);
	$file_path	= $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"STRUCTURAL","structural_data_exp".$exp_id.".txt",$columns_string);
	$this->set("file_path",$file_path);		
	return;
      }
      else if($export_type=="sequence"){
	
	if(!array_key_exists("sequence_type",$_POST)){return;}
	$sequence_type	= $_POST['sequence_type'];
	$file_path	= null;
	if($sequence_type=="original"){
	  $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"SEQ_TRANSCRIPT","transcripts_exp".$exp_id.".fasta");	 
	}
	else if($sequence_type=="orf"){
	 $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"SEQ_ORF","orfs_exp".$exp_id.".fasta");	
	}
	else if($sequence_type=="aa"){
	 $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"SEQ_AA","proteins_exp".$exp_id.".fasta");    
	}
	$this->set("file_path",$file_path);
        return;
      }
      else if($export_type=="gf"){
	if(!array_key_exists("gf_type",$_POST)){return;}
	$gf_type	= $_POST['gf_type'];
	$file_path	= null;
	if($gf_type=="transcript"){
	  $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"TRANSCRIPT_GF","transcripts_gf_exp".$exp_id.".txt");
	}
	else if($gf_type=="phylo"){
	  $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"GF_TRANSCRIPT","gf_transcripts_exp".$exp_id.".txt");
	}  
	else if($gf_type=="reference"){
	  $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"GF_REFERENCE","gf_reference_exp".$exp_id.".txt");
	}

	$this->set("file_path",$file_path);
        return;    
      }
      else if($export_type=="go" || $export_type=="interpro"){	
	if(!array_key_exists("functional_type",$_POST)){return;}
	$functional_type	= $_POST['functional_type'];
 	$file_path		= null;
	if($functional_type=="transcript_go"){	
	  $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"TRANSCRIPT_GO","transcripts_go_exp".$exp_id.".txt");
	}
	else if($functional_type=="meta_go"){
	  $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"GO_TRANSCRIPT","go_transcripts_exp".$exp_id.".txt");
	}
	else if($functional_type=="transcript_ipr"){
	 $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"TRANSCRIPT_INTERPRO","transcripts_interpro_exp".$exp_id.".txt");
	}
	else if($functional_type=="meta_ipr"){
	 $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"INTERPRO_TRANSCRIPT","interpro_transcripts_exp".$exp_id.".txt");
	}
	$this->set("file_path",$file_path);
        return;
      }
      else if($export_type=="subsets"){
	if(!array_key_exists("subset_label",$_POST)){return;}
	$subset_label		= mysql_real_escape_string($_POST['subset_label']);       
	if(!array_key_exists($subset_label,$available_subsets)){return;}
        $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"TRANSCRIPT_LABEL",$subset_label."_transcripts_exp".$exp_id.".txt",$subset_label); 
	//pr($file_path);
	$this->set("file_path",$file_path);
      }
      else{
	return;
      }
    }
			      
  }










  function empty_experiment($exp_id=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);     
    //delete everything from all tables for this experiment
    $this->TranscriptsGo->query("DELETE FROM `transcripts_go` WHERE `experiment_id`='".$exp_id."'");	 
    $this->TranscriptsInterpro->query("DELETE FROM `transcripts_interpro` WHERE `experiment_id`='".$exp_id."'");
    $this->GeneFamilies->query("DELETE FROM `gene_families` WHERE `experiment_id`='".$exp_id."'");
    $this->TranscriptsLabels->query("DELETE FROM `transcripts_labels` WHERE `experiment_id`='".$exp_id."'");
    $this->Transcripts->query("DELETE FROM `transcripts` WHERE `experiment_id`='".$exp_id."'");
    $this->Similarities->query("DELETE FROM `similarities` WHERE `experiment_id`='".$exp_id."'");
    $this->DataUploads->query("DELETE FROM `data_uploads` WHERE `experiment_id`='".$exp_id."'");
   
    //first things first: delete all the jobs from the cluster, which are attached to this experiment
    //this will prevent overloading of the cluster system by malignant people constantly creating new experiments with new jobs, 
    //and subsequently deleting them
    $jobs	= $this->ExperimentJobs->getJobs($exp_id);
    foreach($jobs as $job){$job_id=$job['job_id'];$this->TrapidUtils->deleteClusterJob($exp_id,$job_id);}
    $this->ExperimentJobs->query("DELETE FROM `experiment_jobs` WHERE `experiment_id`='".$exp_id."'");

    $this->Experiments->updateAll(array("last_edit_date"=>"'".date("Y-m-d H:i:s")."'","process_state"=>"'empty'"),
		array("experiment_id"=>$exp_id));  
    $this->ExperimentLog->addAction($exp_id,"empty_experiment","");  

    //remove directory from the temp storage 
    $tmp_dir	= TMP."experiment_data/".$exp_id."/";	
    if(file_exists($tmp_dir) && is_dir($tmp_dir)){       
	shell_exec("rm -rf $tmp_dir/*");
    }	
    //return;


    //redirect
    $this->redirect(array("controller"=>"trapid","action"=>"experiments"));
  }



  function delete_experiment($exp_id=null){
    $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]); 	
    //delete everything from all tables for this experiment	
    $this->TranscriptsGo->query("DELETE FROM `transcripts_go` WHERE `experiment_id`='".$exp_id."'");	 
    $this->TranscriptsInterpro->query("DELETE FROM `transcripts_interpro` WHERE `experiment_id`='".$exp_id."'");
    $this->GeneFamilies->query("DELETE FROM `gene_families` WHERE `experiment_id`='".$exp_id."'");
    $this->TranscriptsLabels->query("DELETE FROM `transcripts_labels` WHERE `experiment_id`='".$exp_id."'");
    $this->Transcripts->query("DELETE FROM `transcripts` WHERE `experiment_id`='".$exp_id."'");
    $this->Similarities->query("DELETE FROM `similarities` WHERE `experiment_id`='".$exp_id."'"); 
    $this->DataUploads->query("DELETE FROM `data_uploads` WHERE `experiment_id`='".$exp_id."'");

    //first things first: delete all the jobs from the cluster, which are attached to this experiment
    //this will prevent overloading of the cluster system by malignant people constantly creating new experiments with new jobs, 
    //and subsequently deleting them
    $jobs	= $this->ExperimentJobs->getJobs($exp_id);
    foreach($jobs as $job){$job_id=$job['job_id'];$this->TrapidUtils->deleteClusterJob($exp_id,$job_id);}
    $this->ExperimentJobs->query("DELETE FROM `experiment_jobs` WHERE `experiment_id`='".$exp_id."'");

    $this->ExperimentLog->query("DELETE FROM `experiment_log` WHERE `experiment_id`='".$exp_id."'");
    //remove experiment
    $this->Experiments->deleteAll(array("Experiments.experiment_id"=>$exp_id));

    //remove directory from the temp storage 
    $tmp_dir	= TMP."experiment_data/".$exp_id."/";	
    if(file_exists($tmp_dir) && is_dir($tmp_dir)){       
	shell_exec("rm -rf $tmp_dir");
    }	

    $this->redirect(array("controller"=>"trapid","action"=>"experiments"));
  }





  /*******************************************************************************************************
   *
   *  AUTHENTICATION STUFF : COOKIES ETC.
   * 
   ********************************************************************************************************/



  function change_password(){
    //Configure::write("debug",2);       

    $user_id		= parent::check_user();	
    //retrieve information about the user
    $user_email		= $this->Authentication->find("first",array("fields"=>array("email"),"conditions"=>array("user_id"=>$user_id)));
    $user_email	= $user_email['Authentication']['email'];
   
    if($_POST){
      if(array_key_exists("new_password1",$_POST) && array_key_exists("new_password2",$_POST)){
	$pass1 = $_POST['new_password1'];
	$pass2 = $_POST['new_password2'];
	if($pass1!=$pass2){$this->set("error","Passwords are not the same");return;}
	if(strlen($pass1) < 5){$this->set("error","Passwords need to consist of 5 or more characters");return;}

	//checks done, create hash-version of the new password
	$hashed_pass	= hash("sha256",$pass1);
	$this->Authentication->updateAll(array("password"=>"'".$hashed_pass."'"),array("user_id"=>$user_id));
	//send email to user with password information
	$this->TrapidUtils->send_registration_email($user_email,$pass1,true);	      
	
	$this->Cookie->destroy();
     	$this->redirect("/");
      }      
    }

  }
  


  /*
   * Authentication: registration and login
   */
  function authentication($registration=null){  
    //Configure::write("debug",2);
    //$hashed_pass	= hash("sha256","test");
    //pr($hashed_pass);
	
    //basic first check, to see whether user is already logged in.
    $user_id		= $this->Cookie->read("user_id");
    $email		= $this->Cookie->read("email");
    $user_id  		= mysql_real_escape_string($user_id);
    $email		= mysql_real_escape_string($email);
    $user_data		= $this->Authentication->find("first",array("conditions"=>array("user_id"=>$user_id,"email"=>$email)));	
    if($user_data){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}

    if($registration=="registration"){
      $this->set("registration",true);
    }
    if($registration=="password_recovery"){
      $this->set("pass_recovery",true);
    }
	
    if($_POST){              
      //registration part of the page
      if(array_key_exists("registration",$_POST)){
	//standard validation of input parameters
	if(!(array_key_exists("login",$_POST)&&array_key_exists("organization",$_POST)&&array_key_exists("country",$_POST))){
	  $this->set("error","Invalid form parameters");return;
	}
	$email		= mysql_real_escape_string($_POST["login"]);
	$organization	= mysql_real_escape_string($_POST["organization"]);
	$country	= mysql_real_escape_string($_POST["country"]);
	if(!($email!="" && $organization!="" && $country!="")){
	  $this->set("error","Not all fields are filled in");return;
	}
	//check whether valid email-address, using the models validation
	$this->Authentication->set(array("email"=>$email));
	if(!$this->Authentication->validates()){
	  $this->set("error","Invalid email address");return;
	}	
	//check whether email-address is already present in authentication table of database
	$user_data	= $this->Authentication->find("first",array("conditions"=>array("email"=>$email)));
	if($user_data){
	  $this->set("error","Email-address already in use");return;
	}
	//now, we can actually create a password, add the user to the database	
	$password	= $this->TrapidUtils->rand_str(8);	 	
	$hashed_pass	= hash("sha256",$password);
	$this->Authentication->save(array("user_id"=>"","email"=>$email,"password"=>$hashed_pass,"group"=>"academic",
					  "organization"=>$organization,"country"=>$country));
	//send email to user with password information
	$this->TrapidUtils->send_registration_email($email,$password);	
	$this->set("message","Please use the authentication information send to you by email to login");
	$this->set("registration",false);
	return;       
      }

      //authentication part of the page
      else{       
	if(array_key_exists("login",$_POST) && array_key_exists("password",$_POST)){
	  $email	= mysql_real_escape_string($_POST["login"]);
	  $password	= mysql_real_escape_string(hash("sha256",$_POST["password"]));	
	  $user_data 	= $this->Authentication->find("first",array("conditions"=>array("email"=>$email,"password"=>$password)));
	  if(!$user_data){$this->set("error","Wrong email/password");return;}
	  $this->Cookie->write("user_id",$user_data['Authentication']['user_id']);
	  $this->Cookie->write("email",$user_data['Authentication']['email']);

	  $this->cleanup_experiments();

	  $this->redirect(array("controller"=>"trapid","action"=>"experiments"));
        }
        else{$this->redirect(array("controller"=>"trapid","action"=>"authentication"));}
      }     
    }   
  }



  //stub to perform the check on whether experiments should be deleted or not.
  //As such, when one user succesfully logs into the system, then a quick and small check is done to see
  //whether the current month has already been flagged in the database. This check should be sufficiently fast so no
  //negative user experience should be produced.
  //If the check fails however, and as such an entire pipeline must be run, we run a shell-script in asynchronous mode
  //on the cluster, not connected to the current user-id (as its a system job).
  //Here we will do 2 things:
  //a) check for any jobs that are not edited in X months, and add them to the database. Also send email to users.
  //b) delete any jobs that were added during the check two months ago (two months to prevent the possibility
  //that the first person to log into the system does it on the 30th, and the next day its a new month so newly flagged 
  //experiments (for a single day) get deleted after only a single day warning. 
  //Furthermore, the deletion does not occur if the edit-date has been changed in the mean-time. Rather, the deletion is
  //dismissed in this case. This allows users to just check their experiment to prevent deletion.
  function cleanup_experiments(){
    
    $year	= date("Y");
    $month 	= date("n");
    $cleanup_warning	= 2;
    $cleanup_delete	= 1;

    $cleanup_id	= $this->CleanupDate->checkDateStatus($year,$month);     
    if($cleanup_id==-1){      
      $output_file	= TMP."experiment_data/cleanup_".$year."_".$month.".out";     
      $error_file	= TMP."experiment_data/cleanup_".$year."_".$month.".err";  
      $qsub_file	= $this->TrapidUtils->create_qsub_script_general();
      $shell_script	= $this->TrapidUtils->create_monthly_cleanup_script($year,$month,$cleanup_warning,$cleanup_delete);
           
      if($qsub_file == null || $shell_script == null){}
      else{
	$command  	= "sh $qsub_file -q medium -o $output_file -e $error_file $shell_script ";
	//pr($command);
        $output		= array();      
        exec($command,$output);   
	//pr($output);
      }     
     }   
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
    /* $this->set("title" ,WEBSITE_TITLE);
    $this->Cookie->name		= "trapid_cookie";
    $this->Cookie->time		= "7200";
    $this->Cookie->path		= "/webtools/trapid/";
    $this->Cookie->domain	= "bioinformatics.psb.ugent.be";
    $this->Cookie->key		= "JsjdKO09DJfdfjODWSkdW89Sd";
    $this->Cookie->secure	= false;
    */
  }


 

   /*
    * Function which deletes the stored cookies for authentication, then redirects to home-page
    */
   function log_off(){
     $this->Cookie->destroy();
     $this->redirect("/");
   }



}



?>