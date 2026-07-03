/**
 * Styled-button paint mirroring in the builder preview.
 */
(function (builder) {
	"use strict";

	builder.applyStyledButtonCssVariables = function(repeaterItem, link, preview, wrapper) {
		if (!repeaterItem || !link || !wrapper) {
			return;
		}

		var bg = builder.readColorControlValue(builder.getRepeaterControlInner(repeaterItem, "ecbb_background"));
		if (bg) {
			wrapper.style.setProperty("--ecbb-btn-bg", bg);
		}

		var textColor = builder.readTypographyControlColor(
			repeaterItem,
			wrapper,
			preview,
			[link]
		);
		if (textColor) {
			wrapper.style.setProperty("--ecbb-btn-fg", textColor);
		}

		link.style.removeProperty("background-color");
		link.style.removeProperty("color");
	}

	builder.applyStyledButtonBorderToSurface = function(repeaterItem, surface) {
		if (!repeaterItem || !surface) {
			return;
		}

		var padding = builder.readSpacingControlValue(builder.getRepeaterControlInner(repeaterItem, "btn_padding"));
		if (padding) {
			surface.style.padding = padding;
		}

		var radius = builder.readSpacingControlValue(builder.getRepeaterControlInner(repeaterItem, "btn_border_radius"));
		if (radius) {
			surface.style.borderRadius = radius;
		}

		var borderType =
			builder.readSelectControlValue(builder.getRepeaterControlInner(repeaterItem, "btn_border_type")) || "";
		var borderWidth = builder.readNumberControlValue(
			builder.getRepeaterControlInner(repeaterItem, "btn_border_width")
		);
		var borderColor = builder.readColorControlValue(builder.getRepeaterControlInner(repeaterItem, "btn_border_color"));

		if (borderType === "none") {
			surface.style.setProperty("border", "none");
		} else if (borderType && borderColor) {
			surface.style.setProperty(
				"border",
				(borderWidth || "1px") + " " + (borderType || "solid") + " " + borderColor
			);
		} else if (borderType && borderWidth) {
			surface.style.setProperty(
				"border-width",
				borderWidth
			);
			surface.style.setProperty("border-style", borderType || "solid");
		} else if (borderColor) {
			surface.style.setProperty("border-color", borderColor);
		}

		surface.style.setProperty("box-sizing", "border-box");
	}

	/**
	 * Button paint target: link when hover is on, plain span when hover is off + button styles.
	 */
	builder.findStyledButtonPreviewSurface = function(wrapper) {
		if (!wrapper) {
			return null;
		}
		var link = wrapper.querySelector(
			".ecbb-event__link, a.event-button, a.ecbb-event-card__button"
		);
		if (link) {
			return link;
		}
		if (wrapper.classList.contains("ecbb-has-btn")) {
			return wrapper.querySelector(".ecbb-event__plain");
		}
		return null;
	}

	builder.clearStyledButtonPreviewPaint = function(wrapper) {
		if (!wrapper) {
			return;
		}

		wrapper.classList.remove("ecbb-has-btn");
		wrapper.style.removeProperty("--ecbb-btn-bg");
		wrapper.style.removeProperty("--ecbb-btn-fg");
		[
			"backgroundColor",
			"background",
			"color",
			"padding",
			"border",
			"borderRadius",
			"display",
			"alignItems",
			"justifyContent",
			"width",
			"maxWidth",
			"boxSizing",
		].forEach(function (prop) {
			wrapper.style[prop] = "";
		});

		wrapper
			.querySelectorAll(
				".ecbb-event__link, .ecbb-event__plain, a.event-button, a.ecbb-event-card__button"
			)
			.forEach(function (node) {
			node.removeAttribute("style");
		});
	}

	/**
	 * Bricks repeater live CSS paints the row wrapper; keep button paint on the inner surface.
	 */
	builder.syncStyledButtonPreviewPaint = function(repeaterItem) {
		var preview = builder.getBricksPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}

		var rowId = builder.readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return;
		}

		var wrapper = preview.querySelector('[data-field-id="' + rowId + '"]');
		if (!wrapper) {
			return;
		}

		var btnStyleOn = builder.isStyledButtonModeEnabled(repeaterItem);
		if (!btnStyleOn) {
			wrapper.classList.remove("ecbb-has-btn");
			if (builder.isLayoutActionButtonPart(repeaterItem)) {
				if (builder.isStyle2CardActionButtonContext(preview, wrapper)) {
					builder.syncStyle2ActionButtonTypographyColor(
						repeaterItem,
						wrapper,
						preview,
						builder.findLayoutActionButtonSurfaces(wrapper)
					);
				} else {
					builder.syncLayoutActionButtonPreviewStyle(repeaterItem);
				}
				return;
			}
			builder.clearStyledButtonPreviewPaint(wrapper);
			return;
		}

		if (builder.isStyle2CardActionButtonContext(preview, wrapper)) {
			wrapper.classList.remove("ecbb-has-btn");
			builder.syncStyle2ActionButtonTypographyColor(
				repeaterItem,
				wrapper,
				preview,
				builder.findLayoutActionButtonSurfaces(wrapper)
			);
			return;
		}

		wrapper.classList.add("ecbb-has-btn");

		var surface = builder.findStyledButtonPreviewSurface(wrapper);
		if (!surface) {
			return;
		}

		var typoColor = builder.readTypographyControlColor(
			repeaterItem,
			wrapper,
			preview,
			surface ? [surface] : null
		);
		var typoOverrides = builder.readTypographyControlSnapshot(
			repeaterItem,
			wrapper,
			preview,
			surface ? [surface] : null
		);
		if (!typoColor && typoOverrides.color) {
			typoColor = typoOverrides.color;
		}
		var computed =
			preview.defaultView && wrapper
				? preview.defaultView.getComputedStyle(wrapper)
				: null;

		builder.setWrapperTypographyFlags(wrapper, repeaterItem, typoColor, typoOverrides);
		builder.collapseRepeaterWrapperTypographyShell(wrapper);

		[
			"backgroundColor",
			"background",
			"color",
			"padding",
			"border",
			"borderRadius",
		].forEach(function (prop) {
			if (wrapper.style[prop]) {
				wrapper.style[prop] = "";
			}
		});

		builder.applyStyledButtonBorderToSurface(repeaterItem, surface);
		builder.applyStyledButtonCssVariables(repeaterItem, surface, preview, wrapper);
		if (computed) {
			builder.applyTypographyToButtonPreviewSurface(
				surface,
				computed,
				typoColor,
				typoOverrides
			);
		}

		builder.scheduleHoverPreviewStyleSync(repeaterItem);

		surface.style.setProperty("display", "inline-flex");
		surface.style.setProperty("align-items", "center");
		surface.style.setProperty("justify-content", "center");
		surface.style.setProperty("width", "auto");
		surface.style.setProperty("max-width", "100%");
		surface.style.setProperty("box-sizing", "border-box");
	}

	builder.scheduleStyledButtonPreviewSync = function(repeaterItem) {
		if (builder.sync.styledButton) {
			builder.sync.styledButton.schedule(repeaterItem);
			return;
		}
		builder.syncStyledButtonPreviewPaint(repeaterItem);
	}
})(window.ECBbuilder.builder);

