<?php
/**
 * The booking form: storage, validation and delivery.
 *
 * Every submission is saved as a private post so nothing is lost if mail
 * fails, then a notification is sent. Works with JavaScript (fetch) and
 * without it (plain POST to admin-post.php).
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

const SAVTA_FORM_NONCE   = 'savta_lead_form';
const SAVTA_LEAD_TYPE    = 'savta_lead';
const SAVTA_RATE_LIMIT   = 5;
const SAVTA_RATE_WINDOW  = HOUR_IN_SECONDS;
const SAVTA_MIN_FILL_SEC = 3;

/**
 * Registers the private post type that stores submissions.
 *
 * @return void
 */
function savta_register_lead_type(): void {
	register_post_type(
		SAVTA_LEAD_TYPE,
		array(
			'labels'              => array(
				'name'          => __( 'פניות', 'savta-al-hasafsal' ),
				'singular_name' => __( 'פנייה', 'savta-al-hasafsal' ),
				'menu_name'     => __( 'פניות', 'savta-al-hasafsal' ),
				'all_items'     => __( 'כל הפניות', 'savta-al-hasafsal' ),
				'search_items'  => __( 'חיפוש פניות', 'savta-al-hasafsal' ),
				'not_found'     => __( 'אין פניות עדיין.', 'savta-al-hasafsal' ),
				'edit_item'     => __( 'פרטי הפנייה', 'savta-al-hasafsal' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-heart',
			'menu_position'       => 26,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'capabilities'        => array(
				'create_posts' => 'do_not_allow',
			),
		)
	);
}
add_action( 'init', 'savta_register_lead_type' );

/**
 * The fields the form accepts, with their labels for the admin and the mail.
 *
 * @return array<string,string>
 */
function savta_lead_fields(): array {
	return array(
		'name'    => __( 'שם פרטי', 'savta-al-hasafsal' ),
		'phone'   => __( 'טלפון', 'savta-al-hasafsal' ),
		'city'    => __( 'עיר', 'savta-al-hasafsal' ),
		'age'     => __( 'גיל', 'savta-al-hasafsal' ),
		'contact' => __( 'דרך קשר מועדפת', 'savta-al-hasafsal' ),
		'slot'    => __( 'מועד מבוקש', 'savta-al-hasafsal' ),
		'topic'   => __( 'נושא השיחה', 'savta-al-hasafsal' ),
		'consent' => __( 'אישור', 'savta-al-hasafsal' ),
	);
}

/**
 * The days of the week the calendar offers, from the page's settings.
 *
 * @return int[] 0 (Sunday) … 6 (Saturday).
 */
function savta_calendar_days(): array {
	$days = array();

	foreach ( explode( ',', (string) savta_get( 'cal_days' ) ) as $day ) {
		$day = trim( $day );

		if ( '' !== $day && is_numeric( $day ) && (int) $day >= 0 && (int) $day <= 6 ) {
			$days[] = (int) $day;
		}
	}

	return array_values( array_unique( $days ) );
}

/**
 * The time slots the calendar offers, one per line in the page's settings.
 *
 * @return string[]
 */
function savta_calendar_slots(): array {
	$slots = array();

	foreach ( preg_split( '/\r\n|\r|\n/', (string) savta_get( 'cal_slots' ) ) as $slot ) {
		$slot = trim( $slot );

		if ( '' !== $slot ) {
			$slots[] = $slot;
		}
	}

	return $slots;
}

/**
 * Slots that are already taken, so the calendar can disable them.
 *
 * A lead that booked a slot holds it; the list is only the future ones.
 *
 * @return string[] "YYYY-MM-DD HH:MM–HH:MM" values.
 */
function savta_booked_slots(): array {
	$leads = get_posts(
		array(
			'post_type'      => SAVTA_LEAD_TYPE,
			'post_status'    => 'private',
			'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Future bookings only; a few dozen at most.
			'fields'         => 'ids',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded by date, one query per page view.
			'meta_query'     => array(
				array(
					'key'     => '_savta_slot_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		)
	);

	$booked = array();

	foreach ( $leads as $lead_id ) {
		$slot = (string) get_post_meta( $lead_id, '_savta_slot', true );

		if ( '' !== $slot ) {
			$booked[] = $slot;
		}
	}

	return array_values( array_unique( $booked ) );
}

/**
 * Configuration the front-end script needs.
 *
 * @return array<string,mixed>
 */
function savta_form_config(): array {
	return array(
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( SAVTA_FORM_NONCE ),
		'days'     => savta_calendar_days(),
		'slots'    => savta_calendar_slots(),
		'count'    => max( 1, min( 12, (int) savta_get( 'cal_count' ) ) ),
		'booked'   => savta_booked_slots(),
		'dayNames' => array(
			__( 'יום ראשון', 'savta-al-hasafsal' ),
			__( 'יום שני', 'savta-al-hasafsal' ),
			__( 'יום שלישי', 'savta-al-hasafsal' ),
			__( 'יום רביעי', 'savta-al-hasafsal' ),
			__( 'יום חמישי', 'savta-al-hasafsal' ),
			__( 'יום שישי', 'savta-al-hasafsal' ),
			__( 'שבת', 'savta-al-hasafsal' ),
		),
		'labels'   => array(
			'open'   => (string) savta_get( 'form_cal_open' ),
			'close'  => (string) savta_get( 'form_cal_close' ),
			'empty'  => (string) savta_get( 'form_slot_empty' ),
			'taken'  => __( 'תפוס', 'savta-al-hasafsal' ),
			/* translators: %s: the chosen day and time. */
			'picked' => __( 'נבחר המועד: %s', 'savta-al-hasafsal' ),
		),
		'messages' => savta_form_messages(),
	);
}

/**
 * The messages the form shows, keyed by error code.
 *
 * @return array<string,string>
 */
function savta_form_messages(): array {
	return array(
		'ok'      => __( 'תודה, הפרטים התקבלו.', 'savta-al-hasafsal' ),
		'name'    => __( 'נא למלא שם.', 'savta-al-hasafsal' ),
		'phone'   => __( 'נא למלא מספר טלפון תקין.', 'savta-al-hasafsal' ),
		'age'     => __( 'נא למלא גיל בין 16 ל־120.', 'savta-al-hasafsal' ),
		'consent' => __( 'נא לאשר את תיבת הסימון.', 'savta-al-hasafsal' ),
		'slot'    => __( 'המועד שנבחר אינו זמין יותר. נא לבחור מועד אחר.', 'savta-al-hasafsal' ),
		'rate'    => __( 'נשלחו יותר מדי פניות. אפשר לנסות שוב מאוחר יותר.', 'savta-al-hasafsal' ),
		'nonce'   => __( 'הטופס פג תוקף. נא לרענן את העמוד ולנסות שוב.', 'savta-al-hasafsal' ),
		'spam'    => __( 'הפנייה לא התקבלה. נא לנסות שוב.', 'savta-al-hasafsal' ),
		'network' => __( 'לא הצלחנו לשלוח. נא לבדוק את החיבור ולנסות שוב.', 'savta-al-hasafsal' ),
		'server'  => __( 'משהו השתבש אצלנו. נא לנסות שוב בעוד רגע.', 'savta-al-hasafsal' ),
		'sending' => __( 'שולחים…', 'savta-al-hasafsal' ),
	);
}

/**
 * A privacy-preserving key for the visitor, for rate limiting only.
 *
 * @return string
 */
function savta_visitor_key(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return 'savta_rl_' . hash( 'sha256', $ip . wp_salt( 'nonce' ) );
}

/**
 * Whether the visitor has already sent the hour's allowance of submissions.
 *
 * Only stored submissions count, so a visitor correcting a typo is never
 * locked out by their own retries.
 *
 * @return bool
 */
function savta_rate_limited(): bool {
	return (int) get_transient( savta_visitor_key() ) >= SAVTA_RATE_LIMIT;
}

/**
 * Records one stored submission against the visitor's allowance.
 *
 * @return void
 */
function savta_rate_count(): void {
	$key = savta_visitor_key();
	set_transient( $key, (int) get_transient( $key ) + 1, SAVTA_RATE_WINDOW );
}

/**
 * Whether a phone number looks real: 7–15 digits once formatting is stripped.
 *
 * @param string $phone Raw phone.
 * @return bool
 */
function savta_valid_phone( string $phone ): bool {
	$digits = preg_replace( '/\D/', '', $phone );

	return is_string( $digits ) && strlen( $digits ) >= 7 && strlen( $digits ) <= 15;
}

/**
 * Validates a slot against the calendar: a configured time on an offered
 * weekday, in the future, and not already taken.
 *
 * @param string $slot "YYYY-MM-DD HH:MM–HH:MM".
 * @return bool
 */
function savta_valid_slot( string $slot ): bool {
	if ( 1 !== preg_match( '/^(\d{4}-\d{2}-\d{2}) (.+)$/u', $slot, $match ) ) {
		return false;
	}

	$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $match[1], wp_timezone() );

	if ( ! $date instanceof DateTimeImmutable || $date->format( 'Y-m-d' ) !== $match[1] ) {
		return false;
	}

	$today = new DateTimeImmutable( 'today', wp_timezone() );

	if ( $date <= $today ) {
		return false;
	}

	if ( ! in_array( (int) $date->format( 'w' ), savta_calendar_days(), true ) ) {
		return false;
	}

	if ( ! in_array( $match[2], savta_calendar_slots(), true ) ) {
		return false;
	}

	return ! in_array( $slot, savta_booked_slots(), true );
}

/**
 * Reads, validates and stores a submission.
 *
 * @return array{ok:bool,code:string,id?:int}
 */
function savta_process_submission(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified right here.
	$nonce = isset( $_POST['savta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['savta_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, SAVTA_FORM_NONCE ) ) {
		return array(
			'ok'   => false,
			'code' => 'nonce',
		);
	}

	// Honeypot: a real visitor never sees this field.
	if ( ! empty( $_POST['website'] ) ) {
		return array(
			'ok'   => false,
			'code' => 'spam',
		);
	}

	// A form filled in under three seconds was filled by a script.
	$started = isset( $_POST['savta_t'] ) ? (int) $_POST['savta_t'] : 0;

	if ( $started > 0 && ( time() - $started ) < SAVTA_MIN_FILL_SEC ) {
		return array(
			'ok'   => false,
			'code' => 'spam',
		);
	}

	if ( savta_rate_limited() ) {
		return array(
			'ok'   => false,
			'code' => 'rate',
		);
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$city    = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
	$age_raw = isset( $_POST['age'] ) ? sanitize_text_field( wp_unslash( $_POST['age'] ) ) : '';
	$contact = isset( $_POST['contact'] ) ? sanitize_key( wp_unslash( $_POST['contact'] ) ) : '';
	$slot    = isset( $_POST['slot'] ) ? sanitize_text_field( wp_unslash( $_POST['slot'] ) ) : '';
	$topic   = isset( $_POST['topic'] ) ? sanitize_textarea_field( wp_unslash( $_POST['topic'] ) ) : '';
	$consent = ! empty( $_POST['consent'] );
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	$name  = mb_substr( $name, 0, 80 );
	$city  = mb_substr( $city, 0, 80 );
	$topic = mb_substr( $topic, 0, 2000 );

	if ( '' === $name ) {
		return array(
			'ok'   => false,
			'code' => 'name',
		);
	}

	if ( ! savta_valid_phone( $phone ) ) {
		return array(
			'ok'   => false,
			'code' => 'phone',
		);
	}

	$age = '';
	if ( '' !== $age_raw ) {
		if ( ! is_numeric( $age_raw ) || (int) $age_raw < 16 || (int) $age_raw > 120 ) {
			return array(
				'ok'   => false,
				'code' => 'age',
			);
		}
		$age = (string) (int) $age_raw;
	}

	if ( ! in_array( $contact, array( 'phone', 'whatsapp' ), true ) ) {
		$contact = 'phone';
	}

	if ( '' !== $slot && ! savta_valid_slot( $slot ) ) {
		return array(
			'ok'   => false,
			'code' => 'slot',
		);
	}

	if ( ! $consent ) {
		return array(
			'ok'   => false,
			'code' => 'consent',
		);
	}

	$lead_id = wp_insert_post(
		array(
			'post_type'   => SAVTA_LEAD_TYPE,
			'post_status' => 'private',
			'post_title'  => $name . ' · ' . $phone,
		),
		true
	);

	if ( is_wp_error( $lead_id ) ) {
		return array(
			'ok'   => false,
			'code' => 'server',
		);
	}

	$values = array(
		'name'    => $name,
		'phone'   => $phone,
		'city'    => $city,
		'age'     => $age,
		'contact' => 'whatsapp' === $contact ? (string) savta_get( 'form_contact_wa' ) : (string) savta_get( 'form_contact_phone' ),
		'slot'    => $slot,
		'topic'   => $topic,
		'consent' => 'כן',
	);

	foreach ( $values as $key => $value ) {
		update_post_meta( $lead_id, '_savta_' . $key, $value );
	}

	if ( '' !== $slot ) {
		update_post_meta( $lead_id, '_savta_slot_date', substr( $slot, 0, 10 ) );
	}

	savta_rate_count();

	savta_notify_lead( $lead_id, $values );

	return array(
		'ok'   => true,
		'code' => 'ok',
		'id'   => (int) $lead_id,
	);
}

/**
 * Sends the notification mail, when it is switched on.
 *
 * @param int                  $lead_id Saved lead.
 * @param array<string,string> $values  Field values.
 * @return void
 */
function savta_notify_lead( int $lead_id, array $values ): void {
	if ( ! savta_option( 'savta_form_mail' ) ) {
		return;
	}

	$recipient = sanitize_email( (string) savta_option( 'savta_form_recipient' ) );

	if ( ! is_email( $recipient ) ) {
		$recipient = (string) get_option( 'admin_email' );
	}

	$labels = savta_lead_fields();
	$lines  = array();

	foreach ( $values as $key => $value ) {
		if ( '' === $value ) {
			continue;
		}
		$lines[] = ( $labels[ $key ] ?? $key ) . ': ' . $value;
	}

	$lines[] = '';
	$lines[] = __( 'לצפייה בפנייה:', 'savta-al-hasafsal' ) . ' ' . admin_url( 'post.php?post=' . $lead_id . '&action=edit' );

	/* translators: %s: name of the person who submitted the form. */
	$subject = sprintf( __( 'פנייה חדשה מהאתר: %s', 'savta-al-hasafsal' ), $values['name'] );

	$sent = wp_mail( $recipient, $subject, implode( "\n", $lines ) );

	if ( ! $sent ) {
		// The lead is already saved; the failure only affects the notification.
		update_post_meta( $lead_id, '_savta_mail_failed', '1' );
	}
}

/**
 * Handles the JavaScript submission (fetch to admin-ajax.php).
 *
 * @return void
 */
function savta_ajax_submit(): void {
	$result   = savta_process_submission();
	$messages = savta_form_messages();
	$payload  = array(
		'ok'      => $result['ok'],
		'code'    => $result['code'],
		'message' => $messages[ $result['code'] ] ?? $messages['server'],
	);

	if ( $result['ok'] ) {
		wp_send_json_success( $payload );
	}

	wp_send_json_error( $payload, 'rate' === $result['code'] ? 429 : 400 );
}
add_action( 'wp_ajax_savta_lead', 'savta_ajax_submit' );
add_action( 'wp_ajax_nopriv_savta_lead', 'savta_ajax_submit' );

/**
 * Hands out a fresh nonce, so a page served from a full-page cache still
 * submits with a valid one.
 *
 * @return void
 */
function savta_ajax_nonce(): void {
	wp_send_json_success( array( 'nonce' => wp_create_nonce( SAVTA_FORM_NONCE ) ) );
}
add_action( 'wp_ajax_savta_nonce', 'savta_ajax_nonce' );
add_action( 'wp_ajax_nopriv_savta_nonce', 'savta_ajax_nonce' );

/**
 * Handles the plain POST submission (no JavaScript) and returns to the form.
 *
 * @return void
 */
function savta_post_submit(): void {
	$result = savta_process_submission();

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified inside savta_process_submission(); only used to pick the page to return to.
	$back = isset( $_POST['_wp_http_referer'] ) ? esc_url_raw( wp_unslash( $_POST['_wp_http_referer'] ) ) : '';
	$back = wp_validate_redirect( $back, home_url( '/' ) );
	$back = remove_query_arg( array( 'savta', 'savta_code' ), $back );
	$back = strtok( $back, '#' );
	$back = add_query_arg(
		array(
			'savta'      => $result['ok'] ? 'ok' : 'error',
			'savta_code' => $result['code'],
		),
		$back
	) . '#signup';

	wp_safe_redirect( $back );
	exit;
}
add_action( 'admin_post_savta_lead', 'savta_post_submit' );
add_action( 'admin_post_nopriv_savta_lead', 'savta_post_submit' );

/**
 * The outcome of a no-JavaScript submission, read back from the URL.
 *
 * @return array{ok:bool,message:string}|null
 */
function savta_form_outcome(): ?array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only flags; no data changes.
	if ( ! isset( $_GET['savta'] ) ) {
		return null;
	}

	$state    = sanitize_key( wp_unslash( $_GET['savta'] ) );
	$code     = isset( $_GET['savta_code'] ) ? sanitize_key( wp_unslash( $_GET['savta_code'] ) ) : '';
	$messages = savta_form_messages();
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( 'ok' === $state ) {
		return array(
			'ok'      => true,
			'message' => $messages['ok'],
		);
	}

	return array(
		'ok'      => false,
		'message' => $messages[ $code ] ?? $messages['server'],
	);
}

/**
 * Columns in the leads list.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function savta_lead_columns( array $columns ): array {
	return array(
		'cb'      => $columns['cb'] ?? '',
		'title'   => __( 'שם וטלפון', 'savta-al-hasafsal' ),
		'contact' => __( 'דרך קשר', 'savta-al-hasafsal' ),
		'slot'    => __( 'מועד מבוקש', 'savta-al-hasafsal' ),
		'city'    => __( 'עיר', 'savta-al-hasafsal' ),
		'date'    => __( 'התקבלה', 'savta-al-hasafsal' ),
	);
}
add_filter( 'manage_' . SAVTA_LEAD_TYPE . '_posts_columns', 'savta_lead_columns' );

/**
 * Fills the custom columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Lead.
 * @return void
 */
function savta_lead_column( string $column, int $post_id ): void {
	if ( in_array( $column, array( 'contact', 'slot', 'city' ), true ) ) {
		$value = (string) get_post_meta( $post_id, '_savta_' . $column, true );
		echo '' !== $value ? esc_html( $value ) : '&mdash;';
	}
}
add_action( 'manage_' . SAVTA_LEAD_TYPE . '_posts_custom_column', 'savta_lead_column', 10, 2 );

/**
 * Shows the submission on the lead's edit screen.
 *
 * @return void
 */
function savta_lead_meta_box(): void {
	add_meta_box(
		'savta-lead',
		__( 'פרטי הפנייה', 'savta-al-hasafsal' ),
		'savta_render_lead_meta_box',
		SAVTA_LEAD_TYPE,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_' . SAVTA_LEAD_TYPE, 'savta_lead_meta_box' );

/**
 * Renders the read-only submission details.
 *
 * @param WP_Post $post Lead.
 * @return void
 */
function savta_render_lead_meta_box( WP_Post $post ): void {
	echo '<table class="widefat striped"><tbody>';

	foreach ( savta_lead_fields() as $key => $label ) {
		$value = (string) get_post_meta( $post->ID, '_savta_' . $key, true );

		if ( '' === $value ) {
			continue;
		}

		printf(
			'<tr><th scope="row" style="width:180px">%s</th><td>%s</td></tr>',
			esc_html( $label ),
			'phone' === $key ? '<a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $value ) ) . '" dir="ltr">' . esc_html( $value ) . '</a>' : nl2br( esc_html( $value ) )
		);
	}

	if ( get_post_meta( $post->ID, '_savta_mail_failed', true ) ) {
		printf( '<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html__( 'מייל', 'savta-al-hasafsal' ), esc_html__( 'שליחת ההתראה במייל נכשלה. הפנייה נשמרה כאן.', 'savta-al-hasafsal' ) );
	}

	echo '</tbody></table>';
}
