<?php
/**
 * Language switcher: "UA EN" links to the current page in each published language.
 *
 * @package Zernyar
 */

if ( ! function_exists( 'trp_custom_language_switcher' ) ) {
	return;
}

$zernyar_languages = trp_custom_language_switcher();
if ( count( $zernyar_languages ) < 2 ) {
	return;
}

$zernyar_current = get_locale();
?>
<nav class="zernyar-lang" aria-label="<?php esc_attr_e( 'Language', 'zernyar' ); ?>" data-no-translation>
	<?php foreach ( $zernyar_languages as $zernyar_code => $zernyar_language ) : ?>
		<?php
		$zernyar_short = 'uk' === $zernyar_code ? 'UA' : strtoupper( substr( (string) $zernyar_code, 0, 2 ) );
		$zernyar_lang  = str_replace( '_', '-', (string) $zernyar_code );
		?>
		<a
			href="<?php echo esc_url( $zernyar_language['current_page_url'] ); ?>"
			lang="<?php echo esc_attr( $zernyar_lang ); ?>"
			hreflang="<?php echo esc_attr( $zernyar_lang ); ?>"
			title="<?php echo esc_attr( $zernyar_language['language_name'] ); ?>"
			<?php echo $zernyar_code === $zernyar_current ? 'aria-current="true"' : ''; ?>
		><?php echo esc_html( $zernyar_short ); ?></a>
	<?php endforeach; ?>
</nav>
