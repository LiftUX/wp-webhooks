<?php
/**
 * WP Webhooks Route
 *
 * @package Lift\WP_Webhooks
 * @subpackage Integrations
 */

namespace Lift\WP_Webhooks\Integrations;

use Lift\Core\Hook_Catalog;
use Lift\Core\Interfaces\Integration;
use Lift\Core\Interfaces\Subscriber;
use Lift\Core\Interfaces\Provider;
use Lift\Core\Decorators\Integration_Decorator;
use Lift\WP_Webhooks\WP_Webhooks;

/**
 * Class: WP_Webhooks_Route
 *
 * Exposes a REST route for Webhooks.
 *
 * @since v0.1.0
 */
class WP_Webhooks_Route extends \WP_Rest_Controller implements Integration {

	use Integration_Decorator;

	/**
	 * Constructor
	 *
	 * @param Hook_Catalog     $hook_catalog Hook Catalog.
	 * @param array|Provider[] ...$providers Array of Providers.
	 *
	 * @return  Integration Instance of self
	 */
	public function __construct( Hook_Catalog $hook_catalog, Provider ...$providers ) {
		$this->decorate_integration( $hook_catalog, ...$providers );
	}

	/**
	 * Add Subscriptions
	 *
	 * @return Subscriber Instance of self.
	 */
	public function add_subscriptions() : Subscriber {
		$this->subscribe( 'rest_api_init', array( $this, 'register_routes' ) );
		return $this;
	}

	/**
	 * Register Routes
	 *
	 * @return void
	 */
	public function register_routes() {
		$version = 1;
		$this->namespace = apply_filters( 'wp_webhooks_namespace', 'webhooks/v' . $version );
		$this->rest_base = apply_filters( 'wp_webhooks_rest_base', 'list' );

		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array( $this, 'list' ),
				'permission_callback' => '__return_true',
			],
		] );

		register_rest_route( $this->namespace, '/ping', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => function() {
					return new \WP_REST_Response( [
						'message' => 'listening',
					], 200 );
				},
				'permission_callback' => [ $this, 'webhook_permission_check' ],
			],
		] );

		register_rest_route( $this->namespace, '/ping/(?P<source>\w+)', [
			[
				'methods' => \WP_REST_Server::ALLMETHODS,
				'callback' => array( $this, 'ping' ),
				'permission_callback' => [ $this, 'webhook_permission_check' ],
			],
		] );
	}

	/**
	 * List
	 *
	 * Lists the events registered to send webhooks and their targets.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response $response A respone object listing the webhooks and targets.
	 */
	public function list( \WP_REST_Request $request ) {
		return new \WP_REST_Response( WP_Webhooks::factory()->send_events, 200 );
	}

	/**
	 * Ping
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response $response A respone object listing the webhooks and targets.
	 */
	public function ping( \WP_REST_Request $request ) {
		$default_response = new \WP_REST_Response( [
			'success' => false,
			'message' => 'Error Method Not Allowed: No handler configured to accept pings from this source.',
		], 405 );
		$response = apply_filters( 'webhook_ping', $default_response, $request->get_param( 'source' ), $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Webhook Permission Check
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return boolean True if permissible, false otherwise.  By default true, since no action is taken by default.
	 */
	public function webhook_permission_check( \WP_REST_Request $request ) : bool {
		$permissible = apply_filters( 'webhook_ping_permission_check', true, $request );
		return ( is_bool( $permissible ) ) ? $permissible : false;
	}
}
