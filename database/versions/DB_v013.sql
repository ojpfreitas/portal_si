BEGIN;

ALTER TABLE media ADD COLUMN IF NOT EXISTS status BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE media ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP;

-- A data de exclusão existe se, e somente se, a mídia está excluída
ALTER TABLE media DROP CONSTRAINT IF EXISTS chk_media_deleted_at;
ALTER TABLE media ADD CONSTRAINT chk_media_deleted_at
    CHECK ((status = FALSE) = (deleted_at IS NOT NULL));

-- Índice das mídias excluídas, por data de exclusão
CREATE INDEX IF NOT EXISTS idx_media_deleted_at ON media(deleted_at) WHERE status = FALSE;

COMMIT;
