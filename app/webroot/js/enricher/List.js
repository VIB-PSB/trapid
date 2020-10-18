function List(options) {
    var width = constant(100),
        height = constant(100),
        x = constant(0),
        y = constant(0),
        itemwidth = constant("90%"),
        itemheight = function (d) {
            return 35 + 15 * d.names.length;
        },
        itemx = constant("5%"),
        itemy = function (d, i) {
            var y = 0;
            d3.select(this.parentNode).each(function (nodes) {
                for (var n = 0; n < i; n++) {
                    y += itemheight(nodes[n]) + 5;
                }
            });
            return y;
        };


    function list(selection) {
        selection.each(function (data) {
            // Select the svg element that we draw to or add it if it doesn't exist
            var svg = d3.select(this).selectAll("svg").data([data]);
            var firsttime = svg.enter().append("svg");
            firsttime.append("rect")
                .attr("class", "background")
                .attr("width", "100%")
                .attr("height", "100%");
            firsttime.append("text")
                .text("Hidden nodes")
                .attr("text-anchor", "middle")
                .attr("x", "50%")
                .attr("dy", "1.25em");

            var contents = firsttime.append("svg").attr("class", "list");
            contents.attr("y", 30);

            // Size the list as appropriate
            svg.attr("width", width.call(this, data));
            svg.attr("height", height.call(this, data));
            svg.attr("x", x.call(this, data));
            svg.attr("y", y.call(this, data));

            // Draw the list items
            var items = svg.select(".list").selectAll(".item").data(data, function (d) { return d.id; });

            // Draw new items
            var newitems = items.enter()
                .insert("svg", ":first-child")
                .attr("class", "item")
                .attr("opacity", 1e-8)
                .attr("width", itemwidth)
                .attr("height", itemheight)
                .attr("x", itemx)
                .attr("y", itemy);
            newitems.each(drawitem);

            items.transition().delay(options.animationDuration).duration(options.animationDuration).attr("x", itemx).attr("y", itemy);
            newitems.transition().delay(options.animationDuration).duration(options.animationDuration).attr("opacity", 1);

            // Remove old items
            items.exit().transition().each('end', function () {
                if (items[0].length == 0) svg.transition().duration(options.animationDuration).attr("opacity", 1e-6).remove();
            }).duration(options.animationDuration).attr("opacity", 1e-6).remove();

        });
    }

    var drawitem = function (d, i) {
        var item = d3.select(this);
        item.append("rect")
            .attr("x", "1%")
            .attr("y", "1%")
            .attr("width", "98%")
            .attr("height", "98%")
            .attr("rx", "15")
            .attr("ry", "15");
        if (d.selection) {
            var size = d.selection.length;
            var text = size + " nodes:";
            if (size == 1) {
                var text = size + " node:";
            }
            item.append("text").text(text).attr("x", "50%").attr("dy", "1em");
        }
        if (d.names.length) {
            var i = 2;
            for (name in d.names) {
                item.append("text").text(d.names[name]).attr("x", "50%").attr("dy", i + "em");
                i++;
            }
        }
    }

    var bbox = function (d, i) {
        var brect = this.getBoundingClientRect();
        var bbox = {
            x: brect.left, y: brect.top, width: brect.right - brect.left, height: brect.bottom - brect.top
        }
        return bbox;
    }

    list.select = function (item) {
        return d3.select(this).selectAll(".item").data([item], function (d) { return d.id; }).node();
    }


    list.width = function (_) { if (!arguments.length) return width; width = constant(_); return list; }
    list.height = function (_) { if (!arguments.length) return height; height = constant(_); return list; }
    list.x = function (_) { if (!arguments.length) return x; x = constant(_); return list; }
    list.y = function (_) { if (!arguments.length) return y; y = constant(_); return list; }
    list.itemwidth = function (_) { if (!arguments.length) return itemwidth; itemwidth = constant(_); return list; }
    list.itemheight = function (_) { if (!arguments.length) return itemheight; itemheight = constant(_); return list; }
    list.itemx = function (_) { if (!arguments.length) return itemx; itemx = constant(_); return list; }
    list.itemy = function (_) { if (!arguments.length) return itemy; itemy = constant(_); return list; }
    list.bbox = function (_) { if (!arguments.length) return bbox; bbox = constant(_); return list; }

    return list;
}