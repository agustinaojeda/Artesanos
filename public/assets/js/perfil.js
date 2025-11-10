// Funciones globales para manejo de fechas (disponibles antes de DOMContentLoaded)
// Convierte "YYYY-MM-DD HH:MM:SS" (MySQL) o "YYYY-MM-DDTHH:MM:SS" a milisegundos
function parseMySQLDateToMs(mysqlDate) {
  if (!mysqlDate || typeof mysqlDate !== "string") return NaN;
  // Limpiar por si trae microsegundos u otros caracteres
  mysqlDate = mysqlDate.trim().replace(/\.\d+$/, ""); // quita .000000 si existiera

  // Acepta "YYYY-MM-DD HH:MM:SS" o "YYYY-MM-DDTHH:MM:SS"
  const parts = mysqlDate.split(/[\sT]/);
  if (parts.length < 2) return NaN;

  const fechaPart = parts[0].split("-");
  const horaPart = parts[1].split(":");
  if (fechaPart.length !== 3 || horaPart.length < 2) return NaN;

  const year = parseInt(fechaPart[0], 10);
  const month = parseInt(fechaPart[1], 10) - 1; // JS month 0-11
  const day = parseInt(fechaPart[2], 10);
  const hour = parseInt(horaPart[0], 10);
  const minute = parseInt(horaPart[1], 10);
  const second = horaPart.length > 2 ? parseInt(horaPart[2], 10) : 0;

  // Crea la fecha en zona local (evita problemas de interpretación automática)
  return new Date(year, month, day, hour, minute, second).getTime();
}

function tiempoRelativo(fech, ahoraMs = Date.now()) {
  if (!fech) return "fecha inválida";

  // Si ya es number, usarlo; si es string MySQL, parsearlo
  const fechaMs = typeof fech === "number" ? fech : parseMySQLDateToMs(fech);

  if (!isFinite(fechaMs)) {
    console.warn("Fecha inválida recibida:", fech);
    return "fecha inválida";
  }

  let diffMs = ahoraMs - fechaMs;
  if (diffMs < 0) diffMs = 0;

  const diffSeg = Math.floor(diffMs / 1000);
  const diffMin = Math.floor(diffSeg / 60);
  const diffHoras = Math.floor(diffMin / 60);
  const diffDias = Math.floor(diffHoras / 24);

  if (diffSeg < 60) return `hace ${diffSeg} segundos`;
  if (diffMin < 60) return `hace ${diffMin} minutos`;
  if (diffHoras < 24) return `hace ${diffHoras} horas`;
  if (diffDias === 1) return `ayer`;
  if (diffDias < 30) return `hace ${diffDias} días`;

  const diffMeses = Math.floor(diffDias / 30);
  if (diffMeses < 12) return `hace ${diffMeses} meses`;

  const diffAnios = Math.floor(diffMeses / 12);
  return `hace ${diffAnios} años`;
}

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("modalDetalleAlbum");
  const modalLabel = document.getElementById("modalDetalleAlbumLabel");
  const modalBodyIzq = document.getElementById("detalleAlbumIzquierda");
  const modalBodyDer = document.getElementById("detalleAlbumDerecha");
  const fotoPerfil = document.getElementById("modalFotoPerfil");


  function joinUrl(base, path) {
    const b = String(base || '').replace(/\/+$/, '');
    const p = String(path || '').replace(/^\/+/, '');
    return `${b}/${p}`;
  }

  document.querySelectorAll(".album-card").forEach((card) => {
    // Ignorar cards de "Me gusta" que tienen data-tipo="likes-usuario"
    if (card.dataset.tipo === 'likes-usuario') {
      return; // Esta card se maneja con onclick directo
    }
    
    card.addEventListener("click", async () => {
      const albumId = card.dataset.id;

      modalLabel.innerHTML = `<img src='${window.BASE_URL}/assets/images/logo.png' width='28' class='me-2'> Cargando álbum...`;
      modalBodyIzq.innerHTML = `<p class='text-center py-5'>Cargando imágenes...</p>`;
      modalBodyDer.innerHTML = `<p class='text-center py-5'>Cargando datos...</p>`;
      fotoPerfil.src = "";

      const url = joinUrl(window.BASE_URL, `api/detalleAlbum?id=${encodeURIComponent(albumId)}`);

      try {
        const res = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
        const ctype = res.headers.get('content-type') || '';
        // Leemos SIEMPRE como texto para inspeccionar si viene HTML
        const raw = await res.text();

        if (!res.ok) {
          throw new Error(`HTTP ${res.status} — body: ${raw.slice(0,200)}`);
        }

        // Parseo seguro a JSON
        let data;
        try {
          data = JSON.parse(raw);
        } catch (e) {
          console.error('[detalleAlbum] JSON parse error:', e);
          // si el body empieza con <, probablemente es HTML de error o login
          if (raw.trim().startsWith('<')) {
            console.warn('[detalleAlbum] Parece HTML. ¿PHP tiró warnings? ¿Redirigió al login?');
          }
          modalLabel.textContent = "Error de formato";
          modalBodyIzq.innerHTML = `<pre class="p-3 text-danger" style="white-space:pre-wrap; max-height:300px; overflow:auto;">${raw.slice(0,1000)}</pre>`;
          modalBodyDer.innerHTML = "";
          return;
        }

        if (data.error) {
          modalBodyIzq.innerHTML = `<p class='text-danger text-center py-5'>${data.error}</p>`;
          modalBodyDer.innerHTML = "";
          modalLabel.textContent = "Error";
          return;
        }

        // Mostrar datos
        let fechaRelativa = tiempoRelativo(data.fecha);
        const perfilUrl = `${window.BASE_URL}/perfil?id=${data.idUsuario}`;
        modalLabel.innerHTML = `
          <div class="d-flex flex-column">
            <div class="d-flex align-items-center gap-2">
              <a href="${perfilUrl}" style="text-decoration: none; color: inherit; cursor: pointer;">
                <h4 class="mb-0"><strong style="cursor: pointer;">${data.apodo}</strong></h4>
              </a>
              <a href="${perfilUrl}" style="text-decoration: none; color: inherit; cursor: pointer;">
                <div class="text-muted fw-light"><small style="cursor: pointer;"> - @${data.usuario}</small></div>
              </a>
            </div>
            <div class="text-muted mt-1 fw-light" style="font-size: 0.9rem;">${fechaRelativa}</div>
          </div>`;

        fotoPerfil.src = data.fotoPerfil;
        fotoPerfil.style.cursor = "pointer";
        fotoPerfil.onclick = () => { window.location.href = perfilUrl; };
        
        const btnSeguir = document.getElementById("btnSeguir");
        if (btnSeguir) {
          // Si el usuario actual es el propietario del álbum, ocultar el botón
          if (data.esPropietario) {
            btnSeguir.style.display = 'none';
          } else {
            btnSeguir.style.display = '';
            btnSeguir.setAttribute("data-id", data.idUsuario);
            btnSeguir.setAttribute("data-id-seguido", data.idUsuario);
            
            // Verificar y actualizar estado del botón seguir después de un pequeño delay
            setTimeout(() => {
              verificarEstadoSeguirModal(btnSeguir, data.idUsuario);
            }, 100);
          }
        }

        // Mostrar/ocultar menú de denunciar según si es propietario
        const dropdownDenunciar = document.getElementById("dropdownDenunciarAlbum");
        if (dropdownDenunciar) {
          if (data.esPropietario) {
            dropdownDenunciar.style.display = 'none';
          } else {
            dropdownDenunciar.style.display = '';
          }
        }
        
        modalBodyIzq.innerHTML = data.izquierda;
        modalBodyDer.innerHTML = data.derecha;
        
        // Corregir aria-hidden después de cargar el contenido
        setTimeout(() => {
          if (modal.classList.contains('show')) {
            modal.removeAttribute('aria-hidden');
            modal.setAttribute('aria-modal', 'true');
          }
        }, 200);

        // Comentarios y likes iniciales en modal
        setTimeout(() => {
          const btnEnviar = document.getElementById("btnEnviarComentario");
          const inputComentario = document.getElementById("inputComentario");
          const carrusel = document.getElementById("carouselAlbum");
          const listaComentarios = document.getElementById("listaComentarios");
          const btnLikeModal = document.getElementById("btn-like-imagen");
          const countModal = document.getElementById("likes-count-display");

          function mostrarComentarios(idImg) {
            const comentarios = data.comentarios[idImg] || [];
            listaComentarios.innerHTML = comentarios
              .map(
                (c) => `
              <div class="d-flex gap-2 mb-3" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px;">
                <img src="${c.avatar}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                <div>
                  <strong>${c.apodo}</strong><br>
                  <p class="mb-0">${c.mensaje}</p>
                </div>
              </div>`
              )
              .join("");
          }

          async function actualizarInfoImagen() {
            if (!carrusel) return;
            const activo = carrusel.querySelector(".carousel-item.active");
            if (!activo) return;
            const titulo = activo?.dataset.titulo || "";
            const descripcion = activo?.dataset.descripcion || "";
            const idImagen = activo?.dataset.idimagen;
            const tituloEl = document.getElementById("tituloImagen");
            const descEl = document.getElementById("descripcionImagen");
            if (tituloEl) tituloEl.textContent = titulo;
            if (descEl) descEl.textContent = descripcion;
            if (btnEnviar && idImagen) btnEnviar.setAttribute("data-idimagen", idImagen);
            if (idImagen) mostrarComentarios(idImagen);

            // Actualizar estado y conteo de like del modal para la imagen activa
            if (btnLikeModal && idImagen) {
              btnLikeModal.dataset.idimagen = idImagen;
              try {
                const resp = await fetch(`${window.BASE_URL}/api/obtenerLikes?idImagen=${encodeURIComponent(idImagen)}`);
                const likeData = await resp.json();
                if (resp.ok && likeData.totalLikes !== undefined) {
                  if (countModal) countModal.textContent = likeData.totalLikes;
                  btnLikeModal.src = likeData.likedByUser
                    ? `${window.BASE_URL}/assets/images/likelleno.png`
                    : `${window.BASE_URL}/assets/images/like.png`;
                }
              } catch (err) {
                console.warn("No se pudieron cargar likes en modal para imagen", idImagen, err);
              }
            }
          }

          if (btnEnviar && inputComentario) {
            btnEnviar.addEventListener("click", () => {
              const idImagen = btnEnviar.getAttribute("data-idimagen");
              const mensaje = inputComentario.value.trim();
              if (!mensaje) return;

              fetch(`${window.BASE_URL}/api/agregarComentario`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ idImagen, mensaje }),
              })
                .then((res) => res.json())
                .then((res) => {
                  if (res.ok) {
                    const nuevo = `
                      <div class="d-flex gap-2 mb-3">
                        <img src="${res.avatar}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                        <div>
                          <strong>${res.apodo}</strong><br>
                          <p class="mb-0">${res.mensaje}</p>
                        </div>
                      </div>`;
                    listaComentarios.insertAdjacentHTML("beforeend", nuevo);
                    inputComentario.value = "";

                    if (!data.comentarios[idImagen]) data.comentarios[idImagen] = [];
                    data.comentarios[idImagen].push({
                      apodo: res.apodo,
                      avatar: res.avatar,
                      mensaje: res.mensaje,
                      fecha: new Date().toISOString(),
                    });
                  }
                });
            });
          }

          if (carrusel) {
          actualizarInfoImagen();
          carrusel.addEventListener("slid.bs.carousel", actualizarInfoImagen);
          }
        }, 150);
      } catch (err) {
        console.error("Error al cargar álbum:", err);
        modalLabel.textContent = "Error de carga";
        modalBodyIzq.innerHTML = `<p class='text-danger text-center py-5'>No se pudo cargar el álbum.</p>`;
        modalBodyDer.innerHTML = "";
      }
    });
  });

  // ===== Inicializar conteos y estado del corazón en tarjetas (álbumes) =====
  document.querySelectorAll(".btn-like-galeria").forEach(async (el) => {
    const idAlbum = el.dataset.idalbum;
    if (!idAlbum) return;
    try {
      const resp = await fetch(`${window.BASE_URL}/api/obtenerLikes?idAlbum=${idAlbum}`);
      const data = await resp.json();
      if (resp.ok && data.totalLikes !== undefined) {
        // Actualiza el contador adyacente para evitar colisiones de IDs en distintas secciones
        const contador = el.nextElementSibling || el.parentElement?.querySelector(`span[id="likes-count-album-${idAlbum}"]`);
        if (contador) contador.textContent = data.totalLikes;
        el.src = data.likedByUser
          ? `${window.BASE_URL}/assets/images/likelleno.png`
          : `${window.BASE_URL}/assets/images/like.png`;
      }
    } catch (err) {
      console.warn("No se pudieron cargar likes iniciales para álbum", idAlbum, err);
    }
  });

  // ===== Inicializar conteos y estado del corazón en tarjetas (imágenes) =====
  document.querySelectorAll(".btn-like-imagen-perfil").forEach(async (el) => {
    const idImagen = el.dataset.idimagen;
    if (!idImagen) return;
    try {
      const resp = await fetch(`${window.BASE_URL}/api/obtenerLikes?idImagen=${encodeURIComponent(idImagen)}`);
      const data = await resp.json();
      if (resp.ok && data.totalLikes !== undefined) {
        const contador = document.getElementById(`likes-count-image-${idImagen}`);
        if (contador) contador.textContent = data.totalLikes;
        el.src = data.likedByUser
          ? `${window.BASE_URL}/assets/images/likelleno.png`
          : `${window.BASE_URL}/assets/images/like.png`;
      }
    } catch (err) {
      console.warn("No se pudieron cargar likes iniciales para imagen", idImagen, err);
    }
  });

  // ===== Manejo de clics de like (delegado) para evitar abrir el modal =====
  document.addEventListener("click", async (e) => {
    // Like del modal de imagen
    const btnLikeModal = e.target.closest("#btn-like-imagen");
    if (btnLikeModal) {
      e.preventDefault();
      e.stopPropagation();
      const idImagen = btnLikeModal.dataset.idimagen;
      if (!idImagen) return;
      try {
        const resp = await fetch(`${window.BASE_URL}/api/megusta`, {
          method: "POST",
          body: new URLSearchParams({ idImagen })
        });
        const data = await resp.json();
        if (resp.ok && data.totalLikes !== undefined) {
          const contadorModal = document.getElementById("likes-count-display");
          if (contadorModal) contadorModal.textContent = data.totalLikes;
          btnLikeModal.src = data.accion === "like"
            ? `${window.BASE_URL}/assets/images/likelleno.png`
            : `${window.BASE_URL}/assets/images/like.png`;
        } else {
          console.error("Error al registrar el like de imagen (modal):", data.error || "Respuesta inesperada");
        }
      } catch (err) {
        console.error("Error en el fetch de like de imagen (modal):", err);
      }
      return;
    }

    const btnAlbum = e.target.closest(".btn-like-galeria");
    if (btnAlbum) {
      e.preventDefault();
      e.stopPropagation();
      const idAlbum = btnAlbum.dataset.idalbum;
      if (!idAlbum) return;
      try {
        const resp = await fetch(`${window.BASE_URL}/api/megusta`, {
          method: "POST",
          body: new URLSearchParams({ idAlbum })
        });
        const data = await resp.json();
        if (resp.ok && data.totalLikes !== undefined) {
          const contador = btnAlbum.nextElementSibling || btnAlbum.parentElement?.querySelector(`span[id="likes-count-album-${idAlbum}"]`);
          if (contador) contador.textContent = data.totalLikes;
          btnAlbum.src = data.accion === "like"
            ? `${window.BASE_URL}/assets/images/likelleno.png`
            : `${window.BASE_URL}/assets/images/like.png`;
        } else {
          console.error("Error al registrar el like de álbum:", data.error || "Respuesta inesperada");
        }
      } catch (err) {
        console.error("Error en el fetch de like de álbum:", err);
      }
      return;
    }

    const btnImg = e.target.closest(".btn-like-imagen-perfil");
    if (btnImg) {
      e.preventDefault();
      e.stopPropagation();
      const idImagen = btnImg.dataset.idimagen;
      if (!idImagen) return;
      try {
        const resp = await fetch(`${window.BASE_URL}/api/megusta`, {
          method: "POST",
          body: new URLSearchParams({ idImagen })
        });
        const data = await resp.json();
        if (resp.ok && data.totalLikes !== undefined) {
          const contador = document.getElementById(`likes-count-image-${idImagen}`);
          if (contador) contador.textContent = data.totalLikes;
          btnImg.src = data.accion === "like"
            ? `${window.BASE_URL}/assets/images/likelleno.png`
            : `${window.BASE_URL}/assets/images/like.png`;
        } else {
          console.error("Error al registrar el like de imagen:", data.error || "Respuesta inesperada");
        }
      } catch (err) {
        console.error("Error en el fetch de like de imagen:", err);
      }
      return;
    }
  });

  // Funcionalidad del botón Seguir en el modal
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('#btnSeguir');
    if (!btn) return;

    const idSeguido = btn.dataset.idSeguido || btn.dataset.idseguido || btn.dataset.id;
    if (!idSeguido) return;

    // Si ya sigue o está pendiente → dejar de seguir
    if (btn.classList.contains('btn-success') || btn.classList.contains('btn-secondary')) {
      fetch(`${window.BASE_URL}/api/dejarSeguir`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `idSeguido=${encodeURIComponent(idSeguido)}`
      })
      .then(res => res.text())
      .then(data => {
        if (data.trim() === 'ok') {
          actualizarBotonSeguirModal(btn, 'ninguno');
        } else {
          alert('Error al dejar de seguir: ' + data);
        }
      })
      .catch(err => console.error(err));
      return;
    }

    // Si no sigue → redirigir al perfil (igual que en la página de perfil)
    const perfilUrl = `${window.BASE_URL}/perfil?id=${idSeguido}`;
    window.location.href = perfilUrl;
  });

  // Polling para actualizar estado del botón seguir en el modal cada 5 segundos
  setInterval(() => {
    const btn = document.querySelector('#btnSeguir');
    if (!btn) return;
    
    const idSeguido = btn.dataset.idSeguido || btn.dataset.idseguido || btn.dataset.id;
    if (!idSeguido) return;
    
    verificarEstadoSeguirModal(btn, idSeguido);
  }, 5000);
}); // Cierre del DOMContentLoaded

// Función para verificar el estado de seguimiento en el modal (disponible globalmente)
function verificarEstadoSeguirModal(btn, idSeguido) {
  if (!btn || !idSeguido) return;
  
  fetch(`${window.BASE_URL}/api/checkFollowStatus`, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `idSeguido=${encodeURIComponent(idSeguido)}`
  })
  .then(res => res.text())
  .then(status => {
    const estado = status.trim();
    if (['activo', 'aceptado'].includes(estado)) {
      actualizarBotonSeguirModal(btn, 'siguiendo');
    } else if (estado === 'pendiente') {
      actualizarBotonSeguirModal(btn, 'pendiente');
    } else {
      actualizarBotonSeguirModal(btn, 'ninguno');
    }
  })
  .catch(err => console.error(err));
}

// Función para actualizar el botón seguir en el modal (disponible globalmente)
function actualizarBotonSeguirModal(btn, estado) {
  if (!btn) return;
  btn.classList.remove('btn-outline-primary', 'btn-success', 'btn-secondary');
  switch (estado) {
    case 'siguiendo':
    case 'activo':
      btn.classList.add('btn-success');
      btn.innerHTML = '<i class="bi bi-check2 me-2"></i> Siguiendo';
      break;
    case 'pendiente':
      btn.classList.add('btn-secondary');
      btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Pendiente';
      break;
    default:
      btn.classList.add('btn-outline-primary');
      btn.innerHTML = 'Seguir';
  }
}

// Función para cargar el detalle del álbum virtual de "Me gusta" (disponible globalmente)
window.cargarDetalleLikesUsuario = async function(idUsuario) {
  console.log('cargarDetalleLikesUsuario llamado con idUsuario:', idUsuario);
  
  if (!idUsuario) {
    console.error('ID de usuario no proporcionado');
    alert('Error: ID de usuario no proporcionado');
    return;
  }

  const modal = document.getElementById("modalDetalleAlbum");
  const modalLabel = document.getElementById("modalDetalleAlbumLabel");
  const modalBodyIzq = document.getElementById("detalleAlbumIzquierda");
  const modalBodyDer = document.getElementById("detalleAlbumDerecha");
  const fotoPerfil = document.getElementById("modalFotoPerfil");

  if (!modal) {
    console.error('Modal no encontrado');
    alert('Error: Modal no encontrado');
    return;
  }
  
  if (!modalLabel || !modalBodyIzq || !modalBodyDer) {
    console.error('Elementos del modal no encontrados', { modalLabel, modalBodyIzq, modalBodyDer });
    alert('Error: Elementos del modal no encontrados');
    return;
  }

  // Mostrar modal con estado de carga
  modalLabel.innerHTML = `<img src='${window.BASE_URL}/assets/images/logo.png' width='28' class='me-2'> Cargando contenido...`;
  modalBodyIzq.innerHTML = `<p class='text-center py-5'>Cargando imágenes...</p>`;
  modalBodyDer.innerHTML = `<p class='text-center py-5'>Cargando datos...</p>`;
  if (fotoPerfil) fotoPerfil.src = "";

  // Abrir modal usando Bootstrap
  try {
    const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
    bsModal.show();
    // Corregir aria-hidden después de que el modal se muestre
    setTimeout(() => {
      if (modal.classList.contains('show')) {
        modal.removeAttribute('aria-hidden');
        modal.setAttribute('aria-modal', 'true');
      }
    }, 100);
    console.log('Modal abierto');
  } catch (e) {
    console.error('Error al abrir modal:', e);
    // Fallback: usar jQuery si Bootstrap no está disponible
    $(modal).modal('show');
  }

  const url = `${window.BASE_URL}/api/detalleLikesUsuario?idUsuario=${encodeURIComponent(idUsuario)}`;
  console.log('Llamando a:', url);

  try {
    const res = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
    const raw = await res.text();
    console.log('Respuesta HTTP:', res.status, res.statusText);
    console.log('Respuesta raw (primeros 500 chars):', raw.slice(0, 500));

    if (!res.ok) {
      console.error('Error HTTP:', res.status, raw);
      throw new Error(`HTTP ${res.status} — ${raw.slice(0,200)}`);
    }

    let data;
    try {
      data = JSON.parse(raw);
      console.log('JSON parseado correctamente');
    } catch (e) {
      console.error('[detalleLikesUsuario] JSON parse error:', e);
      console.error('Raw response:', raw);
      modalLabel.textContent = "Error de formato";
      modalBodyIzq.innerHTML = `<pre class="p-3 text-danger" style="white-space:pre-wrap; max-height:300px; overflow:auto;">${raw.slice(0,1000)}</pre>`;
      modalBodyDer.innerHTML = "";
      return;
    }

    if (data.error) {
      modalBodyIzq.innerHTML = `<p class='text-danger text-center py-5'>${data.error}</p>`;
      modalBodyDer.innerHTML = "";
      modalLabel.textContent = "Error";
      return;
    }

    // Mostrar datos
    console.log('Datos recibidos:', data);
    
    // Validar que los datos necesarios estén presentes
    if (!data.apodo || !data.usuario || !data.idUsuario) {
      console.error('Datos incompletos:', data);
      modalLabel.textContent = "Error: Datos incompletos";
      modalBodyIzq.innerHTML = `<p class='text-danger text-center py-5'>Error: No se pudieron cargar los datos del usuario.</p>`;
      modalBodyDer.innerHTML = "";
      return;
    }
    
    // Usar fechaUnix si está disponible, sino usar fecha
    let fechaRelativa = 'hace un momento';
    if (data.fechaUnix) {
      fechaRelativa = tiempoRelativo(data.fechaUnix, Date.now());
    } else if (data.fecha) {
      fechaRelativa = tiempoRelativo(data.fecha);
    }
    
    const perfilUrl = `${window.BASE_URL}/perfil?id=${data.idUsuario}`;
    modalLabel.innerHTML = `
      <div class="d-flex flex-column">
        <div class="d-flex align-items-center gap-2">
          <a href="${perfilUrl}" style="text-decoration: none; color: inherit; cursor: pointer;">
            <h4 class="mb-0"><strong style="cursor: pointer;">${data.apodo || 'Usuario'}</strong></h4>
          </a>
          <a href="${perfilUrl}" style="text-decoration: none; color: inherit; cursor: pointer;">
            <div class="text-muted fw-light"><small style="cursor: pointer;"> - @${data.usuario || 'usuario'}</small></div>
          </a>
        </div>
        <div class="text-muted mt-1 fw-light" style="font-size: 0.9rem;">${fechaRelativa}</div>
      </div>`;

    fotoPerfil.src = data.fotoPerfil;
    fotoPerfil.style.cursor = "pointer";
    fotoPerfil.onclick = () => { window.location.href = perfilUrl; };
    
    const btnSeguir = document.getElementById("btnSeguir");
    if (btnSeguir) {
      // Si el usuario actual es el propietario, ocultar el botón
      if (data.esPropietario) {
        btnSeguir.style.display = 'none';
      } else {
        btnSeguir.style.display = '';
        btnSeguir.setAttribute("data-id", data.idUsuario);
        btnSeguir.setAttribute("data-id-seguido", data.idUsuario);
        
        setTimeout(() => {
          verificarEstadoSeguirModal(btnSeguir, data.idUsuario);
        }, 100);
      }
    }

    // Mostrar/ocultar menú de denunciar según si es propietario (en "Me gusta" siempre será false)
    const dropdownDenunciar = document.getElementById("dropdownDenunciarAlbum");
    if (dropdownDenunciar) {
      if (data.esPropietario) {
        dropdownDenunciar.style.display = 'none';
      } else {
        dropdownDenunciar.style.display = '';
      }
    }
    
    modalBodyIzq.innerHTML = data.izquierda;
    modalBodyDer.innerHTML = data.derecha;

    // Inicializar carrusel y funcionalidades
    setTimeout(() => {
      const carrusel = document.getElementById(data.carruselId || "carouselAlbumVirtual");
      const btnEnviar = document.getElementById("btnEnviarComentario");
      const inputComentario = document.getElementById("inputComentario");
      const listaComentarios = document.getElementById("listaComentarios");
      const btnLikeModal = document.getElementById("btn-like-imagen");
      const countModal = document.getElementById("likes-count-display");

      // Inicializar carrusel
      if (carrusel) {
        try {
          new bootstrap.Carousel(carrusel, { ride: false });
        } catch (e) {
          console.warn('Error al inicializar carrusel:', e);
        }

        // Actualizar info cuando cambia la imagen
        const actualizarInfoImagen = () => {
          const activo = carrusel.querySelector('.carousel-item.active');
          if (!activo) return;

          const tituloEl = document.getElementById('tituloImagen');
          const descEl = document.getElementById('descripcionImagen');
          const esPortada = activo.dataset.esportada === '1';

          if (tituloEl) {
            tituloEl.textContent = activo.dataset.titulo || activo.dataset.nombrealbum || '';
          }
          if (descEl) {
            descEl.textContent = activo.dataset.descripcion || '';
          }

          // Mostrar/ocultar botón de like según si es portada
          if (btnLikeModal) {
            if (esPortada) {
              btnLikeModal.style.display = 'none';
              if (countModal) countModal.style.display = 'none';
            } else {
              btnLikeModal.style.display = 'block';
              if (countModal) countModal.style.display = 'block';
              const idImagen = activo.dataset.idimagen;
              if (idImagen) {
                btnLikeModal.dataset.idimagen = idImagen;
                // Cargar estado de like
                fetch(`${window.BASE_URL}/api/obtenerLikes?idImagen=${idImagen}`)
                  .then(res => res.json())
                  .then(data => {
                    if (countModal) countModal.textContent = data.totalLikes || 0;
                    if (btnLikeModal) {
                      btnLikeModal.src = data.likedByUser 
                        ? `${window.BASE_URL}/assets/images/likelleno.png`
                        : `${window.BASE_URL}/assets/images/like.png`;
                    }
                  })
                  .catch(err => console.error(err));
              }
            }
          }
        };

        carrusel.addEventListener('slid.bs.carousel', actualizarInfoImagen);
        actualizarInfoImagen(); // Inicial
      } else {
        console.error('Carrusel no encontrado. ID buscado:', data.carruselId || "carouselAlbumVirtual");
      }

      // Comentarios (similar al código existente)
      if (btnEnviar && inputComentario && listaComentarios) {
        function mostrarComentarios(idImg) {
          if (!data.comentarios || !data.comentarios[idImg]) {
            listaComentarios.innerHTML = '<p class="text-muted small">Sin comentarios aún.</p>';
            return;
          }
          const comentarios = data.comentarios[idImg];
          if (comentarios.length === 0) {
            listaComentarios.innerHTML = '<p class="text-muted small">Sin comentarios aún.</p>';
            return;
          }
          listaComentarios.innerHTML = comentarios.map(c => {
            const fechaCom = tiempoRelativo(c.fechaComentario);
            return `
              <div class="d-flex gap-2 mb-2">
                <img src="${c.avatar || window.BASE_URL + '/assets/images/imagen.png'}" 
                     class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                <div class="flex-grow-1">
                  <div class="fw-semibold small">${c.apodoUsuario || 'Usuario'}</div>
                  <div class="small">${c.mensajeComentario || ''}</div>
                  <div class="text-muted" style="font-size: 0.75rem;">${fechaCom}</div>
                </div>
              </div>`;
          }).join('');
        }

        btnEnviar.onclick = async () => {
          const activo = carrusel?.querySelector('.carousel-item.active');
          if (!activo) return;
          const idImg = activo.dataset.idimagen;
          const esPortada = activo.dataset.esportada === '1';
          
          if (esPortada) {
            alert('No se pueden agregar comentarios a portadas de álbumes');
            return;
          }

          const texto = inputComentario.value.trim();
          if (!texto || !idImg) return;

          try {
            const res = await fetch(`${window.BASE_URL}/api/agregarComentario`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: `idImagen=${idImg}&mensaje=${encodeURIComponent(texto)}`
            });
            const result = await res.json();
            if (result.success) {
              inputComentario.value = '';
              mostrarComentarios(idImg);
            } else {
              alert('Error: ' + (result.error || 'No se pudo agregar el comentario'));
            }
          } catch (err) {
            console.error(err);
            alert('Error al enviar comentario');
          }
        };

        // Mostrar comentarios iniciales
        const activo = carrusel?.querySelector('.carousel-item.active');
        if (activo && !activo.dataset.esportada) {
          mostrarComentarios(activo.dataset.idimagen);
        }
      }
    }, 100);
  } catch (err) {
    console.error('[detalleLikesUsuario] Error:', err);
    modalLabel.textContent = "Error";
    modalBodyIzq.innerHTML = `<p class='text-danger text-center py-5'>Error al cargar el contenido: ${err.message}</p>`;
    modalBodyDer.innerHTML = "";
  }
};
