(function () {
	"use strict";

	/**
	 * Bricks builder helpers for ECBB event-parts repeater rows:
	 * - Content / Style tab UI in the panel
	 * - Live preview style mirroring (typography, backgrounds, hover, buttons, meta rows)
	 */

	var builderConfig = typeof ECBBBuilder !== "undefined" ? ECBBBuilder : {};
	var REPEATER_TAB_CONTENT_LABEL = builderConfig.tabContent || "CONTENT";
	var REPEATER_TAB_STYLE_LABEL = builderConfig.tabStyle || "STYLE";
	var HOVER_CAPABLE_PART_SLUGS = Array.isArray(builderConfig.hoverParts) ? builderConfig.hoverParts : [
		"title",
		"categories",
		"tags",
		"read_more",
		"event_tickets",
		"event_rsvp",
		"image",
	];
	var STYLE2_META_ICON_PART_SLUGS = [
		"venue",
		"date",
		"event_date",
		"event_time",
		"event_day",
		"event_cost",
	];

	function readRepeaterPartControlValue(item) {
		var partInner = getRepeaterControlInner(item, "part");
		if (!partInner) {
			return "";
		}

		var select = partInner.querySelector("select");
		if (select && select.value) {
			return select.value;
		}

		var inputs = partInner.querySelectorAll("input");
		var i;
		for (i = 0; i < inputs.length; i++) {
			if (inputs[i].value) {
				return inputs[i].value;
			}
		}

		var option = partInner.querySelector(
			".select-option.active, .select-option.is-active, .option.active"
		);
		if (option) {
			var fromData =
				option.getAttribute("data-value") || option.getAttribute("value");
			if (fromData) {
				return fromData;
			}
		}

		return "";
	}

	function readRepeaterPartSlug(repeaterItem) {
		if (!repeaterItem) {
			return "";
		}
		var part = readRepeaterPartControlValue(repeaterItem);
		if (part) {
			return part;
		}
		return repeaterItem.getAttribute("data-ecbb-part") || "";
	}

	function partSupportsHoverControls(part, item) {
		if (part === "" || HOVER_CAPABLE_PART_SLUGS.indexOf(part) === -1) {
			return false;
		}
		if (part === "title" && item && !isTitleLinkEnabledInPanel(item)) {
			return false;
		}
		return true;
	}

	function isTitleLinkEnabledInPanel(item) {
		if (!item) {
			return false;
		}
		var inner = getRepeaterControlInner(item, "link");
		if (!inner) {
			return false;
		}
		var cb = inner.querySelector('input[type="checkbox"]');
		if (cb) {
			return cb.checked;
		}
		var toggle = inner.querySelector("[aria-checked]");
		if (toggle) {
			return toggle.getAttribute("aria-checked") === "true";
		}
		var select = inner.querySelector("select");
		if (select) {
			return select.value !== "no" && select.value !== "0" && select.value !== "";
		}
		return false;
	}

	function isHoverStylingEnabledInPanel(item) {
		if (!item) {
			return true;
		}

		var inner = getRepeaterControlInner(item, "ecbb_use_hover");
		if (!inner) {
			return true;
		}

		var select = inner.querySelector("select");
		if (select) {
			return select.value !== "no" && select.value !== "0";
		}

		var cb = inner.querySelector('input[type="checkbox"]');
		if (cb) {
			return cb.checked;
		}

		var toggle = inner.querySelector("[aria-checked]");
		if (toggle) {
			return toggle.getAttribute("aria-checked") === "true";
		}

		return true;
	}

	function syncRepeaterHoverToggleAttribute(item) {
		if (!item || !item.classList.contains("ecbb-parts-repeater-item")) {
			return;
		}

		var part = readRepeaterPartControlValue(item);
		if (!partSupportsHoverControls(part, item)) {
			item.removeAttribute("data-ecbb-use-hover");
			return;
		}

		item.setAttribute(
			"data-ecbb-use-hover",
			isHoverStylingEnabledInPanel(item) ? "true" : "false"
		);
	}

	function syncRepeaterHoverPanelState(item) {
		if (!item || !item.classList.contains("ecbb-parts-repeater-item")) {
			return;
		}

		var part = readRepeaterPartSlug(item);
		item.setAttribute("data-ecbb-part", part);
		item.setAttribute(
			"data-ecbb-title-link",
			part === "title" && isTitleLinkEnabledInPanel(item) ? "true" : "false"
		);
		item.classList.toggle("ecbb-part-no-hover", part !== "" && !partSupportsHoverControls(part, item));
		syncRepeaterHoverToggleAttribute(item);
	}

	function ensureRepeaterAccordionDefaultState(item) {
		if (!item || !item.classList || !item.classList.contains("ecbb-parts-repeater-item")) {
			return;
		}
		if (!item.hasAttribute("data-ecbb-hover-open")) {
			item.setAttribute("data-ecbb-hover-open", "true");
		}
		if (!item.hasAttribute("data-ecbb-btn-border-open")) {
			item.setAttribute("data-ecbb-btn-border-open", "true");
		}
	}

	function bindRepeaterAccordionToggle(item, sepKey, attrName) {
		var sep = getRepeaterControlInner(item, sepKey);
		if (!sep || sep.getAttribute("data-ecbb-accordion-bound") === "1") {
			return;
		}
		sep.setAttribute("data-ecbb-accordion-bound", "1");
		sep.classList.add("ecbb-accordion-sep");
		sep.addEventListener(
			"click",
			function (e) {
				// Don't toggle when user interacts with an actual form input.
				if (e.target && (e.target.tagName === "INPUT" || e.target.tagName === "SELECT" || e.target.tagName === "TEXTAREA")) {
					return;
				}
				var open = item.getAttribute(attrName);
				item.setAttribute(attrName, open === "false" ? "true" : "false");
			},
			true
		);
	}

	function initRepeaterPanelAccordions(item) {
		ensureRepeaterAccordionDefaultState(item);
		bindRepeaterAccordionToggle(item, "ecbb_sep_hover", "data-ecbb-hover-open");
		bindRepeaterAccordionToggle(item, "btn_sep_border", "data-ecbb-btn-border-open");
	}

	function isEventPartsRepeaterRow(item) {
		return (
			item &&
			item.querySelector &&
			item.querySelector('.repeater-item-inner[data-control-key="part"]') &&
			(
				item.querySelector('.repeater-item-inner[data-control-key="ecbb_sep_style"]') ||
				item.querySelector('.repeater-item-inner[data-control-key="ecbb_sep_hover"]') ||
				item.querySelector('.repeater-item-inner[data-control-key="ecbb_typography"]')
			)
		);
	}

	function removeRepeaterContentStyleTabs(item) {
		var bar = item.querySelector(".ecbb-repeater-tabs");
		if (bar) {
			bar.remove();
		}
	}

	function injectRepeaterContentStyleTabs(item) {
		var partEl = getRepeaterControlInner(item, "part");
		if (!partEl || !partEl.parentNode) {
			return;
		}

		item.classList.add("ecbb-parts-repeater-item");
		if (!item.getAttribute("data-ecbb-tab")) {
			item.setAttribute("data-ecbb-tab", "content");
		}

		var wrap = document.createElement("div");
		wrap.className = "ecbb-repeater-tabs";
		wrap.setAttribute("role", "tablist");

		var contentTabButton = document.createElement("button");
		contentTabButton.type = "button";
		contentTabButton.className = "ecbb-repeater-tabs__btn is-active";
		contentTabButton.setAttribute("role", "tab");
		contentTabButton.setAttribute("data-ecbb-tab-btn", "content");
		contentTabButton.setAttribute("aria-selected", "true");
		contentTabButton.textContent = REPEATER_TAB_CONTENT_LABEL;

		var styleTabButton = document.createElement("button");
		styleTabButton.type = "button";
		styleTabButton.className = "ecbb-repeater-tabs__btn";
		styleTabButton.setAttribute("role", "tab");
		styleTabButton.setAttribute("data-ecbb-tab-btn", "style");
		styleTabButton.setAttribute("aria-selected", "false");
		styleTabButton.textContent = REPEATER_TAB_STYLE_LABEL;

		wrap.appendChild(contentTabButton);
		wrap.appendChild(styleTabButton);

		partEl.insertAdjacentElement("afterend", wrap);

		syncRepeaterTabButtonStates(item);
		syncRepeaterHoverPanelState(item);
		initRepeaterPanelAccordions(item);
	}

	function syncRepeaterTabButtonStates(item) {
		var tab = item.getAttribute("data-ecbb-tab") || "content";
		var btns = item.querySelectorAll(".ecbb-repeater-tabs__btn");
		btns.forEach(function (btn) {
			var id = btn.getAttribute("data-ecbb-tab-btn");
			var on = id === tab;
			btn.classList.toggle("is-active", on);
			btn.setAttribute("aria-selected", on ? "true" : "false");
		});
	}

	function ensureRepeaterContentStyleTabs(item) {
		if (!item || !item.classList || !item.classList.contains("repeater-item")) {
			return;
		}

		if (!isEventPartsRepeaterRow(item)) {
			if (item.classList.contains("ecbb-parts-repeater-item")) {
				removeRepeaterContentStyleTabs(item);
				item.classList.remove("ecbb-parts-repeater-item");
				item.removeAttribute("data-ecbb-tab");
				item.removeAttribute("data-ecbb-part");
			}
			return;
		}

		item.classList.add("ecbb-parts-repeater-item");

		var isOpen =
			item.classList.contains("open") || item.classList.contains("always-open");

		if (!isOpen) {
			removeRepeaterContentStyleTabs(item);
			return;
		}

		if (item.querySelector(".ecbb-repeater-tabs")) {
			syncRepeaterHoverPanelState(item);
			initRepeaterPanelAccordions(item);
			return;
		}

		injectRepeaterContentStyleTabs(item);
	}

	function hookPreviewIframeMutationResync() {
		var preview = getBricksPreviewDocument();
		if (!preview || !preview.body || preview.__ecbbPreviewMutationSyncHooked) {
			return;
		}
		preview.__ecbbPreviewMutationSyncHooked = true;
		var previewIframeMutationObserver = new MutationObserver(scheduleRepeaterRowScan);
		previewIframeMutationObserver.observe(preview.body, {
			childList: true,
			subtree: true,
		});
	}

	function scanRepeaterRowsAndSyncPreview() {
		document.querySelectorAll(".repeater-item").forEach(function (item) {
			ensureRepeaterContentStyleTabs(item);
			syncRepeaterHoverPanelState(item);
			initRepeaterPanelAccordions(item);
		});
		hookPreviewIframeMutationResync();
		runAllPreviewSyncHandlers(true);
	}

	var repeaterScanDebounceTimer = null;
	function scheduleRepeaterRowScan() {
		if (repeaterScanDebounceTimer) {
			clearTimeout(repeaterScanDebounceTimer);
		}
		repeaterScanDebounceTimer = setTimeout(function () {
			repeaterScanDebounceTimer = null;
			scanRepeaterRowsAndSyncPreview();
		}, 80);
	}

	function readRepeaterRowId(item) {
		if (!item) {
			return "";
		}
		if (item.getAttribute("data-id")) {
			return item.getAttribute("data-id");
		}
		if (item.dataset && item.dataset.id) {
			return item.dataset.id;
		}
		var idInput = item.querySelector(
			'input[data-setting="id"], input[name$="[id]"]'
		);
		if (idInput && idInput.value) {
			return idInput.value;
		}
		return "";
	}

	function getRepeaterControlInner(item, key) {
		if (!item || !key) {
			return null;
		}
		return item.querySelector(
			'.repeater-item-inner[data-control-key="' + key + '"]'
		);
	}

	function getBricksPreviewDocument() {
		var selectors = [
			"#bricks-builder-iframe",
			"#bricks-preview-iframe",
			"iframe#bricks-preview",
		];
		var i;
		for (i = 0; i < selectors.length; i++) {
			var iframe = document.querySelector(selectors[i]);
			if (iframe && iframe.contentDocument && iframe.contentDocument.body) {
				return iframe.contentDocument;
			}
		}
		return null;
	}

	function getBricksBuilderPanelRoot() {
		return (
			document.querySelector(
				"#bricks-panel, #bricks-panel-wrapper, .bricks-panel, .brx-panel"
			) || document.body
		);
	}

	function mutationAffectsRepeaterRow(mutations) {
		var i;
		for (i = 0; i < mutations.length; i++) {
			var mutation = mutations[i];
			var target = mutation.target;
			if (
				target &&
				target.nodeType === 1 &&
				target.closest &&
				target.closest(".repeater-item")
			) {
				return true;
			}
			if (mutation.type === "childList") {
				var j;
				for (j = 0; j < mutation.addedNodes.length; j++) {
					var node = mutation.addedNodes[j];
					if (
						node.nodeType === 1 &&
						((node.matches && node.matches(".repeater-item")) ||
							(node.querySelector && node.querySelector(".repeater-item")))
					) {
						return true;
					}
				}
			}
		}
		return false;
	}

	var previewSyncRegistry = [];
	var layoutActionButtonPreviewSync;
	var categoryChipPreviewSync;
	var style2MetaIconPreviewSync;
	var style1GridMetaListRowPreviewSync;
	var titleInnerBackgroundPreviewSync;
	var typographyPreviewSync;
	var hoverPreviewSync;
	var styledButtonPreviewSync;

	function createPreviewSyncHandler(options) {
		var delay = options.delay != null ? options.delay : 60;
		var timers =
			typeof WeakMap !== "undefined" ? new WeakMap() : null;
		var fallbackById = {};

		function run(item) {
			if (options.run) {
				options.run(item);
				return;
			}
			options.sync(item);
		}

		function schedule(item) {
			if (!item) {
				return;
			}
			if (options.matches && !options.matches(item)) {
				return;
			}

			if (timers) {
				var pending = timers.get(item);
				if (pending) {
					clearTimeout(pending);
				}
				timers.set(
					item,
					setTimeout(function () {
						timers.delete(item);
						run(item);
					}, delay)
				);
				return;
			}

			var id = readRepeaterRowId(item) || item;
			if (fallbackById[id]) {
				clearTimeout(fallbackById[id]);
			}
			fallbackById[id] = setTimeout(function () {
				delete fallbackById[id];
				run(item);
			}, delay);
		}

		function syncAll() {
			document
				.querySelectorAll(".ecbb-parts-repeater-item")
				.forEach(function (item) {
					if (options.matches && !options.matches(item)) {
						return;
					}
					options.sync(item);
				});
		}

		function handlesKey(key) {
			return !!(
				key &&
				options.controlKeys &&
				options.controlKeys.indexOf(key) !== -1
			);
		}

		var entry = {
			id: options.id || "",
			schedule: schedule,
			syncAll: syncAll,
			handlesKey: handlesKey,
		};
		previewSyncRegistry.push(entry);
		return entry;
	}

	function runAllPreviewSyncHandlers(includeDelayed) {
		var i;
		for (i = 0; i < previewSyncRegistry.length; i++) {
			previewSyncRegistry[i].syncAll();
		}
		if (!includeDelayed || !hoverPreviewSync || !typographyPreviewSync) {
			return;
		}
		setTimeout(function () {
			hoverPreviewSync.syncAll();
		}, 150);
		setTimeout(function () {
			hoverPreviewSync.syncAll();
		}, 350);
		setTimeout(function () {
			typographyPreviewSync.syncAll();
		}, 150);
		setTimeout(function () {
			typographyPreviewSync.syncAll();
		}, 350);
	}

	var LAYOUT_ACTION_BUTTON_PART_SLUGS = ["read_more", "event_tickets", "event_rsvp"];

	function isLayoutActionButtonPart(repeaterItem) {
		return LAYOUT_ACTION_BUTTON_PART_SLUGS.indexOf(readRepeaterPartControlValue(repeaterItem)) !== -1;
	}

	function findLayoutActionButtonSurfaces(wrapper) {
		if (!wrapper) {
			return [];
		}
		return wrapper.querySelectorAll(
			".ecbb-event__link, a.event-button, a.ecbb-event-card__button"
		);
	}

	/**
	 * Bricks repeater live CSS paints typography on the row wrapper. Collapse that
	 * shell so mirrored font-size on the inner button does not leave phantom space.
	 */
	function collapseRepeaterWrapperTypographyShell(wrapper) {
		if (!wrapper) {
			return;
		}
		wrapper.style.setProperty("padding", "0", "important");
		wrapper.style.setProperty("margin", "0", "important");
		wrapper.style.setProperty("background-color", "transparent", "important");
		wrapper.style.setProperty("font-size", "0", "important");
		wrapper.style.setProperty("line-height", "0", "important");
	}

	function applyTypographyToButtonPreviewSurface(surface, computed, typoColor, typoOverrides) {
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

	function isStyledButtonModeEnabled(repeaterItem) {
		var btnStyleInner = getRepeaterControlInner(repeaterItem, "btn_style");
		if (!btnStyleInner) {
			return false;
		}
		var btnCb = btnStyleInner.querySelector('input[type="checkbox"]');
		return !!(btnCb && btnCb.checked);
	}

	function readTypographyControlColor(repeaterItem, wrapper, preview, surfaces) {
		var controlInner = getRepeaterControlInner(repeaterItem, "ecbb_typography");
		var color = "";

		if (controlInner) {
			var colorWrap = controlInner.querySelector(
				'[data-control-key="color"], [data-setting="color"], .control-color'
			);
			color = readColorControlValue(colorWrap || controlInner);
		}

		if (!color) {
			color = readActivePickrColor();
		}

		if (!color && wrapper && preview) {
			color = readPreviewRepeaterCssValue(wrapper, preview, "color");
		}

		if (!color && wrapper && preview && preview.defaultView) {
			color = preview.defaultView.getComputedStyle(wrapper).color;
		}

		if (!color && surfaces && surfaces.length && preview && preview.defaultView) {
			color = preview.defaultView.getComputedStyle(surfaces[0]).color;
		}

		return normalizeTypographyColor(color);
	}

	function readActivePickrColor() {
		var app = document.querySelector(".pcr-app.visible");
		if (!app) {
			return "";
		}

		var inputs = app.querySelectorAll('input[type="text"]');
		var i;
		for (i = 0; i < inputs.length; i++) {
			var value = (inputs[i].value || "").trim();
			if (normalizeTypographyColor(value)) {
				return value;
			}
		}

		var swatches = app.querySelectorAll(".pcr-current-color, .pcr-color-preview");
		for (i = 0; i < swatches.length; i++) {
			if (swatches[i].style && swatches[i].style.backgroundColor) {
				var swatchColor = normalizeTypographyColor(
					swatches[i].style.backgroundColor
				);
				if (swatchColor) {
					return swatchColor;
				}
			}
		}

		return "";
	}

	function normalizeTypographyColor(value) {
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

	function normalizeCssSizeValue(value, fallbackUnit) {
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

	function readTypographyPropertyValue(repeaterItem, property) {
		var controlInner = getRepeaterControlInner(repeaterItem, "ecbb_typography");
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
		if (bricksControl) {
			var bricksInput = bricksControl.querySelector(
				"input[type='number'], input[type='text'], input:not([type='hidden'])"
			);
			if (bricksInput && bricksInput.value) {
				var bricksUnit = bricksControl.querySelector("select");
				var bricksValue = normalizeCssSizeValue(
					bricksInput.value,
					bricksUnit && bricksUnit.value ? bricksUnit.value : "px"
				);
				if (bricksValue) {
					return bricksValue;
				}
			}
		}

		var keyedWrap = controlInner.querySelector(
			'[data-control-key="' + property + '"], [data-setting="' + property + '"]'
		);
		if (keyedWrap) {
			var keyedInput = keyedWrap.querySelector(
				"input[type='number'], input[type='text'], input:not([type='hidden'])"
			);
			if (keyedInput && keyedInput.value) {
				var keyedUnit = keyedWrap.querySelector("select");
				var keyedValue = normalizeCssSizeValue(
					keyedInput.value,
					keyedUnit && keyedUnit.value ? keyedUnit.value : "px"
				);
				if (keyedValue) {
					return keyedValue;
				}
			}
		}

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
				var value = normalizeCssSizeValue(input.value, "px");
				if (value) {
					return value;
				}
			}
		}

		var allInputs = controlInner.querySelectorAll(
			"input[type='number'], input[type='text']"
		);
		for (i = 0; i < allInputs.length; i++) {
			var inputMeta = (
				(allInputs[i].getAttribute("data-setting") || "") +
				(allInputs[i].getAttribute("name") || "") +
				(allInputs[i].getAttribute("data-name") || "") +
				(allInputs[i].getAttribute("data-control-key") || "")
			).toLowerCase();
			var propertyNeedle = property.replace(/-/g, "").toLowerCase();
			if (
				inputMeta.indexOf(propertyNeedle) !== -1 &&
				allInputs[i].value
			) {
				var matchedValue = normalizeCssSizeValue(allInputs[i].value, "px");
				if (matchedValue) {
					return matchedValue;
				}
			}
		}

		return "";
	}

	function readPreviewRepeaterCssValue(wrapper, preview, cssProperty) {
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

	function readTypographyControlSnapshot(repeaterItem, wrapper, preview, surfaces) {
		var snapshot = {
			color: readTypographyControlColor(repeaterItem, wrapper, preview, surfaces),
			fontSize: normalizeCssSizeValue(
				readTypographyPropertyValue(repeaterItem, "font-size"),
				"px"
			),
			lineHeight: normalizeCssSizeValue(
				readTypographyPropertyValue(repeaterItem, "line-height"),
				"px"
			),
			fontWeight: readTypographyPropertyValue(repeaterItem, "font-weight"),
			letterSpacing: normalizeCssSizeValue(
				readTypographyPropertyValue(repeaterItem, "letter-spacing"),
				"px"
			),
			textTransform: readTypographyPropertyValue(repeaterItem, "text-transform"),
			fontFamily: readTypographyPropertyValue(repeaterItem, "font-family"),
		};

		if (!snapshot.fontSize && wrapper && preview) {
			snapshot.fontSize = normalizeCssSizeValue(
				readPreviewRepeaterCssValue(wrapper, preview, "font-size"),
				"px"
			);
		}
		if (!snapshot.lineHeight && wrapper && preview) {
			snapshot.lineHeight = normalizeCssSizeValue(
				readPreviewRepeaterCssValue(wrapper, preview, "line-height"),
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
				snapshot.fontSize = normalizeCssSizeValue(surfaceComputed.fontSize, "px");
			}
			if (!snapshot.lineHeight && surfaceComputed && surfaceComputed.lineHeight) {
				snapshot.lineHeight = normalizeCssSizeValue(surfaceComputed.lineHeight, "px");
			}
			if (!snapshot.fontWeight && surfaceComputed && surfaceComputed.fontWeight) {
				snapshot.fontWeight = surfaceComputed.fontWeight;
			}
			if (!snapshot.letterSpacing && surfaceComputed && surfaceComputed.letterSpacing) {
				snapshot.letterSpacing = normalizeCssSizeValue(
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

	function setWrapperTypographyFlags(wrapper, repeaterItem, typoColor, typoOverrides) {
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
				readRepeaterPartSlug(repeaterItem) === "categories"
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

	function readTextAlignControlValue(repeaterItem) {
		var controlInner = getRepeaterControlInner(repeaterItem, "ecbb_text_align");
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

	function readLayoutActionButtonTypographyOverrides(repeaterItem, wrapper, preview, surfaces) {
		return readTypographyControlSnapshot(repeaterItem, wrapper, preview, surfaces);
	}

	function isLayoutOwnedPreviewNode(node, repeaterItem) {
		if (!node || !repeaterItem) {
			return false;
		}
		var part = readRepeaterPartSlug(repeaterItem);
		if (
			( part === "read_more" ||
				part === "event_tickets" ||
				part === "event_rsvp" ) &&
			!isStyledButtonModeEnabled(repeaterItem) &&
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

	function clearMirroredPreviewInlineStyles(repeaterItem) {
		var preview = getBricksPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}
		var rowId = readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return;
		}
		var wrapper = preview.querySelector('[data-field-id="' + rowId + '"]');
		if (!wrapper) {
			return;
		}
		wrapper
			.querySelectorAll(
				".ecbb-event__term-chip, .ecbb-event__link, .ecbb-event__term, a.event-button, a.ecbb-event-card__button, .ecbb-event-card__category"
			)
			.forEach(function (node) {
				if (!isLayoutOwnedPreviewNode(node, repeaterItem)) {
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
	function syncTypographyColorToPreviewTargets(repeaterItem) {
		var preview = getBricksPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}

		var rowId = readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return;
		}

		var wrapper = preview.querySelector('[data-field-id="' + rowId + '"]');
		if (!wrapper || !preview.defaultView) {
			return;
		}

		if (isStyle1OrGridMetaListPreviewRow(wrapper, preview)) {
			syncStyle1GridMetaListRowPreviewStyle(repeaterItem);
			return;
		}

		var typoColor = readTypographyControlColor(
			repeaterItem,
			wrapper,
			preview
		);
		if (!typoColor) {
			wrapper.classList.remove("ecbb-has-typo-fg");
			wrapper.style.removeProperty("--ecbb-btn-fg");
			wrapper.style.removeProperty("--ecbb-chip-fg");
			if (
				isLayoutActionButtonPart(repeaterItem) &&
				!isStyledButtonModeEnabled(repeaterItem)
			) {
				return;
			}
			if (readRepeaterPartSlug(repeaterItem) === "categories") {
				return;
			}
			clearMirroredPreviewInlineStyles(repeaterItem);
			return;
		}

		setWrapperTypographyFlags(wrapper, repeaterItem, typoColor, {});

		var targets = wrapper.querySelectorAll(
			".ecbb-event__term-chip, .ecbb-event__link, .ecbb-event__term, .ecbb-event__date-day, .ecbb-event__date-time, .ecbb-event__date-sep, a.event-button, a.ecbb-event-card__button, .ecbb-event-card__category"
		);

		targets.forEach(function (node) {
			if (isLayoutOwnedPreviewNode(node, repeaterItem)) {
				return;
			}
			node.style.setProperty("color", typoColor);
		});
	}

	/** View Details / tickets / RSVP: Bricks paints the row wrapper; paint the inner button. */
	function syncLayoutActionButtonPreviewStyle(repeaterItem) {
		if (!repeaterItem || !isLayoutActionButtonPart(repeaterItem)) {
			return;
		}
		if (isStyledButtonModeEnabled(repeaterItem)) {
			return;
		}

		var preview = getBricksPreviewDocument();
		if (!preview) {
			return;
		}

		var rowId = readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return;
		}

		var wrapper = preview.querySelector('[data-field-id="' + rowId + '"]');
		if (!wrapper || !preview.defaultView) {
			return;
		}

		var surfaces = findLayoutActionButtonSurfaces(wrapper);
		if (!surfaces.length) {
			return;
		}

		var bg = readColorControlValue(getRepeaterControlInner(repeaterItem, "ecbb_background"));
		var pad = readSpacingControlValue(getRepeaterControlInner(repeaterItem, "ecbb_padding"));
		var typoOverrides = readLayoutActionButtonTypographyOverrides(
			repeaterItem,
			wrapper,
			preview,
			surfaces
		);
		var typoColor =
			readTypographyControlColor(repeaterItem, wrapper, preview, surfaces) ||
			typoOverrides.color;
		var textAlign = readTextAlignControlValue(repeaterItem);
		var computed = preview.defaultView.getComputedStyle(
			surfaces[0] || wrapper
		);

		setWrapperTypographyFlags(wrapper, repeaterItem, typoColor, typoOverrides);
		syncLayoutButtonTypographyPreviewRule(repeaterItem, typoOverrides);

		collapseRepeaterWrapperTypographyShell(wrapper);
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
			applyTypographyToButtonPreviewSurface(node, computed, typoColor, typoOverrides);
		});
	}

	function scheduleLayoutActionButtonPreviewSync(repeaterItem) {
		if (layoutActionButtonPreviewSync) {
			layoutActionButtonPreviewSync.schedule(repeaterItem);
			return;
		}
		syncLayoutActionButtonPreviewStyle(repeaterItem);
	}

	/** Categories (Style 1 chips + Style 2 pills): Bricks paints the wrapper; paint each button. */
	function syncCategoryChipPreviewStyle(repeaterItem) {
		if (!repeaterItem || readRepeaterPartSlug(repeaterItem) !== "categories") {
			return;
		}

		var preview = getBricksPreviewDocument();
		if (!preview || !preview.defaultView) {
			return;
		}

		var rowId = readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return;
		}

		var wrapper = preview.querySelector('[data-field-id="' + rowId + '"]');
		if (!wrapper) {
			return;
		}

		var bg = readColorControlValue(getRepeaterControlInner(repeaterItem, "ecbb_background"));
		var pad = readSpacingControlValue(getRepeaterControlInner(repeaterItem, "ecbb_padding"));
		var chips = wrapper.querySelectorAll(
			".ecbb-event__term-chip, .ecbb-event-card__category"
		);
		var typoOverrides = readTypographyControlSnapshot(
			repeaterItem,
			wrapper,
			preview,
			chips.length ? chips : null
		);
		var typoColor =
			readTypographyControlColor(
				repeaterItem,
				wrapper,
				preview,
				chips.length ? chips : null
			) || typoOverrides.color;

		if (!bg && !pad && !typoColor && !typoOverrides.fontSize && !typoOverrides.lineHeight) {
			clearMirroredPreviewInlineStyles(repeaterItem);
			wrapper.classList.remove("ecbb-has-typo-fg", "ecbb-has-typo-size");
			return;
		}

		collapseRepeaterWrapperTypographyShell(wrapper);
		setWrapperTypographyFlags(wrapper, repeaterItem, typoColor, typoOverrides);

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
			applyTypographyToButtonPreviewSurface(chip, null, typoColor, typoOverrides);
		});
	}

	/** Style 2 meta rows (venue / timing / cost): paint the leading icon sibling. */
	function syncStyle2MetaIconPreviewStyle(repeaterItem) {
		if (!repeaterItem || STYLE2_META_ICON_PART_SLUGS.indexOf(readRepeaterPartSlug(repeaterItem)) === -1) {
			return;
		}

		var preview = getBricksPreviewDocument();
		if (!preview || !preview.defaultView || !isStyle2LayoutPreview(preview)) {
			return;
		}

		var rowId = readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return;
		}

		var wrapper = preview.querySelector('[data-field-id="' + rowId + '"]');
		if (!wrapper) {
			return;
		}

		var li = wrapper.closest("li.ecbb-event-card__meta-item");
		if (!li) {
			return;
		}

		var icon = li.querySelector(":scope > .ecbb-event-card__meta-icon");
		if (!icon) {
			return;
		}

		var color = readColorControlValue(
			getRepeaterControlInner(repeaterItem, "ecbb_meta_icon_color")
		);
		var bg = readColorControlValue(
			getRepeaterControlInner(repeaterItem, "ecbb_meta_icon_background")
		);
		var typoOverrides = readTypographyControlSnapshot(
			repeaterItem,
			wrapper,
			preview,
			icon ? [icon] : null
		);
		if (!color) {
			color =
				readTypographyControlColor(
					repeaterItem,
					wrapper,
					preview,
					icon ? [icon] : null
				) || typoOverrides.color;
		}

		if (color) {
			icon.style.setProperty("color", color, "important");
		} else {
			icon.style.removeProperty("color");
		}

		if (bg) {
			icon.style.setProperty("background-color", bg, "important");
		} else {
			icon.style.removeProperty("background-color");
		}

		if (typoOverrides.fontSize) {
			icon.style.setProperty("font-size", typoOverrides.fontSize, "important");
		} else {
			icon.style.removeProperty("font-size");
		}
		if (typoOverrides.lineHeight) {
			icon.style.setProperty("line-height", typoOverrides.lineHeight, "important");
		} else {
			icon.style.removeProperty("line-height");
		}
	}

	function syncTitleInnerBackgroundPreview(repeaterItem) {
		if (!repeaterItem || readRepeaterPartControlValue(repeaterItem) !== "title") {
			return;
		}

		var preview = getBricksPreviewDocument();
		if (!preview) {
			return;
		}

		var rowId = readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return;
		}

		var wrapper = preview.querySelector('[data-field-id="' + rowId + '"]');
		if (!wrapper) {
			return;
		}

		var bg = readColorControlValue(getRepeaterControlInner(repeaterItem, "ecbb_background"));
		if (!bg) {
			bg = readColorControlValue(getRepeaterControlInner(repeaterItem, "ecbb_background_inner"));
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

	function scheduleTitleInnerBackgroundPreviewSync(repeaterItem) {
		if (titleInnerBackgroundPreviewSync) {
			titleInnerBackgroundPreviewSync.schedule(repeaterItem);
			return;
		}
		syncTitleInnerBackgroundPreview(repeaterItem);
	}

	function syncStyledButtonCssColorVariable(repeaterItem) {
		if (!repeaterItem || !isStyledButtonModeEnabled(repeaterItem)) {
			return;
		}

		var preview = getBricksPreviewDocument();
		if (!preview) {
			return;
		}

		var rowId = readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return;
		}

		var wrapper = preview.querySelector('[data-field-id="' + rowId + '"]');
		if (!wrapper) {
			return;
		}

		var surface = findStyledButtonPreviewSurface(wrapper);
		var textColor = readTypographyControlColor(
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

	function runTypographyPreviewSyncChain(repeaterItem) {
		var part = readRepeaterPartSlug(repeaterItem);

		syncTypographyColorToPreviewTargets(repeaterItem);
		syncStyledButtonCssColorVariable(repeaterItem);

		if (part === "categories") {
			syncCategoryChipPreviewStyle(repeaterItem);
		}
		if (STYLE2_META_ICON_PART_SLUGS.indexOf(part) !== -1) {
			syncStyle2MetaIconPreviewStyle(repeaterItem);
		}

		if (isLayoutActionButtonPart(repeaterItem)) {
			if (isStyledButtonModeEnabled(repeaterItem)) {
				syncStyledButtonPreviewPaint(repeaterItem);
			} else {
				syncLayoutActionButtonPreviewStyle(repeaterItem);
			}
		}

		scheduleHoverPreviewStyleSync(repeaterItem);
	}

	var STYLED_BUTTON_BACKGROUND_KEYS = ["ecbb_background"];
	var STYLED_BUTTON_BORDER_KEYS = Array.isArray(builderConfig.btnBorderKeys)
		? builderConfig.btnBorderKeys.filter(function (key) {
				return key !== "btn_sep_border";
		  })
		: [
				"btn_border_type",
				"btn_border_width",
				"btn_border_color",
				"btn_padding",
				"btn_border_radius",
		  ];
	var STYLED_BUTTON_CONTROL_KEYS = STYLED_BUTTON_BACKGROUND_KEYS.concat(STYLED_BUTTON_BORDER_KEYS);
	var HOVER_PREVIEW_CONTROL_KEYS = Array.isArray(builderConfig.hoverKeys)
		? builderConfig.hoverKeys.filter(function (key) {
				return (
					key === "ecbb_use_hover" ||
					key === "ecbb_hover_color" ||
					key === "ecbb_hover_background"
				);
		  })
		: [ "ecbb_use_hover", "ecbb_hover_color", "ecbb_hover_background" ];

	function readHoverControlColor(repeaterItem, key) {
		var color = readColorControlValue(getRepeaterControlInner(repeaterItem, key));
		return isVisibleHoverColor(color) ? color : "";
	}

	function readHoverTextControlColor(repeaterItem) {
		return readHoverControlColor(repeaterItem, "ecbb_hover_color");
	}

	function readHoverBackgroundControlColor(repeaterItem) {
		return readHoverControlColor(repeaterItem, "ecbb_hover_background");
	}

	function isVisibleHoverColor(value) {
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

	function isStyle2LayoutPreview(preview) {
		return !!(
			preview &&
			preview.querySelector(
				".ecbb-ev__item--style-2, .ecbb-ev__item-inner--style-2"
			)
		);
	}

	function isStyle1OrGridMetaListPreviewRow(wrapper, preview) {
		if (!wrapper || !preview || isStyle2LayoutPreview(preview)) {
			return false;
		}
		var li = wrapper.closest("li");
		return !!(li && li.closest(".event-meta"));
	}

	function syncStyle1GridMetaListRowPreviewStyle(repeaterItem) {
		var preview = getBricksPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}

		var rowId = readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return;
		}

		var wrapper = preview.querySelector('[data-field-id="' + rowId + '"]');
		if (!wrapper || !isStyle1OrGridMetaListPreviewRow(wrapper, preview)) {
			return;
		}

		var li = wrapper.closest("li");
		if (!li) {
			return;
		}

		var icon = li.querySelector(":scope > .ecbb-event-card__meta-icon");
		var bg = readColorControlValue(getRepeaterControlInner(repeaterItem, "ecbb_background"));
		var typoColor = readTypographyControlColor(
			repeaterItem,
			wrapper,
			preview,
			icon ? [icon] : null
		);
		var typoOverrides = readTypographyControlSnapshot(
			repeaterItem,
			wrapper,
			preview,
			icon ? [icon] : null
		);
		if (!typoColor && typoOverrides.color) {
			typoColor = typoOverrides.color;
		}
		var pad = readSpacingControlValue(getRepeaterControlInner(repeaterItem, "ecbb_padding"));

		li.style.setProperty("gap", "8px", "important");
		li.style.setProperty("align-items", "center", "important");

		if (bg) {
			li.style.setProperty("background-color", bg, "important");
			li.style.setProperty("width", "fit-content", "important");
			li.style.setProperty("max-width", "100%", "important");
			wrapper.style.setProperty("background-color", "transparent", "important");
			if (icon) {
				icon.style.setProperty("background-color", "transparent", "important");
			}
		} else {
			li.style.removeProperty("background-color");
			li.style.removeProperty("width");
			li.style.removeProperty("max-width");
		}

		if (typoColor) {
			li.style.setProperty("color", typoColor, "important");
			wrapper.style.setProperty("color", typoColor, "important");
			if (icon) {
				icon.style.setProperty("color", typoColor, "important");
			}
		} else {
			li.style.removeProperty("color");
			if (icon) {
				icon.style.removeProperty("color");
			}
		}

		if (icon) {
			if (typoOverrides.fontSize) {
				icon.style.setProperty("font-size", typoOverrides.fontSize, "important");
			} else {
				icon.style.removeProperty("font-size");
			}
			if (typoOverrides.lineHeight) {
				icon.style.setProperty("line-height", typoOverrides.lineHeight, "important");
			} else {
				icon.style.removeProperty("line-height");
			}
		}

		if (pad) {
			li.style.setProperty("padding", pad, "important");
			wrapper.style.setProperty("padding", "0", "important");
		} else {
			li.style.removeProperty("padding");
		}
	}

	function repeaterHasCustomHoverColors(repeaterItem) {
		return !!(
			readHoverTextControlColor(repeaterItem) ||
			readHoverBackgroundControlColor(repeaterItem)
		);
	}

	function findPreviewPartElementForRepeaterRow(repeaterItem, preview) {
		if (!preview || !repeaterItem) {
			return null;
		}

		var rowId = readRepeaterRowId(repeaterItem);
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

	function isPartHoverPreviewEnabled(repeaterItem) {
		if (!repeaterItem) {
			return false;
		}
		var part = readRepeaterPartSlug(repeaterItem);
		if (!partSupportsHoverControls(part, repeaterItem)) {
			return false;
		}
		if (repeaterHasCustomHoverColors(repeaterItem)) {
			return true;
		}
		return isHoverStylingEnabledInPanel(repeaterItem);
	}

	function buildHoverPreviewSelectorScope(rowId) {
		return '[data-field-id="' + rowId + '"]';
	}

	function buildHoverPreviewBackgroundSelectors(scope) {
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

	function buildHoverPreviewColorSelectors(scope) {
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

	var hoverPreviewRulesByRepeaterItem =
		typeof WeakMap !== "undefined" ? new WeakMap() : null;
	var hoverPreviewRulesByRepeaterItemByRowId = {};
	var layoutButtonTypoPreviewRulesByRepeaterItem =
		typeof WeakMap !== "undefined" ? new WeakMap() : null;
	var layoutButtonTypoPreviewRulesByRepeaterItemByRowId = {};

	function getOrCreateBuilderPreviewStyleElement(preview, styleId) {
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

	function getOrCreateHoverPreviewStyleElement(preview) {
		return getOrCreateBuilderPreviewStyleElement(preview, "ecbb-builder-hover-css");
	}

	function rebuildLayoutButtonTypoPreviewStylesheet(preview) {
		var el = getOrCreateBuilderPreviewStyleElement(
			preview,
			"ecbb-builder-layout-btn-typo-css"
		);
		if (!el) {
			return;
		}
		var chunks = [];
		if (layoutButtonTypoPreviewRulesByRepeaterItem) {
			layoutButtonTypoPreviewRulesByRepeaterItem.forEach(function (rule) {
				if (rule) {
					chunks.push(rule);
				}
			});
		} else {
			Object.keys(layoutButtonTypoPreviewRulesByRepeaterItemByRowId).forEach(
				function (key) {
					if (layoutButtonTypoPreviewRulesByRepeaterItemByRowId[key]) {
						chunks.push(layoutButtonTypoPreviewRulesByRepeaterItemByRowId[key]);
					}
				}
			);
		}
		el.textContent = chunks.join("\n");
	}

	function buildLayoutButtonTypographyPreviewRule(rowId, typoOverrides) {
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

	function syncLayoutButtonTypographyPreviewRule(repeaterItem, typoOverrides) {
		var preview = getBricksPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}

		var rowId = readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return;
		}

		var rule = buildLayoutButtonTypographyPreviewRule(rowId, typoOverrides || {});
		if (layoutButtonTypoPreviewRulesByRepeaterItem) {
			if (rule) {
				layoutButtonTypoPreviewRulesByRepeaterItem.set(repeaterItem, rule);
			} else {
				layoutButtonTypoPreviewRulesByRepeaterItem.delete(repeaterItem);
			}
		} else if (rule) {
			layoutButtonTypoPreviewRulesByRepeaterItemByRowId[rowId] = rule;
		} else {
			delete layoutButtonTypoPreviewRulesByRepeaterItemByRowId[rowId];
		}

		rebuildLayoutButtonTypoPreviewStylesheet(preview);
	}

	function rebuildHoverPreviewStylesheet(preview) {
		var el = getOrCreateHoverPreviewStyleElement(preview);
		if (!el) {
			return;
		}
		var chunks = [];
		if (hoverPreviewRulesByRepeaterItem) {
			hoverPreviewRulesByRepeaterItem.forEach(function (rule) {
				if (rule) {
					chunks.push(rule);
				}
			});
		} else {
			Object.keys(hoverPreviewRulesByRepeaterItemByRowId).forEach(function (key) {
				if (hoverPreviewRulesByRepeaterItemByRowId[key]) {
					chunks.push(hoverPreviewRulesByRepeaterItemByRowId[key]);
				}
			});
		}
		el.textContent = chunks.join("\n");
	}

	function syncPreviewHoverStateClass(repeaterItem) {
		var preview = getBricksPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}

		var wrapper = findPreviewPartElementForRepeaterRow(repeaterItem, preview);
		if (!wrapper) {
			return;
		}

		var useHoverInner = getRepeaterControlInner(repeaterItem, "ecbb_use_hover");
		if (useHoverInner) {
			wrapper.classList.toggle("ecbb-no-hover", !isPartHoverPreviewEnabled(repeaterItem));
		}
	}

	function syncHoverPreviewStyleRules(repeaterItem) {
		var preview = getBricksPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}

		syncPreviewHoverStateClass(repeaterItem);

		var wrapper = findPreviewPartElementForRepeaterRow(repeaterItem, preview);
		if (!wrapper) {
			return;
		}

		var rowId = readRepeaterRowId(repeaterItem);
		var enabled = isPartHoverPreviewEnabled(repeaterItem);
		var hoverColor = readHoverTextControlColor(repeaterItem);
		var hoverBg = readHoverBackgroundControlColor(repeaterItem);

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

		var scope = buildHoverPreviewSelectorScope(rowId);
		var rule = "";
		if (enabled) {
			if (
				readRepeaterPartSlug(repeaterItem) === "categories" &&
				isStyle2LayoutPreview(preview)
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
				rule += buildHoverPreviewColorSelectors(scope) + "{color:" + hoverColor + " !important;}";
			}
			if (hoverBg) {
				rule += buildHoverPreviewBackgroundSelectors(scope) + "{background-color:" + hoverBg + " !important;}";
			}
		}

		if (hoverPreviewRulesByRepeaterItem) {
			hoverPreviewRulesByRepeaterItem.set(repeaterItem, rule);
		} else {
			hoverPreviewRulesByRepeaterItemByRowId[rowId] = rule;
		}

		rebuildHoverPreviewStylesheet(preview);
	}

	function scheduleHoverPreviewStyleSync(repeaterItem) {
		if (hoverPreviewSync) {
			hoverPreviewSync.schedule(repeaterItem);
			return;
		}
		syncHoverPreviewStyleRules(repeaterItem);
	}

	function readColorControlValue(controlInner) {
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
			if (normalizeTypographyColor(pickrBg)) {
				return pickrBg;
			}
		}

		var pickrBtn = controlInner.querySelector(".pcr-button");
		if (pickrBtn && pickrBtn.style && pickrBtn.style.backgroundColor) {
			var pickrBtnBg = pickrBtn.style.backgroundColor;
			if (normalizeTypographyColor(pickrBtnBg)) {
				return pickrBtnBg;
			}
		}

		return "";
	}

	function applyStyledButtonCssVariables(repeaterItem, link, preview, wrapper) {
		if (!repeaterItem || !link || !wrapper) {
			return;
		}

		var bg = readColorControlValue(getRepeaterControlInner(repeaterItem, "ecbb_background"));
		if (bg) {
			wrapper.style.setProperty("--ecbb-btn-bg", bg);
		}

		var textColor = readTypographyControlColor(
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

	function readSpacingControlValue(controlInner) {
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
	}

	function readSelectControlValue(controlInner) {
		if (!controlInner) {
			return "";
		}
		var select = controlInner.querySelector("select");
		return select && select.value ? String(select.value).trim() : "";
	}

	function readNumberControlValue(controlInner) {
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
	}

	function applyStyledButtonBorderToSurface(repeaterItem, surface) {
		if (!repeaterItem || !surface) {
			return;
		}

		var padding = readSpacingControlValue(getRepeaterControlInner(repeaterItem, "btn_padding"));
		if (padding) {
			surface.style.padding = padding;
		}

		var radius = readSpacingControlValue(getRepeaterControlInner(repeaterItem, "btn_border_radius"));
		if (radius) {
			surface.style.borderRadius = radius;
		}

		var borderType =
			readSelectControlValue(getRepeaterControlInner(repeaterItem, "btn_border_type")) || "";
		var borderWidth = readNumberControlValue(
			getRepeaterControlInner(repeaterItem, "btn_border_width")
		);
		var borderColor = readColorControlValue(getRepeaterControlInner(repeaterItem, "btn_border_color"));

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
	function findStyledButtonPreviewSurface(wrapper) {
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

	function clearStyledButtonPreviewPaint(wrapper) {
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
	function syncStyledButtonPreviewPaint(repeaterItem) {
		var preview = getBricksPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}

		var rowId = readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return;
		}

		var wrapper = preview.querySelector('[data-field-id="' + rowId + '"]');
		if (!wrapper) {
			return;
		}

		var btnStyleOn = isStyledButtonModeEnabled(repeaterItem);
		if (!btnStyleOn) {
			wrapper.classList.remove("ecbb-has-btn");
			if (isLayoutActionButtonPart(repeaterItem)) {
				syncLayoutActionButtonPreviewStyle(repeaterItem);
				return;
			}
			clearStyledButtonPreviewPaint(wrapper);
			return;
		}

		wrapper.classList.add("ecbb-has-btn");

		var surface = findStyledButtonPreviewSurface(wrapper);
		if (!surface) {
			return;
		}

		var typoColor = readTypographyControlColor(
			repeaterItem,
			wrapper,
			preview,
			surface ? [surface] : null
		);
		var typoOverrides = readTypographyControlSnapshot(
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

		setWrapperTypographyFlags(wrapper, repeaterItem, typoColor, typoOverrides);
		collapseRepeaterWrapperTypographyShell(wrapper);

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

		applyStyledButtonBorderToSurface(repeaterItem, surface);
		applyStyledButtonCssVariables(repeaterItem, surface, preview, wrapper);
		if (computed) {
			applyTypographyToButtonPreviewSurface(
				surface,
				computed,
				typoColor,
				typoOverrides
			);
		}

		scheduleHoverPreviewStyleSync(repeaterItem);

		surface.style.setProperty("display", "inline-flex");
		surface.style.setProperty("align-items", "center");
		surface.style.setProperty("justify-content", "center");
		surface.style.setProperty("width", "auto");
		surface.style.setProperty("max-width", "100%");
		surface.style.setProperty("box-sizing", "border-box");
	}

	function scheduleStyledButtonPreviewSync(repeaterItem) {
		if (styledButtonPreviewSync) {
			styledButtonPreviewSync.schedule(repeaterItem);
			return;
		}
		syncStyledButtonPreviewPaint(repeaterItem);
	}

	function initPreviewSyncRegistry() {
		previewSyncRegistry.length = 0;

		layoutActionButtonPreviewSync = createPreviewSyncHandler({
			id: "layoutActionButtonPreview",
			matches: isLayoutActionButtonPart,
			controlKeys: ["ecbb_background", "ecbb_padding", "ecbb_typography", "ecbb_text_align"],
			sync: syncLayoutActionButtonPreviewStyle,
		});

		categoryChipPreviewSync = createPreviewSyncHandler({
			id: "categoryChipPreview",
			matches: function (item) {
				return readRepeaterPartSlug(item) === "categories";
			},
			controlKeys: ["ecbb_background", "ecbb_padding", "ecbb_typography"],
			sync: syncCategoryChipPreviewStyle,
		});

		style2MetaIconPreviewSync = createPreviewSyncHandler({
			id: "style2MetaIconPreview",
			matches: function (item) {
				return STYLE2_META_ICON_PART_SLUGS.indexOf(readRepeaterPartSlug(item)) !== -1;
			},
			controlKeys: [
				"ecbb_meta_icon_color",
				"ecbb_meta_icon_background",
				"ecbb_typography",
			],
			sync: syncStyle2MetaIconPreviewStyle,
		});

		style1GridMetaListRowPreviewSync = createPreviewSyncHandler({
			id: "style1GridMetaListRowPreview",
			controlKeys: ["ecbb_background", "ecbb_padding", "ecbb_typography"],
			sync: syncStyle1GridMetaListRowPreviewStyle,
		});

		titleInnerBackgroundPreviewSync = createPreviewSyncHandler({
			id: "titleInnerBackgroundPreview",
			matches: function (item) {
				return readRepeaterPartControlValue(item) === "title";
			},
			controlKeys: ["ecbb_background", "ecbb_background_inner"],
			sync: syncTitleInnerBackgroundPreview,
		});

		typographyPreviewSync = createPreviewSyncHandler({
			id: "typographyPreview",
			controlKeys: ["ecbb_typography"],
			delay: 0,
			sync: syncTypographyColorToPreviewTargets,
			run: runTypographyPreviewSyncChain,
		});

		hoverPreviewSync = createPreviewSyncHandler({
			id: "hoverPreview",
			controlKeys: HOVER_PREVIEW_CONTROL_KEYS,
			sync: syncHoverPreviewStyleRules,
			run: function (item) {
				syncHoverPreviewStyleRules(item);
				setTimeout(function () {
					syncHoverPreviewStyleRules(item);
				}, 120);
			},
		});

		styledButtonPreviewSync = createPreviewSyncHandler({
			id: "styledButtonPreview",
			matches: function (item) {
				return !!getRepeaterControlInner(item, "btn_style");
			},
			controlKeys: STYLED_BUTTON_CONTROL_KEYS,
			sync: syncStyledButtonPreviewPaint,
			run: function (item) {
				syncStyledButtonPreviewPaint(item);
				setTimeout(function () {
					syncStyledButtonPreviewPaint(item);
				}, 120);
			},
		});
	}

	function onRepeaterControlInput(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item) {
			return;
		}
		var keys = getRepeaterControlKeysFromEvent(e);
		var i;
		var k;
		var j;
		for (i = 0; i < previewSyncRegistry.length; i++) {
			for (j = 0; j < keys.length; j++) {
				k = keys[j];
				if (k && previewSyncRegistry[i].handlesKey(k)) {
					previewSyncRegistry[i].schedule(item);
				}
			}
		}
	}

	function onRepeaterControlChange(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item) {
			return;
		}
		var key = getRepeaterControlKeyFromEvent(e);
		if (key === "part") {
			syncRepeaterHoverPanelState(item);
			runAllPreviewSyncHandlers(false);
			return;
		}
		if (key === "link") {
			syncRepeaterHoverPanelState(item);
			scheduleTitleInnerBackgroundPreviewSync(item);
			return;
		}
		if (key === "ecbb_use_hover") {
			syncRepeaterHoverToggleAttribute(item);
			scheduleStyledButtonPreviewSync(item);
			scheduleHoverPreviewStyleSync(item);
			return;
		}
		onRepeaterControlInput(e);
	}

	function getRepeaterControlKeyFromEvent(e) {
		var inner = e.target.closest(".repeater-item-inner[data-control-key]");
		if (!inner) {
			return "";
		}
		return inner.getAttribute("data-control-key") || "";
	}

	function getRepeaterControlKeysFromEvent(e) {
		var keys = [];
		var key = getRepeaterControlKeyFromEvent(e);
		if (key) {
			keys.push(key);
		}
		if (
			e.target &&
			e.target.closest &&
			e.target.closest(
				'.repeater-item-inner[data-control-key="ecbb_typography"]'
			) &&
			keys.indexOf("ecbb_typography") === -1
		) {
			keys.push("ecbb_typography");
		}
		return keys;
	}

	var typographyColorPickerRaf = 0;

	function syncOpenRepeaterTypographyPreview() {
		document
			.querySelectorAll(
				".ecbb-parts-repeater-item.open, .ecbb-parts-repeater-item.always-open"
			)
			.forEach(function (item) {
				if (getRepeaterControlInner(item, "ecbb_typography")) {
					runTypographyPreviewSyncChain(item);
				}
			});
	}

	function watchTypographyColorPickerDrag() {
		if (!document.querySelector(".pcr-app.visible")) {
			typographyColorPickerRaf = 0;
			return;
		}
		syncOpenRepeaterTypographyPreview();
		typographyColorPickerRaf = requestAnimationFrame(
			watchTypographyColorPickerDrag
		);
	}

	function startTypographyColorPickerDragWatch() {
		if (!typographyColorPickerRaf) {
			typographyColorPickerRaf = requestAnimationFrame(
				watchTypographyColorPickerDrag
			);
		}
	}

	function stopTypographyColorPickerDragWatch() {
		if (typographyColorPickerRaf) {
			cancelAnimationFrame(typographyColorPickerRaf);
			typographyColorPickerRaf = 0;
		}
		syncOpenRepeaterTypographyPreview();
	}

	initPreviewSyncRegistry();

	document.addEventListener("input", onRepeaterControlInput, true);
	document.addEventListener("change", onRepeaterControlChange, true);

	document.addEventListener(
		"pointerdown",
		function (e) {
			if (
				e.target.closest(
					'.repeater-item-inner[data-control-key="ecbb_typography"] .pickr, .repeater-item-inner[data-control-key="ecbb_typography"] .pcr-button, .pcr-app'
				)
			) {
				startTypographyColorPickerDragWatch();
			}
		},
		true
	);
	document.addEventListener("pointerup", stopTypographyColorPickerDragWatch, true);

	document.addEventListener(
		"click",
		function (e) {
			if (!e.target.closest('.repeater-item-inner[data-control-key="ecbb_use_hover"]')) {
				return;
			}
			var item = e.target.closest(".ecbb-parts-repeater-item");
			if (!item) {
				return;
			}
			setTimeout(function () {
				syncRepeaterHoverToggleAttribute(item);
				scheduleHoverPreviewStyleSync(item);
			}, 0);
		},
		true
	);

	document.addEventListener(
		"click",
		function (e) {
			var btn = e.target.closest(".ecbb-repeater-tabs__btn");
			if (!btn) {
				return;
			}
			var item = btn.closest(".ecbb-parts-repeater-item");
			if (
				!item ||
				(!item.classList.contains("open") &&
					!item.classList.contains("always-open"))
			) {
				return;
			}
			e.preventDefault();
			var next = btn.getAttribute("data-ecbb-tab-btn");
			if (next !== "content" && next !== "style") {
				return;
			}
			item.setAttribute("data-ecbb-tab", next);
			syncRepeaterTabButtonStates(item);
		},
		true
	);

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", scanRepeaterRowsAndSyncPreview);
	} else {
		scanRepeaterRowsAndSyncPreview();
	}

	var builderPanelMutationObserver = new MutationObserver(function (mutations) {
		if (mutationAffectsRepeaterRow(mutations)) {
			scheduleRepeaterRowScan();
		}
	});
	builderPanelMutationObserver.observe(getBricksBuilderPanelRoot(), {
		childList: true,
		subtree: true,
		attributes: true,
		attributeFilter: ["class"],
	});
})();
