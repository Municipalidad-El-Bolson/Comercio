@push('js')
    <script>
        window.addEventListener('show-delete-confirmation', event => {
            Swal.fire({
                title: '¿Seguro desea Eliminar?',
                text: "Los cambios no se pueden deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cerrar',
                confirmButtonText: 'Si'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.emit('deleteConfirmed')
                }
            })
        })

        window.addEventListener('deleted', event => {
            Swal.fire(
                'Eliminado!',
                event.detail.message,
                'success'
            )
        })

        window.addEventListener('confirmation-eliminar_producto', event => {
            Swal.fire({
                title: 'Desea Eliminar este Articulo?',
                text: "Los cambios no se pueden deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cerrar',
                confirmButtonText: 'Si'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.emit('EliminarArt')
                }
            })
        })

        //Tramite Resuelto
        window.addEventListener('eliminado', event => {
            Swal.fire(
                'El Articulo fue Eliminado con Exito!',
                event.detail.message,
                'success'
            )
        })

        //Tramite Sin Observaciones
        window.addEventListener('sinObservaciones', event => {
            Swal.fire(
                'Debe contener una Observación!',
                event.detail.message,
                'warning'
            )
        })

        //Operacion no permitida
        window.addEventListener('noPermitido', event => {
            Swal.fire(
                'Operacion No Permitida!',
                event.detail.message,
                'error'
            )
        })

        //Mensajes Ventas

        window.addEventListener('venta_exitosa', event => {
            Swal.fire(
                'Venta Realizada con Exito!',
                event.detail.message,
                'success'
            )
        })

        //Cierre de Caja Exitoso

        window.addEventListener('cierreok', event => {
            Swal.fire(
                'Cierre realizado con Exito!',
                event.detail.message,
                'success'
            )
        })

        // Ingreso de Concepto Ok

        window.addEventListener('ingresoOK', event => {
            Swal.fire(
                'Movimiento de Caja generado con Exito!',
                event.detail.message,
                'success'
            )
        })
    </script>
@endpush
