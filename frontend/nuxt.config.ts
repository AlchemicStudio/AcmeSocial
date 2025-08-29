// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },

  modules: [
    '@nuxt/eslint',
    '@nuxt/fonts',
    '@nuxt/icon',
    '@nuxt/image',
    '@nuxt/scripts',
    '@nuxt/test-utils',
    '@pinia/nuxt',
    'vuetify-nuxt-module',
    'nuxt-auth-sanctum',
    '@nuxtjs/i18n'
  ],

  sanctum: {
      baseUrl: 'http://localhost:8000', // Laravel API
  },
})