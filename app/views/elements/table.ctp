

<?php if($result && count($result)!=0): ?>

<div>
	<table cellpadding="0" cellspacing="0">
		<tr>
			<?php
			$first_res = $result[0];
			foreach($first_res as $key=>$value){
				echo "<th>";
				echo $key;
				echo "</th>\n";
			}
			?>
		</tr>

		<?php
		$i=0;
		foreach($result as $res){
			$class= null;
			if($i++ %2 ==0){
				$class = ' class="altrow"';
			}
			echo "<tr".$class.">";
			foreach($res as $key=>$val){
				echo "<td>";
				if($key=="gene_id"){
				    echo $html->link($val,array("controller="=>"genes","action"=>"view",$val));
				}
				else if($key=="go"){
				    $new_link_go = str_replace(":","-",$val);
				    echo $html->link($val,array("controller"=>"go","action"=>"view",$new_link_go));
				}
				else if($key=="gene_family" || $key=="gf_id"){
				    echo $html->link($val,array("controller"=>"gene_families","action"=>"view",$val));
				}
				else{
				    echo $val;
				}
				echo "</td>\n";
			}
			echo "</tr>\n";
		}
		?>
		
	</table>

</div>


<?php endif; ?>
