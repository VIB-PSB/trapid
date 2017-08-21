<div class="container">
<!--	<div style='float:right;width:100px;text-align:right;margin-right:100px;'>-->
<!--		<a class="mainref" href="/webtools/trapid/documentation/">Documentation</a>-->
<!--	</div>-->
<!--	<div style='clear:both;width:700px;font-size:8px;'>&nbsp;</div>-->
    <div class="page-header">
		<h1 class="text-primary">TRAPID FAQ</h1>
    </div>
	<div id="tutorial" style="width:900px;">

		<h2>General</h2>
		<div class="subdiv">
			<div class="faq_question"> - How many TRAPID experiments can I create?</div>
			<div class="faq_answer"> - Each user-account has the ability to create up to 10 different TRAPID experiments.</div>

			<div class="faq_question"> - I've lost my password. What to do next?</div>
			<div class="faq_answer"> - <a href='http://bioinformatics.psb.ugent.be/webtools/trapid/documentation/about'>Contact us</a> and we will send you a news password for your user-account. </div>

			<div class="faq_question"> - I think I found a bug. Can I report it?</div>
			<div class="faq_answer"> - Sure. Just <a href='http://bioinformatics.psb.ugent.be/webtools/trapid/documentation/about'>send us an email</a>, and we will investigate the issue.</div>
	
			<div class="faq_question"> - I want to share my experiment with my colleagues. Do I have to share my account information with them?</div>
			<div class="faq_answer"> - This is not necessary! If your colleagues also create a TRAPID account, you can easily share only a select number of experiments with them. This is done by following the 'Experiment access' link available on the top section of an experiment page.</div>

			<div class="faq_question">  - Is my data secure?</div>
			<div class="faq_answer"> - Yes. We have taken extensive measures to ensure that only authorized people have access to the user data.</div>

		</div>

		<h2>Input/Output</h2>
		<div class="subdiv">
			<div class="faq_question"> - What input files should I use?</div>
			<div class="faq_answer"> - TRAPID supports properly formatted multi-fasta files, with the '>' symbol indicating the transcript identifier of the following sequence (see also <a href='http://en.wikipedia.org/wiki/FASTA_format'>here</a>). In case the headers of the multi-fasta file consist of multiple sections separated by the '|' symbol, the first section will be used as unique identifier.</div>

			<div class="faq_question"> - How many sequences can I process at once?</div>
			<div class="faq_answer"> - The TRAPID system is able to process up to 200k transcripts within a single experiment. Adding more transcripts is possible, but correct processing is not guaranteed in this case.</div>

			<div class="faq_question"> - How can I upload subset label information?</div>
			<div class="faq_answer"> - On an experiment page, there is a link to 'Import transcript labels' in the 'Import/Export' section. Here,  you have to give - for each label - a file containing the transcript ids which should be associated with the indicated subset label.</div>

			<div class="faq_question"> - Can I export the TRAPID results in bulk?</div>
			<div class="faq_answer"> - Yes you can. The necessary download options can be found in the 'Import/Export' section of an experiment page.</div>

			<div class="faq_question"> - Can I analyze transcripts from multiple species simultaneously? </div>
			<div class="faq_answer"> - In case you use the labels to mark the origin of different transcripts (e.g. from species X and Y), you can analyze these two sets of transcripts in a combined manner in one TRAPID experiment. Note that you easily can upload multiple sequence files into one experiment; see General documentation, section Uploading transcript sequences and Job control. </div>

			

		</div>

		<h2>Analyzes</h2>
		<div class="subdiv">
			<div class="faq_question"> - How can I download an alignment and/or phylogenetic tree?</div>
			<div class="faq_answer"> - After the multiple sequence alignment (MSA) has been created for a given gene family, the user can both view the MSA and download the MSA in text-format, by following the alignment links within the toolbox on the gene family page. If a phylogenetic tree has been created, both the MSA and the tree will be downloadable by following the tree links within the toolbox on the gene family page. The phylogenetic tree will be downloadable in both newick format and phyloxml-format.</div>

			<div class="faq_question"> - Is it possible to automatically generate all phylogenetic trees within a TRAPID experiment?</div>
			<div class="faq_answer"> - Due to the heavy computational requirements for generating multiple thousands of multiple sequence alignments and phylogenetic trees, this is currently not possible. As such, creating phylogenetic trees can only be done on a per-gene family basis. </div>


			<div class="faq_question"> - Is it possible to automatically run FrameDP on each transcript within a TRAPID experiment?</div>
			<div class="faq_answer"> - FrameDP is extremely computationally expensive, and is such only offered on a per-gene family basis.</div>

		</div>

	</div>
</div>






