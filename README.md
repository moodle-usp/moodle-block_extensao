# Cursos de Extensão USP

Projeto desenvolvido pela equipe de Moodle da USP.

- [Talita Ventura](https://github.com/TalitaVentura16);
- [Thiago Gomes Veríssimo](https://github.com/thiagogomesverissimo);
- [Ricardo Fontoura](https://github.com/ricardfo);
- [Octavio Augusto Potalej](https://github.com/Potalej).

Instruções de instalação:

    cd blocks
    git clone https://github.com/SEU-FORK/moodle-block_extensao.git extensao
    cd extensao

Referências:

- https://gitlab.uspdigital.usp.br/atp/moodle/-/tree/edisc/blocks/usp_cursos

Para mais informações, consulte a [Wiki](https://github.com/moodle-usp/moodle-block_extensao/wiki).

## Configurações

O plugin possui as seguintes configurações:
- Credenciais da base de dados (Apolo):
    - Host;
    - Porta;
    - Base de dados;
    - Usuário;
    - Senha.
- Envio de e-mails:
    - Texto enviado para novos usuários. Contém tokens de substituição;
- Tabelas da base de dados ([ver relação do CEPAVIEW](#mapeamento-cepaview)):
    - Oferecimento do curso/turma. Padrão: `OFERECIMENTOATIVIDADECEU`;
    - Informações do curso. Padrão: `CURSOCEU`;
    - Informações da edição do curso. Padrão: `EDICAOCURSOOFECEU`;
    - Informações dos ministrantes. Padrão: `MINISTRANTECEU`;
    - E-mails. Padrão: `EMAILPESSOA`;
    - Unidades da USP. Padrão: `UNIDADE`;
    - Campi da USP. Padrão: `CAMPUS`;
    - Informações de cadastro. Padrão: `PESSOA`;
- Busca de cursos:
    - Período de busca de turmas: 3, 6, 9 meses ou 1 ano.

## Sincronização com o Apolo

Configurados os dados de acesso à base, para sincronizar com o Sistema Apolo, rode:

    php cli/sync.php

Para mais informações, há a opção de ajuda:

    php cli/sync.php -h
    
### Mapeamento cepaview:

As tabelas na base do CEPAVIEW são as correspondentes:

| Padrão                   | CEPAVIEW (Visualização)        |
| ------------------------ |:------------------------------:|
| OFERECIMENTOATIVIDADECEU | CEPAVIEW_OFERECIMENTOATIVIDADE |
| CURSOCEU                 | CEPAVIEW_CURSOCEU              |
| EDICAOCURSOOFECEU        | CEPAVIEW_EDICAOCURSOOFECEU     |
| MINISTRANTECEU           | CEPAVIEW_MINISTRANTECEU        |
| EMAILPESSOA              | CEPAVIEW_EMAILPESSOA           |
| UNIDADE                  | CEPAVIEW_UNIDADE               |
| CAMPUS                   | CEPAVIEW_CAMPUS                |
| PESSOA                   | CEPAVIEW_PESSOA                |   
    
## Troubleshooting
   
Listagem dos ministrantes com quantidades de turmas:

    select codpes, count(*) as n_turmas from block_extensao_ministrante group by codpes order by n_turmas;
    
Listagem de turmas com quantidades de ministrantes:

    select codofeatvceu, count(*) as n_ministrantes from block_extensao_ministrante group by codofeatvceu order by n_ministrantes;    
    
Dado um turma com codofeatvceu igual a 90509, listar seus ministrantes:

    select codpes from block_extensao_ministrante where codofeatvceu = 90509;