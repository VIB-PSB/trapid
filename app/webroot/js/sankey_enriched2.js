"use strict";

/* Global variables: */

// label to display instead of null.
var null_label = 'no label';
var no_gf_label = 'no family';
// The initial amount of nodes to show
var nodes_to_show = 10;
// checked_labels contain the names of all checked labels
var checked_labels = Object.create(null);
// Keeps track of which name belongs to which column, used for the urls
var column = {};
var names_list = [];

var log_enrichments = Object.create(null);

var second_links;
var first_links;

var distribution = [];

// flow from middle nodes to the right column {1_HOM000248: 50, 1_HOM001148: 108, 1_HOM004574: 21, 1_HOM000586: 84, 1_HOM002222: 60…}
var flow = Object.create(null);

// set of p values to fill the dropdown with.
var p_values = Object.create(null);
var minimum_size;
var options = [];

/* HTML identifiers used to select on */
var hidden_id = '#hidden';
var type_id = '#type';
var p_val_id = '#pvalue';
var dropdown_id = '#right_min';
var normalization_id = '#normalize';
var enrichment_id = '#enrichment';

var boxes = '#left_boxes';
var col_classes = ['left_col','right_col'];

/* Globals defined in sankey_enriched

 enrichedIdents : [label][p_val][identifier] = [hidden,sign]
 ['cluster1']['0.1'][GO:0000271] =  "1"
 transcriptIdent : [transcript][identifier] = 1
 [contig00001][GO:0003824] = 1
 transcriptLabelGF : [label][transcript] = gf_id
 [cluster1][contig00001] = "1_HOM005284"
 descriptions : [identifier] = {desc:'bla bla', type:'CC'}
 label_counts : [label] = count
 total_count : int
 dropdown_filter_name : string used in dropdown
 urls : [strings]
 place_holder : '###'
 GO : bool
 */

$(document).ready(function () {
    hide_enrichment_direction();
    if(!GO){hide_type();}
    process_data();
    add_checkboxes();
    fill_in_p_values();
    calculate_current_flow();
    fill_in_dropdown();

    draw_sankey();


});


// real_width is used for layout purposes
var margin = {top: 1, right: 1, bottom: 6, left: 1},
    real_width = calculate_good_width(),
    width = real_width - margin.left - margin.right,
    height = calculate_good_height() - margin.top - margin.bottom;


/* calculate_good_x because we have to determine some size before drawing the diagram,
 d3 will then fill in the whole space allocated to it, so for a small amount of transcripts we allocate less space
 Otherwise we've got a huge diagram without super big nodes, which looks ridiculous */
function calculate_good_height(){
    return Math.min(window.innerHeight - 200, Math.log2(2*Object.keys(descriptions).length)* 200);
}


function calculate_good_width(){
    // return Math.min(window.innerWidth - margin.left - margin.right - 80,Math.log2(2*Object.keys(transcriptIdent).length)* 200);
    return Math.min(window.innerWidth - margin.left - margin.right - 80 - 300, Math.log2(2*Object.keys(descriptions).length)* 200);
}


////////// Behaviour of the refine button and fields ////////////

function hide_enrichment_direction(){
    /* If not working with GO terms, we want to hide the `Type` select element */
    // Parent because otherwise the label stays visible.
    $(enrichment_id).parent().addClass("hidden");

    // We also want to hide the `Show hidden` checkbox because it is only useful with GO terms
    // $(hidden_id).parent().addClass("hidden");
}


function hide_type(){
    /* If not working with GO terms, we want to hide the `Type` select element */
    // Parent because otherwise the label stays visible.
    $(type_id).parent().addClass("hidden");

    // We also want to hide the `Show hidden` checkbox because it is only useful with GO terms
    // $(hidden_id).parent().addClass("hidden");
}


function fill_in_dropdown(){
    var dropdown_elmt  = document.getElementById(dropdown_id.split("#")[1]);  // Get element id
    // Clear the dropdown before adding new options
    // $(dropdown_id).update();
    $(dropdown_id).empty();

    // If there are no options, ask the user to select something
    if(options.length === 0){
        // $(dropdown_id).options.add(new Option('Please select labels', 0));
        dropdown_elmt.add(new Option('Please select subsets', 0));
        return;
    }
    // Fill in the dropdown
    for(var i = 0,len = options.length; i < len; i++){
        var option_string = '≥' + options[i][0] + ' [' + options[i][1] + ' ' + dropdown_filter_name[1]+ ']';
        // var option_string = '>=' + options[i][0] + ' [' + options[i][1] + ' ' + dropdown_filter_name[1]+ ']';
        dropdown_elmt.add(new Option(option_string, options[i][0]));
    }

    $(dropdown_id).val(minimum_size);
}


function fade_dropdown() {

    const dropdown_elmt  = document.getElementById(dropdown_id.split("#")[1]);  // Get element id
    dropdown_elmt.classList.remove('fading');
    // Triggering reflow is necessary to reload the animation
    void dropdown_elmt.offsetWidth;
    dropdown_elmt.classList.add('fading');
}


/* p values are used in the filtering, filled in only once*/
function fill_in_p_values(){
    var dropdown_elmt  = document.getElementById(p_val_id.split("#")[1]);  // Get element id (non-jQuery)
    for(var i = 0, len = p_values.length; i < len; i++){
        dropdown_elmt.add(new Option(p_values[i], i));
    }
}


function calculate_options(){
    options = [];
    minimum_size = undefined;
    var total = 0;

    var used_distribution = distribution;
    for(var i = used_distribution.length - 1; i > 0; i--){
        if(typeof used_distribution[i] != 'undefined' && used_distribution[i] !== 0){
            total += used_distribution[i];
            // console.log(total);
            options.push([i,total]);
            if(!minimum_size && total > nodes_to_show){
                minimum_size = options[Math.max(0,options.length - 2)][0];
            }
        }
    }
    // show options in ascending order
    options.reverse();
    // Set a decent minimum value, if choice was never set, there aren't that many choices so pick the first value.
    if(!minimum_size && options.length > 0){
        minimum_size = options[0][0];
    }
}


/* Dynamically add checkboxes based on the labels that are supplied */
function add_checkboxes(){
    names_list.sort();

    for (var i = 0, len = names_list.length; i < len; ++i) {
        // Create the checkboxes and labels for them here.
        var n = names_list[i];
        var checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = n;
        checkbox.value = n;
        checkbox.id = boxes + n;
        checkbox.onchange = function(event){checkbox_changed(event);};
        var label = document.createElement('label');

        label.htmlFor = checkbox.id;

        if(n !== null_label){
            label.appendChild(document.createTextNode(' ' + n + ' [' + label_counts[n] + ' tr' + (label_counts[n] !== 1 ? 's] ' : '] ')));
        } else {
            // To make only part of the label red & bold
            label.appendChild(document.createTextNode(''));
            // We can't just create a textNode with the text we want because this displays the span tags as text
            label.html(' <span class="bad_label">' + n + '</span> [' + label_counts[n] + ' tr' + (label_counts[n] !== 1 ? 's] ' : '] '));
        }

        // var container = $(boxes).select('.' + col_classes[i % 2])[0];
        var container = $(boxes).find('.' + col_classes[i % 2])[0];
        container.appendChild(checkbox); // Fix for IE browsers, first append, then check.
        container.appendChild(label);
        container.appendChild(document.createElement('br'));
        // Don't check the 'no label' label
        if(n === null_label){
            continue;
        }
        // check  all values in the initial view some values
        // Check the box only if there is enriched data for the subset?
        if(Object.keys(enrichedIdents).indexOf(n) > -1){
            checkbox.checked = true;
            checked_labels[n] = 1;
        }
        else {
            checkbox.checked = false;
            // checkbox.disabled = true;  // Disable them too?
        }

    }
}


function checkbox_changed(event){
    disable_everything();
    var chckbx = event.target;

    if(chckbx.checked){
        checked_labels[chckbx.name] = 1;
    } else {
        delete checked_labels[chckbx.name];
    }
    // Other groupings, other options.
    update_middle_nodes();

    enable_everything();
}


function middle_filter(){
    disable_everything();
    update_middle_nodes();
    enable_everything();
}


function update_middle_nodes(){
    calculate_current_flow();
    fill_in_dropdown();
    fade_dropdown();
}

/* Enabling and disabeling all inputs during computations out of fear of race conditions and other bad things */
function disable_everything(){
    var input_elements = document.getElementsByTagName('input');
    for(var i = 0, len = input_elements.length; i < len ; i++){
        input_elements[i].disabled = true;
    }
}


function enable_everything(){
    var input_elements = document.getElementsByTagName('input');
    for(var i = 0, len = input_elements.length; i < len ; i++){
        input_elements[i].disabled = false;
    }
}


////////// Sankey vizualization ////////////

// Data Processing
var gftranscript = Object.create(null);
function process_data(){
    names_list = Object.keys(sankeyEnrichmentData);
    // names_list = Object.keys(transcriptLabelGF);
    var p_set = Object.create(null);
    for(var label2 in enrichedIdents){
        for(var p2 in enrichedIdents[label2]){
            p_set[p2] = 1;
        }
    }

    p_values = Object.keys(p_set);
    p_values.sort(function(a, b) {
        return Number(a) - Number(b);
    }); // Sorts correctly, even with scientific notation

    for(var label in enrichedIdents){
        log_enrichments[label] = Object.create(null);
        for(var p in enrichedIdents[label]){
            for(var ident in enrichedIdents[label][p]){
                if(!(ident in log_enrichments)){
                    log_enrichments[label][ident] = enrichedIdents[label][p][ident][1];
                }
                enrichedIdents[label][p][ident][1] = enrichedIdents[label][p][ident][1] > 0 ? 1 :-1; // only keep the sign
            }
        }
    }

    gftranscript[no_gf_label] = [];
    for(var label in transcriptLabelGF){
        for(var transcript in transcriptLabelGF[label]){
            if(transcriptLabelGF[label][transcript] ===  null){
                transcriptLabelGF[label][transcript] = no_gf_label;
                gftranscript[no_gf_label].push(transcript);
            } else {
                gftranscript[transcriptLabelGF[label][transcript]] = transcript;
            }
        }
    }
}

/* The current links are the ones that aren't filtered out, they are split up in 2 parts, first and second _links*/
function determine_current_links(){
    first_links = Object.create(null);
    second_links = Object.create(null);

    var p_value = $(p_val_id + " option:selected").text();  // $(p_val_id).options[$(p_val_id).selectedIndex].text;
    var type = $(type_id + " option:selected").text();
    var show_hidden = $(hidden_id).is(":checked");
    var sign = parseInt($(enrichment_id + " option:selected").val()) === 0 ? 1 : -1;


    for(var label in checked_labels) {
        if (!(label in first_links)) {
            first_links[label] = Object.create(null);
            column[label] = 0;
        }
        for (var identifier in sankeyEnrichmentData[label][p_value]) {
            // Is this identifier hidden? We only check this if the checkbox isn't checked
            if(!show_hidden && sankeyEnrichmentData[label][p_value][identifier]['is_hidden'] === true){
                continue;
            }
            // if(enrichedIdents[label][p_value][identifier][1] !== sign){
            //     continue;
            // }
            // Is the type correct? We only check this if we're dealing with GO terms
            if(GO && type !== "All" && type !== descriptions[identifier].type){
                continue;
            }


            first_links[label][identifier] = sankeyEnrichmentData[label][p_value][identifier]['n_hits'];
            column[identifier] = 1;

            for (var gf in sankeyGfData[label][identifier]) {
                if(!(identifier in second_links)){
                    second_links[identifier] = Object.create(null);
                }
                if(!(gf in second_links[identifier])){
                    second_links[identifier][gf] = sankeyGfData[label][identifier][gf];
                    column[gf] = 2;
                } else {
                    second_links[identifier][gf]+= sankeyGfData[label][identifier][gf];
                }
            }
        }
    }
}

/*

 /!* The current links are the ones that aren't filtered out, they are split up in 2 parts, first and second _links*!/
 function determine_current_links(){
 first_links = Object.create(null);
 second_links = Object.create(null);

 var p_value = $(p_val_id + " option:selected").text();  // $(p_val_id).options[$(p_val_id).selectedIndex].text;
 var type = $(type_id + " option:selected").text();
 var show_hidden = $(hidden_id).is(":checked");
 var sign = parseInt($(enrichment_id + " option:selected").val()) === 0 ? 1 : -1;


 for(var label in checked_labels){
 if(!(label in first_links)){
 first_links[label] = Object.create(null);
 column[label] = 0;
 }
 for(var transcript in transcriptLabelGF[label]){
 for(var identifier in transcriptIdent[transcript]){
 // Is the GO term enriched for this p_value? (this check is unnecessary, the second if catches this case too)
 if(!(p_value in enrichedIdents[label]) || !(identifier in enrichedIdents[label][p_value])){
 continue;
 }
 // Is this identifier hidden? We only check this if the checkbox isn't checked
 if(!show_hidden && enrichedIdents[label][p_value][identifier][0] === true){
 continue;
 }
 if(enrichedIdents[label][p_value][identifier][1] !== sign){
 continue;
 }
 // Is the type correct? We only check this if we're dealing with GO terms
 if(GO && type !== "All" && type !== descriptions[identifier].type){
 continue;
 }

 // create or increment this link.
 if(!(identifier in first_links[label])){
 first_links[label][identifier] = 1;
 column[identifier] = 1;
 } else {
 first_links[label][identifier]++;
 }

 // create or increment this link in the second mapping.
 var gf = transcriptLabelGF[label][transcript];
 if(!(identifier in second_links)){
 second_links[identifier] = Object.create(null);
 }
 if(!(gf in second_links[identifier])){
 second_links[identifier][gf] = 1;
 column[gf] = 2;
 } else {
 second_links[identifier][gf]++;
 }
 }
 }
 }
 }
 */


function calculate_current_flow(){
    // reset data
    flow = Object.create(null);
    distribution = [];

    determine_current_links();

    // calculate flow into each gf
    for(var node in second_links){
        var map = second_links[node];
        for(var target in map){
            if(!(target in flow)){
                flow[target] = 0;
            }
            flow[target] += map[target];
        }
    }

    // Fill the distribution array
    for(var trgt in flow){
        var fl = flow[trgt];
        if(!distribution[fl]){
            distribution[fl] = 1;
        } else {
            distribution[fl]++;
        }
    }
    // new flow = new options
    calculate_options();
}


function filter_links_to_use(){
    var links = [];
    var used_middle_nodes = Object.create(null);
    var second_min_flow =  $(dropdown_id + " option:selected").val(); // $(dropdown_id).options[$(dropdown_id).selectedIndex].value;
    // First add all links in the second mapping, keeping track of the used middle nodes,
    // prevents middle nodes from floating to the right when all it's targets are filtered out
    for(var ident in second_links){
        var idengf = second_links[ident];
        for(var gf in idengf){
            var fl = flow[gf];
            if(fl >=second_min_flow ){
                links.push([ident, gf, idengf[gf]]);
                used_middle_nodes[ident] = 1;
            }
        }
    }

    for(var lbl in first_links){
        var lblIden = first_links[lbl];
        for(var iden in lblIden){
            if(iden in used_middle_nodes){
                links.push([lbl, iden, lblIden[iden]]);
            }
        }
    }

    return links;
}


// 1: Every block has width 100, divide by the sum of outgoing links
function normalize_links(links){
    // First we calculate the current divisor
    var divisors = Object.create(null);
    links.forEach(function(link){
        if(link[0] in divisors){
            divisors[link[0]] += +link[2];
        } else {
            divisors[link[0]] = +link[2];
        }
    });
    // Divide by the calculated divisor
    links.forEach(function(link){
        link[2] = link[2]*100/divisors[link[0]];
    });
}


// The format of the numbers when hovering over a link or node
var formatNumber = d3.format(",.0f"),
    format = function(d) { return formatNumber(d) + " transcripts"; };
var color = d3.scale.category20();

var svg = d3.select("#sankey").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom);

// (Re)draw the sankey diagram
function draw_sankey() {

    // Remove the old svg if it exists
    d3.select("svg").text('');

    // Remove old tooltips
    $('.d3-tip').remove();


    var svg = d3.select("svg")
        .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");


    // Tooltip based on this snippet: http://bl.ocks.org/FabricioRHS/80ef58d4390b06305c91fdc831844009
    // Position offset
    var linkTooltipOffsetX = 90;
    var linkTooltipOffsetY = 100;
    var nodeTooltipOffsetX = 30;
    var nodeTooltipOffsetY = 33;

    // Initialize tooltips
    var tipLink = d3.tip()
        .attr('class', 'd3-tip d3-tip-link');
    var tipNode = d3.tip()
        .attr('class', 'd3-tip d3-tip-node');

    svg = d3.select('svg').call(tipLink).call(tipNode);

    // TODO: return content as array and deal with formatting in this function?
    tipLink.html(function(d) {
        var tooltipContent = d3.select(this).select("hovertext").text();
        return tooltipContent;
    });

    tipNode.html(function(d) {
        var tooltipContent = d3.select(this).select("hovertext").text();
        var html = tooltipContent;
        html += "<br><span class='text-justify d3-tip-footer'>Drag to move, click to highlight, double-click to view.</span>";
        return html;
    });


    // Based on http://www.d3noob.org/2013/02/formatting-data-for-sankey-diagrams-in.html
    var graph = {"nodes" : [], "links" : []};

    var good_links = filter_links_to_use();
    if($(normalization_id).is(":checked")){
        normalize_links(good_links);
    }
    good_links.forEach(function (d) {
        graph.nodes.push({ "name": d[0] });
        graph.nodes.push({ "name": d[1] });
        graph.links.push({ "source": d[0],
            "target": d[1],
            "value": +d[2]});
    });

    // return only the distinct / unique nodes
    graph.nodes = d3.keys(d3.nest()
        .key(function (d) { return d.name; })
        .map(graph.nodes));

    // loop through each link replacing the text with its index from node
    graph.links.forEach(function (d, i) {
        graph.links[i].source = graph.nodes.indexOf(graph.links[i].source);
        graph.links[i].target = graph.nodes.indexOf(graph.links[i].target);
    });

    //now loop through each nodes to make nodes an array of objects
    // rather than an array of strings
    graph.nodes.forEach(function (d, i) {
        var col = column[d];
        graph.nodes[i] = { name: d.replace(/^\d+_/g,''),
            href: urls[col].replace(place_holder,d).replace('GO:','GO-'),
            nodeId: "node_" + i.toString() };
    });


    var sankey = d3.sankey()
        .size([width, height])
        .nodeWidth(15)
        .nodePadding(10)
        .nodes(graph.nodes)
        .links(graph.links)
        .layout(32);

    var path = sankey.link();

    var link = svg.append("g").selectAll(".link")
        .data(graph.links)
        .enter().append("path")
        .attr("class", "link")
        .attr("d", path)
        .style("stroke-width", function(d) { return Math.max(1, d.dy); })
        .sort(function(a, b) { return b.dy - a.dy; });

    link.append("hovertext")
        .text(function(d) { return create_link_hovertext(d);});


    // Add link tooltips
    link.on('mousemove', function(event) {
        tipLink.style("left", function () {
            var left = (Math.max(d3.event.pageX - linkTooltipOffsetX, 10));
            left = Math.min(left, window.innerWidth - $('.d3-tip').width() - 20);
            return left + "px";
        })
            .style("top", function() { return (d3.event.pageY - linkTooltipOffsetY) + "px" })
    })
        .on('mouseover', tipLink.show)
        .on('mouseout', tipLink.hide);


    // Work around to make something dragable also clickable
    // From http://jsfiddle.net/2EqA3/3/

    var node = svg.append("g").selectAll(".node")
        .data(graph.nodes)
        .enter().append("g")
        .attr("class", "node")
        .attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; })
        .call(d3.behavior.drag()
            .origin(function(d) { return d; })
            .on("dragstart", function(dragged) {
                d3.event.sourceEvent.stopPropagation();
                // this.parentNode.appendChild(this);
                svg.selectAll(".node").sort(function(a, b) {
                    var toFront = dragged.nodeId;
                    return (a.nodeId === toFront) - (b.nodeId === toFront);
                });
                d3.select(this).classed("dragged", true);
            })
            .on("drag", dragmove)
            .on('dragend', function(){
                d3.select(this).classed("dragged", false);
            }))
        .on("click", highlightCurrentNode)
        .on("dblclick", click)
        .on('mousemove', function(event) {
            var nodeStrokeColor = d3.select(this).select("rect").style("stroke");
            tipNode
                .style("left", function () {
                    var left = (Math.max(d3.event.pageX - $('.d3-tip-node').width() - nodeTooltipOffsetX, 10));
                    left = Math.min(left, window.innerWidth - $('.d3-tip-node').width() - 20);
                    return left + "px"; })
                .style("top", (d3.event.pageY - $('.d3-tip-node').height() - nodeTooltipOffsetY) + "px")
                .style("border", function() { return nodeStrokeColor + ' solid 1px'; })

        })
        .on('mouseover.tooltip', tipNode.show)
        .on('mouseout.tooltip', tipNode.hide)
        .on("mouseover.links", highlightConnectedLinks)
        .on("mouseout.links", resetConnectedLinks);


    function highlightConnectedLinks(d) {
        // Add `connected` class to the link if it is connected to the node
        link.classed("connected", function(l) {
            if (l.source.name == d.name || l.target.name == d.name) {
                return true;
            }
            else
                return false;
        });
    }

    // Remove `connected` class from all links
    function resetConnectedLinks(d) {
        link.classed("connected", false);
    }


    function highlightCurrentNode(d) {
        if(d3.event.defaultPrevented) {
            return;
        }
        // Highlight current node
        var node = d3.select(this);
        var isHighlighted = node.classed("highlighted");
        node.classed("highlighted", !isHighlighted);
        // Get names of currently highlighted nodes
        var highlightedNodes = [];
        svg.selectAll(".node.highlighted").each(function(n) { highlightedNodes.push(n.name) });
        // Toggle `highlighted` class to connected links as appropriate
        link.each(function (l) {
            var currentLink = d3.select(this);
            if((l.source.name === d.name || l.target.name === d.name) && (!highlightedNodes.includes(l.source.name) || !highlightedNodes.includes(l.target.name))) {
                currentLink.classed("highlighted", !isHighlighted);
            }
        });
    }


    function click(d) {
        if (d3.event.defaultPrevented)
        { return;}
        window.open(d.href,'_blank');
    }

    node.append("rect")
        .attr("height", function(d) { return d.dy; })
        .attr("width", sankey.nodeWidth())
        .style("fill", function(d) { return d.color = color(d.name); })
        .style("stroke", function(d) { return d.color = color(d.name); })
        // .style("stroke", function(d) { return d3.rgb(d.color).darker(2); })
        .append("hovertext")
        .text(function(d) { return create_hovertext(d);});
    node.append("text")
        .attr("x", -6)
        .attr("y", function(d) { return d.dy / 2; })
        .attr("dy", ".35em")
        .attr("text-anchor", "end")
        .attr("transform", null)
        .text(function(d) { return create_node_title(d.name); })
        .filter(function(d) { return d.x < width / 2; })
        .attr("x", 6 + sankey.nodeWidth())
        .attr("text-anchor", "start");

    function dragmove(d) {
        d3.select(this).attr("transform", "translate(" + d.x + "," + (d.y = Math.max(0, Math.min(height - d.dy, d3.event.y))) + ")");
        sankey.relayout();
        link.attr("d", path);
    }

    function create_hovertext(d){
        if(d.name in descriptions){
            return "<span class='d3-tip-title'>" + d.name + "</span><br>\n" + descriptions[d.name].desc;
        }
        if(d.name in label_counts){
            return "<span class='d3-tip-title'>" + d.name + "</span><br>\n" + label_counts[d.name] + " transcript" + (label_counts[d.name] !== 1 ? 's' : '');
        } else {
            var gf_prefix;
            if(d.name === no_gf_label){
                gf_prefix = '';
            } else {
                gf_prefix = exp_id + '_';
            }
            return "<span class='d3-tip-title'>" + d.name + "</span><br>\n" + flow[d.name] + " transcript" + (flow[d.name] !== 1 ? 's' : '');
        }
    }

    // The hovertext varies depending on the normalization used
    function create_link_hovertext(d){
        var arrow = "<br>→ ";
        var hover_string = "<span class='d3-tip-title'>" + d.source.name + arrow + d.target.name + "</span>";
        if($(normalization_id).is(":checked")){
            hover_string += "\n" + parseFloat(d.value).toFixed(2) + '% of transcripts shown';
        } else {
            hover_string += "<br>\n" + d.value + ' transcript' + (d.value != 1 ? 's' : '');
        }
        if(d.source.name in log_enrichments && d.target.name in log_enrichments[d.source.name]){
            hover_string +=  "<br>\nlog<sub>2</sub> enrichment: " + parseFloat(log_enrichments[d.source.name][d.target.name]).toFixed(4);
        }
        return  hover_string ;
    }


    function create_node_title(name){
        var max_length = 40;
        if(name in descriptions){
            var descrip = descriptions[name].desc;
            // max_lenght +5, so atleast 8 chars get cut.
            if(descrip.length > max_length + 5){
                descrip = descrip.substring(0,max_length - 3) + '...';
            }
            return descrip;
        } else {
            return name;
        }
    }
}
