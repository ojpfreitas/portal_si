BEGIN;

-- Fuso horário padrão das conexões ao banco
DO $$
BEGIN
    EXECUTE format('ALTER DATABASE %I SET timezone TO %L', current_database(), 'America/Sao_Paulo');
END $$;

COMMIT;
