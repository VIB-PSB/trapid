<div class="page-header">
    <h1 class="text-primary">Preprocess functional enrichments</h1>
</div>

<section class="page-section-sm">
    <p class="text-justify">The preprocessing of functional enrichments within the TRAPID framework is <strong>only available when at least 1 transcript subset is defined</strong>: indeed, the enrichments are computed by taking the ratio of transcripts assigned to a label, with the transcripts in the entire data set. </p>
    <p class="text-justify">Preprocessing the functional enrichments is required when viewing the Sankey diagrams (computing enrichments on-the-fly would make loading the Sankey diagrams very long).
    Functional enrichments will be performed using all available subsets and functional annotation types. </p>
    <p class="text-justify"><strong>Nb: </strong>the preprocessing of the functional enrichments is not done during the 'initial processing' phase because labels can be added afterwards, and they do not play a role in the initial processing.</p>
</section>

<section class="page-section-sm">
    <?php
    echo $this->Form->create(false,array("url"=>array("controller"=>"trapid","action"=>"enrichment_preprocessing",$exp_id),"type"=>"post"));
    ?>
    <p class="text-center">
        <input class='btn btn-primary' type="submit" value="Run enrichment preprocessing (<?php echo $num_subsets;?>)"/>
    </p>
    <?php echo $this->Form->end();?>
</section>
