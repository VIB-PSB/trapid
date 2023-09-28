<?php

/* This class represents the help_tooltips information shown within the `help_tooltips/create_tooltip` element */

class HelpTooltips extends AppModel {
    /* Get and return tooltip text for a given tooltip id */
    function getTooltipText($tooltip_id) {
        $tooltip_text_data = $this->find('first', [
            'conditions' => ['tooltip_id' => $tooltip_id],
            'fields' => ['tooltip_text']
        ]);
        if (!$tooltip_text_data) {
            return null;
        }
        return $tooltip_text_data['HelpTooltips']['tooltip_text'];
    }
}
