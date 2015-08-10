<div>
<?php
//echo $javascript->link(array('prototype-1.7.0.0'));	
?>
<h2>Process transcripts</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>	
	
	<h3>Overview</h3>
	<div class="subdiv" style="width:700px;">
	The transcript pipeline of the PLAZA workbench can be used to analyze transcripts (provided by the user) of species not present in the PLAZA database. This is useful for e.g. transcriptome analyzes during specific conditions or for species for which no genome is present, only a transcriptome. Transcripts are initially associated with PLAZA gene families using a translational approach. Further analyzes are then done on a per-family basis. 
	</div>
	
	<h3>Options</h3>
	<div class="subdiv">
	<?php
	if(isset($error)){
	echo "<span class='error'>".$error."</span>\n";
	}
	?>	
		
	<?php
	echo $form->create("",array("url"=>array("controller"=>"trapid","action"=>"initial_processing",$exp_id),"type"=>"post"));
	?>
		<dl class="standard2">
			<dt>&nbsp;</dt>
			<dd><span>				
					Use <a href='http://www.ncbi.nlm.nih.gov/taxonomy'>NCBI Taxonomy</a> to find the closest relative species or best clade.
				</span>
			</dd>
			<dt>
				Similarity Search Database Type
			</dt>
			<dd>
				<select name="blast_db_type" id="blast_db_type" style="width:300px">
					<?php
					foreach($possible_db_types as $k=>$v){
						echo "<option value='".$k."'>".$v."</option>\n";
					}
					?>	
				</select>								
			</dd>	

			<dt>	
				Similarity Search Database
			</dt>
			<dd>
				<select name="blast_db" id="blast_db" style="width:300px;">
					<?php
					foreach($available_species as $sn=>$cn){					
						echo "<option value='".$sn."'>".$cn." </option>\n";	
					}
					?>				
				</select>
			</dd>

			<dt>
				Similarity Search E-value
			</dt>
			<dd>
				<select name="blast_evalue" id="blast_evalue" style="width:300px;">
					<?php					
					foreach($possible_evalues as $k=>$v){
						$sel=null;if($k=="10e-5"){$sel=" selected='selected'";}
						echo "<option value='".$k."' $sel>".$k."</option>\n";
					}
					?>
				</select>
			</dd>

			<dt>
				Gene Family type
			</dt>
			<dd>
				<select name="gf_type" id="gf_type" style="width:300px;">
					<?php
					foreach($possible_gf_types as $k=>$v){
						echo "<option value='".$k."' id='gf_type_".strtolower($k)."'>".$v."</option>\n";
					}
					?>				
				</select>
			</dd>
			<dt>
				Functional annotation
			</dt>
			<dd>
				<select name="functional_annotation" id="functional_annotation" style="width:300px;">
					<?php
					foreach($possible_func_annot as $k=>$v){
						echo "<option value='".$k."' >".$v."</option>\n";
					}
					?>
				</select>
			</dd>					
		</dl>
		<br/>
		<input type="submit" value="Start transcriptome pipeline" style="width:200px;" />	
	</form>
	</div>
</div>

<script type="text/javascript">
//<![CDATA[ 
	//alert("TEST");
	$("blast_db_type").observe("change",function(){
		var blast_db_type	= $("blast_db_type").options[$("blast_db_type").selectedIndex].value;	
		if(blast_db_type=="SINGLE_SPECIES"){setSingleSpeciesData();}
		else if(blast_db_type=="CLADE"){setCladeData();}
		else if(blast_db_type=="GF_REP"){setGfRepData();}
	});	


	function clearDBSelect(){
		var counter	= 0;
		while($("blast_db").options.length>0){
			counter++; if(counter>1000){alert("?");break;}
			$("blast_db").remove(0);
		}							
	}

	function clearGFSelect(){
		var counter	= 0;
		while($("gf_type").options.length>0){
			counter++; if(counter>1000){alert("?");break;}
			$("gf_type").remove(0);
		}
	}

	function createOption(value,text){
		var option	= document.createElement("option");
		option.text 	= text;
		option.value	= value;
		return option;
	}

	function setSingleSpeciesData(){
		<?php if(array_key_exists("SINGLE_SPECIES",$possible_db_types)) : ?>			
		clearDBSelect();
		clearGFSelect();		
		<?php
		//add species to database selection
		foreach($available_species as $sn=>$cn){		
			echo "$('blast_db').add(createOption('".$sn."','".$cn."'),null);\n";
		}
		//add all possible gf types (if applicable) to gf selection
		foreach($possible_gf_types as $k=>$v){
			echo "$('gf_type').add(createOption('".$k."','".$v."'),null);\n";		
		}
		?>
		<?php endif;?>
	}
	function setCladeData(){
		<?php if(array_key_exists("CLADE",$possible_db_types)) : ?>	
		clearDBSelect();
		clearGFSelect();	
		<?php
		//add clades to database selection
		foreach($clades_species as $clade=>$species){
			echo "$('blast_db').add(createOption('".$clade."','".$clade."'),null);\n";
		}
		//add only HOM to gf selection
		echo "$('gf_type').add(createOption('HOM','".$possible_gf_types['HOM']."'),null);\n";	
		?>
		<?php endif;?>
	}
	function setGfRepData(){
		<?php if(array_key_exists("GF_REP",$possible_db_types)):?>		
		clearDBSelect();
		clearGFSelect();	
		<?php
		//add the gf representatives to the database selection
		foreach($gf_representatives as $gf_rep=>$gf_rep2){
			echo "$('blast_db').add(createOption('".$gf_rep."','Gene family representatives'),null);\n";
		}
		//add only HOM to gf selection
		echo "$('gf_type').add(createOption('HOM','".$possible_gf_types['HOM']."'),null);\n";	
		?>
		<?php endif;?>					
	}

//]]>
</script>

</div>
