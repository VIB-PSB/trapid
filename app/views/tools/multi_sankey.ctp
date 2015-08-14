<div>
<h2><?php echo $titleIsAKeyword;?> to gene family</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
<h3><?php echo $titleIsAKeyword;?> to gene family</h3>
<div id="sankey" class="subdiv">

<?php


	echo $html->css('sankey');
	echo $javascript->link(array('d3-3.5.6.min','sankey','mysankey'));	
   
?>
</div>
</div>
</div>
