<?php
	//load the necessary javascript libraries			
			
	echo $javascript->link(array('ftiens4.js','ua.js'));
	//echo $javascript->link(array('prototype-1.7.0.0'));
?>

<div>
<h2>Create multiple sequence alignment</h2>
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
	
	//echo "previous result :".$previous_result." - ".$run_pipeline."<br/>";

	$hide_options	= false;
	
	$msa_title	= "Multiple sequence alignment";
	$msa_type	= "normal";
	if($stripped_msa){
		$msa_title 	= "Stripped multiple sequence alignment";
		$msa_type	= "stripped";
	}
	

	if(isset($previous_result) && $previous_result==true){
		$msa_url_1= $html->url(array("controller"=>"tools","action"=>"view_msa",$hashed_user_id,$exp_id,$gf_id,$msa_type),true);
		echo "<h3>".$msa_title."</h3>\n";
		echo "<div class='subdiv'>\n";
    		echo "<dl class='standard'>\n";
    		echo "<dt>View</dt>\n";
    		echo "<dd>".$html->link("View multiple sequence alignment","javascript:$('form_msa_norm').submit();")."</dd>\n";   
		echo "<dt>Download</dt>\n";
		echo "<dd>".$html->link("Download multiple sequence alignment",$msa_url_1)."</dd>";
    		echo "</dl>\n"; 

		echo "<form action='http://bioinformatics.psb.ugent.be/webtools/jalview/jalview.jnlp' id='form_msa_norm' method='post'>";
		echo "<input type='hidden' name='data' value='".$msa_url_1."' />";
	    	echo "</form>\n";			
	  
		echo "</div>\n";
		$hide_options	= true;
	}
	else if((isset($previous_result) && $previous_result==false) || !isset($previous_result)){	   
	    if(isset($run_pipeline)){
		echo "<h3>".$msa_title."</h3>\n";
	   	echo "<div id='msa_div'>";
		echo "A job for creating the MSA has been added to the queue. <br/>";
		echo "An email will be send when the job has finished.</br>";
		echo "</div>\n";
	    }

	    /*
	    if(isset($run_pipeline) && isset($job_id)){
		    echo "<h3>".$msa_title."</h3>\n";
		    echo "<div class='subdiv'>";
		    echo "<div id='msa_div'>";
		    echo "<div style='width:300px;min-height:200px;border:1px solid black;background-color:#F2F2F2'><br/><br/><br/><br/><br/><br/>";
		    echo "<center>";
		    echo $html->image('ajax-loader.gif');
		    echo "<br/><span>Loading...please wait</span><br/>\n";
		    echo "</center>";
		    echo "</div>";
		    echo "</div>";			
		    echo "</div>\n";
    		    $hide_options	= true;	
		    echo $javascript->codeBlock($ajax->remoteFunction(
			array('url'=>"/tools/load_msa/".$exp_id."/".$gf_id."/".$job_id."/",
			'update'=>'msa_div')));
	    }*/
	}
	?>

	<?php
	$options_div_style		= null;
	if($hide_options){		
		$options_div_style	= " style='display:none;' ";
		echo "<div id='rerun_div'>";
		echo "<br/><br/><a href=\"javascript:void(0);\" onclick=\"javascript:$('options_div').style.display='block';$('rerun_div').style.display='none';return;\" >Create multiple sequence alignment with different species</a>\n";
		echo "</div>";
	}		
	?>

	<div id="options_div" <?php echo $options_div_style;?> >
	<br/>	
	<?php
		echo $form->create("",array("action"=>"create_msa/".$exp_id."/".$gf_id,"type"=>"post"));
	?>	


	<?php if(!isset($run_pipeline)):?>

	<h3>Species/clade selection</h3>
	<div class="subdiv">
	 

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
				$("status").innerHTML	= "<span class='error'>Error: Too many genes selected (Max: "+MAX_GENES+")</span>";
				$("submit_button").disabled	= "disabled";
			}
			else{
				$("status").innerHTML	= "OK";
				$("submit_button").disabled	= false;
			}
		}

	//]]>
	</script>	
	
	
	<h3>Overview</h3>
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
				echo "<span class='error'>Error: Too many genes selected (Max: ".$MAX_GENES.")</span>";	
				$no_sub	= true;
			}
			else if(isset($run_pipeline)){
				$no_sub = true;
			}
			else{
				echo "OK";
			}
			?>
			</span>
		</dd>	
		</dl>
	</div>

	<div class="subdiv">
		<br/>
		<input type="submit" value="Create multiple sequence alignment" id="submit_button" <?php if($no_sub){echo "disabled='disabled'";}?>/>
	</div>
	
	<?php endif;?>


	</form>

	</div>
</div>
</div>
