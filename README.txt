FEIRAFÁCIL — SISTEMA WEB DE CADASTRO E GESTÃO DE PRODUTOS PARA FEIRA
Aluno: Andrei Silva Perozini
Disciplina: Desenvolvimento Web I
Tecnologias: HTML5, CSS3, JavaScript, Bootstrap 5, PHP e MySQL.

REQUISITOS
- XAMPP (Apache e MySQL) ou ambiente equivalente
- PHP 8 ou superior, extensões PDO MySQL e Fileinfo habilitadas
- Navegador atualizado e conexão com internet para carregar Bootstrap 5 via CDN

COMO EXECUTAR NO XAMPP
1. Extraia a pasta sistema_feira_livre para C:\xampp\htdocs\
2. Abra o XAMPP e inicie Apache e MySQL.
3. Acesse http://localhost/phpmyadmin
4. Importe o arquivo database.sql (aba Importar).
5. Confira usuário e senha do MySQL no config.php. No XAMPP padrão: usuário root e senha vazia.
6. Acesse http://localhost/sistema_feira_livre/

FUNCIONALIDADES IMPLEMENTADAS
- CRUD de categorias/tipos de produto.
- CRUD de produtos com nome, quantidade, valor, validade e categoria.
- Listagem com preço em Real e indicação visual de validade.
- Upload de várias fotos por produto, pré-visualização e carrossel Bootstrap.
- Exclusão individual de fotos.
- Exclusão do produto com remoção em cascata dos registros de fotos (FK ON DELETE CASCADE) e dos arquivos.
- Validação no navegador e validação novamente no servidor.
- Consultas SQL preparadas com PDO para reduzir risco de SQL injection.
- Interface responsiva com Bootstrap 5 e HTML5 semântico.

OBSERVAÇÕES
- Os arquivos enviados ficam na pasta uploads/. Ela precisa ter permissão de escrita para o Apache.
- Formatos permitidos: JPG, PNG, WEBP e GIF. Limite de 5 MB por foto.
- O banco impede excluir uma categoria que ainda esteja vinculada a produtos; primeiro altere ou exclua esses produtos.
- O Bootstrap é carregado por CDN, portanto a primeira abertura precisa de internet.

ESTRUTURA
database.sql  -> criação do banco e das três tabelas
config.php    -> conexão PDO e funções auxiliares
index.php     -> telas e operações CRUD
style.css     -> identidade visual e responsividade
script.js     -> validação e pré-visualização de fotos
uploads/      -> imagens enviadas pelos usuários
