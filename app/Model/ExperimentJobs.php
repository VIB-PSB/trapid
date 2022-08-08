<?php
/*
 * This model represents the cluster jobs of an experiment.
 */

class ExperimentJobs extends AppModel {
    function addJob($exp_id, $job_id, $type = 'long', $comment = '') {
        $query =
            "INSERT INTO `experiment_jobs`(`experiment_id`,`job_id`,`job_type`,`start_date`,`comment`) VALUES ('" .
            $exp_id .
            "','" .
            $job_id .
            "','" .
            $type .
            "',NOW(),'" .
            $comment .
            "')";
        $this->query($query);
    }

    function getJobs($exp_id) {
        $data_source = $this->getDataSource();
        $query =
            "SELECT * FROM `experiment_jobs` WHERE `experiment_id`='" . $data_source->value($exp_id, 'integer') . "' ";
        $res = $this->query($query);
        $result = [];
        foreach ($res as $r) {
            $result[] = $r['experiment_jobs'];
        }
        return $result;
    }

    function getNumJobs($exp_id) {
        $data_source = $this->getDataSource();
        $query =
            "SELECT COUNT(*) as count FROM `experiment_jobs` WHERE `experiment_id`='" .
            $data_source->value($exp_id, 'integer') .
            "' ";
        $res = $this->query($query);
        $result = $res[0][0]['count'];
        return $result;
    }

    function deleteJob($exp_id, $job_id) {
        $data_source = $this->getDataSource();
        $query =
            "DELETE FROM `experiment_jobs` WHERE `experiment_id`='" .
            $data_source->value($exp_id, 'integer') .
            "' AND `job_id`='" .
            $data_source->value($job_id, 'integer') .
            "' ";
        $this->query($query);
    }

    // Unused function?
    function deleteJobReturn($exp_id, $job_id) {
        $query =
            "DELETE FROM `experiment_jobs` WHERE `experiment_id`='" . $exp_id . "' AND `job_id`='" . $job_id . "' ";
        $this->query($query);
        return $this->getJobs($exp_id);
    }
}
