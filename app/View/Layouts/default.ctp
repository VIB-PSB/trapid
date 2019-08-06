<?php
/* Layout for the 'internal' part of TRAPID (i.e. all that is in experiment) */
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="description" content="TRAPID: Rapid analysis of transcriptomics data">
    <meta name="author" content="Comparative Network Biology group">
    <meta name="viewport" content="width=device-width, user-scalable=no">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <?php
    echo $this->Html->script(array('jquery-3.1.1.min', 'bootstrap-3.3.7.min'));
//     echo $this->Html->script(array('scriptaculous'));
//     TODO: only import what is actually used from bootstrap + use minified version
//     echo $this->Html->script(array('swfobject', 'bootstrap-3.3.7'));
//     echo $this->Html->script(array('swfobject', 'bootstrap-3.3.7'));
    ?>
    <title>
        <?php
        if ($title_for_layout != WEBSITE_TITLE) {
            echo $title_for_layout . " &middot; TRAPID";
        } else {
            // If no title defined (in controller or view), leave the default one.
            echo $title_for_layout;
        }
        ?>
    </title>
    <?php
    // echo $html->charset()."\n"; // Duplicated above?
    // echo $this->Html->css(array('bootstrap-3.3.7'))."\n";
    //	 echo $this->Html->css(array('bootstrap-3.3.7', 'trapid'))."\n";
    echo $this->Html->css(array('bootstrap_paper', 'trapid')) . "\n";
    // echo $this->Html->css(array('trapid'))."\n";
    ?>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css">
    <!-- Favicon (if we do not want to keep the PSB bioinformatics one) -->
    <link rel="icon" href="<?php echo $this->webroot . 'favicon.ico'; ?>" type="image/x-icon"/>
    <script src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>
<!--    <script src="https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js"></script>-->
</head>
<!--<body style="margin-top:50px;">-->
<body>
<?php echo $this->element('navbar_experiment', array("exp_id" => $exp_id, "exp_info" => $exp_info)); ?>
<!-- Page content -->
<div class="content-wrapper">
    <?php
    echo $this->element('header_experiment',
        array("exp_id" => $exp_id, "exp_title" => $exp_info['title'], "job_count"=>$exp_info["job_count"])); //, "active_navbar_item" => "")
    ?>
    <div class="side-content preload">
        <div class="container-fluid">
            <?php echo $this->fetch('content'); ?>
            <!--<br/>
            <div style="float:right;width:105px;background-color:#B5EAAA;border:1px solid #A0C544;font-size:10px;padding:3px;margin-right:5px;">
                Powered by <a href='http://bioinformatics.psb.ugent.be/plaza/'>PLAZA</a>
            </div>
            <div style="clear:both;font-size:8px;margin-bottom:-5px;">&nbsp;</div>
            -->

            <!-- Footer -->
            <!--    <footer style="padding:10px; font-size:90%;">-->
            <!--        <div class="container">-->
            <!--            <hr>-->
            <!--            <p class="text-muted">Remarks, suggestions or questions? Please contact the-->
            <!--                -->
            <?php //echo $this->Html->link("Project leader",array("controller"=>"documentation","action"=>"about")); ?><!-- &nbsp; &nbsp;</p>-->
            <!--        </div>-->
            <!--    </footer>-->
            <!-- End footer -->

            <!--    <div id="footer">-->
            <!-- 	<p style="font-size: 10px; color: #000000; text-align:center;">-->
            <!--	    &nbsp; &nbsp;-->
            <!--	    <br/>-->
            <!--	</p>-->
        </div>
    </div>
</div>
<!-- Google Analytics code for TRAPID -->
<script type="text/javascript">
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-38245034-1']);
    _gaq.push(['_trackPageview']);
    (function () {
        var ga = document.createElement('script');
        ga.type = 'text/javascript';
        ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(ga, s);
    })();
</script>
<!-- Google Analytics code for bioinformatics (BEG)  -->
<script type="text/javascript">
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-3992537-1']);
    _gaq.push(['_trackPageview']);
    (function () {
        var ga = document.createElement('script');
        ga.type = 'text/javascript';
        ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(ga, s);
    })();
</script>

<script type="text/javascript">
    // TODO: find something better to add 'active' class to header/sidebar <li> elements. Maybe from CakePHP instead?
    var header_item_text = "<?php echo (isset($active_header_item) ? $active_header_item : ''); ?>";
    var sidebar_item_text = "<?php echo (isset($active_sidebar_item) ? $active_sidebar_item : ''); ?>";
    // console.log(header_item_text.toString());  // Debug/test
    // console.log(sidebar_item_text.toString());  // Debug/test
    function activeHeaderItem(itemText) {
        // Add active class to menu item containing 'itemText'
        $('.header-bar .navbar-nav li').filter(function () {
            return $.text([this]).indexOf(itemText) > -1;
        }).addClass('active');
    }
    function activeSidebarItem(itemText) {
        // Add active class to menu item containing 'itemText'
        $('.sidebar-nav li').filter(function () {
            return $.text([this]).indexOf(itemText) > -1;
        }).addClass('active');
    }
    if(header_item_text!=='') {
        activeHeaderItem(header_item_text);
    }
    if(sidebar_item_text!=='') {
        activeSidebarItem(sidebar_item_text);
    }

    // Alter sidebar classes depending on screen size, on page load... To improve
    var $sidebar = $('#sidebar');
    $(window).on("load", function() {
        $sidebar.toggleClass('sidebar-fixed-left', $(window).width() < 768);
        $sidebar.toggleClass('open', $(window).width() >= 768);
        $sidebar.toggleClass('sidebar-stacked', $(window).width() >= 768);
        // Remove '.preload' class to enable transitions
        $sidebar.removeClass("preload");
        $(".side-content").removeClass("preload");
        $("#header-exp").removeClass("preload");
    });
    $(window).on("load resize", function() {
        $sidebar.toggleClass('sidebar-fixed-left', $(window).width() < 768);
        $sidebar.toggleClass('sidebar-stacked', $(window).width() >= 768);
    });
</script>

</body>
</html>
