<div class="page-header">
    <h1 class="text-primary"><?php echo $titleIsAKeyword; ?> to gene family</h1>
</div>
<div id="sankey">
    <?php
    echo '<script type="text/javascript">';
    echo 'var sankey_data = ' . $mapping . ";\n";
    echo 'var descriptions = ' . $descriptions . ";\n";
    echo 'var urls = ' . $urls . ";\n";
    echo "var place_holder = '" . $place_holder . "';\n";
    echo '</script>';

    echo $this->Html->css('sankey');
    echo $this->Html->script(['d3-3.5.6.min', 'd3-tip', 'sankey', 'sankey_single']);

    echo '<div id="refinement">';
    echo $this->Form->create(false, ['class' => 'refine_box']);
    echo $this->Form->input('Minimum Gene Family size: ', ['options' => [], 'id' => 'min']);
    echo $this->Form->button('  Refine  ', ['type' => 'button', 'id' => 'refine', 'onclick' => 'draw_sankey()']);
    echo $this->Form->end();
    echo '</div>';
    ?>
</div>
