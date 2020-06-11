<?php
App::uses('Sanitize', 'Utility');
/*
 * General controller class for the trapid functionality
 */
class RnaFamilyController extends AppController{
    var $name		= "RnaFamily";
    var $helpers		= array("Html"); // ,"Javascript","Ajax");
    var $uses		= array("Authentication","Experiments","Configuration","Transcripts","RnaFamilies",
                            "ExtendedGo", "TranscriptsGo", "TranscriptsInterpro", "ProteinMotifs", "TranscriptsLabels",
                            "HelpTooltips",
        // To remove 'overloaded property' warning messages ...
        "GfData", "ProteinMotifs", "GoParents", "ExtendedGo", "Annotation", "AnnotSources", "KoTerms", "TranscriptsKo"
    );

    var $components	= array("Cookie","TrapidUtils", "DataTable");

    var $paginate		= array(
        "RnaFamilies"=>
            array(
                "maxLimit"=>20,
                "order"=>array(
                    "RnaFamilies.experiment_id"=>"ASC", // Needed to force use of `experiment_id` index
                    "RnaFamilies.rf_id"=>"ASC"
                )
            )
    );


    // Paginated table with RFAM families, with cake sorting allowed
    function index($exp_id=null) {
        if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
        // $exp_id	= mysql_real_escape_string($exp_id);
        parent::check_user_exp($exp_id);
        $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
        $this->set("exp_info", $exp_info);
        $this->set("exp_id", $exp_id);
        $rna_families_p = $this->paginate("RnaFamilies",array("RnaFamilies.experiment_id = '" . $exp_id . "'"));
        $this->set("rna_families", $rna_families_p);
        // Get RFAM name and description from values stored in `configuration`
        // Are names only enough to display on this page?
        $rna_families_ids_original	= $this->TrapidUtils->reduceArray($rna_families_p, 'RnaFamilies','rfam_rf_id');
        $rna_clans_ids_original	= $this->TrapidUtils->reduceArray($rna_families_p, 'RnaFamilies','rfam_clan_id');
        // Here not using `IN` resulted in a SQL error when there is only one RNA family to display. The problem:
        // backticks around 'key' would be missing (reserved word). Adding `IN` seems to solve the issue...
        $rf_names = $this->Configuration->find("all", array("conditions"=>array("method"=>"rfam_families", "key IN"=>$rna_families_ids_original, "attr"=>"rfam_id"), "fields"=>array("key","value")));
        $clan_names = $this->Configuration->find("all", array("conditions"=>array("method"=>"rfam_clans", "key IN"=>$rna_clans_ids_original, "attr"=>"clan_id"), "fields"=>array("key", "value")));
        $rf_names = $this->TrapidUtils->indexArraySimple($rf_names, "Configuration", "key", "value");
        $clan_names = $this->TrapidUtils->indexArraySimple($clan_names, "Configuration", "key", "value");
        $rfam_linkouts = $this->Configuration->find("all", array('conditions'=>array('method'=>'linkout', 'key'=>'rfam')));
        $rfam_linkouts = $this->TrapidUtils->indexArraySimple($rfam_linkouts, "Configuration", "attr", "value");
        $user_group=$this->Authentication->find("first",array("fields"=>array("group"),"conditions"=>array("user_id"=>parent::check_user())));
        if($user_group['Authentication']['group'] == "admin"){$this->set("admin", 1);}

        $this->set("rf_names", $rf_names);
        $this->set("clan_names", $clan_names);
        $this->set("rfam_linkouts", $rfam_linkouts);
        $this->set("active_sidebar_item", "Browse RNA families");
        $this -> set('title_for_layout', 'RNA families');
    }


    // RNA family page, similar to Gene Family page.
    // For now simply provide a list of transcripts in RF (need to discuss what's next depending on pipeline changes)
    function rna_family($exp_id=null, $rf_id=null) {
        if(!$exp_id || !$rf_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
        // $exp_id	= mysql_real_escape_string($exp_id);
        parent::check_user_exp($exp_id);
        // Get experiment information
        $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
        // Get RNA family / RFAM data
        // $rf_id	= $this->RnaFamilies->getDataSource()->value($rf_id, 'string'); //Useless/unclean?
        $rf_id	= filter_var($rf_id, FILTER_SANITIZE_STRING); //Useless/unclean?
        $rf_data = $this->RnaFamilies->find("first", array("conditions"=>array("experiment_id"=>$exp_id, "rf_id"=>$rf_id)));
        // Get RFAM name and description from values stored in `configuration`
        $rf_name = $this->Configuration->find("first", array("conditions"=>array("method"=>"rfam_families", "key"=>$rf_data['RnaFamilies']['rfam_rf_id'], "attr"=>"rfam_id"), "fields"=>array("value")));
        $rf_data['RnaFamilies']['name'] = $rf_name['Configuration']['value'];
        $rf_desc = $this->Configuration->find("first", array("conditions"=>array("method"=>"rfam_families", "key"=>$rf_data['RnaFamilies']['rfam_rf_id'], "attr"=>"description"), "fields"=>array("value")));
        $rf_data['RnaFamilies']['description'] = $rf_desc['Configuration']['value'];
        // Get clan name and description too!
        if($rf_data['RnaFamilies']['rfam_clan_id']) {
            $clan_name = $this->Configuration->find("first", array("conditions"=>array("method"=>"rfam_clans", "key"=>$rf_data['RnaFamilies']['rfam_clan_id'], "attr"=>"clan_id"), "fields"=>array("value")));
            $rf_data['RnaFamilies']['clan_name'] = $clan_name['Configuration']['value'];
            $clan_desc = $this->Configuration->find("first", array("conditions"=>array("method"=>"rfam_clans", "key"=>$rf_data['RnaFamilies']['rfam_clan_id'], "attr"=>"description"), "fields"=>array("value")));
            $rf_data['RnaFamilies']['clan_description'] = $clan_desc['Configuration']['value'];
        }
        $rfam_linkouts = $this->Configuration->find("all", array('conditions'=>array('method'=>'linkout', 'key'=>'rfam')));
        $rfam_linkouts = $this->TrapidUtils->indexArraySimple($rfam_linkouts, "Configuration", "attr", "value");
        // Get transcript data
        // NOTE: there will be a problem with this implementation for transcripts that were assigned to multipel RNA families
        $transcripts_p = $this->paginate("Transcripts", array("experiment_id"=>$exp_id, "is_rna_gene"=>1, "rf_ids"=>$rf_id));
        $transcript_ids = $this->TrapidUtils->reduceArray($transcripts_p, "Transcripts", "transcript_id");
        $transcripts = $this->Transcripts->find("all", array("conditions"=>array("experiment_id"=>$exp_id, "transcript_id"=>$transcript_ids)));
        // Get functional annotation for transcripts table
        $transcripts_go	= $this->TrapidUtils->indexArray($this->TranscriptsGo->find("all", array("conditions"=>array("experiment_id"=>$exp_id, "transcript_id"=>$transcript_ids, "is_hidden"=>"0", "type"=>"go"))), "TranscriptsGo", "transcript_id", "name");
        $go_info	= array();
        if(count($transcripts_go)!=0){
            $go_ids		=  array_unique(call_user_func_array("array_merge",array_values($transcripts_go)));
            $go_info        = $this->ExtendedGo->retrieveGoInformation($go_ids);
        }
        $transcripts_ipr = $this->TrapidUtils->indexArray($this->TranscriptsInterpro->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids, "type"=>"ipr"))),"TranscriptsInterpro","transcript_id","name");
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
        // Get subset/label information
        $transcripts_labels	= $this->TrapidUtils->indexArray($this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids))),"TranscriptsLabels","transcript_id","label");

        // Subsets - # transcripts information and tooltip (for subset creation form)
        $all_subsets = $this->TranscriptsLabels->getLabels($exp_id);
        $tooltip_text_subset_creation = $this->HelpTooltips->getTooltipText("transcript_table_subset_creation");
        $this->set("all_subsets", $all_subsets);
        $this->set("tooltip_text_subset_creation", $tooltip_text_subset_creation);


        $this->set("exp_info",$exp_info);
        $this->set("exp_id",$exp_id);
        $this->set("rf_data", $rf_data['RnaFamilies']);
        $this->set("rfam_linkouts", $rfam_linkouts);
        // Data for transcript table
        $this->set("transcript_data",$transcripts);
        $this->set("transcripts_go",$transcripts_go);
        $this->set("transcripts_ipr",$transcripts_ipr);
        $this->set("transcripts_ko",$transcripts_ko);
        $this->set("transcripts_labels",$transcripts_labels);
        $this->set("go_info_transcripts",$go_info);
        $this->set("ipr_info_transcripts",$ipr_info);
        $this->set("ko_info_transcripts",$ko_info);
        $this->set("active_sidebar_item", "Browse RNA families");
        $this -> set('title_for_layout', $rf_id.' &middot; RNA family');

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