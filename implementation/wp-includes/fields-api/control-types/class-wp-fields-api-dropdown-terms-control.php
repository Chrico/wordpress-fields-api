<?php
/**
 * @package    WordPress
 * @subpackage Fields_API
 */

/**
 * Fields API Dropdown Terms Control class.
 *
 * @see WP_Fields_API_Control
 */
class WP_Fields_API_Dropdown_Terms_Control extends WP_Fields_API_Select_Control {

	/**
	 * {@inheritdoc}
	 */
	public $type = 'dropdown-terms';

	/**
	 * @var string Taxonomy name
	 */
	public $taxonomy;

	/**
	 * @var array Arguments to send to get_terms
	 */
	public $get_args = array();

	/**
	 * @var bool Whether to exclude current item ID from list
	 */
	public $exclude_current_item_id = false;

	/**
	 * @var bool Whether to exclude current item ID and descendants from list
	 */
	public $exclude_tree_current_item_id = false;

	/**
	 * {@inheritdoc}
	 */
	public function choices() {

		$choices = array();

		// Handle default taxonomy
		if ( empty( $this->taxonomy ) && 'term' == $this->object_type && ! empty( $this->object_name ) ) {
			$this->taxonomy = $this->object_name;
		}

		if ( empty( $this->taxonomy ) ) {
			return $choices;
		}

		$args = $this->get_args;

		$item_id = $this->get_item_id();

		if ( ! isset( $args['exclude'] ) && $this->exclude_current_item_id && 0 < $item_id ) {
			$args['exclude'] = $item_id;
		}

		if ( ! isset( $args['exclude_tree'] ) && $this->exclude_tree_current_item_id && 0 < $item_id ) {
			$args['exclude_tree'] = $item_id;
		}

		// @todo Revisit limit later
		$args['number'] = 100;

		$terms = get_terms( $this->taxonomy, $args );

		if ( $terms && ! is_wp_error( $terms ) ) {
			$choices = $this->get_choices_recurse( $choices, $terms );
		}

		return $choices;

	}

	/**
	 * Recursively build choices array the full depth
	 *
	 * @param array     $choices List of choices.
	 * @param WP_Term[] $terms   List of terms.
	 * @param int       $depth   Current depth.
	 * @param int       $parent  Current parent term ID.
	 *
	 * @return array
	 */
	public function get_choices_recurse( $choices, $terms, $depth = 0, $parent = 0 ) {

		$pad = str_repeat( '&nbsp;', $depth * 3 );

		$taxonomy = '';

		foreach ( $terms as $term ) {
			$is_hierarchical = false;

			if ( $taxonomy !== $term->taxonomy && is_taxonomy_hierarchical( $term->taxonomy ) ) {
				$is_hierarchical = true;
			}

			// Avoid multiple calls to is_taxonomy_hierarchical when terms are using the same taxonomy
			$taxonomy = $term->taxonomy;

			if ( ! $is_hierarchical || $parent == $term->parent ) {
				$title = $term->name;

				if ( '' === $title ) {
					/* translators: %d: term_id of a term */
					$title = sprintf( __( '#%d (no title)' ), $term->term_id );
				}

				$choices[ $term->term_id ] = $pad . $title;

				if ( $is_hierarchical ) {
					$choices = $this->get_choices_recurse( $choices, $terms, $depth + 1, $term->term_id );
				}
			}
		}

		return $choices;

	}

}