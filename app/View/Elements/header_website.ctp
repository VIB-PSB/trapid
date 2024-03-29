<nav class="navbar navbar-inverse navbar-static-top" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">TRAPID
                <?php if (IS_DEV_ENVIRONMENT) {
                    echo '<label class="label label-beta">dev</label>';
                } ?>
            </a>
        </div>
        <div class="collapse navbar-collapse" id="main-navbar-collapse">
            <ul class="nav navbar-nav">
                <li><?php echo $this->Html->link('Home', ['controller' => 'trapid', 'action' => 'index']); ?></li>
                <li><?php echo $this->Html->link('Documentation', ['controller' => 'documentation', 'action' => 'index']); ?></li>
                <li><?php echo $this->Html->link('About', ['controller' => 'documentation', 'action' => 'about']); ?></li>
                <li><?php echo $this->Html->link('Contact', ['controller' => 'documentation', 'action' => 'contact']); ?></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <?php $user_logged_in = $this->requestAction('/trapid/is_logged_in/'); ?>
                <?php if ($user_logged_in): ?>
                    <li><?php echo $this->Html->link('Experiments', ['controller' => 'trapid', 'action' => 'experiments']); ?></li>
                    <li>
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                            <span class="glyphicon glyphicon-user"></span> Account <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <?php echo $this->Html->link('Change password', ['controller' => 'trapid', 'action' => 'change_password']); ?>
                            </li>
                            <li><?php echo $this->Html->link('Log out', ['controller' => 'trapid', 'action' => 'log_off']); ?></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <div class="navbar-form">
                        <div class="form-group">
                            <?php
                            echo $this->Html->link('Log in',
                                ['controller' => 'trapid', 'action' => 'authentication'],
                                ['class' => 'btn btn-link']
                            );
                            echo '&nbsp;';
                            echo $this->Html->link(
                                'Register',
                                ['controller' => 'trapid', 'action' => 'authentication', 'registration'],
                                ['class' => 'btn btn-default']
                            );
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>