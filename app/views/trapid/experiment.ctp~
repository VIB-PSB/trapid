<div>
<h2>Experiment</h2>
<div class="subdiv">

	<div style="float:left;width:1010px;">
	<h3>Experiment information</h3>
	<div class="subdiv">		
		<div style="float:left;width:500px;">
		<dl class="standard">
			<dt>Name</dt>
			<dd><?php echo $standard_experiment_info['Experiments']['title'];?></dd>
			<dt>Description</dt>
			<dd><div style="width:300px;">
				<?php 
					if($standard_experiment_info['Experiments']['description']){
						echo $standard_experiment_info['Experiments']['description'];
					}
					else{
						echo "<span style='color:#B2B2B2'>No description available</span>";
					}
				?>
			</div></dd>
			<dt>Processing status</dt>
			<dd><?php echo $standard_experiment_info['Experiments']['process_state'];?></dd>
			<dt>Data source</dt>
			<dd>
				<?php 				
				if($datasource_info['URL']){echo $html->link($datasource_info['name'],$datasource_info['URL']);}
				else{echo $datasource_info['name'];}
				?>			
			</dd>
			<dt>Creation</dt>
			<dd><?php echo $standard_experiment_info['Experiments']['creation_date'];?></dd>
			<dt>Last edit</dt>
			<dd><?php echo $standard_experiment_info['Experiments']['last_edit_date'];?></dd>			
		</dl>		
		</div>
		<div style="float:left;width:500px;">
		<dl class="standard">
			<dt>Transcript count</dt>
			<dd><?php echo $transcript_experiment_info[0][0]['transcript_count'];?></dd>
			<dt>Gene family count</dt>
			<dd><?php echo $transcript_experiment_info[0][0]['gf_count'];?></dd>
			<?php
			if($standard_experiment_info['Experiments']['used_blast_database']){
			echo "<dt>Used similarity search</dt>";
			echo "<dd>".$standard_experiment_info['Experiments']['used_blast_database']."</dd>\n";
			}
			?>
			<dt>Log</dt>
			<dd><?php echo $html->link("View log",array("controller"=>"trapid","action"=>"view_log",$exp_id));?></dd>
			<dt>Experiment access</dt>
			<dd><?php echo $html->link("Share this experiment",array("controller"=>"trapid","action"=>"experiment_access",$exp_id));?></dd>
			<dt>Settings</dt>
			<dd><?php echo $html->link("Change settings",array("controller"=>"trapid","action"=>"experiment_settings",$exp_id));?></dd>
			<dt>Content</dt>
			<dd><?php	
				    echo $html->link("Empty experiment",
				    array("controller"=>"trapid","action"=>"empty_experiment",$exp_id),
				    array("style"=>"color:#AA0055;font-weight:bold;"),
				    "Are you sure you want to delete all content from this experiment?");
				    echo "&nbsp;/&nbsp;";
			    	    echo $html->link("Delete experiment",
				    array("controller"=>"trapid","action"=>"delete_experiment",$exp_id),
				    array("style"=>"color:red;font-weight:bold;"),
				    "Are you sure you want to delete the experiment?");	
				?>
			</dd>
		</dl>
		</div>
		<div style="clear:both;width:700px;font-size:8px;">&nbsp;</div>
	</div>
	</div>
	<div style="float:right;width:100px;text-align:right;margin-right:50px;">
		<?php
			echo $html->link("Experiments",array("controller"=>"trapid","action"=>"experiments"),array("class"=>"mainref"));
			echo "<br/>\n";
			echo $html->link("Documentation",array("controller"=>"documentation","action"=>"index"),array("target"=>"_blank","class"=>"mainref"));
			echo "<br/>\n";
		?>
	</div>	
	<div style="clear:both;width:700px;font-size:8px;">&nbsp;</div>
		
	<h3>Import/Export</h3>
	<div class="subdiv">
		<dl class="standard">
			<dt>Import transcripts</dt>
			<dd>
				<?php
				$process_state	= $standard_experiment_info['Experiments']['process_state'];
				if($process_state=="empty"||$process_state=="upload"){
					echo $html->link("Import data",array("controller"=>"trapid","action"=>"import_data",$exp_id));
				}
				else{
					echo "<span class='disabled'>Disabled after initial data processing</span>\n";
				}
				?>
			</dd>
			<dt>Import transcript labels</dt>
			<dd>
				<?php
				if($num_transcripts==0){
					echo "<span class='disabled'>Disabled prior to transcripts import</span>\n";
				}
				else{
					echo $html->link("Import data",array("controller"=>"trapid","action"=>"import_labels",$exp_id));
				}
				?>
			</dd>
			<dt>Export data</dt>
			<dd>
				<?php
				if($num_transcripts==0){
					echo "<span class='disabled'>Disabled prior to transcripts import</span>\n";
				}
				else{
					echo $html->link("Export data",array("controller"=>"trapid","action"=>"export_data",$exp_id));
				}
				?>
			</dd>
		<!--	<dt>Clone experiment</dt>
			<dd>
				<?php
				if($num_transcripts==0){
					echo "<span class='disabled'>Disabled prior to transcripts import</span>\n";
				}
				else{
					echo $html->link("Copy transcripts to new experiment",
						array("controller"=>"trapid","action"=>"clone_experiment",$exp_id));
				}
				?>
			</dd>
		-->	
		</dl>	
	</div>
	<br/>

	<h3>Search</h3>
	<div class="subdiv">
		<br/>
		<?php echo $this->element("search_element");?>	
	</div>			
	<br/>
	

	<?php 
		if($standard_experiment_info['Experiments']['process_state']=="upload" || isset($admin)){
			echo "<h3>Initial processing</h3>\n";
			echo "<div class='subdiv'>\n";
			echo "<dl class='standard'>\n";
			if($standard_experiment_info['Experiments']['process_state']!="upload"){
				echo "<dt><span style='color:red'>Override</span></dt>\n";
				echo "<dd><span style='color:red'>Experiment is not in upload state. Override at own risk</span></dd>\n";
			}
			echo "<dt>Process</dt>\n";
			echo "<dd>";
			echo $html->link("Perform transcript processing",
				array("controller"=>"trapid","action"=>"initial_processing",$exp_id));
			echo "</dd>\n";
			echo "</dl>\n";
			echo "</div><br/>\n";
		}
	?>

	<h3>Toolbox</h3>
	<div class="subdiv">	
	<?php 
	if($num_transcripts==0){
		echo "<span class='disabled'>Disabled prior to transcripts import and processing</span>\n";
	}
	else{					
		$disable_cluster_tools	= false;
		if(isset($max_number_jobs_reached)){
			echo "<span class='error'>The maximum number of jobs (".MAX_CLUSTER_JOBS.") you can have queued has been reached for this experiment.<br/>Some tools will be unavailable until the currently scheduled jobs have finished or have been deleted.</span><br/><br/>\n";			$disable_cluster_tools = true;
		}
	
		$subset1  = true; if($num_subsets>0){$subset1=false;}
		$subset2  = true; if($num_subsets>1){$subset2=false;}
		if($num_subsets==0){
			echo "<span class='warning'>No subsets have been defined. Several options from the 'Explore' section in the toolbox have been disabled</span><br/><br/>\n";
		}

	
		$toolbox= array("Statistics"=>array(
					array(
						"General statistics",
						$html->url(array("controller"=>"tools","action"=>"statistics",$exp_id)),
						"some_image.png"
					),				
					array(
						"Length distribution transcript sequences",
						$html->url(array("controller"=>"tools","action"=>"length_distribution",$exp_id,"transcript")),
						"some_image.png"
					),
					array(
						"Length distribution ORF sequences",
						$html->url(array("controller"=>"tools","action"=>"length_distribution",$exp_id,"orf")),
						"some_image.png"
					)					
				),
				"Explore"=>array(
					array(	
						"GO enrichment from a subset compared to background",
						$html->url(array("controller"=>"tools","action"=>"enrichment",$exp_id,"go")),
						"other_image.png",
						$subset1||$disable_cluster_tools
					),
					array(
						"GO ratios between subsets (table)",
						$html->url(array("controller"=>"tools","action"=>"compare_ratios",$exp_id,"go")),
						"other_image.png",
						$subset2
					),
					array(
						"GO ratios between subsets (chart)",
						$html->url(array("controller"=>"tools","action"=>"compare_ratios_chart",$exp_id,"go")),
						"other_image.png",
						$subset2
					),				
					array(	
						"Protein domain enrichment from a subset compared to background",
						$html->url(array("controller"=>"tools","action"=>"enrichment",$exp_id,"ipr")),
						"other_image.png",
						$subset1||$disable_cluster_tools
					),
				
					array(
						"Protein domain ratios between subsets",
						$html->url(array("controller"=>"tools","action"=>"compare_ratios",$exp_id,"ipr")),
						"other_image.png",
						$subset2
					),					
					array(
						"Different subsets",
						$html->url(array("controller"=>"labels","action"=>"subset_overview",$exp_id)),
						"some_image.png",
						$subset1
					)												
				),				
				"Browse"=>array(
					array(
						"Gene families",
						$html->url(array("controller"=>"gene_family","action"=>"index",$exp_id)),
						"other_image.png"
					)					
				),
				"Find"=>array(
					array(
						"Expanded/depleted gene families",
						$html->url(array("controller"=>"gene_family","action"=>"expansion",$exp_id)),
						"image.png"
					)
				)			
			);
		$this->set("toolbox",$toolbox);
		echo $this->element("toolbox");						
	}
	?>					
	</div>
	<br/>
	<a name='transcripts' />
	<h3>Transcripts</h3>
	<div class="subdiv">
		<?php if($num_transcripts==0):?>
		<span class='disabled'>Disabled prior to transcripts import</span>
		<?php else: ?>
		<?php 
			//$paginator->options(array("url"=>$this->passedArgs));
			$paginator->options(array("url"=>array("controller"=>"trapid","action"=>"experiment",$exp_id,"#"=>"transcripts")));
		?>
		<table cellpadding="0" cellspacing="0" style="width:90%;">
			<tr>
				<th style="width:10%">Transcript</th>
				<th style="width:15%">Gene family</th>
				<th style="width:27%">GO annotation</th>
				<th style="width:27%">Protein domain annotation</th>
				<th style="width:10%">Subset</th>
				<th style="width:10%">Meta annotation</th>				
				<!--<th style="width:5%">Edit</th>-->
			</tr>
			<?php
			$bad_status	= "Unassigned";
			$tr_counter	= 0;		
			foreach($transcript_data as $transcript_dat){
				$row_class	= null; if($tr_counter++%2==0){$row_class=" class='altrow' ";}
				
				$td=$transcript_dat['Transcripts'];
				echo "<tr $row_class>";

				//TRANSCRIPT ID
				echo "<td>".$html->link($td['transcript_id'],
					array("action"=>"transcript",$exp_id,urlencode($td['transcript_id'])))."</td>";
				


				//GF ID	
				echo "<td>";
				if($td['gf_id']){
					echo $html->link($td['gf_id'],
					     array("controller"=>"gene_family","action"=>"gene_family",$exp_id,urlencode($td['gf_id'])));
				}
				else{echo "<span class='disabled'>".$bad_status."</span>";}
				echo "</td>\n";
				



				//GO annotation				
				if(!array_key_exists($td['transcript_id'],$transcripts_go)){
					echo "<td><span class='disabled'>Unavailable</span></td>";
				}
				else{
					echo "<td class='left'>";
					for($i=0;$i<count($transcripts_go[$td['transcript_id']]) && $i<3;$i++){
						$go	= $transcripts_go[$td['transcript_id']][$i];
						$go_web	= str_replace(":","-",$go);
						$desc	= $go_info[$go]['desc'];
						echo ($i+1).") ".$html->link($desc,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$go_web))."<br/>";
					}
					echo "</td>";
				}			




				//InterPro annotation
				if(!array_key_exists($td['transcript_id'],$transcripts_ipr)){
					echo "<td><span class='disabled'>Unavailable</span></td>";
				}
				else{
					echo "<td class='left'>";
					for($i=0;$i<count($transcripts_ipr[$td['transcript_id']]) && $i<3;$i++){
						$ipr	= $transcripts_ipr[$td['transcript_id']][$i];
						$desc	= $ipr_info[$ipr]['desc'];
						echo ($i+1).") ".$html->link($desc,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$ipr))."</br>";
					}
					echo "</td>";
				}

			
				//SUBSET
				if(!array_key_exists($td['transcript_id'],$transcripts_labels)){
					echo "<td><span class='disabled'>Unavailable</span></td>";
				}
				else{
					echo "<td class='left'>";
					for($i=0;$i<count($transcripts_labels[$td['transcript_id']]) && $i<3;$i++){
						$label	= $transcripts_labels[$td['transcript_id']][$i];
						echo ($i+1).") ".$html->link($label,array("controller"=>"labels","action"=>"view",$exp_id,urlencode($label)))."<br/>";
					}
					echo "</td>";
				}

				//EDIT
				echo "<td>".$html->link($td['meta_annotation'],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"meta_annotation",urlencode($td['meta_annotation'])))."</td>";
			
				echo "</tr>\n";	
			}
			?>
		</table>
		<div class='paging'>
			<?php
			echo $paginator->prev('<< '.__('previous', true), array(), null, array('class'=>'disabled'));
			echo "&nbsp;";
  			echo $paginator->numbers();
			echo "&nbsp;";
			echo $paginator->next(__('next', true).' >>', array(), null, array('class'=>'disabled'));
			?>	
		</div>
		<?php endif;?>
	</div>
	<br/>
</div>
</div>
