<?php
App::uses('Controller', 'Controller');
App::uses('Sanitize', 'Utility');
App::uses('CakeEmail', 'Network/Email');

/**
 * General controller class for TRAPID.
 */

class AppController extends Controller {
    var $components = ['Cookie'];
    var $helpers = ['Html', 'Form'];
    var $process_states = [
        'default' => ['empty', 'upload', 'finished'],
        'all' => ['empty', 'upload', 'finished', 'processing', 'error'],
        'finished' => ['finished'],
        'upload' => ['upload'],
        'start' => ['empty', 'upload']
    ];
    var $uses = ['Authentication', 'Experiments', 'SharedExperiments'];

    /*
     * Cookie setup:
     * The entire TRAPID website is based on user-defined data sets, and as such a method for account handling and user
     * identification is required.
     *
     * The 'beforeFilter' method is executed BEFORE each method, and as such ensures that the necessary identification
     * through cookies is done.
     *
     * Cookie settings are defined in the `webapp_settings.ini` configuration file (`app/scripts/ini_files/`).
     * For more information about cookie setup: https://book.cakephp.org/2/en/core-libraries/components/cookie.html
     */
    function beforeFilter() {
        parent::beforeFilter();
        $this->set('title', WEBSITE_TITLE);
        $this->Cookie->name = COOKIE_NAME;
        $this->Cookie->time = COOKIE_TIME;
        $this->Cookie->domain = COOKIE_DOMAIN;
        $this->Cookie->path = COOKIE_PATH;
        $this->Cookie->key = COOKIE_KEY;
        $this->Cookie->secure = COOKIE_SECURE;
    }

    /*
     * Authentication of user-experiment combination,
     * not through cookie, but through extra hashed variables.
     */
    function check_user_exp_no_cookie($hashed_user_id = null, $exp_id = null) {
        // Get experiment info
        if (!$hashed_user_id || !$exp_id) {
            return false;
        }

        // Check experiment exists
        $exp_info = $this->Experiments->find('first', ['conditions' => ['experiment_id' => $exp_id]]);
        if (!$exp_info) {
            return false;
        }

        // Check admin hashes
        $admin_users = $this->Authentication->find('all', [
            'conditions' => ['group' => 'admin'],
            'fields' => 'user_id'
        ]);
        foreach ($admin_users as $au) {
            $t = $au['Authentication']['user_id'] . $this->Cookie->key;
            $new_hashed_user_id = hash('md5', $t);
            if ($new_hashed_user_id == $hashed_user_id) {
                return true;
            }
        }

        // Check the owner of the experiment itself
        $user_id = $exp_info['Experiments']['user_id'];
        $t = $user_id . '' . $this->Cookie->key;
        $new_hashed_user_id = hash('md5', $t);
        if ($hashed_user_id == $new_hashed_user_id) {
            return true;
        }

        // Shared experiment: check if user can access it
        $shared_users = $this->SharedExperiments->find('all', [
            'conditions' => ['experiment_id' => $exp_id],
            'fields' => 'user_id'
        ]);
        foreach ($shared_users as $su) {
            $t = $su['SharedExperiments']['user_id'] . $this->Cookie->key;
            $new_hashed_user_id = hash('md5', $t);
            if ($new_hashed_user_id == $hashed_user_id) {
                return true;
            }
        }
        return false;
    }

    function get_hashed_user_id() {
        $user_id = $this->Cookie->read('user_id');
        $t = $user_id . '' . $this->Cookie->key;
        $hashed_user_id = hash('md5', $t);
        return $hashed_user_id;
    }

    /**
     * Authentication of user through cookie-data
     */
    function check_user() {
        $user_id = $this->Cookie->read('user_id');
        $email = $this->Cookie->read('email');
        $user_data = $this->Authentication->find('first', [
            'conditions' => ['user_id' => $user_id, 'email' => $email]
        ]);
        if (!$user_data) {
            $this->redirect(['controller' => 'trapid', 'action' => 'authentication']);
        }
        return $user_data['Authentication']['user_id'];
    }

    function is_owner($exp_id = null) {
        if (!$exp_id) {
            $this->redirect(['controller' => 'trapid', 'action' => 'experiments']);
        }
        $user_id = $this->check_user();
        $user_data = $this->Authentication->find('first', ['conditions' => ['user_id' => $user_id]]);
        $experiment = $this->Experiments->find('first', ['conditions' => ['experiment_id' => $exp_id]]);
        // Possibility 1: the experiment is private, the user is not an admin, and the user matches the experiment.
        if ($experiment['Experiments']['user_id'] == $user_id) {
            return true;
        }
        // Possibility 2: the experiment is private but the user is an admin
        elseif ($user_data['Authentication']['group'] == 'admin') {
            return true;
        }
        return false;
    }

    /*
     * Authentication of user and experiment
     */
    function check_user_exp($exp_id = null) {
        if (!$exp_id) {
            $this->redirect(['controller' => 'trapid', 'action' => 'experiments']);
        }
        $user_id = $this->check_user();
        $user_data = $this->Authentication->find('first', ['conditions' => ['user_id' => $user_id]]);
        $shared_exp = $this->SharedExperiments->find('first', [
            'conditions' => ['user_id' => $user_id, 'experiment_id' => $exp_id]
        ]);

        $experiment = $this->Experiments->find('first', ['conditions' => ['experiment_id' => $exp_id]]);

        // Possibility 1: the experiment is a public experiment. And as such visible to everyone.
        if ($experiment['Experiments']['public_experiment'] == 1) {
            $this->changeDbConfigs($exp_id); //no need to check on user-experiment
            return;
        }
        // Possibility 2: the experiment is private, the user is not an admin, and the user matches the experiment.
        elseif ($experiment['Experiments']['user_id'] == $user_id) {
            $this->changeDbConfigs($exp_id);
            return;
        }
        // Possibility 3: the experiment is private, the user is an admin
        elseif ($user_data['Authentication']['group'] == 'admin') {
            $this->changeDbConfigs($exp_id);
            return;
        }
        // Possibility 4: experiment is a shared experiment between users.
        elseif ($shared_exp) {
            $this->changeDbConfigs($exp_id);
            return;
        }
        // Possibility 5: the experiment is private, the user is not an admin, and the user does not match the experiment
        // In that case, redirect to the `experiments` page.
        else {
            $this->redirect(['controller' => 'trapid', 'action' => 'experiments']);
        }
    }

    /*
     * Important part of the communication with non-trapid databases. These databases could be anything, and as such we
     * have to change the configuration based on the experiment.
     */
    function changeDbConfigs($exp_id) {
        // Get the experiment's reference database (historically 'plaza' database, hence the field name).
        $exp_ref_db = $this->Experiments->find('first', [
            'conditions' => ['experiment_id' => $exp_id],
            'fields' => 'used_plaza_database'
        ]);
        $exp_ref_db = $exp_ref_db['Experiments']['used_plaza_database'];
        // Set new `useDBConfig` for models
        $this->AnnotSources->useDbConfig = $exp_ref_db;
        $this->Annotation->useDbConfig = $exp_ref_db;
        $this->ExtendedGo->useDbConfig = $exp_ref_db;
        $this->GoParents->useDbConfig = $exp_ref_db;
        $this->ProteinMotifs->useDbConfig = $exp_ref_db;
        $this->GfData->useDbConfig = $exp_ref_db;
        $this->KoTerms->useDbConfig = $exp_ref_db;
    }
}
