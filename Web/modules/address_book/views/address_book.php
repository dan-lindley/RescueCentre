<?php
// modules/address_book/views/address_book.php

if (!defined('APP_LOADED')) {
    exit;
}

require_once __DIR__ . '/../controllers/address_book_lib.php';

address_book_ensure_schema($pdo);

$addressBookLang = address_book_module_language();
$addressBookAction = 'modules/address_book/controllers/address_book_handler.php';
$centre_id_int = (int)($centre_id ?? $_SESSION['centre_id'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));
$selectedFinderId = (int)($_GET['finder_id'] ?? 0);
$finders = address_book_fetch_finders($pdo, $centre_id_int, $q);
$selectedFinder = $selectedFinderId > 0 ? address_book_fetch_finder($pdo, $selectedFinderId, $centre_id_int) : null;
$finderAdmissions = $selectedFinder ? address_book_fetch_admissions($pdo, (int)$selectedFinder['finder_id'], $centre_id_int) : [];

function address_book_lang(string $key): string
{
    global $lang, $addressBookLang;
    return address_book_h($addressBookLang[$key] ?? $lang[$key] ?? $key);
}

function address_book_message(?string $key): string
{
    if (!$key || !str_starts_with($key, 'ADD_')) {
        return '';
    }
    return address_book_lang($key);
}

$msg = address_book_message((string)($_GET['msg'] ?? ''));
$error = address_book_message((string)($_GET['error'] ?? ''));
$finderForm = $selectedFinder ?: [
    'finder_id' => 0,
    'finder_name' => '',
    'finder_tel' => '',
    'finder_email' => '',
    'finder_address_line1' => '',
    'finder_town' => '',
    'finder_postcode' => '',
    'preferred_contact_method' => '',
    'has_donated' => 0,
    'gift_aid_consent' => 0,
];
?>

<link rel="stylesheet" href="modules/address_book/address.css">

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2><?= address_book_lang('ADD_ADDRESS_BOOK_TITLE') ?></h2>
            <p><?= address_book_lang('ADD_ADDRESS_BOOK_SUBTITLE') ?></p>
        </div>
    </div>
</div>

<?php if ($msg !== ''): ?>
    <div class="alert-box alert-green"><?= $msg ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert-box alert-red"><?= $error ?></div>
<?php endif; ?>

<div class="content-block address-book-toolbar">
    <form method="get" action="module.php" class="address-book-search">
        <input type="hidden" name="module" value="address_book">
        <input type="hidden" name="view" value="address_book">
        <div class="xform-field">
            <label class="xform-label" for="address_book_q"><?= address_book_lang('ADD_SEARCH_FINDERS') ?></label>
            <input
                class="xform-input"
                type="search"
                id="address_book_q"
                name="q"
                value="<?= address_book_h($q) ?>"
                placeholder="<?= address_book_lang('ADD_SEARCH_FINDERS_PLACEHOLDER') ?>">
        </div>
        <div class="address-book-actions">
            <button type="submit" class="btn green"><?= address_book_lang('ADD_SEARCH') ?></button>
            <a class="btn grey" href="<?= address_book_h(address_book_view_url()) ?>"><?= address_book_lang('ADD_CLEAR') ?></a>
            <a class="btn" href="<?= address_book_h(address_book_view_url()) ?>"><?= address_book_lang('ADD_NEW_FINDER') ?></a>
        </div>
    </form>
</div>

<div class="content-block address-book-shell">
    <div class="address-book-layout">
        <div class="address-book-list-pane">
            <div class="address-book-panel-head">
                <h3><?= address_book_lang('ADD_FINDERS') ?></h3>
            </div>
            <?php if (!$finders): ?>
                <p class="rc-muted"><?= address_book_lang('ADD_NO_FINDERS') ?></p>
            <?php else: ?>
                <table class="rc-table row-hover address-book-table">
                    <thead>
                        <tr>
                            <th><?= address_book_lang('ADD_FINDER_NAME') ?></th>
                            <th><?= address_book_lang('ADD_FINDER_TEL') ?></th>
                            <th><?= address_book_lang('ADD_CREATED_ON') ?></th>
                            <th><?= address_book_lang('ADD_DURATION') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($finders as $finder): ?>
                            <tr>
                                <td>
                                    <a href="<?= address_book_h(address_book_view_url(['finder_id' => (int)$finder['finder_id'], 'q' => $q])) ?>">
                                        <?= address_book_h($finder['finder_name']) ?>
                                    </a>
                                </td>
                                <td><?= address_book_h($finder['finder_tel'] ?? '') ?></td>
                                <td><?= address_book_h(address_book_short_date($finder['created_at'] ?? '')) ?></td>
                                <td><?= address_book_h(address_book_short_duration($finder['created_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="address-book-detail-pane">
            <div class="address-book-panel-head">
                <h3><?= $selectedFinder ? address_book_lang('ADD_EDIT_FINDER') : address_book_lang('ADD_NEW_FINDER') ?></h3>
            </div>
            <form method="post" action="<?= address_book_h($addressBookAction) ?>" class="xform-grid">
                <input type="hidden" name="action" value="save_finder">
                <input type="hidden" name="finder_id" value="<?= (int)$finderForm['finder_id'] ?>">

                <div class="xform-field span-2">
                    <label class="xform-label" for="finder_name"><?= address_book_lang('ADD_FINDER_NAME') ?></label>
                    <input class="xform-input" type="text" id="finder_name" name="finder_name" value="<?= address_book_h($finderForm['finder_name']) ?>" required>
                </div>

                <div class="xform-field">
                    <label class="xform-label" for="finder_tel"><?= address_book_lang('ADD_FINDER_TEL') ?></label>
                    <input class="xform-input" type="tel" id="finder_tel" name="finder_tel" value="<?= address_book_h($finderForm['finder_tel']) ?>">
                </div>

                <div class="xform-field">
                    <label class="xform-label" for="finder_email"><?= address_book_lang('ADD_FINDER_EMAIL') ?></label>
                    <input class="xform-input" type="email" id="finder_email" name="finder_email" value="<?= address_book_h($finderForm['finder_email']) ?>">
                </div>

                <div class="xform-field">
                    <label class="xform-label" for="finder_address_line1"><?= address_book_lang('ADD_FINDER_ADDRESS') ?></label>
                    <input class="xform-input" type="text" id="finder_address_line1" name="finder_address_line1" value="<?= address_book_h($finderForm['finder_address_line1']) ?>">
                </div>

                <div class="xform-field">
                    <label class="xform-label" for="finder_town"><?= address_book_lang('ADD_FINDER_TOWN') ?></label>
                    <input class="xform-input" type="text" id="finder_town" name="finder_town" value="<?= address_book_h($finderForm['finder_town']) ?>">
                </div>

                <div class="xform-field">
                    <label class="xform-label" for="finder_postcode"><?= address_book_lang('ADD_FINDER_POSTCODE') ?></label>
                    <input class="xform-input" type="text" id="finder_postcode" name="finder_postcode" value="<?= address_book_h($finderForm['finder_postcode']) ?>">
                </div>

                <div class="xform-field">
                    <label class="xform-label" for="preferred_contact_method"><?= address_book_lang('ADD_PREFERRED_CONTACT_METHOD') ?></label>
                    <select class="xform-input" id="preferred_contact_method" name="preferred_contact_method">
                        <?php foreach (['' => 'ADD_SELECT_CONTACT_METHOD', 'telephone' => 'ADD_CONTACT_TELEPHONE', 'sms' => 'ADD_CONTACT_SMS', 'email' => 'ADD_CONTACT_EMAIL', 'none' => 'ADD_CONTACT_NONE'] as $value => $key): ?>
                            <option value="<?= address_book_h($value) ?>" <?= (string)$finderForm['preferred_contact_method'] === (string)$value ? 'selected' : '' ?>>
                                <?= address_book_lang($key) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="xform-field">
                    <label class="xform-label">
                        <input type="checkbox" name="has_donated" value="1" <?= !empty($finderForm['has_donated']) ? 'checked' : '' ?>>
                        <?= address_book_lang('ADD_HAS_DONATED') ?>
                    </label>
                </div>

                <div class="xform-field">
                    <label class="xform-label">
                        <input type="checkbox" name="gift_aid_consent" value="1" <?= !empty($finderForm['gift_aid_consent']) ? 'checked' : '' ?>>
                        <?= address_book_lang('ADD_GIFT_AID_CONSENT') ?>
                    </label>
                </div>

                <div class="xform-button-row span-2">
                    <button type="submit" class="btn green"><?= address_book_lang('ADD_SAVE_FINDER') ?></button>
                </div>
            </form>

            <?php if ($selectedFinder): ?>
                <form method="post" action="<?= address_book_h($addressBookAction) ?>" class="xform-button-row">
                    <input type="hidden" name="action" value="delete_finder">
                    <input type="hidden" name="finder_id" value="<?= (int)$selectedFinder['finder_id'] ?>">
                    <button type="submit" class="btn red" onclick="return confirm('<?= address_book_lang('ADD_DELETE_FINDER_CONFIRM') ?>');">
                        <?= address_book_lang('ADD_DELETE_FINDER') ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($selectedFinder): ?>
    <div class="content-block address-book-admissions">
        <h3><?= address_book_lang('ADD_ADMISSIONS') ?></h3>
        <form method="post" action="<?= address_book_h($addressBookAction) ?>" class="address-book-inline-form">
            <input type="hidden" name="action" value="link_existing_admissions">
            <input type="hidden" name="finder_id" value="<?= (int)$selectedFinder['finder_id'] ?>">
            <button type="submit" class="btn grey"><?= address_book_lang('ADD_LINK_EXISTING_ADMISSIONS') ?></button>
        </form>
        <form method="post" action="<?= address_book_h($addressBookAction) ?>" class="xform-grid">
            <input type="hidden" name="action" value="link_admission">
            <input type="hidden" name="finder_id" value="<?= (int)$selectedFinder['finder_id'] ?>">
            <div class="xform-field">
                <label class="xform-label" for="admission_id"><?= address_book_lang('ADD_ADMISSION_ID') ?></label>
                <input class="xform-input" type="number" min="1" id="admission_id" name="admission_id" required>
            </div>
            <div class="xform-button-row">
                <button type="submit" class="btn green"><?= address_book_lang('ADD_LINK_ADMISSION') ?></button>
            </div>
        </form>

        <?php if (!$finderAdmissions): ?>
            <p class="rc-muted"><?= address_book_lang('ADD_NO_ADMISSIONS') ?></p>
        <?php else: ?>
            <table class="rc-table row-hover address-book-table">
                <thead>
                    <tr>
                        <th><?= address_book_lang('ADD_ADMISSION_ID') ?></th>
                        <th><?= address_book_lang('ADD_PATIENT') ?></th>
                        <th><?= address_book_lang('ADD_SPECIES') ?></th>
                        <th><?= address_book_lang('ADD_ADMISSION_DATE') ?></th>
                        <th><?= address_book_lang('ADD_CREATED_AT') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($finderAdmissions as $admission): ?>
                        <tr>
                            <td><?= (int)$admission['admission_id'] ?></td>
                            <td><?= address_book_h($admission['name'] ?? '') ?></td>
                            <td><?= address_book_h($admission['animal_species'] ?? '') ?></td>
                            <td><?= address_book_h($admission['admission_date'] ?? '') ?></td>
                            <td><?= address_book_h($admission['created_at'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>
