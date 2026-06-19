<!-- CARE NOTES SIDE PANEL -->
 <!-- Sidepanel variables can not be taken from PNP -> javascript/span classes only-->
<div id="careNotesPanel" class="side-panel">
  <div class="panel-header">
    <h2>Add Care Note</h2>
    <button class="close-btn" onclick="closeCareNotesPanel()">&times;</button>
  </div>

  <div class="panel-body">
    <b>Patient – <span class="admissionnameDisplay"></span></b>
    (CRN: <span class="admissionIDDisplay"></span>)

    <form id="careNotesForm" method="post" action="">
      <input type="hidden" name="patient_id" value="">
      <div class="form-group">
        <label>Note</label>
        <textarea name="new_note" rows="4" style="width:100%" required></textarea>
      </div>
      <div class="form-group">
        <label>Author</label>
        <input type="text" name="note_author" style="width:100%" required>
      </div>
      <div class="form-group">
        <label>Public</label>
        <select name="public" style="width:100%">
          <option value="0" selected>No</option>
          <option value="1">Yes</option>
        </select>
      </div>
      <div class="form-group">
        <label>Attach image (optional)</label>
        <div class="image-container"></div>
      </div>
      <button type="submit" name="carenotes" class="btn-primary">Save Note</button>
    </form>
  </div>
</div>
<style>
  .side-panel {
  position: fixed;
  top: 0;
  right: -420px; /* hidden off-screen */
  width: 400px;
  height: 100%;
  background: #fff;
  box-shadow: -3px 0 10px rgba(0,0,0,0.3);
  overflow-y: auto;
  z-index: 1000;
  transition: right 0.3s ease;
  border-left: 1px solid #ddd;
}

.side-panel.open {
  right: 0; /* slide in */
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8f9fa;
  padding: 10px 20px;
  border-bottom: 1px solid #ddd;
}

.panel-header h2 {
  font-size: 18px;
  margin: 0;
  color: #007bff;
}

.close-btn {
  font-size: 26px;
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
}

.close-btn:hover { color: #000; }

.panel-body { padding: 20px; }
</style>
<script>
const careNotesPanel = document.getElementById("careNotesPanel");
const imageEndpoint = "controllers/ajax_get_patient_images.php";

function openCareNotesPanel(button) {
  const patientId   = button.getAttribute("data-id") || "";
  const patientName = button.getAttribute("data-name") || "";

  careNotesPanel.classList.add("open");

  // Fill text and hidden field
  careNotesPanel.querySelector(".admissionnameDisplay").textContent = patientName;
  careNotesPanel.querySelector(".admissionIDDisplay").textContent   = patientId;
  careNotesPanel.querySelector('input[name="patient_id"]').value    = patientId;

  // Load images dynamically
  const container = careNotesPanel.querySelector(".image-container");
  container.innerHTML = "<p>Loading images...</p>";

  fetch(`${imageEndpoint}?patient_id=${patientId}`)
    .then(r => r.json())
    .then(data => {
      if (!Array.isArray(data) || data.length === 0) {
        container.innerHTML = '<p>No images available.</p>';
        return;
      }
      container.innerHTML = "";
      data.forEach(img => {
        container.insertAdjacentHTML("beforeend", `
          <label style="display:inline-block;text-align:center;margin:5px;">
            <input type="radio" name="image_id" value="${img.image_id}">
            <img src="/wp-content/themes/brikk-child/${img.image_url}" width="100px"><br>
            <small>${img.file_name}</small>
          </label>
        `);
      });
    })
    .catch(() => {
      container.innerHTML = "<p style='color:red;'>Error loading images</p>";
    });
}

function closeCareNotesPanel() {
  careNotesPanel.classList.remove("open");
}

// optional: close panel when clicking outside
window.addEventListener("click", (e) => {
  if (e.target === careNotesPanel) closeCareNotesPanel();
});
</script>
