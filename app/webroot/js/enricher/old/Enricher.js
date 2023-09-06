// lightweight is an optional argument that will try to draw the graph as fast as possible
function EnricherGo(attachPoint, json, options) {
    options = options || {};

    options.width = options.width || '100%';
    options.height = options.height || '100%';

    options.animationEnabled = options.animationEnabled || true;    // This would always be 'true'...
    // options.animationEnabled = options.animationEnabled && true;  // This should work
    options.animationDuration = options.animationDuration || 0;

    options.levelSeparation = options.levelSeparation || 500;
    options.edgeSeparation = options.edgeSeparation || 5;
    options.nodeSeparation = options.nodeSeparation || 75;

    options.namespace = options.namespace || undefined;
    options.nodeSize = options.nodeSize || function (n) {
        return 100;
    };
    options.nodeColorScale = options.nodeColorScale || d3.interpolateRdYlGn;
    options.nodeOpacity = options.nodeOpacity || function (n) {
        return 1;
    };
    options.nodeValue = options.nodeValue || function (n) {
        return 1;
    };
    options.nodeLabelPosition = options.nodeLabelPosition || 'bottom';

    options.donutGraphOuterWidth = options.donutGraphOuterWidth || 20;
    options.donutGraphInnerWidth = options.donutGraphInnerWidth || 10;
    options.donutGraphColorScale = options.donutGraphColorScale || d3.interpolateRdYlGn;
    options.donutGraphValue = options.donutGraphValue || function (n) {
        return 1;
    };

    options.sidebarWidth = options.sidebarWidth || '20%';
    // options.sidebarMiniMapShow = options.sidebarMiniMapShow || true;    // This would always be 'true'...
    options.sidebarMiniMapShow = options.sidebarMiniMapShow && true;  // This should work
    options.sidebarMiniMapHeight = options.sidebarMiniMapHeight || '20%';

    options.export = options.export || {};
    options.export.png = options.export.png || undefined;
    options.export.svg = options.export.svg || undefined;
    options.export.json = options.export.json || undefined;

    options.link = options.link || undefined;
    options.linkTarget = options.linkTarget || '_self';

    options.tooltipGravity = options.tooltipGravity || $.fn.tipsy.autoWE;
    options.tooltipFields = options.tooltipFields || {};

    options.onLoadingStart = options.onLoadingStart || function () { };
    options.onLoadingStop = options.onLoadingStop || function () { };

    var dag = this;

    // Get the necessary parameters
    var lightweight = !options.animationEnabled;

    // Twiddle the attach point a little bit
    var rootSVG = d3.select(attachPoint).append("svg").attr("width", options.width).attr("height",options.height);
    var graphSVG = rootSVG.append("svg").attr("width", "100%").attr("height", "100%").attr("class", "graph-attach");
    graphSVG.node().oncontextmenu = function (d) { return false; };
    var minimapSVG = rootSVG.append("svg").attr("class", "minimap-attach");
    var listSVG = rootSVG.append("svg").attr("class", "history-attach");

    // Create the graph and history representations
    var graph = createGraphFromJSON(json, options);
    var history = DirectedAcyclicGraphHistory();

    var bbox = d3.select(attachPoint).node().getBoundingClientRect();

    // Create the chart instances
    var DAG = DirectedAcyclicGraph(attachPoint, options).animate(!lightweight);
    var sidebarX = 0, sidebarY = 0;
    if (options.sidebarWidth.indexOf('%') != -1) {
        sidebarX = (100 - parseFloat(options.sidebarWidth)) + '%';
    } else {
        sidebarX = bbox.width - options.sidebarWidth;
    }
    if (options.sidebarMiniMapHeight.indexOf('%') != -1) {
        sidebarY = (100 - parseFloat(options.sidebarMiniMapHeight)) + '%';
    } else {
        sidebarY = bbox.height - options.sidebarMiniMapHeight;
    }
    if (options.sidebarMiniMapShow) {
        var DAGMinimap = DirectedAcyclicGraphMinimap(DAG, options)
            .width(options.sidebarWidth)
            .height(options.sidebarMiniMapHeight)
            .x(sidebarX)
            .y(sidebarY);
    }
    var DAGHistory = List(options)
        .width(options.sidebarWidth)
        .height(options.sidebarMiniMapShow ? bbox.height - options.sidebarMiniMapHeight : bbox.height)
        .x(sidebarX)
        .y(0);
    var DAGTooltip = DirectedAcyclicGraphTooltip(options);
    var DAGContextMenu = DirectedAcyclicGraphContextMenu(graph, graphSVG);

    // Attach the panzoom behavior
    var refreshViewport = function () {
        var t = zoom.translate();
        var scale = zoom.scale();
        graphSVG.select(".graph").attr("transform", "translate(" + t[0] + "," + t[1] + ") scale(" + scale + ")");
        minimapSVG.select('.viewfinder').attr("x", -t[0] / scale).attr("y", -t[1] / scale).attr("width", attachPoint.offsetWidth / scale).attr("height", attachPoint.offsetHeight / scale);
        if (!lightweight) graphSVG.selectAll(".node text").attr("opacity", 3 * scale - 0.3);
    }
    var zoom = MinimapZoom().scaleExtent([0.001, 2.0]).on("zoom", refreshViewport);
    zoom.call(this, rootSVG, minimapSVG);

    // A function that resets the viewport by zooming all the way out
    var resetViewport = function () {
        var curbbox = graphSVG.node().getBBox();
        var bbox = { x: curbbox.x, y: curbbox.y, width: curbbox.width + 50, height: curbbox.height + 50 };
        scale = Math.min(attachPoint.offsetWidth / bbox.width, attachPoint.offsetHeight / bbox.height);
        w = attachPoint.offsetWidth / scale;
        h = attachPoint.offsetHeight / scale;
        tx = ((w - bbox.width) / 2 - bbox.x + 25) * scale;
        ty = ((h - bbox.height) / 2 - bbox.y + 25) * scale;
        zoom.translate([tx, ty]).scale(scale);
        refreshViewport();
    }

    // Attaches a context menu to any selected graph nodess
    function attachContextMenus() {
        DAGContextMenu.call(graphSVG.node(), graphSVG.selectAll(".node"));
        DAGContextMenu.on("open", function () {
            DAGTooltip.hide();
        }).on("close", function () {
            if (!lightweight) {
                graphSVG.selectAll(".node").classed("preview", false);
                graphSVG.selectAll(".edge").classed("preview", false);
            }
        }).on("hidenodes", function (nodes) {
            var names = [];
            for (var n in nodes) {
                names.push(nodes[n].details.id);
            }
            var item = history.addSelection(nodes, names);
            if (!lightweight) graphSVG.classed("hovering", false);
            listSVG.datum(history).call(DAGHistory);

            // Find the point to animate the hidden nodes to
            var bbox = DAGHistory.bbox().call(DAGHistory.select.call(listSVG.node(), item), item);
            var transform = zoom.getTransform(bbox);
            DAG.removenode(function (d) {
                if (lightweight) {
                    d3.select(this).remove();
                } else {
                    d3.select(this).classed("visible", false).transition().duration(800).attr("transform", transform).remove();
                }
            });

            dag.draw();

            // Refresh selected edges
            var selected = {};
            graphSVG.selectAll(".node.selected").data().forEach(function (d) { selected[d.id] = true; });
            graphSVG.selectAll(".edge").classed("selected", function (d) {
                return selected[d.source.id] && selected[d.target.id];
            });
        }).on("hovernodes", function (nodes) {
            if (!lightweight) {
                graphSVG.selectAll(".node").classed("preview", function (d) {
                    return nodes.indexOf(d) != -1;
                })
                var previewed = {};
                graphSVG.selectAll(".node.preview").data().forEach(function (d) { previewed[d.id] = true; });
                graphSVG.selectAll(".edge").classed("preview", function (d) {
                    return previewed[d.source.id] && previewed[d.target.id];
                });
            }
        }).on("selectnodes", function (nodes) {
            var selected = {};
            nodes.forEach(function (d) { selected[d.id] = true; });
            graphSVG.selectAll(".node").classed("selected", function (d) {
                var selectme = selected[d.id];
                if (d3.event.ctrlKey) selectme = selectme || d3.select(this).classed("selected");
                return selectme;
            })
            graphSVG.selectAll(".edge").classed("selected", function (d) {
                var selectme = selected[d.source.id] && selected[d.target.id];
                if (d3.event.ctrlKey) selectme = selectme || d3.select(this).classed("selected");
                return selectme;
            });
            attachContextMenus();
            DAGTooltip.hide();
        });
    }

    // Detaches any bound context menus
    function detachContextMenus() {
        $(".graph .node").unbind("contextmenu");
    }

    // A function that attaches mouse-click events to nodes to enable node selection
    function setupEvents() {
        var nodes = graphSVG.selectAll(".node");
        var edges = graphSVG.selectAll(".edge");
        var items = listSVG.selectAll(".item");

        // Set up node selection events
        var select = Selectable().getrange(function (a, b) {
            var path = getNodesBetween(a, b).concat(getNodesBetween(b, a));
            return nodes.data(path, DAG.nodeid());
        }).on("select", function () {
            var selected = {};
            graphSVG.selectAll(".node.selected").data().forEach(function (d) { selected[d.id] = true; });
            edges.classed("selected", function (d) {
                return selected[d.source.id] && selected[d.target.id];
            });
            attachContextMenus();
            DAGTooltip.hide();
        });
        select(nodes);


        if (!lightweight) {
            nodes.on("mouseover", function (d) {
                graphSVG.classed("hovering", true);
                highlightPath(d);
            }).on("mouseout", function (d) {
                graphSVG.classed("hovering", false);
                edges.classed("hovered", false).classed("immediate", false);
                nodes.classed("hovered", false).classed("immediate", false);
            });
        }

        // When a list item is clicked, it will be removed from the history and added to the graph
        // So we override the DAG node transition behaviour so that the new nodes animate from the click position
        items.on("click", function (d, i) {
            // Remove the item from the history and redraw the history
            history.remove(d);
            listSVG.datum(history).call(DAGHistory);

            // Now update the location that the new elements of the graph will enter from
            var transform = zoom.getTransform(DAGHistory.bbox().call(this, d));
            DAG.newnodetransition(function (d) {
                if (DAG.animate()) {
                    d3.select(this).attr("transform", transform).transition().duration(800).attr("transform", DAG.nodeTranslate);
                } else {
                    d3.select(this).attr("transform", transform).attr("transform", DAG.nodeTranslate);
                }
            })

            // Redraw the graph and such
            dag.draw();
        })

        function highlightPath(center) {
            var path = getEntirePathLinks(center);

            var pathnodes = {};
            var pathlinks = {};

            path.forEach(function (p) {
                pathnodes[p.source.id] = true;
                pathnodes[p.target.id] = true;
                pathlinks[p.source.id + p.target.id] = true;
            });

            edges.classed("hovered", function (d) {
                return pathlinks[d.source.id + d.target.id];
            })
            nodes.classed("hovered", function (d) {
                return pathnodes[d.id];
            });

            var immediatenodes = {};
            var immediatelinks = {};
            immediatenodes[center.id] = true;
            center.getVisibleParents().forEach(function (p) {
                immediatenodes[p.id] = true;
                immediatelinks[p.id + center.id] = true;
            })
            center.getVisibleChildren().forEach(function (p) {
                immediatenodes[p.id] = true;
                immediatelinks[center.id + p.id] = true;
            })

            edges.classed("immediate", function (d) {
                return immediatelinks[d.source.id + d.target.id];
            })
            nodes.classed("immediate", function (d) {
                return immediatenodes[d.id];
            })
        }
    }

    // The main draw function
    this.draw = function () {
        options.onLoadingStart();
        DAGTooltip.hide();                  // Hide any tooltips
        console.log("draw begin")
        var begin = (new Date()).getTime();
        var start = (new Date()).getTime();
        graphSVG.datum(graph).call(DAG);    // Draw a DAG at the graph attach
        console.log("draw graph", new Date().getTime() - start);
        if (options.sidebarMiniMapShow) {
            start = (new Date()).getTime();
            minimapSVG.datum(graphSVG.node()).call(DAGMinimap);  // Draw a Minimap at the minimap attach
            console.log("draw minimap", new Date().getTime() - start);
        }
        start = (new Date()).getTime();
        graphSVG.selectAll(".node").call(DAGTooltip);        // Attach tooltips
        console.log("draw tooltips", new Date().getTime() - start);
        start = (new Date()).getTime();
        setupEvents();                      // Set up the node selection events
        console.log("draw events", new Date().getTime() - start);
        start = (new Date()).getTime();
        refreshViewport();                  // Update the viewport settings
        console.log("draw viewport", new Date().getTime() - start);
        start = (new Date()).getTime();
        attachContextMenus();
        console.log("draw contextmenus", new Date().getTime() - start);
        console.log("draw complete, total time=", new Date().getTime() - begin);
        options.onLoadingStop();
    }

    //Call the draw function
    this.draw();

    // Start with the graph all the way zoomed out
    resetViewport();

    // Save important variables
    this.attachPoint = attachPoint;
    this.reports = json;
    this.DAG = DAG
    this.DAGMinimap = DAGMinimap;
    this.DAGHistory = DAGHistory;
    this.DAGTooltip = DAGTooltip;
    this.DAGContextMenu = DAGContextMenu;
    this.graph = graph;
    this.resetViewport = resetViewport;
    this.history = history;


    // Add a play button
    //    console.log("appending play button");
    //    var playbutton = rootSVG.append("svg").attr("x", "10").attr("y", "5").append("text").attr("text-anchor", "left").append("tspan").attr("x", 0).attr("dy", "1em").text("Click To Play").on("click",
    //            function(d) {
    //        animate();
    //    });

    var animate = function () {
        var startTime = new Date().getTime();

        // Find the min and max times
        var max = 0;
        var min = Infinity;
        graphSVG.selectAll(".node").each(function (d) {
            var time = parseFloat(d.report["Timestamp"]);
            if (time < min) {
                min = time;
            }
            if (time > max) {
                max = time;
            }
        })

        var playDuration = 10000;

        var update = function () {
            var elapsed = new Date().getTime() - startTime
            var threshold = (elapsed * (max - min) / playDuration) + min;
            graphSVG.selectAll(".node").attr("display", function (d) {
                d.animation_hiding = parseFloat(d.report["Timestamp"]) < threshold ? null : true;
                return d.animation_hiding ? "none" : "";
            });
            graphSVG.selectAll(".edge").attr("display", function (d) {
                return (d.source.animation_hiding || d.target.animation_hiding) ? "none" : "";
            })
            if (elapsed < playDuration) {
                window.setTimeout(update, 10);
            }
        }
        update();

    }

    function copyStylesInline(destinationNode, callback) {
        d3.select(destinationNode).attr("width", 1920).attr("height", 1080).selectAll('text').attr("opacity", 1);
        d3.xhr("dist/enricher.css", function (data) {
            d3.select(destinationNode).insert("defs", ":first-child").append("style").attr("type", "text/css").text(data.responseText);
            callback();
        });
    }

    function triggerDownload(imgURI, fileName) {
        var evt = new MouseEvent("click", {
            view: window,
            bubbles: false,
            cancelable: true
        });
        var a = document.createElement("a");
        a.setAttribute("download", fileName);
        a.setAttribute("href", imgURI);
        a.setAttribute("target", '_blank');
        a.dispatchEvent(evt);
    }

    function downloadJSON(filename) {
        options.onLoadingStart();
        // convert svg source to URI data scheme
        var imgURI = "data:text/plain;charset=utf-8," + encodeURIComponent(JSON.stringify(json));
        triggerDownload(imgURI, filename);
        options.onLoadingStop();
    }

    function downloadSVG(filename) {
        options.onLoadingStart();
        // get svg source
        var serializer = new XMLSerializer();
        var copy = graphSVG.select('svg').node().cloneNode(true);
        copyStylesInline(copy, function () {
            var source = serializer.serializeToString(copy);
            source = '<?xml version="1.0" standalone="no"?>\r\n' + source;

            // convert svg source to URI data scheme
            var imgURI = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(source);
            triggerDownload(imgURI, filename);
            options.onLoadingStop();
        });
    }

    function downloadPNG(filename) {
        options.onLoadingStart();
        // get svg source
        var serializer = new XMLSerializer();
        var copy = graphSVG.select('svg').node().cloneNode(true);
        copyStylesInline(copy, function () {
            var source = serializer.serializeToString(copy);
            source = '<?xml version="1.0" standalone="no"?>\r\n' + source;

            // convert svg source to URI data scheme
            var url = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(source);

            // create canvas to convert svg to png
            var bbox = graphSVG.node().getBBox();
            var canvas = document.createElement("canvas");
            canvas.width = 1920;
            canvas.height = 1080;
            var ctx = canvas.getContext("2d");
            ctx.clearRect(0, 0, bbox.width, bbox.height);

            // do the conversion and serve png
            var img = new Image();
            img.onload = function () {
                ctx.drawImage(img, 0, 0);
                var imgURI = canvas
                    .toDataURL("image/png")
                    .replace("image/png", "image/octet-stream");
                triggerDownload(imgURI, filename);
                document.removeChild(canvas);
                options.onLoadingStop();
            };
            img.src = url;
        });
    }

    if (options.export.png) {
        document.getElementById(options.export.png).addEventListener('click', function () {
            downloadPNG('graph.png');
        });
    }
    if (options.export.svg) {
        document.getElementById(options.export.svg).addEventListener('click', function () {
            downloadSVG('graph.svg');
        });
    }
    if (options.export.json) {
        document.getElementById(options.export.json).addEventListener('click', function () {
            downloadJSON('graph.json');
        });
    }
}
