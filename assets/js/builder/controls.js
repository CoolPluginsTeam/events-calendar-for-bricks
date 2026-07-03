/**
 * Bricks panel control value readers (color, spacing, select, number).
 */
(function (builder) {
	"use strict";

	builder.readColorControlValue = function (controlInner) {
		if (!controlInner) {
			return "";
		}

		var inputs = controlInner.querySelectorAll(
			'input[type="text"], input[type="color"]'
		);
		var i;
		for (i = 0; i < inputs.length; i++) {
			var value = (inputs[i].value || "").trim();
			if (!value) {
				continue;
			}
			if (
				value.indexOf("#") === 0 ||
				value.indexOf("rgb") === 0 ||
				value.indexOf("hsl") === 0 ||
				value.indexOf("hwb") === 0 ||
				value.indexOf("var(") === 0
			) {
				return value;
			}
		}

		var pickr = controlInner.querySelector(".pcr-result");
		if (pickr && pickr.style && pickr.style.backgroundColor) {
			var pickrBg = pickr.style.backgroundColor;
			if (builder.normalizeTypographyColor(pickrBg)) {
				return pickrBg;
			}
		}

		var pickrBtn = controlInner.querySelector(".pcr-button");
		if (pickrBtn && pickrBtn.style && pickrBtn.style.backgroundColor) {
			var pickrBtnBg = pickrBtn.style.backgroundColor;
			if (builder.normalizeTypographyColor(pickrBtnBg)) {
				return pickrBtnBg;
			}
		}

		return "";
	};

	builder.readSpacingControlValue = function (controlInner) {
		if (!controlInner) {
			return "";
		}

		var sides = ["top", "right", "bottom", "left"];
		var values = {};
		var hasValue = false;
		var i;

		for (i = 0; i < sides.length; i++) {
			var side = sides[i];
			var input = controlInner.querySelector(
				'input[name*="' + side + '"], input[data-name="' + side + '"], input[data-key="' + side + '"]'
			);
			if (!input) {
				var labeled = controlInner.querySelectorAll("input[type='text'], input[type='number']");
				if (labeled.length >= 4) {
					input = labeled[i];
				}
			}
			if (input && input.value) {
				values[side] = String(input.value).trim();
				if (values[side]) {
					hasValue = true;
				}
			}
		}

		if (!hasValue) {
			return "";
		}

		return (
			(values.top || "0") +
			" " +
			(values.right || values.top || "0") +
			" " +
			(values.bottom || values.top || "0") +
			" " +
			(values.left || values.right || values.top || "0")
		);
	};

	builder.readSelectControlValue = function (controlInner) {
		if (!controlInner) {
			return "";
		}
		var select = controlInner.querySelector("select");
		return select && select.value ? String(select.value).trim() : "";
	};

	builder.readNumberControlValue = function (controlInner) {
		if (!controlInner) {
			return "";
		}
		var input = controlInner.querySelector(
			'input[type="number"], input[type="text"]'
		);
		if (!input || !input.value) {
			return "";
		}
		var value = String(input.value).trim();
		if (!value) {
			return "";
		}
		if (/[a-z%]+$/i.test(value)) {
			return value;
		}
		return value + "px";
	};
})(window.ECBB.builder);
