<?php
echo $form->create(null,array("controller"=>"trapid","action"=>"search/".$exp_id,"type"=>"post"));
$search_types = array("transcript"=>"Transcript identifier","gene"=>"Gene identifier","GO"=>"GO description",
			"interpro"=>"Protein domain description","gf"=>"Gene family",
			"meta_annotation"=>"Meta annotation");
echo "<select name='search_type' style='width:200px; margin-right:20px;' id='search_type'>";
foreach($search_types as $k=>$v){
	echo "<option value='".$k."' ";
	if(isset($search_type) && $search_type==$k){echo " selected='selected' ";}
	echo ">".$v."</option>";
}
echo "</select>\n";
echo "<span id='search_content'>\n";
if(!$mvc){
	echo "<input type='text' name='search_value' style='width:200px;margin-right:20px;' maxlength='50' ";
	if(isset($search_value)){echo " value='".$search_value."' ";}
	echo "/>\n";
}
else{
	$sv	= "";
	if(isset($search_value)){$sv=$search_value;}
	echo "<textarea name='search_value' rows='1' cols='30'>".$sv."</textarea>";
}
echo "</span>\n";
if((isset($search_type) && ($search_type=="transcript" || $search_type=="gene")) || !isset($search_type)){
	$checked = null;
	if($mvc){$checked=" checked='checked' ";}
	echo "<input type='checkbox' name='multiple_values_check' id='multiple_values_check' style='margin-right:5px;' ".$checked."/>";
	echo "<span style='margin-right:20px;' id='mvs_txt'>Multiple values</span>";
}
echo "<input type='submit' value='Search' />\n";
echo "</form>\n";
?>

<script type='text/javascript'>
//<![CDATA[
	$("search_type").observe("change",function(){
		var opt	= $("search_type").options[$("search_type").selectedIndex];
		if(opt.value=="meta_annotation"){
		    $("search_content").innerHTML = "<select name='search_value' style='width:200px;margin-right:20px;'><option value='No Information'>No Information</option><option value='Partial'>Partial</option><option value='Full Length'>Full Length</option><option value='Quasi Full Length'>Quasi Full Length</option></select>";			}
		else{
		   $("search_content").innerHTML = "<input type='text' name='search_value' style='width:200px;margin-right:20px;' maxlength='50' />";
		   $("multiple_values_check").checked = false;
		}
		if(!(opt.value=="transcript" || opt.value=="gene")){
		   $("multiple_values_check").style.display = "none";
		   $("mvs_txt").style.display = "none";
		}
		else{
		   $("multiple_values_check").style.display = "inline";
		   $("mvs_txt").style.display = "inline";
		}
	
	});
	$("multiple_values_check").observe("change",function(){
		var mvc = $("multiple_values_check").checked;
		var opt = $("search_type").options[$("search_type").selectedIndex];
		if(opt.value=="transcript" || opt.value=="gene"){
			if(mvc){
				$("search_content").innerHTML = "<textarea name='search_value' rows='1' cols='30'></textarea>";
			}
			else{
				$("search_content").innerHTML = "<input type='text' name='search_value' style='width:200px;margin-right:20px;' maxlength='50' />";	
			}
		}
	});

//]]>
</script>
