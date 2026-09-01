<template>
  <div
    class="relative h-full min-h-[450px] w-full overflow-hidden bg-slate-100"
  >
    <div
      ref="mapElement"
      class="h-full min-h-[450px] w-full"
      aria-label="Peta penyaluran Kota Mojokerto"
    />

    <div
      v-if="isLoadingBoundary"
      class="absolute inset-0 z-[500] flex items-center justify-center bg-white/75"
    >
      <div
        class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-slate-600 shadow-lg"
      >
        Memuat batas kecamatan...
      </div>
    </div>

    <div
      v-else-if="boundaryError"
      class="absolute inset-x-4 top-4 z-[500] rounded-xl border border-red-200 bg-red-50 p-4 shadow-lg"
    >
      <p class="text-sm font-semibold text-red-700">
        {{ boundaryError }}
      </p>

      <button
        type="button"
        class="mt-3 rounded-lg bg-red-700 px-3 py-2 text-xs font-bold text-white hover:bg-red-800"
        @click="loadKecamatanBoundaries"
      >
        Muat ulang batas wilayah
      </button>
    </div>

    <div
      v-if="hasSelection && !boundaryError"
      class="pointer-events-none absolute bottom-4 left-1/2 z-[500] -translate-x-1/2 rounded-full bg-slate-950/80 px-4 py-2 text-center text-xs font-semibold text-white shadow-lg"
    >
      Klik area kosong pada peta untuk melepas pilihan
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'

import * as L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import type {
  Feature,
  FeatureCollection,
  Geometry,
} from 'geojson'

import type {
  HeadOfficeKecamatanMetric,
  HeadOfficeKelurahanMetric,
} from '@/types/headOfficeDashboard'

interface Props {
  kecamatans: HeadOfficeKecamatanMetric[]
  kelurahans: HeadOfficeKelurahanMetric[]
  selectedKecamatanId: number | null
  selectedKelurahanId: number | null
}

interface KecamatanBoundaryProperties {
  Kecamatan?: string
  Kode_Wilay?: string
  Kota?: string
}

interface RegionIdentity {
  id: number
  code: string
  name: string
}

interface KecamatanView extends RegionIdentity {
  totalKpm: number
  transactedKpm: number
  pendingKpm: number
  completionPercentage: number
}

interface KelurahanView extends RegionIdentity {
  totalKpm: number
  transactedKpm: number
  pendingKpm: number
  completionPercentage: number
}

type KecamatanFeature = Feature<
  Geometry,
  KecamatanBoundaryProperties
>

type KecamatanFeatureCollection = FeatureCollection<
  Geometry,
  KecamatanBoundaryProperties
>

type UnknownRecord = Record<string, unknown>

const props = defineProps<Props>()

const emit = defineEmits<{
  clear: []
  selectKecamatan: [
    kecamatanId: number,
  ]
  selectKelurahan: [
    payload: {
      kecamatanId: number
      kelurahanId: number
    },
  ]
}>()

const KECAMATAN_GEOJSON_URL =
  'https://services8.arcgis.com/TIDaqFGVtcBJNSoQ/arcgis/rest/services/ADMINKECAMATAN_AR_5K/FeatureServer/0/query?where=1%3D1&outFields=Kecamatan%2CKode_Wilay%2CKota&returnGeometry=true&outSR=4326&geometryPrecision=6&maxAllowableOffset=0.00002&f=geojson'

const MOJOKERTO_CENTER: L.LatLngExpression = [
  -7.4726,
  112.4381,
]

const kelurahanCenters: Record<
  string,
  L.LatLngTuple
> = {
  balongsari: [-7.4569, 112.4452],
  blooto: [-7.4818, 112.4159],
  gedongan: [-7.4668, 112.4391],
  gununggedangan: [-7.4821, 112.4571],
  jagalan: [-7.4661, 112.4354],
  kauman: [-7.4698, 112.4298],
  kedundung: [-7.4729, 112.4614],
  kranggan: [-7.4763, 112.4392],
  magersari: [-7.4698, 112.4498],
  mentikan: [-7.4665, 112.4258],
  meri: [-7.4842, 112.4472],
  miji: [-7.4732, 112.4338],
  prajuritkulon: [-7.4729, 112.4204],
  pulorejo: [-7.4879, 112.4107],
  purwotengah: [-7.4677, 112.4329],
  sentanan: [-7.4707, 112.4369],
  surodinawan: [-7.4853, 112.4242],
  wates: [-7.4557, 112.4591],
}

const mapElement =
  ref<HTMLDivElement | null>(null)

const isLoadingBoundary = ref(false)
const boundaryError =
  ref<string | null>(null)

let map: L.Map | null = null

let boundaryCollection:
  KecamatanFeatureCollection | null = null

let kecamatanLayer:
  L.GeoJSON<KecamatanBoundaryProperties> | null =
  null

let kecamatanLabelLayer:
  L.LayerGroup | null = null

let kelurahanLayer:
  L.LayerGroup | null = null

let requestController:
  AbortController | null = null

const kecamatanBounds =
  new Map<number, L.LatLngBounds>()

const hasSelection = computed(
  () =>
    props.selectedKecamatanId !== null ||
    props.selectedKelurahanId !== null,
)

const kecamatanViews =
  computed<KecamatanView[]>(() =>
    props.kecamatans
      .map((metric) =>
        buildKecamatanView(metric),
      )
      .filter((item) => item.id > 0),
  )

const kelurahanViews =
  computed<KelurahanView[]>(() =>
    props.kelurahans
      .map((metric) =>
        buildKelurahanView(metric),
      )
      .filter((item) => item.id > 0),
  )

function asRecord(
  value: unknown,
): UnknownRecord {
  if (
    typeof value === 'object' &&
    value !== null &&
    !Array.isArray(value)
  ) {
    return value as UnknownRecord
  }

  return {}
}

function readText(
  record: UnknownRecord,
  keys: string[],
  fallback = '',
): string {
  for (const key of keys) {
    const value = record[key]

    if (
      typeof value === 'string' &&
      value.trim() !== ''
    ) {
      return value.trim()
    }
  }

  return fallback
}

function normalizeNumber(
  value: unknown,
): number | null {
  if (
    typeof value === 'number' &&
    Number.isFinite(value)
  ) {
    return value
  }

  if (
    typeof value === 'string' &&
    value.trim() !== ''
  ) {
    const parsedValue = Number(value)

    if (Number.isFinite(parsedValue)) {
      return parsedValue
    }
  }

  return null
}

function readNumber(
  record: UnknownRecord,
  keys: string[],
  fallback = 0,
): number {
  for (const key of keys) {
    const value =
      normalizeNumber(record[key])

    if (value !== null) {
      return value
    }
  }

  return fallback
}

function readRegion(
  metric: unknown,
  regionKey:
    | 'kecamatan'
    | 'kelurahan',
): RegionIdentity {
  const metricRecord =
    asRecord(metric)

  const regionRecord =
    asRecord(metricRecord[regionKey])

  return {
    id: readNumber(
      regionRecord,
      ['id'],
      readNumber(
        metricRecord,
        [`${regionKey}_id`],
      ),
    ),

    code: readText(
      regionRecord,
      ['code', 'kode'],
      readText(metricRecord, [
        `${regionKey}_code`,
        `${regionKey}_kode`,
      ]),
    ),

    name: readText(
      regionRecord,
      ['name', 'nama'],
      readText(metricRecord, [
        `${regionKey}_name`,
        `${regionKey}_nama`,
      ]),
    ),
  }
}

function buildMetricValues(
  metric: unknown,
): {
  totalKpm: number
  transactedKpm: number
  pendingKpm: number
  completionPercentage: number
} {
  const record =
    asRecord(metric)

  const totalKpm =
    readNumber(record, [
      'total_kpm',
      'kpm_total',
    ])

  const transactedKpm =
    readNumber(record, [
      'transacted_kpm',
      'transaction_count',
      'completed_kpm',
      'sudah_transaksi',
    ])

  const pendingKpm =
    readNumber(
      record,
      [
        'pending_kpm',
        'not_transacted_kpm',
        'belum_transaksi',
      ],
      Math.max(
        0,
        totalKpm - transactedKpm,
      ),
    )

  const completionPercentage =
    readNumber(
      record,
      [
        'completion_percentage',
        'transaction_percentage',
        'progress_percentage',
      ],
      totalKpm > 0
        ? (
            transactedKpm /
            totalKpm
          ) * 100
        : 0,
    )

  return {
    totalKpm,
    transactedKpm,
    pendingKpm,
    completionPercentage,
  }
}

function buildKecamatanView(
  metric:
    HeadOfficeKecamatanMetric,
): KecamatanView {
  return {
    ...readRegion(
      metric,
      'kecamatan',
    ),

    ...buildMetricValues(metric),
  }
}

function buildKelurahanView(
  metric:
    HeadOfficeKelurahanMetric,
): KelurahanView {
  return {
    ...readRegion(
      metric,
      'kelurahan',
    ),

    ...buildMetricValues(metric),
  }
}

function normalizeRegionName(
  value: string,
): string {
  return value
    .normalize('NFD')
    .replace(
      /[\u0300-\u036f]/g,
      '',
    )
    .toLowerCase()
    .replace(
      /[^a-z0-9]/g,
      '',
    )
}

function normalizeRegionCode(
  value: string,
): string {
  return value
    .replace(/\D/g, '')
    .slice(0, 6)
}

function findKecamatanView(
  feature: KecamatanFeature,
): KecamatanView | undefined {
  const featureCode =
    normalizeRegionCode(
      String(
        feature.properties
          ?.Kode_Wilay ?? '',
      ),
    )

  const featureName =
    normalizeRegionName(
      String(
        feature.properties
          ?.Kecamatan ?? '',
      ),
    )

  return kecamatanViews.value.find(
    (item) => {
      const itemCode =
        normalizeRegionCode(
          item.code,
        )

      if (
        featureCode !== '' &&
        itemCode !== '' &&
        featureCode === itemCode
      ) {
        return true
      }

      return (
        normalizeRegionName(
          item.name,
        ) === featureName
      )
    },
  )
}

function formatNumber(
  value: number,
): string {
  return new Intl.NumberFormat(
    'id-ID',
  ).format(value)
}

function formatPercentage(
  value: number,
): string {
  const formattedValue =
    new Intl.NumberFormat(
      'id-ID',
      {
        maximumFractionDigits: 1,
      },
    ).format(value)

  return `${formattedValue}%`
}

function escapeHtml(
  value: string,
): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

function popupContent(
  item:
    | KecamatanView
    | KelurahanView,
): string {
  return `
    <div class="head-office-map-popup">
      <strong>${escapeHtml(item.name)}</strong>

      <dl>
        <div>
          <dt>Total KPM</dt>
          <dd>${formatNumber(item.totalKpm)}</dd>
        </div>

        <div>
          <dt>Sudah transaksi</dt>
          <dd>${formatNumber(item.transactedKpm)}</dd>
        </div>

        <div>
          <dt>Belum</dt>
          <dd>${formatNumber(item.pendingKpm)}</dd>
        </div>

        <div>
          <dt>Penyelesaian</dt>
          <dd>${formatPercentage(item.completionPercentage)}</dd>
        </div>
      </dl>
    </div>
  `
}

function districtStyle(
  item:
    KecamatanView | undefined,
): L.PathOptions {
  const isSelected =
    item !== undefined &&
    item.id ===
      props.selectedKecamatanId

  return {
    bubblingMouseEvents: false,
    color: isSelected
      ? '#065f46'
      : '#475569',
    fillColor: isSelected
      ? '#10b981'
      : '#94a3b8',
    fillOpacity: isSelected
      ? 0.2
      : 0.07,
    opacity: 1,
    weight: isSelected
      ? 4
      : 2,
  }
}

function districtLabelIcon(
  name: string,
): L.DivIcon {
  return L.divIcon({
    className:
      'head-office-district-label',

    html:
      `<span>${escapeHtml(name)}</span>`,

    iconAnchor: [0, 0],
  })
}

function isBoundaryCollection(
  value: unknown,
): value is KecamatanFeatureCollection {
  const record =
    asRecord(value)

  return (
    record.type ===
      'FeatureCollection' &&
    Array.isArray(
      record.features,
    )
  )
}

function removeThematicLayers():
  void {
  if (map === null) {
    return
  }

  if (
    kecamatanLayer !== null
  ) {
    map.removeLayer(
      kecamatanLayer,
    )

    kecamatanLayer = null
  }

  if (
    kecamatanLabelLayer !== null
  ) {
    map.removeLayer(
      kecamatanLabelLayer,
    )

    kecamatanLabelLayer = null
  }

  if (
    kelurahanLayer !== null
  ) {
    map.removeLayer(
      kelurahanLayer,
    )

    kelurahanLayer = null
  }

  kecamatanBounds.clear()
}

function renderKelurahanMarkers():
  void {
  const selectedKecamatanId =
    props.selectedKecamatanId

  if (
    map === null ||
    selectedKecamatanId === null
  ) {
    return
  }

  kelurahanLayer =
    L.layerGroup().addTo(map)

  for (
    const item
    of kelurahanViews.value
  ) {
    const center =
      kelurahanCenters[
        normalizeRegionName(
          item.name,
        )
      ]

    if (
      center === undefined
    ) {
      continue
    }

    const isSelected =
      item.id ===
      props.selectedKelurahanId

    const marker =
      L.circleMarker(
        center,
        {
          bubblingMouseEvents:
            false,

          color: '#ffffff',

          fillColor:
            isSelected
              ? '#ea580c'
              : '#047857',

          fillOpacity: 1,
          opacity: 1,

          radius:
            isSelected
              ? 9
              : 7,

          weight: 3,
        },
      )

    marker.bindTooltip(
      item.name,
      {
        direction: 'top',
        offset: [0, -8],
        permanent: true,
      },
    )

    marker.bindPopup(
      popupContent(item),
      {
        className:
          'head-office-popup',

        closeButton: false,
        offset: [0, -4],
      },
    )

    marker.on(
      'click',
      (
        event:
          L.LeafletMouseEvent,
      ) => {
        L.DomEvent.stopPropagation(
          event.originalEvent,
        )

        emit(
          'selectKelurahan',
          {
            kecamatanId:
              selectedKecamatanId,

            kelurahanId:
              item.id,
          },
        )
      },
    )

    marker.addTo(
      kelurahanLayer,
    )
  }
}

function focusCurrentSelection():
  void {
  if (
    map === null ||
    kecamatanLayer === null
  ) {
    return
  }

  if (
    props.selectedKelurahanId !==
    null
  ) {
    const selectedKelurahan =
      kelurahanViews.value.find(
        (item) =>
          item.id ===
          props.selectedKelurahanId,
      )

    const center =
      selectedKelurahan
        ? kelurahanCenters[
            normalizeRegionName(
              selectedKelurahan.name,
            )
          ]
        : undefined

    if (
      center !== undefined
    ) {
      map.flyTo(
        center,
        15,
        {
          duration: 0.45,
        },
      )

      return
    }
  }

  if (
    props.selectedKecamatanId !==
    null
  ) {
    const bounds =
      kecamatanBounds.get(
        props.selectedKecamatanId,
      )

    if (
      bounds !== undefined &&
      bounds.isValid()
    ) {
      map.flyToBounds(
        bounds,
        {
          duration: 0.45,
          maxZoom: 14,
          padding: [28, 28],
        },
      )

      return
    }
  }

  const cityBounds =
    kecamatanLayer.getBounds()

  if (
    cityBounds.isValid()
  ) {
    map.fitBounds(
      cityBounds,
      {
        maxZoom: 13,
        padding: [28, 28],
      },
    )
  }
}

function renderMapData():
  void {
  if (
    map === null ||
    boundaryCollection === null
  ) {
    return
  }

  removeThematicLayers()

  const labelLayer =
    L.layerGroup().addTo(map)

  kecamatanLabelLayer =
    labelLayer

  kecamatanLayer =
    L.geoJSON<KecamatanBoundaryProperties>(
      boundaryCollection,
      {
        style: (feature) =>
          districtStyle(
            feature
              ? findKecamatanView(
                  feature,
                )
              : undefined,
          ),

        onEachFeature:
          (
            feature,
            layer,
          ) => {
            const item =
              findKecamatanView(
                feature,
              )

            if (
              item === undefined ||
              !(
                layer
                instanceof L.Polygon
              )
            ) {
              return
            }

            const polygon =
              layer

            const bounds =
              polygon.getBounds()

            kecamatanBounds.set(
              item.id,
              bounds,
            )

            L.marker(
              bounds.getCenter(),
              {
                icon:
                  districtLabelIcon(
                    item.name,
                  ),

                interactive:
                  false,
              },
            ).addTo(labelLayer)

            polygon.bindPopup(
              popupContent(item),
              {
                className:
                  'head-office-popup',

                closeButton:
                  false,
              },
            )

            polygon.on(
              'mouseover',
              () => {
                polygon.setStyle({
                  fillOpacity:
                    item.id ===
                    props.selectedKecamatanId
                      ? 0.24
                      : 0.14,

                  weight:
                    item.id ===
                    props.selectedKecamatanId
                      ? 4
                      : 3,
                })

                polygon.bringToFront()
              },
            )

            polygon.on(
              'mouseout',
              () => {
                polygon.setStyle(
                  districtStyle(
                    item,
                  ),
                )
              },
            )

            polygon.on(
              'click',
              (
                event:
                  L.LeafletMouseEvent,
              ) => {
                L.DomEvent
                  .stopPropagation(
                    event.originalEvent,
                  )

                emit(
                  'selectKecamatan',
                  item.id,
                )
              },
            )

            if (
              item.id ===
              props.selectedKecamatanId
            ) {
              polygon.bringToFront()
            }
          },
      },
    ).addTo(map)

  renderKelurahanMarkers()
  focusCurrentSelection()
}

async function loadKecamatanBoundaries():
  Promise<void> {
  requestController?.abort()

  const controller =
    new AbortController()

  requestController =
    controller

  isLoadingBoundary.value =
    true

  boundaryError.value =
    null

  try {
    const response =
      await fetch(
        KECAMATAN_GEOJSON_URL,
        {
          headers: {
            Accept:
              'application/geo+json, application/json',
          },

          signal:
            controller.signal,
        },
      )

    if (!response.ok) {
      throw new Error(
        `HTTP ${response.status}`,
      )
    }

    const payload: unknown =
      await response.json()

    if (
      !isBoundaryCollection(
        payload,
      )
    ) {
      throw new Error(
        'Format GeoJSON tidak sesuai.',
      )
    }

    boundaryCollection =
      payload

    renderMapData()
  } catch (error: unknown) {
    if (
      error
        instanceof DOMException &&
      error.name ===
        'AbortError'
    ) {
      return
    }

    boundaryError.value =
      'Batas kecamatan belum dapat dimuat. Periksa koneksi lalu coba lagi.'
  } finally {
    if (
      requestController ===
      controller
    ) {
      requestController =
        null

      isLoadingBoundary.value =
        false
    }
  }
}

function handleMapBackgroundClick():
  void {
  if (hasSelection.value) {
    emit('clear')
  }
}

function initializeMap():
  void {
  if (
    mapElement.value === null ||
    map !== null
  ) {
    return
  }

  map = L.map(
    mapElement.value,
    {
      attributionControl:
        true,

      maxZoom: 19,
      minZoom: 11,
      preferCanvas: true,
      zoomControl: true,
    },
  ).setView(
    MOJOKERTO_CENTER,
    13,
  )

  L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
      attribution:
        '&copy; OpenStreetMap contributors',

      maxZoom: 19,
    },
  ).addTo(map)

  map.attributionControl
    .addAttribution(
      'Batas wilayah: Pemerintah Kota Mojokerto',
    )

  map.on(
    'click',
    handleMapBackgroundClick,
  )

  void loadKecamatanBoundaries()
}

watch(
  () => [
    props.kecamatans,
    props.kelurahans,
    props.selectedKecamatanId,
    props.selectedKelurahanId,
  ],
  () => {
    renderMapData()
  },
  {
    deep: true,
  },
)

onMounted(() => {
  initializeMap()
})

onBeforeUnmount(() => {
  requestController?.abort()
  requestController = null

  if (map !== null) {
    map.off(
      'click',
      handleMapBackgroundClick,
    )

    map.remove()
    map = null
  }

  boundaryCollection = null
  kecamatanLayer = null
  kecamatanLabelLayer = null
  kelurahanLayer = null

  kecamatanBounds.clear()
})
</script>

<style scoped>
:global(.leaflet-container) {
  background: #e2e8f0;
  font-family: inherit;
}

:global(
  .head-office-district-label
) {
  background: transparent;
  border: 0;
  height: auto !important;
  width: auto !important;
}

:global(
  .head-office-district-label span
) {
  display: block;
  transform: translate(
    -50%,
    -50%
  );
  white-space: nowrap;
  border: 1px solid
    rgb(203 213 225 / 90%);
  border-radius: 9999px;
  background:
    rgb(255 255 255 / 92%);
  box-shadow:
    0 2px 8px
    rgb(15 23 42 / 12%);
  color: #0f172a;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  padding: 6px 9px;
}

:global(
  .head-office-popup
  .leaflet-popup-content-wrapper
) {
  border-radius: 14px;
  box-shadow:
    0 12px 32px
    rgb(15 23 42 / 18%);
}

:global(
  .head-office-popup
  .leaflet-popup-content
) {
  margin: 14px 16px;
  min-width: 180px;
}

:global(
  .head-office-map-popup
  strong
) {
  color: #0f172a;
  display: block;
  font-size: 14px;
  margin-bottom: 10px;
}

:global(
  .head-office-map-popup
  dl
) {
  display: grid;
  gap: 7px;
  margin: 0;
}

:global(
  .head-office-map-popup
  dl
  div
) {
  align-items: center;
  display: flex;
  gap: 18px;
  justify-content:
    space-between;
}

:global(
  .head-office-map-popup
  dt
) {
  color: #64748b;
  font-size: 12px;
}

:global(
  .head-office-map-popup
  dd
) {
  color: #0f172a;
  font-size: 12px;
  font-weight: 700;
  margin: 0;
}

:global(.leaflet-tooltip) {
  border: 1px solid #dbe3ea;
  border-radius: 8px;
  box-shadow:
    0 4px 12px
    rgb(15 23 42 / 10%);
  color: #334155;
  font-size: 11px;
  font-weight: 700;
  padding: 5px 8px;
}
</style>