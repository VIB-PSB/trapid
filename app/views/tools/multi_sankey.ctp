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
    echo "var urls = " . json_encode($urls) .";";
    echo "var place_holder = '" . $place_holder ."';";
    echo '</script>';

	echo $html->css('multi_sankey');
	echo $javascript->link(array('d3-3.5.6.min','sankey','multi_sankey'));	

    $number_of_choices = 31;
    echo '<div id="choices">';
    ///////////////// Left refinement /////////////////
    echo $form->create(false, array('id'=> 'left_refine_form'));
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

    echo $form->create(false, array('id'=> 'middle_refine_form'));
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
    echo $form->create(false, array('id'=> 'right_refine_form'));
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
