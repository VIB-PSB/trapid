<?php  echo $javascript->link(array('jit'));     ?>
<div>
<h2>Length distribution sequences</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>


	
	<?php if($sequence_type=="transcript"): ?>
	<h3>Options</h3>
	<div class="subdiv">
		<?php
				
		$disable_graph_type	= null; 		
		if(!(isset($meta_partial)||isset($meta_noinfo))){$disable_graph_type=" disabled='disabled' ";}			
		echo $form->create("",array("action"=>"length_distribution/".$exp_id."/".$sequence_type,"type"=>"post"));

		echo "<dl class='standard'>\n";		
		echo "<dt>#Bins</dt>\n";
		echo "<dd>\n";
		echo "<select name='num_bins' style='width:150px;' id='num_bins'>\n";
		foreach($possible_bins as $k=>$v){
			$selected	= null;
			if($k==$num_bins){$selected=" selected='selected' ";}
			echo "<option value='".$k."' $selected>".$k."</option>\n";
		}
		echo "</select>\n";
		echo "</dd>\n";
		
		echo "<dt>Meta-annotation</dt>\n";
		echo "<dd>\n";
		echo "<div>";
		echo "<input type='checkbox' "; if(isset($meta_partial)){echo "checked='checked'";} echo " name='meta_partial' id='meta_partial' />\n";
		echo "<span style='margin-left:10px;'>Display 'partial' data separately</span>\n";
		echo "<br/>";
		echo "<input type='checkbox' "; if(isset($meta_noinfo)){echo "checked='checked'";} echo " name='meta_noinfo' id='meta_noinfo' />\n";
		echo "<span style='margin-left:10px;'>Display 'non information' data separately</span>\n";
		echo "</div>";
		echo "</dd>\n";	
		echo "<dt>Graph type</dt>\n";
		echo "<dd>\n";
		echo "<select name='graphtype' style='width:150px;' id='graphtype' $disable_graph_type>\n";
		foreach($possible_graphtypes as $k=>$v){
			$selected	= null;
			if($k==$graphtype){$selected=" selected='selected' ";}
			echo "<option value='".$k."' $selected>".$k."</option>\n";
		}
		echo "</select>\n";
		echo "</dd>\n";		
		echo "</dl>\n";
		echo "<input type='submit' value='Update graph'/>\n";			
		echo "</form>\n";
		?>	
	</div>
	<script type="text/javascript"> 
	//<![CDATA[

	$("meta_partial").observe("change",function(){
	     checkDisableGraphtype();
	});		
	$("meta_noinfo").observe("change",function(){
		checkDisableGraphtype();
	});
	function checkDisableGraphtype(){
		if(!($("meta_partial").checked || $("meta_noinfo").checked )){
			$("graphtype").disabled=true;
		}
		else{
			$("graphtype").disabled=false;delete $("graphtype");	
		}	
	}	
	

	//]]>
	</script>	
	<br/>

	<h3>Transcript sequences</h3>
	<div class="subdiv">	
		<div><ul id="infovis1_list"></ul></div>		
		<div id="infovis1" class="infovis" style="width:900px;height:630px;"></div>				
	</div>
	<h3>Legend</h3>
	<div class="subdiv">
		<div class="subdiv" style="padding-left:15px;">
		<dl class="standard">
			<dt>X-axis</dt><dd>Transcript nucleotide length</dd>
			<dt>Y-axis</dt><dd>#Transcripts</dd>
		</dl>
		</div>
		<div class="legend_div">
			<ul id="legend_list"></ul>
		</div>
	</div>

	
	<script type="text/javascript">
	//<![CDATA[

	var transcript_lengths	= <?php echo $javascript->object($bins_transcript); ?>;
	var offset_bars		= <?php echo $bars_offset;?>;


	var barchart_transcript	= new $jit.BarChart({
	    injectInto:"infovis1",
	    animate:true,
	    orientation:"vertical",
	    barsOffset:offset_bars,   
	    Margin:{
		top:10,
		left:20,
		right:20,
		bottom:60
	    },
	    labelOffset:5,
	    type:'<?php echo $graphtype;?>:gradient',
	    showAggregates:true,
	    showLabels:true,    
	    Label:{
		type:'Native',
		size:10,
		family:'Arial',
		color:'black',
		orientation:"vertical"
	    },
	    Tips:{
		enable:true,	
		onShow:function(tip,elem){
		    tip.innerHTML = "<div style='background-color:white;color:black;padding:10px;'>#Transcripts with transcript length in range ["+elem.label+"] : <b>"+elem.value+"</b></div>";
		}
	    },	
	    Events:{
		enable:true,
		onClick:function(node,eventInfo,e){
		  // printData(node);
		  redirect_transcript(node);
		}
	    }					    							
	});
	barchart_transcript.loadJSON(transcript_lengths);

	var transcript_legend		= barchart_transcript.getLegend();
	var transcript_listitems	= [];
	for(var name in transcript_legend){	
		transcript_listitems.push('<div class=\'querycolor\' style=\'background-color:' + transcript_legend[name] +'\'>&nbsp;</div>' + name);	
	}
	$jit.id('legend_list').innerHTML = '<li>' + transcript_listitems.join('</li><li>') + '</li>';



	function redirect_transcript(node){ 
		var bin_split		= node.label.split(",");
		var min_bin_size	= bin_split[0];
		var max_bin_size	= bin_split[1];
		var basic_url		= <?php echo "'".$html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id),true)."'";?>;
		var final_url		= basic_url+"/min_transcript_length/"+min_bin_size+"/max_transcript_length/"+max_bin_size+"/";
		window.location		= final_url;
	}


	//]]>
	</script>
	




	<?php elseif($sequence_type=="orf"):?>
	<h3>Options</h3>
	<div class="subdiv">
		<?php
			
		$disable_normalize_data	= null;	if($selected_ref_species==""){$disable_normalize_data=" disabled='disabled' ";}
				
		echo $form->create("",array("action"=>"length_distribution/".$exp_id."/".$sequence_type,"type"=>"post"));
		echo "<dl class='standard'>\n";		
		echo "<dt>#Bins</dt>\n";
		echo "<dd>\n";
		echo "<select name='num_bins' style='width:150px;' id='num_bins'>\n";
		foreach($possible_bins as $k=>$v){
			$selected	= null;
			if($k==$num_bins){$selected=" selected='selected' ";}
			echo "<option value='".$k."' $selected>".$k."</option>\n";
		}
		echo "</select>\n";
		echo "</dd>\n";
	
		echo "<dt>Reference species</dt>\n";
		echo "<dd>\n";
		echo "<div>\n";
		echo "<select name='reference_species' id='reference_species' style='width:150px;'>\n";
		echo "<option value=''>None</option>\n";
		foreach($available_reference_species as $s=>$cn){
			$selected	= null;
			if($s==$selected_ref_species){$selected=" selected='selected' ";}
			echo "<option value='".$s."' $selected>".$cn."</option>\n";
		}
		echo "</select>\n";
		echo "<br/>\n";
		echo "<input type='checkbox' ";if(isset($normalize_data)){echo "checked='checked'";} echo " name='normalize' id='normalize' $disable_normalize_data />\n";
		echo "<span style='margin-left:10px;'>Normalize data (by number of transcripts/genes)</span>\n";
		echo "</div>\n";	
		echo "</dd>\n";		
		echo "</dl>\n";
		echo "<input type='submit' value='Update graph'/>\n";			
		echo "</form>\n";
		?>	
	</div>
	<script type="text/javascript"> 
	//<![CDATA[
	$("reference_species").observe("change",function(){
	    var val	= $("reference_species").value;	    
	    if(val==""){$("normalize").disabled = "disabled";} 		
	    else{$("normalize").disabled=false;delete $("normalize").disabled;}	 
	});		
	//]]>
	</script>	
	<br/>
	
	<h3>ORF sequences</h3>
	<div class="subdiv">
		<div><ul id="infovis2_list"></ul></div>
		<div id="infovis2" class="infovis" style="width:900px;height:630px;"></div>
	</div>
	<h3>Legend</h3>
	<div class="subdiv">
		<div class="subdiv" style="padding-left:15px;">
		<dl class="standard">
			<dt>X-axis</dt><dd>ORF nucleotide length</dd>
			<dt>Y-axis</dt><dd>#Transcripts</dd>
		</dl>
		</div>
		<div class="legend_div">
			<ul id="legend_list"></ul>
		</div>
	</div>

	
	<script type="text/javascript">
	//<![CDATA[

	var orf_lengths		= <?php echo $javascript->object($bins_orf); ?>;
	var offset_bars		= <?php echo $bars_offset;?>;

	var barchart_orf	= new $jit.BarChart({
	    injectInto:"infovis2",
	    animate:true,
	    orientation:"vertical",
	    barsOffset:offset_bars,
	    Margin:{
		top:10,
		left:20,
		right:20,
		bottom:60
	    },
	    labelOffset:5,
	    type:'<?php echo $graphtype;?>:gradient',
	    showAggregates:true,
	    showLabels:true,  
	    Label:{
		type:'Native',
		size:10,
		family:'Arial',
		color:'black',
		orientation:'vertical'
	    },
	    Tips:{
		enable:true,	
		onShow:function(tip,elem){
		     tip.innerHTML = "<div style='background-color:white;color:black;padding:10px;'>#Transcripts with ORF length in range ["+elem.label+"] : <b>"+elem.value+"</b></div>";	   	   
		}
	    },	
	    Events:{
		enable:true,
		onClick:function(node,eventInfo,e){
		  // printData(node);
		  redirect_orf(node);
		}
	    }					    							
	});
	barchart_orf.loadJSON(orf_lengths);

	var orf_legend			= barchart_orf.getLegend();
	var orf_listitems	= [];
	for(var name in orf_legend){	
		orf_listitems.push('<div class=\'querycolor\' style=\'background-color:' + orf_legend[name] +'\'>&nbsp;</div>' + name);	
	}
	$jit.id('legend_list').innerHTML = '<li>' + orf_listitems.join('</li><li>') + '</li>';

	function redirect_orf(node){
		var bin_split		= node.label.split(",");
		var min_bin_size	= bin_split[0];
		var max_bin_size	= bin_split[1];
		var basic_url		= <?php echo "'".$html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id),true)."'";?>;
		var final_url		= basic_url+"/min_orf_length/"+min_bin_size+"/max_orf_length/"+max_bin_size+"/";
		window.location		= final_url;
	}


	//]]>
	</script>
	

	<?php endif;?>





</div>
</div>
