<?php
echo $this->Html->script('selectize.min.js');
echo $this->Html->css('selectize.paper.css');
$go_web = str_replace(':', '-', $go_info['name']);
?>
<div class="page-header">
    <h1 class="text-primary"><?php echo $go_info['name']; ?> <small>GO term</small></h1>
</div>
<section class="page-section-xs">
    <h3>Overview</h3>
    <dl class="standard dl-horizontal">
        <dt>GO term</dt>
        <dd>
            <?php
            echo $exp_info['allow_linkout']
                ? $this->Html->link($go_info['name'], $exp_info['datasource_URL'] . 'go/view/' . $go_web)
                : $go_info['name'];
            echo '&nbsp;';
            echo $this->element('go_category_badge', ['go_category' => $go_info['info'], 'small_badge' => true]);
            echo ' &nbsp; &nbsp; ';
            echo $this->element('linkout_func', ['linkout_type' => 'amigo', 'query_term' => $go_info['name']]);
            echo ' ';
            echo $this->element('linkout_func', [
                'linkout_type' => 'quickgo',
                'query_term' => $go_info['name']
            ]);
            ?>
        </dd>
        <dt>Description</dt>
        <dd><?php echo $go_info['desc']; ?></dd>
        <dt>#Transcripts</dt>
        <dd><?php echo $num_transcripts; ?></dd>
    </dl>
</section>
<section class="page-section-sm">
    <?php
    $toolbox = [
        'Find' => [
            ['The associated gene families table', $this->Html->url(['action' => 'assoc_gf', $exp_id, 'go', $go_web])],
            [
                'The associated gene families visualization',
                $this->Html->url(['controller' => 'tools', 'action' => 'GOSankey', $exp_id, $go_web])
            ]
        ],
        'Explore' => [
            ['Explore the children GO terms', $this->Html->url(['action' => 'child_go', $exp_id, $go_web])],
            ['Explore the parental GO terms', $this->Html->url(['action' => 'parent_go', $exp_id, $go_web])]
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
                'collection_type' => 'go',
                'tooltip_text' => $tooltip_text_subset_creation,
                'selection_parameters' => [$go_web]
            ]); ?>
        </div>
        <div class="col-md-3 pull-right text-right">
            <?php
            $download_url = $this->Html->url([
                'controller' => 'trapid',
                'action' => 'transcript_selection',
                $exp_id,
                'go',
                $go_web
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
