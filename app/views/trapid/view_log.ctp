<div>
<h2>Log history</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	
	<h3>Log history</h3>
	<div class="subdiv">

			
		<div style='border:1px solid black;background-color:white;width:700px;padding:10px;'>
		<?php
		$colors	= array(0=>"#000000",1=>"#202020",2=>"#404040",3=>"#606060");
		foreach($log_info as $li){
			$l	= $li['ExperimentLog'];	
			$date	= $l['date'];
			$action	= $l['action'];
			$param	= $l['parameters'];
			$depth	= $l['depth'];
			echo "+ <span style='color:".$colors[$depth]."'>";
			for($i=0;$i<$depth;$i++){echo "&nbsp;&nbsp;";}
			echo $date."\t".$action."\t".$param;
			echo "</span><br/>\n";
		
		}
		?>	
		</div>

		<br/><br/>

		<?php
		/*				
		$num_rows	= count($log_info)+4;
		if($num_rows>20){$num_rows=20;}
		echo "<textarea rows='".$num_rows."' style='width:700px;'>";					
		foreach($log_info as $li){
			$l	= $li['ExperimentLog'];	
			$date	= $l['date'];
			$action	= $l['action'];
			$param	= $l['parameters'];
			$depth	= $l['depth'];
			for($i=0;$i<=$depth;$i++){echo " * ";}
			echo $date."\t".$action."\t".$param."\n";
		}
		echo "</textarea>\n";
		*/
		?>
	</div>

</div>
</div>
