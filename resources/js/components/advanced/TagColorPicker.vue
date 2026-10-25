<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger, SliderRoot, SliderThumb, SliderTrack } from 'reka-ui'

// A color picker built on Reka UI (https://reka-ui.com)

type Hsl = [number, number, number]

// A hex color, e.g. '#eb5e41'. Anything else, like null or a palette name, shows as gray.
const color = defineModel<string | null>({ default: null })

const GRAY = '#6b7280'

// Quick picks, matching the palette in tagColors.js.
const presets = ['#6b7280', '#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#a855f7']

const isHex = (value: unknown): value is string => typeof value === 'string' && /^#[0-9a-f]{6}$/i.test(value)

// Hex to HSL and back, following the CSS Color spec (https://www.w3.org/TR/css-color-4/#rgb-to-hsl).
function hexToHsl(hex: string): Hsl {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255)
  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  const l = (max + min) / 2

  if (max === min) {
    return [0, 0, Math.round(l * 100)]
  }

  const d = max - min
  const s = l > 0.5 ? d / (2 - max - min) : d / (max + min)
  const h = max === r ? (g - b) / d + (g < b ? 6 : 0) : max === g ? (b - r) / d + 2 : (r - g) / d + 4

  return [Math.round(h * 60), Math.round(s * 100), Math.round(l * 100)]
}

function hslToHex([h, s, l]: Hsl): string {
  const a = (s / 100) * Math.min(l / 100, 1 - l / 100)
  const channel = (n: number) => {
    const k = (n + h / 30) % 12
    const value = l / 100 - a * Math.max(-1, Math.min(k - 3, 9 - k, 1))

    return Math.round(value * 255).toString(16).padStart(2, '0')
  }

  return `#${channel(0)}${channel(8)}${channel(4)}`
}

const shown = computed(() => (isHex(color.value) ? color.value.toLowerCase() : GRAY))

// The sliders' values
const hsl = ref<Hsl>(hexToHsl(shown.value))

watch(color, (value) => {
  if (isHex(value) && value.toLowerCase() !== hslToHex(hsl.value)) {
    hsl.value = hexToHsl(value)
  }
})

// What's typed in the hex box. It's only saved once it's a full hex color.
const typed = ref(shown.value)

watch(shown, (value) => {
  typed.value = value
})

const channels = computed(() => {
  const [h, s, l] = hsl.value

  return [
    { label: 'Hue', index: 0, max: 360, track: 'linear-gradient(to right, #f00, #ff0, #0f0, #0ff, #00f, #f0f, #f00)' },
    { label: 'Saturation', index: 1, max: 100, track: `linear-gradient(to right, hsl(${h} 0% ${l}%), hsl(${h} 100% ${l}%))` },
    { label: 'Lightness', index: 2, max: 100, track: `linear-gradient(to right, #000, hsl(${h} ${s}% 50%), #fff)` },
  ]
})

function setChannel(index: number, value: number) {
  const next: Hsl = [...hsl.value]
  next[index] = value
  hsl.value = next
  color.value = hslToHex(next)
}

function setColor(value: string) {
  if (isHex(value)) {
    color.value = value.toLowerCase()
  }
}
</script>

<template>
  <PopoverRoot>
    <PopoverTrigger as-child>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-md border border-input px-2 py-1 font-mono text-sm uppercase hover:bg-accent hover:text-accent-foreground"
      >
        <span class="size-4 rounded-sm border border-input" :style="{ backgroundColor: shown }" />
        {{ shown }}
      </button>
    </PopoverTrigger>

    <PopoverPortal>
      <PopoverContent
        align="start"
        :side-offset="4"
        class="z-50 w-64 space-y-3 rounded-md border border-input bg-popover p-3 text-popover-foreground shadow-md"
      >
        <div v-for="channel in channels" :key="channel.label" class="space-y-1">
          <div class="flex justify-between text-xs text-muted-foreground">
            <span>{{ channel.label }}</span>
            <span>{{ hsl[channel.index] }}</span>
          </div>
          <SliderRoot
            :model-value="[hsl[channel.index]]"
            :max="channel.max"
            :step="1"
            class="relative flex h-4 w-full touch-none select-none items-center"
            @update:model-value="(value?: number[]) => value && setChannel(channel.index, value[0])"
          >
            <SliderTrack class="relative h-2 w-full grow rounded-full" :style="{ background: channel.track }" />
            <SliderThumb
              :aria-label="channel.label"
              class="block size-4 rounded-full border-2 border-white shadow ring-1 ring-black/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              :style="{ backgroundColor: shown }"
            />
          </SliderRoot>
        </div>

        <div class="flex flex-wrap gap-1.5">
          <button
            v-for="preset in presets"
            :key="preset"
            type="button"
            :aria-label="preset"
            :aria-pressed="shown === preset"
            :class="[
              'size-6 rounded-full border border-input',
              shown === preset ? 'ring-2 ring-ring ring-offset-2 ring-offset-background' : '',
            ]"
            :style="{ backgroundColor: preset }"
            @click="setColor(preset)"
          />
        </div>

        <input
          v-model="typed"
          type="text"
          maxlength="7"
          aria-label="Hex color"
          class="w-full rounded-md border border-input bg-transparent px-2 py-1 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-ring/50"
          @input="(event) => setColor((event.target as HTMLInputElement).value)"
        />
      </PopoverContent>
    </PopoverPortal>
  </PopoverRoot>
</template>
