<div>
    <?php
    //echo $javascript->link(array('prototype-1.7.0.0'));
    ?>
    <div class="page-header">
        <h1 class="text-primary">Process transcripts</h1>
    </div>
    <div class="subdiv">
        <?php echo $this->element("trapid_experiment"); ?>

        <h2>Overview</h2>
        <div class="subdiv">
            <p class="text-justify">The transcript pipeline of the PLAZA workbench can be used to analyze transcripts (provided by the user) of
            species not present in the PLAZA database. This is useful for e.g. transcriptome analyzes during specific
            conditions or for species for which no genome is present, only a transcriptome. Transcripts are initially
            associated with PLAZA gene families using a translational approach. Further analyzes are then done on a
                per-family basis.</p>
        </div>
<section class="page-section">
        <h2>Options</h2>
<!--    <form class="form-horizontal">-->
<!--        <div class="form-group">-->
<!--            <label for="" class="col-sm-2 control-label">Email</label>-->
<!--            <div class="col-sm-8">-->
<!--                <input type="email" class="form-control" id="inputEmail3" placeholder="Email">-->
<!--            </div>-->
<!--        </div>-->
<!--        <div class="form-group">-->
<!--            <label for="inputPassword3" class="col-sm-4 control-label">Password</label>-->
<!--            <div class="col-sm-7">-->
<!--                <input type="password" class="form-control" id="inputPassword3" placeholder="Password">-->
<!--            </div>-->
<!--        </div>-->
<!--        <div class="form-group">-->
<!--            <div class="col-sm-offset-4 col-sm-7">-->
<!--                <div class="checkbox">-->
<!--                    <label>-->
<!--                        <input type="checkbox"> Remember me-->
<!--                    </label>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!--        <div class="form-group">-->
<!--            <div class="col-sm-offset-4 col-sm-7">-->
<!--                <button type="submit" class="btn btn-default">Sign in</button>-->
<!--            </div>-->
<!--        </div>-->
<!--    </form>-->
        <div class="subdiv">
            <?php
            if (isset($error)) {
                echo "<span class='error'>" . $error . "</span>\n";
            }
            ?>

            <?php
            echo $form->create("", array("url" => array("controller" => "trapid", "action" => "initial_processing", $exp_id), "type" => "post"));
            ?>
            <dl class="standard2">
                <dt>&nbsp;</dt>
                <dd><span>
					Use <a href='http://www.ncbi.nlm.nih.gov/taxonomy' target='_blank'>NCBI Taxonomy</a> to find the closest relative species or best clade.
				</span>
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
                    Similarity Search Database
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
                    Similarity Search E-value
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
                    Functional annotation
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
            </dl>
            <br/>
            <button class="btn btn-lg btn-primary" type="submit"><span class="glyphicon glyphicon-chevron-right"></span> Start transcriptome pipeline</button>
            </form>
        </div>
    </section>
    </div>

    <script type="text/javascript">
        // Modified to jQuery
        //<![CDATA[
//        console.log("TEST");
        $("#blast_db_type").change(function () {
            var blast_db_type = $("#blast_db_type").val();
//            console.log(blast_db_type);
            if (blast_db_type == "SINGLE_SPECIES") {
                setSingleSpeciesData();
            }
            else if (blast_db_type == "CLADE") {
                setCladeData();
            }
            else if (blast_db_type == "GF_REP") {
                setGfRepData();
            }
        });


        function clearDBSelect() {
            $("#blast_db").empty();
//            var counter = 0;
//            while ($("#blast_db").options.length > 0) {
//                counter++;
//                if (counter > 1000) {
//                    alert("?");
//                    break;
//                }
//                $("#blast_db").remove(0);
//            }
        }

        function clearGFSelect() {
            $("#gf_type").empty();
//            var counter = 0;
//            while ($("gf_type").options.length > 0) {
//                counter++;
//                if (counter > 1000) {
//                    alert("?");
//                    break;
//                }
//                $("gf_type").remove(0);
//            }
        }

        function createOption(value, text) {
            // Return select element option having `value` and labelled with `text`.
            var option = new Option(text, value)
//            var option = document.createElement("option");
//            option.text = text;
//            option.value = value;
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

        //]]>
    </script>

</div>
