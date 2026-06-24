<template>
  <div>
    <!-- 搜索和筛选 -->
    <n-card style="margin-bottom: 16px">
      <n-space align="center" :wrap="true">
        <n-input
          v-model:value="filters.keyword"
          placeholder="搜索插件名称、包名、作者..."
          clearable
          style="width: 300px"
          @update:value="debouncedSearch"
        >
          <template #prefix>🔍</template>
        </n-input>

        <n-select
          v-model:value="filters.category_id"
          :options="categoryOptions"
          placeholder="全部分类"
          clearable
          style="width: 180px"
          @update:value="fetchPlugins"
        />

        <n-select
          v-model:value="filters.status"
          :options="statusOptions"
          placeholder="全部状态"
          clearable
          style="width: 150px"
          @update:value="fetchPlugins"
        />

        <n-button type="primary" @click="fetchPlugins">搜索</n-button>
      </n-space>
    </n-card>

    <!-- 插件列表 -->
    <n-spin :show="loading">
      <n-grid :cols="3" :x-gap="16" :y-gap="16" responsive="screen" item-responsive>
        <n-grid-item v-for="plugin in plugins" :key="plugin.id" span="3 m:2 l:1">
          <n-card hoverable>
            <template #header>
              <n-space align="center">
                <n-avatar :size="40" round style="background-color: #2080f0">
                  {{ plugin.display_name?.charAt(0)?.toUpperCase() || 'P' }}
                </n-avatar>
                <div>
                  <div style="font-weight: bold">{{ plugin.display_name }}</div>
                  <n-text depth="3" style="font-size: 12px">{{ plugin.package_name }}</n-text>
                </div>
              </n-space>
            </template>

            <template #header-extra>
              <n-tag
                :type="statusTagType(plugin.status)"
                size="small"
                round
              >
                {{ statusLabel(plugin.status) }}
              </n-tag>
            </template>

            <n-ellipsis :line-clamp="2" :tooltip="false">
              {{ plugin.description || '暂无描述' }}
            </n-ellipsis>

            <div style="margin-top: 12px">
              <n-space :size="8">
                <n-tag size="small" v-if="plugin.is_installed" type="success">
                  ✅ 已安装
                </n-tag>
                <n-tag size="small" v-if="plugin.installed_version">
                  v{{ plugin.installed_version }}
                </n-tag>
                <n-tag size="small" v-if="plugin.latest_version">
                  最新: v{{ plugin.latest_version.version }}
                </n-tag>
                <n-text depth="3" style="font-size: 12px">
                  📥 {{ plugin.download_count }}
                </n-text>
              </n-space>
            </div>

            <template #action>
              <n-space justify="space-between">
                <n-space :size="4">
                  <n-button
                    v-if="!plugin.is_installed"
                    type="primary"
                    size="small"
                    @click="openInstallDialog(plugin)"
                  >
                    安装
                  </n-button>
                  <n-button
                    v-if="plugin.is_installed"
                    type="warning"
                    size="small"
                    @click="handleUninstall(plugin)"
                    :loading="plugin._uninstalling"
                  >
                    卸载
                  </n-button>
                  <n-button
                    size="small"
                    @click="handleToggle(plugin)"
                    :loading="plugin._toggling"
                  >
                    {{ plugin.is_active ? '禁用' : '启用' }}
                  </n-button>
                </n-space>
                <n-space :size="4">
                  <n-button
                    size="small"
                    @click="$router.push({ name: 'plugin-edit', params: { id: plugin.id } })"
                  >
                    编辑
                  </n-button>
                  <n-button
                    size="small"
                    type="error"
                    quaternary
                    @click="handleDelete(plugin)"
                  >
                    删除
                  </n-button>
                </n-space>
              </n-space>
            </template>
          </n-card>
        </n-grid-item>
      </n-grid>

      <!-- 空状态 -->
      <n-empty
        v-if="!loading && plugins.length === 0"
        description="暂无插件"
        style="margin-top: 48px"
      >
        <template #extra>
          <n-button type="primary" @click="$router.push({ name: 'plugin-create' })">
            添加第一个插件
          </n-button>
        </template>
      </n-empty>

      <!-- 分页 -->
      <n-space justify="center" style="margin-top: 24px" v-if="pagination.total > 0">
        <n-pagination
          v-model:page="pagination.current_page"
          :page-count="pagination.last_page"
          :page-size="pagination.per_page"
          show-size-picker
          :page-sizes="[10, 15, 20, 50]"
          @update:page="fetchPlugins"
          @update:page-size="(size: number) => { pagination.per_page = size; fetchPlugins() }"
        >
          <template #prefix="{ itemCount }">
            共 {{ itemCount }} 个插件
          </template>
        </n-pagination>
      </n-space>
    </n-spin>

    <!-- 安装对话框 -->
    <n-modal v-model:show="showInstallDialog" preset="dialog" title="安装插件" style="width: 500px">
      <n-space vertical v-if="installTarget">
        <n-descriptions :column="1" label-placement="left" bordered size="small">
          <n-descriptions-item label="插件名称">{{ installTarget.display_name }}</n-descriptions-item>
          <n-descriptions-item label="包名">{{ installTarget.package_name }}</n-descriptions-item>
        </n-descriptions>

        <n-tabs type="segment">
          <!-- Composer 安装 -->
          <n-tab-pane name="composer" tab="Composer 安装">
            <n-form label-placement="left" label-width="80">
              <n-form-item label="版本号">
                <n-input
                  v-model:value="installForm.version"
                  placeholder="留空安装最新版本"
                />
              </n-form-item>
            </n-form>
            <n-button
              type="primary"
              block
              @click="handleComposerInstall"
              :loading="installLoading"
            >
              🚀 通过 Composer 安装
            </n-button>
          </n-tab-pane>

          <!-- Zip 上传安装 -->
          <n-tab-pane name="upload" tab="Zip 上传">
            <n-upload
              :max="1"
              accept=".zip"
              :custom-request="handleUploadInstall"
              :show-file-list="false"
            >
              <n-button block :loading="installLoading">
                📦 上传 Zip 包安装
              </n-button>
            </n-upload>
          </n-tab-pane>
        </n-tabs>
      </n-space>
    </n-modal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useMessage, useDialog, type UploadCustomRequestOptions } from 'naive-ui'
import { pluginApi, categoryApi, type Plugin, type Category, type PaginatedResponse } from '@/api/plugin'

const message = useMessage()
const dialog = useDialog()

const loading = ref(false)
const plugins = ref<(Plugin & { _uninstalling?: boolean; _toggling?: boolean })[]>([])
const categories = ref<Category[]>([])
const showInstallDialog = ref(false)
const installTarget = ref<Plugin | null>(null)
const installLoading = ref(false)
const installForm = reactive({ version: '' })

const filters = reactive({
    keyword: '',
    category_id: null as number | null,
    status: null as string | null,
})

const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
})

const statusOptions = [
    { label: '已启用', value: 'active' },
    { label: '已禁用', value: 'inactive' },
    { label: '待安装', value: 'pending' },
]

const categoryOptions = ref<Array<{ label: string; value: number }>>([])

let searchTimer: ReturnType<typeof setTimeout> | null = null
function debouncedSearch() {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => fetchPlugins(), 300)
}

function statusTagType(status: string) {
    const map: Record<string, string> = { active: 'success', inactive: 'warning', pending: 'info' }
    return (map[status] || 'default') as any
}

function statusLabel(status: string) {
    const map: Record<string, string> = { active: '已启用', inactive: '已禁用', pending: '待安装' }
    return map[status] || status
}

async function fetchPlugins() {
    loading.value = true
    try {
        const res = await pluginApi.list({
            ...filters,
            page: pagination.current_page,
            per_page: pagination.per_page,
        })
        const json = res.data as PaginatedResponse<Plugin>
        plugins.value = json.data
        Object.assign(pagination, json.meta)
    } catch (e: any) {
        message.error('加载插件列表失败')
    } finally {
        loading.value = false
    }
}

async function fetchCategories() {
    try {
        const res = await categoryApi.list()
        categories.value = res.data.data
        categoryOptions.value = categories.value.map((c) => ({
            label: c.name,
            value: c.id,
        }))
    } catch {}
}

function openInstallDialog(plugin: Plugin) {
    installTarget.value = plugin
    installForm.version = ''
    showInstallDialog.value = true
}

async function handleComposerInstall() {
    if (!installTarget.value) return
    installLoading.value = true
    try {
        const res = await pluginApi.install({
            plugin_id: installTarget.value.id,
            version: installForm.version || undefined,
        })
        message.success(res.data.message || '安装成功')
        showInstallDialog.value = false
        await fetchPlugins()
    } catch (e: any) {
        message.error(e.response?.data?.message || '安装失败')
    } finally {
        installLoading.value = false
    }
}

async function handleUploadInstall({ file }: UploadCustomRequestOptions) {
    if (!installTarget.value || !file.file) return
    installLoading.value = true
    try {
        const formData = new FormData()
        formData.append('plugin_id', String(installTarget.value.id))
        formData.append('file', file.file)
        const res = await pluginApi.upload(formData)
        message.success(res.data.message || '安装成功')
        showInstallDialog.value = false
        await fetchPlugins()
    } catch (e: any) {
        message.error(e.response?.data?.message || '上传安装失败')
    } finally {
        installLoading.value = false
    }
}

async function handleUninstall(plugin: Plugin & { _uninstalling?: boolean }) {
    dialog.warning({
        title: '确认卸载',
        content: `确定要卸载 ${plugin.display_name} 吗？`,
        positiveText: '卸载',
        negativeText: '取消',
        onPositiveClick: async () => {
            plugin._uninstalling = true
            try {
                const res = await pluginApi.uninstall(plugin.id)
                message.success(res.data.message || '卸载成功')
                await fetchPlugins()
            } catch (e: any) {
                message.error(e.response?.data?.message || '卸载失败')
            } finally {
                plugin._uninstalling = false
            }
        },
    })
}

async function handleToggle(plugin: Plugin & { _toggling?: boolean }) {
    plugin._toggling = true
    try {
        const res = await pluginApi.toggle(plugin.id)
        message.success(res.data.message || '操作成功')
        await fetchPlugins()
    } catch (e: any) {
        message.error(e.response?.data?.message || '操作失败')
    } finally {
        plugin._toggling = false
    }
}

function handleDelete(plugin: Plugin) {
    dialog.warning({
        title: '确认删除',
        content: `确定要删除 ${plugin.display_name} 吗？此操作可恢复（软删除）。`,
        positiveText: '删除',
        negativeText: '取消',
        onPositiveClick: async () => {
            try {
                await pluginApi.delete(plugin.id)
                message.success('插件已删除')
                await fetchPlugins()
            } catch (e: any) {
                message.error(e.response?.data?.message || '删除失败')
            }
        },
    })
}

onMounted(() => {
    fetchPlugins()
    fetchCategories()
})
</script>
