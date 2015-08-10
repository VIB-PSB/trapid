<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <?php
	//echo $javascript->link(array('prototype-1.7.0.0','scriptaculous'));
	echo $javascript->link(array('prototype-1.7.0.0','swfobject'));	
    ?>	
    <meta name="description" content="Trapid: Rapid analysis of transcriptomics data" />
    <meta name="author" content="Michiel Van Bel"/>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />	
    <title>
	<?php echo $title_for_layout; ?> 
    </title>	
    <?php
	echo $html->charset()."\n";	
	echo $html->css('trapid')."\n";
    ?>		
</head>
<body>
  
    <div id="title_div">
	<?php echo $html->link("TRAPID: Rapid Analysis of Transcriptome Data",array("controller"=>"pages","action"=>"index")); ?>
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

