<?php
$centre_id    = $centre_id    ?? 0;
$patient_id   = $patient_id   ?? 0;
$admission_id = $admission_id ?? 0;
$user_id      = $user_id      ?? 0;

/*
Controller must provide:
$backgrounds = SELECT bg_id, name, file_path FROM rescue_backgrounds WHERE active=1 ORDER BY name
$saved_diagrams = SELECT diag_id, background_used, diagram_png, label_data, created_at FROM rescue_diagrams WHERE patient_id=?
*/
?>

<div id="diagram-wrapper">

  <!-- Toolbar --><br>
  <div id="toolbar">
    <button id="drawModeBtn" class="btn green">Draw</button>
    <button id="labelModeBtn" class="btn green">Label</button>


    <select id="backgroundSelect" class="xform-input" style="width: 300px;">
      <?php foreach ($backgrounds as $bg): ?>
        <option value="<?= htmlspecialchars($bg['file_path']) ?>">
          <?= htmlspecialchars($bg['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select id="colorSelect" class="xform-input" style="width: 100px;">
      <option value="black">Black</option>
      <option value="red">Red</option>
      <option value="blue">Blue</option>
      <option value="green">Green</option>
    </select>

    <button id="saveBtn" class="btn blue">Save Diagram</button>
  </div>

  <!-- Saved Diagrams -->
  <?php if (!empty($saved_diagrams)): ?>
  <div id="saved-diagrams">
    <h4>Previous Diagrams</h4>
    <div class="diagram-list">
      <?php foreach ($saved_diagrams as $diag): ?>
        <div class="diagram-thumb-wrap">
          <img loading="lazy"
            src="<?= htmlspecialchars($diag['diagram_png']) ?>"
            class="diagram-thumb"
            data-diag='<?= json_encode($diag, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
          >
          <div class="diagram-date">
            <?= date("d M Y H:i", strtotime($diag["created_at"])) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Drawing Canvas -->
  <canvas id="drawCanvas" width="1200" height="720"></canvas>

</div>


<style>
#diagram-wrapper {
  max-width: 1600px;
  margin: 0 auto;
}

#toolbar {
  margin-bottom: 15px;
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

#saved-diagrams {
  margin-bottom: 15px;
}

#saved-diagrams h4 {
  margin-bottom: 8px;
}

.diagram-list {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.diagram-thumb-wrap {
  text-align: center;
}

.diagram-thumb {
  width: 180px;
  height: auto;
  border: 1px solid #bbb;
  border-radius: 4px;
  cursor: pointer;
  transition: 0.2s;
  background: #fafafa;
  image-rendering: auto;
}

.diagram-thumb:hover {
  transform: scale(1.05);
  border-color: #0a84ff;
}

.diagram-date {
  font-size: 0.85rem;
  margin-top: 4px;
  color: #666;
}

#drawCanvas {
  width: 100%;
  max-width: 1500px;
  height: auto;
  border: 2px solid #888;
  border-radius: 4px;
  background: #fff;
  cursor: crosshair;
  touch-action: none;
  box-shadow: 0 0 6px rgba(0,0,0,0.15);
}
</style>


<script>
const canvas = document.getElementById("drawCanvas");
const ctx = canvas.getContext("2d");

// Buttons
const drawBtn  = document.getElementById("drawModeBtn");
const labelBtn = document.getElementById("labelModeBtn");
const saveBtn  = document.getElementById("saveBtn");
const bgSelect = document.getElementById("backgroundSelect");
const colorSelect = document.getElementById("colorSelect");

// --- Added buttons ---
const undoBtn = document.createElement("button");
undoBtn.className = "btn orange";
undoBtn.textContent = "Undo";
document.getElementById("toolbar").appendChild(undoBtn);

const clearBtn = document.createElement("button");
clearBtn.className = "btn red";
clearBtn.textContent = "Clear";
document.getElementById("toolbar").appendChild(clearBtn);

const downloadBtn = document.createElement("button");
downloadBtn.className = "btn blue";
downloadBtn.textContent = "Download";
document.getElementById("toolbar").appendChild(downloadBtn);

let mode = "draw";
let drawing = false;
let labels = [];

let bgImage = new Image();
let currentBg = bgSelect.value;

// --- Undo snapshot ---
let undoImage = null;

// ---------------------------------------------------
// Load Background
// ---------------------------------------------------
function loadBackground(src) {
    if (bgImage.src !== src) {
        bgImage.onload = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(bgImage, 0, 0, canvas.width, canvas.height);
            labels.forEach(l => drawLabel(l));
        };
        bgImage.src = src;
    } else {
        // already loaded, just redraw
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(bgImage, 0, 0, canvas.width, canvas.height);
        labels.forEach(l => drawLabel(l));
    }
}


if (currentBg) loadBackground(currentBg);

// Background Change
bgSelect.addEventListener("change", () => {
  currentBg = bgSelect.value;
  loadBackground(currentBg);
});

// Mode
drawBtn.onclick  = () => mode = "draw";
labelBtn.onclick = () => mode = "label";

// Drawing
let lastX = 0;
let lastY = 0;
let penColor = "black";
let penSize = 4;

colorSelect.addEventListener("change", () => {
  penColor = colorSelect.value;
});

canvas.addEventListener("mousedown", e => {
  if (mode !== "draw") return;
  drawing = true;

  // --- Take undo snapshot BEFORE drawing ---
  undoImage = ctx.getImageData(0, 0, canvas.width, canvas.height);

  const pos = getMousePos(e);
  lastX = pos.x;
  lastY = pos.y;
});

canvas.addEventListener("mousemove", e => {
  if (!drawing || mode !== "draw") return;

  const pos = getMousePos(e);

  ctx.strokeStyle = penColor;
  ctx.lineWidth = penSize;
  ctx.lineCap = "round";
  ctx.lineJoin = "round";

  ctx.beginPath();
  ctx.moveTo(lastX, lastY);
  ctx.lineTo(pos.x, pos.y);
  ctx.stroke();

  lastX = pos.x;
  lastY = pos.y;
});

canvas.addEventListener("mouseup", () => drawing = false);
canvas.addEventListener("mouseleave", () => drawing = false);

// ---------------------------------------------------
// Label Mode
// ---------------------------------------------------
function drawLabel(l) {
  ctx.font = "20px Arial";
  ctx.fillStyle = "blue";
  ctx.fillText(l.text, l.x, l.y);
}

canvas.addEventListener("click", e => {
  if (mode !== "label") return;

  const pos = getMousePos(e);
  const text = prompt("Enter label:");
  if (!text) return;

  labels.push({ text, x: pos.x, y: pos.y });
  drawLabel({ text, x: pos.x, y: pos.y });
});

// Convert mouse event to canvas coords
function getMousePos(e) {
  const r = canvas.getBoundingClientRect();
  return {
    x: (e.clientX - r.left) * (canvas.width / r.width),
    y: (e.clientY - r.top)  * (canvas.height / r.height)
  };
}

// ---------------------------------------------------
// Undo (restore last snapshot)
// ---------------------------------------------------
undoBtn.onclick = () => {
  if (!undoImage) return;
  ctx.putImageData(undoImage, 0, 0);
};

// ---------------------------------------------------
// Clear Canvas (restore original background only)
// ---------------------------------------------------
clearBtn.onclick = () => {
  labels = [];
  loadBackground(currentBg);
};

// ---------------------------------------------------
// Download with center watermark
// ---------------------------------------------------
function drawWatermark() {
  ctx.save();
  ctx.globalAlpha = 0.2;
  ctx.fillStyle = "black";
  ctx.textAlign = "center";
  ctx.textBaseline = "middle";
  ctx.font = "50px Arial";

  ctx.fillText("Rescue Centre- Wildlife Casualty Diagram", canvas.width / 2, canvas.height / 2);
  ctx.restore();
}

downloadBtn.onclick = () => {
  // Draw watermark temporarily
  drawWatermark();

  const link = document.createElement("a");
  link.download = "diagram.png";
  link.href = canvas.toDataURL("image/png");
  link.click();

  // Remove watermark by reloading canvas state
  loadBackground(currentBg);
  labels.forEach(drawLabel);
};

// ---------------------------------------------------
// Load Saved Diagram
// ---------------------------------------------------
document.querySelectorAll(".diagram-thumb").forEach(img => {
  img.addEventListener("click", () => {
    const diag = JSON.parse(img.dataset.diag);

    labels = [];

    const saved = new Image();
    saved.src = diag.diagram_png;

    saved.onload = () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(saved, 0, 0, canvas.width, canvas.height);

      if (diag.label_data) {
        try {
          labels = JSON.parse(diag.label_data);
          labels.forEach(drawLabel);
        } catch (e) {}
      }
    };

    currentBg = diag.background_used;
    bgSelect.value = currentBg;
  });
});

// ---------------------------------------------------
// Save Diagram
// ---------------------------------------------------
saveBtn.onclick = () => {
  const payload = {
    centre_id: <?= (int)$centre_id ?>,
    patient_id: <?= (int)$patient_id ?>,
    user_id: <?= (int)$user_id ?>,

    background_used: currentBg,
    diagram_png: canvas.toDataURL("image/png"),
    label_data: labels,

    canvas_width: 1500,
    canvas_height: 900
  };

  fetch("/controllers/save_rescue_diagram.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === "success") {
      alert("Diagram saved. ID: " + data.diag_id);
      location.reload();
    } else {
      alert("Save failed.");
    }
  })
  .catch(err => alert("Error: " + err));
};
</script>

