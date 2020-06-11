
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
    return Math.min(window.innerWidth - margin.left - margin.right - 80 - 300, Math.log2(sankey_data.length + 1)* 400);
}

document.getElementById('sankey').setAttribute("style","display:block;");

/* Run everything on page loading */
$(document).ready(function () {
    calculate_distribution();
    if(sankey_data.length > 20){
        $('#refinement').css("float", "right");
        $('#refinement').css("float", "0 0 50px 10px");
        calculate_options();
        fill_in_dropdown();
    } else {
        hide_refinement();
    }
    draw_sankey();
});


/* Legacy prototype JS code */
/* document.observe('dom:loaded', function(){
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
*/


////////// Behaviour of the refine button and fields ////////////
// TODO: set up CSS classes and toggle them (cleaner than modifying the CSS from here)
function hide_refinement(){
    $('#refinement').css("display", "none");
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


var dropdown_id = '#min';
function fill_in_dropdown(){
    // Clear the dropdown before adding new options
    $(dropdown_id).empty();

    // If there are no options, ask the user to select something
    if(options.length === 0){
        document.getElementById(dropdown_id.substring(1)).add(new Option("Please select labels", 0));
        // $(dropdown_id).options.add(new Option('Please select labels', 0));
        return;
    }
    // Fill in the dropdown
    for(var i = 0,len = options.length; i < len; i++){
        var option_string = '>=' + options[i][0] + ' [' + options[i][1] + ' Gene families]';
        document.getElementById(dropdown_id.substring(1)).add(new Option(option_string, options[i][0]));
    }

    $(dropdown_id).val(minimum_size);
}

////////// Sankey visualization ////////////

// The format of the numbers when hovering over a link or node
var formatNumber = d3.format(",.0f"),
    format = function(d) { return formatNumber(d) + " transcript" + (Math.floor(d) !== 1 ? 's' : ''); },
    color = d3.scale.category20();

var svg = d3.select("#sankey").append("svg")
	    .attr("width", width + margin.left + margin.right)
	    .attr("height", height + margin.top + margin.bottom);

 // (Re)draw the sankey diagram
function draw_sankey() {
    // Empty the old svg if it exists
    d3.select("svg").text('');

    // Remove old tooltips
    $('.d3-tip').remove();

    var svg = d3.select("svg")
	    .append("g")
	    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");


    // Tooltip based on this snippet: http://bl.ocks.org/FabricioRHS/80ef58d4390b06305c91fdc831844009
    // Position offset
    var linkTooltipOffsetX = 90;
    var linkTooltipOffsetY = 100;
    var nodeTooltipOffsetX = 30;
    var nodeTooltipOffsetY = 33;

    // Initialize tooltips
    var tipLink = d3.tip()
        .attr('class', 'd3-tip d3-tip-link');
    var tipNode = d3.tip()
        .attr('class', 'd3-tip d3-tip-node');

    svg = d3.select('svg').call(tipLink).call(tipNode);

    // TODO: return content as array and deal with formatting in this function?
    tipLink.html(function(d) {
        var tooltipContent = d3.select(this).select("hovertext").text();
        return tooltipContent;
    });

    tipNode.html(function(d) {
        var tooltipContent = d3.select(this).select("hovertext").text();
        var html = tooltipContent;
        html += "<br><span class='text-justify d3-tip-footer'>Drag to move and click to view.</span>";
        return html;
    });



    var sankey_data_copy =JSON.parse(JSON.stringify(sankey_data));
    var min_flow = 0;
    var n_options = $(dropdown_id + ' option').length;  // Should not be zero if dropdown was populated
    if(n_options != 0 && parseInt($(dropdown_id + " option:selected").val()) !== -1){
        min_flow = +$(dropdown_id + " option:selected").val();
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
                           href: urls[col].replace(place_holder,d).replace('GO:','GO-'),
                           nodeId: "node_" + i.toString() };
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

link.append("hovertext")
	.text(function(d) { return "<span class='d3-tip-title'>" + d.source.name + "<br>→ " + d.target.name + "</span><br>  \n" + format(d.value); });

    // Add link tooltips
    link.on('mousemove', function(event) {
        tipLink.style("left", function () {
            var left = (Math.max(d3.event.pageX - linkTooltipOffsetX, 10));
            left = Math.min(left, window.innerWidth - $('.d3-tip').width() - 20);
            return left + "px";
        })
            .style("top", function() { return (d3.event.pageY - linkTooltipOffsetY) + "px" })
    })
        .on('mouseover', tipLink.show)
        .on('mouseout', tipLink.hide);

// Work around to make something dragable also clickable
// From http://jsfiddle.net/2EqA3/3/

var node = svg.append("g").selectAll(".node")
	.data(graph.nodes)
	.enter().append("g")
	.attr("class", "node")
	.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; })
	.call(d3.behavior.drag()
	.origin(function(d) { return d; })
	.on("dragstart", function(dragged) {
        d3.event.sourceEvent.stopPropagation();
        // this.parentNode.appendChild(this);
        svg.selectAll(".node").sort(function(a, b) {
            var toFront = dragged.nodeId;
            return (a.nodeId === toFront) - (b.nodeId === toFront);
        });
        d3.select(this).classed("dragged", true);
    })
	.on("drag", dragmove)
    .on('dragend', function(){
        d3.select(this).classed("dragged", false);
    }))
    .on("click", highlightCurrentNode)
    .on("dblclick", click)
    .on('mousemove', function(event) {
        var nodeFillColor = d3.select(this).select("rect").style("fill");
        tipNode
            .style("left", function () {
                var left = (Math.max(d3.event.pageX - $('.d3-tip-node').width() - nodeTooltipOffsetX, 10));
                left = Math.min(left, window.innerWidth - $('.d3-tip-node').width() - 20);
                return left + "px"; })
            .style("top", (d3.event.pageY - $('.d3-tip-node').height() - nodeTooltipOffsetY) + "px")
            .style("border", function() { return nodeFillColor + ' solid 1px'; })

    })
    .on('mouseover.tooltip', tipNode.show)
    .on('mouseout.tooltip', tipNode.hide)
    .on("mouseover.links", highlightConnectedLinks)
    .on("mouseout.links", resetConnectedLinks);


    function highlightConnectedLinks(d) {
        // Add `connected` class to the link if it is connected to the node
        link.classed("connected", function(l) {
            if (l.source.name == d.name || l.target.name == d.name) {
                return true;
            }
            else
                return false;
        });
    }

    // Remove `connected` class from all links
    function resetConnectedLinks(d) {
        link.classed("connected", false);
    }


    function highlightCurrentNode(d) {
        if(d3.event.defaultPrevented) {
            return;
        }
        // Highlight current node
        var node = d3.select(this);
        var isHighlighted = node.classed("highlighted");
        node.classed("highlighted", !isHighlighted);
        // Get names of currently highlighted nodes
        var highlightedNodes = [];
        svg.selectAll(".node.highlighted").each(function(n) { highlightedNodes.push(n.name) });
        // Toggle `highlighted` class to connected links as appropriate
        link.each(function (l) {
            var currentLink = d3.select(this);
            if((l.source.name === d.name || l.target.name === d.name) && (!highlightedNodes.includes(l.source.name) || !highlightedNodes.includes(l.target.name))) {
                currentLink.classed("highlighted", !isHighlighted);
            }
        });
    }


    function click(d) {
  if (d3.event.defaultPrevented)
    { return;}
  window.open(d.href,'_blank');
}

node.append("rect")
	.attr("height", function(d) { return d.dy; })
	.attr("width", sankey.nodeWidth())
	.style("fill", function(d) { return d.color = color(d.name); })
	.style("stroke", function(d) { d.color = color(d.name); })
	// .style("stroke", function(d) { return d3.rgb(d.color).darker(2); })
	.append("hovertext")
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
        var hover_text = "<span class='d3-tip-title'>" + d.name + "</span><br>\n";
        if(d.name in descriptions){
           hover_text += descriptions[d.name].desc + "<br>\n";
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
