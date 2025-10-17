<?php

    global $rz_explore;

    $term = null;
    $has_more = null;
    $title = apply_filters('brikk/explore/global/title', esc_html__( 'Explore', 'brikk') );

    if( $rz_explore->type and $rz_explore->type->id ) {

        $title = $rz_explore->type->get('rz_name_plural');

        $fields = Rz()->json_decode( $rz_explore->type->get('rz_fields') );
        foreach( $fields as $field ) {
            if( $field->template->id == 'taxonomy' ) {
                if( ! $rz_explore->request->is_empty( Rz()->unprefix( $field->fields->key ) ) ) {

                    $term_value = $rz_explore->request->get( Rz()->unprefix( $field->fields->key ) );
                    $term_name = Rz()->prefix( $field->fields->key );

                    if( is_array( $term_value ) ) {
                        $has_more = true;
                        $term_value = reset( $term_value );
                    }

                    $term = get_term_by( 'slug', $term_value, $term_name );

                    if( $term ) {
                        $title = $term->name;
                    }

                }
            }
        }
    }

?>

<?php if( $title ): ?>
    <div class="rz-taxonomy-heading">
        <div class="rz--inner">

            <div class="rz--title">
                <h4 class="rz--name">
                    <?php echo esc_html( $title ); ?>
                    <?php if( $has_more ) { echo ' ...'; } ?>
                </h4>
            </div>

            <div class="rz--action">
                <ul>
                    <?php if( $term ): ?>
                        <li>
                            <a href="<?php echo esc_url( Rz()->get_explore_page_url( [ 'type' => $rz_explore->type->get('rz_slug') ], false ) ); ?>" class="rz--close rz-action-dynamic-explore">
                                <i class="fas fa-times"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>
    </div>
<?php endif; ?>
