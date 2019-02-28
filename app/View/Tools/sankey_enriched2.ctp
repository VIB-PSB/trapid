<div class="page-header" style="margin-top:-15px;">
    <h2 class="text-primary"><?php echo "$col_names[0] - $col_names[1] - $col_names[2] intersection";?></h2>
</div>

<section class="page-section-xs">
    <!-- Sankey controls -->
    <div class="row" id="choices-row">
        <div class="panel panel-default" id="choices">
            <div class="panel-heading">
                <h3 class="panel-title">Sankey diagram display options</h3>
            </div>

            <div class="panel-body">
                <div class="col-md-4">
                    <?php
                    $number_of_choices = 31;
                    ///////////////// Left refinement /////////////////
                    echo $this->Form->create(false, array('id' => 'left_boxes', 'class'=> 'refine_box'));
                    echo '<div class="left_col"></div><div class="right_col"></div><br>';
                    $options = array(
                        'type' => 'button',
                        'id' => 'left_refine',
                        'onclick' => 'draw_sankey()'
                    );
                    // echo $this->Form->button('  Refine  ',$options);
                    echo $this->Form->end();
                    ?>
                </div>
                <div class="col-md-4">
                    <?php
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
                    // echo $this->Form->button('  Refine  ',$options);
                    echo $this->Form->end();

                    ?>
                </div>
                <div class="col-md-4">
                    <?php
                    ///////////////// Right refinement /////////////////
                    echo $this->Form->create(false, array('id'=> 'right_refine_form','class'=> 'refine_box'));
                    echo $this->Form->input("Minimum $col_names[2] size: ", array('options' => array(), 'id' =>'right_min'));
                    // echo $this->Form->button('  Refine  ', array('type' => 'button', 'id' => 'right_refine', 'onclick' => 'draw_sankey()'));
                    echo $this->Form->end();
                    ?>
                </div>
            </div>

            <div class="panel-footer">
                <div class="text-right"> <strong>Export as: </strong>
                    <button class="btn btn-default btn-xs" onclick="alert('To do!');" title="Export Sankey diagram (PNG)">PNG</button> <!-- TODO! -->
                    <button class="btn btn-default btn-xs" onclick="alert('To do!');" title="Export Sankey diagram (SVG)">SVG</button> |
                    <button type="submit" class="btn btn-primary btn-sm" onclick="draw_sankey()" title="Redraw Sankey diagram">
                        <span class="glyphicon glyphicon-repeat"></span> Redraw</button>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="page-section-sm">
<div id="sankey">

<?php
    echo '<script type="text/javascript">';
    echo "\nvar enrichedIdents = " . json_encode($enriched_gos) .";";
    echo "\nvar transcriptIdent = " . json_encode($transcriptGO) .";";
    echo "\nvar transcriptLabelGF = " . json_encode($transcriptLabelGF) .";";
    echo "\nvar descriptions = " . json_encode($descriptions) .";";
    echo "\nvar label_counts = " . json_encode($counts) .";";
    echo "\nvar total_count = " .   $exp_info['transcript_count'] .";";
    echo "\nvar dropdown_filter_name = " . json_encode($dropdown_names) .';';
    echo "\nvar urls = " . json_encode($urls) .";";
    echo "\nvar place_holder = '" . $place_holder ."';";
    echo "\nvar GO = '" . $GO ."';";
    echo "\nvar exp_id = '" . $exp_id ."';";
    echo '</script>';

	echo $this->Html->css('multi_sankey_intersection');
	echo $this->Html->script(array('d3-3.5.6.min','sankey','sankey_enriched2'));	


?>
</div>
</section>
