<div id="sankey"  >
<!--<div id="t" ></div> -->
<?php
    //echo $sankeyData;
    echo '<script type="text/javascript">';
    echo "var sankeyData = " . $sankeyData;
    echo '</script>';	
	
	echo $html->css('sankey');
	echo $javascript->link(array('d3-3.5.6.min','sankey','mysankey'));	

    // Generate min and max number of genes
    $selectable_values = range(0,$maximum_count,round($maximum_count/30 + 1));
    echo $form->create(false, array('id'=> 'refine_form', 'style' => 'float:right;'));
    //echo $form->create(false);
    echo $form->input('Minimum gene family size: ', array('options' => $selectable_values, 'empty' => '0','id' =>'min')); 
    echo $form->input('Maximum gene family size: ', array('options' => array_reverse($selectable_values), 'empty' => $maximum_count,'id' =>'max')); 
    echo '<br />';
    $options = array(
    'type' => 'button',
    'id' => 'refine',
    'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end();
	
?>
</div>

