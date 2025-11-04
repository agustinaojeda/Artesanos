document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("modalDetalleAlbum");
  const modalLabel = document.getElementById("modalDetalleAlbumLabel");
  const modalBodyIzq = document.getElementById("detalleAlbumIzquierda");
  const modalBodyDer = document.getElementById("detalleAlbumDerecha");
  const fotoPerfil = document.getElementById("modalFotoPerfil");

  function tiempoRelativo(fech) {
    let fecha = new Date(fech);
    let ahora = new Date();
    let diffMs = ahora - fecha;
    let diffSeg = Math.floor(diffMs / 1000);
    let diffMin = Math.floor(diffSeg / 60);
    let diffHoras = Math.floor(diffMin / 60);
    let diffDias = Math.floor(diffHoras / 24);

    if (diffSeg < 60) return `hace ${diffSeg} segundos`;
    if (diffMin < 60) return `hace ${diffMin} minutos`;
    if (diffHoras < 24) return `hace ${diffHoras} horas`;
    if (diffDias === 1) return `ayer`;
    return `hace ${diffDias} días`;
  }

  document.querySelectorAll(".album-card").forEach((card) => {
    card.addEventListener("click", async () => {
      const albumId = card.dataset.id;

      modalLabel.innerHTML = `<img src='../../public/assets/images/logo.png' width='28' class='me-2'> Cargando álbum...`;
      modalBodyIzq.innerHTML = `<p class='text-center py-5'>Cargando imágenes...</p>`;
      modalBodyDer.innerHTML = `<p class='text-center py-5'>Cargando datos...</p>`;
      fotoPerfil.src = "";

      try {
        const res = await fetch(`../../app/controllers/detalleAlbum.php?id=${albumId}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        if (data.error) {
          modalBodyIzq.innerHTML = `<p class='text-danger text-center py-5'>${data.error}</p>`;
          modalBodyDer.innerHTML = "";
          modalLabel.textContent = "Error";
          return;
        }

        // Mostrar datos
        let fechaRelativa = tiempoRelativo(data.fecha);
        modalLabel.innerHTML = `
          <div class="d-flex flex-column">
            <div class="d-flex align-items-center gap-2">
              <h4 class="mb-0"><strong>${data.apodo}</strong></h4>
              <div class="text-muted fw-light"><small> - @${data.usuario}</small></div>
            </div>
            <div class="text-muted mt-1 fw-light" style="font-size: 0.9rem;">${fechaRelativa}</div>
          </div>`;

        fotoPerfil.src = data.fotoPerfil;
        document.getElementById("btnSeguir").setAttribute("data-id", data.idUsuario);
        modalBodyIzq.innerHTML = data.izquierda;
        modalBodyDer.innerHTML = data.derecha;

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
            const activo = carrusel.querySelector(".carousel-item.active");
            const titulo = activo?.dataset.titulo || "";
            const descripcion = activo?.dataset.descripcion || "";
            const idImagen = activo?.dataset.idimagen;
            document.getElementById("tituloImagen").textContent = titulo;
            document.getElementById("descripcionImagen").textContent = descripcion;
            btnEnviar.setAttribute("data-idimagen", idImagen);
            mostrarComentarios(idImagen);

            // Actualizar estado y conteo de like del modal para la imagen activa
            if (btnLikeModal && idImagen) {
              btnLikeModal.dataset.idimagen = idImagen;
              try {
                const resp = await fetch(`../controllers/obtenerLikes.php?idImagen=${encodeURIComponent(idImagen)}`);
                const likeData = await resp.json();
                if (resp.ok && likeData.totalLikes !== undefined) {
                  if (countModal) countModal.textContent = likeData.totalLikes;
                  btnLikeModal.src = likeData.likedByUser
                    ? "../../public/assets/images/likelleno.png"
                    : "../../public/assets/images/like.png";
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

              fetch("../../app/controllers/agregarComentario.php", {
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

          actualizarInfoImagen();
          carrusel.addEventListener("slid.bs.carousel", actualizarInfoImagen);
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
      const resp = await fetch(`../controllers/obtenerLikes.php?idAlbum=${encodeURIComponent(idAlbum)}`);
      const data = await resp.json();
      if (resp.ok && data.totalLikes !== undefined) {
        // Actualiza el contador adyacente para evitar colisiones de IDs en distintas secciones
        const contador = el.nextElementSibling || el.parentElement?.querySelector(`span[id="likes-count-album-${idAlbum}"]`);
        if (contador) contador.textContent = data.totalLikes;
        el.src = data.likedByUser
          ? "../../public/assets/images/likelleno.png"
          : "../../public/assets/images/like.png";
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
      const resp = await fetch(`../controllers/obtenerLikes.php?idImagen=${encodeURIComponent(idImagen)}`);
      const data = await resp.json();
      if (resp.ok && data.totalLikes !== undefined) {
        const contador = document.getElementById(`likes-count-image-${idImagen}`);
        if (contador) contador.textContent = data.totalLikes;
        el.src = data.likedByUser
          ? "../../public/assets/images/likelleno.png"
          : "../../public/assets/images/like.png";
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
        const resp = await fetch("megusta.php", {
          method: "POST",
          body: new URLSearchParams({ idImagen })
        });
        const data = await resp.json();
        if (resp.ok && data.totalLikes !== undefined) {
          const contadorModal = document.getElementById("likes-count-display");
          if (contadorModal) contadorModal.textContent = data.totalLikes;
          btnLikeModal.src = data.accion === "like"
            ? "../../public/assets/images/likelleno.png"
            : "../../public/assets/images/like.png";
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
        const resp = await fetch("megusta.php", {
          method: "POST",
          body: new URLSearchParams({ idAlbum })
        });
        const data = await resp.json();
        if (resp.ok && data.totalLikes !== undefined) {
          const contador = btnAlbum.nextElementSibling || btnAlbum.parentElement?.querySelector(`span[id="likes-count-album-${idAlbum}"]`);
          if (contador) contador.textContent = data.totalLikes;
          btnAlbum.src = data.accion === "like"
            ? "../../public/assets/images/likelleno.png"
            : "../../public/assets/images/like.png";
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
        const resp = await fetch("megusta.php", {
          method: "POST",
          body: new URLSearchParams({ idImagen })
        });
        const data = await resp.json();
        if (resp.ok && data.totalLikes !== undefined) {
          const contador = document.getElementById(`likes-count-image-${idImagen}`);
          if (contador) contador.textContent = data.totalLikes;
          btnImg.src = data.accion === "like"
            ? "../../public/assets/images/likelleno.png"
            : "../../public/assets/images/like.png";
        } else {
          console.error("Error al registrar el like de imagen:", data.error || "Respuesta inesperada");
        }
      } catch (err) {
        console.error("Error en el fetch de like de imagen:", err);
      }
      return;
    }
  });
});
