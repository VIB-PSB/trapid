<?php
/* This element corresponds to the sidebar (left menu) shown when the user is in an experiment.

It is heavily inspired by:
 * AdminLTE template (https://adminlte.io/themes/AdminLTE/),
 * The Evry iGEM 2015 wiki (http://2015.igem.org/Team:Evry),
 * and this CodePen snippet (https://codepen.io/zavoloklom/pen/dIgco).
 */

$process_state = $exp_info['process_state'];
// All the possible menu links + their text and URLs (when there is one).
$link_text = array(
    "overview"=>array("Overview", $this->Html->Url(array("controller" => "trapid", "action" => "experiment", $exp_id))),
    "import"=>array("Import data", $this->Html->Url(array("controller" => "trapid", "action" => "import_data", $exp_id))),
    "export"=>array("Export data", $this->Html->Url(array("controller" => "trapid", "action" => "export_data", $exp_id))),
    "stats"=>array("Statistics",  ""),
    "gen_stats"=>array("General statistics",  $this->Html->Url(array("controller" => "tools", "action" => "statistics", $exp_id))),
    "len_tr"=>array("Sequence length distribution", $this->Html->Url(array("controller" => "tools", "action" => "length_distribution", $exp_id, "transcript"))),
    "len_orf"=>array("Length distribution ORF", $this->Html->Url(array("controller" => "tools", "action" => "length_distribution", $exp_id, "orf"))),
    "tax_binning"=>array("Taxonomic binning", $this->Html->Url(array("controller" => "tools", "action" => "tax_binning", $exp_id))),
    "gf"=>array("Browse gene families", $this->Html->Url(array("controller" => "gene_family", "action" => "index", $exp_id))),
    "rf"=>array("Browse RNA families (beta)", $this->Html->Url(array("controller" => "rna_family", "action" => "index", $exp_id))),
    "cgfc"=>array("Core GF completeness", $this->Html->Url(array("controller" => "tools", "action" => "core_gf_completeness", $exp_id))),
    "subsets"=>array("Explore subsets", $this->Html->Url(array("controller" => "labels", "action" => "subset_overview", $exp_id))),
    "enrichment"=>array("Subset enrichment", $this->Html->Url(array("controller" => "tools", "action" => "enrichment", $exp_id))),
    "enrichment_go"=>array("GO term enrichment", $this->Html->Url(array("controller" => "tools", "action" => "enrichment", $exp_id, "go"))),
    "enrichment_ipr"=>array("Protein domain enrichment", $this->Html->Url(array("controller" => "tools", "action" => "enrichment", $exp_id, "ipr"))),
    "sankey"=>array("Sankey diagrams", ""),
    "sankey_enriched_go_gf"=>array("Label→Enriched GO→GF", $this->Html->Url(array("controller" => "tools", "action" => "label_enrichedgo_gf2", $exp_id))),
    "sankey_enriched_ipr_gf"=>array("Label→Enriched IPR→GF", $this->Html->Url(array("controller" => "tools", "action" => "label_enrichedinterpro_gf2", $exp_id))),
    "compare_subsets"=>array("Compare subsets", ""),
    "compare_subsets_go"=>array("GO terms between subsets", $this->Html->Url(array("controller" => "tools", "action" => "compare_ratios", $exp_id, "go"))),
    "compare_subsets_ipr"=>array("Protein domains between subsets", $this->Html->Url(array("controller" => "tools", "action" => "compare_ratios", $exp_id, "ipr"))),
    "doc"=>array("Documentation", $this->Html->Url(array("controller" => "documentation", "action" => "index"))),
    "back_exp"=>array("Back to experiments", $this->Html->Url(array("controller" => "trapid", "action" => "experiments")))
);
?>

<script type="text/javascript">
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
        <a class="sidebar-brand" href="<?php echo $this->Html->Url(array("controller" => "trapid", "action" => "experiments")); ?>">TRAPID
            <label class="label label-beta">beta</label>
        </a>
        <!-- Sidebar brand image -->
        <!--        <div class="sidebar-image">-->
        <!--            <img src="">-->
        <!--        </div>-->
    </div>
    <!-- Experiment sidebar navigation -->
    <ul class="nav sidebar-nav">

        <?php
        // if ($exp_info['transcript_count'] != 0)... Check on transcript counts too?
        // First way to adjust availability of sidebar links is by checking the status of the experiment.
        if (in_array($process_state, ["processing", "loading_db", "error"])): ?>
        <?php foreach (["overview", "import", "export"] as $link_id) {
            echo "<li class=\"sidebar-text sidebar-disabled\">"  . $link_text[$link_id][0] . "</li>\n";
        }
        ?>
        <li class="divider"></li>
        <?php foreach (["stats", "tax_binning", "gf", "rf", "cgfc", "subsets", "enrichment", "sankey", "compare_subsets"] as $link_id) {
            echo "<li class=\"sidebar-text sidebar-disabled\">"  . $link_text[$link_id][0] . "</li>\n";
        }
        ?>

        <?php elseif ($process_state == "empty"): ?>
        <?php foreach (["overview", "import"] as $link_id) {
            echo "<li><a href='"  . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
        }
        echo "<li class=\"sidebar-text sidebar-disabled\">" . $link_text["export"][0] . "</li>\n";
        ?>
        <li class="divider"></li>
        <?php foreach (["stats", "tax_binning", "gf", "rf", "cgfc", "subsets", "enrichment", "sankey", "compare_subsets"] as $link_id) {
            echo "<li class=\"sidebar-text sidebar-disabled\">"  . $link_text[$link_id][0] . "</li>\n";
        }
        ?>

        <?php elseif ($process_state == "upload"): ?>
        <?php foreach (["overview", "import", "export"] as $link_id) {
            echo "<li><a href='"  . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
        }
        ?>
        <li class="divider"></li>
        <li class="dropdown">
            <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                <?php echo $link_text["stats"][0];?><b class="caret"></b>
            </a>
            <ul id="stats-dropdown" class="dropdown-menu">
                <?php foreach (["gen_stats", "len_tr"] as $link_id) {
                    echo "<li><a href='"  . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
                }
                ?>
            </ul>
        </li>
        <?php
        echo "<li class=\"sidebar-text sidebar-disabled\">"  . $link_text["tax_binning"][0] . "</li>\n";
        foreach (["gf", "rf"] as $link_id) {
            echo "<li><a href='"  . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
        }
        foreach (["cgfc", "subsets", "enrichment", "sankey", "compare_subsets"] as $link_id) {
            echo "<li class=\"sidebar-text sidebar-disabled\">"  . $link_text[$link_id][0] . "</li>\n";
        }
        ?>

        <?php elseif ($process_state == "finished"): ?>
        <?php foreach (["overview", "import", "export"] as $link_id) {
            echo "<li><a href='"  . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
        }
        ?>
        <li class="divider"></li>
        <li class="dropdown">
            <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                <?php echo $link_text["stats"][0];?><b class="caret"></b>
            </a>
            <ul id="stats-dropdown" class="dropdown-menu">
                <?php foreach (["gen_stats", "len_tr"] as $link_id) {
                    echo "<li><a href='"  . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
                }
                ?>
            </ul>
        </li>
        <?php
        if ($exp_info['perform_tax_binning'] == 1) {
            echo "<li><a href='"  . $link_text["tax_binning"][1] . "'>" . $link_text["tax_binning"][0] . "</a></li>\n";
        }
        else {
            echo "<li class=\"sidebar-text sidebar-disabled\">"  . $link_text["tax_binning"][0] . "</li>\n";
        }
        foreach (["gf", "rf", "cgfc"] as $link_id) {
            echo "<li><a href='"  . $link_text[$link_id][1] . "'>" . $link_text[$link_id][0] . "</a></li>\n";
        }
        ?>
        <?php if($exp_info['label_count'] == 0): ?>
            <?php
            foreach (["subsets", "enrichment", "sankey", "compare_subsets"] as $link_id) {
                echo "<li class=\"sidebar-text sidebar-disabled\">"  . $link_text[$link_id][0] . "</li>\n";
            }
        ?>
        <?php else: ?>
            <?php echo "<li><a href='"  . $link_text["subsets"][1] . "'>" . $link_text["subsets"][0] . "</a></li>\n"; ?>
                    <?php echo "<li><a href='"  . $link_text["enrichment"][1] . "'>" . $link_text["enrichment"][0] . "</a></li>\n"; ?>

            <li class="dropdown">
                <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                    <?php echo $link_text["sankey"][0];?><b class="caret"></b>
                </a>
                <ul id="stats-dropdown" class="dropdown-menu">
                    <?php echo "<li><a href='"  . $link_text["sankey_enriched_go_gf"][1] . "'>" . $link_text["sankey_enriched_go_gf"][0] . "</a></li>\n"; ?>
                    <?php echo "<li><a href='"  . $link_text["sankey_enriched_ipr_gf"][1] . "'>" . $link_text["sankey_enriched_ipr_gf"][0] . "</a></li>\n"; ?>
                </ul>
            </li>

        <?php if($exp_info['label_count'] > 1): ?>
        <li class="dropdown">
                <a class="ripple-effect dropdown-toggle" href="#" data-toggle="dropdown">
                    <?php echo $link_text["compare_subsets"][0];?><b class="caret"></b>
                </a>
                <ul id="stats-dropdown" class="dropdown-menu">
                    <?php echo "<li><a href='"  . $link_text["compare_subsets_go"][1] . "'>" . $link_text["compare_subsets_go"][0] . "</a></li>\n"; ?>
                    <?php echo "<li><a href='"  . $link_text["compare_subsets_ipr"][1] . "'>" . $link_text["compare_subsets_ipr"][0] . "</a></li>\n"; ?>
                </ul>
        </li>
        <?php else : ?>
        <?php echo "<li class=\"sidebar-text sidebar-disabled\">"  . $link_text["compare_subsets"][0] . "</li>\n"; ?>
        <?php endif; // End -- label count > 1 ?>
        <?php endif; //  End -- label count == 0 ?>
    <?php endif; ?>
    </ul>
    <!-- Sidebar divider -->
    <!--     <div class="sidebar-divider"></div>-->
    <div style="position:relative;">
        <ul class="nav sidebar-nav">
            <li class="divider"></li>
            <!--    <div class="dropdown-header">Misc</div>-->
            <?php echo "<li><a href='"  . $link_text["doc"][1] . "'>" . $link_text["doc"][0] . "</a></li>\n"; ?>
            <?php echo "<li><a href='"  . $link_text["back_exp"][1] . "'>" . $link_text["back_exp"][0] . "</a></li>\n"; ?>
        </ul>
    </div>
    <!-- Sidebar text -->
    <!--  <div class="sidebar-text">Text</div> -->
</aside>
