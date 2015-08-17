<div>
<h2><?php echo $titleIsAKeyword;?> to gene family</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
<h3><?php echo $titleIsAKeyword;?> to gene family</h3>
<div id="sankey" class="subdiv">

<?php
    echo '<script type="text/javascript">';
    echo "var first_mapping = " . json_encode($first_mapping) .";";
    echo "var second_mapping = " . json_encode($second_mapping) .";";
    echo '</script>';

	echo $html->css('multi_sankey');
	echo $javascript->link(array('d3-3.5.6.min','sankey','multi_sankey'));	

    $number_of_choices = 31;
    echo '<div id="choices">';
    ///////////////// Left refinement /////////////////
    /*$left_selectable_values = range(0,$left_maximum_count,round($left_maximum_count/$number_of_choices));
    $left_selectable_values[count($left_selectable_values) - 1] = $left_maximum_count;
    
    $y = 0;
    while($y <= count($left_selectable_values)) {
        if($left_selectable_values[$y] >= $left_minimum_count){
            break;
        }
        $y++;
    } */
    echo $form->create(false, array('id'=> 'left_refine_form'));
    //echo $form->input("Minimum $first_col size: ", array('options' => $left_selectable_values, 'id' =>'left_min', 'default'=>$left_minimum_count));
    //echo $form->input("Maximum $first_col size: ", array('options' => array_reverse($left_selectable_values), 'id' =>'left_max'));
    echo $form->input("Minimum $first_col size: ", array('options' => array(), 'id' =>'left_min'));
    echo $form->input("Maximum $first_col size: ", array('options' => array(), 'id' =>'left_max'));
    $options = array(
    'type' => 'button',
    'id' => 'left_refine',
    'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end();



    ///////////////// Middle refinement /////////////////
   /* $middle_selectable_values = range(0,$middle_maximum_count,round($middle_maximum_count/$number_of_choices));
    $middle_selectable_values[count($middle_selectable_values) - 1] = $middle_maximum_count;
    
    $y = 0;
    while($y <= count($middle_selectable_values)) {
        if($middle_selectable_values[$y] >= $middle_minimum_count){
            break;
        }
        $y++;
    }*/
    echo $form->create(false, array('id'=> 'middle_refine_form'));
 //   echo $form->input("Minimum $second_col size: ", array('options' => $middle_selectable_values, 'id' =>'middle_min', 'default'=>$middle_minimum_count));
 //   echo $form->input("Maximum $second_col size: ", array('options' => array_reverse($middle_selectable_values), 'id' =>'middle_max'));
    echo $form->input("Minimum $second_col size: ", array('options' => array(), 'id' =>'middle_min'));
    echo $form->input("Maximum $second_col size: ", array('options' => array(), 'id' =>'middle_max'));
    $options = array(
    'type' => 'button',
    'id' => 'middle_refine',
    'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end();


    ///////////////// Right refinement /////////////////
   /* $right_selectable_values = range(0,$right_maximum_count,round($right_maximum_count/$number_of_choices));
    $right_selectable_values[count($right_selectable_values) - 1] = $right_maximum_count;
    
    $y = 0;
    while($y <= count($right_selectable_values)) {
        if($right_selectable_values[$y] >= $right_minimum_count){
            break;
        }
        $y++;
    } */
    echo $form->create(false, array('id'=> 'right_refine_form'));
//    echo $form->input("Minimum $third_col size: ", array('options' => $right_selectable_values, 'id' =>'right_min', 'default'=>$right_minimum_count));
//    echo $form->input("Maximum $third_col size: ", array('options' => array_reverse($right_selectable_values), 'id' =>'right_max'));
    echo $form->input("Minimum $third_col size: ", array('options' => array(), 'id' =>'right_min'));
    echo $form->input("Maximum $third_col size: ", array('options' => array(), 'id' =>'right_max'));
    $options = array(
    'type' => 'button',
    'id' => 'right_refine',
    'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end();


?>
</div>
</div>
</div>
</div>
