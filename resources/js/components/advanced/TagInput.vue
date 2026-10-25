<script>
// Gives each TagInput's fields their own ids, for <label for="...">.
let nextId = 0;
</script>

<script setup>
import { ChevronDown } from '@lucide/vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxLabel,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxTrigger,
    ComboboxViewport,
} from 'reka-ui';
import { computed, ref, watch } from 'vue';
import { chipClass, chipStyle } from '../../tagColors.js';
import { tagIcons } from '../../tagIcons.js';
import TagColorPicker from './TagColorPicker.vue';
import TagCreateDialog from './TagCreateDialog.vue';
import TagIconPicker from './TagIconPicker.vue';

// TagInput for advanced mode: the page's model's tags are in a dropdown under the input,
// filtered by typing, and new tags are made with the New tag popup, which sets their
// description, color, and icon. New tags are for the page's model. Needs the installs in the
// README's advanced mode section.

// Attributes like id and aria-label go on the text input, so <label for="..."> works.
defineOptions({ inheritAttrs: false });

const props = defineProps({
    // Tag names to suggest, e.g. TagManager::suggestions(Post::class) for the page's model's tags.
    // A list grouped by model shows a heading for each.
    // Typing only adds tags from this list.
    suggestions: { type: [Array, Object], default: () => [] },
    // The page's model's tags' saved color, icon, and description by name, from
    // TagManager::details(Post::class). Picked tags show them right away.
    details: { type: Object, default: () => ({}) },
    // The models from TagManager::models(), for the New tag popup's model field.
    models: { type: Array, default: () => [] },
    // The page's model type, from $post->getMorphClass(), e.g. App\Models\Post. New tags are
    // for this model, so the New tag popup shows it but doesn't let it change.
    model: { type: String, default: null },
    // The icon names users can pick, from TagManager::icons(). Without it, every Lucide icon.
    availableIcons: { type: Array, default: null },
    // When set, render hidden inputs so the tags and their details submit with a regular form.
    name: { type: String, default: null },
    colorsName: { type: String, default: null },
    iconsName: { type: String, default: null },
    descriptionsName: { type: String, default: null },
    placeholder: { type: String, default: 'Search tags...' },
    // Keep in sync with the app's validation, e.g. 'tags.*' => 'max:32'.
    maxLength: { type: Number, default: 32 },
    // Matches Tag::MAX_DESCRIPTION_LENGTH on the server.
    maxDescriptionLength: { type: Number, default: 255 },
});

const tags = defineModel({ type: Array, default: () => [] });

// Details by tag name. Save them with TagManager::setColors(), setIcons(), and
// setDescriptions(), passing the page's model, after syncTags().
const colors = defineModel('colors', { type: Object, default: () => ({}) });
const icons = defineModel('icons', { type: Object, default: () => ({}) });
const descriptions = defineModel('descriptions', { type: Object, default: () => ({}) });

const input = ref('');

// The tag whose color, icon, and description are being changed, if any.
const editing = ref(null);

// Whether the New tag popup is open, and the name it starts with.
const creating = ref(false);
const creatingName = ref('');

// A typed name that isn't one of this model's tags yet, to offer creating it.
const unknownName = ref('');

const id = `tag-input-${nextId++}`;

// Match Tag::normalizeName() on the server.
const normalize = (name) => name.trim().toLowerCase();

const grouped = computed(() => (Array.isArray(props.suggestions) ? { '': props.suggestions } : props.suggestions));

// The tag names that can be added here: the suggestions, plus the tags already added.
const known = computed(() => new Set([...Object.values(grouped.value).flat().map(normalize), ...tags.value]));

// The New tag popup's model field: just the page's model, which it can't change. Without
// the page's model in models, the field is hidden.
const lockedModels = computed(() => props.models.filter((option) => option.value === props.model));

// A tag's color, icon, or description: the one set here, or else the saved one from details.
// Saved ones are only shown, not added to the v-models, so saving doesn't rewrite them.
const detail = (changed, tag, key) => (tag in changed ? changed[tag] : (props.details[tag]?.[key] ?? null));
const colorOf = (tag) => detail(colors.value, tag, 'color');
const iconOf = (tag) => detail(icons.value, tag, 'icon');
const descriptionOf = (tag) => detail(descriptions.value, tag, 'description');

// The existing tags in the dropdown: the suggestions that match what's typed, without tags
// already added. A plain list is one group with no heading. Reka's own filtering is off, so
// matching follows normalize().
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

// The ComboboxRoot, for the option the arrow keys are on.
const combobox = ref(null);

watch(input, () => {
    unknownName.value = '';
});

// Adds an existing tag. A name that isn't one of this model's tags yet isn't added; the
// user is offered the New tag popup instead.
function addTag(name) {
    const tag = normalize(name);

    if (tag === '') {
        return;
    }

    if (!known.value.has(tag)) {
        unknownName.value = tag;

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

    if (editing.value === tag) {
        editing.value = null;
    }
}

function toggleEditing(tag) {
    editing.value = editing.value === tag ? null : tag;
}

// The color picker stays open, so the color can be adjusted more than once.
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

// Picking a tag in the dropdown. Only tags not yet added are listed, so this always adds one.
function onPick(value) {
    tags.value = value;
    input.value = '';
}

// Runs before Reka's keys. Enter or comma adds the typed tag when it's an existing one, even
// if the dropdown has another tag highlighted. Otherwise comma picks the highlighted tag, as
// Enter does in Reka.
function onKeydownCapture(event) {
    if (event.key !== 'Enter' && event.key !== ',') {
        return;
    }

    const typed = normalize(input.value);
    const highlighted = combobox.value?.highlightedElement;

    if (known.value.has(typed)) {
        event.preventDefault();
        event.stopPropagation();
        addTag(typed);
    } else if (event.key === ',' && highlighted?.isConnected) {
        event.preventDefault();
        event.stopPropagation();
        highlighted.click();
    }
}

// Reka handles the arrow keys, Escape, and Enter on a highlighted tag. Enter or comma with
// nothing highlighted tries the typed name, which offers the New tag popup when it isn't a tag.
// Backspace in an empty input removes the last tag.
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
        <div class="flex items-start gap-2">
            <ComboboxRoot
                ref="combobox"
                :model-value="tags"
                multiple
                ignore-filter
                open-on-focus
                open-on-click
                :reset-search-term-on-blur="false"
                class="flex-1"
                @update:model-value="onPick"
            >
                <ComboboxAnchor
                    class="flex flex-wrap items-center gap-2 rounded-md border border-input px-2 py-1.5 focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
                    @keydown.capture="onKeydownCapture"
                    @keydown="onKeydown"
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

                    <ComboboxInput
                        v-bind="$attrs"
                        v-model="input"
                        class="min-w-32 flex-1 border-0 bg-transparent p-1 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-0"
                        :placeholder="placeholder"
                        :maxlength="maxLength"
                    />

                    <!-- Opens and closes the list of existing tags. The arrow flips while it's open. -->
                    <ComboboxTrigger
                        aria-label="Show existing tags"
                        :disabled="options.length === 0"
                        class="group ml-auto rounded-sm p-1 text-muted-foreground hover:text-foreground disabled:opacity-50"
                    >
                        <ChevronDown class="size-4 transition-transform group-data-[state=open]:rotate-180" aria-hidden="true" />
                    </ComboboxTrigger>
                </ComboboxAnchor>

                <ComboboxPortal v-if="options.length > 0">
                    <ComboboxContent
                        position="popper"
                        :side-offset="4"
                        aria-label="Existing tags"
                        class="z-50 w-[var(--reka-combobox-trigger-width)] rounded-md border border-input bg-popover text-sm text-popover-foreground shadow-md"
                    >
                        <!-- A hint that typing searches the list; it stays put while the list scrolls. -->
                        <p class="border-b border-input px-3 py-1.5 text-xs text-muted-foreground" aria-hidden="true">
                            Type to search tags
                        </p>
                        <ComboboxViewport class="max-h-60 p-1">
                            <ComboboxGroup v-for="group in groups" :key="group.label">
                                <ComboboxLabel v-if="group.label" class="px-2 py-1 text-xs font-medium text-muted-foreground">
                                    {{ group.label }}
                                </ComboboxLabel>
                                <ComboboxItem
                                    v-for="option in group.options"
                                    :key="option"
                                    :value="option"
                                    :title="descriptionOf(option) || undefined"
                                    class="flex cursor-pointer items-center rounded-sm px-2 py-1.5 outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                                >
                                    <span
                                        :class="['inline-flex items-center gap-1 rounded-full px-3 py-0.5', chipClass(colorOf(option))]"
                                        :style="chipStyle(colorOf(option))"
                                    >
                                        <component
                                            :is="tagIcons[iconOf(option)]"
                                            v-if="tagIcons[iconOf(option)]"
                                            class="size-3.5"
                                            aria-hidden="true"
                                        />
                                        {{ option }}
                                    </span>
                                </ComboboxItem>
                            </ComboboxGroup>
                        </ComboboxViewport>
                    </ComboboxContent>
                </ComboboxPortal>
            </ComboboxRoot>

            <button
                type="button"
                class="shrink-0 rounded-md border border-input px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground"
                @click="openCreate()"
            >
                New tag
            </button>
        </div>

        <p v-if="unknownName" class="text-sm text-muted-foreground">
            There's no tag named "{{ unknownName }}".
            <button type="button" class="font-medium text-foreground underline" @click="openCreate(unknownName)">
                Create it
            </button>
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

            <div v-if="availableIcons === null || availableIcons.length > 0" class="flex flex-wrap items-center gap-2">
                <span class="text-muted-foreground">Icon for {{ editing }}:</span>
                <TagIconPicker
                    :key="editing"
                    :model-value="iconOf(editing)"
                    :available-icons="availableIcons"
                    @update:model-value="(icon) => setIcon(editing, icon)"
                />
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
            :existing="[...known]"
            :models="lockedModels"
            lock-model
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
        ]" :key="index">
            <template v-if="fieldName">
                <template v-for="tag in tags" :key="`${fieldName}-${tag}`">
                    <input v-if="details[tag]" type="hidden" :name="`${fieldName}[${tag}]`" :value="details[tag]" />
                </template>
            </template>
        </template>
    </div>
</template>
