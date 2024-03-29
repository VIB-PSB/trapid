<?php

/**
 * This model represents the necessary functionality to authenticate users,
 * and to regulate their access rights.
 */

class Authentication extends AppModel {
    var $useTable = 'authentication';
    var $validate = ['email' => ['rule' => ['email']]];
}
