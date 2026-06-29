import { configureEcho } from '@laravel/echo-vue'
import api from './useApi'

/**
 * Configure the global Laravel Echo client for Laravel Reverb (FR-017).
 *
 * Called once at app start (main.ts). Per-component subscriptions are managed
 * with `useEcho` from `@laravel/echo-vue`, which lazily instantiates Echo on
 * first use and tears subscriptions down on unmount.
 *
 * Private-channel authorization is delegated to the app's axios instance so the
 * Sanctum bearer token (and its 401 handling) is applied to the auth request
 * automatically. The request targets `/api/broadcasting/auth` because the axios
 * baseURL already includes `/api` (see bootstrap/app.php withBroadcasting).
 */
export function setupEcho(): void {
  configureEcho({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    // `data` is Pusher's ChannelAuthorizationData (not cleanly exported from the
    // top-level pusher-js entry); typed loosely at this third-party boundary.
    authorizer: (channel: { name: string }) => ({
      authorize: (socketId: string, callback: (error: Error | null, data: any) => void) => {
        api
          .post('/broadcasting/auth', {
            socket_id: socketId,
            channel_name: channel.name,
          })
          .then((response) => callback(null, response.data))
          .catch((error: unknown) =>
            callback(error instanceof Error ? error : new Error('Channel authorization failed'), null),
          )
      },
    }),
  })
}
