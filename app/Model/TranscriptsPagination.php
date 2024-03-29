<?php
/*
 * This model handles pagination of transcript data.
 */

class TranscriptsPagination extends AppModel {
    var $useTable = 'transcripts';

    function getPossibleParameters() {
        // transcripts:			a
        // transcripts_labels:		b, b1, b2, b3, ...
        // transcripts_go:		c, c1, c2, c3, ...
        // transcripts_interpro: 	d, d1, d2, d3, ...
        // transcripts_ko: 	d, d1, d2, d3, ...
        // Note: `transcripts_ko` Still set to `d` (not `e`) as InterPro and KO are mutually exclusive
        $possible_parameters = [
            'min_transcript_length' => [
                'query' => 'CHAR_LENGTH(UNCOMPRESS(`transcript_sequence`))>=',
                'table' => '`transcripts`',
                'tabid' => 'a',
                'desc' => 'Min. transcript length'
            ],
            'max_transcript_length' => [
                'query' => 'CHAR_LENGTH(UNCOMPRESS(`transcript_sequence`))<=',
                'table' => '`transcripts`',
                'tabid' => 'a',
                'desc' => 'Max. transcript length'
            ],
            'min_orf_length' => [
                'query' => 'CHAR_LENGTH(UNCOMPRESS(`orf_sequence`))>=',
                'table' => '`transcripts`',
                'tabid' => 'a',
                'desc' => 'Min. ORF length'
            ],
            'max_orf_length' => [
                'query' => 'CHAR_LENGTH(UNCOMPRESS(`orf_sequence`))<=',
                'table' => '`transcripts`',
                'tabid' => 'a',
                'desc' => 'Max. ORF length'
            ],
            'meta_annotation' => [
                'query' => '`meta_annotation`=',
                'table' => '`transcripts`',
                'tabid' => 'a',
                'desc' => 'Meta annotation'
            ],
            'gf_id' => ['query' => '`gf_id`=', 'table' => '`transcripts`', 'tabid' => 'a', 'desc' => 'Gene family'],
            'label' => ['query' => '`label`=', 'table' => '`transcripts_labels`', 'tabid' => 'b', 'desc' => 'Subset'],
            'go' => [
                'query' => "`type`='go' AND `name`=",
                'table' => '`transcripts_annotation`',
                'tabid' => 'c',
                'desc' => 'GO label'
            ],
            'interpro' => [
                'query' => "`type`='ipr' AND `name`=",
                'table' => '`transcripts_annotation`',
                'tabid' => 'd',
                'desc' => 'InterPro domain'
            ],
            'ko' => [
                'query' => "`type`='ko' AND `name`=",
                'table' => '`transcripts_annotation`',
                'tabid' => 'd',
                'desc' => 'KO term'
            ]
        ];
        return $possible_parameters;
    }

    function getParsedParameters($parameters) {
        $result = [];
        $possible_parameters = $this->getPossibleParameters();
        $num_parameters = count($parameters);
        for ($i = 1; $i < $num_parameters; $i += 2) {
            $key = filter_var($parameters[$i], FILTER_SANITIZE_STRING);
            $value = filter_var(urldecode($parameters[$i + 1]), FILTER_SANITIZE_STRING);
            if (array_key_exists($key, $possible_parameters)) {
                $desc = $possible_parameters[$key]['desc'];
                if (!array_key_exists($desc, $result)) {
                    $result[$desc] = [];
                }
                $result[$desc][] = $value;
            }
        }
        return $result;
    }

    function createQuery($parameters, $count = false) {
        $possible_parameters = $this->getPossibleParameters();
        $params = [];
        $num_parameters = count($parameters);
        $exp_id = filter_var($parameters[0], FILTER_SANITIZE_NUMBER_INT);
        // Parse parameters
        for ($i = 1; $i < $num_parameters; $i += 2) {
            $key = filter_var($parameters[$i], FILTER_SANITIZE_STRING);
            $value = filter_var(urldecode($parameters[$i + 1]), FILTER_SANITIZE_STRING);
            if (!array_key_exists($key, $possible_parameters)) {
                return false;
            }
            $params[] = ['key' => $key, 'value' => $value]; // Not single hash, as multiple keys should be able to be used at the same time.
        }

        // Select the necessary tables for joining.
        $used_tables = ['`transcripts` a']; // Default inclusion
        $used_joins = [];
        $used_exp_sel = ["a.`experiment_id`='" . $exp_id . "'"];
        $used_queries = [];
        $used_increases = ['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0];
        foreach ($params as $param) {
            $par = $possible_parameters[$param['key']];
            $tabid = $par['tabid'];
            // Establish unique table identifier per table (except transcripts)
            $table_id = $tabid;
            if ($tabid != 'a') {
                $used_increases[$tabid]++;
                $table_id = $tabid . '' . $used_increases[$tabid];
            }
            // Table definitions
            $used_tables[] = $par['table'] . ' ' . $table_id;
            // Joins between transcript table and other tables
            if ($tabid != 'a') {
                // Don't need a join when restriction is put on table transcripts
                $used_joins[] = $table_id . '.`transcript_id`=a.`transcript_id`';
            }
            // Every table should be restricted by experiment id as well
            $used_exp_sel[] = $table_id . ".`experiment_id`='" . $exp_id . "'";
            // Query itself, table identifier should be entered before the first "`" character (which should indicate a column)
            $query_insert_loc = strpos($par['query'], '`');
            $used_query =
                substr($par['query'], 0, $query_insert_loc) .
                '' .
                $table_id .
                '.' .
                substr($par['query'], $query_insert_loc) .
                "'" .
                $param['value'] .
                "'";
            $used_queries[] = $used_query;
        }
        $used_tables = implode(',', array_unique($used_tables));
        $used_joins = implode(' AND ', array_unique($used_joins));
        $used_exp_sel = implode(' AND ', array_unique($used_exp_sel));
        $used_queries = implode(' AND ', array_unique($used_queries));

        $query = 'SELECT ';
        if ($count) {
            $query = $query . 'COUNT(a.`transcript_id`) as count ';
        } else {
            $query = $query . ' a.`transcript_id` ';
        }
        $query = $query . ' FROM ' . $used_tables . ' WHERE ' . $used_exp_sel . ' AND ' . $used_queries;
        if (strlen($used_joins) > 0) {
            $query = $query . ' AND ' . $used_joins;
        }
        return $query;
    }

    function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = []) {
        $custom_query = $this->createQuery($conditions, false);
        if ($custom_query === false) {
            return null;
        }
        $limit_start = ($page - 1) * $limit;
        $limit_end = $limit;
        if ($limit_start < 0) {
            $limit_start = 0;
        }
        $use_limit = true;

        if ($order) {
            // $custom_query		= $custom_query." ORDER BY ";
            // Hardcoding ordering w/ `experiment_id` seems to force MySQL to use the correct index ...
            $custom_query = $custom_query . ' ORDER BY ';
            foreach ($order as $k => $v) {
                // CakePHP may add the class name as prefix, causing the queries defined here to fail.
                if (strpos($k, $this->name) == 0) {
                    $field_split = explode('.', $k);
                    $correct_field = $field_split[1];
                    $custom_query = $custom_query . ' a.`' . $correct_field . '` ' . $v . ' ';
                } else {
                    $custom_query = $custom_query . ' a.`' . $k . '` ' . $v . ' ';
                }
            }
        }
        if ($use_limit) {
            $custom_query = $custom_query . ' LIMIT ' . $limit_start . ',' . $limit_end;
        }
        $res = $this->query($custom_query);
        $result = [];
        foreach ($res as $r) {
            $result[] = $r['a']['transcript_id'];
        }
        return $result;
    }

    function paginateCount($conditions = null, $recursive = 0, $extra = []) {
        $custom_query = $this->createQuery($conditions, true);
        if ($custom_query === false) {
            return 0;
        }
        $res = $this->query($custom_query);
        $result = $res[0][0]['count'];
        return $result;
    }
}
