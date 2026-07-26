{{--
    Contenedor de `Alpine.store('ui').toasts` (016-ui-design-system). El store existe desde 005
    pero sin nada que lo renderice todavía — 006 es su primer consumidor real (`resenaForm`/
    `favoritoToggle` llaman a `addToast()` para dar feedback de guardar/eliminar reseña o
    favorito).
--}}
<div
    x-data
    class="pointer-events-none fixed inset-x-0 top-16 z-50 flex flex-col items-center gap-8 px-16"
>
    <template x-for="toast in $store.ui.toasts" :key="toast.id">
        <div
            class="pointer-events-auto w-full max-w-sm rounded-inputs border px-16 py-12 text-body shadow-lg"
            :class="{
                'border-emerald-500 bg-emerald-50 text-emerald-700': toast.type === 'success',
                'border-ember bg-ember/10 text-ember': toast.type === 'error',
                'border-steel bg-paper text-steel': toast.type === 'info',
            }"
            x-text="toast.message"
        ></div>
    </template>
</div>
