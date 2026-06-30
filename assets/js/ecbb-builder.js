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
		if (!item.hasAttribute("data-ecbb-btn-border-open")) {
			item.setAttribute("data-ecbb-btn-border-open", "true");
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
		bindAccordionToggle(item, "btn_sep_border", "data-ecbb-btn-border-open");
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

	function hookPreviewResync() {
		var preview = getPreviewDocument();
		if (!preview || !preview.body || preview.__ecbbPreviewSyncHooked) {
			return;
		}
		preview.__ecbbPreviewSyncHooked = true;
		var previewObs = new MutationObserver(scheduleScan);
		previewObs.observe(preview.body, {
			childList: true,
			subtree: true,
		});
	}

	function scan() {
		document.querySelectorAll(".repeater-item").forEach(function (item) {
			ensureTabs(item);
			syncHoverVisibility(item);
			ensureAccordions(item);
		});
		hookPreviewResync();
		syncAllButtonPaint();
		syncAllTitleInnerBackground();
		syncAllCategoryChipBackgrounds();
		syncAllActionButtonRepeaterStyles();
		syncAllTypographyFromWrapper();
	}

	function syncAllTypographyFromWrapper() {
		document
			.querySelectorAll(".ecbb-parts-repeater-item")
			.forEach(function (item) {
				syncTypographyColorFromWrapper(item);
			});
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

	var ACTION_BUTTON_PARTS = ["read_more", "event_tickets", "event_rsvp"];

	function isActionButtonPart(repeaterItem) {
		return ACTION_BUTTON_PARTS.indexOf(readPartValue(repeaterItem)) !== -1;
	}

	function getActionButtonSurfaces(wrapper) {
		if (!wrapper) {
			return [];
		}
		return wrapper.querySelectorAll(
			".ecbb-event__link, a.event-button, a.ecbb-event-card__button"
		);
	}

	function readBtnStyleEnabled(repeaterItem) {
		var btnStyleInner = repeaterItem.querySelector(
			'.repeater-item-inner[data-control-key="btn_style"]'
		);
		if (!btnStyleInner) {
			return false;
		}
		var btnCb = btnStyleInner.querySelector('input[type="checkbox"]');
		return !!(btnCb && btnCb.checked);
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

		var targets = wrapper.querySelectorAll(
			".ecbb-event__term-chip, .ecbb-event__link, .ecbb-event__term, .ecbb-event__date-day, .ecbb-event__date-time, .ecbb-event__date-sep, a.event-button, a.ecbb-event-card__button, .ecbb-event-card__category"
		);
		var useImportant = isActionButtonPart(repeaterItem) && !readBtnStyleEnabled(repeaterItem);

		targets.forEach(function (node) {
			if (useImportant) {
				node.style.setProperty("color", color, "important");
			} else {
				node.style.setProperty("color", color);
			}
		});
	}

	/** View Details / tickets / RSVP: Bricks paints the row wrapper; paint the inner button. */
	function syncActionButtonRepeaterStyle(repeaterItem) {
		if (!repeaterItem || !isActionButtonPart(repeaterItem)) {
			return;
		}
		if (readBtnStyleEnabled(repeaterItem)) {
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
		if (!wrapper || !preview.defaultView) {
			return;
		}

		var surfaces = getActionButtonSurfaces(wrapper);
		if (!surfaces.length) {
			return;
		}

		var computed = preview.defaultView.getComputedStyle(wrapper);
		var bg = readControlInnerColor(
			repeaterItem.querySelector(
				'.repeater-item-inner[data-control-key="ecbb_background"]'
			)
		);
		if (
			!bg &&
			computed.backgroundColor &&
			computed.backgroundColor !== "rgba(0, 0, 0, 0)" &&
			computed.backgroundColor !== "transparent"
		) {
			bg = computed.backgroundColor;
		}

		var pad =
			computed.paddingTop !== "0px" ||
			computed.paddingRight !== "0px" ||
			computed.paddingBottom !== "0px" ||
			computed.paddingLeft !== "0px"
				? computed.padding
				: "";

		wrapper.style.setProperty("padding", "0", "important");
		wrapper.style.setProperty("background-color", "transparent", "important");

		surfaces.forEach(function (node) {
			if (bg) {
				node.style.setProperty("background-color", bg, "important");
			} else {
				node.style.removeProperty("background-color");
			}
			if (pad) {
				node.style.setProperty("padding", pad, "important");
			} else {
				node.style.removeProperty("padding");
			}
			if (computed.color) {
				node.style.setProperty("color", computed.color, "important");
			}
			if (computed.fontFamily) {
				node.style.setProperty("font-family", computed.fontFamily, "important");
			}
			if (computed.fontSize) {
				node.style.setProperty("font-size", computed.fontSize, "important");
			}
			if (computed.fontWeight) {
				node.style.setProperty("font-weight", computed.fontWeight, "important");
			}
			if (computed.lineHeight) {
				node.style.setProperty("line-height", computed.lineHeight, "important");
			}
			if (computed.letterSpacing) {
				node.style.setProperty("letter-spacing", computed.letterSpacing, "important");
			}
			if (computed.textTransform && computed.textTransform !== "none") {
				node.style.setProperty("text-transform", computed.textTransform, "important");
			}
		});
	}

	var actionBtnSyncTimer = null;
	function scheduleActionButtonRepeaterStyleSync(repeaterItem) {
		if (!repeaterItem || !isActionButtonPart(repeaterItem)) {
			return;
		}
		if (actionBtnSyncTimer) {
			clearTimeout(actionBtnSyncTimer);
		}
		actionBtnSyncTimer = setTimeout(function () {
			actionBtnSyncTimer = null;
			syncActionButtonRepeaterStyle(repeaterItem);
		}, 60);
	}

	function syncAllActionButtonRepeaterStyles() {
		document
			.querySelectorAll(".ecbb-parts-repeater-item")
			.forEach(function (item) {
				if (isActionButtonPart(item)) {
					syncActionButtonRepeaterStyle(item);
				}
			});
	}

	function onActionButtonStyleInteraction(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item || !isActionButtonPart(item)) {
			return;
		}
		if (
			e.target.closest(
				'.repeater-item-inner[data-control-key="ecbb_background"]'
			) ||
			e.target.closest(
				'.repeater-item-inner[data-control-key="ecbb_padding"]'
			) ||
			e.target.closest(
				'.repeater-item-inner[data-control-key="ecbb_typography"]'
			) ||
			e.target.closest(
				'.repeater-item-inner[data-control-key="ecbb_text_align"]'
			)
		) {
			scheduleActionButtonRepeaterStyleSync(item);
		}
	}

	/** Categories (Style 1 chips + Style 2 pills): Bricks paints the wrapper; paint each button. */
	function syncCategoryChipBackground(repeaterItem) {
		if (!repeaterItem || readPartValue(repeaterItem) !== "categories") {
			return;
		}

		var preview = getPreviewDocument();
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

		var bg = readControlInnerColor(
			repeaterItem.querySelector(
				'.repeater-item-inner[data-control-key="ecbb_background"]'
			)
		);
		if (
			!bg &&
			preview.defaultView.getComputedStyle(wrapper).backgroundColor &&
			preview.defaultView.getComputedStyle(wrapper).backgroundColor !== "rgba(0, 0, 0, 0)" &&
			preview.defaultView.getComputedStyle(wrapper).backgroundColor !== "transparent"
		) {
			bg = preview.defaultView.getComputedStyle(wrapper).backgroundColor;
		}

		var computed = preview.defaultView.getComputedStyle(wrapper);
		var pad =
			computed.paddingTop !== "0px" ||
			computed.paddingRight !== "0px" ||
			computed.paddingBottom !== "0px" ||
			computed.paddingLeft !== "0px"
				? computed.padding
				: "";

		wrapper.style.setProperty("background-color", "transparent", "important");
		if (pad) {
			wrapper.style.setProperty("padding", "0", "important");
		}

		wrapper
			.querySelectorAll(".ecbb-event__term-chip, .ecbb-event-card__category")
			.forEach(function (chip) {
				if (bg) {
					chip.style.setProperty("background-color", bg, "important");
				} else {
					chip.style.removeProperty("background-color");
				}
				if (pad) {
					chip.style.setProperty("padding", pad, "important");
				}
			});
	}

	var categoryChipBgTimer = null;
	function scheduleCategoryChipBackgroundSync(repeaterItem) {
		if (!repeaterItem) {
			return;
		}
		if (categoryChipBgTimer) {
			clearTimeout(categoryChipBgTimer);
		}
		categoryChipBgTimer = setTimeout(function () {
			categoryChipBgTimer = null;
			syncCategoryChipBackground(repeaterItem);
		}, 60);
	}

	function syncAllCategoryChipBackgrounds() {
		document
			.querySelectorAll(".ecbb-parts-repeater-item")
			.forEach(function (item) {
				if (readPartValue(item) === "categories") {
					syncCategoryChipBackground(item);
				}
			});
	}

	function onCategoryChipBackgroundInteraction(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item || readPartValue(item) !== "categories") {
			return;
		}
		if (
			e.target.closest(
				'.repeater-item-inner[data-control-key="ecbb_background"]'
			) ||
			e.target.closest(
				'.repeater-item-inner[data-control-key="ecbb_padding"]'
			)
		) {
			scheduleCategoryChipBackgroundSync(item);
		}
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
			scheduleActionButtonRepeaterStyleSync(repeaterItem);
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

	var BTN_STYLE_PAINT_KEYS = ["btn_bg", "btn_text_color", "ecbb_background"];
	var BTN_BORDER_PAINT_KEYS = Array.isArray(L.btnBorderKeys)
		? L.btnBorderKeys.filter(function (key) {
				return key !== "btn_sep_border";
		  })
		: [
				"btn_border_type",
				"btn_border_width",
				"btn_border_color",
				"btn_padding",
				"btn_border_radius",
		  ];
	var BTN_PAINT_KEYS = BTN_STYLE_PAINT_KEYS.concat(BTN_BORDER_PAINT_KEYS);

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

	function applyButtonBorderChrome(repeaterItem, surface) {
		if (!repeaterItem || !surface) {
			return;
		}

		var padding = readSpacingControlValue(
			repeaterItem.querySelector(
				'.repeater-item-inner[data-control-key="btn_padding"]'
			)
		);
		if (padding) {
			surface.style.padding = padding;
		} else {
			surface.style.removeProperty("padding");
		}

		var radius = readSpacingControlValue(
			repeaterItem.querySelector(
				'.repeater-item-inner[data-control-key="btn_border_radius"]'
			)
		);
		if (radius) {
			surface.style.borderRadius = radius;
		} else {
			surface.style.removeProperty("border-radius");
		}

		var borderType =
			readSelectControlValue(
				repeaterItem.querySelector(
					'.repeater-item-inner[data-control-key="btn_border_type"]'
				)
			) || "solid";
		var borderWidth =
			readNumberControlValue(
				repeaterItem.querySelector(
					'.repeater-item-inner[data-control-key="btn_border_width"]'
				)
			) || "1px";
		var borderColor = readControlInnerColor(
			repeaterItem.querySelector(
				'.repeater-item-inner[data-control-key="btn_border_color"]'
			)
		);

		if (borderType === "none") {
			surface.style.setProperty("border", "none");
		} else if (borderColor) {
			surface.style.setProperty(
				"border",
				borderWidth + " " + borderType + " " + borderColor
			);
		} else {
			surface.style.removeProperty("border");
		}

		surface.style.setProperty("box-sizing", "border-box");
	}

	function applyButtonBorderColor(repeaterItem, link) {
		applyButtonBorderChrome(repeaterItem, link);
	}

	/**
	 * Button paint target: link when hover is on, plain span when hover is off + button styles.
	 */
	function getButtonPaintSurface(wrapper) {
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

		var btnStyleOn = readBtnStyleEnabled(repeaterItem);
		if (!btnStyleOn) {
			wrapper.classList.remove("ecbb-has-btn");
			if (isActionButtonPart(repeaterItem)) {
				syncActionButtonRepeaterStyle(repeaterItem);
				return;
			}
			clearButtonPaintFromWrapper(wrapper);
			return;
		}

		wrapper.classList.add("ecbb-has-btn");

		var surface = getButtonPaintSurface(wrapper);
		if (!surface) {
			return;
		}

		wrapper.style.setProperty("padding", "0");

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

		applyButtonBorderChrome(repeaterItem, surface);
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
				e.target.closest('.repeater-item-inner[data-control-key="btn_border_type"]') ||
				e.target.closest('.repeater-item-inner[data-control-key="btn_border_width"]') ||
				e.target.closest('.repeater-item-inner[data-control-key="btn_border_color"]') ||
				e.target.closest('.repeater-item-inner[data-control-key="btn_padding"]') ||
				e.target.closest('.repeater-item-inner[data-control-key="btn_border_radius"]') ||
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
	document.addEventListener("input", onActionButtonStyleInteraction, true);
	document.addEventListener("change", onActionButtonStyleInteraction, true);
	document.addEventListener("input", onTitleInnerBackgroundInteraction, true);
	document.addEventListener("change", onTitleInnerBackgroundInteraction, true);
	document.addEventListener("input", onCategoryChipBackgroundInteraction, true);
	document.addEventListener("change", onCategoryChipBackgroundInteraction, true);

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
