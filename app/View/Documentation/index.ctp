<div class="container">
	<div class="page-header">
		<h1 class="text-primary">Documentation</h1>
	</div>
	<section class="page-section-sm">
		<p class="text-justify">These pages contain the documentation for the TRAPID platform.
			If you think something is missing or unclear, do not hesitate to
			<?php echo $this->Html->link('contact us', ['controller' => 'documentation', 'action' => 'contact']); ?>.
			Several sections are available within the documentation. Use the <strong>menu below</strong> to start browsing.
		</p>
	</section>
	<section class="page-section-sm">
		<dl class="dl-horizontal">
			<dt>
				<?php echo $this->Html->link('FAQ', ['controller' => 'documentation', 'action' => 'faq']); ?>
			</dt>
			<dd>Frequently Asked Questions</dd>
			<dt>
				<?php echo $this->Html->link('General documentation', ['controller' => 'documentation', 'action' => 'general']); ?>
			</dt>
			<dd>A comprehensive overview of TRAPID's capabilities</dd>
			<dt>
				<?php echo $this->Html->link('Tutorials', ['controller' => 'documentation', 'action' => 'tutorial']); ?>
			</dt>
			<dd>Step-by-step examples of how to use the TRAPID platform</dd>
			<dt>
				<a href="https://ftp.psb.ugent.be/pub/trapid">FTP Server</a>
			</dt>
			<dd>FTP server containing a multitude of example files.</dd>
			<dt>
				<?php echo $this->Html->link('Tools & parameters', ['controller' => 'documentation', 'action' => 'tools_parameters']); ?>
			</dt>
			<dd>An overview of all tools and parameters used within TRAPID</dd>
		</dl>
	</section>
</div>
