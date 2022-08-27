<?php
/*
 * General controller class for the TRAPID functionality
 */
class TrapidController extends AppController{
  var $name		= "Trapid";
  var $helpers		= array("Html", "Form"); // ,"Javascript","Ajax"); //NOTE: Javascript and Ajax helpers were removed in Cake 2.X

  var $uses	= array('Authentication', 'AnnotSources', 'Annotation', 'CleanupDate', 'CleanupExperiments',
                    'Configuration', 'DataSources', 'DataUploads', 'DeletedExperiments', 'ExperimentJobs', 'ExperimentLog', 'Experiments', 'ExperimentStats',
                    'ExtendedGo', 'FunctionalEnrichments', 'FullTaxonomy', 'GeneFamilies', 'GfData', 'GoParents', 'HelpTooltips', 'KoTerms',
                    'News', 'ProteinMotifs', 'SharedExperiments', 'Similarities', 'Transcripts', 'TranscriptsGo',
                    'TranscriptsInterpro', 'TranscriptsLabels', 'TranscriptsKo', 'TranscriptsPagination', 'TranscriptsTax',
                    'RnaSimilarities', 'RnaFamilies'
                    );

  var $components	= array("Cookie", "TrapidUtils", "Sequence", "Session");
  var $paginate		= array(
    "Transcripts"=>
					array(
					    // If we want to retrieve only fields that are actually used
                        // 'fields' => array('Transcripts.transcript_id', 'Transcripts.gf_id', 'Transcripts.meta_annotation'),
						"limit"=>10,
			       			"order"=>array(
			       			    "Transcripts.experiment_id"=>"ASC",  // Extra sorting needed too (see gitlab issue #5)
			       			    "Transcripts.transcript_id"=>"ASC"
                            )
				  	),
				"TranscriptsPagination"=>
					array(
					      "limit"=>10,
					      "order"=>array(
                              "TranscriptsPagination.experiment_id"=>"ASC, ", // Extra sorting needed too (see gitlab issue #5)
                              "TranscriptsPagination.transcript_id"=>"ASC"
                          )
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



  function manage_jobs($exp_id=null){
    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    $exp_id	=  $this->Experiments->getDataSource()->value($exp_id, 'integer'); // Useless?
    parent::check_user_exp($exp_id);

    // Delete jobs that already finished but that may still be in `experiment_jobs` table, before getting `$exp_info`
    $experiment_jobs = $this->ExperimentJobs->getJobs($exp_id);
    $finished_jobs = $this->TrapidUtils->getFinishedJobIds($exp_id, $experiment_jobs);
    $cluster_status = $this->TrapidUtils->check_cluster_status();
    $this->set("cluster_status", $cluster_status);
    $tooltip_text_cluster_status = $this->HelpTooltips->getTooltipText("cluster_status");
    $this->set("tooltip_text_cluster_status", $tooltip_text_cluster_status);

      // pr($finished_jobs);
    foreach($finished_jobs as $finished_job_id) {
        // pr("Delete job ". $finished_job_id);
        $this->ExperimentJobs->deleteJob($exp_id, $finished_job_id);
        // Remove from `$experiment_jobs`
        // I am doing this because although the value is deleted from the DB, it is still in the array
        // (and would be shown on the page)
        foreach($experiment_jobs as $k=>$v) {
            if($v['job_id'] == $finished_job_id) {
                unset($experiment_jobs[$k]);
            }
        }
    }

    $exp_info = $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $running_jobs	= $this->TrapidUtils->checkJobStatus($exp_id, $experiment_jobs);
    //if(count($running_jobs)==0){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
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
      $this->set("active_header_item", "Jobs");
      $this->set('title_for_layout', 'Jobs');
  }



  function change_status($exp_id=null){
    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info", $exp_info);
    $this->set("exp_id", $exp_id);
    $this->set('title_for_layout', 'Experiment status');

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
	// $new_status	= mysql_real_escape_string($_POST['new_status']);
	$new_status	= $_POST['new_status'];
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
        $this->layout = "external";  // Layout for external pages (i.e. not in experiment)
        $this->set("active_header_item", "Home");
        $this -> set('title_for_layout', 'Welcome');
        $max_user_experiments = MAX_USER_EXPERIMENTS;
        $this->set("max_user_experiments", $max_user_experiments);
        $news_items = $this->News->find('all', array(
            "conditions"=>array("is_visible"=>"y"),
            "order"=>array('News.date DESC'))
        );
        $this->set('news_items', $news_items);
        $user_id	= $this->Cookie->read("user_id");
      	$email		= $this->Cookie->read("email");
        // $user_id  	= $this->Authentication->getDataSource()->value($user_id, 'string');
        // $email		= $this->Authentication->getDataSource()->value($email, 'string');
        // No need to escape SQL data when using `find` and proper array notation?
        $user_data	= $this->Authentication->find("first",array("conditions"=>array("user_id"=>$user_id,"email"=>$email)));
        // Disable redirection as it can be confusing to have the home page disappearing?
        // if($user_data){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}

      // Send a test email
      // mail("frbuc@psb.ugent.be","test email","blablabla");
  }


// Retrieve transcript count for experiment `$exp_id`. Function called via AJAX for each experiment of the experiments
// overview page (`experiments`).
  function experiments_num_transcripts($exp_id){
    Configure::write("debug",1);
    $this->layout = "";
    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    // pr($exp_info);
    $num_transcripts = "NA";  // Default value to display
    $exp_transcripts	= $this->Experiments->getTranscriptCount($exp_id);
    if($exp_transcripts){
      $num_transcripts = $exp_transcripts;
    }
    $this->set("num_transcripts",$num_transcripts);
    return;
  }


  /*
   * Displays experiment information for a given user
   */
  function experiments(){


      // Configure::write("debug",2);
    $this->layout = "external";  // Layout for external pages (i.e. not in experiment)
    $this->set("active_header_item", "Experiments");
    $this -> set('title_for_layout', 'Experiments overview');

    $max_user_experiments	= MAX_USER_EXPERIMENTS;
    $this->set("max_user_experiments",$max_user_experiments);

    //check whether valid user id.
    //$user_id 		= $this->check_user();
    $user_id		= parent::check_user();

    //retrieve information about the user
    $user_email		= $this->Authentication->find("first",array("fields"=>array("email"),"conditions"=>array("user_id"=>$user_id)));
    $this->set("user_email",$user_email);

    //retrieve possible available PLAZA databases from the configuration table
    $all_available_sources = $this->DataSources->find("all");
    $available_sources = array();
    // Filter visible data sources based on the user's group
    $user_group = $this->Authentication->find("first",array("fields"=>array("group"),"conditions"=>array("user_id"=>$user_id)));
    foreach($all_available_sources as $data_source) {
        if(!empty($data_source['DataSources']['restrict_to'])) {
            $allowed_groups = explode(",", $data_source['DataSources']['restrict_to']);
            if(in_array($user_group['Authentication']['group'], $allowed_groups)) {
                $available_sources[] = $data_source;
            }
        }
        else {
            $available_sources[] = $data_source;
        }
    }

    $this->set("available_sources",$available_sources);

    //retrieve current user experiments.
    $experiments	= $this->Experiments->getUserExperiments($user_id);

    // Delete jobs that already finished but that may still be in `experiment_jobs` table, for each experiment
    // If slowing down page loading, find another solution.
    // This code can be improved by retrieving job IDs of all experiments before looping: consider if things get slow.
    foreach ($experiments as $key=>$value) {
        $current_exp_id = $experiments[$key]['Experiments']['experiment_id'];
        $experiment_jobs = $this->ExperimentJobs->getJobs($current_exp_id);
        // If there are experiments, check if they need to be deleted or not
        // No need to check if it is not the case.
        if(!empty($experiment_jobs)) {
            $finished_jobs = $this->TrapidUtils->getFinishedJobIds($current_exp_id, $experiment_jobs);
            foreach($finished_jobs as $finished_job_id) {
                // Remove from experiment_jobs
                $this->ExperimentJobs->deleteJob($current_exp_id, $finished_job_id);
            }
            $exp_jobs = $experiments[$key]['experiment_jobs'];
            // Also remove from `$experiments`
            // I am doing this because although the value is deleted from the DB, it is still in the array (and still on the page)
            foreach($exp_jobs as $k=>$v) {
                if(in_array($v['job_id'], $finished_jobs)) {
                    // pr("remove ". $v['job_id'] . ", key ". $k);
                    unset($experiments[$key]['experiment_jobs'][$k]);
                }
            }
        }
    }

    // pr($experiments);
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
       	// $experiment_name	= mysql_real_escape_string($_POST['experiment_name']);
	    // $experiment_description	= mysql_real_escape_string($_POST['experiment_description']);
	    // $data_source		= mysql_real_escape_string($_POST['data_source']);
          // No need to escape (using CakePHP's `save()`+arrays)?
	    $experiment_name	= $_POST['experiment_name'];
	    $experiment_description	= $_POST['experiment_description'];
	    $data_source		= $_POST['data_source'];

	//check whether person has not already reached the limit of number of experiments (normally form should be disabled as well)
	if(count($experiments)>=$max_user_experiments){
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
  // `experiment_id` value set to NULL (setting it to an empty string, like before, would require to turn SQL strict mode off)
	$this->Experiments->save(array("user_id"=>$user_id,"experiment_id"=>NULL,"title"=>$experiment_name,"description"=>$experiment_description,"creation_date"=>date("Y-m-d H:i:s"),"last_edit_date"=>date("Y-m-d H:i:s"),"process_state"=>"empty","used_plaza_database"=>$data_source));
	// get last experiment id
	$user_experiments	= $this->Experiments->query("SELECT `experiment_id` FROM `experiments` WHERE `user_id`='". $this->Authentication->getDataSource()->value($user_id, 'integer') ."' ORDER BY `experiment_id` DESC ");
	$exp_id			= $user_experiments[0]['experiments']['experiment_id'];
	$this->ExperimentLog->addAction($exp_id,"create_experiment","");
	$this->ExperimentLog->addAction($exp_id,"create_experiment","options", 1);
	$this->ExperimentLog->addAction($exp_id,"create_experiment_options","reference_database=" . $data_source_name, 2);
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
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["all"]);
    $this->set("exp_info", $exp_info);
    $this->set("exp_id", $exp_id);

    $log_info	= $this->ExperimentLog->find("all",array("conditions"=>array("experiment_id"=>$exp_id),
							"order"=>array("ExperimentLog.date ASC")));
    $this->set("log_info",$log_info);
    $this->set("active_header_item", "Log");
    $this->set('title_for_layout', 'Log');
  }



  /*
   * Share the experiment.
   */
  function experiment_access($exp_id=null){
     // $exp_id	= mysql_real_escape_string($exp_id);
     parent::check_user_exp($exp_id);
     $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
     //however, only the owner of the experiment (original creator) can change the access to the experiment
     $is_owner = parent::is_owner($exp_id);
     $this->set("is_owner", $is_owner);
     $this->set("exp_info",$exp_info);
     $this->set("exp_id",$exp_id);
     $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]);


     //get all the users.
     $all_users_	= $this->Authentication->find("all",array("fields"=>array("user_id","email")));
     $all_users		= $this->TrapidUtils->indexArraySimple($all_users_,"Authentication","user_id","email");
     $all_users_inv	= $this->TrapidUtils->indexArraySimple($all_users_,"Authentication","email","user_id");
     $this->set("all_users",$all_users);

     // Two possible cases: sharing the experiment with user(s) or revoking the access of a user
     if($_POST && $is_owner && array_key_exists("exp_access_change", $_POST)) {
       // Sharing experiment with new users
       if($_POST["exp_access_change"] == "share" && array_key_exists("new_share", $_POST)) {
         $new_share	= preg_split("/[ \n]/",$_POST['new_share']);
         $selected	= array();
         foreach($new_share as $ns){
           $ns	= trim($ns);
           if(array_key_exists($ns,$all_users_inv)){
             $selected[$all_users_inv[$ns]] = $ns;
           }
         }
         foreach($selected as $k=>$v){
           $this->SharedExperiments->save(array("user_id"=>$k,"experiment_id"=>$exp_id));
         }
       }
       if($_POST["exp_access_change"] == "revoke" && array_key_exists("revoke_email", $_POST)) {
         $revoke_email = $_POST["revoke_email"];
         if(array_key_exists($revoke_email, $all_users_inv)) {
           $revoke_user_id = $all_users_inv[$revoke_email];
           $this->SharedExperiments->deleteAll(array("user_id"=>$revoke_user_id, "experiment_id"=>$exp_id), false);
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

     $this->set("active_header_item", "Settings");
     $this->set("title_for_layout", "Experiment access");

  }




  /*
   * Change some settings (name, description, etc...) of an experiment.
   */
  function experiment_settings($exp_id=null){

    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    //$this->set("exp_info",$exp_info);
    $this->set("exp_id", $exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    $this->set("exp_info",$exp_info);
    $this->set("show_experiment_overview_description",1);

    if($_POST){
      if(array_key_exists("experiment_name",$_POST)){
	$new_exp_name		=  $_POST["experiment_name"];
	if($new_exp_name==""){
	  $this->set("error","No name defined");
	  return;
	}
	$this->Experiments->updateAll(array("title"=> $this->Experiments->getDataSource()->value($new_exp_name, 'string')),array("experiment_id"=>$exp_id));
      }
      if(array_key_exists("experiment_description",$_POST)){
	$new_exp_desc		= $_POST["experiment_description"];
	$this->Experiments->updateAll(array("description"=> $this->Experiments->getDataSource()->value($new_exp_desc, 'string')),array("experiment_id"=>$exp_id));
      }
      $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
      $this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
    }
    $this->set("active_header_item", "Settings");
    $this->set("title_for_layout", "Change experiment settings");
  }





  /*
   * Content page of a single experiment
   * Data displayed here should consist of basic information.
   * More complicated info (which would require more processing)
   * should only be accesible through tool-pages
   */
  function experiment($exp_id=null){
//    $exp_id	= $this->Experiments->getDataSource()->value($exp_id, 'integer');  // Useless / unclean?
    parent::check_user_exp($exp_id);

    // Delete jobs that already finished but that may still be in `experiment_jobs` table, before getting `$exp_info`
    // If slowing down page loading, find another solution.
    $experiment_jobs = $this->ExperimentJobs->getJobs($exp_id);
    $finished_jobs = $this->TrapidUtils->getFinishedJobIds($exp_id, $experiment_jobs);
    foreach($finished_jobs as $finished_job_id) {
        $this->ExperimentJobs->deleteJob($exp_id, $finished_job_id);
    }

    $standard_experiment_info	= $this->Experiments->find("first",array("conditions"=>array("experiment_id"=>$exp_id)));
    $this->TrapidUtils->checkPageAccess($standard_experiment_info['Experiments']['title'], $standard_experiment_info['Experiments']['process_state'], $this->process_states["default"]);

    // Set the edit date
    $this->Experiments->updateAll(array("last_edit_date"=>"'".date("Y-m-d H:i:s")."'"),array("experiment_id"=>$exp_id));

    $this->set("exp_id",$exp_id);
    $user_group=$this->Authentication->find("first",array("fields"=>array("group"),"conditions"=>array("user_id"=>parent::check_user())));
    if($user_group['Authentication']['group'] == "admin"){$this->set("admin", 1);}

    // Check whether the number of jobs in the queue for this experiment has not been reached.
    $current_job_number = $this->ExperimentJobs->getNumJobs($exp_id);
    if($current_job_number>=MAX_CLUSTER_JOBS){$this->set("max_number_jobs_reached",true);}

    // Get default experiment information
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
	// Retrieve information for table at bottom of page
    // The query used to retrieve transcripts to display was modified (see gitlab issue #5 for more details)
    // The previous one was not using `experiment_id` index and taking a very long time.
	// $transcripts_p	= $this->paginate("Transcripts",array("Transcripts.experiment_id"=>$exp_id));
    $transcripts_p = $this->paginate("Transcripts",array("Transcripts.experiment_id = '" . $exp_id . "'"));
	$transcript_ids	= $this->TrapidUtils->reduceArray($transcripts_p,"Transcripts","transcript_id");
    $this->set("transcript_ids", $transcript_ids);

	// retrieve functional annotation for the table
	$transcripts_go	= $this->TrapidUtils->indexArray($this->TranscriptsGo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids,"is_hidden"=>"0", "type"=>"go"))),"TranscriptsGo","transcript_id","name");
    // TRAPID DB structure changed
	// $transcripts_go	= $this->TrapidUtils->indexArray($this->TranscriptsGo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids,"is_hidden"=>"0", "type"=>"go"))),"TranscriptsGo","transcript_id","go");
	$go_info	= array();
	if(count($transcripts_go)!=0){
		$go_ids		=  array_unique(call_user_func_array("array_merge",array_values($transcripts_go)));
		$go_info        = $this->ExtendedGo->retrieveGoInformation($go_ids);
	}

	$transcripts_ipr= $this->TrapidUtils->indexArray($this->TranscriptsInterpro->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids, "type"=>"ipr"))),"TranscriptsInterpro","transcript_id","name");
    // TRAPID DB structure changed
	// $transcripts_ipr= $this->TrapidUtils->indexArray($this->TranscriptsInterpro->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids, "type"=>"ipr"))),"TranscriptsInterpro","transcript_id","interpro");
	$ipr_info	= array();
	if(count($transcripts_ipr)!=0){
		$ipr_ids        = array_unique(call_user_func_array("array_merge",array_values($transcripts_ipr)));
		$ipr_info	= $this->ProteinMotifs->retrieveInterproInformation($ipr_ids);
	}
    // KO
    $transcripts_ko = $this->TrapidUtils->indexArray($this->TranscriptsKo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids, "type"=>"ko"))),"TranscriptsKo","transcript_id","name");
    $ko_info	= [];
    if(count($transcripts_ko)!=0){
        $ko_ids = array_unique(call_user_func_array("array_merge",array_values($transcripts_ko)));
        $ko_info = $this->KoTerms->retrieveKoInformation($ko_ids);
    }

	//retrieve subset/label information
	$transcripts_labels	= $this->TrapidUtils->indexArray($this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids))),"TranscriptsLabels","transcript_id","label");


	$this->set("transcript_data",$transcripts_p);
	$this->set("transcripts_go",$transcripts_go);
	$this->set("transcripts_ipr",$transcripts_ipr);
	$this->set("transcripts_ko",$transcripts_ko);
	$this->set("transcripts_labels",$transcripts_labels);
	$this->set("go_info",$go_info);
	$this->set("ipr_info",$ipr_info);
	$this->set("ko_info",$ko_info);
    }
    // TODO: To change as we now get experiment information twice... Not very smart but will do for prototyping.
      $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
      $this->set("exp_info",$exp_info);
      // Test HelpTooltips model
      $tooltip_text_test = $this->HelpTooltips->getTooltipText("test_tooltip");
      $this->set("tooltip_text_test", $tooltip_text_test);
      $this->set("active_sidebar_item", "Overview");
      $this -> set('title_for_layout', 'Experiment overview &middot; ' . $standard_experiment_info['Experiments']['title']);
  }


  function similarity_hits($exp_id=null,$transcript_id=null){
    Configure::write("debug",2);
    if(!$exp_id || !$transcript_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    $transcript_id 	= urldecode($transcript_id);
    //check whether transcript is valid
    $transcript_info    = $this->Transcripts->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
    if(!$transcript_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("transcript_info",$transcript_info['Transcripts']);
    //get the similarity search hits for this transcript
    // TODO: use virtual field
    $similarity_hits   = $this->Similarities->find("first",array("fields"=>array("UNCOMPRESS(similarity_data) as sim_data"), "conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
    // $similarity_hits	= explode(";",$similarity_hits['Similarities']['similarity_data']);
    $similarity_hits	= explode(";",$similarity_hits[0]['sim_data']);
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

    // Get reference DB type
      $db_type = "plaza";
      if(strpos($exp_info["used_plaza_database"], "eggnog") !== false){
          $db_type = "eggnog";
      }
      $this->set("db_type", $db_type);  // move?

    //ok, now see whether the experiment is HOM or IORTHO. If IORTHO, don't do anything.
    // `HOM` can correspond to either PLAZA or EggNOG reference databases.
    // If the experiment's reference database is EggNOG, we retrieve GF data at the taxonomic level of the current
    // transcript's GF (if any)
    if($exp_info['genefamily_type']=="HOM"){
      $gf_prefix	= $this->DataSources->find("first",array("conditions"=>array("name"=>$exp_info['datasource'])));
      $gf_prefix	= $gf_prefix['DataSources']['gf_prefix'];
      if($gf_prefix) {
          $gf_ids=$this->GfData->find("all",array("conditions"=>array("gene_id"=>$gene_ids,"`gf_id` LIKE '".$gf_prefix."%'")));
      }
      else {
          if($db_type == "eggnog") {
              // Set taxonomic scope
              $tax_scope = "NOG";
              if($transcript_info['Transcripts']['gf_id']) {
                  $ref_gf = $this->GeneFamilies->find("first", array("fields"=>"plaza_gf_id",
                      "conditions"=>array("experiment_id"=>$exp_id, "gf_id"=>$transcript_info['Transcripts']['gf_id'])));
                  $tax_scope_data = $this->GfData->getEggnogTaxScope($ref_gf['GeneFamilies']['plaza_gf_id']);
                  $tax_scope = $tax_scope_data['scope'];
              }
              $gf_ids = $this->GfData->find("all",array("conditions"=>array("gene_id"=>$gene_ids, "scope"=>$tax_scope)));
              $this->set("tax_scope_data", $tax_scope_data);
          }
          else {
              $gf_ids = $this->GfData->find("all",array("conditions"=>array("gene_id"=>$gene_ids)));
          }
//          pr($gf_ids);
      }
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

      // $new_plaza_gf_id		= mysql_real_escape_string($_POST['plaza_gf_id']);
      $new_plaza_gf_id		= $_POST['plaza_gf_id'];
      //check if exists. If not, return to page with error message.
      $num_plaza_genes		= $this->GfData->find("count",array("conditions"=>array("gf_id"=>$new_plaza_gf_id)));
      if($num_plaza_genes==0){$this->set("error","Illegal external identifier for gene family");}
      $new_trapid_gf_id		= null;
      $new_trapid_gf_info	= null;
      $total_new_gf		= true;
      if(array_key_exists("trapid_gf_id",$_POST)){
	// $new_trapid_gf_id	= mysql_real_escape_string($_POST['trapid_gf_id']);
	$new_trapid_gf_id	= $_POST['trapid_gf_id'];
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

      $this -> set('title_for_layout', 'Similarity hits');

  }


    // RNA similarity output exploration page
    function rna_similarity_hits($exp_id=null,$transcript_id=null){
        Configure::write("debug",2);
        if(!$exp_id || !$transcript_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
        // $exp_id	= mysql_real_escape_string($exp_id);
        parent::check_user_exp($exp_id);
        $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
        $this->TrapidUtils->checkPageAccess($exp_info['title'], $exp_info["process_state"], $this->process_states["default"]);
        //check whether transcript is valid
        // $transcript_id = mysql_real_escape_string($transcript_id);
        $transcript_info = $this->Transcripts->find("first", array("conditions"=>array("experiment_id"=>$exp_id, "transcript_id"=>$transcript_id)));
        if(!$transcript_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment", $exp_id));}
        //get the similarity search hits for this transcript
        // TODO: create dedicted model function to handle `uncompress`?
        $rna_similarity_hits = $this->RnaSimilarities->find("first", array("fields"=>array("UNCOMPRESS(similarity_data) as sim_data"), "conditions"=>array("experiment_id"=>$exp_id, "transcript_id"=>$transcript_id)));
        $rna_similarity_hits = explode(";",$rna_similarity_hits[0]['sim_data']);
        $rna_sim_hits		= array();
        $gene_ids		= array();
        //get the gene identifiers.
        foreach($rna_similarity_hits as $sh){
            $t		= explode(",",$sh);
            $gene_id		= $t[0];
            $gene_ids[] 	= $gene_id;
            $rna_sim_hits[$gene_id][]	= $t;
        }
        $gene_ids		= array_unique($gene_ids);
        $rf_ids		= array();
        $rfam_linkouts = $this->Configuration->find("all", array('conditions'=>array('method'=>'linkout', 'key'=>'rfam')));
        $rfam_linkouts = $this->TrapidUtils->indexArraySimple($rfam_linkouts, "Configuration", "attr", "value");


        // if($this->Session->check("error")){$this->set("error",$this->Session->read("error"));$this->Session->delete("error");}
        // if($this->Session->check("message")){$this->set("message",$this->Session->read("message"));$this->Session->delete("message");}
        // To write: code to modify assigned RNA family!

        $this->set("exp_info", $exp_info);
        $this->set("exp_id", $exp_id);
        $this->set("transcript_info", $transcript_info['Transcripts']);
        $this->set("sim_hits", $rna_sim_hits);
        $this->set("rf_ids", $rf_ids);
        $this->set("rfam_linkouts", $rfam_linkouts);
        $this -> set('title_for_layout', 'RNA similarity hits');

    }

  /******************************************************************************************************
   *
   * DATA TYPE PAGES :
   *
   ******************************************************************************************************
   */



  // Unused function? To remove?
  function detect_orfs($exp_id=null,$transcript_id=null){
    $this->layout = "";
    if(!$exp_id || !$transcript_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);

    //check whether transcript is valid
    // $transcript_id 	= mysql_real_escape_string($transcript_id);
    $transcript_info = $this->Transcripts->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
    if(!$transcript_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
  }


  function transcript($exp_id=null,$transcript_id=null){
    if(!$exp_id || !$transcript_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    parent::check_user_exp($exp_id);
    $exp_info = $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'], $exp_info["process_state"], $this->process_states["default"]);

    // Check whether transcript is valid
    $transcript_id = urldecode($transcript_id);
    $transcript_info = $this->Transcripts->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
    // If no `$transcript_info` (i.e. `$transcript_id` is invalid), redirect to experiment page.
    if(!$transcript_info){
        $this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
    }

    // Handle POST requests: user made changes to the initial data!
    // Depending on what the user chose to change, some other post processing steps might be necessary
    // Put it here, as it might influence the later results. Reload the transcript info after the update!
    if($_POST){
        // Edit ORF sequence
        if(array_key_exists("orf_sequence",$_POST)){
            $this->Transcripts->updateAll(array("orf_sequence"=>"COMPRESS(" . $this->Transcripts->getDataSource()->value($_POST['orf_sequence'], 'string') . ")"),array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id));
            $this->Transcripts->updateCodonStats($exp_id,$transcript_id,$_POST['orf_sequence']);
            $this->ExperimentLog->addAction($exp_id,"change_orf_sequence",$transcript_id);
        }
        // Edit transcript sequence (disabled at the moment)
        if(array_key_exists("transcript_sequence",$_POST)){
            $this->Transcripts->updateAll(array("transcript_sequence"=>"COMPRESS(".$this->Transcripts->getDataSource()->value($_POST['transcript_sequence'], 'string').")"),array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id));
            $this->ExperimentLog->addAction($exp_id,"change_transcript_sequence",$transcript_id);
        }
        // Edit frameshift corrected sequence
        if(array_key_exists("corrected_sequence",$_POST)) {
            $this->Transcripts->updateAll(array("transcript_sequence_corrected" => "COMPRESS(" . $this->Transcripts->getDataSource()->value($_POST['corrected_sequence'], 'string') . ")"), array("experiment_id" => $exp_id, "transcript_id" => $transcript_id));
            $this->ExperimentLog->addAction($exp_id, "change_corrected_sequence", $transcript_id);
        }
        // Edit meta-annotation
        if(array_key_exists("meta_annotation",$_POST)){
            $this->Transcripts->updateAll(array("meta_annotation"=>$this->Transcripts->getDataSource()->value($_POST['meta_annotation'], 'string')),array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id));
            $this->ExperimentLog->addAction($exp_id,"change_meta_annotation",$transcript_id);
        }
        // Edit subset information
        if(array_key_exists("subsets",$_POST) && $_POST["subsets"]=="subsets"){
            $available_subsets	= $this->TranscriptsLabels->getLabels($exp_id);
            $transcript_subsets	= $this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
            $transcript_subsets	= $this->TrapidUtils->reduceArray($transcript_subsets,"TranscriptsLabels","label");

            // Check if a new subset was created
            if(array_key_exists("new_subset",$_POST) && $_POST['new_subset']=="on" && array_key_exists("new_subset_name",$_POST)){
                $new_subset = filter_var($_POST['new_subset_name'], FILTER_SANITIZE_STRING);  // No check needed since only used in `saveAll()`?
                $new_subset = preg_replace('/\s/u', '', $new_subset); // Also remove whitespace from subset name
                $save_data = array(array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id,"label"=>$new_subset));
                $this->TranscriptsLabels->saveAll($save_data);
            }
            // Check if transcript was added to an existing subset
            $save_data = array();
            foreach($_POST as $k=>$v) {
                if($v=="on" && array_key_exists($k,$available_subsets) && !in_array($k,$transcript_subsets)){
                    $save_data[] = array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id,"label"=>$k);
                }
            }
            if(count($save_data)>0){
                $this->TranscriptsLabels->saveAll($save_data);
            }
            // Check if transcript was removed from an existing subset
            foreach($transcript_subsets as $subset){
                if(!array_key_exists($subset,$_POST)){	 // Deletion of subset
                    $this->TranscriptsLabels->query("DELETE FROM `transcripts_labels` WHERE `experiment_id`='".$exp_id."' AND `transcript_id`='".$transcript_id."' AND `label`='".$subset."' ");
                }
            }
            //$available_subsets	= $this->TranscriptsLabels->getLabels($exp_id);
            //$this->set("available_subsets",$available_subsets);
        }
        // Finally, update the edit date of the experiment and get `$transcript_info`
       $this->Experiments->updateAll(array("last_edit_date"=>"'".date("Y-m-d H:i:s")."'"),array("experiment_id"=>$exp_id));
       $transcript_info    = $this->Transcripts->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
       // $this->redirect(array("controller"=>"trapid","action"=>"transcript",$exp_id,$transcript_id));
    }

    // Get ORF translated sequence
    if($transcript_info['Transcripts']['orf_sequence'] !=""){
       $transcript_info['Transcripts']['aa_sequence'] = $this->Sequence->translate_cds_php($transcript_info['Transcripts']['orf_sequence'], $transcript_info['Transcripts']['transl_table']);
    }
    $this->set("transcript_info",$transcript_info['Transcripts']);

    // Get transcript functional annotation
    // 1. GO
    $associated_go	= $this->TranscriptsGo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id, "type"=>"go")));
    $go_ids		= $this->TrapidUtils->reduceArray($associated_go,"TranscriptsGo","name");
    //TODO!!
    $go_information	= $this->ExtendedGo->retrieveGoInformation($go_ids);
    $this->set("associated_go",$associated_go);
    $this->set("go_info",$go_information);
    // 2. InterPro
    $associated_interpro	= $this->TranscriptsInterpro->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id, "type"=>"ipr")));
    $interpros	= $this->TrapidUtils->reduceArray($associated_interpro,"TranscriptsInterpro","name");
    $interpro_information = $this->ProteinMotifs->retrieveInterproInformation($interpros);
    $this->set("associated_interpro",$associated_interpro);
    $this->set("interpro_info",$interpro_information);
    // 3. KO
    $associated_ko	= $this->TranscriptsKo->find("all", array("conditions"=>array("experiment_id"=>$exp_id, "transcript_id"=>$transcript_id, "type"=>"ko")));
    $ko_terms	= $this->TrapidUtils->reduceArray($associated_ko, "TranscriptsKo", "name");
    $ko_information = $this->KoTerms->retrieveKoInformation($ko_terms);
    $this->set("associated_ko", $associated_ko);
    $this->set("ko_info" ,$ko_information);

    // Get transcript subset information
    $available_subsets = $this->TranscriptsLabels->getLabels($exp_id);
    $transcript_subsets	= $this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_id)));
    $transcript_subsets	= $this->TrapidUtils->reduceArray($transcript_subsets,"TranscriptsLabels","label");
    $this->set("available_subsets",$available_subsets);
    $this->set("transcript_subsets",$transcript_subsets);

    // Get taxonomic classification information (only if this step was performed during initial processing)
    if($exp_info['perform_tax_binning'] == 1) {
        $unclassified_str = "Unclassified";
        $transcript_txdata = $this->TranscriptsTax->find("first", array("conditions"=>array("experiment_id"=>$exp_id, "transcript_id"=>$transcript_id), "fields"=>array("txid", "tax_results")));
        $transcript_txid = $transcript_txdata['TranscriptsTax']['txid'];
        $transcript_lineage = [];
        // If the transcript was unclassified, set name of clade to 'Unclassified' and score to 0.
        if($transcript_txid == 0) {
            $transcript_txname = $unclassified_str;
            $transcript_txscore = 0;
        }
        // Otherwise retrieve taxonomy data from `full_taxonomy` and parse `tax_results` string to extract Kaiju score.
        else {
            $txdata = $this->FullTaxonomy->find("first", array("fields"=>array("scname", "tax"), "conditions"=>array("txid"=>$transcript_txid)));
            $transcript_txname = $txdata["FullTaxonomy"]["scname"];
            $transcript_lineage = array_reverse(explode("; ", $txdata["FullTaxonomy"]["tax"]));
            array_pop($transcript_lineage);
            $txscore_re = '/^score=([0-9]+)/m';
            preg_match($txscore_re,  $transcript_txdata['TranscriptsTax']['tax_results'], $txscore_match);
            $transcript_txscore = $txscore_match[1];
        }
        $this->set("transcript_txid", $transcript_txid);
        $this->set("transcript_txname", $transcript_txname);
        $this->set("transcript_txscore", $transcript_txscore);
        $this->set("transcript_lineage", $transcript_lineage);
    }



    // Check whether the number of jobs in the queue for this experiment has not been reached.
    $current_job_number = $this->ExperimentJobs->getNumJobs($exp_id);
    if($current_job_number>=MAX_CLUSTER_JOBS){
        $this->set("max_number_jobs_reached",true);
    }

    // Help tooltips
    $tooltips = $this->TrapidUtils->indexArraySimple(
          $this->HelpTooltips->find("all", array("conditions"=>array("tooltip_id LIKE 'transcript%'"))),
          "HelpTooltips","tooltip_id","tooltip_text"
      );

    $this->set("tooltips", $tooltips);
    $this -> set('title_for_layout', $transcript_id . ' &middot; Transcript');
  }



  /*
   * TODO further implement method, and take care in the possible parameters data structure to also indicate the
   * necessary joins and required tables.
   */
  function transcript_selection(){
//    Configure::write("debug",1);
    $num_parameters	= func_num_args();
    if($num_parameters < 3 || $num_parameters%2==0 ){$this->redirect("/");}
    $parameters		= func_get_args();
    // $exp_id		= mysql_real_escape_string($parameters[0]);
    $exp_id		= $parameters[0];
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
      $this->paginate['TranscriptsPagination']['maxLimit'] = $exp_info['transcript_count'];
    }
    //ok, now retrieve the transcripts
    $transcript_ids	= $this->paginate("TranscriptsPagination", $parameters);
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
    $transcripts_go	= $this->TrapidUtils->indexArray($this->TranscriptsGo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids,"is_hidden"=>"0", "type"=>"go"))),"TranscriptsGo","transcript_id","name");
    $go_info	= array();
    if(count($transcripts_go)!=0){
	    $go_ids		=  array_unique(call_user_func_array("array_merge",array_values($transcripts_go)));
	    $go_info        = $this->ExtendedGo->retrieveGoInformation($go_ids);
    }

    $transcripts_ipr= $this->TrapidUtils->indexArray($this->TranscriptsInterpro->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids, "type"=>"ipr"))),"TranscriptsInterpro","transcript_id","name");
    $ipr_info	= array();
    if(count($transcripts_ipr)!=0){
	    $ipr_ids        = array_unique(call_user_func_array("array_merge",array_values($transcripts_ipr)));
	    $ipr_info	= $this->ProteinMotifs->retrieveInterproInformation($ipr_ids);
    }
    // KO
    $transcripts_ko = $this->TrapidUtils->indexArray($this->TranscriptsKo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids, "type"=>"ko"))),"TranscriptsKo","transcript_id","name");
    $ko_info	= [];
    if(count($transcripts_ko)!=0){
      $ko_ids = array_unique(call_user_func_array("array_merge",array_values($transcripts_ko)));
      $ko_info = $this->KoTerms->retrieveKoInformation($ko_ids);
    }

    // Retrieve subset/label information
    $transcripts_labels	= $this->TrapidUtils->indexArray($this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids))),"TranscriptsLabels","transcript_id","label");
    // Subsets - # transcripts information and tooltip (for subset creation form)
    $all_subsets = $this->TranscriptsLabels->getLabels($exp_id);
    $tooltip_text_subset_creation = $this->HelpTooltips->getTooltipText("transcript_table_subset_creation");


    $this->set("parameters",$parsed_parameters);
    $this->set("raw_parameters", $parameters);
    $this->set("transcripts_go",$transcripts_go);
    $this->set("transcripts_ipr",$transcripts_ipr);
    $this->set("transcripts_ko",$transcripts_ko);
    $this->set("transcripts_labels",$transcripts_labels);
    $this->set("go_info_transcripts",$go_info);
    $this->set("ipr_info_transcripts",$ipr_info);
    $this->set("ko_info_transcripts",$ko_info);
    $this->set("all_subsets", $all_subsets);
    $this->set("tooltip_text_subset_creation", $tooltip_text_subset_creation);


      if($download_type=="table"){$this->set("file_name","table_".implode("_",$parameters).".tsv");return;}
  }


  /* Create a transcript subset or add transcripts to an existing subset, from any collection of transcripts (tables
     on the website). Possible collection types:
      * 'gf'/'rf': from a gene or RNA family page
      * 'go'/'ipr'/'ko': from a functional annotation page
      * 'selection': from a transcript selection page
  */
  function create_collection_subset($exp_id=null, $collection_type=null){
      $this->autoRender = false;
      parent::check_user_exp($exp_id);
      $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
      $label_base = "<label class=\"label {label_class}\">{label_content}</label>";
      $allowed_types = ["gf", "rf", "go", "ipr", "ko", "selection", "subset"];
      if($this->request->is('post')) {
          set_time_limit(75);
          // Check if collection type validity
          if (!in_array($collection_type, $allowed_types)) {
              return;
          }
//          pr($this->request->data);

          // Sanitize subset name and check validity
          $subset_name = filter_var($this->request->data['subset-add-select'], FILTER_SANITIZE_STRING);  // TODO: use a more strignent filter
          // Strip + replace blank spaces by underscores
          $subset_name = preg_replace('/\s+/', '_', trim($subset_name));
          if(empty($subset_name)){
              return("<label class=\"label label-warning\">Error: incorrect subset name</label>");
          }
          $exp_subsets = $this->TranscriptsLabels->find("all",array("fields"=>array("label"), "conditions"=>array("experiment_id"=>$exp_id)));
          $subset_exists = false;
          $creation_msg = "Subset created";
          $creation_class = "label-success";
          foreach($exp_subsets as $subset) {
              $subset_to_test = $subset["TranscriptsLabels"]["label"];
              if($subset_to_test == $subset_name) {
                  $subset_exists = true;
                  break;
              }
          }

          if($subset_exists) {
              $creation_msg = "Subset updated";
              $creation_class = "label-primary";
          }

          $parameters = json_decode($this->request->data['selection-parameters']);
          // Depending on the type of collection, retrieve the corresponding transcripts
          $transcript_ids = [];
          // Transcript selection
          // Replace by a switch statement?
          switch($collection_type) {
              case "selection":
                  // Get selection parameters
                  if (sizeof($parameters) % 2 != 0) {
                      return;
                  }
                  // Retrieve transcripts for selection parameters
                  // Mostly copied from `transcript_selection()`, and could be improved
                  // Replace hyphen by colon in GO id (should be replaced) + decode parameters
                  for ($i = 0; $i < count($parameters); $i++) {
                      $value = $parameters[$i];
                      if ($value == "go" && $i < (count($parameters) - 1)) {
                          $next_value = $parameters[$i + 1];
                          $parameters[$i + 1] = str_replace("-", ":", $next_value);
                      }
                      $parameters[$i] = urldecode($value);
                  }
                  // Add `$exp_id` as first parameter (expected in TranscriptPagination)
                  array_unshift($parameters, $exp_id);
                  $this->paginate['TranscriptsPagination']['limit'] = $exp_info['transcript_count'];
                  $this->paginate['TranscriptsPagination']['maxLimit'] = $exp_info['transcript_count'];
                  $transcript_ids = $this->paginate("TranscriptsPagination", $parameters);
                  break;
              // Functional annotation
              case "go":
                  $go_term = str_replace("-", ":", $parameters[0]);
                  $transcript_data = $this->TranscriptsGo->find("all",array("fields"=>"transcript_id", "conditions"=>array("experiment_id"=>$exp_id, "type"=>"go", "name"=>$go_term)));
                  $transcript_ids = array_map(function($x) {return $x['TranscriptsGo']['transcript_id'];}, $transcript_data);
                  break;
              case "ipr":
                  $transcript_data = $this->TranscriptsInterpro->find("all",array("fields"=>"transcript_id", "conditions"=>array("experiment_id"=>$exp_id,"is_hidden"=>"0", "type"=>"ipr", "name"=>$parameters[0])));
                  $transcript_ids = array_map(function($x) {return $x['TranscriptsInterpro']['transcript_id'];}, $transcript_data);
                  break;
              case "ko":
                  $transcript_data = $this->TranscriptsKo->find("all",array("fields"=>"transcript_id", "conditions"=>array("experiment_id"=>$exp_id,"is_hidden"=>"0", "type"=>"ko", "name"=>$parameters[0])));
                  $transcript_ids = array_map(function($x) {return $x['TranscriptsKo']['transcript_id'];}, $transcript_data);
                  break;
              case "gf":
                  $transcript_data = $this->Transcripts->find("all", array("fields"=>array("transcript_id"), "conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$parameters[0])));
                  $transcript_ids = array_map(function($x) {return $x['Transcripts']['transcript_id'];}, $transcript_data);
                  break;
              case "rf":
                  $transcript_data = $this->Transcripts->find("all", array("fields"=>array("transcript_id"), "conditions"=>array("experiment_id"=>$exp_id,"rf_ids"=>$parameters[0])));
                  $transcript_ids = array_map(function($x) {return $x['Transcripts']['transcript_id'];}, $transcript_data);
                  break;
              case "subset":
                  $transcript_data = $this->TranscriptsLabels->find("all",array("fields"=>array("transcript_id"), "conditions"=>array("experiment_id"=>$exp_id,"label"=>$parameters[0])));
                  $transcript_ids = array_map(function($x) {return $x['TranscriptsLabels']['transcript_id'];}, $transcript_data);
                  break;
              default:
                  $transcript_ids = [];
          }
          // Return an error message if no transcript were selected
          if(empty($transcript_ids)) {
              return str_replace(["{label_class}", "{label_content}"], ["label-warning", "Error: no transcripts retrieved"], $label_base);
          }
          // Add transcripts to the subset
          // If the subset exsists, first remove transcripts from it (no support for `INSERT IGNORE` in CakePHP)?
          // TODO: find a better way to deal with that -- at the moment we added 'ignore' to the insertMulti() function of CakePHP.
          $counter = $this->TranscriptsLabels->enterTranscriptsByChunks($exp_id, $transcript_ids, $subset_name);
          // Return label with appropriate style / content
          return str_replace(["{label_class}", "{label_content}"], [$creation_class, $creation_msg . " (" . $counter . " transcripts)"], $label_base);
      }
  }


  function getTrapidSequences($exp_id,$transcript_data){
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $seq_type	= $this->DataSources->find("first",array("conditions"=>array("name"=>$exp_info['datasource']),"fields"=>"seq_type"));
    $seq_type 	= $seq_type['DataSources']['seq_type'];
    $result	= array();
    foreach($transcript_data as $td){
      $transcript_id		= $td['Transcripts']['transcript_id'];
      $transl_table = $td['Transcripts']['transl_table'];
      $transcript_sequence	= $td['Transcripts']['orf_sequence'];
      $result[$transcript_id]	= $transcript_sequence;
    }
    $result	= $this->Sequence->translate_multicds_php($result, $transl_table);
    return $result;
  }


  function getReferenceSequences($exp_id,$param_type,$param_value){
    // $exp_id	= mysql_real_escape_string($exp_id);
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
        pr($result);
      $result	= $this->Sequence->translate_multicds_php($result); // TODO: handle alternative translation tables here too! But solve that when fixing download.
    }
    return $result;
  }





  /******************************************************************************************************
   *
   * SEARCH RESULT PAGES :
   *
   ******************************************************************************************************
   */

    // TODO: replace raw queries?
    // TODO: modify error messages when searching functional annotation identifiers (minor).
    // TODO: handle non-reachable break statements.
    // TODO: redirect directly to functional annotation page when using exact ids?
    // Note:  when searching functional annotation identifiers, only results with the exact match are displayed.
    // Should we try to have consistent behaviour between id/descs (i.e. retrieve all labels matching the desc)?
    /**
     *
     * Process a user's search and display results depending on the selected search data type ('search_type') and search
     * query ('search_value'), both passed as the parameters of a POST request.
     *
     * @param null $exp_id the experiment id.
     */
    function search($exp_id=null) {
        parent::check_user_exp($exp_id);
        $exp_info = $this->Experiments->getDefaultInformation($exp_id);
        //	pr($exp_info); // Debug
        $this->TrapidUtils->checkPageAccess($exp_info['title'], $exp_info["process_state"], $this->process_states["default"]);
        $this->set("exp_info", $exp_info);
        $this->set("exp_id", $exp_id);
        $this -> set('title_for_layout', 'Search results');

        if(!$_POST) {
            $this->redirect(array("controller"=>"trapid", "action"=>"experiment", $exp_id));
        }
        if(!array_key_exists("search_type", $_POST) || !array_key_exists("search_value", $_POST)) {
            $this->redirect(array("controller"=>"trapid", "action"=>"experiment", $exp_id));
        }
        // Get search type. `$st` is checked against valid types. If it's not valid, redirect to the experiment overview.
        $st	= trim($_POST['search_type']);
        // Get search value and sanitize it
        // Should provide temporary relief before all the requests below are replaced
        // Useless if using `find()`, but there could be some raw SQL left
        $sv = filter_var(trim($_POST['search_value']),
            FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

        $this->set("search_type", $st);
        $this->set("search_value", $sv);
        if(strlen($sv) == 0) {
            $this->set("search_result", "bad_search");
            return;
        }

        // Perform search depending on user-selected search type (`$st`) and return results
        switch ($st) {
            // Transcript sequence
            case "transcript":
                $transcripts_info = $this->Transcripts->find("count", array("conditions" => array("experiment_id" => $exp_id, "transcript_id" => $sv)));
                if (!$transcripts_info) {
                    $this->set("search_result", "bad_search");
                    return;
                }
                else{
                    $this->redirect(array("controller" => "trapid", "action" => "transcript", $exp_id, urlencode($sv)));
                }
                break;

            // Reference DB gene identifier
            case "gene":
                // okay, this becomes complicated based on the type of reference database / GF.
                // If the used reference database is EggNOG, we need to check all GFs the ref. gene belongs to.
                // If it is a PLAZA database, the two GF types need to be handled:
                //  * If iORTHO, we do a query on the gf_content row of the table gene families (not optimal)
                //  * If HOM, we do a query on the associated PLAZA database. This should be a relatively fast query,
                // from which we can further on deduct the associated gene family.
                $db_type = $exp_info['ref_db_type'];
                $this->set("db_type", $db_type);  // move?
                // EggNOG reference database: potentially multiple GFs to handle for a single gene ID (tax. scope)
                if ($db_type == "eggnog") {
                    // Find gene. If there is no corresponding gene in `annotation`, return an error
                    $genes = $this->Annotation->find("first", array("conditions" => array("gene_id" => $sv)));
                    if (!$genes) {
                        $this->set("search_result", "bad_search");
                        return;
                    }
                    $gene_id = $genes['Annotation']['gene_id'];
                    // Get associated GF information from reference DB
                    $gene_family_data = $this->GfData->find("all", array("fields" => "gf_id", "conditions" => array("gene_id" => $gene_id)));
                    // If no GF is associated to the provided gene identifier, return an error
                    if (!$gene_family_data) {
                        $this->set("search_result", "bad_search");
                        return;
                    }
                    // Find matching GF data in TRAPID experiment
                    $gf_ids = array_map(function ($x) {
                        return $x['GfData']['gf_id'];
                    }, $gene_family_data);
                    $gf_info = $this->GeneFamilies->find("all", array("conditions" => array("experiment_id" => $exp_id, "plaza_gf_id" => $gf_ids), "fields" => array("gf_id", "plaza_gf_id", "num_transcripts")));
                    // If nothing corresponds, return an error
                    if (!$gf_info) {
                        $this->set("search_result", "bad_search");
                        return;
                    }
                    $this->set("search_result", "gene");
                    $this->set("gf_info", $gf_info);
                    return;
                }
                // PLAZA reference database (default)
                else {
                    // iORTHO GF
                    if ($exp_info['genefamily_type'] == "IORTHO") {
                        // TODO: check the method used to retrieve data (returns only one GF?)
                        $gf_info = $this->GeneFamilies->findByGene($exp_id, $sv);
                        if (!$gf_info) {
                            $this->set("search_result", "bad_search");
                            return;
                        }
                        $this->set("search_result", "gene");
                        $this->set("gf_info", $gf_info);
                        return;
                    }
                    else if ($exp_info['genefamily_type'] == 'HOM') {
                        // Find gene. If there is no corresponding gene in `annotation`, return an error
                        $genes = $this->Annotation->find("first", array("conditions" => array("gene_id" => $sv)));
                        if (!$genes) {
                            $this->set("search_result", "bad_search");
                            return;
                        }
                        $gene_id = $genes['Annotation']['gene_id'];
                        // Get associated GF information from reference DB
                        $gene_family_data = $this->GfData->find("first", array("fields" => "gf_id", "conditions" => array("gene_id" => $gene_id, "gf_id LIKE" => $exp_info['gf_prefix'] . "%")));
                        // If no GF is associated to the provided gene identifier, return an error
                        if (!$gene_family_data) {
                            $this->set("search_result", "bad_search");
                            return;
                        }
                        // Find matching GF data in TRAPID experiment
                        $gf_id = $gene_family_data['GfData']['gf_id'];
                        $gf_info = $this->GeneFamilies->find("first", array("conditions" => array("experiment_id" => $exp_id, "plaza_gf_id" => $gf_id), "fields" => array("gf_id", "plaza_gf_id", "num_transcripts")));
                        // If nothing corresponds, return an error
                        if (!$gf_info) {
                            $this->set("search_result", "bad_search");
                            return;
                        }
                        $gf_info = $gf_info['GeneFamilies'];
                        $this->set("search_result", "gene");
                        $this->set("gf_info", $gf_info);
                        return;
                    }
                    else {
                        $this->redirect("/");
                    }
                }
                break;

            // Gene family
            case "gf":
                $transcripts_info = $this->GeneFamilies->find("count", array("conditions" => array("experiment_id" => $exp_id, "gf_id LIKE" => "%$sv%")));
                if (!$transcripts_info) {
                    $this->set("search_result", "bad_search");
                    return;
                }
                else {
                    // Find first result matching GF query .. Why not display all the matches instead?
                    $gf = $this->GeneFamilies->find("first", array("conditions" => array("experiment_id" => $exp_id, "gf_id LIKE" => "%$sv%")));
                    $gf_id = $gf['GeneFamilies']['gf_id'];
                    // pr($gf_id);
                    // return;
                    $this->redirect(array("controller" => "gene_family", "action" => "gene_family", $exp_id, urlencode($gf_id)));
                }
                break;

            // GO term
            case "go":
               // check on length of search value.
                if (strlen($sv) < 3) {
                    $this->set("search_result", "bad_search");
                    $this->set("error", "GO term query should be 3 characters or more");
                    return;
                }
                // If the query is a valid GO term identifier, perform search using this ID
                if ($this->ExtendedGo->isValidGoIdPattern($sv)) {
                    $go_terms = $this->ExtendedGo->find("all", array("conditions" => array("name" => $sv, "type" => "go")));
                }
                // Otherwise, it is a description: first check if it is an exact description (e.g. from search suggestions),
                // or find all GO term IDs matching the search string.
                else {
                    $go_terms = $this->ExtendedGo->find("all", array("conditions" => array("desc LIKE" => "%$sv%", "type" => "go")));
                }
                if (!$go_terms) {
                    $this->set("search_result", "bad_search");
                    $this->set("error", "Unknown GO description");
                    return;
                }
                $go_terms = $this->TrapidUtils->indexArrayMulti($go_terms, "ExtendedGo", "name", array("desc", "info"));
                // Find possible associated transcripts
                $transcripts_info = $this->TranscriptsGo->findTranscriptsFromGo($exp_id, $go_terms);
                if (!$transcripts_info) {
                    $this->set("search_result", "bad_search");
                    return;
                }
                $this->set("transcripts_info", $transcripts_info);
                $this->set("search_result", "go");
                break;

            // InterPro domain
            case "interpro":
                // check on length of search value
                if (strlen($sv) < 3) {
                    $this->set("search_result", "bad_search");
                    $this->set("error", "Protein domain query should be 3 characters or more");
                    return;
                }

                // If the query is a valid InterPro identifier, perform search using this ID
                if ($this->ProteinMotifs->isValidIprIdPattern($sv)) {
                    // $this->set("is_identifier", true);
                    $ipr_terms = $this->ProteinMotifs->find("all", array("conditions" => array("name" => $sv, "type" => "interpro")));
                }
                // Otherwise, it is a description: find all InterPro IDs matching it
                else {
                    $ipr_terms = $this->ProteinMotifs->find("all", array("conditions" => array("desc LIKE" => "%$sv%", "type" => "interpro")));
                }

                if (!$ipr_terms) {
                    $this->set("search_result", "bad_search");
                    $this->set("error", "Unknown InterPro description");
                    return;
                }
                $ipr_terms = $this->TrapidUtils->indexArraySimple($ipr_terms, "ProteinMotifs", "name", "desc");
                // Find possible associated transcripts
                $transcripts_info = $this->TranscriptsInterpro->findTranscriptsFromInterpro($exp_id, $ipr_terms);
                if (!$transcripts_info) {
                    $this->set("search_result", "bad_search");
                    return;
                }
                $this->set("transcripts_info", $transcripts_info);
                $this->set("search_result", "interpro");
                break;

            // KO term
            case "ko":
                // Check on length of search value
                if (strlen($sv) < 3) {
                    $this->set("search_result", "bad_search");
                    $this->set("error", "KO term query should be 3 characters or more");
                    return;
                }
                // If the query is a valid KO term identifier, perform search using this ID
                if ($this->KoTerms->isValidKoIdPattern($sv)) {
                    $ko_terms = $this->KoTerms->find("all", array("conditions" => array("name" => $sv, "type" => "ko")));
                } // Otherwise, it is a description: find all KO term IDs matching it
                else {
                    $ko_terms = $this->KoTerms->find("all", array("conditions" => array("desc LIKE" => "%$sv%", "type" => "ko")));
                }

                if (!$ko_terms) {
                    $this->set("search_result", "bad_search");
                    $this->set("error", "Unknown KO term description");
                    return;
                }
                $ko_terms = $this->TrapidUtils->indexArraySimple($ko_terms, "KoTerms", "name", "desc");
                // Find possible associated transcripts
                $transcripts_info = $this->TranscriptsKo->findTranscriptsFromKo($exp_id, $ko_terms);
                if (!$transcripts_info) {
                    $this->set("search_result", "bad_search");
                    return;
                }
                $this->set("transcripts_info", $transcripts_info);
                $this->set("search_result", "ko");
                break;

            // Meta-annotation
            case "meta_annotation":
                if (!in_array(strtolower($sv), ["no information", "partial", "quasi full length", "full Length"])) {
                    $this->set("search_result", "bad_search");
                    return;
                }
                $clean_sv = ucwords(strtolower($sv));  // Title case transform for cleaner result display
                $this->redirect(array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "meta_annotation", urlencode($clean_sv)));
                break;

            // RNA family
            case "rf":
                $transcripts_info = $this->RnaFamilies->find("count", array("conditions" => array("experiment_id" => $exp_id, "rf_id LIKE" => "%$sv%")));
                if (!$transcripts_info) {
                    $this->set("search_result", "bad_search");
                    return;
                }
                else {
                    $rf = $this->RnaFamilies->find("first", array("conditions" => array("experiment_id" => $exp_id, "rf_id LIKE" => "%$sv%")));
                    $rf_id = $rf['RnaFamilies']['rf_id'];
                    $this->redirect(array("controller" => "rna_family", "action" => "rna_family", $exp_id, urlencode($rf_id)));
                }
                break;

            // Invalid search type `$st`
            default:
                $this->redirect(array("controller" => "trapid", "action" => "experiment", $exp_id));
                break;
        }
    }


    /*
     * Suggest search results to user (autocomplete). It takes a search string and a data type as input,
     * and found results from the appropriate table table are returned as JSON, using different formats depending on
     * the data type.
     *
     * To provide search suggestions, we perform two types of queries:
     *      1. Look for match using the LIKE operator (and limit the amount of fetched records to `$limit_results`).
     *      2. Look for an exact match with the search string
     */

    function suggest_search($exp_id=null, $search_type=null, $search_str=null) {
        // header('Expires: '.date('r', strtotime('+1 day')));
        // header('Content-type: application/json; charset=utf-8');
        $this->autoRender = false;
        $this->response->type('json');
        $limit_results = 60;  // Retrieve only this amount of results ('LIKE' clause in query)
        $show_results =  15;  // Display only this amount of results (the most similar to the query `$search_str`)
        $min_length = 3;  // Minimum length of search string to search, will return nothing if less than that.
        $allowed_types = ["transcript", "gene", "gf", "rf", "go", "interpro", "ko", "meta_annotation"];

        // Basic checks: experiment access rights, existence and validity of required parameters
        parent::check_user_exp($exp_id);
        if(!$exp_id || !$search_type || !$search_str){return;}
        if(!in_array($search_type, $allowed_types)){return;}
        if(strlen($search_str) < $min_length){return;}

        $search_str = filter_var($search_str, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);  // Useless if using `find()`?
        $search_str = str_replace("__", ":", $search_str);
        $search_str = strtolower($search_str);
        $suggestions = [];

        // Retrieve data depending on search type
        switch ($search_type) {
            case "transcript":
                $search_data = $this->Transcripts->find("all", array(
                    "fields"=>array("transcript_id"),
                    "conditions"=>array("experiment_id"=>$exp_id, "transcript_id LIKE"=>"%$search_str%"),
                    "limit"=>$limit_results)
                );
                $suggestions = array_map(function($x) {return $x['Transcripts']['transcript_id'];}, $search_data);
                $exact_data = $this->Transcripts->find("first", array(
                        "fields"=>array("transcript_id"),
                        "conditions"=>array("experiment_id"=>$exp_id, "transcript_id"=>$search_str))
                );
                if($exact_data) {
                    $suggestions[] = $exact_data['Transcripts']['transcript_id'];
                }
                break;

            case "gf":
                $search_data = $this->GeneFamilies->find("all", array(
                        "fields"=>array("gf_id"),
                        "conditions"=>array("experiment_id"=>$exp_id, "gf_id LIKE"=>"%$search_str%"),
                        "limit"=>$limit_results)
                );
                $suggestions = array_map(function($x) {return $x['GeneFamilies']['gf_id'];}, $search_data);
                $exact_data = $this->GeneFamilies->find("first", array(
                        "fields"=>array("gf_id"),
                        "conditions"=>array("experiment_id"=>$exp_id, "gf_id"=>$search_str))
                );
                if($exact_data) {
                    $suggestions[] =  $exact_data['GeneFamilies']['gf_id'];
                }
                break;

            case "rf":
                $search_data = $this->RnaFamilies->find("all", array(
                        "fields"=>array("rf_id"),
                        "conditions"=>array("experiment_id"=>$exp_id, "rf_id LIKE"=>"%$search_str%"),
                        "limit"=>$limit_results)
                );
                $suggestions = array_map(function($x) {return $x['RnaFamilies']['rf_id'];}, $search_data);
                $exact_data = $this->RnaFamilies->find("first", array(
                        "fields"=>array("rf_id"),
                        "conditions"=>array("experiment_id"=>$exp_id, "rf_id"=>$search_str))
                );
                if($exact_data) {
                    $suggestions[] =  $exact_data['RnaFamilies']['rf_id'];
                }
                break;

            case "go":
                // If query starts with 'GO:' & contains 1-7 digits, make suggestions based on GO identifiers.
                if(preg_match("/^GO:[0-9]{1,7}$/i", $search_str)) {
                    $search_data = $this->ExtendedGo->find("all", array(
                            "fields"=>array("name", "desc", "info"),
                            "conditions"=>array("type"=>"go", "LOWER(name) LIKE"=>"$search_str%"),  // LOWER() since `name` uses a case-sensitive collation in EggNOG ref. DB
                            "limit"=>$limit_results)
                    );
                    foreach($search_data as $sd) {
                        $suggestions[] = array("name"=>$sd['ExtendedGo']['name'], "info"=>$sd['ExtendedGo']['info'], "desc"=>$sd['ExtendedGo']['desc']);
                    }
                }
                else {
                    $search_data = $this->ExtendedGo->find("all", array(
                            "fields"=>array("name", "desc", "info"),
                            "conditions"=>array("type"=>"go", "desc LIKE"=>"%$search_str%"),
                            "limit"=>$limit_results)
                    );
                    foreach($search_data as $sd) {
                        $suggestions[] = array("name"=>$sd['ExtendedGo']['name'], "info"=>$sd['ExtendedGo']['info'], "desc"=>$sd['ExtendedGo']['desc']);
                    }
                    $exact_data =  $this->ExtendedGo->find("first", array(
                            "fields"=>array("name", "desc", "info"),
                            "conditions"=>array("type"=>"go", "desc"=>$search_str))
                    );
                    if($exact_data) {
                        $suggestions[] =  array("name"=>$exact_data['ExtendedGo']['name'], "info"=>$exact_data['ExtendedGo']['info'], "desc"=>$exact_data['ExtendedGo']['desc']);
                    }
                }
                break;

            case "interpro":
                // If query starts with 'IPR' & contains 1-6 digits, make suggestions based on InterPro identifiers.
                if(preg_match("/^IPR[0-9]{1,6}$/i", $search_str)) {
                    $search_data = $this->ProteinMotifs->find("all", array(
                            "fields"=>array("name", "desc", "info"),
                            "conditions"=>array("type"=>"interpro", "name LIKE"=>"$search_str%"),
                            "limit"=>$limit_results)
                    );
                    foreach ($search_data as $sd) {
                        $suggestions[] = array("name"=>$sd['ProteinMotifs']['name'], "info"=>$sd['ProteinMotifs']['info'], "desc"=>$sd['ProteinMotifs']['desc']);
                    }
                }
                else {
                    $search_data = $this->ProteinMotifs->find("all", array(
                            "fields"=>array("name", "desc", "info"),
                            "conditions"=>array("type"=>"interpro", "desc LIKE"=>"%$search_str%"),
                            "limit"=>$limit_results)
                    );
                    foreach($search_data as $sd) {
                        $suggestions[] = array("name"=>$sd['ProteinMotifs']['name'], "info"=>$sd['ProteinMotifs']['info'], "desc"=>$sd['ProteinMotifs']['desc']);
                    }
                    $exact_data =  $this->ProteinMotifs->find("first", array(
                            "fields"=>array("name", "desc", "info"),
                            "conditions"=>array("type"=>"interpro", "desc"=>$search_str))
                    );
                    if($exact_data) {
                        $suggestions[] =  array("name"=>$exact_data['ProteinMotifs']['name'], "info"=>$exact_data['ProteinMotifs']['info'], "desc"=>$exact_data['ProteinMotifs']['desc']);
                    }
                }
                break;

            case "ko":
                // If query starts with 'K' & contains 2-5 digits, make suggestions based on KO term identifiers.
                if(preg_match("/^K[0-9]{2,5}$/i", $search_str)) {
                    $search_data = $this->KoTerms->find("all", array(
                            "fields"=>array("name", "desc"),
                            "conditions"=>array("type"=>"ko", "LOWER(name) LIKE"=>"$search_str%"),  // LOWER() since `name` uses a case-sensitive collation in EggNOG ref. DB
                            "limit"=>$limit_results)
                    );
                    foreach ($search_data as $sd) {
                        $suggestions[] = array("name"=>$sd['KoTerms']['name'], "desc"=>$sd['KoTerms']['desc']);
                    }
                }
                else {
                    $search_data = $this->KoTerms->find("all", array(
                            "fields"=>array("name", "desc"),
                            "conditions"=>array("type"=>"ko", "desc LIKE"=>"%$search_str%"),
                            "limit"=>$limit_results)
                    );
                    foreach($search_data as $sd) {
                        $suggestions[] = array("name"=>$sd['KoTerms']['name'], "desc"=>$sd['KoTerms']['desc']);
                    }
                    $exact_data =  $this->KoTerms->find("first", array(
                            "fields"=>array("name", "desc"),
                            "conditions"=>array("type"=>"ko", "desc"=>$search_str))
                    );
                    if($exact_data) {
                        $suggestions[] =  array("name"=>$exact_data['KoTerms']['name'], "desc"=>$exact_data['KoTerms']['desc']);
                    }
                }
            break;

            case "meta_annotation":
                $ma_values = ["No Information", "Partial", "Quasi Full Length", "Full Length"];
                foreach($ma_values as $ma) {
                    if(stripos($ma, $search_str)!==false) {
                        $suggestions[] = $ma;
                    }
                }
                break;

            // Would it be useful to retrieve/display species as well?
            // Too slow when using EggNOG ref. DB... To either change or disable for this data type
            case "gene":
                /*
                $search_data = $this->Annotation->find("all", array(
                        "fields"=>array("gene_id"),
                        "conditions"=>array("gene_id LIKE"=>"%$search_str%"),
                        "limit"=>$limit_results)
                );
                $suggestions = array_map(function($x) {return $x['Annotation']['gene_id'];}, $search_data);
                $exact_data =  $this->Annotation->find("first", array(
                        "fields"=>array("gene_id"),
                        "conditions"=>array("gene_id"=>$search_str))
                );
                if($exact_data) {
                    $suggestions[] = $exact_data['Annotation']['gene_id'];
                }
                */
                break;

            default:
                break;
        }

        // Remove duplicates and sort by similarity to search string (Levenshtein distance)
        $suggestions = array_unique($suggestions, SORT_REGULAR);

        if(in_array($search_type, ['transcript', 'rf', 'gf', 'meta_annotation', 'gene'])) {
            usort($suggestions, function ($a, $b) use ($search_str) {
                $levA = levenshtein($search_str, $a);
                $levB = levenshtein($search_str, $b);
                return $levA === $levB ? 0 : ($levA > $levB ? 1 : -1);
            });
        }
        // Other data types (functional annotation) return a list of dict, we sort it by their 'desc' attribute
        else {
            uasort($suggestions, function ($a, $b) use ($search_str) {
                $levA = levenshtein($search_str, $a['desc']);
                $levB = levenshtein($search_str, $b['desc']);
                return $levA === $levB ? 0 : ($levA > $levB ? 1 : -1);
            });
        }
        $suggestions = array_slice($suggestions, 0, $show_results);
        return(json_encode($suggestions));
    }






   /*******************************************************************************************************
   *
   *  DATA PROCESSING
   *
   ********************************************************************************************************/



  function enrichment_preprocessing($exp_id=null){
    parent::check_user_exp($exp_id);
    $exp_info = $this->Experiments->getDefaultInformation($exp_id);
    // Important checks: is the experiment in finished state and is the enrichment_state not in 'processing'?
    if($exp_info['process_state']!="finished" || $exp_info['enrichment_state']=='processing'){
      $this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
    }
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    $all_subsets = $this->TranscriptsLabels->getLabels($exp_id);
    $this->set("num_subsets", count($all_subsets));

    //ok, there are no actual settings: the p-value cutoff is set at the lowest, and these results are stored
    //if other p-values are needed, they can be extracted with filtering.
    $possible_pvalues	= array(0.05,0.01,0.005,0.001,1e-4,1e-5);

    // Depending on the type of reference database, different type of functional annotation are available
    // For PLAZA databases
    $possible_types	= array("go"=>"GO","ipr"=>"Protein domain");
    // Check DB type (quick and dirty)
    if(strpos($exp_info["used_plaza_database"], "eggnog") !== false){
        // Modify available types for EggNOG database
        $possible_types = array("go"=>"GO", "ko"=>"KO");
    }
    // Users don't need to select an annotation type anymore. Instead, enrichments are computed for all possible types.
    // $this->set("possible_types",$possible_types);
    $this->set("title_for_layout", "Preprocess functional enrichments");

    if($_POST){
      $type		= null;
      // if(array_key_exists("type",$_POST)){$type	= mysql_real_escape_string($_POST['type']);}
      // `$type` is already checked against `$possible_types`
        foreach(array_keys($possible_types) as $type) {
            if(!array_key_exists($type,$possible_types)) {
                $this->redirect(array("controller"=>"trapid","action"=>"enrichment_preprocessing", $exp_id));
            }

            //create shell file for submission to the web-cluster.
            //Shell file contains both the necessary module load statements
            $qsub_file 	= $this->TrapidUtils->create_qsub_script($exp_id);
            $ini_file = $this->TrapidUtils->create_ini_file_enrichment($exp_id, $exp_info['used_plaza_database']);
            $shell_file	= $this->TrapidUtils->create_shell_file_enrichment_preprocessing($exp_id,$ini_file,$type,$possible_pvalues,$all_subsets);
            if($shell_file == null || $qsub_file == null ){$this->set("error","problem creating program files");return;}

            //ok, now we submit this program to the web-cluster
            $tmp_dir	= TMP."experiment_data/".$exp_id."/";
            $qsub_out	= $tmp_dir."/".$type."_enrichment_preprocessing.out";
            $qsub_err	= $tmp_dir."/".$type."_enrichment_preprocessing.err";
            if(file_exists($qsub_out)){unlink($qsub_out);}
            if(file_exists($qsub_err)){unlink($qsub_err);}

            $output   = array();
            $command  = "sh $qsub_file -q medium -o $qsub_out -e $qsub_err $shell_file";
            exec($command, $output);
            $job_id	= $this->TrapidUtils->getClusterJobId($output);

            //indicate int the database the new job-id
            $this->ExperimentJobs->addJob($exp_id,$job_id, "medium", "enrichment_preprocessing_" . $type);

            //indicate in the database that the current experiments enrichment_state is "processing"
            $this->Experiments->updateAll(array("enrichment_state"=>"'processing'"),array("experiment_id"=>$exp_id));

            $this->ExperimentLog->addAction($exp_id,"enrichment_preprocessing","");
            $this->ExperimentLog->addAction($exp_id,"enrichment_preprocessing","options",1);
            $this->ExperimentLog->addAction($exp_id,"enrichment_preprocessing","data_type=".$type,2);
            $this->ExperimentLog->addAction($exp_id,"enrichment_preprocessing","start",1);
        }
        // Finally, redirect to experiments page
        $this->redirect(array("controller"=>"trapid", "action"=>"experiments"));
    } // end POST request

  }



  // TODO: modify logic to display/disable/hide some elements of the page to make function cleaner
  function initial_processing($exp_id=null){
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
     $tooltips = $this->TrapidUtils->indexArraySimple(
        $this->HelpTooltips->find("all", array("conditions"=>array("tooltip_id LIKE 'initial_processing%'"))),
        "HelpTooltips","tooltip_id","tooltip_text"
    );
    $user_group=$this->Authentication->find("first",array("fields"=>array("group"),"conditions"=>array("user_id"=>parent::check_user())));
    if($user_group['Authentication']['group'] != "admin"){
      $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["upload"]);
    }
    $this->set("exp_id", $exp_id);
    $this->set("exp_info", $exp_info);
    $this -> set('title_for_layout', "Perform initial processing");
    $this->set("tooltips", $tooltips);

    // Retrieve RFAM clans information
    $rfam_clans_default = $this->Configuration->getRfamClansDefault();
    $rfam_clans = $this->Configuration->getRfamClansMetadata();
    $this->set("rfam_clans_default", $rfam_clans_default);
    $this->set("rfam_clans", $rfam_clans);

    // Retrieve translation table information
      $transl_table_data = $this->Configuration->find("all", array("conditions"=>array("method"=>"transl_tables", "attr"=>"desc"), "fields"=>array("key","value")));
      $transl_table_descs = array();
      foreach ($transl_table_data as $tt){
          $idx = $tt['Configuration']['key'];
          $desc = $tt['Configuration']['value'];
          $transl_table_descs[$idx] = $desc;
      }
      ksort($transl_table_descs);
      $this->set("transl_table_descs", $transl_table_descs);


      $possible_db_types	= array("SINGLE_SPECIES"=>"Single Species","CLADE"=>"Phylogenetic Clade","GF_REP"=>"Gene Family Representatives");
    $possible_gf_types	= array("HOM"=>"Gene Families","IORTHO"=>"Integrative Orthology");
    // $possible_gf_types	= array("HOM"=>"Gene Families");  // ,"IORTHO"=>"Integrative Orthology");

    //retrieve species information for BLAST info
    $species_info = $this->AnnotSources->getSpeciesCommonNames();
    $data_sources = $this->DataSources->find("first",array("conditions"=>array("db_name"=>$exp_info["used_plaza_database"])));
    $clades	= $this->TrapidUtils->valueToIndexArray(explode(";",$data_sources["DataSources"]["clades"]));
    ksort($clades);
    // Replace RapSearch2 by DIAMOND
    // $species_info	= $this->TrapidUtils->checkAvailableRapsearchDB($exp_info['used_plaza_database'],$species_info);
    // $clades		= $this->TrapidUtils->checkAvailableRapsearchDB($exp_info['used_plaza_database'],$clades);
    // $gf_representatives	= $this->TrapidUtils->checkAvailableRapsearchDB($exp_info['used_plaza_database'],array("gf_representatives"=>"Genefamily representatives"));
    $species_info	= $this->TrapidUtils->checkAvailableDiamondDB($exp_info['used_plaza_database'],$species_info);
    $clades		= $this->TrapidUtils->checkAvailableDiamondDB($exp_info['used_plaza_database'],$clades);
    $gf_representatives	= $this->TrapidUtils->checkAvailableDiamondDB($exp_info['used_plaza_database'],array("gf_representatives"=>"Genefamily representatives"));


    $this->set("available_species",$species_info);
    $this->set("clades_species",$clades);
    $this->set("gf_representatives",$gf_representatives);
    if(count($species_info)==0) {
        $this->set("error","No valid species similarity search databases found. ");
        $this->set("no_species_available", true);
    }
    if(count($clades)==0 && count($species_info)>0){$this->set("error","No valid clades similarity search databases found. ");}
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
    // Note: technically not true, as using evalue `-x` in RapSearch will put the threshold value at x = log10(e-value threshold).
    // So the real threshold is rather 1e-x, not 10e-x
/*    $possible_evalues	= array("10e-2"=>"-2","10e-3"=>"-3","10e-4"=>"-4",
				"10e-5"=>"-5","10e-6"=>"-6","10e-7"=>"-7",
				"10e-8"=>"-8","10e-9"=>"-9","10e-10"=>"-10");*/
    // Replace possible e-values by -log10 values
    $possible_evalues = array("2", "3", "4", "5", "6", "7", "8", "9", "10");
    $this->set("possible_evalues",$possible_evalues);

    //possible func annots
    $possible_func_annot  = array(
				"gf"=>"Transfer based on gene family",
				"besthit"=>"Transfer from best similarity hit",
				"gf_besthit"=>"Transfer from both GF and best hit"
				);
    $this->set("possible_func_annot",$possible_func_annot);

    // If we are using EggNOG data, set taxonomic scope data
      $tax_scope_data = null;
      if(strpos($exp_info["used_plaza_database"], "eggnog")!== false) {
          // Populate `$tax_scope_data` with all possible values
          $tax_scope_data = $this->AnnotSources->getEggnogTaxLevels();
      }

      $this->set("tax_scope_data", $tax_scope_data);

      // Get default clade for current reference database
      $default_sim_search_clade = $this->Configuration->find("first", array("fields"=>"value", "conditions"=>array("method"=>"initial_processing_settings", "key"=>$data_sources['DataSources']['db_name'], "attr"=>"default_sim_search_clade")));
      $default_sim_search_clade = $default_sim_search_clade['Configuration']['value'];
      $this->set("default_sim_search_clade", $default_sim_search_clade);

      if($_POST){
//        pr($_POST);
//        return;

      // Parameter checking.
      // TODO: check existence of new keys (tax binning, nc RNA annotation, translation table, etc.)
      if(!(array_key_exists("blast_db_type",$_POST) && array_key_exists("blast_db",$_POST)
	   && array_key_exists("blast_evalue",$_POST) && array_key_exists("gf_type",$_POST)
	   && array_key_exists("functional_annotation",$_POST)
	)){
	$this->set("error","Incorrect parameters : missing parameters");return;
      }
      $num_blast_hits	= 1;
      // $blast_db_type 	= mysql_real_escape_string($_POST['blast_db_type']);
      // $blast_db		= mysql_real_escape_string($_POST['blast_db']);
      // $blast_evalue	= mysql_real_escape_string($_POST['blast_evalue']);
      // $gf_type		= mysql_real_escape_string($_POST['gf_type']);
      // $func_annot	= mysql_real_escape_string($_POST['functional_annotation']);
      $blast_db_type 	= filter_var($_POST['blast_db_type'], FILTER_SANITIZE_STRING);
      $blast_db		= filter_var($_POST['blast_db'], FILTER_SANITIZE_STRING);
      $blast_evalue	= filter_var($_POST['blast_evalue'], FILTER_SANITIZE_STRING);
      $gf_type		= filter_var($_POST['gf_type'], FILTER_SANITIZE_STRING);
      $func_annot	= filter_var($_POST['functional_annotation'], FILTER_SANITIZE_STRING);
      $tax_scope = "";
      if(isset($_POST['tax-scope'])) {
          $tax_scope = filter_var($_POST['tax-scope'], FILTER_SANITIZE_STRING);
      }
      $perform_tax_binning = false;
      $use_cds = false;
      $transl_table = 1;
      $used_blast_desc	= "";
      if(!(array_key_exists($blast_db_type, $possible_db_types) &&
	   array_key_exists($gf_type, $possible_gf_types) &&
	   in_array($blast_evalue,$possible_evalues) &&
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

      // Tax binning
        if(isset($_POST['tax-binning']) && $_POST['tax-binning'] == 'y') {
          $perform_tax_binning = true;
        }

      // Sequence type (i.e. input sequences are CDS).
      if(isset($_POST['use-cds']) && $_POST['use-cds'] == 'y') {
        $use_cds = true;
      }

      // Check translation table existence/validity
      $possible_transl_tables = array_keys($transl_table_descs);
      if(!isset($_POST['transl_table'])){
          $this->set("error","Incorrect genetic code/translation table, please try again. ");return;
      }
      // if it's invalid, just ignore it seilently and use translation table 1.
      // If it's in the list of possible translation tables, update the value of `$transl_table`
      if(in_array($_POST['transl_table'], $possible_transl_tables)) {
          $transl_table = filter_var($_POST['transl_table'], FILTER_SANITIZE_STRING);  // To move up with the rest? Let's first make it work.
      }

      // EggNOG taxonomic scope data check (should be in the list of possible choice or 'auto')
      // `$tax_scope` not empty == we are working with EggNOG
        if($tax_scope) {
          $possible_scopes = array_keys($tax_scope_data);
          array_push($possible_scopes, "auto");
          if(!in_array($tax_scope, $possible_scopes)) {
              $this->set("error","Incorrect parameters: unrecognized taxonomic scope - ".$tax_scope);
              return;
          }
        }

      // Non-coding RNA processing: RFAM clan selection
      $all_rfam_clans = array_keys($this->Configuration->getRfamClansMetadata());
      $rfam_clans = array();
      if(isset($_POST['rfam-clans'])) {
          foreach ($_POST['rfam-clans'] as $clan) {
              // Check if the (sanitized) clan accession exists in `configuration`. If not throw an error.
              $clan_acc = filter_var($clan, FILTER_SANITIZE_STRING);  // No need to sanitize since selected clans are compared to a list of valid clans already?
              $clan_acc = $clan;
              if (!in_array($clan_acc, $all_rfam_clans)) {
                  $this->set("error", "Incorrect parameters: invalid RFAM clan selected - " . $clan_acc);
                  return;
              }
              array_push($rfam_clans, $clan_acc);
          }
          // Create string with all selected RFAM clan accessions
          $rfam_clans_str = implode(",", $rfam_clans);
      }
      else {
          $rfam_clans_str = "None";
      }

      // pr($_POST);
      // return;

      // Parameters are ok: we can now proceed with the actual pipeline organization.
      // Create shell file for submission to the web-cluster.
      // Shell file contains both the necessary module load statements
      // as well as the correct name for the global perl-file.
      // A single "initial processing" job should only run on a single cluster node
      $qsub_file  = $this->TrapidUtils->create_qsub_script($exp_id);
      // Create configuration file for initial processing of this experiment
      $ini_file = $this->TrapidUtils->create_ini_file_initial($exp_id,$exp_info['used_plaza_database'],$blast_db,$gf_type,$num_blast_hits,$blast_evalue, $func_annot, $perform_tax_binning, $tax_scope, $rfam_clans_str, $use_cds, $transl_table);
      // pr($ini_file);
      // $shell_file = $this->TrapidUtils->create_shell_file_initial($exp_id,$exp_info['used_plaza_database'],$blast_db,$gf_type,$num_blast_hits,$possible_evalues[$blast_evalue],$func_annot, $perform_tax_binning);
      // $shell_file = $this->TrapidUtils->create_shell_file_initial($exp_id,$exp_info['used_plaza_database'],$blast_db,$gf_type,$num_blast_hits,$blast_evalue, $func_annot, $perform_tax_binning, $tax_scope);
      $shell_file = $this->TrapidUtils->create_shell_file_initial($exp_id, $ini_file);
      if($shell_file == null || $qsub_file == null || $ini_file == null){$this->set("error", "Problem creating program files");return;}

      //ok, now we submit this program to the web-cluster
      $tmp_dir	= TMP."experiment_data/".$exp_id."/";
      $qsub_out	= $tmp_dir."initial_processing.out";
      $qsub_err	= $tmp_dir."initial_processing.err";
      if(file_exists($qsub_out)){unlink($qsub_out);}
      if(file_exists($qsub_err)){unlink($qsub_err);}

      $output   = array();
      $command  = "sh $qsub_file -pe serial 2 -q long -o $qsub_out -e $qsub_err $shell_file";
      // pr($command);
      // return;
      exec($command,$output);
      $job_id	= $this->TrapidUtils->getClusterJobId($output);

      //indicate int the database the new job-id
      $this->ExperimentJobs->addJob($exp_id,$job_id,"long","initial_processing");

      //indicate in the database that the current experiment is "busy", and should as such not be accessible.
      $this->Experiments->updateAll(array("process_state"=>"'processing'","genefamily_type"=>"'".$gf_type."'","last_edit_date"=>"'".date("Y-m-d H:i:s")."'","used_blast_database"=>"'".$possible_db_types[$blast_db_type]."/".$used_blast_desc."'", "perform_tax_binning"=>(int)$perform_tax_binning),array("experiment_id"=>$exp_id));
      if($gf_type=="IORTHO"){
	$this->Experiments->updateAll(array("target_species"=>"'".$blast_db."'"),array("experiment_id"=>$exp_id));
      }

      $this->ExperimentLog->addAction($exp_id,"initial_processing","");
      $this->ExperimentLog->addAction($exp_id,"initial_processing","options",1);
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","blast_db_type=".$blast_db_type,2);
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","blast_db=".$blast_db,2);
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","e_value=1e-".$blast_evalue,2);
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","gf_type=".$gf_type,2);
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","func_annot=".$func_annot,2);
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","transl_table=".$transl_table,2);
      if($tax_scope) {
          $this->ExperimentLog->addAction($exp_id,"initial_processing_options","taxonomic_scope=".$tax_scope, 2);
      }
      if($use_cds) {
            $this->ExperimentLog->addAction($exp_id,"initial_processing_options","use_cds=". (int) $use_cds, 2);
        }
      $this->ExperimentLog->addAction($exp_id,"initial_processing_options","n_rfam_clans=".sizeof($rfam_clans), 2);
      // TODO: If too many clans are selected, the value will be too long for `parameters`! Solve that once prototype works.
        if(sizeof($rfam_clans) <= 30 && $rfam_clans_str != "None"){
            $this->ExperimentLog->addAction($exp_id,"initial_processing_options","rfam_clans=".$rfam_clans_str, 2);
        }
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

    Configure::write("debug",1);
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
      $this->set("active_sidebar_item", "Import data");
      $this -> set('title_for_layout', "Import data");
    // $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["start"]);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    $this->set("exp_info", $exp_info);
    $this->set("exp_id", $exp_id);

    // Gey help tooltips data
    $tooltips = $this->TrapidUtils->indexArraySimple(
          $this->HelpTooltips->find("all", array("conditions"=>array("tooltip_id LIKE 'data_upload%'"))),
          "HelpTooltips","tooltip_id","tooltip_text"
      );
    $this->set("tooltips", $tooltips);

    //this is a basis location for creating the temp storage for an experiment.
    $tmp_dir	= TMP."experiment_data/".$exp_id."/";
    if(!file_exists($tmp_dir) || !is_dir($tmp_dir)){mkdir($tmp_dir,0777);}
    $chmodret = shell_exec("chmod -R a+rwx $tmp_dir");
    chmod($tmp_dir, 0777);
    $upload_dir = $tmp_dir."upload_files/";
    if(!file_exists($upload_dir) || !is_dir($upload_dir)){mkdir($upload_dir,0777);}
    shell_exec("chmod -R a+rwx $upload_dir");
    chmod($upload_dir,0777);

    // Check if there are any session data from `import_labels()` to handle -- can be an error (`subset_error`) or a message (`subset_message`)
    if ($this->Session->check("subset_error")) {
        $this->set("subset_error", $this->Session->read("subset_error"));
        $this->Session->delete("subset_error");
    }

    if ($this->Session->check("subset_message")) {
        $this->set("subset_message", $this->Session->read("subset_message"));
        $this->Session->delete("subset_message");
    }

    //give an overview of the content of the directory, so the user doesn't upload the same file twice
    //$uploaded_files = $this->TrapidUtils->readDir($upload_dir);
    $uploaded_files = $this->DataUploads->find('all', array('conditions' => array("user_id"=>$exp_info['user_id'],"experiment_id"=>$exp_id)));
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
	  // $uploadurl = mysql_real_escape_string($_POST['uploadedurl']);
      // No need to escape since we use Cake's ORM + array notation?
	  $uploadurl = filter_var($_POST['uploadedurl'], FILTER_SANITIZE_URL); // URL must be checked!
      $this->DataUploads->saveAll(array(array("user_id"=>$exp_info['user_id'],"experiment_id"=>$exp_id,"type"=>"url",
              "name"=>$uploadurl,"label"=>$label_name,"status"=>"to_download")));
      $this->redirect(array("controller"=>"trapid","action"=>"import_data",$exp_id));
	}
	//#####   FILE  ###########################  --> IMMEDIATELY UNZIP DATA!!!
	else if($_POST['uploadtype']=="file" && array_key_exists("uploadedfile",$_FILES)){
	   // Note (2018/05/08): I put much higher values for these limits so we can start processing big datasets from the web interface
/*	   set_time_limit(180);
	   $MAX_FILE_SIZE_NORMAL		= 32000000;
	   $MAX_FILE_SIZE_ZIP			= 32000000;*/
        set_time_limit(1800);
        $MAX_FILE_SIZE_NORMAL		= 500000000;
        $MAX_FILE_SIZE_ZIP			= 500000000;
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
	$this->redirect(array("controller"=>"trapid", "action"=>"import_data", $exp_id));
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
        $command  = "sh $qsub_file -q medium -o $qsub_out -e $qsub_err ". " -cwd "  . $shell_file;
	$output   = array();
	$qsub_submit = exec($command . " 2>&1",$output);
	$job_id	= $this->TrapidUtils->getClusterJobId($output);

	//indicate in the database the new job-id
	$this->ExperimentJobs->addJob($exp_id,$job_id,"medium","database_upload");

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

    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["start"]);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    //this is a basis location for creating the temp storage for an experiment.
    $tmp_dir	= TMP."experiment_data/".$exp_id."/";
    if(!file_exists($tmp_dir) || !is_dir($tmp_dir)){
	mkdir($tmp_dir,0777);
	shell_exec("chmod a+rwx $tmp_dir");
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
	    	  $current_transcript = filter_var(trim(substr($buffer,1)), FILTER_SANITIZE_STRING);
	       	  // TODO: Add check if `filter_var()` returns FALSE?
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
	  $label_name	= filter_var($_POST['label_name'], FILTER_SANITIZE_STRING);
	  if($label_name) {
    	  $this->TranscriptsLabels->enterTranscripts($exp_id,$all_transcript_ids,$label_name);
      }
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


  // NOTE: should this method be merged with the rest in `import_data()`?
  function import_labels($exp_id=null){
    parent::check_user_exp($exp_id);
    $this->autoRender = false;
    $this->layout = "";
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
      if ($exp_info['transcript_count'] == 0) {
        return;
    }

    // Handle subset creation
    if ($this->request->is('post')) {
        $MAX_FILE_SIZE_NORMAL = 2000000;
        // Check for multiple invalid cases
        if(!isset($_FILES["uploadedfile"])) {
            // should we redirect somewhere else?
            $this->redirect(array("controller"=>"trapid","action"=>"experiment"));
        }
        if(($_FILES['uploadedfile']['name']=="") || ($_FILES['uploadedfile']['size'] == 0) ||($_FILES['uploadedfile']['tmp_name'] == "")){
            $this->Session->write("subset_error", "Illegal input file");
            $this->redirect(array("controller"=>"trapid","action"=>"import_data", $exp_id));
        }

        if(!isset($_POST['label'])||$_POST['label']==""){
            $this->Session->write("subset_error", "No label defined");
            $this->redirect(array("controller"=>"trapid","action"=>"import_data", $exp_id));
        }
        if($_FILES['uploadedfile']['size']>$MAX_FILE_SIZE_NORMAL){
            $this->Session->write("subset_error", "File is too large");
            $this->redirect(array("controller"=>"trapid","action"=>"import_data", $exp_id));
        }
        // Create the subset
        $label 		= Sanitize::paranoid($_POST['label'],array('.','_','-'));
        $myFile = $_FILES['uploadedfile']['tmp_name'];
        $fh = fopen($myFile,'r');
        $transcripts_input = fread($fh,filesize($myFile));
        $transcripts = preg_split("/[\s,]+/",$transcripts_input);
        $transcripts = array_unique($transcripts);
        fclose($fh);
        // Check correctness of both the transcripts and whether label already exists for the indicated transcripts
        $counter = $this->TranscriptsLabels->enterTranscripts($exp_id, $transcripts, $label);
        $this->ExperimentLog->addAction($exp_id,"label_definition", $label);
        $this->Session->write("subset_message", $counter." transcripts have been labeled as '".$label."' ");
        $this->redirect(array("controller"=>"trapid","action"=>"import_data", $exp_id));
    }
    else {
        // throw new NotFoundException();
        return;  // Return blank page instead?
    }
  }


    /**
     *
     * Export experiment data. Export is performed via POST request in which the export type is specified by the
     * `export_type` variable. The export file is created with the `performExport` function of the TrapidUtils component.
     *
     * @param null $exp_id the experiment id.
     */
    function export_data($exp_id=null) {
        // Maximum time allowed for an export job
        $max_timeout = 210;
        // Configure::write("debug",2);
        parent::check_user_exp($exp_id);
        $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
        $plaza_database	= $exp_info['used_plaza_database'];
        $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
        $this->set("exp_info",$exp_info);
        $this->set("exp_id",$exp_id);
        $this->set("active_sidebar_item", "Export data");
        $this -> set('title_for_layout', "Export data");

        $structural_export = array("transcript_id"=>"Transcript identifier","frame_info"=>"Frame information","frameshift_info"=>"Frameshift information","orf"=>"ORF information","meta_annotation"=>"Meta annotation");
        $structural_export_cols = array(	"transcript_id"=>array("transcript_id"),
            "frame_info"=>array("detected_frame","detected_strand","full_frame_info"),
//					"frameshift_info"=>array("putative_frameshift","is_frame_corrected"),
            "frameshift_info"=>array("putative_frameshift"),
            "orf"=>array("orf_start","orf_stop","orf_contains_start_codon","orf_contains_stop_codon"),
            "meta_annotation"=>array("meta_annotation","meta_annotation_score")
        );
        $this->set("structural_export",$structural_export);

        $available_subsets		= $this->TranscriptsLabels->getLabels($exp_id);
        $this->set("available_subsets", $available_subsets);

        // Perform export
        if($_POST){
            // pr($_POST);
            set_time_limit($max_timeout);
            $timestamp = date('Ymd_his');
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
                if(is_null($file_path)) {
                    $this->set("export_failed", true);
                    return;
                }
                $this->set("file_path",$file_path);
                $this->redirect($file_path);
                return;
            }
            else if($export_type=="sequence"){

                if(!array_key_exists("sequence_type",$_POST)){return;}
                $sequence_type	= $_POST['sequence_type'];
                $subset_label = null;
                $outfile_suffix = "_exp" . $exp_id;
                // ok check?
                if(array_key_exists("subset_label",$_POST) && !empty($_POST['subset_label'])) {
                    $subset_label = filter_var($_POST['subset_label'], FILTER_SANITIZE_STRING);
                    $outfile_suffix = $outfile_suffix . "_" . $subset_label;
                }
                $file_path	= null;
                if($sequence_type=="original"){
                    $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"SEQ_TRANSCRIPT","transcripts" . $outfile_suffix . ".fasta", $subset_label);
                }
                else if($sequence_type=="orf"){
                    $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"SEQ_ORF","orfs" . $outfile_suffix . ".fasta", $subset_label);
                }
                else if($sequence_type=="aa"){
                    $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"SEQ_AA","proteins" . $outfile_suffix . ".fasta", $subset_label);
                }
                if(is_null($file_path)) {
                    $this->set("export_failed", true);
                    return;
                }
                $this->set("file_path",$file_path);
                $this->redirect($file_path);
                return;
            }

            else if($export_type=="tax"){
                $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"TAX_CLASSIFICATION","transcripts_tax_exp".$exp_id.".txt");
                if(is_null($file_path)) {
                    $this->set("export_failed", true);
                    return;
                }
                $this->set("file_path",$file_path);
                $this->redirect($file_path);
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
                if(is_null($file_path)) {
                    $this->set("export_failed", true);
                    return;
                }
                $this->set("file_path",$file_path);
                $this->redirect($file_path);
                return;
            }

            else if($export_type=="rf"){
                if(!array_key_exists("rf_type", $_POST)){
                    return;
                }
                $rf_type	= $_POST['rf_type'];
                $file_path	= null;
                if($rf_type=="transcript"){
                    $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"TRANSCRIPT_RF","transcripts_rf_exp".$exp_id.".txt");
                }
                else if($rf_type=="rf"){
                    $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"RF_TRANSCRIPT","rf_transcripts_exp".$exp_id.".txt");
                }
                if(is_null($file_path)) {
                    $this->set("export_failed", true);
                    return;
                }
                $this->set("file_path", $file_path);
                $this->redirect($file_path);
                return;
            }


            else if($export_type=="go" || $export_type=="interpro" || $export_type=="ko"){
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
                else if($functional_type=="transcript_ko"){
                    $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"TRANSCRIPT_KO","transcripts_ko_exp".$exp_id.".txt");
                }
                else if($functional_type=="meta_ko"){
                    $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"KO_TRANSCRIPT","ko_transcripts_exp".$exp_id.".txt");
                }
                if(is_null($file_path)) {
                    $this->set("export_failed", true);
                    return;
                }
                $this->set("file_path",$file_path);
                $this->redirect($file_path);
                return;
            }
            else if($export_type=="subsets"){
                if(!array_key_exists("subset_label",$_POST)){return;}
                $subset_label		= filter_var($_POST['subset_label'], FILTER_SANITIZE_STRING);
                if(!array_key_exists($subset_label,$available_subsets)){return;}
                $file_path = $this->TrapidUtils->performExport($plaza_database,$user_id,$exp_id,"TRANSCRIPT_LABEL",$subset_label."_transcripts_exp".$exp_id.".txt",$subset_label);
                //pr($file_path);
                if(is_null($file_path)) {
                    $this->set("export_failed", true);
                    return;
                }
                $this->set("file_path",$file_path);
                $this->redirect($file_path);
            }
            else{
                return;
            }
        }

    }




  function empty_experiment($exp_id=null){
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    //delete everything from all tables for this experiment
    // Only one query needed since there is now only one table for the annotations?
    $this->TranscriptsGo->query("DELETE FROM `transcripts_annotation` WHERE `experiment_id`='".$exp_id."'");
    // $this->TranscriptsGo->query("DELETE FROM `transcripts_go` WHERE `experiment_id`='".$exp_id."'");
    // $this->TranscriptsInterpro->query("DELETE FROM `transcripts_interpro` WHERE `experiment_id`='".$exp_id."'");
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
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);

    // Delete records from all tables for this experiment
    $this->TranscriptsGo->query("DELETE FROM `transcripts_annotation` WHERE `experiment_id`='".$exp_id."'");
    $this->TranscriptsTax->query("DELETE FROM `transcripts_tax` WHERE `experiment_id`='".$exp_id."'");
    $this->GeneFamilies->query("DELETE FROM `gene_families` WHERE `experiment_id`='".$exp_id."'");
    $this->RnaFamilies->query("DELETE FROM `rna_families` WHERE `experiment_id`='".$exp_id."'");
    $this->TranscriptsLabels->query("DELETE FROM `transcripts_labels` WHERE `experiment_id`='".$exp_id."'");
    $this->FunctionalEnrichments->query("DELETE FROM `functional_enrichments` WHERE `experiment_id`='".$exp_id."'");
    $this->Transcripts->query("DELETE FROM `transcripts` WHERE `experiment_id`='".$exp_id."'");
    $this->Similarities->query("DELETE FROM `similarities` WHERE `experiment_id`='".$exp_id."'");
    $this->RnaSimilarities->query("DELETE FROM `rna_similarities` WHERE `experiment_id`='".$exp_id."'");
    $this->DataUploads->query("DELETE FROM `data_uploads` WHERE `experiment_id`='".$exp_id."'");

    // Delete all associated jobs from the cluster
    // This will prevent overloading of the cluster system by malignant people constantly creating new experiments with
    // new jobs, and subsequently deleting them
    $jobs = $this->ExperimentJobs->getJobs($exp_id);
    foreach($jobs as $job){$job_id=$job['job_id'];$this->TrapidUtils->deleteClusterJob($exp_id,$job_id);}
    $this->ExperimentJobs->query("DELETE FROM `experiment_jobs` WHERE `experiment_id`='".$exp_id."'");

    $this->ExperimentLog->query("DELETE FROM `experiment_log` WHERE `experiment_id`='".$exp_id."'");
    $this->ExperimentStats->query("DELETE FROM `experiment_stats` WHERE `experiment_id`='".$exp_id."'");

    // Remove experiment
    $this->Experiments->deleteAll(array("Experiments.experiment_id"=>$exp_id));

    // Remove directory from the temp storage
    $tmp_dir	= TMP."experiment_data/".$exp_id."/";
    if(file_exists($tmp_dir) && is_dir($tmp_dir)) {
    	shell_exec("rm -rf $tmp_dir");
    }

    // Keep track of the experiment in `deleted_experiments`
    $this->DeletedExperiments->save(
        array("user_id"=>$exp_info["user_id"], "experiment_id"=>$exp_info["experiment_id"], "used_plaza_database"=>$exp_info["used_plaza_database"],
            "num_transcripts"=>$exp_info["transcript_count"], "title"=>$exp_info["title"], "creation_date"=>$exp_info["creation_date"],
            "last_edit_date"=>$exp_info["last_edit_date"], "deletion_date"=>date("Y-m-d H:i:s"))
    );
    $this->redirect(array("controller"=>"trapid","action"=>"experiments"));
  }





  /*******************************************************************************************************
   *
   *  AUTHENTICATION STUFF : COOKIES ETC.
   *
   ********************************************************************************************************/



  function change_password(){
    Configure::write("debug",2);
    $this->layout = "external";  // Layout for external pages (i.e. not in experiment)
    $this->set("active_header_item", "Account");
    $this -> set('title_for_layout', "Change password");

    $user_id		= parent::check_user();
    //retrieve information about the user
    $user_email		= $this->Authentication->find("first",array("fields"=>array("email"),"conditions"=>array("user_id"=>$user_id)));
    $user_email	= $user_email['Authentication']['email'];

    if($_POST){
      if(array_key_exists("new_password1",$_POST) && array_key_exists("new_password2",$_POST)){
	$pass1 = $_POST['new_password1'];
	$pass2 = $_POST['new_password2'];
	if($pass1!=$pass2){$this->set("error","Passwords are not the same");return;}
	if(strlen($pass1) < 8){$this->set("error","Passwords need to consist of 8 or more characters");return;}

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
     Configure::write("debug",3);
    $this->layout = "external";  // Layout for external pages (i.e. not in experiment)
    $this -> set('title_for_layout', "Authentication");

      $hashed_pass	= hash("sha256","test");
    // pr($hashed_pass);

    // Basic first check: see whether user is already logged in.
    $user_id		= $this->Cookie->read("user_id");
    $email		= $this->Cookie->read("email");
    // No need to escape SQL data when using `find` and proper array notation? + `mysql_real_escape_string` does not exist anymore in PHP 7
    // $user_id  		= mysql_real_escape_string($user_id);
    // $email		= mysql_real_escape_string($email);
    $user_data		= $this->Authentication->find("first",array("conditions"=>array("user_id"=>$user_id,"email"=>$email)));
    if($user_data){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}

    // User registration
    if($registration=="registration"){
      $this -> set('title_for_layout', "Register");
      $this->set("registration",true);
    }

    // Password recovery
    if($registration=="password_recovery"){
      $this -> set('title_for_layout', "Forgotten password");
      $this->set("pass_recovery",true);
    }

    // If the form was submitted, three possibilities: registration, password recovery, or login.
    if($_POST){

      // Registration of a new user
      if(array_key_exists("registration",$_POST)){
	    // Standard validation of input parameters
        if(!(array_key_exists("login",$_POST)&&array_key_exists("organization",$_POST)&&array_key_exists("country",$_POST))){
          $this->set("error","Invalid form parameters");return;
	    }
	    // $email		= mysql_real_escape_string($_POST["login"]);
	    // $organization	= mysql_real_escape_string($_POST["organization"]);
	    // $country	= mysql_real_escape_string($_POST["country"]);
        // No need to escape?
	    $email = $_POST["login"];
	    $organization = $_POST["organization"];
	    $country = $_POST["country"];
	    if(!($email!="" && $organization!="" && $country!="")){
	      $this->set("error","Not all fields are filled in");return;
	    }
	    // Check whether valid email address, using the models validation
        $this->Authentication->set(array("email"=>$email));
          if(!$this->Authentication->validates()){
            $this->set("error","Invalid email address");return;
	    }
        // Check whether email address is already present in `authentication` table of database
        $user_data	= $this->Authentication->find("first",array("conditions"=>array("email"=>$email)));
        if($user_data){
          $this->set("error","Email-address already in use");return;
	    }
        // Now, we can actually create a password and add the user to the database
        $password	= $this->TrapidUtils->create_password();
        $hashed_pass	= hash("sha256",$password);
        // `user_id` value set to NULL (setting it to an empty string, like before, would require to turn SQL strict mode off)
        // Reason: sql_mode is not the same on psbsql01 and psbsql03
        $this->Authentication->save(array("user_id"=>NULL,"email"=>$email,"password"=>$hashed_pass,"group"=>"academic",
					                "organization"=>$organization,"country"=>$country));
	    // Send email to user with login information
        $this->TrapidUtils->send_registration_email($email,$password);
        $this->set("message", "Please use the authentication information sent to you by email to login");
        $this->set("registration", false);
          return;
      }

      // Password recovery
      if(array_key_exists("pass_recovery", $_POST)) {
          $this->set("registration", false);
          $this->set("pass_recovery", true);
          // Check if email is correct and if it exists in TRAPID's DB.
          // $email = mysql_real_escape_string($_POST["login"]);
          $email = $_POST["login"];  // No need to escape? (using Cake's find+arrays)
          if(!(array_key_exists("login",$_POST))){
              $this->set("error","Invalid form parameters");return;
          }
          $this->Authentication->set(array("email"=>$email));
          if(!$this->Authentication->validates()){
              $this->set("error","Invalid email address");return;
          }
          $user_data = $this->Authentication->find("first",array("conditions"=>array("email"=>$email)));
          $user_id = $user_data['Authentication']['user_id'];
          // Note: It may be better to not display anything if the email is not found? Otherwise it would potentially
          // be a way to retrieve what email addresses are in TRAPID...
          if(!$user_data){
              $this->set("error","Email-address does not correspond to a TRAPID login. ");return;
          }
          // Generate new password
          $password	= $this->TrapidUtils->create_password();
          $hashed_pass	= hash("sha256", $password);
          // Store updated information in the DB
          $this->Authentication->updateAll(array("password"=>"'".$hashed_pass."'"),array("user_id"=>$user_id));
          // Send 'password reset' email
          $this->TrapidUtils->send_registration_email($email, $password, true);
          // If everything is OK, reload the page with success message
          $this->set("sent_reset_email", true);
          $this->set("email", $email);
      }

      // Logging in
      else{
    	if(array_key_exists("login",$_POST) && array_key_exists("password",$_POST)){
	      // $email	= mysql_real_escape_string($_POST["login"]);
	      // $password	= mysql_real_escape_string(hash("sha256",$_POST["password"]));
          // No need to escape?
	      $email	= $_POST["login"];
	      $password	= hash("sha256",$_POST["password"]);
    	  $user_data 	= $this->Authentication->find("first",array("conditions"=>array("email"=>$email,"password"=>$password)));
	      if(!$user_data){$this->set("error","Wrong email/password");return;}
	      $this->Cookie->write("user_id", $user_data['Authentication']['user_id']);
	      $this->Cookie->write("email", $user_data['Authentication']['email']);
//          $this->cleanup_experiments();
          $this->redirect(array("controller"=>"trapid","action"=>"experiments"));
        }
        else{
    	  $this->redirect(array("controller"=>"trapid","action"=>"authentication"));
    	}
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
//        exec($command,$output);
	//pr($output);
      }
     }
  }


  // TRAPID maintenance page
  function maintenance(){
      $this->layout = "external";  // Layout for external pages (i.e. not in experiment)
      $this -> set('title_for_layout', "Maintenance");
  }

  /*
   * Cookie setup:
   * The entire TRAPID website is based on user-defined data sets, and as such a method for
   * account handling and user identification is required.
   *
   * The 'beforeFilter' method is executed BEFORE each method, and as such ensures that the necessary
   * identification through cookies is done.
   *
   * Cookie settings can be adjusted in `app/scripts/ini_files/webapp_settings.ini`.
   *
   */
  function beforeFilter(){
    parent::beforeFilter();
  }




   /*
    * Function which deletes the stored cookies for authentication, then redirects to home-page
    */
   function log_off(){
     $this->Cookie->destroy();
     $this->redirect("/");
   }


  /* Simple function that returns True if a user is logged in (so the value for
  `user_id` in the Cookie isn't null), False otherwise.
  Used in the navbar to display different links if a user is logged in. */
  function is_logged_in(){
    $user_id	= $this->Cookie->read("user_id");
    if($user_id!=null){
      return True;
    }
    else {
      return False;
  }
}

/* Trying to use jQuery dataTables for the transcripts table (with data retrieved on the fly via ajax).
   WIP */

//function ajaxData() {
//    $this->modelClass = "TranscriptsPagination";
//    $this->autoRender = false;
//    $output = $this->TranscriptsPagination->GetData();
//    echo json_encode($output);
//    // echo $output;
//}

}



?>
