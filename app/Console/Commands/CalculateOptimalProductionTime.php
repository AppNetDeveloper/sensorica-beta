<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Sensor;
use App\Models\SensorCount;
use App\Models\ProductList;
use Illuminate\Database\QueryException;
//anadimos modbuses
use App\Models\Modbus;
use App\Models\ShiftHistory;

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

            // Obtener todos los sensores junto con su ProductList relacionado.
            // El uso de 'with(['productList'])' carga la relación 'productList' de forma eager loading,
            // evitando el problema de consultas N+1.
            /** @var \Illuminate\Database\Eloquent\Collection|Sensor[] $sensors */
            $sensors = Sensor::with(['productList'])->get();

            // Obtener los valores mínimo y máximo de tiempo de producción desde las variables de entorno.
            // Si no se encuentran, se usan los valores predeterminados 3 y 10 respectivamente.
            $minTime = (float) env("PRODUCTION_MIN_TIME", 2);
            $maxTime = (float) env("PRODUCTION_MAX_TIME", 10);

            // Iterar sobre cada sensor para procesar su información.
            foreach ($sensors as $sensor) {
                if ((int)$sensor->sensor_type === 0) {
                    //if sensor es shift_type no es shift y event start ponemos $this info
                    if($sensor->shift_type != 'shift' && $sensor->event != "start"){
                        $this->info("Sensor name: {$sensor->name} sensor_type: {$sensor->sensor_type} shift_type: {$sensor->shift_type} event_start: {$sensor->event_start}");
                    }else{
                        $this->processSensorType0($sensor, $minTime, $maxTime);
                    }
                    
                } else {
                    // Aquí se implementará la lógica específica para sensor_type 1, 2 y 3
                    $this->processSensorOtherTypes($sensor);
                }
            }

            //obtenemos todos los modbuses de la base de datos
            $modbuses = Modbus::all();

            //creamos un foreach para recorrer todos los modbuses y entro los separamos por model_type 0 1 2 3 
            foreach ($modbuses as $modbus) {
                if ((int)$modbus->model_type === 0) {
                    if($sensor->shift_type != 'shift' && $sensor->event != "start"){
                        $this->info("Modbus name: {$modbus->name} model_type: {$modbus->model_type} shift_type: {$modbus->shift_type} event_start: {$modbus->event_start}");
                    }else{
                        $this->processModbusType0($modbus);
                    }
                    // Aquí se implementará la lógica específica para model_type 0
                   
                } else {
                    // Aquí se implementará la lógica específica para model_type 1, 2 y 3
                    $this->processModbusOtherTypes($modbus);
                }
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
    private function processSensorType0(Sensor $sensor, float $minTime, float $maxTime)
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

                // Releer el registro actualizado de product_lists
                $productList = ProductList::where('client_id', $modelProduct)->first();

                // Si el tiempo calculado es mayor que el valor almacenado en product_lists, se asigna este último.
                if ($optimalProductionTime > $productList->optimal_production_time) {
                    $sensor->optimal_production_time = $productList->optimal_production_time;
                } else {
                    $sensor->optimal_production_time = $optimalProductionTime;
                }
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
 * Procesa sensores de tipos distintos a 0, calculando el tiempo óptimo de producción
 * a partir de la división: downtime_count / count_order_1.
 *
 * Se actualiza el campo optimal_production_time en la tabla sensors y, según el tipo de sensor,
 * se actualiza el campo correspondiente en product_lists, respetando que si el valor en product_lists
 * es menor que el calculado, se mantiene y se usa ese valor para el sensor.
 *
 * @param Sensor $sensor El sensor a procesar (debe ser de tipo distinto a 0).
 * @param float $minTime Valor mínimo de producción (no se utiliza en este método).
 * @param float $maxTime Valor máximo de producción (no se utiliza en este método).
 */
private function processSensorOtherTypes(Sensor $sensor)
{
    try {
        // Validar que el sensor no sea de tipo 0 (este método es para otros tipos)
        if ((int)$sensor->sensor_type === 0) {
            $this->info("El sensor {$sensor->name} es de tipo 0 y debe procesarse con processSensorType0.");
            return;
        }

        // Validar que count_order_1 no sea cero para evitar división por cero
        if ($sensor->count_order_1 < 3) {
            $modelProduct = optional($sensor->productList)->client_id;
            if ($modelProduct) {
                $productList = ProductList::where('client_id', $modelProduct)->first();
                if ($productList) {
                    $field = "optimalproductionTime_sensorType_" . $sensor->sensor_type;
                    $defaultValue = $productList->$field;
                    if (is_null($defaultValue) || $defaultValue < 1) {
                        $this->error("El sensor {$sensor->name} tiene count_order_1 menor que 3 y no existe un valor default válido en product_lists para {$field}.");
                    } else {
                        $sensor->optimal_production_time = $defaultValue;
                        $sensor->save();
                        $this->info("Sensor '{$sensor->name}' actualizado: count_order_1 menor que 3, se asigna valor default {$defaultValue} desde product_lists campo {$field}.");
                    }
                } else {
                    $this->error("El sensor {$sensor->name} tiene count_order_1 menor que 3 y no se encontró registro en product_lists para: {$modelProduct}.");
                }
            } else {
                $this->error("El sensor {$sensor->name} tiene count_order_1 menor que 3 y no tiene producto asociado.");
            }
            return;
        }

        // Calcular el tiempo óptimo usando downtime_count / count_order_1
        $calculatedOptimalTime = $sensor->downtime_count / $sensor->count_order_1;

        // Si el resultado es 0, se considera inválido y se reemplaza por el valor por defecto.
        // Para ello, usamos los valores de entorno (o los predeterminados) para min y max.
        if ($calculatedOptimalTime < 1) {
            $this->error("El sensor {$sensor->name} no tiene un resultado valido");
            return;
            
        }

        // Obtener el producto asociado mediante la relación productList
        $modelProduct = optional($sensor->productList)->client_id;
        if ($modelProduct) {
            $productList = ProductList::where('client_id', $modelProduct)->first();
            if ($productList) {
                // Construir dinámicamente el nombre del campo según el sensor_type
                $field = "optimalproductionTime_sensorType_" . $sensor->sensor_type;
                $existingValue = $productList->$field;

                // Si no existe un valor válido en product_lists (null o 0), se actualiza con el valor calculado
                if (is_null($existingValue) || $existingValue == 0 || $calculatedOptimalTime < $existingValue) {
                    $productList->$field = $calculatedOptimalTime;
                    $productList->save();
                    $this->info("Sensor '{$sensor->name}' actualizado: Se estableció {$field} = {$calculatedOptimalTime} en product_lists.");
                    $sensor->optimal_production_time = $calculatedOptimalTime;
                    $sensor->save();
                    $this->info("Sensor '{$sensor->name}' actualizado: Se estableció optimal_production_time = {$calculatedOptimalTime} y se actualizó {$field} en product_lists (antes era nulo o 0).");
                } else {
                    // Si ya existe un valor válido en product_lists, se compara:
                    if ($calculatedOptimalTime < $existingValue && $calculatedOptimalTime > 0) {
                        // El valor calculado es menor: se actualizan ambos registros
                        $productList->$field = $calculatedOptimalTime;
                        $productList->save();
                        $sensor->optimal_production_time = $calculatedOptimalTime;
                        $sensor->save();
                        $this->info("Sensor '{$sensor->name}' actualizado: Se actualizó {$field} a {$calculatedOptimalTime} (valor calculado menor que el existente {$existingValue}).");
                    } else {
                        // Si el valor calculado no es menor, se asigna al sensor el valor existente de product_lists
                        $sensor->optimal_production_time = $existingValue;
                        $sensor->save();
                        $this->info("Sensor '{$sensor->name}' actualizado: optimal_production_time se mantiene en {$existingValue} (valor existente en product_lists para {$field}).");
                    }
                }
            } else {
                // Si no se encuentra el registro en product_lists, simplemente se actualiza el sensor
                $sensor->optimal_production_time = $calculatedOptimalTime;
                $sensor->save();
                $this->info("Sensor '{$sensor->name}' actualizado: optimal_production_time = {$calculatedOptimalTime}. No se encontró registro en product_lists para: {$modelProduct}");
            }
        } else {
            // Si no hay producto asociado, se actualiza solo el sensor
            $sensor->optimal_production_time = $calculatedOptimalTime;
            $sensor->save();
            $this->info("Sensor '{$sensor->name}' actualizado: optimal_production_time = {$calculatedOptimalTime}. No tiene producto asociado.");
        }
    } catch (\Exception $e) {
        $this->error("Error procesando sensor '{$sensor->name}' de tipo {$sensor->sensor_type}: {$e->getMessage()}");
    }
}

    private function processModbusType0($modbusData)
    {
        // Implementación del procesamiento específico para datos de tipo 0

        //ahor extraemos  el production_line_id
        $production_line_id = $modbusData->production_line_id;
        //ahora por production_line_id sacamos  de shift_history  por production_line_id y sacamos la ultima lunea con where type= shift y action = start 
        // y sacamos la created_at
        $shift_history = ShiftHistory::where('production_line_id', $production_line_id)
            ->where('type', 'shift')
            ->where('action', 'start')
            ->orderBy('created_at', 'desc')
            ->first();

        //si el shift no existe salimos
        if (!$shift_history) {
            return;
        }
        //sacamos se modbus tambien el productName  que productList_client_id
        $productListClient = $modbusData->productName;
        //si el product no existe salimos
        if (!$productListClient) {
            return;
        }
        //hacemos calculamos en segundos el tiempo desde shift_history hasta ahora
        $time = Carbon::parse($shift_history->created_at)->diffInSeconds(Carbon::now());
        //ahora hacemos una media de tiempo por cada caja pesada
        // Extraer el valor de rec_box_shift (que es un string) y convertirlo a entero.
        $boxes = $modbusData->rec_box_shift;
        $boxCount = (int)$boxes; // Convertimos la cadena a entero

        if ($boxCount <= 0) {
            $this->info("No se encontraron cajas registradas en rec_box_shift para el modbus.");
            return;
        }

        $average_time = round($time / $boxCount, 2);

        //ahora buscamos en product_list el producnto con $productListClient que es client_id 
        $product = ProductList::where('client_id', $productListClient)->first();
        //si el product no existe salimos
        if (!$product) {
            return;
        }
        //ahora si el $average_time es menor que lo que esta en product->optimalproductionTime_weight lo actualizamos si no lo dejamos como es
        //pero si el tiempo de inicio de turno que sea mayor a 300 segundos
        if ($average_time < $product->optimalproductionTime_weight && $time > 3600) {
            $product->optimalproductionTime_weight = $average_time;
            $product->save();
            $this->info("Actualizado en product_lists: optimalproductionTime_weight = {$average_time} para el producto: {$productListClient}");
        }else {
            $this->info("No se actualizó en product_lists: optimalproductionTime_weight = {$average_time} para el producto: {$productListClient}");
        }
    }

    private function processModbusOtherTypes($modbusData)
    {
        // Implementación del procesamiento específico para datos de otros tipos
        //ahor extraemos  el production_line_id
        $production_line_id = $modbusData->production_line_id;
        //ahora por production_line_id sacamos  de shift_history  por production_line_id y sacamos la ultima lunea con where type= shift y action = start 
        // y sacamos la created_at
        $shift_history = ShiftHistory::where('production_line_id', $production_line_id)
            ->where('type', 'shift')
            ->where('action', 'start')
            ->orderBy('created_at', 'desc')
            ->first();

        //si el shift no existe salimos
        if (!$shift_history) {
            return;
        }
        //sacamos se modbus tambien el productName  que productList_client_id
        $productListClient = $modbusData->productName;
        //si el product no existe salimos
        if (!$productListClient) {
            return;
        }
        //hacemos calculamos en segundos el tiempo desde shift_history hasta ahora
        $time = Carbon::parse($shift_history->created_at)->diffInSeconds(Carbon::now());
        // Extraer el valor de rec_box_shift (que es un string) y convertirlo a entero.
        $boxes = $modbusData->rec_box_shift;
        $boxCount = (int)$boxes; // Convertimos la cadena a entero

        if ($boxCount <= 0) {
            $this->info("No se encontraron cajas registradas en rec_box_shift para el modbus.");
            return;
        }

        $average_time = round($time / $boxCount, 2);

        //ahora buscamos en product_list el producnto con $productListClient que es client_id 
        $product = ProductList::where('client_id', $productListClient)->first();
        //ahora si el $average_time es menor que lo que esta en product->optimalproductionTime_weight lo actualizamos si no lo dejamos como es
        //pero si el tiempo de inicio de turno que sea mayor a 300 segundos
        //si el product no existe salimos
        if (!$product) {
            return;
        }
        $modelType="optimalproductionTime_weight_".$modbusData->model_type;
        if ($average_time < $product->optimalproductionTime_weight && $time > 3600) {
            $product->$modelType  = $average_time;
            $product->save();
            $this->info("Actualizado en product_lists: optimalproductionTime_weight = {$average_time} para el producto: {$productListClient}");
        }else {
            $this->info("No se actualizó en product_lists: optimalproductionTime_weight = {$average_time} para el producto: {$productListClient}");
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
        $this->info("Sensor name: {$sensor->name} count time_11 calculado: {$optimalTime}");

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