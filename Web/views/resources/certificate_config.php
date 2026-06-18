<?php
if (!defined('APP_LOADED')) exit;

/* ------------------------------------------------------------
   ADMIN GUARD (DB truth: accounts.role)
------------------------------------------------------------ */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$account_id = (int)($_SESSION['account_id'] ?? 0);
if ($account_id <= 0) {
    echo '<div class="rc-alert red">Access denied.</div>';
    return;
}

$stmt = $pdo->prepare("SELECT role FROM accounts WHERE id = ? LIMIT 1");
$stmt->execute([$account_id]);
$role = (string)($stmt->fetchColumn() ?? '');
if ($role !== 'Admin') {
    echo '<div class="rc-alert red">Access denied.</div>';
    return;
}

/* ------------------------------------------------------------
   PAGE LOAD: fetch templates including layout_recipient + layout_admin
------------------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT template_id, title, image_path, thumb_path, category, layout_recipient, layout_admin
    FROM rescue_certificate_templates
    WHERE active = 1
    ORDER BY category, title
");
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Category list
$categories = [];
foreach ($templates as $t) {
    $categories[$t['category']] = true;
}
$categories = array_keys($categories);
sort($categories);
?>

<div id="certificate-config-wrapper">

  <div class="rc-panel">
    <h4>Certificate Config</h4>
    <p class="rc-muted" style="margin:4px 0 0 0;">Set default positions for recipient, date, logo zone and centre name, then save.</p>
  </div>

  <div id="certCfg-toolbar" class="rc-panel">
    <select id="categorySelectCfg" class="xform-input" style="width:260px;">
      <option value="">All categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
      <?php endforeach; ?>
    </select>

    <input
      type="text"
      id="recipientInputCfg"
      class="xform-input"
      placeholder="Recipient preview text"
      style="width:260px;"
      autocomplete="off"
      value="Recipient Name"
    >

    <button type="button" id="saveRecipientBtnCfg" class="btn blue" disabled>Save recipient</button>
    <button type="button" id="saveDateBtnCfg" class="btn blue" disabled>Save date</button>
    <button type="button" id="saveLogoBtnCfg" class="btn blue" disabled>Save logo</button>
    <button type="button" id="saveCentreNameBtnCfg" class="btn blue" disabled>Save centre name</button>
    <button type="button" id="resetPosBtnCfg" class="btn orange" disabled>Reset</button>

    <span id="saveStatusCfg" style="margin-left:10px;"></span>
  </div>

  <div id="certCfg-gallery"></div>

  <canvas id="certCanvasCfg" width="1500" height="900"></canvas>

</div>

<style>
#certificate-config-wrapper { max-width: 1600px; margin: 0 auto; }

#certCfg-toolbar {
  margin: 14px 0 15px;
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

#certCfg-gallery {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin-bottom: 15px;
}

#certificate-config-wrapper .cert-thumb {
  width: 220px;
  cursor: pointer;
  border: 1px solid var(--rc-border);
  border-radius: 4px;
  background: var(--rc-surface);
  transition: 0.2s;
}

#certificate-config-wrapper .cert-thumb:hover {
  transform: scale(1.04);
  border-color: #0a84ff;
}

#certCanvasCfg {
  width: 100%;
  height: auto;
  border: 2px solid var(--rc-border);
  border-radius: 4px;
  background: #fff;
  cursor: default;
  touch-action: none;
}
</style>

<script>
(() => {
  const certCfgTemplates = <?= json_encode($templates, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  const categorySelect       = document.getElementById("categorySelectCfg");
  const recipientInput       = document.getElementById("recipientInputCfg");
  const saveRecipientBtn     = document.getElementById("saveRecipientBtnCfg");
  const saveDateBtn          = document.getElementById("saveDateBtnCfg");
  const saveLogoBtn          = document.getElementById("saveLogoBtnCfg");
  const saveCentreNameBtn    = document.getElementById("saveCentreNameBtnCfg");
  const resetBtn             = document.getElementById("resetPosBtnCfg");
  const statusEl             = document.getElementById("saveStatusCfg");
  const gallery              = document.getElementById("certCfg-gallery");

  const canvas = document.getElementById("certCanvasCfg");
  const ctx = canvas.getContext("2d");

  // Single controller handles all actions
  const SAVE_URL = "/controllers/save_certificate_recipient_layout.php";

  let bgImage = new Image();
  let currentBgSrc = "";
  let selectedTemplateId = 0;

  // Recipient
  let recipientName = recipientInput.value || "Recipient Name";
  const recipientFontSize = 64;
  const recipientFontFamily = "Arial";
  let recipientX = canvas.width / 2;
  let recipientY = canvas.height * 0.62;

  // Date
  const dateFontSize = 36;
  const dateFontFamily = "Arial";
  let dateText = "";
  let dateX = canvas.width / 2;
  let dateY = canvas.height * 0.72;

  // Logo zone (white circle, 150px, 75% opacity)
  const logoDiameter = 150;
  const logoRadius = logoDiameter / 2;
  let logoX = 140;
  let logoY = 140;

  // Centre name placeholder (smaller than date: ~70% of 36 => 26)
  const centreNameFontSize = 26;
  const centreNameFontFamily = "Arial";
  let centreNameText = "Rescue Centre Name";
  let centreNameX = canvas.width / 2;
  let centreNameY = canvas.height * 0.12;

  // Reset baselines (per template)
  let baseRecipientX = recipientX, baseRecipientY = recipientY;
  let baseDateX = dateX, baseDateY = dateY;
  let baseLogoX = logoX, baseLogoY = logoY;
  let baseCentreNameX = centreNameX, baseCentreNameY = centreNameY;

  // Drag state: 'logo' | 'recipient' | 'date' | 'centre_name' | null
  let draggingKey = null;
  let dragOffsetX = 0;
  let dragOffsetY = 0;

  function setStatus(msg, ok = true) {
    statusEl.textContent = msg;
    statusEl.style.color = ok ? "#117a00" : "#b00000";
  }

  function pctToPx(p, total) { return (p / 100) * total; }
  function pxToPct(px, total) { return (px / total) * 100; }

  function formatDateUK() {
    const d = new Date();
    return d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
  }

  function safeParseJSON(raw) {
    if (!raw) return null;
    try {
      const obj = JSON.parse(raw);
      return (obj && typeof obj === 'object') ? obj : null;
    } catch {
      return null;
    }
  }

  function renderGallery() {
    const cat = categorySelect.value;
    gallery.innerHTML = "";

    certCfgTemplates.forEach(t => {
      if (cat && t.category !== cat) return;

      const img = document.createElement("img");
      img.src = t.thumb_path || t.image_path;
      img.className = "cert-thumb";
      img.title = t.title;
      img.onclick = () => selectTemplate(t);
      gallery.appendChild(img);
    });
  }

  function setFont(size, family, align="center") {
    ctx.font = `${size}px ${family}`;
    ctx.textAlign = align;
    ctx.textBaseline = "alphabetic";
  }

  function bboxForText(text, x, y, size, family, align="center") {
    if (!text) return null;
    setFont(size, family, align);
    const w = ctx.measureText(text).width;
    const h = size;
    const left = (align === "center") ? (x - w/2) : (align === "right" ? (x - w) : x);
    const top  = y - h;
    return { left, top, width: w, height: h };
  }

  function hitLogoZone(mx, my) {
    const dx = mx - logoX;
    const dy = my - logoY;
    return (dx*dx + dy*dy) <= (logoRadius * logoRadius);
  }

  function drawLogoZone() {
    ctx.save();

    // 75% opacity white circle
    ctx.globalAlpha = 0.75;
    ctx.beginPath();
    ctx.arc(logoX, logoY, logoRadius, 0, Math.PI * 2);
    ctx.closePath();
    ctx.fillStyle = "#fff";
    ctx.fill();

    // Outline at full opacity
    ctx.globalAlpha = 1;
    ctx.lineWidth = 2;
    ctx.strokeStyle = "#ddd";
    ctx.stroke();

    // label
    ctx.fillStyle = "#666";
    ctx.font = "18px Arial";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText("LOGO", logoX, logoY);

    ctx.restore();
  }

  function drawCentreName() {
    if (!centreNameText) return;
    ctx.save();
    setFont(centreNameFontSize, centreNameFontFamily, "center");
    ctx.fillStyle = "#000";
    ctx.fillText(centreNameText, centreNameX, centreNameY);
    ctx.restore();
  }

  function drawRecipient() {
    if (!recipientName) return;
    ctx.save();
    setFont(recipientFontSize, recipientFontFamily, "center");
    ctx.fillStyle = "#000";
    ctx.fillText(recipientName, recipientX, recipientY);
    ctx.restore();
  }

  function drawDate() {
    if (!dateText) return;
    ctx.save();
    setFont(dateFontSize, dateFontFamily, "center");
    ctx.fillStyle = "#000";
    ctx.fillText(dateText, dateX, dateY);
    ctx.restore();
  }

  function redraw() {
    if (!currentBgSrc) {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      return;
    }
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(bgImage, 0, 0, canvas.width, canvas.height);

    // Order: logo zone -> centre name -> recipient -> date
    drawLogoZone();
    drawCentreName();
    drawRecipient();
    drawDate();
  }

  function loadCertificate(src) {
    currentBgSrc = src;

    bgImage.onload = () => redraw();
    bgImage.onerror = () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.fillStyle = "#c00";
      ctx.font = "30px Arial";
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";
      ctx.fillText("Failed to load certificate image", canvas.width / 2, canvas.height / 2);
      console.error("Failed to load image:", src);
    };

    bgImage.src = src;
  }

  function selectTemplate(t) {
    selectedTemplateId = parseInt(t.template_id, 10) || 0;
    loadCertificate(t.image_path);

    saveRecipientBtn.disabled = false;
    saveDateBtn.disabled = false;
    saveLogoBtn.disabled = false;
    saveCentreNameBtn.disabled = false;
    resetBtn.disabled = false;

    dateText = formatDateUK();

    // Recipient defaults
    const lr = safeParseJSON(t.layout_recipient);
    if (lr && typeof lr.x === 'number' && typeof lr.y === 'number') {
      recipientX = pctToPx(lr.x, canvas.width);
      recipientY = pctToPx(lr.y, canvas.height);
    } else {
      recipientX = canvas.width / 2;
      recipientY = canvas.height * 0.62;
    }

    // Admin defaults: date + logo + rescue_name
    const la = safeParseJSON(t.layout_admin);

    if (la && la.date && typeof la.date.x === 'number' && typeof la.date.y === 'number') {
      dateX = pctToPx(la.date.x, canvas.width);
      dateY = pctToPx(la.date.y, canvas.height);
    } else {
      dateX = canvas.width / 2;
      dateY = canvas.height * 0.72;
    }

    if (la && la.logo && typeof la.logo.x === 'number' && typeof la.logo.y === 'number') {
      logoX = pctToPx(la.logo.x, canvas.width);
      logoY = pctToPx(la.logo.y, canvas.height);
    } else {
      logoX = 140; logoY = 140;
    }

    if (la && la.rescue_name && typeof la.rescue_name.x === 'number' && typeof la.rescue_name.y === 'number') {
      centreNameX = pctToPx(la.rescue_name.x, canvas.width);
      centreNameY = pctToPx(la.rescue_name.y, canvas.height);
    } else {
      centreNameX = canvas.width / 2;
      centreNameY = canvas.height * 0.12;
    }

    baseRecipientX = recipientX; baseRecipientY = recipientY;
    baseDateX = dateX; baseDateY = dateY;
    baseLogoX = logoX; baseLogoY = logoY;
    baseCentreNameX = centreNameX; baseCentreNameY = centreNameY;

    setStatus(`Selected: ${t.title}`, true);
    redraw();
  }

  function getMousePos(e) {
    const r = canvas.getBoundingClientRect();
    return {
      x: (e.clientX - r.left) * (canvas.width / r.width),
      y: (e.clientY - r.top)  * (canvas.height / r.height)
    };
  }

  canvas.addEventListener("mousedown", (e) => {
    if (!currentBgSrc) return;
    const pos = getMousePos(e);

    // Logo zone first (easy to grab)
    if (hitLogoZone(pos.x, pos.y)) {
      draggingKey = "logo";
      dragOffsetX = pos.x - logoX;
      dragOffsetY = pos.y - logoY;
      canvas.style.cursor = "move";
      return;
    }

    // Centre name
    const cnBB = bboxForText(centreNameText, centreNameX, centreNameY, centreNameFontSize, centreNameFontFamily, "center");
    if (cnBB &&
        pos.x >= cnBB.left && pos.x <= cnBB.left + cnBB.width &&
        pos.y >= cnBB.top  && pos.y <= cnBB.top  + cnBB.height) {
      draggingKey = "centre_name";
      dragOffsetX = pos.x - centreNameX;
      dragOffsetY = pos.y - centreNameY;
      canvas.style.cursor = "move";
      return;
    }

    // Recipient
    const recBB = bboxForText(recipientName, recipientX, recipientY, recipientFontSize, recipientFontFamily, "center");
    if (recBB &&
        pos.x >= recBB.left && pos.x <= recBB.left + recBB.width &&
        pos.y >= recBB.top  && pos.y <= recBB.top  + recBB.height) {
      draggingKey = "recipient";
      dragOffsetX = pos.x - recipientX;
      dragOffsetY = pos.y - recipientY;
      canvas.style.cursor = "move";
      return;
    }

    // Date
    const dateBB = bboxForText(dateText, dateX, dateY, dateFontSize, dateFontFamily, "center");
    if (dateBB &&
        pos.x >= dateBB.left && pos.x <= dateBB.left + dateBB.width &&
        pos.y >= dateBB.top  && pos.y <= dateBB.top  + dateBB.height) {
      draggingKey = "date";
      dragOffsetX = pos.x - dateX;
      dragOffsetY = pos.y - dateY;
      canvas.style.cursor = "move";
      return;
    }
  });

  canvas.addEventListener("mousemove", (e) => {
    if (!currentBgSrc) return;
    const pos = getMousePos(e);

    if (!draggingKey) {
      const cnBB  = bboxForText(centreNameText, centreNameX, centreNameY, centreNameFontSize, centreNameFontFamily, "center");
      const recBB = bboxForText(recipientName, recipientX, recipientY, recipientFontSize, recipientFontFamily, "center");
      const dateBB= bboxForText(dateText, dateX, dateY, dateFontSize, dateFontFamily, "center");

      const hover =
        hitLogoZone(pos.x, pos.y) ||
        (cnBB  && pos.x >= cnBB.left  && pos.x <= cnBB.left  + cnBB.width  && pos.y >= cnBB.top  && pos.y <= cnBB.top  + cnBB.height) ||
        (recBB && pos.x >= recBB.left && pos.x <= recBB.left + recBB.width && pos.y >= recBB.top && pos.y <= recBB.top + recBB.height) ||
        (dateBB&& pos.x >= dateBB.left&& pos.x <= dateBB.left+ dateBB.width&& pos.y >= dateBB.top&& pos.y <= dateBB.top+ dateBB.height);

      canvas.style.cursor = hover ? "move" : "default";
      return;
    }

    if (draggingKey === "logo") {
      logoX = pos.x - dragOffsetX;
      logoY = pos.y - dragOffsetY;
      logoX = Math.max(logoRadius, Math.min(canvas.width - logoRadius, logoX));
      logoY = Math.max(logoRadius, Math.min(canvas.height - logoRadius, logoY));
    } else if (draggingKey === "centre_name") {
      centreNameX = pos.x - dragOffsetX;
      centreNameY = pos.y - dragOffsetY;
      centreNameX = Math.max(0, Math.min(canvas.width, centreNameX));
      centreNameY = Math.max(centreNameFontSize, Math.min(canvas.height, centreNameY));
    } else if (draggingKey === "recipient") {
      recipientX = pos.x - dragOffsetX;
      recipientY = pos.y - dragOffsetY;
      recipientX = Math.max(0, Math.min(canvas.width, recipientX));
      recipientY = Math.max(recipientFontSize, Math.min(canvas.height, recipientY));
    } else if (draggingKey === "date") {
      dateX = pos.x - dragOffsetX;
      dateY = pos.y - dragOffsetY;
      dateX = Math.max(0, Math.min(canvas.width, dateX));
      dateY = Math.max(dateFontSize, Math.min(canvas.height, dateY));
    }

    redraw();
  });

  function endDrag() {
    draggingKey = null;
    canvas.style.cursor = "default";
  }
  canvas.addEventListener("mouseup", endDrag);
  canvas.addEventListener("mouseleave", endDrag);

  categorySelect.addEventListener("change", renderGallery);

  recipientInput.addEventListener("input", () => {
    recipientName = recipientInput.value || "";
    if (bgImage.complete && bgImage.naturalWidth) redraw();
  });

  resetBtn.addEventListener("click", () => {
    recipientX = baseRecipientX; recipientY = baseRecipientY;
    dateX = baseDateX; dateY = baseDateY;
    logoX = baseLogoX; logoY = baseLogoY;
    centreNameX = baseCentreNameX; centreNameY = baseCentreNameY;
    redraw();
    setStatus("Reset to current saved defaults.", true);
  });

  // Save recipient
  saveRecipientBtn.addEventListener("click", async () => {
    if (!selectedTemplateId) return setStatus("Select a template first.", false);

    const xPct = pxToPct(recipientX, canvas.width);
    const yPct = pxToPct(recipientY, canvas.height);

    setStatus("Saving recipient…", true);

    try {
      const resp = await fetch(SAVE_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "save_recipient", template_id: selectedTemplateId, x: xPct, y: yPct })
      });

      const data = await resp.json();

      if (data.status === "success") {
        setStatus(`Recipient saved`, true);

        const idx = certCfgTemplates.findIndex(t => parseInt(t.template_id, 10) === selectedTemplateId);
        if (idx >= 0) {
          let obj = {};
          try { obj = certCfgTemplates[idx].layout_recipient ? JSON.parse(certCfgTemplates[idx].layout_recipient) : {}; } catch { obj = {}; }
          obj.x = data.layout.x;
          obj.y = data.layout.y;
          certCfgTemplates[idx].layout_recipient = JSON.stringify(obj);
          baseRecipientX = pctToPx(obj.x, canvas.width);
          baseRecipientY = pctToPx(obj.y, canvas.height);
        }
      } else {
        setStatus(data.message || "Recipient save failed", false);
      }
    } catch (e) {
      console.error(e);
      setStatus("Recipient save failed (network/server).", false);
    }
  });

  // Save date
  saveDateBtn.addEventListener("click", async () => {
    if (!selectedTemplateId) return setStatus("Select a template first.", false);

    const xPct = pxToPct(dateX, canvas.width);
    const yPct = pxToPct(dateY, canvas.height);

    setStatus("Saving date…", true);

    try {
      const resp = await fetch(SAVE_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "save_date", template_id: selectedTemplateId, x: xPct, y: yPct })
      });

      const data = await resp.json();

      if (data.status === "success") {
        setStatus(`Date saved`, true);

        const idx = certCfgTemplates.findIndex(t => parseInt(t.template_id, 10) === selectedTemplateId);
        if (idx >= 0) {
          let obj = {};
          try { obj = certCfgTemplates[idx].layout_admin ? JSON.parse(certCfgTemplates[idx].layout_admin) : {}; } catch { obj = {}; }
          obj.date = { x: data.layout.date.x, y: data.layout.date.y };
          certCfgTemplates[idx].layout_admin = JSON.stringify(obj);
          baseDateX = pctToPx(obj.date.x, canvas.width);
          baseDateY = pctToPx(obj.date.y, canvas.height);
        }
      } else {
        setStatus(data.message || "Date save failed", false);
      }
    } catch (e) {
      console.error(e);
      setStatus("Date save failed (network/server).", false);
    }
  });

  // Save logo
  saveLogoBtn.addEventListener("click", async () => {
    if (!selectedTemplateId) return setStatus("Select a template first.", false);

    const xPct = pxToPct(logoX, canvas.width);
    const yPct = pxToPct(logoY, canvas.height);

    setStatus("Saving logo…", true);

    try {
      const resp = await fetch(SAVE_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "save_logo", template_id: selectedTemplateId, x: xPct, y: yPct, d: logoDiameter })
      });

      const data = await resp.json();

      if (data.status === "success") {
        setStatus(`Logo saved`, true);

        const idx = certCfgTemplates.findIndex(t => parseInt(t.template_id, 10) === selectedTemplateId);
        if (idx >= 0) {
          let obj = {};
          try { obj = certCfgTemplates[idx].layout_admin ? JSON.parse(certCfgTemplates[idx].layout_admin) : {}; } catch { obj = {}; }
          obj.logo = { x: data.layout.logo.x, y: data.layout.logo.y, d: data.layout.logo.d };
          certCfgTemplates[idx].layout_admin = JSON.stringify(obj);
          baseLogoX = pctToPx(obj.logo.x, canvas.width);
          baseLogoY = pctToPx(obj.logo.y, canvas.height);
        }
      } else {
        setStatus(data.message || "Logo save failed", false);
      }
    } catch (e) {
      console.error(e);
      setStatus("Logo save failed (network/server).", false);
    }
  });

  // Save centre name position
  saveCentreNameBtn.addEventListener("click", async () => {
    if (!selectedTemplateId) return setStatus("Select a template first.", false);

    const xPct = pxToPct(centreNameX, canvas.width);
    const yPct = pxToPct(centreNameY, canvas.height);

    setStatus("Saving centre name…", true);

    try {
      const resp = await fetch(SAVE_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "save_rescue_name", template_id: selectedTemplateId, x: xPct, y: yPct })
      });

      const data = await resp.json();

      if (data.status === "success") {
        setStatus(`Centre name saved`, true);

        const idx = certCfgTemplates.findIndex(t => parseInt(t.template_id, 10) === selectedTemplateId);
        if (idx >= 0) {
          let obj = {};
          try { obj = certCfgTemplates[idx].layout_admin ? JSON.parse(certCfgTemplates[idx].layout_admin) : {}; } catch { obj = {}; }
          obj.rescue_name = { x: data.layout.rescue_name.x, y: data.layout.rescue_name.y };
          certCfgTemplates[idx].layout_admin = JSON.stringify(obj);
          baseCentreNameX = pctToPx(obj.rescue_name.x, canvas.width);
          baseCentreNameY = pctToPx(obj.rescue_name.y, canvas.height);
        }
      } else {
        setStatus(data.message || "Centre name save failed", false);
      }
    } catch (e) {
      console.error(e);
      setStatus("Centre name save failed (network/server).", false);
    }
  });

  renderGallery();
})();
</script>

