<?php

namespace PedLibraries;

abstract class Post {

	public const POST_TYPE = '';

	protected static $postTypeOptions = [];
	protected static $instances;
	protected $post;

	public static function init() {
		static::registerPostType();
	}

	protected function __construct( $post = null ) {
		if ( empty( $post ) ) {
			$post = new \stdClass();
		}
		if ( is_array( $post ) ) {
			$post = (object) $post;
		}
		$this->post = $post;
	}

	protected static function registerPostType() {
		register_post_type( static::POST_TYPE, static::$postTypeOptions );
	}

	public static function clearInstances() {
		static::$instances = array();
	}

	/**
	 * Get instance
	 *
	 * @param \WP_Post|int|string $post Post object, ID or slug
	 *
	 * @return self
	 */
	public static function getInstance( $post ) {

		if ( is_scalar( $post ) ) {

			if ( ! empty( static::$instances[ get_called_class() ][ $post ] ) ) {
				return static::$instances[ get_called_class() ][ $post ];
			} else if ( is_numeric( $post ) ) {
				$post = get_post( $post );
			} else {
				$posts = get_posts( [
					'name'           => $post,
					'post_status'    => 'any',
					'post_type'      => static::POST_TYPE,
					'posts_per_page' => 1
				] );
				$post  = ( ! empty( $posts ) && is_array( $posts ) ) ? $posts[0] : null;
			}
		}

		if ( ! empty( $post ) and is_object( $post ) and $post->post_type == static::POST_TYPE ) {

			if ( empty( static::$instances[ get_called_class() ][ $post->ID ] ) ) {

				static::$instances[ get_called_class() ][ $post->ID ] = new static( $post );
			}

			return static::$instances[ get_called_class() ][ $post->ID ];
		}
	}

	public static function newInstance() {
		return static::$instances[ get_called_class() ][] = new static();
	}

	public static function getPosts( $args = [] ) {
		$output   = [];
		$defaults = [
			'post_type'      => static::POST_TYPE,
			'posts_per_page' => 10,
			'order'          => 'DESC'
		];

		$args = wp_parse_args( $args, $defaults );

		$query = new \WP_Query( $args );
		$posts = $query->posts;

		if ( $posts ) {
			foreach ( $posts as $post ) {
				$output[] = static::getInstance( $post );
			}
		}

		return $output;

	}

	public function getId() {
		if ( isset( $this->post->ID ) ) {
			return $this->post->ID;
		}

		return false;
	}

	public function getPostMeta( $name, $single = true ) {
		return get_post_meta( $this->getId(), $name, $single );
	}

	public function setPostMeta( $name, $value ) {
		update_post_meta( $this->getId(), $name, $value );

		return $this;
	}

	public function getTitle() {
		if ( isset( $this->post->post_title ) ) {
			return $this->post->post_title;
		}

		return false;
	}

	public function setTitle( $title ) {
		$this->post->post_title = $title;

		return $this;
	}

	public function getSlug() {
		if ( isset( $this->post->post_name ) ) {
			return $this->post->post_name;
		}

		return false;
	}

	public function setSlug( $slug ) {
		$this->post->post_name = $slug;

		return $this;
	}

	public function getContent() {
		if ( isset( $this->post->post_content ) ) {
			return $this->post->post_content;
		}

		return false;
	}

	public function setContent( $desc ) {
		$this->post->post_content = $desc;

		return $this;
	}

	public function getExcerpt() {
		if ( isset( $this->post->post_excerpt ) ) {
			return $this->post->post_excerpt;
		}

		return false;
	}

	public function setExcerpt( $val ) {
		$this->post->post_excerpt = $val;

		return $this;
	}

	public function clone( $with_meta = false ) {
		$new_post = static::newInstance();
		$new_post->setTitle( $this->getTitle() . '-(Clone)' )
		         ->setContent( $this->getContent() )
		         ->setParent( $this->getParentId() )
		         ->setAuthor( $this->getAuthorId() )
		         ->setExcerpt( $this->getExcerpt() )
		         ->setParent( $this->getParentId() )
		         ->save();

		$thumbnail_id = $this->getThumbnailId();
		if ( $thumbnail_id ) {
			$new_post->setThumbnail( $thumbnail_id );
		}

		if ( $with_meta ) {
			$all_meta = get_post_meta( $this->getId() );

			foreach ( $all_meta as $metafield_key => $metafield_value ) {
				if ( count( $metafield_value ) == 1 && isset( $metafield_value[0] ) ) {
					$new_post->setPostMeta( $metafield_key, $metafield_value[0] );
					continue;
				}

				$new_post->setPostMeta( $metafield_key, $metafield_value );
			}
		}

		return $new_post;
	}

	public function save() {
		$this->post->post_type = static::POST_TYPE;

		if ( $this->getId() ) {

			$result = wp_update_post( (array) $this->post, $wp_error = true );

			if ( is_numeric( $result ) ) {
				return $result;
			} else {
				return false;
			}
		} else {
			$id         = wp_insert_post( (array) $this->post );
			$this->post = get_post( $id );

			return $id;
		}
	}

	public function getPermalink() {
		return get_permalink( $this->getId() );
	}

	public function getPost() {
		return $this->post;
	}

	public function getTags( $args = array() ) {
		return wp_get_post_tags( $this->getId(), $args );
	}

	public function setTags( $tags ) {
		if ( ! is_array( $tags ) ) {
			$tags = explode( ',', $tags );
		}

		$tags = array_map( 'trim', $tags );
		$tags = implode( ',', $tags );

		return wp_set_post_tags( $this->getId(), $tags, $append = false );
	}

	public function setParent( $id ) {
		$this->post->post_parent = $id;

		return $this;
	}

	public function getStatus() {
		if ( ! empty( $this->post->post_status ) ) {
			return $this->post->post_status;
		}

		return false;
	}

	public function setStatus( $status ) {
		$this->post->post_status = $status;

		return $this;
	}

	public function setAuthor( $userId ) {
		$this->post->post_author = $userId;

		return $this;
	}

	public function getMenuOrder() {
		return $this->post->menu_order;
	}

	public function setMenuOrder( $order ) {
		$this->post->menu_order = $order;

		return $this;
	}

	public function setThumbnail( $thumbnail_id ) {
		set_post_thumbnail( $this->getPost(), $thumbnail_id );

		return $this;
	}

	public function getThumbnailId() {
		return get_post_thumbnail_id( $this->getPost() );
	}

	public function getAuthorId() {
		if ( ! empty( $this->post->post_author ) ) {
			return $this->post->post_author;
		}

		return false;
	}

	public function getAuthor() {
		if ( ! empty( $this->post->post_author ) and $user = get_userdata( $this->post->post_author ) ) {
			return $user;
		}

		return false;
	}

	public function getAuthorEmail() {
		if ( $user = $this->getAuthor() ) {
			return $user->user_email;
		}

		return false;
	}

	public function getAuthorDisplayName() {
		if ( $user = $this->getAuthor() ) {
			return $user->display_name;
		}

		return false;
	}

	public function getAuthorLogin() {
		if ( $user = $this->getAuthor() ) {
			return $user->user_login;
		}

		return false;
	}

	public function getCreatedDate() {
		return $this->post->post_date;
	}

	public function formatCreatedDate() {
		return get_the_date( get_option( 'date_format' ), $this->getId() );
	}

	public function getModifiedDate() {
		return $this->post->post_modified;
	}

	public function formatModifiedDate() {
		return get_the_modified_date( get_option( 'date_format' ), $this->getId() );
	}

	public function getPostParent() {
		return $this->getParentId();
	}

	public function getParentId() {
		if ( ! empty( $this->post->post_parent ) ) {
			return $this->post->post_parent;
		}

		return false;
	}

	public function getThumbnailUrl( $size = 'thumbnail' ) {
		return get_the_post_thumbnail_url( $this->post, $size );
	}

	public function delete( $force = false ) {
		$result = wp_delete_post( $this->getId(), $force );
		unset( static::$instances[ get_called_class() ][ $this->getId() ] );

		return $result;
	}
}
