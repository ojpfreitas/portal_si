CREATE TABLE publication_tags (
    id SERIAL PRIMARY KEY,
    publication_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,

    UNIQUE (publication_id, tag_id),

    CONSTRAINT fk_publication_tags_publication
        FOREIGN KEY (publication_id)
        REFERENCES publications(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_publication_tags_tag
        FOREIGN KEY (tag_id)
        REFERENCES tags(id)
        ON DELETE CASCADE
);