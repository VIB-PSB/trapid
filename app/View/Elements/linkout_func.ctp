<?php

// Map linkout names to actual URLs, text, and `title` attribute text.
// Add other linkouts when other types of annotations become available within TRAPID
$linkout_map = array(
    "quickgo"=>array("url"=>"https://www.ebi.ac.uk/QuickGO/term/", "text"=>"QuickGO", "title_text"=>"View GO term in QuickGO"),
    "amigo"=>array("url"=>"http://amigo2.berkeleybop.org/amigo/term/", "text"=>"AmiGO", "title_text"=>"View GO term in AmiGO"),
    "interpro"=>array("url"=>"http://www.ebi.ac.uk/interpro/entry/", "text"=>"InterPro", "title_text"=>"View protein domain in InterPro")
);

// If provided linkout type is not set or not in the keys defined above, or we don't have any query term, display nothing
if(isset($linkout_type) && isset($query_term) && array_key_exists ($linkout_type, $linkout_map)) {
    echo "<a title=\"". $linkout_map[$linkout_type]["title_text"]  . "\" target=\"_blank\" href=\"" . $linkout_map[$linkout_type]["url"] . $query_term . "\" style=\"margin-left:0px;\"><span class=\"btn btn-primary btn-xs\">" . $linkout_map[$linkout_type]["text"] . "  <span class=\"glyphicon glyphicon-new-window\"></span></span></a>";
}
else {
    pr("Cannot display linkout. Are `\$linkout_type` and `\$query_term` correctly defined?");
}

?>