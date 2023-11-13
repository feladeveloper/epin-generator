<?php 
namespace Sgs\Buybyraffle;
class BuyByRaffleEnvConfig {
    private $configFilePath;

    public function __construct() {
        $this->setEnvironmentConfig();
    }

    private function setEnvironmentConfig() {
        $environment = wp_get_environment_type();

        switch ($environment) {
            case 'development':
                $this->configFilePath = 'C:\wamp64\www\wordpress\buybyraffle-dcc92f760bee.json';
                break;
            case 'staging':
                $this->configFilePath = '/home/master/applications/aczbbjzsvv/private_html/buybyraffle-dcc92f760bee.json';
                break;
            case 'production':
                $this->configFilePath = '/home/master/applications/bbqpcmbxkq/private_html/buybyraffle-dcc92f760bee.json';
                break;
            default:
                error_log("Unrecognized environment type: $environment");
                $this->configFilePath = '/path/to/default/config.json'; // Define a default path or handle the error accordingly
        }
    }

    public function getConfigFilePath() {
        return $this->configFilePath;
    }
}
