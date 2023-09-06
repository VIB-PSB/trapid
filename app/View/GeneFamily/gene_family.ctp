<?php
echo $this->Html->script('selectize.min.js');
echo $this->Html->css('selectize.paper.css');
?>
<div class="page-header">
    <h1 class="text-primary"><?php echo $gf_info['gf_id']; ?> <small>gene family</small></h1>
</div>
<section class="page-section-xs">
    <h3>Overview</h3>
    <dl class="standard dl-horizontal">
        <dt>Gene Family</dt>
        <dd><?php echo $gf_info['gf_id']; ?></dd>
        <dt>Transcript count</dt>
        <dd><?php echo $gf_info['num_transcripts']; ?></dd>
        <?php if ($exp_info['genefamily_type'] == 'HOM') {
            echo "<dt>Original Gene Family</dt>\n";
            echo '<dd>';
            if ($exp_info['allow_linkout']) {
                $linkout_base = isset($eggnog_og_linkout) ? '#/app/results?target_nogs=' : 'gene_families/view/';
                echo $this->Html->link(
                    $gf_info['plaza_gf_id'],
                    $exp_info['datasource_URL'] . $linkout_base . $gf_info['plaza_gf_id'],
                    ['target' => '_blank', 'class' => 'linkout']
                );
            } else {
                echo $gf_info['plaza_gf_id'];
            }
            echo "</dd>\n";
            if (isset($gf_tax_scope)) {
                echo '<dt>NOG taxonomic level</dt>';
                echo '<dd>' .
                    $gf_tax_scope['name'] .
                    " <span class='label label-default'>" .
                    $gf_tax_scope['scope'] .
                    '</span></dd>';
            }
            if (isset($gf_func_data)) {
                echo '<dt>NOG functional data</dt>';
                echo '<dd>' .
                    $gf_func_data['func_cat_label'] .
                    " <span class=\"label label-default\">" .
                    $gf_func_data['func_cat_id'] .
                    '</span> ; ' .
                    $gf_func_data['description'] .
                    '</dd>';
            }
        } else {
            echo "<dt>Ortho group content</dt>\n";
            echo "<dd>\n";
            echo "<div id='ocg1'>";
            echo "<a href=\"javascript:$('#ocg1').css('display', 'none');$('#ocg2').css('display', 'block');void(0);\">Show content</a>";
            echo "</div>\n";
            echo "<div id='ocg2' style='display:none;'>";
            echo "<a href=\"javascript:$('#ocg1').css('display', 'block');$('#ocg2').css('display', 'none');void(0);\">Hide content</a><br/>";
            echo "<table class='table table-bordered table-striped table-condensed' cellpadding='0' cellspacing='0' style='width:800px;font-size:90%;'>";
            echo "<thead><tr><th style='width:30%;'>Species</th><th style='width:'10%;'>#genes</th><th style='width:60%;'>Genes</th></tr></thead>";
            echo '<tbody>';
            foreach ($gf_content as $species => $gc) {
                $common_name = $all_species[$species];
                echo '<tr>';
                echo '<td>' .
                    $this->Html->link(
                        $common_name,
                        $exp_info['datasource_URL'] . '/organism/view/' . urlencode($common_name)
                    ) .
                    '</td>';
                echo '<td>' . count($gc) . '</td>';
                echo '<td>';
                foreach ($gc as $g) {
                    echo $this->Html->link($g, $exp_info['datasource_URL'] . '/genes/view/' . urlencode($g)) . ' ';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo "</div>\n";
            echo "</dd>\n";
        } ?>
    </dl>
</section>

<section class="page-section-sm">
    <?php
    $disable_cluster_tools = false;
    if (isset($max_number_jobs_reached)) {
        echo "<span class='text-danger'>The maximum number of jobs (" .
            MAX_CLUSTER_JOBS .
            ") you can have queued has been reached for this experiment.<br/>Some tools will be unavailable until the currently scheduled jobs have finished or have been deleted.</span><br/><br/>\n";
        $disable_cluster_tools = true;
    }

    $toolbox = [
        'Comparative genomics' => [
            [
                $gf_info['tree'] || $gf_info['msa']
                    ? 'View or create multiple sequence alignment / phylogenetic tree'
                    : 'Create multiple sequence alignment / phylogenetic tree',
                $this->Html->url(['controller' => 'tools', 'action' => 'create_tree', $exp_id, $gf_info['gf_id']]),
                null,
                $disable_cluster_tools
            ]
        ],
        'Functional data' => [
            [
                'View associated functional annotation',
                $this->Html->url([
                    'controller' => 'gene_family',
                    'action' => 'functional_annotation',
                    $exp_id,
                    $gf_info['gf_id']
                ])
            ]
        ]
    ];
    $this->set('toolbox', $toolbox);
    echo $this->element('toolbox');
    ?>
</section>
<section class="page-section-xs">
    <h3>Transcripts</h3>
    <div class="row" id="table-header">
        <div class="col-md-9">
            <?php echo $this->element('subset_create_form', [
                'exp_id' => $exp_id,
                'all_subsets' => $all_subsets,
                'collection_type' => 'gf',
                'tooltip_text' => $tooltip_text_subset_creation,
                'selection_parameters' => [$gf_info['gf_id']]
            ]); ?>
        </div>
        <div class="col-md-3 pull-right text-right">
            <?php
            $download_url = $this->Html->url([
                'controller' => 'trapid',
                'action' => 'transcript_selection',
                $exp_id,
                'gf_id',
                $gf_info['gf_id']
            ]);
            $this->set('download_url', $download_url);
            $this->set('allow_reference_aa_download', 1);
            echo $this->element('download_dropdown', ['align_right' => true]);
            ?>
        </div>
    </div>
    <?php echo $this->element('table_func'); ?>
</section>
<?php echo $this->element('help_tooltips/enable_tooltips', ['container' => '#table-header']); ?>
