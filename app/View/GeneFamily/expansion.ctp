<div class="page-header">
    <h1 class="text-primary">Gene family expansion/depletion</h1>
</div>
<?php
$alert_content = "<strong>Note:</strong> detected expansions can be due to splice variants, allelic variation or fragmented transcripts. Detected depletions can be due to fragmented transcripts and/or insufficient transcriptome coverage.";
echo $this->element('bs_alert', ['alert_class' => 'alert-info', 'alert_content' => $alert_content]);
if (isset($error)) {
    echo "<span class='text-danger'><strong>Error:</strong> " . $error . "</span><br/>";
}
?>
<section class="page-section-sm">
    <h3>Options</h3>
    <?php
    echo $this->Form->create(false, array("action" => "expansion/" . $exp_id, "type" => "post"));
    ?>
    <dl class="dl-horizontal">
        <dt>Reference species</dt>
        <dd>
            <select name="reference_species" class="form-control" style="width:200px;">
                <?php
                foreach ($available_species as $k => $v) {
                    $s = null;
                    if (isset($selected_species) && $selected_species == $k) {
                        $s = " selected='selected' ";
                    }
                    echo "<option value='" . $k . "' $s>" . $v . "</option>\n";
                }
                ?>
            </select>
        </dd>
        <dt>Type</dt>
        <dd>
            <select name="type" class="form-control" style="width:200px;">
                <?php
                foreach ($available_types as $k => $v) {
                    $s = null;
                    if (isset($selected_type) && $selected_type == $k) {
                        $s = " selected='selected' ";
                    }
                    echo "<option value='" . $k . "' $s>" . $v . "</option>\n";
                }
                ?>
            </select>
        </dd>
        <dt>Minimal ratio</dt>
        <dd>
            <select name="ratio" class="form-control" style="width:200px;">
                <?php
                foreach ($available_ratios as $k) {
                    $s = null;
                    if (isset($selected_ratio) && $selected_ratio == $k) {
                        $s = " selected='selected' ";
                    }
                    echo "<option value='" . $k . "' $s>" . $k . "</option>\n";
                }
                ?>
            </select>
        </dd>
        <dt>Extra</dt>
        <dd>
            <label>
                <input type="checkbox" name="zero_reference" <?php if (isset($zero_reference)) {
                                                                    echo " checked='checked' ";
                                                                } ?> /> <strong>Include zero values in reference species</strong>
            </label>
        </dd>
    </dl>
    <input type='submit' class="btn btn-primary" value='Find gene families' />
    </form>
</section>

<?php
if (isset($result)) {
    echo "<section class='page-section-sm'>";
    echo $this->Html->script("sorttable");
    echo "<h3>Result</h3>\n";
    echo $this->element('sorttable');
    echo "<table class='table table-striped table-bordered table-hover table-condensed sortable'>\n";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Gene family</th>";
    echo "<th>Transcript count</th>";
    echo "<th>" . $available_species[$selected_species] . "</th>";
    echo "</tr>\n";
    echo "</thead>";
    echo "<tbody>";
    foreach ($transcripts_counts as $gf_id => $transcript_count) {
        $reference_count = 0;
        if (array_key_exists($gf_id, $reference_counts)) {
            $reference_count = $reference_counts[$gf_id];
        }
        $display = false;
        if ($reference_count != 0 || isset($zero_reference)) {
            if ($selected_type == "expansion") {
                if ($transcript_count >= $selected_ratio * $reference_count) {
                    $display = true;
                }
            }
            if ($selected_type == "depletion") {
                if ($reference_count >= $selected_ratio * $transcript_count) {
                    $display = true;
                }
            }
        }
        if ($display) {
            echo "<tr>";
            echo "<td>" . $this->Html->link($gf_id, array("controller" => "gene_family", "action" => "gene_family", $exp_id, urlencode($gf_id))) . "</td>";
            echo "<td>" . $transcript_count . "</td>";
            echo "<td>" . $reference_count . "</td>";
            echo "</tr>\n";
        }
    }
    echo "</tbody>";
    echo "</table>\n";
    echo "</div>\n";
}
?>
