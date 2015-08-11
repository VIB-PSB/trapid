<p id="sankey" ></p>
<div id="t" ></div>
<?php
	$jscriptdata = "var data = " + $data;
	//$javascript->codeBlock($jscriptdata, $options = array('allowCache'=>true,'safe'=>true,'inline'=>true);
	
	echo $html->css('sankey');
	echo $javascript->link(array('d3-3.5.6.min','sankey','mysankey'));	
	

?>

