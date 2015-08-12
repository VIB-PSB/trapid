
// real_width is used for layout purposes
var margin = {top: 1, right: 1, bottom: 6, left: 1},
    real_width = 960,    
    width = real_width - margin.left - margin.right,
    height = 500 - margin.top - margin.bottom;

// The format of the numbers when hovering over a link or node
var formatNumber = d3.format(",.0f"),
    format = function(d) { return formatNumber(d) + " genes"; },
    color = d3.scale.category20();

// Set the width of the div, so the buttons can float right.
document.getElementById('sankey').setAttribute("style","display:block;width:"+ real_width.toString()+"px");
document.getElementById('sankey').style.width=real_width.toString()+"px";

var svg = d3.select("#sankey").append("svg")
	.attr("width", width + margin.left + margin.right)
	.attr("height", height + margin.top + margin.bottom)
	.append("g")
	.attr("transform", "translate(" + margin.left + "," + margin.top + ")");

var sankey = d3.sankey()
	.size([width, height])
	.nodeWidth(15)
	.nodePadding(10)
	.nodes(sankeyData.nodes)
	.links(sankeyData.links)
	.layout(32);

var path = sankey.link();

var link = svg.append("g").selectAll(".link")
	.data(sankeyData.links)
	.enter().append("path")
	.attr("class", "link")
	.attr("d", path)
	.style("stroke-width", function(d) { return Math.max(1, d.dy); })
	.sort(function(a, b) { return b.dy - a.dy; });

link.append("title")
	.text(function(d) { return d.source.name + " → " + d.target.name + "\n" + format(d.value); });

var node = svg.append("g").selectAll(".node")
	.data(sankeyData.nodes)
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

////////// Behaviour of the refine button and fields ////////////

function min_changed(){    
    for (var i = 0, len = $('max').options.length; i < len; i++) {
        $('max').options[i].disabled = i > len - $('min').value;
    }
}

function max_changed(){
    for (var i = 0, len = $('min').options.length; i < len; i++) {
        $('min').options[i].disabled = i > len - $('max').value;
    }
}

document.observe('dom:loaded', function(){
  $('min').observe('change', min_changed);
  $('max').observe('change', max_changed);
});




function draw_sankey() {
    var e = document.getElementById("min");
    var minimal = e.options[e.selectedIndex].text;
    var e = document.getElementById("max");
    var maximal = e.options[e.selectedIndex].text;    

}

