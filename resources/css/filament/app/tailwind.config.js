import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    safelist: [
        // Margin/display classes used in seeder data (CSV content like CJIS policy)
        'ml-4',
        'ml-8',
        'block',
        'hidden',
        'text-sm',
        'text-lg',
        'font-medium',
        'font-semibold',
        'font-bold',
         // Ensure grcblue colors are never purged
        'bg-grcblue-200',
        'bg-grcblue-400',
        'bg-grcblue-500',
        'bg-grcblue-600',
        'bg-grcblue-700',
        'bg-grcblue-800',
        'text-grcblue-400',
        'text-grcblue-800',
        'hover:bg-grcblue-600',
        'hover:bg-grcblue-700',
        // Pattern matching for any grcblue variations
        {
            pattern: /(bg|text|border)-(grcblue)-(100|200|300|400|500|600|700|800|900)/,
            variants: ['hover', 'focus', 'active'],
        },
        // Spinning icon for export button (arbitrary variant targeting .fi-btn-icon)
        '[&_.fi-btn-icon]:animate-spin',
    ],
}
