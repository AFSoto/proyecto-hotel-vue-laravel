<template>
  <div class="bg-white rounded-xl border border-gray-200 p-4">
    <div class="flex items-center gap-3">
      <div :class="['w-10 h-10 rounded-lg flex items-center justify-center shrink-0', colorBg]">
        <component :is="icon" v-if="icon" :class="['w-5 h-5', colorText]" />
      </div>
      <div class="min-w-0">
        <p class="text-xs text-gray-500 truncate">{{ label }}</p>
        <p class="text-xl font-bold text-gray-800 truncate">{{ value }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: '—' },
  icon: { type: [Object, Function], default: null },
  colorBg: { type: String, default: 'bg-blue-50' },
  colorText: { type: String, default: 'text-blue-600' },
})
</script>
