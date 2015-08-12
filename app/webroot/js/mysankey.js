
// real_width is used for layout purposes
var margin = {top: 1, right: 1, bottom: 6, left: 1},
    real_width = calculate_good_width(),    
    width = real_width - margin.left - margin.right,
    height = calculate_good_height() - margin.top - margin.bottom;

function calculate_good_height(){
    return Math.log1p(sankeyData.nodes.length)* 200;
    
}

function calculate_good_width(){
    return Math.min(window.innerWidth - margin.left - margin.right,Math.log2(sankeyData.nodes.length)* 200);
     
    //return Math.log2(sankeyData.nodes.length)* 200;
    
}



// Set the width of the div, so the buttons can float right.
document.getElementById('sankey').setAttribute("style","display:block;width:"+ real_width.toString()+"px");
document.getElementById('sankey').style.width=real_width.toString()+"px";


////////// Behaviour of the refine button and fields ////////////

/*  These functions disable the choice of a maximum below the set minimum and vice versa. */
function min_changed(){    
    for (var i = 0, len = $('max').options.length; i < len; i++) {
        $('max').options[i].disabled = i > len - 1 - $('min').value;
    }
}

function max_changed(){
    for (var i = 0, len = $('min').options.length; i < len; i++) {
        $('min').options[i].disabled = i > len - 1- $('max').value;
    }
}

document.observe('dom:loaded', function(){
  draw_sankey();
  $('min').observe('change', min_changed);
  $('max').observe('change', max_changed);
});
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
    var minimal = e.options[e.selectedIndex].text;
    var ma = document.getElementById("max");
    var maximal = ma.options[ma.selectedIndex].text; 
    
    //var sankey_data_copy = JSON.parse(JSON.stringify(sankeyData)); 
    var sankey_data_copy = {nodes : [], links : []};

    // 1. get a list of all gfs we need to remove from the viz
    var bad_gfs = [];
    for (var key in inflow_data) {
      if (inflow_data.hasOwnProperty(key)) {
            if(inflow_data[key] < minimal || inflow_data[key] > maximal){
                bad_gfs.push(key);
            }
        }
    }
    console.log(bad_gfs);
    // 2. Get the indices of these gfs
    var bad_indices = [];
    var good_nodes = [];
    for(var j = 0; j < sankeyData.nodes.length; j++) {
        // If the name appears in the list of bad gf names, add the index
        // BUG: if a label has the same name as a GF, it will be added to the bad_gfs
        if(bad_gfs.indexOf(sankeyData.nodes[j].name) > -1){
            bad_indices.push(j);
        }
        else {
            var w = {name:sankeyData.nodes[j].name};
            sankey_data_copy.nodes.push(w);
        }
    }    

    // 3. Remove all links with a target gf in the list.
    for(var j = 0; j < sankeyData.links.length; j++) {
        // If the target isn't bad, add the node
        if(!(bad_gfs.indexOf(sankeyData.links[j].target.name) > -1)){
            
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
    console.log(sankey_data_copy);

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

var node = svg.append("g").selectAll(".node")
	.data(sankey_data_copy.nodes)
	.enter().append("g")
	.attr("class", "node")
	.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; })
	.call(d3.behavior.drag()
	.origin(function(d) { return d; })
	.on("dragstart", function() { this.parentNode.appendChild(this); })
	.on("drag", dragmove));

node.append("rect")
	.attr("height", function(d) { return d.dy; })
	.attr("width", sankey.nodeWidth())
	.style("fill", function(d) { return d.color = color(d.name); })
	.style("stroke", function(d) { return d3.rgb(d.color).darker(2); })
	.append("title")
	.text(function(d) { return d.name + "\n" + format(d.value); });

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

