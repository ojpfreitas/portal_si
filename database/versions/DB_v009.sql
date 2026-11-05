CREATE TABLE audit_log (
    id SERIAL PRIMARY KEY,

    user_id INTEGER,
    action VARCHAR(100) NOT NULL,
    affected_table VARCHAR(100),
    record_id INTEGER,
    details JSONB,
    origin_ip INET,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_log_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE OR REPLACE FUNCTION log_audit()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO audit_log (action, affected_table, record_id, details)
    VALUES (
        TG_OP,
        TG_TABLE_NAME,
        COALESCE(NEW.id, OLD.id),
        CASE
            WHEN TG_OP = 'DELETE' THEN to_jsonb(OLD)
            ELSE to_jsonb(NEW)
        END
    );
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_publications_audit
AFTER INSERT OR UPDATE OR DELETE ON publications
FOR EACH ROW EXECUTE FUNCTION log_audit();