<?php
App::uses('Sanitize', 'Utility');
/*
 * General controller class for the trapid functionality
 */
class FunctionalAnnotationController extends AppController{
  var $name		= "FunctionalAnnotation";
  var $helpers		= array("Html", "Form");  // ,"Javascript","Ajax");
  var $uses		= array("Authentication","Experiments","Configuration","Transcripts","GeneFamilies","GfData",
				"TranscriptsGo","TranscriptsKo","TranscriptsInterpro","TranscriptsLabels",

				"AnnotSources","Annotation","ExtendedGo","GoParents","KoTerms","ProteinMotifs");


  var $components	= array("Cookie","TrapidUtils");
  // Note: pagination should be handled by jQuery DataTables
  var $paginate		= array(
				"TranscriptsGo"=>
				array(
					"limit"=>10,
			       	"order"=>array("TranscriptsGo.transcript_id"=>"ASC")
				),
				"TranscriptsInterpro"=>
				array(
				    "limit"=>10,
					"order"=>array("TranscriptsInterpro.transcript_id"=>"ASC")
				),
      			"TranscriptsKo"=>
				array(
				    "limit"=>10,
					"order"=>array("TranscriptsKo.transcript_id"=>"ASC")
				)
			  );




  //intermediary page displaying an overview of the child GO terms (when less than 100), and their
  function child_go($exp_id=null,$go_web=null){

    $max_child_gos	= 200;
    //check experiment.
    if(!$exp_id || !$go_web){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    // $go			= mysql_real_escape_string(str_replace("-",":",$go_web));
    $go			= str_replace("-",":",$go_web); // No need to escape/check?
    // $go_information	= $this->ExtendedGo->find("first",array("conditions"=>array("go"=>$go)));
    $go_information	= $this->ExtendedGo->find("first",array("conditions"=>array("type"=>"go", "name"=>$go)));
    $num_transcripts	= $this->TranscriptsGo->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"type"=>"go", "name"=>$go)));
    if(!$go_information || $num_transcripts==0){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("go",$go);
    $this->set("go_web",$go_web);
    $this->set("go_info",$go_information["ExtendedGo"]);
    $this->set("num_transcripts",$num_transcripts);

    //get the child go terms.
    $child_gos		= $this->GoParents->find("all",array("conditions"=>array("parent"=>$go, "type"=>"go"),"fields"=>array("child")));
    $child_gos		= $this->TrapidUtils->reduceArray($child_gos,"GoParents","child");
    $this->set("num_child_gos",count($child_gos));
    if(count($child_gos)==0){return;}
    //prevent data overload on page and too many queries
    /* if(count($child_gos) > $max_child_gos){
      $this->set("max_child_gos_reached",$max_child_gos);
      $child_gos	= array_slice($child_gos,0,$max_child_gos);
      }*/
    //get descriptions for child GO terms
    // $go_descriptions	= $this->ExtendedGo->find("all",array("conditions"=>array("go"=>$child_gos)));
    // $go_descriptions	= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","go","desc");
    $go_descriptions	= $this->ExtendedGo->find("all",array("conditions"=>array("type"=>"go", "name"=>$child_gos)));
    $go_descriptions	= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo", "name", "desc");

    //get transcript counts for child GO terms
    $go_counts		= $this->TranscriptsGo->findTranscriptsFromGo($exp_id,$go_descriptions);
    $this->set("num_child_gos",count($go_counts));
    $this->set("child_go_counts",$go_counts);
    $this -> set('title_for_layout', $go.' &middot; Child GO terms');
  }



  function parent_go($exp_id=null,$go_web=null){
    $max_parental_gos	= 200;
    //check experiment.
    if(!$exp_id || !$go_web){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    // $go			= mysql_real_escape_string(str_replace("-",":",$go_web));
    $go			= str_replace("-",":",$go_web); // No need to escape/check?
    // $go_information	= $this->ExtendedGo->find("first",array("conditions"=>array("go"=>$go)));
    $go_information	= $this->ExtendedGo->find("first",array("conditions"=>array("name"=>$go, "type"=>"go")));
    $num_transcripts	= $this->TranscriptsGo->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"type"=>"go", "name"=>$go)));
    if(!$go_information || $num_transcripts==0){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("go",$go);
    $this->set("go_web",$go_web);
    $this->set("go_info",$go_information["ExtendedGo"]);
    $this->set("num_transcripts",$num_transcripts);


    // Get the parent go terms.
    // $parental_gos		= $this->GoParents->find("all",array("conditions"=>array("child_go"=>$go),"fields"=>array("parent_go")));
    // $parental_gos		= $this->TrapidUtils->reduceArray($parental_gos,"GoParents","parent_go");
    $parental_gos		= $this->GoParents->find("all",array("conditions"=>array("child"=>$go, "type"=>"go"),"fields"=>array("parent")));
    $parental_gos		= $this->TrapidUtils->reduceArray($parental_gos,"GoParents","parent");
    $this->set("num_parent_gos",count($parental_gos));
    if(count($parental_gos)==0){return;}
    //prevent data overload on page and too many queries
    /* if(count($child_gos) > $max_child_gos){
      $this->set("max_child_gos_reached",$max_child_gos);
      $child_gos	= array_slice($child_gos,0,$max_child_gos);
      }*/
    //get descriptions for child GO terms
    $go_descriptions	= $this->ExtendedGo->find("all",array("conditions"=>array("name"=>$parental_gos, "type"=>"go")));
    $go_descriptions	= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","name","desc");

    //get transcript counts for child GO terms
    $go_counts		= $this->TranscriptsGo->findTranscriptsFromGo($exp_id,$go_descriptions);
    $this->set("num_parent_gos",count($go_counts));
    $this->set("parent_go_counts",$go_counts);

    $this -> set('title_for_layout', $go.' &middot; Parental GO terms');
  }



  function go($exp_id=null,$go_web=null){
    //check experiment.
    if(!$exp_id || !$go_web){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    // Disable linkout if we use eggnog (they do not have dedicated pages to functional annotations).
    // Not clean but will do for the workshop
    if(strpos($exp_info['used_plaza_database'], "eggnog") !== false) {
        $exp_info['allow_linkout'] = 0;
    }
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);

    // Check GO validity
    // $go			= mysql_real_escape_string(str_replace("-",":",$go_web));
    $go			= str_replace("-",":",$go_web); // No need to check (find)?
    // $go_information	= $this->ExtendedGo->find("first",array("conditions"=>array("go"=>$go)));
    $go_information	= $this->ExtendedGo->find("first",array("conditions"=>array("name"=>$go)));
    $num_transcripts	= $this->TranscriptsGo->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"type"=>"go", "name"=>$go)));
    if(!$go_information || $num_transcripts==0){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("go_info",$go_information["ExtendedGo"]);
    $this->set("num_transcripts",$num_transcripts);

    // Pagination should be handled by jQuery DataTables
    // TODO: reread how the information is formatted/displayed and try to optimize (same for IPR)
     $transcripts_p		= $this->paginate("TranscriptsGo",array("experiment_id"=>$exp_id,"name"=>$go, "type"=>"go"));
    $transcripts_p		= $this->TranscriptsGo->find("all", array("fields"=>array("transcript_id"), "conditions"=>array("experiment_id"=>$exp_id, "type"=>"go", "name"=>$go)));
    $transcript_ids	= $this->TrapidUtils->reduceArray($transcripts_p,"TranscriptsGo","transcript_id");
    $transcripts	= $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids)));

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

    //retrieve subset/label information
    $transcripts_labels	= $this->TrapidUtils->indexArray($this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids))),"TranscriptsLabels","transcript_id","label");


    $this->set("transcript_data",$transcripts);
    $this->set("transcripts_go",$transcripts_go);
    $this->set("transcripts_ipr",$transcripts_ipr);
    $this->set("transcripts_labels",$transcripts_labels);
    $this->set("go_info_transcripts",$go_info);
    $this->set("ipr_info_transcripts",$ipr_info);

    $this -> set('title_for_layout', $go.' &middot; GO term ');

  }



  function interpro($exp_id=null,$interpro=null){
    //check experiment
    if(!$exp_id || !$interpro){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);

    // Check InterPro validity
    // $interpro		= mysql_real_escape_string($interpro);
    // No need to check (find)?
    // $interpro_info	= $this->ProteinMotifs->find("first",array("conditions"=>array("motif_id"=>$interpro)));
    $interpro_info	= $this->ProteinMotifs->find("first",array("conditions"=>array("name"=>$interpro, "type"=>"interpro")));
    $num_transcripts =$this->TranscriptsInterpro->find("count",array("conditions"=>array("experiment_id"=>$exp_id, "type"=>"ipr", "name"=>$interpro)));
    if(!$interpro_info || $num_transcripts==0){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("interpro_info",$interpro_info['ProteinMotifs']);
    $this->set("num_transcripts",$num_transcripts);

    // Pagination should be handled by jQuery DataTables
     $transcripts_p	= $this->paginate("TranscriptsInterpro",array("experiment_id"=>$exp_id, "type"=>"ipr", "name"=>$interpro));
    $transcripts_p	= $this->TranscriptsInterpro->find("all",array("fields"=>array("transcript_id"), "conditions"=>array("experiment_id"=>$exp_id, "type"=>"ipr", "name"=>$interpro)));
    $transcript_ids	= $this->TrapidUtils->reduceArray($transcripts_p,"TranscriptsInterpro","transcript_id");
    $transcripts	= $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids)));

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

    //retrieve subset/label information
    $transcripts_labels	= $this->TrapidUtils->indexArray($this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids))),"TranscriptsLabels","transcript_id","label");



    $this->set("transcript_data",$transcripts);
    $this->set("transcripts_go",$transcripts_go);
    $this->set("transcripts_ipr",$transcripts_ipr);
    $this->set("transcripts_labels",$transcripts_labels);
    $this->set("go_info_transcripts",$go_info);
    $this->set("ipr_info_transcripts",$ipr_info);

    $this -> set('title_for_layout', $interpro.' &middot; Protein domain ');
  }




  function assoc_gf($exp_id=null,$type=null,$identifier=null){
    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);

      // Disable linkout if we use eggnog (they do not have dedicated pages to functional annotations).
      // Not clean but will do for the workshop
      if(strpos($exp_info['used_plaza_database'], "eggnog") !== false) {
          $exp_info['allow_linkout'] = 0;
      }

      $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    if(!$type||!$identifier||!($type=="go"||$type=="interpro")){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $gene_families	= array();


      if($type=="go"){
      // $go	= str_replace("-",":",mysql_real_escape_string($identifier));
      $go	= str_replace("-",":",$identifier);
      //find whether any genes are associated (this also validates the go itself).
      $num_transcripts= $this->TranscriptsGo->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"type"=>"go", "name"=>$go)));
      if($num_transcripts==0){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
      $go_information	= $this->ExtendedGo->find("first",array("conditions"=>array("name"=>$go, "type"=>"go")));
      $this->set("description",$go_information["ExtendedGo"]["desc"]);
      $this->set("go_category",$go_information["ExtendedGo"]["info"]);
      $this->set("type", "go");
      $this->set("go",$go);
      $this->set("num_transcripts",$num_transcripts);
      if($num_transcripts>5000){$this->set("error","Unable to find associated gene families for GO terms with more than 5000 associated transcripts");return;}
      $transcripts_p = $this->TranscriptsGo->find("all",array("fields"=>array("transcript_id"),
							      "conditions"=>array("experiment_id"=>$exp_id, "type"=>"go", "name"=>$go)));
      $transcript_ids	= $this->TrapidUtils->reduceArray($transcripts_p,"TranscriptsGo","transcript_id");
      $gene_families	= $this->Transcripts->findAssociatedGf($exp_id,$transcript_ids);
      $this->set("gene_families",$gene_families);
    }
    else if($type=="interpro"){
      // $interpro		= mysql_real_escape_string($identifier);
      $interpro		= $identifier;
      //find whether any genes are associated (this also validates the interpro itself)
      $num_transcripts= $this->TranscriptsInterpro->find("count",array("conditions"=>array("experiment_id"=>$exp_id, "type"=>"ipr", "name"=>$interpro)));
      if($num_transcripts==0){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
      // TRAPID db structure changed
      // $interpro_information	= $this->ProteinMotifs->find("first",array("conditions"=>array("motif_id"=>$interpro)));
      //  $this->set("description",$interpro_information["ProteinMotifs"]["desc"]);
      // Note: `ipr`/`interpro` = not consistent???
      $interpro_information	= $this->ProteinMotifs->find("first",array("conditions"=>array("name"=>$interpro, "type"=>"interpro")));
      $this->set("description",$interpro_information["ProteinMotifs"]["desc"]);
      $this->set("type","interpro");
      $this->set("interpro",$interpro);
      $this->set("num_transcripts",$num_transcripts);
      if($num_transcripts>5000){$this->set("error","Unable to find associated gene families for InterPro domains with more than 5000 associated transcripts");return;}
      $transcripts_p = $this->TranscriptsInterpro->find("all",array("fields"=>array("transcript_id"),
							      "conditions"=>array("experiment_id"=>$exp_id, "type"=>"ipr", "name"=>$interpro)));
      $transcript_ids	= $this->TrapidUtils->reduceArray($transcripts_p,"TranscriptsInterpro","transcript_id");
      $gene_families	= $this->Transcripts->findAssociatedGf($exp_id,$transcript_ids);
      $this->set("gene_families",$gene_families);
    }
    //more func annot per gene family
    $extra_annot_go	= array();
    $extra_annot_ipr	= array();
    $first_transcripts	= array();
    $go_descriptions	= array();
    $ipr_descriptions	= array();
    $assoc_transcripts	= $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>array_keys($gene_families)),"fields"=>array("transcript_id","gf_id")));
    foreach($assoc_transcripts as $t){
      $trid	= $t['Transcripts']['transcript_id'];
      $gf	= $t['Transcripts']['gf_id'];
      if(!array_key_exists($gf,$first_transcripts)){
	$first_transcripts[$gf] = $trid;
	$trid_go	= $this->TranscriptsGo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$trid, "type"=>"go")));
        $trid_ipr	= $this->TranscriptsInterpro->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$trid, "type"=>"ipr")));
        $extra_annot_go[$gf]	= array();
	$extra_annot_ipr[$gf]	= array();
	foreach($trid_go as $tg){$extra_annot_go[$gf][]=$tg['TranscriptsGo']['name'];$go_descriptions[]=$tg['TranscriptsGo']['name'];}
	foreach($trid_ipr as $ti){$extra_annot_ipr[$gf][]=$ti['TranscriptsInterpro']['name'];$ipr_descriptions[]=$ti['TranscriptsInterpro']['name'];}
      }
    }
//    $go_descriptions	= $this->ExtendedGo->find("all",array("conditions"=>array("go"=>array_unique($go_descriptions))));
//    $ipr_descriptions	= $this->ProteinMotifs->find("all",array("conditions"=>array("motif_id"=>array_unique($ipr_descriptions))));
    $go_descriptions	= $this->ExtendedGo->find("all",array("conditions"=>array("name"=>array_unique($go_descriptions), "type"=>"go")));
    $ipr_descriptions	= $this->ProteinMotifs->find("all",array("conditions"=>array("name"=>array_unique($ipr_descriptions), "type"=>"interpro")));
    //pr($ipr_descriptions);

    //    pr($extra_annot_go);
    //	pr($go_descriptions);
    //    pr($extra_annot_ipr);
    //    pr($ipr_descriptions);
//    $go_descriptions	= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","go","desc");
//    $ipr_descriptions	= $this->TrapidUtils->indexArraySimple($ipr_descriptions,"ProteinMotifs","motif_id","desc");
    $go_categories  = $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","name","info");
    $go_descriptions = $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","name","desc");
      $ipr_descriptions	= $this->TrapidUtils->indexArraySimple($ipr_descriptions,"ProteinMotifs","name","desc");
    //$this->set("extra_annotation",$extra_annotation);

    $this->set("extra_annot_go",$extra_annot_go);
    $this->set("extra_annot_ipr",$extra_annot_ipr);
    $this->set("go_descriptions",$go_descriptions);
    $this->set("go_categories", $go_categories);
    $this->set("ipr_descriptions",$ipr_descriptions);

    $this -> set('title_for_layout', str_replace("-",":", $identifier) .' &middot; Associated gene families');
  }


  function ko($exp_id=null, $ko=null){
      // Check experiment access
      if(!$exp_id || !$ko) {
        $this->redirect(array("controller"=>"trapid", "action"=>"experiments"));
      }
      parent::check_user_exp($exp_id);
      // Get experiment information
      $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
      $this->set("exp_info", $exp_info);
      $this->set("exp_id", $exp_id);
      $this->TrapidUtils->checkPageAccess($exp_info['title'], $exp_info["process_state"], $this->process_states["default"]);

      // Check validity of supplied KO term (i.e. does it exist, is there any transcript associated to it)
      $ko_info	= $this->KoTerms->find("first",array("conditions"=>array("name"=>$ko, "type"=>"ko")));
      $num_transcripts =$this->TranscriptsKo->find("count",array("conditions"=>array("experiment_id"=>$exp_id, "type"=>"ko", "name"=>$ko)));
      if(!$ko_info || $num_transcripts == 0) {
        $this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
      }
      $this->set("ko_info",$ko_info['KoTerms']);
      $this->set("num_transcripts", $num_transcripts);

      // Get (paginated) transcripts annotated with the same KO term
      $transcripts_p = $this->paginate("TranscriptsKo", array("experiment_id"=>$exp_id, "type"=>"ko", "name"=>$ko));
      $transcripts_p = $this->TranscriptsKo->find("all",array("fields"=>array("transcript_id"), "conditions"=>array("experiment_id"=>$exp_id, "type"=>"ko", "name"=>$ko)));
      $transcript_ids = $this->TrapidUtils->reduceArray($transcripts_p,"TranscriptsKo","transcript_id");
      $transcripts = $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids)));

      // Fetch associated data (functional annotation, subsets) to populate the table
      // GO
      $transcripts_go = $this->TrapidUtils->indexArray($this->TranscriptsGo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids,"is_hidden"=>"0", "type"=>"go"))),"TranscriptsGo","transcript_id","name");
      $go_info	= array();
      if(count($transcripts_go)!=0){
          $go_ids = array_unique(call_user_func_array("array_merge",array_values($transcripts_go)));
          $go_info = $this->ExtendedGo->retrieveGoInformation($go_ids);
      }
      // IPR
      $transcripts_ipr = $this->TrapidUtils->indexArray($this->TranscriptsInterpro->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids, "type"=>"ipr"))),"TranscriptsInterpro","transcript_id","name");
      $ipr_info	= array();
      if(count($transcripts_ipr)!=0){
          $ipr_ids = array_unique(call_user_func_array("array_merge",array_values($transcripts_ipr)));
          $ipr_info = $this->ProteinMotifs->retrieveInterproInformation($ipr_ids);
      }
      // KO
      $transcripts_ko = $this->TrapidUtils->indexArray($this->TranscriptsKo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids, "type"=>"ko"))),"TranscriptsKo","transcript_id","name");
      // Subset/label information
      $transcripts_labels = $this->TrapidUtils->indexArray($this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids))),"TranscriptsLabels","transcript_id","label");

      $this->set("transcript_data", $transcripts);
      $this->set("transcripts_go", $transcripts_go);
      $this->set("transcripts_ipr", $transcripts_ipr);
      $this->set("transcripts_ko", $transcripts_ko);
      $this->set("transcripts_labels", $transcripts_labels);
      $this->set("go_info_transcripts", $go_info);
      $this->set("ipr_info_transcripts", $ipr_info);

      $this -> set('title_for_layout', $ko . ' &middot; KO term ');
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
