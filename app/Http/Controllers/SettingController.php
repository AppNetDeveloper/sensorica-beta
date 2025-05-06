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
        return view('settings.setting');
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
        // Actualiza variables de entorno
        $arrEnv = [
            'TIMEZONE' => $request->timezone,
            'SITE_RTL' => !isset($request->SITE_RTL) ? 'off' : 'on',
        ];
        UtilityFacades::setEnvironmentValue($arrEnv);

        $post = [
            'authentication'   => isset($request->authentication) ? 'activate' : 'deactivate',
            'timezone'         => isset($request->timezone) ? $request->timezone : '',
            'site_date_format' => isset($request->site_date_format) ? $request->site_date_format : '',
            'default_language' => isset($request->default_language) ? $request->default_language : '',
            'dark_mode'        => isset($request->dark_mode) ? $request->dark_mode : '',
            'color'            => isset($request->color) ? $request->color : '',
        ];

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
}
