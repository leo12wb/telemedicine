import pluginVue from 'eslint-plugin-vue'
import tseslint from 'typescript-eslint'
import vueParser from 'vue-eslint-parser'

export default tseslint.config(
  {
    ignores: ['public/**', 'vendor/**', 'node_modules/**'],
  },

  // TypeScript rules for .ts files
  {
    files: ['resources/js/**/*.ts'],
    extends: [...tseslint.configs.recommended],
  },

  // Vue files: vue-eslint-parser as outer parser, tseslint.parser inside <script>
  {
    files: ['resources/js/**/*.vue'],
    extends: [...pluginVue.configs['flat/recommended'], ...tseslint.configs.recommended],
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: tseslint.parser,
        extraFileExtensions: ['.vue'],
      },
    },
    rules: {
      // ─── Regras semânticas desligadas intencionalmente ───────────────────
      // Componentes de página com nome único são aceitáveis (DashboardPage, etc.)
      'vue/multi-word-component-names': 'off',
      // Componentes inline (AppointmentList em DashboardPage) são intencionais
      'vue/one-component-per-file': 'off',

      // ─── Regras de formatação: delegadas ao padrão existente do projeto ──
      // O estilo compacto atual é consistente; não forçar quebras de linha cosméticas
      'vue/singleline-html-element-content-newline': 'off',
      'vue/multiline-html-element-content-newline': 'off',
      'vue/max-attributes-per-line': 'off',
      'vue/first-attribute-linebreak': 'off',
      'vue/html-closing-bracket-newline': 'off',
      'vue/attributes-order': 'off',
      // <input /> self-closing é aceitável em Vue (JSX-like)
      'vue/html-self-closing': 'off',
    },
  },

  // Shared rules for all JS/TS/Vue files
  {
    files: ['resources/js/**/*.{ts,vue}'],
    rules: {
      '@typescript-eslint/no-explicit-any': 'warn',
    },
  },
)
