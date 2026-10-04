/* =====================================================================
   portal.js — perilaku bersama untuk semua halaman portal (sama di semua website).
   Hook: [data-price-input], [data-no-price], [data-counter], [data-image-input],
         form[data-keepalive], dan semua <form method="post"> (anti klik ganda).
   ===================================================================== */
(function () {
  "use strict";

  var body = document.body;
  var UPLOAD_MAX = parseInt(body.getAttribute("data-upload-max"), 10) || 3 * 1024 * 1024;
  var POST_MAX = parseInt(body.getAttribute("data-post-max"), 10) || 0;
  // Photos are resized to this longest side and re-encoded before upload. Phone
  // photos are 3–8MB, above most shared-hosting upload limits (often 2MB).
  var MAX_SIDE = 1600;
  var SHRINK_ABOVE_BYTES = 600 * 1024;
  var JPEG_QUALITY = 0.82;

  function formatBytes(n) {
    return n >= 1024 * 1024 ? (n / 1024 / 1024).toFixed(1).replace(".", ",") + " MB" : Math.round(n / 1024) + " KB";
  }

  function rupiah(n) {
    return "Rp" + n.toLocaleString("id-ID");
  }

  /* ---------- Price: same rules as parse_price() in includes/helpers.php ---------- */
  function parsePrice(raw) {
    var s = String(raw || "").toLowerCase().replace(/\s+/g, "").replace(/^rp\.?/, "");
    if (s === "") return null;
    var k = s.match(/^(\d+(?:[.,]\d+)?)(k|rb|ribu)$/);
    if (k) return Math.round(parseFloat(k[1].replace(",", ".")) * 1000);
    if (!/^\d[\d.,]*$/.test(s)) return null;
    s = s.replace(/[.,]\d{1,2}$/, "");
    var digits = s.replace(/\D/g, "");
    if (digits === "" || digits.length > 10) return null;
    return parseInt(digits, 10);
  }

  document.querySelectorAll("[data-price-input]").forEach(function (input) {
    var preview = document.getElementById(input.getAttribute("data-price-input"));
    function update() {
      if (!preview) return;
      preview.classList.remove("is-error", "is-ok");
      if (input.disabled) { preview.textContent = ""; return; }
      if (input.value.trim() === "") { preview.textContent = "Isi harga, atau centang “Tanpa harga tetap”."; return; }
      var price = parsePrice(input.value);
      if (price === null) {
        preview.textContent = "Harga tidak bisa dibaca. Tulis angka saja, contoh: 25000";
        preview.classList.add("is-error");
      } else if (price < 100) {
        preview.textContent = "Terlalu kecil (" + rupiah(price) + "). Maksudnya " + rupiah(price * 1000) + "?";
        preview.classList.add("is-error");
      } else {
        preview.textContent = "Tampil di website: " + rupiah(price);
        preview.classList.add("is-ok");
      }
    }
    input.addEventListener("input", update);
    update();
    input._updatePricePreview = update;
  });

  document.querySelectorAll("[data-no-price]").forEach(function (box) {
    var input = document.getElementById(box.getAttribute("data-no-price"));
    if (!input) return;
    box.addEventListener("change", function () {
      input.disabled = box.checked;
      input.required = !box.checked;
      if (box.checked) input.value = "";
      else input.focus();
      if (input._updatePricePreview) input._updatePricePreview();
    });
  });

  /* ---------- Character counters ---------- */
  document.querySelectorAll("[data-counter]").forEach(function (field) {
    var counter = document.getElementById(field.getAttribute("data-counter"));
    var max = field.maxLength;
    if (!counter || max <= 0) return;
    function update() {
      var used = field.value.length;
      counter.textContent = used + " / " + max + " karakter";
      counter.classList.toggle("is-near", used > max * 0.85 && used < max);
      counter.classList.toggle("is-full", used >= max);
    }
    field.addEventListener("input", update);
    update();
  });

  /* ---------- Image inputs: shrink big photos in the browser + preview ---------- */
  function loadImage(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () { resolve({ img: img, url: url }); };
      img.onerror = function () { URL.revokeObjectURL(url); reject(new Error("decode")); };
      img.src = url; // <img> applies the EXIF rotation of phone photos
    });
  }

  function canvasToBlob(canvas, type, quality) {
    return new Promise(function (resolve) { canvas.toBlob(resolve, type, quality); });
  }

  /** Resolves to a File that fits the upload limit when possible (original file otherwise). */
  function shrink(file) {
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return Promise.resolve(file);
    return loadImage(file).then(function (loaded) {
      var img = loaded.img;
      var w = img.naturalWidth;
      var h = img.naturalHeight;
      var scale = Math.min(1, MAX_SIDE / Math.max(w, h));
      URL.revokeObjectURL(loaded.url);
      if (scale === 1 && file.size <= SHRINK_ABOVE_BYTES) return file;

      var canvas = document.createElement("canvas");
      canvas.width = Math.round(w * scale);
      canvas.height = Math.round(h * scale);
      canvas.getContext("2d").drawImage(img, 0, 0, canvas.width, canvas.height);

      // PNG/WEBP may be transparent (product cut-outs) — keep alpha with WEBP;
      // Safari can't encode WEBP and hands back PNG, which still keeps alpha.
      var keepAlpha = file.type !== "image/jpeg";
      return canvasToBlob(canvas, keepAlpha ? "image/webp" : "image/jpeg", JPEG_QUALITY).then(function (blob) {
        if (!blob) return file;
        if (blob.size >= file.size && scale === 1) return file;
        var ext = { "image/jpeg": "jpg", "image/png": "png", "image/webp": "webp" }[blob.type] || "jpg";
        var name = file.name.replace(/\.[^.]+$/, "") + "." + ext;
        return new File([blob], name, { type: blob.type, lastModified: Date.now() });
      });
    }).catch(function () { return file; });
  }

  document.querySelectorAll("input[type=file][data-image-input]").forEach(function (input) {
    var form = input.form;
    var info = document.createElement("div");
    info.className = "file-preview";
    info.setAttribute("aria-live", "polite");
    input.insertAdjacentElement("afterend", info);

    input.addEventListener("change", function () {
      var files = Array.prototype.slice.call(input.files || []);
      info.innerHTML = "";
      if (!files.length) return;
      if (form) form.setAttribute("data-processing", "1");
      info.textContent = "Memproses foto…";

      Promise.all(files.map(shrink)).then(function (results) {
        var replaced = results.some(function (f, i) { return f !== files[i]; });
        if (replaced) {
          try {
            var dt = new DataTransfer();
            results.forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
          } catch (e) {
            results = files; // very old browser: upload the originals
          }
        }

        info.innerHTML = "";
        var tooBig = [];
        var total = 0;
        results.forEach(function (f, i) {
          total += f.size;
          if (f.size > UPLOAD_MAX) tooBig.push(f.name);
          var item = document.createElement("figure");
          var img = document.createElement("img");
          img.src = URL.createObjectURL(f);
          img.alt = "";
          var cap = document.createElement("figcaption");
          cap.textContent = f === files[i] ? formatBytes(f.size) : formatBytes(files[i].size) + " → " + formatBytes(f.size);
          item.appendChild(img);
          item.appendChild(cap);
          info.appendChild(item);
        });

        var message = "";
        if (tooBig.length) {
          message = "Foto masih terlalu besar (maks. " + formatBytes(UPLOAD_MAX) + "): " + tooBig.join(", ") + ". Pilih foto lain.";
        } else if (POST_MAX && total > POST_MAX * 0.95) {
          message = "Total foto terlalu besar untuk sekali unggah (maks. " + formatBytes(POST_MAX) + "). Unggah lebih sedikit foto sekaligus.";
        }
        if (message) {
          input.value = "";
          var warn = document.createElement("p");
          warn.className = "hint hint-error";
          warn.textContent = message;
          info.appendChild(warn);
        }
      }).finally(function () {
        if (form) form.removeAttribute("data-processing");
      });
    });
  });

  /* ---------- Submit guard: no double-submit, wait for photo processing ---------- */
  document.querySelectorAll("form[method=post]").forEach(function (form) {
    form.addEventListener("submit", function (event) {
      // An inline onsubmit="return confirm(...)" that was cancelled already prevented it.
      if (event.defaultPrevented) return;
      if (form.hasAttribute("data-processing")) {
        event.preventDefault();
        alertInline(form, "Tunggu sebentar, foto masih diproses…");
        return;
      }
      if (form.hasAttribute("data-submitting")) {
        event.preventDefault();
        return;
      }
      form.setAttribute("data-submitting", "1");
      var button = event.submitter || form.querySelector("[type=submit]");
      // Disable after the browser has collected the form data, so the button's
      // own name/value (e.g. "reset") is still sent.
      setTimeout(function () {
        if (!button) return;
        button.disabled = true;
        button.setAttribute("data-label", button.textContent);
        if (button.classList.contains("btn-primary")) button.textContent = "Menyimpan…";
      }, 0);
    });
  });

  // Back/forward cache restores a page with the guard still set; reset it.
  window.addEventListener("pageshow", function (e) {
    if (!e.persisted) return;
    document.querySelectorAll("form[data-submitting]").forEach(function (form) {
      form.removeAttribute("data-submitting");
      form.querySelectorAll("[data-label]").forEach(function (b) {
        b.disabled = false;
        b.textContent = b.getAttribute("data-label");
      });
    });
  });

  function alertInline(form, text) {
    var note = form.querySelector(".form-note");
    if (!note) {
      note = document.createElement("p");
      note.className = "hint hint-error form-note";
      form.appendChild(note);
    }
    note.textContent = text;
  }

  /* ---------- Keep-alive: an open form never loses its session ---------- */
  var keepForm = document.querySelector("form[data-keepalive]");
  if (keepForm) {
    var timer = setInterval(function () {
      fetch("ping.php", { credentials: "same-origin", cache: "no-store" }).then(function (res) {
        if (res.status === 401) {
          clearInterval(timer);
          var banner = document.createElement("div");
          banner.className = "flash flash-error";
          banner.textContent = "Sesi Anda berakhir. Salin teks yang sudah diketik, lalu buka halaman ini di tab baru untuk masuk lagi sebelum menyimpan.";
          keepForm.parentNode.insertBefore(banner, keepForm);
        }
      }).catch(function () { /* offline for a moment — try again next tick */ });
    }, 4 * 60 * 1000);
  }
})();
