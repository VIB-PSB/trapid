<?php

/* This class represents the help_tooltips information shown within the `help_tooltips/create_tooltip` element */
class HelpTooltips extends AppModel
{

    /* Get and return tooltip text for a given `tooltip_id` */
    // TODO: replace by `find()`?
    function getTooltipText($tooltip_id) {
        $data_source = $this->getDataSource();
        $tooltip_id = $data_source->value($tooltip_id, 'string');
        $query = "SELECT `tooltip_text` FROM `help_tooltips` WHERE `tooltip_id`=" . $tooltip_id . ";";
        $res = $this->query($query);
        if($res) {
            return $res[0]["help_tooltips"]["tooltip_text"];
        }
        else {
            return null;
        }
    }


}