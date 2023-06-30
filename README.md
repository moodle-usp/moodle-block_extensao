# Cursos de Extensão USP

Instruções de instalação:

    cd blocks
    git clone https://github.com/SEU-FORK/moodle-block_extensao.git extensao
    cd extensao
    composer install

Projeto desenvolvido pela equipe de Moodle da USP.

- [Talita Ventura](https://github.com/TalitaVentura16);
- [Thiago Gomes Veríssimo](https://github.com/thiagogomesverissimo);
- [Ricardo Fontoura](https://github.com/ricardfo);
- [Octavio Augusto Potalej](https://github.com/Potalej).


Referências:

- https://gitlab.uspdigital.usp.br/atp/moodle/-/tree/edisc/blocks/usp_cursos

## Sincronização com o Apolo

Para sincronizar com o Sistema Apolo, basta rodar

    php cli/sync.php

Caso a base já esteja sincronizada e deseje ainda assim capturar os dados, é possível limpar a base e fazer a sincronização. Basta rodar:

    php cli/sync.php --apagar

Para mais informações, há a opção de ajuda:

    php cli/sync.php -h