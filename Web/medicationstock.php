<?php
include 'dashmain.php'; // this is the include that has $pdo defined in it
include 'getcentreinfo.php';
?>

<?=template_admin_header(
    ($lang['MED_STOCK_TITLE'] ?? 'Medication Stock Management') . ' - ' . $rescue_name . ' - Rescue Centre - Rescue Management System',
    'medication',
    'stock'
)?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M128 176C128 149.5 149.5 128 176 128C202.5 128 224 149.5 224 176L224 288L128 288L128 176zM240 432C240 383.3 258.1 338.8 288 305L288 176C288 114.1 237.9 64 176 64C114.1 64 64 114.1 64 176L64 464C64 525.9 114.1 576 176 576C213.3 576 246.3 557.8 266.7 529.7C249.7 501.1 240 467.7 240 432zM304.7 499.4C309.3 508.1 321 509.1 328 502.1L502.1 328C509.1 321 508.1 309.3 499.4 304.7C479.3 294 456.4 288 432 288C352.5 288 288 352.5 288 432C288 456.3 294 479.3 304.7 499.4zM361.9 536C354.9 543 355.9 554.7 364.6 559.3C384.7 570 407.6 576 432 576C511.5 576 576 511.5 576 432C576 407.7 570 384.7 559.3 364.6C554.7 355.9 543 354.9 536 361.9L361.9 536z"/></svg>
        </div>
        <div class="txt">
            <h2 class="pagehead"><?= $lang['MED_STOCK_HEADING'] ?? 'Medication - Stock Management' ?></h2>
            <p><?= $lang['MED_STOCK_SUBTITLE'] ?? "Manage your centre's stock" ?></p>
        </div>
    </div>

    <div class="btns">
        <a href="views/print_meds_by_area.php?centre_id=<?= $centre_id ?>&auto_print=1"
           target="_blank"
           class="btn blue">
           🖨 <?= $lang['MEDS_PRINT_AREA_ROUNDS'] ?? 'Print Area Rounds' ?>
        </a>
        <a href="views/print_meds_by_time.php?centre_id=<?= $centre_id ?>&auto_print=1"
           target="_blank"
           class="btn orange">
           🖨 <?= $lang['MEDS_PRINT_TIME_ROUNDS'] ?? 'Print Time Rounds' ?>
        </a>
    </div>
</div>

<?php
$sub = $_GET['sub'] ?? 'list';
?>

<div class="rc-tabs rc-tabs-pill">
    <a href="medicationstock.php?sub=list" class="rc-tab <?= $sub === 'list' ? 'is-active' : '' ?>">
        <?= $lang['MED_STOCK_TAB_LIST'] ?? 'Medication in Stock' ?>
    </a>

    <a href="medicationstock.php?sub=add" class="rc-tab <?= $sub === 'add' ? 'is-active' : '' ?>">
        <?= $lang['MED_STOCK_ADD_TITLE'] ?? 'Add Medication to Stock' ?>
    </a>

    <a href="medicationstock.php?sub=profiles" class="rc-tab <?= $sub === 'profiles' ? 'is-active' : '' ?>">
        <?= $lang['MED_STOCK_TAB_PROFILES'] ?? 'Medication Profiles' ?>
    </a>

    <a href="medicationstock.php?sub=diet" class="rc-tab <?= $sub === 'diet' ? 'is-active' : '' ?>">
        <?= $lang['MED_STOCK_TAB_DIET'] ?? 'Diet Items' ?>
    </a>
</div>

<div class="rc-tab-panel is-active">
    <?php
    if ($sub === 'add') {
        include __DIR__ . '/views/medication/stock_add.php';

    } elseif ($sub === 'profiles') {
        include __DIR__ . '/views/medication/meds_profiles.php';

    } elseif ($sub === 'diet') {
        include __DIR__ . '/views/medication/diet_items.php';

    } else {
        include __DIR__ . '/views/medication/stock_list.php';
    }
    ?>
</div>

<?=template_admin_footer()?>
