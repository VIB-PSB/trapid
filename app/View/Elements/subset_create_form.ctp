<style>
    .selectize-control {
        display: inline-block;
        vertical-align: middle;
        margin-left: 1em;
        margin-right: 1em;
    }

    .selectize-input>input, .selectize-input {
        font-size: 14px;
    }
    /*.selectize-input { padding: 0; }*/

    .selectize-control.single .selectize-input:after {
        content: 'edit'; /*This will draw the icon*/
        font-family: "Material Icons";
        font-size: 18px;
        line-height: 2;
        display: block;
        position: absolute;
        top: -2px;
        right: 15px;
        background-color: white;
        padding-right: 2px;
        color: #999999;
    }

    .selectize-control.single .selectize-input.dropdown-active:after {
        content: ' '; /*This will draw the icon*/
        font-family: "Material Icons";
        line-height: 2;
        display: block;
        position: absolute;
        top: -5px;
        right: 15px;
        background-color: white;
        padding-right: 2px;
        color: #666666;
    }

    .selectize-option-label {
        margin-top: 4px;
        font-weight: normal;
    }

    .div-inline {
        display: inline-block;
    }

    .fadeout {
        opacity: 0;
        transition: opacity 2s linear;
        transition-delay: 4s;
    }
</style>

<?php echo $this->Form->create(false, array("url"=>array("controller" => "trapid", "action" => "create_collection_subset/", $exp_id, $collection_type), "type" => "post", "default"=>"false", "id"=>"subset-add-form", "class"=>"form-inline", "name"=>"subset-add-form")); ?>
    <div class="form-group">
        <label for="subset-add-select"><strong>Subsets: </strong></label>
        <select name="subset-add-select" id="subset-add-select" class="form-control input-sm" style="width: 180px;display: inline-block; vertical-align: middle;">
        </select>
    </div>
    <div class="form-group" style="margin-right: 5px">
        <input type="submit" class="btn btn-primary btn-sm" id="subset-add-btn" disabled value="Add transcripts"/>
    </div>
<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=> $tooltip_text, "tooltip_placement"=>"right", "override_span_class"=>"glyphicon glyphicon-question-sign")); ?>
    <div id="loading" class="hidden" style="margin-left:14px;">
        <div class="ajax-spinner ajax-spinner-sm" style="vertical-align: middle;"></div>
        <span class="text-muted">loading...</span>
    </div>
    <div id="subset-add-result" class="div-inline" style="margin-left:14px;"></div>
<?php echo $this->Form->end(); ?>

<script type="text/javascript">
    var selectizeOptions = [
        <?php
        $subset_ids = array_keys($all_subsets);
        $last_subset = end($subset_ids);
        foreach ($all_subsets as $subset=>$n_trs) {
            echo "{'id': '" . $subset . "', 'label': '" . $subset . "', 'trsCount': " . $n_trs . "}";
            if($subset != $last_subset) {
                echo",";
            }
            echo "\n";
        }
        ?>
    ];
    $("#subset-add-select").selectize({
        'options': selectizeOptions,
        'placeholder': 'Select or create new... ',
        'valueField': 'id',
        'labelField': 'label',
        'create': true,
        'createFilter': function(input) { return input.length <= 50; },
        'render': {
            'option_create': function (data, escape) {
                return '<div class="create">Create subset <code>' + escape(data.input) + '</code>&hellip;</div>';
            },
            'option': function(data, escape) {
                var optLabel = (escape(data.label.length) > 15) ? escape(data.label).substring(0, 14) + "..." : escape(data.label);
                var optTitle = escape(data.label) + " (" + data.trsCount + " transcripts)";
                // var optCount = '<span class="badge badge-light">' + escape(data.geneCount) + "</span>";
                var optCount = '<span class="label label-default pull-right selectize-option-label">' + escape(data.trsCount) + "</span>";
                return '<div class="option" title="' + optTitle + '">' +
                    '<span class="title">' + optLabel + " " + optCount + '</span>' +
                    '</div>';
            },
            'item': function(data, escape) {
                var itemTitle = escape(data.label) + " [" + data.id + "]";
                return '<div class="item" title="' + itemTitle + '">' + escape(data.label) + '</div>';
            }
        },
        searchField: 'label'
    });


    var create_subset_btn = "#subset-add-btn";
    var create_subset_form = "#subset-add-form";
    var display_div_id = "#subset-add-result";
    var loading_div_id = "#loading";
    var subset_name_input = "#subset-add-select";
    var selection_parameters = <?php echo json_encode($selection_parameters); ?>;

    // Check if a subset name is defined.
    // If yes, the submission button is enabled.
    function update_form_check(subset_text_input, form_sub_btn) {
        if($(subset_text_input).val() == '') {
            $(form_sub_btn).attr("disabled", true);
        }
        else {
            $(form_sub_btn).attr("disabled", false);
        }
    }

    // Bind the update form check function to change event for the input text.
    $(subset_name_input).on('change', function(){
        update_form_check(subset_name_input, create_subset_btn);
    });

    // Quickfix, disable submission button on lead (if it was enabled before and a user reloads the page, it should be disabled).
    // Needed or not?
    $(function() {
        update_form_check(subset_name_input, create_subset_btn);
    });


    // Subset creation form submission
    $(create_subset_form).submit(function (e) {
        console.log("Subset creation form was submitted! ");
//                    $(loading_div_id).css("display", "block");
        $(loading_div_id).toggleClass('hidden div-inline');
        $(create_subset_btn).attr("disabled", true);
        $(display_div_id).empty();
        $(display_div_id).removeClass('fadeout');
        // Unclean?
        var submission_data = $(this).serialize() + "&selection-parameters=" +  JSON.stringify(selection_parameters);
        console.log($(this).serialize());
//                    console.log(JSON.stringify(tax_list));
        console.log(submission_data);
        e.preventDefault();
        $.ajax({
            url: "<?php echo $this->Html->url(array("controller" => "trapid", "action" => "create_collection_subset", $exp_id, $collection_type), array("escape" => false)); ?>",
            type: 'POST',
            data: submission_data,
            dataType: 'html',
            success: function (data) {
                $(loading_div_id).toggleClass('hidden div-inline');
                $(create_subset_btn).attr("disabled", false);
                $(display_div_id).hide().html(data).fadeIn();
                $(display_div_id).addClass('fadeout');
            },
            error: function () {
                console.log('Unable to submit subset creation. If this problems persists, contact us.');
            }
        });
    });
</script>
