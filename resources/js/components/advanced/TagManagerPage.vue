<script setup>
import { router } from '@inertiajs/vue3';
import {
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogOverlay,
    AlertDialogPortal,
    AlertDialogRoot,
    AlertDialogTitle,
} from 'reka-ui';
import { computed, ref } from 'vue';
import { chipClass, chipStyle } from '../../tagColors.js';
import { tagIcons } from '../../tagIcons.js';
import TagCreateDialog from './TagCreateDialog.vue';

// The tag manager page for advanced mode: every tag, searchable, with the New tag popup and a
// delete button for each. Render it from the Inertia page in the README's tag manager section,
// passing the props from TagManagerController.

const props = defineProps({
    // Every tag, with its details, model label, use count, and delete URL.
    tags: { type: Array, default: () => [] },
    // Tag names by model type, so the New tag popup can check names for the model it's set to.
    existing: { type: Object, default: () => ({}) },
    // The models to choose from in the New tag popup, from TagManager::models().
    models: { type: Array, default: () => [] },
    // The icon names users can pick, from TagManager::icons().
    icons: { type: Array, default: null },
    // Where the New tag popup's tags are saved.
    storeUrl: { type: String, required: true },
    maxLength: { type: Number, default: 32 },
    // Matches Tag::MAX_DESCRIPTION_LENGTH on the server.
    maxDescriptionLength: { type: Number, default: 255 },
});

const search = ref('');

// Whether to show only tags that aren't on anything.
const unusedOnly = ref(false);

// Whether the New tag popup is open.
const creating = ref(false);

// Whether the delete confirmation is open, and the tag it's for. The tag is kept after it
// closes, since Reka closes it before the Delete button's click handler runs.
const confirming = ref(false);
const deleting = ref(null);

// Errors from the server, e.g. a name another user just took.
const errors = ref({});

// Tags whose name, description, or model contains what's typed, and with Unused only, that
// aren't on anything.
const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.tags.filter(
        (tag) =>
            (!unusedOnly.value || tag.uses === 0) &&
            [tag.name, tag.description, tag.model].some((text) => text?.toLowerCase().includes(term)),
    );
});

const uses = (count) => (count === 1 ? '1 item' : `${count} items`);

function onCreate(tag) {
    router.post(props.storeUrl, tag, {
        preserveScroll: true,
        onSuccess: () => (errors.value = {}),
        onError: (response) => (errors.value = response),
    });
}

function askToDelete(tag) {
    deleting.value = tag;
    confirming.value = true;
}

function confirmDelete() {
    router.delete(deleting.value.deleteUrl, { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            <input
                v-model="search"
                type="search"
                aria-label="Search tags"
                placeholder="Search tags..."
                class="min-w-48 flex-1 rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/50"
            />
            <label class="inline-flex items-center gap-2 text-sm">
                <input v-model="unusedOnly" type="checkbox" class="size-4 rounded border-input" />
                Unused only
            </label>
            <button
                type="button"
                class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground hover:bg-primary/90"
                @click="creating = true"
            >
                New tag
            </button>
        </div>

        <ul v-if="Object.keys(errors).length > 0" role="alert" class="space-y-1 text-sm text-destructive">
            <li v-for="(message, field) in errors" :key="field">{{ message }}</li>
        </ul>

        <div class="overflow-x-auto rounded-md border border-input">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-input text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-3 py-2 font-medium">Tag</th>
                        <th scope="col" class="px-3 py-2 font-medium">Description</th>
                        <th v-if="models.length > 0" scope="col" class="px-3 py-2 font-medium">Model</th>
                        <th scope="col" class="px-3 py-2 font-medium">Used on</th>
                        <th scope="col" class="px-3 py-2"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tag in filtered" :key="tag.id" class="border-b border-input last:border-0">
                        <td class="px-3 py-2">
                            <span
                                :class="['inline-flex items-center gap-1 rounded-full px-3 py-1', chipClass(tag.color)]"
                                :style="chipStyle(tag.color)"
                            >
                                <component :is="tagIcons[tag.icon]" v-if="tagIcons[tag.icon]" class="size-3.5" aria-hidden="true" />
                                {{ tag.name }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">{{ tag.description }}</td>
                        <td v-if="models.length > 0" class="px-3 py-2">{{ tag.model }}</td>
                        <td class="px-3 py-2">{{ uses(tag.uses) }}</td>
                        <td class="px-3 py-2 text-right">
                            <button
                                type="button"
                                class="rounded-md border border-input px-2 py-1 hover:bg-accent hover:text-accent-foreground"
                                :aria-label="`Delete ${tag.name}`"
                                @click="askToDelete(tag)"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="filtered.length === 0">
                        <td colspan="5" class="px-3 py-6 text-center text-muted-foreground">
                            {{
                                tags.length === 0
                                    ? 'No tags yet.'
                                    : unusedOnly && search.trim() === ''
                                      ? 'Every tag is in use.'
                                      : 'No tags match your search.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <TagCreateDialog
            v-model:open="creating"
            :existing="existing"
            :models="models"
            :available-icons="icons"
            :max-length="maxLength"
            :max-description-length="maxDescriptionLength"
            @create="onCreate"
        />

        <AlertDialogRoot v-model:open="confirming">
            <AlertDialogPortal>
                <AlertDialogOverlay class="fixed inset-0 z-50 bg-black/50" />
                <AlertDialogContent
                    class="fixed left-1/2 top-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-lg border border-input bg-background p-6 text-foreground shadow-lg"
                >
                    <AlertDialogTitle class="text-lg font-semibold">Delete "{{ deleting?.name }}"?</AlertDialogTitle>
                    <AlertDialogDescription class="mt-1 text-sm text-muted-foreground">
                        {{
                            deleting?.uses
                                ? `It's removed from the ${uses(deleting.uses)} it's on. This can't be undone.`
                                : "It isn't on anything. This can't be undone."
                        }}
                    </AlertDialogDescription>
                    <div class="mt-4 flex justify-end gap-2 text-sm">
                        <AlertDialogCancel class="rounded-md border border-input px-3 py-2 hover:bg-accent hover:text-accent-foreground">
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction
                            class="rounded-md bg-destructive px-3 py-2 text-white hover:bg-destructive/90"
                            @click="confirmDelete"
                        >
                            Delete
                        </AlertDialogAction>
                    </div>
                </AlertDialogContent>
            </AlertDialogPortal>
        </AlertDialogRoot>
    </div>
</template>
