<div style="background-color:#D2D2D2;border:1px solid black;width:700px; padding-left:20px;padding-top:10px;">
<?php
foreach($toolbox as $subtitle=>$content){
echo "<h4 style='text-decoration:underline;'>".$subtitle."</h4>\n";
echo "<div class='subdiv bottom'>\n";
echo "<ul>";
foreach($content as $cont){
	echo "<li>";
	$desc		= $cont[0];
	$link		= $cont[1];	
	$img		= null; if(count($cont)>2){$img=$cont[2];}
	$disabled	= false; if(count($cont)>3){$disabled=$cont[3];}
	if($disabled){
		echo "<span class='disabled'>".$desc."</span>";
	}
	else{
		echo "<a href='".$link."'>".$desc."</a>";
	}
	echo "</li>\n";
}	
echo "</ul>\n";	
echo "</div>\n";
}
?>
</div>