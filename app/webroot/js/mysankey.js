
// real_width is used for layout purposes
var margin = {top: 1, right: 1, bottom: 6, left: 1},
    real_width = calculate_good_width(),    
    width = real_width - margin.left - margin.right,
    height = calculate_good_height() - margin.top - margin.bottom;

function calculate_good_height(){
    return Math.min(window.innerHeight - 200, Math.log1p(sankeyData.nodes.length)* 150);   
}

function calculate_good_width(){
    return Math.min(window.innerWidth - margin.left - margin.right - 80,Math.log2(sankeyData.nodes.length)* 200);
}

// Set the width of the div, so the buttons can float right.
document.getElementById('sankey').setAttribute("style","display:block;width:"+ real_width.toString()+"px");
document.getElementById('sankey').style.width=real_width.toString()+"px";


////////// Behaviour of the refine button and fields ////////////

document.observe('dom:loaded', function(){
  draw_sankey();
  /*  These functions disable the choice of a maximum below the set minimum and vice versa. */
  $('min').observe('change', function() {
    bound_changed('min','max');
  });
  $('max').observe('change', function() {
    bound_changed('max','min');
  });
  $('left_min').observe('change', function() {
    bound_changed('left_min','left_max');
  });
  $('left_max').observe('change', function() {
    bound_changed('left_max','left_min');
  });
});

function bound_changed(current, sibling){
    for (var i = 0, len = $(sibling).options.length; i < len; i++) {
        $(sibling).options[i].disabled = i > len - 1- $(current).value;
    }
}


////////// Sankey vizualization ////////////

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


    var e = document.getElementById("min");
    var minimal_inflow = e.options[e.selectedIndex].text;
    var ma = document.getElementById("max");
    var maximal_inflow = ma.options[ma.selectedIndex].text; 

    var e_o = document.getElementById("left_min");
    var minimal_outflow = e_o.options[e_o.selectedIndex].text;
    var ma_o = document.getElementById("left_max");
    var maximal_outflow = ma_o.options[ma_o.selectedIndex].text; 
    var sankey_data_copy = {nodes : [], links : []};

    // 1. create a list of all nodes we need to remove from the viz
    var bad_indices = {};
    for (var key in inflow_data) {
      if (inflow_data.hasOwnProperty(key)) {
            if(inflow_data[key] < minimal_inflow || inflow_data[key] > maximal_inflow){
                //bad_indices[key] = true;
            }
        }
    }    

    for (var key in outflow_data) {
        if (outflow_data.hasOwnProperty(key)) {
            if(outflow_data[key] < minimal_outflow || outflow_data[key] > maximal_outflow){
                //bad_indices[key] = true;
            }
        }
    }

    // 2 We check for possible orphans either on the source or target side
    // We check for this in one pass.
    // Go over all links, if the link is good, meaning both source and target aren't bad, the involved nodes are added to the good node set
    var good_nodes = {};
    for(var j = 0; j < sankeyData.links.length; j++) {
        // If the target isn't in the list of the bad_indices add it.
        if(!(sankeyData.links[j].target in bad_indices) && !(sankeyData.links[j].source in bad_indices)){
            good_nodes[sankeyData.links[j].source] = true;
            good_nodes[sankeyData.links[j].target] = false;
        }
    }
    
    for(index in good_nodes){
        console
        var w = {name:sankeyData.nodes[index].name,
                     href:sankeyData.nodes[index].href,
                     original_flow:good_nodes[index]?outflow_data[index]:inflow_data[index]};
        sankey_data_copy.nodes.push(w);
    } 
    
    
    // 3. Remove all links with a bad index as source or target.
    for(var j = 0; j < sankeyData.links.length; j++) {
        // If the target isn't bad, add the link
        if(!(sankeyData.links[j].target in bad_indices) && !(sankeyData.links[j].source in bad_indices)){
            
            // indices might have changed with the removal of nodes.
            link = {"value":sankeyData.links[j].value};
            var found = 0;
            for (var i=0; i < sankey_data_copy.nodes.length; i++) {
                
                if (sankey_data_copy.nodes[i].name === sankeyData.nodes[sankeyData.links[j].target].name) {
                    link['target'] = i;
                    found++;
                    if(found === 2) break;
                    continue;
                }
                if (sankey_data_copy.nodes[i].name === sankeyData.nodes[sankeyData.links[j].source].name) {
                    link['source'] = i;
                    found++;
                    if(found === 2) break;
                }
            }
            if(found === 2)
                sankey_data_copy.links.push(link);
        }
    }    

var sankey = d3.sankey()
	.size([width, height])
	.nodeWidth(15)
	.nodePadding(10)
	.nodes(sankey_data_copy.nodes)
	.links(sankey_data_copy.links)
	.layout(32);

var path = sankey.link();

var link = svg.append("g").selectAll(".link")
	.data(sankey_data_copy.links)
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
	.data(sankey_data_copy.nodes)
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
//console.log(sankey_data_copy);
    function dragmove(d) {
	    d3.select(this).attr("transform", "translate(" + d.x + "," + (d.y = Math.max(0, Math.min(height - d.dy, d3.event.y))) + ")");
	    sankey.relayout();
	    link.attr("d", path);
    }   

}

