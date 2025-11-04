function tiempoRelativo(fech, ahoraMs = Date.now()) {
  const fechaMs = typeof fech === "number" ? fech : new Date(fech).getTime();
  let diffMs = ahoraMs - fechaMs;
  if (!isFinite(diffMs)) diffMs = 0;
  if (diffMs < 0) diffMs = 0;
  const diffSeg = Math.floor(diffMs / 1000);
  const diffMin = Math.floor(diffSeg / 60);
  const diffHoras = Math.floor(diffMin / 60);
  const diffDias = Math.floor(diffHoras / 24);

  if (diffSeg < 60) return `hace ${diffSeg} segundos`;
  if (diffMin < 60) return `hace ${diffMin} minutos`;
  if (diffHoras < 24) return `hace ${diffHoras} horas`;
  if (diffDias < 30) return `hace ${diffDias} días`;
  const diffMeses = Math.floor(diffDias / 30);
  if (diffMeses < 12) return `hace ${diffMeses} meses`;
  const diffAnios = Math.floor(diffMeses / 12);
  return `hace ${diffAnios} años`;
}


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

// Carga contador y estado (icono) del like para una imagen
async function actualizarEstadoLikeImagen(idImagen) {
  if (!idImagen) return;
  try {
    const resp = await fetch(`../controllers/obtenerLikes.php?idImagen=${encodeURIComponent(idImagen)}`);
    const data = await resp.json();
    if (resp.ok && data.totalLikes !== undefined) {
      const mostradorModal = document.getElementById("likes-count-display");
      if (mostradorModal) mostradorModal.textContent = data.totalLikes;
      const btnLikeModal = document.getElementById("btn-like-imagen");
      if (btnLikeModal) {
        btnLikeModal.src = data.likedByUser
          ? "../../public/assets/images/likelleno.png"
          : "../../public/assets/images/like.png";
      }
    }
  } catch (err) {
    console.warn("No se pudo cargar estado de like de imagen", err);
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
            ? "../../public/assets/images/likelleno.png"
            : "../../public/assets/images/like.png";
      }
      return;
    }

    // 2️⃣ - BOTÓN DE LIKE DE LA GALERÍA (Home) → Like de ÁLBUM
    const btnLikeGaleria = e.target.closest(".btn-like-galeria");
    if (btnLikeGaleria) {
      e.preventDefault();
  
      const idAlbum = btnLikeGaleria.dataset.idalbum;
      if (!idAlbum) {
        console.error("❌ No se encontró data-idalbum en el botón de galería");
        return;
      }
  
      try {
        const resp = await fetch("../views/megusta.php", {
          method: "POST",
          body: new URLSearchParams({ idAlbum }),
        });
  
        const data = await resp.json();
  
        if (resp.ok && data.totalLikes !== undefined) {
          const contador = document.querySelector(`#likes-count-album-${idAlbum}`);
          if (contador) contador.textContent = data.totalLikes;
  
          // Cambia el icono si corresponde
          if (data.accion === "like") {
            btnLikeGaleria.src = "../../public/assets/images/likelleno.png";
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

  // --- Inicializar: cargar conteos de likes para botones de galería (álbum) ---
  document.querySelectorAll(".btn-like-galeria").forEach(async (el) => {
    const idAlbum = el.dataset.idalbum;
    if (!idAlbum) return;
    try {
      const resp = await fetch(`../controllers/obtenerLikes.php?idAlbum=${encodeURIComponent(idAlbum)}`);
      const data = await resp.json();
      if (resp.ok && data.totalLikes !== undefined) {
        const contador = document.getElementById(`likes-count-album-${idAlbum}`);
        if (contador) contador.textContent = data.totalLikes;
        el.src = (data.likedByUser)
          ? "../../public/assets/images/likelleno.png"
          : "../../public/assets/images/like.png";
      }
    } catch (err) {
      console.warn("No se pudieron cargar likes iniciales para", idAlbum, err);
    }
  });


//carga el detalle del album en el modal
document.querySelectorAll(".abrir-modal-album").forEach((el) => {
  el.addEventListener("click", function () {
    let idAlbum = this.getAttribute("data-id");

    fetch(`../controllers/detalleAlbum.php?id=${idAlbum}`)
      .then((res) => res.json())
      .then((data) => {
        console.log(data);
        // Guardar timestamp base del álbum para cálculo relativo
        window.serverFechaUnix = data.fechaUnix;
        //datos del usuario
        document.getElementById("modalDetalleAlbumLabel").innerHTML = `
        <div class="d-flex flex-column gap-1">
          <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0"><strong>${data.apodo}</strong></h4>
            <div class="text-muted fw-light"><small> - @${data.usuario}</small></div>
          </div>
          <div class="d-flex align-items-center">
            <div class="text-muted mt-1 fw-light" style="font-size: 0.9rem;"><span id="fechaRelativaLabel">${tiempoRelativo(data.fechaUnix, Date.now())}</span></div>
          </div>
        </div>
        `;

        document.getElementById("modalFotoPerfil").src = data.fotoPerfil;
        document
          .getElementById("btnSeguir")
          .setAttribute("data-id", data.idUsuario);

        document.getElementById("detalleAlbumIzquierda").innerHTML =
          data.izquierda;
        document.getElementById("detalleAlbumDerecha").innerHTML = data.derecha;

        // Actualizar tiempo relativo con hora local y mantenerlo actualizado cada minuto
        const actualizarFechaHeader = () => {
          const label = document.getElementById("fechaRelativaLabel");
          if (label && window.serverFechaUnix) {
            label.textContent = tiempoRelativo(window.serverFechaUnix, Date.now());
          }
        };
        actualizarFechaHeader();
        if (window.relativeTimeTimer) clearInterval(window.relativeTimeTimer);
        window.relativeTimeTimer = setInterval(actualizarFechaHeader, 60000);
        const modalEl = document.getElementById("modalDetalleAlbum");
        if (modalEl) {
          modalEl.addEventListener("hidden.bs.modal", () => {
            if (window.relativeTimeTimer) {
              clearInterval(window.relativeTimeTimer);
              window.relativeTimeTimer = null;
            }
          }, { once: true });
        }
        // Inicializar estado de like y contador para la imagen activa
        setTimeout(() => {
          const carr = document.getElementById("carouselAlbum");
          if (carr) {
            const activo = carr.querySelector(".carousel-item.active");
            if (activo) {
              const idImgActivo = activo.getAttribute("data-idimagen");
              const btnLike = document.getElementById("btn-like-imagen");
              if (btnLike && idImgActivo) {
                btnLike.dataset.idimagen = idImgActivo;
                actualizarEstadoLikeImagen(idImgActivo);
              }
            }
          }
        }, 0);
        setTimeout(() => {
          const btnEnviar = document.getElementById("btnEnviarComentario");
          const inputComentario = document.getElementById("inputComentario");

          if (btnEnviar && inputComentario) {
            btnEnviar.addEventListener("click", function () {
              let idImagen = this.getAttribute("data-idimagen");
              let mensaje = inputComentario.value.trim();
              if (!mensaje) return;

              fetch("../controllers/agregarComentario.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ idImagen, mensaje }),
              })
                .then((res) => res.json())
                .then((res) => {
                  if (res.ok) {
                    let nuevo = `
            <div class="d-flex gap-2 mb-3">
              <img src="${res.avatar}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
              <div>
                <strong>${res.apodo}</strong><br>
                <p class="mb-0">${res.mensaje}</p>
              </div>
            </div>
          `;
                    document
                      .getElementById("listaComentarios")
                      .insertAdjacentHTML("beforeend", nuevo);
                    inputComentario.value = "";

                    if (!data.comentarios[idImagen]) {
                      data.comentarios[idImagen] = [];
                    }
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
        }, 0);

        //cambia la info de la imagen al cambiar de slide y los comentarios
        let carrusel = document.getElementById("carouselAlbum");
        function actualizarInfoImagen() {
          let activo = carrusel.querySelector(".carousel-item.active");
          let titulo = activo.getAttribute("data-titulo") || "";
          let descripcion = activo.getAttribute("data-descripcion") || "";
          let idImagen = activo.getAttribute("data-idimagen");
          let fechaUnixSlide = activo.getAttribute("data-fechaunix");

          document.getElementById("tituloImagen").textContent = titulo;
          document.getElementById("descripcionImagen").textContent =
            descripcion;

          // Actualizar el tiempo relativo usando la hora local del dispositivo
          const fechaLabel = document.getElementById("fechaRelativaLabel");
          if (fechaLabel) {
            fechaLabel.textContent = tiempoRelativo(window.serverFechaUnix, Date.now());
          }

          let comentarios = data.comentarios[idImagen] || [];
          let htmlComentarios = comentarios
            .map(
              (c) => `
    <div class="d-flex gap-2 mb-3" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px;">
      <img src="${c.avatar}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
      <div>
        <strong>${c.apodo}</strong><br>
        <p class="mb-0">${c.mensaje}</p>
      </div>
    </div>
  `
            )
            .join("");

          document.getElementById("listaComentarios").innerHTML =
            htmlComentarios;

          document
            .getElementById("btnEnviarComentario")
            .setAttribute("data-idimagen", idImagen);
          // Actualizar dataset del botón de like y cargar estado
          const btnLike = document.getElementById("btn-like-imagen");
          if (btnLike && idImagen) {
            btnLike.dataset.idimagen = idImagen;
            actualizarEstadoLikeImagen(idImagen);
          }
        }

        actualizarInfoImagen();

        carrusel.addEventListener("slid.bs.carousel", actualizarInfoImagen);
      });
  });
});

//funcion para el boton de cargar mas albumes
document.addEventListener("DOMContentLoaded", function () {
  let albums = document.querySelectorAll(".album-item");
  let loadMoreBtn = document.getElementById("loadMore");

  let itemsPerPage = 20;
  let currentItems = itemsPerPage;

  // ocultar todos menos los primeros 20
  albums.forEach((album, index) => {
    if (index >= currentItems) album.style.display = "none";
  });

  if (loadMoreBtn) {
    loadMoreBtn.addEventListener("click", () => {
      for (let i = currentItems; i < currentItems + itemsPerPage; i++) {
        if (albums[i]) albums[i].style.display = "block";
      }
      currentItems += itemsPerPage;

      if (currentItems >= albums.length) {
        loadMoreBtn.style.display = "none";
      }
    });

    if (albums.length <= itemsPerPage) {
      loadMoreBtn.style.display = "none";
    }
  }
});

//cambia de "pagina" dentro del formulario
function mostrarSigForm() {
  //primero valida que los datos del album esten bien
  const form = document.getElementById("formCrearAlbum");
  form.classList.add("was-validated");

  document.getElementById("inputPortada").classList.remove("is-invalid");
  document.getElementById("tituloAlb").classList.remove("is-invalid");
  document.getElementById("etiquetaAlb").classList.remove("is-invalid");

  let valido = true;
  let portada = document.getElementById("inputPortada").files[0];
  let tituloAlb = document.getElementById("tituloAlb").value.trim();
  let etiquetaAlb = document.getElementById("etiquetaAlb").value.trim();

  if (!portada || !portada.type.startsWith("image/")) {
    document.getElementById("inputPortada").classList.add("is-invalid");
    document.getElementById("inputPortada").nextElementSibling.textContent =
      "Sube una imagen de portada.";
    valido = false;
  }
  if (tituloAlb.length < 3) {
    document.getElementById("tituloAlb").classList.add("is-invalid");
    document.getElementById("tituloAlb").nextElementSibling.textContent =
      "Completa el título del álbum. Debe tener al menos 3 letras.";
    valido = false;
  }
  if (etiquetaAlb != "" && etiquetaAlb.length < 3) {
    document.getElementById("etiquetaAlb").classList.add("is-invalid");
    document.getElementById("etiquetaAlb").nextElementSibling.textContent =
      "Si agregas alguna etiqueta, debe tener al menos 3 letras.";
    valido = false;
  }

  //si hay errores queda en la pagina esa y sino avanza
  if (!valido) return;

  document.getElementById("formParteUno").classList.add("d-none");
  document.getElementById("formParteDos").classList.remove("d-none");
  document.getElementById("formParteTres").classList.add("d-none");
}

//muestra la vista previa de la portada
const elPortada = document.getElementById("inputPortada");
if (elPortada) elPortada.addEventListener("change", function () {
  let archivo = this.files[0];
  let preview = document.getElementById("previoPortada");

  if (archivo) {
    let lector = new FileReader();

    lector.onload = function (e) {
      preview.src = e.target.result;
      preview.style.display = "block";
      document.getElementById("portada").style.display = "none";
    };

    lector.readAsDataURL(archivo);
  }
});

const elInputImgs = document.getElementById("inputImagenes");
if (elInputImgs) elInputImgs.addEventListener("change", function () {
    let cantidad = this.files.length;

    if (cantidad > 0) {
      mostrarSigForm2();
    }
  });

let imagenes = [];
let indiceActual = 0;

// Permite agregar otra imagen de forma secuencial
function agregarOtraImagen() {
  if (imagenes.length >= 20) {
    if (window.Swal) {
      Swal.fire({
        icon: "info",
        title: "Límite alcanzado",
        text: "El máximo son 20 imágenes por álbum.",
      });
    }
    return;
  }

  const picker = document.createElement("input");
  picker.type = "file";
  picker.accept = "image/*";
  picker.onchange = function () {
    const file = picker.files && picker.files[0];
    if (!file) return;
    imagenes.push(file);
    indiceActual = imagenes.length - 1;
    mostrarImagenActual();
  };
  picker.click();
}

function mostrarSigForm2() {
  let input = document.getElementById("inputImagenes");
  imagenes = Array.from(input.files);
  indiceActual = 0;

  if (imagenes.length === 0) return;

  document.getElementById("formParteDos").classList.add("d-none");
  document.getElementById("formParteTres").classList.remove("d-none");

  mostrarImagenActual();
}

//muestra las imagenes q cargo el usuario y el form para que le agregue detalles
function mostrarImagenActual() {
  let archivo = imagenes[indiceActual];
  let lector = new FileReader();

  lector.onload = function (e) {
    let bloque = `<p><small>Imagen ${indiceActual + 1} de ${
      imagenes.length
    }</small></p>
      <div class="row mb-4 align-items-center">
        <div class="col-lg-6 col-12">
          <img src="${
            e.target.result
          }" class="img-fluid rounded shadow-sm imagenPreview">
        </div>
        <div class="col-lg-6 col-12">
          <input type="text" id="tituloImagen${indiceActual}" name="tituloImagen${indiceActual}" class="form-control mb-3" placeholder="Título de la imagen">
            <div class="invalid-feedback">Si completás este campo, que tenga al menos 1 carácter visible.</div>

          <textarea name="descripcionImagen${indiceActual}" id="descripcionImagen${indiceActual}" class="form-control mb-3" rows="4" placeholder="Descripción" style="resize: none;"></textarea>
            <div class="invalid-feedback">Si completás este campo, que tenga al menos 1 carácter visible.</div>

          <input type="text" name="etiquetaImagen${indiceActual}" id="etiquetaImagen${indiceActual}" class="form-control mb-3" placeholder="#etiqueta">
            <div class="invalid-feedback">Si agregas alguna etiqueta, debe tener al menos 3 letras.</div>

        </div>
      </div>
    `;
    document.getElementById("bloqueImagenActual").innerHTML = bloque;

    document
      .querySelector(`input[name="etiquetaImagen${indiceActual}"]`)
      .addEventListener("keydown", function (e) {
        if (e.key === " ") e.preventDefault();
      });

    //mostrar o no los botones segun la posicion
    let btnCrear = document.getElementById("btnCrear");
    let btnSiguiente = document.getElementById("btnSiguienteImagen");
    let btnAnterior = document.getElementById("btnAnteriorImagen");
    let btnAgregarOtra = document.getElementById("btnAgregarOtra");

    btnAnterior.classList.toggle("d-none", indiceActual === 0);
    btnSiguiente.classList.toggle(
      "d-none",
      indiceActual === imagenes.length - 1
    );
    btnCrear.classList.toggle("d-none", indiceActual !== imagenes.length - 1);
    if (btnAgregarOtra) {
      btnAgregarOtra.onclick = () => {
        if (!validarImagenActual()) return;
        guardarDatosImagenActual();
        agregarOtraImagen();
      };
    }

    document.getElementById("btnSiguienteImagen").onclick = () => {
      if (!validarImagenActual()) return;
      guardarDatosImagenActual();
      if (indiceActual < imagenes.length - 1) {
        indiceActual++;
        mostrarImagenActual();
      }
    };
  };

  lector.readAsDataURL(archivo);
}
let datosImagenes = [];
function guardarDatosImagenActual() {
  datosImagenes[indiceActual] = {
    archivo: imagenes[indiceActual],
    titulo: document.getElementById(`tituloImagen${indiceActual}`).value,
    descripcion: document.getElementById(`descripcionImagen${indiceActual}`)
      .value,
    etiqueta: document.getElementById(`etiquetaImagen${indiceActual}`).value,
  };
}
function validarImagenActual() {
  let valido = true;

  const form = document.getElementById("formCrearAlbum");

  let titulo = document.getElementById(`tituloImagen${indiceActual}`);
  let descripcion = document.getElementById(`descripcionImagen${indiceActual}`);
  let etiqueta = document.getElementById(`etiquetaImagen${indiceActual}`);

  titulo.classList.remove("is-invalid");
  descripcion.classList.remove("is-invalid");
  etiqueta.classList.remove("is-invalid");

  if (titulo.value.trim() < 3) {
    titulo.classList.add("is-invalid");
    titulo.nextElementSibling.textContent =
      "Completa el título de la imagen. Debe tener al menos 3 letras.";
    valido = false;
  }

  if (descripcion.value.trim() !== "" && descripcion.value.trim().length < 1) {
    descripcion.classList.add("is-invalid");
    descripcion.nextElementSibling.textContent =
      "Si completás este campo, que tenga al menos 1 carácter visible.";
    valido = false;
  }

  if (etiqueta.value.trim() !== "" && etiqueta.value.trim().length < 3) {
    etiqueta.classList.add("is-invalid");
    etiqueta.nextElementSibling.textContent =
      "Si agregas alguna etiqueta, debe tener al menos 3 letras.";
    valido = false;
  }

  return valido;
}

const btnAnteriorImagen = document.getElementById("btnAnteriorImagen");
if (btnAnteriorImagen) btnAnteriorImagen.addEventListener("click", () => {
  guardarDatosImagenActual();
  if (indiceActual > 0) {
    indiceActual--;
    mostrarImagenActual();
  }
});

let modalCrearAlbum = document.getElementById("modalCrearAlbum");
if (modalCrearAlbum) modalCrearAlbum.addEventListener("hidden.bs.modal", function () {
  //resetea el form si se cierra
  const form = document.getElementById("formCrearAlbum");
  form.reset();
  form.classList.remove("was-validated");

  form.querySelectorAll(".form-control, .form-select").forEach((campo) => {
    campo.classList.remove("is-invalid", "is-valid");
  });

  form.querySelectorAll(".invalid-feedback").forEach((msg) => {
    msg.textContent = "";
  });

  document.getElementById("formParteUno").classList.remove("d-none");
  document.getElementById("formParteDos").classList.add("d-none");
  document.getElementById("formParteTres").classList.add("d-none");

  document.getElementById("portada").style.display = "block";
  document.getElementById("previoPortada").style.display = "none";
  document.getElementById("previoImagen").classList.add("d-none");
});

//permite una sola palabra en la etiqueta
document
  .querySelector('input[name="etiquetaAlb"]')
  .addEventListener("keydown", function (e) {
    if (e.key === " ") e.preventDefault();
  });

function actualizarVistaPrincipal() {}

//envio del formulario
const btnCrearEl = document.getElementById("btnCrear");
if (btnCrearEl) btnCrearEl.addEventListener("click", function (e) {
  if (!validarImagenActual()) return;
  guardarDatosImagenActual();
  e.preventDefault();
  const formData = new FormData();

  //album
  formData.append("tituloAlbum", document.getElementById("tituloAlb").value);
  formData.append(
    "etiquetaAlbum",
    document.getElementById("etiquetaAlb").value
  );
  formData.append("portada", document.getElementById("inputPortada").files[0]);
  // privacidad (0: seguidores, 1: público)
  const selPriv = document.querySelector('select[name="privacidad"]');
  if (selPriv) {
    formData.append("esPublico", selPriv.value);
  }

  //imagenes
  // Enviar todas las seleccionadas; si no hay datos cargados para alguna,
  // usar valores por defecto y el archivo directo de 'imagenes'
  for (let i = 0; i < imagenes.length; i++) {
    const datos = datosImagenes[i] || {
      archivo: imagenes[i],
      titulo: "",
      descripcion: "",
      etiqueta: "",
    };
    formData.append(`imagen${i}`, datos.archivo);
    formData.append(`tituloImagen${i}`, datos.titulo ?? "");
    formData.append(`descripcionImagen${i}`, datos.descripcion ?? "");
    formData.append(`etiquetaImagen${i}`, datos.etiqueta ?? "");
  }

  formData.append("cantidadImagenes", imagenes.length);
  //envio los datos al controlador
  // ajusta segun la estructura de carpetas
  const base = window.location.origin;
  const rutaRaiz = window.location.pathname.split("/app/views")[0]; // todo antes de /app/views
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
          alert("Error: " + data.mensaje);
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
