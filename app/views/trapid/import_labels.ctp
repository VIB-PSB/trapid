<div>
<h2>Import labels</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>	
	
	<h3>Import labels</h3>
	<div class="subdiv">
		<?php if(isset($error)){echo "<span class='error'>".$error."</span><br/><br/>\n";}?>
		<?php if(isset($message)){echo "<span class='message'>".$message."</span><br/><br/>\n";}?>

		<div style="margin-bottom:10px;font-weight:bold;width:700px;">				
		</div>
		<div style="margin-bottom:10px;">
			<div style="width:700px;margin-bottom:10px;">
			   Please enter a file containing transcript identifiers which should have the same label.
			</div>
			<div style="font:monospace;background-color:white;padding:5px;width:500px;">
			    transcript1<br/>
			    transcript3<br/>
			    transcript1012<br/>
			    ...<br/>			  	
			</div>		
			<br/>
		
			
    			<?php
    			 echo $form->create("",array("controller"=>"trapid","action"=>"import_labels/".$exp_id,
						"type"=>"post","enctype"=>"multipart/form-data",
						"id"=>"import_labels_form","name"=>"import_labels_form"));    			       
    			?>

			<input name="uploadedfile" type="file" />
			<br/><br/>
			<input type="text" name="label" /> <span>Label for the transcripts</span>
			<br/><br/>
			<input type="submit" value="Import labels" style="width:200px;margin-bottom:10px;margin-top:5px;"/>
			</form>						
		</div>	
	</div>
</div>
</div>