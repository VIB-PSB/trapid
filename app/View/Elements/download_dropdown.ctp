<?php
// Add class to `.dropdown-menu` ul element if `$align_right` is true
// This aligns the dropdown menu options to the right
$dropdown_menu_class = "dropdown-menu";
if(isset($align_right) && $align_right) {
    $dropdown_menu_class = "dropdown-menu dropdown-menu-right";
}
?>

<form action='<?php echo $download_url; ?>' method='post' id="download-form">
<input type="hidden" id="download_type" name="download_type"/>
<div class="dropdown" id="download-dropdown">
    <button class="btn btn-default btn-sm dropdown-toggle" type="button" id="download-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <span class="glyphicon glyphicon-download-alt"></span> Download &nbsp;
        <span class="caret"></span>
    </button>
    <ul class="<?php echo $dropdown_menu_class; ?>" aria-labelledby="download-btn" id="download-list">
        <li><a id="download-table">Table content (TSV)</a></li>
        <li role="separator" class="divider"></li>
<!--        <li class="dropdown-header">Sequences</li>-->
        <li><a id="download-fasta_transcript">Transcripts (FASTA)</a></li>
        <li><a id="download-fasta_orf">Predicted ORFs (FASTA)</a></li>
        <?php if(isset($allow_reference_aa_download)) : ?>
        <li><a id="download-fasta_protein_ref">Proteins (incl. reference, FASTA)</a></li>
        <?php endif; ?>
    </ul>
</div>
</form>
<script type="text/javascript">
    var downloadLinks = document.querySelectorAll("#download-list a");
    var downloadForm = document.getElementById("download-form");
    var hiddenField = document.getElementById("download_type");  // Kept the underscore

    function downloadData(downloadLink, hiddenField, downloadForm) {
        // Get download type value from id of 'a' element
        var dlId = downloadLink.id;
        var dlType = dlId.split("-")[1];
        // Set download type value and submit download form
        hiddenField.value = dlType;
        downloadForm.submit();
    }

    for (var i = 0; i < downloadLinks.length; i++) {
        downloadLinks[i].addEventListener('click', function(event) {
            downloadData(this, hiddenField, downloadForm);
        });
    }
</script>
