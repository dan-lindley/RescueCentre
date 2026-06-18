<?php
// modules/learning/controllers/home_widgets.php
//
// Legacy integration point for the generic home widget registry.
// The learner card is now rendered directly by modules/learning/views/home_widget.php
// from home.php, so this provider is intentionally disabled to avoid rendering
// a second "My Learning" card with the old "View Learning" link.

function learning_home_widgets_provider(): array
{
    return [];
}

function learning_render_home_widget(PDO $pdo, array $context = []): string
{
    return '';
}
