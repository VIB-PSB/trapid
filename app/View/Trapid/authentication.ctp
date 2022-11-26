<div class="container">
    <?php
    // Show alert element if there is an error or message
    if (isset($error)) {
        $alert_content = '<strong>Error:</strong> ' . $error;
        echo $this->element('bs_alert', ['alert_class' => 'alert-warning', 'alert_content' => $alert_content]);
    }
    if (isset($message)) {
        $alert_content = '<strong>Information:</strong> ' . $message;
        echo $this->element('bs_alert', ['alert_class' => 'alert-success', 'alert_content' => $alert_content]);
    }
    ?>
    <?php if (isset($registration) && $registration): ?>
        <div class="page-header">
            <h1 class="text-primary">Create a TRAPID account</h1>
        </div>
        <div class="form-auth-wrapper">
            <?php echo $this->Form->create('Authentication', [
                'url' => ['controller' => 'trapid', 'action' => 'authentication', 'registration'],
                'type' => 'post',
                'class' => 'form-auth'
            ]); ?>
            <input type="hidden" name="registration" value="registration" />
            <div class="form-group">
                <label for="login"><strong>Email</strong></label>
                <input type="email" class="form-control" name="login" id="login" placeholder="you@example.org" required>
            </div>
            <div class="form-group">
                <label for="organization"><strong>Organization</strong></label>
                <input type="text" class="form-control" name="organization" id="organization" placeholder="Example University" required>
            </div>
            <div class="form-group">
                <label for="country"><strong>Country</strong></label>
                <input type="text" class="form-control" name="country" id="country" placeholder="Example Country" required>
            </div>
            <button type="submit" class="btn btn-lg btn-primary btn-block btn-auth">Register</button>
            <p class="text-justify text-muted">
                Registration will be complete after the system has sent you a password to the provided email address.
            </p>
            <?php echo $this->Form->end(); ?>
        </div>

    <?php elseif (isset($pass_recovery) && $pass_recovery): ?>
        <div class="page-header">
            <h1 class="text-primary">Password recovery</h1>
        </div>
        <?php if (!isset($sent_reset_email)): ?>
            <p class="text-justify">Forgot your account's password? Enter your email address to reset it. </p>
            <div class="form-auth-wrapper">
                <?php echo $this->Form->create('Authentication', [
                    'url' => ['controller' => 'trapid', 'action' => 'authentication', 'registration'],
                    'type' => 'post',
                    'class' => 'form-auth'
                ]); ?>
                <input type="hidden" name="pass_recovery" value="pass_recovery" />
                <div class="form-group">
                    <label for="login"><strong>Email</strong></label>
                    <input type="email" class="form-control" name="login" id="login" placeholder="you@example.org" required>
                </div>
                <button type="submit" class="btn btn-lg btn-primary btn-block btn-auth">Reset password</button>
                <?php echo $this->Form->end(); ?>
            </div>
        <?php else: ?>
            <p class="lead text-justify">A reset password was sent to <samp><?php echo $email; ?></samp>.</p>
            <p class="text-justify">
                If you don't see this email in your inbox within 15 minutes, look for it in your junk mail folder. If you find it there, please mark it as "Not Junk".
            </p>
        <?php endif; ?>

    <?php else: ?>
        <div class="page-header">
            <h1 class="text-primary">Log in to TRAPID</h1>
        </div>
        <div class="form-auth-wrapper">
            <?php echo $this->Form->create('Authentication', [
                'url' => ['controller' => 'trapid', 'action' => 'authentication'],
                'type' => 'post',
                'class' => 'form-auth'
            ]); ?>
            <div class="form-group">
                <label for="login"><strong>Email</strong></label>
                <input type="email" class="form-control" name="login" id="login" placeholder="you@example.org" required>
            </div>
            <div class="form-group">
                <label for="password"><strong>Password</strong></label>
                <input type="password" class="form-control" name="password" id="password" placeholder="********" required>
                <p class="text-right text-muted" style="font-size: 88%; margin-top:5px;">
                    <?php echo $this->Html->link('Forgot your password?', [
                        'controller' => 'trapid',
                        'action' => 'authentication',
                        'password_recovery'
                    ]); ?>
                </p>
            </div>
            <button type="submit" class="btn btn-lg btn-primary btn-block btn-auth">Log in</button>
            <p class="text-justify text-muted">If you do not have an account, you can create one
                <?php echo $this->Html->link('here', [
                    'controller' => 'trapid',
                    'action' => 'authentication',
                    'registration'
                ]); ?>.
            </p>
            <?php echo $this->Form->end(); ?>
        </div>
    <?php endif; ?>
</div>
