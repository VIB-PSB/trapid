<div>
<h2><?php echo "$col_names[0] to $col_names[1] intersection";?></h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
<h3><?php echo"$col_names[0] to $col_names[1] intersection";?> </h3>
<div id="sankey" class="subdiv">

<?php
    echo '<script type="text/javascript">';
    echo "var mapping = " . json_encode($mapping) .";";
    echo "var descriptions = " . json_encode($descriptions) .";";
    echo "var urls = " . json_encode($urls) .";";
    echo "var place_holder = '" . $place_holder ."';";
    echo '</script>';

	echo $html->css('multi_sankey_intersection');
	echo $javascript->link(array('d3-3.5.6.min','sankey','multi_sankey_intersection'));	

    $number_of_choices = 31;
    echo '<div id="choices">';
    ///////////////// Left refinement /////////////////
    echo $form->create(false, array('id' => 'left_boxes', 'class'=> 'refine_box'));
    $options = array(
    'type' => 'button',
    'id' => 'left_boxes_button',
    'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end(); 

    ///////////////// Middle refinement /////////////////

    echo $form->create(false, array('id'=> 'middle_refine_form', 'class'=> 'refine_box'));
    echo $form->input("Minimum $col_names[1] size: ", array('options' => array(), 'id' =>'middle_min'));
    $options = array(
    'type' => 'button',
    'id' => 'middle_refine',
    'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end();

    ///////////////// Right refinement /////////////////
    echo $form->create(false, array('id' => 'right_boxes', 'class'=> 'refine_box'));
    $options = array(
    'type' => 'button',
    'id' => 'right_boxes_button',
    'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end(); 

?>
</div>
</div>
</div>
</div>