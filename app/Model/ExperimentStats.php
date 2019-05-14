<?php
/**
 * A model that represents statistics associated to an experiment, as displayed on the 'general statistics' page
 */

class ExperimentStats extends AppModel{

    var $name	= "ExperimentStats";
    var $useTable = "experiment_stats";


    // Retrieve GO annotation stats for experiment `$exp_id` and functional annotation type `$fa_type`.
    // Return them as associative array.
    function getFuncAnnotStats($exp_id, $fa_type) {
        $possible_types = ['go', 'ipr', 'ko'];
        $stat_types = array("go"=>["trs_go", "n_go"], "ipr"=>["trs_pr", "n_ipr"], "ko"=>["trs_ko", "n_ko"]);
        $stat_keys = array("go"=>["num_transcript_go", "num_go"], "ipr"=>["num_transcript_interpro", "num_interpro"], "ko"=>["num_transcript_ko", "num_ko"]);
        if(!in_array($fa_type, $possible_types)) {
            return null;
        }
        $fa_stats = array($stat_keys[$fa_type][0]=>0, $stat_keys[$fa_type][1]=>0);
        // Retrieve data from DB
        $trs_fa = $this->find("first", array('fields'=>array('stat_value'), 'conditions'=>array('experiment_id'=>$exp_id, 'stat_type'=>$stat_types[$fa_type][0])));
        $n_fa = $this->find("first", array('fields'=>array('stat_value'), 'conditions'=>array('experiment_id'=>$exp_id, 'stat_type'=>$stat_types[$fa_type][1])));
        if($trs_fa) {
            $fa_stats[$stat_keys[$fa_type][0]] = $trs_fa['ExperimentStats']['stat_value'];
        }
        if($n_fa) {
            $fa_stats[$stat_keys[$fa_type][1]] = $n_fa['ExperimentStats']['stat_value'];
        }
        return $fa_stats;
    }

}
