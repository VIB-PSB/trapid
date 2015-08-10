<div>
<h2>Gene Family</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>	
	
	<h3>Overview</h3>
	<div class="subdiv">
		<dl class="standard">
			<dt>Gene Family</dt>
			<dd><?php echo $gf_info['gf_id'];?></dd>
			<dt>Transcript count</dt>
			<dd><?php echo $gf_info['num_transcripts'];?></dd>
			<?php
			if($exp_info['genefamily_type']=="HOM"){
			    echo "<dt>Original Gene Family</dt>\n";
			    echo "<dd>";
			    if($exp_info['allow_linkout']){
				 echo $html->link($gf_info['plaza_gf_id'],$exp_info["datasource_URL"]."/gene_families/view/".$gf_info['plaza_gf_id']);
			    }
			    else{
				echo $gf_info['plaza_gf_id'];
			    }			 
			    echo "</dd>\n";
			}
			else{	
			    echo "<dt>Ortho group content</dt>\n";
			    echo "<dd>\n";		
			    echo "<div id='ocg1'>";
		 	    echo "<a href=\"javascript:$('ocg1').style.display='none';$('ocg2').style.display='block';void(0);\">Show content</a>";			   
			    echo "</div>\n";
			    echo "<div id='ocg2' style='display:none;'>";
			    echo "<a href=\"javascript:$('ocg1').style.display='block';$('ocg2').style.display='none';void(0);\">Hide content</a><br/>";
			    echo "<table class='small' cellpadding='0' cellspacing='0' style='width:800px;'>";
			    echo "<tr><th style='width:30%;'>Species</th><th style='width:'10%;'>#genes</th><th style='width:60%;'>Genes</th></tr>";			$i=0;
			    foreach($gf_content as $species=>$gc){
				$common_name	= $all_species[$species];
				$class=null; if($i++%2==0){$class=" class='altrow' ";}
				echo "<tr $class>";
				echo "<td>".$html->link($common_name,$exp_info['datasource_URL']."/organism/view/".urlencode($common_name))."</td>";
				echo "<td>".count($gc)."</td>";
				echo "<td>";
				foreach($gc as $g){
					echo $html->link($g,$exp_info['datasource_URL']."/genes/view/".urlencode($g))." ";
				}
				echo "</td>";
				echo "</tr>";
			    }			    
			    echo "</table>";		
			    echo "</div>\n";
			    echo "</dd>\n";			    
			}						
			?>						
		</dl>			
	</div>
	
	<h3>Toolbox</h3>
	<div class="subdiv">		
		<?php
		$disable_cluster_tools	= false;
		if(isset($max_number_jobs_reached)){
			echo "<span class='error'>The maximum number of jobs (".MAX_CLUSTER_JOBS.") you can have queued has been reached for this experiment.<br/>Some tools will be unavailable until the currently scheduled jobs have finished or have been deleted.</span><br/><br/>\n";			$disable_cluster_tools = true;
		}
	

		//$num_putative_fs_transcripts	= 0;
		//foreach($transcripts as $tran){if($tran['Transcripts']['putative_frameshift']==1){$num_putative_fs_transcripts++;}}

		
		$msa_string	= "Create multiple sequence alignment";
		$tree_string	= "Create phylogenetic tree";
		if($gf_info['msa']){$msa_string = $msa_string."/View current alignment";}
		if($gf_info['tree']){$tree_string = $tree_string."/View current tree";}		


		$toolbox= array("Structural data"=>array(
				    array(
					"Correct frameshift(s) with FrameDP",
					$html->url(array("controller"=>"tools","action"=>"framedp",$exp_id,$gf_info['gf_id'])),
					"some_image.png",
					$disable_cluster_tools				
				    ),				   
				),
				"Comparative genomics"=>array(
				    array(	
					$msa_string,
					$html->url(array("controller"=>"tools","action"=>"create_msa",$exp_id,$gf_info['gf_id'])),
					"other_image.png",
					$disable_cluster_tools
				    ),
				    array(
					$tree_string,
					$html->url(array("controller"=>"tools","action"=>"create_tree",$exp_id,$gf_info['gf_id'])),
					"other_image.png",
					$disable_cluster_tools
				    )
				),
				"Functional data"=>array(
				    array(
					"View associated functional annotation",
					$html->url(array("controller"=>"gene_family","action"=>"functional_annotation", $exp_id,$gf_info['gf_id'])),
					"some_image.png"
				    )
				)
			);
		$this->set("toolbox",$toolbox);
		echo $this->element("toolbox");					
		?>
	</div>	

	<h3>Transcripts</h3>
	<div class="subdiv">
		<?php echo $this->element("table_func");?>	
	</div>
	
	<?php 
		$download_url	= $html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"gf_id",$gf_info['gf_id']),true);	
		$this->set("download_url",$download_url);
		$this->set("allow_reference_aa_download",1);	
		echo $this->element("download"); 
	?>
	<br/>
</div>
</div>
