<div>
<?php
//echo $javascript->link(array('prototype-1.7.0.0'));	
?>
<h2>Transcript</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>	
	
	<h3>Structural transcript information</h3>
	<div class="subdiv">
			
		<dl class="standard2">

		<dt>Transcript identifier</dt>
		<dd><?php echo $transcript_info['transcript_id'];?></dd>

		<dt>Uploaded sequence</dt>
		<dd>
			<div>		
			<textarea cols="80" rows="5"  name="transcript_sequence"><?php echo $transcript_info['transcript_sequence'];?></textarea>
			<br/>			
			<?php echo "<span>Sequence length: ".strlen($transcript_info['transcript_sequence'])." nt</span>";?>		
			</div>	
		</dd>

		<dt>Frameshift corrected sequence</dt>
		<dd>
			<div>
			<?php
			if($transcript_info['transcript_sequence_corrected']!=""){
				echo $form->create("",array("action"=>"transcript/".$exp_id."/".$transcript_info['transcript_id'],"type"=>"post"));
				echo "<textarea cols='80' rows='5'  name='corrected_sequence'>".$transcript_info['transcript_sequence_corrected']."</textarea>\n";
				echo "<br/>\n";
				echo "<span>Sequence length: ".strlen($transcript_info['transcript_sequence_corrected'])." nt</span>\n";
				echo "<br/>\n";
				echo "<input type='submit' value='Store changed corrected sequence' />\n";
				echo "</form>\n";		
			}
			else{
				echo "<span class='disabled'>Unavailable</span>\n";
			}
			?>
			</div>
		</dd>
	

		<dt>ORF sequence</dt>
		<dd>
			<div>
			<?php
			if($transcript_info['orf_sequence']!=""){
				echo $form->create("",array("action"=>"transcript/".$exp_id."/".$transcript_info['transcript_id'],"type"=>"post"));
				echo "<textarea cols='80' rows='5' name='orf_sequence'>".$transcript_info['orf_sequence']."</textarea>\n";
				echo "<br/>\n";
				echo "<span>Sequence length: ".strlen($transcript_info['orf_sequence'])." nt &nbsp; / &nbsp; <a href='javascript:show_aa();'>".(number_format(strlen($transcript_info['orf_sequence'])/3,0))." aa</a></span>\n";
				echo "<br/>\n";
				echo "<input type='submit' value='Store changed ORF sequence' style='margin-bottom:10px;'/>\n";
				echo "</form>\n";				
				echo "<script type='text/javascript'>\n";
				echo "//<![CDATA[\n";
				echo "function show_aa(){\n";
				echo "$('aa_seq_dt').style.display='block';\n";
				echo "$('aa_seq_dd').style.display='block';\n";
				echo "}\n";				
				echo "//]]>\n";
				echo "</script>\n";
			}
			else{
				echo "<span class='disabled'>Unavailable</span>\n";	
			}		
			?>			
			</div>
		</dd>	

		<dt id='aa_seq_dt' style='display:none;'>AA sequence</dt>
		<dd id='aa_seq_dd' style='display:none;'><div><textarea cols='80' rows='3'><?php echo $transcript_info['aa_sequence'];?></textarea></div></dd>
		

		<dt>Detected frame</dt>
		<dd><?php echo $transcript_info['detected_frame'];?></dd>
		
		<dt>Detected strand</dt>
		<dd><?php echo $transcript_info['detected_strand'];?></dd>
		
		<dt>Start/stop codon</dt>
		<dd>
			<div>
			<?php
			if($transcript_info['orf_contains_start_codon']==1){
				echo "The ORF sequence <span style='color:green'>starts with a start codon</span>\n";
			}
			else{
				echo "The ORF sequence <span style='color:red'>does not start with a start codon</span>\n";
			}
			echo "<br/>\n";
			if($transcript_info['orf_contains_stop_codon']==1){
				echo "The ORF sequence <span style='color:green'>ends with a stop codon</span>\n";
			}
			else{
				echo "The ORF sequence <span style='color:red'>does not end with a stop codon</span>\n";
			}
			?>
			</div>
		</dd>	
		
		<?php
		if($transcript_info['putative_frameshift']==1){

		$is_corrected	= ($transcript_info['is_frame_corrected']==1);
		$style1		=  " style='color:orange' "; if($is_corrected){$style1= " style='color:blue' ";}
		echo "<dt>Frameshift</dt>\n";
		echo "<dd>";
		echo "<div style='padding-left:20px;'>";
		echo "<ul>";
		echo "<li $style1>A putative frameshift was detected in this sequence</li>";
		if($transcript_info['is_frame_corrected']==1){
			echo "<li style='color:green'>A putative frameshift was corrected with FrameDP</li>";
		}	
		echo "</ul>";
		echo "</div>";
		echo "</dd>\n";			
		}
		?>

		<dt>Meta annotation</dt>
		<dd>
			<div>			
			<?php
			$possible_meta	= array(	"No Information"=>array("color"=>"#D2D2D2"),
							"Partial"=>array("color"=>"#D23333"),
							"Full Length"=>array("color"=>"#000000"),
							"Quasi Full Length"=>array("color"=>"#000000")
					);				
			echo $form->create("",array("action"=>"transcript/".$exp_id."/".$transcript_info['transcript_id'],"type"=>"post"));
			echo "<span style='color:".$possible_meta[$transcript_info['meta_annotation']]['color']."'>";
			echo $transcript_info['meta_annotation'];
			echo "</span>\n";
			echo "<select name='meta_annotation' style='width:150px;margin-left:50px;'>";
			foreach($possible_meta as $pm=>$pm_data){
				$sel	= null;
				if($pm == $transcript_info['meta_annotation']){$sel=" selected='selected' ";}
				echo "<option value='".$pm."' $sel>".$pm."</option>";
			}
			echo "</select>\n";
			echo "<input type='submit' value='Store changed meta annotation' style='margin-left:20px;' />\n";
			echo "</form>\n";
			?>
			</div>
		</dd>
		
		<dt>Gene family</dt>
		<dd>
			<div>
			<?php
			if($transcript_info['gf_id']!=""){
				echo $html->link($transcript_info['gf_id'],array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$transcript_info['gf_id']));
			}
			else{
				echo "<span class='disabled'>Unavailable</span>\n";
			}			
			?>
			<?php if($exp_info['genefamily_type']=="HOM"): ?>					
			<span style='margin-left:5px;'>
			<?php
				echo "(".$html->link("change gene family",array("controller"=>"trapid","action"=>"similarity_hits",$exp_id,$transcript_info['transcript_id'])).")";
			?>
			</span>	
			<?php endif;?>
			</div>
		</dd>	
		
		<dt>Subsets</dt>
		<dd>
			<div>
				<?php
				if(count($transcript_subsets)==0){
				     	echo "<span class='disabled'>No subset defined</span>\n";
				}
				else{
					for($i=0;$i<count($transcript_subsets);$i++){
					      echo $html->link($transcript_subsets[$i],array("controller"=>"labels","action"=>"view",$exp_id,urlencode($transcript_subsets[$i])));
						if($i!=((count($transcript_subsets))-1)){
							echo "&nbsp; , &nbsp; ";
						}
					}
				}			
				echo "<span style='margin-left:10px;'>(<a href='javascript:show_subsets();'>Add / change subsets</a>)</span>\n";			
				echo "<script type='text/javascript'>\n";
				echo "//<![CDATA[\n";
				echo "function show_subsets(){\n";
				echo "$('all_subsets').style.display='block';\n";			
				echo "}\n";				
				echo "//]]>\n";
				echo "</script>\n";								
				?>
			</div>	
		</dd>
		</dl>
		<?php
		echo "<div id='all_subsets' style='display:none;'>\n";	
		echo $form->create("",array("action"=>"transcript/".$exp_id."/".$transcript_info['transcript_id'],"type"=>"post"));
		echo "<input type='hidden' name='subsets' value='subsets'/>";
		echo "<table cellpadding='0' cellspacing='0' style='width:430px;'>\n";
		echo "<tr><th style='width:15%'>Include</th><th style='width:60%'>Subset</th><th>#Transcripts</th></tr>\n";
		foreach($available_subsets as $subset=>$count){
			echo "<tr>";
			$checked	= null;
			if(in_array($subset,$transcript_subsets)){$checked=" checked='checked' ";}
			echo "<td><input type='checkbox' name='".$subset."' $checked /></td>";
			echo "<td>".$html->link($subset,array("controller"=>"labels","action"=>"view",$exp_id,urlencode($subset)))."</td>";
			echo "<td>".$count."</td>";
			echo "</tr>\n";
		}
		echo "<tr>";
		echo "<td><input type='checkbox' name='new_subset' /></td>";
		echo "<td><input type='text' name='new_subset_name' /> New subset</td>";
		echo "<td></td>";	
		echo "</tr>\n";
		echo "</table>\n";
		echo "<input type='submit' value='Store changed subset information' />\n";
		echo "</form>\n";
		echo "</div>\n";

		?>	

	</div>
	<br/><br/>
	<h3>Toolbox</h3>
	<div class="subdiv">
	<?php
	
	$disable_cluster_tools	= false;
	if(isset($max_number_jobs_reached)){
		echo "<span class='error'>The maximum number of jobs (".MAX_CLUSTER_JOBS.") you can have queued has been reached for this experiment.<br/>Some tools will be unavailable until the currently scheduled jobs have finished or have been deleted.</span><br/><br/>\n";			$disable_cluster_tools = true;
	}

	$disabled_framedp	= true;
	if($transcript_info['gf_id']){$disabled_framedp=false;}	
	$toolbox	= array("Structural data"=>array(
					array(
					"Correct frameshifts with FrameDP",
					$html->url(array("controller"=>"tools","action"=>"framedp",$exp_id,$transcript_info['gf_id'],$transcript_info['transcript_id'])),
					"some_image.png",
					$disabled_framedp||$disable_cluster_tools				
					),
				),							
				"Similarity search"=>array(
					array(
					"Browse similarity search output",
					$html->url(array("controller"=>"trapid","action"=>"similarity_hits",$exp_id,$transcript_info['transcript_id'])),
					"some_image.png"
					)
				),
			);
	$this->set("toolbox",$toolbox);
	echo $this->element("toolbox");	
	?>		
	</div>

	<br/><br/>
	<h3>Functional transcript information</h3>
	<div class="subdiv">
		<dl class="standard2">
		
		<dt>Gene Ontology</dt>
		<dd>			
			<?php
			if($associated_go){
			        echo "<ul class='tabbed_header'>\n";
				echo "<li id='tab_collapsed' class='selected_tab'>";
				echo "<a href=\"javascript:switch_go_display('tab_collapsed','div_collapsed');\">Collapsed GO data</a>";
				echo "</li>\n";								
				echo "<li id='tab_all'>";
				echo "<a href=\"javascript:switch_go_display('tab_all','div_all');\">All GO data</a>";
				echo "</li>\n";
				echo "</ul>\n";
				
				echo "<div class='tabbed_div selected_tabbed_div' id='div_collapsed'>\n";
				echo "<table class='small' cellpadding='0' cellspacing='0' style='width:600px;'>\n";
				echo "<tr><th style='width:20%;'>GO term</th><th style='width:80%;'>Description</th></tr>\n";
				foreach($associated_go as $ag){
					$go		= $ag['TranscriptsGo']['go'];
					$is_hidden	= $ag['TranscriptsGo']['is_hidden'];
					if($is_hidden==0){	
					    $web_go		= str_replace(":","-",$go);
					    echo "<tr>";
					    echo "<td>".$html->link($go,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$web_go))."</td>";
					    echo "<td>".$go_info[$go]['desc']."</td>";
					    echo "</tr>\n";
					}
				}
				echo "</table>\n";
				echo "</div>\n";
					
				echo "<div class='tabbed_div' id='div_all'>\n";
				echo "<table class='small' cellpadding='0' cellspacing='0' style='width:600px;'>\n";
				echo "<tr><th style='width:20%;'>GO term</th><th style='width:80%;'>Description</th></tr>\n";
				foreach($associated_go as $ag){
					$go		= $ag['TranscriptsGo']['go'];
					$web_go		= str_replace(":","-",$go);
					$is_hidden	= $ag['TranscriptsGo']['is_hidden'];
					$class		= null;
					if($is_hidden==0){$class=" class='altrow' ";}	
					echo "<tr $class >";
					echo "<td>".$html->link($go,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$web_go))."</td>";
					echo "<td>".$go_info[$go]['desc']."</td>";
					echo "</tr>\n";
				}
				echo "</table>\n";
				echo "</div>\n";		
			}
			else{
				echo "<span class='disabled'>Unavailable</span>";
			}
			?>						
		</dd>

		<dt>Interpro domains</dt>
		<dd>
			<?php
			if($associated_interpro){
				echo "<table class='small' cellpadding='0' cellspacing='0' style='width:600px;'>\n";
				echo "<tr><th style='width:20%;'>InterPro domain</th><th style='width:80%;'>Description</th></tr>\n";
				foreach($associated_interpro as $ai){
					$ipr	= $ai['TranscriptsInterpro']['interpro'];
					echo "<tr>";
					echo "<td>".$html->link($ipr,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$ipr))."</td>";	
					echo "<td>".$interpro_info[$ipr]['desc']."</td>";
					echo "</tr>\n";
				}
				echo "</table>\n";
			}
			else{
				echo "<span class='disabled'>Unavailable</span>";
			}
			?>		
		</dd>
					
		</dl>
	</div>
	<script type="text/javascript">
	//<![CDATA[
		function switch_go_display(tab_id,div_id){
			//make all tabs and divs 'normal'
			$("tab_collapsed").className="";
			$("tab_all").className="";
			$("div_collapsed").className="tabbed_div";
			$("div_all").className="tabbed_div";
			//create extra class for the correct tab and div
			$(tab_id).className="selected_tab";
			$(div_id).className="tabbed_div selected_tabbed_div";	
		}
	//]]>
	</script>
	
</div>
</div>
