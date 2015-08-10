<div>
<h2>Lengths distribution gene family</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	
	<h3>Gene family ORF lengths</h3>

	<div class="subdiv">
	
	<?php
	//get standard stat info
	$min_size	= PHP_INT_MAX;
	$max_size	= -1;
	$average	= 0;
	$median		= 0;
	$tmp		= array();

	$data_size	= count($gene_sizes);		
	foreach($gene_sizes as $gs){
		//min and max
		if($gs < $min_size){$min_size = $gs;}
		if($gs > $max_size){$max_size = $gs;}		
		$tmp[]	= $gs;
		$average+= $gs;
	}
	$average	= intval($average/$data_size);
	sort($tmp);
	$median		= $tmp[intval($data_size/2)];
	$std_deviation	= 0;
	foreach($gene_sizes as $gene_id=>$gs){
		pr($gene_id."\t".$gs."\t".($gs-$average)*($gs-$average));
		$std_deviation+=(($gs-$average)*($gs-$average));
	}
	$std_deviation	= intval(sqrt($std_deviation/$data_size));
		
	echo "<dl class='standard'>\n";
	echo "<dt>Number of genes</dt>\n";
	echo "<dd>".$data_size."</dd>\n";
	echo "<dt>Minimum CDS length</dt>\n";
	echo "<dd>".$min_size."</dd>\n";
	echo "<dt>Maximum CDS length</dt>\n";
	echo "<dd>".$max_size."</dd>\n";	
	echo "<dt>Average CDS length<dt>\n";	
	echo "<dd>".$average."</dd>\n";
	echo "<dt>Median CDS length</dt>\n";
	echo "<dd>".$median."</dd>\n";
	echo "<dt>Standard deviation</dt>\n";
	echo "<dd>".$std_deviation."</dd>\n";
	echo "</dl>\n";

	//define bucket sizes
	$max_buckets	= 10;
	$num_buckets	= intval(sqrt(count($gene_sizes)));
	if($num_buckets > $max_buckets){$num_buckets = $max_buckets;}	
	$bucket_size	= intval(($max_size-$min_size) / $num_buckets);
	//create buckets
	$buckets	= array();
	foreach($gene_sizes as $gene_id=>$gs){
	    $bucket	= intval(($gs-$min_size)/$bucket_size);
	    $buckets[$gene_id] = $gs."\t".$bucket;
	}	
	
	pr($buckets);

	?>		
	</div>
	
</div>
	
</div>
