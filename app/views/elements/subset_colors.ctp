<?php
function generateLighterColor($base_color){
	$red	= hexdec(substr($base_color,1,2));	
	$green	= hexdec(substr($base_color,3,2));
	$blue	= hexdec(substr($base_color,5,2));	
	$new_color	= "rgba(".$red.",".$green.",".$blue.",0.6)";	
	return $new_color;
}
if(isset($subset_colors) && count($subset_colors)!=0){
	echo "<div style='margin-top:10px;'>\n";
	echo "<h4>Subset color legend</h4>\n";

	echo "<div style='float:left;width:200px;'>\n";
	echo "<dl class='standard'>\n";
	$counter	= 0;
	foreach($subset_colors as $subset=>$color){
		$counter++;
		if($counter%2!=0){
			echo "<dt>".$html->link($subset,array("controller"=>"labels","action"=>"view",$exp_id,urlencode($subset)))."</dt>\n";
			echo "<dd style='margin-left:10em;'>";
			echo "<div style='width:30px;height:15px;border:4px solid ".$color.";background-color:".generateLighterColor($color)."'>";
			echo "&nbsp;";
			echo "</div>";
			echo "</dd>";
		}
	}
	echo "</dl>\n";
	echo "</div>\n";
	
	echo "<div style='float:left;width:200px;'>\n";
	echo "<dl class='standard'>\n";
	$counter	= 0;
	foreach($subset_colors as $subset=>$color){
		$counter++;
		if($counter%2==0){
			echo "<dt>".$html->link($subset,array("controller"=>"labels","action"=>"view",$exp_id,urlencode($subset)))."</dt>\n";
			echo "<dd style='margin-left:10em;'>";
			echo "<div style='width:30px;height:15px;border:4px solid ".$color.";background-color:".generateLighterColor($color)."'>";
			echo "&nbsp;";
			echo "</div>";
			echo "</dd>";
		}
	}
	echo "</dl>\n";
	echo "</div>\n";

	echo "<div style='clear:both;width:600px;'>&nbsp;</div>\n";
	 	
		
	echo "</div>";
}
?>
