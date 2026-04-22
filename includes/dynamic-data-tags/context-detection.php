<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * ----------------------------------------
 * Context Detection Tags
 * ----------------------------------------
 *
 * Tags:
 *   {is_singular}                      → "1" on single post/page/CPT views, "0" on archives
 *   {post_has_term:taxonomy:slug}       → "1" if the current post has that term, "0" otherwise
 *   {post_has_term:taxonomy:id:123}     → "1" if the current post has term with that ID
 *
 * Bricks Builder condition recipes:
 *
 *   Show only on single posts (not archives):
 *     {post_type} == post   AND   {is_singular} == 1
 *
 *   Exclude a category:
 *     {post_has_term:category:nome-categoria} != 1
 *
 *   Full example — single posts excluding "banche-finanza" and "inchieste":
 *     {post_type}                      == post
 *     {is_singular}                    == 1
 *     {post_has_term:category:banche-finanza}  != 1
 *     {post_has_term:category:inchieste}       != 1
 * ----------------------------------------
 */

// ── Registration ──────────────────────────────────────────────────

add_filter( 'bricks/dynamic_tags_list', 'snn_register_context_detection_tags' );
function snn_register_context_detection_tags( $tags ) {
    $tags[] = [
        'name'  => '{is_singular}',
        'label' => 'Is Singular (1/0)',
        'group' => 'SNN',
    ];

    $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
    foreach ( $taxonomies as $tax ) {
        $tags[] = [
            'name'  => '{post_has_term:' . $tax->name . ':slug}',
            'label' => 'Post has ' . $tax->label . ' (by slug)',
            'group' => 'SNN',
        ];
    }

    $tags[] = [
        'name'  => '{post_has_term:taxonomy:id:123}',
        'label' => 'Post has Term by ID',
        'group' => 'SNN',
    ];

    return $tags;
}

// ── Core resolvers ────────────────────────────────────────────────

function snn_resolve_context_detection_tag( $tag, $post ) {
    // {is_singular}
    if ( $tag === '{is_singular}' ) {
        return is_singular() ? '1' : '0';
    }

    // {post_has_term:taxonomy:id:123}
    if ( preg_match( '/^\{post_has_term:([\w-]+):id:(\d+)\}$/', $tag, $m ) ) {
        $post_id = $post ? $post->ID : get_the_ID();
        if ( ! $post_id ) return '0';
        return has_term( (int) $m[2], $m[1], $post_id ) ? '1' : '0';
    }

    // {post_has_term:taxonomy:slug}
    if ( preg_match( '/^\{post_has_term:([\w-]+):([\w-]+)\}$/', $tag, $m ) ) {
        $post_id = $post ? $post->ID : get_the_ID();
        if ( ! $post_id ) return '0';
        return has_term( $m[2], $m[1], $post_id ) ? '1' : '0';
    }

    return null;
}

// ── Render tag ────────────────────────────────────────────────────

add_filter( 'bricks/dynamic_data/render_tag', 'snn_render_context_detection_tag', 10, 3 );
function snn_render_context_detection_tag( $tag, $post, $context = 'text' ) {
    if ( ! is_string( $tag ) ) {
        return $tag;
    }

    $result = snn_resolve_context_detection_tag( $tag, $post );

    return $result !== null ? $result : $tag;
}

// ── Render content ────────────────────────────────────────────────

add_filter( 'bricks/dynamic_data/render_content', 'snn_render_context_detection_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'snn_render_context_detection_content', 10, 2 );
function snn_render_context_detection_content( $content, $post, $context = 'text' ) {
    if ( ! is_string( $content ) ) {
        return $content;
    }

    $pattern = '/\{is_singular\}|\{post_has_term:[\w-]+:(?:id:\d+|[\w-]+)\}/';

    if ( preg_match_all( $pattern, $content, $matches ) ) {
        foreach ( $matches[0] as $tag ) {
            $value   = snn_resolve_context_detection_tag( $tag, $post );
            $content = str_replace( $tag, $value !== null ? $value : '', $content );
        }
    }

    return $content;
}
