<?php

/*
 * This model represents gene family information associated with an experiment's transcripts.
 */

class GeneFamilies extends AppModel {
    function findByGene($exp_id, $gene_id) {
        $data_source = $this->getDataSource();
        $exp_id = $data_source->value($exp_id, 'integer');
        $result = [];
        if (strlen($gene_id) <= 5) {
            return $result;
        }

        $gf_data = $this->find('all', [
            'conditions' => ['experiment_id' => $exp_id, 'gf_content LIKE' => "%$gene_id%"],
            'fields' => ['gf_id', 'plaza_gf_id', 'num_transcripts']
        ]);
        foreach ($gf_data as $r) {
            $result = $r['GeneFamilies'];
        }
        return $result;
    }

    function gfExists($exp_id, $gf_id) {
        $gf_exists = false;
        $gf_data = $this->find('first', ['conditions' => ['experiment_id' => $exp_id, 'gf_id' => $gf_id]]);
        if ($gf_data) {
            $gf_exists = true;
        }
        return $gf_exists;
    }
}
