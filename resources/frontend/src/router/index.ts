import type { RouteRecordRaw } from 'vue-router'

export const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'plugin-list',
        component: () => import('@/pages/PluginList.vue'),
        meta: { title: '插件列表' },
    },
    {
        path: '/create',
        name: 'plugin-create',
        component: () => import('@/pages/PluginCreate.vue'),
        meta: { title: '添加插件' },
    },
    {
        path: '/edit/:id',
        name: 'plugin-edit',
        component: () => import('@/pages/PluginEdit.vue'),
        meta: { title: '编辑插件' },
    },
    {
        path: '/categories',
        name: 'category-manage',
        component: () => import('@/pages/CategoryManage.vue'),
        meta: { title: '分类管理' },
    },
]
