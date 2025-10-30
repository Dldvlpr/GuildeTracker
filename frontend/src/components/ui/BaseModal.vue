<template>
  <Teleport to="body">
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="modelValue" class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
        <!-- Backdrop with blur -->
        <div
          class="fixed inset-0 bg-black/50 backdrop-blur-sm"
          @click="onBackdrop"
          aria-hidden="true"
        />

        <!-- Modal Dialog -->
        <transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="modelValue"
            :class="['relative z-[1001] w-full rounded-2xl border border-white/10 bg-slate-900/95 backdrop-blur text-slate-100 shadow-2xl', sizeClasses]"
            role="dialog"
            :aria-labelledby="title ? dialogTitleId : undefined"
            aria-modal="true"
            ref="dialogEl"
            @keydown.esc.prevent="onEsc"
            @keydown.tab.prevent="trapFocus"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 pt-6 pb-4">
              <h2 v-if="title" :id="dialogTitleId" class="text-xl font-bold">{{ title }}</h2>
              <button
                type="button"
                class="ml-auto inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:text-white hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                aria-label="Close"
                @click="close()"
                ref="closeBtn"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-2">
              <slot />
            </div>

            <!-- Footer -->
            <div v-if="$slots.footer" class="px-6 pb-6 pt-4 border-t border-white/10">
              <slot name="footer" />
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, computed, nextTick, getCurrentInstance, onUnmounted } from 'vue'

const props = withDefaults(defineProps<{
  modelValue: boolean
  title?: string
  closeOnEsc?: boolean
  closeOnBackdrop?: boolean
  size?: 'sm' | 'md' | 'lg' | 'xl'
}>(), {
  closeOnEsc: true,
  closeOnBackdrop: true,
  size: 'md'
})

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'open'): void
  (e: 'close'): void
}>()

const dialogEl = ref<HTMLElement | null>(null)
const closeBtn = ref<HTMLButtonElement | null>(null)
const previouslyFocused = ref<HTMLElement | null>(null)
const uid = (getCurrentInstance()?.uid ?? Math.floor(Math.random() * 1e6)).toString()
const dialogTitleId = computed(() => `dialog-title-${uid}`)

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl'
  }
  return sizes[props.size]
})

function close() {
  emit('update:modelValue', false)
}

function onEsc() {
  if (props.closeOnEsc) close()
}

function onBackdrop() {
  if (props.closeOnBackdrop) close()
}

function trapFocus() {
  if (!props.modelValue || !dialogEl.value) return
  const focusables = dialogEl.value.querySelectorAll<HTMLElement>(
    'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
  )
  if (!focusables.length) return
  const first = focusables[0]
  const last = focusables[focusables.length - 1]
  const active = document.activeElement as HTMLElement | null
  const goingBackward = (window.event as KeyboardEvent)?.shiftKey
  if (goingBackward && (active === first || !active)) { last.focus(); return }
  if (!goingBackward && active === last) { first.focus(); return }
}

watch(() => props.modelValue, async (isOpen) => {
  if (isOpen) {
    previouslyFocused.value = document.activeElement as HTMLElement | null
    emit('open')
    document.documentElement.style.overflow = 'hidden'
    await nextTick()
    if (closeBtn.value) closeBtn.value.focus()
  } else {
    emit('close')
    document.documentElement.style.overflow = ''
    previouslyFocused.value?.focus?.()
  }
})

onUnmounted(() => {
  document.documentElement.style.overflow = ''
})
</script>

