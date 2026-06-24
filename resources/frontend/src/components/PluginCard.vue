<template>
  <n-card hoverable>
    <template #header>
      <n-space align="center">
        <n-avatar :size="48" round :style="{ backgroundColor: avatarColor }">
          {{ plugin.display_name?.charAt(0)?.toUpperCase() || 'P' }}
        </n-avatar>
        <div>
          <div style="font-weight: 600; font-size: 16px">{{ plugin.display_name }}</div>
          <n-text depth="3" style="font-size: 12px">{{ plugin.package_name }}</n-text>
        </div>
      </n-space>
    </template>

    <template #header-extra>
      <n-tag :type="statusType" size="small" round>{{ statusText }}</n-tag>
    </template>

    <n-ellipsis :line-clamp="2" :tooltip="false">
      {{ plugin.description || '暂无描述' }}
    </n-ellipsis>

    <div style="margin-top: 12px">
      <n-space :size="8" align="center">
        <n-tag v-if="plugin.is_installed" type="success" size="small">✅ 已安装</n-tag>
        <version-tag v-if="plugin.installed_version" :version="plugin.installed_version" />
        <n-text v-if="plugin.author" depth="3" style="font-size: 12px">
          👤 {{ plugin.author }}
        </n-text>
      </n-space>
    </div>

    <template #action>
      <slot name="actions" />
    </template>
  </n-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Plugin } from '@/api/plugin'
import VersionTag from './VersionTag.vue'

const props = defineProps<{
  plugin: Plugin
}>()

const avatarColor = computed(() => {
    const colors = ['#2080f0', '#18a058', '#f0a020', '#d03050', '#8a2be2']
    const idx = props.plugin.display_name.charCodeAt(0) % colors.length
    return colors[idx]
})

const statusType = computed(() => {
    const map: Record<string, string> = { active: 'success', inactive: 'warning', pending: 'info' }
    return (map[props.plugin.status] || 'default') as any
})

const statusText = computed(() => {
    const map: Record<string, string> = { active: '已启用', inactive: '已禁用', pending: '待安装' }
    return map[props.plugin.status] || props.plugin.status
})
</script>
