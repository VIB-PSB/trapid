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

// Print correctly-formatted information for a paper
function print_paper_element($title, $authors, $url, $journal) {
    $citation_html =  "<blockquote style='font-size: 100%;'>
<p class=\"text-justify\">
        <strong>{title}</strong><br>
        {authors}<br>
        <a title='View article in a new tab' target=\"_blank\" href=\"{url}\">
        <span class=\"glyphicon glyphicon-link\"></span></a>
        <em>{journal}</em>
    </p>
</blockquote>";
    echo str_replace(['{title}', '{authors}', '{url}', '{journal}'], [$title, $authors, $url, $journal], $citation_html);
}
?>

<div class="container">

    <div class="page-header">
        <h1 class="text-primary">TRAPID general documentation</h1>
    </div>

    <div class="row">
        <div class="col-md-9" id="tutorial-col">
            <section class="page-section" id="introduction">
                <h3>Introduction &amp; references</h3>
                <section class="page-section-sm">
                    <p class="text-justify">TRAPID is an online tool for the fast and efficient processing of assembled
                        RNA-Seq (meta)transcriptome data. TRAPID offers high-throughput annotation, ORF prediction, frameshift detection and
                        includes a functional, comparative and phylogenetic toolbox, making use of reference proteomes from 319 eukaryotes, 1,678 bacteria, and 115 archaea.
                        The TRAPID platform is available at: <a href="http://bioinformatics.psb.ugent.be/trapid_02" alt="TRAPID link">http://bioinformatics.psb.ugent.be/trapid_02</a>.
                    </p>

                    <p class="text-justify">Detailed information about the platform and its capabilities are provided in the different sections of this page. In
                        addition, we provide step-by-step tutorials
                        <?php echo $this->Html->link("here", array("controller" => "documentation", "action" => "tutorial")); ?>, to guide non-experts through the different steps of processing a
                        complete transcriptome using TRAPID.</p>
                    <p class="text-justify">Sample data, including a <em>Panicum</em> transcriptome (from <a href="https://onlinelibrary.wiley.com/doi/abs/10.1111/j.1365-313X.2012.04938.x" target="_blank" class="linkout">Meyer et al., 2012</a>) and several microbial eukaryote transcriptomes from the MMETSP (<a href="https://dx.doi.org/10.1371%2Fjournal.pbio.1001889" target="_blank" class="linkout">Keeling et al., 2014</a>) can be found on <a href="ftp://ftp.psb.ugent.be/pub/trapid" target="_blank" class="linkout">TRAPID's public FTP</a>. </p>
                </section>
                <section class="page-section-sm">
                <h4 id="requirements">Software requirements</h4>
                <p class="text-justify">In order to use TRAPID, all you need is any modern browser with JavaScript
                    enabled. TRAPID was tested using Firefox 76 & Chrome 81, on Ubuntu and Windows. </p>
                </section>
                <section class="page-section-sm">
                    <h4 id="citations">Citations</h4>
                    <p class="text-justify">In case you publish results generated using TRAPID, please cite this paper:</p>

                    <?php print_paper_element("TRAPID, an efficient online tool for the functional and comparative analysis of de novo
                            RNA-Seq transcriptomes", "Van Bel, M., Proost, S., Van Neste, C., Deforce, D., Van de Peer, Y., & Vandepoele, K.<sup>*</sup>", "https://genomebiology.biomedcentral.com/articles/10.1186/gb-2013-14-12-r134", "Genome biology 14, no. 12 (2013): R134"); ?>

                    <p class="text-justify">In case you publish taxonomic classification results, please also cite Kaiju:</p>
                    <?php print_paper_element("Fast and sensitive taxonomic classification for metagenomics with Kaiju", "Menzel, P., Ng, K. L., & Krogh, A.", "https://www.nature.com/articles/ncomms11257", "Nature communications 7.1 (2016)"); ?>


                    <p class="text-justify">In case you publish multiple sequence alignments or phylogenetic trees, please also cite the
                        corresponding papers:</p>
                    <?php print_paper_element("MAFFT Multiple Sequence Alignment Software Version 7: Improvements in Performance and Usability", "Katoh, K., & Standley, D. M.", "https://academic.oup.com/mbe/article/30/4/772/1073398", " Molecular biology and evolution 30.4 (2013): 772-780."); ?>
                    <?php print_paper_element("MUSCLE: multiple sequence alignment with high accuracy and high throughput", "Edgar, R. C.", "https://academic.oup.com/nar/article/32/5/1792/2380623", "Nucleic acids research 32.5 (2004): 1792-1797"); ?>
                    <?php print_paper_element("FastTree 2–approximately maximum-likelihood trees for large alignments", "Price, M. N., Dehal, P. S., & Arkin, A. P.", "https://journals.plos.org/plosone/article?id=10.1371/journal.pone.0009490", "PloS one 5, no. 3 (2010)"); ?>
                    <?php print_paper_element("IQ-TREE: a fast and effective stochastic algorithm for estimating maximum-likelihood phylogenies", "Nguyen, L. T., Schmidt, H. A., Von Haeseler, A., & Minh, B. Q.", "https://academic.oup.com/mbe/article/32/1/268/2925592", "Molecular biology and evolution 32.1 (2015): 268-274"); ?>
                    <?php print_paper_element("New Algorithms and Methods to Estimate Maximum-Likelihood Phylogenies: Assessing the Performance of PhyML 3.0", "Guindon, S., Dufayard, J. F., Lefort, V., Anisimova, M., Hordijk, W., & Gascuel, O.", "https://academic.oup.com/sysbio/article-abstract/59/3/307/1702850", "Systematic biology 59.3 (2010): 307-321."); ?>
                    <?php print_paper_element(" RAxML version 8: a tool for phylogenetic analysis and post-analysis of large phylogenies ", "Stamatakis, A.", "https://academic.oup.com/bioinformatics/article/30/9/1312/238053", "Bioinformatics 30.9 (2014): 1312-1313."); ?>
                </section>
            </section>
            <section class="page-section" id="authentication">
                <h3>User authentication</h3>
                <p class="text-justify">Data security is a necessary concern when dealing with online platforms and
                    services. TRAPID requires users to be registered, and no user can access the data of any other user, except if requested explicitely (shared experiment).
                    User authentication is performed through username/password combination. </p>
                <p class="text-justify">To create a TRAPID account, click on
                    the <?php echo $this->Html->link("register", array("controller" => "trapid", "action" => "authentication", "registration")); ?>
                    button of the header of TRAPID's website. After filling in your information and registering, a password will be sent to you. Using the email address/password combination, the user gains access to the user-restricted area within the TRAPID platform. We recommend user to change their password after logging in for the first time (<code>Account</code> > <code>Change password</code>). </p>
                <p class="text-justify">Step-by-step instructions on how to create an account
                        and log in can be found in the                             <?php echo $this->Html->link("tutorial", array("controller" => "documentation", "action" => "tutorial", "#"=>"account-setup")); ?>.
            </section>

            <section class="page-section" id="create-experiments">
                <h3>Creating TRAPID experiments</h3>
                <p class="text-justify">In order to be processed, the transcriptome data must be first uploaded to TRAPID.
                    Before doing this, it is important to note that a user has the ability to
                    create different experiments for different transcriptome data sets, with a <strong>maximum of 20 experiments</strong> per user.
                    So analyzing different transcriptome data sets at the same time is perfectly possible. </p>
                <p class="text-justify">When creating an experiment, the most important setting to adjust is the reference database to use. The PLAZA reference databases should be very good for transcriptome data sets from plant or green algal species, while the EggNOG reference database should be used for any other species, such as animals, fungi or bacteria.
                </p>
                <p class="text-justify"> An overview of the content of the various reference databases can be found in the <?php echo $this->Html->link("tools & parameters", array("controller" => "documentation", "action" => "tools_parameters", "#"=>"ref-dbs")); ?> documentation page, and step-by-step instructions on how to create an experiment
                    can be found in the <?php echo $this->Html->link("tutorial", array("controller" => "documentation", "action" => "tutorial", "#"=>"tutorial-1-upload")); ?>. </p>
            </section>

            <section class="page-section" id="data-upload">
                <h3>Uploading transcript sequences and job control</h3>
                <p class="text-justify">After a TRAPID experiment was created, the user can upload his
                    transcriptome data to the platform. The transcriptome data must be made available in <a href='http://en.wikipedia.org/wiki/FASTA_format' class='linkout'>FASTA format</a>.
                    The maximum allowed size for an input file is <strong>32MB</strong>. However, to accommodate for the rather large file size associated with plain-text multi-fasta files,
                    the uploaded file can also be compressed (<code>.zip</code> or <code>.gz</code>).
                    Input files may also be uploaded by providing a URL to a specific transcript
                    file (e.g. FTP site, cloud, public Dropbox URL, etc.); this option allows to upload larger files (max. size 300MB).
                    Please note that this option requires to provide a <strong>direct link</strong> to the data.
                    If the transcriptome data is split over several files, the user has the ability to
                    continue uploading data (via file upload or URLs) into his experiment before starting
                    the processing phase.
                    Once your sequences have been successfully uploaded into the database, an e-mail will be sent.
                </p>
                <p class="text-justify">At any moment and during all processing steps (upload, initial processing, running downstream
                    analyses from the web application), users can check the experiment's jobs or log, both accessible via links from the experiments overview page (outside an experiment) or the header (within an experiment).
                    <ul>
                      <li>The job management page enables users to see if the status of their jobs, and to delete them if needed. </li>
                     <li>The experiment log stores detailed information about all the experiment's computation steps, used tools and parameters. It can be exported to flat file.</li>
                    </ul>
                In addition, while an experiment's status is <code>processing</code> or <code>error</code>, you can go to the experiment Status page and modify the status (e.g. to <code>finished</code>). </p>
                <p class="text-justify">Step-by-step instructions on how to upload data can be found in the
                    <?php echo $this->Html->link("tutorial", array("controller" => "documentation", "action" => "tutorial", "#"=>"tutorial-1-upload")); ?>.</p>
            </section>

            <section class="page-section" id="initial-processing">
                <h3>Performing transcript processing</h3>
                <p class="text-justify">The initial processing phase of the TRAPID platform is the next step, and necessary
                    before any of the user downstream analyses can be performed. This phase is initiated by clicking the <code>Process transcripts</code>
                    button on an experiment overview page. For this step, the user should consider
                    the options carefully, as they may seriously impact the downstream analyses.</p>

                <p class="text-justify">Step-by-step instructions on how to process
                        transcripts can be found in the <?php echo $this->Html->link("tutorial", array("controller" => "documentation", "action" => "tutorial", "#"=>"tutorial-1-upload")); ?>.
                <section class="page-section-sm" id="initial-processing-settings">
                    <h4>Initial processing options</h4>
                    <p class="text-justify">
                    <ul>
                        <li>
                            <strong>Similarity search options</strong>
                        <ul>
                            <li><code>Similarity search database</code>: whether either a single species, or a phylogenetic clade will be used for the similarity search. A
                                single species is a good choice if in the reference database a close relative of the
                                transcriptome species is present. If a good encompassing phylogenetic clade is available, then
                                this is also a solid choice. By default, this is se to the most general clade of the reference database. Note that if eggNOG 4.5 is used as reference database, it is not possible to choose a single species as similarity search atabase, due to the very high number of represented species.
                            </li>
                            <li><code>Maximum E-value threshold</code>: the maximum E-value cutoff to use for the DIAMOND similarity search.</li>
                        </ul>
                        </li>
                        <li>
                            <strong>
                                RNA annotation options
                            </strong>
                            <ul>
                                <li><code>RFAM clans</code>: TRAPID identifies potential non-coding RNAs within the input data, using Infernal against a selection of RFAM RNA models. This option sets the <a href="https://rfam.xfam.org/clans" target="_blank" class="linkout">RFAM clans</a> to search for. By default, a collection of models corresponding to ubiquitous non-coding RNAs is used.</li>
                            </ul>
                        </li>
                        <li>
                            <strong>
                                Gene families and annotation options
                            </strong>
                            <ul>
                                <li><code>Gene Family Type</code>: for PLAZA reference databases (PLAZA 4.5 monocots/dicots, pico-PLAZA
                                    3), this is <samp>Gene families</samp> (TribeMCL clusters) or <samp>Integrative
                                        Orthology</samp> (OrthoMCL/OrthoFinder clusters). <samp>Integrative
                                        Orthology</samp> may be selected only in case a single species was selected as Database Type. For EggNOG, only <samp>Gene families</samp> can be selected (which correspond to EggNOG ortholog groups).
                                </li>
                                <li><code>Functional annotation</code> (only for PLAZA reference databases): the strategy used for functional annotation to be transferred from gene family to transcript. In general, <samp>Gene families</samp> is the most conservative approach
                                    while <samp>Best hit</samp> is yielding a larger number of functionally annotated
                                    transcripts. We recommend combining both methods and use <samp>Both</samp>, as it yields the
                                    largest fraction of annotated transcripts.
                                </li>
                                <li><code>Taxonomic scope</code> (only for EggNOG): define EggNOG mapper's taxonomic level used for annotation. We recommend keeping the default value, <samp>Adjust automatically</samp>.
                                </li>
                            </ul>
                        </li>
                        <li>
                            <strong>
                                Extra options
                            </strong>
                            <ul>
                                <li><code>Perform taxonomic classification</code> if checked, the similarity search is preceded by a <strong>taxonomic
                                        classification</strong> of the transcripts, performed using Kaiju against the NCBI NR protein
                                    database.The main purpose of this step is to enable the identification of potential contaminant sequences within single-species transcriptomes or to facilitate the analysis of transcriptome data from communities (or single-cell transcriptomes).
                                    In addition, it is possible to define transcript subsets from the taxonomic classification
                                    results, for quick sequence extraction or downstream analyses.
                                </li>
                            <li><code>Input sequences are CDS</code>: this options should be checked if input sequences are CDS (nucleotides). The ORF prediction step of the initial processing will be skipped and all sequences will be translated in <samp>+1</samp> frame. </li>
                                <li><code>Genetic code</code>: the genetic code to use for ORF prediction and translation of input sequences. Any translation table from the <a href="https://www.ncbi.nlm.nih.gov/Taxonomy/Utils/wprintgc.cgi" target="_blank" class="linkout">NCBI taxonomy</a> may be selected. </li>
                            </ul>
                        </li>
                    </ul>
                    </p>
                    <p class="text-justify">Click <code>Run initial processing</code> to launch the job. During this step, the experiment will become unavailable while the server performs
                        the initial processing of the data. Again, you will a receive an e-mail when the processing has finished.</p>
                </section>
                <h4 id="time-trapid-02">Initial processing time</h4>
            <p class="text-justify">The processing time depends on various factors. The most impactful are the size and composition of the input data set, the selected reference and similarity search databases, the number of selected RFAM clans, and the taxonomic classification. We can however give a few indicative numbers.

            <p class="text-justify">For the example <em>Panicum</em> data set (25,392 transcripts) discussed in the manuscript, the complete processing using PLAZA 4.5 monocots and all options set to their default value (no taxonomic classification) took around 1 hour and 3 minutes in total. The initial processing of a diatom-dominated phytoplankton community metatranscriptome consisting of 143,308 sequences took around 2 hours and 12 minutes in total, using Pico-PLAZA 3 as reference database, with all options set to their default value and performing taxonomic classification.
            </p>
            </section>

            <section class="page-section" id="basic-analyses">
                <h3>Basic analyses</h3>
                <p class="text-justify">After the initial processing of the data has finished, several new data
                    types are available for the TRAPID experiment: gene families, RNA families, and functional annotation (GO terms,
                    protein domains, or KEGG orthologs). Using these extra data types offers exciting new analyses to the
                    user, accessible from the side menu of the experiment. Step-by-step instructions on how to explore some of these data types can be found in the <?php echo $this->Html->link("tutorial", array("controller" => "documentation", "action" => "tutorial", "#"=>"tutorial-1-explore")); ?>. </p>
                <h4 id="statistics">General statistics</h4>
                <div class="row">
                    <div class="col-md-4">
                        <div class="doc-figure">
                            <?php echo $this->Html->image('tutorial/general_statistics.png', array('alt' => 'General statistics page', 'class'=>'img-responsive img-centered')); ?>
                            <p class="text-justify doc-figure-legend small">
                                <strong>Figure 1: general statistics page example.</strong> Statistics generated using the <em>Panicum</em> example data set.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-8">

                        <p class="text-justify">
                            The general statistics page offers a complete overview of ORF finding, taxonomic classification, gene or RNA family
                            assignments, similarity search species information, meta-annotation and functional
                            information. This report can be exported to PDF by clicking the <code>Export to PDF</code> button in the top right.
                        </p>
                        <p class="text-justify">
                            The length distribution of the experiment's transcript and predicted ORF sequences can be inspected via the <code>Sequence length distribution</code> page.</p>
                    </div>
                </div>


                <section class="page-section-sm">
                <h4 id="labels">Transcript subsets</h4>
                    <p class="text-justify">Within a TRAPID experiment, transcript subsets can be defined from any arbitrary list of transcript identifiers. For instance, if the data set comprises transcriptome data from different sources (e.g. different species, tissues, developmental types or stress conditions), then the user has the ability to define transcript subsets corresponding to these sources. Subsets may either be uploaded as a file (from the <code>Import data</code> page, providing a list of transcript identifiers for the subset), or created interactively (for instance from the <code>Taxonomic classification</code> page, as shown in the dedicated section). </p>

                    <p class="text-justify">By creating transcript subsets, several new analyses become available, such as comparison of functional annotation between different subsets, or functional enrichment analysis.
                        Note that it is possible to assign multiple labels to one transcript, and tha three transcript subsets are defined by default after initial processing completion, corresponding to protein-coding transcripts, RNA transcripts, and ambiguous transcripts (transcripts assigned to both a RNA and protein-coding gene family). </p>

                    <p class="text-justify">Step-by-step instructions on how to define and analyze subsets can be found in the <?php echo $this->Html->link("tutorial", array("controller" => "documentation", "action" => "tutorial", "#"=>"tutorial-1-subsets")); ?>.</p>
                </section>
                <section class="page-section-sm">
                <h4 id="data-search">Searching for data</h4>
                    <p class="text-justify">The user has the ability to search for a various data types within their experiment, using the search box present in the header of the experiment. Functional annotation (GO terms, protein domains, or KO terms) can be searched using either identifiers (e.g. <code>GO:0005509</code>) or
                        descriptions (e.g. <code>Calcium ion binding</code>). Similarly, gene and RNA families can be searched using either TRAPID's internal identifier (with a <code>exp_id_</code> prefix) or
                        the reference DB gene family identifier. Finally, the search box is convenient to retrieve the transcripts having a given meta-annotation. </p>
                    <h4 id="data-export">Exporting data</h4>
                    <p class="text-justify">The TRAPID platform allows the export of both the original data and the
                        annotated and processed data of a user experiment.
                        This data access is available under the <em>Export data</em> header on an experiment page and
                        includes structural ORF information, transcript/ORF/protein sequences, taxonomic classification,
                        gene/RNA family information, and functional GO/InterPro information.</p>
                    <p class="text-justify">The remainder of this section consists of a description of each type of export file, organized by category, complemented by minimal examples (ten first records).
                        Please click on the <span class='label label-primary'>Toggle example</span> links to show the corresponding minimal example export file. </p>
                </section>
                <section class="page-section-sm">
                    <h5>Structural data</h5>
                    <p class="text-justify">
                        The structural data export file is a tab-delimited file providing the following information for each sequence of an experiment:
                    <ul>
                        <li><code>Transcript identifier</code>: the transcript sequence identifier. </li>
                        <li><code>Frame information</code>: the detected frame, strand, and full frame information (homology support) for the inferred ORF sequence of the transcript.</li>
                        <li><code>Frameshift information</code>: flag putative frameshifts (0/1 boolean value). </li>
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
                <section class="page-section-sm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="doc-figure">
                                <?php echo $this->Html->image('documentation/toolbox.png', array('alt' => 'Gene family page', 'class'=>'img-responsive img-centered')); ?>
                                <p class="text-justify doc-figure-legend small">
                                    <strong>Figure 2: gene family page toolbox.</strong> The toolbox is shows on top of any gene family page.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <p class="text-justify">Several pages (gene family, RNA family, functional annotation) feature a toolbox that contains links to analyze or visualize the given data object. Figure 2 gives an example of toolbox as shown on the gene family page, which features links to the multiple sequence alignement / phylogenetic tree creation page, and the functional annotation associated to the family. </p>
                        </div>
                    </div>

                </section>

            </section>

            <section class="page-section">
            </section>

            <section class="page-section">
                <h3 id="enrichment">Analyzing subsets</h3>
                <div class="doc-figure">
                    <?php echo $this->Html->image('documentation/ipr_enrichment_mmetsp0936.png', array('alt' => 'Subset functional enrichment', 'class'=>'img-responsive img-centered')); ?>
                    <p class="text-justify doc-figure-legend small">
                        <strong>Figure 3: InterPro enrichment results for a subset of <code>MMETSP0936</code> (<em>Ostreococcus mediterraneus</em>) heat shock transcripts. </strong> (A) Enriched InterPro domains visualization. (B) Sankey diagram depicting the relationships between heat shock transcripts (left blocks), significantly enriched InterPro domains (middle blocks), and PLAZA gene families (right blocks).
                    </p>
                </div>
                <p class="text-justify">Apart from the functional annotation of individual transcripts, TRAPID also
                    supports the quantitative analysis of experiment subsets, for instance using functional enrichment statistics (Figure 3).</p>
                <div class="row">
                    <div class="col-md-4">
                        <div class="doc-figure">
                            <img
                                    src="/webtools/trapid/img/documentation/003_go_graph.png" alt="GO enrichment graph"
                                    class="img-responsive img-centered"/>
                            <p class="text-justify doc-figure-legend small">
                                <strong>Figure 4: GO enrichment graph example (biological process).</strong></p>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <p class="text-justify">Various comparative analyses can be performed using subsets, accessible from the side menu of an experiment:
                        <ul>
                            <li><code>Subset enrichment</code>: subset functional enrichment (subset versus all; hypergeometric distribution): visualized as bar chart (Figure 3A), table, Sanley diagrams (Figure 3B), and enrichment
                                GO graph output when working with GO terms (Figure 4).
                            </li>
                            <li><code>Compare subset</code>: calculates functional annotation frequencies between subsets. Also includes
                                subset-specific annotations.
                            </li>
                            <li><code>Explore subsets</code>: list subsets, and visualize them as an interactive Venn diagram. From this page it is also possible to delete subsets. </li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="page-section">
                <h3 id="tax-classification">Taxonomic classification</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="doc-figure">
                            <?php echo $this->Html->image('documentation/tax_krona.png', array('alt' => 'Taxonomic classification results', 'class'=>'img-responsive img-centered')); ?>
                            <p class="text-justify doc-figure-legend small"><strong>Figure 5: taxonomic classification results visualization. </strong> Krona representaition of the taxonomic classification results for <code>MMETSP0938</code> (<em>Ostreococcus mediterraneus</em>).</p>
                        </div>
                    </div>
                    <div class="col-md-8">
                        Taxonomic classification results can be explored by the user through multiple interactive visualizations. To access the taxonomic classification results, click <code>Taxonomicclassification</code> in the side menu, within a TRAPID experiment. If this step was not performed during the initial processing, this page is not accessible. </p>
                        <p class="text-justify">The Krona radial chart, visible in Figure 5, and the tree viewer enable an in-depth examination of the results. in contrast, the sample composition bar and pie charts (Figure 6) provide a quick overview of the results, depicting the domain-level composition and the ten most represented clades at adjustable taxonomic ranks.
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="doc-figure">
                            <?php echo $this->Html->image('documentation/tax_subsets.gif', array('alt' => 'Taxonomic classification subset', 'class'=>'img-responsive img-centered')); ?>
                            <p class="text-justify doc-figure-legend small"><strong>Figure 6: example subset creation from taxonomic classification results. </strong> Transcripts assigned to 'bacteria' were selected and grouped in a subset created on-the-fly. The visualized data corresponds to <code>MMETSP0938</code> (<em>Ostreococcus mediterraneus</em>).</p>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <p class="text-justify">The interactive visualizations can also be used to quickly create transcript subsets by selecting clades: all the transcripts assigned to the selected clade are then grouped in a new transcript subset that can be exported or further analyzed within TRAPID.</p>
                        <p class="text-justify">To create a subset from the taxonomic classification result page, hold the <kbd>CTRL</kbd> key and click on the clades for which a subset should be created. Once the selection is over, type a name for the subset and click <code>create susbet</code>. Figure X shows an example.</p>
                    </div>
                </div>
            </section>

            <section class="page-section">
                <h3 id="completeness">Core GF completeness analysis</h3>
                <p class="text-justify">TRAPID users can assess and examine the gene space completeness of their input transcriptome data sets, by checking the presence of core gene families (‘core GFs’). Core GFs consist of a set of gene families that are highly conserved in a majority of species within a defined evolutionary lineage. A key feature of this functionality in TRAPID is the on-the-fly definition of core GFs sets for any lineage represented in the selected reference database, making it possible for users to rapidly examine gene space completeness along an evolutionary gradient. The output of the core GF completeness analysis consists of the completeness score (an intuitive quantitative measure of the gene space completeness at the selected taxonomic level ranging between 0-100%), together with the list of represented and missing core GFs and their associated biological functions.</p>

                <p class="text-justify">Please note that core gene families are not necessarily single-copy, which is one of the main differences between this approach and the widely-used BUSCO. While the usage of all conserved gene families, regardless of their copy number, enables to cover a much larger fraction of the gene function space, it has nonetheless been shown that core GF completeness and BUSCO scores are generally in agreement (<a href="https://www.sciencedirect.com/science/article/pii/S1369526618300980" target="_blank" class="linkout">Van Bel et al, 2018</a>)</p>
                        <p class="text-justify">Step-by-step instructions on how to perform core  GF completeness analysis can be found in the <?php echo $this->Html->link("tutorial", array("controller" => "documentation", "action" => "tutorial", "#"=>"tutorial-2")); ?>.</p>
            </section>

            <section class="page-section">
                <h3 id="msa">Multiple sequence alignments</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="doc-figure">
                            <?php echo $this->Html->image('documentation/msa_viewer.png', array('alt' => 'MSA viewer', 'class'=>'img-responsive img-centered')); ?>

                            <p class="text-justify doc-figure-legend small"><strong>Figure 7: example multiple sequence alignment.</strong> <em>Panicum</em>
                                data set, transcript <code>contig16311</code> in family HOM04x5M005933 (PLAZA 4.5 monocots, 52 genes from 35 species).</p>
                        </div>
                        </div>
                    <div class="col-md-8">
                    <p class="text-justify">Starting from a selected transcript, the user has the ability to create
                        an amino acid multiple sequence alignment (MSA) within a gene family context. As such, the
                        user can create an MSA containing the transcripts within a gene family together with a
                        selection of coding sequences from the reference database. This tool is accessible from the
                        toolbox from a gene family (as shown in Figure 2). The MSA is created using MAFFT (default) or MUSCLE.
                        The versions and parameters used for either tool can be found in the <?php echo $this->Html->link("tools & parameters", array("controller" => "documentation", "action" => "tools_parameters", "#"=>"msa-phylogeny")); ?> page. </p>
                    <p class="text-justify">After the MSA has been created, the user has the ability to view this
                        alignment using an in-browser MSA viewer (Figure 5), or to download the MSA and inspect it using a different
                        tool (<code>Files & extra</code> tab of the MSA/tree page).</p>
                    <p class="text-justify">Step-by-step instructions on how to generate a MSA can be found in the <?php echo $this->Html->link("tutorial", array("controller" => "documentation", "action" => "tutorial", "#"=>"tutorial-1-phylogeny")); ?>.</p>
                    </div>
                </div>
            </section>

            <section class="page-section">
                <h3 id="tree">Phylogenetic trees</h3>

                <div class="row">
                    <div class="col-md-4">
                        <div class="doc-figure">
                                <?php echo $this->Html->image('tutorial/phylogenetic_tree.png', array('alt' => 'Gene family phylogenetic tree', 'class'=>'img-responsive img-centered')); ?>
                                <p class="text-justify doc-figure-legend small"><strong>Figure 8: example phylogenetic tree (tutorial).</strong> Transcript meta-annotation and subset information  are also displayed, depicted next to the transcript identifiers as colored squares and circles, respectively.
                                </p>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <p class="text-justify">Similar to the MSA, the user also has the
                            ability to create a phylogenetic tree within a gene family context (see previous section).
                            In order to create a phylogenetic tree.</p>
                        <p class="text-justify">In order to create phylogenetic trees which are less dependent on
                            putative large gaps, the standard MSA can optionally be edited (or stripped). In this
                            stripped MSA the alignment length is reduced by removing iinut sequences, positions, or both (dependin on the user's choice).  </p>
                        <p class="text-justify">In case the editing is too stringent and yields a edited MSA with zero or only a
                            few conserved alignment positions, do not hesitate re-run the tree analysis using a more relaxed editing
                            option (or with no editing altogether, the default), which will yield more conserved alignment positions.</p>
                        <p class="text-justify">Within TRAPID, four different tree creation programs can be used: FastTree2, IQ-TREE, PhyML, or RaxML.
                            The latter is TRAPID's default due to its very fast processing speed.
                            The versions and parameters used for each tool can be found in the <?php echo $this->Html->link("tools & parameters", array("controller" => "documentation", "action" => "tools_parameters", "#"=>"msa-phylogeny")); ?> page. </p>
                        <p class="text-justify">Finally, if the user has defined subsets within his/her experiment,
                            these subsets can also be displayed on the phylogenetic tree, together with meta-annotation, making subsequent analyses
                            much easier. By default, both the subset and meta-annotation informations are displayed next to the
                            phylogenetic tree (see Figure 8). </p>
                <p class="text-justify">Step-by-step instructions on how to create phylogenetic trees can be found in the <?php echo $this->Html->link("tutorial", array("controller" => "documentation", "action" => "tutorial", "#"=>"tutorial-1-phylogeny")); ?>.</p>
                    </div>
                </div>
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
                        <div class="doc-figure">
                            <img
                                    src="/webtools/trapid/img/documentation/007_tree.png" alt="phylo tree"
                                    style="max-width: 85%;" class="img-responsive img-centered"/>
                            <p class="text-justify doc-figure-legend small"><strong>Figure 9:</strong> example one-to-one orthology
                                (<em>Panicum</em> data set, transcript contig14762 RecQ helicase). The node indicated in red
                                shows the monocot homologs for this clade (1 indicates 100% bootstrap support). Within this
                                sub-tree, from each included monocot species a single gene is present, revealing simple
                                one-to-one orthology relationships.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="doc-figure">
                            <img
                                    src="/webtools/trapid/img/documentation/008_tree.png" alt="phylo tree"
                                    style="max-width: 85%;" class="img-responsive img-centered"/>
                            <p class="text-justify doc-figure-legend small"><strong>Figure 10:</strong> example one-to-many orthology
                                (<em>Panicum</em> data set, transcript contig00984 ATPase). The node indicated in red shows
                                the monocot homologs for this clade (1 indicates 100% bootstrap support). Within this
                                sub-tree, 2 genes from <i>Zea mays</i> are present, revealing that for the single Panicum
                                transcript two co-orthologs exist in <i>Z. mays</i>.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div> <!-- End column -->

        <div class="col-md-3 scrollspy" id="navigation-col">
            <ul class="nav hidden-xs hidden-sm" id="sidebar-nav" data-spy="affix">
                <h5 class="doc-sidebar-header"><i class="material-icons md-24">toc</i> Sections</h5>
                <li><a href="#introduction">Introduction & references</a>
                    <ul class="nav">
                        <li><a href="#requirements">Software requirements</a></li>
                        <li><a href="#citations">Citations</a></li>
                        <!--li><a href="#patterns_ref">References</a></li-->
                    </ul>
                </li>
                <li><a href="#authentication">User authentication</a></li>
                <li><a href="#create-experiments">Creating experiments</a></li>
                <li><a href="#data-upload">Transcript upload & jobs</a></li>
                <li><a href="#initial-processing">Perform transcript processing</a>
                    <ul class="nav">
                        <li><a href="#initial-processing-settings">Initial processing options</a></li>
                        <li><a href="#time-trapid-02">Initial processing time</a></li>
                    </ul>
                </li>
                <li><a href="#basic-analyses">Basic analyses</a>
                    <ul class="nav">
                        <li><a href="#statistics">General statistics</a></li>
                        <li><a href="#labels">Transcript subsets</a></li>
                        <li><a href="#data-search">Searching for data</a></li>
                        <li><a href="#data-export">Exporting data</a></li>
                        <li><a href="#toolbox">Toolbox</a></li>
                    </ul>
                </li>
                <li><a href="#enrichment">Analyzing subsets</a></li>
                <li><a href="#tax-classification">Taxonomic classification</a></li>
                <li><a href="#completeness">Core GF completeness</a></li>
                <li><a href="#msa">Multiple sequence alignments</a></li>
                <li><a href="#tree">Phylogenetic trees</a></li>
                <li><a href="#orthology">Orthology</a></li>
                <li class="sidebar-nav-to-top"><a href="#top">Back to top</a></li>
            </ul>
        </div>

    </div> <!-- End row -->

    <!-- Figure full size modal -->
    <div class="modal" id="figure-modal" tabindex="-1" role="dialog" aria-labelledby="figure-full-size" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><br>
                    <img src="" class="imagepreview img-centered" style="max-width: 100%;" ><br>
                    <p id="modal-legend" class="text-justify"></p>
                </div>
            </div>
        </div>
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

    $('.doc-figure img').on('click', function() {
        $('.imagepreview').attr('src', $(this).attr('src'));
        var figureLegend = $(this).parent().find('p.doc-figure-legend').html();
        if(figureLegend == null) {
            $('#modal-legend').empty();
        }
        else {
            $('#modal-legend').html(figureLegend);
        }
        $('#figure-modal').modal('show');
    });

</script>