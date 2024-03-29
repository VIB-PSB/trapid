<?php
/*
 * This model represents info on the transcript sequence of a TRAPID experiment.
 */

class Transcripts extends AppModel {
    public $virtualFields = [
        'transcript_sequence' => 'UNCOMPRESS(Transcripts.transcript_sequence)',
        'transcript_sequence_corrected' => 'UNCOMPRESS(Transcripts.transcript_sequence_corrected)',
        'orf_sequence' => 'UNCOMPRESS(Transcripts.orf_sequence)'
    ];

    // Note: using a raw query because the "find" method uses too much memory to be effective
    function getColumnInfo($exp_id, $columns) {
        $query = 'SELECT `transcript_id`';
        foreach ($columns as $column) {
            $query = $query . ',`' . $column . '`';
        }
        $query = $query . " FROM `transcripts` WHERE `experiment_id`='" . $exp_id . "'";
        $res = $this->query($query);
        $result = [];
        foreach ($res as $r) {
            $result[] = $r['transcripts'];
        }
        return $result;
    }

    function findExperimentInformation($exp_id) {
        $data_source = $this->getDataSource();
        $exp_id = $data_source->value($exp_id, 'integer');
        $query =
            "SELECT COUNT(`transcript_id`) as transcript_count, COUNT(DISTINCT(`gf_id`)) as gf_count FROM `transcripts` WHERE `experiment_id`='" .
            $exp_id .
            "' ";
        $res = $this->query($query);
        return $res;
    }

    function updateCodonStats($exp_id, $transcript_id, $orf_sequence) {
        $has_start_codon = 0;
        $has_stop_codon = 0;
        if (strlen($orf_sequence) >= 3) {
            $start_codon = substr($orf_sequence, 0, 3);
            $stop_codon = substr($orf_sequence, -3, 3);
            if ($start_codon == 'ATG') {
                $has_start_codon = 1;
            }
            if ($stop_codon == 'TAA' || $stop_codon == 'TAG' || $stop_codon == 'TGA') {
                $has_stop_codon = 1;
            }
        }
        $statement =
            "update `transcripts` SET `orf_contains_start_codon`='" .
            $has_start_codon .
            "',`orf_contains_stop_codon`='" .
            $has_stop_codon .
            "' WHERE `experiment_id`='" .
            $exp_id .
            "' AND `transcript_id`='" .
            $transcript_id .
            "' ";
        $this->query($statement);
    }

    function findAssociatedGf($exp_id, $transcript_ids) {
        $transcripts_string = "('" . implode("','", $transcript_ids) . "')";
        $query =
            "SELECT `gf_id`,COUNT(`transcript_id`) as count FROM `transcripts` WHERE `experiment_id`='" .
            $exp_id .
            "' AND
	`transcript_id` IN " .
            $transcripts_string .
            ' GROUP BY `gf_id` ';
        $res = $this->query($query);
        $result = [];
        foreach ($res as $r) {
            $result[$r['transcripts']['gf_id']] = $r[0]['count'];
        }
        return $result;
    }

    function getAvgTranscriptLength($exp_id) {
        $query =
            "SELECT AVG(CHAR_LENGTH(UNCOMPRESS(`transcript_sequence`))) as avg_transcript_length FROM `transcripts`
			WHERE `experiment_id`='" .
            $exp_id .
            "' ";
        $res = $this->query($query);
        $result = round($res[0][0]['avg_transcript_length'], 1);
        return $result;
    }

    function getAvgOrfLength($exp_id) {
        $query =
            "SELECT AVG(CHAR_LENGTH(UNCOMPRESS(`orf_sequence`))) as avg_orf_length FROM `transcripts`
			WHERE `experiment_id`='" .
            $exp_id .
            "' ";
        $res = $this->query($query);
        $result = round($res[0][0]['avg_orf_length'], 1);
        return $result;
    }

    function getLengths($exp_id, $sequence_type = null, $meta_annot = null) {
        $result = [];
        $query = null;
        if ($sequence_type == 'transcript') {
            $query =
                "SELECT CHAR_LENGTH(UNCOMPRESS(`transcript_sequence`)) as `length` FROM `transcripts` WHERE `experiment_id`='" .
                $exp_id .
                "'";
        } elseif ($sequence_type == 'orf') {
            $query =
                "SELECT CHAR_LENGTH(UNCOMPRESS(`orf_sequence`)) as `length` FROM `transcripts` WHERE `experiment_id`='" .
                $exp_id .
                "'";
        } else {
            return $result;
        }
        if ($meta_annot != null) {
            $query = $query . " AND `meta_annotation`='" . $meta_annot . "' ";
        }
        $res = $this->query($query);
        $result = [];
        foreach ($res as $r) {
            $result[] = $r[0]['length'];
        }
        return $result;
    }

    function getLabelToGFMapping($exp_id, $reverse = false) {
        $query =
            "SELECT COUNT(*), transcripts.`gf_id`,transcripts_labels.`label`
               FROM transcripts LEFT JOIN transcripts_labels ON
                  (transcripts_labels.`transcript_id`=transcripts.`transcript_id`
                   AND transcripts_labels.`experiment_id`=transcripts.`experiment_id`)
               WHERE transcripts.`experiment_id` = " .
            $exp_id .
            " AND transcripts.`gf_id` IS NOT NULL
               GROUP BY transcripts.`gf_id`, transcripts_labels.`label`
               ORDER BY COUNT( * ) DESC ";
        $res = $this->query($query);
        $result = [];
        foreach ($res as $r) {
            $gf_id = $r['transcripts']['gf_id'];
            $label = $r['transcripts_labels']['label'];
            $count = reset($r[0]);
            if (!$reverse) {
                $result[] = [$gf_id, $label, $count];
            } else {
                $result[] = [$label, $gf_id, $count];
            }
        }
        return $result;
    }

    function getGOToGFMapping($exp_id, $reverse = false) {
        $query =
            "SELECT COUNT( * ) , transcripts.`gf_id` , transcripts_annotation.`name`
               FROM transcripts
               LEFT JOIN transcripts_annotation ON ( transcripts_annotation.`transcript_id` = transcripts.`transcript_id`
                                             AND transcripts_annotation.`experiment_id` = transcripts.`experiment_id` )
               WHERE transcripts.`experiment_id` = " .
            $exp_id .
            "
               AND transcripts_annotation.`type` = 'go'
               AND transcripts.`gf_id` IS NOT NULL AND transcripts_annotation.`name` IS NOT NULL
               GROUP BY transcripts.`gf_id` , transcripts_annotation.`name`
               ORDER BY COUNT( * ) DESC ";
        $res = $this->query($query);
        $result = [];
        foreach ($res as $r) {
            $gf_id = $r['transcripts']['gf_id'];
            $GO = $r['transcripts_annotation']['name'];
            $count = reset($r[0]);
            if (!$reverse) {
                $result[] = [$gf_id, $GO, $count];
            } else {
                $result[] = [$GO, $gf_id, $count];
            }
        }
        return $result;
    }

    function getOneGOToGFMapping($exp_id, $go) {
        $query = "SELECT COUNT( * ) , transcripts.`gf_id` , transcripts_annotation.`name`
               FROM transcripts
               LEFT JOIN transcripts_annotation ON ( transcripts_annotation.`transcript_id` = transcripts.`transcript_id`
                                             AND transcripts_annotation.`experiment_id` = transcripts.`experiment_id` )
               WHERE transcripts.`experiment_id` = $exp_id
               AND transcripts_annotation.`name` = '$go'
               AND transcripts_annotation.`type` = 'go'
               GROUP BY transcripts.`gf_id`";
        $res = $this->query($query);
        $result = [];
        foreach ($res as $r) {
            $gf_id = $r['transcripts']['gf_id'];
            $GO = $r['transcripts_annotation']['name'];
            $count = reset($r[0]);
            $result[] = [$GO, $gf_id, $count];
        }
        return $result;
    }

    function getinterproToGFMapping($exp_id, $reverse = false) {
        $query =
            "SELECT COUNT( * ) , transcripts.`gf_id` , transcripts_annotation.`name`
               FROM transcripts
               LEFT JOIN transcripts_annotation ON ( transcripts_annotation.`transcript_id` = transcripts.`transcript_id`
                                             AND transcripts_annotation.`experiment_id` = transcripts.`experiment_id` )
               WHERE transcripts.`experiment_id` = " .
            $exp_id .
            "
               AND transcripts_annotation.`type`='ipr'
               AND transcripts.`gf_id` IS NOT NULL
               AND transcripts_annotation.`name` IS NOT NULL
               GROUP BY transcripts.`gf_id` , transcripts_annotation.`name`
               ORDER BY COUNT( * ) DESC ";
        $res = $this->query($query);
        $result = [];
        foreach ($res as $r) {
            $gf_id = $r['transcripts']['gf_id'];
            $interpro = $r['transcripts_annotation']['name'];
            $count = reset($r[0]);
            if (!$reverse) {
                $result[] = [$gf_id, $interpro, $count];
            } else {
                $result[] = [$interpro, $gf_id, $count];
            }
        }
        return $result;
    }

    function getOneInterproToGFMapping($exp_id, $interpro) {
        $query = "SELECT COUNT( * ) , transcripts.`gf_id` , transcripts_annotation.`name`
               FROM transcripts
               LEFT JOIN transcripts_annotation ON ( transcripts_annotation.`transcript_id` = transcripts.`transcript_id`
                                             AND transcripts_annotation.`experiment_id` = transcripts.`experiment_id` )
               WHERE transcripts.`experiment_id` = $exp_id
               AND transcripts_annotation.`type` = 'ipr'
               AND transcripts_annotation.`name` = '$interpro'
               GROUP BY transcripts.`gf_id` "; //, transcripts_interpro.`interpro` ";
        $res = $this->query($query);
        $result = [];
        foreach ($res as $r) {
            $gf_id = $r['transcripts']['gf_id'];
            $interpro = $r['transcripts_annotation']['name'];
            $count = reset($r[0]);
            $result[] = [$interpro, $gf_id, $count];
        }
        return $result;
    }

    // No check on `$ko`??
    function getOneKOToGFMapping($exp_id, $ko) {
        $query = "SELECT COUNT( * ) , transcripts.`gf_id` , transcripts_annotation.`name`
               FROM transcripts
               LEFT JOIN transcripts_annotation ON ( transcripts_annotation.`transcript_id` = transcripts.`transcript_id`
                                             AND transcripts_annotation.`experiment_id` = transcripts.`experiment_id` )
               WHERE transcripts.`experiment_id` = $exp_id
               AND transcripts_annotation.`type` = 'ko'
               AND transcripts_annotation.`name` = '$ko'
               GROUP BY transcripts.`gf_id` ";
        $res = $this->query($query);
        $result = [];
        foreach ($res as $r) {
            $gf_id = $r['transcripts']['gf_id'];
            $ko_id = $r['transcripts_annotation']['name'];
            $count = reset($r[0]);
            $result[] = [$ko_id, $gf_id, $count];
        }
        return $result;
    }

    // Count the number of ORFs in an experiment
    // ORF => `orf_stop` > 0 (end of ORF sequence on the transcript)
    function getOrfCount($exp_id) {
        $result = [];
        $query =
            "SELECT COUNT(*) as count FROM `transcripts` WHERE `experiment_id`='" . $exp_id . "' AND `orf_stop` > 0;";
        $res = $this->query($query);
        $result = 0;
        foreach ($res as $r) {
            $result = $r[0]['count'];
        }
        return $result;
    }
}
