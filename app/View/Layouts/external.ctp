<?php
    /* Layout for the 'external' part of TRAPID (i.e. all that is not in experiment) */
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
  <meta charset="utf-8">
  <meta name="description" content="TRAPID: Rapid analysis of transcriptomics data">
  <meta name="author" content="The CNB group at VIB-UGent">
  <meta name="viewport" content="width=device-width, user-scalable=no">
  <meta http-equiv="Content-Style-Type" content="text/css">
    <?php
    // TODO: only import what is actually used from bootstrap + use minified version
    // Note: we use a legacy version of jQuery because version >=3.0 breaks the GO enrichment graph tooltips (tipsy)
    echo $this->Html->script(array('jquery-2.2.4.min', 'bootstrap-3.3.7.min'));
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
	echo $this->Html->css(array('bootstrap_paper', 'trapid'))."\n";
    ?>
  <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<!--  <link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css">-->
  <!-- Favicon (if we do not want to keep the PSB bioinformatics one) -->
  <link rel="icon" href="<?php echo $this->webroot.'favicon.ico';?>" type="image/x-icon" />
<!--  <script src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>-->
</head>
<body class="external">
    <?php
        // Insert external website navbar and content
        echo $this->element('navbar_website');
        echo $this->fetch('content');
    ?>

    <!-- Footer -->
    <footer class="footer">
    <div class="container">
        <span class="text-muted">Powered by the <a href="http://bioinformatics.psb.ugent.be/cnb" target="_blank">CNB group</a> at <a href="https://psb.ugent.be/" target="_blank">VIB-UGent<span class="hidden-xs"> Center for Plant Systems Biology</span></a>.</span>
        <span class="text-muted pull-right">&COPY; 2021 TRAPID</span>
    </div>
    </footer>
    <!-- End footer -->


<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-38245034-1"></script>
<script type="text/javascript">
    // Google analytics
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'UA-38245034-1');

    // A small utility function that enable to add the 'active' class on navigation bar items if they contain a string
    var header_item_text = "<?php echo (isset($active_header_item) ? $active_header_item : ''); ?>";
    function activeItem(itemText) {
        // Add active class to menu item containing 'itemText'
        $('.navbar-nav li').filter(function() { return $.text([this]).indexOf(itemText) > -1; }).addClass('active');
    }

    if(header_item_text !== '') {
        activeItem(header_item_text);
    }

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
