<?php
	header("Content-disposition: attachment; filename=$file_name");
	header("Content-type: text/plain"); 
	foreach($transcripts as $transcript){
		echo ">".$transcript['Transcripts']['transcript_id']."\n";
		echo $transcript['Transcripts']['orf_sequence']."\n";
	}
	foreach($gf_content as $gfc){
		echo ">".$gfc['Annotation']['gene_id']."\n";
		echo $gfc['Annotation']['seq']."\n";
	}
?>