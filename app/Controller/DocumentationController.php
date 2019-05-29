<?php

class DocumentationController extends AppController{
  var $name		= "Documentation";
  var $helpers		= array("Html","Form"); // ,"Javascript","Ajax");
  var $uses		= array("Configuration", "DataSources");
  var $layout = "external";  // Layout for external pages (i.e. not in experiment)

  function index(){
      $this -> set('title_for_layout', 'Documentation index');
  }


  function about(){
      $this -> set('title_for_layout', 'About');
  }


  /* Trying to separate 'about' and 'contact' pages! */
  function contact(){
      $this -> set('title_for_layout', 'Contact us');
  }


  function quickstart(){
      $this -> set('title_for_layout', 'Quick start');
  }


  function faq(){
      $this -> set('title_for_layout', 'Frequently asked questions');
  }


  function general(){
      // Retrieve export file examples from `configuration` table.
      $export_data = $this->Configuration->find("all", array('conditions'=>array('method'=>'doc_export_files', 'attr'=>'file_content'), 'fields'=>array('key', 'value')));
      $export_examples = array();
      foreach($export_data as $ed){
          $export_type = $ed['Configuration']['key'];
          $export_content = $ed['Configuration']['value'];
          $export_examples[$export_type] = $export_content;
      }

      $this -> set("export_examples", $export_examples);
      $this -> set('title_for_layout', 'General documentation');
  }


  function tutorial(){
    // Configure::write("debug",2);
      $this -> set('title_for_layout', 'TRAPID tutorial');
  }


  function tools_parameters() {
      // Get reference db data
      $ref_db_data = $this->DataSources->find("all");
      // Get tools' data
      $tools_parameters_data = $this->Configuration->getDocToolsParameters();
      $this -> set('title_for_layout', 'Tools & parameters');
      $this -> set('ref_db_data', $ref_db_data);
      $this -> set('tools_parameters_data', $tools_parameters_data);
  }


}
?>
