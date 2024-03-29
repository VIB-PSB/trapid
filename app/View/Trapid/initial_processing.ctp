<?php
// Selectize JS + CSS
echo $this->Html->script('selectize.min.js');
echo $this->Html->css('selectize.paper.css');

//echo $this->Html->css('selectize.default.css');
?>
    <div class="page-header">
        <h1 class="text-primary">Process transcripts</h1>
    </div>
    <section class="page-section-sm">
            <p class="text-justify">TRAPID's transcriptome pipeline can be used to annotate and analyze user-provided transcripts  of
            species that are not present in the selected reference database. This is useful for e.g. transcriptome analyzes during specific
            conditions or for species for which no genome is available. Transcripts are initially
            associated with gene families using a translational approach. Further analyzes are then done on a
                per-family basis.</p>
        <p class="text-justify">Adjust the parameters that TRAPID should use for initial processing by adjusting the values below. </p>
    </section>

    <!-- Initial processing form -->
    <?php
        echo $this->Form->create(false, array(
                "url" => array("controller" => "trapid", "action" => "initial_processing", $exp_id),
                "type" => "post", "id"=>"initial-processing-form"));
    ?>

    <div class="row">
        <div class="col-md-6">
            <!-- Similarity search -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    Similarity search options
                </div>
                <div class="panel-body">
                            <div class="form-group">
                                <label for=""><strong>Similarity search database</strong></label>
                                <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_db'], "tooltip_placement"=>"top")); ?>
                                <br>
                                <label class="radio-inline">
                                        <input id="blast_db_type_clade" name="blast_db_type" type="radio" value="CLADE" checked> Phylogenetic clade &nbsp;
                                    </label>
                                <?php if(isset($no_species_available)): ?>
                                <label class="radio-inline">
                                    <input id="blast_db_type_species" name="blast_db_type" type="radio" value="SINGLE_SPECIES" disabled> Single species
                                </label>
                                <?php else: ?>
                                <label class="radio-inline">
                                    <input id="blast_db_type_species" name="blast_db_type" type="radio" value="SINGLE_SPECIES"> Single species
                                </label>
                                <?php endif; ?>
                                <br>
                                <select class="form-control" id="blast_db" name="blast_db">
                                    <option disabled>Loading list...</option>
                                </select>
                            </div>
                    <p class="text-justify help-block" style="font-size: 88%; margin-top: 10px;"><strong>Nb:</strong> Use <a href='http://www.ncbi.nlm.nih.gov/taxonomy' target='_blank' class="linkout">NCBI Taxonomy</a> to find the closest relative species or best clade.            </p>
                    <div class="form-group">
                                <label for=""><strong>Maximum E-value threshold</strong> (-log<sub>10</sub>)</label>
                                <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_evalue'], "tooltip_placement"=>"top", "use_html"=>"true")); ?>
                                <input class="form-control" id="blast_evalue" max="10" min="2" name="blast_evalue" step="1" value="5" type="number" required></div>
                </div>
            </div>

            <!-- RNA annotation / Infernal / RFAM options -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    RNA annotation options
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for="rfam-clans"><strong>Rfam clans</strong> (max. 10)</label>
                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_rfam_clans'], "tooltip_placement"=>"top")); ?>
<!--                        <label style="margin-right:5px;" class="label label-info pull-right">test</label>-->
                        <br>
                        <select id="rfam-clans" name="rfam-clans[]" multiple size="10">
                            <option value="">Select or search clans...</option>
                            <?php foreach($rfam_clans as $clan_acc=>$clan_data) {
                                $selected_str = in_array($clan_acc, $rfam_clans_default) ? 'selected' : '';
                                echo "<option value='" . $clan_acc . "' " . $selected_str . ">" . $clan_data["clan_id"] . " (" . $clan_data["clan_desc"]. ")</option>";
                            }
                            ?>
                        </select>
                        <p class="help-block" style="font-size: 88%;"><strong>Nb:</strong> More information about Rfam clans can be found on the <a href="http://rfam.xfam.org/browse" class="linkout" target="_blank">Rfam website</a>.</p>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-6">
            <!-- GF + annotation -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    Gene families and annotation options
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for=""><strong>Gene family type</strong></label>
                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_gf_type'], "tooltip_placement"=>"top")); ?>
                        <br>
                        <label class="radio-inline">
                            <input checked="checked" id="gf_type_hom" name="gf_type" type="radio" value="HOM"> Gene families  &nbsp;
                        </label>
                        <?php if(!$tax_scope_data): ?>
                            <label class="radio-inline">
                                <input id="gf_type_iortho" name="gf_type" type="radio" value="IORTHO" diabled> Integrative orthology
                            </label>
                        <?php endif; ?>
                        <br>
                    </div>
                    <div class="form-group<?php if($tax_scope_data){echo " hidden";};?>">
                        <label for=""><strong>Functional annotation</strong></label>
                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_annotation'], "tooltip_placement"=>"top", "use_html"=>"true")); ?>
                        <br>
                        <label class="radio-inline">
                            <input id="functional_annotation_besthit" name="functional_annotation" type="radio" value="besthit"> Best similarity hit  &nbsp;
                        </label>
                        <label class="radio-inline">
                            <input id="functional_annotation_gf" name="functional_annotation" type="radio" value="gf"> Gene families &nbsp;
                        </label>
                        <label class="radio-inline">
                            <input checked="checked" id="functional_annotation_gf_besthit" name="functional_annotation" type="radio" value="gf_besthit"> Both
                        </label>
                        <br>
                    </div>
                    <?php
                    // If `tax_scope_data` is set, display it.
                    if($tax_scope_data): ?>
                    <div class="form-group">
                        <label for=""><strong>Taxonomic scope</strong> (EggNOG)</label>
                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_tax_scope'], "tooltip_placement"=>"top")); ?>
                        <select class="form-control" id="tax-scope" name="tax-scope">
                            <option value="auto">Adjust automatically (recommended)</option>
                            <?php
                            foreach ($tax_scope_data as $level => $name) {
                                echo "<option value='" . $level . "'>" . $name . " </option>\n";
                            }
                            ?>
                        </select>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Extra: tax. binning, sequence tpye, RNA genes annotation -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    Extra options
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for="tax-binning"><strong>Perform taxonomic classification</strong></label> &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_tax_binning'], "tooltip_placement"=>"top")); ?>
                        <span class="pull-right" style="margin-right:12%;"><input id="tax-binning" name="tax-binning" value="y" type="checkbox"></span>
                    </div>

                    <div class="form-group">
                        <label for="use-cds"><strong>Input sequences are CDS</strong></label> &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_use_cds'], "tooltip_placement"=>"top")); ?>
                        <span class="pull-right" style="margin-right:12%;"><input id="use-cds" name="use-cds" value="y" type="checkbox"></span>
                    </div>

<!--                    <div class="form-group">
                        <label for="tax-binning" class="text-muted"><strong>Stop after taxonomic binning</strong></label> &nbsp;<?php /*echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_stop_tax_binning'], "tooltip_placement"=>"top")); */?>
                        <span class="pull-right" style="margin-right:12%;"><input id="not-yet" name="not-yet" value="y" type="checkbox" disabled></span>
                    </div>
-->
                    <!-- Genetic code choice -->
                    <div class="form-group">
                        <label for="transl_table"><strong>Genetic code</strong> (ORF prediction)</label>
                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_transl_table'], "tooltip_placement"=>"top")); ?>
                        <!-- <label style="margin-right:10px;" class="label label-info pull-right">test</label> -->
                        <select class="form-control" id='transl-table' name="transl_table">
                            <?php
                            foreach($transl_table_descs as $idx=>$desc){
                                echo "<option value='" . $idx . "'>" . $idx . " - " . $desc . "</option>\n";
                            }
                            ?>
                        </select>
                        <p class="help-block" style="font-size: 88%;"><strong>Nb:</strong> More information about genetic codes can be found on the <a href="https://www.ncbi.nlm.nih.gov/Taxonomy/taxonomyhome.html/index.cgi?chapter=cgencodes" class="linkout" target="_blank">NCBI Taxonomy</a>.</p>
                    </div>

                </div>
            </div>

        </div>

        <div class="col-md-4">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
        </div>
            <div class="col-md-6">

            </div>
    </div>

    <?php
    if (isset($error)) {
        echo "<span class='error'><strong>" . $error . "</strong></span>\n";
        echo "<br>";
    }
    ?>

    <p class="text-center" style="margin-top: 18px;"><button type="submit" class="btn btn-primary">Run initial processing </button> | <a style="cursor: pointer;" onclick="reset_form('initial-processing-form');">Reset all</a></p>
    </form> <!-- End initial processing form -->
    </div>


    <script type="text/javascript">
        // Modified to jQuery
        $('input[type=radio][name=blast_db_type]').change(function() {
            var blast_db_type = $("input[name='blast_db_type']:checked").val();
            setBlastDbChoices(blast_db_type)
        });


        function clearDBSelect() {
            $("#blast_db").empty();
        }

        function clearGFSelect() {
            $("#gf_type").empty();
        }

        function createOption(value, text) {
            // Return select element option having `value` and labelled with `text`.
            var option = new Option(text, value)
            return option;
        }

        function setSingleSpeciesData() {
            <?php if(array_key_exists("SINGLE_SPECIES", $possible_db_types)) : ?>
            clearDBSelect();
            clearGFSelect();
            <?php
            //add species to database selection
            foreach ($available_species as $sn => $cn) {
                echo "$('#blast_db').append(createOption('" . $sn . "','" . $cn . "'));\n";
            }
            // Make possible GF types available (only if not using EggNOG as ref. db)
            // foreach ($possible_gf_types as $k => $v) {
            //     echo "$('#gf_type').append(createOption('" . $k . "','" . $v . "'));\n";
            // }
            if(!$tax_scope_data) {
                echo "document.getElementById(\"gf_type_iortho\").disabled = false;\n";
            }
            ?>
            <?php endif;?>
        }

        function setCladeData() {
            <?php if(array_key_exists("CLADE", $possible_db_types)) : ?>
            clearDBSelect();
            clearGFSelect();
            <?php
            //add clades to database selection
            foreach ($clades_species as $clade => $species) {
                echo "$('#blast_db').append(createOption('" . $clade . "','" . $clade . "'));\n";
            }
            // If the clade is the default one for the current reference database, select it!
            echo "$('#blast_db').val(\"$default_sim_search_clade\");";
            // Make only HOM GF type available (only if not using EggNOG as ref. db)
            // echo "$('#gf_type').append(createOption('HOM','" . $possible_gf_types['HOM'] . "'));\n";
            if(!$tax_scope_data) {
                echo "document.getElementById(\"gf_type_hom\").checked = true;\n";
                echo "document.getElementById(\"gf_type_iortho\").disabled = true;\n";
             }
            ?>
            <?php endif;?>

        }

        // GF representatives are not used anymore???
        function setGfRepData() {
            <?php if(array_key_exists("GF_REP", $possible_db_types)):?>
            clearDBSelect();
            clearGFSelect();
            <?php
            //add the gf representatives to the database selection
            foreach ($gf_representatives as $gf_rep => $gf_rep2) {
                echo "$('#blast_db').append(createOption('" . $gf_rep . "','Gene family representatives'));\n";
            }
            //add only HOM to gf selection
            echo "$('#gf_type').append(createOption('HOM','" . $possible_gf_types['HOM'] . "'));\n";
            ?>
            <?php endif;?>
        }

        function setBlastDbChoices(blast_db_type) {
            if (blast_db_type == "SINGLE_SPECIES") {
                setSingleSpeciesData();
            }
            else if (blast_db_type == "CLADE") {
                setCladeData();
            }
            else if (blast_db_type == "GF_REP") {
                setGfRepData();
            }
        }


        // Reset submission form
        function reset_form(form_id) {
            var form_elmt = document.getElementById(form_id);
            form_elmt.reset();
            var blast_db_type =  $("input[name='blast_db_type']:checked").val();
            setBlastDbChoices(blast_db_type);
            // Reset Rfam clans
            $("#rfam-clans")[0].selectize.setValue(<?php echo json_encode($rfam_clans_default); ?>);

        }


        // On page load, populate input similarity search database list
        $( document ).ready(function() {
            var blast_db_type =  $("input[name='blast_db_type']:checked").val(); // $("#blast_db_type").val();
            setBlastDbChoices(blast_db_type);
            $("#rfam-clans").selectize({maxItems: 10, sortField: 'text'});
            // $("#transl-table").selectize();
        });
        //]]>
    </script>

</div>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#initial-processing-form")); ?>