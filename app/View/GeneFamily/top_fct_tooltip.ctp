<?php

// GO categories to CSS class (`class`), single letter abbreviation (`text`) and full name (`title_text`).
$go_categories = [
    'BP' => ['class' => 'badge-go-bp', 'text' => 'P', 'title_text' => 'Biological Process'],
    'CC' => ['class' => 'badge-go-cc', 'text' => 'C', 'title_text' => 'Cellular Component'],
    'MF' => ['class' => 'badge-go-mf', 'text' => 'M', 'title_text' => 'Molecular Function']
];

if (isset($ref_gf_id)) {
    // Display GF functional category and description (`$func_data` is set only for eggnog reference DBs)
    if (isset($func_data)) {
        if (!empty($func_data)) {
            echo " <span class=\"label label-default\">" .
                $func_data['func_cat_id'] .
                '</span> <strong>' .
                $func_data['func_cat_label'] .
                '</strong> ; ';
            echo $func_data['description'];
        } else {
            echo "<span class='text-muted'><strong>No NOG functional data found.</strong></span>";
        }
        echo '<br>';
    }

    // Display top GOs
    if (isset($top_gos) && !empty($top_gos)) {
        echo '<strong>Functional data (GO): </strong><br>';
        $array_keys = array_keys($top_gos);
        $last_key = end($array_keys);
        foreach ($top_gos as $go_aspect => $go_data) {
            $classes = ['badge-go', $go_categories[$go_aspect]['class']];
            echo "<span class=\"" .
                implode(' ', $classes) .
                "\" title='" .
                $go_categories[$go_aspect]['title_text'] .
                "'>" .
                $go_categories[$go_aspect]['text'];
            echo '</span>&nbsp;';
            // echo ": ";
            foreach ($go_data as $go) {
                echo "<a class='linkout' target='_blank' href='https://www.ebi.ac.uk/QuickGO/term/" .
                    $go['name'] .
                    "' >" .
                    $go['desc'] .
                    '</a> ';
            }
            if ($go_aspect != $last_key) {
                echo '<br>';
            }
        }
    } else {
        echo "<span class='text-muted'><strong>No functional data (GO) found.</strong></span>";
    }

    // Display top IPRs (`$top_iprs` is set only for PLAZA reference DBs)
    if (isset($top_iprs)) {
        echo '<br>';
        if (!empty($top_iprs)) {
            echo '<strong>Functional data (InterPro): </strong><br>';
            foreach ($top_iprs as $ipr) {
                echo "<a class='linkout' target='_blank' href='https://www.ebi.ac.uk/interpro/entry/InterPro/" .
                    $ipr['name'] .
                    "' >" .
                    $ipr['desc'] .
                    '</a> ';
            }
        } else {
            echo "<span class='text-muted'><strong>No functional data (InterPro) found.</strong></span>";
        }
    }
}
?>
