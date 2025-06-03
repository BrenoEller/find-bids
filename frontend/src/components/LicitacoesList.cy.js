import LicitacoesList from './LicitacoesList.vue'

describe('<LicitacoesList />', () => {
  it('renders', () => {
    // see: https://on.cypress.io/mounting-vue
    cy.mount(LicitacoesList)
  })
})