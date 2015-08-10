<div>
<h2>Documentation</h2>
<div class='subdiv'>
	
	These pages contain the documentation for the TRAPID platform for the fast analysis of transcriptome data. <br/>
	If you think something is missing, or unclear, do not hesitate to <a href='mailto:plaza@psb.vib-ugent.be'>contact us</a>. <br/>
	Several sections are available within the documentation: <br/><br/>
	<div style='margin-left:40px;'>
	<dl class='standard'>		
		<!--
		<dt>		
			<?php echo $html->link("Quick start",array("controller"=>"documentation","action"=>"quickstart")); ?>
		</dt>
		<dd>
			<span class='doctopspan'>A quick overview of how to use the TRAPID platform</span>
		</dd>
		-->
		
		<dt>
			<?php echo $html->link("FAQ",array("controller"=>"documentation","action"=>"faq")); ?>
		</dt>
		<dd>
			<span class='doctopspan'>Frequently Asked Questions</span>
		</dd>
		
		<dt>		
			<?php echo $html->link("General documentation",array("controller"=>"documentation","action"=>"general")); ?>
		</dt>
		<dd>
			<span class='doctopspan'>A comprehensive overview of the TRAPID capabilities</span>
		</dd>		
		<dt>
			<?php echo $html->link("Tutorials",array("controller"=>"documentation","action"=>"tutorial")); ?>
		</dt>
		<dd>
			<span class='doctopspan'>Step-by-step examples of how to use the TRAPID platform</span>
		</dd>			
		<dt>
			<a href="ftp://ftp.psb.ugent.be/pub/trapid">FTP Server</a>
		</dt>
		<dd>
			FTP-server containing a multitude of example files.
		</dd>
		
	</dl>	
	</div>
	<br/><br/><br/>
	<?php
	echo $html->link("Return to TRAPID",array("controller"=>"trapid","action"=>"index")); 
	?>
	
</div>
</div>
