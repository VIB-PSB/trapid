function DirectedAcyclicGraphMinimap(DAG, options) {
    /*
     * Specifies the draw function for the Minimap class, for a DAG
     */
    return Minimap(options).draw(function (d) {
        var edgedata = d3.select(d).selectAll(".edge.visible").data();
        var nodedata = d3.select(d).selectAll(".node.visible").data();

        var edges = d3.select(this).selectAll(".edge").data(edgedata);
        edges.enter().append("path").attr("class", "edge");
        edges.exit().remove();

        var nodes = d3.select(this).selectAll(".node").data(nodedata);
        nodes.enter().append("g").attr("class", "node").append("rect");
        nodes.selectAll("rect").attr("x", function (d) { return -(d.bbox.width / 2); })
            .attr("y", function (d) { return -(d.bbox.height / 2); })
            .attr("width", function (d) { return d.width; })
            .attr("height", function (d) { return d.height; });
        nodes.exit().remove();

        // Set positions of nodes and edges
        nodes.attr("transform", DAG.nodeTranslate);
        edges.attr("d", DAG.splineGenerator);
    });
}

function Minimap(options) {
    var width   = constant(100),
        height  = constant(100),
        x       = constant(0),
        y       = constant(0);

    function minimap(selection) {
        selection.each(function (data) {
            // Select the svg element that we draw to or add it if it doesn't exist
            var svg = d3.select(this).selectAll("svg").data([data]);
            var firsttime = svg.enter().append("svg");
            firsttime.append("rect")
                .attr("class", "background")
                .attr("width", "100%")
                .attr("height", "100%");
            var contents = firsttime.append("svg").attr("class", "minimap");
            contents.append("g").attr("class", "contents")
            contents.append("rect")
                .attr("class", "viewfinder")

            // Size the list as appropriate
            svg.attr("width", width.call(this, data));
            svg.attr("height", height.call(this, data));
            svg.attr("x", x.call(this, data));
            svg.attr("y", y.call(this, data));

            // Draw the contents of the minimap
            draw.call(svg.select('.contents').node(), data);

            // Zoom the minimap to the extent of the contents
            var curbbox = svg.select('.contents').node().getBBox();
            var bbox = { x: curbbox.x - 50, y: curbbox.y - 50, width: curbbox.width + 100, height: curbbox.height + 100 };
            svg.select(".minimap").attr("viewBox", bbox.x + " " + bbox.y + " " + bbox.width + " " + bbox.height);
            contents.select(".viewfinder").attr("x", bbox.x).attr("y", bbox.y).attr("width", bbox.width).attr("height", bbox.height);
        });
    }

    var draw = function (d) {
        // Default - copy verbatim
        var ctx = this;
        d3.selectAll(d3.select(d).select("svg").node().childNodes).each(function (d) {
            ctx.appendChild(this.cloneNode(true));
        });
    }

    minimap.draw = function (_) { if (!arguments.length) return draw; draw = _; return minimap; }
    minimap.width = function (_) { if (!arguments.length) return width; width = constant(_); return minimap; }
    minimap.height = function (_) { if (!arguments.length) return height; height = constant(_); return minimap; }
    minimap.x = function (_) { if (!arguments.length) return x; x = constant(_); return minimap; }
    minimap.y = function (_) { if (!arguments.length) return y; y = constant(_); return minimap; }

    return minimap;
}