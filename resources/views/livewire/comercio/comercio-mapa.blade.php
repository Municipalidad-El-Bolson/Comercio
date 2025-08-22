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

                   <div>
                    {{-- Filtros --}}
                    <div class="mb-3">
                        <div class="form-row">
                            <div class="form-group col-md-4 mb-2">
                                <label class="mb-1">Mega rubro</label>
                                <select id="f-mega" class="form-control form-control-sm" wire:model.live="selectedMega">
                                    <option value="">-- Seleccione Mega rubro --</option>
                                    @foreach ($megas as $mega)
                                        <option value="{{ $mega }}">{{ $mega }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-4 mb-2">
                                <label class="mb-1">Rubro madre</label>
                                <select id="f-madre" class="form-control form-control-sm"
                                        wire:model.live="selectedMadre"
                                        @disabled(empty($selectedMega))>
                                    <option value="">-- Seleccione Rubro madre --</option>
                                    @foreach ($madres as $madre)
                                        <option value="{{ $madre }}">{{ $madre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-4 mb-2">
                                <label class="mb-1">Subrubro</label>
                                <select id="f-sub" class="form-control form-control-sm"
                                        wire:model.live="selectedSubId"
                                        @disabled(empty($selectedMadre))>
                                    <option value="">-- Seleccione Subrubro --</option>
                                    @foreach ($subs as $op)
                                        <option value="{{ $op['id'] }}">{{ $op['sub'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div id="map" style="height: 500px; width: 100%; min-width: 200px;"></div>
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
                        const markerIconUrl = "https://maps.gstatic.com/mapfiles/api-3/images/spotlight-poi2_hdpi.png";

                        function updateMarkers() {
                            const selectedRubros = Array.from(document.querySelectorAll('input.rubro-checkbox:checked')).map(cb => cb
                                .value);

                            markers.forEach(marker => marker.remove());

                            ubicaciones.forEach(record => {
                                if (selectedRubros.includes(record.rubro.subrubro.toLowerCase().trim())) {
                                    let geocodeURL =
                                        `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(record.domicilio_comercio)}&key=${googleApiKey}`;

                                    fetch(geocodeURL)
                                        .then(response => response.json())
                                        .then(georreferencia => {
                                            if (georreferencia.results.length > 0) {
                                                var localizacion = georreferencia.results[0].geometry.location;

                                                // Crea un div para el marcador personalizado pequeño
                                                const el = document.createElement('div');
                                                el.style.backgroundImage = `url('${markerIconUrl}')`;
                                                el.style.width = '30px';
                                                el.style.height = '30px';
                                                el.style.backgroundSize = 'contain';
                                                el.style.backgroundRepeat = 'no-repeat';

                                                const marker = new mapboxgl.Marker(el)
                                                    .setLngLat([localizacion.lng, localizacion.lat])
                                                    .addTo(map);

                                                const popup = new mapboxgl.Popup({
                                                        offset: 25
                                                    })
                                                    .setHTML(`
                                                <h3>${record.razon_social}</h3>
                                                <p><strong>Dirección:</strong> ${record.domicilio_comercio}</p>
                                                <p><strong>Rubro:</strong> ${record.rubro.subrubro}</p>
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
