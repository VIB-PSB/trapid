<script>
    // Enable Bootstrap tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip({
        <?php
            if(isset($container)) {
                echo "container: \"" . $container . "\"";
            }
            else {
                echo "container: 'body'";
            }
            ?>
        });
    });
</script>