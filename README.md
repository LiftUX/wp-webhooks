# WordPress Webhooks

A simple framework for firing and listening for webhooks in a WordPress installation.

### Usage
```php
// Send a webhook to a target URL every time a post updates.
add_action( 'wp_webhooks_register', function( $webhooks ) {
	$event = 'wp_update_post';
	$target = 'http://httpbin.org/post';
	$webhooks->register_send_event( $event, $target );
} );

// Listen for a webhook coming from another service.
add_filter( 'webhook_ping', function( $response, $source, $request ) ) {
	if ( 'github' !== $source ) {
		return;
	}
	$response->set_status( 200 );
	$response->data = array(
		'success' => true,
		'message' => 'Update received.',
	);
	return $response;
}
```

Ping Url: `http://{SITEURL}.com/wp-json/webhooks/v1/ping/{SOURCE}`.
