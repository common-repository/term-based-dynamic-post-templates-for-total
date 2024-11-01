<?php
/**
 * Plugin Name:       Term Based Dynamic Post Templates for Total
 * Plugin URI:        https://wordpress.org/plugins/term-based-dynamic-post-templates-for-total/
 * Description:       Adds 2 new options inside the Theme Settings metabox while editing any taxonomy term so you can assign a dynamic template to the term archive or for the posts within that term.
 * Version:           1.3
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Author:            WPExplorer
 * Author URI:        https://www.wpexplorer.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       term-based-dynamic-post-templates-for-total
 * Domain Path:       /languages/
 */

/*
Term Based Dynamic Post Templates for Total is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Term Based Dynamic Post Templates for Total is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Term Based Dynamic Post Templates for Total. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Term_Based_Dynamic_Post_Templates Class.
 *
 * @since 1.0
 */
if ( ! class_exists( 'Term_Based_Dynamic_Post_Templates' ) ) {

	final class Term_Based_Dynamic_Post_Templates {

		/**
		 * Term_Based_Dynamic_Post_Templates constructor.
		 */
		public function __construct() {
			add_action( 'init', [ self::class, 'load_text_domain' ] );

			// Backend Actions.
			if ( is_admin() ) {
				add_filter( 'wpex_term_meta_options', [ self::class, 'add_term_options' ], 10, 2 );
			}

			// Frontend actions.
			if ( ! is_admin() || wp_doing_ajax() ) {
				add_filter( 'wpex_archive_template_id', [ self::class, 'maybe_modify_taxonomy_template' ] );
				add_filter( 'wpex_singular_template_id', [ self::class, 'maybe_modify_post_template' ] );
			}
		}

		/**
		 * Load text domain.
		 */
		public static function load_text_domain() {
			load_plugin_textdomain(
				'term-based-dynamic-post-templates-for-total',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages'
			);
		}

		/**
		 * Add new field to the Theme Settings terms metabox.
		 */
		public static function add_term_options( $options, $taxonomy ) {
			if ( function_exists( 'totaltheme_call_non_static' ) ) {

				$archive_templates = totaltheme_call_non_static( 'Theme_Builder', 'get_template_choices', 'archive' );

				if ( $archive_templates ) {
					$choices = [
						'' => '- ' . esc_html__( 'Select', 'total' ) . ' -',
					];
					$archive_templates = $choices + $archive_templates;
				}

				$single_templates = totaltheme_call_non_static( 'Theme_Builder', 'get_template_choices', 'single' );

				if ( $single_templates ) {
					$choices = [
						'' => '- ' . esc_html__( 'Select', 'total' ) . ' -',
					];
					$single_templates = $choices + $single_templates;
				}

			}

			if ( ! empty( $archive_templates ) ) {
				$options['wpex_archive_template'] = array(
					'label'   => esc_html__( 'Archive Template', 'total-theme-core' ),
					'type'    => 'select',
					'choices' => $archive_templates,
				);
			}

			if ( ! empty( $single_templates ) ) {
				$options['wpex_post_template'] = array(
					'label'   => esc_html__( 'Post Template', 'total-theme-core' ),
					'type'    => 'select',
					'choices' => $single_templates,
				);
			}

			return $options;
		}

		/**
		 * Check if we need to modify the term archvie template.
		 */
		public static function maybe_modify_taxonomy_template( $template_id ) {
			if ( is_category() || is_tag() || is_tax() ) {
				$term_archive_template = get_term_meta( get_queried_object_id(), 'wpex_archive_template', true );
				if ( $term_archive_template ) {
					$template_id = $term_archive_template;
				}
			}
			return $template_id;
		}

		/**
		 * Check if we need to modify the post template based on it's terms.
		 */
		public static function maybe_modify_post_template( $template_id ) {
			if ( $term_post_template = self::locate_post_term_template() ) {
				$template_id = $term_post_template;
			}
			return $template_id;
		}

		/**
		 * Check for current post template based on term setting.
		 */
		private static function locate_post_term_template() {
			$template_id = null;
			$post_type = $post_type = get_post_type();
			if ( $post_type ) {
				$taxonomies = get_object_taxonomies( $post_type );
				if ( $taxonomies ) {
					$args = [
						'fields'   => 'ids',
						'meta_key' => 'wpex_post_template',
					];
					$terms = wp_get_post_terms( get_the_ID(), $taxonomies, $args );
					if ( $terms && is_array( $terms ) && ! is_wp_error( $terms ) ) {
						foreach ( $terms as $term ) {
							$template_id = get_term_meta( $term, 'wpex_post_template', true );
							if ( $template_id ) {
								break;
							}
						}
					}
				}
			}
			return $template_id;
		}

	}

}

new Term_Based_Dynamic_Post_Templates;
