<?php
/**
 * Created by PhpStorm.
 * User: frbuc
 * Date: 12/20/17
 * Time: 8:20 PM
 */
?>
<hr>
<h4>Core GF completeness results <small><em><?php echo $tax_name; ?></em></small></h4>
<div id="results-content">
    <ul class="nav nav-tabs nav-justified" id="results-tabs" data-tabs="tabs">
        <li class="active"><a href="#core-gf-summary" data-toggle="tab">Summary</a></li>
        <li><a href="#represented-gfs" data-toggle="tab">Represented GFs table</a></li>
        <li><a href="#missing-gfs" data-toggle="tab">Missing GFs table</a></li>
    </ul>

    <div id="results-tab-content" class="tab-content"> <!-- style="border: 1px lightgray solid;"> -->
        <div id="core-gf-summary" class="tab-pane active"><br>
            <div class="row">
                <div class="col-md-6">
                    <?php echo $this->element("charts/bar_core_gfs", array("n_missing"=>$n_missing, "n_represented"=>$n_represented, "n_total"=>$n_total, "chart_div_id"=>"bar_core_gfs", "chart_title"=> $tax_name. " core gene families (GFs)", "chart_subtitle"=>"Represented and missing core GFs (".$label.")"));?>
                </div>
                <div class="col-md-6">
                    <br>
                <ul>
                    <li><strong>Core GF completeness score</strong>: <?php echo number_format((float)$completeness_score, 3, '.', ',');?></li>
                    <li><strong>Represented core GFs</strong>: <?php echo $n_represented . " / " . $n_total;?></li>
                    <li><strong>Missing core GFs</strong>: <?php echo $n_missing . " / " . $n_total;?></li>
                    <li><strong>Transcript subset</strong>: <code><?php echo $label;?></code></li>
                    <li><strong>Parameters</strong>: conservation threshold <code><?php echo $species_perc; ?></code> - top hits <code><?php echo $top_hits; ?></code></li>
                </ul>
                </div>
            </div>
            <br>
        </div>
        <div id="represented-gfs" class="tab-pane"><br>

            <?php
            if($n_represented > 0) {
                echo "<table class=\"table table-compact table-striped table-hover table-bordered table-responsive gf-table\">
                <thead>
                    <th>GF identifier</th>
                    <th># genes</th>
                    <th># species</th>
                    <th>GF weight</th>
                    <th>Found with...</th>
                </thead>
                <tbody>";
                foreach ($represented_gfs_array as $represented_gf) {
                    echo "<tr>";
                    if($linkout_prefix) {
                        echo "<td><a>" .$represented_gf["gf_id"] . "</a></td>";
                    }
                    else {
                        echo "<td>" . $represented_gf["gf_id"] . "</td>";
                    }
                    echo "<td>" . $represented_gf["n_genes"] . "</td>";
                    echo "<td>" . $represented_gf["n_species"] . "</td>";
                    echo "<td>" . number_format((float)$represented_gf["gf_weight"], 3, '.', '') . "</td>";
                    echo "<td>";
                    $queries = explode(',', $represented_gf["queries"]);
                    if(sizeof($queries) <= 5) {
                        foreach($queries as $query) {
                            echo $query . " ";
                        }
                    }
                    else {
                        echo sizeof($queries) . " transcripts";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</tbody>
            </table>";
            }
            else {
                echo "No represented GF to show. ";
            }
            ?>
        </div>

        <div id="missing-gfs" class="tab-pane"><br>
            <?php
            if($n_missing > 0) {
                echo "<table class=\"table table-compact table-striped table-hover table-bordered gf-table\">
                <thead>
                    <th>GF identifier</th>
                    <th># genes</th>
                    <th># species</th>
                    <th>GF weight</th>
                </thead>
                <tbody>";
                foreach ($missing_gfs_array as $missing_gf) {
                    echo "<tr>";
                    echo "<tr>";
                    if($linkout_prefix) {
                        echo "<td><a>" .$missing_gf["gf_id"] . "</a></td>";
                    }
                    else {
                        echo "<td>" . $missing_gf["gf_id"] . "</td>";
                    }
                    echo "<td>" . $missing_gf["n_genes"] . "</td>";
                    echo "<td>" . $missing_gf["n_species"] . "</td>";
                    echo "<td>" . number_format((float)$missing_gf["gf_weight"], 3, '.', '') . "</td>";
                    echo "</tr>";
                }
                echo "</tbody>
            </table>";
            }
            else {
                echo "No missing GF to show. ";
            }
            ?>
        </div>
    </div>
</div>
<?php if($linkout_prefix): ?>
    <script>
        // Redirect to external GF page
        function redirectToPage(gf_id) {
            <?php
                if($db_type == "plaza") {
                    $linkout_url = $linkout_prefix . "gene_families/view/";
                }
                elseif ($db_type == "eggnog"){
                    $linkout_url = $linkout_prefix . "#/app/results?target_nogs=";
                }
            ?>
            var linkout_url = "<?php echo $linkout_url; ?>" + gf_id;
            window.open(linkout_url, '_blank');
        }
        // On click of a gf in a `.gf-table`, trigger the redirect function
        $(function() {
            $(".gf-table tr td:first-child").click(function(e){
                var gf_txt = $(e.target).text();
                redirectToPage(gf_txt);
            });
        });

    </script>
<?php endif; ?>