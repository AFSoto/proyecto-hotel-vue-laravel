<template>
  <div class="relative" :style="{ height: height }">
    <canvas ref="canvasEl" />
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  type: { type: String, default: 'doughnut' },
  data: { type: Object, required: true },
  options: { type: Object, default: () => ({}) },
  height: { type: String, default: '260px' },
})

const canvasEl = ref(null)
let chart = null

function render() {
  if (!canvasEl.value) return
  if (chart) chart.destroy()
  chart = new Chart(canvasEl.value, {
    type: props.type,
    data: props.data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      ...props.options,
    },
  })
}

onMounted(render)

// Re-renderiza cuando cambian los datos (nuevo rango de fechas)
watch(() => props.data, render, { deep: true })

onBeforeUnmount(() => chart?.destroy())
</script>
