<div>
<h2><?php echo $titleIsAKeyword;?> to gene family</h2>
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

	echo $html->css('sankey');
	echo $javascript->link(array('d3-3.5.6.min','sankey','sankey_single'));

    echo '<div id="refinement">';
    echo $form->create(false, array('class'=> 'refine_box'));
    echo $form->input("Minimum Gene Family size: ", array('options' => array(), 'id' =>'min'));
    echo $form->button('  Refine  ', array('type' => 'button', 'id' => 'refine', 'onclick' => 'draw_sankey()'));    
    echo $form->end();

    echo '</div>';

?>
</div>
</div>
</div>
