-- =====================================================
-- MÓDULO DE PROYECTOS Y PRESUPUESTOS
-- =====================================================

-- Tabla de Proyectos (Maestro)
CREATE TABLE IF NOT EXISTS proyectos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  cliente_id INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NULL,
  estado ENUM('activo', 'finalizado', 'cancelado') DEFAULT 'activo',
  valor_cierre DECIMAL(15, 2) DEFAULT 0.00,
  costo_total DECIMAL(15, 2) DEFAULT 0.00,
  iva_porcentaje DECIMAL(5, 2) DEFAULT 19.00,
  administracion_porcentaje DECIMAL(5, 2) DEFAULT 0.00,
  improvistos_porcentaje DECIMAL(5, 2) DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  INDEX idx_cliente (cliente_id),
  INDEX idx_estado (estado),
  INDEX idx_fecha_inicio (fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Categorías de Proyecto (Especialidades)
CREATE TABLE IF NOT EXISTS proyecto_categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE,
  descripcion TEXT NULL,
  color VARCHAR(7) DEFAULT '#00e5ff',
  icono VARCHAR(50) DEFAULT 'category',
  orden INT DEFAULT 0,
  activo BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_activo (activo),
  INDEX idx_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Items de Proyecto (Detalle de Presupuesto)
CREATE TABLE IF NOT EXISTS proyecto_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT NOT NULL,
  categoria_id INT NOT NULL,
  descripcion TEXT NOT NULL,
  unidad VARCHAR(50) DEFAULT 'UN',
  cantidad DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
  valor_unitario DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  costo_materiales DECIMAL(15, 2) DEFAULT 0.00,
  mano_de_obra DECIMAL(15, 2) DEFAULT 0.00,
  orden INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE,
  FOREIGN KEY (categoria_id) REFERENCES proyecto_categorias(id) ON DELETE RESTRICT,
  INDEX idx_proyecto (proyecto_id),
  INDEX idx_categoria (categoria_id),
  INDEX idx_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS INICIALES - CATEGORÍAS POR DEFECTO
-- =====================================================

INSERT INTO proyecto_categorias (nombre, descripcion, color, icono, orden) VALUES
('Eléctrico', 'Instalaciones eléctricas y cableado', '#00e5ff', 'electric_bolt', 1),
('Domótica', 'Automatización y control inteligente', '#10b981', 'home_automation', 2),
('Iluminación', 'Sistemas de iluminación LED y decorativa', '#f59e0b', 'lightbulb', 3),
('Sonido', 'Audio profesional y entretenimiento', '#8b5cf6', 'volume_up', 4),
('Redes', 'Infraestructura de red y comunicaciones', '#3b82f6', 'router', 5),
('Seguridad', 'Cámaras, alarmas y control de acceso', '#ef4444', 'security', 6),
('Climatización', 'HVAC y control de temperatura', '#06b6d4', 'ac_unit', 7),
('Otros', 'Servicios y materiales adicionales', '#6b7280', 'more_horiz', 99)
ON DUPLICATE KEY UPDATE 
  descripcion = VALUES(descripcion),
  color = VALUES(color),
  icono = VALUES(icono),
  orden = VALUES(orden);
