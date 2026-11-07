<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { chipClass, chipStyle } from '../tagColors.js';

// A dropdown of checkboxes for filtering a list by tag, e.g. next to a search bar. It only keeps
// track of the checked tags. Filter the results in your own controller with withAnyTags().

const props = defineProps({
    // The tag names to list. For the page's model's tags, Tag::suggestions(Post::class).
    tags: { type: Array, default: () => [] },
    // The tags' color and icon by name, from Tag::details(Post::class).
    details: { type: Object, default: () => ({}) },
    // Icon components by name, e.g. tagIcons from tagIcons.js. Without it, icons aren't shown.
    icons: { type: Object, default: () => ({}) },
    // When set, the checked tags submit with a regular form, as name[].
    name: { type: String, default: null },
    label: { type: String, default: 'Tags' },
    // The side of the button the dropdown lines up with. 'right' keeps it on screen when the
    // button is at the end of a row, like after a search bar.
    align: { type: String, default: 'right' },
});

const selected = defineModel({ type: Array, default: () => [] });

const open = ref(false);
const root = ref(null);
const button = ref(null);

// A click on Clear removes the button before this runs, so looking for the button would make
// it seem like an outside click. The click's path still shows it came from inside the dropdown.
function closeOnOutsideClick(event) {
    if (!event.composedPath().includes(root.value)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener('click', closeOnOutsideClick));
onBeforeUnmount(() => document.removeEventListener('click', closeOnOutsideClick));

function closeAndRefocus() {
    open.value = false;
    button.value?.focus();
}

// The Clear button disappears once nothing is checked, so keep the keyboard on the Tags button.
function clear() {
    selected.value = [];
    button.value?.focus();
}

// Close when the keyboard moves on to something outside the dropdown.
function onFocusout(event) {
    if (event.relatedTarget && !root.value?.contains(event.relatedTarget)) {
        open.value = false;
    }
}
</script>

<template>
    <div ref="root" class="relative inline-block text-sm" @keydown.esc="closeAndRefocus" @focusout="onFocusout">
        <button
            ref="button"
            type="button"
            class="inline-flex items-center gap-2 rounded-md border border-input px-3 py-2 hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-2 focus:ring-ring/50"
            :aria-expanded="open"
            @click="open = !open"
        >
            {{ label }}
            <span v-if="selected.length > 0" class="rounded-full bg-primary px-2 text-xs text-primary-foreground">
                {{ selected.length }}
                <span class="sr-only">selected</span>
            </span>
            <span :class="['text-xs text-muted-foreground transition-transform', { 'rotate-180': open }]" aria-hidden="true">&#9662;</span>
        </button>

        <!-- Hidden rather than removed when closed, so a regular form still submits the checked tags. -->
        <div
            v-show="open"
            role="group"
            :aria-label="label"
            :class="[
                'absolute z-50 mt-1 w-56 rounded-md border border-input bg-popover text-popover-foreground shadow-md',
                align === 'left' ? 'left-0' : 'right-0',
            ]"
        >
            <div class="max-h-60 overflow-y-auto p-1">
                <label
                    v-for="tag in tags"
                    :key="tag"
                    :title="details[tag]?.description || undefined"
                    class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 hover:bg-accent hover:text-accent-foreground"
                >
                    <input
                        v-model="selected"
                        type="checkbox"
                        :value="tag"
                        :name="name ? `${name}[]` : undefined"
                        class="size-4 shrink-0 rounded border-input"
                    />
                    <span
                        :class="['inline-flex min-w-0 items-center gap-1 rounded-full px-3 py-0.5', chipClass(details[tag]?.color)]"
                        :style="chipStyle(details[tag]?.color)"
                    >
                        <component
                            :is="icons[details[tag]?.icon]"
                            v-if="icons[details[tag]?.icon]"
                            class="size-3.5 shrink-0"
                            aria-hidden="true"
                        />
                        <span class="truncate">{{ tag }}</span>
                    </span>
                </label>

                <p v-if="tags.length === 0" class="px-2 py-1.5 text-muted-foreground">No tags yet</p>
            </div>

            <div v-if="selected.length > 0" class="border-t border-input p-1">
                <button
                    type="button"
                    class="w-full rounded-sm px-2 py-1.5 text-left text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                    @click="clear"
                >
                    Clear
                </button>
            </div>
        </div>
    </div>
</template>
