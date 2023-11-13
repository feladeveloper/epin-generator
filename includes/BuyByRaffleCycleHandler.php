<?php 
/**
 * Handles the database operations using PDO for the Raffle system.
 *
 * This class is responsible for establishing a connection to the database
 * and executing queries, particularly for the Raffle system. It retrieves
 * the necessary database credentials from WordPress options.
 */

namespace Sgs\Buybyraffle;

use Exception;

class BuyByRaffleCycleHandler {

    /**
     * Constructor.
     */
    public function __construct() {
        // Any necessary constructor code can go here.
    }

    /**
     * Posts a new raffle cycle to an external API using Basic Authentication.
     *
     * @param array $raffleCycleData Data for the raffle cycle.
     * @return string|NULL|array The ID of the last inserted row from the API response.
     * @throws Exception If there's an error in API request or response.
     */
    public function createRaffleCycle($raffleCycleData) {
        try{
            $configPath = $this->getConfigPath();
            //error_log(print_r($configPath, true));
           
            $configArray = $this->loadConfig($configPath);
            //error_log(print_r($configArray, true));
            
            // Assign each value to a variable
            $idpTokenPassword = $configArray['idp_token_password'];
            $idpTokenUsername = $configArray['idp_token_username'];
            $idpBaseUrl = $configArray['idp_base_url'];
            $pgsCashtokenCampaignId = $configArray['pgs_cashtoken_campaign_id'];
            $email = $configArray['email'];
            $apiUrl = $configArray['api_url'];
            $authPassword = $configArray['auth_password'];


            $data_to_send = json_encode($raffleCycleData);

            // Basic Auth credentials (replace with actual credentials)
            $base64_credentials = base64_encode($email . ':' . $authPassword);

            $api_response = wp_remote_post($apiUrl, [
                'body' => $data_to_send,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic ' . $base64_credentials,
                ],
                'method' => 'POST',
                'data_format' => 'body',
                'timeout' => 15 // Increase the timeout to 15 seconds
            ]);
            //error_log($api_response['response']['code']);
            //if (!is_wp_error($api_response) && $api_response['response']['code'] == 200) {
            if(!is_wp_error($api_response) && $api_response['response']['code'] >= 200 && $api_response['response']['code'] < 300) {
                $response_data = json_decode( wp_remote_retrieve_body( $api_response ), true );
                 // Check if 'raffle_cycle_id' is set in the response
                if (isset($response_data['raffle_cycle_id'])) {
                    $raffle_cycle_id = $response_data['raffle_cycle_id'];
                    //error_log($raffle_cycle_id);
                    return array(0=>$raffle_cycle_id, 1=>$api_response['response']['code']);
                    // Do something with $raffle_cycle_id
                } else {
                    // Handle the case where 'raffle_cycle_id' is not set
                    $message = "Raffle Cycle ID was not created. HTTP Error coder";
                    \Sgs\Buybyraffle\BuyByRaffleLogger::log($message, 'Creating a raffle cycle');
                    return 0;
                   
                }
            }elseif (is_wp_error($api_response)) {
                $error_message = $api_response->get_error_message();
                throw new Exception( $error_message );
            }elseif($api_response['response']['code'] == 404) {
                throw new Exception( "Remote create raffle cycle API could not be found." );
            }elseif($api_response['response']['code'] == 401) {
                throw new Exception( "Authentication to the remote create raffle cycle API failed." );
            } else{
                throw new Exception( "The remote create raffle cycle API server is down." );
            }

        } catch (Exception $e) {
            $message = 'Error in createRaffleCycle: ' . $e->getMessage();
            \Sgs\Buybyraffle\BuyByRaffleLogger::log($message, 'Creating a raffle cycle');
            return array(1 =>$api_response['response']['code']);
        }
    }

    /**
     * Determines the configuration file path based on the server environment.
     *
     * @return string Configuration file path.
     */
    private function getConfigPath() {
        $environment = wp_get_environment_type();
    
        switch ($environment) {
            case 'development':
                return 'C:\wamp64\www\wordpress\buybyraffle_local_env.json';
            case 'staging':
                return '/home/master/applications/aczbbjzsvv/private_html/buybyraffle_env.json';
            case 'production':
                return '/home/master/applications/bbqpcmbxkq/private_html/buybyraffle_env.json';
            default:
                // Handle unexpected environment
                $errorMessage = "Unexpected environment type: $environment";
                error_log($errorMessage);

                // Send an email notification
                $to = 'terungwa@cashtoken.africa'; // Replace with your admin email address
                $subject = 'Configuration Error in BuyByRaffleCycleHandler';
                $message = "An error occurred in the BuyByRaffleCycleHandler: $errorMessage";
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
     * Loads the configuration from a JSON file.
     *
     * @param string $configPath Path to the configuration file.
     * @return array Configuration data as an associative array.
     * @throws Exception If the file does not exist or cannot be parsed.
     */
    public function loadConfig($configPath) {
        if (!file_exists($configPath)) {
            throw new Exception('Configuration file does not exist: ' . $configPath);
        }
    
        $config = json_decode(file_get_contents($configPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decoding JSON configuration file: ' . json_last_error_msg());
        }
    
        // Prepare an array to hold the loaded configurations
        $loadedConfig = [
            'idp_token_password' => $config['IDP_TOKEN_PASSWORD'] ?? '',
            'idp_token_username' => $config['IDP_TOKEN_USERNAME'] ?? '',
            'idp_base_url' => $config['IDP_BASE_URL'] ?? '',
            'pgs_cashtoken_campaign_id' => $config['PGS_CASHTOKEN_CAMPAIGN_ID'] ?? '',
            'email' => $config['EMAIL'] ?? '',
            'api_url' => $config['API_URL'] ?? '',
            'auth_password' => $config['AUTH_PASSWORD'] ?? '',
        ];
    
        return $loadedConfig;
    }
    
}