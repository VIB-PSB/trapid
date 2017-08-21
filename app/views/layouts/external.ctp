<?php
    /* Layout for the 'external' part of TRAPID (i.e. all that is not in experiment) */
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
  <meta charset="utf-8">
  <meta name="description" content="TRAPID: Rapid analysis of transcriptomics data">
  <meta name="author" content="Michiel Van Bel">
  <meta name="viewport" content="width=device-width, user-scalable=no">
  <meta http-equiv="Content-Style-Type" content="text/css">
    <?php
	echo $javascript->link(array('jquery-3.1.1.min', 'bootstrap-3.3.7.min'));
	// echo $javascript->link(array('prototype-1.7.0.0','scriptaculous'));
    // TODO: only import what is actually used from bootstrap + use minified version
	// echo $javascript->link(array('prototype-1.7.0.0','swfobject', 'bootstrap-3.3.7'));
	// echo $javascript->link(array('swfobject', 'bootstrap-3.3.7'));
    ?>
    <title>
        <?php
        if ($title_for_layout != WEBSITE_TITLE) {
            echo $title_for_layout . " &middot; TRAPID";
        }
        else {
            // If no title defined (in controller or view), leave the default one.
            echo $title_for_layout;
        }
        ?>
    </title>
    <?php
	// echo $html->charset()."\n"; // Duplicated above?
	// echo $html->css(array('bootstrap-3.3.7'))."\n";
//	 echo $html->css(array('bootstrap-3.3.7', 'trapid'))."\n";
	echo $html->css(array('bootstrap_paper', 'trapid'))."\n";
	// echo $html->css(array('trapid'))."\n";
    ?>
  <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
  <link href="http://allfont.net/allfont.css?fonts=redensek" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css">
  <!-- Favicon (if we do not want to keep the PSB bioinformatics one) -->
  <link rel="icon" href="<?php echo $this->webroot.'favicon.ico';?>" type="image/x-icon" />
  <script src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>
</head>
<body>
    <?php
        // Insert external website navbar
        echo $this->element('navbar_website');
    ?>
	<?php echo $content_for_layout; ?>
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
<!--                --><?php //echo $html->link("Project leader",array("controller"=>"documentation","action"=>"about")); ?><!-- &nbsp; &nbsp;</p>-->
<!--        </div>-->
<!--    </footer>-->
    <!-- End footer -->

<!--    <div id="footer">-->
<!-- 	<p style="font-size: 10px; color: #000000; text-align:center;">-->
<!--	    &nbsp; &nbsp;-->
<!--	    <br/>-->
<!--	</p>-->
    </div>
    <!-- Google Analytics code for TRAPID -->
    <script type="text/javascript">
  	var _gaq = _gaq || [];
  	_gaq.push(['_setAccount', 'UA-38245034-1']);
  	_gaq.push(['_trackPageview']);
  	(function() {
    		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  	})();
    </script>
   <!-- Google Analytics code for bioinformatics (BEG)  -->
    <script type="text/javascript">
  	var _gaq = _gaq || [];
  	_gaq.push(['_setAccount', 'UA-3992537-1']);
  	_gaq.push(['_trackPageview']);
  	(function() {
    		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  	})();
    </script>
<script type="text/javascript">
    // A small utility function that enable to add the 'active' class on navigation bar items if they contain a string
    // TODO: find something cool to add 'active' class to navbar <li> elements. Maybe from CakePHP instead?
    function activeItem(itemText) {
        // Add active class to menu item containing 'itemText'
        $('.navbar-nav li').filter(function() { return $.text([this]).indexOf(itemText) > -1; }).addClass('active');
    }
    activeItem();
</script>
</body>
</html>
