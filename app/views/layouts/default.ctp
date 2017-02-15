<!DOCTYPE html>
<html lang="en-US">
<head>
  <meta charset="utf-8">
  <meta name="description" content="TRAPID: Rapid analysis of transcriptomics data">
  <meta name="author" content="Michiel Van Bel">
  <meta http-equiv="Content-Style-Type" content="text/css">
    <?php
	// echo $javascript->link(array('prototype-1.7.0.0','scriptaculous'));
  // TODO: only import what is actually used from bootstrap + use minified version
	echo $javascript->link(array('prototype-1.7.0.0','swfobject', 'bootstrap-3.3.7'));
    ?>
    <title>
	<?php echo $title_for_layout; ?>
    </title>
    <?php
	// echo $html->charset()."\n"; // Duplicated above?
	echo $html->css(array('trapid', 'bootstrap-3.3.7'))."\n";
    ?>
</head>
<body>

    <div id="title_div"><!-- Temporary title to be able to differentiate the two trapid rapidly -->
	<?php echo $html->link("TRAPID 2: Rapid Analysis of Transcriptome Data",array("controller"=>"pages","action"=>"index")); ?>
    </div>

    <div id="container">
	<?php echo $content_for_layout; ?>
	<!--<br/>
	<div style="float:right;width:105px;background-color:#B5EAAA;border:1px solid #A0C544;font-size:10px;padding:3px;margin-right:5px;">
	    Powered by <a href='http://bioinformatics.psb.ugent.be/plaza/'>PLAZA</a>
	</div>
	<div style="clear:both;font-size:8px;margin-bottom:-5px;">&nbsp;</div>
	-->
    </div>

    <div id="footer">
 	<p style="font-size: 10px; color: #000000; text-align:center;">
	    &nbsp; &nbsp; Remarks, suggestions or questions? Please contact the
	    <?php echo $html->link("Project leader",array("controller"=>"documentation","action"=>"about")); ?> &nbsp; &nbsp;
	    <br/>
	</p>
    </div>


    <!-- Google analytics code specifically for trapid -->
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
   <!-- Google analytics code specifically for bioinformatics (BEG)  -->
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

</body>
</html>
