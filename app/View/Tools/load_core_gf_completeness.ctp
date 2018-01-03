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
                    <li><strong>Core GF completeness score</strong>: <?php echo $completeness_score;?></li>
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
                echo "<table class=\"table table-compact table-striped table-hover table-bordered\">
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
                    echo "<td>" . $represented_gf["gf_id"] . "</td>";
                    echo "<td>" . $represented_gf["n_genes"] . "</td>";
                    echo "<td>" . $represented_gf["n_species"] . "</td>";
                    echo "<td>" . $represented_gf["gf_weight"] . "</td>";
                    echo "<td>" . $represented_gf["queries"] . "</td>";
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
                echo "<table class=\"table table-compact table-striped table-hover table-bordered\">
                <thead>
                    <th>GF identifier</th>
                    <th># genes</th>
                    <th># species</th>
                    <th>GF weight</th>
                </thead>
                <tbody>";
                foreach ($missing_gfs_array as $missing_gf) {
                    echo "<tr>";
                    echo "<td>" . $missing_gf["gf_id"] . "</td>";
                    echo "<td>" . $missing_gf["n_genes"] . "</td>";
                    echo "<td>" . $missing_gf["n_species"] . "</td>";
                    echo "<td>" . $missing_gf["gf_weight"] . "</td>";
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