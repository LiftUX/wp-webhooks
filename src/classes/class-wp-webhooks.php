<?php
/**
 * WP Qello Webhooks
 *
 * The main plugin class.
 *
 * @package Lift\WP_Webhooks
 */

namespace Lift\WP_Webhooks;

use Lift\Core\Dependency_Injector;
use Lift\Core\Hook_Catalog;
use Lift\Core\Hook_Definition;
use Lift\Core\Decorators\Integration_Decorator;
use Lift\Core\Interfaces\Subscriber;
use Lift\WP_Webhooks\Integrations;

/**
 * Class: WP_Webhooks
 *
 * Main plugin class.
 *
 * @since v0.1.0
 */
class WP_Webhooks implements Subscriber {
	use Integration_Decorator;

	/**
	 * Injector
	 *
	 * @var Dependency_Injector Instance of Dependency_Injector.
	 */
	public $injector;

	/**
	 * Hook Catalog
	 *
	 * @var Hook_Catalog Instance of Hook_Catalog.
	 */
	public $hook_catalog;

	/**
	 * Send Events
	 *
	 * @var array An associate array of send event targets keyed by the event.
	 */
	public $send_events = array();

	/**
	 * Singleton
	 *
	 * @var WP_Webhooks Singleton instance.
	 */
	public static $singleton = null;

	/**
	 * Construct
	 *
	 * @param Dependency_Injector $injector Instance of Dependency_Injector.
	 */
	public function __construct( Dependency_Injector $injector ) {
		$this->injector = $injector->setup();
		$this->hook_catalog = $injector->inject( 'hook_catalog' );
		return $this;
	}

	/**
	 * Static Factory
	 *
	 * @param Dependency_Injector $injector Optional Dependency_Injector that can inject defined integrations.
	 * @param boolean             $new      Optional flag, when true, will ensure return value is a new instance.
	 * @return WP_Webhooks            Self instance.
	 */
	public static function factory( Dependency_Injector $injector = null, $new = false ) : WP_Webhooks {
		if ( $new || ! self::$singleton instanceof self ) {
			self::$singleton = new self( ( $injector ?  $injector : new Dependency_Injector ) );
		}
		return self::$singleton;
	}

	/**
	 * Magic: __invoke
	 *
	 * @return Qello_Plugin The current singleton instance or a new instance that will become the singleton.
	 */
	public function __invoke() {
		return self::factory();
	}

	/**
	 * Inject
	 *
	 * Alias of self::$injector::inject().
	 *
	 * @see Lift\Core\Dependency_Injector::inject();
	 * @param  mixed $dependency Dependency to inject.
	 * @return mixed             The dependency.
	 */
	protected function inject( $dependency ) {
		return $this->injector->inject( $dependency );
	}

	/**
	 * Register Dependency
	 *
	 * Alias of self::$injector::register_dependency().
	 *
	 * @see Lift\Core\Dependency_Injector::register_dependency()
	 * @param  string  $name       Name of the dependency.
	 * @param  mixed   $dependency The dependency.
	 * @param  boolean $required   Whether the dependency is required.
	 * @return mixed              The dependency.
	 */
	protected function register_dependency( string $name, $dependency, bool $required ) {
		return $this->injector->register_dependency( $name, $dependency, $required );
	}

	/**
	 * Setup
	 *
	 * Registers integrations and providers on the dependency injector.
	 *
	 * @return Qello_Plugin Self instance.
	 */
	public function setup() {
		$this->register_rest_integration()->add_subscriptions();
		$this->add_subscriptions();
		do_action( 'wp_webhooks_setup' );
		return $this;
	}

	/**
	 * Register WP Qello Webhooks REST Route.
	 *
	 * @return Integrations\WP_Webhooks_Route
	 */
	public function register_rest_integration() : Integrations\WP_Webhooks_Route {
		$wp_webhooks_route = new Integrations\WP_Webhooks_Route( $this->hook_catalog );
		$this->register_dependency( 'wp_webhooks_route', $wp_webhooks_route, true );
		return $wp_webhooks_route;
	}

	/**
	 * Add Subscriptions
	 *
	 * Registers the hooks used by this class.
	 *
	 * @return Subscriber Instance of self.
	 */
	public function add_subscriptions() : Subscriber {
		$this->subscribe( 'init', array( $this, 'register_send_events' ) );
		return $this;
	}

	/**
	 * Register Send Events
	 *
	 * Fires the action that other plugins/themes can hook into to register send events.
	 *
	 * @return void
	 */
	public function register_send_events() {
		do_action( 'wp_webhooks_register', $this );
	}

	/**
	 * Register Send Event
	 *
	 * @param string   $hook The WordPress hook to fire the send event on.
	 * @param string   $target The URL of the target.
	 * @param callable $formatter A function to format the data sent.  By default, no data is sent.
	 * @return void
	 */
	public function register_send_event( string $hook, string $target, callable $formatter = null ) {
		$this->hook_catalog->add_entry( new Hook_Definition( $hook, function() use ( $hook, $target, $formatter ) {
			$this->send_event( $hook, $target, $formatter, func_get_args() );
		} ) );
		$this->send_events[ $hook ][] = $target;
	}

	/**
	 * Send Event
	 *
	 * @param string   $hook      The WordPress hook to fire the send event on.
	 * @param string   $target    The URL of the target.
	 * @param callable $formatter A function to format the data sent.  By default, no data is sent.
	 * @param array    $args      An array of arguments passed to the formatter, supplied by the hook.
	 * @return void
	 */
	public function send_event( string $hook, string $target, callable $formatter = null, array $args = null ) {
		$args['blocking'] = false;
		$args['headers'] = [
			'X-WordPress-Event' => $hook,
		];
		$args['body'] = ! is_null( $formatter ) ? call_user_func_array( $formatter, $args ) : [
			'action' => $hook,
		];
		wp_remote_post( $target, $args );
	}
}
