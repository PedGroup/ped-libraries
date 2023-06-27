<?php

namespace PedLibraries;

class RESTBlockTemplatesController extends \WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @since 2.4-beta-1
	 *
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * The base of this controller's route.
	 *
	 * @since 2.4-beta-1
	 *
	 *
	 * @var string
	 */
	protected $rest_base;

	protected static $instance = null;

	public static function getInstance() {
		if ( empty ( self::$instance ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	protected function __construct() {
		$this->namespace = 'block-template-parts/v1';
		$this->rest_base = 'get';

		add_action( 'rest_api_init', function () {
			$this->register_routes();
		} );
	}

	/**
	 * Registers routes for the controller.
	 *
	 * @since 2.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [],
				],
			]
		);
	}

	/**
	 * Checks whether the current user has permission to manage options.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return true|\WP_Error True if the request has permission; WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return current_user_can( 'manage_options' )
			? true
			: new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to manage options.', 'block-template-parts' ),
				[ 'status' => rest_authorization_required_code() ]
			);
	}

	/**
	 * Retrieves all available reader themes.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response Response object.
	 */
	public function get_items( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$block_template_parts = \_get_block_templates_files( 'wp_template_part' );
		if ( empty( $block_template_parts ) ) {
			return new \WP_REST_Response( [] );
		}

		return new \WP_REST_Response( array_map( function ( $template_part ) {
			return $template_part['slug'];
		}, $block_template_parts ) );
	}
}
