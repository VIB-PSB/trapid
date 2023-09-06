<?php
if (isset($aln)) {
    header('Access-Control-Allow-Origin: *');
    header('Content-type: text/plain');
    $msa_aln = $aln;
    $msa_aln = str_replace('>', "\n>", $msa_aln);
    $msa_aln = str_replace(';', "\n", $msa_aln);
    $msa_aln = trim($msa_aln);
    echo $msa_aln;
}
?>
