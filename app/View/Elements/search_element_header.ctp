<?php

/* A rewrite of TRAPID's original search function. This element should be included in the experiment header bar.
Difference with the previous search element: no more support for multiple search values, new layout/style, autocomplete,
and support for more data types.
*/

$search_types = array(
    "transcript" => "Transcript identifier",
    "gf" => "Gene family",
    "rf" => "RNA family",
    "go" => "GO term",
    "interpro" => "InterPro description",
    "ko" => "KO term",
    "meta_annotation" => "Meta annotation",
    "gene" => "Ref. gene identifier");

    $sv = "";
    if (isset($search_value)) {
        $sv = $search_value;
    }

// Functional annotation types that are displayed in the table: by default, all types are shown.
// If `$exp_info` is set, use the list of functional annotation types defined there and filter out types not present from
// search types.
$function_types = ['go', 'interpro', 'ko'];
if(isset($exp_info)){
    $exp_function_types = $exp_info['function_types'];
    foreach ($function_types as $function_type) {
        if(!in_array($function_type, $exp_function_types)) {
            unset($search_types[$function_type]);
        }
    }
}

?>

<div id="search-wrap">
<?php echo $this->Form->create(false, array("url"=>array("controller" => "trapid", "action" => "search", $exp_id), "type" => "post", "class" => "", "role" => "search")); ?>
    <div id="search">
        <select name="search_type" id="search-type">
            <?php
            foreach ($search_types as $k => $v) {
                echo "<option value='" . $k . "' ";
                if (isset($search_type) && $search_type == $k) {
                    echo " selected='selected' ";
                }
                echo ">" . $v . "</option>";
            }
            ?>
        </select>
        <input type="text" id="search-term" placeholder="Search this experiment..." maxlength='700' name='search_value' autocomplete="off" value="<?php echo $sv;?>">
        <span id="search-clear" class="glyphicon glyphicon-remove hidden"></span>
        <div id="search-suggestions" class="autocomplete-items"></div>
        <button type="submit" id="search-button">
            <span class="glyphicon glyphicon-search"></span>
        </button>
    </div>
<?php echo $this->Form->end(); ?>
</div>

<script type="text/javascript">
    // Search suggestions URL for ajax calls
    var ajaxUrl = "<?php echo $this->Html->url(array("controller" => "trapid", "action" => "suggest_search", $exp_id)); ?>";
    // Lookup timeout
    var lookupDelay = 330;  // Value in ms
    var lookupTimeout = null;
    // Minimum number of characters of search query for looking up search suggestions
    var min_query_length = 3;
    // Dictionary to store search suggestions
    var lookupCache = {};
    <?php
    foreach(array_keys($search_types) as $k) {
        echo "lookupCache['". $k . "'] = {};";
    }
    ?>
    // HTML for GO category badge (displayed in search suggestions when searching for GO terms)
    var go_badges = {
        "BP": "<?php echo addslashes($this->element("go_category_badge", array("go_category"=>"BP", "small_badge"=>true, "no_color"=>false))); ?>",
        "MF": "<?php echo addslashes($this->element("go_category_badge", array("go_category"=>"MF", "small_badge"=>true, "no_color"=>false))); ?>",
        "CC": "<?php echo addslashes($this->element("go_category_badge", array("go_category"=>"CC", "small_badge"=>true, "no_color"=>false))); ?>"
    };

    // Get search box elements
    var search_elmt = document.getElementById("search");  // Global search element (wrapper around all components)
    var search_term_elmt = document.getElementById("search-term");  // Search term
    var search_type_elmt = document.getElementById("search-type");  // Search type
    var search_clear_elmt = document.getElementById("search-clear");  // Button to clear search term
    var search_suggestions_elmt = document.getElementById("search-suggestions");  // Container for search suggestions


     // Function to perform search suggestion lookup
     var serverLookup = function () {
         var query = search_term_elmt.value.trim().replace(/:/g, "__");
         var query_length = search_term_elmt.value.trim().length;
         var search_type = search_type_elmt.value;
         if(query.length >= min_query_length) {
             // console.log("Looking for " + query + " for data type " + search_type);  // Debug
             if (query in lookupCache[search_type]) {
                 populateOptions(lookupCache[search_type][query], search_type);
             } else {
                 // console.log("Data retrieval...");
                 $.get(ajaxUrl + "/" + search_type + "/" + query, function (data) {
                     // var results = JSON.parse(data);
                     // lookupCache[search_type][query] = results;
                     lookupCache[search_type][query] = data;
                     // Set results (only if current input value is long enough)
                     // Make an extra check on current input value?
                     // if(query_length >= min_query_length) {
                         // populateOptions(results, search_type);
                     populateOptions(data, search_type);
                     // }
                 });
             }
         }
         else {
             emptySearchSuggestions();
         }
     };

     // Function to actually populate the search suggestion results from the lookup.
     var populateOptions = function (options, search_type) {
          console.log(options);
         var toKeep = [];
         options.forEach(function (item, index) {
             // Depending on search type, format search suggestion items differently
             if(search_type === "go") {
                 toKeep.push({
                     dv: item['desc'],
                     label: "<strong>" + item['name'] + "</strong> " + go_badges[item['info']] + " - " + item['desc']
                 });
             }
             else if(search_type === "interpro"){
                 toKeep.push({
                     dv: item['desc'],
                     label: "<strong>" + item['name'] + "</strong> (" + item['info'] +") - " + item['desc']
                 });
             }
             else if(search_type === "ko"){
                 toKeep.push({
                     dv: item['desc'],
                     label: "<strong>" + item['name'] + "</strong> - " + item['desc']
                 });
             }
             else {
                 toKeep.push({dv: item, label: item});
             }
         });
         // console.log(toKeep);  // Debug
         // Create suggestion rows and set results
         var html_str = toKeep.map(x=>"<div data-value='" + x['dv'] + "' class='row-fluid row-suggestion'>" + x['label'] + "</div>").join('');
         search_suggestions_elmt.innerHTML = html_str;

     };


     // Function to remove all search suggestions
     var emptySearchSuggestions = function() {
         search_suggestions_elmt.innerHTML = "";
     };

    // Function to toggle the search clear button visibility
    var toggleSearchClear = function() {
        if (search_term_elmt.value.length > 0) {
            search_clear_elmt.classList.remove("hidden");
        }
        else {
            search_clear_elmt.classList.add("hidden");
        }
    };


     // Trigger the lookup when the user pauses after typing a search query
    search_term_elmt.addEventListener('input', function (event) {
        clearTimeout(lookupTimeout);
        lookupTimeout = setTimeout(serverLookup, lookupDelay);
        // Search clear button should only be shown when there is content
        toggleSearchClear();
    });

    // Trigger the lookup when the user pauses after changing the search type type
    search_type_elmt.addEventListener('input', function (event) {
        clearTimeout(lookupTimeout);
        lookupTimeout = setTimeout(serverLookup, lookupDelay);
    });


    // When user clicks on a search suggestion, change the search term to the `data-value` attribute of the suggestion
    // row, and empty suggestions.
    $("#search-suggestions").on('click', '.row-suggestion', function(e){
        var search_str = e.target.getAttribute('data-value');
        search_term_elmt.value = search_str;
        emptySearchSuggestions();
    });

    // When clicking on the search clear button, empty suggestions and clear search
    search_clear_elmt.addEventListener("click", function(event) {
        emptySearchSuggestions();
        search_term_elmt.value = '';
        toggleSearchClear();
    });


    // If the user clicks outside of the search or suggestion div and some suggestions were visible, empty suggestions!
    // There are probably better ways to obtain this behavior but this seems to work welll
    document.addEventListener('click', function(event) {
        var isClickInside = search_suggestions_elmt.contains(event.target) || search_elmt.contains(event.target);
        if (!isClickInside && search_suggestions_elmt.innerHTML !== "") {
            emptySearchSuggestions();
        }
    });


    // Search/suggestions keyboard interaction
    // Up/down: select an item among visible search suggestions
    // Enter: if any search suggestion was selected, use it as search term and empty suggestions
    // Escape: empty suggestions
    search_term_elmt.addEventListener("keydown",   function(event) {
        var n_suggestions = search_suggestions_elmt.childNodes.length;
        if(n_suggestions > 0) {
            var $search_suggestions = $('#search-suggestions div');
            var active_idx = $('#search-suggestions div.active').index();
            // console.log(active_idx);
            // console.log(n_suggestions);
            if (event.keyCode === 40) {  // Down
                var next_active_idx = Math.min(active_idx+1, n_suggestions-1);
                if(active_idx === -1 || active_idx === n_suggestions-1) {
                    next_active_idx = 0;
                }
                $search_suggestions.eq(active_idx).removeClass('active');
                $search_suggestions.eq(next_active_idx).addClass('active');
            } else if (event.keyCode == 38) { // Up
                var next_active_idx = Math.min(active_idx-1, n_suggestions-1);
                if(active_idx === -1) {
                    next_active_idx = n_suggestions - 1;
                }
                $search_suggestions.eq(active_idx).removeClass('active');
                $search_suggestions.eq(next_active_idx).addClass('active');
            } else if (event.keyCode === 13) { // Enter
                if(active_idx !== -1) {
                    event.preventDefault();
                    var search_str = $('#search-suggestions div').eq(active_idx)[0].getAttribute('data-value');
                    search_term_elmt.value = search_str;
                    emptySearchSuggestions();
                }
            }
            else if (event.keyCode === 27) { // Escape
                emptySearchSuggestions();
            }
        }
    });

    // Check length of search value on page load and toggle  search clear button visibility appropriately
    document.addEventListener("DOMContentLoaded", function() {
        toggleSearchClear();
    });


</script>