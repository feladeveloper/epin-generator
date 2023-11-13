<?php 
namespace Sgs\Buybyraffle;

class BuyByRaffleLogger {
    /**
     * Logs a message and sends an email to the admin and the user, if applicable.
     *
     * @param string $message The message to be logged.
     * @param string $user_action The action the user was performing.
     */
    public static function log($message, $user_action = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'buybyraffle_error_logs';
        $current_user_id = get_current_user_id(); // Returns 0 if no user is logged in.
        $logged_at = current_time('mysql');
    
        // Prepare data for insertion
        $data = array(
            'user_id' => $current_user_id ? $current_user_id : null,
            'user_action' => $user_action,
            'message' => $message,
            'logged_at' => $logged_at
        );
        $format = array('%d', '%s', '%s', '%s');
    
        // Insert data into the database
        $wpdb->insert($table_name, $data, $format);

        // Send email if not on localhost
        if (!self::isLocalhost()) {
            self::emailAdminAndUser($message, $current_user_id, $user_action);
        }
        error_log($message);
    }
    
    /**
     * Sends an email to the site administrator with error details.
     *
     * @param string $message The error message.
     * @param int $userId The ID of the user who experienced the error.
     * @param string $userAction The action the user was performing.
     */
    private static function emailAdminAndUser($message, $userId, $userAction) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $subject = "Error Notification from $site_name";

        // Retrieve user email and name if available.
        $user_info = $userId ? get_userdata($userId) : null;
        $user_email = $user_info ? $user_info->user_email : 'Not logged in';
        $user_name = $user_info ? $user_info->display_name : 'Guest';

        // Construct the email body.
        $body = "<p>An error has occurred on your site:</p>
                 <p><strong>Error Message:</strong> {$message}</p>
                 <p><strong>User:</strong> {$user_name} ({$user_email})</p>
                 <p><strong>Action:</strong> {$userAction}</p>
                 <p>This email is sent automatically by the BuyByRaffle plugin.</p>";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        if (!empty($user_email) && $user_email !== 'Not logged in') {
            $headers[] = 'Cc: ' . $user_email;
        }

        wp_mail($admin_email, $subject, $body, $headers);
    }

    /**
     * Determines if the current request is from a localhost environment.
     *
     * @return bool True if it's localhost, false otherwise.
     */
    private static function isLocalhost() {
        $whitelist = array('127.0.0.1', '::1');
        return in_array($_SERVER['REMOTE_ADDR'], $whitelist);
    }
}
