<?php

/* Quick rewrite of TRAPID's original search function (to put in experiment header's bar + bootstrap style + rewritten
JS with jQuery). Not very practical for multiple values though... Need to think of a better way to perform searches. */

echo $form->create(null, array("controller" => "trapid", "action" => "search/" . $exp_id, "type" => "post", "class" => "navbar-form navbar-left", "role" => "search"));
$search_types = array(
    "transcript" => "Transcript identifier",
    "gene" => "Gene identifier", "GO" => "GO description",
    "interpro" => "Protein domain description",
    "gf" => "Gene family",
    "meta_annotation" => "Meta annotation");
echo "<span id='search_content'>\n";
if (!isset($mvc) || !$mvc) {
    echo "<input type=\"text\" class=\"form-control\" placeholder=\"Search this experiment... \" maxlength='50' name='search_value'>";
//	if(isset($search_value)){echo " value='".$search_value."' ";}
//	echo "/>\n";
} else {
    $sv = "";
    if (isset($search_value)) {
        $sv = $search_value;
    }
    echo "<textarea name='search_value' rows='1' cols='30'>" . $sv . "</textarea>";
}
echo "</span>\n";

echo "<select class=\"form-control\" name=\"search_type\" id=\"search_type\">";
foreach ($search_types as $k => $v) {
    echo "<option value='" . $k . "' ";
    if (isset($search_type) && $search_type == $k) {
        echo " selected='selected' ";
    }
    echo ">" . $v . "</option>";
}

echo "</select>\n";
if ((isset($search_type) && ($search_type == "transcript" || $search_type == "gene")) || !isset($search_type)) {
    $checked = null;
    if (isset($mvc) && $mvc) {
        $checked = " checked='checked' ";
    }
    echo "<input type='checkbox' name='multiple_values_check' id='multiple_values_check' style='margin-right:5px;' " . $checked . "/>";
    echo "<span style='margin-right:20px; font-size: 80%;' id='mvs_txt'>Multiple values</span>";
}
echo "<button type='submit' class='btn btn-default btn-sm'>";
echo "<span class=\"glyphicon glyphicon-search\"></span> Search\n";
echo "</button>\n";
echo "</form>\n";
?>

<script type='text/javascript'> // TODO: improve search functionality!
    //<![CDATA[
    $("#search_type").change(function () {
        console.log("Search type changed. "); // Debug
        var selectedType = $("#search_type").val();
        if (selectedType == "meta_annotation") {
            console.log("LOL");
//            $("#search_content").innerHTML = "<select name='search_value' style='width:200px;margin-right:20px;'><option value='No Information'>No Information</option><option value='Partial'>Partial</option><option value='Full Length'>Full Length</option><option value='Quasi Full Length'>Quasi Full Length</option></select>";
        }
        else {
//		    $("search_content").innerHTML = "<input type='text' name='search_value' style='width:200px;margin-right:20px;' maxlength='50' />";
            $("#multiple_values_check").prop("checked", false);
        }
        if (!(selectedType == "transcript" || selectedType == "gene")) {
            $("#multiple_values_check").addClass('hidden');
            $("#mvs_txt").addClass('hidden');
        }
        else {
            $("#multiple_values_check").removeClass('hidden');
            $("#mvs_txt").removeClass('hidden');
        }
    });
    // Change on multiple values checkbox
    $("#multiple_values_check").change(function () {
        var mvc = $("#multiple_values_check");
        var selectedType = $("#search_type").val();
        if (selectedType == "transcript" || selectedType == "gene") {
            if (mvc.is(":checked")) {
                console.log("Multiple values checked!");
//				$("search_content").innerHTML = "<textarea name='search_value' rows='1' cols='30'></textarea>";
            }
            else {
                console.log("Multiple values not checked!");
//				$("search_content").innerHTML = "<input type='text' name='search_value' style='width:200px;margin-right:20px;' maxlength='50' />";
            }
        }
    });

    //]]>
</script>
