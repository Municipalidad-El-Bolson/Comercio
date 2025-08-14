<!-- Main content -->
<section class="content">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Habilitaciones Comerciales - Mapa</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active"><a href="/">Home</a></li>
                        <li class="breadcrumb-item">Mapa</li>
                    </ol>
                </div>
            </div>
            <div>
                <div>
                    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
                    <link rel="stylesheet"
                        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

                    <!-- Rubro Filter Checkboxes -->
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5>Rubros</h5>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="select-all" value="select-all">
                                <label class="form-check-label text-danger text-bold" for="select-all">Seleccionar
                                    Todos</label>
                            </div>
                            @php
                                $uniqueRubros = [];
                            @endphp
                            @foreach ($ubicaciones as $ubicacion)
                                @php
                                    $normalizedRubro = strtolower(trim($ubicacion->rubro->rubro));
                                @endphp
                                @if (!in_array($normalizedRubro, $uniqueRubros))
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input rubro-checkbox" type="checkbox"
                                            id="rubro-{{ $normalizedRubro }}" value="{{ $normalizedRubro }}" checked>
                                        <label class="form-check-label"
                                            for="rubro-{{ $normalizedRubro }}">{{ $ubicacion->rubro->rubro }}</label>
                                    </div>
                                    @php
                                        $uniqueRubros[] = $normalizedRubro;
                                    @endphp
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div id="map" style="height: 500px; 100%;"></div>
                        </div>
                    </div>

                    <script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
                    <script>
                        const googleApiKey = "AIzaSyAyL3dQW5_PKAJLxYhs7EuzN3KGfbF7Ang";

                        mapboxgl.accessToken =
                            'pk.eyJ1IjoiYm9sc29uc2lzdGVtYXMiLCJhIjoiY2tpb3AzamM3MWYybzJ6dTYxZTR1cWJudCJ9.17kL4-zY3HQ16MGRHyuEkQ';
                        const map = new mapboxgl.Map({
                            container: 'map',
                            style: 'mapbox://styles/mapbox/streets-v12',
                            center: [-71.53, -41.9645], // Centra el mapa en El Bolsón
                            zoom: 14
                        });

                        map.on('load', function() {
                            const layers = map.getStyle().layers;
                            layers.forEach((layer) => {
                                if (layer.id.includes('poi') || layer.id.includes('place')) {
                                    map.setLayoutProperty(layer.id, 'visibility', 'none'); // Oculta la capa
                                }
                            });
                        });

                        const ubicaciones = @json($ubicaciones);
                        const markers = [];

                        function updateMarkers() {
                            const selectedRubros = Array.from(document.querySelectorAll('input.rubro-checkbox:checked')).map(cb => cb
                                .value);

                            markers.forEach(marker => marker.remove());

                            ubicaciones.forEach(record => {
                                if (selectedRubros.includes(record.rubro.rubro.toLowerCase().trim())) {
                                    let geocodeURL =
                                        `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(record.direccion)}&key=${googleApiKey}`;

                                    fetch(geocodeURL)
                                        .then(response => response.json())
                                        .then(georreferencia => {
                                            if (georreferencia.results.length > 0) {
                                                var localizacion = georreferencia.results[0].geometry.location;

                                                const el = document.createElement('div');
                                                el.className = `${record.rubro.rubro}` == 'Inmobiliaria' ?
                                                    'text-info font-weight-bold h4' : 'text-danger font-weight-bold h4';
                                                el.innerHTML = `${record.tipo}`;
                                                el.title = `${record.razon_social}\n${record.direccion}\n${record.rubro.rubro}`;

                                                const marker = new mapboxgl.Marker(el)
                                                    .setLngLat([localizacion.lng, localizacion.lat])
                                                    .addTo(map);

                                                const popup = new mapboxgl.Popup({
                                                        offset: 25
                                                    })
                                                    .setHTML(`
                                                <h3>${record.razon_social}</h3>
                                                <p><strong>Dirección:</strong> ${record.direccion}</p>
                                                <p><strong>Rubro:</strong> ${record.rubro.rubro}</p>
                                                `);

                                                marker.setPopup(popup);
                                                markers.push(marker);
                                            }
                                        });
                                }
                            });
                        }

                        document.querySelectorAll('input.rubro-checkbox').forEach(checkbox => {
                            checkbox.addEventListener('change', updateMarkers);
                        });

                        document.getElementById('select-all').addEventListener('change', function() {
                            const isChecked = this.checked;
                            document.querySelectorAll('input.rubro-checkbox').forEach(checkbox => {
                                checkbox.checked = isChecked;
                            });
                            updateMarkers();
                        });

                        updateMarkers();
                    </script>
                </div>
            </div>
        </div>
    </div>
</section>
