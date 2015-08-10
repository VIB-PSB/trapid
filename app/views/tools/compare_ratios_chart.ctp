<div>
<h2><?php echo $available_types[$type];?> ratios subsets</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	<h3>Subset selection</h3>
	<div class="subdiv">
		<?php
		if(isset($error)){echo "<span class='error'>".$error."</span>\n";}
		echo $form->create("",array("action"=>"compare_ratios_chart/".$exp_id."/".$type,"type"=>"post"));
		echo "<dl class='standard'>\n";	
		echo "<dt>Subset 1</dt>\n";
		echo "<dd>";
		echo "<select name='subset1' style='width:300px;'>";
		foreach($subsets as $subset=>$count){
			if(isset($subset1) && $subset1==$subset){
				echo "<option value='".$subset."' selected='selected'>".$subset." (".$count." transcripts)</option>\n";	
			}
			else{
				echo "<option value='".$subset."'>".$subset." (".$count." transcripts)</option>\n";
			}
		}		
		echo "</select>";
		echo "</dd>\n";
		echo "<dt>Subset 2</dt>\n";
		echo "<dd>";
		echo "<select name='subset2' style='width:300px;'>";
		foreach($subsets as $subset=>$count){
			if(isset($subset2) && $subset2==$subset){
				echo "<option value='".$subset."' selected='selected'>".$subset." (".$count." transcripts)</option>\n";	
			}
			else{
				echo "<option value='".$subset."'>".$subset." (".$count." transcripts)</option>\n";
			}
		}		
		echo "</select>";
		echo "</dd>\n";
		if($type=="go"){
			echo "<dt>GO category</dt>\n";
			echo "<dd>";
			echo "<select name='go_category' style='width:300px;'>";
			foreach($possible_go_types as $k=>$v){
				if(isset($go_category) && $go_category==$k){
					echo "<option value='".$k."' selected='selected'>".$v."</option>\n";
				}
				else{
					echo "<option value='".$k."'>".$v."</option>\n";
				}	
			}
			echo "</select>";
			echo "</dd>";
			echo "<dt>GO depth</dt>\n";
			echo "<dd>";
			echo "<select name='go_depth' style='width:300px;'>";
			for($i=1;$i<=$max_go_depth;$i++){
				if(isset($go_depth) && $go_depth==$i){
					echo "<option value='".$i."' selected='selected'>".$i."</option>\n";
				}
				else{
					echo "<option value='".$i."'>".$i."</option>\n";
				}
			}
			echo "</select>";
			echo "<span style='margin-left:25px;'>More information on the <a target='_blank' href='http://www.geneontology.org/GO.ontology.structure.shtml#go-as-a-graph'>Gene Ontology website</a></span>";
			echo "</dd>\n";	
			echo "<dt>Minimum ratio</dt>\n";
			echo "<dd>";
			$min_cov_values=array("none"=>"No minimum coverage");
			for($i=1;$i<10;$i++){$min_cov_values[$i]=$i."%";}
			for($i=10;$i<100;$i+=10){$min_cov_values[$i]=$i."%";}
			echo "<select name='min_coverage' style='width:300px;'>";
			foreach($min_cov_values as $k=>$v){
				if(isset($min_coverage) && $min_coverage==$k){
					echo "<option value='".$k."' selected='selected'>".$v."</option>\n";
				}
				else{
					echo "<option value='".$k."'>".$v."</option>\n";
				}
			}
			echo "</select>\n";	
			echo "<span style='margin-left:20px;'>Minimum ratio in both subsets</span>";		
			echo "</dd>\n";	
			echo "<dt>Present in both subsets</dt>\n";
			echo "<dd>";
			$both_present_check	= null; if(isset($both_present)){$both_present_check = " checked='checked' ";}
			echo "<input type='checkbox' name='both_present' $both_present_check />\n";
			echo "</dd>";			
		}
		echo "</dl>\n";			
		echo "<br/><br/>\n";
		echo "<input type='submit' style='width:200px;' value='Compute ".$available_types[$type]." ratios' />\n";	
		echo "</form>\n";
		?>	
	</div>



	<?php if(isset($result)) : ?>		
		<h3>Results</h3>
		<div class='subdiv'>

		<?php if($num_selected_gos==0): ?>
		 	<span class='error'>No results found</span>

		<?php else : ?>

		<?php 
			//echo $javascript->link(array('jit'));
			echo $javascript->link("canvasXpress/canvasXpress.min.js","canvasXpress/ext-canvasXpress.js");
		?>

		<!--<div><ul id="infovis_list"></ul></div>-->

		
		<?php
		$height	= 200+60* $num_selected_gos;
		//echo "<div id='infovis' class='infovis' style='width:900px;height:".$height."px;'></div>\n";	
		echo "<canvas id='canvas1' width='900' height='".$height."'></canvas>\n";		
		?>
		<div style='clear:both; width:900px;' id='clearer'>&nbsp;</div>
		
		<script type="text/javascript">
		//<![CDATA[
	
		new CanvasXpress("canvas1",{			
				"y":<?php echo $javascript->object($result); ?>,					
			},
			{
				"graphType": "Bar",
                  		"showAnimation": false,
                  		"graphOrientation": "horizontal",                  		
                  		"title": "GO ratios subsets",
                  		"smpHairlineColor": false,
				"varTitle":"Ratio",
                  		"smpTitle": "GO categories",
                 		"plotByVariable": true,
                  		"smpLabelInterval": 2,
				"autoScaleFont":false,			
			},
			{										
				click:function(o){
					redirect(o["y"]);
				}									
			}
		
		);
		
		function redirect(o){
			var basic_url		= <?php echo "'".$html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id),true)."'";?>;
			var go_id		= o["vars"].toString().split(" ")[0];
			var subset		= o["smps"].toString();
			if(subset!="All"){
				var go_web		= go_id.replace(":","-");
				var final_url		= basic_url+"/go/"+go_web+"/label/"+escape(subset);
				//alert(final_url);
				window.location		= final_url;
			}
		}
	

		//]]>
		</script>

		<?php endif;?>	

		</div>	
	<?php endif; ?>


</div>
</div>
