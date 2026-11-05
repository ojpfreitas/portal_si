BEGIN;

CREATE OR REPLACE FUNCTION log_audit() RETURNS trigger AS $$
DECLARE
    v_details   jsonb;
    v_user_id   integer;
    v_origin_ip inet;
BEGIN
    IF TG_OP = 'DELETE' THEN
        v_details := to_jsonb(OLD);
    ELSE
        v_details := to_jsonb(NEW);
    END IF;

    IF TG_TABLE_NAME = 'users' THEN
        v_details := v_details - 'password_hash';

        IF TG_OP = 'UPDATE'
           AND OLD.password_hash IS DISTINCT FROM NEW.password_hash THEN
            v_details := v_details || jsonb_build_object('password_changed', true);
        END IF;
    END IF;

    -- Só grava o usuário se ele existir; sem sessão, user_id fica nulo
    SELECT id INTO v_user_id
    FROM users
    WHERE id = NULLIF(current_setting('app.user_id', true), '')::integer;

    v_origin_ip := NULLIF(current_setting('app.origin_ip', true), '')::inet;

    INSERT INTO audit_log (user_id, action, affected_table, record_id, details, origin_ip)
    VALUES (v_user_id, TG_OP, TG_TABLE_NAME, COALESCE(NEW.id, OLD.id), v_details, v_origin_ip);

    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

COMMIT;
