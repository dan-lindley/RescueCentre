<?php
// modules/partner_logs/controllers/patient_row_actions.php

require_once __DIR__ . '/partner_logs_lib.php';

function partner_logs_patient_row_actions_provider(): array
{
    return [
        'key' => 'partner_logs',
        'order' => 850,
        'button_callback' => 'partner_logs_render_patient_button',
        'form_callback' => 'partner_logs_render_patient_form',
    ];
}
