BEGIN;

DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'users_profile_code_not_null'
          AND conrelid = 'public.users'::regclass
    ) THEN
        ALTER TABLE users RENAME CONSTRAINT users_profile_code_not_null TO users_profile_id_not_null;
    END IF;
END $$;

--

CREATE INDEX IF NOT EXISTS idx_events_author_id ON events(author_id);
CREATE INDEX IF NOT EXISTS idx_events_status_id ON events(status_id);
CREATE INDEX IF NOT EXISTS idx_media_uploaded_by ON media(uploaded_by);
CREATE INDEX IF NOT EXISTS idx_media_mime_type_id ON media(mime_type_id);
CREATE INDEX IF NOT EXISTS idx_publications_author_id ON publications(author_id);
CREATE INDEX IF NOT EXISTS idx_publications_status_id ON publications(status_id);
CREATE INDEX IF NOT EXISTS idx_publications_type_id ON publications(publication_type_id);
CREATE INDEX IF NOT EXISTS idx_users_profile_id ON users(profile_id);
CREATE INDEX IF NOT EXISTS idx_publication_tags_tag_id ON publication_tags(tag_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_user_id ON audit_log(user_id);

--
CREATE INDEX IF NOT EXISTS idx_audit_log_created_at ON audit_log(created_at);
CREATE INDEX IF NOT EXISTS idx_audit_log_table_record ON audit_log(affected_table, record_id);

--

ALTER TABLE events ADD COLUMN IF NOT EXISTS created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL;
ALTER TABLE publications ADD COLUMN IF NOT EXISTS created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL;

--

ALTER TABLE events ADD COLUMN IF NOT EXISTS updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL;
ALTER TABLE publications ADD COLUMN IF NOT EXISTS updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL;

--

CREATE OR REPLACE FUNCTION set_updated_at() RETURNS trigger AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_events_updated_at BEFORE UPDATE ON events
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_publications_updated_at BEFORE UPDATE ON publications
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

--

ALTER TABLE publications ADD COLUMN IF NOT EXISTS media_id integer;

ALTER TABLE publications ADD CONSTRAINT fk_publication_media
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_publications_media_id ON publications(media_id);

ALTER TABLE publications DROP COLUMN cover_image;

--

CREATE OR REPLACE FUNCTION log_audit() RETURNS trigger AS $$
DECLARE
    v_details jsonb;
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

    INSERT INTO audit_log (action, affected_table, record_id, details)
    VALUES (TG_OP, TG_TABLE_NAME, COALESCE(NEW.id, OLD.id), v_details);

    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

COMMIT;