/* =====================================================================
   main.js — KHUSUS LAYOUT INI. Saat layout website baru berbeda, yang
   ditulis ulang cukup fungsi template (productCard, promoCard, …) dan
   pemanggilan widget di init(). Data & widget sudah disediakan oleh
   `Site` (js/site.js) dan `UI` (js/ui.js).

   Hook HTML yang diisi file ini:
     [data-products="all|featured|other"]   grid menu
     [data-promo-section] + [data-promos]    section promo (disembunyikan bila kosong)
     [data-slideshow]                        slideshow foto dari slider.json
     img[data-setting="hero_image"]          foto dari settings.json
   ===================================================================== */

const { escapeHtml, paragraphs, formatPrice, orderLinks, waLink } = Site;

/* ---------------- Templates (ubah bebas per layout) ---------------- */

// Channels with an official logo image; the rest use the SVG icon from site.js.
const ORDER_ICON_IMAGES = {
  shopee: "images/icons/shopee.png",
  smexpo: "images/icons/smexpo.png",
};

function orderButtons(product) {
  return orderLinks(product, `Halo ${SITE.name}, saya mau pesan ${product.name}`)
    .map((link) => {
      const image = ORDER_ICON_IMAGES[link.key];
      return `
      <a class="order-btn order-btn--${escapeHtml(link.key)}" href="${escapeHtml(link.url)}"
         target="_blank" rel="noopener" title="Pesan via ${escapeHtml(link.label)}"
         aria-label="Pesan ${escapeHtml(product.name)} via ${escapeHtml(link.label)}">
        ${image ? `<img src="${escapeHtml(image)}" alt="" width="46" height="46" loading="lazy">` : link.icon}
      </a>`;
    })
    .join("");
}

function productCard(product) {
  const price = formatPrice(product.price);
  return `
    <article class="product-card" data-reveal>
      <div class="product-card__img">
        <img src="${escapeHtml(product.image || Site.NO_PHOTO)}" alt="${escapeHtml(product.name)}" loading="lazy"
             onerror="this.onerror=null;this.src='${Site.NO_PHOTO}'">
      </div>
      <div class="product-card__body">
        <h3>${escapeHtml(product.name)}</h3>
        <p>${escapeHtml(product.desc)}</p>
        <span class="price${price ? "" : " price--ask"}">${price ? escapeHtml(price) : "Tanya harga via chat"}</span>
        <div class="order-btns">${orderButtons(product)}</div>
      </div>
    </article>`;
}

function promoCard(promo) {
  return `
    <article class="promo-card">
      <div class="promo-card__copy">
        <span class="badge">Promo</span>
        <h2>${escapeHtml(promo.title)}</h2>
        <div class="promo-card__desc">${paragraphs(promo.desc)}</div>
        <a class="btn btn--primary" href="${escapeHtml(waLink(`Halo ${SITE.name}, saya mau tanya promo "${promo.title}"`))}"
           target="_blank" rel="noopener">Pesan Sekarang</a>
      </div>
      <div class="promo-card__img">
        <img src="${escapeHtml(promo.image)}" alt="${escapeHtml(promo.title)}" loading="lazy">
      </div>
    </article>`;
}

const MENU_ERROR_HTML = `
  <p class="grid-message">Produk belum bisa dimuat. Silakan hubungi kami langsung via
    <a data-wa="Halo ${escapeHtml(SITE.name)}, saya mau tanya produk">WhatsApp</a>.</p>`;
const MENU_EMPTY_HTML = `<p class="grid-message">Produk segera hadir.</p>`;

/* ---------------- Renderers (pola tetap, jarang perlu diubah) ---------------- */

/** One bad item must never blank the whole grid: render each card on its own. */
function safeCards(list, template) {
  return list.map((item) => {
    try {
      return template(item);
    } catch (err) {
      console.error(`[${SITE.name}] Gagal menampilkan item:`, item, err);
      return "";
    }
  }).join("");
}

function renderProducts(products) {
  const grids = Array.from(document.querySelectorAll("[data-products]"));
  // Safety net: a layout with only a "featured" grid would silently hide every
  // non-featured menu. Without an "all"/"other" grid, "featured" shows everything.
  const hasCatchAll = grids.some((g) => ["all", "other", ""].includes(g.dataset.products || ""));

  grids.forEach((grid) => {
    if (products === null) {
      grid.innerHTML = MENU_ERROR_HTML;
      Site.bindStatic(grid);
      return;
    }
    let mode = grid.dataset.products || "all";
    if (mode === "featured" && !hasCatchAll) {
      console.warn(`[${SITE.name}] Tidak ada grid data-products="other"/"all"; grid unggulan menampilkan semua menu.`);
      mode = "all";
    }
    const list = products.filter((p) =>
      mode === "featured" ? p.featured === true : mode === "other" ? p.featured !== true : true);

    // A grid wrapped in [data-products-block] hides the whole block (heading included)
    // when it has nothing to show; an unwrapped grid shows a message instead.
    const block = grid.closest("[data-products-block]");
    if (block) block.hidden = list.length === 0;

    grid.innerHTML = list.length ? safeCards(list, productCard) : block ? "" : MENU_EMPTY_HTML;
  });
}

function renderPromos(promos) {
  const section = document.querySelector("[data-promo-section]");
  const list = document.querySelector("[data-promos]");
  if (!section || !list) return;
  section.hidden = promos.length === 0;
  list.innerHTML = safeCards(promos, promoCard);
}

/** Replace the default <img>s in [data-slideshow] with the admin's photos. */
function renderSlider(slides) {
  const root = document.querySelector("[data-slideshow]");
  if (!root) return;
  if (slides.length) {
    const alt = root.dataset.alt || `Foto ${SITE.name}`;
    root.querySelectorAll(":scope > img").forEach((img) => img.remove());
    const anchor = root.querySelector("[data-slideshow-dots]");
    slides.forEach((s) => {
      const img = document.createElement("img");
      img.src = s.image;
      img.alt = alt;
      img.loading = "lazy";
      root.insertBefore(img, anchor);
    });
  }
  UI.slideshow(root);
}

/**
 * Position dots under a [data-carousel]. Indicator only: UI.carousel keeps its own
 * index, so jumping from a dot would desync its autoplay. Clones (aria-hidden) are
 * skipped, and the index wraps so the clone strip still lights the right dot.
 */
function carouselDots(root) {
  const track = root.querySelector("[data-carousel-track]");
  const dots = root.querySelector("[data-carousel-dots]");
  if (!track || !dots) return;
  const count = Array.from(track.children).filter((el) => !el.hasAttribute("aria-hidden")).length;
  if (count < 2) return;
  dots.innerHTML = "<span></span>".repeat(count);
  const spans = Array.from(dots.children);
  const update = () => {
    const first = track.children[0];
    const step = first.getBoundingClientRect().width + (parseFloat(getComputedStyle(track).columnGap) || 0);
    const active = Math.round(track.scrollLeft / step) % count;
    spans.forEach((s, i) => s.classList.toggle("is-active", i === active));
  };
  track.addEventListener("scroll", update, { passive: true });
  update();
}

/* ---------------- Init ---------------- */

async function init() {
  Site.bindStatic();
  UI.nav();
  UI.headerScrolled();
  document.querySelectorAll("[data-carousel]").forEach((root) => {
    UI.carousel(root);
    carouselDots(root);
  });

  // Promo & slider are off for this brand; only fetch them when the layout has their hooks
  // (avoids 404s for data files that do not exist).
  const hasPromos = !!document.querySelector("[data-promo-section]");
  const hasSlider = !!document.querySelector("[data-slideshow]");
  const [settings, products, promos, slides] = await Promise.all([
    Site.getSettings(), Site.getProducts(),
    hasPromos ? Site.getPromos() : [], hasSlider ? Site.getSlider() : [],
  ]);
  Site.applySettings(settings);
  renderProducts(products);
  renderPromos(promos);
  renderSlider(slides);

  // These depend on the rendered content, so they run last.
  UI.reveal();
  UI.counters();
  UI.scrollSpy();
}

init();
