<?php
/**
 * Hebrew date + daily zmanim front-end.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\Services\ZmanimService;
use TCM\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The [tehillim_zmanim] shortcode renders a card with today's Hebrew date and
 * the core daily zmanim for the configured city.
 */
final class Zmanim implements Registerable {

	/**
	 * @var ZmanimService
	 */
	private $service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service = new ZmanimService();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_shortcode( 'tehillim_zmanim', array( $this, 'render' ) );
	}

	/**
	 * Render the zmanim card.
	 *
	 * @param array $atts Shortcode attributes (city override).
	 * @return string
	 */
	public function render( $atts ) {
		Assets::ensure();
		$atts = shortcode_atts( array( 'city' => '' ), $atts, 'tehillim_zmanim' );

		$city_key = sanitize_key( '' !== $atts['city'] ? $atts['city'] : (string) Options::get( 'zmanim_city' ) );
		if ( ! isset( ZmanimService::cities()[ $city_key ] ) ) {
			$city_key = 'tel_aviv';
		}
		$city = ZmanimService::city( $city_key );

		return Templating::render(
			'partials/zmanim',
			array(
				'hebrew_date' => ZmanimService::hebrew_date(),
				'city_label'  => $city['label'],
				'zmanim'      => $this->service->for_city( $city_key ),
				'labels'      => ZmanimService::labels(),
			)
		);
	}
}
