<div>
    <div class="page-header">
<h1 class="text-primary"><?php echo "$col_names[0] to $col_names[1] intersection";?></h1>
    </div>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>

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
    echo "\nvar GO = '" . $GO ."';";
    echo '</script>';

	echo $this->Html->css('multi_sankey_intersection');
	echo $this->Html->script(array('d3-3.5.6.min','sankey','sankey_intersection'));	
    echo '<div id="choices">';
    ///////////////// Left refinement /////////////////
    echo $this->Form->create(false, array('id' => 'left_boxes', 'class'=> 'refine_box'));
    echo '<div class="left_col"></div><div class="right_col"></div><br>';
    $options = array(
    'type' => 'button',
    'id' => 'left_boxes_button',
    'onclick' => 'draw_sankey()'
    );
    echo $this->Form->button('  Refine  ',$options);
    echo $this->Form->end(); 

    ///////////////// Middle refinement /////////////////

    echo $this->Form->create(false, array('id'=> 'middle_refine_form', 'class'=> 'refine_box'));
    echo $this->Form->input("Minimum $col_names[1] size: ", array('options' => array(), 'id' =>'middle_min'));
    echo $this->Form->input("Normalization: ", array('options' => array('None','Intersection','Cluster'), 'id' =>'normalization'));
    echo $this->Form->input('type: ', array('options' => array('All','MF','BP','CC'), 'id' =>'type','onchange' => 'middle_filter()'));
    $options = array(
    'type' => 'button',
    'id' => 'middle_refine',
    'onclick' => 'draw_sankey()'
    );
    echo $this->Form->button('  Refine  ',$options);
    echo $this->Form->end();

    ///////////////// Right refinement /////////////////
    echo $this->Form->create(false, array('id' => 'right_boxes', 'class'=> 'refine_box'));
    echo '<div class="left_col"></div><div class="right_col"></div><br>';
    $options = array(
    'type' => 'button',
    'id' => 'right_boxes_button',
    'onclick' => 'draw_sankey()'
    );
    echo $this->Form->button('  Refine  ',$options);
    echo $this->Form->end(); 

?>
</div>
</div>
</div>
</div>
