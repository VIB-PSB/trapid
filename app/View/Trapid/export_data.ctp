<div>
    <div class="page-header">
        <h1 class="text-primary">Export data</h1>
    </div>
    <div class="subdiv">
        <?php echo $this->element("trapid_experiment"); ?>
        <?php
        $unfinished = null;
        if ($exp_info['process_state'] != "finished") {
            $unfinished = " disabled='disabled' ";
        }
        ?>

        <h3>Export structural data</h3>
        <div class="subdiv bottom">
            <span style="font-weight:bold;">Select columns for output data</span><br/><br/>
            <?php echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post")); ?>
            <input type="hidden" name="export_type" value="structural"/>
            <?php
            foreach ($structural_export as $k => $v) {
                if ($k != "transcript_id") {
                    echo "<input type='checkbox' name='" . $k . "' class='standard_checkbox' $unfinished />";
                } else {
                    echo "<input type='checkbox' name='" . $k . "' checked='checked' class='standard_checkbox' />";
                }
                echo "<span class='checkboxlabel'>" . $v . "</span><br/>\n";
            }
            ?>
            <br/>
            <input type="submit" value="Structural information" class="bigbutton"/><br/>
            </form>
            <?php
            if (isset($export_type) && $export_type == "structural" && isset($file_path)) {
                echo "<br/><a href='" . $file_path . "'>Download file</a>\n";
            }
            ?>
        </div>

        <h3>Export sequences</h3>
        <div class="subdiv">
            <span style="font-weight:bold;">Protein sequences are translated ORF sequences</span><br/><br/>
            <div style="float:left;width:220px;">
                <?php echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post")); ?>
                <input type="hidden" name="export_type" value="sequence"/>
                <input type="hidden" name="sequence_type" value="original"/>
                <input type="submit" value="Transcript sequences" class="bigbutton"/>
                </form>
            </div>

            <div style="float:left;width:220px;">
                <?php echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post")); ?>
                <input type="hidden" name="export_type" value="sequence"/>
                <input type="hidden" name="sequence_type" value="orf"/>
                <input type="submit" value="ORF sequences" class="bigbutton" <?php echo $unfinished; ?> />
                </form>
            </div>

            <div style="float:left;width:220px;">
                <?php echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post")); ?>
                <input type="hidden" name="export_type" value="sequence"/>
                <input type="hidden" name="sequence_type" value="aa"/>
                <input type="submit" value="Protein sequences" class="bigbutton" <?php echo $unfinished; ?> />
                </form>
            </div>

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

        <h3>Export gene family data</h3>
        <div class="subdiv">
            <span style='font-weight:bold;'><i>Transcripts with GF</i> contains all transcripts with their associated gene family (if existing).</span><br/>
            <span style='font-weight:bold;'><i>GF with transcripts</i> contains all the gene families, with their associated transcripts.</span><br/>
            <span style='font-weight:bold;'><i>Gf reference data</i> contains the gene content of the reference gene families. </span></br
            />

            <br/>
            <div style="float:left;width:220px;">
                <?php echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post")); ?>
                <input type="hidden" name="export_type" value="gf"/>
                <input type="hidden" name="gf_type" value="transcript"/>
                <input type="submit" value="Transcripts with GF" class="bigbutton" <?php echo $unfinished; ?>/>
                </form>
            </div>

            <div style="float:left;width:220px;">
                <?php echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post")); ?>
                <input type="hidden" name="export_type" value="gf"/>
                <input type="hidden" name="gf_type" value="phylo"/>
                <input type="submit" value="GF with transcripts" class="bigbutton" <?php echo $unfinished; ?>/>
                </form>
            </div>

            <div style="float:left;width:220px;">
                <?php echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post")); ?>
                <input type="hidden" name="export_type" value="gf"/>
                <input type="hidden" name="gf_type" value="reference"/>
                <input type="submit" value="GF reference data" class="bigbutton" <?php echo $unfinished; ?>/>
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

        <h3>Export functional data</h3>
        <div class="subdiv">
            <h4>Gene Ontology data</h4>
            <span style="font-weight:bold;"><i>Transcripts with GO</i> contains transcripts with associated GO terms.</span><br/>
            <span style="font-weight:bold;"><i>GO meta data</i> contains GO terms with counts and associated transcripts.</span><br/>
            <br/>
            <div style="float:left;width:220px;">
                <?php echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post")); ?>
                <input type="hidden" name="export_type" value="go"/>
                <input type="hidden" name="functional_type" value="transcript_go"/>
                <input type="submit" value="Transcripts with GO" class="bigbutton" <?php echo $unfinished; ?>/>
                </form>
            </div>


            <div style="float:left;width:220px;">
                <?php echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post")); ?>
                <input type="hidden" name="export_type" value="go"/>
                <input type="hidden" name="functional_type" value="meta_go"/>
                <input type="submit" value="GO meta data" class="bigbutton" <?php echo $unfinished; ?>/>
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

            <div style="clear:both;width:800px;font-size:x-small;">&nbsp;</div>

            <h4>Protein domain data</h4>
            <span style="font-weight:bold;"><i>Transcripts with protein domains</i> contains transcripts with the associated protein domains.</span><br/>
            <span style="font-weight:bold;"><i>Protein domain meta data</i> contains protein domains with counts and associated transcripts.</span><br/>
            <br/>
            <div style="float:left;width:220px;">
                <?php echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post")); ?>
                <input type="hidden" name="export_type" value="interpro"/>
                <input type="hidden" name="functional_type" value="transcript_ipr"/>
                <input type="submit" value="Transcripts with protein domain"
                       class="bigbutton" <?php echo $unfinished; ?>/>
                </form>
            </div>


            <div style="float:left;width:220px;">
                <?php echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post")); ?>
                <input type="hidden" name="export_type" value="interpro"/>
                <input type="hidden" name="functional_type" value="meta_ipr"/>
                <input type="submit" value="Protein domain meta data" class="bigbutton" <?php echo $unfinished; ?>/>
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
        </div>

        <h3>Export subsets</h3>
        <div class="subdiv">
            <?php
            echo $this->Form->create("", array("action" => "export_data/" . $exp_id, "type" => "post"));
            echo "<input type='hidden' name='export_type' value='subsets' />\n";

            echo "<div style='float:left;width:220px;'>\n";
            $disabled = null;
            if (count($available_subsets) == 0) {
                $disabled = " disabled='disabled' ";
            }
            echo "<select name='subset_label' $disabled style='width:200px;'>\n";
            foreach ($available_subsets as $subset => $count) {
                echo "<option value='" . $subset . "' >" . $subset . " (" . $count . " transcripts)</option>\n";
            }
            echo "</select>\n";
            echo "</div>\n";

            echo "<div style='float:left;width:220px;'>\n";
            echo "<input type='submit' value='Download subset' class='bigbutton' />";
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
