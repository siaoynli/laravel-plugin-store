/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue'
    const component: DefineComponent<{}, {}, any>
    export default component
}

interface Window {
    __PLUGIN_STORE_CONFIG__: {
        apiBase: string
        csrfToken: string
        user: { id: number; name: string } | null
    }
}
