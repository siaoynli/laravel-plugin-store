import axios from 'axios'

const api = axios.create({
    baseURL: '/api/plugin-store',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
})

// 请求拦截器 — 注入 CSRF token
api.interceptors.request.use((config) => {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]')
    if (csrfMeta) {
        config.headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content') || ''
    }
    return config
})

// 响应拦截器 — 统一错误处理
api.interceptors.response.use(
    (response) => response,
    (error) => {
        console.error('API Error:', error.response?.data || error.message)
        return Promise.reject(error)
    },
)

// ================================================================
// 插件 API
// ================================================================
export const pluginApi = {
    /** 插件列表 */
    list: (params?: Record<string, any>) =>
        api.get('/plugins', { params }),

    /** 插件详情 */
    show: (id: number) =>
        api.get(`/plugins/${id}`),

    /** 创建插件 */
    create: (data: Record<string, any>) =>
        api.post('/plugins', data),

    /** 更新插件 */
    update: (id: number, data: Record<string, any>) =>
        api.put(`/plugins/${id}`, data),

    /** 删除插件 */
    delete: (id: number) =>
        api.delete(`/plugins/${id}`),

    /** 切换启用/禁用 */
    toggle: (id: number) =>
        api.patch(`/plugins/${id}/toggle`),

    /** Composer 安装 */
    install: (data: { plugin_id: number; version?: string }) =>
        api.post('/install', data),

    /** 上传 Zip 安装 */
    upload: (formData: FormData) =>
        api.post('/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        }),

    /** 卸载 */
    uninstall: (id: number) =>
        api.post(`/uninstall/${id}`),

    /** 同步已安装插件 */
    refresh: () =>
        api.post('/refresh'),
}

// ================================================================
// 分类 API
// ================================================================
export const categoryApi = {
    /** 获取所有分类 */
    list: () =>
        api.get('/categories'),

    /** 创建分类 */
    create: (data: Record<string, any>) =>
        api.post('/categories', data),

    /** 更新分类 */
    update: (id: number, data: Record<string, any>) =>
        api.put(`/categories/${id}`, data),

    /** 删除分类 */
    delete: (id: number) =>
        api.delete(`/categories/${id}`),
}

// ================================================================
// 类型定义
// ================================================================
export interface Plugin {
    id: number
    category_id: number | null
    category?: Category
    package_name: string
    display_name: string
    slug: string
    description: string | null
    author: string | null
    homepage: string | null
    icon: string | null
    status: 'active' | 'inactive' | 'pending'
    install_type: 'composer' | 'upload'
    installed_version: string | null
    installed_path: string | null
    download_count: number
    settings: Record<string, any> | null
    is_installed: boolean
    is_active: boolean
    latest_version?: PluginVersion
    versions?: PluginVersion[]
    created_at: string
    updated_at: string
}

export interface PluginVersion {
    id: number
    plugin_id: number
    version: string
    changelog: string | null
    file_path: string | null
    file_size: number
    formatted_file_size: string
    min_php_version: string | null
    min_laravel_version: string | null
    download_count: number
    is_latest: boolean
    created_at: string
    updated_at: string
}

export interface Category {
    id: number
    name: string
    slug: string
    description: string | null
    sort_order: number
    plugins_count: number
    created_at: string
    updated_at: string
}

export interface PaginatedResponse<T> {
    data: T[]
    meta: {
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
}
