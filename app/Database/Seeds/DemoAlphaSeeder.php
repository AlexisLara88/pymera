<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use RuntimeException;
use Throwable;

/**
 * Creates a deterministic and repairable journey for controlled demonstrations.
 *
 * It intentionally excludes users, credentials, memberships, audit history and
 * modules whose data model has not been implemented yet.
 */
class DemoAlphaSeeder extends Seeder
{
    private string $now;

    public function run(): void
    {
        $this->now = Time::now('UTC')->toDateTimeString();
        $this->db->transException(true);

        try {
            if (! $this->db->transBegin()) {
                throw new RuntimeException('No fue posible iniciar la carga demostrativa.');
            }

            $businessId = $this->seedBusiness();
            $this->seedProfile($businessId);

            foreach ($this->objectiveDataset() as $objective) {
                $activities = $objective['activities'];
                unset($objective['activities']);

                $objectiveId = $this->upsert(
                    'objectives',
                    [
                        'business_id' => $businessId,
                        'title'       => $objective['title'],
                    ],
                    $objective,
                    revivesSoftDeletedRows: true,
                );

                foreach ($activities as $activity) {
                    $this->upsert(
                        'activities',
                        [
                            'objective_id' => $objectiveId,
                            'title'        => $activity['title'],
                        ],
                        $activity,
                        revivesSoftDeletedRows: true,
                    );
                }
            }

            foreach ($this->financialDataset() as $entry) {
                $this->upsert(
                    'financial_daily_entries',
                    [
                        'business_id'    => $businessId,
                        'operation_date' => $entry['operation_date'],
                    ],
                    $entry,
                );
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('No fue posible confirmar la carga demostrativa.');
            }
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    private function seedBusiness(): int
    {
        return $this->upsert(
            'businesses',
            ['name' => 'Dulce Barrio'],
            [
                'name'          => 'Dulce Barrio',
                'currency_code' => 'USD',
                'timezone'      => 'America/Guayaquil',
                'status'        => 'active',
            ],
            revivesSoftDeletedRows: true,
        );
    }

    private function seedProfile(int $businessId): void
    {
        $this->upsert(
            'business_profiles',
            ['business_id' => $businessId],
            [
                'business_id' => $businessId,
                'what_it_does' => 'Pastelería artesanal de Quito que diseña y produce pasteles personalizados, postres y mesas dulces para celebraciones familiares y pequeños eventos corporativos.',
                'customers_served' => 'Familias, parejas, organizadores de celebraciones y pequeños negocios que valoran una atención cercana, personalización y cumplimiento en la entrega.',
                'products_offered' => 'Pasteles personalizados, pasteles de boda e infantiles, cajas de postres, mesas dulces y pedidos corporativos para celebraciones.',
                'objectives_summary' => 'Reducir reclamos, ordenar la confirmación de pedidos, mejorar el seguimiento comercial y sostener un resultado operativo saludable.',
                'differentiator' => 'Cada pedido se diseña con acompañamiento personalizado y una validación previa de sabor, tamaño, diseño, presupuesto y condiciones de entrega.',
                'differentiation_delivery' => 'El negocio realiza una entrevista breve, documenta lo acordado, comparte una referencia visual y ofrece muestras de sabor en pedidos especiales.',
                'customer_outcome' => 'El cliente recibe un producto alineado con su celebración, conoce qué recibirá y reduce la incertidumbre sobre diseño, sabor, tamaño y entrega.',
                'purchase_reason' => 'Los clientes eligen Dulce Barrio por la personalización, la comunicación cercana, las recomendaciones y la confianza generada durante el pedido.',
                'acquisition_channels' => 'Instagram, recomendaciones de clientes, WhatsApp y búsquedas locales.',
            ],
            revivesSoftDeletedRows: true,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function objectiveDataset(): array
    {
        return [
            [
                'title' => 'Reducir los reclamos por entregas de pasteles',
                'description' => 'Reducir durante agosto los reclamos relacionados con diferencias de diseño, sabor o condiciones de entrega mediante una confirmación más clara del pedido.',
                'category' => 'improvement',
                'status' => 'active',
                'start_date' => '2026-08-01',
                'target_date' => '2026-08-31',
                'completed_at' => null,
                'activities' => [
                    [
                        'title' => 'Crear un procedimiento de aceptación',
                        'description' => 'Confirmar por escrito diseño, sabor, tamaño, precio, anticipo y fecha antes de iniciar la producción.',
                        'status' => 'in_progress',
                        'is_urgent' => 1,
                        'is_important' => 1,
                        'due_date' => '2026-08-07',
                        'completed_at' => null,
                    ],
                    [
                        'title' => 'Preparar una muestra de sabor',
                        'description' => 'Definir una prueba simple para pedidos de boda, corporativos o de mayor valor.',
                        'status' => 'pending',
                        'is_urgent' => 0,
                        'is_important' => 1,
                        'due_date' => '2026-08-15',
                        'completed_at' => null,
                    ],
                    [
                        'title' => 'Delegar la actualización de condiciones de entrega',
                        'description' => 'Solicitar a un colaborador que unifique las condiciones utilizadas en presupuestos y confirmaciones.',
                        'status' => 'pending',
                        'is_urgent' => 1,
                        'is_important' => 0,
                        'due_date' => '2026-08-08',
                        'completed_at' => null,
                    ],
                    [
                        'title' => 'Eliminar formatos duplicados de pedidos',
                        'description' => 'Retirar plantillas en papel y mensajes antiguos que contienen condiciones diferentes.',
                        'status' => 'cancelled',
                        'is_urgent' => 0,
                        'is_important' => 0,
                        'due_date' => null,
                        'completed_at' => null,
                    ],
                    [
                        'title' => 'Registrar la causa de cada reclamo',
                        'description' => 'Clasificar cada reclamo por diseño, sabor, tamaño, puntualidad o comunicación para detectar patrones.',
                        'status' => 'in_progress',
                        'is_urgent' => 0,
                        'is_important' => 1,
                        'due_date' => '2026-08-31',
                        'completed_at' => null,
                    ],
                ],
            ],
            [
                'title' => 'Aumentar los pedidos confirmados desde canales digitales',
                'description' => 'Mejorar durante agosto la respuesta y el seguimiento de consultas que llegan por Instagram, WhatsApp y recomendaciones.',
                'category' => 'commercial',
                'status' => 'active',
                'start_date' => '2026-08-01',
                'target_date' => '2026-09-15',
                'completed_at' => null,
                'activities' => [
                    [
                        'title' => 'Publicar el catálogo de celebraciones de agosto',
                        'description' => 'Preparar una selección breve de pasteles y mesas dulces con precios orientativos y llamados a reservar.',
                        'status' => 'in_progress',
                        'is_urgent' => 1,
                        'is_important' => 1,
                        'due_date' => '2026-08-10',
                        'completed_at' => null,
                    ],
                    [
                        'title' => 'Contactar a cinco clientes anteriores',
                        'description' => 'Solicitar recomendaciones y recordar opciones para próximas celebraciones sin enviar mensajes masivos.',
                        'status' => 'pending',
                        'is_urgent' => 0,
                        'is_important' => 1,
                        'due_date' => '2026-08-14',
                        'completed_at' => null,
                    ],
                    [
                        'title' => 'Delegar las respuestas iniciales de Instagram',
                        'description' => 'Usar una guía breve para responder consultas frecuentes y escalar los pedidos que requieren cotización.',
                        'status' => 'pending',
                        'is_urgent' => 1,
                        'is_important' => 0,
                        'due_date' => '2026-08-09',
                        'completed_at' => null,
                    ],
                    [
                        'title' => 'Registrar el canal de origen de cada consulta',
                        'description' => 'Anotar si la consulta llegó por Instagram, WhatsApp, recomendación o búsqueda local.',
                        'status' => 'pending',
                        'is_urgent' => 0,
                        'is_important' => 1,
                        'due_date' => '2026-08-20',
                        'completed_at' => null,
                    ],
                ],
            ],
            [
                'title' => 'Desempeño del negocio',
                'description' => 'Registrar los cierres diarios de agosto para conocer ventas, costo de ventas, utilidad bruta, gastos operativos, gastos administrativos y EBITDA.',
                'category' => 'financial',
                'status' => 'active',
                'start_date' => '2026-08-01',
                'target_date' => '2026-08-31',
                'completed_at' => null,
                'activities' => [
                    [
                        'title' => 'Registrar el cierre de caja al final del día',
                        'description' => 'Consolidar ingresos y gastos del día utilizando los comprobantes disponibles.',
                        'status' => 'in_progress',
                        'is_urgent' => 1,
                        'is_important' => 1,
                        'due_date' => '2026-08-06',
                        'completed_at' => null,
                    ],
                    [
                        'title' => 'Revisar los gastos fijos del mes',
                        'description' => 'Comprobar alquiler, servicios y compromisos recurrentes incluidos en el total agregado.',
                        'status' => 'pending',
                        'is_urgent' => 0,
                        'is_important' => 1,
                        'due_date' => '2026-08-20',
                        'completed_at' => null,
                    ],
                    [
                        'title' => 'Delegar la organización de comprobantes',
                        'description' => 'Separar y ordenar comprobantes por fecha para facilitar el registro diario.',
                        'status' => 'pending',
                        'is_urgent' => 1,
                        'is_important' => 0,
                        'due_date' => '2026-08-07',
                        'completed_at' => null,
                    ],
                    [
                        'title' => 'Eliminar fotografías duplicadas de comprobantes',
                        'description' => 'Descartar copias idénticas una vez comprobado que el movimiento fue registrado.',
                        'status' => 'cancelled',
                        'is_urgent' => 0,
                        'is_important' => 0,
                        'due_date' => null,
                        'completed_at' => null,
                    ],
                ],
            ],
            [
                'title' => 'Estandarizar la preparación de entregas',
                'description' => 'Crear y probar una lista breve de control para verificar producto, empaque, horario y condiciones antes de cada entrega.',
                'category' => 'operational',
                'status' => 'completed',
                'start_date' => '2026-07-15',
                'target_date' => '2026-07-31',
                'completed_at' => '2026-07-30 18:00:00',
                'activities' => [
                    [
                        'title' => 'Diseñar la lista de control de entrega',
                        'description' => 'Incluir nombre del cliente, producto, dedicatoria, accesorios, saldo y horario.',
                        'status' => 'completed',
                        'is_urgent' => 1,
                        'is_important' => 1,
                        'due_date' => '2026-07-20',
                        'completed_at' => '2026-07-19 17:30:00',
                    ],
                    [
                        'title' => 'Probar la lista en cinco pedidos',
                        'description' => 'Aplicar la lista y registrar cualquier elemento faltante antes de adoptarla.',
                        'status' => 'completed',
                        'is_urgent' => 0,
                        'is_important' => 1,
                        'due_date' => '2026-07-28',
                        'completed_at' => '2026-07-27 20:00:00',
                    ],
                    [
                        'title' => 'Compartir la lista con los colaboradores',
                        'description' => 'Explicar cuándo se utiliza y quién confirma cada entrega.',
                        'status' => 'completed',
                        'is_urgent' => 1,
                        'is_important' => 0,
                        'due_date' => '2026-07-31',
                        'completed_at' => '2026-07-30 18:00:00',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function financialDataset(): array
    {
        return [
            [
                'operation_date' => '2026-08-01',
                'income_amount' => '1450.00',
                'fixed_expense_amount' => '350.00',
                'variable_expense_amount' => '590.00',
                'administrative_expense_amount' => '100.00',
                'status' => 'recorded',
                'notes' => 'Inicio de mes con pedidos de fin de semana y compra de insumos principales.',
            ],
            [
                'operation_date' => '2026-08-02',
                'income_amount' => '1680.00',
                'fixed_expense_amount' => '420.00',
                'variable_expense_amount' => '680.00',
                'administrative_expense_amount' => '120.00',
                'status' => 'recorded',
                'notes' => 'Entregas familiares y anticipo de un pedido corporativo.',
            ],
            [
                'operation_date' => '2026-08-03',
                'income_amount' => '1550.00',
                'fixed_expense_amount' => '390.00',
                'variable_expense_amount' => '610.00',
                'administrative_expense_amount' => '110.00',
                'status' => 'recorded',
                'notes' => 'Ventas de pasteles personalizados y reposición de empaques.',
            ],
            [
                'operation_date' => '2026-08-04',
                'income_amount' => '1810.00',
                'fixed_expense_amount' => '440.00',
                'variable_expense_amount' => '730.00',
                'administrative_expense_amount' => '130.00',
                'status' => 'recorded',
                'notes' => 'Jornada de mayor facturación por una mesa dulce y dos celebraciones.',
            ],
            [
                'operation_date' => '2026-08-05',
                'income_amount' => '1680.00',
                'fixed_expense_amount' => '400.00',
                'variable_expense_amount' => '680.00',
                'administrative_expense_amount' => '120.00',
                'status' => 'recorded',
                'notes' => 'Cobros de pedidos entregados y compra de ingredientes frescos.',
            ],
            [
                'operation_date' => '2026-08-06',
                'income_amount' => '480.00',
                'fixed_expense_amount' => '90.00',
                'variable_expense_amount' => '190.00',
                'administrative_expense_amount' => '30.00',
                'status' => 'recorded',
                'notes' => 'Cierre parcial del día utilizado en el recorrido de la demostración.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $identity
     * @param array<string, mixed> $data
     */
    private function upsert(
        string $table,
        array $identity,
        array $data,
        bool $revivesSoftDeletedRows = false,
    ): int {
        $builder = $this->db->table($table);
        $existing = $builder
            ->select('id')
            ->where($identity)
            ->get()
            ->getRowArray();
        $payload = [
            ...$identity,
            ...$data,
            'updated_at' => $this->now,
        ];

        if ($revivesSoftDeletedRows) {
            $payload['deleted_at'] = null;
        }

        if ($existing !== null) {
            if (! $builder->where('id', $existing['id'])->update($payload)) {
                throw new RuntimeException("No fue posible actualizar {$table}.");
            }

            return (int) $existing['id'];
        }

        $payload['created_at'] = $this->now;

        if (! $builder->insert($payload)) {
            throw new RuntimeException("No fue posible crear {$table}.");
        }

        return (int) $this->db->insertID();
    }
}
