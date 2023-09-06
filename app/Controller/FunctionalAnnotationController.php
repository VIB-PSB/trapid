<?php

/*
 * General controller class for exploration of functional annotations
 */

class FunctionalAnnotationController extends AppController {
    var $components = array("TrapidUtils");
    var $name = "FunctionalAnnotation";
    // Note: in the future, pagination should be handled by jQuery DataTables (for now we rely on CakePHP's pagination)
    var $paginate = array(
        "TranscriptsGo" => array("limit" => 10, "order" => array("TranscriptsGo.transcript_id" => "ASC")),
        "TranscriptsInterpro" => array("limit" => 10, "order" => array("TranscriptsInterpro.transcript_id" => "ASC")),
        "TranscriptsKo" => array("limit" => 10, "order" => array("TranscriptsKo.transcript_id" => "ASC"))
    );
    var $uses = array("AnnotSources", "Annotation", "Authentication", "Configuration", "Experiments", "ExtendedGo",
        "GeneFamilies", "GfData", "GoParents", "HelpTooltips", "KoTerms", "ProteinMotifs", "Transcripts",
        "TranscriptsGo", "TranscriptsInterpro", "TranscriptsKo", "TranscriptsLabels");


    // Intermediary page displaying an overview of the child GO terms (when less than 100) and associated data:
    // # of annotated transcripts and description
    function child_go($exp_id = null, $go_web = null) {
        $max_child_gos = 200;
        // Check experiment.
        if (!$exp_id || !$go_web) {
            $this->redirect(array("controller" => "trapid", "action" => "experiments"));
        }
        parent::check_user_exp($exp_id);
        $exp_info = $this->Experiments->getDefaultInformation($exp_id);
        $this->set("exp_info", $exp_info);
        $this->set("exp_id", $exp_id);
        $this->TrapidUtils->checkPageAccess($exp_info['title'], $exp_info["process_state"], $this->process_states["default"]);
        // $go			= mysql_real_escape_string(str_replace("-",":",$go_web));
        $go = str_replace("-", ":", $go_web); // No need to escape/check?
        $go_information = $this->ExtendedGo->find("first", array("conditions" => array("type" => "go", "name" => $go)));
        $num_transcripts = $this->TranscriptsGo->find("count", array("conditions" => array("experiment_id" => $exp_id, "type" => "go", "name" => $go)));
        if (!$go_information || $num_transcripts == 0) {
            $this->redirect(array("controller" => "trapid", "action" => "experiment", $exp_id));
        }
        $this->set("go", $go);
        $this->set("go_web", $go_web);
        $this->set("go_info", $go_information["ExtendedGo"]);
        $this->set("num_transcripts", $num_transcripts);

        // Get the child GO terms.
        $child_gos = $this->GoParents->find("all", array("conditions" => array("parent" => $go, "type" => "go"), "fields" => array("child")));
        $child_gos = $this->TrapidUtils->reduceArray($child_gos, "GoParents", "child");
        $this->set("num_child_gos", count($child_gos));
        if (count($child_gos) == 0) {
            return;
        }
        // Prevent data overload on page and too many queries
        /* if(count($child_gos) > $max_child_gos){
          $this->set("max_child_gos_reached",$max_child_gos);
          $child_gos	= array_slice($child_gos,0,$max_child_gos);
          }*/
        // Get descriptions for children GO terms.
        $go_descriptions = $this->ExtendedGo->find("all", array("conditions" => array("type" => "go", "name" => $child_gos)));
        $go_descriptions = $this->TrapidUtils->indexArrayMulti($go_descriptions, "ExtendedGo", "name", array("desc", "info"));

        // Get transcript counts for each child GO term.
        $go_counts = $this->TranscriptsGo->findTranscriptsFromGo($exp_id, $go_descriptions);
        $this->set("num_child_gos", count($go_counts));
        $this->set("child_go_counts", $go_counts);
        $this->set('title_for_layout', $go . ' &middot; Child GO terms');
    }


    // Intermediary page displaying an overview of the parental GO terms and associated data: # of annotated transcripts
    // and description.
    function parent_go($exp_id = null, $go_web = null) {
        $max_parental_gos = 200;
        // Check experiment.
        if (!$exp_id || !$go_web) {
            $this->redirect(array("controller" => "trapid", "action" => "experiments"));
        }
        parent::check_user_exp($exp_id);
        $exp_info = $this->Experiments->getDefaultInformation($exp_id);
        $this->set("exp_info", $exp_info);
        $this->set("exp_id", $exp_id);
        $this->TrapidUtils->checkPageAccess($exp_info['title'], $exp_info["process_state"], $this->process_states["default"]);
        // $go			= mysql_real_escape_string(str_replace("-",":",$go_web));
        $go = str_replace("-", ":", $go_web); // No need to escape/check?
        // $go_information	= $this->ExtendedGo->find("first",array("conditions"=>array("go"=>$go)));
        $go_information = $this->ExtendedGo->find("first", array("conditions" => array("name" => $go, "type" => "go")));
        $num_transcripts = $this->TranscriptsGo->find("count", array(
            "conditions" => array("experiment_id" => $exp_id, "type" => "go", "name" => $go)
        ));
        if (!$go_information || $num_transcripts == 0) {
            $this->redirect(array("controller" => "trapid", "action" => "experiment", $exp_id));
        }
        $this->set("go", $go);
        $this->set("go_web", $go_web);
        $this->set("go_info", $go_information["ExtendedGo"]);
        $this->set("num_transcripts", $num_transcripts);

        // Get the parent GO terms.
        // $parental_gos		= $this->GoParents->find("all",array("conditions"=>array("child_go"=>$go),"fields"=>array("parent_go")));
        // $parental_gos		= $this->TrapidUtils->reduceArray($parental_gos,"GoParents","parent_go");
        $parental_gos = $this->GoParents->find("all", array(
            "conditions" => array("child" => $go, "type" => "go"),
            "fields" => array("parent")
        ));
        $parental_gos = $this->TrapidUtils->reduceArray($parental_gos, "GoParents", "parent");
        $this->set("num_parent_gos", count($parental_gos));
        if (count($parental_gos) == 0) {
            return;
        }
        // Prevent data overload on page and too many queries.
        /* if(count($child_gos) > $max_child_gos){
          $this->set("max_child_gos_reached",$max_child_gos);
          $child_gos	= array_slice($child_gos,0,$max_child_gos);
          }*/
        // Get descriptions for parent GO terms.
        $go_descriptions = $this->ExtendedGo->find("all", array("conditions" => array("name" => $parental_gos, "type" => "go")));
        $go_descriptions = $this->TrapidUtils->indexArrayMulti($go_descriptions, "ExtendedGo", "name", array("desc", "info"));
        // Get transcript counts for each parent GO term.
        $go_counts = $this->TranscriptsGo->findTranscriptsFromGo($exp_id, $go_descriptions);
        $this->set("num_parent_gos", count($go_counts));
        $this->set("parent_go_counts", $go_counts);
        $this->set('title_for_layout', $go . ' &middot; Parental GO terms');
    }


    function go($exp_id = null, $go_web = null) {
        //check experiment.
        if (!$exp_id || !$go_web) {
            $this->redirect(array("controller" => "trapid", "action" => "experiments"));
        }
        parent::check_user_exp($exp_id);
        $exp_info = $this->Experiments->getDefaultInformation($exp_id);
        // Disable linkout if we use eggnog: there is no dedicated pages for functional annotations.
        // Not clean but will do for the workshop
        // TODO: update with updated method in `Experiments` model
        if (strpos($exp_info['used_plaza_database'], "eggnog") !== false) {
            $exp_info['allow_linkout'] = 0;
        }
        $this->set("exp_info", $exp_info);
        $this->set("exp_id", $exp_id);
        $this->TrapidUtils->checkPageAccess($exp_info['title'], $exp_info["process_state"], $this->process_states["default"]);

        // Check GO validity.
        // $go			= mysql_real_escape_string(str_replace("-",":",$go_web));
        $go = str_replace("-", ":", $go_web); // No need to check (find)?
        $go_information = $this->ExtendedGo->find("first", array("conditions" => array("name" => $go)));
        $num_transcripts = $this->TranscriptsGo->find("count", array(
            "conditions" => array("experiment_id" => $exp_id, "type" => "go", "name" => $go)
        ));
        if (!$go_information || $num_transcripts == 0) {
            $this->redirect(array("controller" => "trapid", "action" => "experiment", $exp_id));
        }
        $this->set("go_info", $go_information["ExtendedGo"]);
        $this->set("num_transcripts", $num_transcripts);

        // TODO: These tables (incl. pagination) should be handled by jQuery DataTables.
        // For now keep relying on CakePHP
        // TODO: reread how the information is formatted/displayed and try to optimize (same for IPR)
        $transcripts_p = $this->paginate("TranscriptsGo", array("experiment_id" => $exp_id, "name" => $go, "type" => "go"));
        // $transcripts_p		= $this->TranscriptsGo->find("all", array("fields"=>array("transcript_id"), "conditions"=>array("experiment_id"=>$exp_id, "type"=>"go", "name"=>$go)));
        $transcript_ids = $this->TrapidUtils->reduceArray($transcripts_p, "TranscriptsGo", "transcript_id");
        $transcripts = $this->Transcripts->find("all", array(
            "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids)
        ));

        // Retrieve functional annotation for the table.
        // GO
        $transcripts_go = $this->TrapidUtils->indexArray(
            $this->TranscriptsGo->find("all", array(
                "conditions" => array(
                    "experiment_id" => $exp_id, "transcript_id" => $transcript_ids, "is_hidden" => "0", "type" => "go")
            )), "TranscriptsGo", "transcript_id", "name");
        $go_info = array();
        if (count($transcripts_go) != 0) {
            $go_ids = array_unique(call_user_func_array("array_merge", array_values($transcripts_go)));
            $go_info = $this->ExtendedGo->retrieveGoInformation($go_ids);
        }
        // IPR
        $transcripts_ipr = $this->TrapidUtils->indexArray(
            $this->TranscriptsInterpro->find("all", array(
                "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids, "type" => "ipr")
            )), "TranscriptsInterpro", "transcript_id", "name");
        $ipr_info = array();
        if (count($transcripts_ipr) != 0) {
            $ipr_ids = array_unique(call_user_func_array("array_merge", array_values($transcripts_ipr)));
            $ipr_info = $this->ProteinMotifs->retrieveInterproInformation($ipr_ids);
        }
        // KO
        $transcripts_ko = $this->TrapidUtils->indexArray(
            $this->TranscriptsKo->find("all", array(
                "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids, "type" => "ko")
            )), "TranscriptsKo", "transcript_id", "name");
        $ko_info = [];
        if (count($transcripts_ko) != 0) {
            $ko_ids = array_unique(call_user_func_array("array_merge", array_values($transcripts_ko)));
            $ko_info = $this->KoTerms->retrieveKoInformation($ko_ids);
        }

        // Retrieve subset/label information.
        $transcripts_labels = $this->TrapidUtils->indexArray(
            $this->TranscriptsLabels->find("all", array(
                "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids)
            )), "TranscriptsLabels", "transcript_id", "label");
        // Subsets - # transcripts information and tooltip (for subset creation form)
        $all_subsets = $this->TranscriptsLabels->getLabels($exp_id);
        $tooltip_text_subset_creation = $this->HelpTooltips->getTooltipText("transcript_table_subset_creation");
        $this->set("all_subsets", $all_subsets);
        $this->set("tooltip_text_subset_creation", $tooltip_text_subset_creation);

        $this->set("transcript_data", $transcripts);
        $this->set("transcripts_go", $transcripts_go);
        $this->set("transcripts_ipr", $transcripts_ipr);
        $this->set("transcripts_ko", $transcripts_ko);
        $this->set("transcripts_labels", $transcripts_labels);
        $this->set("go_info_transcripts", $go_info);
        $this->set("ipr_info_transcripts", $ipr_info);
        $this->set("ko_info_transcripts", $ko_info);
        $this->set('title_for_layout', $go . ' &middot; GO term');

    }


    function interpro($exp_id = null, $interpro = null) {
        // Check experiment.
        if (!$exp_id || !$interpro) {
            $this->redirect(array("controller" => "trapid", "action" => "experiments"));
        }
        parent::check_user_exp($exp_id);
        $exp_info = $this->Experiments->getDefaultInformation($exp_id);
        $this->set("exp_info", $exp_info);
        $this->set("exp_id", $exp_id);
        $this->TrapidUtils->checkPageAccess($exp_info['title'], $exp_info["process_state"], $this->process_states["default"]);

        // Check InterPro validity.
        $interpro_info = $this->ProteinMotifs->find("first", array(
            "conditions" => array("name" => $interpro, "type" => "interpro")
        ));
        $num_transcripts = $this->TranscriptsInterpro->find("count", array(
            "conditions" => array("experiment_id" => $exp_id, "type" => "ipr", "name" => $interpro)
        ));
        if (!$interpro_info || $num_transcripts == 0) {
            $this->redirect(array("controller" => "trapid", "action" => "experiment", $exp_id));
        }
        $this->set("interpro_info", $interpro_info['ProteinMotifs']);
        $this->set("num_transcripts", $num_transcripts);

        $transcripts_p = $this->paginate("TranscriptsInterpro", array("experiment_id" => $exp_id, "type" => "ipr", "name" => $interpro));
        // $transcripts_p	= $this->TranscriptsInterpro->find("all",array("fields"=>array("transcript_id"), "conditions"=>array("experiment_id"=>$exp_id, "type"=>"ipr", "name"=>$interpro)));
        $transcript_ids = $this->TrapidUtils->reduceArray($transcripts_p, "TranscriptsInterpro", "transcript_id");
        $transcripts = $this->Transcripts->find("all", array(
            "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids)
        ));

        // Retrieve functional annotation for the table.
        // GO
        $transcripts_go = $this->TrapidUtils->indexArray(
            $this->TranscriptsGo->find("all", array(
                "conditions" => array(
                    "experiment_id" => $exp_id, "transcript_id" => $transcript_ids, "is_hidden" => "0", "type" => "go")
            )), "TranscriptsGo", "transcript_id", "name");
        $go_info = array();
        if (count($transcripts_go) != 0) {
            $go_ids = array_unique(call_user_func_array("array_merge", array_values($transcripts_go)));
            $go_info = $this->ExtendedGo->retrieveGoInformation($go_ids);
        }
        // IPR
        $transcripts_ipr = $this->TrapidUtils->indexArray(
            $this->TranscriptsInterpro->find("all", array(
                "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids, "type" => "ipr")
            )), "TranscriptsInterpro", "transcript_id", "name");
        $ipr_info = array();
        if (count($transcripts_ipr) != 0) {
            $ipr_ids = array_unique(call_user_func_array("array_merge", array_values($transcripts_ipr)));
            $ipr_info = $this->ProteinMotifs->retrieveInterproInformation($ipr_ids);
        }
        // KO: a bit nonsensical since there are no case where we have both IPR and KO??
        $transcripts_ko = $this->TrapidUtils->indexArray(
            $this->TranscriptsKo->find("all", array(
                "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids, "type" => "ko")
            )), "TranscriptsKo", "transcript_id", "name");
        $ko_info = [];
        if (count($transcripts_ko) != 0) {
            $ko_ids = array_unique(call_user_func_array("array_merge", array_values($transcripts_ko)));
            $ko_info = $this->KoTerms->retrieveKoInformation($ko_ids);
        }

        // Retrieve subset/label information.
        $transcripts_labels = $this->TrapidUtils->indexArray(
            $this->TranscriptsLabels->find("all", array(
                "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids)
            )), "TranscriptsLabels", "transcript_id", "label");
        // Subsets - # transcripts information and tooltip (for subset creation form)
        $all_subsets = $this->TranscriptsLabels->getLabels($exp_id);
        $tooltip_text_subset_creation = $this->HelpTooltips->getTooltipText("transcript_table_subset_creation");
        $this->set("all_subsets", $all_subsets);
        $this->set("tooltip_text_subset_creation", $tooltip_text_subset_creation);

        $this->set("transcript_data", $transcripts);
        $this->set("transcripts_go", $transcripts_go);
        $this->set("transcripts_ipr", $transcripts_ipr);
        $this->set("transcripts_ko", $transcripts_ko);
        $this->set("transcripts_labels", $transcripts_labels);
        $this->set("go_info_transcripts", $go_info);
        $this->set("ipr_info_transcripts", $ipr_info);
        $this->set("ko_info_transcripts", $ko_info);
        $this->set('title_for_layout', $interpro . ' &middot; Protein domain');
    }


    function assoc_gf($exp_id = null, $type = null, $identifier = null) {
        $max_transcripts = 5000;  // The maximum number of transcripts for which this page is displayed
        $valid_types = ["go", "interpro", "ko"];

        if (!$exp_id) {
            $this->redirect(array("controller" => "trapid", "action" => "experiments"));
        }
        parent::check_user_exp($exp_id);
        $exp_info = $this->Experiments->getDefaultInformation($exp_id);

        // Disable linkout if we use eggnog (they do not have dedicated pages to functional annotations).
        // Not clean but will do for the workshop
        // TODO: update with updated method in `Experiments` model
        if (strpos($exp_info['used_plaza_database'], "eggnog") !== false) {
            $exp_info['allow_linkout'] = 0;
        }

        $this->set("exp_info", $exp_info);
        $this->set("exp_id", $exp_id);
        if (!$type || !$identifier || !in_array($type, $valid_types)) {
            $this->redirect(array("controller" => "trapid", "action" => "experiment", $exp_id));
        }
        $gene_families = array();


        if ($type == "go") {
            // $go	= str_replace("-",":",mysql_real_escape_string($identifier));
            $go = str_replace("-", ":", $identifier);
            // Find whether any genes are associated (this also validates the GO term itself).
            $num_transcripts = $this->TranscriptsGo->find("count", array(
                "conditions" => array("experiment_id" => $exp_id, "type" => "go", "name" => $go)
            ));
            if ($num_transcripts == 0) {
                $this->redirect(array("controller" => "trapid", "action" => "experiment", $exp_id));
            }
            $go_information = $this->ExtendedGo->find("first", array("conditions" => array("name" => $go, "type" => "go")));
            $this->set("description", $go_information["ExtendedGo"]["desc"]);
            $this->set("go_category", $go_information["ExtendedGo"]["info"]);
            $this->set("type", "go");
            $this->set("go", $go);
            $this->set("num_transcripts", $num_transcripts);
            if ($num_transcripts > $max_transcripts) {
                $this->set("error", "Unable to find associated gene families for GO terms with more than " .
                    $max_transcripts . " associated transcripts");
                return;
            }
            $transcripts_p = $this->TranscriptsGo->find("all", array("fields" => array("transcript_id"),
                "conditions" => array("experiment_id" => $exp_id, "type" => "go", "name" => $go)));
            $transcript_ids = $this->TrapidUtils->reduceArray($transcripts_p, "TranscriptsGo", "transcript_id");
            $gene_families = $this->Transcripts->findAssociatedGf($exp_id, $transcript_ids);
            $this->set("gene_families", $gene_families);
        }
        else if ($type == "interpro") {
            $interpro = $identifier;
            //find whether any genes are associated (this also validates the interpro identifier itself).
            $num_transcripts = $this->TranscriptsInterpro->find("count", array(
                "conditions" => array("experiment_id" => $exp_id, "type" => "ipr", "name" => $interpro)
            ));
            if ($num_transcripts == 0) {
                $this->redirect(array("controller" => "trapid", "action" => "experiment", $exp_id));
            }
            // Note: `ipr`/`interpro` = not consistent???
            $interpro_information = $this->ProteinMotifs->find("first", array(
                "conditions" => array("name" => $interpro, "type" => "interpro")
            ));
            $this->set("description", $interpro_information["ProteinMotifs"]["desc"]);
            $this->set("type", "interpro");
            $this->set("interpro", $interpro);
            $this->set("num_transcripts", $num_transcripts);
            if ($num_transcripts > $max_transcripts) {
                $this->set("error", "Unable to find associated gene families for InterPro domains with more than " .
                    $max_transcripts . " associated transcripts");
                return;
            }
            $transcripts_p = $this->TranscriptsInterpro->find("all", array("fields" => array("transcript_id"),
                "conditions" => array("experiment_id" => $exp_id, "type" => "ipr", "name" => $interpro)));
            $transcript_ids = $this->TrapidUtils->reduceArray($transcripts_p, "TranscriptsInterpro", "transcript_id");
            $gene_families = $this->Transcripts->findAssociatedGf($exp_id, $transcript_ids);
            $this->set("gene_families", $gene_families);
        }
        else if ($type == "ko") {
            $ko = $identifier;
            // Find whether any transcripts are associated (also validates the KO term itself).
            $num_transcripts = $this->TranscriptsKo->find("count", array(
                "conditions" => array("experiment_id" => $exp_id, "type" => "ko", "name" => $ko)
            ));
            if ($num_transcripts == 0) {
                $this->redirect(array("controller" => "trapid", "action" => "experiment", $exp_id));
            }
            $ko_information = $this->KoTerms->find("first", array("conditions" => array("name" => $ko, "type" => "ko")));
            $this->set("description", $ko_information["KoTerms"]["desc"]);
            $this->set("type", "ko");
            $this->set("ko", $ko);
            $this->set("num_transcripts", $num_transcripts);
            if ($num_transcripts > $max_transcripts) {
                $this->set("error", "Unable to find associated gene families for KO terms with more than " .
                    $max_transcripts . " associated transcripts");
                return;
            }
            $transcripts_p = $this->TranscriptsKo->find("all", array("fields" => array("transcript_id"),
                "conditions" => array("experiment_id" => $exp_id, "type" => "ko", "name" => $ko)));
            $transcript_ids = $this->TrapidUtils->reduceArray($transcripts_p, "TranscriptsKo", "transcript_id");
            $gene_families = $this->Transcripts->findAssociatedGf($exp_id, $transcript_ids);
            $this->set("gene_families", $gene_families);
        }

        // More functional annotation per gene family.
        $extra_annot_go = array();
        $extra_annot_ipr = array();
        $extra_annot_ko = array();
        $first_transcripts = array();
        $go_descriptions = array();
        $ipr_descriptions = array();
        $ko_descriptions = array();
        $assoc_transcripts = $this->Transcripts->find("all", array(
            "conditions" => array("experiment_id" => $exp_id, "gf_id" => array_keys($gene_families)),
            "fields" => array("transcript_id", "gf_id")
        ));
        foreach ($assoc_transcripts as $t) {
            $trid = $t['Transcripts']['transcript_id'];
            $gf = $t['Transcripts']['gf_id'];
            if (!array_key_exists($gf, $first_transcripts)) {
                $first_transcripts[$gf] = $trid;
                $trid_go = $this->TranscriptsGo->find("all", array(
                    "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $trid, "type" => "go")
                ));
                $trid_ipr = $this->TranscriptsInterpro->find("all", array(
                    "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $trid, "type" => "ipr")
                ));
                $trid_ko = $this->TranscriptsKo->find("all", array(
                    "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $trid, "type" => "ko")
                ));
                $extra_annot_go[$gf] = array();
                $extra_annot_ipr[$gf] = array();
                $extra_annot_ko[$gf] = array();
                foreach ($trid_go as $tg) {
                    $extra_annot_go[$gf][] = $tg['TranscriptsGo']['name'];
                    $go_descriptions[] = $tg['TranscriptsGo']['name'];
                }
                foreach ($trid_ipr as $ti) {
                    $extra_annot_ipr[$gf][] = $ti['TranscriptsInterpro']['name'];
                    $ipr_descriptions[] = $ti['TranscriptsInterpro']['name'];
                }
                foreach ($trid_ko as $tk) {
                    $extra_annot_ko[$gf][] = $tk['TranscriptsKo']['name'];
                    $ko_descriptions[] = $tk['TranscriptsKo']['name'];
                }
            }
        }
        $go_descriptions = $this->ExtendedGo->find("all", array(
            "conditions" => array("name" => array_unique($go_descriptions), "type" => "go")
        ));
        $ipr_descriptions = $this->ProteinMotifs->find("all", array(
            "conditions" => array("name" => array_unique($ipr_descriptions), "type" => "interpro")
        ));
        $ko_descriptions = $this->KoTerms->find("all", array(
            "conditions" => array("name" => array_unique($ko_descriptions), "type" => "ko")
        ));
        $go_categories = $this->TrapidUtils->indexArraySimple($go_descriptions, "ExtendedGo", "name", "info");
        $go_descriptions = $this->TrapidUtils->indexArraySimple($go_descriptions, "ExtendedGo", "name", "desc");
        $ipr_descriptions = $this->TrapidUtils->indexArraySimple($ipr_descriptions, "ProteinMotifs", "name", "desc");
        $ko_descriptions = $this->TrapidUtils->indexArraySimple($ko_descriptions, "KoTerms", "name", "desc");

        $this->set("extra_annot_go", $extra_annot_go);
        $this->set("extra_annot_ipr", $extra_annot_ipr);
        $this->set("extra_annot_ko", $extra_annot_ko);
        $this->set("go_descriptions", $go_descriptions);
        $this->set("go_categories", $go_categories);
        $this->set("ipr_descriptions", $ipr_descriptions);
        $this->set("ko_descriptions", $ko_descriptions);
        $this->set('title_for_layout', str_replace("-", ":", $identifier) . ' &middot; Associated gene families');
    }


    function ko($exp_id = null, $ko = null) {
        // Check experiment access.
        if (!$exp_id || !$ko) {
            $this->redirect(array("controller" => "trapid", "action" => "experiments"));
        }
        parent::check_user_exp($exp_id);
        // Get experiment information.
        $exp_info = $this->Experiments->getDefaultInformation($exp_id);
        $this->set("exp_info", $exp_info);
        $this->set("exp_id", $exp_id);
        $this->TrapidUtils->checkPageAccess($exp_info['title'], $exp_info["process_state"], $this->process_states["default"]);

        // Check validity of supplied KO term (i.e. does it exist, is there any transcript associated to it).
        $ko_info = $this->KoTerms->find("first", array("conditions" => array("name" => $ko, "type" => "ko")));
        $num_transcripts = $this->TranscriptsKo->find("count", array(
            "conditions" => array("experiment_id" => $exp_id, "type" => "ko", "name" => $ko)
        ));
        if (!$ko_info || $num_transcripts == 0) {
            $this->redirect(array("controller" => "trapid", "action" => "experiment", $exp_id));
        }
        $this->set("ko_info", $ko_info['KoTerms']);
        $this->set("num_transcripts", $num_transcripts);

        // Get (paginated) transcripts annotated with the same KO term.
        $transcripts_p = $this->paginate("TranscriptsKo", array("experiment_id" => $exp_id, "type" => "ko", "name" => $ko));
        // $transcripts_p = $this->TranscriptsKo->find("all",array("fields"=>array("transcript_id"), "conditions"=>array("experiment_id"=>$exp_id, "type"=>"ko", "name"=>$ko)));
        $transcript_ids = $this->TrapidUtils->reduceArray($transcripts_p, "TranscriptsKo", "transcript_id");
        $transcripts = $this->Transcripts->find("all", array(
            "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids)
        ));

        // Fetch associated data (functional annotation, subsets) to populate the table
        // GO
        $transcripts_go = $this->TrapidUtils->indexArray(
            $this->TranscriptsGo->find("all", array(
                "conditions" => array(
                    "experiment_id" => $exp_id, "transcript_id" => $transcript_ids, "is_hidden" => "0", "type" => "go")
            )), "TranscriptsGo", "transcript_id", "name");
        $go_info = array();
        if (count($transcripts_go) != 0) {
            $go_ids = array_unique(call_user_func_array("array_merge", array_values($transcripts_go)));
            $go_info = $this->ExtendedGo->retrieveGoInformation($go_ids);
        }
        // IPR
        $transcripts_ipr = $this->TrapidUtils->indexArray(
            $this->TranscriptsInterpro->find("all", array(
                "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids, "type" => "ipr")
            )), "TranscriptsInterpro", "transcript_id", "name");
        $ipr_info = array();
        if (count($transcripts_ipr) != 0) {
            $ipr_ids = array_unique(call_user_func_array("array_merge", array_values($transcripts_ipr)));
            $ipr_info = $this->ProteinMotifs->retrieveInterproInformation($ipr_ids);
        }
        // KO
        $transcripts_ko = $this->TrapidUtils->indexArray(
            $this->TranscriptsKo->find("all", array(
                "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids, "type" => "ko")
            )), "TranscriptsKo", "transcript_id", "name");
        $ko_info = [];
        if (count($transcripts_ko) != 0) {
            $ko_ids = array_unique(call_user_func_array("array_merge", array_values($transcripts_ko)));
            $ko_info = $this->KoTerms->retrieveKoInformation($ko_ids);
        }
        // Subset/label information.
        $transcripts_labels = $this->TrapidUtils->indexArray(
            $this->TranscriptsLabels->find("all", array(
                "conditions" => array("experiment_id" => $exp_id, "transcript_id" => $transcript_ids)
            )), "TranscriptsLabels", "transcript_id", "label");
        // Subsets - # transcripts information and tooltip (for subset creation form)
        $all_subsets = $this->TranscriptsLabels->getLabels($exp_id);
        $tooltip_text_subset_creation = $this->HelpTooltips->getTooltipText("transcript_table_subset_creation");
        $this->set("all_subsets", $all_subsets);
        $this->set("tooltip_text_subset_creation", $tooltip_text_subset_creation);

        $this->set("transcript_data", $transcripts);
        $this->set("transcripts_go", $transcripts_go);
        $this->set("transcripts_ipr", $transcripts_ipr);
        $this->set("transcripts_ko", $transcripts_ko);
        $this->set("transcripts_labels", $transcripts_labels);
        $this->set("go_info_transcripts", $go_info);
        $this->set("ipr_info_transcripts", $ipr_info);
        $this->set("ko_info_transcripts", $ko_info);
        $this->set('title_for_layout', $ko . ' &middot; KO term');
    }


    /*
      * Cookie setup:
      * The entire TRAPID website is based on user-defined data sets, and as such a method for
      * account handling and user identification is required.
      *
      * The 'beforeFilter' method is executed BEFORE each method, and as such ensures that the necessary
      * identification through cookies is done.
      */
    function beforeFilter() {
        parent::beforeFilter();
    }
}