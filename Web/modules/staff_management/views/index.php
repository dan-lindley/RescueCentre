<?php
// modules/staff_management/views/index.php

if (!defined('APP_LOADED')) {
    exit;
}

require_once __DIR__ . '/../controllers/staff_management_lib.php';

staff_management_ensure_schema($pdo);
staff_management_register_permissions();

$staffLang = staff_management_module_language();
$centre_id_int = (int)($centre_id ?? $_SESSION['centre_id'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));
$selectedPersonId = (int)($_GET['person_id'] ?? 0);

function staff_management_lang(string $key): string
{
    global $lang, $staffLang;
    return staff_management_h($staffLang[$key] ?? $lang[$key] ?? $key);
}

function staff_management_message(?string $key): string
{
    if (!$key || !str_starts_with($key, 'ADD_')) {
        return '';
    }
    return staff_management_lang($key);
}

if (!staff_management_can_access()) {
    echo '<div class="alert-box alert-red">' . staff_management_lang('ADD_ACCESS_DENIED') . '</div>';
    return;
}

$people = staff_management_fetch_people($pdo, $centre_id_int, $q);
$accounts = staff_management_fetch_accounts($pdo, $centre_id_int);
$roleOptions = staff_management_role_options();
$statusOptions = staff_management_status_options();
$selectedPerson = $selectedPersonId > 0 ? staff_management_fetch_person($pdo, $selectedPersonId, $centre_id_int) : null;
$personForm = $selectedPerson ?: [
    'id' => 0,
    'account_id' => '',
    'first_name' => '',
    'last_name' => '',
    'known_as' => '',
    'role_type' => 'volunteer',
    'status' => 'active',
    'email' => '',
    'telephone' => '',
    'address_line1' => '',
    'town' => '',
    'postcode' => '',
    'latitude' => '',
    'longitude' => '',
    'emergency_contact_name' => '',
    'emergency_contact_tel' => '',
    'notes' => '',
];

$msg = staff_management_message((string)($_GET['msg'] ?? ''));
$error = staff_management_message((string)($_GET['error'] ?? ''));
$action = 'modules/staff_management/controllers/staff_management_handler.php';
?>

<link rel="stylesheet" href="modules/staff_management/staff.css">

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2><?= staff_management_lang('ADD_STAFF_TITLE') ?></h2>
            <p><?= staff_management_lang('ADD_STAFF_SUBTITLE') ?></p>
        </div>
    </div>
</div>

<?php if ($msg !== ''): ?>
    <div class="alert-box alert-green"><?= $msg ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert-box alert-red"><?= $error ?></div>
<?php endif; ?>

<div class="content-block staff-management-toolbar">
    <form method="get" action="module.php" class="staff-management-search">
        <input type="hidden" name="module" value="staff_management">
        <input type="hidden" name="view" value="index">
        <div class="xform-field">
            <label class="xform-label" for="staff_management_q"><?= staff_management_lang('ADD_SEARCH_PEOPLE') ?></label>
            <input
                class="xform-input"
                type="search"
                id="staff_management_q"
                name="q"
                value="<?= staff_management_h($q) ?>"
                placeholder="<?= staff_management_lang('ADD_SEARCH_PEOPLE_PLACEHOLDER') ?>">
        </div>
        <div class="staff-management-actions">
            <button type="submit" class="btn green"><?= staff_management_lang('ADD_SEARCH') ?></button>
            <a class="btn grey" href="<?= staff_management_h(staff_management_view_url()) ?>"><?= staff_management_lang('ADD_CLEAR') ?></a>
            <a class="btn" href="<?= staff_management_h(staff_management_view_url()) ?>"><?= staff_management_lang('ADD_NEW_PERSON') ?></a>
        </div>
    </form>
</div>

<div class="content-block staff-management-shell">
    <div class="staff-management-layout">
        <div class="staff-management-list-pane">
            <div class="staff-management-panel-head">
                <h3><?= staff_management_lang('ADD_STAFF_DIRECTORY') ?></h3>
            </div>
            <?php if (!$people): ?>
                <p class="rc-muted"><?= staff_management_lang('ADD_NO_PEOPLE') ?></p>
            <?php else: ?>
                <table class="rc-table row-hover staff-management-table">
                    <thead>
                        <tr>
                            <th><?= staff_management_lang('ADD_NAME') ?></th>
                            <th><?= staff_management_lang('ADD_ROLE_TYPE') ?></th>
                            <th><?= staff_management_lang('ADD_STATUS') ?></th>
                            <th><?= staff_management_lang('ADD_ACCOUNT') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($people as $person): ?>
                            <?php
                                $displayName = trim((string)$person['first_name'] . ' ' . (string)$person['last_name']);
                                $knownAs = trim((string)($person['known_as'] ?? ''));
                                $accountLabel = trim((string)($person['username'] ?? ''));
                            ?>
                            <tr>
                                <td>
                                    <a class="staff-management-name" href="<?= staff_management_h(staff_management_view_url(['person_id' => (int)$person['id'], 'q' => $q])) ?>">
                                        <?= staff_management_h($displayName) ?>
                                    </a>
                                    <?php if ($knownAs !== ''): ?>
                                        <div class="staff-management-detail"><?= staff_management_h($knownAs) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($person['email']) || !empty($person['telephone'])): ?>
                                        <div class="staff-management-detail">
                                            <?= staff_management_h(trim((string)($person['email'] ?? '') . ' ' . (string)($person['telephone'] ?? ''))) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="rc-chip"><?= staff_management_lang($roleOptions[(string)$person['role_type']] ?? 'ADD_OTHER') ?></span></td>
                                <td><span class="rc-status <?= staff_management_h(staff_management_status_class((string)$person['status'])) ?>"><?= staff_management_lang($statusOptions[(string)$person['status']] ?? 'ADD_INACTIVE') ?></span></td>
                                <td><?= $accountLabel !== '' ? staff_management_h($accountLabel) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <div class="staff-management-detail-pane">
        <div class="staff-management-panel-head">
            <h3><?= $selectedPerson ? staff_management_lang('ADD_EDIT_PERSON') : staff_management_lang('ADD_NEW_PERSON') ?></h3>
        </div>

        <form method="post" action="<?= staff_management_h($action) ?>" class="xform-grid">
            <input type="hidden" name="action" value="save_person">
            <input type="hidden" name="id" value="<?= (int)$personForm['id'] ?>">

            <div class="xform-field">
                <label class="xform-label" for="first_name"><?= staff_management_lang('ADD_FIRST_NAME') ?></label>
                <input class="xform-input" type="text" id="first_name" name="first_name" value="<?= staff_management_h($personForm['first_name']) ?>" required>
            </div>

            <div class="xform-field">
                <label class="xform-label" for="last_name"><?= staff_management_lang('ADD_LAST_NAME') ?></label>
                <input class="xform-input" type="text" id="last_name" name="last_name" value="<?= staff_management_h($personForm['last_name']) ?>" required>
            </div>

            <div class="xform-field">
                <label class="xform-label" for="known_as"><?= staff_management_lang('ADD_KNOWN_AS') ?></label>
                <input class="xform-input" type="text" id="known_as" name="known_as" value="<?= staff_management_h($personForm['known_as']) ?>">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="account_id"><?= staff_management_lang('ADD_ACCOUNT') ?></label>
                <select class="xform-input" id="account_id" name="account_id">
                    <option value=""><?= staff_management_lang('ADD_SELECT_ACCOUNT') ?></option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= (int)$account['id'] ?>" <?= (int)$personForm['account_id'] === (int)$account['id'] ? 'selected' : '' ?>>
                            <?= staff_management_h(staff_management_account_label($account)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label" for="role_type"><?= staff_management_lang('ADD_ROLE_TYPE') ?></label>
                <select class="xform-input" id="role_type" name="role_type">
                    <?php foreach ($roleOptions as $value => $key): ?>
                        <option value="<?= staff_management_h($value) ?>" <?= (string)$personForm['role_type'] === $value ? 'selected' : '' ?>><?= staff_management_lang($key) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label" for="status"><?= staff_management_lang('ADD_STATUS') ?></label>
                <select class="xform-input" id="status" name="status">
                    <?php foreach ($statusOptions as $value => $key): ?>
                        <option value="<?= staff_management_h($value) ?>" <?= (string)$personForm['status'] === $value ? 'selected' : '' ?>><?= staff_management_lang($key) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label" for="email"><?= staff_management_lang('ADD_EMAIL') ?></label>
                <input class="xform-input" type="email" id="email" name="email" value="<?= staff_management_h($personForm['email']) ?>">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="telephone"><?= staff_management_lang('ADD_TELEPHONE') ?></label>
                <input class="xform-input" type="tel" id="telephone" name="telephone" value="<?= staff_management_h($personForm['telephone']) ?>">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="address_line1"><?= staff_management_lang('ADD_ADDRESS_LINE1') ?></label>
                <input class="xform-input" type="text" id="address_line1" name="address_line1" value="<?= staff_management_h($personForm['address_line1']) ?>">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="town"><?= staff_management_lang('ADD_TOWN') ?></label>
                <input class="xform-input" type="text" id="town" name="town" value="<?= staff_management_h($personForm['town']) ?>">
            </div>

            <div class="xform-field span-2">
                <label class="xform-label" for="postcode"><?= staff_management_lang('ADD_POSTCODE') ?></label>
                <div class="staff-management-lookup-row">
                    <input class="xform-input" type="text" id="postcode" name="postcode" value="<?= staff_management_h($personForm['postcode']) ?>">
                    <div class="staff-management-lookup-action">
                        <button type="button" class="btn blue" id="staff_location_lookup"><?= staff_management_lang('ADD_LOOKUP_LOCATION') ?></button>
                        <span class="rc-status active staff-management-found-pill" id="staff_location_found"><?= staff_management_lang('ADD_FOUND') ?></span>
                    </div>
                </div>
            </div>

            <div class="xform-field">
                <label class="xform-label" for="latitude"><?= staff_management_lang('ADD_LATITUDE') ?></label>
                <input class="xform-input" type="number" step="0.0000001" min="-90" max="90" id="latitude" name="latitude" value="<?= staff_management_h($personForm['latitude']) ?>">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="longitude"><?= staff_management_lang('ADD_LONGITUDE') ?></label>
                <input class="xform-input" type="number" step="0.0000001" min="-180" max="180" id="longitude" name="longitude" value="<?= staff_management_h($personForm['longitude']) ?>">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="emergency_contact_name"><?= staff_management_lang('ADD_EMERGENCY_CONTACT_NAME') ?></label>
                <input class="xform-input" type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?= staff_management_h($personForm['emergency_contact_name']) ?>">
            </div>

            <div class="xform-field">
                <label class="xform-label" for="emergency_contact_tel"><?= staff_management_lang('ADD_EMERGENCY_CONTACT_TEL') ?></label>
                <input class="xform-input" type="tel" id="emergency_contact_tel" name="emergency_contact_tel" value="<?= staff_management_h($personForm['emergency_contact_tel']) ?>">
            </div>

            <div class="xform-field span-2">
                <label class="xform-label" for="notes"><?= staff_management_lang('ADD_NOTES') ?></label>
                <textarea class="xform-input" id="notes" name="notes" rows="4"><?= staff_management_h($personForm['notes']) ?></textarea>
            </div>

            <div class="xform-button-row span-2">
                <button type="submit" class="btn green"><?= staff_management_lang('ADD_SAVE_PERSON') ?></button>
            </div>
        </form>

        <?php if ($selectedPerson): ?>
            <form method="post" action="<?= staff_management_h($action) ?>" class="xform-button-row">
                <input type="hidden" name="action" value="delete_person">
                <input type="hidden" name="id" value="<?= (int)$selectedPerson['id'] ?>">
                <button type="submit" class="btn red" onclick="return confirm('<?= staff_management_lang('ADD_DELETE_PERSON_CONFIRM') ?>');">
                    <?= staff_management_lang('ADD_DELETE_PERSON') ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
    </div>
</div>

<script>
(function() {
    const lookupButton = document.getElementById('staff_location_lookup');
    if (!lookupButton) return;

    const line1 = document.getElementById('address_line1');
    const town = document.getElementById('town');
    const postcode = document.getElementById('postcode');
    const latitude = document.getElementById('latitude');
    const longitude = document.getElementById('longitude');
    const foundPill = document.getElementById('staff_location_found');
    const messages = {
        enterAddress: <?= json_encode(staff_management_lang('ADD_ENTER_ADDRESS_FIRST')) ?>,
        looking: <?= json_encode(staff_management_lang('ADD_LOOKING_UP_LOCATION')) ?>,
        notFound: <?= json_encode(staff_management_lang('ADD_LOCATION_NOT_FOUND')) ?>,
        resolved: <?= json_encode(staff_management_lang('ADD_LOCATION_RESOLVED')) ?>,
        failed: <?= json_encode(staff_management_lang('ADD_LOCATION_LOOKUP_FAILED')) ?>
    };

    function setStatus(message) {
        lookupButton.title = message;
    }

    function setFound(visible) {
        if (foundPill) foundPill.style.display = visible ? 'inline-flex' : 'none';
    }

    setFound(Boolean(latitude && latitude.value && longitude && longitude.value));

    lookupButton.addEventListener('click', function() {
        const query = [line1 && line1.value, town && town.value, postcode && postcode.value]
            .map(value => (value || '').trim())
            .filter(Boolean)
            .join(', ');

        if (query.length < 3) {
            setStatus(messages.enterAddress);
            setFound(false);
            return;
        }

        lookupButton.disabled = true;
        setFound(false);
        setStatus(messages.looking);

        fetch('ajax/nominatim.php?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data) || !data.length) {
                    setStatus(messages.notFound);
                    setFound(false);
                    return;
                }

                const item = data[0];
                if (latitude) latitude.value = item.lat || '';
                if (longitude) longitude.value = item.lon || '';

                const address = item.address || {};
                if (postcode && address.postcode) postcode.value = address.postcode;
                setStatus(messages.resolved);
                setFound(true);
            })
            .catch(() => {
                setStatus(messages.failed);
                setFound(false);
            })
            .finally(() => {
                lookupButton.disabled = false;
            });
    });
})();
</script>
