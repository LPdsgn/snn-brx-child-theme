<?php
/**
 * Bricks Builder element: Lucide Icon
 *
 * Bricks 2.x doesn't expose a filter to register new icon-font libraries
 * in the native Icon picker, so we ship a dedicated element that mirrors
 * the built-in Icon element's controls (color, size, link) but uses a
 * searchable select populated from assets/css/libs/lucide-icons.css.
 *
 * Frontend markup: <i class="icon-<name>"></i> (wrapped in <a> when link is set)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Snn_Element_Icon_Lucide extends \Bricks\Element {
	public $category = 'snn';
	public $name     = 'lucide-icon';
	public $icon     = 'ti-star';

	public function get_label() {
		return esc_html__( 'Lucide Icon', 'snn' );
	}

	public function set_controls() {
		// Build the options list (assoc: class name => human label).
		$classes = function_exists( 'snn_get_lucide_icon_classes' ) ? snn_get_lucide_icon_classes() : [];
		$options = [];

		foreach ( $classes as $class ) {
			// Strip the 'icon-' prefix in the label for readability; the value stays full class name.
			$label             = str_replace( '-', ' ', substr( $class, 5 ) );
			$options[ $class ] = $label . '  (' . $class . ')';
		}

		$this->controls['iconClass'] = [
			'label'       => esc_html__( 'Icon', 'snn' ),
			'type'        => 'select',
			'options'     => $options,
			'searchable'  => true,
			'default'     => 'icon-star',
			'description' => sprintf(
				/* translators: %d: icon count */
				esc_html__( '%d Lucide icons available. Type to filter.', 'snn' ),
				count( $options )
			),
		];

		$this->controls['iconColor'] = [
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '',
				],
			],
		];

		$this->controls['iconSize'] = [
			'label' => esc_html__( 'Size', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'font-size',
					'selector' => '',
				],
			],
		];

		$this->controls['link'] = [
			'label' => esc_html__( 'Link', 'bricks' ),
			'type'  => 'link',
		];
	}

	public function render() {
		$settings   = $this->settings;
		$icon_class = isset( $settings['iconClass'] ) ? sanitize_html_class( $settings['iconClass'] ) : '';
		$link       = ! empty( $settings['link'] ) && bricks_is_frontend() ? $settings['link'] : false;

		if ( ! $icon_class ) {
			return $this->render_element_placeholder( [
				'title' => esc_html__( 'No icon selected.', 'snn' ),
			] );
		}

		// Make sure the icon class is registered in our CSS, else bail with placeholder.
		$known = function_exists( 'snn_get_lucide_icon_classes' ) ? snn_get_lucide_icon_classes() : [];
		if ( ! in_array( $icon_class, $known, true ) ) {
			return $this->render_element_placeholder( [
				'title' => esc_html__( 'Unknown Lucide icon class.', 'snn' ),
			] );
		}

		// Add the chosen icon class to the root <i>
		$this->set_attribute( '_root', 'class', [ $icon_class ] );

		if ( $link ) {
			// Link wrapper handling, mirroring built-in Icon element behaviour
			$custom_attributes = $this->get_custom_attributes( $settings );
			if ( is_array( $custom_attributes ) ) {
				foreach ( $custom_attributes as $key => $value ) {
					if ( isset( $this->attributes['_root'][ $key ] ) ) {
						unset( $this->attributes['_root'][ $key ] );
					}
				}
			}

			$this->set_link_attributes( 'link', $link );
			$this->set_attribute( 'link', 'class', 'bricks-link-wrapper' );

			echo "<a {$this->render_attributes( 'link', true )}>";
			echo "<i {$this->render_attributes( '_root' )}></i>";
			echo '</a>';
		} else {
			echo "<i {$this->render_attributes( '_root' )}></i>";
		}
	}

	public static function render_builder() {
		?>
		<script type="text/x-template" id="tmpl-bricks-element-lucide-icon">
			<i :class="settings.iconClass || 'icon-star'"></i>
		</script>
		<?php
	}
}
