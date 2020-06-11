<div class="container">
    <div class="page-header">
        <h1 class="text-primary">Tools & parameters</h1>
    </div>
    <section class="page-section">
        <p class="text-justify">This page lists the third-party resources and tools used within TRAPID. </p>
            <p class="text-justify">They are organized by general categories, depending on which part of TRAPID makes use of them, and sorted by alphabetical order. Further information about used versions and parameters, together with links to either the official website or to the relevant publication (when applicable), are also provided.
        </p>
    </section>

    <div class="row">

        <div class="col-md-9" id="tutorial-col">
            <section class="page-section" id="ref-dbs">
                <h3>Reference databases</h3>
                <p class="text-justify">The below table provides an overview of TRAPID 2.0 reference databases. The gene family count only includes homology-based families for PLAZA databases, and only orthologous groups at the root level for EggNOG 4.5. For extensive details about the reference databases (e.g. further information on gene family construction, list of included clades/species, ...), please refer to their own documentation. </p><br>
                <style>
                    td,th {
                        text-align: center;
                        vertical-align: middle;
                    }
                    td.first-col, th.first-col{
                        text-align: right;
                        font-weight: bold;
                        width: 15%;
                    }
                </style>
                <table class="table table-hover table-striped" style="font-size: 90%;">
                    <thead>
                        <tr>
                            <th class="first-col"></th>
                            <?php foreach(['db_trapid_ref_plaza_dicots_04_5_test', 'db_trapid_ref_plaza_monocots_04_5_test', 'db_trapid_ref_plaza_pico_03_test', 'db_trapid_ref_eggnog_test_02'] as $ref_db): ?>
                            <th><a href="<?php echo $ref_db_data[$ref_db]['url']; ?>" target="_blank" class="linkout"><?php echo $ref_db_data[$ref_db]['name']; ?></a></th>
                            <?php endforeach;?>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="first-col"># Species</td>
                            <td>55</td>
                            <td>39</td>
                            <td>39</td>
                            <td>2,031</td>
                        </tr>
                        <tr>
                            <td class="first-col"># Genes</td>
                            <td>3,065,012</td>
                            <td>1,563,555</td>
                            <td>705,020</td>
                            <td>14,116,949</td>
                        </tr>
                        <tr>
                            <td class="first-col"># Gene families</td>
                            <td>208,456</td>
                            <td>213,318</td>
                            <td>127,718</td>
                            <td>190,803</td>
                        </tr>
                        <tr>
                            <td class="first-col">Taxonomic focus</td>
                            <td>Dicot plants</td>
                            <td>Monocot plants</td>
                            <td>Microbial photosynthetic eukaryotes</td>
                            <td>Archaea, Bacteria, Eukaryotes</td>
                        </tr>
                        <tr>
                            <td class="first-col">Functional annotation</td>
                            <td>GO, InterPro</td>
                            <td>GO, InterPro</td>
                            <td>GO, InterPro</td>
                            <td>GO, KO</td>
                        </tr>
                        <tr>
                            <td class="first-col">Gene family construction</td>
                            <td>Tribe-MCL, integrative orthologs</td>
                            <td>Tribe-MCL, integrative orthologs</td>
                            <td>Tribe-MCL, integrative orthologs</td>
                            <td>EggNOG</td>
                        </tr>
                        </tbody>
                    </table>
            </section>
            <hr>
            <section class="page-section" id="init-processing">
                <h3>Initial processing</h3>
                <?php
                $init_processing_tools = array("diamond", "infernal", "kaiju", "ncbi_nr_prot", "ncbi_tax", "rfam");
                foreach ($init_processing_tools as $tool) {
                    echo $this->element('doc_tools_parameters', array('tool_data'=>$tools_parameters_data[$tool]));
                }
                ?>
            </section>
            <hr>
            <section class="page-section" id="msa-phylogeny">
                <h3>MSA and phylogeny</h3>
                <?php
                $msa_trees_tools = array("mafft", "muscle", "fasttree", "iq_tree", "raxml", "phyml");
                foreach ($msa_trees_tools as $tool) {
                    echo $this->element('doc_tools_parameters', array('tool_data'=>$tools_parameters_data[$tool]));
                }
                ?>
            </section>
            <hr>
            <section class="page-section" id="visualization">
                <h3>Data visualization</h3>
                <?php
                $viz_tools = array("unipept_viz", "krona", "msa_viewer", "phyd3");
                foreach ($viz_tools as $tool) {
                    echo $this->element('doc_tools_parameters', array('tool_data'=>$tools_parameters_data[$tool]));
                }
                ?>
            </section>

        </div> <!-- End column -->

        <div class="col-md-3 scrollspy" id="navigation-col">
            <ul class="nav hidden-xs hidden-sm" id="sidebar-nav" data-spy="affix">
<!--                <h5 class="doc-sidebar-header"><i class="material-icons md-24">toc</i> Sections</h5>-->
                <h5 class="doc-sidebar-header">Contents</h5>
                <li><a href="#ref-dbs">Reference databases</a></li>
                <li><a href="#init-processing">Initial processing</a></li>
                <li><a href="#msa-phylogeny">MSA and pylogeny</a></li>
                <li><a href="#visualization">Data visualization</a></li>
                <li class="sidebar-nav-to-top"><a href="#top">Back to top</a></li>
            </ul>
        </div>


    </div> <!-- End row -->
</div>
</div>
<script type="text/javascript">
//    $(document).ready(function () {
        // Affix navigation (bootstrap)
        $('body').attr('data-spy', 'scroll');
        $('body').attr('data-target', '.scrollspy');
        $('#sidebar-nav').affix({
            offset: {
                top: $('#sidebar-nav').offset().top
            }
        });
        // Scroll to anchors smoothly
        $('a[href^="#"]').click(function () {
            var the_id = $(this).attr("href");
            $('html, body').animate({
                scrollTop: $(the_id).offset().top
            }, 250, 'swing');
            return false;
        });
//    });
</script>
