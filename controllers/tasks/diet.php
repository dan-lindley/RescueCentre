<?php
/**
 * controllers/tasks/diet.php
 * Diet / Feeding setup task (POST-driven).
 *
 * - Simple list of linked items with (remove) text link
 * - Display shelf life/use_within only (no editing)
 * - Predictive search via /controllers/tasks/diet_suggest.php
 * - Shows a small status line if suggestions fail (no devtools needed)
 * - No global functions (prevents redeclare fatals)
 */

if (!isset($pdo) || !($pdo instanceof PDO) || !isset($centre_id) || $centre_id === '') {
    echo '<div class="alert-box alert-red" style="margin:0;"><strong>Diet / Feeding</strong><br>Context missing.</div>';
    return;
}
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

$centre_id = (string)$centre_id;

$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$default_use_within = function ($category) {
    $c = strtolower(trim((string)$category));
    return ($c === 'liquid') ? 730 : 365;
};

/* ---------- CSRF ---------- */
if (!isset($_SESSION['csrf_tokens'])) $_SESSION['csrf_tokens'] = [];
$csrf_form_key = 'setup_diet';
if (empty($_SESSION['csrf_tokens'][$csrf_form_key])) {
    $_SESSION['csrf_tokens'][$csrf_form_key] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_tokens'][$csrf_form_key];

/* ---------- state ---------- */
$step = isset($_POST['step']) ? max(1, (int)$_POST['step']) : 1;
$flash = null;

/* ---------- actions (add/remove/mark_done only) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rc_diet_action'])) {
    $action = (string)$_POST['rc_diet_action'];

    $mutating = in_array($action, ['add_item','remove_item'], true);
    if ($mutating) {
        $posted_form  = (string)($_POST['csrf_form'] ?? '');
        $posted_token = (string)($_POST['csrf_token'] ?? '');
        if ($posted_form !== $csrf_form_key || !hash_equals($csrf_token, $posted_token)) {
            $flash = ['type'=>'red','msg'=>'Security check failed. Please refresh and try again.'];
        }
    }

    if (!$flash && $action === 'mark_done') {
        $step = 2;
    }

    if (!$flash && $action === 'add_item') {
        $diet_item_id = (int)($_POST['diet_item_id'] ?? 0);
        if ($diet_item_id <= 0) {
            $flash = ['type'=>'red','msg'=>'Please select an item first.'];
        } else {
            $stmt = $pdo->prepare("SELECT 1 FROM rescue_centre_diet_items WHERE centre_id = ? AND diet_item_id = ? LIMIT 1");
            $stmt->execute([$centre_id, $diet_item_id]);
            if ($stmt->fetchColumn()) {
                $flash = ['type'=>'green','msg'=>'Already added.'];
            } else {
                $stmt = $pdo->prepare("SELECT category FROM rescue_diet_items WHERE diet_item_id = ? LIMIT 1");
                $stmt->execute([$diet_item_id]);
                $category = (string)$stmt->fetchColumn();
                $use_within = (int)$default_use_within($category);

                $stmt = $pdo->prepare("
                    INSERT INTO rescue_centre_diet_items
                        (centre_id, diet_item_id, use_within_days, is_enabled, notes)
                    VALUES
                        (?, ?, ?, 1, '')
                ");
                if ($stmt->execute([$centre_id, $diet_item_id, $use_within])) {
                    $flash = ['type'=>'green','msg'=>'Item added.'];
                } else {
                    $flash = ['type'=>'red','msg'=>'Could not add item.'];
                }
            }
        }
    }

    if (!$flash && $action === 'remove_item') {
        $centre_diet_item_id = (int)($_POST['centre_diet_item_id'] ?? 0);
        if ($centre_diet_item_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM rescue_centre_diet_items WHERE centre_id = ? AND centre_diet_item_id = ? LIMIT 1");
            $ok = $stmt->execute([$centre_id, $centre_diet_item_id]);
            $flash = $ok ? ['type'=>'green','msg'=>'Item removed.'] : ['type'=>'red','msg'=>'Could not remove item.'];
        }
    }
}

/* ---------- load centre items ---------- */
$centreItemsStmt = $pdo->prepare("
    SELECT
        cdi.centre_diet_item_id,
        cdi.diet_item_id,
        cdi.use_within_days,
        cdi.is_enabled,
        di.name,
        di.category,
        di.default_unit
    FROM rescue_centre_diet_items cdi
    INNER JOIN rescue_diet_items di ON di.diet_item_id = cdi.diet_item_id
    WHERE cdi.centre_id = ?
    ORDER BY di.name ASC
");
$centreItemsStmt->execute([$centre_id]);
$centreItems = $centreItemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* ---------- render ---------- */
echo '<style>
.card.rc-diet-wizard{padding:10px;}
.rc-head{display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;}
.rc-title{font-weight:900;}
.rc-sub{font-size:13px; opacity:.8; font-weight:800;}
hr.soft{border:none; border-top:1px solid rgba(0,0,0,.10); margin:10px 0;}
.small{font-size:12px; opacity:.85;}
ul.tight{margin:0; padding-left:18px;}
ul.tight li{margin:2px 0; line-height:1.25;}
.remove-btn{background:none; border:none; padding:0; margin:0 0 0 8px; color:#c00; cursor:pointer; font-weight:400; font-size:12px;}
.remove-btn:hover{text-decoration:underline;}
.suggest{position:relative; max-width:560px;}
.dd{position:absolute; top:100%; left:0; right:0; z-index:50; background:#fff; border:1px solid rgba(0,0,0,.12);
    border-radius:10px; margin-top:6px; box-shadow:0 6px 20px rgba(0,0,0,.08); overflow:hidden; display:none;}
.dd .row{padding:8px 10px; cursor:pointer; border-top:1px solid rgba(0,0,0,.06);}
.dd .row:first-child{border-top:none;}
.dd .row:hover{background:#f6f6f6;}
.dd .name{font-weight:900;}
.dd .meta{font-size:12px; opacity:.8; margin-top:2px; display:flex; gap:8px; flex-wrap:wrap;}
.pill{display:flex; justify-content:space-between; gap:10px; padding:8px 10px; border:1px solid rgba(0,0,0,.10); border-radius:12px; background:#fff; margin-top:8px; flex-wrap:wrap;}
.badge{display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; background:#f2f2f2; color:#333;}
.badge.green{background:#e8f7ee; color:#1b6b35;}
.status{margin-top:6px; font-size:12px; color:#c00; display:none;}
</style>';

echo '<div class="card rc-diet-wizard">';
echo '  <div class="rc-head">';

echo '    <div class="rc-title">Step '.(int)$step.' of 2</div>';
echo '  </div>';

if ($flash) {
    $cls = ($flash['type'] ?? '') === 'green' ? 'alert-green' : 'alert-red';
    echo '<div class="alert-box '.$cls.'" style="margin:8px 0 0 0;">'.$h((string)$flash['msg']).'</div>';
}

if ($step === 2) {
    echo '<div style="height:8px;"></div>';
    echo '<div style="font-weight:900; margin-bottom:6px;">All set 🎉</div>';
    echo '<div class="small" style="margin-bottom:10px;">You can manage diet items later in your diet/stock area.</div>';
    echo '<div style="display:flex; gap:8px; flex-wrap:wrap;">';
    echo '<form method="post" style="margin:0;"><input type="hidden" name="step" value="1"><button class="btn btn-secondary" type="submit">Run again</button></form>';
    echo '<a class="btn" href="?">Close</a>';
    echo '</div>';
    echo '</div>';
    return;
}

echo '<hr class="soft">';

/* Already added */
echo '<div style="font-weight:900; margin-bottom:6px;">Already added</div>';

if (empty($centreItems)) {
    echo '<div class="alert-box alert-blue" style="margin:0;">No items added yet.</div>';
} else {
    echo '<ul class="tight">';
    foreach ($centreItems as $row) {
        $cid = (int)$row['centre_diet_item_id'];
        $uwd = $row['use_within_days'];
        $uwdVal = ($uwd === null || $uwd === '') ? (int)$default_use_within((string)$row['category']) : (int)$uwd;

        echo '<li>';
        echo $h($row['name']).' <span class="small">('.$h($row['default_unit']).', '.$h($row['category']).', '.$uwdVal.' days)</span>';

        echo '<form method="post" style="display:inline; margin:0;" onsubmit="return confirm(\'Remove this item?\');">';
        echo '<input type="hidden" name="rc_diet_action" value="remove_item">';
        echo '<input type="hidden" name="centre_diet_item_id" value="'.$cid.'">';
        echo '<input type="hidden" name="csrf_form" value="'.$h($csrf_form_key).'">';
        echo '<input type="hidden" name="csrf_token" value="'.$h($csrf_token).'">';
        echo '<button type="submit" class="remove-btn">(remove)</button>';
        echo '</form>';

        echo '</li>';
    }
    echo '</ul>';
}

echo '<hr class="soft">';

/* Search + suggestions */
echo '<div style="font-weight:900; margin-bottom:6px;">Search and add</div>';
echo '<div class="small" style="margin-bottom:8px;">Type 2+ characters. Click a suggestion, then add it.</div>';

echo '<div class="suggest">';
echo '  <label>Diet item name</label>';
echo '  <input id="dietSearch" class="form-control" type="text" autocomplete="off" placeholder="e.g. mealworm, kitten milk, fish...">';
echo '  <div id="dietDD" class="dd"></div>';
echo '  <div id="dietStatus" class="status"></div>';
echo '</div>';

echo '<div id="selectedBox" style="display:none;" class="pill">';
echo '  <div>';
echo '    <div id="selName" style="font-weight:900;"></div>';
echo '    <div id="selMeta" class="small" style="margin-top:2px;"></div>';
echo '  </div>';
echo '  <div id="selAction"></div>';
echo '</div>';

echo '<div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">';
echo '<form method="post" style="margin:0;">';
echo '<input type="hidden" name="rc_diet_action" value="mark_done">';
echo '<button class="btn" type="submit">Mark completed</button>';
echo '</form>';
echo '</div>';

echo '</div>'; // card end

?>
<script>
(() => {
  const input = document.getElementById('dietSearch');
  const dd = document.getElementById('dietDD');
  const status = document.getElementById('dietStatus');
  const selectedBox = document.getElementById('selectedBox');
  const selName = document.getElementById('selName');
  const selMeta = document.getElementById('selMeta');
  const selAction = document.getElementById('selAction');

  const endpoint = '/controllers/tasks/diet_suggest.php';
  const centreId = <?= json_encode((string)$centre_id) ?>;

  let timer = null;
  let last = '';

  function esc(s){
    return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
      .replaceAll('"','&quot;').replaceAll("'","&#039;");
  }
  function hideDD(){ dd.style.display='none'; dd.innerHTML=''; }
  function showStatus(msg){
    status.textContent = msg;
    status.style.display = msg ? 'block' : 'none';
  }

  async function fetchSuggest(term){
    const body = new URLSearchParams();
    body.set('term', term);
    body.set('centre_id', centreId);

    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString()
    });

    if (!res.ok) {
      const t = await res.text().catch(()=> '');
      throw new Error(`Suggest failed (${res.status}). ${t ? t.slice(0,120) : ''}`);
    }

    const data = await res.json();
    if (data && data.error) throw new Error(data.error);
    return data;
  }

  function showDD(items){
    if (!items || !items.length) { hideDD(); return; }
    dd.innerHTML = items.map(it => {
      const meta = [it.type, it.category, it.default_unit].filter(Boolean).join(' · ');
      const tag = it.already_added ? '<span class="badge green">Already added</span>' : '';
      return `
        <div class="row" data-id="${it.diet_item_id}"
             data-name="${esc(it.name)}"
             data-type="${esc(it.type||'')}"
             data-category="${esc(it.category||'')}"
             data-unit="${esc(it.default_unit||'')}"
             data-notes="${esc(it.notes||'')}"
             data-added="${it.already_added ? 1 : 0}">
          <div class="name">${esc(it.name)} ${tag}</div>
          <div class="meta">${esc(meta)}</div>
        </div>
      `;
    }).join('');
    dd.style.display = 'block';
  }

  function setSelected(it){
    selectedBox.style.display = '';
    selName.textContent = it.name;
    const meta = [it.type, it.category, it.default_unit].filter(Boolean).join(' · ');
    selMeta.textContent = meta + (it.notes ? (' — ' + it.notes) : '');

    if (it.already_added) {
      selAction.innerHTML = `<span class="badge green">Already added</span>`;
      return;
    }

    selAction.innerHTML = `
      <form method="post" style="margin:0;">
        <input type="hidden" name="rc_diet_action" value="add_item">
        <input type="hidden" name="diet_item_id" value="${it.diet_item_id}">
        <input type="hidden" name="csrf_form" value="<?= $h($csrf_form_key) ?>">
        <input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
        <button class="btn blue" type="submit">+ Add</button>
      </form>
    `;
  }

  input.addEventListener('input', () => {
    const term = input.value.trim();
    selectedBox.style.display = 'none';
    showStatus('');

    if (term.length < 2) { hideDD(); return; }

    if (timer) clearTimeout(timer);
    timer = setTimeout(async () => {
      if (term === last) return;
      last = term;
      try {
        const items = await fetchSuggest(term);
        showDD(items);
      } catch (e) {
        hideDD();
        showStatus(`Search suggestions not loading: ${e.message}`);
      }
    }, 150);
  });

  dd.addEventListener('click', (e) => {
    const row = e.target.closest('.row');
    if (!row) return;

    const it = {
      diet_item_id: parseInt(row.getAttribute('data-id'), 10),
      name: row.getAttribute('data-name') || '',
      type: row.getAttribute('data-type') || '',
      category: row.getAttribute('data-category') || '',
      default_unit: row.getAttribute('data-unit') || '',
      notes: row.getAttribute('data-notes') || '',
      already_added: row.getAttribute('data-added') === '1'
    };

    input.value = it.name;
    hideDD();
    setSelected(it);
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.suggest')) hideDD();
  });

})();
</script>