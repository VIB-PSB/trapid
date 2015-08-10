<div>
<h2>Transcript file management</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>	
	<h3>Upload transcript files</h3>
	<div class="subdiv border">
	    <div style="margin-bottom:10px;font-weight:bold;width:700px;">
		Maximum file-size is 30Mb. If your file is larger, compress the file (using zip or gzip) and upload the compressed file.
	    </div>
	    <div>
		<div style="width:700px;margin-bottom:10px;">
		    Please enter a multi-fasta file, with each transcript identifier's length not exceeding 50 characters.
		</div>
		<div style="font:monospace;background-color:white;padding:5px;width:500px;">
		    >transcript_identifier1<br/>
		    AAGCTAGAGATCTCGAGAGAGAGAGCTAGAGCTAGC...<br/>
		    >transcript_identifier2<br/>
		    AAGCTAGAGAGCTCTAGGAATCGAC...<br/>
		    ...<br/>		
		</div>		
		<br/>
		<?php if(isset($error)){echo "<span class='error'>".$error."</span><br/><br/>\n";}?>
	
		<?php
		    echo $form->create("",array("controller"=>"trapid","action"=>"import_data/".$exp_id,
						"type"=>"post","enctype"=>"multipart/form-data",
						"id"=>"import_data_form","name"=>"import_data_form"));
		?>
		<input name="type" type="hidden" value="upload_file"/>
		<input type="radio" name="uploadtype" value="file" checked="checked" id="r1"/>      
		<span style="margin-right:8px;margin-left:5px;">File</span> 
		<input name="uploadedfile" type="file" id="ri1"/>
		<br/><br/>
		<input type="radio" name="uploadtype" value="url" id="r2"/>		
		<span style="margin-left:5px;margin-right:5px;">URL</span>
		<input type="text" name="uploadedurl" size="35" id="ri2" disabled="disabled"/>
		<br/><br/>
		<input type="checkbox" name="include_label" id="include_label"/>
		<span style="margin-left:5px;">Assign label to uploaded transcripts</span>
		<input type="text" id="label_name" name="label_name" style="margin-left:5px;" disabled="disabled"/>			
		<br/><br/>
		<input type="submit" value="Upload file / define URL" style="width:200px;margin-bottom:10px;margin-top:5px;"/>
		</form>
	    </div>
	</div>

	<h3>Delete transcript files</h3>
	<div class="subdiv border">
	    <?php if(count($uploaded_files)>0):?>
	    <div>
		<?php if(isset($error)){echo "<span class='error'>".$error."</span><br/><br/>\n";}?>
		<?php
		    echo $form->create("",array("controller"=>"trapid","action"=>"import_data/".$exp_id,
						"type"=>"post","enctype"=>"multipart/form-data",
						"id"=>"import_data_form","name"=>"import_data_form"));		    		   
		?>
		<input name="type" type="hidden" value="delete_file"/>
		<table cellpadding="0" cellspacing="0" style="width:730px;">
		    <tr>
			<th style="width:10%">Type</th>
			<th style="width:50%">Name</th>
			<th style="width:20%">Label</th>
			<th style="width:10%">Status</th>
			<th style="width:10%">Delete</th>
		    </tr>
		    <?php
			foreach($uploaded_files as $data_upload){
				$du = $data_upload['DataUploads'];
				echo "<tr>";
				echo "<td>".$du["type"]."</td>";
				echo "<td>".$du["name"]."</td>";
				echo "<td>".$du["label"]."</td>";
				if($du['status']=="uploaded" || $du['status']=="to_download"){echo "<td>Ready</td>";}
				else if($du['status']=="error"){echo "<td style='color:red'>Error</td>";}
				else{echo "<td></td>";}
				echo "<td><input type='checkbox' name='id_".$du['id']."'/></td>\n";
				echo "</tr>\n";	
			}
		    ?>
		</table>
		<br/>
		<input type="submit" value="Delete selected files/URLs" style="width:200px;margin-bottom:10px;margin-top:5px;"/>	
		</form>			
	    </div>		
	    <?php else: ?>
		<span>No transcript files uploaded</span>
	    <?php endif;?>
	</div>


	
	<h3>Upload transcripts into database</h3>
	<div class="subdiv border">
	    <div>	   
		<?php
		    echo $form->create("",array("controller"=>"trapid","action"=>"import_data/".$exp_id,
						"type"=>"post","enctype"=>"multipart/form-data",
						"id"=>"import_data_form","name"=>"import_data_form"));		    		   
		?>
		<input name="type" type="hidden" value="database_file"/>
		<?php
			$disabled=null;
			if(count($uploaded_files)==0){
				$disabled=" disabled='disabled' ";
			}
			echo "<input type='submit' $disabled value='Load data from files/URLs into database' style='width:250px;margin-bottom:10px;margin-top:5px;'/>\n";
			if(count($uploaded_files)==0){
				echo "<span style='margin-left:20px;color:red;font-weight:bold;'>No files have been uploaded or URLs defined for data transfer</span>\n";
			}
		?>
		</form>
	    </div>
	</div>


	<script type="text/javascript">
	//<![CDATA[
		
		$("ri1").observe("change",function(){
			var max_size = 32000000;
		   	if( $("ri1").files[0].size > max_size){
				alert("Maximum size of file upload is 32MB. This upload will not work!");				
			}
		});
	

		$("include_label").observe("change",function(){
			if($("include_label").checked){
				$("label_name").disabled = null;
				delete 	$("label_name").disabled;
			}
			else{
				$("label_name").disabled = "disabled";	
			}
		});
		
		$("r1").observe("change",function(){
			sl();
		});
		$("r2").observe("change",function(){
			sl();
		});
		function sl(){		
			var ena = "";
			var dis = "";
			if($("r1").checked){
				ena = "ri1";
				dis = "ri2";			
			}
			else if($("r2").checked){
				ena = "ri2";
				dis = "ri1";
			}
			if(ena!="" && dis!=""){
				$(ena).disabled = null;
				delete $(ena).disabled;
				$(dis).disabled="disabled";
			}
		}				
		
	//]]>
	</script>
</div>	
</div>
