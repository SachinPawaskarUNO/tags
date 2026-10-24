import {
    Bone,
    Car,
    Circle,
    CircleAlert,
    CircleDashed,
    CircleDashedCheck,
    Eye,
    File,
    FingerprintPattern,
    Flag,
    Folder,
    Heart,
    MapPin,
    Microscope,
    ShieldAlert,
    Shovel,
    Square,
    Star,
    Triangle,
} from '@lucide/vue';

// Every icon the package supports, by name. Keep in sync with Tag::ICONS.
// Which ones users can pick is set by config('tags.icons'), passed to TagInput's available-icons.
// To support another icon, import it from @lucide/vue (https://lucide.dev/icons) and add it here.
export const tagIcons = {
    file: File,
    folder: Folder,
    star: Star,
    'shield-alert': ShieldAlert,
    'circle-alert': CircleAlert,
    bone: Bone,
    shovel: Shovel,
    car: Car,
    'map-pin': MapPin,
    eye: Eye,
    'fingerprint-pattern': FingerprintPattern,
    flag: Flag,
    microscope: Microscope,
    circle: Circle,
    'circle-dashed': CircleDashed,
    'circle-dashed-check': CircleDashedCheck,
    triangle: Triangle,
    square: Square,
    heart: Heart,
    // Add supported icons here, and their names to Tag::ICONS.
};

// The [name, component] pairs users can pick: the supported icons that are also in `available`
// (from TagManager::icons()). Without `available`, every supported icon.
export function pickableIcons(available = null) {
    return Object.entries(tagIcons).filter(([name]) => available === null || available.includes(name));
}
