<?php
    // If tooltip text is not set, just don't display anything...
    if(isset($tooltip_text)) {
        // Default variables, if nothing is set
        $data_placement = "top";
        $data_html = "false";
        // Set default span class but have it as variable to override it if needed
        $span_class = "glyphicon glyphicon-question-sign help-tooltip-icon";
        // If some parameters were set, override defaults...
        if(isset($override_span_class)) {
            $span_class = $override_span_class;
        }
        if(isset($use_html)) {
            $data_html = $use_html;
        }
        if(isset($tooltip_placement)) {
            $data_placement = $tooltip_placement;
        }
        echo "<span class=\"" . $span_class .
            "\" data-toggle=\"tooltip\" data-placement=\"" . $data_placement .
            "\" data-html=\"" . $data_html .
            "\" aria-hidden=\"true\" data-original-title=\"" . $tooltip_text . "\">";
        echo "</span>";
    }
?>