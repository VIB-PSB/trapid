<div class="page-header">
    <h1 class="text-primary">Associated gene families</h1>
</div>
	<?php // echo $this->element("trapid_experiment"); ?>
    <section class="page-section-sm">
	<h3>Overview</h3>
		<dl class="standard dl-horizontal">
			<?php
			if($type=="go"){
				echo "<dt>GO term</dt>";
				echo "<dd>";
				$go_web	= str_replace(":","-",$go); 			
			    	if(!$exp_info['allow_linkout']) {
			    	    echo $go;
                        echo "&nbsp;";
                        echo $this->element("go_category_badge", array("go_category"=>$go_category, "small_badge"=>true));

                    }
			    	else {
			    	    echo $this->Html->link($go,$exp_info['datasource_URL']."go/view/".$go_web);
                        echo "&nbsp;";
                        echo $this->element("go_category_badge", array("go_category"=>$go_category, "small_badge"=>true));
			    	}
            echo "&nbsp; &nbsp;";
            echo  $this->element("linkout_func", array("linkout_type"=>"amigo", "query_term"=>$go));
            echo "&nbsp;";
            echo  $this->element("linkout_func", array("linkout_type"=>"quickgo", "query_term"=>$go));
            echo "</dd>\n";
			}
			else if($type=="interpro"){
                echo "<dt>Protein domain</dt>";
                echo "<dd>";
                echo $interpro;
                echo "&nbsp; &nbsp;";
                echo  $this->element("linkout_func", array("linkout_type"=>"interpro", "query_term"=>$interpro));
                echo "</dd>\n";
			}

            else if($type=="ko"){
                echo "<dt>KO term</dt>";
                echo "<dd>";
                echo $ko;
                echo "&nbsp; &nbsp;";
                echo  $this->element("linkout_func", array("linkout_type"=>"kegg_ko", "query_term"=>$ko));
                echo "</dd>\n";
            }
			?>
			<dt>Description</dt>
			<dd><?php echo $description;?></dd>
			<dt>#transcripts</dt>
			<dd><?php echo $num_transcripts;?></dd>
		</dl>
    </section>
    <section class="page-section-sm">
	<h3>Associated gene families</h3>
		<?php if(isset($error)):?>
		<span class="error"><?php echo $error;?></span>
		
		<?php else: ?>

		<?php echo $this->Html->script("sorttable");?>
		<?php echo $this->element("sorttable");?>
		<table class='table table-striped table-condensed table-bordered table-hover sortable'>
            <thead>
			<tr>
				<th>Gene family</th>
				<th>#transcripts</th>
                <?php if(in_array("go", $exp_info['function_types'])): ?>
				<th>GO terms</th>
                <?php endif; ?>
                <?php if(in_array("interpro", $exp_info['function_types'])): ?>
				<th>Protein domains</th>
                <?php endif; ?>
                <?php if(in_array("ko", $exp_info['function_types'])): ?>
                <th>KO terms</th>
                <?php endif; ?>
			</tr>
            </thead>
            <tbody>
			<?php
			$j=0;
			$max_items = 3;
			foreach($gene_families as $gf_id=>$transcript_count){							
				$class=null; if($j++%2==0){$class=" class='altrow' ";}
				echo "<tr $class>";
				echo "<td>".$this->Html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id))."</td>";
				echo "<td>".$transcript_count."</td>";

            if(in_array("go", $exp_info['function_types'])) {
                echo "<td>";
                echo "<ul class='table-items'>";
                for ($i = 0; $i < count($extra_annot_go[$gf_id]) && $i < $max_items; $i++) {
                    $go = $extra_annot_go[$gf_id][$i];
                    $desc = $go_descriptions[$go];
                    $go_cat = $go_categories[$go];
                    echo "<li>";
                    echo $this->Html->link($desc, array("controller" => "functional_annotation", "action" => "go", $exp_id, str_replace(":", "-", $go)));
                    echo "&nbsp;";
                    echo $this->element("go_category_badge", array("go_category" => $go_cat, "small_badge" => true));
                    echo "</li>";
                }
                echo "</ul>";
                echo "</td>";
            }

            if(in_array("interpro", $exp_info['function_types'])) {
                echo "<td>";
                echo "<ul class='table-items'>";
                for ($i = 0; $i < count($extra_annot_ipr[$gf_id]) && $i < $max_items; $i++) {
                    $ipr = $extra_annot_ipr[$gf_id][$i];
                    $desc = $ipr_descriptions[$ipr];
                    echo "<li>";
                    echo $this->Html->link($desc, array("controller" => "functional_annotation", "action" => "interpro", $exp_id, $ipr));
                    echo "</li>";
                }
                echo "</ul>";
                echo "</td>";
            }
            if(in_array("ko", $exp_info['function_types'])) {
                echo "<td>";
                echo "<ul class='table-items'>";
                    for($i=0;$i<count($extra_annot_ko[$gf_id]) && $i<$max_items;$i++){
                        $ko	= $extra_annot_ko[$gf_id][$i];
                        $desc	= $ko_descriptions[$ko];
                        echo "<li>";
                        echo $this->Html->link($desc,array("controller"=>"functional_annotation","action"=>"ko",$exp_id,$ko));
                        echo "</li>";
                    }
                    echo "</ul>";
                    echo "</td>";
            }
            echo "</tr>\n";
			}
			?>
            </tbody>
		</table>
		<?php endif;?>
    </section>