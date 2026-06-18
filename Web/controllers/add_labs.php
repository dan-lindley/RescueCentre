<?php if (!isset($lab_dropdown_cache_loaded)) {

    // Load sample types once
    $lab_sample_types = $pdo->query("
        SELECT s_type_id, sample_type
        FROM rescue_sample_types
        ORDER BY sample_type ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

// Get the s_type_id for 'Other' (fallback bucket) - NEVER allow 0
$other_type_id = (int)($pdo->query("
    SELECT s_type_id
    FROM rescue_sample_types
    WHERE sample_type = 'Other'
    LIMIT 1
")->fetchColumn());

if ($other_type_id <= 0) {
    // fallback to an existing valid sample type id (first available)
    $other_type_id = (int)($pdo->query("
        SELECT MIN(s_type_id)
        FROM rescue_sample_types
    ")->fetchColumn());
}


    // Load lab tests once (force an effective sample type id for filtering)
    $stmt = $pdo->prepare("
        SELECT
            t.l_test_id,
            t.lab_test,
            t.lab_category,
            COALESCE(t.sample_type_id, :other_id) AS effective_sample_type_id
        FROM rescue_labs_tests t
        ORDER BY (t.lab_test = 'No test specified') DESC, t.lab_test ASC
    ");
    $stmt->execute([':other_id' => $other_type_id]);
    $lab_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lab_dropdown_cache_loaded = true;

} 


// CSRF TOKEN GENERATION
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

 ?>



<!-- ADD LAB RESULT FORM -->

<form action="controllers/form_handler.php" method="post" class="xform" id="addlabsform">

    <div class="xform-grid">

        <!-- DATE/TIME -->
        <div class="xform-field">
            <label class="xform-label" for="lab_date"><?= htmlspecialchars($lang['DATE'] . ' & ' . $lang['TIME']) ?></label>
            <input type="datetime-local" name="lab_date" id="lab_date" class="xform-input" required>
        </div>

       <!-- SAMPLE TYPE -->
<div class="xform-field">
    <label class="xform-label" for="sample_type"><?= htmlspecialchars($lang['SAMPLE_TYPE']) ?></label>
    <select name="sample_type" class="xform-input js-sample-type" required>
        <option value="" disabled selected><?= htmlspecialchars($lang['SELECT'] . ' ' . $lang['SAMPLE_TYPE']) ?></option>
        <?php foreach ($lab_sample_types as $row): ?>
            <option value="<?= (int)$row['s_type_id'] ?>"><?= htmlspecialchars($row['sample_type']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<!-- LAB TEST (Typeahead) -->
<div class="xform-field">
    <label class="xform-label"><?= htmlspecialchars($lang['LAB_TEST']) ?></label>

    <input
        type="text"
        class="xform-input js-labtest-input"
        placeholder="<?= htmlspecialchars($lang['LAB_SEARCH_PLACEHOLDER']) ?>"
        autocomplete="off"
        required
    >

    <!-- posts the SAME field name as before -->
    <input type="hidden" name="lab_test" class="js-labtest-id" value="">

    <!-- results dropdown -->
    <div class="labtypeahead js-labtest-menu" style="display:none;"></div>

    <small style="display:block; margin-top:6px; opacity:0.8;">
        <?= htmlspecialchars($lang['LAB_SEARCH_HELP']) ?>
    </small>
</div>

<!-- Hidden JSON data for this form instance (loop-safe) -->
<script type="application/json" class="js-labtest-data">
<?= json_encode(array_map(function($r){
    return [
        'id'    => (int)$r['l_test_id'],
        'label' => $r['lab_test'] . ' (' . $r['lab_category'] . ')',
        'sid'   => (int)($r['effective_sample_type_id'] ?? 0),
    ];
}, $lab_tests), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
</script>







        <!-- RESULT -->
        <div class="xform-field">
            <label class="xform-label" for="lab_result"><?= htmlspecialchars($lang['RESULT']) ?></label>
            <input type="text" placeholder="<?= htmlspecialchars($lang['RESULT'] . ' - eg 1.654 mg/L or +++') ?>" name="lab_result" id="lab_result" class="xform-input">
        </div>


   <!-- RESULT STATUS -->
<div class="xform-field">
    <label class="xform-label"><?= htmlspecialchars($lang['POSITIVE'] . ' ' . $lang['RESULT']) ?>?</label><p>

    <!-- Hidden default (unchecked = 0 / negative) -->
    <input type="hidden" name="is_positive" value="0">

    <label class="switch">
        <input type="checkbox" name="is_positive" value="1">
        <span class="slider"></span>
    </label>

        <span class="toggle-text"><?= htmlspecialchars($lang['POSITIVE']) ?></span>
</div>

        <!-- REPORTED BY (FULL WIDTH OR SPAN 2, UP TO YOU) -->
        <div class="xform-field">
            <label class="xform-label" for="reported_by"><?= htmlspecialchars($lang['REPORTED_BY']) ?></label>
            <input type="text" name="reported_by" id="reported_by" class="xform-input" value="<?php echo $record_name; ?>" readonly>
            <!--- URGENT TO SORT OUT required name to auto-populate-->
        </div>

        <!-- EMPTY COLS TO ALIGN GRID -->
        <div class="xform-field"></div>
        <div class="xform-field"></div>

    </div>

    <br>

    <!-- HIDDEN FIELDS -->
    <input type="hidden" name="centre_id" value="<?php echo $centre_id; ?>">
    <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
    <input type="hidden" name="admission_id" value="<?php echo $admission_id; ?>">
    <input type="hidden" name="audit_action" value="Lab Result added for CRN-<?= $patient_id ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <!-- SUBMIT -->
    <button type="submit" name="addlabsform" class="btn blue"><?= htmlspecialchars($lang['TIP_ADD_LAB_RESULTS'] . ' ' . $lang['FOR'] . ' ' . $patient_name) ?></button>

</form>
<!-- END ADD LAB RESULT FORM -->
<script>
(function () {
  function initLabTypeahead(form) {
    if (!form || form.dataset.labTypeaheadInit === '1') return;
    form.dataset.labTypeaheadInit = '1';

    const sampleSel = form.querySelector('.js-sample-type');
    const input     = form.querySelector('.js-labtest-input');
    const hiddenId  = form.querySelector('.js-labtest-id');
    const menu      = form.querySelector('.js-labtest-menu');
    const dataEl    = form.querySelector('.js-labtest-data');

    if (!sampleSel || !input || !hiddenId || !menu || !dataEl) return;

    // Position the dropdown correctly
    const fieldWrap = input.parentElement;
    if (fieldWrap && getComputedStyle(fieldWrap).position === 'static') {
      fieldWrap.style.position = 'relative';
    }

    let tests = [];
    try {
      tests = JSON.parse(dataEl.textContent || '[]');
    } catch (e) {
      tests = [];
    }

    let activeIndex = -1;
    let currentHits = [];

    function normalize(s) { return String(s || '').toLowerCase(); }

    function clearSelection() { hiddenId.value = ''; }

    function closeMenu() {
      menu.style.display = 'none';
      menu.innerHTML = '';
      activeIndex = -1;
      currentHits = [];
    }

    function openMenu() { menu.style.display = 'block'; }

    function render(hits) {
      menu.innerHTML = '';
      hits.forEach((t, idx) => {
        const div = document.createElement('div');
        div.className = 'item' + (idx === activeIndex ? ' active' : '');
        div.textContent = t.label;

        div.addEventListener('mousedown', function (e) {
          e.preventDefault();
          selectItem(idx);
        });

        menu.appendChild(div);
      });

      if (hits.length) openMenu(); else closeMenu();
    }

    function selectItem(idx) {
      const t = currentHits[idx];
      if (!t) return;

      input.value = t.label;
      hiddenId.value = String(t.id);

      if (t.sid) {
        sampleSel.value = String(t.sid);
        sampleSel.dispatchEvent(new Event('change', { bubbles: true }));
      }

      closeMenu();
    }

    function search(q) {
      const nq = normalize(q).trim();
      if (!nq) return [];

      const hits = tests.filter(t => normalize(t.label).includes(nq));

      hits.sort((a, b) => {
        const ai = normalize(a.label).indexOf(nq);
        const bi = normalize(b.label).indexOf(nq);
        if (ai !== bi) return ai - bi;
        return a.label.length - b.label.length;
      });

      return hits.slice(0, 12);
    }

    input.addEventListener('input', function () {
      clearSelection();
      activeIndex = -1;
      currentHits = search(this.value);
      render(currentHits);
    });

    input.addEventListener('keydown', function (e) {
      if (menu.style.display !== 'block') return;

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex = Math.min(activeIndex + 1, currentHits.length - 1);
        render(currentHits);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex = Math.max(activeIndex - 1, 0);
        render(currentHits);
      } else if (e.key === 'Enter') {
        if (activeIndex >= 0) {
          e.preventDefault();
          selectItem(activeIndex);
        }
      } else if (e.key === 'Escape') {
        closeMenu();
      }
    });

    input.addEventListener('blur', function () {
      setTimeout(closeMenu, 120);
    });

    form.addEventListener('submit', function (e) {
      if (!hiddenId.value) {
        e.preventDefault();
        input.focus();
        alert(<?= json_encode($lang['SELECT'] . ' ' . $lang['LAB_TEST']) ?>);
      }
    });
  }

  // Init all forms on the page (wrapper/loop safe)
  document.querySelectorAll('form').forEach(initLabTypeahead);
})();
</script>
