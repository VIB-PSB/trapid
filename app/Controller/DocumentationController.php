<?php

class DocumentationController extends AppController {
    var $layout = "external";  // Layout for external pages (i.e. not in experiment)
    var $name = "Documentation";
    var $uses = array("Configuration", "DataSources");

    function index() {
        $this->set("active_header_item", "Documentation");
        $this->set("title_for_layout", "Documentation index");
    }


    function about() {
        $this->set("active_header_item", "About");
        $this->set("title_for_layout", "About");
    }


    /* Trying to separate 'about' and 'contact' pages! */
    function contact() {
        $this->set("active_header_item", "Contact");
        $this->set("title_for_layout", "Contact us");
    }


    function faq() {
        $max_user_experiments = MAX_USER_EXPERIMENTS;
        $this->set("max_user_experiments", $max_user_experiments);
        $this->set("active_header_item", "Documentation");
        $this->set("title_for_layout", "Frequently Asked Questions");
    }


    function general() {
        // Retrieve export file examples from `configuration` table.
        $export_data = $this->Configuration->find("all", array('conditions' => array('method' => 'doc_export_files', 'attr' => 'file_content'), 'fields' => array('key', 'value')));
        $export_examples = array();
        foreach ($export_data as $ed) {
            $export_type = $ed['Configuration']['key'];
            $export_content = $ed['Configuration']['value'];
            $export_examples[$export_type] = $export_content;
        }

        $this->set("export_examples", $export_examples);
        $this->set("active_header_item", "Documentation");
        $this->set("title_for_layout", "General documentation");
    }


    function tutorial() {
        $this->set("active_header_item", "Documentation");
        $this->set("title_for_layout", "TRAPID tutorial");
    }


    function tools_parameters() {
        // Get reference db data
        $ref_db_data_raw = $this->DataSources->find("all", array('conditions' => array("restrict_to" => NULL)));
        $ref_db_data = array();
        foreach ($ref_db_data_raw as $ref_db) {
            $ref_db_data[$ref_db['DataSources']['db_name']] = array("url" => $ref_db['DataSources']['URL'], "name" => $ref_db['DataSources']['name']);
        }
        // Get tools' data
        $tools_parameters_data = $this->Configuration->getDocToolsParameters();

        $this->set("title_for_layout", "Tools & parameters");
        $this->set("ref_db_data", $ref_db_data);
        $this->set("active_header_item", "Documentation");
        $this->set("tools_parameters_data", $tools_parameters_data);
    }


}
