<div class="container">
<!--div style="min-height:500px;"-->
  <div class="page-header">
    <h1 class="text-primary">Welcome<!-- to TRAPID --></h1>
  </div>
    <section class="page-section">
		<p class="text-justify lead">TRAPID is an online tool for the fast, reliable and user-friendly analysis of de novo transcriptomes. </p>
	  <p class="text-justify">Through a highly optimized processing pipeline the TRAPID system offers functional and comparative analyses for transcriptome data sets. TRAPID is highly competitive with respect to other existing solutions with regards to both speed and quality.</p>
    </section>
    <section class="page-section">
		<h2>TRAPID features</h2>
    <p class="text-justify">TRAPID currently offers the following features:</p>
		  <ul>
			  <li>Allows each user to have up to 10 different working sets, each allowing up to a 200,000 putative transcripts</li>
			  <li>Allows the user to select a reference database of choice; currently >170 genomes are available through PLAZA 2.5 and OrthoMCLDB version 5.0</li>
			  <li>Assign each transcript to a reference gene family or orthologous group.</li>
			  <li>Transfer functional annotation based on homology/orthology information for each transcript</li>
			  <li>Perform gene family-based analyses such as multiple sequence alignments and phylogenetic tree construction</li>
			  <li>Performs functional GO enrichment analysis of subsets</li>
			  <li>Extensive editing and export capabilities</li>
			  <li>Free of charge for academic use</li>
		  </ul>
      <p class="text-justify">More information about these features and a comprehensive overview of the TRAPID capabilities can be found in the 			<?php echo $html->link("documentation",array("controller"=>"documentation","action"=>"general")); ?>. </p>
    </section>
		<!--div class="subdiv" style="width:800px;margin-top:200px;">
			<center-->
			<?php
      /*
				echo $html->link("Login",array("controller"=>"trapid","action"=>"authentication"),
							array("class"=>"startlink"));
				echo "<span class='line'>&#8226;</span>\n";
				echo $html->link("Register",array("controller"=>"trapid","action"=>"authentication","registration"),
							array("class"=>"startlink"));
				echo "<span class='line'>&#8226;</span>\n";
				echo $html->link("Documentation",array("controller"=>"documentation","action"=>"index"),
							array("class"=>"startlink","target"=>"_blank"));
				echo "<span class='line'>&#8226;</span>\n";
				//echo "<a href='mailto:plaza@psb.vib-ugent.be' class='startlink'>Contact</a>\n";
				echo $html->link("About",array("controller"=>"documentation","action"=>"about"),
							array("class"=>"startlink","target"=>"_blank"));
      */
			?>
			<!--/center>
		</div-->



	    	<!--
		<h4>Authentication</h4>
		<div class="subdiv">
			<?php
			echo $form->create("Authentication",array("url"=>array("controller"=>"trapid","action"=>"authentication"),
				"type"=>"post"));
			?>
	    		<input type="text" name="login"/><span style="padding-left:5px;">User login</span><br/><br/>
	    		<input type="password" name="password"/><span style="padding-left:5px;">Password</span><br/><br/>
	    		<input type="submit" value="Submit" />	 &nbsp;&nbsp;
			<?php
			echo $html->link("Registration",array("controller"=>"trapid","action"=>"authentication","registration"));
			?>
			</form>
		</div>
		-->
	</div>
    </div>
<!--/div-->
</div>