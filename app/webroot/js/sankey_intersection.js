"use strict";

/* Global variables corresponding to HTML elements in the view */
var type_id = '#type';
var dropdown_id = '#middle_min';
var boxes_ids = ['#left_boxes', '#right_boxes'];
var col_classes = ['.left_col', '.right_col'];
var normalize_id = "#normalization";

// This defines if we're comparing 2 groups or just viewing a single side of the diagram
var single_mode = false;
var null_label = 'no_label';
var null_label_txt = 'No label';


/* Run everything on page loading */
$(document).ready(function () {
    if(!GO){hide_type();}
    process_data();
    add_checkboxes();
    calculate_current_flow();
    fill_in_dropdown();
    draw_sankey();

});


/* Legacy prototype JS code */
/*
document.observe('dom:loaded', function(){
  if(!GO){hide_type();}
  process_data();
  add_checkboxes();
  calculate_current_flow();
  fill_in_dropdown();
  draw_sankey();
});
*/


////////// Behaviour of the refine button and fields ////////////
function hide_type(){
    // Parent because otherwise the label stays visible.
    $(type_id).parent().css("display", 'none');
}


function middle_filter(){
    disable_everything();
    calculate_current_flow();
    fill_in_dropdown();
    fade_dropdown();
    enable_everything();
}


function fill_in_dropdown(){
    var choice;
    var total = 0;
    var options = [];
    var used_distribution = single_mode? single_distribution : distribution;
    var dropdown_elmt  = document.getElementById(dropdown_id.substring(1));  // Get element id without leading `#`
    for(var i = used_distribution.length - 1; i > 0; i--){
        if(typeof used_distribution[i] != 'undefined' && used_distribution[i] !== 0){
            total += used_distribution[i];
            options.push([i,total]);
            if(!choice && total > 20){
                choice = options[Math.max(0,options.length - 2)][0];
            }

        }
    }
    // Clear the dropdown before adding new options
    $(dropdown_id).empty();

    // If there are no options, ask the user to select something
    if(options.length === 0){
        dropdown_elmt.add(new Option("Please select labels", 0));
        return;
    }
    // show options in ascending order
    options.reverse();

    for(var i = 0,len = options.length; i < len; i++){
        // `dropdown_filter_name` is defined in the view itself.
        var option_string = ">=" + options[i][0] + " [" + options[i][1] + " " + dropdown_filter_name + "]";
        dropdown_elmt.add(new Option(option_string, options[i][0]));
    }
    // Set a decent minimum value, if choice was never set, there aren't that many choices so pick the first value.
    if(!choice && options.length > 0){
        choice = options[0][0];
    }
    $(dropdown_id).val(choice);
}


function fade_dropdown() {

    const dropdown_elmt  = document.getElementById(dropdown_id.substring(1));  // Get element id without leading '#'
    dropdown_elmt.classList.remove('fading');
    // Triggering reflow is necessary to reload the animation
    void dropdown_elmt.offsetWidth;
    dropdown_elmt.classList.add('fading');
}



// `checked_labels` contains the names of all checked labels, per column
var checked_labels = [Object.create(null),Object.create(null)];
function add_checkboxes(){
    names_list.sort();

    boxes_ids.forEach(function(boxes,col){
        for (var i = 0, len2 = names_list.length; i < len2; ++i) {
            // Create the checkboxes and labels for them here.
            var n = names_list[i];
            var checkbox = document.createElement('input');
            checkbox.type = "checkbox";
            checkbox.name = n;
            checkbox.value = n;
            checkbox.id = boxes.substring(1) + n;  // We don't want the trailing hash here either.
            checkbox.onchange = function(event){checkbox_changed(event,col)};

            var label = document.createElement('label');

            label.htmlFor = checkbox.id;

            if(n !== null_label){
                label.appendChild(document.createTextNode(' ' + n + ' [' + label_counts[n] + ' tr' + (label_counts[n] !== 1 ? 's] ' : '] ')));
             } else {
                // To make only part of the label red & bold, otherwise the span tags are displayed.
                label.appendChild(document.createTextNode(""));
                label.innerHTML = ' <span class="bad_label">' + null_label_txt + '</span> [' + label_counts[n] + ' tr' + (label_counts[n] !== 1 ? 's] ' : '] ');
            }


            var container = $(boxes).find(col_classes[i % 2])[0];
            container.appendChild(checkbox);// Fix for IE browsers, first append, then check.
            container.appendChild(label);
            container.appendChild(document.createElement('br'));
            if(n === null_label){
                continue;
            }
            // In the first column check the selected label, disable the rest
            if(col === 0){
                if(n === selected_label){
                    checkbox.checked = true;
                    checked_labels[col][n] = 1;
                } else {
                    checkbox.disabled = true;

                }
            } else {
                // In the second column check the other labels, disable the selected_label
                if(n === selected_label){
                    checkbox.disabled = true;
                } else {
                    checkbox.checked = true;
                    checked_labels[col][n] = 1;
                }
            }
        }
    });
}


function checkbox_changed(event,col){
    disable_everything();
    var chckbx = event.target;
    console.log(chckbx.checked);
    // Check if it starts with `left_`
    var sibling_id;
    if(chckbx.id.lastIndexOf('left') === 0){
        // By default javascript only replaces the first occurence, which is what we want.
        sibling_id = chckbx.id.replace('left','right');
    } else {
        sibling_id = chckbx.id.replace('right','left');
    }
    //Dis/enable the other based on the checkedness
    $("#" + sibling_id).attr("disabled", chckbx.checked);
    if(chckbx.checked){
        checked_labels[col][chckbx.name] = 1;
    } else {
        delete checked_labels[col][chckbx.name];
    }
    single_mode = Object.keys(checked_labels[col]).length === 0 || Object.keys(checked_labels[1 - col]).length === 0;
    // Other groupings, other options.

    calculate_current_flow();
    fill_in_dropdown();
    fade_dropdown();


    enable_everything();
}

function disable_everything(){
    var input_elements = document.getElementsByTagName('input');
    for(var i = 0, len = input_elements.length; i < len ; i++){
        input_elements[i].disabled = true;
    }
}


function enable_everything(){
    var input_elements = document.getElementsByTagName('input');
    for(var i = 0, len = input_elements.length; i < len ; i++){
       var element = input_elements[i];
       if(element.type === 'checkbox'){
           var sibling_id;
           if(element.id.lastIndexOf('left') === 0){
                sibling_id = element.id.replace('left','right');
            } else {
                sibling_id = element.id.replace('right','left');
            }
           element.disabled = $("#"+sibling_id).is(":checked"); // checked;
           // Debug
           // console.log("Element: "+element.id+" -- Sibling: "+sibling_id+" -- State: "+element.disabled);
        } else {
            element.disabled = false;
       }
    }
}


// current_flow maps middle columns to a pair of values giving the left and right flow
// {'IPR01':[4,8],'IPR02':[8,0]...}
var current_flow;
var distribution;
var single_distribution;
function calculate_current_flow(){
    distribution = [];
    single_distribution = [];
    current_flow = Object.create(null);

    var type = $(type_id + " option:selected").text();
    checked_labels.forEach(function(labels,col){
        for(var label in labels){
            var map = per_label_mapping[label];
            for(var target in map){
                if(GO && type !== "All" && descriptions[target].type !== type){
                    continue;
                }
                if(! (target in current_flow)){
                    current_flow[target] = [0,0];
                }
                current_flow[target][col] += map[target];
            }
        }
    });

    // Fill the initial distribution array
    for(var target in current_flow){
        var big = current_flow[target][0];
        var small = current_flow[target][1];
        if( small > big ){
            var temp = big;
            big = small;
            small = temp;
        }
        // Only take into account the elements that will be shown
        if(small > 0){
            if(!distribution[big]) {
                distribution[big] = 1;
            } else {
                distribution[big]++;
            }
        }
        if(!single_distribution[big]) {
                single_distribution[big] = 1;
        } else {
                single_distribution[big]++;
        }
    }
}


////////// Sankey vizualization ////////////

// Data Processing
// The mappings contain their data as [[source1,target1,value1],[source2,target1,value2],...]
// The processed data is put into global variables so other functions can read the computed values

var column = Object.create(null); // Indicates which column a name is in
var names = Object.create(null); // set of different labels
var names_list = [];
//var flow = [Object.create(null),Object.create(null)]; // a set with the in/outflow of each node


var reverse_mapping = [];
var per_label_mapping = Object.create(null); // a practical mapping used to keep track of the distributions

function process_data(){
    // We assume that the the in/outflow for the middle collum is equal.
    var current_max = 0;
    mapping.forEach(function (d) {
        var source = d[0];
        var target = d[1];
        var value = +d[2];

        if(source  === null){
            d[0] = source = null_label;
        }
        //Fill the list of names
        if(!(source in names)){
            per_label_mapping[source] = Object.create(null);
            names[source] = 1;
            names_list.push(source);
            column[source] = 0;
        }
        per_label_mapping[source][target] = value;

        // Generate a list of reverse mappings
        reverse_mapping.push([target,source,value]);
        column[target] = 1;

    });


    // Counting the number of unlabeled genes
    // Since label_counts only contains the (label -> count) numbers, we substract the sum of labeled counts from the total transcript count
    var gene_count = 0;
    for(var label in label_counts){
        gene_count += +label_counts[label];
    }
    label_counts[null_label] = total_count - gene_count;
}

function filter_links_to_use(){
    var links = [];
    var min_flow = $(dropdown_id + " option:selected").val();
    var type = $(type_id + " option:selected").text();

    if(!single_mode){
        mapping.forEach(function(s) {
            var left_flow = current_flow[s[1]] ? current_flow[s[1]][0]: 0;
            var right_flow = current_flow[s[1]]? current_flow[s[1]][1]: 0;
            if(s[0] in checked_labels[0] &&
              ((right_flow >= min_flow && left_flow > 0) || (left_flow >= min_flow && right_flow > 0))){

                  if(GO && type !== "All" && type !== descriptions[s[1]].type){
                        // Do nothing
                 } else {
                    links.push(copy_link(s));
                 }
            }
        });

        reverse_mapping.forEach(function(s) {
            var left_flow = current_flow[s[0]]? current_flow[s[0]][0]: 0;
            var right_flow = current_flow[s[0]]? current_flow[s[0]][1]: 0;
            if(s[1] in checked_labels[1] &&
              ((right_flow >= min_flow && left_flow > 0) || (left_flow >= min_flow && right_flow > 0))){

                  if(GO && type !== "All" && type !== descriptions[s[0]].type){
                        // Do nothing
                  } else {
                        links.push(copy_link(s));
                  }
            }
        });
    } else {
        // single_mode
        var other_col = Object.keys(checked_labels[0]).length === 0 ? 0:1;
        var label_col = 1 - other_col;
        var mapping_to_use = other_col === 0 ? reverse_mapping : mapping;
        mapping_to_use.forEach(function(s) {
            var flow = current_flow[s[other_col]]? current_flow[s[other_col]][label_col]: 0;
            if(s[label_col] in checked_labels[label_col] && flow >= min_flow){
                // Is the type correct? We only check this if we're dealing with GO terms
                if(GO && type !== "All" && type !== descriptions[s[other_col]].type){
                    // Do nothing
                } else {
                    links.push(copy_link(s));
                }
            }
        });
    }
    return links;
}

function copy_link(link){
    return [link[0],link[1],link[2]];
}
var divisors;
/* Normalize links according to the normalization setting
 * 0: do nothing
 * 1: Every block has width 100, divide by the sum of outgoing links
 * 2: Every links is divided by the total number of genes belonging to a label
 */
function normalize_links(links){
    var option = parseInt($(normalize_id + " option:selected").val());
    switch(option){
        case 0:
            return;
        break;
        case 1:
            // First we calculate the current divisor
            divisors = Object.create(null);
            var name;
            for(name in names){
                divisors[name] = 0;
            }
            links.forEach(function(link){
                if(link[0] in divisors){
                    divisors[link[0]] += +link[2];
                } else {
                    divisors[link[1]] += +link[2];
                }
            });
            // Divide by the calculated divisor
            links.forEach(function(link){
                if(link[0] in divisors){
                    link[2] = link[2]*100/divisors[link[0]];
                } else {
                    link[2] = link[2]*100/divisors[link[1]];
                }
            });
        break;
        case 2:
            links.forEach(function(link){
                var divisor = link[0] in names? label_counts[link[0]] :label_counts[link[1]];
                link[2] = link[2]*100/divisor;
            });
        break;
        default:
        return;
    }
}


// The format of the numbers when hovering over a link or node
var formatNumber = d3.format(",.0f"),
    format = function(d) { return formatNumber(d) + " transcript" + (Math.floor(d) !== 1 ? 's' : '');; },
    color = d3.scale.category20();


// real_width is used for layout purposes
var margin = {top: 1, right: 1, bottom: 6, left: 1},
    real_width = calculate_good_width(),
    width = real_width - margin.left - margin.right,
    height = calculate_good_height() - margin.top - margin.bottom;

function calculate_good_height(){
    // return Math.min(window.innerHeight - 200, Math.log2(2*(mapping.length + 1))* 200);
    // New height calculation taking into account a ~ 200px-high row for settings
    // This should allow the user to scroll and have controls + diagram on the same screen
    return Math.min(window.innerHeight - 320, Math.log2(2*(mapping.length + 1))* 200);
}

function calculate_good_width(){
    // return Math.min(window.innerWidth - margin.left - margin.right - 80,Math.log2(2*(mapping.length + 1))* 200);
    return Math.min(window.innerWidth - margin.left - margin.right - 80 - 300, Math.log2(2*(mapping.length + 1))* 200);

}

// Create an empty svg as a placeholder
var svg = d3.select("#sankey").append("svg")
	    .attr("width", width + margin.left + margin.right)
	    .attr("height", height + margin.top + margin.bottom);



/////////////// (Re)draw the sankey diagram /////////////////
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
    normalize_links(good_links);
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
       graph.nodes[i] = {name: d.replace(/^\d+_/g,''),
                          href: urls[col].replace(place_holder,d).replace('GO:','GO-'),
                          nodeId: "node_" + i.toString()}//,
                          //original_flow:flow[col][d]};
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
        .sort(function(a, b) { return b.dy - a.dy; })

   link.append("hovertext")
	    .text(function(d) { return create_link_hovertext(d)});

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
            /* The line below displayed the dragged element on top of the others, by appending to the parent node.
             * However, doing so caused problems with the click event in Chrome, so we replaced it.
             * Instead, we assigned a unique identifier to the nodes ('nodeId') and sort them using a custom function
             * that puts the dragged element in front of the others.
             */
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
	    .style("fill", function(d) { console.log(d.name);return d.color = color(d.name); })
	    // .style("stroke", function(d) { return d3.rgb(d.color).darker(2); })
	    .style("stroke", function(d) { return d.color = color(d.name); })
	    .append("hovertext")
	    .text(function(d) { return create_hovertext(d)});
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
            // d3.select(this).on('click', click);


        }

        function create_hovertext(d){
            if(d.name in label_counts ) {
                return "<span class='d3-tip-title'>" + d.name + "</span><br>\n" + label_counts[d.name] + " transcript" + (label_counts[d.name] !== 1 ? 's' : '');
                // return d.name + "\n" + label_counts[d.name] + " transcript" + (label_counts[d.name] !== 1 ? 's' : '');
            }
            var hover_text = "<span class='d3-tip-title'>" + d.name + "</span><br>";
            if(d.name in descriptions){
               hover_text += "\n" + descriptions[d.name].desc + "<br>";
            }
            var used_name = d.name;
            if(!(used_name in current_flow)){
                used_name = exp_id + '_' + used_name;
            }
            var flows = current_flow[used_name];
            return hover_text + "\n" + flows[0]+ " transcript" + (flows[0] !== 1 ? 's' : '') + ' | ' +  flows[1] + " transcript" + (flows[1] !== 1 ? 's' : '');
        }

        // The hovertext varies depending on the normalization used
        function create_link_hovertext(d){
            var label_node, target_node, arrow = "<br>→ ";
            if( d.source.name in names){
                label_node = d.source;
                target_node = d.target;
            } else {
                label_node = d.target;
                target_node = d.source;
            }
            var hover_string = "<span class='d3-tip-title'>" + label_node.name + arrow + target_node.name + "</span><br>\n";
            var option = parseInt($(normalize_id + " option:selected").val());
            switch(option){
                case 0:
                    hover_string += d.value + " transcript" + (d.value !== 1 ? 's' : '');
                break;
                case 1:
                    hover_string += parseFloat(d.value).toFixed(2) + '% of transcripts in intersection';
                break;
                case 2:
                    hover_string += parseFloat(d.value).toFixed(2) + '% of transcripts in ' + label_node.name;
                break;
                default:
            }
            return  hover_string ;
        }


        function create_node_title(name){
            var max_length = 40;
            if(name in descriptions){
                var descrip = descriptions[name].desc;
                if(descrip.length > max_length + 5){
                    descrip = descrip.substring(0,max_length - 3) + '...';
                }
                return descrip;
            } else {
                return name;
            }
        }
}
