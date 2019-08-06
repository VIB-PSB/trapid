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
	echo $this->Html->script(array('jquery-3.1.1.min', 'bootstrap-3.3.7.min'));
	// echo $this->Html->script(array('prototype-1.7.0.0','scriptaculous'));
    // TODO: only import what is actually used from bootstrap + use minified version
	// echo $this->Html->script(array('prototype-1.7.0.0','swfobject', 'bootstrap-3.3.7'));
	// echo $this->Html->script(array('swfobject', 'bootstrap-3.3.7'));
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
	// echo $this->Html->css(array('bootstrap-3.3.7'))."\n";
//	 echo $this->Html->css(array('bootstrap-3.3.7', 'trapid'))."\n";
	echo $this->Html->css(array('bootstrap_paper', 'trapid'))."\n";
	// echo $this->Html->css(array('trapid'))."\n";
    ?>
  <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
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
<!--                --><?php //echo $this->Html->link("Project leader",array("controller"=>"documentation","action"=>"about")); ?><!-- &nbsp; &nbsp;</p>-->
<!--        </div>-->
<!--    </footer>-->
    <!-- End footer -->

<!--    <div id="footer">-->
<!-- 	<p style="font-size: 10px; color: #000000; text-align:center;">-->
<!--	    &nbsp; &nbsp;-->
<!--	    <br/>-->
<!--	</p>-->
    </div>

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-38245034-1"></script>
<script type="text/javascript">
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'UA-38245034-1');
</script>
<script type="text/javascript">
    // A small utility function that enable to add the 'active' class on navigation bar items if they contain a string
    // TODO: find something cool to add 'active' class to navbar <li> elements. Maybe from CakePHP instead?
    function activeItem(itemText) {
        // Add active class to menu item containing 'itemText'
        $('.navbar-nav li').filter(function() { return $.text([this]).indexOf(itemText) > -1; }).addClass('active');
    }
    activeItem();

    // Cookie acceptance JS
    (function(){
        var msg = "We use cookies to provide anonymous statistics that help us improve our website. By continuing to use the site without changing settings, you are agreeing to our use of cookies.";
        var closeBtnMsg = "OK";

        // Check cookies
        if(document.cookie){
            var cookieString = document.cookie;
            var cookieList = cookieString.split(";");
            // if cookie named OKCookie is found, return
            for(x = 0; x < cookieList.length; x++){
                if (cookieList[x].indexOf("Trapid2OKCookie") != -1){return};
            }
        }

        // Create cookie acceptance message element
        var docRoot = document.body;
        var okC = document.createElement("div");
        okC.setAttribute("id", "okCookie");
        var okCp = document.createElement("p");
        okCp.setAttribute("class", "text-justify");
        var okcText = document.createTextNode(msg);

        // Close button
        var okCclose = document.createElement("a");
        var okcCloseText = document.createTextNode(closeBtnMsg);
        okCclose.setAttribute("href", "#");
        okCclose.setAttribute("id", "okClose");
        okCclose.appendChild(okcCloseText);
        okCclose.addEventListener("click", closeCookie, false);

        // Add to DOM
        okCp.appendChild(okcText);
        okC.appendChild(okCp);
        okC.appendChild(okCclose);
        docRoot.appendChild(okC);

        okC.classList.add("okcBeginAnimate");

        function closeCookie(){
            var cookieExpire = new Date();
            cookieExpire.setFullYear(cookieExpire.getFullYear() +2);
            document.cookie="Trapid2OKCookie=1; expires=" + cookieExpire.toGMTString() + ";";
            docRoot.removeChild(okC);
        }
    })();
</script>
</body>
</html>
