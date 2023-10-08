<?php
echo $this->Html->script(['jspdf/jspdf.umd.min.js', 'jspdf/jspdf.plugin.autotable.min.js']);

function perc($num, $total, $nr, $textmode = true) {
    $perc = round((100 * $num) / $total, $nr);
    $res = '';
    if ($textmode) {
        $res = ' (' . $perc . '%)';
    } else {
        $res = $perc;
    }
    return $res;
}

function draw_progress_bar($perc) {
    return "<div class=\"progress stats-progress\"><div class=\"progress-bar\" role=\"progressbar\" style=\"width: " .
        $perc .
        "%;\" aria-valuenow=\"" .
        $perc .
        "\" aria-valuemin=\"0\" aria-valuemax=\"100\"></div></div>";
}

function create_stats_row($metrics_name, $metrics_value, $metrics_perc, $row_id = null, $ajax = false) {
    if (!$row_id) {
        echo "<div class=\"row\">\n";
    } else {
        echo "<div class=\"row\" id='" . $row_id . "'>\n";
    }
    echo "<div class=\"col-md-4 col-md-offset-1 col-xs-8 stats-metric\">" . $metrics_name . "</div>\n";
    if ($metrics_perc) {
        echo "<div class=\"col-md-2 col-xs-4 stats-value\">" .
            $metrics_value .
            ' (' .
            $metrics_perc .
            '%)' .
            "</div>\n";
        echo "<div class=\"col-md-4 hidden-sm hidden-xs\">\n";
        echo draw_progress_bar($metrics_perc);
        echo "</div>\n";
    } else {
        echo "<div class=\"col-md-2 col-xs-4 stats-value\">" . $metrics_value . "</div>\n";
    }
    echo "</div>\n";
}

$loading_span_elmt =
    "<span class=\"text-muted\">" .
    $this->Html->image('small-ajax-loader.gif', ['style' => 'max-height: 14px;']) .
    ' &nbsp; loading...</span>';
?>

<div class="page-header">
    <div class='btn-toolbar pull-right'>
        <br> <!-- Fix to position the export button -->
        <button type="submit" class="btn btn-sm btn-default" id="btn-pdf-export" disabled>
            <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading']); ?>
            <span class="glyphicon glyphicon-download-alt hidden"></span>
            Export to PDF
        </button>
    </div>
    <h1 class="text-primary">General statistics</h1>
</div>


<div class="panel panel-default">
    <div class="panel-heading">
        Transcript information
    </div>
    <div class="panel-body">
        <?php create_stats_row('#Transcripts', $num_transcripts, null); ?>
        <?php create_stats_row('Average sequence length', $loading_span_elmt, null, 'avg_trs_length'); ?>
        <?php create_stats_row('#Transcripts with ORF', $num_orfs, null); ?>
        <?php create_stats_row('Average ORF length', $loading_span_elmt, null, 'avg_orf_length'); ?>
        <?php create_stats_row(
            '#ORFs with a start codon',
            $num_start_codons,
            perc($num_start_codons, $num_transcripts, 1, false)
        ); ?>
        <?php create_stats_row(
            '#ORFs with a stop codon',
            $num_stop_codons,
            perc($num_stop_codons, $num_transcripts, 1, false)
        ); ?>
        <?php create_stats_row(
            '#Transcripts with putative frameshift',
            $num_putative_fs,
            perc($num_putative_fs, $num_transcripts, 1, false)
        ); ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Meta annotation information
    </div>
    <div class="panel-body">
        <?php create_stats_row(
            '#Full-length',
            $meta_annot_fulllength,
            perc($meta_annot_fulllength, $num_transcripts, 1, false)
        ); ?>
        <?php create_stats_row(
            '#Quasi full-length',
            $meta_annot_quasi,
            perc($meta_annot_quasi, $num_transcripts, 1, false)
        ); ?>
        <?php create_stats_row(
            '#Partial',
            $meta_annot_partial,
            perc($meta_annot_partial, $num_transcripts, 1, false)
        ); ?>
        <?php create_stats_row(
            '#No information',
            $meta_annot_noinfo,
            perc($meta_annot_noinfo, $num_transcripts, 1, false)
        ); ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Taxonomic classification information (Kaiju)
    </div>
    <div class="panel-body">
        <?php if ($exp_info['perform_tax_binning'] == 1): ?>
            <?php create_stats_row(
                '#Classified',
                $num_classified_trs,
                perc($num_classified_trs, $num_transcripts, 2, false)
            ); ?>
            <?php create_stats_row(
                '#Unclassified',
                $num_unclassified_trs,
                perc($num_unclassified_trs, $num_transcripts, 2, false)
            ); ?>
            <h5>Domain composition</h5>
            <?php foreach ($top_tax_domain as $top_tax) {
                if ($top_tax[0] != 'Unclassified') {
                    create_stats_row(
                        '#' . $top_tax[0],
                        (int) $top_tax[1],
                        perc((int) $top_tax[1], $num_transcripts, 2, false)
                    );
                }
            } ?>
        <?php else: ?>
            <p class="lead text-muted">No taxonomic classification was performed for this experiment. </p>
        <?php endif; ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Similarity search information (DIAMOND)
    </div>
    <div class="panel-body">
        <p class="text-justify">Best similarity search hit for each transcript. By default, only the top 20 species are
            shown. If there are more, click the <code>Show all</code> link to display all species.</p>

        <?php
        $split = explode(';', $exp_info['hit_results']);
        $tmp = [];
        $sum = 0;
        $max_species = 20; // Max. number of species to show by default.. Should this come from the controller instead?
        $extra_div = false;
        foreach ($split as $s) {
            $k = explode('=', $s);
            $tmp[$k[0]] = $k[1];
            $sum += $k[1];
        }
        arsort($tmp);
        $species_keys = array_keys($tmp);
        $last_species = end($species_keys);
        foreach ($tmp as $k => $v) {
            if (array_search($k, $species_keys) == $max_species) {
                $extra_div = true;
                echo "<a id=\"toggle-extra-hits\" onclick=\"toggleExtraHits()\">";
                echo "<span id=\"toggle-extra-hits-icon\" class=\"glyphicon small-icon glyphicon-menu-right\"></span> ";
                echo 'Show all...';
                echo "</a>\n";
                echo "<div id='extra-hits' class='hidden'>\n";
            }
            create_stats_row($all_species[$k], $v, perc($v, $sum, 2, false));
            if ($extra_div && $k == $last_species) {
                echo '</div>';
            }
        }
        echo '<hr>';
        create_stats_row('Total hits', $sum, null);
        ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Gene family information
    </div>
    <div class="panel-body">
        <?php create_stats_row('#Gene families', $num_gf, null); ?>
        <?php create_stats_row(
            '#Transcripts in GF',
            $num_transcript_gf,
            perc($num_transcript_gf, $num_transcripts, 1, false)
        ); ?>
        <?php create_stats_row(
            'Largest GF',
            $this->Html->link($biggest_gf['gf_id'], [
                'controller' => 'gene_family',
                'action' => 'gene_family',
                $exp_id,
                $biggest_gf['gf_id']
            ]) .
                ' (' .
                $biggest_gf['num_transcripts'] .
                ' transcripts)',
            null
        ); ?>
        <?php create_stats_row('#Single copy', $single_copy, null); ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        RNA family information
    </div>
    <div class="panel-body">
        <?php create_stats_row('#RNA families', $num_rf, null); ?>
        <?php create_stats_row(
            '#Transcripts in RF',
            $num_transcript_rf,
            perc($num_transcript_rf, $num_transcripts, 1, false)
        ); ?>
        <?php if ($biggest_rf['rf_id'] == 'N/A') {
            create_stats_row(
                'Largest RF',
                $biggest_rf['rf_id'] . ' (' . $biggest_rf['num_transcripts'] . ' transcripts)',
                null
            );
        } else {
            create_stats_row(
                'Largest RF',
                $this->Html->link($biggest_rf['rf_id'], [
                    'controller' => 'rna_family',
                    'action' => 'rna_family',
                    $exp_id,
                    $biggest_rf['rf_id']
                ]) .
                    ' (' .
                    $biggest_rf['num_transcripts'] .
                    ' transcripts)',
                null
            );
        } ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Functional annotation information
    </div>
    <div class="panel-body">
        <?php if (in_array('go', $exp_info['function_types'])): ?>
            <h5>Gene Ontology</h5>
            <?php create_stats_row('#GO terms', $num_go, null); ?>
            <?php create_stats_row(
                '#Transcripts with GO',
                $num_transcript_go,
                perc($num_transcript_go, $num_transcripts, 1, false)
            ); ?>
        <?php endif; ?>
        <?php if (in_array('interpro', $exp_info['function_types'])): ?>
            <h5>InterPro</h5>
            <?php create_stats_row('#InterPro domains', $num_interpro, null); ?>
            <?php create_stats_row(
                '#Transcripts with Protein Domain',
                $num_transcript_interpro,
                perc($num_transcript_interpro, $num_transcripts, 1, false)
            ); ?>
        <?php endif; ?>
        <?php if (in_array('ko', $exp_info['function_types'])): ?>
            <h5>KEGG Orthology</h5>
            <?php create_stats_row('#KO terms', $num_ko, null); ?>
            <?php create_stats_row(
                '#Transcripts with KO',
                $num_transcript_ko,
                perc($num_transcript_ko, $num_transcripts, 1, false)
            ); ?>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript" defer="defer">
    const experiment_id = <?php echo $exp_id; ?>;
    const btn_export = document.querySelector('#btn-pdf-export');
    const pdf_sections = <?php echo json_encode($pdf_sections); ?>;
    let avg_transcript_length = null;
    let avg_orf_length = null;

    // Toggle extra similarity search hits. Called on click of 'toggle-extra-hits' link.
    function toggleExtraHits() {
        var extraHitsDiv = "extra-hits";
        var extraHitsIcon = "toggle-extra-hits-icon";
        var ehIconElmt = document.getElementById(extraHitsIcon);
        document.getElementById(extraHitsDiv).classList.toggle("hidden");
        if (ehIconElmt.classList.contains("glyphicon-menu-right")) {
            ehIconElmt.classList.replace("glyphicon-menu-right", "glyphicon-menu-down");
        } else {
            ehIconElmt.classList.replace("glyphicon-menu-down", "glyphicon-menu-right");
        }
    }

    // Retrieve sequence stats data (avg. transcript/orf lengths)
    // TODO: If more than 2 values are retrieved that way, rewrite proper function
    function get_avg_transcript_length(exp_id) {
        let row_id = "#avg_trs_length";
        let stat_val_elmt = document.querySelector(row_id).querySelector('.stats-value');
        let ajax_url = <?php echo "\"" .
            $this->Html->url(['controller' => 'tools', 'action' => 'avg_transcript_length']) .
            "\""; ?>+"/" + exp_id + "/";
        $.ajax({
            type: "GET",
            url: ajax_url,
            contentType: "application/json;charset=UTF-8",
            success: function (data) {
                avg_transcript_length = data;
                $(stat_val_elmt).html(data);
                update_pdf_value("###avg_trs_length###", avg_transcript_length, " bp");
                check_export();
            },
            error: function () {
                console.log("Unable to retrieve average transcript length for experiment \'" + exp_id + "\'. ");
            },
            complete: function () {
                // Debug
                // console.log(experiment_id);
                // console.log(ajax_url);
            }
        });
    }

    function get_avg_orf_length(exp_id) {
        let row_id = "#avg_orf_length";
        let stat_val_elmt = document.querySelector(row_id).querySelector('.stats-value');
        let ajax_url = <?php echo "\"" .
            $this->Html->url(['controller' => 'tools', 'action' => 'avg_orf_length']) .
            "\""; ?>+"/" + exp_id + "/";
        $.ajax({
            type: "GET",
            url: ajax_url,
            contentType: "application/json;charset=UTF-8",
            success: function (data) {
                avg_orf_length = data;
                $(stat_val_elmt).html(data);
                update_pdf_value("###avg_orf_length###", avg_orf_length, " bp");
                check_export();
            },
            error: function () {
                console.log("Unable to retrieve average ORF length for experiment \'" + exp_id + "\'. ");
            },
            complete: function () {
                // Debug
                // console.log(experiment_id);
                // console.log(ajax_url);
            }
        });
    }

    function check_export() {
        const isLoading = !(avg_transcript_length && avg_orf_length);
        btn_export.disabled = isLoading;
        btn_export.querySelector('img').classList.toggle('hidden', !isLoading);
        btn_export.querySelector('.glyphicon').classList.toggle('hidden', isLoading);
    }

    function update_pdf_value(placeholder, value, suffix = null) {
        pdf_sections.forEach((section) => {
            section.data.forEach((record, idx) => {
                if (record[1] === placeholder) {
                    section.data[idx][1] = value.toString() + suffix;
                }
            })
        });
    }

    function create_statistics_pdf(exp_id) {
        const doc = new window.jspdf.jsPDF();
        doc.setProperties({
            title: `TRAPID general statistics (${exp_id})`,
            author: 'CNB group (VIB-UGent Center for Plant Systems Biology)',
            creator: 'TRAPID 2.0'
        });
        doc.setFontSize(24);
        doc.setTextColor(33, 150, 243);
        doc.text("General statistics", 14, 20);
        doc.setFontSize(16);
        doc.setTextColor(33, 33, 33);

        pdf_sections.forEach((section) => {
            if (section.data.length > 0) {
                const finalY = doc.lastAutoTable.finalY || 20;
                doc.text(section.title, 14, finalY + 16)
                doc.autoTable({
                    startY: finalY + 20,
                    head: [],
                    body: section.data,
                    columnStyles: {0: {cellWidth: 80, fontStyle: 'bold'}},
                    styles: {cellPadding: 0.4},
                    theme: 'plain'
                });
            }
        });

        doc.save(`trapid_statistics_${exp_id}.pdf`);
    }

    check_export();
    get_avg_transcript_length(experiment_id);
    get_avg_orf_length(experiment_id);
    btn_export.addEventListener('click', function () {
        try {
            create_statistics_pdf(experiment_id);
        } catch (error) {
            alert("An error occurred during PDF file generation. Please contact us to report the issue.");
            console.error(error);
        }
    }, false);
</script>
