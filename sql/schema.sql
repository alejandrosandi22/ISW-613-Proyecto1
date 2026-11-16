-- =====================================================================
-- SIGEL - Esquema de base de datos
-- Crea la base de datos desde cero.
-- ADVERTENCIA: elimina la base de datos "sigel" si ya existe.
-- =====================================================================

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS sigel;
CREATE DATABASE sigel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigel;

CREATE TABLE roles (
    id    TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(30)      NOT NULL COMMENT 'Clave interna usada por el código',
    label VARCHAR(50)      NOT NULL COMMENT 'Nombre visible en español',
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB;

CREATE TABLE users (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    role_id         TINYINT UNSIGNED NOT NULL,
    full_name       VARCHAR(100)     NOT NULL,
    email           VARCHAR(150)     NOT NULL,
    password_hash   VARCHAR(255)     NOT NULL COMMENT 'Resultado de password_hash()',
    is_active       TINYINT(1)       NOT NULL DEFAULT 1,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until    DATETIME         NULL,
    last_login_at   DATETIME         NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME         NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_full_name (full_name),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE auth_tokens (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED NOT NULL,
    selector       CHAR(18)     NOT NULL COMMENT 'bin2hex(random_bytes(9))',
    validator_hash CHAR(64)     NOT NULL COMMENT 'hash(sha256, validador)',
    expires_at     DATETIME     NOT NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_auth_tokens_selector (selector),
    KEY idx_auth_tokens_expires (expires_at),
    CONSTRAINT fk_auth_tokens_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    name          VARCHAR(60)      NOT NULL,
    description   VARCHAR(255)     NULL,
    max_loan_days TINYINT UNSIGNED NOT NULL DEFAULT 7,
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    DATETIME         NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name),
    CONSTRAINT chk_categories_max_loan_days CHECK (max_loan_days BETWEEN 1 AND 60)
) ENGINE=InnoDB;

CREATE TABLE equipment (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL,
    code        VARCHAR(30)  NOT NULL COMMENT 'Código de inventario',
    name        VARCHAR(100) NOT NULL,
    description VARCHAR(500) NULL,
    status      ENUM('available', 'maintenance', 'retired') NOT NULL DEFAULT 'available',
    version     INT UNSIGNED NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_equipment_code (code),
    KEY idx_equipment_name (name),
    KEY idx_equipment_status (status),
    CONSTRAINT fk_equipment_category FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE equipment_images (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    equipment_id  INT UNSIGNED NOT NULL,
    stored_name   VARCHAR(64)  NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type     VARCHAR(50)  NOT NULL,
    size_bytes    INT UNSIGNED NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_equipment_images_stored_name (stored_name),
    CONSTRAINT fk_equipment_images_equipment FOREIGN KEY (equipment_id) REFERENCES equipment (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE loans (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           INT UNSIGNED NOT NULL,
    operator_id       INT UNSIGNED NULL,
    borrower_name     VARCHAR(100) NOT NULL,
    borrower_document VARCHAR(30)  NOT NULL COMMENT 'Cédula o carné',
    status            ENUM('requested', 'approved', 'delivered', 'returned', 'cancelled')
                      NOT NULL DEFAULT 'requested',
    start_at          DATETIME     NOT NULL,
    end_at            DATETIME     NOT NULL COMMENT 'Fecha de fin prevista',
    notes             VARCHAR(500) NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_loans_status (status),
    KEY idx_loans_dates (start_at, end_at),
    CONSTRAINT fk_loans_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_loans_operator FOREIGN KEY (operator_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_loans_dates CHECK (end_at > start_at)
) ENGINE=InnoDB;

CREATE TABLE loan_items (
    loan_id      INT UNSIGNED NOT NULL,
    equipment_id INT UNSIGNED NOT NULL,
    returned_at  DATETIME     NULL,
    PRIMARY KEY (loan_id, equipment_id),
    KEY idx_loan_items_equipment (equipment_id),
    CONSTRAINT fk_loan_items_loan FOREIGN KEY (loan_id) REFERENCES loans (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_loan_items_equipment FOREIGN KEY (equipment_id) REFERENCES equipment (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE audit_log (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED    NULL,
    action     VARCHAR(30)     NOT NULL COMMENT 'create, update, delete, login, logout...',
    entity     VARCHAR(50)     NOT NULL,
    entity_id  INT UNSIGNED    NULL,
    old_values JSON            NULL,
    new_values JSON            NULL,
    ip_address VARCHAR(45)     NOT NULL COMMENT 'Admite IPv6',
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_log_entity (entity, entity_id),
    KEY idx_audit_log_created (created_at),
    CONSTRAINT fk_audit_log_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TRIGGER trg_audit_log_no_update BEFORE UPDATE ON audit_log FOR EACH ROW
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La bitácora es de solo inserción';

CREATE TRIGGER trg_audit_log_no_delete BEFORE DELETE ON audit_log FOR EACH ROW
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La bitácora es de solo inserción';

INSERT INTO roles (id, name, label) VALUES
    (1, 'admin', 'Administrador'),
    (2, 'operator', 'Operador');
