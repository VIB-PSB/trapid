<?php

/*
 * Controller class for all functionality related to transcript labels / subsets.
 */

class LabelsController extends AppController {
    var $components = ['Session', 'Statistics', 'TrapidUtils'];
    var $name = 'Labels';
    var $uses = [
        'AnnotSources',
        'Annotation',
        'Authentication',
        'Configuration',
        'ExperimentJobs',
        'ExperimentLog',
        'Experiments',
        'ExtendedGo',
        'GeneFamilies',
        'GfData',
        'GoParents',
        'HelpTooltips',
        'KoTerms',
        'ProteinMotifs',
        'Transcripts',
        'TranscriptsGo',
        'TranscriptsInterpro',
        'TranscriptsKo',
        'TranscriptsLabels'
    ];

    function view($exp_id = null, $label = null) {
        if (!$exp_id || !$label) {
            $this->redirect(['controller' => 'trapid', 'action' => 'experiments']);
        }
        parent::check_user_exp($exp_id);
        $exp_info = $this->Experiments->getDefaultInformation($exp_id);
        $this->set('exp_info', $exp_info);
        $this->set('exp_id', $exp_id);
        $label = filter_var($label, FILTER_SANITIZE_STRING);
        // Check whether there is at least one transcript associated with this label.
        $num_transcripts = $this->TranscriptsLabels->find('count', [
            'conditions' => ['experiment_id' => $exp_id, 'label' => $label]
        ]);
        // User got here by illegal means: show an error message.
        if ($num_transcripts == 0) {
            $this->set('error', "No transcripts are associated with label '" . $label . "' ");
            return;
        }
        $this->set('num_transcripts', $num_transcripts);
        $this->set('label', $label);

        $transcripts_p = $this->paginate('TranscriptsLabels', ['experiment_id' => $exp_id, 'label' => $label]);
        $transcript_ids = $this->TrapidUtils->reduceArray($transcripts_p, 'TranscriptsLabels', 'transcript_id');
        $transcripts = $this->Transcripts->find('all', [
            'conditions' => ['experiment_id' => $exp_id, 'transcript_id' => $transcript_ids]
        ]);

        // Retrieve functional annotation for the table
        // GO
        $transcripts_go = $this->TrapidUtils->indexArray(
            $this->TranscriptsGo->find('all', [
                'conditions' => [
                    'experiment_id' => $exp_id,
                    'transcript_id' => $transcript_ids,
                    'is_hidden' => '0',
                    'type' => 'go'
                ]
            ]),
            'TranscriptsGo',
            'transcript_id',
            'name'
        );
        $go_info = [];
        if (count($transcripts_go) != 0) {
            $go_ids = array_unique(call_user_func_array('array_merge', array_values($transcripts_go)));
            $go_info = $this->ExtendedGo->retrieveGoInformation($go_ids);
        }

        // IPR
        $transcripts_ipr = $this->TrapidUtils->indexArray(
            $this->TranscriptsInterpro->find('all', [
                'conditions' => ['experiment_id' => $exp_id, 'transcript_id' => $transcript_ids, 'type' => 'ipr']
            ]),
            'TranscriptsInterpro',
            'transcript_id',
            'name'
        );
        $ipr_info = [];
        if (count($transcripts_ipr) != 0) {
            $ipr_ids = array_unique(call_user_func_array('array_merge', array_values($transcripts_ipr)));
            $ipr_info = $this->ProteinMotifs->retrieveInterproInformation($ipr_ids);
        }

        // KO
        $transcripts_ko = $this->TrapidUtils->indexArray(
            $this->TranscriptsKo->find('all', [
                'conditions' => ['experiment_id' => $exp_id, 'transcript_id' => $transcript_ids, 'type' => 'ko']
            ]),
            'TranscriptsKo',
            'transcript_id',
            'name'
        );
        $ko_info = [];
        if (count($transcripts_ko) != 0) {
            $ko_ids = array_unique(call_user_func_array('array_merge', array_values($transcripts_ko)));
            $ko_info = $this->KoTerms->retrieveKoInformation($ko_ids);
        }

        // Retrieve subset/label information
        $transcripts_labels = $this->TrapidUtils->indexArray(
            $this->TranscriptsLabels->find('all', [
                'conditions' => ['experiment_id' => $exp_id, 'transcript_id' => $transcript_ids]
            ]),
            'TranscriptsLabels',
            'transcript_id',
            'label'
        );

        // Retrieve translation table descriptions
        $transl_table_data = $this->Configuration->find('all', [
            'conditions' => ['method' => 'transl_tables', 'attr' => 'desc'],
            'fields' => ['key', 'value']
        ]);
        $transl_table_descs = [];
        foreach ($transl_table_data as $tt) {
            $idx = $tt['Configuration']['key'];
            $desc = $tt['Configuration']['value'];
            $transl_table_descs[$idx] = $desc;
        }
        ksort($transl_table_descs);

        if ($this->Session->check('error')) {
            $this->set('error', $this->Session->read('error'));
            $this->Session->delete('error');
        }

        // Subsets - # transcripts information and tooltip (for subset creation form)
        $all_subsets = $this->TranscriptsLabels->getLabels($exp_id);
        $tooltip_text_subset_creation = $this->HelpTooltips->getTooltipText('transcript_table_subset_creation');
        $this->set('all_subsets', $all_subsets);
        $this->set('tooltip_text_subset_creation', $tooltip_text_subset_creation);

        $this->set('transcript_data', $transcripts);
        $this->set('transcripts_go', $transcripts_go);
        $this->set('transcripts_ipr', $transcripts_ipr);
        $this->set('transcripts_ko', $transcripts_ko);
        $this->set('transcripts_labels', $transcripts_labels);
        $this->set('go_info_transcripts', $go_info);
        $this->set('ipr_info_transcripts', $ipr_info);
        $this->set('ko_info_transcripts', $ko_info);
        $this->set('transl_table_descs', $transl_table_descs);
        $this->set('title_for_layout', $label . ' &middot; Subset');
    }

    function subset_overview($exp_id = null) {
        parent::check_user_exp($exp_id);
        $exp_info = $this->Experiments->getDefaultInformation($exp_id);
        $this->TrapidUtils->checkPageAccess(
            $exp_info['title'],
            $exp_info['process_state'],
            $this->process_states['default']
        );
        $this->set('exp_info', $exp_info);
        $this->set('exp_id', $exp_id);

        // Get an overview of the counts present in the database.
        $data_raw = $this->TranscriptsLabels->getDataTranscript2Labels($exp_id);
        $data_venn = $this->Statistics->makeVennOverview($data_raw);
        $this->set('data_venn', $data_venn);

        $this->set('active_sidebar_item', 'Browse subsets');
        $this->set('title_for_layout', 'Subsets overview');
    }

    // Delete label `$label_id` and associated data from experiment `$exp_id`.
    function delete_label($exp_id = null, $label_id = null) {
        $label_id = $this->TranscriptsLabels->getDataSource()->value($label_id, 'string');
        parent::check_user_exp($exp_id);
        // TODO: check if nothing is running before deleting labels?
        // TODO: replace raw queries
        $this->TranscriptsLabels->query(
            "DELETE FROM `transcripts_labels` WHERE `experiment_id`='" . $exp_id . "' AND `label` = " . $label_id . ';'
        );
        // Also delete all extra data using these labels...
        // Core GF completeness results
        $this->TranscriptsLabels->query(
            "DELETE FROM `completeness_results` WHERE `experiment_id`='" .
                $exp_id .
                "' AND `label` = " .
                $label_id .
                ';'
        );
        // Functional enrichment results
        $this->TranscriptsLabels->query(
            "DELETE FROM `functional_enrichments` WHERE `experiment_id`='" .
                $exp_id .
                "' AND `label` = " .
                $label_id .
                ';'
        );
        $this->redirect(['controller' => 'labels', 'action' => 'subset_overview', $exp_id]);
    }

    // Retranslate all sequences of subset `$label_id` from experiment `$experiment_id` using translation table
    // `$transl_table` (ORF prediction).
    function retranslate_sqces($exp_id = null, $label_id = null) {
        $this->autoRender = false;
        if ($this->request->is('post')) {
            // Check experiment
            if (!$exp_id || !$label_id) {
                $this->redirect(['controller' => 'trapid', 'action' => 'experiments']);
            }
            parent::check_user_exp($exp_id);
            // Check subset/label id
            $label_id = filter_var($label_id, FILTER_SANITIZE_STRING);
            $label_data = $this->TranscriptsLabels->find('all', [
                'fields' => 'DISTINCT label',
                'conditions' => ['experiment_id' => $exp_id]
            ]);
            $all_labels = array_map(function ($x) {
                return $x['TranscriptsLabels']['label'];
            }, $label_data);
            if (!in_array($label_id, $all_labels)) {
                // Maybe redirect somewhere else and display an error message?
                $this->redirect(['controller' => 'trapid', 'action' => 'experiments']);
            }
            // Check translation table
            $transl_table_data = $this->Configuration->find('all', [
                'conditions' => ['method' => 'transl_tables', 'attr' => 'desc'],
                'fields' => ['key']
            ]);
            $possible_transl_tables = array_map(function ($x) {
                return $x['Configuration']['key'];
            }, $transl_table_data);
            if (!isset($_POST['transl_table']) || !in_array($_POST['transl_table'], $possible_transl_tables)) {
                $this->Session->write(
                    'error',
                    'Problem with the selected genetic code/translation table, please try again.'
                );
                $this->redirect(['controller' => 'labels', 'action' => 'view', $exp_id, $label_id]);
            }

            // Everything should be ok, so create everything necessary to run retranslation job
            $transl_table = $_POST['transl_table'];
            $qsub_file = $this->TrapidUtils->create_qsub_script($exp_id);
            $shell_file = $this->TrapidUtils->create_shell_script_retranslate_subset($exp_id, $label_id, $transl_table);
            if ($shell_file == null || $qsub_file == null) {
                $this->Session->write('error', 'Problem during job submission, impossible to create program files.');
                $this->redirect(['controller' => 'labels', 'action' => 'view', $exp_id, $label_id]);
            }
            $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
            $base_name = 'retranslate_' . $label_id;
            $qsub_out = $tmp_dir . $base_name . '.out';
            $qsub_err = $tmp_dir . $base_name . '.err';
            if (file_exists($qsub_out)) {
                unlink($qsub_out);
            }
            if (file_exists($qsub_err)) {
                unlink($qsub_err);
            }

            $output = [];
            $command = "sh $qsub_file -pe serial 1 -q medium -o $qsub_out -e $qsub_err $shell_file";
            exec($command, $output);
            $job_id = $this->TrapidUtils->getClusterJobId($output);
            // Create new job in DB
            $this->ExperimentJobs->addJob($exp_id, $job_id, 'medium', 'retranslate_subset');
            // Update experiment log
            $this->ExperimentLog->addAction($exp_id, 'retranslate_subset_sequences', '');
            $this->ExperimentLog->addAction($exp_id, 'retranslate_subset_sequences', 'subset=' . $label_id, 1);
            $this->ExperimentLog->addAction(
                $exp_id,
                'retranslate_subset_sequences',
                'translation_table=' . $transl_table,
                1
            );
            // Redirect to experiments overview page -- Maybe redirect somewhere else?
            $this->redirect(['controller' => 'trapid', 'action' => 'experiments']);
        } else {
            return;
        }
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
