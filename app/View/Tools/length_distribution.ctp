<?php  echo $this->Html->script(array('jit'));     ?>
<?php
// TODO: host locally
// Highcharts
echo $this->Html->script('https://code.highcharts.com/highcharts.js');
echo $this->Html->script('https://code.highcharts.com/modules/exporting.js');
?>
<div class="page-header">
    <h1 class="text-primary">Sequence length distribution</h1>
</div>
<div class="subdiv">

<section class="page-section">
<!--    <h3>Work in progress! </h3>-->
    <?php
        echo $this->Form->create(false, array("url"=>array("controller"=>"tools", "action"=>"length_distribution", $exp_id),"type"=>"post", "id"=>"graph-update-form"));
    ?>
    <div class="row">

        <div id="options-col" class="col-md-3 vcenter">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Display settings</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <strong>Sequence type</strong>
                        <div class="radio">
                            <label>
                                <input id="sequence-type-tr" name="sequence_type" type="radio" value="transcript" checked> Transcript &nbsp;
                            </label>
                            <?php if($exp_info['process_state'] == 'finished'): ?>
                                <label>
                                    <input id="sequence-type-orf" name="sequence_type" type="radio" value="orf"> ORF
                                </label>
                            <?php else: ?>
                                <label title="Cannot visualize ORF length distribution before initial processing">
                                    <input id="sequence-type-orf" name="sequence_type" type="radio" value="orf" disabled title="Cannot visualize ORF length distribution before initial processing"> ORF
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for=""><strong>Number of bins</strong>
                            (current: <span id="num-bins-value"></span>)
                        </label><br>

                        <div id="bins-row" class="row" style="">
                            <div id="bins-min" class="col-sm-2 hidden-xs text-right text-muted" style="font-size:88%;"><?php echo $range_bins[0]; ?></div>
                            <div id="bins-slider" class="col-sm-8">
                                <input id='num_bins' name='num_bins' type="range" min="<?php echo $range_bins[0]; ?>" max="<?php echo $range_bins[1]; ?>" step="5" value="<?php echo $default_bins; ?>" onchange="updateNumBins(this.value);">
                            </div>
                            <div id="bins-max" class="col-sm-2 hidden-xs text-left text-muted" style="font-size:88%;"><?php echo $range_bins[1]; ?></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="checkbox">
                            <strong>Transcript meta-annotation</strong>
                            <label>
                                <input type="checkbox" id="meta_noinfo" name="meta_noinfo"> Show 'no information'
                            </label><br>
                            <label>
                                <input type="checkbox" id="meta_partial" name="meta_partial"> Show 'partial'
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for=""><strong>Reference species (ORF)</strong></label><br>
                        <?php
                        echo "<select name='reference_species' id='reference_species' class='form-control'>\n";
                        echo "<option value=''>None</option>\n";
                        foreach($available_reference_species as $s=>$cn){
                            $selected	= null;
                            if($s==$selected_ref_species){$selected=" selected='selected' ";}
                            echo "<option value='".$s."' $selected>".$cn."</option>\n";
                        }
                        echo "</select>\n";
                        ?>
                        <div class="checkbox">
                        <label class="checkbox-inline">
                            <input type="checkbox" id="meta_partial" name="meta_partial"> Normalize data
                        </label>
                        </div>
                    </div>

                    <div class="form-group">
                            <strong>Graph type</strong></label>
                        <div class="radio">
                            <label>
                                <input id="graph-type-grouped" name="graph_type" type="radio" value="grouped" checked> Grouped
                            </label>

                            <label>
                                <input id="graph-type-stacked" name="graph_type" type="radio" value="stacked"> Stacked
                            </label>
                        </div>
                    </div>

                </div>
            </div><!-- end panel -->
            <!-- Graph update form submission -->
            <p class="text-center">
                <input type="submit" class="btn btn-primary" id="graph-update-submit" value="Create graph"/>
                | <a id="graph-update-reset" style="cursor:pointer;" onclick="resetGraphForm('graph-update-form');">Reset all</a>
            </p>
        </div><!--
        --><div id="chart-col" class="col-md-9 vcenter">
            <div id="loading" class="hidden">
                <p class="text-center">
                    <?php echo $this->Html->image('ajax-loader.gif'); ?><br>
                    Loading... Please wait.
                </p>
            </div>
            <div id="chart-container"></div>
        </div>
    </div></form>
</section>
<br>
<br>
<br>
<br>
    <hr>
	<?php if($sequence_type=="transcript"): ?>
	<h3>Options</h3>
	<div class="subdiv">
		<?php

		$disable_graph_type	= null;
		if(!(isset($meta_partial)||isset($meta_noinfo))){$disable_graph_type=" disabled='disabled' ";}
//		echo $this->Form->create(false, array("url"=>array("controller"=>"tools", "action"=>"length_distribution", $exp_id, $sequence_type),"type"=>"post"));

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
//		echo "</form>\n";
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
<?php pr($bins_transcript); ?>

	<script type="text/javascript">
	//<![CDATA[

	var transcript_lengths	= <?php echo json_encode($bins_transcript); ?>;
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
		var basic_url		= <?php echo "'".$this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id),true)."'";?>;
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

		echo $this->Form->create(false,array("url"=>array("controller"=>"tools", "action"=>"length_distribution", $exp_id, $sequence_type),"type"=>"post"));
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

	var orf_lengths		= <?php echo json_encode($bins_orf); ?>;
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
		var basic_url		= <?php echo "'".$this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id),true)."'";?>;
		var final_url		= basic_url+"/min_orf_length/"+min_bin_size+"/max_orf_length/"+max_bin_size+"/";
		window.location		= final_url;
	}


	//]]>
	</script>


	<?php endif;?>





</div>
</div>
<script type="text/javascript">
    // Various elements ids (jQuery selectors)
    var display_div_id = "#chart-container";
    var loading_div_id = "#loading";
    var sub_form_id = "#graph-update-form";
    var sub_btn_id = "#graph-update-submit";
    var reset_btn_id = "#graph-update-reset";


    function populateChartCol() {
        console.log("[Message] Graph update form was submitted! ");
        $(loading_div_id).removeClass("hidden");
        $(sub_btn_id).attr("disabled", true);
        $(display_div_id).empty();
        $.ajax({
            url: "<?php echo $this->Html->url(array("controller" => "tools", "action" => "length_distribution", $exp_id), array("escape" => false)); ?>",
            type: 'POST',
            data: $(sub_form_id).serialize(),
            dataType: 'html',
            success: function (data) {
                $(sub_btn_id).attr("disabled", false);
                $(loading_div_id).addClass("hidden");
                $(display_div_id).fadeOut('slow', function () {
                    $(display_div_id).hide().html(data).fadeIn();
                });
            },
            error: function () {
                alert('Unable to update chart data!');
            }
        });
    }

    $(function() {
        $(sub_form_id).submit(function (e) {
            e.preventDefault();
            populateChartCol();
        });
    });


    // Display current number of bins
    function updateNumBins(val) {
        document.getElementById('num-bins-value').textContent=val;
    }


    // Reset graph update form
    function resetGraphForm(formId) {
        document.getElementById(formId).reset();
        updateNumBins(document.getElementById('num_bins').value);
    }


    // Do stuff on page load
    window.onload = (function(){
        // Display current number of bins
        updateNumBins(document.getElementById('num_bins').value);
        // Load default length distribution chart
        populateChartCol();
    });

</script>
<?php  echo $this->element('sql_dump');  // Dump all MySQL queries (debug) ?>