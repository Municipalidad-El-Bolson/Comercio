$(document).ready(function () {
  toastr.options = {
    positionClass: "toast-top-right",
    progressBar: true,
  };

  window.addEventListener("hide-form", (event) => {
    $("#form").modal("hide");

    console.log(event.detail[0].message);

    toastr.success(event.detail[0].message);
  });
});

window.addEventListener("show-form", (event) => {
  $("#form").modal("show");
});

window.addEventListener("show-delete-modal", (event) => {
  $("#confirmationModal").modal("show");
});

window.addEventListener("hide-delete-modal", (event) => {
  $("#confirmationModal").modal("hide");
  toastr.success(event.detail.message, "Success!");
});

window.addEventListener("alert", (event) => {
  toastr.error(event.detail.message, "Success!");
});

window.addEventListener("updated", (event) => {
  toastr.success(event.detail.message, "Success!");
});

window.addEventListener("toast", (event) => {
  const detalle = Array.isArray(event.detail) ? event.detail[0] : event.detail;
  const tipo = detalle?.type ?? "info";
  const mensaje = detalle?.message ?? "Mensaje sin contenido";

  switch (tipo) {
    case "success":
      toastr.success(mensaje, "✅ Éxito");
      break;
    case "error":
      toastr.error(mensaje, "❌ Error");
      break;
    case "warning":
      toastr.warning(mensaje, "⚠️ Advertencia");
      break;
    case "info":
    default:
      toastr.info(mensaje, "ℹ️ Info");
  }
});

$('[x-ref="profileLink"]').on("click", function () {
  localStorage.setItem("_x_currentTab", '"profile"');
});
$('[x-ref="changePasswordLink"]').on("click", function () {
  localStorage.setItem("_x_currentTab", '"changePassword"');
});

$("#form").on("shown.bs.modal", function () {
  setTimeout(function () {
    $("#nombre").focus();
  }, 100);
});
