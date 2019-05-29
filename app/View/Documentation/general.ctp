<?php
// Create HTML for export file example (<pre> wrapped in bootstrap collapsible element) with id `$elmt_id` and
// using data from `$example_data`.
function create_export_example($elmt_id, $example_data){
    echo "<div class=\"collapse\" id=\"" . $elmt_id . "\">\n";
    if($example_data) {
        echo "<pre>" . $example_data . "</pre>\n";
    }
    else {
        echo "<pre>Error: no example data found for this type of export. </pre>\n";
    }
    echo "</div>\n";
}
?>
<div class="container">
    <div class="page-header">
        <h1 class="text-primary">TRAPID General Documentation</h1>
    </div>
    <div class="row">
        <div class="col-md-3 scrollspy" id="navigation-col">
            <ul class="nav hidden-xs hidden-sm" id="sidebar-nav" data-spy="affix">
                <h4 style="padding-top: 15px;"><span class="glyphicon glyphicon-list-alt"></span> Navigation</h4><br>
                <li><a href="#introduction">Introduction & references</a>
                    <ul class="nav">
                        <li><a href="#requirements">Software requirements</a></li>
                        <li><a href="#citations">Citations</a></li>
                        <!--li><a href="#patterns_ref">References</a></li-->
                    </ul>
                </li>
                <li><a href="#authentication">User Authentication</a></li>
                <li><a href="#create-experiments">Creating experiments</a></li>
                <li><a href="#data-upload">Transcript upload & jobs</a></li>
                <li><a href="#initial-processing">Perform transcript processing</a>
                    <ul class="nav">
                        <li><a href="#time-trapid-01">TRAPID 1 processing time</a></li>
                        <li><a href="#time-trapid-02">TRAPID 2 processing time</a></li>
                    </ul>
                </li>
                <li><a href="#basic-analyses">Basic analyses</a>
                    <ul class="nav">
                        <li><a href="#statistics">General statistics</a></li>
                        <li><a href="#labels">Subsets/labels</a></li>
                        <li><a href="#data-search">Searching for data</a></li>
                        <li><a href="#data-export">Exporting data</a></li>
                        <li><a href="#toolbox">Toolbox</a></li>
                    </ul>
                </li>
                <li><a href="#frameshift">Frameshift correction</a></li>
                <li><a href="#enrichment">Functional enrichment</a></li>
                <li><a href="#msa">Multiple sequence alignments</a></li>
                <li><a href="#tree">Phylogenetic trees</a></li>
                <li><a href="#orthology">Orthology</a></li>
            </ul>
        </div>
        <div class="col-md-9" id="tutorial-col">
            <section class="page-section" id="introduction">
                <h3>Introduction &amp; References</h3>
                <p class="text-justify">TRAPID is an online tool for the fast and efficient processing of assembled
                    RNA-Seq transcriptome data. TRAPID offers high-throughput ORF detection, frameshift correction and
                    includes a functional, comparative and phylogenetic toolbox, making use of 175 reference proteomes.
                    The TRAPID platform Is available at <a href="http://bioinformatics.psb.ugent.be/webtools/trapid"
                                                           alt="PLAZA link">http://bioinformatics.psb.ugent.be/webtools/trapid</a>
                </p>
                <p class="text-justify">
                    Detailed information about the platform and the tools are provided in the different sections. In
                    addition, we provide a detailed step-by-step tutorial
                    <a href="http://bioinformatics.psb.ugent.be/webtools/trapid/documentation/tutorial"
                       alt="Tutorial link">here</a>, to guide non-experts through the different steps of processing a
                    complete transcriptome using TRAPID. Sample data including Panicum transcrips (from Meyer et al.,
                    2012; see [1]) and subset labels can be found at <a href="ftp://ftp.psb.ugent.be/pub/trapid/"
                                                                        alt="FTP Link">ftp://ftp.psb.ugent.be/pub/trapid/</a>.
                </p>
                <h4 id="requirements">Software requirements</h4>
                <p class="text-justify">In order to use TRAPID, all you need is any modern browser with JavaScript
                    enabled. TRAPID was tested using Firefox 58 & Chrome 64, on Ubuntu and Windows. </p>

                <p class="text-justify" style="font-size:88%;">[1] Meyer E, Logan TL, Juenger TE: Transcriptome analysis
                    and gene expression atlas for Panicum hallii var. filipes, a diploid model for biofuel research.
                    Plant J 2012, 70(5):879-890.</p>
                <br/>
                <h4 id="citations">Citations</h4>
                <p class="text-justify">In case you publish results generated using TRAPID, please cite this paper:</p>

                <p class="text-justify">
                    <strong>TRAPID, an efficient online tool for the functional and comparative analysis of de novo
                        RNA-Seq transcriptomes.</strong> <br>
                    Michiel Van Bel, Sebastian Proost, Christophe Van Neste, Dieter Deforce, Yves Van de Peer and Klaas
                    Vandepoele<sup>*</sup> <br>
                    <em>Genome Biology, 14:R134, 2013</em>
                    <span class="pull-right">
        <a target="_blank" href="https://genomebiology.biomedcentral.com/articles/10.1186/gb-2013-14-12-r134">
          <span class="btn btn-primary btn-xs"><span
                      class="glyphicon glyphicon-new-window"></span> Genome Biology</span>
        </a>
      </span>
                </p>

                <p class="text-justify">In case you publish frameshift corrected sequences, MUSCLE multiple sequence
                    alignments or phylogenetic trees generated using FastTree2 or PhyML, please also cite the
                    corresponding papers:</p>

                <p class="text-justify">
                    <strong>FrameDP: sensitive peptide detection on noisy matured sequences.</strong> <br>
                    Gouzy J, Carrere S, Schiex T <br>
                    <em>Bioinformatics, 25:670-671, 2009</em>
                    <span class="pull-right">
        <a target="_blank" href="https://doi.org/10.1093/bioinformatics/btp024">
          <span class="btn btn-primary btn-xs"><span
                      class="glyphicon glyphicon-new-window"></span> Bioinformatics</span>
        </a>
      </span>
                </p>

                <p class="text-justify">
                    <strong>MUSCLE: multiple sequence alignment with high accuracy and high throughput.</strong> <br>
                    Edgar RC <br>
                    <em>Nucleic Acids Res, 32:1792-1797, 2004</em>
                    <span class="pull-right">
        <a target="_blank" href="https://doi.org/10.1093/nar/gkh340">
          <span class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-new-window"></span> NAR</span>
        </a>
      </span>
                </p>

                <p class="text-justify">
                    <strong>New algorithms and methods to estimate maximum-likelihood phylogenies: assessing the
                        performance of PhyML 3.0.</strong> <br>
                    Guindon S, Dufayard JF, Lefort V, Anisimova M, Hordijk W, Gascuel O <br>
                    <em>Syst Biol 59:307-321, 2010</em>
                    <span class="pull-right">
        <a target="_blank" href="https://doi.org/10.1093/sysbio/syq010">
          <span class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-new-window"></span> Syst. Biol.</span>
        </a>
      </span>
                </p>

                <p class="text-justify">
                    <strong>FastTree 2--approximately maximum-likelihood trees for large alignments.</strong> <br>
                    Price MN, Dehal PS, Arkin AP <br>
                    <em>PLoS One, 5:e9490, 2010</em>
                    <span class="pull-right">
        <a target="_blank" href="https://doi.org/10.1371/journal.pone.0009490">
          <span class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-new-window"></span> PLos One</span>
        </a>
      </span>
                </p>


            </section>
            <section class="page-section" id="authentication">
                <h3>User Authentication</h3>
                <p class="text-justify">Data security is a necessary concern when dealing with online platforms and
                    services. Through the use of user authentication no user has access to the data of any other user.
                    User authentication is performed through username/password combination. </p>
                <p class="text-justify">To acquire a username/password combination for the platform, select
                    the <?php echo $this->Html->link("register", array("controller" => "trapid", "action" => "authentication", "registration")); ?>
                    option when visiting the TRAPID website. After supplying a valid email-address an associated
                    password will be sent to you. Using the email-address/password combination the user gains access to
                    the user-restricted area within the TRAPID platform. </p>
                <p class="text-justify">Step-by-step instructions on how to <a href="./tutorial#intro">create an account
                        and log in</a> can be found in the tutorial.</a>
            </section>

            <section class="page-section" id="create-experiments">
                <h3>Creating TRAPID Experiments</h3>
                <p class="text-justify">The transcriptome data should be uploaded to the TRAPID platform.
                    Before doing this, it is important to note that, after authentication, the user has the ability to
                    create different experiments for different transcriptome data sets, with a maximum of 20 experiments
                    per user.
                    So analyzing different transcriptome data sets at the same time is perfectly possible. </p>
                <p class="text-justify">The most important choice to be made here is what kind of reference database the
                    user would like to use.
                    The PLAZA reference databases should be very good for transcriptome data sets from plant or green
                    algal species, while the EggNOG reference database should be used for any other species, such as
                    animals, fungi or bacteria.
                </p>
                <p class="text-justify">Step-by-step instructions on how to <a href="./tutorial#t1p1">create an
                        experiment</a> can be found in the tutorial.</a>
            </section>

            <section class="page-section" id="data-upload">
                <h3>Uploading transcript sequences and job control</h3>
                <p class="text-justify">After the creation of a TRAPID experiment, the user should upload his
                    transcriptome data to the platform. The transcriptome data should be made available as a multi-fasta
                    file before upload to the server (max. size 30MB using the File upload option).
                    In order to accommodate for the rather large file size associated with plain-text multi-fasta files,
                    the uploaded file can also be compressed using <samp>zip</samp> or <samp>gzip</samp>.
                    Fasta files, compressed or not, can also be uploaded by providing a URL to a specific transcript
                    file (e.g. hosted at FTP site, public DropBox URL, etc.); this option allows to upload bigger data
                    sets (max. size 300MB).
                    If the transcriptome data is split over several multi-fasta files, the user has the ability to
                    continue uploading data (via file upload or URLs) into his transcriptome data set before starting
                    the processing phase.
                    You will a receive an e-mail when your sequences have been successfully uploaded into the database.
                </p>
                <p class="text-justify">During all TRAPID processing steps (upload, transcript processing, running
                    frameshift correction or computing alignment/phylogenetic tree), users can check the experiment
                    status to see if their job is queued, running or in error status. In case you want to cancel or stop
                    your job, go to the Experiment Status page and modify the New status to Finished. </p>
                <p class="text-justify">Step-by-step instructions on how to <a href="./tutorial#t1p1">upload data</a>
                    can be found in the tutorial.</a>
            </section>

            <section class="page-section" id="initial-processing">
                <h3>Perform transcript processing</h3>
                <p class="text-justify">The processing phase of the TRAPID platform is the next step, and necessary
                    before any of the user custom analyses can be performed. This phase is initiated by selecting the
                    Perform Initial Processing link on an experiment page. During this step, the user should consider
                    the options carefully, as they may seriously impact the custom analyses.</p>
                <p class="text-justify">
                <ul>
                    <li>First and foremost is the choice of whether either a single species, a phylogenetic clade or the
                        gene family representatives will be used for the similarity search (i.e. Database Type). A
                        single species is a good choice if in the reference database a close relative of the
                        transcriptome species is present. If a good encompassing phylogenetic clade is available, then
                        this is also a solid choice. If none of the above, then the gene family representatives will
                        provide a good sample distribution of the gene content within each reference database.
                    </li>
                    <li>Set an E-value cutoff for the DIAMOND similarity search.</li>
                    <li>Define the Gene Family Type: for PLAZA reference databases (PLAZA 3 monocots/dicots, pico-PLAZA
                        02), this is <samp>Gene families</samp> (TribeMCL clusters) or <samp>Integrative
                            Orthology</samp> (in case a single species was selected as Database Type), for EggNOG this
                        is <samp>Gene families</samp> (EggNOG ortholog groups).
                    </li>
                    <li>Define how the functional annotation should be transferred from the family to the transcript
                        level. In general, <em>transfer based on gene family</em> is the most conservative approach
                        while
                        <em>transfer based on best hit</em> is yielding a larger number of functionally annotated
                        transcripts.
                        <!--                (47% and 51%, respectively, for the Panicum data set using clade = Monocots). -->
                        Logically, combining both methods using <em>transfer from both GF and best hit</em> yields the
                        largest fraction of annotated transcripts.
                        <!--                (54% Panicum dataset). -->
                    </li>
                    <li>Decide if the similarity search should be preceded by a <strong>taxonomic
                            classification</strong> of the transcripts (performed using kaiju against NCBI NR protein
                        database).
                        This step can be useful to flag potential contamination in a transcriptome dataset or have a
                        quick overview of the ocmposition of a complex sample (e.g. single-cell transcriptome).
                        In addition, it is possible to define transcript subsets from the taxonomic classification
                        results.
                    </li>
                </ul>
                </p>
                <p class="text-justify">After this step, the experiment will become available while the server performs
                    the initial processing of the data. Again, you will a receive an e-mail when the processing is
                    finished.</p>
                <p class="text-justify">The processing of the data is sufficiently fast for normal transcriptome data
                    sets. The fraction of very short or very long transcripts will impact the total processing time
                    during this initial phase. </p>
                <h4 id="time-trapid-01">Initial processing time with TRAPID 01</h4>
                A test data set containing 90.000 transcripts can be processed in less than 3 hours, with approximately
                28% of these transcripts assigned to gene families.
                For the Panicum data set (25392 transcripts) discussed in the manuscript, the complete processing (incl.
                upload as gzip file from public URL + transcript processing using PLAZA 2.5, clade = Monocots)
                takes around 1 hour (with 60% of transcripts assigned to gene families).
                </p>
                <h4 id="time-trapid-02">Initial processing time with TRAPID 02</h4>
                <p class="text-justify">Using the same <em>Panicum</em> dataset discussed in TRAPID 01's manuscript,
                    PLAZA 03 monocots reference database and 'Monocots' as clade, the processing time takes around 30
                    minutes (including taxonomic binning). </p>
                <p class="text-justify">Step-by-step instructions on how to <a href="./tutorial#t1p1">process
                        transcripts</a> can be found in the tutorial.</a>
            </section>

            <section class="page-section" id="basic-analyses">
                <h3>Basic analyses</h3>
                <p class="text-justify">After the initial processing of the data has been performed, several new data
                    types are available for the TRAPID experiment: gene families and functional annotation (GO
                    categories or protein domains). Using these extra data types offers exciting new analyses to the
                    user.</p>
                <p class="text-justify">Step-by-step instructions on how to <a href="./tutorial#t1p2">browse these
                        statistics</a> can be found in the tutorial.</a>
                <h4 id="statistics">General statistics</h4>
                <div class="row">
                    <div class="col-md-4">
                        <a href="/webtools/trapid/img/documentation/001_stats.png">
                            <img src="/webtools/trapid/img/documentation/001_stats.png" alt="General statistics"
                                 style="max-width: 85%;" class="img-rounded img-responsive img-centered"/>
                        </a>
                        <br>
                        <p class="text-justify"><strong>Figure 1:</strong> general statistics example.</p>
                    </div>
                    <div class="col-md-8">

                        <p class="text-justify">
                            The general statistics page offers a complete overview of ORF finding, gene family
                            assignments, similarity search species information, meta-annotation and functional
                            information.
                        </p>
                    </div>
                </div>


                <h4 class="clear" id="labels">Subsets and labels</h4>
                <p class="text-justify">If the data set is comprised of transcriptome data from different sources (with
                    sources indicating different tissues, developmental types or stress conditions),
                    then the user has the ability to assign labels to the subsets.
                    This is done through the <em>Import data > Transcript subsets/labels</em> link on the experiment
                    side menu and providing per label a list of transcript identifiers.
                    Note that it is possible to assign multiple labels to one transcript. </p>
                <p class="text-justify">By assigning labels to transcripts, several new analyses become available, such
                    as comparison of functional annotation between different subsets, or by computing functional
                    enrichment. </p>
                <p class="text-justify">Step-by-step instructions on how to <a href="./tutorial#t1p4">use subsets and
                        labels</a> can be found in the tutorial.</a>
                <h4 id="data-search">Searching for data</h4>
                <p class="text-justify">The user has the ability to search for a number of possible data types within
                    his selected experiment, through the search bar in the header bar of the experiment.
                    Functional annotation can be searched for both through direct term identifiers (e.g. GO:0005509) or
                    through the descriptions (e.g. Calcium ion binding).
                    Similarly, gene families can be searched using TRAPID's identifier (with experiment ID prefix) or
                    the original gene family identifier. </p>
                <h4 id="data-export">Exporting data</h4>
                <p class="text-justify">The TRAPID platform allows the export of both the original data and the
                    annotated and processed data of a user experiment.
                    This data access is available under the <em>Export data</em> header on an experiment page and
                    includes structural ORF information, transcript/ORF/protein sequences, taxonomic classification,
                    gene/RNA family information, and functional GO/InterPro information.</p>
                <p class="text-justify">The remainder of this section consists of a description of each type of export file, organized by category, complemented by minimal examples (ten first records).
                Please click on the <span class='label label-primary'>Toggle example</span> links to show the corresponding minimal example export file. </p>

                <section class="page-section-sm">
                    <h5>Structural data</h5>
                    <p class="text-justify">
                        The structural data export file is a tab-delimited file providing the following information for each sequence of an experiment:
                    <ul>
                        <li><code>Transcript identifier</code>: the transcript sequence identifier. </li>
                        <li><code>Frame information</code>: the detected frame, strand, and full frame information (homology support) for the inferred ORF sequence of the transcript.</li>
                        <li><code>Frameshift information</code>: flag putative frameshift and potential frameshift correction (0/1 boolean values). </li>
                        <li><code>ORF information</code>: the start/stop coordinates of the inferred ORF sequence and the presence of start/stop codons. </li>
                        <li><code>Meta annotation </code>: the meta-annotation complemented by meta-annotation scoring information. </li>
                    </ul>
                    Users can choose to export any combination of the above information.
                    <span class="pull-right"><a class='label label-primary' data-toggle="collapse" data-target="#collapse-structural" aria-expanded="false" aria-controls="collapse-structural">Toggle example</a></span>
                    </p>
                    <?php create_export_example("collapse-structural", $export_examples['structural']); ?>
                </section>

                <section class="page-section-sm">
                    <h5>Taxonomic classification</h5>
                    <p class="text-justify">
                        The taxonomic classification export file is a tab-delimited file that provides, for each transcript of an experiment, their associated taxonomic label (NCBI tax ID of the lowest common ancestor, set to 0 if a transcript was not classified).
                        In case a transcript was classified, classification metrics (score, number of matching tax IDs, number of matching sequences) and full taxonomic lineage are also provided.
                        The classification score corresponds to the length of the best MEM sequence found by Kaiju.
                    <span class="pull-right"><a class='label label-primary' data-toggle="collapse" data-target="#collapse-tax-class" aria-expanded="false" aria-controls="collapse-tax-class">Toggle example</a></span>
                    </p>
                    <?php create_export_example("collapse-tax-class", $export_examples['tax_class']); ?>
                </section>

                <section class="page-section-sm">
                    <h5>Gene family data</h5>
                    <p class="text-justify">Three types of gene family data export files are available:
                        <ol>
                            <li>
                                <code>Transcripts with GF</code>: a tab-delimited file that contains the transcripts of an experiment and their associated gene family (if any).
                                <span class="pull-right"><a class='label label-primary' data-toggle="collapse" data-target="#collapse-trs-gf" aria-expanded="false" aria-controls="collapse-trs-gf">Toggle example</a></span>
                            </li>
                            <li>
                                <code>GF with transcripts</code>: a tab-delimited file that contains, for each gene family of an experiment, the number and identifiers of transcripts assigned to the gene family (on a single line).
                                <span class="pull-right"><a class='label label-primary' data-toggle="collapse" data-target="#collapse-gf-trs" aria-expanded="false" aria-controls="collapse-gf-trs">Toggle example</a></span>
                            </li>
                            <li>
                                <code>GF reference data</code>: a tab-delimited file that contains the reference data (GF name and members from the reference database) for each gene family of an experiment.
                                <span class="pull-right"><a class='label label-primary' data-toggle="collapse" data-target="#collapse-gf-ref" aria-expanded="false" aria-controls="collapse-gf-ref">Toggle example</a></span>
                            </li>
                        </ol>
                    </p>
                    <?php create_export_example("collapse-trs-gf", $export_examples['trs_gf']); ?>
                    <?php create_export_example("collapse-gf-trs", $export_examples['gf_trs']); ?>
                    <?php create_export_example("collapse-gf-ref", $export_examples['gf_ref']); ?>

                </section>

                <section class="page-section-sm">
                    <h5>RNA family data</h5>
                    <p class="text-justify">The export files for RNA family data are identical to the gene family data export files (but containing RNA family information).
                    However, no export file for reference information of RNA families is available, as this data is not stored anywhere within TRAPID.
                        Please visit the <a href="http://rfam.xfam.org/" target="_blank" class="linkout">RFAM website</a> to retrieve this information.  </p>
                </section>

                <section class="page-section-sm">
                    <h5>Sequences</h5>
                    <p class="text-justify">
                        Sequence export files are FASTA files for a chosen type of sequence and a selection of transcript sequences from an experiment.
                        Exported sequences can either be the uploaded transcript sequences, the inferred ORF sequences, or aminoacid (translated ORF) sequences.
                        It is possible to export sequences for all the transcripts of an experiment (default) or for any defined transcript subset.
                        <span class="pull-right"><a class='label label-primary' data-toggle="collapse" data-target="#collapse-sequences" aria-expanded="false" aria-controls="collapse-sequences">Toggle example</a></span>
                    </p>
                    <?php create_export_example("collapse-sequences", $export_examples['sequences']); ?>
                </section>

                <section class="page-section-sm">
                    <h5>Functional data</h5>
                    <p class="text-justify">For each type of available functional annotation data (GO terms, protein domains, KO terms), two types of export files are available:
                        <ol>
                            <li>
                                <code>Transcripts with functional annotation</code>: a tab-delimited file that contains the transcripts of an experiment and their associated functional annotation labels (identifiers and descriptions).
                                <span class="pull-right"><a class='label label-primary' data-toggle="collapse" data-target="#collapse-funct-data" aria-expanded="false" aria-controls="collapse-funct-data">Toggle example</a></span>
                            </li>
                            <li>
                                <code>Functional annotation metadata</code>: a tab-delimited file that contains, for each of every functional annotation label (identifier and description), the number and identifiers of associated transcripts (on a single line).
                                <span class="pull-right"><a class='label label-primary' data-toggle="collapse" data-target="#collapse-funct-metadata" aria-expanded="false" aria-controls="collapse-funct-metadata">Toggle example</a></span>
                            </li>
                        </ol>
                    </p>
                    <p class="text-justify"><strong>Note: </strong>the export of GO functional information has extra columns. The <code>is_hidden</code> column
                        indicates whether a GO term is flagged as hidden, due to the presence of more informative GO codes in the GO graph for the given transcript,
                        while the <code>evidence_code</code> column (value set to <code>ISS</code>) indicates that the GO annotation was assigned to the transcript via sequence similarity search.
                    </p>
                    <?php create_export_example("collapse-funct-data", $export_examples['funct_data']); ?>
                    <?php create_export_example("collapse-funct-metadata", $export_examples['funct_metadata']); ?>


                </section>
                <section class="page-section-sm">
                    <h5>Subsets</h5>
                    <p class="text-justify">
                        Subset export files enable the retrieval of the list of sequences that are part of a given transcript subset.
                        They simply consist in a list of sequence identifiers (one identifier per line).
                        <span class="pull-right"><a class='label label-primary' data-toggle="collapse" data-target="#collapse-subsets" aria-expanded="false" aria-controls="collapse-subsets">Toggle example</a></span>
                    </p>
                    <?php create_export_example("collapse-subsets", $export_examples['subset']); ?>
                </section>

                <hr>

                <h4 id="toolbox">The toolbox</h4>
                <p class="text-justify">On most pages (experiment/transcript/gene family/GO/protein domain) a toolbox is
                    available which contains the most common analyses to be performed on the given data object. </p>

            </section>

            <section class="page-section">
                <h3 id="frameshift">Frameshift correction</h3>
                <p class="text-justify text-danger"><strong>This feature is currently disabled.</strong></p>
                <p class="text-justify">For transcripts that were flagged as potentially containing frameshifts, the
                    user can execute FrameDP to putatively correct the transcript sequence and identify the correct ORF.
                    FrameDP is a program which uses BLAST together with machine learning methods to build models which
                    are used to test whether a sequence has a putative frameshift or not. The model is then used to
                    correct the sequence (by inserting N-nucleotides at the necessary locations), which of course also
                    directly has an impact on the associated Open Reading Frame (ORF). The drawback is however the
                    exceptional long processing time. As such, the correction of frameshifts can only be done per gene
                    family, and not on an entire transcript experimement.</p>
                <p class="text-justify">The putative frameshifts are first detected during the "initial processing"
                    phase, using a simple algorithm. The user has the ability to, on a gene family page, select these
                    transcripts for FrameDP processing. If the total number of selected transcripts is lower than 20,
                    additional random transcripts are added in order to have a good background model. Subsequently, all
                    sequences are used for training and 'correction'.</p>
                <p class="text-justify">Step-by-step instructions on how to <a href="./tutorial#tutorial2">correct
                        frameshifts using FrameDP</a> can be found in the tutorial.</a>
            </section>

            <section class="page-section">
                <h3 id="enrichment">Functional enrichment analysis</h3>
                <p class="text-justify">Apart from the functional annotation of individual transcripts, TRAPID also
                    supports the quantitative analysis of experiment subsets using GO and protein domain enrichment
                    statistics. Through the association of specific labels to (sub-)sets of sequences, transcripts can
                    be annotated with specific sample information (e.g. tissue, developmental stage, control or
                    treatment condition) and be used to perform within-transcriptome functional analysis. </p>
                <p class="text-justify">Specific comparative analyses than can be performed using subsets are:
                <ul>
                    <li>GO enrichment (subset versus all; hypergeometric distribution): bar chart, table and enrichment
                        GO graph output
                    </li>
                    <li class="clear">GO ratios (table) calculates GO frequencies between subsets. Also includes
                        subset-specific GO annotations
                    </li>
                    <li>GO ratios (table) calculates GO frequencies between subsets. Also includes subset-specific GO
                        annotations
                    </li>
                    <li>Protein domain enrichment (subset versus all; hypergeometric distribution)</li>
                    <li>Protein domain ratios between subsets</li>
                    <li>Different subsets - Venn diagrams</li>
                </ul>
                <br>
                <div class="row">
                    <div class="col-md-4">
                        <a target="_blank" href="/webtools/trapid/img/documentation/002_go_enrichment.png"><img
                                    src="/webtools/trapid/img/documentation/002_go_enrichment.png" alt="Go enrichment"
                                    style="max-width: 85%;"
                                    class="img-rounded img-responsive img-centered"/></a><br><strong>Figure 2:</strong>
                        GO enrichment.
                    </div>
                    <div class="col-md-4">
                        <a target="_blank" href="/webtools/trapid/img/documentation/003_go_graph.png"><img
                                    src="/webtools/trapid/img/documentation/003_go_graph.png" alt="Go graph"
                                    style="max-width: 85%;" class="img-rounded img-responsive img-centered"/></a>
                        <br><strong>Figure 3:</strong> GO graph.
                    </div>
                    <div class="col-md-4">
                        <a target="_blank" href="/webtools/trapid/img/documentation/004_go_ratios.png"><img
                                    src="/webtools/trapid/img/documentation/004_go_ratios.png" alt="Go ratios"
                                    style="max-width: 85%;" class="img-rounded img-responsive img-centered"/></a>
                        <br><strong>Figure 4:</strong> GO ratios.
                    </div>
                </div>
            </section>

            <section class="page-section">
                <h3 class="clear" id="msa">Multiple sequence alignment</h3>
                <div class="row">
                    <div class="col-md-4">
                        <a href="/webtools/trapid/img/documentation/005_jalview.png"><img
                                    src="/webtools/trapid/img/documentation/005_jalview.png" alt="Jalview"
                                    style="max-width: 85%;" class="img-rounded img-responsive img-centered"/></a>
                        <p class="text-justify"><strong>Figure 5:</strong> example multiple sequence alignment. <em>Panicum</em>
                            data set, transcript contig16311 in family HOM000957 covering 117 genes from 25 species.</p>
                    </div>
                    <div class="col-md-8">
                        <p class="text-justify">Starting from a selected transcript, the user has the ability to create
                            an amino acid multiple sequence alignment (MSA) within a gene family context. As such, the
                            user can create an MSA containing the transcripts within a gene family together with a
                            selection of coding sequences from the reference database. This tool is accessible from the
                            toolbox from a gene family. The MSA is created using MUSCLE (<a
                                    href="http://www.ebi.ac.uk/Tools/msa/muscle/">http://www.ebi.ac.uk/Tools/msa/muscle/</a>),
                            a tool which delivers a good balance between speed and accuracy (Edgar 2004 Nucleic Acids
                            Res. 2004; 32(5): 1792-1797). In order to reduce the computation time, the maximum number of
                            iterations in the MUSCLE algorithm is fixed at three. All other settings are left at
                            default.</p>
                        <p class="text-justify">After the MSA has been created, the user has the ability to view this
                            alignment using JalView, or to download the MSA and investigate it using a different
                            tool.</p>
                    </div>
                </div>
            </section>

            <section class="page-section">
                <h3 class="clear" id="tree">Phylogenetic trees</h3>

                <div class="row">
                    <div class="col-md-4">
                        <a href="/webtools/trapid/img/documentation/006_tree.png">
                            <img src="/webtools/trapid/img/documentation/006_tree.png" alt="phylo tree"
                                 style="max-width: 85%;" class="img-rounded img-responsive img-centered"/>
                        </a>
                        <p class="text-justify"><strong>Figure 5:</strong> example FastTree phylogenetic tree (<em>Panicum</em>
                            data set, transcript contig16311 in family HOM000957 covering 117 genes from 25 species,
                            relaxed editing). The query transcript is shown in grey while homologs from the reference
                            proteomes are shown in colors based on their taxonomic information. Meta-annotations are
                            displayed as colored boxes next to the gene identifiers. Only a part of the complete tree is
                            depicted in the image below.</p>
                    </div>
                    <div class="col-md-8">
                        <p class="text-justify">The user has, similar to the multiple sequence alignment, also the
                            ability to create a phylogenetic tree within a gene family context (see previous section).
                            In order to create a phylogenetic tree, the system needs this MSA for the tree building
                            algorithm. This step, however, is done automatically by the TRAPID system in case no
                            previous MSA is present for the indicated gene family.</p>
                        <p class="text-justify">In order to create phylogenetic trees which are less dependent on
                            putative large gaps, the standard MSA is transformed to a <em>stripped</em> MSA. In this
                            stripped MSA the alignment length is reduced by removing all positions (for every
                            gene/transcript sequence) for which a certain fraction (0.10 for stringent editing, 0.25 for
                            relaxed editing) is a gap. As such, all gaps introduced by a small number of sequences will
                            be removed. </p>
                        <p class="text-justify">In case the stringent editing yields a stripped MSA with zero or only a
                            few conserved alignment positions, please re-run the tree analysis using the relaxed editing
                            option, which will yield more conserved alignment positions.</p>
                        <p class="text-justify">In the TRAPID system we offer two different tree inference algorithms:
                            FastTree (<a href="http://meta.microbesonline.org/fasttree/">http://meta.microbesonline.org/fasttree/</a>)
                            and PhyMl (<a href="http://www.atgc-montpellier.fr/phyml/">http://www.atgc-montpellier.fr/phyml/</a>),
                            with the first one being the default due to its very fast processing speed, coupled with
                            equal or better fidelity (Price et al., 2010 PLoS One Mar 10;5(3):e9490). The user here has
                            the ability to choose which algorithm to use, and -if desired- how many bootstrap runs will
                            be applied. For FastTree we used the following non-default settings: <span
                                    style='font-size:x-small'> '--wag gamma'</span>, which indicate that the algorithm
                            uses the WAG+CAT model, and rescales the branch lengths. For PhyML we used the following
                            non-default settings: <span style='font-size:x-small'>'-m WAG -f e -c 4 -a e'</span>, which
                            indicate that the algorithm uses the WAG+CAT model, that empirical amino acid frequencies
                            are used, that 4 relative substitution rate categories are used, and that the parameter for
                            the gamma distribution shape is based on the maximum likelihood estimate.</p>
                        <p class="text-justify">Finally, if the user has defined subsets within his/her experiment,
                            these subsets can also be displayed on the phylogenetic tree, making subsequent analyses
                            much easier. By default, the meta-annotation is displayed as domains next to the
                            phylogenetic tree (see example below). </p>
                    </div>
                </div>
                <p class="text-justify">The default way to create a phylogenetic tree is:
                <ol>
                    <li>Login into the TRAPID platform, and select the desired experiment</li>
                    <li>Select the transcript, either through the search function, or through any link in the platform
                    </li>
                    <li>On the transcript page, select the associated gene family</li>
                    <li>On the gene family page, select the <em>create phylogenetic tree</em> from the toolbox</li>
                    <li>Select the reference species from the reference database you want to include in the tree</li>
                </ol>
                <p class="text-justify">Step-by-step instructions on how to <a href="./tutorial#t1p3">construct
                        phylogenetic trees</a> can be found in the tutorial.</a>
                </p>

            </section>

            <section class="page-section">
                <h3 id="orthology">Orthology</h3>
                <p class="text-justify">A key challenge in comparative genomics is reliably grouping homologous genes
                    (derived from a common ancestor) and orthologous genes (homologs separated by a speciation event)
                    into gene families. Orthology is generally considered a good proxy to identify genes performing a
                    similar function in different species. Consequently, orthologs are frequently used as a means to
                    transfer functional information from well-studied model systems to non-model organisms, for which
                    e.g. only RNA-Seq-based gene catalogs are available. In eukaryotes, the utilization of orthology is
                    not trivial, due to a wealth of paralogs (homologous genes created through a duplication event) in
                    almost all lineages. Ancient duplication events preceding speciation led to outparalogs, which are
                    frequently considered as subtypes within large gene families. In contrast to these are inparalogs,
                    genes that originated through duplication events occurring after a speciation event. Besides
                    continuous duplication events (for instance, via tandem duplication), many paralogs are remnants of
                    different whole genome duplications (WGDs), resulting in the establishment of one-to-many and
                    many-to-many orthologs (or co-orthologs).</p>
                <p class="text-justify">Within TRAPID, the phylogenetic trees provide the most detailed approach to
                    identify orthology relationships. For a given transcript, inspecting the phylogenetic tree can
                    reveal whether orthologs exist in related species and if this relationship is a one-to-one,
                    one-to-many or many-to-many orthology. Apart from the trees, also the <em>Browse similarity search
                        output</em> tool, available from a transcript page, offers an overview of homologous genes in
                    related species. Below, we show two examples demonstrating how orthologous groups and simple/complex
                    orthology relationships can be derived from a phylogenetic tree generated using TRAPID.</p>
                <div class="row">
                    <div class="col-md-6">
                        <!-- Extremely hackish -->
                        <div class="hidden-md hidden-sm hidden-xs" style="height:50px;"></div>
                        <div class="hidden-sm hidden-xs" style="height:100px;"></div>
                        <a href="/webtools/trapid/img/documentation/007_tree.png"><img
                                    src="/webtools/trapid/img/documentation/007_tree.png" alt="phylo tree"
                                    style="max-width: 85%;" class="img-rounded img-responsive img-centered"/></a>
                        <p class="text-justify"><strong>Figure 7:</strong> example one-to-one orthology
                            (<em>Panicum</em> data set, transcript contig14762 RecQ helicase). The node indicated in red
                            shows the monocot homologs for this clade (1 indicates 100% bootstrap support). Within this
                            sub-tree, from each included monocot species a single gene is present, revealing simple
                            one-to-one orthology relationships.</p>
                    </div>
                    <div class="col-md-6">
                        <a href="/webtools/trapid/img/documentation/008_tree.png"><img
                                    src="/webtools/trapid/img/documentation/008_tree.png" alt="phylo tree"
                                    style="max-width: 85%;" class="img-rounded img-responsive img-centered"/></a>
                        <p class="text-justify"><strong>Figure 8:</strong> example one-to-many orthology
                            (<em>Panicum</em> data set, transcript contig00984 ATPase). The node indicated in red shows
                            the monocot homologs for this clade (1 indicates 100% bootstrap support). Within this
                            sub-tree, 2 genes from <i>Zea mays</i> are present, revealing that for the single Panicum
                            transcript two co-orthologs exist in <i>Z. mays</i>.</p>
                    </div>
                </div>
            </section>
        </div> <!-- End column -->
        </div> <!-- End row -->
    </div>

<script>
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