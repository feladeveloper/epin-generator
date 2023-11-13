<?php
namespace Sgs\Buybyraffle;

use Google\Cloud\PubSub\PubSubClient;
use Google\Client;
use Exception;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Handles publishing messages to Google Cloud Pub/Sub and exposes a REST API endpoint for the same.
 */
class BuyByRaffleQueuePubSub extends WP_REST_Controller {
    /**
     * Google Cloud Pub/Sub client.
     *
     * @var PubSubClient
     */
    private $pubSubClient;

    /**
     * Path to the configuration file for Google Cloud credentials.
     *
     * @var string
     */
    private $configFilePath;

    /**
     * Google Cloud Pub/Sub API URL.
     *
     * @var string
     */
    private $apiUrl = 'https://pubsub.googleapis.com/v1/projects/buybyraffle/topics/draw-engine:publish';
    private $tableName;
    /**
     * Constructor for the publishToTopic class.
     * Initializes the Pub/Sub client and sets up the REST API endpoint.
     */
    public function __construct() {
        global $wpdb;
        $this->setEnvironmentConfig();
        $this->pubSubClient = new PubSubClient([
            'keyFilePath' => $this->configFilePath
        ]);

        // Set namespace and rest base for the REST API endpoint.
        $this->namespace = 'buybyraffle/v1';
        $this->rest_base = 'publish';
        add_action('rest_api_init', array($this, 'register_routes'), 99);

        $this->tableName = $wpdb->prefix . 'buybyraffle_queued_raffles';
       
    }

    /**
     * Registers the routes for the REST API endpoint.
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/queue', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'handle_queue_request'),
            'permission_callback' => array($this, 'publish_permissions_check')
        ));
    }

    /**
     * Checks if the current user has permission to publish to the topic.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool True if user has permissions, false otherwise.
     */
    public function publish_permissions_check(WP_REST_Request $request) {
       return current_user_can('manage_options');
       //return true;
    }

   /**
     * Sets the configuration file path based on the server environment.
     */
    private function setEnvironmentConfig() {
        $environment = wp_get_environment_type();
    
        switch ($environment) {
            case 'local':
                // Set path for local environment
                $this->configFilePath = 'C:\wamp64\www\wordpress\buybyraffle-dcc92f760bee.json';
                break;
            case 'staging':
                // Set path for staging environment (assuming '138.68.91.147' is your staging server)
                $this->configFilePath = '/home/master/applications/aczbbjzsvv/private_html/buybyraffle-dcc92f760bee.json';
                break;
            case 'production':
                // Set path for production environment
                $this->configFilePath = '/home/master/applications/bbqpcmbxkq/private_html/buybyraffle-dcc92f760bee.json';
                break;
            default:
                // Handle unexpected environment
                $errorMessage = "Unexpected environment type: $environment";
                error_log($errorMessage);

                // Send an email notification
                $to = 'terungwa@cashtoken.africa'; // Replace with your admin email address
                $subject = 'Configuration Error in BuyByRaffle Plugin';
                $message = "An error occurred in the BuyByRaffle plugin: $errorMessage";
                $headers = 'From: admin@buybyraffle.com' . "\r\n"; // Replace with your from email address

                if (!mail($to, $subject, $message, $headers)) {
                    error_log('Failed to send email regarding environment configuration error.');
                }

                // Set a default configuration path or handle the error
                //$this->configFilePath = '/path/to/default/config.json';
                break;
        }
    }
    

    /**
     * Retrieves the bearer token for authentication with Google Cloud APIs.
     *
     * @return string The bearer token.
     * @throws Exception If unable to fetch the bearer token.
     */
    private function getBearerToken() {
        try {
            // Initialize the Google Client
            $client = new Client();
            $client->setAuthConfig($this->configFilePath);
            $client->setScopes(['https://www.googleapis.com/auth/pubsub']);

            // Fetch the access token
            $accessToken = $client->fetchAccessTokenWithAssertion();

            // Return the access token
            return $accessToken['access_token'];
        } catch (Exception $e) {
            // Handle exceptions, such as file not found or invalid credentials
            error_log('Exception in getBearerToken: ' . $e->getMessage());
            throw $e; // Rethrow the exception for the caller to handle
        }
    }

    /**
     * Handles the actual publishing of messages to Google Cloud Pub/Sub.
     *
     * @param string $topicName The name of the Pub/Sub topic.
     * @param string $queueId The ID of the queue (message identifier).
     * @param array $data The data to be sent.
     * @return array|bool The response from Google Cloud Pub/Sub or false on failure.
     */
    public function publishToTopic($topicName, $queueId, $data) {
        try {
            $bearerToken = $this->getBearerToken();// Determine the environment
            $env = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) ? 'local' : 'remote';
    
            $payload = [
                'messages' => [
                    [
                        'attributes' => [
                            'id' => strval($queueId), // Convert the queue ID to a string.
                            'env' => $env
                        ],
                        'data' => base64_encode(json_encode($data))
                    ]
                ]
            ];
    
            $response = wp_remote_post($this->apiUrl, [
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $bearerToken
                ],
                'body' => json_encode($payload),
                'data_format' => 'body'
            ]);
    
            if (is_wp_error($response)) {
                error_log('Error in publishToTopic: ' . $response->get_error_message());
                return false;
            }
    
            $body = wp_remote_retrieve_body($response);
            return json_decode($body, true);
    
        } catch (Exception $e) {
            error_log('Exception in publishToTopic: ' . $e->getMessage());
            return false;
        }
    }

   
     /**
     * Handles the queue request, logs to database, and publishes to PubSub.
     * 
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error
     */
    public function handle_queue_request(WP_REST_Request $request) {
        global $wpdb;
        $params = $request->get_json_params();
        $raffleCycleId = $params['raffle_cycle_id'] ?? 0;
        $order_id = $params['order_id'] ?? 0;
        $action = $params['action'] ?? '';

        switch ($action) {
            case 'initiate_raffle':
                $taskId = $this->queueInitiateRaffle($raffleCycleId);
                break;

            case 'giftcashtoken':
                $taskId = $this->queueGiftCashtoken($order_id);
                break;

            default:
                return new WP_Error('invalid_action', 'Invalid action specified', ['status' => 400]);
        }

        if ($taskId === false) {
            return new WP_Error('queue_error', 'Failed to queue the request', ['status' => 500]);
        }

        return new WP_REST_Response(['message' => 'Queued and published successfully', 'task_id' => $taskId], 200);
    }

    private function queueInitiateRaffle($raffleCycleId) {
        global $wpdb;
        $tableName = $wpdb->prefix . 'buybyraffle_queued_raffles';
    
        // Insert into database
        $inserted = $wpdb->insert(
            $tableName,
            array(
                'raffle_cycle_id' => $raffleCycleId,
                'status' => 'pending'
            ),
            array('%d', '%s')
        );
    
        if ($inserted === false) {
            error_log('Failed to insert into database: ' . $wpdb->last_error);
            return false; // Return false on failure
        }
    
        $taskId = $wpdb->insert_id;
    
        // Prepare data for publishing
        $data = ['raffle_cycle_id' => $raffleCycleId];
        
        // Publish to PubSub topic
        $publishResult = $this->publishToTopic('draw-engine', $taskId, $data);
    
        if ($publishResult === false) {
            error_log('Failed to publish to draw-engine topic');
            return false; // Return false on failure
        }
    
        return $taskId; // Return the task ID on success
    }
    

    private function queueGiftCashtoken($order_id) {
        global $wpdb;
        $tableName = $wpdb->prefix . 'buybyraffle_cashtoken_gifting_logs';
    
        // Insert into database
        $inserted = $wpdb->insert(
            $tableName,
            array(
                'order_id' => $order_id,
                'status' => 'pending'
            ),
            array('%d', '%s')
        );
    
        if ($inserted === false) {
            error_log('Failed to insert into database: ' . $wpdb->last_error);
            return false; // Return false on failure
        }
    
        $taskId = $wpdb->insert_id;
    
        // Prepare data for publishing
        $data = ['order_id' => $order_id];
        
        // Publish to PubSub topic
        $publishResult = $this->publishToTopic('Cashtoken', $taskId, $data);
    
        if ($publishResult === false) {
            error_log('Failed to publish to Cashtoken topic');
            return false; // Return false on failure
        }
    
        return $taskId; // Return the task ID on success
    }
    
}
