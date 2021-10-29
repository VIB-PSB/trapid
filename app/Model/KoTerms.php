<?php

/* This model represents KO (KEGG Orthology) terms */

class KoTerms extends AppModel {
    var $name	= 'KoTerms';
    var $useTable	= 'functional_data';


    // Retrieve description for an array of KO term identifiers `$ko_ids`.
    function retrieveKoInformation($ko_ids){
        $result	= [];
        if(is_null($ko_ids) || count($ko_ids) == 0) {
            return $result;
        }
        $ko_string = "('" . implode("','",$ko_ids) . "')";  // string to use in SQL 'IN' clause
        $query = "SELECT `name`, `desc` FROM `functional_data` WHERE `type`=\"ko\" AND `name` IN ". $ko_string . ";";
        $res = $this->query($query);
        foreach($res as $r){
            $s	= $r['functional_data'];
            $result[$s['name']] = array("desc"=>$s['desc']);
        }
        return $result;
    }

    // Check if a `$ko_id` string could be valid KO identifier (based on a pattern only, not checking the database)
    function isValidKoIdPattern($ko_id) {
        // A string composed of `K` + 5 digits is considered to be a valid ID
        $ko_pattern = "/^K[0-9]{5}$/i";
        return (bool) preg_match($ko_pattern, $ko_id);
    }

}