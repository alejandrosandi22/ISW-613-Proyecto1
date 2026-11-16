-- =====================================================================
-- SIGEL - Datos de prueba
-- Ejecutar después de schema.sql.
-- Contraseñas: admin@sigel.local / Admin123!   operador@sigel.local / Operador123!
-- =====================================================================

SET NAMES utf8mb4;
USE sigel;

INSERT INTO users (id, role_id, full_name, email, password_hash) VALUES
    (1, 1, 'Ana Administradora', 'admin@sigel.local',
        '$2y$10$wbk.OEF2NGwu5HnAfdiqU.QvKliYBMiP8pbxU4dXdt/gt8kYTn36W'),
    (2, 2, 'Óscar Operador', 'operador@sigel.local',
        '$2y$10$94WuFcoI1ltTM/eULz4Wiu1OuFhiPKOyzTj85GR3CRJxtZ/ZOPyWS');

INSERT INTO categories (id, name, description, max_loan_days) VALUES
    (1, 'Computadoras portátiles', 'Laptops para uso en laboratorio y proyectos', 7),
    (2, 'Proyectores', 'Proyectores multimedia', 3),
    (3, 'Microcontroladores', 'Placas de desarrollo y kits de electrónica', 14),
    (4, 'Instrumentos de medición', 'Multímetros, osciloscopios y fuentes', 5),
    (5, 'Cámaras', 'Cámaras fotográficas y de video', 3),
    (6, 'Audio', 'Micrófonos, parlantes y grabadoras', 3),
    (7, 'Redes', 'Routers, switches y cables de red', 7),
    (8, 'Impresoras 3D', NULL, 2);

INSERT INTO equipment (id, category_id, code, name, description, status) VALUES
    (1, 1, 'LAP-001', 'Laptop Dell Latitude 5420', 'Intel i5, 16 GB RAM', 'available'),
    (2, 1, 'LAP-002', 'Laptop Dell Latitude 5420', 'Intel i5, 16 GB RAM', 'available'),
    (3, 1, 'LAP-003', 'Laptop Lenovo ThinkPad E14', 'AMD Ryzen 5, 8 GB RAM', 'maintenance'),
    (4, 2, 'PRO-001', 'Proyector Epson PowerLite', '3600 lúmenes', 'available'),
    (5, 2, 'PRO-002', 'Proyector BenQ MW560', '4000 lúmenes', 'available'),
    (6, 3, 'MIC-001', 'Arduino Uno R3', 'Kit con protoboard y cables', 'available'),
    (7, 3, 'MIC-002', 'Arduino Mega 2560', 'Kit con sensores', 'available'),
    (8, 3, 'MIC-003', 'Raspberry Pi 4 (4 GB)', 'Con fuente y tarjeta microSD', 'available'),
    (9, 3, 'MIC-004', 'ESP32 DevKit', 'Wi-Fi y Bluetooth', 'retired'),
    (10, 4, 'MED-001', 'Multímetro Fluke 117', NULL, 'available'),
    (11, 4, 'MED-002', 'Osciloscopio Rigol DS1054Z', '4 canales, 50 MHz', 'available'),
    (12, 4, 'MED-003', 'Fuente de poder Korad KA3005D', '30 V, 5 A', 'available');

-- Préstamos en distintos estados (fechas relativas al momento de la carga).
INSERT INTO loans (id, user_id, operator_id, borrower_name, borrower_document, status, start_at, end_at, notes) VALUES
    (1, 2, 2, 'María Rodríguez', '1-1234-0567', 'returned',
        NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 7 DAY, 'Proyecto de redes'),
    (2, 2, 2, 'Carlos Jiménez', '2-0456-0789', 'delivered',
        NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 2 DAY, NULL),
    (3, 2, 1, 'Laura Vargas', '3-0789-0123', 'approved',
        NOW() + INTERVAL 3 DAY, NOW() + INTERVAL 5 DAY, 'Presentación de tesis'),
    (4, 2, NULL, 'José Mora', '4-0111-0222', 'requested',
        NOW() + INTERVAL 1 DAY, NOW() + INTERVAL 4 DAY, NULL),
    (5, 2, 2, 'Sofía Castro', '5-0333-0444', 'cancelled',
        NOW() + INTERVAL 2 DAY, NOW() + INTERVAL 3 DAY, 'Cancelado por la solicitante');

INSERT INTO loan_items (loan_id, equipment_id, returned_at) VALUES
    (1, 1, NOW() - INTERVAL 7 DAY),
    (1, 6, NOW() - INTERVAL 7 DAY),
    (2, 2, NULL),
    (2, 10, NULL),
    (3, 4, NULL),
    (4, 7, NULL),
    (4, 11, NULL),
    (5, 5, NULL);
