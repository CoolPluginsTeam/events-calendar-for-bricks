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
		var link = wrapper.querySelector(builder.config.layoutActionSurfaceSelector);
		if (link) {
			return link;
		}
		if (wrapper.classList.contains("ecbb-has-btn")) {
			return wrapper.querySelector(".ecbb-event__plain");
		}
		return null;
	}

	builder.clearStyledButtonMirroredSurfaceStyles = function(node) {
		if (!node) {
			return;
		}
		builder.clearInlineProps(node, [
			"background-color",
			"background",
			"color",
			"padding",
			"border",
			"border-radius",
			"font-size",
			"line-height",
			"font-family",
			"font-weight",
			"letter-spacing",
			"text-transform",
			"display",
			"align-items",
			"justify-content",
			"width",
			"max-width",
			"box-sizing",
		]);
	};

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
				builder.config.layoutActionSurfaceSelector + ", .ecbb-event__plain"
			)
			.forEach(function (node) {
			builder.clearStyledButtonMirroredSurfaceStyles(node);
		});
	}

	builder.readStyledButtonPaintInputs = function(repeaterItem, wrapper, preview, surface) {
		var surfaces = surface ? [surface] : null;
		var typoColor = builder.readTypographyControlColor(
			repeaterItem,
			wrapper,
			preview,
			surfaces
		);
		var typoOverrides = builder.readTypographyControlSnapshot(
			repeaterItem,
			wrapper,
			preview,
			surfaces
		);
		if (!typoColor && typoOverrides.color) {
			typoColor = typoOverrides.color;
		}
		var computed =
			preview.defaultView && wrapper
				? preview.defaultView.getComputedStyle(wrapper)
				: null;
		return { typoColor: typoColor, typoOverrides: typoOverrides, computed: computed };
	}

	builder.paintStyledButtonSurface = function(repeaterItem, wrapper, preview, surface, inputs) {
		builder.setWrapperTypographyFlags(
			wrapper,
			repeaterItem,
			inputs.typoColor,
			inputs.typoOverrides
		);
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
		if (inputs.computed) {
			builder.applyTypographyToButtonPreviewSurface(
				surface,
				inputs.computed,
				inputs.typoColor,
				inputs.typoOverrides
			);
		}

		surface.style.setProperty("display", "inline-flex");
		surface.style.setProperty("align-items", "center");
		surface.style.setProperty("justify-content", "center");
		surface.style.setProperty("width", "auto");
		surface.style.setProperty("max-width", "100%");
		surface.style.setProperty("box-sizing", "border-box");
	}

	/**
	 * Bricks repeater live CSS paints the row wrapper; keep button paint on the inner surface.
	 */
	builder.syncStyledButtonPreviewPaint = function(repeaterItem) {
		var ctx = builder.resolvePreviewRowContext(repeaterItem);
		if (!ctx) {
			return;
		}
		var preview = ctx.preview;
		var wrapper = ctx.wrapper;

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

		var inputs = builder.readStyledButtonPaintInputs(repeaterItem, wrapper, preview, surface);
		builder.paintStyledButtonSurface(repeaterItem, wrapper, preview, surface, inputs);
		builder.scheduleHoverPreviewStyleSync(repeaterItem);
	}

	builder.scheduleStyledButtonPreviewSync = function(repeaterItem) {
		if (builder.sync.styledButton) {
			builder.sync.styledButton.schedule(repeaterItem);
			return;
		}
		builder.syncStyledButtonPreviewPaint(repeaterItem);
	}
})(window.ECBB.builder);

