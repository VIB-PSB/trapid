<div class='page-header'>
    <h1 class="text-primary">Gene families</h1>
</div>
<?php $this->Paginator->options(['url' => $this->passedArgs]); ?>
<table class="table table-bordered table-striped table-hover table-condensed">
    <thead>
        <th><?php echo $this->Paginator->sort('gf_id', 'Gene Family'); ?></th>
        <th><?php echo $this->Paginator->sort('num_transcripts', '# Transcripts'); ?></th>
        <?php if ($exp_info['genefamily_type'] == 'HOM') {
            echo "<th>External GF</th>\n";
            echo "<th>#Genes external GF</th>\n";
            echo "<th>#Species external GF</th>\n";
        } else {
            echo "<th>#Genes IOrtho group</th>\n";
            echo "<th>#Species IOrtho group</th>\n";
        } ?>
        <th>Computed MSA</th>
        <th>Computed tree</th>
    </thead>
    <tbody>
        <?php foreach ($gene_families as $gene_family) {
            echo '<tr>';
            echo '<td>' .
                $this->Html->link($gene_family['GeneFamilies']['gf_id'], [
                    'controller' => 'gene_family',
                    'action' => 'gene_family',
                    $exp_id,
                    $gene_family['GeneFamilies']['gf_id']
                ]) .
                '</td>';
            echo '<td>' . $gene_family['GeneFamilies']['num_transcripts'] . '</td>';
            if ($exp_info['genefamily_type'] == 'HOM') {
                if ($exp_info['allow_linkout']) {
                    $linkout_base = isset($eggnog_og_linkout) ? '#/app/results?target_nogs=' : '/gene_families/view/';
                    echo '<td>';
                    echo $this->Html->link(
                        $gene_family['GeneFamilies']['plaza_gf_id'],
                        $exp_info['datasource_URL'] . $linkout_base . $gene_family['GeneFamilies']['plaza_gf_id'],
                        ['target' => '_blank', 'class' => 'linkout']
                    );
                    echo "</td>\n";
                } else {
                    echo '<td>' . $gene_family['GeneFamilies']['plaza_gf_id'] . "</td>\n";
                }
                echo '<td>' . $gf_gene_counts[$gene_family['GeneFamilies']['plaza_gf_id']] . "</td>\n";
                echo '<td>' . $gf_species_counts[$gene_family['GeneFamilies']['plaza_gf_id']] . "</td>\n";
            } else {
                echo '<td>' . $gf_gene_counts[$gene_family['GeneFamilies']['gf_id']] . "</td>\n";
                echo '<td>' . $gf_species_counts[$gene_family['GeneFamilies']['gf_id']] . "</td>\n";
            }

            echo "<td class='text-center'>";
            echo $gene_family['GeneFamilies']['msa']
                ? "<span class='material-icons md-18 text-success'>check</span>"
                : "<span class='material-icons md-18 text-danger'>close</span>";
            echo '</td>';
            echo "<td class='text-center'>";
            echo $gene_family['GeneFamilies']['tree']
                ? "<span class='material-icons md-18 text-success'>check</span>"
                : "<span class='material-icons md-18 text-danger'>close</span>";
            echo '</td>';
            echo "</tr>\n";
        } ?>
    </tbody>
</table>
<div class="text-right">
    <div class='pagination pagination-sm no-margin-top'>
        <?php
        echo $this->Paginator->prev(__('Previous'), ['tag' => 'li'], null, [
            'tag' => 'li',
            'class' => 'disabled',
            'disabledTag' => 'a'
        ]);
        echo $this->Paginator->numbers([
            'separator' => '',
            'currentTag' => 'a',
            'currentClass' => 'active',
            'tag' => 'li',
            'first' => 1
        ]);
        echo $this->Paginator->next(__('Next'), ['tag' => 'li', 'currentClass' => 'disabled'], null, [
            'tag' => 'li',
            'class' => 'disabled',
            'disabledTag' => 'a'
        ]);
        ?>
    </div>
</div>