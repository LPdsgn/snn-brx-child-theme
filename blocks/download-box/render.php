<?php
/**
 * Download Box — Server-side render
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content (unused).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$file_url         = esc_url( $attributes['fileUrl'] ?? '' );
$titolo           = esc_html( $attributes['titolo'] ?? '' );
$didascalia       = esc_html( $attributes['didascalia'] ?? '' );
$mostra_pulsante  = (bool) ( $attributes['mostraPulsante'] ?? false );
$testo_pulsante   = $attributes['testoPulsante'] ?? '';

if ( empty( $file_url ) || empty( $titolo ) ) {
	return;
}

$button_label = ! empty( $testo_pulsante ) ? esc_html( $testo_pulsante ) : 'Scarica qui il documento';

$margin_top    = $attributes['marginTop'] ?? '';
$margin_bottom = $attributes['marginBottom'] ?? '';
$inline_style  = '';
if ( $margin_top )    $inline_style .= 'margin-top:' . esc_attr( $margin_top ) . ';';
if ( $margin_bottom ) $inline_style .= 'margin-bottom:' . esc_attr( $margin_bottom ) . ';';

$wrapper_attributes = get_block_wrapper_attributes( array(
	'style' => $inline_style ?: null,
) );
?>
<a href="<?php echo $file_url; ?>" target="_blank" class="download-box" <?php echo $wrapper_attributes; ?>>
	<div class="download-box__icon-wrap">
		<svg class="download-box__icon-svg" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" fill="none">
			<path d="M17.5 2.5H5V27.5H25V10L17.5 2.5Z" stroke="#72333D" stroke-width="1.25" stroke-miterlimit="10"/>
			<path d="M17.5 2.5V10H25" stroke="#72333D" stroke-width="1.25" stroke-miterlimit="10"/>
		</svg>
	</div>
	<div class="download-box__info">
		<div class="download-box__text">
			<p><?php echo $titolo; ?></p>
			<?php if ( ! empty( $didascalia ) ) : ?>
				<p class="download-box__caption"><?php echo $didascalia; ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $mostra_pulsante ) : ?>
			<span class="download-box__button"><?php echo $button_label; ?><i class="ti-download"></i></span>
		<?php endif; ?>
	</div>
</a>
