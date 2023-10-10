<?php
$unfinished = null;
$unfinished_cls = null;
if ($exp_info['process_state'] != "finished") {
    $unfinished = " disabled='disabled' ";
    $unfinished_cls = " disabled";
}
?>

<div class="page-header">
    <h1 class="text-primary">Export data</h1>
</div>

<section class="page-section-sm">
    <p class="text-justify">
        Detailed descriptions and minimal examples of export files can be found in the
        <?php echo $this->Html->link("documentation", array("controller" => "documentation", "action" => "general", "#" => "data-export")); ?>.
    </p>
    <p class="text-justify">
        <strong>Note:</strong> exported files are generated on-the-fly and can take a while (up to ~1 minute) to be created.
    </p>
    <?php if (isset($export_failed) && ($export_failed === true)) : ?>
        <br>
        <p class="text-justify text-danger">
            <strong>Error: an error occurred while exporting data.</strong> If this keeps happening and you feel what you did should not have resulted in such an error, please
            <?php echo $this->Html->link("contact us", array("controller" => "documentation", "action" => "contact")); ?>.
        </p>
    <?php endif; ?>
</section>

<ul class="nav nav-tabs nav-justified export-tabs" id="tabs" data-tabs="tabs">
    <li class="active"><a href="#structural-data" data-toggle="tab">Structural data</a></li>
    <li><a href="#tax-data" data-toggle="tab">Taxonomic classification</a></li>
    <li><a href="#gf-data" data-toggle="tab">Gene family data</a></li>
    <li><a href="#rf-data" data-toggle="tab">RNA family data</a></li>
    <li><a href="#sqces-data" data-toggle="tab">Sequences</a></li>
    <li><a href="#functional-data" data-toggle="tab">Functional data</a></li>
    <li><a href="#subset-data" data-toggle="tab">Subsets</a></li>
</ul>

<div class="tab-content page-section-sm">
    <div id="structural-data" class="tab-pane active"><br>
        <h4>Export structural data</h4>
        <p class='text-justify'>
            <strong>Select columns for output data:</strong>
        </p>
        <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
        <input type="hidden" name="export_type" value="structural" />
        <?php
        foreach ($structural_export as $k => $v) {
            $checkbox_attrs = $k == "transcript_id" ? "checked='checked'" : $unfinished;
            $container_cls = $k == "transcript_id" ? null : $unfinished_cls;
            echo "<div class='checkbox" . $container_cls . "'>";
            echo "<label for='" . $k . "'>\n";
            echo "<input type='checkbox' name='" . $k . "' id='" . $k . "' " . $checkbox_attrs . " />";
            echo $v;
            echo "</label>\n";
            echo "</div>";
        }
        ?>
        <button class="btn btn-default export-btn perform-export" type="submit">
            <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
            <span class="glyphicon glyphicon-download-alt"></span>&nbsp;
            Structural information
        </button>
        <?php echo $this->Form->end(); ?>
    </div>

    <div id="tax-data" class="tab-pane"><br>
        <h4>Export taxonomic classification (Kaiju) data</h4>
        <p class="text-justify">
            The taxonomic classification export file contains all transcripts with their associated taxonomic label (tax ID, lineage) and
            classification metrics (score, number of matching tax IDs, number of matching sequences).
        </p>
        <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post", "class" => "export-btn-group")); ?>
        <input type="hidden" name="export_type" value="tax" />
        <?php if ($exp_info['perform_tax_binning'] == 1) : ?>
            <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                <span class="glyphicon glyphicon-download-alt"></span>
                &nbsp;Transcripts tax. classification
            </button>
        <?php else : ?>
            <button class="btn btn-default perform-export" type="submit" disabled>
                <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                <span class="glyphicon glyphicon-download-alt"></span>
                &nbsp;Transcripts tax. classification
            </button>
            <span class="text-muted small">No taxonomic classification performed during initial processing.</span>
        <?php endif; ?>
        <?php echo $this->Form->end(); ?>
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
        <div class="export-btn-group">
            <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
            <input type="hidden" name="export_type" value="gf" />
            <input type="hidden" name="gf_type" value="transcript" />
            <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                <span class="glyphicon glyphicon-download-alt"></span> 
                Transcripts with GF
            </button>
            <?php echo $this->Form->end(); ?>

            <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
            <input type="hidden" name="export_type" value="gf" />
            <input type="hidden" name="gf_type" value="phylo" />
            <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                <span class="glyphicon glyphicon-download-alt"></span> 
                GF with transcripts
            </button>
            <?php echo $this->Form->end(); ?>

            <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
            <input type="hidden" name="export_type" value="gf" />
            <input type="hidden" name="gf_type" value="reference" />
            <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                <span class="glyphicon glyphicon-download-alt"></span> 
                GF reference data
            </button>
            <?php echo $this->Form->end(); ?>
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

        <div class="export-btn-group">
            <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
            <input type="hidden" name="export_type" value="rf" />
            <input type="hidden" name="rf_type" value="transcript" />
            <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                <span class="glyphicon glyphicon-download-alt"></span> 
                Transcripts with RF
            </button>
            <?php echo $this->Form->end(); ?>

            <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
            <input type="hidden" name="export_type" value="rf" />
            <input type="hidden" name="rf_type" value="rf" />
            <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                <span class="glyphicon glyphicon-download-alt"></span> RF with transcripts
            </button>
            <?php echo $this->Form->end(); ?>
        </div>
    </div>

    <div id="sqces-data" class="tab-pane"><br>
        <h4>Export sequences</h4>

        <p class="text-justify"><strong>Note:</strong> Protein sequences are translated ORF sequences.</p>

        <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post", "class" => "form-inline export-sqces")); ?>
        <input type="hidden" name="export_type" value="sequence" />
        <div class="form-group">
            <label for="sequence_type"><strong>Sequence type</strong></label>&nbsp;
            <label>
                <input type="radio" <?php echo $unfinished; ?> id="sequence_type_original" name="sequence_type" value="original" checked>Transcript&nbsp;
            </label>
            <label>
                <input type="radio" <?php echo $unfinished; ?> id="sequence_type_orf" name="sequence_type" value="orf">ORF&nbsp;
            </label>
            <label>
                <input type="radio" <?php echo $unfinished; ?> id="sequence_type_aa" name="sequence_type" value="aa">Protein&nbsp;
            </label>
        </div>
        <div class="form-group">
            <label for="subset_label"><strong>Transcript selection</strong></label>&nbsp;
            <select name='subset_label' id='subset_label' class='form-control'>
                <option selected value="">All transcripts</option>
                <?php
                foreach ($available_subsets as $subset => $count) {
                    echo "<option value='" . $subset . "' >" . $subset . " (" . $count . " transcripts)</option>\n";
                }
                ?>
            </select>
        </div>
        <button <?php echo $unfinished; ?> type="submit" class="btn btn-default perform-export">
            <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
            <span class="glyphicon glyphicon-download-alt"></span> 
            Export sequences
        </button>
        <?php echo $this->Form->end(); ?>
    </div>

    <div id="functional-data" class="tab-pane"><br>
        <h4>Export functional data</h4>
        <div class="page-section-sm">

            <?php if (in_array("go", $exp_info['function_types'])) : ?>
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
                    <div class="export-btn-group">
                        <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                        <input type="hidden" name="export_type" value="go" />
                        <input type="hidden" name="functional_type" value="transcript_go" />
                        <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                            <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                            <span class="glyphicon glyphicon-download-alt"></span> Transcripts with GO
                        </button>
                        <?php echo $this->Form->end(); ?>

                        <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                        <input type="hidden" name="export_type" value="go" />
                        <input type="hidden" name="functional_type" value="meta_go" />
                        <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                            <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                            <span class="glyphicon glyphicon-download-alt"></span> GO meta data
                        </button>
                        <?php echo $this->Form->end(); ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (in_array("interpro", $exp_info['function_types'])) : ?>
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
                    <div class="export-btn-group">
                        <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                        <input type="hidden" name="export_type" value="interpro" />
                        <input type="hidden" name="functional_type" value="transcript_ipr" />
                        <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                            <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                            <span class="glyphicon glyphicon-download-alt"></span> Transcripts with protein domain
                        </button>
                        <?php echo $this->Form->end(); ?>
                        <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                        <input type="hidden" name="export_type" value="interpro" />
                        <input type="hidden" name="functional_type" value="meta_ipr" />
                        <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                            <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                            <span class="glyphicon glyphicon-download-alt"></span> Protein domain meta data
                        </button>
                        <?php echo $this->Form->end(); ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (in_array("ko", $exp_info['function_types'])) : ?>
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
                    <div class="export-btn-group">
                        <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                        <input type="hidden" name="export_type" value="ko" />
                        <input type="hidden" name="functional_type" value="transcript_ko" />
                        <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                            <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                            <span class="glyphicon glyphicon-download-alt"></span> Transcripts with KO
                        </button>
                        <?php echo $this->Form->end(); ?>
                        <?php echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post")); ?>
                        <input type="hidden" name="export_type" value="ko" />
                        <input type="hidden" name="functional_type" value="meta_ko" />
                        <button class="btn btn-default perform-export" type="submit" <?php echo $unfinished; ?>>
                            <?php echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']); ?>
                            <span class="glyphicon glyphicon-download-alt"></span> KO meta data
                        </button>
                        <?php echo $this->Form->end(); ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <div id="subset-data" class="tab-pane"><br>
        <h4>Export subsets</h4>
        <?php
        echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "export_data", $exp_id), "type" => "post", "class" => "export-subsets"));
        echo "<input type='hidden' name='export_type' value='subsets' />\n";

        $disabled = null;
        if (count($available_subsets) == 0) {
            $disabled = " disabled='disabled' ";
        }
        echo "<select name='subset_label' $disabled class='form-control'>\n";
        foreach ($available_subsets as $subset => $count) {
            $trs_word = $count == 1 ? 'transcript' : 'transcripts';
            echo "<option value='" . $subset . "' >" . $subset . " (" . $count . " " . $trs_word . ")</option>\n";
        }
        echo "</select>\n";
        echo "<button class=\"btn btn-default perform-export\" type=\"submit\">";
        echo $this->Html->image('small-ajax-loader.gif', ['class' => 'loading hidden']);
        echo "<span class=\"glyphicon glyphicon-download-alt\"></span> Download subset</button>";
        echo $this->Form->end();
        ?>
    </div>
</div>
<script type="text/javascript">
    const performExportBtns = document.querySelectorAll('.perform-export');
    const baseDownloadUrl = "<?php echo TMP_WEB . 'experiment_data/' . $exp_id; ?>";
    const maxDurationMs = <?php echo $max_duration_ms; ?>;
    const checkIntervalMs = 4000;

    performExportBtns.forEach((exportBtn) => {
        exportBtn.addEventListener('click', function(event) {
            // Initiate export and delegate to `handleExport()`.
            try {
                event.preventDefault();
                setLoadingState(true);
                $.ajax({
                    url: "<?php echo $this->Html->url(['controller' => 'trapid', 'action' => 'export_data', $exp_id], ['escape' => false]); ?>",
                    type: 'POST',
                    data: $(this).parent().serialize(),
                    dataType: 'json',
                    success: function(data) {
                        const exportData = data;
                        exportData.ellapsedTimeMs = 0;
                        const exportTimeout = setTimeout(handleExport, checkIntervalMs, exportData);
                    },
                    error: function() {
                        setLoadingState(false);
                        alert("Unable to start the export. If this issue persists, please contact us.");
                    }
                });
            } catch (error) {
                setLoadingState(false);
                alert("An error occurred during the export. If this issue persists, please contact us.");
                console.error(error);
          }
        }, false);
    });

    function handleExport(exportData, intervalMs = checkIntervalMs) {
        const baseUrl = "<?php echo $this->Html->url(['controller' => 'trapid', 'action' => 'handle_export_data'], ['escape' => false]); ?>";
        const params = <?php echo json_encode([$exp_id]); ?>;
        params.push(exportData.jobId, exportData.timestamp);
        if (exportData.ellapsedTimeMs + intervalMs > maxDurationMs) {
            params.push('1');
        }
        const checkJobUrl = [baseUrl, ...params].join('/');
        $.ajax({
            url: checkJobUrl,
            dataType: 'json',
            success: function(data) {
                exportData.status = data.status;
                exportData.ellapsedTimeMs += intervalMs;  // ignores request time.
                // Note: we don't check 'error' status as in this case the requests retuns status code 500, raising
                // the error below.
                if (exportData.status === 'ready') {
                    const downloadUrl = [baseDownloadUrl, exportData.zipName].join('/');
                    downloadExportFile(downloadUrl);
                    setLoadingState(false);
                } else {
                    const checkTimeout = setTimeout(handleExport, intervalMs, exportData);
                }
            },
            error: function() {
                setLoadingState(false);
                alert("An error occurred during the export. If this issue persists, please contact us.");
            },
        });
    }


    function setLoadingState(isLoading) {
        // Disable tabs during export
        document.querySelectorAll('.export-tabs li:not(.active)').forEach((tab) => {
            tab.classList.toggle('disabled', isLoading);
        })
        // Set export buttons 'loading' state (disabled + spinner + loading class)
        document.querySelectorAll('.perform-export').forEach((btn) => {
            if (!btn.disabled || btn.classList.contains('loading')) {
                btn.disabled = isLoading;
                btn.classList.toggle('loading', isLoading);
                btn.querySelector('img').classList.toggle('hidden', !isLoading);
                btn.querySelector('.glyphicon').classList.toggle('hidden', isLoading);
            }
        });
    }

    function downloadExportFile(fileUrl) {
        const anchor = document.createElement("a");
        anchor.href = fileUrl;
        document.body.appendChild(anchor);
        anchor.click();
        window.URL.revokeObjectURL(fileUrl);
        anchor.remove();
    }
</script>
