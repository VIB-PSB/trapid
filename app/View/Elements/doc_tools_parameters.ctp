<?php if(isset($tool_data)): ?>
<section class="page-section-sm">

<?php
    // Should we define this somewhere else than in the element?
    $data_prefixes = array("source_link"=>"Source: ", "web_link"=> "Website: ", "version"=>"Version: ", "parameters"=>"Parameters/command-line: ", "extra"=>"");
?>

<h4><?php echo $tool_data['name']?></h4>

<?php
    // Show reference if relevant
    $ref_keys = ['ref_title', 'ref_authors', 'ref_url', 'ref_journal'];
    if(sizeof(array_intersect($ref_keys, array_keys($tool_data))) == sizeof($ref_keys)) {
        echo $this->element("doc_paper",
            array("title"=>$tool_data['ref_title'], "authors"=>$tool_data['ref_authors'],
                   "url"=>$tool_data['ref_url'], "journal"=>$tool_data['ref_journal']));
    }

    $links_arr = array_intersect(array("source_link", "web_link"), array_keys($tool_data));
    if(sizeof($links_arr) > 0) {
        echo "<p class='text-justify'>";
        foreach($links_arr as $k) {
            $link = explode(';', $tool_data[$k]);
            $link_txt = $link[0];
            $link_href = $link[1];
            echo "<strong>" . $data_prefixes[$k] . "</strong>" . "<a class='linkout' href='" . $link_href . "' target='_blank'>" . $link_txt . "</a><br>";
        }
        echo "</p>";
    }

    $params_arr = array_intersect(array("version", "parameters", "extra"), array_keys($tool_data));
    if(sizeof($params_arr) > 0) {
    echo "<ul>";
    foreach($params_arr as $k) {
        echo "<li><strong>" . $data_prefixes[$k] . "</strong>" . $tool_data[$k] . "</li>";
    }
    echo "</ul>";
    }
?>

</section>
<?php endif; ?>
