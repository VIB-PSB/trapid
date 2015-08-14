<div>
<h2><?php echo $titleIsAKeyword;?> to gene family</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
<h3><?php echo $titleIsAKeyword;?> to gene family</h3>
<div id="sankey" class="subdiv">

<?php
    echo '<script type="text/javascript">';
    echo "var sankeyData = " . $sankeyData .";";
    echo "var inflow_data = " . $inflow_data .";";
    echo "var outflow_data = " . $outflow_data .";";
    echo '</script>';	

	echo $html->css('sankey');
	echo $javascript->link(array('d3-3.5.6.min','sankey','mysankey'));	
    
   // The number of choices in the dropdown menus
    $number_of_choices = 31;

    ///////////////// Left refinement /////////////////
    $left_selectable_values = range(0,$left_maximum_count,round($left_maximum_count/$number_of_choices));
    $left_selectable_values[count($left_selectable_values) - 1] = $left_maximum_count;
    echo $form->create(false, array('id'=> 'left_refine_form'));
    $y = 0;
    while($y <= count($left_selectable_values)) {
        if($left_selectable_values[$y] >= $left_minimum_count){
            break;
        }
        $y++;
    }
    echo $form->input("Minimum $titleIsAKeyword size: ", array('options' => $left_selectable_values, 'id' =>'left_min', 'default'=>$left_minimum_count));
    echo $form->input("Maximum $titleIsAKeyword size: ", array('options' => array_reverse($left_selectable_values), 'id' =>'left_max'));
    $options = array(
    'type' => 'button',
    'id' => 'left_refine',
    'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end();


    ///////////////// Right refinement /////////////////
 
    // Generate min and max number of genes
    $selectable_values = range(0,$maximum_count,round($maximum_count/$number_of_choices));
    $selectable_values[count($selectable_values) - 1] = $maximum_count;// The largest family should always be selectable
    echo $form->create(false, array('id'=> 'right_refine_form'));
    $x = 0;
    while($x <= count($selectable_values)) {
        if($selectable_values[$x] >= $minimum_count){
            break;
        }
        $x++;
    }
    echo $form->input('Minimum gene family size: ', array('options' => $selectable_values, 'id' =>'min', 'default'=>$minimum_count));
    echo $form->input('Maximum gene family size: ', array('options' => array_reverse($selectable_values), 'id' =>'max'));
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
