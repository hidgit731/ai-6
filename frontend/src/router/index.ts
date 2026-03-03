import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        redirect: '/notes',
    },
    {
        path: '/notes',
        name: 'notes-list',
        component: () => import('@/pages/NotesListPage.vue'),
    },
    {
        // IMPORTANT: /notes/new MUST be declared before /notes/:id
        // otherwise "new" would be treated as a UUID and cause 404
        path: '/notes/new',
        name: 'note-create',
        component: () => import('@/pages/NoteEditPage.vue'),
    },
    {
        path: '/notes/:id',
        name: 'note-view',
        component: () => import('@/pages/NoteViewPage.vue'),
    },
    {
        path: '/notes/:id/edit',
        name: 'note-edit',
        component: () => import('@/pages/NoteEditPage.vue'),
    },
    {
        // Fallback: catches all unmatched routes → 404
        // MUST be last in the array
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/pages/NotFoundPage.vue'),
    },
]

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
})

export default router
