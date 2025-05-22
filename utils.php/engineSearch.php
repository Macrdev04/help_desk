<?php

/**
 * Motor de Búsqueda Universal para Mesa de Ayuda Helpdesk_db
 * 
 * Características:
 * - Búsqueda multi-tabla con joins optimizados
 * - Filtros dinámicos y operadores flexibles
 * - Búsqueda full-text y fuzzy matching
 * - Paginación automática
 * - Cache de resultados
 * - Logging de búsquedas
 * 
 * @author Helpdesk Team
 * @version 1.0
 */

namespace Src\Services;

use Src\Utils\Database;
use Src\Utils\Logger;
use Exception;

class SearchEngine 
{
    private $db;
    private $logger;
    private $cache = [];
    private $searchHistory = [];
    
    // Configuraciones por defecto
    private $config = [
        'default_limit' => 20,
        'max_limit' => 100,
        'cache_ttl' => 300, // 5 minutos
        'enable_fuzzy' => true,
        'min_search_length' => 2,
        'enable_logging' => true
    ];
    
    // Mapeo de tablas y sus campos searchables
    private $searchableFields = [
        'tickets' => [
            'primary' => ['title', 'description'],
            'secondary' => ['id'],
            'joins' => [
                'users as creator' => 'tickets.user_id = creator.id',
                'users as agent' => 'tickets.assigned_to = agent.id',
                'ticket_types' => 'tickets.ticket_type_id = ticket_types.id',
                'severity_levels' => 'tickets.severity_level_id = severity_levels.id'
            ],
            'searchable_joins' => [
                'creator.name as creator_name',
                'creator.email as creator_email',
                'agent.name as agent_name',
                'ticket_types.name as type_name',
                'severity_levels.name as severity_name'
            ]
        ],
        'users' => [
            'primary' => ['name', 'email'],
            'secondary' => ['id', 'role'],
            'joins' => [
                'user_status' => 'users.id = user_status.user_id'
            ],
            'searchable_joins' => [
                'user_status.status as user_status'
            ]
        ],
        'comments' => [
            'primary' => ['content'],
            'secondary' => ['id'],
            'joins' => [
                'tickets' => 'comments.ticket_id = tickets.id',
                'users' => 'comments.user_id = users.id'
            ],
            'searchable_joins' => [
                'tickets.title as ticket_title',
                'users.name as author_name'
            ]
        ],
        'notifications' => [
            'primary' => ['title', 'message'],
            'secondary' => ['type'],
            'joins' => [
                'users' => 'notifications.user_id = users.id'
            ],
            'searchable_joins' => [
                'users.name as recipient_name'
            ]
        ]
    ];

    public function __construct(Database $database, Logger $logger = null) 
    {
        $this->db = $database;
        $this->logger = $logger;
    }

    /**
     * Búsqueda universal - Punto de entrada principal
     * 
     * @param string $table Tabla principal a buscar
     * @param array $params Parámetros de búsqueda
     * @return array Resultados paginados
     */
    public function search(string $table, array $params = []): array 
    {
        try {
            // Validar tabla
            if (!isset($this->searchableFields[$table])) {
                throw new Exception("Tabla no válida para búsqueda: {$table}");
            }

            // Normalizar parámetros
            $params = $this->normalizeParams($params);
            
            // Generar clave de cache
            $cacheKey = $this->generateCacheKey($table, $params);
            
            // Verificar cache
            if ($this->isCacheValid($cacheKey)) {
                return $this->cache[$cacheKey]['data'];
            }

            // Construir y ejecutar consulta
            $query = $this->buildQuery($table, $params);
            $countQuery = $this->buildCountQuery($table, $params);
            
            // Ejecutar consultas
            $results = $this->db->query($query['sql'], $query['params']);
            $totalResults = $this->db->query($countQuery['sql'], $countQuery['params']);
            $total = $totalResults[0]['total'] ?? 0;

            // Preparar respuesta
            $response = [
                'data' => $results,
                'pagination' => [
                    'current_page' => $params['page'],
                    'per_page' => $params['limit'],
                    'total' => (int)$total,
                    'total_pages' => ceil($total / $params['limit']),
                    'has_more' => ($params['page'] * $params['limit']) < $total
                ],
                'meta' => [
                    'search_term' => $params['search'] ?? '',
                    'filters_applied' => count($params['filters']),
                    'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
                    'table' => $table
                ]
            ];

            // Guardar en cache
            $this->saveToCache($cacheKey, $response);
            
            // Log de búsqueda
            $this->logSearch($table, $params, $total);

            return $response;

        } catch (Exception $e) {
            $this->logError($e, ['table' => $table, 'params' => $params]);
            throw $e;
        }
    }

    /**
     * Búsqueda específica para tickets con filtros avanzados
     */
    public function searchTickets(array $params = []): array 
    {
        $enhancedParams = array_merge($params, [
            'filters' => array_merge($params['filters'] ?? [], [
                // Filtros específicos de tickets
                'status_priority' => true,
                'sla_monitoring' => true
            ])
        ]);

        return $this->search('tickets', $enhancedParams);
    }

    /**
     * Búsqueda global en múltiples tablas
     */
    public function globalSearch(string $searchTerm, array $tables = null, int $limit = 10): array 
    {
        if ($tables === null) {
            $tables = ['tickets', 'users', 'comments'];
        }

        $results = [];
        
        foreach ($tables as $table) {
            try {
                $tableResults = $this->search($table, [
                    'search' => $searchTerm,
                    'limit' => $limit,
                    'page' => 1
                ]);
                
                $results[$table] = [
                    'data' => $tableResults['data'],
                    'total' => $tableResults['pagination']['total'],
                    'table_label' => $this->getTableLabel($table)
                ];
                
            } catch (Exception $e) {
                $results[$table] = [
                    'data' => [],
                    'total' => 0,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'results' => $results,
            'search_term' => $searchTerm,
            'total_found' => array_sum(array_column($results, 'total'))
        ];
    }

    /**
     * Construir consulta SQL principal
     */
    private function buildQuery(string $table, array $params): array 
    {
        $config = $this->searchableFields[$table];
        $select = $this->buildSelectClause($table, $config);
        $joins = $this->buildJoinsClause($config);
        $where = $this->buildWhereClause($table, $params, $config);
        $orderBy = $this->buildOrderByClause($params);
        $limit = $this->buildLimitClause($params);

        $sql = "
            SELECT {$select}
            FROM {$table}
            {$joins}
            {$where['clause']}
            {$orderBy}
            {$limit}
        ";

        return [
            'sql' => $sql,
            'params' => $where['params']
        ];
    }

    /**
     * Construir consulta de conteo
     */
    private function buildCountQuery(string $table, array $params): array 
    {
        $config = $this->searchableFields[$table];
        $joins = $this->buildJoinsClause($config);
        $where = $this->buildWhereClause($table, $params, $config);

        $sql = "
            SELECT COUNT(DISTINCT {$table}.id) as total
            FROM {$table}
            {$joins}
            {$where['clause']}
        ";

        return [
            'sql' => $sql,
            'params' => $where['params']
        ];
    }

    /**
     * Construir cláusula SELECT
     */
    private function buildSelectClause(string $table, array $config): string 
    {
        $fields = ["{$table}.*"];
        
        if (isset($config['searchable_joins'])) {
            $fields = array_merge($fields, $config['searchable_joins']);
        }

        // Agregar campos calculados específicos por tabla
        switch ($table) {
            case 'tickets':
                $fields[] = "CASE 
                    WHEN {$table}.status IN ('resolved', 'closed') THEN 'Completado'
                    WHEN NOW() > {$table}.due_date THEN 'Vencido'
                    WHEN TIMESTAMPDIFF(HOUR, NOW(), {$table}.due_date) <= 4 THEN 'Urgente'
                    ELSE 'Normal'
                END as urgency_status";
                $fields[] = "TIMESTAMPDIFF(HOUR, {$table}.created_at, COALESCE({$table}.resolved_at, NOW())) as hours_open";
                break;
                
            case 'users':
                $fields[] = "COALESCE(us.open_tickets_count, 0) as current_tickets";
                break;
        }

        return implode(', ', $fields);
    }

    /**
     * Construir cláusula WHERE con búsqueda y filtros
     */
    private function buildWhereClause(string $table, array $params, array $config): array 
    {
        $conditions = [];
        $queryParams = [];

        // Búsqueda por texto
        if (!empty($params['search'])) {
            $searchCondition = $this->buildSearchCondition($table, $params['search'], $config);
            $conditions[] = $searchCondition['condition'];
            $queryParams = array_merge($queryParams, $searchCondition['params']);
        }

        // Filtros específicos
        foreach ($params['filters'] as $filter => $value) {
            if (empty($value) && $value !== 0) continue;

            $filterCondition = $this->buildFilterCondition($table, $filter, $value);
            if ($filterCondition) {
                $conditions[] = $filterCondition['condition'];
                if (isset($filterCondition['params'])) {
                    $queryParams = array_merge($queryParams, $filterCondition['params']);
                }
            }
        }

        // Filtros de seguridad por rol (implementar según necesidad)
        $securityFilter = $this->buildSecurityFilter($table);
        if ($securityFilter) {
            $conditions[] = $securityFilter;
        }

        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        return [
            'clause' => $whereClause,
            'params' => $queryParams
        ];
    }

    /**
     * Construir condición de búsqueda de texto
     */
    private function buildSearchCondition(string $table, string $search, array $config): array 
    {
        $search = trim($search);
        $searchParams = [];
        $conditions = [];

        // Búsqueda en campos primarios (más peso)
        $primaryFields = $config['primary'] ?? [];
        foreach ($primaryFields as $field) {
            $conditions[] = "{$table}.{$field} LIKE ?";
            $searchParams[] = "%{$search}%";
        }

        // Búsqueda en campos secundarios
        $secondaryFields = $config['secondary'] ?? [];
        foreach ($secondaryFields as $field) {
            if (is_numeric($search) || strlen($search) <= 10) {
                $conditions[] = "{$table}.{$field} = ?";
                $searchParams[] = $search;
            }
        }

        // Búsqueda en campos de joins
        if (isset($config['searchable_joins'])) {
            foreach ($config['searchable_joins'] as $joinField) {
                $fieldName = explode(' as ', $joinField)[0];
                $conditions[] = "{$fieldName} LIKE ?";
                $searchParams[] = "%{$search}%";
            }
        }

        // Búsqueda fuzzy (opcional)
        if ($this->config['enable_fuzzy'] && strlen($search) >= 3) {
            $fuzzyConditions = $this->buildFuzzySearch($table, $search, $config);
            $conditions = array_merge($conditions, $fuzzyConditions['conditions']);
            $searchParams = array_merge($searchParams, $fuzzyConditions['params']);
        }

        return [
            'condition' => '(' . implode(' OR ', $conditions) . ')',
            'params' => $searchParams
        ];
    }

    /**
     * Construir filtros específicos por tabla
     */
    private function buildFilterCondition(string $table, string $filter, $value): ?array 
    {
        switch ($table) {
            case 'tickets':
                return $this->buildTicketFilter($filter, $value);
            case 'users':
                return $this->buildUserFilter($filter, $value);
            case 'comments':
                return $this->buildCommentFilter($filter, $value);
            default:
                return null;
        }
    }

    /**
     * Filtros específicos para tickets
     */
    private function buildTicketFilter(string $filter, $value): ?array 
    {
        switch ($filter) {
            case 'status':
                if (is_array($value)) {
                    $placeholders = str_repeat('?,', count($value) - 1) . '?';
                    return [
                        'condition' => "tickets.status IN ({$placeholders})",
                        'params' => $value
                    ];
                }
                return ['condition' => 'tickets.status = ?', 'params' => [$value]];

            case 'priority':
                return ['condition' => 'severity_levels.priority = ?', 'params' => [$value]];

            case 'assigned_to':
                return ['condition' => 'tickets.assigned_to = ?', 'params' => [$value]];

            case 'created_by':
                return ['condition' => 'tickets.user_id = ?', 'params' => [$value]];

            case 'ticket_type':
                return ['condition' => 'tickets.ticket_type_id = ?', 'params' => [$value]];

            case 'sla_breach':
                return ['condition' => 'tickets.sla_breach = ?', 'params' => [(bool)$value]];

            case 'overdue':
                return ['condition' => 'tickets.due_date < NOW() AND tickets.status NOT IN ("resolved", "closed")', 'params' => []];

            case 'date_range':
                if (isset($value['from']) && isset($value['to'])) {
                    return [
                        'condition' => 'tickets.created_at BETWEEN ? AND ?',
                        'params' => [$value['from'], $value['to']]
                    ];
                }
                break;

            case 'urgent':
                return [
                    'condition' => 'TIMESTAMPDIFF(HOUR, NOW(), tickets.due_date) <= 4 AND tickets.status NOT IN ("resolved", "closed")',
                    'params' => []
                ];
        }

        return null;
    }

    /**
     * Filtros específicos para usuarios
     */
    private function buildUserFilter(string $filter, $value): ?array 
    {
        switch ($filter) {
            case 'role':
                return ['condition' => 'users.role = ?', 'params' => [$value]];
            case 'status':
                return ['condition' => 'user_status.status = ?', 'params' => [$value]];
            case 'active_tickets':
                return ['condition' => 'users.open_tickets_count > 0', 'params' => []];
        }
        return null;
    }

    /**
     * Filtros específicos para comentarios
     */
    private function buildCommentFilter(string $filter, $value): ?array 
    {
        switch ($filter) {
            case 'ticket_id':
                return ['condition' => 'comments.ticket_id = ?', 'params' => [$value]];
            case 'is_internal':
                return ['condition' => 'comments.is_internal = ?', 'params' => [(bool)$value]];
            case 'author':
                return ['condition' => 'comments.user_id = ?', 'params' => [$value]];
        }
        return null;
    }

    /**
     * Construir búsqueda fuzzy/aproximada
     */
    private function buildFuzzySearch(string $table, string $search, array $config): array 
    {
        $conditions = [];
        $params = [];

        // Búsqueda con SOUNDEX para nombres/títulos
        $primaryFields = $config['primary'] ?? [];
        foreach ($primaryFields as $field) {
            if (in_array($field, ['title', 'name'])) {
                $conditions[] = "SOUNDEX({$table}.{$field}) = SOUNDEX(?)";
                $params[] = $search;
            }
        }

        return [
            'conditions' => $conditions,
            'params' => $params
        ];
    }

    /**
     * Normalizar parámetros de entrada
     */
    private function normalizeParams(array $params): array 
    {
        return [
            'search' => trim($params['search'] ?? ''),
            'filters' => $params['filters'] ?? [],
            'sort_by' => $params['sort_by'] ?? 'created_at',
            'sort_direction' => strtoupper($params['sort_direction'] ?? 'DESC'),
            'page' => max(1, intval($params['page'] ?? 1)),
            'limit' => min($this->config['max_limit'], max(1, intval($params['limit'] ?? $this->config['default_limit'])))
        ];
    }

    /**
     * Construir cláusula ORDER BY
     */
    private function buildOrderByClause(array $params): string 
    {
        $sortBy = $params['sort_by'];
        $direction = in_array($params['sort_direction'], ['ASC', 'DESC']) ? $params['sort_direction'] : 'DESC';
        
        // Mapeo de campos de ordenamiento personalizados
        $sortMappings = [
            'priority' => 'severity_levels.priority ASC, tickets.created_at',
            'urgency' => 'tickets.due_date ASC, severity_levels.priority',
            'agent' => 'agent.name',
            'creator' => 'creator.name'
        ];

        $orderField = $sortMappings[$sortBy] ?? $sortBy;
        
        return "ORDER BY {$orderField} {$direction}";
    }

    /**
     * Construir cláusula LIMIT con paginación
     */
    private function buildLimitClause(array $params): string 
    {
        $offset = ($params['page'] - 1) * $params['limit'];
        return "LIMIT {$offset}, {$params['limit']}";
    }

    /**
     * Construir JOINs necesarios
     */
    private function buildJoinsClause(array $config): string 
    {
        $joins = [];
        
        if (isset($config['joins'])) {
            foreach ($config['joins'] as $table => $condition) {
                $joins[] = "LEFT JOIN {$table} ON {$condition}";
            }
        }

        return implode(' ', $joins);
    }

    /**
     * Aplicar filtros de seguridad según el rol del usuario
     */
    private function buildSecurityFilter(string $table): ?string 
    {
        // Implementar según las reglas de negocio
        // Por ejemplo, los usuarios solo ven sus propios tickets
        return null;
    }

    /**
     * Gestión de cache
     */
    private function generateCacheKey(string $table, array $params): string 
    {
        return 'search_' . $table . '_' . md5(serialize($params));
    }

    private function isCacheValid(string $key): bool 
    {
        return isset($this->cache[$key]) && 
               (time() - $this->cache[$key]['timestamp']) < $this->config['cache_ttl'];
    }

    private function saveToCache(string $key, array $data): void 
    {
        $this->cache[$key] = [
            'data' => $data,
            'timestamp' => time()
        ];
    }

    /**
     * Logging y métricas
     */
    private function logSearch(string $table, array $params, int $results): void 
    {
        if (!$this->config['enable_logging'] || !$this->logger) return;

        $this->logger->info('Search performed', [
            'table' => $table,
            'search_term' => $params['search'] ?? '',
            'filters_count' => count($params['filters']),
            'results_found' => $results,
            'page' => $params['page'],
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        ]);

        // Guardar en historial para análisis
        $this->searchHistory[] = [
            'table' => $table,
            'term' => $params['search'] ?? '',
            'results' => $results,
            'timestamp' => time()
        ];
    }

    private function logError(Exception $e, array $context = []): void 
    {
        if ($this->logger) {
            $this->logger->error('Search engine error: ' . $e->getMessage(), $context);
        }
    }

    /**
     * Utilidades
     */
    private function getTableLabel(string $table): string 
    {
        $labels = [
            'tickets' => 'Tickets',
            'users' => 'Usuarios',
            'comments' => 'Comentarios',
            'notifications' => 'Notificaciones'
        ];

        return $labels[$table] ?? ucfirst($table);
    }

    /**
     * Obtener estadísticas de búsqueda
     */
    public function getSearchStats(): array 
    {
        return [
            'total_searches' => count($this->searchHistory),
            'most_searched_table' => $this->getMostSearchedTable(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'avg_results_per_search' => $this->getAverageResults()
        ];
    }

    private function getMostSearchedTable(): ?string 
    {
        if (empty($this->searchHistory)) return null;
        
        $tables = array_column($this->searchHistory, 'table');
        $counts = array_count_values($tables);
        
        return array_keys($counts, max($counts))[0];
    }

    private function getCacheHitRate(): float 
    {
        // Implementar lógica de cálculo de cache hit rate
        return 0.0;
    }

    private function getAverageResults(): float 
    {
        if (empty($this->searchHistory)) return 0.0;
        
        $totalResults = array_sum(array_column($this->searchHistory, 'results'));
        return $totalResults / count($this->searchHistory);
    }

    /**
     * Limpiar cache manualmente
     */
    public function clearCache(): void 
    {
        $this->cache = [];
    }

    /**
     * Configurar opciones del motor
     */
    public function setConfig(array $config): void 
    {
        $this->config = array_merge($this->config, $config);
    }
}