<template>
  <n-spin :show="loading">
    <n-card title="编辑插件" style="max-width: 900px">
      <n-tabs type="line">
        <!-- 基本信息 -->
        <n-tab-pane name="info" tab="基本信息">
          <n-form
            ref="formRef"
            :model="form"
            :rules="rules"
            label-placement="left"
            label-width="120"
          >
            <n-form-item label="包名" path="package_name">
              <n-input v-model:value="form.package_name" placeholder="vendor/package-name" />
            </n-form-item>

            <n-form-item label="显示名称" path="display_name">
              <n-input v-model:value="form.display_name" placeholder="插件的显示名称" />
            </n-form-item>

            <n-form-item label="Slug" path="slug">
              <n-input v-model:value="form.slug" placeholder="URL 标识" />
            </n-form-item>

            <n-form-item label="分类" path="category_id">
              <n-select
                v-model:value="form.category_id"
                :options="categoryOptions"
                placeholder="选择分类（可选）"
                clearable
              />
            </n-form-item>

            <n-form-item label="描述" path="description">
              <n-input
                v-model:value="form.description"
                type="textarea"
                :rows="3"
                placeholder="插件描述"
              />
            </n-form-item>

            <n-form-item label="作者" path="author">
              <n-input v-model:value="form.author" placeholder="作者名称" />
            </n-form-item>

            <n-form-item label="主页" path="homepage">
              <n-input v-model:value="form.homepage" placeholder="https://..." />
            </n-form-item>

            <n-form-item label="安装方式" path="install_type">
              <n-radio-group v-model:value="form.install_type">
                <n-radio-button value="composer">Composer</n-radio-button>
                <n-radio-button value="upload">上传 Zip</n-radio-button>
              </n-radio-group>
            </n-form-item>

            <n-form-item label="状态" path="status">
              <n-select v-model:value="form.status" :options="statusOptions" />
            </n-form-item>

            <n-space justify="end">
              <n-button @click="$router.back()">返回</n-button>
              <n-button type="primary" @click="handleSave" :loading="saving">保存修改</n-button>
            </n-space>
          </n-form>
        </n-tab-pane>

        <!-- 安装管理 -->
        <n-tab-pane name="install" tab="安装管理">
          <n-descriptions :column="1" bordered label-placement="left">
            <n-descriptions-item label="安装状态">
              <n-tag :type="plugin?.is_installed ? 'success' : 'warning'" size="small">
                {{ plugin?.is_installed ? '已安装' : '未安装' }}
              </n-tag>
            </n-descriptions-item>
            <n-descriptions-item label="当前版本">
              {{ plugin?.installed_version || '-' }}
            </n-descriptions-item>
            <n-descriptions-item label="安装路径">
              <n-text code>{{ plugin?.installed_path || '-' }}</n-text>
            </n-descriptions-item>
            <n-descriptions-item label="安装方式">
              {{ plugin?.install_type === 'composer' ? 'Composer' : 'Zip 上传' }}
            </n-descriptions-item>
          </n-descriptions>

          <n-divider />

          <n-space vertical>
            <n-alert v-if="plugin?.is_installed" type="warning" title="卸载插件">
              卸载后插件文件将被移除，数据库记录保留。
              <template #action>
                <n-button type="warning" size="small" @click="handleUninstall" :loading="uninstalling">
                  卸载
                </n-button>
              </template>
            </n-alert>

            <n-alert v-else type="info" title="安装插件">
              <n-space>
                <n-input v-model:value="installVersion" placeholder="版本号（可选）" style="width: 200px" />
                <n-button type="primary" size="small" @click="handleInstall" :loading="installing">
                  Composer 安装
                </n-button>
              </n-space>
            </n-alert>
          </n-space>
        </n-tab-pane>

        <!-- 版本记录 -->
        <n-tab-pane name="versions" tab="版本记录">
          <n-data-table
            :columns="versionColumns"
            :data="plugin?.versions || []"
            :bordered="false"
            :single-line="false"
          />
        </n-tab-pane>
      </n-tabs>
    </n-card>
  </n-spin>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMessage, useDialog, type FormInst, type FormRules, NTag } from 'naive-ui'
import { pluginApi, categoryApi, type Plugin, type Category, type PluginVersion } from '@/api/plugin'

const route = useRoute()
const router = useRouter()
const message = useMessage()
const dialog = useDialog()

const loading = ref(false)
const saving = ref(false)
const installing = ref(false)
const uninstalling = ref(false)
const installVersion = ref('')
const formRef = ref<FormInst | null>(null)
const plugin = ref<Plugin | null>(null)
const categories = ref<Category[]>([])

const categoryOptions = ref<Array<{ label: string; value: number }>>([])
const statusOptions = [
    { label: '待安装', value: 'pending' },
    { label: '已启用', value: 'active' },
    { label: '已禁用', value: 'inactive' },
]

const form = reactive({
    package_name: '',
    display_name: '',
    slug: '',
    category_id: null as number | null,
    description: '',
    author: '',
    homepage: '',
    install_type: 'composer',
    status: 'pending',
})

const rules: FormRules = {
    package_name: [
        { required: true, message: '请输入包名', trigger: 'blur' },
    ],
    display_name: [
        { required: true, message: '请输入显示名称', trigger: 'blur' },
    ],
}

const versionColumns = [
    {
        title: '版本',
        key: 'version',
        render: (row: PluginVersion) => h(NTag, { size: 'small', type: row.is_latest ? 'success' : 'default' }, () => `v${row.version}${row.is_latest ? ' (最新)' : ''}`),
    },
    { title: '更新日志', key: 'changelog', ellipsis: { tooltip: true } },
    { title: '文件大小', key: 'formatted_file_size' },
    { title: '下载次数', key: 'download_count' },
    {
        title: '创建时间',
        key: 'created_at',
        render: (row: PluginVersion) => row.created_at ? new Date(row.created_at).toLocaleDateString() : '-',
    },
]

async function fetchPlugin() {
    loading.value = true
    try {
        const id = Number(route.params.id)
        const res = await pluginApi.show(id)
        plugin.value = res.data.data

        Object.assign(form, {
            package_name: plugin.value.package_name,
            display_name: plugin.value.display_name,
            slug: plugin.value.slug,
            category_id: plugin.value.category_id,
            description: plugin.value.description || '',
            author: plugin.value.author || '',
            homepage: plugin.value.homepage || '',
            install_type: plugin.value.install_type,
            status: plugin.value.status,
        })
    } catch {
        message.error('加载插件信息失败')
        router.back()
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

async function handleSave() {
    try {
        await formRef.value?.validate()
    } catch {
        return
    }

    saving.value = true
    try {
        const id = Number(route.params.id)
        const data: Record<string, any> = { ...form }
        if (!data.category_id) delete data.category_id
        if (!data.description) data.description = null
        if (!data.author) data.author = null
        if (!data.homepage) data.homepage = null

        const res = await pluginApi.update(id, data)
        plugin.value = res.data.data
        message.success('保存成功')
    } catch (e: any) {
        message.error(e.response?.data?.message || '保存失败')
    } finally {
        saving.value = false
    }
}

async function handleInstall() {
    if (!plugin.value) return
    installing.value = true
    try {
        const res = await pluginApi.install({
            plugin_id: plugin.value.id,
            version: installVersion.value || undefined,
        })
        message.success(res.data.message || '安装成功')
        await fetchPlugin()
    } catch (e: any) {
        message.error(e.response?.data?.message || '安装失败')
    } finally {
        installing.value = false
    }
}

function handleUninstall() {
    if (!plugin.value) return
    dialog.warning({
        title: '确认卸载',
        content: `确定要卸载 ${plugin.value.display_name} 吗？`,
        positiveText: '卸载',
        negativeText: '取消',
        onPositiveClick: async () => {
            uninstalling.value = true
            try {
                const res = await pluginApi.uninstall(plugin.value!.id)
                message.success(res.data.message || '卸载成功')
                await fetchPlugin()
            } catch (e: any) {
                message.error(e.response?.data?.message || '卸载失败')
            } finally {
                uninstalling.value = false
            }
        },
    })
}

onMounted(() => {
    fetchPlugin()
    fetchCategories()
})
</script>
