<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * ----------------------------------------
 * Term Description Tags
 * ----------------------------------------
 *
 * Tags:
 *   {post_primary_term_desc:taxonomy}          → description of the primary term of the
 *                                                current post (works in single post templates)
 *   {static_term_desc:taxonomy:slug}           → description of a specific term by slug
 *   {static_term_desc:id:123}                  → description of a specific term by ID
 *
 * Examples:
 *   {post_primary_term_desc:category}          → primary category description of current post
 *   {post_primary_term_desc:post_tag}          → primary tag description of current post
 *   {static_term_desc:category:banche-finanza} → description of that specific category
 *   {static_term_desc:id:42}                   → description of term with ID 42
 *
 * Primary term resolution uses The SEO Framework's _primary_term_{taxonomy} meta.
 * Falls back to the first assigned term when no primary is set.
 * ----------------------------------------
 */

// ── Registration ──────────────────────────────────────────────────

add_filter( 'bricks/dynamic_tags_list', 'snn_register_term_description_tags' );
function snn_register_term_description_tags( $tags ) {
    $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

    foreach ( $taxonomies as $tax ) {
        $tags[] = [
            'name'  => '{post_primary_term_desc:' . $tax->name . '}',
            'label' => $tax->label . ' — Primary Term Description',
            'group' => 'SNN',
        ];
    }

    $tags[] = [
        'name'  => '{static_term_desc:taxonomy:slug}',
        'label' => 'Term Description by Slug',
        'group' => 'SNN',
    ];

    $tags[] = [
        'name'  => '{static_term_desc:id:123}',
        'label' => 'Term Description by ID',
        'group' => 'SNN',
    ];

    return $tags;
}

// ── Helpers ───────────────────────────────────────────────────────

/**
 * Resolve the primary term for a post+taxonomy.
 * Uses TSF's _primary_term_{taxonomy} meta; falls back to first assigned term.
 */
function snn_get_primary_term_for_desc( $post_id, $taxonomy ) {
    $primary_id = (int) get_post_meta( $post_id, '_primary_term_' . $taxonomy, true );

    if ( $primary_id ) {
        $term = get_term( $primary_id, $taxonomy );
        if ( $term && ! is_wp_error( $term ) ) {
            return $term;
        }
    }

    $terms = get_the_terms( $post_id, $taxonomy );
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        return $terms[0];
    }

    return null;
}

/**
 * Core resolver — returns the description string or '' for a given raw tag pattern.
 */
function snn_resolve_term_description_tag( $tag, $post ) {
    // {post_primary_term_desc:taxonomy}
    if ( preg_match( '/^\{post_primary_term_desc:([\w-]+)\}$/', $tag, $m ) ) {
        $taxonomy = $m[1];
        $post_id  = $post ? $post->ID : get_the_ID();

        if ( ! $post_id ) {
            return '';
        }

        $term = snn_get_primary_term_for_desc( $post_id, $taxonomy );

        return ( $term && ! is_wp_error( $term ) ) ? wp_kses_post( $term->description ) : '';
    }

    // {static_term_desc:id:123}
    if ( preg_match( '/^\{static_term_desc:id:(\d+)\}$/', $tag, $m ) ) {
        $term = get_term( (int) $m[1] );
        return ( $term && ! is_wp_error( $term ) ) ? wp_kses_post( $term->description ) : '';
    }

    // {static_term_desc:taxonomy:slug}
    if ( preg_match( '/^\{static_term_desc:([\w-]+):([\w-]+)\}$/', $tag, $m ) ) {
        $term = get_term_by( 'slug', $m[2], $m[1] );
        return ( $term && ! is_wp_error( $term ) ) ? wp_kses_post( $term->description ) : '';
    }

    return null; // not our tag
}

// ── Render tag ────────────────────────────────────────────────────

add_filter( 'bricks/dynamic_data/render_tag', 'snn_render_term_description_tag', 10, 3 );
function snn_render_term_description_tag( $tag, $post, $context = 'text' ) {
    if ( ! is_string( $tag ) ) {
        return $tag;
    }

    $result = snn_resolve_term_description_tag( $tag, $post );

    return $result !== null ? $result : $tag;
}

// ── Render content ────────────────────────────────────────────────

add_filter( 'bricks/dynamic_data/render_content', 'snn_render_term_description_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'snn_render_term_description_content', 10, 2 );
function snn_render_term_description_content( $content, $post, $context = 'text' ) {
    if ( ! is_string( $content ) ) {
        return $content;
    }

    // Match all three patterns in one pass
    $pattern = '/\{post_primary_term_desc:[\w-]+\}|\{static_term_desc:(?:id:\d+|[\w-]+:[\w-]+)\}/';

    if ( preg_match_all( $pattern, $content, $matches ) ) {
        foreach ( $matches[0] as $tag ) {
            $value   = snn_resolve_term_description_tag( $tag, $post );
            $content = str_replace( $tag, $value !== null ? $value : '', $content );
        }
    }

    return $content;
}
