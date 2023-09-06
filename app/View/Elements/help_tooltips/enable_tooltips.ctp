<script>
    // Enable Bootstrap tooltips
    $(function() {
        $('[data-toggle="tooltip"]').tooltip({
            <?php echo isset($container) ? "container: \"" . $container . "\"" : "container: 'body'"; ?>
        });
    });
</script>
