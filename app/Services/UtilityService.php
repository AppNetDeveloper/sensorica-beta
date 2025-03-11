<?php

namespace App\Services;

class UtilityService
{
    public function settings()
    {
        // Tu lógica para obtener settings
    }

    public function setEnvironmentValue(array $values)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($values as $envKey => $envValue) {
            $envValue = trim($envValue);
            $pattern = '/^' . preg_quote($envKey, '/') . '=(.*)$/m';
            $replacement = $envKey . '="' . $envValue . '"';
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent, 1);
            } else {
                $envContent .= "\n" . $replacement;
            }
        }
        return file_put_contents($envFile, $envContent) !== false;
    }

    public function getValByName($key)
    {
        $settings = $this->settings();
        return isset($settings[$key]) ? $settings[$key] : '';
    }

    public function languages()
    {
        // Lógica para obtener idiomas
    }

    // Otros métodos...
}
