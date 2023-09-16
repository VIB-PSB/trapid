<?php

// Intermediate view handling long-running core GF completeness jobs:
//    1. Show a loading indicator in the results dive (`$result_elmt_id`), and reload itself after `$interval` milliseconds.
//    2. Increment ellapsed time. 

// TODO: if we go beyond that duration, render a timeout message (and job gets deleted from cluster).

if (isset($interval_ms, $max_duration_ms)): ?>

<div id="loading">
        <div class="text-center">
            <div class="ajax-spinner"></div><br>
            Running... Please wait.
        </div>
    </div>
</div>

<!-- Reload state of job after `$interval` seconds -->
<script defer="defer" type="text/javascript">
const loadUrl = "<?php echo $this->Html->url(['controller' => 'tools', 'action' => 'handle_core_gf_completeness', $exp_id, $cluster_job_id, $clade_tax_id, $label, $tax_source, $species_perc, $top_hits]); ?>";
const intervalMs = <?php echo $interval_ms; ?>;
const targetElmtSelector = "#<?php echo $result_elmt_id ?>";
$(document).ready(function() {
    function reloadJobState(selector) {
        $(targetElmtSelector).load(loadUrl);
        coreGfNS.ellapsedTimeMs += intervalMs;
    }
        const timeoutId = setTimeout(reloadJobState, intervalMs, targetElmtSelector);
        coreGfNS.timeoutId = timeoutId;
    });
</script>
<?php endif; ?>
