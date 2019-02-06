<div>

    <div class="page-header">
        <h1 class="text-primary"><?php echo $available_types[$type];?> ratios subsets</h1>
    </div>

<div class="subdiv">
	<?php // echo $this->element("trapid_experiment");?>
	<section class="page-section">
	<h3>Subset selection</h3>
		<?php
		if(isset($error)){echo "<span class='error text-danger'><strong>Error: </strong>".$error."</span><br>\n";}
		echo $this->Form->create(false, array("url"=>array("controller"=>"tools", "action"=>"compare_ratios", $exp_id, $type),"type"=>"post", "id"=>"compare-ratios-form", "class"=>"form-inline"));
//		echo "<dl class='standard dl-horizontal'>";
        echo "<div class='form-group' style='margin-right: 20px;'>";
		echo "<label for='subset1'><strong>Subset 1</strong></label><br>";
		echo "<select name='subset1' style='' class='form-control'>";
		foreach($subsets as $subset=>$count){
			if(isset($subset1) && $subset1==$subset){
				echo "<option value='".$subset."' selected='selected'>".$subset." (".$count." transcripts)</option>\n";	
			}
			else{
				echo "<option value='".$subset."'>".$subset." (".$count." transcripts)</option>\n";
			}
		}		
		echo "</select>";
		echo "</div>";
        echo "<div class='form-group'>";
		echo "<label for=''subset2'><strong>Subset 2</strong></label><br>";
		echo "<select name='subset2' style='' class='form-control'>";
		foreach($subsets as $subset=>$count){
			if(isset($subset2) && $subset2==$subset){
				echo "<option value='".$subset."' selected='selected'>".$subset." (".$count." transcripts)</option>\n";	
			}
			else{
				echo "<option value='".$subset."'>".$subset." (".$count." transcripts)</option>\n";
			}
		}		
		echo "</select>";
		echo "</div>";
//		echo "</dl>\n";
//		echo "<br/><br/>";
        echo "<br><div class='form-group' style='margin-top: 20px;'>";
		echo "&nbsp;<input type='submit' class='btn btn-primary' value='Compute ".$available_types[$type]." ratios' />\n";
		echo "</div>";
		echo "</form>\n";
		?>	
	</section>


	<?php
	//indication that results are present
	if(isset($data_subset1)){
		echo $this->Html->script("sorttable");
		echo "<hr>\n";
		echo "<section class='page-section'>\n";
		echo "<h2>Results</h2>\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		if($type=="ipr"){

		    echo "<p class='text-justify'><strong>Jump to:</strong>\n";
		    echo "<ul>\n";
		    echo "<li><a href='#both'>Functional annotation present in both subsets</a></li>\n";
		    echo "<li><a href='#subset1specific'>Functional annotation specific to subset '".$subset1."'</a></li>\n";
		    echo "<li><a href='#subset2specific'>Functional annotation specific to subset '".$subset2."'</a></li>\n";
		    echo "</ul>\n";
		    echo "</p>\n";
		    echo "<br>";

		    echo "<a name='both'></a>\n";
		    echo "<h3>".$available_types[$type]." present in ".$subset1." and ".$subset2."</h3>\n";
		    echo "<div class='subdiv' style='margin-bottom:30px;'>\n";
		    echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span>\n";
		    echo "<table class='table table-condensed table-hover table-striped table-bordered sortable'>\n";
		    echo "<thead>";
		    echo "<tr>";
		    echo "<th>".$available_types[$type]."</th>";
		    echo "<th>Description</th>";
		    echo "<th>Subset 1</th>";
		    echo "<th>Subset 2</th>";		
		    echo "<th>Ratio subset 1</th>";
		    echo "<th>Ratio subset 2</th>";
		    echo "<th>Ratio subset 1/2</th>";
		    echo "</tr>\n";
            echo "</thead>";
            echo "<tbody>";
		    $counter	= 0;
		    foreach($data_subset1 as $k=>$v){
			if(array_key_exists($k,$data_subset2)){
				$class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				echo "<tr $class>";
				$wk	= str_replace(":","-",$k);
				if($type=='go'){
					echo "<td>".$this->Html->link($k,array("controller"=>"functional_annotation","go",$exp_id,$wk))."</td>";
				}
				else if($type=='ipr'){
					echo "<td>".$this->Html->link($k,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$wk))."</td>";
				}
				echo "<td>".$descriptions[$k]."</td>";
				echo "<td>".$this->Html->link($data_subset1[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset1),"interpro",$wk))."</td>";
				echo "<td>".$this->Html->link($data_subset2[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset2),"interpro",$wk))."</td>";
				$ratio_subset1	= 100*$data_subset1[$k]/$subset1_size;
				$ratio_subset2	= 100*$data_subset2[$k]/$subset2_size;
				echo "<td>".number_format($ratio_subset1,1)."%</td>";					
				echo "<td>".number_format($ratio_subset2,1)."%</td>";	
				echo "<td>".number_format($ratio_subset1/$ratio_subset2,2)."</td>";
				echo "</tr>\n";			
			}
		    }		
		    echo "</tbody>\n";
		    echo "</table>\n";

		    
		    echo $this->Html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"ipr","1",urlencode($subset1),urlencode($subset2)),
				array("style"=>"margin-left:10px;padding-bottom:5px;",  "class"=>"btn btn-default btn-sm"));

		    echo "</div>\n";

            echo "<hr>";
		    echo "<a name='subset1specific'></a>\n";
		    echo "<h3>".$available_types[$type]." present in ".$subset1." and not in ".$subset2."</h3>\n";
		    echo "<div class='subdiv' style='margin-bottom:30px;'>\n";
		    echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span>\n";
		    echo "<table class='table table-condensed table-hover table-striped table-bordered sortable'>\n";
		    echo "<thead>";
		    echo "<tr>";
		    echo "<th>".$available_types[$type]."</th>";
		    echo "<th>Description</th>";
		    echo "<th>Subset 1</th>";
		    echo "<th>Ratio subset 1</th>";
		    echo "</tr>\n";
		    echo "</thead>";
		    echo "<tbody>";
		    $counter	= 0;
		    foreach($data_subset1 as $k=>$v){
			if(!array_key_exists($k,$data_subset2)){
				$class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				echo "<tr $class>";
				$wk	= str_replace(":","-",$k);
				if($type=='go'){
					echo "<td>".$this->Html->link($k,array("controller"=>"functional_annotation","go",$exp_id,$wk))."</td>";
				}
				else if($type=='ipr'){
					echo "<td>".$this->Html->link($k,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$wk))."</td>";
				}
				echo "<td>".$descriptions[$k]."</td>";
				//echo "<td>".$data_subset1[$k]."</td>";			
				echo "<td>".$this->Html->link($data_subset1[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset1),"interpro",$wk))."</td>";
				$ratio_subset1	= 100*$data_subset1[$k]/$subset1_size;			
				echo "<td>".number_format($ratio_subset1,1)."%</td>";	
				echo "</tr>\n";			
			}
		    }

		    echo "</tbody>\n";
		    echo "</table>\n";
		    echo $this->Html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"ipr","2",urlencode($subset1),urlencode($subset2)),
				array("style"=>"margin-left:10px;padding-bottom:5px;",  "class"=>"btn btn-default btn-sm"));

		    echo "</div>\n";

            echo "<hr>";
		    echo "<a name='subset2specific'></a>\n";
		    echo "<h3>".$available_types[$type]." present in ".$subset2." and not in ".$subset1."</h3>\n";
		    echo "<div class='subdiv' style='margin-bottom:30px;' >\n";
		    echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span>\n";
		    echo "<table class='table table-condensed table-hover table-striped table-bordered sortable'>\n";
            echo "<thead>";
		    echo "<tr>";
		    echo "<th>".$available_types[$type]."</th>";
		    echo "<th>Description</th>";
		    echo "<th>Subset 2</th>";		
		    echo "<th>Ratio subset 2</th>";		
		    echo "</tr>\n";
            echo "</thead>";
            echo "<tbody>";
		    $counter	= 0;
		    foreach($data_subset2 as $k=>$v){
			if(!array_key_exists($k,$data_subset1)){
				$class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				echo "<tr $class>";
				$wk	= str_replace(":","-",$k);
				if($type=='go'){
					echo "<td>".$this->Html->link($k,array("controller"=>"functional_annotation","go",$exp_id,$wk))."</td>";
				}
				else if($type=='ipr'){
					echo "<td>".$this->Html->link($k,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$wk))."</td>";
				}
				echo "<td>".$descriptions[$k]."</td>";			
				//echo "<td>".$data_subset2[$k]."</td>";		
				echo "<td>".$this->Html->link($data_subset2[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset2),"interpro",$wk))."</td>";
				$ratio_subset2	= 100*$data_subset2[$k]/$subset2_size;		
				echo "<td>".number_format($ratio_subset2,1)."%</td>";	
				echo "</tr>\n";			
			}
		    }

		    echo "</tbody>\n";
		    echo "</table>\n";
		    echo $this->Html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"ipr","3",urlencode($subset1),urlencode($subset2)),
				array("style"=>"margin-left:10px;padding-bottom:5px;",  "class"=>"btn btn-default btn-sm"));

		    echo "</div>\n";
		}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		else if($type=="go"){
            echo "<p class='text-justify'><strong>Jump to:</strong>\n";
            echo "<ul>\n";
            echo "<li><a href='#both'>Functional annotation present in both subsets</a></li>\n";
            echo "<li><a href='#subset1specific'>Functional annotation specific to subset '".$subset1."'</a></li>\n";
            echo "<li><a href='#subset2specific'>Functional annotation specific to subset '".$subset2."'</a></li>\n";
            echo "</ul>\n";
            echo "</p>\n";
		    echo "<br>";

            echo "<hr>";
		    echo "<a name='both'></a>\n";
    		    		      			
		    echo "<h3>".$available_types[$type]." present in ".$subset1." and ".$subset2."</h3>\n";
		    echo "<div class='subdiv' style='margin-bottom:30px;'>\n";
		    echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span><br/><br/>\n";
    		
    
    		    echo "<ul class='tabbed_header'>\n";
    		    echo "<li id='tab_both_BP' class='selected_tab tab_both'><a href='javascript:switchtab(\"tab_both_BP\",\".tab_both\",\"tabdiv_both_BP\",\".tabdiv_both\");'>Biological process</a></li>";
    		    echo "<li id='tab_both_MF' class='tab_both'><a href='javascript:switchtab(\"tab_both_MF\",\".tab_both\",\"tabdiv_both_MF\",\".tabdiv_both\");'>Molecular function</a></li>";
    		    echo "<li id='tab_both_CC' class='tab_both'><a href='javascript:switchtab(\"tab_both_CC\",\".tab_both\",\"tabdiv_both_CC\",\".tabdiv_both\");'>Cellular component</a></li>";
    		    echo "</ul>\n";
    		
    		    foreach($type_desc as $go_type=>$go_type_desc){
			//file for download is written immediately at the same time		
			$style	= null;
			if($go_type=="BP"){$style=" style='display:block;' ";}
    			echo "<div id='tabdiv_both_".$go_type."' class='tabbed_div tabdiv_both tabbed_div2' $style>\n";    
    			echo "<center>\n";
			echo "<table class='table table-condensed table-hover table-striped table-bordered sortable'>\n";
			echo "<thead>";
			echo "<tr>";
			echo "<th>".$available_types[$type]."</th>";
			echo "<th>Description</th>";
			echo "<th>Subset 1</th>";
			echo "<th>Subset 2</th>";
			echo "<th>Ratio subset 1</th>";
			echo "<th>Ratio subset 2</th>";
			echo "<th>Ratio subset 1/2</th>";
			echo "</tr>\n";
            echo "</thead>";
            echo "<tbody>";
			$counter	= 0;
			foreach($data_subset1 as $k=>$v){
			    if(array_key_exists($k,$data_subset2) && $go_types[$k]==$go_type){
				    $class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				    echo "<tr $class>";
				    $wk	= str_replace(":","-",$k);				    
				    echo "<td>".$this->Html->link($k,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$wk))."</td>";	
				    echo "<td>".$descriptions[$k]."</td>";
				    echo "<td>".$this->Html->link($data_subset1[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset1),"go",$wk))."</td>";
				    echo "<td>".$this->Html->link($data_subset2[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset2),"go",$wk))."</td>";
				    $ratio_subset1	= 100*$data_subset1[$k]/$subset1_size;
				    $ratio_subset2	= 100*$data_subset2[$k]/$subset2_size;
				    echo "<td>".number_format($ratio_subset1,1)."%</td>";					
				    echo "<td>".number_format($ratio_subset2,1)."%</td>";	
				    echo "<td>".number_format($ratio_subset1/$ratio_subset2,2)."</td>";
				    echo "</tr>\n";			
			    }
			}		    		
			echo "</tbody>\n";
			echo "</table>\n";
			echo "</center>\n";
			echo $this->Html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"go","1",urlencode($subset1),urlencode($subset2),$go_type),
				array("style"=>"margin-left:10px;padding-bottom:5px;",  "class"=>"btn btn-default btn-sm"));
    			echo "</div>\n";
    		    } 		     
		    echo "</div>\n";


            echo "<hr>";
		    echo "<a name='subset1specific'></a>\n";
		    echo "<h3>".$available_types[$type]." present in ".$subset1." and not in ".$subset2."</h3>\n";
		    echo "<div class='subdiv' style='margin-bottom:30px;'>\n";
		    echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span><br/><br/>\n";
    
    		    echo "<ul class='tabbed_header'>\n";
    		    echo "<li id='tab_sub1_BP' class='selected_tab tab_sub1'><a href='javascript:switchtab(\"tab_sub1_BP\",\".tab_sub1\",\"tabdiv_sub1_BP\",\".tabdiv_sub1\");'>Biological process</a></li>";
    		    echo "<li id='tab_sub1_MF' class='tab_sub1'><a href='javascript:switchtab(\"tab_sub1_MF\",\".tab_sub1\",\"tabdiv_sub1_MF\",\".tabdiv_sub1\");'>Molecular function</a></li>";
    		    echo "<li id='tab_sub1_CC' class='tab_sub1'><a href='javascript:switchtab(\"tab_sub1_CC\",\".tab_sub1\",\"tabdiv_sub1_CC\",\".tabdiv_sub1\");'>Cellular component</a></li>";
    		    echo "</ul>\n";
    		
    		    foreach($type_desc as $go_type=>$go_type_desc){
			$style	= null;
			if($go_type=="BP"){$style=" style='display:block;' ";}
    			echo "<div id='tabdiv_sub1_".$go_type."' class='tabbed_div tabdiv_sub1 tabbed_div2' $style>\n";    
    			echo "<center>\n";
 
			echo "<table class='table table-condensed table-hover table-striped table-bordered sortable'>\n";
			echo "<thead>";
			echo "<tr>";
			echo "<th>".$available_types[$type]."</th>";
			echo "<th>Description</th>";
			echo "<th>Subset 1</th>";				
			echo "<th>Ratio subset 1</th>";				
			echo "</tr>\n";
			echo "</thead>";
			echo "<tbody>";
			$counter	= 0;
			foreach($data_subset1 as $k=>$v){
			    if(!array_key_exists($k,$data_subset2) && $go_types[$k]==$go_type){
				    $class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				    echo "<tr $class>";
				    $wk	= str_replace(":","-",$k);				
				    echo "<td>".$this->Html->link($k,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$wk))."</td>";
				    echo "<td>".$descriptions[$k]."</td>";							
				    echo "<td>".$this->Html->link($data_subset1[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset1),"go",$wk))."</td>";
				    $ratio_subset1	= 100*$data_subset1[$k]/$subset1_size;			
				    echo "<td>".number_format($ratio_subset1,1)."%</td>";	
				    echo "</tr>\n";			
			    }
			}
			echo "</tbody>\n";
			echo "</table>\n";
    			echo "</center>\n";

			echo $this->Html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"go","2",urlencode($subset1),urlencode($subset2),$go_type),
				array("style"=>"margin-left:10px;padding-bottom:5px;",  "class"=>"btn btn-default btn-sm"));
    			echo "</div>\n";
    		    }
    
		    echo "</div>\n";




            echo "<hr>";
		    echo "<a name='subset2specific'></a>\n";
		    echo "<h3>".$available_types[$type]." present in ".$subset2." and not in ".$subset1."</h3>\n";
		    echo "<div class='subdiv' style='margin-bottom:30px;'>\n";
		    echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span><br/><br/>\n";
    
    		    echo "<ul class='tabbed_header'>\n";
    		    echo "<li id='tab_sub2_BP' class='selected_tab tab_sub2'><a href='javascript:switchtab(\"tab_sub2_BP\",\".tab_sub2\",\"tabdiv_sub2_BP\",\".tabdiv_sub2\");'>Biological process</a></li>";
    		    echo "<li id='tab_sub2_MF' class='tab_sub2'><a href='javascript:switchtab(\"tab_sub2_MF\",\".tab_sub2\",\"tabdiv_sub2_MF\",\".tabdiv_sub2\");'>Molecular function</a></li>";
    		    echo "<li id='tab_sub2_CC' class='tab_sub2'><a href='javascript:switchtab(\"tab_sub2_CC\",\".tab_sub2\",\"tabdiv_sub2_CC\",\".tabdiv_sub2\");'>Cellular component</a></li>";
    		    echo "</ul>\n";
    		
    		    foreach($type_desc as $go_type=>$go_type_desc){
			$style	= null;
			if($go_type=="BP"){$style=" style='display:block;' ";}
    			echo "<div id='tabdiv_sub2_".$go_type."' class='tabbed_div tabdiv_sub2 tabbed_div2' $style>\n";    
    			echo "<center>\n";
    
			echo "<table class='table table-condensed table-hover table-striped table-bordered sortable'>\n";
			echo "<thead>";
			echo "<tr>";
			echo "<th>".$available_types[$type]."</th>";
			echo "<th>Description</th>";
			echo "<th>Subset 2</th>";		
			echo "<th>Ratio subset 2</th>";		
			echo "</tr>\n";
			echo "</thead>";
			echo "<tbody>";
			$counter	= 0;
			foreach($data_subset2 as $k=>$v){
			    if(!array_key_exists($k,$data_subset1) && $go_types[$k]==$go_type){
				    $class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				    echo "<tr $class>";
				    $wk	= str_replace(":","-",$k);			
				    echo "<td>".$this->Html->link($k,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$wk))."</td>";
				    echo "<td>".$descriptions[$k]."</td>";							
				    echo "<td>".$this->Html->link($data_subset2[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset2),"go",$wk))."</td>";
				    $ratio_subset2	= 100*$data_subset2[$k]/$subset2_size;		
				    echo "<td>".number_format($ratio_subset2,1)."%</td>";	
				    echo "</tr>\n";			
			    }
			}
			echo "</tbody>\n";
			echo "</table>\n";
			echo "</center>\n";
			
			echo $this->Html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"go","3",urlencode($subset1),urlencode($subset2),$go_type),
				array("style"=>"margin-left:10px;padding-bottom:5px;", "class"=>"btn btn-default btn-sm"));

			echo "</div>\n";
    		    }
		    echo "</div>\n";	

		}

		echo "</div>\n";
	}	
?>

</div>
</div>

<script type='text/javascript'>
    //<![CDATA[
    // Toggle table visibility depending on user's choice
    function switchtab(tabid, tabclass, divid, divclass) {
        $(divclass).each(function() {
            $(this).css("display", "none");
        });
        $("#" + divid).css("display", "block");
        $(tabclass).each(function(entity) {
            try {
                $(this).removeClass('selected_tab');
            }
            catch(exception) {}
        });
        $("#" + tabid).addClass('selected_tab');
        console.log("Hej");
    }
    //]]>
</script>
