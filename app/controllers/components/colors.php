<?php
class ColorsComponent extends Object{

  function create_interpro_color($interpro_id){
    $num 	= substr($interpro_id,strlen($interpro_id)-6);
    $num	= $num/18000*5;
    $red	= $this->getRed($num);
    $blue	= $this->getBlue($num);
    $green 	= $this->getGreen($num);
    
    $shad	= 16;    
    $red	= $red+$shad; if($red>255){$red=255;}
    $blue	= $blue+$shad; if($blue>255){$blue=255;}
    $green 	= $green+$shad;	if($green>255){$green=255;}
    $color	= "#".dechex($red).dechex($green).dechex($blue);
    return $color;	
  }


  function create_interpro_colors($interpro_id){
    $num 	= substr($interpro_id,strlen($interpro_id)-6);
    $num	= $num/18000*5;
    $red	= $this->getRed($num);
    $blue	= $this->getBlue($num);
    $green 	= $this->getGreen($num);
    $color2	= "#".dechex($red).dechex($green).dechex($blue);
        
    $shad	= 16;    
    $red	= $red+$shad; if($red>255){$red=255;}
    $blue	= $blue+$shad; if($blue>255){$blue=255;}
    $green 	= $green+$shad;	if($green>255){$green=255;}
    $color1	= "#".dechex($red).dechex($green).dechex($blue);	

    $result	= array("color1"=>$color1,"color2"=>$color2);   
    return $result;
 
  }

  function getRed($num){
    return intval((cos($num)+1.0)*127.0);
  }
  function getGreen($num){
    return intval((sin($num*2.02151648)+1.0)*127.0);
  }
  function getBlue($num){
    return intval((sin($num/2.33524)+1.0)*100);
  }

}
?>