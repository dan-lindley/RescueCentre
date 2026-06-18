<?php
function show_status_message() {

    if (!isset($_GET['msg'])) {
        return;
    }

    $messages = [

        // SUCCESS MESSAGES
        'measurement_added' => [
            'text' => 'Measurement added successfully.',
            'bg'   => '#d4edda',
            'color'=> '#155724',
            'border'=> '#c3e6cb'
        ],

        'weight_added' => [
            'text' => 'Weight added successfully.',
            'bg'   => '#d4edda',
            'color'=> '#155724',
            'border'=> '#c3e6cb'
        ],

        'note_added' => [
            'text' => 'Note saved successfully.',
            'bg'   => '#d4edda',
            'color'=> '#155724',
            'border'=> '#c3e6cb'
        ],

        'medication_added' => [
            'text' => 'Medication record added.',
            'bg'   => '#d4edda',
            'color'=> '#155724',
            'border'=> '#c3e6cb'
        ],

        // ERROR MESSAGES
        'error' => [
            'text' => 'An unexpected error occurred.',
            'bg'   => '#f8d7da',
            'color'=> '#721c24',
            'border'=> '#f5c6cb'
        ],
    ];

    $key = $_GET['msg'];

    if (!isset($messages[$key])) {
        return;
    }

    $m = $messages[$key];

    echo '<div style="
        padding: 12px 16px;
        margin-bottom: 15px;
        border-radius: 6px;
        background: ' . $m['bg'] . ';
        color: ' . $m['color'] . ';
        border: 1px solid ' . $m['border'] . ';
        font-weight: 500;
    ">' . htmlspecialchars($m['text']) . '</div>';
}
?>
