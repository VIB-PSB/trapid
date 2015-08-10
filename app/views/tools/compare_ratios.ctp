<div>
<h2><?php echo $available_types[$type];?> ratios subsets</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	<h3>Subset selection</h3>
	<div class="subdiv">
		<?php
		if(isset($error)){echo "<span class='error'>".$error."</span>\n";}
		echo $form->create("",array("action"=>"compare_ratios/".$exp_id."/".$type,"type"=>"post"));
		echo "<dl class='standard'>";	
		echo "<dt>Subset 1</dt>";
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
		echo "</dd>";
		echo "<dt>Subset 2</dt>";
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
		echo "</dd>";
		echo "</dl>\n";
		echo "<br/><br/>";
		echo "<input type='submit' style='width:200px;' value='Compute ".$available_types[$type]." ratios' />\n";	
		echo "</form>\n";
		?>	
	</div>


	<?php
	//indication that results are present
	if(isset($data_subset1)){
		echo $javascript->link("sorttable");
		echo "<h3>Results</h3>\n";
		echo "<div class='subdiv'>\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		if($type=="ipr"){

		    echo "<a href='#both'>View functional annotation present in both subsets</a><br/>\n";
		    echo "<a href='#subset1specific'>View functional annotation specific to subset ".$subset1."</a><br/>\n";
		    echo "<a href='#subset2specific'>View functional annotation specific to subset ".$subset2."</a><br/>\n";
		    echo "<br/><br/>";	

		    echo "<a name='both'></a>\n";
		    echo "<h4><u>".$available_types[$type]." present in ".$subset1." and ".$subset2."</u></h4>\n";
		    echo "<div class='subdiv' style='margin-bottom:30px;'>\n";
		    echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span>\n";
		    echo "<table cellpadding='0' cellspacing='0' style='width:900px;' class='sortable'>\n";
		    echo "<tr>";
		    echo "<th>".$available_types[$type]."</th>";
		    echo "<th style='width:40%'>Description</th>";
		    echo "<th>Subset 1</th>";
		    echo "<th>Subset 2</th>";		
		    echo "<th>Ratio subset 1</th>";
		    echo "<th>Ratio subset 2</th>";
		    echo "<th>Ratio subset 1/2</th>";
		    echo "</tr>\n";
		    $counter	= 0;
		    foreach($data_subset1 as $k=>$v){
			if(array_key_exists($k,$data_subset2)){
				$class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				echo "<tr $class>";
				$wk	= str_replace(":","-",$k);
				if($type=='go'){
					echo "<td>".$html->link($k,array("controller"=>"functional_annotation","go",$exp_id,$wk))."</td>";
				}
				else if($type=='ipr'){
					echo "<td>".$html->link($k,array("controller"=>"functional_annotation","interpro",$exp_id,$wk))."</td>";
				}
				echo "<td>".$descriptions[$k]."</td>";
				echo "<td>".$html->link($data_subset1[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset1),"interpro",$wk))."</td>";
				echo "<td>".$html->link($data_subset2[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset2),"interpro",$wk))."</td>";
				$ratio_subset1	= 100*$data_subset1[$k]/$subset1_size;
				$ratio_subset2	= 100*$data_subset2[$k]/$subset2_size;
				echo "<td>".number_format($ratio_subset1,1)."%</td>";					
				echo "<td>".number_format($ratio_subset2,1)."%</td>";	
				echo "<td>".number_format($ratio_subset1/$ratio_subset2,2)."</td>";
				echo "</tr>\n";			
			}
		    }		
		    echo "</table>\n";
		
		    
		    echo $html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"ipr","1",urlencode($subset1),urlencode($subset2)),
				array("style"=>"margin-left:10px;padding-bottom:5px;"));

		    echo "</div>\n";


		    echo "<a name='subset1specific'></a>\n";
		    echo "<h4><u>".$available_types[$type]." present in ".$subset1." and not in ".$subset2."</u></h4>\n";
		    echo "<div class='subdiv' style='margin-bottom:30px;'>\n";
		    echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span>\n";
		    echo "<table cellpadding='0' cellspacing='0' style='width:700px;' class='sortable'>\n";
		    echo "<tr>";
		    echo "<th>".$available_types[$type]."</th>";
		    echo "<th style='width:40%'>Description</th>";
		    echo "<th>Subset 1</th>";				
		    echo "<th>Ratio subset 1</th>";				
		    echo "</tr>\n";
		    $counter	= 0;
		    foreach($data_subset1 as $k=>$v){
			if(!array_key_exists($k,$data_subset2)){
				$class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				echo "<tr $class>";
				$wk	= str_replace(":","-",$k);
				if($type=='go'){
					echo "<td>".$html->link($k,array("controller"=>"functional_annotation","go",$exp_id,$wk))."</td>";
				}
				else if($type=='ipr'){
					echo "<td>".$html->link($k,array("controller"=>"functional_annotation","interpro",$exp_id,$wk))."</td>";
				}
				echo "<td>".$descriptions[$k]."</td>";
				//echo "<td>".$data_subset1[$k]."</td>";			
				echo "<td>".$html->link($data_subset1[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset1),"interpro",$wk))."</td>";
				$ratio_subset1	= 100*$data_subset1[$k]/$subset1_size;			
				echo "<td>".number_format($ratio_subset1,1)."%</td>";	
				echo "</tr>\n";			
			}
		    }

		    echo "</table>\n";
		    echo $html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"ipr","2",urlencode($subset1),urlencode($subset2)),
				array("style"=>"margin-left:10px;padding-bottom:5px;"));

		    echo "</div>\n";


		    echo "<a name='subset2specific'></a>\n";
		    echo "<h4><u>".$available_types[$type]." present in ".$subset2." and not in ".$subset1."</u></h4>\n";
		    echo "<div class='subdiv' style='margin-bottom:30px;' >\n";
		    echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span>\n";
		    echo "<table cellpadding='0' cellspacing='0' style='width:700px;' class='sortable'>\n";
		    echo "<tr>";
		    echo "<th>".$available_types[$type]."</th>";
		    echo "<th style='width:40%'>Description</th>";		
		    echo "<th>Subset 2</th>";		
		    echo "<th>Ratio subset 2</th>";		
		    echo "</tr>\n";
		    $counter	= 0;
		    foreach($data_subset2 as $k=>$v){
			if(!array_key_exists($k,$data_subset1)){
				$class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				echo "<tr $class>";
				$wk	= str_replace(":","-",$k);
				if($type=='go'){
					echo "<td>".$html->link($k,array("controller"=>"functional_annotation","go",$exp_id,$wk))."</td>";
				}
				else if($type=='ipr'){
					echo "<td>".$html->link($k,array("controller"=>"functional_annotation","interpro",$exp_id,$wk))."</td>";
				}
				echo "<td>".$descriptions[$k]."</td>";			
				//echo "<td>".$data_subset2[$k]."</td>";		
				echo "<td>".$html->link($data_subset2[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset2),"interpro",$wk))."</td>";
				$ratio_subset2	= 100*$data_subset2[$k]/$subset2_size;		
				echo "<td>".number_format($ratio_subset2,1)."%</td>";	
				echo "</tr>\n";			
			}
		    }

		    echo "</table>\n";
		    echo $html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"ipr","3",urlencode($subset1),urlencode($subset2)),
				array("style"=>"margin-left:10px;padding-bottom:5px;"));

		    echo "</div>\n";
		}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		else if($type=="go"){
		    echo "<a href='#both'>View functional annotation present in both subsets</a>\n";    		  	
    		    echo "<br/>\n";
		    echo "<a href='#subset1specific'>View functional annotation specific to subset ".$subset1."</a>\n";    
    		    echo "<br/>\n";
		    echo "<a href='#subset2specific'>View functional annotation specific to subset ".$subset2."</a>\n";    		
    		    echo "<br/>\n";
		    echo "<br/><br/>";	
       		
		    echo "<a name='both'></a>\n";
    		    		      			
		    echo "<h4><u>".$available_types[$type]." present in ".$subset1." and ".$subset2."</u></h4>\n";
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
			if($go_type=="BP"){$style=" style='display:block;width:950px;' ";}			
    			echo "<div id='tabdiv_both_".$go_type."' class='tabbed_div tabdiv_both tabbed_div2' $style>\n";    
    			echo "<center>\n";
			echo "<table cellpadding='0' cellspacing='0' style='width:900px;' class='sortable'>\n";
			echo "<tr>";
			echo "<th>".$available_types[$type]."</th>";
			echo "<th style='width:40%'>Description</th>";
			echo "<th>Subset 1</th>";
			echo "<th>Subset 2</th>";
			echo "<th>Ratio subset 1</th>";
			echo "<th>Ratio subset 2</th>";
			echo "<th>Ratio subset 1/2</th>";
			echo "</tr>\n";
			$counter	= 0;
			foreach($data_subset1 as $k=>$v){
			    if(array_key_exists($k,$data_subset2) && $go_types[$k]==$go_type){
				    $class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				    echo "<tr $class>";
				    $wk	= str_replace(":","-",$k);				    
				    echo "<td>".$html->link($k,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$wk))."</td>";	
				    echo "<td>".$descriptions[$k]."</td>";
				    echo "<td>".$html->link($data_subset1[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset1),"go",$wk))."</td>";
				    echo "<td>".$html->link($data_subset2[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset2),"go",$wk))."</td>";
				    $ratio_subset1	= 100*$data_subset1[$k]/$subset1_size;
				    $ratio_subset2	= 100*$data_subset2[$k]/$subset2_size;
				    echo "<td>".number_format($ratio_subset1,1)."%</td>";					
				    echo "<td>".number_format($ratio_subset2,1)."%</td>";	
				    echo "<td>".number_format($ratio_subset1/$ratio_subset2,2)."</td>";
				    echo "</tr>\n";			
			    }
			}		    		
			echo "</table>\n";
			echo "</center>\n";
			echo $html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"go","1",urlencode($subset1),urlencode($subset2),$go_type),
				array("style"=>"margin-left:10px;padding-bottom:5px;"));			
    			echo "</div>\n";
    		    } 		     
		    echo "</div>\n";
		   
		    
		    echo "<a name='subset1specific'></a>\n";
		    echo "<h4><u>".$available_types[$type]." present in ".$subset1." and not in ".$subset2."</u></h4>\n";
		    echo "<div class='subdiv' style='margin-bottom:30px;'>\n";
		    echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span><br/><br/>\n";
    
    		    echo "<ul class='tabbed_header'>\n";
    		    echo "<li id='tab_sub1_BP' class='selected_tab tab_sub1'><a href='javascript:switchtab(\"tab_sub1_BP\",\".tab_sub1\",\"tabdiv_sub1_BP\",\".tabdiv_sub1\");'>Biological process</a></li>";
    		    echo "<li id='tab_sub1_MF' class='tab_sub1'><a href='javascript:switchtab(\"tab_sub1_MF\",\".tab_sub1\",\"tabdiv_sub1_MF\",\".tabdiv_sub1\");'>Molecular function</a></li>";
    		    echo "<li id='tab_sub1_CC' class='tab_sub1'><a href='javascript:switchtab(\"tab_sub1_CC\",\".tab_sub1\",\"tabdiv_sub1_CC\",\".tabdiv_sub1\");'>Cellular component</a></li>";
    		    echo "</ul>\n";
    		
    		    foreach($type_desc as $go_type=>$go_type_desc){
			$style	= null;
			if($go_type=="BP"){$style=" style='display:block;width:950px;' ";}			
    			echo "<div id='tabdiv_sub1_".$go_type."' class='tabbed_div tabdiv_sub1 tabbed_div2' $style>\n";    
    			echo "<center>\n";
 
			echo "<table cellpadding='0' cellspacing='0' style='width:700px;' class='sortable'>\n";
			echo "<tr>";
			echo "<th>".$available_types[$type]."</th>";
			echo "<th style='width:40%'>Description</th>";
			echo "<th>Subset 1</th>";				
			echo "<th>Ratio subset 1</th>";				
			echo "</tr>\n";
			$counter	= 0;
			foreach($data_subset1 as $k=>$v){
			    if(!array_key_exists($k,$data_subset2) && $go_types[$k]==$go_type){
				    $class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				    echo "<tr $class>";
				    $wk	= str_replace(":","-",$k);				
				    echo "<td>".$html->link($k,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$wk))."</td>";
				    echo "<td>".$descriptions[$k]."</td>";							
				    echo "<td>".$html->link($data_subset1[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset1),"go",$wk))."</td>";
				    $ratio_subset1	= 100*$data_subset1[$k]/$subset1_size;			
				    echo "<td>".number_format($ratio_subset1,1)."%</td>";	
				    echo "</tr>\n";			
			    }
			}
			echo "</table>\n";
    			echo "</center>\n";

			echo $html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"go","2",urlencode($subset1),urlencode($subset2),$go_type),
				array("style"=>"margin-left:10px;padding-bottom:5px;"));
    			echo "</div>\n";
    		    }
    
		    echo "</div>\n";	




		
		    echo "<a name='subset2specific'></a>\n";
		    echo "<h4><u>".$available_types[$type]." present in ".$subset2." and not in ".$subset1."</u></h4>\n";
		    echo "<div class='subdiv' style='margin-bottom:30px;'>\n";
		    echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span><br/><br/>\n";
    
    		    echo "<ul class='tabbed_header'>\n";
    		    echo "<li id='tab_sub2_BP' class='selected_tab tab_sub2'><a href='javascript:switchtab(\"tab_sub2_BP\",\".tab_sub2\",\"tabdiv_sub2_BP\",\".tabdiv_sub2\");'>Biological process</a></li>";
    		    echo "<li id='tab_sub2_MF' class='tab_sub2'><a href='javascript:switchtab(\"tab_sub2_MF\",\".tab_sub2\",\"tabdiv_sub2_MF\",\".tabdiv_sub2\");'>Molecular function</a></li>";
    		    echo "<li id='tab_sub2_CC' class='tab_sub2'><a href='javascript:switchtab(\"tab_sub2_CC\",\".tab_sub2\",\"tabdiv_sub2_CC\",\".tabdiv_sub2\");'>Cellular component</a></li>";
    		    echo "</ul>\n";
    		
    		    foreach($type_desc as $go_type=>$go_type_desc){
			$style	= null;
			if($go_type=="BP"){$style=" style='display:block;width:950px;' ";}			
    			echo "<div id='tabdiv_sub2_".$go_type."' class='tabbed_div tabdiv_sub2 tabbed_div2' $style>\n";    
    			echo "<center>\n";
    
			echo "<table cellpadding='0' cellspacing='0' style='width:700px;' class='sortable'>\n";
			echo "<tr>";
			echo "<th>".$available_types[$type]."</th>";
			echo "<th style='width:40%'>Description</th>";		
			echo "<th>Subset 2</th>";		
			echo "<th>Ratio subset 2</th>";		
			echo "</tr>\n";
			$counter	= 0;
			foreach($data_subset2 as $k=>$v){
			    if(!array_key_exists($k,$data_subset1) && $go_types[$k]==$go_type){
				    $class	= null; if($counter++%2==0){$class=" class='altrow' ";}
				    echo "<tr $class>";
				    $wk	= str_replace(":","-",$k);			
				    echo "<td>".$html->link($k,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$wk))."</td>";
				    echo "<td>".$descriptions[$k]."</td>";							
				    echo "<td>".$html->link($data_subset2[$k],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"label",urlencode($subset2),"go",$wk))."</td>";
				    $ratio_subset2	= 100*$data_subset2[$k]/$subset2_size;		
				    echo "<td>".number_format($ratio_subset2,1)."%</td>";	
				    echo "</tr>\n";			
			    }
			}
			echo "</table>\n";
			echo "</center>\n";
			
			echo $html->link("Download data",
				array("controller"=>"tools","action"=>"compare_ratios_download",$exp_id,"go","3",urlencode($subset1),urlencode($subset2),$go_type),
				array("style"=>"margin-left:10px;padding-bottom:5px;"));

			echo "</div>\n";
    		    }
		    echo "</div>\n";	



		    echo "<script type='text/javascript'>\n";
		    echo "//<![CDATA[\n";
		    echo "function switchtab(tabid,tabclass,divid,divclass){\n";
		    echo "$$(divclass).each(function(entity){\n";
		    echo "entity.style.display='none';\n";	
		    echo "});\n";
		    echo "$(divid).style.display='block';\n";
		    echo "$(divid).style.width='950px';\n";
	
		    echo "$$(tabclass).each(function(entity){\n";
		    echo "try{entity.removeClassName('selected_tab');}catch(exception){}\n";
		    echo "});\n";
		    echo "$(tabid).addClassName('selected_tab');\n";		   

		    echo "}\n";		    
		    echo "//]]>\n";
    		    echo "</script>\n";
    

		}		

		echo "</div>\n";
	}	
	?>

</div>
</div>
