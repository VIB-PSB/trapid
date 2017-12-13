<?php
App::uses("Component", "Controller");
class StatisticsComponent extends Component{



  function makeVennOverview($transcript2labels){
    $result	= array();
    foreach($transcript2labels as $transcript=>$labels){
      $labels		= array_unique($labels);
      sort($labels);
      $label_string	= implode(";;;",$labels);
      if(!array_key_exists($label_string,$result)){$result[$label_string] = 0;}
      $result[$label_string]++;
    }
    return $result;
  }




  function create_json_data_infovis($data,$data_label){
    $values	= $data['values'];
    $labels	= $data['labels'];
    $result	= array();
    $new_values	= array();
    for($i=0;$i<count($values);$i++){
      $val	= array();
      $val["label"] = $labels[$i];
      $val["values"] = array($values[$i]);
      $new_values[] = $val;
    }
    //$result["color"]	= array("#FF0000","#00FF00","#0000FF");

    $result["label"]	= array($data_label);
    $result["values"]	= $new_values;
    return $result;
  }


  function normalize_json_data($json){
    //first step, get the sum for each possible entry
    $sums	= array();
    foreach($json['label'] as $k=>$v){$sums[$k]=0;} //initialize sums array
    foreach($json['values'] as $val){
      foreach($val['values'] as $k=>$v){
	$sums[$k] = $sums[$k] + $v;
      }
    }
    //sums is the total amount of transcripts/genes

    //second step: normalize the data
    foreach($json['values'] as $index=>$val){
      foreach($val['values'] as $k=>$v){
	$norm	= ceil(100*$v/$sums[$k]);
	//$norm = 100*$v/$sums[$k];
	$json['values'][$index]['values'][$k] = $norm;
      }
    }
    //pr($json);
    return $json;
  }


  function update_json_data($data_label,$data,$json,$bins,$reduce_count=true){
    $num_bins		= count($bins['labels']);
    $min_val_arr	= explode(",",$bins['labels'][0]);
    $max_val_arr 	= explode(",",$bins['labels'][$num_bins-1]);
    $min_val		= $min_val_arr[0];
    $max_val		= $max_val_arr[1];
    $interval		= $min_val_arr[1]-$min_val_arr[0];

    //format of json requires multiple sub-arrays below the "label" and "value" sections
    //using the current index is a convenient way to directly get the new index.
    $curr_index		= count($json['label']);
    $json['label'][]	= $data_label;

    for($i=0;$i<$num_bins;$i++){
      $json['values'][$i]['values'][$curr_index]	= 0;
    }
    //pr($json);


    foreach($data as $d){
      $bin		= floor(($d-$min_val)/$interval);
      if($bin>=$num_bins){$bin = $num_bins-1;}   //can only happen when one of the values of data is larger than any in the bins
      if($bin<0){$bin = 0;} //can only happen when one of the data values is smaller than any in the bins
      $prev_value	= $json['values'][$bin]['values'][$curr_index];
      $json['values'][$bin]['values'][$curr_index]	= $prev_value + 1;

      if($reduce_count){
      	//reduce the number of counts for the default counting. This way the sum is still correct
      	$prev_value_all	= $json['values'][$bin]['values'][0];
      	$json['values'][$bin]['values'][0] = $prev_value_all-1;
      }
    }
    //pr($json);
    return $json;
  }


  function create_length_bins($data,$num_bins){
    sort($data);	//sort lengths from small to big
    $result		= array();
    for($i=0;$i<$num_bins;$i++){$result[$i]=0;}	// initialize array, makes it easier downstream
    $smallest_length	= $data[0];
    $largest_length	= $data[count($data)-1];
    $bin_size		= ($largest_length-$smallest_length) / $num_bins;
    foreach($data as $d){
      $bin		= floor(($d-$smallest_length)/$bin_size);
      if($bin>=$num_bins){$bin = $num_bins-1;}
      $result[$bin]++;
    }
    $labels 		= array();
    for($i=0;$i<$num_bins;$i++){
      if($i!=($num_bins-1)){
      	$labels[]	= "".($smallest_length+round($i*$bin_size)).",".($smallest_length+round(($i+1)*$bin_size));
      }
      else{
	$labels[]	= "".($smallest_length+round($i*$bin_size)).",".$largest_length;
      }
    }
    $final_result = array("values"=>$result,"labels"=>$labels);
    return $final_result;
  }




  function create_bins($data,$num_bins,$use_standard_bins = true){
    ksort($data);
    $result		= array();
    $keys		= array_keys($data);
    $largest_key	= $keys[count($keys) - 1];
    $smallest_key	= $keys[0];
    $divider		= 10;
    if($use_standard_bins){
      $divider		= 1000;
    }

    //round the keys.
    $bottom		= $divider * floor($smallest_key / $divider);
    $top		= $divider * ceil($largest_key / $divider);
    $diff		= round(($top - $bottom)/$num_bins);
    for($i = 0; $i < $num_bins; $i++){
      $b		= $bottom + $i * $diff;
      $t		= $bottom + ($i+1) * $diff ;
      $key		= $b."->".$t;
      $result[$key]	= 0;
      foreach($data as $k=>$v){
	  if($k>=$b && $k<=$t){$result[$key] ++ ; }
      }
    }

    return $result;
  }


  function get_stats_group_data(){
	 $group_data		= array("structural"=>array("avg_num_introns1","avg_num_introns2","avg_intron_length","avg_exon_length"),
				       	"codon"=>array(	"Alanine"=>array("GCT","GCC","GCA","GCG"),
							"Arginine"=>array("CGT","CGC","CGA","CGG","AGA","AGG"),
							"Asparagine"=>array("AAT","AAC"),
							"Aspartic acid"=>array("GAT","GAC"),
							"Cysteine"=>array("TGT","TGC"),
							"Glutamine"=>array("CAA","CAG"),
							"Glutamic acid"=>array("GAA","GAG"),
							"Glycine"=>array("GGT","GGC","GGA","GGG"),
							"Histidine"=>array("CAT","CAC"),
							"Isoleucine"=>array("ATT","ATC","ATA"),
							"Leucine"=>array("TTA","TTG","CTT","CTC","CTA","CTG"),
							"Lysine"=>array("AAA","AAG"),
							"Methionine"=>array("ATG"),
							"Phenylalanine"=>array("TTT","TTC"),
							"Proline"=>array("CCT","CCC","CCA","CCG"),
							"Serine"=>array("TCT","TCC","TCA","TCG","AGT","AGC"),
							"Threonine"=>array("ACT","ACC","ACA","ACG"),
							"Tryptophan"=>array("TGG"),
							"Tyrosine"=>array("TAT","TAC"),
							"Valine"=>array("GTT","GTC","GTA","GTG"),
							"STOP"=>array("TAA","TGA","TAG")
							),
					"sequence"=>array("cds_length","gc_percentage")
					);
	 return $group_data;
  }
  function get_stats_explanations(){
	 $explanations	= array("avg_num_introns1"=>"Sum of all introns of a genome, divided by the total number of genes in the genome.",
					"avg_num_introns2"=>"Sum of all introns of a genome, divided by the number of genes in the genome which have at least 1 intron.",
					"avg_intron_length"=>"Sum of all intron lengths of genes within a genome, divided by the number of introns within the genome.",
					"avg_exon_length"=>"Sum of all exon lengths of genes within a genome, divided by the number of exons within the genome.",
					"cds_length"=>"Sum of all exon lengths of genes within a genome.",
					"gc_percentage"=>"Percentage of G/C nucleotides within the exons."
					);
	 return $explanations;
  }

}
?>
