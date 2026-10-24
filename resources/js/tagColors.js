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

// Inline styles for a hex color from the color picker
export function chipStyle(color) {
    if (!/^#[0-9a-f]{6}$/i.test(color ?? '')) {
        return undefined;
    }

    const [r, g, b] = [1, 3, 5].map((i) => parseInt(color.slice(i, i + 2), 16));
    const light = (r * 299 + g * 587 + b * 114) / 1000 > 150;

    return { backgroundColor: color, color: light ? '#000' : '#fff' };
}
