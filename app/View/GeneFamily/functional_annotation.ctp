<?php
    // CSS class string to apply to all tables
    $table_class = "table table-bordered table-hover table-striped";
?>
<div class="page-header">
    <h1 class="text-primary">Associated functional annotation</h1>
</div>

<section class="page-section-sm">
    <h3>Gene family information</h3>
    <dl class="standard dl-horizontal">
        <dt>Gene Family</dt>
        <dd><?php echo $this->Html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id));?></dd>
        <dt>Transcript count</dt>
        <dd><?php echo $gf_info['GeneFamilies']['num_transcripts'];?></dd>
    </dl>
</section>

<div class="row">
    <div class="col-md-9 col-lg-10">
        <section class="page-section">
    	<h3>Functional annotation</h3>

        <?php if(in_array("go", $exp_info['function_types'])): ?>
        <section class="page-section-sm" id="funct-go">
	    	<h4>Gene Ontology terms</h4>
    		<?php if(count($go_descriptions) == 0): ?>
	    	<p class='lead text-muted'>No GO terms are associated with this gene family</p>
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
                echo "<h5 id='funct-go-" . strtolower($go_cat_id) . "'>";
                echo $go_category["title"] . " ";
                echo $this->element("go_category_badge", array("go_category"=>$go_cat_id, "small_badge"=>false));
                echo "</h5>";
                if(empty($go_category["go_terms"])) {
                    echo "<p class='text-muted'>No GO terms from this aspect are associated with this gene family</p>";
                }
                else {
                    echo "<table class='". $table_class . "'>\n";
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
        <?php endif; ?>

        <?php if(in_array("interpro", $exp_info['function_types'])): ?>
        <section class="page-section-sm" id="funct-ipr">
		<h4>Protein domains</h4>
        <?php if(count($interpro_descriptions) == 0): ?>
            <p class='lead text-muted'>No protein domains are associated with this gene family</p>
        <?php else: ?>
		<table class="<?php echo $table_class; ?>">
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
				echo "<tr>";
				echo "<td>".$this->Html->link($interpro,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$interpro))."</td>";				
				echo "<td>".$desc['desc']."</td>";
				echo "<td>".$this->Html->link("Transcripts",array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"gf_id",$gf_id,"interpro",$interpro))."</td>";
				echo "</tr>\n";
			}
			?>
            </tbody>
		</table>
        <?php endif; ?>
        </section>
        <?php endif; ?>


        <?php if(in_array("ko", $exp_info['function_types'])): ?>
        <section class="page-section-sm" id="funct-ko">
		<h4>KEGG Orthology</h4>
        <?php if(count($ko_descriptions) == 0): ?>
            <p class='lead text-muted'>No KO terms are associated with this gene family</p>
        <?php else: ?>
		<table class="<?php echo $table_class; ?>">
            <thead>
			<tr>
				<th style='width:20%'>KO term</th>
				<th>Description</th>
				<th style='width:15%'>Assoc. transcripts</th>
			</tr>
            </thead>
            <tbody>
			<?php
			$i	= 0;
			foreach($ko_descriptions as $ko=>$desc){
				echo "<tr>";
				echo "<td>".$this->Html->link($ko,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$ko))."</td>";
				echo "<td>".$desc['desc']."</td>";
				echo "<td>".$this->Html->link("Transcripts",array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"gf_id",$gf_id,"ko",$ko))."</td>";
				echo "</tr>\n";
			}
			?>
            </tbody>
		</table>
        <?php endif; ?>
        </section>
        <?php endif; ?>
	</section>
    </div><!-- End functional data col -->

    <div class="col-md-3 col-lg-2 hidden-sm hidden-xs">
        <nav class="scrollspy" style="margin-top: 9em;">
            <ul class="nav transcript-nav" id="sidebar-nav" data-spy="affix">
                <h5 class="doc-sidebar-header">Contents</h5>
                <?php if(in_array("go", $exp_info['function_types'])): ?>
                <li><a href="#funct-go">Gene Ontology terms</a>
                    <ul class="nav">
                        <li><a href="#funct-go-bp">Biological process</a></li>
                        <li><a href="#funct-go-mf">Molecular function</a></li>
                        <li><a href="#funct-go-cc">Cellular component</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if(in_array("interpro", $exp_info['function_types'])): ?>
                <li><a href="#funct-ipr">Protein domains</a></li>
                <?php endif; ?>
                <?php if(in_array("ko", $exp_info['function_types'])): ?>
                <li><a href="#funct-ko">KEGG Orthology</a></li>
                <?php endif; ?>
                <li class="sidebar-nav-to-top"><a href="#top">Back to top</a></li>
            </ul>
        </nav>
    </div><!-- End navigation col -->
</div><!-- End row -->

<script type="text/javascript">
    // Affix navigation (bootstrap)
    $('body').attr('data-spy', 'scroll');
    $('body').attr('data-target', '.scrollspy');
    $("#sidebar-nav").affix({
        offset: {
            top: $("#sidebar-nav").offset().top - 50
        }
    });
    // Scroll to anchors smoothly
    $('a[href^="#"]').click(function () {
        var the_id = $(this).attr("href");
        $('html, body').animate({
            scrollTop: $(the_id).offset().top
        }, 250, 'swing');
        return false;
    });
</script>