<div class="page-header" style="margin-top:-15px;">
    <h2 class="text-primary"><?php echo "$col_names[0] - $col_names[1] intersection";?></h2>
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
    ///////////////// Left refinement /////////////////
    echo $this->Form->create(false, array('id' => 'left_boxes', 'class'=> 'refine_box'));
    echo '<div class="left_col"></div><div class="right_col"></div><br>';
    $options = array(
    'type' => 'button',
    'id' => 'left_boxes_button',
    'onclick' => 'draw_sankey()'
    );
    // echo $this->Form->button('  Refine  ',$options);
    echo $this->Form->end();
    ?>
  </div>
  <div class="col-md-4">
    <?php
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
    // echo $this->Form->button('  Refine  ',$options);
    echo $this->Form->end();
    ?>
  </div>
  <div class="col-md-4">
    <?php
    ///////////////// Right refinement /////////////////
    echo $this->Form->create(false, array('id' => 'right_boxes', 'class'=> 'refine_box'));
    echo '<div class="left_col"></div><div class="right_col"></div><br>';
    $options = array(
    'type' => 'button',
    'id' => 'right_boxes_button',
    'onclick' => 'draw_sankey()'
    );
    // echo $this->Form->button('  Refine  ',$options);
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
<section class="section-page-sm">
<!-- Sankey graph -->
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

?>
</div> <!-- end Sankey graph -->
</section>
