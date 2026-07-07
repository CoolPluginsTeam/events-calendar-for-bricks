/**
 * Live preview mirroring and hover preview stylesheet injection.
 */
(function (builder) {
	"use strict";

	builder.isLayoutActionButtonPart = function(repeaterItem) {
		return builder.config.layoutActionButtonParts.indexOf(builder.readRepeaterPartControlValue(repeaterItem)) !== -1;
	}

	builder.findLayoutActionButtonSurfaces = function(wrapper) {
		if (!wrapper) {
			return [];
		}
		return wrapper.querySelectorAll(
			".ecbb-event__link, a.event-button, a.ecbb-event-card__button"
		);
	}

	builder.isStyle2CardActionButtonContext = function(preview, wrapper) {
		if (!wrapper || !builder.isStyle2LayoutPreview(preview)) {
			return false;
		}
		if (
			wrapper.classList.contains("ecbb-style2-read-more") ||
			wrapper.classList.contains("ecbb-style2-event-tickets") ||
			wrapper.classList.contains("ecbb-style2-event-rsvp")
		) {
			return true;
		}
		return !!wrapper.querySelector("a.ecbb-event-card__button");
	}

	builder.clearStyle2ActionButtonMirroredStyles = function(surfaces) {
		if (!surfaces || !surfaces.length) {
			return;
		}
		var props = [
			"color",
			"background-color",
			"padding",
			"font-size",
			"line-height",
			"font-family",
			"font-weight",
			"letter-spacing",
			"text-transform",
			"vertical-align",
		];
		surfaces.forEach(function (node) {
			if (!node.classList.contains("ecbb-event-card__button")) {
				return;
			}
			props.forEach(function (prop) {
				node.style.removeProperty(prop);
			});
		});
	}

	builder.clearStyle2ActionButtonWrapperMirroredStyles = function(wrapper) {
		if (!wrapper) {
			return;
		}
		[
			"padding",
			"margin",
			"background-color",
			"font-size",
			"line-height",
			"text-align",
		].forEach(function (prop) {
			wrapper.style.removeProperty(prop);
		});
	}

	builder.syncStyle2ActionButtonTypographyColor = function(repeaterItem, wrapper, preview, surfaces) {
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

		builder.clearStyle2ActionButtonWrapperMirroredStyles(wrapper);
		builder.clearStyle2ActionButtonMirroredStyles(surfaces);

		if (typoColor) {
			builder.setWrapperTypographyFlags(wrapper, repeaterItem, typoColor, {});
			return;
		}

		wrapper.classList.remove("ecbb-has-typo-fg");
		wrapper.style.removeProperty("--ecbb-btn-fg");
	}

	/**
	 * Bricks repeater live CSS paints typography on the row wrapper. Collapse that
	 * shell so mirrored font-size on the inner button does not leave phantom space.
	 */
	builder.collapseRepeaterWrapperTypographyShell = function(wrapper) {
		if (!wrapper) {
			return;
		}
		wrapper.style.setProperty("padding", "0", "important");
		wrapper.style.setProperty("margin", "0", "important");
		wrapper.style.setProperty("background-color", "transparent", "important");
		wrapper.style.setProperty("font-size", "0", "important");
		wrapper.style.setProperty("line-height", "0", "important");
	}

	builder.applyTypographyToButtonPreviewSurface = function(surface, computed, typoColor, typoOverrides) {
		if (!surface) {
			return;
		}

		typoOverrides = typoOverrides || {};

		if (typoColor) {
			surface.style.setProperty("color", typoColor, "important");
		}
		if (typoOverrides.fontFamily || (computed && computed.fontFamily)) {
			surface.style.setProperty(
				"font-family",
				typoOverrides.fontFamily || computed.fontFamily,
				"important"
			);
		}
		if (typoOverrides.fontSize) {
			surface.style.setProperty("font-size", typoOverrides.fontSize, "important");
		} else if (computed && computed.fontSize && computed.fontSize !== "0px") {
			surface.style.setProperty("font-size", computed.fontSize, "important");
		}
		if (typoOverrides.fontWeight) {
			surface.style.setProperty("font-weight", typoOverrides.fontWeight, "important");
		} else if (computed && computed.fontWeight) {
			surface.style.setProperty("font-weight", computed.fontWeight, "important");
		}
		if (typoOverrides.letterSpacing) {
			surface.style.setProperty(
				"letter-spacing",
				typoOverrides.letterSpacing,
				"important"
			);
		} else if (computed && computed.letterSpacing) {
			surface.style.setProperty(
				"letter-spacing",
				computed.letterSpacing,
				"important"
			);
		}
		var textTransform =
			typoOverrides.textTransform ||
			(computed && computed.textTransform ? computed.textTransform : "");
		if (textTransform && textTransform !== "none") {
			surface.style.setProperty("text-transform", textTransform, "important");
		}
		if (typoOverrides.lineHeight) {
			surface.style.setProperty("line-height", typoOverrides.lineHeight, "important");
		} else {
			surface.style.setProperty("line-height", "1", "important");
		}
		surface.style.setProperty("vertical-align", "top", "important");
	}

	builder.isStyledButtonModeEnabled = function(repeaterItem) {
		var btnStyleInner = builder.getRepeaterControlInner(repeaterItem, "btn_style");
		if (!btnStyleInner) {
			return false;
		}
		var btnCb = btnStyleInner.querySelector('input[type="checkbox"]');
		return !!(btnCb && btnCb.checked);
	}

	builder.readTypographyControlColor = function(repeaterItem, wrapper, preview, surfaces) {
		var controlInner = builder.getRepeaterControlInner(repeaterItem, "ecbb_typography");
		var color = "";

		// Live pickr session wins over a stale saved control value.
		if (document.querySelector(".pcr-app.visible")) {
			color = builder.readActivePickrColor();
		}

		if (!color && controlInner) {
			var colorWrap = controlInner.querySelector(
				'[data-control-key="color"], [data-setting="color"], .control-color'
			);
			color = builder.readColorControlValue(colorWrap || controlInner);
		}

		if (!color) {
			color = builder.readActivePickrColor();
		}

		if (!color && wrapper && preview) {
			color = builder.readPreviewRepeaterCssValue(wrapper, preview, "color");
		}

		if (!color && wrapper && preview && preview.defaultView) {
			color = preview.defaultView.getComputedStyle(wrapper).color;
		}

		if (!color && surfaces && surfaces.length && preview && preview.defaultView) {
			var surface = surfaces[0];
			// Meta icons keep inline builder paint; reading them back returns a stale first pick.
			if (
				!(
					surface &&
					surface.classList &&
					surface.classList.contains("ecbb-event-card__meta-icon")
				)
			) {
				color = preview.defaultView.getComputedStyle(surface).color;
			}
		}

		return builder.normalizeTypographyColor(color);
	}

	/**
	 * Style 1 / grid meta rows: Bricks typography paints the part wrapper; mirror that
	 * color onto the leading icon sibling (and row) after live CSS settles.
	 */
	builder.mirrorMetaRowIconColorFromText = function(wrapper, preview) {
		if (!wrapper || !preview || !preview.defaultView) {
			return "";
		}

		var li = wrapper.closest("li");
		if (!li || !li.closest(".event-meta")) {
			return "";
		}

		var icon = li.querySelector(":scope > .ecbb-event-card__meta-icon");
		if (!icon && wrapper) {
			var inlineIcons = wrapper.querySelectorAll(".ecbb-event-card__meta-icon--inline");
			if (inlineIcons.length) {
				inlineIcons.forEach(function (inlineIcon) {
					inlineIcon.style.setProperty("color", "inherit", "important");
				});
			}
		}
		if (!icon) {
			return "";
		}

		var textColor = builder.normalizeTypographyColor(
			preview.defaultView.getComputedStyle(wrapper).color
		);
		if (!textColor) {
			return "";
		}

		li.style.setProperty("color", textColor, "important");
		icon.style.setProperty("color", "inherit", "important");
		return textColor;
	}

	/** Paint Style 1 / grid meta list rows as one unit (icon + text share the <li> chrome). */
	builder.applyUnifiedMetaListRowPreviewChrome = function(repeaterItem, li, wrapper, icon, preview) {
		if (!li || !wrapper) {
			return;
		}

		var ul = li.closest("ul.event-meta");
		var bg = builder.readColorControlValue(
			builder.getRepeaterControlInner(repeaterItem, "ecbb_background")
		);
		var margin = builder.readSpacingControlValue(
			builder.getRepeaterControlInner(repeaterItem, "ecbb_margin")
		);
		var pad = builder.readSpacingControlValue(
			builder.getRepeaterControlInner(repeaterItem, "ecbb_padding")
		);
		var typoColor = builder.readTypographyControlColor(
			repeaterItem,
			wrapper,
			preview,
			null
		);
		var typoOverrides = builder.readTypographyControlSnapshot(
			repeaterItem,
			wrapper,
			preview,
			null
		);
		if (!typoColor && typoOverrides.color) {
			typoColor = typoOverrides.color;
		}

		li.style.setProperty("display", "inline-flex", "important");
		li.style.setProperty("align-items", "center", "important");
		li.style.setProperty("gap", "8px", "important");
		li.style.setProperty("width", "fit-content", "important");
		li.style.setProperty("max-width", "100%", "important");
		li.style.setProperty("border-radius", "10px", "important");

		if (ul) {
			ul.style.setProperty("margin", "0", "important");
			ul.style.setProperty("padding", "0", "important");
			ul.style.setProperty("list-style", "none", "important");
		}

		li.style.setProperty("margin", margin || "0", "important");
		wrapper.style.setProperty("margin", "0", "important");
		wrapper.style.setProperty("padding", "0", "important");
		wrapper.style.setProperty("background-color", "transparent", "important");

		if (bg) {
			li.style.setProperty("background-color", bg, "important");
		} else {
			li.style.removeProperty("background-color");
		}

		if (typoColor) {
			li.style.setProperty("color", typoColor, "important");
		} else if (preview && preview.defaultView) {
			var mirrored = builder.normalizeTypographyColor(
				preview.defaultView.getComputedStyle(wrapper).color
			);
			if (mirrored) {
				li.style.setProperty("color", mirrored, "important");
			} else {
				li.style.removeProperty("color");
			}
		} else {
			li.style.removeProperty("color");
		}

		wrapper.style.setProperty("color", "inherit", "important");

		var typoProps = [
			["font-size", typoOverrides.fontSize],
			["line-height", typoOverrides.lineHeight],
			["font-weight", typoOverrides.fontWeight],
			["letter-spacing", typoOverrides.letterSpacing],
			["font-family", typoOverrides.fontFamily],
			["text-transform", typoOverrides.textTransform],
		];
		var i;
		for (i = 0; i < typoProps.length; i++) {
			if (typoProps[i][1]) {
				li.style.setProperty(typoProps[i][0], typoProps[i][1], "important");
			} else {
				li.style.removeProperty(typoProps[i][0]);
			}
		}

		wrapper.style.setProperty("font-size", "inherit", "important");
		wrapper.style.setProperty("line-height", "inherit", "important");
		wrapper.style.setProperty("font-weight", "inherit", "important");
		wrapper.style.setProperty("letter-spacing", "inherit", "important");
		wrapper.style.setProperty("font-family", "inherit", "important");
		wrapper.style.setProperty("text-transform", "inherit", "important");

		if (pad) {
			li.style.setProperty("padding", pad, "important");
		} else {
			li.style.removeProperty("padding");
		}

		if (icon) {
			icon.style.setProperty("width", "auto", "important");
			icon.style.setProperty("height", "auto", "important");
			icon.style.setProperty("flex", "0 0 auto", "important");
			icon.style.setProperty("padding", "0", "important");
			icon.style.setProperty("background", "transparent", "important");
			icon.style.setProperty("border-radius", "0", "important");
			icon.style.setProperty("color", "inherit", "important");
			icon.style.setProperty("font-size", "inherit", "important");
			icon.style.setProperty("line-height", "inherit", "important");
			icon.style.setProperty("font-weight", "inherit", "important");
			icon.style.setProperty("letter-spacing", "inherit", "important");
			icon.style.setProperty("font-family", "inherit", "important");
		} else if (wrapper) {
			wrapper.querySelectorAll(".ecbb-event-card__meta-icon--inline").forEach(function (inlineIcon) {
				inlineIcon.style.setProperty("width", "auto", "important");
				inlineIcon.style.setProperty("height", "auto", "important");
				inlineIcon.style.setProperty("flex", "0 0 auto", "important");
				inlineIcon.style.setProperty("padding", "0", "important");
				inlineIcon.style.setProperty("background", "transparent", "important");
				inlineIcon.style.setProperty("border-radius", "0", "important");
				inlineIcon.style.setProperty("color", "inherit", "important");
				inlineIcon.style.setProperty("font-size", "inherit", "important");
				inlineIcon.style.setProperty("line-height", "inherit", "important");
				inlineIcon.style.setProperty("font-weight", "inherit", "important");
				inlineIcon.style.setProperty("letter-spacing", "inherit", "important");
				inlineIcon.style.setProperty("font-family", "inherit", "important");
			});
		}
	}

	builder.scheduleStyle1GridMetaListRowPreviewStyle = function(repeaterItem) {
		if (!repeaterItem) {
			return;
		}

		builder.syncStyle1GridMetaListRowPreviewStyle(repeaterItem);
		setTimeout(function () {
			builder.syncStyle1GridMetaListRowPreviewStyle(repeaterItem);
		}, 120);
		setTimeout(function () {
			builder.syncStyle1GridMetaListRowPreviewStyle(repeaterItem);
		}, 350);
	}

	builder.readActivePickrColor = function() {
		var app = document.querySelector(".pcr-app.visible");
		if (!app) {
			return "";
		}

		var inputs = app.querySelectorAll('input[type="text"]');
		var i;
		for (i = 0; i < inputs.length; i++) {
			var value = (inputs[i].value || "").trim();
			if (builder.normalizeTypographyColor(value)) {
				return value;
			}
		}

		var swatches = app.querySelectorAll(".pcr-current-color, .pcr-color-preview");
		for (i = 0; i < swatches.length; i++) {
			if (swatches[i].style && swatches[i].style.backgroundColor) {
				var swatchColor = builder.normalizeTypographyColor(
					swatches[i].style.backgroundColor
				);
				if (swatchColor) {
					return swatchColor;
				}
			}
		}

		return "";
	}

	builder.normalizeTypographyColor = function(value) {
		if (!value) {
			return "";
		}
		var trimmed = String(value).trim();
		if (
			!trimmed ||
			trimmed === "transparent" ||
			trimmed === "currentcolor" ||
			trimmed === "inherit" ||
			trimmed === "initial" ||
			trimmed === "unset"
		) {
			return "";
		}
		if (/^rgba?\(/i.test(trimmed)) {
			var rgbaParts = trimmed.match(/[\d.]+/g);
			if (rgbaParts && rgbaParts.length >= 4 && parseFloat(rgbaParts[3]) <= 0) {
				return "";
			}
		}
		return trimmed;
	}

	builder.normalizeCssSizeValue = function(value, fallbackUnit) {
		if (value === null || value === undefined) {
			return "";
		}
		var trimmed = String(value).trim();
		if (!trimmed) {
			return "";
		}
		if (/^-?\d*\.?\d+(px|rem|em|%)$/i.test(trimmed)) {
			return trimmed;
		}
		if (/^-?\d*\.?\d+$/.test(trimmed)) {
			return trimmed + (fallbackUnit || "px");
		}
		return trimmed;
	}

	builder.readTypographyInputFromWrap = function(wrap, fallbackUnit) {
		if (!wrap) {
			return "";
		}
		var input = wrap.querySelector(
			"input[type='number'], input[type='text'], input:not([type='hidden'])"
		);
		if (!input || !input.value) {
			return "";
		}
		var unitSelect = wrap.querySelector("select");
		return builder.normalizeCssSizeValue(
			input.value,
			unitSelect && unitSelect.value ? unitSelect.value : fallbackUnit || "px"
		);
	}

	builder.readTypographyBySettingSelectors = function(controlInner, property) {
		var selectors = [
			'input[data-setting="' + property + '"]',
			'[data-setting="' + property + '"] input',
			'input[name*="' + property + '"]',
			'[data-control="' + property + '"] input',
		];
		var i;
		for (i = 0; i < selectors.length; i++) {
			var input = controlInner.querySelector(selectors[i]);
			if (input && input.value) {
				var value = builder.normalizeCssSizeValue(input.value, "px");
				if (value) {
					return value;
				}
			}
		}
		return "";
	}

	builder.readTypographyByFuzzyInputMeta = function(controlInner, property) {
		var propertyNeedle = property.replace(/-/g, "").toLowerCase();
		var allInputs = controlInner.querySelectorAll(
			"input[type='number'], input[type='text']"
		);
		var i;
		for (i = 0; i < allInputs.length; i++) {
			var inputMeta = (
				(allInputs[i].getAttribute("data-setting") || "") +
				(allInputs[i].getAttribute("name") || "") +
				(allInputs[i].getAttribute("data-name") || "") +
				(allInputs[i].getAttribute("data-control-key") || "")
			).toLowerCase();
			if (
				inputMeta.indexOf(propertyNeedle) !== -1 &&
				allInputs[i].value
			) {
				var matchedValue = builder.normalizeCssSizeValue(allInputs[i].value, "px");
				if (matchedValue) {
					return matchedValue;
				}
			}
		}
		return "";
	}

	builder.readTypographyPropertyValue = function(repeaterItem, property) {
		var controlInner = builder.getRepeaterControlInner(repeaterItem, "ecbb_typography");
		if (!controlInner || !property) {
			return "";
		}

		var bricksControl = controlInner.querySelector(
			'[data-control-key="' +
				property +
				'"], [data-setting="' +
				property +
				'"], .control-' +
				property.replace(/[^a-z0-9_-]/gi, "")
		);
		var fromBricks = builder.readTypographyInputFromWrap(bricksControl, "px");
		if (fromBricks) {
			return fromBricks;
		}

		var keyedWrap = controlInner.querySelector(
			'[data-control-key="' + property + '"], [data-setting="' + property + '"]'
		);
		var fromKeyed = builder.readTypographyInputFromWrap(keyedWrap, "px");
		if (fromKeyed) {
			return fromKeyed;
		}

		var fromSelectors = builder.readTypographyBySettingSelectors(controlInner, property);
		if (fromSelectors) {
			return fromSelectors;
		}

		return builder.readTypographyByFuzzyInputMeta(controlInner, property);
	}

	builder.readPreviewRepeaterCssValue = function(wrapper, preview, cssProperty) {
		if (!wrapper || !preview || !preview.styleSheets) {
			return "";
		}

		var fieldId = wrapper.getAttribute("data-field-id");
		if (!fieldId) {
			return "";
		}

		var prop =
			cssProperty.indexOf("-") !== -1
				? cssProperty
				: cssProperty.replace(/([A-Z])/g, "-$1").toLowerCase();
		var needles = [
			'[data-field-id="' + fieldId + '"] a.event-button',
			'[data-field-id="' + fieldId + '"] a.ecbb-event-card__button',
			'[data-field-id="' + fieldId + '"] .ecbb-event__link',
			'[data-field-id="' + fieldId + '"] .ecbb-event-card__category',
			'[data-field-id="' + fieldId + '"] .ecbb-event__term-chip',
			'[data-field-id="' + fieldId + '"]',
		];
		var sheetIndex;
		var ruleIndex;

		for (sheetIndex = 0; sheetIndex < preview.styleSheets.length; sheetIndex++) {
			var sheet = preview.styleSheets[sheetIndex];
			var rules;
			try {
				rules = sheet.cssRules || sheet.rules;
			} catch (err) {
				continue;
			}
			if (!rules) {
				continue;
			}
			for (ruleIndex = rules.length - 1; ruleIndex >= 0; ruleIndex--) {
				var rule = rules[ruleIndex];
				if (!rule || !rule.selectorText || !rule.style) {
					continue;
				}
				var selectorText = String(rule.selectorText);
				var matched = false;
				var n;
				for (n = 0; n < needles.length; n++) {
					if (selectorText.indexOf(needles[n]) !== -1) {
						matched = true;
						break;
					}
				}
				if (!matched) {
					continue;
				}
				var cssValue = rule.style.getPropertyValue(prop);
				if (cssValue && String(cssValue).trim()) {
					return String(cssValue).trim();
				}
			}
		}

		return "";
	}

	builder.readTypographyControlSnapshot = function(repeaterItem, wrapper, preview, surfaces) {
		var snapshot = {
			color: builder.readTypographyControlColor(repeaterItem, wrapper, preview, surfaces),
			fontSize: builder.normalizeCssSizeValue(
				builder.readTypographyPropertyValue(repeaterItem, "font-size"),
				"px"
			),
			lineHeight: builder.normalizeCssSizeValue(
				builder.readTypographyPropertyValue(repeaterItem, "line-height"),
				"px"
			),
			fontWeight: builder.readTypographyPropertyValue(repeaterItem, "font-weight"),
			letterSpacing: builder.normalizeCssSizeValue(
				builder.readTypographyPropertyValue(repeaterItem, "letter-spacing"),
				"px"
			),
			textTransform: builder.readTypographyPropertyValue(repeaterItem, "text-transform"),
			fontFamily: builder.readTypographyPropertyValue(repeaterItem, "font-family"),
		};

		if (!snapshot.fontSize && wrapper && preview) {
			snapshot.fontSize = builder.normalizeCssSizeValue(
				builder.readPreviewRepeaterCssValue(wrapper, preview, "font-size"),
				"px"
			);
		}
		if (!snapshot.lineHeight && wrapper && preview) {
			snapshot.lineHeight = builder.normalizeCssSizeValue(
				builder.readPreviewRepeaterCssValue(wrapper, preview, "line-height"),
				"px"
			);
		}

		if (surfaces && surfaces.length && preview && preview.defaultView) {
			var surfaceComputed = preview.defaultView.getComputedStyle(surfaces[0]);
			if (
				!snapshot.fontSize &&
				surfaceComputed &&
				surfaceComputed.fontSize &&
				surfaceComputed.fontSize !== "0px"
			) {
				snapshot.fontSize = builder.normalizeCssSizeValue(surfaceComputed.fontSize, "px");
			}
			if (!snapshot.lineHeight && surfaceComputed && surfaceComputed.lineHeight) {
				snapshot.lineHeight = builder.normalizeCssSizeValue(surfaceComputed.lineHeight, "px");
			}
			if (!snapshot.fontWeight && surfaceComputed && surfaceComputed.fontWeight) {
				snapshot.fontWeight = surfaceComputed.fontWeight;
			}
			if (!snapshot.letterSpacing && surfaceComputed && surfaceComputed.letterSpacing) {
				snapshot.letterSpacing = builder.normalizeCssSizeValue(
					surfaceComputed.letterSpacing,
					"px"
				);
			}
			if (
				!snapshot.textTransform &&
				surfaceComputed &&
				surfaceComputed.textTransform &&
				surfaceComputed.textTransform !== "none"
			) {
				snapshot.textTransform = surfaceComputed.textTransform;
			}
			if (!snapshot.fontFamily && surfaceComputed && surfaceComputed.fontFamily) {
				snapshot.fontFamily = surfaceComputed.fontFamily;
			}
		}

		return snapshot;
	}

	builder.setWrapperTypographyFlags = function(wrapper, repeaterItem, typoColor, typoOverrides) {
		if (!wrapper) {
			return;
		}
		typoOverrides = typoOverrides || {};
		wrapper.classList.toggle("ecbb-has-typo-fg", !!typoColor);
		wrapper.classList.toggle("ecbb-has-typo-size", !!typoOverrides.fontSize);
		if (typoColor) {
			wrapper.style.setProperty("--ecbb-btn-fg", typoColor);
			if (
				repeaterItem &&
				builder.readRepeaterPartSlug(repeaterItem) === "categories"
			) {
				wrapper.style.setProperty("--ecbb-chip-fg", typoColor);
			}
		} else {
			wrapper.style.removeProperty("--ecbb-btn-fg");
			wrapper.style.removeProperty("--ecbb-chip-fg");
		}
		if (typoOverrides.fontSize) {
			wrapper.style.setProperty("--ecbb-btn-font-size", typoOverrides.fontSize);
		} else {
			wrapper.style.removeProperty("--ecbb-btn-font-size");
		}
		if (typoOverrides.lineHeight) {
			wrapper.style.setProperty("--ecbb-btn-line-height", typoOverrides.lineHeight);
		} else {
			wrapper.style.removeProperty("--ecbb-btn-line-height");
		}
	}

	builder.readTextAlignControlValue = function(repeaterItem) {
		var controlInner = builder.getRepeaterControlInner(repeaterItem, "ecbb_text_align");
		if (!controlInner) {
			return "";
		}

		var active = controlInner.querySelector(
			".control-button.active, .control-button.is-active, [aria-pressed='true']"
		);
		if (active) {
			var fromActive =
				active.getAttribute("data-value") ||
				active.getAttribute("data-align") ||
				active.getAttribute("value") ||
				"";
			fromActive = String(fromActive).trim().toLowerCase();
			if (
				fromActive === "left" ||
				fromActive === "center" ||
				fromActive === "right" ||
				fromActive === "justify"
			) {
				return fromActive;
			}
		}

		var hidden = controlInner.querySelector('input[type="hidden"], input[type="text"]');
		if (hidden && hidden.value) {
			var hiddenValue = String(hidden.value).trim().toLowerCase();
			if (
				hiddenValue === "left" ||
				hiddenValue === "center" ||
				hiddenValue === "right" ||
				hiddenValue === "justify"
			) {
				return hiddenValue;
			}
		}

		return "";
	}

	builder.readLayoutActionButtonTypographyOverrides = function(repeaterItem, wrapper, preview, surfaces) {
		return builder.readTypographyControlSnapshot(repeaterItem, wrapper, preview, surfaces);
	}

	builder.isLayoutOwnedPreviewNode = function(node, repeaterItem) {
		if (!node || !repeaterItem) {
			return false;
		}
		var part = builder.readRepeaterPartSlug(repeaterItem);
		if (
			( part === "read_more" ||
				part === "event_tickets" ||
				part === "event_rsvp" ) &&
			!builder.isStyledButtonModeEnabled(repeaterItem) &&
			node.matches(
				"a.event-button, a.ecbb-event-card__button, .ecbb-event__link"
			)
		) {
			return true;
		}
		if (
			part === "categories" &&
			node.matches(
				".ecbb-event__term-chip, .ecbb-event-card__category, a.ecbb-event-card__category"
			)
		) {
			return true;
		}
		return false;
	}

	builder.clearMirroredPreviewInlineStyles = function(repeaterItem) {
		var ctx = builder.resolvePreviewRowContext(repeaterItem);
		if (!ctx) {
			return;
		}
		var wrapper = ctx.wrapper;
		wrapper
			.querySelectorAll(
				".ecbb-event__term-chip, .ecbb-event__link, .ecbb-event__term, a.event-button, a.ecbb-event-card__button, .ecbb-event-card__category"
			)
			.forEach(function (node) {
				if (!builder.isLayoutOwnedPreviewNode(node, repeaterItem)) {
					return;
				}
				node.style.removeProperty("color");
				node.style.removeProperty("background-color");
				node.style.removeProperty("padding");
				node.style.removeProperty("font-size");
				node.style.removeProperty("line-height");
				node.style.removeProperty("font-family");
				node.style.removeProperty("font-weight");
				node.style.removeProperty("letter-spacing");
				node.style.removeProperty("text-transform");
				node.style.removeProperty("vertical-align");
			});
	}

	/**
	 * Bricks repeater live CSS ignores child selectors in the builder; mirror wrapper
	 * color onto category chips / links so typography color updates instantly.
	 */
	builder.syncTypographyColorToPreviewTargets = function(repeaterItem) {
		var ctx = builder.resolvePreviewRowContext(repeaterItem, { needView: true });
		if (!ctx) {
			return;
		}
		var preview = ctx.preview;
		var wrapper = ctx.wrapper;

		if (builder.isStyle1OrGridMetaListPreviewRow(wrapper, preview)) {
			builder.scheduleStyle1GridMetaListRowPreviewStyle(repeaterItem);
			return;
		}

		var typoColor = builder.readTypographyControlColor(
			repeaterItem,
			wrapper,
			preview
		);
		if (!typoColor) {
			wrapper.classList.remove("ecbb-has-typo-fg");
			wrapper.style.removeProperty("--ecbb-btn-fg");
			wrapper.style.removeProperty("--ecbb-chip-fg");
			if (
				builder.isLayoutActionButtonPart(repeaterItem) &&
				!builder.isStyledButtonModeEnabled(repeaterItem)
			) {
				return;
			}
			if (builder.readRepeaterPartSlug(repeaterItem) === "categories") {
				return;
			}
			builder.clearMirroredPreviewInlineStyles(repeaterItem);
			return;
		}

		builder.setWrapperTypographyFlags(wrapper, repeaterItem, typoColor, {});

		var targets = wrapper.querySelectorAll(
			".ecbb-event__term-chip, .ecbb-event__link, .ecbb-event__term, .ecbb-event__date-day, .ecbb-event__date-time, .ecbb-event__date-sep, a.event-button, a.ecbb-event-card__button, .ecbb-event-card__category"
		);

		targets.forEach(function (node) {
			if (builder.isLayoutOwnedPreviewNode(node, repeaterItem)) {
				return;
			}
			node.style.setProperty("color", typoColor);
		});
	}

	/** View Details / tickets / RSVP: Bricks paints the row wrapper; paint the inner button. */
	builder.syncLayoutActionButtonPreviewStyle = function(repeaterItem) {
		if (!repeaterItem || !builder.isLayoutActionButtonPart(repeaterItem)) {
			return;
		}
		if (builder.isStyledButtonModeEnabled(repeaterItem)) {
			return;
		}

		var ctx = builder.resolvePreviewRowContext(repeaterItem, { needView: true });
		if (!ctx) {
			return;
		}
		var preview = ctx.preview;
		var wrapper = ctx.wrapper;

		var surfaces = builder.findLayoutActionButtonSurfaces(wrapper);
		if (!surfaces.length) {
			return;
		}

		if (builder.isStyle2CardActionButtonContext(preview, wrapper)) {
			builder.syncStyle2ActionButtonTypographyColor(
				repeaterItem,
				wrapper,
				preview,
				surfaces
			);
			return;
		}

		var bg = builder.readColorControlValue(builder.getRepeaterControlInner(repeaterItem, "ecbb_background"));
		var pad = builder.readSpacingControlValue(builder.getRepeaterControlInner(repeaterItem, "ecbb_padding"));
		var typoOverrides = builder.readLayoutActionButtonTypographyOverrides(
			repeaterItem,
			wrapper,
			preview,
			surfaces
		);
		var typoColor =
			builder.readTypographyControlColor(repeaterItem, wrapper, preview, surfaces) ||
			typoOverrides.color;
		var textAlign = builder.readTextAlignControlValue(repeaterItem);
		var computed = preview.defaultView.getComputedStyle(
			surfaces[0] || wrapper
		);

		builder.setWrapperTypographyFlags(wrapper, repeaterItem, typoColor, typoOverrides);
		builder.syncLayoutButtonTypographyPreviewRule(repeaterItem, typoOverrides);

		builder.collapseRepeaterWrapperTypographyShell(wrapper);
		if (textAlign) {
			wrapper.style.setProperty("text-align", textAlign, "important");
		}

		surfaces.forEach(function (node) {
			if (bg) {
				node.style.setProperty("background-color", bg, "important");
			}
			if (pad) {
				node.style.setProperty("padding", pad, "important");
			}
			builder.applyTypographyToButtonPreviewSurface(node, computed, typoColor, typoOverrides);
		});
	}

	/** Categories (Style 1 chips + Style 2 pills): Bricks paints the wrapper; paint each button. */
	builder.syncCategoryChipPreviewStyle = function(repeaterItem) {
		var ctx = builder.resolvePreviewRowContext(repeaterItem, {
			matches: function (item) {
				return builder.readRepeaterPartSlug(item) === "categories";
			},
			needView: true,
		});
		if (!ctx) {
			return;
		}
		var preview = ctx.preview;
		var wrapper = ctx.wrapper;

		var bg = builder.readColorControlValue(builder.getRepeaterControlInner(repeaterItem, "ecbb_background"));
		var pad = builder.readSpacingControlValue(builder.getRepeaterControlInner(repeaterItem, "ecbb_padding"));
		var chips = wrapper.querySelectorAll(
			".ecbb-event__term-chip, .ecbb-event-card__category"
		);
		var typoOverrides = builder.readTypographyControlSnapshot(
			repeaterItem,
			wrapper,
			preview,
			chips.length ? chips : null
		);
		var typoColor =
			builder.readTypographyControlColor(
				repeaterItem,
				wrapper,
				preview,
				chips.length ? chips : null
			) || typoOverrides.color;

		if (!bg && !pad && !typoColor && !typoOverrides.fontSize && !typoOverrides.lineHeight) {
			builder.clearMirroredPreviewInlineStyles(repeaterItem);
			wrapper.classList.remove("ecbb-has-typo-fg", "ecbb-has-typo-size");
			return;
		}

		builder.collapseRepeaterWrapperTypographyShell(wrapper);
		builder.setWrapperTypographyFlags(wrapper, repeaterItem, typoColor, typoOverrides);

		if (bg || pad) {
			wrapper.style.setProperty("background-color", "transparent", "important");
			if (pad) {
				wrapper.style.setProperty("padding", "0", "important");
			}
		}

		chips.forEach(function (chip) {
			if (bg) {
				chip.style.setProperty("background-color", bg);
			} else {
				chip.style.removeProperty("background-color");
			}
			if (pad) {
				chip.style.setProperty("padding", pad);
			} else {
				chip.style.removeProperty("padding");
			}
			builder.applyTypographyToButtonPreviewSurface(chip, null, typoColor, typoOverrides);
		});
	}

	/** Style 2 meta rows (venue / timing / cost): paint the leading icon sibling. */
	builder.syncStyle2MetaIconPreviewStyle = function(repeaterItem) {
		var ctx = builder.resolvePreviewRowContext(repeaterItem, {
			matches: function (item) {
				return builder.config.style2MetaIconParts.indexOf(builder.readRepeaterPartSlug(item)) !== -1;
			},
			needView: true,
			wrapperTest: function (wrapper, preview) {
				return builder.isStyle2LayoutPreview(preview);
			},
		});
		if (!ctx) {
			return;
		}
		var preview = ctx.preview;
		var wrapper = ctx.wrapper;

		var li = wrapper.closest("li.ecbb-event-card__meta-item");
		if (!li) {
			return;
		}

		var icon = li.querySelector(":scope > .ecbb-event-card__meta-icon");
		var icons = [];
		if (icon) {
			icons.push(icon);
		} else {
			wrapper.querySelectorAll(".ecbb-event-card__meta-icon").forEach(function (inlineIcon) {
				icons.push(inlineIcon);
			});
		}
		if (!icons.length) {
			return;
		}

		var ul = li.closest("ul.ecbb-event-card__meta");
		var margin = builder.readSpacingControlValue(
			builder.getRepeaterControlInner(repeaterItem, "ecbb_margin")
		);
		if (ul) {
			ul.style.setProperty("margin", "0", "important");
			ul.style.setProperty("padding", "0", "important");
			ul.style.setProperty("list-style", "none", "important");
		}
		if (margin) {
			li.style.setProperty("margin", margin, "important");
		} else {
			li.style.setProperty("margin", "0", "important");
		}
		wrapper.style.setProperty("margin", "0", "important");

		var color = builder.readColorControlValue(
			builder.getRepeaterControlInner(repeaterItem, "ecbb_meta_icon_color")
		);
		var bg = builder.readColorControlValue(
			builder.getRepeaterControlInner(repeaterItem, "ecbb_meta_icon_background")
		);
		var typoOverrides = builder.readTypographyControlSnapshot(
			repeaterItem,
			wrapper,
			preview,
			null
		);
		if (!color) {
			color =
				builder.readTypographyControlColor(
					repeaterItem,
					wrapper,
					preview,
					null
				) || typoOverrides.color;
		}

		if (color) {
			icons.forEach(function (metaIcon) {
				metaIcon.style.setProperty("color", color, "important");
			});
		} else {
			icons.forEach(function (metaIcon) {
				metaIcon.style.removeProperty("color");
			});
		}

		if (bg) {
			icons.forEach(function (metaIcon) {
				metaIcon.style.setProperty("background-color", bg, "important");
			});
		} else {
			icons.forEach(function (metaIcon) {
				metaIcon.style.removeProperty("background-color");
			});
		}

		if (typoOverrides.fontSize) {
			icons.forEach(function (metaIcon) {
				metaIcon.style.setProperty("font-size", typoOverrides.fontSize, "important");
			});
		} else {
			icons.forEach(function (metaIcon) {
				metaIcon.style.removeProperty("font-size");
			});
		}
		if (typoOverrides.lineHeight) {
			icons.forEach(function (metaIcon) {
				metaIcon.style.setProperty("line-height", typoOverrides.lineHeight, "important");
			});
		} else {
			icons.forEach(function (metaIcon) {
				metaIcon.style.removeProperty("line-height");
			});
		}
	}

	builder.syncTitleInnerBackgroundPreview = function(repeaterItem) {
		var ctx = builder.resolvePreviewRowContext(repeaterItem, {
			matches: function (item) {
				return builder.readRepeaterPartControlValue(item) === "title";
			},
		});
		if (!ctx) {
			return;
		}
		var wrapper = ctx.wrapper;

		var bg = builder.readColorControlValue(builder.getRepeaterControlInner(repeaterItem, "ecbb_background"));
		if (!bg) {
			bg = builder.readColorControlValue(builder.getRepeaterControlInner(repeaterItem, "ecbb_background_inner"));
		}

		wrapper.style.setProperty("background-color", "transparent", "important");

		wrapper
			.querySelectorAll(".ecbb-event__link, .ecbb-event__title-text")
			.forEach(function (node) {
				if (bg) {
					node.style.setProperty("background-color", bg, "important");
					node.style.setProperty("display", "inline-block");
					node.style.setProperty("width", "fit-content");
					node.style.setProperty("max-width", "100%");
				} else {
					node.style.removeProperty("background-color");
				}
			});
	}

	builder.scheduleTitleInnerBackgroundPreviewSync = function(repeaterItem) {
		if (builder.sync.titleInnerBackground) {
			builder.sync.titleInnerBackground.schedule(repeaterItem);
			return;
		}
		builder.syncTitleInnerBackgroundPreview(repeaterItem);
	}

	builder.syncStyledButtonCssColorVariable = function(repeaterItem) {
		if (!repeaterItem || !builder.isStyledButtonModeEnabled(repeaterItem)) {
			return;
		}

		var ctx = builder.resolvePreviewRowContext(repeaterItem);
		if (!ctx) {
			return;
		}
		var preview = ctx.preview;
		var wrapper = ctx.wrapper;

		if (builder.isStyle2CardActionButtonContext(preview, wrapper)) {
			return;
		}

		var surface = builder.findStyledButtonPreviewSurface(wrapper);
		var textColor = builder.readTypographyControlColor(
			repeaterItem,
			wrapper,
			preview,
			surface ? [surface] : null
		);
		if (textColor) {
			wrapper.style.setProperty("--ecbb-btn-fg", textColor);
			wrapper.classList.add("ecbb-has-typo-fg");
		}
	}

	builder.runTypographyPreviewSyncChain = function(repeaterItem) {
		var part = builder.readRepeaterPartSlug(repeaterItem);

		builder.syncTypographyColorToPreviewTargets(repeaterItem);
		builder.syncStyledButtonCssColorVariable(repeaterItem);

		if (part === "categories") {
			builder.syncCategoryChipPreviewStyle(repeaterItem);
		}
		if (builder.config.style2MetaIconParts.indexOf(part) !== -1) {
			builder.syncStyle2MetaIconPreviewStyle(repeaterItem);
		}

		if (builder.isLayoutActionButtonPart(repeaterItem)) {
			if (builder.isStyledButtonModeEnabled(repeaterItem)) {
				builder.syncStyledButtonPreviewPaint(repeaterItem);
			} else {
				builder.syncLayoutActionButtonPreviewStyle(repeaterItem);
			}
		}

		builder.scheduleHoverPreviewStyleSync(repeaterItem);
	}

	builder.readHoverControlColor = function(repeaterItem, key) {
		var color = builder.readColorControlValue(builder.getRepeaterControlInner(repeaterItem, key));
		return builder.isVisibleHoverColor(color) ? color : "";
	}

	builder.readHoverTextControlColor = function(repeaterItem) {
		return builder.readHoverControlColor(repeaterItem, "ecbb_hover_color");
	}

	builder.readHoverBackgroundControlColor = function(repeaterItem) {
		return builder.readHoverControlColor(repeaterItem, "ecbb_hover_background");
	}

	builder.isVisibleHoverColor = function(value) {
		if (!value || typeof value !== "string") {
			return false;
		}
		var trimmed = value.trim();
		var lower = trimmed.toLowerCase();
		if (
			lower === "" ||
			lower === "transparent" ||
			lower === "currentcolor" ||
			lower === "inherit" ||
			lower === "initial" ||
			lower === "unset"
		) {
			return false;
		}
		if (/^rgba?\(/i.test(trimmed)) {
			var rgbaParts = trimmed.match(/[\d.]+/g);
			if (rgbaParts && rgbaParts.length >= 4 && parseFloat(rgbaParts[3]) <= 0) {
				return false;
			}
		}
		if (/^hsla?\(/i.test(trimmed)) {
			var hslaParts = trimmed.match(/[\d.]+/g);
			if (hslaParts && hslaParts.length >= 4 && parseFloat(hslaParts[3]) <= 0) {
				return false;
			}
		}
		return true;
	}

	builder.isStyle2LayoutPreview = function(preview) {
		return !!(
			preview &&
			preview.querySelector(
				".ecbb-ev__item--style-2, .ecbb-ev__item-inner--style-2"
			)
		);
	}

	builder.isStyle1OrGridMetaListPreviewRow = function(wrapper, preview) {
		if (!wrapper || !preview || builder.isStyle2LayoutPreview(preview)) {
			return false;
		}
		var li = wrapper.closest("li");
		return !!(li && li.closest(".event-meta"));
	}

	builder.syncStyle1GridMetaListRowPreviewStyle = function(repeaterItem) {
		var ctx = builder.resolvePreviewRowContext(repeaterItem, {
			wrapperTest: function (wrapper, preview) {
				return builder.isStyle1OrGridMetaListPreviewRow(wrapper, preview);
			},
		});
		if (!ctx) {
			return;
		}
		var preview = ctx.preview;
		var wrapper = ctx.wrapper;

		var li = wrapper.closest("li");
		if (!li) {
			return;
		}

		var icon = li.querySelector(":scope > .ecbb-event-card__meta-icon");
		builder.applyUnifiedMetaListRowPreviewChrome(
			repeaterItem,
			li,
			wrapper,
			icon,
			preview
		);
		builder.mirrorMetaRowIconColorFromText(wrapper, preview);
	}

	builder.repeaterHasCustomHoverColors = function(repeaterItem) {
		return !!(
			builder.readHoverTextControlColor(repeaterItem) ||
			builder.readHoverBackgroundControlColor(repeaterItem)
		);
	}

	builder.findPreviewPartElementForRepeaterRow = function(repeaterItem, preview) {
		if (!preview || !repeaterItem) {
			return null;
		}

		var rowId = builder.readRepeaterRowId(repeaterItem);
		if (rowId) {
			var byId = preview.querySelector('[data-field-id="' + rowId + '"]');
			if (byId) {
				return byId;
			}
		}

		var items = repeaterItem.parentNode
			? repeaterItem.parentNode.querySelectorAll(".ecbb-parts-repeater-item")
			: [];
		var rowIndex = -1;
		var i;
		for (i = 0; i < items.length; i++) {
			if (items[i] === repeaterItem) {
				rowIndex = i;
				break;
			}
		}
		if (rowIndex >= 0) {
			var byClass = preview.querySelector(".ecbb-p" + rowIndex);
			if (byClass) {
				return byClass;
			}
		}

		return null;
	}

	builder.isPartHoverPreviewEnabled = function(repeaterItem) {
		if (!repeaterItem) {
			return false;
		}
		var part = builder.readRepeaterPartSlug(repeaterItem);
		if (!builder.partSupportsHoverControls(part, repeaterItem)) {
			return false;
		}
		if (builder.repeaterHasCustomHoverColors(repeaterItem)) {
			return true;
		}
		return builder.isHoverStylingEnabledInPanel(repeaterItem);
	}

	builder.buildHoverPreviewSelectorScope = function(rowId) {
		return '[data-field-id="' + rowId + '"]';
	}

	builder.buildHoverPreviewBackgroundSelectors = function(scope) {
		return (
			scope +
			" .ecbb-event__term-chip:hover," +
			scope +
			" .ecbb-event__link:hover," +
			scope +
			" > .ecbb-event__link:hover," +
			scope +
			" a.event-button:hover," +
			scope +
			" > a.event-button:hover," +
			scope +
			" a.ecbb-event-card__button:hover," +
			scope +
			" > a.ecbb-event-card__button:hover," +
			scope +
			" .ecbb-event__term:hover," +
			scope +
			" .ecbb-event__title-text:hover," +
			scope +
			" .ecbb-event-card__category:hover," +
			"li:has(> " +
			scope +
			"):hover > .ecbb-event-card__meta-icon"
		);
	}

	builder.buildHoverPreviewColorSelectors = function(scope) {
		return (
			scope +
			" .ecbb-event__term-chip:hover," +
			scope +
			" .ecbb-event__link:hover," +
			scope +
			" > .ecbb-event__link:hover," +
			scope +
			" a.event-button:hover," +
			scope +
			" > a.event-button:hover," +
			scope +
			" a.ecbb-event-card__button:hover," +
			scope +
			" > a.ecbb-event-card__button:hover," +
			scope +
			" .ecbb-event__term:hover," +
			scope +
			" .ecbb-event__title-text:hover," +
			scope +
			" .ecbb-event-card__category:hover," +
			"li:has(> " +
			scope +
			"):hover > .ecbb-event-card__meta-icon"
		);
	}

	builder.getOrCreateBuilderPreviewStyleElement = function(preview, styleId) {
		var doc =
			preview && preview.defaultView
				? preview.defaultView.document
				: document;
		var el = doc.getElementById(styleId);
		if (!el) {
			el = doc.createElement("style");
			el.id = styleId;
			doc.head.appendChild(el);
		}
		return el;
	}

	builder.getOrCreateHoverPreviewStyleElement = function(preview) {
		return builder.getOrCreateBuilderPreviewStyleElement(preview, "ecbb-builder-hover-css");
	}

	builder.rebuildLayoutButtonTypoPreviewStylesheet = function(preview) {
		var el = builder.getOrCreateBuilderPreviewStyleElement(
			preview,
			"ecbb-builder-layout-btn-typo-css"
		);
		if (!el) {
			return;
		}
		var chunks = [];
		if (builder.hover.layoutBtnTypoRulesByItem) {
			builder.hover.layoutBtnTypoRulesByItem.forEach(function (rule) {
				if (rule) {
					chunks.push(rule);
				}
			});
		} else {
			Object.keys(builder.hover.layoutBtnTypoRulesByRowId).forEach(
				function (key) {
					if (builder.hover.layoutBtnTypoRulesByRowId[key]) {
						chunks.push(builder.hover.layoutBtnTypoRulesByRowId[key]);
					}
				}
			);
		}
		el.textContent = chunks.join("\n");
	}

	builder.buildLayoutButtonTypographyPreviewRule = function(rowId, typoOverrides) {
		if (!rowId || !typoOverrides) {
			return "";
		}

		var scope = '[data-field-id="' + rowId + '"]';
		var surfaceSelectors =
			scope +
			" a.event-button," +
			scope +
			" > a.event-button," +
			scope +
			" > .ecbb-event__link," +
			scope +
			" .ecbb-event__link," +
			scope +
			" a.ecbb-event-card__button," +
			scope +
			" > a.ecbb-event-card__button";
		var rule = "";

		if (typoOverrides.fontSize) {
			rule += surfaceSelectors + "{font-size:" + typoOverrides.fontSize + " !important;";
			if (typoOverrides.lineHeight) {
				rule += "line-height:" + typoOverrides.lineHeight + " !important;";
			}
			rule += "}";
		}

		if (typoOverrides.color) {
			rule +=
				surfaceSelectors +
				"{color:" +
				typoOverrides.color +
				" !important;}";
		}

		if (typoOverrides.fontSize || typoOverrides.lineHeight) {
			rule += scope + "{font-size:0 !important;line-height:0 !important;}";
		}

		return rule;
	}

	builder.syncLayoutButtonTypographyPreviewRule = function(repeaterItem, typoOverrides) {
		var ctx = builder.resolvePreviewRowContext(repeaterItem);
		if (!ctx) {
			return;
		}
		if (builder.isStyle2CardActionButtonContext(ctx.preview, ctx.wrapper)) {
			return;
		}

		var rowId = ctx.rowId;
		var preview = ctx.preview;
		var rule = builder.buildLayoutButtonTypographyPreviewRule(rowId, typoOverrides || {});
		if (builder.hover.layoutBtnTypoRulesByItem) {
			if (rule) {
				builder.hover.layoutBtnTypoRulesByItem.set(repeaterItem, rule);
			} else {
				builder.hover.layoutBtnTypoRulesByItem.delete(repeaterItem);
			}
		} else if (rule) {
			builder.hover.layoutBtnTypoRulesByRowId[rowId] = rule;
		} else {
			delete builder.hover.layoutBtnTypoRulesByRowId[rowId];
		}

		builder.rebuildLayoutButtonTypoPreviewStylesheet(preview);
	}

	builder.rebuildHoverPreviewStylesheet = function(preview) {
		var el = builder.getOrCreateHoverPreviewStyleElement(preview);
		if (!el) {
			return;
		}
		var chunks = [];
		if (builder.hover.rulesByItem) {
			builder.hover.rulesByItem.forEach(function (rule) {
				if (rule) {
					chunks.push(rule);
				}
			});
		} else {
			Object.keys(builder.hover.rulesByRowId).forEach(function (key) {
				if (builder.hover.rulesByRowId[key]) {
					chunks.push(builder.hover.rulesByRowId[key]);
				}
			});
		}
		el.textContent = chunks.join("\n");
	}

	builder.syncPreviewHoverStateClass = function(repeaterItem) {
		var preview = builder.getBricksPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}

		var wrapper = builder.findPreviewPartElementForRepeaterRow(repeaterItem, preview);
		if (!wrapper) {
			return;
		}

		var useHoverInner = builder.getRepeaterControlInner(repeaterItem, "ecbb_use_hover");
		if (useHoverInner) {
			wrapper.classList.toggle("ecbb-no-hover", !builder.isPartHoverPreviewEnabled(repeaterItem));
		}
	}

	builder.syncHoverPreviewStyleRules = function(repeaterItem) {
		var preview = builder.getBricksPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}

		builder.syncPreviewHoverStateClass(repeaterItem);

		var wrapper = builder.findPreviewPartElementForRepeaterRow(repeaterItem, preview);
		if (!wrapper) {
			return;
		}

		var rowId = builder.readRepeaterRowId(repeaterItem);
		var enabled = builder.isPartHoverPreviewEnabled(repeaterItem);
		var hoverColor = builder.readHoverTextControlColor(repeaterItem);
		var hoverBg = builder.readHoverBackgroundControlColor(repeaterItem);

		if (enabled && hoverColor) {
			wrapper.style.setProperty("--ecbb-hover-fg", hoverColor);
		} else {
			wrapper.style.removeProperty("--ecbb-hover-fg");
		}
		if (enabled && hoverBg) {
			wrapper.style.setProperty("--ecbb-hover-bg", hoverBg);
		} else {
			wrapper.style.removeProperty("--ecbb-hover-bg");
		}
		wrapper.classList.toggle("ecbb-has-hover-fg", !!(enabled && hoverColor));
		wrapper.classList.toggle("ecbb-has-hover-bg", !!(enabled && hoverBg));

		if (!rowId) {
			return;
		}

		var scope = builder.buildHoverPreviewSelectorScope(rowId);
		var rule = "";
		if (enabled) {
			if (
				builder.readRepeaterPartSlug(repeaterItem) === "categories" &&
				builder.isStyle2LayoutPreview(preview)
			) {
				rule +=
					scope +
					" a.ecbb-event-card__category:hover{color:#0d55d8!important;background-color:#d4e4ff!important;}";
			}
			if (hoverColor) {
				rule +=
					scope +
					" .ecbb-event-card__category:hover{color:" +
					hoverColor +
					" !important;}";
			}
			if (hoverBg) {
				rule +=
					scope +
					" .ecbb-event-card__category:hover{background-color:" +
					hoverBg +
					" !important;}";
			}
			if (hoverColor) {
				rule += builder.buildHoverPreviewColorSelectors(scope) + "{color:" + hoverColor + " !important;}";
			}
			if (hoverBg) {
				rule += builder.buildHoverPreviewBackgroundSelectors(scope) + "{background-color:" + hoverBg + " !important;}";
			}
		}

		if (builder.hover.rulesByItem) {
			builder.hover.rulesByItem.set(repeaterItem, rule);
		} else {
			builder.hover.rulesByRowId[rowId] = rule;
		}

		builder.rebuildHoverPreviewStylesheet(preview);
	}

	builder.scheduleHoverPreviewStyleSync = function(repeaterItem) {
		if (builder.sync.hover) {
			builder.sync.hover.schedule(repeaterItem);
			return;
		}
		builder.syncHoverPreviewStyleRules(repeaterItem);
	}
})(window.ECBB.builder);

