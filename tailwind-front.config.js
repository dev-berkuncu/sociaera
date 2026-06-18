/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/*.php",
    "./public/partials/**/*.php",
    "./app/**/*.php"
  ],
  darkMode: "class",
  theme: {
    extend: {
      "colors": {
          "on-error": "#690005",
          "surface-bright": "#31394d",
          "on-secondary": "#263143",
          "on-tertiary-container": "#00364b",
          "on-secondary-container": "#aeb9d0",
          "tertiary": "#7bd0ff",
          "on-secondary-fixed-variant": "#3c475a",
          "error": "#ffb4ab",
          "surface-container-highest": "#353436",
          "on-primary-fixed": "#390c00",
          "outline": "#a98a80",
          "on-surface-variant": "#e1bfb5",
          "on-primary-container": "#5f1900",
          "tertiary-fixed": "#c4e7ff",
          "surface-container-low": "#1c1b1c",
          "on-error-container": "#ffdad6",
          "surface-container-high": "#2a2a2b",
          "on-surface": "#e5e2e3",
          "primary": "#ffb97c",
          "tertiary-fixed-dim": "#7bd0ff",
          "inverse-surface": "#e5e2e3",
          "inverse-on-surface": "#283044",
          "inverse-primary": "#ab3500",
          "error-container": "#93000a",
          "secondary": "#bcc7de",
          "background": "#131314",
          "surface-dim": "#131314",
          "surface-container": "#201f20",
          "secondary-fixed-dim": "#bcc7de",
          "on-tertiary-fixed-variant": "#004c69",
          "on-tertiary": "#00354a",
          "on-primary": "#5d1900",
          "primary-fixed": "#ffdbd0",
          "tertiary-container": "#00a5de",
          "surface-container-lowest": "#0e0e0f",
          "primary-container": "#ff9100",
          "outline-variant": "#594139",
          "surface-tint": "#ffb97c",
          "primary-fixed-dim": "#ffb97c",
          "on-secondary-fixed": "#111c2d",
          "on-tertiary-fixed": "#001e2c",
          "surface": "#131314",
          "secondary-container": "#3e495d",
          "secondary-fixed": "#d8e3fb",
          "on-background": "#e5e2e3",
          "on-primary-fixed-variant": "#832600",
          "surface-variant": "#353436"
      },
      "borderRadius": {
          "DEFAULT": "0.25rem",
          "lg": "0.5rem",
          "xl": "0.75rem",
          "full": "9999px"
      },
      "spacing": {
          "stack-md": "24px",
          "stack-lg": "48px",
          "base": "8px",
          "gutter": "24px",
          "container-padding": "32px",
          "stack-sm": "12px"
      },
      "fontFamily": {
          "body-md": ["Inter"],
          "label-sm": ["Inter"],
          "display-lg": ["Manrope"],
          "headline-md": ["Manrope"],
          "body-lg": ["Inter"],
          "label-md": ["Inter"],
          "headline-lg": ["Manrope"]
      },
      "fontSize": {
          "body-md": ["16px", { "lineHeight": "1.6", "fontWeight": "400" }],
          "label-sm": ["12px", { "lineHeight": "1.2", "letterSpacing": "0.05em", "fontWeight": "600" }],
          "display-lg": ["48px", { "lineHeight": "1.2", "letterSpacing": "-0.02em", "fontWeight": "700" }],
          "headline-md": ["24px", { "lineHeight": "1.4", "fontWeight": "600" }],
          "body-lg": ["18px", { "lineHeight": "1.6", "fontWeight": "400" }],
          "label-md": ["14px", { "lineHeight": "1.2", "letterSpacing": "0.01em", "fontWeight": "500" }],
          "headline-lg": ["32px", { "lineHeight": "1.3", "letterSpacing": "-0.01em", "fontWeight": "600" }]
      },
      "animation": {
          "float-fast": "float 4s ease-in-out infinite",
          "float": "float 5s ease-in-out infinite",
          "float-slow": "float 6s ease-in-out infinite"
      },
      "keyframes": {
          "float": {
              "0%, 100%": { transform: "translateY(0)" },
              "50%": { transform: "translateY(-15px)" }
          }
      }
    }
  }
}
