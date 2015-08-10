<div>
<?php
if(isset($error)){
	echo "<span class='error'>".$error."</span>\n";
}
else{
	$msa_url_1	= $html->url(array("controller"=>"tools","action"=>"view_msa",$hashed_user_id,$exp_id,$gf_id,"normal"),true);
	echo "<dl class='standard'>\n";
	echo "<dt>View</dt>\n";
	echo "<dd>".$html->link("View full multiple sequence alignment","javascript:$('form_msa_norm').submit();")."</dd>\n";
	echo "<dt>Download</dt>\n";
	echo "<dd>".$html->link("Download multiple sequence alignment",$msa_url_1)."</dd>";    
    	echo "</dl>\n"; 		

	echo "<form action='http://bioinformatics.psb.ugent.be/webtools/jalview/jalview.jnlp' id='form_msa_norm' method='post'>";
	echo "<input type='hidden' name='data' value='".$msa_url_1."' />";
 	echo "</form>\n";				
}
?>	
</div>
