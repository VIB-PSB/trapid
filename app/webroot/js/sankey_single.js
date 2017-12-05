
// real_width is used for layout purposes
// var margin = {top: 1, right: 1, bottom: 6, left: 1},
var margin = {top: 1, right: 1, bottom: 6, left: 1},
    real_width = calculate_good_width(),
    width = real_width - margin.left - margin.right,
    height = calculate_good_height() - margin.top - margin.bottom;

function calculate_good_height(){
    return Math.min(window.innerHeight - 200, Math.log2(sankey_data.length + 1)* 200);   
}

// TODO: update to fit new TRAPID layout!
function calculate_good_width(){
    // return Math.min(window.innerWidth - margin.left - margin.right - 80,Math.log2(sankey_data.length + 1)* 300);
    return Math.min(window.innerWidth - margin.left - margin.right - 80 - 300, Math.log2(sankey_data.length + 1)* 300);
}

document.getElementById('sankey').setAttribute("style","display:block;");


////////// Behaviour of the refine button and fields ////////////

document.observe('dom:loaded', function(){
    calculate_distribution();
    if(sankey_data.length > 20){
        $('refinement').style.float = 'right';
        $('refinement').style.float = '0 0 50px 10px';        
        calculate_options();
        fill_in_dropdown();
    } else {
        hide_refinement();
    }

  draw_sankey();
});


function hide_refinement(){
    $('refinement').style.display = 'none';
}

var column = Object.create(null);
var no_gf_label = 'no family';
var distribution = [];
function calculate_distribution(){
    for(var i = 0; i < sankey_data.length; i++){
        if(sankey_data[i][1] === null){
          sankey_data[i][1] = no_gf_label;  
        }
        column[sankey_data[i][0]] = 0;
        column[sankey_data[i][1]] = 1;
        
        var fl = sankey_data[i][2];
        if(!distribution[fl]){
            distribution[fl] = 1;
        } else {
            distribution[fl]++;
        }
    }
}

var nodes_to_show = 20;
var options;
var minimum_size;
function calculate_options(){
    options = [];
    minimum_size = undefined;
    var total = 0;

    for(var i = distribution.length - 1; i > 0; i--){
        if(typeof distribution[i] != 'undefined' && distribution[i] !== 0){
            total += distribution[i];
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


var dropdown_id = 'min'
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
        var option_string = '>=' + options[i][0] + ' [' + options[i][1] + ' Gene families]';
        $(dropdown_id).options.add(new Option(option_string, options[i][0]));
    }

    $(dropdown_id).value = minimum_size;
}

////////// Sankey vizualization ////////////

// The format of the numbers when hovering over a link or node
var formatNumber = d3.format(",.0f"),
    format = function(d) { return formatNumber(d) + " gene" + (Math.floor(d) !== 1 ? 's' : ''); },
    color = d3.scale.category20();

var svg = d3.select("#sankey").append("svg")
	    .attr("width", width + margin.left + margin.right)
	    .attr("height", height + margin.top + margin.bottom);

 // (Re)draw the sankey diagram
function draw_sankey() {
    // Empty the old svg if it exists
    d3.select("svg").text('');

    var svg = d3.select("svg")
	    .append("g")
	    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");
   
    var sankey_data_copy =JSON.parse(JSON.stringify(sankey_data));
    var min_flow = 0;
    if($(dropdown_id).selectedIndex !== -1){
        min_flow = +$(dropdown_id).options[$(dropdown_id).selectedIndex].value;
    }
    var links = sankey_data_copy.filter(function(link){        
        return +link[2] >= min_flow;        
    });

    var graph = {"nodes" : [], "links" : []};
    
    var good_links = links;

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
	.text(function(d) { return create_hovertext(d); });

node.append("text")
	.attr("x", -6)
	.attr("y", function(d) { return d.dy / 2; })
	.attr("dy", ".35em")
	.attr("text-anchor", "end")
	.attr("transform", null)
	.text(function(d) { return create_node_title(d); })
	.filter(function(d) { return d.x < width / 2; })
	.attr("x", 6 + sankey.nodeWidth())
	.attr("text-anchor", "start");

    function dragmove(d) {
	    d3.select(this).attr("transform", "translate(" + d.x + "," + (d.y = Math.max(0, Math.min(height - d.dy, d3.event.y))) + ")");
	    sankey.relayout();
	    link.attr("d", path);
    }

    function create_hovertext(d){
        var hover_text = d.name + "\n";
        if(d.name in descriptions){
           hover_text += descriptions[d.name].desc + "\n";
        } 
        return hover_text + format(d.value);        
    }
  
    function create_node_title(d){
        var max_length = 40;
        if(d.name in descriptions){
            var descrip = descriptions[d.name].desc;               
            if(descrip.length > max_length + 5){
                descrip = descrip.substring(0,max_length - 3) + '...';
            }
            return descrip;
        } else {
            return d.name;
        }
    } 

}

