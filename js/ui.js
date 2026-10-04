/* =====================================================================
   ui.js — WIDGET UMUM, digerakkan atribut data-*. Sama untuk semua website;
   tampilan widget diatur di css/style.css, bukan di sini.
   Menyediakan objek global `UI`.
   ===================================================================== */

const UI = (() => {
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const canHover = window.matchMedia("(hover: hover)").matches;

  /** <button data-nav-toggle aria-controls="nav"> toggles .is-open on <nav data-nav id="nav">. */
  function nav() {
    const toggle = document.querySelector("[data-nav-toggle]");
    const menu = document.querySelector("[data-nav]");
    if (!toggle || !menu) return;

    function setOpen(open) {
      menu.classList.toggle("is-open", open);
      toggle.classList.toggle("is-open", open);
      toggle.setAttribute("aria-expanded", String(open));
    }
    toggle.addEventListener("click", () => setOpen(!menu.classList.contains("is-open")));
    menu.querySelectorAll("a").forEach((a) => a.addEventListener("click", () => setOpen(false)));
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") setOpen(false); });
  }

  /** <header data-header> gets .is-scrolled once the page is scrolled. */
  function headerScrolled() {
    const header = document.querySelector("[data-header]");
    if (!header) return;
    const update = () => header.classList.toggle("is-scrolled", window.scrollY > 12);
    update();
    window.addEventListener("scroll", update, { passive: true });
  }

  /**
   * Highlights the nav link (.is-active) of the section in view. A link can cover
   * several sections with data-also="id,id". Links whose sections are all hidden
   * (e.g. Promo with no active promo) are hidden too. Returns a refresh function.
   */
  function scrollSpy() {
    const links = Array.from(document.querySelectorAll('[data-nav] a[href^="#"]'));
    const header = document.querySelector("[data-header]");
    const targets = links.map((a) => {
      const ids = [a.getAttribute("href").slice(1)].concat((a.dataset.also || "").split(",").filter(Boolean));
      return [a, ids.map((id) => document.getElementById(id)).filter(Boolean)];
    });

    function update() {
      targets.forEach(([a, els]) => { if (els.length) a.hidden = els.every((el) => el.hidden); });
      const line = (header ? header.offsetHeight : 0) + window.innerHeight / 3;
      let current = null;
      targets.forEach(([a, els]) => {
        if (els.some((el) => !el.hidden && el.getBoundingClientRect().top <= line)) current = a;
      });
      // The last section is often too short to reach the line; the page bottom counts as it.
      if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
        current = links[links.length - 1] || current;
      }
      links.forEach((a) => a.classList.toggle("is-active", a === current));
    }

    // Time-based throttle: requestAnimationFrame pauses in background tabs.
    let last = 0;
    let trailing = null;
    window.addEventListener("scroll", () => {
      const now = Date.now();
      if (now - last >= 100) {
        last = now;
        update();
      } else if (trailing === null) {
        trailing = setTimeout(() => { trailing = null; last = Date.now(); update(); }, 100);
      }
    }, { passive: true });
    window.addEventListener("resize", update);
    update();
    return update;
  }

  /**
   * Crossfade slideshow. Markup: <div data-slideshow data-interval="3000"> <img>… <div data-slideshow-dots></div></div>
   * The active <img> gets .is-active; the CSS does the fade.
   */
  function slideshow(root) {
    if (!root) return;
    const slides = Array.from(root.querySelectorAll(":scope > img"));
    const dotsWrap = root.querySelector("[data-slideshow-dots]");
    if (dotsWrap) dotsWrap.innerHTML = "";
    slides.forEach((img, i) => img.classList.toggle("is-active", i === 0));
    if (slides.length < 2) return;

    const interval = Number(root.dataset.interval) || 3000;
    let index = 0;
    let timer = null;

    const dots = slides.map((img, i) => {
      if (!dotsWrap) return null;
      const dot = document.createElement("button");
      dot.type = "button";
      dot.setAttribute("aria-label", `Tampilkan foto ${i + 1}`);
      dot.addEventListener("click", () => { show(i); restart(); });
      dotsWrap.appendChild(dot);
      return dot;
    });

    function show(i) {
      index = (i + slides.length) % slides.length;
      slides.forEach((img, n) => img.classList.toggle("is-active", n === index));
      dots.forEach((dot, n) => {
        if (!dot) return;
        dot.classList.toggle("is-active", n === index);
        dot.setAttribute("aria-current", String(n === index));
      });
    }
    function start() {
      if (reduceMotion) return;
      stop();
      timer = setInterval(() => show(index + 1), interval);
    }
    function stop() {
      if (timer) clearInterval(timer);
      timer = null;
    }
    function restart() { start(); }

    // Pause on hover only with a real mouse: touch devices fire a fake mouseenter on tap.
    if (canHover) {
      root.addEventListener("mouseenter", stop);
      root.addEventListener("mouseleave", start);
    }
    show(0);
    start();
  }

  /**
   * Endless horizontal carousel. Markup:
   *   <div data-carousel data-interval="2500">
   *     <button data-carousel-prev></button>
   *     <ul data-carousel-track> <li>…</li> </ul>   (track: display:flex; overflow-x:auto; scroll-snap optional)
   *     <button data-carousel-next></button>
   *   </div>
   * Items are cloned once so moving past the last item keeps going forward
   * instead of rewinding across the whole row.
   */
  function carousel(root) {
    if (!root) return;
    const track = root.querySelector("[data-carousel-track]");
    if (!track) return;
    const items = Array.from(track.children);
    const count = items.length;
    if (count < 2) return;

    items.forEach((item) => {
      const clone = item.cloneNode(true);
      clone.setAttribute("aria-hidden", "true");
      clone.querySelectorAll("a, button").forEach((el) => el.setAttribute("tabindex", "-1"));
      track.appendChild(clone);
    });

    const SLIDE_MS = 420;
    let index = 0;
    let busy = false;
    let timer = null;

    const step = () => items[0].getBoundingClientRect().width + (parseFloat(getComputedStyle(track).columnGap) || 0);
    const go = (i, smooth) => track.scrollTo({ left: i * step(), behavior: smooth ? "smooth" : "auto" });

    function next() {
      if (busy) return;
      if (index === count - 1) {
        busy = true;
        go(count, true);
        index = 0;
        setTimeout(() => { go(0, false); busy = false; }, SLIDE_MS);
      } else {
        go(++index, true);
      }
    }
    function prev() {
      if (busy) return;
      if (index === 0) {
        busy = true;
        go(count, false);
        index = count - 1;
        go(index, true);
        setTimeout(() => { busy = false; }, SLIDE_MS);
      } else {
        go(--index, true);
      }
    }
    function start() {
      if (reduceMotion) return;
      clearInterval(timer);
      timer = setInterval(next, Number(root.dataset.interval) || 2500);
    }

    root.querySelector("[data-carousel-prev]")?.addEventListener("click", () => { prev(); start(); });
    root.querySelector("[data-carousel-next]")?.addEventListener("click", () => { next(); start(); });
    if (canHover) {
      root.addEventListener("mouseenter", () => clearInterval(timer));
      root.addEventListener("mouseleave", start);
    }
    // Item width changes with the viewport; re-align or a card ends up cut in half.
    window.addEventListener("resize", () => go(index, false));
    start();
  }

  /**
   * Reveal-on-scroll: [data-reveal] gets .is-visible when it enters the viewport.
   * [data-reveal="stagger"] staggers its children via the --d custom property.
   */
  function reveal(root = document) {
    const els = Array.from(root.querySelectorAll("[data-reveal]:not(.is-visible)"));
    els.forEach((el) => {
      if (el.dataset.reveal === "stagger") {
        Array.from(el.children).forEach((child, i) => child.style.setProperty("--d", i * 90 + "ms"));
      }
    });
    if (reduceMotion || !("IntersectionObserver" in window)) {
      els.forEach((el) => el.classList.add("is-visible"));
      return;
    }
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
    els.forEach((el) => observer.observe(el));
  }

  /** <span data-count="10000">0</span> counts up when visible (data-format="year" skips the thousands dot). */
  function counters() {
    const els = Array.from(document.querySelectorAll("[data-count]"));
    const format = (el, n) => (el.dataset.format === "year" ? String(n) : n.toLocaleString("id-ID"));
    function run(el) {
      const target = parseInt(el.dataset.count, 10) || 0;
      if (reduceMotion) { el.textContent = format(el, target); return; }
      let start = null;
      function frame(ts) {
        if (start === null) start = ts;
        const p = Math.min((ts - start) / 1400, 1);
        el.textContent = format(el, Math.floor((1 - Math.pow(1 - p, 3)) * target));
        if (p < 1) requestAnimationFrame(frame);
      }
      requestAnimationFrame(frame);
    }
    if (!("IntersectionObserver" in window)) { els.forEach(run); return; }
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) { run(entry.target); observer.unobserve(entry.target); }
      });
    }, { threshold: 0.6 });
    els.forEach((el) => observer.observe(el));
  }

  return { reduceMotion, nav, headerScrolled, scrollSpy, slideshow, carousel, reveal, counters };
})();
