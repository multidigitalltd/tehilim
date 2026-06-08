<?php
/**
 * One-click demo content importer.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

use TCM\Database\AmbassadorsRepository;
use TCM\Database\AssignmentsRepository;
use TCM\Database\ReferralsRepository;
use TCM\PostTypes\AdPostType;
use TCM\PostTypes\CampaignPostType;
use TCM\PostTypes\PrayerPostType;
use TCM\Services\RoundService;
use TCM\Services\StatsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seeds a complete, lifelike sample site - sample campaigns (with chapters,
 * taken/completed activity, ambassadors and a leaderboard), segulot/prayers and
 * demo ad banners - so a fresh install looks exactly like the design preview,
 * the way a premium theme's "demo import" does. Idempotent: every created post
 * is tagged with a `_tcm_demo` meta and tracked in an option, so a second run
 * does nothing and removal is clean.
 */
final class DemoContent {

	const OPTION    = 'tcm_demo_imported';
	const DEMO_META = '_tcm_demo';

	/**
	 * Demo participant first names (used only for the activity feed display).
	 *
	 * @var string[]
	 */
	private $names = array(
		'שרה',
		'דוד',
		'רחל',
		'יוסף',
		'מרים',
		'אברהם',
		'לאה',
		'משה',
		'חנה',
		'יעקב',
		'אסתר',
		'יצחק',
		'דבורה',
		'נתן',
	);

	/**
	 * Whether demo content has already been imported.
	 *
	 * @return bool
	 */
	public function imported() {
		$state = get_option( self::OPTION, array() );
		return is_array( $state ) && ! empty( $state['campaigns'] );
	}

	/**
	 * Import all demo content. Safe to call repeatedly.
	 *
	 * @return array{campaigns:int,prayers:int,ads:int}
	 */
	public function import() {
		if ( $this->imported() ) {
			return array(
				'campaigns' => 0,
				'prayers'   => 0,
				'ads'       => 0,
			);
		}

		$state = array(
			'campaigns' => $this->campaigns(),
			'prayers'   => $this->prayers(),
			'ads'       => $this->ads(),
		);
		update_option( self::OPTION, $state );

		return array(
			'campaigns' => count( $state['campaigns'] ),
			'prayers'   => count( $state['prayers'] ),
			'ads'       => count( $state['ads'] ),
		);
	}

	/**
	 * Create the sample campaigns and seed lifelike activity.
	 *
	 * @return int[] Created campaign ids.
	 */
	private function campaigns() {
		$specs = array(
			array(
				'title'     => 'לרפואת רבקה בת לאה',
				'dedicated' => 'רבקה בת לאה - לרפואה שלמה',
				'target'    => 3,
				'bonus'     => 1,
				'content'   => '<p>מתאחדים יחד באמירת ספר תהילים שלם לרפואתה השלמה של רבקה בת לאה. כל פרק שתבחרו מצטרף למאמץ המשותף - בחרו פרק, אִמרו אותו, וסַמנו שהושלם.</p>',
				'taken'     => 11,
				'done'      => 28,
			),
			array(
				'title'     => 'להצלחת ולישועת עם ישראל',
				'dedicated' => 'לישועת כלל ישראל',
				'target'    => 5,
				'bonus'     => 0,
				'content'   => '<p>קמפיין קהילתי לאמירת תהילים לזכות עם ישראל. הצטרפו, שתפו חברים, וצפו במד ההתקדמות מתמלא יחד.</p>',
				'taken'     => 7,
				'done'      => 16,
			),
		);

		$ids = array();
		foreach ( $specs as $spec ) {
			$id = wp_insert_post(
				array(
					'post_type'    => CampaignPostType::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => $spec['title'],
					'post_content' => $spec['content'],
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			$id = (int) $id;
			update_post_meta( $id, '_tcm_target_books', (int) $spec['target'] );
			update_post_meta( $id, '_tcm_bonus_books', (int) $spec['bonus'] );
			update_post_meta( $id, '_tcm_status', 'active' );
			update_post_meta( $id, '_tcm_dedicated_to', $spec['dedicated'] );
			update_post_meta( $id, self::DEMO_META, 1 );

			$rounds = new RoundService();
			if ( ! $rounds->has_round( $id, 1 ) ) {
				$rounds->generate( $id, 1 );
			}
			$this->seed_activity( $id, (int) $spec['taken'], (int) $spec['done'] );
			StatsService::flush( $id );
			$ids[] = $id;
		}
		return $ids;
	}

	/**
	 * Claim and complete some chapters, and attribute them to demo ambassadors,
	 * so the progress bar, activity feed and leaderboard are populated.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $taken       How many chapters to leave "taken".
	 * @param int $done        How many chapters to mark "done".
	 * @return void
	 */
	private function seed_activity( $campaign_id, $taken, $done ) {
		$assignments = new AssignmentsRepository();
		$ambassadors = new AmbassadorsRepository();
		$referrals   = new ReferralsRepository();

		// Two demo ambassadors to drive the leaderboard.
		$amb = array();
		foreach ( array( 'משפחת לוי', 'קהילת אור החיים' ) as $i => $name ) {
			$row = $ambassadors->create(
				array(
					'campaign_id'   => $campaign_id,
					'user_id'       => 0,
					'name'          => $name,
					'email'         => '',
					'code'          => 'demo-' . $campaign_id . '-' . ( $i + 1 ),
					'goal_chapters' => 18,
				)
			);
			if ( $row ) {
				$amb[] = (int) $row->id;
			}
		}

		$total = $taken + $done;
		$rows  = $assignments->free_chapters( $campaign_id, 1, $total );
		$n     = 0;
		foreach ( $rows as $row ) {
			$name  = $this->names[ $n % count( $this->names ) ];
			$token = wp_generate_password( 20, false );
			$assignments->claim(
				(int) $row->id,
				array(
					'name'  => $name,
					'email' => 'demo+' . $campaign_id . '-' . $n . '@example.com',
					'phone' => '',
				),
				$token
			);
			if ( $amb ) {
				$referrals->record( $campaign_id, $amb[ $n % count( $amb ) ], (int) $row->id );
			}
			if ( $n < $done ) {
				$assignments->mark_done( (int) $row->id );
			}
			++$n;
		}
	}

	/**
	 * Create the sample prayers / segulot.
	 *
	 * @return int[] Created post ids.
	 */
	private function prayers() {
		$specs = array(
			array(
				'title'    => 'תפילת הדרך',
				'category' => 'תפילות',
				'content'  => '<p>יהי רצון מלפניך ה׳ אלוקינו ואלוקי אבותינו שתוליכנו לשלום ותצעידנו לשלום ותדריכנו לשלום…</p>',
			),
			array(
				'title'    => 'סגולה לרפואה - פרק כ׳ בתהילים',
				'category' => 'רפואה',
				'content'  => '<p>נהגו לומר פרק כ׳ (יענך ה׳ ביום צרה) כסגולה לרפואה ולישועה. מומלץ לאומרו בכוונה מיוחדת.</p>',
			),
			array(
				'title'    => 'סגולה לפרנסה טובה',
				'category' => 'פרנסה',
				'content'  => '<p>פרשת המן וסגולות חז״ל לפרנסה - אמירה יומית בכוונה לפתיחת שערי שפע.</p>',
			),
			array(
				'title'    => 'תיקון הכללי',
				'category' => 'תיקונים',
				'content'  => '<p>עשרה מזמורי תהילים שתיקן רבי נחמן מברסלב כתיקון כללי לנפש.</p>',
			),
		);

		$ids = array();
		foreach ( $specs as $spec ) {
			$id = wp_insert_post(
				array(
					'post_type'    => PrayerPostType::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => $spec['title'],
					'post_content' => $spec['content'],
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			$id = (int) $id;
			update_post_meta( $id, self::DEMO_META, 1 );
			wp_set_object_terms( $id, $spec['category'], PrayerPostType::TAXONOMY );
			$ids[] = $id;
		}
		return $ids;
	}

	/**
	 * Create the demo ad banners (never inside the reading area).
	 *
	 * @return int[] Created ad ids.
	 */
	private function ads() {
		$banner = TCM_PLUGIN_URL . 'assets/img/demo-banner.svg';
		$specs  = array(
			array(
				'title' => 'באנר לדוגמה - ראש הארכיון',
				'zone'  => 'archive_top',
			),
			array(
				'title' => 'באנר לדוגמה - סרגל צד',
				'zone'  => 'sidebar',
			),
		);

		$ids = array();
		foreach ( $specs as $spec ) {
			$id = wp_insert_post(
				array(
					'post_type'   => AdPostType::POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => $spec['title'],
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			$id = (int) $id;
			update_post_meta( $id, '_tcm_ad_zone', $spec['zone'] );
			update_post_meta( $id, '_tcm_ad_image', esc_url_raw( $banner ) );
			update_post_meta( $id, '_tcm_ad_url', 'https://example.com/' );
			update_post_meta( $id, '_tcm_ad_active', '1' );
			// AdService::pick() keys its schedule clauses on these meta rows, so
			// they must exist (empty = "no schedule limit") for the ad to match.
			update_post_meta( $id, '_tcm_ad_start', '' );
			update_post_meta( $id, '_tcm_ad_end', '' );
			update_post_meta( $id, self::DEMO_META, 1 );
			$ids[] = $id;
		}
		return $ids;
	}
}
