<?php
/**
 * Dashboard editing UI: one meta box per section on the homepage.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

const SAVTA_NONCE_ACTION = 'savta_save_sections';
const SAVTA_NONCE_NAME   = 'savta_sections_nonce';

/**
 * Whether a page carries the section fields: the front page, or any page on
 * the homepage template.
 *
 * @param WP_Post|int|null $post Post or ID.
 * @return bool
 */
function savta_is_section_page( $post = null ): bool {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
		return false;
	}

	if ( (int) get_option( 'page_on_front' ) === (int) $post->ID ) {
		return true;
	}

	return 'templates/template-home.php' === get_page_template_slug( $post );
}

/**
 * Registers the meta boxes.
 *
 * @param string  $post_type Current post type.
 * @param WP_Post $post      Current post.
 * @return void
 */
function savta_register_meta_boxes( string $post_type, WP_Post $post ): void {
	if ( 'page' !== $post_type || ! savta_is_section_page( $post ) ) {
		return;
	}

	foreach ( savta_field_groups() as $group_id => $group ) {
		add_meta_box(
			'sv-group-' . $group_id,
			$group['label'],
			'savta_render_meta_box',
			'page',
			'normal',
			'default',
			array( 'group' => $group_id )
		);
	}

	add_meta_box(
		'sv-sections-nav',
		__( 'מקטעי העמוד', 'savta-al-hasafsal' ),
		'savta_render_sections_nav',
		'page',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'savta_register_meta_boxes', 10, 2 );

/**
 * The side box: a jump list to every section's meta box.
 *
 * @return void
 */
function savta_render_sections_nav(): void {
	echo '<ul class="sv-jump">';

	foreach ( savta_field_groups() as $group_id => $group ) {
		printf( '<li><a href="#sv-group-%s">%s</a></li>', esc_attr( $group_id ), esc_html( $group['label'] ) );
	}

	echo '</ul>';
	printf( '<p class="description">%s</p>', esc_html__( 'כל מקטע נערך בתיבה שלו מתחת לאזור העריכה. "עדכון" שומר את כולן יחד.', 'savta-al-hasafsal' ) );
}

/**
 * Renders one section's fields.
 *
 * @param WP_Post $post     Page being edited.
 * @param array   $meta_box Meta box arguments.
 * @return void
 */
function savta_render_meta_box( WP_Post $post, array $meta_box ): void {
	$groups   = savta_field_groups();
	$group_id = $meta_box['args']['group'] ?? '';

	if ( ! isset( $groups[ $group_id ] ) ) {
		return;
	}

	static $nonce_printed = false;

	if ( ! $nonce_printed ) {
		wp_nonce_field( SAVTA_NONCE_ACTION, SAVTA_NONCE_NAME );
		$nonce_printed = true;
	}

	echo '<div class="sv-fields">';

	foreach ( $groups[ $group_id ]['fields'] as $name => $definition ) {
		savta_render_field( $name, $definition, savta_get( $name, $post->ID ), 'savta[' . $name . ']', 'sv-' . $name );
	}

	echo '</div>';
}

/**
 * Renders a single control.
 *
 * @param string $name       Field name.
 * @param array  $definition Field definition.
 * @param mixed  $value      Current value.
 * @param string $name_attr  Input name attribute.
 * @param string $id_attr    Input id.
 * @return void
 */
function savta_render_field( string $name, array $definition, $value, string $name_attr, string $id_attr ): void {
	$type  = $definition['type'] ?? 'text';
	$label = $definition['label'] ?? $name;
	$desc  = $definition['desc'] ?? '';

	if ( 'repeater' === $type ) {
		savta_render_repeater( $definition, (array) $value, $name_attr, $id_attr );
		return;
	}

	echo '<div class="sv-field sv-field--' . esc_attr( $type ) . '">';

	if ( 'checkbox' !== $type ) {
		printf( '<label class="sv-field__label" for="%s">%s</label>', esc_attr( $id_attr ), esc_html( $label ) );
	}

	switch ( $type ) {
		case 'textarea':
			printf(
				'<textarea class="sv-field__input" id="%1$s" name="%2$s" rows="3">%3$s</textarea>',
				esc_attr( $id_attr ),
				esc_attr( $name_attr ),
				esc_textarea( (string) $value )
			);
			break;

		case 'checkbox':
			printf(
				'<label class="sv-field__toggle" for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> <span>%4$s</span></label>',
				esc_attr( $id_attr ),
				esc_attr( $name_attr ),
				checked( (int) $value, 1, false ),
				esc_html( $label )
			);
			break;

		case 'image':
			$preview = savta_image_url( $value, 'medium' );
			printf(
				'<div class="sv-media" data-sv-media>
					<div class="sv-media__preview">%1$s</div>
					<input type="hidden" id="%2$s" name="%3$s" value="%4$s" data-sv-media-value />
					<button type="button" class="button" data-sv-media-pick>%5$s</button>
					<button type="button" class="button-link" data-sv-media-clear>%6$s</button>
				</div>',
				'' !== $preview ? '<img src="' . esc_url( $preview ) . '" alt="" />' : '',
				esc_attr( $id_attr ),
				esc_attr( $name_attr ),
				esc_attr( (string) $value ),
				esc_html__( 'בחירת תמונה', 'savta-al-hasafsal' ),
				esc_html__( 'הסרת התמונה', 'savta-al-hasafsal' )
			);
			break;

		case 'url':
		case 'text':
		default:
			printf(
				'<input class="sv-field__input" type="text" id="%1$s" name="%2$s" value="%3$s" />',
				esc_attr( $id_attr ),
				esc_attr( $name_attr ),
				esc_attr( (string) $value )
			);
			break;
	}

	if ( '' !== $desc ) {
		printf( '<p class="sv-field__desc">%s</p>', esc_html( $desc ) );
	}

	echo '</div>';
}

/**
 * Renders a repeater with its row template.
 *
 * @param array  $definition Repeater definition.
 * @param array  $rows       Saved rows.
 * @param string $name_attr  Base name attribute.
 * @param string $id_attr    Base id.
 * @return void
 */
function savta_render_repeater( array $definition, array $rows, string $name_attr, string $id_attr ): void {
	$sub_fields = $definition['fields'] ?? array();
	$max        = (int) ( $definition['max'] ?? 20 );

	echo '<div class="sv-repeater" data-sv-repeater data-max="' . esc_attr( (string) $max ) . '">';
	printf( '<p class="sv-field__label">%s</p>', esc_html( $definition['label'] ?? '' ) );

	if ( ! empty( $definition['desc'] ) ) {
		printf( '<p class="sv-field__desc">%s</p>', esc_html( $definition['desc'] ) );
	}

	echo '<div class="sv-repeater__rows" data-sv-rows>';

	$index = 0;
	foreach ( $rows as $row ) {
		savta_render_repeater_row( $sub_fields, (array) $row, $name_attr, $id_attr, (string) $index );
		++$index;
	}

	echo '</div>';
	echo '<p class="screen-reader-text" data-sv-said role="status" aria-live="polite"></p>';
	printf( '<button type="button" class="button" data-sv-add>%s</button>', esc_html__( 'הוספת שורה', 'savta-al-hasafsal' ) );
	echo '<script type="text/html" data-sv-template>';
	savta_render_repeater_row( $sub_fields, array(), $name_attr, $id_attr, '__index__' );
	echo '</script>';
	echo '</div>';
}

/**
 * Renders one repeater row.
 *
 * @param array  $sub_fields Sub-field definitions.
 * @param array  $row        Row values.
 * @param string $name_attr  Base name attribute.
 * @param string $id_attr    Base id.
 * @param string $index      Row index or the template placeholder.
 * @return void
 */
function savta_render_repeater_row( array $sub_fields, array $row, string $name_attr, string $id_attr, string $index ): void {
	echo '<div class="sv-row" data-sv-row>';
	printf(
		'<div class="sv-row__order">
			<span class="sv-row__num" data-sv-num aria-hidden="true"></span>
			<button type="button" class="sv-row__move" data-sv-move="up" aria-label="%1$s" title="%1$s"><span aria-hidden="true">&#9650;</span></button>
			<button type="button" class="sv-row__move" data-sv-move="down" aria-label="%2$s" title="%2$s"><span aria-hidden="true">&#9660;</span></button>
		</div>',
		esc_attr__( 'העברה למעלה', 'savta-al-hasafsal' ),
		esc_attr__( 'העברה למטה', 'savta-al-hasafsal' )
	);
	echo '<div class="sv-row__body">';

	foreach ( $sub_fields as $key => $definition ) {
		savta_render_field(
			$key,
			$definition,
			$row[ $key ] ?? ( $definition['default'] ?? '' ),
			$name_attr . '[' . $index . '][' . $key . ']',
			$id_attr . '-' . $index . '-' . $key
		);
	}

	echo '</div>';
	printf( '<button type="button" class="sv-row__remove" data-sv-remove aria-label="%1$s" title="%1$s">&times;</button>', esc_attr__( 'מחיקת שורה', 'savta-al-hasafsal' ) );
	echo '</div>';
}

/**
 * Sanitises one submitted value according to its definition.
 *
 * @param mixed $value      Raw value.
 * @param array $definition Field definition.
 * @return mixed
 */
function savta_sanitize_value( $value, array $definition ) {
	switch ( $definition['type'] ?? 'text' ) {
		case 'textarea':
			return sanitize_textarea_field( (string) $value );

		case 'checkbox':
			return empty( $value ) ? 0 : 1;

		case 'url':
			$raw = trim( (string) $value );
			return '' === $raw ? '' : esc_url_raw( $raw, array( 'http', 'https', 'mailto', 'tel' ) );

		case 'image':
			if ( is_numeric( $value ) ) {
				return absint( $value );
			}
			$file = sanitize_file_name( (string) $value );
			return isset( savta_bundled_image_sizes()[ $file ] ) ? $file : '';

		case 'repeater':
			$sub_fields = $definition['fields'] ?? array();
			$max        = (int) ( $definition['max'] ?? 20 );
			$rows       = array();

			foreach ( (array) $value as $row ) {
				if ( count( $rows ) >= $max ) {
					break;
				}
				if ( ! is_array( $row ) ) {
					continue;
				}

				$clean = array();
				$typed = '';

				foreach ( $sub_fields as $key => $sub_definition ) {
					$clean[ $key ] = savta_sanitize_value( $row[ $key ] ?? '', $sub_definition );
					$typed        .= (string) $clean[ $key ];
				}

				if ( '' !== $typed ) {
					$rows[] = $clean;
				}
			}

			return $rows;

		case 'text':
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Persists the submitted fields.
 *
 * @param int     $post_id Page being saved.
 * @param WP_Post $post    Page.
 * @return void
 */
function savta_save_meta_boxes( int $post_id, WP_Post $post ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || 'page' !== $post->post_type ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified on the next line.
	$nonce = isset( $_POST[ SAVTA_NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ SAVTA_NONCE_NAME ] ) ) : '';

	if ( '' === $nonce || ! wp_verify_nonce( $nonce, SAVTA_NONCE_ACTION ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) || ! savta_is_section_page( $post ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked above; every value is sanitised per field type below.
	$submitted = isset( $_POST['savta'] ) && is_array( $_POST['savta'] ) ? wp_unslash( $_POST['savta'] ) : array();

	foreach ( savta_fields_flat() as $name => $definition ) {
		/*
		 * Two kinds of field send nothing when they are empty: an unchecked
		 * box, and a list whose every row was removed. Both must still be
		 * stored — as 0 and as an empty list — or the old value would come back.
		 */
		if ( ! array_key_exists( $name, $submitted ) && ! in_array( $definition['type'], array( 'checkbox', 'repeater' ), true ) ) {
			continue;
		}

		update_post_meta( $post_id, SAVTA_META_PREFIX . $name, savta_sanitize_value( $submitted[ $name ] ?? '', $definition ) );
	}
}
add_action( 'save_post_page', 'savta_save_meta_boxes', 10, 2 );

/**
 * Loads the editing assets only on the page that needs them.
 *
 * @param string $hook_suffix Current admin screen.
 * @return void
 */
function savta_admin_assets( string $hook_suffix ): void {
	if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
		return;
	}

	if ( ! savta_is_section_page( get_post() ) ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_style( 'sv-admin', savta_asset_uri( 'assets/css/admin.css' ), array(), savta_asset_version( 'assets/css/admin.css' ) );
	wp_enqueue_script( 'sv-admin', savta_asset_uri( 'assets/js/admin.js' ), array(), savta_asset_version( 'assets/js/admin.js' ), true );
	wp_localize_script(
		'sv-admin',
		'svAdmin',
		array(
			'chooseImage' => __( 'בחירת תמונה', 'savta-al-hasafsal' ),
			'useImage'    => __( 'שימוש בתמונה', 'savta-al-hasafsal' ),
			'maxRows'     => __( 'הגעתם למספר השורות המרבי במקטע הזה.', 'savta-al-hasafsal' ),
			/* translators: 1: new position of the row, 2: how many rows there are. */
			'movedTo'     => __( 'השורה הועברה למקום %1$d מתוך %2$d.', 'savta-al-hasafsal' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'savta_admin_assets' );

/**
 * Points the editor at the section fields below the content area.
 *
 * @return void
 */
function savta_editor_notice(): void {
	$screen = get_current_screen();

	if ( ! $screen || 'page' !== $screen->id || ! savta_is_section_page( get_post() ) ) {
		return;
	}

	printf(
		'<div class="notice notice-info"><p>%s</p></div>',
		esc_html__( 'כל תוכן העמוד נערך מהתיבות שמתחת לאזור העריכה — מקטע אחד לכל תיבה. ברשימה שבצד אפשר לקפוץ ישר למקטע שרוצים לשנות.', 'savta-al-hasafsal' )
	);
}
add_action( 'admin_notices', 'savta_editor_notice' );
