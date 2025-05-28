<?php
/**
 * proxy.php – Pasarela mínima hacia Ollama /api/generate
 * - Reenvía CUALQUIER petición (normalmente POST) a:
 *     http://185.26.5.37:11434/api/generate
 * - Devuelve la respuesta tal cual y añade CORS "Access-Control-Allow-Origin: *".
 *
 * ⚠️  Simplísimo: si mañana quisieras llamar a /api/tags u otro endpoint,
 *     duplica la lógica o añade un pequeño switch.
 */

$OLLAMA_URL = 'http://185.26.5.37:11434/api/generate';

/* ───── Recogemos cuerpo y cabeceras del cliente ───── */
$body          = file_get_contents('php://input');
$clientHeaders = getallheaders();
$forwardHeaders = [];

// reenviamos Content-Type y Authorization si llegan
foreach ($clientHeaders as $k => $v) {
    $kLow = strtolower($k);
    if (in_array($kLow, ['content-type', 'authorization'])) {
        $forwardHeaders[] = "$k: $v";
    }
}

/* ───── cURL hacia Ollama ───── */
$ch = curl_init($OLLAMA_URL);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => $_SERVER['REQUEST_METHOD'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => $forwardHeaders,
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err       = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    header('Content-Type: text/plain');
    echo "Proxy error: $err";
    exit;
}

/* ───── Cabeceras para el navegador ───── */
header('Access-Control-Allow-Origin: *');           // cambia * por tu dominio si quieres
header('Content-Type: application/json');           // Ollama siempre responde JSON
http_response_code($httpCode);

echo $response;
