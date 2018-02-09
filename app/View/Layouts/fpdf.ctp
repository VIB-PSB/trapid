<?php
    if(isset($pdf_file_name)) {
        $file_name = $pdf_file_name;
    }
    else {
        $file_name = "downloaded.pdf";
    }
?>
<?php header('Content-Disposition: attachment; filename="'. $file_name .'"');
    echo $content_for_layout;
?>