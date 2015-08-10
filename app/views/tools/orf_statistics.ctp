<div>
<h2>Statistics</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	<h3>Meta annotation</h3>
	<div class="subdiv">
	    <dl class="standard">	
		<?php
		if(array_key_exists("none",$meta_info)){
			echo "<dt>No info</dt>";
			echo "<dd>".$meta_info['none']['total']."</dd>\n";
		}	
		if(array_key_exists("FullLength",$meta_info)){	
			echo "<dt>Full Length</dt>";
			echo "<dd>".$meta_info['FullLength']['total']."</dd>\n";
		}
		if(array_key_exists("Partial",$meta_info)){	
			echo "<dt>Partial</dt>";
			echo "<dd>".$meta_info['Partial']['total']."</dd>\n";
		}
		if(array_key_exists("TooLong",$meta_info)){	
			echo "<dt>Too long</dt>";
			echo "<dd>".$meta_info['TooLong']['total']."</dd>\n";
		}

		?>
	    </dl>		
	</div>
</div>
</div>