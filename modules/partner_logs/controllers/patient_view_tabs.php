<?php
// modules/partner_logs/controllers/patient_view_tabs.php

require_once __DIR__ . '/partner_logs_lib.php';

function partner_logs_patient_view_tabs_provider(): array
{
    return [
        'key' => 'partner_logs',
        'tabs' => [
            [
                'id' => 'partner_logs',
                'label' => 'Partner',
                'view' => dirname(__DIR__) . '/views/viewpatient.php',
                'allowed' => partner_logs_can_access(),
                'order' => 850,
            ],
        ],
    ];
}
