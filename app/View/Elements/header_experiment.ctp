<?php $dropdown_items = [
    'share' => $this->Html->link('Share experiment', [
        'controller' => 'trapid',
        'action' => 'experiment_access',
        $exp_id
    ]),
    'settings' => $this->Html->link('Change settings', [
        'controller' => 'trapid',
        'action' => 'experiment_settings',
        $exp_id
    ]),
    'empty' => $this->Html->link(
        "<span class='glyphicon glyphicon-warning-sign'></span> Reset experiment",
        ['controller' => 'trapid', 'action' => 'empty_experiment', $exp_id],
        ['escape' => false],
        'Are you sure you want to delete all content from this experiment?'
    ),
    'delete' => $this->Html->link(
        "<span class='glyphicon glyphicon-warning-sign'></span> Delete experiment",
        ['controller' => 'trapid', 'action' => 'delete_experiment', $exp_id],
        ['escape' => false],
        'Are you sure you want to delete this experiment?'
    )
];

$jobs_label = 'Jobs';
if ($job_count > 0) {
    $jobs_label .= "&nbsp;<span class='badge-header'>" . $job_count . '</span>';
}
$exp_management_items = [
    'jobs' => $this->Html->link(
        $jobs_label,
        ['controller' => 'trapid', 'action' => 'manage_jobs', $exp_id],
        ['escape' => false]
    ),
    'log' => $this->Html->link('Log', ['controller' => 'trapid', 'action' => 'view_log', $exp_id])
];
?>
<header id="header-exp" class="header-bar navbar-inverse navbar-fixed-top preload">
    <ul class="nav navbar-nav navbar-left">
        <li>
            <a class="sidebar-toggle" title="Toggle side menu"><span class="glyphicon glyphicon-menu-hamburger"></span></a>
        </li>
    </ul>
    <ul class="nav navbar-nav hidden-xs hidden-sm">
        <li>
            <a class="navbar-brand"><?php echo isset($exp_title) ? $exp_title : 'No title'; ?></a>
        </li>
    </ul>

    <ul class="nav navbar-nav navbar-right hidden-xs hidden-sm">
        <li><?php echo $exp_management_items['jobs']; ?></li>
        <li><?php echo $exp_management_items['log']; ?></li>
        <li><a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <span class="glyphicon glyphicon-cog"></span>
                <span class="hidden">Settings</span>
                <span class="caret"></span></a>
            <ul class="dropdown-menu-right dropdown-menu">
                <li><?php echo $dropdown_items['share']; ?></li>
                <li><?php echo $dropdown_items['settings']; ?></li>
                <li><?php echo $dropdown_items['empty']; ?></li>
                <li><?php echo $dropdown_items['delete']; ?></li>
            </ul>
        </li>
    </ul>
    <ul class="nav navbar-nav navbar-right hidden-md hidden-lg navbar-header">
        <li>
            <a href="#" class="dropdwn-toggle" data-toggle="dropdown" title="Manage experiment">
                <span class="glyphicon glyphicon-option-vertical"></span>
            </a>
            <ul class="dropdown-menu-right dropdown-menu">
                <li><?php echo $exp_management_items['jobs']; ?></li>
                <li><?php echo $exp_management_items['log']; ?></li>
                <li role="separator" class="divider"></li>
                <li><?php echo $dropdown_items['share']; ?></li>
                <li><?php echo $dropdown_items['settings']; ?></li>
                <li><?php echo $dropdown_items['empty']; ?></li>
                <li><?php echo $dropdown_items['delete']; ?></li>
            </ul>
        </li>
    </ul>
    <div id="header-search-container">
        <?php echo $this->element('search_element_header'); ?>
    </div>
</header>
