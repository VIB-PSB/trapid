<div>
<h2><?php echo "$col_names[0] to $col_names[1] to $col_names[2] intersection";?></h2>
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

	echo $html->css('multi_sankey_intersection');
	echo $javascript->link(array('d3-3.5.6.min','sankey','sankey_enriched2'));	

    $number_of_choices = 31;
    echo '<div id="choices">';
    ///////////////// Left refinement /////////////////
    echo $form->create(false, array('id' => 'left_boxes', 'class'=> 'refine_box'));
    echo '<div class="left_col"></div><div class="right_col"></div><br>';
    $options = array(
      'type' => 'button',
      'id' => 'left_refine',
      'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end();

    ///////////////// Middle refinement /////////////////

    echo $form->create(false, array('id'=> 'middle_refine_form','class'=> 'refine_box'));
    echo $form->input('type: ', array('options' => array('All','MF','BP','CC'), 'id' =>'type','onchange' => 'middle_filter()'));
    echo $form->input('p value: ', array('options' => array(), 'id' => 'pvalue','onchange' => 'middle_filter()'));

    $options=array('x'=>' all ', '+'=>' positive ', '-'=>' negative ');
    // Select positives by default, also remove the ugly box around the radio buttions
    echo 'Enrichment: <div style="padding-left:20px;">';
    $attributes=array('value'=>'x',
                      'legend'=>false,
                      'separator' => '<br />',
                      'onchange' => 'middle_filter()');
    echo $form->radio('Enrichment',$options,$attributes);
    echo '</div>';

    echo $form->input(' show hidden', array('type' => 'checkbox', 'id' => 'hidden','onchange' => 'middle_filter()'));
    echo $form->input(' normalize links', array('type' => 'checkbox', 'id' => 'normalize'));

    $options = array(
      'type' => 'button',
      'id' => 'middle_refine',
      'onclick' => 'draw_sankey()'
    );
    echo $form->button('  Refine  ',$options);
    echo $form->end();

    ///////////////// Right refinement /////////////////
    echo $form->create(false, array('id'=> 'right_refine_form','class'=> 'refine_box'));
    echo $form->input("Minimum $col_names[2] size: ", array('options' => array(), 'id' =>'right_min'));
    echo $form->button('  Refine  ', array('type' => 'button', 'id' => 'right_refine', 'onclick' => 'draw_sankey()'));    
    echo $form->end();


?>
</div>
</div>
</div>
</div>
