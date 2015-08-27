"use strict";

/* Global variables: */

// label to display instead of null.
var null_label = 'no label';
var no_gf_label = 'no family';
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

var distribution = [];
var single_distribution = [];
// current_flow maps names to the current inflow, twice
// [{'IPR01':4,'IPR02':8,...},{'HOM02':4,'HOM03':8,...}]


// Set of nodes in the middle collumn, as determined by the left checkboxes and the middle filters
var middle_nodes = Object.create(null);
// flow from middle nodes to the right collumn {1_HOM000248: 50, 1_HOM001148: 108, 1_HOM004574: 21, 1_HOM000586: 84, 1_HOM002222: 60…}
var flow = Object.create(null);
//
var first_links = Object.create(null);
// set of p values to fill the dropdown with.
var p_values = Object.create(null);
var minimum_size;
var options = [];

/* Html identifiers used to select on */
var hidden_id = 'hidden';
var type_id = 'type';
var p_val_id = 'pvalue';
var dropdown_id = 'right_min';
var normalization_id = 'normalize';

var boxes = 'left_boxes';
var col_classes = ['left_col','right_col'];

/* Globals defined in sankey_enriched 

    enrichedIdents : [p_val][identifier] = hidden
        ['0.1'][GO:0000271] =  "1"
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

document.observe('dom:loaded', function(){
  // Hide the option to filter on type when we're not dealing with GO.
  if(!GO){hide_type();}    
  process_data();
  add_checkboxes();
  fill_in_p_values();
  determine_current_links();
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
    return Math.min(window.innerHeight - 200, Math.log2(2*Object.keys(transcriptIdent).length)* 200);   
}

function calculate_good_width(){
    return Math.min(window.innerWidth - margin.left - margin.right - 80,Math.log2(2*Object.keys(transcriptIdent).length)* 200);
}


////////// Behaviour of the refine button and fields ////////////

function hide_type(){
    // Parent because otherwise the label stays visible.
    $(type_id).parentElement.style.display = 'none';
}


function fill_in_dropdown(){
    // Clear the dropdown before adding new options
    $(dropdown_id).update();
    
    // If there are no options, ask the user to select something
    if(options.length === 0){
        $(dropdown_id).options.add(new Option('Please select labels', 0));
        return;
    }
    // Fill in the dropdown
    for(var i = 0,len = options.length; i < len; i++){
        var option_string = '>=' + options[i][0] + ' [' + options[i][1] + ' ' + dropdown_filter_name[1]+ ']';
        $(dropdown_id).options.add(new Option(option_string, options[i][0]));
    }

    $(dropdown_id).value = minimum_size;
}


function fill_in_p_values(){
    var list = Object.keys(enrichedIdents);
    list.sort(function(a, b) {
        return Number(a) - Number(b);
    }); // Sorts correctly, even with scientific notation
    for(var i = 0, len = list.length; i < len; i++){
      $(p_val_id).options.add(new Option(list[i], i));
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
        checkbox.onchange = function(event){checkbox_changed(event);};

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
            checked_labels[n] = 1;
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
var gftranscript = Object.create(null);
function process_data(){
    names_list = Object.keys(transcriptLabelGF);
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

    return;
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
            per_label_mapping[source] = [];
            names[source] = 1;
            column[source] = 0;
        }
        //Fill the set of p values
        p_values[p_val] = 1;

        column[target] = 1;
        per_label_mapping[source].push(d);    
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
var current_links;
var second_links_temp;
var first_links_temp;
function determine_current_links(){
    middle_nodes = Object.create(null);
    first_links_temp = Object.create(null);
    second_links_temp = Object.create(null);
    
    current_links = [];

    var p_value = $(p_val_id).options[$(p_val_id).selectedIndex].text;
    var type = $(type_id).options[$(type_id).selectedIndex].text;
    var show_hidden = $(hidden_id).checked;

    for(var label in checked_labels){        
        if(!(label in first_links_temp)){
            first_links_temp[label] = Object.create(null);
            column[label] = 0;
        }

        //var transcripts = transcriptLabelGF[label];
        for(var transcript in transcriptLabelGF[label]){
            //if(!(transcript in transcriptIdent)) These are transcripts with no GO
            for(var identifier in transcriptIdent[transcript]){
                // Is the GO term enriched for this p_value? (this check is unnecessary, the second if catches this case too)
                if(!(identifier in enrichedIdents[p_value])){
                    continue;
                }
                if(!show_hidden && enrichedIdents[p_value][identifier] === "1"){
                    continue;
                }
                if(GO && type !== "All" && type !== descriptions[identifier].type){
                    continue;
                }
                // create or increment this link.
                if(!(identifier in first_links_temp[label])){
                    first_links_temp[label][identifier] = 1;
                    column[identifier] = 1;
                } else {
                    first_links_temp[label][identifier]++;
                }

                // Keep track of what the middle nodes are made of
                if(!(identifier in middle_nodes)){
                    middle_nodes[identifier] = Object.create(null);
                }
                middle_nodes[identifier][transcript] = 1;

                // create or increment this link in the second mapping.
                if(!(identifier in second_links_temp)){
                    second_links_temp[identifier] = Object.create(null);
                }
                var gf = transcriptLabelGF[label][transcript];
                //console.log(gf);
                if(! (gf in second_links_temp[identifier])){
                    second_links_temp[identifier][gf] = 1;
                    column[gf] = 2;
                } else {
                    second_links_temp[identifier][gf]++;
                }               
            }                    
        }
    }
}   


function calculate_current_flow(){
    // reset data
    flow = Object.create(null);
    distribution = [];

    determine_current_links();

    for(var node in second_links_temp){        
        var map = second_links_temp[node];
         for(var target in map){
            console.log
            if(!(target in flow)){
                flow[target] = 0;
             } 
             flow[target] += map[target];
        }
    }

    // Fill the second distribution array
    for(var trgt in flow){
        var fl = flow[trgt];
        if(!distribution[fl]){
            distribution[fl] = 1;
        } else {
            distribution[fl]++;
        }
    }
    calculate_options();
}


var links;
var left_nodes;
var right_middle_nodes;
function filter_links_to_use(){
    links = [];
    right_middle_nodes = Object.create(null);
    left_nodes = Object.create(null);
    var second_min_flow = $(dropdown_id).options[$(dropdown_id).selectedIndex].value;

    for(var ident in second_links_temp){
        var idengf = second_links_temp[ident]
        for(var gf in idengf){
            var fl = flow[gf];
             if(fl >=second_min_flow ){
                links.push([ident, gf, idengf[gf]]);
                right_middle_nodes[ident] = 1;
                left_nodes[gftranscript[gf]] = 1;
            }
        }
    }

    for(var lbl in first_links_temp){
        var lblIden = first_links_temp[lbl]
        for(var iden in lblIden){
            if(iden in right_middle_nodes){
                links.push([lbl, iden, lblIden[iden]]);
            }
        }
    }

/*
    second_mapping.forEach(function(s) { 
        var fl = flow[s[1]] ? flow[s[1]]: 0;
        if(s[0] in middle_nodes && fl >= second_min_flow){
            links.push(copy_link(s));
            right_middle_nodes[s[0]] = 1;
           }        
    });

    var p_value = $(p_val_id).options[$(p_val_id).selectedIndex].text;
    var type = $(type_id).options[$(type_id).selectedIndex].text;
    var show_hidden = $(hidden_id).checked;
    first_mapping.forEach(function(s) {

        if(s[0] in checked_labels && p_value === s[4]){
            // If it's a hidden link only show it when show hidden is true.
            if(!show_hidden && s[3] === "1"){
                return;
            }
            // GO can filter on type, it it's not all, it has to match the selected type
            if(GO && type !== "All" && type !== descriptions[s[1]].type){
                return;
            }
            if(s[1] in right_middle_nodes){
                links.push(copy_link(s));
            }
        }
    });    */
    return links;
}

//TODO unused function
function copy_link(link,first_mapping){
    return [link[0],link[1],link[2]];    
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
    //var good_links = current_links;
    if($(normalization_id).checked){
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
                          href: urls[col].replace(place_holder,d).replace('GO:','GO-')};
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
            if(d.name in label_counts){
                return d.name + "\n" + label_counts[d.name] + " genes";
            } else {
                var gf_prefix = exp_id + '_';
                return d.name + "\n" + flow[gf_prefix + d.name] + " genes";
            }
        }

        // The hovertext varies depending on the normalization used
        function create_link_hovertext(d){
            var arrow = " → "; 
            var hover_string = d.source.name + arrow + d.target.name + "\n";
            if($(normalization_id).checked){
                hover_string += parseFloat(d.value).toFixed(2) + '% of genes shown';
            } else {
                hover_string += d.value + ' genes';
            }            
            return  hover_string ; 
        }
}

