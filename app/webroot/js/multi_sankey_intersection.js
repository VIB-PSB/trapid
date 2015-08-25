"use strict";

document.observe('dom:loaded', function(){
  process_data();
  add_checkboxes();
  calculate_current_flow();
  fill_in_dropdown();
  draw_sankey();
});

// This defines if we're comparing 2 groups or just viewing a single side of the diagram
var single_mode = false;
var null_label = 'no label';

////////// Behaviour of the refine button and fields ////////////

var dropdown_name = 'middle_min';
function fill_in_dropdown(){    
    var choice;
    var total = 0;
    var options = [];
    var used_distribution = single_mode? single_distribution : distribution;
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
    $(dropdown_name).update();
    
    // If there are no options, ask the user to select something
    if(options.length === 0){
        $(dropdown_name).options.add(new Option("Please select labels.", 0));
        return;
    }
    // show options in ascending order
    options.reverse();

    for(var i = 0,len = options.length; i < len; i++){
        var option_string = ">=" + options[i][0] + " [" + options[i][1] + " " + dropdown_filter_name+ "]";
        $(dropdown_name).options.add(new Option(option_string, options[i][0]));
    }
    // Set a decent minimum value, if choice was never set, there aren't that many  choices so pick the first value.
    if(!choice && options.length > 0){
        choice = options[0][0];
    }
    $(dropdown_name).value = choice;
}

// checked_labels contain the names of all checked labels, per column
var checked_labels = [Object.create(null),Object.create(null)];
var boxes_ids = ['left_boxes','right_boxes'];
var col_classes = ['left_col','right_col'];
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
            checkbox.id = boxes + n;
            checkbox.onchange = function(event){checkbox_changed(event,col)};

            var label = document.createElement('label');

            label.htmlFor = checkbox.id;
            
            if(n !== null_label){
                label.appendChild(document.createTextNode(' ' + n + ' [' + label_counts[n] + ' genes] '));
             } else {
                // To make only part of the label red & bold, otherwise the span tags are displayed.
                label.appendChild(document.createTextNode(""));
                label.innerHTML = ' <span class="bad_label">' + n + '</span> [' + label_counts[n] + ' genes] ';                
            }
            
            
            var container = $(boxes).select('.' + col_classes[i % 2])[0];
            container.appendChild(checkbox);// Fix for IE browsers, first append, then check.
            container.appendChild(label);
            container.appendChild(document.createElement('br'));
            if(n === null_label){
                continue;
            }
            // Make sure other checkboxes are selected in each collumn
            if((i + col) % 2 === 1){
                checkbox.checked = true;
                checked_labels[col][n] = 1;
            } else {
                checkbox.disabled = true;
            }
        }
    });  
}

function checkbox_changed(event,col){
    disable_everything();
    var chckbx = event.target;
    // Check if it starts with left_
    var sibling_id;
    if(chckbx.id.lastIndexOf('left',0) === 0){
        // By default javascript only replaces the first occurence, which is what we want.
        sibling_id = chckbx.id.replace('left','right');
    } else {
        sibling_id = chckbx.id.replace('right','left');
    }
    //Dis/enable the other based on the checkedness
    $(sibling_id).disabled = chckbx.checked;
    if(chckbx.checked){
        checked_labels[col][chckbx.name] = 1;
    } else {
        delete checked_labels[col][chckbx.name];        
    }        
    single_mode = Object.keys(checked_labels[col]).length === 0 || Object.keys(checked_labels[1 - col]).length === 0;
    // Other groupings, other options.
    update_current_flow(chckbx.name,col,chckbx.checked);
    
    fill_in_dropdown();
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
           if(element.id.lastIndexOf('left',0) === 0){
                sibling_id = element.id.replace('left','right');
            } else {
                sibling_id = element.id.replace('right','left');
            }
            element.disabled = $(sibling_id).checked;
        } else {
            element.disabled = false;
       }
    }
}


var distribution = [];
var single_distribution = [];
// current_flow maps middle columns to a pair of values giving the left and right flow
// {'IPR01':[4,8],'IPR02':[8,0]...}
var current_flow = Object.create(null);
function calculate_current_flow(){
    checked_labels.forEach(function(labels,col){
        for(var label in labels){
            var map = per_label_mapping[label];
            for(var target in map){
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

function update_current_flow(name, col, selected){
    var map = per_label_mapping[name];
    for(var target in map){
        // The target might not have been added yet
        if(! (target in current_flow)){
            current_flow[target] = [0,0];
        }
        var before = Math.max(current_flow[target][0],current_flow[target][1]);
        var in_distr_before = current_flow[target][0] > 0 && current_flow[target][1] > 0;
        // Update current_flow
        if(selected){
            // Add the flow when something new is checked
            current_flow[target][col] += map[target];
        } else {
            // Remove it when the label is deselected
            current_flow[target][col] -= map[target];
        }

        var after = Math.max(current_flow[target][0],current_flow[target][1]);
        var in_distr_now = current_flow[target][0] > 0 && current_flow[target][1] > 0;
        /* Update the distribution if it changed
         * if this node wasn't part of the distribution and still isn't, or it was and the value didn't change the distribution stays the same
         *  The distribution changes in 3 cases
         *  - Node was in the distribution and isn't anymore -> The previous value is decremented
         *  - Node was in the distribution and still is, with a different value, the previous gets decremented, the current one incremented
         *  - Node wasn't in the distribution and now is, increment the new value 
         */
        if(in_distr_before){
            if(in_distr_now ){
                // If the value changed, decrement the previous and increment the current
                if(before !== after){
                    distribution[before]--;
                    // Check if it exists, create the value or increment it
                    if(!distribution[after]) {
                       distribution[after] = 1;
                    } else {
                        distribution[after]++;
                    }
                } else {
                    // The value stayed the same, so do nothing.
                }
            } else {
                // Was in the distribution before, not anymore, so decrement the previous
                distribution[before]--;
            }
        } else {
             if(in_distr_now){
                // Check if it exists, create the value or increment it
                if(!distribution[after]) {
                   distribution[after] = 1;
                } else {
                    distribution[after]++;
                }
            }
        }
        // Also update the single_distribution
        if(before !== after){
            single_distribution[before]--;
            if(!single_distribution[after]) {
               single_distribution[after] = 1;
            } else {
               single_distribution[after]++;
            }   
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
    var min_flow = $(dropdown_name).options[$(dropdown_name).selectedIndex].value;

    if(!single_mode){
        mapping.forEach(function(s) {
            var left_flow = current_flow[s[1]] ? current_flow[s[1]][0]: 0;
            var right_flow = current_flow[s[1]]? current_flow[s[1]][1]: 0;  
            if(s[0] in checked_labels[0] && 
              ((right_flow >= min_flow && left_flow > 0) || (left_flow >= min_flow && right_flow > 0))){
                  links.push(copy_link(s));
            }
        });

        reverse_mapping.forEach(function(s) { 
            var left_flow = current_flow[s[0]]? current_flow[s[0]][0]: 0;
            var right_flow = current_flow[s[0]]? current_flow[s[0]][1]: 0;  
            if(s[1] in checked_labels[1] && 
              ((right_flow >= min_flow && left_flow > 0) || (left_flow >= min_flow && right_flow > 0))){
                  links.push(copy_link(s));
            }        
        });
    } else {
        var other_col = Object.keys(checked_labels[0]).length === 0 ? 0:1;
        var label_col = 1 - other_col;
        var mapping_to_use = other_col === 0 ? reverse_mapping : mapping;
        mapping_to_use.forEach(function(s) {
            var flow = current_flow[s[other_col]]? current_flow[s[other_col]][label_col]: 0;
            if(s[label_col] in checked_labels[label_col] && flow >= min_flow){
                links.push(copy_link(s));
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
    var option = $('normalization').selectedIndex;
    switch(option){
        case 0:
            return;
        break;
        case 1:
            // First we calculate the current divisor
            divisors = Object.create(null);
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
    format = function(d) { return formatNumber(d) + " genes"; },
    color = d3.scale.category20();


// real_width is used for layout purposes
var margin = {top: 1, right: 1, bottom: 6, left: 1},
    real_width = calculate_good_width(),
    width = real_width - margin.left - margin.right,
    height = calculate_good_height() - margin.top - margin.bottom;

function calculate_good_height(){
    return Math.min(window.innerHeight - 200, Math.log2(2*mapping.length)* 200);
}

function calculate_good_width(){
    return Math.min(window.innerWidth - margin.left - margin.right - 80,Math.log2(2*mapping.length)* 200);
}

// Create an empty svg as a placeholder
var svg = d3.select("#sankey").append("svg")
	    .attr("width", width + margin.left + margin.right)
	    .attr("height", height + margin.top + margin.bottom);


/////////////// (Re)draw the sankey diagram /////////////////
function draw_sankey() {

    // Remove the old svg if it exists
    d3.select("svg").text('');

    var svg = d3.select("svg")
	    .append("g")
	    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

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
       graph.nodes[i] = { name: d.replace(/^\d+_/g,''),
                          href: urls[col].replace(place_holder,d).replace('GO:','GO-')}//,
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
        .sort(function(a, b) { return b.dy - a.dy; });

   link.append("title")
	    .text(function(d) { return create_link_hovertext(d)});
      
    // Work around to make something dragable also clickable
    // From http://jsfiddle.net/2EqA3/3/

    var node = svg.append("g").selectAll(".node")
	    .data(graph.nodes)
	    .enter().append("g")
	    .attr("class", "node")
	    .attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; })
	    .call(d3.behavior.drag()
	    .origin(function(d) { return d; })
	    .on("dragstart", function() { 
                            d3.event.sourceEvent.stopPropagation();
                            this.parentNode.appendChild(this); })
	    .on("drag", dragmove))
        .on('click', click);

    function click(d) {
      if (d3.event.defaultPrevented)
        { return;}
      window.open(d.href,'_blank');
    }

    node.append("rect")
	    .attr("height", function(d) { return d.dy; })
	    .attr("width", sankey.nodeWidth())
	    .style("fill", function(d) { return d.color = color(d.name); })
	    .style("stroke", function(d) { return d3.rgb(d.color).darker(2); })
	    .append("title")
	    .text(function(d) { return create_hovertext(d)});
    node.append("text")
	    .attr("x", -6)
	    .attr("y", function(d) { return d.dy / 2; })
	    .attr("dy", ".35em")
	    .attr("text-anchor", "end")
	    .attr("transform", null)
	    .text(function(d) { return d.name; })
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
               return descriptions[d.name].desc;
            } 
            if(d.name in label_counts ) {
                return d.name + "\n" + label_counts[d.name] + " genes";
            } else {
                return d.name;
            }
        }

        // The hovertext varies depending on the normalization used
        function create_link_hovertext(d){
            var label_node, target_node, arrow = " → ";
            if( d.source.name in names){
                label_node = d.source;
                target_node = d.target;
            } else {
                label_node = d.target;
                target_node = d.source;
            } 
            var hover_string = label_node.name + arrow + target_node.name + "\n";
            var option = $('normalization').selectedIndex;
            switch(option){
                case 0:
                    hover_string += d.value + ' genes';
                break;
                case 1:
                    hover_string += parseFloat(d.value).toFixed(2) + '% of genes in intersection';
                break;
                case 2:
                    hover_string += parseFloat(d.value).toFixed(2) + '% of genes in ' + label_node.name;
                break;
                default:
            }
            return  hover_string ; 
        }
}

