<div>
<h2>Similarity hits</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment"); ?>

	<h3>Transcript overview</h3>
	<div class="subdiv">
		<dl class="standard">
			<dt>Transcript id</dt>
			<dd><?php echo $html->link($transcript_info['transcript_id'],array("controller"=>"trapid","action"=>"transcript",$exp_id,$transcript_info['transcript_id'])); ?></dd>
			<dt>Gene family</dt>
			<dd>
			<?php
			if($transcript_info['gf_id']==""){echo "<span class='disabled'>Unavailable</span>\n";}
			else{echo $html->link($transcript_info['gf_id'],array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$transcript_info['gf_id']));}		
			?>
			</dd>	
		</dl>
	</div>		
	
	<?php
		if(isset($error)){
			echo "<br/><br/><span class='error'>".$error."</span><br/><br/>";
		}
		if(isset($message)){
			echo "<br/><br/><span class='message'>".$message."</span><br/><br/>";
		}		
	?>	


	<h3>Similarity hits</h3>
	<div class="subdiv">
		<?php
		//pr($transcript_info);
		//pr($exp_info);		
		?>
		<table cellpadding="0" cellspacing="0" style="width:1100px;">
			<tr>
				<th>Gene id</th>
				<th style='width:15%'>E-value</th>
				<th>Alignment length</th>
				<th>Percent identity</th>
				<th>Gene family(external)</th>
				<th>#genes</th>
				<th>Gene family(TRAPID)</th>				
				<th>#transcripts</th>
				<th style='width:15%'>Select as GF</th>
			</tr>
			<?php
			$prev_gf		= null;
			$altrow			= null;		
			foreach($sim_hits as $gene_id=>$simd){
				/*if($exp_info['genefamily_type']=="HOM"){							
					$plaza_gf_id	= $gf_ids[$gene_id];	
					if($prev_gf==null){$prev_gf=$plaza_gf_id;}
					if($prev_gf!=$plaza_gf_id){
						$prev_gf	= $plaza_gf_id;
						if($altrow==null){$altrow=" class='altrow' ";}
						else{$altrow=null;}
					}
				}
				*/
									
				foreach($simd as $index=>$sim){
				    echo "<tr $altrow>";
				    //gene identifier
    				    if($index==0){
				    	if(!$exp_info['allow_linkout']){
					    echo "<td>".$gene_id."</td>";
				    	}
				    	else{
					    echo "<td>".$html->link($gene_id,
							    $exp_info['datasource_URL']."genes/view/".urlencode($gene_id))."</td>";	
				   	 }	
    				    }
    				    else{
    					echo "<td></td>";
    				    }


				    $e_value	= $sim[1];				
				    $e_value_loc	= strpos($e_value,"E");
				    if($e_value_loc){$e_value=number_format(substr($e_value,0,$e_value_loc),2).substr($e_value,$e_value_loc);}
				    else{$e_value=number_format($e_value,4);}
				    echo "<td>".$e_value."</td>";
				    echo "<td>".$sim[3]."</td>";
				    echo "<td>".round($sim[4],1)."%</td>";

				    if($exp_info['genefamily_type']=="HOM"){
					    $plaza_gf_id		= $gf_ids[$gene_id];
					    $display_change_form	= false;
					    $disabled_change_form	= null;	
					    if($prev_gf!=$plaza_gf_id){
						    $prev_gf		= $plaza_gf_id;
						    $display_change_form	= true;
					    }		

					    //PLAZA GF
					    if(!$exp_info['allow_linkout']){
						    echo "<td>".$plaza_gf_id."</td>";
					    }
					    else{
						    echo "<td>".$html->link($plaza_gf_id,
							    $exp_info['datasource_URL']."gene_families/view/".urlencode($plaza_gf_id))."</td>";
					    }
					    //NUM GENES PLAZA GF
					    echo "<td>".$plaza_gf_counts[$plaza_gf_id]."</td>";

					    $trapid_gf_id		= null;
					    //TRAPID GF & TRANSCRIPT COUNT
					    if(array_key_exists($plaza_gf_id,$transcript_gfs1)){
						    $trapid_gf_id	= $transcript_gfs1[$plaza_gf_id];
						    if($trapid_gf_id == $transcript_info['gf_id']){
							    $disabled_change_form=" disabled='disabled' ";
						    }
						    echo "<td>".$html->link($trapid_gf_id,
						    array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$trapid_gf_id))."</td>";
						    $trapid_gf_count = $transcript_gfs2[$trapid_gf_id];
						    echo "<td>".$trapid_gf_count."</td>";
					    }
					    else{
						    echo "<td><span class='disabled'>Unavailable</span></td>";
						    echo "<td><span class='disabled'>Unavailable</span></td>";
					    }

					    //change gene gene family form					
					    if($display_change_form){
						    echo "<td>";
						    echo "<div>";
						    echo $form->create("",array("action"=>"similarity_hits/".$exp_id."/".$transcript_info['transcript_id'],"type"=>"post"));
						    echo "<input type='hidden' name='plaza_gf_id' value='".$plaza_gf_id."' />";
						    if($trapid_gf_id!=null){
							    echo "<input type='hidden' name='trapid_gf_id' value='".$trapid_gf_id."' />";
						    }
						    echo "<input type='submit' value='Set as gene family' $disabled_change_form />";
						    echo "</form>";
						    echo "</div>";
						    echo "</td>";
					    }
					    else{
						    echo "<td></td>";
					    }

				    }
				    else{	//IORTHO
					    echo "<td><span class='disabled'>Unavailable</span></td>";
					    echo "<td><span class='disabled'>Unavailable</span></td>";
					    echo "<td><span class='disabled'>Unavailable</span></td>";
					    echo "<td><span class='disabled'>Unavailable</span></td>";
					    echo "<td><span class='disabled'>Unavailable</span></td>";
				    }	
				    echo "</tr>\n";
				}					
											
			}
			?>
		</table>		
	</div>
</div>
</div>