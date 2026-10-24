import { File, Folder } from 'lucide-vue-next';

// Every icon the package supports, by name. Keep in sync with Tag::ICONS.
// Which ones users can pick is set by config('tags.icons'), passed to TagInput's available-icons.
// To support another icon, import it from lucide-vue-next (https://lucide.dev/icons) and add it here.
export const tagIcons = {
    file: File,
    folder: Folder,
    // Add supported icons here, and their names to Tag::ICONS.
};
