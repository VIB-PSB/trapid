<?php
if(isset($file_name)){
	header("Content-disposition: attachment; filename=$file_name");
	header("Content-type: text/plain");
	if(isset($error)){
		echo $error."\n";
	}
	else{		
		if($type=="go"){
			echo "#Type\tGO-id\tEnrichment\tp-value\tsubset-ratio\tdescription\n";
			foreach($go_types as $go_type=>$go_ids){
				foreach($go_ids as $go_id){
					$res	= $result[$go_id];
					$desc	= $go_descriptions[$go_id][0];
					echo $go_type."\t".$go_id."\t".$res['enrichment']."\t".$res['p-value']."\t".$res['subset_ratio']."\t".$desc."\n";
				}
			}		
		}
		else if($type=="ipr"){
			echo "ProteinDomain\tEnrichment\tp-value\tsubset-ratio\tdescription\n";
			foreach($result as $res){
				$desc	= $ipr_descriptions[$res["ipr"]][0];
				echo $res["ipr"]."\t".$res["enrichment"]."\t".$res["p-value"]."\t".$res["subset_ratio"]."\t".$desc."\n";
			}
		}	
	}
}
else{
	if(isset($error)){
		echo $error."\n";
	}
}
?>
