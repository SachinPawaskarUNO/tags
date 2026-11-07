<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import TagFilter from '@/vendor/tags/components/TagFilter.vue';
import TagList from '@/vendor/tags/components/TagList.vue';
import { tagIcons } from '@/vendor/tags/tagIcons.js';

// Set up like the README's filtering section.
const props = defineProps(['posts', 'filters', 'tags', 'details']);

const search = ref(props.filters.search ?? '');
const selected = ref(props.filters.tags ?? []);

const apply = () =>
    router.get('/posts', { search: search.value, tags: selected.value }, { preserveState: true, preserveScroll: true, replace: true });

watch(selected, apply);
</script>

<template>
    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold">Posts</h1>
        <form class="mb-4 flex items-center gap-2" @submit.prevent="apply">
            <input
                v-model="search"
                type="search"
                aria-label="Search posts"
                placeholder="Search posts..."
                class="min-w-48 flex-1 rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/50"
            />
            <TagFilter v-model="selected" :tags="tags" :details="details" :icons="tagIcons" />
        </form>
        <ul class="space-y-4">
            <li v-for="post in posts" :key="post.id">
                <Link :href="`/posts/${post.id}/edit`" class="font-medium underline">{{ post.title }}</Link>
                <TagList class="mt-1" :tags="post.tags" :icons="tagIcons" />
            </li>
        </ul>
        <p v-if="posts.length === 0" class="text-sm text-muted-foreground">No posts match.</p>
    </AppLayout>
</template>
