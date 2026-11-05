BEGIN;

ALTER TABLE users
    RENAME COLUMN profile_code TO profile_id;

COMMIT;
