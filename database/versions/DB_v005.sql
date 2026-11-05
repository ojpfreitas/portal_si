CREATE TABLE publication_status (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO publication_status (name) VALUES
('Rascunho'),
('Publicado'),
('Arquivado');

CREATE TABLE publications (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(280) NOT NULL UNIQUE,
    summary TEXT,
    body TEXT NOT NULL,
    cover_image VARCHAR(500),
    youtube_link VARCHAR(500),

    publication_type_id INTEGER NOT NULL,
    author_id INTEGER NOT NULL,
    status_id INTEGER NOT NULL,

    featured BOOLEAN NOT NULL DEFAULT FALSE,
    published_at TIMESTAMP,

    CONSTRAINT fk_publication_type
        FOREIGN KEY (publication_type_id)
        REFERENCES publication_types(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_publication_author
        FOREIGN KEY (author_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_publication_status
        FOREIGN KEY (status_id)
        REFERENCES publication_status(id)
        ON DELETE RESTRICT
);