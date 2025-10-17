<?php

defined('ABSPATH') || exit;

if( ! function_exists('routiz') ) {
    return;
}

if( version_compare( RZ_VERSION, '1.3.4' ) < 0 ) {
    return;
}

$active_site = routiz()->notify->get_active_site();

?>

<nav class="brk-nav brk-nav-notifications">
    <ul>
        <li>
            <a href="#" class="brk--pad" data-action="toggle-side">
                <i class="material-icon-notification_important">
                    <?php if( $active_site ): ?>
                        <span class="brk--dot">
                            <?php echo (int) $active_site; ?>
                        </span>
                    <?php endif; ?>
                </i>
            </a>
        </li>
    </ul>
</nav>
