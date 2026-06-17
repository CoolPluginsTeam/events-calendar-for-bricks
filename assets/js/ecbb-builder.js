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

	function partSupportsHover(part) {
		return part !== "" && HOVER_PARTS.indexOf(part) !== -1;
	}

	function syncHoverVisibility(item) {
		if (!item || !item.classList.contains("ecbb-parts-repeater-item")) {
			return;
		}

		var part = readPartValue(item);
		item.setAttribute("data-ecbb-part", part);
		item.classList.toggle("ecbb-part-no-hover", part !== "" && !partSupportsHover(part));
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

	document.addEventListener(
		"change",
		function (e) {
			var item = e.target.closest(".ecbb-parts-repeater-item");
			if (
				!item ||
				!e.target.closest('.repeater-item-inner[data-control-key="part"]')
			) {
				return;
			}
			syncHoverVisibility(item);
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
