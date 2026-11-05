CREATE TABLE mime_types (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    extension VARCHAR(10) NOT NULL
);

INSERT INTO mime_types (name, extension) VALUES
('image/jpeg', 'jpg'),
('image/png', 'png'),
('image/webp', 'webp'),
('application/pdf', 'pdf');

CREATE TABLE media (
    id SERIAL PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    path VARCHAR(500) NOT NULL,
    mime_type_id INTEGER NOT NULL,
    size_bytes BIGINT NOT NULL,
    uploaded_by INTEGER NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_media_mime_type
        FOREIGN KEY (mime_type_id)
        REFERENCES mime_types(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_media_user
        FOREIGN KEY (uploaded_by)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_media_size
        CHECK (size_bytes >= 0)
);