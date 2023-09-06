<?php echo $this->Html->script('sorttable'); ?>
<div class="page-header">
    <h1 class="text-primary"><?php echo $go; ?> <small>children GO terms</small></h1>
</div>
<section class="page-section-sm">
    <h3>Parental GO term</h3>
    <dl class="standard dl-horizontal">
        <dt>GO term</dt>
        <dd>
            <?php
            echo $this->Html->link($go, ['controller' => 'functional_annotation', 'action' => 'go', $exp_id, $go_web]);
            echo ' ';
            echo $this->element('go_category_badge', ['go_category' => $go_info['info'], 'small_badge' => true]);
            echo ' &nbsp; &nbsp; ';
            echo $this->element('linkout_func', ['linkout_type' => 'amigo', 'query_term' => $go_info['name']]);
            echo ' ';
            echo $this->element('linkout_func', ['linkout_type' => 'quickgo', 'query_term' => $go_info['name']]);
            ?>
        </dd>
        <dt>GO description</dt>
        <dd><?php echo $go_info['desc']; ?></dd>
        <dt>#Transcripts</dt>
        <dd><?php echo $num_transcripts; ?></dd>
    </dl>
</section>
<?php if (isset($max_child_gos_reached)) {
    echo "<section class='page-section-xs'>\n";
    echo "<span class='text-danger'>\n";
    echo 'Too many children GOs present (limit is ' . $max_child_gos_reached . ')<br/>';
    echo 'Only top ' . $max_child_gos_reached . ' children GO terms are displayed';
    echo '</span>';
    echo "</section>\n";
} ?>
<section class="page-section-sm">
    <h3>Children GO terms</h3>
    <?php if ($num_child_gos == 0): ?>
        <p class="text-justify text-muted">No children GO terms with associated transcripts available</p>
    <?php else: ?>
        <?php echo $this->element('sorttable'); ?>
        <table class='table table-striped table-condensed table-bordered table-hover sortable'>
            <thead>
                <tr>
                    <th style="width:20%;">Child GO</th>
                    <th style="width:15%;">#Transcripts</th>
                    <th style="width:60%;">Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($child_go_counts as $child_go => $child_go_info) {
                    echo '<tr>';
                    echo '<td>' .
                        $this->Html->link($child_go, [
                            'controller' => 'functional_annotation',
                            'action' => 'go',
                            $exp_id,
                            str_replace(':', '-', $child_go)
                        ]) .
                        ' ' .
                        $this->element('go_category_badge', [
                            'go_category' => $child_go_info['info'],
                            'small_badge' => false,
                            'no_color' => false
                        ]) .
                        '</td>';
                    echo '<td>' . $child_go_info['count'] . '</td>';
                    echo '<td>' . $child_go_info['desc'] . '</td>';
                    echo "</tr>\n";
                } ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
