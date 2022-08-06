<?php

/*
 * A model for reference database protein domains (i.e. InterPro) functional annotation.
 */

class ProteinMotifs extends AppModel {
    var $useTable = 'functional_data';

    function retrieveInterproInformation($interpro_ids) {
        $result = [];
        if ($interpro_ids == null || count($interpro_ids) == 0) {
            return $result;
        }
        $interpro_string = "('" . implode("','", $interpro_ids) . "')";
        // $query	= "SELECT * FROM `protein_motifs` WHERE `motif_id` IN ".$interpro_string;
        $query = "SELECT * FROM `functional_data` WHERE `type`=\"interpro\" AND `name` IN " . $interpro_string;
        $res = $this->query($query);
        foreach ($res as $r) {
            // $s	= $r['protein_motifs'];
            // $result[$s['motif_id']] = array("desc"=>$s['desc']);
            $s = $r['functional_data'];
            $result[$s['name']] = ['desc' => $s['desc']];
        }
        return $result;
    }

    // Check if a `$ipr_id` string could be valid InterPro identifier (based on a pattern only, not checking the database)
    function isValidIprIdPattern($ipr_id) {
        // A string composed of `IPR` + 6 digits is considered to be a valid ID
        $ipr_pattern = "/^IPR[0-9]{6}$/i";
        return (bool) preg_match($ipr_pattern, $ipr_id);
    }
}
