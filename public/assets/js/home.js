// home.js (versión corregida y completa)
// --------------------------------------------------
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
// Variable global para guardar los datos del álbum cargado en el modal
let datosAlbumCargado = {};

// Función para obtener y actualizar el conteo de likes (Usa obtenerLikes.php)
async function actualizarConteoLikes(idImagen) {
  if (!idImagen) return;
  const countDisplay = document.getElementById("likes-count-display");
  try {
    const resp = await fetch(
      `../controllers/obtenerLikes.php?idImagen=${encodeURIComponent(idImagen)}`
    );
    const data = await resp.json();

    if (resp.ok && data.totalLikes !== undefined) {
      if (countDisplay) countDisplay.textContent = data.totalLikes;
      // también actualizamos el contador en la galería si existe
      const contGaleria = document.getElementById(`likes-count-album-${idImagen}`);
      if (contGaleria) contGaleria.textContent = data.totalLikes;
    } else {
      if (countDisplay) countDisplay.textContent = "0";
      console.warn("obtenerLikes: respuesta inesperada", data);
    }
  } catch (err) {
    console.error("Error al cargar likes de imagen:", err);
    if (countDisplay) countDisplay.textContent = "0";
  }
}

// Función unificada para manejar el Like/Dislike (Usa megusta.php)
async function manejarMeGusta(idImagen) {
  if (!idImagen || Number(idImagen) === 0) return null;

  try {
    const resp = await fetch("../views/megusta.php", {
      method: "POST",
      body: new URLSearchParams({ idImagen }),
    });

    const data = await resp.json();

    if (!resp.ok) {
      console.error("megusta.php respondió error:", data);
      return data;
    }

    if (data.totalLikes !== undefined) {
      // 1. Actualiza el contador en el modal
      const mostradorModal = document.getElementById("likes-count-display");
      if (mostradorModal) mostradorModal.textContent = data.totalLikes;

      // 2. Actualiza el contador en la galería (usa el idImagen)
      const contGaleria = document.getElementById(`likes-count-album-${idImagen}`);
      if (contGaleria) contGaleria.textContent = data.totalLikes;
    } else {
      console.error("Error al dar me gusta: respuesta inesperada", data);
    }

    return data;
  } catch (err) {
    console.error("Error en el fetch de like:", err);
    return null;
  }
}

// --- Lógica para abrir modal y cargar detalle del álbum ---
// Nota: atamos los listeners en DOMContentLoaded para evitar problemas con elementos que no existen aún.
document.addEventListener("DOMContentLoaded", function () {
  // 1) Delegación: botones .abrir-modal-album
  document.querySelectorAll(".abrir-modal-album").forEach((el) => {
    el.addEventListener("click", function (ev) {
      ev.preventDefault();
      let idAlbum = this.getAttribute("data-id");
      if (!idAlbum) {
        console.error("abrir-modal-album sin data-id");
        return;
      }

      fetch(`../controllers/detalleAlbum.php?id=${encodeURIComponent(idAlbum)}`)
        .then((res) => res.json())
        .then((data) => {
          datosAlbumCargado = data;
          let fechaRelativa = tiempoRelativo(data.fecha || new Date().toISOString());
          // datos del usuario
          const labelEl = document.getElementById("modalDetalleAlbumLabel");
          if (labelEl) {
            labelEl.innerHTML = `
              <div class="d-flex flex-column">
                <div class="d-flex align-items-center gap-2">
                  <h4 class="mb-0"><strong>${data.apodo || ""}</strong></h4>
                  <div class="text-muted fw-light"><small> - @${data.usuario || ""}</small></div>
                </div>
                <div class="text-muted mt-1 fw-light" style="font-size: 0.9rem;">${fechaRelativa}</div>
              </div>`;
          }

          const fotoPerfil = document.getElementById("modalFotoPerfil");
          if (fotoPerfil) fotoPerfil.src = data.fotoPerfil || "";

          const btnSeguir = document.getElementById("btnSeguir");
          if (btnSeguir && data.idUsuario) btnSeguir.setAttribute("data-id", data.idUsuario);

          const izquierda = document.getElementById("detalleAlbumIzquierda");
          const derecha = document.getElementById("detalleAlbumDerecha");
          if (izquierda) izquierda.innerHTML = data.izquierda || "";
          if (derecha) derecha.innerHTML = data.derecha || "";

          // después de inyectar el HTML, ligar listeners locales (like en modal, comentarios, carousel)
          setTimeout(() => {
            const btnEnviar = document.getElementById("btnEnviarComentario");
            const inputComentario = document.getElementById("inputComentario");

            if (btnEnviar && inputComentario) {
              // quitar posibles listeners previos para evitar duplicados
              btnEnviar.onclick = async function () {
                let idImagen = this.getAttribute("data-idimagen");
                let mensaje = inputComentario.value.trim();
                if (!mensaje) return;
                try {
                  const res = await fetch("../controllers/agregarComentario.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ idImagen, mensaje }),
                  });
                  const rjson = await res.json();
                  if (rjson.ok) {
                    let nuevo = `
                      <div class="d-flex gap-2 mb-3">
                        <img src="${rjson.avatar}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                        <div>
                          <strong>${rjson.apodo}</strong><br>
                          <p class="mb-0">${rjson.mensaje}</p>
                        </div>
                      </div>`;
                    const lista = document.getElementById("listaComentarios");
                    if (lista) lista.insertAdjacentHTML("beforeend", nuevo);
                    inputComentario.value = "";

                    if (!datosAlbumCargado.comentarios) datosAlbumCargado.comentarios = {};
                    if (!datosAlbumCargado.comentarios[idImagen]) datosAlbumCargado.comentarios[idImagen] = [];
                    datosAlbumCargado.comentarios[idImagen].push({
                      apodo: rjson.apodo,
                      avatar: rjson.avatar,
                      mensaje: rjson.mensaje,
                      fecha: new Date().toISOString(),
                    });
                  } else {
                    console.warn("Agregar comentario: respuesta no OK", rjson);
                  }
                } catch (err) {
                  console.error("Error al enviar comentario:", err);
                }
              };
            }

            // gestionar carousel: actualizar titulo/desc/likes/comentarios al cambiar slide
            const carrusel = document.getElementById("carouselAlbum");
            const btnLikeModal = document.getElementById("btn-like-imagen");
            function actualizarInfoImagen() {
              if (!carrusel) return;
              const activo = carrusel.querySelector(".carousel-item.active");
              if (!activo) return;
              const titulo = activo.getAttribute("data-titulo") || "";
              const descripcion = activo.getAttribute("data-descripcion") || "";
              const idImagen = activo.getAttribute("data-idimagen") || "";

              const tituloEl = document.getElementById("tituloImagen");
              const descEl = document.getElementById("descripcionImagen");
              if (tituloEl) tituloEl.textContent = titulo;
              if (descEl) descEl.textContent = descripcion;

              if (btnLikeModal && idImagen) btnLikeModal.setAttribute("data-idimagen", idImagen);
              actualizarConteoLikes(idImagen);

              // render comentarios si vienen
              const listaComentarios = document.getElementById("listaComentarios");
              const comentarios = (datosAlbumCargado.comentarios && datosAlbumCargado.comentarios[idImagen]) || [];
              if (listaComentarios) {
                listaComentarios.innerHTML = comentarios
                  .map((c) => `
                    <div class="d-flex gap-2 mb-3" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px;">
                      <img src="${c.avatar}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                      <div>
                        <strong>${c.apodo}</strong><br>
                        <p class="mb-0">${c.mensaje}</p>
                      </div>
                    </div>
                  `)
                  .join("");
              }

              if (btnEnviar) btnEnviar.setAttribute("data-idimagen", idImagen);
            }

            // inicializar info y ligar evento slid (evita duplicar listeners)
            actualizarInfoImagen();
            if (carrusel) {
              carrusel.removeEventListener("slid.bs.carousel", actualizarInfoImagen);
              carrusel.addEventListener("slid.bs.carousel", actualizarInfoImagen);
            }
          }, 50);
        })
        .catch((err) => {
          console.error("Error al traer detalleAlbum:", err);
        });
    });
  });

  // --- MANEJAR EVENTO DE LIKE EN EL MODAL y GALERÍA (delegado) ---
  document.addEventListener("click", async (e) => {
    // 1️⃣ - BOTÓN DE LIKE DEL MODAL
    const btnLikeModal = e.target.closest("#btn-like-imagen");
    if (btnLikeModal) {
      e.preventDefault();
      const idImagen = btnLikeModal.dataset.idimagen;
      const res = await manejarMeGusta(idImagen);
      if (res && res.accion) {
        btnLikeModal.src =
          res.accion === "like"
            ? "../../public/assets/images/like-activo.png"
            : "../../public/assets/images/like.png";
      }
      return;
    }

    // 2️⃣ - BOTÓN DE LIKE DE LA GALERÍA (Home)
    const btnLikeGaleria = e.target.closest(".btn-like-galeria");
    if (btnLikeGaleria) {
      e.preventDefault();

      // Usamos idImagen (asegurate que en home.php lo imprimiste como data-idimagen)
      const idImagen = btnLikeGaleria.dataset.idimagen;
      if (!idImagen) {
        console.error("❌ No se encontró data-idimagen en el botón de galería");
        return;
      }

      try {
        const resp = await fetch("../views/megusta.php", {
          method: "POST",
          body: new URLSearchParams({ idImagen }),
        });

        const data = await resp.json();

        if (resp.ok && data.totalLikes !== undefined) {
          // Actualiza el contador visible en el home
          const contador = document.querySelector(`#likes-count-album-${idImagen}`);
          if (contador) contador.textContent = data.totalLikes;

          // Opcional: cambiar el ícono si el usuario ya dio like
          if (data.accion === "like") {
            btnLikeGaleria.src = "../../public/assets/images/like-activo.png";
          } else if (data.accion === "dislike") {
            btnLikeGaleria.src = "../../public/assets/images/like.png";
          }
        } else {
          console.error("Error al registrar el like:", data.error || "Respuesta inesperada");
        }
      } catch (err) {
        console.error("Error en el fetch de like de galería:", err);
      }
      return;
    }
  });

  // --- Inicializar: cargar conteos de likes para botones de galería (opcional) ---
  document.querySelectorAll(".btn-like-galeria").forEach(async (el) => {
    const idImagen = el.dataset.idimagen;
    if (!idImagen) return;
    try {
      const resp = await fetch(`../controllers/obtenerLikes.php?idImagen=${encodeURIComponent(idImagen)}`);
      const data = await resp.json();
      if (resp.ok && data.totalLikes !== undefined) {
        const contador = document.getElementById(`likes-count-album-${idImagen}`);
        if (contador) contador.textContent = data.totalLikes;
      }
    } catch (err) {
      // no interrumpe la carga si falla alguno
      console.warn("No se pudieron cargar likes iniciales para", idImagen, err);
    }
  });

  // preview portada
  const inputPortada = document.getElementById("inputPortada");
  if (inputPortada) {
    inputPortada.addEventListener("change", function () {
      let archivo = this.files[0];
      let preview = document.getElementById("previoPortada");
      if (!preview) return;
      if (archivo) {
        let lector = new FileReader();
        lector.onload = function (e) {
          preview.src = e.target.result;
          preview.style.display = "block";
          const portadaLabel = document.getElementById("portada");
          if (portadaLabel) portadaLabel.style.display = "none";
        };
        lector.readAsDataURL(archivo);
      }
    });
  }

  // inputImagenes change
  const inputImagenes = document.getElementById("inputImagenes");
  if (inputImagenes) {
    inputImagenes.addEventListener("change", function () {
      let cantidad = this.files.length;
      if (cantidad > 0) {
        if (typeof mostrarSigForm2 === "function") mostrarSigForm2();
      }
    });
  }

  // btnAnteriorImagen safe attach
  const btnAnterior = document.getElementById("btnAnteriorImagen");
  if (btnAnterior) {
    btnAnterior.addEventListener("click", () => {
      if (typeof guardarDatosImagenActual === "function") guardarDatosImagenActual();
      if (window.indiceActual > 0) {
        window.indiceActual = Math.max(0, (window.indiceActual || 0) - 1);
        if (typeof mostrarImagenActual === "function") mostrarImagenActual();
      }
    });
  }

  // btnCrear (envío formulario) safe attach
  const btnCrear = document.getElementById("btnCrear");
  if (btnCrear) {
    btnCrear.addEventListener("click", function (e) {
      if (typeof validarImagenActual === "function" && !validarImagenActual()) return;
      if (typeof guardarDatosImagenActual === "function") guardarDatosImagenActual();
      e.preventDefault();

      const form = document.getElementById("formCrearAlbum");
      if (!form) return;
      const formData = new FormData();

      // album
      const tituloEl = document.getElementById("tituloAlb");
      const etiquetaEl = document.getElementById("etiquetaAlb");
      const portadaFile = (document.getElementById("inputPortada") || {}).files
        ? document.getElementById("inputPortada").files[0]
        : null;

      formData.append("tituloAlbum", tituloEl ? tituloEl.value : "");
      formData.append("etiquetaAlbum", etiquetaEl ? etiquetaEl.value : "");
      if (portadaFile) formData.append("portada", portadaFile);

      if (Array.isArray(window.datosImagenes)) {
        window.datosImagenes.forEach((img, i) => {
          formData.append(`imagen${i}`, img.archivo);
          formData.append(`tituloImagen${i}`, img.titulo || "");
          formData.append(`descripcionImagen${i}`, img.descripcion || "");
          formData.append(`etiquetaImagen${i}`, img.etiqueta || "");
        });
        formData.append("cantidadImagenes", window.datosImagenes.length);
      } else {
        formData.append("cantidadImagenes", 0);
      }

      // build ruta apropiada (como tenías)
      const base = window.location.origin;
      const rutaRaiz = window.location.pathname.split("/app/views")[0];
      const ruta = `${base}${rutaRaiz}/app/controllers/guardarAlbum.php`;

      fetch(ruta, {
        method: "POST",
        body: formData,
      })
        .then(async (res) => {
          const text = await res.text();
          try {
            const data = JSON.parse(text);
            if (data.exito) {
              Swal.fire({
                title: "¡Álbum creado con éxito!",
                text: "Redirigiendo a la pagina principal...",
                icon: "success",
                timer: 3000,
                showConfirmButton: false,
                willClose: () => {
                  location.reload();
                },
              });
            } else {
              alert("Error: " + (data.mensaje || "Error desconocido"));
            }
          } catch (err) {
            console.error("Respuesta no válida:", text);
            alert("Error inesperado del servidor.");
          }
        })
        .catch((err) => {
          console.error("Error:", err);
          Swal.fire({
            icon: "error",
            title: "Error de conexión",
            text: "No se pudo conectar con el servidor.",
          });
        });
    });
  }
}); 

function mostrarRegistro() {
  // Oculta el login
  document.getElementById('bloqueLogin').style.display = 'none';

  // Muestra el registro
  document.getElementById('bloqueRegistro').style.display = 'block';
}
function mostrarLogin() {
  document.getElementById('bloqueLogin').style.display = 'block';

  document.getElementById('bloqueRegistro').style.display = 'none';
}