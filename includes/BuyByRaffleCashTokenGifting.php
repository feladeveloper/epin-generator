<?php 
namespace Sgs\Buybyraffle;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;
use WP_REST_Request;
use WP_REST_Response;

class BuyByRaffleCashTokenGifting {

    public function __construct() {
        add_action('rest_api_init', [$this, 'init_rest_api']);
    }

    public function init_rest_api() {
        register_rest_route('cashtoken/v2', '/gifting', [
            'methods' => 'POST',
            'callback' => [$this, 'handleGifting'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handleGifting(WP_REST_Request $request) {
        $jwt = $this->extractJwt($request); // Implement this method
        if (!$this->validateJwt($jwt)) { // Implement this method
            return new WP_REST_Response('Invalid token', 403);
        }

        $input = @file_get_contents("php://input");
        $eventObj = json_decode($input);

        $order_id = $eventObj->order_id;
        $sns_status = get_post_meta($order_id, '_customer_gifted', true);

        if ($sns_status === 'true' || $sns_status === 'processing') {
            return $this->returnStatusCode($sns_status === 'true' ? 200 : 500);
        }

        return $this->processGifting($order_id);
    }

    private function processGifting($order_id) {
        // The main logic of the gifting process goes here
        // Extracted from the original code and refined
        // Return appropriate status code
    }

    private function extractJwt(WP_REST_Request $request) {
        $headers = $request->get_headers();
        $authHeader = isset($headers['authorization']) ? $headers['authorization'] : '';
    
        // The Authorization header format should be "Bearer <token>"
        if (!empty($authHeader) && is_array($authHeader)) {
            $authBearer = array_shift($authHeader);
            if (preg_match('/Bearer\s(\S+)/', $authBearer, $matches)) {
                return $matches[1]; // The JWT token
            }
        }
    
        return ''; // Return empty string if no JWT token found
    }
    

    private function validateJwt($jwt) {
        $jwksUrl = 'https://www.googleapis.com/oauth2/v3/certs'; // URL to get Google's public keys
        $jwksJson = file_get_contents($jwksUrl);
        $jwks = json_decode($jwksJson, true);
        // Store the parsed keys in a variable
        $parsedKeySet = JWK::parseKeySet($jwks);
        try {
            // Decoding the JWT with RS256 algorithm.
            //$decoded = JWT::decode($jwt, $parsedKeySet, ['RS256']);
            $decoded = JWT::decode($jwt, new Key($parsedKeySet, 'RS256'));
            // Additional validation can be added here (e.g., checking 'iss', 'aud' fields)
            return true;
        } catch (\Exception $e) {
            error_log('JWT validation failed: ' . $e->getMessage());
            return false;
        }
    }
    

    private function returnStatusCode($code) {
        return new WP_REST_Response('', $code);
    }
}
