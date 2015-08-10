<h3>Download</h3>
<div class="subdiv">
	<?php
	echo "<form action='".$download_url."' method='post' >\n";
	echo "<select name='download_type' style='width:200px;'>\n";
	echo "<option value='table'>Table content</option>";
	echo "<option value='fasta_transcript'>Transcript multi-fasta</option>";
	echo "<option value='fasta_orf'>ORF multi-fasta</option>";
	if(isset($allow_reference_aa_download)){	
		echo "<option value='fasta_protein_ref'>AA multi-fasta (incl reference)</option>";
	}
	echo "</select>\n";
	echo "<input type='submit' value='Download'  style='margin-left:20px;'/>";	
	echo "</form>\n";	
	?>
</div>
