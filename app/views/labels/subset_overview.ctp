<?php
	echo $javascript->link("canvasXpress/canvasXpress.min.js");
?>

<div>
<h2>Subsets overview </h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	
	<?php
	$full_counts	= array();
	foreach($data_venn as $intersection=>$count){
		$labels	= explode(";;;",$intersection);
		foreach($labels as $label){
			if(!array_key_exists($label,$full_counts)){$full_counts[$label] = 0;}
			$full_counts[$label]+=$count;
		}
	}	
	$fc_count	= count($full_counts);
	if($fc_count>4){$fc_count=4;}
		

	$data_venn_js	= array("venn"=>array("data"=>array(),"legend"=>array()));
	$info_venn_js	= array("graphType"=>"Venn","vennGroups"=>$fc_count);
	$mapping	= array();	
	$possible_chars	= array("A","B","C","D");
	$counter	= 0;	
	foreach($full_counts as $fc=>$count){
		if($counter<4){
			$curr_char	= $possible_chars[$counter];
			$mapping[$fc]	= $curr_char;
			$data_venn_js["venn"]["legend"][$curr_char]	= $fc;
		}
		$counter++;
	}	
	
	//reduce data, because not all labels might be selected.	
	$data_venn_reduced	= array();
	foreach($data_venn as $intersection=>$count){
		$labels			= explode(";;;",$intersection);
		$new_labels		= array();	
		foreach($labels as $label){
			if(array_key_exists($label,$mapping)){$new_labels[]=$label;}			
		}	
		if(count($new_labels)!=0){
			sort($new_labels);
			$new_intersection	= implode(";;;",$new_labels);
			if(array_key_exists($new_intersection,$data_venn_reduced)){$data_venn_reduced[$new_intersection]+=$count;}
			else{$data_venn_reduced[$new_intersection]=$count;}
		}
	}

	//pr($data_venn_reduced);

	//ok, now use the reduced data to actually construct the JSON object, used to 
	$data_directive	= array(2=>array("A"=>0,"B"=>0,"AB"=>0),
				3=>array("A"=>0,"B"=>0,"C"=>0,"AB"=>0,"AC"=>0,"BC"=>0,"ABC"=>0),
				4=>array("A"=>0,"B"=>0,"C"=>0,"D"=>0,"AB"=>0,"AC"=>0,"AD"=>0,"BC"=>0,"BD"=>0,"CD"=>0,"ABC"=>0,"ABD"=>0,"ACD"=>0,"BCD"=>0,"ABCD"=>0)
				);
	$data_venn_js["venn"]["data"] = $data_directive[$fc_count];	

	foreach($data_venn_reduced as $intersection=>$count){	
		$labels			= explode(";;;",$intersection);
		$new_labels		= array();		
		foreach($labels as $label){
			$char		= $mapping[$label];
			$new_labels[]	= $char;
		}
		sort($new_labels);
		$new_label_string	= implode("",$new_labels);	
		$data_venn_js["venn"]["data"][$new_label_string] = $count;
	}


	//pr($data_venn);
	//pr($data_venn_js);

	
	?>

	<h3>Non-intersection subsets</h3>
	<div class="subdiv">	
		<div style="float:left;width:500px;">
		<table cellspacing="0" cellpadding="0" style="width:400px;">
			<tr>
				<th style="width:10%">Select</th>
				<th style="width:60%">Label</th>
				<th style="width:30%">#Transcripts</th>
			</tr>
			<?php
			$i=0;		
			foreach($full_counts as $fc=>$count){
				$alt	= null; if($i++%2==0){$alt=" class='altrow2'";}
				$sel	= " checked='checked' "; if($i>4){$sel=null;}
				echo "<tr $alt>\n";
				echo "<td><input type='checkbox' class='label_select' id='".$fc."' $sel></td>";
				echo "<td>".$html->link($fc,array("controller"=>"labels","action"=>"view",$exp_id,urlencode($fc)))."</td>";
				echo "<td>".$count."</td>";
				echo "</tr>\n";
			}
			?>
		</table>
		</div>
		<div style="float:left;width:200px;"><span style='color:red;font-weight:bold;' id='comment'></span></div>
		<div style="clear:both;width:700px;">&nbsp;</div>
	</div>
	
	<h3>Intersection subsets</h3>
	<div class="subdiv">
		<table cellspacing="0" cellpadding="0" style="width:400px;">
			<tr>
				<th style="width:70%">Label(s)</th>
				<th style="width:30%">#Transcripts</th>
			</tr>
			<?php
			$i=0;		
			foreach($data_venn as $intersection=>$count){
				$alt		= null; if($i++%2==0){$alt=" class='altrow2'";}
				echo "<tr $alt>\n";
				$labels		= explode(";;;",$intersection);
				$labels_url	= implode("/label/",$labels);
				$label_string	= implode(" + ",$labels);				
				echo "<td>".$html->link($label_string,array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",$labels_url))."</td>";
				echo "<td>".$count."</td>";
				echo "</tr>\n";
			}
			?>
		</table>
	</div>

	<div id="canvas_container" style="height:500px;"><canvas id="venn_canvas" width="600" height="500"></canvas></div>
	<script type="text/javascript">
		//<![CDATA[
		var venn_data	= <?php echo $javascript->object($data_venn_js); ?>;
		var venn_info	= <?php echo $javascript->object($info_venn_js); ?>;
		var data_venn_full	= <?php echo $javascript->object($data_venn); ?>;
		var fc_count	= <?php echo $fc_count;?>;


		if(fc_count>=2 && fc_count<=4){
			new CanvasXpress("venn_canvas",venn_data,venn_info);	
		}		

		document.observe('dom:loaded', function() {
 	               	$$('.label_select').each(function(element) {
				element.observe("change",function(event){
					//count number of elements
					var count		= 0;
					var selected_labels	= new Array();
					$$('.label_select').each(function(e){if(e.checked){count++;selected_labels.push(e.id)}});
					if(count>=2 && count<=4){
						$("comment").innerHTML = "";					
						updateVennDiagram(selected_labels);
					}
					else if(count<2){
						$("comment").innerHTML = "Cannot display Venn diagram (too few selected labels)";
					}
					else{
						$("comment").innerHTML = "Cannot display Venn diagram (too many selected labels)";
					}
				});
			});
		});				

		function updateVennDiagram(selected_labels){
			var new_venn_data	= JSON.parse(JSON.stringify(venn_data));	//deep copy
			var new_venn_info	= JSON.parse(JSON.stringify(venn_info));	//deep copy	
			//set correct number of Venn groups	
			new_venn_info.vennGroups	= selected_labels.length;
			//ok, now update the data itself, so only selected labels are taken into account.
			//we need to do this by updating the "new_venn_data", using the full data 
			//first step, create a mapping.
			var mapping		= new Hash();
			var possible_chars	= ["A","B","C","D"];
			
			//alert(JSON.stringify(new_venn_data.venn.legend));
			try{
				delete new_venn_data.venn.legend.A;
				delete new_venn_data.venn.legend.B;
				delete new_venn_data.venn.legend.C;
				delete new_venn_data.venn.legend.D;
			}
			catch(exc){}
			//alert(JSON.stringify(new_venn_data.venn.legend));
			for(var i=0;i<selected_labels.length;i++){
				var c	= possible_chars[i];
				mapping.set(selected_labels[i],c);
				new_venn_data.venn.legend[c]	= selected_labels[i];					
			}
			//alert(JSON.stringify(new_venn_data.venn.legend));
			var dvf		= new Hash(data_venn_full);		
			var dvf_keys	= dvf.keys();
			var dvf_reduc	= new Hash();
			for(var i=0;i<dvf_keys.length;i++){	
				var labels	= dvf_keys[i].split(";;;");
				var count	= dvf.get(dvf_keys[i]);
				var acc_labels	= new Array();
				for(var j=0;j<labels.length;j++){
					var label	= labels[j];
					var index	= selected_labels.indexOf(label);
					if(index!=-1){acc_labels.push(label);}	
				}
				if(acc_labels.length!=0){
					acc_labels.sort();
					var label_string	= acc_labels.join(";;;");
					if(dvf_reduc.get(label_string)==undefined){dvf_reduc.set(label_string,count);}
					else{dvf_reduc.set(label_string,dvf_reduc.get(label_string)+count);}		
				}
			}
			//clear the local venn_data cache
			var tmp	= new Hash(new_venn_data.venn.data);
			//alert(JSON.stringify(new_venn_data.venn.data));
			for(var i=0;i<tmp.keys().length;i++){
				var k	= tmp.keys()[i];
				//delete new_venn_data.venn.data[k];
				new_venn_data.venn.data[k] = 0;
			}
			//alert(JSON.stringify(new_venn_data.venn.data));
			
			//ok, the dvf_reduc hash now contains the correct counts.
			//so now we just have to generate the mapping, and than update the data.			
			for(var i=0;i<dvf_reduc.keys().length;i++){
				var key		= dvf_reduc.keys()[i];
				var labels	= key.split(";;;");
				var count	= dvf_reduc.get(key);
				var label_map	= new Array();
				for(var j=0;j<labels.length;j++){
					var label	= labels[j];
					var c		= mapping.get(label);
					label_map.push(c);
				}
				label_map.sort();				
				var label_map_string	= ""+label_map.join("")+"";	
				//alert(key+"\t"+label_map_string);
				new_venn_data.venn.data[label_map_string] = count;			
				//alert(label_map_string+"\t"+count+"\t"+JSON.stringify(new_venn_data.venn.data));
			}
			//new_venn_data.venn.data["ABC"] = 156;
			
			$("canvas_container").innerHTML = "<canvas id='venn_canvas' width='600' height='500'></canvas>";
				
			//alert(JSON.stringify(new_venn_data));
			//alert(JSON.stringify(new_venn_info));

			new CanvasXpress("venn_canvas",new_venn_data,new_venn_info);			

		}


	
		//]]>
	</script>	
	
</div>
</div>