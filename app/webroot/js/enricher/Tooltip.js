jQuery.fn.outerHTML = function () {
	return jQuery('<div />').append(this.eq(0).clone()).html();
};

var DirectedAcyclicGraphTooltip = function (options) {

	var tooltip = Tooltip(options).title(function (d) {
		var term = d.details;

		function appendRow(key, value, tooltip) {
			var keyrow = $("<div>").attr("class", "key").append(key);
			var valrow = $("<div>").attr("class", "value").append(value);
			var clearrow = $("<div>").attr("class", "clear");
			tooltip.append($("<div>").append(keyrow).append(valrow).append(clearrow));
		}
		var tooltip = $("<div>").attr("class", "enricher-tooltip");

		appendRow("ID", term.id, tooltip);
		appendRow("Name", term.name, tooltip);
		//appendRow("Namespace", term.namespace, tooltip);
		//appendRow("Definition", term.def.defstr, tooltip);
		for (var key in options.tooltipFields) {
			appendRow(key, options.tooltipFields[key](term), tooltip);
		}
		return tooltip.outerHTML();
	});

	return tooltip;
}

var Tooltip = function (options) {

	var tooltip = function (selection) {
		selection.each(function (d) {
			$(this).tipsy({
				gravity: options.tooltipGravity,
				html: true,
				title: function () { return title(d); },
				opacity: 1
			});
		});
	}

	var title = function (d) { return ""; };
	console.log("yo");
	// console.log("yo");

	tooltip.hide = function () { $(".tipsy").remove(); }
	tooltip.title = function (_) { if (arguments.length == 0) return title; title = _; return tooltip; }


	return tooltip;
}
