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

        $this->controls['sfScrollHeight'] = [
            'group'       => 'layout',
            'label'       => esc_html__( 'Viewport Height', 'snn' ),
            'type'        => 'number',
            'units'       => true,
            'css'         => [
                [
                    'property' => 'height',
                    'selector' => '.sf-scroll',
                ],
            ],
            'inline'      => true,
            'placeholder' => '100vh',
        ];

        $this->controls['sfGap'] = [
            'group'       => 'layout',
            'label'       => esc_html__( 'Column Gap', 'snn' ),
            'type'        => 'number',
            'units'       => true,
            'css'         => [
                [
                    'property' => 'gap',
                    'selector' => '.sf-items',
                ],
            ],
            'inline'      => true,
            'placeholder' => '1.25em',
        ];

        $this->controls['sfMaxWidth'] = [
            'group'       => 'layout',
            'label'       => esc_html__( 'Max Width', 'snn' ),
            'type'        => 'number',
            'units'       => true,
            'css'         => [
                [
                    'property' => 'max-width',
                    'selector' => '.sf-items',
                ],
            ],
            'inline'      => true,
            'placeholder' => '70em',
        ];

        $this->controls['sfAspectRatio'] = [
            'group'       => 'layout',
            'label'       => esc_html__( 'Media Aspect Ratio', 'snn' ),
            'type'        => 'text',
            'css'         => [
                [
                    'property' => 'aspect-ratio',
                    'selector' => '.sf-item-media',
                ],
            ],
            'inline'      => true,
            'placeholder' => '1 / 1.3',
        ];

        $this->controls['sfBorderRadius'] = [
            'group'       => 'layout',
            'label'       => esc_html__( 'Media Border Radius', 'snn' ),
            'type'        => 'number',
            'units'       => true,
            'css'         => [
                [
                    'property' => 'border-radius',
                    'selector' => '.sf-item-media',
                ],
            ],
            'inline'      => true,
            'placeholder' => '0.75em',
        ];

        $this->controls['sfTextMaxWidth'] = [
            'group'       => 'layout',
            'label'       => esc_html__( 'Text Max Width', 'snn' ),
            'type'        => 'number',
            'units'       => true,
            'css'         => [
                [
                    'property' => 'max-width',
                    'selector' => '.sf-item-text > *',
                ],
            ],
            'inline'      => true,
            'placeholder' => '27.5em',
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
            'group'       => 'progress',
            'label'       => esc_html__( 'Bar Height', 'snn' ),
            'type'        => 'number',
            'units'       => true,
            'css'         => [
                [
                    'property' => 'height',
                    'selector' => '.sf-progress',
                ],
            ],
            'inline'      => true,
            'placeholder' => '0.25em',
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
        wp_enqueue_style(
            'snn-sticky-features',
            SNN_URL_ASSETS . 'css/sticky-features.css',
            [],
            '1.2'
        );
        wp_enqueue_script( 'gsap-js' );
        wp_enqueue_script( 'gsap-st-js' );
        wp_enqueue_script(
            'snn-sticky-features-js',
            SNN_URL_ASSETS . 'js/sticky-features.js',
            [ 'gsap-js', 'gsap-st-js' ],
            '1.2',
            true
        );
    }

    /**
     * Extract a CSS-ready color string from a Bricks color control value.
     * Handles: raw CSS variable, hex, hex+opacity (rgba), or fallback.
     */
    private static function resolve_color( $color_value, $fallback = '' ) {
        if ( ! is_array( $color_value ) ) {
            return $fallback;
        }
        // CSS variable (raw) takes priority
        if ( ! empty( $color_value['raw'] ) ) {
            return $color_value['raw'];
        }
        if ( ! empty( $color_value['hex'] ) ) {
            $hex = $color_value['hex'];
            $op  = isset( $color_value['opacity'] ) ? floatval( $color_value['opacity'] ) : 1;
            if ( $op < 1 ) {
                list( $r, $g, $b ) = sscanf( $hex, '#%02x%02x%02x' );
                return "rgba({$r},{$g},{$b},{$op})";
            }
            return $hex;
        }
        return $fallback;
    }

    public function render() {
        $s = $this->settings;

        $duration      = floatval( $s['sfDuration'] ?? 0.75 );
        $ease          = $s['sfEase'] ?? 'power4.inOut';
        $show_progress = ! empty( $s['sfShowProgress'] );

        // Border radius — needed as CSS var (used in clip-path in CSS + JS)
        $br    = esc_attr( $s['sfBorderRadius'] ?? '0.75em' );
        // Aspect ratio — needed as CSS var (used in mobile media query)
        $ratio = esc_attr( $s['sfAspectRatio'] ?? '1 / 1.3' );

        // Progress bar colors — Bricks color controls don't have a `css` mechanism for custom props
        $prog_color = self::resolve_color( $s['sfProgressColor'] ?? null, '#fff' );
        $prog_bg    = self::resolve_color( $s['sfProgressBg'] ?? null, 'rgba(255,255,255,0.15)' );

        if ( ! empty( $this->attributes['_root']['id'] ) ) {
            $root_id = $this->attributes['_root']['id'];
        } else {
            $root_id = 'sf-' . $this->id;
            $this->set_attribute( '_root', 'id', $root_id );
        }

        // CSS custom properties — only for values NOT handled by Bricks' css mechanism
        // gap, max-width, aspect-ratio, border-radius, text-max-width, prog-height
        // are all applied by Bricks via the 'css' key on each control.
        $css_vars  = "--sf-br:" . esc_attr( $br ) . ";";
        $css_vars .= "--sf-ratio:" . esc_attr( $ratio ) . ";";
        if ( $show_progress ) {
            $css_vars .= "--sf-prog-color:" . esc_attr( $prog_color ) . ";";
            $css_vars .= "--sf-prog-bg:" . esc_attr( $prog_bg ) . ";";
        }

        $this->set_attribute( '_root', 'data-sf-wrap', '' );
        $this->set_attribute( '_root', 'data-sf-duration', $duration );
        $this->set_attribute( '_root', 'data-sf-ease', $ease );
        $this->set_attribute( '_root', 'data-sf-border-radius', $br );
        $this->set_attribute( '_root', 'style', $css_vars );

        echo "<div {$this->render_attributes( '_root' )}>";

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
