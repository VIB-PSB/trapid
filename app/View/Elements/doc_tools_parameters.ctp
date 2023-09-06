<?php
$data_prefixes = [
    'source_link' => 'Source: ',
    'web_link' => 'Website: ',
    'version' => 'Version: ',
    'parameters' => 'Parameters/command-line: ',
    'extra' => ''
];
$ref_keys = ['ref_title', 'ref_authors', 'ref_url', 'ref_journal'];

if (isset($tool_data)) {
    echo "<section class='page-section-sm'>\n";
    echo '<h4>' . $tool_data['name'] . "</h4>\n";
    if (sizeof(array_intersect($ref_keys, array_keys($tool_data))) == sizeof($ref_keys)) {
        echo $this->element('doc_paper', [
            'title' => $tool_data['ref_title'],
            'authors' => $tool_data['ref_authors'],
            'url' => $tool_data['ref_url'],
            'journal' => $tool_data['ref_journal']
        ]);
    }
    $links_arr = array_intersect(['source_link', 'web_link'], array_keys($tool_data));
    if (sizeof($links_arr) > 0) {
        echo "<p class='text-justify'>";
        foreach ($links_arr as $k) {
            $link = explode(';', $tool_data[$k]);
            $link_txt = $link[0];
            $link_href = $link[1];
            echo '<strong>' .
                $data_prefixes[$k] .
                '</strong>' .
                "<a class='linkout' href='" .
                $link_href .
                "' target='_blank'>" .
                $link_txt .
                '</a><br>';
        }
        echo '</p>';
    }
    $params_arr = array_intersect(['version', 'parameters', 'extra'], array_keys($tool_data));
    if (sizeof($params_arr) > 0) {
        echo '<ul>';
        foreach ($params_arr as $k) {
            echo '<li><strong>' . $data_prefixes[$k] . '</strong>' . $tool_data[$k] . '</li>';
        }
        echo '</ul>';
    }
    echo '</section>';
}
?>
