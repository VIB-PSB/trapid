"use strict";

////////// Behaviour of the refine button and fields ////////////
var min_names = ['middle_min'];
var max_names = ['middle_max'];

document.observe('dom:loaded', function(){
  process_data();
  fill_in_dropdown_bounds();
  add_bound_checking_dropdown();
  add_checkboxes();
  draw_sankey();
});

function bound_changed(event, current, sibling){
    for(var i = 0, len = $(sibling).options.length; i < len; i++) {
        $(sibling).options[i].disabled = i > len - 1- $(current).value;
    }
}

function add_bound_checking_dropdown(){
  /* These functions disable the choice of a maximum below the set minimum and vice versa. */
  for(var i = 0, len = min_names.length; i < len; i++) {
      // .bindAsEventListener is necessary to pass the arguments
      $(min_names[i]).observe('change', 
        bound_changed.bindAsEventListener(this, min_names[i],max_names[i])
      );
      $(max_names[i]).observe('change',
        bound_changed.bindAsEventListener(this, max_names[i],min_names[i])
      );
  }
}

var boxes_ids = ['left_boxes','right_boxes'];
function add_checkboxes(){
    names_list.sort();
    boxes_ids.forEach(function(boxes){
        for (var i = 0, len = names_list.length; i < len; ++i) {
            var n = names_list[i];
            var checkbox = document.createElement('input');
            checkbox.type = "checkbox";
            checkbox.name = n;
            checkbox.value = n;
            checkbox.id = boxes + n;            

            var label = document.createElement('label')
            label.htmlFor = checkbox.id;
            label.appendChild(document.createTextNode(' ' + n));
            
            var container = $(boxes);
            container.appendChild(checkbox);
            checkbox.checked = true; // Fix for IE browsers
            container.appendChild(label);
            container.appendChild(document.createElement('br'));
            

        }
        // Move the button to the bottom
        var button = $(boxes + '_button');
        button.parentNode.appendChild(button);
    });
}


////////// Sankey vizualization ////////////

// Data Processing 
// The mappings contain their data as [[source1,target1,value1],[source2,target1,value2],...]
// The processed data is put into global variables so other functions can read the computed values

var collumn = Object.create(null);
var names = Object.create(null); // Set of different labels
var name_list = [];
var flow = Object.create(null); // a set with the in/outflow of each node
var max_flow = 0; //
var reverse_mapping = [];

function process_data(){
    // We assume that the the in/outflow for the middle collum is equal.
    var current_max = 0;
    mapping.forEach(function (d) {
        //Fill the list of names
        if(d[0] === "null"){
            d[0] = "no_label"   
        }
        if(!(d[0] in names)){
            names[d[0]] = 1;
            name_list.push[d[0]];
            collumn[d[0]] = 0;
        }
        // Generate a list of reverse mappings
        reverse_mapping.push([d[1],d[0],d[2]]);
        // Keep track of the flow into each node
        var current_val = +d[2];
        if(d[1] in flow){
            flow[d[1]] += current_val;
            current_val = flow[d[1]];
        }else {
            flow[d[1]] = +d[2];
            collumn[d[1]] = 1;
        } 

        if(current_val > current_max){
            current_max = current_val;
        }
    });
    max_flow = current_max;    
}

// Dropdowns offers choices according to x^2
function fill_in_dropdown_bounds(){

    for(var i = 0, len = min_names.length; i < len; i++){     
        var powers = []
        for(var j=1; j*j < max_flow; j++){            
            $(min_names[i]).options.add(new Option(j*j,j));
            powers.push(j*j);            
        }
        powers.reverse();
         if(Math.pow(Math.round(Math.sqrt(max_flow)),2) !== max_flow){
            $(max_names[i]).options.add(new Option(max_flow,1));
            $(min_names[i]).options.add(new Option(max_flow,powers.length));
        }
        for(var j=0, len2 = powers.length; j < len2; j++){
            $(max_names[i]).options.add(new Option(powers[j],j+1))
        }        

        // Also select a hopefully good value here.        
        $(min_names[i]).value = Math.round(Math.log10(mapping.length));
    }  
}


function filter_links_to_use(){
    var links = [];   
    var good_labels = [Object.create(null),Object.create(null)];

    for(var collumn = 0, number_collumns = boxes_ids.length; collumn < number_collumns ; collumn++){
        $(boxes_ids[collumn]).getInputs('checkbox').forEach(function(chckbx){
            if(chckbx.checked){
                good_labels[collumn][chckbx.name] = 1;
            }
        });
    }

    var min_flow = $(min_names[0]).options[$(min_names[0]).selectedIndex].text;
    var max_flow = $(max_names[0]).options[$(max_names[0]).selectedIndex].text;
    mapping.forEach(function(s) { 
        var node_flow = flow[s[1]];
        if(s[0] in good_labels[0] && node_flow >= min_flow && node_flow <= max_flow ){
            links.push(copy_link(s));
        }
    });

    reverse_mapping.forEach(function(s) { 
        if(s[1] in good_labels[1]){
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
                          original_flow:flow[col]};
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
        //TODO put the description in the above line
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

