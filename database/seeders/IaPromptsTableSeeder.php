<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\IaPrompt; // Asegúrate de que este modelo exista si decides usar la versión con Eloquent
use Illuminate\Support\Carbon; // Para now()

class IaPromptsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Contenido del prompt individual
        $individualPromptContent = <<<PROMPT
Por favor, realiza un análisis DETALLADO y PROFUNDO del rendimiento del siguiente trabajador. Tu respuesta debe estar en español.

Datos de todos los trabajadores (Contexto Global del Periodo Seleccionado):
{{overallSummary}}

---
Análisis específico para este trabajador:
Nombre del Trabajador: {{workerName}}
ID Cliente: {{workerClientId}}

Resumen de Actividad de ESTE TRABAJADOR en el Periodo Seleccionado:
- Número total de puestos trabajados con producción: {{activePostsCount}}
- Cajas totales producidas por este trabajador: {{workerTotalCajas}}
- Eficiencia promedio (Cajas/Hora) de este trabajador (calculada sobre duración de puestos): {{workerAvgCajasHora}}

Detalles de sus puestos con producción (cantidad > 0):
{{postsDetails}}

Instrucciones para tu análisis (enfócate en el trabajador individual, usando el contexto global para comparaciones):
1.  **Resumen General:** Comienza con un párrafo breve resumiendo el desempeño general del trabajador.
2.  **Puntos Fuertes:** Identifica y describe claramente las fortalezas del trabajador (ej. alta productividad en ciertos puestos, consistencia, versatilidad si trabajó en muchos puestos, buena eficiencia promedio).
3.  **Áreas de Mejora:** Basado en los datos (ej. baja productividad en algunos puestos, inactividad, baja eficiencia), sugiere áreas específicas donde el trabajador podría mejorar. Sé constructivo.
4.  **Patrones Notables:**
    * ¿Hay puestos donde es particularmente eficiente o ineficiente?
    * ¿Muestra consistencia en su producción o hay mucha variabilidad?
    * Si trabajó en múltiples puestos, ¿cómo se compara su rendimiento entre ellos?
    * ¿Hay alguna relación entre el tipo de confección y su rendimiento?
5.  **Sugerencias (opcional, si aplica):** Si los datos lo permiten, ofrece alguna sugerencia breve y accionable.
6.  **Comparación con el Equipo:** Compara brevemente el rendimiento del trabajador (ej. eficiencia, volumen en puestos clave) con los promedios o rangos observados en el contexto global proporcionado anteriormente. ¿Está por encima, por debajo o en línea con el rendimiento general del equipo en aspectos relevantes?

Formato: Usa párrafos para el resumen y listas con viñetas para los puntos fuertes, áreas de mejora y patrones.
Evita frases como "Basándome en estos datos" o "Aquí está el análisis". Ve directo al grano.
Si los datos son insuficientes para un análisis profundo en algún área, indícalo brevemente.
PROMPT;

        // Contenido del prompt global
        $globalPromptContent = <<<PROMPT
Por favor, realiza un ANÁLISIS GLOBAL DETALLADO del rendimiento para el conjunto de trabajadores, basado en los siguientes datos resumidos del periodo. Tu respuesta debe estar en español.

{{overallSummary}}

Instrucciones para tu análisis:
1.  **Resumen Ejecutivo:** Comienza con un párrafo que resuma la productividad y eficiencia general del equipo.
2.  **Observaciones Clave de Productividad:**
    * Comenta sobre el volumen total de producción y la eficiencia promedio (Cajas/Hora Global). ¿Es buena, regular, necesita mejorar?
3.  **Análisis de Puestos de Trabajo:**
    * ¿Qué puestos son los más productivos en términos de volumen? ¿A qué podría deberse?
    * ¿Hay puestos con notablemente baja producción? ¿Posibles causas?
    * ¿La carga de trabajo parece estar bien distribuida entre los puestos o hay concentraciones?
4.  **Distribución del Rendimiento entre Trabajadores (basado en el resumen de top/low performers):**
    * ¿Se observa una gran disparidad en la producción individual o el rendimiento es relativamente homogéneo?
    * ¿Hay indicios claros de un grupo de alto rendimiento y otro de bajo rendimiento? ¿Qué implicaciones podría tener esto?
5.  **Posibles Cuellos de Botella o Ineficiencias:** Basado en los datos agregados (puestos, promedios), ¿puedes inferir posibles cuellos de botella o áreas generales de ineficiencia en el proceso?
6.  **Sugerencias Estratégicas Generales:** Ofrece 2-3 sugerencias generales y accionables para optimizar el rendimiento del equipo, mejorar la distribución del trabajo, o potenciar a los trabajadores.

Formato: Usa párrafos para el resumen y listas con viñetas para las observaciones y sugerencias.
Evita frases como "Basándome en estos datos". Ve directo al análisis.
PROMPT;

        // Definimos los prompts que queremos asegurar en la base de datos
        $promptsData = [
            [
                'key' => 'individual_worker_analysis_v3',
                'name' => 'Análisis Individual de Trabajador con Contexto Global',
                'content' => $individualPromptContent,
                'model_name' => 'gemma3:4b-it-qat',
                'is_active' => true,
            ],
            [
                'key' => 'overall_team_analysis_v3',
                'name' => 'Análisis Global de Equipo',
                'content' => $globalPromptContent,
                'model_name' => 'gemma3:4b-it-qat',
                'is_active' => true,
            ],
        ];

        // Iteramos sobre cada prompt para verificar e insertar si no existe
        foreach ($promptsData as $promptData) {
            // Verificamos si ya existe un prompt con esa 'key' usando el Query Builder
            $exists = DB::table('ia_prompts')->where('key', $promptData['key'])->exists();

            if (!$exists) {
                // Si no existe, lo insertamos
                DB::table('ia_prompts')->insert([
                    'key' => $promptData['key'],
                    'name' => $promptData['name'],
                    'content' => $promptData['content'],
                    'model_name' => $promptData['model_name'],
                    'is_active' => $promptData['is_active'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                $this->command->info("Prompt '{$promptData['key']}' creado.");
            } else {
                $this->command->info("Prompt '{$promptData['key']}' ya existe. No se ha realizado ninguna acción.");
            }
        }

        // Alternativa usando el modelo Eloquent IaPrompt (si está configurado y prefieres este enfoque)
        /*
        foreach ($promptsData as $promptData) {
            IaPrompt::firstOrCreate(
                ['key' => $promptData['key']], // Atributos para buscar
                [ // Atributos para crear si no se encuentra (excluyendo 'key' ya que está en el primer array)
                    'name' => $promptData['name'],
                    'content' => $promptData['content'],
                    'model_name' => $promptData['model_name'],
                    'is_active' => $promptData['is_active'],
                    // 'created_at' y 'updated_at' serán manejados automáticamente por Eloquent si los timestamps están habilitados en el modelo
                ]
            );
            // Para mostrar un mensaje similar al de arriba, necesitarías comprobar si el modelo fue creado recientemente:
            // $prompt = IaPrompt::firstOrCreate([...]);
            // if ($prompt->wasRecentlyCreated) {
            //     $this->command->info("Prompt '{$promptData['key']}' creado con Eloquent.");
            // } else {
            //     $this->command->info("Prompt '{$promptData['key']}' ya existía (Eloquent). No se ha realizado ninguna acción.");
            // }
        }
        */
    }
}
