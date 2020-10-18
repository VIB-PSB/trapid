// Problems with resizing and jquery and chrome and this stuff is so dumb.
window.width = function () {
    return document.body.clientWidth;
};

window.height = function () {
    return document.body.clientHeight;
};

// https://github.com/d3/d3/blob/f797dfe883ee510f32acadf3ab8be736146e5927/CHANGES.md#internals
function constant(x) {
    return function () {
        return x;
    };
}

// http://stackoverflow.com/questions/523266/how-can-i-get-a-specific-parameter-from-location-search
var getParameter = function (name) {
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regexS = "[\\?&]" + name + "=([^&#]*)";
    var regex = new RegExp(regexS);
    var results = regex.exec(window.location.href);
    if (results == null)
        return "";
    else
        return results[1];
};

var createGraphFromJSON = function (json, options) {

    // Create nodes
    var nodes = {};
    for (var nodeid in json.nodes) {
        if (options.namespace == undefined || options.namespace == json.nodes[nodeid].namespace) {
            nodes[nodeid] = new Node(nodeid);
            nodes[nodeid].details = json.nodes[nodeid];
        }
    }

    // Second link the nodes together
    for (var childid in json.nodes) {
        var node = json.nodes[childid];
        for (var parent in node.parents) {
            parentid = node.parents[parent];
            if (nodes[parentid] && nodes[childid]) {
                nodes[parentid].addChild(nodes[childid]);
                nodes[childid].addParent(nodes[parentid]);
            }
        }
    }

    // Create the graph and add the nodes
    var graph = new Graph();
    for (var id in nodes) {
        graph.addNode(nodes[id]);
    }

    return graph;
}


var createJSONFromVisibleGraph = function (graph) {
    var nodes = graph.getVisibleNodes();
    var reports = [];

    for (var i = 0; i < nodes.length; i++) {
        var node = nodes[i];
        var parents = node.getVisibleParents();
        var report = $.extend({}, node.report);
        report["Edge"] = [];
        for (var j = 0; j < parents.length; j++) {
            report["Edge"].push(parents[j].id);
        }
        reports.push(report);
    }

    return { "reports": reports };
}
