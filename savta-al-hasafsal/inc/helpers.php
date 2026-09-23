<?php
/**
 * Data access and rendering helpers shared by the templates.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Intrinsic dimensions of the images bundled with the theme, for CLS-free markup.
 *
 * @return array<string,array{0:int,1:int}>
 */
function savta_bundled_image_sizes(): array {
	return array(
		'logo.png'             => array( 800, 800 ),
		'bench-garden.webp'    => array( 890, 1112 ),
		'two-women-bench.webp' => array( 728, 485 ),
		'tea-hands.webp'       => array( 676, 676 ),
		'efrat.png'            => array( 480, 604 ),
		'empty-bench.webp'     => array( 756, 504 ),
	);
}

/**
 * ID of the page that stores the section content.
 *
 * The front page by default; the page in the loop when the homepage template
 * is used on another page.
 *
 * @return int
 */
function savta_content_id(): int {
	$front = (int) get_option( 'page_on_front' );

	if ( $front > 0 && is_front_page() ) {
		return $front;
	}

	$queried = (int) get_queried_object_id();

	return $queried > 0 && is_page() ? $queried : $front;
}

/**
 * Reads one field from the page's meta, falling back to the schema default.
 *
 * @param string   $name    Field name.
 * @param int|null $post_id Page holding the content.
 * @return mixed
 */
function savta_get( string $name, ?int $post_id = null ) {
	$fields = savta_fields_flat();

	if ( ! isset( $fields[ $name ] ) ) {
		return null;
	}

	$definition = $fields[ $name ];
	$default    = $definition['default'] ?? '';
	$post_id    = $post_id ?? savta_content_id();

	if ( $post_id <= 0 ) {
		return $default;
	}

	$stored = get_post_meta( $post_id, SAVTA_META_PREFIX . $name, true );

	if ( 'checkbox' === $definition['type'] ) {
		return '' === $stored ? (int) $default : (int) $stored;
	}

	if ( 'repeater' === $definition['type'] ) {
		return is_array( $stored ) && array() !== $stored ? $stored : (array) $default;
	}

	if ( 'image' === $definition['type'] ) {
		// An image the editor cleared on purpose stays cleared.
		return metadata_exists( 'post', $post_id, SAVTA_META_PREFIX . $name ) ? $stored : $default;
	}

	return '' === $stored || null === $stored ? $default : $stored;
}

/**
 * Reads a repeater and guarantees every row carries all declared sub-keys.
 *
 * @param string   $name    Repeater name.
 * @param int|null $post_id Page holding the content.
 * @return array<int,array<string,mixed>>
 */
function savta_rows( string $name, ?int $post_id = null ): array {
	$fields = savta_fields_flat();

	if ( ! isset( $fields[ $name ]['fields'] ) ) {
		return array();
	}

	$rows = array();

	foreach ( (array) savta_get( $name, $post_id ) as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$clean = array();
		foreach ( $fields[ $name ]['fields'] as $key => $definition ) {
			$clean[ $key ] = (string) ( $row[ $key ] ?? ( $definition['default'] ?? '' ) );
		}

		$rows[] = $clean;
	}

	return $rows;
}

/**
 * Whether a section is switched on.
 *
 * @param string $field Checkbox field name.
 * @return bool
 */
function savta_section_enabled( string $field ): bool {
	return (bool) savta_get( $field );
}

/**
 * Escapes a multi-line field, keeping the author's line breaks as <br>.
 *
 * @param string $text Raw value.
 * @return string
 */
function savta_lines( string $text ): string {
	return nl2br( esc_html( $text ), false );
}

/**
 * Resolves an image field to a URL: an attachment ID or a bundled file name.
 *
 * @param mixed  $value Field value.
 * @param string $size  Image size for attachments.
 * @return string Empty when the image cannot be resolved.
 */
function savta_image_url( $value, string $size = 'full' ): string {
	if ( is_numeric( $value ) && (int) $value > 0 ) {
		return (string) wp_get_attachment_image_url( (int) $value, $size );
	}

	$file = is_string( $value ) ? basename( $value ) : '';

	if ( '' === $file || ! isset( savta_bundled_image_sizes()[ $file ] ) ) {
		return '';
	}

	return get_theme_file_uri( 'assets/img/' . $file );
}

/**
 * Renders an <img> for an image field.
 *
 * Attachments go through wp_get_attachment_image() for srcset and any
 * WebP/AVIF plugin; bundled files get explicit dimensions.
 *
 * @param mixed $value Attachment ID or bundled file name.
 * @param array $args  alt, class, size, loading, fetchpriority, fit (adds data-fit).
 * @return bool Whether anything was printed.
 */
function savta_the_image( $value, array $args = array() ): bool {
	$args = wp_parse_args(
		$args,
		array(
			'alt'           => '',
			'class'         => '',
			'size'          => 'full',
			'loading'       => 'lazy',
			'fetchpriority' => '',
			'fit'           => false,
		)
	);

	$attributes = array(
		'class'    => $args['class'],
		'alt'      => $args['alt'],
		'loading'  => $args['loading'],
		'decoding' => 'async',
	);

	if ( '' !== $args['fetchpriority'] ) {
		$attributes['fetchpriority'] = $args['fetchpriority'];
	}

	if ( $args['fit'] ) {
		$attributes['data-fit'] = '1';
	}

	if ( is_numeric( $value ) && (int) $value > 0 ) {
		$attachment_id = (int) $value;

		if ( '' === $args['alt'] ) {
			$library_alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );

			if ( '' !== $library_alt ) {
				$attributes['alt'] = $library_alt;
			}
		}

		$html = wp_get_attachment_image( $attachment_id, $args['size'], false, $attributes );

		if ( '' === $html ) {
			return false;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes attributes.

		return true;
	}

	$url = savta_image_url( $value );

	if ( '' === $url ) {
		return false;
	}

	$file       = basename( is_string( $value ) ? $value : '' );
	$dimensions = savta_bundled_image_sizes()[ $file ] ?? null;

	if ( $dimensions ) {
		$attributes['width']  = (string) $dimensions[0];
		$attributes['height'] = (string) $dimensions[1];
	}

	$rendered = '';
	foreach ( $attributes as $name => $attribute_value ) {
		if ( '' === $attribute_value && 'alt' !== $name ) {
			continue;
		}
		$rendered .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $attribute_value ) );
	}

	printf( '<img src="%s"%s />', esc_url( $url ), $rendered ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes escaped above.

	return true;
}

/**
 * Renders one of the arched "window" frames with its image inside.
 *
 * The frame drifts with the scroll (data-window) and takes the image's own
 * aspect ratio once it has loaded (data-fit), exactly as in the design.
 *
 * @param string $shape       Frame shape class suffix: arch, arch-down, circle, portrait, side, leaf.
 * @param mixed  $image       Image field value.
 * @param string $alt         Alt text.
 * @param string $placeholder Text shown when no image is set.
 * @param array  $args        Extra: class, loading, fetchpriority.
 * @return void
 */
function savta_the_window( string $shape, $image, string $alt, string $placeholder = '', array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'class'         => '',
			'loading'       => 'lazy',
			'fetchpriority' => '',
		)
	);
	?>
	<div class="sv-window sv-window--<?php echo esc_attr( $shape ); ?> <?php echo esc_attr( $args['class'] ); ?>" data-window>
		<div class="sv-window__inner">
			<?php
			$printed = savta_the_image(
				$image,
				array(
					'alt'           => $alt,
					'class'         => 'sv-window__img',
					'loading'       => $args['loading'],
					'fetchpriority' => $args['fetchpriority'],
					'fit'           => true,
				)
			);

			if ( ! $printed ) :
				?>
				<div class="sv-window__placeholder" role="img" aria-label="<?php echo esc_attr( $alt ); ?>">
					<svg viewBox="0 0 120 80" width="96" height="64" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
						<path d="M14 40 H106" />
						<path d="M18 40 V66 M102 40 V66" />
						<path d="M26 40 V28 Q26 22 32 22 H88 Q94 22 94 28 V40" />
						<path d="M60 60 C60 60 46 52 46 44 C46 39 49.5 37 52.5 37 C55.5 37 58 39 60 42 C62 39 64.5 37 67.5 37 C70.5 37 74 39 74 44 C74 52 60 60 60 60 Z" stroke="#8F4531" />
					</svg>
					<?php if ( '' !== $placeholder ) : ?>
						<span class="sv-window__placeholder-text"><?php echo esc_html( $placeholder ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * The falling heart / petal / bird layers of the hero.
 *
 * Every item's position, timing, size, colour and opacity is copied verbatim
 * from the design; the values ride on custom properties so the CSS owns the
 * animation itself.
 *
 * @param string $layer 'hero' for the whole hero, 'logo' for the logo column.
 * @return array<int,array<string,string|int|float>>
 */
function savta_falling_items( string $layer ): array {
	$heart = 'M12 21 C12 21 2 13 2 7.5 C2 4 4.6 2 7.2 2 C9.3 2 11 3.6 12 5.4 C13 3.6 14.7 2 16.8 2 C19.4 2 22 4 22 7.5 C22 13 12 21 12 21 Z';
	$petal = 'M1 15 C7 3 20 1 25 3 C22 14 9 18 1 15 Z';

	if ( 'logo' === $layer ) {
		return array(
			array(
				'kind'  => 'heart',
				'x'     => '14%',
				'dur'   => '13s',
				'delay' => '0s',
				'w'     => 15,
				'h'     => 14,
				'fill'  => '#C98573',
				'op'    => .5,
			),
			array(
				'kind'  => 'petal',
				'x'     => '38%',
				'dur'   => '17s',
				'delay' => '2.5s',
				'w'     => 17,
				'h'     => 12,
				'fill'  => '#9CA884',
				'op'    => .55,
			),
			array(
				'kind'  => 'heart',
				'x'     => '62%',
				'dur'   => '15s',
				'delay' => '5s',
				'w'     => 13,
				'h'     => 12,
				'fill'  => '#D8A08D',
				'op'    => .45,
			),
			array(
				'kind'  => 'petal',
				'x'     => '84%',
				'dur'   => '19s',
				'delay' => '7.5s',
				'w'     => 15,
				'h'     => 11,
				'fill'  => '#B3BC9B',
				'op'    => .5,
			),
			array(
				'kind'  => 'petal',
				'x'     => '26%',
				'dur'   => '21s',
				'delay' => '10s',
				'w'     => 12,
				'h'     => 9,
				'fill'  => '#C9A783',
				'op'    => .45,
			),
			array(
				'kind'  => 'heart',
				'x'     => '6%',
				'dur'   => '14s',
				'delay' => '1.5s',
				'w'     => 16,
				'h'     => 15,
				'fill'  => '#C98573',
				'op'    => .55,
			),
			array(
				'kind'  => 'heart',
				'x'     => '50%',
				'dur'   => '12s',
				'delay' => '3.5s',
				'w'     => 13,
				'h'     => 12,
				'fill'  => '#D8A08D',
				'op'    => .5,
			),
			array(
				'kind'  => 'heart',
				'x'     => '72%',
				'dur'   => '16s',
				'delay' => '6.5s',
				'w'     => 11,
				'h'     => 10,
				'fill'  => '#C98573',
				'op'    => .45,
			),
			array(
				'kind'  => 'heart',
				'x'     => '92%',
				'dur'   => '18s',
				'delay' => '8.5s',
				'w'     => 14,
				'h'     => 13,
				'fill'  => '#D8A08D',
				'op'    => .4,
			),
			array(
				'kind'  => 'heart',
				'x'     => '32%',
				'dur'   => '20s',
				'delay' => '11.5s',
				'w'     => 12,
				'h'     => 11,
				'fill'  => '#C98573',
				'op'    => .42,
			),
		);
	}

	return array(
		array(
			'kind'  => 'petal',
			'x'     => '6%',
			'dur'   => '23s',
			'delay' => '1.5s',
			'w'     => 13,
			'h'     => 10,
			'fill'  => '#C9A783',
			'op'    => .4,
		),
		array(
			'kind'  => 'heart',
			'x'     => '52%',
			'dur'   => '27s',
			'delay' => '6s',
			'w'     => 12,
			'h'     => 11,
			'fill'  => '#D8A08D',
			'op'    => .35,
		),
		array(
			'kind'  => 'petal',
			'x'     => '92%',
			'dur'   => '25s',
			'delay' => '12s',
			'w'     => 14,
			'h'     => 10,
			'fill'  => '#B3BC9B',
			'op'    => .4,
		),
		array(
			'kind'  => 'heart',
			'x'     => '32%',
			'dur'   => '16s',
			'delay' => '2s',
			'w'     => 14,
			'h'     => 13,
			'fill'  => '#C98573',
			'op'    => .45,
		),
		array(
			'kind'  => 'heart',
			'x'     => '76%',
			'dur'   => '19s',
			'delay' => '9s',
			'w'     => 12,
			'h'     => 11,
			'fill'  => '#D8A08D',
			'op'    => .5,
		),
		array(
			'kind'  => 'heart',
			'x'     => '44%',
			'dur'   => '21s',
			'delay' => '15s',
			'w'     => 16,
			'h'     => 15,
			'fill'  => '#C98573',
			'op'    => .38,
		),
		array(
			'kind'   => 'bird',
			'x'      => '-6%',
			'y'      => '16%',
			'dur'    => '26s',
			'delay'  => '0s',
			'w'      => 26,
			'h'      => 12,
			'stroke' => 1.6,
		),
		array(
			'kind'   => 'bird',
			'x'      => '-10%',
			'y'      => '24%',
			'dur'    => '31s',
			'delay'  => '4s',
			'w'      => 20,
			'h'      => 10,
			'stroke' => 1.8,
		),
		array(
			'kind'   => 'bird',
			'x'      => '-8%',
			'y'      => '9%',
			'dur'    => '36s',
			'delay'  => '11s',
			'w'      => 15,
			'h'      => 8,
			'stroke' => 2,
		),
	);
}

/**
 * Renders a falling layer.
 *
 * @param string $layer See savta_falling_items().
 * @return void
 */
function savta_the_falling_layer( string $layer ): void {
	$heart = 'M12 21 C12 21 2 13 2 7.5 C2 4 4.6 2 7.2 2 C9.3 2 11 3.6 12 5.4 C13 3.6 14.7 2 16.8 2 C19.4 2 22 4 22 7.5 C22 13 12 21 12 21 Z';
	$petal = 'M1 15 C7 3 20 1 25 3 C22 14 9 18 1 15 Z';

	echo '<div class="sv-falling" aria-hidden="true">';

	foreach ( savta_falling_items( $layer ) as $item ) {
		$style = sprintf(
			'--x:%s;--y:%s;--dur:%s;--delay:%s',
			$item['x'],
			$item['y'] ?? '0',
			$item['dur'],
			$item['delay']
		);

		if ( 'bird' === $item['kind'] ) {
			printf(
				'<span class="sv-falling__item sv-falling__item--bird" style="%1$s"><svg width="%2$d" height="%3$d" viewBox="0 0 32 14" fill="none" stroke="#C98573" stroke-width="%4$s" stroke-linecap="round"><path d="M1 9 C5 3 9 3 13 8"></path><path d="M13 8 C17 2 22 2 26 8"></path></svg></span>',
				esc_attr( $style ),
				(int) $item['w'],
				(int) $item['h'],
				esc_attr( (string) $item['stroke'] )
			);
			continue;
		}

		$is_heart = 'heart' === $item['kind'];

		printf(
			'<span class="sv-falling__item" style="%1$s"><svg width="%2$d" height="%3$d" viewBox="%4$s" fill="%5$s" opacity="%6$s"><path d="%7$s"></path></svg></span>',
			esc_attr( $style ),
			(int) $item['w'],
			(int) $item['h'],
			$is_heart ? '0 0 24 22' : '0 0 26 18',
			esc_attr( (string) $item['fill'] ),
			esc_attr( (string) $item['op'] ),
			esc_attr( $is_heart ? $heart : $petal )
		);
	}

	echo '</div>';
}

/**
 * The small rose heart used on the quote cards.
 *
 * @return void
 */
function savta_the_heart_icon(): void {
	echo '<svg class="sv-heart" width="15" height="14" viewBox="0 0 24 22" fill="#C98573" aria-hidden="true" focusable="false"><path d="M12 21 C12 21 2 13 2 7.5 C2 4 4.6 2 7.2 2 C9.3 2 11 3.6 12 5.4 C13 3.6 14.7 2 16.8 2 C19.4 2 22 4 22 7.5 C22 13 12 21 12 21 Z"></path></svg>';
}

/**
 * The section anchors the nav falls back to when no menu is assigned.
 *
 * @return array<string,string> Anchor => label.
 */
function savta_default_nav(): array {
	return array(
		'#about'        => __( 'מה זה', 'savta-al-hasafsal' ),
		'#how-it-works' => __( 'איך זה עובד', 'savta-al-hasafsal' ),
		'#who'          => __( 'למי זה מתאים', 'savta-al-hasafsal' ),
		'#faq'          => __( 'שאלות', 'savta-al-hasafsal' ),
	);
}

/**
 * Whether a string carries no right-to-left letters.
 *
 * @param string $value Text to inspect.
 * @return bool
 */
function savta_is_ltr_text( string $value ): bool {
	if ( '' === trim( $value ) ) {
		return false;
	}

	return 0 === preg_match( '/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}]/u', $value );
}

/**
 * Whether the current request renders the homepage sections.
 *
 * @return bool
 */
function savta_is_home_layout(): bool {
	if ( is_front_page() ) {
		return true;
	}

	return is_page() && 'templates/template-home.php' === get_page_template_slug( get_queried_object_id() );
}

/**
 * URL of the privacy-policy page, as chosen in the Customizer or WordPress.
 *
 * @return string
 */
function savta_privacy_url(): string {
	$page_id = (int) savta_option( 'savta_privacy_page' );

	if ( $page_id <= 0 ) {
		$page_id = (int) get_option( 'wp_page_for_privacy_policy' );
	}

	$url = $page_id > 0 ? get_permalink( $page_id ) : '';

	return is_string( $url ) ? $url : '';
}
