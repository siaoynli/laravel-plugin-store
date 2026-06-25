<template>
  <n-config-provider :theme-overrides="themeOverrides">
    <n-message-provider>
      <n-dialog-provider>
        <n-notification-provider>
          <n-layout has-sider style="min-height: 100vh">
            <n-layout-sider
              bordered
              :width="240"
              :collapsed-width="64"
              collapse-mode="width"
              show-trigger
            >
              <div style="padding: 20px; text-align: center; font-size: 18px; font-weight: bold">
                🔌 插件市场
              </div>
              <n-menu
                :options="menuOptions"
                :value="activeKey"
                @update:value="handleMenuUpdate"
              />
            </n-layout-sider>
            <n-layout>
              <n-layout-header bordered style="padding: 16px 24px; display: flex; align-items: center; justify-content: space-between;">
                <n-breadcrumb>
                  <n-breadcrumb-item>插件市场</n-breadcrumb-item>
                  <n-breadcrumb-item v-if="currentPageTitle">{{ currentPageTitle }}</n-breadcrumb-item>
                </n-breadcrumb>
                <n-space>
                  <n-button type="primary" @click="handleRefresh" :loading="refreshing">
                    🔄 同步已安装插件
                  </n-button>
                </n-space>
              </n-layout-header>
              <n-layout-content style="padding: 24px;">
                <router-view />
              </n-layout-content>
            </n-layout>
          </n-layout>
        </n-notification-provider>
      </n-dialog-provider>
    </n-message-provider>
  </n-config-provider>
</template>

<script setup lang="ts">
import { computed, ref, h } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { createDiscreteApi } from 'naive-ui'
import { pluginApi } from './api/plugin'

// 根组件无法注入自身的 provider，使用 Discrete API 代替 useMessage()
const { message } = createDiscreteApi(['message'])

const router = useRouter()
const route = useRoute()
const refreshing = ref(false)

const themeOverrides = {
    common: {
        primaryColor: '#2080f0',
        primaryColorHover: '#4098fc',
    },
}

const menuOptions = [
    {
        label: '插件列表',
        key: 'plugin-list',
        icon: () => h('span', '📦'),
    },
    {
        label: '添加插件',
        key: 'plugin-create',
        icon: () => h('span', '➕'),
    },
    {
        label: '分类管理',
        key: 'category-manage',
        icon: () => h('span', '📁'),
    },
]

const activeKey = computed(() => {
    const name = route.name as string
    if (name?.startsWith('plugin-edit')) return 'plugin-list'
    return name || 'plugin-list'
})

const currentPageTitle = computed(() => {
    const map: Record<string, string> = {
        'plugin-list': '插件列表',
        'plugin-create': '添加插件',
        'plugin-edit': '编辑插件',
        'category-manage': '分类管理',
    }
    return map[route.name as string] || ''
})

function handleMenuUpdate(key: string) {
    router.push({ name: key })
}

async function handleRefresh() {
    refreshing.value = true
    try {
        const res = await pluginApi.refresh()
        message.success(res.data.message || '同步完成')
    } catch (e: any) {
        message.error(e.response?.data?.message || '同步失败')
    } finally {
        refreshing.value = false
    }
}
</script>
