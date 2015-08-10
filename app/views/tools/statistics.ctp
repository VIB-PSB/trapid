<?php
	function perc($num,$total,$nr,$textmode=true){
		$perc	= round(100*$num/$total,$nr);
		$res	= "";
		if($textmode){$res = " (".$perc."%)";}
		else{$res = $perc;}
		//$res	= " (".round(100*$num/$total,$nr)."%)";
		return $res;
	}	
	function gradDiv($perc,$text){
		$res	= "";
		if($perc==0){
			$res	= "<div style='width:300px;margin-bottom:2px;border:1px solid rgb(180,180,180);'>".$text."</div>";
		}
		else{
			$css1	= "background: linear-gradient(left,rgb(180,180,180) ".$perc."%,rgb(245,245,245) ".$perc."%); ";
			$css2	= "background: -o-linear-gradient(left,rgb(180,180,180) ".$perc."%,rgb(245,245,245) ".$perc."%); ";
			$css3	= "background: -moz-linear-gradient(left,rgb(180,180,180) ".$perc."%,rgb(245,245,245) ".$perc."%); ";
			$css4	= "background: -webkit-linear-gradient(left,rgb(180,180,180) ".$perc."%,rgb(245,245,245) ".$perc."%); ";
			$css5	= "background: -ms-linear-gradient(left,rgb(180,180,180) ".$perc."%,rgb(245,245,245) ".$perc."%); ";
			$css6	= "background: -webkit-gradient(linear,left top,right top,color-stop(".($perc/100).", rgb(180,180,180)),color-stop(".($perc/100).", rgb(245,245,245)));";		
			$res	= "<div style='width:300px;".$css1.$css2.$css3.$css4.$css5.$css6."border:1px solid rgb(180,180,180);margin-bottom:2px;'>".$text."</div>";
		}
		return $res;
	}

?>
<?php if(!isset($pdf_view)) : ?>
<div>
<h2>Statistics</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	<h3>Transcript information</h3>
	<div class="subdiv">
		<dl class="standard extra">
			<dt>#Transcripts</dt>
			<dd><?php echo $num_transcripts; ?></dd>
			<dt>Average sequence length</dt>
			<dd><?php echo $seq_stats["transcript"];?></dd>
			<dt>Average ORF length</dt>
			<dd><?php echo $seq_stats["orf"];?></dd>
			<dt>#ORFs with a start codon</dt>
			<dd><?php echo gradDiv(perc($num_start_codons,$num_transcripts,0,false),$num_start_codons.perc($num_start_codons,$num_transcripts,1));?></dd>
			<dt>#ORFs with a stop codon</dt>
			<dd><?php echo gradDiv(perc($num_stop_codons,$num_transcripts,0,false),$num_stop_codons.perc($num_stop_codons,$num_transcripts,1));?></dd>				
		</dl>
	</div>

	<h3>Frameshift information</h3>
	<div class="subdiv">
		<dl class="standard extra">
			<dt>#Transcripts with putative frameshift</dt>
			<dd><?php echo gradDiv(perc($num_putative_fs,$num_transcripts,0,false),$num_putative_fs.perc($num_putative_fs,$num_transcripts,1));?></dd>
			<dt>#Transcripts with corrected frameshift</dt>			
			<dd><?php echo gradDiv(perc($num_correct_fs,$num_transcripts,0,false),$num_correct_fs.perc($num_correct_fs,$num_transcripts,1));?></dd>
		</dl>
	</div>

	<h3>Meta annotation information</h3>
	<div class="subdiv">
		<dl class="standard extra">	
			<dt>#Meta annotation full-length</dt>
			<dd><?php echo gradDiv(perc($meta_annot_fulllength,$num_transcripts,0,false),$meta_annot_fulllength.perc($meta_annot_fulllength,$num_transcripts,1));?></dd>
			<dt>#Meta annotation quasi full-length</dt>
			<dd><?php echo gradDiv(perc($meta_annot_quasi,$num_transcripts,0,false),$meta_annot_quasi.perc($meta_annot_quasi,$num_transcripts,1));?></dd>			
			<dt>#Meta annotation partial</dt>
			<dd><?php echo gradDiv(perc($meta_annot_partial,$num_transcripts,0,false),$meta_annot_partial.perc($meta_annot_partial,$num_transcripts,1));?></dd>
			<dt>#Meta annotation no information</dt>
			<dd><?php echo gradDiv(perc($meta_annot_noinfo,$num_transcripts,0,false),$meta_annot_noinfo.perc($meta_annot_noinfo,$num_transcripts,1));?></dd>	
		</dl>
	</div>	
	
	<h3>Similarity search information</h3>
	<div class="subdiv">
		Best similarity search hit for each transcript.
		<dl class="standard extra">
		<?php
			$split	= explode(";",$exp_info['hit_results']);
			$tmp	= array();
			$sum	= 0;
			foreach($split as $s){$k=explode("=",$s);$tmp[$k[0]]=$k[1];$sum+=$k[1];}
			arsort($tmp);
			foreach($tmp as $k=>$v){
				echo "<dt>".$all_species[$k]."</dt>";			
				echo "<dd>".gradDiv(perc($v,$sum,0,false),$v.perc($v,$sum,1))."</dd>\n";
			}
			echo "<dt><u>Total</u></dt>\n";
			echo "<dd>".$sum."</dd>\n";
		?>		
		</dl>
	</div>


	<h3>Gene family information</h3>
	<div class="subdiv">
		<dl class="standard extra">
			<dt>#Gene families</dt>
			<dd><?php echo $num_gf;?></dd>
			<dt>#Transcripts in GF</dt>
			<dd><?php echo gradDiv(perc($num_transcript_gf,$num_transcripts,0,false),$num_transcript_gf.perc($num_transcript_gf,$num_transcripts,1));?></dd>
			<dt>Largest GF</dt>
			<dd><?php echo $html->link($biggest_gf['gf_id'],array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$biggest_gf['gf_id']))." (".$biggest_gf['num_transcripts']." transcripts)";?></dd>
			<dt>#single copy</dt>
			<dd><?php echo $single_copy;?></dd>	
		</dl>
	</div>

	<h3>Functional annotation information</h3>
	<div class="subdiv">
		<h4>Gene Ontology</h4>
		<dl class="standard extra">
			<dt>#GO terms</dt>
			<dd><?php echo $num_go;?></dd>
			<dt>#Transcripts with GO</dt>
			<dd><?php echo gradDiv(perc($num_transcript_go,$num_transcripts,0,false),$num_transcript_go.perc($num_transcript_go,$num_transcripts,1));?></dd>
		</dl>
		<h4>InterPro</h4>
		<dl class="standard extra">
			<dt>#InterPro domains</dt>
			<dd><?php echo $num_interpro;?></dd>
			<dt>#Transcripts with Protein Domain</dt>
			<dd><?php echo gradDiv(perc($num_transcript_interpro,$num_transcripts,0,false),$num_transcript_interpro.perc($num_transcript_interpro,$num_transcripts,1));?></dd>
		</dl>
	</div>

	<h3>Export</h3>
	<div class="subdiv">
		<br/>
		<?php
		echo $form->create("",array("url"=>array("controller"=>"tools","action"=>"statistics/".$exp_id),
				"type"=>"post"));		
		echo "<input type='hidden' name='export_type' value='pdf'/>\n";				
		echo "<input type='submit' value='PDF export' />\n";
		echo "</form>\n";
		?>	
	</div>	
</div>
</div>
<?php else: ?>
<?php
	$fpdf->setTitle("TRAPID analysis");
	$fpdf->AliasNbPages();
	$fpdf->AddPage();
	
	//ok, first display the standard information about the experiment.
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"General TRAPID experiment information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	$standard_info	= array("Title"=>"title","Description"=>"description","Creation date"=>"creation_date",
				"Last edit"=>"last_edit_date","Similarity search database"=>"used_blast_database",
				"Reference database"=>"used_plaza_database");
	foreach($standard_info as $k=>$v){
		$val	= $exp_info[$v];
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$val);	
		$fpdf->Ln();
	}
	$fpdf->Ln();
	$fpdf->Ln();
	
	//next, transcript counts
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Transcript information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	foreach($pdf_transcript_info as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v);	
		$fpdf->Ln();		
	}
	$fpdf->Ln();
	$fpdf->Ln();
	

	//next, frameshift information
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Frameshift information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	foreach($pdf_frameshift_info as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v);	
		$fpdf->Ln();		
	}
	$fpdf->Ln();
	$fpdf->Ln();


	//next, meta information
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Meta annotation information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	foreach($pdf_meta_info as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v);	
		$fpdf->Ln();		
	}
	$fpdf->Ln();
	$fpdf->Ln();

	//next, similarity search information
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Similarity search information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	$split	= explode(";",$exp_info['hit_results']);
	$tmp	= array();
	$sum	= 0;
	foreach($split as $s){$k=explode("=",$s);$tmp[$k[0]]=$k[1];$sum+=$k[1];}
	arsort($tmp);
	foreach($tmp as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$all_species[$k]);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v.perc($v,$sum,1));
		$fpdf->Ln();		
	}		
	$fpdf->Ln();
	$fpdf->Ln();


	//next, gene family information
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Gene family information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	foreach($pdf_gf_info as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v);	
		$fpdf->Ln();		
	}
	$fpdf->Ln();
	$fpdf->Ln();
	
	//next, gene family information
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Functional annotation information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	foreach($pdf_func_info as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v);	
		$fpdf->Ln();		
	}
	$fpdf->Ln();
	$fpdf->Ln();
	

	echo $fpdf->fpdfOutput();    
?>
<?php endif; ?>
