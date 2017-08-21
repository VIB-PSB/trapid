<?php

class DocumentationController extends AppController{
  var $name		= "Documentation";
  var $helpers		= array("Html","Form","Javascript","Ajax");
  var $uses		= array();
  var $layout = "external";  // Layout for external pages (i.e. not in experiment)

  function index(){
      $this->pageTitle = 'Documentation index';
  }


  function about(){
      $this->pageTitle = 'About';
  }


  /* Trying to separate 'about' and 'contact' pages! */
  function contact(){
      $this->pageTitle = 'Contact us';
  }


  function quickstart(){
      $this->pageTitle = 'Quick start';
  }


  function faq(){
      $this->pageTitle = 'Frequently asked questions';
  }


  function general(){
      $this->pageTitle = 'General documentation';
  }


  function tutorial(){
    // Configure::write("debug",2);
      $this->pageTitle = 'TRAPID tutorial';
  }


}
?>
