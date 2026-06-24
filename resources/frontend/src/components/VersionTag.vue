<template>
  <n-tag size="small" :type="type" :bordered="bordered">
    v{{ version }}
  </n-tag>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  version: string
  type?: 'default' | 'info' | 'success' | 'warning' | 'error'
  bordered?: boolean
}>(), {
    type: 'info',
    bordered: false,
})
</script>
