CREATE TABLE event_status (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO event_status (name) VALUES
('Agendado'),
('Cancelado'),
('Realizado');

CREATE TABLE events (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP,
    location VARCHAR(255),
    registration_link VARCHAR(500),

    author_id INTEGER NOT NULL,
    status_id INTEGER NOT NULL,

    CONSTRAINT fk_event_author
        FOREIGN KEY (author_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_event_status
        FOREIGN KEY (status_id)
        REFERENCES event_status(id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_event_dates
        CHECK (
            end_date IS NULL
            OR end_date >= start_date
        )
);