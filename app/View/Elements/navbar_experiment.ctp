<script type="text/javascript">
    /* Sidebar is inspired by AdminLTE template (https://adminlte.io/themes/AdminLTE/), iGEM 2015 Evry wiki
    (http://2015.igem.org/Team:Evry) and this CodePen snippet (https://codepen.io/zavoloklom/pen/dIgco).
    */

    // Toggle sidebar
    $(document).ready(function () {
        var overlay = $('.sidebar-overlay');

        $('.sidebar-toggle').on('click', function () {
            var sidebar = $('#sidebar');
            sidebar.toggleClass('open');
            if ((sidebar.hasClass('sidebar-fixed-left') || sidebar.hasClass('sidebar-fixed-right')) && sidebar.hasClass('open')) {
                overlay.addClass('active');
            } else {
                overlay.removeClass('active');
            }
        });
        overlay.on('click', function () {
            $(this).removeClass('active');
            $('#sidebar').removeClass('open');
        });

    });

    // Side content
    $(document).ready(function () {

        var sidebar = $('#sidebar');
        var sidebarHeader = $('#sidebar .sidebar-header');
        var sidebarImg = sidebarHeader.css('background-image');
        var toggleButtons = $('.sidebar-toggle');


        // Sidebar position
        $('#sidebar-position').change(function () {
            var value = $(this).val();
            sidebar.removeClass('sidebar-fixed-left sidebar-fixed-right sidebar-stacked').addClass(value).addClass('open');
            if (value == 'sidebar-fixed-left' || value == 'sidebar-fixed-right') {
                $('.sidebar-overlay').addClass('active');
            }
        });

        // Changin sidebar theme (to remove once we are happy with one theme?)
        $('#sidebar-theme').change(function () {
            var value = $(this).val();
            sidebar.removeClass('sidebar-default sidebar-inverse sidebar-colored sidebar-colored-inverse').addClass(value)
        });
    });

    (function (removeClass) {
        jQuery.fn.removeClass = function (value) {
            if (value && typeof value.test === "function") {
                for (var i = 0, l = this.length; i < l; i++) {
                    var elem = this[i];
                    if (elem.nodeType === 1 && elem.className) {
                        var classNames = elem.className.split(/\s+/);

                        for (var n = classNames.length; n--;) {
                            if (value.test(classNames[n])) {
                                classNames.splice(n, 1);
                            }
                        }
                        elem.className = jQuery.trim(classNames.join(" "));
                    }
                }
            } else {
                removeClass.call(this, value);
            }
            return this;
        }
    })(jQuery.fn.removeClass);

    // Add animation to sidebar dropdown elements (jQuery slideDown/slideUp). To replace by CSS animation if possible.
    $(document).ready(function () {
        var dropdown = $('.dropdown');
        var slideDuration = 150; // Animation duration in ms
        // Add slidedown animation to dropdown
        dropdown.on('show.bs.dropdown', function (e) {
            $(this).find('.dropdown-menu').first().stop(true, true).slideDown(slideDuration);
        });
        // Add slideup animation to dropdown
        dropdown.on('hide.bs.dropdown', function (e) {
            $(this).find('.dropdown-menu').first().stop(true, true).slideUp(slideDuration);
        });
        // Toggle sidebar shadow ('sidebar tools' on the experiment overview page)
        $('#toggle-shadow').on('click', function () {
            var sidebar = $('#sidebar');
            sidebar.toggleClass('sidebar-shadow');
        });
    });
</script>
<!-- Overlay (if fixed sidebar) -->
<div class="sidebar-overlay"></div>
<!-- Experiment navbar (sidebar) -->
<aside id="sidebar" class="sidebar sidebar-colored open sidebar-stacked sidebar-shadow" role="navigation">

    <!-- Sidebar header -->
    <div class="sidebar-header">
        <a class="sidebar-brand" href="#">TRAPID <!-- font-family: 'Redensek', arial; -->
            <label class="label label-beta">beta</label>
        </a>
        <!-- Sidebar brand image -->
        <!--        <div class="sidebar-image">-->
        <!--            <img src="">-->
        <!--        </div>-->
    </div>
    <?php
    $process_state = $exp_info['process_state'];
    //                $process_state =
    //                echo "<pre>".$process_state."</pre><br>";
    //                echo "<pre>".$exp_info['label_count']."</pre><br>";
    // print_r($exp_info);
    ?>
    <!-- Experimenr sidebar navigation -->
    <ul class="nav sidebar-nav">
        <li>
            <?php echo $this->Html->link("Overview", array("controller" => "trapid", "action" => "experiment", $exp_id)); ?>
        </li>
        <li class="dropdown">
            <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                Import data
                <b class="caret"></b>
            </a>
            <ul id="import-dropdown" class="dropdown-menu">
                <?php if ($process_state == "empty" || $process_state == "upload") : ?>
                    <li>
                        <?php echo $this->Html->link("Transcripts", array("controller" => "trapid", "action" => "import_data", $exp_id)); ?>
                    </li>
                <?php else : ?>
                    <li class="sidebar-text sidebar-disabled">Transcripts</li>
                <?php endif ?>
                <?php if ($exp_info['transcript_count'] != 0) : ?>
                    <li>
                        <?php echo $this->Html->link("Transcript subsets/labels", array("controller" => "trapid", "action" => "import_labels", $exp_id)); ?>
                    </li>
                <?php else : ?>
                    <li class="sidebar-text sidebar-disabled">Transcript subsets/labels</li>
                <?php endif ?>
            </ul>
        </li>
        <?php if ($exp_info['transcript_count'] != 0) : ?>
            <li>
                <?php echo $this->Html->link("Export data", array("controller" => "trapid", "action" => "export_data", $exp_id)); ?>
            </li>
        <?php else : ?>
            <li class="sidebar-text sidebar-disabled">Export data</li>

        <?php endif ?>

        <li class="divider"></li>
        <?php if ($exp_info['transcript_count'] == 0) :
            // No transcripts == every tool is  disabled ?>
            <li class="sidebar-text sidebar-disabled">Statistics</li>
            <li class="sidebar-text sidebar-disabled">Taxonomic binning</li>
            <li class="sidebar-text sidebar-disabled">Browse gene families</li>
<!--            <li class="sidebar-text sidebar-disabled">Expanded/depleted GFs</li>-->
            <li class="sidebar-text sidebar-disabled">Core GF completeness</li>
        <?php else :
            // Transcripts uploaded == 'statistics' + GFs available ?>
            <li class="dropdown">
                <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                    Statistics
                    <b class="caret"></b>
                </a>
                <ul id="stats-dropdown" class="dropdown-menu">
                    <li>
                        <?php echo $this->Html->link("General statistics", array("controller" => "tools", "action" => "statistics", $exp_id)); ?>
                    </li>
                    <li>
                        <?php echo $this->Html->link("Length distribution transcripts", array("controller" => "tools", "action" => "length_distribution", $exp_id, "transcript")); ?>
                    </li>
                    <li>
                        <?php echo $this->Html->link("Length distribution ORF", array("controller" => "tools", "action" => "length_distribution", $exp_id, "orf")); ?>
                    </li>
                </ul>
            </li>
            <li>
                <?php echo $this->Html->link("Taxonomic binning <span class=\"label label-primary\">test</span>", array("controller" => "tools", "action" => "tax_binning", $exp_id), array("escape" => false)); ?>
            </li>
            <li>
                <?php echo $this->Html->link("Browse gene families", array("controller" => "gene_family", "action" => "index", $exp_id)); ?>
            </li>
<!--            <li>-->
<!--                --><?php //echo $this->Html->link("Expanded/depleted GFs", array("controller" => "gene_family", "action" => "expansion", $exp_id)); ?>
<!--            </li>-->
        <?php endif ?>
        <?php
        // Some items should be unavailable until the experiment is fully finished? For example tax binning
        if ($process_state == "finished"): ?>
            <li>
                <?php echo $this->Html->link("Core GF completeness <span class=\"label label-primary\">test</span>", array("controller" => "tools", "action" => "core_gf_completeness", $exp_id), array("escape" => false)); ?>
            </li>
        <?php    // Some elements are still disabled
        else: ?>
            <li class="sidebar-text sidebar-disabled">Core GF completeness</li>
        <?php endif ?>
        <?php if ($exp_info['label_count'] < 1) :
            // If no subsets are defined, lock other tools. ?>
            <li class="sidebar-text sidebar-disabled">Explore subsets</li>
            <li class="sidebar-text sidebar-disabled">Subset enrichment</li>
            <li class="sidebar-text sidebar-disabled">Sankey diagrams</li>
        <?php else : ?>
            <li>
                <?php echo $this->Html->link("Explore subsets", array("controller" => "labels", "action" => "subset_overview", $exp_id)); ?>
            </li>
            <li class="dropdown">
                <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                    Subset enrichment
                    <b class="caret"></b>
                </a>
                <ul id="stats-dropdown" class="dropdown-menu">
                    <li>
                        <?php echo $this->Html->link("GO term enrichment", array("controller" => "tools", "action" => "enrichment", $exp_id, "go")); ?>
                    </li>
                    <li>
                        <?php echo $this->Html->link("Protein domain enrichment", array("controller" => "tools", "action" => "enrichment", $exp_id, "ipr")); ?>
                    </li>
                </ul>
            </li>

            <li class="dropdown">
                <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                    Sankey diagrams
                    <b class="caret"></b>
                </a>
                <ul id="stats-dropdown" class="dropdown-menu">
                    <li>
                        <?php echo $this->Html->link("Label→Enriched GO→GF", array("controller" => "tools", "action" => "label_enrichedgo_gf2", $exp_id)); ?>
                    </li>
                    <li>
                        <?php echo $this->Html->link("Label→Enriched IPR→GF", array("controller" => "tools", "action" => "label_enrichedinterpro_gf2", $exp_id)); ?>
                    </li>
                </ul>
            </li>
        <?php endif ?>
        <?php if ($exp_info['label_count'] < 2) : ?>
            <li class="sidebar-text sidebar-disabled">Compare subsets</li>
        <?php else : ?>
            <li class="dropdown">
                <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                    Compare subsets
                    <b class="caret"></b>
                </a>
                <ul id="stats-dropdown" class="dropdown-menu">
                    <li>
                        <?php echo $this->Html->link("GO terms between subsets", array("controller" => "tools", "action" => "compare_ratios", $exp_id, "go")); ?>
                    </li>
                    <li>
                        <?php echo $this->Html->link("Protein domains between subsets", array("controller" => "tools", "action" => "compare_ratios", $exp_id, "ipr")); ?>
                    </li>
                </ul>
            </li>
        <?php endif ?>
    </ul>
    <!-- Sidebar divider -->
    <!--     <div class="sidebar-divider"></div>-->
    <div style="position:relative;">
        <ul class="nav sidebar-nav">
            <li class="divider"></li>
            <!--    <div class="dropdown-header">Misc</div>-->
            <li><?php echo $this->Html->link("Documentation", array("controller" => "documentation", "action" => "index"), array("escape" => false)); ?></li>
            <li><?php echo $this->Html->link("Back to experiments", array("controller" => "trapid", "action" => "experiments")); ?></li>
        </ul>
    </div>
    <!-- Sidebar text -->
    <!--  <div class="sidebar-text">Text</div> -->
</aside>
