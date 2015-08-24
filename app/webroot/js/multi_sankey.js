"use strict";
/* 
 * TODO: first just filter on the labels and middle amount
 * Stage 2 : create a second distribution to use as the second filter
 * Stage 3 : Get single mode working
 */

// label to display instead of null.
var null_label = 'no label';
// When no labels are checked we fall back to comparing the second mapping
var single_mode = false;
// The initial amout of nodes to show
var nodes_to_show = 20;

document.observe('dom:loaded', function(){
  process_data();
  add_checkboxes();
  calculate_current_flow();
  fill_in_dropdown();
  
  draw_sankey(); 
  
});

// real_width is used for layout purposes
var margin = {top: 1, right: 1, bottom: 6, left: 1},
    real_width = calculate_good_width(),    
    width = real_width - margin.left - margin.right,
    height = calculate_good_height() - margin.top - margin.bottom;

function calculate_good_height(){
    return Math.min(window.innerHeight - 200, Math.log2(first_mapping.length + second_mapping.length)* 200);   
}

function calculate_good_width(){
    return Math.min(window.innerWidth - margin.left - margin.right - 80,Math.log2(first_mapping.length + second_mapping.length)* 200);
}


////////// Behaviour of the refine button and fields ////////////

var first_dropdown = 'middle_min';
var second_dropdown = 'right_min';
function fill_in_dropdown(){

    // Clear the dropdown before adding new options
    $(first_dropdown).update();
    
    // If there are no options, ask the user to select something
    if(first_options.length === 0){
        $(first_dropdown).options.add(new Option('Please select labels', 0));
        return;
    }
    // Fill in the dropdown
    for(var i = 0,len = first_options.length; i < len; i++){
        var option_string = '>=' + first_options[i][0] + ' [' + first_options[i][1] + ' ' + dropdown_filter_name[0]+ ']';
        $(first_dropdown).options.add(new Option(option_string, first_options[i][0]));
    }

    $(first_dropdown).value = first_minimum_size;


    // TODO: put first and second in arrays

    // Clear the dropdown before adding new options
    $(second_dropdown).update();
    
    // If there are no options, ask the user to select something
    if(second_options.length === 0){
        $(second_dropdown).options.add(new Option('Please select labels', 0));
        return;
    }
    // Fill in the dropdown
    for(var i = 0,len = second_options.length; i < len; i++){
        var option_string = '>=' + second_options[i][0] + ' [' + second_options[i][1] + ' ' + dropdown_filter_name[1]+ ']';
        $(second_dropdown).options.add(new Option(option_string, second_options[i][0]));
    }

    $(second_dropdown).value = second_minimum_size;
}

var first_minimum_size;
var first_options = [];
function calculate_first_options(){
    first_options = [];
    var total = 0;

    var used_distribution = single_mode? single_distribution : distribution;
    for(var i = used_distribution.length - 1; i > 0; i--){
        if(typeof used_distribution[i] != 'undefined' && used_distribution[i] !== 0){
            total += used_distribution[i];
            if(!first_minimum_size && total > nodes_to_show){
                first_minimum_size = first_options[first_options.length - 1][0];
            }
            first_options.push([i,total]);
        }
    }
    // show options in ascending order
    first_options.reverse();
    // Set a decent minimum value, if choice was never set, there aren't that many choices so pick the first value.
    if(!first_minimum_size && first_options.length > 0){
        first_minimum_size = first_options[0][0];
    }
}

var second_minimum_size;
var second_options = [];
function calculate_second_options(){
    second_options = [];
    var total = 0;

    var used_distribution = second_distribution;
    for(var i = used_distribution.length - 1; i > 0; i--){
        if(typeof used_distribution[i] != 'undefined' && used_distribution[i] !== 0){
            total += used_distribution[i];
            if(!second_minimum_size && total > nodes_to_show){
                second_minimum_size = second_options[second_options.length - 1][0];
            }
            second_options.push([i,total]);
        }
    }
    // show options in ascending order
    second_options.reverse();
    // Set a decent minimum value, if choice was never set, there aren't that many choices so pick the first value.
    if(!second_minimum_size && second_options.length > 0){
        second_minimum_size = second_options[0][0];
    }
}


// checked_labels contain the names of all checked labels
var checked_labels = Object.create(null);
var boxes_ids = ['left_boxes'];
var col_classes = ['left_col','right_col'];
function add_checkboxes(){
    names_list.sort();

    boxes_ids.forEach(function(boxes,col){
        for (var i = 0, len2 = names_list.length; i < len2; ++i) {
            // Create the checkboxes and labels for them here.
            var n = names_list[i];
            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = n;
            checkbox.value = n;
            checkbox.id = boxes + n;
            checkbox.onchange = function(event){checkbox_changed(event)};

            var label = document.createElement('label');

            label.htmlFor = checkbox.id;
            
            if(n !== null_label){
                label.appendChild(document.createTextNode(' ' + n + ' [' + label_counts[n] + ' genes] '));
             } else {
                // To make only part of the label red & bold, otherwise the span tags are displayed.
                label.appendChild(document.createTextNode(''));
                label.innerHTML = ' <span class="bad_label">' + n + '</span> [' + label_counts[n] + ' genes] ';                
            }            
            
            var container = $(boxes).select('.' + col_classes[i % 2])[0];
            container.appendChild(checkbox);// Fix for IE browsers, first append, then check.
            container.appendChild(label);
            container.appendChild(document.createElement('br'));
            // Don't check the 'no label' label
            if(n === null_label){
                continue;
            }
            // check some values
            if((i + col) % 2 === 0){
                checkbox.checked = true;
                checked_labels[n] = 1
            } 
        }
    });  
}


function checkbox_changed(event){
    disable_everything();
    var chckbx = event.target;
   
    if(chckbx.checked){
        checked_labels[chckbx.name] = 1;
    } else {
        delete checked_labels[chckbx.name];        
    }        
    single_mode = Object.keys(checked_labels).length === 0 || Object.keys(checked_labels).length === 0;
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
         input_elements[i].disabled = false;       
    }
}



////////// Sankey vizualization ////////////

// Data Processing 
// The mappings contain their data as [[source1,target1,value1],[source2,target1,value2],...]

// The flow variable contains a with the in/outflow of each node
var column = {};
var names = Object.create(null); // set of different labels
var names_list = [];
var per_label_mapping = Object.create(null);
var second_hashmap = Object.create(null);

function process_data(){
    // We assume that the the in/outflow for the middle collum is equal.
    first_mapping.forEach(function (d) {
        var source = d[0];
        var target = d[1];
        var value = +d[2];
    
        if(source === null){
            d[0] = source = null_label;
        }
        //Fill the list of names
        if(!(source in names)){
            per_label_mapping[source] = Object.create(null);
            names[source] = 1;
            names_list.push(source);
            column[source] = 0;
        }
        column[target] = 1;
        per_label_mapping[source][target] = value;
    
    });
    var names2 = Object.create(null);
    for(var i=0, len=second_mapping.length; i < len; i++){
        var d = second_mapping[i];
        var source = d[0];
        var target = d[1];
        var value = +d[2];
    
        if(source === null){
            d[0] = source = null_label;
        }
        //Fill the list of names
        if(!(source in names2)){
            second_hashmap[source] = Object.create(null);
            names2[source] = 1;            
        } 
        second_hashmap[source][target] = value; 
        column[target] = 2;     
    }
}

var distribution = [];
var second_distribution = [];
var single_distribution = [];
// current_flow maps names to the current inflow, twice
// [{'IPR01':4,'IPR02':8,...},{'HOM02':4,'HOM03':8,...}]

var first_flow = Object.create(null);
var second_flow = Object.create(null);
function calculate_current_flow(){
    /* caculate the current flow in a waterfall fashion 
     * First the flow into the first column is determined, we already know the cutoff point
     * So then the second flow is calculated according to the nodes we show at first.
     */
    
    // Label to first col flow
    for(var label in checked_labels){
        var map = per_label_mapping[label];
        for(var target in map){
            if(!(target in first_flow)){
                first_flow[target] = 0;
            }
            first_flow[target] += map[target];
        }
    }

    // Fill the first distribution array
    for(var target in first_flow){
        var flow = first_flow[target];
        if(!distribution[flow]) {
            distribution[flow] = 1;
        } else {
            distribution[flow]++;
        }        
    }
    
    calculate_first_options();
    
    // The second flow is calculated here
    for(var middle_node in first_flow){
        if(first_flow[middle_node] >= first_minimum_size){
            var map = second_hashmap[middle_node];
            for(var target in map){
                 if(!(target in second_flow)){
                    second_flow[target] = 0;
                 }
                 second_flow[target] += map[target];                    
            }
        }
    }
    // Fill the second distribution array
    for(var target in second_flow){
        var flow = second_flow[target];
        if(!second_distribution[flow]) {
            second_distribution[flow] = 1;
        } else {
            second_distribution[flow]++;
        }        
    }

    calculate_second_options();

}

function update_current_flow(name, col, selected){
//TODO update to current situation
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
            if(in_distr_now){
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

function filter_links_to_use(){
    var links = [];
    var min_flow = $(first_dropdown).options[$(first_dropdown).selectedIndex].value;

    first_mapping.forEach(function(s) { 
        var flow = first_flow[s[1]] ? first_flow[s[1]]: 0;
        if(s[0] in checked_labels && flow >= min_flow){
            links.push(copy_link(s));
            console.log(s[0],s[1], flow);
           }
    });
    var second_min_flow = $(second_dropdown).options[$(second_dropdown).selectedIndex].value;
    second_mapping.forEach(function(s) { 
        var flow = second_flow[s[1]] ? second_flow[s[1]]: 0;
        if(flow >= second_min_flow){
            links.push(copy_link(s));
           }
    });    
    return links;
}

function copy_link(link,first_mapping){
    return [link[0],link[1],link[2]];    
}


// The format of the numbers when hovering over a link or node
var formatNumber = d3.format(",.0f"),
    format = function(d) { return formatNumber(d) + " genes"; },
    color = d3.scale.category20();

var svg = d3.select("#sankey").append("svg")
	    .attr("width", width + margin.left + margin.right)
	    .attr("height", height + margin.top + margin.bottom);
var graph;
 // (Re)draw the sankey diagram
function draw_sankey() {

    // Remove the old svg if it exists
    d3.select("svg").text('');

    var svg = d3.select("svg")
	    .append("g")
	    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    // Based on http://www.d3noob.org/2013/02/formatting-data-for-sankey-diagrams-in.html
    graph = {"nodes" : [], "links" : []};
    
    var good_links = filter_links_to_use();
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
                          href: urls[col].replace(place_holder,d)};//,
                         // original_flow:flow[col][d] };
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
	    .text(function(d) { return d.source.name + " → " + d.target.name + "\n" + format(d.value); });
      
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
	    .text(function(d) { return d.name + "\n" + format(d.value) + " / " + format(d.original_flow); });

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
}

