<?php

defined('ABSPATH') || exit;

global $rz_listing;

$action_types = Rz()->json_decode( $rz_listing->type->get('rz_action_types') );

if( ! $action_types ) {
    return;
}

$action_mobile_button_label = $rz_listing->type->get('rz_action_mobile_button_label');

if( ! $action_mobile_button_label ) {
    $action_mobile_button_label = esc_html__('View', 'brikk');
}

?>

<div class="brk-mobile-listing-bottom">
    <div class="rz-w-100">
        <a href="#" class="rz-button rz-button-accent rz-block" data-action="toggle-mobile-action">
            <span><?php echo str_replace('[price]', Rz()->format_price( $rz_listing->get('rz_price') ), wp_kses_post( $action_mobile_button_label ) ); ?></span>
        </a>
    </div>
</div>
