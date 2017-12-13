<div>
	<h3>Species selection</h3>
	<div class="subdiv">
		<?php	
		foreach($all_species as $spec){
			echo $spec['AnnotSources']['common_name'];
			echo "<br/>\n";
		}
		?>	
	</div>
</div>