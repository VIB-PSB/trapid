"use strict";

document.observe('dom:loaded', function(){
  process_data();
  add_checkboxes();
  get_checked_labels();
  calculate_current_flow();
  fill_in_dropdown();
  draw_sankey();
});

////////// Behaviour of the refine button and fields ////////////

var dropdown_name = 'middle_min';
function fill_in_dropdown(){    
    var choice;
    var total = 0;
    var options = [];
    for(var i = distribution.length - 1; i > 0; i--){
        if(typeof distribution[i] != 'undefined' && distribution[i] !== 0){
            total += distribution[i];
            if(!choice && total >= 25){
                choice = i;
            }
            options.push([i,total]);          
        }
    }
    // Clear the dropdown before adding new options
    $(dropdown_name).update();
    // show options in ascending order
    options.reverse();

    for(var i = 0,len = options.length; i < len; i++){
        var option_string = ">=" + options[i][0] + " [" + options[i][1] + " IPR families]";
        $(dropdown_name).options.add(new Option(option_string, options[i][0]));
    }
    // Set a decent minimum value
    $(dropdown_name).value = choice;
}

var boxes_ids = ['left_boxes','right_boxes'];
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
            checkbox.onchange = function(event){checkbox_changed(event,col)}

            var label = document.createElement('label')
            label.htmlFor = checkbox.id;
            label.appendChild(document.createTextNode(' ' + n + ' [' + flow[0][n] + ' genes] '));
            
            var container = $(boxes);
            container.appendChild(checkbox);// Fix for IE browsers, first append, then check.
            // Make sure other checkboxes are selected in each collumn
            if((i + col) % 2 === 1){
                checkbox.checked = true;
            } else {
                checkbox.disabled = true;
            }
            container.appendChild(label);
            if (i % 2 === 1 ){
                container.appendChild(document.createElement('br'));
            }
        }
        // Add a break after the last element so the refine button is always on a new line
        $(boxes).appendChild(document.createElement('br')); 
        // Move the button to the bottom
        var button = $(boxes + '_button');
        button.parentNode.appendChild(button);
    });  
}

function checkbox_changed(event,col){
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
    
    // Other groupings, other options.
    update_current_flow(chckbx.name,col,chckbx.checked);
    
    fill_in_dropdown();
}


var distribution = [];
// Current flow maps middle columns to a pair of values giving the left and right flow
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
        var biggest = Math.max(current_flow[target][0],current_flow[target][1]);
        if(!distribution[biggest]) {
            distribution[biggest] = 1;
        } else {
            distribution[biggest]++;
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
        if(selected){
            // Add the flow when something new is checked
            current_flow[target][col] += map[target];
        } else {
            // Remove it when the label is deselected
            current_flow[target][col] -= map[target];
        }
        var after = Math.max(current_flow[target][0],current_flow[target][1]);
        // Update the distribution if it changed
        if(before !== after){
            distribution[before]--;
            if(!distribution[after]) {
                distribution[after] = 1;
            } else {
                distribution[after]++;
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
var flow = [Object.create(null),Object.create(null)]; // a set with the in/outflow of each node

var max_flow = 0; //
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
            d[0] = source = "no label";
        }
        //Fill the list of names
        if(!(source in names)){
            per_label_mapping[source] = Object.create(null);
            names[source] = 1;
            names_list.push(source);
            column[source] = 0;
            flow[0][source] = +value;            
        } else {
            flow[0][source] += +value;
        }
        per_label_mapping[source][target] = value;

        // Generate a list of reverse mappings
        reverse_mapping.push([target,source,value]);
        // Keep track of the flow into each node
        var current_val = +value;
        if(target in flow[1]){
            flow[1][target] += current_val;
            current_val = flow[1][target];
        }else {
            flow[1][target] = +value;
            column[target] = 1;
        } 

        if(current_val > current_max){
            current_max = current_val;
        }
    });
    max_flow = current_max;
}

// checked_labels contain the names of all checked labels, per column
var checked_labels = [Object.create(null),Object.create(null)];
function get_checked_labels(){
    for(var col = 0, number_columns = boxes_ids.length; col < number_columns ; col++){
        $(boxes_ids[col]).getInputs('checkbox').forEach(function(chckbx){
            if(chckbx.checked){
                checked_labels[col][chckbx.name] = 1;
            }
        });
    }
}

function filter_links_to_use(){
    var links = [];   

    var min_flow = $(dropdown_name).options[$(dropdown_name).selectedIndex].value;

    mapping.forEach(function(s) {
        var left_flow = current_flow[s[1]][0];
        var right_flow = current_flow[s[1]][1];  
        if(s[0] in checked_labels[0] && 
          ((right_flow >= min_flow && left_flow > 0) || (left_flow >= min_flow && right_flow > 0))){
              links.push(copy_link(s));
        }
    });

    reverse_mapping.forEach(function(s) { 
        var left_flow = current_flow[s[0]][0];
        var right_flow = current_flow[s[0]][1];  
        if(s[1] in checked_labels[1] && 
          ((right_flow >= min_flow && left_flow > 0) || (left_flow >= min_flow && right_flow > 0))){
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
//console.log(good_links);
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
                          original_flow:flow[col][d]};
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
	    .text(function(d) { return d.source.name + (d.source.name in names ? " → " : " ← " ) + d.target.name + "\n" + format(d.value); });
      
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
	    .text(function(d) { return (d.name in descriptions? descriptions[d.name].desc : d.name)+ "\n" + format(d.value) + " / " + format(d.original_flow); });
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

