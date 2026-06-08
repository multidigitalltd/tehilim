<?php
/**
 * Subscriptions front-end module.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\Services\SubscriptionService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The [tehillim_subscribe] form and the unsubscribe link handler.
 */
final class Subscriptions implements Registerable {

	/**
	 * @var SubscriptionService
	 */
	private $service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service = new SubscriptionService();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_shortcode( 'tehillim_subscribe', array( $this, 'form' ) );
		add_action( 'template_redirect', array( $this, 'handle_unsubscribe' ) );
		add_action( 'tcm_campaign_announced', array( $this, 'on_campaign_announced' ) );
	}

	/**
	 * Notify "campaign alerts" subscribers when a new campaign launches.
	 *
	 * @param int $campaign_id Campaign post id.
	 * @return void
	 */
	public function on_campaign_announced( $campaign_id ) {
		$this->service->notify_campaign( (int) $campaign_id );
	}

	/**
	 * Render the subscribe form.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function form( $atts ) {
		Assets::ensure();
		$atts = shortcode_atts( array( 'list' => 'daily_chapter' ), $atts );
		return Templating::render(
			'partials/subscribe',
			array( 'list' => sanitize_key( $atts['list'] ) )
		);
	}

	/**
	 * Handle a one-click unsubscribe link.
	 *
	 * @return void
	 */
	public function handle_unsubscribe() {
		if ( empty( $_GET['tcm_unsub'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$token = sanitize_text_field( wp_unslash( $_GET['tcm_unsub'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->service->unsubscribe( $token );
		wp_safe_redirect( add_query_arg( 'tcm_unsubbed', '1', home_url( '/' ) ) );
		exit;
	}
}
