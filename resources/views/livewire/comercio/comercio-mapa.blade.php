<div id="comercio-mapa-root"><!-- ÚNICO ROOT -->
@include('livewire.comercio.form')
  <section class="content">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1 class="m-0 pb-2 border-bottom" style="font-size:2.50rem;">Mapa comercios</h1></div>
          <div class="col-sm-6 text-right">
            <button id="btnAddMode" type="button" class="btn btn-sm btn-primary">
              <i class="fas fa-map-pin mr-1"></i> Agregar comercio
            </button>
          </div>
        </div>

        {{-- Filtros --}}
        <div class="card mb-3" id="filtros-card">
          <div class="card-header d-flex align-items-center justify-content-between py-2">
            <strong class="mb-0">Filtros</strong>
            <button id="btnToggleFilters" type="button" class="btn btn-sm btn-outline-secondary">
              <i id="icoToggleFilters" class="fas fa-chevron-up"></i>
            </button>
          </div>

          <div class="card-body py-2" id="filtros-body">
            <div class="mb-2">
              <div class="form-row">
                <div class="form-group col-md-3 mb-2">
                  <label class="mb-1">Barrio</label>
                  <select class="form-control form-control-sm" wire:model.live="selectedBarrio">
                    <option value="">-- Todos --</option>
                    @foreach($barrios as $b)
                      <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="form-group col-md-3 mb-2">
                  <label class="mb-1">Estado</label>
                  <select class="form-control form-control-sm" wire:model.live="selectedEstado">
                    <option value="">-- Todos --</option>
                    @foreach(\App\Livewire\Comercio\Ubicaciones::estadoLabels() as $value => $label)
                      <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="form-group col-md-6 mb-2 position-relative">
                  <label class="mb-1">Nombre de fantasía</label>
                  <input type="text" class="form-control form-control-sm"
                         placeholder="Escribí para buscar (mín. 2 letras)"
                         wire:model.live.debounce.300ms="fantasiaQuery" />
                  @if(!empty($fantasiaQuery) && count($fantasiaSuggestions) > 0)
                    <ul class="list-group position-absolute w-50" style="z-index:1000;">
                      @foreach($fantasiaSuggestions as $sug)
                        <li class="list-group-item list-group-item-action p-1"
                            style="cursor:pointer"
                            wire:click="$set('fantasiaQuery','{{ addslashes($sug) }}')">
                          {{ $sug }}
                        </li>
                      @endforeach
                    </ul>
                  @endif
                </div>
              </div>
            </div>

            {{-- Rubro (combo buscable) + Nomenclatura --}}
            <div class="mb-3">
              <div class="form-row">
                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1">Rubro General</label>
                  <select class="form-control form-control-sm" wire:model.live="rubroGeneral">
                      <option value="">-- Todos --</option>
                      <option value="ALOJAMIENTO">Alojamiento</option>
                      <option value="GASTRONOMIA">Gastronomía</option>
                      <option value="SERVICIOS">Servicios</option>
                      <option value="COMERCIO">Comercio</option>
                      <option value="AGRO / PRODUCCION">Agro / Producción</option>
                      <option value="OTROS">Otros</option>
                  </select>
                </div>

                <div class="form-group col-md-4 mb-2" wire:ignore>
                  <label class="mb-1">Rubro</label>
                  <select id="select-map-rubro" class="form-control form-control-sm">
                    <option value="">-- Todos --</option>
                    @foreach($rubroOpts as $op)
                      <option value="{{ $op['id'] }}">{{ $op['subrubro'] }}</option>
                    @endforeach
                  </select>
                </div>


                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1">Nomenclatura catastral</label>
                  <input class="form-control form-control-sm"
                         list="nomen-list"
                         placeholder="Escribí la nomenclatura…"
                         wire:model.live.debounce.300ms="selectedNomen" />
                  <datalist id="nomen-list">
                    @foreach($nomenOpts as $n)
                      <option value="{{ $n }}"></option>
                    @endforeach
                  </datalist>
                </div>

                <div class="form-group col-md-3 mb-2">
                  <label class="mb-1 d-block">Situación</label>
                  <div class="form-check">
                    <input id="chk-claus" type="checkbox" class="form-check-input" wire:model.live="solo_clausurados">
                    <label for="chk-claus" class="form-check-label">Sólo clausurados</label>
                  </div>
                </div>

                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1 d-block">Capas</label>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="toggleBarrios">
                    <label class="form-check-label" for="toggleBarrios">Barrios</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="toggleCatastro" checked>
                    <label class="form-check-label" for="toggleCatastro">Catastro</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="toggleCpu">
                    <label class="form-check-label" for="toggleCpu">CPU</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div id="map" wire:ignore style="height:520px;width:100%;min-width:200px;"></div>
          </div>
        </div>

      </div>
    </div>
  </section>

</div>

@push('styles')
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
  <style>
    #filtros-body { transition: height .18s ease, opacity .18s ease; }
    .mapboxgl-popup-content { padding:0!important;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.18);min-width:260px;}
    .popup-card { font-family: system-ui,-apple-system,Segoe UI,Roboto,Arial; }
    .popup-title { display:flex;font-size:18px;align-items:center;gap:.5rem;background:#0d6efd;color:#fff;padding:.6rem .8rem;font-weight:600;}
    .popup-row { display:grid;grid-template-columns:20px 1fr;gap:.6rem;padding:.55rem .8rem;border-top:1px solid #f0f1f3;font-size:.95rem;align-items:start;}
    .popup-row i { opacity:.7;margin-top:.15rem;}
  </style>
@endpush

@push('scripts')
  <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
  <script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
<script>
  // === CONFIG / TOKENS ===
  mapboxgl.accessToken = @json(config('services.mapbox.token'));
  const googleApiKey   = @json(config('services.google.maps_key'));
  if (!mapboxgl.accessToken) { console.error('Falta MAPBOX_TOKEN en .env / config.'); }

  const normNom = (s)=> String(s||'').replace(/\s+/g,'').toUpperCase();

  const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v12',
    center: [-71.53, -41.9645],
    zoom: 14
  });

  document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('select-map-rubro');

    sel.addEventListener('change', function () {
      @this.set('selectedRubroId', this.value || null);
    });


  });

  // ======== Helpers comunes ========
  const sleep = (ms)=>new Promise(r=>setTimeout(r,ms));
  const esc = (s)=>String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#39;");

  // Minimizar filtros (idempotente y a prueba de re-renders)
  function bindCollapsibleFilters(){
    const KEY='map.filters.collapsed';
    const body=document.getElementById('filtros-body');
    const btn=document.getElementById('btnToggleFilters');
    const ico=document.getElementById('icoToggleFilters');
    if(!body||!btn||!ico) return;
    if(btn._bound) return; btn._bound = true;

    let collapsed = true; try{ if(localStorage.getItem(KEY)==='0') collapsed=false }catch{}
    const apply = (v)=>{
      body.style.display = v ? 'none' : '';
      ico.classList.toggle('fa-chevron-up', !v);
      ico.classList.toggle('fa-chevron-down', v);
      try{ localStorage.setItem(KEY, v ? '1':'0'); }catch{}
      setTimeout(()=>{ try{ map.resize(); }catch{} }, 120);
    };
    apply(collapsed);
    btn.addEventListener('click',()=>{ collapsed = !collapsed; apply(collapsed); });
  }

  // Re-vincular después de render de Livewire
  document.addEventListener('livewire:init', () => {
    Livewire.hook('message.processed', bindCollapsibleFilters);
    bindCollapsibleFilters();
  });

  // ======== Fuentes y capas ========
  let GEO_CATASTRO=null, GEO_CPU=null, NOM_KEY=null, CPU_NAME_KEY='CPU_NOMBRE', CPU_CODE_KEY='CPU_COD';

  const detectNomenKey=(fc)=>{
    const p = fc?.features?.[0]?.properties || {};
    const keys = Object.keys(p);
    const cand = ['NOMEN','NOMENC','NOMENCLATURA','RefName','refname','nomenclatura'];
    for(const k of cand) if(keys.includes(k)) return k;
    const f = keys.find(k=>k.toLowerCase().includes('nomen')); 
    return f || 'NOMEN';
  };

  function addTextLayer(id, sourceId, textExpr, paint={}){
    if(map.getLayer(id)) return;
    map.addLayer({
      id, type: 'symbol', source: sourceId,
      layout: { 'text-field': textExpr, 'text-size': 12, 'text-allow-overlap': false },
      paint: Object.assign({ 'text-color': '#111', 'text-halo-color': '#fff', 'text-halo-width': 1.2 }, paint)
    });
  }

  function setLayerVisibility(prefix, visible){
    const v = visible ? 'visible' : 'none';
    ['fill','line','text'].forEach(sfx=>{
      const id = `${prefix}-${sfx}`;
      if(map.getLayer(id)) map.setLayoutProperty(id, 'visibility', v);
    });
  }

  // ======== Carga inicial del mapa ========
  map.on('load', async () => {
    // Limpia POIs que molestan
    (map.getStyle()?.layers||[]).forEach(l=>{
      const id = l.id || '';
      if(id.includes('poi') || id.includes('place')){
        try{ map.setLayoutProperty(id,'visibility','none'); }catch{}
      }
    });

    // --- BARRIOS
    map.addSource('barrios-src', { type:'geojson', data:'/geo/BARRIOS1.json' });
    map.addLayer({ id:'barrios-fill', type:'fill', source:'barrios-src', paint:{ 'fill-color':'#0080ff', 'fill-opacity':0.12 } });
    map.addLayer({ id:'barrios-line', type:'line', source:'barrios-src', paint:{ 'line-color':'#0080ff', 'line-width':1 } });
    addTextLayer('barrios-text', 'barrios-src', ['get', 'BARRIO']);

    // --- CATASTRO
    GEO_CATASTRO = await fetch('/geo/CATASTRO_GEO.json').then(r=>r.json());
    NOM_KEY = detectNomenKey(GEO_CATASTRO);
    map.addSource('catastro-src', { type:'geojson', data:'/geo/CATASTRO_GEO.json' });
    map.addLayer({ id:'catastro-fill', type:'fill', source:'catastro-src', paint:{ 'fill-color':'#ff8800', 'fill-opacity':0.08 } });
    map.addLayer({ id:'catastro-line', type:'line', source:'catastro-src', paint:{ 'line-color':'#ff8800', 'line-width':1 } });
    addTextLayer('catastro-text', 'catastro-src', ['get', NOM_KEY], { 'text-color':'#b06000' });

    // Highlight de catastro
    map.addSource('catastro-hl-src',{ type:'geojson', data:{ type:'FeatureCollection', features:[] }});
    map.addLayer({ id:'catastro-hl-fill', type:'fill', source:'catastro-hl-src', paint:{ 'fill-color':'#ff0000','fill-opacity':0.20 } });
    map.addLayer({ id:'catastro-hl-line', type:'line', source:'catastro-hl-src', paint:{ 'line-color':'#ff0000','line-width':2 } });

    // --- CPU (apagado por defecto, archivo CPU_MEB.json)
    GEO_CPU = await fetch('/geo/CPU_MEB.json').then(r=>r.json()).catch(()=>null);
    if (GEO_CPU){
      map.addSource('cpu-src', { type:'geojson', data:'/geo/CPU_MEB.json' });
      map.addLayer({ id:'cpu-fill', type:'fill', source:'cpu-src', paint:{ 'fill-color':'#00aa88', 'fill-opacity':0.10 } });
      map.addLayer({ id:'cpu-line', type:'line', source:'cpu-src', paint:{ 'line-color':'#00aa88', 'line-width':1 } });
      // etiqueta: CPU_NOMBRE (o CPU_COD como fallback)
      addTextLayer('cpu-text', 'cpu-src', ['coalesce', ['get', CPU_NAME_KEY], ['get', CPU_CODE_KEY]], { 'text-color':'#0a6' });
    }

    applyToggles(); // respeta el estado de los checkboxes
  });

  // ======== Toggles de capas (resilientes) ========
  function applyToggles(){
    const barriosOn  = document.getElementById('toggleBarrios')?.checked === true;
    const catastroOn = document.getElementById('toggleCatastro')?.checked !== false; // por defecto ON
    const cpuOn      = document.getElementById('toggleCpu')?.checked === true;

    setLayerVisibility('barrios', barriosOn);
    setLayerVisibility('catastro', catastroOn);
    setLayerVisibility('catastro-hl', catastroOn); // highlight acompaña catastro
    setLayerVisibility('cpu', cpuOn);
  }
  ['toggleBarrios','toggleCatastro','toggleCpu'].forEach(id=>{
    document.getElementById(id)?.addEventListener('change', applyToggles);
  });

  // ======== Puntos de comercios ========
  const popup = new mapboxgl.Popup({ closeButton:true, offset:16 });

  function toGeo(list){
    const feats = [];
    for (const r of (list||[])){
      let coords = null;

      if (r.nomen && GEO_CATASTRO && NOM_KEY) {
        const feat = (GEO_CATASTRO.features||[]).find(f => normNom(f.properties?.[NOM_KEY]) === normNom(r.nomen));
        if (feat) {
          try {
            const cm = turf.centerOfMass(feat);
            coords = cm?.geometry?.coordinates || null;
          } catch (_) {}
        }
      }

      // Si no hay nomen, usar lat/lng
      if (!coords){
        const lat = parseFloat(r.lat ?? r.latitud);
        const lng = parseFloat(r.lng ?? r.longitud);
        if (Number.isFinite(lat) && Number.isFinite(lng)){
          coords = [lng,lat];
        }
      }

      if (!coords) continue; // si no hay nada, no agrego el punto

      feats.push({
        type:'Feature',
        geometry:{ type:'Point', coordinates: coords },
        properties:{
          id: r.id,
          nombre: r.nombre_comercial ?? r.razon_social ?? '',
          direccion: r.domicilio_comercio ?? '',
          nomen: r.nomen ?? '',
          barrio: r.barrio ?? '-',
          estado: r.estado ?? '-',
          rubro: r?.rubro?.subrubro ?? ''
        }
      });
    }
    return { type:'FeatureCollection', features: feats };
  }

  const SHOW_URL_BASE = @json(route('comercio.data', ['ubicacion' => '__ID__']));
  const showUrl = (id) => SHOW_URL_BASE.replace('__ID__', String(id));


  // Mapeo de estados internos a etiquetas visibles
  const estadoLabels = {
    entramite: "021 - En trámite",
    vigente:   "Alta",
    irregular: "032 - Irregular",
    baja:      "Baja"
  };

  function popupHTML(p) {
    const estado = estadoLabels[p.estado] || p.estado || "-";

    return `
        <div class="popup-card">
          <div class="popup-title">
            <i class="fas fa-store"></i>
            <a href="${showUrl(p.id)}" class="text-white" style="text-decoration:none;">
              <span>${esc(p.nombre || '')}</span>
            </a>
          </div>

          <div class="popup-row">
            ${p.direccion
              ? `<i class="fas fa-map-marker-alt"></i><div>${esc(p.direccion)}</div>`
              : `<i class="fas fa-vector-square"></i><div><strong>Nomenclatura:</strong> ${esc(p.nomen || '(sin datos)')}</div>`
            }
          </div>

          <div class="popup-row">
            <i class="fas fa-tags"></i><div>${esc(p.rubro || '-')}</div>
          </div>

          <div class="popup-row">
            <i class="fas fa-city"></i><div>${esc(p.barrio || '-')}</div>
          </div>

          <div class="popup-row">
            <i class="fas fa-clipboard-check"></i><div>${estado}</div>
          </div>
        </div>
      `;
  }


  let srcReady = false;
  map.on('load', () => {
    map.addSource('comercios-src', { type:'geojson', data: toGeo(@json($ubicaciones)) });
    map.addLayer({ id:'comercios-points', type:'circle', source:'comercios-src',
      paint:{ 'circle-color':'#1e90ff','circle-radius':7,'circle-stroke-color':'#fff','circle-stroke-width':2 }});
    map.on('click','comercios-points',(e)=>{if(addMode) return;
      const f = e.features[0];
      const p = f.properties;

      popup.setLngLat(f.geometry.coordinates).setHTML(popupHTML(p)).addTo(map);

      // Si no hay dirección pero sí nomen, resaltar catastro
      if (!p.direccion && p.nomen && GEO_CATASTRO && NOM_KEY){
        const feats = (GEO_CATASTRO.features||[]).filter(ff => (ff.properties?.[NOM_KEY]??'') === p.nomen);
        const hl = map.getSource('catastro-hl-src');
        if (hl){ hl.setData({ type:'FeatureCollection', features: feats }); }
        if (feats.length) fitToFeaturesBounds({ type:'FeatureCollection', features: feats });
      }
    });
    map.on('mouseenter','comercios-points',()=>map.getCanvas().style.cursor='pointer');
    map.on('mouseleave','comercios-points',()=>map.getCanvas().style.cursor='');
    srcReady = true;
    applyToggles();
  });

  function placeCadastreBehindPoints() {
    const points = 'comercios-points';
    const layers = [
      'catastro-text',
      'catastro-line',
      'catastro-fill',
      'catastro-hl-line',
      'catastro-hl-fill',
    ];
    layers.forEach(id => {
      if (map.getLayer(id) && map.getLayer(points)) {
        // mueve 'id' justo antes de 'comercios-points' (queda por debajo)
        try { map.moveLayer(id, points); } catch (_) {}
      }
    });
  }

  placeCadastreBehindPoints();

  map.on('styledata', () => { placeCadastreBehindPoints(); });

  // Zoom al cambiar los resultados o una nomenclatura
  function fitToFeaturesBounds(fc){
    const feats = fc?.features || [];
    if (!feats.length) return;
    const b = new mapboxgl.LngLatBounds();
    feats.forEach(f=>{
      const g = f.geometry||{};
      if(g.type==='Point'){ b.extend(g.coordinates); }
      if(g.type==='Polygon'){ (g.coordinates[0]||[]).forEach(c=>b.extend(c)); }
      if(g.type==='MultiPolygon'){ g.coordinates.forEach(poly => (poly[0]||[]).forEach(c=>b.extend(c))); }
    });
    try{ map.fitBounds(b, { padding: 40, maxZoom: 17, duration: 600 }); }catch{}
  }

  window.addEventListener('ubicacionesUpdated', (ev) => {
      const list = ev.detail?.ubicaciones ?? [];
      const nom  = ev.detail?.selectedNomen ?? '';
      const data = toGeo(list);

      if (srcReady){
          const src = map.getSource('comercios-src');
          if (src) src.setData(data);

          // 👉 ZOOM SIEMPRE que haya puntos, salvo cuando la búsqueda fue por nomen
          if (!nom && data.features.length > 0) {
              fitToFeaturesBounds(data);
          }
      }

      // 👉 Si hay nomen: resaltar y hacer zoom
      if (nom && GEO_CATASTRO && NOM_KEY){
          const feats = (GEO_CATASTRO.features || [])
              .filter(f => normNom(f.properties?.[NOM_KEY]) === normNom(nom));

          const hl = map.getSource('catastro-hl-src');
          if (hl) hl.setData({ type:'FeatureCollection', features: feats });

          if (feats.length > 0){
              fitToFeaturesBounds({ type:'FeatureCollection', features: feats });
          }
      }
  });


  let addMode = false, addMarker = null;
  document.getElementById('btnAddMode')?.addEventListener('click', () => {
    addMode = !addMode;
    const btn = document.getElementById('btnAddMode');
    btn.classList.toggle('btn-success', addMode);
    btn.classList.toggle('btn-primary', !addMode);

    if (addMode) {
      btn.innerHTML = '<i class="fas fa-location-dot mr-1"></i> Click en el mapa para crear';
    } else {
      btn.innerHTML = '<i class="fas fa-map-pin mr-1"></i> Agregar comercio';
      // limpiar marker rojo si estaba
      if (addMarker) {
        addMarker.remove();
        addMarker = null;
      }
    }
  });


  map.on('click', async (e) => {
    if (!addMode) return;

    const { lng, lat } = e.lngLat;

    // 1) Tomar barrio y nomenclatura desde las capas visibles
    const featBarrio = map.queryRenderedFeatures(e.point, { layers: ['barrios-fill'] })[0];
    const barrio = featBarrio?.properties?.BARRIO ?? '';

    // NOM_KEY la definiste al cargar CATASTRO (detecta la clave de nomenclatura)
    const featCat = map.queryRenderedFeatures(e.point, { layers: ['catastro-hl-fill','catastro-fill','catastro-line'] })[0];
    const nomen = featCat?.properties?.[NOM_KEY ?? 'RefName'] ?? '';

    // 2) (Opcional) Reverse geocode con tu Google API si la tenés
    let direccion = '';
    try {
      if (window.googleApiKey) {
        const url = `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${googleApiKey}&language=es-AR&region=ar`;
        const r = await fetch(url); const j = await r.json();
        direccion = j?.results?.[0]?.formatted_address ?? '';
      }
    } catch (_) { /* silencio */ }

    // 3) Marcador + popup mostrando Dirección / Barrio / Nomenclatura
    if (addMarker) addMarker.remove();
    addMarker = new mapboxgl.Marker({ color: '#d81b60' }).setLngLat([lng, lat]).addTo(map);

      const html = `
        <div class="popup-card" style="min-width:260px">
          <div class="popup-title"><i class="fas fa-location-dot"></i><span>Agregar comercio</span></div>
          <div class="popup-row"><i class="fas fa-city"></i><div><strong>Barrio:</strong> ${esc(barrio || '(sin datos)')}</div></div>
          <div class="popup-row"><i class="fas fa-vector-square"></i><div><strong>Nomenclatura:</strong> ${esc(nomen || '(sin datos)')}</div></div>
        <button id="btnConfirmCreateHere" class="btn btn-sm btn-primary w-100">
          <i class="fas fa-plus mr-1"></i> Abrir formulario
        </button>
      </div>`;
    new mapboxgl.Popup({ offset: 12 }).setLngLat([lng, lat]).setHTML(html).addTo(map);

    // 4) Llamada a Livewire CON los tres argumentos
    setTimeout(() => {
      const b = document.getElementById('btnConfirmCreateHere');
      if (!b) return;
      b.onclick = () => {
        @this.call('crearDesdeMapaConDatos', direccion, barrio, nomen, lat, lng);
      };
    }, 0);
  });

  Livewire.emit('open-create-from-map', {
    lat: markerLat,
    lng: markerLng,
    direccion: direccionElegida,
    barrio: barrioElegido,
    nomen: nomenElegida
  });


  function escapeHtml(s) {
    return String(s)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');
  }

</script>
@endpush
