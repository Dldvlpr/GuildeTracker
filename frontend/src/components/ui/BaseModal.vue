<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-[1000] flex items-center justify-center">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="onBackdrop" />
        <div
          class="relative z-[1001] w-full max-w-lg mx-4 rounded-2xl bg-slate-900 text-slate-100 shadow-2xl ring-1 ring-white/10"
          role="dialog"
          :aria-labelledby="title ? dialogTitleId : undefined"
          aria-modal="true"
          ref="dialogEl"
          @keydown.esc.prevent="onEsc"
          @keydown.tab.prevent="trapFocus"
        >
          <div class="flex items-center justify-between px-5 pt-5">
            <h2 v-if="title" :id="dialogTitleId" class="text-lg font-semibold">{{ title }}</h2>
            <button
              type="button"
              class="ml-auto inline-flex items-center justify-center rounded-lg p-2 text-slate-300 hover:text-white hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              aria-label="Close"
              @click="close()"
              ref="closeBtn"
            >
              <span aria-hidden="true">✕</span>
            </button>
          </div>
          <div class="px-5 py-4">
            <slot />
          </div>
          <div v-if="$slots.footer" class="px-5 pb-5 pt-2">
            <slot name="footer" />
          </div>
        </div>
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
}>(), {
  closeOnEsc: true,
  closeOnBackdrop: true
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

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
