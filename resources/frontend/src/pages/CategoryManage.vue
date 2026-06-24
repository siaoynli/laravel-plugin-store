<template>
  <n-card title="分类管理" style="max-width: 800px">
    <template #header-extra>
      <n-button type="primary" size="small" @click="openCreateDialog">
        ➕ 新建分类
      </n-button>
    </template>

    <n-spin :show="loading">
      <n-data-table
        :columns="columns"
        :data="categories"
        :bordered="false"
        :single-line="false"
      />

      <n-empty v-if="!loading && categories.length === 0" description="暂无分类" style="margin-top: 32px" />
    </n-spin>

    <!-- 创建/编辑对话框 -->
    <n-modal v-model:show="showDialog" preset="dialog" :title="editingCategory ? '编辑分类' : '新建分类'" style="width: 480px">
      <n-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-placement="left"
        label-width="80"
      >
        <n-form-item label="名称" path="name">
          <n-input v-model:value="form.name" placeholder="分类名称" />
        </n-form-item>
        <n-form-item label="Slug" path="slug">
          <n-input v-model:value="form.slug" placeholder="URL 标识" />
        </n-form-item>
        <n-form-item label="描述" path="description">
          <n-input v-model:value="form.description" type="textarea" :rows="2" placeholder="描述（可选）" />
        </n-form-item>
        <n-form-item label="排序" path="sort_order">
          <n-input-number v-model:value="form.sort_order" :min="0" />
        </n-form-item>
      </n-form>

      <template #action>
        <n-space>
          <n-button @click="showDialog = false">取消</n-button>
          <n-button type="primary" @click="handleSubmit" :loading="submitting">
            {{ editingCategory ? '保存' : '创建' }}
          </n-button>
        </n-space>
      </template>
    </n-modal>
  </n-card>
</template>

<script setup lang="ts">
import { ref, reactive, h, onMounted } from 'vue'
import { useMessage, useDialog, NButton, NSpace, NTag, type FormInst, type FormRules } from 'naive-ui'
import { categoryApi, type Category } from '@/api/plugin'

const message = useMessage()
const dialog = useDialog()

const loading = ref(false)
const submitting = ref(false)
const showDialog = ref(false)
const editingCategory = ref<Category | null>(null)
const categories = ref<Category[]>([])
const formRef = ref<FormInst | null>(null)

const form = reactive({
    name: '',
    slug: '',
    description: '',
    sort_order: 0,
})

const rules: FormRules = {
    name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
    slug: [
        { required: true, message: '请输入 Slug', trigger: 'blur' },
        { pattern: /^[a-z0-9-]+$/, message: '仅允许小写字母、数字和横线', trigger: 'blur' },
    ],
}

const columns = [
    { title: '名称', key: 'name' },
    { title: 'Slug', key: 'slug' },
    { title: '排序', key: 'sort_order', width: 80 },
    {
        title: '插件数',
        key: 'plugins_count',
        width: 80,
        render: (row: Category) => h(NTag, { size: 'small' }, () => String(row.plugins_count)),
    },
    {
        title: '操作',
        key: 'actions',
        width: 160,
        render: (row: Category) =>
            h(NSpace, { size: 4 }, () => [
                h(NButton, { size: 'small', onClick: () => openEditDialog(row) }, () => '编辑'),
                h(NButton, { size: 'small', type: 'error', quaternary: true, onClick: () => handleDelete(row) }, () => '删除'),
            ]),
    },
]

function openCreateDialog() {
    editingCategory.value = null
    form.name = ''
    form.slug = ''
    form.description = ''
    form.sort_order = 0
    showDialog.value = true
}

function openEditDialog(category: Category) {
    editingCategory.value = category
    form.name = category.name
    form.slug = category.slug
    form.description = category.description || ''
    form.sort_order = category.sort_order
    showDialog.value = true
}

async function fetchCategories() {
    loading.value = true
    try {
        const res = await categoryApi.list()
        categories.value = res.data.data
    } catch {
        message.error('加载分类失败')
    } finally {
        loading.value = false
    }
}

async function handleSubmit() {
    try {
        await formRef.value?.validate()
    } catch {
        return
    }

    submitting.value = true
    try {
        if (editingCategory.value) {
            await categoryApi.update(editingCategory.value.id, { ...form })
            message.success('分类更新成功')
        } else {
            await categoryApi.create({ ...form })
            message.success('分类创建成功')
        }
        showDialog.value = false
        await fetchCategories()
    } catch (e: any) {
        message.error(e.response?.data?.message || '操作失败')
    } finally {
        submitting.value = false
    }
}

function handleDelete(category: Category) {
    dialog.warning({
        title: '确认删除',
        content: `确定要删除分类 "${category.name}" 吗？该分类下的插件将变为未分类。`,
        positiveText: '删除',
        negativeText: '取消',
        onPositiveClick: async () => {
            try {
                await categoryApi.delete(category.id)
                message.success('分类已删除')
                await fetchCategories()
            } catch (e: any) {
                message.error(e.response?.data?.message || '删除失败')
            }
        },
    })
}

onMounted(() => {
    fetchCategories()
})
</script>
