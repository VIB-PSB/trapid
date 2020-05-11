<?php

// FAQ content is defined here, as associative arrays (one per FAQ category).
// The rest of the page parses this information to display the content.
// array structure: array("element-id"=>array("q"=>"question", "a"=>"answer"))... Element ids must be unique!

$faq_general = array(
    "max-exp"=>array("q"=>"How many TRAPID experiments can I create?", "a"=>"Each user has the ability to create up to 20 different TRAPID experiments."),
    "forgotten-pwd"=>array("q"=>"I've lost my password. What to do next?", "a"=>"Go to the " . $this->Html->link("forgotten password page",array("controller"=>"trapid","action"=>"authentication","password_recovery")) . " and fill in the form with the email address used to create a TRAPID account to reset your password. If this does not work, <a href='http://bioinformatics.psb.ugent.be/trapid_02/documentation/contact'>contact us</a> and we will send you a news password for your user-account. "),
    "bug-report"=>array("q"=>"I think I found a bug. Can I report it?", "a"=>"Please do. Just <a href='http://bioinformatics.psb.ugent.be/trapid_02/documentation/contact'>send us an email</a>, and we will investigate the issue."),
    "share-exp"=>array("q"=>" I want to share my experiment with my colleagues. Do I have to share my account information with them?", "a"=>"This is not necessary! If your colleagues also create a TRAPID account, you can easily share only a select number of experiments with them. This is done by following the 'Experiment settings' link available on the top section of an experiment page, under settings (<span class=\"glyphicon glyphicon-cog\"></span> icon)."),
    "data-secure"=>array("q"=>"Is my data secure?", "a"=>"Yes. We have taken extensive measures to ensure that only authorized people have access to the user data."),
);

$faq_io = array(
    "input"=>array("q"=>"What input files should I use?", "a"=>"TRAPID supports properly formatted multi-fasta files, with the '>' symbol indicating the transcript identifier of the following sequence (see also <a href='http://en.wikipedia.org/wiki/FASTA_format'>here</a>). In case the headers of the multi-fasta file consist of multiple sections separated by the '|' symbol, the first section will be used as unique identifier."),
    "max-sqce"=>array("q"=>"How many sequences can I process at once?", "a"=>"The TRAPID system is able to process up to 200k transcripts within a single experiment. Adding more transcripts is possible, but correct processing is not guaranteed in this case."),
    "import-subset"=>array("q"=>"How can I upload subset label information?", "a"=>"On an experiment page, there is a link to 'Import transcript labels' in the 'Import/Export' section. Here,  you have to give - for each label - a file containing the transcript ids which should be associated with the indicated subset label."),
    "export-data"=>array("q"=>"Can I export the TRAPID results in bulk?", "a"=>"Yes you can. The necessary download options can be found in the 'Import/Export' section of an experiment page."),
    "multi-species"=>array("q"=>"Can I analyze transcripts from multiple species simultaneously?", "a"=>"In case you use the labels to mark the origin of different transcripts (e.g. from species X and Y), you can analyze these two sets of transcripts in a combined manner in one TRAPID experiment. Note that you easily can upload multiple sequence files into one experiment; see General documentation, section Uploading transcript sequences and Job control. "),
);

$faq_analyzes = array(
    "tree-download"=>array("q"=>"How can I download an alignment and/or phylogenetic tree?", "a"=>"After the multiple sequence alignment (MSA) has been created for a given gene family, the user can both view the MSA and download the MSA in text-format, by following the alignment links within the toolbox on the gene family page. If a phylogenetic tree has been created, both the MSA and the tree will be downloadable by following the tree links within the toolbox on the gene family page. The phylogenetic tree will be downloadable in both newick format and phyloxml-format."),
    "tree-all"=>array("q"=>"Is it possible to automatically generate all phylogenetic trees within a TRAPID experiment?", "a"=>"Due to the heavy computational requirements for generating multiple thousands of multiple sequence alignments and phylogenetic trees, this is currently not possible. As such, creating phylogenetic trees can only be done on a per-gene family basis. "),
    "framedp-all"=>array("q"=>"Is it possible to automatically run FrameDP on each transcript within a TRAPID experiment?", "a"=>"FrameDP is extremely computationally expensive, and is such only offered on a per-gene family basis."),
);

?>

<div class="container">
    <div class="page-header">
		<h1 class="text-primary">Frequently Asked Questions</h1>
    </div>

    <div class="row">
        <section class="page-section-sm">
            <h3>General</h3>
            <ul class="list-unstyled">
                <?php
                foreach ($faq_general as $faq_key=>$faq_data) {
                    echo "<li><a href='#" . $faq_key . "'>" . $faq_data['q'] . "</a></li>";
                }
                ?>
            </ul>
        </section>
        <section class="page-section-sm">
            <h3>Input/output</h3>
            <ul class="list-unstyled">
                <?php
                foreach ($faq_io as $faq_key=>$faq_data) {
                    echo "<li><a href='#" . $faq_key . "'>" . $faq_data['q'] . "</a></li>";
                }
                ?>
            </ul>
        </section>
        <section class="page-section-sm">
            <h3>Analyzes</h3>
            <ul class="list-unstyled">
                <?php
                foreach ($faq_analyzes as $faq_key=>$faq_data) {
                    echo "<li><a href='#" . $faq_key . "'>" . $faq_data['q'] . "</a></li>";
                }
                ?>
            </ul>
        </section>
    </div>

    <hr>
    <div class="row">
        <h2>Answers</h2>
        <div class="col-md-3 scrollspy" id="navigation-col">
            <ul class="nav hidden-xs hidden-sm" id="sidebar-nav" data-spy="affix">
                <h4 style="padding-top: 15px;"><span class="glyphicon glyphicon-list-alt"></span> Navigation</h4><br>
                <li>
                    <a href="#general">General</a>
                </li>
                <li>
                    <a href="#in-out">Input/output</a>
                </li>
                <li>
                    <a href="#analyzes">Analyzes</a>
                </li>
                <li class="sidebar-nav-to-top"><a href="#top">Back to top</a></li>
            </ul>
        </div>
        <div class="col-md-9" id="tutorial-col">
            <section class="page-section">
                <h3 id="general">General</h3>
                    <?php foreach ($faq_general as $faq_key=>$faq_data): ?>
                    <div class="panel panel-default" id="<?php echo $faq_key; ?>">
                        <div class="panel-heading"><?php echo $faq_data['q'] ?></div>
                        <div class="panel-body">
                            <p class="text-justify">
                                <?php echo $faq_data['a'] ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach;?>
            </section>

            <section class="page-section">
                <h3 id="in-out">Input/Output</h3>
                <?php foreach ($faq_io as $faq_key=>$faq_data): ?>
                    <div class="panel panel-default" id="<?php echo $faq_key; ?>">
                        <div class="panel-heading"><?php echo $faq_data['q'] ?></div>
                        <div class="panel-body">
                            <p class="text-justify">
                                <?php echo $faq_data['a'] ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach;?>
            </section>

            <section class="page-section">
                <h3 id="analyzes">Analyzes</h3>
            <?php foreach ($faq_analyzes as $faq_key=>$faq_data): ?>
                <div class="panel panel-default" id="<?php echo $faq_key; ?>">
                    <div class="panel-heading"><?php echo $faq_data['q'] ?></div>
                    <div class="panel-body">
                        <p class="text-justify">
                            <?php echo $faq_data['a'] ?>
                        </p>
                    </div>
                </div>
            <?php endforeach;?>
            </section>
    	</div>
    </div>
</div>


<script type="text/javascript">
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

</script>




