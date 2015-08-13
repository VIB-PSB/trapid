<div>
<h2>Sankey Diagram</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
<h3>Sankey Diagram</h3>
<div id="sankey" class="subdiv">

<?php
    echo '<script type="text/javascript">';
    echo "var sankeyData = " . $sankeyData .";";
    echo "var inflow_data = " . $inflow_data .";";
    echo '</script>';	

	echo $html->css('sankey');
	echo $javascript->link(array('d3-3.5.6.min','sankey','mysankey'));	

    // The number of choices in the dropdown menus
    $number_of_choices = 31;
    // Generate min and max number of genes
    $selectable_values = range(0,$maximum_count,round($maximum_count/$number_of_choices));
    $selectable_values[$number_of_choices ] = $maximum_count;// The largest family should always be selectable
    echo $form->create(false, array('id'=> 'refine_form', 'style' => 'float:right;'));
    $x = 0;
    while($x <= count($selectable_values)) {
        if($selectable_values[$x] >= $minimum_count){
            break;
        }
        $x++;
    }
    echo $form->input('Minimum gene family size: ', array('options' => $selectable_values, 'id' =>'min', 'default'=>$x)); //,'empty' => '0'
    echo $form->input('Maximum gene family size: ', array('options' => array_reverse($selectable_values), 'id' =>'max')); //'empty' => $maximum_count,
    //echo '<br />'; Too big find something smaller
    $options = array(
    'type' => 'button',
    'id' => 'refine',
    'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end();
	
?>
</div>
</div>
</div>
