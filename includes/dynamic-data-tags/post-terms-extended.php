<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * ----------------------------------------
 * Post Terms Extended — Limit & Primary
 * ----------------------------------------
 *
 * Tags:
 *   {post_terms_limited:taxonomy:N}        → first N terms (linked)
 *   {post_terms_limited:taxonomy:N:plain}   → first N terms (plain text)
 *   {post_terms_primary:taxonomy}           → primary term only (linked)
 *   {post_terms_primary:taxonomy:plain}     → primary term only (plain text)
 *
 * Examples:
 *   {post_terms_limited:category:1}         → first category, linked
 *   {post_terms_limited:category:2:plain}   → first 2 categories, plain
 *   {post_terms_limited:post_tag:3}         → first 3 tags, linked
 *   {post_terms_primary:category}           → primary category (TSF), linked
 *   {post_terms_primary:category:plain}     → primary category (TSF), plain
 *
 * Primary term uses The SEO Framework's _primary_term_{taxonomy} meta.
 * Falls back to the first term if no primary is set.
 * ----------------------------------------
 */

// ── Registration ──────────────────────────────────────────────────

add_filter( 'bricks/dynamic_tags_list', 'snn_register_post_terms_extended_tags' );
function snn_register_post_terms_extended_tags( $tags ) {
    $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

    foreach ( $taxonomies as $tax ) {
        $tags[] = [
            'name'  => '{post_terms_limited:' . $tax->name . ':N}',
            'label' => $tax->label . ' — Limited (N)',
            'group' => 'SNN',
        ];
        $tags[] = [
            'name'  => '{post_terms_primary:' . $tax->name . '}',
            'label' => $tax->label . ' — Primary',
            'group' => 'SNN',
        ];
    }

    return $tags;
}

// ── Helpers ───────────────────────────────────────────────────────

/**
 * Format a list of WP_Term objects as comma-separated links or plain text.
 */
function snn_format_terms( $terms, $taxonomy, $plain = false ) {
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '';
    }

    $out = [];
    foreach ( $terms as $term ) {
        if ( $plain ) {
            $out[] = esc_html( $term->name );
        } else {
            $link  = get_term_link( $term, $taxonomy );
            $out[] = '<a href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a>';
        }
    }

    return implode( ', ', $out );
}

/**
 * Get the primary term for a post+taxonomy via The SEO Framework meta.
 * Falls back to the first assigned term.
 */
function snn_get_primary_term( $post_id, $taxonomy ) {
    $primary_id = (int) get_post_meta( $post_id, '_primary_term_' . $taxonomy, true );

    if ( $primary_id ) {
        $term = get_term( $primary_id, $taxonomy );
        if ( $term && ! is_wp_error( $term ) ) {
            return $term;
        }
    }

    // Fallback: first term
    $terms = get_the_terms( $post_id, $taxonomy );
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        return $terms[0];
    }

    return null;
}

// ── Render tag ────────────────────────────────────────────────────

add_filter( 'bricks/dynamic_data/render_tag', 'snn_render_post_terms_extended_tag', 10, 3 );
function snn_render_post_terms_extended_tag( $tag, $post, $context = 'text' ) {
    if ( ! is_string( $tag ) ) {
        return $tag;
    }

    // {post_terms_limited:taxonomy:N} or {post_terms_limited:taxonomy:N:plain}
    if ( preg_match( '/^\{post_terms_limited:([\w-]+):(\d+)(?::(plain))?\}$/', $tag, $m ) ) {
        $taxonomy = $m[1];
        $limit    = (int) $m[2];
        $plain    = ! empty( $m[3] );
        $post_id  = $post ? $post->ID : get_the_ID();

        $terms = get_the_terms( $post_id, $taxonomy );
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return '';
        }

        $terms = array_slice( $terms, 0, $limit );
        return snn_format_terms( $terms, $taxonomy, $plain );
    }

    // {post_terms_primary:taxonomy} or {post_terms_primary:taxonomy:plain}
    if ( preg_match( '/^\{post_terms_primary:([\w-]+)(?::(plain))?\}$/', $tag, $m ) ) {
        $taxonomy = $m[1];
        $plain    = ! empty( $m[2] );
        $post_id  = $post ? $post->ID : get_the_ID();

        $term = snn_get_primary_term( $post_id, $taxonomy );
        if ( ! $term ) {
            return '';
        }

        return snn_format_terms( [ $term ], $taxonomy, $plain );
    }

    return $tag;
}

// ── Render content ────────────────────────────────────────────────

add_filter( 'bricks/dynamic_data/render_content', 'snn_render_post_terms_extended_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'snn_render_post_terms_extended_content', 10, 2 );
function snn_render_post_terms_extended_content( $content, $post, $context = 'text' ) {
    // {post_terms_limited:taxonomy:N} or {post_terms_limited:taxonomy:N:plain}
    if ( preg_match_all( '/\{post_terms_limited:([\w-]+):(\d+)(?::(plain))?\}/', $content, $matches, PREG_SET_ORDER ) ) {
        foreach ( $matches as $m ) {
            $value   = snn_render_post_terms_extended_tag( $m[0], $post, $context );
            $content = str_replace( $m[0], $value, $content );
        }
    }

    // {post_terms_primary:taxonomy} or {post_terms_primary:taxonomy:plain}
    if ( preg_match_all( '/\{post_terms_primary:([\w-]+)(?::(plain))?\}/', $content, $matches, PREG_SET_ORDER ) ) {
        foreach ( $matches as $m ) {
            $value   = snn_render_post_terms_extended_tag( $m[0], $post, $context );
            $content = str_replace( $m[0], $value, $content );
        }
    }

    return $content;
}
