<div style='min-height:400px;'>
<h2>Quick start documentation</h2>
<div style='text-align:right;margin-right:20px;'>
	<?php echo $html->link("Documentation",array("controller"=>"documentation","action"=>"index"));?>
</div>
<div class='subdiv' style='width:900px;'>
	This quick start section will give, through a limited set of steps to follow, a basic insight in how to process your transcriptome datasets with the TRAPID platform. For more detailed documentation, please refer to the “General Documentation” section.
	<br/><br/>
	The TRAPID platform is available at <a href='http://bioinformatics.psb.ugent.be/webtools/trapid'>http://bioinformatics.psb.ugent.be/webtools/trapid</a> <br/><br/>
	A PDF-version of the documentation can be found <?php echo $html->link("here",$html->url("/files/TRAPID_quickstart.pdf",true)); ?> 
	<br/>
	

	<h3>1. User authentication</h3>
	<div class='subdiv3'>
	 	The TRAPID platform for the fast analysis of transcriptome data sets stores the data of users at the server. To limit access to this data, each user needs to register with the TRAPID platform in order to receive a username and password. Using these credentials, unauthorized access is impossible.
		<ol>
			<li>Go to the website</li> 
			<li>Click on the “register” link at the bottom </li>
			<li>Provide a valid email-address and other necessary information </li>
			<li>An email will be send to you, with your password. Your email-address will be used as login </li>
			<li>Use this email-address and password to login into the TRAPID platform</li>
		</ol>

	</div>	

	<h3>2. Create a TRAPID transcriptome experiment</h3>
	<div class='subdiv3'>
		Each user can have up to five different transcriptome data sets at the same time within the TRAPID platform. In order to differentiate between these data sets, the user must create a so-called “TRAPID experiment”.
		<ol>
			<li>Login into the TRAPID platform </li> 
			<li>Provide a name and description for your data set </li>
			<li>Indicate which reference database you would like to use. If the transcriptome data contains transcripts from a plant species, we recommend to use the PLAZA resource as reference, otherwise the OrthoMCLDB resource</li>
			<li>Create the experiment</li>
			<li>The newly created experiment is now present in the table. Click on the name to gain access to the experiment page</li>
		</ol>		
	</div>

	<h3>3. Uploading transcriptome data</h3>
	<div class='subdiv3'>
		The TRAPID experiment does not contain transcriptome data yet. The TRAPID platform supports upload of transcriptome data in multi-FASTA format. The potential large size of the transcriptome data files is offset by the ability of users to upload compressed files as well, using either zip or gzip.
		<ol>
			<li>Login into the TRAPID platform, and select the desired experiment </li> 
			<li>Click on the “import data” link </li>
			<li>Select the file containing the transcriptome data set  </li>
			<li>Click on “import transcript sequences” </li>	
		</ol>
		In  case the transcriptome data set is split across several multi-fasta files, the user has the ability to perform this process within the same experiment several times, by following steps 2 to 4 again.
	</div>

	<h3>4. Process the transcriptome data</h3>
	<div class='subdiv3'>
		Performing functional comparisons, GO enrichment,…  can be achieved by defining subsets within the experiment (for example based on tissues, stress-conditions or developmental stage). 	
		<ol>
			<li>Login into the TRAPID platform, and select the desired experiment </li> 
			<li>Click on the link “Perform transcript processing”, under the header “Initial Processing” </li>
			<li>
				Select the necessary options: 
				<ol>
					<li>Similarity search database type: if the organism from which the transcripts originate has a very close relative species or clade available from within the list, choose this. If unknown, choose the gene family representatives</li>
					<li>Similarity search database: see previous point</li>
					<li>E-value: E-value to be used during the similarity search</li>
					<li>Gene family type:  If a single species is selected as similarity search database type (using PLAZA as reference database), then the Integrative Orthology groups will provide the same or better coverage, but with smaller groups, than the otherwise selected gene family clusters.</li>
				</ol>
			</li>
			<li>Click on the “start transcriptome pipeline” button</li>			
		</ol>
	</div>

	<h3>5. Perform custom analyses</h3>	
	<div class='subdiv3'>
		With the processing having been completed by the TRAPID platform, the user now has the ability to perform many custom analyses, or view statistics, etc… <br/>
		For example:<br/><br/>
		<h4>Define and use subsets within the experiment</h4>
		By defining subsets within the experiment (for example based on tissues, stress-conditions or developmental stage), several new features become available, such as functional comparisons, GO enrichment, etc.. <br/>
To define a subset within an experiment:<br/>
		<ol>
			<li>Login into the TRAPID platform, and select the desired experiment</li>
			<li>Click on the link “import data” under the “import/export” header</li>	
			<li>Give a file in the desired format, and give a proper name for the subset.</li>
		</ol>
		Afterwards, you will be notified how many transcripts have been annotated with the indicated label.
		<br/><br/>
		<h4>Create phylogenetic trees</h4>	
		<ol>
			<li>Login into the TRAPID platform, and select the desired experiment</li>
			<li>Select the transcript, either through the search function, or through any link in the platform</li>
			<li>On the transcript page, select the associated gene family</li>
			<li>On the gene family page, select the “create phylogenetic tree” from the toolbox</li>
			<li>Select the reference species from the reference database you want to include in the tree</li>

		</ol>

	</div>
</div>
</div>
