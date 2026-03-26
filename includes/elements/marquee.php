<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Marquee extends \Bricks\Element {
	public $category     = 'custom';
	public $name         = 'marquee';
	public $icon         = 'ti-layout-slider-alt';
	public $css_selector = '.marquee'; // Default CSS selector for all controls with 'css' properties
	public $scripts      = []; // Enqueue registered scripts by their handle

  	public function get_label() {
    	return esc_html__( 'Marquee', 'bricks' );
  	}

	public function set_control_groups() {
		$this->control_groups['options'] = [
			'title' => esc_html__( 'Options', 'bricks' ),
			'tab'   => 'content',
		];
		$this->control_groups['itemsStyle'] = [
			'title' => esc_html__( 'Items Style', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// Child type control
		$this->controls['type'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Type', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'media' => esc_html__( 'Media', 'bricks' ),
				'text'  => esc_html__( 'Text', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Select...', 'bricks' ),
		];

		// IMAGES (when type is 'media')
		$this->controls['items'] = [
			'tab'      => 'content',
			'type'     => 'image-gallery',
			'label'    => esc_html__( 'Images', 'bricks' ),
			'required' => [ 'type', '=', 'media' ],
		];

		$this->controls['imageWidth'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Image width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.marquee__item img',
				],
			],
			'inline'      => true,
			'placeholder' => 'auto',
			'required' => [ 'type', '=', 'media' ],
		];

		$this->controls['imageHeight'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Image height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
					'selector' => '.marquee__item img',
				],
			],
			'inline'      => true,
			'placeholder' => 'auto',
			'required'    => [ 'type', '=', 'media' ],
		];

		$this->controls['imageRatio'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Image ratio', 'bricks' ),
			'type'     => 'text',
			'css'      => [
				[
					'selector' => '.marquee__item img',
					'property' => 'aspect-ratio',
				],
			],
			'inline'   => true,
			'required' => [ 'type', '=', 'media' ],
		];

		$this->controls['imageObjectFit'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Object fit', 'bricks' ),
			'type'     => 'select',
			'options'  => [
				'fill'       => esc_html__( 'Fill', 'bricks' ),
				'contain'    => esc_html__( 'Contain', 'bricks' ),
				'cover'      => esc_html__( 'Cover', 'bricks' ),
				'none'       => esc_html__( 'None', 'bricks' ),
				'scale-down' => esc_html__( 'Scale down', 'bricks' ),
			],
			'css'      => [
				[
					'property' => 'object-fit',
					'selector' => '.marquee__item img',
				],
			],
			'inline'      => true,
			'required' => [ 'type', '=', 'media' ],
		];

		// Attribute: fetchpriority (@since 2.0)
		$this->controls['fetchpriorityAttribute'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Fetch priority', 'bricks' ),
			'inline'  => true,
			'type'    => 'select',
			'options' => [
				'high' => esc_html__( 'High', 'bricks' ),
				'low'  => esc_html__( 'Low', 'bricks' ),
				'auto' => esc_html__( 'Auto', 'bricks' ),
			],
			'required' => [ 'type', '=', 'media' ],
		];

		// Attribute: loading (@since 2.0)
		$this->controls['loadingAttribute'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Loading', 'bricks' ),
			'inline'  => true,
			'type'    => 'select',
			'options' => [
				'lazy'  => esc_html__( 'Lazy', 'bricks' ),
				'eager' => esc_html__( 'Eager', 'bricks' ),
			],
			'required' => [ 'type', '=', 'media' ],
		];

		// TEXT ITEMS (when type is 'text')
		$this->controls['textItems'] = [
			'tab'         => 'content',
			'type'        => 'repeater',
			'label'       => esc_html__( 'Text items', 'bricks' ),
			'placeholder' => esc_html__( 'Text item', 'bricks' ),
			'fields'      => [
				'text' => [
					'label' => esc_html__( 'Text', 'bricks' ),
					'type'  => 'text',
				],
			],
			'default'     => [
				[
					'text' => esc_html__( 'Item 1', 'bricks' ),
				],
				[
					'text' => esc_html__( 'Item 2', 'bricks' ),
				],
				[
					'text' => esc_html__( 'Item 3', 'bricks' ),
				],
			],
			'required'    => [ 'type', '=', 'text' ],
		];

		$this->controls['textTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'typography',
					'selector' => '.marquee__item',
				],
			],
			'required' => [ 'type', '=', 'text' ],
		];

		// OPTIONS GROUP
		$this->controls['direction'] = [
			'tab'         => 'content',
			'group'       => 'options',
			'label'       => esc_html__( 'Direction', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'horizontal' => esc_html__( 'Horizontal', 'bricks' ),
				'vertical'   => esc_html__( 'Vertical', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Horizontal', 'bricks' ),
		];

		$this->controls['reverse'] = [
			'tab'   => 'content',
			'group' => 'options',
			'label' => esc_html__( 'Reverse', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['pauseOnHover'] = [
			'tab'   => 'content',
			'group' => 'options',
			'label' => esc_html__( 'Pause on hover', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['repeat'] = [
			'tab'         => 'content',
			'group'       => 'options',
			'label'       => esc_html__( 'Repeat', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'placeholder' => 2,
		];

		$this->controls['duration'] = [
			'tab'         => 'content',
			'group'       => 'options',
			'label'       => esc_html__( 'Duration', 'bricks' ) . ' (s)',
			'type'        => 'number',
			'min'         => 1,
			'placeholder' => 20,
		];

		$this->controls['gap'] = [
			'tab'         => 'content',
			'group'       => 'options',
			'label'       => esc_html__( 'Gap', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
				],
			],
			'placeholder' => '1rem',
		];

		// CONTENT

		$this->controls['contentMargin'] = [
			'group' => 'itemsStyle',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.marquee__item',
				],
			],
		];

		$this->controls['contentPadding'] = [
			'group'   => 'itemsStyle',
			'label'   => esc_html__( 'Padding', 'bricks' ),
			'type'    => 'spacing',
			'css'     => [
				[
					'property' => 'padding',
					'selector' => '.marquee__item',
				],
			],
		];

		/* $this->controls['contentColor'] = [
			'group' => 'itemsStyle',
			'label' => esc_html__( 'Text color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.marquee__item',
				],
			],
		]; */

		$this->controls['contentBackgroundColor'] = [
			'group' => 'itemsStyle',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.marquee__item',
				],
			],
		];

		$this->controls['contentBorder'] = [
			'group'   => 'itemsStyle',
			'label'   => esc_html__( 'Border', 'bricks' ),
			'type'    => 'border',
			'css'     => [
				[
					'property' => 'border',
					'selector' => '.marquee__item',
				],
			],
		];
	}

	/**
	 * Enqueue element styles and scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'snn-marquee',
			SNN_URL_ASSETS . 'css/marquee.css',
			[],
			'1.0'
		);
	}

	/**
	 * Render marquee element
	 */
	public function render() {
		$settings = \Bricks\Helpers::get_normalized_image_settings( $this, $this->settings );

		// Get settings with defaults
		$type           = $settings['type'] ?? 'text';
		$direction      = $settings['direction'] ?? 'horizontal';
		$reverse        = isset( $settings['reverse'] );
		$pause_on_hover = isset( $settings['pauseOnHover'] );
		$repeat_setting = 2;
		if ( isset( $settings['repeat'] ) ) {
			$repeat_setting = intval( $settings['repeat'] );
		}
		$repeat = max( 2, $repeat_setting );
		$duration       = intval( $settings['duration'] ?? 20 );
		$gap            = $settings['gap'] ?? '1rem';

		$images     = $settings['items']['images'] ?? false;
		$size       = $settings['items']['size'] ?? BRICKS_DEFAULT_IMAGE_SIZE;
		$text_items = $settings['textItems'] ?? false;

		// STEP: Return placeholder for media type without images
		if ( $type === 'media' && ! $images ) {
			if ( ! empty( $settings['items']['useDynamicData'] ) ) {
				if ( ! \Bricks\Helpers::is_bricks_template( $this->post_id ) ) {
					return $this->render_element_placeholder(
						[
							'title' => esc_html__( 'Dynamic data is empty.', 'bricks' )
						]
					);
				}
			} else {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'No image selected.', 'bricks' ),
					]
				);
			}
		}

		// STEP: Return placeholder for text type without items
		if ( $type === 'text' && ! $text_items ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No text items added.', 'bricks' ),
				]
			);
		}

		// Build marquee classes
		$marquee_classes = [ 'marquee', "marquee--{$direction}" ];
		
		if ( $pause_on_hover ) {
			$marquee_classes[] = 'pausable';
		}
		if ( $reverse ) {
			$marquee_classes[] = 'reverse';
		}

		$this->set_attribute( '_root', 'class', $marquee_classes );
		$this->set_attribute( '_root', 'style', "--duration: {$duration}s; --gap: {$gap};" );
		
		// Render wrapper
		echo "<div {$this->render_attributes( '_root' )}>";

		// Render marquee track (internal container)
		echo '<div class="marquee__track">';

		// Render content repeated
		for ( $i = 0; $i < $repeat; $i++ ) {
			// Render marquee content (the animated container)
			echo '<div class="marquee__content">';

			// Render content based on type
			if ( $type === 'media' && $images ) {
				$this->render_images( $images, $size, $settings );
			} elseif ( $type === 'text' && $text_items ) {
				$this->render_text_items( $text_items, $settings );
			}

			echo '</div>'; // Close marquee__content
		}

		echo '</div>'; // Close marquee__track
		
		echo '</div>';
	}

	/**
	 * Render images for marquee
	 */
	private function render_images( $images, $size, $settings ) {
		foreach ( $images as $index => $item ) {
			$image_id = $item['id'] ?? false;

			if ( ! $image_id ) {
				continue;
			}

			$image_classes = [ 'marquee__item' ];

			// Image lazy load
			if ( $this->lazy_load() ) {
				$image_classes[] = 'bricks-lazy-hidden';
			}

			// CSS filters
			$image_classes[] = 'css-filter';

			echo '<div class="' . esc_attr( implode( ' ', $image_classes ) ) . '">';

			// STEP: Render image
			$image_atts        = [];
			$image_atts_string = '';

			// Set fetchpriority attribute
			$attribute_fetchpriority = ! empty( $settings['fetchpriorityAttribute'] ) ? esc_attr( $settings['fetchpriorityAttribute'] ) : '';
			if ( ! empty( $attribute_fetchpriority ) ) {
				$image_atts['fetchpriority'] = $attribute_fetchpriority;
				$image_atts_string          .= ' fetchpriority="' . $attribute_fetchpriority . '"';
			}

			// Set loading attribute
			$attribute_loading = ! empty( $settings['loadingAttribute'] ) ? esc_attr( $settings['loadingAttribute'] ) : '';
			if ( ! empty( $attribute_loading ) ) {
				$image_atts['loading'] = $attribute_loading;
				$image_atts_string    .= ' loading="' . $attribute_loading . '"';
			}

			echo wp_get_attachment_image( $image_id, $size, false, $image_atts );

			echo '</div>';
		}
	}

	/**
	 * Render text items for marquee
	 */
	private function render_text_items( $text_items, $settings ) {
		foreach ( $text_items as $index => $item ) {
			$text = $item['text'] ?? '';

			if ( empty( $text ) ) {
				continue;
			}

			// Render dynamic data if present
			$text = $this->render_dynamic_data( $text );

			echo '<div class="marquee__item">';
			echo esc_html( $text );
			echo '</div>';
		}
	}

	/**
	 * Render element HTML in builder (optional)
	 * 
	 * Adds element render scripts to wp_footer via x-template.
	 * Better performance than PHP 'render' function, which requires AJAX requests for every HTML re-render. 
	 * Works only with static, non-database data.
	 */
	public static function render_builder() {
		?>
			<script type="text/x-template" id="tmpl-bricks-element-marquee">
				<component 
					:is="tag"
					:class="[
						'marquee',
						'marquee--' + (settings.direction || 'horizontal'),
						{ 'pausable': settings.pauseOnHover }
					]"
					:style="{
						'--duration': (settings.duration || 20) + 's',
						'--gap': settings.gap || '1rem'
					}">
					<div class="marquee__track">
						<template v-for="i in Math.max(settings.repeat || 2, 2)">
							<div class="marquee__content">
								<!-- Media type -->
								<template v-if="settings.type === 'media'">
									<div 
										v-for="(item, index) in settings.items.images"
										:key="'repeat-' + i + '-' + index"
										class="marquee__item">
										<img 
											:src="item.url" 
											:alt="item.alt || ''"
											:loading="settings.loadingAttribute || 'lazy'"
											:fetchpriority="settings.fetchpriorityAttribute || 'auto'" />
									</div>
								</template>
								<!-- Text type -->
								<template v-else>
									<div 
										v-for="(item, index) in settings.textItems"
										:key="'repeat-' + i + '-' + index"
										class="marquee__item">
										{{ item.text }}
									</div>
								</template>
							</div>
						</template>
					</div>
				</component>
			</script>
		<?php
	}
}
