<script setup>
import { computed, ref } from 'vue';

// Attributes like id and aria-label go on the text input, so <label for="..."> works.
defineOptions({ inheritAttrs: false });

const props = defineProps({
    // Tag names to offer as one-click suggestions. These suggestions are chosen in the app. 
    suggestions: { type: Array, default: () => [] },
    // When set, renders hidden inputs so the tags submit with a regular form.
    name: { type: String, default: null },
    placeholder: { type: String, default: 'Add a tag...' },
    // Keep in sync with the app's validation, e.g. 'tags.*' => 'max:50'.
    maxLength: { type: Number, default: 50 },
});

const tags = defineModel({ type: Array, default: () => [] });

const input = ref('');

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
}

// Enter or comma adds the typed tag. Backspace in an empty input removes the last tag.
function onKeydown(event) {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        addTag(input.value);
    } else if (event.key === 'Backspace' && input.value === '' && tags.value.length > 0) {
        tags.value = tags.value.slice(0, -1);
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
                class="inline-flex items-center gap-1 rounded-full bg-muted px-3 py-1 text-sm text-muted-foreground"
            >
                {{ tag }}
                <button
                    type="button"
                    class="hover:text-foreground"
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
    </div>
</template>
