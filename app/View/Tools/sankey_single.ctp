<?php
echo $this->Html->script('prototype-1.7.0.0');  // Prototype is still used in the sankey JS code
echo $this->Html->script("canvasXpress/canvasXpress.min.js");
?>
<div>
    <div class="page-header">
<h1 class="text-primary"><?php echo $titleIsAKeyword;?> to gene family</h1>
    </div>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
<h3><?php echo $titleIsAKeyword;?> to gene family</h3>
<div id="sankey" class="subdiv">

<?php
    echo '<script type="text/javascript">';
    echo "var sankey_data = " . $mapping .";";
    echo "var descriptions = " . $descriptions .";";
    echo "var urls = " . $urls.";";
    echo "var place_holder = '" . $place_holder ."';";
    echo '</script>';	

	echo $this->Html->css('sankey');
	echo $this->Html->script(array('d3-3.5.6.min','sankey','sankey_single'));

    echo '<div id="refinement">';
    echo $this->Form->create(false, array('class'=> 'refine_box'));
    echo $this->Form->input("Minimum Gene Family size: ", array('options' => array(), 'id' =>'min'));
    echo $this->Form->button('  Refine  ', array('type' => 'button', 'id' => 'refine', 'onclick' => 'draw_sankey()'));    
    echo $this->Form->end();

    echo '</div>';

?>
</div>
</div>
</div>
