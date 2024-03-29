<?php

// GO categories to CSS class (`class`), single letter abbreviation (`text`) and full name (`title_text`).
$go_categories = [
    'BP' => ['class' => 'badge-go-bp', 'text' => 'P', 'title_text' => 'Biological Process'],
    'CC' => ['class' => 'badge-go-cc', 'text' => 'C', 'title_text' => 'Cellular Component'],
    'MF' => ['class' => 'badge-go-mf', 'text' => 'M', 'title_text' => 'Molecular Function']
];

// If no GO category is set, don't display anything
if (isset($go_category)) {
    // Default variables for styling (if nothing is set)
    $use_small_badge = false;
    $small_badge_class = 'badge-go-sm';
    $no_color_class = 'badge-go-nc';
    $use_no_color = false;
    // If some parameters were set, override defaults (i.e. colored badge and normal size
    if (isset($small_badge)) {
        $use_small_badge = $small_badge;
    }
    if (isset($no_color)) {
        $use_no_color = $no_color;
    }
    // Get all CSS classes
    $classes = ['badge-go'];
    if (!$use_no_color) {
        array_push($classes, $go_categories[$go_category]['class']);
    } else {
        array_push($classes, $no_color_class);
    }
    if ($use_small_badge) {
        array_push($classes, $small_badge_class);
    }
    echo "<span class=\"" .
        implode(' ', $classes) .
        "\" title='" .
        $go_categories[$go_category]['title_text'] .
        "'>" .
        $go_categories[$go_category]['text'];
    echo '</span>';
}
?>
