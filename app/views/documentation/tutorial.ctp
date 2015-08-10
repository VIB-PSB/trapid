<!--<html>

<head>
	<link rel="stylesheet" type="text/css" href="../trapid.css">
	<title>TRAPID -- Tutorial</title>
</head>
<body>
-->
<div id="container">
	<div style='float:right;width:100px;text-align:right;margin-right:100px;'>
		<a class="mainref" href="/webtools/trapid/documentation/">Documentation</a>
	</div>
	<div style='clear:both;width:700px;font-size:8px;'>&nbsp;</div>
	<div id="tutorial">
		<h2>TRAPID Tutorial</h2>

		<a id="intro"><h3>INTRODUCTION </h3></a>
	
		<div class="subdiv">
			<p>TRAPID is an online tool for the fast and efficient processing of assembled RNA-Seq transcriptome data. TRAPID offers high-throughput ORF detection, frameshift correction and includes a functional, comparative and phylogenetic toolbox. Sequences can be ESTs, full-length cDNAs or RNA-Seq transcriptome sequences. Additionally, coding sequence derived from an annotated genome can also be used. We offer two reference databases: for plants and green algae  <a href="http://bioinformatics.psb.ugent.be/plaza/" alt="PLAZA link" target="_blank">PLAZA 2.5</a>, for Alveolata, Amoebozoa, Euglenozoa, Fungi, Metazoa and prokaryotes (Bacteria and Archaea) <a href="http://www.orthomcl.org/cgi-bin/OrthoMclWeb.cgi" alt="OrthoMCL DB link" target="_blank">OrthoMCL-DB version 5</a> is available. </p>

			<p>Once the initial processing has assigned functional annotations and gene families to the user-defined transcripts, evolutionary studies on gene families including the uploaded transcripts can be performed. Through a few simple operations, multiple sequence alignments and phylogenetic trees can be generated. </p>
			<p>Although TRAPID hosts several prokaryotic reference genomes, it was not developed to process data from large-scale metagenomic studies. </p>
		</div>
		
		<h3 class="clear">SETTING UP AN ACCOUNT</h3>
		<div class="subdiv">
							<div class="picture right" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/001_registration.png"><img src="/webtools/trapid/img/tutorial/001_registration.png" width="276" alt="Registration Image" /></a> <br /><strong>FIGURE 1 REGISTRATION FORM.</strong> Fill in your information and click register. An e-mail with login credentials will be send.</div>
			<p>Before you can use TRAPID, you need to register for an account. For academics this is free of charge. First click on the <em>"Register"</em> link (or click <a href="http://bioinformatics.psb.ugent.be/webtools/trapid/trapid/authentication/registration" alt="Register link" target="_blank">here</a>) on the bottom of the page, on the next page fill in the required information and click on the <em>"Register"</em> button. Make sure to provide a valid e-mail address as your login credential will be send immediately by e-mail.</p>

		</div>
		<h3 class="clear">LOGIN TO YOUR ACCOUNT</h3>	<div class="picture right" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/002_login_2.png"><img src="/webtools/trapid/img/tutorial/002_login_2.png" width="276" alt="Login Image" /></a> <br /><strong>FIGURE 2 LOGIN FORM.</strong> Login using your e-mail address and the provided password.</div>
		<div class="subdiv">

			<p>On the main page, click <em>"Login"</em> (or click <a href="http://bioinformatics.psb.ugent.be/webtools/trapid/trapid/authentication" alt="Login link" target="_blank">here</a>) at the bottom of the page, this will take you to a login form. Here use the <strong>e-mail address</strong> used to register and the <strong>password</strong> sent by mail to login.</p>

		</div>
		
		<a id="tutorial1"><h3 class="clear">TUTORIAL 1: FUNCTIONAL ANNOTATION OF PANICUM TRANSCRIPTS</h3></a>
		<a id="t1p1"><h4>PART 1: UPLOADING AND PROCESSING THE DATA</h4></a>
		<div class="subdiv">
			<p>After login the main page will be replaced by the <em>Experiments overview</em>. If you are new this overview will be empty. Please note the sections <em>Current experiments</em> (experiments you uploaded and own), <em>Shared experiments</em> (experiments uploaded by others you are allowed to view) and <em>Add new experiment</em> where a new experiment can be started.</p>

			<p>In this tutorial you'll learn how to functionally annotate the transcriptome of <i>Panicum hallii</i> (Meyer <i>et al</i>. 2012, Transcriptome analysis and gene expression atlas for <i>Panicum hallii</i> var. filipes, a diploid model for biofuel research.). The dataset can be obtained from : <a href="ftp://ftp.psb.ugent.be/pub/trapid/">ftp://ftp.psb.ugent.be/pub/trapid/</a>. </p>
			<div class="picture right" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/003_new_experiment.png"><img src="/webtools/trapid/img/tutorial/003_new_experiment.png" width="276" alt="Experiment Overview Image" /></a> <br /><strong>FIGURE 3 EXPERIMENT OVERVIEW.</strong> At the bottom a name and description for the new experiment needs to be filled in for the new experiment. Create the experiment by clicking <em>Create experiment</em>.</div>
			<p>To start, like shown in Figure 3, enter <em>Tutorial 1</em> as a name and <em>Panicum transcripts</em> as description. Leave the Reference DB at its default setting <em>PLAZA 2.5</em>, this is the recommended database for plants and algae. In case data from other organisms is analyzed, select OrthoMCL-DB 5.0. Add the experiment by clicking <em>Create experiment</em>. The new experiment will now appear in the <em>Current experiments</em> list. Note that each user can have a <strong>maximum of 10 experiments</strong>.</p>

			<p class="clear">To continue, click on the experiment's name (<em>"Tutorial 1"</em>) in the <em>Current experiments</em> overview.  This will take you to the <em>Experiment page</em>, here general statistics are shown (<em>Experiment information</em>), sequences can be imported and exported (<em>Import/Export</em>) and a search function is available to find specific sequences (<em>Search</em>).  Further down are options to start the processing (<em>Initial processing</em>) and once data is added a toolbox will appear and a detailed overview of the transcripts.</p>						<div class="picture right" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/004_experiment_added.png"><img src="/webtools/trapid/img/tutorial/004_experiment_added.png" width="276" alt="Experiment Overview Image" /></a> <br /><strong>FIGURE 4 EXPERIMENT OVERVIEW.</strong> The new experiment appeared in the <em>"Current experiments"</em> list. To add sequences, first click on the name of the experiment.</div>
			<p class="clear">Next click <em>Import data</em> to go to a page where the data can be uploaded (Transcript file management). All data needs to be provided as a fasta file. Make sure each sequence has a <strong>unique identifier</strong> (max. 50 characters) and no empty sequences are present. <strong>Single files cannot be bigger than 32Mb</strong>. For large datasets it is possible to offer the files as a (g)zipped file. Two options are available, in case you downloaded the tutorial dataset you can upload the file (use <em>Browse</em> to locate the file on your system), alternatively you can enter the URL <a href="ftp://ftp.psb.ugent.be/pub/trapid/datasets/panicum/panicum_transcripts.zip">ftp://ftp.psb.ugent.be/pub/trapid/datasets/panicum/panicum_transcripts.zip</a>. To upload/get the dataset click <em>Import transcript sequences</em>. Once the data is available on our server, click <em>Load data from files into database</em> to get the sequences into our database. <strong>Both steps are essential before the data can be processed.</strong></p>									
			<div class="picture left" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/005_experiment_page.png"><img src="/webtools/trapid/img/tutorial/005_experiment_page.png" width="276" alt="Experiment Overview Image" /></a> <br /><strong>FIGURE 5 EXPERIMENT PAGE.</strong> The, currently still empty, experiment page. Click on <em>"Import data"</em> to import sequences.</div>
			<div class="picture right" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/006_experiment_upload.png"><img src="/webtools/trapid/img/tutorial/006_experiment_upload.png" width="276" alt="Experiment Overview Image" /></a> <br /><strong>FIGURE 6 TRANSCRIPT FILE MANAGEMENT PAGE.</strong> From this page a dataset can be uploaded or directly grabbed from a URL. After the data is successfully transferred to our system please click <em>"Load data from files into database"</em>.</div>
						
			<p class="clear">After the sequences have been loaded to the TRAPID database, the first thing that is required is to perform the initial processing. This step will add them to gene families and add functional annotation.  Go to the experiment page (note how the <em>Toolbox</em> and <em>Transcripts</em> have become available, though they only become useful after the processing) and click <em>Perform transcript processing</em>.  On the next page you have to specify how transcripts should be assigned to gene families. For this tutorial select the settings as shown in Figure 7. Finish by clicking <em>Start transcriptome pipeline</em>. Depending on the size of the dataset and the load on our servers this can take several hours. In the meanwhile the experiment will be in processing state and cannot be analyzed. <strong>An e-mail will be send as soon as the dataset has been processed.</strong></p>
			<p>Once the initial processing is done, all sequences will be included in gene families and will be functionally annotated (if possible). Additionally, as transcript data often included truncated sequences or sequences with indels, problematic sequences are flagged. </p>

			<div class="picture right" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/007_process_transcripts.png"><img src="/webtools/trapid/img/tutorial/007_process_transcripts.png" width="276" alt="Experiment Overview Image" /></a> <br /><strong>FIGURE 7 PROCESS TRANSCRIPTS.</strong> Select the desired settings how the sequences should be added to gene families and which funtional annotation should be transferred.</div>
		</div>
		<a id="t1p2"><h4 class="clear">PART 2: EXPLORING TRAPID OUTPUT</h4></a>
		<div class="subdiv">
		<div class="picture right" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/008_stats.png"><img src="/webtools/trapid/img/tutorial/008_stats.png" width="276" alt="Experiment Overview Image" /></a> <br /><strong> FIGURE 8 THE STATISTICS PAGE.</strong> Here general information is shown that can be used to assess the quality of the transcripts, how many were assigned to gene families and how many recieved funtional annotation. Also notice the <em>"pdf export"</em> button on the bottom of the page, this will generate a PDF with all shown information.</div>
			<p>Once the initial processing is completed, go back to the <em>"experiment overview"</em> page, options in the <em>"Toolbox"</em> are now available and at the bottom of the page a list of your sequences is shown with their gene family and predicted functional annotation. In the <em>Toolbox</em> click on <em>General statistics</em>. The following page will show a broad range of statistics that reveal the quality of the input dataset, how many sequences were assigned to gene families and how many were functionally annotated (Figure 8). Other statistics available from the <em>Toolbox</em> are length distribution of the transcripts and the open reading frames.</p>
																					
			
			<p>Gene families (which group genes derived from a common ancestor) are available from the <em>Toolbox</em> under <em>Gene families</em>.</p>
			<p>Relevant families can be found using the search function on the experiment page. E.g. by selecting <em>GO description</em> relevant GO labels can be found and the associated sequences can be found.</p>

		</div>
		<a id="t1p3"><h4 class="clear">PART 3 PHYLOGENETIC  ANALYSIS OF A SPECIFIC GENE FAMILY</h4></a>
		<div class="subdiv">
			<div class="picture right" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/009_search_results.png"><img src="/webtools/trapid/img/tutorial/009_search_results.png" width="276" alt="Experiment Overview Image" /></a> <br /><strong>  FIGURE 9 SEARCH RESULTS FOR TERM <em>"CELL WALL"</em></strong> After searching on a GO description a list of corresponding go labels. from here quickly the associated sequences can be found.</div>
			<p>First look for the GO term <em>leaf senescence</em> and look at the genes assigned this label in the dataset. Look at <strong>contig04501</strong>, this transcript is annotated as quasi full length. Now click the gene family ID next to the transcript identifier, this will take you to a gene family page. Build the multiple sequence alignment for this gene family by clicking <em>Create multiple sequence alignment</em> in the Toolbox. After the alignment is generated, a link will appear to start JalView to view the sequence alignment, or download the alignment. For this tutorial click <em>View full multiple sequence alignment</em> to start JalView. From this alignment can be seen the transcript has indeed a good alignment with most of the other members in the family, though at the N-terminal end there likely is a portion missing (and hence is indeed quasi full length). The other transcript (<strong>contig20276</strong>) is the opposite, here the C-terminal end appears to be missing, potentially both contigs represent a single, split transcript.</p>
			<p>Go back and find <strong>contig01069</strong>, find the gene family page of this transcript and in the toolbox select <em>Create phylogenetic tree</em>. On the next page, settings for the tree need to be set. Switch the Editing from <em>Stringent editing</em> to <em>Relaxed editing</em> (optionally fewer species can be selected). Next click <em>Create phylogenetic tree</em>.  The job will be started and an e-mail will be send upon completion (usually within a few minutes). To view the tree, go back to the gene family page and click <em>Create phylogenetic tree</em> again. Now an ATV applet will be included on the page which shows the tree.</p>
						<div class="picture right" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/010_phylo_tree.png"><img src="/webtools/trapid/img/tutorial/010_phylo_tree.png" width="276" alt="Phylogenetic tree" /></a> <br /><strong>  FIGURE 10 PHYLOGENETIC TREE</strong> ATV applet displaying the phylogenetic tree of contig01069 and its orthologs.</div>
			<p>In case you don't see a tree or get an error message very often the number of sequences is to large and should be lowered by excluding some species or the editing is too stringent and the relaxed editing should be selected. On the next page an ATV applet will show the phylogenetic tree. Please note that before generating a new tree with different settings the Java Cache needs to be cleared (for detailed instructions please check <a href="http://www.java.com/en/download/help/plugin_cache.xml">http://www.java.com/en/download/help/plugin_cache.xml</a>).</p>
			<p>From the phylogenetic tree page, the tree can be <strong>downloaded in PhyloXML and Newick formats</strong>. </p>
			<p>Read more about <a href="../documentation/general#msa">multiple sequence alignments</a> and <a href="../documentation/general#tree">phylogenetic trees</a> in the general documentation</p>
		</div>
		<a id="t1p4"><h4 class="clear">PART 4 ADD TRANSCRIPT LABELS AND ANALYZE EXPERIMENT SUBSETS (WITHIN-TRANSCRIPTOME FUNCTIONAL ANALYSIS)</h4></a>
		<div class="subdiv">
			<div class="picture right" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/011_interpro_enrichment.png"><img src="/webtools/trapid/img/tutorial/011_interpro_enrichment.png" width="276" alt="InterPro enrichment" /></a> <br /><strong>  FIGURE 11 ENRICHED INTERPRO DOMAINS</strong> Overview of InterPro domain enrichted within the "Cell Cycle" subset</div>
			<p>If the data set is comprised of transcriptome data from different sources (with sources indicating different tissues, developmental types or stress conditions), then the user has the ability to assign labels to the subsets. This is done through the <em>import transcript labels</em> link on the experiment page. By doing so, several new analyses become available, such as comparison of functional annotation between different subsets, or by computing the enrichment factor compared to the overall transcriptome.</p>
			<p>In the example here a set of Cell Cycle genes (list can be downloaded from <a href="ftp://ftp.psb.ugent.be/pub/trapid/datasets/panicum/panicum_cell_cycle.lst">ftp://ftp.psb.ugent.be/pub/trapid/datasets/panicum/panicum_cell_cycle.lst</a> ). Add the labels to the dataset from the previous tutorial by clicking <em>Import data</em> next to <em>Import transcript labels</em>. On the next page, hit browse to select the file, enter a label (e.g. <em>Cell_cycle</em>) and click <em>Import labels</em>.</p>
			<p>Go back to the experiment overview page, in the <em>Toolbox</em> a number of new options are enabled. To check if this set is enriched for specific GO terms or Protein domains, click <em>GO enrichment from a subset compared to background</em> or <em>Protein domain enrichment from a set compared to background</em>.  For both GO and Protein domains, select the subset (here <em>Cell_cycle</em>) and the desired p-value and click <em>Compute enrichment</em>.</p>
			<p>Figure 10 shows the resulting page with for each of the enriched InterPro domains the fold enrichment, significance and a short description. Note that the InterPro domain codes  are hyperlinks to pages with more detailed information.</p>
			<p>Read more about <a href="../documentation/general#labels">labels</a> and <a href="../documentation/general#enrichment">functional enrichment</a> in the general documentation</p>
		</div>
		
		<a id="tutorial2"><h3 class="clear">TUTORIAL 2: CORRECTING FRAMESHIFTS USING FRAMEDP</h3></a>
		<div class="subdiv">
			<p>In this second tutorial we'll use a single <em>Panicum</em> transcript which containing one indel (small insertion or deletion causing a frameshift, in this case a deletion), create a new experiment called <em>"Tutorial 2"</em> using the same data as tutorial 1. Next, start the transcript processing as shown in the previous tutorial using <em>"Eudicots"</em> as the phylogenetic clade. Once the processing is finished, go to the "Experiment overview" page and select a transcript (e.g. <em>contig17160</em>. The transcript page will show the line <strong>"A putative frameshift was detected in this sequence"</strong>. To attempt to correct this frameshift, select <em>"Correct framseshifts with FrameDP"</em> in the <em>"Toolbox"</em>. The next page will ask if you want to correct additional genes from the same family. This is not necessary here, so continue by clicking <em>"Perform frameshift correction"</em>. A new job is started and after a while you'll receive a notification via e-mail. After completion, TRAPID will indicate if a frameshift was corrected or not (Figure 12) and on the transcript page the corrected ORF can be obtained (Figure 13). </p>
			<p>Read more about <a href="../documentation/general#frameshift">frameshift correction</a> in the general documentation</p>
			<div class="picture left" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/012_frameshift_correction.png"><img src="/webtools/trapid/img/tutorial/012_frameshift_correction.png" width="276" alt="Frameshift correction" /></a> <br /><strong>FIGURE 12 FRAMESHIFT CORRECTION.</strong> After FrameDP finishes the website will indicate a frameshift has succesfully been corrected.</div>
			<div class="picture right" style="width:278px;"> <a href="/webtools/trapid/img/tutorial/013_corrected_sequence.png"><img src="/webtools/trapid/img/tutorial/013_corrected_sequence.png" width="276" alt="corrected sequence" /></a> <br /><strong>FIGURE 13 CORRECTED ORF SEQUENCE.</strong> In case a frameshift could be corrected the corrected sequence is available from the transcript page</div>
			<p class="clear"> </p>
		
		</div>
	</div>
</div>


<!--
</body>
</html>
-->
