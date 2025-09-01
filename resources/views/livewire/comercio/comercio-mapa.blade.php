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
                            <div id="map" wire:ignore style="height: 500px; width: 100%; min-width: 200px;"></div>
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
                            center: [-71.53, -41.9645], 
                            zoom: 14
                        });

                        map.on('load', function() {
                            const layers = map.getStyle()?.layers ?? [];
                            layers.forEach((layer) => {
                                if (layer.id?.includes('poi') || layer.id?.includes('place')) {
                                    try { map.setLayoutProperty(layer.id, 'visibility', 'none'); } catch {}
                                }
                            });
                        });

                        // 1) que sea reasignable:
                        let ubicaciones = @json($ubicaciones);

                        // 2) pool de marcadores:
                        const markers = [];
                        const markerIconUrl = "https://maps.gstatic.com/mapfiles/api-3/images/spotlight-poi2_hdpi.png";

                        function clearMarkers() {
                            while (markers.length) {
                            const m = markers.pop();
                            try { m.remove(); } catch {}
                            }
                        }

                        async function placeMarker(record) {
                            // Prioridad: usar lat/long si existen
                            let lat = parseFloat(record.latitud);
                            let lng = parseFloat(record.longitud);

                            if (!(Number.isFinite(lat) && Number.isFinite(lng))) {
                            // Fallback: geocodificar una sola vez
                            try {
                                const geocodeURL = `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(record.domicilio_comercio || '')}&key=${googleApiKey}`;
                                const res = await fetch(geocodeURL);
                                const data = await res.json();
                                const loc = data?.results?.[0]?.geometry?.location;
                                if (loc) { lat = loc.lat; lng = loc.lng; }
                            } catch(e) {}
                            }

                            if (!(Number.isFinite(lat) && Number.isFinite(lng))) return;

                            const el = document.createElement('div');
                            el.style.backgroundImage = `url('${markerIconUrl}')`;
                            el.style.width = '30px';
                            el.style.height = '30px';
                            el.style.backgroundSize = 'contain';
                            el.style.backgroundRepeat = 'no-repeat';

                            const marker = new mapboxgl.Marker(el)
                            .setLngLat([lng, lat])
                            .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML(`
                                <h3>${record.razon_social ?? ''}</h3>
                                <p><strong>Dirección:</strong> ${record.domicilio_comercio ?? ''}</p>
                                <p><strong>Rubro:</strong> ${record.rubro?.subrubro ?? ''}</p>
                            `))
                            .addTo(map);

                            markers.push(marker);
                        }

                        async function updateMarkers() {
                            clearMarkers();

                            // Si querés sostener los checkboxes, filtrar sólo si hay alguno tildado.
                            // Si NO usás checkboxes, esto deja pasar todo.
                            const checked = Array.from(document.querySelectorAll('input.rubro-checkbox:checked'))
                            .map(cb => (cb.value || '').toLowerCase().trim());
                            const filterFn = checked.length
                            ? (rec) => checked.includes((rec.rubro?.subrubro || '').toLowerCase().trim())
                            : () => true;

                            // Dibujar
                            for (const record of (ubicaciones || []).filter(filterFn)) {
                            await placeMarker(record);
                            }
                        }

                        // 3) Escuchar actualizaciones desde Livewire (tu $this->dispatch(...))
                        window.addEventListener('ubicacionesUpdated', (ev) => {
                            ubicaciones = ev.detail?.ubicaciones ?? [];
                            updateMarkers();
                        });

                        // 4) Si tenés "seleccionar todo" y/o checkboxes, seguí escuchándolos
                        document.querySelectorAll('input.rubro-checkbox').forEach(checkbox => {
                            checkbox.addEventListener('change', updateMarkers);
                        });
                        const selectAll = document.getElementById('select-all');
                        if (selectAll) {
                            selectAll.addEventListener('change', function() {
                            const isChecked = this.checked;
                            document.querySelectorAll('input.rubro-checkbox').forEach(cb => cb.checked = isChecked);
                            updateMarkers();
                            });
                        }

                        // 5) Primera pinta
                        updateMarkers();
                    </script>
                </div>
            </div>
        </div>
    </div>
</section>
