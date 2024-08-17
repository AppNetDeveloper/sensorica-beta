<?php

namespace App\Helpers;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Exceptions\DataTransferException;

/**
 * este helper solo llama manda mesaje en mqtt y desconecta , no se queda activo. recomandable para usarlo en llamas rapidas pero que no se repiten
 * Se usa :
 *  use App\Helpers\MqttHelper;
 *  y se llama con:
 *  MqttHelper::publishMessage($topic, $message, env('MQTT_SERVER'), intval(env('MQTT_PORT')));
 *
 */




 class MqttHelper
 {
     public static function publishMessage($topic, $message, $server, $port)
     {
         $clientId = uniqid();
         $maxRetries = 3;
         $retryDelay = 5; // seconds

         $connectionSettings = new ConnectionSettings();
         $connectionSettings->setKeepAliveInterval(60);
         $connectionSettings->setUseTls(false);
         $connectionSettings->setTlsSelfSignedAllowed(false);
         $connectionSettings->setUsername(env('MQTT_USERNAME'));
         $connectionSettings->setPassword(env('MQTT_PASSWORD'));

         $attempt = 0;
         $mqtt = null;

         while ($attempt < $maxRetries) {
             try {
                 if ($mqtt === null || !$mqtt->isConnected()) {
                     $mqtt = new MqttClient($server, $port, $clientId);
                     $mqtt->connect($connectionSettings, true);

                     Log::info('Connected to MQTT server.', [
                         'server' => $server,
                         'port' => $port,
                         'clientId' => $clientId
                     ]);
                 }

                 $mqtt->publish($topic, json_encode($message), 0);

                 Log::info('Published MQTT message.', [
                     'topic' => $topic,
                     'message' => $message
                 ]);

                 // Sólo desconectamos si no planeamos usar el objeto nuevamente.
                 $mqtt->disconnect();

                 Log::info('Disconnected from MQTT server.');
                 return; // Salimos si la publicación fue exitosa
             } catch (DataTransferException $e) {
                 Log::warning('Data transfer failed. Attempting to reconnect...', [
                     'error' => $e->getMessage(),
                     'attempt' => $attempt + 1
                 ]);

                 $attempt++;
                 $mqtt = null; // Forzamos la reconexión
                 if ($attempt < $maxRetries) {
                     sleep($retryDelay);
                     continue; // Intentamos reconectar y reintentar
                 } else {
                     Log::critical('Exceeded maximum number of retries due to data transfer issues. Message could not be published.', [
                         'topic' => $topic,
                         'message' => $message
                     ]);
                     throw $e; // Opción para relanzar la excepción tras los intentos máximos
                 }
             } catch (\Exception $e) {
                 Log::error('Failed to publish MQTT message. Attempt ' . ($attempt + 1) . ' of ' . $maxRetries, [
                     'error' => $e->getMessage(),
                     'topic' => $topic,
                     'message' => $message
                 ]);

                 $attempt++;
                 $mqtt = null; // Forzamos la reconexión en el siguiente intento
                 if ($attempt < $maxRetries) {
                     sleep($retryDelay);
                 } else {
                     Log::critical('Exceeded maximum number of retries. Message could not be published.', [
                         'topic' => $topic,
                         'message' => $message
                     ]);
                     throw $e; // Opción para relanzar la excepción tras los intentos máximos
                 }
             }
         }
     }
 }

