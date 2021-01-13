<?php
//    echo $this->Html->script('prototype-1.7.0.0');
	echo $this->Html->script("canvasXpress/canvasXpress.min.js");
?>

<div>
<div class="page-header">
    <h1 class="text-primary">Subsets overview </h1>
</div>
<div class="subdiv">
    <?php // echo $this->element("trapid_experiment");?>

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

	// pr($data_venn_reduced);

	//ok, now use the reduced data to actually construct the JSON object, used to
	$data_directive	= array(2=>array("A"=>0,"B"=>0,"AB"=>0),
				3=>array("A"=>0,"B"=>0,"C"=>0,"AB"=>0,"AC"=>0,"BC"=>0,"ABC"=>0),
				4=>array("A"=>0,"B"=>0,"C"=>0,"D"=>0,"AB"=>0,"AC"=>0,"AD"=>0,"BC"=>0,"BD"=>0,"CD"=>0,"ABC"=>0,"ABD"=>0,"ACD"=>0,"BCD"=>0,"ABCD"=>0)
				);
	$data_venn_js["venn"]["data"] = array();
	// Only set if more than 1 subset are defined
	if($fc_count > 1) {
        $data_venn_js["venn"]["data"] = $data_directive[$fc_count];
    }

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

	<h3>Subsets table</h3>
    <p class="text-justify">Selected subsets in the <code>Show</code> column are shown on the Venn diagram. </p>
	<div class="subdiv">
        <div class="row">
<!--		<table class="table table-striped table-bordered">-->
            <div class="col-md-4">
		<table style="font-size:88%;" id="label_table" class="table table-striped table-hover table-condensed table-bordered">
			<thead>
				<th>Label</th>
				<th>#Transcripts</th>
				<th style="width:6%;">Show</th>
				<th style="width:7%;">Delete</th>
			</thead>
            <tbody>
			<?php
			$i=0;		
			foreach($full_counts as $fc=>$count){
				$alt	= null; if($i++%2==0){$alt=" class='altrow2'";}
				$sel	= " checked='checked' "; if($i>4){$sel=null;}
				echo "<tr $alt>\n";
				echo "<td>".$this->Html->link($fc,array("controller"=>"labels","action"=>"view",$exp_id,urlencode($fc)))."</td>";
				echo "<td>".$count."</td>";
				echo "<td class='text-center'><input type='checkbox' class='label_select' id='".$fc."' $sel></td>";
                echo "<td style=\"text-align:center;\">".$this->Html->link("<span class='material-icons'>delete</span>",
                        array("controller"=>"labels","action"=>"delete_label", $exp_id, $fc),
                        array("style"=>"color: #666;", "escape"=>false, "title"=>"Delete label"),
                        "Are you sure you want to delete label '".$fc."'?")."</td>";
				echo "</tr>\n";
			}
			?>
            </tbody>
		</table>
            </div>
            <div class="col-md-offset-2 col-md-4">
                <span class="text-danger" style='font-weight:bold;' id='comment'></span>
                <div id="canvas_container" style="height:500px;    margin-left: auto;    margin-right: auto;"><canvas id="venn_canvas" width="600" height="500"></canvas></div>
            </div>

        </div>
	<h3>Subset intersections</h3>
        <p class="text-justify">Click on any of the links in the table below to view transcripts that are in the corresponding subset(s). </p>
	<div class="subdiv">
		<table style="font-size:88%;" class="table table-striped table-hover table-condensed table-bordered">
            <thead>
			<tr>
				<th>Label(s)</th>
				<th>#Transcripts</th>
			</tr>
            </thead>
			<?php
			$i=0;		
			foreach($data_venn as $intersection=>$count){
				$alt		= null; if($i++%2==0){$alt=" class='altrow2'";}
				echo "<tr $alt>\n";
				$labels		= explode(";;;",$intersection);
				$labels_url	= implode("/label/",$labels);
				$label_string	= implode(" + ",$labels);
				echo "<td><a href='".urldecode($this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",$labels_url)))."'>".$label_string."</a></td>";
				echo "<td>".$count."</td>";
				echo "</tr>\n";
			}
			?>
		</table>
	</div>

	<script type="text/javascript">
		// Trying to get rid of prototype...
		//<![CDATA[
		var venn_data	= <?php echo json_encode($data_venn_js); ?>;
		var venn_info	= <?php echo json_encode($info_venn_js); ?>;
		var data_venn_full	= <?php echo json_encode($data_venn); ?>;
		var fc_count	= <?php echo $fc_count;?>;

		// Create Venn diagram if there are between 2 and 4 labels selected
		if(fc_count>=2 && fc_count<=4){
			new CanvasXpress("venn_canvas", venn_data, venn_info);
		}

		function get_selected_labels(){
            var total_checked = $('.label_select:checkbox:checked');
            return(total_checked);
        }

        $(document).ready(function () {
            $( "#label_table" ).change(function() {
                var selected = get_selected_labels();
                if (selected.length >=2 && selected.length <=4){
                    // console.log("Update chart");
                    $("#comment").html("");
                    updateVennDiagram(selected);
                }
                else if(selected.length < 2){
                    $("#comment").html("Too few selected labels: cannot display Venn diagram. ");
                }
                else{
                    $("#comment").html("Too many selected labels: cannot display Venn diagram. ");
                }
            });
            });


        function updateVennDiagram(selected_labels){
            var new_venn_data	= JSON.parse(JSON.stringify(venn_data));	//deep copy
            var new_venn_info	= JSON.parse(JSON.stringify(venn_info));	//deep copy
            // Set correct number of Venn groups
            new_venn_info.vennGroups	= selected_labels.length;
            // Get IDs of currently selected labels
            selected_labels_ids = [];
            selected_labels.each(function(){ selected_labels_ids.push(this.id); });
            // Now update the data itself, so only selected labels are taken into account.
            // We need to do this by updating `new_venn_data` using the full data
            // First step, create a mapping.
            var mapping		= new Object();
            var possible_chars	= ["A","B","C","D"];
            // console.log(JSON.stringify(new_venn_data.venn.legend));  // {"A":"a","B":"d","C":"b","D":"e"}
            try{
                delete new_venn_data.venn.legend.A;
                delete new_venn_data.venn.legend.B;
                delete new_venn_data.venn.legend.C;
                delete new_venn_data.venn.legend.D;
            }
            catch(exc){}
            // console.log(JSON.stringify(new_venn_data.venn.legend));  // {}
            for(var i=0;i<selected_labels.length;i++){
                var c	= possible_chars[i];
                mapping[selected_labels[i].id] = c;
                new_venn_data.venn.legend[c]	= selected_labels[i].id;
            }
            // console.log(JSON.stringify(new_venn_data.venn.legend));  // {"A":"d","B":"b","C":"e"}
            var dvf		= new Object(data_venn_full);
            var dvf_keys	= Object.keys(dvf);
            // console.log(JSON.stringify(dvf_keys));
            var dvf_reduc	= new Object();
            for(var i=0;i<dvf_keys.length;i++){
                var labels	= dvf_keys[i].split(";;;");
                var count	= dvf[dvf_keys[i]];
                var acc_labels	= new Array();
                for(var j=0;j<labels.length;j++){
                    var label	= labels[j];
                    var index	= selected_labels_ids.indexOf(label);
                    if(index!=-1){acc_labels.push(label);}
                }
                if(acc_labels.length!=0){
                    acc_labels.sort();
                    var label_string	= acc_labels.join(";;;");
                    if(dvf_reduc[label_string]===undefined){
                        dvf_reduc[label_string]=count;
                    }
                    else {
                        dvf_reduc[label_string] = dvf_reduc[label_string]+count;
                    }
                }
            }
            // Clear the local venn_data cache
            var tmp	= new Object(new_venn_data.venn.data);
            // console.log(JSON.stringify(new_venn_data.venn.data));  // {"A":0,"B":0,"C":0,"D":0,"AB":0,"AC":0,"AD":0,"BC":0,"BD":0,"CD":0,"ABC":0,"ABD":0,"ACD":0,"BCD":0,"ABCD":0}
            for(var i=0;i<Object.keys(tmp).length;i++){
                var k	= Object.keys(tmp)[i];
                // Delete new_venn_data.venn.data[k];
                new_venn_data.venn.data[k] = 0;
            }
            // console.log(JSON.stringify(new_venn_data.venn.data));

            // Ok, the `dvf_reduc` hash now contains the correct counts.
            // Now we just have to generate the mapping, and then update the data.
            for(var i=0;i<Object.keys(dvf_reduc).length;i++){
                var key		= Object.keys(dvf_reduc)[i];
                var labels	= key.split(";;;");
                var count	= dvf_reduc[key];
                var label_map	= new Array();
                for(var j=0;j<labels.length;j++){
                    var label	= labels[j];
                    var c		= mapping[label];
                    label_map.push(c);
                }
                label_map.sort();
                var label_map_string	= ""+label_map.join("")+"";
                // console.log(key+"\t"+label_map_string);  // d	A
                new_venn_data.venn.data[label_map_string] = count;
                // console.log(label_map_string+"\t"+count+"\t"+JSON.stringify(new_venn_data.venn.data)); // A	3	{"A":3,"B":0,"C":0,"D":0,"AB":0,"AC":0,"AD":0,"BC":0,"BD":0,"CD":0,"ABC":0,"ABD":0,"ACD":0,"BCD":0,"ABCD":0}
            }
            //new_venn_data.venn.data["ABC"] = 156;

            $("#canvas_container").html("<canvas id='venn_canvas' width='600' height='500'></canvas>");

            // console.log(JSON.stringify(new_venn_data));  // {"venn":{"data":{"A":3,"B":0,"C":4,"D":0,"AB":0,"AC":0,"AD":0,"BC":0,"BD":0,"CD":0,"ABC":3,"ABD":0,"ACD":0,"BCD":0,"ABCD":0},"legend":{"A":"d","B":"b","C":"e"}}}
            // console.log(JSON.stringify(new_venn_info));  //  {"graphType":"Venn","vennGroups":3}

            new CanvasXpress("venn_canvas", new_venn_data, new_venn_info);

        }


		 /* document.observe('dom:loaded', function() {
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

			alert(JSON.stringify(new_venn_data.venn.legend));
			try{
				delete new_venn_data.venn.legend.A;
				delete new_venn_data.venn.legend.B;
				delete new_venn_data.venn.legend.C;
				delete new_venn_data.venn.legend.D;
			}
			catch(exc){}
			alert(JSON.stringify(new_venn_data.venn.legend));
			for(var i=0;i<selected_labels.length;i++){
				var c	= possible_chars[i];
				mapping.set(selected_labels[i],c);
				new_venn_data.venn.legend[c]	= selected_labels[i];
			}
			alert(JSON.stringify(new_venn_data.venn.legend));
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
			alert(JSON.stringify(new_venn_data.venn.data));

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
				alert(key+"\t"+label_map_string);
				new_venn_data.venn.data[label_map_string] = count;
				alert(label_map_string+"\t"+count+"\t"+JSON.stringify(new_venn_data.venn.data));
			}
			//new_venn_data.venn.data["ABC"] = 156;

			$("canvas_container").innerHTML = "<canvas id='venn_canvas' width='600' height='500'></canvas>";

			alert(JSON.stringify(new_venn_data));
			alert(JSON.stringify(new_venn_info));

			new CanvasXpress("venn_canvas",new_venn_data,new_venn_info);

		}  */

		//]]>
	</script>	
	
</div>
</div>