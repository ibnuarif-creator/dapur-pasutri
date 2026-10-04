/* =====================================================================
   site.js — LAPISAN DATA. Sama untuk semua website; layout baru tidak
   perlu mengubah file ini selain blok SITE di bawah.
   Menyediakan objek global `Site` yang dipakai js/main.js.
   ===================================================================== */

// Samakan dengan SITE_NAME / WHATSAPP_NUMBER / PRODUCT_LINKS di includes/config.php.
const SITE = {
  name: "Dapur Pasutri",
  whatsapp: "6281315127837",
  // Label + ikon untuk tiap link pemesanan produk (key = key di products.json).
  orderLinks: {
    shopee:   { label: "Shopee",   icon: "shopee" },
    gofood:   { label: "GoFood",   icon: "gofood" },
    smexpo:   { label: "SMEXPO",   icon: "link" },
    whatsapp: { label: "WhatsApp", icon: "whatsapp" },
  },
};

const Site = (() => {
  const ICONS = {
    whatsapp: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.33 4.95L2 22l5.28-1.38a9.9 9.9 0 0 0 4.76 1.21h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2Zm5.8 14.07c-.24.68-1.4 1.3-1.93 1.38-.5.08-1.12.11-1.8-.11-.42-.13-.95-.3-1.64-.6-2.88-1.24-4.76-4.12-4.9-4.31-.14-.19-1.18-1.56-1.18-2.98s.74-2.11 1-2.4c.26-.29.57-.36.76-.36h.55c.18 0 .42-.07.65.5.24.58.82 2 .89 2.14.07.14.12.31.02.5-.1.19-.15.31-.3.48l-.44.51c-.14.14-.29.29-.13.57.16.29.71 1.18 1.53 1.91 1.05.94 1.94 1.23 2.22 1.37.28.14.45.12.61-.07.16-.19.7-.81.89-1.09.19-.28.38-.23.63-.14.26.1 1.65.78 1.94.92.28.14.47.21.54.33.07.12.07.68-.17 1.35Z"/></svg>',
    shopee: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 8h-1.5a3.5 3.5 0 0 0-7 0H7a2 2 0 0 0-2 2l-.8 9.2A2 2 0 0 0 6.2 21.4h11.6a2 2 0 0 0 2-2.2L19 10a2 2 0 0 0-2-2Zm-5-2a1.5 1.5 0 0 1 1.5 1.5h-3A1.5 1.5 0 0 1 12 6Zm-2.5 5.5a2.5 2.5 0 0 0 5 0V10h1.5v1.5a4 4 0 0 1-8 0V10h1.5v1.5Z"/></svg>',
    gofood: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 2.8a.8.8 0 0 1 1.6 0V7h1.4V2.8a.8.8 0 0 1 1.6 0V7h1.4V2.8a.8.8 0 0 1 1.6 0V8a3.6 3.6 0 0 1-2.6 3.46V21a1 1 0 0 1-2 0v-9.54A3.6 3.6 0 0 1 5 8V2.8ZM17.5 2C19.4 2.6 20 5 20 8v4.5a1 1 0 0 1-1 1h-.6V21a1 1 0 0 1-2 0V3a1 1 0 0 1 1.1-1Z"/></svg>',
    instagram: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 3.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Zm0 2a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5ZM17.5 6a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z"/></svg>',
    link: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.6 13.4a1 1 0 0 1 0-1.4l3.4-3.4a1 1 0 1 1 1.4 1.4L12 13.4a1 1 0 0 1-1.4 0ZM8.5 18.5a3.5 3.5 0 0 1-2.5-6l2.1-2.1a1 1 0 0 1 1.4 1.4l-2.1 2.1a1.5 1.5 0 0 0 2.1 2.1l2.1-2.1a1 1 0 0 1 1.4 1.4L11 17.5a3.5 3.5 0 0 1-2.5 1Zm6.3-4.4a1 1 0 0 1-.7-1.7l2.1-2.1a1.5 1.5 0 0 0-2.1-2.1L12 10.3a1 1 0 0 1-1.4-1.4L12.7 6.8a3.5 3.5 0 0 1 5 5l-2.1 2.1a1 1 0 0 1-.8.3Z"/></svg>',
  };

  /* ---------- Formatting ---------- */

  // EVERY value from data/*.json goes through this before touching innerHTML.
  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
  }

  /** Escaped text with blank lines turned into <p> paragraphs and single newlines into <br>. */
  function paragraphs(text) {
    return String(text ?? "")
      .split(/\n\s*\n/)
      .map((p) => p.trim())
      .filter(Boolean)
      .map((p) => `<p>${escapeHtml(p).replace(/\n/g, "<br>")}</p>`)
      .join("");
  }

  /** "Rp22.500", or null when the product has no fixed price (or an unreadable one). */
  function formatPrice(price) {
    if (price === null || price === undefined || price === "") return null;
    const n = typeof price === "number" ? price : Number(String(price).replace(/\D/g, ""));
    if (!Number.isFinite(n) || n <= 0) return null;
    return "Rp" + n.toLocaleString("id-ID");
  }

  /** Fallback for a product photo that is missing on the server (see onerror in main.js). */
  const NO_PHOTO = "images/no-photo.svg";

  function icon(name) {
    return ICONS[name] || ICONS.link;
  }

  /* ---------- WhatsApp & order links ---------- */

  function waLink(message) {
    return `https://wa.me/${SITE.whatsapp}` + (message ? `?text=${encodeURIComponent(message)}` : "");
  }

  // A wa.me link without its own message gets one appended (e.g. the menu name).
  function withWaText(url, message) {
    if (/wa\.me\/|api\.whatsapp\.com/i.test(url) && !/[?&]text=/i.test(url)) {
      return url + (url.includes("?") ? "&" : "?") + "text=" + encodeURIComponent(message);
    }
    return url;
  }

  /**
   * Filled-in order links of a product, in SITE.orderLinks order:
   * [{ key, url, label, icon }]. Empty links are skipped, so the icon disappears.
   */
  function orderLinks(product, waMessage) {
    return Object.keys(SITE.orderLinks)
      // WhatsApp always shows (falls back to the main number) so a menu is never
      // left without any way to order; other channels show only when filled in.
      .filter((key) => product[key] || key === "whatsapp")
      .map((key) => {
        const meta = SITE.orderLinks[key];
        const url = key === "whatsapp"
          ? (product[key] ? withWaText(product[key], waMessage) : waLink(waMessage))
          : product[key];
        return { key, url, label: meta.label, icon: icon(meta.icon) };
      });
  }

  /* ---------- Data loading ---------- */

  // Returns `fallback` (default null) when the file is missing or broken, so callers
  // can tell "failed to load" (null) apart from "loaded but empty" ([]).
  async function loadJSON(path, fallback = null) {
    try {
      // no-store covers the browser; the ?v= timestamp also defeats hosting/proxy
      // caches that ignore it — otherwise a newly added menu "doesn't appear".
      const url = path + (path.includes("?") ? "&" : "?") + "v=" + Date.now();
      const res = await fetch(url, { cache: "no-store" });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (err) {
      console.warn(`[${SITE.name}] Gagal memuat ${path}:`, err);
      return fallback;
    }
  }

  /** Products with a usable name, or null when the file failed to load. */
  async function getProducts() {
    const data = await loadJSON("data/products.json");
    if (!Array.isArray(data)) return null;
    return data.filter((p) => p && typeof p === "object" && String(p.name ?? "").trim() !== "");
  }

  /** Active promos only. */
  async function getPromos() {
    const data = await loadJSON("data/promos.json", []);
    return Array.isArray(data) ? data.filter((p) => p && p.active !== false) : [];
  }

  async function getSettings() {
    const data = await loadJSON("data/settings.json", {});
    return data && typeof data === "object" ? data : {};
  }

  /** Slider photos, or [] when the admin hasn't saved any (keep the HTML defaults). */
  async function getSlider() {
    const data = await loadJSON("data/slider.json", []);
    return Array.isArray(data) ? data.filter((s) => s && s.image) : [];
  }

  /* ---------- Static hooks (work with any layout) ---------- */

  /**
   * - <a data-wa="pesan">       → href = WhatsApp link with that message
   * - <span data-year>          → current year
   * - <img data-setting="key">  → src from settings.json (applySettings)
   */
  function bindStatic(root = document) {
    root.querySelectorAll("[data-wa]").forEach((a) => {
      a.href = waLink(a.dataset.wa);
      if (!a.target) {
        a.target = "_blank";
        a.rel = "noopener";
      }
    });
    root.querySelectorAll("[data-year]").forEach((el) => {
      el.textContent = new Date().getFullYear();
    });
  }

  function applySettings(settings) {
    document.querySelectorAll("img[data-setting]").forEach((img) => {
      const value = settings[img.dataset.setting];
      if (value) img.src = value;
    });
  }

  return {
    escapeHtml, paragraphs, formatPrice, icon, NO_PHOTO,
    waLink, withWaText, orderLinks,
    loadJSON, getProducts, getPromos, getSettings, getSlider,
    bindStatic, applySettings,
  };
})();
