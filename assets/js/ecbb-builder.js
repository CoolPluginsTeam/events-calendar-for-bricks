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
		var partInner = getControlInner(item, "part");
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
		var part = readPartValue(repeaterItem);
		if (part) {
			return part;
		}
		return repeaterItem.getAttribute("data-ecbb-part") || "";
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
		var inner = getControlInner(item, "link");
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

		var inner = getControlInner(item, "ecbb_use_hover");
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

		var part = readRepeaterPartSlug(item);
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
		if (!item.hasAttribute("data-ecbb-btn-border-open")) {
			item.setAttribute("data-ecbb-btn-border-open", "true");
		}
	}

	function bindAccordionToggle(item, sepKey, attrName) {
		var sep = getControlInner(item, sepKey);
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
		var partEl = getControlInner(item, "part");
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
		runAllSyncPasses(true);
	}

	function syncAllTypographyFromWrapper() {
		if (typographySync) {
			typographySync.syncAll();
		}
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

	function getControlInner(item, key) {
		if (!item || !key) {
			return null;
		}
		return item.querySelector(
			'.repeater-item-inner[data-control-key="' + key + '"]'
		);
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

	function getBuilderPanelRoot() {
		return (
			document.querySelector(
				"#bricks-panel, #bricks-panel-wrapper, .bricks-panel, .brx-panel"
			) || document.body
		);
	}

	function mutationTouchesRepeater(mutations) {
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

	var syncRegistry = [];
	var actionButtonSync;
	var categoryChipSync;
	var titleInnerBgSync;
	var typographySync;
	var hoverSync;
	var buttonPaintSync;

	function createSync(options) {
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
		syncRegistry.push(entry);
		return entry;
	}

	function runAllSyncPasses(includeDelayed) {
		var i;
		for (i = 0; i < syncRegistry.length; i++) {
			syncRegistry[i].syncAll();
		}
		if (!includeDelayed || !hoverSync || !typographySync) {
			return;
		}
		setTimeout(function () {
			hoverSync.syncAll();
		}, 150);
		setTimeout(function () {
			hoverSync.syncAll();
		}, 350);
		setTimeout(function () {
			typographySync.syncAll();
		}, 150);
		setTimeout(function () {
			typographySync.syncAll();
		}, 350);
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
		var btnStyleInner = getControlInner(repeaterItem, "btn_style");
		if (!btnStyleInner) {
			return false;
		}
		var btnCb = btnStyleInner.querySelector('input[type="checkbox"]');
		return !!(btnCb && btnCb.checked);
	}

	function readTypographyColor(repeaterItem) {
		return readControlInnerColor(getControlInner(repeaterItem, "ecbb_typography"));
	}

	function isLayoutChromeNode(node, repeaterItem) {
		if (!node || !repeaterItem) {
			return false;
		}
		var part = readRepeaterPartSlug(repeaterItem);
		if (
			( part === "read_more" ||
				part === "event_tickets" ||
				part === "event_rsvp" ) &&
			!readBtnStyleEnabled(repeaterItem) &&
			node.matches(
				"a.event-button, a.ecbb-event-card__button, .ecbb-event__link.event-button, .ecbb-event__link.ecbb-event-card__button"
			)
		) {
			return true;
		}
		if (
			part === "categories" &&
			node.matches(".ecbb-event-card__category, a.ecbb-event-card__category")
		) {
			return true;
		}
		return false;
	}

	function clearLayoutChromeInlineStyles(repeaterItem) {
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
		wrapper
			.querySelectorAll(
				".ecbb-event__term-chip, .ecbb-event__link, .ecbb-event__term, a.event-button, a.ecbb-event-card__button, .ecbb-event-card__category"
			)
			.forEach(function (node) {
				if (!isLayoutChromeNode(node, repeaterItem)) {
					return;
				}
				node.style.removeProperty("color");
				node.style.removeProperty("background-color");
				node.style.removeProperty("padding");
			});
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

		var typoColor = readTypographyColor(repeaterItem);
		if (!typoColor) {
			clearLayoutChromeInlineStyles(repeaterItem);
			return;
		}

		var targets = wrapper.querySelectorAll(
			".ecbb-event__term-chip, .ecbb-event__link, .ecbb-event__term, .ecbb-event__date-day, .ecbb-event__date-time, .ecbb-event__date-sep, a.event-button, a.ecbb-event-card__button, .ecbb-event-card__category"
		);

		targets.forEach(function (node) {
			if (isLayoutChromeNode(node, repeaterItem)) {
				return;
			}
			node.style.setProperty("color", typoColor);
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

		var bg = readControlInnerColor(getControlInner(repeaterItem, "ecbb_background"));
		var pad = readSpacingControlValue(getControlInner(repeaterItem, "ecbb_padding"));
		var typoColor = readControlInnerColor(getControlInner(repeaterItem, "ecbb_typography"));
		var computed = preview.defaultView.getComputedStyle(wrapper);

		if (bg || pad) {
			wrapper.style.setProperty("padding", "0", "important");
			wrapper.style.setProperty("background-color", "transparent", "important");
		}

		surfaces.forEach(function (node) {
			if (bg) {
				node.style.setProperty("background-color", bg, "important");
			}
			if (pad) {
				node.style.setProperty("padding", pad, "important");
			}
			if (typoColor) {
				node.style.setProperty("color", typoColor, "important");
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
			if (computed.letterSpacing) {
				node.style.setProperty("letter-spacing", computed.letterSpacing, "important");
			}
			if (computed.textTransform && computed.textTransform !== "none") {
				node.style.setProperty("text-transform", computed.textTransform, "important");
			}
			// Keep layout buttons on their native baseline so font-size changes do not
			// look like extra top padding in the builder preview.
			node.style.setProperty("line-height", "1", "important");
		});
	}

	function scheduleActionButtonRepeaterStyleSync(repeaterItem) {
		if (actionButtonSync) {
			actionButtonSync.schedule(repeaterItem);
			return;
		}
		syncActionButtonRepeaterStyle(repeaterItem);
	}

	function syncAllActionButtonRepeaterStyles() {
		if (actionButtonSync) {
			actionButtonSync.syncAll();
		}
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
		if (!repeaterItem || readRepeaterPartSlug(repeaterItem) !== "categories") {
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

		var bg = readControlInnerColor(getControlInner(repeaterItem, "ecbb_background"));
		var pad = readSpacingControlValue(getControlInner(repeaterItem, "ecbb_padding"));

		if (!bg && !pad) {
			clearLayoutChromeInlineStyles(repeaterItem);
			return;
		}

		wrapper.style.setProperty("background-color", "transparent", "important");
		if (pad) {
			wrapper.style.setProperty("padding", "0", "important");
		}

		wrapper
			.querySelectorAll(".ecbb-event__term-chip, .ecbb-event-card__category")
			.forEach(function (chip) {
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
			});
	}

	function scheduleCategoryChipBackgroundSync(repeaterItem) {
		if (categoryChipSync) {
			categoryChipSync.schedule(repeaterItem);
			return;
		}
		syncCategoryChipBackground(repeaterItem);
	}

	function syncAllCategoryChipBackgrounds() {
		if (categoryChipSync) {
			categoryChipSync.syncAll();
		}
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

		var bg = readControlInnerColor(getControlInner(repeaterItem, "ecbb_background"));
		if (!bg) {
			bg = readControlInnerColor(getControlInner(repeaterItem, "ecbb_background_inner"));
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

	function scheduleTitleInnerBackgroundSync(repeaterItem) {
		if (titleInnerBgSync) {
			titleInnerBgSync.schedule(repeaterItem);
			return;
		}
		syncTitleInnerBackground(repeaterItem);
	}

	function syncAllTitleInnerBackground() {
		if (titleInnerBgSync) {
			titleInnerBgSync.syncAll();
		}
	}

	function onTitleInnerBackgroundInteraction(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item) {
			return;
		}
		if (
			e.target.closest(
				'.repeater-item-inner[data-control-key="ecbb_background"]'
			) ||
			e.target.closest(
				'.repeater-item-inner[data-control-key="ecbb_background_inner"]'
			)
		) {
			scheduleTitleInnerBackgroundSync(item);
		}
	}

	function syncButtonTypographyForeground(repeaterItem) {
		if (!repeaterItem || !readBtnStyleEnabled(repeaterItem)) {
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

		var textColor = readControlInnerColor(getControlInner(repeaterItem, "ecbb_typography"));
		if (!textColor && preview.defaultView) {
			textColor = preview.defaultView.getComputedStyle(wrapper).color;
		}
		if (textColor) {
			wrapper.style.setProperty("--ecbb-btn-fg", textColor);
		}
	}

	function runTypographySyncPasses(repeaterItem) {
		syncTypographyColorFromWrapper(repeaterItem);
		syncButtonTypographyForeground(repeaterItem);
		scheduleActionButtonRepeaterStyleSync(repeaterItem);
		scheduleHoverPreviewSync(repeaterItem);
	}

	function scheduleTypographyColorSync(repeaterItem) {
		if (typographySync) {
			typographySync.schedule(repeaterItem);
			return;
		}
		runTypographySyncPasses(repeaterItem);
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

	var BTN_STYLE_PAINT_KEYS = ["ecbb_background"];
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
	var HOVER_PAINT_KEYS = Array.isArray(L.hoverKeys)
		? L.hoverKeys.filter(function (key) {
				return (
					key === "ecbb_use_hover" ||
					key === "ecbb_hover_color" ||
					key === "ecbb_hover_background"
				);
		  })
		: [ "ecbb_use_hover", "ecbb_hover_color", "ecbb_hover_background" ];

	function readRepeaterHoverPaint(repeaterItem, key) {
		var color = readControlInnerColor(getControlInner(repeaterItem, key));
		return isMeaningfulHoverColor(color) ? color : "";
	}

	function readRepeaterHoverColor(repeaterItem) {
		return readRepeaterHoverPaint(repeaterItem, "ecbb_hover_color");
	}

	function readRepeaterHoverBackground(repeaterItem) {
		return readRepeaterHoverPaint(repeaterItem, "ecbb_hover_background");
	}

	function isMeaningfulHoverColor(value) {
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

	function isStyle2WidgetPreview(preview) {
		return !!(
			preview &&
			preview.querySelector(
				".ecbb-ev__item--style-2, .ecbb-ev__item-inner--style-2"
			)
		);
	}

	function repeaterHasHoverPaint(repeaterItem) {
		return !!(
			readRepeaterHoverColor(repeaterItem) ||
			readRepeaterHoverBackground(repeaterItem)
		);
	}

	function getPreviewPartWrapper(repeaterItem, preview) {
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

	function partHoverEnabled(repeaterItem) {
		if (!repeaterItem) {
			return false;
		}
		var part = readRepeaterPartSlug(repeaterItem);
		if (!partSupportsHover(part, repeaterItem)) {
			return false;
		}
		if (repeaterHasHoverPaint(repeaterItem)) {
			return true;
		}
		return readUseHoverValue(repeaterItem);
	}

	function buildPreviewHoverScope(rowId) {
		return '[data-field-id="' + rowId + '"]';
	}

	function buildPreviewHoverBgSelectors(scope) {
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

	function buildPreviewHoverColorSelectors(scope) {
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

	var hoverPreviewRules =
		typeof WeakMap !== "undefined" ? new WeakMap() : null;
	var hoverPreviewFallback = {};

	function getBuilderHoverStyleEl(preview) {
		var doc =
			preview && preview.defaultView
				? preview.defaultView.document
				: document;
		var el = doc.getElementById("ecbb-builder-hover-css");
		if (!el) {
			el = doc.createElement("style");
			el.id = "ecbb-builder-hover-css";
			doc.head.appendChild(el);
		}
		return el;
	}

	function rebuildHoverPreviewStylesheet(preview) {
		var el = getBuilderHoverStyleEl(preview);
		if (!el) {
			return;
		}
		var chunks = [];
		if (hoverPreviewRules) {
			hoverPreviewRules.forEach(function (rule) {
				if (rule) {
					chunks.push(rule);
				}
			});
		} else {
			Object.keys(hoverPreviewFallback).forEach(function (key) {
				if (hoverPreviewFallback[key]) {
					chunks.push(hoverPreviewFallback[key]);
				}
			});
		}
		el.textContent = chunks.join("\n");
	}

	function syncPreviewHoverClass(repeaterItem) {
		var preview = getPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}

		var wrapper = getPreviewPartWrapper(repeaterItem, preview);
		if (!wrapper) {
			return;
		}

		var useHoverInner = getControlInner(repeaterItem, "ecbb_use_hover");
		if (useHoverInner) {
			wrapper.classList.toggle("ecbb-no-hover", !partHoverEnabled(repeaterItem));
		}
	}

	function syncHoverPreviewStyles(repeaterItem) {
		var preview = getPreviewDocument();
		if (!preview || !repeaterItem) {
			return;
		}

		syncPreviewHoverClass(repeaterItem);

		var wrapper = getPreviewPartWrapper(repeaterItem, preview);
		if (!wrapper) {
			return;
		}

		var rowId = readRepeaterRowId(repeaterItem);
		var enabled = partHoverEnabled(repeaterItem);
		var hoverColor = readRepeaterHoverColor(repeaterItem);
		var hoverBg = readRepeaterHoverBackground(repeaterItem);

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

		var scope = buildPreviewHoverScope(rowId);
		var rule = "";
		if (enabled) {
			if (
				readRepeaterPartSlug(repeaterItem) === "categories" &&
				isStyle2WidgetPreview(preview)
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
				rule += buildPreviewHoverColorSelectors(scope) + "{color:" + hoverColor + " !important;}";
			}
			if (hoverBg) {
				rule += buildPreviewHoverBgSelectors(scope) + "{background-color:" + hoverBg + " !important;}";
			}
		}

		if (hoverPreviewRules) {
			hoverPreviewRules.set(repeaterItem, rule);
		} else {
			hoverPreviewFallback[rowId] = rule;
		}

		rebuildHoverPreviewStylesheet(preview);
	}

	function scheduleHoverPreviewSync(repeaterItem) {
		if (hoverSync) {
			hoverSync.schedule(repeaterItem);
			return;
		}
		syncHoverPreviewStyles(repeaterItem);
	}

	function syncAllHoverPreviewStyles() {
		if (hoverSync) {
			hoverSync.syncAll();
		}
	}

	function onHoverControlInteraction(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item) {
			return;
		}

		var i;
		for (i = 0; i < HOVER_PAINT_KEYS.length; i++) {
			if (
				e.target.closest(
					'.repeater-item-inner[data-control-key="' +
						HOVER_PAINT_KEYS[i] +
						'"]'
				)
			) {
				scheduleHoverPreviewSync(item);
				return;
			}
		}
	}

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

		var bg = readControlInnerColor(getControlInner(repeaterItem, "ecbb_background"));
		if (bg) {
			wrapper.style.setProperty("--ecbb-btn-bg", bg);
		}

		var textColor = readControlInnerColor(
			repeaterItem.querySelector(
				'.repeater-item-inner[data-control-key="ecbb_typography"]'
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

		var padding = readSpacingControlValue(getControlInner(repeaterItem, "btn_padding"));
		if (padding) {
			surface.style.padding = padding;
		}

		var radius = readSpacingControlValue(getControlInner(repeaterItem, "btn_border_radius"));
		if (radius) {
			surface.style.borderRadius = radius;
		}

		var borderType =
			readSelectControlValue(getControlInner(repeaterItem, "btn_border_type")) || "";
		var borderWidth = readNumberControlValue(
			getControlInner(repeaterItem, "btn_border_width")
		);
		var borderColor = readControlInnerColor(getControlInner(repeaterItem, "btn_border_color"));

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

		scheduleHoverPreviewSync(repeaterItem);

		surface.style.setProperty("display", "inline-flex");
		surface.style.setProperty("align-items", "center");
		surface.style.setProperty("justify-content", "center");
		surface.style.setProperty("width", "auto");
		surface.style.setProperty("max-width", "100%");
		surface.style.setProperty("box-sizing", "border-box");
	}

	function scheduleButtonPaintSync(repeaterItem) {
		if (buttonPaintSync) {
			buttonPaintSync.schedule(repeaterItem);
			return;
		}
		syncButtonPaintToInnerLink(repeaterItem);
	}

	function syncAllButtonPaint() {
		if (buttonPaintSync) {
			buttonPaintSync.syncAll();
		}
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

	function initSyncRegistry() {
		syncRegistry.length = 0;

		actionButtonSync = createSync({
			id: "actionButton",
			matches: isActionButtonPart,
			controlKeys: ["ecbb_background", "ecbb_padding", "ecbb_typography", "ecbb_text_align"],
			sync: syncActionButtonRepeaterStyle,
		});

		categoryChipSync = createSync({
			id: "categoryChip",
			matches: function (item) {
				return readRepeaterPartSlug(item) === "categories";
			},
			controlKeys: ["ecbb_background", "ecbb_padding"],
			sync: syncCategoryChipBackground,
		});

		titleInnerBgSync = createSync({
			id: "titleInnerBg",
			matches: function (item) {
				return readPartValue(item) === "title";
			},
			controlKeys: ["ecbb_background", "ecbb_background_inner"],
			sync: syncTitleInnerBackground,
		});

		typographySync = createSync({
			id: "typography",
			controlKeys: ["ecbb_typography"],
			sync: syncTypographyColorFromWrapper,
			run: runTypographySyncPasses,
		});

		hoverSync = createSync({
			id: "hover",
			controlKeys: HOVER_PAINT_KEYS,
			sync: syncHoverPreviewStyles,
			run: function (item) {
				syncHoverPreviewStyles(item);
				setTimeout(function () {
					syncHoverPreviewStyles(item);
				}, 120);
			},
		});

		buttonPaintSync = createSync({
			id: "buttonPaint",
			matches: function (item) {
				return !!getControlInner(item, "btn_style");
			},
			controlKeys: BTN_PAINT_KEYS,
			sync: syncButtonPaintToInnerLink,
			run: function (item) {
				syncButtonPaintToInnerLink(item);
				setTimeout(function () {
					syncButtonPaintToInnerLink(item);
				}, 120);
			},
		});
	}

	function routeRepeaterPanelInput(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item) {
			return;
		}
		var key = getControlKeyFromEvent(e);
		if (!key) {
			return;
		}
		var i;
		for (i = 0; i < syncRegistry.length; i++) {
			if (syncRegistry[i].handlesKey(key)) {
				syncRegistry[i].schedule(item);
			}
		}
	}

	function routeRepeaterPanelChange(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item) {
			return;
		}
		var key = getControlKeyFromEvent(e);
		if (key === "part") {
			syncHoverVisibility(item);
			runAllSyncPasses(false);
			return;
		}
		if (key === "link") {
			syncHoverVisibility(item);
			scheduleTitleInnerBackgroundSync(item);
			return;
		}
		if (key === "ecbb_use_hover") {
			syncUseHoverEnabled(item);
			scheduleButtonPaintSync(item);
			scheduleHoverPreviewSync(item);
			return;
		}
		routeRepeaterPanelInput(e);
	}

	function getControlKeyFromEvent(e) {
		var inner = e.target.closest(".repeater-item-inner[data-control-key]");
		if (!inner) {
			return "";
		}
		return inner.getAttribute("data-control-key") || "";
	}

	initSyncRegistry();

	document.addEventListener("input", routeRepeaterPanelInput, true);
	document.addEventListener("change", routeRepeaterPanelChange, true);

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
				scheduleHoverPreviewSync(item);
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

	var obs = new MutationObserver(function (mutations) {
		if (mutationTouchesRepeater(mutations)) {
			scheduleScan();
		}
	});
	obs.observe(getBuilderPanelRoot(), {
		childList: true,
		subtree: true,
		attributes: true,
		attributeFilter: ["class"],
	});
})();
