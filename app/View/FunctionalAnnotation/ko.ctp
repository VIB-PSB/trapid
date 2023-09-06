<?php
echo $this->Html->script('selectize.min.js');
echo $this->Html->css('selectize.paper.css');
$ko = $ko_info['name'];
?>
<div class="page-header">
    <h1 class="text-primary"><?php echo $ko; ?> <small>KO term</small></h1>
</div>
<section class="page-section-xs">
    <h3>Overview</h3>
    <dl class="standard dl-horizontal">
        <dt>KO term</dt>
        <dd>
            <?php
            echo $ko;
            echo ' &nbsp; &nbsp; ';
            echo $this->element('linkout_func', ['linkout_type' => 'kegg_ko', 'query_term' => $ko]);
            ?>
        </dd>
        <dt>Description</dt>
        <dd><?php echo $ko_info['desc']; ?></dd>
        <dt>#Transcripts</dt>
        <dd><?php echo $num_transcripts; ?></dd>
    </dl>
</section>
<section class="page-section-sm">
    <?php
    $toolbox = [
        'Find' => [
            ['The associated gene families table', $this->Html->url(['action' => 'assoc_gf', $exp_id, 'ko', $ko])],
            [
                'The associated gene families visualization',
                $this->Html->url(['controller' => 'tools', 'action' => 'KOSankey', $exp_id, $ko])
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
                'collection_type' => 'ko',
                'tooltip_text' => $tooltip_text_subset_creation,
                'selection_parameters' => [$ko]
            ]); ?>
        </div>
        <div class="col-md-3 pull-right text-right">
            <?php
            $download_url = $this->Html->url([
                'controller' => 'trapid',
                'action' => 'transcript_selection',
                $exp_id,
                'ko',
                $ko
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
