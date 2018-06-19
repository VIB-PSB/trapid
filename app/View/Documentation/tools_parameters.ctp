<div class="container">
    <div class="page-header">
        <h1 class="text-primary">Tools & parameters</h1>
    </div>
    <section class="page-section">
        <p class="text-justify">This page provides an overview of the external resources/tools used within TRAPID.</p>
        <p class="text-justify">The resources/tools ae organized by general categories, depending on which part of TRAPID makes use of them, and then sorted by alphabetical order.
            Extra information regarding used versions or parameters, and links to either the official website or to the relevant publication, are also provided (when applicable).
        </p>
    </section>

    <div class="row">
        <div class="col-md-3 scrollspy" id="navigation-col">
            <ul class="nav hidden-xs hidden-sm" id="sidebar-nav" data-spy="affix">
                <h4 style="padding-top: 15px;"><span class="glyphicon glyphicon-list-alt"></span> Navigation</h4><br>
                <li><a href="#ref-dbs">Reference databases</a></li>
                <li><a href="#init-processing">Initial processing</a></li>
                <li><a href="#msa-phylogeny">MSA and pylogeny</a></li>
            </ul>
        </div>

        <div class="col-md-9" id="tutorial-col">
            <section class="page-section" id="ref-dbs">
                <h3>Reference databases</h3>
                <p class="text-justify">For extensive details about the reference databases (e.g. gene family construction, included clades/species, ...), please refer to their documentation. </p>
                <ul class="list-unstyled">
                <?php
                foreach ($ref_db_data as $db) {
                    echo "<li><a class='linkout' href='". $db['DataSources']['URL'] . "' target='_blank'>" . $db['DataSources']['name']. "</a></li>";
                }
                ?>
                </ul>
            </section>
            <section class="page-section" id="init-processing">
                <h3>Initial processing</h3>
                <?php
                $init_processing_tools = array("diamond", "infernal", "kaiju", "ncbi_nr_prot", "ncbi_tax", "rfam");
                foreach ($init_processing_tools as $tool) {
                    echo $this->element('doc_tools_parameters', array('tool_data'=>$tools_parameters_data[$tool]));
                }
                ?>
            </section>

            <section class="page-section" id="msa-phylogeny">
                <h3>MSA and phylogeny</h3>
                <?php
                $msa_trees_tools = array("fasttree", "muscle", "ncbi_tax", "phyml");
                foreach ($msa_trees_tools as $tool) {
                    echo $this->element('doc_tools_parameters', array('tool_data'=>$tools_parameters_data[$tool]));
                }
                ?>
            </section>

        </div> <!-- End column -->
    </div> <!-- End row -->
</div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
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
            }, 'slow');
            return false;
        });
    });
</script>
