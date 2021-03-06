<div class="container">
  <div class="page-header">
    <h1 class="text-primary">Welcome<!-- to TRAPID --></h1>
  </div>
            <section class="page-section">
                <p class="text-justify lead">TRAPID is an online tool for the fast, reliable and user-friendly analysis of de novo transcriptomes. </p>
              <p class="text-justify">Through a highly optimized processing pipeline the TRAPID system offers functional and comparative analyses for transcriptome data sets. TRAPID is highly competitive with respect to other existing solutions with regards to both speed and quality.</p>
            </section>
    <div class="row">
        <div class="col-md-9 col-sm-8">
            <section class="page-section-xs">
                <h2>TRAPID features</h2>
                <p class="text-justify">TRAPID 2.0 currently offers the following features:</p>
                  <ul>
                      <li>Allow each user to have up to <?php echo $max_user_experiments; ?> different working sets, each allowing up to a 200,000 putative transcripts</li>
                      <li>Allow the user to select a reference database of choice; currently >2,000 genomes are available through PLAZA and EggNOG version 4.5</li>
                      <li>Homology-supported ORF finding supporting non-canonical genetic codes</li>
                      <li>Infer taxonomic classification of transcript sequences</li>
                      <li>Identify and annotate potential non-coding RNAs</li>
                      <li>Assign each transcript to a reference gene family or orthologous group.</li>
                      <li>Transfer functional annotation based on homology/orthology information for each transcript</li>
                      <li>Perform gene family-based analyses such as multiple sequence alignments and phylogenetic tree construction</li>
                      <li>Perform functional enrichment analysis of subsets</li>
                      <li>Extensive editing and export capabilities</li>
                      <li>Free of charge for academic use</li>
                  </ul>
              <p class="text-justify">More information about these features and a comprehensive overview of the TRAPID capabilities can be found in the 			<?php echo $this->Html->link("documentation",array("controller"=>"documentation","action"=>"general")); ?>. </p>
            </section>
        </div>
        <div class="col-md-3 col-sm-4 hidden-xs">
            <div style="height:27px;"></div>
            <ul class="nav nav-tabs nav-justified" id="tabs" data-tabs="tabs">
                <li class="active"><a href="#news" data-toggle="tab">News</a></li>
                <li><a href="#twitter-feed" data-toggle="tab">Twitter feed</a></li>
                <!--<li><a href="#raw-data" data-toggle="tab">Raw data</a></li>-->
            </ul>
            <div class="tab-content">
                <div id="news" class="tab-pane active"><br>
                    <p class="text-justify small">
                        <strong>2021-05-05:</strong> fixed a bug causing some protein sequence export files to be truncated (due to transcripts having no predicted ORF sequence).
                    </p>
                    <hr>
                    <p class="text-justify small">
                        <strong>2021-04-22:</strong> fixed a bug that was causing an incorrect number of transcripts to be selected when creating subsets from the taxonomic classification visualizations.
                    </p>
                    <hr>
                    <p class="text-justify small">
                        <strong>2021-03-23:</strong> a new reference database is available, <a href="https://bioinformatics.psb.ugent.be/plaza/versions/plaza_diatoms_01/" class="linkout" target="_blank">PLAZA diatoms 1.0</a>!
                    </p>
                    <hr>
                </div>
                <div id="twitter-feed" class="tab-pane">
                    <div style="height:100px;">
                        <section class="page-section">
                            <a class="twitter-timeline" data-dnt="true" data-lang="en" data-height="500px" data-theme="light" data-link-color="#0c84e4" href="https://twitter.com/trapid_genomics?ref_src=twsrc%5Etfw">Loading tweets...</a> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
                        </section>
                    </div>
                </div>
            </div>
    </div>
</div>