// The colors a tag can have. Keep in sync with Tag::COLORS in src/Models/Tag.php.
// Class names are written out in full so Tailwind includes them in the build.
export const tagColors = {
    gray: { chip: 'bg-muted text-muted-foreground', swatch: 'bg-gray-400' },
    red: { chip: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200', swatch: 'bg-red-500' },
    orange: { chip: 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-200', swatch: 'bg-orange-500' },
    yellow: { chip: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-200', swatch: 'bg-yellow-400' },
    green: { chip: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200', swatch: 'bg-green-500' },
    blue: { chip: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200', swatch: 'bg-blue-500' },
    purple: { chip: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-200', swatch: 'bg-purple-500' },
};

// Chip classes for a color. Tags without a color are gray.
export function chipClass(color) {
    return (tagColors[color] ?? tagColors.gray).chip;
}
