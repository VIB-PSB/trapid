<!-- Experiment main menu (header) -->
<header class="header-bar navbar-inverse navbar-fixed-top">
    <ul class="nav navbar-nav navbar-left">
        <li style="height: 50px; border-right: 1px #29B6F6 solid">
            <a class="sidebar-toggle"><span class="glyphicon glyphicon-menu-hamburger" style=""></span></a>
            <!--            <button class="sidebar-toggle" title="Togueule ze meniou">-->
            <!--                <span class="sr-only">Toggle navigation</span>-->
            <!--                <span class="icon-bar"></span>-->
            <!--                <span class="icon-bar"></span>-->
            <!--                <span class="icon-bar"></span>-->
            <!--            </button>-->
        </li>
    </ul>
    <ul class="nav navbar-nav">
        <li>
            <a class="navbar-brand"><?php  echo (isset($exp_title) ? $exp_title : "No title?");?></a>
        </li>
    </ul>
    <ul class="nav navbar-nav navbar-right">
        <?php echo $this->element("search_element_header");?>
        <li><?php echo $html->link("Manage jobs",array("controller"=>"trapid","action"=>"manage_jobs", $exp_id)); ?></li>
        <li><a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <!--                    <span class="glyphicon glyphicon-cog"></span> -->
                Settings <span class="caret"></span></a>
            <ul class="dropdown-menu-right dropdown-menu">
                <li><?php echo $html->link("View log",array("controller"=>"trapid","action"=>"view_log",$exp_id));?></li>
                <li><?php echo $html->link("Share experiment",array("controller"=>"trapid","action"=>"experiment_access",$exp_id));?></li>
                <li><?php echo $html->link("Change settings",array("controller"=>"trapid","action"=>"experiment_settings",$exp_id));?></li>
                <li><?php
                    echo $html->link("<span class='glyphicon glyphicon-warning-sign'></span> Reset experiment",
                        array("controller"=>"trapid","action"=>"empty_experiment",$exp_id),
                        array("class"=>"text-info", "escape"=>false),
                        "Are you sure you want to delete all content from this experiment?"); ?>
                </li>
                <li>
                    <?php    echo $html->link("<span class='glyphicon glyphicon-warning-sign'></span> Delete experiment",
                        array("controller"=>"trapid","action"=>"delete_experiment",$exp_id),
                        array("class"=>"text-danger", "escape"=>false), //, "style"=>"color:red;"
                        "Are you sure you want to delete the experiment?");
                    ?></li>
            </ul></li>
    </ul>
</header>