<div>
<h3>Frameshift correction using FrameDP</h3>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	<h3>FrameDP</h3>
	<div class="subdiv">

		<?php if(isset($error) && $error=="framedp_state"):?>
		
		<span class="error">
		The FrameDP pre-processing step (creating models from a set of transcripts) has not finished yet.<br/>		
		Current status: <u><?php echo $exp_info['framedp_state'];?></u><br/>
		</span>
		<?php else: ?>
		<?php
		echo $form->create(null,array("action"=>"framedp/".$exp_id."/".$gf_id,"type"=>"post"));
		?>
		<dl class="standard">
			<dt>Gene family</dt>
			<dd>
				<?php echo $html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id));?>
			</dd>

			<?php if(!isset($run_pipeline)) :?>
			<dt>Transcripts</dt>
			<dd>	
				<div>				
				<?php	

				//pr($transcripts);
															
				echo "<div style='float:left;width:450px;'>\n";		
				echo "<table cellpadding='0' cellspacing='0' style='width:450px;'>\n";
				echo "<tr><th>Include</th><th>Transcript</th><th>Putative frameshift</th><th>FrameDP run</th><th>Frameshift corrected</th></tr>";
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
						echo "<td><input type='checkbox' name='".$transcript_id."' id='".$transcript_id."' checked='checked'/></td>";
					}
					else{
						echo "<td><input type='checkbox' name='".$transcript_id."' id='".$transcript_id."' /></td>";
					}	
					echo "<td>".$html->link($transcript_id,array("controller"=>"trapid","action"=>"transcript",$exp_id,$transcript_id))."</td>";
					if($putative_fs){echo "<td><span class='message'>V</span></td>";}
					else{echo "<td></td>";}

					if($framedp_run){echo "<td><span class='message'>V</span></td>";}
					else{echo "<td></td>";}
					
					if($putative_fs){
						if($frame_corrected){echo "<td><span class='message'>V</span></td>";}
						else{echo "<td><span class='error'>X</span></td>";}
					}
					else{echo "<td></td>";}
		
					echo "</tr>\n";
					$last_i = $i;
				}								
				echo "</table>\n";
				echo "</div>\n";
				if($two_table){
				    echo "<div style='float:left;width:450px;margin-left:20px;'>\n";		
				    echo "<table cellpadding='0' cellspacing='0' style='width:450px;'>\n";
				    echo "<tr><th>Include</th><th>Transcript</th><th>Putative Frameshift</th><th>FrameDP run</th><th>Frameshift corrected</th></tr>";
				    for($i=($last_i+1);$i<(count($transcripts));$i++){
					    $transcript	= $transcripts[$i]['Transcripts'];	
					    $transcript_id	= $transcript["transcript_id"];		
					    $putative_fs	= $transcript["putative_frameshift"];
					    $framedp_run	= $transcript["is_framedp_run"];
				            $frame_corrected	= $transcript["is_frame_corrected"];
					    echo "<tr>";
					    if($putative_fs){
						    echo "<td><input type='checkbox' name='".$transcript_id."' id='".$transcript_id."' checked='checked'/></td>";
					    }
					    else{
						    echo "<td><input type='checkbox' name='".$transcript_id."' id='".$transcript_id."' /></td>";
					    }	
					    echo "<td>".$html->link($transcript_id,array("controller"=>"trapid","action"=>"transcript",$exp_id,$transcript_id))."</td>";
					    if($putative_fs){echo "<td><span class='message'>V</span></td>";}	
					    else{echo "<td></td>";}

					    if($framedp_run){echo "<td><span class='message'>V</span></td>";}
					    else{echo "<td></td>";}

					    if($putative_fs){
					    	if($frame_corrected){echo "<td><span class='message'>V</span></td>";}
						else{echo "<td><span class='error'>X</span></td>";}
					    }
				  	    else{echo "<td></td>";}
					    echo "</tr>\n";
				    }								
				    echo "</table>\n";
				    echo "</div>\n";	
				}			
				?>

				<div style='clear:both;width:700px;'>							
				<input type='checkbox' id='all_fs_transcripts' checked='checked'/>
				<span style='margin-left:10px;'>Select all transcripts with putative frameshifts</span> <br/>
				<input type='checkbox' id='all_transcripts' />
				<span style='margin-left:10px;'>Select all transcripts</span>
				<script type='text/javascript'>
				<?php
					$new_transcript_data	= array();
					foreach($transcripts as $transcript){
						$tr	= $transcript['Transcripts'];
						$new_transcript_data[] = array("transcript_id"=>$tr['transcript_id'],
										"putative_frameshift"=>$tr['putative_frameshift']);	
					}	
					echo "var transcript_data=".$javascript->object($new_transcript_data).";";
				?>
				
				//<![CDATA[
					$("all_fs_transcripts").observe("change",function(){						
						for(var i=0;i<transcript_data.length;i++){
							var transcript_id	= transcript_data[i].transcript_id;
							var frameshift		= transcript_data[i].putative_frameshift;
							if(frameshift){
								$(transcript_id).checked=$("all_fs_transcripts").checked;
							}
						}					
					});			
					$("all_transcripts").observe("change",function(){
						for(var i=0;i<transcript_data.length;i++){
							var transcript_id	= transcript_data[i].transcript_id;		
							$(transcript_id).checked=$("all_transcripts").checked;
						}	
					});
				//]]>
				</script>
				</div>
				
				</div>
			</dd>	
			<?php endif; ?>			
		</dl>
		<br/>
		<?php
		if(isset($error)){
			echo "<span class='error'>".$error."</span>\n";
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
		<input type="submit" value="Perform frameshift correction" />
		</form>
		<?php endif;?>


		<?php endif;?>

	</div>
</div>	
</div>
