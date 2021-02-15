
<div class="container">
    <div class="page-header">
		<h1 class="text-primary">Tutorials</h1>
    </div>

    <div class="row">

        <div class="col-md-9" id="tutorial-col">
            <section class="page-section" id="introduction">
                <h3>Introduction</h3>
                    <p class="text-justify">
                        TRAPID is an online tool for the fast and efficient processing of assembled RNA-Seq transcriptome data.
                        TRAPID offers high-throughput ORF detection, frameshift correction and includes a functional, comparative and phylogenetic toolbox.
                        Input sequences can be ESTs, full-length cDNAs or RNA-Seq transcriptome sequences. Additionally, coding sequence derived from an annotated genome can also be used.
                        We offer four reference databases: for plants and green algae, the latest <a href="http://bioinformatics.psb.ugent.be/plaza/" alt="PLAZA link" target="_blank" class="linkout">PLAZA</a> databases (PLAZA dicots and monocots 4.5, pico-PLAZA 3), and for other eukaryotes (e.g. Alveolata, Amoebozoa, Euglenozoa, Fungi, Metazoa) or prokaryotes (Bacteria and Archaea) <a href="http://eggnog45.embl.de" alt="eggNOG 4.5 link" target="_blank" class="linkout">eggNOG version 4.5</a> is available.
                    </p>
                    <p class="text-justify">Once the initial processing has assigned functional annotations and gene families to the user-defined transcripts, evolutionary studies on gene families including the uploaded transcripts can be performed. Through a few simple operations, multiple sequence alignments and phylogenetic trees can be generated. </p>
                    <p class="text-justify">Although TRAPID hosts a wide range of reference genomes, it was not developed to process data from massive-scale meta -omic studies, as it can process 200,000 sequences maximum per experiment. Adding more transcripts is possible, but correct processing or website performance is not guaranteed in this case. </p>
            </section>

            <section class="page-section" id="account-setup">
                <h3>Account registration and login</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="doc-figure">
                            <?php echo $this->Html->image('tutorial/optimized/authentication_register.png', array('alt' => 'TRAPID login page', 'class'=>'img-responsive img-centered')); ?>
                            <p class="text-justify doc-figure-legend small">
                            <strong>Figure 1: registration form.</strong> Fill in your information and click the 'register' button: an e-mail with login credentials will be sent.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <p class="text-justify">Before you can use TRAPID, you need to register for an account. For academics, this is free of charge.
                            First, click on <code>Register</code> in the header (or click <a href="<?php echo $this->Html->Url(array("controller" => "trapid", "action" => "authentication", "register")); ?>" alt="Register link" target="_blank" class="linkout">here</a>).
                            On the next page, fill in the required information and click on the <code>Register</code> button (Figure 1). Make sure to provide a valid e-mail address as your login credentials will be sent immediately at this address.
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="doc-figure">
                            <?php echo $this->Html->image('tutorial/optimized/authentication_login.png', array('alt' => 'TRAPID login page', 'class'=>'img-responsive img-centered')); ?>
                            <p class="text-justify doc-figure-legend small">
                                <strong>Figure 2: login form.</strong> Log in using your e-mail address and the provided password.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <p class="text-justify">On the main page, click <code>Login</code> in the header (or click <a href="<?php echo $this->Html->Url(array("controller" => "trapid", "action" => "authentication", "login")); ?>" alt="Login link" target="_blank" class="linkout">here</a>).
                            This will take you to a login form (Figure 2). Here, use the <strong>e-mail address</strong> used to register and the <strong>password</strong> sent by mail to log in.
                        </p>
                    </div>
                </div>

            </section>
            <hr>
            <section class="page-section" id="tutorial-1">
                <h3>Tutorial 1:  <em>Panicum</em> transcriptome functional annotation</h3>
                <p class="text-justify">In this tutorial, you'll learn how to functionally annotate and analyze the transcriptome of <a href="https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=206008" title="View in NCBI taxonomy" target="_blank" class="linkout"><em>Panicum hallii</em></a> (Meyer <i>et al</i>. 2012, Transcriptome analysis and gene expression atlas for <i>Panicum hallii</i> var. filipes, a diploid model for biofuel research.).
                    The dataset can be obtained from the <a target="_blank" class="linkout" href="ftp://ftp.psb.ugent.be/pub/trapid/datasets/panicum/">TRAPID FTP</a>. </p><br>
                <section class="page-section-sm" id="tutorial-1-upload">
                    <h4>Part 1: uploading and processing the data</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="doc-figure">
                                <?php echo $this->Html->image('tutorial/optimized/experiments_empty.png', array('alt' => 'Experiments overview (empty)', 'class'=>'img-responsive img-centered')); ?>
                                <p class="text-justify doc-figure-legend small">
                                <strong>Figure 3: empty experiments overview.</strong> At the bottom of the page, click <strong>'add new experiment'</strong> to create a new experiment.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <p class="text-justify">After logging in, you will be redirected to the <strong>experiments overview</strong>. If you are a new user, this page will be empty (Figure 3).
                                Please note this page can list two types of experiments:
                            <ul>
                                <li>Current experiments (experiments you uploaded and own), </li>
                                <li>Shared experiments (experiments uploaded by others you are allowed to view) </li>
                            </ul>
                            </p>
                        </div>
                    </div>
                        <div class="row">
                            <div class="col-md-4">
                            <div class="doc-figure">
                            <?php echo $this->Html->image('tutorial/optimized/experiments_creation.png', array('alt' => 'Experiment creation', 'class'=>'img-responsive img-centered')); ?>
                            <p class="text-justify doc-figure-legend small">
                             <strong>Figure 4: experiment creation.</strong> A name, a description, and  reference database need to be chosen for the new experiment. Finalize the creation by clicking <code>Create experiment</code>.
                            </p>
                            </div>
                            </div>
                            <div class="col-md-8">
                                <p class="text-justify">Clicking on the <code>add new experiment</code> button (<span class="glyphicon glyphicon-plus"></span> icon) opens the experiment creation window (Figure 4). </p>
                                <p class="text-justify">To start, like shown in Figure 4, enter a name and a description for the experiment. For instance, <code>Tutorial 1</code> as a name and <code>Documentation tutorial 1 (Panicum)</code> as description.
                                    Select <code>PLAZA 4.5 monocots</code> as reference database, as PLAZA is the recommended database for plants and algae, and the 'monocots' version contains genomes from closely related species.
                                    In case data from other lineages is analyzed, we recommend selecting eggNOG 4.5. Add the experiment by clicking <code>create experiment</code>. </p>
                            </div>
                        </div>
                        <div class="row">
                        <div class="col-md-4">
                            <div class="doc-figure">
                                <?php echo $this->Html->image('tutorial/optimized/experiments_created.png', array('alt' => 'Experiments overview', 'class'=>'img-responsive img-centered')); ?>
                                <p class="text-justify doc-figure-legend small">
                                 <strong>Figure 5: experiments overview.</strong> The newly created experiment appeared in the current experiments table. To add sequences, first click on the name of the experiment.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <p class="text-justify">The new experiment will now appear in the current experiments table (Figure 5). Note that each user can create a <strong>maximum of 20 experiments</strong>.</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="doc-figure">
                                <?php echo $this->Html->image('tutorial/optimized/experiment_page.png', array('alt' => 'Experiment page', 'class'=>'img-responsive img-centered')); ?>
                                <p class="text-justify doc-figure-legend small">
                                    <strong>Figure 6: empty experiment overview page. </strong> The newly created experiment overview page. Click on <code>Import data</code> in the side menu to import sequences.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <p class="text-justify">
                                To continue, click on the experiment's name (e.g. <code>Tutorial 1</code>) in the current experiments table. This will take you to your newly created TRAPID experiment and display the <strong>experiment overview page</strong> (Figure 6). After transcripts sequences are processed, this page will contain general experiment statistics (in the <code>Experiment information</code> panel) and a detailed overview of the transcripts. </p>
                            <p class="text-justify">
                                Two main elements may be used for navigating a TRAPID experiment: the <strong>experiment header</strong> (top) and the <strong>side menu</strong> (left). The experiment header contains links to experiment controls (jobs, log, and settings) and a search box to find specific sequences, gene/RNA families, or functional data. The side menu lists the main data import/export, exploration, and analysis options available within TRAPID. Note that in figure 6, all the items except <code>Import data</code> are disabled, since the experiment is empty. </p>
                           <p class="text-justify">To continue and upload input sequences, click on the <code>Import data</code> item of the side menu. </p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="doc-figure">
                                <?php echo $this->Html->image('tutorial/optimized/import_transcripts.png', array('alt' => 'Import data page', 'class'=>'img-responsive img-centered')); ?>
                                <p class="text-justify doc-figure-legend small">
                                    <strong>Figure 7: uploading transcript sequences.</strong> From this page, a dataset can be uploaded from a file or a URL. After adding files or URLs, please click <code>Load data into database</code> to upload the data.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-8">
                           <p class="text-justify">
                               Input sequences must be provided in <a href='https://en.wikipedia.org/wiki/FASTA_format' title='FASTA format (Wikipedia)' target='_blank' class='linkout'>FASTA format</a>. Make sure each sequence has a unique identifier (max. 100 characters) and no empty sequences are present. Please note that <strong>individual files cannot be larger than 32Mb</strong>. For large datasets, we therefore recommend to supply compressed data (zipped or gzipped) and/or to split the data in multiple files.
                           </p>
                           <p class="text-justify">
                               In case you downloaded the tutorial dataset, you can upload the file (click <code>Browse...</code> and locate it on your system). Otherwise, you can directly supply the URL (<code>ftp://ftp.psb.ugent.be/pub/trapid/datasets/panicum/panicum_transcripts.zip</code>). After selecting a file or URL, click <code>Add file/URL</code> to confirm your choice and add the dataset. Afterwards, click <code>Load data into database</code> to load all sequences into our database. <strong>Both steps are essential before the data can be processed. </strong>
                           </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="doc-figure">
                                <?php echo $this->Html->image('tutorial/optimized/process_transcripts.png', array('alt' => 'Initial processing page', 'class'=>'img-responsive img-centered')); ?>
                                <p class="text-justify doc-figure-legend small">
                                 <strong>Figure 8: process transcripts.</strong> Select the desired settings for the various processing steps and click <code>Run initial processing</code>.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <p class="text-justify">After the sequences have been loaded into the TRAPID database, the next step is to perform the initial processing. This step will add them to gene or RNA families, add annotations and a taxonomic label (if this step was enabled). To start the initial processing, go to your TRAPID experiment. Note how the page now contains a table with transcripts at the bottom, and how some options of the side menu have become enabled (although they only become useful after the processing). Click <code>Process transcripts</code>.  </p>
                            <p class="text-justify">On the next page, you have to specify how transcripts should be processed by TRAPID. For this tutorial, select the settings as shown in Figure 8 (default settings except for the taxonomic classification that was disabled). More details on these settings can be found in the
                            <?php echo $this->Html->link("general documentation", array("controller" => "documentation", "action" => "general", "#"=>"initial-processing")); ?>.

                                Finish by clicking <code>Run initial processing</code>. Depending on the size of the dataset, the selected settings, and the load on our servers, this can take up to several hours. For this tutorial, it should take around one hour after starting. In the meantime, the experiment will be in <code>processing</code> state and cannot be accessed, except for the experiment's job management and log pages. <strong>An e-mail will be sent upon completion</strong>. </p>
                        <p class="text-justify">Once the initial processing has finished, all sequences will be included in gene/RNA families and be  annotated (when possible). Additionally, as transcript data often includes truncated sequences or sequences with indels, potentially problematic sequences are flagged. </p>
                        </div>
                   </div>
                </section>

                <section class="page-section-sm" id="tutorial-1-explore">
                    <h4>Part 2: exploring TRAPID output</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="doc-figure">
                                <?php echo $this->Html->image('tutorial/optimized/general_statistics.png', array('alt' => 'General statistics page', 'class'=>'img-responsive img-centered')); ?>
                                <p class="text-justify doc-figure-legend small">
                                    <strong>Figure 9: the general statistics page.</strong> This page displays general information that can be used to assess the quality of the transcripts, their taxonomic classification (if performed), how many were assigned to gene or RNA families, and how many received functional annotation. This report can be exported to PDF by clicking the <code>Export to PDF</code> button in the top right.
                                </p>
                        </div>
                        </div>
                        <div class="col-md-8">
                            <p class="text-justify">Once the initial processing has finished, go to the experiment overview page. More options are now accessible from the side menu, and the table at the bottom of the page lists your sequences with their assigned gene family, predicted functional annotation, and meta-annotation. Click the <code>Statistics</code> > <code>General statistics</code> item in the side menu.</p>
                            <p class="text-justify">The following page will show a broad range of statistics that reveal the quality of the input dataset, how many sequences were assigned to gene or RNA families, and how many were functionally annotated (Figure 9). The other page available under <code>Statistics</code>, <code>Sequence length distribution</code>, displays the length distribution of the experiment's transcript and predicted ORF sequences. </p>
                            <p class="text-justify">Gene families (which group protein-coding genes derived from a common ancestor) and RNA families (which group homologs of known non-coding RNAs) are available from the side menu, under <code>Browse gene families</code> and <code>Browse RNA families</code>, respectively.</p>
                            <p class="text-justify">Relevant families can be found using the search function of the experiment header. For instance, by selecting <code>GO term</code>, relevant GO identifiers or descriptions labels can be searched, and the associated sequences retrieved.</p>

                        </div>
                    </div>

                </section>
                <section class="page-section-sm" id="tutorial-1-phylogeny">
                    <h4>Part 3: phylogenetic analysis of a specific gene family</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="doc-figure">
                                <?php echo $this->Html->image('tutorial/optimized/go_term_search.png', array('alt' => 'GO term search results', 'class'=>'img-responsive img-centered')); ?>
                                <p class="text-justify doc-figure-legend small"><strong> Figure 10: GO term search results for <code>leaf senescence</code>.</strong> The GO terms having matching descriptions were retrieved. From here, the associated sequences can rapidly be found. </p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <p class="text-justify">First, search for <code>leaf senescence</code> GO terms, and look at the transcripts annotated with the <code>leaf senescence (GO:0010150)</code> term by clicking on the GO identifier (Figure 10). </p>
                            <p class="text-justify">Look at <code>contig04501</code>: this transcript is annotated as 'quasi full length'. Click on its identifier to get to the transcript page. Once there, click the associated gene family identifier, which will take you to its associated gene family page. </p>
                            <p class="text-justify">From the gene family page, build the multiple sequence alignment (MSA) for this gene family by clicking <code>Create multiple sequence alignment / phylogenetic tree</code> in the toolbox. On the next page, check <code>Generate MSA only</code> to ensure no phylogenetic tree is generated. While it's possible to use MUSCLE instead of MAFFT and to modify the set of input sequences, we will not do so in the tutorial. Click <code>Create MSA/tree</code> to launch the creation of the MSA. Note that if <strong>more than 250 genes</strong> are selected, it will not be possible to submit the MSA creation job.</p>
                            <p class="text-justify">After completion, the page will contain a tab with a viewer for the MSA. From the alignment, it can be seen the transcript has indeed a good alignment with most of the other members in the family, although at the N-terminal end there likely is a portion missing (and hence is indeed quasi full length). The other transcript assigned to the family, <code>contig20276</code>, is the opposite: the C-terminal end appears to be missing. Both contigs may therefore represent a single, split transcript.</p>
                            <p class="text-justify">The <code>Files & extra</code> tab provides a link to download the MSA in <code>.faln</code> format and lists the parameters used to generate it.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="doc-figure">
                                <?php echo $this->Html->image('tutorial/optimized/phylogenetic_tree.png', array('alt' => 'Gene family phylogenetic tree', 'class'=>'img-responsive img-centered')); ?>
                                <p class="text-justify doc-figure-legend small"><strong>Figure 11: phylogenetic tree.</strong> Interactive tree viewer (PhyD3) showing the phylogenetic tree of <code>contig01069</code> and its homologs. Transcript meta-annotation and subset information  are also displayed, depicted next to the transcript identifiers as colored squares and circles, respectively.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <p class="text-justify">Now, search for another transcript: <code>contig01069</code>. Find the gene family page of this transcript and click <code>Create multiple sequence alignment / phylogenetic tree</code>. This time, we will create a phylogenetic tree. In addition to the settings mentioned in the previous part, various phylogenetic tree creation settings can be adjusted: the MSA editing mode, the tree construction algorithm, and tree annotations (extra information about the transcripts displayed next to tree leaves). Feel free to do so (for instance, selecting fewer species from the reference gene family). </p>
                                <p class="text-justify">Next, click <code>Create MSA / tree</code>. The tree creation job will be launched and an e-mail will be sent upon completion. Once the job has finished, go back to the gene family page and click <code>View or create multiple sequence alignment / phylogenetic tree</code> to view the tree. The page will now contain a tab with an interactive viewer for the generated phylogenetic tree (Figure 11).</p>
                            <p class="text-justify">In case no tree is displayed or get an error message, very often the selected MSA editing setting is too stringent and should be modified. To check if this is the case, go to the multiple sequence alignment tab, and check the length of the edited alignment: if it is zero amino acids, then the editing was too stringent.</p>
                            <p class="text-justify">The Files & extra tab provides links to download the tree in PhyloXML and Newick formats and lists the parameters used to generate the phylogenetic tree. In case you want to create a new MSA or tree for the gene family with different settings, simply click the <code>Create MSA / phylogenetic tree […]</code> link on top of the page.</p>
                            <p class="text-justify">Read more about
                                <?php echo $this->Html->link("multiple sequence alignments", array("controller" => "documentation", "action" => "general", "#"=>"msa")); ?>
                               and <?php echo $this->Html->link("phylogenetic trees", array("controller" => "documentation", "action" => "general", "#"=>"tree")); ?> in the general documentation</p>
                        </div>
                    </div>
                </section>
                <section class="page-section-sm" id="tutorial-1-subsets">
                    <h4>Part 4: defining and analyzing subsets (within-transcriptome functional analysis)</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="doc-figure">
                                <?php echo $this->Html->image('tutorial/optimized/cell_cycle_ipr_enrichment.png', array('alt' => 'InterPro domain enrichment results', 'class'=>'img-responsive img-centered')); ?>
                                <p class="text-justify doc-figure-legend small"><strong>Figure 12: enriched InterPro domains. </strong> Overview of InterPro domain enriched within the <code>Cell_cycle</code> subset (maximum corrected p-value 0.005).
                                </p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <p class="text-justify">Within a TRAPID experiment,  transcript subsets can be defined from any arbitrary list of transcript identifiers, for instance transcripts expressed in a specific condition or tissue, and used to perform subsequent within-transcriptome functional analyses. Subsets may either be uploaded as a file or created interactively from the web application. By creating transcript subsets, several new analyses become available, such as comparison of functional annotation between different subsets, or functional enrichment analysis.</p>
                            <p class="text-justify">In this tutorial, we provide an example set of 33 Cell Cycle transcripts. The list can be downloaded from <a href='ftp://ftp.psb.ugent.be/pub/trapid/datasets/panicum/panicum_cell_cycle.lst'  download>TRAPID’s FTP</a>. To create a new transcript subset, click <code>Import data</code> (side menu). In the <code>Transcript subset</code> tab, click <code>Browse...</code> to select the downloaded file and enter a name for the subset (e.g. <code>Cell_cycle</code>). Click <code>Import subset</code> to finish.</p>
                            <p class="text-justify">Transcripts subsets can be inspected and deleted from the <code>Explore subsets</code> page (side menu).  To check if the cell cycle set is enriched for specific GO terms or Protein domains, click the <code>Subset enrichment</code> item in the side menu. On the next page, select a type of functional annotation for which to perform the analysis, a transcript subset, and a maximum q-value (corrected p-value) threshold. For this tutorial, we selected <code>InterPro domains</code>, <code>Cell_cycle</code> and <code>0.005</code>. Click <code>Compute enrichment</code> to launch the analysis.</p>
                            <p class="text-justify">Figure 12 shows the resulting page displaying for each of the enriched InterPro domains the enrichment fold, significance and a short description. Note that the InterPro domain identifiers are hyperlinks to pages containing more detailed information, and that results may also be explored as a table or downloaded.
                            </p>
                            <p class="text-justify">It is optionally possible to precompute functional enrichments for all available types of functional annotation, subsets, and q-value thresholds, by clicking the <code>Run functional enrichment</code> button on the experiment overview page.</p>
                            <p class="text-justify">Read more about
                                <?php echo $this->Html->link("subsets/labels", array("controller" => "documentation", "action" => "general", "#"=>"labels")); ?>
                                and <?php echo $this->Html->link("functional enrichment", array("controller" => "documentation", "action" => "general", "#"=>"enrichment")); ?>
                                in the general documentation</p>
                        </div>
                    </div>
                </section>
            </section>
            <hr>
            <section class="page-section" id="tutorial-2">
                <h3>Tutorial 2: examining gene space completeness</h3>
                <section class="page-section-sm">
                    <p class='text-justify'>
                        <strong>For this second tutorial, we'll continue using the TRAPID experiment created previously. Please make sure that the initial processing has been performed before following this tutorial. </strong></p>

                    <p class='text-justify'>
                        TRAPID enables users to assess and examine the gene space completeness of transcriptomes by checking the presence of <strong>core gene families</strong> (‘core GFs’), leveraging the GF assignment step of the initial processing.
                    <ul>
                        <li>Core GFs consist of a set of gene families that are highly conserved in a majority of species within a defined evolutionary lineage. </li>
                        <li>Core GF sets can be defined on-the-fly for any clade represented in the selected reference database, making it possible to rapidly examine gene space completeness along an evolutionary gradient. </li>
                    </ul>
                    </p>
                    <p class='text-justify'>
                        Read more about the <?php echo $this->Html->link("core GF completeness analysis", array("controller" => "documentation", "action" => "general", "#"=>"completeness")); ?> in the general documentation.
                    </p>
                </section>

                <div class="row">
                    <div class="col-md-4">
                        <div class="doc-figure">
                            <?php echo $this->Html->image('tutorial/optimized/completeness_job.png', array('alt' => 'Core GF completeness analysis', 'class'=>'img-responsive img-centered')); ?>
                            <p class="text-justify doc-figure-legend small"><strong>Figure 13: core GF completeness analysis submission form. </strong> Any phylogenetic clade represented in the selected reference database may be used for the analysis.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <p class='text-justify'>First, click the <code>core GF completeness</code> item of the side menu. The next page, shown in Figure 13, enables submission of core GF completeness jobs (<code>New analysis</code> tab) and exploration of previous analysis results (<code>Previous analysis</code> tab, disabled when no prior analysis was performed). Select a clade for the analysis, and click <code>Run analysis</code> to launch the job. The analysis can take up to a few minutes.
                        </p>
                        <p class='text-justify'>The default value of the conservation threshold parameter is <code>0.9</code>, meaning that a gene family is considered to be 'core' if it is represented in at least 90% of the species of the selected clade. This threshold does not require complete conservation across all species of the clade and can be adjusted in case more stringent or relaxed conservation requirements are needed.
                        </p>
                    </div>
                </div>
                    <div class="row">
                    <div class="col-md-4">
                        <div class="doc-figure">
                            <?php echo $this->Html->image('tutorial/optimized/completeness_results.png', array('alt' => 'Core GF completeness results', 'class'=>'img-responsive img-centered')); ?>
                            <p class="text-justify doc-figure-legend small"><strong>Figure 14: list of previous analyses and core GF completeness result panel. </strong> The result panel is organized in three tabs: a summary, the represented GFs table, and the missing GFs table.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-8">

                        <p class='text-justify'>After completion of the job, you should now be able to see the result panel (or an error message), composed of three tabs: summary, represented GFs table, and missing GFs table. The summary consists of a bar chart depicting the number of represented and missing core gene families, the completeness score and additional analysis metrics. The represented and missing core gene families, and their associated functional data, can be further investigated using the dedicated tables. It is also possible to export the tables to flat files. </p>
                        <p class='text-justify'>Finally, you can select different clades or settings and re-run the analysis for the same dataset. If you reload the core GF completeness page, the 'previous analyses' tab will be active and list all previous core GF completeness results (Figure 14). </p>

                    </div>
                </div>
            </section>
        </div>


        <div class="col-md-3 scrollspy" id="navigation-col">
            <ul class="nav hidden-xs hidden-sm" id="sidebar-nav" data-spy="affix">
<!--                <h5 class="doc-sidebar-header"><i class="material-icons md-24">toc</i> Sections</h5>-->
                <h5 class="doc-sidebar-header">Contents</h5>
                <li><a href="#introduction">Introduction</a></li>
                <li><a href="#account-setup">Registration & login</a></li>
                <li><a href="#tutorial-1">Functional annotation tutorial</a>
                        <ul class="nav">
                            <li><a href="#tutorial-1-upload">Data uploading and processing</a></li>
                            <li><a href="#tutorial-1-explore">Exploring TRAPID output</a></li>
                            <li><a href="#tutorial-1-phylogeny">Phylogenetic analysis</a></li>
                            <li><a href="#tutorial-1-subsets">Defining and analyzing subsets</a></li>
                        </ul>
                </li>
                <li><a href="#tutorial-2">Gene space completeness</a></li>
                <li class="sidebar-nav-to-top"><a href="#top">Back to top</a></li>
            </ul>
        </div>
    </div>
</div>

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

<script type="text/javascript">
    //    $(document).ready(function () {
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