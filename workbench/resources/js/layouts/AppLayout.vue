<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="mx-auto max-w-3xl p-6">
        <nav class="mb-6 flex gap-4 text-sm underline">
            <Link href="/posts">Posts</Link>
            <Link href="/admin/tags">Tag manager</Link>
        </nav>
        <slot />
    </div>
</template>
