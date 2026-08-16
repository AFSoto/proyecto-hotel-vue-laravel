# Imagen base: Node 22 (tu package.json pide ">=22.12"). Variante slim (Debian/glibc)
# por máxima compatibilidad con los binarios nativos de Vite/Tailwind/esbuild.
FROM node:22-slim

WORKDIR /app

# Copiamos primero SOLO los manifiestos para aprovechar la caché de capas de Docker:
# mientras package.json / package-lock.json no cambien, no se reinstala en cada build.
COPY package.json package-lock.json ./

# Instalación de dependencias. Usamos `npm install` (no `npm ci`) porque el
# package-lock.json del repo está desincronizado con package.json; `npm ci`
# exige sincronía exacta y falla. Si algún día se regenera el lock en el host
# (npm install + commit), se puede volver a `npm ci` para builds reproducibles.
RUN npm install

# El resto del código se monta por volumen en desarrollo (ver docker-compose.yml).
# Puerto del servidor de desarrollo de Vite.
EXPOSE 5173

# --host hace que Vite escuche en 0.0.0.0; sin él solo escucharía dentro del
# contenedor y no lo verías desde Windows en http://localhost:5173.
CMD ["npm", "run", "dev", "--", "--host"]
