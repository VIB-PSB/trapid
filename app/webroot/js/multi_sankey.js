"use strict";
/* 
 * TODO: first just filter on the labels and middle amount
 * Stage 2 : create a second distribution to use as the second filter
 * Stage 3 : Get single mode working
 * If logging in doesn't work, change cookie path back!
 */

var null_label = 'no label';
// When no labels are checked we fall back to comparing the last mapping
var single_mode = false;

document.observe('dom:loaded', function(){
  process_data();
  add_checkboxes();
  calculate_current_flow();
  fill_in_dropdown_bounds();
  add_dropdown_limits();
  
  
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
var min_names = ['middle_min','right_min'];
var max_names = ['middle_max','right_max'];
function add_dropdown_limits(){
/*  These functions disable the choice of a maximum below the set minimum and vice versa. */
  for (var i = 0, len = min_names.length; i < len; i++) {
      // .bindAsEventListener is necessary to pass the arguments
      $(min_names[i]).observe('change', 
        bound_changed.bindAsEventListener(this, min_names[i],max_names[i])
        );        
      $(max_names[i]).observe('change', 
        bound_changed.bindAsEventListener(this, max_names[i],min_names[i])
       );
  }
}

function bound_changed(event, current, sibling){
    for (var i = 0, len = $(sibling).options.length; i < len; i++) {
        $(sibling).options[i].disabled = i > len - 1- $(current).value;
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
            checkbox.type = "checkbox";
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
            // check some values
            if((i + col) % 2 === 1){
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
var flow = [Object.create(null),Object.create(null),Object.create(null)];
var maxes = [];
var collumn = {};
//var names = Object.create(null); // set of different labels
var names_list = [];

function process_data(){
    //TODO update to current situation
    // We assume that the the in/outflow for the middle collum is equal.
    first_mapping.forEach(function (d) {
        var source = d[0];
        var target = d[1];
        var value = +d[2];
    
      if(source === null){
        d[0] = source = null_label;
      }
      if(d[0] in flow[0]){
        flow[0][d[0]] += +d[2];
      } else {
        names_list.push(d[0]);
        flow[0][d[0]] = +d[2];
        collumn[d[0]] = 0;
      }
      if(d[1] in flow[1]){
        flow[1][d[1]] += +d[2];
      }else {
        flow[1][d[1]] = +d[2];
        collumn[d[1]] = 1;
      }        
    });

    for(var i=0, len=second_mapping.length; i < len; i++){
      var d = second_mapping[i];
      if(d[1] in flow[2]){
        flow[2][d[1]] += +d[2];
      } else {
        flow[2][d[1]] = +d[2];
        collumn[d[1]] = 2;
      }
      
    }
}

var distribution = [];
var single_distribution = [];
// current_flow maps names to the current inflow, twice
// [{'IPR01':4,'IPR02':8,...},{'HOM02':4,'HOM03':8,...}]

var first_flow = Object.create(null);
var second_flow = Object.create(null);
function calculate_current_flow(){
    /* caculate the current flow in a waterfall fashion (pun not intended)
     * First the flow into the first column is determined, we already know the cutoff point
     * So then the second flow is calculated according to the nodes we show at first.
     */
    
    // Label to first col flow
    for(var label in checked_labels){
        var map = per_label_mapping[label];
        for(var target in map){
            if(!(target in current_flow)){
                first_flow[target] = 0;
            }
            first_flow[target] += map[target];
        }
    }

    // Fill the first distribution array
    for(var target in first_flow){
        // Only take into account the elements that will be shown
        var flow = first_flow[target];
            if(!distribution[flow]) {
                distribution[flow] = 1;
            } else {
                distribution[flow]++;
            }
        }
    }   


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



//TODO use distributions here
// Dropdowns offer number_of_choices choices, step_size between them
function fill_in_dropdown_bounds(){

    var number_of_choices = 50;

    for(var i=0, len=min_names.length; i < len; i++){
        var step_size = Math.round(maxes[i]/number_of_choices);
        for(var j=0; j < number_of_choices - 1; j++){
            $(min_names[i]).options.add(new Option(j*step_size,j))
            $(max_names[i]).options.add(new Option(maxes[i] - j*step_size,j))
        }
        $(min_names[i]).options.add(new Option(maxes[i],number_of_choices - 1));
        
        // Also select a hopefully good value here.
        switch(i){
            case 0:
                $(min_names[i]).value = Math.round(Math.log(first_mapping.length));
                break;
            case 1:
                $(min_names[i]).value = Math.round(Math.log(first_mapping.length + second_mapping.length));
                break;
            case 2:
                $(min_names[i]).value = Math.round(Math.log(second_mapping.length));
                break;
             default:
                $(min_names[i]).value = 0;
        }
        $(max_names[i]).options.add(new Option(0,number_of_choices - 1));
    }
}


function filter_links_to_use(){
    var links = [];
  
    var min_in_flow = $(min_names[0]).options[$(min_names[0]).selectedIndex].text;
    var max_in_flow = $(max_names[0]).options[$(max_names[0]).selectedIndex].text;
    first_mapping.forEach(function(s) { 
        var out_node_flow = +flow[0][s[0]];
        var in_node_flow = +flow[1][s[1]];
        if(s[0] in checked_labels &&
           in_node_flow >= min_in_flow &&
           in_node_flow <= max_in_flow ){
            links.push(copy_link(s));
           }
    });
    var min_out_flow = $(min_names[1]).options[$(min_names[1]).selectedIndex].text;
    var max_out_flow = $(max_names[1]).options[$(max_names[1]).selectedIndex].text;
    second_mapping.forEach(function(s) { 
        var in_node_flow = +flow[1][s[0]];
        var out_node_flow = +flow[2][s[1]];
        
        if(out_node_flow >= min_in_flow &&
           out_node_flow <= max_in_flow &&
           in_node_flow >= min_out_flow &&
           in_node_flow <= max_out_flow ){
            links.push(copy_link(s));
           }
        else{
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
       var col = collumn[d];
       graph.nodes[i] = { name: d.replace(/^\d+_/g,''),
                          href: urls[col].replace(place_holder,d),
                          original_flow:flow[col][d] };
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

