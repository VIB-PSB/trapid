<?php
App::uses('Sanitize', 'Utility');
/*
 * General controller class for the trapid functionality
 */
class RnaFamilyController extends AppController{
    var $name		= "RnaFamily";
    var $helpers		= array("Html"); // ,"Javascript","Ajax");
    var $uses		= array("Authentication","Experiments","Configuration","Transcripts","RnaFamilies",
        // To remove 'overloaded property' warning messages ...
        "GfData", "ProteinMotifs", "GoParents", "ExtendedGo", "Annotation", "AnnotSources"
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
        $exp_id	= mysql_real_escape_string($exp_id);
        parent::check_user_exp($exp_id);
        $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
        $this->set("exp_info", $exp_info);
        $this->set("exp_id", $exp_id);
        $rna_families_p = $this->paginate("RnaFamilies",array("RnaFamilies.experiment_id = '" . $exp_id . "'"));
        $this->set("rna_families", $rna_families_p);
        $rna_families_ids_original	= $this->TrapidUtils->reduceArray($rna_families_p, 'RnaFamilies','rfam_rf_id');

        $user_group=$this->Authentication->find("first",array("fields"=>array("group"),"conditions"=>array("user_id"=>parent::check_user())));
        if($user_group['Authentication']['group'] == "admin"){$this->set("admin", 1);}

        $this->set("active_sidebar_item", "Browse RNA families");
        $this -> set('title_for_layout', 'RNA families');
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