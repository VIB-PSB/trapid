<?php
/* Left sidebar shown when the user is in an experiment. This element is heavily inspired by:
 * AdminLTE template (https://adminlte.io/themes/AdminLTE/),
 * The Evry iGEM 2015 wiki (http://2015.igem.org/Team:Evry),
 * and this CodePen snippet (https://codepen.io/zavoloklom/pen/dIgco).
 */

$process_state = $exp_info['process_state'];
// All the possible menu links + their text and URLs (when there is one).
$link_text = [
    'overview' => ['Overview', $this->Html->Url(['controller' => 'trapid', 'action' => 'experiment', $exp_id])],
    'import' => ['Import data', $this->Html->Url(['controller' => 'trapid', 'action' => 'import_data', $exp_id])],
    'export' => ['Export data', $this->Html->Url(['controller' => 'trapid', 'action' => 'export_data', $exp_id])],
    'stats' => ['Statistics', ''],
    'gen_stats' => [
        'General statistics',
        $this->Html->Url(['controller' => 'tools', 'action' => 'statistics', $exp_id])
    ],
    'len_tr' => [
        'Sequence length distribution',
        $this->Html->Url(['controller' => 'tools', 'action' => 'length_distribution', $exp_id, 'transcript'])
    ],
    'len_orf' => [
        'Length distribution ORF',
        $this->Html->Url(['controller' => 'tools', 'action' => 'length_distribution', $exp_id, 'orf'])
    ],
    'tax_binning' => [
        'Taxonomic classification',
        $this->Html->Url(['controller' => 'tools', 'action' => 'tax_binning', $exp_id])
    ],
    'gf' => ['Browse gene families', $this->Html->Url(['controller' => 'gene_family', 'action' => 'index', $exp_id])],
    'rf' => ['Browse RNA families', $this->Html->Url(['controller' => 'rna_family', 'action' => 'index', $exp_id])],
    'cgfc' => [
        'Core GF completeness',
        $this->Html->Url(['controller' => 'tools', 'action' => 'core_gf_completeness', $exp_id])
    ],
    'subsets' => [
        'Browse subsets',
        $this->Html->Url(['controller' => 'labels', 'action' => 'subset_overview', $exp_id])
    ],
    'enrichment' => [
        'Subset enrichment',
        $this->Html->Url(['controller' => 'tools', 'action' => 'enrichment', $exp_id])
    ],
    'enrichment_go' => [
        'GO term enrichment',
        $this->Html->Url(['controller' => 'tools', 'action' => 'enrichment', $exp_id, 'go'])
    ],
    'enrichment_ipr' => [
        'Protein domain enrichment',
        $this->Html->Url(['controller' => 'tools', 'action' => 'enrichment', $exp_id, 'ipr'])
    ],
    'sankey' => ['Sankey diagrams', ''],
    'sankey_enriched_go_gf' => [
        'Subset↔Enriched GO↔GF',
        $this->Html->Url(['controller' => 'tools', 'action' => 'label_enrichedgo_gf2', $exp_id])
    ],
    'sankey_enriched_ipr_gf' => [
        'Subset↔Enriched IPR↔GF',
        $this->Html->Url(['controller' => 'tools', 'action' => 'label_enrichedinterpro_gf2', $exp_id])
    ],
    'sankey_enriched_ko_gf' => [
        'Subset↔Enriched KO↔GF',
        $this->Html->Url(['controller' => 'tools', 'action' => 'label_enrichedko_gf2', $exp_id])
    ],
    'compare_subsets' => [
        'Compare subsets',
        $this->Html->Url(['controller' => 'tools', 'action' => 'compare_ratios', $exp_id])
    ],
    'doc' => ['Documentation', $this->Html->Url(['controller' => 'documentation', 'action' => 'index'])],
    'back_exp' => ['Back to experiments', $this->Html->Url(['controller' => 'trapid', 'action' => 'experiments'])]
];
?>

<script type="text/javascript">
    // Toggle sidebar
    $(document).ready(function() {
        var overlay = $('.sidebar-overlay');

        $('.sidebar-toggle').on('click', function() {
            var sidebar = $('#sidebar');
            sidebar.toggleClass('open');
            if ((sidebar.hasClass('sidebar-fixed-left') || sidebar.hasClass('sidebar-fixed-right')) && sidebar.hasClass('open')) {
                overlay.addClass('active');
            } else {
                overlay.removeClass('active');
            }
        });
        overlay.on('click', function() {
            $(this).removeClass('active');
            $('#sidebar').removeClass('open');
        });
    });
    // Add animation to sidebar dropdown elements (jQuery slideDown/slideUp). To replace by CSS animation if possible.
    $(document).ready(function() {
        var dropdown = $('.dropdown');
        var slideDuration = 150; // Animation duration in ms
        // Add slidedown animation to dropdown
        dropdown.on('show.bs.dropdown', function(e) {
            $(this).find('.dropdown-menu').first().stop(true, true).slideDown(slideDuration);
        });
        // Add slideup animation to dropdown
        dropdown.on('hide.bs.dropdown', function(e) {
            $(this).find('.dropdown-menu').first().stop(true, true).slideUp(slideDuration);
        });
        // Toggle sidebar shadow ('sidebar tools' on the experiment overview page)
        $('#toggle-shadow').on('click', function() {
            var sidebar = $('#sidebar');
            sidebar.toggleClass('sidebar-shadow');
        });
    });
</script>
<!-- Overlay (if fixed sidebar) -->
<div class="sidebar-overlay"></div>
<!-- Experiment navbar (sidebar) -->
<aside id="sidebar" class="sidebar sidebar-colored open sidebar-stacked sidebar-shadow preload" role="navigation">
    <!-- Sidebar header -->
    <div class="sidebar-header">
    <?php
    $tpd_label = 'TRAPID';
    if (IS_DEV_ENVIRONMENT) {
        $tpd_label .= ' <label class="label label-beta">dev</label>';
    }
    echo $this->Html->link(
        $tpd_label,
        ['controller' => 'trapid', 'action' => 'experiments'],
        ['class' => 'sidebar-brand', 'escape' => false]
    );
    ?>

    </div>
    <!-- Experiment sidebar navigation -->
    <ul class="nav sidebar-nav">
        <?php // if ($exp_info['transcript_count'] != 0)... Check on transcript counts too?
        // First way to adjust availability of sidebar links is by checking the status of the experiment.
        if (in_array($process_state, ['processing', 'loading_db', 'error'])): ?>
            <?php foreach (['overview', 'import', 'export'] as $link_id) {
                echo "<li class=\"sidebar-text sidebar-disabled\">" . $link_text[$link_id][0] . "</li>\n";
            } ?>
            <li class="divider"></li>
            <?php foreach (
                ['stats', 'tax_binning', 'gf', 'rf', 'cgfc', 'subsets', 'enrichment', 'sankey', 'compare_subsets']
                as $link_id
            ) {
                echo "<li class=\"sidebar-text sidebar-disabled\">" . $link_text[$link_id][0] . "</li>\n";
            } ?>

        <?php elseif ($process_state == 'empty'): ?>
            <?php
            foreach (['overview', 'import'] as $link_id) {
                echo "<li><a href='" . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
            }
            echo "<li class=\"sidebar-text sidebar-disabled\">" . $link_text['export'][0] . "</li>\n";
            ?>
            <li class="divider"></li>
            <?php foreach (
                ['stats', 'tax_binning', 'gf', 'rf', 'cgfc', 'subsets', 'enrichment', 'sankey', 'compare_subsets']
                as $link_id
            ) {
                echo "<li class=\"sidebar-text sidebar-disabled\">" . $link_text[$link_id][0] . "</li>\n";
            } ?>

        <?php elseif ($process_state == 'upload'): ?>
            <?php foreach (['overview', 'import', 'export'] as $link_id) {
                echo "<li><a href='" . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
            } ?>
            <li class="divider"></li>
            <li class="dropdown">
                <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                    <?php echo $link_text['stats'][0]; ?><b class="caret"></b>
                </a>
                <ul id="stats-dropdown" class="dropdown-menu">
                    <?php foreach (['gen_stats', 'len_tr'] as $link_id) {
                        echo "<li><a href='" . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
                    } ?>
                </ul>
            </li>
            <?php
            echo "<li class=\"sidebar-text sidebar-disabled\">" . $link_text['tax_binning'][0] . "</li>\n";
            foreach (['gf', 'rf'] as $link_id) {
                echo "<li><a href='" . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
            }
            foreach (['cgfc', 'subsets', 'enrichment', 'sankey', 'compare_subsets'] as $link_id) {
                echo "<li class=\"sidebar-text sidebar-disabled\">" . $link_text[$link_id][0] . "</li>\n";
            }
            ?>

        <?php elseif ($process_state == 'finished'): ?>
            <?php foreach (['overview', 'import', 'export'] as $link_id) {
                echo "<li><a href='" . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
            } ?>
            <li class="divider"></li>
            <li class="dropdown">
                <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                    <?php echo $link_text['stats'][0]; ?><b class="caret"></b>
                </a>
                <ul id="stats-dropdown" class="dropdown-menu">
                    <?php foreach (['gen_stats', 'len_tr'] as $link_id) {
                        echo "<li><a href='" . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
                    } ?>
                </ul>
            </li>
            <?php
            if ($exp_info['perform_tax_binning'] == 1) {
                echo "<li><a href='" .
                    $link_text['tax_binning'][1] .
                    "'>" .
                    $link_text['tax_binning'][0] .
                    "</a></li>\n";
            } else {
                echo "<li class=\"sidebar-text sidebar-disabled\">" . $link_text['tax_binning'][0] . "</li>\n";
            }
            foreach (['gf', 'rf', 'cgfc'] as $link_id) {
                echo "<li><a href='" . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
            }
            ?>
            <li class="divider"></li>
            <?php if ($exp_info['label_count'] == 0): ?>
                <?php foreach (['subsets', 'enrichment', 'sankey', 'compare_subsets'] as $link_id) {
                } ?>
            <?php else: ?>
                <?php echo "<li><a href='" .
                    $link_text['subsets'][1] .
                    "'>" .
                    $link_text['subsets'][0] .
                    "</a></li>\n"; ?>
                <?php echo "<li><a href='" .
                    $link_text['enrichment'][1] .
                    "'>" .
                    $link_text['enrichment'][0] .
                    "</a></li>\n"; ?>
                <?php if ($exp_info['enrichment_state'] == 'finished'): ?>
                    <li class="dropdown">
                        <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                            <?php echo $link_text['sankey'][0]; ?><b class="caret"></b>
                        </a>
                        <ul id="stats-dropdown" class="dropdown-menu">
                            <?php
                            $link = [
                                'go' => 'sankey_enriched_go_gf',
                                'interpro' => 'sankey_enriched_ipr_gf',
                                'ko' => 'sankey_enriched_ko_gf'
                            ];
                            foreach ($exp_info['function_types'] as $fct_type) {
                                echo "<li><a href='" .
                                    $link_text[$link[$fct_type]][1] .
                                    "'>" .
                                    $link_text[$link[$fct_type]][0] .
                                    "</a></li>\n";
                            }
                            ?>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="sidebar-text sidebar-disabled"><?php echo $link_text['sankey'][0]; ?></li>
                <?php endif; ?>

                <?php if ($exp_info['label_count'] > 1): ?>
                    <li>
                        <a href="<?php echo $link_text['compare_subsets'][1]; ?>">
                            <?php echo $link_text['compare_subsets'][0]; ?>
                        </a>
                    </li>
                <?php else: ?>
                    <?php echo "<li class=\"sidebar-text sidebar-disabled\">" .
                        $link_text['compare_subsets'][0] .
                        "</li>\n"; ?>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </ul>
    <div>
        <ul class="nav sidebar-nav">
            <li class="divider"></li>
            <?php echo "<li><a href='" . $link_text['doc'][1] . "'>" . $link_text['doc'][0] . "</a></li>\n"; ?>
            <?php echo "<li><a href='" .
                $link_text['back_exp'][1] .
                "'>" .
                $link_text['back_exp'][0] .
                "</a></li>\n"; ?>
        </ul>
    </div>
</aside>
