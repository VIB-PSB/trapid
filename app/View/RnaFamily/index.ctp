<?php
/**
 * Created by PhpStorm.
 * User: frbuc
 * Date: 6/21/18
 * Time: 6:47 PM
 */
?>
<div class="page-header">
    <h1 class="text-primary">RNA families</h1>
</div>
<section class="page-section">
        <?php $this->Paginator->options(array("url" => $this->passedArgs)); ?>
        <table class="table table-bordered table-striped table-hover table-condensed">
            <thead>
                <th><?php echo $this->Paginator->sort("rf_id", "RNA Family"); ?></th>
                <th><?php echo $this->Paginator->sort("num_transcripts", "# Transcripts"); ?></th>
                <th>External RF</th>
                <th>Clan</th>
            </thead>
            <tbody>
            <?php
            //TODO: move linkout url to DB's `configuration` table
            $linkout_base_url = "http://rfam.xfam.org/family/";
            foreach ($rna_families as $rna_family) {
                echo "<tr>";
                echo "<td>" . $this->Html->link($rna_family['RnaFamilies']['rf_id'], array("controller" => "rna_family", "action" => "index", $exp_id, $rna_family['RnaFamilies']['rf_id'])) . "</td>";
                echo "<td>" . $rna_family['RnaFamilies']['num_transcripts'] . "</td>";
                echo "<td><a class='linkout' target='_blank' href='". $linkout_base_url . $rna_family['RnaFamilies']['rfam_rf_id'] . "'>" . $rna_family['RnaFamilies']['rfam_rf_id'] . "</a></td>";
                echo "<td>" . $rna_family['RnaFamilies']['rfam_clan_id'] . "</td>";
                echo "</tr>\n";
            }
            ?>
            </tbody>
        </table>
        <div class='paging'>
            <?php
            echo $this->Paginator->prev('<< ' . __('previous'), array(), null, array('class' => 'disabled'));
            echo "&nbsp;";
            echo $this->Paginator->numbers();
            echo "&nbsp;";
            echo $this->Paginator->next(__('next') . ' >>', array(), null, array('class' => 'disabled'));
            ?>
        </div>
</section>
