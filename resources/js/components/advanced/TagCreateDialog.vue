<script>
// Gives each dialog's fields their own ids, for <label for="...">.
let nextId = 0;
</script>

<script setup>
import { ref, watch } from 'vue';
import { DialogClose, DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import TagColorPicker from './TagColorPicker.vue';
import TagIconPicker from './TagIconPicker.vue';

// A popup for creating a tag in advanced mode, with a name, description, color, icon, and model.
// It doesn't save anything: Create emits the new tag, for TagInput to add to the form or
// TagManagerPage to save.

const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    // The name to start with, e.g. what the user typed.
    initialName: { type: String, default: '' },
    // The model type to start with, e.g. the page's model. Without it, or if it isn't one of
    // models, the popup starts on the first model.
    initialModel: { type: String, default: null },
    // Tag names that already exist, so they aren't created again: a list, or names by model
    // type, like { 'App\Models\Post': ['pottery'] }, to check the model the popup is set to.
    existing: { type: [Array, Object], default: () => [] },
    // The models to choose from, from TagManager::models(). Without them, the model field is hidden.
    models: { type: Array, default: () => [] },
    // Show the model field but don't let it change, e.g. in TagInput, where new tags are for the page's model.
    lockModel: { type: Boolean, default: false },
    // The icon names users can pick, from TagManager::icons().
    availableIcons: { type: Array, default: null },
    maxLength: { type: Number, default: 32 },
    // Matches Tag::MAX_DESCRIPTION_LENGTH on the server.
    maxDescriptionLength: { type: Number, default: 255 },
});

const emit = defineEmits(['create']);

const id = `tag-create-${nextId++}`;

// Match Tag::normalizeName() on the server.
const normalize = (name) => name.trim().toLowerCase();

const blank = () => ({
    name: props.initialName,
    description: '',
    color: null,
    icon: null,
    modelType: props.models.some((model) => model.value === props.initialModel)
        ? props.initialModel
        : (props.models[0]?.value ?? null),
});

const form = ref(blank());

const error = ref('');

// Start fresh each time the popup opens.
watch(open, (isOpen) => {
    if (isOpen) {
        form.value = blank();
        error.value = '';
    }
});

function submit() {
    const name = normalize(form.value.name);

    if (name === '') {
        error.value = 'Enter a name for the tag.';

        return;
    }

    const taken = Array.isArray(props.existing) ? props.existing : (props.existing[form.value.modelType] ?? []);

    if (taken.includes(name)) {
        error.value = `A tag named "${name}" already exists${props.models.length > 0 ? ' for this model' : ''}.`;

        return;
    }

    emit('create', {
        name,
        description: form.value.description.trim() || null,
        color: form.value.color,
        icon: form.value.icon,
        modelType: form.value.modelType,
    });

    open.value = false;
}
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-black/50" />
            <DialogContent
                :aria-describedby="undefined"
                class="fixed left-1/2 top-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-lg border border-input bg-background p-6 text-foreground shadow-lg"
            >
                <DialogTitle class="text-lg font-semibold">New tag</DialogTitle>

                <form class="mt-4 space-y-4 text-sm" @submit.prevent="submit">
                    <div class="space-y-1">
                        <label :for="`${id}-name`" class="font-medium">Name</label>
                        <input
                            :id="`${id}-name`"
                            v-model="form.name"
                            type="text"
                            :maxlength="maxLength"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring/50"
                        />
                    </div>

                    <div class="space-y-1">
                        <label :for="`${id}-description`" class="font-medium">Description</label>
                        <textarea
                            :id="`${id}-description`"
                            v-model="form.description"
                            rows="3"
                            :maxlength="maxDescriptionLength"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring/50"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">Color</span>
                        <TagColorPicker v-model="form.color" />
                    </div>

                    <div v-if="availableIcons === null || availableIcons.length > 0" class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">Icon</span>
                        <TagIconPicker v-model="form.icon" :available-icons="availableIcons" />
                    </div>

                    <div v-if="models.length > 0" class="space-y-1">
                        <label :for="`${id}-model`" class="font-medium">Model</label>
                        <select
                            :id="`${id}-model`"
                            v-model="form.modelType"
                            :disabled="lockModel"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <option v-for="model in models" :key="model.value" :value="model.value">
                                {{ model.label }}
                            </option>
                        </select>
                    </div>

                    <p v-if="error" role="alert" class="text-destructive">{{ error }}</p>

                    <div class="flex justify-end gap-2">
                        <DialogClose as-child>
                            <button type="button" class="rounded-md border border-input px-3 py-2 hover:bg-accent hover:text-accent-foreground">
                                Cancel
                            </button>
                        </DialogClose>
                        <button type="submit" class="rounded-md bg-primary px-3 py-2 text-primary-foreground hover:bg-primary/90">
                            Create
                        </button>
                    </div>
                </form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
