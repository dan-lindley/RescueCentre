<?php
if (!defined('APP_LOADED')) exit;

if (!empty($messagingSchemaError)): ?>
    <div class="content-title">
        <div class="title">
            <div class="txt">
                <h2><?= messaging_h(messaging_text('MSG_TITLE')) ?></h2>
                <p><?= messaging_h(messaging_text('MSG_SUBTITLE')) ?></p>
            </div>
        </div>
    </div>
    <div class="rc-alert red">
        <?= messaging_h(messaging_text('MSG_SCHEMA_MISSING')) ?>
        <strong><?= messaging_h($messagingSchemaError) ?></strong>
    </div>
    <?php return;
endif;
?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="30" height="30" viewBox="0 0 640 640" aria-hidden="true"><path d="M112 128C85.5 128 64 149.5 64 176C64 191.1 71.1 205.3 83.2 214.4L291.2 370.4C308.3 383.2 331.7 383.2 348.8 370.4L556.8 214.4C568.9 205.3 576 191.1 576 176C576 149.5 554.5 128 528 128L112 128zM64 260L64 448C64 483.3 92.7 512 128 512L512 512C547.3 512 576 483.3 576 448L576 260L377.6 408.8C343.5 434.4 296.5 434.4 262.4 408.8L64 260z"/></svg>
        </div>
        <div class="txt">
            <h2><?= messaging_h(messaging_text('MSG_TITLE')) ?></h2>
            <p><?= messaging_h(messaging_text('MSG_SUBTITLE')) ?></p>
        </div>
    </div>
</div>

<?php
try {
    $userId = messaging_user_id();
    $centreId = messaging_centre_id();
    $threads = messaging_fetch_threads($pdo, $userId);
    $threadParticipants = messaging_fetch_thread_participants($pdo, $userId);
    $threadId = (int)($_GET['thread_id'] ?? 0);
    $selectedThread = $threadId > 0 ? messaging_fetch_thread($pdo, $threadId, $userId) : null;
    $messages = [];
    if ($selectedThread) {
        messaging_mark_read($pdo, $threadId, $userId);
        $messages = messaging_fetch_messages($pdo, $threadId);
    }
    $centreStaff = messaging_fetch_centre_staff($pdo, $centreId, $userId);
    $friendStaff = messaging_can('messages.contact_friend_centres') ? messaging_fetch_friend_staff($pdo, $centreId) : [];
    $canGroup = messaging_can('messages.send_group');
    $isAdmin = messaging_is_app_admin();
} catch (Throwable $e) {
    ?>
    <div class="rc-alert red">
        <strong><?= messaging_h(messaging_text('MSG_LOAD_FAILED')) ?></strong>
        <?= messaging_h($e->getMessage()) ?>
    </div>
    <?php return;
}

$msgKey = (string)($_GET['msg'] ?? '');
$errorKey = (string)($_GET['error'] ?? '');
$threadTypeLabels = [
    'direct' => messaging_text('MSG_DIRECT'),
    'group' => messaging_text('MSG_GROUP'),
    'all_staff' => messaging_text('MSG_ALL_STAFF'),
    'platform' => messaging_text('MSG_PLATFORM'),
];
$activeTab = (string)($_GET['tab'] ?? ($threadId > 0 ? 'inbox' : 'compose'));
if (!in_array($activeTab, ['compose', 'inbox'], true)) $activeTab = 'compose';
$recipientOptions = [];
foreach (array_merge($centreStaff, $friendStaff) as $account) {
    $recipientOptions[] = [
        'id' => (int)$account['id'],
        'label' => messaging_account_label($account),
        'detail' => (string)($account['rescue_name'] ?? ''),
        'type' => 'person',
    ];
}
if ($canGroup) {
    $recipientOptions[] = ['id' => 'all_staff', 'label' => messaging_text('MSG_ALL_STAFF'), 'detail' => '', 'type' => 'all_staff'];
}
if ($isAdmin) {
    $recipientOptions[] = ['id' => 'platform', 'label' => messaging_text('MSG_PLATFORM'), 'detail' => '', 'type' => 'platform'];
}
?>

<?php if ($msgKey !== ''): ?><div class="rc-alert green"><?= messaging_h(messaging_text($msgKey)) ?></div><?php endif; ?>
<?php if ($errorKey !== ''): ?><div class="rc-alert red"><?= messaging_h(messaging_text($errorKey)) ?></div><?php endif; ?>

<nav class="rc-tabs rc-tabs-pill" aria-label="<?= messaging_h(messaging_text('MSG_TITLE')) ?>">
    <a class="rc-tab <?= $activeTab === 'compose' ? 'is-active' : '' ?>" href="messages.php?tab=compose"><?= messaging_h(messaging_text('MSG_COMPOSE')) ?></a>
    <a class="rc-tab <?= $activeTab === 'inbox' ? 'is-active' : '' ?>" href="messages.php?tab=inbox"><?= messaging_h(messaging_text('MSG_INBOX')) ?></a>
</nav>

<?php if ($activeTab === 'inbox'): ?>
    <div class="messaging-layout">
        <div class="rc-panel rc-stack messaging-inbox-list">
            <h3><?= messaging_h(messaging_text('MSG_INBOX')) ?></h3>
            <?php if (!$threads): ?>
                <div class="rc-alert grey"><?= messaging_h(messaging_text('MSG_NO_THREADS')) ?></div>
            <?php else: ?>
                <div class="rc-list">
                    <?php foreach ($threads as $thread): ?>
                        <?php $conversationParticipants = $threadParticipants[(int)$thread['thread_id']] ?? []; ?>
                        <a class="rc-item messaging-thread <?= (int)$thread['thread_id'] === $threadId ? 'is-active' : '' ?>" href="messages.php?tab=inbox&amp;thread_id=<?= (int)$thread['thread_id'] ?>">
                            <span class="rc-item-main">
                                <span class="messaging-participant-line">
                                    <?php if (!$conversationParticipants): ?>
                                        <strong><?= messaging_h(messaging_text('MSG_UNKNOWN_PARTICIPANT')) ?></strong>
                                    <?php else: ?>
                                        <?php foreach (array_slice($conversationParticipants, 0, 3) as $index => $participant): ?>
                                            <?php if ($index > 0): ?>, <?php endif; ?>
                                            <strong><?= messaging_h(messaging_account_label($participant)) ?></strong>
                                            <?php if (!empty($participant['rescue_name'])): ?>
                                                <span class="rc-muted">(<?= messaging_h($participant['rescue_name']) ?>)</span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if (count($conversationParticipants) > 3): ?>
                                            <span class="rc-muted">+<?= count($conversationParticipants) - 3 ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </span>
                                <small><?= messaging_h(mb_strimwidth((string)$thread['last_body'], 0, 85, '...')) ?></small>
                            </span>
                            <?php if ((int)$thread['unread_count'] > 0): ?><span class="rc-chip blue"><?= (int)$thread['unread_count'] ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <section class="rc-panel rc-stack">
            <?php if (!$selectedThread): ?>
                <div class="rc-alert grey"><?= messaging_h(messaging_text('MSG_SELECT_THREAD')) ?></div>
            <?php else: ?>
                <div class="rc-row-head">
                    <div>
                        <?php $selectedParticipants = $threadParticipants[$threadId] ?? []; ?>
                        <h3><?= messaging_h($selectedParticipants ? implode(', ', array_map('messaging_account_label', $selectedParticipants)) : messaging_text('MSG_UNKNOWN_PARTICIPANT')) ?></h3>
                        <div class="rc-inline-list">
                            <span class="rc-chip"><?= messaging_h($threadTypeLabels[$selectedThread['thread_type']] ?? $selectedThread['thread_type']) ?></span>
                            <span class="rc-chip"><?= (int)($selectedThread['participant_count'] ?? 0) ?> <?= messaging_h(messaging_text('MSG_PARTICIPANTS')) ?></span>
                        </div>
                    </div>
                </div>
                <div class="messaging-message-list">
                    <?php foreach ($messages as $message): ?>
                        <?php $mine = (int)$message['sender_user_id'] === $userId; ?>
                        <article class="rc-alert <?= $mine ? 'green is-mine' : 'blue' ?> messaging-message">
                            <div class="rc-row-head">
                                <strong><?= messaging_h(messaging_account_label($message)) ?></strong>
                                <small class="rc-muted">
                                    <?= messaging_h(date('d M Y', strtotime((string)$message['created_at']))) ?>
                                    <strong class="messaging-message-time"><?= messaging_h(date('H:i', strtotime((string)$message['created_at']))) ?></strong>
                                </small>
                            </div>
                            <p><?= nl2br(messaging_h($message['body'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($selectedThread['replies_allowed']) || $isAdmin): ?>
                    <form method="post" action="core/components/messaging/controllers/messaging_handler.php" class="rc-stack">
                        <input type="hidden" name="action" value="reply">
                        <input type="hidden" name="thread_id" value="<?= $threadId ?>">
                        <textarea class="xform-input" name="body" rows="4" required></textarea>
                        <div class="rc-actions"><button class="btn green" type="submit"><?= messaging_h(messaging_text('MSG_REPLY')) ?></button></div>
                    </form>
                <?php else: ?>
                    <div class="rc-alert grey"><?= messaging_h(messaging_text('MSG_REPLIES_DISABLED')) ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
<?php else: ?>
<div class="content-block messaging-compose">
    <h3><?= messaging_h(messaging_text('MSG_COMPOSE')) ?></h3>
    <form method="post" action="core/components/messaging/controllers/messaging_handler.php" class="rc-stack">
        <input type="hidden" name="action" value="send">
        <input type="hidden" id="recipient_type" name="recipient_type" value="direct">
        <div class="xform-field messaging-to-field">
            <label class="xform-label" for="message_recipient_search"><?= messaging_h(messaging_text('MSG_TO')) ?></label>
            <div id="message-selected-recipients" class="rc-chip-row"></div>
            <input class="xform-input" id="message_recipient_search" type="text" autocomplete="off" placeholder="<?= messaging_h(messaging_text('MSG_SEARCH_RECIPIENTS')) ?>">
            <div id="message-recipient-results" class="rc-autocomplete-results"></div>
            <div id="message-recipient-inputs"></div>
        </div>
        <div class="xform-field">
            <label class="xform-label" for="message_body"><?= messaging_h(messaging_text('MSG_MESSAGE')) ?></label>
            <textarea class="xform-input" id="message_body" name="body" rows="6" required></textarea>
        </div>
        <label class="rc-inline-list"><input type="checkbox" name="disable_replies" value="1"> <?= messaging_h(messaging_text('MSG_DISABLE_REPLIES')) ?></label>
        <div class="rc-actions"><button class="btn green" type="submit"><?= messaging_h(messaging_text('MSG_SEND')) ?></button></div>
    </form>
</div>
<script>
(() => {
    const options = <?= json_encode($recipientOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const canGroup = <?= $canGroup ? 'true' : 'false' ?>;
    const input = document.getElementById('message_recipient_search');
    const results = document.getElementById('message-recipient-results');
    const chips = document.getElementById('message-selected-recipients');
    const hiddenInputs = document.getElementById('message-recipient-inputs');
    const typeInput = document.getElementById('recipient_type');
    let selected = [];

    const renderSelected = () => {
        chips.innerHTML = '';
        hiddenInputs.innerHTML = '';
        selected.forEach(option => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'rc-chip blue';
            chip.textContent = option.label + ' ×';
            chip.addEventListener('click', () => {
                selected = selected.filter(item => String(item.id) !== String(option.id));
                typeInput.value = 'direct';
                renderSelected();
                renderResults();
            });
            chips.appendChild(chip);
            if (option.type === 'person') {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'recipient_ids[]';
                hidden.value = option.id;
                hiddenInputs.appendChild(hidden);
            }
        });
    };

    const selectOption = option => {
        if (option.type !== 'person') {
            selected = [option];
            typeInput.value = option.type;
        } else {
            selected = selected.filter(item => item.type === 'person');
            if (!canGroup) selected = [];
            if (!selected.some(item => String(item.id) === String(option.id))) selected.push(option);
            typeInput.value = 'direct';
        }
        input.value = '';
        results.innerHTML = '';
        renderSelected();
    };

    const renderResults = () => {
        const query = input.value.trim().toLowerCase();
        results.innerHTML = '';
        if (!query) return;
        options.filter(option => {
            if (selected.some(item => String(item.id) === String(option.id))) return false;
            return (option.label + ' ' + option.detail).toLowerCase().includes(query);
        }).slice(0, 12).forEach(option => {
            const row = document.createElement('div');
            row.className = 'rc-autocomplete-option';
            row.innerHTML = '<strong></strong><small class="rc-muted"></small>';
            row.querySelector('strong').textContent = option.label;
            row.querySelector('small').textContent = option.detail;
            row.addEventListener('click', () => selectOption(option));
            results.appendChild(row);
        });
    };

    input.addEventListener('input', renderResults);
    input.addEventListener('focus', renderResults);
    document.addEventListener('click', event => {
        if (!event.target.closest('.messaging-to-field')) results.innerHTML = '';
    });
})();
</script>
<?php endif; ?>
