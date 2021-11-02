<?php
App::uses('Controller', 'Controller');
App::uses('Sanitize', 'Utility');
App::uses('CakeEmail', 'Network/Email');

/**
 * General controller class for TRAPID.
 */

class AppController extends Controller {

    var $components = array("Cookie");
    var $helpers = array("Html", "Form");
    var $process_states = array("default" => array("empty", "upload", "finished"),
        "all" => array("empty", "upload", "finished", "processing", "error"),
        "finished" => array("finished"),
        "upload" => array("upload"),
        "start" => array("empty", "upload")
    );
    var $uses = array("Authentication", "Experiments", "SharedExperiments");


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
        $this->set("title", WEBSITE_TITLE);
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
        //get experiment info
        if (!$hashed_user_id || !$exp_id) {
            return false;
        }

        //experiment must exist!
        $exp_info = $this->Experiments->find("first", array("conditions" => array("experiment_id" => $exp_id)));
        if (!$exp_info) {
            return false;
        }

        //check admin hashes
        $admin_users = $this->Authentication->find("all", array(
            "conditions" => array("group" => "admin"), "fields" => "user_id")
        );
        foreach ($admin_users as $au) {
            $t = $au["Authentication"]["user_id"] . $this->Cookie->key;
            $new_hashed_user_id = hash("md5", $t);
            if ($new_hashed_user_id == $hashed_user_id) {
                return true;
            }
        }

        //check the owner of the experiment itself
        $user_id = $exp_info['Experiments']['user_id'];
        $t = $user_id . "" . $this->Cookie->key;
        $new_hashed_user_id = hash("md5", $t);
        if ($hashed_user_id == $new_hashed_user_id) {
            return true;
        }

        //ok, no admin and owner, but perhaps a shared experiment?
        $shared_users = $this->SharedExperiments->find("all", array(
            "conditions" => array("experiment_id" => $exp_id), "fields" => "user_id")
        );
        foreach ($shared_users as $su) {
            $t = $su['SharedExperiments']['user_id'] . $this->Cookie->key;
            $new_hashed_user_id = hash("md5", $t);
            if ($new_hashed_user_id == $hashed_user_id) {
                return true;
            }
        }

        return false;
    }


    function get_hashed_user_id() {
        $user_id = $this->Cookie->read("user_id");
        $t = $user_id . "" . $this->Cookie->key;
        $hashed_user_id = hash("md5", $t);
        return $hashed_user_id;
    }


    /**
     * Authentication of user through cookie-data
     */
    function check_user() {
        $user_id = $this->Cookie->read("user_id");
        $email = $this->Cookie->read("email");
        // No need to escape SQL data when using `find` and proper array notation?
        // See: https://stackoverflow.com/questions/3534243/how-do-you-escape-sql-data-in-cakephp
        $user_data = $this->Authentication->find("first", array(
            "conditions" => array("user_id" => $user_id, "email" => $email)
        ));
        if (!$user_data) {
            $this->redirect(array("controller" => "trapid", "action" => "authentication"));
        }
        return $user_data["Authentication"]["user_id"];
    }


    function is_owner($exp_id = null) {
        if (!$exp_id) {
            $this->redirect(array("controller" => "trapid", "action" => "experiments"));
        }
        $user_id = $this->check_user();
        $user_data = $this->Authentication->find("first", array("conditions" => array("user_id" => $user_id)));
        $experiment = $this->Experiments->find("first", array("conditions" => array("experiment_id" => $exp_id)));
        //possibility 1: the experiment is private, the user is not an admin, and the user matches the experiment.
        if ($experiment['Experiments']['user_id'] == $user_id) {
            return true;
        }
        //possibility 3: the experiment is private, the user is an admin
        else if ($user_data['Authentication']['group'] == "admin") {
            return true;
        }
        return false;
    }


    /*
     * Authentication of user and experiment
     */
    function check_user_exp($exp_id = null) {
        if (!$exp_id) {
            $this->redirect(array("controller" => "trapid", "action" => "experiments"));
        }
        $user_id = $this->check_user();
        $user_data = $this->Authentication->find("first", array("conditions" => array("user_id" => $user_id)));
        $shared_exp = $this->SharedExperiments->find("first", array(
            "conditions" => array("user_id" => $user_id, "experiment_id" => $exp_id))
        );

        $experiment = $this->Experiments->find("first", array("conditions" => array("experiment_id" => $exp_id)));

        // Possibility 1: the experiment is a public experiment. And as such visible to everyone.
        if ($experiment['Experiments']['public_experiment'] == 1) {
            $this->changeDbConfigs($exp_id);    //no need to check on user-experiment
            return;
        }
        // Possibility 2: the experiment is private, the user is not an admin, and the user matches the experiment.
        else if ($experiment['Experiments']['user_id'] == $user_id) {
            $this->changeDbConfigs($exp_id);
            return;
        }
        // Possibility 3: the experiment is private, the user is an admin
        else if ($user_data['Authentication']['group'] == "admin") {
            $this->changeDbConfigs($exp_id);
            return;
        }
        // Possibility 4: experiment is a shared experiment between users.
        else if ($shared_exp) {
            $this->changeDbConfigs($exp_id);
            return;
        }
        // Possibility 5: the experiment is private, the user is not an admin, and the user does not match the experiment
        // In that case, redirect to the `experiments` page.
        else {
            $this->redirect(array("controller" => "trapid", "action" => "experiments"));
        }
    }


    /*
     * Important part of the communication with non-trapid databases. These databases could be anything, and as such we
     * have to change the configuration based on the experiment.
     */
    function changeDbConfigs($exp_id) {
        //first of all, get the associated non-trapid database for the experiment.
        $non_trapid_db = $this->Experiments->find("first", array(
            "conditions" => array("experiment_id" => $exp_id), "fields" => "used_plaza_database")
        );
        $non_trapid_db = $non_trapid_db['Experiments']['used_plaza_database'];
        //set new useDBConfigs for models
        $this->AnnotSources->useDbConfig = $non_trapid_db;
        $this->Annotation->useDbConfig = $non_trapid_db;
        $this->ExtendedGo->useDbConfig = $non_trapid_db;
        $this->GoParents->useDbConfig = $non_trapid_db;
        $this->ProteinMotifs->useDbConfig = $non_trapid_db;
        $this->GfData->useDbConfig = $non_trapid_db;
        $this->KoTerms->useDbConfig = $non_trapid_db;
        //is equal to setDataSource($non_trapid_db);
        return;
    }

}
