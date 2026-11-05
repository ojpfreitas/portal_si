CREATE TABLE publication_types (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(100),
    order_position INTEGER NOT NULL DEFAULT 0,
    active BOOLEAN NOT NULL DEFAULT TRUE
);

INSERT INTO publication_types (name, slug, description, icon, order_position, active) VALUES
('Home', 'home', 'Página inicial e destaques', 'bi-house', 1, TRUE),
('Sobre o Curso', 'sobre-o-curso', 'Informações institucionais e matriz curricular', 'bi-info-circle', 2, TRUE),
('Ações Sociais', 'acoes-sociais', 'Projetos comunitários e intervenções sociais', 'bi-heart', 3, TRUE),
('Atividades Complementares', 'atividades-complementares', 'Normas e eventos de extensão acadêmica', 'bi-journal-check', 4, TRUE),
('Projetos de Extensão', 'projetos-extensao', 'Iniciativas práticas com a comunidade externa', 'bi-mortarboard', 5, TRUE),
('Eventos', 'eventos', 'Congressos, palestras, jornadas e workshops de SI', 'bi-calendar-event', 6, TRUE);