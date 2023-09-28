<?php

/*
 * This model represents KO information associated to the transcripts.
 */

class TranscriptsKo extends AppModel {
    var $useTable = 'transcripts_annotation';

    function findTranscriptsFromKo($exp_id, $ko_terms) {
        $result = [];
        $ko_terms_string = "('" . implode("','", array_keys($ko_terms)) . "')";
        $query =
            "SELECT `name`,count(`transcript_id`) as count FROM `transcripts_annotation` WHERE `experiment_id`='" .
            $exp_id .
            "' AND `type`='ko' AND `name` IN " .
            $ko_terms_string .
            ' GROUP BY `name` ';
        $res = $this->query($query);
        foreach ($res as $r) {
            $result[$r['transcripts_annotation']['name']] = [
                'count' => $r[0]['count'],
                'desc' => $ko_terms[$r['transcripts_annotation']['name']]
            ];
        }
        return $result;
    }

    function getStats($exp_id) {
        $query =
            "SELECT COUNT(DISTINCT(`name`)) as count1, COUNT(DISTINCT(`transcript_id`)) as count2 FROM `transcripts_annotation` WHERE `experiment_id`='" .
            $exp_id .
            "' AND `type`='ko' ";
        $res = $this->query($query);
        $result = ['num_ko' => 0, 'num_transcript_ko' => 0];
        if ($res) {
            $result['num_ko'] = $res[0][0]['count1'];
            $result['num_transcript_ko'] = $res[0][0]['count2'];
        }
        return $result;
    }
}
