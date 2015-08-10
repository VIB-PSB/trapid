<?php
	//load the necessary javascript libraries			
			
	echo $javascript->link(array('ftiens4.js','ua.js'));
	//echo $javascript->link(array('prototype-1.7.0.0'));
?>
<div>
<h2>Create phylogenetic tree</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	
	<h3>Gene family</h3>
	<div class="subdiv">
		<dl class='standard'>
		<dt>Gene family</dt>
		<dd><?php echo $html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id));?></dd>
		<dt>#Transcripts</dt>
		<dd><?php echo $gf_info['GeneFamilies']['num_transcripts'];?></dd>
		</dl>
	</div>	

	<?php
	$hide_options	= false;
	if(isset($previous_result) && $previous_result==true){
		echo "<h3>Phylogenetic tree</h3>\n";
		echo "<div class='subdiv'>\n";		
		$data_url_tree	= $html->url(array("controller"=>"tools","action"=>"view_tree",$hashed_user_id,$exp_id,$gf_id),true);
		$data_url_tree_newick = $html->url(array("controller"=>"tools","action"=>"view_tree",$hashed_user_id,$exp_id,$gf_id,"newick"),true);
		$web_url_msa		= $html->url(array("controller"=>"tools","action"=>"create_msa",$exp_id,$gf_id),true);
		$web_url_msa_stripped	= $html->url(array("controller"=>"tools","action"=>"create_msa",$exp_id,$gf_id,"stripped"),true);

		$jar_file_location = $html->url("/files/forester/",true);	 	
		$config_url	   = TMP_WEB."experiment_data/".$exp_id."/atv_config.cfg";	
				

		if($stripped_msa_length==0){
		    echo "<br/><br/><span class='error'>The computed stripped multiple sequence alignment has a length of zero. As such, no phylogenetic tree is constructed.</span></div>\n";		    
		}
		else{
		    echo "<span>Download phylogenetic tree :</span>";
		    echo "<ul style='margin-left:30px;'>";
		    echo "<li>".$html->link("PhyloXML",$data_url_tree)."</li>";
		    echo "<li>".$html->link("Newick",$data_url_tree_newick)."</li>";
		    echo "</ul>";				
		    echo "<span>View multiple sequence alignment :</span>";
		    echo "<ul style='margin-left:30px;'>";
		    echo "<li>".$html->link("Full MSA",$web_url_msa)." (Length: ".$full_msa_length." amino acids)</li>";
		    echo "<li>".$html->link("Stripped MSA",$web_url_msa_stripped)." (Length: ".$stripped_msa_length." amino acids)</li>";
		    echo "</ul>";
		    echo "<br/>\n";

		    echo "<div style='margin-left:5px;margin-top:-10px;'>\n";	
		    echo "<p><br/><br/>\n";			
		    echo "<applet archive='forester.jar' code='org.forester.atv.ATVe.class' codebase='$jar_file_location' width='950' height='750' alt='Archeopteryx is not working on your system (requires at least Java 1.5)'>\n";
		    echo "<param name='url_of_tree_to_load' value='$data_url_tree'>\n";
		    echo "<param name='config_file' value='$config_url' >\n";
		    echo "<param name='base_linkout_url' value='".$html->url("/",true)."trapid/transcript/".$exp_id."/'  >\n";
		    echo "</applet>\n";	    
		    echo "</p></div>\n";   
	            if($include_subsets){
		    	echo $this->element("subset_colors");
		    }
		    else{
			//echo $this->element("meta_colors");
		    }
		    echo "</div>\n";
	        }
		$hide_options	= true;
	}
	else if((isset($previous_result) && $previous_result==false) || !isset($previous_result)){
	    if(isset($run_pipeline)){
		    echo "<h3>Phylogenetic tree</h3>\n";
		    echo "<div class='subdiv'>";
		    echo "A job for creating the phylogenetic tree has been added to the queue. <br/>";
		    echo "An email will be send when the job has finished.</br>";
		    echo "</div>\n";		
	    }
	}
	?>

	<?php
	$options_div_style		= null;
	if($hide_options){		
		$options_div_style	= " style='display:none;' ";
		echo "<div id='rerun_div'>";
		echo "<br/><br/><a href=\"javascript:void(0);\" onclick=\"javascript:$('options_div').style.display='block';$('rerun_div').style.display='none';return;\" >Create phylogenetic tree with different species</a><span style='margin-left:20px;'>(You may have to clear the Java cache to see the new result)</span>\n";
		echo "</div>";
	}		
	?>

	<div id="options_div" <?php echo $options_div_style;?> >
	<br/>	
	<?php
		echo $form->create("",array("action"=>"create_tree/".$exp_id."/".$gf_id,"type"=>"post"));
	?>		


	<?php if(!isset($run_pipeline)):?>

	<h3>Species/clade selection</h3>
	<div class="subdiv">
	     <div style="float:left;width:650px;">	     		 
		<script type="text/javascript">
		//<![CDATA[
		var USEFRAMES 		= 0;
		var WRAPTEXT 		= 1;
		var USETEXTLINKS  	= 0;
		var STARTALLOPEN  	= 0;
		var HIGHLIGHT 	  	= 0;
		var PRESERVESTATE 	= 0;
		var USEICONS	  	= 0;
		var BUILDALL		= 1;
		var ICONPATH 		= <?php echo "\"".$html->url("/",true)."img/tree_view/"."\""; ?>;

		function generateCheckBoxClade(parentfolderObject,itemLabel,checkBoxDOMId,checkBoxName,is_checked){
			var newObj;			
			newObj = insFld(parentfolderObject,gFld(itemLabel,""));
			var prep 	  = "<td valign=middle><input class='clade' type=checkbox id='"+checkBoxDOMId+"' name='"+checkBoxName+"' ";
			if(is_checked){prep+=" checked='checked' ";}
			prep		+= " onchange=\"javascript:changeClade('"+checkBoxDOMId+"');\"></td>";			
			newObj.prependHTML=prep;			
			return newObj;
		}

		//function to generate the checkboxes in the tree
		function generateCheckBoxLeaf(parentfolderObject, itemLabel, checkBoxDOMId,checkBoxName,is_checked,is_disabled) {	
  			var newObj;
			newObj = insDoc(parentfolderObject, gLnk("R", itemLabel,""));
			var prep	  = "<td valign=middle><input class='species' type=checkbox id='"+checkBoxDOMId+"' name='"+checkBoxName+"' ";
			if(is_checked){prep+=" checked='checked' ";}
			if(is_disabled){prep+=" disabled='disabled' ";}
			prep		+= " onchange=\"javascript:changeSpecies('"+checkBoxDOMId+"');\"></td>";			
			newObj.prependHTML=prep;
		}
	

		//here, we create the actual tree
		var foldersTree = gFld("", "");
		foldersTree.treeID = "cladeSpeciesSelectionTree";
		<?php		
		function plotTree($arr,$parent,$counter,$ast,$ac,$cok,$sel_clades,$sel_species,$phylo_profile){
		    foreach($arr as $k=>$v){
			if(!(is_numeric($k)  && is_numeric($v))){
				$aux	 = "aux".$counter++;
				$num_total_species_clade	= count($ac[$k]);
				$num_gf_species_clade		= count($cok[$k]);
				
				if($num_gf_species_clade==0){
				    $c_label = "<u>".$k."</u> (".$num_total_species_clade." species in total, <b>".$num_gf_species_clade."</b> in the gene family)";
				    echo "generateCheckBoxLeaf(".$parent.",\"".$c_label."\",\"".$k."\",\"".$k."\",0,1);\n";
				}
				else{									
				    $c_label = "<u>".$k."</u> (".$num_total_species_clade." species in total, ".$num_gf_species_clade." in the gene family)";			
    				    $selected_clade	= 0; if(array_key_exists($k,$sel_clades)){$selected_clade=1;}
				    echo "var ".$aux." = generateCheckBoxClade(".$parent.",\"".$c_label."\",\"".$k."\",\"".$k."\",".$selected_clade.");\n";
				    //now, based on content of '$v' variable, perform different functionality (subclades or leaves).
				    $c		= 0;	
				    $has_called_child	= false;		
				    foreach($v as $v1=>$v2){
					    //if both v1 and v2 are numeric -> leaf
					    if(is_numeric($v1) && is_numeric($v2)){    					
						    $spec_info	= $ast[$v2];
    						    //check on whether the species is present in the gene family, and whether the 
						    //species is selected by a previous run (or prev form submission).   
						    $num_genes	= 0;
						    if(array_key_exists($spec_info['species'],$phylo_profile)){
							$num_genes = $phylo_profile[$spec_info['species']];
						    }
    						    if($num_genes==0){
							echo "generateCheckBoxLeaf(".$aux.",\"".$spec_info['common_name']."\",\"taxid_".$v2."\",\"".$v2."\",0,1);\n";		
    						    }
    						    else{
    							$selected_species = 0; if(array_key_exists($v2,$sel_species)){$selected_species=1;}
							echo "generateCheckBoxLeaf(".$aux.",\"".$spec_info['common_name']."\",\"taxid_".$v2."\",\"".$v2."\",".$selected_species.",0);\n";	
    						    }
					    }		
					    else{
						    if(!$has_called_child){
							    $new_counter		= $counter."".$c++;
							    $has_called_child	= true;
							    plotTree($v,$aux,$new_counter,$ast,$ac,$cok,$sel_clades,$sel_species,$phylo_profile);
						    }

					    }
				    }
				}
			}

		    }
 		}

		$clades	= array_keys($available_clades);	
		sort($clades);		
		$clades_phylo			= array();
		$clades_ok_species		= array();	
		foreach($clades as $clade){	
			$ok_species		= array();
			$num_genes		= 0;	
			foreach($available_clades[$clade] as $tax_id){
				$species	= $available_species_tax[$tax_id]['species'];
				if(array_key_exists($species,$phylo_profile)){
					$num_genes+=$phylo_profile[$species];
					$ok_species[]	= $tax_id;
				}
			}
			$clades_phylo[$clade]		= $num_genes;
			$clades_ok_species[$clade]	= $ok_species;
		}
		$sel_clades	= $available_clades;
		$sel_species	= $available_species_tax;
		if(isset($selected_clades)){$sel_clades=$selected_clades;}
		if(isset($selected_species)){$sel_species=$selected_species;}

		plotTree($full_tree,"foldersTree",0,$available_species_tax,$available_clades,$clades_ok_species,$sel_clades,$sel_species,$phylo_profile);


		?>
		//]]>
		</script>

	
		<table style="background-color:transparent;border:0px;">
	   		<tr style="border:0px;"><td style="border:0px;">
				<a style="font-size:7pt;text-decoration:none;color:black;font-weight:normal;" href="http://www.treemenu.net/" target=_blank>Please select the species for which you want to include its associated genes.</a>
	   		</td></tr>
		</table>	

	
		<div class="TreeviewSpanArea">
			<script type="text/javascript">
			//<![CDATA[
			initializeDocument();
			//]]>
			</script>
	 		<noscript>Please enable Javascript in your browser</noscript>
 		</div>
	    </div> <!-- End float -->
	    <div style='float:left;width:300px;'>
		<?php if($exp_info['hit_results']):?>				
		<span style="margin-left:10px;">Species similarity hits</span>
		<div style="margin-top:5px;" id="species_simsearch_selection_div">
			<table cellpadding="0" cellspacing="0" style="width:290px;">
				<tr>					
					<th style="width:65%">Species</th>
					<th style="width:35%">Hit count (global)</th>
				</tr>
				<?php
					$hit_results	= explode(";",$exp_info['hit_results']);
					$tmp		= array();	
					$sum		= 0;		
					foreach($hit_results as $s){$k = explode("=",$s); $tmp[$k[0]]=$k[1]; $sum+=$k[1];}
					arsort($tmp);
					foreach($tmp as $k=>$v){
						echo "<tr>";					
						echo "<td>".$available_species_species[$k]['common_name']."</td>";
						$perc	= round(100*$v/$sum,0);
						$perc2	= $perc;
$css1	= "background: linear-gradient(left,rgb(180,180,180) ".$perc."%,rgb(245,245,245) ".$perc2."%); ";
$css2	= "background: -o-linear-gradient(left,rgb(180,180,180) ".$perc."%,rgb(245,245,245) ".$perc2."%); ";
$css3	= "background: -moz-linear-gradient(left,rgb(180,180,180) ".$perc."%,rgb(245,245,245) ".$perc2."%); ";
$css4	= "background: -webkit-linear-gradient(left,rgb(180,180,180) ".$perc."%,rgb(245,245,245) ".$perc2."%); ";
$css5	= "background: -ms-linear-gradient(left,rgb(180,180,180) ".$perc."%,rgb(245,245,245) ".$perc2."%); ";
$css6	= "background: -webkit-gradient(linear,left top,right top,color-stop(".($perc/100).", rgb(180,180,180)),color-stop(".($perc2/100).", rgb(245,245,245)));";
						$css	= $css1.$css2.$css3.$css4.$css5.$css6;
						echo "<td><div style='".$css."'>".$v." hits</div></td>";
						echo "</tr>\n";
					}
				?>
			</table>	
		</div>	
		<?php endif;?>
	    </div>  <!-- End float -->
	    <div style='clear:both;width:800px;'>&nbsp;</div>
	</div>
	
	<script type="text/javascript">
	    //<![CDATA[
	    <?php
		$total_selected_species	= 0;
		$total_selected_genes	= 0;
		$phylo_profile_tax	= array();
		foreach($available_species_tax as $txid=>$spec_info){
			$ng	= 0;
			if(array_key_exists($spec_info['species'],$phylo_profile)){
				$ng =  $phylo_profile[$spec_info['species']];
			}	
			$phylo_profile_tax[$txid] = $ng;
		}

		if(isset($selected_species)){
			$total_selected_species	= count($selected_species);
			foreach($selected_species as $sel){
				$total_selected_genes+=$phylo_profile[$available_species_tax[$sel]['species']];
			}
		}
		else{
			foreach($available_species_tax as $txid=>$spec_info){
				$ng	= 0;
				if(array_key_exists($spec_info['species'],$phylo_profile)){
					$ng =  $phylo_profile[$spec_info['species']];
				}
				if($ng!=0){
					$total_selected_species++;
					$total_selected_genes+=$ng;
				}
			}		
		}
	    ?>		
	    var total_selected_species	= <?php echo $total_selected_species;?>;
	    var total_selected_genes	= <?php echo $total_selected_genes;?>;

	    var clades_to_species	= <?php echo $javascript->object($clades_ok_species);?>;
	    var species_phylo		= <?php echo $javascript->object($phylo_profile_tax);?>;
	    var parent_child_clades	= <?php echo $javascript->object($parent_child_clades);?>;
	    var MAX_GENES		= <?php echo $MAX_GENES;?>;
		
			

	
			function changeClade(clade){
				var element	= document.getElementById(clade);			
				clades_to_species[clade].each(function(sp){	
					var sp_id		= "taxid_"+sp;		
					var sp_el		= $(sp_id);
					if(sp_el!=null){							
					    if(element.checked){	//add new genes and species from total count
						    if(sp_el.checked!=element.checked){
							    total_selected_species++;							
							    total_selected_genes+=species_phylo[sp];
						    }
					    }
					    else{	//remove genes and species from total count
						    if(sp_el.checked!=element.checked){
							    total_selected_species--;
							    total_selected_genes-=species_phylo[sp];	
						    }
					    }
    					    if(sp_el.disabled){}
    					    else{sp_el.checked	= element.checked;}
					}
				});
				//check or uncheck child-clades as well. This is purely for visualization purposes.
				parent_child_clades[clade].each(function(child_clade){
					try{
						var cc		= $(child_clade);
						if(cc!=null){
							if(cc.disabled){}
							else{cc.checked	= element.checked;}
						}
					}
					catch(exc){					
					}
				});
													
				updateCounts();	
			}			
	
			function changeSpecies(sp_id){
				var element	= document.getElementById(sp_id);			
				var sp		= sp_id.substr(6);				
				if(element.checked){
					total_selected_species++;
					total_selected_genes+=species_phylo[sp];	
				}				
				else{
					total_selected_species--;
					total_selected_genes-=species_phylo[sp];
				}
				updateCounts();
			}
		
		function updateCounts(){
			//alert(total_selected_genes);
			$("num_species").innerHTML 	= total_selected_species;
			$("num_genes").innerHTML	= total_selected_genes;
			if(total_selected_genes > MAX_GENES){
				$("status").innerHTML	= "<span class='error'>Error: too many genes selected (max: "+MAX_GENES+"). Deselect species to continue.</span>";
				$("submit_button").disabled	= "disabled";
			}
			else{
				$("status").innerHTML	= "OK";
				$("submit_button").disabled	= false;
			}
		}

	//]]>
	</script>	


	<h3>Transcript selection</h3>
	<div class="subdiv">	   
		<?php		
		$disabled	= null;
		if($num_partial_transcripts==0){$disabled=" disabled='disabled' ";}
		echo "<input type='checkbox' name='no_partial_transcripts' id='no_partial_transcripts' $disabled />\n";
		echo "<span style='margin-left:10px;'>Do not include partial transcripts (".$num_partial_transcripts." partial transcripts detected in this gene family)</span>\n";	
		echo "<br/>\n";
		echo "<input type='checkbox' name='single_transcript_selection' id='single_transcript_selection'/>\n";
		echo "<span style='margin-left:10px;'>Single transcript <u><b>exclusion</b></u></span>\n";		
		?>
		<div style="margin-top:10px;display:none;" id="transcript_select_div">					
			<table cellpadding="0" cellspacing="0" style="width:400px;">
				<tr>
					<th style="width:15%">Exclude</th>
					<th style="width:45%">Transcript</th>
					<th style="width:40%">Meta annotation</th>
				</tr>
				<?php
				foreach($gf_transcripts as $tr=>$met){
					echo "<tr>";
					echo "<td><input type='checkbox' name='exclude_".$tr."' id='exclude_".$tr."' /></td>";
					echo "<td>".$html->link($tr,array("controller"=>"trapid","action"=>"transcript",$exp_id,urlencode($tr)))."</td>";
					echo "<td>".$met."</td>";
					echo "</tr>";
				}	
				?>
			</table>	
		</div>
		<script type="text/javascript">
			//<![CDATA[
			var transcript_meta	= new Hash(<?php echo $javascript->object($gf_transcripts);?>);
			$("single_transcript_selection").observe("change",function(){
				if($("single_transcript_selection").checked){
					$("transcript_select_div").style.display="block";
				}
				else{
					transcript_meta.keys().each(function(tr){
						var cb	= "exclude_"+tr;							
						$(cb).checked = null;
					});
					$("transcript_select_div").style.display="none";
				}	
			});
			$("no_partial_transcripts").observe("change",function(){
				if($("no_partial_transcripts").checked){
					$("single_transcript_selection").checked = "checked";
					$("transcript_select_div").style.display="block";
					transcript_meta.keys().each(function(tr){
						var meta	= transcript_meta.get(tr);
						if(meta=="Partial"){
							var cb	= "exclude_"+tr;							
							$(cb).checked = "checked";
						}
					});					
				}
				else{
					//$("single_transcript_selection").checked = null;
					//$("transcript_select_div").style.display="none";
					transcript_meta.keys().each(function(tr){
						var meta	= transcript_meta.get(tr);
						if(meta=="Partial"){
							var cb	= "exclude_"+tr;							
							$(cb).checked = null;
						}
					});				
				}				
			});	
			//]]>
		</script>
	</div>


	<h3>Overview and Options</h3>
	<div class="subdiv">
		<dl class="standard">
		<dt>#Selected species</dt>
		<dd><span id='num_species'><?php echo $total_selected_species;?></span></dd>
		<dt>#Selected genes</dt>
		<dd><span id='num_genes'><?php echo $total_selected_genes; ?></span></dd>	
		<dt>Status</dt>
		<dd>
			<span id='status'>
			<?php 
			$no_sub	= false;
			if(isset($error)){
				echo "<span class='error'>".$error."</span>";
				$no_sub	= true;
			}
			else if($total_selected_genes>$MAX_GENES){
				echo "<span class='error'>Error: too many genes selected (max: ".$MAX_GENES."). Deselect species to continue.</span>";	
				$no_sub	= true;
			}
			else{
				echo "OK";
			}
			?>
			</span>
		</dd>
		<dt>Algorithm</dt>
		<dd>
			<select name="tree_program" style="width:150px;">
			<?php
			foreach($tree_programs as $k=>$v){
				$selected	= null;
				if(isset($tree_program) && $tree_program==$k){$selected = " selected='selected' ";}
				echo "<option value='$k' $selected>$v</option>\n";
			}
			?>
			</select>
		</dd>
		<dt>Editing</dt>
		<dd>
			<select name="editing_mode" style="width:150px;">
			<?php
			foreach($editing_modes as $k=>$v){
				$selected	= null;
				if(isset($editing_mode) && $editing_mode == $k){$selected = " selected='selected' ";}
				echo "<option value='$k' $selected>$v</option>\n";
			}
			?>
			</select>
		</dd>
		<dt>Bootstrapping</dt>
		<dd>
			<select name="bootstrap_mode" style="width:150px;">
			<?php
			foreach($bootstrap_modes as $k=>$v){
				$selected	= null;
				if(isset($bootstrap_mode) && $bootstrap_mode == $k){$selected = " selected='selected' ";}
				echo "<option value='$k' $selected>$v</option>\n";
			}
			?>
			</select>
		</dd>
		<!--<dt>Parameter optimization</dt>
		<dd>
			<select name="optimization_mode" style="width:150px;">
			<?php
			foreach($optimization_modes as $k=>$v){
				$selected	= null;
				if(isset($optimization_mode) && $optimization_mode == $k){$selected = " selected='selected' ";}
				echo "<option value='$k' $selected>$v</option>\n";	
			}
			?>
			</select>
		</dd>
		-->
		<dt>Extra</dt>
		<dd>
			<div>
			<?php
			$checked	= null;
			if(isset($include_subsets) && $include_subsets){$checked = " checked='checked' ";}
			echo "<input name='include_extra' $checked type='radio' value='subsets'/>\n";
			echo "<span style='margin-left:20px;'>Include subsets in tree</span>\n";
			echo "<br/>\n";
			$checked2	= " checked='checked' ";
			if($checked){$checked2 = null;}
			echo "<input name='include_extra' $checked2 type='radio' value='meta'/>\n";
			echo "<span style='margin-left:20px;'>Include meta-annotation in tree</span>\n";
			?>
			</div>
		</dd>
		</dl>
	</div>

	<div class="subdiv">
		<br/>
		<input type="submit" value="Create phylogenetic tree" id="submit_button" <?php if($no_sub){echo "disabled='disabled'";}?>/>
	</div>

	<?php endif;?>

	</form>

	</div>

</div>
</div>
