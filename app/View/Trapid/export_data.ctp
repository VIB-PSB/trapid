<?php
    $unfinished = null;
    if ($exp_info['process_state'] != "finished") {
        $unfinished = " disabled='disabled' ";
    }
?>


<div>
    <div class="page-header">
        <h1 class="text-primary">Export data</h1>
    </div>

    <?php // echo $this->element("trapid_experiment"); ?>
    <p class="text-justify">Detailed descriptions and minimal examples of export files can be found in the
        <?php echo $this->Html->link("documentation", array("controller" => "documentation", "action" => "general", "#"=>"data-export")); ?>.
    </p>
    <p class="text-justify"><strong>Note: </strong> exported files are generated on-the-fly and can take a while (up to ~1 minute) to be created.</p>
    <!-- Boot strap alert (if export failed) -->
    <?php if(isset($export_failed) && ($export_failed === true)): ?>
    <br>
    <p class="text-justify text-danger">
        <strong>Error: an error occurred while exporting data.</strong> If this keeps happening and you feel what you did should not have resulted in such an error, please
        <?php echo $this->Html->link("contact us", array("controller"=>"documentation","action"=>"contact")); ?>.
    </p>
    <?php endif; ?>
    <br>
    <!-- Navigation tabs -->
    <ul class="nav nav-tabs nav-justified" id="tabs" data-tabs="tabs">
        <li class="active"><a href="#structural-data" data-toggle="tab">Structural data</a></li>
        <li><a href="#tax-data" data-toggle="tab">Taxonomic classification</a></li>
        <li><a href="#gf-data" data-toggle="tab">Gene family data</a></li>
        <li><a href="#rf-data" data-toggle="tab">RNA family data</a></li>
        <li><a href="#sqces-data" data-toggle="tab">Sequences</a></li>
        <li><a href="#functional-data" data-toggle="tab">Functional data</a></li>
        <li><a href="#subset-data" data-toggle="tab">Subsets</a></li>
    </ul>

    <!-- Tab content -->
    <div class="tab-content">

        <div id="structural-data" class="tab-pane active"><br>
            <h4>Export structural data</h4>
                <span style="font-weight:bold;">Select columns for output data:</span><br/>
                <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                <input type="hidden" name="export_type" value="structural"/>

                <?php
                foreach ($structural_export as $k => $v) {
                    echo "<div class='form-group' style='margin-bottom:3px;'>";
                    if ($k != "transcript_id") {
                        echo "<input type='checkbox' name='" . $k . "' id='" . $k . "' class='standard_checkbox' $unfinished />";
                    } else {
                        echo "<input type='checkbox' name='" . $k . "' id='" . $k . "' checked='checked' class='standard_checkbox' />";
                    }
                    echo "&nbsp;&nbsp;<label for='".$k."'> " . $v . "</label>\n";
                    echo "</div>";
                }
                ?>
                <br/>
                <button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-download-alt"></span> Structural information</button>

<!--                <input type="submit" value="Structural information" class="btn btn-default"/><br/>-->
                </form>
                <?php
                if (isset($export_type) && $export_type == "structural" && isset($file_path)) {
                    echo "<br/><a href='" . $file_path . "'>Download file</a>\n";
                }
                ?>
        </div>

        <div id="tax-data" class="tab-pane"><br>
            <h4>Export taxonomic classification (Kaiju) data</h4>
            <p class="text-justify">
                 The taxonomic classification export file contains all transcripts with their associated taxonomic label (tax ID, lineage) and
                classification metrics (score, number of matching tax IDs, number of matching sequences).
            </p>
                <div style="float:left;margin-right: 20px;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="tax"/>
                    <?php if($exp_info['perform_tax_binning'] == 1): ?>
                        <button class="btn btn-default" type="submit"<?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> Transcripts tax. classification</button>
                    <?php else: ?>
                        <button class="btn btn-default" type="submit" disabled><span class="glyphicon glyphicon-download-alt"></span> Transcripts tax. classification</button>
                        <span class="text-muted small">No taxonomic classification performed during initial processing.</span>
                    <?php endif; ?>
                    </form>
                </div>
        </div>

        <div id="gf-data" class="tab-pane"><br>
            <h4>Export gene family data</h4>
                <ul>
                    <li>
                        <strong>Transcripts with GF</strong> contains all transcripts with their associated gene family (if existing).
                    </li>
                    <li>
                        <strong>GF with transcripts</strong> contains all the gene families, with their associated transcripts.
                    </li>
                    <li>
                        <strong>Gf reference data</strong> contains the gene content of the reference gene families.
                    </li>
                </ul>

                <br/>
                <div style="float:left;margin-right: 20px;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="gf"/>
                    <input type="hidden" name="gf_type" value="transcript"/>
                   <button class="btn btn-default" type="submit"<?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> Transcripts with GF</button>
                    </form>
                </div>

                <div style="float:left;margin-right: 20px;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="gf"/>
                    <input type="hidden" name="gf_type" value="phylo"/>
                   <button class="btn btn-default" type="submit"<?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> GF with transcripts</button>
                    </form>
                </div>

                <div style="float:left;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="gf"/>
                    <input type="hidden" name="gf_type" value="reference"/>
                   <button class="btn btn-default" type="submit"<?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> GF reference data</button>
                    </form>
                </div>

                <div style="clear:both;width:800px;">
                    <?php
                    if (isset($export_type) && $export_type == "gf" && isset($file_path)) {
                        echo "<br/><a href='" . $file_path . "'>Download file</a>\n";
                    } else {
                        echo "<span style='font-size:x-small;'>&nbsp;</span>";
                    }
                    ?>
                </div>
        </div>

        <div id="rf-data" class="tab-pane"><br>
            <h4>Export RNA family data</h4>
                <ul>
                    <li>
                        <strong>Transcripts with RF</strong> contains all transcripts with their associated RNA family (if existing).
                    </li>
                    <li>
                        <strong>RF with transcripts</strong> contains all the RNA families, with their associated transcripts.
                    </li>
                </ul>

                <br/>
                <div style="float:left;margin-right: 20px;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="rf"/>
                    <input type="hidden" name="rf_type" value="transcript"/>
                   <button class="btn btn-default" type="submit"<?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> Transcripts with RF</button>
                    </form>
                </div>

                <div style="float:left;margin-right: 20px;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="rf"/>
                    <input type="hidden" name="rf_type" value="rf"/>
                   <button class="btn btn-default" type="submit"<?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> RF with transcripts</button>
                    </form>
                </div>


                <div style="clear:both;width:800px;">
                    <?php
                    if (isset($export_type) && $export_type == "gf" && isset($file_path)) {
                        echo "<br/><a href='" . $file_path . "'>Download file</a>\n";
                    } else {
                        echo "<span style='font-size:x-small;'>&nbsp;</span>";
                    }
                    ?>
                </div>
        </div>

        <div id="sqces-data" class="tab-pane"><br>
            <h4>Export sequences</h4>

            <p class="text-justify"><strong>Note:</strong> Protein sequences are translated ORF sequences. </p>

            <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post", "class"=>"form-inline")); ?>
            <input type="hidden" name="export_type" value="sequence"/>
                <div class="form-group">
                    <label for="sequence_type"><strong>Sequence type</strong></label>&nbsp;
                    <label>
                        <input type="radio" <?php echo $unfinished; ?>  id="sequence_type_original" name="sequence_type" value="original" checked>Transcript&nbsp;
                    </label>
                    <label>
                        <input type="radio" <?php echo $unfinished; ?> id="sequence_type_orf" name="sequence_type" value="orf">ORF&nbsp;
                    </label>
                    <label>
                        <input type="radio" <?php echo $unfinished; ?> id="sequence_type_aa" name="sequence_type" value="aa">Protein&nbsp;
                    </label>
                </div>
                <div class="form-group" style="margin-left:20px;">
                    <label for="subset_label"><strong>Transcript selection</strong></label>&nbsp;
                    <select name='subset_label' id='subset_label' style='max-width:280px;' class='form-control'>
                    <option selected value="">All transcripts</option>
                    <?php
                        foreach ($available_subsets as $subset => $count) {
                            echo "<option value='" . $subset . "' >" . $subset . " (" . $count . " transcripts)</option>\n";
                        }
                    ?>
                    </select>
                </div>
                <button <?php echo $unfinished; ?> type="submit" class="btn btn-default" style="margin-left:20px;"><span class="glyphicon glyphicon-download-alt"></span> Export sequences</button>
            <?php echo $this->Form->end(); ?>

                <div style="clear:both;width:800px;">
                    <?php
                    if (isset($export_type) && $export_type == "sequence" && isset($file_path)) {
                        echo "<br/><a href='" . $file_path . "'>Download file</a>\n";
                    } else {
                        echo "<span style='font-size:x-small;'>&nbsp;</span>";
                    }
                    ?>
                </div>
        </div>

        <div id="functional-data" class="tab-pane"><br>
            <h4>Export functional data</h4>
            <div class="page-section-sm">

                <?php if(in_array("go", $exp_info['function_types'])): ?>
                <section class="page-section-sm">
                <h5>Gene Ontology data</h5>
                <ul>
                    <li>
                    <strong>Transcripts with GO</strong> contains transcripts with associated GO terms.
                    </li>
                    <li>
                    <strong>GO meta data</strong> contains GO terms with counts and associated transcripts.
                    </li>
                </ul>
                <br/>
                <div style="float:left;margin-right: 20px;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="go"/>
                    <input type="hidden" name="functional_type" value="transcript_go"/>
                    <button class="btn btn-default" type="submit" <?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> Transcripts with GO</button>
                    </form>
                </div>


                <div style="float:left;width:220px;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="go"/>
                    <input type="hidden" name="functional_type" value="meta_go"/>
                    <button class="btn btn-default" type="submit" <?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> GO meta data</button>
                    </form>
                </div>


                <div style="clear:both;width:800px;">
                    <?php
                    if (isset($export_type) && $export_type == "go" && isset($file_path)) {
                        echo "<br/><a href='" . $file_path . "'>Download file</a>\n";
                    } else {
                        echo "<span style='font-size:x-small;'>&nbsp;</span>";
                    }
                    ?>
                </div>
                </section>
                <?php endif; ?>

                <?php if(in_array("interpro", $exp_info['function_types'])): ?>
                <section class="page-section-sm">
                <h5>Protein domain data</h5>
                <ul>
                    <li>
                        <strong>Transcripts with protein domains</strong> contains transcripts with the associated protein domains.
                    </li>
                    <li>
                        <strong>Protein domain meta data</strong> contains protein domains with counts and associated transcripts.
                    </li>
                </ul>
                <br/>
                <div style="float:left;margin-right: 20px;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="interpro"/>
                    <input type="hidden" name="functional_type" value="transcript_ipr"/>
                    <button class="btn btn-default" type="submit" <?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> Transcripts with protein domain</button>
                    </form>
                </div>


                <div style="float:left;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="interpro"/>
                    <input type="hidden" name="functional_type" value="meta_ipr"/>
                    <button class="btn btn-default" type="submit" <?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> Protein domain meta data</button>
                    </form>
                </div>

                <div style="clear:both;width:800px;">
                    <?php
                    if (isset($export_type) && $export_type == "interpro" && isset($file_path)) {
                        echo "<br/><a href='" . $file_path . "'>Download file</a>\n";
                    } else {
                        echo "<span style='font-size:x-small;'>&nbsp;</span>";
                    }
                    ?>
                </div>
                </section>
                <?php endif; ?>


                <?php if(in_array("ko", $exp_info['function_types'])): ?>
                <section class="page-section-sm">
                <h5>KEGG Orthology data</h5>
                <ul>
                    <li>
                        <strong>Transcripts with KO</strong> contains transcripts with the associated protein domains.
                    </li>
                    <li>
                        <strong>KO meta data</strong> contains protein domains with counts and associated transcripts.
                    </li>
                </ul>
                <br/>
                <div style="float:left;margin-right: 20px;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="ko"/>
                    <input type="hidden" name="functional_type" value="transcript_ko"/>
                    <button class="btn btn-default" type="submit" <?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> Transcripts with KO</button>
                    </form>
                </div>


                <div style="float:left;">
                    <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                    <input type="hidden" name="export_type" value="ko"/>
                    <input type="hidden" name="functional_type" value="meta_ko"/>
                    <button class="btn btn-default" type="submit" <?php echo $unfinished; ?>><span class="glyphicon glyphicon-download-alt"></span> KO meta data</button>
                    </form>
                </div>

                <div style="clear:both;width:800px;">
                    <?php
                    if (isset($export_type) && $export_type == "interpro" && isset($file_path)) {
                        echo "<br/><a href='" . $file_path . "'>Download file</a>\n";
                    } else {
                        echo "<span style='font-size:x-small;'>&nbsp;</span>";
                    }
                    ?>
                </div>
                </section>
                <?php endif; ?>
            </div>
        </div>

        <div id="subset-data" class="tab-pane"><br>
            <h4>Export subsets</h4>
                <?php
                echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post"));
                echo "<input type='hidden' name='export_type' value='subsets' />\n";

                echo "<div style='float:left;width:290px;'>\n";
                $disabled = null;
                if (count($available_subsets) == 0) {
                    $disabled = " disabled='disabled' ";
                }
                echo "<select name='subset_label' $disabled style='max-width:280px;' class='form-control'>\n";
                foreach ($available_subsets as $subset => $count) {
                    echo "<option value='" . $subset . "' >" . $subset . " (" . $count . " transcripts)</option>\n";
                }
                echo "</select>\n";
                echo "</div>\n";

                echo "<div style='float:left;width:220px;'>\n";
                echo "<button class=\"btn btn-default\" type=\"submit\"><span class=\"glyphicon glyphicon-download-alt\"></span> Download subset</button>";
//                echo "<input type='submit' value='Download subset' class='btn btn-default' />";
                echo "</div>\n";

                echo "<div style='clear:both;width:800px;'>\n";
                if (isset($export_type) && $export_type == "subsets" && isset($file_path)) {
                    echo "<br/><a href='" . $file_path . "'>Download file</a>\n";
                } else {
                    echo "<span style='font-size:x-small;'>&nbsp;</span>";
                }
                echo "</div>\n";
                echo "</form>\n";
                ?>
        </div>
    </div>
</div>