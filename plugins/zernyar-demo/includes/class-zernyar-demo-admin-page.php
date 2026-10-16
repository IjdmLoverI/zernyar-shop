<?php
/**
 * Tools → Zernyar demo.
 *
 * @package ZernyarDemo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Demo import and settings for running the demo on free hosting.
 */
final class Zernyar_Demo_Admin_Page {

	private const SLUG   = 'zernyar-demo';
	private const GROUP  = 'zernyar_demo';
	private const ACTION = 'zernyar_demo_seed';

	/**
	 * Seconds of work per request, well below the 30–60 s limit of free hosts.
	 */
	private const BUDGET = 15;

	/**
	 * Hooks the page in.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_page' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'seed' ) );
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
	 * Runs part of the import and comes back to the page.
	 */
	public static function seed(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to import the demo content.', 'zernyar-demo' ), 403 );
		}
		check_admin_referer( self::ACTION );

		$finished = ( new Zernyar_Demo_Seeder( ZERNYAR_DEMO_CATALOG ) )->run( self::BUDGET );

		wp_safe_redirect( add_query_arg( 'seed', $finished ? 'done' : 'continue', admin_url( 'tools.php?page=' . self::SLUG ) ) );
		exit;
	}

	/**
	 * Renders the page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$alternate = 'yes' === get_option( 'zernyar_demo_alternate_cron', 'no' );
		$progress  = ( new Zernyar_Demo_Seeder( ZERNYAR_DEMO_CATALOG ) )->progress();
		$status    = isset( $_GET['seed'] ) ? sanitize_key( wp_unslash( $_GET['seed'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		$continue  = 'continue' === $status && $progress['running'];
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Zernyar demo', 'zernyar-demo' ); ?></h1>

			<?php if ( 'done' === $status ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'The demo content is imported.', 'zernyar-demo' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Demo content', 'zernyar-demo' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %d: number of products. */
					esc_html__( 'Imports %d products with images, the attributes and categories, the About, Delivery, Contacts and Terms pages and the menu. Existing demo items are updated, nothing is duplicated.', 'zernyar-demo' ),
					(int) $progress['products']
				);
				?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="zernyar-demo-seed">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<?php wp_nonce_field( self::ACTION ); ?>
				<?php if ( $continue ) : ?>
					<p>
						<?php
						printf(
							/* translators: 1: import step, 2: product number. */
							esc_html__( 'Importing: %1$s, item %2$d. The next part starts by itself.', 'zernyar-demo' ),
							esc_html( $progress['step'] ),
							(int) $progress['item']
						);
						?>
					</p>
					<?php submit_button( __( 'Continue the import', 'zernyar-demo' ), 'primary', 'submit', false ); ?>
					<script>document.getElementById( 'zernyar-demo-seed' ).submit();</script>
				<?php else : ?>
					<?php submit_button( __( 'Import demo content', 'zernyar-demo' ), 'primary', 'submit', false ); ?>
				<?php endif; ?>
			</form>

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
