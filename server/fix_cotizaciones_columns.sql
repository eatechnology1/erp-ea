-- ================================================
-- FIX: Agregar columnas faltantes a la tabla cotizaciones
-- ================================================
-- Error: Unknown column 'c.tipo_impuesto' in 'SELECT'
-- Solución: Agregar las columnas que faltan
-- ================================================

-- Verificar si la columna tipo_impuesto existe, si no, agregarla
ALTER TABLE cotizaciones 
ADD COLUMN IF NOT EXISTS tipo_impuesto VARCHAR(50) DEFAULT 'IVA' AFTER estado;

-- Verificar si las columnas AIU existen, si no, agregarlas
ALTER TABLE cotizaciones 
ADD COLUMN IF NOT EXISTS aiu_administracion DECIMAL(5, 2) DEFAULT 10.00 AFTER tipo_impuesto;

ALTER TABLE cotizaciones 
ADD COLUMN IF NOT EXISTS aiu_imprevistos DECIMAL(5, 2) DEFAULT 5.00 AFTER aiu_administracion;

ALTER TABLE cotizaciones 
ADD COLUMN IF NOT EXISTS aiu_utilidad DECIMAL(5, 2) DEFAULT 5.00 AFTER aiu_imprevistos;

-- ================================================
-- Verificación
-- ================================================
-- Para verificar que las columnas se agregaron correctamente:
-- DESCRIBE cotizaciones;
