<?php
	// Load the necessary javascript libraries
    echo $this->Html->script(array('ftiens4.js', 'ua.js', 'd3-3.5.6.min.js', 'phyd3.min.js'));
    echo $this->Html->css('phyd3.min.css');
?>

<style>
    /* Experimenting with styling the tree viewer */
    text {
        font-family: sans-serif;
    }
</style>

<div>
    <div class="page-header">
        <h1 class="text-primary">Create phylogenetic tree</h1>
    </div>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>

    <p class="text-justify">
        <strong>Gene family: </strong> <?php echo $this->Html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family", $exp_id, $gf_id));?>
        (<?php echo $gf_info['GeneFamilies']['num_transcripts'];?> transcripts). </p>

    <!--
  	<h3>Gene family</h3>
	<div class="subdiv">
		<dl class='standard dl-horizontal'>
		<dt>Gene family</dt>
		<dd><?php echo $this->Html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id));?></dd>
		<dt>#Transcripts</dt>
		<dd><?php echo $gf_info['GeneFamilies']['num_transcripts'];?></dd>
		</dl>
	</div>
	-->

	<?php
	$hide_options	= false;
	if(isset($previous_result) && $previous_result==true){
		echo "<h3>Phylogenetic tree</h3>\n";
		?>

        <?php if(!$stripped_msa_length==0): ?>
            <!-- First trial with PhyD3! Controls copied from the example given in the doc then adapted. -->
            <div id="phyd3-row" class="row phyd3-controls" style="margin-top: 10px;">
                <div id="phyd3-settings" class="col-sm-3">
                    <div class="panel panel-default">
                        <div class="panel-heading">Visualization settings & export</div>
                        <div class="panel-body">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="dynamicHide" type="checkbox" checked="checked"> dynamic node hiding
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group checkbox">
                                    <label class="top-padding">
                                        <input id="invertColors" type="checkbox"> invert colors
                                    </label>
                                    <span class="input-group-btn">
                                    <div class="input-group colorpicker-component" id="foregroundColorButton">
                                        <input type="text" class="form-control hidden" name="foregroundColor" id="foregroundColor" />
                                        <span class="input-group-addon btn btn-fab btn-fab-mini"><i></i></span>
                                    </div>
                                </span>
                                    <span class="input-group-btn">
                                    <div class="input-group colorpicker-component" id="backgroundColorButton">
                                        <input type="text" class="form-control hidden" name="backgroundColor" id="backgroundColor" />
                                        <span class="input-group-addon btn btn-fab btn-fab-mini"><i></i></span>
                                    </div>
                                </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="phylogram" type="checkbox" checked="checked"> show phylogram
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="lineupNodes" type="checkbox" checked="checked"> lineup nodes
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="lengthValues" type="checkbox"> show branch length values
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-4 text-right left-dropdown middle-padding">decimals</div>
                                <div class="col-xs-3 no-padding">
                                    <input id="maxDecimalsLengthValues" type="number" min="0" id="domainLevel" class="form-control no-padding col-sm-6"  value="3" disabled />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="supportValues" type="checkbox"> show support values
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-4 text-right left-dropdown middle-padding">decimals</div>
                                <div class="col-xs-3 no-padding">
                                    <input id="maxDecimalsSupportValues" type="number" min="0" id="domainLevel" class="form-control no-padding col-sm-6" value="1" disabled />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="nodeNames" type="checkbox" checked="checked"> show node names
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3 text-right left-dropdown middle-padding">for</div>
                                <div class="col-xs-5 no-padding">
                                    <select id="nodesType" class="form-control">
                                        <option selected="selected">all</option>
                                        <option>only leaf</option>
                                        <option>only inner</option>
                                    </select>
                                </div>
                                <div class="col-xs-4 text-left right-dropdown middle-padding">
                                    nodes
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="nodeLabels" type="checkbox" checked="checked"> show node labels
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="sequences" type="checkbox"> show additional node information
                                    </label>
                                </div>
                            </div>
<!--                            <div class="form-group">-->
<!--                                <div class="checkbox">-->
<!--                                    <label>-->
<!--                                        <input id="taxonomy" type="checkbox" checked="checked"> show taxonomy-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="form-group">-->
<!--                                <div class="checkbox">-->
<!--                                    <label>-->
<!--                                        <input id="taxonomyColors" type="checkbox" checked="checked"> taxonomy colorization-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="row">-->
<!--                                <div class="col-xs-11 text-right left-dropdown middle-padding"><a class="pointer" data-toggle="modal" data-target="#taxonomyColorsModal">show taxonomy colors table</a></div>-->
<!--                            </div>-->
                            <div class="row">
                                <div class="col-xs-3">
                                    node size
                                </div>
                                <div class="col-xs-3 text-right">
                                    <button id="nodeHeightLower" class="btn btn-primary" title="make them smaller"><span class="glyphicon glyphicon-zoom-out" aria-hidden="true"></span></button>
                                </div>
                                <div class="col-xs-3 text-center middle-padding">
                                    <input type="text" id="nodeHeight" disabled="disabled" class="form-control no-padding" />
                                </div>
                                <div class="col-xs-3 text-left">
                                    <button id="nodeHeightHigher" class="btn btn-primary" title="make them bigger"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span></button>
                                </div>
                            </div><br>
                            <div class="row">
                                <div class="col-xs-4 col-xs-offset-4 text-center">
                                    <button id="zoominY" class="btn btn-primary" title="zoom in along Y axis"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span> Y</button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-4 text-center">
                                    <button id="zoomoutX" class="btn btn-primary" title="zoom out along X axis"><span class="glyphicon glyphicon-zoom-out" aria-hidden="true"></span> X</button>
                                </div>
                                <div class="col-xs-4 text-center">
                                    <button id="resetZoom" class="btn btn-link">RESET</button>
                                </div>
                                <div class="col-xs-4 text-center">
                                    <button id="zoominX" class="btn btn-primary" title="zoom in along X axis"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span> X</button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-4 col-xs-offset-4 text-center">
                                    <button id="zoomoutY" class="btn btn-primary" title="zoom out alongY axis"><span class="glyphicon glyphicon-zoom-out" aria-hidden="true"></span> Y</button>
                                </div>
                            </div>
<!--                            <div class="form-group">-->
<!--                                <div class="checkbox">-->
<!--                                    <label>-->
<!--                                        <input id="domains" type="checkbox" checked="checked"> show domain architecture-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="form-group">-->
<!--                                <div class="checkbox">-->
<!--                                    <label>-->
<!--                                        <input id="domainNames" type="checkbox"> show domain names-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="form-group">-->
<!--                                <div class="checkbox">-->
<!--                                    <label>-->
<!--                                        <input id="domainColors" type="checkbox" checked="checked"> domain colorization-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="row">-->
<!--                                <div class="col-xs-3">-->
<!--                                    domain scale-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-right">-->
<!--                                    <button id="domainWidthLower" class="btn btn-primary" title="make them shorter"><span class="glyphicon glyphicon-zoom-out" aria-hidden="true"></span></button>-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-center middle-padding">-->
<!--                                    <input type="text" id="domainWidth" disabled="disabled" class="form-control no-padding" />-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-left">-->
<!--                                    <button id="domainWidthHigher" class="btn btn-primary" title="make them longer"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span></button>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <br />-->
<!--                            <div class="row">-->
<!--                                <div class="col-xs-3">-->
<!--                                    p &nbsp; value-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-right">-->
<!--                                    <button id="domainLevelLower" class="btn btn-primary" title="lower the threshold">-</button>-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-center middle-padding">-->
<!--                                    <input type="text" id="domainLevel" disabled="disabled" class="form-control no-padding" />-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-left">-->
<!--                                    <button id="domainLevelHigher" class="btn btn-primary" title="higher the threshold">+</button>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="form-group">-->
<!--                                <div class="checkbox">-->
<!--                                    <label>-->
<!--                                        <input id="graphs" type="checkbox" checked="checked"> show graphs-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="form-group">-->
<!--                                <div class="checkbox">-->
<!--                                    <label>-->
<!--                                        <input id="graphLegend" type="checkbox" checked="checked"> show graphs legend-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="row">-->
<!--                                <div class="col-xs-3">-->
<!--                                    graph scale-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-right">-->
<!--                                    <button id="graphWidthLower" class="btn btn-primary" title="make them shorter"><span class="glyphicon glyphicon-zoom-out" aria-hidden="true"></span></button>-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-center middle-padding">-->
<!--                                    <input type="text" id="graphWidth" disabled="disabled" class="form-control" />-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-left">-->
<!--                                    <button id="graphWidthHigher" class="btn btn-primary" title="make them longer"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span></button>-->
<!--                                </div>-->
<!--                            </div>-->
                            <br>
                            <strong>Search in tree</strong> (regexp supported):
                                <input type="text" id="searchQuery" placeholder="Type your search..." class="form-control no-padding form-inline" />
                        </div> <!--  end .panel-body -->
                        <div class="panel-footer">
                            <strong>Export as:</strong>
                            <button class="btn btn-sm btn-primary" id="linkSVG">SVG</button>
                            <button class="btn btn-sm btn-primary" id="linkPNG">PNG</button>
                            <a class="btn btn-sm btn-primary" href="<?php echo $this->Html->url(array("controller"=>"tools","action"=>"view_tree",$hashed_user_id,$exp_id,$gf_id,"newick"),true); ?>">Newick</a>
                            <a class="btn btn-sm btn-primary" href="<?php echo $this->Html->url(array("controller"=>"tools","action"=>"view_tree",$hashed_user_id,$exp_id,$gf_id),true); ?>">XML</a>
                        </div>
                    </div> <!--  end .panel -->
                    <p class="text-justify">Use your mouse to drag, zoom and modify the tree. <strong>Actions:</strong><br />
                        <kbd><kbd>ctrl</kbd> + <kbd>wheel</kbd></kbd> scale Y <br>
                        <kbd><kbd>alt</kbd> + <kbd>wheel</kbd></kbd> scale X <br>
                        <kbd><kbd>mouse click</kbd></kbd> show node info
                    </p>
                </div> <!--  end column -->
                <div id="phyd3-viewer" class="col-sm-9"></div>
            </div>
        <?php endif; ?>

        <?php
		echo "<div class='subdiv'>\n";
		$data_url_tree	= $this->Html->url(array("controller"=>"tools","action"=>"view_tree",$hashed_user_id,$exp_id,$gf_id),true);
		$data_url_tree_newick = $this->Html->url(array("controller"=>"tools","action"=>"view_tree",$hashed_user_id,$exp_id,$gf_id,"newick"),true);
		$web_url_msa		= $this->Html->url(array("controller"=>"tools","action"=>"create_msa",$exp_id,$gf_id),true);
		$web_url_msa_stripped	= $this->Html->url(array("controller"=>"tools","action"=>"create_msa",$exp_id,$gf_id,"stripped"),true);

		$jar_file_location = $this->Html->url("/files/forester/",true);
		$config_url	   = TMP_WEB."experiment_data/".$exp_id."/atv_config.cfg";


		if($stripped_msa_length==0){
		    echo "<br/><br/><span class='error'>The computed stripped multiple sequence alignment has a length of zero. As such, no phylogenetic tree is constructed.</span></div>\n";
		}
		else{
//		    echo "<span>Download phylogenetic tree:</span>";
//		    echo "<ul style='margin-left:30px;'>";
//		    echo "<li>".$this->Html->link("PhyloXML",$data_url_tree)."</li>";
//		    echo "<li>".$this->Html->link("Newick",$data_url_tree_newick)."</li>";
//		    echo "</ul>";
		    echo "<h3>Multiple sequence alignment</h3>";
		    echo "<ul style='margin-left:30px;'>";
		    echo "<li>".$this->Html->link("Full MSA",$web_url_msa)." (Length: ".$full_msa_length." amino acids)</li>";
		    echo "<li>".$this->Html->link("Stripped MSA",$web_url_msa_stripped)." (Length: ".$stripped_msa_length." amino acids)</li>";
		    echo "</ul>";
		    echo "<br/>\n";

		    echo "<div style='margin-left:5px;margin-top:-10px;'>\n";
//		    echo "<p><br/><br/>\n";
//		    echo "<applet archive='forester.jar' code='org.forester.atv.ATVe.class' codebase='$jar_file_location' width='950' height='750' alt='Archeopteryx is not working on your system (requires at least Java 1.5)'>\n";
//		    echo "<param name='url_of_tree_to_load' value='$data_url_tree'>\n";
//		    echo "<param name='config_file' value='$config_url' >\n";
//		    echo "<param name='base_linkout_url' value='".$this->Html->url("/",true)."trapid/transcript/".$exp_id."/'  >\n";
//		    echo "</applet>\n";
//		    echo "</p>";
            echo "</div>\n";
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
		    echo "An email will be sent when the job has finished.</br>";
		    echo "</div>\n";
	    }
	}
	?>

	<?php
	$options_div_style		= null;
	if($hide_options){
		$options_div_style	= " style='display:none;' ";
		echo "<div id='rerun_div'>";
		// echo "<br/><br/><a href=\"javascript:void(0);\" onclick=\"javascript:toggleElementsNewMsa();\" >Create phylogenetic tree with different species</a><span style='margin-left:20px;'>(You may have to clear the Java cache to see the new result)</span>\n";
		echo "<br><a href=\"javascript:void(0);\" onclick=\"javascript:toggleElementsNewMsa();\" >Create phylogenetic tree with different species or settings</a>\n";
		echo "</div>";
		echo "<br>\n";
	}
	?>

	<div id="options_div" <?php echo $options_div_style;?> >
	<br/>
	<?php
		echo $this->Form->create(false,array("action"=>"create_tree/".$exp_id."/".$gf_id,"type"=>"post"));
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
		var ICONPATH 		= <?php echo "\"".$this->Html->url("/",true)."img/tree_view/"."\""; ?>;

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
	    <div style='float:left;width:330px;'>
		<?php if($exp_info['hit_results']):?>
            <span style="margin-left:10px;"><strong>Species similarity hits</strong></span>
		<div style="margin-top:5px;" id="species_simsearch_selection_div">
			<table class="table table-condensed table-bordered table-striped table-hover" cellpadding="0" cellspacing="0" style="width:330px; font-size: 85%;">
				<thead>
                <tr>
					<th style="width:60%">Species</th>
					<th style="width:40%">Hit count (global)</th>
                </tr>
				</thead>
                <tbody>
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
                </tbody>
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
				$ng =  (int) $phylo_profile[$spec_info['species']];
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

	    var clades_to_species	= <?php echo json_encode($clades_ok_species);?>;
	    var species_phylo		= <?php echo json_encode($phylo_profile_tax);?>;
	    var parent_child_clades	= <?php echo json_encode($parent_child_clades);?>;
	    var MAX_GENES		= <?php echo $MAX_GENES;?>;


			function changeClade(clade) {
				var element	= document.getElementById(clade);
                clades_to_species[clade].forEach(function (sp) {
					var sp_el		= document.getElementById("taxid_"+sp);
					if(sp_el!=null) {
					    if(element.checked) {	// Add new genes and species from total count
						    if(sp_el.checked!=element.checked) {
							    total_selected_species++;
							    total_selected_genes+=species_phylo[sp];
						    }
					    }
					    else {	// Remove genes and species from total count
						    if(sp_el.checked!=element.checked) {
							    total_selected_species--;
							    total_selected_genes-=species_phylo[sp];
						    }
					    }
    					    if(sp_el.disabled) {}
    					    else {
    					        sp_el.checked	= element.checked;
    					    }
					}
				});
				// Check or uncheck child-clades as well. This is purely for visualization purposes.
				parent_child_clades[clade].forEach(function(child_clade){
					try {
						var cc		= document.getElementById(child_clade);
						if(cc!=null) {
							if(cc.disabled) {}
							else {
							    cc.checked	= element.checked;
							}
						}
					}
					catch(exc) {
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
            document.getElementById("num_species").innerHTML = total_selected_species;
            document.getElementById("num_genes").innerHTML = total_selected_genes;
			if(total_selected_genes > MAX_GENES) {
                document.getElementById("status").innerHTML	= "<span class='error'>Error: too many genes selected (max: "+MAX_GENES+"). Deselect species to continue.</span>";
                document.getElementById("submit_button").disabled = "disabled";
			}
			else {
                document.getElementById("status").innerHTML	= "OK";
                document.getElementById("submit_button").disabled = false;
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
			<table class="table-bordered table table-condensed table-striped table-hover" cellpadding="0" cellspacing="0" style="width:400px;">
				<thead>
                <tr>
					<th style="width:15%">Exclude</th>
					<th style="width:45%">Transcript</th>
					<th style="width:40%">Meta annotation</th>
				</tr>
                </thead>
                <tbody>
				<?php
				foreach($gf_transcripts as $tr=>$met){
					echo "<tr>";
					echo "<td class='text-center'><input type='checkbox' name='exclude_".$tr."' id='exclude_".$tr."' /></td>";
					echo "<td>".$this->Html->link($tr,array("controller"=>"trapid","action"=>"transcript",$exp_id,urlencode($tr)))."</td>";
					echo "<td>".$met."</td>";
					echo "</tr>";
				}
				?>
                </tbody>
			</table>
		</div>
		<script type="text/javascript">
			//<![CDATA[
			var transcript_meta	= <?php echo json_encode($gf_transcripts);?>;
			$("#single_transcript_selection").change(function(){
				if(document.getElementById("single_transcript_selection").checked){
                    document.getElementById("transcript_select_div").style.display="block";
				}
				else{
					Object.keys(transcript_meta).forEach(function(tr){
						var cb	= "exclude_"+tr;
                        document.getElementById(cb).checked = null;
					});
                    document.getElementById("transcript_select_div").style.display="none";
				}
			});
			$("#no_partial_transcripts").change(function(){
				if(document.getElementById("no_partial_transcripts").checked){
                    document.getElementById("single_transcript_selection").checked = "checked";
                    document.getElementById("transcript_select_div").style.display="block";
					Object.keys(transcript_meta).forEach(function(tr){
						var meta = transcript_meta[tr];
						if(meta === "Partial") {
							var cb	= "exclude_"+tr;
                            document.getElementById(cb).checked = "checked";
						}
					});
				}
				else{
					// document.getElementById("single_transcript_selection").checked = null;
					// document.getElementById("transcript_select_div").style.display="none";
					Object.keys(transcript_meta).forEach(function(tr){
					    console.log(transcript_meta[tr]);
						var meta = transcript_meta[tr];
						if(meta === "Partial"){
							var cb	= "exclude_"+tr;
                            document.getElementById(cb).checked = null;
						}
					});
				}
			});


            // Toggle visibility of HTML elements if the user chooses to build a new tree.
            function toggleElementsNewMsa() {
                document.getElementById('options_div').style.display = 'block';
                document.getElementById('rerun_div').style.display = 'none';
            }
			//]]>
		</script>
	</div>


	<h3>Overview and Options</h3>
	<div class="subdiv">
		<dl class="standard dl-horizontal">
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
		<input type="submit" value="Create phylogenetic tree" id="submit_button" <?php if($no_sub){echo "disabled='disabled'";}?> class="btn btn-primary"/>
	</div>
<br>
	<?php endif;?>

	</form>

	</div>

</div>
</div>
<?php if(isset($previous_result) && $previous_result==true && $stripped_msa_length!=0): ?>
    <script type="text/javascript">
        var treeUrl = "<?php echo $this->Html->url(array("controller"=>"tools","action"=>"get_tree",$exp_id, $gf_id)); ?>"; // , "xml_tree"
        console.log(treeUrl);
        var phyD3Elmt = "phyd3-viewer";
        var opts = {
            dynamicHide: true,
            height: 750,
            invertColors: false,
            lineupNodes: true,
            // showDomains: true,
            // showDomainNames: false,
            // showDomainColors: true,
            showGraphs: true,
            showGraphLegend: true,
            showLength: false,
            showNodeNames: true,
            // showNodesType: "only leaf",
            showNodesType: "all",
            showPhylogram: false,
            showTaxonomy: true,
            showFullTaxonomy: false,
            showSequences: false,
            showTaxonomyColors: true,
            backgroundColor: "#f5f5f5",
            foregroundColor: "#000000",
            nanColor: "#f5f5f5"
        };

        function loadPhyd3() {
            jQuery('#foregroundColor').val(opts.foregroundColor);
            jQuery('#backgroundColor').val(opts.backgroundColor);
            jQuery('#foregroundColorButton').colorpicker({color: opts.foregroundColor});
            jQuery('#backgroundColorButton').colorpicker({color: opts.backgroundColor});
            d3.select("#" + phyD3Elmt).text("Loading tree viewer... ");
            d3.text(treeUrl, function (data) {
                d3.select("#" + phyD3Elmt).text(null);
                var tree = phyd3.newick.parse(data);
                phyd3.phylogram.build("#" + phyD3Elmt, tree, opts);

            });
        }
/*
            d3.xml(treeUrl, "application/xml", function(xml) {
                d3.select("#" + phyD3Elmt).text(null);
                if(xml == null) {
                    d3.select("#phyd3").text("Error while reading PhyloXML file from the server");
                }
                else {
                    console.log(phyd3.phyloxml.parse(xml));
                    console.log("wqdqwdqw");
                    var tree = phyd3.phyloxml.parse(xml);
                    phyd3.phylogram.build("#" + phyD3Elmt, tree, opts);
                }
            });
        };
*/

        $(document).ready(function () {
            loadPhyd3();
        });
</script>
<?php endif; ?>