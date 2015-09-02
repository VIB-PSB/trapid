<div>
<h2><?php echo "$col_names[0] to $col_names[1] intersection";?></h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
<h3><?php echo"$col_names[0] to $col_names[1] intersection";?> </h3>
<div id="sankey" class="subdiv">

<?php
    echo '<script type="text/javascript">';
    echo "var selected_label = '". $selected_label ."';";
    echo "var mapping = " . json_encode($mapping) .";";
    echo "\nvar descriptions = ". json_encode($descriptions) .";";
    echo "\nvar label_counts = ". json_encode($counts) .";";
    echo "\nvar total_count = ".   $exp_info['transcript_count'] .";";
    echo 'var dropdown_filter_name = "'. $dropdown_name .'";';
    echo "\nvar urls = ". json_encode($urls) .";";
    echo "\nvar place_holder = '". $place_holder ."';";
    echo "\nvar exp_id = '" . $exp_id ."';";
    echo '</script>';

	echo $html->css('multi_sankey_intersection');
	echo $javascript->link(array('d3-3.5.6.min','sankey','sankey_intersection'));	
    echo '<div id="choices">';
    ///////////////// Left refinement /////////////////
    echo $form->create(false, array('id' => 'left_boxes', 'class'=> 'refine_box'));
    echo '<div class="left_col"></div><div class="right_col"></div><br>';
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
    echo $form->input("Normalization: ", array('options' => array('None','Intersection','Cluster'), 'id' =>'normalization'));
    echo $form->input('type: ', array('options' => array('All','MF','BP','CC'), 'id' =>'type','onchange' => 'middle_filter()'));
    $options = array(
    'type' => 'button',
    'id' => 'middle_refine',
    'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end();

    ///////////////// Right refinement /////////////////
    echo $form->create(false, array('id' => 'right_boxes', 'class'=> 'refine_box'));
    echo '<div class="left_col"></div><div class="right_col"></div><br>';
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
