<?php


$trapid_ref = $this->element("doc_paper", array("title" => "TRAPID 2.0: a web application for taxonomic and functional analysis of <em>de novo</em> transcriptomes", "authors" => "Francois Bucchini, Andrea Del Cortona, Łukasz Kreft, Alexander Botzki, Michiel Van Bel, Klaas Vandepoele", "url" => "https://doi.org/10.1093/nar/gkab565", "journal" => "Nucleic Acids Research, 01 July, 2021"));

// FAQ content is defined here, as associative arrays (one per FAQ category).
// The rest of the view parses this information to display the content.
// array structure: array("element-id"=>array("q"=>"question", "a"=>"answer"))... Element ids must be unique!

$faq_general = array(
    "max-exp"=>array(
        "q"=>"How many TRAPID experiments can I create?",
        "a"=>"Each user has the ability to create up to " . $max_user_experiments . " different TRAPID experiments."),
    "forgotten-pwd"=>array(
        "q"=>"I've lost my password. What to do next?",
        "a"=>"Go to the " . $this->Html->link("forgotten password page",array("controller"=>"trapid","action"=>"authentication","password_recovery")) . " and fill in the form with the email address used to create a TRAPID account to reset your password. If this does not work, <a href='http://bioinformatics.psb.ugent.be/trapid_02/documentation/contact'>contact us</a> and we will send you a new password for your user account. "),
    "bug-report"=>array(
        "q"=>"I think I found a bug. Can I report it?",
        "a"=>"Please do. Just <a href='http://bioinformatics.psb.ugent.be/trapid_02/documentation/contact'>send us an email</a>, and we will investigate the issue."),
    "share-exp"=>array(
        "q"=>"I want to share my experiment with my colleagues. Do I have to share my account information with them?",
        "a"=>"No, this is not necessary! If your colleagues also create a TRAPID account, you can easily share an experiment with them. This is done by following the <code>Share experiment</code> link available in the experiment header the top section of an experiment page, under settings (<span class=\"glyphicon glyphicon-cog\"></span> icon). "),
    "data-secure"=>array(
        "q"=>"Is my data secure?",
        "a"=>"Yes. We have taken extensive measures to ensure that only authorized people have access to the user data."),
    "licensing"=>array("q"=>"Is TRAPID free to use?", "a"=>"TRAPID is freely accessible for academic use exclusively. If you have a commercial interest in the platform, or would like to use TRAPID for commercial purposes, please <a href='http://bioinformatics.psb.ugent.be/trapid_02/documentation/contact'>contact Klaas Vandepoele</a>. "),
    "citation"=>array("q"=>"How can I cite TRAPID?", "a"=>"In case you publish results generated using TRAPID, please cite this paper: " . $trapid_ref . "If you cite results based on third-party resources used by TRAPID (e.g. taxonomic classification, phylogenetic trees), please cite the appropriate papers as well. More information about this can be found in " . $this->Html->link("tools & parameters", array("controller" => "documentation", "action" => "tools_parameters")) . ". ")
);

$faq_io = array(
    "input"=>array(
        "q"=>"What input files should I use?",
        "a"=>"TRAPID supports correctly formatted <a href='http://en.wikipedia.org/wiki/FASTA_format' class='linkout'>FASTA files</a>, with the <code>&gt;</code> symbol indicating the transcript identifier of the following sequence. In case the headers of the file consist of multiple sections separated by the <code>|</code> symbol (pipe), the first section will be used as unique identifier. Please note that TRAPID works with <strong>nucleotide sequences exclusively</strong>. <br><br>".
            "Several online resources are available to help users perform sequence assembly and prepare their data for use with TRAPID. More information can be found in the " .  $this->Html->link("general documentation", array("controller" => "documentation", "action" => "general", "#"=>"assembly-resources")) . ". "),
    "max-sqce"=>array(
        "q"=>"How many sequences can I process at once?",
        "a"=>"TRAPID is able to process up to 200,000 transcripts within a single experiment. Adding more transcripts is possible, but correct processing or website performance is not guaranteed in this case."),
    "import-subset"=>array(
        "q"=>"How can I upload a transcript subset file?",
        "a"=>"Within a TRAPID experiment, click <code>Import data</code> in the side menu. There, choose <code>Transcript subset</code>. You can then upload a file containing the list of transcript ids for the subset, and choose a name for the subset. Finish by clicking <code>Import subset</code>. "),
    "export-data"=>array(
        "q"=>"Can I export TRAPID's results in bulk?",
        "a"=>"Yes you can. Within a TRAPID experiment, click <code>Export data</code> to go to the export page. More information about the content and format of exported files can be found in the " . $this->Html->link("general documentation", array("controller" => "documentation", "action" => "general", "#"=>"data-export")) . ". "),
    "multi-species"=>array(
        "q"=>"Can I analyze transcripts from multiple species simultaneously?",
        "a"=>"You can. In case you do, we recommend to either make use of the taxonomic classification functionality of TRAPID (ensure this step is enabled during initial processing), or to use subsets to mark the origin of different transcripts (e.g. from species X and Y). Note that you easily can upload multiple sequence files into one experiment; see the " . $this->Html->link("general documentation", array("controller" => "documentation", "action" => "general", "#"=>"data-upload")) . " for more information. "),
);

$faq_analyses = array(
    "ref-dbs"=>array("q"=>"What reference databases are available, and which one should I use?", "a"=>"We offer four reference databases: the latest PLAZA databases (PLAZA dicots and monocots 4.5, pico-PLAZA 3), and EggNOG 4.5. In case you are working with data originating from plants or algae, we recommend using the most suited PLAZA instance (i.e. that contains genomes from closely related species). In case data from other lineages is analyzed, we recommend selecting EggNOG 4.5. An overview of the content of the various reference databases can be found in the " . $this->Html->link("tools & parameters", array("controller" => "documentation", "action" => "tools_parameters", "#"=>"ref-dbs")) .  " documentation page. "),
    "tools-parameters"=>array("q"=>"What third-party resources and tools are used by TRAPID? What software version and parameters are used?", "a"=>"All third-party resources used by TRAPID are listed in the " . $this->Html->link("tools & parameters", array("controller" => "documentation", "action" => "tools_parameters", "#"=>"ref-dbs")) . " page, along with version and parameters information. "),
    "tree-download"=>array(
        "q"=>"How can I download an alignment or phylogenetic tree?",
        "a"=>"After the multiple sequence alignment (MSA) or phylogenetic tree has been created for a given gene family, the user can download the MSA in <code>.faln</code> (fasta) format, or the phylogenetic tree in newick and phyloXML format, by following the download links provided in the <code>Files & extra</code> tab of the MSA/tree page. "),
    "tree-all"=>array(
        "q"=>"Is it possible to automatically generate all phylogenetic trees within a TRAPID experiment?",
        "a"=>"Due to the heavy computational requirements for generating multiple thousands of multiple sequence alignments and phylogenetic trees, this is currently not possible. As such, creating phylogenetic trees can only be done on a per-gene family basis. ")
);

?>

<div class="container">
    <div class="page-header">
		<h1 class="text-primary">Frequently Asked Questions</h1>
    </div>

    <div class="row">
        <div class="col-md-9" id="tutorial-col">
        <section class="page-section-sm">
            <div class="panel panel-slim panel-default" id="faq-general" role="tablist">
                <div class="panel-heading">General</div>
                <div class="list-group">
                    <?php foreach ($faq_general as $faq_key=>$faq_data): ?>
                        <a class="list-group-item" data-toggle="collapse" data-target="#<?php echo $faq_key; ?>" data-parent="#faq-general" role="button">
                            <i class="material-icons md-18 text-muted">contact_support</i> &nbsp;
                            <?php echo $faq_data['q']; ?>
                        </a>
                        <div class="collapse" id="<?php echo $faq_key; ?>">
                            <div class="list-group-item faq-answer">
                                <p class="text-justify">
                                    <?php echo $faq_data['a']; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach;?>
                </div>
            </div>
        </section>
        <section class="page-section-sm">
            <div class="panel panel-slim panel-default" id="faq-io" role="tablist">
                <div class="panel-heading">Input / output</div>
                <div class="list-group">
                    <?php foreach ($faq_io as $faq_key=>$faq_data): ?>
                        <a class="list-group-item" data-toggle="collapse" data-target="#<?php echo $faq_key; ?>" data-parent="#faq-io" role="button">
                            <i class="material-icons md-18 text-muted">contact_support</i> &nbsp;
                            <?php echo $faq_data['q']; ?>
                        </a>
                        <div class="collapse" id="<?php echo $faq_key; ?>">
                            <div class="list-group-item faq-answer">
                                <p class="text-justify">
                                <?php echo $faq_data['a']; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach;?>
                </div>
            </div>
        </section>
        <section class="page-section-sm">
            <div class="panel panel-slim panel-default" id="faq-analyses" role="tablist">
                <div class="panel-heading">Analyses</div>
                <div class="list-group">
                    <?php foreach ($faq_analyses as $faq_key=>$faq_data): ?>
                        <a class="list-group-item" data-toggle="collapse" data-target="#<?php echo $faq_key; ?>" data-parent="#faq-analyses" role="button">
                            <i class="material-icons md-18 text-muted">contact_support</i> &nbsp;
                            <?php echo $faq_data['q']; ?>
                        </a>
                        <div class="collapse" id="<?php echo $faq_key; ?>">
                            <div class="list-group-item faq-answer">
                                <p class="text-justify">
                                <?php echo $faq_data['a']; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach;?>
                </div>
            </div>
        </section>
    </div>
        <div class="col-md-3 scrollspy" id="navigation-col">
            <ul class="nav hidden-xs hidden-sm" id="sidebar-nav" data-spy="affix">
<!--                <h5 class='doc-sidebar-header'><i class="material-icons md-24">toc</i> Sections</h5>-->
                <h5 class="doc-sidebar-header">Contents</h5>
                <li>
                    <a href="#faq-general">General</a>
                </li>
                <li>
                    <a href="#faq-io">Input/output</a>
                </li>
                <li>
                    <a href="#faq-analyses">Analyses</a>
                </li>
                <li class="sidebar-nav-to-top"><a href="#top">Back to top</a></li>
            </ul>
        </div>
    </div>
</div>

<script type="text/javascript">
    // Affix navigation (bootstrap)
    $('body').attr('data-spy', 'scroll');
    $('body').attr('data-target', '.scrollspy');
    $('#sidebar-nav').affix({
        offset: {
            top: $('#sidebar-nav').offset().top - 15
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




