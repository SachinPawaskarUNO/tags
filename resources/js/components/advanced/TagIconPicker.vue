<script setup>
import { DialogClose, DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle, DialogTrigger } from 'reka-ui';
import { computed, ref, watch } from 'vue';
import { loadTagIcons, pickableIcons, tagIcon } from '../../tagIcons.js';

// An icon picker built on Reka UI: a button showing the icon, which opens a popup of every
// icon users can pick, with a search. Picking None removes the icon.

// An icon name, e.g. 'map-pin', or null for no icon.
const icon = defineModel({ type: String, default: null });

const props = defineProps({
    // The icon names users can pick, from TagManager::icons(). Without it, every Lucide icon.
    availableIcons: { type: Array, default: null },
});

const open = ref(false);
const search = ref('');
const loaded = ref(false);

const current = computed(() => tagIcon(icon.value));

const icons = computed(() => pickableIcons(props.availableIcons));

// Icons whose name contains what's typed. Spaces match the dashes in names, so "map pin" finds map-pin.
const shown = computed(() => {
    const term = search.value.trim().toLowerCase().replace(/\s+/g, '-');

    return term === '' ? icons.value : icons.value.filter(([name]) => name.includes(term));
});

watch(open, async (isOpen) => {
    if (isOpen) {
        search.value = '';
        await loadTagIcons();
        loaded.value = true;
    }
});

function pick(name) {
    icon.value = name;
    open.value = false;
}
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogTrigger as-child>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-md border border-input px-2 py-1 hover:bg-accent hover:text-accent-foreground"
            >
                <component :is="current" v-if="current" class="size-4" aria-hidden="true" />
                {{ icon ?? 'None' }}
                <span class="sr-only">, choose an icon</span>
            </button>
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-black/50" />
            <DialogContent
                :aria-describedby="undefined"
                class="fixed left-1/2 top-1/2 z-50 flex max-h-[85vh] w-[calc(100%-2rem)] max-w-2xl -translate-x-1/2 -translate-y-1/2 flex-col rounded-lg border border-input bg-background p-6 text-sm text-foreground shadow-lg"
            >
                <DialogTitle class="text-lg font-semibold">Choose an icon</DialogTitle>

                <div class="mt-4 flex gap-2">
                    <input
                        v-model="search"
                        type="search"
                        aria-label="Search icons"
                        placeholder="Search icons..."
                        class="min-w-0 flex-1 rounded-md border border-input bg-transparent px-3 py-2 placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/50"
                    />
                    <button
                        type="button"
                        :aria-pressed="!icon"
                        :class="[
                            'rounded-md border border-input px-3 py-2 hover:bg-accent hover:text-accent-foreground',
                            !icon ? 'bg-accent text-accent-foreground' : '',
                        ]"
                        @click="pick(null)"
                    >
                        None
                    </button>
                </div>

                <p class="mt-2 text-xs text-muted-foreground" aria-live="polite">
                    {{ loaded ? `${shown.length} icons` : 'Loading icons...' }}
                </p>

                <div class="mt-2 grid min-h-0 flex-1 grid-cols-[repeat(auto-fill,minmax(2.75rem,1fr))] gap-1 overflow-y-auto p-1">
                    <button
                        v-for="[name, component] in shown"
                        :key="name"
                        type="button"
                        :aria-label="name"
                        :title="name"
                        :aria-pressed="component === current"
                        :class="[
                            'flex aspect-square items-center justify-center rounded-md hover:bg-accent hover:text-accent-foreground',
                            component === current ? 'bg-accent text-accent-foreground ring-2 ring-ring' : '',
                        ]"
                        @click="pick(name)"
                    >
                        <component :is="component" class="size-5" aria-hidden="true" />
                    </button>
                </div>

                <div class="mt-4 flex justify-end">
                    <DialogClose as-child>
                        <button type="button" class="rounded-md border border-input px-3 py-2 hover:bg-accent hover:text-accent-foreground">
                            Cancel
                        </button>
                    </DialogClose>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
