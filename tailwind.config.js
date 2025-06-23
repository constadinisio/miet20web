module.exports = {
  content: [
    "./*.php",
    "./**/*.php",
    "./*.html",
    "./**/*.html",
  ],
  theme: {
    extend: {
      backgroundImage: {
        'front-et20': "url('./images/front_et20.jpg')",
        'demo-tics': "url('./images/ing_tics.webp')",
        'demo-mult': "url('./images/set_mult.jpg')",
        'cat-eventos': "url('./images/categoria1.jpg')",
        'cat-especialidad': "url('./images/categoria2.jpg')",
        'cat-talleres': "url('./images/categoria3.jpg')",
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
  plugins: [require('@tailwindcss/typography')]
};
