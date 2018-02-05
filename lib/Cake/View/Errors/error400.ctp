<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Errors
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>

<?php
    // Use external website layoutfor error pages
    $this->layout = 'external';
?>

<div class="container">
    <div class="page-header">
        <h1>Error: <?php echo $name; ?></h1>
    </div>
    <p class="error">
	    <strong><?php echo __d('cake', 'Error'); ?>: </strong>
	    <?php printf(
		    __d('cake', 'The requested address %s was not found on this server.'),
		    "<strong>'{$url}'</strong>"
	    ); ?>
    </p>
    <p class="text-justify">If you feel what you just did should not have resulted in such an error, please let us know by
        <?php echo $this->Html->link("sending us a message", array("controller"=>"documentation","action"=>"contact")); ?>.</p>
    <p class="text-justify">You may also want to <a href="javascript:%20history.go(-1);">go back to last page</a> or
        <?php echo $this->Html->link("return to index", array("controller"=>"trapid","action"=>"index")); ?>. </p>
    <?php
    if (Configure::read('debug') > 0 ):
	    echo $this->element('exception_stack_trace');
    endif;
    ?>
</div>