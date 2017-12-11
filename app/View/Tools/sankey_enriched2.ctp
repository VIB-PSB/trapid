<?php
//    echo $this->Html->script('prototype-1.7.0.0');
//    echo $this->Html->script("canvasXpress/canvasXpress.min.js");
?>
<div>
    <div class="page-header">
<h1 class="text-primary"><?php echo "$col_names[0] to $col_names[1] to $col_names[2] intersection";?></h1>
    </div>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
<h3><?php echo "$col_names[0] to $col_names[1] to $col_names[2] intersection";?></h3>
<div id="sankey" class="subdiv">

<?php
    echo '<script type="text/javascript">';
    echo "\nvar enrichedIdents = " . json_encode($enriched_gos) .";";
    echo "\nvar transcriptIdent = " . json_encode($transcriptGO) .";";
    echo "\nvar transcriptLabelGF = " . json_encode($transcriptLabelGF) .";";
    echo "\nvar descriptions = " . json_encode($descriptions) .";";
    echo "\nvar label_counts = " . json_encode($counts) .";";
    echo "\nvar total_count = " .   $exp_info['transcript_count'] .";";
    echo "var dropdown_filter_name = " . json_encode($dropdown_names) .';';
    echo "\nvar urls = " . json_encode($urls) .";";
    echo "\nvar place_holder = '" . $place_holder ."';";
    echo "\nvar GO = '" . $GO ."';";
    echo "\nvar exp_id = '" . $exp_id ."';";
    echo '</script>';

	echo $this->Html->css('multi_sankey_intersection');
	echo $this->Html->script(array('d3-3.5.6.min','sankey','sankey_enriched2'));	

    $number_of_choices = 31;
    echo '<div id="choices">';
    ///////////////// Left refinement /////////////////
    echo $this->Form->create(false, array('id' => 'left_boxes', 'class'=> 'refine_box'));
    echo '<div class="left_col"></div><div class="right_col"></div><br>';
    $options = array(
      'type' => 'button',
      'id' => 'left_refine',
      'onclick' => 'draw_sankey()'
    );
    echo $this->Form->button('  Refine  ',$options);
    echo $this->Form->end();

    ///////////////// Middle refinement /////////////////

    echo $this->Form->create(false, array('id'=> 'middle_refine_form','class'=> 'refine_box'));
    echo $this->Form->input('type: ', array('options' => array('All','MF','BP','CC'), 'id' =>'type','onchange' => 'middle_filter()'));
    echo $this->Form->input('p value: ', array('options' => array(), 'id' => 'pvalue','onchange' => 'middle_filter()'));
    echo $this->Form->input('Enrichment: ', array('options' => array('positive', 'negative'), 'id' => 'enrichment','onchange' => 'middle_filter()'));

    echo $this->Form->input(' show hidden', array('type' => 'checkbox', 'id' => 'hidden','onchange' => 'middle_filter()'));
    echo $this->Form->input(' normalize links', array('type' => 'checkbox', 'id' => 'normalize'));

    $options = array(
      'type' => 'button',
      'id' => 'middle_refine',
      'onclick' => 'draw_sankey()'
    );
    echo $this->Form->button('  Refine  ',$options);
    echo $this->Form->end();

    ///////////////// Right refinement /////////////////
    echo $this->Form->create(false, array('id'=> 'right_refine_form','class'=> 'refine_box'));
    echo $this->Form->input("Minimum $col_names[2] size: ", array('options' => array(), 'id' =>'right_min'));
    echo $this->Form->button('  Refine  ', array('type' => 'button', 'id' => 'right_refine', 'onclick' => 'draw_sankey()'));    
    echo $this->Form->end();


?>
</div>
</div>
</div>
</div>
