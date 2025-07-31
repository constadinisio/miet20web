const plugin = require('@tailwindcss/typography');

module.exports = {
  content: [
    "./public/**/*.php",
    "./public/**/*.html",
    "./public/**/*.js"
  ],
  theme: {
    extend: {
      backgroundImage: {
        'front-et20': "url('/images/front_et20.webp')",
        'demo-tics': "url('/images/ing_tics.webp')",
        'demo-mult': "url('/images/set_mult.webp')",
        'cat-eventos': "url('/images/categoria1.webp')",
        'cat-especialidad': "url('/images/categoria2.webp')",
        'cat-talleres': "url('/images/categoria3.webp')",
      },
      colors: {
        azulInstitucional: '#376bb1',
        rojoDestacado: '#b32a32',
        verdeEsperanza: '#3faa3e',
        amarilloEnergia: '#f4c822',
        rojo: '#ff0000',
        rosaMagico: '#e11d48',
      }
    },
  },
  plugins: [plugin],
};