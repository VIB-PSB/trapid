<nav class="navbar navbar-inverse navbar-static-top" role="navigation">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
        <a class="navbar-brand" href="#" style="font-family: 'Redensek', arial;">TRAPID
      <label class="label label-beta">beta</label></a>
    </div>
    <div class="collapse navbar-collapse" id="main-navbar-collapse">
      <ul class="nav navbar-nav">
        <li><?php echo $this->Html->link("Home", array("controller"=>"trapid","action"=>"index")); ?></a></li>
        <li><?php echo $this->Html->link("Documentation", array("controller"=>"documentation","action"=>"index")); ?></li>
        <li><?php echo $this->Html->link("About", array("controller"=>"documentation","action"=>"about")); ?></li>
        <li><?php echo $this->Html->link("Contact", array("controller"=>"documentation","action"=>"contact")); ?></li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
      <?php $user_logged_in = $this->requestAction('/trapid/is_logged_in/'); ?>
      <?php if($user_logged_in) : ?>
        <li><?php echo $this->Html->link("Experiments", array("controller"=>"trapid","action"=>"experiments")); ?></li>
        <li>
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><span
                  class="glyphicon glyphicon-user"></span> Account <span class="caret"></span></a>
            <ul class="dropdown-menu" role="menu">
              <li><?php echo $this->Html->link("Change password", array("controller"=>"trapid","action"=>"change_password")); ?></li>
              <li><?php echo $this->Html->link("Log out", array("controller"=>"trapid","action"=>"log_off")); ?></li>
            </ul>
        </li>
      <?php else : ?>
        <form class="navbar-form navbar-left">
          <div class="form-group">
            <?php 	echo $this->Html->link("Log in",array("controller"=>"trapid","action"=>"authentication"), array("class"=>"btn btn-link"));
                    echo "&nbsp;";
                    echo $this->Html->link("Register",array("controller"=>"trapid","action"=>"authentication","registration"), array("class"=>"btn btn-default"));
            ?>
          </div>
      </form>
    <?php endif ?>
      </ul>
    </div>
  </div>
</nav> <!-- End navigation bar -->