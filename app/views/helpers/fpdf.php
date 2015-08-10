<?php 
vendor('fpdf/fpdf');

if (!defined('PARAGRAPH_STRING')) define('PARAGRAPH_STRING', '~~~');

class fpdfHelper extends FPDF {
    
    var $helpers = array();	

    /**
    * Allows you to change the defaults set in the FPDF constructor
    *
    * @param string $orientation page orientation values: P, Portrait, L, or Landscape    (default is P)
    * @param string $unit values: pt (point 1/72 of an inch), mm, cm, in. Default is mm
    * @param string $format values: A3, A4, A5, Letter, Legal or a two element array with the width and height in unit given in $unit
    */
    function setup ($orientation='P',$unit='mm',$format='A4') {
        $this->FPDF($orientation, $unit, $format); 
    }
    
    /**
    * Allows you to control how the pdf is returned to the user, most of the time in CakePHP you probably want the string
    *
    * @param string $name name of the file.
    * @param string $destination where to send the document values: I, D, F, S
    * @return string if the $destination is S
    */
    function fpdfOutput ($name = 'page.pdf', $destination = 's') {
        // I: send the file inline to the browser. The plug-in is used if available. 
        //    The name given by name is used when one selects the "Save as" option on the link generating the PDF.
        // D: send to the browser and force a file download with the name given by name.
        // F: save to a local file with the name given by name.
        // S: return the document as a string. name is ignored.
        return $this->Output($name, $destination);
    }



    function Header()
    {
        //Logo      
        $this->Image(WWW_ROOT.DS.'img/vib_logo.gif',10,8,20);
        // you can use jpeg or pngs see the manual for fpdf for more info
        //Arial bold 15
        $this->SetFont('Arial','B',15);
        //Move to the right
        $this->Cell(70);
        //Title
        $this->Cell(60,10,$this->title,1,0,'C');
        //Line break
        $this->Ln(30);
    }

    //Page footer
    function Footer()
    {
        //Position at 1.5 cm from bottom
        $this->SetY(-15);
        //Arial italic 8
        $this->SetFont('Arial','I',8);
        //Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    } 	



    function basicOverview($header,$data){
	$this->SetFont('Arial','U',12);
    	$this->Cell(60,10,$header);
    	$this->Ln();	
    	$this->SetFont('Arial','',10);
	foreach($data as $k=>$v){
		$this->SetFont('','B');
		$this->Cell(60,5,$k);	
		$this->SetFont('','');
		$this->Cell(10);
		//if $v is not an array, we just print the data
		if(!is_array($v)){
		    $this->Cell(60,5,$v);		
		}
		else{
		  //html link, existing from raw data: 2 keys: url and text
		  if(array_key_exists("url",$v) && array_key_exists("text",$v)){
		    $final_url	= "http://".$_SERVER["SERVER_NAME"].$v['url'];	
		    $this->SetTextColor(0,0,255);
    		    $this->SetFont('','U');		    
		    $this->Cell(60,5,$v['text'],0,0,'',false,$final_url);
		    $this->SetFont('','');
    		    $this->SetTextColor(0);
		  }
		  else{
		    $this->Cell(60,5,"Problem sourcing HTML link");	
		  }
		}
		$this->Ln();
	}
	$this->Ln();
    }
       	

    function basicTable($header,$data)
    {
        //Header
        foreach($header as $col)
            $this->Cell(40,7,$col,1);
        $this->Ln();
        //Data
        foreach($data as $row) {
            foreach($row as $col) {
                $this->Cell(40,6,$col,1);
            }
            $this->Ln();
        }
    } 	


    function fancyTable($header,$table_header,$col_width,$table_data){
      //General header
      $this->SetFont('Arial','U',12);
      $this->Cell(60,10,$header);
      $this->Ln();	
      $this->SetFont('Arial','',8);

      $this->SetDrawColor(50,50,50);
      $this->SetLineWidth(.3);
      $this->SetFont('','B');
      $this->SetFillColor(230,230,230);

      //Table Header
      for($i=0;$i<count($table_header);$i++){
	$this->Cell($col_width[$i],5,$table_header[$i],1,0,'C',1);
      }
      $this->Ln();
      //Table content
      $this->SetFont('','');
      foreach($table_data as $row){
	for($i=0;$i<count($row);$i++){
	  $v	= $row[$i];
	  if(!is_array($v)){
	    $this->Cell($col_width[$i],5,$v,'LR',0,'L');	
	  }
	  else{
	   if(array_key_exists("url",$v) && array_key_exists("text",$v)){
	     $final_url	= "http://".$_SERVER["SERVER_NAME"].$v['url'];	
	     $this->SetTextColor(0,0,255);
    	     $this->SetFont('','U');		    
	     $this->Cell($col_width[$i],5,$v['text'],'LR',0,'L',false,$final_url);
	     $this->SetFont('','');
    	     $this->SetTextColor(0);
	   }
	   else{
	     $this->Cell($col_width[$i],5,"Data Problem",'LR',0,'L');	
	   }
	  }
	}	   	
	$this->Ln();
      }
      $this->Cell(array_sum($col_width),0,'','T');
    }

    /*

    function fancyTable($header, $colWidth, $data) {
	//Colors, line width and bold font
	$this->SetFillColor(255,0,0);
	$this->SetTextColor(255);
	$this->SetDrawColor(128,0,0);
	$this->SetLineWidth(.3);
	$this->SetFont('','B');
    
	//Header
	for($i=0;$i< count($header);$i++)
	    $this->Cell($colWidth[$i],7,$header[$i],1,0,'C',1);
	$this->Ln();
	//Color and font restoration
	$this->SetFillColor(224,235,255);
	$this->SetTextColor(0);
	$this->SetFont('');
	//Data
	$fill=0;
	foreach($data as $row) {
	    $i = 0;
	    foreach($row as $col) {
		$this->Cell($colWidth[$i++],6,$col,'LR',0,'L',$fill);
	    }
	    $this->Ln();
	    $fill=!$fill;
	    }
	$this->Cell(array_sum($colWidth),0,'','T');
    } 
    */

}
?> 