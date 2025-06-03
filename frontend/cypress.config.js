import { defineConfig } from "cypress";

export default defineConfig({
  e2e: {
    baseUrl: "http://localhost:5173",
    specPattern: "cypress/integration/**/*.js",
    supportFile: "cypress/support/index.js",
    fixturesFolder: "cypress/fixtures",
    viewportWidth: 1280,
    viewportHeight: 720,
    video: false
  },
  component: {
    devServer: {
      framework: "vue",
      bundler: "vite"
    }
  }
});