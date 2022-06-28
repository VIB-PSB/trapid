<?php

/*
 * This model represents gene family information associated with an experiment's transcripts.
 */

class GeneFamilies extends AppModel {

    function findByGene($exp_id, $gene_id) {
        $data_source = $this->getDataSource();
        $exp_id = $data_source->value($exp_id, 'integer');
        $result = array();
        if (strlen($gene_id) <= 5) {
            return $result;
        }

        $gf_data = $this->find("all",
            array("conditions" => array("experiment_id" => $exp_id, "gf_content LIKE" => "%$gene_id%"),
                "fields" => array("gf_id", "plaza_gf_id", "num_transcripts")));
        // $query  = "SELECT `gf_id`,`plaza_gf_id`,`num_transcripts` FROM `gene_families` WHERE `experiment_id`='".$exp_id."' AND `gf_content` LIKE '%".$gene_id."%' ";
        // $res    = $this->query($query);
        // foreach($res as $r) {
        //    $result = $r['gene_families'];
        // }
        foreach ($gf_data as $r) {
            $result = $r['GeneFamilies'];
        }
        return $result;
    }

    function gfExists($exp_id, $gf_id) {
        $data_source = $this->getDataSource();
        $exp_id = $data_source->value($exp_id, 'integer');
        $gf_id = $data_source->value($gf_id, 'string');
        $query = "SELECT * FROM `gene_families` WHERE `experiment_id`='" . $exp_id . "' AND `gf_id`=" . $gf_id . ";";
        $res = $this->query($query);
        if ($res) {
            return true;
        }
        else {
            return false;
        }
    }

}
