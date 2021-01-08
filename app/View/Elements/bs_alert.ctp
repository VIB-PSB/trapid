<?php
// Create a bootstrap alert element for given alert class `$alert_class` and content `$alert_content`.

if(isset($alert_class) & isset($alert_content)) {
    echo "<div class=\"alert " . $alert_class . " alert-dismissible\" role=\"alert\">";
    echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>";
    echo $alert_content;
    echo"</div>";
}