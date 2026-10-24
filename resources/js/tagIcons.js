import { shallowRef } from 'vue';

// Every Lucide icon (https://lucide.dev/icons), by component name, e.g. MapPin. They're loaded
// the first time an icon is shown, so pages without tag icons don't download them.
const lucide = shallowRef(null);
let loading = null;

export function loadTagIcons() {
    loading ??= import('@lucide/vue').then((module) => {
        lucide.value = module.icons;
    });

    return loading;
}

// Icon names are written like on lucide.dev, e.g. 'map-pin'. Matches Tag::isValidIcon() on the server.
const NAME = /^[a-z0-9]+(-[a-z0-9]+)*$/;

// 'map-pin' to its component name, 'MapPin'.
const componentName = (name) =>
    name
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');

// And back, for listing every icon: 'MapPin' to 'map-pin', 'Grid2x2' to 'grid-2x2'.
const iconName = (component) =>
    component.replace(/(?<=[a-z])(?=[A-Z])|(?<=\d)(?=[A-Z])|(?<=[A-Za-z])(?<!\dx)(?=\d)/g, '-').toLowerCase();

// An icon's component by name, e.g. tagIcon('map-pin'). Null while the icons load, or if
// Lucide has no icon by that name.
export function tagIcon(name) {
    if (typeof name !== 'string' || !NAME.test(name)) {
        return null;
    }

    loadTagIcons();

    return lucide.value?.[componentName(name)] ?? null;
}

// Icon components by name, for templates and TagList's icons prop, e.g. tagIcons['map-pin'].
export const tagIcons = new Proxy({}, { get: (target, name) => tagIcon(name) ?? undefined });

// The [name, component] pairs users can pick: the icons in `available` (from TagManager::icons()),
// or without it, every Lucide icon once they've loaded.
export function pickableIcons(available = null) {
    if (available !== null) {
        return available.map((name) => [name, tagIcon(name)]).filter(([, icon]) => icon !== null);
    }

    loadTagIcons();

    return Object.entries(lucide.value ?? {}).map(([component, icon]) => [iconName(component), icon]);
}
