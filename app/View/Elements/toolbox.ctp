<div class="panel panel-default" style="max-width:700px;">
    <div class="panel-heading">
        Toolbox
    </div>
    <div class="panel-body">
        <?php foreach ($toolbox as $subtitle => $content) {
            echo '<h5>' . $subtitle . "</h5>\n";
            echo "<ul class='list-unstyled'>";
            foreach ($content as $cont) {
                echo '<li>';
                $desc = $cont[0];
                $link = $cont[1];
                $img = null;
                if (count($cont) > 2) {
                    $img = $cont[2];
                }
                $disabled = false;
                if (count($cont) > 3) {
                    $disabled = $cont[3];
                }
                if ($disabled) {
                    echo "<span class='disabled'>" . $desc . '</span>';
                } else {
                    // If `$link` is an array, multiple attributes/values are represented as associative array (attribute=>value)
                    // Otherwise we just consider `$link` to contain the value for 'href'.
                    if (is_array($link)) {
                        $attr_str = ' ';
                        foreach ($link as $attr => $val) {
                            $attr_str = $attr_str . ' ' . $attr . "='" . $val . "'";
                        }
                        echo '<a' . $attr_str . '>' . $desc . '</a>';
                    } else {
                        echo "<a href='" . $link . "'>" . $desc . '</a>';
                    }
                }
                echo "</li>\n";
            }
            echo "</ul>\n";
        }
        ?>
    </div>
</div>