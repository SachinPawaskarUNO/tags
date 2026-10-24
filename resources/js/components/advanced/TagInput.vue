<script>
// Gives each TagInput's fields their own ids, for <label for="...">.
let nextId = 0;
</script>

<script setup>
import { computed, ref, watch } from 'vue';
import { chipClass, chipStyle } from '../../tagColors.js';
import { pickableIcons, tagIcons } from '../../tagIcons.js';
import TagColorPicker from './TagColorPicker.vue';
import TagCreateDialog from './TagCreateDialog.vue';

// TagInput for advanced mode: existing tags are listed under the input and filtered by typing, and
// new tags are made with the New tag popup, which sets their description, color, icon, and
// model. Clicking a tag changes its color, icon, and description. Needs the installs in the
// README's advanced mode section.

// Attributes like id and aria-label go on the text input, so <label for="..."> works.
defineOptions({ inheritAttrs: false });

const props = defineProps({
    // Tag names to suggest, e.g. TagManager::suggestions(Post::class) for the page's model's tags.
    // A list grouped by model, like { Post: ['pottery'] }, shows a heading for each.
    // Typing only adds tags from this list.
    suggestions: { type: [Array, Object], default: () => [] },
    // Every tag's saved color, icon, and description by name, from TagManager::details(). Picked
    // tags show them right away, and names from other models aren't offered for creating.
    details: { type: Object, default: () => ({}) },
    // The models to choose from in the New tag popup, from TagManager::models().
    models: { type: Array, default: () => [] },
    // The page's model type, e.g. 'App\\Models\\Post'. The New tag popup starts on it.
    model: { type: String, default: null },
    // The icon names users can pick, from TagManager::icons(). Without it, every icon in tagIcons.js shows.
    availableIcons: { type: Array, default: null },
    // When set, render hidden inputs so the tags and their details submit with a regular form.
    name: { type: String, default: null },
    colorsName: { type: String, default: null },
    iconsName: { type: String, default: null },
    descriptionsName: { type: String, default: null },
    modelTypesName: { type: String, default: null },
    placeholder: { type: String, default: 'Add a tag...' },
    // Keep in sync with the app's validation, e.g. 'tags.*' => 'max:32'.
    maxLength: { type: Number, default: 32 },
    // Matches Tag::MAX_DESCRIPTION_LENGTH on the server.
    maxDescriptionLength: { type: Number, default: 255 },
});

const tags = defineModel({ type: Array, default: () => [] });

// Details by tag name. Save them with TagManager::setColors(), setIcons(), setDescriptions(),
// and setModelTypes(), after syncTags().
const colors = defineModel('colors', { type: Object, default: () => ({}) });
const icons = defineModel('icons', { type: Object, default: () => ({}) });
const descriptions = defineModel('descriptions', { type: Object, default: () => ({}) });
const modelTypes = defineModel('modelTypes', { type: Object, default: () => ({}) });

const input = ref('');

// The tag whose color, icon, and description are being changed, if any.
const editing = ref(null);

// Whether the New tag popup is open, and the name it starts with.
const creating = ref(false);
const creatingName = ref('');

// A typed name that isn't a tag yet, to offer creating it.
const unknownName = ref('');

// A typed name that's a tag for another model, so it isn't offered for creating.
const otherModelName = ref('');

const id = `tag-input-${nextId++}`;

// Match Tag::normalizeName() on the server.
const normalize = (name) => name.trim().toLowerCase();

const grouped = computed(() => (Array.isArray(props.suggestions) ? { '': props.suggestions } : props.suggestions));

// The tag names that can be added here: the suggestions, plus the tags already added.
const known = computed(() => new Set([...Object.values(grouped.value).flat().map(normalize), ...tags.value]));

// Every tag name that exists, including other models' tags.
const allNames = computed(() => new Set([...known.value, ...Object.keys(props.details).map(normalize)]));

// A tag's color, icon, or description: the one set here, or else the saved one from details.
// Saved ones are only shown, not added to the v-models, so saving doesn't rewrite them.
const detail = (changed, tag, key) => (tag in changed ? changed[tag] : (props.details[tag]?.[key] ?? null));
const colorOf = (tag) => detail(colors.value, tag, 'color');
const iconOf = (tag) => detail(icons.value, tag, 'icon');
const descriptionOf = (tag) => detail(descriptions.value, tag, 'description');

// The existing tags listed under the input: the suggestions that match what's typed, without
// tags already added. A plain list is one group with no heading.
const groups = computed(() => {
    const search = normalize(input.value);

    return Object.entries(grouped.value)
        .map(([label, names]) => ({
            label,
            options: [...new Set(names.map(normalize))].filter(
                (suggestion) => !tags.value.includes(suggestion) && suggestion.includes(search),
            ),
        }))
        .filter((group) => group.options.length > 0);
});

const options = computed(() => groups.value.flatMap((group) => group.options));

const editableIcons = computed(() => pickableIcons(props.availableIcons));

watch(input, () => {
    unknownName.value = '';
    otherModelName.value = '';
});

// Adds an existing tag. A name that isn't a tag yet isn't added; the user is offered
// the New tag popup instead. Another model's tag isn't added either.
function addTag(name) {
    const tag = normalize(name);

    if (tag === '') {
        return;
    }

    if (!known.value.has(tag)) {
        if (allNames.value.has(tag)) {
            otherModelName.value = tag;
        } else {
            unknownName.value = tag;
        }

        return;
    }

    if (!tags.value.includes(tag)) {
        tags.value = [...tags.value, tag];
    }

    input.value = '';
}

function openCreate(name = '') {
    creatingName.value = name;
    creating.value = true;
}

function onCreate(tag) {
    tags.value = [...tags.value, tag.name];

    if (tag.color) {
        colors.value = { ...colors.value, [tag.name]: tag.color };
    }

    if (tag.icon) {
        icons.value = { ...icons.value, [tag.name]: tag.icon };
    }

    if (tag.description) {
        descriptions.value = { ...descriptions.value, [tag.name]: tag.description };
    }

    if (tag.modelType) {
        modelTypes.value = { ...modelTypes.value, [tag.name]: tag.modelType };
    }

    input.value = '';
    unknownName.value = '';
}

// Returns the details without the tag's entry.
const without = (details, tag) => Object.fromEntries(Object.entries(details).filter(([name]) => name !== tag));

function removeTag(tag) {
    tags.value = tags.value.filter((existing) => existing !== tag);
    colors.value = without(colors.value, tag);
    icons.value = without(icons.value, tag);
    descriptions.value = without(descriptions.value, tag);
    modelTypes.value = without(modelTypes.value, tag);

    if (editing.value === tag) {
        editing.value = null;
    }
}

function toggleEditing(tag) {
    editing.value = editing.value === tag ? null : tag;
}

// Unlike the palette, the picker stays open, so the color can be adjusted more than once.
function setColor(tag, color) {
    colors.value = { ...colors.value, [tag]: color };
}

// A null icon removes the tag's icon.
function setIcon(tag, icon) {
    icons.value = { ...icons.value, [tag]: icon };
}

function setDescription(tag, description) {
    descriptions.value = { ...descriptions.value, [tag]: description };
}

// Enter or comma adds the typed tag, or the only tag left in the list. Backspace in an empty
// input removes the last tag.
function onKeydown(event) {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        const typed = normalize(input.value);
        addTag(!known.value.has(typed) && options.value.length === 1 ? options.value[0] : typed);
    } else if (event.key === 'Backspace' && input.value === '' && tags.value.length > 0) {
        removeTag(tags.value[tags.value.length - 1]);
    }
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-start gap-2">
            <div class="relative flex-1">
                <div
                    class="flex flex-wrap items-center gap-2 rounded-md border border-input px-2 py-1.5 focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
                >
                    <span
                        v-for="tag in tags"
                        :key="tag"
                        :class="['inline-flex items-center gap-1 rounded-full text-sm', chipClass(colorOf(tag))]"
                        :style="chipStyle(colorOf(tag))"
                        :title="descriptionOf(tag) || undefined"
                    >
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 py-1 pl-3"
                            :aria-label="`Change the details of ${tag}`"
                            :aria-expanded="editing === tag"
                            @click="toggleEditing(tag)"
                        >
                            <component :is="tagIcons[iconOf(tag)]" v-if="tagIcons[iconOf(tag)]" class="size-3.5" aria-hidden="true" />
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
                        autocomplete="off"
                        :aria-controls="`${id}-existing`"
                        class="min-w-32 flex-1 border-0 bg-transparent p-1 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-0"
                        :placeholder="placeholder"
                        :maxlength="maxLength"
                        @keydown="onKeydown"
                    />
                </div>
            </div>

            <button
                type="button"
                class="shrink-0 rounded-md border border-input px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground"
                @click="openCreate()"
            >
                New tag
            </button>
        </div>

        <div v-if="options.length > 0" :id="`${id}-existing`" class="max-h-40 space-y-2 overflow-y-auto text-sm">
            <div v-for="group in groups" :key="group.label" class="flex flex-wrap items-center gap-2">
                <span class="text-muted-foreground">{{ group.label || 'Existing tags' }}:</span>
                <button
                    v-for="option in group.options"
                    :key="option"
                    type="button"
                    :aria-label="`Add ${option}`"
                    :title="descriptionOf(option) || undefined"
                    :class="[
                        'inline-flex items-center gap-1 rounded-full px-3 py-1 hover:opacity-80',
                        chipClass(colorOf(option)),
                    ]"
                    :style="chipStyle(colorOf(option))"
                    @click="addTag(option)"
                >
                    <component :is="tagIcons[iconOf(option)]" v-if="tagIcons[iconOf(option)]" class="size-3.5" aria-hidden="true" />
                    {{ option }}
                </button>
            </div>
        </div>

        <p v-if="unknownName" class="text-sm text-muted-foreground">
            There's no tag named "{{ unknownName }}".
            <button type="button" class="font-medium text-foreground underline" @click="openCreate(unknownName)">
                Create it
            </button>
        </p>

        <p v-if="otherModelName" class="text-sm text-muted-foreground">
            "{{ otherModelName }}" is a tag for another model, so it can't be added here.
        </p>

        <div v-if="editing" class="space-y-2 text-sm">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-muted-foreground">Color for {{ editing }}:</span>
                <TagColorPicker
                    :key="editing"
                    :model-value="colorOf(editing)"
                    @update:model-value="(color) => setColor(editing, color)"
                />
            </div>

            <div v-if="editableIcons.length > 0" class="flex flex-wrap items-center gap-2">
                <span class="text-muted-foreground">Icon for {{ editing }}:</span>
                <button
                    type="button"
                    :aria-pressed="!iconOf(editing)"
                    :class="[
                        'rounded-md border border-input px-2 py-1 hover:bg-accent hover:text-accent-foreground',
                        !iconOf(editing) ? 'bg-accent text-accent-foreground' : '',
                    ]"
                    @click="setIcon(editing, null)"
                >
                    None
                </button>
                <button
                    v-for="[iconName, icon] in editableIcons"
                    :key="iconName"
                    type="button"
                    :aria-label="iconName"
                    :aria-pressed="iconOf(editing) === iconName"
                    :class="[
                        'rounded-md border border-input p-1.5 hover:bg-accent hover:text-accent-foreground',
                        iconOf(editing) === iconName ? 'bg-accent text-accent-foreground' : '',
                    ]"
                    @click="setIcon(editing, iconName)"
                >
                    <component :is="icon" class="size-4" aria-hidden="true" />
                </button>
            </div>

            <div class="space-y-1">
                <label :for="`${id}-description`" class="text-muted-foreground">Description for {{ editing }}:</label>
                <textarea
                    :id="`${id}-description`"
                    :value="descriptionOf(editing) ?? ''"
                    rows="2"
                    :maxlength="maxDescriptionLength"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring/50"
                    @input="(event) => setDescription(editing, event.target.value)"
                />
            </div>
        </div>

        <TagCreateDialog
            v-model:open="creating"
            :initial-name="creatingName"
            :initial-model="model"
            :existing="[...allNames]"
            :models="models"
            :available-icons="availableIcons"
            :max-length="maxLength"
            :max-description-length="maxDescriptionLength"
            @create="onCreate"
        />

        <template v-if="name">
            <input v-for="tag in tags" :key="tag" type="hidden" :name="`${name}[]`" :value="tag" />
        </template>

        <template v-for="([fieldName, details], index) in [
            [colorsName, colors],
            [iconsName, icons],
            [descriptionsName, descriptions],
            [modelTypesName, modelTypes],
        ]" :key="index">
            <template v-if="fieldName">
                <template v-for="tag in tags" :key="`${fieldName}-${tag}`">
                    <input v-if="details[tag]" type="hidden" :name="`${fieldName}[${tag}]`" :value="details[tag]" />
                </template>
            </template>
        </template>
    </div>
</template>
