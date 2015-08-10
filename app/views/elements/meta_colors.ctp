<?php
function generateLighterColor($base_color){
	$red	= hexdec(substr($base_color,1,2));	
	$green	= hexdec(substr($base_color,3,2));
	$blue	= hexdec(substr($base_color,5,2));	
	$new_color	= "rgba(".$red.",".$green.",".$blue.",0.6)";	
	return $new_color;
}

if(isset($meta_colors) && count($meta_colors)!=0){
	echo "<div style='margin-top:10px;'>\n";
	echo "<h4>Meta-information color legend</h4>\n";

	echo "<div>\n";
	echo "<dl class='standard'>\n";
	foreach($meta_colors as $meta=>$color){
      echo "<dt>".$html->link($meta,array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"meta_annotation",urlencode($meta)));
		echo "<dd style='margin-left:10em;'>";
		echo "<div style='width:30px;height:15px;border:4px solid ".$color.";background-color:".generateLighterColor($color)."'>";
		echo "&nbsp;";
		echo "</div>";
		echo "</dd>";		
	}
	echo "</dl>\n";
	echo "</div>\n";
		
	echo "</div>";
}
?>
