<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Sensor;
use App\Models\SensorCount;
use App\Models\ProductList;
use Illuminate\Database\QueryException;

/**
 * Clase CalculateOptimalProductionTime
 *
 * Este comando de consola calcula el tiempo óptimo de producción para cada producto
 * basándose en los datos recopilados por los sensores.
 */
class CalculateOptimalProductionTime extends Command
{
    /**
     * La firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'production:calculate-optimal-time';

    /**
     * La descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Calculate the optimal production time for each product based on sensor data';

    /**
     * Crea una nueva instancia del comando.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ejecuta el comando de la consola.
     *
     * Este método es el punto de entrada principal del comando. Se ejecuta en un bucle infinito
     * que calcula el tiempo óptimo de producción cada 10 minutos.
     *
     * @return int
     */
    public function handle()
    {
        // Bucle infinito para ejecutar el comando continuamente.
        while (true) {
            $this->info("Starting the calculation of optimal production times...");

            // Obtener todos los sensores de tipo 0 (sensores de producción) junto con su ProductList relacionado.
            // El uso de 'with(['productList'])' carga la relación 'productList' de forma eager loading,
            // evitando el problema de consultas N+1.
            /** @var \Illuminate\Database\Eloquent\Collection|Sensor[] $sensors */
            $sensors = Sensor::with(['productList'])->where('sensor_type', 0)->get();

            // Obtener los valores mínimo y máximo de tiempo de producción desde las variables de entorno.
            // Si no se encuentran, se usan los valores predeterminados 3 y 10 respectivamente.
            $minTime = (float) env("PRODUCTION_MIN_TIME", 2);
            $maxTime = (float) env("PRODUCTION_MAX_TIME", 10);

            // Iterar sobre cada sensor para procesar su información.
            foreach ($sensors as $sensor) {
                $this->processSensor($sensor, $minTime, $maxTime);
            }

            $this->info("Waiting for 10 minutes before the next run...");
            // Pausar la ejecución durante 10 minutos (600 segundos).
            sleep(600);
        }
        return 0;
    }

    /**
     * Procesa un sensor individual para calcular y actualizar el tiempo óptimo de producción.
     *
     * Este método encapsula la lógica para procesar un sensor, calcular su tiempo óptimo,
     * actualizar la tabla 'product_lists' (si es necesario) y el registro del sensor.
     *
     * @param Sensor $sensor El sensor a procesar.
     * @param float $minTime El tiempo mínimo de producción.
     * @param float $maxTime El tiempo máximo de producción.
     */
    private function processSensor(Sensor $sensor, float $minTime, float $maxTime)
    {
        try {
            // Obtener el nombre del producto (client_id) asociado al sensor a través de la relación productList.
            // Se usa 'optional()' para evitar errores si la relación 'productList' no existe.
            $modelProduct = optional($sensor->productList)->client_id;

            // Si se encuentra un producto asociado al sensor...
            if ($modelProduct) {
                // Calcular el tiempo óptimo de producción para el sensor actual.
                $optimalProductionTime = $this->calculateOptimalTimeForSensor($sensor, $minTime, $maxTime);

                // Actualizar la tabla 'product_lists' con el tiempo óptimo calculado, si es menor al existente.
                $this->updateProductListIfNeeded($modelProduct, $optimalProductionTime);

                // Actualizar el campo 'optimal_production_time' del sensor actual.
                $sensor->optimal_production_time = $optimalProductionTime;
                $sensor->save();

                $this->info("Actualizado en sensors: optimal_production_time = {$optimalProductionTime} para el sensor: {$sensor->name} (Producto: {$modelProduct})");
            } else {
                // Si no se encuentra un producto asociado, manejar el caso especial.
                $this->handleMissingProduct($sensor, $minTime, $maxTime);
            }
        } catch (QueryException $e) {
            // Capturar excepciones específicas de la base de datos.
            $this->error("Database error processing sensor {$sensor->name}: {$e->getMessage()}");
        } catch (\Exception $e) {
            // Capturar cualquier otra excepción.
            $this->error("Error processing sensor {$sensor->name}: {$e->getMessage()}");
        }
    }

    /**
     * Calcula el tiempo óptimo de producción para un sensor específico.
     *
     * Busca en la tabla 'sensor_counts' el registro que cumpla con las condiciones
     * (mismo 'model_product', mismo 'sensor_id', 'value' igual a '1', creado en los últimos 30 días,
     * 'time_11' no nulo y dentro del rango de tiempo mínimo y máximo) y que tenga el menor 'time_11'.
     *
     * @param Sensor $sensor El sensor para el cual se calcula el tiempo óptimo.
     * @param float $minTime El tiempo mínimo de producción.
     * @param float $maxTime El tiempo máximo de producción.
     * @return float El tiempo óptimo de producción calculado.
     */
    private function calculateOptimalTimeForSensor(Sensor $sensor, float $minTime, float $maxTime): float
    {
        // Buscar el registro en 'sensor_counts' que cumple con los criterios especificados.
        // Aquí se asume que 'productName' en Sensor coincide con 'client_id' en ProductList.
        $sensorCount = SensorCount::where('model_product', $sensor->productName)
            ->where('sensor_id', $sensor->id)
            ->where('value', '1') // Considerar usar una constante o configuración para este valor.
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('time_11')
            ->whereBetween('time_11', [$minTime, $maxTime]) // Podría ser redundante si 'time_11' siempre está entre 'minTime' y 'maxTime'.
            ->orderBy('time_11', 'asc') // Ordenar por 'time_11' ascendente para obtener el menor primero.
            ->first();

        // Si se encuentra un registro en 'sensor_counts', usar su 'time_11' como tiempo óptimo.
        // De lo contrario, usar el tiempo óptimo predeterminado.
        $optimalTime = $sensorCount ? $sensorCount->time_11 : $this->getDefaultOptimalTime($minTime, $maxTime);
        $this->info("Sensor count time_11 calculado: {$optimalTime}");

        return $optimalTime;
    }

    /**
     * Actualiza la tabla 'product_lists' con el tiempo óptimo de producción si el nuevo tiempo es menor.
     *
     * Busca un registro en 'product_lists' que coincida con el 'client_id' proporcionado.
     * Si lo encuentra y el nuevo tiempo óptimo es menor que el existente, actualiza el registro.
     *
     * @param string $modelProduct El nombre del producto (que coincide con 'client_id' en 'product_lists').
     * @param float $optimalProductionTime El nuevo tiempo óptimo de producción.
     */
    private function updateProductListIfNeeded(string $modelProduct, float $optimalProductionTime)
    {
        // Buscar el registro en 'product_lists' que coincida con el 'client_id'.
        $productList = ProductList::where('client_id', $modelProduct)->first();

        // Si se encuentra un registro en 'product_lists'...
        if ($productList) {
            // Actualizar el 'optimal_production_time' solo si el nuevo tiempo es menor que el tiempo actual.
            if ($optimalProductionTime < $productList->optimal_production_time) {
                $productList->optimal_production_time = $optimalProductionTime;
                $productList->save();
                $this->info("Actualizado en product_lists: optimal_production_time = {$optimalProductionTime} para el producto: {$modelProduct}");
            }
        } else {
            $this->info("No se encontró registro en product_lists para: {$modelProduct}");
        }
    }

    /**
     * Maneja el caso en que no se encuentra un producto asociado al sensor.
     *
     * Establece el tiempo óptimo de producción del sensor al valor predeterminado.
     *
     * @param Sensor $sensor El sensor para el cual no se encontró un producto.
     * @param float $minTime El tiempo mínimo de producción.
     * @param float $maxTime El tiempo máximo de producción.
     */
    private function handleMissingProduct(Sensor $sensor, float $minTime, float $maxTime)
    {
        $this->info("No se encontró productName para el sensor: {$sensor->name}. Usando valor predeterminado.");
        // Establecer el tiempo óptimo de producción del sensor al valor predeterminado.
        $sensor->optimal_production_time = $this->getDefaultOptimalTime($minTime, $maxTime);
        $sensor->save();
        $this->info("Updated optimal production time for sensor: {$sensor->name} (Producto: NULL)");
    }

    /**
     * Calcula el tiempo óptimo de producción predeterminado.
     *
     * Este método calcula un tiempo óptimo predeterminado basado en el promedio del tiempo mínimo y máximo.
     *
     * @param float $minTime El tiempo mínimo de producción.
     * @param float $maxTime El tiempo máximo de producción.
     * @return float El tiempo óptimo de producción predeterminado.
     */
    private function getDefaultOptimalTime($minTime, $maxTime)
    {
        // Calcular el promedio del tiempo mínimo y máximo.
        return ($minTime + $maxTime) / 2;
    }
}