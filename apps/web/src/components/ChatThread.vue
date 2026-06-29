<script setup lang="ts">
import { ref, onMounted, nextTick, computed } from 'vue'
import { useEcho } from '@laravel/echo-vue'
import { MessageSquare, Send, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import { useChat } from '../composables/useChat'
import type { ChatMessage } from '../types'

const props = defineProps<{
  applicationId: string
  counterpartName?: string
}>()

const auth = useAuthStore()
const { loading, sending, error, fetchMessages, sendMessage } = useChat()

const messages = ref<ChatMessage[]>([])
const draft = ref('')
const listRef = ref<HTMLElement | null>(null)

const currentUserId = computed(() => auth.user?.id ?? null)

function isMine(message: ChatMessage): boolean {
  return message.sender_id === currentUserId.value
}

function scrollToBottom(): void {
  nextTick(() => {
    if (listRef.value) {
      listRef.value.scrollTop = listRef.value.scrollHeight
    }
  })
}

/**
 * Append a message unless we already have it (the sender receives their own
 * broadcast in addition to the POST response — dedupe by id).
 */
function appendMessage(message: ChatMessage): void {
  if (messages.value.some((m) => m.id === message.id)) {
    return
  }
  messages.value = [...messages.value, message]
  scrollToBottom()
}

// Real-time delivery: a message sent by the other party appears here without a
// page reload (docs/DESIGN-SYSTEM.md Section 6.1 — purposeful real-time feedback).
useEcho(`chat.${props.applicationId}`, 'MessageSent', (payload: ChatMessage) => {
  appendMessage(payload)
})

onMounted(async () => {
  messages.value = await fetchMessages(props.applicationId)
  scrollToBottom()
})

async function send(): Promise<void> {
  const content = draft.value.trim()
  if (content === '' || sending.value) {
    return
  }

  const sent = await sendMessage(props.applicationId, content)
  if (sent) {
    draft.value = ''
    appendMessage(sent)
  }
}

function formatTime(iso: string): string {
  return new Date(iso).toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <section class="chat" aria-label="Percakapan dengan tim rekrutmen">
    <header class="chat__header">
      <MessageSquare :size="18" class="chat__header-icon" />
      <div>
        <h2 class="chat__title">Percakapan</h2>
        <p v-if="counterpartName" class="chat__subtitle">dengan {{ counterpartName }}</p>
      </div>
    </header>

    <div ref="listRef" class="chat__body">
      <div v-if="loading" class="chat__state">Memuat percakapan...</div>

      <div v-else-if="messages.length === 0" class="chat__empty">
        <MessageSquare :size="32" class="chat__empty-icon" />
        <p class="chat__empty-text">Belum ada pesan. Mulai percakapan.</p>
      </div>

      <TransitionGroup v-else name="message" tag="div" class="chat__messages">
        <div
          v-for="message in messages"
          :key="message.id"
          class="message"
          :class="{ 'message--mine': isMine(message) }"
        >
          <div class="message__bubble">
            <span v-if="!isMine(message)" class="message__sender">
              {{ message.sender?.name ?? 'Tim Rekrutmen' }}
            </span>
            <p class="message__content">{{ message.content }}</p>
            <span class="message__time">{{ formatTime(message.sent_at) }}</span>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <form class="chat__composer" @submit.prevent="send">
      <input
        v-model="draft"
        type="text"
        class="chat__input"
        placeholder="Tulis pesan..."
        autocomplete="off"
        :disabled="sending"
      />
      <button
        type="submit"
        class="chat__send"
        :disabled="sending || draft.trim() === ''"
        aria-label="Kirim pesan"
      >
        <Loader2 v-if="sending" :size="18" class="spin" />
        <Send v-else :size="18" />
      </button>
    </form>

    <p v-if="error" class="chat__error">{{ error }}</p>
  </section>
</template>

<style scoped>
.chat {
  margin-top: 20px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.chat__header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--color-border);
}

.chat__header-icon {
  color: var(--color-primary);
  flex-shrink: 0;
}

.chat__title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0;
}

.chat__subtitle {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  margin: 2px 0 0;
}

.chat__body {
  height: 360px;
  overflow-y: auto;
  padding: 16px 20px;
  background: var(--color-background);
}

.chat__state,
.chat__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--color-text-secondary);
  gap: 8px;
}

.chat__empty-icon {
  color: var(--color-border);
}

.chat__empty-text {
  font-size: 0.875rem;
  margin: 0;
}

.chat__messages {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.message {
  display: flex;
  justify-content: flex-start;
}

.message--mine {
  justify-content: flex-end;
}

.message__bubble {
  max-width: 78%;
  padding: 8px 12px;
  border-radius: 12px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.message--mine .message__bubble {
  background: var(--color-primary);
  border-color: var(--color-primary);
}

.message__sender {
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--color-primary);
}

.message__content {
  font-size: 0.875rem;
  color: var(--color-text-primary);
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
}

.message--mine .message__content {
  color: #ffffff;
}

.message__time {
  font-size: 0.625rem;
  color: var(--color-text-secondary);
  align-self: flex-end;
}

.message--mine .message__time {
  color: rgba(255, 255, 255, 0.75);
}

.chat__composer {
  display: flex;
  gap: 8px;
  padding: 12px 16px;
  border-top: 1px solid var(--color-border);
  background: var(--color-surface);
}

.chat__input {
  flex: 1;
  padding: 10px 14px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-size: 0.875rem;
  font-family: inherit;
  color: var(--color-text-primary);
  background: var(--color-background);
}

.chat__input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.chat__send {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  border: none;
  border-radius: 8px;
  background: var(--color-primary);
  color: #ffffff;
  cursor: pointer;
  transition: background-color 0.15s;
  flex-shrink: 0;
}

.chat__send:hover:not(:disabled) {
  background: var(--color-primary-hover);
}

.chat__send:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.chat__error {
  margin: 0;
  padding: 8px 16px 12px;
  font-size: 0.8125rem;
  color: var(--color-error);
}

/* Subtle entrance for a newly arrived message — purposeful real-time feedback
   on compositor-friendly properties only (transform/opacity), per
   docs/DESIGN-SYSTEM.md Section 6.1. */
.message-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.message-enter-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .message-enter-active,
  .spin {
    transition: none;
    animation: none;
  }
}
</style>
