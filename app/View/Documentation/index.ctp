<div class="container">
    <div class="page-header">
        <h1 class="text-primary">Documentation</h1>
    </div>
<!--<div class='subdiv'>-->
<section class="page-section-sm">
    <p class="text-justify">These pages contain the documentation for the TRAPID platform.
        If you think something is missing, or unclear, do not hesitate to <?php echo $this->Html->link("contact us", array("controller"=>"documentation","action"=>"contact")); ?>. Several sections are available within the documentation. Use the <strong>menu below</strong> to start browsing.</p>
</section>
	<section class="page-section-sm">
	<dl class='standard dl-horizontal'>
		<!--
		<dt>
			<?php echo $this->Html->link("Quick start",array("controller"=>"documentation","action"=>"quickstart")); ?>
		</dt>
		<dd>
			<span class='doctopspan'>A quick overview of how to use the TRAPID platform</span>
		</dd>
		-->

		<dt>
			<?php echo $this->Html->link("FAQ",array("controller"=>"documentation","action"=>"faq")); ?>
		</dt>
		<dd>
			<span class='doctopspan'>Frequently Asked Questions</span>
		</dd>

		<dt>
			<?php echo $this->Html->link("General documentation",array("controller"=>"documentation","action"=>"general")); ?>
		</dt>
		<dd>
			<span class='doctopspan'>A comprehensive overview of TRAPID's capabilities</span>
		</dd>
		<dt>
			<?php echo $this->Html->link("Tutorials",array("controller"=>"documentation","action"=>"tutorial")); ?>
		</dt>
		<dd>
			<span class='doctopspan'>Step-by-step examples of how to use the TRAPID platform</span>
		</dd>
		<dt>
			<a href="https://ftp.psb.ugent.be/pub/trapid">FTP Server</a>
		</dt>
		<dd>
			FTP server containing a multitude of example files.
		</dd>
        <dt>
            <?php echo $this->Html->link("Tools & parameters", array("controller"=>"documentation","action"=>"tools_parameters")); ?>
        </dt>
        <dd>
            An overview of all tools and parameters used within TRAPID
        </dd>

	</dl>
	</section>
	<br/><br/><br/>
	<?php
	// echo $this->Html->link("Return to TRAPID",array("controller"=>"trapid","action"=>"index"));
	?>

</div>
</div>
