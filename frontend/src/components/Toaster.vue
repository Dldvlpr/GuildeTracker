<template>
  <div class="notifications" role="region" aria-label="Notifications">
    <div
      v-for="n in items"
      :key="n.id"
      class="notification"
      :class="n.type"
      role="status"
      aria-live="polite"
    >
      {{ n.message }}
    </div>
  </div>
</template>

<script setup lang="ts">
defineOptions({ name: 'AppToaster' })
type ToastType = 'success' | 'error' | 'warning' | 'info'
interface Notification {
  id: string
  message: string
  type: ToastType
}

defineProps<{ items: Notification[] }>()
</script>

<style scoped>
.notifications {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.notification {
  padding: 1rem 1.25rem;
  border-radius: 10px;
  color: white;
  font-weight: 600;
  min-width: 300px;
  animation: slideIn 0.25s ease;
}
.notification.success {
  background: #10b981;
}
.notification.error {
  background: #ef4444;
}
.notification.warning {
  background: #f59e0b;
}
.notification.info {
  background: #3b82f6;
}
@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
@media (max-width: 768px) {
  .notifications {
    left: 1rem;
    right: 1rem;
  }
  .notification {
    min-width: auto;
  }
}
</style>
