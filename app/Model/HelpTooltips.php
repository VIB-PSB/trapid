<?php

/* This class represents the help_tooltips information shown within the `help_tooltips/create_tooltip` element */
class HelpTooltips extends AppModel
{

    /* Get and return tooltip text for a given `tooltip_id` */
    function getTooltipText($tooltip_id) {
        $tooltip_id = mysql_real_escape_string($tooltip_id);
        $query = "SELECT `tooltip_text` FROM `help_tooltips` WHERE `tooltip_id`='" . $tooltip_id . "';";
        $res = $this->query($query);
        if($res) {
            return $res[0]["help_tooltips"]["tooltip_text"];
        }
        else {
            return null;
        }
    }


}