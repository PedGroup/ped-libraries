<?php

namespace PedLibraries;

abstract class Term {

	const TAXONOMY = '';
	const FIELDS_MODEL = 'model';
	const FIELDS_ALL = 'all';
	const FIELDS_IDS = 'ids';
	const FIELDS_NAMES = 'names';
	const FIELDS_COUNT = 'count';
	const FIELDS_ID_PARENT = 'id=>parent';
	const FIELDS_ID_SLUG = 'id=>slug';
	const FIELDS_ID_NAME = 'id=>name';

	protected static $taxonomyOptions;
	protected static $postTypes = [];
	static protected $instances;
	protected $term;


	public static function init() {
		static::registerTaxonomy();
	}

	protected function __construct( $term = null ) {
		if ( empty( $term ) ) {
			$term           = new \stdClass();
			$term->taxonomy = static::TAXONOMY;
		}
		if ( is_array( $term ) ) {
			$term = (object) $term;
		}

		$this->term = $term;
	}
	/**
	 * Get instance
	 *
	 * @param object|int $term Term object or ID
	 *
	 * @return self
	 */

	public static function getInstance( $term ) {

		if ( is_object( $term ) and $term instanceof static ) {
			return $term;
		} else if ( is_scalar( $term ) ) {
			if ( ! empty( static::$instances[ get_called_class() ][ $term ] ) ) {
				return static::$instances[ get_called_class() ][ $term ];
			} else if ( is_numeric( $term ) ) {
				$term = get_term_by( 'term_id', $term, static::TAXONOMY );
			} else {
				$term = get_term_by( 'slug', $term, static::TAXONOMY );
			}
		}

		if ( ! empty( $term ) and is_object( $term ) and $term->taxonomy == static::TAXONOMY ) {
			if ( empty( static::$instances[ get_called_class() ][ $term->term_id ] ) ) {
				static::$instances[ get_called_class() ][ $term->term_id ] = new static( $term );
			}

			return static::$instances[ get_called_class() ][ $term->term_id ];
		}
	}

	public static function newInstance() {
		return static::$instances[ get_called_class() ][] = new static();
	}

	public function getTermMeta( $key = '', $single = true ) {
		return get_term_meta( $this->getId(), $key, $single );
	}

	public function setTermMeta( $meta_key, $meta_value, $prev_value = '' ) {
		return update_term_meta( $this->getId(), $meta_key, $meta_value, $prev_value );
	}

	public function addTermMeta( $meta_key, $meta_value, $unique = false ) {
		return add_term_meta( $this->getId(), $meta_key, $meta_value, $unique );
	}

	public function delete() {
		return wp_delete_term( $this->getId(), static::TAXONOMY );
	}

	public function getTerm() {
		if ( isset( $this->term ) ) {
			return $this->term;
		}

		return false;
	}

	public function getId() {
		if ( isset( $this->term->term_id ) ) {
			return $this->term->term_id;
		}

		return false;
	}

	public function setName( $name ) {
		$this->term->name = $name;

		return $this;
	}

	public function getName() {
		if ( isset( $this->term->name ) ) {
			return $this->term->name;
		}

		return false;
	}

	public function setDescription( $description ) {
		$this->term->description = $description;

		return $this;
	}

	public function getDescription() {
		if ( isset( $this->term->description ) ) {
			return $this->term->description;
		}

		return false;
	}

	public function setSlug( $slug ) {
		$this->term->slug = $slug;

		return $this;
	}

	public function getSlug() {
		if ( isset( $this->term->slug ) ) {
			return $this->term->slug;
		}

		return false;
	}

	public function setParent( $id ) {
		$this->term->parent = $id;

		return $this;
	}

	public function getParentId() {
		if ( isset( $this->term->parent ) ) {
			return $this->term->parent;
		}

		return false;
	}

	public function getPermalink() {
		return static::getTermPermalink( $this->term );
	}

	static public function getTermPermalink( $term ) {
		return get_term_link( $term, $term->taxonomy );
	}

	public function getParentInstance() {
		if ( $this->term->parent ) {
			return static::getInstance( $this->term->parent );
		}
	}

	public function clone( $with_meta = false, $with_posts = false ) {
		$new_term = static::newInstance();
		$new_term->setName( $this->getName() . '-(Clone)' )
		         ->setDescription( $this->getDescription() )
		         ->setParent( $this->getParentId() )
		         ->save();

		if ( $with_meta ) {
			$all_meta = get_term_meta( $this->getId() );

			foreach ( $all_meta as $metafield_key => $metafield_value ) {
				if ( count( $metafield_value ) == 1 && isset( $metafield_value[0] ) ) {
					$new_term->setTermMeta( $metafield_key, $metafield_value[0] );
					continue;
				}

				$new_term->setTermMeta( $metafield_key, $metafield_value );
			}
		}

		if ( $with_posts ) {
			$posts = $this->getPosts();
			if ( $posts ) {
				foreach ( $posts as $post ) {
					$new_term->setPostTerms( $post->ID );
				}
			}
		}

		return $new_term;
	}

	public function save() {
		if ( $this->getId() ) {
			$result = wp_update_term( $this->getId(), static::TAXONOMY, (array) $this->getTerm() );

			if ( is_numeric( $result ) ) {
				return $result;
			} else {
				return false;
			}
		} else {
			$id         = wp_insert_term( $this->getName(), static::TAXONOMY, [
				'description' => $this->getDescription() ? $this->getDescription() : '',
				'parent'      => $this->getParentId() ? $this->getParentId() : 0
			] );
			$this->term = get_term_by( 'term_id', $id['term_id'], static::TAXONOMY );

			return $id;
		}
	}

	public function getPosts() {
		$args = [
			'post_type'      => static::$postTypes,
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'tax_query'      => [
				[
					'taxonomy' => static::TAXONOMY,
					'field'    => 'id',
					'terms'    => $this->getId()
				]
			]
		];

		$query = new \WP_Query( $args );

		return $query->posts;
	}

	public function setPostTerms( $postId ) {
		return wp_set_post_terms( $postId, [ $this->getId() ], static::TAXONOMY, true );
	}

	public static function getTree( $params = array(), $depth = 0 ) {
		$params = shortcode_atts( array( 'orderby' => 'name', 'hide_empty' => 0, 'parent' => null ), $params );
		$terms  = get_terms( static::TAXONOMY, $params );

		$output = array();

		foreach ( $terms as $term ) {
			if ( $obj = static::getInstance( $term ) ) {

				$output[ $term->term_id ] = str_repeat( '-', $depth ) . ' ' . $term->name;
				$output                   += static::getTree( array( 'parent' => $term->term_id ), $depth + 1 );

			}
		}

		return $output;
	}

	public static function getTreeArray( $params = array(), $depth = 0, $fields = self::FIELDS_MODEL ) {

		if ( empty( $params['orderby'] ) ) {
			$params['orderby'] = 'name';
		}

		if ( empty( $params['hide_empty'] ) ) {
			$params['hide_empty'] = 0;
		}

		if ( empty( $params['parent'] ) ) {
			$params['parent'] = null;
		}

		$terms  = get_terms( static::TAXONOMY, $params );
		$output = array();

		foreach ( $terms as $term ) {
			if ( $obj = static::getInstance( $term ) ) {
				$term->term_id = intval( $term->term_id );
				switch ( $fields ) {
					case self::FIELDS_IDS:
						$value = $term->term_id;
						break;
					case self::FIELDS_NAMES:
					case self::FIELDS_ID_NAME:
						$value = $term->name;
						break;
					case self::FIELDS_ALL:
						$value = $term;
						break;
					default:
						$value = $obj;
				}
				$output[ $params['parent'] ? $params['parent'] : 0 ][ $term->term_id ] = $value;
				$output                                                                += self::getTreeArray( array( 'parent' => $term->term_id ), $depth + 1, $fields );
			}
		}

		return $output;
	}

	public static function getAll( $fields = self::FIELDS_MODEL, $params = array() ) {

		$terms = get_terms( static::TAXONOMY, array_merge( [
			'hide_empty' => 0,
			'fields'     => ( $fields == self::FIELDS_MODEL ? self::FIELDS_ALL : $fields ),
			'orderby'    => 'id',
			'order'      => 'DESC',
		], $params ) );

		if ( $fields == self::FIELDS_MODEL ) {
			return static::mapTerms( $terms );
		} else {
			return $terms;
		}
	}

	public static function mapTerms( array $terms ) {
		$output = array();

		foreach ( $terms as $term ) {
			$output[ $term->term_id ] = ( $term instanceof static ? $term : static::getInstance( $term ) );
		}

		return $output;
	}

	public static function mapByParent( array $terms ) {
		$out = array();

		foreach ( $terms as $term ) {
			$term                                                    = ( $term instanceof static ? $term : static::getInstance( $term ) );
			$out[ intval( $term->getParentId() ) ][ $term->getId() ] = $term;
		}

		return $out;
	}

	public static function getPostTerms( $postId, $fields = Term::FIELDS_MODEL, $params = array() ) {

		if ( in_array( $fields, array(
			static::FIELDS_IDS,
			static::FIELDS_NAMES,
			static::FIELDS_ALL,
			static::FIELDS_MODEL
		) ) ) {

			$terms = wp_get_post_terms( $postId, static::TAXONOMY, array_merge( $params, array(
				'fields' => ( $fields == static::FIELDS_MODEL ? static::FIELDS_ALL : $fields ),
			) ) );

			if ( $fields == static::FIELDS_MODEL ) {
				return static::mapTerms( $terms );
			} else {
				return $terms;
			}

		} else {
			$terms = wp_get_post_terms( $postId, static::TAXONOMY, array_merge( $params, array(
				'fields' => static::FIELDS_IDS,
			) ) );

			return static::getAll( $fields, array_merge( $params, array( 'include' => $terms ) ) );
		}
	}

	protected static function registerTaxonomy() {
		register_taxonomy( static::TAXONOMY, static::$postTypes, static::$taxonomyOptions );
	}
}
