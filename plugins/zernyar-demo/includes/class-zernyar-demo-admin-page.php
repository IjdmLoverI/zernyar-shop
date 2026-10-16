<?php
/**
 * Tools → Zernyar demo.
 *
 * @package ZernyarDemo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings for running the demo on free hosting.
 */
final class Zernyar_Demo_Admin_Page {

	private const SLUG  = 'zernyar-demo';
	private const GROUP = 'zernyar_demo';

	/**
	 * Hooks the page in.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_page' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
	}

	/**
	 * Adds the page under Tools.
	 */
	public static function add_page(): void {
		add_management_page(
			__( 'Zernyar demo', 'zernyar-demo' ),
			__( 'Zernyar demo', 'zernyar-demo' ),
			'manage_options',
			self::SLUG,
			array( self::class, 'render' )
		);
	}

	/**
	 * Registers the options.
	 */
	public static function register_settings(): void {
		register_setting(
			self::GROUP,
			'zernyar_demo_alternate_cron',
			array(
				'type'              => 'string',
				'default'           => 'no',
				'sanitize_callback' => static fn ( $value ): string => 'yes' === $value ? 'yes' : 'no',
			)
		);
	}

	/**
	 * Renders the page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$alternate = 'yes' === get_option( 'zernyar_demo_alternate_cron', 'no' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Zernyar demo', 'zernyar-demo' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<h2><?php esc_html_e( 'Hosting', 'zernyar-demo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Scheduled tasks', 'zernyar-demo' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="zernyar_demo_alternate_cron" value="yes" <?php checked( $alternate ); ?>>
								<?php esc_html_e( 'Run scheduled tasks during page visits', 'zernyar-demo' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Turn this on when the host blocks requests from the site to itself, as InfinityFree does. WordPress then runs due tasks through a short redirect of the visitor\'s browser.', 'zernyar-demo' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
