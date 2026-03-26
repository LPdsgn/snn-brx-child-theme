<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bricks\Element;
use Bricks\Frontend;

class Prefix_Element_Sticky_Features extends Element {
    public $category     = 'snn';
    public $name         = 'sticky-features';
    public $icon         = 'ti-layout-media-left-alt';
    public $css_selector = '';
    public $scripts      = [];
    public $nestable     = true;

    public function get_label() {
        return esc_html__( 'Sticky Features', 'snn' ) . ' (' . esc_html__( 'Nestable', 'snn' ) . ')';
    }

    public function get_keywords() {
        return [ 'nestable', 'sticky', 'features', 'scroll', 'gsap', 'scrolltrigger', 'animation', 'pin' ];
    }

    public function set_control_groups() {
        $this->control_groups['animation'] = [
            'title' => esc_html__( 'Animation', 'snn' ),
        ];
        $this->control_groups['layout'] = [
            'title' => esc_html__( 'Layout', 'snn' ),
        ];
        $this->control_groups['progress'] = [
            'title' => esc_html__( 'Progress Bar', 'snn' ),
        ];
    }

    public function set_controls() {
        $this->controls['_children'] = [
            'type'  => 'repeater',
            'items' => 'children',
        ];

        $this->controls['sfDuration'] = [
            'group'   => 'animation',
            'label'   => esc_html__( 'Transition Duration (s)', 'snn' ),
            'type'    => 'number',
            'default' => 0.75,
            'min'     => 0.05,
            'max'     => 3,
            'step'    => 0.05,
        ];

        $this->controls['sfEase'] = [
            'group'   => 'animation',
            'label'   => esc_html__( 'Easing', 'snn' ),
            'type'    => 'select',
            'options' => [
                'power4.inOut' => 'power4.inOut',
                'power3.inOut' => 'power3.inOut',
                'power2.inOut' => 'power2.inOut',
                'power1.inOut' => 'power1.inOut',
                'expo.inOut'   => 'expo.inOut',
                'circ.inOut'   => 'circ.inOut',
                'back.inOut'   => 'back.inOut',
                'none'         => 'none (linear)',
            ],
            'default' => 'power4.inOut',
        ];

        $this->controls['sfScrollAmount'] = [
            'group'   => 'animation',
            'label'   => esc_html__( 'Scroll Range Used', 'snn' ),
            'type'    => 'number',
            'default' => 0.9,
            'min'     => 0.1,
            'max'     => 1,
            'step'    => 0.05,
        ];

        $this->controls['sfGap'] = [
            'group'   => 'layout',
            'label'   => esc_html__( 'Column Gap', 'snn' ),
            'type'    => 'text',
            'default' => '1.25em',
        ];

        $this->controls['sfMaxWidth'] = [
            'group'   => 'layout',
            'label'   => esc_html__( 'Max Width', 'snn' ),
            'type'    => 'text',
            'default' => '70em',
        ];

        $this->controls['sfAspectRatio'] = [
            'group'   => 'layout',
            'label'   => esc_html__( 'Media Aspect Ratio', 'snn' ),
            'type'    => 'text',
            'default' => '1 / 1.3',
        ];

        $this->controls['sfBorderRadius'] = [
            'group'   => 'layout',
            'label'   => esc_html__( 'Media Border Radius', 'snn' ),
            'type'    => 'text',
            'default' => '0.75em',
        ];

        $this->controls['sfTextMaxWidth'] = [
            'group'   => 'layout',
            'label'   => esc_html__( 'Text Max Width', 'snn' ),
            'type'    => 'text',
            'default' => '27.5em',
        ];

        $this->controls['sfShowProgress'] = [
            'group'   => 'progress',
            'label'   => esc_html__( 'Show Progress Bar', 'snn' ),
            'type'    => 'checkbox',
            'default' => true,
        ];

        $this->controls['sfProgressColor'] = [
            'group'   => 'progress',
            'label'   => esc_html__( 'Bar Color', 'snn' ),
            'type'    => 'color',
            'default' => [ 'hex' => '#ffffff' ],
        ];

        $this->controls['sfProgressBg'] = [
            'group'   => 'progress',
            'label'   => esc_html__( 'Track Color', 'snn' ),
            'type'    => 'color',
            'default' => [ 'hex' => '#ffffff', 'opacity' => 0.15 ],
        ];

        $this->controls['sfProgressHeight'] = [
            'group'   => 'progress',
            'label'   => esc_html__( 'Bar Height', 'snn' ),
            'type'    => 'text',
            'default' => '0.25em',
        ];
    }

    public function get_nestable_item() {
        return [
            'name'     => 'block',
            'label'    => esc_html__( 'Slide', 'snn' ) . ' {item_index}',
            'settings' => [
                '_hidden' => [
                    '_cssClasses' => 'sf-item',
                ],
            ],
            'children' => [
                [
                    'name'     => 'block',
                    'label'    => esc_html__( 'Media', 'snn' ),
                    'settings' => [
                        '_hidden' => [
                            '_cssClasses' => 'sf-item-media',
                        ],
                    ],
                    'children' => [
                        [
                            'name'     => 'image',
                            'label'    => esc_html__( 'Image', 'snn' ),
                            'settings' => [
                                '_hidden' => [
                                    '_cssClasses' => 'sf-media',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name'     => 'block',
                    'label'    => esc_html__( 'Text', 'snn' ),
                    'settings' => [
                        '_hidden' => [
                            '_cssClasses' => 'sf-item-text',
                        ],
                    ],
                    'children' => [
                        [
                            'name'     => 'text-basic',
                            'label'    => esc_html__( 'Tag', 'snn' ),
                            'settings' => [
                                'text'    => '0{item_index}',
                                'tag'     => 'span',
                                '_hidden' => [ '_cssClasses' => 'sf-tag sf-text' ],
                            ],
                        ],
                        [
                            'name'     => 'heading',
                            'label'    => esc_html__( 'Heading', 'snn' ),
                            'settings' => [
                                'text'    => esc_html__( 'Feature Heading', 'snn' ) . ' {item_index}',
                                'tag'     => 'h2',
                                '_hidden' => [ '_cssClasses' => 'sf-heading sf-text' ],
                            ],
                        ],
                        [
                            'name'     => 'text-basic',
                            'label'    => esc_html__( 'Description', 'snn' ),
                            'settings' => [
                                'text'    => esc_html__( 'A short description of this feature goes here.', 'snn' ),
                                '_hidden' => [ '_cssClasses' => 'sf-desc sf-text' ],
                            ],
                        ],
                        [
                            'name'     => 'text-basic',
                            'label'    => esc_html__( 'Link', 'snn' ),
                            'settings' => [
                                'text'    => esc_html__( 'Learn more', 'snn' ),
                                '_hidden' => [ '_cssClasses' => 'sf-link sf-text' ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function get_nestable_children() {
        $children = [];
        for ( $i = 0; $i < 4; $i++ ) {
            $item = $this->get_nestable_item();
            $item = wp_json_encode( $item );
            $item = str_replace( '{item_index}', $i + 1, $item );
            $item = json_decode( $item, true );
            $children[] = $item;
        }
        return $children;
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'gsap-js' );
        wp_enqueue_script( 'gsap-st-js' );
        wp_enqueue_script(
            'snn-sticky-features',
            SNN_URL_ASSETS . 'js/sticky-features.js',
            [ 'gsap-js', 'gsap-st-js' ],
            '1.2',
            true
        );
    }

    public function render() {
        $s = $this->settings;

        $duration      = floatval( $s['sfDuration'] ?? 0.75 );
        $ease          = $s['sfEase'] ?? 'power4.inOut';
        $scroll_amount = floatval( $s['sfScrollAmount'] ?? 0.9 );
        $gap           = esc_attr( $s['sfGap'] ?? '1.25em' );
        $max_width     = esc_attr( $s['sfMaxWidth'] ?? '70em' );
        $aspect_ratio  = esc_attr( $s['sfAspectRatio'] ?? '1 / 1.3' );
        $br            = esc_attr( $s['sfBorderRadius'] ?? '0.75em' );
        $text_max_w    = esc_attr( $s['sfTextMaxWidth'] ?? '27.5em' );
        $show_progress = ! empty( $s['sfShowProgress'] );
        $prog_h        = esc_attr( $s['sfProgressHeight'] ?? '0.25em' );

        $prog_color = '#fff';
        if ( ! empty( $s['sfProgressColor']['hex'] ) ) {
            $prog_color = esc_attr( $s['sfProgressColor']['hex'] );
        }
        $prog_bg = 'rgba(255,255,255,0.15)';
        if ( ! empty( $s['sfProgressBg']['hex'] ) ) {
            $op = isset( $s['sfProgressBg']['opacity'] ) ? floatval( $s['sfProgressBg']['opacity'] ) : 1;
            if ( $op < 1 ) {
                list( $r, $g, $b ) = sscanf( $s['sfProgressBg']['hex'], '#%02x%02x%02x' );
                $prog_bg = "rgba({$r},{$g},{$b},{$op})";
            } else {
                $prog_bg = esc_attr( $s['sfProgressBg']['hex'] );
            }
        }

        if ( ! empty( $this->attributes['_root']['id'] ) ) {
            $root_id = $this->attributes['_root']['id'];
        } else {
            $root_id = 'sf-' . $this->id;
            $this->set_attribute( '_root', 'id', $root_id );
        }

        // CSS custom properties for per-instance dynamic values
        $css_vars  = "--sf-gap:{$gap};";
        $css_vars .= "--sf-max-w:{$max_width};";
        $css_vars .= "--sf-ratio:{$aspect_ratio};";
        $css_vars .= "--sf-br:{$br};";
        $css_vars .= "--sf-text-max-w:{$text_max_w};";
        if ( $show_progress ) {
            $css_vars .= "--sf-prog-h:{$prog_h};";
            $css_vars .= "--sf-prog-color:{$prog_color};";
            $css_vars .= "--sf-prog-bg:{$prog_bg};";
        }

        $this->set_attribute( '_root', 'data-sf-wrap', '' );
        $this->set_attribute( '_root', 'data-sf-duration', $duration );
        $this->set_attribute( '_root', 'data-sf-ease', $ease );
        $this->set_attribute( '_root', 'data-sf-scroll-amount', $scroll_amount );
        $this->set_attribute( '_root', 'data-sf-border-radius', $br );
        $this->set_attribute( '_root', 'style', $css_vars );

        echo "<div {$this->render_attributes( '_root' )}>";

        // ====== CSS — no #{id} scoping: Bricks per-element styles (#brxe-xyz) always win ======
        echo "<style>
/*
 * Sticky Features — structural + default visual styles
 * Selectors use bare .sf-* classes (specificity 0,0,1,0).
 * Bricks per-element styles (#brxe-xyz, specificity 0,1,0,0) always override these.
 * Dynamic per-instance values come from CSS custom properties on [data-sf-wrap].
 */

[data-sf-wrap] {
    width: 100%;
    position: relative;
}

.sf-scroll {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    position: relative;
}

/* Grid container — all items overlap in one row */
.sf-items {
    display: grid !important;
    grid-template-columns: 1fr 1fr;
    gap: var(--sf-gap, 1.25em);
    width: 100%;
    max-width: var(--sf-max-w, 70em);
    margin: 0 auto;
}

/* display:contents → media & text become direct grid children */
.sf-item {
    display: contents !important;
}

/* Media column — display:block overrides Bricks flex so ::before aspect-ratio works */
.sf-item-media {
    display: block !important;
    grid-column: 1;
    grid-row: 1;
    position: relative;
    overflow: hidden;
    border-radius: var(--sf-br, 0.75em);
    clip-path: inset(50% round var(--sf-br, 0.75em));
}

.sf-item:first-child .sf-item-media,
.sf-items > :first-child.sf-item-media {
    clip-path: inset(0% round var(--sf-br, 0.75em));
}

.sf-item-media::before {
    content: '';
    display: block;
    width: 100%;
    aspect-ratio: var(--sf-ratio, 1 / 1.3);
}

.sf-item-media > * {
    position: absolute !important;
    inset: 0;
    width: 100% !important;
    height: 100% !important;
}

.sf-item-media img,
.sf-item-media video {
    object-fit: cover;
    width: 100%;
    height: 100%;
    display: block;
}

/* Text column — overlapping in grid-row:1, col 2 */
.sf-item-text {
    grid-column: 2;
    grid-row: 1;
    display: flex !important;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    gap: 1.5em;
    opacity: 0;
    visibility: hidden;
}

.sf-item:first-child .sf-item-text,
.sf-items > :first-child.sf-item-text {
    opacity: 1;
    visibility: visible;
}

.sf-item-text > * {
    max-width: var(--sf-text-max-w, 27.5em);
}

/* Default visual styles — easily overridden from Bricks panel */
.sf-tag {
    font-size: 1em;
    line-height: 1;
    display: inline-block;
}

.sf-heading {
    margin: 0;
    font-size: 3.75em;
    font-weight: 500;
    line-height: 1;
}

.sf-desc {
    color: rgba(255,255,255,0.7);
    margin-bottom: 0;
    font-size: 1.25em;
    line-height: 1.2;
}

.sf-link {
    color: #fff;
    text-decoration: underline;
    font-size: 1.25em;
    line-height: 1.2;
    cursor: pointer;
}

/* Progress bar */
.sf-progress {
    grid-column: 1;
    grid-row: 1;
    align-self: end;
    height: var(--sf-prog-h, 0.25em);
    background: var(--sf-prog-bg, rgba(255,255,255,0.15));
    z-index: 2;
    pointer-events: none;
    border-radius: 0 0 var(--sf-br, 0.75em) var(--sf-br, 0.75em);
    overflow: hidden;
}

.sf-progress-bar {
    width: 100%;
    height: 100%;
    background: var(--sf-prog-color, #fff);
    transform: scaleX(0);
    transform-origin: 0% 50%;
}

/* Mobile */
@media screen and (max-width: 767px) {
    .sf-scroll {
        height: auto;
        min-height: 100svh;
        padding: 1.25em 0 2.5em;
        align-items: flex-start;
    }
    .sf-items {
        grid-template-columns: 1fr !important;
    }
    .sf-item-media {
        grid-column: 1;
    }
    .sf-item-media::before {
        aspect-ratio: 1;
    }
    .sf-item-text {
        grid-column: 1;
        grid-row: 2;
    }
    .sf-item-text > * { max-width: none; }
    .sf-heading { font-size: 2.5em; }
    .sf-desc, .sf-link { font-size: 1em; }
}

/* Builder preview: all items visible, stacked vertically */
.iframe .sf-scroll { height: auto; }
.iframe .sf-items {
    grid-template-columns: 1fr 1fr !important;
    grid-auto-rows: auto;
}
.iframe .sf-item {
    display: grid !important;
    grid-template-columns: subgrid;
    grid-column: 1 / -1;
}
.iframe .sf-item-media {
    grid-row: auto;
    clip-path: inset(0% round var(--sf-br, 0.75em)) !important;
}
.iframe .sf-item-text {
    grid-row: auto;
    opacity: 1 !important;
    visibility: visible !important;
}
.iframe .sf-progress { display: none; }
</style>";

        // DOM structure
        echo '<div class="sf-scroll">';
        echo '<div class="sf-items">';
        echo Frontend::render_children( $this );

        if ( $show_progress ) {
            echo '<div class="sf-progress"><div class="sf-progress-bar" data-sf-progress></div></div>';
        }

        echo '</div>'; // .sf-items
        echo '</div>'; // .sf-scroll
        echo '</div>'; // _root
    }
}
