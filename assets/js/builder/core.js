/**
 * ECBB Bricks builder — shared toolbox (loads first).
 *
 * HOW THE BUILDER SCRIPTS FIT TOGETHER
 * ------------------------------------
 * WordPress loads several small files in order (see class-ecbb-plugin.php):
 * core → color → tabs → controls → sync → preview → button → main.
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
		style2CategoryHoverFg: fromPhp.style2CategoryHoverFg || "#0d55d8",
		style2CategoryHoverBg: fromPhp.style2CategoryHoverBg || "#d4e4ff",
		hoverCapableParts: Array.isArray(fromPhp.hoverParts)
			? fromPhp.hoverParts
			: ["title", "categories", "tags", "read_more", "event_tickets", "event_rsvp", "image"],
		style2MetaIconParts: Array.isArray(fromPhp.style2MetaIconParts)
			? fromPhp.style2MetaIconParts
			: ["venue", "date", "event_cost", "venue_time_cost"],
		layoutActionButtonParts: ["read_more", "event_tickets", "event_rsvp"],
		layoutActionSurfaceSelector: ".ecbb-event__link, a.event-button, a.ecbb-event-card__button",
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
	builder.config.hoverPreviewSuffixes = Array.isArray(fromPhp.hoverPreviewSuffixes)
		? fromPhp.hoverPreviewSuffixes
		: [];
	builder.config.hoverPreviewLiHasSuffixes = Array.isArray(fromPhp.hoverPreviewLiHasSuffixes)
		? fromPhp.hoverPreviewLiHasSuffixes
		: [];
	builder.config.shellCssVarBindings = Array.isArray(fromPhp.shellCssVarBindings)
		? fromPhp.shellCssVarBindings
		: [];
	builder.config.shellCssVarWatchKeys = Array.isArray(fromPhp.shellCssVarWatchKeys)
		? fromPhp.shellCssVarWatchKeys
		: [];

	// Shared state buckets (other files read/write these).
	builder.sync = {
		registry: [],
		handlersByKey: typeof Map !== "undefined" ? new Map() : null,
	};
	builder.tabs = {
		scanTimer: null,
		sortEndRaf: 0,
		pointerUpScanTimer: null,
	};
	builder.preview = {
		typographyPickerRaf: 0,
		lastTypographyPickerColor: "",
		lastTypographySyncAt: 0,
		iframeEl: null,
		iframeDoc: null,
		repeaterCssCache: typeof Map !== "undefined" ? new Map() : null,
		resyncTimer: 0,
		resyncInFlight: false,
	};
	builder.hover = {
		rulesByRowId: {},
		layoutBtnTypoRulesByRowId: {},
	};

	builder.runWithSettleRetry = function(fn, delay) {
		if (typeof fn !== "function") {
			return;
		}
		fn();
		setTimeout(fn, delay || 120);
	};

	builder.clearInlineProps = function(node, props) {
		if (!node || !props || !props.length) {
			return;
		}
		props.forEach(function (prop) {
			node.style.removeProperty(prop);
		});
	};
})();
