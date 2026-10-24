<script setup>
import { computed } from 'vue';
import { chipClass, chipStyle } from '../tagColors.js';

const props = defineProps({
    // Tag names, or tag objects with a name and optional color and icon, e.g. post.tags.
    tags: { type: Array, default: () => [] },
    // Icon components by name, e.g. tagIcons from tagIcons.js. Without it, icons aren't shown.
    icons: { type: Object, default: () => ({}) },
});

const items = computed(() => props.tags.map((tag) => (typeof tag === 'string' ? { name: tag, color: null } : tag)));
</script>

<template>
    <ul v-if="items.length > 0" class="flex flex-wrap gap-2">
        <li
            v-for="tag in items"
            :key="tag.name"
            :class="['inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm', chipClass(tag.color)]"
            :style="chipStyle(tag.color)"
        >
            <component :is="icons[tag.icon]" v-if="icons[tag.icon]" class="size-3.5" aria-hidden="true" />
            {{ tag.name }}
        </li>
    </ul>
</template>
