# Find Bids

Aplicação para consultar licitações do ComprasNet via scraping, com API em PHP (MVC) e frontend em Vue.js.

---

## Instalação e execução

### Backend (PHP)

1. Abra um terminal na raiz do projeto.  

2. Instale as dependências via Composer:
   ```bash
   composer install
   ```
3. Abra o servidor PHP
    ```bash
   php -S localhost:8080 -t public
   ```

### FRONTEND (VUE)

1. Abra um terminal na pasta frontend.  

2. Instale as dependências:
   ```bash
   npm install
   ```
3. Abra o servidor
    ```bash
   npm run dev
   ```

### Importante: os dois servidores precisam estar rodando ao mesmo tempo!

---

### Rotas

1. Lista os que estão na página 1 (para não sobrecarregar)
    ```bash
   http://localhost:8080/api/licitacoes
   ```


2. Lista os que estão na página que foi passada na url opcional (para não sobrecarregar)
   ```bash
   http://localhost:8080/api/licitacoes?pagina=2
   ```

3. Procurar por UASG
    ```bash
   http://localhost:8080/api/licitacoes/uasg/16042
   ```

4. Procurar por número do pregão
    ```bash
   http://localhost:8080/api/licitacoes/pregao?numero=90020/2025
   ```
---
### Testes

1. Para rodar os testes do Backend
    ```bash
   ./vendor/bin/phpunit --testdox
   ```

2. Para rodar os testes do Front
   ```bash
   npm run cypress:run
   ```

---


### Respostas

<b>Pergunta</b>: Imagine que, após a entrega dessa funcionalidade, o time percebe lentidão no carregamento das licitações quando há mais de 1.000 registros.

<b>R</b>: Uma das soluções seria fazer paginação, aplicado no caso no teste feito. A página faz a requisição sempre que movemos para a próxima listagem, então isso não sobrecarrega a requisição, que está buscando no máximo 20 itens.

A segunda solução seria fazer o cacheamento da lista completa, então somente a primeira busca feita seria lenta, as demais serão extremamente rápidas, pois estão buscando no cache ao invés de fazer uma requisição nova. A cada 5minutos o cache se refaz para buscar novos itens. Dependendo do fluxo, o tempo poderia ser maior ou menor. Foi aplicado também nesse projeto, porém só na página 1 como exemplo de funcionalidade. Caso fosse levado para frente, redis seria uma boa escolha para fazer o cacheamento.

---

<b>Pergunta</b>: Informe quais partes do teste foram implementadas e o tempo estimado gasto

<b>R</b>: Modelagem inicial da arquitura do projeto: 30 minutos<br>
Endpoints: 15hrs<br>
Página web: 6hrs<br>
Testes: 3hrs

---

<b>Pergunta</b>: Caso não tenha experiência com alguma ferramenta, explique como você aprenderia ou resolveria a demanda.

<b>R</b>: Algumas tecnologias propostas no teste, não tenho experiência, mas consegui entregar e fazer tarefas básicas com o conhecimento em geral que tenho com a programação. Para realmente aprender uma tecnologia nova, faria cursos com Udemy, Alura e participaria de comunidades da stack para ver projetos reais.
