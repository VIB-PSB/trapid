<?php

/*
 * This model represents experiment cleanup dates (each DB record represents a cleanup and its date).
 */

class CleanupDate extends AppModel {
    var $name = 'CleanupDate';
    var $useTable = 'cleanup_date';

    /* Return id of a cleanup date record for a given year and month. If a record does not exist, insert it (and return -1). */
    function checkDateStatus($year, $month) {
        $query = "SELECT `id` FROM `cleanup_date` WHERE `year`='" . $year . "' AND `month`='" . $month . "' ";
        $res = $this->query($query);
        if ($res) {
            return $res[0]['cleanup_date']['id'];
        } else {
            $insert_query = "INSERT INTO `cleanup_date` (`year`,`month`) VALUES ('" . $year . "','" . $month . "') ";
            $this->query($insert_query);
            return -1;
        }
    }
}
