<div>
    <div class="page-header">
        <h1 class="text-primary">Frameshift correction</h1>
    </div>
<div class="subdiv">
	<?php // echo $this->element("trapid_experiment");?>
	<h3>Options</h3>
	<div class="subdiv" style="margin-top: 20px;">

		<?php if(isset($error) && $error=="framedp_state"):?>
		
		<span class="error">
		The FrameDP pre-processing step (creating models from a set of transcripts) has not finished yet.<br/>		
		Current status: <u><?php echo $exp_info['framedp_state'];?></u><br/>
		</span>
		<?php else: ?>
		<?php
		echo $this->Form->create(false, array("action"=>"framedp/".$exp_id."/".$gf_id,"type"=>"post"));
		?>
		<dl class="standard">
			<dt>Gene family</dt>
			<dd>
				<?php echo $this->Html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id));?>
			</dd>

			<?php if(!isset($run_pipeline)) :?>
			<dt>Transcripts</dt>
			<dd>	
				<div>				
				<?php	

				//pr($transcripts);
															
				echo "<div style='float:left;max-width:650px;'>\n";
				echo "<table class='table table-hover table-stripsed table-condensed table-bordered table-centered' style='font-size:88%;'>\n";
				echo "<thead>\n";
				echo "<tr><th>Include</th><th>Transcript</th><th>Putative frameshift</th><th>FrameDP run</th><th>Frameshift corrected</th></tr>";
				echo "</thead>\n";
				echo "<tbody>\n";
				$start1	= 0; $stop1=(count($transcripts)/2); $two_table=true;
				if(count($transcripts) <5 ){$stop1=(count($transcripts)-1);$two_table=false;}
						
				$last_i	= 0;
				for($i=$start1;$i<=$stop1;$i++){
					$transcript	= $transcripts[$i]['Transcripts'];	
					$transcript_id	= $transcript["transcript_id"];		
					$putative_fs	= $transcript["putative_frameshift"];
					$framedp_run	= $transcript["is_framedp_run"];
					$frame_corrected= $transcript["is_frame_corrected"];				
					echo "<tr>";
					if($putative_fs){
						echo "<td class='text-center'><input type='checkbox' name='".$transcript_id."' id='".$transcript_id."' checked='checked'/></td>";
					}
					else{
						echo "<td class='text-center'><input type='checkbox' name='".$transcript_id."' id='".$transcript_id."' /></td>";
					}	
					echo "<td>".$this->Html->link($transcript_id,array("controller"=>"trapid","action"=>"transcript",$exp_id,$transcript_id))."</td>";
					if($putative_fs){echo "<td><span class='text-success'>V</span></td>";}
					else{echo "<td></td>";}

					if($framedp_run){echo "<td><span class='text-success'>V</span></td>";}
					else{echo "<td></td>";}
					
					if($putative_fs){
						if($frame_corrected){echo "<td><span class='text-success'>V</span></td>";}
						else{echo "<td><span class='text-danger'>X</span></td>";}
					}
					else{echo "<td></td>";}
		
					echo "</tr>\n";
					$last_i = $i;
				}
                echo "</tbody>\n";
                echo "</table>\n";
				echo "</div>\n";
				if($two_table){
				    echo "<div style='float:left;max-width:650px;margin-left:20px; font-size: 88%;'>\n";
				    echo "<table class='table table-hover table-stripsed table-condensed table-bordered table-centered' style='max-width:650px;'>\n";
				    echo "<tr><th>Include</th><th>Transcript</th><th>Putative Frameshift</th><th>FrameDP run</th><th>Frameshift corrected</th></tr>";
				    for($i=($last_i+1);$i<(count($transcripts));$i++){
					    $transcript	= $transcripts[$i]['Transcripts'];	
					    $transcript_id	= $transcript["transcript_id"];		
					    $putative_fs	= $transcript["putative_frameshift"];
					    $framedp_run	= $transcript["is_framedp_run"];
				            $frame_corrected	= $transcript["is_frame_corrected"];
					    echo "<tr>";
					    if($putative_fs){
						    echo "<td class='text-center'><input type='checkbox' name='".$transcript_id."' id='".$transcript_id."' checked='checked'/></td>";
					    }
					    else{
						    echo "<td class='text-center'><input type='checkbox' name='".$transcript_id."' id='".$transcript_id."' /></td>";
					    }	
					    echo "<td>".$this->Html->link($transcript_id,array("controller"=>"trapid","action"=>"transcript",$exp_id,$transcript_id))."</td>";
					    if($putative_fs){echo "<td><span class='text-success'>V</span></td>";}	
					    else{echo "<td></td>";}

					    if($framedp_run){echo "<td><span class='text-success'>V</span></td>";}
					    else{echo "<td></td>";}

					    if($putative_fs){
					    	if($frame_corrected){echo "<td><span class='text-success'>V</span></td>";}
						else{echo "<td><span class='text-danger'>X</span></td>";}
					    }
				  	    else{echo "<td></td>";}
					    echo "</tr>\n";
				    }								
				    echo "</table>\n";
				    echo "</div>\n";	
				}			
				?>

				<div style='clear:both;'>
				<input type='checkbox' id='all_fs_transcripts' checked='checked'/>
                    <label for="all_fs_transcripts" style='margin-left:10px;'><strong>Select all transcripts with putative frameshifts</strong></label> <br/>
				<input type='checkbox' id='all_transcripts' />
                    <label style='margin-left:10px;' for="all_transcripts"><strong>Select all transcripts</strong></label>
				<script type='text/javascript'>
				<?php
					$new_transcript_data	= array();
					foreach($transcripts as $transcript){
						$tr	= $transcript['Transcripts'];
						$new_transcript_data[] = array("transcript_id"=>$tr['transcript_id'],
										"putative_frameshift"=>$tr['putative_frameshift']);	
					}	
					echo "var transcript_data=".json_encode($new_transcript_data).";";
				?>
				
				//<![CDATA[

                    // Handle transcript selection with checkboxes

					$("#all_fs_transcripts").on("change", function() {
					    var all_fs_transcripts_check = document.getElementById("all_fs_transcripts").checked;
						for(var i=0;i<transcript_data.length;i++) {
							var transcript_id	= transcript_data[i].transcript_id;
							var frameshift		= transcript_data[i].putative_frameshift;
							if(frameshift) {
								document.getElementById(transcript_id).checked = all_fs_transcripts_check;
							}
						}					
					});

					$("#all_transcripts").on("change", function() {
                        var all_transcripts_check = document.getElementById("all_transcripts").checked;
						for(var i=0;i<transcript_data.length;i++) {
							var transcript_id	= transcript_data[i].transcript_id;
                            document.getElementById(transcript_id).checked = all_transcripts_check;
						}	
					});
				//]]>
				</script>
				</div>
				
				</div>
			</dd>	
			<?php endif; ?>			
		</dl>
		<?php
		if(isset($error)){
			echo "<br><span class='text-danger'><strong>".$error."</strong></span>\n";
			echo "<br/>\n";
		}
		?>
		
		<?php	
		if(isset($run_pipeline)){
			echo "<div class='subdiv'>";
			echo "<div id='framedp_div'>";		
			echo "A job for running framedp  has been added to the queue. <br/>";
		        echo "An email will be send when the job has finished.</br>";
			echo "</div>";
			echo "</div>";	
		}

		?>
		

		<?php if(!isset($run_pipeline)) : ?>
		<input type="submit" class='btn btn-primary' value="Perform frameshift correction" disabled/><br><br>
		</form>
		<?php endif;?>


		<?php endif;?>

	</div>
</div>	
</div>
