<?php
namespace App\Facades;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Utility {

    public function settings()
    {
        $data = DB::table('settings');
        if(Auth::check())
        {
            $userId = Auth::user()->creatorId();
            $data   = $data->where('created_by', '=', $userId);
        }
        else
        {
            $data = $data->where('created_by', '=', 1);
        }
        $data = $data->get();

        $settings = [
            "site_date_format" => "M j, Y",
            "site_time_format" => "g:i A",
            "authentication"   => "deactivate",
            "default_language" => 'en'
        ];
        foreach($data as $row)
        {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public function setEnvironmentValue(array $values)
    {
        $envFile = app()->environmentFilePath();
        $str     = file_get_contents($envFile);
        if(count($values) > 0)
        {
            foreach($values as $envKey => $envValue)
            {
                $envValue = trim($envValue);
                // Buscamos la línea que comience con la clave y capturamos lo que haya hasta el final de la línea
                $pattern = '/^' . preg_quote($envKey, '/') . '=(.*)$/m';
                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = ($keyPosition !== false && $endOfLinePosition !== false) ? substr($str, $keyPosition, $endOfLinePosition - $keyPosition) : false;
                if($keyPosition === false || $endOfLinePosition === false || !$oldLine)
                {
                    $str .= "{$envKey}='{$envValue}'\n";
                }
                else
                {
                    $str = str_replace($oldLine, "{$envKey}='{$envValue}'", $str);
                }
            }
        }
        // Aseguramos que el archivo termine con una nueva línea
        $str = rtrim($str) . "\n";
        if(!file_put_contents($envFile, $str))
        {
            return false;
        }
        return true;
    }

    public function getValByName($key)
    {
        $setting = $this->settings();
        if(!isset($setting[$key]) || empty($setting[$key]))
        {
            $setting[$key] = '';
        }
        return $setting[$key];
    }

    public function languages()
    {
        $dir  = base_path() . '/resources/lang/';
        $glob = glob($dir . "*", GLOB_ONLYDIR);
        $arrLang = array_map(function ($value) use ($dir) {
            return str_replace($dir, '', $value);
        }, $glob);
        $arrLang = array_map(function ($value) {
            return preg_replace('/[0-9]+/', '', $value);
        }, $arrLang);
        $arrLang = array_filter($arrLang);
        return $arrLang;
    }

    public function delete_directory($dir)
    {
        if(!file_exists($dir))
        {
            return true;
        }
        if(!is_dir($dir))
        {
            return unlink($dir);
        }
        foreach(scandir($dir) as $item)
        {
            if($item == '.' || $item == '..')
            {
                continue;
            }
            if(!$this->delete_directory($dir . DIRECTORY_SEPARATOR . $item))
            {
                return false;
            }
        }
        return rmdir($dir);
    }

    public function dateFormat($date)
    {
        $settings = $this->settings();
        return date($settings['site_date_format'], strtotime($date));
    }
}
