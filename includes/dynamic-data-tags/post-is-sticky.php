<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * ----------------------------------------
 * Dynamic Post Is Sticky Module
 * ----------------------------------------
 * Usage: {is_sticky}
 *
 * Description:
 * Displays "1" if the current post is marked as sticky, otherwise displays "0".
 * ----------------------------------------
 */
add_filter( 'bricks/dynamic_tags_list', 'register_is_sticky_tag' );
function register_is_sticky_tag( $tags ) {
    $tags[] = [
        'name'  => '{is_sticky}',
        'label' => 'Is Sticky Post',
        'group' => 'SNN',
    ];
    return $tags;
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_is_sticky_tag', 10, 3 );
function render_is_sticky_tag( $tag, $post, $context = 'text' ) {
    if ( $tag !== '{is_sticky}' ) {
        return $tag;
    }

    if ( ! $post ) {
        return '0';
    }

    return is_sticky( $post->ID ) ? '1' : '0';
}

add_filter( 'bricks/dynamic_data/render_content', 'render_is_sticky_tag_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_is_sticky_tag_in_content', 10, 2 );
function render_is_sticky_tag_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{is_sticky}' ) === false ) {
        return $content;
    }

    $value   = render_is_sticky_tag( '{is_sticky}', $post, $context );
    $content = str_replace( '{is_sticky}', $value, $content );

    return $content;
}
