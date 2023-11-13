<?php
namespace Sgs\Buybyraffle;
/**
 * Handles the creation and management of the database connection
 * and the interaction with the external API for the Raffle system.
 */
class BuyByRaffleApiDbConnector {
    /**
     * @var PDO|null The PDO instance for database connections.
     */
    private static $pdo = null;

    /**
     * The URL of the token refresh endpoint.
     *
     * @var string
     */
    private static $tokenRefreshUrl = 'https://example.com/api/refresh_token'; // Replace with actual token refresh endpoint

    /**
     * Create and return a PDO connection.
     *
     * @return PDO
     * @throws Exception If the database connection fails.
     */
    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                // Set the credentials from WordPress options.
                $host = get_option('_databaseHost');
                $dbname = get_option('_databaseName');
                $user = get_option('_databaseUser');
                $pass = get_option('_databasePassword');
                $port = get_option('_databasePort', 3306); // Use default port if not specified.

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                // Log and handle the exception as needed.
                error_log($e->getMessage());
                throw new Exception("Database connection error: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
    /**
     * Refreshes the bearer token and returns it.
     *
     * @return string The new bearer token.
     * @throws Exception If token refresh fails.
     */
    public static function refreshToken() {
        $response = wp_remote_post(self::$tokenRefreshUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => json_encode(['refresh_token' => get_option('_refreshToken')]), // Assume you store the refresh token in WP options
            'method' => 'POST',
            'data_format' => 'body',
        ]);

        if (is_wp_error($response)) {
            throw new Exception('Error refreshing token: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['access_token'])) {
            // Save the new access token and its expiration time.
            update_option('_accessToken', $data['access_token']);
            update_option('_accessTokenExpires', time() + (60 * 60)); // 1 hour from now.

            return $data['access_token'];
        } else {
            throw new Exception('Invalid response during token refresh.');
        }
    }

    /**
     * Gets the current bearer token, refreshing it if it's expired.
     *
     * @return string The bearer token.
     */
    public static function getBearerToken() {
        $expires = get_option('_accessTokenExpires', 0);
        $currentToken = get_option('_accessToken', '');

        if (time() >= $expires) {
            return self::refreshToken();
        }

        return $currentToken;
    }
}