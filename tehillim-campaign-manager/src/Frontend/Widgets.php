<?php
/**
 * Small data widgets / shortcodes (Elementor-friendly).
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\Database\AssignmentsRepository;
use TCM\PostTypes\CampaignPostType;
use TCM\Services\StatsService;
use TCM\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A family of lightweight shortcodes that surface a single figure or small UI
 * element, so a page builder can place campaign data anywhere.
 */
final class Widgets implements Registerable {

	/**
	 * @var AssignmentsRepository
	 */
	private $assignments;

	/**
	 * @var StatsService
	 */
	private $stats;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->assignments = new AssignmentsRepository();
		$this->stats       = new StatsService( $this->assignments );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		$map = array(
			'tehillim_progress_percent'   => 'percent',
			'tehillim_participants'       => 'participants',
			'tehillim_remaining_chapters' => 'remaining',
			'tehillim_remaining'          => 'remaining',
			'tehillim_completed_books'    => 'completed_books',
			'tehillim_books_done'         => 'completed_books',
			'tehillim_stats'              => 'stats_box',
			'tehillim_stats_box'          => 'stats_box',
			'tehillim_cta'                => 'cta',
			'tehillim_urgency'            => 'urgency',
			'tehillim_progress_bar'       => 'progress_bar',
			'tehillim_data'               => 'data',
			'tehillim_global_stats'       => 'global_stats',
		);
		foreach ( $map as $tag => $method ) {
			add_shortcode( $tag, array( $this, $method ) );
		}
	}

	/**
	 * Site-wide "live community" stats strip (across all campaigns).
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function global_stats( $atts ) {
		Assets::ensure();
		$totals = $this->assignments->global_totals();
		$query  = new \WP_Query(
			array(
				'post_type'      => CampaignPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		$active = (int) $query->found_posts;

		$items = array(
			array( $active, __( 'Active campaigns', 'tehillim-campaign-manager' ) ),
			array( $totals['done'], __( 'Chapters of Tehillim said', 'tehillim-campaign-manager' ) ),
			array( intdiv( $totals['done'], 150 ), __( 'Books completed', 'tehillim-campaign-manager' ) ),
			array( $totals['participants'], __( 'Participants', 'tehillim-campaign-manager' ) ),
		);

		$out = '<div class="tcm-wrap tcm-global-stats"><p class="tcm-section-eyebrow">'
			. esc_html__( 'The community - in real time', 'tehillim-campaign-manager' )
			. '</p><ul class="tcm-mini-stats">';
		foreach ( $items as $item ) {
			$out .= '<li class="tcm-mini-stat"><strong data-tcm-count="' . esc_attr( (string) (int) $item[0] ) . '">'
				. esc_html( number_format_i18n( (int) $item[0] ) ) . '</strong>'
				. esc_html( $item[1] ) . '</li>';
		}
		return $out . '</ul></div>';
	}

	/**
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function percent( $atts ) {
		$id = $this->id( $atts );
		return $id ? esc_html( $this->stats->for_campaign( $id )['percent'] . '%' ) : '';
	}

	/**
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function participants( $atts ) {
		$id = $this->id( $atts );
		return $id ? esc_html( (string) $this->assignments->participant_count( $id ) ) : '';
	}

	/**
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function remaining( $atts ) {
		$id = $this->id( $atts );
		return $id ? esc_html( (string) $this->stats->for_campaign( $id )['round_free'] ) : '';
	}

	/**
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function completed_books( $atts ) {
		$id = $this->id( $atts );
		return $id ? esc_html( (string) $this->stats->for_campaign( $id )['completed_books'] ) : '';
	}

	/**
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function progress_bar( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'label' => '1',
			),
			$atts
		);
		$id   = $this->id( $atts );
		if ( ! $id ) {
			return '';
		}
		$s     = $this->stats->for_campaign( $id );
		$label = '';
		if ( '0' !== (string) $atts['label'] ) {
			/* translators: %s: completion percentage. */
			$text  = sprintf( __( 'Progress: %s%%', 'tehillim-campaign-manager' ), $s['percent'] );
			$label = '<div class="tcm-progress-label">' . esc_html( $text ) . '</div>';
		}
		return '<div class="tcm-progress-wrap">' . $label
			. '<div class="tcm-progress" role="progressbar" aria-valuenow="' . esc_attr( $s['percent'] ) . '" aria-valuemin="0" aria-valuemax="100">'
			. '<span style="width:' . esc_attr( $s['percent'] ) . '%"></span></div></div>';
	}

	/**
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function stats_box( $atts ) {
		$id = $this->id( $atts );
		if ( ! $id ) {
			return '';
		}
		$s = $this->stats->for_campaign( $id );
		return '<div class="tcm-wrap"><div class="tcm-card"><ul class="tcm-mini-stats">'
			. '<li class="tcm-mini-stat"><strong>' . esc_html( $s['percent'] ) . '%</strong>' . esc_html__( 'Progress', 'tehillim-campaign-manager' ) . '</li>'
			. '<li class="tcm-mini-stat"><strong>' . esc_html( (string) $this->assignments->participant_count( $id ) ) . '</strong>' . esc_html__( 'Participants', 'tehillim-campaign-manager' ) . '</li>'
			. '<li class="tcm-mini-stat"><strong>' . esc_html( $s['round_free'] ) . '</strong>' . esc_html__( 'Free chapters', 'tehillim-campaign-manager' ) . '</li>'
			. '<li class="tcm-mini-stat"><strong>' . esc_html( $s['completed_books'] ) . '</strong>' . esc_html__( 'Books completed', 'tehillim-campaign-manager' ) . '</li>'
			. '</ul></div></div>';
	}

	/**
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function cta( $atts ) {
		$id = $this->id( $atts );
		if ( ! $id ) {
			return '';
		}
		$remaining = (int) $this->stats->for_campaign( $id )['round_free'];
		$threshold = (int) Options::get( 'finish_wave_threshold', 20 );
		$text      = ( $remaining > 0 && $remaining <= $threshold )
			? __( 'Help finish now', 'tehillim-campaign-manager' )
			: __( 'Join the reading', 'tehillim-campaign-manager' );
		return '<a class="tcm-btn tcm-cta" href="' . esc_url( get_permalink( $id ) ) . '#tcm">' . esc_html( $text ) . '</a>';
	}

	/**
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function urgency( $atts ) {
		$id = $this->id( $atts );
		if ( ! $id ) {
			return '';
		}
		$remaining = (int) $this->stats->for_campaign( $id )['round_free'];
		$threshold = (int) Options::get( 'finish_wave_threshold', 20 );
		if ( $remaining <= 0 ) {
			return '<span class="tcm-badge">' . esc_html__( 'The current book is fully taken', 'tehillim-campaign-manager' ) . '</span>';
		}
		if ( $remaining <= $threshold ) {
			/* translators: %d: number of chapters remaining. */
			$text = sprintf( __( 'Only %d chapters left to finish!', 'tehillim-campaign-manager' ), $remaining );
			return '<span class="tcm-badge tcm-urgent">' . esc_html( $text ) . '</span>';
		}
		/* translators: %d: number of free chapters. */
		$text = sprintf( __( '%d free chapters remaining', 'tehillim-campaign-manager' ), $remaining );
		return '<span class="tcm-badge">' . esc_html( $text ) . '</span>';
	}

	/**
	 * Single data point with optional prefix/suffix and conditional display.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function data( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'      => 0,
				'field'   => 'percent',
				'prefix'  => '',
				'suffix'  => '',
				'if_less' => '',
				'text'    => '',
			),
			$atts
		);
		$id   = $this->id( $atts );
		if ( ! $id ) {
			return '';
		}
		$s     = $this->stats->for_campaign( $id );
		$field = sanitize_key( $atts['field'] );
		$value = $this->field_value( $field, $id, $s );
		if ( null === $value ) {
			return '';
		}

		if ( '' !== $atts['if_less'] && is_numeric( $value ) ) {
			if ( $value >= (float) $atts['if_less'] ) {
				return '';
			}
			if ( '' !== $atts['text'] ) {
				return esc_html( str_replace( '{value}', (string) $value, $atts['text'] ) );
			}
		}
		return esc_html( $atts['prefix'] . $value . $atts['suffix'] );
	}

	/**
	 * Resolve a named field to a value.
	 *
	 * @param string $field Field key.
	 * @param int    $id    Campaign id.
	 * @param array  $s     Stats.
	 * @return mixed|null
	 */
	private function field_value( $field, $id, $s ) {
		switch ( $field ) {
			case 'participants':
				return $this->assignments->participant_count( $id );
			case 'percent':
			case 'progress':
			case 'progress_percent':
				return $s['percent'];
			case 'remaining':
			case 'available':
				return $s['round_free'];
			case 'completed':
				return $s['round_done'];
			case 'taken':
				return $s['round_taken'];
			case 'total':
				return 150;
			case 'books':
			case 'completed_books':
			case 'books_done':
				return $s['completed_books'];
			case 'books_target':
				return $s['target'];
			case 'bonus_books':
				return $s['bonus'];
			case 'round':
				return $s['round'];
			case 'title':
				return get_the_title( $id );
			case 'url':
				return get_permalink( $id );
			default:
				return null;
		}
	}

	/**
	 * Resolve the campaign id from attributes/context.
	 *
	 * @param array $atts Attributes.
	 * @return int
	 */
	private function id( $atts ) {
		$id = is_array( $atts ) && ! empty( $atts['id'] ) ? absint( $atts['id'] ) : 0;
		if ( ! $id && CampaignPostType::POST_TYPE === get_post_type( get_the_ID() ) ) {
			$id = (int) get_the_ID();
		}
		if ( $id && CampaignPostType::POST_TYPE !== get_post_type( $id ) ) {
			return 0;
		}
		return $id;
	}
}
