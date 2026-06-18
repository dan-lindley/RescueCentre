<?php
if (!defined('APP_LOADED')) exit;

$centre_id = (int)($centre_id ?? 0);

// Fetch centre logo
$centre_logo = '';
if ($centre_id > 0) {
    $stmt = $pdo->prepare("SELECT centre_logo FROM rescue_centre_meta WHERE centre_id = ? LIMIT 1");
    $stmt->execute([$centre_id]);
    $centre_logo = (string)($stmt->fetchColumn() ?? '');
}

// Fetch templates (include layout JSON)
$stmt = $pdo->prepare("
    SELECT template_id, title, image_path, thumb_path, category, layout_recipient, layout_admin
    FROM rescue_certificate_templates
    WHERE active = 1
    ORDER BY category, title
");
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Categories
$categories = [];
foreach ($templates as $t) $categories[$t['category']] = true;
$categories = array_keys($categories);
sort($categories);
?>

<div id="certificate-gen-wrapper">

  <!-- TOP: category selector -->
  <div class="certGen-topbar">
    <div class="certGen-topbar-left">
      <label class="certGen-label">Category</label>
      <select id="categorySelectGen" class="xform-input" style="width:320px;">
        <option value="">All categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="certGen-topbar-right">
      <button type="button" id="downloadPngBtnGen" class="btn blue">Download PNG</button>
      <div id="certGenDebug" class="rc-alert purple certGen-status">Ready.</div>
    </div>
  </div>

  <!-- THUMBS -->
  <div id="certGen-gallery" class="certGen-gallery"></div>

  <!-- NAME + FORMATTING -->
  <div class="certGen-panel">
    <div class="certGen-panel-title">Name & formatting</div>

    <div class="certGen-row">
      <div class="certGen-field">
        <label class="certGen-label">Recipient name</label>
        <input type="text" id="recipientInputGen" class="xform-input" placeholder="Recipient name" style="width:320px;" autocomplete="off">
      </div>

      <div class="certGen-field">
        <label class="certGen-label">Font</label>
        <select id="recipientFontGen" class="xform-input" style="width:320px;">
          <option value='Arial, sans-serif' style="font-family: Arial, sans-serif;">Arial</option>
          <option value='Trebuchet MS, Arial, sans-serif' style="font-family: Trebuchet MS, Arial, sans-serif;">Trebuchet</option>
          <option value='Georgia, serif' style="font-family: Georgia, serif;">Georgia</option>
          <option value='Times New Roman, Times, serif' style="font-family: 'Times New Roman', Times, serif;">Times New Roman</option>

          <!-- script-like (best effort; depends on OS fonts; falls back to cursive) -->
          <option value='"Segoe Script","Brush Script MT","Lucida Handwriting","Apple Chancery",cursive'
                  style="font-family: 'Segoe Script','Brush Script MT','Lucida Handwriting','Apple Chancery',cursive;">
            Script 1
          </option>
          <option value='"Brush Script MT","Segoe Script","Apple Chancery",cursive'
                  style="font-family: 'Brush Script MT','Segoe Script','Apple Chancery',cursive;">
            Script 2
          </option>
          <option value='"Lucida Handwriting","Segoe Script","Apple Chancery",cursive'
                  style="font-family: 'Lucida Handwriting','Segoe Script','Apple Chancery',cursive;">
            Script 3
          </option>
        </select>
      </div>

      <div class="certGen-field">
        <label class="certGen-label">Size</label>
        <select id="recipientSizeGen" class="xform-input" style="width:140px;">
          <option value="48">48px</option>
          <option value="56">56px</option>
          <option value="64" selected>64px</option>
          <option value="72">72px</option>
          <option value="80">80px</option>
          <option value="90">90px</option>
        </select>
      </div>

      <div class="certGen-field certGen-field-inline">
        <label class="certGen-label">Bold</label>
        <label class="certGen-check">
          <input type="checkbox" id="recipientBoldGen">
          <span>On</span>
        </label>
      </div>

      <div class="certGen-field certGen-field-inline">
        <label class="certGen-label">Reset</label>
        <button type="button" id="resetPosBtnGen" class="btn orange">Reset name position</button>
      </div>
    </div>

    <!-- COLOURS as squares -->
    <div class="certGen-row certGen-row-colours">
      <div class="certGen-colour-group">
        <div class="certGen-label">Recipient colour</div>
        <div class="certGen-swatches" data-target="recipient"></div>
      </div>

      <div class="certGen-colour-group">
        <div class="certGen-label">Date colour</div>
        <div class="certGen-swatches" data-target="date"></div>
      </div>

      <div class="certGen-colour-group">
        <div class="certGen-label">Centre name colour</div>
        <div class="certGen-swatches" data-target="centre"></div>
      </div>
    </div>
  </div>

  <!-- EMAIL -->
  <div class="certGen-panel">
    <div class="certGen-panel-title">Email</div>

    <div class="certGen-row">
      <div class="certGen-field">
        <label class="certGen-label">To</label>
        <input type="email" id="emailToGen" class="xform-input" placeholder="Email to…" style="width:320px;">
      </div>

      <div class="certGen-field">
        <label class="certGen-label">Subject</label>
        <input type="text" id="emailSubjectGen" class="xform-input" placeholder="Subject (optional)" style="width:320px;">
      </div>

      <div class="certGen-field" style="flex:1; min-width:340px;">
        <label class="certGen-label">Message</label>
        <textarea id="emailMessageGen" class="xform-input" placeholder="Message (optional)" rows="2" style="width:100%;resize:vertical;"></textarea>
      </div>

      <div class="certGen-field certGen-field-inline">
        <label class="certGen-label">Send</label>
        <button type="button" id="sendEmailBtnGen" class="btn green">Email PNG</button>
        <span id="emailStatusGen" style="margin-left:10px;"></span>
      </div>
    </div>
  </div>

  <!-- PREVIEW -->
  <div class="certGen-panel">
    <div class="certGen-panel-title">Preview</div>
    <canvas id="certCanvasGen" width="1500" height="900"></canvas>
  </div>

</div>

<style>
#certificate-gen-wrapper { max-width: 1600px; margin: 0 auto; }

.certGen-topbar{
  display:flex;
  align-items:flex-end;
  justify-content:space-between;
  gap:12px;
  margin-bottom: 12px;
  border: 1px solid var(--rc-border);
  border-radius: var(--rc-radius);
  padding: 12px;
  background: var(--rc-surface);
  box-shadow: var(--rc-shadow);
}
.certGen-topbar-left{ display:flex; flex-direction:column; gap:6px; }
.certGen-topbar-right{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }

.certGen-status{ padding:8px 10px; margin:0; display:inline-block; }

.certGen-label{
  font-size: 0.85rem;
  color: var(--rc-muted);
}

.certGen-gallery{
  display:flex;
  flex-wrap:wrap;
  gap:14px;
  margin-bottom: 16px;
}

.cert-thumb{
  width:220px;
  cursor:pointer;
  border:1px solid var(--rc-border);
  border-radius:4px;
  background:var(--rc-surface);
  transition:0.2s;
}
.cert-thumb:hover{
  transform: scale(1.04);
  border-color:#0a84ff;
}

.certGen-panel{
  border: 1px solid var(--rc-border);
  border-radius: var(--rc-radius);
  padding: 12px;
  margin-bottom: 14px;
  background: var(--rc-surface);
  box-shadow: var(--rc-shadow);
}
.certGen-panel-title{
  font-weight: 700;
  margin-bottom: 10px;
  color: var(--rc-text);
}

.certGen-row{
  display:flex;
  gap:12px;
  align-items:flex-end;
  flex-wrap:wrap;
}
.certGen-field{
  display:flex;
  flex-direction:column;
  gap:6px;
}
.certGen-field-inline{
  flex-direction:row;
  align-items:center;
  gap:10px;
  padding-top: 18px;
}

.certGen-check{
  display:inline-flex;
  align-items:center;
  gap:6px;
  user-select:none;
  padding: 0 6px;
}

.certGen-row-colours{
  margin-top: 10px;
  align-items:flex-start;
}
.certGen-colour-group{
  display:flex;
  flex-direction:column;
  gap:6px;
  min-width: 220px;
}
.certGen-swatches{
  display:flex;
  gap:8px;
  align-items:center;
  flex-wrap:wrap;
}
.certGen-swatch{
  width: 22px;
  height: 22px;
  border-radius: 5px;
  border: 1px solid var(--rc-border);
  cursor:pointer;
  box-shadow: 0 1px 2px rgba(0,0,0,0.08);
}
.certGen-swatch.active{
  outline: 3px solid rgba(10,132,255,0.45);
  border-color: #0a84ff;
}

#certCanvasGen{
  width:100%;
  height:auto;
  border:2px solid var(--rc-border);
  border-radius:4px;
  background:#fff;
  cursor:default;
  touch-action:none;
}
</style>

<script>
(() => {
  const TEMPLATES = <?= json_encode($templates, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  const rescueName = <?= json_encode((string)($rescue_name ?? 'Rescue Centre')) ?>;
  const centreLogoSrc = <?= json_encode((string)$centre_logo) ?>;

  const el = (id) => document.getElementById(id);
  const dbgEl = el("certGenDebug");
  const dbg = (msg) => { dbgEl.textContent = msg; };

  const categorySelect = el("categorySelectGen");
  const gallery = el("certGen-gallery");

  const recipientInput = el("recipientInputGen");
  const recipientFontSel = el("recipientFontGen");
  const recipientSizeSel = el("recipientSizeGen");
  const recipientBoldChk = el("recipientBoldGen");
  const resetPosBtn = el("resetPosBtnGen");

  const downloadPngBtn = el("downloadPngBtnGen");

  const emailToEl = el("emailToGen");
  const emailSubjectEl = el("emailSubjectGen");
  const emailMessageEl = el("emailMessageGen");
  const sendEmailBtn = el("sendEmailBtnGen");
  const emailStatusEl = el("emailStatusGen");

  const canvas = el("certCanvasGen");
  const ctx = canvas.getContext("2d");

  function setEmailStatus(msg, ok=true) {
    emailStatusEl.textContent = msg;
    emailStatusEl.style.color = ok ? "#117a00" : "#b00000";
  }

  // Colour swatches (squares)
  const COLOURS = [
    { key: "black",  value: "#000000" },
    { key: "white",  value: "#FFFFFF" },
    { key: "gold",   value: "#C9A227" },
    { key: "red",    value: "#D64545" },
    { key: "green",  value: "#2E8B57" },
    { key: "blue",   value: "#2F6FD6" },
    { key: "yellow", value: "#E0C341" },
  ];

  let recipientColour = "#000000";
  let dateColour = "#000000";
  let centreColour = "#000000";

  function renderSwatches() {
    document.querySelectorAll(".certGen-swatches").forEach(container => {
      const target = container.getAttribute("data-target");
      container.innerHTML = "";

      COLOURS.forEach(c => {
        const sw = document.createElement("div");
        sw.className = "certGen-swatch";
        sw.style.background = c.value;

        const activeVal = (target === "recipient") ? recipientColour : (target === "date") ? dateColour : centreColour;
        if (activeVal === c.value) sw.classList.add("active");

        sw.title = c.key;

        sw.onclick = () => {
          if (target === "recipient") recipientColour = c.value;
          if (target === "date") dateColour = c.value;
          if (target === "centre") centreColour = c.value;

          renderSwatches();
          if (bgImage.complete && bgImage.naturalWidth) redraw();
        };

        container.appendChild(sw);
      });
    });
  }

  // Images
  let bgImage = new Image();
  let logoImage = new Image();
  let logoLoaded = false;

  // Prevent canvas tainting (works for same-origin paths)
  bgImage.crossOrigin = "anonymous";
  logoImage.crossOrigin = "anonymous";

  let currentBgSrc = "";

  // Recipient font controls
  let recipientFontFamily = recipientFontSel.value || "Arial, sans-serif";
  let recipientFontSize = parseInt(recipientSizeSel.value || "64", 10) || 64;
  let recipientBold = !!recipientBoldChk.checked;

  // Recipient state (draggable)
  let recipientName = "";
  let recipientX = canvas.width / 2;
  let recipientY = canvas.height * 0.62;
  let baseRecipientX = recipientX;
  let baseRecipientY = recipientY;

  // Date
  const dateFontSize = 36;
  const dateFontFamily = "Arial";
  let dateText = "";
  let dateX = canvas.width / 2;
  let dateY = canvas.height * 0.72;

  // Rescue name
  const rescueNameFontSize = 26;
  const rescueNameFontFamily = "Arial";
  let rescueNameX = canvas.width / 2;
  let rescueNameY = canvas.height * 0.12;

  // Logo placement
  let logoX = 140, logoY = 140, logoD = 150, logoR = 75;
  const logoInsetPx = 8;

  // Drag
  let draggingRecipient = false;
  let dragOffsetX = 0, dragOffsetY = 0;

  function safeParseJSON(raw) {
    if (!raw) return null;
    try { const obj = JSON.parse(raw); return (obj && typeof obj === "object") ? obj : null; }
    catch { return null; }
  }
  function pctToPx(p, total) { return (p / 100) * total; }
  function formatDateUK() {
    const d = new Date();
    return d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
  }

  // Load logo
  (function initLogo(){
    if (!centreLogoSrc) { dbg("Logo not set for centre."); return; }
    logoImage.onload  = () => { logoLoaded = true; redraw(); };
    logoImage.onerror = () => { logoLoaded = false; dbg("Logo failed to load: " + centreLogoSrc); redraw(); };
    logoImage.src = centreLogoSrc;
  })();

  function renderGallery() {
    const cat = categorySelect.value;
    gallery.innerHTML = "";

    TEMPLATES.forEach(t => {
      if (cat && t.category !== cat) return;

      const img = document.createElement("img");
      img.src = t.thumb_path || t.image_path;
      img.className = "cert-thumb";
      img.title = t.title;
      img.onclick = () => selectTemplate(t);

      gallery.appendChild(img);
    });

    dbg("Gallery ready. Select a certificate.");
  }

  function applyLayout(t) {
    dateText = formatDateUK();

    const lr = safeParseJSON(t.layout_recipient);
    if (lr && typeof lr.x === "number" && typeof lr.y === "number") {
      recipientX = pctToPx(lr.x, canvas.width);
      recipientY = pctToPx(lr.y, canvas.height);
    } else {
      recipientX = canvas.width / 2;
      recipientY = canvas.height * 0.62;
    }
    baseRecipientX = recipientX; baseRecipientY = recipientY;

    const la = safeParseJSON(t.layout_admin);

    if (la && la.date && typeof la.date.x === "number" && typeof la.date.y === "number") {
      dateX = pctToPx(la.date.x, canvas.width);
      dateY = pctToPx(la.date.y, canvas.height);
    } else {
      dateX = canvas.width / 2;
      dateY = canvas.height * 0.72;
    }

    if (la && la.rescue_name && typeof la.rescue_name.x === "number" && typeof la.rescue_name.y === "number") {
      rescueNameX = pctToPx(la.rescue_name.x, canvas.width);
      rescueNameY = pctToPx(la.rescue_name.y, canvas.height);
    } else {
      rescueNameX = canvas.width / 2;
      rescueNameY = canvas.height * 0.12;
    }

    if (la && la.logo && typeof la.logo.x === "number" && typeof la.logo.y === "number") {
      logoX = pctToPx(la.logo.x, canvas.width);
      logoY = pctToPx(la.logo.y, canvas.height);
      if (typeof la.logo.d === "number") logoD = la.logo.d;
    } else {
      logoX = 140; logoY = 140; logoD = 150;
    }

    if (!Number.isFinite(logoD) || logoD < 50) logoD = 150;
    if (logoD > 500) logoD = 500;
    logoR = logoD / 2;
  }

  function setFont(size, family, align="center", bold=false) {
    ctx.font = `${bold ? "700 " : ""}${size}px ${family}`;
    ctx.textAlign = align;
    ctx.textBaseline = "alphabetic";
  }

  function drawLogoPlate() {
    ctx.save();
    ctx.globalAlpha = 0.75;
    ctx.beginPath();
    ctx.arc(logoX, logoY, logoR, 0, Math.PI * 2);
    ctx.closePath();
    ctx.fillStyle = "#fff";
    ctx.fill();

    ctx.globalAlpha = 1;
    ctx.lineWidth = 2;
    ctx.strokeStyle = "#ddd";
    ctx.stroke();

    if (!logoLoaded) {
      ctx.fillStyle = "#666";
      ctx.font = "18px Arial";
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";
      ctx.fillText("LOGO", logoX, logoY);
    }
    ctx.restore();
  }

  function drawLogoImageClipped() {
    if (!logoLoaded) return;
    const innerD = Math.max(10, logoD - logoInsetPx);
    const innerR = innerD / 2;
    const left = logoX - innerR;
    const top  = logoY - innerR;

    ctx.save();
    ctx.beginPath();
    ctx.arc(logoX, logoY, innerR, 0, Math.PI * 2);
    ctx.closePath();
    ctx.clip();
    ctx.drawImage(logoImage, left, top, innerD, innerD);
    ctx.restore();
  }

  function drawRescueName() {
    if (!rescueName) return;
    ctx.save();
    setFont(rescueNameFontSize, rescueNameFontFamily, "center", false);
    ctx.fillStyle = centreColour;
    ctx.fillText(rescueName, rescueNameX, rescueNameY);
    ctx.restore();
  }

  function drawDate() {
    if (!dateText) return;
    ctx.save();
    setFont(dateFontSize, dateFontFamily, "center", false);
    ctx.fillStyle = dateColour;
    ctx.fillText(dateText, dateX, dateY);
    ctx.restore();
  }

  function drawRecipient() {
    if (!recipientName) return;
    ctx.save();
    setFont(recipientFontSize, recipientFontFamily, "center", recipientBold);
    ctx.fillStyle = recipientColour;
    ctx.fillText(recipientName, recipientX, recipientY);
    ctx.restore();
  }

  function getRecipientBBox() {
    if (!recipientName) return null;
    ctx.save();
    setFont(recipientFontSize, recipientFontFamily, "center", recipientBold);
    const w = ctx.measureText(recipientName).width;
    ctx.restore();
    const h = recipientFontSize;
    return { left: recipientX - (w/2), top: recipientY - h, width: w, height: h };
  }

  function redraw() {
    if (!currentBgSrc) {
      ctx.clearRect(0,0,canvas.width,canvas.height);
      return;
    }
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.drawImage(bgImage, 0, 0, canvas.width, canvas.height);

    drawLogoPlate();
    drawLogoImageClipped();
    drawRescueName();
    drawRecipient();
    drawDate();
  }

  function loadCertificate(src) {
    currentBgSrc = src;

    bgImage.crossOrigin = "anonymous";
    bgImage.onload = () => { dbg("Backdrop loaded."); redraw(); };
    bgImage.onerror = () => { dbg("Backdrop failed: " + src); };

    bgImage.src = src;
  }

  function selectTemplate(t) {
    applyLayout(t);
    loadCertificate(t.image_path);
    dbg("Selected: " + (t.title || "template"));
  }

  function ensureSelected() {
    if (!currentBgSrc) {
      alert("Select a certificate first.");
      dbg("No certificate selected.");
      return false;
    }
    return true;
  }

  function getMousePos(e) {
    const r = canvas.getBoundingClientRect();
    return {
      x: (e.clientX - r.left) * (canvas.width / r.width),
      y: (e.clientY - r.top)  * (canvas.height / r.height)
    };
  }

  canvas.addEventListener("mousedown", (e) => {
    if (!currentBgSrc || !recipientName) return;
    const pos = getMousePos(e);
    const bb = getRecipientBBox();
    if (!bb) return;

    const inside = pos.x >= bb.left && pos.x <= bb.left + bb.width && pos.y >= bb.top && pos.y <= bb.top + bb.height;
    if (inside) {
      draggingRecipient = true;
      dragOffsetX = pos.x - recipientX;
      dragOffsetY = pos.y - recipientY;
      canvas.style.cursor = "move";
    }
  });

  canvas.addEventListener("mousemove", (e) => {
    if (!currentBgSrc) return;
    const pos = getMousePos(e);

    if (!draggingRecipient && recipientName) {
      const bb = getRecipientBBox();
      const hover = bb && pos.x >= bb.left && pos.x <= bb.left + bb.width && pos.y >= bb.top && pos.y <= bb.top + bb.height;
      canvas.style.cursor = hover ? "move" : "default";
    }

    if (!draggingRecipient) return;

    recipientX = pos.x - dragOffsetX;
    recipientY = pos.y - dragOffsetY;

    recipientX = Math.max(0, Math.min(canvas.width, recipientX));
    recipientY = Math.max(recipientFontSize, Math.min(canvas.height, recipientY));

    redraw();
  });

  function endDrag() { draggingRecipient = false; canvas.style.cursor = "default"; }
  canvas.addEventListener("mouseup", endDrag);
  canvas.addEventListener("mouseleave", endDrag);

  // Download PNG
  downloadPngBtn.onclick = () => {
    dbg("PNG click.");
    if (!ensureSelected()) return;

    try {
      redraw();
      canvas.toBlob((blob) => {
        if (!blob) { dbg("PNG export failed."); return; }
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = "certificate.png";
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
        dbg("PNG download triggered.");
      }, "image/png");
    } catch (e) {
      dbg("PNG error: " + (e && e.message ? e.message : e));
    }
  };

  // Email PNG
  sendEmailBtn.onclick = async () => {
    dbg("Email click.");
    if (!ensureSelected()) return;

    const to = (emailToEl.value || "").trim();
    if (!to) { alert("Enter an email address."); dbg("Missing email address."); return; }

    setEmailStatus("Sending…", true);

    try {
      redraw();
      const pngDataUrl = canvas.toDataURL("image/png");

      const resp = await fetch("/controllers/email_certificate.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          to,
          subject: (emailSubjectEl.value || "").trim(),
          message: (emailMessageEl.value || "").trim(),
          png_data_url: pngDataUrl
        })
      });

      const text = await resp.text();
      let json = null;
      try { json = JSON.parse(text); } catch { json = null; }

      if (!resp.ok) {
        setEmailStatus("Email failed (HTTP " + resp.status + ")", false);
        console.error("Email controller response:", text);
        dbg("Email HTTP " + resp.status);
        return;
      }

      if (json && json.status === "success") {
        setEmailStatus("Sent ✅", true);
        dbg("Email sent.");
      } else {
        setEmailStatus((json && json.message) ? json.message : "Email failed", false);
        console.error("Email controller response:", text);
        dbg("Email failed (server).");
      }
    } catch (e) {
      console.error(e);
      dbg("Email exception: " + (e && e.message ? e.message : e));
      setEmailStatus("Email failed (network/server).", false);
    }
  };

  // UI events
  categorySelect.addEventListener("change", renderGallery);

  recipientInput.addEventListener("input", () => {
    recipientName = recipientInput.value || "";
    if (bgImage.complete && bgImage.naturalWidth) redraw();
  });

  recipientFontSel.addEventListener("change", () => {
    recipientFontFamily = recipientFontSel.value || "Arial, sans-serif";
    if (bgImage.complete && bgImage.naturalWidth) redraw();
  });

  recipientSizeSel.addEventListener("change", () => {
    const v = parseInt(recipientSizeSel.value || "64", 10);
    recipientFontSize = Number.isFinite(v) ? v : 64;
    if (bgImage.complete && bgImage.naturalWidth) redraw();
  });

  recipientBoldChk.addEventListener("change", () => {
    recipientBold = !!recipientBoldChk.checked;
    if (bgImage.complete && bgImage.naturalWidth) redraw();
  });

  resetPosBtn.addEventListener("click", () => {
    recipientX = baseRecipientX;
    recipientY = baseRecipientY;
    if (bgImage.complete && bgImage.naturalWidth) redraw();
    dbg("Name reset.");
  });

  // Init
  renderSwatches();
  renderGallery();
})();
</script>

