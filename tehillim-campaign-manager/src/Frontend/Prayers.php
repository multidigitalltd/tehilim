<?php
/**
 * Prayers / Segulot front-end module.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\PostTypes\PrayerPostType;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Archive + single shortcodes for prayers/segulot. The prayer text itself is a
 * "sacred space" — no ads are injected onto it (only the archive is commercial).
 */
final class Prayers implements Registerable {

    /**
     * {@inheritDoc}
     */
    public function register() {
        add_shortcode('tehillim_segulot', array($this, 'archive'));
        add_shortcode('tehillim_prayer', array($this, 'single'));
        add_filter('the_content', array($this, 'auto_content'));
    }

    /**
     * Auto-render a single prayer on its own page.
     *
     * @param string $content Content.
     * @return string
     */
    public function auto_content($content) {
        if (!is_singular(PrayerPostType::POST_TYPE) || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        // Use the already-processed $content to avoid re-filtering recursion.
        return Templating::render(
            'partials/prayer-single',
            array('title' => get_the_title(), 'content' => $content)
        );
    }

    /**
     * Archive of prayers, with category filter and search.
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function archive($atts) {
        $atts = shortcode_atts(array('category' => '', 'per_page' => 24), $atts);

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only browsing.
        $category = $atts['category'] ? sanitize_title($atts['category']) : (isset($_GET['tcm_cat']) ? sanitize_title(wp_unslash($_GET['tcm_cat'])) : '');
        $search   = isset($_GET['tcm_q']) ? sanitize_text_field(wp_unslash($_GET['tcm_q'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $args = array(
            'post_type'      => PrayerPostType::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => max(1, (int) $atts['per_page']),
            'no_found_rows'  => true,
            's'              => $search,
        );
        if ($category) {
            $args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                array('taxonomy' => PrayerPostType::TAXONOMY, 'field' => 'slug', 'terms' => $category),
            );
        }

        $query   = new \WP_Query($args);
        $prayers = array();
        foreach ($query->posts as $post) {
            $prayers[] = array(
                'title'     => get_the_title($post),
                'permalink' => get_permalink($post),
                'excerpt'   => wp_strip_all_tags(get_the_excerpt($post)),
            );
        }

        $terms = get_terms(array('taxonomy' => PrayerPostType::TAXONOMY, 'hide_empty' => true));

        return do_shortcode('[tehillim_ad slot="archive_top"]')
            . Templating::render(
                'partials/prayer-archive',
                array(
                    'prayers'  => $prayers,
                    'terms'    => is_array($terms) ? $terms : array(),
                    'current'  => $category,
                    'search'   => $search,
                    'base_url' => get_post_type_archive_link(PrayerPostType::POST_TYPE) ?: home_url('/'),
                )
            );
    }

    /**
     * A single prayer (sacred space — no ads).
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function single($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $id   = absint($atts['id']);
        if (!$id && is_singular(PrayerPostType::POST_TYPE)) {
            $id = (int) get_the_ID();
        }
        if (!$id || PrayerPostType::POST_TYPE !== get_post_type($id)) {
            return '';
        }
        return Templating::render(
            'partials/prayer-single',
            array(
                'title'   => get_the_title($id),
                'content' => wpautop(do_blocks(get_post_field('post_content', $id))),
            )
        );
    }
}
