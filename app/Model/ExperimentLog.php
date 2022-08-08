<?php

/*
 * This model represents the storage of logging information for each experiment
 * Actions can read and write to this table, providing an action, and the necessary parameters.
 */

class ExperimentLog extends AppModel {
    var $useTable = 'experiment_log';

    function addAction($exp_id, $action, $param, $depth = 0) {
        $db = $this->getDataSource();
        $this->create();
        $this->save([
            'experiment_id' => $exp_id,
            'date' => $db->expression('NOW()'),
            'action' => $action,
            'parameters' => $param,
            'depth' => $depth
        ]);
    }
}
