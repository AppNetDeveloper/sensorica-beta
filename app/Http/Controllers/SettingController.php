<?php

namespace App\Http\Controllers;

use App\Facades\UtilityFacades;
use App\Mail\TestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SettingController extends Controller
{
    public function index()
    {
        // Valores por defecto para RFID
        $rfid_config = [
            'rfid_reader_ip' => env('RFID_READER_IP', '192.168.1.100'),
            'rfid_reader_port' => env('RFID_READER_PORT', '1080'),
            'rfid_monitor_url' => env('RFID_MONITOR_URL', 'http://172.25.25.173:3000/')
        ];
        
        // Valores por defecto para Redis
        $redis_config = [
            'redis_host' => env('REDIS_HOST', '127.0.0.1'),
            'redis_port' => env('REDIS_PORT', '6379'),
            'redis_password' => env('REDIS_PASSWORD', ''),
            'redis_prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_')
        ];
        
        // Leer el archivo .env directamente
        $envPath = base_path('.env');
        
        // Verificar si el archivo existe y es legible
        if (!file_exists($envPath)) {
            \Log::error("El archivo .env no existe en: " . $envPath);
        } elseif (!is_readable($envPath)) {
            \Log::error("No se puede leer el archivo .env. Permisos: " . substr(sprintf('%o', fileperms($envPath)), -4));
        } else {
            // Leer el archivo .env si existe
            $envContent = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            // Buscar las variables en el archivo .env
            foreach ($envContent as $line) {
                $line = trim($line);
                
                // Ignorar comentarios y líneas vacías
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }
                
                // Buscar RFID_READER_IP
                if (strpos($line, 'RFID_READER_IP=') === 0) {
                    $value = substr($line, strpos($line, '=') + 1);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    if (!empty($value)) {
                        $rfid_config['rfid_reader_ip'] = $value;
                    }
                }
                
                // Buscar RFID_READER_PORT
                if (strpos($line, 'RFID_READER_PORT=') === 0) {
                    $value = substr($line, strpos($line, '=') + 1);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    if (is_numeric($value)) {
                        $rfid_config['rfid_reader_port'] = $value;
                    }
                }
            }
        }
        
        // Registrar los valores que se están enviando a la vista
        \Log::info('Valores finales para la vista:', $rfid_config);
        
        // Buscar las variables en el archivo .env
        foreach ($envContent as $line) {
            $line = trim($line);
            
            // Ignorar comentarios y líneas vacías
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Buscar RFID_READER_IP
            if (strpos($line, 'RFID_READER_IP=') === 0) {
                $value = substr($line, strpos($line, '=') + 1);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (!empty($value)) {
                    $rfid_config['rfid_reader_ip'] = $value;
                }
            }
            
            // Buscar RFID_READER_PORT
            if (strpos($line, 'RFID_READER_PORT=') === 0) {
                $value = substr($line, strpos($line, '=') + 1);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (is_numeric($value)) {
                    $rfid_config['rfid_reader_port'] = $value;
                }
            }
        }
        
        // Registrar los valores que se están enviando a la vista
        \Log::info('RFID Config:', [
            'ip' => $rfid_config['rfid_reader_ip'],
            'port' => $rfid_config['rfid_reader_port'],
            'env_path' => $envPath,
            'file_exists' => file_exists($envPath)
        ]);

        return view('settings.setting', compact('rfid_config'));
    }

    public function getmail()
    {
        $timezones = config('timezones');
        $mail_config = [
            'mail_driver'      => config('mail.mailer'),
            'mail_host'        => config('mail.host'),
            'mail_port'        => config('mail.port'),
            'mail_username'    => config('mail.username'),
            'mail_password'    => config('mail.password'),
            'mail_encryption'  => config('mail.encryption'),
            'mail_from_address'=> config('mail.from.address'),
            'mail_from_name'   => config('mail.from.name'),
        ];
        
        return view('settings.emailset', compact('timezones', 'mail_config'));
    }
    
    public function saveEmailSettings(Request $request)
    {
        // Si fuera modo demo se podría desactivar la acción.
        $arrEnv = [
            'MAIL_MAILER'      => $request->mail_driver,
            'MAIL_HOST'        => $request->mail_host,
            'MAIL_PORT'        => $request->mail_port,
            'MAIL_USERNAME'    => $request->mail_username,
            'MAIL_PASSWORD'    => $request->mail_password,
            'MAIL_ENCRYPTION'  => $request->mail_encryption,
            'MAIL_FROM_NAME'   => $request->mail_from_name,
            'MAIL_FROM_ADDRESS'=> $request->mail_from_address,
        ];
        UtilityFacades::setEnvironmentValue($arrEnv);

        return redirect()->back()->with('success', __('Setting successfully updated.'));
    }

    /**
     * Guarda las variables de entorno para Finish Shift Emails.
     */
    public function saveFinishShiftEmailsSettings(Request $request)
    {
        $data = $request->validate([
            'EMAIL_FINISH_SHIFT_LISTWORKERS'        => 'nullable|string',
            'EMAIL_FINISH_SHIFT_LISTCONFECCIONSIGNED' => 'nullable|string',
        ]);

        // Reescribimos solo estas dos en el .env
        UtilityFacades::setEnvironmentValue([
            'EMAIL_FINISH_SHIFT_LISTWORKERS'         => $data['EMAIL_FINISH_SHIFT_LISTWORKERS'] ?? '',
            'EMAIL_FINISH_SHIFT_LISTCONFECCIONSIGNED' => $data['EMAIL_FINISH_SHIFT_LISTCONFECCIONSIGNED'] ?? '',
        ]);

        return redirect()
            ->back()
            ->with('success', __('Finish Shift Email settings updated.'));
    }


    public function getdate()
    {
        $settings = UtilityFacades::settings();
        $timezones = config('timezones');
        return view('settings.datetime', compact('settings', 'timezones'));
    }

    public function saveSystemSettings(Request $request)
    {
        // Update environment variables
        $arrEnv = [
            // App URLs
            'APP_URL' => rtrim($request->app_url, '/'),
            'ASSET_URL' => $request->filled('asset_url') ? rtrim($request->asset_url, '/') : '',
            
            // Timezone
            'APP_TIMEZONE' => $request->timezone,
            'TIMEZONE' => $request->timezone,
            
            // Database configuration
            'DB_CONNECTION' => $request->db_connection ?? 'mysql',
            'DB_HOST' => $request->db_host ?? '127.0.0.1',
            'DB_PORT' => $request->db_port ?? '3306',
            'DB_DATABASE' => $request->db_database ?? '',
            'DB_USERNAME' => $request->db_username ?? '',
            'DB_PASSWORD' => $request->db_password ?? '',
            
            // MQTT Configuration
            'MQTT_SERVER' => $request->mqtt_server ?? '',
            'MQTT_PORT' => $request->mqtt_port ?? '1883',
            'MQTT_SENSORICA_SERVER' => $request->mqtt_sensorica_server ?? '127.0.0.1',
            'MQTT_SENSORICA_PORT' => $request->mqtt_sensorica_port ?? '1883',
            'MQTT_SENSORICA_SERVER_BACKUP' => $request->mqtt_sensorica_server_backup ?? '',
            'MQTT_SENSORICA_PORT_BACKUP' => $request->mqtt_sensorica_port_backup ?? '1883',
            
            // Backup Configuration
            'BACKUP_ARCHIVE_PASSWORD' => $request->backup_archive_password ?? null,
            'BACKUP_ARCHIVE_ENCRYPTION' => $request->backup_archive_encryption ?? null,
            
            // SFTP Configuration
            'SFTP_HOST' => $request->sftp_host ?? 'localhost',
            'SFTP_USERNAME' => $request->sftp_username ?? '',
            'SFTP_PASSWORD' => $request->sftp_password ?? '',
            'SFTP_PORT' => $request->sftp_port ?? '22',
            'SFTP_ROOT' => $request->sftp_root ?? '/var/www/ftp/',
            
            // System Settings
            'SHIFT_TIME' => $request->shift_time ?? '08:00:00',
            'CLEAR_DB_DAY' => $request->clear_db_day ?? '40',
            'PRODUCTION_MIN_TIME' => $request->production_min_time ?? '3',
            'PRODUCTION_MAX_TIME' => $request->production_max_time ?? '5',
            'PRODUCTION_MIN_TIME_WEIGHT' => $request->production_min_time_weight ?? '30',
            'USE_CURL' => $request->has('use_curl') ? 'true' : 'false',
            'RFID_AUTO_ADD' => $request->has('rfid_auto_add') ? 'true' : 'false',
            
            // External API Settings
            'USE_CURL' => $request->has('use_curl') ? 'true' : 'false',
            'EXTERNAL_API_QUEUE_TYPE' => strtolower($request->external_api_queue_type ?? 'put'),
            'EXTERNAL_API_QUEUE_MODEL' => $request->external_api_queue_model ?? 'dataToSend3',
            
            // RFID Settings
            'RFID_AUTO_ADD' => $request->has('rfid_auto_add') ? 'true' : 'false',
            
            // Local Server Settings
            'LOCAL_SERVER' => rtrim($request->local_server ?? 'http://127.0.0.1', '/') . '/',
            'TOKEN_SYSTEM' => $request->token_system ?? '',
            'TCP_SERVER' => $request->tcp_server ?? 'localhost',
            'TCP_PORT' => $request->tcp_port ?? '8000',
            
            // Production Settings
            'SHIFT_TIME' => $request->shift_time ?? '08:00:00',
            'PRODUCTION_MIN_TIME' => $request->production_min_time ?? '3',
            'PRODUCTION_MAX_TIME' => $request->production_max_time ?? '5',
            'CLEAR_DB_DAY' => $request->clear_db_day ?? '40',
            'PRODUCTION_MIN_TIME_WEIGHT' => $request->production_min_time_weight ?? '30',
            
            // WhatsApp Configuration
            'WHATSAPP_LINK' => $request->whatsapp_link ?? 'http://127.0.0.1:3005',
            'WHATSAPP_PHONE_NOT' => $request->whatsapp_phone_not ?? '',
            
            // Other settings
            'SITE_RTL' => !isset($request->SITE_RTL) ? 'off' : 'on',
        ];
        
        // Save to .env file
        UtilityFacades::setEnvironmentValue($arrEnv);

        // Save to database (only non-sensitive or necessary fields)
        $post = [
            // Authentication
            'authentication'   => $request->has('authentication') ? 'activate' : 'deactivate',
            
            // General settings
            'timezone'         => $request->timezone ?? '',
            'site_date_format' => $request->site_date_format ?? '',
            'default_language' => $request->default_language ?? '',
            'dark_mode'        => $request->dark_mode ?? '',
            'color'            => $request->color ?? '',
            
            // URLs
            'app_url'          => $request->app_url ?? '',
            'asset_url'        => $request->asset_url ?? '',
            
            // Database (for reference in admin panel)
            'db_connection'    => $request->db_connection ?? 'mysql',
            'db_host'          => $request->db_host ?? '127.0.0.1',
            'db_port'          => $request->db_port ?? '3306',
            'db_database'      => $request->db_database ?? '',
            'db_username'      => $request->db_username ?? '',
            
            // System Settings
            'shift_time'       => $request->shift_time ?? '08:00:00',
            'clear_db_day'     => $request->clear_db_day ?? '40',
            'production_min_time' => $request->production_min_time ?? '3',
            'production_max_time' => $request->production_max_time ?? '5',
            'production_min_time_weight' => $request->production_min_time_weight ?? '30',
            'use_curl'         => $request->has('use_curl') ? 'true' : 'false',
            'rfid_auto_add'    => $request->has('rfid_auto_add') ? 'true' : 'false',
            
            // External API Settings
            'external_api_queue_type' => strtolower($request->external_api_queue_type ?? 'put'),
            'external_api_queue_model' => $request->external_api_queue_model ?? 'dataToSend3',
            'local_server'     => rtrim($request->local_server ?? 'http://127.0.0.1', '/') . '/',
            
            // WhatsApp Configuration
            'whatsapp_link'    => $request->whatsapp_link ?? 'http://127.0.0.1:3005',
            'whatsapp_phone_not' => $request->whatsapp_phone_not ?? '',
        ];
        
        // Don't save sensitive data in the database
        unset($post['db_password']);

        foreach ($post as $key => $data) {
            DB::insert(
                'insert into settings (`value`, `name`, `created_by`, `created_at`, `updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
                [
                    $data,
                    $key,
                    Auth::user()->creatorId(),
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]
            );
        }
        return redirect()->back()->with('success', __('Setting successfully updated.'));
    }

    public function getlogo()
    {
        $settings = UtilityFacades::settings();
        return view('settings.logo', compact('settings'));
    }

    public function store(Request $request)
    {
        // Validar y almacenar logos y favicon
        if ($request->hasFile('dark_logo')) {
            $request->validate([
                'dark_logo' => 'image|mimes:jpeg,png,jpg,svg|max:3072',
            ]);
            $logoName = 'dark_logo.png';
            $request->file('dark_logo')->storeAs('uploads/logo/', $logoName);
        }
        if ($request->hasFile('light_logo')) {
            $request->validate([
                'light_logo' => 'image|mimes:png',
            ]);
            $logoName = 'light_logo.png';
            $request->file('light_logo')->storeAs('uploads/logo/', $logoName);
        }
        if ($request->hasFile('favicon')) {
            $request->validate([
                'favicon' => 'image|mimes:png',
            ]);
            $favicon = 'favicon.png';
            $request->file('favicon')->storeAs('uploads/logo/', $favicon);
        }

        // Actualiza APP_NAME en el archivo .env; la función en la fachada reemplaza la línea existente
        UtilityFacades::setEnvironmentValue(['APP_NAME' => $request->app_name]);

        $post = $request->except('_token');
        foreach ($post as $key => $data) {
            DB::insert(
                'insert into settings (`value`, `name`, `created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
                [
                    $data,
                    $key,
                    Auth::user()->creatorId(),
                ]
            );
        }
        return redirect()->back()->with('success', __('Setting successfully updated.'));
    }

    public function testMail()
    {
        return view('settings.test_mail');
    }

    public function testSendMail(Request $request)
    {
        $validator = \Validator::make($request->all(), ['email' => 'required|email']);
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }
        try {
            Mail::to($request->email)->send(new TestMail());
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            return redirect()->back()->with('error', $smtp_error);
        }
        return redirect()->back()->with('success', __('Email send Successfully.'));
    }
    public function testFinishShiftEmails()
    {
        Log::info("message: Envio de test EMAIL SHIFT FIN ");
        // Llamamos a tu método de envío
        try {
            $this->sendFinishShiftEmails();
            return redirect()
                ->back()
                ->with('success', __('Test emails have been dispatched. Check logs for details.'));
        } catch (\Throwable $e) {
            Log::error('testFinishShiftEmails: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', __('An error occurred while dispatching test emails.'));
        }
    }

    /**
     * Tu método existente (puede ser privado o público)
     */
    public function sendFinishShiftEmails()
    {
        // 1. Leemos y explodemos las dos listas
        $raw1  = trim(env('EMAIL_FINISH_SHIFT_LISTWORKERS', ''));
        $raw2  = trim(env('EMAIL_FINISH_SHIFT_LISTCONFECCIONSIGNED', ''));
        $list1 = array_filter(array_map('trim', explode(',', $raw1)));
        $list2 = array_filter(array_map('trim', explode(',', $raw2)));
    
        // 2. Si ambas listas están vacías, no hacemos nada aquí
        if (empty($list1) && empty($list2)) {
            $this->info('sendFinishShiftEmails: No hay correos configurados en .env, abortando envío pero permitiendo continuar la ejecución externa.');
            return; // salimos de este método, pero el código que lo llamó sigue
        }
    
        // 3. Base URL limpia
        $appUrl = rtrim(env('LOCAL_SERVER'), '/');
    
        // 4. Configuramos los jobs
        $jobs = [
            [
                'emails'   => $list1,
                'endpoint' => $appUrl . '/api/workers-export/send-email',
                'log_key'  => 'report',
            ],
            [
                'emails'   => $list2,
                'endpoint' => $appUrl . '/api/workers-export/send-assignment-list',
                'log_key'  => 'assignment',
            ],
        ];
    
        // 5. Cliente Guzzle
        $client = new \GuzzleHttp\Client([
            'timeout'     => 0.1,
            'http_errors' => false,
            'verify'      => false,
        ]);
    
        // 6. Envío asíncrono
        foreach ($jobs as $job) {
            foreach ($job['emails'] as $email) {
                $url = $job['endpoint'] . '?email=' . urlencode($email);
                $promise = $client->getAsync($url);
    
                $promise->then(
                    function ($response) use ($url, $job) {
                        \Log::info(sprintf(
                            "[%s][%s] GET %s → %d",
                            Carbon::now()->toDateTimeString(),
                            $job['log_key'],
                            $url,
                            $response->getStatusCode()
                        ));
                    },
                    function ($e) use ($url, $job) {
                        \Log::error(sprintf(
                            "[%s][%s] Error GET %s: %s",
                            Carbon::now()->toDateTimeString(),
                            $job['log_key'],
                            $url,
                            $e->getMessage()
                        ));
                    }
                );
    
                $promise->wait(false);
            }
        }
    }

    /**
     * Guarda la configuración del lector RFID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveRfidSettings(Request $request)
    {
        $request->validate([
            'rfid_reader_ip' => 'required|ip',
            'rfid_reader_port' => 'required|integer|min:1|max:65535',
            'rfid_monitor_url' => 'nullable|url',
        ]);

        // Actualizar el archivo .env con las nuevas configuraciones
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        // Actualizar o agregar RFID_READER_IP
        if (str_contains($envContent, 'RFID_READER_IP=')) {
            $envContent = preg_replace(
                '/^RFID_READER_IP=.*/m',
                'RFID_READER_IP=' . $request->rfid_reader_ip,
                $envContent
            );
        } else {
            $envContent .= "\nRFID_READER_IP=" . $request->rfid_reader_ip;
        }

        // Actualizar o agregar RFID_READER_PORT
        if (str_contains($envContent, 'RFID_READER_PORT=')) {
            $envContent = preg_replace(
                '/^RFID_READER_PORT=.*/m',
                'RFID_READER_PORT=' . $request->rfid_reader_port,
                $envContent
            );
        } else {
            $envContent .= "\nRFID_READER_PORT=" . $request->rfid_reader_port;
        }

        // Actualizar o agregar RFID_MONITOR_URL
        if (str_contains($envContent, 'RFID_MONITOR_URL=')) {
            if (!empty($request->rfid_monitor_url)) {
                $envContent = preg_replace(
                    '/^RFID_MONITOR_URL=.*/m',
                    'RFID_MONITOR_URL=' . $request->rfid_monitor_url,
                    $envContent
                );
            } else {
                // Si el campo está vacío, eliminamos la línea
                $envContent = preg_replace('/^RFID_MONITOR_URL=.*\n?/m', '', $envContent);
            }
        } elseif (!empty($request->rfid_monitor_url)) {
            $envContent .= "\nRFID_MONITOR_URL=" . $request->rfid_monitor_url;
        }

        // Guardar los cambios en el archivo .env
        file_put_contents($envFile, $envContent);

        // Limpiar la caché de configuración
        \Artisan::call('config:clear');
        \Artisan::call('config:cache');

        return redirect()->back()->with('success', __('Configuración RFID guardada correctamente.'));
    }

    /**
     * Guarda la configuración de Redis.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveRedisSettings(Request $request)
    {
        $request->validate([
            'redis_host' => 'required|string|max:255',
            'redis_port' => 'required|integer|min:1|max:65535',
            'redis_password' => 'nullable|string|max:255',
            'redis_prefix' => 'required|string|max:255',
        ]);

        // Actualizar el archivo .env con las nuevas configuraciones
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        // Función auxiliar para actualizar o agregar una variable de entorno
        $updateEnvVar = function($key, $value) use (&$envContent) {
            if (str_contains($envContent, $key . '=')) {
                $envContent = preg_replace(
                    '/^' . preg_quote($key, '/') . '=.*/m',
                    $key . '=' . $value,
                    $envContent
                );
            } else {
                $envContent .= "\n" . $key . '=' . $value;
            }
        };

        // Actualizar o agregar cada variable de Redis
        $updateEnvVar('REDIS_HOST', $request->redis_host);
        $updateEnvVar('REDIS_PORT', $request->redis_port);
        $updateEnvVar('REDIS_PASSWORD', $request->filled('redis_password') ? $request->redis_password : '');
        $updateEnvVar('REDIS_PREFIX', $request->redis_prefix);

        // Guardar los cambios en el archivo .env
        file_put_contents($envFile, $envContent);

        // Limpiar la caché de configuración
        \Artisan::call('config:clear');
        \Artisan::call('config:cache');

        return redirect()->back()->with('success', __('Configuración de Redis guardada correctamente.'));
    }

    /**
     * Guarda la configuración de la base de datos de réplica.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveReplicaDbSettings(Request $request)
    {
        $request->validate([
            'replica_db_host' => 'required|string|max:255',
            'replica_db_port' => 'required|integer|min:1|max:65535',
            'replica_db_database' => 'required|string|max:255',
            'replica_db_username' => 'required|string|max:255',
            'replica_db_password' => 'required|string|max:255',
        ]);

        // Actualizar el archivo .env con las nuevas configuraciones
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        // Función auxiliar para actualizar o agregar una variable de entorno
        $updateEnvVar = function($key, $value) use (&$envContent) {
            if (str_contains($envContent, $key . '=')) {
                $envContent = preg_replace(
                    '/^' . preg_quote($key, '/') . '=.*/m',
                    $key . '=' . $value,
                    $envContent
                );
            } else {
                $envContent .= "\n" . $key . '=' . $value;
            }
        };

        // Actualizar o agregar cada variable de la base de datos de réplica
        $updateEnvVar('REPLICA_DB_HOST', $request->replica_db_host);
        $updateEnvVar('REPLICA_DB_PORT', $request->replica_db_port);
        $updateEnvVar('REPLICA_DB_DATABASE', $request->replica_db_database);
        $updateEnvVar('REPLICA_DB_USERNAME', $request->replica_db_username);
        $updateEnvVar('REPLICA_DB_PASSWORD', $request->replica_db_password);

        // Guardar los cambios en el archivo .env
        file_put_contents($envFile, $envContent);

        // Limpiar la caché de configuración
        \Artisan::call('config:clear');
        \Artisan::call('config:cache');

        return response()->json([
            'success' => true,
            'message' => __('Configuración de la base de datos de réplica guardada correctamente.')
        ]);
    }

    /**
     * Prueba la conexión a la base de datos de réplica.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function testReplicaDbConnection(Request $request)
    {
        // Determine which field name format to use
        $host = $request->input('host', $request->input('replica_db_host'));
        $port = $request->input('port', $request->input('replica_db_port'));
        $database = $request->input('database', $request->input('replica_db_database'));
        $username = $request->input('username', $request->input('replica_db_username'));
        $password = $request->input('password', $request->input('replica_db_password'));

        // Validate the input
        $request->validate([
            'replica_db_host' => 'sometimes|required|string|max:255',
            'replica_db_port' => 'sometimes|required|integer|min:1|max:65535',
            'replica_db_database' => 'sometimes|required|string|max:255',
            'replica_db_username' => 'sometimes|required|string|max:255',
            'replica_db_password' => 'sometimes|required|string|max:255',
            // Legacy field names
            'host' => 'sometimes|required|string|max:255',
            'port' => 'sometimes|required|integer|min:1|max:65535',
            'database' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|max:255',
            'password' => 'sometimes|required|string|max:255',
        ]);

        $connection = 'replica_test';
        
        // Configure the temporary connection
        config([
            'database.connections.' . $connection => [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]
        ]);

        try {
            // Try to connect to the database
            DB::connection($connection)->getPdo();
            
            // Check if the database exists
            $databaseExists = DB::connection($connection)->select(
                "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", 
                [$database]
            );
            
            return response()->json([
                'success' => true,
                'database_exists' => !empty($databaseExists),
                'message' => __('Conexión exitosa a la base de datos.')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error de conexión: ') . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Creates the database on the remote server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createReplicaDatabase(Request $request)
    {
        // Determine which field name format to use
        $host = $request->input('host', $request->input('replica_db_host'));
        $port = $request->input('port', $request->input('replica_db_port'));
        $database = $request->input('database', $request->input('replica_db_database'));
        $username = $request->input('username', $request->input('replica_db_username'));
        $password = $request->input('password', $request->input('replica_db_password'));

        // Validate the input
        $request->validate([
            'replica_db_host' => 'sometimes|required|string|max:255',
            'replica_db_port' => 'sometimes|required|integer|min:1|max:65535',
            'replica_db_database' => 'sometimes|required|string|max:255',
            'replica_db_username' => 'sometimes|required|string|max:255',
            'replica_db_password' => 'sometimes|required|string|max:255',
            // Legacy field names
            'host' => 'sometimes|required|string|max:255',
            'port' => 'sometimes|required|integer|min:1|max:65535',
            'database' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|max:255',
            'password' => 'sometimes|required|string|max:255',
        ]);

        $connection = 'replica_creation';
        
        // Configure the temporary connection without specifying the database
        config([
            'database.connections.' . $connection => [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]
        ]);

        try {
            // Connect without specifying the database
            DB::purge($connection);
            
            // Create the database
            DB::connection($connection)->statement(
                "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
            
            return response()->json([
                'success' => true,
                'message' => __('Base de datos creada exitosamente.')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error al crear la base de datos: ') . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Guarda la configuración de Upload Stats.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveUploadStatsSettings(Request $request)
    {
        $request->validate([
            'mysql_server' => 'required|string|max:255',
            'mysql_port' => 'required|integer|min:1|max:65535',
            'mysql_db' => 'required|string|max:255',
            'mysql_user' => 'required|string|max:255',
            'mysql_password' => 'nullable|string|max:255',
            'mysql_table_line' => 'required|string|max:255',
            'mysql_table_sensor' => 'required|string|max:255',
        ]);

        // Ruta al archivo .env
        $envFile = base_path('.env');
        
        // Leer el contenido actual del archivo .env
        $envContent = file_get_contents($envFile);
        
        // Función auxiliar para actualizar o agregar una variable de entorno
        $updateEnvVar = function($key, $value) use (&$envContent) {
            // Escapar los caracteres especiales en el valor
            $escapedValue = str_replace(['\\', '\$'], ['\\\\', '\\$'], $value);
            
            // Patrón para buscar la variable de entorno
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$escapedValue}";
            
            // Si la variable ya existe, actualizarla, de lo contrario agregarla
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= PHP_EOL . $replacement;
            }
        };
        
        // Actualizar las variables de entorno para Upload Stats
        $updateEnvVar('MYSQL_SERVER', $request->mysql_server);
        $updateEnvVar('MYSQL_PORT', $request->mysql_port);
        $updateEnvVar('MYSQL_DB', $request->mysql_db);
        $updateEnvVar('MYSQL_USER', $request->mysql_user);
        $updateEnvVar('MYSQL_PASSWORD', $request->filled('mysql_password') ? $request->mysql_password : '');
        $updateEnvVar('MYSQL_TABLE_LINE', $request->mysql_table_line);
        $updateEnvVar('MYSQL_TABLE_SENSOR', $request->mysql_table_sensor);

        // Guardar los cambios en el archivo .env
        file_put_contents($envFile, $envContent);

        // Limpiar la caché de configuración
        \Artisan::call('config:clear');
        \Artisan::call('config:cache');

        return redirect()->back()->with('success', __('Configuración de Upload Stats guardada correctamente.'));
    }
}
