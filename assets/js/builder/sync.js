/**
 * Preview sync registry — maps control changes to preview update functions.
 * Adds functions to window.ECBB.builder (see core.js).
 */
(function (builder) {
	"use strict";

	builder.createPreviewSyncHandler = function(options) {
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

			var id = builder.readRepeaterRowId(item) || item;
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
		builder.sync.registry.push(entry);
		return entry;
	}

	builder.runAllPreviewSyncHandlers = function(includeDelayed) {
		var i;
		for (i = 0; i < builder.sync.registry.length; i++) {
			builder.sync.registry[i].syncAll();
		}
		if (!includeDelayed || !builder.sync.hover || !builder.sync.typography) {
			return;
		}
		setTimeout(function () {
			builder.sync.hover.syncAll();
		}, 150);
		setTimeout(function () {
			builder.sync.hover.syncAll();
		}, 350);
		setTimeout(function () {
			builder.sync.typography.syncAll();
		}, 150);
		setTimeout(function () {
			builder.sync.typography.syncAll();
		}, 350);
	}
})(window.ECBbuilder.builder);

