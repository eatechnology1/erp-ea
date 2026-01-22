-- ================================================
-- MIGRACIÓN: Crear tablas para el módulo de Cotizaciones
-- ================================================
-- Fecha: 2026-01-21
-- Descripción: Crea las tablas necesarias para el sistema de cotizaciones
--              con soporte para diferentes regímenes tributarios DIAN
-- ================================================

-- Tabla principal de cotizaciones
CREATE TABLE IF NOT EXISTS cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    iva DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    total DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    estado ENUM('borrador', 'enviada', 'aprobada', 'rechazada') NOT NULL DEFAULT 'borrador',
    tipo_impuesto VARCHAR(50) DEFAULT 'IVA',
    aiu_administracion DECIMAL(5, 2) DEFAULT 10.00,
    aiu_imprevistos DECIMAL(5, 2) DEFAULT 5.00,
    aiu_utilidad DECIMAL(5, 2) DEFAULT 5.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de ítems de cotización
CREATE TABLE IF NOT EXISTS cotizacion_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    inventario_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (inventario_id) REFERENCES inventario(id) ON DELETE RESTRICT,
    INDEX idx_cotizacion (cotizacion_id),
    INDEX idx_inventario (inventario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Datos de prueba (opcional - comentar si no se necesita)
-- ================================================

-- Insertar una cotización de ejemplo (solo si existe un cliente con id=1)
-- INSERT INTO cotizaciones (cliente_id, fecha, subtotal, iva, total, estado, tipo_impuesto)
-- VALUES (1, NOW(), 1000000.00, 190000.00, 1190000.00, 'borrador', 'IVA');

-- Insertar ítems de ejemplo (solo si existe inventario con id=1)
-- INSERT INTO cotizacion_items (cotizacion_id, inventario_id, cantidad, precio_unitario, subtotal)
-- VALUES (1, 1, 2, 500000.00, 1000000.00);

-- ================================================
-- Verificación de tablas creadas
-- ================================================
-- Para verificar que las tablas se crearon correctamente, ejecutar:
-- SHOW TABLES LIKE 'cotizacion%';
-- DESCRIBE cotizaciones;
-- DESCRIBE cotizacion_items;
