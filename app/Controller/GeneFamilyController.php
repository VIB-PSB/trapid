<?php
/*
 * General controller class for the trapid functionality
 */
class GeneFamilyController extends AppController{
  var $name		= "GeneFamily";
  var $helpers		= array("Html", "Form"); // ,"Javascript","Ajax");
  var $uses		= array("Authentication","Experiments","Configuration","Transcripts","GeneFamilies","TranscriptsGo",
				"TranscriptsInterpro","TranscriptsLabels","TranscriptsKo","DataSources","ExperimentJobs", "GoParents",

				"GfData","AnnotSources","Annotation","ExtendedGo","KoTerms","ProteinMotifs", "HelpTooltips");

  var $components	= array("Cookie","TrapidUtils", "DataTable");

  var $paginate		= array(
				"Transcripts"=>
				array(
				      "limit"=>10,
				      "order"=>array("Transcripts.transcript_id"=>"ASC")
				),
				"GeneFamilies"=>
				array(
					"maxLimit"=>20,
			       		"order"=>array(
                            "GeneFamilies.experiment_id"=>"ASC", // Needed to force use of `experiment_id` index
			       		    "GeneFamilies.gf_id"=>"ASC"
                        )
				)
			  );



  function expansion($exp_id=null){
    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $available_species	= $this->AnnotSources->getSpeciesCommonNames();
    $this->set("available_species",$available_species);

    $available_types	= array("expansion"=>"Expansion","depletion"=>"Depletion");
    $this->set("available_types",$available_types);

    $available_ratios	= array("2","3","4","5","10","15","20");
    $this->set("available_ratios",$available_ratios);

    if($_POST){
      set_time_limit(120);
      if(!(array_key_exists("reference_species",$_POST) && array_key_exists("type",$_POST) && array_key_exists("ratio",$_POST))){
	$this->set("error","Undefined variables");return;
      }
      $selected_species	= filter_var($_POST['reference_species'], FILTER_SANITIZE_STRING);
      $selected_type	= filter_var($_POST['type'], FILTER_SANITIZE_STRING);
      $selected_ratio	= filter_var($_POST['ratio'], FILTER_SANITIZE_NUMBER_FLOAT);
      if(!(array_key_exists($selected_species,$available_species)
		&& array_key_exists($selected_type,$available_types)
	   && in_array($selected_ratio,$available_ratios))){
	$this->set("error","Illegal values");return;
      }
      $this->set("selected_species",$selected_species);
      $this->set("selected_type",$selected_type);
      $this->set("selected_ratio",$selected_ratio);

      //$zero_transcript	= false;
      $zero_reference	= false;
      //if(array_key_exists("zero_transcript",$_POST)){$zero_transcript=true;$this->set("zero_transcript",1);}
      if(array_key_exists("zero_reference",$_POST)){$zero_reference=true;$this->set("zero_reference",1);}

      $transcripts_gf		= $this->GeneFamilies->find("all",
						array("conditions"=>array("experiment_id"=>$exp_id),
						      "fields"=>array("gf_id","plaza_gf_id","gf_content","num_transcripts")));
      $this->set("result",1);
      $reference_counts		= array();
      $transcripts_counts	= array();
      if($exp_info["genefamily_type"]=="HOM"){
	$gf_mapping		= array();
	foreach($transcripts_gf as $tg){
	  $gf_id		= $tg['GeneFamilies']['gf_id'];
	  $transcript_count	= $tg['GeneFamilies']['num_transcripts'];
	  $transcripts_counts[$gf_id] = $transcript_count;
	  $gf_mapping[$tg['GeneFamilies']['plaza_gf_id']] = $gf_id;
	}
	$reference_genes	= $this->Annotation->find("all",array("conditions"=>array("species"=>$selected_species),"fields"=>array("gene_id")));
	$reference_genes	= $this->TrapidUtils->reduceArray($reference_genes,"Annotation","gene_id");
	$reference_counts_extgf = $this->GfData->getProfile($reference_genes,$exp_info['gf_prefix']);
	foreach($reference_counts_extgf as $plaza_gf=>$count){
	  if(array_key_exists($plaza_gf,$gf_mapping)){
	  	$trapid_gf	= $gf_mapping[$plaza_gf];
	  	$reference_counts[$trapid_gf] = $count;
	  }
	}
      }
      else if($exp_info["genefamily_type"]=="IORTHO"){
	//retrieve all genes from reference species in hashed array
	$reference_genes	= $this->Annotation->find("all",array("conditions"=>array("species"=>$selected_species),"fields"=>array("gene_id")));
	$reference_genes	= $this->TrapidUtils->indexArraySimple($reference_genes,"Annotation","gene_id","gene_id");
	foreach($transcripts_gf as $tg){
	  $gf_id		= $tg['GeneFamilies']['gf_id'];
	  $transcript_count	= $tg['GeneFamilies']['num_transcripts'];
	  $transcripts_counts[$gf_id] = $transcript_count;
	  $genes		= explode(" ",$tg['GeneFamilies']['gf_content']);
	  $ref_count		= 0;
	  foreach($genes as $g){if(array_key_exists($g,$reference_genes)){$ref_count++;}}
	  $reference_counts[$gf_id] = $ref_count;
	}
      }
      $this->set("reference_counts",$reference_counts);
      $this->set("transcripts_counts",$transcripts_counts);

    }
  }








  // Paginated table with gene families, with cake sorting allowed
  function index($exp_id=null){
    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $gene_families_p	= $this->paginate("GeneFamilies",array("GeneFamilies.experiment_id = '" . $exp_id . "'"));
    $this->set("gene_families",$gene_families_p);
    $gene_families_ids_original	= $this->TrapidUtils->reduceArray($gene_families_p,'GeneFamilies','plaza_gf_id');

    $gf_gene_counts	= array();
    $gf_species_counts	= array();
    if($exp_info['genefamily_type']=="HOM"){
      //get gf-gene counts from external database
      $gf_gene_counts		= $this->GfData->getGeneCount($gene_families_ids_original);
      $gf_species_counts     	= $this->GfData->getSpeciesCount($gene_families_ids_original);
    }
    else{
      //get iortho-content from the trapid database itself.
      foreach($gene_families_p as  $gfp){
	$gf_id			= $gfp['GeneFamilies']['gf_id'];
	$gene_ids		= explode(" ",$gfp['GeneFamilies']['gf_content']);
	$gf_gene_counts[$gf_id] = count($gene_ids);
	$gf_species_counts[$gf_id]  = $this->Annotation->getSpeciesCountForGenes($gene_ids);
      }
    }
    $this->set("gf_gene_counts",$gf_gene_counts);
    $this->set("gf_species_counts",$gf_species_counts);
    //pr($gf_species_counts);
    // Not clean but will do for the workshop
    if(strpos($exp_info['used_plaza_database'], "eggnog") !== false) {
        $this->set("eggnog_og_linkout", true);
    }
    $this->set("active_sidebar_item", "Browse gene families");
    $this -> set('title_for_layout', 'Gene families');
      $user_group=$this->Authentication->find("first",array("fields"=>array("group"),"conditions"=>array("user_id"=>parent::check_user())));
      if($user_group['Authentication']['group'] == "admin"){$this->set("admin", 1);}

  }



  function functional_annotation($exp_id=null,$gf_id=null){

    //Configure::write("debug",2);

    if(!$exp_id || !$gf_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    //check whether gene family is valid
    $gf_id	= filter_var($gf_id, FILTER_SANITIZE_STRING);
    $gf_info 	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));
    if(!$gf_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));} //failsafe
    $this->set("gf_info",$gf_info);
    $this->set("gf_id",$gf_id);

    //we cannot just take the best hit anymore, because of the different types of functional transfer.
    $transcripts = $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id),"fields"=>array("transcript_id")));
    $cutoff_presence		= 0.5;
    $transcripts		= $this->TrapidUtils->reduceArray($transcripts,"Transcripts","transcript_id");
    $num_transcripts		= count($transcripts);

    $go_data		= $this->TranscriptsGo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcripts, "type"=>"go")));
    $interpro_data	= $this->TranscriptsInterpro->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcripts, "type"=>"ipr")));
    $ko_data	= $this->TranscriptsKo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcripts, "type"=>"ko")));

    $go_ids		= $this->TrapidUtils->reduceArray($go_data,"TranscriptsGo","name");
    $interpro_ids	= $this->TrapidUtils->reduceArray($interpro_data,"TranscriptsInterpro","name");
    $ko_ids	= $this->TrapidUtils->reduceArray($ko_data,"TranscriptsKo","name");

    $go_ids		= array_unique($go_ids);
    $interpro_ids	= array_unique($interpro_ids);
    $ko_ids	= array_unique($ko_ids);

    //now get the descriptions, and the gene counts in the different species
    $go_descriptions		= $this->ExtendedGo->retrieveGoInformation($go_ids);
    $interpro_descriptions	= $this->ProteinMotifs->retrieveInterproInformation($interpro_ids);
    $ko_descriptions	= $this->KoTerms->retrieveKoInformation($ko_ids);

    $this->set("go_descriptions",$go_descriptions);
    $this->set("interpro_descriptions",$interpro_descriptions);
    $this->set("ko_descriptions", $ko_descriptions);

    // $go_profile		= $this->ExtendedGo->getPhyloProfile($go_ids);
    //$interpro_profile	= $this->ProteinMotifs->getPhyloProfile($interpro_ids);

      $this -> set('title_for_layout', 'Associated functional annotation &middot; ' . $gf_id);
  }



  function orf_lengths($exp_id=null,$gf_id=null){
    //first:check exp_id
    if(!$exp_id || !$gf_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    //pr($exp_info);

    //check whether gene family is valid
    $gf_id	= filter_var($gf_id, FILTER_SANITIZE_STRING);
    $gf_info 	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));
    if(!$gf_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));} //failsafe
    $transcripts = $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));
    if($gf_info['GeneFamilies']['num_transcripts']!=count($transcripts)){
	$this->normalizeGfInfo($exp_id,$gf_id,count($transcripts));
	$gf_info['GeneFamilies']['num_transcripts'] = count($transcripts);
    }
    $this->set("gf_info",$gf_info['GeneFamilies']);
    $this->set("transcripts",$transcripts);
    //now, retrieve gene_ids for the gene family.
    $gf_content	= $this->getGfContent($exp_id,$gf_id,$gf_info['GeneFamilies']['plaza_gf_id'],$exp_info['genefamily_type']);
    //get gene_sizes for this gene family content
    $gene_sizes	= $this->Annotation->getGeneSizes($gf_content);
    $this->set("gene_sizes",$gene_sizes);
  }




  function getGfContent($exp_id,$gf_id,$plaza_gf_id,$gf_type){
    $result = array();
    if($gf_type=="HOM"){
      $t	= $this->GfData->find("all",array("conditions"=>array("gf_id"=>$plaza_gf_id),"fields"=>array("gene_id")));
      $result	= $this->TrapidUtils->reduceArray($t,"GfData","gene_id");
    }
    else if($gf_type="IORTHO"){
      $t	= $this->GeneFamilies->find("first",array("fields"=>array("gf_content"),"conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));
      $result	= explode(" ",$t['GeneFamilies']['gf_content']);
    }
    return $result;
  }



  function multifasta($exp_id=null,$gf_id=null){
    //first:check exp_id
    if(!$exp_id || !$gf_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    //pr($exp_info);

    //check whether gene family is valid
    $gf_id	= filter_var($gf_id, FILTER_SANITIZE_STRING);
    $gf_info 	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));
    if(!$gf_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));} //failsafe
    $transcripts = $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));

    //get gf content
    $gf_content = $this->getGfContent($exp_id,$gf_id,$gf_info['GeneFamilies']['plaza_gf_id'],$exp_info['genefamily_type']);

    //get gf_content sequences
    $gf_content_seqs  = $this->Annotation->find("all",array("conditions"=>array("gene_id"=>$gf_content),"fields"=>array("gene_id","seq")));

    $this->set("transcripts",$transcripts);
    $this->set("gf_content",$gf_content_seqs);
    $this->set("file_name","multifasta_".$exp_id."_".$gf_id.".txt");
    $this->layout = "";
  }



  function species_selection($exp_id=null){
    //first:check exp_id
    if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);

    //retrieve species from associated database
    $all_species = $this->AnnotSources->find("all");
    $this->set("all_species",$all_species);
    //pr($all_species);
  }


  function gene_family($exp_id=null,$gf_id=null){
    //Configure::write("debug",2);
    //data for gene family must be present in tables 'transcripts','gene_families'
    if(!$exp_id || !$gf_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    //pr($exp_info);

      //check whether gene family is valid
    $gf_id	= filter_var($gf_id, FILTER_SANITIZE_STRING);
    $gf_info 	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));
    if(!$gf_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));} //failsafe

    //pr($gf_info);

    //check whether the number of jobs in the queue for this experiment has not been reached.
    $current_job_number = $this->ExperimentJobs->getNumJobs($exp_id);
    if($current_job_number>=MAX_CLUSTER_JOBS){$this->set("max_number_jobs_reached",true);}

    $transcript_count = $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));
    if($gf_info['GeneFamilies']['num_transcripts']!=$transcript_count){
	$this->normalizeGfInfo($exp_id,$gf_id,$transcript_count);
	$gf_info['GeneFamilies']['num_transcripts'] = $transcript_count;
    }
    $this->set("gf_info",$gf_info['GeneFamilies']);
    if($exp_info['genefamily_type']=='IORTHO'){
      $this->set("gf_content",$this->Annotation->getSpeciesForGenes(explode(" ",$gf_info['GeneFamilies']['gf_content'])));
      $this->set("all_species",$this->AnnotSources->getSpeciesCommonNames());
    }


    $transcripts_p	= $this->paginate("Transcripts",array("experiment_id"=>$exp_id,"gf_id"=>$gf_id));
    $transcript_ids	= $this->TrapidUtils->reduceArray($transcripts_p,"Transcripts","transcript_id");
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

    // KO
    $transcripts_ko = $this->TrapidUtils->indexArray($this->TranscriptsKo->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids, "type"=>"ko"))),"TranscriptsKo","transcript_id","name");
    $ko_info	= [];
    if(count($transcripts_ko)!=0){
      $ko_ids = array_unique(call_user_func_array("array_merge",array_values($transcripts_ko)));
      $ko_info = $this->KoTerms->retrieveKoInformation($ko_ids);
    }

    //retrieve subset/label information
    $transcripts_labels	= $this->TrapidUtils->indexArray($this->TranscriptsLabels->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"transcript_id"=>$transcript_ids))),"TranscriptsLabels","transcript_id","label");


    // Adapt linkout if we use eggnog. Not clean but will do for the workshop.
    // Also get taxonomic scope of the currrent NOG
    if(strpos($exp_info['used_plaza_database'], "eggnog") !== false) {
        $this->set("eggnog_og_linkout", true);
        $gf_tax_scope = $this->GfData->getEggnogTaxScope($gf_info['GeneFamilies']['plaza_gf_id']);
        $this->set("gf_tax_scope", $gf_tax_scope);
    }

    // Subsets - # transcripts information and tooltip (for subset creation form)
    $all_subsets = $this->TranscriptsLabels->getLabels($exp_id);
    $tooltip_text_subset_creation = $this->HelpTooltips->getTooltipText("transcript_table_subset_creation");
    $this->set("all_subsets", $all_subsets);
    $this->set("tooltip_text_subset_creation", $tooltip_text_subset_creation);


    $this->set("transcript_data",$transcripts);
    $this->set("transcripts_go",$transcripts_go);
    $this->set("transcripts_ipr",$transcripts_ipr);
    $this->set("transcripts_ko",$transcripts_ko);
    $this->set("transcripts_labels",$transcripts_labels);
    $this->set("go_info_transcripts",$go_info);
    $this->set("ipr_info_transcripts",$ipr_info);
    $this->set("ko_info_transcripts",$ko_info);

    $this -> set('title_for_layout', $gf_id.' &middot; Gene family');
  }


  // Get top GF GO terms to display GF tooltip (used in core GF completeness core GF tables).
  function top_go_tooltip($exp_id=null, $ref_gf_id=null){
      $this->layout = "";
      $n_max = 3;
      if(!$exp_id || !$ref_gf_id){return;}
      parent::check_user_exp($exp_id);
      $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
      // $this->set("exp_info",$exp_info);
      // $this->set("exp_id",$exp_id);
      // Check if GF exists in reference database
      $gf_exists = $this->GfData->gfExists($ref_gf_id);
      if($gf_exists) {
        $this->set("ref_gf_id", $ref_gf_id);
        $ref_gf_top_gos = $this->GfData->getTopGoTerms($ref_gf_id, $n_max);
        $this->set("top_gos", $ref_gf_top_gos);
      }
  }


    function normalizeGfInfo($exp_id,$gf_id,$new_count=null){
      if($new_count==null){
        $new_count = count($this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id))));
      }
      if($new_count==0){	//remove the gene family! No transcripts remaining for this gene family, so should be removed!
        $this->GeneFamilies->deleteAll(array("experiment_id"=>$exp_id,"gf_id"=>$gf_id));
        $this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
      }
      else{
        $this->GeneFamilies->updateAll(array("num_transcripts"=>"'".$new_count."'"),array("experiment_id"=>$exp_id,"gf_id"=>$gf_id));
        return;
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
  }
}



?>
