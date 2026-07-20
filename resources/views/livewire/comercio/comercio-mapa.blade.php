<div id="comercio-mapa-root" class="commerce-map-page"><!-- ÚNICO ROOT -->
@if($formKey !== '')
  @include('livewire.comercio.form')
@endif
  <section class="content">
    <div class="content-header map-page-header">
      <div class="container-fluid">
        <div class="map-page-hero mb-3">
          <div>
            <div class="map-page-eyebrow"><i class="fas fa-location-dot mr-1"></i> Comercio municipal</div>
            <h1 class="map-page-title">Mapa de comercios</h1>
            <p class="map-page-subtitle mb-0">Explorá ubicaciones, aplicá filtros y consultá cada comercio desde el mapa.</p>
          </div>
          <div class="map-page-actions">
            <button id="btnAddMode" type="button" class="btn btn-sm btn-primary">
              <i class="fas fa-map-pin mr-1"></i> Agregar comercio
            </button>
          </div>
        </div>

        {{-- Filtros --}}
        <div class="card map-filter-card mb-3" id="filtros-card">
          <div class="card-header d-flex align-items-center justify-content-between py-2">
            <strong class="mb-0"><i class="fas fa-sliders-h mr-2"></i>Filtros del mapa</strong>
            <button id="btnToggleFilters" type="button" class="btn btn-sm btn-outline-secondary">
              <i id="icoToggleFilters" class="fas fa-chevron-down"></i>
            </button>
          </div>

          <div class="card-body py-2" id="filtros-body" style="display:none;opacity:0">
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
                    @foreach($estados as $value => $label)
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
                      <option value="">-- Todos los rubros --</option>
                      <option value="ALOJAMIENTO DE ALQUILER TURISTICO">Alojamiento de alquiler turistico</option>
                      <option value="GASTRONOMIA">Gastronomía</option>
                      <option value="CENTRO DE ESTETICA Y SPA">Centro de esterica y spa</option>
                      <option value="LAVADEROS DE AUTOS">Lavaderos de autos</option>
                      <option value="LUBRICENTROS">Lubricentros</option>
                      <option value="TALLER DEL AUTOMOTOR">Taller del automotor</option>
                      <option value="SALUD">Salud</option>
                      <option value="GIMNASIOS">Gimnasios</option>
                      <option value="ALQUILER DE CANCHAS">Alquiler de canchas</option>
                      <option value="VENTA DE ARTESANIAS Y PRODUCTOS REGIONALES">Venta de artesanias y productos regionales</option>
                      <option value="SALA DE ELABORACION">Sala de elaboracion</option>
                      <option value="COCINA DOMICILIARIA">Cocina domiciliaria</option>
                      <option value="SERVICIOS">Servicios</option>
                      <option value="COMERCIO">Comercio</option>
                      <option value="AGRO / PRODUCCION">Agro/Produccion</option>
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

                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1 d-block">Situación</label>
                  <div class="form-check form-check-inline">
                    <input id="chk-claus" type="checkbox" class="form-check-input" wire:model.live="solo_clausurados">
                    <label for="chk-claus" class="form-check-label">Sólo clausurados</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input id="chk-baja-temporal" type="checkbox" class="form-check-input" wire:model.live="solo_bajas_temporarias">
                    <label for="chk-baja-temporal" class="form-check-label">Baja temporaria</label>
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

                <div class="form-group col-md-4 mb-2 d-flex align-items-end justify-content-md-end">
                  <button type="button" class="btn btn-outline-secondary btn-sm map-clear-filters"
                          wire:click="limpiarFiltrosMapa" wire:loading.attr="disabled" wire:target="limpiarFiltrosMapa">
                    <i class="fas fa-eraser mr-1"></i>
                    <span wire:loading.remove wire:target="limpiarFiltrosMapa">Limpiar filtros</span>
                    <span wire:loading wire:target="limpiarFiltrosMapa">Limpiando…</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card map-shell-card">
          <div class="card-header map-shell-header d-flex align-items-center justify-content-between">
            <strong><i class="fas fa-map-marked-alt mr-2"></i>Ubicaciones comerciales</strong>
            <div class="d-flex align-items-center map-shell-actions">
              <button id="btnMapFiltersHeader" type="button" class="btn btn-sm map-header-filter-button">
                <i class="fas fa-sliders-h mr-1"></i> Abrir filtros
              </button>
              <span class="map-result-count"><i class="fas fa-store mr-1"></i>{{ count($ubicaciones) }} comercios</span>
            </div>
          </div>
          <div class="card-body map-shell-body">
            <div id="map" wire:ignore></div>
            <div class="map-legend" aria-label="Referencias del mapa">
              <span><i class="map-dot dot-active"></i>021/90</span>
              <span><i class="map-dot dot-irregular"></i>032/01</span>
              <span><i class="map-dot dot-040"></i>040/25</span>
              <span><i class="map-dot dot-closed"></i>Clausurado</span>
              <span><i class="map-dot dot-temporary"></i>Baja temporaria</span>
            </div>
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
  map.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'top-right');
  map.addControl(new mapboxgl.FullscreenControl(), 'top-right');

  document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('select-map-rubro');

    sel.addEventListener('change', function () {
      @this.set('selectedRubroId', this.value || null);
    });

    setTimeout(() => {
      document.getElementById('map')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      try { map.resize(); } catch {}
    }, 350);


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

    let collapsed = true;
    const apply = (v)=>{
      body.style.display = v ? 'none' : '';
      ico.classList.toggle('fa-chevron-up', !v);
      ico.classList.toggle('fa-chevron-down', v);
      try{ localStorage.setItem(KEY, v ? '1':'0'); }catch{}
      setTimeout(()=>{ try{ map.resize(); }catch{} }, 120);
    };
    apply(collapsed);
    btn.addEventListener('click',()=>{ collapsed = !collapsed; apply(collapsed); });
    const headerFilter=document.getElementById('btnMapFiltersHeader');
    if(headerFilter && !headerFilter._bound){
      headerFilter._bound=true;
      headerFilter.addEventListener('click',()=>{
        collapsed=false;
        apply(false);
        document.getElementById('filtros-card')?.scrollIntoView({behavior:'smooth',block:'center'});
      });
    }
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
          situacion: r.situacion ?? '',
          baja_temporaria: r.baja_temporaria ? 1 : 0,
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
    entramite:   "021/90",
    vigente:     "021/90",
    irregular:   "032/01",
    "040":       "040/25",
    baja:        "Baja",
    baja_oficio: "Baja de oficio",
    sin_efecto:  "Expediente sin efecto"
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
      paint:{
        'circle-color': [
          'case',
          ['==', ['get', 'baja_temporaria'], 1], '#7b8794',
          ['==', ['get', 'situacion'], 'clausurado'], '#d94b51',
          ['in', ['get', 'estado'], ['literal', ['baja','baja_oficio','sin_efecto']]], '#5f6f7f',
          ['==', ['get', 'estado'], '040'], '#277fb5',
          ['==', ['get', 'estado'], 'irregular'], '#e6a31a',
          '#17845f'
        ],
        'circle-radius': ['interpolate', ['linear'], ['zoom'], 11, 4.5, 14, 7, 17, 10],
        'circle-stroke-color':'#fff',
        'circle-stroke-width':2,
        'circle-opacity':.94
      }});
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

  window.addEventListener('mapFiltersCleared', () => {
    const rubro = document.getElementById('select-map-rubro');
    if (rubro?.tomselect) rubro.tomselect.clear(true);
    else if (rubro) rubro.value = '';

    const hl = map.getSource('catastro-hl-src');
    if (hl) hl.setData({ type:'FeatureCollection', features:[] });
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
@push('styles')
<style>

  /* ---------- General ---------- */
  .commerce-map-page .card {
    border-radius: 0.7rem !important;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #e2e2e2 !important;
  }

  .commerce-map-page .card-header {
    font-weight: 600;
    font-size: 0.95rem;
    background: #f7f9fb !important;
    border-bottom: 1px solid #e5e5e5 !important;
  }

  .commerce-map-page .card-body {
    background: #ffffff;
    padding-top: 1.15rem !important;
  }

  .commerce-map-page .titulo-comercio {
    font-size: 1.9rem !important;
    font-weight: 800 !important;
    letter-spacing: -0.5px;
  }

  /* ---------- Etiquetas / Categorías ---------- */
  .commerce-map-page .badge {
    padding: 0.45em 0.65em !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    border-radius: 0.35rem !important;
  }

  .commerce-map-page .badge-light {
    background: #f2f2f2 !important; 
    color: #555 !important; 
  }

  .commerce-map-page .badge-success { background-color: #2ecc71 !important; }
  .commerce-map-page .badge-info    { background-color: #3498db !important; }
  .commerce-map-page .badge-warning { background-color: #f1c40f !important; color:#333 !important; }
  .commerce-map-page .badge-danger  { background-color: #e74c3c !important; }

  /* ---------- Títulos pequeños ---------- */
  .commerce-map-page .text-muted.small {
    font-size: 0.72rem !important;
    letter-spacing: 0.3px;
    text-transform: uppercase;
  }

  .commerce-map-page .font-weight-bold {
    font-size: 0.92rem;
  }

  /* ---------- Encabezado general ---------- */
  .commerce-map-page .content-header {
    border-bottom: 1px solid #e5e5e5;
    background: linear-gradient(to right, #ffffff, #fafafa);
    padding-bottom: 1rem;
    padding-top: 0.5rem;
  }

  /* ---------- Botonera derecha ---------- */
  .commerce-map-page .btn-group .btn {
    border-radius: 0.4rem !important;
    font-size: 0.78rem;
  }

  .commerce-map-page .btn-primary {
    background: #4a6cf7 !important;
    border-color: #4a6cf7 !important;
  }

  .commerce-map-page .btn-danger {
    background: #e74c3c !important;
    border-color: #e74c3c !important;
  }

  .commerce-map-page .btn-secondary {
    background: #bdc3c7 !important;
    border-color: #bdc3c7 !important;
  }

  /* ---------- Separadores ---------- */
  .commerce-map-page hr.my-2 {
    border-top: 1px solid #ddd !important;
  }

  /* ---------- Tablas ---------- */
  .commerce-map-page table.table {
    border-radius: 0.5rem !important;
    overflow: hidden;
  }

  .commerce-map-page .table thead th {
    background: #f7f9fb !important;
    font-weight: 600 !important;
  }

  .commerce-map-page .table tbody tr td {
    font-size: 0.82rem !important;
  }

  /* ---------- Badges de documentación ---------- */
  .commerce-map-page .docs-box {
    transition: 0.2s;
  }

  .commerce-map-page .docs-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
  }

  .commerce-map-page {
    --map-navy: #17385f;
    --map-blue: #286da8;
    --map-green: #17845f;
    --map-ink: #20344b;
    --map-muted: #6f7f91;
    --map-line: #dce6ef;
    color: var(--map-ink);
  }
  .commerce-map-page .content { padding-bottom: 1.5rem; }
  .commerce-map-page .map-page-header {
    padding: 1rem 0 0;
    border: 0;
    background: transparent;
  }
  .commerce-map-page .map-page-hero {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    padding: 1.35rem 1.55rem;
    border-radius: 1rem;
    background: linear-gradient(125deg,#17385f 0%,#245f91 64%,#268b8b 125%);
    color: #fff;
    box-shadow: 0 16px 35px rgba(23,56,95,.18);
  }
  .commerce-map-page .map-page-hero::after {
    content: '';
    position: absolute;
    width: 240px;
    height: 240px;
    top: -145px;
    right: -55px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    pointer-events: none;
  }
  .commerce-map-page .map-page-eyebrow {
    margin-bottom: .3rem;
    color: #bfe2f3;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .09em;
    text-transform: uppercase;
  }
  .commerce-map-page .map-page-title {
    margin: 0 0 .25rem;
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.12;
    letter-spacing: -.035em;
  }
  .commerce-map-page .map-page-subtitle { color: rgba(255,255,255,.76); font-size: .9rem; }
  .commerce-map-page .map-page-actions { position: relative; z-index: 1; margin-left: auto; }
  .commerce-map-page .map-page-actions .btn {
    min-height: 40px;
    padding: .45rem .9rem;
    border: 1px solid rgba(255,255,255,.55) !important;
    border-radius: .65rem !important;
    background: #fff !important;
    color: var(--map-navy) !important;
    font-weight: 700;
    box-shadow: 0 5px 14px rgba(8,30,50,.15);
  }
  .commerce-map-page .map-page-actions .btn:hover { transform: translateY(-1px); }
  .commerce-map-page .card {
    border: 1px solid var(--map-line) !important;
    border-radius: 1rem !important;
    box-shadow: 0 6px 20px rgba(30,58,86,.07);
  }
  .commerce-map-page .card-header {
    min-height: 52px;
    padding: .75rem 1rem !important;
    border-bottom: 1px solid var(--map-line) !important;
    background: linear-gradient(135deg,#f9fbfd,#eef5fa) !important;
    color: var(--map-navy);
    font-weight: 700;
  }
  .commerce-map-page .map-filter-card .card-body { padding: 1rem 1.1rem .35rem !important; }
  .commerce-map-page .map-filter-card label {
    color: #5f7184;
    font-size: .72rem;
    font-weight: 750;
    letter-spacing: .025em;
  }
  .commerce-map-page .map-filter-card .form-control,
  .commerce-map-page .map-filter-card .ts-control {
    min-height: 36px;
    border-color: #cedbe6;
    border-radius: .55rem;
    background: #fbfdff;
  }
  .commerce-map-page .map-filter-card .form-control:focus,
  .commerce-map-page .map-filter-card .ts-control.focus {
    border-color: #74a9d2;
    box-shadow: 0 0 0 3px rgba(40,109,168,.1);
  }
  .commerce-map-page .map-clear-filters {
    min-height: 36px;
    padding: .42rem .85rem;
    border-color: #c4d2de;
    border-radius: .58rem;
    background: #fff;
    color: #50667b;
    font-weight: 700;
  }
  .commerce-map-page .map-clear-filters:hover {
    border-color: var(--map-blue);
    background: var(--map-blue);
    color: #fff;
  }
  .commerce-map-page #btnToggleFilters {
    width: 34px;
    height: 34px;
    margin-left: auto;
    border-radius: .6rem;
  }
  .commerce-map-page .map-result-count {
    padding: .42rem .72rem;
    border-radius: 999px;
    background: #dcecf7;
    color: #245f91;
    font-size: .74rem;
    font-weight: 800;
  }
  .commerce-map-page .map-shell-body { position: relative; padding: .55rem !important; background: #eaf1f6; }
  .commerce-map-page .map-shell-actions { gap:.55rem; }
  .commerce-map-page .map-header-filter-button { border:1px solid #b8d2e4 !important; border-radius:9px !important; background:#e7f2f9 !important; color:#205f8d !important; font-weight:800; }
  .commerce-map-page .map-header-filter-button:hover { border-color:#286da8 !important; background:#286da8 !important; color:#fff !important; }
  .commerce-map-page select.form-control {
    min-height:40px !important; padding: .42rem 2.2rem .42rem .7rem !important;
    appearance:none; border:1px solid #c6d6e2 !important; border-radius:.65rem !important;
    background-color:#fbfdff !important;
    background-image:linear-gradient(45deg,transparent 50%,#52718a 50%),linear-gradient(135deg,#52718a 50%,transparent 50%) !important;
    background-position:calc(100% - 16px) 17px,calc(100% - 11px) 17px !important;
    background-size:5px 5px,5px 5px !important; background-repeat:no-repeat !important;
    color:#29445d; box-shadow:0 3px 10px rgba(30,58,86,.06);
  }
  .commerce-map-page select.form-control:focus { border-color:#65a2ce !important; box-shadow:0 0 0 3px rgba(40,109,168,.12) !important; }
  .commerce-map-page select.form-control option { padding:.55rem; background:#fff; color:#29445d; }
  .commerce-map-page #map {
    width: 100%;
    min-width: 200px;
    height: min(68vh, 680px);
    min-height: 520px;
    overflow: hidden;
    border: 1px solid #cbd9e4;
    border-radius: .72rem;
  }
  .commerce-map-page .mapboxgl-ctrl-group {
    overflow: hidden;
    border: 1px solid #d6e1e9;
    border-radius: .65rem;
    box-shadow: 0 5px 15px rgba(30,58,86,.13);
  }
  .commerce-map-page .mapboxgl-popup-content { border: 1px solid #dce6ef; }
  .commerce-map-page .popup-title { background: linear-gradient(120deg,#245f91,#268b8b); }
  .commerce-map-page .map-legend {
    position: absolute;
    z-index: 2;
    left: 1rem;
    bottom: 1rem;
    display: flex;
    flex-wrap: wrap;
    gap: .38rem .7rem;
    max-width: calc(100% - 2rem);
    padding: .5rem .7rem;
    border: 1px solid rgba(213,225,234,.95);
    border-radius: .65rem;
    background: rgba(255,255,255,.92);
    color: #42586d;
    font-size: .68rem;
    font-weight: 750;
    box-shadow: 0 6px 18px rgba(25,51,75,.13);
    backdrop-filter: blur(6px);
  }
  .commerce-map-page .map-legend span { display: inline-flex; align-items: center; white-space: nowrap; }
  .commerce-map-page .map-dot {
    width: 9px; height: 9px; margin-right: .3rem;
    border: 1.5px solid #fff; border-radius: 50%;
    box-shadow: 0 0 0 1px rgba(36,62,82,.14);
  }
  .commerce-map-page .dot-active { background: #17845f; }
  .commerce-map-page .dot-irregular { background: #e6a31a; }
  .commerce-map-page .dot-040 { background: #277fb5; }
  .commerce-map-page .dot-closed { background: #d94b51; }
  .commerce-map-page .dot-temporary { background: #7b8794; }
  @media (max-width: 767.98px) {
    .commerce-map-page .map-page-hero { align-items: flex-start; flex-direction: column; padding: 1.15rem; }
    .commerce-map-page .map-page-title { font-size: 1.55rem; }
    .commerce-map-page .map-page-actions { width: 100%; }
    .commerce-map-page .map-page-actions .btn { width: 100%; }
    .commerce-map-page #map { height: 62vh; min-height: 430px; }
    .commerce-map-page .map-shell-header { align-items: flex-start !important; gap: .6rem; flex-wrap: wrap; }
    .commerce-map-page .map-result-count { margin-left: auto; }
  }

</style>
@endpush
