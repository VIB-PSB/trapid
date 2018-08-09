<!-- TODO: move inline style to separate stylesheet -->

<style>
    .form-wrapper {
        margin-top: 70px;
        margin-bottom: 70px;
    }

    .form-signin,
    .form-signup {
        max-width: 380px;
        padding: 15px 35px 7px;
        margin: 0 auto;
        background-color: #fff;
        border: 1px solid rgba(0,0,0,0.1);

    .form-signin-heading,
    .checkbox {
        margin-bottom: 30px;
    }

    .form-control {
        position: relative;
        font-size: 16px;
        height: auto;
        padding: 10px;
    @include box-sizing(border-box);

    &:focus {
         z-index: 2;
     }
    }

    input[type="email"] {
        margin-bottom: -1px;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }

    input[type="password"] {
        margin-bottom: 20px;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }
    }
</style>

<div class="container">
	<div class="page-header">
	<h1 class="text-primary">Change password</h1>
</div>
	<div class="subdiv">
		<?php
		if(isset($error)){echo "<span class='error'>".$error."</span><br/><br/>\n";}
		if(isset($message)){echo "<span class='message'>".$message."</span><br/><br/>\n";}
		?>
    </div>

    <div class="form-wrapper">
        <?php
        echo $this->Form->create(null,array("url"=>array("controller"=>"trapid","action"=>"change_password"),
            "type"=>"post", "class"=>"form-signup"));
        ?>
        <input type="hidden" name="registration" value="registration" />
        <div class="form-group">
            <label for="login"><strong>New password</strong></label>
            <input type="password" class="form-control" name="new_password1" id="new_password1" placeholder="********">
        </div>
        <div class="form-group">
            <label for="organization"><strong>Confirm new password</strong></label>
            <input type="password" class="form-control" name="new_password2" id="new_password2" placeholder="********">
        </div>
        <button type="submit" style="margin-top:30px;" class="btn btn-lg btn-primary btn-block">Change password</button>
        <br>
        </form>
    </div>
</div>
