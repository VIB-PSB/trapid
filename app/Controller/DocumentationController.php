<?php

class DocumentationController extends AppController{
  var $name		= "Documentation";
  var $helpers		= array("Html","Form"); // ,"Javascript","Ajax");
  var $uses		= array();
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
      $title = "SEGPA";
      $this -> set('title_for_layout', 'Frequently asked questions');
      $this->set('title_for_layout', 'List User');
  }


  function general(){
      $this -> set('title_for_layout', 'General documentation');
  }


  function tutorial(){
    // Configure::write("debug",2);
      $this -> set('title_for_layout', 'TRAPID tutorial');
  }


}
?>
