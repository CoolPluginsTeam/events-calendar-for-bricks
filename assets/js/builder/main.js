/**
 * Registry init, event listeners, and startup.
 * Runs last; wires everything together.
 */
(function (builder) {
	"use strict";
	builder.initPreviewSyncRegistry = function() {
		builder.sync.registry.length = 0;
		if (builder.sync.handlersByKey) {
			builder.sync.handlersByKey.clear();
		}
		builder.sync.handlersByKeyFallback = {};

		builder.createPreviewSyncHandler({
			id: "layoutActionButtonPreview",
			matches: builder.isLayoutActionButtonPart,
			controlKeys: ["ecbb_background", "ecbb_padding", "ecbb_typography", "ecbb_text_align"],
			sync: builder.syncLayoutActionButtonPreviewStyle,
		});

		builder.createPreviewSyncHandler({
			id: "categoryChipPreview",
			matches: function (item) {
				return builder.readRepeaterPartSlug(item) === "categories";
			},
			controlKeys: ["ecbb_background", "ecbb_padding", "ecbb_typography"],
			sync: builder.syncCategoryChipPreviewStyle,
		});

		builder.createPreviewSyncHandler({
			id: "style2MetaIconPreview",
			matches: function (item) {
				return builder.config.style2MetaIconParts.indexOf(builder.readRepeaterPartSlug(item)) !== -1;
			},
			controlKeys: [
				"ecbb_meta_icon_color",
				"ecbb_meta_icon_background",
				"ecbb_margin",
				"ecbb_typography",
			],
			sync: builder.syncStyle2MetaIconPreviewStyle,
		});

		builder.createPreviewSyncHandler({
			id: "style1GridMetaListRowPreview",
			matches: function (item) {
				var part = builder.readRepeaterPartSlug(item);
				return (
					part !== "" &&
					builder.config.style2MetaIconParts.indexOf(part) !== -1
				);
			},
			controlKeys: [
				"ecbb_background",
				"ecbb_margin",
				"ecbb_padding",
				"ecbb_typography",
			],
			sync: builder.syncStyle1GridMetaListRowPreviewStyle,
			run: function (item) {
				if (
					builder.getRepeaterControlInner(item, "ecbb_typography") &&
					builder.readRepeaterPartSlug(item)
				) {
					builder.scheduleStyle1GridMetaListRowPreviewStyle(item);
					return;
				}
				builder.syncStyle1GridMetaListRowPreviewStyle(item);
			},
		});

		builder.sync.titleInnerBackground = builder.createPreviewSyncHandler({
			id: "titleInnerBackgroundPreview",
			matches: function (item) {
				return builder.readRepeaterPartControlValue(item) === "title";
			},
			controlKeys: ["ecbb_background"],
			sync: builder.syncTitleInnerBackgroundPreview,
		});

		builder.sync.typography = builder.createPreviewSyncHandler({
			id: "typographyPreview",
			controlKeys: ["ecbb_typography"],
			delay: 0,
			sync: builder.syncTypographyColorToPreviewTargets,
			run: builder.runTypographyPreviewSyncChain,
		});

		builder.sync.hover = builder.createPreviewSyncHandler({
			id: "hoverPreview",
			controlKeys: builder.config.hoverPreviewControlKeys,
			sync: builder.syncHoverPreviewStyleRules,
			run: function (item) {
				builder.runWithSettleRetry(function () {
					builder.syncHoverPreviewStyleRules(item);
				}, 120);
			},
		});

		builder.sync.styledButton = builder.createPreviewSyncHandler({
			id: "styledButtonPreview",
			matches: function (item) {
				return !!builder.getRepeaterControlInner(item, "btn_style");
			},
			controlKeys: builder.config.styledButtonControlKeys,
			sync: builder.syncStyledButtonPreviewPaint,
			run: function (item) {
				builder.runWithSettleRetry(function () {
					builder.syncStyledButtonPreviewPaint(item);
				}, 120);
			},
		});
	}

	builder.onRepeaterControlInput = function(e) {
		var item = e.target.closest(".ecbb-parts-repeater-item");
		if (!item) {
			return;
		}
		var keys = builder.getRepeaterControlKeysFromEvent(e);
		var seen = typeof Set !== "undefined" ? new Set() : null;
		var seenFallback = {};
		var i;
		var k;
		var j;
		var handlers;
		for (j = 0; j < keys.length; j++) {
			k = keys[j];
			if (!k) {
				continue;
			}
			handlers = builder.sync.handlersByKey
				? builder.sync.handlersByKey.get(k)
				: (builder.sync.handlersByKeyFallback
					? builder.sync.handlersByKeyFallback[k]
					: null);
			if (!handlers) {
				continue;
			}
			for (i = 0; i < handlers.length; i++) {
				if (seen) {
					if (seen.has(handlers[i])) {
						continue;
					}
					seen.add(handlers[i]);
				} else if (seenFallback[handlers[i].id]) {
					continue;
				} else {
					seenFallback[handlers[i].id] = true;
				}
				handlers[i].schedule(item);
			}
		}
	}

	builder.onRepeaterControlChange = function(e) {
			var item = e.target.closest(".ecbb-parts-repeater-item");
			if (!item) {
				return;
			}
		var key = builder.getRepeaterControlKeyFromEvent(e);
		if (key === "part") {
			builder.syncRepeaterHoverPanelState(item);
			builder.runAllPreviewSyncHandlers(false);
				return;
			}
		if (key === "link") {
			builder.syncRepeaterHoverPanelState(item);
			builder.scheduleTitleInnerBackgroundPreviewSync(item);
				return;
			}
		if (key === "ecbb_use_hover") {
			builder.syncRepeaterHoverToggleAttribute(item);
			builder.scheduleStyledButtonPreviewSync(item);
			builder.scheduleHoverPreviewStyleSync(item);
				return;
			}
		builder.onRepeaterControlInput(e);
	}

	builder.getRepeaterControlKeyFromEvent = function(e) {
		var inner = e.target.closest(".repeater-item-inner[data-control-key]");
		if (!inner) {
			return "";
		}
		return inner.getAttribute("data-control-key") || "";
	}

	builder.getRepeaterControlKeysFromEvent = function(e) {
		var keys = [];
		var key = builder.getRepeaterControlKeyFromEvent(e);
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

	builder.syncOpenRepeaterTypographyPreview = function() {
		document
			.querySelectorAll(
				".ecbb-parts-repeater-item.open, .ecbb-parts-repeater-item.always-open"
			)
			.forEach(function (item) {
				if (builder.getRepeaterControlInner(item, "ecbb_typography")) {
					builder.runTypographyPreviewSyncChain(item);
				}
			});
	}

	builder.watchTypographyColorPickerDrag = function() {
		if (!document.querySelector(".pcr-app.visible")) {
			builder.preview.typographyPickerRaf = 0;
			builder.preview.lastTypographyPickerColor = "";
			return;
		}
		var activeColor = builder.readActivePickrColor();
		var now = Date.now();
		if (
			activeColor !== builder.preview.lastTypographyPickerColor ||
			!builder.preview.lastTypographySyncAt ||
			now - builder.preview.lastTypographySyncAt >= 100
		) {
			builder.preview.lastTypographyPickerColor = activeColor;
			builder.preview.lastTypographySyncAt = now;
			builder.syncOpenRepeaterTypographyPreview();
			builder.scheduleWidgetShellCssVarSync();
		}
		builder.preview.typographyPickerRaf = requestAnimationFrame(
			builder.watchTypographyColorPickerDrag
		);
	}

	builder.startTypographyColorPickerDragWatch = function() {
		if (!builder.preview.typographyPickerRaf) {
			builder.preview.typographyPickerRaf = requestAnimationFrame(
				builder.watchTypographyColorPickerDrag
			);
		}
	}

	builder.stopTypographyColorPickerDragWatch = function() {
		if (builder.preview.typographyPickerRaf) {
			cancelAnimationFrame(builder.preview.typographyPickerRaf);
			builder.preview.typographyPickerRaf = 0;
		}
		builder.preview.lastTypographySyncAt = 0;
		builder.preview.lastTypographyPickerColor = "";
		builder.syncOpenRepeaterTypographyPreview();
		builder.scheduleWidgetShellCssVarSync();
	}

	builder.schedulePanelRepeaterScan = function() {
		if (builder.tabs.pointerUpScanTimer) {
			clearTimeout(builder.tabs.pointerUpScanTimer);
		}
		builder.tabs.pointerUpScanTimer = setTimeout(function () {
			builder.tabs.pointerUpScanTimer = 0;
			if (!builder.isRepeaterSortActive()) {
				builder.scanRepeaterRowsAndSyncPreview();
			}
		}, 120);
	}

	builder.initPreviewSyncRegistry();

	document.addEventListener("input", builder.onRepeaterControlInput, true);
	document.addEventListener("change", builder.onRepeaterControlChange, true);
	document.addEventListener("input", builder.onPanelShellControlInput, true);
	document.addEventListener("change", builder.onPanelShellControlInput, true);

	document.addEventListener(
		"pointerdown",
		function (e) {
			if (
				e.target.closest(
					'.repeater-item-inner[data-control-key="ecbb_typography"] .pickr, .repeater-item-inner[data-control-key="ecbb_typography"] .pcr-button, .pcr-app'
				)
			) {
				builder.startTypographyColorPickerDragWatch();
			}
		},
		true
	);
	document.addEventListener("pointerup", builder.stopTypographyColorPickerDragWatch, true);
	document.addEventListener(
		"pointerup",
		function (e) {
			if (
				!(
					e.target &&
					e.target.closest &&
					e.target.closest("#bricks-panel-element")
				)
			) {
				return;
			}
			if (
				e.target &&
				e.target.closest &&
				e.target.closest(
					'#bricks-panel-element [data-control-key="parts_style1"] .repeater-item > .drag, #bricks-panel-element [data-control-key="parts_style2"] .repeater-item > .drag, #bricks-panel-element [data-control-key="parts_grid"] .repeater-item > .drag'
				)
			) {
				builder.scheduleRepeaterSortEndResync();
				return;
			}
			builder.schedulePanelRepeaterScan();
		},
		true
	);

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
				builder.syncRepeaterHoverToggleAttribute(item);
				builder.scheduleHoverPreviewStyleSync(item);
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
			builder.syncRepeaterTabButtonStates(item);
		},
		true
	);

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", builder.scanRepeaterRowsAndSyncPreview);
	} else {
		builder.scanRepeaterRowsAndSyncPreview();
	}

	var builderPanelMutationObserver = new MutationObserver(function (mutations) {
		if (builder.mutationAffectsRepeaterRow(mutations)) {
			builder.scheduleRepeaterRowScan();
		}
	});
	builderPanelMutationObserver.observe(builder.getBricksBuilderPanelRoot(), {
		childList: true,
		subtree: true,
		attributes: true,
		attributeFilter: ["class"],
	});
})(window.ECBB.builder);

