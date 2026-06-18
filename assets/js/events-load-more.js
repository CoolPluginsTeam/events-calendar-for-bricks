(() => {
  const cfg = (window.ECBBEventsLoadMore || {});

  function closest(el, sel) {
    while (el && el.nodeType === 1) {
      if (el.matches(sel)) return el;
      el = el.parentElement;
    }
    return null;
  }

  async function postForm(url, data) {
    const body = new URLSearchParams();
    Object.entries(data).forEach(([k, v]) => body.append(k, String(v)));
    const res = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body,
      credentials: "same-origin",
    });
    return await res.json();
  }

  function setBusy(btn, busy, loadingText, defaultText) {
    btn.disabled = !!busy;
    btn.classList.toggle("is-loading", !!busy);
    if (busy && loadingText) btn.textContent = loadingText;
    if (!busy && defaultText) btn.textContent = defaultText;
  }

  function showNoMore(container, msg, hideMs) {
    const btn = container.querySelector(".ecbb-load-more__btn");
    if (btn) btn.style.display = "none";

    const note = container.querySelector(".ecbb-load-more__done");
    if (note) {
      note.textContent = msg || "No more events";
      note.style.display = "inline-block";
      window.setTimeout(() => {
        note.classList.add("is-hiding");
        window.setTimeout(() => {
          note.remove();
          container.remove();
        }, 400);
      }, Math.max(300, hideMs || 1500));
    }
  }

  function sanitizeFragment(doc) {
    const blocked = "script,style,iframe,object,embed,link,meta,base";
    doc.querySelectorAll(blocked).forEach((el) => el.remove());

    doc.querySelectorAll("*").forEach((el) => {
      Array.from(el.attributes).forEach((attr) => {
        const name = attr.name.toLowerCase();
        const value = attr.value.trim();

        if (name.startsWith("on") || name === "srcdoc") {
          el.removeAttribute(attr.name);
          return;
        }

        if ((name === "href" || name === "src") && /^(javascript|data):/i.test(value)) {
          el.removeAttribute(attr.name);
        }
      });
    });

    return doc.body;
  }

  document.addEventListener("click", async (e) => {
    const btn = e.target && e.target.closest ? e.target.closest(".ecbb-load-more__btn") : null;
    if (!btn) return;

    const root = closest(btn, ".ecbb-ev");
    const box = closest(btn, ".ecbb-load-more");
    if (!root || !box) return;

    const list = root.querySelector(".ecbb-ev__list");
    if (!list) return;

    const settings = btn.getAttribute("data-settings") || "{}";
    const limit = parseInt(btn.getAttribute("data-limit") || "6", 10);
    const hideMs = parseInt(btn.getAttribute("data-hide-ms") || "1500", 10);
    const noMoreText = btn.getAttribute("data-no-more") || "No more events";
    const loadingText = btn.getAttribute("data-loading") || "Loading...";
    const defaultText = btn.getAttribute("data-text") || btn.textContent || "Load more";

    let offset = parseInt(btn.getAttribute("data-offset") || "0", 10);
    if (Number.isNaN(offset)) offset = 0;

    setBusy(btn, true, loadingText, defaultText);

    try {
      const json = await postForm(cfg.ajaxUrl, {
        action: "ecbb_events_load_more",
        nonce: cfg.nonce || "",
        ecbb_settings: settings,
        ecbb_offset: offset,
        ecbb_limit: limit,
      });

      if (!json || !json.success) {
        setBusy(btn, false, loadingText, defaultText);
        return;
      }

      const html = (json.data && json.data.html) ? json.data.html : "";
      if (html) {
        const doc = new DOMParser().parseFromString(html, "text/html");
        const safeBody = sanitizeFragment(doc);
        while (safeBody.firstChild) {
          list.appendChild(safeBody.firstChild);
        }
      }

      const nextOffset = (json.data && typeof json.data.nextOffset === "number") ? json.data.nextOffset : (offset + limit);
      btn.setAttribute("data-offset", String(nextOffset));

      if (!json.data || json.data.hasMore === false) {
        showNoMore(box, noMoreText, hideMs);
        return;
      }

      setBusy(btn, false, loadingText, defaultText);
    } catch (_err) {
      setBusy(btn, false, loadingText, defaultText);
    }
  });
})();
