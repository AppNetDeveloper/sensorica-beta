<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class MqttServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(MqttClient::class, function ($app) {
            $server = env('MQTT_SERVER');
            $port = intval(env('MQTT_PORT'));
            $clientId = 'php-mqtt-client2';

            $connectionSettings = new ConnectionSettings(); 
            $connectionSettings->setKeepAliveInterval(60);
            $connectionSettings->setUseTls(false); 
            $connectionSettings->setTlsSelfSignedAllowed(false);
            // Asegúrate de que los valores de usuario y contraseña se están estableciendo correctamente
            $connectionSettings->setUsername(env('MQTT_USERNAME'));
            $connectionSettings->setPassword(env('MQTT_PASSWORD'));

            return new MqttClient($server, $port, $clientId, $connectionSettings);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
