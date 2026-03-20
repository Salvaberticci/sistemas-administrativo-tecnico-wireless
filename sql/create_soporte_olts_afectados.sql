-- Tabla de múltiples OLTs/PONs afectados por soporte (NIVEL 3)
CREATE TABLE IF NOT EXISTS `soporte_olts_afectados` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_soporte` INT NOT NULL,
    `id_olt` INT NOT NULL,
    `id_pon` INT DEFAULT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_soa_soporte` FOREIGN KEY (`id_soporte`) REFERENCES `soportes`(`id_soporte`) ON DELETE CASCADE,
    CONSTRAINT `fk_soa_olt` FOREIGN KEY (`id_olt`) REFERENCES `olt`(`id_olt`),
    CONSTRAINT `fk_soa_pon` FOREIGN KEY (`id_pon`) REFERENCES `pon`(`id_pon`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
