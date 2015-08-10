<div>
<?php
if(isset($error)){
	echo "<span class='error'>".$error."</span>\n";
}
else{
	$data_url_tree	= $html->url(array("controller"=>"tools","action"=>"view_tree",$hashed_user_id,$exp_id,$gf_id),true);
	$data_url_tree_newick = $html->url(array("controller"=>"tools","action"=>"view_tree",$hashed_user_id,$exp_id,$gf_id,"newick"),true);

	$jar_file_location = $html->url("/files/forester/",true);	
	echo "<span>Download phylogenetic tree </span>";
	echo $html->link("(PhyloXML)",$data_url_tree);
	echo "&nbsp;&nbsp;";
	echo $html->link("(Newick)",$data_url_tree_newick);
	
	echo "<div style='margin-left:5px;margin-top:-10px;'>\n";
	echo "<p><br/><br/>\n";			
	echo "<applet archive='forester.jar' code='org.forester.atv.ATVe.class' codebase='$jar_file_location' width='950' height='700' alt='Archeopteryx is not working on your system (requires at least Java 1.5)'>\n";
	echo "<param name='url_of_tree_to_load' value='$data_url_tree'>\n";
	echo "<param name='config_file' value='$atv_config_file' >\n";
	echo "<param name='base_linkout_url' value='".$html->url("/",true)."'  >\n";
	echo "</applet>\n";	    
	echo "</p></div>\n";
	echo $this->element("subset_colors");
}
?>
</div>
