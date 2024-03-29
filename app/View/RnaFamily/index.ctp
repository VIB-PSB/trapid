<div class="page-header">
    <h1 class="text-primary">RNA families</h1>
</div>
<section class="page-section">
    <?php $this->Paginator->options(['url' => $this->passedArgs]); ?>
    <table class="table table-bordered table-striped table-hover table-condensed">
        <thead>
            <th><?php echo $this->Paginator->sort('rf_id', 'RNA Family'); ?></th>
            <th><?php echo $this->Paginator->sort('num_transcripts', '# Transcripts'); ?></th>
            <th>External RF</th>
            <th>Clan</th>
        </thead>
        <tbody>
            <?php foreach ($rna_families as $rna_family) {
                $rf_name = $rf_names[$rna_family['RnaFamilies']['rfam_rf_id']];
                echo '<tr>';
                echo '<td>' .
                    $this->Html->link($rna_family['RnaFamilies']['rf_id'], [
                        'controller' => 'rna_family',
                        'action' => 'rna_family',
                        $exp_id,
                        $rna_family['RnaFamilies']['rf_id']
                    ]) .
                    '</td>';
                echo '<td>' . $rna_family['RnaFamilies']['num_transcripts'] . '</td>';
                echo "<td><a class='linkout' target='_blank' href='" .
                    $rfam_linkouts['base_url'] .
                    $rfam_linkouts['family'] .
                    $rna_family['RnaFamilies']['rfam_rf_id'] .
                    "'>" .
                    $rf_name .
                    ' (' .
                    $rna_family['RnaFamilies']['rfam_rf_id'] .
                    ')</a></td>';
                // Not all RFAM families are in clans
                if (!empty($rna_family['RnaFamilies']['rfam_clan_id'])) {
                    $clan_name = $clan_names[$rna_family['RnaFamilies']['rfam_clan_id']];
                    echo "<td><a class='linkout' target='_blank' href='" .
                        $rfam_linkouts['base_url'] .
                        $rfam_linkouts['clan'] .
                        $rna_family['RnaFamilies']['rfam_clan_id'] .
                        "'>" .
                        $clan_name .
                        ' (' .
                        $rna_family['RnaFamilies']['rfam_clan_id'] .
                        ')</a></td>';
                } else {
                    echo '<td>-</td>';
                }
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
</section>
