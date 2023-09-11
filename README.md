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

Para sincronizar com o Sistema Apolo, rode pediodicamente:

    php cli/sync.php

Para mais informações, há a opção de ajuda:

    php cli/sync.php -h
    
Mapeamento cepaview:

    OFERECIMENTOATIVIDADECEU -> CEPAVIEW_OFERECIMENTOATIVIDADE
    CURSOCEU -> CEPAVIEW_CURSOCEU
    EDICAOCURSOOFECEU -> CEPAVIEW_EDICAOCURSOOFECEU
    MINISTRANTECEU -> CEPAVIEW_MINISTRANTECEU
    EMAILPESSOA -> CEPAVIEW_EMAILPESSOA
    UNIDADE -> CEPAVIEW_UNIDADE
    CAMPUS -> CEPAVIEW_CAMPUS
    PESSOA -> CEPAVIEW_PESSOA
    
## Troubleshooting
   
Listagem dos ministrantes com quantidades de turmas:

    select codpes, count(*) as n_turmas from block_extensao_ministrante group by codpes order by n_turmas;
    
Listagem de turmas com quantidades de ministrantes:

    select codofeatvceu, count(*) as n_ministrantes from block_extensao_ministrante group by codofeatvceu order by n_ministrantes;    
    
Dado um turma com codofeatvceu igual a 90509, listar seus ministrantes:

    select codpes from block_extensao_ministrante where codofeatvceu = 90509;

    
 
 
 
 
 
 
 
