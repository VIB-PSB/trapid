<?php

// Intermediate view handling long-running core GF completeness jobs:
//    1. Display a loading message in the results div (`#display-results`) and reload this view after `$interval` milliseconds.
//    2. Increment ellapsed time: if the job runs longer than this duration, trigger rendering of an error message.

if (isset($interval_ms, $max_duration_ms)): ?>

<div id="loading">
        <div class="text-center">
            <div class="ajax-spinner"></div><br>
            Running... Please wait.
        </div>
    </div>
</div>

<script defer="defer" type="text/javascript">
const intervalMs = <?php echo $interval_ms; ?>;
const maxDurationMs = <?php echo $max_duration_ms; ?>;
const baseUrl = "<?php echo $this->Html->url(['controller' => 'tools', 'action' => 'handle_core_gf_completeness']); ?>";
const params = <?php echo json_encode([
    $exp_id,
    $cluster_job_id,
    $clade_tax_id,
    $label,
    $tax_source,
    $species_perc,
    $top_hits
]); ?>

if (coreGfNS.ellapsedTimeMs + intervalMs > maxDurationMs) {
    params.push('timeout');
}

const reloadUrl = [baseUrl, ...params].join('/');

function reloadCoreGfJob(selector, url) {
    $(selector).load(url);
    coreGfNS.ellapsedTimeMs += intervalMs;
}

$(document).ready(function() {
    const timeoutId = setTimeout(reloadCoreGfJob, intervalMs, '#display-results', reloadUrl);
    coreGfNS.timeoutId = timeoutId;
});
</script>
<?php endif; ?>
