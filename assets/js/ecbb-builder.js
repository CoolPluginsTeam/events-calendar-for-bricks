(function () {
	"use strict";

	var L = typeof ECBBBuilder !== "undefined" ? ECBBBuilder : {};
	var TAB_CONTENT = L.tabContent || "CONTENT";
	var TAB_STYLE = L.tabStyle || "STYLE";
	var HOVER_PARTS = Array.isArray(L.hoverParts) ? L.hoverParts : [
		"title",
		"categories",
		"tags",
		"read_more",
		"event_tickets",
		"event_rsvp",
		"image",
	];

	function readPartValue(item) {
		var partInner = item.querySelector(
			'.repeater-item-inner[data-control-key="part"]'
		);
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

	function partSupportsHover(part, item) {
		if (part === "" || HOVER_PARTS.indexOf(part) === -1) {
			return false;
		}
		if (part === "title" && item && !titleLinkEnabled(item)) {
			return false;
		}
		return true;
	}

	function titleLinkEnabled(item) {
		if (!item) {
			return false;
		}
		var inner = item.querySelector(
			'.repeater-item-inner[data-control-key="link"]'
		);
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

	function readUseHoverValue(item) {
		if (!item) {
			return true;
		}

		var inner = item.querySelector(
			'.repeater-item-inner[data-control-key="ecbb_use_hover"]'
		);
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

	function syncUseHoverEnabled(item) {
		if (!item || !item.classList.contains("ecbb-parts-repeater-item")) {
			return;
		}

		var part = readPartValue(item);
		if (!partSupportsHover(part, item)) {
			item.removeAttribute("data-ecbb-use-hover");
			return;
		}

		item.setAttribute(
			"data-ecbb-use-hover",
			readUseHoverValue(item) ? "true" : "false"
		);
	}

	function syncHoverVisibility(item) {
		if (!item || !item.classList.contains("ecbb-parts-repeater-item")) {
			return;
		}

		var part = readPartValue(item);
		item.setAttribute("data-ecbb-part", part);
		item.setAttribute(
			"data-ecbb-title-link",
			part === "title" && titleLinkEnabled(item) ? "true" : "false"
		);
		item.classList.toggle("ecbb-part-no-hover", part !== "" && !partSupportsHover(part, item));
		syncUseHoverEnabled(item);
	}

	function ensureAccordionState(item) {
		if (!item || !item.classList || !item.classList.contains("ecbb-parts-repeater-item")) {
			return;
		}
		if (!item.hasAttribute("data-ecbb-hover-open")) {
			item.setAttribute("data-ecbb-hover-open", "true");
		}
		if (!item.hasAttribute("data-ecbb-btn-open")) {
			item.setAttribute("data-ecbb-btn-open", "true");
		}
	}

	function bindAccordionToggle(item, sepKey, attrName) {
		var sep = item.querySelector('.repeater-item-inner[data-control-key="' + sepKey + '"]');
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

	function ensureAccordions(item) {
		ensureAccordionState(item);
		bindAccordionToggle(item, "ecbb_sep_hover", "data-ecbb-hover-open");
		bindAccordionToggle(item, "btn_sep_style", "data-ecbb-btn-open");
	}

	function isECBBPartsRow(item) {
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

	function removeTabs(item) {
		var bar = item.querySelector(".ecbb-repeater-tabs");
		if (bar) {
			bar.remove();
		}
	}

	function injectTabs(item) {
		var partEl = item.querySelector('.repeater-item-inner[data-control-key="part"]');
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

		var bContent = document.createElement("button");
		bContent.type = "button";
		bContent.className = "ecbb-repeater-tabs__btn is-active";
		bContent.setAttribute("role", "tab");
		bContent.setAttribute("data-ecbb-tab-btn", "content");
		bContent.setAttribute("aria-selected", "true");
		bContent.textContent = TAB_CONTENT;

		var bStyle = document.createElement("button");
		bStyle.type = "button";
		bStyle.className = "ecbb-repeater-tabs__btn";
		bStyle.setAttribute("role", "tab");
		bStyle.setAttribute("data-ecbb-tab-btn", "style");
		bStyle.setAttribute("aria-selected", "false");
		bStyle.textContent = TAB_STYLE;

		wrap.appendChild(bContent);
		wrap.appendChild(bStyle);

		partEl.insertAdjacentElement("afterend", wrap);

		syncButtons(item);
		syncHoverVisibility(item);
		ensureAccordions(item);
	}

	function syncButtons(item) {
		var tab = item.getAttribute("data-ecbb-tab") || "content";
		var btns = item.querySelectorAll(".ecbb-repeater-tabs__btn");
		btns.forEach(function (btn) {
			var id = btn.getAttribute("data-ecbb-tab-btn");
			var on = id === tab;
			btn.classList.toggle("is-active", on);
			btn.setAttribute("aria-selected", on ? "true" : "false");
		});
	}

	function ensureTabs(item) {
		if (!item || !item.classList || !item.classList.contains("repeater-item")) {
			return;
		}

		if (!isECBBPartsRow(item)) {
			if (item.classList.contains("ecbb-parts-repeater-item")) {
				removeTabs(item);
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
			removeTabs(item);
			return;
		}

		if (item.querySelector(".ecbb-repeater-tabs")) {
			syncHoverVisibility(item);
			ensureAccordions(item);
			return;
		}

		injectTabs(item);
	}

	function scan() {
		document.querySelectorAll(".repeater-item").forEach(function (item) {
			ensureTabs(item);
			syncHoverVisibility(item);
			ensureAccordions(item);
		});
		syncAllButtonPaint();
		syncAllTitleInnerBackground();
	}

	var t = null;
	function scheduleScan() {
		if (t) {
			clearTimeout(t);
		}
		t = setTimeout(function () {
			t = null;
			scan();
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

	function getPreviewDocument() {
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

	/**
	 * Bricks repeater live CSS ignores child selectors in the builder; mirror wrapper
	 * color onto category chips / links so typography color updates instantly.
	 */
	function syncTypographyColorFromWrapper(repeaterItem) {
		var preview = getPreviewDocument();
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

		var color = preview.defaultView.getComputedStyle(wrapper).color;
		if (!color) {
			return;
		}

		wrapper
			.querySelectorAll(
				".ecbb-event__term-chip, .ecbb-event__link, .ecbb-event__term"
			)
			.forEach(function (node) {
				node.style.setProperty("color", color);
			});
	}

	function syncTitleInnerBackground(repeaterItem) {
		if (!repeaterItem || readPartValue(repeaterItem) !== "title") {
			return;
		}

		var preview = getPreviewDocument();
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

		var bg = readControlInnerColor(
			repeaterItem.querySelector(
				'.repeater-item-inner[data-control-key="ecbb_background_inner"]'
			)
		);

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

	var titleInnerBgTimer = null;
	function scheduleTitleInnerBackgroundSync(repeaterItem) {
		if (!repeaterItem) {
			return;
		}
		if (titleInnerBgTimer) {
			clearTimeout(titleInnerBgTimer);
		}
		titleInnerBgTimer = setTimeout(function () {
			titleInnerBgTimer = null;
			syncTitleInnerBackground(repeaterItem);
		}, 60);
	}

	function syncAllTitleInnerBackground() {
		document
			.querySelectorAll(".ecbb-parts-repeater-item")
			.forEach(function (item) {
				if (readPartValue(item) === "title") {
					syncTitleInnerBackground(item);
				}
			});
	}

	function onTitleInnerBackgroundInteraction(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item) {
			return;
		}
		if (
			e.target.closest(
				'.repeater-item-inner[data-control-key="ecbb_background_inner"]'
			)
		) {
			scheduleTitleInnerBackgroundSync(item);
		}
	}

	var typoSyncTimer = null;
	function scheduleTypographyColorSync(repeaterItem) {
		if (!repeaterItem) {
			return;
		}
		if (typoSyncTimer) {
			clearTimeout(typoSyncTimer);
		}
		typoSyncTimer = setTimeout(function () {
			typoSyncTimer = null;
			syncTypographyColorFromWrapper(repeaterItem);
		}, 60);
	}

	function onTypographyControlInteraction(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item) {
			return;
		}
		if (
			!e.target.closest(
				'.repeater-item-inner[data-control-key="ecbb_typography"]'
			)
		) {
			return;
		}
		scheduleTypographyColorSync(item);
	}

	var BTN_PAINT_KEYS = [
		"btn_bg",
		"btn_text_color",
		"btn_border_color",
		"btn_padding",
		"ecbb_background",
	];

	function readControlInnerColor(controlInner) {
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
				value.indexOf("hwb") === 0
			) {
				return value;
			}
		}

		var pickr = controlInner.querySelector(".pcr-result");
		if (pickr && pickr.style && pickr.style.backgroundColor) {
			return pickr.style.backgroundColor;
		}

		return "";
	}

	function applyButtonColors(repeaterItem, link, preview, wrapper) {
		if (!repeaterItem || !link || !wrapper) {
			return;
		}

		var bg = readControlInnerColor(
			repeaterItem.querySelector(
				'.repeater-item-inner[data-control-key="btn_bg"]'
			)
		);
		if (!bg) {
			bg = readControlInnerColor(
				repeaterItem.querySelector(
					'.repeater-item-inner[data-control-key="ecbb_background"]'
				)
			);
		}
		if (bg) {
			wrapper.style.setProperty("--ecbb-btn-bg", bg);
		} else {
			wrapper.style.removeProperty("--ecbb-btn-bg");
		}

		var textColor = readControlInnerColor(
			repeaterItem.querySelector(
				'.repeater-item-inner[data-control-key="btn_text_color"]'
			)
		);
		if (
			!textColor &&
			wrapper &&
			preview &&
			preview.defaultView
		) {
			textColor = preview.defaultView.getComputedStyle(wrapper).color;
		}
		if (textColor) {
			wrapper.style.setProperty("--ecbb-btn-fg", textColor);
		} else {
			wrapper.style.removeProperty("--ecbb-btn-fg");
		}

		link.style.removeProperty("background-color");
		link.style.removeProperty("color");
	}

	function copyComputedButtonChrome(wrapper, link, preview) {
		if (!wrapper || !link || !preview || !preview.defaultView) {
			return;
		}

		var cs = preview.defaultView.getComputedStyle(wrapper);

		if (cs.paddingTop && cs.paddingTop !== "0px") {
			link.style.paddingTop = cs.paddingTop;
			link.style.paddingRight = cs.paddingRight;
			link.style.paddingBottom = cs.paddingBottom;
			link.style.paddingLeft = cs.paddingLeft;
		}
	}

	function applyButtonBorderColor(repeaterItem, link) {
		if (!repeaterItem || !link) {
			return;
		}

		var borderColor = readControlInnerColor(
			repeaterItem.querySelector(
				'.repeater-item-inner[data-control-key="btn_border_color"]'
			)
		);
		if (borderColor) {
			link.style.setProperty("border", "1px solid " + borderColor);
			link.style.setProperty("box-sizing", "border-box");
		} else {
			link.style.removeProperty("border");
		}
	}

	/**
	 * Button paint target: link when hover is on, plain span when hover is off + button styles.
	 */
	function getButtonPaintSurface(wrapper) {
		if (!wrapper) {
			return null;
		}
		var link = wrapper.querySelector(".ecbb-event__link");
		if (link) {
			return link;
		}
		if (wrapper.classList.contains("ecbb-has-btn")) {
			return wrapper.querySelector(".ecbb-event__plain");
		}
		return null;
	}

	function clearButtonPaintFromWrapper(wrapper) {
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

		wrapper.querySelectorAll(".ecbb-event__link, .ecbb-event__plain").forEach(function (node) {
			node.removeAttribute("style");
		});
	}

	/**
	 * Bricks repeater live CSS paints the row wrapper; keep button paint on the inner surface.
	 */
	function syncButtonPaintToInnerLink(repeaterItem) {
		var preview = getPreviewDocument();
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

		var btnStyleInner = repeaterItem.querySelector(
			'.repeater-item-inner[data-control-key="btn_style"]'
		);
		var btnStyleOn = false;
		if (btnStyleInner) {
			var btnCb = btnStyleInner.querySelector('input[type="checkbox"]');
			if (btnCb) {
				btnStyleOn = btnCb.checked;
			}
		}
		var hoverOn = readUseHoverValue(repeaterItem);

		if (!hoverOn || !btnStyleOn) {
			clearButtonPaintFromWrapper(wrapper);
			return;
		}

		wrapper.classList.add("ecbb-has-btn");

		var surface = getButtonPaintSurface(wrapper);
		if (!surface) {
			return;
		}

		[
			"backgroundColor",
			"background",
			"color",
			"padding",
			"border",
			"borderRadius",
		].forEach(function (prop) {
			if (wrapper.style[prop]) {
				surface.style[prop] = wrapper.style[prop];
				wrapper.style[prop] = "";
			}
		});

		copyComputedButtonChrome(wrapper, surface, preview);
		applyButtonBorderColor(repeaterItem, surface);
		applyButtonColors(repeaterItem, surface, preview, wrapper);

		surface.style.setProperty("display", "inline-flex");
		surface.style.setProperty("align-items", "center");
		surface.style.setProperty("justify-content", "center");
		surface.style.setProperty("width", "auto");
		surface.style.setProperty("max-width", "100%");
		surface.style.setProperty("box-sizing", "border-box");
	}

	var btnSyncTimer = null;
	function scheduleButtonPaintSync(repeaterItem) {
		if (!repeaterItem) {
			return;
		}
		if (btnSyncTimer) {
			clearTimeout(btnSyncTimer);
		}
		btnSyncTimer = setTimeout(function () {
			btnSyncTimer = null;
			syncButtonPaintToInnerLink(repeaterItem);
			// Bricks border/spacing controls may apply after the first paint pass.
			setTimeout(function () {
				syncButtonPaintToInnerLink(repeaterItem);
			}, 120);
		}, 60);
	}

	function syncAllButtonPaint() {
		document
			.querySelectorAll(".ecbb-parts-repeater-item")
			.forEach(function (item) {
				if (
					item.querySelector(
						'.repeater-item-inner[data-control-key="btn_style"]'
					)
				) {
					syncButtonPaintToInnerLink(item);
				}
			});
	}

	function onButtonControlInteraction(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item) {
			return;
		}

		var i;
		for (i = 0; i < BTN_PAINT_KEYS.length; i++) {
			if (
				e.target.closest(
					'.repeater-item-inner[data-control-key="' +
						BTN_PAINT_KEYS[i] +
						'"]'
				)
			) {
				scheduleButtonPaintSync(item);
				return;
			}
		}
	}

	document.addEventListener(
		"change",
		function (e) {
			var item = e.target.closest(".ecbb-parts-repeater-item");
			if (!item) {
				return;
			}
			if (e.target.closest('.repeater-item-inner[data-control-key="part"]')) {
				syncHoverVisibility(item);
				return;
			}
			if (e.target.closest('.repeater-item-inner[data-control-key="link"]')) {
				syncHoverVisibility(item);
				scheduleTitleInnerBackgroundSync(item);
				return;
			}
			if (e.target.closest('.repeater-item-inner[data-control-key="ecbb_use_hover"]')) {
				syncUseHoverEnabled(item);
				scheduleButtonPaintSync(item);
				return;
			}
			if (e.target.closest('.repeater-item-inner[data-control-key="ecbb_typography"]')) {
				scheduleTypographyColorSync(item);
				scheduleButtonPaintSync(item);
			}
			if (e.target.closest('.repeater-item-inner[data-control-key="ecbb_background_inner"]')) {
				scheduleTitleInnerBackgroundSync(item);
			}
			if (
				e.target.closest('.repeater-item-inner[data-control-key="btn_style"]') ||
				e.target.closest('.repeater-item-inner[data-control-key="btn_bg"]') ||
				e.target.closest('.repeater-item-inner[data-control-key="btn_text_color"]') ||
				e.target.closest('.repeater-item-inner[data-control-key="btn_border_color"]') ||
				e.target.closest('.repeater-item-inner[data-control-key="btn_padding"]') ||
				e.target.closest('.repeater-item-inner[data-control-key="ecbb_background"]')
			) {
				scheduleButtonPaintSync(item);
			}
		},
		true
	);

	document.addEventListener("input", onTypographyControlInteraction, true);
	document.addEventListener("input", onButtonControlInteraction, true);
	document.addEventListener("change", onButtonControlInteraction, true);
	document.addEventListener("input", onTitleInnerBackgroundInteraction, true);
	document.addEventListener("change", onTitleInnerBackgroundInteraction, true);

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
				syncUseHoverEnabled(item);
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
			syncButtons(item);
		},
		true
	);

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", scan);
	} else {
		scan();
	}

	var obs = new MutationObserver(scheduleScan);
	obs.observe(document.body, {
		childList: true,
		subtree: true,
		attributes: true,
		attributeFilter: ["class"],
	});
})();
