<script setup>
import { computed } from 'vue';
import { chipClass } from '../tagColors.js';

const props = defineProps({
    // Tag names, or tag objects with a name, e.g. post.tags. A tag's color comes from
    // tag.pivot.color (the color on this model), or tag.color for plain objects.
    tags: { type: Array, default: () => [] },
});

const items = computed(() =>
    props.tags.map((tag) =>
        typeof tag === 'string' ? { name: tag, color: null } : { name: tag.name, color: tag.pivot?.color ?? tag.color ?? null },
    ),
);
</script>

<template>
    <ul v-if="items.length > 0" class="flex flex-wrap gap-2">
        <li
            v-for="tag in items"
            :key="tag.name"
            :class="['rounded-full px-3 py-1 text-sm', chipClass(tag.color)]"
        >
            {{ tag.name }}
        </li>
    </ul>
</template>
