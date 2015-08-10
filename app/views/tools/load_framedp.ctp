<div>
<?php
if(isset($error)){
	echo "<span class='error'>".$error."</span>\n";
}
else{
	echo "<span>FrameDP finished correcting the sequence for frameshifts</span><br/>\n";
	echo $html->link("Reload this page to view the results",array("controller"=>"tools","action"=>"framedp",$exp_id,$gf_id));	
			
}
?>	
</div>