<!-- Experiment main menu (header) -->
<header id="header-exp" class="header-bar navbar-inverse navbar-fixed-top preload">

    <ul class="nav navbar-nav navbar-left">
        <li>
            <a class="sidebar-toggle" title="Toggle side menu"><span class="glyphicon glyphicon-menu-hamburger" style=""></span></a>
<!--                        <button class="sidebar-toggle" title="hello world">-->
<!--                            <span class="sr-only">Toggle navigation</span>-->
<!--                            <span class="icon-bar"></span>-->
<!--                            <span class="icon-bar"></span>-->
<!--                            <span class="icon-bar"></span>-->
<!--                        </button>-->
        </li>
    </ul>
    <ul class="nav navbar-nav hidden-xs hidden-sm">
        <li>
            <a class="navbar-brand"><?php echo (isset($exp_title) ? $exp_title : "No title");?></a>
        </li>
    </ul>

    <ul class="nav navbar-nav navbar-right hidden-xs hidden-sm">
        <li>
            <a href="<?php echo $this->Html->Url(array("controller"=>"trapid","action"=>"manage_jobs", $exp_id));?>">Jobs&nbsp;
                <?php if($job_count != 0): ?>
                <span class="badge-header"><?php echo $job_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li><?php echo $this->Html->link("Log",array("controller"=>"trapid","action"=>"view_log",$exp_id));?></li>
        <li><a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <!--                    <span class="glyphicon glyphicon-cog"></span> -->
                <span class="glyphicon glyphicon-cog"></span>
                <span class="hidden">Settings</span>
                <span class="caret"></span></a>
            <ul class="dropdown-menu-right dropdown-menu">
<!--                <li>--><?php //echo $this->Html->link("View log",array("controller"=>"trapid","action"=>"view_log",$exp_id));?><!--</li>-->
<!--                <li class="dropdown-header">Experiment</li>-->
                <li><?php echo $this->Html->link("Share experiment",array("controller"=>"trapid","action"=>"experiment_access",$exp_id));?></li>
                <li><?php echo $this->Html->link("Change settings",array("controller"=>"trapid","action"=>"experiment_settings",$exp_id));?></li>
                <li><?php
                    echo $this->Html->link("<span class='glyphicon glyphicon-warning-sign'></span> Reset experiment",
                        array("controller"=>"trapid","action"=>"empty_experiment",$exp_id),
                        array("class"=>"text-info", "escape"=>false),
                        "Are you sure you want to delete all content from this experiment?"); ?>
                </li>
                <li>
                    <?php    echo $this->Html->link("<span class='glyphicon glyphicon-warning-sign'></span> Delete experiment",
                        array("controller"=>"trapid","action"=>"delete_experiment",$exp_id),
                        array("class"=>"text-danger", "escape"=>false), //, "style"=>"color:red;"
                        "Are you sure you want to delete the experiment?");
                    ?></li>
                <!--<li class="dropdown-header">Account</li>
                <li><?php /*echo $this->Html->link("Change password", array("controller"=>"trapid","action"=>"change_password")); */?></li>
                <li><?php /*echo $this->Html->link("Logout", array("controller"=>"trapid","action"=>"log_off")); */?></li>-->
            </ul></li>
    </ul>
    <ul class="nav navbar-nav navbar-right hidden-md hidden-lg navbar-header">
        <li><a href="#" class="dropdwn-toggle" data-toggle="dropdown" title="Manage experiment"><span class="glyphicon glyphicon-option-vertical"></span></a>
            <ul class="dropdown-menu-right dropdown-menu">
                <li>
                    <a href="<?php echo $this->Html->Url(array("controller"=>"trapid","action"=>"manage_jobs", $exp_id));?>">Jobs&nbsp;
                        <?php if($job_count != 0): ?>
                            <span class="badge"><?php echo $job_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><?php echo $this->Html->link("Log",array("controller"=>"trapid","action"=>"view_log",$exp_id));?></li>
                <li role="separator" class="divider"></li>
                <li><?php echo $this->Html->link("Share experiment",array("controller"=>"trapid","action"=>"experiment_access",$exp_id));?></li>
                <li><?php echo $this->Html->link("Change settings",array("controller"=>"trapid","action"=>"experiment_settings",$exp_id));?></li>
                <li><?php
                    echo $this->Html->link("<span class='glyphicon glyphicon-warning-sign'></span> Reset experiment",
                        array("controller"=>"trapid","action"=>"empty_experiment",$exp_id),
                        array("class"=>"text-info", "escape"=>false),
                        "Are you sure you want to delete all content from this experiment?"); ?>
                </li>
                <li>
                    <?php    echo $this->Html->link("<span class='glyphicon glyphicon-warning-sign'></span> Delete experiment",
                        array("controller"=>"trapid","action"=>"delete_experiment",$exp_id),
                        array("class"=>"text-danger", "escape"=>false), //, "style"=>"color:red;"
                        "Are you sure you want to delete the experiment?");
                    ?></li>
            </ul>

            </li>
    </ul>
    <div id="header-search-container">
        <?php echo $this->element("search_element_header"); ?>
    </div>

</header>