import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const config = window.documentRealtimeConfig;

if (config?.userId && config?.key) {
  window.Pusher = Pusher;

  window.Echo = new Echo({
    broadcaster: 'reverb',
    key: config.key,
    wsHost: config.host,
    wsPort: Number(config.port || 80),
    wssPort: Number(config.port || 443),
    forceTLS: config.scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: config.authEndpoint,
    auth: {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      }
    }
  });

  const connection = window.Echo.connector.pusher.connection;
  connection.bind('connected', () => {
    window.documentRealtimeConnected = true;
    window.refreshDocumentNotifications?.();
  });
  connection.bind('disconnected', () => {
    window.documentRealtimeConnected = false;
  });
  connection.bind('error', () => {
    window.documentRealtimeConnected = false;
  });

  window.Echo.private(`users.${config.userId}`)
    .listen('.documents.updated', () => {
      window.refreshDocumentNotifications?.();
    });
}
