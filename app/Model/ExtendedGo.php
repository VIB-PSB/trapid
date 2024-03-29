<?php
/*
 * A model for reference database GO annotation.
 */

class ExtendedGo extends AppModel {
    var $useTable = 'functional_data';

    function getDepthsPerCategory() {
        $result = [];
        // The 'info' field contains the category of GO terms for GO functional data.
        $query = "SELECT `info` , MAX( `num_sptr_steps` ) AS max FROM `functional_data`
			WHERE `type`='go' AND `is_obsolete` = '0' GROUP BY `info`";
        $res = $this->query($query);
        foreach ($res as $r) {
            // $type	= $r['extended_go']['type'];
            $type = $r['functional_data']['info'];
            $max = $r[0]['max'];
            $result[$type] = $max;
        }
        return $result;
    }

    function retrieveGoInformation($go_ids) {
        $result = [];
        if ($go_ids == null || count($go_ids) == 0) {
            return $result;
        }
        $go_string = "('" . implode("','", $go_ids) . "')";
        // $query	= "SELECT * FROM `extended_go` WHERE `go` IN ".$go_string;
        $query = "SELECT * FROM `functional_data` WHERE `type`=\"go\" AND `name` IN " . $go_string;
        $res = $this->query($query);
        foreach ($res as $r) {
            // $s	= $r['extended_go'];
            // $result[$s['go']] = array("desc"=>$s['desc'],"type"=>$s['type']);
            $s = $r['functional_data'];
            $result[$s['name']] = ['desc' => $s['desc'], 'type' => $s['info']];
        }
        return $result;
    }

    /**
     * Retrieve the root GO terms (GO:0005575 [CC], GO:0003674 [MF], GO:0008150 [BP])
     *
     * @return array Root-level GO terms
     *
     */
    function getRootGoTerms() {
        $result = [];
        $query_result = $this->find('all', [
            'fields' => 'name',
            'conditions' => ['type' => 'go', 'is_obsolete' => 0, 'num_sptr_steps' => 0]
        ]);
        if ($query_result) {
            $result = array_map(function ($qr) {
                return $qr['ExtendedGo']['name'];
            }, $query_result);
        }
        return $result;
    }

    // Check if a `$go_id` string could be valid GO identifier (based on a pattern only, not checking the database)
    function isValidGoIdPattern($go_id) {
        // A string composed of `GO:` + 7 digits is considered to be a valid ID
        $go_pattern = "/^GO:[0-9]{7}$/i";
        return (bool) preg_match($go_pattern, $go_id);
    }

    // TODO: remove unused function
    // function getGeneralProfile($go_ids) {
    //     if (!is_array($go_ids)) {
    //         $go_ids = [$go_ids];
    //     }
    // }
}
