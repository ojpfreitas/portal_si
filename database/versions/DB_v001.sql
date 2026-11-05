CREATE TABLE user_profile (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    status BOOLEAN NOT NULL DEFAULT TRUE
);

INSERT INTO user_profile (name, description) VALUES
('Acesso Total', 'Perfil com acesso completo ao sistema'),
('Visualização', 'Perfil com permissão apenas de visualização'),
('Divulgação', 'Perfil com permissão de postagem de conteúdo'),
('Administrador', 'Perfil com permissões administrativas');