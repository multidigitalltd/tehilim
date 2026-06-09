<?php
/**
 * Hebrew date + daily zmanim (halachic times).
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Computes the Hebrew date and the core daily zmanim for a configured city,
 * with no external API: sunrise/sunset come from PHP's built-in date_sun_info()
 * and the derived times use proportional ("zmaniyot") hours. The Hebrew date
 * uses ext-intl when available and degrades gracefully otherwise.
 */
final class ZmanimService {

	const TZ = 'Asia/Jerusalem';

	/**
	 * Supported cities (Israel) with coordinates.
	 *
	 * @return array<string,array{label:string,lat:float,lng:float}>
	 */
	public static function cities() {
		return array(
			'tel_aviv'   => array(
				'label' => __( 'Tel Aviv', 'tehillim-campaign-manager' ),
				'lat'   => 32.0853,
				'lng'   => 34.7818,
			),
			'jerusalem'  => array(
				'label' => __( 'Jerusalem', 'tehillim-campaign-manager' ),
				'lat'   => 31.7683,
				'lng'   => 35.2137,
			),
			'bnei_brak'  => array(
				'label' => __( 'Bnei Brak', 'tehillim-campaign-manager' ),
				'lat'   => 32.0807,
				'lng'   => 34.8338,
			),
			'haifa'      => array(
				'label' => __( 'Haifa', 'tehillim-campaign-manager' ),
				'lat'   => 32.7940,
				'lng'   => 34.9896,
			),
			'beer_sheva' => array(
				'label' => __( 'Beer Sheva', 'tehillim-campaign-manager' ),
				'lat'   => 31.2518,
				'lng'   => 34.7913,
			),
		);
	}

	/**
	 * Resolve a city key to its data (falls back to Tel Aviv).
	 *
	 * @param string $key City key.
	 * @return array{label:string,lat:float,lng:float}
	 */
	public static function city( $key ) {
		$cities = self::cities();
		return $cities[ $key ] ?? $cities['tel_aviv'];
	}

	/**
	 * Hebrew date string (e.g. "כ״ב בסיון תשפ״ו"). Empty when no formatter is
	 * available.
	 *
	 * @param int|null $timestamp Unix timestamp (defaults to now).
	 * @return string
	 */
	public static function hebrew_date( $timestamp = null ) {
		$timestamp = null === $timestamp ? time() : (int) $timestamp;

		if ( class_exists( '\IntlDateFormatter' ) ) {
			$fmt = new \IntlDateFormatter(
				'he_IL@calendar=hebrew',
				\IntlDateFormatter::LONG,
				\IntlDateFormatter::NONE,
				self::TZ,
				\IntlDateFormatter::TRADITIONAL
			);
			$out = $fmt->format( $timestamp );
			if ( is_string( $out ) && '' !== $out ) {
				return $out;
			}
		}

		if ( function_exists( 'jdtojewish' ) ) {
			$tz   = new \DateTimeZone( self::TZ );
			$date = new \DateTime( '@' . $timestamp );
			$date->setTimezone( $tz );
			$jd = gregoriantojd( (int) $date->format( 'n' ), (int) $date->format( 'j' ), (int) $date->format( 'Y' ) );
			return (string) jdtojewish( $jd, false );
		}

		return '';
	}

	/**
	 * Core daily zmanim for a city as a label => "HH:MM" map (Asia/Jerusalem).
	 * Returns an empty array on the rare day date_sun_info cannot resolve.
	 *
	 * @param string   $city_key  City key.
	 * @param int|null $timestamp Unix timestamp within the target day (now).
	 * @return array<string,string>
	 */
	public function for_city( $city_key, $timestamp = null ) {
		$timestamp = null === $timestamp ? time() : (int) $timestamp;
		$city      = self::city( $city_key );

		$info = date_sun_info( $timestamp, $city['lat'], $city['lng'] );
		if ( ! is_array( $info ) || empty( $info['sunrise'] ) || empty( $info['sunset'] ) ) {
			return array();
		}

		$sunrise = (int) $info['sunrise'];
		$sunset  = (int) $info['sunset'];
		$transit = ! empty( $info['transit'] ) ? (int) $info['transit'] : (int) ( ( $sunrise + $sunset ) / 2 );
		$hour    = ( $sunset - $sunrise ) / 12; // Proportional ("zmanit") hour, seconds.

		$times = array(
			'alot'          => $sunrise - ( 72 * 60 ),
			'sunrise'       => $sunrise,
			'sof_shma'      => (int) round( $sunrise + ( 3 * $hour ) ),
			'sof_tfila'     => (int) round( $sunrise + ( 4 * $hour ) ),
			'chatzot'       => $transit,
			'mincha_gedola' => (int) round( $transit + ( 0.5 * $hour ) ),
			'plag'          => (int) round( $sunset - ( 1.25 * $hour ) ),
			'sunset'        => $sunset,
			'tzeit'         => $sunset + ( 18 * 60 ),
		);

		$tz  = new \DateTimeZone( self::TZ );
		$out = array();
		foreach ( $times as $key => $ts ) {
			$dt = new \DateTime( '@' . (int) $ts );
			$dt->setTimezone( $tz );
			$out[ $key ] = $dt->format( 'H:i' );
		}
		return $out;
	}

	/**
	 * Human labels for each zman key (Hebrew via translation).
	 *
	 * @return array<string,string>
	 */
	public static function labels() {
		return array(
			'alot'          => __( 'Dawn', 'tehillim-campaign-manager' ),
			'sunrise'       => __( 'Sunrise', 'tehillim-campaign-manager' ),
			'sof_shma'      => __( 'Latest Shema', 'tehillim-campaign-manager' ),
			'sof_tfila'     => __( 'Latest Shacharit', 'tehillim-campaign-manager' ),
			'chatzot'       => __( 'Midday', 'tehillim-campaign-manager' ),
			'mincha_gedola' => __( 'Mincha Gedola', 'tehillim-campaign-manager' ),
			'plag'          => __( 'Plag HaMincha', 'tehillim-campaign-manager' ),
			'sunset'        => __( 'Sunset', 'tehillim-campaign-manager' ),
			'tzeit'         => __( 'Nightfall', 'tehillim-campaign-manager' ),
		);
	}
}
