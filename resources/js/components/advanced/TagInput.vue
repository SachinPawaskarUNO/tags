<script>
// Gives each TagInput's dropdown its own id, for aria-controls.
let nextId = 0;
</script>

<script setup>
import { computed, ref, watch } from 'vue';
import { chipClass, chipStyle } from '../../tagColors.js';
import { tagIcons } from '../../tagIcons.js';
import TagColorPicker from './TagColorPicker.vue';

// TagInput for advanced mode: suggestions grouped by model in a dropdown, any color
// from TagColorPicker, and an icon from tagIcons.js. Needs the installs in the README's
// advanced mode section.

// Attributes like id and aria-label go on the text input, so <label for="..."> works.
defineOptions({ inheritAttrs: false });

const props = defineProps({
    // Tag names to suggest, grouped by model, e.g. { Post: ['pottery'] } from TagManager::suggestions().
    // A plain list shows in the dropdown without headings.
    suggestions: { type: [Array, Object], default: () => [] },
    // When set, renders hidden inputs so the tags submit with a regular form.
    name: { type: String, default: null },
    // When set, renders hidden inputs so the colors submit with a regular form.
    colorsName: { type: String, default: null },
    // When set, renders hidden inputs so the icons submit with a regular form.
    iconsName: { type: String, default: null },
    // The icon names users can pick, from TagManager::icons(). Without it, every icon in tagIcons.js shows.
    availableIcons: { type: Array, default: null },
    placeholder: { type: String, default: 'Add a tag...' },
    // Keep in sync with the app's validation, e.g. 'tags.*' => 'max:32'.
    maxLength: { type: Number, default: 32 },
});

const tags = defineModel({ type: Array, default: () => [] });

// Colors by tag name, e.g. { pottery: '#eb5e41' }. Save them with TagManager::setColors().
const colors = defineModel('colors', { type: Object, default: () => ({}) });

// Icons by tag name, e.g. { pottery: 'file' }. Save them with TagManager::setIcons().
const icons = defineModel('icons', { type: Object, default: () => ({}) });

const input = ref('');

// The tag whose color and icon are being picked, if any.
const editing = ref(null);

const listboxId = `tag-suggestions-${nextId++}`;

const open = ref(false);

// The index of the dropdown option picked with the arrow keys, or -1 for none.
const highlighted = ref(-1);

// Match Tag::normalizeName() on the server.
const normalize = (name) => name.trim().toLowerCase();

// The dropdown's groups of matching suggestions, without tags already added. A plain list
// is one group with no heading. Each option gets an index for the arrow keys.
const groups = computed(() => {
    const search = normalize(input.value);
    const grouped = Array.isArray(props.suggestions) ? { '': props.suggestions } : props.suggestions;
    let index = 0;

    return Object.entries(grouped)
        .map(([label, names]) => ({
            label,
            options: [...new Set(names.map(normalize))]
                .filter((suggestion) => !tags.value.includes(suggestion) && suggestion.includes(search))
                .map((suggestion) => ({ name: suggestion, index: index++ })),
        }))
        .filter((group) => group.options.length > 0);
});

const options = computed(() => groups.value.flatMap((group) => group.options));

// The icons shown in the picker: the supported ones in tagIcons.js that are also enabled.
const pickableIcons = computed(() =>
    Object.entries(tagIcons).filter(([iconName]) => props.availableIcons === null || props.availableIcons.includes(iconName)),
);

watch(input, () => {
    highlighted.value = -1;
});

function addTag(name) {
    const tag = normalize(name);

    if (tag !== '' && !tags.value.includes(tag)) {
        tags.value = [...tags.value, tag];
    }

    input.value = '';
    highlighted.value = -1;
}

function removeTag(tag) {
    tags.value = tags.value.filter((existing) => existing !== tag);

    const { [tag]: removedColor, ...otherColors } = colors.value;
    colors.value = otherColors;

    const { [tag]: removedIcon, ...otherIcons } = icons.value;
    icons.value = otherIcons;

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

function moveHighlight(step) {
    if (options.value.length === 0) {
        return;
    }

    open.value = true;
    highlighted.value = (highlighted.value + step + options.value.length) % options.value.length;
}

// Enter or comma adds the highlighted suggestion, or the typed tag. The arrow keys move
// through the dropdown and Escape closes it. Backspace in an empty input removes the last tag.
function onKeydown(event) {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        addTag(open.value && highlighted.value >= 0 ? options.value[highlighted.value].name : input.value);
    } else if (event.key === 'ArrowDown') {
        event.preventDefault();
        moveHighlight(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        moveHighlight(-1);
    } else if (event.key === 'Escape') {
        open.value = false;
        highlighted.value = -1;
    } else if (event.key === 'Backspace' && input.value === '' && tags.value.length > 0) {
        removeTag(tags.value[tags.value.length - 1]);
    }
}
</script>

<template>
    <div class="space-y-2">
        <div class="relative">
            <div
                class="flex flex-wrap items-center gap-2 rounded-md border border-input px-2 py-1.5 focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
            >
                <span
                    v-for="tag in tags"
                    :key="tag"
                    :class="['inline-flex items-center gap-1 rounded-full text-sm', chipClass(colors[tag])]"
                    :style="chipStyle(colors[tag])"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 py-1 pl-3"
                        :aria-label="`Change the color and icon of ${tag}`"
                        :aria-expanded="editing === tag"
                        @click="toggleEditing(tag)"
                    >
                        <component :is="tagIcons[icons[tag]]" v-if="tagIcons[icons[tag]]" class="size-3.5" aria-hidden="true" />
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
                    role="combobox"
                    autocomplete="off"
                    aria-autocomplete="list"
                    :aria-expanded="open && options.length > 0"
                    :aria-controls="listboxId"
                    :aria-activedescendant="open && highlighted >= 0 ? `${listboxId}-${highlighted}` : undefined"
                    class="min-w-32 flex-1 border-0 bg-transparent p-1 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-0"
                    :placeholder="placeholder"
                    :maxlength="maxLength"
                    @focus="open = true"
                    @input="open = true"
                    @blur="open = false"
                    @keydown="onKeydown"
                />
            </div>

            <div
                v-if="open && options.length > 0"
                :id="listboxId"
                role="listbox"
                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-input bg-popover p-1 text-sm text-popover-foreground shadow-md"
            >
                <div
                    v-for="group in groups"
                    :key="group.label"
                    role="group"
                    :aria-labelledby="group.label ? `${listboxId}-group-${group.label}` : undefined"
                >
                    <div
                        v-if="group.label"
                        :id="`${listboxId}-group-${group.label}`"
                        class="px-2 py-1 text-xs font-medium text-muted-foreground"
                    >
                        {{ group.label }}
                    </div>
                    <!-- mousedown.prevent keeps focus in the input, so the dropdown stays open. -->
                    <div
                        v-for="option in group.options"
                        :id="`${listboxId}-${option.index}`"
                        :key="option.name"
                        role="option"
                        :aria-selected="highlighted === option.index"
                        :class="[
                            'cursor-pointer rounded-sm px-2 py-1.5',
                            highlighted === option.index ? 'bg-accent text-accent-foreground' : '',
                        ]"
                        @mousedown.prevent="addTag(option.name)"
                        @mouseenter="highlighted = option.index"
                    >
                        {{ option.name }}
                    </div>
                </div>
            </div>
        </div>

        <div v-if="editing" class="space-y-2 text-sm">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-muted-foreground">Color for {{ editing }}:</span>
                <TagColorPicker
                    :key="editing"
                    :model-value="colors[editing]"
                    @update:model-value="(color) => setColor(editing, color)"
                />
            </div>

            <div v-if="pickableIcons.length > 0" class="flex flex-wrap items-center gap-2">
                <span class="text-muted-foreground">Icon for {{ editing }}:</span>
                <button
                    type="button"
                    :aria-pressed="!icons[editing]"
                    :class="[
                        'rounded-md border border-input px-2 py-1 hover:bg-accent hover:text-accent-foreground',
                        !icons[editing] ? 'bg-accent text-accent-foreground' : '',
                    ]"
                    @click="setIcon(editing, null)"
                >
                    None
                </button>
                <button
                    v-for="[iconName, icon] in pickableIcons"
                    :key="iconName"
                    type="button"
                    :aria-label="iconName"
                    :aria-pressed="icons[editing] === iconName"
                    :class="[
                        'rounded-md border border-input p-1.5 hover:bg-accent hover:text-accent-foreground',
                        icons[editing] === iconName ? 'bg-accent text-accent-foreground' : '',
                    ]"
                    @click="setIcon(editing, iconName)"
                >
                    <component :is="icon" class="size-4" aria-hidden="true" />
                </button>
            </div>
        </div>

        <template v-if="name">
            <input v-for="tag in tags" :key="tag" type="hidden" :name="`${name}[]`" :value="tag" />
        </template>

        <template v-if="colorsName">
            <template v-for="tag in tags" :key="`color-${tag}`">
                <input v-if="colors[tag]" type="hidden" :name="`${colorsName}[${tag}]`" :value="colors[tag]" />
            </template>
        </template>

        <template v-if="iconsName">
            <template v-for="tag in tags" :key="`icon-${tag}`">
                <input v-if="icons[tag]" type="hidden" :name="`${iconsName}[${tag}]`" :value="icons[tag]" />
            </template>
        </template>
    </div>
</template>
