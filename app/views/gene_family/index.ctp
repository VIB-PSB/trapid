<div>
<h2>Gene families and phylogenetic profile</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	<h3>Overview</h3>
	<div class="subdiv">	
		<?php $paginator->options(array("url"=>$this->passedArgs));?>
		<table cellpadding="0" cellspacing="0" style="width:900px">
			<tr>
				<th style="width:20%"><?php echo $paginator->sort("Gene Family","gf_id");?></th>
				<th style="width:15%"><?php echo $paginator->sort("#Transcripts","num_transcripts");?></th>
				<?php
				if($exp_info["genefamily_type"]=="HOM"){
					echo "<th style='width:15%'>External GF</th>\n";
					echo "<th style='width:10%'>#Genes external GF</th>\n";
					echo "<th style='width:10%'>#Species external GF</th>\n";
				}
				else{
					echo "<th style='width:10%'>#Genes IOrtho group</th>\n";
					echo "<th style='width:10%'>#Species IOrtho group</th>\n";
				}
				?>			
				<th style="width:10%">Computed MSA</th>
				<th style="width:10%">Computed tree</th>
			</tr>
			<?php 
			foreach($gene_families as $gene_family){
			echo "<tr>";
			echo "<td>".$html->link($gene_family['GeneFamilies']['gf_id'],array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gene_family['GeneFamilies']['gf_id']))."</td>";
			echo "<td>".$gene_family['GeneFamilies']['num_transcripts']."</td>";

			
			if($exp_info['genefamily_type']=="HOM"){
			    if($exp_info['allow_linkout']){
				    echo "<td>".$html->link($gene_family['GeneFamilies']['plaza_gf_id'],$exp_info["datasource_URL"]."/gene_families/view/".$gene_family['GeneFamilies']['plaza_gf_id'])."</td>\n";
			    }
			    else{
				    echo "<td>".$gene_family['GeneFamilies']['plaza_gf_id']."</td>\n";
			    }			
			    echo "<td>".$gf_gene_counts[$gene_family['GeneFamilies']['plaza_gf_id']]."</td>\n";
			    echo "<td>".$gf_species_counts[$gene_family['GeneFamilies']['plaza_gf_id']]."</td>\n";
			}
			else{
			    echo "<td>".$gf_gene_counts[$gene_family['GeneFamilies']['gf_id']]."</td>\n";
			    echo "<td>".$gf_species_counts[$gene_family['GeneFamilies']['gf_id']]."</td>\n";		
			}


			if($gene_family['GeneFamilies']['msa']){echo "<td><span style='font-weight:bold;color:green'>V</span></td>\n";}
			else{echo "<td><span style='font-weight:bold;color:red'>X</span></td>\n";}
			if($gene_family['GeneFamilies']['tree']){echo "<td><span style='font-weight:bold;color:green'>V</span></td>\n";}
			else{echo "<td><span style='font-weight:bold;color:red'>X</span></td>\n";}		
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

	</div>
</div>
</div>
