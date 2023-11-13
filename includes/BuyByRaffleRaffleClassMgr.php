<?php 
/**
 * BuyByRaffleRaffleClassMgr
 *
 * This class manages the raffle classes used in the plugin. It allows for setting
 * the raffle classes upon plugin activation and provides a method to retrieve
 * a raffle class ID by its name.
 */
namespace Sgs\Buybyraffle;
class BuyByRaffleRaffleClassMgr {

    /**
     * Initializes the raffle classes within the WordPress options table.
     *
     * This method is intended to be called on plugin activation. It sets up
     * an array of raffle classes, each with a unique ID and name, and stores
     * them in the wp_options table for later retrieval.
     */
    public static function init_raffle_classes() {
        // Define the array of raffle classes
        $raffle_classes = [
            ['raffle_class_id' => 1, 'raffle_class_name' => 'bait'],
            ['raffle_class_id' => 2, 'raffle_class_name' => 'hero'],
            ['raffle_class_id' => 3, 'raffle_class_name' => 'solo']
        ];

        // Save the array of raffle classes in the wp_options table
        update_option('raffle_classes', $raffle_classes);
    }

    /**
     * Retrieves the raffle class ID by its name.
     *
     * @param string $raffle_class_name The name of the raffle class to search for.
     * @return int|null Returns the ID of the raffle class if found, or null otherwise.
     *
     * This method looks up the raffle class ID based on the provided raffle class name.
     * It is useful when you have the name of the raffle class and need to retrieve its
     * corresponding ID for database operations or logic checks.
     */
    public static function get_raffle_class_id_by_name($raffle_class_name) {
        // Retrieve the array of raffle classes from the wp_options table
        $raffle_classes = get_option('raffle_classes');

        // If the option is not set or an error occurred, return null
        if (!$raffle_classes) {
            return null;
        }

        // Iterate through the array of raffle classes
        foreach ($raffle_classes as $raffle_class) {
            // Check if the current class name matches the provided name
            if ($raffle_class['raffle_class_name'] === $raffle_class_name) {
                // If a match is found, cast the ID to an integer for consistency and return it
                return (int) $raffle_class['raffle_class_id'];
            }
        }

        // If no match is found, return null
        return null;
    }

    /**
     * Retrieves the raffle class name by its ID.
     *
     * @param int $raffle_class_id The ID of the raffle class to search for.
     * @return string|null The name of the raffle class if found, or null otherwise.
     */
    public static function get_raffle_class_name_by_id($raffle_class_id) {
        $raffle_classes = get_option('raffle_classes');

        if (!$raffle_classes) {
            return null; // The option has not been set or an error occurred.
        }

        foreach ($raffle_classes as $raffle_class) {
            if ((int) $raffle_class['raffle_class_id'] === $raffle_class_id) {
                return $raffle_class['raffle_class_name'];
            }
        }

        return null; // Raffle class ID not found.
    }
}
