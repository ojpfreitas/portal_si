DO $$
DECLARE
    tbl RECORD;
BEGIN
    FOR tbl IN
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_type = 'BASE TABLE'
          AND table_name NOT IN ('audit_log', 'schema_version')
    LOOP
        EXECUTE format(
            'DROP TRIGGER IF EXISTS trg_%s_audit ON %I;
            CREATE TRIGGER trg_%s_audit
            AFTER INSERT OR UPDATE OR DELETE ON %I
            FOR EACH ROW EXECUTE FUNCTION log_audit();',
            tbl.table_name, tbl.table_name,
            tbl.table_name, tbl.table_name
        );
    END LOOP;
END $$;