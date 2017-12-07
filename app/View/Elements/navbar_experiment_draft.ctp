<?php
//echo $helptext; //outputs "Oh, this text is very helpful."
?>
<style>



/* Adding some more new CSS (trying a new layout, 30 of August) */
:focus {
  outline: none;
}

.row {
  margin-right: 0;
  margin-left: 0;
}

.side-menu {
  position: fixed;
  width: 250px; /* 250px; */
  height: calc(100%);
  background-color: #2196f3;
  /* border-right: 10px solid cyan;/*#e7e7e7;*/ */
  transition: background-color 0.2s ease;
}

.side-menu .navbar {
  border: none;
  -webkit-box-shadow: none;
  box-shadow: none;
}

.side-menu .nav li a,
.side-menu .nav li a:focus {
color: #E0E0E0;
transition: color 0.2s ease;
}

.side-menu .nav > li.active > a,
.side-menu .nav > li > a:hover {
color: white;
background-color: #1976d2;
transition: all 0.2s ease;
}

.side-menu .nav > .active > a,
.side-menu .nav > li.active > a,
.side-menu .nav > .active > a:hover,
.side-menu .nav > .active > a:focus {
background-color: #1976d2;
color: white;
}

.side-menu .navbar-header {
  width: 100%;
/*  border-bottom: 1px solid #e7e7e7; */
}

.side-menu .navbar-nav .active a {
  background-color: transparent;
/*  margin-left: 1px; */
  border-left: 4px solid #64b5f6;
  background-color:#1976d2;
}
.side-menu .navbar-nav li {
  display: block;
  width: 100%;
  background-color: #2196f3;
 /* border-bottom: 1px solid #e7e7e7;*/
}
.side-menu .navbar-nav li a {
  padding: 15px;
}
.side-menu .navbar-nav li a .glyphicon,
.side-menu .navbar-nav li a .fa {
  padding-right: 10px;
}

.side-menu .dropdown li {
padding-left:0;}

.side-menu .dropdown {
  border: 0;
  margin-bottom: 0;
  border-radius: 0;
/*  background-color: green;*/
  box-shadow: none;
}
.side-menu .dropdown .caret {
  float: right;
  margin: 9px 5px 0;
}
.side-menu .dropdown .indicator {
  float: right;
}
.side-menu .dropdown > a {
  /*border-bottom: 1px solid #e7e7e7;*/
}
.side-menu .dropdown .panel-body {
  padding: 0;
  background-color: ;
}
.side-menu .dropdown .panel-body .navbar-nav {
  width: 100%;
}
.side-menu .dropdown .panel-body .navbar-nav li {
  padding-left: 0px;
 /* border-bottom: 1px solid #e7e7e7;*/
}
.side-menu .dropdown .panel-body .navbar-nav li > a {
/*padding-left: 45px;*/
padding: 10px 10px 10px 52px;
}
.side-menu .dropdown .panel-body .navbar-nav li:last-child {
  border-bottom: none;
}
.side-menu .dropdown .panel-body .panel > a {
  margin-left: -20px;
  padding-left: 35px;
}
.side-menu .dropdown .panel-body .panel-body {
  margin-left: -15px;
}
.side-menu .dropdown .panel-body .panel-body li {
  padding-left: 30px;
}
.side-menu .dropdown .panel-body .panel-body li:last-child {
 /* border-bottom: 1px solid #e7e7e7;*/
}
.side-menu #search-trigger {
  background-color: #f3f3f3;
  border: 0;
  border-radius: 0;
  position: absolute;
  top: 0;
  right: 0;
  padding: 15px 18px;
}
.side-menu .brand-name-wrapper {
  min-height: 40px;
/*  background-color: #ff0000;*/
}
.side-menu .brand-name-wrapper .navbar-brand {
  /*display: block;*/
}
.side-menu #search {
  position: relative;
  z-index: 1000;
}
.side-menu #search .panel-body {
  padding: 0;
}
.side-menu #search .panel-body .navbar-form {
  padding: 0;
  padding-right: 50px;
  width: 100%;
  margin: 0;
  position: relative;
  /*border-top: 1px solid #e7e7e7;*/
}
.side-menu #search .panel-body .navbar-form .form-group {
  width: 100%;
  position: relative;
}
.side-menu #search .panel-body .navbar-form input {
  border: 0;
  border-radius: 0;
  box-shadow: none;
  width: 100%;
  height: 50px;
}
.side-menu #search .panel-body .navbar-form .btn {
  position: absolute;
  right: 0;
  top: 0;
  border: 0;
  border-radius: 0;
  background-color: #f3f3f3;
  padding: 15px 18px;
}
/* Main body section */
.side-body {
  margin-left: 250px;
}
  .side-menu .navbar .navbar-header {
  background-color: #1976d2;
  border: none;
  }

.side-menu .navbar .navbar-header a{
color: white;
  }


/* small screen */
@media (max-width: 768px) {
  .side-menu {
    position: relative;
    width: 100%;
    height: 0;
    border-right: 0;
z-index: 1000;
  }
  .side-menu .brand-name-wrapper .navbar-brand {
/*    display: inline-block; */
/* display: none; */
  }
.side-menu .brand-name-wrapper {
   display: none;
}
  /* Slide in animation */
  @-moz-keyframes slidein {
    0% {
      left: -250px;
    }
    100% {
      left: 10px;
    }
  }
  @-webkit-keyframes slidein {
    0% {
      left: -250px;
    }
    100% {
      left: 10px;
    }
  }
  @keyframes slidein {
    0% {
      left: -250px;
    }
    100% {
      left: 10px;
    }
  }
  @-moz-keyframes slideout {
    0% {
      left: 0;
    }
    100% {
      left: -250px;
    }
  }
  @-webkit-keyframes slideout {
    0% {
      left: 0;
    }
    100% {
      left: -250px;
    }
  }
  @keyframes slideout {
    0% {
      left: 0;
    }
    100% {
      left: -250px;
    }
  }
  /* Slide side menu*/
  /* Add .absolute-wrapper.slide-in for scrollable menu -> see top comment */
  .side-menu-container > .navbar-nav.slide-in {
    -moz-animation: slidein 200ms forwards;
    -o-animation: slidein 200ms forwards;
    -webkit-animation: slidein 200ms forwards;
    animation: slidein 200ms forwards;
    -webkit-transform-style: preserve-3d;
    transform-style: preserve-3d;
  }
  .side-menu-container > .navbar-nav {
    /* Add position:absolute for scrollable menu -> see top comment */
    position: fixed;
    left: -250px;
    width: 250px;
    top: 43px;
    height: 100%;
    border-right: 1px solid #e7e7e7;
    background-color: #2196f3;
    -moz-animation: slideout 200ms forwards;
    -o-animation: slideout 200ms forwards;
    -webkit-animation: slideout 200ms forwards;
    animation: slideout 200ms forwards;
    -webkit-transform-style: preserve-3d;
    transform-style: preserve-3d;
  }
  /* Uncomment for scrollable menu -> see top comment */
  /*.absolute-wrapper{
        width:285px;
        -moz-animation: slideout 200ms forwards;
        -o-animation: slideout 200ms forwards;
        -webkit-animation: slideout 200ms forwards;
        animation: slideout 200ms forwards;
        -webkit-transform-style: preserve-3d;
        transform-style: preserve-3d;
    }*/
  @-moz-keyframes bodyslidein {
    0% {
      left: 0;
    }
    100% {
      left: 250px;
    }
  }
  @-webkit-keyframes bodyslidein {
    0% {
      left: 0;
    }
    100% {
      left: 250px;
    }
  }
  @keyframes bodyslidein {
    0% {
      left: 0;
    }
    100% {
      left: 250px;
    }
  }
  @-moz-keyframes bodyslideout {
    0% {
      left: 250px;
    }
    100% {
      left: 0;
    }
  }
  @-webkit-keyframes bodyslideout {
    0% {
      left: 250px;
    }
    100% {
      left: 0;
    }
  }
  @keyframes bodyslideout {
    0% {
      left: 250px;
    }
    100% {
      left: 0;
    }
  }
  /* Slide side body*/
  .side-body {
    margin-left: 5px;
    margin-top: 70px;
    position: relative;
    -moz-animation: bodyslideout 200ms forwards;
    -o-animation: bodyslideout 200ms forwards;
    -webkit-animation: bodyslideout 200ms forwards;
    animation: bodyslideout 200ms forwards;
    -webkit-transform-style: preserve-3d;
    transform-style: preserve-3d;
  }
  .body-slide-in {
    -moz-animation: bodyslidein 200ms forwards;
    -o-animation: bodyslidein 200ms forwards;
    -webkit-animation: bodyslidein 200ms forwards;
    animation: bodyslidein 200ms forwards;
    -webkit-transform-style: preserve-3d;
    transform-style: preserve-3d;
  }
  /* Hamburger */
  .navbar-toggle {
    border: 0;
    float: left;
    padding: 18px;
    margin: 0;
    border-radius: 0;
    background-color:  #bbdefb;
  }
.navbar-default .navbar-toggle .icon-bar {
background-color: #ffffff;
}
  /* Search */
  #search .panel-body .navbar-form {
    border-bottom: 0;
  }
  #search .panel-body .navbar-form .form-group {
    margin: 0;
  }
  .navbar-	 {
    /* this is probably redundant */
    position: fixed;
    z-index: 3;
    background-color: #2196f3;
  }
  /* Dropdown tweak */
  #dropdown .panel-body .navbar-nav {
    margin: 0;
  }
}

.page-header {
/*    font-size: 110%!important; */
}

.side-menu a {
/*text-transform:uppercase;*/
}

.side-footer {
  width: 100%;
  position: absolute;
  bottom: 0;
border-top: 4px #64b5f6 solid;
padding-top: 15px;
/*  height: 0px; */
  bottom: 0;
  background-color: #1976d2;
}

/* Using a star (*) here is overkill and bad practice. But it is nearly 3 am. */
.side-menu .nav li.current-panel,
.side-menu .nav li.current-panel * {
background-color: #1976d2;
}

.side-menu .navbar-brand
{
    position: absolute;
    width: 100%;
    left: 0;
    top: 0;
    text-align: center;
    margin: auto;
padding-top: 5px;
}

/* Footer icons ftw */
.side-footer a {
    /*padding-right: 0!important;*/
}

.side-footer a span,
.side-footer a:visited span {
color: #eee;
transition: all 0.2s ease;
}

.side-footer a span:hover {
color: white;
transition: all 0.2s ease;
}

.side-footer a span:hover .fa-inverse {
color: #2196f3;
transition: color 0.2s ease;
}

.side-body a,
.side-body a:visited {
    color: #1ABC9C;
    text-decoration: none;
    padding-bottom: 1px;
    border-bottom: 1px #DDDDDD solid;
    transition: border 0.2s ease;
}

.side-body a:hover,
.side-body a:focus  {
border-bottom: 1px #1ABC9C solid;
transition: border 0.2s ease;
}
</style>


<div class="row">
    <!-- Menu -->
    <div class="side-menu">
    <nav class="navbar navbar-default" role="navigation">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
        <div class="brand-wrapper">
            <!-- Hamburger -->
            <button type="button" class="navbar-toggle" style="background-color:  #3f51b5;">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar" style="color: white;"></span>
                <span class="icon-bar" style="color: white;"></span>
                <span class="icon-bar" style="color: white;"></span>
            </button>

            <!-- Brand -->
            <div class="brand-name-wrapper">
                <a class="navbar-brand" href="#" style="font-family: 'Redensek', arial;">TRAPID
                <label class="label label-success" style="background: #1abc9c;font-family: 'Redensek', arial;">beta</label></a>
            </div>
        </div>
    </div>
    <!-- Main Menu -->
    <div class="side-menu-container" id="side-navigation">
      <!--form class="navbar-form navbar-left form-inline" style='background:#2196f3;'>
        <div class="form-group">
          <input type="text" class="form-control" placeholder="Search" style='width:180px;'>
        </div>
        <button type="submit" class="btn btn-default">Search</button>
    </form-->
        <ul class="nav navbar-nav">
            <!--li><a href="#"><span class="glyphicon glyphicon-home"></span> Home</a></li--> <!-- Home maybe redundant?-->
            <li class='active'><a href="#"><span class="glyphicon glyphicon-home"></span> Experiment overview</a></li>
            <!-- Dropdown-->
            <li class="panel panel-default dropdown" id="project-menu">
                <a data-toggle="collapse" href="#dropdown-project">
                    <span class="glyphicon glyphicon-transfer"></span> Import/Export data <span class="caret"></span>
                </a>
                <div id="dropdown-project" class="panel-collapse collapse">
                    <div class="panel-body">
                        <ul class="nav navbar-nav">
                            <li id="project_description"><a href="#">0</a></li>
                            <li><a href="#">a</a></li>
                            <li><a href="#">b</a></li>
                            <li><a href="#">c</a></li>
                        </ul>
                    </div>
                </div>
            </li>
            <li class="panel panel-default dropdown" id="software-menu">
                <a data-toggle="collapse" href="#dropdown-software">
                    <span class="glyphicon glyphicon-equalizer"></span> Statistics <span class="caret"></span>
                </a>
                <div id="dropdown-software" class="panel-collapse collapse">
                    <div class="panel-body">
                        <ul class="nav navbar-nav">
                            <li><a href="#">0</a></li>
                            <li><a href="#">a</a></li>
                            <li><a href="#">b</a></li>
                            <li><a href="#">c</a></li>
                        </ul>
                    </div>
                </div>
            </li>


            <li class="panel panel-default dropdown" id="practices-menu">
                <a data-toggle="collapse" href="#dropdown-practices">
                    <span class="glyphicon glyphicon-filter"></span> Explore subsets <span class="caret"></span>
                </a>
                <div id="dropdown-practices" class="panel-collapse collapse">
                    <div class="panel-body">
                        <ul class="nav navbar-nav">
            <li><a href="#">Introduction</a></li>
            <li><a href="#">Funding state of art</a></li>
            <li><a href="#">Innovative funding</a></li>
            <li><a href="#">Society perception</a></li>
            <li><a href="#">Conclusion</a></li>
                       </ul>
                    </div>
                </div>
            </li>

            <li class="panel panel-default dropdown" id="team-menu">
                <a data-toggle="collapse" href="#team-dropdown">
                    <span class="glyphicon glyphicon-random"></span> Sankey diagrams <span class="caret"></span>
                </a>
                <div id="team-dropdown" class="panel-collapse collapse">
                    <div class="panel-body">
                        <ul class="nav navbar-nav">
                            <li><a href="#">Members</a></li>
                            <li><a href="#">Attributions</a></li>
                            <li><a href="#">Acknowledgements</a></li>
                            <!--li><a href="#">Media</a></li-->
                        </ul>
                    </div>
                </div>
            </li>

            <li><a href="#"><span class="glyphicon glyphicon-search"></span> Browse gene families</a></li>
            <li><a href="#"><span class="glyphicon glyphicon-list"></span> Find expanded/depleted GFs</a></li>

    </ul>

    </div><!-- /.navbar-collapse -->
</nav>

<!-- Navbar footer: link to documentation and experiments -->
<div class="side-footer">
    <ul class='nav navbar-nav' style='width:100%;'>
        <?php
		/* 	echo $this->Html->link("Experiments",array("controller"=>"trapid","action"=>"experiments"),array("class"=>"mainref"));
			echo "<br/>\n";
			echo $this->Html->link("Documentation",array("controller"=>"documentation","action"=>"index"),array("target"=>"_blank","class"=>"mainref"));
			echo "<br/>\n"; */
		?>
        <li><a href="#" style='background:#1976d2;'><span class='glyphicon glyphicon-question-sign'></span> Documentation</a></li>
        <li><a href="#" style='background:#1976d2;'><span class='glyphicon glyphicon-menu-left'></span> Back to experiments</a></li>
    </ul>
<p class="text-center" style="color: rgba(255,255,255,0.80); font-size: 88%; margin-top:5px;"><small>TRAPID dev team, 2017</small></p>
            </div>
    </div>

<script type="text/javascript">

    $("li.panel > a").click( function () {
        $(this).parent().toggleClass('current-panel');
        $("li.panel").not($(this).parent()).each(function(){
            $('.panel-collapse.in').collapse('hide');
            $(this).removeClass('current-panel');
        })
    });


    // Open panel on the menu if parent <li> is active
    $(document).ready(function() {
        // Get id of active item
        var opened_id = $(".side-menu li.panel.dropdown.active").attr('id');
        // Open corresponding panel/dropdown WITH ANIMATION (uncomment if you want to use)
        // $('#' + opened_id + ' > .panel-collapse').collapse('show');
        // Open corresponding panel/dropdown without animation
        $('#' + opened_id + ' > .panel-collapse').addClass('in');
    });
</script>
