<p id="sankey" ></p>
<!--<div id="t" ></div> -->
<?php
    //echo $sankeyData;
    echo '<script type="text/javascript">';
    echo "var sankeyData = " . $sankeyData;
    echo '</script>';	
	
	echo $html->css('sankey');
	echo $javascript->link(array('d3-3.5.6.min','sankey','mysankey'));	

    // Generate min and max number of genes
    echo $this->Form->create(false);
    echo $this->Form->input('Minimal overlapping genes', array('options' => array(1,2,3,4,5), 'empty' => 'Minimal # of overlap')); 

    echo $this->Form->end('Refine');
	
?>

