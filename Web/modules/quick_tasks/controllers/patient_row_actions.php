<?php
// modules/quick_tasks/controllers/patient_row_actions.php

require_once __DIR__ . '/quick_tasks_lib.php';

function quick_tasks_patient_row_actions_provider(): array
{
    return [
        'key' => 'quick_tasks',
        'order' => 900,
        'button_callback' => 'quick_tasks_render_patient_button',
        'icons_callback' => 'quick_tasks_render_patient_icons',
        'form_callback' => 'quick_tasks_render_patient_form',
    ];
}
