<?php
App::uses('Sanitize', 'Utility');
/*
 * General controller class for the trapid functionality
 */
class LabelsController extends AppController{
  var $name		= "Labels";
  var $helpers		= array("Html", "Form");  // ,"Javascript","Ajax");
  var $uses		= array("Authentication","Experiments","Configuration","Transcripts","AnnotSources","Annotation",
				"PlazaConfiguration","GeneFamilies","ExtendedGo","ProteinMotifs", "GoParents", "GfData",
				"TranscriptsGo","TranscriptsInterpro","TranscriptsLabels");

  var $components	= array("Cookie","TrapidUtils","Statistics");



  function view($exp_id=null,$label=null){
    if(!$exp_id || !$label){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    $label	= filter_var($label, FILTER_SANITIZE_STRING);
    //check whether there is at least one transcript with this label associated.
    $num_transcripts	= $this->TranscriptsLabels->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"label"=>$label)));
    //got here by illegal means
    if($num_transcripts==0){$this->set("error","No transcripts are associated with label '".$label."' ");return;}
    $this->set("num_transcripts",$num_transcripts);
    $this->set("label",$label);


    $transcripts_p	= $this->paginate("TranscriptsLabels",array("experiment_id"=>$exp_id,"label"=>$label));
    $transcript_ids	= $this->TrapidUtils->reduceArray($transcripts_p,"TranscriptsLabels","transcript_id");
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

    $this -> set('title_for_layout', $label.' &middot; Subset');

  }


  function subset_overview($exp_id=null){
    //Configure::write("debug",2);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    //ok, get an overview of the counts present in the database.
    $data_raw 			= $this->TranscriptsLabels->getDataTranscript2Labels($exp_id);
    $data_venn			= $this->Statistics->makeVennOverview($data_raw);
    $this->set("data_venn",$data_venn);

    $this->set("active_sidebar_item", "Explore subsets");
    $this -> set('title_for_layout', 'Subsets overview');
  }


    // Delete label `$label_id` and associated data from experiment `$exp_id`.
    function delete_label($exp_id=null, $label_id=null){
        $label_id = $this->TranscriptsLabels->getDataSource()->value($label_id, 'string');
        parent::check_user_exp($exp_id);
        // TODO: check if nothing is running before deleting labels?
        $this->TranscriptsLabels->query("DELETE FROM `transcripts_labels` WHERE `experiment_id`='".$exp_id."' AND `label` = ".$label_id.";");
        // Also delete all extra data using these labels...
        // Core GF completeness results
        $this->TranscriptsLabels->query("DELETE FROM `completeness_results` WHERE `experiment_id`='".$exp_id."' AND `label` = ".$label_id.";");
        // Functional enrichment results
        $this->TranscriptsLabels->query("DELETE FROM `functional_enrichments` WHERE `experiment_id`='".$exp_id."' AND `label` = ".$label_id.";");
        $this->redirect(array("controller"=>"labels","action"=>"subset_overview", $exp_id));
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
