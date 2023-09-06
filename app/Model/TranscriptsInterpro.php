<?php

/*
 * This model represents Protein domain / InterPro information associated to the transcripts
 */

class TranscriptsInterpro extends AppModel {
    var $useTable = 'transcripts_annotation';

    function findInterproCountsFromTranscripts($exp_id, $transcript_ids) {
        $result = [];
        $transcripts_string = "('" . implode("','", $transcript_ids) . "')";
        $query =
            "SELECT `name`,COUNT(`transcript_id`) as `count` FROM `transcripts_annotation` WHERE `type`='ipr' AND `experiment_id`='" .
            $exp_id .
            "' AND `transcript_id` IN " .
            $transcripts_string .
            ' GROUP BY `name` ORDER BY `count` DESC';
        // Trapid db structure update
        // $query = "SELECT `interpro`,COUNT(`transcript_id`) as `count` FROM `transcripts_interpro` WHERE `experiment_id`='".$exp_id."' AND `transcript_id` IN ".$transcripts_string." GROUP BY `interpro` ORDER BY `count` DESC";
        $res = $this->query($query);
        foreach ($res as $r) {
            $go = $r['transcripts_annotation']['name'];
            $count = $r[0]['count'];
            $result[$go] = $count;
        }
        return $result;
    }

    function findTranscriptsFromInterpro($exp_id, $ipr_terms) {
        $result = [];
        $ipr_terms_string = "('" . implode("','", array_keys($ipr_terms)) . "')";
        $query =
            "SELECT `name`,count(`transcript_id`) as count FROM `transcripts_annotation` WHERE `experiment_id`='" .
            $exp_id .
            "' AND `type`='ipr' AND `name` IN " .
            $ipr_terms_string .
            ' GROUP BY `name` ';
        $res = $this->query($query);
        foreach ($res as $r) {
            $result[$r['transcripts_annotation']['name']] = [
                'count' => $r[0]['count'],
                'desc' => $ipr_terms[$r['transcripts_annotation']['name']],
            ];
        }
        return $result;
    }

    function getStats($exp_id) {
        $query =
            "SELECT COUNT(DISTINCT(`name`)) as count1, COUNT(DISTINCT(`transcript_id`)) as count2 FROM `transcripts_annotation` WHERE `experiment_id`='" .
            $exp_id .
            "' AND `type`='ipr' ";
        $res = $this->query($query);
        $result = ['num_interpro' => 0, 'num_transcript_interpro' => 0];
        if ($res) {
            $result['num_interpro'] = $res[0][0]['count1'];
            $result['num_transcript_interpro'] = $res[0][0]['count2'];
        }
        return $result;
    }
}
