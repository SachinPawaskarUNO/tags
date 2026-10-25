<script setup>
import { computed, ref } from 'vue';
import { chipClass, tagColors } from '../../tagColors.js';

// Attributes like id and aria-label go on the text input, so <label for="..."> works.
defineOptions({ inheritAttrs: false });

const props = defineProps({
    // Tag names to offer as one-click suggestions. These suggestions are chosen in the app.
    suggestions: { type: Array, default: () => [] },
    // When set, renders hidden inputs so the tags submit with a regular form.
    name: { type: String, default: null },
    // When set, renders hidden inputs so the colors submit with a regular form.
    colorsName: { type: String, default: null },
    placeholder: { type: String, default: 'Add a tag...' },
    // Keep in sync with the app's validation, e.g. 'tags.*' => 'max:32'.
    maxLength: { type: Number, default: 32 },
});

const tags = defineModel({ type: Array, default: () => [] });

// Colors by tag name, e.g. { pottery: 'red' }. Save them with TagManager::setColors().
const colors = defineModel('colors', { type: Object, default: () => ({}) });

const input = ref('');

// The tag whose color is being picked, if any.
const editing = ref(null);

// Match Tag::normalizeName() on the server.
const normalize = (name) => name.trim().toLowerCase();

// Suggestions that match what's typed and aren't already added.
const filteredSuggestions = computed(() => {
    const search = normalize(input.value);

    return props.suggestions
        .map(normalize)
        .filter((suggestion) => !tags.value.includes(suggestion) && suggestion.includes(search));
});

function addTag(name) {
    const tag = normalize(name);

    if (tag !== '' && !tags.value.includes(tag)) {
        tags.value = [...tags.value, tag];
    }

    input.value = '';
}

function removeTag(tag) {
    tags.value = tags.value.filter((existing) => existing !== tag);

    const { [tag]: removed, ...rest } = colors.value;
    colors.value = rest;

    if (editing.value === tag) {
        editing.value = null;
    }
}

function toggleEditing(tag) {
    editing.value = editing.value === tag ? null : tag;
}

function setColor(tag, color) {
    colors.value = { ...colors.value, [tag]: color };
    editing.value = null;
}

// Enter or comma adds the typed tag. Backspace in an empty input removes the last tag.
function onKeydown(event) {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        addTag(input.value);
    } else if (event.key === 'Backspace' && input.value === '' && tags.value.length > 0) {
        removeTag(tags.value[tags.value.length - 1]);
    }
}
</script>

<template>
    <div class="space-y-2">
        <div
            class="flex flex-wrap items-center gap-2 rounded-md border border-input px-2 py-1.5 focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
        >
            <span
                v-for="tag in tags"
                :key="tag"
                :class="['inline-flex items-center gap-1 rounded-full text-sm', chipClass(colors[tag])]"
            >
                <button
                    type="button"
                    class="py-1 pl-3"
                    :aria-label="`Change the color of ${tag}`"
                    :aria-expanded="editing === tag"
                    @click="toggleEditing(tag)"
                >
                    {{ tag }}
                </button>
                <button
                    type="button"
                    class="py-1 pr-3 opacity-70 hover:opacity-100"
                    :aria-label="`Remove ${tag}`"
                    @click="removeTag(tag)"
                >
                    &times;
                </button>
            </span>

            <input
                v-bind="$attrs"
                v-model="input"
                type="text"
                class="min-w-32 flex-1 border-0 bg-transparent p-1 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-0"
                :placeholder="placeholder"
                :maxlength="maxLength"
                @keydown="onKeydown"
            />
        </div>

        <div v-if="editing" class="flex flex-wrap items-center gap-2 text-sm">
            <span class="text-muted-foreground">Color for {{ editing }}:</span>
            <button
                v-for="(classes, color) in tagColors"
                :key="color"
                type="button"
                :class="[
                    'size-6 rounded-full',
                    classes.swatch,
                    (colors[editing] ?? 'gray') === color ? 'ring-2 ring-ring ring-offset-2 ring-offset-background' : '',
                ]"
                :aria-label="color"
                :aria-pressed="(colors[editing] ?? 'gray') === color"
                @click="setColor(editing, color)"
            />
        </div>

        <div v-if="filteredSuggestions.length > 0" class="flex flex-wrap items-center gap-2 text-sm">
            <span class="text-muted-foreground">Common tags:</span>
            <button
                v-for="suggestion in filteredSuggestions"
                :key="suggestion"
                type="button"
                class="rounded-full border border-input px-3 py-1 hover:bg-accent hover:text-accent-foreground"
                @click="addTag(suggestion)"
            >
                {{ suggestion }}
            </button>
        </div>

        <template v-if="name">
            <input v-for="tag in tags" :key="tag" type="hidden" :name="`${name}[]`" :value="tag" />
        </template>

        <template v-if="colorsName">
            <template v-for="tag in tags" :key="`color-${tag}`">
                <input v-if="colors[tag]" type="hidden" :name="`${colorsName}[${tag}]`" :value="colors[tag]" />
            </template>
        </template>
    </div>
</template>
