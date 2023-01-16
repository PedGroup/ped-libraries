<?php

namespace Pedestrian\PedLibraries;

abstract class User {

	const ROLE = '';
	const ROLE_DISPLAY_NAME = '';

	protected static $roleCaps = [];
	protected static $instances;
	protected $user;

	public static function init() {
		static::addUserRole();
	}

	protected function __construct( $user = null ) {
		if ( empty( $user ) ) {
			$user = new \stdClass();
		}
		if ( is_array( $user ) ) {
			$user = (object) $user;
		}
		$this->user = $user;
	}

	/**
	 * Get instance
	 *
	 * @param \WP_User|int $user User object or ID
	 *
	 * @return self
	 */
	public static function getInstance( $user ) {

		if ( is_scalar( $user ) ) {

			if ( ! empty( static::$instances[ $user ] ) ) {
				return static::$instances[ $user ];
			} else if ( is_numeric( $user ) ) {
				$user = get_user_by( 'ID', $user );
			} else if ( filter_var( $user, FILTER_VALIDATE_EMAIL ) ) {
				$user = get_user_by( 'email', $user );
			}
		}

		if ( ! empty( $user ) and is_object( $user ) and in_array( static::ROLE, $user->roles ) ) {

			if ( empty( static::$instances[ $user->ID ] ) ) {

				static::$instances[ $user->ID ] = new static( $user );
			}

			return static::$instances[ $user->ID ];
		}

	}

	public static function newInstance() {
		return static::$instances[] = new static();
	}

	public static function getUsers( $args = [] ) {
		$args      = array_merge( $args, [
			'role' => static::ROLE
		] );
		$users     = get_users( $args );
		$users_arr = [];

		if ( $users ) {
			foreach ( $users as $user ) {
				$users_arr[] = static::getInstance( $user );
			}
		}

		return $users_arr;
	}

	public static function clearInstances() {
		static::$instances = array();
	}

	public static function addUserRole() {
		if ( ! static::roleExists() ) {
			add_role( static::ROLE, static::ROLE_DISPLAY_NAME, static::$roleCaps );
		}
	}

	public static function removeUserRole() {
		if ( static::roleExists() ) {
			remove_role( static::ROLE );
		}
	}

	public static function roleExists( $role = '' ) {
		$role = ! empty( $role ) ? $role : static::ROLE;

		if ( ! empty( $role ) ) {
			return $GLOBALS['wp_roles']->is_role( $role );
		}

		return false;
	}

	public function save() {

		if ( $this->getId() ) {
			$roles             = $this->user->roles;
			$roles[]           = static::ROLE;
			$roles             = array_values( array_unique( $roles ) );
			$this->user->roles = $roles;

			$result = wp_update_user( (array) $this->user, $wp_error = true );

			if ( is_numeric( $result ) ) {
				return $result;
			} else {
				return false;
			}
		} else {
			$this->user->role = static::ROLE;
			$id               = wp_insert_user( (array) $this->user );
			if ( $id instanceof \WP_Error ) {
				$this->throwError( $id );
			}
			$this->user = get_user_by( 'ID', $id );

			return $id;
		}
	}

	public function getId() {
		if ( isset( $this->user->ID ) ) {
			return $this->user->ID;
		}

		return false;
	}

	public function getUser() {
		return $this->user;
	}

	public function getUserMeta( $name, $single = true ) {
		return get_user_meta( $this->getId(), $name, $single );
	}

	public function setUserMeta( $name, $value ) {
		update_user_meta( $this->getId(), $name, $value );

		return $this;
	}

	public function getLogin() {
		if ( isset( $this->user->user_login ) ) {
			return $this->user->user_login;
		}

		return false;
	}

	public function setLogin( $login ) {
		$this->user->user_login = $login;

		return $this;
	}

	public function getPassword() {
		if ( isset( $this->user->user_pass ) ) {
			return $this->user->user_pass;
		}

		return false;
	}

	public function setPassword( $pass ) {
		$this->user->user_pass = $pass;

		return $this;
	}

	public function getNicename() {
		if ( isset( $this->user->user_nicename ) ) {
			return $this->user->user_nicename;
		}

		return false;
	}

	public function setNicename( $user_nicename ) {
		$this->user->user_nicename = $user_nicename;

		return $this;
	}

	public function getDisplayName() {
		if ( isset( $this->user->display_name ) ) {
			return $this->user->display_name;
		}

		return false;
	}

	public function setDisplayName( $display_name ) {
		$this->user->display_name = $display_name;

		return $this;
	}

	public function getFirstName() {
		return $this->getUserMeta( 'first_name' );
	}

	public function setFirstName( $name ) {
		if ( $this->getId() ) {
			$this->setUserMeta( 'first_name', $name );
		} else {
			$this->user->first_name = $name;
		}

		return $this;
	}

	public function getLastName() {
		return $this->getUserMeta( 'last_name' );
	}

	public function setLastName( $name ) {
		if ( $this->getId() ) {
			$this->setUserMeta( 'last_name', $name );
		} else {
			$this->user->last_name = $name;
		}

		return $this;
	}

	public function getEmail() {
		if ( isset( $this->user->user_email ) ) {
			return $this->user->user_email;
		}

		return false;
	}

	public function setEmail( $user_email ) {
		$this->user->user_email = $user_email;

		return $this;
	}

	public function getRegistered() {
		if ( isset( $this->user->user_registered ) ) {
			return $this->user->user_registered;
		}

		return false;
	}

	public function setRegistered( $user_registered ) {
		$this->user->user_registered = $user_registered;

		return $this;
	}

	public function throwError( $error ) {
		throw new \Exception( $error->get_error_message() );
	}

}
