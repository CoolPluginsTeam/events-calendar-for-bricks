/**
 * Panel CONTENT/STYLE tabs, hover panel state, DOM helpers, row scanning.
 * Adds functions to window.ECBB.builder (see core.js).
 */
(function (builder) {
	"use strict";
	builder.readRepeaterPartControlValue = function(item) {
		var partInner = builder.getRepeaterControlInner(item, "part");
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

	builder.readRepeaterPartSlug = function(repeaterItem) {
		if (!repeaterItem) {
			return "";
		}
		var part = builder.readRepeaterPartControlValue(repeaterItem);
		if (part) {
			return part;
		}
		return repeaterItem.getAttribute("data-ecbb-part") || "";
	}

	builder.partSupportsHoverControls = function(part, item) {
		if (part === "" || builder.config.hoverCapableParts.indexOf(part) === -1) {
			return false;
		}
		if (part === "title" && item && !builder.isTitleLinkEnabledInPanel(item)) {
			return false;
		}
		return true;
	}

	builder.isTitleLinkEnabledInPanel = function(item) {
		if (!item) {
			return false;
		}
		var inner = builder.getRepeaterControlInner(item, "link");
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

	builder.isHoverStylingEnabledInPanel = function(item) {
		if (!item) {
			return true;
		}

		var inner = builder.getRepeaterControlInner(item, "ecbb_use_hover");
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

	builder.syncRepeaterHoverToggleAttribute = function(item) {
		if (!item || !item.classList.contains("ecbb-parts-repeater-item")) {
			return;
		}

		var part = builder.readRepeaterPartControlValue(item);
		if (!builder.partSupportsHoverControls(part, item)) {
			item.removeAttribute("data-ecbb-use-hover");
			return;
		}

		item.setAttribute(
			"data-ecbb-use-hover",
			builder.isHoverStylingEnabledInPanel(item) ? "true" : "false"
		);
	}

	builder.syncRepeaterHoverPanelState = function(item) {
		if (!item || !item.classList.contains("ecbb-parts-repeater-item")) {
			return;
		}

		var part = builder.readRepeaterPartSlug(item);
		item.setAttribute("data-ecbb-part", part);
		item.setAttribute(
			"data-ecbb-title-link",
			part === "title" && builder.isTitleLinkEnabledInPanel(item) ? "true" : "false"
		);
		item.classList.toggle("ecbb-part-no-hover", part !== "" && !builder.partSupportsHoverControls(part, item));
		builder.syncRepeaterHoverToggleAttribute(item);
	}

	builder.ensureRepeaterAccordionDefaultState = function(item) {
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

	builder.bindRepeaterAccordionToggle = function(item, sepKey, attrName) {
		var sep = builder.getRepeaterControlInner(item, sepKey);
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

	builder.initRepeaterPanelAccordions = function(item) {
		builder.ensureRepeaterAccordionDefaultState(item);
		builder.bindRepeaterAccordionToggle(item, "ecbb_sep_hover", "data-ecbb-hover-open");
		builder.bindRepeaterAccordionToggle(item, "btn_sep_border", "data-ecbb-btn-border-open");
	}

	builder.isEventPartsRepeaterRow = function(item) {
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

	builder.removeRepeaterContentStyleTabs = function(item) {
		var bar = item.querySelector(".ecbb-repeater-tabs");
		if (bar) {
			bar.remove();
		}
	}

	builder.injectRepeaterContentStyleTabs = function(item) {
		var partEl = builder.getRepeaterControlInner(item, "part");
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
		contentTabButton.textContent = builder.config.tabContentLabel;

		var styleTabButton = document.createElement("button");
		styleTabButton.type = "button";
		styleTabButton.className = "ecbb-repeater-tabs__btn";
		styleTabButton.setAttribute("role", "tab");
		styleTabButton.setAttribute("data-ecbb-tab-btn", "style");
		styleTabButton.setAttribute("aria-selected", "false");
		styleTabButton.textContent = builder.config.tabStyleLabel;

		wrap.appendChild(contentTabButton);
		wrap.appendChild(styleTabButton);

		partEl.insertAdjacentElement("afterend", wrap);

		builder.syncRepeaterTabButtonStates(item);
		builder.syncRepeaterHoverPanelState(item);
		builder.initRepeaterPanelAccordions(item);
	}

	builder.syncRepeaterTabButtonStates = function(item) {
		var tab = item.getAttribute("data-ecbb-tab") || "content";
		var btns = item.querySelectorAll(".ecbb-repeater-tabs__btn");
		btns.forEach(function (btn) {
			var id = btn.getAttribute("data-ecbb-tab-btn");
			var on = id === tab;
			btn.classList.toggle("is-active", on);
			btn.setAttribute("aria-selected", on ? "true" : "false");
		});
	}

	builder.ensureRepeaterContentStyleTabs = function(item) {
		if (!item || !item.classList || !item.classList.contains("repeater-item")) {
			return;
		}

		if (!builder.isEventPartsRepeaterRow(item)) {
			if (item.classList.contains("ecbb-parts-repeater-item")) {
				builder.removeRepeaterContentStyleTabs(item);
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
			builder.removeRepeaterContentStyleTabs(item);
			return;
		}

		if (item.querySelector(".ecbb-repeater-tabs")) {
			builder.syncRepeaterHoverPanelState(item);
			builder.initRepeaterPanelAccordions(item);
			return;
		}

		builder.injectRepeaterContentStyleTabs(item);
	}

	builder.hookPreviewIframeMutationResync = function() {
		var preview = builder.getBricksPreviewDocument();
		if (!preview || !preview.body || preview.__ecbbPreviewMutationSyncHooked) {
			return;
		}
		preview.__ecbbPreviewMutationSyncHooked = true;
		if (builder.preview.iframeEl && !builder.preview.iframeEl.__ecbbCacheInvalidationHooked) {
			builder.preview.iframeEl.__ecbbCacheInvalidationHooked = true;
			builder.preview.iframeEl.addEventListener("load", function () {
				builder.invalidateBricksPreviewDocumentCache();
			});
		}
		var previewIframeMutationObserver = new MutationObserver(function () {
			builder.schedulePreviewResync(true);
		});
		previewIframeMutationObserver.observe(preview.body, {
			childList: true,
			subtree: true,
		});
	}

	builder.getEventPartsRepeaterPanelScope = function() {
		return (
			document.querySelector("#bricks-panel-element") ||
			builder.getBricksBuilderPanelRoot()
		);
	};

	builder.scanRepeaterRowsForTabs = function() {
		var scope = builder.getEventPartsRepeaterPanelScope();
		scope.querySelectorAll(".repeater-item").forEach(function (item) {
			if (!builder.isEventPartsRepeaterRow(item)) {
				return;
			}
			builder.ensureRepeaterContentStyleTabs(item);
		});
	};

	builder.schedulePreviewResync = function(includeDelayed) {
		if (builder.preview.resyncTimer) {
			clearTimeout(builder.preview.resyncTimer);
		}
		builder.preview.resyncTimer = setTimeout(function () {
			builder.preview.resyncTimer = 0;
			if (builder.preview.resyncInFlight) {
				return;
			}
			builder.preview.resyncInFlight = true;
			try {
				builder.runAllPreviewSyncHandlers(includeDelayed !== false);
			} finally {
				builder.preview.resyncInFlight = false;
			}
		}, 120);
	};

	builder.scanRepeaterRowsAndSyncPreview = function() {
		builder.scanRepeaterRowsForTabs();
		builder.hookPreviewIframeMutationResync();
		builder.schedulePreviewResync(true);
	}

	builder.isRepeaterSortActive = function() {
		return !!document.querySelector(
			".repeater-item.ui-sortable-helper, .repeater-item.sortable-ghost, .repeater.ui-sortable-dragging, .sortable-fallback"
		);
	}

	builder.scheduleRepeaterRowScan = function() {
		if (builder.isRepeaterSortActive()) {
			return;
		}
		if (builder.tabs.scanTimer) {
			clearTimeout(builder.tabs.scanTimer);
		}
		builder.tabs.scanTimer = setTimeout(function () {
			builder.tabs.scanTimer = null;
			builder.scanRepeaterRowsAndSyncPreview();
		}, 80);
	}

	builder.watchRepeaterSortEnd = function() {
		if (builder.isRepeaterSortActive()) {
			builder.tabs.sortEndRaf = requestAnimationFrame(builder.watchRepeaterSortEnd);
			return;
		}
		builder.tabs.sortEndRaf = 0;
		builder.scanRepeaterRowsAndSyncPreview();
	}

	builder.scheduleRepeaterSortEndResync = function() {
		if (builder.tabs.sortEndRaf) {
			cancelAnimationFrame(builder.tabs.sortEndRaf);
		}
		builder.tabs.sortEndRaf = requestAnimationFrame(builder.watchRepeaterSortEnd);
	}

	builder.readRepeaterRowId = function(item) {
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

	builder.getRepeaterControlInner = function(item, key) {
		if (!item || !key) {
			return null;
		}
		return item.querySelector(
			'.repeater-item-inner[data-control-key="' + key + '"]'
		);
	}

	builder.invalidateBricksPreviewDocumentCache = function() {
		builder.preview.iframeEl = null;
		builder.preview.iframeDoc = null;
		if (builder.preview.repeaterCssCache) {
			builder.preview.repeaterCssCache.clear();
		}
	};

	builder.getBricksPreviewDocument = function() {
		if (builder.preview.iframeDoc && builder.preview.iframeEl) {
			try {
				if (
					builder.preview.iframeEl.contentDocument &&
					builder.preview.iframeEl.contentDocument.body
				) {
					return builder.preview.iframeDoc;
				}
			} catch (err) {
				builder.invalidateBricksPreviewDocumentCache();
			}
		}

		var selectors = [
			"#bricks-builder-iframe",
			"#bricks-preview-iframe",
			"iframe#bricks-preview",
		];
		var i;
		for (i = 0; i < selectors.length; i++) {
			var iframe = document.querySelector(selectors[i]);
			if (iframe && iframe.contentDocument && iframe.contentDocument.body) {
				builder.preview.iframeEl = iframe;
				builder.preview.iframeDoc = iframe.contentDocument;
				return builder.preview.iframeDoc;
			}
		}
		builder.invalidateBricksPreviewDocumentCache();
		return null;
	}

	/** Shared preview row lookup: preview doc, row id, and [data-field-id] wrapper. */
	builder.resolvePreviewRowContext = function(repeaterItem, opts) {
		opts = opts || {};
		if (!repeaterItem) {
			return null;
		}
		if (opts.matches && !opts.matches(repeaterItem)) {
			return null;
		}
		var preview = builder.getBricksPreviewDocument();
		if (!preview) {
			return null;
		}
		if (opts.needView && !preview.defaultView) {
			return null;
		}
		var rowId = builder.readRepeaterRowId(repeaterItem);
		if (!rowId) {
			return null;
		}
		var wrapper = preview.querySelector('[data-field-id="' + rowId + '"]');
		if (!wrapper) {
			return null;
		}
		if (opts.wrapperTest && !opts.wrapperTest(wrapper, preview, repeaterItem)) {
			return null;
		}
		return { preview: preview, rowId: rowId, wrapper: wrapper };
	}

	builder.getBricksBuilderPanelRoot = function() {
		return (
			document.querySelector(
				"#bricks-panel, #bricks-panel-wrapper, .bricks-panel, .brx-panel"
			) || document.body
		);
	}

	builder.mutationAffectsRepeaterRow = function(mutations) {
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
})(window.ECBB.builder);

