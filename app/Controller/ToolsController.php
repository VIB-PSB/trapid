<?php
App::uses('Sanitize', 'Utility');
App::import('Vendor', 'Fpdf', array('file' => 'fpdf/fpdf.php'));

/*
 * General controller class for the trapid functionality
 */
class ToolsController extends AppController{
  var $name		= "Tools";
  var $helpers		= array("Html", "Form");  // ,"Javascript","Ajax");

  var $uses		= array(
  // TRAPID core db
  "Authentication",
  "Configuration",
  "Experiments",
  "DataSources",
  "Transcripts",
  "GeneFamilies",
  "RnaFamilies",
  "TranscriptsGo",
  "TranscriptsInterpro",
  "TranscriptsKo",
  "TranscriptsLabels",
  "ExperimentLog",
  "ExperimentJobs",
  "ExperimentStats",
  "FunctionalEnrichments",
  "FullTaxonomy",
  "CompletenessResults",
  // Reference db
  "AnnotSources",
  "Annotation",
  "ExtendedGo",
  "KoTerms",
  "ProteinMotifs",
  "GfData",
  "GoParents",
  "HelpTooltips",
  "TranscriptsTax"
	);

  var $components	= array("Cookie","TrapidUtils","Statistics");
  var $paginate		= array(
				"Transcripts"=>
				array(
					"limit"=>10,
			       		"order"=>array("Transcripts.transcript_id"=>"ASC")
				)
			  );



  function view_msa($user_identifier=null,$exp_id=null,$gf_id=null,$type="normal",$viewtype="display"){
   //Configure::write("debug",1);
    $this->layout = "";
    if(!$user_identifier||!$exp_id || !$gf_id){return;}
    $user_identifer = filter_var($user_identifier, FILTER_SANITIZE_NUMBER_INT); // Unnecessary?
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    if(!parent::check_user_exp_no_cookie($user_identifier,$exp_id)){return;}
    $gf_info	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));
    if(!$gf_info){return;}
    if(!($type=="normal" || $type=="stripped")){$type="normal";}
    $this->set("gf_id",$gf_id);
    if($type=="normal"){$this->set("msa",$gf_info['GeneFamilies']['msa']);}
    else if($type=="stripped"){$this->set("msa",$gf_info['GeneFamilies']['msa_stripped']);}
    $this->set("file_name","msa_".$exp_id."_".$gf_id.".faln");
  }


    /**
     * Get MSA (normal or stripped) for a given experiment and gene family from the DB, as raw text.
     * The returned data gets formatted in the `get_msa` view and is then ready to visualize with BioJS's MSAViewer.
     */
    // When using an incorrect exp_id, some warnings are thrown: should disappear after debug mode is disabled?
  function get_msa($exp_id=null, $gf_id=null, $type="normal"){
    $this->layout = "";
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    if(!$gf_id) {
        return;
    }
    if(!$this->GeneFamilies->gfExists($exp_id, $gf_id)) {
        return;
    }
    // Get field that is storing the msa in the database, depending on `$type`
      if($type == "stripped") {
          $alignment_field = "msa_stripped";
      }
      else {
          $alignment_field = "msa";
      }
      // $gf_id = mysql_real_escape_string($gf_id);
      // Retrieve data from the db and create a string. If not empty, return it.
      $alignment = $this->GeneFamilies->find("first", array("fields"=>array($alignment_field), "conditions"=>array("experiment_id"=>$exp_id, "gf_id"=>$gf_id)));
      $alignment_str = $alignment['GeneFamilies'][$alignment_field];
      if(!$alignment_str) {
          return;
      }
      $this->set("aln", $alignment_str);
  }



  function create_msa($exp_id=null,$gf_id=null,$stripped=null){
    if(!$exp_id || !$gf_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $this -> set('title_for_layout', 'Multiple sequence alignment');
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

    $MAX_GENES_MSA_TREE		= 250; // 200;
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



  function view_tree($user_identifier=null,$exp_id=null,$gf_id=null,$format="xml"){
    $this->layout 	= "";
    if(!$user_identifier||!$exp_id || !$gf_id){return;}
    $user_identifer 	= filter_var($user_identifier, FILTER_SANITIZE_NUMBER_INT);
    // $exp_id		= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
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


    /**
     * Get computed phylogenetic tree for a given experiment and gene family from the DB, as raw text.
     */
    // When using an incorrect exp_id, some warnings are thrown: should disappear after debug mode is disabled?
    function get_tree($exp_id=null, $gf_id=null, $format="newick"){
        $this->layout = "ajax";
        // $this->autoRender = false;
        // $exp_id	= mysql_real_escape_string($exp_id);
        parent::check_user_exp($exp_id);
        if(!$gf_id) {
            return;
        }
        if(!$this->GeneFamilies->gfExists($exp_id, $gf_id)) {
            return;
        }
        // Get field that is storing the msa in the database, depending on `$type`
        if($format == "newick") {
            $tree_field = "tree";
        }
        else {
            $tree_field = "xml_tree";
        }
        // $gf_id = mysql_real_escape_string($gf_id); // Not needed (find)?
        // Retrieve data from the db and create a string. If not empty, return it.
        $tree = $this->GeneFamilies->find("first", array("fields"=>array($tree_field), "conditions"=>array("experiment_id"=>$exp_id, "gf_id"=>$gf_id)));
        $tree_str = $tree['GeneFamilies'][$tree_field];
        if(!$tree_str) {
            return;
        }
        $this->set("tree", $tree_str);
    }



  function create_tree($exp_id=null, $gf_id=null){
    if(!$exp_id || !$gf_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    $this -> set('title_for_layout', "MSA & phylogenetic tree");

    // Get tooltip content
    $tooltips = $this->TrapidUtils->indexArraySimple(
        $this->HelpTooltips->find("all", array("conditions"=>array("tooltip_id LIKE 'msatree_%'"))),
        "HelpTooltips","tooltip_id","tooltip_text"
    );
    $this->set("tooltips", $tooltips);

    /* TODO: fix species/gene selection tree generation! The current `plotTree` function relies on creating big ints...
    By default, PHP displays up to 14 significant digits (`precision` variable, see this link for more information
    https://www.php.net/manual/en/ini.core.php#ini.precision). This made it impossible to generate the species/gene
    selection tree when dealing when having to deal with lineages that are 'too deep' in the taxonomy.
    Setting a higher value to fixes the issue (i.e. users can select species/genes for their MSA and trees) but is not
    a solution.
    */
    ini_set('precision', 25);

    // Check gf_id
    $gf_info	= $this->GeneFamilies->find("first",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id)));
    if(!$gf_info){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("gf_id",$gf_id);
    $this->set("gf_info",$gf_info);

    // Check whether the number of jobs in the queue for this experiment has not been reached.
    $current_job_number = $this->ExperimentJobs->getNumJobs($exp_id);
    if($current_job_number>=MAX_CLUSTER_JOBS){$this->redirect(array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id));}

    // Get phylogenetic profile, depending on type of GF assignment (HOM/IORTHO)
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

    // Get number of transcripts which are partial
    $num_partial_transcripts = $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id,"meta_annotation"=>"Partial")));
    $this->set("num_partial_transcripts",$num_partial_transcripts);

    // Get all transcripts, together with their meta annotation
    $gf_transcripts	= $this->Transcripts->find("all",array("conditions"=>array("experiment_id"=>$exp_id,"gf_id"=>$gf_id),"fields"=>array("transcript_id","meta_annotation")));
    $gf_transcripts	= $this->TrapidUtils->indexArraySimple($gf_transcripts,"Transcripts","transcript_id","meta_annotation");
    $this->set("gf_transcripts",$gf_transcripts);

    // Retrieve the species from the associated reference database
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


    $MAX_GENES_MSA_TREE		= 250;  // 200;
    $this->set("MAX_GENES",$MAX_GENES_MSA_TREE);

    // List valid options
    $tree_programs	= array("fasttree"=>"FastTree", "iqtree"=>"IQ-TREE", "phyml"=>"PhyML");
    $this->set("tree_programs",$tree_programs);
    $tree_program	= "fasttree";
    $this->set("tree_program",$tree_program);

    $msa_programs	= array("muscle"=>"MUSCLE", "mafft"=>"MAFFT");
    $this->set("msa_programs",$msa_programs);
    $msa_program = "muscle";
    $this->set("msa_program", $msa_program);

    $editing_modes	= ["none", "column", "row", "column_row"];
    $this->set("editing_modes",$editing_modes);
    $editing_mode = "column";
    $this->set("editing_mode",$editing_mode);

    $include_subsets	= false;
    $include_meta	= false;
    $this->set("include_subsets",$include_subsets);
    $this->set("include_meta", $include_meta);

    // Check whether there already are any MSA / stripped MSA / tree present in the database.
      $previous_results = array("msa"=>false, "msa_stripped"=>false, "tree"=>false);
      if($gf_info['GeneFamilies']['msa']){
          $previous_results['msa'] = true;
          $this->set("full_msa_length",$this->TrapidUtils->getMsaLength($gf_info["GeneFamilies"]["msa"]));
      }
      if($gf_info['GeneFamilies']['msa_stripped']){
          $previous_results['msa_stripped'] = true;
          $this->set("stripped_msa_length",$this->TrapidUtils->getMsaLength($gf_info["GeneFamilies"]["msa_stripped"]));
      }
      if($gf_info['GeneFamilies']['tree']){ $previous_results['tree'] = true; }
      $this->set("previous_results", $previous_results);

    // Get the used species for current MSA
    if($previous_results['msa']){
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

    // New MSA/tree creation
    if($_POST){
      $tmp_dir	= TMP."experiment_data/".$exp_id."/";
      $this->set("previous_results", array("msa"=>false, "msa_stripped"=>false, "tree"=>false));
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

      // First check if user chose to generate MSA only!
      $msa_only = false;
      if(array_key_exists('msa_only',$_POST) && $_POST['msa_only']=='y') {
          $msa_only = $_POST['msa_only'];
      }

      // Check MSA program
      if(!array_key_exists("msa_program",$_POST)){$this->set("error","No MSA algorithm defined");return;}
      if(array_key_exists($_POST['msa_program'],$msa_programs)){$msa_program = $_POST['msa_program'];}
      $this->set("msa_program",$msa_program);

      // If user chose to generate tree, some extra checks are performed: MSA editing, tree program/annotation
      if(!$msa_only) {
          // Check tree program
          if(!array_key_exists("tree_program",$_POST)){$this->set("error","No tree algorithm defined");return;}
          if(array_key_exists($_POST['tree_program'],$tree_programs)){$tree_program = $_POST['tree_program'];}
          $this->set("tree_program",$tree_program);

          // Select editing mode for MSA
          if(!array_key_exists("editing_mode",$_POST)){$this->set("error","No editing mode defined");return;}
          if(in_array($_POST["editing_mode"], $editing_modes)){$editing_mode = $_POST["editing_mode"];}
          $this->set("editing_mode", $editing_mode);

          // Select subset presence / meta-annotation presence
          if(array_key_exists('include_subsets', $_POST) && $_POST['include_subsets'] == "y") {
              $include_subsets=true;
          }
          if(array_key_exists('include_meta_annotation', $_POST) && $_POST['include_meta_annotation'] == "y") {
              $include_meta=true;
          }
          $this->set("include_subsets",$include_subsets);
          $this->set("include_meta",$include_meta);

      }


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

      if(!$msa_only) {
          $shell_file = $this->TrapidUtils->create_shell_script_tree($exp_id,$gf_id, $msa_program, $editing_mode, $tree_program, $include_subsets, $include_meta);
          $job_type = "tree";
      }
      else {
          $shell_file = $this->TrapidUtils->create_shell_script_msa($exp_id,$gf_id, $msa_program);
          $job_type = "msa";
      }
      if($shell_file == null || $qsub_file == null ){$this->set("error","Problem creating program files");return;}
      $qsub_out = $tmp_dir . $job_type . "_" . $exp_id . "_" . $gf_id . ".out";
      $qsub_err = $tmp_dir . $job_type . "_" . $exp_id . "_" . $gf_id . ".err";
      if(file_exists($qsub_out)){unlink($qsub_out);}
      if(file_exists($qsub_err)){unlink($qsub_err);}
      $command  	= "sh $qsub_file -q medium -o $qsub_out -e $qsub_err $shell_file";
      $output		= array();
      exec($command,$output);
      $cluster_job	= $this->TrapidUtils->getClusterJobId($output);


      //add job to the cluster queue, and then redirect the entire program.
      //the user will receive an email to notify him when the job is done, together with a link to this page.
      //the result will then automatically be opened.
      $this->ExperimentJobs->addJob($exp_id,$cluster_job,"medium","create_".$job_type." ".$gf_id);

      $this->ExperimentLog->addAction($exp_id,"create_".$job_type, $gf_id);
      //declare options in the log
      $this->ExperimentLog->addAction($exp_id,"create_".$job_type, "options",1);
      $this->ExperimentLog->addAction($exp_id,"create_".$job_type, "gene_family=".$gf_id,2);
      $this->ExperimentLog->addAction($exp_id,"create_".$job_type, "selected_species",2);
      foreach($selected_species as $ss){$this->ExperimentLog->addAction($exp_id,"create_".$job_type, $ss,3);}
      $this->ExperimentLog->addAction($exp_id,"create_".$job_type,"msa_algorithm=".$msa_program,2);
      if(!$msa_only) {
          $this->ExperimentLog->addAction($exp_id,"create_tree","tree_algorithm=".$tree_program,2);
          $this->ExperimentLog->addAction($exp_id,"create_tree","msa_editing=".$editing_mode,2);
          $this->ExperimentLog->addAction($exp_id,"create_tree","incl_meta=".(int)$include_meta,2);
          $this->ExperimentLog->addAction($exp_id,"create_tree","incl_subsets=".(int)$include_subsets,2);
      }
      $this->ExperimentLog->addAction($exp_id,"create_" . $job_type . "_start",$gf_id,1);
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
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $possible_types	= array("go"=>"GO","ipr"=>"Protein domain");
    //check type
    // $type		= mysql_real_escape_string($type);
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
        $subset1	= filter_var($_POST['subset1'], FILTER_SANITIZE_STRING);
        $subset2	= filter_var($_POST['subset2'], FILTER_SANITIZE_STRING);
        if($subset1==$subset2){$this->set("error","Subset 1 should not be equal to Subset 2");return;}
	    if(!(array_key_exists($subset1,$subsets) && array_key_exists($subset2,$subsets))){
		$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
	    }
	    $this->set("subset1",$subset1);
      	    $this->set("subset2",$subset2);

      	    // $go_category	= mysql_real_escape_string($_POST['go_category']);
	        // $go_depth		= mysql_real_escape_string($_POST['go_depth']);
      	    // $go_category	= $this->ExtendedGo->getDataSource()->value($_POST['go_category'], 'string');
	        // $go_depth		= $this->ExtendedGo->getDataSource()->value($_POST['go_depth'], 'integer');
            // No need to escape as only used in `find()`?
      	    $go_category	= $_POST['go_category'];
	        $go_depth		= $_POST['go_depth'];
	    if(!(array_key_exists($go_category,$possible_go_types) && $go_depth>0 && $go_depth<=$max_depth)){
		$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
	    }
	    $this->set("go_category",$go_category);
	    $this->set("go_depth",$go_depth);

	    $min_coverage	= null;
	    // if(array_key_exists("min_coverage", $_POST)){$min_coverage=mysql_real_escape_string($_POST['min_coverage']);}
        // ???
	    if(array_key_exists("min_coverage", $_POST)){$min_coverage=$this->ExtendedGo->getDataSource()->value($_POST['min_coverage'], 'float');}
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

	    // pr(count($subset1_transcripts));
	    // pr(count($subset2_transcripts));

	    //ok, now select all the GOs from extended go which adhere to the given category and depth
	    $go_ids	= $this->ExtendedGo->find("all",array("conditions"=>array("type"=>"go","num_sptr_steps"=>$go_depth,"is_obsolete"=>"0", "info"=>$go_category),"fields"=>array("name","desc")));

	    if(count($go_ids)==0){$this->set("error","No GO terms match with the given parameters");return;}
	    $go_ids	= $this->TrapidUtils->indexArraySimple($go_ids,"ExtendedGo","name","desc");
	    $data_all = $this->TranscriptsGo->findTranscriptCountsFromGos($exp_id,array_keys($go_ids));
	    $data_sub1 = $this->TranscriptsGo->findTranscriptCounts($exp_id,array_keys($go_ids),$subset1_transcripts);
	    $data_sub2 = $this->TranscriptsGo->findTranscriptCounts($exp_id,array_keys($go_ids),$subset2_transcripts);

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
//	    pr($result);
      }

    }

  }





  /*
   * Display ratios between GO or protein domains
   */
  function compare_ratios($exp_id=null, $type=null){
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
    $this -> set('title_for_layout', "Compare transcript subsets");
    $this->set("active_sidebar_item", "Compare subsets");


      // Possible functional annotation types, depending on ref. DB type
    $possible_types	= array("go"=>"GO","ipr"=>"Protein domain");
    if(strpos($exp_info["used_plaza_database"], "eggnog") !== false){
        // Modify available types for EggNOG database
        $possible_types = array("go"=>"GO", "ko"=>"KO");
    }

    $this->set("available_types",$possible_types);

    $subsets	= $this->TranscriptsLabels->getLabels($exp_id);
    if(count($subsets) <= 1){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("subsets",$subsets);
    // $this->set("hashed_user_id",parent::get_hashed_user_id());

    if($_POST){
        // Check functional annotation type
        if(!array_key_exists("type",$_POST)) {
            $this->redirect("/");
        }
        $type = $_POST['type'];

        if(!array_key_exists($type, $possible_types)) {
            $this->redirect("/");
        }
        // Check subsets
        if(!(array_key_exists("subset1",$_POST) && array_key_exists("subset2",$_POST))) {
        	$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));
        }
        // No need to do that since these variables are used with `find()`?
        $subset1 = filter_var($_POST['subset1'], FILTER_SANITIZE_STRING);
        $subset2 = filter_var($_POST['subset2'], FILTER_SANITIZE_STRING);
        if($subset1==$subset2) {
            $this->set("error","subset 1 must be different from subset 2");return;
        }
        if(!(array_key_exists($subset1, $subsets) && array_key_exists($subset2, $subsets))) {
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
//	$go_descriptions	= $this->ExtendedGo->find("all",array("conditions"=>array("go"=>$go_ids)));
	$go_descriptions	= $this->ExtendedGo->find("all",array("conditions"=>array("name"=>$go_ids, "type"=>"go")));
//	$go_types		= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","go","type");
	$go_types		= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","name","info");
//	$go_descriptions	= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","go","desc");
	$go_descriptions	= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","name","desc");
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
//	$ipr_descriptions	= $this->ProteinMotifs->find("all",array("conditions"=>array("motif_id"=>$ipr_ids)));
	$ipr_descriptions	= $this->ProteinMotifs->find("all",array("conditions"=>array("name"=>$ipr_ids, "type"=>"interpro")));
//	$ipr_descriptions	= $this->TrapidUtils->indexArraySimple($ipr_descriptions,"ProteinMotifs","motif_id","desc");
	$ipr_descriptions	= $this->TrapidUtils->indexArraySimple($ipr_descriptions,"ProteinMotifs","name","desc");
    $this->set("data_subset1",$subset1_ipr_counts);
	$this->set("data_subset2",$subset2_ipr_counts);
	$this->set("descriptions",$ipr_descriptions);
      }
    $this->set("type", $type);
    }
  }


  function compare_ratios_download($exp_id=null,$type=null,$comparison=null,$subset1=null,$subset2=null,$subtype=null){
    // Configure::write("debug",2);
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $possible_types	= array("go"=>"GO","ipr"=>"Protein domain");
    //check type
    // $type		= mysql_real_escape_string($type);
    // No need to cescape since `type` is only compared to two possible values?

      if(!array_key_exists($type,$possible_types)){$this->redirect("/");}
    $this->set("type",$type);

    $subsets	= $this->TranscriptsLabels->getLabels($exp_id);
    if(count($subsets) <= 1){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}
    $this->set("subsets",$subsets);

    // $comparison		= mysql_real_escape_string($comparison); // Not needed since checked just below?
    $subset1		= filter_var($subset1, FILTER_SANITIZE_STRING);
    $subset2		= filter_var($subset2, FILTER_SANITIZE_STRING);
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
      // $subtype		= mysql_real_escape_string($subtype); // Not needed since checked just below?
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
//	$go_descriptions	= $this->ExtendedGo->find("all",array("conditions"=>array("go"=>$go_ids)));
	$go_descriptions	= $this->ExtendedGo->find("all",array("conditions"=>array("name"=>$go_ids, "type"=>"go")));
//	$go_types		= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","go","type");
	$go_types		= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","name","info");
//	$go_descriptions	= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","go","desc");
	$go_descriptions	= $this->TrapidUtils->indexArraySimple($go_descriptions,"ExtendedGo","name","desc");
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
//	$ipr_descriptions	= $this->ProteinMotifs->find("all",array("conditions"=>array("motif_id"=>$ipr_ids)));
	$ipr_descriptions	= $this->ProteinMotifs->find("all",array("conditions"=>array("name"=>$ipr_ids, "type"=>"interpro")));
//	$ipr_descriptions	= $this->TrapidUtils->indexArraySimple($ipr_descriptions,"ProteinMotifs","motif_id","desc");
	$ipr_descriptions	= $this->TrapidUtils->indexArraySimple($ipr_descriptions,"ProteinMotifs","name", "desc");
	$this->set("data_subset1",$subset1_ipr_counts);
	$this->set("data_subset2",$subset2_ipr_counts);
	$this->set("descriptions",$ipr_descriptions);
	$this->set("file_name","compare_ratios_".$exp_id."_".$type."_".$subset1."_".$subset2."_".$comparison_types[$comparison].".txt");
    }

  }




  function enrichment($exp_id=null){
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    $this -> set('title_for_layout', "Subset enrichment");
    $this->set("active_sidebar_item", "Subset enrichment");

      // Get tooltip content
    $tooltips = $this->TrapidUtils->indexArraySimple(
        $this->HelpTooltips->find("all", array("conditions"=>array("tooltip_id LIKE 'enrichment_%'"))),
        "HelpTooltips","tooltip_id","tooltip_text"
    );
    $this->set("tooltips", $tooltips);

    $possible_types	= array("go"=>"GO","ipr"=>"Protein domain");

      // Check DB type (quick and dirty)
      if(strpos($exp_info["used_plaza_database"], "eggnog") !== false){
          // Modify available types for EggNOG database
          $possible_types = array("go"=>"GO", "ko"=>"KO");
      }

    //check type
    // $type		= mysql_real_escape_string($type); // Not needed since checked just below?
//    if(!array_key_exists($type,$possible_types)){$this->redirect("/");}
    $this->set("available_types", $possible_types);

    //check whether the number of jobs in the queue for this experiment has not been reached.
    $current_job_number = $this->ExperimentJobs->getNumJobs($exp_id);
    if($current_job_number>=MAX_CLUSTER_JOBS){$this->redirect(array("controller"=>"trapid","action"=>"experiment",$exp_id));}

    // Get subsets for the experiment
    $subsets	= $this->TranscriptsLabels->getLabels($exp_id);
    if(count($subsets)==0){$this->set("error","No subsets defined");return;}
    $this->set("subsets",$subsets);

    //possible p-values
    $possible_pvalues	= array("0.05", "0.01", "0.005", "0.001", "0.0001", "0.00001");
    $selected_pvalue	= 0.05;
    $this->set("possible_pvalues",$possible_pvalues);
    $this->set("selected_pvalue",$selected_pvalue);

    //see if the user posted form
    if($_POST){
        // Check functional annotation type
        $type = "";
        if(!array_key_exists("annotation_type",$_POST)) {
            $this->set("error","no functional annotation type indicated in form");return;
        }
        if(!in_array($_POST['annotation_type'], array_keys($possible_types))) {
            $this->set("error","invalid functional annotation type");return;
        }
        else {
            $type = filter_var($_POST['annotation_type'], FILTER_SANITIZE_STRING);  // Useless filtering?
        }
        $this->set("type", $type);

      //check for present subset
      if(!array_key_exists("subset",$_POST)){$this->set("error","no subset indicated in form");return;}
      $subset		= filter_var($_POST['subset'], FILTER_SANITIZE_STRING);
      if(!array_key_exists($subset,$subsets)){$this->set("error","illegal subset");return;}
      $this->set("selected_subset",$subset);

      if(array_key_exists("pvalue",$_POST)){
	$pvalue	= filter_var($_POST['pvalue'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	if(in_array($pvalue,$possible_pvalues)){$selected_pvalue=$pvalue;}
	$this->set("selected_pvalue",$selected_pvalue);
      }
      // Check if previous results exist in the DB
      $enrichment_results = $this->FunctionalEnrichments->find("all", array("conditions"=>array("experiment_id"=>$exp_id, "label"=>$subset, "max_p_value"=>$pvalue, "data_type"=>$type)));

      // File locations
      $tmp_dir = TMP."experiment_data/".$exp_id."/";

      // If force computation or result does not exist: perform functional enrichment computation
      if(empty($enrichment_results) || !array_key_exists("use_cache",$_POST)){
        // Create shell script to `qsub` jobs on the web cluster
    	$qsub_file  = $this->TrapidUtils->create_qsub_script($exp_id);
        // Create enrichment configuration file (contains paths / db information)
        $ini_file = $this->TrapidUtils->create_ini_file_enrichment($exp_id, $exp_info['used_plaza_database']);
        //create shell file which contains necessary java programs
        $shell_file = $this->TrapidUtils->create_shell_file_enrichment($exp_id, $ini_file,  $type, $subset, $selected_pvalue);
	if($shell_file == null || $qsub_file == null ){$this->set("error","problem creating program files");return;}
	$qsub_out	= $tmp_dir.$type."_enrichment_".$subset.".out";
      	$qsub_err	= $tmp_dir.$type."_enrichment_".$subset.".err";
      	if(file_exists($qsub_out)){unlink($qsub_out);}
      	if(file_exists($qsub_err)){unlink($qsub_err);}
	$command  	= "sh $qsub_file -q short -o $qsub_out -e $qsub_err $shell_file";
	$output		= array();
        exec($command,$output);
	$cluster_job	= $this->TrapidUtils->getClusterJobId($output);
	if($cluster_job==null){$this->set("error","problem with retrieving job identifier from web cluster");return;}
	$this->set("job_id",$cluster_job);
	$this->set("load_results", true);
	return;
      }
      else {
          $this->set("load_results", true);
          return;
      }
    }
  }




    // NOTE (2017/12/07): function bugged?
  function go_enrichment_graph($exp_id=null,$selected_subset=null,$go_type=null,$pvalue=null){
    // $exp_id	= mysql_real_escape_string($exp_id);
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

    // Retrieve enrichment results
    $enrichment_results = $this->FunctionalEnrichments->find("all", array("conditions"=>array("experiment_id"=>$exp_id, "label"=>$selected_subset, "max_p_value"=>$pvalue, "data_type"=>"go")));
    if(empty($enrichment_results)){$this->redirect(array("controller"=>"tools","action"=>"enrichment",$exp_id));}

    // Read the results.

    $result	= array();
//    foreach(explode("\n",$result_data_string) as $r){
//      $s	= explode("\t",$r);
//      if(count($s)==5){
//	if($s[3]>0){
//		$result[$s[0]]	= array("go"=>$s[0],"hidden"=>$s[1],"p_value"=>$s[2],"ratio"=>$s[3],"perc"=>$s[4]);
//	}
//      }
//    }
//
      foreach($enrichment_results as $r){
          $res = $r['FunctionalEnrichments'];
          // Only working with enrichment (not depletion), so keep only results with log2 enrichment > 0
          if($res['log_enrichment'] > 0) {
              $result[$res['identifier']] = array("go"=>$res['identifier'],"hidden"=>$res['is_hidden'],"p_value"=>$res['p_value'], "ratio"=>$res['log_enrichment'], "perc"=>$res['subset_ratio']);
          }
      }

//    $go_data		= $this->ExtendedGo->find("all",array("conditions"=>array("go"=>array_keys($result))));
//    $go_types		= $this->TrapidUtils->indexArraySimple($go_data,"ExtendedGo","go","type");
//    $go_sptr		= $this->TrapidUtils->indexArraySimple($go_data,"ExtendedGo","go","num_sptr_steps");
    $go_data		= $this->ExtendedGo->find("all",array("conditions"=>array("name"=>array_keys($result), "type"=>"go")));
    $go_types		= $this->TrapidUtils->indexArraySimple($go_data,"ExtendedGo","name","info");
    $go_sptr		= $this->TrapidUtils->indexArraySimple($go_data,"ExtendedGo","name","num_sptr_steps");

//    pr($go_data);
//    pr($go_types);
//    pr($go_sptr);

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
      if(array_key_exists($i, $sptr_array)){
	  $level_gos 		= $sptr_array[$i];
	  foreach($level_gos as $level_go){
//	      pr($level_go);
	    if(count($all_graphs) > 0){
	      //check the already present graphs in the array, to determine whether or not the given GO term is already accounted for
    	      $done = false;
    	      foreach($all_graphs as $ag){
//    	          pr("BEFORE");
//                  if($level_go == "GO:0034621") {
//    	          pr($ag);
//                  }
//    	          pr("AFTER");
		if(array_key_exists($level_go, $ag['desc'])){$done = true; break 1;}
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
//    pr($all_graphs);
    //pr($accepted_gos);
    $this->set("all_graphs",$all_graphs);
    $this->set("accepted_gos",$accepted_gos);
    $this->set("data",$data);

  }


  function download_enrichment($exp_id=null,$type=null,$selected_subset=null,$pvalue=null){
    //Configure::write("debug",1);
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $this->layout = "";
//    //file locations
//    $tmp_dir		= TMP."experiment_data/".$exp_id."/";
    if($type==null || $selected_subset==null || $pvalue==null){
      $this->set("error","Cannot instantiate data");
      return;
    }

    $type		= filter_var($type, FILTER_SANITIZE_STRING);
    $selected_subset	= filter_var($selected_subset, FILTER_SANITIZE_STRING);
    $pvalue		= filter_var($pvalue, FILTER_SANITIZE_STRING);
    $this->set("file_name",$type."_enrichment_".$exp_id."_".$selected_subset."_".$pvalue.".tsv");
    $this->set("type", $type);

    // Retrieve enrichment results from db
    $db_results = $this->FunctionalEnrichments->find("all", array(
        "fields"=>array("identifier", "label", "p_value", "log_enrichment", "subset_ratio", "is_hidden"),
        "conditions"=>array("experiment_id"=>$exp_id, "label"=>$selected_subset, "max_p_value"=>$pvalue, "data_type"=>$type)
    ));
    if(empty($db_results)){
      $this->set("error", "No data found");
      return;
    }

      $result	= array();
      foreach($db_results as $r){
          $res = $r['FunctionalEnrichments'];
          // Only working with enrichment (not depletion), so keep only results with log2 enrichment > 0
          if($res['log_enrichment'] > 0) {
              if($type=="go"){
                  $result[$res['identifier']] = array("go"=>$res['identifier'],"is_hidden"=>$res['is_hidden'],"p-value"=>$res['p_value'], "enrichment"=>$res['log_enrichment'], "subset_ratio"=>$res['subset_ratio']);
              }
              else if($type=="ipr"){
                  $result[$res['identifier']] = array("ipr"=>$res['identifier'],"is_hidden"=>$res['is_hidden'],"p-value"=>$res['p_value'], "enrichment"=>$res['log_enrichment'], "subset_ratio"=>$res['subset_ratio']);
              }
              else if($type=="ko"){
                  $result[$res['identifier']] = array("ko"=>$res['identifier'],"is_hidden"=>$res['is_hidden'],"p-value"=>$res['p_value'], "enrichment"=>$res['log_enrichment'], "subset_ratio"=>$res['subset_ratio']);
              }
          }
      }

    $this->set("result",$result);

    // Get extra information
    if($type=="go"){
    	$go_data		= $this->ExtendedGo->find("all",array("conditions"=>array("name"=>array_keys($result), "type"=>"go")));
    	$go_descriptions	= $this->TrapidUtils->indexArray($go_data,"ExtendedGo","name","desc");
    	$go_types		= array("BP"=>array(), "MF"=>array(), "CC"=>array());
    	foreach($go_data as $gd){
      		$go_type	= $gd['ExtendedGo']['info'];
      		$go_types[$go_type][] = $gd['ExtendedGo']['name'];
    	}
    	$this->set("go_descriptions",$go_descriptions);
    	$this->set("go_types",$go_types);
    }
    else if($type=="ipr"){
      $ipr_data			= $this->ProteinMotifs->find("all",array("conditions"=>array("name"=>array_keys($result), "type"=>"interpro")));
      $ipr_descriptions		= $this->TrapidUtils->indexArray($ipr_data,"ProteinMotifs", "name", "desc");
      $ipr_types		= $this->TrapidUtils->indexArray($ipr_data,"ProteinMotifs", "name", "info");
      $this->set("ipr_descriptions",$ipr_descriptions);
      $this->set("ipr_types",$ipr_types);
    }
    else if($type=="ko"){
      $ko_data			= $this->KoTerms->find("all",array("conditions"=>array("name"=>array_keys($result), "type"=>"ko")));
      $ko_descriptions		= $this->TrapidUtils->indexArray($ko_data,"KoTerms", "name", "desc");
      $this->set("ko_descriptions",$ko_descriptions);
    }
  }


  function load_enrichment($exp_id=null,$type=null,$subset_title=null,$pvalue=null,$job_id=null){
    // Configure::write("debug",2);
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["finished"]);
    $this->set("exp_id",$exp_id);
    $this->layout = "";
//    $this->helpers[] = 'FlashChart';

    $possible_types	= array("go"=>"GO","ipr"=>"Protein domain", "ko"=>"KO");
    //check type
    // $type		= mysql_real_escape_string($type); // Checked below already against possible types
    if(!array_key_exists($type,$possible_types)){$this->redirect("/");}
    $this->set("available_types",$possible_types);
    $this->set("type",$type);


    if($subset_title==null){
      $this->set("error", "no subset defined");
      return;
    }
    $this->set("subset",urldecode($subset_title));

    if($pvalue==null){
      $this->set("error","no P-value defined");
      return;
    }
    $this->set("selected_pvalue",$pvalue);

    // Handle running cluster job (i.e. $job_id is not null)
    if($job_id!=null){
      $cluster_res	= $this->TrapidUtils->waitfor_cluster($exp_id,$job_id, 300, 5);
      if(isset($cluster_res["error"])){
          $this->set("error",$cluster_res["error"]);
          return;
      }
    }

    // No error when running enrichment job on the cluster, so let's retrieve the results from the database
    $db_results = $this->FunctionalEnrichments->find("all", array(
        "fields"=>array("identifier", "label", "p_value", "log_enrichment", "subset_ratio", "subset_hits", "is_hidden"),
        "conditions"=>array("experiment_id"=>$exp_id, "label"=>$subset_title, "max_p_value"=>$pvalue, "data_type"=>$type)
    ));
    // pr($db_results);

    $result	= array();
    foreach($db_results as $r){
        $res = $r['FunctionalEnrichments'];
      // Only working with enrichment (not depletion)
      if($res['log_enrichment'] > 0) {
		  $result[$res['identifier']] = array($type=>$res['identifier'],"is_hidden"=>$res['is_hidden'],"p-value"=>$res['p_value'], "enrichment"=>$res['log_enrichment'], "subset_ratio"=>$res['subset_ratio'], "subset_hits"=>$res['subset_hits']);
	  }
    }

    $this->set("result",$result);
//       pr($result);

    // Get extra information when working with GO: aspects / interactive enrichment GO graph input data
    if($type=="go"){
        $go_graph_namespaces = array("BP"=>"biological_process", "MF"=>"molecular_function", "CC"=>"cellular_component");
        $go_graph_data_all = array("headers"=>array("ontology"=>"go"), "nodes"=>array());
//        $go_graph_data = array("headers"=>array("ontology"=>"go"), "nodes"=>array());
        $n_trs_subset = $this->TranscriptsLabels->find("count", array("conditions"=>array("experiment_id"=>$exp_id, "label"=>$subset_title)));
        $root_go_terms = $this->ExtendedGo->getRootGoTerms();
        $root_go_data = $this->ExtendedGo->retrieveGoInformation($root_go_terms);
        $root_go_per_type = array();
        foreach ($root_go_data as $go_id=>$go_data) {
          $root_go_per_type[$go_data['type']] = $go_id;
        }

    	$go_data = $this->ExtendedGo->find("all",array("conditions"=>array("name"=>array_keys($result))));
    	$go_descriptions = $this->TrapidUtils->indexArray($go_data,"ExtendedGo","name","desc");
    	$go_parents = $this->GoParents->getGoParentsSimple(array_keys($result));

    	$go_types = array("BP"=>array(), "MF"=>array(), "CC"=>array());
        $go_graph_data_all["headers"]["ontology"] = "go";
        $go_graph_data_all["nodes"] = array();
    	foreach($go_data as $gd){
//      		$go_type	= $gd['ExtendedGo']['type'];
//      		$go_types[$go_type][] = $gd['ExtendedGo']['go'];
            // Db structure changed...
            $go_id = $gd['ExtendedGo']['name'];
      		$go_type	= $gd['ExtendedGo']['info'];
      		$go_types[$go_type][] = $go_id;
      		// Create node data for enrichment GO graph
            $node_parents = array_values(array_intersect(array_keys($result), $go_parents[$go_id]));
            // Add root term as parent for current aspect if there are none
            if(empty($node_parents)) {
              $node_parents[] = $root_go_per_type[$go_type];
            }
            $node_data = array(
                "id"    => $go_id,
                "name"  => $gd['ExtendedGo']['desc'],
                "namespace"     => $go_graph_namespaces[$go_type],
                "parents"       => $node_parents,
                "enricher"      => array(
                    "counts"=> array(
                        "ftr_size"      => intval($n_trs_subset),  // We set this to the size of the subset
//                        "set_size"      => intval("100"),
                        "n_hits"        => intval($result[$go_id]['subset_hits'])
                    ),
                    "scores"=> array(
                        "enr_fold"      => floatval($result[$go_id]['enrichment']),
                        "p-val"         => floatval($result[$go_id]['p-value'])
                    )
                )
            );
            $go_graph_data_all["nodes"][$go_id] = $node_data;
/*            if($result[$go_id]['is_hidden'] == 0) {
                // Filter parents to keep only those for which `is_hidden` equals zero (or root terms)
                $go_graph_data["nodes"][$go_id] = $node_data;
            }*/
    	}

    	// Add root nodes for each aspect having enriched GO terms
        foreach($go_types as $type => $gos) {
    	  if(!empty($gos)) {
    	      $root_go_id = $root_go_per_type[$type];
              $node_data = array(
                  "id"	=> $root_go_id,
                  "name"	=> $root_go_data[$root_go_id]['desc'],
                  "namespace"	=> $go_graph_namespaces[$type],
                  "is_root" => 1
              );
              $go_graph_data_all["nodes"][$root_go_id] = $node_data;
//              $go_graph_data["nodes"][$go_id] = $node_data;
          }
        }

/*        foreach($root_go_terms as $go_id) {
    	  $node_data = array(
              "id"	=> $go_id,
              "name"	=> $root_go_data[$go_id]['desc'],
              "namespace"	=> $go_graph_namespaces[$root_go_data[$go_id]['type']],
              "is_root" => 1
          );
            $go_graph_data_all["nodes"][$go_id] = $node_data;
//            $go_graph_data["nodes"][$go_id] = $node_data;

        }*/

    	$this->set("go_descriptions",$go_descriptions);
    	$this->set("go_types", $go_types);
    	$this->set("go_graph_data_all", $go_graph_data_all);
//    	$this->set("go_graph_data", $go_graph_data);

        // Get tooltip content
        $tooltips = $this->TrapidUtils->indexArraySimple(
            $this->HelpTooltips->find("all", array("conditions"=>array("tooltip_id LIKE 'go_enrichment_graph%'"))),
            "HelpTooltips","tooltip_id","tooltip_text"
        );
        $this->set("tooltips", $tooltips);

    }
    else if($type=="ipr"){
      $ipr_data			= $this->ProteinMotifs->find("all",array("conditions"=>array("name"=>array_keys($result))));
      $ipr_descriptions	= $this->TrapidUtils->indexArray($ipr_data,"ProteinMotifs", "name", "desc");
      $ipr_types	= $this->TrapidUtils->indexArray($ipr_data,"ProteinMotifs", "name", "info");
      $this->set("ipr_descriptions", $ipr_descriptions);
      $this->set("ipr_types", $ipr_types);
    }
    else if($type=="ko"){
      $ko_data			= $this->KoTerms->find("all",array("conditions"=>array("name"=>array_keys($result))));
      $ko_descriptions	= $this->TrapidUtils->indexArray($ko_data, "KoTerms", "name", "desc");
      $this->set("ko_descriptions", $ko_descriptions);
    }
  }







  function orf_statistics($exp_id=null){
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);


    //now, get distribution of meta annotation of ORF sequences.
    $meta_info	= $this->Transcripts->getMetaAnnotation($exp_id);
    $this->set("meta_info",$meta_info);

  }



  function length_distribution($exp_id=null){
    parent::check_user_exp($exp_id);
    $exp_info = $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    $this->set("exp_info", $exp_info);
    $this->set("exp_id", $exp_id);

    //Binning information
    $range_bins = [5, 200];
    $valid_sequence_types = ["transcript", "orf"];
    $num_bins = 30;
    $this->set("default_bins", $num_bins);
    $this->set("range_bins", $range_bins);
    // Allowed chart types
    $possible_graphtypes   = array("grouped"=>"","stacked"=>"normal");

    if($this->request->is('post')) {
//        pr(ini_get('memory_limit'));
        ini_set('memory_limit', '256M');
//        pr(ini_get('memory_limit'));
        $invalid_str = "<p class='text-center lead text-danger'>Invalid parameters. Cannot display graph!</p>";  // Quick and dirty error message
        $this->layout = "";
        $this->autoRender = false;

        // Check value for bins
        if(array_key_exists("num_bins",$_POST) && $_POST['num_bins'] >= $range_bins[0] && $_POST['num_bins'] <= $range_bins[1]) {
            $num_bins = $_POST['num_bins'];  // Already checked?
        }

        // Chart type
        $graph_type = "grouped";
        if(array_key_exists("graph_type", $_POST) && array_key_exists($_POST['graph_type'], $possible_graphtypes)) {
            $graph_type = $_POST['graph_type'];
        }

        // Transcript sequences
        if(array_key_exists("sequence_type", $_POST) && $_POST['sequence_type'] == "transcript") {
            $sequence_type = "transcript";
            $transcript_lengths	= $this->Transcripts->getLengths($exp_id, "transcript");
            $show_partials = false;
            $show_noinfo = false;
            $bins_transcript = $this->Statistics->create_length_bins($transcript_lengths, $num_bins);
            $json_transcript = $this->Statistics->create_json_data_infovis($bins_transcript,"Transcripts");

            if(array_key_exists("meta_partial",$_POST)) {
                $show_partials = true;
            }
            if(array_key_exists("meta_noinfo",$_POST)) {
                $show_noinfo = true;
            }
            if($show_partials){
                $partial_lengths = $this->Transcripts->getLengths($exp_id,"transcript", "Partial");
                $json_transcript = $this->Statistics->update_json_data("Transcripts (Partial)",
                    $partial_lengths,$json_transcript,$bins_transcript);
            }
            if($show_noinfo){
                $noinfo_lengths = $this->Transcripts->getLengths($exp_id,"transcript","No Information");
                $json_transcript = $this->Statistics->update_json_data("Transcripts (No Information)",
                    $noinfo_lengths,$json_transcript,$bins_transcript);
            }
            // Update last label
            $last_label_transcript	= explode(",",$bins_transcript["labels"][$num_bins-1]);
            $json_transcript["values"][$num_bins-1]["label"]=">=".$last_label_transcript[0];
            if(count($json_transcript["label"])>1){
                $json_transcript["label"][0] = "Transcripts (full-length or quasi full-length)";
            }

            $this->set("sequence_type", $sequence_type);
            $this->set("bins_transcript", $json_transcript);
            $this->set("chart_title", "Sequence length distribution");
            $this->set("chart_subtitle", "Sequence type: " . $sequence_type . " -- "  . $num_bins . " bins. ");
            $this->set("chart_data", $json_transcript);
            $this->set("stacking_str", $possible_graphtypes[$graph_type]);
            $this->set("chart_div_id", "transcript-length-chart");
            $this->render('/Elements/charts/bar_length_distribution');
        }

        // ORF sequences
        elseif(array_key_exists("sequence_type", $_POST) && $_POST['sequence_type'] == "orf") {
            $sequence_type = "orf";
            $orf_lengths = $this->Transcripts->getLengths($exp_id, "orf");
            $num_bins	= $_POST['num_bins'];  // Already checked?
            $selected_ref_species = "";

            if(array_key_exists("reference_species", $_POST)){
                $rs	= $_POST['reference_species']; // No need to check (find)?
                if($this->AnnotSources->find("first",array("conditions"=>array("species"=>$rs)))){
                    $selected_ref_species = $rs;
                }
            }
            $normalize_data	= false;
            // Normalize the values of the json data if necessary.
            if(array_key_exists("normalize",$_POST) && $selected_ref_species!=""){
                $normalize_data	= true;
            }

            $bins_orf			= $this->Statistics->create_length_bins($orf_lengths, $num_bins);
            $json_orf			= $this->Statistics->create_json_data_infovis($bins_orf, "ORF sequences");

            if($selected_ref_species!=""){
                $ref_species_lengths	= $this->Annotation->getLengths($selected_ref_species);
                $ref_species_data = $this->AnnotSources->find("first",array("conditions"=>array("species"=>$rs)));
                $ref_species_name = $ref_species_data['AnnotSources']['common_name'];
                $json_orf = $this->Statistics->update_json_data($ref_species_name, $ref_species_lengths,$json_orf,$bins_orf,false);
            }
            // Update last label
            $last_label_orf		= explode(",",$bins_orf["labels"][$num_bins-1]);
            $json_orf["values"][$num_bins-1]["label"]=">=".$last_label_orf[0];

            if($normalize_data){
                $json_orf			= $this->Statistics->normalize_json_data($json_orf);
            }

            $this->set("sequence_type", $sequence_type);
            $this->set("bins_transcript", $json_orf);
            $this->set("chart_title", "Sequence length distribution");
            $this->set("chart_subtitle", "Sequence type: " . $sequence_type . " -- "  . $num_bins . " bins. ");
            $this->set("chart_data", $json_orf);
            $this->set("stacking_str", $possible_graphtypes[$graph_type]);
            $this->set("chart_div_id", "orf-length-chart");
            $this->render('/Elements/charts/bar_length_distribution');

        }

        else {
            echo $invalid_str;
        }
    }

    // ORF reference species (used to populate form select options)
    $reference_species	= $this->AnnotSources->find("all", array("order"=>"`species` ASC"));
    $reference_species	= $this->TrapidUtils->indexArraySimple($reference_species, "AnnotSources", "species", "common_name");

      // Get tooltip content
      $tooltips = $this->TrapidUtils->indexArraySimple(
          $this->HelpTooltips->find("all", array("conditions"=>array("tooltip_id LIKE 'seq_len_%'"))),
          "HelpTooltips","tooltip_id","tooltip_text"
      );
      $this->set("tooltips", $tooltips);


      $this->set("available_reference_species", $reference_species);
    $this -> set('title_for_layout', 'Sequence length distribution');
  }



    // Get average transcript length for experiment `$exp_id`.
    function avg_transcript_length($exp_id=null) {
        $this->autoRender = false;
        if(!$exp_id){echo "";return;}
        parent::check_user_exp($exp_id);
        $avg_transcript_length = 0;  // Default value to display
        $seq_len_data = $this->Transcripts->getAvgTranscriptLength($exp_id);
        // Useless condition?
         if($seq_len_data) {
            $avg_transcript_length = $seq_len_data;
         }
        echo $avg_transcript_length;
    }


    // Get average ORF length for experiment `$exp_id`.
    function avg_orf_length($exp_id) {
        $this->autoRender = false;
        if(!$exp_id){echo "";return;}
        parent::check_user_exp($exp_id);
        $avg_transcript_length = 0;  // Default value to display
        $seq_len_data = $this->Transcripts->getAvgOrfLength($exp_id);
        // Useless condition?
        if($seq_len_data) {
            $avg_transcript_length = $seq_len_data;
        }
        echo $avg_transcript_length;
  }


    function statistics($exp_id=null, $pdf='0'){
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $this -> set('title_for_layout', "General statistics");
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);

    //all species names
    $all_species	= $this->AnnotSources->getSpeciesCommonNames();
    $this->set("all_species",$all_species);

    //get transcript information
    $num_transcripts	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id)));
    // $seq_stats		= $this->Transcripts->getSequenceStats($exp_id);
    $num_orfs	= $this->Transcripts->getOrfCount($exp_id);
    $num_start_codons	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"orf_contains_start_codon"=>1)));
    $num_stop_codons	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"orf_contains_stop_codon"=>1)));
    $num_putative_fs	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"putative_frameshift"=>1)));
    $num_correct_fs	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"is_frame_corrected"=>1)));
    $meta_annot_fulllength	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"meta_annotation"=>"Full Length")));
    $meta_annot_quasi	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"meta_annotation"=>"Quasi Full Length")));
    $meta_annot_partial	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"meta_annotation"=>"Partial")));
    $meta_annot_noinfo	= $this->Transcripts->find("count",array("conditions"=>array("experiment_id"=>$exp_id,"meta_annotation"=>"No Information")));

    $this->set("num_transcripts",$num_transcripts);
    // $this->set("seq_stats",$seq_stats);
    $this->set("num_orfs",$num_orfs);
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

    // Get RNA family information
    $num_rf = $this->RnaFamilies->find("count", array("conditions"=>array("experiment_id"=>$exp_id)));
    $num_transcript_rf = $this->Transcripts->find("count", array("conditions"=>array("experiment_id"=>$exp_id, "not"=>array("rf_ids"=>null))));
    $biggest_rf = $this->RnaFamilies->find("first", array("conditions"=>array("experiment_id"=>$exp_id),"order"=>array("num_transcripts DESC")));
    if(!$biggest_rf) {
        $biggest_rf_res = array("rf_id"=>"N/A","num_transcripts"=>0);
    }
    else {
        $biggest_rf_res = array("rf_id"=>$biggest_rf['RnaFamilies']['rf_id'],"num_transcripts"=>$biggest_rf['RnaFamilies']['num_transcripts']);
    }

    $this->set("num_rf",$num_rf);
    $this->set("num_transcript_rf",$num_transcript_rf);
    $this->set("biggest_rf",$biggest_rf_res);


    // Get high-level tax. classification information (if this step was performed)
      if($exp_info['perform_tax_binning'] == 1) {
          // Number of unclassified transcripts
          $num_unclassified_trs = $this->TranscriptsTax->find("count", array("conditions"=>array("experiment_id"=>$exp_id, "txid"=>"0")));
          // Number of classified transcripts
          $num_classified_trs = $this->TranscriptsTax->find("count", array("conditions"=>array("experiment_id"=>$exp_id, "not"=>array("txid"=>"0"))));
          // Superkingdom-level tax. classification summary
          $top_tax_domain = $this->read_top_tax_data($exp_id=$exp_id, $tax_rank="domain");
          $this->set("num_unclassified_trs", $num_unclassified_trs);
          $this->set("num_classified_trs", $num_classified_trs);
          $this->set("top_tax_domain", $top_tax_domain);
      }

    //get functional data information
    $go_stats = $this->ExperimentStats->getFuncAnnotStats($exp_id, "go");

//    debug($go_stats);
    $this->set("num_go",$go_stats['num_go']);
    $this->set("num_transcript_go",$go_stats['num_transcript_go']);
    $interpro_stats	= $this->ExperimentStats->getFuncAnnotStats($exp_id, "ipr");
    $this->set("num_interpro",$interpro_stats['num_interpro']);
    $this->set("num_transcript_interpro",$interpro_stats['num_transcript_interpro']);
    $ko_stats	= $this->ExperimentStats->getFuncAnnotStats($exp_id, "ko");
    $this->set("num_ko",$ko_stats['num_ko']);
    $this->set("num_transcript_ko",$ko_stats['num_transcript_ko']);

    // PDF output
    // Values that were retrieved via AJAX calls need to be retrrieved prior to generating the PDF output
    if($_POST || $pdf=='1'){
      if($pdf=='1' || (array_key_exists("export_type",$_POST) && $_POST['export_type']=="pdf")){
	$this->set("pdf_view",1);
	$this->layout	= "fpdf";

	// Retrieve average transcript/orf length
    $seq_stats		= $this->Transcripts->getSequenceStats($exp_id);

    $pdf_transcript_info	= array(
        "#Transcripts"=>$num_transcripts,
        "Average transcript length"=>$seq_stats['transcript']." basepairs",
        "#Transcripts with ORF"=>$num_orfs,
        "Average ORF length"=>$seq_stats['orf']." basepairs",
        "#ORFs with start codon"=>$num_start_codons." (".round(100*$num_start_codons/$num_transcripts,1)."%)",
        "#ORFs with stop codon"=>$num_stop_codons." (".round(100*$num_stop_codons/$num_transcripts,1)."%)",
        "#Transcripts with putative frameshift"=>$num_putative_fs." (".round(100*$num_putative_fs/$num_transcripts,1)."%)"
    );

//	$pdf_frameshift_info	= array(
//		        "#Transcripts with putative frameshift"=>$num_putative_fs." (".round(100*$num_putative_fs/$num_transcripts,1)."%)",
//			"#Transcripts with corrected frameshift"=>$num_correct_fs." (".round(100*$num_correct_fs/$num_transcripts,1)."%)"
//					);

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

	$pdf_rf_info		= array(
			 "#RNA families"=>$num_rf,
			 "#Transcripts in RF"=>$num_transcript_rf." (".round(100*$num_transcript_rf/$num_transcripts,1)."%)"
				);

	$pdf_func_info		= array(
	   "#Transcripts with GO"=>$go_stats['num_transcript_go']." (".round(100*$go_stats['num_transcript_go']/$num_transcripts,1)."%)",
	   "#Transcripts with Protein Domain"=>$interpro_stats['num_transcript_interpro']." (".round(100*$interpro_stats['num_transcript_interpro']/$num_transcripts,1)."%)",
	   "#Transcripts with KO"=>$ko_stats['num_transcript_ko']." (".round(100*$ko_stats['num_transcript_ko']/$num_transcripts,1)."%)",
				);


	$this->set("pdf_transcript_info",$pdf_transcript_info);
//	$this->set("pdf_frameshift_info",$pdf_frameshift_info);
	$this->set("pdf_meta_info",$pdf_meta_info);
	$this->set("pdf_gf_info",$pdf_gf_info);
	$this->set("pdf_rf_info", $pdf_rf_info);
	$this->set("pdf_func_info",$pdf_func_info);

//	$this->render();

          $this->set('fpdf', new FPDF('P','mm','A4'));
          $this->set('pdf_file_name', "TRAPID_statistics_". $exp_id .".pdf");
          $this->set('data', 'Hello, PDF world');
          $this->render('statistics');
      }
    }


  }

  function comparative_statistics($exp_id=null){

  }


  /* Check experiment + get general experiment information */
  function general_set_up($exp_id=null){
    // $exp_id	= mysql_real_escape_string($exp_id);
    parent::check_user_exp($exp_id);
    $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
    $this->TrapidUtils->checkPageAccess($exp_info['title'],$exp_info["process_state"],$this->process_states["default"]);
    $this->set("exp_info",$exp_info);
    $this->set("exp_id",$exp_id);
  }



  function GOSankey($exp_id=null,$go_web){
    $this->general_set_up($exp_id);
    $go = str_replace('-',':',$go_web);
    $place_holder = '###';

    // Need to use `urldecode()` otherwise `#` gets encoded.
    $urls = array(urldecode(Router::url(array("controller"=>"functional_annotation","action"=>"go",$exp_id,$place_holder))),
                  urldecode(Router::url(array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$place_holder)))
    );
    $rows	= $this->Transcripts->getOneGOToGFMapping($exp_id,$go);

    $this->set('mapping',json_encode($rows));
    $this->set('descriptions',json_encode($this->ExtendedGo->retrieveGoInformation(array($go))));
    $this->set('urls', json_encode($urls));
    $this->set("titleIsAKeyword", 'GO');
    $this->set("place_holder", $place_holder);

    $this -> set('title_for_layout', "GO term - gene family");
    $this->render('sankey_single');

  }

  function interproSankey($exp_id=null,$interpro=null){
    $this->general_set_up($exp_id);
    $place_holder = '###';

    // Need to use `urldecode()` otherwise `#` gets encoded.
    $urls = array(urldecode(Router::url(array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$place_holder))),
                  urldecode(Router::url(array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$place_holder)))
    );
    $rows	= $this->Transcripts->getOneInterproToGFMapping($exp_id,$interpro);

    $this->set('mapping',json_encode($rows));
    $this->set('descriptions',json_encode($this->ProteinMotifs->retrieveInterproInformation(array($interpro))));
    $this->set('urls', json_encode($urls));
    $this->set('titleIsAKeyword', 'Interpro');
    $this->set("place_holder", $place_holder);

      $this -> set('title_for_layout', "Protein domain - gene family");
      $this->render('sankey_single');
  }



  // Sankey diagram to visualize relationships between GFs and a KO term
  function KOSankey($exp_id=null, $ko=null){
    $this->general_set_up($exp_id);
    $place_holder = '###';

    // Need to use `urldecode()` otherwise `#` gets encoded.
    $urls = array(urldecode(Router::url(array("controller"=>"functional_annotation","action"=>"ko",$exp_id,$place_holder))),
                  urldecode(Router::url(array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$place_holder)))
    );
    $rows	= $this->Transcripts->getOneKOToGFMapping($exp_id, $ko);

    $this->set('mapping',json_encode($rows));
    $this->set('descriptions',json_encode($this->KoTerms->retrieveKoInformation(array($ko))));  // Why not `find()` if only one term?
    $this->set('urls', json_encode($urls));
    $this->set('titleIsAKeyword', 'KO');
    $this->set("place_holder", $place_holder);

      $this -> set('title_for_layout', "KO term - Gene family");
      $this->render('sankey_single');

  }



  function label_enrichedgo_gf2($exp_id=null){
//     ini_set('memory_limit', '512M');  // Hack-ish?
    $this->general_set_up($exp_id);
    $this -> set('title_for_layout', "Subsets - Enriched GO terms - GF intersection");

    $this->set("col_names", array('Label','Go','Gene family'));
    $this->set('dropdown_names',array('Domains', 'Gene families'));
    $place_holder = '###';
    $this->set("place_holder", $place_holder);

    $enriched_gos = $this->FunctionalEnrichments->getEnrichedGO($exp_id);
    $transcriptLabelGF = []; // $this->FunctionalEnrichments->getTranscriptToLabelAndGF($exp_id);
    $transcriptGO = [];  //$this->FunctionalEnrichments->getTranscriptGOMapping($exp_id);
    $counts = $this->TranscriptsLabels->getLabels($exp_id);// not necessary anymore, still used though
//    $sankey_link_data = $this->FunctionalEnrichments->getSankeyLinkData($exp_id, 'go');
    $sankey_enrichment_data = $this->FunctionalEnrichments->getSankeyEnrichmentResults($exp_id, 'go');

    $go_ids = array();
    foreach($enriched_gos as $label){
        if(array_key_exists('0.05',$label)){
            foreach ($label['0.05'] as $key => $val){
                $go_ids[] = $key;
            }
        }
    }
    $go_info	= $this->ExtendedGo->retrieveGoInformation($go_ids);

    $this->set('counts',$counts);

    $gf_prefix = $exp_id . "_";
    $urls = array(urldecode(Router::url(array("controller"=>"labels","action"=>"view",$exp_id,$place_holder))),
        urldecode(Router::url(array("controller"=>"functional_annotation","action"=>"go",$exp_id,$place_holder))),
        urldecode(Router::url(array("controller"=>"gene_family","action"=>"gene_family",$exp_id, $gf_prefix . $place_holder)))
    );
    $this->set('descriptions', $go_info);
    $this->set('enriched_gos',$enriched_gos);
    $this->set('transcriptGO', $transcriptGO);
    $this->set('transcriptLabelGF', $transcriptLabelGF);
    $this->set('sankey_enrichment_data', $sankey_enrichment_data['enrichment']);
    $this->set('sankey_gf_data', $sankey_enrichment_data['n_hits_gf']);
    $this->set('urls', $urls);
    $this->set('GO', true);
    $this->render('sankey_enriched2');
  }



  function label_enrichedinterpro_gf2($exp_id=null){
    $this->general_set_up($exp_id);
    $this -> set('title_for_layout', 'Sankey diagram');
    $this->set("col_names", array('Label','InterPro','Gene family'));
    $this->set('dropdown_names',array('Domains', 'gene families'));
    $place_holder = '###';
    $this->set("place_holder", $place_holder);

    $enriched_interpros = $this->FunctionalEnrichments->getEnrichedInterpro($exp_id);
    $transcriptLabelGF = []; // $this->FunctionalEnrichments->getTranscriptToLabelAndGF($exp_id);
    $transcriptInterpro = []; // $this->FunctionalEnrichments->getTranscriptInterproMapping($exp_id);
    $counts = $this->TranscriptsLabels->getLabels($exp_id);// not necessary anymore
    // $sankey_link_data = $this->FunctionalEnrichments->getSankeyLinkData($exp_id, 'ipr');
    $sankey_enrichment_data = $this->FunctionalEnrichments->getSankeyEnrichmentResults($exp_id, 'ipr');


      $interpros = array();
    foreach($enriched_interpros as $label){
        if(array_key_exists('0.05',$label)){
            foreach ($label['0.05'] as $key => $val){
                $interpros[] = $key;
            }
        }
    }
    $interpro_info	= $this->ProteinMotifs->retrieveInterproInformation($interpros);
    $this->set('counts', $counts);

    $gf_prefix = $exp_id . "_";

    $urls = array(urldecode(Router::url(array("controller"=>"labels","action"=>"view",$exp_id,$place_holder))),
        urldecode(Router::url(array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$place_holder))),
        urldecode(Router::url(array("controller"=>"gene_family","action"=>"gene_family",$exp_id, $gf_prefix . $place_holder)))
    );
    $this->set('enriched_gos',$enriched_interpros);
    $this->set('transcriptGO', $transcriptInterpro);
    $this->set('transcriptLabelGF', $transcriptLabelGF);
    $this->set('descriptions', $interpro_info);
    $this->set('sankey_enrichment_data', $sankey_enrichment_data['enrichment']);
    $this->set('sankey_gf_data', $sankey_enrichment_data['n_hits_gf']);
    $this->set('urls', $urls);
    $this->set('GO', false);
    $this->render('sankey_enriched2');
  }


  function label_enrichedko_gf2($exp_id=null){
    $this->general_set_up($exp_id);
    $this -> set('title_for_layout', 'Sankey diagram');
    $this->set("col_names", array('Label','KO','Gene family'));
    $this->set('dropdown_names',array('KOs', 'gene families'));
    $place_holder = '###';
    $this->set("place_holder", $place_holder);

    $enriched_kos = $this->FunctionalEnrichments->getEnrichedKo($exp_id);
    $transcriptLabelGF = []; // $this->FunctionalEnrichments->getTranscriptToLabelAndGF($exp_id);
    $transcriptKo = []; // $this->FunctionalEnrichments->getTranscriptInterproMapping($exp_id);
    $counts = $this->TranscriptsLabels->getLabels($exp_id);// not necessary anymore
    $sankey_enrichment_data = $this->FunctionalEnrichments->getSankeyEnrichmentResults($exp_id, 'ko');

    $ko_ids = array();
    foreach($enriched_kos as $label){
        if(array_key_exists('0.05',$label)){
            foreach ($label['0.05'] as $key => $val){
                $ko_ids[] = $key;
            }
        }
    }
    $interpro_info	= $this->KoTerms->retrieveKoInformation($ko_ids);
    $this->set('counts', $counts);

    $gf_prefix = $exp_id . "_";

    $urls = array(urldecode(Router::url(array("controller"=>"labels","action"=>"view",$exp_id,$place_holder))),
        urldecode(Router::url(array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$place_holder))),
        urldecode(Router::url(array("controller"=>"gene_family","action"=>"gene_family",$exp_id, $gf_prefix . $place_holder)))
    );
    $this->set('enriched_gos',$enriched_kos);
    $this->set('transcriptGO', $transcriptKo);
    $this->set('transcriptLabelGF', $transcriptLabelGF);
    $this->set('descriptions', $interpro_info);
    $this->set('sankey_enrichment_data', $sankey_enrichment_data['enrichment']);
    $this->set('sankey_gf_data', $sankey_enrichment_data['n_hits_gf']);
    $this->set('urls', $urls);
    $this->set('GO', false);
    $this->render('sankey_enriched2');
  }



  function label_gf_intersection($exp_id=null,$label=null){
    $this->general_set_up($exp_id);
    $place_holder = '###';
    $this->set("place_holder", $place_holder);
    $this->set('selected_label',$label);
    $this->set("col_names", array('Label','Gene family','Label'));
    $this->set('dropdown_name','Gene families');

    $label_rows	= $this->Transcripts->getLabelToGFMapping($exp_id,true);
    $this->set('mapping', $label_rows);
    $this->set('descriptions', array());
    $this->set('counts',$this->TranscriptsLabels->getLabels($exp_id));

    $urls = array(urldecode(Router::url(array("controller"=>"labels","action"=>"view",$exp_id,$place_holder))),
                  urldecode(Router::url(array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$place_holder)))
    );
    $this->set('urls', $urls);
    $this->set('GO', false);
    $this->render('sankey_intersection');
  }



  function label_interpro_intersection($exp_id=null,$label=null){
    $this->general_set_up($exp_id);

    $this->set('selected_label',$label);
    $this->set('dropdown_name','IPR Domains');
    $this->set("col_names", array('Label','InterPro','Label'));

    $this->set('counts',$this->TranscriptsLabels->getLabels($exp_id));
    $label_rows	= $this->TranscriptsLabels->getLabelToFctMapping($exp_id, "ipr", true);
    $interpros = array();
    foreach ($label_rows as $row){
        $interpros[] = $row[1];
    }
    $interpro_info	= $this->ProteinMotifs->retrieveInterproInformation($interpros);
    $this->set('mapping', $label_rows);
    $this->set('descriptions', $interpro_info);
    $place_holder = '###';
    $this->set("place_holder", $place_holder);
    $urls = array(urldecode(Router::url(array("controller"=>"labels","action"=>"view",$exp_id,$place_holder))),
                  urldecode(Router::url(array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$place_holder)))
    );
    $this->set('urls', $urls);
    $this->set('GO', false);

    $this->render('sankey_intersection');
  }


  function label_ko_intersection($exp_id=null,$label=null){
    $this->general_set_up($exp_id);

    $this->set('selected_label',$label);
    $this->set('dropdown_name','KOs');
    $this->set("col_names", array('Label','KO','Label'));

    $this->set('counts',$this->TranscriptsLabels->getLabels($exp_id));
    $label_rows	= $this->TranscriptsLabels->getLabelToFctMapping($exp_id, "ko", true);
    $ko_ids = array();
    foreach ($label_rows as $row){
        $ko_ids[] = $row[1];
    }
    $ko_info = $this->KoTerms->retrieveKoInformation($ko_ids);
    $this->set('mapping', $label_rows);
    $this->set('descriptions', $ko_info);
    $place_holder = '###';
    $this->set("place_holder", $place_holder);
    $urls = array(urldecode(Router::url(array("controller"=>"labels", "action"=>"view", $exp_id, $place_holder))),
                  urldecode(Router::url(array("controller"=>"functional_annotation", "action"=>"ko", $exp_id, $place_holder)))
    );
    $this->set('urls', $urls);
    $this->set('GO', false);
    $this->render('sankey_intersection');
  }



function label_go_intersection($exp_id=null,$label=null){
    $this->general_set_up($exp_id);

    $this->set('selected_label',$label);
    $this->set('dropdown_name',"GO's");

    $this->set("col_names", array('Label','Go','Label'));

    $this->set('counts',$this->TranscriptsLabels->getLabels($exp_id));
    $label_rows	= $this->TranscriptsLabels->getLabelToFctMapping($exp_id, "go", true);
    $go_ids = array();
    foreach ($label_rows as $row){
        $go_ids[] = $row[1];
    }
    $go_info	= $this->ExtendedGo->retrieveGoInformation($go_ids);

    $this->set('mapping', $label_rows);
    $this->set('descriptions', $go_info);
    $place_holder = '###';
    $this->set("place_holder", $place_holder);
    $urls = array(urldecode(Router::url(array("controller"=>"labels","action"=>"view",$exp_id,$place_holder))),
                  urldecode(Router::url(array("controller"=>"functional_annotation","action"=>"go",$exp_id,$place_holder)))
    );
    $this->set('urls', $urls);
    $this->set('GO', true);
    $this->render('sankey_intersection');
  }



  /* Taxonomic binning related controllers */

  // Main page for taxonomic binning.
    function tax_binning($exp_id=null){
        if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
        // $exp_id	= mysql_real_escape_string($exp_id);
        parent::check_user_exp($exp_id);
        $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
        $this->set("exp_info",$exp_info);
        $this->set("exp_id",$exp_id);
        // Pie/bar charts (domain / tax-rank composition).
        // Top tax domain level
        $top_tax_domain = $this->read_top_tax_data($exp_id=$exp_id, $tax_rank="domain");
        $domain_sum_transcripts = number_format(array_sum(array_map(function($x) {return intval($x[1]);}, $top_tax_domain)));
        // Top tax phylum level
//        $top_tax_phylum = $this->read_top_tax_data($exp_id=$exp_id, $tax_rank="phylum");
        $top_tax_phylum = $this->read_top_tax_data($exp_id=$exp_id, $tax_rank="phylum");
        $phylum_sum_transcripts = number_format(array_sum(array_map(function($x) {return intval($x[1]);}, $top_tax_phylum)));
        // Top tax order level
        $top_tax_order = $this->read_top_tax_data($exp_id=$exp_id, $tax_rank="order");
        $order_sum_transcripts = number_format(array_sum(array_map(function($x) {return intval($x[1]);}, $top_tax_order)));
        // Top tax genus level
        $top_tax_genus = $this->read_top_tax_data($exp_id=$exp_id, $tax_rank="genus");
        $genus_sum_transcripts = number_format(array_sum(array_map(function($x) {return intval($x[1]);}, $top_tax_genus)));
        // Set all variables and render page
        $this->set("display_krona_url", Router::url(array("controller"=>"tools","action"=>"display_krona", $exp_id)));
        $this->set("treeview_json_url", Router::url(array("controller"=>"tools","action"=>"get_treeview_json", $exp_id)));
        $this->set("top_tax_domain", $top_tax_domain);
        $this->set("domain_sum_transcripts", $domain_sum_transcripts);
        $this->set("top_tax_phylum", $top_tax_phylum);
        $this->set("phylum_sum_transcripts", $phylum_sum_transcripts);
        $this->set("top_tax_order", $top_tax_order);
        $this->set("order_sum_transcripts", $order_sum_transcripts);
        $this->set("top_tax_genus", $top_tax_genus);
        $this->set("genus_sum_transcripts", $genus_sum_transcripts);
        $tooltip_text_subset_name = $this->HelpTooltips->getTooltipText("tax_binning_subset_name");
        $this->set("tooltip_text_subset_name", $tooltip_text_subset_name);
        $this->set("active_sidebar_item", "Tax");
        $this -> set('title_for_layout', 'Taxonomic binning');
    }


    // Reads a Krona HTML file, store it as a variable and render the 'display_krona' page
    // By doing that we don't reveal the real location of the file
    // TODO: return error if user is not owner of experiment (not the experiments page?)
    // TODO: fix exception handling
    function display_krona($exp_id=null){
        // Check privileges to access current experiment's data
        parent::check_user_exp($exp_id);
        try {
            $krona_html_path = TMP."experiment_data/".$exp_id."/kaiju/kaiju_merged.krona.html";
            // pr($krona_html_path);  // Debug
            $krona_code = implode(" ", file($krona_html_path));
        }
        catch(Exception $e){
            $krona_code = "Unable to retrieve Krona code";
        }
        $this->layout = "";
        $this->set("krona_code", $krona_code);
        $this->render('tax_binning_krona');
    }


    // Read a top taxa summary file, return it as two-dimensional list.
    // In the future, this information should be in the database and read from there instead.
    function read_top_tax_data($exp_id=null, $tax_rank){
        $top_tax_path = TMP."experiment_data/".$exp_id."/kaiju/top_tax.".$tax_rank.".tsv";
        // pr($top_tax_path);
        $top_tax_data = array();
        $top_tax_file = file($top_tax_path);
        foreach($top_tax_file as $key=>$value) {
            $top_tax_data[$key] = explode("\t", $value);
//            pr($key);
        }
//         pr($top_tax_data);  // Debug
        return $top_tax_data;
    }


    // Reads JSON data for treeview visualization
    // By doing that we don't reveal the real location of the file
    // TODO: return error if user is not owner of experiment (not the experiments page?)
    // TODO: change the workaround used to get json (rename function from krona to something more general)
    function get_treeview_json($exp_id=null){
        // Check privileges to access current experiment's data
        parent::check_user_exp($exp_id);
        try {
            $json_web_path = TMP_WEB."experiment_data/".$exp_id."/kaiju/kaiju_merged.to_treeview.json";
            $json_string = implode("", file($json_web_path));
        }
        catch(Exception $e){
            $json_string = "Unable to retrieve JSON data. ";
        }
        $this->layout = "";
        $this->set("krona_code", $json_string);
        $this->render('tax_binning_krona');
    }


    // Create a transcript subset based on user-selected phylogenetic clades, by inspecting the `transcripts_tax` table.
    function create_tax_subset($exp_id=null) {
        $this->autoRender = false;
        // $exp_id	= mysql_real_escape_string($exp_id);
        parent::check_user_exp($exp_id);
        if($this->request->is('post')) {
            set_time_limit(75);
            $unclassified_str = "Unclassified";
            $max_tax = 20;
            // 1. Check subset name. If it already exists, return error message.
            $subset_name = filter_var($this->request->data['subset-name'], FILTER_SANITIZE_STRING);
            // Strip + replace blank spaces by underscores
            $subset_name = preg_replace('/\s+/', '_', trim($subset_name));
            if(empty($subset_name)){
                return("<label class=\"label label-warning\">Error: incorrect subset name</label>");
            }
            $exp_subsets = $this->TranscriptsLabels->find("all",array("fields"=>array("label"), "conditions"=>array("experiment_id"=>$exp_id)));
            $subset_exists = false;
            foreach($exp_subsets as $subset) {
                $subset_to_test = $subset["TranscriptsLabels"]["label"];
                if($subset_to_test == $subset_name) {
                    $subset_exists = true;
                    break;
                }
            }
            if($subset_exists) {
                return "<label class=\"label label-warning\">Error: subset already exists</label>";
            }

            $tax_names = array_slice(json_decode($this->request->data['tax-list']), 0, $max_tax);
            // 2. Look for redundancies in the selected clades and create a list of tax names to lookup
            $tax_lookup = array();
            // pr($tax_names);
            foreach ($tax_names as $tn){
                // pr($tn);
                if($tn == $unclassified_str) {
                    array_push($tax_lookup, $tn);
                }
                else {
                    $lineage = $this->FullTaxonomy->find("first", array("fields" => array("tax"), "conditions" => array("scname" => $tn)));
                    // Is any of its parent there? Yes = we do not want to look it up (redundant).
                    $parents_list = array_slice(explode('; ', $lineage["FullTaxonomy"]["tax"]), 1);
                    // pr($parents_list);
                    if(sizeof(array_intersect($parents_list, $tax_names)) == 0) {
                        array_push($tax_lookup, $tn);
                    }
                }
            }
            // pr($tax_lookup);
            // 3. Retrieve tax binning results
            $tax_binning_summary = $this->TranscriptsTax->getSummaryAndLineages($exp_id);
            // If unable to retrieve the results, return an error message!
            if(empty($tax_binning_summary)){
                return "<label class=\"label label-danger\">Error: unable to read results</label>";
            }
            // 4. iterate on tax binning results to retrieve sequence identifiers that match with user's choice
            $transcripts = array();
            $all_tax_ids = array_keys($tax_binning_summary);
            // For each result, check if lineage has an intersection with list of clades to lookup.
            // If yes, append transcripts to `$transcripts`
            foreach($all_tax_ids as $tax_id) {
                // If user chose `$unclassified_str`, handle it
                if($tax_id == 0 && in_array($unclassified_str, $tax_lookup)) {
                    $transcripts = array_merge($transcripts, $tax_binning_summary[$tax_id]["transcripts"]);
                    // pr($tax_binning_summary[$tax_id]["transcripts"]);
                }
                if(sizeof(array_intersect($tax_binning_summary[$tax_id]["lineage"], $tax_lookup)) != 0) {
                    $transcripts = array_merge($transcripts, $tax_binning_summary[$tax_id]["transcripts"]);
                }
            }
            // pr($transcripts);
            // 5. Create the new subset with these transcripts
            if(sizeof($transcripts)>0) {
                // Here I tried multiple ways to save the data
                // Would it be faster to use one of Cake's built-in saving functions?
                // Way 1: `INSERT` statements, looping on selected transcripts (w/o data check implemented in `enterTranscripts` method)
                // $counter = $this->TranscriptsLabels->enterTranscriptsNoCheck($exp_id, $transcripts, $subset_name);
                // Way 2: usuing CakePHP's `saveMany()` function
                // $counter = 0;
                // $to_save = array();
                // foreach($transcripts as $transcript) {
                //     $counter += 1;
                //     array_push($to_save, array("transcript_id"=>$transcript, "experiment_id"=>$exp_id, "label"=>$subset_name));
                // }
                // $this->TranscriptsLabels->saveMany($to_save, array("callbacks"=>false));
                // pr($to_save);

                // Way 3: using DboSource's `insertMulti()` method: seems to be the fastest as of now.
//                $counter = $this->TranscriptsLabels->enterTranscriptsInsertMulti($exp_id, $transcripts, $subset_name);
                $counter = $this->TranscriptsLabels->enterTranscriptsByChunks($exp_id, $transcripts, $subset_name, 20000, null);
                return "<label class=\"label label-success\">Subset created (".$counter." transcripts)</label>";
//                return "<label class=\"label label-success\">Subset created</label>";
            }
            return null; // meh?
        }
        // Should be a 404 error page
        else {
            return null;
        }
    }


    /* Core GF completeness analysis controllers */

    function core_gf_completeness($exp_id=null){
        if(!$exp_id){$this->redirect(array("controller"=>"trapid","action"=>"experiments"));}
        // $exp_id	= mysql_real_escape_string($exp_id);
        parent::check_user_exp($exp_id);
        $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
        $this->set("exp_info",$exp_info);
        $this->set("active_sidebar_item", "Core GF");
        $this->set("exp_id",$exp_id);
        $this -> set('title_for_layout', 'Core GF completeness');

        // Get tooltip content
        $tooltips = $this->TrapidUtils->indexArraySimple(
            $this->HelpTooltips->find("all", array("conditions"=>array("tooltip_id LIKE 'core_gf_%'"))),
            "HelpTooltips","tooltip_id","tooltip_text"
        );
        $this->set("tooltips", $tooltips);

        // Get all previously run core GF analyses
        $previous_completeness_jobs = $this->CompletenessResults->find("all", array("conditions"=>array("experiment_id"=>$exp_id), "fields"=>array("id", "clade_txid", "used_method", "label", "completeness_score")));
        // Get name corresponding to tax_ids for proper display
        foreach($previous_completeness_jobs as &$completeness_job){
            $tax_id = $completeness_job["CompletenessResults"]['clade_txid'];
            $tax_name = $this->FullTaxonomy->find("first", array("fields"=>array("scname"), "conditions"=>array("txid"=>$tax_id)));
            $tax_name = $tax_name["FullTaxonomy"]["scname"];
            $completeness_job["CompletenessResults"]['clade_name'] =  $tax_name;
        }
        $this->set("previous_completeness_jobs", $previous_completeness_jobs);

        // Get transcript labels to populate submission form
        $subsets = $this->TranscriptsLabels->getLabels($exp_id);
        $this->set("subsets", $subsets);

        // Get list of valid clades for the current reference database to populate submission form
        $core_gf_clades = array();
        $core_gf_clade_str = $this->Configuration->find("first", array('conditions'=>array('method'=>'completeness_parameters', 'key'=>$exp_info['used_plaza_database'], 'attr'=>'clade_list'), 'fields'=>array('value')));
        $core_gf_tax_ids = explode(';', $core_gf_clade_str['Configuration']['value']);
        // Get name corresponding to tax_ids for proper display
        // Note: Wouldn't it make more sense to run only one query using all tax ids?
        foreach($core_gf_tax_ids as $tax_id) {
            $tax_name = $this->FullTaxonomy->find("first", array("fields"=>array("scname"), "conditions"=>array("txid"=>$tax_id)));
            $tax_name = $tax_name["FullTaxonomy"]["scname"];
            $core_gf_clades[$tax_id] = $tax_name;
        }
        asort($core_gf_clades);
        $this->set("core_gf_clades", $core_gf_clades);

        // Check whether the number of jobs in the queue for this experiment has not been reached.
        // If there are already too many jobs running, it should not be possible to submit a new job (TODO).
            $current_job_number = $this->ExperimentJobs->getNumJobs($exp_id);
            // if($current_job_number>=MAX_CLUSTER_JOBS){$this->redirect(array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id));}
            // Submission of new core GF completeness job.
            if($this->request->is('post')){
                // pr($this->request->data);
                $clade_tax_id = $this->request->data['clade'];
                $clade_db = $this->FullTaxonomy->find("first",array("conditions"=>array("txid"=>$clade_tax_id)));
                // If clade not in `full_taxonomy`, do not launch job and return error message.
                // TODO: change to also check if the clade is in the valid cclade list of the reference database
                if(empty($clade_db)){
                    // To change (does not look clean). Move to 'job handling' function?
                    $this->autoRender=false;
                    return "Invalid clade, try again. ";
                    // $this->set("error","Invalid phylogenetic clade: '".$clade_tax_id."'.");return;
                }
                $transcript_label = "None";
                if($this->request->data['transcripts-choice'] != "all"){
                    $transcript_label = $this->request->data['transcripts-choice'];
                }
//                $tax_source = "json";
//                if($this->request->data['tax-radio-ncbi'] == "on") {
                    $tax_source = "ncbi";
//                }
                $species_perc = $this->request->data['species-perc'];
                $top_hits = $this->request->data['top-hits'];
                // Check if an identical job exists. If yes, just load the existing completeness results.
                // More checks to come ...
                $previous_completeness_job = $this->CompletenessResults->find("first", array("conditions"=>array("experiment_id"=>$exp_id,
                    "clade_txid"=>$clade_tax_id, "label"=>$transcript_label,
                    "used_method"=>"sp=".$species_perc.";ts=".$tax_source.";th=".$top_hits)));
                if(sizeof($previous_completeness_job) > 0){
                    $this->redirect(array("controller"=>"tools", "action"=>"load_core_gf_completeness", $exp_id,  $clade_tax_id, $transcript_label, $tax_source, $species_perc, $top_hits));
                }
                // No similar job exists: create and launch job
                // Check if we are working with EggNOG reference database (to call correct the completeness script)
                $db_type = "plaza";
                if(strpos($exp_info["used_plaza_database"], "eggnog") !== false){
                    $db_type = "eggnog";
                }
                $tmp_dir = TMP."experiment_data/".$exp_id."/";
                $completeness_dir		= $tmp_dir."completeness/";
                $qsub_file = $this->TrapidUtils->create_qsub_script($exp_id);
                $shell_file = $this->TrapidUtils->create_shell_script_completeness(
                    $clade_tax_id,  // Clade tax id
                    $exp_id,  // Experiment ID
                    $transcript_label,  // Transcript label. 'None' if all transcripts were chosen.
                    $species_perc, // `species_perc` (threshold to consider what is a core GF or not)
                    $top_hits, // `top_hits`, the number of top gits (by query) used for the core GF completeness analysis.
                    $tax_source, // `tax_source` (can only be 'ncbi' for now)
                    $db_type // The type of reference database (different wrapper script called when working with EggNOG)
                );
                if($shell_file == null || $qsub_file == null ){$this->set("error","Problem creating program files. ");return;}
                $qsub_out = $completeness_dir."core_gf_completeness_".$exp_id."_".$clade_tax_id."_sp".$species_perc."_th".$top_hits.".out";
                $qsub_err = $completeness_dir."core_gf_completeness_".$exp_id."_".$clade_tax_id."_sp".$species_perc."_th".$top_hits.".err";
                if(file_exists($qsub_out)){unlink($qsub_out);}
                if(file_exists($qsub_err)){unlink($qsub_err);}
                $command  	= "sh $qsub_file -q short -o $qsub_out -e $qsub_err $shell_file";
                $output		= array();
                exec($command,$output);
                // Get cluster job ID
                $cluster_job	= $this->TrapidUtils->getClusterJobId($output);
                // Add job to the `experiment_jobs` table.
                $this->ExperimentJobs->addJob($exp_id, $cluster_job, "short", "core_gf_completeness_".$clade_tax_id);
                $this->ExperimentLog->addAction($exp_id, "core_gf_completeness_".$clade_tax_id, "");
                $this->ExperimentLog->addAction($exp_id,"core_gf_completeness","options", 1);
                $this->ExperimentLog->addAction($exp_id,"core_gf_completeness_options","conservation_threshold=".$species_perc,2);
                $this->ExperimentLog->addAction($exp_id,"core_gf_completeness_options","top_hits=".$top_hits, 2);
//                $this->ExperimentLog->addAction($exp_id,"core_gf_completeness_options","tax_source"." ncbi", 2);
                $this->redirect(array("controller"=>"tools", "action"=>"handle_core_gf_completeness", $exp_id, $cluster_job, $clade_tax_id, $transcript_label, $tax_source, $species_perc, $top_hits));
            }
    }


    // Wait for cluster job to finish. Once it is over, redirect to `load_core_gf_completeness`
    function handle_core_gf_completeness($exp_id, $cluster_job_id, $clade_tax_id, $label, $tax_source, $species_perc, $top_hits) {
        parent::check_user_exp($exp_id);
        $this->autoRender=false;
        $job_result = $this->TrapidUtils->waitfor_cluster($exp_id, $cluster_job_id, 600, 5);
        // Once our job finished running (with error or not) remove it from the `experiment_jobs` table
        $this->ExperimentJobs->deleteJob($exp_id, $cluster_job_id);
        // Load results.
        // if($job_result != "ok"){
        //     $this->redirect(...);
        // }

//        if($job_result != 0) {
//            return "<p class='text-danger'>Error! Check chosen clade & parameters?</p>";
//        }
        $this->redirect(array("controller"=>"tools", "action"=>"load_core_gf_completeness", $exp_id,  $clade_tax_id, $label, $tax_source, $species_perc, $top_hits));
    }


    // Load results of a core GF analysis. Clade + subset + method (top hits + species percent) gives a unique id
    // TODO (more 'to think'): create a function to get the `id` field of the DB (and then just have to load that) or keep all parameters in URL? What is best?
    // TODO: find a way to NOT load all the data at once (will be particularly relevant when working with larger quantities of data ,i.e. EggNOG)
    function load_core_gf_completeness($exp_id, $clade_tax_id, $label, $tax_source, $species_perc, $top_hits){
        parent::check_user_exp($exp_id);
        $exp_info	= $this->Experiments->getDefaultInformation($exp_id);
        $this->layout = "";
        // Set clade tax name
        $tax_name = $this->FullTaxonomy->find("first", array("fields"=>array("scname"), "conditions"=>array("txid"=>$clade_tax_id)));
        $tax_name = $tax_name["FullTaxonomy"]["scname"];
        // Retrieve data from database
        $completeness_job = $this->CompletenessResults->find("first", array("conditions"=>array("experiment_id"=>$exp_id,
            "clade_txid"=>$clade_tax_id, "label"=>$label,
            "used_method"=>"sp=".$species_perc.";ts=".$tax_source.";th=".$top_hits)));
        // Dirty but works to catch errors.
        if(empty($completeness_job)) {
            $this->autoRender = false;
            return("<p class='text-danger'><strong>Error:</strong> could not retrieve any species from the reference database. Are you sure the clade you chose is represented there?</p>");
        }
        // Count number of missing/represented GFs. Done this way:
        // get length of strings: if 0, return 0, otherwise give the length of string splitted by `;`
        $n_missing = strlen($completeness_job['CompletenessResults']['missing_gfs']) ? sizeof(explode(";", $completeness_job['CompletenessResults']['missing_gfs'])) : 0;
        $n_represented = strlen($completeness_job['CompletenessResults']['represented_gfs']) ? sizeof(explode(";", $completeness_job['CompletenessResults']['represented_gfs'])) : 0;
        // pr(explode(";", $completeness_job['CompletenessResults']['represented_gfs']));
        $n_total = $n_missing + $n_represented;
        $missing_gfs_array = array();
        $represented_gfs_array = array();
        if($n_missing > 0) {
            foreach (explode(";", $completeness_job['CompletenessResults']['missing_gfs']) as $missing_gf_str) {
                $record = explode(":", $missing_gf_str);
                array_push($missing_gfs_array, array("gf_id" => $record[0], "n_genes" => $record[1], "n_species" => $record[2], "gf_weight" => $record[3]));
            }
            // pr($missing_gfs_array);
        }
        if($n_represented > 0) {
            foreach (explode(";", $completeness_job['CompletenessResults']['represented_gfs']) as $represented_gf_str) {
                $record = explode(":", $represented_gf_str);
                array_push($represented_gfs_array, array("gf_id" => $record[0], "n_genes" => $record[1], "n_species" => $record[2], "gf_weight" => $record[3], "queries"=> $record[4]));
            }
            // pr($represented_gfs_array);
        }

        // Get linkout prefix if it is allowed, otherwise return null
        if($exp_info['allow_linkout']){
            $linkout_prefix =  $exp_info['datasource_URL'];
        }
        else {
            $linkout_prefix = null;
        }
        // Check if we are working with EggNOG reference database (to get proper linkouts)
        $db_type = "plaza";
        if(strpos($exp_info["used_plaza_database"], "eggnog") !== false){
            $db_type = "eggnog";
        }
        // Finally, set all variables used in the view
        $this->set("exp_id", $exp_id);
        $this->set("label", $label);
        $this->set("tax_name", $tax_name);
        $this->set("n_missing", $n_missing);
        $this->set("n_represented", $n_represented);
        $this->set("n_total", $n_total);
        $this->set("completeness_score", $completeness_job['CompletenessResults']['completeness_score']);
        $this->set("missing_gfs_array", $missing_gfs_array);
        $this->set("represented_gfs_array", $represented_gfs_array);
        $this->set("species_perc", $species_perc);
        $this->set("top_hits", $top_hits);
        $this->set("linkout_prefix", $linkout_prefix);
        $this->set("db_type", $db_type);
    }


    // Delete coreF completeness results
    function delete_core_gf_results($exp_id=null, $clade_tax_id=null, $label=null, $tax_source=null, $species_perc=null, $top_hits=null){
        // $exp_id	= mysql_real_escape_string($exp_id);
        parent::check_user_exp($exp_id);
        // Build query to delete core GF completeness analysis results
        $delete_query = "DELETE FROM `completeness_results` WHERE `experiment_id`='" . $exp_id . "' AND `label` = '" . $label .
            "' AND `clade_txid` = '" . $clade_tax_id . "'";
            " AND `used_method` = 'sp=" . $species_perc . ";ts=" . $tax_source . ";th=" . $top_hits . "';";
        pr($delete_query);
        pr($delete_query);
        pr($delete_query);
        pr($delete_query);
        // Execute it and redirect to core GF page
        $this->CompletenessResults->query($delete_query);
        $this->redirect(array("controller"=>"tools","action"=>"core_gf_completeness", $exp_id));
    }


    /*
     * A (test) function to search for phylogenetic clades, used in the core GF completeness submission form. It takes
     * a prefix as input, and found phylogenetic clades from `full_taxonomy` table are return as JSON.
     * Format of returned data: `{tax_id: scname, ...}`
     */
    // Should we restrict this to logged-in users?
    function search_tax($clade_prefix) {
        $this->autoRender = false;
        $limit_results = 200;  // Retrieve only this amount of results
        $min_length = 3;  // Minimum length of prefix to search, will return nothing if less than that.
        if(strlen($clade_prefix) < $min_length){
            return(null);
            // throw new NotFoundException();
        }
        // Retrieve data
        // Should we allow search by tax ID?
        // $clades = $this->FullTaxonomy->find("all", array("fields"=>array("scname", "txid"), "conditions"=>array("OR"=>array("scname LIKE"=>$clade_prefix."%", "txid LIKE"=>$clade_prefix."%")), "limit"=>$limit_results));
        $clades = $this->FullTaxonomy->find("all", array("fields"=>array("scname", "txid"), "conditions"=>array("scname LIKE"=>$clade_prefix."%"), "limit"=>$limit_results));
        $clades_json = array();
        foreach($clades as $clade){
            $clades_json[$clade["FullTaxonomy"]["txid"]] = $clade["FullTaxonomy"]["scname"];
        }
        $clades_json = json_encode($clades_json);
        return($clades_json);
    }




  /*
   * Cookie setup:
   * The entire TRAPID website is based on user-defined data sets, and as such a method for
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
