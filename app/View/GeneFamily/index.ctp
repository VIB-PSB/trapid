<div class='page-header'>
    <h1 class="text-primary">Gene families</h1>
</div>
<!--<ol class="breadcrumb breadcrumb-sm">-->
<!--  <li><a href="#">Experiment</a></li>-->
<!--  <li><a href="#">Gene families</a></li>-->
<!--  <li class="active">Overview</li>-->
<!--</ol>-->
<div class="subdiv">
    <?php echo $this->element("trapid_experiment"); ?>
<!--    <h2>Overview</h2>-->
    <div class="subdiv">
        <?php $this->Paginator->options(array("url" => $this->passedArgs)); ?>
        <!--		<table cellpadding="0" cellspacing="0" style="width:900px">-->
        <table class="table table-bordered table-striped table-hover table-condensed">
            <thead>
            <!--			<tr>-->
<!--            <th style="width:20%">--><?php //echo $this->Paginator->sort("Gene Family", "gf_id"); ?><!--</th>-->
<!--            <th style="width:15%">--><?php //echo $this->Paginator->sort("#Transcripts", "num_transcripts"); ?><!--</th>-->
            <th style="width:20%"><?php echo $this->Paginator->sort("gf_id", "Gene Family"); ?></th>
            <th style="width:15%"><?php echo $this->Paginator->sort("num_transcripts", "# Transcripts"); ?></th>
            <?php
            if ($exp_info["genefamily_type"] == "HOM") {
                echo "<th style='width:15%'>External GF</th>\n";
                echo "<th style='width:10%'>#Genes external GF</th>\n";
                echo "<th style='width:10%'>#Species external GF</th>\n";
            } else {
                echo "<th style='width:10%'>#Genes IOrtho group</th>\n";
                echo "<th style='width:10%'>#Species IOrtho group</th>\n";
            }
            ?>
            <th style="width:10%">Computed MSA</th>
            <th style="width:10%">Computed tree</th>
            </thead>
            <!--			</tr>-->
            <tbody>
            <?php
            foreach ($gene_families as $gene_family) {
                echo "<tr>";
                echo "<td>" . $this->Html->link($gene_family['GeneFamilies']['gf_id'], array("controller" => "gene_family", "action" => "gene_family", $exp_id, $gene_family['GeneFamilies']['gf_id'])) . "</td>";
                echo "<td>" . $gene_family['GeneFamilies']['num_transcripts'] . "</td>";


                if ($exp_info['genefamily_type'] == "HOM") {
                    if ($exp_info['allow_linkout']) {
                        if(isset($eggnog_og_linkout)) {
                            echo "<td>" . $this->Html->link($gene_family['GeneFamilies']['plaza_gf_id'], $exp_info["datasource_URL"] . "#/app/results?target_nogs=" . $gene_family['GeneFamilies']['plaza_gf_id'], array("target"=>"_blank", "class"=>"linkout")) . "</td>\n";
                        }
                        else {
                            echo "<td>" . $this->Html->link($gene_family['GeneFamilies']['plaza_gf_id'], $exp_info["datasource_URL"] . "/gene_families/view/" . $gene_family['GeneFamilies']['plaza_gf_id'], array("target"=>"_blank", "class"=>"linkout")) . "</td>\n";
                        }
                    }
                    else {
                        echo "<td>" . $gene_family['GeneFamilies']['plaza_gf_id'] . "</td>\n";
                    }
                    echo "<td>" . $gf_gene_counts[$gene_family['GeneFamilies']['plaza_gf_id']] . "</td>\n";
                    echo "<td>" . $gf_species_counts[$gene_family['GeneFamilies']['plaza_gf_id']] . "</td>\n";
                } else {
                    echo "<td>" . $gf_gene_counts[$gene_family['GeneFamilies']['gf_id']] . "</td>\n";
                    echo "<td>" . $gf_species_counts[$gene_family['GeneFamilies']['gf_id']] . "</td>\n";
                }


                if ($gene_family['GeneFamilies']['msa']) {
                    echo "<td><span style='font-weight:bold;' class='text-success'>V</span></td>\n";
                } else {
                    echo "<td><span style='font-weight:bold;' class='text-danger'>X</span></td>\n";
                }
                if ($gene_family['GeneFamilies']['tree']) {
                    echo "<td><span style='font-weight:bold;' class='text-success'>V</span></td>\n";
                } else {
                    echo "<td><span style='font-weight:bold;' class='text-danger'>X</span></td>\n";
                }
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

    </div>
</div>
</div>

<?php echo $this->element('sql_dump');  // Dump all MySQL queries (debug) ?>