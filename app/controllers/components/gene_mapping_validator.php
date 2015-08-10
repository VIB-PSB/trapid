<?php
class GeneMappingValidatorComponent extends Object{
	
     var $controller = true;

	  
     function startup(&$controller){
       $this->controller = & $controller;
     } 	

	


    function checkSpecies($species=null){
      if(!$species){$this->redirect("/");}
      if(!($this->AnnotSource->isValidSpecies($species))){$this->redirect("/");}
      return true;
    }
    function checkType($type=null){
      if(!$type){$this->redirect("/");}
      if(!($type=="gf" || $type=="go" || $type=="interpro" || $type=="gene_type")){$this->redirect("/");}
      return true;
    }
    function checkId($type=null,$id=null){
      if(!$type || !$id){$this->redirect("/");}
      if($type=="gf" && !$this->GeneFamily->gfExists($id)){$this->redirect("/");}
      if($type=="go" && !$this->ExtendedGo->isValidGo($id)){$this->redirect("/");}
      if($type=="interpro" && !$this->ProteinMotifs->isValidMotif($id)){$this->redirect("/");}
      return true;
    }


}

?>