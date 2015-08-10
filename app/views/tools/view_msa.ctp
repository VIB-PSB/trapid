<?php
header("Content-type: text/plain");
header("Content-disposition: attachment; filename=$file_name");
if(isset($msa)){
	$explode	= explode(">",$msa);
	foreach($explode as $e){
		$split	= explode(";",$e);
		if(count($split)==2){
			echo ">".$split[0]."\n";
			echo $split[1]."\n";	
		}
	}
}
?>
