<div class="container">
    <div class="page-header">
        <h1 class="text-primary">Change password</h1>
    </div>
    <?php
    if (isset($error)) {
        echo "<p class='text-danger'><strong>Error:</strong> " . $error . "</p>\n";
    }
    if (isset($message)) {
        echo "<p class='text-primary'>" . $message . "</p>\n";
    }
    ?>
    <div class="form-auth-wrapper">
        <?php echo $this->Form->create(null, [
            'url' => ['controller' => 'trapid', 'action' => 'change_password'],
            'type' => 'post',
            'class' => 'form-auth'
        ]); ?>
        <input type="hidden" name="registration" value="registration" />
        <div class="form-group">
            <label for="login"><strong>New password</strong></label>
            <input type="password" class="form-control" name="new_password1" id="new_password1" placeholder="********" required>
        </div>
        <div class="form-group">
            <label for="organization"><strong>Confirm new password</strong></label>
            <input type="password" class="form-control" name="new_password2" id="new_password2" placeholder="********" required>
        </div>
        <button type="submit" class="btn btn-lg btn-primary btn-block btn-auth">Change password</button>
        <?php echo $this->Form->end(); ?>
    </div>
</div>