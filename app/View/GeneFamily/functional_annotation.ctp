<div>
    <div class="page-header">
<h1 class="text-primary">Gene family</h1>
    </div>
<div class="subdiv">
	<?php // echo $this->element("trapid_experiment"); ?>
	
	<h3>Gene family information</h3>
	<div class="subdiv page-section">
		<dl class="standard dl-horizontal">
			<dt>Gene family</dt>
			<dd><?php echo $this->Html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id));?></dd>
			<dt>#transcripts</dt>
			<dd><?php echo $gf_info['GeneFamilies']['num_transcripts'];?></dd>
		</dl>	
	</div>			

	<h3>Associated functional annotation</h3>
	<div class="subdiv">
		
        <section class="page-section-sm">
		<h4>Gene Ontology terms</h4>
		<?php if(count($go_descriptions)==0): ?>
		<span class='error'>No GO terms are associated with this gene family</span>
		<?php else: ?>
            <?php
            // Create three GO arrays (one for each GO ontology)
            $go_terms_bp = array();  // Biological process
            $go_terms_mf = array();  // Molecular function
            $go_terms_cc = array();  // Cellular component
            // Loop over found GO terms to map them to their ontologies
            foreach ($go_descriptions as $go_id=>$desc) {
                $go_data = array("go_id"=>$go_id, "desc"=>$desc['desc']);
                switch ($desc['type']) {
                    case "BP":
                        $go_terms_bp[] = $go_data;
                        break;
                    case "MF":
                        $go_terms_mf[] = $go_data;
                        break;
                    case "CC":
                        $go_terms_cc[] = $go_data;
                        break;
                    default:
                        break;
                }
            }
            $all_gos = array(
                "BP"=>array("title"=>"Biological process", "go_terms"=>$go_terms_bp),
                "MF"=>array("title"=>"Molecular function", "go_terms"=>$go_terms_mf),
                "CC"=>array("title"=>"Cellular component", "go_terms"=>$go_terms_cc)
            );

            // Create a table for each GO category
            foreach($all_gos as $go_cat_id => $go_category) {
                if(!empty($go_category["go_terms"])) {
                    echo "<h5>" . $go_category["title"] . " ";
                    echo $this->element("go_category_badge", array("go_category"=>$go_cat_id, "small_badge"=>false));
                    echo "</h5>";
                    echo "<table class='table table-striped table-bordered table-hover'>\n";
                    echo "<thead><tr><th style='width:20%'>GO term</th><th>Description</th><th style='width:15%'>Assoc. transcripts</th></tr></thead>\n";
                    foreach($go_category["go_terms"] as $go_term) {
                        $web_go = str_replace(":", "-", $go_term["go_id"]);
                        echo "<tr>";
                        echo "<td>" . $this->Html->link($go_term["go_id"], array("controller" => "functional_annotation", "action" => "go", $exp_id, $web_go)) . "</td>";
                        echo "<td>" . $go_term['desc'] . "</td>";
                        echo "<td>".$this->Html->link("Transcripts", array("controller"=>"trapid","action"=>"transcript_selection", $exp_id, "gf_id", $gf_id, "go", $web_go))."</td>";
                        echo "</tr>\n";
                    }
                }
                echo "</tbody>\n";
                echo "</table>\n\n";
            }
            ?>
		<?php endif;?>
            </section>

        <section class="page-section-sm">
		<h4>Protein domains</h4>
		<table class="table table-bordered table-hover table-striped">
            <thead>
			<tr>
				<th style='width:20%'>Protein domain</th>
				<th>Description</th>
				<th style='width:15%'>Assoc. transcripts</th>
			</tr>
            </thead>
            <tbody>
			<?php
			$i	= 0;
			foreach($interpro_descriptions as $interpro=>$desc){
				$class = null;
				if($i++%2==0){$class=" class='altrow' ";}				
				echo "<tr $class>";				
				echo "<td>".$this->Html->link($interpro,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$interpro))."</td>";				
				echo "<td>".$desc['desc']."</td>";
				echo "<td>".$this->Html->link("Transcripts",array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"gf_id",$gf_id,"interpro",$interpro))."</td>";
				echo "</tr>\n";
			}
			?>
            </tbody>
		</table>
        </section>
	</div>
</div>
</div>
