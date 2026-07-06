/**
 * ECBB Bricks builder — shared toolbox (loads first).
 *
 * HOW THE BUILDER SCRIPTS FIT TOGETHER
 * ------------------------------------
 * WordPress loads several small files in order (see class-ecbb-plugin.php).
 * They all add functions to ONE shared object: window.ECBB.builder
 *
 * Each other file uses this pattern:
 *
 *   (function (builder) {
 *     builder.doSomething = function () { ... };
 *   })(window.ECBB.builder);
 *
 *   (function (builder) { ... })  → run immediately, in a private scope
 *   builder                         → same object as window.ECBB.builder
 *   builder.doSomething = ...         → register a function for other files
 *   )(window.ECBB.builder)           → pass that object in when the file runs
 */
(function () {
	"use strict";

	window.ECBB = window.ECBB || {};
	window.ECBB.builder = window.ECBB.builder || {};

	var builder = window.ECBB.builder;
	var fromPhp = typeof ECBBBuilder !== "undefined" ? ECBBBuilder : {};

	builder.config = {
		tabContentLabel: fromPhp.tabContent || "CONTENT",
		tabStyleLabel: fromPhp.tabStyle || "STYLE",
		hoverCapableParts: Array.isArray(fromPhp.hoverParts)
			? fromPhp.hoverParts
			: ["title", "categories", "tags", "read_more", "event_tickets", "event_rsvp", "image"],
		style2MetaIconParts: ["venue", "date", "event_cost", "venue_time_cost"],
		layoutActionButtonParts: ["read_more", "event_tickets", "event_rsvp"],
		styledButtonBackgroundKeys: ["ecbb_background"],
		styledButtonBorderKeys: Array.isArray(fromPhp.btnBorderKeys)
			? fromPhp.btnBorderKeys.filter(function (key) { return key !== "btn_sep_border"; })
			: ["btn_border_type", "btn_border_width", "btn_border_color", "btn_padding", "btn_border_radius"],
	};

	builder.config.styledButtonControlKeys = builder.config.styledButtonBackgroundKeys.concat(
		builder.config.styledButtonBorderKeys
	);
	builder.config.hoverPreviewControlKeys = Array.isArray(fromPhp.hoverKeys)
		? fromPhp.hoverKeys.filter(function (key) {
			return key === "ecbb_use_hover" || key === "ecbb_hover_color" || key === "ecbb_hover_background";
		})
		: ["ecbb_use_hover", "ecbb_hover_color", "ecbb_hover_background"];

	// Shared state buckets (other files read/write these).
	builder.sync = { registry: [] };
	builder.tabs = { scanTimer: null };
	builder.preview = { typographyPickerRaf: 0 };
	builder.hover = {
		rulesByItem: typeof WeakMap !== "undefined" ? new WeakMap() : null,
		rulesByRowId: {},
		layoutBtnTypoRulesByItem: typeof WeakMap !== "undefined" ? new WeakMap() : null,
		layoutBtnTypoRulesByRowId: {},
	};
})();
