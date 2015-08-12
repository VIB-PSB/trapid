<p id="sankey" ></p>
<!--<div id="t" ></div> -->
<?php
    //echo $sankeyData;
    echo '<script type="text/javascript">';
    echo "var sankeyData = " . $sankeyData;
    echo '</script>';	
	
	echo $html->css('sankey');
	echo $javascript->link(array('d3-3.5.6.min','sankey','mysankey'));	
	
?>

