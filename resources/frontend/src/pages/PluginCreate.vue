<template>
  <n-card title="添加插件" style="max-width: 800px">
    <n-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-placement="left"
      label-width="120"
    >
      <n-form-item label="包名" path="package_name">
        <n-input v-model:value="form.package_name" placeholder="例如: vendor/package-name" />
      </n-form-item>

      <n-form-item label="显示名称" path="display_name">
        <n-input v-model:value="form.display_name" placeholder="插件的显示名称" />
      </n-form-item>

      <n-form-item label="Slug" path="slug">
        <n-input v-model:value="form.slug" placeholder="URL 标识（留空自动生成）" />
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
        <n-input v-model:value="form.homepage" placeholder="https://github.com/..." />
      </n-form-item>

      <n-form-item label="安装方式" path="install_type">
        <n-radio-group v-model:value="form.install_type">
          <n-radio-button value="composer">Composer</n-radio-button>
          <n-radio-button value="upload">上传 Zip</n-radio-button>
        </n-radio-group>
      </n-form-item>

      <n-form-item label="状态" path="status">
        <n-select
          v-model:value="form.status"
          :options="statusOptions"
        />
      </n-form-item>

      <n-space justify="end">
        <n-button @click="$router.back()">取消</n-button>
        <n-button type="primary" @click="handleSubmit" :loading="submitting">
          创建插件
        </n-button>
      </n-space>
    </n-form>
  </n-card>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMessage, type FormInst, type FormRules } from 'naive-ui'
import { pluginApi, categoryApi, type Category } from '@/api/plugin'

const router = useRouter()
const message = useMessage()
const formRef = ref<FormInst | null>(null)
const submitting = ref(false)
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
        { pattern: /^[a-z0-9-]+\/[a-z0-9-]+$/, message: '格式: vendor/package-name', trigger: 'blur' },
    ],
    display_name: [
        { required: true, message: '请输入显示名称', trigger: 'blur' },
    ],
    slug: [
        { pattern: /^[a-z0-9-]*$/, message: '仅允许小写字母、数字和横线', trigger: 'blur' },
    ],
}

async function handleSubmit() {
    try {
        await formRef.value?.validate()
    } catch {
        return
    }

    submitting.value = true
    try {
        const data: Record<string, any> = { ...form }
        // 清理空值
        if (!data.slug) delete data.slug
        if (!data.category_id) delete data.category_id
        if (!data.description) delete data.description
        if (!data.author) delete data.author
        if (!data.homepage) delete data.homepage

        await pluginApi.create(data)
        message.success('插件创建成功')
        router.push({ name: 'plugin-list' })
    } catch (e: any) {
        const errors = e.response?.data?.errors
        if (errors) {
            const firstError = Object.values(errors)[0]
            message.error(Array.isArray(firstError) ? firstError[0] : '创建失败')
        } else {
            message.error(e.response?.data?.message || '创建失败')
        }
    } finally {
        submitting.value = false
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

onMounted(() => {
    fetchCategories()
})
</script>
