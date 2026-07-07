/**
 * Shared color normalization for builder controls and preview sync.
 */
(function (builder) {
	"use strict";

	/**
	 * @param {string} value
	 * @param {{allowHsla?: boolean}} [opts]
	 * @returns {string}
	 */
	builder.isMeaningfulColor = function (value, opts) {
		opts = opts || {};
		if (!value) {
			return "";
		}
		var trimmed = String(value).trim();
		var lower = trimmed.toLowerCase();
		if (
			!trimmed ||
			lower === "transparent" ||
			lower === "currentcolor" ||
			lower === "inherit" ||
			lower === "initial" ||
			lower === "unset"
		) {
			return "";
		}
		if (/^rgba?\(/i.test(trimmed)) {
			var rgbaParts = trimmed.match(/[\d.]+/g);
			if (rgbaParts && rgbaParts.length >= 4 && parseFloat(rgbaParts[3]) <= 0) {
				return "";
			}
		}
		if (opts.allowHsla && /^hsla?\(/i.test(trimmed)) {
			var hslaParts = trimmed.match(/[\d.]+/g);
			if (hslaParts && hslaParts.length >= 4 && parseFloat(hslaParts[3]) <= 0) {
				return "";
			}
		}
		return trimmed;
	};

	builder.normalizeTypographyColor = function (value) {
		return builder.isMeaningfulColor(value);
	};

	builder.isVisibleHoverColor = function (value) {
		return !!builder.isMeaningfulColor(value, { allowHsla: true });
	};
})(window.ECBB.builder);
