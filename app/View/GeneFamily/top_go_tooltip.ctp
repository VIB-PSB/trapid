<?php

// GO categories to CSS class (`class`), single letter abbreviation (`text`) and full name (`title_text`).
$go_categories = array(
    "BP"=>array("class"=>"badge-go-bp", "text"=>"P", "title_text"=>"Biological Process"),
    "CC"=>array("class"=>"badge-go-cc", "text"=>"C", "title_text"=>"Cellular Component"),
    "MF"=>array("class"=>"badge-go-mf", "text"=>"M", "title_text"=>"Molecular Function")
);

if(isset($ref_gf_id)) {
    if(isset($top_gos) && !empty($top_gos)) {
        echo "<strong>Functional data (GO): </strong><br>";
        $array_keys = array_keys($top_gos);
        $last_key = end($array_keys);
        foreach ($top_gos as $go_aspect=>$go_data) {
            $classes = array("badge-go", $go_categories[$go_aspect]['class']);
            echo "<span class=\"" . implode(" ", $classes) . "\" title='" . $go_categories[$go_aspect]["title_text"] . "'>" . $go_categories[$go_aspect]['text'];
            echo "</span>&nbsp;";
            // echo ": ";
            foreach ($go_data as $go) {
                echo "<a class='linkout' target='_blank' href='https://www.ebi.ac.uk/QuickGO/term/" . $go['name'] . "' >" . $go['desc'] . "</a> ";
            }
            if($go_aspect != $last_key) {
                echo "<br>";
            }
        }
    }
    else {
        echo "No functional data (GO) found. ";
    }
}
?>