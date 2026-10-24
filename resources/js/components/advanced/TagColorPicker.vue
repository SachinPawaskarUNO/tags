<script setup lang="ts">
import { ColorPicker, ColorPickerArea, ColorPickerAreaThumb, ColorPickerContent, ColorPickerControl, ColorPickerEyeDropperTrigger, ColorPickerInput, ColorPickerSlider, ColorPickerSwatchPreview, ColorPickerTrigger, ColorPickerView } from "@/components/ui/color-picker"
import { InputGroup, InputGroupAddon, InputGroupInput } from "@/components/ui/input-group"

// A hex color, e.g. '#eb5e41'. Anything else, like null or a palette name, starts at gray.
const color = defineModel<string | null>({ default: null })

const isHex = (value: string | null) => typeof value === "string" && /^#[0-9a-f]{6}$/i.test(value)
</script>

<template>
  <ColorPicker
    class="w-full max-w-xs"
    :defaultValue="isHex(color) ? color! : '#6b7280'"
    format="hsla"
    :positioning='{ placement: "bottom-start" }'
    @value-change-end="(details: any) => (color = details.value.toString('hex').toLowerCase())"
  >
    <ColorPickerControl>
      <InputGroup>
        <ColorPickerTrigger :asChild="true">
          <InputGroupAddon><ColorPickerSwatchPreview /></InputGroupAddon>
        </ColorPickerTrigger>
        <ColorPickerInput :asChild="true">
          <InputGroupInput class="font-mono uppercase" />
        </ColorPickerInput>
      </InputGroup>
    </ColorPickerControl>
    <ColorPickerContent>
      <ColorPickerArea><ColorPickerAreaThumb /></ColorPickerArea>
      <ColorPickerView format="hsla">
        <div class="flex items-center gap-2">
          <ColorPickerEyeDropperTrigger />
          <div class="flex min-w-0 flex-1 flex-col gap-2">
            <ColorPickerSlider channel="hue" />
          </div>
        </div>
      </ColorPickerView>
    </ColorPickerContent>
  </ColorPicker>
</template>
