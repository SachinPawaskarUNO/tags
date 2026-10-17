<script setup>
import { computed } from 'vue';

const props = defineProps({
    // Tag names, or tag objects with a name, e.g. post.tags.
    tags: { type: Array, default: () => [] },
});

const names = computed(() => props.tags.map((tag) => (typeof tag === 'string' ? tag : tag.name)));
</script>

<template>
    <ul v-if="names.length > 0" class="flex flex-wrap gap-2">
        <li
            v-for="name in names"
            :key="name"
            class="rounded-full bg-muted px-3 py-1 text-sm text-muted-foreground"
        >
            {{ name }}
        </li>
    </ul>
</template>
