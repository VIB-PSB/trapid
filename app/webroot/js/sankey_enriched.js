"use strict";

/* Global variables: */

// label to display instead of null.
var null_label = 'no label';
// When no labels are checked we fall back to comparing the second mapping
var single_mode = false;
// The initial amout of nodes to show
var nodes_to_show = 20;
// checked_labels contain the names of all checked labels
var checked_labels = Object.create(null);

// The flow variable contains a with the in/outflow of each node
var column = {};
var names = Object.create(null); // set of different labels
var names_list = [];
var per_label_mapping = Object.create(null);
var second_hashmap = Object.create(null);
var p_values = Object.create(null);
var minimum_size;
var options = [];

/* Html identifiers used to select on */
var hidden_id = 'hidden';
var type_id = 'type';
var p_val_id = 'pvalue';
var dropdown = 'right_min';

var boxes = 'left_boxes';
var col_classes = ['left_col','right_col'];

/* Global defined in sankey_enriched 
    first_mapping :
    second_mapping : containdata as [[source1,target1,value1],[source2,target1,value2],...]
    descriptions :
    label_counts :
    total_count :
    dropdown_filter_name : 
    urls :
    place_holder : 
    GO :
*/

document.observe('dom:loaded', function(){
  // Hide the option to filter on type when we're not dealing with GO.
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

function calculate_good_height(){
    return Math.min(window.innerHeight - 200, Math.log2(first_mapping.length + second_mapping.length)* 200);   
}

function calculate_good_width(){
    return Math.min(window.innerWidth - margin.left - margin.right - 80,Math.log2(first_mapping.length + second_mapping.length)* 200);
}


////////// Behaviour of the refine button and fields ////////////

function hide_type(){
    // Parent because otherwise the label stays visible.
    $(type_id).parentElement.style.display = 'none';
}


function fill_in_dropdown(){
    // Clear the dropdown before adding new options
    $(dropdown).update();
    
    // If there are no options, ask the user to select something
    if(second_options.length === 0){
        $(dropdown_id).options.add(new Option('Please select labels', 0));
        return;
    }
    // Fill in the dropdown
    for(var i = 0,len = second_options.length; i < len; i++){
        var option_string = '>=' + second_options[i][0] + ' [' + second_options[i][1] + ' ' + dropdown_filter_name[1]+ ']';
        $(dropdown_id).options.add(new Option(option_string, second_options[i][0]));
    }

    $(dropdown_id).value = second_minimum_size;
}


function fill_in_p_values(){
    var list = Object.keys(p_values);
    list.sort();
    for(var i = 0, len = list.length; i < len; i++){
      $(p_val_id).options.add(new Option(list[i], i));
    }
}


function calculate_options(){
    options = [];
    minimum_size = undefined;
    var total = 0;

    var used_distribution = distribution;//single_mode? single_distribution : distribution;
    for(var i = used_distribution.length - 1; i > 0; i--){
        if(typeof used_distribution[i] != 'undefined' && used_distribution[i] !== 0){
            total += used_distribution[i];
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
        checkbox.onchange = function(event){checkbox_changed(event)};

        var label = document.createElement('label');

        label.htmlFor = checkbox.id;
        
        if(n !== null_label){
            label.appendChild(document.createTextNode(' ' + n + ' [' + label_counts[n] + ' genes] '));
         } else {
            // To make only part of the label red & bold
            label.appendChild(document.createTextNode(''));
            // We can't just create a textNode with the text we want because this displays the span tags as text
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
        if(i % 2 === 0){
            checkbox.checked = true;
            checked_labels[n] = 1
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
    single_mode = Object.keys(checked_labels).length === 0;
    // Other groupings, other options.
    update_current_flow(chckbx.name,chckbx.checked);
    
    
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

function process_data(){
    // We assume that the the in/outflow for the middle collum is equal.
    first_mapping.forEach(function (d) {
        var source = d[0];
        var target = d[1];
        var value = +d[2];
        var p_val = d[4];
    
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
        //Fill the set of p values
        p_values[p_val] = 1;

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

var middle_nodes = Object.create(null);
function determine_middle_nodes(){
    for(var label in checked_labels){
        var map = per_label_mapping[label];
        for(var target in map){
            middle_nodes[target] = 1;
        }
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

    //TODO remove 
/*
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
    */
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

function update_current_flow(name, selected){
    var map = per_label_mapping[name];
    var changed_nodes = Object.create(null);
    for(var target in map){
        // The target might not have been added yet
        if(! (target in first_flow)){
            first_flow[target] = 0;
        }
        var flow_before = first_flow[target];
        if(flow_before === 0){
            changed_nodes[target] = 1;
        }
        
        // Update the first flow
        if(selected){
            // Add the flow when something new is checked
            first_flow[target] += map[target];
        } else {
            // Remove it when the label is deselected
            first_flow[target] -= map[target];
        }

        var flow_after = first_flow[target];
        if(flow_after === 0){
            changed_nodes[target] = 1;
        }

        // decrement the previous and increment the current
        distribution[flow_before]--;
        // Check if it exists, create the value or increment it
        if(!distribution[flow_after]) {
           distribution[flow_after] = 1;
        } else {
            distribution[flow_after]++;
        }
            
        /*// Also update the single_distribution
        if(before !== after){
            single_distribution[before]--;
            if(!single_distribution[after]) {
               single_distribution[after] = 1;
            } else {
               single_distribution[after]++;
            }
        }*/
    }
    calculate_first_options();
    for(var node in changed_nodes){
        var map = second_hashmap[node];
        for(var target in map){
            // The target might not have been added yet
            if(! (target in second_flow)){
                second_flow[target] = 0;
            }
            var flow_before = first_flow[target];
              
            // Update the first flow
            if(selected){
                // Add the flow when something new is checked
                second_flow[target] += map[target];
            } else {
                // Remove it when the label is deselected
                second_flow[target] -= map[target];
            }

            var flow_after = second_flow[target];

            // decrement the previous and increment the current
            second_distribution[flow_before]--;
            // Check if it exists, create the value or increment it
            if(!second_distribution[flow_after]) {
               second_distribution[flow_after] = 1;
            } else {
                second_distribution[flow_after]++;
            }
        }
    }
    calculate_second_options(); 
}

function filter_links_to_use(){
    var links = [];
    //var min_flow = $(first_dropdown).options[$(first_dropdown).selectedIndex].value;
    var p_value = $(p_val_id).options[$(p_val_id).selectedIndex].text;
    var type = $(type_id).options[$(type_id).selectedIndex].text;
    var show_hidden = $(hidden_id).checked;
    var good_middle_nodes = Object.create(null);
    first_mapping.forEach(function(s) {
        //console.log(s[0], p_value, s[3], show_hidden,);
        if(s[0] in checked_labels && p_value === s[4]){
            // If it4s a hidden link only show it when show hidden is true.
            if(!show_hidden && s[3] === "1"){
                return;
            }
            // GO can filter on type, it it's not all, it has to match the selected type
            if(GO && type !== "All" && type !== descriptions[s[1]]['type']){
                return;
            }

            links.push(copy_link(s));
            good_middle_nodes[s[1]] = 1; 
        }
    });
    //var second_min_flow = $(second_dropdown).options[$(second_dropdown).selectedIndex].value;
    second_mapping.forEach(function(s) { 
        var flow = second_flow[s[1]] ? second_flow[s[1]]: 0;
        if(s[0] in good_middle_nodes ){//&& flow >= second_min_flow){
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

 // (Re)draw the sankey diagram
function draw_sankey() {

    // Remove the old svg if it exists
    d3.select("svg").text('');

    var svg = d3.select("svg")
	    .append("g")
	    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    // Based on http://www.d3noob.org/2013/02/formatting-data-for-sankey-diagrams-in.html
    var graph = {"nodes" : [], "links" : []};
    
    var good_links = filter_links_to_use();
    console.log(good_links);
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

