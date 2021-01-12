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
    // TODO: only import what is actually used from bootstrap + use minified version
    // Note: we use a legacy version of jQuery because version >=3.0 breaks the GO enrichment graph tooltips (tipsy)
    echo $this->Html->script(array('jquery-2.2.4.min', 'bootstrap-3.3.7.min', 'datatables.min'));
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
    echo $this->Html->css(array('bootstrap_paper', 'datatables.min', 'trapid')) . "\n";
    ?>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <!-- Favicon (if we do not want to keep the PSB bioinformatics one) -->
    <link rel="icon" href="<?php echo $this->webroot . 'favicon.ico'; ?>" type="image/x-icon"/>
</head>
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

            <!--    <div id="footer">-->
            <!-- 	<p style="font-size: 10px; color: #000000; text-align:center;">-->
            <!--	    &nbsp; &nbsp;-->
            <!--	    <br/>-->
            <!--	</p>-->
        </div>
    </div>
</div>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-38245034-1"></script>
<script type="text/javascript">
    // Google analytics
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'UA-38245034-1');

    // Set 'active' class on header/sidebar <li> elements
    // TODO: check if it makes more sense to do it server-side?
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
