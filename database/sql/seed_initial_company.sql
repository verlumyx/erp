-- =============================================================
-- Seed: Empresa inicial con rol administrador y usuario admin
-- Contraseña del usuario: password
-- PostgreSQL compatible
-- =============================================================

DO
$$
    DECLARE
        v_company_id UUID := gen_random_uuid();
        v_role_id    UUID := gen_random_uuid();
        v_user_id    UUID := gen_random_uuid();
        v_uc_id      UUID := gen_random_uuid();
    BEGIN

        -- 1. Compañía
        INSERT INTO app_companies (id, name, status, description, created_by, created_at, updated_at)
        VALUES (v_company_id,
                'Mi Empresa',
                'active',
                'Empresa inicial del sistema',
                v_user_id,
                NOW(),
                NOW());

        -- 2. Rol administrador (acceso total, vinculado a la empresa)
        INSERT INTO app_roles (id, company_id, name, status, description, permission_type, created_at, updated_at)
        VALUES (v_role_id,
                v_company_id,
                'Administrador',
                'active',
                'Rol con acceso total al sistema',
                'all',
                NOW(),
                NOW());

        -- 3. Usuario administrador
        INSERT INTO users (id, name, email, email_verified_at, password, created_at, updated_at, is_system_owner)
        VALUES (v_user_id,
                'Administrador',
                'admin@miempresa.com',
                NOW(),
                '$2y$12$M/mSl1aP6SrvFFiS8xxxaeW9l/ZnA5WpopieEzkPBaYivLgWsgwBm', -- password
                NOW(),
                NOW(),
                true);

        -- 4. Relación usuario ↔ empresa
        INSERT INTO user_company (id, user_id, company_id, role_id, status, created_at, updated_at)
        VALUES (v_uc_id,
                v_user_id,
                v_company_id,
                v_role_id,
                'active',
                NOW(),
                NOW());

    END
$$;

-- Verificación
SELECT u.name    AS usuario,
       u.email,
       r.name    AS rol,
       r.permission_type,
       c.name    AS empresa,
       uc.status AS estado_en_empresa
FROM users u
         JOIN user_company uc ON uc.user_id = u.id
         JOIN app_companies c ON c.id = uc.company_id
         JOIN app_roles r ON r.id = uc.role_id
WHERE u.email = 'admin@miempresa.com';
