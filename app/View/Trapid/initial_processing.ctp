    <div class="page-header">
        <h1 class="text-primary">Process transcripts</h1>
    </div>
        <?php // echo $this->element("trapid_experiment"); ?>
    <section class="page-section">
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
        <!-- Similarity search -->
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Similarity search options</h3>
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
                    <p class="text-justify" style="font-size: 88%; margin-top: 10px;"><strong>Nb:</strong> Use <a href='http://www.ncbi.nlm.nih.gov/taxonomy' target='_blank' class="linkout">NCBI Taxonomy</a> to find the closest relative species or best clade.            </p>
                    <div class="form-group">
                                <label for=""><strong>Maximum E-value threshold</strong> (-log<sub>10</sub>)</label>
                                <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_evalue'], "tooltip_placement"=>"top", "use_html"=>"true")); ?>
                                <input class="form-control" id="blast_evalue" max="10" min="2" name="blast_evalue" step="1" value="5" type="number" required></div>
                </div>
            </div>
        </div>

        <!-- GF + annotation -->
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Gene families and annotation options</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for=""><strong>Gene family type</strong></label>
                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_gf_type'], "tooltip_placement"=>"top")); ?>
                        <br>
                        <label class="radio-inline">
                            <input checked="checked" id="gf_type_hom" name="gf_type" type="radio" value="HOM"> Gene families  &nbsp;
                        </label>
                        <label class="radio-inline unavailable">
                            <input id="gf_type_iortho" name="gf_type" type="radio" value="IORTHO" disabled> Integrative orthology
                        </label><br>
                    </div>
                    <div class="form-group">
                        <label for=""><strong>Functional annotation</strong></label>
                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_annotation'], "tooltip_placement"=>"top")); ?>
                        <br>
                        <label class="radio-inline">
                            <input checked="checked" id="functional_annotation_besthit" name="functional_annotation" type="radio" value="besthit"> Best similarity hit  &nbsp;
                        </label>
                        <label class="radio-inline">
                            <input id="functional_annotation_gf" name="functional_annotation" type="radio" value="gf"> Gene families &nbsp;
                        </label>
                        <label class="radio-inline">
                            <input id="functional_annotation_gf_besthit" name="functional_annotation" type="radio" value="gf_besthit"> Both
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
        </div>

        <!-- Extra: tax. binning, RNA genes annotation -->
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Taxonomic binning options</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for="tax-binning"><strong>Perform taxonomic binning</strong></label> &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_tax_binning'], "tooltip_placement"=>"top")); ?>
                        <span class="pull-right" style="margin-right:12%;"><input checked id="tax-binning" name="tax-binning" value="y" type="checkbox"></span>
                    </div>

                    <div class="form-group">
                        <label for="tax-binning" class="text-muted"><strong>Stop after taxonomic binning</strong></label> &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_stop_tax_binning'], "tooltip_placement"=>"top")); ?>
                        <span class="pull-right" style="margin-right:12%;"><input id="not-yet" name="not-yet" value="y" type="checkbox" disabled></span>
                    </div>


                </div>
            </div>
        </div>
    </div>
    <div style="border:1px gray dotted;">
        <div class="form-group">
            <label for="rfam-clans"><strong>RFAM clans</strong></label>
            <label style="margin-left:5px;" class="label label-primary pull-right">work in progress</label>
            <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_rfam_clans'], "tooltip_placement"=>"top")); ?>
            <br>
            <select id="rfam-clans" name="rfam-clans[]" multiple size="8">
<!--                <option value="rrrrrr">dddddd</option>-->
                <?php foreach($rfam_clans as $clan_acc=>$clan_data) {
                    $selected_str = in_array($clan_acc, $rfam_clans_default) ? 'selected' : '';
                    echo "<option value='" . $clan_acc . "' " . $selected_str . ">" . $clan_data["clan_id"] . " (" . $clan_data["clan_desc"]. ")</option>";
                }
                ?>
            </select>
        </div>
    </div>
    <?php
    if (isset($error)) {
        echo "<span class='error'><strong>" . $error . "</strong></span>\n";
        echo "<br>";
    }
    ?>

    <p class="text-center" style="margin-top: 18px;"><button type="submit" class="btn btn-primary">Run initial processing </button> | <a style="cursor: pointer;" onclick="reset_form('initial-processing-form');">Reset all</a></p>
    </form> <!-- Eng initial processing form -->

    <?php
    /*
     * Keep the original form as the new one is still experimental
     *
<section class="page-section">
        <h2>Initial processing options</h2>
        <div class="subdiv">
            <?php
            if (isset($error)) {
                echo "<span class='error'>" . $error . "</span>\n";
            }
            ?>
            <?php
            echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "initial_processing", $exp_id), "type" => "post", "id"=>"initial-processing-form"));
            ?>
            <dl class="standard2">
                <dt>&nbsp;</dt>
                <dd><span>
					<strong>Nb:</strong> Use <a href='http://www.ncbi.nlm.nih.gov/taxonomy' target='_blank' class="linkout">NCBI Taxonomy</a> to find the closest relative species or best clade.
				</span>
                    <br><br>
                </dd>
                <dt>
                    Similarity Search Database Type
                </dt>
                <dd>
                    <select name="blast_db_type" id="blast_db_type" style="width:300px">
                        <?php
                        foreach ($possible_db_types as $k => $v) {
                            echo "<option value='" . $k . "'>" . $v . "</option>\n";
                        }
                        ?>
                    </select>
                </dd>

                <dt>
                    Similarity Search Database  &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_db'], "tooltip_placement"=>"top", "override_span_class"=>"glyphicon glyphicon-question-sign")); ?>
                </dt>
                <dd>
                    <select name="blast_db" id="blast_db" style="width:300px;">
                        <?php
                        foreach ($available_species as $sn => $cn) {
                            echo "<option value='" . $sn . "'>" . $cn . " </option>\n";
                        }
                        ?>
                    </select>
                </dd>

                <dt>
                    Similarity Search E-value &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_evalue'], "tooltip_placement"=>"top", "override_span_class"=>"glyphicon glyphicon-question-sign")); ?>
                </dt>
                <dd>
                    <select name="blast_evalue" id="blast_evalue" style="width:300px;">
                        <?php
                        foreach ($possible_evalues as $k => $v) {
                            $sel = null;
                            if ($k == "10e-5") {
                                $sel = " selected='selected'";
                            }
                            echo "<option value='" . $k . "' $sel>" . $k . "</option>\n";
                        }
                        ?>
                    </select>
                </dd>

                <dt>
                    Gene Family type
                </dt>
                <dd>
                    <select name="gf_type" id="gf_type" style="width:300px;">
                        <?php
                        foreach ($possible_gf_types as $k => $v) {
                            echo "<option value='" . $k . "' id='gf_type_" . strtolower($k) . "'>" . $v . "</option>\n";
                        }
                        ?>
                    </select>
                </dd>
                <dt>
                    Functional annotation &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_annotation'], "tooltip_placement"=>"top", "override_span_class"=>"glyphicon glyphicon-question-sign")); ?>
                </dt>
                <dd>
                    <select name="functional_annotation" id="functional_annotation" style="width:300px;">
                        <?php
                        foreach ($possible_func_annot as $k => $v) {
                            echo "<option value='" . $k . "' >" . $v . "</option>\n";
                        }
                        ?>
                    </select>
                </dd>
                <dt></dt>
                <dd style="max-width:300px; padding-top:5px;">
                    <label for="tax-binning"><strong>Perform taxonomic binning</strong></label> &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['initial_processing_tax_binning'], "tooltip_placement"=>"top", "override_span_class"=>"glyphicon glyphicon-question-sign")); ?>
                <span class="pull-right"><input checked id="tax-binning" name="tax-binning" value="y" type="checkbox"></span>
                </dd>
            </dl>
<!--            <br/>-->
            <button class="btn btn-lg btn-primary" type="submit"><span class="glyphicon glyphicon-chevron-right"></span> Start transcriptome pipeline</button>
            </form>
        </div>
    </section>
    */
    ?>
    </div>


    <script type="text/javascript">
        // Modified to jQuery
        //<![CDATA[

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
            //add all possible gf types (if applicable) to gf selection
            foreach ($possible_gf_types as $k => $v) {
                echo "$('#gf_type').append(createOption('" . $k . "','" . $v . "'));\n";
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
            //add only HOM to gf selection
            echo "$('#gf_type').append(createOption('HOM','" . $possible_gf_types['HOM'] . "'));\n";
            ?>
            <?php endif;?>
        }

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

        }


        // On page load, populate input similarity search database list
        $( document ).ready(function() {
            var blast_db_type =  $("input[name='blast_db_type']:checked").val(); // $("#blast_db_type").val();
            setBlastDbChoices(blast_db_type)
        });
        //]]>
    </script>

</div>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#initial-processing-form")); ?>