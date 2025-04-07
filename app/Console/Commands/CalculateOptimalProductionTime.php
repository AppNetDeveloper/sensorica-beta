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
use App\Models\RfidDetail;
use App\Services\OrderTimeService;
use App\Models\OptimalSensorTime;


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
                if (
                    ($sensor->shift_type === 'shift' && $sensor->event === 'start') ||
                    ($sensor->shift_type === 'stop' && $sensor->event === 'end')
                ) {
                    // Ejecutar lógica según sensor_type
                    if ((int)$sensor->sensor_type === 0) {
                        $this->processSensorType0($sensor, $minTime, $maxTime);
                    } else {
                        $this->processSensorOtherTypes($sensor);
                    }
                } else {
                    // Registrar información en log si no se cumplen condiciones específicas
                    $this->info("Sensor name: {$sensor->name}, sensor_type: {$sensor->sensor_type}, shift_type: {$sensor->shift_type}, event_start: {$sensor->event_start}");
                }                
            }

            //obtenemos todos los modbuses de la base de datos
            $modbuses = Modbus::all();

            //creamos un foreach para recorrer todos los modbuses y entro los separamos por model_type 0 1 2 3 
            foreach ($modbuses as $modbus) {
                if (($modbus->shift_type === 'shift' && $modbus->event === 'start') || ($modbus->shift_type === 'stop' && $modbus->event === 'end')) {
                    if ((int)$modbus->model_type === 0) {
                        // Lógica específica para model_type 0
                        $this->processModbusType0($modbus);
                    } else {
                        // Lógica para otros model_type
                        $this->processModbusOtherTypes($modbus);
                    }
                } else {
                    // Registro informativo común si no se cumplen las condiciones
                    $this->info("Modbus name: {$modbus->name}, model_type: {$modbus->model_type}, shift_type: {$modbus->shift_type}, event_start: {$modbus->event_start}");
                }                
            }

            //obtenemos todas los rfid de rfid_details
            $rfid_details = RfidDetail::all();
            foreach ($rfid_details as $rfid_detail) {
               $this->processRfid($rfid_detail);
            }



            $this->info("Waiting for 1 minut before the next run...");
            // Pausar la ejecución durante 1 minutos (60 segundos).
            sleep(60);
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
     */
    private function processSensorType0(Sensor $sensor, float $minTime, float $maxTime)
    {
        if ((int)$sensor->sensor_type > 0 || (int)$sensor->count_order_1 < 1) {
            return;
        }
        // Crear instancia del servicio OrderTimeService
        $orderTimeService = new OrderTimeService();
        // Define el productionLineId que necesitas (ejemplo: 1)
        $productionLineId = $sensor->production_line_id;
        
        try {
            // Llamar al método getTimeOrder y capturar el resultado
            $orderTime = $orderTimeService->getTimeOrder($productionLineId);
            //ahora mismo dentro hay 2 campos de $orderTime {"timeOnSeconds":7882,"timeOnFormatted":"02:11:22"}
            //extraemos en 2 variables
            $orderTimeSeconds = $orderTime['timeOnSeconds'];
            $orderTimeFormatted = $orderTime['timeOnFormatted'];
            $orderTimeSecondsSinDownTime = $orderTimeSeconds - $sensor->downtime_count; // Supongamos que se resta 10 segundos
            
            // Registrar el resultado en el log
            $this->info("Tiempo de orden para production_line_id {$productionLineId}: " . json_encode($orderTime));
        } catch (\Exception $e) {
            $this->error("Error al obtener tiempo de orden: " . $e->getMessage());
        }
            // Obtener el nombre del producto (client_id) asociado al sensor a través de la relación productList.
            // Se usa 'optional()' para evitar errores si la relación 'productList' no existe.
            $modelProduct = optional($sensor->productList)->client_id;
            $modelProductId = optional($sensor->productList)->id;
            $modelProductOptimalProductionTime = optional($sensor->productList)->optimal_production_time ?? 1000.0;

        try {
            // Buscar registro existente
            $optimalSensorTime = OptimalSensorTime::where('sensor_id', $sensor->id)
                    ->where('production_line_id', $sensor->production_line_id)
                    ->where('model_product', $modelProduct)
                    ->first();
            $this->info('SENSOR en Optimal_sensor_time: '. json_encode($optimalSensorTime));
        } catch (\Exception $e) {
            $this->error("Error al buscar registro existente: " . $e->getMessage());
        }

        try {


            // Si se encuentra un producto asociado al sensor...
            if ($modelProduct) {
                if ((int)$sensor->count_order_1 < 20) {
                    $this->info("Vamos por debajo de 20 bolsas");
                    if($optimalSensorTime) {
                        $optimalProductionTime = $optimalSensorTime->optimal_time;
                        $this->info("El tiempo óptimo de producción para el sensor actual es!: " . $optimalProductionTime . " segundos.");
                    }else{
                        $optimalProductionTime = $modelProductOptimalProductionTime;
                        $this->info("El tiempo óptimo de producción para el sensor actual es: " . $optimalProductionTime . " segundos.");
                    }
                }else{
                    // Calcular el tiempo óptimo de producción para el sensor actual.
                    $optimalProductionTimeAll = $this->calculateOptimalTimeForSensor($sensor, $orderTimeSecondsSinDownTime);
                    //redondeamos a dos decimales
                    $this->info("Valor calculado sensor por : ". $sensor->name.": ". $optimalProductionTimeAll );
                    $optimalProductionTime = round($optimalProductionTimeAll, 2);
                    $this->info("Valor sensor". $sensor->name." modificado a 2 digitos valor: ".$optimalProductionTime);
                }
                //$this->info("Tiempo optimo para el sensor actual de conteo: sensor {$sensor->name} ID: {$sensor->id} tiempo optimo : " . $optimalProductionTime . " segundos y tiempo actual :". $optimalSensorTime->optimal_time);
            
                //ahora en tabla optimasl_sensor_times si no existe linea con sensor_id = $sensor->id y production_line_id = $sensor->production_line_id
                // y model_product = $modelProduct lo creamos si existe lo actualizamos si el campo optimal_time es menor al existente
                // y si es menor al existente actualizamos el campo optimal_time en la tabla 'optimal_sensor_times'
                if ($optimalProductionTime < 1) {
                    return;
                }
                
                try {
                    
                    
                    if (!$optimalSensorTime ) {
                        // No existe registro, se crea uno nuevo
                        $optimalSensorTime = new OptimalSensorTime();
                        $optimalSensorTime->sensor_id = $sensor->id;
                        $optimalSensorTime->production_line_id = $sensor->production_line_id;
                        $optimalSensorTime->model_product = $modelProduct;
                        $optimalSensorTime->product_list_id = $modelProductId;
                        $optimalSensorTime->optimal_time = $optimalProductionTime;
                        $optimalSensorTime->sensor_type = $sensor->sensor_type;
                        $optimalSensorTime->save();
                        $this->info("Se creó registro en optimal_sensor_times para el sensor {$sensor->name} ID: {$sensor->id} (Producto: {$modelProduct}) con optimal_time: {$optimalProductionTime}");
                    } else if ($optimalProductionTime < $optimalSensorTime->optimal_time) {
                        // Si ya existe y el nuevo tiempo óptimo es menor que el actual, se actualiza el registro
                        $optimalSensorTime->optimal_time = $optimalProductionTime;
                        $optimalSensorTime->save();
                        $this->info("Se actualizó el registro en optimal_sensor_times para el sensor {$sensor->name} ID: {$sensor->id} (Producto: {$modelProduct}) con nuevo optimal_time: {$optimalProductionTime}");
                    } else if ($optimalSensorTime->updated_at->diffInDays(\Carbon\Carbon::now()) > 6) {
                        // Si ya existe y la última actualización fue hace más de 6 días, se actualiza el registro
                        $optimalSensorTime->optimal_time = $optimalProductionTime;
                        $optimalSensorTime->save();

                        // Actualizar la tabla 'product_lists' con el tiempo óptimo calculado, si es menor al existente.
                        $this->updateProductListIfNeeded($modelProduct, $optimalProductionTime);

                        $this->info("Se actualizó el registro en optimal_sensor_times para el sensor {$sensor->name}ID: {$sensor->id}  (Producto: {$modelProduct}) debido a que han pasado más de 6 días desde la última actualización");
                    } else if ($optimalProductionTime > $optimalSensorTime->optimal_time * (1 + $sensor->min_correction_percentage / 100)) {
                        // Se actualiza el registro ya que la diferencia supera el porcentaje mínimo seleccionado
                        $calcValue= $optimalProductionTime * ($sensor->max_correction_percentage / 100);
                        $this->info("Calculo : {$calcValue},  Datos de calculo : {$sensor->max_correction_percentage}, {$sensor->min_correction_percentage}");
                        $optimalSensorTime->optimal_time = $calcValue;
                        $optimalSensorTime->save();

                        // Actualizar la tabla 'product_lists' con el tiempo óptimo calculado, si es menor al existente.
                       $this->updateProductListIfNeeded($modelProduct, $optimalProductionTime);

                        $this->info("Se actualizó el registro en optimal_sensor_times para el sensor {$sensor->name}ID: {$sensor->id}  (Producto: {$modelProduct}) debido a que hay una diferencia mayor del procentaje selecionado");
                    } else {
                        // Si ya existe, el nuevo tiempo es mayor que el actual y no han pasado 6 días, no se hace nada
                    }
                } catch (\Exception $e) {
                    $this->error("Error al procesar optimal_sensor_times para el sensor {$sensor->name}: " . $e->getMessage());
                }


                // Releer el registro actualizado de product_lists
                $productList = ProductList::where('client_id', $modelProduct)->first();

                // Si el tiempo calculado es mayor que el valor almacenado en product_lists, se asigna este último.
                // Buscar el registro en optimal_sensor_times utilizando sensor_id y product_list_id
                try {
                    $optimalSensorRecord = OptimalSensorTime::where('sensor_id', $sensor->id)
                        ->where('product_list_id', $modelProductId)
                        ->first();

                    if ($optimalSensorRecord) {
                        // Si se encontró el registro, comparamos el tiempo óptimo calculado con el almacenado
                        if ($optimalProductionTime > $optimalSensorRecord->optimal_time && $optimalSensorRecord->optimal_time < 1) {
                            $sensor->optimal_production_time = $optimalSensorRecord->optimal_time;
                        }else {
                            $sensor->optimal_production_time = $optimalProductionTime;
                        }
                        $this->info("Sensor {$sensor->name} actualizado con optimal_production_time: {$sensor->optimal_production_time} basado en optimal_sensor_times.");
                    } else {
                        // Si no se encuentra el registro en optimal_sensor_times, se asigna el tiempo calculado
                        $sensor->optimal_production_time = $optimalProductionTime;
                        $this->info("Sensor {$sensor->name} actualizado con optimal_production_time calculado: {$sensor->optimal_production_time} (sin registro en optimal_sensor_times).");
                    }
                } catch (\Exception $e) {
                    $this->error("Error al actualizar el tiempo óptimo del sensor {$sensor->name} basado en optimal_sensor_times: " . $e->getMessage());
                }
                $sensor->save();

                $this->info("Actualizado en sensors: optimal_production_time = {$optimalProductionTime} para el sensor: {$sensor->name} (Producto: {$modelProduct})");
            } else {
                // Si no se encuentra un producto asociado, manejar el caso especial.
                $this->handleMissingProduct($sensor, $minTime, $maxTime);
            }
        } catch (QueryException $e) {
            // Capturar excepciones específicas de la base de datos.
            $this->error("Database error processing sensor::: {$sensor->name}: {$e->getMessage()}");
        } catch (\Exception $e) {
            // Capturar cualquier otra excepción.
            $this->error("Error processing sensor: {$sensor->name}: {$e->getMessage()}");
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
            return;
        }

        // Validar que count_order_1 no sea cero para evitar división por cero ANTES ERA 3 y funcionaba bien por si falla con 1
        if ($sensor->count_order_1 < 1) {
            $modelProduct = optional($sensor->productList)->client_id;
            if ($modelProduct) {
                $productList = ProductList::where('client_id', $modelProduct)->first();
                if ($productList) {
                    $field = "optimalproductionTime_sensorType_" . $sensor->sensor_type;
                    $defaultValue = $productList->$field;
                    if (is_null($defaultValue) || $defaultValue < 1) {
                        $this->error("El sensor {$sensor->name} tiene count_order_1 menor que 3 y no existe un valor default válido en product_lists para {$field}.");
                    } else {
                        $sensor->optimal_production_time = round($defaultValue, 2);
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

        // Obtener el producto asociado mediante la relación productList Se usa para hacer el listado de optimal_sensor_times
        $modelProduct = optional($sensor->productList)->client_id;
        $modelProductId = optional($sensor->productList)->id;


        if ($modelProduct) {
            try {
                // Buscar registro existente
                $optimalSensorTime = OptimalSensorTime::where('sensor_id', $sensor->id)
                    ->where('production_line_id', $sensor->production_line_id)
                    ->where('model_product', $modelProduct)
                    ->first();
            
                if (!$optimalSensorTime) {
                    // No existe registro, se crea uno nuevo
                    $optimalSensorTime = new OptimalSensorTime();
                    $optimalSensorTime->sensor_id = $sensor->id;
                    $optimalSensorTime->production_line_id = $sensor->production_line_id;
                    $optimalSensorTime->model_product = $modelProduct;
                    $optimalSensorTime->product_list_id = $modelProductId;
                    $optimalSensorTime->optimal_time = $calculatedOptimalTime;
                    $optimalSensorTime->sensor_type = $sensor->sensor_type;
                    $optimalSensorTime->save();
                    $this->info("Se creó registro en optimal_sensor_times para el sensor {$sensor->name} (Producto: {$modelProduct}) con optimal_time: {$calculatedOptimalTime}");
                } else if ($calculatedOptimalTime < $optimalSensorTime->optimal_time) {
                    // Si ya existe y el nuevo tiempo óptimo es menor que el actual, se actualiza el registro
                    $optimalSensorTime->optimal_time = $calculatedOptimalTime;
                    $optimalSensorTime->save();
                    $this->info("Se actualizó el registro en optimal_sensor_times para el sensor {$sensor->name} (Producto: {$modelProduct}) con nuevo optimal_time: {$calculatedOptimalTime}");
                } else if ($optimalSensorTime->updated_at->diffInDays(\Carbon\Carbon::now()) > 6) {
                    // Si ya existe y la última actualización fue hace más de 6 días, se actualiza el registro
                    $optimalSensorTime->optimal_time = $calculatedOptimalTime;
                    $optimalSensorTime->save();
                    $this->info("Se actualizó el registro en optimal_sensor_times para el sensor {$sensor->name} (Producto: {$modelProduct}) debido a que han pasado más de 6 días desde la última actualización");
                }  else if ($calculatedOptimalTime > $optimalSensorTime->optimal_time * (1 + $sensor->min_correction_percentage / 100)) {
                    // Se actualiza el registro ya que la diferencia supera el porcentaje mínimo seleccionado
                    $calcValue= $calculatedOptimalTime * ($sensor->max_correction_percentage / 100);
                    $this->info("Calculo : {$calcValue},  Datos de calculo : {$sensor->max_correction_percentage}, {$sensor->min_correction_percentage}");
                    $optimalSensorTime->optimal_time = $calcValue;
                    $optimalSensorTime->save();

                    $this->info("Se actualizó el registro en optimal_sensor_times para el sensor {$sensor->name}ID: {$sensor->id}  (Producto: {$modelProduct}) debido a que hay una diferencia mayor del procentaje selecionado");
                } else {
                    // Si ya existe, el nuevo tiempo es mayor que el actual y no han pasado 6 días, no se hace nada
                }
            } catch (\Exception $e) {
                $this->error("Error al procesar optimal_sensor_times para el sensor {$sensor->name}: " . $e->getMessage());
            }

            //actualizamos el sensor
            // Si el tiempo calculado es mayor que el valor almacenado en product_lists, se asigna este último.
                // Buscar el registro en optimal_sensor_times utilizando sensor_id y product_list_id
                try {
                    $optimalSensorRecord = OptimalSensorTime::where('sensor_id', $sensor->id)
                        ->where('product_list_id', $modelProductId)
                        ->first();

                    if ($optimalSensorRecord) {
                        // Si se encontró el registro, comparamos el tiempo óptimo calculado con el almacenado
                        if ($calculatedOptimalTime > $optimalSensorRecord->optimal_time) {
                            $sensor->optimal_production_time = $optimalSensorRecord->optimal_time;
                        } else {
                            $sensor->optimal_production_time = $calculatedOptimalTime;
                        }
                        $this->info("Sensor {$sensor->name} actualizado con optimal_production_time: {$sensor->optimal_production_time} basado en optimal_sensor_times.");
                    } else {
                        // Si no se encuentra el registro en optimal_sensor_times, se asigna el tiempo calculado
                        $sensor->optimal_production_time = $calculatedOptimalTime;
                        $this->info("Sensor {$sensor->name} actualizado con optimal_production_time calculado: {$sensor->optimal_production_time} (sin registro en optimal_sensor_times).");
                    }
                } catch (\Exception $e) {
                    $this->error("Error al actualizar el tiempo óptimo del sensor {$sensor->name} basado en optimal_sensor_times: " . $e->getMessage());
                }
                $sensor->save();
    
            // Actualizar product_lists si es necesario
            $productList = ProductList::where('client_id', $modelProduct)->first();
            if($updateOn = true) {
                if ($productList) {
                    // Construir dinámicamente el nombre del campo según el sensor_type
                    $field = "optimalproductionTime_sensorType_" . $sensor->sensor_type;
                    $existingValue = $productList->$field;
    
                    // Si no existe un valor válido en product_lists (null o 0), se actualiza con el valor calculado
                    if (is_null($existingValue) || $existingValue == 0 ) {
                        $productList->$field = $calculatedOptimalTime;
                        $productList->save();
                        $this->info("Sensor '{$sensor->name}' actualizado: Se estableció {$field} = {$calculatedOptimalTime} en product_lists.");
                    } else {
                        // Si ya existe un valor válido en product_lists, se compara:
                        if ($calculatedOptimalTime > 0) {
                            // El valor calculado es menor: se actualizan ambos registros
                            $productList->$field = $calculatedOptimalTime;
                            $productList->save();
                            $this->info("Sensor '{$sensor->name}' actualizado: Se actualizó {$field} a {$calculatedOptimalTime} (valor calculado menor que el existente {$existingValue}).");
                        }
                    }
                }
            }
            
            
        } else {
            // Si no hay producto asociado, se actualiza solo el sensor
            $sensor->optimal_production_time = round($calculatedOptimalTime, 2);
            $sensor->save();
            $this->info("Sensor '{$sensor->name}' actualizado: optimal_production_time = {$calculatedOptimalTime}. No tiene producto asociado.");
        }
    } catch (\Exception $e) {
        $this->error("Error procesando sensor '{$sensor->name}' de tipo {$sensor->sensor_type}: {$e->getMessage()}");
    }
}

    private function processModbusType0($modbusData)
    {

        if($modbusData->model_type > 0) {
            return;
        }

        // Crear instancia del servicio OrderTimeService
        $orderTimeService = new OrderTimeService();
        // Define el productionLineId que necesitas (ejemplo: 1)
        $productionLineId = $modbusData->production_line_id;
        
        try {
            // Llamar al método getTimeOrder y capturar el resultado
            $orderTime = $orderTimeService->getTimeOrder($productionLineId);
            //ahora mismo dentro hay 2 campos de $orderTime {"timeOnSeconds":7882,"timeOnFormatted":"02:11:22"}
            //extraemos en 2 variables
            $orderTimeSeconds = $orderTime['timeOnSeconds'];
            $orderTimeFormatted = $orderTime['timeOnFormatted'];
            $orderTimeSecondsSinDownTime = $orderTimeSeconds - $modbusData->downtime_count; // Supongamos que se resta 10 segundos
            
            // Registrar el resultado en el log
            $this->info("Tiempo de orden para production_line_id {$productionLineId}: " . json_encode($orderTime));
        } catch (\Exception $e) {
            $this->error("Error al obtener tiempo de orden: " . $e->getMessage());
        }

        //si el shift no existe salimos
        if (!$orderTimeSecondsSinDownTime) {
            return;
        }
        //sacamos se modbus tambien el productName  que productList_client_id
        $productListClient = $modbusData->productName;
        //si el product no existe salimos
        if (!$productListClient) {
            return;
        }
         //ahora hacemos una media de tiempo por cada caja pesada
        // Extraer el valor de rec_box (que es un string) y convertirlo a entero.
        $boxes = $modbusData->rec_box;
        $boxCount = (int)$boxes; // Convertimos la cadena a entero

        if ($boxCount < 20) {
            $this->info("No se encontraron cajas registradas en rec_box_shift para el modbus.");
            return;
        }

        $average_time = round($orderTimeSecondsSinDownTime / $boxCount, 2);

        //ahora buscamos en product_list el producnto con $productListClient que es client_id 
        $product = ProductList::where('client_id', $productListClient)->first();
        //si el product no existe salimos
        if (!$product) {
            return;
        }

        try {
            // Obtener el nombre del producto (client_id) asociado al sensor a través de la relación productList.
            // Se usa 'optional()' para evitar errores si la relación 'productList' no existe.
            $modelProduct = $product->client_id;
            $modelProductId = $product->id;

            // Si se encuentra un producto asociado al sensor...
            if ($modelProduct) {

                //redondeamos a dos decimales
                $optimalProductionTime = $average_time;
                $this->info("Tiempo optimo para el sensor actual: " .$modbusData->name . ": " . $optimalProductionTime . " segundos");
            
                //ahora en tabla optimasl_sensor_times si no existe linea con sensor_id = $sensor->id y production_line_id = $sensor->production_line_id
                // y model_product = $modelProduct lo creamos si existe lo actualizamos si el campo optimal_time es menor al existente
                // y si es menor al existente actualizamos el campo optimal_time en la tabla 'optimal_sensor_times'
                try {
                    // Buscar registro existente
                    $optimalSensorTime = OptimalSensorTime::where('modbus_id', $modbusData->id)
                        ->where('production_line_id', $modbusData->production_line_id)
                        ->where('model_product', $modelProduct)
                        ->first();
                
                    if (!$optimalSensorTime) {
                        // No existe registro, se crea uno nuevo
                        $optimalSensorTime = new OptimalSensorTime();
                        $optimalSensorTime->modbus_id = $modbusData->id;
                        $optimalSensorTime->production_line_id = $modbusData->production_line_id;
                        $optimalSensorTime->model_product = $modelProduct;
                        $optimalSensorTime->product_list_id = $modelProductId;
                        $optimalSensorTime->optimal_time = $optimalProductionTime;
                        $optimalSensorTime->sensor_type = $modbusData->model_type;
                        $optimalSensorTime->save();
                        $this->info("Se creó registro en optimal_sensor_times para el sensor {$modbusData->name} (Producto: {$modelProduct}) con optimal_time: {$optimalProductionTime}");
                    } else if ($optimalProductionTime < $optimalSensorTime->optimal_time) {
                        // Si ya existe y el nuevo tiempo óptimo es menor que el actual, se actualiza el registro
                        $optimalSensorTime->optimal_time = $optimalProductionTime;
                        $optimalSensorTime->save();
                        $this->info("Se actualizó el registro en optimal_sensor_times para el sensor {$modbusData->name} (Producto: {$modelProduct}) con nuevo optimal_time: {$optimalProductionTime}");
                    }
                } catch (\Exception $e) {
                    $this->error("Error al procesar optimal_sensor_times para el sensor {$modbusData->name}: " . $e->getMessage());
                }

                // Actualizar la tabla 'product_lists' con el tiempo óptimo calculado, si es menor al existente.
                $product->optimalproductionTime_weight =round($average_time, 2);
                $product->save();



                // Si el tiempo calculado es mayor que el valor almacenado en product_lists, se asigna este último.
                // Buscar el registro en optimal_sensor_times utilizando sensor_id y product_list_id
                try {
                    $optimalSensorRecord = OptimalSensorTime::where('modbus_id', $modbusData->id)
                        ->where('product_list_id', $modelProductId)
                        ->first();

                    if ($optimalSensorRecord) {
                        // Si se encontró el registro, comparamos el tiempo óptimo calculado con el almacenado
                        if ($optimalProductionTime > $optimalSensorRecord->optimal_time) {
                            $modbusData->optimal_production_time = $optimalSensorRecord->optimal_time;
                        } else {
                            $modbusData->optimal_production_time = $optimalProductionTime;
                        }
                        $this->info("Sensor {$modbusData->name} actualizado con optimal_production_time: {$modbusData->optimal_production_time} basado en optimal_sensor_times.");
                    } else {
                        // Si no se encuentra el registro en optimal_sensor_times, se asigna el tiempo calculado
                        $modbusData->optimal_production_time = $optimalProductionTime;
                        $this->info("Sensor {$modbusData->name} actualizado con optimal_production_time calculado: {$modbusData->optimal_production_time} (sin registro en optimal_sensor_times).");
                    }
                } catch (\Exception $e) {
                    $this->error("Error al actualizar el tiempo óptimo del sensor {$modbusData->name} basado en optimal_sensor_times: " . $e->getMessage());
                }
                $modbusData->save();

                $this->info("Actualizado en sensors: optimal_production_time = {$optimalProductionTime} para el sensor: {$modbusData->name} (Producto: {$modelProduct})");
            } else {
                // Si no se encuentra un producto asociado, manejar el caso especial.

            }
        } catch (QueryException $e) {
            // Capturar excepciones específicas de la base de datos.
            $this->error("Database error processing sensor {$modbusData->name}: {$e->getMessage()}");
        } catch (\Exception $e) {
            // Capturar cualquier otra excepción.
            $this->error("Error processing sensor {$modbusData->name}: {$e->getMessage()}");
        }
    }

    private function processModbusOtherTypes($modbusData)
    {
        if($modbusData->model_type < 1) {
            return;
        }

        // Crear instancia del servicio OrderTimeService
        $orderTimeService = new OrderTimeService();
        // Define el productionLineId que necesitas (ejemplo: 1)
        $productionLineId = $modbusData->production_line_id;
        
        try {
            // Llamar al método getTimeOrder y capturar el resultado
            $orderTime = $orderTimeService->getTimeOrder($productionLineId);
            //ahora mismo dentro hay 2 campos de $orderTime {"timeOnSeconds":7882,"timeOnFormatted":"02:11:22"}
            //extraemos en 2 variables
            $orderTimeSeconds = $orderTime['timeOnSeconds'];
            $orderTimeFormatted = $orderTime['timeOnFormatted'];
            $orderTimeSecondsSinDownTime = $orderTimeSeconds - $modbusData->downtime_count; // Supongamos que se resta 10 segundos
            
            // Registrar el resultado en el log
            $this->info("Tiempo de orden para production_line_id {$productionLineId}: " . json_encode($orderTime));
        } catch (\Exception $e) {
            $this->error("Error al obtener tiempo de orden: " . $e->getMessage());
        }

        //si el shift no existe salimos
        if (!$orderTimeSecondsSinDownTime) {
            return;
        }
        //sacamos se modbus tambien el productName  que productList_client_id
        $productListClient = $modbusData->productName;
        //si el product no existe salimos
        if (!$productListClient) {
            return;
        }
         //ahora hacemos una media de tiempo por cada caja pesada
        // Extraer el valor de rec_box (que es un string) y convertirlo a entero.
        $boxes = $modbusData->rec_box;
        $boxCount = (int)$boxes; // Convertimos la cadena a entero

        if ($boxCount < 1) {
            $this->info("No se encontraron cajas registradas en rec_box_shift para el modbus.");
            return;
        }

        $average_time = round($orderTimeSecondsSinDownTime / $boxCount, 2);

        //ahora buscamos en product_list el producnto con $productListClient que es client_id 
        $product = ProductList::where('client_id', $productListClient)->first();
        //si el product no existe salimos
        if (!$product) {
            return;
        }

        try {
            // Obtener el nombre del producto (client_id) asociado al sensor a través de la relación productList.
            // Se usa 'optional()' para evitar errores si la relación 'productList' no existe.
            $modelProduct = $product->client_id;
            $modelProductId = $product->id;

            // Si se encuentra un producto asociado al sensor...
            if ($modelProduct) {

                //redondeamos a dos decimales
                $optimalProductionTime = $average_time;
                $this->info("Tiempo optimo para el sensor actual: " .$modbusData->name . ": " . $optimalProductionTime . " segundos");
            
                //ahora en tabla optimasl_sensor_times si no existe linea con sensor_id = $sensor->id y production_line_id = $sensor->production_line_id
                // y model_product = $modelProduct lo creamos si existe lo actualizamos si el campo optimal_time es menor al existente
                // y si es menor al existente actualizamos el campo optimal_time en la tabla 'optimal_sensor_times'
                try {
                    // Buscar registro existente
                    $optimalSensorTime = OptimalSensorTime::where('modbus_id', $modbusData->id)
                        ->where('production_line_id', $modbusData->production_line_id)
                        ->where('model_product', $modelProduct)
                        ->first();
                
                    if (!$optimalSensorTime) {
                        // No existe registro, se crea uno nuevo
                        $optimalSensorTime = new OptimalSensorTime();
                        $optimalSensorTime->modbus_id = $modbusData->id;
                        $optimalSensorTime->production_line_id = $modbusData->production_line_id;
                        $optimalSensorTime->model_product = $modelProduct;
                        $optimalSensorTime->product_list_id = $modelProductId;
                        $optimalSensorTime->optimal_time = $optimalProductionTime;
                        $optimalSensorTime->sensor_type = $modbusData->model_type;
                        $optimalSensorTime->save();
                        $this->info("Se creó registro en optimal_sensor_times para el sensor {$modbusData->name} (Producto: {$modelProduct}) con optimal_time: {$optimalProductionTime}");
                    } else if ($optimalProductionTime < $optimalSensorTime->optimal_time) {
                        // Si ya existe y el nuevo tiempo óptimo es menor que el actual, se actualiza el registro
                        $optimalSensorTime->optimal_time = $optimalProductionTime;
                        $optimalSensorTime->save();
                        $this->info("Se actualizó el registro en optimal_sensor_times para el sensor {$modbusData->name} (Producto: {$modelProduct}) con nuevo optimal_time: {$optimalProductionTime}");
                    }
                } catch (\Exception $e) {
                    $this->error("Error al procesar optimal_sensor_times para el sensor {$modbusData->name}: " . $e->getMessage());
                }

                // Actualizar la tabla 'product_lists' con el tiempo óptimo calculado, si es menor al existente.
                $product->optimalproductionTime_weight =round($average_time, 2);
                $product->save();



                // Si el tiempo calculado es mayor que el valor almacenado en product_lists, se asigna este último.
                // Buscar el registro en optimal_sensor_times utilizando sensor_id y product_list_id
                try {
                    $optimalSensorRecord = OptimalSensorTime::where('modbus_id', $modbusData->id)
                        ->where('product_list_id', $modelProductId)
                        ->first();

                    if ($optimalSensorRecord) {
                        // Si se encontró el registro, comparamos el tiempo óptimo calculado con el almacenado
                        if ($optimalProductionTime > $optimalSensorRecord->optimal_time) {
                            $modbusData->optimal_production_time = $optimalSensorRecord->optimal_time;
                        } else {
                            $modbusData->optimal_production_time = $optimalProductionTime;
                        }
                        $this->info("Sensor {$modbusData->name} actualizado con optimal_production_time: {$modbusData->optimal_production_time} basado en optimal_sensor_times.");
                    } else {
                        // Si no se encuentra el registro en optimal_sensor_times, se asigna el tiempo calculado
                        $modbusData->optimal_production_time = $optimalProductionTime;
                        $this->info("Sensor {$modbusData->name} actualizado con optimal_production_time calculado: {$modbusData->optimal_production_time} (sin registro en optimal_sensor_times).");
                    }
                } catch (\Exception $e) {
                    $this->error("Error al actualizar el tiempo óptimo del sensor {$modbusData->name} basado en optimal_sensor_times: " . $e->getMessage());
                }
                $modbusData->save();

                $this->info("Actualizado en sensors: optimal_production_time = {$optimalProductionTime} para el sensor: {$modbusData->name} (Producto: {$modelProduct})");
            } else {
                // Si no se encuentra un producto asociado, manejar el caso especial.

            }
        } catch (QueryException $e) {
            // Capturar excepciones específicas de la base de datos.
            $this->error("Database error processing sensor {$modbusData->name}: {$e->getMessage()}");
        } catch (\Exception $e) {
            // Capturar cualquier otra excepción.
            $this->error("Error processing sensor {$modbusData->name}: {$e->getMessage()}");
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
    private function calculateOptimalTimeForSensor(Sensor $sensor, $orderTimeSeconds): float
    {
        //en sensors buscamos  y extraemos count_order_1
        $sensorCountOrder1 = $sensor->count_order_1;
        //ahora usamos el tiempo en segundos recibido y partimos a countorder1
        $optimalTime = $orderTimeSeconds / $sensorCountOrder1;

        return $optimalTime;
    }

    private function processRfid($rfid_detail)
    {

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
                $productList->optimal_production_time = round($optimalProductionTime, 2);
                $productList->save();
                //$this->info("Actualizado en product_lists: optimal_production_time = {$optimalProductionTime} para el producto: {$modelProduct}");
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