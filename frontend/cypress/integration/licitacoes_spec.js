describe('Página de Licitações', () => {
  beforeEach(() => {
    cy.intercept('GET', '/api/licitacoes?pagina=*', { fixture: 'licitacoes_page1.json' }).as('getLicitacoes')
    cy.intercept('GET', '/api/licitacoes/uasg/000000', {
      statusCode: 404,
      body: { error: 'Nenhuma licitação encontrada para UASG “000000”.' }
    }).as('getUasgNotFound')
    cy.intercept('GET', '/api/licitacoes/uasg/250005', { fixture: 'licitacoes_page1.json' }).as('getUasgFound')
    cy.intercept('GET', '/api/licitacoes/pregao*', { fixture: 'licitacoes_page1.json' }).as('getPregao')
    cy.visit('/')
    cy.wait('@getLicitacoes')
  })

  it('faz busca por UASG e exibe mensagem “Nenhuma licitação encontrada” se não existir', () => {
    cy.get('input[placeholder="Código UASG"]').type('000000')
    cy.contains('button', 'Buscar UASG').click()
    cy.wait('@getUasgNotFound')
    cy.contains('Nenhuma licitação encontrada para UASG “000000”').should('be.visible')
  })

  it('faz busca por UASG e exibe resultados se encontrar', () => {
    cy.get('input[placeholder="Código UASG"]').type('250005')
    cy.contains('button', 'Buscar UASG').click()
    cy.wait('@getUasgFound')
    cy.get('.card').should('have.length.at.least', 1)
    cy.contains('UASG: 250005').should('be.visible')
  })

  it('faz busca por número do pregão e exibe resultados', () => {
    cy.get('input[placeholder="Número (ou parte) do Pregão"]').type('90007')
    cy.contains('button', 'Buscar Pregão').click()
    cy.wait('@getPregao')
    cy.get('.card').should('have.length.at.least', 1)
    cy.contains('Pregão Eletrônico Nº 90007/2025').should('be.visible')
  })

  it('faz busca por número do pregão e exibe mensagem se não encontrar', () => {
    cy.intercept('GET', '/api/licitacoes/pregao?numero=00000', {
      statusCode: 404,
      body: { error: 'Nenhum pregão contendo “00000” encontrado.' }
    }).as('getPregaoNotFound')
    cy.get('input[placeholder="Número (ou parte) do Pregão"]').type('00000')
    cy.contains('button', 'Buscar Pregão').click()
    cy.wait('@getPregaoNotFound')
    cy.contains('Nenhum pregão contendo “00000” encontrado.').should('be.visible')
  })

  it('navega para próxima página e atualiza a URL', () => {
    cy.intercept('GET', '/api/licitacoes?pagina=2', { fixture: 'licitacoes_empty.json' }).as('getLicitacoesPage2')
    cy.contains('button', 'Próximo').should('exist').click()
    cy.wait('@getLicitacoesPage2')
    cy.url().should('include', '?pagina=2')
  })
})